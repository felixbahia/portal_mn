<?php

namespace App\Http\Controllers;

use App\ComprasNasajon;
use App\HistoricoCompra;
use App\Http\Requests\PedidoMaiorEstoqueConsultaRequest;
use App\NasajonEstabelecimento;
use App\NecessidadeComprasXProjeto;
use App\NotasNasajon;
use App\PedidoMaiorEstoque;
use App\PedidoPortal;
use App\PedidosReservaProdutoNasajon;
use App\ProdutoEspecificacao;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;

class HistoricoComprasPedidoMaiorEstoqueController extends Controller
{
    
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\PedidoMaiorEstoque") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\PedidoMaiorEstoque');
        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[0]);
        unset($estabelecimentos[20]);
    	return view("programs.pedido_maior_estoque.index")->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function filtroTelaPedidoMaiorEstoque(PedidoMaiorEstoqueConsultaRequest $request){
        $campo = $request->only('estabelecimento', 'descricao', 'codigo');

        $PedidoMaiorEstoqueObj = PedidoMaiorEstoque::select();

        if(!empty($campo['estabelecimento'])){
            $PedidoMaiorEstoqueObj->where('estabelecimento', str_pad($campo['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        if(!empty($campo['descricao'])){
            $PedidoMaiorEstoqueObj->where('produto_descricao', 'ilike', $campo['descricao']);
        }
        if(!empty($campo['codigo'])){
            $PedidoMaiorEstoqueObj->where('produto_codigo', $campo['codigo']);
        }

        $PedidoMaiorEstoque = $PedidoMaiorEstoqueObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $saida = [];

        foreach($PedidoMaiorEstoque as $produto){
            $saida[] = [
                'estabelecimento' => $estabelecimentos[(integer)$produto->estabelecimento],
                'produto_codigo' =>  $produto->produto_codigo,
                'produto_descricao' =>  $produto->produto_descricao,  
                'unidade' =>  $produto->unidade,
                'estoque' =>  parserQtd($produto->estoque),
                'compras_aberto' =>  $produto->compras_aberto > 0 ? parserQtd($produto->compras_aberto) : '',
                'necessidade_compras' =>  $produto->necessidade_compras > 0 ? parserQtd($produto->necessidade_compras) : '',
                'estoque_em_transito' => $produto->estoque_em_transito > 0 ? parserQtd($produto->estoque_em_transito) : '',
                'pedidos_aberto' =>  $produto->quantidade > 0 ? parserQtd($produto->quantidade) : '',
                'filters' => encrypt([
                    'estabelecimento' => $produto->estabelecimento,
                    'estabelecimento_pedido_nasajon' => $produto->estabelecimento,
                    'estabelecimento_pedido_portal' => (integer)$produto->estabelecimento,
                    'produto_codigo' => $produto->produto_codigo
                ])
            ];
        }

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => $saida
        ],200);
    }

    public function modalCompras(Request $request){
        $filter = $request->only(['filters']);
        
        try{
            $busca = decrypt($filter['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $ComprasNasajonObj = ComprasNasajon::where('cod_produto', $busca['produto_codigo'])
           ->whereIn('situacao',['Aberto','Aguardando Documento', 'Parcialmente Liquidado'])
           ->where('estabelecimento', $busca['estabelecimento'])
           ->where(DB::raw("case
           when situacao = 'Parcialmente Liquidado' then
               quantidade_restante
           else
               quantidade
           end"), '>', 0);

        $ComprasNasajon = $ComprasNasajonObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $totalCompras = 0;
        $saida = [];

        foreach($ComprasNasajon as $produto){
            $saida[] = [
                'estabelecimento' => $estabelecimentos[(integer)$produto->estabelecimento],
                'PCMN' => $produto->numero_pedido,
                'proforma' => $produto->proforma,
                'id' => encrypt($produto->id_nota),
                'previsao' => parserData($produto->previsao_entrega),
                'compras' => $produto->quantidade > 0 ? parserQtd($produto->quantidade) : ''
            ];
            $totalCompras += $produto->quantidade;
        }
        return view('programs.pedido_maior_estoque.modal.compras')->with(["dados" => $saida, 'totalCompras' => $totalCompras]);
    }

    public function modalPedidosAbertos(Request $request){
        $filter = $request->only(['filters']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $PedidosReservaProdutoNasajonObj = PedidosReservaProdutoNasajon::with(
            'notasEmAbertoNasajon',
            'notasEmAbertoNasajon.cliente',
            'notasEmAbertoNasajon.condicaoDePagamento',
            'notasNasajon',
            'notasNasajon.condicaoDePagamento',
            'pedidosVendaNasajon',
            'pedidosVendaNasajon.cliente_detalhes',
            'pedidosVendaNasajon.forma_pagamento',
            'pedidosVendaNasajon.forma_pagamento.condicao'
        )
        ->where('codigo_produto', $busca['produto_codigo']);

        if($busca['estabelecimento_pedido_nasajon'] != '00'){
            $PedidosReservaProdutoNasajonObj->where('codigo_estabelecimento', $busca['estabelecimento_pedido_nasajon']);
        }

        $PedidosReservaProdutoNasajon = $PedidosReservaProdutoNasajonObj->get();
 
        $PedidoPortalObj = PedidoPortal::with(['cliente', 'condicao_pagamento_detalhes', 'status_pedido_detalhes',
        'itens_pedido.comprasNasajon' => function($query) use ($busca) {
            $query->where('estabelecimento', $busca['estabelecimento']);
        },
        'itens_pedido' => function($query) use ($busca) {
            $query->where('cod_produto', $busca['produto_codigo']);
        }])
        ->whereNotIn('status_pedido', [3, 5, 7])
        ->whereHas('itens_pedido', function($query) use ($busca){
            $query->where('cod_produto', $busca['produto_codigo']);
        });

        if($busca['estabelecimento_pedido_portal'] != 0){
            $PedidoPortalObj->where('estabelecimento', $busca['estabelecimento_pedido_portal']);
        }

        $PedidoPortal = $PedidoPortalObj->get();

        $saida = [];
        $estabelecimentos = returnEmpresasNasajonView();

        $total = 0;

        foreach($PedidosReservaProdutoNasajon as $pedidoNasajon){

            if($pedidoNasajon->eh_pedido == false){
                if(isset($pedidoNasajon->notasEmAbertoNasajon)){
                    $quantidade = $pedidoNasajon->quantidade;
                    $cliente = isset($pedidoNasajon->notasEmAbertoNasajon->cliente) ? $pedidoNasajon->notasEmAbertoNasajon->cliente->nome : $pedidoNasajon->notasEmAbertoNasajon->cliente_nome;
                    $cliente_codigo = isset($pedidoNasajon->notasEmAbertoNasajon->cliente) ? $pedidoNasajon->notasEmAbertoNasajon->cliente->codigo : $pedidoNasajon->notasEmAbertoNasajon->cliente_documento;
                    $condicao_pagamento = !isset($pedidoNasajon->notasEmAbertoNasajon->condicaoDePagamento) ? '': $pedidoNasajon->notasEmAbertoNasajon->condicaoDePagamento->formapagamento_descricao;
                    $status = 'Em Aberto';
                    $nota_em_aberto = $pedidoNasajon->numero;
                    $nota_em_aberto_id = $pedidoNasajon->id;
                    $total += $quantidade;
                }else{
                    $quantidade = $pedidoNasajon->quantidade;
                    $cliente = isset($pedidoNasajon->notasNasajon->cliente) ? $pedidoNasajon->notasNasajon->cliente->nome : $pedidoNasajon->notasNasajon->cliente_nome;
                    $cliente_codigo = isset($pedidoNasajon->notasNasajon->cliente) ? $pedidoNasajon->notasNasajon->cliente->codigo : $pedidoNasajon->notasNasajon->cliente_documento;
                    $condicao_pagamento = !isset($pedidoNasajon->notasNasajon->condicaoDePagamento) ? '': $pedidoNasajon->notasNasajon->condicaoDePagamento->formapagamento_descricao;
                    $status = 'Em Aberto';
                    $nota_nasajon = $pedidoNasajon->numero;
                    $nota_nasajon_id = $pedidoNasajon->id;
                    $total += $quantidade;
                }
            }else{
                $quantidade = $pedidoNasajon->quantidade;
                $cliente = isset($pedidoNasajon->pedidosVendaNasajon->cliente_detalhes) ? $pedidoNasajon->pedidosVendaNasajon->cliente_detalhes->nome : $pedidoNasajon->pedidosVendaNasajon->cliente_nomefantasia;
                $cliente_codigo = isset($pedidoNasajon->pedidosVendaNasajon->cliente_detalhes) ? $pedidoNasajon->pedidosVendaNasajon->cliente_detalhes->codigo :  $pedidoNasajon->pedidosVendaNasajon->cliente_codigo;
                
                if (!isset($pedidoNasajon->pedidosVendaNasajon->forma_pagamento)){
                    $condicao_pagamento = '';
                }elseif(isset($pedidoNasajon->pedidosVendaNasajon->forma_pagamento) and !isset($pedidoNasajon->pedidosVendaNasajon->forma_pagamento->condicao)){
                    $condicao_pagamento = $pedidoNasajon->pedidosVendaNasajon->forma_pagamento->formapagamento_descricao;
                }else if(isset($pedidoNasajon->pedidosVendaNasajon->forma_pagamento)){
                    $condicao_pagamento = $pedidoNasajon->pedidosVendaNasajon->forma_pagamento->condicao->descricao;
                }

                $status = isset($pedidoNasajon->pedidosVendaNasajon) ? $pedidoNasajon->pedidosVendaNasajon->situacao_descricao : '';
                $total += $quantidade;
            }
            $saida[$pedidoNasajon->numero] = [
                'estabelecimento' => $estabelecimentos[(integer)$pedidoNasajon->codigo_estabelecimento],
                'pedido' => isset($nota_em_aberto) || isset($nota_nasajon) ? '': $pedidoNasajon->numero,
                'pedido_id' => isset($nota_em_aberto_id) || isset($nota_em_aberto_id) ? '': $pedidoNasajon->id,
                'nota_aberto' => isset($nota_em_aberto) ? $nota_em_aberto : null,
                'nota_aberto_id' => isset($nota_em_aberto_id) ? $nota_em_aberto_id : null,
                'nota_nasajon' => isset($nota_nasajon) ? $nota_nasajon : null,
                'nota_nasajon_id' => isset($nota_nasajon_id) ? $nota_nasajon_id : null,
                'cliente' => $cliente,
                'emissao' => Carbon::parse($pedidoNasajon->emissao)->format("d/m/Y"),
                'quantidade' => isset($quantidade) ? parserQtd($quantidade) : '',
                'condicao_pagamento' => $condicao_pagamento,
                'status' => $status,
                'origem' =>'nasajon',
                'cliente_codigo' => $cliente_codigo,
            ];
        }

        foreach($PedidoPortal as $pedidoPortal){
            $saida[$pedidoPortal->id]['estabelecimento'] = $estabelecimentos[$pedidoPortal->estabelecimento];
            $saida[$pedidoPortal->id]['pedido'] = $pedidoPortal->id;
            $saida[$pedidoPortal->id]['pedido_id'] = $pedidoPortal->id;
            $saida[$pedidoPortal->id]['PCMN'] = $pedidoPortal->itens_pedido->pluck('numero_compra')->filter()->implode(' ');
            $saida[$pedidoPortal->id]['PCMN_id'] = $pedidoPortal->itens_pedido->map(function($compra){
                return isset($compra->comprasNasajon->id_nota) ? $compra->comprasNasajon->id_nota : null;
            })->filter()->implode(' ');
            $saida[$pedidoPortal->id]['PCMN_id'] = !empty($saida[$pedidoPortal->id]['PCMN_id']) ? encrypt($saida[$pedidoPortal->id]['PCMN_id']) : '';
            $saida[$pedidoPortal->id]['cliente'] = !isset($pedidoPortal->cliente) ? '' : $pedidoPortal->cliente->nome;
            $saida[$pedidoPortal->id]['emissao'] = Carbon::parse($pedidoPortal->data_pedido)->format("d/m/Y");
            $saida[$pedidoPortal->id]['quantidade'] = $pedidoPortal->itens_pedido->sum('quantidade') > 0 ? parserQtd($pedidoPortal->itens_pedido->sum('quantidade')) : '';
            $saida[$pedidoPortal->id]['condicao_pagamento'] = $pedidoPortal->condicao_pagamento_detalhes->descricao;
            $saida[$pedidoPortal->id]['status'] = $pedidoPortal->status_pedido_detalhes->status;
            $saida[$pedidoPortal->id]['origem'] = 'portal';
            $saida[$pedidoPortal->id]['cliente_codigo'] = !isset($pedidoPortal->cliente) ? '': $pedidoPortal->cliente->codigo;
            $total += $pedidoPortal->itens_pedido->sum('quantidade');
        }

        return view('programs.pedido_maior_estoque.modal.pedidos')->with(["dados" => $saida, "total" => parserValor($total)]);
    }

    public function modalHistoricoCompras(Request $request){
        $filter = $request->only(['filters']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $ultimos_180_dias = Carbon::now()->addMonth(-6)->format('Y-m-d');

        $HistoricoCompra = HistoricoCompra::where('data_alteracao', '>=', $ultimos_180_dias)
        ->where('produto_codigo', $busca['produto_codigo'])
        ->where('estabelecimento', $busca['estabelecimento'])
        ->orderBy('data_alteracao', 'desc')
        ->get();

        $saida = [];
        $estabelecimentos = returnEmpresasNasajonView();

        foreach($HistoricoCompra as $compra){
            $saida[] = [
                'estabelecimento' => $estabelecimentos[(integer)$compra->estabelecimento],
                'pedido' => $compra->numero_pedido,
                'pedido_id' => encrypt($compra->pedido_id),
                'data_alteracao' => parserData($compra->data_alteracao),
                'previsao_entrega' => !is_null($compra->previsao_entrega) ? parserData($compra->previsao_entrega) : '',
                'quantidade_comprada' => $compra->quantidade > 0 ? parserQtd($compra->quantidade) : '',
                'quantidade_recebida' => (($compra->status == 'Liquidado' || $compra->status == 'Parcialmente Liquidado') && ($compra->quantidade - $compra->quantidade_restante) > 0)? parserQtd($compra->quantidade - $compra->quantidade_restante)  : '',
                'saldo' => (($compra->status == 'Liquidado' || $compra->status == 'Parcialmente Liquidado') && ($compra->quantidade_restante > 0)) ? parserQtd($compra->quantidade_restante) : '',
                'status' => $compra->status
            ];
        }  
        return view('programs.pedido_maior_estoque.modal.historico_compras')->with(["dados" => $saida]);
    }

    public function modalHistoricoPedidosAbertos(Request $request){
        $filter = $request->only(['filters']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $ultimos_30_dias = Carbon::now()->addMonth(-1)->format('Y-m-d');

        $estabelecimento_20 = NasajonEstabelecimento::select('raizcnpj','ordemcnpj')
        ->where('codigo', 20)
        ->first();


        $NotasNasajon = NotasNasajon::with(['cliente', 'pedido',
         'itens_nota' => function($query) use ($busca) {
            $query->where('codigo', $busca['produto_codigo']);
        },'pedido.itens_pedido' => function($query) use ($busca) {
            $query->where('produto_codigo', $busca['produto_codigo']);
        }])
        ->whereHas('itens_nota', function($query) use ($busca){
            $query->where('codigo', $busca['produto_codigo']);
        })
        ->where('emissao', '>=', $ultimos_30_dias)
        ->where('estabelecimento_codigo', $busca['estabelecimento'])
        ->where('cliente_documento', '!=', mask($estabelecimento_20->raizcnpj.$estabelecimento_20->ordemcnpj,'##.###.###/####-##'))
        ->orderBy('emissao', 'desc')
        ->get();

        $saida = [];
        $estabelecimentos = returnEmpresasNasajonView();

        $total = [
            'quantidade_pedida' => 0,
            'quantidade_faturada' => 0,
            'saldo' => 0
        ];

        foreach($NotasNasajon as $nota){
            $quantidade_pedida = isset($nota->pedido->itens_pedido) ? $nota->pedido->itens_pedido->sum('quantidadecomercial') : 0;
            $quantidade_faturada = isset($nota->pedido->itens_pedido) ? $nota->pedido->itens_pedido->sum('quantidade_faturada') : 0;
            $saldo = $quantidade_faturada - $quantidade_pedida;

            $saida[] = [
                'estabelecimento' => $estabelecimentos[(integer)$nota->estabelecimento_codigo], 
                'nota' => $nota->numero,
                'nota_id' => $nota->id,
                'pedido' => isset($nota->pedido->numero) ? $nota->pedido->numero : '',
                'pedido_id' => isset($nota->pedido->id) ? $nota->pedido->id : '',
                'cliente' => isset($nota->cliente->nome) ? $nota->cliente->nome.' - '.$nota->cliente->cpf_cnpj : $nota->cliente_nome.' - '.$nota->cliente_documento,
                'emissao_nota' => parserData($nota->emissao),
                'emissao_pedido' => isset($nota->pedido) ? parserData($nota->pedido->emissao) : '',
                'quantidade_pedida' => isset($nota->pedido->itens_pedido) ? parserQtd($nota->pedido->itens_pedido->sum('quantidadecomercial')) : '',
                'quantidade_faturada' => isset($nota->pedido->itens_pedido) ? parserQtd($nota->pedido->itens_pedido->sum('quantidade_faturada')) : '',
                'saldo' => $saldo != 0 ? parserQtd($saldo) : ''
            ];
            $total['quantidade_pedida'] += isset($nota->pedido->itens_pedido) ? $nota->pedido->itens_pedido->sum('quantidadecomercial') : 0;
            $total['quantidade_faturada'] += isset($nota->pedido->itens_pedido) ? $nota->pedido->itens_pedido->sum('quantidade_faturada') : 0;
            $total['saldo'] = $total['quantidade_faturada'] - $total['quantidade_pedida'];
        }
        return view('programs.pedido_maior_estoque.modal.historico_pedidos')->with(["dados" => $saida, "total" => $total]);

    }

    public function filtroBaseHistoricoCompras(){
        ini_set('memory_limit','1024M');

        $HistoricoCompra = HistoricoCompra::select()->first();
        
        $hoje =  Carbon::now()->format('Y-m-d');

        if(!empty($HistoricoCompra->id)){
            $ComprasNasajonObj = ComprasNasajon::where('data_criacao', '!=',$hoje)
            ->where('data_alteracao', $hoje);
        }else{
            $ComprasNasajonObj = ComprasNasajon::where('data_criacao', '>=', '2021-01-01')
            ->whereColumn('data_alteracao', '>', 'data_criacao');
        }
        
        $ComprasNasajon = $ComprasNasajonObj->get();

        $comprasAlteradas = [];

        foreach($ComprasNasajon as $compra){
            $comprasAlteradas[] = [
                'estabelecimento' => $compra->estabelecimento,
                'pedido_id' => $compra->id_nota,
                'produto_codigo' => $compra->cod_produto,
                'produto_descricao' => $compra->descricao_produto,
                'numero_pedido' => $compra->numero_pedido,
                'quantidade' => $compra->quantidade,
                'quantidade_restante' => $compra->quantidade_restante,
                'data_alteracao' => $compra->data_alteracao,
                'previsao_entrega' => $compra->previsao_entrega,
                'status' => $compra->situacao,
            ];
        }
        return $comprasAlteradas;
    }

    public function filtroBasepedidoMaiorEstoque(){
        ini_set('memory_limit','1024M');

        $PedidosReservaProdutoNasajon = PedidosReservaProdutoNasajon::where('codigo_estabelecimento', '!=', 20)
        ->get();
        $PedidoPortal = PedidoPortal::with('itens_pedido')
        ->whereNotIn('status_pedido', [3, 5, 7])
        ->where('estabelecimento', '!=', 20)
        ->get();

        $retorno = [];
    
        foreach($PedidosReservaProdutoNasajon as $reserva){
            $saida[$reserva->codigo_produto] = [
                'estabelecimento' => $reserva->codigo_estabelecimento,
                'quantidade_vendas_aberto' => $reserva->quantidade
            ];
            $produtos_codigo[] = $reserva->codigo_produto;
        }

        unset($PedidosReservaProdutoNasajon);

        foreach($PedidoPortal as $pedido){
            foreach($pedido->itens_pedido as $item){
                if(isset($saida[$item->cod_produto])){
                    if($saida[$item->cod_produto]['estabelecimento'] == '0'.$pedido->estabelecimento){
                        $saida[$item->cod_produto]['quantidade_vendas_aberto'] += $item->quantidade;
                        $produtos_codigo[] = $item->cod_produto;
                    }
                }else{
                    $saida[$item->cod_produto]['estabelecimento'] = '0'.$pedido->estabelecimento;
                    $saida[$item->cod_produto]['quantidade_vendas_aberto'] = $item->quantidade;
                    $produtos_codigo[] = $item->cod_produto;
                }
            }
        }

        unset($PedidoPortal);

        $ProdutoEspecificacao = ProdutoEspecificacao::with('estoque')
        ->whereIn('codigo_produto', $produtos_codigo)->get();

        foreach($ProdutoEspecificacao as $produto){
            if(isset($saida[$produto->codigo_produto])){
                $saldo_fiscal = ($saida[$produto->codigo_produto]['estabelecimento'] == '03' || $saida[$produto->codigo_produto]['estabelecimento'] == '04') ? $produto->estoque->where('estabelecimento', $saida[$produto->codigo_produto]['estabelecimento'])->sum('saldo_fiscal') : 0;
                $saldo_movimento_nao_efetivado = ($saida[$produto->codigo_produto]['estabelecimento'] == '03' || $saida[$produto->codigo_produto]['estabelecimento'] == '04') ? $produto->estoque->where('estabelecimento', $saida[$produto->codigo_produto]['estabelecimento'])->sum('saldo_movimento_nao_efetivado') : 0;

                $saida[$produto->codigo_produto]['produto_codigo'] = $produto->codigo_produto;
                $saida[$produto->codigo_produto]['produto_descricao'] = $produto->descricao;
                $saida[$produto->codigo_produto]['unidade'] = $produto->unidade;
                $saida[$produto->codigo_produto]['estoque'] = $produto->estoque->where('estabelecimento', $saida[$produto->codigo_produto]['estabelecimento'])->sum('estoque');                
                $saida[$produto->codigo_produto]['estoque_em_transito'] = $saldo_fiscal + $saldo_movimento_nao_efetivado;
                $saida[$produto->codigo_produto]['compras_aberto'] = $produto->estoque->where('estabelecimento', $saida[$produto->codigo_produto]['estabelecimento'])->sum('compras_aberto');
                $saida[$produto->codigo_produto]['necessidade_compras'] = 0;
            }
        }

        $NecessidadeDeCompra = NecessidadeComprasXProjeto::with(['produto_acabado_projeto.produto' => function($query) use ($produtos_codigo) {
            $query->whereHas('projeto_detalhes', function($query){
                $query->whereIn('status', [4, 5]);
            })
            ->whereIn('codigo_produto', $produtos_codigo);
        }])
        ->where('tipo', 'servico')
        ->get();

        foreach($NecessidadeDeCompra as $necessidade){
            if(isset($necessidade->produto_acabado_projeto->produto->codigo_produto)){
                if(isset($saida[$necessidade->produto_acabado_projeto->produto->codigo_produto])){
                    $saida[$necessidade->produto_acabado_projeto->produto->codigo_produto]['necessidade_compras'] += isset($necessidade->produto_acabado_projeto->produto) ? $necessidade->produto_acabado_projeto->produto->quantidade : 0;
                }
            }
        }

        foreach($saida as $key => $value){
            if(isset($saida[$key]['produto_codigo'])){
                if($saida[$key]['quantidade_vendas_aberto'] > $saida[$key]['compras_aberto'] + $saida[$key]['estoque'] + $saida[$key]['estoque_em_transito'] + $saida[$key]['necessidade_compras']){
                    $chave = $saida[$key]['estabelecimento'].$saida[$key]['produto_codigo'];
                    $retorno[$chave] = [
                        'estabelecimento' => $saida[$key]['estabelecimento'],
                        'produto_codigo' => $saida[$key]['produto_codigo'],
                        'produto_descricao' => $saida[$key]['produto_descricao'],
                        'unidade' => $saida[$key]['unidade'],
                        'estoque' => $saida[$key]['estoque'],
                        'estoque_em_transito' => $saida[$key]['estoque_em_transito'],
                        'compras_aberto' => $saida[$key]['compras_aberto'],
                        'necessidade_compras' => $saida[$key]['necessidade_compras'],
                        'quantidade' => 0
                    ];
                    $retorno[$chave]['quantidade'] += $saida[$key]['quantidade_vendas_aberto'];
               }
           }
        }
        return $retorno;
    }

    public function salvarDadosNasBases(){
        ini_set('memory_limit','1024M');
        $compras = $this->filtroBaseHistoricoCompras();
        $PedidoMaiorEstoque = $this->filtroBasepedidoMaiorEstoque();

        foreach($compras as $compra){ 
            $HistoricoCompra = new HistoricoCompra;
            $HistoricoCompra->produto_codigo = $compra['produto_codigo'];
            $HistoricoCompra->numero_pedido = $compra['numero_pedido'];
            $HistoricoCompra->quantidade = $compra['quantidade'];
            $HistoricoCompra->data_alteracao = $compra['data_alteracao'];
            $HistoricoCompra->previsao_entrega = $compra['previsao_entrega'];
            $HistoricoCompra->status = $compra['status'];
            $HistoricoCompra->estabelecimento = $compra['estabelecimento'];
            $HistoricoCompra->pedido_id = $compra['pedido_id'];
            $HistoricoCompra->produto_descricao = $compra['produto_descricao'];
            $HistoricoCompra->quantidade_restante = $compra['quantidade_restante'];
            $HistoricoCompra->save();
        }

        PedidoMaiorEstoque::truncate();
        
        foreach($PedidoMaiorEstoque as $key => $pedido){
            $PedidoMaiorEstoqueNasajon = new PedidoMaiorEstoque;
            $PedidoMaiorEstoqueNasajon->estabelecimento =$pedido['estabelecimento'];
            $PedidoMaiorEstoqueNasajon->produto_codigo = $pedido['produto_codigo'];
            $PedidoMaiorEstoqueNasajon->produto_descricao = $pedido['produto_descricao'];
            $PedidoMaiorEstoqueNasajon->unidade = $pedido['unidade'];
            $PedidoMaiorEstoqueNasajon->estoque = $pedido['estoque'];
            $PedidoMaiorEstoqueNasajon->compras_aberto = $pedido['compras_aberto'];
            $PedidoMaiorEstoqueNasajon->quantidade = $pedido['quantidade'];
            $PedidoMaiorEstoqueNasajon->necessidade_compras = $pedido['necessidade_compras'];
            $PedidoMaiorEstoqueNasajon->estoque_em_transito = $pedido['estoque_em_transito'];
            $PedidoMaiorEstoqueNasajon->save();
        }
        return count($compras).' registros de compras e '.count($PedidoMaiorEstoque).' de vendas salvo';
    }
}
