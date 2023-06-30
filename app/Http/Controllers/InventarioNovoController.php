<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\Http\Requests\InventarioNovoIniciarRequest;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Exports\InventarioExport;
use App\Exports\InventarioProdutoExport;
use App\Exports\InventarioLogExport;
use App\Exports\InventarioPecaNaoEncontradaExport;

use App\InventarioCodigo;
use App\InventarioCodigoEstoqueAtual;
use App\FracoesDisponiveisNasajon;
use App\FracoesNasajon;
use App\InventarioCodigoLeitura;
use App\InventarioCodigoLeituraLog;
use App\ProdutoNasajon;
use App\InventarioProduto;
use App\ProdutosEstoque;
use App\Movimentacao;

class InventarioNovoController extends Controller
{

    private $estabelecimentos = [];

    private $linhas = 3000000;

    public function __construct(){

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[0]);
        unset($estabelecimentos[1]);
        unset($estabelecimentos[2]);
        unset($estabelecimentos[3]);
        unset($estabelecimentos[4]);
        unset($estabelecimentos[6]);
        unset($estabelecimentos[7]);
        unset($estabelecimentos[30]);

        $this->estabelecimentos = $estabelecimentos;
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\InventarioContagemNovo") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\InventarioContagemNovo');

        return view("programs.inventario_novo.index")->with(['estabelecimentos' => $this->estabelecimentos ]);
    }

    public function modalIniciar(Request $request){  
        return view('programs.inventario_novo.modal.iniciar')->with(['estabelecimentos' => $this->estabelecimentos]);
    }

    public function modalFinalizar(Request $request){  
        $id = $request->only(['id'])['id'];
        
        $query = InventarioCodigo::select();
        $query->where('id', '=', $id);
        $result = $query->first();

        $dados = [
            'id' => encrypt($result->id),
            'codigo' => $result->codigo,
            'estabelecimento' => $this->estabelecimentos[$result->estabelecimento]
        ];

        return view('programs.inventario_novo.modal.finalizar')->with(['dados' => $dados]);
    }

    public function modalDetalhesProdutos(Request $request){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');
        
        $campos = $request->only('id');

        $dados = $this->retornoProduto(new Request($campos)); 

        foreach($dados as $key => $dado){
            $dados[$key]['saldo_estoque']  = ($dados[$key]['saldo_estoque'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['saldo_estoque']);
            $dados[$key]['saldo']  = ($dados[$key]['saldo'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['saldo']);
            $dados[$key]['volume']  = ($dados[$key]['volume'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['volume']);
            $dados[$key]['contagem_1_estoque']  = ($dados[$key]['contagem_1_estoque'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['contagem_1_estoque']);
            $dados[$key]['contagem_2_estoque']  = ($dados[$key]['contagem_2_estoque'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['contagem_2_estoque']);
            $dados[$key]['contagem_3_estoque']  = ($dados[$key]['contagem_3_estoque'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['contagem_3_estoque']);
            $dados[$key]['diferenca_1_saldo_estoque']  = ($dados[$key]['diferenca_1_saldo_estoque'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['diferenca_1_saldo_estoque']);
            $dados[$key]['diferenca_2_saldo_estoque']  = ($dados[$key]['diferenca_2_saldo_estoque'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['diferenca_2_saldo_estoque']);
            $dados[$key]['diferenca_3_saldo_estoque']  = ($dados[$key]['diferenca_3_saldo_estoque'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['diferenca_3_saldo_estoque']);
            $dados[$key]['diferenca_1_estoque']  = ($dados[$key]['diferenca_1_estoque'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['diferenca_1_estoque']);
            $dados[$key]['diferenca_2_estoque']  = ($dados[$key]['diferenca_2_estoque'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['diferenca_2_estoque']);
            $dados[$key]['diferenca_3_estoque']  = ($dados[$key]['diferenca_3_estoque'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['diferenca_3_estoque']);
            $dados[$key]['contagem_1_volume']  = ($dados[$key]['contagem_1_volume'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['contagem_1_volume']);
            $dados[$key]['contagem_2_volume']  = ($dados[$key]['contagem_2_volume'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['contagem_2_volume']);
            $dados[$key]['contagem_3_volume']  = ($dados[$key]['contagem_3_volume'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['contagem_3_volume']);
            $dados[$key]['diferenca_1_volume']  = ($dados[$key]['diferenca_1_volume'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['diferenca_1_volume']);
            $dados[$key]['diferenca_2_volume']  = ($dados[$key]['diferenca_2_volume'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['diferenca_2_volume']);
            $dados[$key]['diferenca_3_volume']  = ($dados[$key]['diferenca_3_volume'])== 'ZERO'?  '0,00' : parserValor($dados[$key]['diferenca_3_volume']);
        }
        
        return view('programs.inventario_novo.modal.detalhes_produto')->with(['dados' => $dados]);
    }

    public function modalDetalhesPecas(Request $request){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');

        $campos = $request->only('id');

        $dados = $this->retornoPecas(new Request($campos)); 
        
        return view('programs.inventario_novo.modal.detalhes_peca')->with(['dados' => $dados]);
    }

    public function iniciar(InventarioNovoIniciarRequest $request){
        $fields = $request->only(
            'estabelecimento_iniciar',
            'codigo_iniciar'            
        );

        $InventarioCodigoObj = new InventarioCodigo;
        $InventarioCodigoObj->estabelecimento = $fields['estabelecimento_iniciar'];
        $InventarioCodigoObj->codigo = strtoupper($fields['codigo_iniciar']);
        $InventarioCodigoObj->data_inicial = Carbon::now();
        $InventarioCodigoObj->verificacao_estoque_atual = true;
        $InventarioCodigoObj->created_by = Auth::id();
        $InventarioCodigoObj->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Iniciado o Processo de Inventário no estabelecimento '.$this->estabelecimentos[$fields['estabelecimento_iniciar']].'.',
            'error' => [],
            'response' => []
        ]);
    }

    public function finalizar(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        
        
        $InventarioCodigoObj = InventarioCodigo::find($id);
        $InventarioCodigoObj->data_final = Carbon::now();
        $InventarioCodigoObj->deleted_by = Auth::id();
        $InventarioCodigoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtro(Request $request){
        $campos = $request->only('estabelecimento', 'ativo');

        $InventarioCodigoObj = InventarioCodigo::select();
        $InventarioCodigoObj->with([
            'estoqueAtualTotal', 
            'estoqueVolumeAtualTotal', 
            'contagemAtualTotal',
            'contagem1VolumeAtualTotal',
            'contagem2VolumeAtualTotal',
            'contagem3VolumeAtualTotal',
            'contagemPecaNaoEncontrado'
        ]);
        if(!empty($campos['estabelecimento'])){
            $InventarioCodigoObj->where('estabelecimento', $campos['estabelecimento']);
        }
        if(!empty($campos['ativo'])){
            $InventarioCodigoObj->whereNull('data_final');
        }
        $InventarioCodigoObjd = $InventarioCodigoObj->get();

        $retorno = [];
        foreach($InventarioCodigoObjd as $inventario){
            $retorno[] = [
                'id' => $inventario->id,
                'codigo' => $inventario->codigo,
                'estabelecimento' => $this->estabelecimentos[$inventario->estabelecimento],
                'data_inicial' => parserDataHoraSegundo($inventario->data_inicial),
                'data_final' => empty($inventario->data_final)? '' : parserDataHoraSegundo($inventario->data_final),
                'estoque' => empty($inventario->estoqueAtualTotal)? 0 : parserValor($inventario->estoqueAtualTotal->total),
                'volume' => empty($inventario->estoqueVolumeAtualTotal)? 0 : parserValor($inventario->estoqueVolumeAtualTotal->total),
                'contagem_1_estoque' =>  empty($inventario->contagemAtualTotal)? 0 : parserValor($inventario->contagemAtualTotal->contagem_1_total),
                'contagem_1_volume' => empty($inventario->contagem1VolumeAtualTotal)? 0 : parserValor($inventario->contagem1VolumeAtualTotal->total),
                'contagem_2_estoque' => empty($inventario->contagemAtualTotal)? 0 : parserValor($inventario->contagemAtualTotal->contagem_2_total),
                'contagem_2_volume' => empty($inventario->contagem2VolumeAtualTotal)? 0 : parserValor($inventario->contagem2VolumeAtualTotal->total),
                'contagem_3_estoque' => empty($inventario->contagemAtualTotal)? 0 : parserValor($inventario->contagemAtualTotal->contagem_3_total),
                'contagem_3_volume' => empty($inventario->contagem3VolumeAtualTotal)? 0 : parserValor($inventario->contagem3VolumeAtualTotal->total),
                'peca_nao_encontrada' => empty($inventario->contagemPecaNaoEncontrado)? 0 : parserValor($inventario->contagemPecaNaoEncontrado->total),
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [$InventarioCodigoObj->toSql()],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function carregarEstoqueAtual(){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');

        $InventarioCodigoObj = InventarioCodigo::select()->where('verificacao_estoque_atual', true)->orderBy('created_at')->get();

        foreach($InventarioCodigoObj as $inventario){
            $inventario->verificacao_estoque_atual = false;
            $inventario->save();

            if($inventario->estabelecimento == '20'){
                $estabelecimentos = ['03', '04'];
            }else{
                $estabelecimentos = [str_pad($inventario->estabelecimento, 2, 0, STR_PAD_LEFT)]; 
            }

            $FracoesDisponiveisNasajonObj = FracoesDisponiveisNasajon::select()->whereIn('estabelecimento_codigo', $estabelecimentos)->get();
            
            foreach($FracoesDisponiveisNasajonObj as $fracao){
                $InventarioCodigoEstoqueAtualObj = new InventarioCodigoEstoqueAtual;
                $InventarioCodigoEstoqueAtualObj->inventario_codigos_id = $inventario->id;
                $InventarioCodigoEstoqueAtualObj->estabelecimento_posse = $fracao->estabelecimento_codigo;
                $InventarioCodigoEstoqueAtualObj->produto_codigo = $fracao->produto_codigo;
                $InventarioCodigoEstoqueAtualObj->produto_id = $fracao->produto_id;
                $InventarioCodigoEstoqueAtualObj->fracao_codigo = $fracao->fracao_codigo;
                $InventarioCodigoEstoqueAtualObj->fracao_id = $fracao->fracao_id;
                $InventarioCodigoEstoqueAtualObj->endereco = $fracao->endereco;
                $InventarioCodigoEstoqueAtualObj->empenhado = $fracao->empenhado;
                $InventarioCodigoEstoqueAtualObj->saldo = $fracao->saldo;
                $InventarioCodigoEstoqueAtualObj->fracao_pai = $fracao->fracao_pai;
                $InventarioCodigoEstoqueAtualObj->peca_veio_de_fracionamento = $fracao->peca_veio_de_fracionamento;
                $InventarioCodigoEstoqueAtualObj->created_by = 1;
                $InventarioCodigoEstoqueAtualObj->save();
            }
        }

        $this->carregarEstoqueProduto();
    }

    public function carregarDadosDeLeitura(){
        $InventarioCodigoLeituraLogObj = InventarioCodigoLeituraLog::select()->where('importado', false)->orderBy('created_at', 'desc')->get();

        foreach($InventarioCodigoLeituraLogObj as $log){            
            $saldo = 0;
            echo '\n'.$log->fracao_codigo.' Teste\n';
            $FracoesDisponiveisNasajon = FracoesNasajon::select()->with('estabelecimentoProprietarioDetalhes')->where('codigo', 'ilike', $log->fracao_codigo)->first();
            
            if(!empty($FracoesDisponiveisNasajon)){
                if(strlen($log->fracao_codigo) == 20){
                    $teste_peca = substr($log->fracao_codigo, 13, 19);

                    $teste_peca = floatval($teste_peca);
                    
                    $teste_peca = $teste_peca / 100;

                    $saldo = $teste_peca;
                }

                $InventarioCodigoLeituraObj = InventarioCodigoLeitura::select()
                ->where('inventario_codigos_id', $log->inventario_codigos_id)
                ->where('codigo_lido', $log->fracao_codigo)
                ->first();

                if(empty($InventarioCodigoLeituraObj)){
                    $InventarioCodigoLeituraObj = new InventarioCodigoLeitura;
                }
                
                $InventarioCodigoLeituraObj->inventario_codigos_id = $log->inventario_codigos_id; 
                $InventarioCodigoLeituraObj->estabelecimento_posse = str_pad($FracoesDisponiveisNasajon->estabelecimentoProprietarioDetalhes->codigo, 2, 0, STR_PAD_LEFT);
                $InventarioCodigoLeituraObj->fracao_codigo = $FracoesDisponiveisNasajon->codigo;
                $InventarioCodigoLeituraObj->codigo_lido = $log->fracao_codigo;
                $InventarioCodigoLeituraObj->produto_codigo = $FracoesDisponiveisNasajon->produto_codigo;
                $InventarioCodigoLeituraObj->saldo = $FracoesDisponiveisNasajon->quantidade;
                if($log->contagem == 1){
                    $InventarioCodigoLeituraObj->contagem_1 = $saldo;
                    $InventarioCodigoLeituraObj->endereco = $log->endereco;
                }else if($log->contagem == 2){
                    $InventarioCodigoLeituraObj->contagem_2 = $saldo;
                    $InventarioCodigoLeituraObj->endereco_lido_2 = $log->endereco;
                }else{
                    $InventarioCodigoLeituraObj->contagem_3 = $saldo;
                    $InventarioCodigoLeituraObj->endereco_lido_3 = $log->endereco;
                }
                $InventarioCodigoLeituraObj->status = 'Não';
                $InventarioCodigoLeituraObj->endereco = $log->endereco;
                $InventarioCodigoLeituraObj->created_by = $log->created_by;
                $InventarioCodigoLeituraObj->importar_inventario_produto = true;
                $InventarioCodigoLeituraObj->save();

                $log->importado = true;
                $log->save();
            }else{
                $peca_field = [];
                $peca_field[] = substr($log->fracao_codigo, 1);
                $peca_field[] = substr($log->fracao_codigo, 0, 8);
                $peca_field[] = substr($log->fracao_codigo, -8);
                $peca_field[] = substr($log->fracao_codigo, 0, 11);
                $peca_field[] = substr($log->fracao_codigo, 0, 13);
                $peca_field[] = substr($log->fracao_codigo, 0, 13).".".substr($log->fracao_codigo, 13, 2);
                $peca_field[] = substr($log->fracao_codigo, 0, 13).".".substr($log->fracao_codigo, 14, 2);
                $peca_field[] = substr($log->fracao_codigo, 0, 13).".".substr($log->fracao_codigo, 15, 1);
                if(strpos($log->fracao_codigo, '.') !== false){
                    $temp_explode = explode('.', $log->fracao_codigo);
                    if(count($temp_explode) === 2){
                        $temp_explode[0] = str_pad($temp_explode[0], 6, '0', STR_PAD_LEFT);
                        $return_explode = $temp_explode[0].'.'.$temp_explode[1];
                        $peca_field[] = $return_explode;
                    }
                    unset($temp_explode);
                }

                $FracoesDisponiveisNasajon = FracoesNasajon::select()->with('estabelecimentoProprietarioDetalhes')->whereIn('codigo', $peca_field)->first();

                if(!empty($FracoesDisponiveisNasajon)){
                    if(strlen($log->fracao_codigo) == 20){
                        $teste_peca = substr($log->fracao_codigo, 13, 19);

                        $teste_peca = floatval($teste_peca);
                        
                        $teste_peca = $teste_peca / 100;

                        $saldo = $teste_peca;
                    }

                    $InventarioCodigoLeituraObj = InventarioCodigoLeitura::select()
                    ->where('inventario_codigos_id', $log->inventario_codigos_id)
                    ->where('codigo_lido', $log->fracao_codigo)
                    ->first();

                    if(empty($InventarioCodigoLeituraObj)){
                        $InventarioCodigoLeituraObj = new InventarioCodigoLeitura;
                    }
                    
                    $InventarioCodigoLeituraObj->inventario_codigos_id = $log->inventario_codigos_id; 
                    $InventarioCodigoLeituraObj->estabelecimento_posse = str_pad($FracoesDisponiveisNasajon->estabelecimentoProprietarioDetalhes->codigo, 2, 0, STR_PAD_LEFT);
                    $InventarioCodigoLeituraObj->fracao_codigo = $FracoesDisponiveisNasajon->codigo;
                    $InventarioCodigoLeituraObj->codigo_lido = $log->fracao_codigo;
                    $InventarioCodigoLeituraObj->produto_codigo = $FracoesDisponiveisNasajon->produto_codigo;
                    $InventarioCodigoLeituraObj->saldo = $FracoesDisponiveisNasajon->quantidade;
                    if($log->contagem == 1){
                        $InventarioCodigoLeituraObj->contagem_1 = $saldo;
                        $InventarioCodigoLeituraObj->endereco = $log->endereco;
                    }else if($log->contagem == 2){
                        $InventarioCodigoLeituraObj->contagem_2 = $saldo;
                        $InventarioCodigoLeituraObj->endereco_lido_2 = $log->endereco;
                    }else{
                        $InventarioCodigoLeituraObj->contagem_3 = $saldo;
                        $InventarioCodigoLeituraObj->endereco_lido_3 = $log->endereco;
                    }
                    $InventarioCodigoLeituraObj->status = 'Não';
                    $InventarioCodigoLeituraObj->endereco = $log->endereco;
                    $InventarioCodigoLeituraObj->created_by = $log->created_by;
                    $InventarioCodigoLeituraObj->importar_inventario_produto = true;
                    $InventarioCodigoLeituraObj->save();

                    $log->importado = true;
                    $log->save();
                }
            }                      
        }
        echo "\nfim\n";
        return true;
    }

    public function importarInventarioProduto(){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');

        $InventarioCodigoLeituraObj = InventarioCodigoLeitura::select()
            ->whereNotNull('produto_codigo')
            ->where('importar_inventario_produto', true)
            ->get();

        foreach($InventarioCodigoLeituraObj as $inventario){
            $produto = ProdutoNasajon::where("codigodebarras", $inventario->fracao_codigo)->first();
            if(!is_null($inventario->contagem_1)){
                $InventarioProdutoObj = InventarioProduto::select()
                ->where('estabelecimento', intval($inventario->estabelecimento_posse))
                ->where('codigo_barras', $inventario->fracao_codigo)
                ->where('contagem', 1)
                ->first();

                if(empty($InventarioProdutoObj)){
                    $InventarioProdutoObj = new InventarioProduto();
                    $InventarioProdutoObj->estabelecimento = intval($inventario->estabelecimento_posse);
                    $InventarioProdutoObj->codigo_barras = $inventario->fracao_codigo;
                    $InventarioProdutoObj->endereco = $inventario->endereco;
                    $InventarioProdutoObj->contagem = 1;
                    $InventarioProdutoObj->codigo_produto = $inventario->produto_codigo;
                    $InventarioProdutoObj->quantidade_produto = $inventario->contagem_1;
                    $InventarioProdutoObj->created_by = $inventario->created_by;
                    $InventarioProdutoObj->produto_codigobarras = empty($produto)? false : true;
                    $InventarioProdutoObj->save();

                    $inventario->importar_inventario_produto = false;
                    $inventario->save();
                }else{
                    $InventarioProdutoObj->endereco = $inventario->endereco;
                    $InventarioProdutoObj->quantidade_produto = $inventario->contagem_1;
                    $InventarioProdutoObj->created_by = $inventario->created_by;
                    $InventarioProdutoObj->save();

                    $inventario->importar_inventario_produto = false;
                    $inventario->save();
                }                
            }
            if(!is_null($inventario->contagem_2)){
                $InventarioProdutoObj = InventarioProduto::select()
                ->where('estabelecimento', intval($inventario->estabelecimento_posse))
                ->where('codigo_barras', $inventario->fracao_codigo)
                ->where('contagem', 2)
                ->first();

                if(empty($InventarioProdutoObj)){
                    $InventarioProdutoObj = new InventarioProduto();
                    $InventarioProdutoObj->estabelecimento = intval($inventario->estabelecimento_posse);
                    $InventarioProdutoObj->codigo_barras = $inventario->fracao_codigo;
                    $InventarioProdutoObj->endereco = $inventario->endereco;
                    $InventarioProdutoObj->contagem = 2;
                    $InventarioProdutoObj->codigo_produto = $inventario->produto_codigo;
                    $InventarioProdutoObj->quantidade_produto = $inventario->contagem_2;
                    $InventarioProdutoObj->created_by = $inventario->created_by;
                    $InventarioProdutoObj->produto_codigobarras = empty($produto)? false : true;
                    $InventarioProdutoObj->save();

                    $inventario->importar_inventario_produto = false;
                    $inventario->save();
                }else{
                    $InventarioProdutoObj->endereco = $inventario->endereco;
                    $InventarioProdutoObj->quantidade_produto = $inventario->contagem_1;
                    $InventarioProdutoObj->created_by = $inventario->created_by;
                    $InventarioProdutoObj->save();

                    $inventario->importar_inventario_produto = false;
                    $inventario->save();
                } 
            }
            if(!is_null($inventario->contagem_3)){
                $InventarioProdutoObj = InventarioProduto::select()
                ->where('estabelecimento', intval($inventario->estabelecimento_posse))
                ->where('codigo_barras', $inventario->fracao_codigo)
                ->where('contagem', 3)
                ->first();

                if(empty($InventarioProdutoObj)){
                    $InventarioProdutoObj = new InventarioProduto();
                    $InventarioProdutoObj->estabelecimento = intval($inventario->estabelecimento_posse);
                    $InventarioProdutoObj->codigo_barras = $inventario->fracao_codigo;
                    $InventarioProdutoObj->endereco = $inventario->endereco;
                    $InventarioProdutoObj->contagem = 3;
                    $InventarioProdutoObj->codigo_produto = $inventario->produto_codigo;
                    $InventarioProdutoObj->quantidade_produto = $inventario->contagem_3;
                    $InventarioProdutoObj->created_by = $inventario->created_by;
                    $InventarioProdutoObj->produto_codigobarras = empty($produto)? false : true;
                    $InventarioProdutoObj->save();

                    $inventario->importar_inventario_produto = false;
                    $inventario->save();
                }else{
                    $InventarioProdutoObj->endereco = $inventario->endereco;
                    $InventarioProdutoObj->quantidade_produto = $inventario->contagem_1;
                    $InventarioProdutoObj->created_by = $inventario->created_by;
                    $InventarioProdutoObj->save();

                    $inventario->importar_inventario_produto = false;
                    $inventario->save();
                } 
            }
        }
    }

    public function retornoPecas(Request $request){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');

        $estabelecimentos = returnEmpresasNasajonView();

        $campos = $request->only('id');

        $InventarioCodigoObj = InventarioCodigo::with([
            'produtosEstoqueAtual.produtoEspecificacao.produtoGrupo', 
            'produtosEstoquLeitura.produtoEspecificacao.produtoGrupo'
            ])->find($campos['id']);

        $dados = [];
        foreach($InventarioCodigoObj->produtosEstoqueAtual as $estoque){
            if(empty($dados[strtoupper($estoque->fracao_codigo)])){
                $dados[strtoupper($estoque->fracao_codigo)] = [
                    'estabelecimento_posse' => empty($estoque->estabelecimento_posse)? '' : $estabelecimentos[intval($estoque->estabelecimento_posse)],
                    'produto_codigo' => $estoque->produto_codigo,
                    'produto_descricao' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->descricao,
                    'grupo' => empty($estoque->produtoEspecificacao->produtoGrupo)? '' : $estoque->produtoEspecificacao->produtoGrupo->descricao,
                    'marca' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->marca,
                    'linha' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->linha,
                    'unidade' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->unidade,
                    'fracao_codigo' => " ".strtoupper($estoque->fracao_codigo),
                    'codigo_lido' => "",
                    'lido' => 'SIM',
                    'status' => 'Disponível',
                    'saldo' => $estoque->saldo,
                    'cont_qtde_lida_1' => '',
                    'cont_qtde_lida_2' => '',
                    'cont_qtde_lida_3' => '',
                    'dif_qtde_1' => '',
                    'dif_qtde_2' => '',
                    'dif_qtde_3' => '',
                    'endereco' => $estoque->endereco,
                    'endereco_lido' => '',
                    'endereco_lido_2' => '',
                    'endereco_lido_3' => '',
                    'lido_contagem_1' => '',
                    'lido_contagem_2' => '',
                    'lido_contagem_3' => '',
                ];
            }          
        }

        foreach($InventarioCodigoObj->produtosEstoquLeitura as $estoque){
            if(empty($dados[strtoupper($estoque->fracao_codigo)])){
                $dados[strtoupper($estoque->fracao_codigo)] = [
                    'estabelecimento_posse' => $estabelecimentos[$InventarioCodigoObj->estabelecimento],
                    'produto_codigo' => $estoque->produto_codigo,
                    'produto_descricao' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->descricao,
                    'grupo' => empty($estoque->produtoEspecificacao->produtoGrupo)? '' : $estoque->produtoEspecificacao->produtoGrupo->descricao,
                    'marca' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->marca,
                    'linha' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->linha,
                    'unidade' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->unidade,
                    'fracao_codigo' => " ".strtoupper($estoque->fracao_codigo),
                    'codigo_lido' => " ".strtoupper($estoque->codigo_lido),
                    'lido' => $estoque->status == 'Disponível'? 'SIM' : "NÃO",
                    'status' => $estoque->status,
                    'saldo' => '',    
                    'cont_qtde_lida_1' => $estoque->saldo,
                    'cont_qtde_lida_2' => empty($estoque->contagem_2)? '' :$estoque->saldo,
                    'cont_qtde_lida_3' => empty($estoque->contagem_3)? '' :$estoque->saldo,
                    'dif_qtde_1' => '',
                    'dif_qtde_2' => '',
                    'dif_qtde_3' => '',              
                    'endereco' => '',
                    'endereco_lido' => $estoque->endereco,
                    'endereco_lido_2' => empty($estoque->endereco_lido_2)? '' :$estoque->endereco_lido_2,
                    'endereco_lido_3' => empty($estoque->endereco_lido_3)? '' :$estoque->endereco_lido_3,
                    'lido_contagem_1' => 'OK',
                    'lido_contagem_2' => empty($estoque->contagem_2)? '' : 'OK',
                    'lido_contagem_3' => empty($estoque->contagem_3)? '' : 'OK',
                ];
            }else{
                $dados[strtoupper($estoque->fracao_codigo)]['codigo_lido'] = " ".strtoupper($estoque->codigo_lido);
                $dados[strtoupper($estoque->fracao_codigo)]['lido_contagem_1'] = 'OK';
                $dados[strtoupper($estoque->fracao_codigo)]['lido_contagem_2'] = empty($estoque->contagem_2)? '' : 'OK';
                $dados[strtoupper($estoque->fracao_codigo)]['lido_contagem_3'] = empty($estoque->contagem_3)? '' : 'OK';
                $dados[strtoupper($estoque->fracao_codigo)]['quantia_lida'] = empty($estoque->saldo)? '0' : $estoque->saldo;
            }         
        }

        foreach($dados as $key => $dado){
            $dados[$key]['saldo'] = empty($dados[$key]['saldo'])? 0 : $dados[$key]['saldo'];
            $dados[$key]['cont_qtde_lida_1'] = empty($dados[$key]['cont_qtde_lida_1'])? 0 : $dados[$key]['cont_qtde_lida_1'];
            $dados[$key]['cont_qtde_lida_2'] = empty($dados[$key]['cont_qtde_lida_2'])? 0 : $dados[$key]['cont_qtde_lida_2'];
            $dados[$key]['cont_qtde_lida_3'] = empty($dados[$key]['cont_qtde_lida_3'])? 0 : $dados[$key]['cont_qtde_lida_3'];

            $dados[$key]['dif_qtde_1'] = $dados[$key]['cont_qtde_lida_1'] -$dados[$key]['saldo'];
            $dados[$key]['dif_qtde_2'] = $dados[$key]['cont_qtde_lida_2'] -$dados[$key]['saldo'];
            $dados[$key]['dif_qtde_3'] = $dados[$key]['cont_qtde_lida_3'] -$dados[$key]['saldo'];

            $dados[$key]['saldo'] = empty($dados[$key]['saldo'])? 'ZERO' : $dados[$key]['saldo'];
            $dados[$key]['cont_qtde_lida_1'] = empty($dados[$key]['cont_qtde_lida_1'])? 'ZERO' : $dados[$key]['cont_qtde_lida_1'];
            $dados[$key]['cont_qtde_lida_2'] = empty($dados[$key]['cont_qtde_lida_2'])? 'ZERO' : $dados[$key]['cont_qtde_lida_2'];
            $dados[$key]['cont_qtde_lida_3'] = empty($dados[$key]['cont_qtde_lida_3'])? 'ZERO' : $dados[$key]['cont_qtde_lida_3'];
            $dados[$key]['dif_qtde_1'] = empty($dados[$key]['dif_qtde_1'])? 'ZERO' : $dados[$key]['dif_qtde_1'];
            $dados[$key]['dif_qtde_2'] = empty($dados[$key]['dif_qtde_2'])? 'ZERO' : $dados[$key]['dif_qtde_2'];
            $dados[$key]['dif_qtde_3'] = empty($dados[$key]['dif_qtde_3'])? 'ZERO' : $dados[$key]['dif_qtde_3'];
        }

        return $dados;
    }

    public function retornoProduto(Request $request){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');
        
        $campos = $request->only('id');

        $estabelecimentos = returnEmpresasNasajonView();

        $InventarioCodigoObj = InventarioCodigo::with([
            'produtosEstoqueAtual.produtoEspecificacao.produtoGrupo', 
            'produtosEstoquLeitura.produtoEspecificacao.produtoGrupo'
            ])->find($campos['id']);

        $dados = [];
        foreach($InventarioCodigoObj->produtosEstoqueAtual as $estoque){
            if(empty($dados[strval($estoque->produto_codigo)])){
                $dados[strval($estoque->produto_codigo)] = [
                    'estabelecimento_posse' => $estabelecimentos[intval($estoque->estabelecimento_posse)],
                    'produto_codigo' => $estoque->produto_codigo,
                    'produto_descricao' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->descricao,
                    'grupo' => empty($estoque->produtoEspecificacao->produtoGrupo)? '' : $estoque->produtoEspecificacao->produtoGrupo->descricao,
                    'marca' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->marca,
                    'linha' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->linha,
                    'unidade' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->unidade,
                    'saldo_estoque' => $estoque->estoque_saldo,
                    'saldo' => $estoque->saldo,
                    'volume' => 1,
                    'contagem_1_estoque' => 0,
                    'contagem_2_estoque' => 0,
                    'contagem_3_estoque' => 0,
                    'diferenca_1_saldo_estoque' => 0,
                    'diferenca_2_saldo_estoque' => 0,
                    'diferenca_3_saldo_estoque' => 0,
                    'diferenca_1_estoque' => 0,
                    'diferenca_2_estoque' => 0,
                    'diferenca_3_estoque' => 0,
                    'contagem_1_volume' => 0,
                    'contagem_2_volume' => 0,
                    'contagem_3_volume' => 0,                    
                    'diferenca_1_volume' => 0,                    
                    'diferenca_2_volume' => 0,
                    'diferenca_3_volume' => 0,                    
                ];
            }else{
                $dados[strval($estoque->produto_codigo)]['saldo'] += $estoque->saldo;
                $dados[strval($estoque->produto_codigo)]['volume']++;
            }           
        }

        foreach($InventarioCodigoObj->produtosEstoquLeitura as $estoque){
            if(empty($dados[strval($estoque->produto_codigo)])){
                $dados[strval($estoque->produto_codigo)] = [
                    'estabelecimento_posse' => empty($estoque->produtoEspecificacao)? '' : $estabelecimentos[intval($estoque->estabelecimento_posse)],
                    'produto_codigo' => $estoque->produto_codigo,
                    'produto_descricao' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->descricao,
                    'grupo' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->produtoGrupo->descricao,
                    'marca' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->marca,
                    'linha' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->linha,
                    'unidade' => empty($estoque->produtoEspecificacao)? '' : $estoque->produtoEspecificacao->unidade,
                    'saldo_estoque' => 0,
                    'saldo' => 0,
                    'volume' => 0,
                    'contagem_1_estoque' => $estoque->contagem_1,
                    'contagem_2_estoque' => $estoque->contagem_2,
                    'contagem_3_estoque' => $estoque->contagem_3,
                    'diferenca_1_saldo_estoque' => 0,
                    'diferenca_2_saldo_estoque' => 0,
                    'diferenca_3_saldo_estoque' => 0,
                    'diferenca_1_estoque' => 0,
                    'diferenca_2_estoque' => 0,
                    'diferenca_3_estoque' => 0,
                    'contagem_1_volume' => empty($estoque->contagem_1)? 0 : 1,
                    'contagem_2_volume' => empty($estoque->contagem_2)? 0 : 1,
                    'contagem_3_volume' => empty($estoque->contagem_3)? 0 : 1,                    
                    'diferenca_1_volume' => 0,                    
                    'diferenca_2_volume' => 0,
                    'diferenca_3_volume' => 0,  
                ];
            }else{
                $dados[strval($estoque->produto_codigo)]['contagem_1_estoque'] += $estoque->contagem_1;
                $dados[strval($estoque->produto_codigo)]['contagem_2_estoque'] += empty($estoque->contagem_2)? 0 : $estoque->contagem_2;
                $dados[strval($estoque->produto_codigo)]['contagem_3_estoque'] += empty($estoque->contagem_3)? 0 : $estoque->contagem_3;
                $dados[strval($estoque->produto_codigo)]['contagem_1_volume']++;
                $dados[strval($estoque->produto_codigo)]['contagem_2_volume'] += empty($estoque->contagem_2)? 0 : 1;
                $dados[strval($estoque->produto_codigo)]['contagem_3_volume'] += empty($estoque->contagem_3)? 0 : 1;
            }           
        }

        foreach($dados as $key => $dado){
            $dados[$key]['diferenca_1_saldo_estoque'] = ($dado['contagem_1_estoque'] - $dado['saldo_estoque']);
            $dados[$key]['diferenca_2_saldo_estoque'] = ($dado['contagem_2_estoque'] - $dado['saldo_estoque']);
            $dados[$key]['diferenca_3_saldo_estoque'] = ($dado['contagem_3_estoque'] - $dado['saldo_estoque']);

            $dados[$key]['diferenca_1_estoque'] = ($dado['contagem_1_estoque'] - $dado['saldo']);
            $dados[$key]['diferenca_1_volume'] = ($dado['contagem_1_volume'] - $dado['volume']);
            
            $dados[$key]['diferenca_2_estoque'] = ($dado['contagem_2_estoque'] - $dado['saldo']);
            $dados[$key]['diferenca_2_volume'] = ($dado['contagem_2_volume'] - $dado['volume']);

            $dados[$key]['diferenca_3_estoque'] = ($dado['contagem_3_estoque'] - $dado['saldo']);
            $dados[$key]['diferenca_3_volume'] = ($dado['contagem_3_volume'] - $dado['volume']);

            $dados[$key]['saldo'] = ($dado['saldo']);
            $dados[$key]['volume'] = ($dado['volume']);

            $dados[$key]['contagem_1_estoque'] = ($dado['contagem_1_estoque']);
            $dados[$key]['contagem_1_volume'] = ($dado['contagem_1_volume']);

            $dados[$key]['contagem_2_estoque'] = ($dado['contagem_2_estoque']);
            $dados[$key]['contagem_2_volume'] = ($dado['contagem_2_volume']);

            $dados[$key]['contagem_3_estoque'] = ($dado['contagem_3_estoque']);
            $dados[$key]['contagem_3_volume'] = ($dado['contagem_3_volume']);
        }

        foreach($dados as $key => $dado){
            $dados[$key]['saldo_estoque']  = empty($dados[$key]['saldo_estoque'])? 'ZERO' : $dados[$key]['saldo_estoque'];
            $dados[$key]['saldo']  = empty($dados[$key]['saldo'])? 'ZERO' : $dados[$key]['saldo'];
            $dados[$key]['volume']  = empty($dados[$key]['volume'])? 'ZERO' : $dados[$key]['volume'];
            $dados[$key]['contagem_1_estoque']  = empty($dados[$key]['contagem_1_estoque'])? 'ZERO' : $dados[$key]['contagem_1_estoque'];
            $dados[$key]['contagem_2_estoque']  = empty($dados[$key]['contagem_2_estoque'])? 'ZERO' : $dados[$key]['contagem_2_estoque'];
            $dados[$key]['contagem_3_estoque']  = empty($dados[$key]['contagem_3_estoque'])? 'ZERO' : $dados[$key]['contagem_3_estoque'];
            $dados[$key]['diferenca_1_saldo_estoque']  = empty($dados[$key]['diferenca_1_saldo_estoque'])? 'ZERO' : $dados[$key]['diferenca_1_saldo_estoque'];
            $dados[$key]['diferenca_2_saldo_estoque']  = empty($dados[$key]['diferenca_2_saldo_estoque'])? 'ZERO' : $dados[$key]['diferenca_2_saldo_estoque'];
            $dados[$key]['diferenca_3_saldo_estoque']  = empty($dados[$key]['diferenca_3_saldo_estoque'])? 'ZERO' : $dados[$key]['diferenca_3_saldo_estoque'];
            $dados[$key]['diferenca_1_estoque']  = empty($dados[$key]['diferenca_1_estoque'])? 'ZERO' : $dados[$key]['diferenca_1_estoque'];
            $dados[$key]['diferenca_2_estoque']  = empty($dados[$key]['diferenca_2_estoque'])? 'ZERO' : $dados[$key]['diferenca_2_estoque'];
            $dados[$key]['diferenca_3_estoque']  = empty($dados[$key]['diferenca_3_estoque'])? 'ZERO' : $dados[$key]['diferenca_3_estoque'];
            $dados[$key]['contagem_1_volume']  = empty($dados[$key]['contagem_1_volume'])? 'ZERO' : $dados[$key]['contagem_1_volume'];
            $dados[$key]['contagem_2_volume']  = empty($dados[$key]['contagem_2_volume'])? 'ZERO' : $dados[$key]['contagem_2_volume'];
            $dados[$key]['contagem_3_volume']  = empty($dados[$key]['contagem_3_volume'])? 'ZERO' : $dados[$key]['contagem_3_volume'];
            $dados[$key]['diferenca_1_volume']  = empty($dados[$key]['diferenca_1_volume'])? 'ZERO' : $dados[$key]['diferenca_1_volume'];
            $dados[$key]['diferenca_2_volume']  = empty($dados[$key]['diferenca_2_volume'])? 'ZERO' : $dados[$key]['diferenca_2_volume'];
            $dados[$key]['diferenca_3_volume']  = empty($dados[$key]['diferenca_3_volume'])? 'ZERO' : $dados[$key]['diferenca_3_volume'];
        }

        return $dados;
    } 

    public function retornoLog(Request $request){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');
        
        $campos = $request->only('id');

        $InventarioCodigoLeituraLogObj = InventarioCodigoLeituraLog::select()->with(['criadoPor'])->where('inventario_codigos_id', $campos['id'])->get();

        $dados = [];

        foreach($InventarioCodigoLeituraLogObj as $log){
            $dados[] = [
                'fracao_codigo' => $log->fracao_codigo,
                'contagem' => $log->contagem,
                'endereco' => $log->endereco,
                'usuario' => $log->criadoPor->name,
                'criacao' => parserDataHoraSegundo($log->created_at)
            ];
        }

        return $dados;
    }

    public function retornoPecaNaoEncontrada(Request $request){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');
        
        $campos = $request->only('id');

        $InventarioCodigoLeituraLogObj = InventarioCodigoLeituraLog::select()->with(['criadoPor'])->where('importado', false)->where('inventario_codigos_id', $campos['id'])->get();
        
        $dados = [];

        foreach($InventarioCodigoLeituraLogObj as $log){
            $dados[] = [
                'fracao_codigo' => $log->fracao_codigo,
                'contagem' => $log->contagem,
                'endereco' => $log->endereco,
                'usuario' => $log->criadoPor->name,
                'criacao' => parserDataHoraSegundo($log->created_at)
            ];
        }

        return $dados;
    }

    public function exportarExcel(Request $request){
        set_time_limit(0);
        ini_set('memory_limit','16384M');

        $dados = $this->retornoPecas($request);

        $array_chunk = array_chunk($dados, $this->linhas);
        $tamanho = count($array_chunk) - 1;

        $caminhos = [];
        
        foreach($array_chunk as $key => $chunk){
            $collection = $chunk;
            if($tamanho == $key){
                Excel::store(new InventarioExport($collection), 'Peças_final.xlsx');
                $caminhos[] = 'Peças_final.xlsx';
            }else{
                Excel::store(new InventarioExport($collection), 'Peças_'.$key.'.xlsx');
                $caminhos[] = 'Peças_'.$key.'.xlsx';
            }   
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => ['caminhos' => $caminhos]
        ];

        return response()->json($response);
    }

    public function exportarProdutoExcel(Request $request){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');

        $dados = $this->retornoProduto($request);

        $array_chunk = array_chunk($dados, $this->linhas);
        $tamanho = count($array_chunk) - 1;

        $caminhos = [];
        
        foreach($array_chunk as $key => $chunk){
            $collection = $chunk;
            if($tamanho == $key){
                Excel::store(new InventarioProdutoExport($collection), 'Contagem_final.xlsx');
                $caminhos[] = 'Contagem_final.xlsx';
            }else{
                Excel::store(new InventarioProdutoExport($collection), 'Contagem_'.$key.'.xlsx');
                $caminhos[] = 'Contagem_'.$key.'.xlsx';
            }   
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => ['caminhos' => $caminhos]
        ];

        return response()->json($response);

    }

    public function exportarLogExcel(Request $request){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');

        $dados = $this->retornoLog($request);

        $array_chunk = array_chunk($dados, $this->linhas);
        $tamanho = count($array_chunk) - 1;

        $caminhos = [];
        
        foreach($array_chunk as $key => $chunk){
            $collection = $chunk;
            if($tamanho == $key){
                Excel::store(new InventarioLogExport($collection), 'Logs_final.xlsx');
                $caminhos[] = 'Logs_final.xlsx';
            }else{
                Excel::store(new InventarioLogExport($collection), 'Logs_'.$key.'.xlsx');
                $caminhos[] = 'Logs_'.$key.'.xlsx';
            }   
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => ['caminhos' => $caminhos]
        ];

        return response()->json($response);

        return Excel::download(new InventarioLogExport($request), 'Logs.xlsx');
    }

    public function exportarPecasNaoEncontradaExcel(Request $request){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');

        $dados = $this->retornoPecaNaoEncontrada($request);

        $array_chunk = array_chunk($dados, $this->linhas);
        $tamanho = count($array_chunk) - 1;

        $caminhos = [];
        
        foreach($array_chunk as $key => $chunk){
            $collection = $chunk;
            if($tamanho == $key){
                Excel::store(new InventarioPecaNaoEncontradaExport($collection), 'pecas_nao_encontrada_final.xlsx');
                $caminhos[] = 'pecas_nao_encontrada_final.xlsx';
            }else{
                Excel::store(new InventarioPecaNaoEncontradaExport($collection), 'pecas_nao_encontrada_'.$key.'.xlsx');
                $caminhos[] = 'pecas_nao_encontrada_'.$key.'.xlsx';
            }   
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => ['caminhos' => $caminhos]
        ];

        return response()->json($response);
        //return Excel::download(new InventarioPecaNaoEncontradaExport($request), 'Peças Não Encontrada.xlsx');
    }

    public function download(Request $request){
        $campos = $request->only('caminho');
        
        $arquivo = Storage::exists($campos['caminho']) ? Storage::path($campos['caminho']) : ''; 

        return response()->download($arquivo);
    }

    public function carregarEstoqueProduto(){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');
        $InventarioCodigoEstoqueAtualObj = InventarioCodigoEstoqueAtual::select('produto_codigo', 'estabelecimento_posse')->where('estoque_saldo', 0)->distinct()->get();

        foreach($InventarioCodigoEstoqueAtualObj as $inventario){
            $ProdutosEstoqueObj = ProdutosEstoque::select()
                ->where('codigo_produto', $inventario->produto_codigo)
                ->where('estabelecimento', $inventario->estabelecimento_posse)
                ->first();
            
            $query = InventarioCodigoEstoqueAtual::select();
            $query->where('produto_codigo', $inventario->produto_codigo);
            $query->where('estabelecimento_posse', $inventario->estabelecimento_posse);
            $query->update(['estoque_saldo' => empty($ProdutosEstoqueObj)? 0 : $ProdutosEstoqueObj->estoque]);
        }
    }

    public function removerProdutoCodigoBarra(){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');
        $ProdutoNasajonObj = ProdutoNasajon::select()->whereNotNull('codigodebarras')->where('controlafracao', false)->get();

        $InventarioCodigoEstoqueAtualObj = InventarioCodigoEstoqueAtual::select()
                ->whereIn('fracao_codigo', $ProdutoNasajonObj->pluck('codigodebarras'))->get();

        foreach($InventarioCodigoEstoqueAtualObj as $inventario){
            $inventario->delete();
            $inventario->save();
        }
    }

    public function formatacaoPeca(){
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');
        $InventarioCodigoLeituraObj = InventarioCodigoLeitura::select()
                ->whereNull('codigo_lido')->get();

        foreach($InventarioCodigoLeituraObj  as $inventario){
            $FracoesDisponiveisNasajon = FracoesNasajon::select()->where('codigo', strtoupper($inventario->fracao_codigo))->first();
            
            if(!empty($FracoesDisponiveisNasajon)){
                $inventario->codigo_lido = $inventario->fracao_codigo;
                $inventario->save();
            }else{ 
                $peca_field = [];
                $peca_field[] = strtoupper(substr($inventario->fracao_codigo, 1));
                $peca_field[] = strtoupper(substr($inventario->fracao_codigo, 0, 8));
                $peca_field[] = strtoupper(substr($inventario->fracao_codigo, -8));
                $peca_field[] = strtoupper(substr($inventario->fracao_codigo, 0, 11));
                $peca_field[] = strtoupper(substr($inventario->fracao_codigo, 0, 13));
                $peca_field[] = strtoupper(substr($inventario->fracao_codigo, 0, 13).".".substr($inventario->fracao_codigo, 13, 2));
                $peca_field[] = strtoupper(substr($inventario->fracao_codigo, 0, 13).".".substr($inventario->fracao_codigo, 14, 2));
                $peca_field[] = strtoupper(substr($inventario->fracao_codigo, 0, 13).".".substr($inventario->fracao_codigo, 15, 1));
                if(strpos($inventario->fracao_codigo, '.') !== false){
                    $temp_explode = explode('.', $inventario->fracao_codigo);
                    if(count($temp_explode) === 2){
                        $temp_explode[0] = str_pad($temp_explode[0], 6, '0', STR_PAD_LEFT);
                        $return_explode = $temp_explode[0].'.'.$temp_explode[1];
                        $peca_field[] = strtoupper($return_explode);
                    }
                    unset($temp_explode);
                }

                $FracoesDisponiveisNasajon = FracoesNasajon::select()->whereIn('codigo', $peca_field)->first();
                if(!empty($FracoesDisponiveisNasajon)){
                    $inventario->codigo_lido = $inventario->fracao_codigo;
                    $inventario->fracao_codigo = $FracoesDisponiveisNasajon->codigo;
                    $inventario->save();
                }

                
            }
        }
    }
}


