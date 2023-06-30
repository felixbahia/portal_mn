<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\InformacaoAdicionalProdutoRequest;
use App\Http\Requests\InformacaoAdicionalProdutoEditarRequest;

use App\Produto;
use App\ProdutoNasajon;
use App\ProdutosEstoque;
use App\ProdutoEspecificacao;
use App\Familia;
use App\Segmento;
use App\Composicao;
use App\Construcao;
use App\ProdutoGrupo;
use App\Sazonalidade;
use App\LogProduto;

use Auth;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class InformacaoAdicionalProdutoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if(Auth::user()->hasPermissionTo("programas App\InformacaoAdicionalProduto") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\InformacaoAdicionalProduto');
        $segmentos =$this->segmentos();

        return view("programs.info_adicional_produto.index")->with(['segmentos' => $segmentos]);
    }

    public function filter(Request $request){
        
        $busca = $request->only('marca', 'linha','grupo', 'subgrupo','produto', 'nome' , 'status','segmentos');

        if (empty($busca['marca']) && empty($busca['linha']) && empty($busca['grupo']) && empty($busca['subgrupo']) && empty($busca['produto']) && empty($busca['nome'])
        && empty($busca['segmentos'])) {

            $error_array = [
                'marca' => 'Especifique um critério para a busca',
                'linha' => 'Especifique um critério para a busca',
                'grupo' => 'Especifique um critério para a busca',
                'produto' => 'Especifique um critério para a busca',
                'nome' => 'Especifique um critério para a busca',
                'nome' => 'Especifique um critério para a busca',
                'segmentos' => 'Especifique um critério para a busca',

            ];

            return response()->json(['status' => 'error', 'message' => '', 'errors' => $error_array, 'response' => []], 422);
        }


        $produtoQuery = ProdutoEspecificacao::with('produtoGrupo');

        if (!empty($busca['marca'])){
            $produtoQuery->where('marca', 'like', "%" . strtoupper($busca['marca']) . "%");
        }
        
        if (!empty($busca['linha'])){
            $produtoQuery->where('linha', 'like', "%" . strtoupper($busca['linha']) . "%");

        }
        
        if (!empty($busca['grupo'])){
            $produtoQuery->whereHas('produtoGrupo', function ($query) use($busca){
                $query->where('descricao', $busca['grupo']);
                
            });
        }

        if (!empty($busca['subgrupo'])){
            $produtoQuery->where('subgrupo', 'like', "%" . strtoupper($busca['subgrupo']) . "%");
        }
        
        if (!empty($busca['produto'])){
            $produtoQuery->where('codigo_produto', 'like', "%" . strtoupper($busca['produto']) . "%");
        }
        
        if (!empty($busca['nome'])){
            $produtoQuery->where('descricao', 'like', "%" . $busca['nome'] . "%");
        }

        if (!empty($busca['status'])){
            $produtoQuery->where('ativo', '=',  $busca['status']);
        }

        if (!empty($busca['segmentos'])){
            $produtoQuery->where('segmentos_id', '=',  $busca['segmentos']);
        }

        $produtoQuery->orderBy('grupo', 'ASC');
        $produtoQuery->orderBy('subgrupo', 'ASC');
        $produtoQuery->orderBy('descricao', 'ASC');
        $produtoQuery->orderBy('linha', 'ASC');
        $produtoQuery->orderBy('marca', 'ASC');

        $produtos = $produtoQuery->get();

        $result = [];
        $codigos = [];

        foreach ($produtos as $key => $value) {
            if (!is_null($value)){

                if (isset($value->produtoGrupo->origem) && $value->produtoGrupo->origem === 'BRASIL'){

                    switch ($value->procedencia) {
                        // Nacional
                        case 0:
                        case 3:
                        case 4:
                        case 5:
                            $exibir_nacional = "Real -> Dólar";
                            break;
                        // Internacional
                        case 1:
                        case 2:
                        case 6:
                        case 7:
                            $exibir_nacional = "Dólar -> Real";
                            break;
                    }
                }
                else{
                    $exibir_nacional = '';
                }

                $result[$key] = [
                    'marca' => $value->marca,
                    'linha' => $value->linha,
                    'grupo' => isset($value->produtoGrupo) ? $value->produtoGrupo->descricao : '',
                    'subgrupo' => $value->subgrupo,
                    'cod_produto' => $value->codigo_produto,
                    'nome' => $value->descricao,
                    'status' => $value->ativo?'Ativo':'Inativo',
                    'id' => isset($value->produtoGrupo->id)? $value->produtoGrupo->id:'',
                    'unidade' => isset($value->unidade)? $value->unidade:'',
                    'largura' => isset($value->produtoGrupo->largura)?$value->produtoGrupo->largura:'',
                    'gramatura' => isset($value->produtoGrupo->gramatura_gml)?$value->produtoGrupo->gramatura_gml:'',
                    'rendimento' => isset($value->produtoGrupo->rendimento)?$value->produtoGrupo->rendimento:'',                    
                ];

            }

        }

        return response()->json($result);
    }

    public function telaEdicao(Request $request){

        $produto = ProdutoEspecificacao::with(
            'produtoGrupo',
            'produtoGrupo.segmento',
            'produtoGrupo.familia',
            'produtoGrupo.composicao',
            'produtoGrupo.construcaos',
            'produtoGrupo.sazonalidade')->where('codigo_produto', $request->cod_produto)->first();

        switch ($produto->procedencia) {
            // Nacional
            case 0:
            case 3:
            case 4:
            case 5:
                $procedencia = "Nacional";
                break;
            // Internacional
            case 1:
            case 2:
            case 6:
            case 7:
                $procedencia = "Importado";
                break;
        }

        $gramatura_tipo = '';
        $tipo_genero = '';
        $padrao_codigo = '';

        if(isset($produto->produtoGrupo)){
            switch ($produto->produtoGrupo->gramatura_tipo) {
                case 'top':
                    $gramatura_tipo = 'Top';
                    break;
                case 'bottom':
                    $gramatura_tipo = 'Bottom';
                    break;
                case 'all_over':
                    $gramatura_tipo = 'All Over';
                    break;
            }

            switch ($produto->produtoGrupo->tipo_genero) {
                case 'feminino':
                    $tipo_genero = 'Feminino';
                    break;
                case 'masculino':
                    $tipo_genero = 'Masculino';
                    break;
                case 'ambos':
                    $tipo_genero = 'Ambos';
                    break;
            }

            switch ($produto->produtoGrupo->padrao_codigo) {
                case '7_11':
                    $padrao_codigo = 'Artigo 7 + 5 desenho';
                    break;
                case '6_10':
                    $padrao_codigo = 'Artigo 6 + 5 desenho';
                    break;
                case '4_9':
                    $padrao_codigo = 'Artigo 4 + 6 desenho + 3 tamanho';
                    break;
            }
    
        }
        
        $produto_array = [
            'marca' => $produto->marca,
            'linha' => $produto->linha,
            'grupo' => isset($produto->produtoGrupo) ? $produto->produtoGrupo->descricao : '',
            'subgrupo' => $produto->subgrupo,
            'cod_produto' => $produto->codigo_produto,
            'nome' => $produto->descricao,
            'status' => $produto->ativo,
            'procedencia' => $procedencia,
            'id' => isset($produto->produtoGrupo->id)? $produto->produtoGrupo->id:'',
            'unidade' => isset($produto->unidade)? $produto->unidade:'',
            'largura' => isset($produto->produtoGrupo->largura)?$produto->produtoGrupo->largura:'',
            'gramatura_gm2' => isset($produto->produtoGrupo->gramatura_gm2)?$produto->produtoGrupo->gramatura_gm2:'',
            'gramatura_gml' => isset($produto->produtoGrupo->gramatura_gml)?$produto->produtoGrupo->gramatura_gml:'',
            'rendimento' => isset($produto->produtoGrupo->rendimento)?$produto->produtoGrupo->rendimento:'',
            'origem' => empty($produto->produtoGrupo->origem)? '' : $produto->produtoGrupo->origem,
            'pecas' => empty($produto->produtoGrupo->pecas)? '' : $produto->produtoGrupo->pecas,
            'caracteristicas' => empty($produto->produtoGrupo->caracteristicas)? '' : $produto->produtoGrupo->caracteristicas,
            'segmento' => isset($produto->produtoGrupo->segmento) ? $produto->produtoGrupo->segmento->descricao : '',
            'tipo_material' => empty($produto->tipo_material)? '' : $produto->tipo_material,
            'padrao_codigo' => $padrao_codigo,
            'familia' => isset($produto->produtoGrupo->familia) ? $produto->produtoGrupo->familia->descricao : '',
            'genero' => $tipo_genero,
            'gramatura_tipo' => $gramatura_tipo,
            'composicao' => isset($produto->produtoGrupo->composicao) ? $produto->produtoGrupo->composicao->descricao : '',
            'construcao' => isset($produto->produtoGrupo->construcaos) ? $produto->produtoGrupo->construcaos->descricao : '',
            'sazonal' => isset($produto->produtoGrupo->sazonalidade) ? $produto->produtoGrupo->sazonalidade->descricao : ''
        ];

        $empresas = returnEmpresasNasajonView();
        $ProdutosEstoqueObj = ProdutosEstoque::where('codigo_produto', $request->cod_produto)->orderBy('estabelecimento')->get();
        $estoques = [];
        foreach ($ProdutosEstoqueObj as $estoque) {
            $estoques[] = $empresas[intval($estoque->estabelecimento)] . ' = '. parserValor($estoque->estoque);
        }
        $status = ['true' => 'Ativo', 'false' => 'Inativo'];

        return view("programs.info_adicional_produto.editar")
        ->with([
            'produto' => $produto_array,
            'status' => $status, 
            'estoques' => $estoques
        ]);

    }

    public function salvar_edicao(InformacaoAdicionalProdutoEditarRequest $request){
        $fields = $request->only('id', 'cod_produto','marca','linha','grupo','subgrupo','status');

        $produtoGrupo = ProdutoGrupo::where('descricao', strtoupper($fields['grupo']))->first();

        
        $produtoEspecificacaoObj = ProdutoEspecificacao::find($fields['cod_produto']);
        $ProdutosNasajon = ProdutoNasajon::where('codigo', 'ilike', $fields['cod_produto'])->first();
        $produtoEspecificacaoObj->descricao = strtoupper($ProdutosNasajon->especificacao);
        $produtoEspecificacaoObj->marca = strtoupper($fields['marca']);
        $produtoEspecificacaoObj->linha = strtoupper($fields['linha']);
        $produtoEspecificacaoObj->produto_grupos_id = $produtoGrupo->id;
        $produtoEspecificacaoObj->grupo = strtoupper($fields['grupo']);
        $produtoEspecificacaoObj->subgrupo = strtoupper($fields['subgrupo']);
        $produtoEspecificacaoObj->ativo = $fields['status'];
        if($fields['status'] == 'true'){
            $produtoEspecificacaoObj->data_ativacao = Carbon::now();
        }else{
            $produtoEspecificacaoObj->data_ativacao = null;
        }
        $produtoEspecificacaoObj->updated_by = Auth::user()->id;
        $produtoEspecificacaoObj->save();

        $log_produto = new LogProduto;
        $log_produto->acao = ($fields['status'] === 'true') ? 'ativar produto' : 'desativar produto';
        $log_produto->produto = $fields['cod_produto'];
        $log_produto->created_by = Auth::user()->id;
        $log_produto->updated_by = Auth::user()->id;
        $log_produto->save();
		
        return response()->json(['status' => 'success', 'message' => '', 'error' => [], 'response' => []], 200);

    }

    public function telaAdicao(){

        $segmentos = Segmento::all()->pluck('descricao', 'id');
        $familia = Familia::orderBy('descricao', 'ASC')->pluck('descricao', 'id');
        $composicao = Composicao::orderBy('descricao')->pluck('descricao', 'id');
        $construcao = Construcao::orderBy('descricao')->pluck('descricao', 'id');
        $sazonalidade = Sazonalidade::orderBy('descricao')->pluck('descricao', 'id');
        $bookVirtualControllerObj = new BookVirtualController;

        return view("programs.info_adicional_produto.criar")->with([
            'segmentos' => $segmentos, 
            'tipos_materiais' => $bookVirtualControllerObj->tipos_materiais,
            'padroes_codigo' => $bookVirtualControllerObj->padroes_codigo,
            'familia' => $familia,
            'tipo_generos' => $bookVirtualControllerObj->tipo_generos,
            'composicao' => $composicao,
            'construcao' => $construcao,
            'sazonalidade' => $sazonalidade
        ]);
        
    }

    public function salvar_adicao(InformacaoAdicionalProdutoRequest $request){
        $fields = $request->only('cod_produto','marca', 'linha', 'grupo', 'subgrupo');

        $query = ProdutoNasajon::select();
        $query->where('codigo', 'ilike', $fields['cod_produto']);
        $result = $query->first();

        $produtoGrupo = ProdutoGrupo::where('descricao', strtoupper($fields['grupo']))->first();

        $produtoEspecificacaoObj = new ProdutoEspecificacao;
        
        $produtoEspecificacaoObj->codigo_produto = strtoupper($fields['cod_produto']);
        $produtoEspecificacaoObj->marca = strtoupper($fields['marca']);
        $produtoEspecificacaoObj->linha = strtoupper($fields['linha']);
        $produtoEspecificacaoObj->produto_grupos_id = $produtoGrupo->id;
        $produtoEspecificacaoObj->grupo = strtoupper($fields['grupo']);
        $produtoEspecificacaoObj->subgrupo = strtoupper($fields['subgrupo']);
        $produtoEspecificacaoObj->descricao = strtoupper($result->especificacao);
        $produtoEspecificacaoObj->procedencia =  $this->procedencia($result->procedencia);
        $produtoEspecificacaoObj->updated_by = Auth::user()->id;
        $produtoEspecificacaoObj->save();


        return response()->json(['status' => 'success', 'message' => '', 'error' => [], 'response' => []], 200);

    }

    public function modalPesquisaProdutos(){
        return view("programs.info_adicional_produto.search_produto_modal");
    }

    public function retornaProdutosSemDetalhes(Request $request){
        $busca = $request->only('marca', 'linha','grupo','codigo', 'nome');

        $produtoQuery = ProdutoEspecificacao::with('produtoGrupo')->where('descricao', '!=', 'DESATIVADO');

        if (!empty($busca['marca'])){
            $produtoQuery->where('marca', 'ilike', "%" . $busca['marca'] . "%");
        }
        
        if (!empty($busca['linha'])){
            $produtoQuery->where('linha', 'ilike', "%" . $busca['linha'] . "%");

        }
        
        if (!empty($busca['grupo'])){
            $produtoQuery->where('grupo', 'ilike', "%" . $busca['grupo'] . "%");
        }
        
        if (!empty($busca['codigo'])){
            $produtoQuery->where('codigo_produto', 'ilike', "%" . $busca['codigo'] . "%");
        }
        
        if (!empty($busca['nome'])){
            $produtoQuery->where('descricao', 'ilike', "%" . $busca['nome'] . "%");
        }

        if(empty($produtoQuery)){
            $produtoQuery = ProdutoNasajon::with('especificacoes.produtoGrupo')->where('especificacao', '!=', 'DESATIVADO');
            if (!empty($busca['codigo'])){
                $produtoQuery->where('codigo', 'ilike', "%" . $busca['codigo'] . "%");
            }
            
            if (!empty($busca['nome'])){
                $produtoQuery->where('especificacao', 'ilike', "%" . $busca['nome'] . "%");
            }
        }

        if (empty($busca['marca']) && empty($busca['linha']) && empty($busca['grupo']) && empty($busca['codigo']) && empty($busca['nome'])) {
            
            $error_array = [
                'linha' => 'Especifique um critério para a busca',
                'grupo' => 'Especifique um critério para a busca',
                'codigo' => 'Especifique um critério para a busca',
                'nome' => 'Especifique um critério para a busca',
            ];

            return response()->json(['status' => 'error', 'message' => '', 'errors' => $error_array, 'response' => []], 422);

        }

        $produtos = $produtoQuery->get();

        $result = [];

        foreach ($produtos as $key => $value) {
            if (is_null($value->produtoGrupo) || is_null($value->especificacoes->produtoGrupo)){

                $result[$key] = [
                    'marca' => utf8_encode($value->marca),
                    'codigo' => utf8_encode($value->codigo_produto??$value->codigo),
                    'linha' => utf8_encode($value->linha),
                    'grupo' => utf8_encode($value->grupo),
                    'nome' => utf8_encode($value->descricao??$value->especificacao),
                    'unidade' => utf8_encode($value->unidade),
                ];
    
            }

        }

        return response()->json($result);

    }

    public function retornaDetalhesProduto(Request $request){

        $produto = ProdutoEspecificacao::with(['produtoGrupo', 'produtoNasajon', 'produtoNovo'])->where('codigo_produto', $request->cod_prod)->first();

        if(empty($produto)){
            $produto = ProdutoNasajon::with('especificacoes.produtoGrupo')->where('codigo', $request->cod_prod)->first();
        }

        if(empty($produto)){
            $error_array = [
                'nome' => 'Produto não cadastrado.',
            ];

            return response()->json(['status' => 'error', 'message' => '', 'errors' => $error_array, 'response' => []], 422);
        }

        if(!is_null($produto)) {
            $procedencia = '';
            if(!empty($produto->procedencia)){
                switch ($this->procedencia($produto->procedencia)) {
                    // Nacional
                    case 0:
                    case 3:
                    case 4:
                    case 5:
                        $procedencia = "Nacional";
                        break;
                    // Internacional
                    case 1:
                    case 2:
                    case 6:
                    case 7:
                        $procedencia = "Importado";
                        break;
                }
            }elseif(!empty($produto->procedencia)){
                switch ($this->procedencia($produto->procedencia)) {
                    // Nacional
                    case 0:
                    case 3:
                    case 4:
                    case 5:
                        $procedencia = "Nacional";
                        break;
                    // Internacional
                    case 1:
                    case 2:
                    case 6:
                    case 7:
                        $procedencia = "Importado";
                        break;
                }
            }
            

            if(!empty($produto->produtoNasajon)){
                $composicao = $produto->produtoNasajon->composicao;
            }else if(!empty($produto->composicao)){
                $composicao = $produto->composicao;
            }else if(!empty($produto->produtoNovo->composicao)){
                $composicao = $produto->produtoNovo->composicao;
            }else{
                $composicao = ''; 
            }

            $produto_array = [
                'grupo' => utf8_decode(utf8_encode($produto->grupo)),
                'subgrupo' => utf8_decode(utf8_encode($produto->subgrupo)),
                'marca' => utf8_decode(utf8_encode($produto->marca)),
                'linha' => utf8_decode(utf8_encode($produto->linha)),
                'nome' => utf8_decode(utf8_encode($produto->descricao??$produto->especificacao)),
                'procedencia' => $procedencia,
                'largura' => isset($produto->produtoGrupo->largura) || isset($produto->especificacoes->produtoGrupo->largura) ?$produto->produtoGrupo->largura:'',
                'gramatura' => isset($produto->produtoGrupo->gramatura_gm2) || isset($produto->especificacoes->produtoGrupo->gramatura_gm2) ?$produto->produtoGrupo->gramatura_gm2:'',
                'composicao' => $composicao,
                'unidade' => isset($produto->produtoNasajon)?  $produto->produtoNasajon->unidade: trim($produto->unidade),
            ];

            return response()->json($produto_array);

        }

            // view("programs.info_adicional_produto.editar")->with(['produto' => $produto_array]);
    }

    public function excluir(Request $request){
        $codigo_produto = $request->only('cod_produto');

        $produtoEspecificacaoObj = ProdutoEspecificacao::where('codigo_produto', $codigo_produto)->delete();

        return response()->json(['status' => 'success', 'message' => '', 'error' => [], 'response' => []], 200);
    }

    public function procedencia($nome){
        switch($nome){
            case 'Fabricação Nacional':
                return 0;
            case 'Estrangeira - Importação Direta':
                return 1;
            case 'Estrangeira - Importada Adquirida no Mercado Interno':
                return 2;
            case 'Nacional, mercadoria ou bem com Conteúdo de Importação superior a 40% e inferior ou igual a 70% (setenta por cento)':
                return 3;
            case 'Nacional, cuja produção tenha sido feita em conformidade com os processos produtivos básicos de que tratam as legislações citadas nos ajustes':
                return 4;
            case 'Nacional, mercadoria ou bem com conteúdo de importação inferior ou igual a 40%':
                return 5;
            case 'Estrangeira - Importação direta, sem similar nacional, constante em lista de resolução CAMEX e gás natural':
                return 6;
            case 'Estrangeira - Adquirida no mercado interno, sem similar nacional, constante em lista de resolução CAMEX e gás natural':
                return 7;
            case 'Nacional, mercadoria ou bem com Conteúdo de Importação superior a 70% (setenta por cento)':
                return 8;
            default:
                return $nome;
        }
    }

    public function segmentos(){

        $segmentos = [];
  
        $segmentos= Segmento::all()->pluck('descricao', 'id');
               
        return  $segmentos;
    }

    public function buscaGrupo(Request $request){
        $campo = $request->only('grupo');

        $produtoGrupo = ProdutoGrupo::with('segmento', 'familia', 'composicao', 'construcaos', 'sazonalidade')
        ->where('descricao', $campo['grupo'])->first();

        switch ($produtoGrupo->gramatura_tipo) {
            case 'top':
                $gramatura_tipo = 'Top';
                break;
            case 'bottom':
                $gramatura_tipo = 'Bottom';
                break;
            case 'all_over':
                $gramatura_tipo = 'All Over';
                break;
            case null:
                $gramatura_tipo = '';
        }

        switch ($produtoGrupo->tipo_genero) {
            case 'feminino':
                $tipo_genero = 'Feminino';
                break;
            case 'masculino':
                $tipo_genero = 'Masculino';
                break;
            case 'ambos':
                $tipo_genero = 'Ambos';
                break;
            case null:
                $tipo_genero = '';
        }

        switch ($produtoGrupo->padrao_codigo) {
            case '7_11':
                $padrao_codigo = 'Artigo 7 + 5 desenho';
                break;
            case '6_10':
                $padrao_codigo = 'Artigo 6 + 5 desenho';
                break;
            case '4_9':
                $padrao_codigo = 'Artigo 4 + 6 desenho + 3 tamanho';
                break;
            case null:
                $padrao_codigo = '';
        }

        $dadosGrupo = [
            'largura' => $produtoGrupo->largura,
            'gramatura_gml' => $produtoGrupo->gramatura_gml,
            'gramatura_gm2' => $produtoGrupo->gramatura_gm2,
            'gramatura_gml' => $produtoGrupo->gramatura_gml,
            'gramatura_tipo' => $gramatura_tipo,
            'rendimento' => $produtoGrupo->rendimento,
            'tipo_genero' => $tipo_genero,
            'segmento' => isset($produtoGrupo->segmento) ? $produtoGrupo->segmento->descricao : '',
            'familia' => isset($produtoGrupo->familia) ? $produtoGrupo->familia->descricao : '',
            'padrao_codigo' => $padrao_codigo,
            'caracteristicas'=> $produtoGrupo->caracteristicas,
            'pecas' => $produtoGrupo->pecas,
            'origem' => $produtoGrupo->origem,
            'composicao' => isset($produtoGrupo->composicao) ? $produtoGrupo->composicao->descricao : '',
            'construcao' => $produtoGrupo->construcao,
            'construcaos' => isset($produtoGrupo->construcaos) ? $produtoGrupo->construcaos->descricao : '',
            'sazonalidade' => isset($produtoGrupo->sazonalidade) ? $produtoGrupo->sazonalidade->descricao : ''
        ];

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $dadosGrupo
        ];
        return response()->json($response, 200);
    }
}
