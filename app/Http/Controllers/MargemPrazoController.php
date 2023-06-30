<?php

namespace App\Http\Controllers;

use App\MargemPrazo;
use Illuminate\Http\Request;
use App\Http\Requests\MargemPrazoRequest;
use App\CepEstado;
use App\AliquotaPreco;
use Auth;

class MargemPrazoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\MargemPrazo") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\MargemPrazo');
        
        $origemObj = AliquotaPreco::with('origem_detalhe')
            ->orderBy('origem')
            ->get()
            ->unique('origem');

        $estabelecimentos = returnEmpresasNasajonView();
        
        foreach ($origemObj as $value){
            $origem_array[$value->origem] = $value->origem_detalhe->estado;
        }

        return view('programs.margem_prazo.index')->with('estabelecimentos', $estabelecimentos);
    }

    public function formCadastro(){
        $origem_array = returnEmpresasNasajonView();
        
        return view('programs.margem_prazo.cadastro')->with(['origem' => $origem_array]);
    }

    public function formEdit(Request $request){

    	$margem = MargemPrazo::findOrFail($request->id);
    	
        $origem_array = returnEmpresasNasajonView();

        $margem->fator_diario = empty($margem->fator_diario)? '0': str_replace(".", ",", $margem->fator_diario);

        $margem->preco_a = empty($margem->preco_a)? '0': str_replace(".", ",", $margem->preco_a);
        $margem->preco_b = empty($margem->preco_b)? '0': str_replace(".", ",", $margem->preco_b);
        $margem->preco_c = empty($margem->preco_c)? '0': str_replace(".", ",", $margem->preco_c); 

    	return view('programs.margem_prazo.editar')->with(['margem' => $margem, 'origem' => $origem_array]);

    }

    public function formDelete(Request $request){

        $margem = MargemPrazo::findOrFail($request->id);
        
        foreach(returnEmpresasNasajonView() as $key => $value){
            $empresasView[str_pad($key, 2, "0", STR_PAD_LEFT)] = $value;
        }

    	return view('programs.margem_prazo.excluir')->with(['margem' => $margem, 'empresas' => $empresasView]);

    }

    public function cadastro(MargemPrazoRequest $request){

    	$MargemPrazoObj = new MargemPrazo;

    	$MargemPrazoObj->estabelecimento = $request->estabelecimento;
		$MargemPrazoObj->fator_diario = !empty($request->fator_diario)?str_replace(",", ".", $request->fator_diario):null;

        $MargemPrazoObj->preco_a = !empty($request->preco_a)?str_replace(",", ".", $request->preco_a):null;
        $MargemPrazoObj->preco_b = !empty($request->preco_b)?str_replace(",", ".", $request->preco_b):null;
        $MargemPrazoObj->preco_c = !empty($request->preco_c)?str_replace(",", ".", $request->preco_c):null;

		$MargemPrazoObj->created_by = Auth::user()->id;

		$MargemPrazoObj->save();

		return response()->json(['saved' => $MargemPrazoObj]);

    }

    public function editar(MargemPrazoRequest $request){

    	$MargemPrazoObj = MargemPrazo::findOrFail($request->id);

    	$MargemPrazoObj->estabelecimento = $request->estabelecimento;
        $MargemPrazoObj->fator_diario = !empty($request->fator_diario)?str_replace(",", ".", $request->fator_diario):null;

        $MargemPrazoObj->preco_a = !empty($request->preco_a)?str_replace(",", ".", $request->preco_a):'0.00';
        $MargemPrazoObj->preco_b = !empty($request->preco_b)?str_replace(",", ".", $request->preco_b):'0.00';
        $MargemPrazoObj->preco_c = !empty($request->preco_c)?str_replace(",", ".", $request->preco_c):'0.00';

		$MargemPrazoObj->created_by = Auth::user()->id;

		$MargemPrazoObj->save();

		return response()->json(['saved' => $MargemPrazoObj]);

    }

    public function excluir(Request $request){

    	$MargemPrazoObj = MargemPrazo::findOrFail($request->id);
    	$MargemPrazoObj->delete();

    	return response()->json(['deleted' => $MargemPrazoObj]);
    }

    public function filter(Request $request){
    	
    	$fields = $request->only(['origem']);
        $where = [];

        $query = new MargemPrazo;
        
        if(!is_null($fields['origem'])) {
        	$query->where('estabelecimento', $fields['origem']);
        }
        
        try {
            // dd($results_query);
        } catch (\Exception $e){
            $return = [
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            ];
            return response()->json($return);
        }

        $query = $query->get();
        $results_query = $query->toArray();

        $results = [];

        $origemObj = AliquotaPreco::with('origem_detalhe')
            ->orderBy('origem')
            ->get()
            ->unique('origem');

        $estabelecimentos = returnEmpresasNasajonView();


       	$n = 0;

        foreach($results_query as $result){

            $results[$n]['id'] = $result['id'];
            $results[$n]['empresa'] = $estabelecimentos[$result['estabelecimento']];
            $results[$n]['fator_diario'] = $result['fator_diario'] > 0 ? str_replace('.', ',', $result['fator_diario']) : '';
   
            $results[$n]['preco_a'] = $result['preco_a'] > 0 ? str_replace('.', ',', $result['preco_a']) : '';
            $results[$n]['preco_b'] = $result['preco_b'] > 0 ? str_replace('.', ',', $result['preco_b']) : '';
            $results[$n]['preco_c'] = $result['preco_c'] > 0 ? str_replace('.', ',', $result['preco_c']) : '';
            $n++;
        }

        $return = [
            "recordsTotal" => count($results),
            "recordsFiltered" => count($results),
            "data" => $results
        ];

        return response()->json($return);


    }
}
