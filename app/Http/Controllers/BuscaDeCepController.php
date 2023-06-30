<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CepEndereco;
use App\CepBairro;
use App\CepCidade;
use App\CepEstado;

class BuscaDeCepController extends Controller{

	/**
	 * Busca de CEP
	 * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
	 */
	public function busca(Request $request){
		$cep = $request->input("cep");
		$cep = (int) trim(str_replace("-","",$cep));
		$CepEndereco = CepEndereco::where('cep', $cep)->with(["cidadeBusca", "bairroBusca"])->first();
		if(is_null($CepEndereco)){
			return response()->json(["status" => "error", "message" => "CEP não encontrado!", "dados" => []]);
		}
		$CepEndereco = $CepEndereco->toArray();
		$return = [
			"cep" => mask(str_pad($CepEndereco["cep"], "0", STR_PAD_LEFT), "#####-###"),
			"logradouro" => $CepEndereco["logradouro"],
			"tipo_logradouro" => $CepEndereco["tipo_logradouro"],
			"complemento" => $CepEndereco["complemento"],
			"local" => $CepEndereco["local"],
			"bairro" => $CepEndereco["bairro_busca"]["bairro"],
			"cidade" => $CepEndereco["cidade_busca"]["cidade"],
			"uf" => $CepEndereco["cidade_busca"]["uf"],
			"cod_ibge" => $CepEndereco["cidade_busca"]["cod_ibge"]
		];
		foreach ($return as $key => $value) {
			if(is_null($value) || empty($value)){
				$return[$key] = "";
			}
		}
		return response()->json(["status" => "success", "message" => "", "dados" => $return]);
	}

	public function buscaCidadePorEstado(Request $request){
		$estado = $request->input("estado");
		$CepEstado = CepEstado::where('uf', $estado)->with(["cidades"])->first();
		if(is_null($CepEstado)){
			return response()->json(["status" => "error", "message" => "Estado não encontrado!", "dados" => []]);
		}
		$CepEstado = $CepEstado->toArray();
		$return = [];
		foreach ($CepEstado["cidades"] as $key => $value) {
			$return[$value["id_cidade"]] = $value["cidade"];
		}
		return response()->json(["status" => "success", "message" => "", "dados" => $return]);
	}
}
