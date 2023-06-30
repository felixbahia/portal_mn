<?php

namespace App\Http\Controllers;

use App\ClienteNasajon;
use App\NotasNasajon;
use App\ProdutoEspecificacao;
use App\User;
use App\VendasSantista;
use App\VendasSantistaLog;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;

use App\Exports\VendasSantistaExport;

use App\Http\Controllers\EmailController;
use Illuminate\Http\Request;

use Auth;

use Carbon\Carbon;

class VendasSantistaController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\VendasSantista") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\VendasSantista');

        $fields = $request->only('estabelecimento', 'representante', 'data_inicio', 'data_fim', 'cliente', 'status');

        $representantesObj = User::select('codigo_representante', 'name')->whereNotNull('codigo_representante')->orderBy('codigo_representante', 'asc')->get();

        $representantes = [];

        $representantesObj->each(function ($linha) use(&$representantes){
            $representantes[$linha->codigo_representante] = $linha->codigo_representante . ' - ' . $linha->name;
        });

        $estabelecimentos = [];

        foreach(returnEmpresasNasajonView() as $key => $value){
            $estabelecimentos[str_pad($key, 2, '0', STR_PAD_LEFT)] = $value;
        }

        $filtro['estabelecimento'] = '';
        $filtro['representante'] = '';
        $filtro['data_inicio'] = '';
        $filtro['data_fim'] = '';
        $filtro['cliente'] = '';
        $filtro['status'] = '';

        if(isset($fields['estabelecimento'])){
            $filtro['estabelecimento'] = $fields['estabelecimento'];
        }

        if(isset($fields['representante'])){
            $filtro['representante'] = $fields['representante'];
        }

        if(isset($fields['data_inicio'])){
            $filtro['data_inicio'] = $fields['data_inicio'];
        }

        if(isset($fields['data_fim'])){
            $filtro['data_fim'] = $fields['data_fim'];
        }

        if(isset($fields['cliente'])){
            $filtro['cliente'] = $fields['cliente'];
        }

        if(isset($fields['status'])){
            $filtro['status'] = $fields['status'];
        }

        return view('programs.vendas_santista.index')->with(['estabelecimentos' => $estabelecimentos, 'representantes' => $representantes, 'filtro' => $filtro]);
    }

    public function filtro(Request $request){

        $fields = $request->only('estabelecimento', 'cliente', 'representante', 'data_inicio', 'data_fim', 'status');

        $vendasQuery = VendasSantista::with('representante', 'produto');

        if(isset($fields['estabelecimento']) && !empty($fields['estabelecimento'])){
            $vendasQuery->where('estabelecimento', $fields['estabelecimento']);
        }

        if(isset($fields['cliente']) && !empty($fields['cliente'])){
            $vendasQuery->where(DB::Raw("TRIM(CONCAT(TRIM(cliente_nome), ' - ', cliente_cnpj))"), 'ilike', "%" . $fields['cliente'] . "%");
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $vendasQuery->where('emissao', '>=', $data_inicio->format("Y-m-d"));
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $vendasQuery->where('emissao', '<=', $data_fim->format("Y-m-d"));
        }

        if(isset($fields['status']) && !empty($fields['status'])){
            $vendasQuery->where('confirmado', $fields['status']);
        }
        
        if(isset($fields['representante']) && !empty($fields['representante'])){

            $representante = $fields['representante'];
            $vendasQuery->whereHas('representante', function($query) use($representante){
                $query->where('codigo_representante', $representante);
            });

        }

        $vendasSantistaobj = $vendasQuery->get();

        $estabelecimentos = returnEmpresasNasajonView();

        $retorno = [];

        $vendasSantistaobj->each(function($linha) use (&$retorno, $estabelecimentos){

            $item = [];

            $item['estabelecimento'] = $estabelecimentos[intval($linha->estabelecimento)];

            $cliente = $linha->cliente_nome . ' - ' . $linha->cliente_cnpj;
            $representante = $linha->representante->codigo_representante . ' - ' . $linha->representante->name;
            
            $item['cliente'] = "<div><div data-toggle='tooltip' data-placement='top' data-trigger='hover' data-title='" . $cliente . "'>" . $cliente . "</div></div>";
            $item['representante'] = "<div><div data-toggle='tooltip' data-placement='top' data-trigger='hover' data-title='" . $representante . "'>" . $representante . "</div></div>";
            $item['serie'] = $linha->serie;
            $item['nota_id'] = $linha->nota_id;
            $item['nota'] = $linha->nota_fiscal;
            $item['emissao'] = parserData($linha->emissao);
            $item['produto'] = "<div><div data-toggle='tooltip' data-placement='top' data-trigger='hover' data-title='" . $linha->produto->codigo_produto . ' - ' . $linha->produto->descricao . "'>" . $linha->produto->descricao . "</div></div>";
            $item['quantidade'] = parserValor($linha->quantidade);
            $item['valor'] = parserValor($linha->valor);
            $item['status'] = ($linha->confirmado === true)? 'Confirmado': 'Não confirmado';

            $retorno[] = $item;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Dados recuperados com sucesso!',
            'error' => [],
            'response' => [
                'dados' => $retorno
            ]
        ], 200);
    }

    public function importacao(){

        $inicio = Carbon::now();
        
        $produtosSantista = ProdutoEspecificacao::select('codigo_produto')
            ->where('marca', 'ilike', "%santista%")
            ->get()
            ->pluck('codigo_produto');

        $notasObj = NotasNasajon::with(['itens_nota' => function($itens) use ($produtosSantista){
                $itens->whereIn('codigo', $produtosSantista);
            }, 'revisao_vendedor_comissao', 'pedido', 'pedido.cliente_detalhes'] )
            ->whereBetween('emissao', [Carbon::now()->subDays(2), Carbon::now()])
            ->where('operacao_codigo', 'like', 'VENDA%')
			->whereRaw("replace(replace(replace(cliente_documento, '.', ''), '-', ''), '/', '') NOT ILIKE '06311274%'")
			->whereRaw("replace(replace(replace(cliente_documento, '.', ''), '-', ''), '/', '') NOT ILIKE '05075884%'")
            ->whereHas('itens_nota', function($query) use ($produtosSantista){
                $query->whereIn('codigo', $produtosSantista);
            })
            ->get();

        VendasSantista::whereBetween('emissao', [Carbon::now()->subDays(2), Carbon::now()])->forceDelete();
        
        $insert = [];

        $sem_cliente = [];

        $notasObj->each(function ($linha) use(&$insert, &$sem_cliente){

            $estabelecimento = $linha->estabelecimento_codigo;
            $cliente_cnpj = $linha->cliente_documento;
            $cliente_nome = $linha->cliente_nome;
            $representante_codigo = $linha->revisao_vendedor_comissao->vendedor_codigo ?? null;
            $serie = $linha->serie;
            $nota_fiscal = $linha->numero;
            $emissao = $linha->emissao;
            $nota_id = $linha->id;

            $linha->itens_nota->each(function ($produto) use(&$insert, $estabelecimento, $cliente_cnpj, $cliente_nome, $representante_codigo, $serie, $nota_fiscal, $emissao, $nota_id){

                $insert[] = [
                    'estabelecimento' => $estabelecimento,
                    'cliente_cnpj' => $cliente_cnpj,
                    'cliente_nome' => $cliente_nome,
                    'representante_codigo' => $representante_codigo,
                    'serie' => $serie,
                    'nota_fiscal' => $nota_fiscal,
                    'nota_id' => $nota_id,
                    'emissao' => $emissao,
                    'codigo_produto' => $produto->codigo,
                    'quantidade' => round($produto->quantidadecomercial, 2),
                    'valor' => round($produto->valortotal, 2),
                    'confirmado' => false,
                    'created_by' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];

            });

        });

        VendasSantista::insert($insert);

        echo 'Demorou ' . $inicio->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;

    }


    public function primeiraImportacao(){

        $inicio = Carbon::now();
        
        $produtosSantista = ProdutoEspecificacao::select('codigo_produto')
            ->where('marca', 'ilike', "%santista%")
            ->get()
            ->pluck('codigo_produto');

        $notasObj = NotasNasajon::with(['itens_nota' => function($itens) use ($produtosSantista){
                $itens->whereIn('codigo', $produtosSantista);
            }, 'revisao_vendedor_comissao', 'pedido', 'pedido.cliente_detalhes'] )
            ->where('emissao', '>=', '2020-01-06')
            ->where('operacao_codigo', 'like', 'VENDA%')
			->whereRaw("replace(replace(replace(cliente_documento, '.', ''), '-', ''), '/', '') NOT ILIKE '06311274%'")
			->whereRaw("replace(replace(replace(cliente_documento, '.', ''), '-', ''), '/', '') NOT ILIKE '05075884%'")
            ->whereHas('itens_nota', function($query) use ($produtosSantista){
                $query->whereIn('codigo', $produtosSantista);
            })
            ->get();
        
        $insert = [];

        $sem_cliente = [];

        $notasObj->each(function ($linha) use(&$insert, &$sem_cliente){

            $estabelecimento = $linha->estabelecimento_codigo;
            $cliente_cnpj = $linha->cliente_documento;
            $cliente_nome = $linha->cliente_nome;
            $representante_codigo = $linha->revisao_vendedor_comissao->vendedor_codigo ?? null;
            $serie = $linha->serie;
            $nota_fiscal = $linha->numero;
            $emissao = $linha->emissao;
            $nota_id = $linha->id;

            $linha->itens_nota->each(function ($produto) use(&$insert, $estabelecimento, $cliente_cnpj, $cliente_nome, $representante_codigo, $serie, $nota_fiscal, $emissao, $nota_id){

                $insert[] = [
                    'estabelecimento' => $estabelecimento,
                    'cliente_cnpj' => $cliente_cnpj,
                    'cliente_nome' => $cliente_nome,
                    'representante_codigo' => $representante_codigo,
                    'serie' => $serie,
                    'nota_fiscal' => $nota_fiscal,
                    'nota_id' => $nota_id,
                    'emissao' => $emissao,
                    'codigo_produto' => $produto->codigo,
                    'quantidade' => round($produto->quantidadecomercial, 2),
                    'valor' => round($produto->valortotal, 2),
                    'confirmado' => false,
                    'created_by' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];

            });

        });
        VendasSantista::insert($insert);

        echo 'Demorou ' . $inicio->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;

    }

    public function validarNotaApi(Request $request){

        $log = new VendasSantistaLog;

        $fields = $request->only('nota', 'cnpj');

        $log->ip = $request->getClientIp();
        $log->nota = $fields['nota'];
        $log->cnpj = $fields['cnpj'];

        $vendasObj = VendasSantista::where('nota_fiscal', $fields['nota'])
        ->where(DB::Raw("replace(replace(replace(cliente_cnpj, '.', ''), '-', ''), '/', '')"), str_replace('.', '', str_replace('-', '', str_replace('/', '', $fields['cnpj']))))->get();

        if($vendasObj->isNotEmpty()){
            
            $nao_confirmado = $vendasObj->where('confirmado', false);

            if($nao_confirmado->isNotEmpty()){
                $vendasObj->each(function($venda){
                    $venda->confirmado = true;
                    $venda->save();    
                });

                $log->resultado = 'Vendas confirmadas';
                $log->save();
                
                return response()->json([
                    'status' => 'ok',
                    'validado' => 'Não'
                ], 200);
            }
            else{

                $log->resultado = 'Vendas já confirmadas anteriormente';
                $log->save();

                return response()->json([
                    'status' => 'ok',
                    'validado' => 'Sim'
                ], 200);
            }
        }
        else{

            $log->resultado = 'Vendas não encontradas';
            $log->save();

            return response()->json([
                'status' => 'erro',
                'validado' => 'Não'
            ], 422);
        }
    }

    public function indexSintetica(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\VendasSantistaSintetica") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\VendasSantistaSintetica');

        $representantesObj = User::select('codigo_representante', 'name')->whereNotNull('codigo_representante')->orderBy('codigo_representante', 'asc')->get();

        $representantes = [];

        $representantesObj->each(function ($linha) use(&$representantes){
            $representantes[$linha->codigo_representante] = $linha->codigo_representante . ' - ' . $linha->name;
        });

        return view('programs.vendas_santista.indexSintetica')->with(['representantes' => $representantes]);
    }

    public function filtroSintetica(Request $request){

        $fields = $request->only('data_inicio', 'data_fim', 'cliente', 'representante', 'status');

        $vendasSantistaQuery = VendasSantista::query();

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $vendasSantistaQuery->where('emissao', '>=', $data_inicio->format("Y-m-d"));
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $vendasSantistaQuery->where('emissao', '<=', $data_fim->format("Y-m-d"));
        }

        if(isset($fields['cliente']) && !empty($fields['cliente'])){
            $vendasSantistaQuery->where(DB::Raw('concat(trim(cliente_nome), \' - \', cliente_cnpj)'), 'ilike', '%'.$fields['cliente'].'%');
        }

        if(isset($fields['representante']) && !empty($fields['representante'])){
            $representante = $fields['representante'];
            $vendasSantistaQuery->where('representante_codigo', $representante);
        }

        if(isset($fields['status']) && !empty($fields['status'])){
            $vendasSantistaQuery->where('confirmado', $fields['status']);
        }

        $vendasSantistaObj = $vendasSantistaQuery->get();

        $result = [];

        $estabelecimentos = returnEmpresasNasajonView();

        $vendasSantistaObj->each(function ($venda) use(&$result, $estabelecimentos, $fields){

            if(!isset($result[$venda->estabelecimento])){

                $filtro = [];

                $filtro['estabelecimento'] = $venda->estabelecimento;

                if(isset($fields['cliente']) && !empty($fields['cliente'])){
                    $filtro['cliente'] = $fields['cliente'];
                }

                if(isset($fields['representante']) && !empty($fields['representante'])){
                    $filtro['representante'] = $fields['representante'];
                }

                if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
                    $filtro['data_inicio'] = $fields['data_inicio'];
                }

                if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
                    $filtro['data_fim'] = $fields['data_fim'];
                }

                if(isset($fields['status']) && !empty($fields['status'])){
                    $filtro['status'] = $fields['status'];
                }
                
                $result[$venda->estabelecimento] = [
                    'estabelecimento' => $estabelecimentos[intval($venda->estabelecimento)],
                    'clientes_arr' => [],
                    'representantes_arr' => [],
                    'clientes' => 0,
                    'representantes' => 0,
                    'valor_conferido' => 0,
                    'valor_nao_conferido' => 0,
                    'valor_total' => 0,
                    'hash' => Crypt::encrypt($filtro)
                ];
            }

            $result[$venda->estabelecimento]['clientes_arr'][] = $venda->cliente_cnpj . ' - ' . $venda->cliente_nome;

            $result[$venda->estabelecimento]['representantes_arr'][] = $venda->representante_codigo;

            if($venda->confirmado === true){
                $result[$venda->estabelecimento]['valor_conferido'] += $venda->valor;
            }
            else{
                $result[$venda->estabelecimento]['valor_nao_conferido'] += $venda->valor;
            }

            $result[$venda->estabelecimento]['valor_total'] += $venda->valor;

        });

        foreach ($result as $estabelecimento => $array){

            $filtro = [];

            $filtro['estabelecimento'] = $estabelecimento;

            if(isset($fields['representante']) && !empty($fields['representante'])){
                $filtro['representante'] = $fields['representante'];
            }

            if(isset($fields['cliente']) && !empty($fields['cliente'])){
                $filtro['cliente'] = $fields['cliente'];
            }

            if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
                $filtro['data_inicio'] = $fields['data_inicio'];
            }

            if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
                $filtro['data_fim'] = $fields['data_fim'];
            }

            if(isset($fields['status']) && !empty($fields['status'])){
                $filtro['status'] = $fields['status'];
            }

            $result[$estabelecimento]['clientes'] = count(array_unique($array['clientes_arr']));
            $result[$estabelecimento]['representantes'] = count(array_unique($array['representantes_arr']));

            $result[$estabelecimento]['valor_conferido'] = parserValor($array['valor_conferido']);
            $result[$estabelecimento]['valor_nao_conferido'] = parserValor($array['valor_nao_conferido']);
            $result[$estabelecimento]['valor_total'] = parserValor($array['valor_total']);
            $result[$estabelecimento]['link'] = route('vendas_santista.index', $filtro);

            unset($result[$estabelecimento]['clientes_arr'], $result[$estabelecimento]['representantes_arr']);

        }

        return response()->json([
            'status' => 'success',
            'message' => 'Dados recuperados com sucesso!',
            'error' => [],
            'response' => [
                'dados' => $result
            ]
        ], 200);

    }

    public function modalAnalitica(Request $request){
        try{
            $filtros = Crypt::decrypt($request->hash);
 
        } catch (Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro no processamento dos dados!',
                'error' => [],
                'response' => [],
            ], 200);
        }

        $requestFiltro = new Request($filtros);
        $result = $this->filtro($requestFiltro);

        $dados = json_decode($result->content(), true)['response']['dados'];

        foreach ($dados as $key => $linha){
            $dados[$key]['estabelecimento'] ="<div><div data-toggle='tooltip' data-placement='top' data-trigger='hover' data-title='" . $linha['estabelecimento'] . "'>" . $linha['estabelecimento'] . "</div></div>";
            $dados[$key]['status'] ="<div><div data-toggle='tooltip' data-placement='top' data-trigger='hover' data-title='" . $linha['status'] . "'>" . $linha['status'] . "</div></div>";
        }

        return view('programs.vendas_santista.modal.index')->with(['dados' => $dados]);
    }

    public function exportarCSV(){

        Excel::store(new VendasSantistaExport,'vendas_santista_' .date('d-m-Y') . '.csv');

        $EmailObj = new EmailController();
		$email_send = [];
        $variaveis = [
            'data' => date('d/m/Y')
        ];
        $returnEmail = $EmailObj->sendEmailToken('00', "vendas_santista_exportacao", $email_send, $variaveis, ['vendas_santista_' .date('d-m-Y') . '.csv' => ['as' => 'vendas_santista_' .date('d-m-Y') . '.csv', 'mime' => 'text/csv']], []);

        Storage::delete('vendas_santista_' .date('d-m-Y') . '.csv');

    }
}
