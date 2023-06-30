<?php

namespace App\Http\Controllers;

use App\Sazonalidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\SazonalidadeEditarRequest;
use App\Http\Requests\SazonalidadeAdicionarRequest;

class SazonalidadeController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Sazonalidade") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Sazonalidade');

        return view('programs.sazonalidade.index');
    }

    public function filtro(Request $request){
        $campo = $request->only('descricao');

        $SazonalidadeObj = Sazonalidade::select()->orderBy('descricao', 'ASC');

        if(!empty($campo['descricao'])){
            $SazonalidadeObj->where('descricao', 'ilike', '%'.$campo['descricao'].'%');
        }

        $Sazonalidades = $SazonalidadeObj->get();
        $saida = [];

        foreach($Sazonalidades as $sazonalidade){
            $saida[] = [
                'id' => encrypt($sazonalidade->id),
                'descricao' => $sazonalidade->descricao
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
        return view('programs.sazonalidade.modal.adicionar');
    }

    public function salvar(SazonalidadeAdicionarRequest $request){
        $campo = $request->only('descricao');

        $SazonalidadeObj = new Sazonalidade;
        $SazonalidadeObj->descricao = strtoupper($campo['descricao']);
        $SazonalidadeObj->created_by = Auth::id();
        $SazonalidadeObj->save();

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

        $SazonalidadeObj = Sazonalidade::find($id);
        if(is_null($SazonalidadeObj)){
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
            'id' => encrypt($SazonalidadeObj->id),
            'descricao' => $SazonalidadeObj->descricao
        ];
        return view('programs.sazonalidade.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(SazonalidadeEditarRequest $request){
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

        $SazonalidadeObj = Sazonalidade::find($id);
        $SazonalidadeObj->descricao = strtoupper($campo['descricao']);
        $SazonalidadeObj->updated_by = Auth::id();
        $SazonalidadeObj->save(); 

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

        $SazonalidadeObj = Sazonalidade::find($id);
        if(is_null($SazonalidadeObj)){
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
            'id' => encrypt($SazonalidadeObj->id),
            'descricao' => $SazonalidadeObj->descricao
        ];
        return view('programs.sazonalidade.modal.deletar')->with(['dados' => $dados]);
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

        $SazonalidadeObj = Sazonalidade::find($id);
        $SazonalidadeObj->deleted_by = Auth::id();
        $SazonalidadeObj->save();
        $SazonalidadeObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }
}