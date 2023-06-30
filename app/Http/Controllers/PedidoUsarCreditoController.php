<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\PedidoUsarCredito;
use App\NotasCreditoReceberNasajon;
use App\PedidoUsarCreditoHistoricoLiberacoes;
use App\PedidoBloqueadoPagamentoNasajon;
use App\PedidoPortal;
use App\HistoricoPedido;
use App\PedidosVendaNasajon;
use App\ClienteNasajon;
use App\GrupoEmpresarial;

use App\Http\Controllers\AprovacaoDePedidoController;
use App\Http\Controllers\PedidoCieloIntegracaoEcommerceController;
use App\Http\Controllers\EmailController;

use Illuminate\Support\Facades\DB;

class PedidoUsarCreditoController extends Controller
{
	private $pedido_tipo_pre_pago = ['pre_pago_rj_x_sp','pre_pago_triangular','pre_pago_futuro','pre_pago','pre_pago_rj_x_sp_futuro'];

    public function liberarPedidoUsarCredito(){
		$query = PedidoUsarCredito::select();
		$query->with(['pedido.condicao_pagamento_detalhes']);
		$query->where('liberado', false);
		$query->whereHas('pedido', function($query){
			$query->where('status_pedido', 9);
			$query->whereNull('deleted_at');
		});
		$result = $query->get();

		$data_atual = Carbon::now()->setTime(0, 0, 0);
        $fields = [];
        $aprovacaoDePedidoControllerObj = new AprovacaoDePedidoController();
		
		foreach($result as $verificacao){

			$cliente = ClienteNasajon::select()->where('codigo', $verificacao->pedido->cod_cliente);
			$cliente = $cliente->first();
			$cliente = $cliente->toArray();
			foreach ($cliente as $key => $value) {
				$cliente[$key] = utf8_encode($value);
			}
			$cpf_cnpj = $cliente["cpf_cnpj"];

			if(strlen(trim($cpf_cnpj)) == 18){
				$cpf_cnpj = substr($cpf_cnpj, 0, 10);
			}
	
			$grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
			->where('raiz_cnpj', $cpf_cnpj)
			->orWhereHas('participantes', function($query) use ($cpf_cnpj){
				$query->where('raiz_cnpj', $cpf_cnpj);
			})
			->first();
	
			$clientesNasajonQuery = ClienteNasajon::select('nome', 'codigo', 'cpf_cnpj', 'id');
	
			if (!is_null($grupoEmpresarialObj)){
				$clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
					if(isset($grupoEmpresarialObj->participantes)){
						foreach($grupoEmpresarialObj->participantes as $participante){
							$query->orWhere('cpf_cnpj', 'ilike', $participante->raiz_cnpj . '%');
						}
						$grupo[] = $participante->raiz_cnpj;
					}
					$query->orWhere('cpf_cnpj', 'ilike', $grupoEmpresarialObj->raiz_cnpj . '%');
				});
			}
			else {
				$clientesNasajonQuery->where('cpf_cnpj', 'ilike', $cpf_cnpj . '%');
			}
	
			unset($clientesQuery);
			$clientesNasajon = $clientesNasajonQuery->orderBy('cpf_cnpj')
			->groupBy('nome', 'codigo', 'cpf_cnpj', 'id')
			->get();

			unset($clientesNasajonQuery);

			//$estabelecimento_pad = str_pad($verificacao->pedido->estabelecimento, 2, '0', STR_PAD_LEFT);;
			$query_credito = NotasCreditoReceberNasajon::select();
			$query_credito->whereIn('cod_cliente', $clientesNasajon->pluck('codigo'));
			//$query_credito->where('codigo', $estabelecimento_pad);
			$result_credito = $query_credito->get();

			$valor_credito_total = $query_credito->sum('valor');
			
			$pedido_venda_aberto = PedidosVendaNasajon::select();
			$pedido_venda_aberto->where('rascunho', 'false');
			$pedido_venda_aberto->where('situacao_descricao', 'ilike', 'aberto');
			$pedido_venda_aberto->where(function($query){
				$query->where(function($query){
					$query->orWhereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA',])
						->orWhereNull('grupodeoperacao');
				})
				->orWhere(function($query){
					$query->whereIn('operacao_codigo', ['PEDAMOSTRA','PEDAMOSTRAGRATIS'])
						->whereHas('nota', function($query){
							$query->whereIn('operacao_codigo', ['REMESSAAMOSTRAGRATIS', 'REMESSAAMOSTRA']);
						});
				});
			});
			$pedido_venda_aberto->where('cliente_codigo', $verificacao->pedido->cod_cliente);
			$pedido_venda_aberto->whereHas('forma_pagamento', function ($query) use($verificacao){
				$query->where('formapagamento', $verificacao->pedido->condicao_pagamento_detalhes->nasajon_forma_pagamento);
			});

			$valor_pedido_aberto = $pedido_venda_aberto->sum('valor');
			$valor_pedido = in_array($verificacao->pedido->tipo_venda, $this->pedido_tipo_pre_pago)? parserNumber(parserValor($verificacao->pedido->valor_total_nota / 2)) : parserNumber(parserValor($verificacao->pedido->valor_total_nota));
			echo "Pedido: ".$verificacao->pedido->id." crédito: ".$valor_credito_total;  
			if($valor_credito_total >= (($valor_pedido_aberto + $valor_pedido) * 0.45)){ // libera com 45% do valor
				$fields['id'] = $verificacao->pedido->id;
				$return = $aprovacaoDePedidoControllerObj->processaIntegracaoPedidoNasajon($verificacao->pedido, $fields);
				
				$verificacao->liberado = true;
				$verificacao->data_liberacao = $data_atual;
				$verificacao->credito_valor = $valor_credito_total;
				if(!empty($return)){
					if($return['status'] != 'success'){
						$verificacao->nasajon_integracao_erro = true;
					}
				}
				$verificacao->updated_by = Auth::id();
				$verificacao->save();

				foreach($result_credito as $credito){
					$pedidoUsarCreditoHistoricoLiberacaoObj = new PedidoUsarCreditoHistoricoLiberacoes;
					$pedidoUsarCreditoHistoricoLiberacaoObj->pedido_usar_creditos_id = $verificacao->id; 
					$pedidoUsarCreditoHistoricoLiberacaoObj->codigo_estabelecimento = $credito->codigo;
					$pedidoUsarCreditoHistoricoLiberacaoObj->numero = $credito->numero;
					$pedidoUsarCreditoHistoricoLiberacaoObj->parcela = $credito->parcela;
					$pedidoUsarCreditoHistoricoLiberacaoObj->vencimento = $credito->vencimento;
					$pedidoUsarCreditoHistoricoLiberacaoObj->valor = $credito->valor;
					$pedidoUsarCreditoHistoricoLiberacaoObj->conta_agencia = $credito->conta_agencia;
					$pedidoUsarCreditoHistoricoLiberacaoObj->conta_agencia_digito = $credito->conta_agencia_digito;
					$pedidoUsarCreditoHistoricoLiberacaoObj->conta_numero = $credito->conta_numero;
					$pedidoUsarCreditoHistoricoLiberacaoObj->conta_digito = $credito->conta_digito;
					$pedidoUsarCreditoHistoricoLiberacaoObj->pedido_aberto_valor = $valor_pedido_aberto;
					$pedidoUsarCreditoHistoricoLiberacaoObj->save();	
				}

				if($verificacao->pedido->rj_x_sp == true){
					$pedido_transferencia = $verificacao->pedido->pedidoRjSp->pedidoTransferenciaPortal;
					$AprovacaoDePedidoControllerObj = new AprovacaoDePedidoController();
					$AprovacaoDePedidoControllerObj->processaIntegracaoPedidoNasajon($pedido_transferencia, []);
				}
			}
		}
	}

	public function monitoramentoPedido(){
		$PedidoBloqueadoPagamentoNasajonObj = PedidoBloqueadoPagamentoNasajon::with(['pedido' => function($pedido){
			$pedido->where(function($query){
				$query->where('grupodeoperacao', 'VENDA')
				->orWhere(['grupodeoperacao' => NULL]);
			});
		}, 'pedido.notaEmAberto'])
		->whereHas('pedido', function($query){
			$query->whereIn('situacao_descricao', ['Em Faturamento']);
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

		$aprovacaoDePedidoController = new AprovacaoDePedidoController;

		$PedidoPortalObj = PedidoPortal::select()
			->whereIn('condicao_pagamento', $aprovacaoDePedidoController->condicao_de_pagamento_usar_credito)
			->wherein(DB::Raw('concat(codigo_operacao, pedido_gerado, estabelecimento)'), array_values($dadosBusca))
			->where('cartao', false)
			->get();
			
		$EmailObj = new EmailController();
		$PedidoPortalObj->each(function($pedido) use ($pedidos, $EmailObj){
			$chave = $pedido->codigo_operacao.''.$pedido->pedido_gerado.''.intval($pedido->estabelecimento);
			$pedidoNasajon = $pedidos[$chave];

			$query_credito = NotasCreditoReceberNasajon::select();
			$query_credito->where('cod_cliente', $pedido->cod_cliente);
			$valor_credito_total = $query_credito->sum('valor');

			/**
			 * Calulo valor restante
			 * Pedidos com status 'Em Faturamento' - Total de credito no sistema
			 * Se o resultado for maior que o valor do pedido usar o valor do pedido como valor restante
			 */
			$pedido_venda_aberto = PedidosVendaNasajon::with(['notaEmAberto', 'nota']);
			$pedido_venda_aberto->where('rascunho', 'false');
			$pedido_venda_aberto->where('situacao_descricao', 'ilike', 'Em Faturamento');
			$pedido_venda_aberto->where(function($query){
				$query->where(function($query){
					$query->orWhereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA'])
						->orWhereNull('grupodeoperacao');
				})
				->orWhere(function($query){
					$query->whereIn('operacao_codigo', ['PEDAMOSTRA','PEDAMOSTRAGRATIS'])
						->whereHas('nota', function($query){
							$query->whereIn('operacao_codigo', ['REMESSAAMOSTRAGRATIS', 'REMESSAAMOSTRA']);
						});
				});
			});
			$pedido_venda_aberto->where('cliente_codigo', $pedido->cod_cliente);
			$pedido_venda_aberto->whereHas('forma_pagamento', function ($query){
				$query->where('formapagamento', 'f6661441-835e-41a5-88a5-9db2224aad4d');
			});
			
			$pedido_venda_aberto = $pedido_venda_aberto->get();
			$valor_pedido_aberto = 0;
			$nao_tem_nota = false;
			$pedido_venda_aberto->each(function($pedido_aberto) use (&$valor_pedido_aberto){
				if(isset($pedido_aberto->notaEmAberto->valor)){
					$valor_pedido_aberto += floatVal($pedido_aberto->notaEmAberto->valor);
				}else if(isset($pedido_aberto->nota->valor)){
					$valor_pedido_aberto += floatVal($pedido_aberto->nota->valor);
				}else{
					$valor_pedido_aberto += floatVal($pedido_aberto->valor);
					$nao_tem_nota = true;
				}
			});
			
			if($nao_tem_nota == false){
				if(isset($pedidoNasajon->notaEmAberto->valor)){
					$valor_pedido = floatVal($pedidoNasajon->notaEmAberto->valor);
				}else if(isset($pedidoNasajon->nota->valor)){
					$valor_pedido = floatVal($pedidoNasajon->nota->valor);
				}
				if(isset($valor_pedido)){
					$valor_restante = ($valor_pedido_aberto - $valor_credito_total);
		
					if($valor_restante > $valor_pedido){
						$valor_restante = $valor_pedido;
					}
					
					if($valor_restante >= 1){
						$email_send = [];
						$nome_cliente = '';
						$cliente = $pedido->cliente;
						if(!empty($pedido->email_comprador)){
							$email_send[] = $pedido->email_comprador;
						}elseif(!empty($cliente->email)){
							$email_send[] = $cliente->email;
						}
		
						$email_send[] = $pedido->usuario_detalhes->email;
		
						if(!empty($pedido->nome_comprador)){
							$nome_cliente = $pedido->nome_comprador;
						}elseif(!empty($cliente->email)){
							$nome_cliente = $cliente->nome;
						}
				
						$variaveis = [
							'nome_cliente' => $nome_cliente,
							'pedido' => $pedidoNasajon->numero,
							'valor_total' => parserValor($valor_pedido),
							'valor_diferenca' => parserValor($valor_restante),
							'valor_credito' => parserValor($valor_credito_total),
						];
		
						if(count($email_send) > 0){
							$EmailObj->sendEmailToken('00', "diferenca_usar_credito", $email_send, $variaveis);
						}
					}
					$this->descloquearPedido($pedidoNasajon->id);
				}
			}			
		});
	}

	public function cancelamentoPorInatividade(){
		$data = Carbon::now();
		$data->subHours(96);

		$pedidosInativosPagamentoUsarCredito = PedidoUsarCredito::select()
			->whereHas('pedido', function($query) use($data){
				$query->where('status_pedido', 9);
				$query->where('data_previsao_entrega', '<', $data);
			})
			->where('liberado', false)
			->where('updated_at', '<', $data)
			->get();

		$pedidosInativosPagamentoUsarCredito->each(function($pedido_usar_credito){
			$pedido_usar_credito->pedido->status_pedido = 7;
			$pedido_usar_credito->pedido->save();

			$HistoricoPedidoObj = new HistoricoPedido();
			$HistoricoPedidoObj->pedido = $pedido_usar_credito->pedido_id;
			$HistoricoPedidoObj->natureza = 'cancelamento_pagamento_pedido';
			$HistoricoPedidoObj->antigo = 'Pedido cancelado por inatividade de pagamento';
			$HistoricoPedidoObj->created_by = 1;
			$HistoricoPedidoObj->save();

			$pedido_usar_credito->deleted_by = Auth::id();
			$pedido_usar_credito->save();
			$pedido_usar_credito->delete();

		});
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
}
