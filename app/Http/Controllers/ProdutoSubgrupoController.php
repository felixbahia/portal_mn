<?php

namespace App\Http\Controllers;

use Auth;

use App\ProdutoSubgrupo;
use App\ProdutoEspecificacao;

use Illuminate\Http\Request;

use App\Http\Requests\ProdutoSubgrupoAdicionarRequest;
use App\Http\Requests\ProdutoSubgrupoEditarRequest;

class ProdutoSubgrupoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ProdutoSubgrupo") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ProdutoSubgrupo');

        return view('programs.produto.subgrupo.index');
    }

    public function modalAdicionar() {
        return view('programs.produto.subgrupo.modal.adicionar');
    }

    public function adicionar(ProdutoSubgrupoAdicionarRequest $request) {
        $fields = $request->only('subgrupo');
        $subgrupo = strtoupper(tirarAcentos($fields['subgrupo']));

        $subgrupoObj = new ProdutoSubgrupo;
        $subgrupoObj->descricao = $subgrupo;
        $subgrupoObj->created_by = Auth::id();
        $subgrupoObj->save();

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

        $query = ProdutoSubgrupo::select();
        $query->where('id', '=', $id);
        if(is_null($query)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
        $result = $query->first();
     
        $dados = [
            'id' => encrypt($result->id),
            'subgrupo' => $result->descricao
        ];
        return view('programs.produto.subgrupo.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(ProdutoSubgrupoEditarRequest $request){
        $fields = $request->only('id','subgrupo');
        $subgrupo = strtoupper(tirarAcentos($fields['subgrupo']));
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

        $subgrupoObj = ProdutoSubgrupo::find($id);

        $subgrupo_antigo = $subgrupoObj->descricao;
        $produtoEspecificacaoObj = ProdutoEspecificacao::select(); 
        $produtoEspecificacaoObj->where('subgrupo', $subgrupo_antigo);
        $produtoEspecificacaoObj->update(['subgrupo' => $subgrupo]);

        $subgrupoObj->descricao = $subgrupo;
        $subgrupoObj->updated_by = Auth::id();
        $subgrupoObj->save();

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
        $query = ProdutoSubgrupo::select();
        $query->where('id', '=', $id);
        if(is_null($query)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
        $result = $query->first();

        $quantidade = ProdutoEspecificacao::where('subgrupo', $result->descricao)->count();

        $dados = [
            'id' => encrypt($result->id),
            'subgrupo' => $result->descricao
        ];

        return view('programs.produto.subgrupo.modal.deletar')->with(['dados' => $dados, 'quantidade' => $quantidade]);
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
        
        
        $subgrupoObj = ProdutoSubgrupo::find($id);
        $subgrupoObj->deleted_by = Auth::id();
        $subgrupoObj->save();
        $subgrupoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filter(Request $request){
        $fields = $request->only('subgrupo');
        $subgrupo = strtoupper(tirarAcentos($fields['subgrupo']));
        $retorno = [];

        $query = ProdutoSubgrupo::select();
        if(!empty($subgrupo)){
            $query->where('descricao', 'like', '%'.$subgrupo.'%');
        }
        $result = $query->get();

        foreach($result as $subgrupo){
            $retorno [] = [
                'id' => encrypt($subgrupo->id),
                'descricao' => $subgrupo->descricao
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
        $query = ProdutoSubgrupo::select('descricao')
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
