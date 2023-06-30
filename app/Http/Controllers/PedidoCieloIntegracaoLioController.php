<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\CieloAutenticao;
use App\CieloStatu;
use App\CieloPedido;
use App\CieloPedidoItem;
use App\PedidosVendaNasajon;
use App\PedidoPortal;
use App\CieloPedidoTransacao;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PedidoCieloIntegracaoLioController extends Controller
{
	private $url_request = '';

	private $client_id = '';
	private $merchant_id = '';
	private $access_token = '';

	private $jsonSend = '';

    private $id_pedido_cielo = '';

	private $request = [];
    
	private $pedidoPortal = '';
	private $pedidoNasajon = '';

	private $CieloPedido = [];

	private $pedido_padrao = [];
	private $item_padrao = [];

	public function __construct(PedidoPortal $pedidoPortal){
		if(!empty($pedidoPortal->id)){
			// Log::info('entrou na função');
			$this->pedidoPortal = $pedidoPortal;

			$this->pedidoPortal->status_pedido = 9;
			$this->pedidoPortal->save();
			
			$this->initConfig();

			$this->jsonSend = $this->createJsonPedido();

			$this->enviaPedido();
			$this->ativaPedido();

		}
		
	}

	private function initConfig(){
		// Log::info('iniciou as configurações');
		$this->setUrlRequest();
		$this->setHeaderParameters();

		$this->pedido_padrao = [
			'number' => '',
			'reference' => '',
			'status' => 'DRAFT',
			'items' => [],
			'notes' => '',
			'price' => 0,
		];

		$this->item_padrao = [
			'sku' => '',
			'name' => '',
			'description' => '',
			'unit_price' => 0,
			'quantity' => 0,
			'unit_of_measure' => 'EACH'
		];
	}

	private function setUrlRequest(){
		if(config('app.debug') == true){
			$this->url_request = config('cielo.lio.url.sandbox');
		}else{
			$this->url_request = config('cielo.lio.url.producao');
		}
		// Log::info('setou a url de chamada');
	}

	private function setHeaderParameters(){
		$CieloAutenticaoObj = CieloAutenticao::query()
			->where('lio', true);
		if(config('app.debug') == true){
			$CieloAutenticaoObj->where('sandbox', true);
			$CieloAutenticaoObj = $CieloAutenticaoObj->first();
			if(!empty($CieloAutenticaoObj)){
				$this->client_id = $CieloAutenticaoObj->client_id;
				$this->merchant_id = $CieloAutenticaoObj->merchant_id;
				$this->access_token = $CieloAutenticaoObj->access_token;
			}
		}else{
			$CieloAutenticaoObj->where('sandbox', false);
			if(!in_array($this->pedidoPortal->estabelecimento_pad, ['03', '04'])){
				$CieloAutenticaoObj->where('estabelecimento', $this->pedidoPortal->estabelecimento_pad);
			}else{
				if($this->pedidoPortal->usuario_detalhes->tipo_usuario_id != 12){
					if($this->pedidoPortal->usuario_detalhes->id == 69 || $this->pedidoPortal->usuario_detalhes->responsavel == 69){
						$CieloAutenticaoObj->where('estabelecimento', '08');
					}else{
						$CieloAutenticaoObj->where('estabelecimento', '05');
					}

				}else{
					$CieloAutenticaoObj->where('estabelecimento', '05');
				}
			}
			$CieloAutenticaoObj = $CieloAutenticaoObj->first();
			if(!empty($CieloAutenticaoObj)){
				$this->client_id = $CieloAutenticaoObj->client_id;
				$this->merchant_id = $CieloAutenticaoObj->merchant_id;
				$this->access_token = $CieloAutenticaoObj->access_token;
			}
		}
		// Log::info('setou os headers de chamada');
	}

	private function setHeaderParametersEstabelecimento($estabelecimento){
		$CieloAutenticaoObj = CieloAutenticao::query()
			->where('lio', true);
		if(config('app.debug') == true){
			$CieloAutenticaoObj->where('sandbox', true);
			$CieloAutenticaoObj = $CieloAutenticaoObj->first();
			if(!empty($CieloAutenticaoObj)){
				$this->client_id = $CieloAutenticaoObj->client_id;
				$this->merchant_id = $CieloAutenticaoObj->merchant_id;
				$this->access_token = $CieloAutenticaoObj->access_token;
			}
		}else{
			$CieloAutenticaoObj->where('sandbox', false);
			if(!in_array($estabelecimento, ['03', '04'])){
				$CieloAutenticaoObj->where('estabelecimento', $estabelecimento);
			}else{
				if($this->pedidoPortal->usuario_detalhes->tipo_usuario_id != 12){
					if($this->pedidoPortal->usuario_detalhes->id == 69 || $this->pedidoPortal->usuario_detalhes->responsavel == 69){
						$CieloAutenticaoObj->where('estabelecimento', '08');
					}else{
						$CieloAutenticaoObj->where('estabelecimento', '05');
					}

				}else{
					$CieloAutenticaoObj->where('estabelecimento', '05');
				}
			}
			$CieloAutenticaoObj = $CieloAutenticaoObj->first();
			if(!empty($CieloAutenticaoObj)){
				$this->client_id = $CieloAutenticaoObj->client_id;
				$this->merchant_id = $CieloAutenticaoObj->merchant_id;
				$this->access_token = $CieloAutenticaoObj->access_token;
			}
		}
		// Log::info('setou os headers de chamada');
	}

	private function createJsonPedido(){

		$retornoJson = [];

		$retornoJson = $this->pedido_padrao;
		$retornoJson['reference'] = 'E'.$this->pedidoPortal->estabelecimento_pad. ' P'. $this->pedidoPortal->id;
		$retornoJson['notes'] = 'E'.$this->pedidoPortal->estabelecimento_pad. ' P'. $this->pedidoPortal->id;
		$valorTotal = 0;
		$CieloPedidoObj = new CieloPedido();
		$CieloPedidoObj->lio = true;
		$CieloPedidoObj->pedido_id = $this->pedidoPortal->id;
		$CieloPedidoObj->pedido_nasajon_id = null;
		$CieloPedidoObj->cielo_status_id = 1;
		$CieloPedidoObj->pedido_uuid = Str::uuid();
		$CieloPedidoObj->referencia = 'E'.$this->pedidoPortal->estabelecimento_pad. ' P'. $this->pedidoPortal->id;
		$CieloPedidoObj->save();
		$retornoJson['number'] = $this->pedidoPortal->id;
		$this->CieloPedido = $CieloPedidoObj;
		foreach($this->pedidoPortal->itens_pedido as $item){
			$item_json = $this->item_padrao;

			$CieloPedidoItemObj = new CieloPedidoItem();
			$CieloPedidoItemObj->cielo_pedido_id = $CieloPedidoObj->id;
			$CieloPedidoItemObj->produto_codigo = $item->cod_produto;
			$CieloPedidoItemObj->produto_uuid = Str::uuid();
			$CieloPedidoItemObj->valor_unitario = $item->preco_unitario;
			$CieloPedidoItemObj->quantidade = $item->quantidade;
			$CieloPedidoItemObj->unidade = 'EACH';
			
			$valor = (float) number_format(($item->quantidade * $item->preco_unitario), 2, '.', '');
			$valorTotal += $valor;

			$item_json['sku'] = $CieloPedidoItemObj->produto_uuid;
			$item_json['name'] = $item->especificacoes->descricao;
			$item_json['description'] = $item->cod_produto . ' - ' . $item->especificacoes->descricao;
			$item_json['unit_price'] = number_format($valor * 100, 0, '', '');
			$item_json['quantity'] = 1;

			$CieloPedidoItemObj->json_criacao = json_encode($item_json);
			$CieloPedidoItemObj->save();


			$retornoJson['items'][] = $item_json;
		}
		$retornoJson['price'] = number_format($valorTotal * 100, 0, '', '');
		
		$CieloPedidoObj->valor_total = $valorTotal;
		$CieloPedidoObj->json_criacao = json_encode($retornoJson);
		$CieloPedidoObj->save();

		return json_encode($retornoJson);
	}

	private function enviaPedido(){
		try{
			$this->request = new \GuzzleHttp\Client(['base_uri' => $this->url_request]);
			$request = $this->request->request('post', $this->url_request.'/orders',[
				'headers' => [
					'Client-Id' => $this->client_id,
					'merchant-Id' => $this->merchant_id,
					'Access-Token' => $this->access_token,
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',
				],
				'body' => $this->jsonSend
			]);
			// Log::info('enviou o pedido');
			if($request->getStatusCode() === 201){
				$response = json_decode($request->getBody()->getContents(), true);
				$this->id_pedido_cielo = $response['id'];
				$this->CieloPedido->pedido_uuid = $response['id'];
				$this->CieloPedido->save();
				return $request;
			}else{
				return false;
			}
		}catch(\Exception $e){
			return false;
		}
	}

	private function ativaPedido(){
		try{
			if(!empty($this->id_pedido_cielo)){
				$this->request = new \GuzzleHttp\Client(['base_uri' => $this->url_request]);
				$url = $this->url_request.'/orders/'.$this->id_pedido_cielo.'?operation=PLACE';
				$request = $this->request->request('put', $url,[
					'headers' => [
						'Client-Id' => $this->client_id,
						'merchant-Id' => $this->merchant_id,
						'Access-Token' => $this->access_token,
						'Accept' => 'application/json',
						'Content-Type' => 'application/json',
					]
				]);
				// Log::info('ativou o pedido');
				if($request->getStatusCode() === 200){
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}catch(\Exception $e){
			return false;
		}
	}

	private function tratarCodigoHttpRequest($request){
		switch($request->getStatusCode()){
			case 200:
				return 'ok';
			break;
			case 201:
				return 'criado';
			break;
			case 204:
				return 'sem_resposta';
			break;
			case 400:
				return 'parametro_invalidos';
			break;
			case 401:
				return 'sem_acesso';
			break;
			case 403:
				return 'acesso_negado';
			break;
			case 404:
				return 'nao_encontrado';
			break;
			case 413:
				return 'limite_caracteres';
			break;
			case 422:
				return 'sem_parametros';
			break;
			case 429:
				return 'limite_tempo';
			break;
			case 500:
				return 'erro_interno';
			break;
		}
	}

	public function integracaoPedido($retorno){
		$CieloPedidoObj = CieloPedido::with('pedidoPortal')->where('pedido_uuid', $retorno['id'])->first();
		$this->setUrlRequest();
		if(!empty($CieloPedidoObj)){
			$CieloPedidoObj->json_retorno = json_encode($retorno);
			$CieloPedidoObj->save();
			if(!empty($CieloPedidoObj->pedidoPortal)){
				$this->pedidoPortal = $CieloPedidoObj->pedidoPortal; 
				$this->setHeaderParametersEstabelecimento($CieloPedidoObj->pedidoPortal->estabelecimento_pad);
				$this->transacaoPedidoLio($retorno, $CieloPedidoObj);
			}
		}
	}

	public function enviarPedidoRestante($pedidoPortal, $pedidoNasajon, $valor_restante){

		// Log::info('entrou na função');
		$this->pedidoPortal = $pedidoPortal;
		$this->pedidoNasajon = $pedidoNasajon;

		
		$this->initConfig();

		$this->jsonSend = $this->createJsonPedidoRestante($valor_restante);

		$this->enviaPedido();
		$this->ativaPedido();
	}


	private function createJsonPedidoRestante($valor_restante){

		$retornoJson = [];

		$retornoJson = $this->pedido_padrao;
		$retornoJson['reference'] = 'E'.$this->pedidoPortal->estabelecimento_pad. ' P'. $this->pedidoNasajon->numero;
		$retornoJson['notes'] = 'E'.$this->pedidoPortal->estabelecimento_pad. ' P'. $this->pedidoNasajon->numero;
		$valorTotal = 0;
		$CieloPedidoObj = new CieloPedido();
		$CieloPedidoObj->lio = true;
		$CieloPedidoObj->pedido_id = $this->pedidoPortal->id;
		$CieloPedidoObj->pedido_nasajon_id = $this->pedidoNasajon->id;
		$CieloPedidoObj->cielo_status_id = 1;
		$CieloPedidoObj->pedido_uuid = Str::uuid();
		$CieloPedidoObj->referencia = 'E'.$this->pedidoPortal->estabelecimento_pad. ' P'. $this->pedidoNasajon->numero;
		$CieloPedidoObj->save();
		$retornoJson['number'] = $this->pedidoNasajon->numero;
		$this->CieloPedido = $CieloPedidoObj;
		$item_json = $this->item_padrao;
		$item_json['sku'] = Str::uuid();
		$item_json['name'] = 'Restante';
		$item_json['description'] = 'Restante';
		$item_json['unit_price'] = (int) ($valor_restante * 100);
		$item_json['quantity'] = 1;

		$retornoJson['items'][] = $item_json;
		$retornoJson['price'] = (int) number_format($valor_restante * 100, 0, '', '');

		$CieloPedidoObj->valor_total = number_format($valor_restante, 2, '.', '');
		$CieloPedidoObj->json_criacao = json_encode($retornoJson);
		$CieloPedidoObj->save();

		return json_encode($retornoJson);
	}

	public function monitoraPedidos(){
		$CieloPedidoObj = CieloPedido::with('pedidoPortal')->has('pedidoPortal')->where('lio', true)->where('pago', false)->get();
		$this->setUrlRequest();
		$CieloPedidoObj->each(function($pedido){
			$this->pedidoPortal = $pedido->pedidoPortal;
			$this->setHeaderParametersEstabelecimento($pedido->pedidoPortal->estabelecimento_pad);
			$retorno = $this->buscaPedidoCielo($pedido->pedido_uuid);
			if($retorno !== false){
				$this->transacaoPedidoLio($retorno, $pedido);
			}
		});
	}

	private function buscaPedidoCielo($pedido){
		try{
			$this->request = new \GuzzleHttp\Client(['base_uri' => $this->url_request]);
			$request = $this->request->request('get', $this->url_request.'/orders/'.$pedido,[
				'headers' => [
					'Client-Id' => $this->client_id,
					'merchant-Id' => $this->merchant_id,
					'Access-Token' => $this->access_token,
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',
				]
			]);
			if($request->getStatusCode() == 200){
				$response = json_decode($request->getBody()->getContents(), true);
				return $response;
			}else{
				return false;
			}
		}catch(\Exception $e){
			return false;
		}
	}

	private function fecharPedido($pedido){
		try{
			$this->request = new \GuzzleHttp\Client(['base_uri' => $this->url_request]);
			$url = $this->url_request.'/orders/'.$pedido.'?operation=CLOSE';
			$request = $this->request->request('put', $url,[
				'headers' => [
					'Client-Id' => $this->client_id,
					'merchant-Id' => $this->merchant_id,
					'Access-Token' => $this->access_token,
					'Accept' => 'application/json',
					'Content-Type' => 'application/json',
				]
			]);
			if($request->getStatusCode() === 200){
				return true;
			}else{
				return false;
			}
		}catch(\Exception $e){
			return false;
		}
	}

	private function transacaoPedidoLio($retorno, $cielo){
		$transacoes = $retorno['transactions'];
		$total = 0;
		foreach($transacoes as $transacao){
			$CieloPedidoTransacaoObj = new CieloPedidoTransacao();
			$CieloPedidoTransacaoObj->cielo_pedido_id = $cielo->id;
			$CieloPedidoTransacaoObj->id_uuid = $transacao["id"];
			$CieloPedidoTransacaoObj->numero = $transacao["number"];
			$CieloPedidoTransacaoObj->terminal_numero = $transacao["terminal_number"];
			$CieloPedidoTransacaoObj->codigo_autorizacao = $transacao["authorization_code"] ?? "";
			$CieloPedidoTransacaoObj->valor = $transacao["amount"] / 100;
			$CieloPedidoTransacaoObj->tipo_pagamento = $transacao["transaction_type"];
			$CieloPedidoTransacaoObj->json_retorno = json_encode($transacao);
			$CieloPedidoTransacaoObj->save();
			$total += $transacao["amount"] / 100; 
		}
		$total = parserFloat10($total);
		$total_pedido = parserFloat10($cielo->valor_total);
		if($total >= $total_pedido){
			$cielo->pago = true;
			$cielo->valor_pago = $total;
			$cielo->save();
			if(!empty($cielo->pedido_nasajon_id)){
				$this->descloquearPedido($cielo->pedido_nasajon_id);
			}else{
				if(empty($cielo->pedidoPortal->pedido_gerado)){
					$AprovacaoDePedidoControllerObj = new AprovacaoDePedidoController();
					$AprovacaoDePedidoControllerObj->processaIntegracaoPedidoNasajon($cielo->pedidoPortal, []);
				}
			}
			$this->fecharPedido($cielo->pedido_uuid);
		}
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

	public function excliurPedido($pedido){
		$CieloPedidoObj = CieloPedido::with('pedidoPortal')->where('id', $pedido)->get();
		$this->setUrlRequest();
		$CieloPedidoObj->each(function($cielo){
			$this->pedidoPortal = $cielo->pedidoPortal;
			$this->setHeaderParametersEstabelecimento($cielo->pedidoPortal->estabelecimento_pad);
			$this->fecharPedido($cielo->pedido_uuid);
		});
	}

	public function cancelarPedidoRestante($pedidoPortal, $pedidoNasajon){
		$this->setUrlRequest();
		$this->pedidoPortal = $pedidoPortal;
		$this->pedidoNasajon = $pedidoNasajon;
		$CieloPedidoObj = CieloPedido::where('pedido_id', $pedidoPortal->id)->where('pedido_nasajon_id', $pedidoNasajon->id)->get();
		$CieloPedidoObj->each(function($cielo){
			$cielo->pago = true;
			$cielo->valor_pago = 0;
			$cielo->cielo_status_id = 6;
			$cielo->save();

			$this->descloquearPedido($cielo->pedido_nasajon_id);

			$this->pedidoPortal = $cielo->pedidoPortal;
			$this->setHeaderParametersEstabelecimento($cielo->pedidoPortal->estabelecimento_pad);
			$this->fecharPedido($cielo->pedido_uuid);

		});
	}
}
