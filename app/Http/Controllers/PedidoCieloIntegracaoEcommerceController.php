<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\CieloAutenticao;
use App\CieloPedido;
use App\CieloPedidoTransacao;
use App\CieloPedidoRetorno;
use App\StoneErroTransacoesPedido;
use App\PedidoPortal;
use App\CieloErro;
use App\PagarmeCodigoErro;
use App\PagarmeLogEnvio;
use App\CartoesContratosNasajon;
use App\CartoesMeiosEletronicosNasajon;
use App\CartoesOperadorasNasajon;
use App\CartoesBandeirasNasajon;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\EmailController;

use Carbon\Carbon;

class PedidoCieloIntegracaoEcommerceController extends Controller
{

	private $url_request = "";

	private $token_api = "";
    
	private $pedidoPortal = [];
	private $pedidoNasajon = [];

	private $CieloPedido = [];

	private $linkPedido = "";
	
	private $tipoPagamentoPedido = [];

	private $jsonSend = "";

	private $arrayPadrao = [
		'amount' => 0, // valor total
		'installments' => 1, // parcelas
		'card_holder_name' => '', // nome cartão
		'card_expiration_date' => '', // data cartão MMAA
		'card_number' => '', // numero cartão
		'card_cvv' => '',
		'payment_method' => 'credit_card', // metodo credito ou debito (credit_card)
		'postback_url' => '', // link retorno
		'customer' => [ // dados de cobrança (quem está pagando)
			'external_id' => '', // codigo
			'name' => '',  // mnome
			'email' => '', // email
			'type' => '', // tipo de pessoal individual ou corporativo
			'country' => 'br', // pais
			'documents' => [
				[
					'type' => '', // tipo documento
					'number' => '' // documento (só numero)
				]
			],
			'phone_numbers' => [ ] // telefone  (só numero com começo +55)
		],
		'billing' => [ // dados de cobrança (quem está pagando)
			'name' => '', // nome cliente
			'address' => []
		],
		'shipping' => [ // dados de envio
			'name' => '', // nome cliente
			'fee' => 0, // valor de frete
			'delivery_date' => '', // data de entrega estimativa
			'expedited' => false, // entrega experssa (true ou false)
			'address' => []
		],
		'items' => [ ]
	];
	private $arrayPadraoItem = [
		'id' => '', // codigo produto
		'title' => '', // descricao do produto
		'unit_price' => 0, // preco unitario
		'quantity' => 0, // quantidade
		'tangible' => true // bem fisico
	];
	private $arrayPadraoEndereco = [
		'country' => 'br', // pais
		'street' => '', /// rua
		'street_number' => '', // endereço
		'state' => '', // estado
		'city' => '', // cidade
		'neighborhood' => '', // bairro
		'zipcode' => '' // cep (só numero)
	];

	
    private $bandeiras_nasajon = [
        'Visa' => 'be5ffccb-86b1-422c-a1c2-0130f15992d7',
        'MasterCard' => '11228c0d-e9d1-4ea0-8e3e-bc53077b6362',
        'Amex' => '9a60b8bf-35cc-488f-b93e-52670c4b1ddb',
        'Elo' => 'b0f50491-0b45-4880-80f5-470e3a4e8fc3',
        'Hipercard' => '6d657fc3-5fa0-4343-b0f8-d4b924ba10ee'
    ];

    private $id_forma_pagamento_nasajon = [
        'credito' => 'c41decd7-935f-449a-9a60-fd1a2330661b',
        'debito' => 'b0444787-b579-422d-af2a-ce691cbff825'
    ];

    private $operacao_cartao_nasajon = [
        'nao_identificado' => 'c4b95a55-71f1-4047-936f-8dced97cbc12'
    ];

    private $meios_eletronicos = [
        'pagarme' => 'Pagar.me'
    ];

    private $cnpj_operadora = '16.501.555/0001-57';

	public function __construct(PedidoPortal $pedidoPortal = null){
		if(!empty($pedidoPortal->id)){
            $this->pedidoPortal = $pedidoPortal;
            
            $this->criaPedido();

            $this->enviaPedido();
        }
	}

	private function initConfig(){
		if(config("app.debug") == true){
			Log::info("iniciou as configurações");
		}
		$this->setUrlRequest();
		$this->setHeaderParameters();
	}

	private function setUrlRequest(){
		$this->url_request = config("pagarme.url");
	}

	private function setHeaderParameters(){
		$CieloAutenticaoObj = CieloAutenticao::query()
			->where("ecommerce", true);
		if(config("app.debug") == true){
			$CieloAutenticaoObj->where("sandbox", true);
			$CieloAutenticaoObj = $CieloAutenticaoObj->first();
			if(!empty($CieloAutenticaoObj)){
				$this->token_api = $CieloAutenticaoObj->token_api;
			}
		}else{
			$CieloAutenticaoObj->where("sandbox", false);
			$CieloAutenticaoObj->where("estabelecimento", $this->CieloPedido->pedidoPortal->estabelecimento_pad);
			$CieloAutenticaoObj = $CieloAutenticaoObj->first();
			if(!empty($CieloAutenticaoObj)){
				$this->token_api = $CieloAutenticaoObj->token_api;
			}
		}
		if(config("app.debug") == true){
			Log::info("setou os headers de chamada");
		}
	}

	private function criaPedido(){
		$CieloPedidoObj = new CieloPedido();
		$CieloPedidoObj->ecommerce = true;
		$CieloPedidoObj->pedido_id = $this->pedidoPortal->id;
		$CieloPedidoObj->cielo_status_id = 1;
        $CieloPedidoObj->pedido_uuid = Str::uuid();
		$CieloPedidoObj->referencia = "E".$this->pedidoPortal->estabelecimento_pad. " P". $this->pedidoPortal->id;
		$CieloPedidoObj->valor_total = $this->pedidoPortal->valor_total_nota;
        $CieloPedidoObj->save();
        
        $this->CieloPedido = $CieloPedidoObj;

        $CieloPedidoObj->link_pedido = $this->createToken();
        $this->linkPedido = route("pedido_online.pagamento", ["token" => $CieloPedidoObj->link_pedido]);
        $CieloPedidoObj->save();
    }
    
    private function createToken(){
		$alfanumericos = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ@";
		do{
			$alfanumericos = str_shuffle($alfanumericos);
			$token = substr($alfanumericos, 0, 12);
			$checa_existe = CieloPedido::where('link_pedido', $token)->exists();
		}while($checa_existe == true);
        return $token;
    }

    private function createTokenRetorno(){
		$alfanumericos = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ@";
		do{
			$alfanumericos = str_shuffle($alfanumericos);
			$token = substr($alfanumericos, 0, 12);
			$checa_existe = CieloPedido::where('token_retorno', $token)->exists();
		}while($checa_existe == true);
        return $token;
    }

	private function enviaPedido(){
		$EmailObj = new EmailController();
		$email_send = [];
		$email_send = [$this->pedidoPortal->email_comprador, $this->pedidoPortal->usuario_detalhes->email];
		$nome_cliente = $this->pedidoPortal->cliente->nome;
        $variaveis = [
            "nome_cliente" => $nome_cliente,
            "numero_pedido" => $this->pedidoPortal->id,
            "valor_pagamento" => parserValor($this->pedidoPortal->valor_total_nota),
            "condicao_pagamento" => $this->pedidoPortal->condicao_pagamento_detalhes->descricao,
            "representante" => $this->pedidoPortal->usuario_detalhes->codigo_representante.' - '.$this->pedidoPortal->usuario_detalhes->name,
            "link_pagamento" => $this->linkPedido
        ];
		$returnEmail = $EmailObj->sendEmailToken("00", "pagamento_pedido", $email_send, $variaveis);

		$this->createLog('envio_email', print_r($returnEmail, true));
		$this->CieloPedido->datahora_envio_email = Carbon::now();
		$this->CieloPedido->save();

		$this->pedidoPortal->status_pedido = 9;
		$this->pedidoPortal->save();
		
    }
    
    public static function telaPedidoPagamento($token){
		$cielo = CieloPedido::with(["pedidoPortal", "pedidoPortal.usuario_detalhes", "pedidoPortal.cliente", "pedidoPortal.condicao_pagamento_detalhes"])->where("link_pedido", $token)->first();
		if(empty($cielo)){
            return abort(404);
		}
        $pedido = $cielo->pedidoPortal ?? [];
		$pedido_token = encrypt($cielo->id);
		$pago = $cielo->pago;
		$cielo_status_id = $cielo->cielo_status_id;
		unset($cielo);
		$liberado = true;
		$processamento = false;
		if(
			empty($pedido) ||
			$pedido->status_pedido == '7' ||
			$cielo_status_id == 5 ||
			$cielo_status_id == 6
		){
			$liberado = false;
			$pago = true;
		}
		if($cielo_status_id == 7){
			$processamento = true;
		}
		
        return view("programs.pagamento_online.index")->with(["pedido" => $pedido, "pedido_token" => $pedido_token, 'pago' => $pago, 'liberado' => $liberado, 'processamento' => $processamento]);
	}

    public static function telaPedidoPagamentoRestante($token){
		$cielo = CieloPedido::with(["pedidoPortal", "pedidoPortal.usuario_detalhes", "pedidoPortal.cliente", "pedidoPortal.condicao_pagamento_detalhes", "pedidoNasajon", "pedidoNasajon.notaEmAberto"])->where("link_pedido", $token)->first();
		if(empty($cielo)){
            return abort(404);
		}
        $pedido = $cielo->pedidoPortal;
		$pedido_token = encrypt($cielo->id);
		$pago = $cielo->pago;
		$diferenca = $cielo->valor_total;
		$processamento = false;
		if($pedido->status_pedido == '7' || $cielo->cielo_status_id == 5 || $cielo->cielo_status_id == 6){
			$liberado = false;
			$pago = true;
		}
		if($cielo->cielo_status_id == 7){
			$processamento = true;
		}
		unset($cielo);
        return view("programs.pagamento_online.restante")->with(["pedido" => $pedido, "pedido_token" => $pedido_token, 'pago' => $pago, 'diferenca' => $diferenca, 'processamento' => $processamento]);
	}
	
    public function pagar($fields){
		if(empty($fields["pedido"])){
            return abort(404);
		}
		try{
			$id = decrypt($fields["pedido"]);
		}catch(\Exception $e){
            return abort(404);
		}
		unset($fields["pedido"]);

		try{
			$this->CieloPedido = CieloPedido::with(["pedidoPortal", "pedidoPortal.condicao_pagamento_detalhes", "pedidoPortal.cliente", 'erros'])->where("id",$id)->first();
			if($this->CieloPedido->pago === true){
				return response()->json([
					"status" => "error",
					"message" => "Pedido já pago",
					"error" => [],
					"response" => []
				], 422);
			}
			if($this->CieloPedido->cielo_status_id === 7){
				return response()->json([
					"status" => "error",
					"message" => "Pedido aguardando processamento",
					"error" => [],
					"response" => []
				], 422);
			}

			if(empty($this->CieloPedido->pedidoPortal)){
				return abort(404);
			}
			$this->initConfig();
			$this->tipoPagamentoPedido = $this->tratarCondicaoPagamento($this->CieloPedido->pedidoPortal->condicao_pagamento_detalhes->descricao);

			$cartao = str_replace(" ", "", $fields["cartao"]);
			$nome = $fields["nome"];
			$vencimento = Carbon::createFromFormat("m/y", $fields["vencimento"]);
			$codigo_verificacao = $fields["codigo_verificacao"];
			$email = $fields["email_pedido"];

			$validacaoCartao = $this->validacaoCartao($cartao);
			if($validacaoCartao !== true){
				return $validacaoCartao;
			}

			$this->createJsonPedido($cartao, $nome, $vencimento, $codigo_verificacao,$email);

			$envio_pedido = $this->enviarPedido();
			if($envio_pedido !== true){
				return $envio_pedido;
			}else{

				$CieloErroObj = new CieloErro();
				$CieloErroObj->json_enviado = json_decode($this->CieloPedido->json_criacao) ?? '{}';;
				$CieloErroObj->json_retorno = 'erro ao enviar';
				$CieloErroObj->motivo_erro = 'erro ao enviar';
				$CieloErroObj->cielo_pedido_id = $this->CieloPedido->id;
				$CieloErroObj->save();

				return response()->json([
					"status" => "success",
					"message" => "Caro cliente a MN agradece e confirma seu pagamento, seu pedido ja esta sendo separado e expedido. Obrigado por comprar conosco!<br />Equipe MN",
					"error" => [$envio_pedido],
					"response" => []
				]);
			}
		}catch(\Exception $e){
			$CieloErroObj = new CieloErro();
			$CieloErroObj->json_enviado = json_decode($this->CieloPedido->json_criacao) ?? '{}';;
			$CieloErroObj->json_retorno = ($e);
			$CieloErroObj->motivo_erro = 'erro ao enviar';
			$CieloErroObj->cielo_pedido_id = $this->CieloPedido->id;
			$CieloErroObj->save();

			return response()->json([
				"status" => "error",
				"message" => "Caro cliente infelizmente não conseguimos confirmar seu pagamento, verifique as informações em seu cartão e tente novamente mais tarde. Equipe Mn",
				"error" => [$e],
				"response" => []
			], 422);
		}

	}

	private function createJsonPedido($cartao, $nome, $vencimento, $codigo_verificacao, $email = ''){
		$array = $this->arrayPadrao;
		$array["card_number"] = $cartao;
		$array["card_holder_name"] = $nome;
		$array["card_expiration_date"] = $vencimento->format("mY");
		$array["card_cvv"] = $codigo_verificacao;
		$array["installments"] = $this->tipoPagamentoPedido["parcelas"];

		$array["amount"] = (int) number_format($this->CieloPedido->valor_total * 100, 0, "", "");
        
		$token = $this->createTokenRetorno();
		$this->CieloPedido->token_retorno = $token;
		$this->CieloPedido->save();
		$rota = route("pedido_online.retorno_pagamento", ["token" => $token]);
		$array["postback_url"] = $rota;

		$array["customer"]["external_id"] = $this->CieloPedido->pedidoPortal->cliente->codigo;
		$array["customer"]["name"] = $this->CieloPedido->pedidoPortal->cliente->nome;
		$array['billing']['name'] =  $this->CieloPedido->pedidoPortal->cliente->nome;
		$array['shipping']['name'] =  $this->CieloPedido->pedidoPortal->cliente->nome;
		$array["customer"]["email"] = empty($email)? $this->CieloPedido->pedidoPortal->email_comprador : $email;
		
		$documento = $this->CieloPedido->pedidoPortal->cliente->cpf_cnpj;
		$documento = str_replace(['-', '.', '/'], '', $documento);
		switch(strlen($documento)){
			case 14:
				$array["customer"]["type"] = "corporation";
				$array["customer"]["documents"][0]['type'] = "cnpj";
			break;
			case 11:
				$array["customer"]["type"] = "individual";
				$array["customer"]["documents"][0]['type'] = "cpf";
			break;
		}
		$array["customer"]["documents"][0]['number'] = $documento;
		$telefones = $this->CieloPedido->pedidoPortal->cliente->telefones;
		if(!empty($telefones)){
			$telefones = explode(',', $telefones);
			$formatar_telefone = function($value){
				return "+55".str_replace(['(', ')', '-'], '', $value);
			};
			$telefones = array_map($formatar_telefone, $telefones);
			$array["customer"]["phone_numbers"] = $telefones;
		}

		$array_endereco = $this->arrayPadraoEndereco;
		$array_endereco['street'] = $this->CieloPedido->pedidoPortal->cliente->tipologradouro.': '.$this->CieloPedido->pedidoPortal->cliente->logradouro;
		$array_endereco['street_number'] = $this->CieloPedido->pedidoPortal->cliente->numero;
		if(!empty($this->CieloPedido->pedidoPortal->cliente->complemento)){
			$array_endereco['complementary'] = $this->CieloPedido->pedidoPortal->cliente->complemento;
		}
		$array_endereco['state'] = $this->CieloPedido->pedidoPortal->cliente->uf;
		$array_endereco['city'] = $this->CieloPedido->pedidoPortal->cliente->cidade;
		$array_endereco['neighborhood'] = $this->CieloPedido->pedidoPortal->cliente->bairro;
		$array_endereco['zipcode'] = str_replace(['-', '_'], '', $this->CieloPedido->pedidoPortal->cliente->cep);

		$this->CieloPedido->pedidoPortal->itens_pedido->each(function($item) use (&$array){
			$array_item = $this->arrayPadraoItem;
			$array_item['id'] = $item->cod_produto;
			$array_item['title'] = $item->especificacoes->descricao;
			$array_item['unit_price'] = (int) number_format(($item->preco_unitario * $item->quantidade) * 100, 0, "", "");
			$array_item['quantity'] = 1;
			$array['items'][] = $array_item;
		});
		
		$array['billing']['address'] = $array_endereco;
		$array['shipping']['address'] = $array_endereco;
		$array['shipping']['delivery_date'] = date('Y-m-d');

		$array['api_key'] = $this->token_api;
		$this->jsonSend = json_encode($array);
		$this->CieloPedido->json_criacao = $this->jsonSend;
		$this->CieloPedido->save();
	}

	private function enviarPedido(){
		$this->request = new \GuzzleHttp\Client(["base_uri" => $this->url_request]);
		try{
			$request = $this->request->request("post", $this->url_request."transactions",[
				"headers" => [
					"Accept" => "application/json",
					"Content-Type" => "application/json"
				],
				"body" => $this->jsonSend
			]);
			if(config("app.debug") == true){
				Log::info("enviou o pedido");
			}
			if($request->getStatusCode() === 200){
				$response = json_decode($request->getBody()->getContents(), true);
				return $this->tratarRetornoPagamento($response);
			}else{
				return response()->json([
					"status" => "error",
					"message" => "Ocorreu uma instabilidade ao fazer o pagamento! Tente novamente!",
					"error" => [],
					"response" => []
				], 422);
			}
		}catch(\Exception $e){
			return response()->json([
				"status" => "error",
				"message" => "Caro cliente infelizmente não conseguimos confirmar seu pagamento, verifique as informações em seu cartão e tente novamente mais tarde. Equipe Mn",
				"error" => [$e],
				"response" => []
			], 422);
		}
	}

	private function tratarRetornoPagamento($response){
		$this->CieloPedido->cielo_status_id = 7;
		$this->CieloPedido->datahora_envio_pagarme = Carbon::now();
		$this->CieloPedido->json_retorno = json_encode($response);
		$this->CieloPedido->save();

		$this->CieloPedido->pedidoPortal->status_pedido = 11;
		$this->CieloPedido->pedidoPortal->save();
		
		$this->createLog('envio_pagarme', $this->jsonSend);

		return response()->json([
			"status" => "success",
			"message" => "Pedido confirmado, aguardando liberação de crédito",
			"error" => [],
			"response" => []
		]);
	}

	private function tratarCondicaoPagamento($condicao_pagamento){
		$return = ["parcelas" => 0, "tipo" => ""];
		$condicao_pagamento = strtolower($condicao_pagamento);
		if(strpos($condicao_pagamento, "credito") !== false){
			$return["tipo"] = "credito";
			$condicao_pagamento = str_replace("cartao de credito", "", $condicao_pagamento);
			$condicao_pagamento = trim($condicao_pagamento);
			$condicao_pagamento = str_replace("x", "", $condicao_pagamento);
			$condicao_pagamento = (int) trim($condicao_pagamento);
			if($condicao_pagamento == 0){
				$condicao_pagamento = 1;
			}
			$return["parcelas"] = $condicao_pagamento;
		}else{
			$return["tipo"] = "debito";
			$return["parcelas"] = 1;
		}
		return $return; 
	}

	private function validacaoCartao($cartao){
		if(count($this->CieloPedido->erros) > 0){
			$returno = false;
			$this->CieloPedido->erros->each(function($erro) use (&$returno, $cartao){
				$json = $erro->json_enviado;
				if($json['card_number'] == $cartao){
					$returno = true;
				}
			});
			if($returno){
				return response()->json([
					"status" => "error",
					"message" => "Pagamento não Autorizado !.",
					"error" => [],
					"response" => []
				], 422);
			}
		}
		return true;
	}

	public function enviarPedidoRestante($pedidoPortal, $pedidoNasajon, $valor_restante){

		$this->pedidoPortal = $pedidoPortal;
		$this->pedidoNasajon = $pedidoNasajon;
            
		$this->criaPedidoRestante($valor_restante);

		$this->enviaPedidoRestante($valor_restante);
	}

	private function criaPedidoRestante($valor_restante){
		$CieloPedidoObj = new CieloPedido();
		$CieloPedidoObj->ecommerce = true;
		$CieloPedidoObj->pedido_id = $this->pedidoPortal->id;
		$CieloPedidoObj->pedido_nasajon_id = $this->pedidoNasajon->id;
		$CieloPedidoObj->cielo_status_id = 1;
        $CieloPedidoObj->pedido_uuid = Str::uuid();
		$CieloPedidoObj->referencia = "E".$this->pedidoPortal->estabelecimento_pad. " P". $this->pedidoPortal->id;
		$CieloPedidoObj->valor_total = $valor_restante;
        $CieloPedidoObj->save();
        
        $this->CieloPedido = $CieloPedidoObj;

        $CieloPedidoObj->link_pedido = $this->createToken();
        $this->linkPedido = route("pedido_online.pagamento_restante", ["token" => $CieloPedidoObj->link_pedido]);
        $CieloPedidoObj->save();
    }
    
	private function enviaPedidoRestante($valor_restante){
		$EmailObj = new EmailController();
		$email_send = [];
		$email_send = [$this->pedidoPortal->email_comprador, $this->pedidoPortal->usuario_detalhes->email];
		$nome_cliente = $this->pedidoPortal->cliente->nome;
        $variaveis = [
            "pedido" => $this->pedidoPortal->id,
            "nome_cliente" => $nome_cliente,
			"link_pagamento" => $this->linkPedido,
			"valor_total" => parserValor($this->pedidoPortal->valor_total_nota),
			"valor_diferenca" => parserValor($valor_restante)
        ];
		$returnEmail = $EmailObj->sendEmailToken("00", "pagamento_pedido_restante", $email_send, $variaveis);
		
		$this->createLog('envio_email', print_r($returnEmail, true));
		$this->CieloPedido->datahora_envio_email = Carbon::now();
		$this->CieloPedido->save();
	}

    public function pagarRestante($fields){
		if(empty($fields["pedido"])){
            return abort(404);
		}
		try{
			$id = decrypt($fields["pedido"]);
		}catch(\Exception $e){
            return abort(404);
		}
		unset($fields["pedido"]);
		try{
			$this->CieloPedido = CieloPedido::with(["pedidoPortal", "pedidoPortal.cliente"])->where("id",$id)->first();
			if($this->CieloPedido->pago === true){
				return response()->json([
					"status" => "error",
					"message" => "Pedido já pago",
					"error" => [],
					"response" => []
				], 422);
			}
			if($this->CieloPedido->cielo_status_id === 7){
				return response()->json([
					"status" => "error",
					"message" => "Pedido aguarda processamento",
					"error" => [],
					"response" => []
				], 422);
			}
			$this->initConfig();
			$this->tipoPagamentoPedido = $this->tratarCondicaoPagamento($this->CieloPedido->pedidoPortal->condicao_pagamento_detalhes->descricao);

			$cartao = str_replace(" ", "", $fields["cartao"]);
			$nome = $fields["nome"];
			$vencimento = Carbon::createFromFormat("m/y", $fields["vencimento"]);
			$codigo_verificacao = $fields["codigo_verificacao"];
			$email = $fields["email_pedido"];

			$validacaoCartao = $this->validacaoCartao($cartao);
			if($validacaoCartao !== true){
				return $validacaoCartao;
			}

			$this->createJsonPedidoRestante($cartao, $nome, $vencimento, $codigo_verificacao, $email);

			$envio_pedido = $this->enviarPedidoPagamentoRestante();
			if($envio_pedido !== true){
				return $envio_pedido;
			}else{

				$CieloErroObj = new CieloErro();
				$CieloErroObj->json_enviado = json_decode($this->CieloPedido->json_criacao) ?? '{}';;
				$CieloErroObj->json_retorno = ('erro ao enviar');
				$CieloErroObj->motivo_erro = 'erro ao enviar';
				$CieloErroObj->cielo_pedido_id = $this->CieloPedido->id;
				$CieloErroObj->save();

				return response()->json([
					"status" => "success",
					"message" => "Caro cliente a MN agradece e confirma seu pagamento, seu pedido ja esta sendo separado e expedido. Obrigado por comprar conosco!<br />Equipe MN",
					"error" => [$envio_pedido],
					"response" => []
				]);
			}
		}catch(\Exception $e){
			$CieloErroObj = new CieloErro();
			$CieloErroObj->json_enviado = json_decode($this->CieloPedido->json_criacao) ?? '{}';
			$CieloErroObj->json_retorno = ($e);
			$CieloErroObj->motivo_erro = 'erro ao enviar';
			$CieloErroObj->cielo_pedido_id = $this->CieloPedido->id;
			$CieloErroObj->save();

			return response()->json([
				"status" => "error",
				"message" => "Caro cliente infelizmente não conseguimos confirmar seu pagamento, verifique as informações em seu cartão e tente novamente mais tarde. Equipe Mn",
				"error" => [$e],
				"response" => []
			], 422);
		}
	}

	private function createJsonPedidoRestante($cartao, $nome, $vencimento, $codigo_verificacao, $email = ''){
		$array = $this->arrayPadrao;
		$array["card_number"] = $cartao;
		$array["card_holder_name"] = $nome;
		$array["card_expiration_date"] = $vencimento->format("mY");
		$array["card_cvv"] = $codigo_verificacao;
		$array["installments"] = 1;

		$array["amount"] = (int) number_format($this->CieloPedido->valor_total * 100, 0, "", "");
        
		$token = $this->createTokenRetorno();
		$this->CieloPedido->token_retorno = $token;
		$this->CieloPedido->save();
		$rota = route("pedido_online.retorno_pagamento", ["token" => $token]);
		$array["postback_url"] = $rota;

		$array["customer"]["external_id"] = $this->CieloPedido->pedidoPortal->cliente->codigo;
		$array["customer"]["name"] = $this->CieloPedido->pedidoPortal->cliente->nome;
		$array['billing']['name'] =  $this->CieloPedido->pedidoPortal->cliente->nome;
		$array['shipping']['name'] =  $this->CieloPedido->pedidoPortal->cliente->nome;
		$array["customer"]["email"] = empty($email)? $this->CieloPedido->pedidoPortal->email_comprador : $email;
		
		$documento = $this->CieloPedido->pedidoPortal->cliente->cpf_cnpj;
		$documento = str_replace(['-', '.', '/'], '', $documento);
		switch(strlen($documento)){
			case 14:
				$array["customer"]["type"] = "corporation";
				$array["customer"]["documents"][0]['type'] = "cnpj";
			break;
			case 11:
				$array["customer"]["type"] = "individual";
				$array["customer"]["documents"][0]['type'] = "cpf";
			break;
		}
		$array["customer"]["documents"][0]['number'] = $documento;
		$telefones = $this->CieloPedido->pedidoPortal->cliente->telefones;
		if(!empty($telefones)){
			$telefones = explode(',', $telefones);
			$formatar_telefone = function($value){
				return "+55".str_replace(['(', ')', '-'], '', $value);
			};
			$telefones = array_map($formatar_telefone, $telefones);
			$array["customer"]["phone_numbers"] = $telefones;
		}

		$array_endereco = $this->arrayPadraoEndereco;
		$array_endereco['street'] = $this->CieloPedido->pedidoPortal->cliente->tipologradouro.': '.$this->CieloPedido->pedidoPortal->cliente->logradouro;
		$array_endereco['street_number'] = $this->CieloPedido->pedidoPortal->cliente->numero;
		if(!empty($this->CieloPedido->pedidoPortal->cliente->complemento)){
			$array_endereco['complementary'] = $this->CieloPedido->pedidoPortal->cliente->complemento;
		}
		$array_endereco['state'] = $this->CieloPedido->pedidoPortal->cliente->uf;
		$array_endereco['city'] = $this->CieloPedido->pedidoPortal->cliente->cidade;
		$array_endereco['neighborhood'] = $this->CieloPedido->pedidoPortal->cliente->bairro;
		$array_endereco['zipcode'] = str_replace(['-', '_'], '', $this->CieloPedido->pedidoPortal->cliente->cep);

		$array_item = $this->arrayPadraoItem;
		$array_item['id'] = '00001';
		$array_item['title'] = "Valor restante";
		$array_item['unit_price'] = (int) number_format($this->CieloPedido->valor_total * 100, 0, "", "");;
		$array_item['quantity'] = '1';
		$array['items'][] = $array_item;
		
		$array['billing']['address'] = $array_endereco;
		$array['shipping']['address'] = $array_endereco;
		$array['shipping']['delivery_date'] = date('Y-m-d');

		$array['api_key'] = $this->token_api;
		$this->jsonSend = json_encode($array);
		$this->CieloPedido->json_criacao = $this->jsonSend;
		$this->CieloPedido->save();$this->CieloPedido->pedidoPortal->id;

		$this->jsonSend = json_encode($array);
	}

	private function enviarPedidoPagamentoRestante(){
		$this->request = new \GuzzleHttp\Client(["base_uri" => $this->url_request]);
		try{
			$request = $this->request->request("post", $this->url_request."transactions",[
				"headers" => [
					"Accept" => "application/json",
					"Content-Type" => "application/json"
				],
				"body" => $this->jsonSend
			]);
			if(config("app.debug") == true){
				Log::info("enviou o pedido");
			}
			if($request->getStatusCode() === 200){
				$response = json_decode($request->getBody()->getContents(), true);
				return $this->tratarRetornoPagamentoRestante($response);
			}else{
				return response()->json([
					"status" => "error",
					"message" => "Ocorreu uma instabilidade ao fazer o pagamento! Tente novamente!",
					"error" => [],
					"response" => []
				], 422);
			}
		}catch(\Exception $e){
			return response()->json([
				"status" => "error",
				"message" => "Caro cliente infelizmente não conseguimos confirmar seu pagamento, verifique as informações em seu cartão e tente novamente mais tarde. Equipe Mn",
				"error" => [$e],
				"response" => []
			], 422);
		}
	}

	private function tratarRetornoPagamentoRestante($response){
		$this->CieloPedido->cielo_status_id = 7;
		$this->CieloPedido->json_retorno = json_encode($response);
		$this->CieloPedido->datahora_envio_pagarme = Carbon::now();
		$this->CieloPedido->save();
		
		return response()->json([
			"status" => "success",
			"message" => "Pedido confirmado, aguardando liberação de crédito",
			"error" => [],
			"response" => []
		]);
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

	public function cancelarPedidoRestante($pedidoPortal, $pedidoNasajon){
		$this->pedidoPortal = $pedidoPortal;
		$this->pedidoNasajon = $pedidoNasajon;
		$CieloPedidoObj = CieloPedido::where('pedido_id', $pedidoPortal->id)->where('pedido_nasajon_id', $pedidoNasajon->id)->get();
		$CieloPedidoObj->each(function($cielo){
			$cielo->pago = true;
			$cielo->valor_pago = 0;
			$cielo->cielo_status_id = 6;
			$cielo->save();

			$this->descloquearPedido($cielo->pedido_nasajon_id);
		});
	}

	private function mensagemErro($retorno){
		$mensagem = $this->corpoMensagemErro($retorno);
		
		$EmailObj = new EmailController();
		$email_send = [];
        $variaveis = [
            "erro" => $mensagem
        ];
		$EmailObj->sendEmailToken("00", "erro_sistema", $email_send, $variaveis);

		$CieloErroObj = new CieloErro();
		$CieloErroObj->json_enviado = json_decode($this->CieloPedido->json_criacao);
		$CieloErroObj->json_retorno = ($retorno);
		$CieloErroObj->motivo_erro = $retorno['current_status'];
		$CieloErroObj->cielo_pedido_id = $this->CieloPedido->id;
		$CieloErroObj->save();
	}

	private function corpoMensagemErro($retorno){
		$html = '';
		$html .= '<b>Pedido: </b>'.$this->CieloPedido->pedidoPortal->id.'</br>';
		$html .= '<b>Retorno Pagarme: </b>'. $this->tratandoCodigoErro($retorno).'</br>';
		$html .= '<b>json retorno: </b>'.json_encode($retorno).'</br>';
		$html .= '<b>json enviado: </b>'.$this->CieloPedido->jsonSend.'</br>';
		return $html;
	}

	public function tratarPostBack($token, $request){
		$this->CieloPedido = CieloPedido::with(['pedidoPortal'])->where('token_retorno', $token)->first();
		if(!empty($this->CieloPedido)){
			$this->CieloPedido->datahora_retorno_pagarme = Carbon::now();
			$this->CieloPedido->save();
			$fields = $request->all();
			$assinatura = $request->header('X-Hub-Signature');
			$object = $fields['object'];

			$CieloPedidoRetornoObj = new CieloPedidoRetorno;
			$CieloPedidoRetornoObj->cielo_pedido_id = $this->CieloPedido->id;
			$CieloPedidoRetornoObj->status = $fields[$object]['status'];
			$CieloPedidoRetornoObj->json_retorno = json_encode($fields);
			$CieloPedidoRetornoObj->save();

			$this->createLog('retorno_pagarme', json_encode($fields));
			if($fields[$object]['status'] === 'failed' || $fields[$object]['status'] === 'refused' || $fields['current_status'] === "refused" ){
				$this->mensagemErro($fields);
				
				$this->CieloPedido->cielo_status_id = 8;
				$this->CieloPedido->save();
				if($fields[$object]['refuse_reason'] == 'antifraud'){
					$this->enviarEmailPedidoAntifraude();
				}else{
					if(empty($this->CieloPedido->pedido_nasajon_id)){
						$this->CieloPedido->pedidoPortal->status_pedido = 9;
						$this->CieloPedido->pedidoPortal->save();
						$this->linkPedido = route("pedido_online.pagamento", ["token" => $this->CieloPedido->link_pedido]);
					}else{
						$this->linkPedido = route("pedido_online.pagamento_restante", ["token" => $this->CieloPedido->link_pedido]);
					}
					$this->enviaRecusaPedido($fields);
				}
			}else if($fields[$object]['status'] === 'success' || $fields[$object]['status'] === 'paid'){
				$this->enviaAprovacaoPedido();
                $CieloPedidoTransacaoObj = new CieloPedidoTransacao();
                $CieloPedidoTransacaoObj->cielo_pedido_id = $this->CieloPedido->id;
                $CieloPedidoTransacaoObj->id_uuid = Str::uuid();
                $CieloPedidoTransacaoObj->numero = $fields[$object]["id"];
                $CieloPedidoTransacaoObj->terminal_numero = $fields[$object]["tid"];
                $CieloPedidoTransacaoObj->codigo_autorizacao = $fields[$object]["authorization_code"] ?? "";
                $CieloPedidoTransacaoObj->valor = $fields[$object]["amount"] / 100;
                $CieloPedidoTransacaoObj->tipo_pagamento = $fields[$object]["payment_method"];
                $CieloPedidoTransacaoObj->json_retorno = json_encode($fields);
                $CieloPedidoTransacaoObj->save();
                
                $this->CieloPedido->cielo_status_id = 4;
                $this->CieloPedido->pago = true;
                $this->CieloPedido->valor_pago = $fields[$object]["amount"] / 100;
                $this->CieloPedido->save();
    
                if(empty($this->CieloPedido->pedidoPortal->pedido_gerado)){
                    $AprovacaoDePedidoControllerObj = new AprovacaoDePedidoController();
                    $AprovacaoDePedidoControllerObj->processaIntegracaoPedidoNasajon($this->CieloPedido->pedidoPortal, []);
                }
                
                if(!empty($this->pedido_nasajon_id)){
                    $this->descloquearPedido($this->CieloPedido->pedido_nasajon_id);

					$id_pagamento = '';
					$retorno_uuid_pagamento = '';

					$sql_excluir_pagamento_nasajon = "select * from integracoes.api_pedidovenda_excluirpagamento(
						'".$this->CieloPedido->pedido_nasajon_id."'
					);";  

					try{
						$sql_nasajon = DB::connection('nasajon')->select($sql_excluir_pagamento_nasajon);
						$mensagem_nasajon = $sql_nasajon[0]->mensagem;
						$mensagem_nasajon = json_decode($mensagem_nasajon, true);

						if($mensagem_nasajon['codigo'] != 'OK'){
							$erro_transacao = new StoneErroTransacoesPedido;
							$erro_transacao->erro_msg = 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_excluirpagamento" - '.$mensagem_nasajon['mensagem'];
							$erro_transacao->pedido_id = $this->CieloPedido->pedidoPortal->id;
							$erro_transacao->stone_cadastro_maquininha_id = $this->CieloPedido->pedidoPortal->pagamentosStone[0]->maquininha->id;
							$erro_transacao->created_by = 1;
							$erro_transacao->save();

							$EmailObj = new EmailController();
							$email_send = [];
							$variaveis = [
								'erro' => 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_excluirpagamento" - '.$mensagem_nasajon['mensagem']
							];
							$returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
						}

					}catch(\Exception $e){
						$erro_transacao = new StoneErroTransacoesPedido;
						$erro_transacao->erro_msg = 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_excluirpagamento" - '.(string)$e->getMessage();
						$erro_transacao->pedido_id = $this->CieloPedido->pedidoPortal->id;
						$erro_transacao->stone_cadastro_maquininha_id = $this->CieloPedido->pedidoPortal->pagamentosStone[0]->maquininha->id;
						$erro_transacao->created_by = 1;
						$erro_transacao->save();

						$EmailObj = new EmailController();
						$email_send = [];
						$variaveis = [
							'erro' => 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_excluirpagamento" - '.(string)$e->getMessage()
						];
						$returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
					}

					$tipo_pagamento = '';

                    if($this->CieloPedido->pedidoPortal->condicao_pagamento_detalhes->nasajon_parcela == '99e8ff10-c34a-4391-a735-d5d78a41d737'){
                        $tipo_pagamento = 1;
                    }else if($this->CieloPedido->pedidoPortal->condicao_pagamento_detalhes->nasajon_parcela == '2931da42-bb7b-44d5-acb7-01e26d10ca4c' || 
					$this->CieloPedido->pedidoPortal->condicao_pagamento_detalhes->nasajon_parcela == 'a755fefa-8e42-4d63-84bd-9644bfc134f5' || 
					$this->CieloPedido->pedidoPortal->condicao_pagamento_detalhes->nasajon_parcela == '3d9a8487-6239-4d03-b655-42b0cd0b00f1'){
                        $tipo_pagamento = 2;
                    }else{
                        $tipo_pagamento = 3;
                    }
					try{
						$transacao = json_decode($this->CieloPedido->transacoes->json_retorno);

						$id_operadora = CartoesOperadorasNasajon::whereIn('operadoracartao',$this->operacao_cartao_nasajon)->first();
						$id_bandeira = CartoesBandeirasNasajon::where(DB::raw("lower(codigo)"),strtolower($transacao->card_brand))->first();
						$id_meio_eletronico = CartoesMeiosEletronicosNasajon::whereIn('codigo',$this->meios_eletronicos)->first();
			
						$contrato_cartao_nasajon = CartoesContratosNasajon::where('estabelecimento',$this->CieloPedido->pedidoPortal->estabelecimentoDetalhes->estabelecimento)
						->where('bandeiracartao',$id_bandeira->bandeiracartao)
						->where('tipooperacao',$tipo_pagamento)
						->where('meioeletronicocartao',$id_meio_eletronico->meioeletronicocartao)
						->first();

						$forma_pagamento = $this->CieloPedido->pedidoPortal->pedidoNasajon->forma_pagamento->formapagamento;
						$parcelamento = $this->CieloPedido->pedidoPortal->pedidoNasajon->forma_pagamento->parcelamento;
						$valor = $this->CieloPedido->valor_pago;
					}catch(\Exception $e){$erro_transacao = new StoneErroTransacoesPedido;
						$erro_transacao->erro_msg = 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_excluirpagamento" - '.(string)$e->getMessage();
						$erro_transacao->pedido_id = $this->CieloPedido->pedidoPortal->id;
						$erro_transacao->stone_cadastro_maquininha_id = $this->CieloPedido->pedidoPortal->pagamentosStone[0]->maquininha->id;
						$erro_transacao->created_by = 1;
						$erro_transacao->save();

						$EmailObj = new EmailController();
						$email_send = [];
						$variaveis = [
							'erro' => 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_excluirpagamento" - '.(string)$e->getMessage()
						];
						$returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
					}

					$sql_registrar_pagamento = "select * from integracoes.api_pedidovenda_registrarpagamento(
						'".$this->CieloPedido->pedido_nasajon_id."',
						'".$forma_pagamento."',
						'".$parcelamento."',
						'".$valor."'
					);";   
		
					$id_pagamento = '';
		
					try{
						$sql_nasajon = DB::connection('nasajon')->select($sql_registrar_pagamento);
						$mensagem_nasajon = $sql_nasajon[0]->mensagem;
						$mensagem_nasajon = json_decode($mensagem_nasajon, true);
		
						if($mensagem_nasajon['codigo'] != 'OK'){
							$erro_transacao = new StoneErroTransacoesPedido;
							$erro_transacao->erro_msg = 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_registrarpagamento" - '.$mensagem_nasajon['mensagem'];
							$erro_transacao->pedido_id = $this->CieloPedido->pedidoPortal->id;
							$erro_transacao->stone_cadastro_maquininha_id = $this->CieloPedido->pedidoPortal->pagamentosStone[0]->maquininha->id;
							$erro_transacao->created_by = 1;
							$erro_transacao->save();

							$EmailObj = new EmailController();
							$email_send = [];
							$variaveis = [
								'erro' => 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_registrarpagamento" - '.$mensagem_nasajon['mensagem']
							];
							$returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
						}
		
						$id_pagamento = $mensagem_nasajon['mensagem'];
		
					}catch(\Exception $e){$erro_transacao = new StoneErroTransacoesPedido;
						$erro_transacao->erro_msg = 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_excluirpagamento" - '.(string)$e->getMessage();
						$erro_transacao->pedido_id = $this->CieloPedido->pedidoPortal->id;
						$erro_transacao->stone_cadastro_maquininha_id = $this->CieloPedido->pedidoPortal->pagamentosStone[0]->maquininha->id;
						$erro_transacao->created_by = 1;
						$erro_transacao->save();

						$EmailObj = new EmailController();
						$email_send = [];
						$variaveis = [
							'erro' => 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_excluirpagamento" - '.(string)$e->getMessage()
						];
						$returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
					}
	
					
					$sql_atualizar_cartao = "select * from integracoes.api_pedidovenda_atualizarcartao(
						'".$this->CieloPedido->pedido_nasajon_id."',
						'".$id_pagamento."',
						'".$this->CieloPedido->transacoes->codigo_autorizacao."',
						'".$this->CieloPedido->transacoes->created_at."',
						'".$this->CieloPedido->transacoes->numero."',
						'".$contrato_cartao_nasajon->contratocartao."',
						'".$this->cnpj_operadora."',
						'".$id_meio_eletronico->meioeletronicocartao."',
						'".$id_operadora->operadoracartao."',
						'".$id_bandeira->bandeiracartao."',
						'".(int)$tipo_pagamento."'
					);"; 
		
					try{
						$sql_atualizar_cartao = DB::connection('nasajon')->select($sql_registrar_pagamento);
						$mensagem_nasajon = $sql_atualizar_cartao[0]->mensagem;
						$mensagem_nasajon = json_decode($mensagem_nasajon, true);
						
						if($mensagem_nasajon['codigo'] != 'OK'){
							$erro_transacao = new StoneErroTransacoesPedido;
							$erro_transacao->erro_msg = 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_registrarpagamento" - '.$mensagem_nasajon['mensagem'];
							$erro_transacao->pedido_id = $this->CieloPedido->pedidoPortal->id;
							$erro_transacao->stone_cadastro_maquininha_id = $this->CieloPedido->pedidoPortal->pagamentosStone[0]->maquininha->id;
							$erro_transacao->created_by = 1;
							$erro_transacao->save();

							$EmailObj = new EmailController();
							$email_send = [];
							$variaveis = [
								'erro' => 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_registrarpagamento" - '.$mensagem_nasajon['mensagem']
							];
						}
		
						$retorno_uuid_pagamento = $mensagem_nasajon['mensagem'];
					}catch(\Exception $e){
						$erro_transacao->erro_msg = 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_excluirpagamento" - '.(string)$e->getMessage();
						$erro_transacao->pedido_id = $this->CieloPedido->pedidoPortal->id;
						$erro_transacao->stone_cadastro_maquininha_id = $this->CieloPedido->pedidoPortal->pagamentosStone[0]->maquininha->id;
						$erro_transacao->created_by = 1;
						$erro_transacao->save();

						$EmailObj = new EmailController();
						$email_send = [];
						$variaveis = [
							'erro' => 'Pedido '.$this->CieloPedido->pedidoPortal->id.' erro ao atualizar cartão no pedido distância automático, na API "api_pedidovenda_excluirpagamento" - '.(string)$e->getMessage()
						];
						$returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
					}  
                }
			}
		}
	}

	private function enviaRecusaPedido($json_retorno){
		$EmailObj = new EmailController();
		$email_send = [];
		$email_send = [$this->CieloPedido->pedidoPortal->email_comprador, $this->CieloPedido->pedidoPortal->usuario_detalhes->email];
		$nome_cliente = $this->CieloPedido->pedidoPortal->cliente->nome;
		$variaveis = [
			"nome_cliente" => $nome_cliente,
			"numero_pedido" => $this->CieloPedido->pedidoPortal->id,
			"valor_pagamento" => parserValor($this->CieloPedido->pedidoPortal->valor_total_nota),
			"motivo_recusa" => $this->tratandoCodigoErro($json_retorno),
			"link_pagamento" => $this->linkPedido
		];
		$returnEmail = $EmailObj->sendEmailToken("00", "recusa_pagamento", $email_send, $variaveis);
		$this->CieloPedido->datahora_envio_email = Carbon::now();
		$this->CieloPedido->save();
		$this->createLog('envio_email_recusa', print_r($returnEmail, true));
	}

	private function enviaAprovacaoPedido(){
		$EmailObj = new EmailController();
		$email_send = [];
		$email_send = [$this->CieloPedido->pedidoPortal->email_comprador, $this->CieloPedido->pedidoPortal->usuario_detalhes->email];
		$nome_cliente = $this->CieloPedido->pedidoPortal->cliente->nome;
		$variaveis = [
			"nome_cliente" => $nome_cliente,
			"numero_pedido" => $this->CieloPedido->pedidoPortal->id
		];
		$EmailObj->sendEmailToken("00", "aprovado_pagamento", $email_send, $variaveis);
	}
	
	public function atualizaValor($pedidoPortal, $pedidoNasajon, $valor_restante){
		$CieloPedidoObj = CieloPedido::where('pedido_id', $pedidoPortal->id)->where('pedido_nasajon_id', $pedidoNasajon->id)->first();
		if(!empty($CieloPedidoObj)){
			if($CieloPedidoObj->valor_total != $valor_restante){
				$CieloPedidoObj->valor_total = $valor_restante;
				$CieloPedidoObj->save();
			}
		}
	}

	private function tratandoCodigoErro($retorno){
		$return = '';
		switch($retorno['transaction']['refuse_reason']){
			case 'antifraud':
				$return = 'Antifraude';
			break;
			case 'acquirer':
				$PagarmeCodigoErroObj = PagarmeCodigoErro::where('codigo', $retorno['transaction']['acquirer_response_code'])->first();
				$return = $PagarmeCodigoErroObj->descricao;
			break;
		}
		return $return;
	}

	private function createLog($codigo, $envio){
		$PagarmeLogEnvioObj = new PagarmeLogEnvio();
		$PagarmeLogEnvioObj->cielo_pedido_id = $this->CieloPedido->id;
		$PagarmeLogEnvioObj->codigo = $codigo;
		$PagarmeLogEnvioObj->envio = $envio;
		$PagarmeLogEnvioObj->datahora_envio = Carbon::now();
		$PagarmeLogEnvioObj->save();
	}

	private function enviarEmailPedidoAntifraude(){
		
		$EmailObj = new EmailController();
		$email_send = [];
		$nome_cliente = $this->CieloPedido->pedidoPortal->cliente->nome;
        $variaveis = [
            "nome_cliente" => $nome_cliente,
            "numero_pedido" => $this->CieloPedido->pedidoPortal->id,
            "valor" => parserValor($this->CieloPedido->pedidoPortal->valor_total_nota),
        ];
		$returnEmail = $EmailObj->sendEmailToken("00", "pedido_sem_seguro", $email_send, $variaveis);

		$this->createLog('envio_email_seguro', print_r($returnEmail, true));
		$this->CieloPedido->save();

	}
}
