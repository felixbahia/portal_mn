<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CepEndereco;
use App\CepBairro;
use App\CepCidade;
use App\CepEstado;

class CepController extends Controller
{
    public function __construct() {
        $this->middleware(['auth:api']);
    }
    
    public $successStatus = 200;
    public $errorStatus = 403;

	public function busca(Request $request){
        header('Access-Control-Allow-Origin: *');

		$cep = $request->input("cep");
		$cep = (int) trim(str_replace("-","",$cep));
		$CepEndereco = CepEndereco::where('cep', $cep)->with(["cidadeBusca", "bairroBusca"])->first();
		if(is_null($CepEndereco)){
			$error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "CEP não encontrado.",
                        "user" => "CEP não encontrado"
                    ]
                ],
                "request" => $request->all(),
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
		}else {
			$CepEndereco = $CepEndereco->toArray();
			$cep = str_pad($CepEndereco["cep"], 8, "0", STR_PAD_LEFT);
			$return = [
				"cep" => mask(str_pad($cep, "0", STR_PAD_LEFT), "#####-###"),
				"logradouro" => $CepEndereco["logradouro"],
				"tipo_logradouro" => $CepEndereco["tipo_logradouro"],
				"complemento" => $CepEndereco["complemento"],
				"localidade" => $CepEndereco["local"],
				"bairro" => $CepEndereco["bairro_busca"]["bairro"],
				"cidade" => $CepEndereco["cidade_busca"]["cidade"],
				"uf" => $CepEndereco["cidade_busca"]["uf"]
			];
			foreach ($return as $key => $value) {
				if(is_null($value) || empty($value)){
					$return[$key] = "";
				}
			}
			$success = [
                'error' =>[
                    "error" => false,
                    "msg" => [
                        "dev" => "",
                        "user" => ""
                    ]
                ],
                'request' => $request->all(),
                'response' => $return
            ];
            return response()->json($success, $this->successStatus); 
		}
    }

    public function buscaCidadePorEstado(Request $request){
        header('Access-Control-Allow-Origin: *');

		$estado = strtoupper($request->input("estado"));
		$CepEstado = CepEstado::where('uf', $estado)->with(["cidades"])->first();
		if(is_null($CepEstado)){
			$error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Estado não encontrado!",
                        "user" => "Estado não encontrado!"
                    ]
                ],
                "request" => new \stdClass(),
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
		}else {
			$CepEstado = $CepEstado->toArray();
			$return = [];
			foreach ($CepEstado["cidades"] as $key => $value) {
				$return[] = [
					"id" => $value["id_cidade"],
					"cidade" => $value["cidade"]
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
				'request' => new \stdClass(),
				'response' => $return
			];
	        return response()->json($success, $this->successStatus); 
	    }
    }
}
