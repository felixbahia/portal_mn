<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Http\Requests\RetornoCobrancaCadastrarRequest;
use App\RetornoCobranca;
use App\Http\Requests\RetornoCobrancaEditarRequest;


class RetornoCobrancaController extends Controller
{
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\RetornoCobranca") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\RetornoCobranca');
        return view('programs.retornos_cobrancas.index');
    }

    public function modalAdicionar(){
        return view('programs.retornos_cobrancas.modal.adicionar');
    }

    public function modalEditar(Request $request){
        $campo = $request->only(['id']);
        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $MotivoRetornoCobrancaObj = RetornoCobranca::find($id);
        if(is_null($MotivoRetornoCobrancaObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }

            $dados = [
                'id' => encrypt($MotivoRetornoCobrancaObj->id),
                'motivo' => $MotivoRetornoCobrancaObj->motivo,
                'observacao' => $MotivoRetornoCobrancaObj->observacao
            ];
     
        return view('programs.retornos_cobrancas.modal.editar')->with(['dados' => $dados]);
    }

    public function modalDeletar(Request $request){
        $campo = $request->only(['id']);

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $MotivoRetornoCobrancaObj = RetornoCobranca::find($id);

        $dados = [
            'id' => encrypt($MotivoRetornoCobrancaObj->id),
            'motivo' => $MotivoRetornoCobrancaObj->motivo
        ];

        return view('programs.retornos_cobrancas.modal.deletar')->with(['dados' => $dados]);
    }

    public function adicionar(RetornoCobrancaCadastrarRequest $request) {
        $campos = $request->only(['motivo', 'observacao']);
        $MotivoRetornoCobrancaObj = new RetornoCobranca();
        $MotivoRetornoCobrancaObj->motivo = $campos['motivo'];
        $MotivoRetornoCobrancaObj->observacao = $campos['observacao'] ?? false;
        $MotivoRetornoCobrancaObj->created_by = Auth::id();
        $MotivoRetornoCobrancaObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtrar(Request $request){
        $filtros = $request->only(['motivo']);
        $MotivoRetornoCobrancaObj = RetornoCobranca::select();
        if(!empty($filtros['motivo'])){
            $MotivoRetornoCobrancaObj->whereRaw('lower(motivo) like \'%'.strtolower(trim($filtros['motivo'])).'%\'');
        }
        $dados = $MotivoRetornoCobrancaObj->get();
        $reponse = [];
        foreach ($dados as $motivo) {
            $reponse[] = [
                'id' => encrypt($motivo->id),
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

    public function editar(RetornoCobrancaEditarRequest $request) {
        $campo = $request->only(['id', 'motivo', 'observacao']);
        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $MotivoRetornoCobrancaObj = RetornoCobranca::find($id);
        $MotivoRetornoCobrancaObj->motivo = $campo['motivo'];
        $MotivoRetornoCobrancaObj->observacao = $campo['observacao'] ?? false;
        $MotivoRetornoCobrancaObj->updated_by = Auth::id();
        $MotivoRetornoCobrancaObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function deletar(Request $request) {
        $campo = $request->only(['id']);

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $MotivoRecusaPedidoObj = RetornoCobranca::find($id);
        $MotivoRecusaPedidoObj->deleted_by = Auth::id();
        $MotivoRecusaPedidoObj->save();
        $MotivoRecusaPedidoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

}
