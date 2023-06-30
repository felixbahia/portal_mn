<?php

namespace App\Http\Controllers;

use Auth;

use App\TipoDeServico;

use Illuminate\Http\Request;

use App\Http\Requests\TipoDeServicoAdicionarRequest;
use App\Http\Requests\TipoDeServicoEditarRequest;

class TipoDeServicoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\TipoDeServico") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\TipoDeServico');

        return view('programs.tipo_de_servico.index');
    }

    public function modalAdicionar() {
        return view('programs.tipo_de_servico.modal.adicionar');
    }

    public function adicionar(TipoDeServicoAdicionarRequest $request) {
        $fields = $request->only('tipo_de_servico');
        $tipo_de_servico = strtoupper(tirarAcentos($fields['tipo_de_servico']));

        $tipo_de_servicoObj = new TipoDeServico;
        $tipo_de_servicoObj->descricao = $tipo_de_servico;
        $tipo_de_servicoObj->created_by = Auth::id();
        $tipo_de_servicoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
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

        $query = TipoDeServico::select();
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
        foreach ($result as $key => $tipo_de_servico) {            
            $dados = [
                'id' => encrypt($tipo_de_servico->id),
                'tipo_de_servico' => $tipo_de_servico->descricao
            ];
        }
        return view('programs.tipo_de_servico.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(TipoDeServicoEditarRequest $request){
        $fields = $request->only('id','tipo_de_servico');
        $tipo_de_servico = strtoupper(tirarAcentos($fields['tipo_de_servico']));
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

        $tipo_de_servicoObj = TipoDeServico::find($id);
        $tipo_de_servicoObj->descricao = $tipo_de_servico;
        $tipo_de_servicoObj->updated_by = Auth::id();
        $tipo_de_servicoObj->save();

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
        $query = TipoDeServico::select();
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
        foreach ($result as $key => $tipo_de_servico) {
            $dados = [
                'id' => encrypt($tipo_de_servico->id),
                'tipo_de_servico' => $tipo_de_servico->descricao
            ];
        }
        return view('programs.tipo_de_servico.modal.deletar')->with(['dados' => $dados]);
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
        
        
        $tipo_de_servicoObj = TipoDeServico::find($id);
        $tipo_de_servicoObj->deleted_by = Auth::id();
        $tipo_de_servicoObj->save();
        $tipo_de_servicoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filter(Request $request){
        $fields = $request->only('tipo_de_servico');
        $tipo_de_servico = strtoupper(tirarAcentos($fields['tipo_de_servico']));

        $query = TipoDeServico::select();
        if(!empty($tipo_de_servico)){
            $query->where('descricao', 'like', '%'.$tipo_de_servico.'%');
        }
        $result = $query->get();

        foreach($result as $tipo_de_servico){
            $retorno [] = [
                'id' => encrypt($tipo_de_servico->id),
                'descricao' => $tipo_de_servico->descricao
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

    public function autoComplete(Request $request){
        $descricao = $request->only('term');
        $return = [];
        $query = TipoDeServico::select('descricao')
            ->limit("15")
            ->orderBy('descricao', "ASC")
            ->where('descricao', 'ilike', '%'.$descricao['term'].'%')
            ->distinct('descricao')
            ->get()
            ->toArray();
        foreach ($query as $value){
            $value = (array) $value;
            $return[] = trim($value['descricao']);
        }

        return response()->json($return);
    }
}
