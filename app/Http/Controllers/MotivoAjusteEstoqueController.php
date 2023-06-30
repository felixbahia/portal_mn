<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\MotivoAjusteEstoque;

use App\Http\Requests\MotivoAjusteEstoqueRequest;

class MotivoAjusteEstoqueController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\MotivoAjusteEstoqueController") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\MotivoAjusteEstoqueController');

        return view('programs.motivo_ajuste_estoque.index');
    }

    public function modalAdicionar(){
        return view('programs.motivo_ajuste_estoque.modal.adicionar');
    }

    public function adicionar(MotivoAjusteEstoqueRequest $request){
        $motivo = $request->only('motivo')['motivo'];
        $motivoAjusteEstoqueObj = new MotivoAjusteEstoque;
        $motivoAjusteEstoqueObj->motivo = $motivo;
        $motivoAjusteEstoqueObj->created_by = Auth::id();
        $motivoAjusteEstoqueObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filter(Request $request){
        $motivo = $request->only('motivo')['motivo'];
        $query = MotivoAjusteEstoque::select();
        if(!empty($fields['motivo'])){
            $query->where('motivo', 'ilike', '%'.$motivo.'%');
        }
        $result = $query->get();
        $reponse = [];
        foreach ($result as $key => $motivo) {
            $reponse[] = [
                'id' => encrypt($motivo->id),
                'motivo' => $motivo->motivo
            ];
        }
        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $reponse
        ];
        return response()->json($retorno);
    }

    public function modalEditar(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $motivo_ajuste_estoque = MotivoAjusteEstoque::find($id);
        if(is_null($motivo_ajuste_estoque)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }

        $dados = [
            'id' => encrypt($motivo_ajuste_estoque->id),
            'motivo' => $motivo_ajuste_estoque->motivo
        ];

        return view('programs.motivo_ajuste_estoque.modal.editar')->with('dados', $dados);
    }

    public function editar(MotivoAjusteEstoqueRequest $request){
        $fields = $request->only('id','motivo');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $motivoAjusteEstoqueObj = MotivoAjusteEstoque::find($id);
        $motivoAjusteEstoqueObj->motivo = $fields['motivo'];
        $motivoAjusteEstoqueObj->updated_by = Auth::id();
        $motivoAjusteEstoqueObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function modalDeletar(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $motivo_ajuste_estoque = MotivoAjusteEstoque::find($id);
        if(is_null($motivo_ajuste_estoque)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }

        $dados = [
            'id' => encrypt($motivo_ajuste_estoque->id),
            'motivo' => $motivo_ajuste_estoque->motivo
        ];
        return view('programs.motivo_ajuste_estoque.modal.deletar')->with('dados', $dados);
    }

    public function deletar(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        
        
        $motivoAjusteEstoqueObj = MotivoAjusteEstoque::find($id);
        $motivoAjusteEstoqueObj->deleted_by = Auth::id();
        $motivoAjusteEstoqueObj->save();
        $motivoAjusteEstoqueObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }
}
