<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\BancoPrevisto;

use App\Http\Requests\BancoPrevistoAdicionarEditarRequest;

class BancoPrevistoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\BancoPrevisto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BancoPrevisto');

        return view('programs.banco_previsto.index');
    }

    public function modalAdicionar() {
        return view('programs.banco_previsto.modal.adicionar');
    }

    public function adicionar(BancoPrevistoAdicionarEditarRequest $request){
        $fields = $request->only('mes_ano','valor');

        $data = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);

        $bancoPrevistoObj = new BancoPrevisto;
        $bancoPrevistoObj->data = $data;
        $bancoPrevistoObj->nacional_valor = empty($fields['valor'])? 0 : parserNumber($fields['valor']);   
        $bancoPrevistoObj->created_by = Auth::id();
        $bancoPrevistoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtro(Request $request){
        $fields = $request->only('mes_ano');
        
        $query = BancoPrevisto::select();
        if(!empty($fields['mes_ano'])){
            $data = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
            $query->where('data', $data);
        }
        $result = $query->get();

        $banco_previstos = [];

        $total = [
            'valor' => 0,
        ];
        foreach($result as $banco_previsto){
            $banco_previstos[] = [
                'id' => encrypt($banco_previsto->id),
                'mes_ano' => $banco_previsto->data->format('m/Y'),
                'valor' => empty($banco_previsto->nacional_valor)? '' : parserValor($banco_previsto->nacional_valor),
            ];

            $total['valor'] += $banco_previsto->nacional_valor;
        }

        $total['valor'] = empty($total['valor'])? '' : parserValor($total['valor']);
    
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'banco_previstos' => $banco_previstos,
                'total' => $total,
            ],
        ]);
    }

    public function modalEditar(Request $request) {
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $bancoPrevistoObj = BancoPrevisto::find($id);

        $dados = [
            'id' => encrypt($bancoPrevistoObj->id),
            'mes_ano' => $bancoPrevistoObj->data->format('m/Y'),
            'valor' => empty($bancoPrevistoObj->nacional_valor)? '' : parserValor($bancoPrevistoObj->nacional_valor),
        ];

        return view('programs.banco_previsto.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(BancoPrevistoAdicionarEditarRequest $request){
        $fields = $request->only('id', 'mes_ano','valor');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $data = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);

        $bancoPrevistoObj = BancoPrevisto::find($id);
        $bancoPrevistoObj->data = $data;
        $bancoPrevistoObj->nacional_valor = empty($fields['valor'])? 0 : parserNumber($fields['valor']);   
        $bancoPrevistoObj->updated_by = Auth::id();
        $bancoPrevistoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function modalDeletar(Request $request) {
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $bancoPrevistoObj = BancoPrevisto::find($id);

        $dados = [
            'id' => encrypt($bancoPrevistoObj->id),
            'mes_ano' => $bancoPrevistoObj->data->format('m/Y'),
            'valor' => empty($bancoPrevistoObj->nacional_valor)? '' : parserValor($bancoPrevistoObj->nacional_valor),
        ];

        return view('programs.banco_previsto.modal.deletar')->with(['dados' => $dados]);
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
            ], 422);
        }

        $bancoPrevistoObj = BancoPrevisto::find($id);
        $bancoPrevistoObj->deleted_by = Auth::id();
        $bancoPrevistoObj->save();
        $bancoPrevistoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }
}
