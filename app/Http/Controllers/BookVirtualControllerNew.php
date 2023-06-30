<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Auth;

use App\ProdutoEspecificacao;

use App\Http\Requests\BookVirtualEditarDesenhoRequestNew;
use App\Http\Requests\BookVirtualEditarRequestNew;
use App\Http\Requests\BookVirtualSalvarDesenhoRequestNew;
use App\ProdutoGrupo;
use App\ProdutoGrupoDesenho;

class BookVirtualControllerNew extends Controller
{

    public $padroes_codigo = [
        '7_11' => 'Artigo 7 + 5 desenho',
        '6_10' => 'Artigo 6 + 5 desenho',
        '4_9' => 'Artigo 4 + 6 desenho + 3 tamanho'
    ];

    public $path = 'public/grupo/';
    public $book_path = 'public/book_virtual';
    public $grupo_path = 'public/grupo';


    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\BookVirtualNew") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BookVirtualNew');
        
        return view('programs.book_virtual_new.index');
    }

    public function cadastroIndex(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\BookVirtualCadastroNew") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\BookVirtualCadastroNew');
        return view('programs.book_virtual_new.cadastro.index');
    }


    public function cadastroModalEditar(Request $request) {
        $campo = $request->only('id');
       
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

        $produtoGrupo = ProdutoGrupo::with(['produtoEspecificacao' => function ($query) {
            $query->where('ativo', 'true');

        }], 'produtoGrupoDesenho')
        ->find($id);

        $dados = [
            'padrao_codigo' => $produtoGrupo->padrao_codigo,
            'id' => encrypt($produtoGrupo->id),
            'imagem' => !empty($produtoGrupo->imagem) ? Storage::url($this->path.$produtoGrupo->imagem) : '',
            'imagem_zoom' => Storage::exists(substr_replace($this->path.$produtoGrupo->imagem_zoom,'_zoom.jpg', -4)) ? Storage::url(substr_replace($this->path.$produtoGrupo->imagem_zoom,'_zoom.jpg', -4)) : '',
            'mostrar' => $produtoGrupo->mostrar
        ];

        $produtos = [];
        $desenhos = collect([]);

        foreach($produtoGrupo->produtoGrupoDesenho as $desenho){
            if(!empty($desenho->imagem)){
                $linha = [];
    
                $linha['id'] = $desenho->id;
                $linha['codigo_desenho'] = $desenho->codigo_desenho;
                $linha['imagem'] = Storage::url($this->path . 'desenhos/' .$desenho->imagem);
                $linha['imagem_zoom'] = Storage::exists($this->path . 'desenhos/'.substr_replace($desenho->imagem_zoom,'_zoom.jpg', -4)) ? Storage::url($this->path . 'desenhos/'.substr_replace($desenho->imagem_zoom,'_zoom.jpg', -4)) : '';
    
                $desenhos->push($linha);
            }else{
                $linha['imagem_zoom'] = Storage::exists($this->path . 'desenhos/'.substr_replace($desenho->imagem_zoom,'_zoom.jpg', -4)) ? Storage::url($this->path . 'desenhos/'.substr_replace($desenho->imagem_zoom,'_zoom.jpg', -4)) : '';
            }
        }
        
        foreach($produtoGrupo->produtoEspecificacao as $produto){
            if($desenhos->pluck('codigo_desenho')->search(substr($produto->codigo_produto, 7, 5), true) === false && $desenhos->pluck('codigo_desenho')->search(substr($produto->codigo_produto, 4, 6), true) === false && ($produtoGrupo->padrao_codigo == '7_11')){
                $desenhos->push(['codigo_desenho' => substr($produto->codigo_produto, 7, 5)]);
            }
            else if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($produto->codigo_produto, 6, 5), true) === false && $produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($produto->codigo_produto, 4, 6), true) === false && ($produtoGrupo->padrao_codigo == '6_10')){
               
                $desenhos->push(['codigo_desenho' => substr($produto->codigo_produto, 6, 5)]);
            }
            else if($desenhos->pluck('codigo_desenho')->search(substr($produto->codigo_produto, 4, 6), true) === false && ($produtoGrupo->padrao_codigo == '4_9')){
                $desenhos->push(['codigo_desenho' => substr($produto->codigo_produto, 4, 6)]);
            }

            $produtos[] = [
                'codigo' => $produto->codigo_produto,
                'descricao' => $produto->descricao,
                'subgrupo' => $produto->subgrupo,
                'marca' => $produto->marca,
                'linha' => $produto->linha
            ];
        }

        return view('programs.book_virtual_new.cadastro.modal.editar')->with([
            'dados' => $dados, 
            'produtos'=> $produtos,
            'padroes_codigo' => $this->padroes_codigo,
            'desenhos' => $desenhos,
            'id' => encrypt($id)
        ]);
    }

    public function cadastroEditar(BookVirtualEditarRequestNew $request) {
        $fields = $request->only('id', 'padrao_codigo', 'imagem_grupo', 'mostrar', 'imagem_grupo_zoom');

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

        $grupoObj = ProdutoGrupo::find($id);

        if(!empty($fields['imagem_grupo'])){
            $arquivo = $fields['imagem_grupo'];
            $nome_arquivo = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/',"\t"], '', str_replace(' ', '_', $grupoObj->descricao))).rand(1,100).".".$arquivo->getClientOriginalExtension();
            $arquivo->storeAs($this->path, $nome_arquivo);
        }else{
            $nome_arquivo = null;
        }  
        
        if(!empty($fields['imagem_grupo_zoom'])){
            $arquivo_zoom = $fields['imagem_grupo_zoom'];
            $nome_arquivo_zoom = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/',"\t"], '', str_replace(' ', '_', $grupoObj->descricao))).rand(1,100).".".$arquivo_zoom->getClientOriginalExtension();
            $arquivo_zoom->storeAs($this->path, substr_replace($nome_arquivo_zoom,'_zoom.jpg', -4));
        }else{
            $arquivo_zoom = null;
        } 

        $grupoObj->padrao_codigo = $fields['padrao_codigo'];
        $grupoObj->mostrar = $fields['mostrar'];

        if(!empty($nome_arquivo)){
            $grupoObj->imagem = $nome_arquivo;
        }
        if(!empty($nome_arquivo_zoom)){
            $grupoObj->imagem_zoom = $nome_arquivo_zoom;
        }
        
        $grupoObj->updated_by = Auth::id();
        $grupoObj->save();

        if(!is_null($grupoObj->padrao_codigo)){
            foreach($request->all() as $campo => $imagem){
                $termo = substr($campo,0,7);
                $termo2 = substr($campo,0,12);
                if($termo == 'imagem_' && $termo2 != 'imagem_zoom_' && $campo != 'imagem_grupo' && $campo != 'imagem_grupo_zoom'){
                    $codigo_desenho = substr($campo,7);
                    $arquivo = $imagem;
    
                    $produtoGrupoDesenho = ProdutoGrupoDesenho::where('codigo_desenho', $codigo_desenho)->where('produto_grupos_id', $id)->first();
                    if(!empty($produtoGrupoDesenho)){
    
                        $produtoGrupoDesenho->codigo_desenho = $codigo_desenho;
                        $nome_arquivo = strtolower(str_replace(',', '', str_replace(' ', '_', $codigo_desenho))).rand(1,100).'.' . $arquivo->getClientOriginalExtension();
                        $produtoGrupoDesenho->imagem =  $nome_arquivo;
                        $produtoGrupoDesenho->updated_by = Auth::id();
    
                        $produtoGrupoDesenho->save();
                        $arquivo->storeAs($this->path . 'desenhos/', $nome_arquivo);
                    }else{
                        $arquivo = $imagem;
                        $nome_arquivo = strtolower(str_replace(',', '', str_replace(' ', '_', $codigo_desenho))).rand(1,100).'.' . $arquivo->getClientOriginalExtension();
    
                        $produtoGrupoDesenho = ProdutoGrupoDesenho::where('produto_grupos_id', $id)->where('codigo_desenho', $codigo_desenho)->get();
    
                        if(count($produtoGrupoDesenho) > 0){
                            foreach($produtoGrupoDesenho as $desenho){
                                $desenho->deleted_by = 1;
                                $desenho->save();
                                $desenho->delete();
                            }
                        }
    
                        $produtoGrupoDesenho = new ProdutoGrupoDesenho;
                        $produtoGrupoDesenho->produto_grupos_id = $id;
                        $produtoGrupoDesenho->codigo_desenho = $codigo_desenho;
                        $produtoGrupoDesenho->imagem = $nome_arquivo;
                        $produtoGrupoDesenho->created_by = Auth::id();
                
                        $arquivo->storeAs($this->path . 'desenhos/', $nome_arquivo);
                    }
                    $produtoGrupoDesenho->save();
                }
                if($termo2 == 'imagem_zoom_' && $campo != 'imagem_grupo' && $campo != 'imagem_grupo_zoom'){
                    $codigo_desenho = substr($campo,12);
                    $arquivo = $imagem;
    
                    $produtoGrupoDesenho = ProdutoGrupoDesenho::where('codigo_desenho', $codigo_desenho)->where('produto_grupos_id', $id)->first();
                    if(!empty($produtoGrupoDesenho)){
                        $produtoGrupoDesenho->codigo_desenho = $codigo_desenho;
                        $nome_arquivo = strtolower(str_replace(',', '', str_replace(' ', '_', $codigo_desenho))).rand(1,100).'.' . $arquivo->getClientOriginalExtension();
                        $produtoGrupoDesenho->imagem_zoom =  $nome_arquivo;
                        $produtoGrupoDesenho->updated_by = Auth::id();
                        
                        $arquivo->storeAs($this->path . 'desenhos/', substr_replace($nome_arquivo,'_zoom.jpg', -4));
                    }else{
                        $produtoGrupoDesenho = ProdutoGrupoDesenho::find($produtoGrupoDesenho->id);
                        $nome_arquivo = strtolower(str_replace(',', '', str_replace(' ', '_', $codigo_desenho))).rand(1,100).'.' . $arquivo->getClientOriginalExtension();
                        $produtoGrupoDesenho->imagem_zoom =  $nome_arquivo;
                        $produtoGrupoDesenho->updated_by = Auth::id();
                        
                        $arquivo->storeAs($this->path . 'desenhos/', substr_replace($nome_arquivo,'_zoom.jpg', -4));
                    }
                    $produtoGrupoDesenho->save();
                }
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function cadastroFilter(Request $request){
        $fields = $request->only('grupo');

        $query = ProdutoGrupo::select();
        if(isset($fields['grupo']) && !empty($fields['grupo'])){
            $query->where('descricao', 'ilike', $fields['grupo']);
        }

        $result = $query->orderBy('descricao', 'asc')->get();

        $book = [];
        foreach($result as $value){
            $book []= [
                'grupo' => $value->descricao,
                'id' => encrypt($value->id)
            ];
        }

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $book
        ];
        return response()->json($retorno);
    }

    public function cadastroFilterChild(Request $request){
        $fields = $request->only('grupo');

        $busca = ProdutoEspecificacao::select();
        $busca->where('grupo', $fields['grupo']);
        $busca->where('ativo', 'true');
        $result = $busca->get();

        $produtos = [];
        foreach($result as $value){
           
            $produtos[]= [
                'codigo' => $value->codigo_produto,
                'descricao' => $value->descricao
            ];  
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $produtos
        ];
        return response()->json($response);
    }

    public function salvarDesenho(BookVirtualSalvarDesenhoRequestNew $request){

        ini_set('upload_max_filesize', '5M');

        $fields = $request->only('id', 'codigo');

        $id = decrypt($fields['id']);

        $produtoGrupoDesenho = ProdutoGrupoDesenho::where('produto_grupos_id', $id)->where('codigo_desenho', $fields['codigo'])->get();

        if(count($produtoGrupoDesenho) > 0){
            foreach($produtoGrupoDesenho as $desenho){
                $desenho->deleted_by = 1;
                $desenho->save();
                $desenho->delete();
            }
        }

        $produtoGrupoDesenhoObj = new ProdutoGrupoDesenho;

        $produtoGrupoDesenhoObj->codigo_desenho = $fields['codigo'];
        $produtoGrupoDesenhoObj->produto_grupos_id = $id;

        $imagem = 'imagem_'.$fields['codigo'];
        $arquivo = $request->$imagem;

        $nome_arquivo = strtolower(str_replace(',', '', str_replace(' ', '_', $fields['codigo']))).rand(1,100).'.' . $arquivo->getClientOriginalExtension();
        $produtoGrupoDesenhoObj->imagem = $nome_arquivo;
        $produtoGrupoDesenhoObj->created_by = Auth::id();
        $produtoGrupoDesenhoObj->save();

        $arquivo->storeAs($this->path . 'desenhos/', $nome_arquivo);

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'id' => $produtoGrupoDesenhoObj->id,
                'codigo_desenho' => $produtoGrupoDesenhoObj->codigo_desenho,
                'imagem' => Storage::url($this->path . 'desenhos/'.$produtoGrupoDesenhoObj->imagem) . '?' . time()
            ]
        ];

        return response()->json($retorno, 220);
    }

    public function editarDesenho(BookVirtualEditarDesenhoRequestNew $request){

        ini_set('upload_max_filesize', '5M');

        $fields = $request->only('id', 'codigo');

        $id = $fields['id'];

        $produtoGrupoDesenhoObj = ProdutoGrupoDesenho::find($id);

        $produtoGrupoDesenho = ProdutoGrupoDesenho::where('produto_grupos_id', $produtoGrupoDesenhoObj->produto_grupos_id)
        ->where('codigo_desenho', $fields['codigo'])
        ->where('id', '!=', $id)
        ->get();

        if(count($produtoGrupoDesenho) > 0){
            foreach($produtoGrupoDesenho as $desenho){
                $desenho->deleted_by = 1;
                $desenho->save();
                $desenho->delete();
            }
        }

        $produtoGrupoDesenhoObj = ProdutoGrupoDesenho::find($id);

        $imagem = 'imagem_'.$fields['codigo'];
        
        if(!empty($request->$imagem)){
            $produtoGrupoDesenhoObj->codigo_desenho = $fields['codigo'];
            $arquivo = $request->$imagem;

            $nome_arquivo = strtolower(str_replace(',', '', str_replace(' ', '_', $fields['codigo']))).rand(1,100).'.' . $arquivo->getClientOriginalExtension();
            $produtoGrupoDesenhoObj->imagem = $nome_arquivo;

            $arquivo->storeAs($this->path . 'desenhos/', $nome_arquivo);
        }

        $imagem_zoom = 'imagem_zoom_'.$fields['codigo'];

        if(!empty($request->$imagem_zoom)){
            $arquivo_zoom = $request->$imagem_zoom;

            $nome_arquivo_zoom = strtolower(str_replace(',', '', str_replace(' ', '_', $fields['codigo']))).rand(1,100).'.' . $arquivo_zoom->getClientOriginalExtension();
            $produtoGrupoDesenhoObj->imagem_zoom = $nome_arquivo_zoom;

            $arquivo_zoom->storeAs($this->path.'desenhos/', substr_replace($nome_arquivo_zoom,'_zoom.jpg', -4));
        }

        $produtoGrupoDesenhoObj->save();
        $produtoGrupoDesenhoObj->updated_by = Auth::id();

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'id' => $produtoGrupoDesenhoObj->id,
                'codigo_desenho' => $produtoGrupoDesenhoObj->codigo_desenho,
                'imagem' => Storage::exists($this->path . 'desenhos/'.$produtoGrupoDesenhoObj->imagem) ? Storage::url($this->path . 'desenhos/'.$produtoGrupoDesenhoObj->imagem) . '?' . time() : '',
                'imagem_zoom' => Storage::exists($this->path . 'desenhos/'.substr_replace($produtoGrupoDesenhoObj->imagem_zoom,'_zoom.jpg', -4)) ? Storage::url($this->path . 'desenhos/'.substr_replace($produtoGrupoDesenhoObj->imagem_zoom,'_zoom.jpg', -4)) . '?' . time() : ''
            ]
        ];

        return response()->json($retorno, 220);
    }

    public function excluirDesenho(Request $request){
        $fields = $request->only('id');

        $produtoGrupoDesenhoObj = ProdutoGrupoDesenho::find($fields['id']);
        $produtoGrupoDesenhoObj->deleted_by = Auth::id();
        $produtoGrupoDesenhoObj->save();
        $codigo_desenho = $produtoGrupoDesenhoObj->codigo_desenho;

        Storage::delete($this->path . 'desenhos/'.$produtoGrupoDesenhoObj->imagem);
        Storage::delete($this->path . 'desenhos/'.substr_replace($produtoGrupoDesenhoObj->imagem_zoom,'_zoom.jpg', -4));

        $produtoGrupoDesenhoObj->delete();

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'codigo_desenho' => $codigo_desenho
            ]
        ];

        return response()->json($retorno, 220);
        
    }
}
