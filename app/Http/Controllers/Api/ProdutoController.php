<?php

namespace App\Http\Controllers\Api;

use Hash;
use Auth;
use DateTime;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

use App\Http\Controllers\PrologosController;

use App\Http\Requests\Api\ProdutoBuscarPedidoRequest;

class ProdutoController extends Controller
{
    public $successStatus = 200;
    public $errorStatus = 403;

	public function busca(Request $request){
        header('Access-Control-Allow-Origin: *');
		$fields = $request->only(['cod_estabelecimento', 'cod_produto', 'descricao', 'limit', 'offset', 'count']);
		$PrologosController = new PrologosController();
		$empresas = $PrologosController->getEmpresas();
		if(
			!isset($fields['cod_estabelecimento']) ||
			strlen(trim($fields['cod_estabelecimento'])) == 0 ||
			!isset($fields['limit']) ||
			strlen(trim($fields['limit'])) == 0 ||
			!isset($fields['offset']) ||
			strlen(trim($fields['offset'])) == 0 ||
			!isset($fields['count']) ||
			strlen(trim($fields['count'])) == 0
		){
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Paramentro(s) informado(s) inválido(s)",
                        "user" => "Paramentro(s) informado(s) inválidos(s)"
                    ]
                ],
                "request" => $fields,
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
		}

		if(!isset($empresas[$fields['cod_estabelecimento']])){
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Estabelecimento não encontrado na base de dados",
                        "user" => "Estabelecimento inválido"
                    ]
                ],
                "request" => $fields,
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
		}

		$send = [
			"estabel"=>$fields['cod_estabelecimento'],
			'limit'=>$fields['limit'],
			'offset'=>$fields['offset'],
			'count'=>$fields['count']
		];
		if(isset($fields['cod_produto'])){
			$send['codigo']=$fields['cod_produto'];
		}
		if(isset($fields['descricao'])){
			$send['nome']= $fields['descricao'];
		}
		$request_send = new Request($send);
		$ProdutoControllerObj = new \App\Http\Controllers\ProdutoController();
		$produtos = $ProdutoControllerObj->filterAnalise($request_send);
        $produtos = (array) json_decode($produtos->content());

        if($fields['count'] === "true"){
        	if(count($produtos["data"]) > 0){
        		$total = $produtos["total"];
        	}else{
        		$total = 0;
        	}
        }
        $produtos = $produtos["data"];
        if($fields['count'] === "true"){
        	$response = ["total"=> intval($total),"produtos"=>[]];
        }else{
        	$response = ["produtos"=>[]];        	
        }

        $estabelecimento = [
        	'id' => $fields['cod_estabelecimento'],
        	'nome' => str_pad($fields['cod_estabelecimento'], 2, "0", STR_PAD_LEFT).' - '.$empresas[$fields['cod_estabelecimento']]
        ];
        unset($empresas);
        unset($send);
        unset($request_send);
        unset($ProdutoControllerObj);

        foreach ($produtos as $key => $produto) {
        	$produto = (array) $produto;
        	$compras = [];
        	$quantidade_compras_futura = 0;
        	foreach ($produto['quinzenas'] as $key => $value) {
        		$value = (array) $value;
        		if(floatval(str_replace(",", ".", str_replace(".", "", $value['quantidade']))) <= 0){
        			continue;
        		}
        		$quinzena = explode("_", $key);
        		$quinzena_text = $quinzena[3].' QUIN '.parserNameMonth($quinzena[2]).'/'.$quinzena[1];
        		$compras[] = [
					"quinzena" => $quinzena_text,
					"quantidade" => floatval(str_replace(",", ".", str_replace(".", "", $value['quantidade']))),
				];
				$quantidade_compras_futura += floatval(str_replace(",", ".", str_replace(".", "", $value['quantidade'])));
        	}
        	$response["produtos"][] = [
                "id" => $produto['codigo'],
                "codigo" => $produto['codigo'],
                "grupo" => $produto['grupo'],
                "marca" => $produto['marca'],
                "linha" => $produto['linha'],
                "medida" => $produto['unidade'],
        		"descricao" => $produto['descricao'],
        		"pronta_entrega" => floatval(str_replace(",", ".", str_replace(".", "", $produto['pronta_entrega']))),
        		"quantidade_compras_futura" => floatval($quantidade_compras_futura),
        		"compras" => $compras,
        		"estabelecimento" => $estabelecimento,
        	];
        }

		$success = [
            'error' =>[
                "error" => false,
                "msg" => [
                    "dev" => "",
                    "user" => ""
                ]
            ],
            'request' => $fields,
            "response" => $response
        ];

		return response()->json($success, $this->successStatus); 
	}

    public function buscaPedido(ProdutoBuscarPedidoRequest $request){

    }

}
