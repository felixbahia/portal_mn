<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\DespesaPlanejada;

use App\Http\Requests\DespesaPlanejadaRequest;

class DespesaPlanejadaController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\DespesaPlanejada") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\DespesaPlanejada');

        return view('programs.despesa_planejada.index');
    }

    public function modalAdicionar() {
        return view('programs.despesa_planejada.modal.adicionar');
    }

    public function adicionar(DespesaPlanejadaRequest $request){
        $fields = $request->only('mes_ano','valor');

        $data = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);

        $despesaPlanejadaObj = new DespesaPlanejada;
        $despesaPlanejadaObj->data = $data;
        $despesaPlanejadaObj->nacional_valor = empty($fields['valor'])? 0 : parserNumber($fields['valor']);   
        $despesaPlanejadaObj->created_by = Auth::id();
        $despesaPlanejadaObj->save();

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
        
        $query = DespesaPlanejada::select();
        if(!empty($fields['mes_ano'])){
            $data = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
            $query->where('data', $data);
        }
        $result = $query->get();

        $despesa_planejadas = [];

        $total = [
            'valor' => 0,
        ];
        foreach($result as $despesa_planejada){
            $despesa_planejadas[] = [
                'id' => encrypt($despesa_planejada->id),
                'mes_ano' => $despesa_planejada->data->format('m/Y'),
                'valor' => empty($despesa_planejada->nacional_valor)? '' : parserValor($despesa_planejada->nacional_valor),
            ];

            $total['valor'] += $despesa_planejada->nacional_valor;
        }

        $total['valor'] = empty($total['valor'])? '' : parserValor($total['valor']);
    
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'despesa_planejadas' => $despesa_planejadas,
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

        $despesaPlanejadaObj = DespesaPlanejada::find($id);

        $dados = [
            'id' => encrypt($despesaPlanejadaObj->id),
            'mes_ano' => $despesaPlanejadaObj->data->format('m/Y'),
            'valor' => empty($despesaPlanejadaObj->nacional_valor)? '' : parserValor($despesaPlanejadaObj->nacional_valor),
        ];

        return view('programs.despesa_planejada.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(DespesaPlanejadaRequest $request){
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

        $despesaPlanejadaObj = DespesaPlanejada::find($id);
        $despesaPlanejadaObj->data = $data;
        $despesaPlanejadaObj->nacional_valor = empty($fields['valor'])? 0 : parserNumber($fields['valor']);   
        $despesaPlanejadaObj->updated_by = Auth::id();
        $despesaPlanejadaObj->save();

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

        $despesaPlanejadaObj = DespesaPlanejada::find($id);

        $dados = [
            'id' => encrypt($despesaPlanejadaObj->id),
            'mes_ano' => $despesaPlanejadaObj->data->format('m/Y'),
            'valor' => empty($despesaPlanejadaObj->nacional_valor)? '' : parserValor($despesaPlanejadaObj->nacional_valor),
        ];

        return view('programs.despesa_planejada.modal.deletar')->with(['dados' => $dados]);
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

        $despesaPlanejadaObj = DespesaPlanejada::find($id);
        $despesaPlanejadaObj->deleted_by = Auth::id();
        $despesaPlanejadaObj->save();
        $despesaPlanejadaObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }
}
