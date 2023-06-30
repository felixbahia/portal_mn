<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\StatusProjetoExibicao;
use App\Http\Requests\StatusProjetoExibicaoCadastrarRequest;
use App\Http\Requests\StatusProjetoExibicaoEditarRequest;

class StatusProjetoExibicaoController extends Controller
{
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\StatusProjetoExibicao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\StatusProjetoExibicao');
        return view('programs.status_projeto.index');
    }

    public function modalAdicionar(){
        return view('programs.status_projeto.modal.adicionar');
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

        $StatusProjetoExibicaoObj = StatusProjetoExibicao::find($id);
        $dados = [
            'id' => encrypt($StatusProjetoExibicaoObj->id),
            'posicao' => $StatusProjetoExibicaoObj->posicao,
            'descricao' => $StatusProjetoExibicaoObj->descricao
        ];
        return view('programs.status_projeto.modal.editar')->with(['dados' => $dados]);
    }

    public function adicionar(StatusProjetoExibicaoCadastrarRequest $request) {
        $campos = $request->only(['posicao', 'descricao']);

        $posicao = StatusProjetoExibicao::where('posicao', $campos['posicao'])->exists();
        if($posicao === true){
            $StatusProjetoExibicaoUpdate = StatusProjetoExibicao::where([
            ['posicao', '>=', $campos['posicao']],
            ['posicao', '!=', 99],
        ]);
            $array = $StatusProjetoExibicaoUpdate->get();
            foreach($array as $value){
                $value->posicao += 1;
                $value->updated_by = Auth::id();
                $value->save();
            }
        }

        $StatusProjetoExibicaoObj = new StatusProjetoExibicao();
        $StatusProjetoExibicaoObj->posicao = $campos['posicao'];
        $StatusProjetoExibicaoObj->descricao = $campos['descricao'];
        $StatusProjetoExibicaoObj->created_by = Auth::id();
        $StatusProjetoExibicaoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtrar(Request $request){
        $filtros = $request->only(['status']);
        $StatusProjetoExibicaoObj = StatusProjetoExibicao::orderBy('posicao');
        if(!empty($filtros['status'])){
            $StatusProjetoExibicaoObj->whereRaw('lower(descricao) like \'%'.strtolower(trim($filtros['status'])).'%\'');
        }
        $dados = $StatusProjetoExibicaoObj->get();
        $reponse = [];
        foreach ($dados as $status) {
            $reponse[] = [
                'id' => encrypt($status->id),
                'status' => $status->descricao,
                'posicao' => $status->posicao
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => '',
            'response' => $reponse
        ]);
    }

    public function editar(StatusProjetoExibicaoEditarRequest $request) {
        $campos = $request->only(['id', 'posicao', 'descricao']);

        try{
            $id = decrypt($campos['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $StatusProjetoExibicaoObj = StatusProjetoExibicao::find($id);

        if($StatusProjetoExibicaoObj->posicao != $campos['posicao'] && $campos['posicao'] != 99){
            $posicao = StatusProjetoExibicao::where('posicao', $campos['posicao'])->exists();
            if($posicao === true){
                $StatusProjetoExibicaoUpdate = StatusProjetoExibicao::where([
                ['posicao', '>=', $campos['posicao']],
                ['posicao', '<', $StatusProjetoExibicaoObj->posicao],
                ['posicao', '!=', 99],
            ]);
                $array = $StatusProjetoExibicaoUpdate->get();
                foreach($array as $value){
                    $value->posicao += 1;
                    $value->updated_by = Auth::id();
                    $value->save();
                }
            }
        }
        $StatusProjetoExibicaoObj->posicao = $campos['posicao'];
        $StatusProjetoExibicaoObj->descricao = $campos['descricao'];
        $StatusProjetoExibicaoObj->updated_by = Auth::id();
        $StatusProjetoExibicaoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

}
