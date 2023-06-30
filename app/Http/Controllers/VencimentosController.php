<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Vencimentos;

class VencimentosController extends Controller
{
    public function filtro(Request $request){

    	$query = Vencimentos::select('CODVCT', 
    		'DESCRICAO', 
    		'DIASVCT_1', 
    		'DIASVCT_2', 
    		'DIASVCT_3', 
    		'DIASVCT_4', 
    		'DIASVCT_5', 
    		'DIASVCT_6', 
    		'DIASVCT_7', 
    		'DIASVCT_8'
    	);

    	// dd($request->prazo_digitavel);

    	$prazos = explode(' ', $request->prazo_digitavel);

    	foreach ($prazos as $value) {
			$query->where('DESCRICAO', 'like', '%'.$value.'%');    		
    	}

    	$result = $query->get()->toArray();
    	
    	foreach ($result as $key => $value) {

    		foreach ($value as $k => $v){
    			$return[$key][$k] = empty($v)?'':utf8_encode($v);
    		}	
    	}

    	return response()->json($return);

    }

    public function modalPrazo(){
    	return view("programs.vencimentos.vencimentos_modal");
    }
    
    public function codvctParaDescricao(Request $request){

    	return response()->json(Vencimentos::findOrFail($request->codvct)->DESCRICAO);

    }
}
