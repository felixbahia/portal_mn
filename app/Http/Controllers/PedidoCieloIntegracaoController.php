<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\PedidosVendaNasajon;
use App\PedidoPortal;
use App\PedidoBloqueadoPagamentoNasajon;

use App\Http\Controllers\PedidoCieloIntegracaoLioController;
use App\Http\Controllers\PedidoCieloIntegracaoEcommerceController;
use App\Http\Controllers\PedidoPortalController;
use App\Http\Controllers\PedidoUsarCreditoController;

use App\Http\Requests\PedidoCieloPagamentoOnlineRequest;

use Illuminate\Support\Facades\DB;

use Auth; 

class PedidoCieloIntegracaoController extends Controller
{
	private $pedidoPortal = '';
	private $pedidoNasajon = '';


	public function __construct(PedidoPortal $pedidoPortal){
		if(!empty($pedidoPortal)){
			$this->pedidoPortal = $pedidoPortal;
			if($this->pedidoPortal->presencial === false){
				$PedidoCieloIntegracaoEcommerceControllerObj = new PedidoCieloIntegracaoEcommerceController($this->pedidoPortal);
			}
		}
	}

	public function integracao(Request $request){
		$PedidoCieloIntegracaoLioControllerObj = new PedidoCieloIntegracaoLioController(new PedidoPortal([]));
		return $PedidoCieloIntegracaoLioControllerObj->integracaoPedido($request->all());
	}

	public function pagamentoOnline(Request $request, $token){
		if(empty($token)){
            return abort(404);
		}
		if($token == 'agradecimento'){
			return view("programs.pagamento_online.agradecimento_pagamento");
		}else{
			return PedidoCieloIntegracaoEcommerceController::telaPedidoPagamento($token);		
		}
	}

	public function pagamentoOnlineRestante(Request $request, $token){
		if(empty($token)){
            return abort(404);
		}
		return PedidoCieloIntegracaoEcommerceController::telaPedidoPagamentoRestante($token);
	}

	public function pagamentoOnlineDebito(Request $request, $token){
		$fields = $request->only(['PaymentId']);
		$PedidoCieloIntegracaoEcommerceControllerObj = new PedidoCieloIntegracaoEcommerceController();
		return $PedidoCieloIntegracaoEcommerceControllerObj->pagamentoDebito($fields, $token);
	}

	public function itensPedido(Request $request){
		$fields = $request->only(['id']);
		try{
			$fields = decrypt($fields['id']);
		}catch(\Exception $e){
            return abort(404);
		}

		$PedidoPortalControllerObj = new PedidoPortalController();
		return $PedidoPortalControllerObj->viewItens($fields);

	}
	
	public function pagar(PedidoCieloPagamentoOnlineRequest $request){
		$fields = $request->only(['pedido', 'cartao', 'nome', 'vencimento', 'codigo_verificacao', 'email_pedido']);
		$PedidoCieloIntegracaoEcommerceControllerObj = new PedidoCieloIntegracaoEcommerceController();
		return $PedidoCieloIntegracaoEcommerceControllerObj->pagar($fields);
	}

	public function pagarRestante(PedidoCieloPagamentoOnlineRequest $request){
		$fields = $request->only(['pedido', 'cartao', 'nome', 'vencimento', 'codigo_verificacao', 'email_pedido']);
		$PedidoCieloIntegracaoEcommerceControllerObj = new PedidoCieloIntegracaoEcommerceController();
		return $PedidoCieloIntegracaoEcommerceControllerObj->pagarRestante($fields);
	}


	public function monitoramentoPedido(){
		$PedidoBloqueadoPagamentoNasajonObj = PedidoBloqueadoPagamentoNasajon::with(['pedido' => function($pedido){
			$pedido->where('grupodeoperacao', 'VENDA');
		}, 'pedido.notaEmAberto'])
		->whereHas('pedido', function($query){
			$query->where('situacao_descricao', 'Em Faturamento');
		})
		->get();
		$dadosBusca = [];
		$pedidos = [];
		$PedidoBloqueadoPagamentoNasajonObj->each(function($pedido) use(&$dadosBusca, &$pedidos) {
			if($pedido->pedido){
				$chave = $pedido->pedido->operacao_codigo.''.$pedido->pedido->numero.''.intval($pedido->pedido->estabelecimento_codigo);
				$dadosBusca[$pedido->pedido->id] = $chave;
				$pedidos[$chave] = $pedido->pedido;
			}
		});
		unset($PedidoBloqueadoPagamentoNasajonObj);
		$PedidoPortalObj = PedidoPortal::with(['cielo' => function($query){
			$query->whereIn('cielo_status_id', [1, 3, 4]);
		}])->wherein(DB::Raw('concat(codigo_operacao, pedido_gerado, estabelecimento)'), array_values($dadosBusca))
		->where('cartao', true)->get();

		$PedidoPortalObj->each(function($pedido) use ($pedidos){
			if(count($pedido->cielo) === 1){
				$chave = $pedido->codigo_operacao.''.$pedido->pedido_gerado.''.intval($pedido->estabelecimento);
				$pedidoNasajon = $pedidos[$chave];
				if(isset($pedidoNasajon->notaEmAberto->valor)){
					$valor_restante = floatVal($pedidoNasajon->notaEmAberto->valor) - $pedido->cielo->sum('valor_total');
				}else{
					$valor_restante = floatVal($pedidoNasajon->nota->valor) - $pedido->cielo->sum('valor_total');
				}
				$valor_restante = (float) number_format($valor_restante, 2, '.', '');
				if($valor_restante >= 1){
					if($pedido->presencial == true){
						$PedidoCieloIntegracaoLioControllerObj = new PedidoCieloIntegracaoLioController(new PedidoPortal([]));
						$PedidoCieloIntegracaoLioControllerObj->enviarPedidoRestante($pedido, $pedidoNasajon, $valor_restante);
					}
					if($pedido->presencial == false){
						$PedidoCieloIntegracaoEcommerceControllerObj = new PedidoCieloIntegracaoEcommerceController(new PedidoPortal([]));
						$PedidoCieloIntegracaoEcommerceControllerObj->enviarPedidoRestante($pedido, $pedidoNasajon, $valor_restante);
					}
				}else{
					$this->descloquearPedido($pedidoNasajon->id);

					if($pedido->rj_x_sp == true){
						$pedido_transferencia = $pedido->pedidoRjSp->pedidoTransferenciaPortal;
						$AprovacaoDePedidoControllerObj = new AprovacaoDePedidoController();
						$AprovacaoDePedidoControllerObj->processaIntegracaoPedidoNasajon($pedido_transferencia, []);
					}
				}
			}else{
				$chave = $pedido->codigo_operacao.''.$pedido->pedido_gerado.''.intval($pedido->estabelecimento);
				$pedidoNasajon = $pedidos[$chave];
				$valor_restante = floatVal($pedidoNasajon->notaEmAberto->valor) - $pedido->cielo->sum('valor_pago');
				if($valor_restante <= 1){
					if($pedido->presencial == true){
						$PedidoCieloIntegracaoLioControllerObj = new PedidoCieloIntegracaoLioController(new PedidoPortal([]));
						$PedidoCieloIntegracaoLioControllerObj->cancelarPedidoRestante($pedido, $pedidoNasajon);
					}
					if($pedido->presencial == false){
						$PedidoCieloIntegracaoEcommerceControllerObj = new PedidoCieloIntegracaoEcommerceController(new PedidoPortal([]));
						$PedidoCieloIntegracaoEcommerceControllerObj->cancelarPedidoRestante($pedido, $pedidoNasajon);
					}
				}else{
					if($pedido->presencial == false){
						$PedidoCieloIntegracaoEcommerceControllerObj = new PedidoCieloIntegracaoEcommerceController(new PedidoPortal([]));
						$PedidoCieloIntegracaoEcommerceControllerObj->atualizaValor($pedido, $pedidoNasajon, $valor_restante);
					}
				}

			}
		});
	}

	public function monitoramentoPedidoLio(){
		$PedidoCieloIntegracaoLioControllerObj = new PedidoCieloIntegracaoLioController(new PedidoPortal([]));
		$PedidoCieloIntegracaoLioControllerObj->monitoraPedidos();
	}

	private function descloquearPedido($pedido){
		$sql_api_validacao = "select * from integracoes.desbloquear_pedido('".$pedido."')";

		try{
			$insert_nasajon = DB::connection('nasajon')->select($sql_api_validacao);
		}catch(\Exception $e){
			return [
				'status' => 'error',
				'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
				'error' => $e,
				'response' => []
			];
		}
	}

	public function retornoPagamento(Request $request, $token){
		$PedidoCieloIntegracaoEcommerceControllerObj = new PedidoCieloIntegracaoEcommerceController(new PedidoPortal([]));
		$PedidoCieloIntegracaoEcommerceControllerObj->tratarPostBack($token, $request);
	}

}
