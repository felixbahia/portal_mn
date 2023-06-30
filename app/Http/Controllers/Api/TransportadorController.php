<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Transportador;

class TransportadorController extends Controller
{
    public function __construct() {
        $this->middleware(['auth:api']);
    }

    public $successStatus = 200;
    public $errorStatus = 403;

	public function busca(Request $request){
        header('Access-Control-Allow-Origin: *');
        $filtro = $request->only(['codigo', 'via', 'nome', 'cidade', 'limit', 'offset', 'count']);
		if(
			!isset($filtro['limit']) || strlen(trim($filtro['limit'])) == 0 ||
			!isset($filtro['offset']) || strlen(trim($filtro['offset'])) == 0
		){
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Paramentro(s) informado(s) inválido(s)",
                        "user" => "Paramentro(s) informado(s) inválidos(s)"
                    ]
                ],
                "request" => $filtro,
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
		}

		$query = Transportador::select('CODTRAN', 'VIATRAN', 'NOME', 'CIDADE', 'ESTADO');
		$query_total = Transportador::select('*');
		if(isset($filtro['codigo']) && !empty($filtro['codigo'])){
			$query->whereRaw('LOWER(CODTRAN) LIKE \'%'.strtoupper($filtro['codigo']).'%\'');
			$query_total->whereRaw('LOWER(CODTRAN) LIKE \'%'.strtoupper($filtro['codigo']).'%\'');
		}
		if(isset($filtro['via']) && !empty($filtro['via'])){
			$query->whereRaw('LOWER(VIATRAN) LIKE \'%'.strtolower($filtro['via']).'%\'');
			$query_total->whereRaw('LOWER(VIATRAN) LIKE \'%'.strtolower($filtro['via']).'%\'');
		}
		if(isset($filtro['nome']) && !empty($filtro['nome'])){
			$query->whereRaw('LOWER(NOME) LIKE \'%'.strtolower($filtro['nome']).'%\'');
			$query_total->whereRaw('LOWER(NOME) LIKE \'%'.strtolower($filtro['nome']).'%\'');
		}
		if(isset($filtro['cidade']) && !empty($filtro['cidade'])){
			$query->whereRaw('LOWER(CIDADE) LIKE \'%'.utf8_decode(strtolower($filtro['cidade'])).'%\'');
			$query_total->whereRaw('LOWER(CIDADE) LIKE \'%'.utf8_decode(strtolower($filtro['cidade'])).'%\'');
		}
		$offset = intval($filtro["offset"]);
		$limit = intval($filtro["limit"]);
		
		$query->offset($offset);
		$query->limit($limit);
		
		$result = $query->get()->toArray();
		if($filtro['count'] === "true"){
			$total = $query_total->count();
		}
		$return = [];
		foreach ($result as $key => $value) {
			$return[$key]['id'] = trim(utf8_encode($value['CODTRAN']));
			$return[$key]['via'] = $this->parserViaTran(intval($value['VIATRAN']));
			$return[$key]['nome'] = trim(utf8_encode($value['NOME']));
			$return[$key]['cidade'] = trim(utf8_encode($value['CIDADE']));
			$return[$key]['estado'] = trim(utf8_encode($value['ESTADO']));
		}
		foreach ($filtro as $key => $value) {
			if(empty($value)){
				$filtro[$key] = '';			
			}
		}
		if(count($result) == 0){
			$error = [
				'error' =>[
					"error" => true,
					"msg" => [
						"dev" => "Sua pesquisa não retornou nenhuma transportadora",
						"user" => "Sua pesquisa não retornou nenhuma transportadora"
					]
				],
				'request' => $filtro,
				'response' => []
			];
			return response()->json($error, $this->errorStatus); 
		}

		if($filtro['count'] === "true"){
			$response = [
				"transportadores" => intval($total),
				"clientes" => $return
			];
		}else{
			$response = [
				"clientes" => $return
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
			'request' => $filtro,
			'response' => $response
		];
		return response()->json($success, $this->successStatus); 
    }

    private function parserViaTran($via_transporte){
        switch (intval($via_transporte)) {
            case 0:
                return "Nosso Carro";
            break;
            case 1:
                return "Rodoviário";
            break;
            case 2:
                return "Ferroviário";
            break;
            case 3:
                return "Aéreo";
            break;
            case 4:
                return "Fluvial";
            break;
            case 5:
                return "Maritímo";
            break;
            case 6:
                return "Retirada";
            break;
        }
    }

}
