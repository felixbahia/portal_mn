<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Auth;
use DateTime;
use App\Cliente;
use App\PedidoPortal;
use App\PedidoItemPortal;
use App\PedidoVenda;
use App\CondicoesPagamentoWeb;
use App\Transportador;
use App\NaturezaDeOperacao;
use App\TipoOperacao;
use App\Produto;
use App\AliquotaPreco;

use App\Http\Requests\Api\PedidoBuscarRequest;
use App\Http\Requests\Api\PedidoSalvarRequest;
use App\Http\Requests\Api\ProdutoPedidoAdicionarRequest;
use App\Http\Requests\Api\ProdutoPedidoModificarRequest;
use App\Http\Requests\Api\ProdutoPedidoApagarRequest;
use App\Http\Requests\ListaDePrecosRequest;

use App\Http\Controllers\PedidoVendaController;
use App\Http\Controllers\ListagemDePrecosController;

use Illuminate\Support\Facades\Artisan;

class PedidoController extends Controller
{

    public $successStatus = 200;
    public $errorStatus = 403;

    public function filtros(){
		header('Access-Control-Allow-Origin: *');
		$filtros = [
			"status" => [
				[
					"id" => "1",
					"nome" => "Em Andamento"
				],
				[
					"id" => "2",
					"nome" => "Em Aprovação"
				],
				[
					"id" => "3",
					"nome" => "Separando"
				],
				[
					"id" => "4",
					"nome" => "Faturado Parcial"
				],
				[
					"id" => "5",
					"nome" => "Faturado Total"
				],
				[
					"id" => "6",
					"nome" => "Cancelado"
				],
				[
					"id" => "web-1",
					"nome" => "Web - Em Andamento"
				],
				[
					"id" => "web-2",
					"nome" => "Web - Em Aprovação"
				],
				[
					"id" => "web-3",
					"nome" => "Web - Reprovado"
				],
				[
					"id" => "web-4",
					"nome" => "Web - Digitação"
				]
			]
	    ];
		$response = [
			"error" => [
		    	"error" => false,
			    "msg" => [
			      "dev" => "",
			      "user" => ""
			    ]
			],
		    "request" => [],
		    "response" => $filtros
		];

		return response()->json($response, $this->successStatus);
    }

    public function buscar(PedidoBuscarRequest $request){
        ini_set('memory_limit', '1024M');
		header('Access-Control-Allow-Origin: *');

		$filtro = $request->only(['cliente', 'status', 'periodo_inicial', 'periodo_final', 'limit', 'offset', 'count']);
		$PedidoVendaControllerObj = new PedidoVendaController();
		$request_filtro = new Request([
			'codigo' => $filtro['cliente'] ?? '',
			'status' => $filtro['status'] ?? '',
			'data_inicio' => $filtro['periodo_inicial'],
			'data_fim' => $filtro['periodo_final'],
			'limit' => $filtro['limit'],
			'offset' => $filtro['offset'],
			'count' => $filtro['count'],
		]);
		$busca = $PedidoVendaControllerObj->filtro($request_filtro);

		$dados = [];
		foreach ($busca['dados'] as $key => $pedido) {
			$dados[] = [
				'id' => $pedido['pedido_number'],
				'estabelecimento' => $pedido['estabelecimento'],
				'cliente' => $pedido['cliente'],
				'data_pedido' => $pedido['emissao'],
				'valor' => $pedido['valor'],
				'status' => $pedido['status'],
				'condicoes' => $pedido['condicao_pagamento'],
				'observacao' => ''
			];
			unset($busca['dados'][$key]);
		}
		foreach ($filtro as $key => $value) {
			if(empty($value)){
				$filtro[$key] = '';
			}
		}
		if(count($dados) == 0){
			$error = [
				'error' =>[
					"error" => true,
					"msg" => [
						"dev" => "Sua pesquisa não retornou nenhum pedido",
						"user" => "Sua pesquisa não retornou nenhum pedido"
					]
				],
				'request' => $filtro,
				'response' => []
			];
			return response()->json($error, $this->errorStatus); 
		}
		$response_dados = [];
		if($filtro['count'] === 'true'){
			$response_dados['total'] = $busca['count'];
		}
		$response_dados['pedidos'] = $dados;

		$response = [
			"error" => [
		    	"error" => false,
			    "msg" => [
			      "dev" => "",
			      "user" => ""
			    ]
			],
		    "request" => $filtro,
		    "response" => $response_dados
		];
		return response()->json($response, $this->successStatus);
    }
	
	
	public function returnDadosAbertura(){
		header('Access-Control-Allow-Origin: *');
		$response_dados = [
			"estabelecimentos" => [
				"01" => "01 - A.BARROSO",
				"02" => "02 - C.BOTELHO",
				"03" => "03 - RONDONIA",
				"04" => "04 - TOCANTINS"
			],
			"tipo_vendas" => [
				"venda" => "NORMAL",
				"triangular" => "TRIANGULAR",
				"isento" => "CLIENTE ISENTO",
			],
			"tipo_fretes" => [
				"P" => "PAGO",
				"A" => "A PAGAR",
				"C" => "COBRADO",
				"T" => "TERCEIRO",
				"S" => "SEM FRETE",
			]
		];
		
		$response = [
			"error" => [
		    	"error" => false,
			    "msg" => [
			      "dev" => "",
			      "user" => ""
			    ]
			],
		    "request" => [],
		    "response" => $response_dados
		];
		return response()->json($response, $this->successStatus);
	}

	public function recuperaUltimosDados(Request $request){
        $fields = $request->only(['codigo_cliente']);
        $ClienteObj = Cliente::where("CODCAD", $fields['codigo_cliente'])->first();
        if(is_null($ClienteObj)){
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Cliente não encontrado!",
                        "user" => "Cliente não encontrado!"
                    ]
                ],
                "request" => $fields,
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus);
        }
        $ultimoPedido = PedidoPortal::where('cod_cliente', $fields['codigo_cliente'])->orderBy('id', 'desc')->first();
        $response = [];

        if (is_null($ultimoPedido)){
            $PedidoVendaObj = PedidoVenda::where('CODCAD', $fields['codigo_cliente'])->orderBy('DATA_PEDIDO', 'desc')->limit(1)->get()->toArray();
            if (!empty($PedidoVendaObj)){
            	$PedidoVendaObj = $PedidoVendaObj[0];
            	$response['nome_contato'] = "";
                $response['email_conato'] = "";
                $response['transportadora_tipo_frete'] = utf8_encode($PedidoVendaObj['TIPO_FRETE']);
                $TransportadorObj = Transportador::find(utf8_encode($PedidoVendaObj['CODTRAN']));
                $response['transportadora_codigo'] = $TransportadorObj->CODTRAN;
                $response['transportadora_descricao'] = $TransportadorObj->NOME;
                $CondicoesPagamentoWebObj = CondicoesPagamentoWeb::where('id_web', $PedidoVendaObj['CODVCT'])->get();
                $CondicoesPagamentoWebObj = $CondicoesPagamentoWebObj[0];
	            $response['condicao_pagamento_codigo'] = $CondicoesPagamentoWebObj->id;
	            $response['condicao_pagamento_descricao'] = $CondicoesPagamentoWebObj->descricao;
            } else {
            	$response['nome_contato'] = "";
                $response['email_conato'] = "";
                $response['transportadora_tipo_frete'] = "";
                $response['transportadora_codigo'] = "";
                $response['transportadora_descricao'] = "";
	            $response['condicao_pagamento_codigo'] = "";
	            $response['condicao_pagamento_descricao'] = "";
            }
        }
        else{
            $ultimoPedido->toArray();
            $response['nome_contato'] = isset($ultimoPedido['nome_comprador']) ? $ultimoPedido['nome_comprador'] : '';
            $response['email_conato'] = isset($ultimoPedido['email_comprador']) ? $ultimoPedido['email_comprador'] : '';
            $response['transportadora_tipo_frete'] = isset($ultimoPedido['tipo_frete']) ? $ultimoPedido['tipo_frete'] : '';
            $TransportadorObj = Transportador::find($ultimoPedido['transportadora']);
            if(!is_null($TransportadorObj)){
	            $response['transportadora_codigo'] = $TransportadorObj->CODTRAN;
	            $response['transportadora_descricao'] = $TransportadorObj->NOME;
            }else{
	            $response['transportadora_codigo'] = "";
	            $response['transportadora_descricao'] = "";
            }
            $response['condicao_pagamento_codigo'] = isset($ultimoPedido['condicao_pagamento']) ? $ultimoPedido['condicao_pagamento'] : '';
            $response['condicao_pagamento_descricao'] = isset($ultimoPedido['condicao_pagamento']) ? (CondicoesPagamentoWeb::find($ultimoPedido['condicao_pagamento'])->descricao ?? ''): '';
        }
		$response = [
			"error" => [
		    	"error" => false,
			    "msg" => [
			      "dev" => "",
			      "user" => ""
			    ]
			],
		    "request" => [],
		    "response" => $response
		];
		return response()->json($response, $this->successStatus);
	}
	
	public function salvarPedido(PedidoSalvarRequest $request){
		$fields = $request->all();
        $error = [
            "error" => [
                "error" => true,
                "msg" => [
                    "dev" => "Cadastro temporariamente desativado! Utilize o portal.tecidosmn.com.br para o cadastro!",
                    "user" => "Cadastro temporariamente desativado! Utilize o portal.tecidosmn.com.br para o cadastro!"
                ]
            ],
            "request" => $fields,
            "response" => new \stdClass()
        ];
        return response()->json($error, $this->errorStatus); 
		if(!empty($fields['id'])){
			$PedidoPortalObj = PedidoPortal::find($fields['id']);
			if(is_null($PedidoPortalObj)){
				$response = [
					"error" => [
				    	"error" => true,
					    "msg" => [
					      "dev" => "Código de pedido informado não encontrado!",
					      "user" => "Código de pedido informado não encontrado!"
					    ]
					],
				    "request" => $fields,
				    "response" => []
				];
				return response()->json($response, $this->errorStatus);
			}
            $PedidoPortalObj->updated_by = Auth::user()->id;
		}else{
			$PedidoPortalObj = new PedidoPortal();
			$PedidoPortalObj->created_by = Auth::user()->id;
            $PedidoPortalObj->usuario = Auth::user()->id;
		}

        $PedidoPortalObj->data_pedido = date('Y-m-d');

		if (!in_array($PedidoPortalObj->status_pedido, [1,5,7]) && !empty($PedidoPortalObj->status_pedido)){

			$response = [
				"error" => [
			    	"error" => true,
				    "msg" => [
				      "dev" => "O pedido já saiu da digitação portanto não pode ser editado.",
				      "user" => "O pedido já saiu da digitação portanto não pode ser editado."
				    ]
				],
			    "request" => $fields,
			    "response" => []
			];

			return response()->json($response, $this->errorStatus);

		}

		$data_previsao_entrega = !empty($fields['data_previsao_entrega']) ? Carbon::createFromFormat('d/m/Y', $fields['data_previsao_entrega']) : null;


        $ClienteObj = Cliente::find($fields['codigo_cliente']);
        $NaturezaDeOperacaoObj = NaturezaDeOperacao::where('estabelecimento', $fields['estabelecimento'])->where('estado_destino', $ClienteObj->ESTADO)->get()->first();

        $errors = [];
        $natop = '';

        if (!empty($NaturezaDeOperacaoObj)){
            if ($fields['tipo_venda'] == 'venda'){
                $natop = $NaturezaDeOperacaoObj->nat_op_pj;
            } else if ($fields['tipo_venda'] == 'isento'){
                if ($clienteObj->IEST == 'ISENTO'){
                    $natop = $NaturezaDeOperacaoObj->nat_op_pf;
                } else{
                    $errors[] = [
                    	"O cadastro deste cliente não permite venda isenta. Favor verificar."
                    ];
                }

            } else if ($fields['tipo_venda'] == 'triangular'){
                $natop = $NaturezaDeOperacaoObj->nat_op_venda_conta_ordem;
            }
        } else{
            $errors[] = [
            	"O cadastro deste cliente está incompleto e não possui estado. Favor verificar."
            ];
        }

        if(!empty($errors)){

			$error = [
				'error' =>[
					"error" => true,
					"msg" => [
						"dev" => $errors[0],
						"user" => $errors[0]
					]
				],
				'request' => $fields,
				'response' => []
			];
			return response()->json($error, $this->errorStatus); 
        }

        if ($fields['transportadora_tipo_frete'] != 'C'){
            $fields['valor_frete'] = 0;
        }
        if ($fields['transportadora_redespacho_tipo_frete'] != 'C'){
            $fields['valor_frete_redespacho'] = 0;
        }
        $fields['valor_desconto'] = floatval($fields['valor_desconto']);

		$PedidoPortalObj->status_pedido = 1;
		$PedidoPortalObj->tipo_venda = $fields['tipo_venda'];
        $PedidoPortalObj->cod_cliente = $ClienteObj->CODCAD;
		$PedidoPortalObj->nome_comprador = $fields['nome_contato'];
		$PedidoPortalObj->email_comprador = $fields['email_contato'];
		$PedidoPortalObj->estabelecimento = $fields['estabelecimento'];
		$PedidoPortalObj->pedido_futuro = ($fields['pedido_futuro'] === 'true' ? true : false);
		$PedidoPortalObj->condicao_pagamento = $fields['condicao_pagamento'];
		$PedidoPortalObj->data_previsao_entrega = $data_previsao_entrega;
		$PedidoPortalObj->observacao = $fields['observacao'];
		$PedidoPortalObj->transportadora = $fields['transportadora'];
		$PedidoPortalObj->tipo_frete = $fields['transportadora_tipo_frete'];
		$PedidoPortalObj->valor_frete = $fields['valor_frete'];
		$PedidoPortalObj->transportadora_redespacho = $fields['transportadora_redespacho'];
		$PedidoPortalObj->tipo_frete_redespacho = $fields['transportadora_redespacho_tipo_frete'];
		$PedidoPortalObj->valor_frete_redespacho = $fields['valor_frete_redespacho'];
		$PedidoPortalObj->valor_desconto = $fields['valor_desconto'];
		$PedidoPortalObj->codigo_operacao = $natop;
		$PedidoPortalObj->conta_e_ordem = (!empty($fields['codigo_cliente_conta_e_ordem']) ? true : false);
		$PedidoPortalObj->cod_cliente_conta_e_ordem = $fields['codigo_cliente_conta_e_ordem'];

        if(!empty($fields['completo']) && $fields['completo'] === 'true' ){



        	if (PedidoItemPortal::where('pedido', $PedidoPortalObj->id)->count() == 0){
				$error = [
					'error' =>[
						"error" => true,
						"msg" => [
							"dev" => "Não há produtos neste pedido",
							"user" => "Não há produtos neste pedido"
						]
					],
					'request' => $fields,
					'response' => []
				];
				return response()->json($error, $this->errorStatus); 
        	}

			$PedidoPortalObj->motivo_rejeicao = null;
	        $PedidoPortalObj->status_pedido = 2;
	        $PedidoPortalObj->observacao = '-'.$PedidoPortalObj->observacao;
	        
	        Artisan::queue('pedido:validacao', ['pedido' => $PedidoPortalObj->id]);

        }

        if($PedidoPortalObj->save()){
			$response = [
				"error" => [
			    	"error" => false,
				    "msg" => [
				      "dev" => "",
				      "user" => ""
				    ]
				],
			    "request" => $fields,
			    "response" => [
			    	'pedido' => $PedidoPortalObj->id
			    ]
			];
			return response()->json($response, $this->successStatus);
        }else{
			$error = [
				'error' =>[
					"error" => true,
					"msg" => [
						"dev" => "Ocorreu uma instabilidade!",
						"user" => "Ocorreu uma instabilidade!"
					]
				],
				'request' => $fields,
				'response' => []
			];
			return response()->json($error, $this->errorStatus); 
        }
	}

	public function adicionarProduto(ProdutoPedidoAdicionarRequest $request, $pedido){

		$fields = $request->only(['codigo_produto', 'quantidade', 'preco_unitario']);
		$pedidoObj = PedidoPortal::find($pedido);
		$produtoObj = Produto::find($fields['codigo_produto']);
		$pedidoItemPortalObj = new PedidoItemPortal();

        switch ($pedidoObj->estabelecimento) {
            case '3':
                $origem = "RO";
                break;
            case '4':
                $origem = "TO";
                break;
            default:
                $origem = "SP";
                break;
        }

        if (in_array($produtoObj->PROCEDENCIA, [1,2,6,8])){
            $internacional = true;
        }
        else{
            $internacional = false;
        }

        $aliquotaObj = AliquotaPreco::where("origem", $origem)
            ->where('estado', $pedidoObj->cliente->ESTADO)
            ->where('internacional', $internacional)->first();

        if ($pedidoObj->tipo_frete == 'A') {
            $frete = 'cif';
        }
        else{
            $frete = 'fob';
        }

        switch ($pedidoObj->cliente->estado_detalhe->regiao) {
            case 'NO':
            case 'N':
                $frete_aliquota = 0.1;
                break;
            case 'CO':
                $frete_aliquota = 0.08;
                break;
            case 'S':
            case 'SE':
                $frete_aliquota = 0.04;
                break;

        }

        if($frete == 'cif'){
            $valor_frete = (str_replace(",", ".", $request->preco_unitario) * str_replace(",", ".", $request->quantidade)) * $frete_aliquota;
        }
        else{
            $valor_frete = 0;
        }

        if ($pedidoObj->condicao_pagamento_detalhes['media'] < 15){
            $coluna = 'prazo_vista';
        }
        else if($pedidoObj->condicao_pagamento_detalhes['media'] >= 15 && $pedidoObj->condicao_pagamento_detalhes['media'] < 30){
            $coluna = 'prazo_15';
        }
        else if($pedidoObj->condicao_pagamento_detalhes['media'] >= 30 && $pedidoObj->condicao_pagamento_detalhes['media'] < 45){
            $coluna = 'prazo_30';
        }
        else if($pedidoObj->condicao_pagamento_detalhes['media'] >= 45 && $pedidoObj->condicao_pagamento_detalhes['media'] < 60){
            $coluna = 'prazo_45';
        }
        else if($pedidoObj->condicao_pagamento_detalhes['media'] >= 60){
            $coluna = 'prazo_60';
        }

        $items = new ListaDePrecosRequest([
            'origem' => $origem,
            'aliquota' => $aliquotaObj->aliquota,
            'produto' => $fields['codigo_produto'],
            'moeda' => 'real',
            'coluna' => $coluna,
            'frete' => $frete,
            'regiao' => $pedidoObj->cliente->estado_detalhe->regiao
        ]);

        $precoObj = new ListagemDePrecosController;
        $precos = json_decode($precoObj->filter($items, true, false)->content(), TRUE);


        if (Auth::user()->tipo_usuario_id != 16){

            if( (float) str_replace(",", ".", $precos[0]['coluna_b']) > $fields['preco_unitario']){
                $coluna_preco = 'a';
            }
            else if( (float) str_replace(",", ".", $precos[0]['coluna_b']) <= $fields['preco_unitario'] && (float) str_replace(",", ".", $precos[0]['coluna_c']) > $fields['preco_unitario']){
                $coluna_preco = 'b';

            }
            else if ( (float) str_replace(",", ".", $precos[0]['coluna_c']) <= $fields['preco_unitario']){
                $coluna_preco = 'c';
            }

        }
        else{
            $coluna_preco = 'a';
        }

		$total = ceil($fields['preco_unitario'] * $fields['quantidade'] * 100) /100;

		$pedidoItemPortalObj->pedido = $pedido;
		$pedidoItemPortalObj->cod_produto = $fields['codigo_produto'];
		$pedidoItemPortalObj->quantidade = $fields['quantidade'];
		$pedidoItemPortalObj->preco_unitario = $fields['preco_unitario'];
		$pedidoItemPortalObj->usuario = Auth::id();
		$pedidoItemPortalObj->created_by = Auth::id();
		$pedidoItemPortalObj->valor_icms = $total * ($aliquotaObj->aliquota/100);
        $pedidoItemPortalObj->base_calculo_icms = $total * (1 - ($aliquotaObj->aliquota/100));
        $pedidoItemPortalObj->valor_ipi = 0;
        $pedidoItemPortalObj->aliquota_icms = $aliquotaObj->aliquota;
        $pedidoItemPortalObj->aliquota_ipi = 0;
        $pedidoItemPortalObj->valor_frete = $valor_frete;
        $pedidoItemPortalObj->valor_total = $total;
        $pedidoItemPortalObj->coluna = $coluna_preco;
        $pedidoItemPortalObj->comissao = $pedidoObj->usuario_detalhes["comissao_" . $coluna_preco];

        if($pedidoItemPortalObj->save()){

			$response = [
				"error" => [
			    	"error" => false,
				    "msg" => [
				      "dev" => "",
				      "user" => ""
				    ]
				],
			    "request" => $fields,
			    "response" => [
					'id' => $pedidoItemPortalObj->id,
					'produto_grupo' => $pedidoItemPortalObj->info_produto->GRUPO,
					'produto_codigo' => $pedidoItemPortalObj->cod_produto,
					'produto_descricao' => $pedidoItemPortalObj->info_produto->DESCR,
					'produto_marca' => $pedidoItemPortalObj->info_produto->MARCA,
					'produto_linha' => $pedidoItemPortalObj->info_produto->LINHA,
					'quantidade' => $pedidoItemPortalObj->quantidade,
					'preco_unitario' => $pedidoItemPortalObj->preco_unitario,
					'total' => $pedidoItemPortalObj->valor_total
			    ]
			];
	
			return response()->json($response, $this->successStatus);
        	
        }
        else{

			$error = [
				'error' =>[
					"error" => true,
					"msg" => [
						"dev" => "Ocorreu uma instabilidade!",
						"user" => "Ocorreu uma instabilidade!"
					]
				],
				'request' => $fields,
				'response' => []
			];

			return response()->json($error, $this->errorStatus); 

        }

	}

	public function editarProduto(ProdutoPedidoModificarRequest $request, $pedido){

		$fields = $request->only(['id', 'codigo_produto', 'quantidade', 'preco_unitario']);
		$pedidoObj = PedidoPortal::find($pedido);
		$produtoObj = Produto::find($fields['codigo_produto']);
		$pedidoItemPortalObj = PedidoItemPortal::find($fields['id']);

        switch ($pedidoObj->estabelecimento) {
            case '3':
                $origem = "RO";
                break;
            case '4':
                $origem = "TO";
                break;
            default:
                $origem = "SP";
                break;
        }

        if (in_array($produtoObj->PROCEDENCIA, [1,2,6,8])){
            $internacional = true;
        }
        else{
            $internacional = false;
        }

        $aliquotaObj = AliquotaPreco::where("origem", $origem)
            ->where('estado', $pedidoObj->cliente->ESTADO)
            ->where('internacional', $internacional)->first();

        if ($pedidoObj->tipo_frete == 'A') {
            $frete = 'cif';
        }
        else{
            $frete = 'fob';
        }

        switch ($pedidoObj->cliente->estado_detalhe->regiao) {
            case 'NO':
            case 'N':
                $frete_aliquota = 0.1;
                break;
            case 'CO':
                $frete_aliquota = 0.08;
                break;
            case 'S':
            case 'SE':
                $frete_aliquota = 0.04;
                break;

        }

        if($frete == 'cif'){
            $valor_frete = (str_replace(",", ".", $request->preco_unitario) * str_replace(",", ".", $request->quantidade)) * $frete_aliquota;
        }
        else{
            $valor_frete = 0;
        }

        if ($pedidoObj->condicao_pagamento_detalhes['media'] < 15){
            $coluna = 'prazo_vista';
        }
        else if($pedidoObj->condicao_pagamento_detalhes['media'] >= 15 && $pedidoObj->condicao_pagamento_detalhes['media'] < 30){
            $coluna = 'prazo_15';
        }
        else if($pedidoObj->condicao_pagamento_detalhes['media'] >= 30 && $pedidoObj->condicao_pagamento_detalhes['media'] < 45){
            $coluna = 'prazo_30';
        }
        else if($pedidoObj->condicao_pagamento_detalhes['media'] >= 45 && $pedidoObj->condicao_pagamento_detalhes['media'] < 60){
            $coluna = 'prazo_45';
        }
        else if($pedidoObj->condicao_pagamento_detalhes['media'] >= 60){
            $coluna = 'prazo_60';
        }

        $items = new ListaDePrecosRequest([
            'origem' => $origem,
            'aliquota' => $aliquotaObj->aliquota,
            'produto' => $fields['codigo_produto'],
            'moeda' => 'real',
            'coluna' => $coluna,
            'frete' => $frete,
            'regiao' => $pedidoObj->cliente->estado_detalhe->regiao
        ]);

        $precoObj = new ListagemDePrecosController;
        $precos = json_decode($precoObj->filter($items, true, false)->content(), TRUE);


        if (Auth::user()->tipo_usuario_id != 16){

            if( (float) str_replace(",", ".", $precos[0]['coluna_b']) > $fields['preco_unitario']){
                $coluna_preco = 'a';
            }
            else if( (float) str_replace(",", ".", $precos[0]['coluna_b']) <= $fields['preco_unitario'] && (float) str_replace(",", ".", $precos[0]['coluna_c']) > $fields['preco_unitario']){
                $coluna_preco = 'b';

            }
            else if ( (float) str_replace(",", ".", $precos[0]['coluna_c']) <= $fields['preco_unitario']){
                $coluna_preco = 'c';
            }

        }
        else{
            $coluna_preco = 'a';
        }

		$total = ceil($fields['preco_unitario'] * $fields['quantidade'] * 100) /100;

		$pedidoItemPortalObj->pedido = $pedido;
		$pedidoItemPortalObj->cod_produto = $fields['codigo_produto'];
		$pedidoItemPortalObj->quantidade = $fields['quantidade'];
		$pedidoItemPortalObj->preco_unitario = $fields['preco_unitario'];
		$pedidoItemPortalObj->updated_by = Auth::id();
		$pedidoItemPortalObj->valor_icms = $total * ($aliquotaObj->aliquota/100);
        $pedidoItemPortalObj->base_calculo_icms = $total * (1 - ($aliquotaObj->aliquota/100));
        $pedidoItemPortalObj->valor_ipi = 0;
        $pedidoItemPortalObj->aliquota_icms = $aliquotaObj->aliquota;
        $pedidoItemPortalObj->aliquota_ipi = 0;
        $pedidoItemPortalObj->valor_frete = $valor_frete;
        $pedidoItemPortalObj->valor_total = $total;
        $pedidoItemPortalObj->coluna = $coluna_preco;
        $pedidoItemPortalObj->comissao = $pedidoObj->usuario_detalhes["comissao_" . $coluna_preco];

        if($pedidoItemPortalObj->save()){

			$response = [
				"error" => [
			    	"error" => false,
				    "msg" => [
				      "dev" => "",
				      "user" => ""
				    ]
				],
			    "request" => $fields,
			    "response" => [
					'id' => $pedidoItemPortalObj->id,
					'produto_grupo' => $pedidoItemPortalObj->info_produto->GRUPO,
					'produto_codigo' => $pedidoItemPortalObj->cod_produto,
					'produto_descricao' => $pedidoItemPortalObj->info_produto->DESCR,
					'produto_marca' => $pedidoItemPortalObj->info_produto->MARCA,
					'produto_linha' => $pedidoItemPortalObj->info_produto->LINHA,
					'quantidade' => $pedidoItemPortalObj->quantidade,
					'preco_unitario' => $pedidoItemPortalObj->preco_unitario,
					'total' => $pedidoItemPortalObj->valor_total
			    ]
			];
	
			return response()->json($response, $this->successStatus);
        	
        }
        else{

			$error = [
				'error' =>[
					"error" => true,
					"msg" => [
						"dev" => "Ocorreu uma instabilidade!",
						"user" => "Ocorreu uma instabilidade!"
					]
				],
				'request' => $fields,
				'response' => []
			];

			return response()->json($error, $this->errorStatus); 

        }

	}

	public function excluirProduto(ProdutoPedidoApagarRequest $request, $pedido){

		$fields = $request->only(['id']);
		
		$pedidoItemPortalObj = PedidoItemPortal::find($fields['id']); 

		$pedidoItemPortalObj->delete();

		$response = [
			"error" => [
		    	"error" => false,
			    "msg" => [
			      "dev" => "",
			      "user" => ""
			    ]
			],
		    "request" => $fields,
		    "response" => []
		];

		return response()->json($response, $this->successStatus);

	}

}
