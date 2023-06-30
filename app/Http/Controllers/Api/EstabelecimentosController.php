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

class EstabelecimentosController extends Controller
{
    public function __construct() {
        $this->middleware(['auth:api']);
    }
    public $successStatus = 200;
    public $errorStatus = 403;

	public function getDados(){
        header('Access-Control-Allow-Origin: *');
        if (Auth::check()) {
			$PrologosController = new PrologosController();
			$empresas = $PrologosController->getEmpresas();
			$resposta_empresa = [];
			foreach ($empresas as $key => $value) {
				$resposta_empresa[] = [
					"id" => $key,
                    "nome" => str_pad($key, 2, "0", STR_PAD_LEFT)." - ".$value,
                    "name" => str_pad($key, 2, "0", STR_PAD_LEFT)." - ".$value,
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
                "response" => $resposta_empresa
            ];
            return response()->json($success, $this->successStatus); 
		} else {
            $error = [
                "error" => [
                    "error" => true,
                    "msg" => [
                        "dev" => "Ocorreu um erro no manuseio do token",
                        "user" => "Credencial expirada, favor efetue o login novamente."
                    ]
                ],
                "request" => new \stdClass(),
                "response" => new \stdClass()
            ];
            return response()->json($error, $this->errorStatus); 
        }
	}
}
