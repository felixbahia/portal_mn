<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncotermCadastrarRequest;
use App\Http\Requests\IncotermEditarRequest;
use Illuminate\Http\Request;
use Auth;
use App\Incoterm;

class IncotermController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Incoterm") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Incoterm');

        return view('programs.incoterm.index');
    }

    public function modalAdicionar() {
        return view('programs.incoterm.modal.adicionar');
    }

    public function adicionarIncoterm(IncotermCadastrarRequest $request){
        $fields = $request->only('tipo');

        $IncotermObj = new Incoterm;
        $IncotermObj->tipo = strtoupper($fields['tipo']);
        $IncotermObj->created_by = Auth::id();
        $IncotermObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function filtro(Request $request){
        $fields = $request->only('tipo');

        $IncotermObj = Incoterm::select();;
        if(!empty($fields['tipo'])){
            $IncotermObj->where('tipo', 'ilike', $fields['tipo']);
        }
        $result = $IncotermObj->get();

        $dadosIncoterm = [];
        foreach($result as $value){
            $dadosIncoterm []= [
                'id' => encrypt($value->id),
                'tipo' => $value->tipo
            ];
        }

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => [],
            'response' => [
                'incoterm' => $dadosIncoterm
            ] 
        ];
        return response()->json($retorno, 200);
    }

    public function modalEditar(Request $request){
        $fields = $request->only('id');
        try{
            $id = decrypt($fields['id']);
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

        $IncotermObj = Incoterm::find($id);
        if(is_null($IncotermObj)){
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

        $Incoterm = [
            'id' => encrypt($IncotermObj->id),
            'tipo' => $IncotermObj->tipo,
        ];

        return view('programs.incoterm.modal.editar')->with(['incoterm' => $Incoterm]);
    }

    public function editarIncoterm(IncotermEditarRequest $request){
        $fields = $request->only('id', 'tipo');
        try{
            $id = decrypt($fields['id']);
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

        $IncotermObj = Incoterm::find($id);
        $IncotermObj->tipo = strtoupper($fields['tipo']);
        $IncotermObj->updated_by = Auth::id();
        $IncotermObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function modalDeletar(Request $request){
        $fields = $request->only('id');
        try{
            $id = decrypt($fields['id']);
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

        $IncotermObj = Incoterm::find($id);
        if(is_null($IncotermObj)){
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

        $incoterm = [
            'id' => encrypt($IncotermObj->id),
            'tipo' => $IncotermObj->tipo
        ];

        return view('programs.incoterm.modal.delete')->with(['incoterm' => $incoterm]);
    }

    public function deletarIncoterm(Request $request){
        $fields = $request->only('id');
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Transportadora não encontrada',
                'error' => [],
                'response' => []
            ]);
        }

        $IncotermObj = Incoterm::find($id);
        $IncotermObj->deleted_by = Auth::id();
        $IncotermObj->save();
        $IncotermObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }
}
