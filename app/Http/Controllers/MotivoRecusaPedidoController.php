<?php

namespace App\Http\Controllers;

use Auth;
use App\User;
use App\MotivoRecusaPedido;
use Illuminate\Http\Request;

use App\Http\Requests\MotivoRecusaPedidoCadastroRequest;
use App\Http\Requests\MotivoRecusaPedidoEdicaoRequest;

class MotivoRecusaPedidoController extends Controller
{
    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\MotivoRecusaPedido") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\MotivoRecusaPedido');
        return view('programs.motivo_recusa.index');
    }

    public function cadastro(){
        return view('programs.motivo_recusa.cadastro');
    }

    /**
    * Display a listing of the resource.
    * @param \App\Http\Requests\MotivoRecusaPedidoCadastroRequest $request 
    * @return \Illuminate\Http\Response
    */
    public function cadastrar(MotivoRecusaPedidoCadastroRequest $request) {
        $campos = $request->only(['motivo']);
        $MotivoRecusaPedidoObj = new MotivoRecusaPedido();
        $MotivoRecusaPedidoObj->motivo = $campos['motivo'];
        $MotivoRecusaPedidoObj->created_by = Auth::id();
        
        try{
            $MotivoRecusaPedidoObj->save();
            return response()->json([
                'status' => 'success',
                'message' => '',
                'error' => '',
                'response' => ''
            ]);
        } catch(\Except $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade. Tenten novamente!',
                'error' => '',
                'response' => ''
            ]);
        }
    }

    public function filtrar(Request $request){
        $filtros = $request->only(['motivo']);
        $MotivoRecusaPedidoObj = MotivoRecusaPedido::select('*');
        if(!empty($filtros['motivo'])){
            $MotivoRecusaPedidoObj->whereRaw('lower(motivo) like \''.strtolower(trim($filtros['motivo'])).'\'');
        }
        $dados = $MotivoRecusaPedidoObj->get();
        $reponse = [];
        foreach ($dados as $key => $motivo) {
            $reponse[] = [
                'id' => $motivo->id,
                'motivo' => $motivo->motivo
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => '',
            'response' => $reponse
        ]);
    }

    public function editcao(Request $request){
        $campo = $request->only(['id']);
        $MotivoRecusaPedidoObj = MotivoRecusaPedido::find($campo['id']);
        if(is_null($MotivoRecusaPedidoObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
        return view('programs.motivo_recusa.edicao')->with(['dados' => $MotivoRecusaPedidoObj->toArray()]);
    }
    /**
    * Display a listing of the resource.
    * @param \App\Http\Requests\MotivoRecusaPedidoEdicaoRequest $request 
    * @return \Illuminate\Http\Response
    */
    public function editar(MotivoRecusaPedidoEdicaoRequest $request) {
        $campos = $request->only(['id', 'motivo']);
        $MotivoRecusaPedidoObj = MotivoRecusaPedido::find($campos['id']);
        $MotivoRecusaPedidoObj->motivo = $campos['motivo'];
        $MotivoRecusaPedidoObj->updated_by = Auth::id();
        
        try{
            $MotivoRecusaPedidoObj->save();
            return response()->json([
                'status' => 'success',
                'message' => '',
                'error' => '',
                'response' => ''
            ]);
        } catch(\Except $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade. Tenten novamente!',
                'error' => '',
                'response' => ''
            ]);
        }
    }

    public function delecao(Request $request){
        $campo = $request->only(['id']);
        $MotivoRecusaPedidoObj = MotivoRecusaPedido::find($campo['id']);
        if(is_null($MotivoRecusaPedidoObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
        return view('programs.motivo_recusa.deletar')->with(['dados' => $MotivoRecusaPedidoObj->toArray()]);
    }
    public function deletar(Request $request) {
        $campos = $request->only(['id']);
        $MotivoRecusaPedidoObj = MotivoRecusaPedido::find($campos['id']);
        $MotivoRecusaPedidoObj->deleted_by = Auth::id();
        
        try{
            $MotivoRecusaPedidoObj->delete();
            return response()->json([
                'status' => 'success',
                'message' => '',
                'error' => '',
                'response' => ''
            ]);
        } catch(\Except $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade. Tenten novamente!',
                'error' => '',
                'response' => ''
            ]);
        }
    }
    
}
