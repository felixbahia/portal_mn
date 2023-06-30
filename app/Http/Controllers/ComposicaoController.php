<?php

namespace App\Http\Controllers;

use App\Composicao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ComposicaoEditarRequest;
use App\Http\Requests\ComposicaoAdicionarRequest;

class ComposicaoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Composicao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Composicao');

        return view('programs.composicao.index');
    }

    public function filtro(Request $request){
        $campo = $request->only('descricao');

        $ComposicaoObj = Composicao::select()->orderBy('descricao', 'ASC');

        if(!empty($campo['descricao'])){
            $ComposicaoObj->where('descricao', 'ilike', '%'.$campo['descricao'].'%');
        }

        $Composicaos = $ComposicaoObj->get();
        $saida = [];

        foreach($Composicaos as $composicao){
            $saida[] = [
                'id' => encrypt($composicao->id),
                'descricao' => $composicao->descricao
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
        return view('programs.composicao.modal.adicionar');
    }

    public function salvar(ComposicaoAdicionarRequest $request){
        $campo = $request->only('descricao');

        $ComposicaoObj = new Composicao;
        $ComposicaoObj->descricao = strtoupper($campo['descricao']);
        $ComposicaoObj->created_by = Auth::id();
        $ComposicaoObj->save();

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

        $ComposicaoObj = Composicao::find($id);
        if(is_null($ComposicaoObj)){
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
            'id' => encrypt($ComposicaoObj->id),
            'descricao' => $ComposicaoObj->descricao
        ];
        return view('programs.composicao.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(ComposicaoEditarRequest $request){
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

        $ComposicaoObj = Composicao::find($id);
        $ComposicaoObj->descricao = strtoupper($campo['descricao']);
        $ComposicaoObj->updated_by = Auth::id();
        $ComposicaoObj->save(); 

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

        $ComposicaoObj = Composicao::find($id);
        if(is_null($ComposicaoObj)){
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
            'id' => encrypt($ComposicaoObj->id),
            'descricao' => $ComposicaoObj->descricao
        ];
        return view('programs.composicao.modal.deletar')->with(['dados' => $dados]);
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

        $ComposicaoObj = Composicao::find($id);
        $ComposicaoObj->deleted_by = Auth::id();
        $ComposicaoObj->save();
        $ComposicaoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }
}

