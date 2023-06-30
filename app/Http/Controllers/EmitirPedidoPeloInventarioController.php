<?php

namespace App\Http\Controllers;
use Auth;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

use App\InventarioProduto;
use App\OperacaoNasajon;
use App\UserNajason;
use App\NasajonEstabelecimento;
use App\TransportadorNasajon;
use App\InventarioHistorico;
use App\InventarioHistoricoProduto;
use App\InventarioHistoricoProdutoPeca;
use App\PedidoPortal;
use App\PedidoItemPortal;
use App\ClienteNasajon;
use App\PedidosVendaNasajon;
use App\PedidoInventario;
use App\NotasCfopNasajon;
use App\NotaItensNasajon;
use App\PedidoRjSp;

use App\Http\Controllers\EmailController;
use Illuminate\Support\Facades\Log;

class EmitirPedidoPeloInventarioController extends Controller
{
	public function index(Request $request){
		if(Auth::user()->hasPermissionTo("programas App\EmitirPedidoPeloInventario") === false){
			return abort(403);
		}
		$request->session()->flash('model', 'App\EmitirPedidoPeloInventario');

		return view("programs.emitir_pedido_inventario.index");
	}

	public function filtro(Request $request){
		$fields = $request->only(['estabelecimento']);

		$empresa = returnEmpresasNasajonView();
		$InventarioProdutoObj = InventarioProduto::select('estabelecimento', DB::raw('sum(cast(quantidade_produto as float)) as quantidade'))->groupBy('estabelecimento');
		$InventarioProdutoObj->where('codigo_produto', '!=', '');
		$InventarioProdutoObj->where(DB::raw('cast(quantidade_produto as float)'), '>', '0');
		if(strlen($fields['estabelecimento']) > 0){
			$InventarioProdutoObj->where('estabelecimento', $fields['estabelecimento']);
		}
		if(Auth::id() == 21){
			$InventarioProdutoObj->whereIn('estabelecimento', ['3', '4']);
		}
		if(Auth::id() == 45){
			$InventarioProdutoObj->whereIn('estabelecimento', ['5']);
		}

		$inventarioProduto = $InventarioProdutoObj->get();

		$retorno = [];
		$inventarioProduto->each(function($inventario) use (&$retorno, $empresa){
			$retorno[intval($inventario->estabelecimento)] = [
				'criterios' => encrypt(["estabelecimento" => $inventario->estabelecimento]),
				'estabelecimento' => $empresa[intval($inventario->estabelecimento)],
				'estabelecimento_codigo' => $inventario->estabelecimento,
				'quantidade' => parserValor($inventario->quantidade),
			];
		});
		$response = [
			"status" => 'success',
			"message" => '',
			"error" => [],
			"response" => $retorno
		];
		return response()->json($response);
	}

	public function gerarPedido(Request $request){
        set_time_limit(10000);
        ini_set('memory_limit','10024M');
		$fields = $request->only(['criterios', 'remessa_retorno']);
        $criterios = decrypt($fields['criterios']);

        $InventarioProdutoObj = InventarioProduto::with(['produtoEspecificacao', 'produtoNasajon', 'produtoEspecificacao.custos', 'produtoEspecificacao.preco']);
        $InventarioProdutoObj->where('estabelecimento', $criterios['estabelecimento']);
		$InventarioProdutoObj->where('codigo_produto', '!=', '');
		$InventarioProdutoObj->where(DB::raw('cast(quantidade_produto as float)'), '>', '0');
		$produtos_inventario = [];
		$total = 0;
		$InventarioProdutoObj = $InventarioProdutoObj->get();
		$InventarioProdutoObj->each(function($inventario) use (&$produtos_inventario, $criterios){
			if(!isset($produtos_inventario[$inventario['codigo_produto']])){
				$produtos_inventario[$inventario['codigo_produto']] = [
					'quantidade' => 0,
					'preco' => 0,
					'peca' => [],
					'produto_codigo' => $inventario['codigo_produto'],
					'produto' => $inventario->produtoNasajon
				];
				$produtoObj = $inventario->produtoEspecificacao;
				$custo = $produtoObj->estoque->where('estabelecimento', str_pad($criterios['estabelecimento'], 2, "0", STR_PAD_LEFT))->first();
				$custo_portal = $produtoObj->custos->where('estabelecimento', str_pad($criterios['estabelecimento'], 2, "0", STR_PAD_LEFT))->first();
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
				/**
				 * Regra preço transferencia
				 */
				/*switch($criterios['estabelecimento']){
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
						$custo = $custo / 0.82;
					break;
				}*/
				$produtos_inventario[$inventario['codigo_produto']]['preco'] = (float) number_format((float) $custo, 2, '.', '');
			}
			$produtos_inventario[$inventario['codigo_produto']]['quantidade'] += (float) $inventario['quantidade_produto'];
			$produtos_inventario[$inventario['codigo_produto']]['peca'][] = $inventario;
		});
		if(empty($produtos_inventario)){
			return response()->json([
				'status' => 'success',
				'message' => 'Pedido Gerado com sucesso',
				'error' => [],
				'response' => []
			],200);
		}
		$NasajonEstabelecimentoObj = NasajonEstabelecimento::where('codigo', str_pad($criterios['estabelecimento'], 2, "0", STR_PAD_LEFT))->first();
		$ClienteNasajonObj = ClienteNasajon::where(DB::raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), $NasajonEstabelecimentoObj->raizcnpj. $NasajonEstabelecimentoObj->ordemcnpj)->where('bloqueado', 'false')->first();
		$cliente_uuid = "'".$ClienteNasajonObj->id."'::uuid";
		$estabelecimento_uuid = "'".$NasajonEstabelecimentoObj->estabelecimento."'::uuid";

		$cliente_conta_e_ordem_uuid = 'null';
		$formapagamento_uuid = 'null';
		$parcelamento_uuid = 'null';

		$transportadora = TransportadorNasajon::where('codigo', '0026')->first();
		$transportadora_uuid = "'".$transportadora->id."'";

		$transportadora_redespacho_uuid = 'null';
		$valor_total = 0;
		$desconto = 0;
		$tipo_frete = '3';

		$usuario_cadastro_uuid = UserNajason::where('nome', 'Mestre')->first()->usuario;
		$operacao = '';
		//$codigo_operacao = 'PEDIDOMUDANCA';
		$codigo_operacao = $fields['remessa_retorno'] === 'remessa'? 'PEDIDOREMESSAFORA' : 'PEDIDORETVENDAFORA';
		if(!isset(OperacaoNasajon::where('codigo', $codigo_operacao)->first()->operacao)){
			return response()->json([
				'status' => 'erro',
				'message' => 'Código de operação '.$codigo_operacao.' não cadastrado, por favor verificar com o setor fiscal.',
				'error' => [],
				'response' => []
			],422);
		}
		$operacao = OperacaoNasajon::where('codigo', $codigo_operacao)->first()->operacao;
		$operacao = "'".$operacao."'";
		$vendedor = "null";
		$observacao = "";

		$valor_frete = 0;

		$tipooperacao = '23';
		$numero_pedido_cliente = '';
		$indicador_pagamento = '1'; // 0 = a vista | 1 = a prazo
		$modo_compra = '2';
		
		$porcentagem_comissao = 0;
		$cfop = $fields['remessa_retorno'] === 'remessa'? '5904': '1904';

		$data_pedido = date('Y-m-d');
		
		//$observacao_nota = 'Mercadorias serão entregues no novo endereço, Rua: Dr. Carlos Botelho,177 - Brás - São Paulo/SP - CEP: 03017-010\nNão incidência  do ICMS conforme resposta consulta 2.422/2013 e não incidência  de IPI conforme art. 38,IV do RIPI/2010';
		if($fields['remessa_retorno'] === 'remessa'){
			$observacao_nota = 'Mercadorias serão entregues no endereço, Expo Center Norte, Rua: José Bernardo Pinto,333 - Vila Guilherme - São Paulo - SP - CEP: 02055-000\nEmitida nos termos da Portaria CAT nº 127/2015';
		}else{
			$observacao_nota = 'Mercadorias serão entregues no endereço, Rua: Dr. Carlos Botelho,177 - Brás - São Paulo/SP - CEP: 03017-010\nNão incidência  do ICMS conforme resposta consulta 2.422/2013 e não incidência  de IPI conforme art. 38,IV do RIPI/2010';
		}
		
		$usuario_cadastro_uuid = UserNajason::where('nome', 'Mestre')->first()->usuario;
		$produtos_inventario = array_chunk($produtos_inventario, 300);
		foreach($produtos_inventario as $key => $produtos){
			$valor_total = 0;
			foreach ($produtos as $item){
				$valor_total += ($item['quantidade'] * $item['preco']);
			}

			$PedidoPortalObj = new PedidoPortal();
			$PedidoPortalObj->data_pedido = $data_pedido;
			$PedidoPortalObj->usuario = 1;
			$PedidoPortalObj->cod_cliente = $ClienteNasajonObj->codigo;
			$PedidoPortalObj->nome_comprador = $ClienteNasajonObj->nome;
			$PedidoPortalObj->email_comprador = $ClienteNasajonObj->email;
			$PedidoPortalObj->status_pedido = 3;
			$PedidoPortalObj->estabelecimento = $criterios['estabelecimento'];
			$PedidoPortalObj->pedido_futuro = false;
			$PedidoPortalObj->condicao_pagamento = null;
			$PedidoPortalObj->no_pedido_compra = '';
			$PedidoPortalObj->cod_usuario_autorizador = 1;
			$PedidoPortalObj->data_previsao_entrega = $data_pedido;
			$PedidoPortalObj->tipo_frete = 'P';
			$PedidoPortalObj->observacao = '';
			$PedidoPortalObj->transportadora = $transportadora->codigo;
			$PedidoPortalObj->transportadora_redespacho = '';
			$PedidoPortalObj->comissao = 0;
			$PedidoPortalObj->created_by = 1;
			$PedidoPortalObj->base_icms = 0;
			$PedidoPortalObj->valor_icms = 0;
			$PedidoPortalObj->base_icmsst = 0;
			$PedidoPortalObj->valor_icmsst = 0;
			$PedidoPortalObj->valor_frete = 0;
			$PedidoPortalObj->valor_seguro = 0;
			$PedidoPortalObj->valor_desconto = 0;
			$PedidoPortalObj->outros_valores = 0;
			$PedidoPortalObj->valor_ipi = 0;
			$PedidoPortalObj->valor_total_produtos = $valor_total;
			$PedidoPortalObj->valor_total_nota = $valor_total;
			$PedidoPortalObj->erro_integracao = '';
			$PedidoPortalObj->motivo_rejeicao = '';
			$PedidoPortalObj->codigo_operacao = $codigo_operacao;
			$PedidoPortalObj->conta_e_ordem = false;
			$PedidoPortalObj->cod_cliente_conta_e_ordem = '';
			$PedidoPortalObj->tipo_venda = 'pronta_entrega_venda';
			$PedidoPortalObj->frete_preco = 'fob';
			$PedidoPortalObj->nasajon = true;
			$PedidoPortalObj->save();
			
			$sql_api_insercao = "select * from integracoes.api_pedidovendanovo (\n".
				"uuid_generate_v4(),/* pedido chave*/\n".
				"current_date,/* data */\n".
				"{$estabelecimento_uuid},/* estabelecimento */\n".
				"{$cliente_uuid},/* cliente */\n".
				"{$vendedor},/* vendedor */\n".
				"'{$observacao}',/* observacao */\n".
				"{$formapagamento_uuid},/* forma pagamento */\n".
				"{$parcelamento_uuid},/* parcelamento */\n".
				"{$valor_total},/* valor total */\n".
				"{$desconto},/* desconto */\n".
				"{$transportadora_uuid},/* transportador */\n".
				"{$transportadora_redespacho_uuid},/* transportador redespacho */\n".
				"{$tipo_frete},/* tipo de frete */\n".
				"{$valor_frete},/* valor de frete */\n".
				"'{$usuario_cadastro_uuid}',/* usuario */\n".
				"{$indicador_pagamento},/* indicador pagamento */\n".
				"'{$cfop}',/* cfop */\n".
				"{$tipooperacao}, /* tipo operacao */\n".
				"'{$numero_pedido_cliente}', /* numero pedido cliente */\n".
				"{$modo_compra}, /* modo de compra */\n".
				"{$cliente_conta_e_ordem_uuid}, /* cliente conta e ordem */\n".
				"{$operacao}, /* operacao */\n".
				"{$porcentagem_comissao}, /* comissão vendedor */\n".
				"'{$data_pedido}',/* data de entrega */\n".
				"'{$observacao_nota}'/* observacao da nota */\n".
			");";

			try{
				$insert_nasajon = DB::connection('nasajon')->select($sql_api_insercao);
			}catch(\Exception $e){
				return response()->json([
					'status' => 'error',
					'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
					'error' => [$e, $sql_api_insercao],
					'response' => []
				], 422);
			}
			
			$mensagem_nasajon = $insert_nasajon[0]->mensagem;

			$mensagem_nasajon = json_decode($mensagem_nasajon, true);
			if($mensagem_nasajon['codigo'] !== 'OK'){
				return response()->json([
					'status' => 'error',
					'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
					'error' => [$mensagem_nasajon['mensagem'], $sql_api_insercao],
					'response' => []
				], 422);
			}
			$pedido_uuid = $mensagem_nasajon['mensagem'];
			$PedidosVendaNasajonObj = PedidosVendaNasajon::find($pedido_uuid);

			foreach($produtos as $produto => $valores){
				$produto = $valores['produto']->codigo;
				$produto_uuid = "'".$valores['produto']->produto."'";
				$quantidade = floatval($valores['quantidade']);
				$unidade_uuid = DB::connection('nasajon')->select("select unidade_id from estoque.vwunidades where unidade_codigo = '{$valores['produto']->unidade}' and estabelecimento_id = {$estabelecimento_uuid}")[0]->unidade_id;
				$unidade_uuid = "'".$unidade_uuid."'";

				$valor_unitario = $valores['preco'];
				$PedidoItemPortalObj = new PedidoItemPortal();
				$PedidoItemPortalObj->pedido = $PedidoPortalObj->id;
				$PedidoItemPortalObj->usuario = 1;
				$PedidoItemPortalObj->cod_produto = $produto;
				$PedidoItemPortalObj->quantidade = $quantidade;
				$PedidoItemPortalObj->preco_unitario = $valor_unitario;
				$PedidoItemPortalObj->created_by = 1;
				$PedidoItemPortalObj->valor_icms = 0;
				$PedidoItemPortalObj->base_calculo_icms = 0;
				$PedidoItemPortalObj->valor_ipi = 0;
				$PedidoItemPortalObj->aliquota_icms = 0;
				$PedidoItemPortalObj->aliquota_ipi = 0;
				$PedidoItemPortalObj->valor_frete = 0;
				$PedidoItemPortalObj->valor_total = $quantidade * $valor_unitario;
				$PedidoItemPortalObj->comissao = 0;
				$PedidoItemPortalObj->preco_base = $valor_unitario;
				$PedidoItemPortalObj->contador = 1;
				$PedidoItemPortalObj->preco_original = 0;
				$PedidoItemPortalObj->save();
				$valor_desconto = floatval(0);
				
				$sql_api_insercao_itens = "select * from integracoes.api_pedidovenda_itemnovo (
					uuid_generate_v4(),
					'{$pedido_uuid}',
					{$produto_uuid},
					{$quantidade},
					{$unidade_uuid},
					{$valor_unitario},
					{$valor_desconto},
					'{$cfop}'
				);";
				try{
					$insercao_produto = DB::connection('nasajon')->select($sql_api_insercao_itens);
				}catch(\Exception $e){
					return response()->json([
						'status' => 'error',
						'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
						'error' => $e,
						'response' => []
					], 422);
				}
				$mensagem_nasajon = json_decode($insercao_produto[0]->mensagem, true);
				if($mensagem_nasajon['codigo'] !== 'OK'){
					return response()->json([
						'status' => 'error',
						'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
						'error' => [$mensagem_nasajon['mensagem']],
						'response' => []
					], 422);
				}
			}

			$sql_api_validacao = "select * from integracoes.api_pedidovenda_processar('".$pedido_uuid."', '".$usuario_cadastro_uuid."')";
			try{
				if(empty($PedidoPortalObj->pedido_gerado)){
					$insert_nasajon = DB::connection('nasajon')->select($sql_api_validacao);
				}else{
					return response()->json([
						'status' => 'error',
						'message' => 'Pedido já foi gerado!',
						'error' => [],
						'response' => []
					], 422);
				}
			}catch(\Exception $e){
				return response()->json([
					'status' => 'error',
					'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
					'error' => $e,
					'response' => []
				], 422);
			}
			$mensagem_nasajon = json_decode($insert_nasajon[0]->mensagem, true);
			if($mensagem_nasajon['codigo'] !== 'OK'){
				return response()->json([
					'status' => 'error',
					'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
					'error' => [$mensagem_nasajon['mensagem']],
					'response' => []
				], 422);
			}
			$codigo_pedido = $PedidosVendaNasajonObj->numero;
			$PedidoPortalObj->pedido_gerado = $codigo_pedido;
			$PedidoPortalObj->save();

			$this->salvarHistoricoInventario($produtos, $PedidoPortalObj, $criterios);
		}


		return response()->json([
			'status' => 'success',
			'message' => 'Pedido Gerado com sucesso',
			'error' => [],
			'response' => []
		]);
	}

	private function salvarHistoricoInventario($produtos, $PedidoPortalObj, $criterios){
		$produtos_inventario = [];

        $InventarioHistoricoObj = new InventarioHistorico();
        $InventarioHistoricoObj->inventario_nasajaon = Str::uuid();
        $InventarioHistoricoObj->estabelecimento = $criterios['estabelecimento'];
        $InventarioHistoricoObj->contagem = '1';
        $InventarioHistoricoObj->data = date('Y-m-d H:i:s');
        $InventarioHistoricoObj->created_by = Auth::id();
        $InventarioHistoricoObj->save();

		$produtos = collect($produtos);
		$produtos->each(function($inventario) use (&$produtos_inventario){
			foreach($inventario['peca'] as $peca){
				if(!isset($produtos_inventario[$peca->codigo_produto])){
					$produtos_inventario[$peca->codigo_produto] = [
						'quantidade' => 0,
						'produto_codigo' => $peca->codigo_produto,
						'codigo_barras' => $peca->produto_codigobarras,
						'pecas' => [],
					];
				}
				$produtos_inventario[$peca->codigo_produto]['quantidade'] += $peca->quantidade_produto;
				$produtos_inventario[$peca->codigo_produto]['pecas'][] = $peca;
	
				$peca->delete();
			}
		});
		unset($produtos);
		$produtos_inventario = collect($produtos_inventario);
		$produtos_inventario->each(function($produto) use($InventarioHistoricoObj){
			$InventarioHistoricoProdutoObj = new InventarioHistoricoProduto();
			$InventarioHistoricoProdutoObj->inventario_historicos_id = $InventarioHistoricoObj->id;
			$InventarioHistoricoProdutoObj->codigo_produto = $produto['produto_codigo'];
			$InventarioHistoricoProdutoObj->quantidade = $produto['quantidade'];
			$InventarioHistoricoProdutoObj->codigo_barras = $produto['codigo_barras'];
			$InventarioHistoricoProdutoObj->created_by = Auth::id();
			$InventarioHistoricoProdutoObj->save();
			$produto['pecas'] = collect($produto['pecas']);
			$produto['pecas']->each(function($peca) use($InventarioHistoricoProdutoObj){
				$InventarioHistoricoProdutoPecaObj = new InventarioHistoricoProdutoPeca();
				$InventarioHistoricoProdutoPecaObj->inventario_historico_produtos_id = $InventarioHistoricoProdutoObj->id;
				$InventarioHistoricoProdutoPecaObj->codigo_peca = $peca->codigo_barras;
				$InventarioHistoricoProdutoPecaObj->endereco = $peca->endereco;
				$InventarioHistoricoProdutoPecaObj->quantidade = $peca->quantidade_produto;
				$InventarioHistoricoProdutoPecaObj->created_by = Auth::id();
				$InventarioHistoricoProdutoPecaObj->usuario_id = $peca->created_by;
				$InventarioHistoricoProdutoPecaObj->save();
			});
		});

		$PedidoInventarioObj = new PedidoInventario();
		$PedidoInventarioObj->pedido_id = $PedidoPortalObj->id;
		$PedidoInventarioObj->inventario_historico_id = $InventarioHistoricoObj->id;
		$PedidoInventarioObj->created_by = Auth::id();
		$PedidoInventarioObj->save();
	}

	public function modalGerarPedido(Request $request){
		$fields = $request->only(['criterios']);
        $criterios = decrypt($fields['criterios']);

        $InventarioProdutoObj = InventarioProduto::with(['produtoEspecificacao', 'produtoNasajon', 'produtoEspecificacao.custos', 'produtoEspecificacao.preco']);
        $InventarioProdutoObj->where('estabelecimento', $criterios['estabelecimento']);
		$InventarioProdutoObj->where('codigo_produto', '!=', '');
		$InventarioProdutoObj->where(DB::raw('cast(quantidade_produto as float)'), '>', '0');
		$produtos_inventario = [];
		$total = 0;
		$InventarioProdutoObj = $InventarioProdutoObj->get();
		$InventarioProdutoObj->each(function($inventario) use (&$produtos_inventario, $criterios){
			if(!isset($produtos_inventario[$inventario['codigo_produto']])){
				$produtos_inventario[$inventario['codigo_produto']] = [
					'quantidade' => 0,
					'preco' => 0,
					'peca' => [],
					'produto_codigo' => $inventario['codigo_produto'],
					'produto' => $inventario->produtoNasajon
				];
				$produtoObj = $inventario->produtoEspecificacao;
				$custo = $produtoObj->estoque->where('estabelecimento', str_pad($criterios['estabelecimento'], 2, "0", STR_PAD_LEFT))->first();
				$custo_portal = $produtoObj->custos->where('estabelecimento', str_pad($criterios['estabelecimento'], 2, "0", STR_PAD_LEFT))->first();
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
				$produtos_inventario[$inventario['codigo_produto']]['preco'] = (float) number_format((float) $custo, 2, '.', '');
			}
			$produtos_inventario[$inventario['codigo_produto']]['quantidade'] += (float) $inventario['quantidade_produto'];
			$produtos_inventario[$inventario['codigo_produto']]['peca'][] = $inventario;
		});

		foreach($produtos_inventario as $index => $value){
			$produtos_inventario[$index]['quantidade'] = parserValor($value['quantidade']);
			$produtos_inventario[$index]['preco'] = parserValor($value['preco']);
		}

		return view("programs.emitir_pedido_inventario.modal.geracao_pedido")->with(['criterios' => encrypt($criterios), 'produtos_inventario' => $produtos_inventario]);
	}

	public function reposicaoMercadoriaFeira(){
		set_time_limit(10000);
        ini_set('memory_limit','10024M');

		$data_atual = Carbon::now()->subDay();
		$data_inicio = Carbon::createFromFormat('Y-m-d','2022-10-06');
		$data_fim = Carbon::createFromFormat('Y-m-d','2022-10-12');

		$notasNasajonObj = NotasCfopNasajon::select();
		$notasNasajonObj->with(['itens_nota.especificacaos.estoque', 'itens_nota.especificacaos.produtoNasajon']);
		$notasNasajonObj->whereIn('cfop', ['5104', '6104']);
		$notasNasajonObj->whereBetween('emissao', [$data_inicio, $data_fim]);
		$notasNasajonObj->where('emissao', $data_atual);
		$notasNasajonObj = $notasNasajonObj->get();

		$produtos = [];
		foreach($notasNasajonObj as $nota){
			foreach($nota->itens_nota as $item){
				if(empty($produtos[$item->codigo])){
					$produtos[$item->codigo] = [
						'quantidade' => 0,
						'dados' => $item->especificacaos,
					];
				}

				$produtos[$item->codigo]['quantidade'] += $item->quantidadecomercial;
			}
		}

		$produto_com_estoque_zerado = [];
		$produtos_inventario = [];
		foreach($produtos as $produto => $value){
			$saldo_produto = DB::connection('nasajon')->select("SELECT * FROM integracoes.exportar_produtos_saldos('05', '{$produto}');")[0];
			$estoque = empty($saldo_produto)? 0 : $saldo_produto->saldo_fiscal;

			$notaItensNasajonObj = NotaItensNasajon::select();
			$notaItensNasajonObj->where('codigo', $produto);
			$notaItensNasajonObj->whereHas('notaCfop', function($query){
				$query->whereIn('cfop', ['5104', '6104']);
			});
			$quantidade_enviada = $notaItensNasajonObj->sum('quantidadecomercial');
			
			$quantidade_feira = $quantidade_enviada - $value['quantidade'];

			$estoque = $estoque - $quantidade_feira;
			
			if($value['quantidade'] > $estoque){
				$quantidade_reenviar = $estoque;
				$produto_com_estoque_zerado[$produto] = $value['dados'];
			}else{
				$quantidade_reenviar = $value['quantidade'];
			}
			if($quantidade_reenviar > 0){
				$produtos_inventario[$produto] = [
					'quantidade' => $quantidade_reenviar,
					'preco' => 0,
					'peca' => [],
					'produto_codigo' => $produto,
					'estoque_estabelecimento' => $estoque,
					'estoque_feira' => $quantidade_feira,
					'descricao' => $value['dados']->descricao,
					'produto' => $value['dados'],
				];
	
				$produtoObj = $value['dados'];
	
				$custo = $produtoObj->estoque->where('estabelecimento', str_pad(5, 2, "0", STR_PAD_LEFT))->first();
				$custo_portal = $produtoObj->custos->where('estabelecimento', str_pad(5, 2, "0", STR_PAD_LEFT))->first();
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
	
				$produtos_inventario[$produto]['preco'] = (float) number_format((float) $custo, 2, '.', '');
			}			
		}

		$NasajonEstabelecimentoObj = NasajonEstabelecimento::where('codigo', str_pad(5, 2, "0", STR_PAD_LEFT))->first();
		$ClienteNasajonObj = ClienteNasajon::where(DB::raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), $NasajonEstabelecimentoObj->raizcnpj. $NasajonEstabelecimentoObj->ordemcnpj)->where('bloqueado', 'false')->first();
		$cliente_uuid = "'".$ClienteNasajonObj->id."'::uuid";
		$estabelecimento_uuid = "'".$NasajonEstabelecimentoObj->estabelecimento."'::uuid";

		$cliente_conta_e_ordem_uuid = 'null';
		$formapagamento_uuid = 'null';
		$parcelamento_uuid = 'null';

		$transportadora = TransportadorNasajon::where('codigo', '0026')->first();
		$transportadora_uuid = "'".$transportadora->id."'";

		$transportadora_redespacho_uuid = 'null';
		$valor_total = 0;
		$desconto = 0;
		$tipo_frete = '3';

		$usuario_cadastro_uuid = UserNajason::where('nome', 'Mestre')->first()->usuario;
		$operacao = '';
		$codigo_operacao = 'PEDIDOREMESSAFORA';
		$operacao = OperacaoNasajon::where('codigo', $codigo_operacao)->first()->operacao;
		$operacao = "'".$operacao."'";
		$vendedor = "null";
		$observacao = "";

		$valor_frete = 0;

		$tipooperacao = '23';
		$numero_pedido_cliente = '';
		$indicador_pagamento = '1'; // 0 = a vista | 1 = a prazo
		$modo_compra = '2';
		
		$porcentagem_comissao = 0;
		$cfop = '5904';

		$data_pedido = date('Y-m-d');
		$teste = [];
		$observacao_nota = 'Mercadorias serão entregues no endereço, Expo Center Norte, Rua: José Bernardo Pinto,333 - Vila Guilherme - São Paulo - SP - CEP: 02055-000\nEmitida nos termos da Portaria CAT nº 127/2015';
		$usuario_cadastro_uuid = UserNajason::where('nome', 'Mestre')->first()->usuario;
		$produtos_inventario = array_chunk($produtos_inventario, 300);
		$valor_total = 0;
		$loop_1 = 0;
		$loop_2 = 0;
		foreach($produtos_inventario as $key => $produtos){
			foreach ($produtos as $item){
				$valor_total += ($item['quantidade'] * $item['preco']);
				$loop_2++;
			}
		}

		$PedidoPortalObj = new PedidoPortal();
		$PedidoPortalObj->data_pedido = $data_pedido;
		$PedidoPortalObj->usuario = 1;
		$PedidoPortalObj->cod_cliente = $ClienteNasajonObj->codigo;
		$PedidoPortalObj->nome_comprador = $ClienteNasajonObj->nome;
		$PedidoPortalObj->email_comprador = $ClienteNasajonObj->email;
		$PedidoPortalObj->status_pedido = 3;
		$PedidoPortalObj->estabelecimento = 5;
		$PedidoPortalObj->pedido_futuro = false;
		$PedidoPortalObj->condicao_pagamento = null;
		$PedidoPortalObj->no_pedido_compra = '';
		$PedidoPortalObj->cod_usuario_autorizador = 1;
		$PedidoPortalObj->data_previsao_entrega = $data_pedido;
		$PedidoPortalObj->tipo_frete = 'P';
		$PedidoPortalObj->observacao = '';
		$PedidoPortalObj->transportadora = $transportadora->codigo;
		$PedidoPortalObj->transportadora_redespacho = '';
		$PedidoPortalObj->comissao = 0;
		$PedidoPortalObj->created_by = 1;
		$PedidoPortalObj->base_icms = 0;
		$PedidoPortalObj->valor_icms = 0;
		$PedidoPortalObj->base_icmsst = 0;
		$PedidoPortalObj->valor_icmsst = 0;
		$PedidoPortalObj->valor_frete = 0;
		$PedidoPortalObj->valor_seguro = 0;
		$PedidoPortalObj->valor_desconto = 0;
		$PedidoPortalObj->outros_valores = 0;
		$PedidoPortalObj->valor_ipi = 0;
		$PedidoPortalObj->valor_total_produtos = $valor_total;
		$PedidoPortalObj->valor_total_nota = $valor_total;
		$PedidoPortalObj->erro_integracao = '';
		$PedidoPortalObj->motivo_rejeicao = '';
		$PedidoPortalObj->codigo_operacao = $codigo_operacao;
		$PedidoPortalObj->conta_e_ordem = false;
		$PedidoPortalObj->cod_cliente_conta_e_ordem = '';
		$PedidoPortalObj->tipo_venda = 'pronta_entrega_venda';
		$PedidoPortalObj->frete_preco = 'fob';
		$PedidoPortalObj->nasajon = true;
		$PedidoPortalObj->save();
		
		$sql_api_insercao = "select * from integracoes.api_pedidovendanovo (\n".
			"uuid_generate_v4(),\n".
			"current_date,\n".
			"{$estabelecimento_uuid},\n".
			"{$cliente_uuid},\n".
			"{$vendedor},\n".
			"'{$observacao}',\n".
			"{$formapagamento_uuid},\n".
			"{$parcelamento_uuid},\n".
			"{$valor_total},\n".
			"{$desconto},\n".
			"{$transportadora_uuid},\n".
			"{$transportadora_redespacho_uuid},\n".
			"{$tipo_frete},\n".
			"{$valor_frete},\n".
			"'{$usuario_cadastro_uuid}',\n".
			"{$indicador_pagamento},\n".
			"'{$cfop}',\n".
			"{$tipooperacao}, \n".
			"'{$numero_pedido_cliente}', \n".
			"{$modo_compra}, \n".
			"{$cliente_conta_e_ordem_uuid},\n".
			"{$operacao}, \n".
			"{$porcentagem_comissao}, \n".
			"'{$data_pedido}',\n".
			"'{$observacao_nota}'\n".
		");";

		try{
			$insert_nasajon = DB::connection('nasajon')->select($sql_api_insercao);
		}catch(\Exception $e){
			return response()->json([
				'status' => 'error',
				'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
				'error' => [$e, $sql_api_insercao],
				'response' => []
			], 422);
		}
		
		$mensagem_nasajon = $insert_nasajon[0]->mensagem;

		$mensagem_nasajon = json_decode($mensagem_nasajon, true);
		if($mensagem_nasajon['codigo'] !== 'OK'){
			return response()->json([
				'status' => 'error',
				'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
				'error' => [$mensagem_nasajon['mensagem'], $sql_api_insercao],
				'response' => []
			], 422);
		}
		$pedido_uuid = $mensagem_nasajon['mensagem'];
		$PedidosVendaNasajonObj = PedidosVendaNasajon::find($pedido_uuid);

		foreach($produtos_inventario as $key => $produtos){
			foreach($produtos as $produto => $valores){
				$produto = $valores['produto']->codigo_produto;
				$produto_uuid = "'".$valores['produto']->produtoNasajon->produto."'";
				$quantidade = floatval($valores['quantidade']);
				$unidade_uuid = DB::connection('nasajon')->select("select unidade_id from estoque.vwunidades where unidade_codigo = '{$valores['produto']->unidade}' and estabelecimento_id = {$estabelecimento_uuid}")[0]->unidade_id;
				$unidade_uuid = "'".$unidade_uuid."'";

				$valor_unitario = $valores['preco'];
				$PedidoItemPortalObj = new PedidoItemPortal();
				$PedidoItemPortalObj->pedido = $PedidoPortalObj->id;
				$PedidoItemPortalObj->usuario = 1;
				$PedidoItemPortalObj->cod_produto = $produto;
				$PedidoItemPortalObj->quantidade = $quantidade;
				$PedidoItemPortalObj->preco_unitario = $valor_unitario;
				$PedidoItemPortalObj->created_by = 1;
				$PedidoItemPortalObj->valor_icms = 0;
				$PedidoItemPortalObj->base_calculo_icms = 0;
				$PedidoItemPortalObj->valor_ipi = 0;
				$PedidoItemPortalObj->aliquota_icms = 0;
				$PedidoItemPortalObj->aliquota_ipi = 0;
				$PedidoItemPortalObj->valor_frete = 0;
				$PedidoItemPortalObj->valor_total = $quantidade * $valor_unitario;
				$PedidoItemPortalObj->comissao = 0;
				$PedidoItemPortalObj->preco_base = $valor_unitario;
				$PedidoItemPortalObj->contador = 1;
				$PedidoItemPortalObj->preco_original = 0;
				$PedidoItemPortalObj->save();
				$valor_desconto = floatval(0);
				
				$sql_api_insercao_itens = "select * from integracoes.api_pedidovenda_itemnovo (
					uuid_generate_v4(),
					'{$pedido_uuid}',
					{$produto_uuid},
					{$quantidade},
					{$unidade_uuid},
					{$valor_unitario},
					{$valor_desconto},
					'{$cfop}'
				);";
				try{
					$insercao_produto = DB::connection('nasajon')->select($sql_api_insercao_itens);
				}catch(\Exception $e){
					return response()->json([
						'status' => 'error',
						'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
						'error' => $e,
						'response' => []
					], 422);
				}
				$mensagem_nasajon = json_decode($insercao_produto[0]->mensagem, true);
				if($mensagem_nasajon['codigo'] !== 'OK'){
					return response()->json([
						'status' => 'error',
						'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
						'error' => [$mensagem_nasajon['mensagem']],
						'response' => []
					], 422);
				}
			}

			$sql_api_validacao = "select * from integracoes.api_pedidovenda_processar('".$pedido_uuid."', '".$usuario_cadastro_uuid."')";
			try{
				if(empty($PedidoPortalObj->pedido_gerado)){
					$insert_nasajon = DB::connection('nasajon')->select($sql_api_validacao);
				}else{
					return response()->json([
						'status' => 'error',
						'message' => 'Pedido já foi gerado!',
						'error' => [],
						'response' => []
					], 422);
				}
			}catch(\Exception $e){
				return response()->json([
					'status' => 'error',
					'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
					'error' => $e,
					'response' => []
				], 422);
			}
			$mensagem_nasajon = json_decode($insert_nasajon[0]->mensagem, true);
			if($mensagem_nasajon['codigo'] !== 'OK'){
				return response()->json([
					'status' => 'error',
					'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
					'error' => [$mensagem_nasajon['mensagem']],
					'response' => []
				], 422);
			}
			$codigo_pedido = $PedidosVendaNasajonObj->numero;
			$PedidoPortalObj->pedido_gerado = $codigo_pedido;
			$PedidoPortalObj->save();
			$teste[] = $codigo_pedido;
		}

		$this->enviarEmailReposicaoFeira($produtos_inventario);
		$this->enviarEmailEstoqueZeradoParaFeira($produto_com_estoque_zerado);
	}

	public function enviarEmailReposicaoFeira($produtos_inventario){
		$body = "Srs, <br/><br/>Segue os produtos da Reposição para Feira:<br/><br/><br/>";

		foreach($produtos_inventario as $key => $produtos){
			foreach($produtos as $produto => $valores){
				$body .= 'Código: '.$valores['produto']->codigo_produto.' - Produto: '.$valores['descricao'].' - Quantidade Reposição: '.parserValor($valores['quantidade']).' - Estoque Feira: '.parserValor($valores['estoque_feira']).' - Estoque Estabelecimento Menos Reposição: '.parserValor($valores['estoque_estabelecimento']-$valores['quantidade']).' - Estoque Estabelecimento: '.parserValor($valores['estoque_estabelecimento']).'<br/>';
			}
		}

		$EmailObj = new EmailController();
            
		$variaveis = [
			'body' => $body,
		];
		
		$retorno = $EmailObj->sendEmailToken('00', 'reposicao_feira', [], $variaveis);

		try{
            if($retorno['status'] === 'error'){
                throw new \Exception;
            }
        } catch (\Exception $e) {
            Log::error($retorno);
        }
		
	}

	public function enviarEmailEstoqueZeradoParaFeira($produtos){
		$body = "Srs, <br/><br/>Segue os produtos que ficaram com o estoque zerado para feira:<br/><br/><br/>";

		foreach($produtos as $produto){
			$body .= 'Código: '.$produto->codigo_produto.' - Produto: '.$produto->descricao.'<br/>';
		}

		$EmailObj = new EmailController();
            
		$variaveis = [
			'body' => $body,
		];
		
		$retorno = $EmailObj->sendEmailToken('00', 'reposicao_feira_estoque_zerada', [], $variaveis);

		try{
            if($retorno['status'] === 'error'){
                throw new \Exception;
            }
        } catch (\Exception $e) {
            Log::error($retorno);
        }
	}
}
