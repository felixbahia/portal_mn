<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

use App\PedidoRjSp;
use App\PedidoPortal;
use App\PedidoItemPortal;
use App\PedidosVendaNasajon;
use App\ProdutoEspecificacao;
use App\ClienteNasajon;
use App\HistoricoPedido;

use App\Http\Controllers\EmailController;
use Illuminate\Support\Facades\Artisan;

class PedidoRjSpController extends Controller{
	private $cliente_transferencia_cnpj = '06.311.274/0007-73';

	public $condicao_de_pagamento_usar_credito = [4202, 4221, 4247];

	public function processarPedidos(){
		$PedidoRjSpObj = PedidoRjSp::with(['pedidoPortal'])->whereNull('pedido_transferencia_id')->whereNull('deleted_at')->get();
		$PedidoRjSpObj->each(function($pedido){
			$retorno = $this->gerarPedidos($pedido->pedidoPortal);
			$pedido_transferencia = PedidosVendaNasajon::where('numero', $retorno['transferencia']->pedido_gerado)->where('estabelecimento_codigo', $retorno['transferencia']->estabelecimento_pad)->where('operacao_codigo', $retorno['transferencia']->codigo_operacao)->first();
			$pedido->pedido_transferencia_id = $retorno['transferencia']->id;
			$pedido->pedido_nasajon_transferencia_id = $pedido_transferencia->id;
			$pedido->save();
		});
	}
    public function gerarPedidos(PedidoPortal $pedidoPortal){

		$pedido_transferencia = $this->gerarPedidoTransferencia($pedidoPortal);
		$pedido_futuro = $this->transformarPedidoFuturo($pedidoPortal);

		return ['transferencia' => $pedido_transferencia, 'futuro' => $pedido_futuro];
	}

	private function gerarPedidoTransferencia(PedidoPortal $pedidoPortal){
		$ClienteNasajonObj = ClienteNasajon::where('cpf_cnpj', $this->cliente_transferencia_cnpj)->where('bloqueado', false)->first();
		$itens = [];
		$pedidoPortal->itens_pedido->each(function($item) use ($pedidoPortal, &$itens){

			$produtoObj = ProdutoEspecificacao::with(['custos', 'preco'])->where('codigo_produto', $item->cod_produto)->first();
			$custo = 0;
            //$custo = $produtoObj->custos->where('estabelecimento', str_pad($pedidoPortal->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
			$custo = $produtoObj->estoque->where('estabelecimento', str_pad($pedidoPortal->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
			$custo_portal = $produtoObj->custos->where('estabelecimento', str_pad($pedidoPortal->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
			if(empty($custo) && empty($custo_portal)){
				$custo = $produtoObj->preco->preco_real / 1.43;
			}else if(!empty($custo) && !empty($custo_portal)){
				if($custo->custo > $custo_portal->custo_medio_contabil){
					$custo = $custo->custo;
				}else if(empty($custo->custo) && empty($custo_portal->custo_medio_contabil)){
					$custo = $produtoObj->preco->preco_real / 1.43;
				}else{
					$custo = $custo_portal->custo_medio_contabil;
				}
			}else if(!empty($custo) && empty($custo_portal)){
				$custo = empty($custo->custo)? $produtoObj->preco->preco_real / 1.43 : $custo->custo;
			}else if(empty($custo) && !empty($custo_portal)){
				$custo = empty($custo_portal->custo_medio_contabil)? $produtoObj->preco->preco_real / 1.43 : $custo_portal->custo_medio_contabil;
			}else{
				$custo = $custo->custo;
			}
            /*if(empty($custo) || empty($custo->custo_medio_contabil)){
                $custo = $produtoObj->preco->preco_real / 1.43;
            }else{
                $custo = $custo->custo_medio_contabil;
            }*/

            /**
             * Regra preço transferencia
             */
            /*switch($pedidoPortal->estabelecimento){
                case '3':
                    $custo = $custo / 0.96;
                break;
                case '4':
                    if(in_array($produtoObj->procedencia, [0, 3, 4, 5])){
                        $custo = $custo / 0.88;
                    }else{
                        $custo = $custo / 0.96;
                    }
                break;
                default:
                    switch($pedidoPortal->cliente->uf){
                        case 'TO':
                        case 'RO':
                            $custo = $custo / 0.93;
                        break;
                        default:
                            $custo = $custo / 0.82;
                        break;
                    }
                break;
            }*/

			$itens[$item->cod_produto] = [
				'codigo' => $item->cod_produto,
				'quantidade' => $item->quantidade,
				'preco' => $custo,
				'preco_total' => ($custo * $item->quantidade)
			];
		});

		$PedidoPortalNovo = new PedidoPortal();
		$PedidoPortalNovo->data_pedido = Carbon::now()->format('Y-m-d');
		$PedidoPortalNovo->usuario = 1;
		$PedidoPortalNovo->estabelecimento = $pedidoPortal->estabelecimento;
		$PedidoPortalNovo->cod_cliente = $ClienteNasajonObj->codigo;
		$PedidoPortalNovo->nome_comprador = $ClienteNasajonObj->nome;
		$PedidoPortalNovo->email_comprador = $ClienteNasajonObj->email;
		$PedidoPortalNovo->status_pedido = 2;
		$PedidoPortalNovo->pedido_futuro = false;
		$PedidoPortalNovo->data_previsao_entrega = Carbon::now()->format('Y-m-d');
		$PedidoPortalNovo->tipo_frete = 'P';
		$PedidoPortalNovo->observacao = '';
		$PedidoPortalNovo->transportadora = $pedidoPortal->transportadora;
		$PedidoPortalNovo->transportadora_redespacho = '';
		$PedidoPortalNovo->updated_by = 1;
		$PedidoPortalNovo->created_by = 1;
		$PedidoPortalNovo->tipo_venda = 'pronta_entrega_venda';
		$PedidoPortalNovo->tabela_tipo_preco = 'fob';
		$PedidoPortalNovo->frete_preco = 'fob';
		$PedidoPortalNovo->nasajon = true;
		$PedidoPortalNovo->save();

		foreach($itens as $item){
			$PedidoItemPortalObj = new PedidoItemPortal();
			$PedidoItemPortalObj->pedido = $PedidoPortalNovo->id;
			$PedidoItemPortalObj->usuario = 1;
			$PedidoItemPortalObj->cod_produto = $item['codigo'];
			$PedidoItemPortalObj->quantidade = $item['quantidade'];
			$PedidoItemPortalObj->preco_unitario = $item['preco'];
			$PedidoItemPortalObj->created_by = 1;
			$PedidoItemPortalObj->updated_by = 1;
			$PedidoItemPortalObj->save();
		}

		$AprovacaoDePedidoControllerObj = new AprovacaoDePedidoController();

		$AprovacaoDePedidoControllerObj->processaIntegracaoPedidoNasajon($PedidoPortalNovo, []);
		
		return $PedidoPortalNovo;
	}
	
	private function transformarPedidoFuturo(PedidoPortal $pedidoPortal){
		$pedidoPortal->estabelecimento = '07';
		$pedidoPortal->data_previsao_entrega = Carbon::now();
		$pedidoPortal->status_pedido = 8;
		$pedidoPortal->pedido_futuro = true;
		if($pedidoPortal->tipo_venda == 'rj_x_sp_triangular'){
			$pedidoPortal->tipo_venda = 'pedido_futuro_triangular';
		}else if($pedidoPortal->tipo_venda == 'pre_pago_rj_x_sp'){
			$pedidoPortal->tipo_venda = 'pre_pago_futuro';
		}else{
			$pedidoPortal->tipo_venda = 'pedido_futuro_venda';
		}
		$pedidoPortal->save();


		$historicoPedidoObj = new HistoricoPedido();
		$historicoPedidoObj->pedido = $pedidoPortal->id;
		$historicoPedidoObj->natureza = 'pedido_rj_sp';
		$historicoPedidoObj->novo = 'Pedido mudado para pedido futuro devido a operação RJ SP';
		$historicoPedidoObj->created_by = 1;
		$historicoPedidoObj->save();

		return $pedidoPortal;

	}

	public function atualizarLiberar(){
		$PedidoRjSpObj = PedidoRjSp::with(['pedidoTransferenciaNasajon', 'pedidoTransferenciaNasajon.itens_pedido' , 'pedidoPortal', 'pedidoPortal.itens_pedido', 'pedidoPortal.pedidoNasajon', 'pedidoTransferenciaNasajon.transportador'])->where('liberado', false)->whereNull('deleted_at')->get();
		$PedidoRjSpObj->each(function($pedido){
			if(!empty($pedido->pedidoTransferenciaNasajon)){
				if($pedido->pedidoTransferenciaNasajon->situacao_descricao == 'Faturado'){
					$this->atualizaQuantidadePedido($pedido);
					if($pedido->email_transportadora == false){
						$this->emailTransferencia($pedido);
					}
					if(empty($pedido->pedidoPortal->pedidoNasajon)){
						if($pedido->pedidoPortal->status_pedido == 8){
							$validação = $this->verificaQuantidadePedido($pedido->pedidoPortal);
							if($validação == true){
								$this->liberarPedidoFuturo($pedido->pedidoPortal);
							}
						}
					}else{
						$pedido->pedido_nasajon_venda_id = $pedido->pedidoPortal->pedidoNasajon->id;
						$pedido->save();
					}
				}
				if(!empty($pedido->pedidoPortal->pedidoNasajon)){
					if($pedido->pedidoPortal->pedidoNasajon->situacao_descricao == 'Faturado'){
						if($pedido->email_pedido == false){
							$this->emailPedidoVenda($pedido);
						}
					}
				}
			}
		});
	}

	private function atualizaQuantidadePedido(PedidoRjSp $PedidoRjSp){
		$PedidoRjSp->pedidoTransferenciaNasajon->itens_pedido->each(function($item) use ($PedidoRjSp){
			if(!empty($item->quantidade_faturada)){
				$item_venda = $PedidoRjSp->pedidoPortal->itens_pedido->firstWhere('cod_produto', $item->produto_codigo);
				$item_venda->quantidade = $item->quantidade_faturada;
				$item_venda->save();
			}
		});
	}

	private function verificaQuantidadePedido(PedidoPortal $pedidoPortal){
		$return = true;
		$pedidoPortal->itens_pedido->each(function($item) use (&$return){
			$estoque = 0;
			$estoques = $item->especificacoes->estoque->firstWhere('estabelecimento', '07');
			if(!empty($estoques)){
				$estoque = $estoques->estoque;
			}
			if(floatval($item->quantidade) > $estoque){
				$return = false;
			}
		});
		return $return;
	}

	private function liberarPedidoFuturo(PedidoPortal $pedidoPortal){
		$historicoPedidoObj = new HistoricoPedido;
		$historicoPedidoObj->pedido = $pedidoPortal->id;
		$historicoPedidoObj->novo = '';
		$historicoPedidoObj->natureza = 'Pedido programado aprovado';
		$historicoPedidoObj->created_by = 1;
		$historicoPedidoObj->save();

		$pedidoPortal->data_previsao_entrega = Carbon::now();
		$pedidoPortal->pedido_futuro = false;
		$pedidoPortal->status_pedido = 2;
		$pedidoPortal->save();

		Artisan::queue('pedido:validacao', ['pedido' => $pedidoPortal->id]);
	}

	public function emailTransferencia(PedidoRjSp $PedidoRjSp){
		$EmailObj = new EmailController();
		$email_send = [];
		//$email_send = [$PedidoRjSp->pedidoTransferenciaNasajon->transportador->email];

		$variaveis = [
			'numero_nota' => $PedidoRjSp->pedidoTransferenciaNasajon->notafiscal_numero,
		];
		$EmailObj->sendEmailToken('00', "pedido_rj_sp_transferencia", $email_send, $variaveis);

		$PedidoRjSp->email_transportadora = true;
		$PedidoRjSp->save();
	}

	public function emailPedidoVenda(PedidoRjSp $PedidoRjSp){
		$EmailObj = new EmailController();
		$email_send = [];
		//$email_send = [$PedidoRjSp->pedidoTransferenciaNasajon->transportador->email];

		$variaveis = [
			'nota_transferencia' => $PedidoRjSp->pedidoTransferenciaNasajon->notafiscal_numero,
			'nota_venda' => $PedidoRjSp->pedidoPortal->pedidoNasajon->notafiscal_numero
		];
		$EmailObj->sendEmailToken('00', "pedido_rj_sp_pedido", $email_send, $variaveis);

		$PedidoRjSp->email_pedido = true;
		$PedidoRjSp->liberado = true;
		$PedidoRjSp->save();
	}
}
