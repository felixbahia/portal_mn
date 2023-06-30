<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Vendedor;
use App\VendedorWw;
use App\VendedorNasajon;
use App\User;
use App\VendedoresCadastroNasajon;

class VendedorController extends Controller
{

	public function indexDialog(){
		return view('programs.vendedor.dialog');
	}

	public function filter(Request $request){

		$return = ['status' => 'success', 'data' => []];
		$filter = $request->only('nome', 'codigo');

		$vendedoresQuery = VendedorNasajon::query();

		if(!empty($filter["nome"])){
			$vendedoresQuery->where('nome', 'ilike',  '%' . $filter["nome"] . '%');
		}
		if(!empty($filter["codigo"])){
			$vendedoresQuery->where("codigo", 'ilike',  '%' . $filter["codigo"] . '%');
		}

		$vendedores = $vendedoresQuery->get();

		foreach ($vendedores as $vendedor){
			$return['data'][] = [
				'codigo' => $vendedor->codigo,
				'nome' => $vendedor->nome
			];
		}

		return response()->json($return);
	}

	public function codigoParaNome(Request $request){
		$codigo = $request->input("codigo");
		$return = ["status"=>"success", "data"=>[]];
		$busca = VendedorNasajon::where('codigo', $codigo)->first();

		if(is_null($busca)){
			$return["status"] = "error";
			return response()->json($return);
		}

		$return["data"] = $busca->nome;

		return response()->json($return);
	}
}
