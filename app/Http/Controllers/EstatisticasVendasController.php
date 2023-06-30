<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

use App\Movimentacao;
use App\ClienteNasajon;

//30/06/2022 retirado faturamentoNasajon e colocado Faturamento (Portal)


use App\Http\Requests\EstatisticasVendasFiltroRequest;

class EstatisticasVendasController extends Controller
{
    private $cfop_vendas = ['5922', '6108','5110', '6110', '5119', '6119', '5123', '6123', '5106', '6106', '5118', '6118', '5122', '6122', '5102', '6102', '5106', '5101', '6101', '5104', '6104'];

    public function __construct() {
        $this->middleware(['auth']);
        $estabelecimentosEmpty[''] = 'ESTABELECIMENTO';
        $estabelecimentosReturn = returnEmpresasNasajonView();
        foreach($estabelecimentosReturn as $key => $estabelecimento){
            $estabelecimentosEmpty[str_pad($key,2,'0', STR_PAD_LEFT)] = $estabelecimento;
        }

        $this->estabelecimentos = $estabelecimentosEmpty;
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\EstatisticasVendas") === false){
            return abort(403);
        };

        $request->session()->flash('model', 'App\EstatisticasVendas');

    	return view("programs.estatisticas_vendas.index")->with(['estabelecimentos' => $this->estabelecimentos]);
    }

    private function clienteExcluir(){
        $clientes_exluir = ClienteNasajon::select('codigo')
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
        ->get();
        return $clientes_exluir->pluck('codigo')->toArray();
    }

    public function filtro(EstatisticasVendasFiltroRequest $request){
        ini_set('memory_limit','2042M');
        set_time_limit(500);
        $fields = $request->only(['marca','grupo', 'linha', 'estabelecimento','data_inicio','data_fim','prepago']);

        $prepago =' ';
       
        if(isset($fields['prepago'])){
           $prepago =   '+ case when preco_prepago is not null then preco_prepago else 0 end';
        }
         

        $data_inicial = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d 00:00:00');
        $data_final = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d 00:00:00');

        $MovimentacaoObj = Movimentacao::query()->select('unidade',DB::raw('estabelecimento,sum(quantidade) as quantidade,cliente_codigo,documento,
        sum(((quantidade * preco)::numeric(15, 2) + frete + ipi + seguro + case when preco_prepago is not null then preco_prepago else 0 end) - desconto)::numeric(15, 2) as valor')) 
        ->whereBetween('data_movimentacao',[$data_inicial,$data_final])
        ->whereNotIn('cliente_codigo',$this->clienteExcluir())
        ->whereIn('cfop',$this->cfop_vendas)
        //->with(['faturamentoNasajon'])
        ->with(['faturamento'])
        
        ->groupBy('estabelecimento','cliente_codigo','documento', 'unidade');

       
        if(!empty($fields['estabelecimento'])){
            $MovimentacaoObj->where('estabelecimento',$fields['estabelecimento']);
        }

      if(!empty($fields['marca']) ||!empty($fields['grupo']) || !empty($fields['linha'])){ 
            if(!empty($fields['marca'])){
                $MovimentacaoObj->where("marca", '=' , $fields['marca']);
            } 
            if(!empty($fields['linha'])){
                $MovimentacaoObj->where("linha", '=', $fields['linha']);
            }            
            if(!empty($fields['grupo'])){
                $MovimentacaoObj->where("grupo", '=' , $fields['grupo']);
            }
        }
        $total = [  
            'peso' => 0,
            'metros' => 0,                
            'outras_unidades' => 0,            
            'cliente' => 0,
            'notas' => 0,
            'valor' => 0,
            'ticket_medio' => 0,
            'data_inicio' => $fields['data_inicio'],
            'data_final' => $fields['data_fim'],
            'filter' => encrypt([
                'estabelecimento' => $fields['estabelecimento'],
                'marca' => $fields['marca'],
                'linha' => $fields['linha'],
                'grupo' => $fields['grupo'],                
                'data_inicio' => $fields['data_inicio'],
                'data_fim' => $fields['data_fim']
            ])
        ];
        $retorno          = [];
        $estabelecimentos = returnEmpresasNasajonView();
        $metros_Array     = [];
        $peso_Array       = [];
        $outra_Array      = [];
        $clientes_array   = ',';
        $notas_array      = ',';
        $valor_array      = [];
        $ticket_array     = [];
        $qtde_notas       = 0;


        $MovimentacaoObj->distinct();
     
        $MovimentacaoObj = $MovimentacaoObj->get();

        foreach($MovimentacaoObj as $movimento){     
            //if(!empty($movimento->faturamentoNasajon)){
            if(!empty($movimento->faturamento)){
                $estabelecimento = $estabelecimentos[(integer)$movimento->estabelecimento];

                if(!isset($retorno[$estabelecimento])){
                    $retorno[$estabelecimento] = [
                        'estabelecimento' => $estabelecimento,
                        'peso' => 0,
                        'metros' => 0,                
                        'outras_unidades' => 0,    
                        'cliente' => 0,
                        'notas' => 0,
                        'valor' => 0,
                        'ticket_medio' => 0,
                        'data_inicio' => $fields['data_inicio'],
                        'data_final' => $fields['data_fim'],
                        'filter' => encrypt([
                            'estabelecimento' => $movimento->estabelecimento,
                            'marca' => $fields['marca'],
                            'linha' => $fields['linha'],
                            'grupo' => $fields['grupo'],					
                            'data_inicio' => $fields['data_inicio'],
                            'data_fim' => $fields['data_fim']
                        ])
                    ];
                
                }
    
                if(substr_count($clientes_array, ','.$movimento->cliente_codigo.',') === 0){
                    $retorno[$estabelecimento]['cliente'] ++;
                    $clientes_array .= $movimento->cliente_codigo.',';               
                }
    
                if(substr_count($notas_array, ','.$movimento->documento.',') === 0){
                    $retorno[$estabelecimento]['notas'] ++;
                    $notas_array .= $movimento->documento.',';    
                    $qtde_notas = $retorno[$estabelecimento]['notas'];
                    $retorno[$estabelecimento]['ticket_medio'] += ($movimento->valor / $qtde_notas);        
                }
    
                $retorno[$estabelecimento]['valor'] += $movimento->valor;
                
                $unidade = ' ';
    
                if (isset($movimento->unidade)) {
                    $unidade = strtoupper(trim($movimento->unidade));
                }
               
                if ($unidade == 'KG') {
                    $retorno[$estabelecimento]['peso'] += $movimento->quantidade;                       
                } else if (in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME'])) {
                    $retorno[$estabelecimento]['metros'] += $movimento->quantidade;                
                } else {
                    $retorno[$estabelecimento]['outras_unidades'] += $movimento->quantidade;
                }
            }
        }

        unset($MovimentacaoObj);

        foreach($retorno as $key => $value){
            
            $total['peso'] += $retorno[$key]['peso'];
            $total['metros'] += $retorno[$key]['metros'];
            $total['outras_unidades'] += $retorno[$key]['outras_unidades'];

            $total['cliente'] += $retorno[$key]['cliente'];
            $total['ticket_medio'] += $retorno[$key]['ticket_medio'];
            $total['valor'] += $retorno[$key]['valor'];
            $total['notas'] += $retorno[$key]['notas'];

            $retorno[$key]['peso'] = ($retorno[$key]['peso'] > 0) ? parserValor($retorno[$key]['peso']) : '';
            $retorno[$key]['metros'] = ($retorno[$key]['metros'] > 0) ? parserValor($retorno[$key]['metros']) : '';
            $retorno[$key]['outras_unidades'] = ($retorno[$key]['outras_unidades'] > 0) ? parserValor($retorno[$key]['outras_unidades']) : '';

            $retorno[$key]['ticket_medio'] = ($retorno[$key]['ticket_medio'] > 0) ? parserValor(($retorno[$key]['valor']/$retorno[$key]['notas'])) : '';
            $retorno[$key]['valor'] = ($retorno[$key]['valor'] > 0) ? parserValor($retorno[$key]['valor']) : '';
            $retorno[$key]['notas'] = ($retorno[$key]['notas'] > 0) ? $retorno[$key]['notas'] : '';
            $retorno[$key]['cliente'] = ($retorno[$key]['cliente'] > 0) ? $retorno[$key]['cliente'] : '';
        }

        $total['peso'] = ($total['peso'] > 0) ? parserValor($total['peso']) : '';
        $total['metros'] = ($total['metros'] > 0) ? parserValor($total['metros']) : '';
        $total['outras_unidades'] = ($total['outras_unidades'] > 0) ? parserValor($total['outras_unidades']) : '';

        $total['ticket_medio'] = ($total['valor'] > 0) ? parserValor($total['valor']  / $total['notas']) : '';
        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        $total['cliente'] = ($total['cliente'] > 0) ? $total['cliente'] : '';
        $total['notas'] = ($total['notas'] > 0) ? $total['notas'] : '';

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => ['saida' => $retorno, 'total' => $total]
        ]);

    }

    public function modalFormaPagamento(Request $request){
        ini_set('memory_limit','1024M');
        set_time_limit(300);
        $filter = $request->only(['filters', 'total']);

        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $data_inicial = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d 00:00:00');
        $data_final = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d 00:00:00');

        $MovimentacaoObj = Movimentacao::select(DB::raw('sum((quantidade * preco)::numeric(15, 2)) as valor,documento,estabelecimento,cliente_codigo'))
        ->whereBetween('data_movimentacao',[$data_inicial,$data_final])
        ->whereIn('cfop',$this->cfop_vendas)
        ->whereNotIn('cliente_codigo',$this->clienteExcluir())
        ->with(['faturamento.detalhesCondicoesPagamentos']); 

        if($filter['total'] == 'false' && !empty($fields['estabelecimento']) || $filter['total'] == 'true' && !empty($fields['estabelecimento'])){
            $MovimentacaoObj->where('estabelecimento',$fields['estabelecimento']);
        }
        
        if(!empty($fields['marca']) ||!empty($fields['grupo']) || !empty($fields['linha'])){
            $MovimentacaoObj->whereHas('produto', function($query) use ($fields){
                if(!empty($fields['marca'])){
                    $query->where("marca", '=' , $fields['marca']);
                }
                if(!empty($fields['linha'])){
                    $query->where("linha", '=', $fields['linha']);
                }
                if(!empty($fields['grupo'])){
                    $query->where("grupo", '=' , $fields['grupo']);
                }
                
            });
        }

        $MovimentacaoObj->groupBy('documento', 'estabelecimento', 'cliente_codigo');
     
        $MovimentacaoObj = $MovimentacaoObj->get();

        $total = [
            'cliente' => 0,
            'notas' => 0,
            'valor' => 0,
            'ticket_medio' => 0,
            'sobre_total' => 0,
            'prazo_medio' => 0
        ];
        $retorno = [];
        $clientes_array = [];
        $notas_Array = [];       
        $clientenotavalores_Array = []; 

       $teste = [];
        foreach($MovimentacaoObj as $movimento){
            $forma_pagamento = '';
            $valor_pagamento = 0;
            $media_pagamento = 0;

            $num_pagto = (isset($movimento->faturamento->detalhesCondicoesPagamentos)) ?  count($movimento->faturamento->detalhesCondicoesPagamentos) : 0;

            if($num_pagto > 0){
                for($i=0;$i<$num_pagto;$i++){
                    $valor_pagamento = $movimento->faturamento->detalhesCondicoesPagamentos[$i]->valor;
                    $forma_pagamento = $movimento->faturamento->detalhesCondicoesPagamentos[$i]->formapagamento_descricao;  
                    //$media_pagamento = $movimento->faturamentoNasajon->detalhesDeCondicoesPagamentos[$i]->condicao;       
                    //dd($media_pagamento)    ;

                    if(!isset($retorno[$forma_pagamento])){
                        $retorno[$forma_pagamento] = [
                            'forma_pagamento' =>$forma_pagamento,
                            'cliente' => 0,
                            'notas' => 0,
                            'valor' => 0,
                            'ticket_medio' => 0,
                            'sobre_total' => 0,
                            'prazo_medio' => 0,
                        ];
                    }

                    if(!in_array($movimento->cliente_codigo.$forma_pagamento,$clientes_array)){
                        $retorno[$forma_pagamento]['cliente'] ++;
                        $clientes_array[] = $movimento->cliente_codigo.$forma_pagamento;               
                    }
                    if(!in_array($movimento->documento.$forma_pagamento,$notas_Array)){
                        $retorno[$forma_pagamento]['notas'] ++;
                        $notas_Array[] = $movimento->documento.$forma_pagamento;  
                    }

                    if($forma_pagamento == "Cartão Crédito"){
                        $teste[] =  $valor_pagamento;
                    }
                    $retorno[$forma_pagamento]['valor'] += $valor_pagamento; 
                }

                $total['valor'] += $valor_pagamento + $movimento->preco_prepago;
                $retorno[$forma_pagamento]['prazo_medio'] += $media_pagamento;
            }
        }
        unset($MovimentacaoObj);

        foreach($retorno as $key => $value){
            $ticke_medio = ($retorno[$key]['valor'] > 0) ? ($retorno[$key]['valor'] / $retorno[$key]['notas']) : 0;

            $total['cliente'] += $retorno[$key]['cliente'];            
            $total['notas'] += $retorno[$key]['notas'];
            $total['prazo_medio'] += $retorno[$key]['prazo_medio'];
            $total['sobre_total'] += ($retorno[$key]['valor'] > 0) ? $retorno[$key]['valor'] / $ticke_medio : 0;
            $total['ticket_medio'] += $ticke_medio;

            $retorno[$key]['sobre_total'] = ($retorno[$key]['valor'] > 0) ? parserValor($retorno[$key]['valor'] / $total['valor'] * 100) : '';
            $retorno[$key]['ticket_medio'] = ($ticke_medio > 0) ? parserValor($ticke_medio) : '';
            $retorno[$key]['valor'] = ($retorno[$key]['valor'] > 0) ? parserValor($retorno[$key]['valor']) : '';
            $retorno[$key]['notas'] = ($retorno[$key]['notas'] > 0) ? $retorno[$key]['notas'] : '';
            $retorno[$key]['cliente'] = ($retorno[$key]['cliente'] > 0) ? $retorno[$key]['cliente'] : '';
            $retorno[$key]['prazo_medio'] = ($retorno[$key]['prazo_medio'] > 0) ? $retorno[$key]['prazo_medio'] : '';
        }

        $total['ticket_medio'] = ($total['valor'] > 0) ? parserValor($total['valor'] / $total['notas']) : '';
        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        $total['cliente'] = ($total['cliente'] > 0) ? $total['cliente'] : '';
        $total['notas'] = ($total['notas'] > 0) ? $total['notas'] : '';
        $total['prazo_medio'] = ($total['prazo_medio'] > 0) ? $total['prazo_medio'] : '';
        $total['sobre_total'] = '';
        
        return view('programs.estatisticas_vendas.modal.forma_pagamento')->with(['response' => $retorno,'total' => $total]);
    }

    public function modalLinha(Request $request){
        ini_set('memory_limit','1024M');
        set_time_limit(300);
        $filter = $request->only(['filters', 'total']);

        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }
        
        $data_inicial_busca = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d 00:00:00');
        $data_final_busca = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d 00:00:00');

        $data_inicial_anterior = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->subYear()->format('Y-m-d 00:00:00');
        $data_final_anterior = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->subYear()->format('Y-m-d 00:00:00');

        $movimentacao_atual = Movimentacao::query()
        ->whereBetween('data_movimentacao',[$data_inicial_busca,$data_final_busca])
        ->whereNotIn('cliente_codigo',$this->clienteExcluir())
        //->with(['faturamentoNasajon'])
        ->with(['faturamento'])
        ->WhereIn('cfop',$this->cfop_vendas);

        $movimentacao_anterior = Movimentacao::query()
        ->whereBetween('data_movimentacao',[$data_inicial_anterior,$data_final_anterior])
        ->whereNotIn('cliente_codigo',$this->clienteExcluir())
        ->WhereIn('cfop',$this->cfop_vendas);

        if($filter['total'] == 'false' && !empty($fields['estabelecimento']) || $filter['total'] == 'true' && !empty($fields['estabelecimento'])){
            $movimentacao_atual->where('estabelecimento',$fields['estabelecimento']);
            $movimentacao_anterior->where('estabelecimento',$fields['estabelecimento']);
        }

        if(!empty($fields['marca']) ||!empty($fields['grupo']) || !empty($fields['linha'])){
            $movimentacao_atual->whereHas('produto', function($query) use ($fields){
                if(!empty($fields['marca'])){
                    $query->where("marca", '=' , $fields['marca']);
                }
                if(!empty($fields['linha'])){
                    $query->where("linha", '=', $fields['linha']);
                }
                if(!empty($fields['grupo'])){
                    $query->where("grupo", '=' , $fields['grupo']);
                }
                
            });

            $movimentacao_anterior->whereHas('produto', function($query) use ($fields){
                if(!empty($fields['marca'])){
                    $query->where("marca", '=' , $fields['marca']);
                }
                if(!empty($fields['linha'])){
                    $query->where("linha", '=', $fields['linha']);
                }
                if(!empty($fields['grupo'])){
                    $query->where("grupo", '=' , $fields['grupo']);
                }
               
            });
        }

        $movimentacao_atual->select('linha',DB::raw('Extract (Year from data_movimentacao) as ano, sum("quantidade") as quantidade, sum((quantidade * preco)::numeric(15, 2)) as valor,  sum(frete) as frete, sum(ipi) as ipi, sum(seguro) as seguro, sum(desconto) as desconto, sum(preco) as preco, sum(preco_prepago) as preco_prepago, documento, estabelecimento, cliente_codigo'))
        ->groupBy('linha','cfop', DB::raw('Extract (Year from data_movimentacao)'), 'documento', 'estabelecimento', 'cliente_codigo');
        $movimentacao_atual = $movimentacao_atual->get();

        $movimentacao_anterior->select('linha',DB::raw('Extract (Year from data_movimentacao) as ano, sum("quantidade") as quantidade, sum((quantidade * preco)::numeric(15, 2))  as valor, sum(frete) as frete, sum(ipi) as ipi, sum(seguro) as seguro, sum(desconto) as desconto, sum(preco) as preco, sum(preco_prepago) as preco_prepago'))
        ->groupBy('linha','cfop', DB::raw('Extract (Year from data_movimentacao)'));
        $movimentacao_anterior = $movimentacao_anterior->get();
        
        $total = [];
        $retorno = [];
        $anos = [
            Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y'),
            Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->subYear()->format('Y')
        ];

        if(!empty($movimentacao_atual)){
            foreach($movimentacao_atual as $movimento){
                if(!empty($movimento->faturamentoNasajon)){
                    if(!isset($retorno[$movimento->ano][$movimento->linha])){
                        $retorno[$movimento->ano][$movimento->linha] = [
                            'quantidade_vendida' => 0,
                            'valor' => 0,
                            'preco_medio' => 0,
                            'percentual' => 0,
                        ];
                    }
                    
                    $retorno[$movimento->ano][$movimento->linha]['quantidade_vendida'] += $movimento->quantidade;
                    $retorno[$movimento->ano][$movimento->linha]['valor'] += ($movimento->valor + $movimento->frete + $movimento->ipi + $movimento->seguro + $movimento->preco_prepago) - $movimento->desconto;
                


                    if(!isset($total[$movimento->ano])){
                        $total[$movimento->ano] = [
                            'quantidade_vendida' => 0,
                            'valor' => 0,
                            'preco_medio' => 0,
                            'percentual' => 0,
                        ];
                    }

                    $total[$movimento->ano]['quantidade_vendida'] += $movimento->quantidade;
                    $total[$movimento->ano]['valor'] += ($movimento->valor + $movimento->frete + $movimento->ipi + $movimento->seguro + $movimento->preco_prepago) - $movimento->desconto;
                }

            }
        }else{
            $retorno[$anos[0]] = [''];
        }

        unset($movimentacao_atual);
        
        if(!empty($movimentacao_anterior)){
            foreach($movimentacao_anterior as $movimento_anterior){
                if(!isset($retorno[$movimento_anterior->ano][$movimento_anterior->linha])){
                    $retorno[$movimento_anterior->ano][$movimento_anterior->linha] = [
                        'quantidade_vendida' => 0,
                        'valor' => 0,
                        'preco_medio' => 0,
                        'percentual' => 0,
                    ];
                }

                $retorno[$movimento_anterior->ano][$movimento_anterior->linha]['quantidade_vendida'] += $movimento_anterior->quantidade;
                $retorno[$movimento_anterior->ano][$movimento_anterior->linha]['valor'] += ($movimento_anterior->valor + $movimento_anterior->frete + $movimento_anterior->ipi + $movimento_anterior->seguro + $movimento_anterior->preco_prepago) - $movimento_anterior->desconto ;
                


                if(!isset($total[$movimento_anterior->ano])){
                    $total[$movimento_anterior->ano] = [
                        'quantidade_vendida' => 0,
                        'valor' => 0,
                        'preco_medio' => 0,
                        'percentual' => 0,
                    ];
                }

                $total[$movimento_anterior->ano]['quantidade_vendida'] += $movimento_anterior->quantidade;
                $total[$movimento_anterior->ano]['valor'] += ($movimento_anterior->valor + $movimento_anterior->frete + $movimento_anterior->ipi + $movimento_anterior->seguro + $movimento_anterior->preco_prepago) - $movimento_anterior->desconto ;
                

            }
        }else{
            $retorno[$anos[1]] = [''];
        }

        unset($movimentacao_anterior);
        $heads = $anos;
        $total_geral = [
            'quantidade_vendida_busca' => '',
            'valor_busca' => '',
            'preco_medio_busca' => '',
            'percentual_busca' => '',
            'quantidade_vendida_anterior' => '',
            'valor_anterior' => '',
            'preco_medio_anterior' => '',
            'percentual_anterior' => '',
        ];
        $tabela = [];

        foreach($retorno as $ano => $value){
            $percentual = 0;

            if($anos[0] == $ano){
                foreach($value as $key => $dados){
                    if(!isset($tabela[$key])){
                        $tabela[$key] = [
                            'linha' =>$key,
                            'valor_ano_busca' => '',
                            'percentual_ano_busca' => '',
                            'quantidade_vendida_ano_busca' => '',
                            'preco_medio_ano_busca' => '',
                            'ano_busca' => $ano,

                            'valor_ano_anterior' => '',
                            'percentual_ano_anterior' => '',
                            'quantidade_vendida_ano_anterior' => '',
                            'preco_medio_ano_anterior' => '',
                            'ano_anterior' => '',
                        ];
                    }

                    $total_ano = $total[$ano]['valor'];
                    $percentual = ($total_ano > 0) ? $retorno[$ano][$key]['valor']/$total_ano*100 : '';

                    $tabela[$key]['preco_medio_ano_busca'] = ($retorno[$ano][$key]['quantidade_vendida'] > 0) ? parserValor($retorno[$ano][$key]['valor'] / $retorno[$ano][$key]['quantidade_vendida']) : '';
                    $tabela[$key]['percentual_ano_busca'] = ($percentual >= 0.01) ? parserValor($percentual) : '';
                    $tabela[$key]['quantidade_vendida_ano_busca'] = ($retorno[$ano][$key]['quantidade_vendida'] > 0) ? parserValor($retorno[$ano][$key]['quantidade_vendida']) : '';
                    $tabela[$key]['valor_ano_busca'] = ($retorno[$ano][$key]['valor'] > 0) ? parserValor($retorno[$ano][$key]['valor']) : '';
    
                }

                $total_geral['preco_medio_busca'] = ($total[$ano]['quantidade_vendida'] > 0) ? parserValor($total[$ano]['valor'] / $total[$ano]['quantidade_vendida']) : '';
                $total_geral['quantidade_vendida_busca'] = ($total[$ano]['quantidade_vendida'] > 0) ? parserValor($total[$ano]['quantidade_vendida']) : '';
                $total_geral['valor_busca'] = ($total[$ano]['valor'] > 0) ? parserValor($total[$ano]['valor']) : '';
                $total_geral['percentual_busca'] = '';
                
            }else{
                foreach($value as $key => $dados){
                    if(!isset($tabela[$key])){
                        $tabela[$key] = [
                            'linha' =>$key,
                            'valor_ano_busca' => '',
                            'percentual_ano_busca' => '',
                            'quantidade_vendida_ano_busca' => '',
                            'preco_medio_ano_busca' => '',
                            'ano_busca' => '',

                            'valor_ano_anterior' => '',
                            'percentual_ano_anterior' => '',
                            'quantidade_vendida_ano_anterior' => '',
                            'preco_medio_ano_anterior' => '',
                            'ano_anterior' => $ano,
                        ];
                    }

                    $total_ano = $total[$ano]['valor'];
                    $percentual = ($total_ano > 0) ? $retorno[$ano][$key]['valor']/$total_ano*100  : '';

                    $tabela[$key]['preco_medio_ano_anterior'] = ($retorno[$ano][$key]['quantidade_vendida'] > 0) ? parserValor($retorno[$ano][$key]['valor'] / $retorno[$ano][$key]['quantidade_vendida']) : '';
                    $tabela[$key]['percentual_ano_anterior'] = ($percentual >= 0.01) ? parserValor($percentual) : '';
                    $tabela[$key]['quantidade_vendida_ano_anterior'] = ($retorno[$ano][$key]['quantidade_vendida'] > 0) ? parserValor($retorno[$ano][$key]['quantidade_vendida']) : '';
                    $tabela[$key]['valor_ano_anterior'] = ($retorno[$ano][$key]['valor'] > 0) ? parserValor($retorno[$ano][$key]['valor']) : '';
    
                }

                $total_geral['preco_medio_anterior'] = ($total[$ano]['quantidade_vendida'] > 0) ? parserValor($total[$ano]['valor'] / $total[$ano]['quantidade_vendida']) : '';
                $total_geral['quantidade_vendida_anterior'] = ($total[$ano]['quantidade_vendida'] > 0) ? parserValor($total[$ano]['quantidade_vendida']) : '';
                $total_geral['valor_anterior'] = ($total[$ano]['valor'] > 0) ? parserValor($total[$ano]['valor']) : '';
                $total_geral['percentual_anterior'] = '';
            }

        }
        
        return view('programs.estatisticas_vendas.modal.linha')->with(['retorno' => $tabela,'total' => $total_geral,'head' => $heads]);
    }

    public function modalGrupo(Request $request){
        ini_set('memory_limit','1024M');
        set_time_limit(300);
        $filter = $request->only(['filters', 'total']);

        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }
        //dd($fields['marca']);

        $data_inicial_busca = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d 00:00:00');
        $data_final_busca = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d 00:00:00');

        $data_inicial_anterior = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->subYear()->format('Y-m-d 00:00:00');
        $data_final_anterior = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->subYear()->format('Y-m-d 00:00:00');

        $movimentacao_atual = Movimentacao::query()
        ->whereBetween('data_movimentacao',[$data_inicial_busca,$data_final_busca])
        ->whereNotIn('cliente_codigo',$this->clienteExcluir())
        //->with(['faturamentoNasajon'])
        ->with(['faturamento'])
        ->WhereIn('cfop',$this->cfop_vendas);

        $movimentacao_anterior = Movimentacao::query()
        ->whereBetween('data_movimentacao',[$data_inicial_anterior,$data_final_anterior])
        ->whereNotIn('cliente_codigo',$this->clienteExcluir())
        ->WhereIn('cfop',$this->cfop_vendas);

        if($filter['total'] == 'false' && !empty($fields['estabelecimento']) || $filter['total'] == 'true' && !empty($fields['estabelecimento'])){
            $movimentacao_atual->where('estabelecimento',$fields['estabelecimento']);
            $movimentacao_anterior->where('estabelecimento',$fields['estabelecimento']);
        }

        if(!empty($fields['marca']) || !empty($fields['grupo']) || !empty($fields['linha'])){
            $movimentacao_atual->whereHas('produto', function($query) use ($fields){
                if(!empty($fields['marca'])){
                    $query->where("marca", '=' , $fields['marca']);
                }
                if(!empty($fields['grupo'])){
                    $query->where("grupo", '=' , $fields['grupo']);
                }
                if(!empty($fields['linha'])){
                    $query->where("linha", '=', $fields['linha']);
                }
            });

            $movimentacao_anterior->whereHas('produto', function($query) use ($fields){
                if(!empty($fields['marca'])){
                    $query->where("marca", '=' , $fields['marca']);
                }
                if(!empty($fields['grupo'])){
                    $query->where("grupo", '=' , $fields['grupo']);
                }
                if(!empty($fields['linha'])){
                    $query->where("linha", '=', $fields['linha']);
                }
            });
        }

        $movimentacao_atual->select('grupo',DB::raw('Extract (Year from data_movimentacao) as ano, sum("quantidade") as quantidade, sum((quantidade * preco)::numeric(15, 2)) as valor,  sum(frete) as frete, sum(ipi) as ipi, sum(seguro) as seguro, sum(desconto) as desconto, sum(preco) as preco, sum(preco_prepago) as preco_prepago, documento, estabelecimento, cliente_codigo'))
        ->groupBy('grupo','cfop', DB::raw('Extract (Year from data_movimentacao)'), 'documento', 'estabelecimento', 'cliente_codigo');
        $movimentacao_atual = $movimentacao_atual->get();

        $movimentacao_anterior->select('grupo',DB::raw('Extract (Year from data_movimentacao) as ano, sum("quantidade") as quantidade, sum((quantidade * preco)::numeric(15, 2)) as valor,  sum(frete) as frete, sum(ipi) as ipi, sum(seguro) as seguro, sum(desconto) as desconto, sum(preco) as preco, sum(preco_prepago) as preco_prepago'))
        ->groupBy('grupo','cfop', DB::raw('Extract (Year from data_movimentacao)'));
        $movimentacao_anterior = $movimentacao_anterior->get();
        
        $total = [];
        $retorno = [];
        $anos = [
            Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y'),
            Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->subYear()->format('Y')
        ];

        if(!empty($movimentacao_atual)){
            foreach($movimentacao_atual as $movimento){
                //if(!empty($movimento->faturamentoNasajon)){
                if(!empty($movimento->faturamento)){
                    if(!isset($retorno[$movimento->ano][$movimento->grupo])){
                        $retorno[$movimento->ano][$movimento->grupo] = [
                            'quantidade_vendida' => 0,
                            'valor' => 0,
                            'preco_medio' => 0,
                            'percentual' => 0,
                        ];
                    }

                    $retorno[$movimento->ano][$movimento->grupo]['quantidade_vendida'] += $movimento->quantidade;
                    $retorno[$movimento->ano][$movimento->grupo]['valor'] += ($movimento->valor + $movimento->frete + $movimento->ipi + $movimento->seguro + $movimento->preco_prepago) - $movimento->desconto;

                    if(!isset($total[$movimento->ano])){
                        $total[$movimento->ano] = [
                            'quantidade_vendida' => 0,
                            'valor' => 0,
                            'preco_medio' => 0,
                            'percentual' => 0,
                        ];
                    }

                    $total[$movimento->ano]['quantidade_vendida'] += $movimento->quantidade;
                    $total[$movimento->ano]['valor'] += ($movimento->valor + $movimento->frete + $movimento->ipi + $movimento->seguro + $movimento->preco_prepago) - $movimento->desconto;
                }
            }
        }else{
            $retorno[$anos[0]] = [''];
        }

        unset($movimentacao_atual);
        
        if(!empty($movimentacao_anterior)){
            foreach($movimentacao_anterior as $movimento_anterior){
                if(!isset($retorno[$movimento_anterior->ano][$movimento_anterior->grupo])){
                    $retorno[$movimento_anterior->ano][$movimento_anterior->grupo] = [
                        'quantidade_vendida' => 0,
                        'valor' => 0,
                        'preco_medio' => 0,
                        'percentual' => 0,
                    ];
                }

                $retorno[$movimento_anterior->ano][$movimento_anterior->grupo]['quantidade_vendida'] += $movimento_anterior->quantidade;
                $retorno[$movimento_anterior->ano][$movimento_anterior->grupo]['valor'] += (($movimento_anterior->valor + $movimento_anterior->frete + $movimento_anterior->ipi + $movimento_anterior->seguro + $movimento_anterior->preco_prepago) - $movimento_anterior->desconto);

                if(!isset($total[$movimento_anterior->ano])){
                    $total[$movimento_anterior->ano] = [
                        'quantidade_vendida' => 0,
                        'valor' => 0,
                        'preco_medio' => 0,
                        'percentual' => 0,
                    ];
                }

                $total[$movimento_anterior->ano]['quantidade_vendida'] += $movimento_anterior->quantidade;
                $total[$movimento_anterior->ano]['valor'] += (($movimento_anterior->valor + $movimento_anterior->frete + $movimento_anterior->ipi + $movimento_anterior->seguro + $movimento_anterior->preco_prepago) - $movimento_anterior->desconto);
            }
        }else{
            $retorno[$anos[1]] = [''];
        }

        unset($movimentacao_anterior);
        $heads = $anos;
        $total_geral = [
            'quantidade_vendida_busca' => '',
            'valor_busca' => '',
            'preco_medio_busca' => '',
            'percentual_busca' => '',
            'quantidade_vendida_anterior' => '',
            'valor_anterior' => '',
            'preco_medio_anterior' => '',
            'percentual_anterior' => '',
        ];
        $tabela = [];

        foreach($retorno as $ano => $value){
            $percentual = 0;

            if($anos[0] == $ano){
                foreach($value as $key => $dados){
                    if(!isset($tabela[$key])){
                        $tabela[$key] = [
                            'grupo' =>$key,
                            'valor_ano_busca' => '',
                            'percentual_ano_busca' => '',
                            'quantidade_vendida_ano_busca' => '',
                            'preco_medio_ano_busca' => '',
                            'ano_busca' => $ano,

                            'valor_ano_anterior' => '',
                            'percentual_ano_anterior' => '',
                            'quantidade_vendida_ano_anterior' => '',
                            'preco_medio_ano_anterior' => '',
                            'ano_anterior' => '',
                        ];
                    }

                    $total_ano = $total[$ano]['valor'];
                    $percentual = ($total_ano > 0) ? $retorno[$ano][$key]['valor']/$total_ano*100 : '';

                    $tabela[$key]['preco_medio_ano_busca'] = ($retorno[$ano][$key]['quantidade_vendida'] > 0) ? parserValor($retorno[$ano][$key]['valor'] / $retorno[$ano][$key]['quantidade_vendida']) : '';
                    $tabela[$key]['percentual_ano_busca'] = ($percentual >= 0.01) ? parserValor($percentual) : '';
                    $tabela[$key]['quantidade_vendida_ano_busca'] = ($retorno[$ano][$key]['quantidade_vendida'] > 0) ? parserValor($retorno[$ano][$key]['quantidade_vendida']) : '';
                    $tabela[$key]['valor_ano_busca'] = ($retorno[$ano][$key]['valor'] > 0) ? parserValor($retorno[$ano][$key]['valor']) : '';
                }

                $total_geral['preco_medio_busca'] = ($total[$ano]['quantidade_vendida'] > 0) ? parserValor($total[$ano]['valor'] / $total[$ano]['quantidade_vendida']) : '';
                $total_geral['quantidade_vendida_busca'] = ($total[$ano]['quantidade_vendida'] > 0) ? parserValor($total[$ano]['quantidade_vendida']) : '';
                $total_geral['valor_busca'] = ($total[$ano]['valor'] > 0) ? parserValor($total[$ano]['valor']) : '';
                $total_geral['percentual_busca'] = '';
                
            }else{
                foreach($value as $key => $dados){
                    if(!isset($tabela[$key])){
                        $tabela[$key] = [
                            'grupo' =>$key,
                            'valor_ano_busca' => '',
                            'percentual_ano_busca' => '',
                            'quantidade_vendida_ano_busca' => '',
                            'preco_medio_ano_busca' => '',
                            'ano_busca' => '',

                            'valor_ano_anterior' => '',
                            'percentual_ano_anterior' => '',
                            'quantidade_vendida_ano_anterior' => '',
                            'preco_medio_ano_anterior' => '',
                            'ano_anterior' => $ano,
                        ];
                    }

                    $total_ano = $total[$ano]['valor'];
                    $percentual = ($total_ano > 0) ? $retorno[$ano][$key]['valor']/$total_ano*100  : '';

                    $tabela[$key]['preco_medio_ano_anterior'] = ($retorno[$ano][$key]['quantidade_vendida'] > 0) ? parserValor($retorno[$ano][$key]['valor'] / $retorno[$ano][$key]['quantidade_vendida']) : '';
                    $tabela[$key]['percentual_ano_anterior'] = ($percentual >= 0.01) ? parserValor($percentual) : '';
                    $tabela[$key]['quantidade_vendida_ano_anterior'] = ($retorno[$ano][$key]['quantidade_vendida'] > 0) ? parserValor($retorno[$ano][$key]['quantidade_vendida']) : '';
                    $tabela[$key]['valor_ano_anterior'] = ($retorno[$ano][$key]['valor'] > 0) ? parserValor($retorno[$ano][$key]['valor']) : '';
                }

                $total_geral['preco_medio_anterior'] = ($total[$ano]['quantidade_vendida'] > 0) ? parserValor($total[$ano]['valor'] / $total[$ano]['quantidade_vendida']) : '';
                $total_geral['quantidade_vendida_anterior'] = ($total[$ano]['quantidade_vendida'] > 0) ? parserValor($total[$ano]['quantidade_vendida']) : '';
                $total_geral['valor_anterior'] = ($total[$ano]['valor'] > 0) ? parserValor($total[$ano]['valor']) : '';
                $total_geral['percentual_anterior'] = '';
            }

        }

        return view('programs.estatisticas_vendas.modal.grupo')->with(['retorno' => $tabela,'total' => $total_geral,'head' => $heads]);
    }

    public function modalMarca(Request $request){
        ini_set('memory_limit','1024M');
        set_time_limit(300);
        $filter = $request->only(['filters', 'total']);

        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

            
        $data_inicial_busca = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d 00:00:00');
        $data_final_busca = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d 00:00:00');

        $data_inicial_anterior = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->subYear()->format('Y-m-d 00:00:00');
        $data_final_anterior = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->subYear()->format('Y-m-d 00:00:00');

        $movimentacao_atual = Movimentacao::query()
        ->whereBetween('data_movimentacao',[$data_inicial_busca,$data_final_busca])
        ->whereNotIn('cliente_codigo',$this->clienteExcluir())
        //->with(['faturamentoNasajon'])
        ->with(['faturamento'])
        ->WhereIn('cfop',$this->cfop_vendas);

        $movimentacao_anterior = Movimentacao::query()
        ->whereBetween('data_movimentacao',[$data_inicial_anterior,$data_final_anterior])
        ->whereNotIn('cliente_codigo',$this->clienteExcluir())
        ->WhereIn('cfop',$this->cfop_vendas);

        if($filter['total'] == 'false' && !empty($fields['estabelecimento']) || $filter['total'] == 'true' && !empty($fields['estabelecimento'])){
            $movimentacao_atual->where('estabelecimento',$fields['estabelecimento']);
            $movimentacao_anterior->where('estabelecimento',$fields['estabelecimento']);
        }

        if(!empty($fields['marca']) ||!empty($fields['grupo']) || !empty($fields['linha'])){
            $movimentacao_atual->whereHas('produto', function($query) use ($fields){
                if(!empty($fields['marca'])){
                    $query->where("marca", '=' , $fields['marca']);
                }
                if(!empty($fields['linha'])){
                    $query->where("linha", '=', $fields['linha']);
                }
                if(!empty($fields['grupo'])){
                    $query->where("grupo", '=' , $fields['grupo']);
                }
               
            });

            $movimentacao_anterior->whereHas('produto', function($query) use ($fields){
                if(!empty($fields['marca'])){
                    $query->where("marca", '=' , $fields['marca']);
                }
                if(!empty($fields['linha'])){
                    $query->where("linha", '=', $fields['linha']);
                }
                if(!empty($fields['grupo'])){
                    $query->where("grupo", '=' , $fields['grupo']);
                }
               
            });
        }

        $movimentacao_atual->select('marca',DB::raw('Extract (Year from data_movimentacao) as ano, sum("quantidade") as quantidade, sum((quantidade * preco)::numeric(15, 2)) as valor,  sum(frete) as frete, sum(ipi) as ipi, sum(seguro) as seguro, sum(desconto) as desconto, sum(preco) as preco, sum(preco_prepago) as preco_prepago, documento, estabelecimento, cliente_codigo'))
        ->groupBy('marca','cfop', DB::raw('Extract (Year from data_movimentacao)'), 'documento', 'estabelecimento', 'cliente_codigo');
        $movimentacao_atual = $movimentacao_atual->get();

        $movimentacao_anterior->select('marca',DB::raw('Extract (Year from data_movimentacao) as ano, sum("quantidade") as quantidade, sum((quantidade * preco)::numeric(15, 2)) as valor,  sum(frete) as frete, sum(ipi) as ipi, sum(seguro) as seguro, sum(desconto) as desconto, sum(preco) as preco, sum(preco_prepago) as preco_prepago'))
        ->groupBy('marca','cfop', DB::raw('Extract (Year from data_movimentacao)'));
        $movimentacao_anterior = $movimentacao_anterior->get();
        
        $total = [];
        $retorno = [];
        $anos = [
            Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y'),
            Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->subYear()->format('Y')
        ];

        if(!empty($movimentacao_atual)){
            foreach($movimentacao_atual as $movimento){
                //if(!empty($movimento->faturamentoNasajon)){
                if(!empty($movimento->faturamento)){
                    if(!isset($retorno[$movimento->ano][$movimento->marca])){
                        $retorno[$movimento->ano][$movimento->marca] = [
                            'quantidade_vendida' => 0,
                            'valor' => 0,
                            'preco_medio' => 0,
                            'percentual' => 0,
                        ];
                    }

                    $retorno[$movimento->ano][$movimento->marca]['quantidade_vendida'] += $movimento->quantidade;
                    $retorno[$movimento->ano][$movimento->marca]['valor'] += ($movimento->valor + $movimento->frete + $movimento->ipi + $movimento->seguro + $movimento->preco_prepago) - $movimento->desconto;

                    if(!isset($total[$movimento->ano])){
                        $total[$movimento->ano] = [
                            'quantidade_vendida' => 0,
                            'valor' => 0,
                            'preco_medio' => 0,
                            'percentual' => 0,
                        ];
                    }

                    $total[$movimento->ano]['quantidade_vendida'] += $movimento->quantidade;
                    $total[$movimento->ano]['valor'] += ($movimento->valor + $movimento->frete + $movimento->ipi + $movimento->seguro + $movimento->preco_prepago) - $movimento->desconto;
                }
            }
        }else{
            $retorno[$anos[0]] = [''];
        }

        unset($movimentacao_atual);
        
        if(!empty($movimentacao_anterior)){
            foreach($movimentacao_anterior as $movimento_anterior){
                if(!isset($retorno[$movimento_anterior->ano][$movimento_anterior->marca])){
                    $retorno[$movimento_anterior->ano][$movimento_anterior->marca] = [
                        'quantidade_vendida' => 0,
                        'valor' => 0,
                        'preco_medio' => 0,
                        'percentual' => 0,
                    ];
                }

                $retorno[$movimento_anterior->ano][$movimento_anterior->marca]['quantidade_vendida'] += $movimento_anterior->quantidade;
                $retorno[$movimento_anterior->ano][$movimento_anterior->marca]['valor'] += (($movimento_anterior->valor + $movimento_anterior->frete + $movimento_anterior->ipi + $movimento_anterior->seguro + $movimento_anterior->preco_prepago) - $movimento_anterior->desconto);

                if(!isset($total[$movimento_anterior->ano])){
                    $total[$movimento_anterior->ano] = [
                        'quantidade_vendida' => 0,
                        'valor' => 0,
                        'preco_medio' => 0,
                        'percentual' => 0,
                    ];
                }

                $total[$movimento_anterior->ano]['quantidade_vendida'] += $movimento_anterior->quantidade;
                $total[$movimento_anterior->ano]['valor'] += (($movimento_anterior->valor + $movimento_anterior->frete + $movimento_anterior->ipi + $movimento_anterior->seguro + $movimento_anterior->preco_prepago) - $movimento_anterior->desconto);
            }
        }else{
            $retorno[$anos[1]] = [''];
        }

        unset($movimentacao_anterior);
        $heads = $anos;
        $total_geral = [
            'quantidade_vendida_busca' => '',
            'valor_busca' => '',
            'preco_medio_busca' => '',
            'percentual_busca' => '',
            'quantidade_vendida_anterior' => '',
            'valor_anterior' => '',
            'preco_medio_anterior' => '',
            'percentual_anterior' => '',
        ];
        $tabela = [];

        foreach($retorno as $ano => $value){
            $percentual = 0;

            if($anos[0] == $ano){
                foreach($value as $key => $dados){
                    if(!isset($tabela[$key])){
                        $tabela[$key] = [
                            'marca' =>$key,
                            'valor_ano_busca' => '',
                            'percentual_ano_busca' => '',
                            'quantidade_vendida_ano_busca' => '',
                            'preco_medio_ano_busca' => '',
                            'ano_busca' => $ano,

                            'valor_ano_anterior' => '',
                            'percentual_ano_anterior' => '',
                            'quantidade_vendida_ano_anterior' => '',
                            'preco_medio_ano_anterior' => '',
                            'ano_anterior' => '',
                        ];
                    }

                    $total_ano = $total[$ano]['valor'];
                    $percentual = ($total_ano > 0) ? $retorno[$ano][$key]['valor']/$total_ano*100 : '';

                    $tabela[$key]['preco_medio_ano_busca'] = ($retorno[$ano][$key]['quantidade_vendida'] > 0) ? parserValor($retorno[$ano][$key]['valor'] / $retorno[$ano][$key]['quantidade_vendida']) : '';
                    $tabela[$key]['percentual_ano_busca'] = ($percentual >= 0.01) ? parserValor($percentual) : '';
                    $tabela[$key]['quantidade_vendida_ano_busca'] = ($retorno[$ano][$key]['quantidade_vendida'] > 0) ? parserValor($retorno[$ano][$key]['quantidade_vendida']) : '';
                    $tabela[$key]['valor_ano_busca'] = ($retorno[$ano][$key]['valor'] > 0) ? parserValor($retorno[$ano][$key]['valor']) : '';
                }

                $total_geral['preco_medio_busca'] = ($total[$ano]['quantidade_vendida'] > 0) ? parserValor($total[$ano]['valor'] / $total[$ano]['quantidade_vendida']) : '';
                $total_geral['quantidade_vendida_busca'] = ($total[$ano]['quantidade_vendida'] > 0) ? parserValor($total[$ano]['quantidade_vendida']) : '';
                $total_geral['valor_busca'] = ($total[$ano]['valor'] > 0) ? parserValor($total[$ano]['valor']) : '';
                $total_geral['percentual_busca'] = '';
                
            }else{
                foreach($value as $key => $dados){
                    if(!isset($tabela[$key])){
                        $tabela[$key] = [
                            'marca' =>$key,
                            'valor_ano_busca' => '',
                            'percentual_ano_busca' => '',
                            'quantidade_vendida_ano_busca' => '',
                            'preco_medio_ano_busca' => '',
                            'ano_busca' => '',

                            'valor_ano_anterior' => '',
                            'percentual_ano_anterior' => '',
                            'quantidade_vendida_ano_anterior' => '',
                            'preco_medio_ano_anterior' => '',
                            'ano_anterior' => $ano,
                        ];
                    }

                    $total_ano = $total[$ano]['valor'];
                    $percentual = ($total_ano > 0) ? $retorno[$ano][$key]['valor']/$total_ano*100  : '';

                    $tabela[$key]['preco_medio_ano_anterior'] = ($retorno[$ano][$key]['quantidade_vendida'] > 0) ? parserValor($retorno[$ano][$key]['valor'] / $retorno[$ano][$key]['quantidade_vendida']) : '';
                    $tabela[$key]['percentual_ano_anterior'] = ($percentual >= 0.01) ? parserValor($percentual) : '';
                    $tabela[$key]['quantidade_vendida_ano_anterior'] = ($retorno[$ano][$key]['quantidade_vendida'] > 0) ? parserValor($retorno[$ano][$key]['quantidade_vendida']) : '';
                    $tabela[$key]['valor_ano_anterior'] = ($retorno[$ano][$key]['valor'] > 0) ? parserValor($retorno[$ano][$key]['valor']) : '';
                }

                $total_geral['preco_medio_anterior'] = ($total[$ano]['quantidade_vendida'] > 0) ? parserValor($total[$ano]['valor'] / $total[$ano]['quantidade_vendida']) : '';
                $total_geral['quantidade_vendida_anterior'] = ($total[$ano]['quantidade_vendida'] > 0) ? parserValor($total[$ano]['quantidade_vendida']) : '';
                $total_geral['valor_anterior'] = ($total[$ano]['valor'] > 0) ? parserValor($total[$ano]['valor']) : '';
                $total_geral['percentual_anterior'] = '';
            }

        }

        return view('programs.estatisticas_vendas.modal.marca')->with(['retorno' => $tabela,'total' => $total_geral,'head' => $heads]);
    }
}
