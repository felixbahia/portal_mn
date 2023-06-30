<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\Feriado;

use App\Http\Requests\FeriadoAdicionarEditarRequest;

class FeriadoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Feriado") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Feriado');

        return view('programs.feriado.index');
    }

    public function modalAdicionar() {
        return view('programs.feriado.modal.adicionar');
    }

    public function adicionar(FeriadoAdicionarEditarRequest $request){
        $feriado = $request->only('feriado')['feriado'];

        $feriado = Carbon::createFromFormat('d/m/Y', $feriado)->setTime(0,0,0);

        $feriadoObj = new Feriado;
        $feriadoObj->feriado = $feriado;
        $feriadoObj->created_by = Auth::id();
        $feriadoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
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

        $feriadoObj = Feriado::find($id);
          
        $dados = [
            'id' => encrypt($feriadoObj->id),
            'feriado' => parserData($feriadoObj->feriado),
        ];

        return view('programs.feriado.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(FeriadoAdicionarEditarRequest $request){
        $fields = $request->only('id','feriado');

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

        $feriado = Carbon::createFromFormat('d/m/Y', $fields['feriado'])->setTime(0,0,0);

        $feriadoObj = Feriado::find($id);
        $feriadoObj->feriado = $feriado;
        $feriadoObj->updated_by = Auth::id();
        $feriadoObj->save();

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

        $feriadoObj = Feriado::find($id);
          
        $dados = [
            'id' => encrypt($feriadoObj->id),
            'feriado' => parserData($feriadoObj->feriado),
        ];

        return view('programs.feriado.modal.deletar')->with(['dados' => $dados]);
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
        
        
        $feriadoObj = Feriado::find($id);
        $feriadoObj->deleted_by = Auth::id();
        $feriadoObj->save();
        $feriadoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtro(Request $request){
        $fields = $request->only(['mes_ano']);
        $retorno = [];

        $query = Feriado::select();
        if(!empty($fields['mes_ano'])){
            $primeiro_dia_do_mes = Carbon::createFromFormat('m/Y', $fields['mes_ano'])->setTime(0,0,0)->firstOfMonth();
            $ultimo_dia_do_mes = Carbon::createFromFormat('m/Y', $fields['mes_ano'])->setTime(0,0,0)->lastOfMonth();

            $query->whereBetween('feriado', [$primeiro_dia_do_mes,$ultimo_dia_do_mes]);
        }
        $result = $query->get();

        foreach($result as $feriado){
            $retorno [] = [
                'id' => encrypt($feriado->id),
                'feriado' => parserData($feriado->feriado),
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function getFeriadosMes($data){
        $primeiro_dia_do_mes = Carbon::parse($data)->setTime(0,0,0)->firstOfMonth();
        $ultimo_dia_do_mes = Carbon::parse($data)->setTime(0,0,0)->lastOfMonth();

        $query = Feriado::select();
        $query->whereBetween('feriado', [$primeiro_dia_do_mes,$ultimo_dia_do_mes]);
        $result = $query->get();
        
        $feriados = [];

        foreach($result as $feriado){
            $feriados [] = $feriado->feriado;
        }

        return $feriados;
    }

    public function getFeriadosPeriodo($data_inicial, $data_final){
        $data_inicial = Carbon::parse($data_inicial)->setTime(0,0,0);
        $data_final = Carbon::parse($data_final)->setTime(0,0,0);

        $query = Feriado::select();
        $query->whereBetween('feriado', [$data_inicial,$data_final]);
        $result = $query->get();
        
        $feriados = [];

        foreach($result as $feriado){
            $feriados [] = $feriado->feriado;
        }

        return $feriados;
    }
}
