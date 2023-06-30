<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\MotivoClienteBlackList;

use App\Http\Requests\MotivoClienteBlackListRequest;

class MotivoClienteBlackListController extends Controller
{
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\MotivoClienteBlackList") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\MotivoClienteBlackList');

        return view('programs.motivo_cliente_black_list.index');
    }

    public function modalAdicionar(){
        return view('programs.motivo_cliente_black_list.modal.adicionar');
    }

    public function adicionar(MotivoClienteBlackListRequest $request){
        $fields = $request->only('motivo');

        $motivoClienteBlackListObj = new MotivoClienteBlackList;
        $motivoClienteBlackListObj->motivo = $fields['motivo'];
        $motivoClienteBlackListObj->created_by = Auth::id();
        $motivoClienteBlackListObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtro(Request $request){
        $fields = $request->only('motivo');
        $query = MotivoClienteBlackList::select('id', 'motivo');
        if(!empty($fields['motivo'])){
            $query->where('motivo', 'ilike', '%'.$fields['motivo'].'%');
        }
        $result = $query->get();
        $reponse = [];
        foreach ($result as $key => $motivo) {
            $reponse[] = [
                'id' => encrypt($motivo->id),
                'motivo' => $this->ajusteCampoTabela($motivo->motivo)
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

    private function ajusteCampoTabela($campo){
        $retorno = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='$campo'>$campo</div></div>";
        return $retorno;
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

        $motivoClienteBlackListObj = MotivoClienteBlackList::find($id);

        $dados = [
            'id' => encrypt($motivoClienteBlackListObj->id),
            'motivo' => $motivoClienteBlackListObj->motivo
        ];

        return view('programs.motivo_cliente_black_list.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(MotivoClienteBlackListRequest $request){
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

        $motivoClienteBlackListObj = MotivoClienteBlackList::find($id);
        $motivoClienteBlackListObj->motivo = $fields['motivo'];
        $motivoClienteBlackListObj->updated_by = Auth::id();
        $motivoClienteBlackListObj->save();

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

        $motivoClienteBlackListObj = MotivoClienteBlackList::find($id);

        $dados = [
            'id' => encrypt($motivoClienteBlackListObj->id),
            'motivo' => $motivoClienteBlackListObj->motivo
        ];

        return view('programs.motivo_cliente_black_list.modal.deletar')->with(['dados' => $dados]);
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
        
        $motivoClienteBlackListObj = MotivoClienteBlackList::find($id);
        $motivoClienteBlackListObj->deleted_by = Auth::id();
        $motivoClienteBlackListObj->save();
        $motivoClienteBlackListObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }
}
