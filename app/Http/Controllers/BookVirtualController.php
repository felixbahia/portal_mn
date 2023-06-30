<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Auth;

use App\BookVirtual;
use App\BookVirtualDesenho;
use App\Familia;
use App\ProdutoEspecificacao;
use App\ItensBookVirtual;
use App\Segmento;
use App\Composicao;
use App\Construcao;
use App\Sazonalidade;

use App\Http\Requests\BookVirtualNovoRequest;
use App\Http\Requests\BookVirtualEditarRequest;
use App\Http\Requests\BookVirtualSalvarDesenhoRequest;
use App\Http\Requests\BookVirtualEditarDesenhoRequest;
use App\Http\Requests\BookVirtualSalvarInstrucoesLavagemRequest;

class BookVirtualController extends Controller
{

    public $tipos_materiais = [
        'estampados' => 'Estampados',
        'fio_tinto' => 'Fio tinto',
        'lisos' => 'Lisos',
        'lisos_jacquard' => 'Liso Jacquard',
        'lisos_dobby' => 'Liso Dobby'
    ];

    public $padroes_codigo = [
        '7_11' => 'Artigo 7 + 5 desenho',
        '6_10' => 'Artigo 6 + 5 desenho',
        '4_9' => 'Artigo 4 + 6 desenho + 3 tamanho'
    ];

    public $path = 'public/book_virtual/';

    public $tipo_generos = [
        'masculino' => 'Masculino',
        'feminino' => 'Feminino',
        'ambos' => 'Ambos'
    ];

    public $tipo_gramatura = [
        'top' => 'Top',
        'bottom' => 'Bottom',
        'all_over' => 'All Over'
    ];

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\BookVirtual") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BookVirtual');
        $files = [];
        foreach (File::glob(storage_path() . '/app/public/book_virtual/*.*') as $file){
            $info = pathinfo(basename($file));
            $files[] = [
                'nome' => $info['filename'],
                'path' => $file,
                'url' => Storage::url('book_virtual/'.basename($file))
            ];
        }
        return view('programs.book_virtual.index')->with('files', $files);
    }

    public function cadastroIndex(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\BookVirtualCadastro") === false){
            return abort(403);
        }

        $retorno = [
            'tipo_material' => $this->tipos_materiais,
            'padroes_codigo' => $this->padroes_codigo,
            'segmentos' => Segmento::all()->pluck('descricao', 'id'),
            'generos' => $this->tipo_generos,
            'composicao' => Composicao::orderBy('descricao')->pluck('descricao', 'id'),
            'construcao' => Construcao::orderBy('descricao')->pluck('descricao', 'id'),
            'sazonalidade' => Sazonalidade::orderBy('descricao')->pluck('descricao', 'id'),
        ];

        $request->session()->flash('model', 'App\BookVirtualCadastro');
        return view('programs.book_virtual.cadastro.index')->with($retorno);
    }

    public function cadastroModalAdicionar() {
        
        $segmentos = Segmento::all()->pluck('descricao', 'id');
        $familia = Familia::orderBy('descricao', 'ASC')->pluck('descricao', 'id');
        $composicao = Composicao::orderBy('descricao')->pluck('descricao', 'id');
        $construcao = Construcao::orderBy('descricao')->pluck('descricao', 'id');
        $sazonalidade = Sazonalidade::orderBy('descricao')->pluck('descricao', 'id');

        return view('programs.book_virtual.cadastro.modal.adicionar')->with([
            'segmentos' => $segmentos, 
            'tipos_materiais' => $this->tipos_materiais,
            'padroes_codigo' => $this->padroes_codigo,
            'familia' => $familia,
            'tipo_generos' => $this->tipo_generos,
            'composicao' => $composicao,
            'construcao' => $construcao,
            'sazonalidade' => $sazonalidade,
            'tipo_gramatura' => $this->tipo_gramatura,
        ]);
    }

    public function cadastroModalEditar(Request $request) {
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
        
        $result_book_virtual = BookVirtual::with('itens_book_virtual', 'itens_book_virtual.produto', 'itens_book_virtual.item_foto', 'desenhos')
            ->where('id', $id)
            ->first();

        $imagem = Storage::url($result_book_virtual->img_instrucoes_lavagem) . '?' . time();        
        
        if(!empty($result_book_virtual->thumb_instrucoes_lavagem) && Storage::exists($result_book_virtual->thumb_instrucoes_lavagem)){
            $thumb_imagem = Storage::url($result_book_virtual->thumb_instrucoes_lavagem) . '?' . time();
        }
        else{
            $thumb_imagem = '';
        }

        if(!empty($result_book_virtual->imagem_tamanho_real) && Storage::exists($this->path . '/' . $result_book_virtual->id . '/desenhos/',  $result_book_virtual->imagem_tamanho_real)){
            $imagem_tamanho_real = '<a href="' . Storage::url($this->path . '/' . $result_book_virtual->id . '/desenhos/' . $result_book_virtual->imagem_tamanho_real) . '?' . time() . '" target="_blank"> Link para a imagem </a>';
        }
        else{
            $imagem_tamanho_real = '';
        }

        $book = [
            'num_book' => $result_book_virtual->num_book,
            'segmento' => $result_book_virtual->segmentos_id,
            'familia' => $result_book_virtual->familias_id,
            'tipo_material' => $result_book_virtual->tipo_material,
            'padrao_codigo' => $result_book_virtual->padrao_codigo,
            'origem' => $result_book_virtual->origem,
            'artigo' => $result_book_virtual->artigo,
            'nome' => $result_book_virtual->nome,
            'pecas' => $result_book_virtual->pecas,
            'img_instrucoes_lavagem' => $imagem,
            'thumb_instrucoes_lavagem' => $thumb_imagem,
            'imagem_tamanho_real' => $imagem_tamanho_real,
            'caracteristicas' => $result_book_virtual->caracteristicas,
            'tipo_genero' => $result_book_virtual->genero,
            'gramatura_tipo' => $result_book_virtual->gramatura_tipo,
            'composicao' => $result_book_virtual->composicaos_id,
            'construcao' => $result_book_virtual->construcaos_id,
            'sazonal' => $result_book_virtual->sazonalidades_id,
        ];

        if($book['tipo_material'] == 'estampados' && empty($book['padrao_codigo'])){
            $book['padrao_codigo'] = '7_11';

        }
        else if($book['tipo_material'] == 'fio_tinto' && empty($book['padrao_codigo'])){
            $book['padrao_codigo'] = '6_10';
        }
        else if($book['segmento'] == 'CONFECIONADOS' && empty($book['padrao_codigo'])){
            $book['padrao_codigo'] = '4_9';
        }

        $desenhos = collect([]);

        $result_book_virtual->desenhos->each(function ($desenho) use (&$desenhos, $result_book_virtual){
            if(Storage::exists($this->path . $result_book_virtual->id . '/desenhos/' .  $desenho->imagem)){
                $linha = [];
    
                $linha['id'] = $desenho->id;
                $linha['codigo_desenho'] = $desenho->codigo_desenho;
                $linha['imagem'] = Storage::url($this->path . $result_book_virtual->id . '/desenhos/' .  $desenho->imagem);
    
                $desenhos->push($linha);
            }
        });

        $result = $result_book_virtual->itens_book_virtual;

        $dados = [];
        $itens = [];
        foreach($result as $value){
            $itens[] = $value->cod_produto;

            if(isset($value->item_foto) && Storage::exists($this->path . $value->item_foto->filename)){
                $foto = "<a data-toggle=\"popover\" data-trigger='hover' data-original-title='Foto' data-content=\"<img src='" . Storage::url($this->path . $value->item_foto->thumb_filename) . "' />\" href=\"" . Storage::url('public/produto_fotos/' . $value->item_foto->filename) . "\" class=\"btn-foto thumb ml-2 mt-1\"></a>"; 
            }
            else{
                $foto = "";
            }

            if($desenhos->pluck('codigo_desenho')->search(substr($value->cod_produto, 7, 5)) === false && $desenhos->pluck('codigo_desenho')->search(substr($value->cod_produto, 4, 6)) === false && ($result_book_virtual->padrao_codigo == '7_11' || (is_null($result_book_virtual->padrao_codigo) && $result_book_virtual->tipo_material == 'estampados'))){
                $desenhos->push(['codigo_desenho' => substr($value->cod_produto, 7, 5)]);
            }
            else if($desenhos->pluck('codigo_desenho')->search(substr($value->cod_produto, 6, 5)) === false && $desenhos->pluck('codigo_desenho')->search(substr($value->cod_produto, 4, 6)) === false && ($result_book_virtual->padrao_codigo == '6_10' || (is_null($result_book_virtual->padrao_codigo) && $result_book_virtual->tipo_material == 'fio_tinto'))){
                $desenhos->push(['codigo_desenho' => substr($value->cod_produto, 6, 5)]);
            }
            else if($desenhos->pluck('codigo_desenho')->search(substr($value->cod_produto, 4, 6)) === false && $desenhos->pluck('codigo_desenho')->search(substr($value->cod_produto, 7, 5)) === false && ($result_book_virtual->padrao_codigo == '4_9' || (is_null($result_book_virtual->padrao_codigo) && $result_book_virtual->segmento == 'CONFECIONADO'))){
                $desenhos->push(['codigo_desenho' => substr($value->cod_produto, 4, 6)]);
            }

            

            $dados []= [
                'foto' => $foto,
                'codigo' => $value->cod_produto,
                'descricao' => $value->produto->descricao,
                'grupo' => $value->produto->grupo,
                'marca' => $value->produto->marca,
                'linha' => $value->produto->linha
            ];
        }
        if(!empty($itens)){
            $itens = encrypt($itens);
        }
        else{
            $itens = '';
        }

        $id = encrypt($id);

        $segmentos = Segmento::all()->pluck('descricao', 'id');
        $familia = Familia::orderBy('descricao', 'ASC')->pluck('descricao', 'id');
        $composicao = Composicao::orderBy('descricao')->pluck('descricao', 'id');
        $construcao = Construcao::orderBy('descricao')->pluck('descricao', 'id');
        $sazonalidade = Sazonalidade::orderBy('descricao')->pluck('descricao', 'id');

        return view('programs.book_virtual.cadastro.modal.editar')->with([
            'dados' => $dados, 
            'itens' => $itens, 
            'book' => $book, 
            'id'=>$id, 
            'segmentos' => $segmentos, 
            'tipos_materiais' => $this->tipos_materiais, 
            'padroes_codigo' => $this->padroes_codigo, 
            'desenhos' => $desenhos,
            'familia' => $familia,
            'tipo_generos' => $this->tipo_generos,
            'composicao' => $composicao,
            'construcao' => $construcao,
            'sazonalidade' => $sazonalidade,
            'tipo_gramatura' => $this->tipo_gramatura,
        ]);
    }

    public function cadastroModalDeletar(Request $request) {
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
        $query_book = BookVirtual::select();
        $query_book->where('id', $id);
        $result_book = $query_book->first();


        $query = ItensBookVirtual::select();
        $query->with(['produto']);
        $query->where('num_book', $result_book->num_book);
        $result = $query->get();
        $produtos = [];

        foreach($result as $value){
            $produtos []= [
                'produto' => $value->cod_produto,
                'descricao' => $value->produto->descricao
            ];
        }
        $book = $result_book->num_book;

        return view('programs.book_virtual.cadastro.modal.deletar')->with(['book' => $book, 'produtos' => $produtos]);
    }

    public function cadastroAdicionar(BookVirtualNovoRequest $request) {
        
        $fields = $request->only('book_virtual', 'artigo', 'nome_artigo', 'pecas', 'padrao_codigo', 'tipo_genero');

        $bookVirtualObj = new BookVirtual;
        $bookVirtualObj->num_book = $fields['book_virtual'];
        $bookVirtualObj->padrao_codigo = $fields['padrao_codigo'];
        $bookVirtualObj->artigo = $fields['artigo'];
        $bookVirtualObj->nome = $fields['nome_artigo'];
        $bookVirtualObj->pecas = $fields['pecas'];
        $bookVirtualObj->created_by = Auth::id();

        $bookVirtualObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'id' => encrypt($bookVirtualObj->id)
            ]
        ];
        return response()->json($response);
    }

    public function cadastroEditar(BookVirtualEditarRequest $request) {

        $fields = $request->only('id', 'itens','book_virtual', 'tipo_material','artigo','nome_artigo','pecas','icon_temp','instrucoes_lavagem', 'codigo_desenho', 'padrao_codigo', 'gramatura_tipo');

        $itens = decrypt($fields['itens']);
        $id = decrypt($fields['id']);
        
        $desenho_nao_cadastrado = [];
        $desenhos = [];

        $bookVirtualObj = BookVirtual::with('desenhos')->find($id);

        if($fields['padrao_codigo'] != $bookVirtualObj->padrao_codigo && !empty($bookVirtualObj->padrao_codigo)){
            
            $cadastrado = [];
            $duplicado = [];

            if($fields['padrao_codigo'] == '6_10'){

                collect($itens)->each(function($item) use (&$bookVirtualObj, &$cadastrado, &$duplicado){

                    if(!in_array(substr($item, 6, 5), $cadastrado)){

                        if($bookVirtualObj->padrao_codigo == '7_11'){
                            $desenhoObj = $bookVirtualObj->desenhos->firstWhere('codigo_desenho', substr($item, 7, 5));
                        }
                        else if($bookVirtualObj->padrao_codigo == '4_9'){
                            $desenhoObj = $bookVirtualObj->desenhos->firstWhere('codigo_desenho', substr($item, 4, 6));
                        }

                        if(!is_null($desenhoObj) ){
                            $desenhoObj->codigo_desenho = substr($item, 6, 5);
                            $desenhoObj->updated_by = Auth::id();

                            $extensao = pathinfo($this->path . '/' . $bookVirtualObj->id . '/desenhos/' .  $desenhoObj->imagem, PATHINFO_EXTENSION);

                            if(
                                Storage::exists($this->path . '/' . $bookVirtualObj->id . '/desenhos/' .  $desenhoObj->imagem) && 
                                !Storage::exists($this->path . '/' . $bookVirtualObj->id . '/desenhos/' . $desenhoObj->codigo_desenho . '.' . $extensao)
                            ){
                                Storage::move($this->path . '/' . $bookVirtualObj->id . '/desenhos/' .  $desenhoObj->imagem, $this->path . '/' . $bookVirtualObj->id . '/desenhos/' . $desenhoObj->codigo_desenho . '.' . $extensao);
                            }

                            $desenhoObj->imagem = $desenhoObj->codigo_desenho . '.' . $extensao;
                            $desenhoObj->save();

                            $cadastrado[] = substr($item, 6, 5);
                        }
                    }
                });
            }
            else if($fields['padrao_codigo'] == '7_11'){
                collect($itens)->each(function($item) use (&$bookVirtualObj, &$cadastrado, &$duplicado){
                    if(!in_array(substr($item, 7, 5), $cadastrado)){
                        if($bookVirtualObj->padrao_codigo == '6_10'){
                            $desenhoObj = $bookVirtualObj->desenhos->firstWhere('codigo_desenho', substr($item, 6, 5));
                        }
                        else if($bookVirtualObj->padrao_codigo == '4_9'){
                            $desenhoObj = $bookVirtualObj->desenhos->firstWhere('codigo_desenho', substr($item, 4, 6));
                        }

                        if(!is_null($desenhoObj)){
                            $desenhoObj->codigo_desenho = substr($item, 7, 5);
                            $desenhoObj->updated_by = Auth::id();
                            $desenhoObj->save();

                            $extensao = pathinfo($this->path . '/' . $bookVirtualObj->id . '/desenhos/' .  $desenhoObj->imagem, PATHINFO_EXTENSION);

                            if(
                                Storage::exists($this->path . '/' . $bookVirtualObj->id . '/desenhos/' .  $desenhoObj->imagem) && 
                                !Storage::exists($this->path . '/' . $bookVirtualObj->id . '/desenhos/' . $desenhoObj->codigo_desenho . '.' . $extensao)
                            ){
                                Storage::move($this->path . '/' . $bookVirtualObj->id . '/desenhos/' .  $desenhoObj->imagem, $this->path . '/' . $bookVirtualObj->id . '/desenhos/' . $desenhoObj->codigo_desenho . '.' . $extensao);
                            }

                            $desenhoObj->imagem = $desenhoObj->codigo_desenho . '.' . $extensao;
                            $desenhoObj->save();

                            $cadastrado[] = substr($item, 7, 5);
                        }
                    }
                });
            }
            else if($fields['padrao_codigo'] == '4_9'){
                collect($itens)->each(function($item) use (&$bookVirtualObj, &$cadastrado, &$duplicado){
                    if(!in_array(substr($item, 4, 6), $cadastrado)){
                        if($bookVirtualObj->padrao_codigo == '6_10'){
                            $desenhoObj = $bookVirtualObj->desenhos->firstWhere('codigo_desenho', substr($item, 6, 5));
                        }
                        else if($bookVirtualObj->padrao_codigo == '7_11'){
                            $desenhoObj = $bookVirtualObj->desenhos->firstWhere('codigo_desenho', substr($item, 7, 5));
                        }

                        if(!is_null($desenhoObj)){
                            $desenhoObj->codigo_desenho = substr($item, 4, 6);
                            $desenhoObj->updated_by = Auth::id();
                            $desenhoObj->save();

                            $extensao = pathinfo($this->path . '/' . $bookVirtualObj->id . '/desenhos/' .  $desenhoObj->imagem, PATHINFO_EXTENSION);

                            if(
                                Storage::exists($this->path . '/' . $bookVirtualObj->id . '/desenhos/' .  $desenhoObj->imagem) && 
                                !Storage::exists($this->path . '/' . $bookVirtualObj->id . '/desenhos/' . $desenhoObj->codigo_desenho . '.' . $extensao)
                            ){
                                Storage::move($this->path . '/' . $bookVirtualObj->id . '/desenhos/' .  $desenhoObj->imagem, $this->path . '/' . $bookVirtualObj->id . '/desenhos/' . $desenhoObj->codigo_desenho . '.' . $extensao);
                            }

                            $desenhoObj->imagem = $desenhoObj->codigo_desenho . '.' . $extensao;
                            $desenhoObj->save();

                            $cadastrado[] = substr($item, 4, 6);
                        }
                    }
                });
            }
        }

        if($fields['padrao_codigo'] == '7_11'){
            $desenhos = collect($itens)->map(function($item){
                return substr($item, 7, 5);
            })->toArray();
        }
        else if($fields['padrao_codigo'] == '6_10'){
            $desenhos = collect($itens)->map(function($item){
                return substr($item, 6, 5);
            })->toArray();
        }
        else if($fields['padrao_codigo'] == '4_9'){
            $desenhos = collect($itens)->map(function($item){
                return substr($item, 4, 6);
            })->toArray();
        }

        foreach($desenhos as $desenho){
            $desenhoObj = BookVirtualDesenho::where('books_virtuals_id', $id)->where('codigo_desenho', $desenho)->first();

            if(is_null($desenhoObj) || !Storage::exists($this->path . '/' . $id . '/desenhos/' .$desenhoObj->imagem_tamanho_real)){
                $desenho_nao_cadastrado[] = $desenho;
            }
        }

        if(count($desenho_nao_cadastrado) > 0){
            return response()->json([
                'status' => 'error',
                'message' => 'Desenho não cadastrado',
                'error' => ['desenhos' => $desenho_nao_cadastrado],
                'response' => []
            ], 422);
        }

        $book = BookVirtual::select()->where('id', $id)->first()->num_book;

        $itensBookVirtualObj = ItensBookVirtual::select();
        $itensBookVirtualObj->where('num_book', $book);
        $itensBookVirtualObj->deleted_by = Auth::id();
        $itensBookVirtualObj->delete();

        $desenhos = [];

        $bookVirtualObj->padrao_codigo = $fields['padrao_codigo'];

        foreach($itens as $item){
            $itensBookVirtualObj2 = new ItensBookVirtual;
            $itensBookVirtualObj2->num_book = $fields['book_virtual'];
            $itensBookVirtualObj2->cod_produto = $item;
            $itensBookVirtualObj2->created_by = Auth::id();
            $itensBookVirtualObj2->save();
            
            $itensBookVirtualObj2->produto->pecas = $fields['pecas'];
            $itensBookVirtualObj2->produto->padrao_codigo = $fields['padrao_codigo'];
            $itensBookVirtualObj2->produto->save();
            

            if( 
                $bookVirtualObj->padrao_codigo == '7_11' ||
                (is_null($bookVirtualObj->padrao_codigo) && $bookVirtualObj->tipo_material == 'estampados')
            ){
                $desenhos[] = substr($itensBookVirtualObj2->cod_produto, 7, 5);
            }
            else if(
                $bookVirtualObj->padrao_codigo == '6_10' ||
                (is_null($bookVirtualObj->padrao_codigo) && $bookVirtualObj->tipo_material == 'fio_tinto')
            ){
                $desenhos[] = substr($itensBookVirtualObj2->cod_produto, 6, 5);
            }
            else if(
                $bookVirtualObj->padrao_codigo == '4_9' ||
                (is_null($bookVirtualObj->padrao_codigo) && $bookVirtualObj->tipo_material == 'fio_tinto')
            ){
                $desenhos[] = substr($itensBookVirtualObj2->cod_produto, 4, 6);
            }
        }

        $bookVirtualObj->num_book = $fields['book_virtual'];
        $bookVirtualObj->artigo = $fields['artigo'];
        $bookVirtualObj->nome = $fields['nome_artigo'];
        $bookVirtualObj->pecas = $fields['pecas'];
        $bookVirtualObj->updated_by = Auth::id();

        $bookVirtualObj->save();

        $bookVirtualObj->load('desenhos');

        if(!is_null($bookVirtualObj->padrao_codigo) || (is_null($bookVirtualObj->padrao_codigo) && in_array($bookVirtualObj->tipo_material, ['estampados', 'fio_tinto']))){
            foreach(array_diff($bookVirtualObj->desenhos->pluck('codigo_desenho')->toArray(), $desenhos) as $desenho){
                $this->excluirDesenho($desenho, $bookVirtualObj->id);
            }

            $bookVirtualObj->imagem_tamanho_real = null;
            $bookVirtualObj->save();

            Storage::delete($this->path . '/' . $bookVirtualObj->id . '/desenhos/' . $bookVirtualObj->imagem_tamanho_real);

        }
        else{
            foreach($bookVirtualObj->desenhos->pluck('codigo_desenho')->toArray() as $desenho){
                $this->excluirDesenho($desenho, $bookVirtualObj->id);
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

    public function cadastroDeletar(Request $request) {
        $book = $request->only(['id'])['id'];

        $itensBookVirtualObj = ItensBookVirtual::select();
        $itensBookVirtualObj->where('num_book', $book);
        $itensBookVirtualObj->deleted_by = Auth::id();
        $itensBookVirtualObj->delete();

        $bookVirtualObj = BookVirtual::select();
        $bookVirtualObj->where('num_book', $book);
        $bookVirtualObj->deleted_by = Auth::id();
        $bookVirtualObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function cadastroFilter(Request $request){
        $fields = $request->only('book_virtual', 'segmentos_id', 'tipo_material', 'artigo', 'nome', 'generos', 'composicao', 'construcao', 'sazonalidade');

        $query = BookVirtual::select();
        if(isset($fields['book_virtual']) && !empty($fields['book_virtual'])){
            $query->where('num_book', $fields['book_virtual']);
        }

        if(isset($fields['segmentos_id']) && !empty($fields['segmentos_id'])){
            $query->where('segmentos_id', $fields['segmentos_id']);
        }

        if(isset($fields['tipo_material']) && !empty($fields['tipo_material'])){
            $query->where('tipo_material', $fields['tipo_material']);
        }

        if(isset($fields['artigo']) && !empty($fields['artigo'])){
            $query->where('artigo', 'ilike', '%' . $fields['artigo'] . '%');

        }

        if(isset($fields['nome']) && !empty($fields['nome'])){
            $query->where('nome', 'ilike', '%' . $fields['nome'] . '%');

        }

        if(isset($fields['generos']) && !empty($fields['generos'])){
            $query->where('genero', 'ilike', '%' . $fields['generos'] . '%');
        }

        if(isset($fields['composicao']) && !empty($fields['composicao'])){
            $query->where('composicaos_id', $fields['composicao']);
        }

        if(isset($fields['construcao']) && !empty($fields['construcao'])){
            $query->where('construcaos_id', $fields['construcao']);
        }

        if(isset($fields['sazonalidade']) && !empty($fields['sazonalidade'])){
            $query->where('sazonalidades_id', $fields['sazonalidade']);
        }

        $result = $query->get();

        $book = [];
        foreach($result as $value){
            $book []= [
                'book' => $value->num_book,
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
        $book = $request->only(['id'])['id'];
        $query = ItensBookVirtual::select();
        $query->with(['produto' => function($query){
            $query->select('codigo_produto', 'descricao');
        }]);
        $query->where('num_book', $book);
        $result = $query->get();
        foreach($result as $value_prod){
            $produto[]= [
                'codigo' => $value_prod->cod_produto,
                'descricao' => $value_prod->produto->descricao
            ];
        }
        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $produto
        ];
        return response()->json($retorno);
    }

    public function adicionarProduto(Request $request){
        $fields = $request->only('cod_produto', 'item');
        if(!empty($fields['item'])){
            try{
                $itens = decrypt($fields['item']);
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados não encontrados',
                    'error' => [],
                    'response' => []
                ]);
            }
            foreach($itens as $item){
                if(strcmp($item, $fields['cod_produto']) == 0){
                    $retorno = [
                        'status' => 'sucess',
                        'message' => '',
                        'error' => ['descricao' => 'Produto já adicionado'],
                        'response' => ''
                    ];
                    return response()->json($retorno,422);
                }
            }
        }else{
            $itens = [];
        }

        $query = ProdutoEspecificacao::with('foto');
        $query->where('codigo_produto', $fields['cod_produto']);
        $result = $query->first();

        $itens[] = $result->codigo_produto;

        if(isset($result->foto) && Storage::exists('public/produto_fotos/' . $result->foto->filename)){
            $foto = "<a data-toggle=\"popover\" data-trigger='hover' data-original-title='Foto' data-content=\"<img src='" . Storage::url('public/produto_fotos/' . $result->foto->thumb_filename) . "' />\" href=\"" . Storage::url('public/produto_fotos/' . $result->foto->filename) . "\" class=\"btn-foto thumb ml-2 mt-1\"></a>"; 
        }
        else{
            $foto = "";
        }

        $produto = [
            'foto' => $foto,
            'codigo' => $result->codigo_produto,
            'descricao' => $result->descricao,
            'grupo' => $result->grupo,
            'marca' => $result->marca,
            'linha' => $result->linha,
            'itens' => encrypt($itens),
            'deletar' => encrypt($result->codigo_produto)
        ];

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $produto
        ];
        return response()->json($retorno);
    }

    public function deletarProduto(Request $request){
        $fields = $request->only('codigo', 'item');
        try{
            $itens = decrypt($fields['item']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $remover = array($fields['codigo']);
        $resultado = array_diff($itens, $remover);
        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $resultado?encrypt($resultado):''
        ];
        return response()->json($retorno);
    }

    public function getItensBookVirtual($book_virtual){
        $query = ItensBookVirtual::select('cod_produto');
        $query->where('num_book', $book_virtual);
        $query->orderBy('num_book');
        $result = $query->get()->toArray();
        return $result;
    }

    public function dadosBookVirtual($book_virtual){
        $query = BookVirtual::select();
        $query->where('num_book', $book_virtual);
        $query->orderBy('num_book');
        $result_book_virtual = $query->first();

        $imagem = Storage::url($result_book_virtual->img_instrucoes_lavagem);

        if(Storage::exists($result_book_virtual->thumb_instrucoes_lavagem)){
            $imagem_thumb = Storage::url($result_book_virtual->thumb_instrucoes_lavagem);
        }
        else{
            $imagem_thumb = Storage::url($result_book_virtual->img_instrucoes_lavagem);

        }

        if(!empty($result_book_virtual->imagem_tamanho_real) && Storage::exists('public/book_virtual/' . $result_book_virtual->imagem_tamanho_real)){
            $imagem_tamanho_real = Storage::url('public/book_virtual/' . $result_book_virtual->imagem_tamanho_real) . '?' . time();
        }
        else{
            $imagem_tamanho_real = '';
        }

        $book = [
            'num_book' => strtoupper($result_book_virtual->num_book),
            'origem' => strtoupper($result_book_virtual->origem),
            'artigo' => strtoupper($result_book_virtual->artigo),
            'nome' => strtoupper($result_book_virtual->nome),
            'pecas' => strtoupper($result_book_virtual->pecas),
            'thumb_instrucoes_lavagem' => $imagem_thumb,
            'img_instrucoes_lavagem' => $imagem,
            'caracteristicas' => strtoupper($result_book_virtual->caracteristicas),
            'imagem_tamanho_real' => $imagem_tamanho_real
        ];
        return $book;
    }

    public function salvarDesenho(BookVirtualSalvarDesenhoRequest $request){

        ini_set('upload_max_filesize', '2M');

        $fields = $request->only('id', 'codigo_desenho');

        $id = decrypt($fields['id']);

        $bookVirtualDesenhoObj = new BookVirtualDesenho;

        $bookVirtualDesenhoObj->codigo_desenho = $fields['codigo_desenho'];
        $bookVirtualDesenhoObj->books_virtuals_id = $id;
        $bookVirtualDesenhoObj->imagem = $bookVirtualDesenhoObj->codigo_desenho . '.' . $request->file('imagem')->getClientOriginalExtension();
        $request->file('imagem')->storeAs($this->path . '/' . $bookVirtualDesenhoObj->books_virtuals_id . '/desenhos/',  $bookVirtualDesenhoObj->imagem);
        $bookVirtualDesenhoObj->created_by = Auth::id();
        
        $bookVirtualDesenhoObj->save();

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'id' => $bookVirtualDesenhoObj->id,
                'codigo_desenho' => $bookVirtualDesenhoObj->codigo_desenho,
                'imagem' => Storage::url($this->path . '/' . $bookVirtualDesenhoObj->books_virtuals_id . '/desenhos/' .  $bookVirtualDesenhoObj->imagem) . '?' . time()
            ]
        ];

        return response()->json($retorno, 220);
    }

    public function editarDesenho(BookVirtualEditarDesenhoRequest $request){

        ini_set('upload_max_filesize', '2M');

        $fields = $request->only('id', 'codigo_desenho');

        $id = $fields['id'];

        $bookVirtualDesenhoObj = BookVirtualDesenho::find($id);

        $bookVirtualDesenhoObj->imagem = $bookVirtualDesenhoObj->codigo_desenho . '.' . $request->file('imagem')->getClientOriginalExtension();
        $request->file('imagem')->storeAs($this->path . '/' . $bookVirtualDesenhoObj->books_virtuals_id . '/desenhos/',  $bookVirtualDesenhoObj->imagem);
        $bookVirtualDesenhoObj->updated_by = Auth::id();
        
        $bookVirtualDesenhoObj->save();

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'id' => $bookVirtualDesenhoObj->id,
                'codigo_desenho' => $bookVirtualDesenhoObj->codigo_desenho,
                'imagem' => Storage::url($this->path . '/' . $bookVirtualDesenhoObj->books_virtuals_id . '/desenhos/' .  $bookVirtualDesenhoObj->imagem) . '?' . time()
            ]
        ];

        return response()->json($retorno, 220);
    }

    private function excluirDesenho($codigo_desenho, $books_virtuals_id){

        $bookVirtualDesenhoObj = BookVirtualDesenho::
            where('books_virtuals_id', $books_virtuals_id)
            ->where('codigo_desenho', $codigo_desenho)
            ->first();

        $bookVirtualDesenhoObj->deleted_by = Auth::id();
        $bookVirtualDesenhoObj->save();

        Storage::delete($this->path . '/' . $bookVirtualDesenhoObj->books_virtuals_id . '/desenhos/' .  $bookVirtualDesenhoObj->imagem);

        $bookVirtualDesenhoObj->delete();

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => []
        ];

        return response()->json($retorno, 220);
        
    }

    public function salvarInstrucoesLavagem(BookVirtualSalvarInstrucoesLavagemRequest $request){

        ini_set('memory_limit', '2M');
        ini_set('post_max_size', '2M');
        ini_set('upload_max_filesize', '2M');

        $fields = $request->only('id');
        $id = decrypt($fields['id']);

        $bookVirtualObj = BookVirtual::find($id);

        $image = imagecreatefromstring(file_get_contents($request->file('instrucoes_lavagem')));

        ob_start();
        imagejpeg($image);
        $icon = ob_get_contents();
        ob_end_clean();

        $thumb_image = imagecreatefromstring(file_get_contents($request->file('instrucoes_lavagem')));
        $thumb_image = imagescale($thumb_image, 150);

        ob_start();
        imagejpeg($thumb_image);
        $thumb_icon = ob_get_contents();
        ob_end_clean();

        $name = $bookVirtualObj->num_book .'.jpg';
        $path_file = $this->path . $bookVirtualObj->id . '/instrucoes_lavagem/' . $name;
        $thumb_path_file = $this->path . $bookVirtualObj->id . '/instrucoes_lavagem/thumb_' . $name;
        Storage::put($path_file, $icon);
        Storage::put($thumb_path_file, $thumb_icon);

        $bookVirtualObj->img_instrucoes_lavagem = $path_file;
        $bookVirtualObj->thumb_instrucoes_lavagem = $thumb_path_file;

        $bookVirtualObj->save();

        $thumb_instrucoes_lavagem = Storage::url($bookVirtualObj->thumb_instrucoes_lavagem) . '?' . time();
        $img_instrucoes_lavagem = Storage::url($bookVirtualObj->img_instrucoes_lavagem) . '?' . time();;

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'thumb_instrucoes_lavagem' => $thumb_instrucoes_lavagem,
                'img_instrucoes_lavagem' => $img_instrucoes_lavagem
            ]
        ];

        return response()->json($retorno, 220);
    }

    public function salvarImagemTamanhoReal(Request $request){
        
        ini_set('memory_limit', '2M');
        ini_set('post_max_size', '2M');
        ini_set('upload_max_filesize', '2M');

        $fields = $request->only('id');
        $id = decrypt($fields['id']);

        $bookVirtualObj = BookVirtual::find($id);

        $bookVirtualObj->imagem_tamanho_real = $bookVirtualObj->id . '.' . $request->file('imagem_tamanho_real')->getClientOriginalExtension();
        $bookVirtualObj->updated_by = Auth::id();

        $bookVirtualObj->save();

        $request->file('imagem_tamanho_real')->storeAs($this->path . '/' . $bookVirtualObj->id . '/desenhos/',  $bookVirtualObj->imagem_tamanho_real);

        $link = '<a href="' . Storage::url($this->path . '/' . $bookVirtualObj->id . '/desenhos/' . $bookVirtualObj->imagem_tamanho_real) . '?' . time() . '" target="_blank"> Link para a imagem </a>';

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'link' => $link,
            ]
        ];

        return response()->json($retorno, 220);
    }
}
