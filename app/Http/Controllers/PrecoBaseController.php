<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\AliquotaPreco;
use App\CepEstado;

use App\Http\Controllers\ProdutoNovoController;

use App\Http\Requests\PrecoBaseCalculoRequest;

class PrecoBaseController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\PrecoBase") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\PrecoBase');

        $origemObj = AliquotaPreco::with('origem_detalhe')
        ->orderBy('origem')
        ->get()
        ->unique('origem');

        foreach ($origemObj as $value){
            $origem[$value->origem] = $value->origem_detalhe->estado; 
        }

        $estadosObj = CepEstado::all();

        foreach ($estadosObj as $value){
            $estados[$value->uf] = $value->estado; 
        }
        
        return view('programs.preco_base.index')->with(['origem' => $origem, 'estados' => $estados]);
    }

    public function calculo(PrecoBaseCalculoRequest $request){
        $fields = $request->only('preco_final', 'origem', 'estado', 'prazo_medio', 'frete', 'tipo_cliente', 'procedencia');

        $preco_final = floatval(str_replace(",",".", str_replace(".", "", $fields['preco_final'])));
        $prazo_medio = intval($fields['prazo_medio']);
        $valor_ipi = 0;
        $desconto = 0;
        $produtoNovoController = new ProdutoNovoController;

        $preco_base = $produtoNovoController->precoBase($preco_final, $fields['frete'], $fields['origem'], $fields['estado'], $valor_ipi, $prazo_medio, $fields['tipo_cliente'], $desconto, $fields['procedencia']);
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'preco_base' => parserValor($preco_base)
            ]
        ];
        return response()->json($response);
    }
}
