<?php

namespace App\Http\Controllers;

use App\ComprasNasajon;
use App\FornecedorNasajon;
use App\Http\Requests\AnalisePerformanceFornecedorConsultaRequest;
use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalisePerformanceFornecedorController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AnalisePerformanceFornecedor") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\AnalisePerformanceFornecedor');

        return view('programs.analise_performance_fornecedor.index');
    }

    public function filtro(AnalisePerformanceFornecedorConsultaRequest $request){
        set_time_limit(600);
        ini_set('memory_limit','1024M');
        $campo = $request->only('fornecedor', 'data_inicio', 'data_fim');

        $ComprasNasajonObj = ComprasNasajon::with('fornecedor')
        ->select('fornecedor_id', 'previsao_entrega', 'fornecedor_nome','numero_pedido', 'situacao','id_nota', DB::raw('sum(preco_compra) as total_saldo, count(distinct numero_pedido) as pedido_quantidade, (select max(data_entrega) from integracoes.vw_produtos_compras compras_data where compras_data.id_nota = integracoes.vw_produtos_compras.id_nota) as data_entrega'))
         ->whereIn('situacao',['Aberto','Aguardando Documento','Parcialmente Liquidado','Liquidado','Cancelado'])
        ->distinct()
        ->groupBy('fornecedor_id', 'fornecedor_nome', 'previsao_entrega', 'situacao' , 'id_nota','estabelecimento','numero_pedido');

        $data_inicio = Carbon::createFromFormat('d/m/Y', $campo['data_inicio'])->format('Y-m-d');
        $data_fim = Carbon::createFromFormat('d/m/Y', $campo['data_fim'])->format('Y-m-d');
        
        $ComprasNasajonObj->whereBetween('data_compra', [$data_inicio, $data_fim]);

        if(!empty($campo['fornecedor'])){
            $FornecedorNasajonObj = FornecedorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj_cpf))'), 'ilike','%' . trim($campo['fornecedor']) . '%')->get();
        
            $ComprasNasajonObj->whereIn('fornecedor_id', $FornecedorNasajonObj->pluck('id'));
        }

        $ComprasNasajon = $ComprasNasajonObj->get();

        $array_pedidos_cancelados = [];
        $pedidos_cancelados = $ComprasNasajon->where('situacao','Cancelado')->unique('numero_pedido');

        $pedidos_cancelados->each(function($query) use (&$array_pedidos_cancelados){
            $array_pedidos_cancelados[] = $query->numero_pedido;
        });

        unset($pedidos_cancelados);
        $ComprasNasajon = $ComprasNasajon->whereNotIn('numero_pedido',$array_pedidos_cancelados);

        $total = [
            'valor_aberto_prazo' => 0,
            'valor_aberto_atraso' => 0,
            'quantidade_aberto_prazo' => 0,
            'quantidade_aberto_atraso' => 0,
            'valor_entregue_prazo' => 0,
            'valor_entregue_atraso' => 0,
            'quantidade_entregue_prazo' => 0,
            'quantidade_entregue_atraso' => 0,
            'quantidade_total_aberto' => 0,
            'quantidade_total_entregue' => 0,
            'percentual_quantidade_aberto_prazo' => 0,
            'percentual_quantidade_aberto_atraso' => 0,
            'percentual_quantidade_entregue_prazo' => 0,
            'percentual_quantidade_entregue_atraso' => 0,
            'filters' => encrypt([
                'fornecedor' => $campo['fornecedor'],
                'data_inicio' => $data_inicio,
                'data_fim' => $data_fim,
                'fornecedor_id' => !empty($FornecedorNasajonObj) ? $FornecedorNasajonObj->pluck('id')->toArray() : ''
            ])
        ];

        foreach($ComprasNasajon->chunk(500) as $chunk){
            foreach($chunk as $pedido){
                $hoje = Carbon::now()->format("Y-m-d"); 
                $previsao_entrega = Carbon::parse($pedido->previsao_entrega);
                $fornecedor = isset($pedido->fornecedor->nome) ? $pedido->fornecedor->nome.' - '.$pedido->fornecedor->cnpj_cpf : $pedido->fornecedor_nome;

                $valor_aberto_prazo = $previsao_entrega->gte($hoje) && in_array($pedido->situacao,['Aberto', 'Parcialmente Liquidado','Aguardando Documento'])  ? $pedido->total_saldo : 0;
                $valor_aberto_atraso = $previsao_entrega->lt($hoje) && in_array($pedido->situacao,['Aberto', 'Parcialmente Liquidado', 'Aguardando Documento'])  ? $pedido->total_saldo : 0;
                $quantidade_aberto_prazo = $previsao_entrega->gte($hoje) && in_array($pedido->situacao,['Aberto','Parcialmente Liquidado', 'Aguardando Documento'])  ? 1 : 0;
                $quantidade_aberto_atraso = $previsao_entrega->lt($hoje) && in_array($pedido->situacao,['Aberto','Parcialmente Liquidado', 'Aguardando Documento']) ? $pedido->pedido_quantidade : 0;
                $quantidade_total_aberto =  in_array($pedido->situacao,['Aberto','Parcialmente Liquidado', 'Aguardando Documento']) ? $pedido->pedido_quantidade : 0;

                $data_entrega = Carbon::parse($pedido->data_entrega);

                $valor_entregue_prazo = $previsao_entrega->gte($data_entrega) && in_array($pedido->situacao,['Liquidado','Parcialmente Liquidado']) ? $pedido->total_saldo : 0;
                $valor_entregue_atraso = $previsao_entrega->lt($data_entrega) && in_array($pedido->situacao,['Liquidado','Parcialmente Liquidado']) ? $pedido->total_saldo : 0;
                $quantidade_entregue_prazo = $previsao_entrega->gte($data_entrega) && in_array($pedido->situacao,['Liquidado','Parcialmente Liquidado']) ? $pedido->pedido_quantidade : 0;
                $quantidade_entregue_atraso = $previsao_entrega->lt($data_entrega) && in_array($pedido->situacao,['Liquidado','Parcialmente Liquidado']) ? $pedido->pedido_quantidade : 0;
                $quantidade_total_entregue = $pedido->pedido_quantidade;

                if(!isset($saida[$fornecedor])){
                    $saida[$fornecedor] = [
                        'fornecedor' => $fornecedor,
                        'valor_aberto_prazo' => 0,
                        'valor_aberto_atraso' => 0,
                        'quantidade_aberto_prazo' => 0,
                        'quantidade_aberto_atraso' => 0,
                        'valor_entregue_prazo' => 0,
                        'valor_entregue_atraso' => 0,
                        'quantidade_entregue_prazo' => 0,
                        'quantidade_entregue_atraso' => 0, 
                        'quantidade_total_aberto' => 0,
                        'quantidade_total_entregue' => 0,
                        'percentual_quantidade_aberto_prazo' => 0,
                        'percentual_quantidade_aberto_atraso' => 0,
                        'percentual_quantidade_entregue_prazo' => 0,
                        'percentual_quantidade_entregue_atraso' => 0,
                        'filters' => encrypt([
                            'fornecedor_id' =>  $pedido->fornecedor_id,
                            'data_inicio' => $data_inicio,
                            'data_fim' => $data_fim
                        ])
                    ];
                }

                if(in_array($pedido->situacao,['Aberto','Aguardando Documento','Parcialmente Liquidado'])){
                    $saida[$fornecedor]['valor_aberto_prazo'] += $valor_aberto_prazo;
                    $saida[$fornecedor]['valor_aberto_atraso'] += $valor_aberto_atraso;
                    $saida[$fornecedor]['quantidade_aberto_prazo'] += $quantidade_aberto_prazo;
                    $saida[$fornecedor]['quantidade_aberto_atraso'] += $quantidade_aberto_atraso;
                    $saida[$fornecedor]['quantidade_total_aberto'] += $quantidade_total_aberto;
                    $saida[$fornecedor]['percentual_quantidade_aberto_prazo'] = $saida[$fornecedor]['quantidade_total_aberto'] > 0 ? (($saida[$fornecedor]['quantidade_aberto_prazo']*100)/$saida[$fornecedor]['quantidade_total_aberto']):0;
                    $saida[$fornecedor]['percentual_quantidade_aberto_atraso'] = $saida[$fornecedor]['quantidade_total_aberto'] > 0 ? (($saida[$fornecedor]['quantidade_aberto_atraso']*100)/$saida[$fornecedor]['quantidade_total_aberto']):0;
                    $total['valor_aberto_prazo'] += $valor_aberto_prazo;
                    $total['valor_aberto_atraso'] += $valor_aberto_atraso;
                    $total['quantidade_aberto_prazo'] += $quantidade_aberto_prazo;
                    $total['quantidade_aberto_atraso'] += $quantidade_aberto_atraso;
                    $total['quantidade_total_aberto'] += $quantidade_total_aberto;
                    $total['percentual_quantidade_aberto_prazo'] =  $total['quantidade_total_aberto'] > 0 ? (($total['quantidade_aberto_prazo']*100)/$total['quantidade_total_aberto']):0;
                    $total['percentual_quantidade_aberto_atraso'] =  $total['quantidade_total_aberto'] > 0 ? (($total['quantidade_aberto_atraso']*100)/$total['quantidade_total_aberto']):0;
                }
                if(in_array($pedido->situacao,['Liquidado','Parcialmente Liquidado'])){
                    $saida[$fornecedor]['valor_entregue_prazo'] += $valor_entregue_prazo;
                    $saida[$fornecedor]['valor_entregue_atraso'] += $valor_entregue_atraso;
                    $saida[$fornecedor]['quantidade_entregue_prazo'] += $quantidade_entregue_prazo;
                    $saida[$fornecedor]['quantidade_entregue_atraso'] += $quantidade_entregue_atraso;
                    $saida[$fornecedor]['quantidade_total_entregue'] += $quantidade_total_entregue;
                    $saida[$fornecedor]['percentual_quantidade_entregue_prazo'] = $saida[$fornecedor]['quantidade_total_entregue'] > 0 ? (($saida[$fornecedor]['quantidade_entregue_prazo']*100)/$saida[$fornecedor]['quantidade_total_entregue']):0;
                    $saida[$fornecedor]['percentual_quantidade_entregue_atraso'] = $saida[$fornecedor]['quantidade_total_entregue'] > 0 ? (($saida[$fornecedor]['quantidade_entregue_atraso']*100)/$saida[$fornecedor]['quantidade_total_entregue']):0;
                    $total['valor_entregue_prazo'] += $valor_entregue_prazo;
                    $total['valor_entregue_atraso'] += $valor_entregue_atraso;
                    $total['quantidade_entregue_prazo'] += $quantidade_entregue_prazo;
                    $total['quantidade_entregue_atraso'] += $quantidade_entregue_atraso;
                    $total['quantidade_total_entregue'] += $quantidade_total_entregue;
                    $total['percentual_quantidade_entregue_prazo'] =  $total['quantidade_total_entregue'] > 0 ? (($total['quantidade_entregue_prazo']*100)/$total['quantidade_total_entregue']):0;
                    $total['percentual_quantidade_entregue_atraso'] =  $total['quantidade_total_entregue'] > 0 ? (($total['quantidade_entregue_atraso']*100)/$total['quantidade_total_entregue']):0;
                }
                
            }
        }

        if(!isset($saida)){
            return response()->json([
                'status' => 'sucess',
                'message' => '',
                'error' => '', 
                'response' => ['pedidos' => null, 'total' => 'null'],
            ], 200);    
        }

        foreach($saida as $key => $value){
            $saida[$key]['valor_aberto_prazo'] = $saida[$key]['valor_aberto_prazo'] > 0 ? parserValor($saida[$key]['valor_aberto_prazo']) : ''; 
            $saida[$key]['valor_aberto_atraso'] = $saida[$key]['valor_aberto_atraso'] > 0 ? parserValor($saida[$key]['valor_aberto_atraso']) : ''; 
            $saida[$key]['valor_entregue_prazo'] = $saida[$key]['valor_entregue_prazo'] > 0 ? parserValor($saida[$key]['valor_entregue_prazo']) : '';
            $saida[$key]['valor_entregue_atraso'] = $saida[$key]['valor_entregue_atraso'] > 0 ? parserValor($saida[$key]['valor_entregue_atraso']) : '';
            $saida[$key]['quantidade_entregue_prazo'] = $saida[$key]['quantidade_entregue_prazo'] > 0 ? $saida[$key]['quantidade_entregue_prazo'] : '';
            $saida[$key]['quantidade_entregue_atraso'] = $saida[$key]['quantidade_entregue_atraso'] > 0 ? $saida[$key]['quantidade_entregue_atraso'] : '';
            $saida[$key]['quantidade_aberto_prazo'] = $saida[$key]['quantidade_aberto_prazo'] > 0 ? $saida[$key]['quantidade_aberto_prazo'] : '';
            $saida[$key]['quantidade_aberto_atraso'] = $saida[$key]['quantidade_aberto_atraso'] > 0 ? $saida[$key]['quantidade_aberto_atraso'] : '';
            $saida[$key]['percentual_quantidade_aberto_prazo'] = $saida[$key]['percentual_quantidade_aberto_prazo'] > 0 ? parserQtd($saida[$key]['percentual_quantidade_aberto_prazo']).'%' : '';
            $saida[$key]['percentual_quantidade_aberto_atraso'] = $saida[$key]['percentual_quantidade_aberto_atraso'] > 0 ? parserQtd($saida[$key]['percentual_quantidade_aberto_atraso']).'%' : '';
            $saida[$key]['percentual_quantidade_entregue_prazo'] = $saida[$key]['percentual_quantidade_entregue_prazo'] > 0 ? parserQtd($saida[$key]['percentual_quantidade_entregue_prazo']).'%' : '';
            $saida[$key]['percentual_quantidade_entregue_atraso'] = $saida[$key]['percentual_quantidade_entregue_atraso'] > 0 ? parserQtd($saida[$key]['percentual_quantidade_entregue_atraso']).'%' : '';
        }

        $total['valor_aberto_prazo'] = $total['valor_aberto_prazo'] > 0 ? parserValor($total['valor_aberto_prazo']):'';
        $total['valor_aberto_atraso'] = $total['valor_aberto_atraso'] > 0 ? parserValor($total['valor_aberto_atraso']):'';
        $total['valor_entregue_prazo'] = $total['valor_entregue_prazo'] > 0 ? parserValor($total['valor_entregue_prazo']):'';
        $total['valor_entregue_atraso'] = $total['valor_entregue_atraso'] > 0 ? parserValor($total['valor_entregue_atraso']):'';
        $total['quantidade_aberto_prazo'] = $total['quantidade_aberto_prazo'] > 0 ? $total['quantidade_aberto_prazo'] : '';
        $total['quantidade_aberto_atraso'] = $total['quantidade_aberto_atraso'] > 0 ? $total['quantidade_aberto_atraso'] : '';
        $total['quantidade_entregue_prazo'] = $total['quantidade_entregue_prazo'] > 0 ? $total['quantidade_entregue_prazo'] : '';
        $total['quantidade_entregue_atraso'] = $total['quantidade_entregue_atraso'] > 0 ? $total['quantidade_entregue_atraso'] : '';
        $total['percentual_quantidade_aberto_prazo'] = $total['percentual_quantidade_aberto_prazo'] > 0 ? parserQtd($total['percentual_quantidade_aberto_prazo']).'%':'';
        $total['percentual_quantidade_aberto_atraso'] = $total['percentual_quantidade_aberto_atraso'] > 0 ? parserQtd($total['percentual_quantidade_aberto_atraso']).'%':'';
        $total['percentual_quantidade_entregue_prazo'] = $total['percentual_quantidade_entregue_prazo'] > 0 ? parserQtd($total['percentual_quantidade_entregue_prazo']).'%':'';
        $total['percentual_quantidade_entregue_atraso'] = $total['percentual_quantidade_entregue_atraso'] > 0 ? parserQtd($total['percentual_quantidade_entregue_atraso']).'%':'';
        
        $linhas = [
            'status' => 'sucess',
            'message' => '',
            'error' => [],
            'response' => [
                'pedidos' => $saida,
                'total' => $total
            ] 
        ];
        return response()->json($linhas, 200);
    }

    public function modalPedidosAbertosPrazo(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters', 'total']);

        try{
            $fields = decrypt($filter['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return, 422);
        }

        $hoje = Carbon::now()->format("Y-m-d"); 

        $ComprasNasajonObj = ComprasNasajon::with('fornecedor')
        ->select('numero_pedido', 'situacao', 'fornecedor_id','estabelecimento', 'previsao_entrega','preco_compra_unitario', 'proforma', 'fornecedor_nome', 'id_nota', 
        DB::raw("sum(preco_compra_unitario * quantidade_restante) as saldo, sum(preco_compra) as preco_compra"), 'proforma', 'fornecedor_nome', DB::raw("sum(quantidade) as quantidade, sum(quantidade_restante) as quantidade_restante, (select max(data_entrega) from integracoes.vw_produtos_compras compras_data where compras_data.id_nota = integracoes.vw_produtos_compras.id_nota) as data_entrega"))
        ->whereIn('situacao',['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado','Cancelado'])
        ->whereBetween('data_compra', [$fields['data_inicio'], $fields['data_fim']])
        ->where('previsao_entrega','>=',$hoje)
        ->distinct()
        ->groupBy('numero_pedido','situacao', 'fornecedor_id', 'fornecedor_nome', 'id_nota','estabelecimento', 'previsao_entrega', 'preco_compra_unitario', 'proforma');
        
        if($filter['total'] != 'true' || !empty($fields['fornecedor_id'])){
            if(is_array($fields['fornecedor_id'])){
                $ComprasNasajonObj->whereIn('fornecedor_id', $fields['fornecedor_id']);
            }else{
                $ComprasNasajonObj->where('fornecedor_id', $fields['fornecedor_id']);
            }
        }

        $ComprasNasajon = $ComprasNasajonObj->get();

        $array_pedidos_cancelados = [];
        $pedidos_cancelados = $ComprasNasajon->where('situacao','Cancelado')->unique('numero_pedido');

        $pedidos_cancelados->each(function($query) use (&$array_pedidos_cancelados){
            $array_pedidos_cancelados[] = $query->numero_pedido;
        });

        unset($pedidos_cancelados);
        $ComprasNasajon = $ComprasNasajon->whereNotIn('numero_pedido',$array_pedidos_cancelados);

        $estabelecimentos = returnEmpresasNasajonView();

        $saida = [];
        $total = [
            'pedido_quantidade' => 0,
            'quantidade_compra' => 0,
            'saldo' => 0,
            'valor' => 0,
            'valor_compra' => 0
        ];

        foreach($ComprasNasajon as $pedido){
            $data = Carbon::parse($pedido->previsao_entrega);
            if($data->lt($hoje)){
                continue;
            }

            $quantidade_compra = $pedido->quantidade;
            $saldo = $pedido->quantidade_restante;
            $valor_saldo = $pedido->saldo;
            $valor_comprado = $pedido->preco_compra;

            if(!isset($saida[$pedido->numero_pedido.$pedido->estabelecimento])){
                $saida[$pedido->numero_pedido.$pedido->estabelecimento] = [
                    'estabelecimento' => $estabelecimentos[(integer)$pedido->estabelecimento],
                    'pedido' => $pedido->numero_pedido,
                    'proforma' => $pedido->proforma,
                    'id' => encrypt($pedido->id_nota),
                    'fornecedor' => isset($pedido->fornecedor->nome) ? $pedido->fornecedor->nome.' - '.$pedido->fornecedor->cnpj_cpf : $pedido->fornecedor_nome,
                    'previsao_recebimento' => parserData($pedido->previsao_entrega),
                    'data_recebimento' => '',
                    'quantidade_compra' => 0,
                    'saldo' =>  0,
                    'valor' =>  0,
                    'valor_compra' => 0
                ];
            }

            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['quantidade_compra'] += $quantidade_compra;
            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['saldo'] += $saldo;
            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['valor'] += $valor_saldo; 
            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['valor_compra'] += $valor_comprado; 

            $total['quantidade_compra'] += $quantidade_compra;
            $total['saldo'] += $saldo;
            $total['valor_compra'] += $valor_comprado;
            $total['valor'] += $valor_saldo;
        }

        foreach($saida as $key => $valor){
            $saida[$key]['quantidade_compra'] = ($saida[$key]['quantidade_compra'] > 0) ? parserQtd($saida[$key]['quantidade_compra']) : '';
            $saida[$key]['saldo'] = ($saida[$key]['saldo'] > 0) ? parserValor($saida[$key]['saldo']) : '';
            $saida[$key]['valor'] = ($saida[$key]['valor'] > 0) ? parserValor($saida[$key]['valor']) : '';
            $saida[$key]['valor_compra'] = ($saida[$key]['valor_compra'] > 0) ? parserValor($saida[$key]['valor_compra']) : '';
        }

        $total['pedido_quantidade'] = count($saida);
        $total['quantidade_compra'] = ($total['quantidade_compra'] > 0) ? parserQtd($total['quantidade_compra']) : '';
        $total['saldo'] = ($total['saldo'] > 0) ? parserValor($total['saldo']) : '';
        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        $total['valor_compra'] = ($total['valor_compra'] > 0) ? parserValor($total['valor_compra']) : '';

        return view('programs.analise_performance_fornecedor.modal.pedidos')->with(['dados' => $saida, 'total' => $total]);
    }

    public function modalPedidosAbertosAtraso(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters', 'total']);

        try{
            $fields = decrypt($filter['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return, 422);
        }

        $hoje = Carbon::now()->format("Y-m-d"); 

        $ComprasNasajonObj = ComprasNasajon::with('fornecedor')
        ->select('numero_pedido','situacao', 'fornecedor_id','estabelecimento', 'previsao_entrega',
        DB::raw("sum(preco_compra_unitario * quantidade_restante) as saldo, sum(preco_compra) as preco_compra"), 'proforma', 'fornecedor_nome', DB::raw("sum(quantidade) as quantidade, sum(quantidade_restante) as quantidade_restante, (select max(data_entrega) from integracoes.vw_produtos_compras compras_data where compras_data.id_nota = integracoes.vw_produtos_compras.id_nota) as data_entrega"))
        ->whereIn('situacao',['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado','Cancelado'])
        ->whereBetween('data_compra', [$fields['data_inicio'], $fields['data_fim']])
        ->where('previsao_entrega','<',$hoje)
        ->distinct()
        ->groupBy('numero_pedido', 'situacao','fornecedor_id', 'fornecedor_nome', 'estabelecimento', 'id_nota', 'previsao_entrega', 'proforma');

        if($filter['total'] != 'true' || !empty($fields['fornecedor_id'])){
            if(is_array($fields['fornecedor_id'])){
                $ComprasNasajonObj->whereIn('fornecedor_id', $fields['fornecedor_id']);
            }else{
                $ComprasNasajonObj->where('fornecedor_id', $fields['fornecedor_id']);
            }
        }

        $ComprasNasajon = $ComprasNasajonObj->get();
        
        $array_pedidos_cancelados = [];
        $pedidos_cancelados = $ComprasNasajon->where('situacao','Cancelado')->unique('numero_pedido');

        $pedidos_cancelados->each(function($query) use (&$array_pedidos_cancelados){
            $array_pedidos_cancelados[] = $query->numero_pedido;
        });

        unset($pedidos_cancelados);
        $ComprasNasajon = $ComprasNasajon->whereNotIn('numero_pedido',$array_pedidos_cancelados);

        $estabelecimentos = returnEmpresasNasajonView();

        $total = [
            'pedido_quantidade' => 0,
            'quantidade_compra' => 0,
            'saldo' => 0,
            'valor' => 0,
            'valor_compra' => 0
        ];

        foreach($ComprasNasajon as $pedido){
            $data = Carbon::parse($pedido->previsao_entrega);
            if($data->lt($hoje)){
                continue;
            }
            $quantidade_compra = $pedido->quantidade;
            $saldo = $pedido->quantidade_restante;
            $valor_saldo = $pedido->saldo;
            $valor_comprado = $pedido->preco_compra;

            if(!isset($saida[$pedido->numero_pedido.$pedido->estabelecimento])){
                $saida[$pedido->numero_pedido.$pedido->estabelecimento] = [
                    'estabelecimento' => $estabelecimentos[(integer)$pedido->estabelecimento],
                    'pedido' => $pedido->numero_pedido,
                    'proforma' => $pedido->proforma,
                    'id' => encrypt($pedido->id_nota),
                    'fornecedor' => isset($pedido->fornecedor->nome) ? $pedido->fornecedor->nome.' - '.$pedido->fornecedor->cnpj_cpf : $pedido->fornecedor_nome,
                    'previsao_recebimento' => parserData($pedido->previsao_entrega),
                    'data_recebimento' => '',
                    'quantidade_compra' => 0,
                    'saldo' =>  0,
                    'valor' =>  0,
                    'valor_compra' => 0
                ];
            }

            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['quantidade_compra'] += $quantidade_compra;
            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['saldo'] += $saldo;
            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['valor'] += $valor_saldo; 
            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['valor_compra'] += $valor_comprado; 

            $total['quantidade_compra'] += $quantidade_compra;
            $total['saldo'] += $saldo;
            $total['valor_compra'] += $valor_comprado;
            $total['valor'] += $valor_saldo;
        }

        foreach($saida as $key => $valor){
            $saida[$key]['quantidade_compra'] = ($saida[$key]['quantidade_compra'] > 0) ? parserQtd($saida[$key]['quantidade_compra']) : '';
            $saida[$key]['saldo'] = ($saida[$key]['saldo'] > 0) ? parserValor($saida[$key]['saldo']) : '';
            $saida[$key]['valor'] = ($saida[$key]['valor'] > 0) ? parserValor($saida[$key]['valor']) : '';
            $saida[$key]['valor_compra'] = ($saida[$key]['valor_compra'] > 0) ? parserValor($saida[$key]['valor_compra']) : '';
        }

        $total['pedido_quantidade'] = count($saida);
        $total['quantidade_compra'] = ($total['quantidade_compra'] > 0) ? parserQtd($total['quantidade_compra']) : '';
        $total['saldo'] = ($total['saldo'] > 0) ? parserValor($total['saldo']) : '';
        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        $total['valor_compra'] = ($total['valor_compra'] > 0) ? parserValor($total['valor_compra']) : '';


        return view('programs.analise_performance_fornecedor.modal.pedidos')->with(['dados' => $saida, 'total' => $total]);

    }

    public function modalPedidosEntreguesPrazo(Request $request){
        set_time_limit(300);
        $filter = $request->only(['filters', 'total']);

        try{
            $fields = decrypt($filter['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return, 422);
        }

        $ComprasNasajonObj = ComprasNasajon::with('fornecedor')
        ->select('numero_pedido', 'fornecedor_id','estabelecimento', 'id_nota','previsao_entrega',
        DB::raw("sum(preco_compra_unitario * quantidade_restante) as saldo, sum(preco_compra) as preco_compra, sum(quantidade_restante) as quantidade_restante"), 'proforma', 'fornecedor_nome', DB::raw("sum(quantidade) as quantidade, (select max(data_entrega) from integracoes.vw_produtos_compras compras_data where compras_data.id_nota = integracoes.vw_produtos_compras.id_nota) as data_entrega"))
        ->whereIn('situacao', ['Liquidado', 'Parcialmente Liquidado'])
        ->whereBetween('data_compra', [$fields['data_inicio'], $fields['data_fim']])
        ->where('previsao_entrega', '>=', DB::raw('(select max(data_entrega) from integracoes.vw_produtos_compras compras_data where compras_data.id_nota = integracoes.vw_produtos_compras.id_nota)'))
        ->groupBy('numero_pedido', 'fornecedor_id', 'fornecedor_nome', 'id_nota', 'estabelecimento', 'previsao_entrega', 'proforma');

        
        if($filter['total'] != 'true' || !empty($fields['fornecedor_id'])){
            if(is_array($fields['fornecedor_id'])){
                $ComprasNasajonObj->whereIn('fornecedor_id', $fields['fornecedor_id']);
            }else{
                $ComprasNasajonObj->where('fornecedor_id', $fields['fornecedor_id']);
            }
        }
        
        $ComprasNasajon = $ComprasNasajonObj->get();

        $estabelecimentos = returnEmpresasNasajonView();

        $total = [
            'pedido_quantidade' => 0,
            'quantidade_compra' => 0,
            'saldo' => 0,
            'valor' => 0,
            'valor_compra' => 0
        ];

        foreach($ComprasNasajon as $pedido){
            $quantidade_compra = $pedido->quantidade;
            $saldo = $pedido->quantidade_restante;
            $valor_saldo = $pedido->saldo;
            $valor_comprado = $pedido->preco_compra;

            if(!isset($saida[$pedido->numero_pedido.$pedido->estabelecimento])){
                $saida[$pedido->numero_pedido.$pedido->estabelecimento] = [
                    'estabelecimento' => $estabelecimentos[(integer)$pedido->estabelecimento],
                    'pedido' => $pedido->numero_pedido,
                    'proforma' => $pedido->proforma,
                    'id' => encrypt($pedido->id_nota),
                    'fornecedor' => isset($pedido->fornecedor->nome) ? $pedido->fornecedor->nome.' - '.$pedido->fornecedor->cnpj_cpf : $pedido->fornecedor_nome,
                    'previsao_recebimento' => parserData($pedido->previsao_entrega),
                    'data_recebimento' => '',
                    'quantidade_compra' => 0,
                    'saldo' =>  0,
                    'valor' =>  0,
                    'valor_compra' => 0
                ];
            }

            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['quantidade_compra'] += $quantidade_compra;
            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['saldo'] += $saldo;
            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['valor'] += $valor_saldo; 
            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['valor_compra'] += $valor_comprado; 

            $total['quantidade_compra'] += $quantidade_compra;
            $total['saldo'] += $saldo;
            $total['valor_compra'] += $valor_comprado;
            $total['valor'] += $valor_saldo;
        }

        foreach($saida as $key => $valor){
            $saida[$key]['quantidade_compra'] = ($saida[$key]['quantidade_compra'] > 0) ? parserQtd($saida[$key]['quantidade_compra']) : '';
            $saida[$key]['saldo'] = ($saida[$key]['saldo'] > 0) ? parserValor($saida[$key]['saldo']) : '';
            $saida[$key]['valor'] = ($saida[$key]['valor'] > 0) ? parserValor($saida[$key]['valor']) : '';
            $saida[$key]['valor_compra'] = ($saida[$key]['valor_compra'] > 0) ? parserValor($saida[$key]['valor_compra']) : '';
        }

        $total['pedido_quantidade'] = count($saida);
        $total['quantidade_compra'] = ($total['quantidade_compra'] > 0) ? parserQtd($total['quantidade_compra']) : '';
        $total['saldo'] = ($total['saldo'] > 0) ? parserValor($total['saldo']) : '';
        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        $total['valor_compra'] = ($total['valor_compra'] > 0) ? parserValor($total['valor_compra']) : '';

        
        return view('programs.analise_performance_fornecedor.modal.pedidos')->with(['dados' => $saida, 'total' => $total]);
    }

    public function modalPedidosEntreguesAtraso(Request $request){
        set_time_limit(300);
        $filter = $request->only(['filters', 'total']);

        try{
            $fields = decrypt($filter['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return, 422);
        }

        $ComprasNasajonObj = ComprasNasajon::with('fornecedor')
        ->select('numero_pedido', 'fornecedor_id','estabelecimento', 'id_nota', 'previsao_entrega',
        DB::raw("sum(preco_compra_unitario * quantidade_restante) as saldo, sum(preco_compra) as preco_compra"), 'proforma', 'fornecedor_nome', DB::raw("sum(quantidade) as quantidade, sum(quantidade_restante) as quantidade_restante, (select max(data_entrega) from integracoes.vw_produtos_compras compras_data where compras_data.id_nota = integracoes.vw_produtos_compras.id_nota) as data_entrega"))
        ->whereIn('situacao', ['Liquidado', 'Parcialmente Liquidado'])
        ->whereBetween('data_compra', [$fields['data_inicio'], $fields['data_fim']])
        ->where('previsao_entrega', '<', DB::raw('(select max(data_entrega) from integracoes.vw_produtos_compras compras_data where compras_data.id_nota = integracoes.vw_produtos_compras.id_nota)'))
        ->groupBy('numero_pedido', 'fornecedor_id', 'fornecedor_nome', 'id_nota', 'estabelecimento', 'previsao_entrega', 'proforma');

        
        if($filter['total'] != 'true' || !empty($fields['fornecedor_id'])){
            if(is_array($fields['fornecedor_id'])){
                $ComprasNasajonObj->whereIn('fornecedor_id', $fields['fornecedor_id']);
            }else{
                $ComprasNasajonObj->where('fornecedor_id', $fields['fornecedor_id']);
            }
        }

        $ComprasNasajon = $ComprasNasajonObj->get();

        $estabelecimentos = returnEmpresasNasajonView();

        $total = [
            'pedido_quantidade' => 0,
            'quantidade_compra' => 0,
            'saldo' => 0,
            'valor' => 0,
            'valor_compra' => 0
        ];

        foreach($ComprasNasajon as $pedido){
            $quantidade_compra = $pedido->quantidade;
            $saldo = $pedido->quantidade_restante;
            $valor_saldo = $pedido->saldo;
            $valor_comprado = $pedido->preco_compra;

            if(!isset($saida[$pedido->numero_pedido.$pedido->estabelecimento])){
                $saida[$pedido->numero_pedido.$pedido->estabelecimento] = [
                    'estabelecimento' => $estabelecimentos[(integer)$pedido->estabelecimento],
                    'pedido' => $pedido->numero_pedido,
                    'proforma' => $pedido->proforma,
                    'id' => encrypt($pedido->id_nota),
                    'fornecedor' => isset($pedido->fornecedor->nome) ? $pedido->fornecedor->nome.' - '.$pedido->fornecedor->cnpj_cpf : $pedido->fornecedor_nome,
                    'previsao_recebimento' => parserData($pedido->previsao_entrega),
                    'data_recebimento' => '',
                    'quantidade_compra' => 0,
                    'saldo' =>  0,
                    'valor' =>  0,
                    'valor_compra' => 0
                ];
            }

            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['quantidade_compra'] += $quantidade_compra;
            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['saldo'] += $saldo;
            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['valor'] += $valor_saldo; 
            $saida[$pedido->numero_pedido.$pedido->estabelecimento]['valor_compra'] += $valor_comprado; 

            $total['quantidade_compra'] += $quantidade_compra;
            $total['saldo'] += $saldo;
            $total['valor_compra'] += $valor_comprado;
            $total['valor'] += $valor_saldo;
        }

        foreach($saida as $key => $valor){
            $saida[$key]['quantidade_compra'] = ($saida[$key]['quantidade_compra'] > 0) ? parserQtd($saida[$key]['quantidade_compra']) : '';
            $saida[$key]['saldo'] = ($saida[$key]['saldo'] > 0) ? parserValor($saida[$key]['saldo']) : '';
            $saida[$key]['valor'] = ($saida[$key]['valor'] > 0) ? parserValor($saida[$key]['valor']) : '';
            $saida[$key]['valor_compra'] = ($saida[$key]['valor_compra'] > 0) ? parserValor($saida[$key]['valor_compra']) : '';
        }

        $total['pedido_quantidade'] = count($saida);
        $total['quantidade_compra'] = ($total['quantidade_compra'] > 0) ? parserQtd($total['quantidade_compra']) : '';
        $total['saldo'] = ($total['saldo'] > 0) ? parserValor($total['saldo']) : '';
        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        $total['valor_compra'] = ($total['valor_compra'] > 0) ? parserValor($total['valor_compra']) : '';


        return view('programs.analise_performance_fornecedor.modal.pedidos')->with(['dados' => $saida, 'total' => $total]);
    }
}
