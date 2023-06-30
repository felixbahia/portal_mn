<?php

namespace App\Http\Controllers;

use App\Margem;
use App\Produto;
use App\ProdutoEspecificacao;
use App\Http\Controllers\ProdutoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\MargemRequest;
use App\AliquotaPreco;
use Auth;

class MargemController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Margem") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Margem');

        $origemObj = AliquotaPreco::with('origem_detalhe')
            ->orderBy('origem')
            ->get()
            ->unique('origem');

        foreach ($origemObj as $value){
            $origem[$value->origem] = $value->origem_detalhe->estado; 
        }

        $estabelecimentos = returnEmpresasNasajonView();

        return view('programs.margem.index')->with(["origem_array", $origem, 'estabelecimentos' => $estabelecimentos]);
    }

    public function formCadastro(Request $request){

    	$empresasView = returnEmpresasNasajonView();
        
        foreach($empresasView as $key => $value){
            $empresas[str_pad($key, 2, "0", STR_PAD_LEFT)] = $value;
        }

    	return view('programs.margem.cadastro')->with(['empresas' => $empresas]);
    }
    
    public function formEdit(Request $request){

    	$margem = Margem::findOrFail($request->id);
        
        $empresasPrologus = returnEmpresasNasajonView();

        foreach($empresasPrologus as $key => $value){
            $empresas[str_pad($key, 2, "0", STR_PAD_LEFT)] = $value;
        }
    	
        if (!empty($margem->produto)){
    		$descr_produto = ProdutoEspecificacao::select('descricao')->where('codigo_produto', $margem->produto)->pluck('descricao');
    	}

    	$margem->produto = empty($descr_produto)?null:$descr_produto[0];

    	return view('programs.margem.editar')->with(['margem' => $margem, 'empresas' => $empresas]);
    }

    public function formDelete(Request $request){
    	$margem = Margem::findOrFail($request->id);

    	$empresasPrologus = returnEmpresasNasajonView();

        foreach($empresasPrologus as $key => $value){
            $empresas[str_pad($key, 2, "0", STR_PAD_LEFT)] = $value;
        }

        if (!empty($margem->produto)){
            $descr_produto = ProdutoEspecificacao::select('descricao')->where('codigo_produto', $margem->produto)->pluck('descricao');
        }

        if (!is_null($margem->empresa)) {
			$margem->empresa = $empresas[$margem->empresa];
    	}

    	$margem->produto = empty($descr_produto)?null:$descr_produto[0];

    	return view('programs.margem.excluir')->with(['margem' => $margem]);

    }

    public function cadastro(MargemRequest $request){

    	$MargemObj = new Margem;

    	$cod_produto = ProdutoEspecificacao::select('codigo_produto')->where('descricao', $request->produto)->pluck('codigo_produto');

    	$MargemObj->empresa = str_pad($request->empresa, 2, '0', STR_PAD_LEFT);
		$MargemObj->grupo = $request->grupo;
		$MargemObj->produto = (isset($cod_produto[0]))? $cod_produto[0]:null;
		$MargemObj->marca = $request->marca;
		$MargemObj->linha = $request->linha;
		$MargemObj->margem_a = !empty($request->margem_a)?$request->margem_a:null;

		$MargemObj->created_by = Auth::user()->id;

		$MargemObj->save();

		return response()->json(['saved' => $MargemObj]);

    }

    public function editar(MargemRequest $request){

    	$MargemObj = Margem::findorFail($request->id);

    	$cod_produto = ProdutoEspecificacao::select('codigo_produto')->where('descricao', $request->produto)->pluck('codigo_produto');

    	$MargemObj->empresa = str_pad($request->empresa, 2, '0', STR_PAD_LEFT);
		$MargemObj->grupo = $request->grupo;
		$MargemObj->produto = (isset($cod_produto[0]))? $cod_produto[0]:null;
		$MargemObj->marca = $request->marca;
		$MargemObj->linha = $request->linha;
		$MargemObj->margem_a = !empty($request->margem_a)?$request->margem_a:null;

		$MargemObj->modified_by = Auth::user()->id;

		$MargemObj->save();

		return response()->json(['saved' => $MargemObj]);

    }

    public function excluir(Request $request){
    	$MargemObj = Margem::findorFail($request->id);
    	$MargemObj->delete();

		return response()->json(['deleted' => $MargemObj]);

    }

    public function filter(Request $request){

    	$fields = $request->only(['empresa', 'grupo', 'produto', 'nome', 'marca', 'linha']);
        $where = [];

        if(!is_null($fields['empresa'])) {
        	$where[] = ['empresa', '=', str_pad($fields['empresa'], 2, '0', STR_PAD_LEFT)];
        }
        if(!empty($fields['grupo'])){
            $where[] = ['LOWER(grupo)', 'like', strtolower($fields['grupo'])];
        }
        if(!empty($fields['nome'])){

	        $codprod = ProdutoEspecificacao::select('codigo_produto')->where('descricao', 'ilike', "%".strtolower($fields['nome'])."%")->get()->toArray();
        }
        if(!empty($fields['produto'])){
        	$codprod[]['codigo_produto'] = $fields['produto'];
        }
        if(!empty($codprod)){

        	foreach ($codprod as $value) {
        		$codprd[] = $value['codigo_produto']; 
        	}

            $where[] = ['produto', '= ANY', "('{".implode(",",$codprd)."}'::varchar[])"];
        	
        }
        if(!empty($fields['marca'])){
            $where[] = ['LOWER(marca)', 'ilike', strtolower($fields['marca'])];
        }
        if(!empty($fields['linha'])){
            $where[] = ['LOWER(linha)', 'ilike', strtolower($fields['linha'])];
        }

        $query = Margem::select('id', 'empresa', 'grupo', 'linha', 'produto', 'marca', 'margem_a');

        foreach ($where as $value) {

        	if (count($value) == 3 && $value[1] == 'like'){
        		$query->whereRaw("{$value[0]} {$value[1]} '%{$value[2]}%'");
        	}
        	else if (count($value) == 3 && $value[1] == '= ANY'){
        		$query->whereRaw("{$value[0]} {$value[1]}{$value[2]}");
        	}
	       	else if (count($value) == 3 && !in_array($value[1], ["like", '= ANY']) ) {
        		$query->whereRaw("{$value[0]} {$value[1]} '{$value[2]}'");
        	}
        	else{
        		$query->whereRaw("{$value[0]} '{$value[1]}'");
        	}
        }

        try {
        	// dd($query->toSql());
            $query = $query->get();
            $results_query = $query->toArray();
        } catch (\Exception $e){
            $return = [
                // "draw" => $fields["draw"],
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            ];
            return response()->json($return);
        }

        $n = 0;
        $results = [];

       	$empresas = returnEmpresasNasajonView();

        foreach($results_query as $result){

        	foreach ($result as $key => $value) {

        		if (empty($value) && !in_array($key, ['empresa', 'produto'])){
        			$result[$key] = ' ';         		
        		}
        		else if ($key == 'produto'){
					if(!empty($value)){
        				$nome = ProdutoEspecificacao::select('descricao')->where('codigo_produto', $value)->pluck('descricao')[0];
					}
					else{
						$result['produto'] = ' ';
					}

        			$result['nome'] = empty($nome)?' ':$nome;
        			unset($nome);
        		}
        		else if ($key == 'empresa'){
        			$result['empresa'] = isset($empresas[intval($value)])?$empresas[intval($value)]:" ";
        		}

        	}

            $results[$n]['id'] = $result['id'];
            $results[$n]['empresa'] = $result['empresa'];
            $results[$n]['grupo'] = $result['grupo'];
            $results[$n]['produto'] = $result['produto'];
            $results[$n]['nome'] = $result['nome'];
            $results[$n]['marca'] = $result['marca'];
            $results[$n]['linha'] = $result['linha'];
            $results[$n]['margem_a'] = parserValor($result['margem_a']);
            $n++;
        }

        $return = [
            // "draw" => $fields["draw"],
            "recordsTotal" => count($results),
            "recordsFiltered" => count($results),
            "data" => $results
        ];

        return response()->json($return);
    }

}
