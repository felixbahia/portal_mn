<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\MotivoCancelamentoPedido;
use App\PedidosVendaNasajon;

use App\Http\Requests\MotivoCancelamentoPedidoRequest;

class MotivoCancelamentoPedidoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\MotivoCancelamentoPedido") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\MotivoCancelamentoPedido');

        return view('programs.motivo_cancelamento_pedido.index');
    }

    public function modalAdicionar(){
        return view('programs.motivo_cancelamento_pedido.modal.adicionar');
    }

    public function adicionar(MotivoCancelamentoPedidoRequest $request){
        $motivo = $request->only('motivo')['motivo'];
        $motivoCancelamentoPedidoObj = new MotivoCancelamentoPedido;
        $motivoCancelamentoPedidoObj->descricao = $motivo;
        $motivoCancelamentoPedidoObj->created_by = Auth::id();
        $motivoCancelamentoPedidoObj->save();

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
        $query = MotivoCancelamentoPedido::select();
        if(!empty($fields['motivo'])){
            $query->where('descricao', 'ilike', '%'.$motivo.'%');
        }
        $result = $query->get();
        $reponse = [];
        foreach ($result as $key => $motivo) {
            $reponse[] = [
                'id' => encrypt($motivo->id),
                'motivo' => $motivo->descricao
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

        $motivo_ajuste_estoque = MotivoCancelamentoPedido::find($id);
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
            'motivo' => $motivo_ajuste_estoque->descricao
        ];

        return view('programs.motivo_cancelamento_pedido.modal.editar')->with('dados', $dados);
    }

    public function editar(MotivoCancelamentoPedidoRequest $request){
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

        $motivoCancelamentoPedidoObj = MotivoCancelamentoPedido::find($id);
        $motivoCancelamentoPedidoObj->descricao = $fields['motivo'];
        $motivoCancelamentoPedidoObj->updated_by = Auth::id();
        $motivoCancelamentoPedidoObj->save();

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

        $motivo_ajuste_estoque = MotivoCancelamentoPedido::find($id);
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
            'motivo' => $motivo_ajuste_estoque->descricao
        ];
        return view('programs.motivo_cancelamento_pedido.modal.deletar')->with('dados', $dados);
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
        
        
        $motivoCancelamentoPedidoObj = MotivoCancelamentoPedido::find($id);
        $motivoCancelamentoPedidoObj->deleted_by = Auth::id();
        $motivoCancelamentoPedidoObj->save();
        $motivoCancelamentoPedidoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function modalCancelamentoPedidoNasajon(Request $request){
        $id = $request->only(['id'])['id'];

        $pedidoObj = PedidosVendaNasajon::find($id);

        $motivo_ajuste_estoque = MotivoCancelamentoPedido::select()->get();

        $motivos = [];
        $motivos[0] = "Selecione o Motivo";
        foreach($motivo_ajuste_estoque as $value){
            $motivos[$value->id] = $value->descricao;
        }

        $dados = [
            'id' => $pedidoObj->id,
            'numero' => $pedidoObj->numero,
            'estabelecimento' => $pedidoObj->estabelecimento_codigo.' - '.$pedidoObj->estabelecimento_descricao,
            'cliente' => $pedidoObj->cliente_razaosocial.' - '.$pedidoObj->cliente_cnpj,
            'valor' => parserValor($pedidoObj->valor),
        ];

        return view('programs.motivo_cancelamento_pedido.modal.cancelamento_pedido_nasajon')->with(['motivos' => $motivos, 'dados' => $dados]);
    }
}
