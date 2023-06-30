<?php

namespace App\Http\Controllers;

use App\NetrinApi;
use Carbon\Carbon;
use App\CepEndereco;
use App\ClienteCredito;
use App\ClienteNasajon;

use App\NetrinApiRetorno;
use App\NotaVendaNasajon;
use App\TransportadorNasajon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class ClienteNetrinApiController extends Controller
{
	private $url_request = '';

	private $access_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJwZW5jIjoiZ0FBQUFBQmhWZ0Jpc0IyeTYySk8zOGkwbWpsSnR3cmdOcmhwMUIyN19JZkEyblgtY0tUM3kyZDg0Z2lLR1B5dF90cC1UMGl5YS1rZkJVRnp3bjRDN2t1OVpDYkdHT2VZQXNXWEVPVEJiN2RnU0hCUWotNDU0TTc4Z2hKTGV2cGIyQTJ0aXZIZ28zVF9WNVdyV3JLZjJueG9xQnNfTG40b2RraHRYdWpqNlVjUGJWOEpIUGpTNUM2SjcxdUQ4WkFIMmZVZDhmcEhfdHM4QzFCdkY0NTYyR1R5NWdyT1lTYXF0WHc4TW5oOXptS0FxT3pXWnZTSGgyVXZDWHdmNkVvUGpCSnBEd1FwRTJzaWF5YU55by16MW5mdTRNYjBEVk44aXc9PSIsImV4cCI6MTc5MDg3ODk0Nn0.ysXQNVqwzscDwdDt_ZTWX9JY8AJqc_R7KhqVmPYoLO4';

	private $jsonBody = [];

	private $receitaFederal = [];


	public $request_retorno = [];
	public $retorno = '';
	public $retorno_novo = '';
	public function __construct()
	{


		$this->initConfig();
	}
	private function initConfig()
	{

		$this->setUrlRequest();
		$this->receitaFederal = [
			'razaoSocial' => '',
			'nomeFantasia' => '',
			'naturezaJuridica' => '',
			'logradouro' => '',
			'numero' => '',
			'complemento' => '7',
			'bairro' => '',
			'municipio' => '',
			'cep' => '',
			'uf' => '',
			'email' => '',
			'telefone' => '',
			'efr' => '',
			'situacaoCadastral' => '',
			'dataSituacaoCadastral' => '',
			'dataInicioAtividade' => '',
			'atividadeEconomica' => '',
			'atividadesEconomicasSecundarias' => '',
			'situacaoEspecial' => '',
			'dataSituacaoEspecial' => '',
			'motivoSituacao' => '',
			'porte' => '',
			'urlComprovante' => ''

		];
	}
	private function setUrlRequest()
	{

		$this->url_request = config('netrin.url.base_uri');
		$this->jsonBody = config('netrin.usuario.producao');
	}
	public function consultaCliente($cnpj)
	{
		$cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);

		$netrinApi = NetrinApi::select()->where('cpf_cnpj', $cnpj)->where('funcao', 'cliente_novo')->first();
		$request = '';
		if (is_null($netrinApi)) {

			$netrinApi = new NetrinApi();
			$netrinApi->cpf_cnpj = $cnpj;

			$netrinApi->tipo = 'Consulta Cliente';
			$netrinApi->url = $this->url_request;
			$netrinApi->access_token = "Bearer " . $this->access_token;
			$netrinApi->created_by = Auth::user()->id;
			$netrinApi->servico = 'todos';
			$netrinApi->funcao = 'cliente_novo';
			$netrinApi->save();

			$netrinApiRetorno = new NetrinApiRetorno();
			$netrinApiRetorno->id_netrin_api = $netrinApi->id;
			try {
				$request = $this->acessaApi($cnpj);
			} catch (\Exception $e) {
				$request  = '';
			}
			if (!empty($request['retorno'])) {
				$netrinApiRetorno->json_retorno =  $request['retorno'];
				$netrinApiRetorno->status_code = $request['statusCode'];

				$netrinApiRetorno->mensagem = strval($this->tratarCodigoHttpRequest($request['statusCode']));
			}
			if (empty($netrinApiRetorno->status_code)) {
				$netrinApiRetorno->status_code = 0;
			}
			if ($netrinApiRetorno->status_code == 200) {
				$netrinApiRetorno->alerta_erro = false;
			} else {
				$netrinApiRetorno->alerta_erro = true;
			}
			$clienteNetrin = json_decode($netrinApiRetorno->json_retorno);
			if (empty($clienteNetrin->receitaFederal)) {
				$netrinApiRetorno->alerta_erro = true;
				$netrinApiRetorno->mensagem = 'RECEITA NÃO ENCONTRADA';
			} else {
				if (!empty($clienteNetrin->sintegra->mensagem) && !empty($clienteNetrin->receitaFederal->mensagem)) {
					$netrinApiRetorno->alerta_erro = true;
					$netrinApiRetorno->mensagem = 'RECEITA e SINTEGRA NÃO ENCONTRADO';
				}
			}


			$netrinApiRetorno->save();

			return $netrinApiRetorno;
		} else {

			$netrinApiRetorno = NetrinApiRetorno::select('json_retorno')->where('id_netrin_api', $netrinApi->id)->where('alerta_erro', false)->first();


			if (is_null($netrinApiRetorno)) {
				$netrinApiRetorno = new NetrinApiRetorno();
				$netrinApiRetorno->id_netrin_api = $netrinApi->id;
				try {
					$request = $this->acessaApi($cnpj);
				} catch (\Exception $e) {
					$request  = '';
				}

				if (!empty($request['retorno'])) {
					$netrinApiRetorno->json_retorno =  $request['retorno'];
					$netrinApiRetorno->status_code = $request['statusCode'];
					

					$netrinApiRetorno->mensagem = strval($this->tratarCodigoHttpRequest($request['statusCode']));
				}
				if (empty($netrinApiRetorno->status_code)) {
					$netrinApiRetorno->status_code = 0;
				}
				if ($netrinApiRetorno->status_code == 200) {
					$netrinApiRetorno->alerta_erro = false;
				} else {
					$netrinApiRetorno->alerta_erro = true;
				}
				$clienteNetrin = json_decode($netrinApiRetorno->json_retorno);
				if (empty($clienteNetrin->receitaFederal)) {
					$netrinApiRetorno->alerta_erro = true;
					$netrinApiRetorno->mensagem = 'RECEITA NÃO ENCONTRADA';
				} else {
					if (!empty($clienteNetrin->sintegra->mensagem) && !empty($clienteNetrin->receitaFedera->mensagem)) {
						$netrinApiRetorno->alerta_erro = true;
						$netrinApiRetorno->mensagem = 'RECEITA e SINTEGRA NÃO ENCONTRADO';				}
				}

				$netrinApiRetorno->save();
			}


			return $netrinApiRetorno;
		}
	}

	public function acessaApi($cnpj)
	{

		try {

			$url = $this->url_request . 'api/v1/consultacomposta/?s=receita_federal&s=sintegra&cnpj=' . $cnpj;
			return $this->jwt_request($this->access_token,$url);
			
		} catch (\Exception $e) {
			return  $e->getMessage();
		}
	}
	private function tratarCodigoHttpRequest($request)
	{
		switch ($request) {
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
			default:
				return 'erro_interno_api';
				break;
		}
	}

	public function atualizaClienteAtivoNetrin()
	{
		$retorno = '';
		$inicio_periodo = Carbon::now()->subMonths(12);

		$notaVendaNasajon = NotaVendaNasajon::select('documento_cliente', DB::RAW('max(emissao) as emissao'))
			->where(DB::RAW('LENGTH(documento_cliente)'), '>', '15')
			->where('emissao', '>=', $inicio_periodo->format('Y-m-d'))
			->groupBy('documento_cliente');

		$notaVendaNasajon = $notaVendaNasajon->get();


		foreach ($notaVendaNasajon as $nota) {

			$cnpj = preg_replace('/[^0-9]/', '', (string) $nota->documento_cliente);

			$netrinApi = NetrinApi::select('netrin_apis.id as netrin_id', '*')->where('cpf_cnpj', $cnpj)->with(['netrinApiRetorno'  => function ($query) {

				$inicio_periodo = Carbon::now()->subMonths(6);

				$query->where('created_at', '>', $inicio_periodo);
				$query->where('alerta_erro', false);
			}])->first();

			$id_netrin = '';
			$retorno = '';

			if (empty($netrinApi->netrinApiRetorno)) {

				if (empty($netrinApi)) {
					$netrinApi = new NetrinApi();
					$netrinApi->cpf_cnpj = $cnpj;

					$netrinApi->tipo = 'Consulta Cliente';
					$netrinApi->url = $this->url_request;
					$netrinApi->access_token = "Bearer " . $this->access_token;
					$netrinApi->created_by = 1;
					$netrinApi->servico = 'receita_sefaz';
					$netrinApi->funcao = 'cliente_atualiza';
					$netrinApi->save();
					$id_netrin = $netrinApi->id;
				} else {
					$id_netrin = $netrinApi->netrin_id;
				}
				$netrinApiRetorno = new NetrinApiRetorno();
				$netrinApiRetorno->id_netrin_api = $id_netrin;

				try {

					$retorno = $this->acessaApiReceitaSefaz($cnpj);
				} catch (\Exception $e) {
					$retorno = '';
				}

				if (!empty($retorno['retorno'])) {

					$netrinApiRetorno->json_retorno = $retorno['retorno'];
					$netrinApiRetorno->status_code = $retorno['statusCode'];
					$netrinApiRetorno->mensagem = strval($this->tratarCodigoHttpRequest($retorno['statusCode']));
				}
				if (empty($netrinApiRetorno->status_code)) {
					$netrinApiRetorno->status_code = 0;
				}
				if ($netrinApiRetorno->status_code == 200) {
					$netrinApiRetorno->alerta_erro = false;
				} else {
					$netrinApiRetorno->alerta_erro = true;
				}
				$clienteNetrin = json_decode($netrinApiRetorno->json_retorno);
				if (empty($clienteNetrin->receitaFederal)) {
					$netrinApiRetorno->alerta_erro = true;
					$netrinApiRetorno->mensagem = 'RECEITA NÃO ENCONTRADA';
				} else {
					if (!empty($clienteNetrin->sintegra->mensagem) && !empty($clienteNetrin->receitaFederal->mensagem)) {
						$netrinApiRetorno->alerta_erro = true;
						$netrinApiRetorno->mensagem = 'RECEITA e SINTEGRA NÃO ENCONTRADO';
					}
				}

				$netrinApiRetorno->atualizado_nasajon = false;

				$netrinApiRetorno->save();
			}
		}
	}


	public function acessaApiReceitaSefaz($cnpj)
	{

		try {

			$url = $this->url_request . 'api/v1/consultacomposta/?s=receita_federal&s=sintegra&cnpj=' . $cnpj;
			return $this->jwt_request($this->access_token,$url);
			
		} catch (\Exception $e) {
			return  $e->getMessage();
		}
	}

	public function atualizaClienteNasajon()
	{

		$netrinApiRetorno = NetrinApiRetorno::select('netrin_api_retornos.id as netrin_retorno_id', '*')->where('alerta_erro', false)->where('atualizado_nasajon', 'false')->with(['netrinApi'])->get();

		foreach ($netrinApiRetorno as $netrin) {
			$cnpj = $netrin->netrinApi->cpf_cnpj;

			$Cliente =    ClienteNasajon::select('*')->where(DB::raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), $cnpj)->first();

			$clienteNetrin = json_decode($netrin->json_retorno);
			$limite_de_credito = 0;



			if (!empty($Cliente)) {

				$raiz_cnpj = $cnpj;
				if (strlen(trim($raiz_cnpj)) == 14) {
					$raiz_cnpj = substr($raiz_cnpj, 0, 8);
				}

				$ClienteCreditoObj = ClienteCredito::where(DB::raw("replace(replace(replace(raiz_cnpj, '.', ''), '-', ''), '/', '')"), 'like', $raiz_cnpj . '%')->get();

				if (!empty($ClienteCreditoObj)) {
					$limite_de_credito = (float) $ClienteCreditoObj->sum('valor');
				}

				$id = $Cliente->id;
				$nome = str_replace('\'', '\\\'', $Cliente->nome);;
				$nomefantasia = str_replace('\'', '\\\'', $Cliente->nomefantasia);;
				$cpf_cnpj = $Cliente->cpf_cnpj;

				$tipologradouro = $Cliente->tipologradouro;
				$logradouro = str_replace('\'', '\\\'', $Cliente->logradouro);
				$numero = $Cliente->numero;
				$complemento = str_replace('\'', '\\\'', $Cliente->complemento);
				$cep = $Cliente->cep;
				$bairro = str_replace('\'', '\\\'', $Cliente->bairro);
				$uf = $Cliente->uf;
				$ibge = $Cliente->ibge;
				$cidade = str_replace('\'', '\\\'', $Cliente->cidade);
				$inscricaoestadual = $Cliente->inscricaoestadual;
				$email = $Cliente->email;
				$ddd = $Cliente->ddd;
				$telefone = $Cliente->telefones;

				$telefone = '';
				$ddd = '';

				if (!empty($clienteNetrin->sintegra->inscricoesEstaduais)) {

					if ($clienteNetrin->sintegra->inscricoesEstaduais[0]->situacaoCadastral == 'INATIVO') {
						$indicadorinscricaoestadual = 9;
					} else {
						$indicadorinscricaoestadual = 1;
					}
				} else {
					$indicadorinscricaoestadual = 9;
				}
			} else {
				$transportadorNasajon = TransportadorNasajon::where(DB::raw("replace(replace(replace(cnpj, '.', ''), '-', ''), '/', '')"), $cnpj)->first();
				if (!empty($transportadorNasajon)) {
					$enderecoObj = CepEndereco::with('cidadeBusca')->where('cep', str_replace('-', '', $transportadorNasajon->cep))->first();
					if (!empty($enderecoObj)) {
						$ibge = $enderecoObj->cidadeBusca->cod_ibge;
						$tipologradouro = $enderecoObj->tipo_logradouro;
					} else {
						$ibge = '';
						$tipologradouro = '';
					}


					$id = $transportadorNasajon->id;
					$nome = $transportadorNasajon->nome;
					$nomefantasia = $transportadorNasajon->nomefantasia;
					$cpf_cnpj = $transportadorNasajon->cnpj;
					$indicadorinscricaoestadual = 9;

					$logradouro = $transportadorNasajon->endereco;
					$numero = $transportadorNasajon->numero;
					$complemento = $transportadorNasajon->complemento;
					$cep = $transportadorNasajon->cep;
					$bairro = $transportadorNasajon->bairro;
					$uf = $transportadorNasajon->uf;

					$cidade = $transportadorNasajon->cidade;
					$inscricaoestadual = $transportadorNasajon->inscricaoestadual;
					$email = $transportadorNasajon->email;
					$telefone = '';
					$ddd = '';


					if (!empty($clienteNetrin->receitaFederal)) {


						$cep = str_replace([".", ",", "/"], "", $clienteNetrin->receitaFederal->cep);
						$logradouro = $clienteNetrin->receitaFederal->logradouro;
						$numero = $clienteNetrin->receitaFederal->numero;
						$bairro = $clienteNetrin->receitaFederal->bairro;
						$cidade =  $clienteNetrin->receitaFederal->municipio;
						$uf = $clienteNetrin->receitaFederal->uf;
						$telefone = $clienteNetrin->receitaFederal->telefone;
						$email = $clienteNetrin->receitaFederal->email;
					}

					if (!empty($clienteNetrin->sintegra->inscricoesEstaduais)) {
						$inscricaoestadual = $clienteNetrin->sintegra->inscricoesEstaduais[0]->inscricaoEstadual;
						if ($clienteNetrin->sintegra->inscricoesEstaduais[0]->situacaoCadastral == 'INATIVO') {
							$indicadorinscricaoestadual = 9;
						} else {
							$indicadorinscricaoestadual = 1;
						}
					} else {
						$indicadorinscricaoestadual = 9;
					}
				}
			}

			if (!empty($Cliente) ||	!empty($transportadorNasajon)) {


				$result = DB::connection('nasajon')->select("SELECT * from integracoes.api_clientealterar(
                        '" . $id . "',
                        E'" . $nome . "',
                        E'" . $nomefantasia . "',
                        '" . $cpf_cnpj . "',
                        '" . $inscricaoestadual . "',
                        '',
                        '" . $email . "',
                        $limite_de_credito,
                        '" . $tipologradouro . "',
                        E'" . $logradouro . "',
                        '" . $numero . "',
                        E'" . $complemento . "',
                        '" . $cep . "',
                        E'" . $bairro . "',
                        '" . $uf . "',
                        '" . $ibge . "',
                        E'" . $cidade . "',
                        '',
                        '" . $ddd . "',
                        '" .  $telefone . "',
                        '" . $indicadorinscricaoestadual . "'
                    )");




				$netrinApiRetornoObj = NetrinApiRetorno::find($netrin->netrin_retorno_id);


				$netrinApiRetornoObj->atualizado_nasajon = true;
				$netrinApiRetornoObj->save();
			}
		}
	}



	public function consultaClientePedido($pedido,$codigo_cliente_balcao)
	{

	
		if(in_array($pedido->cliente->codigo,$codigo_cliente_balcao)){
			return true;
		}
		
		$retorno = '';
		$inicio_periodo = Carbon::now()->subDays(15);


		$cnpj = preg_replace('/[^0-9]/', '', (string) $pedido->cliente->cpf_cnpj);


		$netrinApi = NetrinApi::select('netrin_apis.id as netrin_id', '*')->where('cpf_cnpj', $cnpj)->with(['netrinApiRetorno'  => function ($query) {

			$inicio_periodo = Carbon::now()->subDays(15);
			$query->where('created_at', '>', $inicio_periodo);
			$query->where('alerta_erro', false);
		}])->first();

		$id_netrin = '';
		$retorno = '';

		if (empty($netrinApi->netrinApiRetorno)) {


			if (empty($netrinApi)) {
				$netrinApi = new NetrinApi();
				$netrinApi->cpf_cnpj = $cnpj;

				$netrinApi->tipo = 'Consulta Cliente';
				$netrinApi->url = $this->url_request;
				$netrinApi->access_token = "Bearer " . $this->access_token;
				$netrinApi->created_by = Auth::id();
				$netrinApi->servico = 'receita_sefaz';
				$netrinApi->funcao = 'pedido_aprova';
				$netrinApi->save();
				$id_netrin = $netrinApi->id;
			} else {
				$id_netrin = $netrinApi->netrin_id;
			}
			$netrinApiRetorno = new NetrinApiRetorno();
			$netrinApiRetorno->atualizado_nasajon = false;
			$netrinApiRetorno->id_netrin_api = $id_netrin;

			try {

				$retorno = $this->acessaApiReceitaSefaz($cnpj);
			} catch (\Exception $e) {
				$retorno = '';
			}

			if (!empty($retorno['retorno'])) {

				$netrinApiRetorno->json_retorno = json_decode($retorno['retorno']);
				$netrinApiRetorno->status_code = $retorno['statusCode'];
				$netrinApiRetorno->mensagem = strval($this->tratarCodigoHttpRequest($retorno['statusCode']));
			}
			if (empty($netrinApiRetorno->status_code)) {
				$netrinApiRetorno->status_code = 0;
			}
			if ($netrinApiRetorno->status_code == 200) {
				$netrinApiRetorno->alerta_erro = false;
			} else {
				$netrinApiRetorno->alerta_erro = true;
			}
			$clienteNetrin = json_decode($netrinApiRetorno->json_retorno);
			if (empty($clienteNetrin->receitaFederal)) {
				$netrinApiRetorno->alerta_erro = true;
				$netrinApiRetorno->mensagem = 'RECEITA NÃO ENCONTRADA';
				$netrinApiRetorno->save();
				$emailControllerObj = new EmailController;
				$mail_result = $emailControllerObj->sendEmailToken('01', 'erro_api_netrin', ['ti_contratos@tecidosmn.com.br'], ['erro' => $netrinApiRetorno->mensagem]);
			} else {
				if (!empty($clienteNetrin->sintegra->mensagem) && !empty($clienteNetrin->receitaFederal->mensagem)) {
					$netrinApiRetorno->alerta_erro = true;
					$netrinApiRetorno->mensagem = 'RECEITA e SINTEGRA NÃO ENCONTRADO';
					$netrinApiRetorno->save();
					$emailControllerObj = new EmailController;
					$mail_result = $emailControllerObj->sendEmailToken('01', 'erro_api_netrin', ['ti_contratos@tecidosmn.com.br'], ['erro' => $netrinApiRetorno->mensagem]);
				}
			}




			if (!empty($retorno)) {
				$status_api_nasajon = $this->atualizaClientePedido($netrinApiRetorno, $cnpj);
				if ($status_api_nasajon) {
					$netrinApiRetorno->atualizado_nasajon = true;
					$netrinApiRetorno->save();
				}
				return $netrinApiRetorno->mensagem;
			}
		} else {

			return $netrinApi->netrinApiRetorno->mensagem;
		}
	}

	private function atualizaClientePedido($netrinApiRetorno, $cnpj)
	{


		if (!empty($netrinApiRetorno)) {


			$Cliente =    ClienteNasajon::select('*')->where(DB::raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), $cnpj)->first();

			$clienteNetrin = json_decode($netrinApiRetorno->json_retorno);
			$limite_de_credito = 0;

			if (!empty($Cliente)) {

				$raiz_cnpj = $cnpj;
				if (strlen(trim($raiz_cnpj)) == 14) {
					$raiz_cnpj = substr($raiz_cnpj, 0, 8);
				}

				$ClienteCreditoObj = ClienteCredito::where(DB::raw("replace(replace(replace(raiz_cnpj, '.', ''), '-', ''), '/', '')"), 'like', $raiz_cnpj . '%')->get();

				if (!empty($ClienteCreditoObj)) {
					$limite_de_credito = (float) $ClienteCreditoObj->sum('valor');
				}



				if (!empty($Cliente)) {
					if (!empty($clienteNetrin->receitaFederal)) {
						$response = [
							"nome_razao" => $clienteNetrin->receitaFederal->razaoSocial,
							"guerra_apelido" => $clienteNetrin->receitaFederal->nomeFantasia,
							"cep" => str_replace([".", ",", "/"], "", $clienteNetrin->receitaFedera->cep),
							"logradouro" => $clienteNetrin->receitaFederal->logradouro,
							"numero" => $clienteNetrin->receitaFederal->numero,
							"tipo_logradouro" => '',
							"complemento" => $clienteNetrin->receitaFederal->complemento,
							"local" => '',
							"bairro" => $clienteNetrin->receitaFederal->bairro,
							"cidade" =>  $clienteNetrin->receitaFederal->municipio,
							"uf" => $clienteNetrin->receitaFederal->uf,
							"telefone" => $clienteNetrin->receitaFederal->telefone,
							"email" => $clienteNetrin->receitaFederal->email,
							"socios" => '',
							"inscricao_estadual" => '',
							"inscricao_estadual_indicador" => '',
							"tem_suframa" => '',
							"situacao_cadastral" => $clienteNetrin->receitaFederal->situacaoCadastral
						];
					}

					if (!empty($clienteNetrin->receitaFederal->qsa)) {
						$response['socios']  = $clienteNetrin->receitaFederal->qsa;
					}

					if (!empty($clienteNetrin->sintegra->inscricoesEstaduais)) {
						if ($clienteNetrin->sintegra->inscricoesEstaduais[0]->situacaoCadastral == 'INATIVO') {
							$response['inscricao_estadual_indicador'] = 9;
						} else {
							$response['inscricao_estadual_indicador'] = 1;
						}
					} else {
						$response['inscricao_estadual_indicador'] = 9;
					}
					if (!empty($clienteNetrin->suframa->inscricoesEstaduais)) {
						$response['tem_suframa'] = $clienteNetrin->suframa->inscricoesEstaduais[0]->inscricaoEstadual;
					}
					if (!empty($response['email'])) {
						if (!filter_var($response['email'], FILTER_VALIDATE_EMAIL)) {
							$response['email'] = '';
						}
					}
					if (!empty($response['telefone'])) {
						if (strlen($response['telefone']) > 20) {
							$response['telefone'] = substr($response['telefone'], 0, 20);
						}
					}
					$sql_api = "SELECT * from integracoes.api_clientealterar(
						'" . $Cliente->id . "',
						E'" .  $response['nome_razao'] . "',
						E'" .  $response['guerra_apelido'] . "',
						'" .  $Cliente->cpf_cnpj . "',
						'" .  $Cliente->inscricaoestadual. "',
						'',
						'" .  $response['email'] . "',
						$limite_de_credito,
						'" .  $response['tipo_logradouro'] . "',
						E'" . $response['logradouro'] . "',
						'" .  $response['numero'] . "',
						E'" . $response['complemento'] . "',
						'" . $response['cep'] . "',
						E'" .  $response['bairro'] . "',
						'" .  $response['uf'] . "',
						'" .  $Cliente->ibge . "',
						E'" .  $response['cidade'] . "',
						'',
						'" . $Cliente->ddd . "',
						'" .   $response['telefone'] . "',
						'" .  $response['inscricao_estadual_indicador'] . "'
					)";

					try {
						$result = DB::connection('nasajon')->select($sql_api);
						$atualizado_nasajon = true;
					} catch (\Exception $e) {
						$netrinApiRetorno->mensagem .'' .$e;
						$emailControllerObj = new EmailController;
						$atualizado_nasajon = false;
						$mail_result = $emailControllerObj->sendEmailToken('01', 'erro_api_netrin', ['ti_contratos@tecidosmn.com.br'], ['erro' => $netrinApiRetorno->mensagem]);
						$atualizado_nasajon = false;
			
					}




					$netrinApiRetornoObj = NetrinApiRetorno::find($netrinApiRetorno->id_netrin_api);
					$netrinApiRetornoObj->mensagem =$netrinApiRetorno->mensagem;
					$netrinApiRetornoObj->atualizado_nasajon= $atualizado_nasajon;
					
					$netrinApiRetornoObj->save();
					return true;
				} else {
					return false;
				}
			}
		}
	}


	public function consultaTransportadorPedido($pedido,$codigo_cliente_balcao)
	{

	
		if(in_array($pedido->cliente->codigo,$codigo_cliente_balcao) || empty($pedido->detalhesTransportador) ){
			return true;
		}
		
		$retorno = '';
		$inicio_periodo = Carbon::now()->subDays(15);


		$cnpj = preg_replace('/[^0-9]/', '', (string) $pedido->detalhesTransportador->cnpj);


		$netrinApi = NetrinApi::select('netrin_apis.id as netrin_id', '*')->where('cpf_cnpj', $cnpj)->with(['netrinApiRetorno'  => function ($query) {

			$inicio_periodo = Carbon::now()->subDays(15);
			$query->where('created_at', '>', $inicio_periodo);
			$query->where('alerta_erro', false);
		}])->first();

		$id_netrin = '';
		$retorno = '';

		if (empty($netrinApi->netrinApiRetorno)) {


			if (empty($netrinApi)) {
				$netrinApi = new NetrinApi();
				$netrinApi->cpf_cnpj = $cnpj;

				$netrinApi->tipo = 'Consulta Cliente';
				$netrinApi->url = $this->url_request;
				$netrinApi->access_token = "Bearer " . $this->access_token;
				$netrinApi->created_by = Auth::id();
				$netrinApi->servico = 'receita_sefaz';
				$netrinApi->funcao = 'pedido_aprova';
				$netrinApi->save();
				$id_netrin = $netrinApi->id;
			} else {
				$id_netrin = $netrinApi->netrin_id;
			}
			$netrinApiRetorno = new NetrinApiRetorno();
			$netrinApiRetorno->atualizado_nasajon = false;
			$netrinApiRetorno->id_netrin_api = $id_netrin;

			try {

				$retorno = $this->acessaApiReceitaSefaz($cnpj);
			} catch (\Exception $e) {
				$retorno = '';
			}

			if (!empty($retorno['retorno'])) {

				$netrinApiRetorno->json_retorno = $retorno['retorno'];
				$netrinApiRetorno->status_code = $retorno['statusCode'];
				$netrinApiRetorno->mensagem = strval($this->tratarCodigoHttpRequest($retorno['statusCode']));
			}
			if (empty($netrinApiRetorno->status_code)) {
				$netrinApiRetorno->status_code = 0;
			}
			if ($netrinApiRetorno->status_code == 200) {
				$netrinApiRetorno->alerta_erro = false;
			} else {
				$netrinApiRetorno->alerta_erro = true;
			}
			$clienteNetrin = json_decode($netrinApiRetorno->json_retorno);
			if (empty($clienteNetrin->receitaFederal)) {
				$netrinApiRetorno->alerta_erro = true;
				$netrinApiRetorno->mensagem = 'RECEITA NÃO ENCONTRADA';
				$netrinApiRetorno->save();
				$emailControllerObj = new EmailController;
				$mail_result = $emailControllerObj->sendEmailToken('01', 'erro_api_netrin', ['ti_contratos@tecidosmn.com.br'], ['erro' => $netrinApiRetorno->mensagem]);
			} else {
				if (!empty($clienteNetrin->sintegra->mensagem) && !empty($clienteNetrin->receitaFederal->mensagem)) {
					$netrinApiRetorno->alerta_erro = true;
					$netrinApiRetorno->mensagem = 'RECEITA e SINTEGRA NÃO ENCONTRADO';
					$netrinApiRetorno->save();
					$emailControllerObj = new EmailController;
					$mail_result = $emailControllerObj->sendEmailToken('01', 'erro_api_netrin', ['ti_contratos@tecidosmn.com.br'], ['erro' => $netrinApiRetorno->mensagem]);
				}
			}




			if (!empty($retorno)) {
				$status_api_nasajon = $this->atualizaTransportadorPedido($netrinApiRetorno, $cnpj);
				if ($status_api_nasajon) {
					$netrinApiRetorno->atualizado_nasajon = true;
					$netrinApiRetorno->save();
				}
				return $netrinApiRetorno->mensagem;
			}
		} else {

			return $netrinApi->netrinApiRetorno->mensagem;
		}
	}

	private function atualizaTransportadorPedido($netrinApiRetorno, $cnpj)
	{


		if (!empty($netrinApiRetorno)) {

			$transportadorNasajon = TransportadorNasajon::where(DB::raw("replace(replace(replace(cnpj, '.', ''), '-', ''), '/', '')"), $cnpj)->first();
			

			$clienteNetrin = json_decode($netrinApiRetorno->json_retorno);
			$limite_de_credito = 0;

			if (!empty($transportadorNasajon)) {

				$raiz_cnpj = $cnpj;
				if (strlen(trim($raiz_cnpj)) == 14) {
					$raiz_cnpj = substr($raiz_cnpj, 0, 8);
				}

				$ClienteCreditoObj = ClienteCredito::where(DB::raw("replace(replace(replace(raiz_cnpj, '.', ''), '-', ''), '/', '')"), 'like', $raiz_cnpj . '%')->get();

				if (!empty($ClienteCreditoObj)) {
					$limite_de_credito = (float) $ClienteCreditoObj->sum('valor');
				}



				if (!empty($transportadorNasajon)) {
					if (!empty($clienteNetrin->receitaFederal)) {
						$response = [
							"nome_razao" => $clienteNetrin->receitaFederal->razaoSocial,
							"guerra_apelido" => $clienteNetrin->receitaFederal->nomeFantasia,
							"cep" => str_replace([".", ",", "/"], "", $clienteNetrin->receitaFederal->cep),
							"logradouro" => $clienteNetrin->receitaFederal->logradouro,
							"numero" => $clienteNetrin->receitaFederal->numero,
							"tipo_logradouro" => '',
							"complemento" => $clienteNetrin->receitaFederal->complemento,
							"local" => '',
							"bairro" => $clienteNetrin->receitaFederal->bairro,
							"cidade" =>  $clienteNetrin->receitaFederal->municipio,
							"uf" => $clienteNetrin->receitaFederal->uf,
							"telefone" => $clienteNetrin->receitaFederal->telefone,
							"email" => $clienteNetrin->receitaFederal->email,
							"socios" => '',
							"inscricao_estadual" => '',
							"inscricao_estadual_indicador" => '',
							"tem_suframa" => '',
							"situacao_cadastral" => $clienteNetrin->receitaFederal->situacaoCadastral
						];
					}

					if (!empty($clienteNetrin->receitaFederal->qsa)) {
						$response['socios']  = $clienteNetrin->receitaFederal->qsa;
					}

					if (!empty($clienteNetrin->sintegra->inscricoesEstaduais)) {
						$response->inscricao_estadual = $clienteNetrin->sintegra->inscricoesEstaduais[0]->inscricaoEstadual;
						if ($clienteNetrin->sintegra->inscricoesEstaduais[0]->situacaoCadastral == 'INATIVO') {
							$response['inscricao_estadual_indicador'] = 9;
						} else {
							$response['inscricao_estadual_indicador'] = 1;
						}
					} else {
						$response['inscricao_estadual_indicador'] = 9;
					}
					if (!empty($clienteNetrin->suframa->inscricoesEstaduais)) {
						$response['tem_suframa'] = $clienteNetrin->suframa->inscricoesEstaduais[0]->inscricaoEstadual;
					}
					if (!empty($response['email'])) {
						if (!filter_var($response['email'], FILTER_VALIDATE_EMAIL)) {
							$response['email'] = '';
						}
					}
					if (!empty($response['telefone'])) {
						if (strlen($response['telefone']) > 20) {
							$response['telefone'] = substr($response['telefone'], 0, 20);
						}
					}
					$sql_api = "SELECT * from integracoes.api_clientealterar(
						'" . $transportadorNasajon->id . "',
						E'" .  $response['nome_razao'] . "',
						E'" .  $response['guerra_apelido'] . "',
						'" .  $transportadorNasajon->cnpj . "',
						'" .  $response['inscricao_estadual'] . "',
						'',
						'" .  $response['email'] . "',
						$limite_de_credito,
						'" .  $response['tipo_logradouro'] . "',
						E'" . $response['logradouro'] . "',
						'" .  $response['numero'] . "',
						E'" . $response['complemento'] . "',
						'" . $response['cep'] . "',
						E'" .  $response['bairro'] . "',
						'" .  $response['uf'] . "',
						'" .  $transportadorNasajon->ibge . "',
						E'" .  $response['cidade'] . "',
						'',
						'" . $transportadorNasajon->ddd . "',
						'" .   $response['telefone'] . "',
						'" .  $response['inscricao_estadual_indicador'] . "'
					)";

					try {
						$result = DB::connection('nasajon')->select($sql_api);
						$atualizado_nasajon = true;
					} catch (\Exception $e) {
						$netrinApiRetorno->mensagem .'' .$e;
						$emailControllerObj = new EmailController;
						$atualizado_nasajon = false;
						$mail_result = $emailControllerObj->sendEmailToken('01', 'erro_api_netrin', ['ti_contratos@tecidosmn.com.br'], ['erro' => $netrinApiRetorno->mensagem]);
						$atualizado_nasajon = false;
			
					}




					$netrinApiRetornoObj = NetrinApiRetorno::find($netrinApiRetorno->id_netrin_api);
					$netrinApiRetornoObj->mensagem =$netrinApiRetorno->mensagem;
					$netrinApiRetornoObj->atualizado_nasajon= $atualizado_nasajon;
					
					$netrinApiRetornoObj->save();
					return true;
				} else {
					return false;
				}
			}
		}
	}

	private function jwt_request($token, $url){
		header('Accept: application/json'); // Specify the type of data
			$ch = curl_init($url); // Initialise cURL
			$authorization = "Authorization: Bearer ".$token; // Prepare the authorisation token
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json' , $authorization )); // Inject the token into the header
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	// Will return the response, if false it print the response
			$result = curl_exec($ch); // Execute the cURL statement
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Status Code
			curl_close($ch); // Close the cURL connection

			$dados = [
				'retorno' => $result,
				'statusCode' => $httpCode
			];

			return $dados;
	}
}
