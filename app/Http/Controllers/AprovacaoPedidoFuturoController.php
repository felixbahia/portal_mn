<?php

namespace App\Http\Controllers;

use Auth;

use App\User;
use App\AprovacaoDePedido;
use App\ClienteNasajon;
use App\ComprasFuturas;
use App\PedidoPortal;
use App\ProdutosEstoque;
use App\Cliente;
use App\Cotacoes;
use App\HistoricoPedido;
use App\TransportadorNasajon;
use App\CondicoesPagamentoWeb;
use App\MotivoRecusaPedido;
use App\ComprasNasajon;
use App\PedidosReservaProdutoNasajon;
use App\PedidoRjSp;

use Illuminate\Http\Request;

use App\Http\Controllers\UserController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\EmailController;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class AprovacaoPedidoFuturoController extends Controller
{
	private $estabelecimentos_prologos = [1, 2, 3, 4];
    //
	public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AprovacaoPedidoFuturo") === false){
            return abort(403);
        }
		$request->session()->flash('model', 'App\AprovacaoPedidoFuturo');

		if (strtolower(Auth::user()->tipo_usuario->nome) === "supervisor"){
			$users = [];
			$users[] = '1';
			$users = array_merge($users, UserController::varreSubordinados(Auth::user()->responsavel));
		}
		else if (!in_array(Auth::user()->tipo_usuario_id, [1, 15])){
			$users = [];
			$users[] = '1';
			$users = array_merge($users, UserController::varreSubordinados(Auth::id()));
		}
		else{
			$users = User::whereNotNull('codigo_representante')->orderby('codigo_representante')->get()->pluck('id');
		}

        $representantes = [];

        foreach ($users as $value) {
			$user_info = User::find($value);
			if(!empty($user_info->codigo_representante)){
				$representantes[$user_info->id] = $user_info->codigo_representante . " - " .$user_info->name;
			}
		}


		$estabelecimentos = returnEmpresasNasajonView();
		unset($estabelecimentos[0]);
		unset($estabelecimentos[5]);
		unset($estabelecimentos[20]);

		if (Auth::user()->username == 'nelson.namura'){
			unset($estabelecimentos[3]);
			unset($estabelecimentos[4]);
		}

		if (date('d') <= 15){
			$quinzenas[1] = '1ª de ' . parserNameMonth(date('m')) . '/' . date('y');
			$quinzenas[2] = '2ª de ' . parserNameMonth(date('m')) . '/' . date('y');
			$quinzenas[3] = '1ª de ' . parserNameMonth(date('m', strtotime('last day of +1 month'))) . '/' . date('y', strtotime('last day of +1 month'));
			$quinzenas[4] = '2ª de ' . parserNameMonth(date('m', strtotime('last day of +1 month'))) . '/' . date('y', strtotime('last day of +1 month'));
		}
		else{
			$quinzenas[1] = '2ª de ' . parserNameMonth(date('m') . '/' . date('y'));
			$quinzenas[2] = '1ª de ' . parserNameMonth(date('m', strtotime('last day of +1 month'))) . '/' . date('y', strtotime('last day of +1 month'));
			$quinzenas[3] = '2ª de ' . parserNameMonth(date('m', strtotime('last day of +1 month'))) . '/' . date('y', strtotime('last day of +1 month'));
			$quinzenas[4] = '1ª de ' . parserNameMonth(date('m', strtotime('last day of +2 month'))) . '/' . date('y', strtotime('last day of +2 month'));
		}

		$quinzenas[6] = 'Todas';

        return view('programs.aprovacao_pedido_futuro.index')->with(['representantes' => $representantes, 'estabelecimentos' => $estabelecimentos, 'quinzenas' => $quinzenas]);
	}

	public function filter(Request $request){
        ini_set('max_execution_time', 500);
		ini_set('memory_limit', '1024M');

		$fields = $request->only(['estabelecimento', 'nome_cliente', 'pedido', 'representantes', 'quinzenas', 'start', 'length', 'draw', 'columns', 'order', 'numero_pcmn', 'linha_produto']);
        $order = $fields["order"];
        $column = $fields["columns"];
        if(!empty($order) && !empty($column)){
            $order_name = $column[intval($order[0]['column'])]["data"];
			$column_temp = $column;
			$column = [];
			foreach($column_temp as $c){
				if($c['orderable'] == "true"){
					$column[] = $c['data'];
				}
			}
			unset($column_temp);
            $order_dir = $order[0]["dir"];
            switch ($order_name){
                case 'estabelecimento':
                    $order_name = 'pedido.estabelecimento';
				break;
                case 'cliente':
                    $order_name = '';
				break;
                case 'codigo':
                    $order_name = 'pedido_item.cod_produto';
				break;
                case 'pedido':
                    $order_name = 'pedido.id';
				break;
                case 'valor':
                    $order_name = 'pedido.valor_total_nota';
				break;
				case 'condicao_pagamento':
					$order_name = '';
				break;
				case 'vendedor':
					$order_name = '';
				break;
				case 'data_previsao_entrega':
					$order_name = 'pedido.data_previsao_entrega';
				break;
            }
        }
        else{
            $order_name = '';
            $order_dir = '';
        }

        $offset = intval($fields["start"]);
        $limit = intval($fields["length"]);
		$estabelecimento = returnEmpresasNasajonView();
		$pedidoQuery = PedidoPortal::query()->with(['itens_pedido.comprasNasajon']);
		$data = '';

		if(isset($fields['pedido']) && !empty($fields['pedido'])){
			$pedidoQuery->where('id', $fields['pedido']);
		}

		$pedidoQuery->where('status_pedido', 8)
			->where('pedido_futuro', 'true');

		if (isset($fields['quinzenas']) && strlen($fields['quinzenas']) > 0) {

			$Carbon = new Carbon;
			
			switch ($fields['quinzenas']) {
				case 1:
					if(date('d') <= 15){
						$data = [$Carbon->format('Y-m-01'), $Carbon->format('Y-m-15')];
					}
					else{
						$data = [$Carbon->format('Y-m-16'), $Carbon->format('Y-m-t')];	
					}
					break;
				case 2:
					if(date('d') <= 15){
						$data = [$Carbon->format('Y-m-16'), $Carbon->format('Y-m-t')];	
					}
					else{
						$data = [$Carbon->addMonthNoOverflow()->format('Y-m-01'), $Carbon->format('Y-m-15')];	

					};
					break;
				case 3:
					if(date('d') <= 15){
						$data = [$Carbon->addMonthNoOverflow()->format('Y-m-01'), $Carbon->format('Y-m-15')];	
					}
					else{
						$data = [$Carbon->addMonthNoOverflow()->format('Y-m-16'), $Carbon->format('Y-m-t')];	
					};
					break;
				case 4:
					if(date('d') <= 15){
						$data = [$Carbon->addMonthNoOverflow()->format('Y-m-16'), $Carbon->format('Y-m-t')];	
					}
					else{
						$data = [$Carbon->addMonthsNoOverflow(2)->format('Y-m-01'), $Carbon->format('Y-m-15')];	
					};
					break;
			}
		}

		if (!empty($data)){
			$pedidoQuery->whereBetween('data_previsao_entrega', $data);
		}

		if (isset($fields['representantes']) && strlen($fields['representantes']) > 0){
			$pedidoQuery->where('usuario', $fields['representantes']);
		}
		else if (strtolower(Auth::user()->tipo_usuario->nome) === "supervisor"){

			$users = UserController::varreSubordinados(Auth::user()->responsavel);
			$users[] = User::where('codigo_representante', '001')->first()->id;

			$pedidoQuery->whereIn('usuario', $users);

		}
		else{
			if (!in_array(Auth::user()->tipo_usuario_id, [1, 15])){

				$users = UserController::varreSubordinados(Auth::id());
				$users[] = User::where('codigo_representante', '001')->first()->id;

				$pedidoQuery->whereIn('usuario', $users);
			}
		}
		if (Auth::user()->username == 'nelson.namura'){
			$pedidoQuery->whereIn('estabelecimento', [1,2]);
		}

		if (isset($fields['estabelecimento']) && !empty($fields['estabelecimento'])){
			$pedidoQuery->where("estabelecimento", intval($fields['estabelecimento']));
		}
		
		if (isset($fields['nome_cliente']) && !empty($fields['nome_cliente'])){
			$pedidoQuery->where(function($query) use ($fields){
				$cliente_busca = ClienteNasajon::select('codigo')->where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'),'ilike', '%'.trim($fields['nome_cliente']).'%')->where('bloqueado', 'false')->get();
				
				$query->orWhereIn('cod_cliente', $cliente_busca->pluck('codigo'));
			});

		}

		if (isset($fields['numero_pcmn']) && !empty($fields['numero_pcmn'])){
			$pedidoQuery->whereHas('itens_pedido', function($query) use ($fields){
				$query->where('numero_compra', $fields['numero_pcmn']);
			});
		}
		if (isset($fields['linha_produto']) && !empty($fields['linha_produto'])){
			$pedidoQuery->whereHas('itens_pedido.especificacoes', function($query) use ($fields){
				$query->where('linha', $fields['linha_produto']);
			});
		}
		
		
		$total_query = $pedidoQuery->count();

		if(strlen($offset) > 0 && !empty($limit)){
			$pedidoQuery
				->offset($offset)
				->limit($limit);
		}
		$pedidos = $pedidoQuery->get();
		
		$pedidos = $pedidos->sortBy(function ($item) {
			if($item->nasajon === false){
				return $item->cliente->NOME;
			}
			else{
				if(isset($item->cliente)){
					return $item->cliente->nome;
				}
			}
		})
		->sortBy(function ($item){
			return $item->id;
		})
		->sortBy(function ($item){
			return $item->data_previsao_entrega;
		});

		// dd($pedidos);
		
		$produtoController = new ProdutoController;
		$return = [];
		foreach ($pedidos as $pedido) {
			$mostrar_botoes = true;

			if (isset($pedido)){
				$estoque = 'success';
				$estoque_futuro = true;
				$numero_pcmn = [];
				$total = 0;
				foreach ($pedido->itens_pedido as $value) {
					$numero_pcmn[$value->numero_compra] = $value->numero_compra;
					$verificacao_estoque_compras = false;

					$estoque_produto = ProdutosEstoque::where('codigo_produto', $value->cod_produto)->where('estabelecimento', str_pad($pedido->estabelecimento, 2, '0', STR_PAD_LEFT))->first();
					if(!empty($value->comprasNasajon)){
						if(!in_array($value->comprasNasajon->situacao_item, ['Liquidado', 'Parcialmente Liquidado', 'Cancelado'])){
							$estoque = 'error';
							$estoque_futuro = true;
						}else{
							$verificacao_estoque_compras = true;
						}
					}else{
						$verificacao_estoque_compras = true;
					}
					if($verificacao_estoque_compras){
						$PedidosVendaNasajon = PedidosReservaProdutoNasajon::select(DB::raw('sum(quantidade) as quantidade'))->where('codigo_produto', $value->cod_produto)->where('codigo_estabelecimento', str_pad($pedido->estabelecimento, 2, '0', STR_PAD_LEFT))->first();
						$PedidoPortalReservaObj = PedidoPortal::select()
							->with(['itens_pedido' => function($query) use ($value){
								$query->where("cod_produto", $value->cod_produto)
								->whereNull('deleted_at');
							}]);
							$PedidoPortalReservaObj->whereNotIn('status_pedido', [3, 5, 7, 8]);
							$PedidoPortalReservaObj->where('estabelecimento', $pedido->estabelecimento)
							->whereHas('itens_pedido', function($query) use ($value){
								$query->where("cod_produto", $value->cod_produto)
								->whereNull('deleted_at');
							})
							->where('id', '!=', $pedido->id);
						$PedidoPortalReservaObj = $PedidoPortalReservaObj->get();

						$item_portal = 0;

						foreach($PedidoPortalReservaObj as $pedido_reserva_portal){
							foreach($pedido_reserva_portal->itens_pedido as $item_reserva_portal){
								$item_portal += $item_reserva_portal->quantidade;
							}
						}

						if(is_object($estoque_produto)){
							if(
								isset($estoque_produto->estoque) &&
								floatval($estoque_produto->estoque) >= floatval($value->quantidade)
							){
								if(
									isset($PedidosVendaNasajon->quantidade) &&
									(floatval($estoque_produto->estoque) - floatval($PedidosVendaNasajon->quantidade) - $item_portal) < floatval($value->quantidade)
								){
									if($estoque != 'warning'){
										$estoque = 'error';
									}
								}
							}else{
								if($estoque != 'warning'){
									$estoque = 'error';
								}
							}
							if(
								$estoque == 'error' &&
								(floatval($estoque_produto->estoque) - (isset($PedidosVendaNasajon->quantidade) - $item_portal ? floatval($PedidosVendaNasajon->quantidade) : 0 )) > 0
							){
								$estoque = 'warning';
							}
		
							$estoque_futuro = $estoque_futuro && isset($estoque_produto->estoque) && intval($estoque_produto->compras) >= $value->quantidade;
						}
						else{
							if($estoque != 'warning'){
								$estoque = 'error';
							}
							$estoque_futuro = false;
						}
					}
					
					
					$total += $value->preco_unitario * $value->quantidade;
				}

				$mostrar_botao = $estoque;
				
				if(isset($pedido->cliente)){
					$nome_cliente = $pedido->cliente->nome;
				}
				else{
					$nome_cliente = $pedido->cod_cliente;
					$mostrar_botao = false;
				}

				foreach($numero_pcmn as $key => $value){
					$ComprasNasajonObj = ComprasNasajon::where('numero_pedido', $value)->where('estabelecimento', str_pad($pedido->estabelecimento, 2, "0", STR_PAD_LEFT))->
						first();
					$numero_pcmn[$key] = '';
					if(!empty($ComprasNasajonObj)){
						$numero_pcmn[$key] = encrypt($ComprasNasajonObj->id_nota);
					}
				}
				$condicao_pagamento = '';
				if(isset($pedido->condicao_pagamento_detalhes->descricao)){
					$condicao_pagamento = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $pedido->condicao_pagamento_detalhes->descricao . "''>". $pedido->condicao_pagamento_detalhes->descricao ."</div></div>";
				}

				$return[] = [
					'id' => $pedido->id,
					'pedido_id' => $pedido->id,
					'estabelecimento_cod' => $pedido->estabelecimento,
					'estabelecimento' => "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $estabelecimento[$pedido->estabelecimento] . "''>". $estabelecimento[$pedido->estabelecimento] ."</div></div>",
					'cliente' => "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $nome_cliente . "''>". $nome_cliente ."</div></div>",
					'cliente_nome' => $nome_cliente,
					'pedido' => $pedido->id,
					'valor' => parserValor($total),
					'condicao_pagamento' => $condicao_pagamento,
					'vendedor' => "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='". $pedido->usuario_detalhes->codigo_representante ." - ". $pedido->usuario_detalhes->name ."'>". $pedido->usuario_detalhes->codigo_representante ." - ". $pedido->usuario_detalhes->name ."</div></div>",
					'estoque' => $estoque,
					'estoque_futuro' => $estoque_futuro,
	                'data_previsao_entrega' => parserData($pedido->data_previsao_entrega),
					'mostrar_botao_aprovar' => $mostrar_botao,
					'numero_pcmn' => $numero_pcmn
				];
			}
		}
		$return = [
			"draw" => $fields["draw"],
			"recordsTotal" => $total_query,
			"recordsFiltered" => $total_query,
			"data" => $return,
			"erro" => ''
		];
		return response()->json($return);
	}

	public function modalDeletar(Request $request){
		$fields = $request->only('id', 'pedido_id', 'estabelecimento', 'cliente_nome');

		return view('programs.aprovacao_pedido_futuro.modal.delete')->with(['aprovacao_id' => $fields['id'],'pedido_id' => $fields['pedido_id'], 'estabelecimento' => $fields['estabelecimento'], 'cliente_nome' => $fields['cliente_nome']]);
	}

	public function deletar(Request $request){
		$fields = $request->only('aprovacao_id', 'pedido_id', 'estabelecimento', 'cliente_nome');

		$AprovacaoDePedidoObj = AprovacaoDePedido::find($fields['aprovacao_id']);
		if(!is_null($AprovacaoDePedidoObj)){
			$AprovacaoDePedidoObj->deleted_by = Auth::id();
			$AprovacaoDePedidoObj->save();
			$AprovacaoDePedidoObj->delete();
		}
		
		$PedidoPortalObj = PedidoPortal::find($fields['pedido_id']);
        if(empty($PedidoPortalObj)){
            return response()->json(['status' => 'success', 'message' => '', 'error' => [], 'response' => []], 200);
        }
        $PedidoPortalObj->deleted_by = Auth::id();
        $PedidoPortalObj->save();
		$PedidoPortalObj->delete();

        $HistoricoPedidoObj = new HistoricoPedido();
		$HistoricoPedidoObj->pedido = $PedidoPortalObj->id;
		$HistoricoPedidoObj->natureza = 'cancelamento';
        $HistoricoPedidoObj->antigo = 'Pedido cancelado pelo usuario';
		$HistoricoPedidoObj->created_by = Auth::id();
		$HistoricoPedidoObj->save();
		
		$this->sendEmail(str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT), $fields['pedido_id'], $fields['cliente_nome']);

		$response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
	}

	private function sendEmail($estabelecimento, $pedido, $cliente_nome){
		$EmailObj = new EmailController();
		$email_send = [];
		$variaveis = [
            'nome_cliente' => $cliente_nome,
            'numero_pedido' => $pedido
		];
		$returnEmail = $EmailObj->sendEmailToken($estabelecimento, "cancelamento_aprovacao_futuro", $email_send, $variaveis);
	}

	public function aprovaPedidoFuturo(Request $request){

		$id = $request->id;

		$pedidoObj = PedidoPortal::with('itens_pedido', 'itens_pedido.precos', 'itens_pedido.comprasNasajon','itens_pedido.estoque', 'usuario_detalhes')->find($id);

		if(is_null($pedidoObj)){
			return response()->json([
				'status' => 'error',
				'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
				'error' => [
					'msg' => 
					[
						'user' => 'Pedido não localizado! Favor verificar com o setor responsável.'
					],
				],
					'response' => []
				], 
			422);
		}
		else if($pedidoObj->status_pedido != 8){
			return response()
			->json(
				[
					'status' => 'success', 
					'message' => 'O pedido não está aguardando aprovação.',
					'error' => [],
					'response' => []
				],
			200);
		}

		if($pedidoObj->nasajon === false){

			$cliente_prologos = $pedidoObj->cod_cliente;
			$transportadora_prologos = $pedidoObj->transportadora;
			$transportadora_redespacho_prologos = $pedidoObj->transportadora_redespacho;

			$clienteNasajon = ClienteNasajon::where('cpf_cnpj', $pedidoObj->cliente->CGC_CPF)->where('bloqueado', 'false')->first();

			if(is_null($clienteNasajon)){

				$historicoPedidoObj = new HistoricoPedido();
				$historicoPedidoObj->pedido = $pedidoObj->id;
				$historicoPedidoObj->natureza = 'erro';
				$historicoPedidoObj->novo = 'Cliente não localizado na Nasajon';
				$historicoPedidoObj->created_by = Auth::id();
				$historicoPedidoObj->save();

				return response()->json([
					'status' => 'error',
					'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
					'error' => [
						'msg' => 
						[
							'user' => 'Cliente não encontrado! Favor verificar com o setor responsável.'
						],
					],
						'response' => []
					], 
				422);

			}
			else{
				$pedidoObj->cod_cliente = $clienteNasajon->codigo;

				if(!empty($pedidoObj->transportadora)){
					$transportador = TransportadorNasajon::where('cnpj', $pedidoObj->detalhesTransportador->CGC)->first();
					
					if(!empty($transportador)){
						$pedidoObj->transportadora = $transportador->codigo;
					}
					else{

						$historicoPedidoObj = new HistoricoPedido();
						$historicoPedidoObj->pedido = $pedidoObj->id;
						$historicoPedidoObj->natureza = 'erro';
						$historicoPedidoObj->novo = 'Transportadora não encontrada no Nasajon';
						$historicoPedidoObj->created_by = Auth::id();
						$historicoPedidoObj->save();

						return response()->json([
							'status' => 'error',
							'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
							'error' => [
								'msg' => 
								[
									'user' => 'Transportadora não encontrada! Favor verificar com o setor responsável.'
								],
							],
								'response' => []
							], 
						422);
					}
				}
				
				if(!empty($pedidoObj->transportadora_redespacho)){
					$transportadorRedespacho = TransportadorNasajon::where('cnpj', $pedidoObj->detalhesTransportadorRedespacho->CGC)->first();
					
					if(!empty($transportadorRedespacho)){
						$pedidoObj->transportadora_redespacho = $transportadorRedespacho->codigo;
					}
					else{

						$historicoPedidoObj = new HistoricoPedido();
						$historicoPedidoObj->pedido = $pedidoObj->id;
						$historicoPedidoObj->natureza = 'erro';
						$historicoPedidoObj->novo = 'Transportadora de redespacho não encontrada no Nasajon';
						$historicoPedidoObj->created_by = Auth::id();
						$historicoPedidoObj->save();

						return response()->json([
							'status' => 'error',
							'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
							'error' => [
								'msg' => 
								[
									'user' => 'Transportadora de redespacho não encontrada! Favor verificar com o setor responsável'
								],
							],
								'response' => []
							], 
						422);
					}
				}

				$condicaoPagamentoNasajon = CondicoesPagamentoWeb::
				where('descricao', $pedidoObj->condicao_pagamento_detalhes->descricao)
				->where('nasajon', true)
                ->where('ativo', true)
				->first();
				
				if(is_null($condicaoPagamentoNasajon)){

					$historicoPedidoObj = new HistoricoPedido();
					$historicoPedidoObj->pedido = $pedidoObj->id;
					$historicoPedidoObj->natureza = 'erro';
					$historicoPedidoObj->novo = 'Condição de pagamento não cadastrada para o Nasajon';
					$historicoPedidoObj->created_by = Auth::id();
					$historicoPedidoObj->save();

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                        'error' => [
                            'msg' => 'Condição de pagamento não encontrada! Favor verificar com o setor responsável.'
                        ],
                            'response' => []
                        ], 
                    422);
				}

				$pedidoObj->condicao_pagamento = $condicaoPagamentoNasajon->id;

				$pedidoObj->nasajon = true;

			}
		}

		$itens = $pedidoObj->itens_pedido;

		$sem_preco = $itens->filter(function ($item, $key) {

			return (!isset($item->precos) || is_null($item->precos->preco_real));

		});

		if($sem_preco->isNotEmpty()){
			return response()->json([
				'status' => 'error',
				'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
				'error' => [
					'msg' => 
					[
						'user' => 'Os produtos abaixo estão sem preço em real cadastrado! Favor procurar o setor responsável: <br>' . 
						$sem_preco->implode('cod_produto', ', ')
					],
				],
					'response' => []
				], 
			422);
		}

		$produtoControllerObj = new ProdutoController;

		$produtos_sem_estoque = '';
		$compra_cancelada = '';
		$compra_aberta = '';
		$pedido_compra_numero = '';
		$data_agora = Carbon::now();
		foreach($pedidoObj->itens_pedido as $item){
			$result_estoque = $produtoControllerObj->retornarDadosEstoqueNasajon($item->especificacoes, $pedidoObj->estabelecimento, $pedidoObj, false, '', true);
			
			$PedidoPortalVerificacaoObj = PedidoPortal::with(['itens_pedido.comprasNasajon'])->
				whereNotIn('status_pedido', [3, 5, 7])
				->where("estabelecimento", $pedidoObj->estabelecimento)
				->whereHas('itens_pedido', function($query) use ($item){
					$query->where("cod_produto", $item->cod_produto);
				});
			$PedidoPortalVerificacaoObj = $PedidoPortalVerificacaoObj->get();
			$data_agora_verificacao = Carbon::now();
			
			if(intval($data_agora_verificacao->format('d')) > 15){
				$data_agora_verificacao = $data_agora_verificacao->lastOfMonth();
			}else{
				$data_agora_verificacao = Carbon::parse($data_agora->format('Y-m').'-15');
			}
			
			$reserva_value = 0;
			foreach($PedidoPortalVerificacaoObj as $pedido_verificacao){
				$quantidade = 0;
				$data_previsao_entrega = Carbon::parse($pedido_verificacao->data_previsao_entrega);
				foreach($pedido_verificacao->itens_pedido as $item_verificacao){
					if($item_verificacao->cod_produto == $item->cod_produto){
						$quantidade += $item_verificacao->quantidade;
					}					
				}
				if($pedido_verificacao->pedido_futuro == false){
					$reserva_value += $quantidade;
				}else if($item->numero_compra != $item_verificacao->numero_compra){
					if($data_agora_verificacao->gte($data_previsao_entrega)){
						$reserva_value += $quantidade;
					}                
				}				
			}
			
			$pronta_entraga = parserNumber($result_estoque['pronta_entrega']);
			$estoque_e_reserva = $pronta_entraga;
			if(
                !isset($result_estoque['pronta_entrega']) ||
                $estoque_e_reserva < $item->quantidade
            ){
				//dd($result_estoque);
				$produtos_sem_estoque = str_replace(".", ", ", $produtos_sem_estoque).$item->cod_produto.' - '.$item->especificacoes->descricao." - Estoque Atual Pronta Entrega: ".parserValor($result_estoque['pronta_entrega']).".";
			} 
			if(!empty($item->comprasNasajon)){
				if(in_array($item->comprasNasajon->situacao, ['Cancelado']) && $pronta_entraga < $item->quantidade){
					$compra_cancelada = str_replace(".", ", ", $produtos_sem_estoque).$item->cod_produto.' - '.$item->especificacoes->descricao.".";
				}else if(in_array($item->comprasNasajon->situacao, ['Aberto']) && $pronta_entraga < $item->quantidade){
					$compra_aberta = str_replace(".", ", ", $produtos_sem_estoque).$item->cod_produto.' - '.$item->especificacoes->descricao.".";
				}

				$pedido_compra_numero = $item->comprasNasajon->numero_pedido;
			}

			
		}

		if(!empty($compra_cancelada)){
			return response()->json([
				'status' => 'error',
				'message' => 'Pedido de Compra Cancelado',
				'error' => [
					'msg' => 
					[
						'user' => 'O(s) produto(s) abaixo está(ão) cancelado(s) no pedido de compra '.$pedido_compra_numero.': <br>' . 
						$compra_cancelada.'<br/><br/><br/>'
					],
				],
					'response' => []
				], 
			422);
		}else if(!empty($compra_aberta)){
			return response()->json([
				'status' => 'error',
				'message' => 'Pedido de Compra Aberto',
				'error' => [
					'msg' => 
					[
						'user' => 'O(s) produto(s) abaixo está(ão) aberto(s) no pedido de compra '.$pedido_compra_numero.': <br>' . 
						$compra_aberta.'<br/><br/><br/>'
					],
				],
					'response' => []
				], 
			422);
		}
		//dd($produtos_sem_estoque);
		if(!empty($produtos_sem_estoque)){
			return response()->json([
				'status' => 'error',
				'message' => 'Produto indisponível!',
				'error' => [
					'msg' => 
					[
						'user' => 'O(s) produto(s) abaixo está(ão) com estoque disponível menor que solicitado no pedido: <br>' . 
						$produtos_sem_estoque.'<br/><br/><br/><br/>'
					],
				],
					'response' => []
				], 
			422);
		}

		$historicoPedidoObj = new HistoricoPedido;
		$historicoPedidoObj->pedido = $pedidoObj->id;
		$historicoPedidoObj->novo = '';
		$historicoPedidoObj->natureza = 'Pedido programado aprovado';
		$historicoPedidoObj->created_by = Auth::id();
		$historicoPedidoObj->save();
		if(intval($pedidoObj->estabelecimento) === 3){
			$hoje = Carbon::Now();

			$cotacaoDoDiaObj = Cotacoes::where('data', $hoje)
			->wherehas('moeda', function($query){
				$query->where('codigo','220');
			})
			->orderBy('lastupdate', 'desc')->first();

			if(empty($cotacaoDoDiaObj)){

				$historicoPedidoObj = new HistoricoPedido;
				$historicoPedidoObj->pedido = $pedidoObj->id;
				$historicoPedidoObj->novo = '';
				$historicoPedidoObj->natureza = 'Pedido programado com erro pois não há cotação do dia cadastrada ';
				$historicoPedidoObj->created_by = Auth::id();
				$historicoPedidoObj->save();
				
				return response()->json([
					'status' => 'error',
					'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
					'error' => [
						'msg' => 
						[
							'user' => 'Não há cotação de dólar cadastrada para hoje! Favor verificar com o setor responsável.'
						],
					],
						'response' => []
					], 
				422);
			}
			else{
				$pedidoObj->status_pedido = 4;
				$pedidoObj->save();

				$mudanca_comissao = false;
				if($pedidoObj->usuario_detalhes->tipo_usuario_id == 12){
					$mudanca_comissao = true;
				}

				$pedidoObj->itens_pedido->each(function($item) use ($cotacaoDoDiaObj, $mudanca_comissao){
					$preco_unitario = round($item->preco_unitario * $cotacaoDoDiaObj->valor, 2);
					$item->preco_unitario = round($item->preco_unitario * $cotacaoDoDiaObj->valor, 2);
					if($mudanca_comissao && $item->comissao <= 3){

						$arr = [];
						$arr['codprd'] = $item->cod_produto;
						$arr['pedido'] = $item->pedido;
						$arr['preco_base_antes'] = '';
						$arr['preco_antes'] = '';
						$arr['comissao_antes'] = '';

						$arr_request = new Request($arr);

						$produtoControllerObj = new ProdutoController();
						$verificao_preco = $produtoControllerObj->retornaInformacoesPreco($arr_request, true, true);

						$desconto_permitido = 9.01;
						
						$coluna_a = parserNumber($verificao_preco['preco_unitario']);
						$desconto = ((1 - ($preco_unitario / $coluna_a)) * 100);

                        if($desconto <= $desconto_permitido){
                            $comissao_porcentagem = 3;
                        }else{
                            $comissao_porcentagem = 2;
						}
						$item->comissao = $comissao_porcentagem;
					}
				});

				$valor_frete = 0;
				$valor_desconto = 0;

				if($pedidoObj->tipo_frete == 'C'){
					$valor_frete = $pedidoObj->valor_frete;
				}

				if(!empty($pedidoObj->valor_desconto)){
					$valor_desconto = $pedidoObj->valor_desconto;
				}

				$pedidoObj->status_pedido = 2;
				$pedidoObj->pedido_futuro = false;
				$pedidoObj->valor_total_produtos = $pedidoObj->valor_total->total;
				$pedidoObj->valor_total_nota = $pedidoObj->valor_total_produtos + $valor_frete - $valor_desconto;
				$pedidoObj->push();

				$historicoPedidoObj->pedido = $pedidoObj->id;
				$historicoPedidoObj->novo = '';
				$historicoPedidoObj->natureza = 'Pedido programado encaminhado para aprovação como pedido efetivo - Cotação do dólar na hora da aprovação: ' . $cotacaoDoDiaObj->valor .  ' - Última atualização na Nasajon: ' . $cotacaoDoDiaObj->lastupdate;
				$historicoPedidoObj->created_by = Auth::id();
				$historicoPedidoObj->save();

			}
		}
			
		$aprovacaoObj = AprovacaoDePedido::where('pedido_id', $pedidoObj->id)->first();
		if(!is_null($aprovacaoObj)){
			$aprovacaoObj->delete();
		}

		$historicoPedidoObj->pedido = $pedidoObj->id;
		$historicoPedidoObj->novo = 'Data atualizada para liberação de pedido futuro de '.$pedidoObj->data_previsao_entrega. '  '.Carbon::now();
		$historicoPedidoObj->natureza = 'data_atualizada';
		$historicoPedidoObj->created_by = Auth::id();
		$historicoPedidoObj->save();

		if(in_array($pedidoObj->tipo_venda, ['rj_x_sp_futuro', 'rj_x_sp_triangular_futuro', 'pre_pago_rj_x_sp_futuro'])){
			switch (intval($pedidoObj->tipo_venda)) {
				case 'rj_x_sp_futuro':
					$pedidoObj->tipo_venda  = "rj_x_sp";
					break;
				case 'rj_x_sp_triangular_futuro':
					$pedidoObj->tipo_venda = "rj_x_sp_triangular";
					break;
				default:
					$pedidoObj->tipo_venda = 'pre_pago_rj_x_sp';
					break;
			}
		}

		$pedidoObj->data_previsao_entrega = Carbon::now();
		$pedidoObj->pedido_futuro = false;
		$pedidoObj->status_pedido = 2;
		$pedidoObj->save();

		Artisan::queue('pedido:validacao', ['pedido' => $pedidoObj->id]);

		return response()
		->json(
			[
				'status' => 'success', 
				'message' => 'O pedido foi para aprovação.',
				'error' => [],
				'response' => []
			],
		200);
	}

	public function modalRecusa(Request $request){
		$fields = $request->only('id');

		$PedidoPortalObj = PedidoPortal::with('cliente', 'usuario_detalhes')->find($fields['id']);
		$estabelecimentos = returnEmpresasNasajonView();

		if(!empty($PedidoPortalObj->cliente)){
			$cliente = $PedidoPortalObj->cliente->nome;
		}else{
			$cliente = $PedidoPortalObj->cod_cliente;
		}

		$info = [
			'id' => $PedidoPortalObj->id,
			'estabelecimento' => $estabelecimentos[$PedidoPortalObj->estabelecimento],
			'cliente' => $cliente,
			'valor' => parservalor($PedidoPortalObj->valor_total['total']),
			'vendedor' => $PedidoPortalObj->usuario_detalhes['name']
		];

		$MotivoRecusaPedidoObj = MotivoRecusaPedido::all();
		$motivos = [''=>''];
		foreach ($MotivoRecusaPedidoObj as $key => $value) {
			$motivos[$value['motivo']] = $value['motivo'];
		}

		return view("programs.aprovacao_pedido_futuro.modal.recusar")->with(['info' => $info, 'motivos' => $motivos]);

	}

	public function reprovarPedidoFuturo(Request $request){
		$fields = $request->only('id', 'motivo_rejeicao');

		if (empty(trim($fields['motivo_rejeicao']) || !isset($fields['motivo_rejeicao']))){
			$error = [
				'status' => 'error',
				'message' => 'Informações inválidas',
				'errors' => [
					'motivo_rejeicao' => 'Digite uma justificativa'
				]
			];

			return response()->json($error, 422);
		}

		$PedidoPortalObj = PedidoPortal::with('cliente', 'usuario_detalhes')->find($fields['id']);
		
		$PedidoPortalObj->status_pedido = 5;
		$PedidoPortalObj->motivo_rejeicao = $fields['motivo_rejeicao'];
		$PedidoPortalObj->save();
		
		$historicoPedidoObj = new HistoricoPedido();

		$historicoPedidoObj->pedido = $fields['id'];
		$historicoPedidoObj->natureza = 'recusa futuro';
		$historicoPedidoObj->novo = $fields['motivo_rejeicao'];
		$historicoPedidoObj->created_by = Auth::id();

		$historicoPedidoObj->save();
		
		return response()->json([], 200);
	}
}
