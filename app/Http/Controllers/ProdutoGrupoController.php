<?php

namespace App\Http\Controllers;

use App\Composicao;
use App\Construcao;
use App\Familia;
use App\Http\Requests\ProdutoGrupoAdicionarRequest;
use Auth;

use App\ProdutoGrupo;
use App\ProdutosEstoque;

use Illuminate\Http\Request;

use App\ProdutoEspecificacao;
use App\Http\Requests\ProdutoGrupoEditarRequest;
use App\Sazonalidade;
use App\Segmento;
use Illuminate\Support\Facades\Storage;

class ProdutoGrupoController extends Controller
{
   
    public $padroes_codigo = [
        '7_11' => 'Artigo 7 + 5 desenho',
        '6_10' => 'Artigo 6 + 5 desenho',
        '4_9' => 'Artigo 4 + 6 desenho + 3 tamanho'
    ];

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

    public $storage = 'public/grupo/';

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ProdutoGrupo") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ProdutoGrupo');

        return view('programs.produto.grupo.index');
    }

    public function modalAdicionar() {
        $segmentos = Segmento::all()->pluck('descricao', 'id');
        $familias = Familia::orderBy('descricao', 'ASC')->pluck('descricao', 'id');
        $composicaos = Composicao::orderBy('descricao')->pluck('descricao', 'id');
        $construcaos = Construcao::orderBy('descricao')->pluck('descricao', 'id');
        $sazonalidades = Sazonalidade::orderBy('descricao')->pluck('descricao', 'id');
  
        return view('programs.produto.grupo.modal.adicionar')->with([
            'segmentos' => $segmentos, 
            'padroes_codigo' => $this->padroes_codigo,
            'familias' => $familias,
            'tipo_generos' => $this->tipo_generos,
            'composicaos' => $composicaos,
            'construcaos' => $construcaos,
            'sazonalidades' => $sazonalidades,
            'tipo_gramatura' => $this->tipo_gramatura,
        ]);
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
            ],422);
        }

        $query = ProdutoGrupo::select();
        $query->where('id', '=', $id);
        if(is_null($query)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ], 422);
        }
        $result = $query->first();

        $segmentos = Segmento::all()->pluck('descricao', 'id');
        $familias = Familia::orderBy('descricao', 'ASC')->pluck('descricao', 'id');
        $composicaos = Composicao::orderBy('descricao')->pluck('descricao', 'id');
        $construcaos = Construcao::orderBy('descricao')->pluck('descricao', 'id');
        $sazonalidades = Sazonalidade::orderBy('descricao')->pluck('descricao', 'id');
        
        $imagem = Storage::url($this->storage.$result->imagem);

        $dados = [
            'id' => encrypt($result->id),
            'grupo' => $result->descricao,
            'largura' => $result->largura,
            'gramatura_gm2' => $result->gramatura_gm2,
            'gramatura_gml' => $result->gramatura_gml,
            'rendimento' => $result->rendimento,
            'segmentos_id' => $result->segmentos_id,
            'familias_id' => $result->familias_id,
            'pecas' => $result->pecas,
            'caracteristicas' => $result->caracteristicas,
            'origem' => $result->origem,
            'padrao_codigo' => $result->padrao_codigo,
            'tipo_genero' => $result->tipo_genero,
            'gramatura_tipo' => $result->gramatura_tipo,
            'composicaos_id' => $result->composicaos_id,
            'construcaos_id' => $result->construcaos_id,
            'construcao' => $result->construcao,
            'sazonalidades_id' => $result->sazonalidades_id,
            'imagem' => $imagem == Storage::url($this->storage) ? '' : $imagem,
            'encolhimento' => $result->encolhimento,
            'titulo_trama'  => $result->titulo_trama,
            'titulo_urdume'  => $result->titulo_urdume,
            'informacao_adicional'  => $result->informacao_adicional,
            'ligamento'  => $result->ligamento,
            'construcao'  => $result->construcao

        ];
        return view('programs.produto.grupo.modal.editar')->with([
            'dados' => $dados,
            'tipo_gramatura' => $this->tipo_gramatura,
            'tipo_generos' => $this->tipo_generos,
            'padroes_codigo' => $this->padroes_codigo,
            'segmentos' => $segmentos,
            'familias' => $familias,
            'composicaos' => $composicaos,
            'construcaos' => $construcaos,
            'sazonalidades' => $sazonalidades
        ]);
    }

    public function adicionar(ProdutoGrupoAdicionarRequest $request) {
        $fields = $request->only(
            'grupo',
            'largura',
            'gramatura_gm2',
            'gramatura_gml',
            'rendimento',
            'segmento',
            'familia',
            'pecas',
            'caracteristicas',
            'origem',
            'padrao_codigo',
            'tipo_genero',
            'gramatura_tipo',
            'composicao',
            'construcao_id',
            'construcao',
            'sazonal',
            'imagem',
            'encolhimento',
            'titulo_trama',
            'titulo_urdume',
            'informacao_adicional',
            'ligamento',
            'construcao'
            
        );
        $grupo = strtoupper($fields['grupo']);
        
        if(!empty($fields['imagem'])){
            $arquivo = $fields['imagem'];
            $nome_arquivo = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/', "\t"], '', str_replace(' ', '_', $grupo))).".".$arquivo->getClientOriginalExtension();
            $arquivo->storeAs($this->storage, $nome_arquivo);
        }else{
            $nome_arquivo = null;
        }

        $grupoObj = new ProdutoGrupo;
        $grupoObj->descricao = $grupo;
        $grupoObj->largura = $fields['largura'];
        $grupoObj->gramatura_gm2 = $fields['gramatura_gm2'];
        $grupoObj->gramatura_gml = $fields['gramatura_gml'];
        $grupoObj->rendimento = $fields['rendimento'];
        $grupoObj->origem = strtoupper($fields['origem']);
        $grupoObj->pecas = $fields['pecas'];
        $grupoObj->caracteristicas = $fields['caracteristicas'];
        $grupoObj->segmentos_id = $fields['segmento'];
        $grupoObj->padrao_codigo = $fields['padrao_codigo'];
        $grupoObj->familias_id = $fields['familia'];
        $grupoObj->tipo_genero = $fields['tipo_genero'];
        $grupoObj->gramatura_tipo = $fields['gramatura_tipo'];
        $grupoObj->composicaos_id = $fields['composicao'];
        $grupoObj->construcaos_id = $fields['construcao_id'];
        $grupoObj->construcao = $fields['construcao'];
        $grupoObj->sazonalidades_id = $fields['sazonal'];

        if(!empty($fields['encolhimento']) ||
           !empty($fields['titulo_trama']) ||
           !empty($fields['titulo_urdume']) ||
           !empty($fields['informacao_adicional']) ||
           !empty($fields['ligamento']) ||
           !empty($fields['construcao'])
        ){
            $grupoObj->status = true;
            $grupoObj->encolhimento = $fields['encolhimento'];
            $grupoObj->titulo_trama = $fields['titulo_trama'];
            $grupoObj->titulo_urdume = $fields['titulo_urdume'];
            $grupoObj->informacao_adicional = $fields['informacao_adicional'];   
            $grupoObj->ligamento = $fields['ligamento'];       
            $grupoObj->construcao = $fields['construcao'];
        }
      
        if(!empty($nome_arquivo)){
            $grupoObj->imagem = $nome_arquivo;
        }
        
        $grupoObj->created_by = Auth::id();
        $grupoObj->save();
        $grupos_id =$grupoObj->id;

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response,200);
    }

    public function editar(ProdutoGrupoEditarRequest $request){
        $fields = $request->only(
            'id',
            'grupo',
            'largura',
            'gramatura_gm2',
            'gramatura_gml',
            'rendimento',
            'segmento',
            'familia',
            'pecas',
            'caracteristicas',
            'origem',
            'padrao_codigo',
            'tipo_genero',
            'gramatura_tipo',
            'composicao',
            'construcao_id',
            'construcao',
            'sazonal',
            'imagem',
            'encolhimento',
            'titulo_trama',
            'titulo_urdume',
            'informacao_adicional',
            'ligamento',
            'construcao'
        );

        $grupo = strtoupper($fields['grupo']);
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

        foreach($grupoObj->produtoEspecificacao as $produto){
            $produto->grupo = $grupo;
            $produto->origem = $fields['origem'];
            $produto->pecas = $fields['pecas'];
            $produto->caracteristicas = $fields['caracteristicas'];
            $produto->segmentos_id = $fields['segmento'];
            $produto->padrao_codigo = $fields['padrao_codigo'];
            $produto->familias_id = $fields['familia'];
            $produto->gramatura_tipo = $fields['gramatura_tipo'];
            $produto->composicaos_id = $fields['composicao'];
            $produto->construcaos_id = $fields['construcao_id'];
            $produto->sazonalidades_id = $fields['sazonal'];
            $produto->save();
        }

        if(!empty($fields['imagem'])){
            $arquivo = $fields['imagem'];
            $nome_arquivo = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/',"\t"], '', str_replace(' ', '_', $grupo))).".".$arquivo->getClientOriginalExtension();
            $arquivo->storeAs($this->storage, $nome_arquivo);
        }else{
            $nome_arquivo = null;
        }
        
        $grupoObj->descricao = $grupo;
        $grupoObj->largura = $fields['largura'];
        $grupoObj->gramatura_gm2 = $fields['gramatura_gm2'];
        $grupoObj->gramatura_gml = $fields['gramatura_gml'];
        $grupoObj->rendimento = $fields['rendimento'];
        $grupoObj->origem = $fields['origem'];
        $grupoObj->pecas = $fields['pecas'];
        $grupoObj->caracteristicas = $fields['caracteristicas'];
        $grupoObj->segmentos_id = $fields['segmento'];
        $grupoObj->padrao_codigo = $fields['padrao_codigo'];
        $grupoObj->familias_id = $fields['familia'];
        $grupoObj->tipo_genero = $fields['tipo_genero'];
        $grupoObj->gramatura_tipo = $fields['gramatura_tipo'];
        $grupoObj->composicaos_id = $fields['composicao'];
        $grupoObj->construcao = $fields['construcao'];
        $grupoObj->construcaos_id = $fields['construcao_id'];
        $grupoObj->sazonalidades_id = $fields['sazonal'];

        if(!empty($fields['encolhimento']) ||
           !empty($fields['titulo_trama']) ||
           !empty($fields['titulo_urdume']) ||
           !empty($fields['informacao_adicional']) ||
           !empty($fields['ligamento']) ||
           !empty($fields['construcao'])
        ){
            $grupoObj->status = true;
            $grupoObj->encolhimento = $fields['encolhimento'];
            $grupoObj->titulo_trama = $fields['titulo_trama'];
            $grupoObj->titulo_urdume = $fields['titulo_urdume'];
            $grupoObj->informacao_adicional = $fields['informacao_adicional'];   
            $grupoObj->ligamento = $fields['ligamento'];       
            $grupoObj->construcao = $fields['construcao'];
        }
        
        if(!empty($nome_arquivo)){
            $grupoObj->imagem = $nome_arquivo;
        }
        
        $grupoObj->updated_by = Auth::id();
        $grupoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response,200);
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
        $query = ProdutoGrupo::select();
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

        $quantidade = ProdutoEspecificacao::where('grupo', $result->descricao)->count(); 

        $dados = [
            'id' => encrypt($id),
            'grupo' => $result->descricao
        ];
        return view('programs.produto.grupo.modal.deletar')->with(['dados' => $dados, 'quantidade' => $quantidade]);
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
            ],422);
        }
        
        
        $grupoObj = ProdutoGrupo::find($id);

        $grupoObj->deleted_by = Auth::id();
        $grupoObj->save();
        $grupoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response,200);
    }

    public function filter(Request $request){
        $fields = $request->only('grupo');
        $grupo = strtoupper(tirarAcentos($fields['grupo']));
        $retorno = [];

        $query = ProdutoGrupo::with('segmento');
        if(!empty($grupo)){
            $query->where('descricao', 'like', '%'.$grupo.'%');
        }
        $result = $query->get();

        foreach($result as $grupo){
            $retorno [] = [
                'id' => encrypt($grupo->id),
                'descricao' => $grupo->descricao,
                'segmento' => isset($grupo->segmento) ? $grupo->segmento->descricao : ''
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
        $query = ProdutoGrupo::select('descricao')
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

    public function atualizarGrupoNoEstoque(){
        $query = ProdutosEstoque::select('codigo_produto');
        $query->with('especificacao.produtoGrupo');
        $query->whereNull('grupo');
        $query->distinct();
        $result = $query->get();

        foreach($result as $value){
            if(!empty($value->especificacao)){
                $produtosEstoqueObj = ProdutosEstoque::select(); 
                $produtosEstoqueObj->where('codigo_produto', $value->codigo_produto);
                $produtosEstoqueObj->update(['grupo' => $value->especificacao->grupo]);
                if(!empty($value->especificacao->produtoGrupo)){
                    $produtosEstoqueObj->update(['produto_grupos_id' => $value->especificacao->produtoGrupo->id]);
                }
            }
        }

        $query = ProdutosEstoque::select();
        $query->whereNull('grupo');
        $query->update(['grupo' => 'A CADASTRAR']);
        $query->update(['produto_grupos_id' => 5256]);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('marca');
        $query->update(['marca' => 'A CADASTRAR']);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('linha');
        $query->update(['linha' => 'A CADASTRAR']);
        
        $query = ProdutoEspecificacao::select();
        $query->whereNull('subgrupo');
        $query->update(['subgrupo' => 'A CADASTRAR']);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('origem');
        $query->update(['origem' => 'A CADASTRAR']);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('pecas');
        $query->update(['pecas' => 'A CADASTRAR']);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('caracteristicas');
        $query->update(['caracteristicas' => 'A CADASTRAR']);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('segmentos_id');
        $query->update(['segmentos_id' => 22]);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('tipo_material');
        $query->update(['tipo_material' => 'A CADASTRAR']);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('padrao_codigo');
        $query->update(['padrao_codigo' => 'A CADASTRAR']);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('familias_id');
        $query->update(['familias_id' => 71]);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('genero');
        $query->update(['genero' => 'A CADASTRAR']);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('gramatura_tipo');
        $query->update(['gramatura_tipo' => 'A CADASTRAR']);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('composicaos_id');
        $query->update(['composicaos_id' => 8]);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('construcaos_id');
        $query->update(['construcaos_id' => 10]);

        $query = ProdutoEspecificacao::select();
        $query->whereNull('sazonalidades_id');
        $query->update(['sazonalidades_id' => 4]);
    }

    public function atualizarUnidadeNoEstoque(){
        $query = ProdutosEstoque::select('codigo_produto');
        $query->with('especificacao');
        $query->whereNull('unidade');
        $query->distinct();
        $result = $query->get();

        foreach($result as $value){
            if(!empty($value->especificacao)){
                $produtosEstoqueObj = ProdutosEstoque::select(); 
                $produtosEstoqueObj->where('codigo_produto', $value->codigo_produto);
                $produtosEstoqueObj->update(['unidade' => $value->especificacao->unidade]);
            }
        }
    }
}
