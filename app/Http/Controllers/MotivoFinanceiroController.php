<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;

use App\Helpers;
use App\MotivoFinanceiro;

use Illuminate\Http\Request;

use App\Http\Requests\MotivoFinanceiroRequest;
use App\Http\Requests\MotivoFinanceiroEditarRequest;

class MotivoFinanceiroController extends Controller
{
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\MotivoFinanceiros") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\MotivoFinanceiros');

        return view('programs.motivo_financeiro.index');
    }

    public function modalAdicionar(){
        return view('programs.motivo_financeiro.modal.adicionar');
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

        $query = MotivoFinanceiro::select('id', 'motivo');
        $query->where('id', '=', $id);
        if(is_null($query)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
        $result = $query->get();
        $reponse = [];
        foreach ($result as $key => $motivo) {
            $data = new Carbon($motivo->data);
            $data = $data->format('d/m/Y');
            
            $dados = [
                'id' => encrypt($motivo->id),
                'motivo' => $motivo->motivo
            ];
        }
        return view('programs.motivo_financeiro.modal.editar')->with(['dados' => $dados]);
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
        $query = MotivoFinanceiro::select('id','motivo');
        $query->where('id', '=', $id);
        if(is_null($query)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
        $result = $query->get();
        $reponse = [];
        foreach ($result as $key => $motivo) {
            $dados = [
                'id' => encrypt($motivo->id),
                'motivo' => $motivo->motivo
            ];
        }
        return view('programs.motivo_financeiro.modal.deletar')->with(['dados' => $dados]);
    }

    public function adicionar(MotivoFinanceiroRequest $request){
        $fields = $request->only('motivo');
        $MotivoFinanceiroObj = new MotivoFinanceiro;
        $MotivoFinanceiroObj->motivo = $fields['motivo'];
        $MotivoFinanceiroObj->created_by = Auth::id();
        $MotivoFinanceiroObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function editar(MotivoFinanceiroEditarRequest $request){
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

        $MotivoFinanceiroObj = MotivoFinanceiro::find($id);
        $MotivoFinanceiroObj->motivo = $fields['motivo'];
        $MotivoFinanceiroObj->updated_by = Auth::id();
        $MotivoFinanceiroObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
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
        
        
        $MotivoFinanceiroObj = MotivoFinanceiro::find($id);
        $MotivoFinanceiroObj->deleted_by = Auth::id();
        $MotivoFinanceiroObj->save();
        $MotivoFinanceiroObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filter(Request $request){
        $fields = $request->only('motivo');
        $query = MotivoFinanceiro::select('id', 'motivo');
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


    private function dadosVendedor(){
        $dropdown_usuarios = ['' => 'Selecione o vendedor'];
        if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false || strpos(strtolower(Auth::user()->tipo_usuario->nome), "supervisor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === 'administrador'){
            if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false){
                $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->whereIn('id', UserController::varreSubordinados(Auth::id()))->orderBy('name', 'asc')->get();
            }
            else if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === 'administrador'){
                $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->orderBy('name', 'asc')->get();
                $status_pedido['cancelados'] = 'Cancelados';
            }
            else if (strtolower(Auth::user()->tipo_usuario->nome) === "supervisor"){
                $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->whereIn('id', UserController::varreSubordinados(Auth::user()->responsavel))->orderBy('name', 'asc')->get();
            }
            foreach ($users as $user) {
                if(is_null($user->tipo_usuario)){
                    continue;
                }
                if (!is_null($user->codigo_representante)){
                    $dropdown_usuarios[$user->id] = strtoupper($user->name);
                }
            }
        }
        return $dropdown_usuarios;
    }

    private function dadosLancamento(){
        $lancamentos = [
            '' => 'Selecione Tipo de Lançamento',
            'D' => 'Débito',
            'C' => 'Crédito'
        ];

        return $lancamentos;
    }

    private function dadosEstabelecimentos(){
        $estabelecimentos = ['' => 'Selecione'];
        $estabelecimentos =array_merge($estabelecimentos, returnEmpresasPrologusView());
        return $estabelecimentos; 
    }

    private function ajusteCampoTabela($campo){
        $retorno = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='$campo'>$campo</div></div>";
        return $retorno;
    }

}
