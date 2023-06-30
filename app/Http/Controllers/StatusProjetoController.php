<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\StatusProjeto;
use App\StatusProjetoExibicao;
use Auth;
use App\Http\Requests\StatusProjetoCadastrarRequest;
use App\Http\Requests\StatusProjetoEditarRequest;

class StatusProjetoController extends Controller
{
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\StatusProjeto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\StatusProjeto');
        return view('programs.fases_projeto.index');
    }

    public function modalAdicionar(){
        $status =  $this->StatusProjetoExibicao();
        return view('programs.fases_projeto.modal.adicionar')->with(['status'=> $status]);
    }

    public function modalEditar(Request $request){
        $status =  $this->StatusProjetoExibicao();
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

        $StatusProjetoObj = StatusProjeto::find($id);
        $dados = [
            'id' => encrypt($StatusProjetoObj->id),
            'posicao' => $StatusProjetoObj->posicao,
            'descricao' => $StatusProjetoObj->descricao,
            'dias' => $StatusProjetoObj->dias,
            'status' => $StatusProjetoObj->status_projeto_exibicao_id
        ];
        return view('programs.fases_projeto.modal.editar')->with(['status'=> $status, 'dados' => $dados]);
    }

    public function adicionar(StatusProjetoCadastrarRequest $request) {
        $campos = $request->only(['posicao', 'descricao', 'dias', 'status']);

        $posicao = StatusProjeto::where('posicao', $campos['posicao'])->exists();
        if($posicao === true){
            $StatusProjetoUpdate = StatusProjeto::where([
            ['posicao', '>=', $campos['posicao']],
            ['posicao', '!=', 99],
        ]);
            $array = $StatusProjetoUpdate->get();
            foreach($array as $value){
                $value->posicao += 1;
                $value->updated_by = Auth::id();
                $value->save();
            }
        }

        $StatusProjetoObj = new StatusProjeto();
        $StatusProjetoObj->posicao = $campos['posicao'];
        $StatusProjetoObj->descricao = $campos['descricao'];
        $StatusProjetoObj->dias = $campos['dias'];
        $StatusProjetoObj->status_projeto_exibicao_id = $campos['status'];
        $StatusProjetoObj->created_by = Auth::id();
        $StatusProjetoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function editar(StatusProjetoEditarRequest $request) {
        $campos = $request->only(['id', 'posicao', 'descricao', 'dias', 'status']);

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

        $StatusProjetoObj = StatusProjeto::find($id);

        if($StatusProjetoObj->posicao != $campos['posicao'] && $campos['posicao'] != 99){
            $posicao = StatusProjeto::where('posicao', $campos['posicao'])->exists();
            if($posicao === true){
                $StatusProjetoUpdate = StatusProjeto::where([
                ['posicao', '>=', $campos['posicao']],
                ['posicao', '<', $StatusProjetoObj->posicao],
                ['posicao', '!=', 99],
            ]);
                $array = $StatusProjetoUpdate->get();
                foreach($array as $value){
                    $value->posicao += 1;
                    $value->updated_by = Auth::id();
                    $value->save();
                }
            }
        }
        $StatusProjetoObj->posicao = $campos['posicao'];
        $StatusProjetoObj->descricao = $campos['descricao'];
        $StatusProjetoObj->dias = $campos['dias'];
        $StatusProjetoObj->status_projeto_exibicao_id = $campos['status'];
        $StatusProjetoObj->updated_by = Auth::id();
        $StatusProjetoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtrar(Request $request){
        $filtros = $request->only(['fase']);
        $StatusProjetoObj = StatusProjeto::with('status_exibicao');
        if(!empty($filtros['fase'])){
            $StatusProjetoObj->whereRaw('lower(descricao) like \'%'.strtolower(trim($filtros['fase'])).'%\'');
        }
        $dados = $StatusProjetoObj->get();
        $reponse = [];
        foreach ($dados as $fase) {
            $reponse[] = [
                'id' => encrypt($fase->id),
                'fase' => $fase->descricao,
                'posicao' => $fase->posicao,
                'status' => $fase->status_exibicao->descricao,
                'dias' => $fase->dias
            ];
        }
        

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => '',
            'response' => $reponse
        ]);
    }

    public function StatusProjetoExibicao(){
        $StatusProjetoExibicaoObj = StatusProjetoExibicao::orderBy('posicao')
        ->get();

        $status = [];
        foreach($StatusProjetoExibicaoObj as $value){
            $status [$value->posicao] = $value->descricao;
        }

        return $status;
    }

    
}
