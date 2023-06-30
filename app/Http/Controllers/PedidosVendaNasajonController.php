<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\PedidosVendaNasajon;
use App\ItensPedidosVendaNasajon;
use App\ProdutoEspecificacao;
use App\CondicoesPagamentoWeb;
use App\CepEndereco;
use App\User;
use App\LogCancelamentoPedido;

use App\Http\Requests\PedidosVendaNasajonCancelaPedidoRequest;
use App\Http\Requests\PedidosVendasNasajonEditarTransportadoraRequest;
use App\PedidoPortal;
use App\PedidoVenda;
use App\TransportadorNasajon;
use Illuminate\Support\Facades\DB;

class PedidosVendaNasajonController extends Controller
{
    public function detalhes($id){
        $return = [];
        $dados  = [];
        $itens  = [];
        
        $desconto = 0;
        $total    = 0;
        $unitario = 0;
        
        $PedidosVendaNasajonObj = PedidosVendaNasajon::
            with(['itens_pedido', 'itens_pedido.produtoNasajon', 'itens_pedido.especificacao', 'formasPagamentosMultiplos','formasPagamentosMultiplos.condicao', 'cliente_detalhes', 'pedido_portal', 'pedido_portal.pedidoTransferenciaRjSp'])
            ->where('rascunho', false)
            ->where('id', $id)
            ->first();

        if(empty($PedidosVendaNasajonObj)){
            exit();
        }
        
        $empresas = returnEmpresasNasajonView();
        $total_itens = [
            'quantidade_pedida' => 0,
            'quantidade_faturada' => 0,
            'desconto' => 0,
            'valor_unitario' => 0,
            'valor_total' => 0,
        ];

        foreach ($PedidosVendaNasajonObj->itens_pedido as $key => $item) {

            $desconto += floatval($item->valordesconto);
            $total += floatval($item->valortotal);

            $produto = $item->especificacao;

            $unitario = floatval($item->valorunitariocomercial);
            
            $itens[] = [
                'codigo' => $produto->codigo_produto??$item->produtoNasajon->codigo,
                'grupo' => $produto->grupo??$item->produtoNasajon->grupo,
                'subgrupo' => $produto->subgrupo??$item->produtoNasajon->subgrupo,
                'unidade' => $item->unidade_codigo??$item->produtoNasajon->unidade,
                'quantidade_pedida' => (!empty($item->quantidadecomercial) ? parserValor($item->quantidadecomercial) : ''),
                'quantidade_faturada' => (!empty($item->quantidade_faturada) ? parserValor($item->quantidade_faturada) : ''),
                'desconto' => (!empty($item->valordesconto) ? parserValor($item->valordesconto) : ''),
                'valor_unitario' => parserValor($unitario),
                'valor_total' => (!empty($item->quantidade_faturada) ? parserValor($item->quantidade_faturada*$unitario) : parserValor($item->valortotal)),
            ];

            $total_itens['quantidade_pedida'] += (!empty($item->quantidadecomercial)) ? $item->quantidadecomercial : 0;
            $total_itens['quantidade_faturada'] += (!empty($item->quantidade_faturada)) ? $item->quantidade_faturada : 0;
            $total_itens['desconto'] += (!empty($item->valordesconto)) ? $item->valordesconto : 0;
            $total_itens['valor_unitario'] += (!empty($unitario)) ? $unitario : 0;
            $total_itens['valor_total'] += (!empty($item->quantidade_faturada) ? ($item->quantidade_faturada*$unitario) : ($item->valortotal));
        }

        $total_itens['quantidade_pedida'] = ($total_itens['quantidade_pedida'] > 0) ? parserValor($total_itens['quantidade_pedida']) : '';
        $total_itens['quantidade_faturada'] = ($total_itens['quantidade_faturada'] > 0) ? parserValor($total_itens['quantidade_faturada']) : '';
        $total_itens['desconto'] = ($total_itens['desconto'] > 0) ? parserValor($total_itens['desconto']) : '';
        $total_itens['valor_unitario'] = ($total_itens['valor_unitario'] > 0) ? parserValor($total_itens['valor_unitario']) : '';
        $total_itens['valor_total'] = ($total_itens['valor_total'] > 0) ? parserValor($total_itens['valor_total']) : '';

        $formapagamento = '';

        if(isset($PedidosVendaNasajonObj->formasPagamentosMultiplos[0])){
            foreach($PedidosVendaNasajonObj->formasPagamentosMultiplos as $formas){
                if(!empty($formapagamento)){
                    $formapagamento .= ', ';
                }
                if(!isset($formas->condicao)){
                    $formapagamento .= $formas->formapagamento_descricao;
                    
                }else if(isset($formas->condicao)){
                    $formapagamento .= $formas->condicao->descricao;
                }

            }
        }
        
        $observacao = $PedidosVendaNasajonObj->observacao_dadosgerais;
        if(in_array($PedidosVendaNasajonObj->operacao_codigo, ['PEDIDOTRANSF', 'PEDIDOTRANSFSEMICMS', 'PEDIDOTRANSFTEXTIL', 'PEDIDOTRANSFTOROSP', 'REMESSA'])){
            $observacao .= ' ' . $PedidosVendaNasajonObj->observacao;
        }else if(in_array($PedidosVendaNasajonObj->operacao_codigo, ['PEDINDUSTRIA'])){
            $observacao .= ' ' . $PedidosVendaNasajonObj->anotacoes_manuais;
        }
		if(!empty($PedidosVendaNasajonObj->pedido_portal->pedidoTransferenciaRjSp)){
			$observacao .= "Pedido Operação RJ/SP: ".$PedidosVendaNasajonObj->pedido_portal->pedidoTransferenciaRjSp->pedido_id;
		}

        $dados = [
            'estabelecimento' => $empresas[intval($PedidosVendaNasajonObj->estabelecimento_codigo)],
            'numero_pedido' => (string) $PedidosVendaNasajonObj->numero,
            'status' => $PedidosVendaNasajonObj->situacao_descricao,
            'tipo_operacao' => $PedidosVendaNasajonObj->operacao_descricao,
            'datahora_pedido' => \parserData($PedidosVendaNasajonObj->emissao),
            'cliente' => $PedidosVendaNasajonObj->cliente_detalhes->nome,
            'vendedor_codigo' => $PedidosVendaNasajonObj->vendedor_codigo,
            'vendedor' => $PedidosVendaNasajonObj->vendedor_nome,
            'transportador_codigo' => $PedidosVendaNasajonObj->transportadora_codigo,
            'transportador' => $PedidosVendaNasajonObj->transportadora_nome,
            'condicao_pagamento' => $formapagamento,
            'desconto_geral' => \parserValor($desconto),
            'valor_total' => parserValor(parserNumber($total_itens['valor_total']) - $desconto),
            'observacao' => $observacao,
        ];

        $dados['cidade_uf'] = $PedidosVendaNasajonObj->cliente_detalhes->cidade . ' - ' . $PedidosVendaNasajonObj->cliente_detalhes->uf;

        if(!is_null($PedidosVendaNasajonObj->pedido_portal)){
            $dados['gerado'] = "<a href='#' class=\"bt-view_pedido\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Pedido\" onclick=\"abrirPedido('" . $PedidosVendaNasajonObj->pedido_portal->id ."')\">Portal -  ".$PedidosVendaNasajonObj->pedido_portal->id."</a>";
            $dados['frete_preco'] = strtoupper($PedidosVendaNasajonObj->pedido_portal->frete_preco);
        }
        else{
            $dados['gerado'] = 'Nasajon';
        }

        return view('programs.pedidos_orcamentos.show_nasajon')->with(["dados" => $dados,"itens" => $itens, "total_itens" => $total_itens]);
    }

    public function detalhesModal(Request $request){
        return $this->detalhes($request->id);
    }

    public function cancelaPedidoNasajon(PedidosVendaNasajonCancelaPedidoRequest $request){
        
        $ids = UserController::varreSubordinados(Auth::id());
        $fields = $request->only('pedido', 'motivo_cancelamento');

        $pedidoObj = PedidosVendaNasajon::find($fields['pedido']);
        $subordinados = User::whereIn('id', $ids)->get()->pluck('codigo_representante')->toArray();

        if(is_null($pedidoObj)){
            $response = [
                "status" => 'error',
                "message" => 'Pedido não encontrado!',
                "error" => [
                ],
                "response" => []
            ];
    
            return response()->json($response, 422);
    
        }
        
        if($pedidoObj->situacao_descricao == 'Faturado'){
            $response = [
                "status" => 'error',
                "message" => 'Pedido já faturado!',
                "error" => [
                ],
                "response" => []
            ];
    
            return response()->json($response, 422);
    
        }

        if($pedidoObj->situacao_descricao == 'Cancelado'){
            $response = [
                "status" => 'error',
                "message" => 'Pedido já cancelado!',
                "error" => [
                ],
                "response" => []
            ];
    
            return response()->json($response, 422);
        }
        /*
        if(!in_array($pedidoObj->vendedor_codigo, $subordinados) && !in_array(Auth::user()->tipo_usuario_id, [1, 15])){
            $response = [
                "status" => 'error',
                "message" => 'Pedido não pertence a equipe do usuário!',
                "error" => [
                ],
                "response" => []
            ];
    
            return response()->json($response, 422);
        }*/


        try {
            $result = DB::connection('nasajon')->select("SELECT * from integracoes.api_pedido_cancelar('" . $fields['pedido']."')");

            if(!is_array($result) || sizeof($result) == 0){
                $response = [
                    "status" => 'error',
                    "message" => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                    "error" => [
                    ],
                    "response" => []
                ];
        
                return response()->json($response, 422);
            }


            $result = $result[0]->mensagem;
            $result = json_decode($result, true);
            if(!isset($result['codigo']) || $result['codigo'] !== 'OK'){
                $response = [
                    "status" => 'error',
                    "message" => $result['mensagem'],
                    "error" => [
                    ],
                    "response" => []
                ];
        
                return response()->json($response, 422);
            }
        } catch (\Illuminate\Database\QueryException $th) {
            $response = [
                "status" => 'error',
                "message" => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                "error" => [
                ],
                "response" => []
            ];
    
            return response()->json($response, 422);
    
        }
        
        $pedidoObj = PedidosVendaNasajon::with('cliente_detalhes')->find($fields['pedido']);

        $logCancelamentoObj = new LogCancelamentoPedido;

        $logCancelamentoObj->pedido_nasajon_id = $pedidoObj->id;
        $logCancelamentoObj->pedido_nasajon_numero = $pedidoObj->numero;
        $logCancelamentoObj->pedido_nasajon_emissao = $pedidoObj->emissao;
        $logCancelamentoObj->cliente = $pedidoObj->cliente_detalhes->nome . ' - ' . $pedidoObj->cliente_detalhes->cpf_cnpj;
        $logCancelamentoObj->motivo_cancelamento_pedidos_id = $fields['motivo_cancelamento'];
        $logCancelamentoObj->user_id = Auth::id();

        $logCancelamentoObj->save();

        $response = [
            "status" => 'success',
            "message" => 'Pedido cancelado com sucesso!',
            "error" => [],
            "response" => ''
        ];
        return response()->json($response, 200);
        
    }

    public function modalEditarTransportadora(Request $request){
        $campos = $request->only('id');

        $pedido_nasajon = PedidosVendaNasajon::with('transportador', 'transportadoraRedespacho')
        ->find($campos['id']);

        if(isset($pedido_nasajon->transportador)){
            $transportadora =  str_replace(' - __.___.___/____-__', '', trim($pedido_nasajon->transportador->nome) . ' - ' . trim($pedido_nasajon->transportador->cnpj));
        }else{
            $transportadora = '';
        }
        
        if(isset($pedido_nasajon->transportadoraRedespacho)){
            $transportadora_redespacho = str_replace(' - __.___.___/____-__', '', trim($pedido_nasajon->transportadoraRedespacho->nome) . ' - ' . trim($pedido_nasajon->transportadoraRedespacho->cnpj));
        }else{
            $transportadora_redespacho = '';
        }

        $itens = [
            'id' => encrypt($pedido_nasajon->id),
            'transportadora_nome' => $transportadora,
            'transportadora_redespacho_nome' => $transportadora_redespacho,
            'transportadora_codigo' => isset($pedido_nasajon->transportador) ? $pedido_nasajon->transportador->codigo : '',
            'transportadora_resdespacho_codigo' => isset($pedido_nasajon->transportadoraRedespacho) ? $pedido_nasajon->transportadoraRedespacho->codigo : '',
        ];  

        return view('programs.pedidos_orcamentos.modal.editar_transportadora')->with($itens);
    }

    public function editarTransportadora(PedidosVendasNasajonEditarTransportadoraRequest $request){
        $campo = $request->only(['id',  'transportadora_codigo', 'transportadora_resdespacho_codigo']);

        $pedido_uuid = decrypt($campo['id']);
        $pedidoVenda = PedidosVendaNasajon::with('pedido_portal')->find($pedido_uuid);

        $pedido_portal = PedidoPortal::find($pedidoVenda->pedido_portal->id);
        $pedido_portal->transportadora =  $campo['transportadora_codigo'];
        $pedido_portal->transportadora_redespacho = $campo['transportadora_resdespacho_codigo'];
        $pedido_portal->updated_by = Auth::id();
        $pedido_portal->save();

        $transportadora_uuid = TransportadorNasajon::where('codigo', $campo['transportadora_codigo'])->first()->id;
        $transportadora_redespacho_uuid = !empty($campo['transportadora_resdespacho_codigo']) ? TransportadorNasajon::where('codigo', $campo['transportadora_resdespacho_codigo'])->first()->id : null;
        $sql_api_insercao = "select * from integracoes.api_pedidovendaalterar_transportadora("."'{$pedido_uuid}',"."'{$transportadora_uuid}',"."'{$transportadora_redespacho_uuid}'".")";
        
        try{
            $api_retorno = DB::connection('nasajon')->select($sql_api_insercao);
        }catch(\Exception $e){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [$e, $sql_api_insercao],
                'response' => []
            ];
        }

        $mensagem_nasajon = $api_retorno[0]->mensagem;
        $mensagem_nasajon = json_decode($mensagem_nasajon, true);
        if($mensagem_nasajon['codigo'] !== 'OK'){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [$mensagem_nasajon['mensagem'], $sql_api_insercao],
                'response' => []
            ];
        }

        $response = [
            "status" => 'success',
            "message" => 'Transportadora alterada com sucesso!',
            "error" => [],
            "response" => ''
        ];
        return response()->json($response, 200);
    }
}