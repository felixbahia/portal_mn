<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\Http\Requests\ProdutoTecidoBaseRequest;
use App\Http\Requests\ProdutoTecidoBaseEditarRequest;

use App\ProdutoTecidoBase;

class ProdutoTecidoBaseController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ProdutoTecidoBase") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ProdutoTecidoBase');

        return view('programs.produto.tecido_base.index');
    }

    public function modalAdicionar() {
        return view('programs.produto.tecido_base.modal.adicionar');
    }

    public function adicionar(ProdutoTecidoBaseRequest $request) {
        $fields = $request->only('codigo_produto', 'codigo_produto_base');

        $codigo_produto = strtoupper($fields['codigo_produto']);
        $codigo_produto_base = strtoupper($fields['codigo_produto_base']);

        $produtoTecidoBaseObj = new ProdutoTecidoBase;
        $produtoTecidoBaseObj->codigo_produto = $codigo_produto;
        $produtoTecidoBaseObj->codigo_produto_base = $codigo_produto_base;
        $produtoTecidoBaseObj->created_by = Auth::id();
        $produtoTecidoBaseObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filter(Request $request){
        $fields = $request->only('codigo_produto', 'codigo_produto_base', 'descricao');

        $retorno = [];

        $query = ProdutoTecidoBase::select();
        if(!empty($fields['codigo_produto'])){
            $query->where('codigo_produto', 'ilike', '%'.$fields['codigo_produto'].'%');
        }
        if(!empty($fields['codigo_produto_base'])){
            $query->where('codigo_produto_base', 'ilike', '%'.$fields['codigo_produto_base'].'%');
        }
        if(!empty($fields['descricao'])){
            $query->whereHas('tecido_base_detalhes', function($query) use ($fields){
                $query
                    ->where('descricao', 'ilike', '%'.$fields['descricao'].'%')
                    ->where('ativo', 'true');
            });
        }
        $result = $query->get();

        foreach($result as $tecido_base){
            $retorno [] = [
                'id' => encrypt($tecido_base->id),
                'codigo_produto' => $tecido_base->codigo_produto,
                'codigo_produto_base' => $tecido_base->codigo_produto_base,
                'descricao' => $tecido_base->tecido_base_detalhes->descricao
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

        $tecido_base = ProdutoTecidoBase::find($id);
        if(is_null($tecido_base)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
              
        $dados = [
            'id' => encrypt($tecido_base->id),
            'codigo_produto' => $tecido_base->codigo_produto,
            'codigo_produto_base' => $tecido_base->codigo_produto_base,
            'descricao' => $tecido_base->tecido_base_detalhes->descricao
        ];
        return view('programs.produto.tecido_base.modal.editar')->with(['dados' => $dados]);
    }

    public function editar(ProdutoTecidoBaseEditarRequest $request){
        $fields = $request->only('id', 'codigo_produto', 'codigo_produto_base');

        $codigo_produto = strtoupper($fields['codigo_produto']);
        $codigo_produto_base = strtoupper($fields['codigo_produto_base']);

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

        $produtoTecidoBaseObj = ProdutoTecidoBase::find($id);
        $produtoTecidoBaseObj->codigo_produto = $codigo_produto;
        $produtoTecidoBaseObj->codigo_produto_base = $codigo_produto_base;
        $produtoTecidoBaseObj->updated_by = Auth::id();
        $produtoTecidoBaseObj->save();

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
        $tecido_base = ProdutoTecidoBase::find($id);
        if(is_null($tecido_base)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }

        $dados = [
            'id' => encrypt($tecido_base->id),
            'codigo_produto' => $tecido_base->codigo_produto,
            'codigo_produto_base' => $tecido_base->codigo_produto_base,
            'descricao' => $tecido_base->tecido_base_detalhes->descricao
        ];

        return view('programs.produto.tecido_base.modal.deletar')->with(['dados' => $dados]);
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
        
        
        $produtoTecidoBaseObj = ProdutoTecidoBase::find($id);
        $produtoTecidoBaseObj->deleted_by = Auth::id();
        $produtoTecidoBaseObj->save();
        $produtoTecidoBaseObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function autoComplete(Request $request){
        $fields = $request->only(["term"]);
        $return = [];
        $query = ProdutoTecidoBase::select();
        $query->whereHas('tecido_base_detalhes', function($query) use($fields){
            $query->where('descricao', 'ilike', '%'.$fields['term'].'%')
            ->where('ativo', 'true');
        });
        $result = $query->get();
        foreach ($result as $value){
            $return[] = [
                'label' => trim($value->tecido_base_detalhes->descricao),
                'value' => trim($value->tecido_base_detalhes->codigo_produto)
            ];
        }
        return response()->json($return);
    }

    public function modalBuscar(){
        return view('programs.produto.tecido_base.modal.buscar');
    }

    public function filtroBuscar(Request $request){
        $fields = $request->only('grupo','codigo_produto','descricao','marca', 'linha', 'subgrupo', 'codigo_produto_base');

        $query = ProdutoTecidoBase::select();
        if(!empty($fields['codigo_produto_base'])){
            $query->where('codigo_produto_base', 'ilike', '%'.$fields['codigo_produto_base'].'%');
        }
        $query->whereHas('tecido_base_detalhes', function($query) use($fields){
            if(!empty($fields['grupo'])){
                $query->where('grupo', 'ilike', '%'.trim($fields['grupo']).'%');
            }
            if(!empty($fields['codigo_produto'])){
                $query->where('codigo_produto', 'ilike', '%'.trim($fields['codigo_produto']).'%');
            }
            if(!empty($fields['descricao'])){
                $query->where('descricao', 'ilike', '%'.trim($fields['descricao']).'%');
            }
            if(!empty($fields['marca'])){
                $query->where('marca', 'ilike', '%'.trim($fields['marca']).'%');
            }
            if(!empty($fields['linha'])){
                $query->where('linha', 'ilike', '%'.trim($fields['linha']).'%');
            }
            if(!empty($fields['campo']) && !empty($fields['condicao'])){
                $query->where($fields['campo'], 'ilike', trim($fields['condicao']));
            }
            $query->where('descricao', 'not ilike', '%DESATIVADO%');
            $query->where('ativo', 'true');
        });
        $result = $query->get();

        $produtos = [];
        $tecidos_base = [];

        foreach($result as $tecido_base){
            $tecidos_base[]=[
                'grupo' => $tecido_base->tecido_base_detalhes->grupo,
                'codigo' => $tecido_base->tecido_base_detalhes->codigo_produto,
                'descricao' => $tecido_base->tecido_base_detalhes->descricao,
                'marca' => $tecido_base->tecido_base_detalhes->marca,
                'linha' => $tecido_base->tecido_base_detalhes->linha,
                'subgrupo' => $tecido_base->tecido_base_detalhes->subgrupo,
                'prefixo' => $tecido_base->codigo_produto_base
            ];
        }
        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $tecidos_base
        ];
        return response()->json($retorno);
    }

    public function getTecidoBase(Request $request){
        $fields = $request->only('codigo', 'descricao');

        if(!empty($fields['descricao']) || !empty($fields['codigo'])){
            $query = ProdutoTecidoBase::select();
            $query->whereHas('tecido_base_detalhes', function($query) use($fields){
                if(!empty($fields['descricao'])){
                    $query->where('descricao', '=', strtoupper(trim($fields['descricao'])));
                }
                if(!empty($fields['codigo'])){
                    $query->where('codigo_produto', '=', strtoupper(trim($fields['codigo'])));
                }
                $query->where('descricao', 'not ilike', '%DESATIVADO%');
                $query->where('ativo', 'true');
            });
            $result = $query->first();

            if(!empty($result)){
                $resultado = [
                    'descricao' => $result->tecido_base_detalhes->descricao,
                    'grupo' => $result->tecido_base_detalhes->grupo,
                    'codigo_produto' => $result->tecido_base_detalhes->codigo_produto,
                    'marca' => $result->tecido_base_detalhes->marca,
                    'linha' => $result->tecido_base_detalhes->linha,
                    'subgrupo' => $result->tecido_base_detalhes->subgripo
                ];
                $retorno = [
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => $resultado
                ];
                return response()->json($retorno);
            }
        }
        
        $retorno = [
            'status' => 'error',
            'message' => '',
            'error' => ['descricao' => 'Tecido Base não encontrado'],
            'response' => ''
        ];
        return response()->json($retorno,422);   
    } 
}
