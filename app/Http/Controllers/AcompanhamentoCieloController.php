<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;

use App\CieloPedido;
use App\HistoricoPedido;
use App\PedidoPortal;
use App\PagarmeCodigoErro;
use App\CieloAutenticao;
use App\CieloPedidosEstorno;
use App\StoneTransacoesPedido;

use App\Http\Requests\PagarmeEstornoSalvarRequest;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\EmailController;
use Exception;

class AcompanhamentoCieloController extends Controller
{
    private $dias_atraso = 1;

	private $url_request = "";

	private $token_api = "";

	private $condicao_de_pagamento_usar_credito = [4202, 4221, 4247];

	public function __construct(PedidoPortal $pedidoPortal = null){
            $this->setUrlRequest();
	}

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AcompanhamentoCielo") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\AcompanhamentoCielo');
        return view('programs.acompanhamento_cielo.index')->with(['dias_atraso' => $this->dias_atraso]);
    }

	private function setUrlRequest(){
		$this->url_request = config("pagarme.url");
	}

    public function filtro(Request $request){
		set_time_limit('300');

        $fields = $request->only(['estabelecimento', 'pedido', 'forma_pagamento','data_de', 'data_ate']);
		
        $CieloPedidoObj = CieloPedido::with(['erros', 'pedidoPortal', 'pedidoPortal.pedidoNasajon']);
        $CieloPedidoObj->where('liberado', 'false');
        $CieloPedidoObj->whereNotIn('cielo_status_id', [5, 6]);

		$pedidos_stone_presencial = StoneTransacoesPedido::with('pedido.pedidoNasajon')
		->whereHas('pedido',function($query){
			$query->whereNotIn('status_pedido',[5,7,13]);
		});

		$transacoes_finalizadas = StoneTransacoesPedido::where(function($query){
			$query->where('status_pre_transacao','1')
			->orWhereNotNull('pagamento_parcial');
		})
        ->select('pedido_id');

		$pedidos_stone_nao_processados = StoneTransacoesPedido::with('pedido')->where(function($query){
			$query->where('status_pre_transacao','0')
			->orWhereNull('pagamento_parcial');
		})
		->whereHas('pedido',function($query){
			$query->whereNotIn('status_pedido',[5,7,13]);
		});


        if(!empty($fields['estabelecimento'])){
            $CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
                $portal->where('estabelecimento', $fields['estabelecimento']);
                $portal->where('status_pedido', '!=', 7);
            });
			$pedidos_stone_presencial->whereHas('pedido',function($query) use ($fields){
				$query->where("estabelecimento",str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
			});
			$pedidos_stone_nao_processados->whereHas('pedido',function($query) use ($fields){
				$query->where("estabelecimento",str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
			});
			
        }else{
            $CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
                $portal->where('status_pedido', '!=', 7);
            });
        }

		if(!empty($fields['pedido'])){
            $CieloPedidoObj->where('pedido_id', $fields['pedido']);
			$pedidos_stone_presencial->where('pedido_id',$fields['pedido']);
			$pedidos_stone_nao_processados->where('pedido_id',$fields['pedido']);
        }

		if(!empty($fields['forma_pagamento'])){
			if($fields['forma_pagamento'] == 'usar_creditos'){
				$CieloPedidoObj->whereHas('pedidoPortal',function($query){
					$query->whereNull('pix_id');
				});
				$pedidos_stone_presencial->whereNull('id');
				$pedidos_stone_nao_processados->whereNull('id');
			}

			if($fields['forma_pagamento'] == 'pix'){
				$CieloPedidoObj->whereHas('pedidoPortal',function($query){
					$query->whereNotNull('pix_id');
				});
				$pedidos_stone_presencial->whereNull('id');
				$pedidos_stone_nao_processados->whereNull('id');
			}

			if($fields['forma_pagamento'] == 'stone'){
				$CieloPedidoObj->whereNull('id');
			}

			if($fields['forma_pagamento'] == 'pagar_me'){
				$pedidos_stone_presencial->whereNull('id');
				$pedidos_stone_nao_processados->whereNull('id');
				$CieloPedidoObj->whereHas('pedidoPortal',function($query){
					$query->where('presencial',false);
				});
			}
        }

        if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $CieloPedidoObj->where('created_at', '>=', $data);
			$pedidos_stone_presencial->where('created_at', '>=', $data);
			$pedidos_stone_nao_processados->where('created_at', '>=', $data);
			$transacoes_finalizadas->where('created_at', '>=', $data);
        }

        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $CieloPedidoObj->where('created_at', '<=', $data);
			$pedidos_stone_presencial->where('created_at', '<=', $data);
			$pedidos_stone_nao_processados->where('created_at', '<=', $data);
			$transacoes_finalizadas->where('created_at', '<=', $data);
        }

		$transacoes_finalizadas = $transacoes_finalizadas->get()
        ->pluck('pedido_id')
        ->toArray();

		$pedidos_stone_nao_processados->whereNotIn('pedido_id',$transacoes_finalizadas);
		
		$CieloPedidoObj = $CieloPedidoObj->get();

		$pedidos_venda = PedidoPortal::with('pedidoNasajon')
		->whereIn('condicao_pagamento',$this->condicao_de_pagamento_usar_credito)
		->whereNotIn('status_pedido',[5,7,13]);

		if(!empty($fields['pedido'])){
            $pedidos_venda->where('id', $fields['pedido']);
        }
		if(!empty($fields['estabelecimento'])){
            $pedidos_venda->where('estabelecimento', str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
        }
		if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $pedidos_venda->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $pedidos_venda->where('created_at', '<=', $data);
        }

		if(!empty($fields['forma_pagamento'])){
			if($fields['forma_pagamento'] == 'pix'){
				$pedidos_venda->whereNotNull('pix_id');
			}
			if($fields['forma_pagamento'] == 'pagar_me'){
				$pedidos_venda->whereNull('id');
			}
			if($fields['forma_pagamento'] == 'stone'){
				$pedidos_venda->whereNull('id');
			}
			if($fields['forma_pagamento'] == 'usar_creditos'){
				$pedidos_venda->whereNull('pix_id');
			}
		}

		$pedidos_venda = $pedidos_venda->get();
		$pedidos_stone_presencial = $pedidos_stone_presencial->get();
		$pedidos_stone_nao_processados = $pedidos_stone_nao_processados->get();
		
		$retorno = [
			'a_pagar_principal' => [
				'pedido' => 0,
				'valor' => 0,
			],
			'a_pagar_diferenca' => [
				'pedido' => 0,
				'valor' => 0,
			],
			'erro' => [
				'pedido' => 0,
				'valor' => 0,
			],
			'credito_sem_nota' => [
				'pedido' => 0,
				'valor' => 0,
				'p' => [],
			],
			'debito_sem_nota' => [
				'pedido' => 0,
				'valor' => 0,
				'p' => [],
			],
			'credito_com_nota' => [
				'pedido' => 0,
				'valor' => 0,
				'p' => [],
			],
			'debito_com_nota' => [
				'pedido' => 0,
				'valor' => 0,
				'p' => [],
			],
			'usar_credito_sem_nota' => [
				'pedido' => 0,
				'valor' => 0,
			],
			'usar_Credito_com_nota' => [
				'pedido' => 0,
				'valor' => 0,
			],
			'credito_preencial_sem_nota' => [
				'pedido' => 0,
				'valor' => 0,
				'p' => [],
			],
			'debito_presencial_sem_nota' => [
				'pedido' => 0,
				'valor' => 0,
				'p' => [],
			],
			'credito_presencial_com_nota' => [
				'pedido' => 0,
				'valor' => 0,
				'p' => [],
			],
			'debito_presencial_com_nota' => [
				'pedido' => 0,
				'valor' => 0,
				'p' => [],
			],
			'usar_Credito_com_nota' => [
				'pedido' => 0,
				'valor' => 0,
				'p' => [],
			],
			'usar_credito_sem_nota' => [
				'pedido' => 0,
				'valor' => 0,
				'p' => [],
			],
		];

		$pedidos_stone_nao_processados->each(function($query) use (&$retorno){
			$retorno['erro']['pedido'] ++;
			$retorno['erro']['valor'] += $query->pedido->valor_total_produtos;
		});

		$pedidos_stone_presencial->each(function($query) use (&$retorno){
			$com_nota = false;
			if(isset($query->pedido->pedidoNasajon->situacao_descricao) &&  $query->pedido->pedidoNasajon->situacao_descricao == 'Faturado'){
				$com_nota = true;
			}

			if($com_nota == true && $query->payment_type == 1){
				$retorno['debito_presencial_com_nota']['pedido'] ++;
				$retorno['debito_presencial_com_nota']['valor'] += $query->transaction_amount;
			}else if($com_nota == true && $query->payment_type == 2 || $com_nota == true && $query->payment_type == 3){
				$retorno['credito_presencial_com_nota']['pedido'] ++;
				$retorno['credito_presencial_com_nota']['valor'] += $query->transaction_amount;
			}

			if($com_nota == false && $query->payment_type == 1){
				$retorno['debito_presencial_sem_nota']['pedido'] ++;
				$retorno['debito_presencial_sem_nota']['valor'] += $query->transaction_amount;
			}else if($com_nota == false && $query->payment_type == 2 || $com_nota == false && $query->payment_type == 3){
				$retorno['credito_preencial_sem_nota']['pedido'] ++;
				$retorno['credito_preencial_sem_nota']['valor'] += $query->transaction_amount;
			}

		});

		$pedidos_venda->each(function($query) use (&$retorno){
			$com_nota = false;
			if(isset($query->pedidoNasajon->situacao_descricao) &&  $query->pedidoNasajon->situacao_descricao == 'Faturado'){
				$com_nota = true;
			}

			if($com_nota == true){
				$retorno['usar_Credito_com_nota']['pedido'] ++;
				$retorno['usar_Credito_com_nota']['valor'] += $query->valor_total_produtos;
			}else if($com_nota == false){
				$retorno['usar_credito_sem_nota']['pedido'] ++;
				$retorno['usar_credito_sem_nota']['valor'] += $query->valor_total_produtos;
			}

		});
		
        $CieloPedidoObj->each(function($cielo) use (&$retorno){

			if($cielo->pedidoPortal->presencial == false){
				if($cielo->pago == false){
					if(empty($cielo->pedido_nasajon_id)){
						$retorno['a_pagar_principal']['pedido'] += 1;
						$retorno['a_pagar_principal']['valor'] += $cielo->valor_total;
					}else{
						$retorno['a_pagar_diferenca']['pedido'] += 1;
						$retorno['a_pagar_diferenca']['valor'] += $cielo->valor_total;
					}

					if(count($cielo->erros) > 0){
						$retorno['erro']['pedido'] += 1;
						$retorno['erro']['valor'] += $cielo->valor_total;
					}
				}else{
					$tipo_pagamento = '';
					switch($cielo->pedidoPortal->condicao_pagamento){
						case '4193':
						case '4194':
						case '4195':
						case '4196':
						case '4197':
						case '4198':
						case '4199':
						case '4213':
							$tipo_pagamento = 'credito';
							break;
						case '4201':
						case '4214':
							$tipo_pagamento = 'debito';
						break;
					}
					if(!empty($cielo->pedidoPortal->pedidoNasajon)){
						$key_retorno = '';
						if($cielo->pedidoPortal->pedidoNasajon->situacao_descricao == 'Faturado'){
							$key_retorno = $tipo_pagamento.'_com_nota';
						}else{
							$key_retorno = $tipo_pagamento.'_sem_nota';
						}
						$retorno[$key_retorno]['pedido'] += 1;
						$retorno[$key_retorno]['valor'] += $cielo->valor_pago;
						$retorno[$key_retorno]['p'][] = $cielo;
					}else{
						$key_retorno = $tipo_pagamento.'_sem_nota';
						$retorno[$key_retorno]['pedido'] += 1;
						$retorno[$key_retorno]['valor'] += $cielo->valor_pago;
						$retorno[$key_retorno]['p'][] = $cielo;
					}
				}
			}else{
				if($cielo->pago == false){
					if(empty($cielo->pedido_nasajon_id)){
						$retorno['a_pagar_principal']['pedido'] += 1;
						$retorno['a_pagar_principal']['valor'] += $cielo->valor_total;
					}else{
						$retorno['a_pagar_diferenca']['pedido'] += 1;
						$retorno['a_pagar_diferenca']['valor'] += $cielo->valor_total;
					}

					if(count($cielo->erros) > 0){
						$retorno['erro']['pedido'] += 1;
						$retorno['erro']['valor'] += $cielo->valor_total;
					}
				}else{
					$tipo_pagamento = '';
					switch($cielo->pedidoPortal->condicao_pagamento){
						case '4193':
						case '4194':
						case '4195':
						case '4196':
						case '4197':
						case '4198':
						case '4199':
						case '4213':
							$tipo_pagamento = 'credito_preencial';
							break;
						case '4201':
						case '4214':
							$tipo_pagamento = 'debito_presencial';
						break;
					}
					if(!empty($cielo->pedidoPortal->pedidoNasajon)){
						$key_retorno = '';
						if($cielo->pedidoPortal->pedidoNasajon->situacao_descricao == 'Faturado'){
							$key_retorno = $tipo_pagamento.'_com_nota';
						}else{
							$key_retorno = $tipo_pagamento.'_sem_nota';
						}
						$retorno[$key_retorno]['pedido'] += 1;
						$retorno[$key_retorno]['valor'] += $cielo->valor_pago;
						$retorno[$key_retorno]['p'][] = $cielo;
					}else{
						$key_retorno = $tipo_pagamento.'_sem_nota';
						$retorno[$key_retorno]['pedido'] += 1;
						$retorno[$key_retorno]['valor'] += $cielo->valor_pago;
						$retorno[$key_retorno]['p'][] = $cielo;
					}
				}
			}

        });

        foreach($retorno as $key => $value){
            if(empty($value['pedido'])){
                $retorno[$key]['pedido'] = '';
            }
            if(empty($value['valor'])){
                $retorno[$key]['valor'] = '';
            }else{
                $retorno[$key]['valor'] = parserValor($value['valor']);
            }
        }

        return response()->json([
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ]);
    }
    
	public function modalEmAberto(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
		$fields = $request->only(['estabelecimento', 'pedido', 'data_de', 'data_ate']);
		$CieloPedidoObj = CieloPedido::with(['pedidoPortal', 'pedidoPortal.clienteSemBloqueio']);
		$CieloPedidoObj->where('liberado', 'false');
		$CieloPedidoObj->where('pago', 'false');
		$CieloPedidoObj->whereNull('pedido_nasajon_id');
        $CieloPedidoObj->whereNotIn('cielo_status_id', [5, 6]);
        if(!empty($fields['estabelecimento'])){
            $CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
                $portal->where('estabelecimento', $fields['estabelecimento']);
                $portal->where('status_pedido', '!=', 7);
            });
        }else{
            $CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
                $portal->where('status_pedido', '!=', 7);
            });
        }
		if(!empty($fields['pedido'])){
            $CieloPedidoObj->where('pedido_id', $fields['pedido']);
        }
        if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $CieloPedidoObj->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $CieloPedidoObj->where('created_at', '<=', $data);
        }
		$CieloPedidoObj = $CieloPedidoObj->get();

		$retorno = [];

        $estabelecimentos = returnEmpresasNasajonView();
		$CieloPedidoObj->each(function($pedido) use (&$retorno, $estabelecimentos){
            $cliente = $pedido->pedidoPortal->cod_cliente;
            if(!empty($pedido->pedidoPortal->clienteSemBloqueio)){
                $cliente = $pedido->pedidoPortal->clienteSemBloqueio->nome . ' - ' . $pedido->pedidoPortal->clienteSemBloqueio->cpf_cnpj;
            }
			$retorno[] = [
				'estabelecimento' => $estabelecimentos[$pedido->pedidoPortal->estabelecimento],
                'presencial' => $pedido->pedidoPortal->presencial ? 'sim' : 'não',
                'presencial_bool' => $pedido->pedidoPortal->presencial,
				'pedido' => $pedido->pedidoPortal->id,
				'cliente' => $cliente,
				'valor_total' => parserValor($pedido->valor_total),
				'valor_pago' => parserValor($pedido->valor_pago),
				'link_pedido' => route("pedido_online.pagamento", ["token" => $pedido->link_pedido]),
                'cancelar_pedido' => true
			];
		});
        return view('programs.acompanhamento_cielo.modal.vencido')->with(['pedidos' => $retorno]);
    }
    
	public function modalEmAbertoDiferenca(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
		$fields = $request->only(['estabelecimento', 'data_de', 'data_ate']);
		$CieloPedidoObj = CieloPedido::with(['pedidoPortal', 'pedidoPortal.clienteSemBloqueio']);
		$CieloPedidoObj->where('liberado', 'false');
		$CieloPedidoObj->where('pago', 'false');
        $CieloPedidoObj->whereNotIn('cielo_status_id', [5, 6]);
		$CieloPedidoObj->whereNotNull('pedido_nasajon_id');
        $CieloPedidoObj->has('pedidoPortal');
		if(!empty($fields['estabelecimento'])){
			$CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
				$portal->where('estabelecimento', $fields['estabelecimento']);
			});
		}
		if(!empty($fields['pedido'])){
            $CieloPedidoObj->where('pedido_id', $fields['pedido']);
        }
        if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $CieloPedidoObj->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $CieloPedidoObj->where('created_at', '<=', $data);
        }
		$CieloPedidoObj = $CieloPedidoObj->get();

		$retorno = [];

        $estabelecimentos = returnEmpresasNasajonView();
		$CieloPedidoObj->each(function($pedido) use (&$retorno, $estabelecimentos){
			$retorno[] = [
				'estabelecimento' => $estabelecimentos[$pedido->pedidoPortal->estabelecimento],
                'presencial' => $pedido->pedidoPortal->presencial ? 'sim' : 'não',
                'presencial_bool' => $pedido->pedidoPortal->presencial,
				'pedido' => $pedido->pedidoPortal->id,
				'cliente' => $pedido->pedidoPortal->clienteSemBloqueio->nome . ' - ' . $pedido->pedidoPortal->clienteSemBloqueio->cpf_cnpj,
				'valor_total' => parserValor($pedido->valor_total),
				'valor_pago' => parserValor($pedido->valor_pago),
                'link_pedido' => route("pedido_online.pagamento", ["token" => $pedido->link_pedido]),
                'cancelar_pedido' => false
			];
		});
        return view('programs.acompanhamento_cielo.modal.vencido')->with(['pedidos' => $retorno]);
	}
    
	public function modalPago(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
		$fields = $request->only(['estabelecimento', 'data_de', 'data_ate']);
		$CieloPedidoObj = CieloPedido::with(['pedidoPortal', 'pedidoPortal.clienteSemBloqueio', 'transacoes']);
		$CieloPedidoObj->where('pago', 'true');
        $CieloPedidoObj->whereNotIn('cielo_status_id', [5, 6]);
		$CieloPedidoObj->whereNull('pedido_nasajon_id');
		if(!empty($fields['estabelecimento'])){
			$CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
				$portal->where('estabelecimento', $fields['estabelecimento']);
			});
		}
        if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $CieloPedidoObj->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $CieloPedidoObj->where('created_at', '<=', $data);
        }
		$CieloPedidoObj = $CieloPedidoObj->get();

		$retorno = [];
        $estabelecimentos = returnEmpresasNasajonView();

		$CieloPedidoObj->each(function($pedido) use (&$retorno, $estabelecimentos){
            $transacoes = $pedido->transacoes;

            $terminal_numero = '';
            $codigo_autorizacao = '';
            $nsu = '';
            $numero_cartao = '';
            $bandeira_cartao = '';
            $tipo_pagamento = '';
            $forma_pagamento = '';

            if(!empty($transacoes)){
                $transacoes_json = json_decode($transacoes->json_retorno, true);
                $terminal_numero = $transacoes->terminal_numero;
                $codigo_autorizacao = $transacoes->codigo_autorizacao;
                $nsu = $transacoes->numero;
                if(!empty($transacoes_json)){
                    if($pedido->lio == true){
                        $numero_cartao = $transacoes_json['card']['mask'];
                        $bandeira_cartao = $transacoes_json['card']['brand'];
                        if(isset($transacoes_json['payment_fields']['primary_product_name'])){
                            $tipo_pagamento = $transacoes_json['payment_fields']['primary_product_name'];
                        }else{
                            $tipo_pagamento = $transacoes_json['payment_fields']['primaryProductName'];
                        }
                        if(isset($transacoes_json['payment_fields']['secondary_product_name'])){
                            $forma_pagamento = $transacoes_json['payment_fields']['secondary_product_name'];
                        }else{
                            $forma_pagamento = $transacoes_json['payment_fields']['secondaryProductName'];
                        }

                    }else{
                        if(isset($transacoes_json['Payment'])){
                            $numero_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['CardNumber'];
                            $bandeira_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['Brand'];
                            if($transacoes_json['Payment']['Type'] === 'CreditCard'){
                                $tipo_pagamento = 'Crédito';
                            }else{
                                $tipo_pagamento = 'Debito';
    
                            }
                        }else{
							$numero_cartao = '********'.$transacoes_json['transaction']['card_last_digits'];
							$bandeira_cartao = $transacoes_json['transaction']['card_brand'];
							if($transacoes_json['transaction']['payment_method'] === 'credit_card'){
								$tipo_pagamento = 'Crédito';
							}else{
								$tipo_pagamento = 'Debito';
							}
                        }
                    }
                }
            }
			$retorno[] = [
				'estabelecimento' => $estabelecimentos[$pedido->pedidoPortal->estabelecimento],
				'presencial' => $pedido->pedidoPortal->presencial ? 'sim' : 'não',
				'pedido' => $pedido->pedidoPortal->id,
				'cliente' => $pedido->pedidoPortal->clienteSemBloqueio->nome . ' - ' . $pedido->pedidoPortal->clienteSemBloqueio->cpf_cnpj,
				'valor_total' => parserValor($pedido->valor_total),
				'valor_pago' => parserValor($pedido->valor_pago),
                'link_pedido' => route("pedido_online.pagamento", ["token" => $pedido->link_pedido]),
                'terminal_numero' => $terminal_numero,
                'codigo_autorizacao' => $codigo_autorizacao,
                'nsu' => $nsu,
                'numero_cartao' => $numero_cartao,
                'bandeira_cartao' => $bandeira_cartao,
                'tipo_pagamento' => $tipo_pagamento,
                'forma_pagamento' => $forma_pagamento,
			];
		});
        return view('programs.acompanhamento_cielo.modal.pagos')->with(['pedidos' => $retorno]);
    }
    
	public function modalPagoDiferenca(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
		$fields = $request->only(['estabelecimento', 'data_de', 'data_ate']);
		$CieloPedidoObj = CieloPedido::with(['pedidoPortal', 'pedidoPortal.clienteSemBloqueio', 'transacoes']);
		$CieloPedidoObj->where('pago', 'true');
		$CieloPedidoObj->whereNotIn('cielo_status_id', [6]);
		$CieloPedidoObj->whereNotNull('pedido_nasajon_id');
        $CieloPedidoObj->has('pedidoPortal');
		if(!empty($fields['estabelecimento'])){
			$CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
				$portal->where('estabelecimento', $fields['estabelecimento']);
			});
		}
        if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $CieloPedidoObj->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $CieloPedidoObj->where('created_at', '<=', $data);
        }
		$CieloPedidoObj = $CieloPedidoObj->get();

		$retorno = [];
        $estabelecimentos = returnEmpresasNasajonView();

		$CieloPedidoObj->each(function($pedido) use (&$retorno, $estabelecimentos){
            $transacoes = $pedido->transacoes;

            $terminal_numero = '';
            $codigo_autorizacao = '';
            $nsu = '';
            $numero_cartao = '';
            $bandeira_cartao = '';
            $tipo_pagamento = '';
            $forma_pagamento = '';

            if(!empty($transacoes)){
                $transacoes_json = json_decode($transacoes->json_retorno, true);
                $terminal_numero = $transacoes->terminal_numero;
                $codigo_autorizacao = $transacoes->codigo_autorizacao;
                $nsu = $transacoes->numero;
                if(!empty($transacoes_json)){
                    if($pedido->lio == true){
                        $numero_cartao = $transacoes_json['card']['mask'];
                        $bandeira_cartao = $transacoes_json['card']['brand'];

                        if(isset($transacoes_json['payment_fields']['primary_product_name'])){
                            $tipo_pagamento = $transacoes_json['payment_fields']['primary_product_name'];
                        }else{
                            $tipo_pagamento = $transacoes_json['payment_fields']['primaryProductName'];
                        }
                        if(isset($transacoes_json['payment_fields']['secondary_product_name'])){
                            $forma_pagamento = $transacoes_json['payment_fields']['secondary_product_name'];
                        }else{
                            $forma_pagamento = $transacoes_json['payment_fields']['secondaryProductName'];
                        }


                    }else{
                        if(isset($transacoes_json['Payment'])){
                            $numero_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['CardNumber'];
                            $bandeira_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['Brand'];
                            if($transacoes_json['Payment']['Type'] === 'CreditCard'){
                                $tipo_pagamento = 'Crédito';
                            }else{
                                $tipo_pagamento = 'Debito';
    
                            }
                        }else{
							$numero_cartao = '********'.$transacoes_json['transaction']['card_last_digits'];
							$bandeira_cartao = $transacoes_json['transaction']['card_brand'];
							if($transacoes_json['transaction']['payment_method'] === 'credit_card'){
								$tipo_pagamento = 'Crédito';
							}else{
								$tipo_pagamento = 'Debito';
							}
                        }

                    }
                }
            }
			$retorno[] = [
				'estabelecimento' => $estabelecimentos[$pedido->pedidoPortal->estabelecimento],
				'presencial' => $pedido->pedidoPortal->presencial ? 'sim' : 'não',
				'pedido' => $pedido->pedidoPortal->id,
				'cliente' => $pedido->pedidoPortal->clienteSemBloqueio->nome . ' - ' . $pedido->pedidoPortal->clienteSemBloqueio->cpf_cnpj,
				'valor_total' => parserValor($pedido->valor_total),
				'valor_pago' => parserValor($pedido->valor_pago),
                'link_pedido' => route("pedido_online.pagamento", ["token" => $pedido->link_pedido]),
                'terminal_numero' => $terminal_numero,
                'codigo_autorizacao' => $codigo_autorizacao,
                'nsu' => $nsu,
                'numero_cartao' => $numero_cartao,
                'bandeira_cartao' => $bandeira_cartao,
                'tipo_pagamento' => $tipo_pagamento,
                'forma_pagamento' => $forma_pagamento,
			];
		});
        return view('programs.acompanhamento_cielo.modal.pagos')->with(['pedidos' => $retorno]);
    }

    public function cancelarPedido(Request $request){
		$fields = $request->only(['pedido']);
        $CieloPedidoObj = CieloPedido::with(['pedidoPortal', 'pedidoPortal']);
        $CieloPedidoObj->where('pedido_id', $fields['pedido']);
		$CieloPedidoObj->where('pago', 'false');
		$CieloPedidoObj->whereNull('pedido_nasajon_id');
        $CieloPedidoObj = $CieloPedidoObj->first();
        if(empty($CieloPedidoObj)){
			//Pedido Pago não pode ser cancelado
			$HistoricoPedidoObj = new HistoricoPedido();
			$HistoricoPedidoObj->pedido = $fields['pedido'];
			$HistoricoPedidoObj->natureza = 'cancelamento_cartao';
			$HistoricoPedidoObj->antigo = 'Pedido cancelado com cartão de credito';
			$HistoricoPedidoObj->created_by = 1;
			$HistoricoPedidoObj->save();

            return response()->json([
				"status" => 'success',
				"message" => 'Pedido cancelado',
				"error" => [],
				"response" => []
			]);
        }
        if($CieloPedidoObj->lio == true){
            $PedidoCieloIntegracaoLioController = new PedidoCieloIntegracaoLioController(new PedidoPortal([]));
            $PedidoCieloIntegracaoLioController->excliurPedido($CieloPedidoObj->id);
        }

        $CieloPedidoObj->pedidoPortal->deleted_by = Auth::id();
        $CieloPedidoObj->pedidoPortal->save();

        $HistoricoPedidoObj = new HistoricoPedido();
        $HistoricoPedidoObj->pedido = $CieloPedidoObj->pedido_id;
        $HistoricoPedidoObj->natureza = 'cancelamento_cartao';
        $HistoricoPedidoObj->antigo = 'Pedido cancelado com cartão de credito';
        $HistoricoPedidoObj->created_by = 1;
        $HistoricoPedidoObj->save();
        
        $CieloPedidoObj->pedidoPortal->delete();

        return response()->json([
            "status" => 'success',
            "message" => 'Pedido cancelado',
            "error" => [],
            "response" => []
        ]);
        
    }

    public function liberarPedido(Request $request){
		$fields = $request->only(['pedido']);
		$CieloPedidoObj = CieloPedido::with('pedidoPortal')->where('pedido_id', $fields['pedido'])->where('pago', false)->get();
		$CieloPedidoObj->each(function($cielo){
			$cielo->pago = true;
			$cielo->valor_pago = $cielo->valor_total;
			$cielo->cielo_status_id = 3;
            $cielo->save();
            
			if(!empty($cielo->pedido_nasajon_id)){
				$this->descloquearPedido($cielo->pedido_nasajon_id);
			}else{
				$AprovacaoDePedidoControllerObj = new AprovacaoDePedidoController();
				$AprovacaoDePedidoControllerObj->processaIntegracaoPedidoNasajon($cielo->pedidoPortal, []);
			}
			if($cielo->lio == true){
                $PedidoCieloIntegracaoLioController = new PedidoCieloIntegracaoLioController(new PedidoPortal([]));
                $PedidoCieloIntegracaoLioController->excliurPedido($cielo->id);
			}
		});
        return response()->json([
            "status" => 'success',
            "message" => 'Pedido liberado',
            "error" => [],
            "response" => []
        ],200);
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
    
    public function enviarEmail(Request $request){
		$fields = $request->only(['pedido']);
        $CieloPedidoObj = CieloPedido::with('pedidoPortal')->where('pedido_id', $fields['pedido'])->where('ecommerce', true)->where('pago', false)->get();
        $CieloPedidoObj->each(function($cielo){
            if(empty($cielo->pedido_nasajon_id)){
                $linkPedido = route("pedido_online.pagamento", ["token" => $cielo->link_pedido]);
                $this->enviaPedido($cielo->pedidoPortal, $cielo->valor_total, $linkPedido);
            }else{
                $linkPedido = route("pedido_online.pagamento_restante", ["token" => $cielo->link_pedido]);
                $this->enviaPedidoRestante($cielo->pedidoPortal, $cielo->valor_total, $linkPedido);
            }
        });
        return response()->json([
            "status" => 'success',
            "message" => 'E-mail enviado novamente',
            "error" => [],
            "response" => []
        ]);
    }
	private function enviaPedido($pedido, $valor, $link){
		$EmailObj = new EmailController();
		$email_send = [];
		$email_send = [$pedido->email_comprador];
		$nome_cliente = $pedido->cliente->nome;
        $variaveis = [
            "nome_cliente" => $nome_cliente,
            "numero_pedido" => $pedido->id,
            "valor_pagamento" => parserValor($valor),
            "condicao_pagamento" => $pedido->condicao_pagamento_detalhes->descricao,
            "representante" => $pedido->usuario_detalhes->codigo_representante.' - '.$pedido->usuario_detalhes->name,
            "link_pagamento" => $link
        ];
		$returnEmail = $EmailObj->sendEmailToken("00", "pagamento_pedido", $email_send, $variaveis);
    }

	private function enviaPedidoRestante($pedido, $valor, $link){
		$EmailObj = new EmailController();
		$email_send = [];
		$email_send = [$pedido->email_comprador];
		$nome_cliente = $pedido->cliente->nome;
        $variaveis = [
            "pedido" => $pedido->id,
            "nome_cliente" => $nome_cliente,
			"link_pagamento" => $link,
			"valor_total" => parserValor($pedido->valor_total_nota),
			"valor_diferenca" => parserValor($valor)
        ];
		$returnEmail = $EmailObj->sendEmailToken("00", "pagamento_pedido_restante", $email_send, $variaveis);
    }
    
    public function modalPagoFiltro(Request $request){
		$fields = $request->only(['estabelecimento']);
        return view('programs.acompanhamento_cielo.modal.pagos_filtro')->with(['fields' => encrypt($fields)]);
    }

    public function filtroPagos(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only(['filtro', 'data_de', 'data_ate']);
        $fields['filtro'] = decrypt($fields['filtro']);
        
		$CieloPedidoObj = CieloPedido::with(['pedidoPortal', 'pedidoPortal.clienteSemBloqueio', 'transacoes']);
		$CieloPedidoObj->where('pago', 'true');
        $CieloPedidoObj->has('pedidoPortal');
		if(!empty($fields['filtro']['estabelecimento'])){
			$CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
				$portal->where('estabelecimento', $fields['filtro']['estabelecimento']);
			});
        }
        if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $CieloPedidoObj->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $CieloPedidoObj->where('created_at', '<=', $data);
        }
		$CieloPedidoObj = $CieloPedidoObj->get();

		$retorno = [];
        $estabelecimentos = returnEmpresasNasajonView();

		$CieloPedidoObj->each(function($pedido) use (&$retorno, $estabelecimentos){
            $transacoes = $pedido->transacoes;

            $terminal_numero = '';
            $codigo_autorizacao = '';
            $nsu = '';
            $numero_cartao = '';
            $bandeira_cartao = '';
            $tipo_pagamento = '';
            $forma_pagamento = '';

            if(!empty($transacoes)){
                $transacoes_json = json_decode($transacoes->json_retorno, true);
                $terminal_numero = $transacoes->terminal_numero;
                $codigo_autorizacao = $transacoes->codigo_autorizacao;
                $nsu = $transacoes->numero;
                if(!empty($transacoes_json)){
                    if($pedido->lio == true){
                        $numero_cartao = $transacoes_json['card']['mask'];
                        $bandeira_cartao = $transacoes_json['card']['brand'];
                        if(isset($transacoes_json['payment_fields']['primary_product_name'])){
                            $tipo_pagamento = $transacoes_json['payment_fields']['primary_product_name'];
                        }else{
                            $tipo_pagamento = $transacoes_json['payment_fields']['primaryProductName'];
                        }
                        if(isset($transacoes_json['payment_fields']['secondary_product_name'])){
                            $forma_pagamento = $transacoes_json['payment_fields']['secondary_product_name'];
                        }else{
                            $forma_pagamento = $transacoes_json['payment_fields']['secondaryProductName'];
                        }


                    }else{
                        $numero_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['CardNumber'];
                        $bandeira_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['Brand'];
                        if($transacoes_json['Payment']['Type'] === 'CreditCard'){
                            $tipo_pagamento = 'Crédito';
                        }else{
                            $tipo_pagamento = 'Debito';

                        }

                    }
                }
            }
			$retorno[] = [
				'estabelecimento' => $estabelecimentos[$pedido->pedidoPortal->estabelecimento],
				'presencial' => $pedido->pedidoPortal->presencial ? 'sim' : 'não',
				'pedido' => $pedido->pedidoPortal->id,
				'cliente' => $pedido->pedidoPortal->clienteSemBloqueio->nome . ' - ' . $pedido->pedidoPortal->clienteSemBloqueio->cpf_cnpj,
				'valor_total' => parserValor($pedido->valor_total),
				'valor_pago' => parserValor($pedido->valor_pago),
                'link_pedido' => route("pedido_online.pagamento", ["token" => $pedido->link_pedido]),
                'terminal_numero' => $terminal_numero,
                'codigo_autorizacao' => $codigo_autorizacao,
                'nsu' => $nsu,
                'numero_cartao' => $numero_cartao,
                'bandeira_cartao' => $bandeira_cartao,
                'tipo_pagamento' => $tipo_pagamento,
                'forma_pagamento' => $forma_pagamento,
			];
        });
        
        return response()->json([
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ]);
    }


	public function modalErros(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
		$fields = $request->only(['estabelecimento', 'pedido', 'forma_pagamento','data_de', 'data_ate']);
		$CieloPedidoObj = CieloPedido::with(['pedidoPortal', 'pedidoPortal.clienteSemBloqueio', 'erros']);
		$CieloPedidoObj->where('pago', 'false');
		$CieloPedidoObj->has('erros');
        $CieloPedidoObj->has('pedidoPortal');

		$transacoes_finalizadas = StoneTransacoesPedido::where(function($query){
			$query->where('status_pre_transacao','1')
			->orWhereNotNull('pagamento_parcial');
		})
        ->select('pedido_id');

		$pedidos_stone_nao_processados = StoneTransacoesPedido::with(['pedido','maquininha'])->where(function($query){
			$query->where('status_pre_transacao','0')
			->orWhereNull('pagamento_parcial');
		})
		->whereHas('pedido',function($query){
			$query->whereNotIn('status_pedido',[5,7,13]);
		});

		if(!empty($fields['estabelecimento'])){
			$CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
				$portal->where('estabelecimento', $fields['estabelecimento']);
			});
			$pedidos_stone_nao_processados->whereHas('pedido',function($query) use ($fields){
				$query->where("estabelecimento",str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
			});
			$transacoes_finalizadas->whereHas('pedido',function($query) use ($fields){
				$query->where("estabelecimento",str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
			});
		}

		if(!empty($fields['pedido'])){
            $CieloPedidoObj->where('pedido_id', $fields['pedido']);
        	$pedidos_stone_nao_processados->where('pedido_id',$fields['pedido']);
			$transacoes_finalizadas->where('pedido_id',$fields['pedido']);
        }

		if(!empty($fields['forma_pagamento'])){
			if($fields['forma_pagamento'] == 'usar_creditos'){
				$CieloPedidoObj->whereHas('pedido',function($query){
					$query->whereNull('pix_id');
				});
				$pedidos_stone_nao_processados->whereNull('id');
				$transacoes_finalizadas->whereNull('id');
			}

			if($fields['forma_pagamento'] == 'pix'){
				$CieloPedidoObj->whereHas('pedido',function($query){
					$query->whereNotNull('pix_id');
				});
				$pedidos_stone_nao_processados->whereNull('id');
				$transacoes_finalizadas->whereNull('id');
			}

			if($fields['forma_pagamento'] == 'stone'){
				$CieloPedidoObj->whereNull('id');
			}

			if($fields['forma_pagamento'] == 'pagar_me'){
				$pedidos_stone_nao_processados->whereNull('id');
				$transacoes_finalizadas->whereNull('id');
				$CieloPedidoObj->whereHas('pedido',function($query){
					$query->where('presencial',false);
				});
			}
        }

        if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $CieloPedidoObj->where('created_at', '>=', $data);
        	$pedidos_stone_nao_processados->where('created_at', '>=', $data);
			$transacoes_finalizadas->where('created_at', '>=', $data);
        }

        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $CieloPedidoObj->where('created_at', '<=', $data);
        	$pedidos_stone_nao_processados->where('created_at', '<=', $data);
			$transacoes_finalizadas->where('created_at', '<=', $data);
        }
		
		$transacoes_finalizadas = $transacoes_finalizadas
        ->get()
        ->pluck('pedido_id')
        ->toArray();
		
		$pedidos_stone_nao_processados->whereNotIn('pedido_id',$transacoes_finalizadas);

        $CieloPedidoObj = $CieloPedidoObj->get();
		$pedidos_stone_nao_processados = $pedidos_stone_nao_processados->get();
		
		$retorno = [];
        $estabelecimentos = returnEmpresasNasajonView();

		$pedidos_stone_nao_processados->each(function($pedido) use (&$retorno,$estabelecimentos){
			$retorno[] = [
				'estabelecimento' => $estabelecimentos[ltrim($pedido->pedido->estabelecimento,'0')],
				'pedido' => $pedido->pedido_id,
				'cliente' => (!empty($pedido->pedido->cliente->nome)) ? $pedido->pedido->cliente->nome . ' - ' . $pedido->pedido->cliente->cpf_cnpj : '',
				'valor_total' => (isset($pedido->pedido->valor_total_produtos)) ? parserValor($pedido->pedido->valor_total_produtos) : '',
				'codigo_erro' => '',
				'mensagem_erro' => 'Não Finalizado.',
				'vinculo' => 'Stone',
				'numero_cartao' => '',
				'bandeira_cartao' => '',
				'forma_pagamento' => '',
			];
		});

		$CieloPedidoObj->each(function($pedido) use (&$retorno, $estabelecimentos){
            $pedido->erros->each(function($erro) use ($pedido, &$retorno, $estabelecimentos){
                $retorno_json = $erro->json_retorno;
                if(isset($retorno_json['Payment'])){
					$numero_cartao = $retorno_json['Payment'][$retorno_json['Payment']['Type']]['CardNumber'];
					$bandeira_cartao = $retorno_json['Payment'][$retorno_json['Payment']['Type']]['Brand'];
					if($retorno_json['Payment']['Type'] === 'CreditCard'){
						$forma_pagamento = 'Crédito';
					}else{
						$forma_pagamento = 'Debito';
					}
					$motivo = $erro->motivo_erro;
					$codigo_erro = $retorno_json['Payment']['ReturnCode'];
				}else{
					$numero_cartao = '********'.$retorno_json['transaction']['card_last_digits'];
					$bandeira_cartao = $retorno_json['transaction']['card_brand'];
					if($retorno_json['transaction']['payment_method'] === 'credit_card'){
						$forma_pagamento = 'Crédito';
					}else{
						$forma_pagamento = 'Debito';
					}
					
					$codigo_erro = $retorno_json['transaction']['acquirer_response_code'];
					$motivo = $this->tratandoCodigoErro($retorno_json);
				}
                $retorno[] = [
                    'estabelecimento' => $estabelecimentos[$pedido->pedidoPortal->estabelecimento],
                    'pedido' => $pedido->pedidoPortal->id,
                    'cliente' => $pedido->pedidoPortal->clienteSemBloqueio->nome . ' - ' . $pedido->pedidoPortal->clienteSemBloqueio->cpf_cnpj,
                    'valor_total' => parserValor($pedido->valor_total),
                    'codigo_erro' => $codigo_erro,
                    'mensagem_erro' => $motivo,
					'vinculo' => 'Pagar.me',
                    'numero_cartao' => $numero_cartao,
                    'bandeira_cartao' => $bandeira_cartao,
                    'forma_pagamento' => $forma_pagamento,
                ];
            });
        });
        return view('programs.acompanhamento_cielo.modal.erro')->with(['pedidos' => $retorno]);
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

	public function modalCreditoComNota(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
		$fields = $request->only(['estabelecimento', 'pedido', 'data_de', 'data_ate']);
		$CieloPedidoObj = CieloPedido::with(['pedidoPortal', 'pedidoPortal.clienteSemBloqueio', 'pedidoPortal.pedidoNasajon', 'transacoes']);
		$CieloPedidoObj->where('pago', 'true');
        $CieloPedidoObj->whereNotIn('cielo_status_id', [5, 6]);
        $CieloPedidoObj->whereHas('pedidoPortal', function($portal){
			$portal->whereIn('condicao_pagamento', ['4193', '4194', '4195', '4196', '4197', '4198', '4199', '4213']);
		});
		if(!empty($fields['estabelecimento'])){
			$CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
				$portal->where('estabelecimento', $fields['estabelecimento']);
			});
		}
        if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $CieloPedidoObj->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $CieloPedidoObj->where('created_at', '<=', $data);
        }
		if(!empty($fields['pedido'])){
            $CieloPedidoObj->where('pedido_id', $fields['pedido']);
        }
        $CieloPedidoObj = $CieloPedidoObj->get();

		$retorno_itens = [];
		$retorno_bandeiras = [];
		$retorno_parcela = [];
        $estabelecimentos = returnEmpresasNasajonView();
		$CieloPedidoObj->each(function($pedido) use (&$retorno_itens, &$retorno_bandeiras, &$retorno_parcela, $estabelecimentos){
			if($pedido->pedidoPortal->pedidoNasajon->situacao_descricao == 'Faturado'){
				$transacoes = $pedido->transacoes;

				$terminal_numero = '';
				$codigo_autorizacao = '';
				$nsu = '';
				$numero_cartao = '';
				$bandeira_cartao = '';
				$tipo_pagamento = '';
				$parcelas = 1;

				if(!empty($transacoes)){
					$transacoes_json = json_decode($transacoes->json_retorno, true);
					$terminal_numero = $transacoes->terminal_numero;
					$codigo_autorizacao = $transacoes->codigo_autorizacao;
					$nsu = $transacoes->numero;
					if(!empty($transacoes_json)){
						if($pedido->lio == true){
							$numero_cartao = $transacoes_json['card']['mask'];
							$bandeira_cartao = $transacoes_json['card']['brand'];
							if(isset($transacoes_json['payment_fields']['primary_product_name'])){
								$tipo_pagamento = $transacoes_json['payment_fields']['primary_product_name'];
							}else{
								$tipo_pagamento = $transacoes_json['payment_fields']['primaryProductName'];
							}
							$parcelas = $transacoes_json['payment_fields']['number_of_quotas'];
							if($parcelas == 0){
								$parcelas = 1;
							}
						}else{
							if(isset($transacoes_json['Payment'])){
								$parcelas = $transacoes_json['Payment']['Installments'];
								$numero_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['CardNumber'];
								$bandeira_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['Brand'];
								if($transacoes_json['Payment']['Type'] === 'CreditCard'){
									$tipo_pagamento = 'Crédito';
								}else{
									$tipo_pagamento = 'Debito';
								}
							}else{
								$parcelas = $transacoes_json['transaction']['installments'];
								$numero_cartao = '********'.$transacoes_json['transaction']['card_last_digits'];
								$bandeira_cartao = $transacoes_json['transaction']['card_brand'];
								if($transacoes_json['transaction']['payment_method'] === 'credit_card'){
									$tipo_pagamento = 'Crédito';
								}else{
									$tipo_pagamento = 'Debito';
								}
							}
						}
					}
				}
				$retorno_itens[] = [
					'estabelecimento' => $estabelecimentos[$pedido->pedidoPortal->estabelecimento],
					'presencial' => $pedido->pedidoPortal->presencial ? 'sim' : 'não',
					'pedido' => $pedido->pedidoPortal->id,
					'nota_id' => $pedido->pedidoPortal->pedidoNasajon->notafiscal_id,
					'nota_numero' => $pedido->pedidoPortal->pedidoNasajon->notafiscal_numero,
					'cliente' => $pedido->pedidoPortal->clienteSemBloqueio->nome . ' - ' . $pedido->pedidoPortal->clienteSemBloqueio->cpf_cnpj,
					'valor_pago' => parserValor($pedido->valor_pago),
					'terminal_numero' => $terminal_numero,
					'codigo_autorizacao' => $codigo_autorizacao,
					'parcela' => $parcelas,
					'nsu' => $nsu,
					'numero_cartao' => $numero_cartao,
					'bandeira_cartao' => $bandeira_cartao,
					'tipo_pagamento' => $tipo_pagamento,
				];
				$bandeira_cartao = strtolower($bandeira_cartao);
				if(!isset($retorno_bandeiras[$bandeira_cartao])){
					$retorno_bandeiras[$bandeira_cartao] = [
						'bandeira' => $bandeira_cartao,
						'valor' => 0
					];
				}
				$retorno_bandeiras[$bandeira_cartao]['valor'] += $pedido->valor_pago;

				if(!isset($retorno_parcela[$parcelas])){
					$retorno_parcela[$parcelas] = [
						'parcela' => $parcelas,
						'valor' => 0
					];
				}
				$retorno_parcela[$parcelas]['valor'] += $pedido->valor_pago;
			}
		});
		$total = [
			'pedidos' => $this->somarValores($retorno_itens, 'valor_pago', true),
			'bandeira' => $this->somarValores($retorno_bandeiras, 'valor', false),
			'parcela' => $this->somarValores($retorno_parcela, 'valor', false),
		];
        return view('programs.acompanhamento_cielo.modal.pago_com_nota')->with(['pedidos' => $retorno_itens,'bandeiras' => $retorno_bandeiras, 'parcelas' => $retorno_parcela, 'total'=>$total]);
	}

	public function modalDebitoComNota(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
		$fields = $request->only(['estabelecimento', 'pedido', 'data_de', 'data_ate']);
		$CieloPedidoObj = CieloPedido::with(['pedidoPortal', 'pedidoPortal.clienteSemBloqueio', 'pedidoPortal.pedidoNasajon', 'transacoes']);
		$CieloPedidoObj->where('pago', 'true');
        $CieloPedidoObj->whereNotIn('cielo_status_id', [5, 6]);
        $CieloPedidoObj->whereHas('pedidoPortal', function($portal){
			$portal->whereIn('condicao_pagamento', ['4201', '4214']);
		});
		if(!empty($fields['estabelecimento'])){
			$CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
				$portal->where('estabelecimento', $fields['estabelecimento']);
			});
		}
		if(!empty($fields['pedido'])){
            $CieloPedidoObj->where('pedido_id', $fields['pedido']);
        }
        if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $CieloPedidoObj->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $CieloPedidoObj->where('created_at', '<=', $data);
        }
        $CieloPedidoObj = $CieloPedidoObj->get();

		$retorno_itens = [];
		$retorno_bandeiras = [];
		$retorno_parcela = [];
        $estabelecimentos = returnEmpresasNasajonView();
		$CieloPedidoObj->each(function($pedido) use (&$retorno_itens, &$retorno_bandeiras, &$retorno_parcela, $estabelecimentos){
			if($pedido->pedidoPortal->pedidoNasajon->situacao_descricao == 'Faturado'){
				$transacoes = $pedido->transacoes;

				$terminal_numero = '';
				$codigo_autorizacao = '';
				$nsu = '';
				$numero_cartao = '';
				$bandeira_cartao = '';
				$tipo_pagamento = '';
				$parcelas = 1;

				if(!empty($transacoes)){
					$transacoes_json = json_decode($transacoes->json_retorno, true);
					$terminal_numero = $transacoes->terminal_numero;
					$codigo_autorizacao = $transacoes->codigo_autorizacao;
					$nsu = $transacoes->numero;
					if(!empty($transacoes_json)){
						if($pedido->lio == true){
							$numero_cartao = $transacoes_json['card']['mask'];
							$bandeira_cartao = $transacoes_json['card']['brand'];
							if(isset($transacoes_json['payment_fields']['primary_product_name'])){
								$tipo_pagamento = $transacoes_json['payment_fields']['primary_product_name'];
							}else{
								$tipo_pagamento = $transacoes_json['payment_fields']['primaryProductName'];
							}
							$parcelas = $transacoes_json['payment_fields']['number_of_quotas'];
							if($parcelas == 0){
								$parcelas = 1;
							}
						}else{
							if(isset($transacoes_json['Payment'])){
								$parcelas = $transacoes_json['Payment']['Installments'];
								$numero_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['CardNumber'];
								$bandeira_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['Brand'];
								if($transacoes_json['Payment']['Type'] === 'CreditCard'){
									$tipo_pagamento = 'Crédito';
								}else{
									$tipo_pagamento = 'Debito';
								}
							}else{
								$parcelas = $transacoes_json['transaction']['installments'];
								$numero_cartao = '********'.$transacoes_json['transaction']['card_last_digits'];
								$bandeira_cartao = $transacoes_json['transaction']['card_brand'];
								if($transacoes_json['transaction']['payment_method'] === 'credit_card'){
									$tipo_pagamento = 'Crédito';
								}else{
									$tipo_pagamento = 'Debito';
								}
							}
						}
					}
				}
				$retorno_itens[] = [
					'estabelecimento' => $estabelecimentos[$pedido->pedidoPortal->estabelecimento],
					'presencial' => $pedido->pedidoPortal->presencial ? 'sim' : 'não',
					'pedido' => $pedido->pedidoPortal->id,
					'nota_id' => $pedido->pedidoPortal->pedidoNasajon->notafiscal_id,
					'nota_numero' => $pedido->pedidoPortal->pedidoNasajon->notafiscal_numero,
					'cliente' => $pedido->pedidoPortal->clienteSemBloqueio->nome . ' - ' . $pedido->pedidoPortal->clienteSemBloqueio->cpf_cnpj,
					'valor_pago' => parserValor($pedido->valor_pago),
					'terminal_numero' => $terminal_numero,
					'codigo_autorizacao' => $codigo_autorizacao,
					'parcela' => $parcelas,
					'nsu' => $nsu,
					'numero_cartao' => $numero_cartao,
					'bandeira_cartao' => $bandeira_cartao,
					'tipo_pagamento' => $tipo_pagamento,
				];
				$bandeira_cartao = strtolower($bandeira_cartao);
				if(!isset($retorno_bandeiras[$bandeira_cartao])){
					$retorno_bandeiras[$bandeira_cartao] = [
						'bandeira' => $bandeira_cartao,
						'valor' => 0
					];
				}
				$retorno_bandeiras[$bandeira_cartao]['valor'] += $pedido->valor_pago;
				if(!isset($retorno_parcela[$parcelas])){
					$retorno_parcela[$parcelas] = [
						'parcela' => $parcelas,
						'valor' => 0
					];
				}
				$retorno_parcela[$parcelas]['valor'] += $pedido->valor_pago;
			}
		});
		$total = [
			'pedidos' => $this->somarValores($retorno_itens, 'valor_pago', true),
			'bandeira' => $this->somarValores($retorno_bandeiras, 'valor', false),
			'parcela' => $this->somarValores($retorno_parcela, 'valor', false),
		];
        return view('programs.acompanhamento_cielo.modal.pago_com_nota')->with(['pedidos' => $retorno_itens,'bandeiras' => $retorno_bandeiras, 'parcelas' => $retorno_parcela, 'total'=>$total]);
	}

	public function modalCreditoSemNota(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
		$fields = $request->only(['estabelecimento', 'pedido', 'data_de', 'data_ate']);
		$CieloPedidoObj = CieloPedido::with(['pedidoPortal', 'pedidoPortal.clienteSemBloqueio', 'pedidoPortal.pedidoNasajon', 'transacoes','estornos']);
		$CieloPedidoObj->where('pago', 'true');
        $CieloPedidoObj->whereNotIn('cielo_status_id', [5, 6]);
        $CieloPedidoObj->whereHas('pedidoPortal', function($portal){
			$portal->whereIn('condicao_pagamento', ['4193', '4194', '4195', '4196', '4197', '4198', '4199', '4213']);
		});
		if(!empty($fields['estabelecimento'])){
			$CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
				$portal->where('estabelecimento', $fields['estabelecimento']);
			});
		}
		if(!empty($fields['pedido'])){
            $CieloPedidoObj->where('pedido_id', $fields['pedido']);
        }
        if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $CieloPedidoObj->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $CieloPedidoObj->where('created_at', '<=', $data);
        }
        $CieloPedidoObj = $CieloPedidoObj->get();

		$retorno_itens = [];
		$retorno_bandeiras = [];
		$retorno_parcela = [];
        $estabelecimentos = returnEmpresasNasajonView();
		$CieloPedidoObj->each(function($pedido) use (&$retorno_itens, &$retorno_bandeiras, &$retorno_parcela, $estabelecimentos){
			if(!empty($pedido->pedidoPortal->pedidoNasajon->situacao_descricao) && $pedido->pedidoPortal->pedidoNasajon->situacao_descricao != 'Faturado' || !isset($pedido->pedidoPortal->pedidoNasajon)){
				$transacoes = $pedido->transacoes;

				$terminal_numero = '';
				$codigo_autorizacao = '';
				$nsu = '';
				$numero_cartao = '';
				$bandeira_cartao = '';
				$tipo_pagamento = '';
				$parcelas = 1;

				if(!empty($transacoes)){
					$transacoes_json = json_decode($transacoes->json_retorno, true);
					$terminal_numero = $transacoes->terminal_numero;
					$codigo_autorizacao = $transacoes->codigo_autorizacao;
					$nsu = $transacoes->numero;
					if(!empty($transacoes_json)){
						if($pedido->lio == true){
							$numero_cartao = $transacoes_json['card']['mask'];
							$bandeira_cartao = $transacoes_json['card']['brand'];
							if(isset($transacoes_json['payment_fields']['primary_product_name'])){
								$tipo_pagamento = $transacoes_json['payment_fields']['primary_product_name'];
							}else{
								$tipo_pagamento = $transacoes_json['payment_fields']['primaryProductName'];
							}
							$parcelas = $transacoes_json['payment_fields']['number_of_quotas'];
							if($parcelas == 0){
								$parcelas = 1;
							}
						}else{
							if(isset($transacoes_json['Payment'])){
								$parcelas = $transacoes_json['Payment']['Installments'];
								$numero_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['CardNumber'];
								$bandeira_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['Brand'];
								if($transacoes_json['Payment']['Type'] === 'CreditCard'){
									$tipo_pagamento = 'Crédito';
								}else{
									$tipo_pagamento = 'Debito';
								}
							}else{
								$parcelas = $transacoes_json['transaction']['installments'];
								$numero_cartao = '********'.$transacoes_json['transaction']['card_last_digits'];
								$bandeira_cartao = $transacoes_json['transaction']['card_brand'];
								if($transacoes_json['transaction']['payment_method'] === 'credit_card'){
									$tipo_pagamento = 'Crédito';
								}else{
									$tipo_pagamento = 'Debito';
								}
							}
						}
					}
				}
				$retorno_itens[] = [
					'estabelecimento' => $estabelecimentos[$pedido->pedidoPortal->estabelecimento],
					'presencial' => $pedido->pedidoPortal->presencial ? 'sim' : 'não',
					'pedido' => $pedido->pedidoPortal->id,
					'status' => (!empty($pedido->pedidoPortal->pedidoNasajon->situacao_descricao)) ? $pedido->pedidoPortal->pedidoNasajon->situacao_descricao : '',
					'nota_numero' => (!empty($pedido->pedidoPortal->pedidoNasajon->notafiscal_numero)) ? $pedido->pedidoPortal->pedidoNasajon->notafiscal_numero : '',
					'cliente' => $pedido->pedidoPortal->clienteSemBloqueio->nome . ' - ' . $pedido->pedidoPortal->clienteSemBloqueio->cpf_cnpj,
					'valor_pago' => parserValor($pedido->valor_pago),
					'terminal_numero' => $terminal_numero,
					'codigo_autorizacao' => $codigo_autorizacao,
					'parcela' => $parcelas,
					'nsu' => $nsu,
					'numero_cartao' => $numero_cartao,
					'bandeira_cartao' => $bandeira_cartao,
					'tipo_pagamento' => $tipo_pagamento,
					'id_cielo' => encrypt($pedido->id),
					'estorno' => (isset($pedido->estornos[0])) ? true : false
				];
				$bandeira_cartao = strtolower($bandeira_cartao);
				if(!isset($retorno_bandeiras[$bandeira_cartao])){
					$retorno_bandeiras[$bandeira_cartao] = [
						'bandeira' => $bandeira_cartao,
						'valor' => 0
					];
				}
				$retorno_bandeiras[$bandeira_cartao]['valor'] += $pedido->valor_pago;
				if(!isset($retorno_parcela[$parcelas])){
					$retorno_parcela[$parcelas] = [
						'parcela' => $parcelas,
						'valor' => 0
					];
				}
				$retorno_parcela[$parcelas]['valor'] += $pedido->valor_pago;
			}
		});

		$total = [
			'pedidos' => $this->somarValores($retorno_itens, 'valor_pago', true),
			'bandeira' => $this->somarValores($retorno_bandeiras, 'valor', false),
			'parcela' => $this->somarValores($retorno_parcela, 'valor', false),
		];
        return view('programs.acompanhamento_cielo.modal.pago_sem_nota')->with(['pedidos' => $retorno_itens,'bandeiras' => $retorno_bandeiras, 'parcelas' => $retorno_parcela, 'total'=>$total]);
	}

	public function modalDebitoSemNota(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
		$fields = $request->only(['estabelecimento', 'pedido', 'data_de', 'data_ate']);
		$CieloPedidoObj = CieloPedido::with(['pedidoPortal', 'pedidoPortal.clienteSemBloqueio', 'pedidoPortal.pedidoNasajon', 'transacoes']);
		$CieloPedidoObj->where('pago', 'true');
        $CieloPedidoObj->whereNotIn('cielo_status_id', [5, 6]);
        $CieloPedidoObj->whereHas('pedidoPortal', function($portal){
			$portal->whereIn('condicao_pagamento', ['4201', '4214']);
		});
		if(!empty($fields['estabelecimento'])){
			$CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
				$portal->where('estabelecimento', $fields['estabelecimento']);
			});
		}
		if(!empty($fields['pedido'])){
            $CieloPedidoObj->where('pedido_id', $fields['pedido']);
        }
        if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $CieloPedidoObj->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $CieloPedidoObj->where('created_at', '<=', $data);
        }
        $CieloPedidoObj = $CieloPedidoObj->get();

		$retorno_itens = [];
		$retorno_bandeiras = [];
		$retorno_parcela = [];
        $estabelecimentos = returnEmpresasNasajonView();
		$CieloPedidoObj->each(function($pedido) use (&$retorno_itens, &$retorno_bandeiras, &$retorno_parcela, $estabelecimentos){
			if($pedido->pedidoPortal->pedidoNasajon->situacao_descricao != 'Faturado' || !isset($pedido->pedidoPortal->pedidoNasajon)){
				$transacoes = $pedido->transacoes;

				$terminal_numero = '';
				$codigo_autorizacao = '';
				$nsu = '';
				$numero_cartao = '';
				$bandeira_cartao = '';
				$tipo_pagamento = '';
				$parcelas = 1;

				if(!empty($transacoes)){
					$transacoes_json = json_decode($transacoes->json_retorno, true);
					$terminal_numero = $transacoes->terminal_numero;
					$codigo_autorizacao = $transacoes->codigo_autorizacao;
					$nsu = $transacoes->numero;
					if(!empty($transacoes_json)){
						if($pedido->lio == true){
							$numero_cartao = $transacoes_json['card']['mask'];
							$bandeira_cartao = $transacoes_json['card']['brand'];
							if(isset($transacoes_json['payment_fields']['primary_product_name'])){
								$tipo_pagamento = $transacoes_json['payment_fields']['primary_product_name'];
							}else{
								$tipo_pagamento = $transacoes_json['payment_fields']['primaryProductName'];
							}
							$parcelas = $transacoes_json['payment_fields']['number_of_quotas'];
							if($parcelas == 0){
								$parcelas = 1;
							}
						}else{
							if(isset($transacoes_json['Payment'])){
								$parcelas = $transacoes_json['Payment']['Installments'];
								$numero_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['CardNumber'];
								$bandeira_cartao = $transacoes_json['Payment'][$transacoes_json['Payment']['Type']]['Brand'];
								if($transacoes_json['Payment']['Type'] === 'CreditCard'){
									$tipo_pagamento = 'Crédito';
								}else{
									$tipo_pagamento = 'Debito';
								}
							}else{
								$parcelas = $transacoes_json['transaction']['installments'];
								$numero_cartao = '********'.$transacoes_json['transaction']['card_last_digits'];
								$bandeira_cartao = $transacoes_json['transaction']['card_brand'];
								if($transacoes_json['transaction']['payment_method'] === 'credit_card'){
									$tipo_pagamento = 'Crédito';
								}else{
									$tipo_pagamento = 'Debito';
								}
							}
						}
					}
				}
				$retorno_itens[] = [
					'estabelecimento' => $estabelecimentos[$pedido->pedidoPortal->estabelecimento],
					'presencial' => $pedido->pedidoPortal->presencial ? 'sim' : 'não',
					'pedido' => $pedido->pedidoPortal->id,
					'status' => $pedido->pedidoPortal->pedidoNasajon->situacao_descricao,
					'nota_numero' => $pedido->pedidoPortal->pedidoNasajon->notafiscal_numero,
					'cliente' => $pedido->pedidoPortal->clienteSemBloqueio->nome . ' - ' . $pedido->pedidoPortal->clienteSemBloqueio->cpf_cnpj,
					'valor_pago' => parserValor($pedido->valor_pago),
					'terminal_numero' => $terminal_numero,
					'codigo_autorizacao' => $codigo_autorizacao,
					'parcela' => $parcelas,
					'nsu' => $nsu,
					'numero_cartao' => $numero_cartao,
					'bandeira_cartao' => $bandeira_cartao,
					'tipo_pagamento' => $tipo_pagamento,
					'id_cielo' => null,
					'estorno' => false
				];
				$bandeira_cartao = strtolower($bandeira_cartao);
				if(!isset($retorno_bandeiras[$bandeira_cartao])){
					$retorno_bandeiras[$bandeira_cartao] = [
						'bandeira' => $bandeira_cartao,
						'valor' => 0
					];
				}
				$retorno_bandeiras[$bandeira_cartao]['valor'] += $pedido->valor_pago;
				if(!isset($retorno_parcela[$parcelas])){
					$retorno_parcela[$parcelas] = [
						'parcela' => $parcelas,
						'valor' => 0
					];
				}
				$retorno_parcela[$parcelas]['valor'] += $pedido->valor_pago;
			}
		});
		$total = [
			'pedidos' => $this->somarValores($retorno_itens, 'valor_pago', true),
			'bandeira' => $this->somarValores($retorno_bandeiras, 'valor', false),
			'parcela' => $this->somarValores($retorno_parcela, 'valor', false),
		];
        return view('programs.acompanhamento_cielo.modal.pago_sem_nota')->with(['pedidos' => $retorno_itens,'bandeiras' => $retorno_bandeiras, 'parcelas' => $retorno_parcela, 'total'=>$total]);
	}
	private function somarValores($array, $campo, $formatacao){
		$valor = 0;
		foreach($array as $campos){
			if(isset($campos[$campo])){
				$valor_campo = $campos[$campo];
				if($formatacao == true){
					$valor_campo = parserNumber($campos[$campo]);
				}
				$valor += (float) $valor_campo;
			}
		}
		return $valor;
	}

	public function modalEstorno(Request $request){
		$campos = $request->only(['pedido_id','total']);

		try{
            $id = decrypt($campos['pedido_id']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return, 422);
        }

		$pedido_pagarme = CieloPedido::with('pedidoPortal','estornos')->find($id);
		$retorno = [];

		$retorno = [
			'cliente' => $pedido_pagarme->pedidoPortal->nome_comprador,
			'pedido_portal' => $pedido_pagarme->pedidoPortal->id,
			'id_cielo' => encrypt($pedido_pagarme->id),
			'valor_pago' => (!empty($pedido_pagarme->estornos->sum('valor_estorno'))) ? parserValor($pedido_pagarme->valor_pago - $pedido_pagarme->estornos->sum('valor_estorno')) : parserValor($pedido_pagarme->valor_pago)
		];

	    return view('programs.acompanhamento_cielo.modal.estorno')->with(['dados' => $retorno, 'total' => (isset($campos['total'])) ? $campos['total'] : null]);
	}

	public function pagarmeEstorno(PagarmeEstornoSalvarRequest $request){
		$campos = $request->only(['estorno_parcial','valor_estorno','cielo_id']);

		try{
            $id = decrypt($campos['cielo_id']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return, 422);
        }

		$pedido_pagarme = CieloPedido::with(['pedidoPortal','transacoes','estornos'])->find($id);

		if(!isset($pedido_pagarme->id)){
			$return = [
                'status' => 'error',
                'message' => 'Pedido não encontrado.',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return, 422);
		}

		$transacoes_id = $pedido_pagarme->transacoes->id; 
		$id_cielo = $pedido_pagarme->id; 

		$array_retorno = json_decode($pedido_pagarme->transacoes->json_retorno, true);

		if(isset($array_retorno['transaction']['id']) && !empty($array_retorno['transaction']['id'])){
			$token_transacao = $array_retorno['transaction']['id'];
		}else{
			$return = [
                'status' => 'error',
                'message' => 'ID da transação não localizado.',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return, 422);
		}

		$this->setHeaderParameters($pedido_pagarme);

		$retorno = $this->enviarEstorno($token_transacao,$campos['valor_estorno'],$transacoes_id,$id_cielo,$pedido_pagarme);
		return $retorno;
	}

	private function setHeaderParameters($CieloPedido){
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
			$CieloAutenticaoObj->where("estabelecimento", $CieloPedido->pedidoPortal->estabelecimento_pad);
			$CieloAutenticaoObj = $CieloAutenticaoObj->first();
			if(!empty($CieloAutenticaoObj)){
				$this->token_api = $CieloAutenticaoObj->token_api;
			}
		}

		if(config("app.debug") == true){
			Log::info("setou os headers de chamada para estorno");
		}
	}

	private function enviarEstorno($token_transacao,$valor_estorno,$transacoes_id,$id_cielo,$pedido_pagarme){
		$this->request = new \GuzzleHttp\Client(['http_errors' => false]);
		$valor_restante = (!empty($pedido_pagarme->estornos[0]->valor_estorno)) ? $pedido_pagarme->valor_pago - $pedido_pagarme->estornos[0]->valor_estorno : null;

		$body = [
			'api_key' => $this->token_api
		];

		if(!empty($valor_estorno)){
			$body = [
				'api_key' => $this->token_api,
				'amount' => $this->limparValor($valor_estorno)
			];
		}

		if(!empty($valor_restante)){
			$body = [
				'api_key' => $this->token_api,
				'amount' => $this->limparValor($valor_restante)
			];
		}

		$request = $this->request->request("post", $this->url_request."transactions/".$token_transacao."/refund",[
			"form_params" => $body
		]);

		if(config("app.debug") == true){
			Log::info("enviou o estorno");
		}
		
		if($request->getStatusCode() == 200){
			$valor_entrada = (!empty($valor_estorno)) ? parserNumber($valor_estorno) : null;
			
			$estorno_cielo = new CieloPedidosEstorno;
			$estorno_cielo->cielo_pedido_transacao_id = $transacoes_id;
			$estorno_cielo->cielo_pedido_id = $id_cielo;
			$estorno_cielo->token_estorno = $token_transacao;
			$estorno_cielo->valor_estorno = (!empty($valor_restante)) ? $valor_restante : $valor_entrada;
			$estorno_cielo->created_by = Auth::user()->id;
			$estorno_cielo->save();

			return response()->json([
				"status" => "success",
				"message" => "Estorno realizado com sucesso.",
				"error" => [],
				"response" => []
			], 200);
		}else{
			$response = json_decode($request->getBody()->getContents(), true);
			return response()->json([
				"status" => "error_request",
				"message" => (isset($response['errors'][0]['message']) && !empty($response['errors'][0]['message'])) ? $response['errors'][0]['message'] : "Ocorreu uma instabilidade ao fazer o estorno, contate o setor responsável!",
				"error" => [$response],
				"response" => (isset($response['errors'][0]['message']) && !empty($response['errors'][0]['message'])) ? $response['errors'][0]['message'] : "Ocorreu uma instabilidade ao fazer o estorno, contate o setor responsável!"
			], 422);
		}
	}

	private function limparValor($str){ 
		return preg_replace("/[^0-9]/", "", $str); 
	}

	public function pixSemNota(Request $request){
		set_time_limit('300');
		$fields = $request->only(['estabelecimento','pedido','data_de','data_ate']);

		$pedidos_venda = PedidoPortal::with('pedidoNasajon','status_pedido_detalhes','cliente','pagamentoPix')
		->whereIn('condicao_pagamento',$this->condicao_de_pagamento_usar_credito);

		if(!empty($fields['pedido'])){
            $pedidos_venda->where('id', $fields['pedido']);
        }
		if(!empty($fields['estabelecimento'])){
            $pedidos_venda->where('estabelecimento', str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
        }
		if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $pedidos_venda->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $pedidos_venda->where('created_at', '<=', $data);
        }

		$pedidos_venda = $pedidos_venda->get();
		$retorno = [];
		$estabelecimentos = returnEmpresasNasajonView();
		$total = [
			'valor' => 0,
			'valor_pix' => 0
		];
		
		$pedidos_venda->each(function($query) use (&$retorno,$estabelecimentos,&$total){
			$com_nota = false;
			if(isset($query->pedidoNasajon->situacao_descricao) &&  $query->pedidoNasajon->situacao_descricao == 'Faturado'){
				$com_nota = true;
			}

			if(!empty($query->pix_id) && $com_nota == false){
				$retorno[] = [
					'cliente' => (isset($query->cliente->nome)) ? $query->cliente->nome : '',
					'estabelecimento' => $estabelecimentos[$query->estabelecimento],
					'pedido' => $query->id,
					'status_pedido' => $query->status_pedido_detalhes->status,
					'pagador_pix' => !empty($query->pagamentoPix->pessoa_nome) ? $query->pagamentoPix->pessoa_nome.' - '.$query->pagamentoPix->pessoa_cnpj : '',
					'situacao_pix' => !empty($query->pagamentoPix->pix_situacao) ? $query->pagamentoPix->pix_situacao : '',
					'data_pix' => !empty($query->pagamentoPix->pix_horario) ? parserDataEHora($query->pagamentoPix->pix_horario) : '',
					'conta' => !empty($query->pagamentoPix->conta_nome) ? $query->pagamentoPix->conta_nome : '',
					'valor_pix' => !empty($query->pagamentoPix->pix_valor) ? parserValor($query->pagamentoPix->pix_valor) : '',
					'valor_total_produtos' => parserValor($query->valor_total_produtos),
				];
				$total['valor'] += $query->valor_total_produtos;
				$total['valor_pix'] += !empty($query->pagamentoPix->pix_valor) ? $query->pagamentoPix->pix_valor : 0;
			}
		});

		return view('programs.acompanhamento_cielo.modal.pix')->with(['pedidos' => $retorno,'total' => $total]);
	}

	public function pixComNota(Request $request){
		set_time_limit('300');
		$fields = $request->only(['estabelecimento','pedido','data_de','data_ate']);

		$pedidos_venda = PedidoPortal::with('pedidoNasajon','status_pedido_detalhes','cliente','pagamentoPix')
		->whereIn('condicao_pagamento',$this->condicao_de_pagamento_usar_credito)
		->whereNotIn('status_pedido',[5,7,13]);

		if(!empty($fields['pedido'])){
            $pedidos_venda->where('id', $fields['pedido']);
        }
		if(!empty($fields['estabelecimento'])){
            $pedidos_venda->where('estabelecimento', str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
        }
		if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $pedidos_venda->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $pedidos_venda->where('created_at', '<=', $data);
        }

		$pedidos_venda = $pedidos_venda->get();
		$retorno = [];
		$estabelecimentos = returnEmpresasNasajonView();
		$total = [
			'valor' => 0,
			'valor_pix' => 0
		];
		
		$pedidos_venda->each(function($query) use (&$retorno,$estabelecimentos,&$total){
			$com_nota = false;
			if(isset($query->pedidoNasajon->situacao_descricao) &&  $query->pedidoNasajon->situacao_descricao == 'Faturado'){
				$com_nota = true;
			}

			if(!empty($query->pix_id) && $com_nota == true){
				$retorno[] = [
					'cliente' => (isset($query->cliente->nome)) ? $query->cliente->nome : '',
					'estabelecimento' => $estabelecimentos[$query->estabelecimento],
					'pedido' => $query->id,
					'status_pedido' => $query->status_pedido_detalhes->status,
					'pagador_pix' => !empty($query->pagamentoPix->pessoa_nome) ? $query->pagamentoPix->pessoa_nome.' - '.$query->pagamentoPix->pessoa_cnpj : '',
					'situacao_pix' => !empty($query->pagamentoPix->pix_situacao) ? $query->pagamentoPix->pix_situacao : '',
					'data_pix' => !empty($query->pagamentoPix->pix_horario) ? parserDataEHora($query->pagamentoPix->pix_horario) : '',
					'conta' => !empty($query->pagamentoPix->conta_nome) ? $query->pagamentoPix->conta_nome : '',
					'valor_pix' => !empty($query->pagamentoPix->pix_valor) ? parserValor($query->pagamentoPix->pix_valor) : '',
					'valor_total_produtos' => parserValor($query->valor_total_produtos),
				];
				$total['valor'] += $query->valor_total_produtos;
				$total['valor_pix'] += !empty($query->pagamentoPix->pix_valor) ? $query->pagamentoPix->pix_valor : 0;
			}
		});

		return view('programs.acompanhamento_cielo.modal.pix')->with(['pedidos' => $retorno,'total' => $total]);
	}

	public function boletoSemNota(Request $request){
		set_time_limit('300');
		$fields = $request->only(['estabelecimento','pedido','data_de','data_ate']);

		$pedidos_venda = PedidoPortal::with(['pedidoNasajon','status_pedido_detalhes','cliente','detalhesTransportador'])
		->whereIn('condicao_pagamento',$this->condicao_de_pagamento_usar_credito)
		->whereNotIn('status_pedido',[5,7,13]);

		if(!empty($fields['pedido'])){
            $pedidos_venda->where('id', $fields['pedido']);
        }
		if(!empty($fields['estabelecimento'])){
            $pedidos_venda->where('estabelecimento', str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
        }
		if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $pedidos_venda->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $pedidos_venda->where('created_at', '<=', $data);
        }

		$pedidos_venda = $pedidos_venda->get();
		$retorno = [];
		$estabelecimentos = returnEmpresasNasajonView();
		$total = [
			'valor' => 0
		];

		$pedidos_venda->each(function($query) use (&$retorno,$estabelecimentos,&$total){
			$com_nota = false;
			if(isset($query->pedidoNasajon->situacao_descricao) &&  $query->pedidoNasajon->situacao_descricao == 'Faturado'){
				$com_nota = true;
			}

			if(empty($query->pix_id) && $com_nota == false){
				$retorno[] = [
					'cliente' => (isset($query->cliente->nome)) ? $query->cliente->nome : '',
					'estabelecimento' => $estabelecimentos[$query->estabelecimento],
					'pedido' => $query->id,
					'status_pedido' => $query->status_pedido_detalhes->status,
					'transportadora' => $query->detalhesTransportador->nome,
					'valor_total_produtos' => parserValor($query->valor_total_produtos),
				];
				$total['valor'] += $query->valor_total_produtos;
			}

		});

	    return view('programs.acompanhamento_cielo.modal.boleto')->with(['pedidos' => $retorno,'total' => $total]);
	}

	public function boletoComNota(Request $request){
		set_time_limit('300');
		$fields = $request->only(['estabelecimento','pedido','data_de','data_ate','tipo_abertura','forma_pagamento']);

		$pedidos_venda = PedidoPortal::with('pedidoNasajon','status_pedido_detalhes','cliente','pagamentoPix')
		->whereIn('condicao_pagamento',$this->condicao_de_pagamento_usar_credito)
		->whereNotIn('status_pedido',[5,7,13]);

		if(!empty($fields['pedido'])){
            $pedidos_venda->where('id', $fields['pedido']);
        }
		if(!empty($fields['estabelecimento'])){
            $pedidos_venda->where('estabelecimento', str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
        }
		if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $pedidos_venda->where('created_at', '>=', $data);
        }
        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $pedidos_venda->where('created_at', '<=', $data);
        }

		if(!empty($fields['forma_pagamento'])){
			if($fields['forma_pagamento'] == 'pix'){
				$pedidos_venda->whereNotNull('pix_id');
			}
			if($fields['forma_pagamento'] == 'pagar_me'){
				$pedidos_venda->whereNull('id');
			}
			if($fields['forma_pagamento'] == 'stone'){
				$pedidos_venda->whereNull('id');
			}
			if($fields['forma_pagamento'] == 'usar_creditos'){
				$pedidos_venda->whereNull('pix_id');
			}
		}

		$pedidos_venda = $pedidos_venda->get();
		$retorno = [];
		$estabelecimentos = returnEmpresasNasajonView();
		$total = [
			'valor' => 0,
			'valor_pix' => 0
		];

		if($fields['tipo_abertura'] == 'usar_credito_com_nota'){
			$pedidos_venda->load('pedidoNasajon');

			$pedidos_venda = $pedidos_venda->filter(function($query){
				if(isset($query->pedidoNasajon->situacao_descricao)){
					return $query->pedidoNasajon->situacao_descricao == 'Faturado';
				}else{
					return false;
				}
			});
		}

		if($fields['tipo_abertura'] == 'usar_credito_sem_nota'){
			$pedidos_venda->load('pedidoNasajon');

			$pedidos_venda = $pedidos_venda->filter(function($query){
				if(isset($query->pedidoNasajon->situacao_descricao)){
					return $query->pedidoNasajon->situacao_descricao != 'Faturado';
				}else{
					return true;
				}
			});
		}
		
		$pedidos_venda->each(function($query) use (&$retorno,$estabelecimentos,&$total){
			$retorno[] = [
				'cliente' => (isset($query->cliente->nome)) ? $query->cliente->nome : '',
				'estabelecimento' => $estabelecimentos[$query->estabelecimento],
				'pedido' => $query->id,
				'nota_id' => (!empty($query->pedidoNasajon->notafiscal_id)) ? $query->pedidoNasajon->notafiscal_id : '',
				'nota_numero' => (!empty($query->pedidoNasajon->notafiscal_numero)) ? $query->pedidoNasajon->notafiscal_numero : '',
				'status_pedido' => $query->status_pedido_detalhes->status,
				'pix' => (!empty($query->pix_id)) ? true : false,
				'pagador_pix' => !empty($query->pagamentoPix->pessoa_nome) ? $query->pagamentoPix->pessoa_nome.' - '.$query->pagamentoPix->pessoa_cnpj : '',
				'situacao_pix' => !empty($query->pagamentoPix->pix_situacao) ? $query->pagamentoPix->pix_situacao : '',
				'data_pix' => !empty($query->pagamentoPix->pix_horario) ? parserDataEHora($query->pagamentoPix->pix_horario) : '',
				'conta' => !empty($query->pagamentoPix->conta_nome) ? $query->pagamentoPix->conta_nome : '',
				'valor_pix' => !empty($query->pagamentoPix->pix_valor) ? parserValor($query->pagamentoPix->pix_valor) : '',
				'valor_total_produtos' => parserValor($query->valor_total_produtos),
			];
			$total['valor'] += $query->valor_total_produtos;
			$total['valor_pix'] += !empty($query->pagamentoPix->pix_valor) ? $query->pagamentoPix->pix_valor : 0;
		});

		return view('programs.acompanhamento_cielo.modal.pix')->with(['pedidos' => $retorno,'total' => $total]);
	}

	public function vendaPresencial(Request $request){
		set_time_limit('300');
		$fields = $request->only(['estabelecimento','pedido','data_de','data_ate','tipo_abertura','id_transacao_stone']);
		
		$CieloPedidoObj = CieloPedido::with(['erros', 'pedidoPortal', 'pedidoPortal.pedidoNasajon']);
        $CieloPedidoObj->where('liberado', 'false');
        $CieloPedidoObj->whereNotIn('cielo_status_id', [5, 6]);

		$pedidos_stone_presencial = StoneTransacoesPedido::with(['pedido.pedidoNasajon','maquininha','pedido.cliente','pagamentoRestante' => function($query){
			$query->where('pago',true);
		},'retornoTransacaoAvulsa' => function ($query){
			$query->where('data_status','paid')
			->with('pedidoStone.pedido.pedidoNasajon','pedidoStone.maquininha');
		}])
		->whereHas('pedido',function($query){
			$query->whereNotIn('status_pedido',[5,7,13]);
		});
		
        if(!empty($fields['estabelecimento'])){
            $CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
                $portal->where('estabelecimento', $fields['estabelecimento']);
                $portal->where('status_pedido', '!=', 7);
            });
			$pedidos_stone_presencial->whereHas('pedido',function($query) use ($fields){
				$query->where("estabelecimento",str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
			});
        }else{
            $CieloPedidoObj->whereHas('pedidoPortal', function($portal) use ($fields){
                $portal->where('status_pedido', '!=', 7);
            });
        }

		if(!empty($fields['pedido'])){
            $CieloPedidoObj->where('pedido_id', $fields['pedido']);
			$pedidos_stone_presencial->where('pedido_id',$fields['pedido']);
        }

		if(!empty($fields['forma_pagamento'])){
			if($fields['forma_pagamento'] == 'usar_creditos'){
				$CieloPedidoObj->whereHas('pedido',function($query){
					$query->whereNull('pix_id');
				});
				$pedidos_stone_presencial->whereNull('id');
			}

			if($fields['forma_pagamento'] == 'pix'){
				$CieloPedidoObj->whereHas('pedido',function($query){
					$query->whereNotNull('pix_id');
				});
				$pedidos_stone_presencial->whereNull('id');
			}

			if($fields['forma_pagamento'] == 'stone'){
				$CieloPedidoObj->whereNull('id');
			}

			if($fields['forma_pagamento'] == 'pagar_me'){
				$pedidos_stone_presencial->whereNull('id');
				$CieloPedidoObj->whereHas('pedido',function($query){
					$query->where('presencial',false);
				});
			}
        }

        if(!empty($fields['data_de'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_de'])->setTime(0,0,0);
            $CieloPedidoObj->where('created_at', '>=', $data);
			$pedidos_stone_presencial->where('created_at', '>=', $data);
        }

        if(!empty($fields['data_ate'])){
            $data = Carbon::createFromFormat('d/m/Y', $fields['data_ate'])->setTime(23,59,59);
            $CieloPedidoObj->where('created_at', '<=', $data);
			$pedidos_stone_presencial->where('created_at', '<=', $data);
        }

		if($fields['tipo_abertura'] == 'debito_sem_nota' || $fields['tipo_abertura'] == 'debito_com_nota'){
			//$pedidos_stone_presencial->where('payment_type',1);

			$CieloPedidoObj->whereHas('pedidoPortal',function($query){
				$query->where('condicao_pagamento','4214');
			})
			->where('pago',true);
		}

		if($fields['tipo_abertura'] == 'credito_sem_nota' || $fields['tipo_abertura'] == 'credito_com_nota'){
			//$pedidos_stone_presencial->where('payment_type',2);

			$CieloPedidoObj->whereHas('pedidoPortal',function($query){
				$query->where('condicao_pagamento','4213');
			})
			->where('pago',true);
		}


		if(!empty($fields['id_transacao_stone'])){
			$pedidos_stone_presencial->where('id',$fields['id_transacao_stone']);
			$pedidos_stone_presencial = $pedidos_stone_presencial->get();
			$id_pedidos_presencial = $pedidos_stone_presencial->pluck('pedido_id')->toArray();
			
			$CieloPedidoObj->whereIn('pedido_id',$id_pedidos_presencial);
			$CieloPedidoObj = $CieloPedidoObj->get();
		}else{
			$pedidos_stone_presencial = $pedidos_stone_presencial->get();
			$CieloPedidoObj = $CieloPedidoObj->get();
		}
		
		if($fields['tipo_abertura'] == 'debito_sem_nota' || $fields['tipo_abertura'] == 'credito_sem_nota'){
			$CieloPedidoObj->load('pedidoPortal.pedidoNasajon');
			$pedidos_stone_presencial->load('pedido.pedidoNasajon');

			$CieloPedidoObj = $CieloPedidoObj->filter(function($query){
				return $query->pedidoPortal->pedidoNasajon->situacao_descricao != 'Faturado';
			});
			$pedidos_stone_presencial = $pedidos_stone_presencial->filter(function($query){
				return $query->pedido->pedidoNasajon->situacao_descricao != 'Faturado';
			});
		}

		if($fields['tipo_abertura'] == 'debito_com_nota' || $fields['tipo_abertura'] == 'credito_com_nota'){
			$CieloPedidoObj->load('pedidoPortal.pedidoNasajon');
			$pedidos_stone_presencial->load('pedido.pedidoNasajon');

			$CieloPedidoObj = $CieloPedidoObj->filter(function($query){
				if(isset($query->pedidoPortal->pedidoNasajon->situacao_descricao)){
					return $query->pedidoPortal->pedidoNasajon->situacao_descricao == 'Faturado';
				}else{
					return false;
				}
			});
			$pedidos_stone_presencial = $pedidos_stone_presencial->filter(function($query){
				if(isset($query->pedido->pedidoNasajon->situacao_descricao)){
					return $query->pedido->pedidoNasajon->situacao_descricao == 'Faturado';
				}else{
					return false;
				}
			});
		}

		$retorno = [];
		$retorno_bandeiras = [];
		$retorno_parcela = [];
        $estabelecimentos = returnEmpresasNasajonView();

		$pedidos_stone_presencial->each(function($query) use (&$retorno,$estabelecimentos,&$retorno_bandeiras,&$retorno_parcela,$fields){
			$tipo_pagamento = '';

			if($query->pagamento_parcial == true){
				$tipo_pagamento = 'Parcial';
			 }else if($query->payment_type === 1){
				$tipo_pagamento = 'Débito';
			 }else if($query->payment_type === 2 || $query->payment_type === 3){
				$tipo_pagamento = 'Crédito';
			 } 

			 if(empty($query->retornoTransacaoAvulsa[0])){
				$retorno[] = [
					'estabelecimento' => $estabelecimentos[ltrim($query->maquininha->estabelecimento,'0')],
					'presencial' => 'Sim',
					'pedido' => $query->pedido_id,
					'nota_id' => (!empty($query->pedido->pedidoNasajon->notafiscal_id)) ? $query->pedido->pedidoNasajon->notafiscal_id : '',
					'nota_numero' => (!empty($query->pedido->pedidoNasajon->notafiscal_numero)) ? $query->pedido->pedidoNasajon->notafiscal_numero : '',
					'cliente' => (!empty($query->pedido->cliente->nome)) ? $query->pedido->cliente->nome . ' - ' . $query->pedido->cliente->cpf_cnpj : '',
					'terminal_numero' => $query->pos_serial_number,
					'codigo_autorizacao' => $query->transaction_authorization_code,
					'nsu' => $query->stone_transaction_id,
					'tipo_pagamento' => $tipo_pagamento,
					'parcela' => $query->installments_number,
					'numero_cartao' => $query->card_number,
					'bandeira_cartao' => $query->card_brand,
					'valor_pago' => (!empty($query->transaction_amount)) ? parserValor($query->transaction_amount) : '',
					'processado' => ($query->status_pre_transacao == 1) ? true : false,
					'manual' => $query->pagamento_parcial
				];
			}else if(!empty($query->retornoTransacaoAvulsa[0])){
				$query->retornoTransacaoAvulsa->each(function($query) use ($estabelecimentos,&$retorno){
					$tipo_pagamento = '';

					if($query->metadata_account_funding_source == 'Credit'){
						$tipo_pagamento = 'Crédito';
					}else if($query->metadata_account_funding_source == 'Debit'){
						$tipo_pagamento = 'Débito';
					}

					$retorno[] = [
						'estabelecimento' => $estabelecimentos[ltrim($query->pedidoStone->maquininha->estabelecimento,'0')],
						'presencial' => 'Sim',
						'pedido' => $query->pedido_id,
						'nota_id' => (!empty($query->pedidoStone->pedido->pedidoNasajon->notafiscal_id)) ? $query->pedidoStone->pedido->pedidoNasajon->notafiscal_id : '',
						'nota_numero' => (!empty($query->pedidoStone->pedido->pedidoNasajon->notafiscal_numero)) ? $query->pedidoStone->pedido->pedidoNasajon->notafiscal_numero : '',
						'cliente' => (!empty($query->pedidoStone->pedido->cliente->nome)) ? $query->pedidoStone->pedido->cliente->nome . ' - ' . $query->pedidoStone->pedido->cliente->cpf_cnpj : '',
						'terminal_numero' => $query->metadata_terminal_serial_number,
						'codigo_autorizacao' => $query->metadata_autorization_code,
						'nsu' => $query->order_code,
						'tipo_pagamento' => $tipo_pagamento,
						'parcela' => $query->metadata_installment_quantity,
						'numero_cartao' => '',
						'bandeira_cartao' => $query->metadata_scheme_name,
						'valor_pago' => (!empty($query->data_paid_amount)) ? parserValor($query->data_paid_amount / 100) : '',
						'processado' => ($query->order_status == 'paid') ? true : false,
						'manual' => ''
					];
				});
			}

			if(!empty($query->pagamentoRestante) && !empty($query->pagamentoRestante->first())){
				if(!empty($query->pagamentoRestante->pagamentosParciais->first())){
					foreach($query->pagamentoRestante->pagamentosParciais as $restante_parcial){
						$retorno[] = [
							'estabelecimento' => $estabelecimentos[ltrim($query->maquininha->estabelecimento,'0')],
							'presencial' => 'Sim',
							'pedido' => $query->pedido_id,
							'nota_id' => (!empty($query->pedido->pedidoNasajon->notafiscal_id)) ? $query->pedido->pedidoNasajon->notafiscal_id : '',
							'nota_numero' => (!empty($query->pedido->pedidoNasajon->notafiscal_numero)) ? $query->pedido->pedidoNasajon->notafiscal_numero : '',
							'cliente' => (!empty($query->pedido->cliente->nome)) ? $query->pedido->cliente->nome . ' - ' . $query->pedido->cliente->cpf_cnpj : '',
							'terminal_numero' => 'Manual',
							'codigo_autorizacao' => $restante_parcial->codigo_autoriazacao,
							'nsu' => $restante_parcial->stone_transaction_id,
							'tipo_pagamento' => $restante_parcial->formaPagamento->descricao,
							'parcela' => (!empty($restante_parcial->parcelamentoNasajon->nome)) ? $restante_parcial->parcelamentoNasajon->nome : '',
							'numero_cartao' => 'Manual',
							'bandeira_cartao' => (!empty($restante_parcial->bandeiraNasajon->codigo)) ? $restante_parcial->bandeiraNasajon->codigo : '',
							'valor_pago' => (!empty($restante_parcial->valor)) ? parserValor($restante_parcial->valor) : '',
							'processado' => ($query->pago == true) ? true : false,
							'manual' => 'Manual'
						];
					}
				}else if(empty($query->retornoTransacaoAvulsa[0])){
					if($query->pagamentoRestante->payment_type === 1){
						$tipo_pagamento = 'Débito';
					}else if($query->pagamentoRestante->payment_type === 2 || $query->payment_type === 3){
						$tipo_pagamento = 'Crédito';
					}

					$retorno[] = [
						'estabelecimento' => $estabelecimentos[ltrim($query->maquininha->estabelecimento,'0')],
						'presencial' => 'Sim',
						'pedido' => $query->pedido_id,
						'nota_id' => (!empty($query->pedido->pedidoNasajon->notafiscal_id)) ? $query->pedido->pedidoNasajon->notafiscal_id : '',
						'nota_numero' => (!empty($query->pedido->pedidoNasajon->notafiscal_numero)) ? $query->pedido->pedidoNasajon->notafiscal_numero : '',
						'cliente' => (!empty($query->pedido->cliente->nome)) ? $query->pedido->cliente->nome . ' - ' . $query->pedido->cliente->cpf_cnpj : '',
						'terminal_numero' => $query->pagamentoRestante->pos_serial_number,
						'codigo_autorizacao' => $query->pagamentoRestante->transaction_authorization_code,
						'nsu' => $query->pagamentoRestante->stone_transaction_id,
						'tipo_pagamento' => $tipo_pagamento,
						'parcela' => $query->pagamentoRestante->installments_number,
						'numero_cartao' => $query->pagamentoRestante->card_number,
						'bandeira_cartao' => $query->pagamentoRestante->card_brand,
						'valor_pago' => (!empty($query->pagamentoRestante->transaction_amount)) ? parserValor($query->pagamentoRestante->transaction_amount) : '',
						'processado' => ($query->pagamentoRestante->status_pre_transacao == 1) ? true : false,
						'manual' => $query->pagamentoRestante->pagamento_parcial
					];
				}
			}

			$bandeira_cartao = (!empty($query->card_brand)) ? $query->card_brand : '';
			$parcelas = (!empty($query->installments_number)) ? $query->installments_number : '';

			if(!isset($retorno_bandeiras[$bandeira_cartao])){
				$retorno_bandeiras[$bandeira_cartao] = [
					'bandeira' => $bandeira_cartao,
					'valor' => 0
				];
			}
			$retorno_bandeiras[$bandeira_cartao]['valor'] += (!empty($query->transaction_amount)) ? $query->transaction_amount : 0;

			if(!isset($retorno_parcela[$parcelas])){
				$retorno_parcela[$parcelas] = [
					'parcela' => $parcelas,
					'valor' => 0
				];
			}
			$retorno_parcela[$parcelas]['valor'] += $query->transaction_amount;
		});

		$total = [
			'pedidos' => $this->somarValores($retorno, 'valor_pago', true),
			'bandeira' => $this->somarValores($retorno_bandeiras, 'valor', false),
			'parcela' => $this->somarValores($retorno_parcela, 'valor', false),
		];

	    return view('programs.acompanhamento_cielo.modal.pagamentos_presencial')->with(['pedidos' => $retorno,'bandeiras' => $retorno_bandeiras, 'parcelas' => $retorno_parcela, 'total'=>$total]);
	}

	public function pagamentosParciais(Request $request){
		$campos = $request->only(['id']);

		try{
			$pedido = PedidoPortal::with(['pagamentosStone.pagamentosParciais'])->findOrFail($campos['id']);
		}catch(Exception $e){
			return response()->json([
				"status" => 'error',
				"message" => 'Pedido não encontrado, erro: '.$e,
				"error" => [$e],
				"response" => []
			],422);
		}

		if(!isset($pedido->id)){
			return response()->json([
				"status" => 'error',
				"message" => 'Pedido não encontrado.',
				"error" => [],
				"response" => []
			],422);
		}

		$retorno = [];
		$total = 0;

		$pedido->pagamentosStone->each(function($query) use (&$retorno,&$total){
			foreach($query->pagamentosParciais as $pagamentos){
				$parcelamento = '';
				 if(empty($pagamentos->formaPagamento->descricao) && empty($pagamentos->codigo_autoriazacao)){
					$parcelamento = 'Desconto';
				 }else if(!empty($pagamentos->formaPagamento->descricao) && empty($pagamentos->codigo_autoriazacao)){
					$parcelamento = 'Dinheiro';
				 }else if(!empty($pagamentos->parcelamentoNasajon->nome)){
					$parcelamento = $pagamentos->parcelamentoNasajon->nome;
				 }

				$retorno[] = [
					'forma_pagamento' => (!empty($pagamentos->formaPagamento->descricao)) ? $pagamentos->formaPagamento->descricao : 'Desconto',
					'parcelamento' => $parcelamento,
					'codigo_autorizacao' => (!empty($pagamentos->codigo_autoriazacao)) ? $pagamentos->codigo_autoriazacao : '',
					'tid' => (!empty($pagamentos->documento_cartao)) ? $pagamentos->documento_cartao : '',
					'bandeira' => (!empty($pagamentos->bandeiraNasajon->codigo)) ? $pagamentos->bandeiraNasajon->codigo : '',
					'criado_por' => (!empty($pagamentos->updated_by->name)) ? $pagamentos->updated_by->name : $pagamentos->createdby->name,
					'valor' => parserValor($pagamentos->valor),
				];
				$total += $pagamentos->valor;
			}
		});

		$total = parserValor($total);

		return view('programs.acompanhamento_cielo.modal.pagamentos_parciais')->with(['transacoes' => $retorno,'total'=>$total]);
	}

	public function listarEstornos(Request $request){
		$campos = $request->only(['pedido_id']);

		try{
            $id = decrypt($campos['pedido_id']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return, 422);
        }

		$pedido_pagarme = CieloPedido::with('pedidoPortal','estornos','estornos.createdBy','transacoes')->find($id);
		$retorno = [];

		$pedido_pagarme->estornos->each(function($query) use ($pedido_pagarme,&$retorno){
			$retorno[] = [
				'usuario_sistema' => $query->createdBy->name,
				'valor_transacao' => parserValor($pedido_pagarme->transacoes->valor),
				'valor_estorno' => (!empty($query->valor_estorno)) ? parserValor($query->valor_estorno) : parserValor($pedido_pagarme->transacoes->valor),
				'estorno_parcial' => (!empty($pedido_pagarme->estornos[0]->valor_estorno) && $pedido_pagarme->estornos->sum('valor_estorno') < $pedido_pagarme->transacoes->valor) ? true : false,
				'id_cielo' => encrypt($pedido_pagarme->id),
				'data' => parserDataEHora($query->created_at)
			];
		});

	    return view('programs.acompanhamento_cielo.modal.lista_estorno')->with(['dados' => $retorno]);
	}

}
