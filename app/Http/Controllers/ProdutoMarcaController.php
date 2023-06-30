<?php

namespace App\Http\Controllers;

use Auth;

use App\ProdutoMarca;
use App\ProdutoEspecificacao;

use Illuminate\Http\Request;

use App\Http\Requests\ProdutoMarcaAdicionarRequest;
use App\Http\Requests\ProdutoMarcaEditarRequest;

class ProdutoMarcaController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ProdutoMarca") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ProdutoMarca');

        return view('programs.produto.marca.index');
    }

    public function modalAdicionar() {
        return view('programs.produto.marca.modal.adicionar');
    }

    public function adicionar(ProdutoMarcaAdicionarRequest $request) {
        $fields = $request->only('marca');
        $marca = strtoupper(tirarAcentos($fields['marca']));

        $marcaObj = new ProdutoMarca;
        $marcaObj->descricao = $marca;
        $marcaObj->created_by = Auth::id();
        $marcaObj->save();

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

        $query = ProdutoMarca::select();
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
            'marca' => $result->descricao
        ];
        return view('programs.produto.marca.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(ProdutoMarcaEditarRequest $request){
        $fields = $request->only('id','marca');
        $marca = strtoupper(tirarAcentos($fields['marca']));
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

        $marcaObj = ProdutoMarca::find($id);

        $marca_antigo = $marcaObj->descricao;
        $produtoEspecificacaoObj = ProdutoEspecificacao::select(); 
        $produtoEspecificacaoObj->where('marca', $marca_antigo);
        $produtoEspecificacaoObj->update(['marca' => $marca]);

        $marcaObj->descricao = $marca;
        $marcaObj->updated_by = Auth::id();
        $marcaObj->save();

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
        $query = ProdutoMarca::select();
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

        $quantidade = ProdutoEspecificacao::where('marca', $result->descricao)->count();

        $dados = [
            'id' => encrypt($result->id),
            'marca' => $result->descricao
        ];

        return view('programs.produto.marca.modal.deletar')->with(['dados' => $dados, 'quantidade' => $quantidade]);
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
        
        
        $marcaObj = ProdutoMarca::find($id);
        $marcaObj->deleted_by = Auth::id();
        $marcaObj->save();
        $marcaObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filter(Request $request){
        $fields = $request->only('marca');
        $marca = strtoupper(tirarAcentos($fields['marca']));
        $retorno = [];

        $query = ProdutoMarca::select();
        if(!empty($marca)){
            $query->where('descricao', 'like', '%'.$marca.'%');
        }
        $result = $query->get();

        foreach($result as $marca){
            $retorno [] = [
                'id' => encrypt($marca->id),
                'descricao' => $marca->descricao
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
        $query = ProdutoMarca::select('descricao')
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
