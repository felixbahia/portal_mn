<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\FaturamentoPrevisto;

use App\Http\Requests\FaturamentoPrevistoAdicionarEditarRequest;

class FaturamentoPrevistoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\FaturamentoPrevisto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\FaturamentoPrevisto');

        return view('programs.faturamento_previsto.index');
    }

    public function modalAdicionar() {
        return view('programs.faturamento_previsto.modal.adicionar');
    }

    public function adicionar(FaturamentoPrevistoAdicionarEditarRequest $request){
        $fields = $request->only('mes_ano','nacional_valor', 'importado_valor');

        $data = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);

        $faturamentoPrevistoObj = new FaturamentoPrevisto;
        $faturamentoPrevistoObj->data = $data;
        $faturamentoPrevistoObj->nacional_valor = empty($fields['nacional_valor'])? 0 : parserNumber($fields['nacional_valor']);   
        $faturamentoPrevistoObj->importado_valor = empty($fields['importado_valor'])? 0 : parserNumber($fields['importado_valor']);        
        $faturamentoPrevistoObj->created_by = Auth::id();
        $faturamentoPrevistoObj->save();

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
        
        $query = FaturamentoPrevisto::select();
        if(!empty($fields['mes_ano'])){
            $data = Carbon::createFromFormat('d/m/Y', '01/'.$fields['mes_ano'])->setTime(0,0,0);
            $query->where('data', $data);
        }
        $result = $query->get();

        $faturamento_previstos = [];

        $total = [
            'nacional_valor' => 0,
            'importado_valor' => 0,
        ];
        foreach($result as $faturamento_previsto){
            $faturamento_previstos[] = [
                'id' => encrypt($faturamento_previsto->id),
                'mes_ano' => $faturamento_previsto->data->format('m/Y'),
                'nacional_valor' => empty($faturamento_previsto->nacional_valor)? '' : parserValor($faturamento_previsto->nacional_valor),
                'importado_valor' => empty($faturamento_previsto->importado_valor)? '' : parserValor($faturamento_previsto->importado_valor),
            ];

            $total['nacional_valor'] += $faturamento_previsto->nacional_valor;
            $total['importado_valor'] += $faturamento_previsto->importado_valor;
        }

        $total['nacional_valor'] = empty($total['nacional_valor'])? '' : parserValor($total['nacional_valor']);
        $total['importado_valor'] = empty($total['importado_valor'])? '' : parserValor($total['importado_valor']);

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'faturamento_previstos' => $faturamento_previstos,
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

        $faturamentoPrevistoObj = FaturamentoPrevisto::find($id);

        $dados = [
            'id' => encrypt($faturamentoPrevistoObj->id),
            'mes_ano' => $faturamentoPrevistoObj->data->format('m/Y'),
            'nacional_valor' => empty($faturamentoPrevistoObj->nacional_valor)? '' : parserValor($faturamentoPrevistoObj->nacional_valor),
            'importado_valor' => empty($faturamentoPrevistoObj->importado_valor)? '' : parserValor($faturamentoPrevistoObj->importado_valor),
        ];

        return view('programs.faturamento_previsto.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(FaturamentoPrevistoAdicionarEditarRequest $request){
        $fields = $request->only('id', 'mes_ano','nacional_valor', 'importado_valor');

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

        $faturamentoPrevistoObj = FaturamentoPrevisto::find($id);
        $faturamentoPrevistoObj->data = $data;
        $faturamentoPrevistoObj->nacional_valor = empty($fields['nacional_valor'])? 0 : parserNumber($fields['nacional_valor']);   
        $faturamentoPrevistoObj->importado_valor = empty($fields['importado_valor'])? 0 : parserNumber($fields['importado_valor']);        
        $faturamentoPrevistoObj->updated_by = Auth::id();
        $faturamentoPrevistoObj->save();

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

        $faturamentoPrevistoObj = FaturamentoPrevisto::find($id);

        $dados = [
            'id' => encrypt($faturamentoPrevistoObj->id),
            'mes_ano' => $faturamentoPrevistoObj->data->format('m/Y'),
            'nacional_valor' => empty($faturamentoPrevistoObj->nacional_valor)? '' : parserValor($faturamentoPrevistoObj->nacional_valor),
            'importado_valor' => empty($faturamentoPrevistoObj->importado_valor)? '' : parserValor($faturamentoPrevistoObj->importado_valor),
        ];

        return view('programs.faturamento_previsto.modal.deletar')->with(['dados' => $dados]);
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

        $faturamentoPrevistoObj = FaturamentoPrevisto::find($id);
        $faturamentoPrevistoObj->deleted_by = Auth::id();
        $faturamentoPrevistoObj->save();
        $faturamentoPrevistoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }
}
