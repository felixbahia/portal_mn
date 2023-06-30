<?php

namespace App\Http\Controllers;

use App\ProdutoDefeito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ProdutoDefeitoEditarRequest;
use App\Http\Requests\ProdutoDefeitoAdicionarRequest;

class ProdutoDefeitoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ProdutoDefeito") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ProdutoDefeito');

        return view('programs.produto_defeito.index');
    }

    public function filtro(Request $request){
        $campo = $request->only('descricao');

        $produtoDefeitoObj = ProdutoDefeito::select()->orderBy('descricao', 'ASC');

        if(!empty($campo['descricao'])){
            $produtoDefeitoObj->where('descricao', 'ilike', '%'.$campo['descricao'].'%');
        }

        $produtoDefeitos = $produtoDefeitoObj->get();
        $saida = [];

        foreach($produtoDefeitos as $produtoDefeito){
            $saida[] = [
                'id' => encrypt($produtoDefeito->id),
                'descricao' => $produtoDefeito->descricao
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $saida
        ];
        return response()->json($response, 200);
    }

    public function modalAdicionar() {
        return view('programs.produto_defeito.modal.adicionar');
    }

    public function salvar(ProdutoDefeitoAdicionarRequest $request){
        $campo = $request->only('descricao');

        $produtoDefeitoObj = new ProdutoDefeito;
        $produtoDefeitoObj->descricao = strtoupper($campo['descricao']);
        $produtoDefeitoObj->created_by = Auth::id();
        $produtoDefeitoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function modalEditar(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $produtoDefeitoObj = ProdutoDefeito::find($id);
        if(is_null($produtoDefeitoObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    $e,
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }

        $dados = [
            'id' => encrypt($produtoDefeitoObj->id),
            'descricao' => $produtoDefeitoObj->descricao
        ];
        return view('programs.produto_defeito.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(ProdutoDefeitoEditarRequest $request){
        $campo = $request->only('id', 'descricao');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $produtoDefeitoObj = ProdutoDefeito::find($id);
        $produtoDefeitoObj->descricao = strtoupper($campo['descricao']);
        $produtoDefeitoObj->updated_by = Auth::id();
        $produtoDefeitoObj->save(); 

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function modalDeletar(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $produtoDefeitoObj = ProdutoDefeito::find($id);
        if(is_null($produtoDefeitoObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    $e,
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }

        $dados = [
            'id' => encrypt($produtoDefeitoObj->id),
            'descricao' => $produtoDefeitoObj->descricao
        ];
        return view('programs.produto_defeito.modal.deletar')->with(['dados' => $dados]);
    }

    public function deletar(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $produtoDefeitoObj = ProdutoDefeito::find($id);
        $produtoDefeitoObj->deleted_by = Auth::id();
        $produtoDefeitoObj->save();
        $produtoDefeitoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }
}