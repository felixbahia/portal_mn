<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParametrosAprovacaoRequest;
use Illuminate\Http\Request;
use App\TipoUsuario;
use App\ParametrosAprovacao;
use Auth;

class ParametrosAprovacaoController extends Controller
{
    //
    public function indexMulti(Request $request){
	    if(Auth::user()->hasPermissionTo("programas App\ParametrosAprovacao") === false){
	            return abort(403);
	        }

        $request->session()->flash('model', 'App\ParametrosAprovacao');

        $tipo_usuarios = TipoUsuario::all();

        $count_tipos = count($tipo_usuarios);

        $count_tipos;

        return view('programs.parametros_aprovacao.index_multi', ['tipo_usuarios' => $tipo_usuarios, 'count_tipos' => $count_tipos]);

    }

    public function index(Request $request){
	    if(Auth::user()->hasPermissionTo("programas App\ParametrosAprovacao") === false){
	            return abort(403);
	        }

        $request->session()->flash('model', 'App\ParametrosAprovacao');

        $tipo_usuarios = TipoUsuario::all();

        return view('programs.parametros_aprovacao.index')->with('tipo_usuarios', $tipo_usuarios);

    }

    public function filterMulti(Request $request){

    	$fields = $request->only(['estabelecimento']);

    	$parametros = ParametrosAprovacao::distinct('parametros_aprovacao.estabelecimento')
    	->leftJoin('parametros_aprovacao AS desconto', 'desconto.estabelecimento', 'parametros_aprovacao.estabelecimento')
    	->leftJoin('parametros_aprovacao AS prazo', 'prazo.estabelecimento', 'parametros_aprovacao.estabelecimento');
    	
    	if (isset($fields['estabelecimento']) && !is_null($fields['estabelecimento'])){
			$parametros->where('parametros_aprovacao.estabelecimento', $fields['estabelecimento']);
    	}

    	$return = $parametros->get()->toArray();

        return response()->json($return);
    }

    public function filter(Request $request){
    	$fields = $request->only(['estabelecimento', 'tipo_usuario_id']);
		$parametros = ParametrosAprovacao::whereNotNull('id');

		// dd($fields);

    	if (isset($fields['estabelecimento']) && !is_null($fields['estabelecimento'])){
			$parametros->where('estabelecimento', $fields['estabelecimento']);
        }

        if (isset($fields['tipo_usuario_id']) && !is_null($fields['tipo_usuario_id'])){
            $parametros->where('tipo_usuario_id', $fields['tipo_usuario_id']);
            // dd($parametros->toSql());
        }


    	$return = $parametros->get();
		$estabelecimentos = returnEmpresasNasajonView();
    	
		// dd($return);

        $result = [];

    	foreach ($return as $key => $value) {
            $tipoUsuario = $value->tipoUsuario()->first()->toArray();
            $result[$key] = $value;
            $result[$key]['tipo_usuario_id'] = $tipoUsuario['nome'];
    		$result[$key]['estabelecimento'] = $estabelecimentos[(int)$value['estabelecimento']];
    		$result[$key]['percentual_desconto'] = empty($value->percentual_desconto)?'':parserValor($value->percentual_desconto);
    		$result[$key]['prazo_adicional'] = empty($value->prazo_adicional)?'':parserValor($value->prazo_adicional);

    	}

        return response()->json($result); 
    }

    public function formCreate(){
    	$estabelecimentos = array();

    	foreach (returnEmpresasNasajonView() as $key => $value) {
    		$estabelecimentos[str_pad($key, 2, '0', STR_PAD_LEFT)] = $value;
    	}

    	$tipoUsuariosObj = TipoUsuario::all();

    	$tipo_usuarios = array();
    	
    	foreach ($tipoUsuariosObj as $value) {
    		$tipo_usuarios[$value->id] = $value->nome;
    		
    	}

        return view('programs.parametros_aprovacao.cadastro', ['estabelecimentos' => $estabelecimentos,'tipo_usuarios' => $tipo_usuarios]);
    }
	
	public function formEdit(Request $request){

		// dd($request);

		$parametro = ParametrosAprovacao::findOrFail($request->id);
    	$estabelecimentos = array();

    	foreach (returnEmpresasNasajonView() as $key => $value) {
    		$estabelecimentos[str_pad($key, 2, '0', STR_PAD_LEFT)] = $value;
    	}

    	$tipoUsuariosObj = TipoUsuario::all();

    	$tipo_usuarios = array();
    	
    	foreach ($tipoUsuariosObj as $value) {
    		$tipo_usuarios[$value->id] = $value->nome;
    		
    	}

        return view('programs.parametros_aprovacao.editar', ['estabelecimentos' => $estabelecimentos,'tipo_usuarios' => $tipo_usuarios, 'parametro' => $parametro]);
    }
	
	public function formDelete(Request $request){

		$fields = $request->only('id');
		$estabelecimentos = returnEmpresasNasajonView();

        $ParametrosAprovacaoObj = ParametrosAprovacao::findOrFail($fields['id']);

        $return = $ParametrosAprovacaoObj->toArray();
        $return['tipo_usuario_nome'] = $ParametrosAprovacaoObj->tipoUsuario()->first()->toArray()['nome'];
        $return['estabelecimento'] = $estabelecimentos[(int)$return['estabelecimento']];

        // dd($return);

        return view('programs.parametros_aprovacao.excluir')->with('parametro', $return);


    }

    public function create(ParametrosAprovacaoRequest $request){

    	$fields = $request->only(['estabelecimento', 'tipo_usuario_id', 'percentual_desconto', 'prazo_adicional']);

    	$ParametrosAprovacaoObj = new ParametrosAprovacao();

    	// dd($fields);

    	$ParametrosAprovacaoObj->estabelecimento = $fields['estabelecimento'];
    	$ParametrosAprovacaoObj->tipo_usuario_id = $fields['tipo_usuario_id'];
    	$ParametrosAprovacaoObj->percentual_desconto = $fields['percentual_desconto'];
    	$ParametrosAprovacaoObj->prazo_adicional = $fields['prazo_adicional'];

    	$ParametrosAprovacaoObj->created_by = Auth::user()->id;

    	$ParametrosAprovacaoObj->save();

    	return response()->json($ParametrosAprovacaoObj);

    }
	
	public function edit(Request $request){

    	$fields = $request->only(['id', 'estabelecimento', 'tipo_usuario_id', 'percentual_desconto', 'prazo_adicional']);

    	$ParametrosAprovacaoObj = ParametrosAprovacao::findOrFail($fields['id']);

    	// dd($fields);

    	$ParametrosAprovacaoObj->estabelecimento = $fields['estabelecimento'];
    	$ParametrosAprovacaoObj->tipo_usuario_id = $fields['tipo_usuario_id'];
    	$ParametrosAprovacaoObj->percentual_desconto = $fields['percentual_desconto'];
    	$ParametrosAprovacaoObj->prazo_adicional = $fields['prazo_adicional'];

    	$ParametrosAprovacaoObj->created_by = Auth::user()->id;

    	$ParametrosAprovacaoObj->save();

    	return response()->json($ParametrosAprovacaoObj);


    }

	public function delete(Request $request){

        $fields = $request->only('id');
        $ParametrosAprovacaoObj = ParametrosAprovacao::findOrFail($fields['id']);
        
        $ParametrosAprovacaoObj->delete();
        
        return response()->json(['deleted' => $ParametrosAprovacaoObj]);

    }
}
