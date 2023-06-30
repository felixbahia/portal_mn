<?php

namespace App\Http\Controllers;

use App\MotivoDivergencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\MotivoDivergenciaEditarRequest;
use App\Http\Requests\MotivoDivergenciaAdicionarRequest;

class MotivoDivergenciaController extends Controller
{
   
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\MotivoDivergencia") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\MotivoDivergencia');

        return view('programs.motivo_divergencia.index');
    }

    public function filtro(Request $request){
        $campo = $request->only('descricao');

        $MotivoDivergenciaObj = MotivoDivergencia::select()->orderBy('descricao', 'ASC');

        if(!empty($campo['descricao'])){
            $MotivoDivergenciaObj->where('descricao', 'ilike', '%'.$campo['descricao'].'%');
        }

        $MotivoDivergencias = $MotivoDivergenciaObj->get();
        $saida = [];

        foreach($MotivoDivergencias as $MotivoDivergencia){
            $saida[] = [
                'id' => encrypt($MotivoDivergencia->id),
                'descricao' => $MotivoDivergencia->descricao
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
        return view('programs.motivo_divergencia.modal.adicionar');
    }

    public function salvar(MotivoDivergenciaAdicionarRequest $request){
        $campo = $request->only('descricao');

        $MotivoDivergenciaObj = new MotivoDivergencia;
        $MotivoDivergenciaObj->descricao = strtoupper($campo['descricao']);
        $MotivoDivergenciaObj->created_by = Auth::id();
        $MotivoDivergenciaObj->save();

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

        $MotivoDivergenciaObj = MotivoDivergencia::find($id);
        if(is_null($MotivoDivergenciaObj)){
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
            'id' => encrypt($MotivoDivergenciaObj->id),
            'descricao' => $MotivoDivergenciaObj->descricao
        ];
        return view('programs.motivo_divergencia.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(MotivoDivergenciaEditarRequest $request){
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

        $MotivoDivergenciaObj = MotivoDivergencia::find($id);
        $MotivoDivergenciaObj->descricao = strtoupper($campo['descricao']);
        $MotivoDivergenciaObj->updated_by = Auth::id();
        $MotivoDivergenciaObj->save(); 

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

        $MotivoDivergenciaObj = MotivoDivergencia::find($id);
        if(is_null($MotivoDivergenciaObj)){
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
            'id' => encrypt($MotivoDivergenciaObj->id),
            'descricao' => $MotivoDivergenciaObj->descricao
        ];
        return view('programs.MotivoDivergencia.modal.deletar')->with(['dados' => $dados]);
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

        $MotivoDivergenciaObj = MotivoDivergencia::find($id);
        $MotivoDivergenciaObj->deleted_by = Auth::id();
        $MotivoDivergenciaObj->save();
        $MotivoDivergenciaObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }
}

