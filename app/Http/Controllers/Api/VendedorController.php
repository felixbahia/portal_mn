<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Vendedor;
use App\VendedorWw;

class VendedorController extends Controller
{
    public function __construct() {
        $this->middleware(['auth:api']);
    }
    
    public $successStatus = 200;
    public $errorStatus = 403;

	public function busca(Request $request){
        header('Access-Control-Allow-Origin: *');

		$filter = $request->only(["codigo", "nome"]);
		$where = [];
		if(!empty($filter["nome"])){
			$where[] = "LOWER(NOME) like '%{$filter["nome"]}%'";
		}
		if(!empty($filter["codigo"])){
			$where[] = "LOWER(CODVND) like '%{$filter["codigo"]}%'";
		}
		$busca = Vendedor::select("CODVND as codigo", "NOME as nome");
		foreach ($where as $key => $value) {
			$busca->whereRaw($value);
		}
		$retorno_busca = $busca->get()->toArray();
		$return = [];
		foreach ($retorno_busca as $key => $value) {
			$value = (array) $value;
			$return[] = [
				"id" => $value["codigo"],
				"nome" => trim(utf8_decode($value["nome"]))
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
