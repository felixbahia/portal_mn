<?php

namespace App\Http\Controllers;

use App\Construcao;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ConstrucaoEditarRequest;
use App\Http\Requests\ConstrucaoAdicionarRequest;

class ConstrucaoController extends Controller
{    public function index(Request $request){
    if(Auth::user()->hasPermissionTo("programas App\Construcao") === false){
        return abort(403);
    }
    $request->session()->flash('model', 'App\Construcao');

    return view('programs.construcao.index');
}

public function filtro(Request $request){
    $campo = $request->only('descricao');

    $ConstrucaoObj = Construcao::select()->orderBy('descricao', 'ASC');

    if(!empty($campo['descricao'])){
        $ConstrucaoObj->where('descricao', 'ilike', '%'.$campo['descricao'].'%');
    }

    $Construcaos = $ConstrucaoObj->get();
    $saida = [];

    foreach($Construcaos as $construcao){
        $saida[] = [
            'id' => encrypt($construcao->id),
            'descricao' => $construcao->descricao
        ];
    }

    $response = [
        "status" => 'success',
        "message" => '',
        "error" => [],
        "response" => $saida
    ];
    return response()->json($response, 200);
}

public function modalAdicionar() {
    return view('programs.construcao.modal.adicionar');
}

public function salvar(ConstrucaoAdicionarRequest $request){
    $campo = $request->only('descricao');

    $ConstrucaoObj = new Construcao;
    $ConstrucaoObj->descricao = strtoupper($campo['descricao']);
    $ConstrucaoObj->created_by = Auth::id();
    $ConstrucaoObj->save();

    $response = [
        "status" => 'success',
        "message" => '',
        "error" => [],
        "response" => []
    ];
    return response()->json($response, 200);
}

public function modalEditar(Request $request){
    $campo = $request->only('id');

    try{
        $id = decrypt($campo['id']);
    }catch(\Exception $e){
        return response()->json([
            'status' => 'error',
            'message' => '',
            'error' => [
                'id' => 'Dados não encontrados'
            ],
            'response' => []
        ], 422);
    }

    $ConstrucaoObj = Construcao::find($id);
    if(is_null($ConstrucaoObj)){
        return response()->json([
            'status' => 'error',
            'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
            'error' => [
                $e,
                'id' => $id
            ],
            'response' => []
        ], 422);
    }

    $dados = [
        'id' => encrypt($ConstrucaoObj->id),
        'descricao' => $ConstrucaoObj->descricao
    ];
    return view('programs.construcao.modal.editar')->with(['dados' => $dados]);
}

public function editar(ConstrucaoEditarRequest $request){
    $campo = $request->only('id', 'descricao');

    try{
        $id = decrypt($campo['id']);
    }catch(\Exception $e){
        return response()->json([
            'status' => 'error',
            'message' => '',
            'error' => [
                'id' => 'Dados não encontrados'
            ],
            'response' => []
        ], 422);
    }

    $ConstrucaoObj = Construcao::find($id);
    $ConstrucaoObj->descricao = strtoupper($campo['descricao']);
    $ConstrucaoObj->updated_by = Auth::id();
    $ConstrucaoObj->save(); 

    $response = [
        "status" => 'success',
        "message" => '',
        "error" => [],
        "response" => []
    ];
    return response()->json($response, 200);
}

public function modalDeletar(Request $request){
    $campo = $request->only('id');

    try{
        $id = decrypt($campo['id']);
    }catch(\Exception $e){
        return response()->json([
            'status' => 'error',
            'message' => '',
            'error' => [
                'id' => 'Dados não encontrados'
            ],
            'response' => []
        ], 422);
    }

    $ConstrucaoObj = Construcao::find($id);
    if(is_null($ConstrucaoObj)){
        return response()->json([
            'status' => 'error',
            'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
            'error' => [
                $e,
                'id' => $id
            ],
            'response' => []
        ], 422);
    }

    $dados = [
        'id' => encrypt($ConstrucaoObj->id),
        'descricao' => $ConstrucaoObj->descricao
    ];
    return view('programs.construcao.modal.deletar')->with(['dados' => $dados]);
}

public function deletar(Request $request){
    $campo = $request->only('id');

    try{
        $id = decrypt($campo['id']);
    }catch(\Exception $e){
        return response()->json([
            'status' => 'error',
            'message' => '',
            'error' => [
                'id' => 'Dados não encontrados'
            ],
            'response' => []
        ], 422);
    }

    $ConstrucaoObj = Construcao::find($id);
    $ConstrucaoObj->deleted_by = Auth::id();
    $ConstrucaoObj->save();
    $ConstrucaoObj->delete();

    $response = [
        "status" => 'success',
        "message" => '',
        "error" => [],
        "response" => []
    ];
    return response()->json($response, 200);
}
}