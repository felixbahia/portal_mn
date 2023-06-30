<?php

namespace App\Http\Controllers;

use App\Familia;
use App\Http\Requests\FamiliaAdicionarRequest;
use App\Http\Requests\FamiliaEditarRequest;
use Illuminate\Http\Request;
use Auth;

class FamiliaController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Familia") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Familia');

        return view('programs.familia.index');
    }

    public function filtro(Request $request){
        $campo = $request->only('descricao');

        $FamiliaObj = Familia::select()->orderBy('descricao', 'ASC');

        if(!empty($campo['descricao'])){
            $FamiliaObj->where('descricao', 'ilike', '%'.$campo['descricao'].'%');
        }

        $Familias = $FamiliaObj->get();
        $saida = [];

        foreach($Familias as $familia){
            $saida[] = [
                'id' => encrypt($familia->id),
                'descricao' => $familia->descricao
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
        return view('programs.familia.modal.adicionar');
    }

    public function salvarFamilia(FamiliaAdicionarRequest $request){
        $campo = $request->only('descricao');

        $FamiliaObj = new Familia;
        $FamiliaObj->descricao = strtoupper($campo['descricao']);
        $FamiliaObj->created_by = Auth::id();
        $FamiliaObj->save();

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

        $FamiliaObj = Familia::find($id);
        if(is_null($FamiliaObj)){
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
            'id' => encrypt($FamiliaObj->id),
            'descricao' => $FamiliaObj->descricao
        ];
        return view('programs.familia.modal.editar')->with(['dados' => $dados]);
    }

    public function editarFamilia(FamiliaEditarRequest $request){
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

        $FamiliaObj = Familia::find($id);
        $FamiliaObj->descricao = strtoupper($campo['descricao']);
        $FamiliaObj->updated_by = Auth::id();
        $FamiliaObj->save(); 

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

        $FamiliaObj = Familia::find($id);
        if(is_null($FamiliaObj)){
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
            'id' => encrypt($FamiliaObj->id),
            'descricao' => $FamiliaObj->descricao
        ];
        return view('programs.familia.modal.deletar')->with(['dados' => $dados]);
    }

    public function deletarFamilia(Request $request){
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

        $FamiliaObj = Familia::find($id);
        $FamiliaObj->deleted_by = Auth::id();
        $FamiliaObj->save();
        $FamiliaObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }
}
