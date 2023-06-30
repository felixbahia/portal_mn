<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\NaturezaDeOperacao;
use App\NaturezaDeOperacaoNasajon;
use App\CepEstado;
use App\TipoOperacao;

use App\Http\Requests\NaturezaOperacaoRequest;

use Auth;

class NaturezaDeOperacaoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\NaturezaDeOperacao") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\NaturezaDeOperacao');

        $estabelecimentos = returnEmpresasNasajonView();
        $estadosObj = CepEstado::all();

        foreach ($estadosObj as $value) {
        	$estados[$value->uf] = $value->estado;
        }
        
        return view('programs.natureza_operacao.index')->with(['estabelecimentos' => $estabelecimentos, 'estados' => $estados]);
    }

    public function filter(Request $request){
        
        $estabelecimentos = returnEmpresasNasajonView();
    	
    	$fields = $request->only('estabelecimento', 'estado_destino');

    	$naturezaDeOperacaoObj = NaturezaDeOperacaoNasajon::with('estado_detalhe');

    	if(isset($fields['estabelecimento'])){
    		$naturezaDeOperacaoObj->where('estabelecimento', $fields['estabelecimento']);
    	}

    	if(isset($fields['estado_destino'])){
    		$naturezaDeOperacaoObj->where('estado_destino', $fields['estado_destino']);
    	}

    	$result = $naturezaDeOperacaoObj->get();

    	foreach ($result as $value) {

    		$response[] = [
    			'id' => $value->id,
    			'estabelecimento' => $estabelecimentos[$value->estabelecimento],
    			'estado_destino' => $value->estado_detalhe['estado'],
    			'cfop_pj' => $value->nat_op_pj,
    			'cfop_pf'=> $value->nat_op_pf,
    		];
		}
		$return = [
			'status' 	=> 'success',
			'message' 	=> '',
			'error' 	=> [],
			'response' 	=> $response
		];
    	return response()->json($return, 200);
    }

    public function adicionarNaturezaModal(){

        $estadosObj = CepEstado::all();
        foreach ($estadosObj as $value) {
        	$estados[$value->uf] = $value->estado;
        }

        return view('programs.natureza_operacao.adicionar')->with(['estados' => $estados]);

    }


    public function editarNaturezaModal(Request $request){

    	$naturezaDeOperacaoObj = NaturezaDeOperacaoNasajon::with('estado_detalhe')->find($request->id);

        $estadosObj = CepEstado::all();
        foreach ($estadosObj as $value) {
        	$estados[$value->uf] = $value->estado;
        }

        return view('programs.natureza_operacao.editar')->with(['estados' => $estados, 'natureza_operacao' => $naturezaDeOperacaoObj]);

    }

    public function adicionarNatureza(NaturezaOperacaoRequest $request){

    	$naturezaDeOperacaoObj = NaturezaDeOperacaoNasajon::create([
    		'estabelecimento' => $request->estabelecimento, 
    		'estado_destino' => $request->estado_destino, 
    		'nat_op_pj' => $request->nat_op_pj, 
    		'nat_op_pf' => $request->nat_op_pf
    	]);

    	return response()->json($naturezaDeOperacaoObj, 200);
    }

	public function editarNatureza(NaturezaOperacaoRequest $request){

		$naturezaDeOperacaoObj = NaturezaDeOperacaoNasajon::find($request->id);
		
		$naturezaDeOperacaoObj->fill([
    		'estabelecimento' => $request->estabelecimento, 
    		'estado_destino' => $request->estado_destino, 
    		'nat_op_pj' => $request->nat_op_pj, 
    		'nat_op_pf' => $request->nat_op_pf
    	]);

    	$naturezaDeOperacaoObj->save();

    	return response()->json($naturezaDeOperacaoObj, 200);	
    }
}
