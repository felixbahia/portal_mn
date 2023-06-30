<?php

namespace App\Http\Controllers;

use Auth;

use App\User;
use Exception;

use Carbon\Carbon;
use App\UserNajason;
use App\AjusteEstoque;
use App\FracaoNasajon;
use App\ProdutoNasajon;
use App\LogColetorRomaneio;
use App\MotivoAjusteEstoque;
use Illuminate\Http\Request;
use App\AjusteEstoqueNasajon;
use App\LotesProdutosNasajon;
use App\ProdutoEspecificacao;
use App\LocalDeEstoqueNasajon;
use App\RastreabilidadeFracoesNasajon;

use App\NasajonEstabelecimento;

use Illuminate\Support\Facades\DB;

use App\LocalDeEstoqueEnderecoNasajon;
use App\Http\Controllers\EmailController;

use App\Http\Requests\AjusteEstoqueFilterRequest;
use App\Http\Requests\AjusteEstoqueDigitacaoRequest;

class AjusteEstoqueController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AjusteEstoqueController") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\AjusteEstoqueController');

        $estabelecimentos = returnEmpresasNasajonView();

        $tipos_ajuste = $this->getTipoEstoque();

        $data_inicial = Carbon::now()->subDays(15)->format("d/m/Y");
        $data_final = Carbon::now()->format("d/m/Y");

        $motivos = $this->getMotivos();

        return view('programs.ajuste_estoque.index')->with(['estabelecimentos' => $estabelecimentos, 'tipos_ajuste' => $tipos_ajuste, 'data_inicial' => $data_inicial, 'data_final' => $data_final, 'motivos' => $motivos]);
    }

    private function getTipoEstoque(){
        return [
            '' => 'Todos',
            'Entrada' => 'Entrada',
            'Saída' => 'Saída',
            'Fracionamento' => 'Fracionamento',
            'Desfazer Fracionamento' => 'Desfazer Fracionamento',
            'Separação' => 'Separação',
            'Armazenamento' => 'Armazenamento',
            'Reclassificacao de Fração' => 'Reclassificacao de Fração',
            'Inventário' => 'Inventário',
            'Acerto de Saldo' => 'Acerto de Saldo',
        ];
    }

    public function filter(AjusteEstoqueFilterRequest $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('estabelecimento', 'data_de', 'data_ate', 'motivo', 'tipo', 'codigo_produto', 'descricao_produto', 'marca_produto', 'linha_produto', 'grupo_produto', 'subgrupo_produto','transferencia','ajuste');
        
        $query = AjusteEstoqueNasajon::where('codigo_estabelecimento','<>','20');

        if(!empty($fields['data_de'])){
            $data_de = Carbon::CreateFromFormat("d/m/Y", $fields['data_de']);
            $query->where('data', '>=', $data_de);
        }
        if(!empty($fields['data_ate'])){
            $data_ate = Carbon::CreateFromFormat("d/m/Y", $fields['data_ate']);
            $query->where('data', '<=', $data_ate);
        }
        if(!empty($fields['tipo'])){
            $query->where('tipo', $fields['tipo']);
        }
        if(!empty($fields['codigo_produto'])){
            $query->where('codigo_produto', $fields['codigo_produto']);
        }
        if(isset($fields['transferencia']) && !isset($fields['ajuste'])){
            $query->where('historico','ilike' ,'%Reclassificação de Peças - De%');
        }
        if(!isset($fields['transferencia']) && isset($fields['ajuste'])){
            $query->where('historico','not ilike','%Reclassificação de Peças - De%');
        }

        $query->with(['detalhes_produto' => function($query) use($fields){
            if(!empty($fields['descricao_produto'])){
                $query->where('descricao', 'ilike', '%'.$fields['descricao_produto'].'%');
            }
            if(!empty($fields['marca_produto'])){
                $query->where('marca', 'ilike', '%'.$fields['marca_produto'].'%');
            }
            if(!empty($fields['linha_produto'])){
                $query->where('linha', 'ilike', '%'.$fields['linha_produto'].'%');
            }
            if(!empty($fields['grupo_produto'])){
                $query->where('grupo', 'ilike', '%'.$fields['grupo_produto'].'%');
            }
            if(!empty($fields['subgrupo_produto'])){
                $query->where('subgrupo', 'ilike', '%'.$fields['subgrupo_produto'].'%');
            }
        },'custos']);

        if(!empty($fields['motivo'])){
            $query->with(['detalhes_ajuste_estoque' => function($query) use($fields){
                $query->where('motivos_ajuste_estoque_id', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
            }]);
        }

        $query->orderBY('codigo_estabelecimento');

        $result = $query->get();

        $retorno = [];
        $total = [
            'quantidade_rastreabilidade' => 0,
            'quantidade_negativa' => 0,
            'quantidade_positiva' => 0,
            'total_quantidade' => 0,
            'valor_negativo' => 0,
            'valor_positivo' => 0,
            'total_valor' => 0  
        ];

        $estabelecimentos = returnEmpresasNasajonView();

        $result = $result->filter(function($query) use ($result){
            if($query->codigo_estabelecimento == '20'){
                $quantidade = $query->quantidade;
                $verifica_armazem = $result->where('codigo_estabelecimento','03')->where('quantidade',$quantidade)->first();

                if(isset($verifica_armazem->id)){
                    return false;
                }else{
                    return true;
                }
            }else{
                return true;
            }
        });
        
        if(!empty($fields['estabelecimento'])){
            $result = $result->where('codigo_estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
        }
        
        foreach($result as $ajuste_estoque){
            $validador = true;
            if(!empty($fields['motivo'])){
                if(empty($ajuste_estoque->detalhes_ajuste_estoque)){
                    $validador = false;
                }
            }else if(empty($ajuste_estoque->detalhes_produto)){
                $validador = false;
            }
            
            if($validador){
                if(empty($retorno[intval($ajuste_estoque->codigo_estabelecimento)])){
                    $retorno[intval($ajuste_estoque->codigo_estabelecimento)] = [
                        'codigo_estabelecimento' => $ajuste_estoque->codigo_estabelecimento,
                        'estabelecimento' => $estabelecimentos[intval($ajuste_estoque->codigo_estabelecimento)],
                        'rastreabilidade' => 0,
                        'positivo' => 0,
                        'negativo' => 0,
                        'total' => 0,
                        'valor_negativo' => 0,
                        'valor_positivo' => 0,
                        'valor_total' => 0
                    ];
                }
    
                if($ajuste_estoque->tipo == "Entrada"){
                    $retorno[intval($ajuste_estoque->codigo_estabelecimento)]['positivo'] += $ajuste_estoque->quantidade;
                    $retorno[intval($ajuste_estoque->codigo_estabelecimento)]['valor_positivo'] += (isset($ajuste_estoque->custos->compra_real)) ?  $ajuste_estoque->custos->compra_real * $ajuste_estoque->quantidade : 0;
                    $total['quantidade_positiva'] += $ajuste_estoque->quantidade;
                    $total['valor_positivo'] += (isset($ajuste_estoque->custos->compra_real)) ?  $ajuste_estoque->custos->compra_real * $ajuste_estoque->quantidade : 0;
                }else{
                    $retorno[intval($ajuste_estoque->codigo_estabelecimento)]['negativo'] += $ajuste_estoque->quantidade;
                    $retorno[intval($ajuste_estoque->codigo_estabelecimento)]['valor_negativo'] += (isset($ajuste_estoque->custos->compra_real)) ?  $ajuste_estoque->custos->compra_real * $ajuste_estoque->quantidade : 0;
                    $total['quantidade_negativa'] += $ajuste_estoque->quantidade;
                    $total['valor_negativo'] += (isset($ajuste_estoque->custos->compra_real)) ?  $ajuste_estoque->custos->compra_real * $ajuste_estoque->quantidade : 0;
                }
            }
        }

        foreach($retorno as $key => $dados){
            $retorno[$key]['total'] = $retorno[$key]['positivo'] - $retorno[$key]['negativo'];
            $retorno[$key]['valor_total'] = $retorno[$key]['valor_positivo'] - $retorno[$key]['valor_negativo'];
            $total['total_quantidade'] += $retorno[$key]['total'];
            $total['total_valor'] += $retorno[$key]['valor_positivo'] - $retorno[$key]['valor_negativo'];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'ajuste_estoque' => $this->ajusteArrayParaValores($retorno),
                'filtro' => encrypt($fields),
                'total' => $this->ajusteArrayParaValores($total)
            ]
        ];
        return response()->json($response,200);
    }

    public function dialog(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('filtros','codigo_estabelecimento','filtro');
        
        if(isset($fields['filtros'])){
            try{
                $fields = decrypt($fields['filtros']);
                $filtro = decrypt($fields['filtros']);
                $filtro_motivo = decrypt($fields['filtro_motivo']);
                $filtro = decrypt($fields['filtros']);
                $filtro = decrypt($filtro['filtro']);
                $fields = decrypt($fields['filtros']);
            }catch(Exception $e){
                $return = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                    'error' => '',
                    'response' => '',
                ];
                return response()->json($return);
            }
        }else{
            $filtro = decrypt($fields['filtro']);
        }
        
        $query = AjusteEstoqueNasajon::where('codigo_estabelecimento','<>','20');
        
        if(!empty($filtro['data_de'])){
            $data_de = Carbon::CreateFromFormat("d/m/Y", $filtro['data_de']);
            $query->where('data', '>=', $data_de->format('Y-m-d'));
        }
        if(!empty($filtro['data_ate'])){
            $data_ate = Carbon::CreateFromFormat("d/m/Y", $filtro['data_ate']);
            $query->where('data', '<=', $data_ate->format('Y-m-d'));
        }
        if(!empty($filtro['tipo'])){
            $query->where('tipo', $filtro['tipo']);
        }
        if(!empty($filtro['codigo_produto'])){
            $query->where('codigo_produto', $filtro['codigo_produto']);
        }
        if(isset($filtro['transferencia']) && !isset($filtro['ajuste'])){
            $query->where('historico','ilike' ,'%Reclassificação de Peças - De%');
        }
        if(!isset($filtro['transferencia']) && isset($filtro['ajuste'])){
            $query->where('historico','not ilike','%Reclassificação de Peças - De%');
        }
        $query->with(['detalhes_produto' => function($query) use($filtro){
            if(!empty($filtro['descricao_produto'])){
                $query->where('descricao', 'ilike', '%'.$filtro['descricao_produto'].'%');
            }
            if(!empty($filtro['marca_produto'])){
                $query->where('marca', 'ilike', '%'.$filtro['marca_produto'].'%');
            }
            if(!empty($filtro['linha_produto'])){
                $query->where('linha', 'ilike', '%'.$filtro['linha_produto'].'%');
            }
            if(!empty($filtro['grupo_produto'])){
                $query->where('produto_grupos.descricao', 'ilike', '%'.$filtro['grupo_produto'].'%');
            }
            if(!empty($filtro['subgrupo_produto'])){
                $query->where('subgrupo', 'ilike', '%'.$filtro['subgrupo_produto'].'%');
            }
        }]);
       
        $query->with(['detalhes_ajuste_estoque' => function($query) use($filtro){
            $query->with(['motivo','criadoPor']);
            if(!empty($filtro['motivo'])){
                $query->where('motivos_ajuste_estoque_id', $filtro['motivo']);
            }
        }]);
        $query->orderBy('data');

        $result = $query->get();

        $result = $result->filter(function($query) use ($result){
            if($query->codigo_estabelecimento == '20'){
                $quantidade = $query->quantidade;
                $verifica_armazem = $result->where('codigo_estabelecimento','03')->where('quantidade',$quantidade)->first();

                if(isset($verifica_armazem->id)){
                    return false;
                }else{
                    return true;
                }
            }else{
                return true;
            }
        });

        if(!empty($fields['codigo_estabelecimento'])){
            $result = $result->where('codigo_estabelecimento', $fields['codigo_estabelecimento']);
        }

        $dados = [];
        $total = [
            'quantidade' => 0,
            'quantidade_negativa' => 0,
            'quantidade_positiva' => 0,
            'quantidade_total' => 0
        ];

        foreach($result as $ajuste_estoque){
            $validador = true;
            $usuario = '';
            if(!empty($filtro['motivo'])){
                if(empty($ajuste_estoque->detalhes_ajuste_estoque)){
                    $validador = false;
                }
            }else if(empty($ajuste_estoque->detalhes_produto)){
                $validador = false;
            }
            
            if(!empty($ajuste_estoque->detalhes_ajuste_estoque->criadoPor->name)){
                $usuario = $ajuste_estoque->detalhes_ajuste_estoque->criadoPor->name;
            }else if(!empty($ajuste_estoque->usuario)){
                $usuario = $ajuste_estoque->usuario;
            }

            $motivo = empty($ajuste_estoque->detalhes_ajuste_estoque) ? 'Nasajon' : $ajuste_estoque->detalhes_ajuste_estoque->motivo->motivo;
            
            if(isset($filtro_motivo) && $filtro_motivo != $motivo){
                continue;
            }

            if($validador){
                if(!isset($dados[$ajuste_estoque->codigo_produto.$ajuste_estoque->data.$usuario.$ajuste_estoque->tipo])){
                    $dados[$ajuste_estoque->codigo_produto.$ajuste_estoque->data.$usuario.$ajuste_estoque->tipo] = [
                        'codigo_produto' => $ajuste_estoque->codigo_produto,
                        'descricao_produto' => $ajuste_estoque->detalhes_produto->descricao,
                        'tipo' => $ajuste_estoque->tipo,
                        'motivo' => $motivo == 'Nasajon'? 'Nasajon - ' . $ajuste_estoque->historico : $motivo,
                        'data_hora' => parserData($ajuste_estoque->data),
                        'usuario' => $usuario,
                        'quantidade' => 0,
                        'quantidade_positiva' => 0,
                        'quantidade_negativa' => 0,
                        'quantidade_total' => 0
                    ];
                }

                $dados[$ajuste_estoque->codigo_produto.$ajuste_estoque->data.$usuario.$ajuste_estoque->tipo]['quantidade_positiva'] += ($ajuste_estoque->tipo == 'Entrada') ? $ajuste_estoque->quantidade : 0;
                $dados[$ajuste_estoque->codigo_produto.$ajuste_estoque->data.$usuario.$ajuste_estoque->tipo]['quantidade_negativa'] += ($ajuste_estoque->tipo == 'Saída') ? $ajuste_estoque->quantidade : 0;
                $dados[$ajuste_estoque->codigo_produto.$ajuste_estoque->data.$usuario.$ajuste_estoque->tipo]['quantidade'] += $ajuste_estoque->quantidade;
                
                $total['quantidade'] += $ajuste_estoque->quantidade;
            }
        }

        foreach($dados as $key => $dado){
            $dados[$key]['quantidade_total'] = $dados[$key]['quantidade_positiva'] - $dados[$key]['quantidade_negativa'];
            $total['quantidade_positiva'] += $dados[$key]['quantidade_positiva'];
            $total['quantidade_negativa'] += $dados[$key]['quantidade_negativa'];
            $dados[$key]['quantidade_total'] = ($dados[$key]['quantidade_total'] > 0) ? parserValor($dados[$key]['quantidade_total']) : '';
            $dados[$key]['quantidade_positiva'] = ($dados[$key]['quantidade_positiva'] > 0) ? parserValor($dados[$key]['quantidade_positiva']) : '';
            $dados[$key]['quantidade_negativa'] = ($dados[$key]['quantidade_negativa'] > 0) ? parserValor($dados[$key]['quantidade_negativa']) : '';
            $dados[$key]['quantidade'] = (is_numeric($dados[$key]['quantidade'])) ? parserValor($dados[$key]['quantidade']) : '';
        }

        $total['quantidade_total'] = ($total['quantidade_positiva'] > 0 || $total['quantidade_negativa'] > 0) ? parserValor($total['quantidade_positiva'] - $total['quantidade_negativa']) : '';
        $total['quantidade_positiva'] = ($total['quantidade_positiva'] > 0) ? parserValor($total['quantidade_positiva']) : '';
        $total['quantidade_negativa'] = ($total['quantidade_negativa'] > 0) ? parserValor($total['quantidade_negativa']) : '';
        $total['quantidade'] = is_numeric($total['quantidade']) ? parserValor($total['quantidade']) : '';
        
        return view('programs.ajuste_estoque.modal.dialog')->with(['dados' => $dados, 'total' => $total]);
    }

    public function dialogMotivoQuantidade(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('codigo_estabelecimento', 'filtro');

        $filtro = decrypt($fields['filtro']);

        $query = AjusteEstoqueNasajon::where('codigo_estabelecimento','<>','20');
        if(!empty($filtro['data_de'])){
            $data_de = Carbon::CreateFromFormat("d/m/Y", $filtro['data_de']);
            $query->where('data', '>=', $data_de->format('Y-m-d'));
        }
        if(!empty($filtro['data_ate'])){
            $data_ate = Carbon::CreateFromFormat("d/m/Y", $filtro['data_ate']);
            $query->where('data', '<=', $data_ate->format('Y-m-d'));
        }
        if(!empty($filtro['tipo'])){
            $query->where('tipo', $filtro['tipo']);
        }
        if(!empty($filtro['codigo_produto'])){
            $query->where('codigo_produto', $filtro['codigo_produto']);
        }
        if(isset($filtro['transferencia']) && !isset($filtro['ajuste'])){
            $query->where('historico','ilike' ,'%Reclassificação de Peças - De%');
        }
        if(!isset($filtro['transferencia']) && isset($filtro['ajuste'])){
            $query->where('historico','not ilike','%Reclassificação de Peças - De%');
        }
        $query->with(['detalhes_produto' => function($query) use($filtro){
            $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
            if(!empty($filtro['descricao_produto'])){
                $query->where('produto_especificacaos.descricao', 'ilike', '%'.$filtro['descricao_produto'].'%');
            }
            if(!empty($filtro['marca_produto'])){
                $query->where('marca', 'ilike', '%'.$filtro['marca_produto'].'%');
            }
            if(!empty($filtro['linha_produto'])){
                $query->where('linha', 'ilike', '%'.$filtro['linha_produto'].'%');
            }
            if(!empty($filtro['grupo_produto'])){
                $query->where('produto_grupos.descricao', 'ilike', '%'.$filtro['grupo_produto'].'%');
            }
            if(!empty($filtro['subgrupo_produto'])){
                $query->where('subgrupo', 'ilike', '%'.$filtro['subgrupo_produto'].'%');
            }
        }]);
       
        $query->with(['detalhes_ajuste_estoque' => function($query) use($filtro){
            $query->with(['motivo','criadoPor']);
            if(!empty($filtro['motivo'])){
                $query->where('motivos_ajuste_estoque_id', $filtro['motivo']);
            }
        }]);
        $query->orderBy('data');

        $result = $query->get();

        $result = $result->filter(function($query) use ($result){
            if($query->codigo_estabelecimento == '20'){
                $quantidade = $query->quantidade;
                $verifica_armazem = $result->where('codigo_estabelecimento','03')->where('quantidade',$quantidade)->first();

                if(isset($verifica_armazem->id)){
                    return false;
                }else{
                    return true;
                }
            }else{
                return true;
            }
        });

        if(!empty($fields['codigo_estabelecimento'])){
            $result = $result->where('codigo_estabelecimento', $fields['codigo_estabelecimento']);
        }

        $dados = [];
        $total = [
            'quantidade' => 0,
            'quantidade_negativa' => 0,
            'quantidade_positiva' => 0,
            'quantidade_total' => 0
        ];

        $result->each(function($ajuste_estoque) use (&$dados,&$total){

            $validador = true;
            $usuario = '';

            if(!empty($filtro['motivo'])){
                if(empty($ajuste_estoque->detalhes_ajuste_estoque)){
                    $validador = false;
                }
            }else if(empty($ajuste_estoque->detalhes_produto)){
                $validador = false;
            }

            $motivo = empty($ajuste_estoque->detalhes_ajuste_estoque) ? 'Nasajon' : $ajuste_estoque->detalhes_ajuste_estoque->motivo->motivo;

            if($validador){
                if(!isset($dados[$motivo])){
                    $dados[$motivo] = [
                        'motivo' => $motivo,
                        'quantidade' => 0,
                        'quantidade_positiva' => 0,
                        'quantidade_negativa' => 0,
                        'filtros' => []
                    ];
                }

                $dados[$motivo]['quantidade_negativa'] += ($ajuste_estoque->tipo == 'Saída') ? $ajuste_estoque->quantidade : 0;
                $dados[$motivo]['quantidade_positiva'] += ($ajuste_estoque->tipo == 'Entrada') ? $ajuste_estoque->quantidade : 0;
            }

        });

        foreach($dados as $key => $dado){
            $dados[$key]['quantidade'] = is_numeric($dados[$key]['quantidade_positiva'] - $dados[$key]['quantidade_negativa']) ? $dados[$key]['quantidade_positiva'] - $dados[$key]['quantidade_negativa']: 0;
            $total['quantidade'] += $dados[$key]['quantidade'];
            $total['quantidade_positiva'] += $dados[$key]['quantidade_positiva'];
            $total['quantidade_negativa'] += $dados[$key]['quantidade_negativa'];
            $dados[$key]['quantidade_positiva'] = is_numeric($dados[$key]['quantidade_positiva']) ? parserValor($dados[$key]['quantidade_positiva']) : '0';
            $dados[$key]['quantidade_negativa'] = is_numeric($dados[$key]['quantidade_negativa']) ? parserValor($dados[$key]['quantidade_negativa']) : '0';
            $dados[$key]['quantidade'] = is_numeric($dados[$key]['quantidade']) ? parserValor($dados[$key]['quantidade']) : '0';
            $dados[$key]['filtros'] = encrypt([
                'filtros' => encrypt($fields),
                'filtro_motivo' => encrypt($key)
            ]);
        }

        $total['quantidade'] = is_numeric($total['quantidade_positiva'] - $total['quantidade_negativa']) ? $total['quantidade_positiva'] - $total['quantidade_negativa'] : '';
        $total['quantidade'] = is_numeric($total['quantidade']) ? parserValor($total['quantidade']) : '';
        $total['quantidade_positiva'] = is_numeric($total['quantidade_positiva']) ? parserValor($total['quantidade_positiva']) : '';
        $total['quantidade_negativa'] = is_numeric($total['quantidade_negativa']) ? parserValor($total['quantidade_negativa']) : '';

        return view('programs.ajuste_estoque.modal.motivo_quantidade')->with(['dados' => $dados, 'total' => $total]);
    }

    public function dialogMotivoValor(Request $request){
        set_time_limit(15000);
        ini_set('memory_limit','1024M');

        $fields = $request->only('codigo_estabelecimento', 'filtro');

        $filtro = decrypt($fields['filtro']);

        $query = AjusteEstoqueNasajon::where('codigo_estabelecimento','<>','20');
        if(!empty($filtro['data_de'])){
            $data_de = Carbon::CreateFromFormat("d/m/Y", $filtro['data_de']);
            $query->where('data', '>=', $data_de->format('Y-m-d'));
        }
        if(!empty($filtro['data_ate'])){
            $data_ate = Carbon::CreateFromFormat("d/m/Y", $filtro['data_ate']);
            $query->where('data', '<=', $data_ate->format('Y-m-d'));
        }
        if(!empty($filtro['tipo'])){
            $query->where('tipo', $filtro['tipo']);
        }
        if(!empty($filtro['codigo_produto'])){
            $query->where('codigo_produto', $filtro['codigo_produto']);
        }
        if(isset($filtro['transferencia']) && !isset($filtro['ajuste'])){
            $query->where('historico','ilike' ,'%Reclassificação de Peças - De%');
        }
        if(!isset($filtro['transferencia']) && isset($filtro['ajuste'])){
            $query->where('historico','not ilike','%Reclassificação de Peças - De%');
        }
        $query->with(['detalhes_produto' => function($query) use($filtro){
            $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
            if(!empty($filtro['descricao_produto'])){
                $query->where('descricao', 'ilike', '%'.$filtro['descricao_produto'].'%');
            }
            if(!empty($filtro['marca_produto'])){
                $query->where('marca', 'ilike', '%'.$filtro['marca_produto'].'%');
            }
            if(!empty($filtro['linha_produto'])){
                $query->where('linha', 'ilike', '%'.$filtro['linha_produto'].'%');
            }
            if(!empty($filtro['grupo_produto'])){
                $query->where('produto_grupos.descricao', 'ilike', '%'.$filtro['grupo_produto'].'%');
            }
            if(!empty($filtro['subgrupo_produto'])){
                $query->where('subgrupo', 'ilike', '%'.$filtro['subgrupo_produto'].'%');
            }
        },'custos']);
       
        $query->with(['detalhes_ajuste_estoque' => function($query) use($filtro){
            $query->with(['motivo','criadoPor']);
            if(!empty($filtro['motivo'])){
                $query->where('motivos_ajuste_estoque_id', $filtro['motivo']);
            }
        }]);
        $query->orderBy('data');
        $result = $query->get();
        $quantidades_estabelecimento_03 = $result->where('codigo_estabelecimento','03');

        if(!empty($fields['codigo_estabelecimento'])){
            $result = $result->where('codigo_estabelecimento', $fields['codigo_estabelecimento']);
        }

        $dados = [];
        $total = [
            'valor_total' => 0,
            'valor_negativo' => 0,
            'valor_positivo' => 0,
        ];

        $result->each(function($ajuste_estoque) use (&$dados,&$quantidades_estabelecimento_03){
            $validador = true;
            $usuario = '';
            $verifica_quantidade_estabelecimento_20 = false;

            if($ajuste_estoque->codigo_estabelecimento == '20'){
                $verifica_quantidade_estabelecimento_20 = $quantidades_estabelecimento_03->where('quantidade',$ajuste_estoque->quantidade)->isEmpty();
                
            }

            if(!empty($filtro['motivo'])){
                if(empty($ajuste_estoque->detalhes_ajuste_estoque)){
                    $validador = false;
                }
            }else if(empty($ajuste_estoque->detalhes_produto)){
                $validador = false;
            }

            $motivo = empty($ajuste_estoque->detalhes_ajuste_estoque) ? 'Nasajon' : $ajuste_estoque->detalhes_ajuste_estoque->motivo->motivo;

            if($validador && !$verifica_quantidade_estabelecimento_20){
                if(!isset($dados[$motivo])){
                    $dados[$motivo] = [
                        'motivo' => $motivo,
                        'valor_total' => 0,
                        'valor_positivo' => 0,
                        'valor_negativo' => 0,
                        'filtros' => []
                    ];
                }

                $dados[$motivo]['valor_negativo'] += ($ajuste_estoque->tipo == 'Saída' && isset($ajuste_estoque->custos->compra_real)) ? $ajuste_estoque->custos->compra_real * $ajuste_estoque->quantidade : 0;
                $dados[$motivo]['valor_positivo'] += ($ajuste_estoque->tipo == 'Entrada' && isset($ajuste_estoque->custos->compra_real)) ? $ajuste_estoque->custos->compra_real * $ajuste_estoque->quantidade : 0;
            }

        });

        unset($result);

        foreach($dados as $key => $dado){

            $dados[$key]['valor_total'] = is_numeric($dados[$key]['valor_positivo'] - $dados[$key]['valor_negativo']) ? $dados[$key]['valor_positivo'] - $dados[$key]['valor_negativo']: 0;
            $total['valor_total'] += $dados[$key]['valor_total'];
            $total['valor_positivo'] += $dados[$key]['valor_positivo'];
            $total['valor_negativo'] += $dados[$key]['valor_negativo'];
            $dados[$key]['valor_positivo'] = is_numeric($dados[$key]['valor_positivo']) ? parserValor($dados[$key]['valor_positivo']) : '0';
            $dados[$key]['valor_negativo'] = is_numeric($dados[$key]['valor_negativo']) ? parserValor($dados[$key]['valor_negativo']) : '0';
            $dados[$key]['valor_total'] = is_numeric($dados[$key]['valor_total']) ? parserValor($dados[$key]['valor_total']) : '0';
            $dados[$key]['filtros'] = encrypt([
                'filtros' => encrypt($fields),
                'filtro_motivo' => encrypt($key)
            ]);
        }

        $total['valor_total'] = is_numeric($total['valor_positivo'] - $total['valor_negativo']) ? $total['valor_positivo'] - $total['valor_negativo'] : '';
        $total['valor_total'] = is_numeric($total['valor_total']) ? parserValor($total['valor_total']) : '';
        $total['valor_positivo'] = is_numeric($total['valor_positivo']) ? parserValor($total['valor_positivo']) : '';
        $total['valor_negativo'] = is_numeric($total['valor_negativo']) ? parserValor($total['valor_negativo']) : '';

        return view('programs.ajuste_estoque.modal.motivo_valor')->with(['dados' => $dados, 'total' => $total]);
    }

    public function dialogValor(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('filtros','codigo_estabelecimento','filtro');

        if(isset($fields['filtros'])){
            try{
                $fields = decrypt($fields['filtros']);
                $filtro = decrypt($fields['filtros']);
                $filtro_motivo = decrypt($fields['filtro_motivo']);
                $filtro = decrypt($fields['filtros']);
                $filtro = decrypt($filtro['filtro']);
                $fields = decrypt($fields['filtros']);
            }catch(Exception $e){
                $return = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                    'error' => '',
                    'response' => '',
                ];
                return response()->json($return);
            }
        }else{
            $filtro = decrypt($fields['filtro']);
        }

        $query = AjusteEstoqueNasajon::where('codigo_estabelecimento','<>','20');
        if(!empty($filtro['data_de'])){
            $data_de = Carbon::CreateFromFormat("d/m/Y", $filtro['data_de']);
            $query->where('data', '>=', $data_de->format('Y-m-d'));
        }
        if(!empty($filtro['data_ate'])){
            $data_ate = Carbon::CreateFromFormat("d/m/Y", $filtro['data_ate']);
            $query->where('data', '<=', $data_ate->format('Y-m-d'));
        }
        if(!empty($filtro['tipo'])){
            $query->where('tipo', $filtro['tipo']);
        }
        if(!empty($filtro['codigo_produto'])){
            $query->where('codigo_produto', $filtro['codigo_produto']);
        }
        if(isset($filtro['transferencia']) && !isset($filtro['ajuste'])){
            $query->where('historico','ilike' ,'%Reclassificação de Peças - De%');
        }
        if(!isset($filtro['transferencia']) && isset($filtro['ajuste'])){
            $query->where('historico','not ilike','%Reclassificação de Peças - De%');
        }
        $query->with(['detalhes_produto' => function($query) use($filtro){
            $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
            if(!empty($filtro['descricao_produto'])){
                $query->where('produto_especificacaos.descricao', 'ilike', '%'.$filtro['descricao_produto'].'%');
            }
            if(!empty($filtro['marca_produto'])){
                $query->where('marca', 'ilike', '%'.$filtro['marca_produto'].'%');
            }
            if(!empty($filtro['linha_produto'])){
                $query->where('linha', 'ilike', '%'.$filtro['linha_produto'].'%');
            }
            if(!empty($filtro['grupo_produto'])){
                $query->where('produto_grupos.descricao', 'ilike', '%'.$filtro['grupo_produto'].'%');
            }
            if(!empty($filtro['subgrupo_produto'])){
                $query->where('subgrupo', 'ilike', '%'.$filtro['subgrupo_produto'].'%');
            }
        },'custos']);
       
        $query->with(['detalhes_ajuste_estoque' => function($query) use($filtro){
            $query->with(['motivo','criadoPor']);
            if(!empty($filtro['motivo'])){
                $query->where('motivos_ajuste_estoque_id', $filtro['motivo']);
            }
        }]);
        $query->orderBy('data');

        $result = $query->get();

        $result = $result->filter(function($query) use ($result){
            if($query->codigo_estabelecimento == '20'){
                $quantidade = $query->quantidade;
                $verifica_armazem = $result->where('codigo_estabelecimento','03')->where('quantidade',$quantidade)->first();

                if(isset($verifica_armazem->id)){
                    return false;
                }else{
                    return true;
                }
            }else{
                return true;
            }
        });

        if(!empty($fields['codigo_estabelecimento'])){
            $result = $result->where('codigo_estabelecimento', $fields['codigo_estabelecimento']);
        }

        $dados = [];
        $total = [
            'quantidade' => 0,
            'valor' => 0,
            'valor_negativo' => 0,
            'valor_positivo' => 0,
            'valor_total' => 0
        ];

        foreach($result as $ajuste_estoque){
            $validador = true;
            $usuario = '';
            if(!empty($filtro['motivo'])){
                if(empty($ajuste_estoque->detalhes_ajuste_estoque)){
                    $validador = false;
                }
            }else if(empty($ajuste_estoque->detalhes_produto)){
                $validador = false;
            }
            
            if(!empty($ajuste_estoque->detalhes_ajuste_estoque->criadoPor->name)){
                $usuario = $ajuste_estoque->detalhes_ajuste_estoque->criadoPor->name;
            }else if(!empty($ajuste_estoque->usuario)){
                $usuario = $ajuste_estoque->usuario;
            }

            $motivo = empty($ajuste_estoque->detalhes_ajuste_estoque) ? 'Nasajon' : $ajuste_estoque->detalhes_ajuste_estoque->motivo->motivo;

            if(isset($filtro_motivo) && $filtro_motivo != $motivo){
                continue;
            }

            if($validador){
                $dados[] = [
                    'codigo_produto' => $ajuste_estoque->codigo_produto,
                    'descricao_produto' => $ajuste_estoque->detalhes_produto->descricao,
                    'tipo' => $ajuste_estoque->tipo,
                    'quantidade' => (empty($ajuste_estoque)) ? '' : parserValor($ajuste_estoque->quantidade),
                    'custo_gerencial' => (!isset($ajuste_estoque->custos->compra_real)) ? '' : parserValor($ajuste_estoque->custos->compra_real),
                    'valor' => (!isset($ajuste_estoque->custos->compra_real)) ? '' : parserValor($ajuste_estoque->custos->compra_real * $ajuste_estoque->quantidade),
                    'usuario' => $usuario,
                    'data_hora' => parserData($ajuste_estoque->data),
                    'motivo' => $motivo == 'Nasajon'? 'Nasajon - ' . $ajuste_estoque->historico : $motivo,
                    'valor_negativo' => ($ajuste_estoque->tipo == 'Saída' && isset($ajuste_estoque->custos->compra_real)) ? $ajuste_estoque->custos->compra_real * $ajuste_estoque->quantidade : 0,
                    'valor_positivo' => ($ajuste_estoque->tipo == 'Entrada' && isset($ajuste_estoque->custos->compra_real)) ? $ajuste_estoque->custos->compra_real * $ajuste_estoque->quantidade : 0,
                    'valor_total' => 0
                ];
                
                $total['valor_negativo'] += ($ajuste_estoque->tipo == 'Saída' && isset($ajuste_estoque->custos->compra_real)) ? $ajuste_estoque->custos->compra_real * $ajuste_estoque->quantidade : 0;
                $total['valor_positivo'] += ($ajuste_estoque->tipo == 'Entrada' && isset($ajuste_estoque->custos->compra_real)) ? $ajuste_estoque->custos->compra_real * $ajuste_estoque->quantidade : 0;
                $total['quantidade'] += empty($ajuste_estoque)? 0 : $ajuste_estoque->quantidade;
                $total['valor'] += (!isset($ajuste_estoque->custos->compra_real)) ? 0 : $ajuste_estoque->custos->compra_real * $ajuste_estoque->quantidade;
            }
        }
        
        foreach($dados as $key => $dado){
            $dados[$key]['valor_total'] = $dados[$key]['valor_positivo'] - $dados[$key]['valor_negativo'];

            $dados[$key]['valor_negativo'] = ($dados[$key]['valor_negativo'] > 0) ? parserValor($dados[$key]['valor_negativo']) : '';
            $dados[$key]['valor_positivo'] = ($dados[$key]['valor_positivo'] > 0) ? parserValor($dados[$key]['valor_positivo']) : '';
            $dados[$key]['valor_total'] = ($dados[$key]['valor_total'] > 0) ? parserValor($dados[$key]['valor_total']) : '';
        }

        $total['valor_total'] = $total['valor_positivo'] - $total['valor_negativo'];
        
        return view('programs.ajuste_estoque.modal.dialog_valor')->with(['dados' => $dados, 'total' => $this->ajusteArrayParaValores($total)]);
    }

    private function ajusteArrayParaValores($array){
        if(is_array($array)){
            foreach($array as $key => $value){
                if(is_array($value)){
                    $array[$key] = $this->ajusteArrayParaValores($value);
                }else{
                    if(is_numeric($value)){
                        if(substr_count($key, "codigo") === 0){
                            $array[$key] = empty($value)? '': parserValor($value);
                        }
                    }else{
                        $array[$key] = $value;
                    }
                }
            }
        }
        return $array;
    }

    public function indexDigitacao(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        if(Auth::user()->hasPermissionTo("programas App\DigitacaoAjusteEstoque") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\DigitacaoAjusteEstoque');

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        return view('programs.ajuste_estoque.digitacao.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function modalAdicionarDigitacao(){
        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        $motivos = $this->getMotivos();

        return view('programs.ajuste_estoque.digitacao.modal.adicionar')->with(['estabelecimentos' => $estabelecimentos, 'motivos' => $motivos]);
    }

    public function getMotivos(){
        $query = MotivoAjusteEstoque::select();
        $result = $query->get();

        $motivos = [];
        foreach($result as $motivo){
            $motivos[$motivo->id] = $motivo->motivo;
        }

        return $motivos;
    }

    public function liberarAjuste(Request $request){
        $fields = $request->only('estabelecimento', 'produto_codigo');

        $produtoQuery = ProdutoEspecificacao::select();
        $produtoQuery->where('codigo_produto', $fields['produto_codigo']);
        $produto = $produtoQuery->first();
        $retorno = [];
        if(empty($produto) && !empty($fields['produto_codigo'])){
            return response()->json([
                'status' => 'error',
                'message' => 'Código de Produto não existe.',
                'error' => ['produto_codigo' => 'Código de Produto não existe.'],
                'response' => []
            ],422);
        }else if(empty($fields['produto_codigo'])){
            $produto = [
                'nome' => "",
                'codigo' => "",
            ];
        }else{
            $produto = [
                'nome' => $produto->descricao,
                'codigo' => $produto->codigo_produto,
            ];
        }

        $liberar = false;
        $lotes = [];
        $locais_de_estoque = [];
        $quantidade = 0;

        if(is_numeric($fields['estabelecimento']) && !empty($fields['produto_codigo']) ){
            $query = ProdutoNasajon::select();
            $query->where('codigo', $fields['produto_codigo']);
            $result = $query->first();

            if(!empty($result)){
                if($result->controlafracao === true){
                    if($fields['estabelecimento'] == 3 ){
                        $estoque_em_terceiros = DB::connection('nasajon')->select("SELECT saldo_em_terceiros FROM integracoes.exportar_produtos_saldos('".str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT)."', '".$fields["produto_codigo"]."') as sal group by saldo_em_terceiros");
                        $lotes[] =[
                            'produto_lote' => encrypt(0),
                            'peca' => "0",
                            'local_de_estoque_codigo' => 'PROP-EMPODERTERCEIROS.',
                            'local_de_estoque_nome' => 'Em Terceiros',
                            'local_de_estoque_uuid' => encrypt('fa0e317c-b4ca-4f6d-8289-93dec3b53667'),
                            'quantidade' => empty($estoque_em_terceiros)? '0,00' : parserValor($estoque_em_terceiros[0]->saldo_em_terceiros),
                            'unidade' => '',
                            'ajuste' => empty($estoque_em_terceiros)? '0,00' : parserValor($estoque_em_terceiros[0]->saldo_em_terceiros),
                        ];

                        $quantidade += empty($estoque_em_terceiros)? 0 :$estoque_em_terceiros[0]->saldo_em_terceiros;
                    }else{
                        $estoque_em_terceiros = DB::connection('nasajon')->select("SELECT saldo_em_terceiros FROM integracoes.exportar_produtos_saldos('".str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT)."', '".$fields["produto_codigo"]."') as sal group by saldo_em_terceiros");                 
                        if(!empty($estoque_em_terceiros)){
                            $locais_de_estoque_nasajon = DB::connection('nasajon')->select("SELECT * FROM estoque.exportar_saldos_sem_peca_tecidos_mn('".str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT)."','".$fields["produto_codigo"]."')");
                            if(!empty($locais_de_estoque_nasajon)){
                                $detalhes_locais_de_estoque_nasajon = LocalDeEstoqueNasajon::select()->where('codigo', $locais_de_estoque_nasajon[0]->localdeestoque_codigo)->first();
                                $lotes[] =[
                                    'produto_lote' => encrypt(0),
                                    'peca' => "0",
                                    'local_de_estoque_codigo' => 'PROP-EMPODERTERCEIROS.',
                                    'local_de_estoque_nome' => 'Em Terceiros',
                                    'local_de_estoque_uuid' => encrypt($detalhes_locais_de_estoque_nasajon->localdeestoque),
                                    'quantidade' => parserValor($estoque_em_terceiros[0]->saldo_em_terceiros),
                                    'unidade' => '',
                                    'ajuste' => parserValor($estoque_em_terceiros[0]->saldo_em_terceiros),
                                ];

                                $quantidade += $estoque_em_terceiros[0]->saldo_em_terceiros;
                            }
                        }
                    }                    
                    
                    $query_lote = FracaoNasajon::select();
                    $query_lote->where('codigo_produto', $result->codigo);
                    $result_lote = $query_lote->first();
                    if(!empty($result_lote)){
                        $liberar = true;

                        $query_estabelecimento = NasajonEstabelecimento::select();
                        if($fields["estabelecimento"] == 3 || $fields["estabelecimento"] == 4){
                            $query_estabelecimento->where('codigo', "20");
                        }else{
                            $query_estabelecimento->where('codigo', str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT));
                        }
                        $codigo_estabelecimento = $query_estabelecimento->first()->estabelecimento;

                        $pecas = DB::connection('nasajon')->select("SELECT * FROM integracoes.exportar_fracoes_produtos('".str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT)."','".$fields["produto_codigo"]."') where empenhado = false");
                
                        $lote_codigo = [];
                        $local_de_estoque = [];

                        foreach($pecas as $peca){
                            $lote_codigo [] = $peca->fracao_codigo;
                            $local_de_estoque [$peca->local_de_estoque_endereco] = $peca->local_de_estoque_endereco;
                        }
                
                        $query_lote = FracaoNasajon::select();
                        $query_lote->where('codigo_produto', $fields['produto_codigo']);
                        $query_lote->whereIn('codigo', $lote_codigo);
                        $result_lote = $query_lote->get();
                
                        $locais_de_estoque_nasajon = LocalDeEstoqueEnderecoNasajon::select()->where('estabelecimento', $codigo_estabelecimento)->whereIn('endereco', $local_de_estoque)->get();

                        foreach($locais_de_estoque_nasajon as $local_de_estoque_nasajon){
                            $detalhes_locais_de_estoque[$local_de_estoque_nasajon->endereco] = [
                                'codigo' => $local_de_estoque_nasajon->endereco,
                                'nome' => $local_de_estoque_nasajon->endereco_simplificado,
                                'uuid' => $local_de_estoque_nasajon->localdeestoqueendereco
                            ];
                        }

                        $codigo_estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);

                        foreach($result_lote as $lote){
                            if(isset($detalhes_locais_de_estoque[$local_de_estoque[$lote->endereco]])){
                                if(empty($fields['obj_pecas'][$lote->codigo])){
                                    $ajuste = parserValor($lote->quantidade);
                                }else{
                                    $ajuste = parserValor($fields['obj_pecas'][$lote->codigo]['ajuste']);
                                }
                                $lotes[] =[
                                    'produto_lote' => encrypt($lote->fracao),
                                    'peca' => $lote->codigo,
                                    'local_de_estoque_codigo' => $detalhes_locais_de_estoque[$local_de_estoque[$lote->endereco]]['codigo'],
                                    'local_de_estoque_nome' => $detalhes_locais_de_estoque[$local_de_estoque[$lote->endereco]]['nome'],
                                    'local_de_estoque_uuid' => encrypt($detalhes_locais_de_estoque[$local_de_estoque[$lote->endereco]]['uuid']),
                                    'quantidade' => parserValor($lote->quantidade),
                                    'unidade' => $lote->unidadecodigo,
                                    'ajuste' => $ajuste
                                ];
        
                                $quantidade += $lote->quantidade;
                            }
                        }

                        if(empty($lotes)){
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Produto sem Estoque neste Estabelecimento',
                                'error' => [
                                    'produto_descricao' => [
                                        'mensagem' => 'Produto sem Estoque neste Estabelecimento',
                                        'dados' => $produto,
                                    ],
                                ],
                                'response' => []
                            ], 422);
                        }
                    }else{
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Produto sem Estoque neste Estabelecimento',
                            'error' => [
                                'produto_descricao' => [
                                    'mensagem' => 'Produto sem Estoque neste Estabelecimento',
                                    'dados' => $produto,
                                ],
                            ],
                            'response' => []
                        ], 422);
                    } 
                }else{
                    $locais_de_estoque_nasajon = DB::connection('nasajon')->select("SELECT * FROM estoque.exportar_saldos_sem_peca_tecidos_mn('".str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT)."','".$fields["produto_codigo"]."')"); 
                    if(!empty($locais_de_estoque_nasajon)){
                        $liberar = true;

                        $query_estabelecimento = NasajonEstabelecimento::select();
                        
                        $query_estabelecimento->where('codigo', str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT));

                        $codigo_estabelecimento = $query_estabelecimento->first()->estabelecimento;
                        
                        $local_de_estoque_codigos =[];
                        foreach($locais_de_estoque_nasajon as $local_de_estoque){
                            $local_de_estoque_codigos[] = $local_de_estoque->localdeestoque_codigo; 
                        }

                        $detalhes_locais_de_estoque_nasajon = LocalDeEstoqueNasajon::select()->where('estabelecimento', $codigo_estabelecimento)->whereIn('codigo', $local_de_estoque_codigos)->get();
                        foreach($detalhes_locais_de_estoque_nasajon as $detalhe_local_de_estoque_nasajon){
                            $array_detalhes_locais_de_estoque[$detalhe_local_de_estoque_nasajon->codigo] = [
                                'codigo' => $detalhe_local_de_estoque_nasajon->codigo,
                                'nome' => $detalhe_local_de_estoque_nasajon->nome,
                                'uuid' => $detalhe_local_de_estoque_nasajon->localdeestoque
                            ];
                        }

                        foreach($locais_de_estoque_nasajon as $local_de_estoque){
                            $quantidade += $local_de_estoque->saldo;
                            if(empty($locais_de_estoque[$array_detalhes_locais_de_estoque[$local_de_estoque->localdeestoque_codigo]['codigo']])){
                                $locais_de_estoque[$array_detalhes_locais_de_estoque[$local_de_estoque->localdeestoque_codigo]['codigo']] = [
                                    'produto_codigo' => $result->produto,
                                    'local_de_estoque_codigo' => $array_detalhes_locais_de_estoque[$local_de_estoque->localdeestoque_codigo]['codigo'],
                                    'local_de_estoque_nome' => $array_detalhes_locais_de_estoque[$local_de_estoque->localdeestoque_codigo]['nome'],
                                    'local_de_estoque_uuid' => encrypt($array_detalhes_locais_de_estoque[$local_de_estoque->localdeestoque_codigo]['uuid']),
                                    'quantidade' => $local_de_estoque->saldo,
                                    'unidade' => $result->unidade,
                                ];
                            }else{
                                $locais_de_estoque[$array_detalhes_locais_de_estoque[$local_de_estoque->localdeestoque_codigo]['codigo']]['quantidade'] += $local_de_estoque->saldo;
                            }
                            
                        }
                        if(empty($locais_de_estoque)){
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Produto sem Estoque neste Estabelecimento',
                                'error' => [
                                    'produto_descricao' => [
                                        'mensagem' => 'Produto sem Estoque neste Estabelecimento',
                                        'dados' => $produto,
                                    ],
                                ],
                                'response' => []
                            ], 422);
                        }else{
                            $locais_de_estoque = $this->ajusteArrayParaValores($locais_de_estoque);
                        }
                    }else{
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Produto sem Estoque neste Estabelecimento',
                            'error' => [
                                'produto_descricao' => [
                                    'mensagem' => 'Produto sem Estoque neste Estabelecimento',
                                    'dados' => $produto,
                                ],
                            ],
                            'response' => []
                        ], 422);
                    }
                }
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Produto não encontrado',
                    'error' => [
                        'produto_descricao' => 'Produto não encontrado'
                    ],
                    'response' => []
                ], 422);
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'liberar' => $liberar,
                'lotes' => $lotes,
                'produto' => $produto,
                'quantidade' => parserValor($quantidade),
                'locais_de_estoque' => $locais_de_estoque
            ]
        ];
        return response()->json($response);
    }

    public function filterPecas(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('estabelecimento', 'produto_codigo', 'codigo_peca', 'local_estoque_peca', 'ajuste_todas_pecas');
        $lotes = [];
        if(!empty($fields['estabelecimento']) && !empty($fields['produto_codigo'])){
            if(empty($fields['codigo_peca']) && empty($fields['local_estoque_peca'])){
                $estoque_em_terceiros = DB::connection('nasajon')->select("SELECT saldo_em_terceiros FROM integracoes.exportar_produtos_saldos('".str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT)."', '".$fields["produto_codigo"]."') as sal group by saldo_em_terceiros");                 
                if(!empty($estoque_em_terceiros)){
                        $locais_de_estoque_nasajon = DB::connection('nasajon')->select("SELECT * FROM estoque.exportar_saldos_sem_peca_tecidos_mn('".str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT)."','".$fields["produto_codigo"]."')");
                        if(!empty($locais_de_estoque_nasajon)){
                            $detalhes_locais_de_estoque_nasajon = LocalDeEstoqueNasajon::select()->where('codigo', $locais_de_estoque_nasajon[0]->localdeestoque_codigo)->first();
                            $lotes[] =[
                                'produto_lote' => encrypt(0),
                                'peca' => "0",
                                'local_de_estoque_codigo' => 'PROP-EMPODERTERCEIROS',
                                'local_de_estoque_nome' => 'Em Terceiros',
                                'local_de_estoque_uuid' => encrypt($detalhes_locais_de_estoque_nasajon->localdeestoque),
                                'quantidade' => parserValor($estoque_em_terceiros[0]->saldo_em_terceiros),
                                'unidade' => '',
                                'ajuste' => empty($fields['ajuste_todas_pecas'])? parserValor($estoque_em_terceiros[0]->saldo_em_terceiros) : $fields['ajuste_todas_pecas'],
                            ];
                        }
                }
            }
            $query_estabelecimento = NasajonEstabelecimento::select();
            if($fields["estabelecimento"] == 3 || $fields["estabelecimento"] == 4){
                $query_estabelecimento->where('codigo', "20");
            }else{
                $query_estabelecimento->where('codigo', str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT));
            }
            $codigo_estabelecimento = $query_estabelecimento->first()->estabelecimento;
    
            $pecas = DB::connection('nasajon')->select("SELECT * FROM integracoes.exportar_fracoes_produtos('".str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT)."','".$fields["produto_codigo"]."') where empenhado = false");
    
            $lote_codigo = [];
            foreach($pecas as $peca){
                $lote_codigo [] = $peca->fracao_codigo;
            }
    
            $query_lote = FracaoNasajon::select();
            $query_lote->where('codigo_produto', $fields['produto_codigo']);
            $query_lote->whereIn('codigo', $lote_codigo);
            if(!empty($fields['codigo_peca'])){
                $query_lote->where('codigo', 'ilike', '%'.$fields['codigo_peca'].'%');
            }
            if(!empty($fields['local_estoque_peca'])){
                $query_lote->where('endereco', 'ilike', '%'.$fields['local_estoque_peca'].'%');
            }
            $result_lote = $query_lote->get();

            $locais_de_estoque_nasajon = LocalDeEstoqueEnderecoNasajon::select()->where('estabelecimento', $codigo_estabelecimento)->whereIn('localdeestoqueendereco', $result_lote->pluck('localdeestoqueendereco'))->get();
            foreach($locais_de_estoque_nasajon as $local_de_estoque_nasajon){
                $detalhes_locais_de_estoque[$local_de_estoque_nasajon->endereco] = [
                    'codigo' => $local_de_estoque_nasajon->endereco,
                    'nome' => $local_de_estoque_nasajon->endereco_simplificado,
                    'uuid' => $local_de_estoque_nasajon->localdeestoqueendereco
                ];
            }
            $codigo_estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);
    
            foreach($result_lote as $lote){
                if(empty($fields['obj_pecas'][$lote->codigo])){
                    $ajuste = parserValor($lote->quantidade);
                }else{
                    $ajuste = parserValor($fields['obj_pecas'][$lote->codigo]['ajuste']);
                }
                $lotes[] =[
                    'produto_lote' => encrypt($lote->fracao),
                    'peca' => $lote->codigo,
                    'local_de_estoque' => $detalhes_locais_de_estoque[$lote->endereco],
                    'local_de_estoque_nome' => $detalhes_locais_de_estoque[$lote->endereco]['nome'],
                    'local_de_estoque_uuid' => encrypt($detalhes_locais_de_estoque[$lote->endereco]['uuid']),
                    'quantidade' => parserValor($lote->quantidade),
                    'unidade' => $lote->unidadecodigo,
                    'ajuste' => empty($fields['ajuste_todas_pecas'])? $ajuste : $fields['ajuste_todas_pecas'],
                ];
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'lotes' => $lotes,
            ]
        ];
        return response()->json($response);
    }

    public function filterProdutos(Request $request){
        $fields = $request->only('estabelecimento', 'produto_codigo', 'local_de_estoque_codigo', 'ajuste_todas_produtos');
        $query = ProdutoNasajon::select();
        $query->where('codigo', $fields['produto_codigo']);
        $result = $query->first();

        $locais_de_estoque_nasajon = DB::connection('nasajon')->select("SELECT * FROM estoque.exportar_saldos_sem_peca_tecidos_mn('".str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT)."','".$fields["produto_codigo"]."')"); 
        
        $locais_de_estoque = [];

        $query_estabelecimento = NasajonEstabelecimento::select();
        if($fields["estabelecimento"] == 3 || $fields["estabelecimento"] == 4){
            $query_estabelecimento->where('codigo', "20");
        }else{
            $query_estabelecimento->where('codigo', str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT));
        }
        $codigo_estabelecimento = $query_estabelecimento->first()->estabelecimento;
        
        $local_de_estoque_codigos =[];
        foreach($locais_de_estoque_nasajon as $local_de_estoque){
            $local_de_estoque_codigos[] = $local_de_estoque->localdeestoque_codigo; 
        }

        $detalhes_locais_de_estoque_nasajon = LocalDeEstoqueNasajon::select()->where('estabelecimento', $codigo_estabelecimento)->whereIn('codigo', $local_de_estoque_codigos)->get();
        
        foreach($detalhes_locais_de_estoque_nasajon as $detalhe_local_de_estoque_nasajon){
            $array_detalhes_locais_de_estoque[$detalhe_local_de_estoque_nasajon->codigo] = [
                'codigo' => $detalhe_local_de_estoque_nasajon->codigo,
                'nome' => $detalhe_local_de_estoque_nasajon->nome,
                'uuid' => $detalhe_local_de_estoque_nasajon->localdeestoque
            ];
        }

        foreach($locais_de_estoque_nasajon as $local_de_estoque){
            $validador = true;
            if(!empty($fields['local_de_estoque_codigo'])){
                if(substr_count(strtolower($array_detalhes_locais_de_estoque[$local_de_estoque->localdeestoque_codigo]['nome']), $fields['local_de_estoque_codigo']) === 0){
                    $validador = false;
                }
            }

            if($validador){
                if(empty($locais_de_estoque[$array_detalhes_locais_de_estoque[$local_de_estoque->localdeestoque_codigo]['codigo']])){
                    $locais_de_estoque[$array_detalhes_locais_de_estoque[$local_de_estoque->localdeestoque_codigo]['codigo']] = [
                        'produto_codigo' => $result->produto,
                        'local_de_estoque_codigo' => $array_detalhes_locais_de_estoque[$local_de_estoque->localdeestoque_codigo]['codigo'],
                        'local_de_estoque_nome' => $array_detalhes_locais_de_estoque[$local_de_estoque->localdeestoque_codigo]['nome'],
                        'local_de_estoque_uuid' => encrypt($array_detalhes_locais_de_estoque[$local_de_estoque->localdeestoque_codigo]['uuid']),
                        'quantidade' => $local_de_estoque->saldo,
                        'unidade' => $result->unidade,
                    ];
                }else{
                    $locais_de_estoque[$array_detalhes_locais_de_estoque[$local_de_estoque->localdeestoque_codigo]['codigo']]['quantidade'] += $local_de_estoque->saldo;
                }
                
            }
        }
        
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $locais_de_estoque
        ];
        return response()->json($response);
    }

    public function adicionar(AjusteEstoqueDigitacaoRequest $request){
        $fields = $request->only('estabelecimento', 'produto_codigo', 'obj_pecas', 'obj_local_estoque', 'motivo', 'produto_descricao');

        $ajuste = "";
        $produto = $fields['produto_codigo']." - ".$fields['produto_descricao'];

        $usuario_portal = Auth::user();
        $usuario_cadastro_uuid = "";
        if(!empty($usuario_portal->codigo_nasajon)){
            $usuario_cadastro_uuid = $usuario_portal->codigo_nasajon;
        }else{
            $usuario_portal = User::find(1);
            $usuario_cadastro_uuid = $usuario_portal->codigo_nasajon;
        }
        $estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);
        $estabelecimento_nasajon = NasajonEstabelecimento::select()->where('codigo', $estabelecimento)->first()->estabelecimento;

        if(isset($fields['obj_pecas'])){
            foreach($fields["obj_pecas"] as $peca){
                $ajuste_fracao = false;
                $uuid_fracao = '';
                if($peca['peca'] == "0"){
                    $query = ProdutoNasajon::select();
                    $query->where('codigo', $fields['produto_codigo']);
                    $result = $query->first();

                    $sql_insert = "select integracoes.alterarsaldoproduto('".$result->produto."',
                        '".$estabelecimento_nasajon."',
                        '".decrypt($peca['local_de_estoque_uuid'])."',
                        ".parserNumber($peca['ajuste']).",
                        '".$usuario_cadastro_uuid."');";
                    try{
                        $insert_nasajon = DB::connection('nasajon')->select($sql_insert);
                        $uuid_fracao = $insert_nasajon[0]->alterarsaldoproduto;
                    }catch(\Exception $e){
                        return  response()->json([
                            'status' => 'error',
                            'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                            'error' => [$e, $sql_insert],
                            'response' => []
                        ], 422);
                    }
                }else{
                    $sql_insert = "select * from integracoes.alterarsaldofracao_v2('".decrypt($peca['produto_lote'])."', ".parserNumber($peca['ajuste']).", '".$usuario_cadastro_uuid."');";
                    try{
                        $insert_nasajon = DB::connection('nasajon')->select($sql_insert);
                        if(isset($insert_nasajon[0]->alterarsaldofracao_v2) && !empty($insert_nasajon[0]->alterarsaldofracao_v2)){
                            $uuid_fracao = str_replace(['{','}'],'',$insert_nasajon[0]->alterarsaldofracao_v2);
                            $uuid_fracao = explode(',',$uuid_fracao);
                            $uuid_fracao = (isset($uuid_fracao[1])) ? $uuid_fracao[1] : $uuid_fracao[0];
                        }
                        $ajuste_fracao = true;
                    }catch(\Exception $e){
                        $messagem = trim(explode(":", explode("\n", $e->getMessage())[0])[3]);

                        if($messagem == 'malformed array literal'){
                            $messagem = str_replace("})", "", str_replace('"', "", trim(explode(":", explode("\n", $e->getMessage())[0])[7])));
                        }
    
                        if(!empty($messagem)){
                            return  response()->json([
                                'status' => 'error',
                                'message' => $messagem,
                                'error' => [explode(":", explode("\n", $e->getMessage())[0]), $sql_insert],
                                'response' => []
                            ], 422);
                        }else{
                            return  response()->json([
                                'status' => 'error',
                                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                                'error' => [$e, $sql_insert,$messagem],
                                'response' => []
                            ], 422);
                        }
                    }
                }
                
                $ajusteEstoqueObj = new AjusteEstoque;
                $ajusteEstoqueObj->estabelecimento_codigo = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);;
                $ajusteEstoqueObj->produto_codigo = $fields["produto_codigo"];
                $ajusteEstoqueObj->pecas_codigo = $peca['peca'];
                if(decrypt($peca['produto_lote']) != 0){
                    $ajusteEstoqueObj->produto_lote_nasajon = decrypt($peca['produto_lote']);
                }
                $ajusteEstoqueObj->local_de_estoque_codigo = $peca['local_de_estoque_codigo'];
                $ajusteEstoqueObj->local_de_estoque_nasajon = decrypt($peca['local_de_estoque_uuid']);
                $ajusteEstoqueObj->quantidade_anterior = parserNumber($peca['quantidade']);
                $ajusteEstoqueObj->quantidade_ajuste = parserNumber($peca['ajuste']);
                $ajusteEstoqueObj->motivos_ajuste_estoque_id = $fields["motivo"];
                $ajusteEstoqueObj->movimento_ajuste_estoque_nasajon = $uuid_fracao;
                $ajusteEstoqueObj->ajuste_fracao = $ajuste_fracao;
                $ajusteEstoqueObj->data = Carbon::NOW()->format('Y-m-d');
                $ajusteEstoqueObj->hora = Carbon::NOW()->format('H:i:s');
                $ajusteEstoqueObj->created_by = Auth::id();
                $ajusteEstoqueObj->save();   
                
                $ajuste = $ajuste."Peça: ".$peca['peca']." Local de Estoque: ". $peca['local_de_estoque_codigo']."Quantidade Anterior: ". parserNumber($peca['quantidade'])." Quantidade do Ajuste: ". parserNumber($peca['ajuste'])."<br>";
            }    
        }
        
        if(isset($fields['obj_local_estoque'])){
            foreach($fields["obj_local_estoque"] as $local_de_estoque){  
                if(empty($local_de_estoque['ajuste'])){
                    $local_de_estoque['ajuste'] = 0;
                }

                $sql_insert = "select integracoes.alterarsaldoproduto('".$local_de_estoque['produto_codigo']."',
                    '".$estabelecimento_nasajon."',
                    '".decrypt($local_de_estoque['local_de_estoque_uuid'])."',
                    ".parserNumber($local_de_estoque['ajuste']).",
                    '".$usuario_cadastro_uuid."');";
                try{
                    $insert_nasajon = DB::connection('nasajon')->select($sql_insert);
                }catch(\Exception $e){
                    return  response()->json([
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                        'error' => [$e, $sql_insert],
                        'response' => []
                    ], 422);
                }
                
                $ajusteEstoqueObj = new AjusteEstoque;
                $ajusteEstoqueObj->estabelecimento_codigo = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);;
                $ajusteEstoqueObj->produto_codigo = $fields["produto_codigo"];
                $ajusteEstoqueObj->local_de_estoque_codigo = $local_de_estoque['local_de_estoque_codigo'];
                $ajusteEstoqueObj->local_de_estoque_nasajon = decrypt($local_de_estoque['local_de_estoque_uuid']);
                $ajusteEstoqueObj->quantidade_anterior = parserNumber($local_de_estoque['quantidade']);
                $ajusteEstoqueObj->quantidade_ajuste = parserNumber($local_de_estoque['ajuste']);
                $ajusteEstoqueObj->motivos_ajuste_estoque_id = $fields["motivo"];
                $ajusteEstoqueObj->movimento_ajuste_estoque_nasajon = $insert_nasajon[0]->alterarsaldoproduto;
                $ajusteEstoqueObj->data = Carbon::NOW()->format('Y-m-d');
                $ajusteEstoqueObj->hora = Carbon::NOW()->format('H:i:s');
                $ajusteEstoqueObj->created_by = Auth::id();
                $ajusteEstoqueObj->save();
            
                $ajuste = $ajuste."Local de Estoque: ".$local_de_estoque['local_de_estoque_codigo']."Quantidade Anterior: ". parserNumber($local_de_estoque['quantidade'])." Quantidade do Ajuste: ". parserNumber($local_de_estoque['ajuste'])."<br>";
            }    
        }

        $this->enviarEmail(str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT), $produto, $ajuste);
        
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => ''
        ]);
    }

    public function filterDigitacao(Request $request){
        $fields = $request->only('estabelecimento', 'peca_codigo', 'local_de_estoque', 'data_de', 'data_ate', 'codigo_produto', 'descricao_produto', 'marca_produto', 'linha_produto', 'grupo_produto', 'subgrupo_produto');

        $query = AjusteEstoque::select();
        if(!empty($fields['estabelecimento'])){
            $query->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
        }
        if(!empty($fields['peca_codigo'])){
            $query->where('pecas_codigo', 'ilike', '%'.$fields['peca_codigo'].'%');
        }
        if(!empty($fields['data_de'])){
            $data_de = Carbon::CreateFromFormat("d/m/Y", $fields['data_de']);
            $query->where('data', '>=', $data_de);
        }
        if(!empty($fields['data_ate'])){
            $data_ate = Carbon::CreateFromFormat("d/m/Y", $fields['data_ate']);
            $query->where('data', '<=', $data_ate);
        }
        if(!empty($fields['codigo_produto'])){
            $query->where('produto_codigo', 'ilike', '%'.$fields['codigo_produto'].'%');
        }
        $query->whereHas('detalhesProduto', function($query) use($fields){
            $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
            if(!empty($fields['descricao_produto'])){
                $query->where('produto_especificacaos.descricao', 'ilike', '%'.$fields['descricao_produto'].'%');
            }
            if(!empty($fields['marca_produto'])){
                $query->where('marca', 'ilike', '%'.$fields['marca_produto'].'%');
            }
            if(!empty($fields['linha_produto'])){
                $query->where('linha', 'ilike', '%'.$fields['linha_produto'].'%');
            }
            if(!empty($fields['grupo_produto'])){
                $query->where('produto_grupos.descricao', 'ilike', '%'.$fields['grupo_produto'].'%');
            }
            if(!empty($fields['subgrupo_produto'])){
                $query->where('subgrupo', 'ilike', '%'.$fields['subgrupo_produto'].'%');
            }
        });
        if(!empty($fields['local_de_estoque'])){
            $query->with(['local_de_estoque' => function($query) use($fields){
                $query->where('nome', 'ilike', '%'.$fields['local_de_estoque'].'%');
            }]);
        }

        $result = $query->get();

        $retorno = [];
        $estabelecimentos = returnEmpresasNasajonView();

        foreach($result as $ajuste_estoque){
            if(!empty($fields['local_de_estoque'])){
                if(!empty($ajuste_estoque->localDeEstoque)){
                    $retorno[] = [
                        'estabelecimento' => $estabelecimentos[intval($ajuste_estoque->estabelecimento_codigo)],
                        'produto_codigo' => $ajuste_estoque->produto_codigo,
                        'produto_descricao' => $ajuste_estoque->detalhesProduto->descricao,
                        'peca_codigo' => $ajuste_estoque->pecas_codigo,
                        'local_de_estoque' => empty($ajuste_estoque->localDeEstoque)? '' : $ajuste_estoque->localDeEstoque->nome,
                        'quantidade_anterior' => $ajuste_estoque->quantidade_anterior,
                        'quantidade_ajuste' => $ajuste_estoque->quantidade_ajuste,
                        'motivo' => $ajuste_estoque->motivo->motivo,
                        'usuario' => $ajuste_estoque->criadoPor->name,
                        'data' => parserData($ajuste_estoque->data)." - ".$ajuste_estoque->hora,
                    ];
                }
            }else{
                $retorno[] = [
                    'estabelecimento' => $estabelecimentos[intval($ajuste_estoque->estabelecimento_codigo)],
                    'produto_codigo' => $ajuste_estoque->produto_codigo,
                    'produto_descricao' => $ajuste_estoque->detalhesProduto->descricao,
                    'peca_codigo' => $ajuste_estoque->pecas_codigo,
                    'local_de_estoque' => empty($ajuste_estoque->localDeEstoque)? '' : $ajuste_estoque->localDeEstoque->nome,
                    'quantidade_anterior' => $ajuste_estoque->quantidade_anterior,
                    'quantidade_ajuste' => $ajuste_estoque->quantidade_ajuste,
                    'motivo' => $ajuste_estoque->motivo->motivo,
                    'usuario' => $ajuste_estoque->criadoPor->name,
                    'data' => parserData($ajuste_estoque->data)." - ".$ajuste_estoque->hora,
                ];
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'ajuste_estoque' => $this->ajusteArrayParaValores($retorno),
            ]
        ];
        return response()->json($response);
    }

    private function enviarEmail($estabelecimento, $produto, $ajuste){
        $EmailObj = new EmailController();

        $email_send = [];

        $variaveis = [
            'usuario' => User::find(Auth::id())->name,
            'produto' => $produto,
            'ajuste' => $ajuste
        ];
        $EmailObj->sendEmailToken($estabelecimento, "ajuste_estoque", $email_send, $variaveis);
	}

    public function ajusteEstoqueRomaneio($numero_nota){
        $coletorRomaneio = LogColetorRomaneio::select()->where('numero_nota',$numero_nota)->whereNull('log_coletor_id');

        $usuario_portal = User::find(1);
        $usuario_cadastro_uuid = $usuario_portal->codigo_nasajon;
        $coletorRomaneios = $coletorRomaneio->get();

   
        foreach($coletorRomaneios as $coletor){
            $sql_insert = "select * from integracoes.alterarsaldofracao_v2('".$coletor->peca_id."', ".parserNumber("0").", '".$usuario_cadastro_uuid."');";

            try{
                $insert_nasajon = DB::connection('nasajon')->select($sql_insert);
                if(isset($insert_nasajon[0]->alterarsaldofracao_v2) && !empty($insert_nasajon[0]->alterarsaldofracao_v2)){
                    $uuid_fracao = str_replace(['{','}'],'',$insert_nasajon[0]->alterarsaldofracao_v2);
                    $uuid_fracao = explode(',',$uuid_fracao);
                    $uuid_fracao = (isset($uuid_fracao[1])) ? $uuid_fracao[1] : $uuid_fracao[0];
                }
                
            }catch(\Exception $e){
                $messagem = trim(explode(":", explode("\n", $e->getMessage())[0])[3]);
                
                
                if(!empty($messagem)){
                    return  response()->json([
                        'status' => 'error',
                        'message' => $messagem,
                        'error' => [$e, $sql_insert],
                        'response' => []
                    ], 422);
                }else{
                    return  response()->json([
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                        'error' => [$e, $sql_insert,$messagem],
                        'response' => []
                    ], 422);
                }
            }

            $ajusteEstoqueObj = new AjusteEstoque;
            $ajusteEstoqueObj->estabelecimento_codigo =$coletor->estabelecimento;
            $ajusteEstoqueObj->produto_codigo = $coletor->produto_codigo;
            $ajusteEstoqueObj->pecas_codigo = $coletor->peca_codigo;
            $ajusteEstoqueObj->produto_lote_nasajon = $coletor->peca_id;
            $ajusteEstoqueObj->quantidade_anterior =parserNumber($coletor->quantidade_romaneio);
            $ajusteEstoqueObj->quantidade_ajuste =parserNumber("0");
            $ajusteEstoqueObj->motivos_ajuste_estoque_id = 4;
            $ajusteEstoqueObj->movimento_ajuste_estoque_nasajon = $uuid_fracao;
            $ajusteEstoqueObj->ajuste_fracao = true;
            $ajusteEstoqueObj->data = Carbon::NOW()->format('Y-m-d');
            $ajusteEstoqueObj->hora = Carbon::NOW()->format('H:i:s');
            $ajusteEstoqueObj->created_by = Auth::id();
            $ajusteEstoqueObj->save(); 
                    
        }

    }

    public function modalRastreabilidade(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('filtros','codigo_estabelecimento','filtro');
        
        if(isset($fields['filtros'])){
            try{
                $fields = decrypt($fields['filtros']);
                $filtro = decrypt($fields['filtros']);
                $filtro = decrypt($filtro['filtro']);
                $fields = decrypt($fields['filtros']);
            }catch(Exception $e){
                $return = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                    'error' => '',
                    'response' => '',
                ];
                return response()->json($return);
            }
        }else{
            $filtro = decrypt($fields['filtro']);
        }

        $query = RastreabilidadeFracoesNasajon::with(['detalhes_produto','notaSaida','notaEntrada']);
        if(!empty($filtro['data_de'])){
            $data_de = Carbon::CreateFromFormat("d/m/Y", $filtro['data_de']);
            $query->where('data', '>=', $data_de->format('Y-m-d'));
        }
        if(!empty($filtro['data_ate'])){
            $data_ate = Carbon::CreateFromFormat("d/m/Y", $filtro['data_ate']);
            $query->where('data', '<=', $data_ate->format('Y-m-d'));
        }
        if(!empty($filtro['tipo'])){
            $query->where('acao', $filtro['tipo']);
        }
        if(!empty($filtro['codigo_produto'])){
            $query->where('produto', $filtro['codigo_produto']);
        }if(!empty($fields['codigo_estabelecimento'])){
            $query->where('proprietario', $fields['codigo_estabelecimento']);
        }
        $query->with(['detalhes_produto' => function($query) use($filtro){
            if(!empty($filtro['descricao_produto'])){
                $query->where('descricao', 'ilike', '%'.$filtro['descricao_produto'].'%');
            }
            if(!empty($filtro['marca_produto'])){
                $query->where('marca', 'ilike', '%'.$filtro['marca_produto'].'%');
            }
            if(!empty($filtro['linha_produto'])){
                $query->where('linha', 'ilike', '%'.$filtro['linha_produto'].'%');
            }
            if(!empty($filtro['grupo_produto'])){
                $query->where('grupo', 'ilike', '%'.$filtro['grupo_produto'].'%');
            }
            if(!empty($filtro['subgrupo_produto'])){
                $query->where('subgrupo', 'ilike', '%'.$filtro['subgrupo_produto'].'%');
            }
        }]);
       
        $query->orderBy('data');

        $result = $query->get();
        $estabelecimentos = returnEmpresasNasajonView();
        $retorno = [];
        $total = 0;

        $result->each(function($query) use (&$retorno,&$total,$estabelecimentos){
            if(!empty($query->detalhes_produto)){
                $id_documento = '';
                $tipo_documento = '';

                if(!empty($query->notaSaida) && $query->acao == 'Saída'){
                    $id_documento = $query->notaSaida->id;
                    $tipo_documento = 'Saída';
                }else if(!empty($query->notaEntrada) && $query->acao == 'Entrada'){
                    $id_documento = $query->notaEntrada['Identificador Documento'];
                    $tipo_documento = 'Entrada';
                }

                $retorno[] = [
                    'proprietario' => $estabelecimentos[(int)$query->proprietario],
                    'detentor' => $estabelecimentos[(int)$query->detentor],
                    'produto' => (!empty($query->detalhes_produto->descricao)) ? $query->detalhes_produto->descricao : '',
                    'id_documento' => $id_documento,
                    'tipo_documento' => $tipo_documento,
                    'documento' => $query->documento,
                    'data' => parserDataEHora($query->data_hora_criacao),
                    'quantidade' => ($query->quantidade > 0) ? parserQtd($query->quantidade) : '',
                    'acao' => $query->acao,
                    'usuario' => $query->nome,
                ];

                $total += $query->quantidade;
            }
        });

        $total = ($total > 0) ? parserQtd($total) : '';

        return view('programs.ajuste_estoque.modal.rastreabilidade')->with(['dados' => $retorno, 'total' => $total]);
    }
 
}
