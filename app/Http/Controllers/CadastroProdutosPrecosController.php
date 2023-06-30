<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\CadastroProdutosPrecos;

class CadastroProdutosPrecosController extends Controller
{

    public function index(Request $request){

        if(Auth::user()->hasPermissionTo("programas App\CadastroProdutosPrecos") === false){
            return abort(403);
        }

        $estadosObj = CepEstado::all()->toArray();

        $request->session()->flash('model', 'App\CadastroProdutosPrecos');

        return view('programs.cadastro_produtos_precos.index');
    }

    public function filter(Request $request){

    	$fields = $request->only(['codigo', 'marca', 'linha', 'grupo', 'subgrupo', 'composicao', 'gramatura', 'largura', 'unidade', 'preco']);

    	$query = new CadastroProdutosPrecos;

    	foreach ($fields as $key => $value) {

    		if(!empty($value) && !is_null($value)){
    			$query->where($key, 'like', "%" . $value . "%");
    		}
    		
    	}

    	$resultado = $query->get();

    }
    public function criarModal(){

    	return view('programs.cadastro_produtos_preco.criar');

    }
    
    public function editarModal(Request $request){
    	
    	return view('programs.cadastro_produtos_precos.editar');
    }

    public function criar(ProdutosPrecosRequest $request){

    	$fields = $request->only(['codigo', 'marca', 'linha', 'grupo', 'subgrupo', 'composicao', 'gramatura', 'largura', 'unidade', 'preco']);

    	$cadastroProdutosPrecos = new CadastroProdutosPrecos;

    	$cadastroProdutosPrecos->codigo = $fields['codigo'];
    	$cadastroProdutosPrecos->marca = $fields['marca'];
    	$cadastroProdutosPrecos->linha = $fields['linha'];
    	$cadastroProdutosPrecos->grupo = $fields['grupo'];
    	$cadastroProdutosPrecos->subgrupo = $fields['subgrupo'];
    	$cadastroProdutosPrecos->composicao = $fields['composicao'];
    	$cadastroProdutosPrecos->gramatura = $fields['gramatura'];
    	$cadastroProdutosPrecos->largura = $fields['largura'];
    	$cadastroProdutosPrecos->unidade = $fields['unidade'];
    	$cadastroProdutosPrecos->preco = $fields['preco'];
		$cadastroProdutosPrecos->created_by = Auth::id();

    	$cadastroProdutosPrecos->save();

    	$return = [
    		'id' => $cadastroProdutosPrecos->id,
    		'codigo' => $cadastroProdutosPrecos->codigo,
    		'marca' => $cadastroProdutosPrecos->marca,
    		'linha' => $cadastroProdutosPrecos->linha,
    		'grupo' => $cadastroProdutosPrecos->grupo,
    		'subgrupo' => $cadastroProdutosPrecos->subgrupo,
    		'composicao'  => $cadastroProdutosPrecos->composicao,
    		'gramatura'  => $cadastroProdutosPrecos->gramatura,
    		'largura' => $cadastroProdutosPrecos->largura,
    		'unidade' => $cadastroProdutosPrecos->unidade,
    		'preco' => $cadastroProdutosPrecos->preco
    	];

    	return response()->json($return);    	

    }
    
    public function editar(ProdutosPrecosRequest $request){

    	$fields = $request->only(['id', 'codigo', 'marca', 'linha', 'grupo', 'subgrupo', 'composicao', 'gramatura', 'largura', 'unidade', 'preco']);

    	$cadastroProdutosPrecos = CadastroProdutosPrecos::find($fields['id']);

    	if(isset($fields['codigo']) && !empty($fields['codigo'])){
	    	$cadastroProdutosPrecos->codigo = $fields['codigo'];
    	}
    	
    	if(isset($fields['marca']) && !empty($fields['marca'])){
	    	$cadastroProdutosPrecos->marca = $fields['marca'];
	    }

    	if(isset($fields['linha']) && !empty($fields['linha'])){
    		$cadastroProdutosPrecos->linha = $fields['linha'];
	    }

    	if(isset($fields['grupo']) && !empty($fields['grupo'])){
    		$cadastroProdutosPrecos->grupo = $fields['grupo'];
	    }

    	if(isset($fields['subgrupo']) && !empty($fields['subgrupo'])){
	    	$cadastroProdutosPrecos->subgrupo = $fields['subgrupo'];
	    }

    	if(isset($fields['composicao']) && !empty($fields['composicao'])){
    		$cadastroProdutosPrecos->composicao = $fields['composicao'];
	    }

    	if(isset($fields['gramatura']) && !empty($fields['gramatura'])){
    		$cadastroProdutosPrecos->gramatura = $fields['gramatura'];
	    }

    	if(isset($fields['largura']) && !empty($fields['largura'])){
    		$cadastroProdutosPrecos->largura = $fields['largura'];
	    }

    	if(isset($fields['unidade']) && !empty($fields['unidade'])){
    		$cadastroProdutosPrecos->unidade = $fields['unidade'];
	    }

    	if(isset($fields['preco']) && !empty($fields['preco'])){
 		   	$cadastroProdutosPrecos->preco = $fields['preco'];
	    }

		$cadastroProdutosPrecos->updated_by = Auth::id();

    	$cadastroProdutosPrecos->save();

    	$return = [
    		'id' => $cadastroProdutosPrecos->id,
    		'codigo' => $cadastroProdutosPrecos->codigo,
    		'marca' => $cadastroProdutosPrecos->marca,
    		'linha' => $cadastroProdutosPrecos->linha,
    		'grupo' => $cadastroProdutosPrecos->grupo,
    		'subgrupo' => $cadastroProdutosPrecos->subgrupo,
    		'composicao'  => $cadastroProdutosPrecos->composicao,
    		'gramatura'  => $cadastroProdutosPrecos->gramatura,
    		'largura' => $cadastroProdutosPrecos->largura,
    		'unidade' => $cadastroProdutosPrecos->unidade,
    		'preco' => $cadastroProdutosPrecos->preco
    	];

    	return response()->json($return);

    }

    public function deletar(Request $request){

    	$cadastroProdutosPrecos = CadastroProdutosPrecos::find($fields['id']);

		$cadastroProdutosPrecos->updated_by = Auth::id();
		
		$cadastroProdutosPrecos->save();

    	$cadastroProdutosPrecos->delete();

    	$return['id'] = $cadastroProdutosPrecos->id;

    	return response()->json($return);

    }

}
