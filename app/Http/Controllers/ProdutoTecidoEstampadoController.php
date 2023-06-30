<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\Http\Requests\ProdutoTecidoEstampadoAdicionarRequest;
use App\Http\Requests\ProdutoTecidoEstampadoEditarRequest;

use App\ProdutoTecidoEstampado;
use App\ProdutoTecidoBase;

class ProdutoTecidoEstampadoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ProdutoTecidoEstampado") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ProdutoTecidoEstampado');

        return view('programs.produto.tecido_estampado.index');
    }

    public function modalAdicionar() {
        return view('programs.produto.tecido_estampado.modal.adicionar');
    }

    public function adicionar(ProdutoTecidoEstampadoAdicionarRequest $request) {
        $fields = $request->only('codigo_produto_final', 'codigo_produto_tecido_base', 'codigo_produto_desenho');

        $codigo_produto_final = strtoupper($fields['codigo_produto_final']);
        $codigo_produto_tecido_base = strtoupper($fields['codigo_produto_tecido_base']);
        $codigo_produto_desenho = strtoupper($fields['codigo_produto_desenho']);

        $query_tecido_base = ProdutoTecidoBase::select();
        $query_tecido_base->whereHas('tecido_base_detalhes', function($query) use($codigo_produto_tecido_base){
            $query->where('codigo_produto', 'ilike', $codigo_produto_tecido_base);
        });
        $id_tecido_base = $query_tecido_base->first()->id;

        $produtoTecidoEstampadoObj = new ProdutoTecidoEstampado;
        $produtoTecidoEstampadoObj->produto_codigo_final = $codigo_produto_final;
        $produtoTecidoEstampadoObj->produto_tecidos_bases_id = $id_tecido_base;
        $produtoTecidoEstampadoObj->produto_codigo_desenho = $codigo_produto_desenho;
        $produtoTecidoEstampadoObj->created_by = Auth::id();
        $produtoTecidoEstampadoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filter(Request $request){
        $fields = $request->only('codigo_produto_final', 'codigo_produto_tecido_base', 'codigo_produto_desenho', 'descricao_final', 'descricao_tecido_base', 'descricao_desenho');

        $retorno = [];

        $query = ProdutoTecidoEstampado::select();
        if(!empty($fields['codigo_produto_final'])){
            $query->where('produto_codigo_final', 'ilike', '%'.$fields['codigo_produto_final'].'%');
        }
        if(!empty($fields['codigo_produto_desenho'])){
            $query->where('produto_codigo_desenho', 'ilike', '%'.$fields['codigo_produto_desenho'].'%');
        }
        if(!empty($fields['codigo_produto_tecido_base']) || !empty($fields['descricao_tecido_base'])){
            $query->whereHas('tecido_base', function ($query) use($fields){
                if(!empty($fields['codigo_produto_tecido_base'])){
                    $query->where('codigo_produto', 'ilike', '%'.$fields['codigo_produto_tecido_base'].'%');
                }
                if(!empty($fields['descricao_tecido_base'])){
                    $query->whereHas('tecido_base_detalhes', function($query) use($fields){
                        $query->where('descricao', 'ilike', '%'.$fields['descricao_tecido_base'].'%');
                    });
                }
            });
            $query->where('produto_codigo_desenho', 'ilike', '%'.$fields['codigo_produto_desenho'].'%');
        }
        if(!empty($fields['descricao_final'])){
            $query->whereHas('produto_final_detalhes', function($query) use ($fields){
                $query->where('descricao', 'ilike', '%'.$fields['descricao_final'].'%');
            });
        }
        if(!empty($fields['descricao_desenho'])){
            $query->whereHas('desenho_detalhes', function($query) use ($fields){
                $query->where('descricao', 'ilike', '%'.$fields['descricao_desenho'].'%');
            });
        }
        $result = $query->get();

        foreach($result as $tecido_estampado){
            $retorno [] = [
                'id' => encrypt($tecido_estampado->id),
                'codigo_produto_tecido_estampado' => $tecido_estampado->produto_codigo_final,
                'descricao_tecido_estampado' => $tecido_estampado->produto_final_detalhes->descricao,
                'codigo_produto_tecido_base' => $tecido_estampado->tecido_base->codigo_produto,
                'descricao_tecido_base' => $tecido_estampado->tecido_base->tecido_base_detalhes->descricao,
                'codigo_produto_desenho' => $tecido_estampado->produto_codigo_desenho,
                'descricao_desenho' => $tecido_estampado->desenho_detalhes->descricao,
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

        $tecido_estampado = ProdutoTecidoEstampado::find($id);
        if(is_null($tecido_estampado)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
              
        $dados = [
            'id' => encrypt($tecido_estampado->id),
            'codigo_produto_tecido_estampado' => $tecido_estampado->produto_codigo_final,
            'descricao_tecido_estampado' => $tecido_estampado->produto_final_detalhes->descricao,
            'codigo_produto_tecido_base' => $tecido_estampado->tecido_base->codigo_produto,
            'descricao_tecido_base' => $tecido_estampado->tecido_base->tecido_base_detalhes->descricao,
            'codigo_produto_desenho' => $tecido_estampado->produto_codigo_desenho,
            'descricao_desenho' => $tecido_estampado->desenho_detalhes->descricao,
        ];
        return view('programs.produto.tecido_estampado.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(ProdutoTecidoEstampadoEditarRequest $request){
        $fields = $request->only('id', 'codigo_produto_final', 'codigo_produto_tecido_base', 'codigo_produto_desenho');

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

        $codigo_produto_final = strtoupper($fields['codigo_produto_final']);
        $codigo_produto_tecido_base = strtoupper($fields['codigo_produto_tecido_base']);
        $codigo_produto_desenho = strtoupper($fields['codigo_produto_desenho']);

        $query_tecido_base = ProdutoTecidoBase::select();
        $query_tecido_base->whereHas('tecido_base_detalhes', function($query) use($codigo_produto_tecido_base){
            $query->where('codigo_produto', 'ilike', $codigo_produto_tecido_base);
        });
        $id_tecido_base = $query_tecido_base->first()->id;

        $produtoTecidoEstampadoObj = ProdutoTecidoEstampado::find($id);
        $produtoTecidoEstampadoObj->produto_codigo_final = $codigo_produto_final;
        $produtoTecidoEstampadoObj->produto_tecidos_bases_id = $id_tecido_base;
        $produtoTecidoEstampadoObj->produto_codigo_desenho = $codigo_produto_desenho;
        $produtoTecidoEstampadoObj->updated_by = Auth::id();
        $produtoTecidoEstampadoObj->save();

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
        $tecido_estampado = ProdutoTecidoEstampado::find($id);
        if(is_null($tecido_estampado)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }

        $dados = [
            'id' => encrypt($tecido_estampado->id),
            'codigo_produto_tecido_estampado' => $tecido_estampado->produto_codigo_final,
            'descricao_tecido_estampado' => $tecido_estampado->produto_final_detalhes->descricao,
            'codigo_produto_tecido_base' => $tecido_estampado->tecido_base->codigo_produto,
            'descricao_tecido_base' => $tecido_estampado->tecido_base->tecido_base_detalhes->descricao,
            'codigo_produto_desenho' => $tecido_estampado->produto_codigo_desenho,
            'descricao_desenho' => $tecido_estampado->desenho_detalhes->descricao,
        ];

        return view('programs.produto.tecido_estampado.modal.deletar')->with(['dados' => $dados]);
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
        
        
        $produtoTecidoEstampadoObj = ProdutoTecidoEstampado::find($id);
        $produtoTecidoEstampadoObj->deleted_by = Auth::id();
        $produtoTecidoEstampadoObj->save();
        $produtoTecidoEstampadoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }
}
