<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\FornecedorNasajon;
use App\ImportacaoFornecedorCreditoDebito;
use App\Importacao;
use App\ImportacaoValorPadrao;

use Illuminate\Support\Facades\DB;

use App\Http\Requests\ImportacaoFornecedorCreditoDebitoAdicionarEditarRequest;

class ImportacaoFornecedorCreditoDebitoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ImportacaoFornecedorCreditoDebito") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ImportacaoFornecedorCreditoDebito');

        return view('programs.importacao_fornecedor_credito_debito.index');
    }

    public function modalAdicionar() {
        return view('programs.importacao_fornecedor_credito_debito.modal.adicionar');
    }

    public function adicionar(ImportacaoFornecedorCreditoDebitoAdicionarEditarRequest $request){
        $fields = $request->only('fornecedor','tipo', 'valor');

        $fornecedor = FornecedorNasajon::select();
        $fornecedor->where(DB::raw('TRIM(CONCAT(nome,\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor']));
        $fornecedor = $fornecedor->first();

        $importacaoFornecedorCreditoDebitoObj = new ImportacaoFornecedorCreditoDebito;
        $importacaoFornecedorCreditoDebitoObj->fornecedor_codigo  = $fornecedor->codigo;
        
        if($fields['tipo'] == 'credito'){
            $importacaoFornecedorCreditoDebitoObj->credito = parserNumber($fields['valor']);
            $importacaoFornecedorCreditoDebitoObj->debito = 0;
        }else{
            $importacaoFornecedorCreditoDebitoObj->credito = 0;
            $importacaoFornecedorCreditoDebitoObj->debito = parserNumber($fields['valor']);
        }
        
        $importacaoFornecedorCreditoDebitoObj->created_by = Auth::id();
        $importacaoFornecedorCreditoDebitoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function modalEditar(Request $request) {
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

        $importacaoFornecedorCreditoDebitoObj = ImportacaoFornecedorCreditoDebito::find($id);
        
        $saldo = '';
        $tipo = 'credito';
        if(($importacaoFornecedorCreditoDebitoObj->credito -  $importacaoFornecedorCreditoDebitoObj->debito) > 0 ){
            $saldo = parserValor($importacaoFornecedorCreditoDebitoObj->credito -  $importacaoFornecedorCreditoDebitoObj->debito);
        }else if(($importacaoFornecedorCreditoDebitoObj->credito -  $importacaoFornecedorCreditoDebitoObj->debito) < 0){
            $saldo = parserValor((-1) * ($importacaoFornecedorCreditoDebitoObj->credito -  $importacaoFornecedorCreditoDebitoObj->debito));
            $tipo = 'debito';
        }

        $dados = [
            'id' => encrypt($importacaoFornecedorCreditoDebitoObj->id),
            'fornecedor' => $importacaoFornecedorCreditoDebitoObj->fornecedorDetalhes->nome.' - '.$importacaoFornecedorCreditoDebitoObj->fornecedorDetalhes->cnpj_cpf,
            'saldo' => $saldo,
            'tipo' => $tipo,
        ];

        return view('programs.importacao_fornecedor_credito_debito.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(ImportacaoFornecedorCreditoDebitoAdicionarEditarRequest $request){
        $fields = $request->only('id', 'fornecedor','tipo', 'valor');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $fornecedor = FornecedorNasajon::select();
        $fornecedor->where(DB::raw('TRIM(CONCAT(nome,\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor']));
        $fornecedor = $fornecedor->first();

        $importacaoFornecedorCreditoDebitoObj = ImportacaoFornecedorCreditoDebito::find($id);
        $importacaoFornecedorCreditoDebitoObj->fornecedor_codigo  = $fornecedor->codigo;
        
        if($fields['tipo'] == 'credito'){
            $importacaoFornecedorCreditoDebitoObj->credito = parserNumber($fields['valor']);
            $importacaoFornecedorCreditoDebitoObj->debito = 0;
        }else{
            $importacaoFornecedorCreditoDebitoObj->credito = 0;
            $importacaoFornecedorCreditoDebitoObj->debito = parserNumber($fields['valor']);
        }
        
        $importacaoFornecedorCreditoDebitoObj->updated_by = Auth::id();
        $importacaoFornecedorCreditoDebitoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function modalDeletar(Request $request) {
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

        $importacaoFornecedorCreditoDebitoObj = ImportacaoFornecedorCreditoDebito::find($id);
        
        if(($importacaoFornecedorCreditoDebitoObj->credito -  $importacaoFornecedorCreditoDebitoObj->debito) > 0 ){
            $saldo = parserValor($importacaoFornecedorCreditoDebitoObj->credito -  $importacaoFornecedorCreditoDebitoObj->debito)." CR";
        }else if(($importacaoFornecedorCreditoDebitoObj->credito -  $importacaoFornecedorCreditoDebitoObj->debito) < 0){
            $saldo = parserValor($importacaoFornecedorCreditoDebitoObj->credito -  $importacaoFornecedorCreditoDebitoObj->debito)." DB";
        }

        $dados = [
            'id' => encrypt($importacaoFornecedorCreditoDebitoObj->id),
            'fornecedor' => $importacaoFornecedorCreditoDebitoObj->fornecedorDetalhes->nome.' - '.$importacaoFornecedorCreditoDebitoObj->fornecedorDetalhes->cnpj_cpf,
            'saldo' => $saldo,
        ];

        return view('programs.importacao_fornecedor_credito_debito.modal.deletar')->with(['dados' => $dados]);
    }

    public function deletar(Request $request){
        $id = $request->only('id')['id'];

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

        $importacaoFornecedorCreditoDebitoObj = ImportacaoFornecedorCreditoDebito::find($id);
        $importacaoFornecedorCreditoDebitoObj->deleted_by = Auth::id();
        $importacaoFornecedorCreditoDebitoObj->delete();
        $importacaoFornecedorCreditoDebitoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtro(Request $request){
        $fields = $request->only('fornecedor_filtro');

        $retorno = [];

        $query = ImportacaoFornecedorCreditoDebito::select();

        if(!empty($fields['fornecedor_filtro'])){
            $query->with(['fornecedorDetalhes' => function($query) use($fields){
                $query->where(DB::raw('TRIM(CONCAT(nome,\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor_filtro']));
            }]);
        }

        $result = $query->get();

        foreach($result as $fornecedor){
            if(!empty($fornecedor->fornecedorDetalhes)){
                $saldo = '';
                if(($fornecedor->credito -  $fornecedor->debito) > 0 ){
                   $saldo = parserValor($fornecedor->credito -  $fornecedor->debito)." CR";
                }else if(($fornecedor->credito -  $fornecedor->debito) < 0){
                    $saldo = parserValor($fornecedor->credito -  $fornecedor->debito)." DB";
                }
    
                $retorno [] = [
                    'id' => encrypt($fornecedor->id),
                    'fornecedor' => $fornecedor->fornecedorDetalhes->nome.' - '.$fornecedor->fornecedorDetalhes->cnpj_cpf,
                    'saldo' => $saldo,
                ];
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function indexConsulta(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ImportacaoConsultaFornecedorCreditoDebito") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ImportacaoConsultaFornecedorCreditoDebito');

        $status = $this->statusProcessos();

        return view('programs.importacao_fornecedor_credito_debito.consulta.index')->with(['status' => $status]);
    }

    public function filtroConsulta(Request $request){
        $fields = $request->only('fornecedor_filtro', 'data_inicio_entrega', 'data_fim_entrega', 'codigo_produto', 'descricao_produto', 'pcmn', 'proforma', 'status', 'atrasado');
        $retorno = [];
        
        $query = ImportacaoFornecedorCreditoDebito::select();
        if(!empty($fields['fornecedor_filtro'])){
            $query->with(['fornecedorDetalhes' => function($query) use($fields){
                $query->where(DB::raw('TRIM(CONCAT(nome,\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor_filtro']));
            }]);
        }
        $result = $query->get();

        //Valor inicial
        foreach($result as $importacao){
            if(!empty($importacao->fornecedorDetalhes)){
                $retorno[$importacao->fornecedor_codigo] = [
                    'fornecedor_codigo' => encrypt($importacao->fornecedor_codigo),
                    'fornecedor' => $importacao->fornecedorDetalhes->nome.' - '.$importacao->fornecedorDetalhes->cnpj_cpf,
                    'custo_realizado' => 0,
                    'custo_previsto' => 0,
                    'saldo' => $importacao->credito - $importacao->debito,
                    'em_producao_inteiro' => 0,
                    'carga_pronta_inteiro' => 0,
                    'embarque_etd_inteiro' => 0,
                    'chegada_porto_eta_inteiro' => 0,
                    'di_inteiro' => 0,
                ];
            }
        }

        $query = Importacao::select();
        $query->with(['fornecedor' => function($query) use($fields){
            if(!empty($fields['fornecedor_filtro'])){
                $query->where(DB::raw('TRIM(CONCAT(nome,\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor_filtro']));
            }
        },
        'pedidoComprasDetalhes' => function($query) use($fields){        
            if(!empty($fields['pcmn'])){
                $query->where('numero_pedido', $fields['pcmn']);
            }

            if(!empty($fields['proforma'])){
                $query->where('proforma', 'ilike', '%'.$fields['proforma'].'%');
            }
            
            if(!empty($fields['codigo_produto'])){
                $query->where('cod_produto', 'ilike', '%'.$fields['codigo_produto'].'%');
            }

            if(!empty($fields['descricao_produto'])){
                $query->where('descricao_produto', 'ilike', '%'.$fields['descricao_produto'].'%');
            }
        }]);
        
        if($fields['status'] == 'di'){
            $query->whereNotNull('data_carga_pronta_realizado');
            $query->whereNotNull('data_embarque_realizado');
            $query->whereNotNull('data_chegada_porto_realizado');
            $query->whereNotNull('data_di_realizado');

            if(!empty($fields['data_inicio_entrega'])){
                $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_inicio_entrega']);;
                $query->where('data_di_realizado', '>=', $data_inicial);
            }
    
            if(!empty($fields['data_fim_entrega'])){
                $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_fim_entrega']);;
                $query->where('data_di_realizado', '<=', $data_final);
            }
        }else if($fields['status'] == 'chegada_porto_eta'){
            $query->whereNotNull('data_carga_pronta_realizado');
            $query->whereNotNull('data_embarque_realizado');
            $query->whereNotNull('data_chegada_porto_realizado');
            $query->whereNull('data_di_realizado');

            if(!empty($fields['data_inicio_entrega'])){
                $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_inicio_entrega']);;
                $query->where('data_chegada_porto_realizado', '>=', $data_inicial);
            }
    
            if(!empty($fields['data_fim_entrega'])){
                $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_fim_entrega']);;
                $query->where('data_chegada_porto_realizado', '<=', $data_final);
            }
        }else if($fields['status'] == 'embarque_etd'){
            $query->whereNotNull('data_carga_pronta_realizado');
            $query->whereNotNull('data_embarque_realizado');
            $query->whereNull('data_chegada_porto_realizado');
            $query->whereNull('data_di_realizado');

            if(!empty($fields['data_inicio_entrega'])){
                $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_inicio_entrega']);;
                $query->where('data_embarque_realizado', '>=', $data_inicial);
            }
    
            if(!empty($fields['data_fim_entrega'])){
                $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_fim_entrega']);;
                $query->where('data_embarque_realizado', '<=', $data_final);
            }
        }else if($fields['status'] == 'carga_pronta'){
            $query->whereNotNull('data_carga_pronta_realizado');
            $query->whereNull('data_embarque_realizado');
            $query->whereNull('data_chegada_porto_realizado');
            $query->whereNull('data_di_realizado');

            if(!empty($fields['data_inicio_entrega'])){
                $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_inicio_entrega']);;
                $query->where('data_carga_pronta_realizado', '>=', $data_inicial);
            }
    
            if(!empty($fields['data_fim_entrega'])){
                $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_fim_entrega']);;
                $query->where('data_carga_pronta_realizado', '<=', $data_final);
            }
        }else if($fields['status'] == 'em_producao'){
            $query->whereNull('data_carga_pronta_realizado');
            $query->whereNull('data_embarque_realizado');
            $query->whereNull('data_chegada_porto_realizado');
            $query->whereNull('data_di_realizado');

            if(!empty($fields['data_inicio_entrega'])){
                $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_inicio_entrega']);;
                $query->where('data_proforma', '>=', $data_inicial);
            }
    
            if(!empty($fields['data_fim_entrega'])){
                $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_fim_entrega']);;
                $query->where('data_proforma', '<=', $data_final);
            }
        }else{
            if(!empty($fields['data_inicio_entrega'])){
                $data_inicial = Carbon::CreateFromFormat("d/m/Y", $fields['data_inicio_entrega']);;
                $query->where('data_proforma', '>=', $data_inicial);
            }
    
            if(!empty($fields['data_fim_entrega'])){
                $data_final = Carbon::CreateFromFormat("d/m/Y", $fields['data_fim_entrega']);;
                $query->where('data_proforma', '<=', $data_final);
            }
        }

        if(isset($fields['atrasado']) && !empty($fields['atrasado'])){
            $data_atual = Carbon::now()->setTime(0,0,0);
            $query->where(function($query) use($data_atual){
                $query->orWhere(function($query) use($data_atual){
                    $query->where('data_carga_pronta_previsao', '<', $data_atual);
                    $query->whereNull('data_carga_pronta_realizado');
                });
                $query->orWhere(function($query) use($data_atual){
                    $query->where('data_embarque_previsao', '<', $data_atual);
                    $query->whereNull('data_embarque_realizado');
                });
                $query->orWhere(function($query) use($data_atual){
                    $query->where('data_chegada_porto_previsao', '<', $data_atual);
                    $query->whereNull('data_chegada_porto_realizado');
                });
                $query->orWhere(function($query) use($data_atual){
                    $query->where('data_di_previsao', '<', $data_atual);
                    $query->whereNull('data_di_realizado');
                });

                $query->orWhere(function($query) use($data_atual){
                    $query->whereColumn('data_carga_pronta_previsao', '>', 'data_carga_pronta_realizado');
                });
                $query->orWhere(function($query) use($data_atual){
                    $query->whereColumn('data_embarque_previsao', '>', 'data_embarque_realizado');
                });
                $query->orWhere(function($query) use($data_atual){
                    $query->whereColumn('data_chegada_porto_previsao', '>', 'data_chegada_porto_realizado');
                });
                $query->orWhere(function($query) use($data_atual){
                    $query->whereColumn('data_di_previsao', '>', 'data_di_realizado');
                });
            });
        }
        $result = $query->get();

        $importacaoValorPadraoObj = ImportacaoValorPadrao::select()->first();
        
        foreach($result as $importacao){
            if(!empty($importacao->fornecedor) && !empty($importacao->pedidoComprasDetalhes)){
                if(empty($retorno[$importacao->fornecedor_codigo])){
                    $retorno[$importacao->fornecedor_codigo] = [
                        'fornecedor_codigo' => encrypt($importacao->fornecedor_codigo),
                        'fornecedor' => $importacao->fornecedor->nome.' - '.$importacao->fornecedor->cnpj_cpf,
                        'em_producao_inteiro' => empty($importacao->data_carga_pronta_realizado) && empty($importacao->data_embarque_realizado) && empty($importacao->data_chegada_porto_realizado) && empty($importacao->data_di_realizado)? 1 : 0,
                        'carga_pronta_inteiro' => !empty($importacao->data_carga_pronta_realizado) && empty($importacao->data_embarque_realizado) && empty($importacao->data_chegada_porto_realizado) && empty($importacao->data_di_realizado)? 1 : 0,
                        'embarque_etd_inteiro' => !empty($importacao->data_carga_pronta_realizado) && !empty($importacao->data_embarque_realizado) && empty($importacao->data_chegada_porto_realizado) && empty($importacao->data_di_realizado)? 1 : 0,
                        'chegada_porto_eta_inteiro' => !empty($importacao->data_carga_pronta_realizado) && !empty($importacao->data_embarque_realizado) && !empty($importacao->data_chegada_porto_realizado) && empty($importacao->data_di_realizado)? 1 : 0,
                        'di_inteiro' => !empty($importacao->data_carga_pronta_realizado) && !empty($importacao->data_embarque_realizado) && !empty($importacao->data_chegada_porto_realizado) && !empty($importacao->data_di_realizado)? 1 : 0,
                        'atrasado' => isset($fields['atrasado']) && !empty($fields['atrasado'])? true : '',
                    ];
                }else{
                    if(empty($importacao->data_carga_pronta_realizado) && empty($importacao->data_embarque_realizado) && empty($importacao->data_chegada_porto_realizado) && empty($importacao->data_di_realizado)){
                        $retorno[$importacao->fornecedor_codigo]['em_producao_inteiro'] += 1;
                    }else if(!empty($importacao->data_carga_pronta_realizado) && empty($importacao->data_embarque_realizado) && empty($importacao->data_chegada_porto_realizado) && empty($importacao->data_di_realizado)){
                        $retorno[$importacao->fornecedor_codigo]['carga_pronta_inteiro'] += 1;
                    }else if(!empty($importacao->data_carga_pronta_realizado) && !empty($importacao->data_embarque_realizado) && empty($importacao->data_chegada_porto_realizado) && empty($importacao->data_di_realizado)){
                        $retorno[$importacao->fornecedor_codigo]['embarque_etd_inteiro'] += 1;
                    }else if(!empty($importacao->data_carga_pronta_realizado) && !empty($importacao->data_embarque_realizado) && !empty($importacao->data_chegada_porto_realizado) && empty($importacao->data_di_realizado)){
                        $retorno[$importacao->fornecedor_codigo]['chegada_porto_eta_inteiro'] += 1;
                    }else{
                        $retorno[$importacao->fornecedor_codigo]['di_inteiro'] += 1;
                    }
                }
            }
        }

        $retorno = $this->ajusteArrayParaValores($retorno);

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    private function ajusteArrayParaValores($array){
        if(is_array($array)){
            foreach($array as $key => $value){
                if(is_array($value)){
                    $array[$key] = $this->ajusteArrayParaValores($value);
                }else{
                    if(is_numeric($value)){
                        if(substr_count($key, "codigo") === 0){
                            if(substr_count($key, "porcetagem") === 0){
                                if(substr_count($key, "inteiro") === 0){
                                    $array[$key] = empty($value)? '': parserValor($value);
                                }else{
                                    $array[$key] = empty($value)? '': $value;
                                }
                            }else{
                                $array[$key] = empty($value)? '': parserValor($value).'%';
                            }                            
                        }
                    }else{
                        $array[$key] = $value;
                    }
                }
            }
        }
        return $array;
    }

    private function statusProcessos(){
        $status = [
            'em_producao' => 'Em Produção',
            'carga_pronta' => 'Carga Pronta',
            'embarque_etd' => 'Embarque',
            'chegada_porto_eta' => 'Chegada no Porto',
            'di' => 'DI',
        ];
        
        return $status;
    }

    public function modalImportacaoPorStatus(Request $request){
        $fields = $request->only('codigo_fornecedor', 'status', 'atrasado');

        $query = Importacao::select();
        $query->where('fornecedor_codigo', decrypt($fields['codigo_fornecedor']));
        if($fields['status'] == 'di'){
            $query->whereNotNull('data_carga_pronta_realizado');
            $query->whereNotNull('data_embarque_realizado');
            $query->whereNotNull('data_chegada_porto_realizado');
            $query->whereNotNull('data_di_realizado');
        }else if($fields['status'] == 'chegada_porto_eta'){
            $query->whereNotNull('data_carga_pronta_realizado');
            $query->whereNotNull('data_embarque_realizado');
            $query->whereNotNull('data_chegada_porto_realizado');
            $query->whereNull('data_di_realizado');
        }else if($fields['status'] == 'embarque_etd'){
            $query->whereNotNull('data_carga_pronta_realizado');
            $query->whereNotNull('data_embarque_realizado');
            $query->whereNull('data_chegada_porto_realizado');
            $query->whereNull('data_di_realizado');
        }else if($fields['status'] == 'carga_pronta'){
            $query->whereNotNull('data_carga_pronta_realizado');
            $query->whereNull('data_embarque_realizado');
            $query->whereNull('data_chegada_porto_realizado');
            $query->whereNull('data_di_realizado');
        }else if($fields['status'] == 'em_producao'){
            $query->whereNull('data_carga_pronta_realizado');
            $query->whereNull('data_embarque_realizado');
            $query->whereNull('data_chegada_porto_realizado');
            $query->whereNull('data_di_realizado');
        }
        if(boolval($fields['atrasado'])){
            $data_atual = Carbon::now()->setTime(0,0,0);
            $query->where(function($query) use($data_atual){
                $query->orWhere(function($query) use($data_atual){
                    $query->where('data_carga_pronta_previsao', '<', $data_atual);
                    $query->whereNull('data_carga_pronta_realizado');
                });
                $query->orWhere(function($query) use($data_atual){
                    $query->where('data_embarque_previsao', '<', $data_atual);
                    $query->whereNull('data_embarque_realizado');
                });
                $query->orWhere(function($query) use($data_atual){
                    $query->where('data_chegada_porto_previsao', '<', $data_atual);
                    $query->whereNull('data_chegada_porto_realizado');
                });
                $query->orWhere(function($query) use($data_atual){
                    $query->where('data_di_previsao', '<', $data_atual);
                    $query->whereNull('data_di_realizado');
                });

                $query->orWhere(function($query) use($data_atual){
                    $query->whereColumn('data_carga_pronta_previsao', '>', 'data_carga_pronta_realizado');
                });
                $query->orWhere(function($query) use($data_atual){
                    $query->whereColumn('data_embarque_previsao', '>', 'data_embarque_realizado');
                });
                $query->orWhere(function($query) use($data_atual){
                    $query->whereColumn('data_chegada_porto_previsao', '>', 'data_chegada_porto_realizado');
                });
                $query->orWhere(function($query) use($data_atual){
                    $query->whereColumn('data_di_previsao', '>', 'data_di_realizado');
                });
            });
        }
        $result = $query->get();
        
        $linhas = [];
        $total = [
            'custo_previsto' => 0,
            'custo_realizado' => 0,
        ];
        $importacaoValorPadraoObj = ImportacaoValorPadrao::select()->first();
        foreach($result as $importacao){
            $total_valor_fob = 0;
            $total_valor_contabil = 0;
            foreach($importacao->pedidoComprasItens as $item){
                $total_valor_fob  += $item->quantidade * $item->preco_compra_unitario;
                $total_valor_contabil += empty($item->produtoDetalhesImportacao->valor_contabil_unitario)? 0 : $item->produtoDetalhesImportacao->valor_contabil_unitario*$item->quantidade;
            }

            $outras_despesas_total = 0;
            foreach($importacao->custosOutraDespesa as $custo_outra_depesa){
                $outras_despesas[] = [
                    'id' => encrypt($custo_outra_depesa->id),
                    'descricao' => $custo_outra_depesa->descricao,
                    'valor' => parserValor($custo_outra_depesa->valor),
                ];

                $outras_despesas_total += $custo_outra_depesa->valor;
            }

            $total_cambio_previsto = empty($importacaoValorPadraoObj->dolar_referencia)? 0 : $total_valor_fob * $importacaoValorPadraoObj->dolar_referencia;
            $ii_previsto = $total_cambio_previsto * 26 / 100;
            $pis_previsto = empty($importacao->valorPadrao)? $total_cambio_previsto * $importacaoValorPadraoObj->pis / 100 : $total_cambio_previsto * $importacao->valorPadrao->pis / 100;
            $cofins_previsto =  empty($importacao->valorPadrao)? $total_cambio_previsto * $importacaoValorPadraoObj->cofins / 100 : $total_cambio_previsto * $importacao->valorPadrao->cofins / 100;

            $custo_realizado = $importacao->custos->ii + $importacao->custos->ipi + $importacao->custos->pis + $importacao->custos->cofins + $importacao->custos->afrmm + $importacao->custos->taxa_siscomex + $importacao->custos->sda + $importacao->custos->honorarios + $importacao->custos->expediente + $importacao->custos->valor_li + $importacao->custos->agencia_maritima + $importacao->custos->armazem + $importacao->custos->laudo + $importacao->custos->outras_despesas + $importacao->custos->icms_saida + $importacao->custos->seguro + $importacao->custos->transporte_rodoviario + $outras_despesas_total;
            $custo_previsto = empty($importacao->valorPadraoTotal)? $importacaoValorPadraoObj->total + $total_cambio_previsto + $pis_previsto+$cofins_previsto : $importacao->valorPadraoTotal->total + $total_cambio_previsto + $pis_previsto+$cofins_previsto;
            
            $linhas[] = [
                'id' => encrypt($importacao->id),
                "id_pedido" => encrypt($importacao->pedidoCompras->id_nota),
                'proforma' => $importacao->numero_proforma,
                'pcmn' => $importacao->pedido_compras,
                'referencia' => $importacao->referencia,
                'data' => parserData($importacao->data_proforma),
                'custo_realizado' => empty($custo_realizado)? '' : parserValor($custo_realizado),
                'custo_previsto' => empty($custo_previsto)? '' : parserValor($custo_previsto),
            ];
            $total['custo_previsto'] += $custo_previsto;
            $total['custo_realizado'] += $custo_realizado;
        }

        $total['custo_previsto'] = empty($total['custo_previsto'])? '' : parserValor($total['custo_previsto']);
        $total['custo_realizado'] = empty($total['custo_realizado'])? '' : parserValor($total['custo_realizado']);

        return view("programs.importacao_fornecedor_credito_debito.consulta.modal.detalhes_importacao_por_status")->with(['linhas' => $linhas, 'total' => $total]);
    }
}
