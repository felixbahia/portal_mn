<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\OrcamentoCompra;
use App\FornecedorNasajon;
use App\CondicoesPagamentoWeb;

use Illuminate\Support\Facades\DB;

use App\Http\Requests\OrcamentoCompraAdicionarEditarRequest;

class OrcamentoCompraController extends Controller
{
    private $modos = [
        'competencia' => 'Competência',
        'fluxo_caixa' => 'Fluxo de Caixa'
    ];
    private $tipos = [
        'nacional' => 'Nacional', 
        'importado' => 'Importado', 
        'uso_consumo' => 'Uso e Consumo',
        'banco' => 'Banco',
        'despesas' => 'Despesas',
    ];
    private $tipos_compras = [
        'nacional' => 'Nacional', 
        'importado' => 'Importado', 
        'uso_consumo' => 'Uso e Consumo',
    ];
    private $tipos_compras_verificacao = [
        'nacional', 
        'importado', 
        'uso_consumo',
        'banco',
        'despesas'
    ];
    private $tipos_outros = [
        'banco' => 'Banco',
        'despesas' => 'Despesas',
    ];

    private $tipos_compras_indice = [
        'nacional' => 'nacional', 
        'importado' => 'importado', 
        'uso_consumo' => 'uso_consumo',
    ];
    private $tipos_outros_indice = [
        'banco' => 'banco',
        'despesas' => 'despesas',
    ];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\OrcamentoCompras") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\OrcamentoCompras');

        return view('programs.orcamento_compras.index')->with(['modos' => $this->modos, 'tipos' => $this->tipos_compras]);
    }

    public function indexOutro(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\BancoPrevisto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BancoPrevisto');

        return view('programs.banco_previsto.index')->with(['modos' => $this->modos, 'tipos' => $this->tipos_outros]);
    }


    public function modalAdicionar(Request $request) {
        $fields = $request->only('tipo');

        $fixo_modulo = '';
        $tipo_abertura = $fields['tipo'];
        if($fields['tipo'] == 'compras'){
            $tipos = $this->tipos_compras;
            $fixo_modulo = 'competencia';
        }else{
            $tipos = $this->tipos_outros;
            $fixo_modulo = 'competencia';
        }
        
        return view('programs.orcamento_compras.modal.adicionar')->with(['modos' => $this->modos, 'tipos' => $tipos, 'fixo_modulo' => $fixo_modulo, 'tipo_abertura' => $tipo_abertura]);
    }

    public function adicionar(OrcamentoCompraAdicionarEditarRequest $request){
        $fields = $request->only(
            'modo', 
            'tipo', 
            'mes_ano', 
            'valor', 
            'fornecedor', 
            'condicao_pagamento_descr', 
            'ano_do', 
            'ano_ate',
            'janeiro',
            'fevereiro',
            'marco',
            'abril',
            'maio',
            'junho',
            'julho',
            'agosto',
            'setembro',
            'outubro',
            'novembro',
            'dezembro',
            'dia_fluxo_inicial'
        );

        $ano_inicial = intval($fields['ano_do']);
        $ano_final = intval($fields['ano_ate']);
        $dia_fluxo_inicial = isset($fields['dia_fluxo_inicial'])? $fields['dia_fluxo_inicial'] : 1;
        $data_atual = Carbon::now();

        $meses = $this->mesesVerificacao();
        $datas = [];

        foreach($meses as $mes){
            if(isset($fields[$mes])){
                while($ano_inicial <= $ano_final){
                    if($ano_inicial == $data_atual->year){
                        if(intval($fields[$mes]) > intval($data_atual->format('m'))){
                            $datas[] = '01/'.$fields[$mes].'/'.$ano_inicial;
                        }
                    }else{
                        $datas[] = '01/'.$fields[$mes].'/'.$ano_inicial;
                    }

                    $ano_inicial++;                  
                }
                $ano_inicial = intval($fields['ano_do']);
            }
        }

        if(in_array($fields['tipo'], $this->tipos_compras_verificacao)){
            $condicoesPagamentoWebObj = CondicoesPagamentoWeb::select()
                ->with(['parcelas.parcelas'])
                ->where('descricao', 'ilike', $fields['condicao_pagamento_descr'])
                ->where('nasajon', true)
                ->where('ativo', true)
                ->first();
        }

        $fornecedor = FornecedorNasajon::select();
        $fornecedor->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor']));
        $fornecedor_result = $fornecedor->first();

        if(!empty($fornecedor_result)){
            $fornecedor_codigo = $fornecedor_result->codigo;
        }else{
            $fornecedor_codigo = null;
        }

        foreach($datas as $data){
            $data_carbon = Carbon::createFromFormat('d/m/Y', $data)->setTime(0,0,0);

            $orcamentoCompraObj = new OrcamentoCompra;
            $orcamentoCompraObj->data = $data_carbon;
            $orcamentoCompraObj->modo = !isset($fields['modo'])? 'competencia' : $fields['modo'];  
            $orcamentoCompraObj->tipo = $fields['tipo']; 
            $orcamentoCompraObj->fornecedor_codigo = $fornecedor_codigo;  
            $orcamentoCompraObj->valor = empty($fields['valor'])? 0 : parserNumber($fields['valor']);  
            if(in_array($fields['tipo'], $this->tipos_compras_verificacao)){
                $orcamentoCompraObj->parcela_quantidade = $condicoesPagamentoWebObj->parcelas->quantidadeparcelas;
                $orcamentoCompraObj->parcela_valor = empty($fields['valor'])? 0 : parserNumber($fields['valor'])/$condicoesPagamentoWebObj->parcelas->quantidadeparcelas;
                $orcamentoCompraObj->condicoes_pagamento_web_id = $condicoesPagamentoWebObj->id;
                $orcamentoCompraObj->dia_fluxo_inicial = intval($dia_fluxo_inicial);
            }
            $orcamentoCompraObj->created_by = Auth::id();
            $orcamentoCompraObj->save();

            if(in_array($fields['tipo'], $this->tipos_compras_verificacao)){
                $datas_fluxo_caixa = [];
                $dia_verificacao = $dia_fluxo_inicial;
                if(intval($data_carbon->month) == 2){
                    if(intval($dia_fluxo_inicial) > 28){
                        $dia_verificacao = 28;
                    }
                }
                foreach($condicoesPagamentoWebObj->parcelas->parcelas as $parcela_detalhes){
                    $data_carbon_fluxo_caixa = Carbon::createFromFormat('d/m/Y', $dia_verificacao."/".substr($data, -7))->setTime(0,0,0);
                    $data_carbon_fluxo_caixa = $data_carbon_fluxo_caixa->addDays($parcela_detalhes->quantidadediapagamento);
                    $datas_fluxo_caixa[] = $data_carbon_fluxo_caixa;
                }
    
                foreach($datas_fluxo_caixa as $data_fluxo_caixa){   
                    $feriadoControllerObj = new FeriadoController;
                    $feriado = $feriadoControllerObj->getFeriadosPeriodo($data_fluxo_caixa,$data_fluxo_caixa);
                        
                    $orcamentoCompraFluxoObj = new OrcamentoCompra;
                    $pagamento = null;
                    
                    if(count($feriado) == 1){
                        $orcamentoCompraFluxoObj->data = $data_fluxo_caixa->addDays(1);
                        $pagamento = 'postecipado';
                    }
                    if($data_fluxo_caixa->dayOfWeekIso == 6){
                        $orcamentoCompraFluxoObj->data = $data_fluxo_caixa->addDays(2);
                        $pagamento = 'postecipado';
                    }
                    elseif($data_fluxo_caixa->dayOfWeekIso == 7){
                        $orcamentoCompraFluxoObj->data = $data_fluxo_caixa->addDays(1);
                        $pagamento = 'postecipado';
                    }else{
                        $orcamentoCompraFluxoObj->data = $data_fluxo_caixa;
                    }
                    
                    
                    $orcamentoCompraFluxoObj->modo = 'fluxo_caixa';  
                    $orcamentoCompraFluxoObj->tipo = $fields['tipo']; 
                    $orcamentoCompraFluxoObj->fornecedor_codigo = $fornecedor_codigo;  
                    $orcamentoCompraFluxoObj->valor = empty($fields['valor'])? 0 : parserNumber($fields['valor'])/$condicoesPagamentoWebObj->parcelas->quantidadeparcelas;
                    $orcamentoCompraFluxoObj->created_by = Auth::id();
                    $orcamentoCompraFluxoObj->competencia_orcamento_compras_id = $orcamentoCompraObj->id;
                    $orcamentoCompraFluxoObj->pagamento = $pagamento;
                    $orcamentoCompraFluxoObj->save();
                }
            }            
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtro(Request $request){
        $fields = $request->only('mes_ano', 'tipo', 'modo', 'fornecedor_filtro', 'tipo_todos');
        
        $query = OrcamentoCompra::select();
        $query->with(['detalhesOrigem']);
        if(!empty($fields['mes_ano'])){
            $data_inicial = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0)->firstOfMonth();
            $data_final = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(23,59,59)->lastOfMonth();
            $query->whereBetween('data', [$data_inicial, $data_final]);
        }
        if(!empty($fields['tipo'])){
            $query->where('tipo', $fields['tipo']);
        }else{
            if($fields['tipo_todos'] == 'compras'){
                $query->whereIn('tipo', $this->tipos_compras_indice);
            }else{
                $query->whereIn('tipo', $this->tipos_outros_indice);
            }
        }
        if(!empty($fields['modo'])){
            $query->where('modo', $fields['modo']);
        }
        if(!empty($fields['fornecedor_filtro'])){
            $query->with(['detalhesFornecedor' => function($query) use($fields){
                $query->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor_filtro']));
            }]);
        }else{
            $query->with(['detalhesFornecedor']);
        }
        $result = $query->get();

        $orcamentos = [];

        $total = [
            'valor' => 0,
        ];
        $data_verificacao = Carbon::now()->setTime(0,0,0)->lastOfMonth();

        foreach($result as $orcamento){
            $liberacao_edicao_exclusao = false;

            if(empty($orcamento->detalhesOrigem)){
                if($data_verificacao->lt($orcamento->data)){
                    $liberacao_edicao_exclusao = true;
                }
            }else if($data_verificacao->lt($orcamento->detalhesOrigem->data)){
                $liberacao_edicao_exclusao = true;
            }
            
            if(!empty($fields['fornecedor_filtro'])){
                if(!empty($orcamento->detalhesFornecedor)){
                    $orcamentos[] = [
                        'id' => encrypt($orcamento->id),
                        'mes_ano' => substr(parserData($orcamento->data), -7),
                        'valor' => empty($orcamento->valor)? '' : parserValor($orcamento->valor),
                        'fornecedor' => empty($orcamento->fornecedor_codigo)? '' : $orcamento->detalhesFornecedor->nome.' - '.$orcamento->detalhesFornecedor->cnpj_cpf,
                        'modo' => $this->modos[$orcamento->modo],
                        'tipo' =>$this->tipos[$orcamento->tipo],
                        'tipo_codigo' => $orcamento->tipo,
                        'modo_codigo' => $orcamento->modo,
                        'origem' => empty($orcamento->detalhesOrigem)? '' : $this->modos[$orcamento->detalhesOrigem->modo].' - '.substr(parserData($orcamento->detalhesOrigem->data), -7),
                        'origem_id' =>  empty($orcamento->detalhesOrigem)? '' : encrypt($orcamento->detalhesOrigem->id),
                        'liberacao_edicao_exclusao' => $liberacao_edicao_exclusao, 
                    ];
                    $total['valor'] += $orcamento->valor;
                }
            }else{
                $orcamentos[] = [
                    'id' => encrypt($orcamento->id),
                    'mes_ano' => substr(parserData($orcamento->data), -7),
                    'valor' => empty($orcamento->valor)? '' : parserValor($orcamento->valor),
                    'fornecedor' => empty($orcamento->detalhesFornecedor)? '' : $orcamento->detalhesFornecedor->nome.' - '.$orcamento->detalhesFornecedor->cnpj_cpf,
                    'modo' => $this->modos[$orcamento->modo],
                    'tipo' =>$this->tipos[$orcamento->tipo],
                    'tipo_codigo' => $orcamento->tipo,
                    'modo_codigo' => $orcamento->modo,
                    'origem' => empty($orcamento->detalhesOrigem)? '' : $this->modos[$orcamento->detalhesOrigem->modo].' - '.substr(parserData($orcamento->detalhesOrigem->data), -7),
                    'origem_id' =>  empty($orcamento->detalhesOrigem)? '' : encrypt($orcamento->detalhesOrigem->id), 
                    'liberacao_edicao_exclusao' => $liberacao_edicao_exclusao, 
                ];
                $total['valor'] += $orcamento->valor;
            }

            
        }

        $total['valor'] = empty($total['valor'])? '' : parserValor($total['valor']);

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'orcamentos' => $orcamentos,
                'total' => $total,
            ],
        ]);
    }

    public function modalEditar(Request $request) {
        $fields = $request->only(['id','tipo']);
        $id = $fields['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $orcamentoCompraObj = OrcamentoCompra::find($id);

        $meses = $this->mesesVerificacao();

        $meses_edicao = [];

        foreach($meses as $index => $mes){
            $verificacao = false;
            if($index == $orcamentoCompraObj->data->month){
                $verificacao = true;
            }

            $meses_edicao[$mes] = $verificacao;
        }

        $dados = [
            'id' => encrypt($orcamentoCompraObj->id),
            'mes_ano' => substr(parserData($orcamentoCompraObj->data), -7),
            'valor' => empty($orcamentoCompraObj->valor)? '' : parserValor($orcamentoCompraObj->valor),
            'fornecedor' => empty($orcamentoCompraObj->fornecedor_codigo)? '' : $orcamentoCompraObj->detalhesFornecedor->nome.' - '.$orcamentoCompraObj->detalhesFornecedor->cnpj_cpf,
            'tipo' => $orcamentoCompraObj->tipo,
            'modo' => $orcamentoCompraObj->modo,
            'condicao_pagamento' => empty($orcamentoCompraObj->detalhesCondicoesPagamentoWeb)? '' : $orcamentoCompraObj->detalhesCondicoesPagamentoWeb->descricao,
            'ano' => $orcamentoCompraObj->data->year,
            'dia_fluxo_inicial' => empty($orcamentoCompraObj->dia_fluxo_inicial)? '' : $orcamentoCompraObj->dia_fluxo_inicial,
            'meses_edicao' => $meses_edicao
        ];

        $fixo_modulo = '';
        if($fields['tipo'] == 'compras'){
            $tipos = $this->tipos_compras;
            $fixo_modulo = 'competencia';
        }else{
            $tipos = $this->tipos_outros;
            $fixo_modulo = 'competencia';
        }

        return view('programs.orcamento_compras.modal.editar')->with(['dados' => $dados,'modos' => $this->modos, 'tipos' => $tipos, 'fixo_modulo' => $fixo_modulo]);
    }

    public function editar(OrcamentoCompraAdicionarEditarRequest $request){
        $fields = $request->only(
            'id',
            'modo', 
            'tipo', 
            'mes_ano', 
            'valor', 
            'fornecedor', 
            'condicao_pagamento_descr', 
            'ano_do', 
            'ano_ate',
            'janeiro',
            'fevereiro',
            'marco',
            'abril',
            'maio',
            'junho',
            'julho',
            'agosto',
            'setembro',
            'outubro',
            'novembro',
            'dezembro',
            'dia_fluxo_inicial'
        );

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $ano_inicial = intval($fields['ano_do']);
        $ano_final = intval($fields['ano_ate']);
        $dia_fluxo_inicial = $fields['dia_fluxo_inicial'];
        $data_atual = Carbon::now();

        $meses = $this->mesesVerificacao();
        $datas = [];

        foreach($meses as $mes){
            if(isset($fields[$mes])){
                while($ano_inicial <= $ano_final){
                    if($ano_inicial == $data_atual->year){
                        if(intval($fields[$mes]) > intval($data_atual->format('m'))){
                            $datas[] = '01/'.$fields[$mes].'/'.$ano_inicial;
                        }
                    }else{
                        $datas[] = '01/'.$fields[$mes].'/'.$ano_inicial;
                    }

                    $ano_inicial++;                  
                }
                $ano_inicial = intval($fields['ano_do']);
            }
        }

        if(in_array($fields['tipo'], $this->tipos_compras_verificacao)){
            $condicoesPagamentoWebObj = CondicoesPagamentoWeb::select()
                ->with(['parcelas.parcelas'])
                ->where('descricao', 'ilike', $fields['condicao_pagamento_descr'])
                ->where('nasajon', true)
                ->where('ativo', true)
                ->first();
        }

        $orcamentoCompraObj = OrcamentoCompra::find($id);

        $fornecedor_codigo = $orcamentoCompraObj->fornecedor_codigo;

        foreach($datas as $data){
            $data_carbon = Carbon::createFromFormat('d/m/Y', $data)->setTime(0,0,0);

            $orcamentoCompraEditarObj = OrcamentoCompra::select()
                ->where('data', $data_carbon)
                ->where('fornecedor_codigo', $fornecedor_codigo)
                ->where('tipo', $fields['tipo']) 
                ->first();
            if(!empty($orcamentoCompraEditarObj)){
                $orcamentoCompraFluxoDeletarObj = OrcamentoCompra::select()->where('competencia_orcamento_compras_id', $orcamentoCompraEditarObj->id)->get();

                foreach($orcamentoCompraFluxoDeletarObj as $value){
                    $value->deleted_by = Auth::id();
                    $value->save();
                   //$value->delete();
                }
            }else{
                $orcamentoCompraEditarObj = new OrcamentoCompra;
            }            
            $orcamentoCompraEditarObj->data = $data_carbon;
            $orcamentoCompraEditarObj->modo = !isset($fields['modo'])? 'competencia' : $fields['modo'];  
            $orcamentoCompraEditarObj->tipo = $fields['tipo']; 
            $orcamentoCompraEditarObj->fornecedor_codigo = $fornecedor_codigo;  
            $orcamentoCompraEditarObj->valor = empty($fields['valor'])? 0 : parserNumber($fields['valor']);  
            if(in_array($fields['tipo'], $this->tipos_compras_verificacao)){
                $orcamentoCompraEditarObj->parcela_quantidade = $condicoesPagamentoWebObj->parcelas->quantidadeparcelas;
                $orcamentoCompraEditarObj->parcela_valor = empty($fields['valor'])? 0 : parserNumber($fields['valor'])/$condicoesPagamentoWebObj->parcelas->quantidadeparcelas;
                $orcamentoCompraEditarObj->condicoes_pagamento_web_id = $condicoesPagamentoWebObj->id;
                $orcamentoCompraEditarObj->dia_fluxo_inicial = intval($dia_fluxo_inicial);
            }
            $orcamentoCompraEditarObj->created_by = Auth::id();
            //$orcamentoCompraEditarObj->save();

            if(in_array($fields['tipo'], $this->tipos_compras_verificacao)){
                $datas_fluxo_caixa = [];
                $dia_verificacao = $dia_fluxo_inicial;
                if(intval($data_carbon->month) == 2){
                    if(intval($dia_fluxo_inicial) > 28){
                        $dia_verificacao = 28;
                    }
                }
                foreach($condicoesPagamentoWebObj->parcelas->parcelas as $parcela_detalhes){
                    $data_carbon_fluxo_caixa = Carbon::createFromFormat('d/m/Y', $dia_verificacao."/".substr($data, -7))->setTime(0,0,0);
                    $data_carbon_fluxo_caixa = $data_carbon_fluxo_caixa->addDays($parcela_detalhes->quantidadediapagamento);
                    $datas_fluxo_caixa[] = $data_carbon_fluxo_caixa;
                }

                foreach($datas_fluxo_caixa as $data_fluxo_caixa){
                    $feriadoControllerObj = new FeriadoController;
                    $feriado = $feriadoControllerObj->getFeriadosPeriodo($data_carbon_fluxo_caixa,$data_carbon_fluxo_caixa);
                    
                    $orcamentoCompraFluxoObj = new OrcamentoCompra;
                    $pagamento = null; 
                 
                    if(count($feriado) == 1){
                        $orcamentoCompraFluxoObj->data = $data_carbon_fluxo_caixa->addDays(1);
                        $pagamento = 'postecipado';
                    }
                    if($data_carbon_fluxo_caixa->dayOfWeekIso == 6){
                        $orcamentoCompraFluxoObj->data = $data_carbon_fluxo_caixa->addDays(1);
                        $pagamento = 'postecipado';
                    }
                    elseif($data_carbon_fluxo_caixa->dayOfWeekIso == 7){
                        $orcamentoCompraFluxoObj->data = $data_carbon_fluxo_caixa->addDays(2);
                        $pagamento = 'postecipado';
                    }else{
                        $orcamentoCompraFluxoObj->data = $data_fluxo_caixa;
                    }
                    
                    $orcamentoCompraFluxoObj->modo = 'fluxo_caixa';  
                    $orcamentoCompraFluxoObj->tipo = $fields['tipo']; 
                    $orcamentoCompraFluxoObj->fornecedor_codigo = $fornecedor_codigo;  
                    $orcamentoCompraFluxoObj->valor = empty($fields['valor'])? 0 : parserNumber($fields['valor'])/$condicoesPagamentoWebObj->parcelas->quantidadeparcelas;
                    $orcamentoCompraFluxoObj->created_by = Auth::id();
                    $orcamentoCompraFluxoObj->competencia_orcamento_compras_id = $orcamentoCompraObj->id;
                    $orcamentoCompraFluxoObj->pagamento = $pagamento;
                    $orcamentoCompraFluxoObj->save();
                }
            }
        }

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
            ], 422);
        }

        $orcamentoCompraObj = OrcamentoCompra::find($id);

        $dia_fluxo_inicial = '';
        if(!empty($orcamentoCompraObj->dia_fluxo_inicial)){
            if(intval($orcamentoCompraObj->data->month) == 2){
                if(intval($orcamentoCompraObj->dia_fluxo_inicial) > 28){
                    $dia_fluxo_inicial = '28/'.substr(parserData($orcamentoCompraObj->data), -7);
                }else{
                    $dia_fluxo_inicial = str_pad($orcamentoCompraObj->dia_fluxo_inicial, 2, 0, STR_PAD_LEFT).'/'.substr(parserData($orcamentoCompraObj->data), -7);
                }
            }else{
                $dia_fluxo_inicial = str_pad($orcamentoCompraObj->dia_fluxo_inicial, 2, 0, STR_PAD_LEFT).'/'.substr(parserData($orcamentoCompraObj->data), -7);
            }
        }

        $dados = [
            'id' => encrypt($orcamentoCompraObj->id),
            'mes_ano' => substr(parserData($orcamentoCompraObj->data), -7),
            'valor' => empty($orcamentoCompraObj->valor)? '' : parserValor($orcamentoCompraObj->valor),
            'fornecedor' => empty($orcamentoCompraObj->fornecedor_codigo)? '' : $orcamentoCompraObj->detalhesFornecedor->nome.' - '.$orcamentoCompraObj->detalhesFornecedor->cnpj_cpf,
            'modo' => $this->modos[$orcamentoCompraObj->modo],
            'tipo' =>$this->tipos[$orcamentoCompraObj->tipo],
            'condicao_pagamento' => empty($orcamentoCompraObj->detalhesCondicoesPagamentoWeb)? '' : $orcamentoCompraObj->detalhesCondicoesPagamentoWeb->descricao,
            'compras_data' => empty($orcamentoCompraObj->dia_fluxo_inicial)? '' : $dia_fluxo_inicial,
        ];

        $orcamentoCompraObj = OrcamentoCompra::select()->where('competencia_orcamento_compras_id', $id)->orderBy('data')->get();
        $fluxos = [];
        foreach($orcamentoCompraObj as $value){
            $fluxos[] = [
                'id' => encrypt($value->id),
                'mes_ano' => parserData($value->data),
                'valor' => empty($value->valor)? '' : parserValor($value->valor),
                'fornecedor' => empty($value->fornecedor_codigo)? '' : $value->detalhesFornecedor->nome.' - '.$value->detalhesFornecedor->cnpj_cpf,
                'modo' => $this->modos[$value->modo],
                'tipo' =>$this->tipos[$value->tipo],
            ];
        }

        return view('programs.orcamento_compras.modal.deletar')->with(['dados' => $dados, 'fluxos' => $fluxos]);
    }

    public function deletar(Request $request){
        $id = $request->only(['id'])['id'];

        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $orcamentoCompraObj = OrcamentoCompra::find($id);
        $orcamentoCompraObj->deleted_by = Auth::id();
        $orcamentoCompraObj->save();
        $orcamentoCompraObj->delete();

        $orcamentoCompraObj = OrcamentoCompra::select()->where('competencia_orcamento_compras_id', $id)->get();

        foreach($orcamentoCompraObj as $value){
            $value->deleted_by = Auth::id();
            $value->save();
            $value->delete();
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    private function mesesVerificacao(){
        $meses = [
            1 => 'janeiro',
            2 => 'fevereiro',
            3 => 'marco',
            4 => 'abril',
            5 => 'maio',
            6 => 'junho',
            7 => 'julho',
            8 => 'agosto',
            9 => 'setembro',
            10 => 'outubro',
            11 => 'novembro',
            12 => 'dezembro',
        ];

        return $meses;
    }
}
