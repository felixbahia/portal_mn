<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Auth;

use App\BuscaDesenhoTemp;
use App\Campanha;
use App\CarrinhoCompra;
use App\CarrinhoCompraItem;
use App\CepEstado;
use App\ClienteBionexo;
use App\ClienteNasajon;
use App\Segmento;
use App\CondicoesPagamentoWeb;
use App\DadosClientePedido;
use App\EstabelecimentoCidadeFob;


use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PedidoPortalController;
use App\Http\Requests\BookVirtualAdicionarClienteRequestNew;
use App\Http\Requests\BookVirtualCarrinhoAdicionarRequestNew;
use App\Http\Requests\BookVirtualEditarClienteRequestNew;
use App\Http\Requests\BookVirtualEditarTransportadoraRequestNew;
use App\Http\Requests\BookVirtualEnviarPDFRequest;
use App\Http\Requests\BookVirtualFiltroPrincipalRequest;
use App\Http\Requests\ListaDePrecosRequest;
use App\Importacao;
use App\PedidoItemPortal;
use App\PedidoPortal;
use App\ProdutoGrupo;
use App\ProdutosEstoque;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use PDF;

class BookVirtualExibicaoControllerNew extends Controller
{

    public $path = 'public/book_virtual/';
    public $pathPdf = 'public/grupo/pdf/';
    public $segmentos_path = 'public/segmentos/';
    public $grupo_patch = 'public/grupo/';
    public $path_produto_fotos = 'public/produto_fotos/';

    public $tipo_vendas = [
        'pronta_entrega' => 'Pronta Entrega',
        'pronta_entrega_futura' => 'Chegada Futura'
    ];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\BookVirtualExibicaoNew") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BookVirtualExibicaoNew');

        $CarrinhoCompraObj = CarrinhoCompra::with('dadosClientePedido.cliente', 'carrinhoCompraItens')
        ->where('created_by', Auth::id())
        ->where('finalizado', false)
        ->orderBy('id', 'desc')
        ->get();

        $quantidade = 0;

        $dadosClientePedido = DadosClientePedido::with('cliente')->where('created_by', Auth::id())->first();
        $cliente = isset($dadosClientePedido->cliente->nome) ? $dadosClientePedido->cliente->nome.' - '.$dadosClientePedido->cliente->cpf_cnpj : '';

        $CarrinhoCompraObj->each(function($item) use(&$quantidade){
            $quantidade += $item->carrinhoCompraItens->count('id');
        });

        if(isMobile() == true){
            return view('programs.book_virtual_exibicao_new.mobile.index')
            ->with([
                'tipo_vendas' => $this->tipo_vendas,
                'contador' => $quantidade,
                'cliente' => $cliente,
                'campanhas' => $this->campanhas()
            ]);
        }else{
            return view('programs.book_virtual_exibicao_new.index')
            ->with([
                'tipo_vendas' => $this->tipo_vendas,
                'contador' => $quantidade,
                'cliente' => $cliente,
                'campanhas' => $this->campanhas()
            ]);
        }
    }

    public function busca(Request $request, $segmentos_id, $marca, $linha, $navegacao){
        if(Auth::user()->hasPermissionTo("programas App\BookVirtualExibicaoNew") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BookVirtualExibicaoNew');

        $CarrinhoCompraObj = CarrinhoCompra::with('dadosClientePedido.cliente', 'carrinhoCompraItens')
        ->where('created_by', Auth::id())
        ->where('finalizado', false)
        ->orderBy('id', 'desc')
        ->get();

        $quantidade = 0;

        $dadosClientePedido = DadosClientePedido::with('cliente')->where('created_by', Auth::id())->first();
        $cliente = isset($dadosClientePedido->cliente->nome) ? $dadosClientePedido->cliente->nome.' - '.$dadosClientePedido->cliente->cpf_cnpj : '';

        $CarrinhoCompraObj->each(function($item) use(&$quantidade){
            $quantidade += $item->carrinhoCompraItens->count('id');
        });

        $return = [
            'contador' => $quantidade,
            'cliente' => $cliente,
            'tipo_vendas' => $this->tipo_vendas,
            'campanhas' => $this->campanhas()
        ];

        $return['grupo'] = '';
        $return['marca'] = $marca == 'false' ? '' : $marca;
        $return['linha'] = $linha == 'false' ? '' : $linha;
        $return['codigo_produto'] = '';
        $return['tipo_venda'] = '';
        $return['busca_navegacao'] = $navegacao;
        $return['campanha'] = !empty(Campanha::where('nome' ,$navegacao)->first()->id) ? Campanha::where('nome' ,$navegacao)->first()->id : '';

        if($segmentos_id == 'busca_avancada'){
            $return['segmentos_id'] = 'busca_avancada';
        }
        else{
            $return['segmentos_id'] = $segmentos_id;
            $return['busca_navegacao'] = Segmento::find($segmentos_id)->descricao;
        }

        if(isMobile() == true){
            return view('programs.book_virtual_exibicao_new.mobile.busca')->with($return);
        }else{
            return view('programs.book_virtual_exibicao_new.busca')->with($return);
        }

    }

    public function filter(BookVirtualFiltroPrincipalRequest $request){

        $fields = $request->only('segmentos_id', 'grupo', 'marca', 'linha', 'codigo_produto', 'tipo_venda', 'busca_navegacao', 'campanha');
      
        $produtosEstoqueObj = ProdutosEstoque::with('produtoGrupo', 'produtoGrupo.segmento')
        ->select(DB::raw('sum(estoque) + sum(compras) as estoque_compras'),'produto_grupos_id')
        ->groupBy('produto_grupos_id')
        ->having(DB::raw('sum(estoque) + sum(compras)'), '>', 0);

        if(isset($fields['segmentos_id']) && !empty($fields['segmentos_id']) && $fields['segmentos_id'] != 'busca_avancada'){
            $produtosEstoqueObj->whereHas('produtoGrupo', function ($query) use ($fields){
                $query->where('segmentos_id', $fields['segmentos_id'])
                ->where('mostrar', true)
                ->orderBy('descricao');
            });
        }

        if(isset($fields['marca']) && !empty($fields['marca'])){
            $produtosEstoqueObj->whereHas('especificacao', function ($query) use ($fields){
                $query->where('marca', $fields['marca'])
                ->orderBy('grupo');
            })
            ->whereHas('produtoGrupo', function ($query){
                $query->where('mostrar', true);
            });
        }

        if(isset($fields['linha']) && !empty($fields['linha'])){
            $produtosEstoqueObj->whereHas('especificacao', function ($query) use ($fields){
                $query->where('linha', $fields['linha'])
                ->orderBy('grupo');
            })
            ->whereHas('produtoGrupo', function ($query){
                $query->where('mostrar', true);
            });
        }

        if(!empty($fields['busca_navegacao'])){
            $campanha = Campanha::where('nome', $fields['busca_navegacao'])->first();
            if(!empty($campanha->id)){
                $produtosEstoqueObj->where('campanha_id',$campanha->id);
            }
        }
        if(!empty($fields['campanha'])){
            $campanha = Campanha::find($fields['campanha']);
            $produtosEstoqueObj->where('campanha_id',$fields['campanha'])
            ->whereHas('produtoGrupo', function ($query){
                $query->where('mostrar', true);
            }); 
        }

        $produtosEstoque = $produtosEstoqueObj->get();

        $dados = [];

        $produtosEstoque->each(function($item) use(&$dados, $fields, $campanha){
            if(isset($item->produtoGrupo->descricao)){
                $pdf_nome = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/',"\t"], '', str_replace(' ', '_', $item->produtoGrupo->descricao))).'.pdf';
                $book_pdf = Storage::exists($this->pathPdf.'book_'.$pdf_nome) ? Storage::url($this->pathPdf.'book_'.$pdf_nome) : '';

                if(empty($book_pdf)){
                    if(isset($item->produtoGrupo->segmento->descricao)){
                        if(Storage::exists($this->pathPdf.$item->produtoGrupo->segmento->descricao.'/book_'.$pdf_nome)){
                            $book_pdf = Storage::url($this->pathPdf.$item->produtoGrupo->segmento->descricao.'/book_'.$pdf_nome);
                        }
                    }
                }
                $dados[] = [
                    'id' => encrypt($item->produtoGrupo->id),
                    'descricao' => $item->produtoGrupo->descricao,
                    'grupo_id' => $item->produto_grupos_id,
                    'imagem' => !empty($item->produtoGrupo->imagem) ? Storage::url($this->grupo_patch.$item->produtoGrupo->imagem) : URL::asset("images/sem_imagem.jpg"),
                    'book_pdf' => !empty($book_pdf) ? $book_pdf : '',
                    'route' => route('book_virtual_exibicao_new.exibicao',[
                        'id' => $item->produtoGrupo->id,
                        "grupo" => $item->produtoGrupo->descricao,
                        "codigo_produto" => !empty($fields['codigo_produto']) ? $fields['codigo_produto'] : 'false',
                        'codigos_produtos' => 'false',
                        'navegacao' => !empty($campanha->nome) ? $campanha->nome : $fields['busca_navegacao']
                    ])
                ];
            }
        });
            
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $dados
        ];
        return response()->json($response);
    }

    public function bookVirtualExibicao(Request $request, $id, $grupo, $codigo_produto, $codigos_produtos, $navegacao){
        ini_set('memory_limit','2048M');
        set_time_limit(300);

        if(Auth::user()->hasPermissionTo("programas App\BookVirtualExibicaoNew") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BookVirtualExibicaoNew');

        if($id == 'busca_avancada'){
            if($codigo_produto == 'grupo'){
                if(!is_numeric($grupo)){
                    $produtoGrupoObj = ProdutoGrupo::where('descricao', $grupo)->first();
                }else{
                    $produtoGrupoObj = ProdutoGrupo::find($grupo);
                }
            }
            elseif($grupo == 'codigo' && $codigo_produto != 'false'){
                $produtoGrupoObj = ProdutoGrupo::whereHas('produtoEspecificacao', function ($query) use ($codigo_produto){
                    $query->where('codigo_produto', $codigo_produto);
                })
                ->first();
            }
            elseif($codigos_produtos == 'true'){
                $buscaDesenhoTemp = BuscaDesenhoTemp::orderBy('id', 'desc')->first();
                $filter = $buscaDesenhoTemp->filtro;
                $filter = decrypt($filter);
                $buscaDesenhoTemp->delete();

                $produtoGrupoObj = ProdutoGrupo::with(['produtoEspecificacao' => function ($query) use ($filter){
                    $query->whereIn('codigo_produto', decrypt($filter['codigos_produtos']));
                }])
                ->whereHas('produtoEspecificacao', function ($query) use ($filter){
                    $query->whereIn('codigo_produto', decrypt($filter['codigos_produtos']));
                })
                ->first();
            }
            
        }else{
            $produtoGrupoObj = ProdutoGrupo::find($id);
        }
        
        $CarrinhoCompraObj = CarrinhoCompra::with('carrinhoCompraItens')
        ->where('created_by', Auth::id())
        ->where('finalizado', false)
        ->orderBy('id', 'desc')
        ->get();

        $quantidade = 0;
        $pedido = '';

        $CarrinhoCompraObj->each(function($item) use(&$quantidade,&$pedido){
            $quantidade += $item->carrinhoCompraItens->count('id');
            $pedido =  $item->pedido_id;
        });

        $dadosClientePedido = DadosClientePedido::with('cliente')->where('created_by', Auth::id())->first();

        $cliente = isset($dadosClientePedido->cliente->nome) ? $dadosClientePedido->cliente->nome.' - '.$dadosClientePedido->cliente->cpf_cnpj : '';
        $pedido_id = !empty($pedido) ? $pedido : '';
        $dados_cliente_id = !empty($dadosClientePedido->id) ? $dadosClientePedido->id : '';

        $result = [
            'marca' => '',
            'linha' => '',
            'tipo_venda' => '',
            'tipo_vendas' => $this->tipo_vendas,
            'busca_navegacao' => $navegacao,
            'campanhas' => $this->campanhas()
        ];

        $result['desenhos'] = [];
        $result['produtos'] = [];
        
        if(!empty($produtoGrupoObj->padrao_codigo)){

            $result['cliente'] = $cliente;
            $result['pedido_id'] = $pedido_id;
            $result['id'] = $produtoGrupoObj->id;
            $result['grupo'] = '';
            $result['grupo_navegacao'] = $produtoGrupoObj->descricao;
            $result['codigos_produtos'] = '';
            $result['codigo_produto'] = $result['codigo_produto'] = $codigo_produto != 'false' && $codigo_produto != 'grupo' ? $codigo_produto : '';
            $campanha = Campanha::where('nome', $navegacao)->first();
            $result['campanha'] = !empty($campanha->id) ?  $campanha->id : '';
            $result['codigo_produto_busca'] = '';
            $result['dados_cliente_id'] = $dados_cliente_id;
            $result['contador'] = $quantidade;

            
            if($codigo_produto == 'grupo'){
                $result['busca_navegacao'] = '';
            }
            if($grupo == 'codigo'){
                $result['busca_navegacao'] = $codigo_produto == 'false' ? '' : $produtoGrupoObj->produtoEspecificacao->firstWhere('codigo_produto', $codigo_produto)->descricao;
                $result['codigo_produto_busca'] = $codigo_produto;
            }

            if($codigo_produto == 'false' && $result['busca_navegacao'] == 'true'){
                $result['busca_navegacao'] = $produtoGrupoObj->segmento->descricao;
            }
            if($codigo_produto == 'false' && $result['busca_navegacao'] != 'true'){
            }

            if(!empty($filter['codigos_produtos'])){
                $result['codigos_produtos'] = $filter['codigos_produtos'];
                $result['codigo_desenho'] = $filter['codigo_desenho'];
                $result['grupo_navegacao'] = $produtoGrupoObj->descricao;

                $produto = decrypt($filter['codigos_produtos']);

                if($codigo_produto == 'false' && $grupo == 'false'){
                    $result['busca_navegacao'] =  $produtoGrupoObj->produtoEspecificacao->firstWhere('codigo_produto', $produto[0]['codigo_produto'])->descricao;
                }

                if($result['busca_navegacao'] == 'false'){
                    $result['busca_navegacao'] =  '';
                    $result['grupo_navegacao'] = $produtoGrupoObj->descricao;
                } 

                if($grupo == 'codigo'){
                    $result['busca_navegacao'] =  $produtoGrupoObj->produtoEspecificacao->firstWhere('codigo_produto', $produto[0]['codigo_produto'])->descricao;
                }
                if($codigo_produto == 'grupo'){
                    $result['busca_navegacao'] = '';
                }

                if(isMobile() == true){
                    return view('programs.book_virtual_exibicao_new.mobile.book_produtos')->with($result);
                }else{
                    return view('programs.book_virtual_exibicao_new.book_produtos')->with($result);
                }
            }
            
            if(isMobile() == true){
                return view('programs.book_virtual_exibicao_new.mobile.book')->with($result);
            }else{
                return view('programs.book_virtual_exibicao_new.book')->with($result);
            }
        }
        else{
            $result['cliente'] = $cliente;
            $result['pedido_id'] = $pedido_id;
            $result['codigo_produto'] = $result['codigo_produto'] = $codigo_produto != 'false' && $codigo_produto != 'grupo' ? $codigo_produto : '';
            $result['id'] = empty($produtoGrupoObj)? null : $produtoGrupoObj->id;
            $result['grupo'] = '';
            $campanha = Campanha::where('nome', $navegacao)->first();
            $result['campanha'] = !empty($campanha->id) ?  $campanha->id : '';
            $result['grupo_navegacao'] = $produtoGrupoObj->descricao;
            $result['codigo_produto_busca'] = '';
            $result['dados_cliente_id'] = $dados_cliente_id;
            $result['contador'] = $quantidade;

            if($grupo == 'codigo'){
                $result['busca_navegacao'] =  $produtoGrupoObj->produtoEspecificacao->firstWhere('codigo_produto', $codigo_produto)->descricao;
                $result['codigo_produto_busca'] = $codigo_produto;
            }
            if($codigo_produto == 'grupo'){
                $result['busca_navegacao'] = '';
            }
            if($codigo_produto == 'false' && $result['busca_navegacao'] == 'true'){
                $result['busca_navegacao'] = $produtoGrupoObj->segmento->descricao;
            }
            if($codigo_produto == 'false' && $result['busca_navegacao'] != 'true'){
            }

            if(isMobile() == true){
                return view('programs.book_virtual_exibicao_new.mobile.book_lisos')->with($result);
            }else{
                return view('programs.book_virtual_exibicao_new.book_lisos')->with($result);
            }
        }
    }

    public function gerarLinkBusca(BookVirtualFiltroPrincipalRequest $request){
        $fields = $request->only(
            'grupo',
            'marca',
            'linha',
            'codigo_produto',
            'tipo_venda',
            'codigos_produtos',
            'codigo_desenho',
            'desenho_produtos',
            'busca_navegacao',
            'grupo_navegacao',
            'campanha'
        );

        if(!empty($fields['grupo']) && empty($fields['campanha'])){
            $produtoGrupo = ProdutoGrupo::where('descricao', $fields['grupo'])->first();
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'link' => route('book_virtual_exibicao_new.exibicao', [
                        'id' => 'busca_avancada',
                        "grupo" => $produtoGrupo->id,
                        "codigo_produto" => 'grupo',
                        'codigos_produtos' => isset($fields['codigos_produtos']) ? 'true' : 'false',
                        'navegacao' => isset($fields['busca_navegacao']) ? $fields['busca_navegacao'] : 'false'
                    ])
                ]
            ];
        }
       
        elseif(!empty($fields['codigo_produto'])){
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'link' => route('book_virtual_exibicao_new.exibicao', [
                        'id' => 'busca_avancada',
                        "grupo" => 'codigo',
                        "codigo_produto" => $fields['codigo_produto'],
                        'codigos_produtos' => isset($fields['codigos_produtos']) ? 'true' : 'false',
                        'navegacao' => isset($fields['busca_navegacao']) ? $fields['busca_navegacao'] : 'false'
                    ])
                ]
            ];
        }elseif(!empty($fields['marca'])){
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'link' => route('book_virtual_exibicao_new.busca', [
                        'segmentos_id' => 'busca_avancada',
                        'marca' => $fields['marca'],
                        'linha' => 'false',
                        'navegacao' => $fields['marca']
                    ])
                ]
            ];
        }
        elseif(!empty($fields['linha'])){
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'link' => route('book_virtual_exibicao_new.busca', [
                        'segmentos_id' => 'busca_avancada',
                        'marca' => 'false',
                        'linha' => $fields['linha'],
                        'navegacao' => $fields['linha']
                    ])
                ]
            ];
        }
        elseif(!empty($fields['codigos_produtos'])){
            $buscaDesenhoTemp = New BuscaDesenhoTemp;
            $buscaDesenhoTemp->filtro = encrypt($fields);
            $buscaDesenhoTemp->save();

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'link' => route('book_virtual_exibicao_new.exibicao', [
                        'id' => 'busca_avancada',
                        "grupo" => $fields['grupo_navegacao'],
                        "codigo_produto" => 'false',
                        'codigos_produtos' => isset($fields['codigos_produtos']) ? 'true' : 'false',
                        'navegacao' => !empty($fields['busca_navegacao']) ? $fields['busca_navegacao'] : 'false'
                    ])
                ]
            ];
        }

        if(!empty($fields['campanha']) && empty($fields['codigos_produtos'])){
            $campanhas = Campanha::find($fields['campanha']);
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [ 
                    'link' => route('book_virtual_exibicao_new.busca', [
                        'segmentos_id' => 'busca_avancada',
                        'marca' => 'false',
                        'linha' => 'false',
                        'navegacao' => $campanhas->nome
                    ])
                ]
            ];
        }

        if(!empty($fields['grupo']) && !empty($fields['campanha'])){
            $produtoGrupo = ProdutoGrupo::where('descricao', $fields['grupo'])->first();
            $campanhas = Campanha::find($fields['campanha']);
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'link' => route('book_virtual_exibicao_new.exibicao', [
                        'id' => 'busca_avancada',
                        "grupo" => $produtoGrupo->id,
                        "codigo_produto" => 'grupo',
                        'codigos_produtos' => isset($fields['codigos_produtos']) ? 'true' : 'false',
                        'navegacao' => $campanhas->nome
                    ])
                ]
            ];
        }

        return response()->json($response,200);
    }


    public function modalAdicionar(Request $request){
        $campo = $request->only(
            'produto_codigo',
            'carrinho',
            'id',
            'dados_cliente_id',
            'visualizar_carrinho',
            'transportadora_redespacho_nome',
            'transportadora_redespacho_tipo_frete',
            'informar_cliente'
        );

        if(isset($campo['id']) && !empty($campo['id'])){
            $CarrinhoCompra = CarrinhoCompra::where('pedido_id', $campo['id'])->exists();
            
            if($CarrinhoCompra == false){
                $CarrinhoCompraObj = CarrinhoCompra::where('finalizado', false)
                ->where('created_by', Auth::id())
                ->orderBy('id', 'desc')
                ->first();

                $request = new Request([
                    'id' => $CarrinhoCompraObj->pedido_id,
                    'carrinho' => true
                ]);
            }
        }
        
        $PedidoPortalController = new PedidoPortalController;
        $dados = $PedidoPortalController->formAdd($request);
       
        if(isset($campo['produto_codigo'])) {
            $dados['produto_codigo'] =  $campo['produto_codigo'];
        }else{
            $dados['produto_codigo'] = '';
        }

        $dados['dados_cliente_id'] = isset($campo['dados_cliente_id']) ? $campo['dados_cliente_id'] : null;
        $dados['informar_cliente'] = isset($campo['informar_cliente']) ? $campo['informar_cliente'] : '';
       
        unset($dados['pedido']['tipo_venda_lista']['pedido_futuro_venda']);
        unset($dados['pedido']['tipo_venda_lista']['pedido_futuro_triangular']);
        unset($dados['pedido']['tipo_venda_lista']['pedido_orgaopublico']);
        unset($dados['pedido']['tipo_venda_lista']['pedido_pilotagem']);
        unset($dados['pedido']['tipo_venda_lista']['remessa_faturamento']);

        $dadosClientePedido = DadosClientePedido::with('cliente', 'clienteContaEOrdem')
            ->find($dados['dados_cliente_id']);

        try{
            if(empty($dados['produto_codigo']) && !empty($dadosClientePedido->id) && $campo['visualizar_carrinho'] != true){
                throw new \Exception;
            }
        }catch (\Exception $e) {
            $response  = [
                'status' => 'error',
                'message' => 'Nenhum produto selecionado!',
                'error' => $e->getMessage(),
                'response' => '',
            ];

            return response()->json($response, 422);
        }

        $dados['produto'] = [];
    
        if(!empty($dados['produto_codigo']) && !empty($dadosClientePedido->id)){
            $request = new Request([
                'in_codigos' => [$dados['produto_codigo']],
                'book' => false,
                'cliente' => true,
                'codigo_cliente' => $dadosClientePedido->cliente_codigo,
                'condicao_pagamento' => $dadosClientePedido->condicao_pagamento,
                'metragem_exata' => $dadosClientePedido->metragem_exata,
                'transportadora_redespacho_tipo_frete' => isset($campo['transportadora_redespacho_tipo_frete']) ? $campo['transportadora_redespacho_tipo_frete'] : '',
                'transportadora_redespacho_nome' => isset($campo['transportadora_redespacho_nome']) ? $campo['transportadora_redespacho_nome'] : ''

            ]);
    
            $estoquePrecos = $this->filtroEstoquePrecos($request);
    
            try{
                $json = json_decode($estoquePrecos);
                if($json == null){
                    if($estoquePrecos->getData()->status == 'error'){
                        throw new \Exception;
                    }
                }
                
            } catch (\Exception $e) {
                $response  = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade!<br/>Tente novamente!',
                    'error' => $estoquePrecos->getData()->error,
                    'response' => '',
                ];
    
                return response()->json($response, 422);
                
            }
    
            if($estoquePrecos->getData()->response->saida == false){
                $response = [
                    "status" => 'success',
                    "message" => '',
                    "error" => [],
                    "response" => [
                        'estoque' => false
                    ]
                ];
                return response()->json($response, 200);
            }else{ 

                foreach($estoquePrecos->getData()->response->precos as $value){
                    $dados['produto'][] = [
                        'estabelecimento_tabela' => $value->estabelecimento_tabela,
                        'estabelecimento' => $value->estabelecimento,
                        'codigo_produto' => $value->codigo_produto,
                        'preco' => $value->preco,
                        'estoque' => $value->estoque
                    ];
                }
            }
        }
        
        if(!empty($campo['dados_cliente_id'])){
        
            $dados['pedido']['id'] = empty($campo['id']) ? $dadosClientePedido->id : $campo['id'];
            $dados['pedido']['cliente_codigo'] = $dadosClientePedido->cliente_codigo;
            $dados['pedido']['cliente_descricao'] = $dadosClientePedido->cliente->nome.' - '.$dadosClientePedido->cliente->cpf_cnpj;
            $dados['pedido']['codigo_cliente_conta_e_ordem'] = $dadosClientePedido->codigo_cliente_conta_e_ordem;
            $dados['pedido']['cliente_conta_e_ordem_descricao'] = isset($dadosClientePedido->clienteContaEOrdem) ? $dadosClientePedido->clienteContaEOrdem->nome.' - '.$dadosClientePedido->clienteContaEOrdem->cpf_cnpj : '';
            $dados['pedido']['transportadora_codigo'] = $dadosClientePedido->transportadora_codigo;
            $dados['pedido']['transportadora_redespacho_codigo'] = $dadosClientePedido->transportadora_redespacho_codigo;
            $dados['pedido']['data_previsao_entrega'] = Carbon::parse($dadosClientePedido->data_previsao_entrega)->format('d/m/Y');
            $dados['pedido']['tipo_frete'] = $dadosClientePedido->tipo_frete;
            $dados['pedido']['valor_frete'] = $dadosClientePedido->valor_frete;
            $dados['pedido']['valor_frete_redespacho'] = $dadosClientePedido->valor_frete_redespacho;
            $dados['pedido']['condicao_pagamento_codigo'] = $dadosClientePedido->condicao_pagamento;
            
            $CondicoesPagamentoWeb = CondicoesPagamentoWeb::where('id', $dadosClientePedido->condicao_pagamento)->where('ativo', true)->first();
            $dados['pedido']['condicao_pagamento_descricao'] = is_null($CondicoesPagamentoWeb) ? '' : $CondicoesPagamentoWeb->descricao;
            $dados['pedido']['nome_comprador'] = $dadosClientePedido->nome_contato;
            $dados['pedido']['email_comprador'] = $dadosClientePedido->email_contato;
            $dados['pedido']['no_pedido_compra'] = $dadosClientePedido->no_pedido_compra;
            $dados['pedido']['bater_amostra'] = $dadosClientePedido->bater_amostra;
            $dados['pedido']['incluir_cartelas'] = $dadosClientePedido->incluir_cartelas;
            $dados['pedido']['metragem_exata'] = $dadosClientePedido->incluir_cartelas;
            $dados['pedido']['cliente_telefone'] = $dadosClientePedido->cliente_telefone;
            $dados['pedido']['tipo_venda'] = $dadosClientePedido->tipo_venda;
            $visualizar_carrinho = isset($campo['visualizar_carrinho']) ? $campo['visualizar_carrinho'] : false;
         
            if($visualizar_carrinho == true){
                return $dados;
            }

            return view('programs.book_virtual_exibicao_new.modal.adicionar')->with($dados);
        }
        elseif(!empty($campo['id'])){
            return view('programs.book_virtual_exibicao_new.modal.adicionar')->with($dados);
        }
        else{
            return view('programs.book_virtual_exibicao_new.modal.adicionar')->with($dados);
        }
    }

    public function filtroEstoquePrecos(Request $request){
        $campos = $request->only(
            'codigo_cliente',
            'nome_cliente',
            'transportadora',
            'transportadora_tipo_frete',
            'condicao_pagamento_descr',
            'data_previsao_entrega',
            'dados_cliente',
            'condicao_pagamento',
            'transportadora_redespacho_nome',
            'transportadora_redespacho_tipo_frete',
            'valor_frete_redespacho',
            'nome_contato',
            'email_contato',
            'no_pedido_compra',
            'cliente_telefone',
            'enfestar',
            'bater_amostra',
            'incluir_cartelas',
            'metragem_exata',
            'produto_codigo',
            'estabelecimento',
            'tipo_venda',
            'book',
            'in_codigos',
            'cliente'
        );

        $estabelecimentos = returnEmpresasNasajonView();

        $estoquePrecos = [];

        if($campos['cliente'] == false){
            $produtoEstoque = ProdutosEstoque::select('codigo_produto',DB::raw('
                (estoque - reserva) as estoque_total,
                (compras_aberto) as compras_total'))
            ->whereIn('codigo_produto', $campos['in_codigos'])
            ->get();  

            foreach($produtoEstoque as $produto){
                if(!isset($estoquePrecos[$produto->codigo_produto])){
                    $estoquePrecos[$produto->codigo_produto] = [
                        'codigo_produto' => $produto->codigo_produto,
                        'estoque_total' => 0,
                        'compras' => 0
                    ];   
                }

                $estoquePrecos[$produto->codigo_produto]['estoque_total'] += $produto->estoque_total > 0 ? $produto->estoque_total : 0;
                $estoquePrecos[$produto->codigo_produto]['compras'] += $produto->compras_total;
            }
            return collect($estoquePrecos);
        }else{
            $preco_frete = $this->fretePreco($campos);
            $frete = $preco_frete['preco'];
            $ClienteNasajon = ClienteNasajon::where('codigo', $campos['codigo_cliente'])->first();
            $estadoObj = CepEstado::find($ClienteNasajon->uf);
            $codigo_produto = isset($campos['in_codigos']) ? $campos['in_codigos'] : [$campos['produto_codigo']];

            $produtoEstoque = ProdutosEstoque::select('*', DB::raw('
                (estoque - reserva) as estoque_total,
                (compras_aberto) as compras_total
            '))
            ->where('estabelecimento', '!=', 20)
            ->whereIn('codigo_produto', $codigo_produto)->get();  
            
            foreach($produtoEstoque as $produto){
                switch ($produto->estabelecimento) {
                    case "03":
                    case 3:
                        $origem  = "RO";
                        break;
                    case "04":
                    case 4:
                        $origem = "TO";
                        break;
                    default:
                        $origem = 'SP';
                        break;
                }
    
                $paramostr_preco = [];
                $paramostr_preco['origem'] = $origem;
                $paramostr_preco['estado'] = $ClienteNasajon->uf;
                $paramostr_preco['moeda'] = 'real';
                $paramostr_preco['promocao'] = false;
                $paramostr_preco['cliente'] = $ClienteNasajon->codigo;
                $paramostr_preco['codigo_vendedor'] = $ClienteNasajon->codigo_vendedor;
                $paramostr_preco['estabelecimento'] = (integer)$produto->estabelecimento;
    
                $paramostr_preco['frete'] = $frete;
                $paramostr_preco['regiao'] = $estadoObj->regiao;
                $paramostr_preco['tipo_cliente'] = (
                    $ClienteNasajon->inscricaoestadual == 'ISENTO' ||
                    intval($ClienteNasajon->indicadorinscricaoestadual) == 2 ||
                    intval($ClienteNasajon->indicadorinscricaoestadual) == 9
                ) ? 'isento' : 'juridico';
                
                $razao_cnpj_textil = '06311274';
    
                $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $ClienteNasajon->cpf_cnpj);
                $raiz_cnpj = substr($raiz_cnpj, 0, 8);
    
                if(
                    $razao_cnpj_textil === $raiz_cnpj
                ){
                    $paramostr_preco['prazo_medio'] = 0;
                } else {
                    $condicao_pagamento = !empty($campos['condicao_pagamento']) ? $campos['condicao_pagamento'] : $campos['condicao_pagamento_descr'];
                    $CondicoesPagamentoWeb = CondicoesPagamentoWeb::find($condicao_pagamento);
                    $paramostr_preco['prazo_medio'] = $CondicoesPagamentoWeb->media ?? 0;
                }
    
                $listaDePrecosObj = new ListagemDePrecosController();
    
                $paramostr_preco['produto'] = $produto->codigo_produto;
                $filter_request = new ListaDePrecosRequest($paramostr_preco);
                $lista_preco_result = $listaDePrecosObj->filter($filter_request, true, false, true);
                $lista_preco_result = reset($lista_preco_result);
                $preco_unitario = empty($lista_preco_result['coluna_a'])? '' : parserNumber($lista_preco_result['coluna_a']);
    
                if(isset($campos['metragem_exata']) && $campos['metragem_exata'] === true){
                    $preco_unitario = $preco_unitario * (1 + ($this->porcentagem_metragem_exata / 100));
                }
    
                $CarrinhoCompraObj = CarrinhoCompra::with(['carrinhoCompraItens' => function($query) use ($produto){
                        $query->where('produto_codigo', $produto->codigo_produto);
                    }])
                    ->where('estabelecimento', $produto->estabelecimento)
                    ->where('finalizado', false)
                    ->where('created_by', Auth::id())
                    ->first();
                
                $CarrinhoCompra = isset($CarrinhoCompraObj->carrinhoCompraItens) ? $CarrinhoCompraObj->carrinhoCompraItens->count('id') : 0;

                if($CarrinhoCompra == 0 && $campos['book'] == false){

                    $estoquePrecos[] = [
                        'estabelecimento' => $produto->estabelecimento,
                        'estabelecimento_tabela' => $estabelecimentos[(integer)$produto->estabelecimento],
                        'codigo_produto' => $produto->codigo_produto,
                        'preco' => parserValor($preco_unitario),
                        'estoque' => $produto->estoque_total > 0 ? parserQtd($produto->estoque_total) : parserQtd(0)
                    ];
                }
                if($CarrinhoCompra == 0 && $campos['book'] == true){
                    if(!isset($estoquePrecos[$produto->codigo_produto])){
                        $estoquePrecos[$produto->codigo_produto] = [
                            'codigo_produto' => $produto->codigo_produto,
                            'preco' => parserValor($preco_unitario),
                            'estoque_total' => 0,
                            'compras' => 0
                        ];
                    }
                    $estoquePrecos[$produto->codigo_produto]['estoque_total'] += $produto->estoque_total > 0 ? $produto->estoque_total : 0;
                    $estoquePrecos[$produto->codigo_produto]['compras'] += $produto->compras_total;
                }
            }
        }
                
        if($campos['book'] == true){
           return collect($estoquePrecos);
        }

        foreach($estoquePrecos as $key => $value){
           if(parserNumber($estoquePrecos[$key]['estoque']) <= 0){
               unset($estoquePrecos[$key]);
           }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'transportadora' => true,
                'precos' => $estoquePrecos,
                'saida' => !empty($estoquePrecos) ?  true : false
            ]
        ];
        return response()->json($response, 200);
    }

    private function fretePreco($campos){
        $return = [
            'preco' => '',
            'frete' => ''
        ];

        if(!empty($campos['transportadora_redespacho_nome'])){

            $return['preco'] = 'FOB';

            if($campos['transportadora_redespacho_tipo_frete'] =='P'){
                $return['frete'] = 'CIF';
            }
            else{
                $return['frete'] = 'FOB';
            }
        }
        elseif(!empty($campos['transportadora_redespacho_tipo_frete']) && empty($campos['transportadora_redespacho_nome'])){
            $ClienteNasajon = ClienteNasajon::where('codigo', $campos['codigo_cliente'])->first();
            $estabelecimentoCidadeFobObj = EstabelecimentoCidadeFob::where('uf', $ClienteNasajon->uf)
            ->where('cidade', $ClienteNasajon->cidade)
            ->first();

            if ($campos['transportadora_redespacho_tipo_frete'] =='P' && is_null($estabelecimentoCidadeFobObj))  {
                $return['frete'] = 'CIF';
                $return['preco'] = 'CIF';
            }
            elseif($campos['transportadora_redespacho_tipo_frete'] == 'P' && !is_null($estabelecimentoCidadeFobObj))  {
                $return['frete'] = 'CIF';
                $return['preco'] = 'FOB';
            }
            else{
                $return['frete'] = 'FOB';
                $return['preco'] = 'FOB';
            }
        }
        return $return;
    }

    public function adicionarCarrinho(BookVirtualCarrinhoAdicionarRequestNew $request){
        $campos = $request->only(
            'id',
            'codigo_cliente',
            'nome_cliente',
            'transportadora_redespacho',
            'transportadora',
            'transportadora_tipo_frete',
            'condicao_pagamento_descr',
            'data_previsao_entrega',
            'dados_cliente',
            'condicao_pagamento',
            'transportadora_redespacho_nome',
            'transportadora_redespacho_tipo_frete',
            'valor_frete_redespacho',
            'nome_contato',
            'email_contato',
            'no_pedido_compra',
            'cliente_telefone',
            'enfestar',
            'bater_amostra',
            'incluir_cartelas',
            'metragem_exata',
            'produto_codigo',
            'estabelecimento',
            'estabelecimento_pedido',
            'quantidade',
            'preco_unitario',
            'pedido',
            'tipo_venda',
            'observacao',
            'adicionar_transportadora',
            'presencial',
            'cartao'
        );

        $CarrinhoCompra = CarrinhoCompra::with('carrinhoCompraItens')
            ->where('estabelecimento', $campos['estabelecimento'])
            ->where('finalizado', false)
            ->where('created_by', Auth::id())
            ->first();
            

        $campos['estabelecimento'] =  (integer)$campos['estabelecimento'];

        if(empty($campos['id']) || empty($CarrinhoCompra->id)){
            $pedido_anterior = CarrinhoCompra::where('finalizado', false)
            ->where('created_by', Auth::id())
            ->orderBy('id', 'desc')
            ->first();

            $estabelecimento = isset($pedido_anterior->estabelecimento) ? $pedido_anterior->estabelecimento : '0'.$campos['estabelecimento'];

            if(empty($campos['transportadora']) && !isset($pedido_anterior->estabelecimento) ||
                $estabelecimento != '0'.$campos['estabelecimento'] && $campos['adicionar_transportadora'] != 'true'){
                    $response  = [
                        'status' => 'success',
                        'message' => '',
                        'error' => '',
                        'response' => [
                            'transportadora' => false
                        ],
                    ];
    
                return response()->json($response, 200);
            }
            
            $campos['id'] = '';
            $PedidoPortalController = new PedidoPortalController;
            $retornoPedidoPortal = $PedidoPortalController->salvarNasajon($campos);

            try{
                if($retornoPedidoPortal->getData()->error->error === true){
                    throw new \Exception;
                }
            } catch (\Exception $e) {
                return $retornoPedidoPortal;
            }

            $pedido = $retornoPedidoPortal->getData()->response->pedido;
            $campos['produto_codigo_base'] = null;
            $campos['produto_codigo_desenho'] = null;

            $PedidoItemPortalController = new PedidoItemPortalController;
            $pedidoPortal = PedidoPortal::with(['usuario_detalhes', 'condicao_pagamento_detalhes', 'itens_pedido', 'itens_pedido.especificacoes'])->find($pedido);
            $retornoPedidoItemPortal = $PedidoItemPortalController->adicionarProdutoNasajon($pedidoPortal, $campos);
            
            try{
                if(!is_array($retornoPedidoItemPortal)){
                    if($retornoPedidoItemPortal->getData()->status === 'error'){
                        throw new \Exception;
                    }
                }
            } catch (\Exception $e) {
                return $retornoPedidoItemPortal;
            }
        }else{
            $pedido = $CarrinhoCompra->pedido_id;
            $campos['produto_codigo_base'] = null;
            $campos['produto_codigo_desenho'] = null;

            $PedidoItemPortalController = new PedidoItemPortalController;
            $pedidoPortal = PedidoPortal::with(['usuario_detalhes', 'condicao_pagamento_detalhes', 'itens_pedido', 'itens_pedido.especificacoes'])->find($pedido);
            $retornoPedidoItemPortal = $PedidoItemPortalController->adicionarProdutoNasajon($pedidoPortal, $campos);
            
            try{
                if(!is_array($retornoPedidoItemPortal)){
                    if($retornoPedidoItemPortal->getData()->status === 'error'){
                        throw new \Exception;
                    }
                }
            } catch (\Exception $e) {
                return $retornoPedidoItemPortal;
            }
            
        }

        if(!empty($CarrinhoCompra->id)){

            $CarrinhoCompraItemObj = new CarrinhoCompraItem;
            $CarrinhoCompraItemObj->carrinho_compra_id = $CarrinhoCompra->id;
            $CarrinhoCompraItemObj->pedido_item_id = $retornoPedidoItemPortal['id'];
            $CarrinhoCompraItemObj->produto_quantidade = parserNumber($campos['quantidade']);
            $CarrinhoCompraItemObj->produto_codigo = $campos['produto_codigo'];
            $CarrinhoCompraItemObj->produto_preco = parserNumber($campos['preco_unitario']);
            $CarrinhoCompraItemObj->created_by = Auth::id();
            $CarrinhoCompraItemObj->save();
        }else{

            $DadosClientePedidoObj = DadosClientePedido::where('cliente_codigo', $campos['codigo_cliente'])
            ->where('created_by', Auth::id())
            ->first();
            
            $CarrinhoCompraObj = new CarrinhoCompra;
            $CarrinhoCompraObj->estabelecimento = '0'.$campos['estabelecimento'];
            $CarrinhoCompraObj->pedido_id = $pedido;
            $CarrinhoCompraObj->dados_cliente_pedido_id = $DadosClientePedidoObj->id;
            $CarrinhoCompraObj->transportadora_codigo = $campos['transportadora'];
            $CarrinhoCompraObj->transportadora_redespacho = $campos['transportadora_redespacho'];
            $CarrinhoCompraObj->created_by = Auth::id();
            $CarrinhoCompraObj->save();

            $CarrinhoCompraItemObj = new CarrinhoCompraItem;
            $CarrinhoCompraItemObj->carrinho_compra_id = $CarrinhoCompraObj->id;
            $CarrinhoCompraItemObj->pedido_item_id = $retornoPedidoItemPortal['id'];
            $CarrinhoCompraItemObj->produto_quantidade = parserNumber($campos['quantidade']);
            $CarrinhoCompraItemObj->produto_codigo = $campos['produto_codigo'];
            $CarrinhoCompraItemObj->produto_preco = parserNumber($campos['preco_unitario']);
            $CarrinhoCompraItemObj->created_by = Auth::id();
            $CarrinhoCompraItemObj->save();
            
        }

        $request = new Request([
            'in_codigos' => [$campos['produto_codigo']],
            'book' => false,
            'cliente' => true,
            'codigo_cliente' => $campos['codigo_cliente'],
            'condicao_pagamento' => $campos['condicao_pagamento'],
            'metragem_exata' => isset($campos['metragem_exata']) ? $campos['metragem_exata'] : null
        ]);

        $estoquePrecos = $this->filtroEstoquePrecos($request);

        try{
            $json = json_decode($estoquePrecos);
            if($json == null){
                if($estoquePrecos->getData()->status == 'error'){
                    throw new \Exception;
                }
            }
            
        } catch (\Exception $e) {
            $response  = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade!<br/>Tente novamente!',
                'error' => $estoquePrecos->getData()->error,
                'response' => '',
            ];

            return response()->json($response, 422);
            
        }

        return $estoquePrecos;
    }

    public function modalAdicionarTransportadora(Request $request){
        return view('programs.book_virtual_exibicao_new.modal.adicionar_transportadora');
    }

    public function modalEditarTransportadora(Request $request){
        $campos = $request->only('id');

        try{
            $id = decrypt($campos['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $CarrinhoCompra = CarrinhoCompra::with('transportadora', 'transportadoraRedespacho')
        ->find($id);

        if(isset($CarrinhoCompra->transportadora)){
            $transportadora =  str_replace(' - __.___.___/____-__', '', trim($CarrinhoCompra->transportadora->nome) . ' - ' . trim($CarrinhoCompra->transportadora->cnpj));
        }else{
            $transportadora = '';
        }

        if(!empty($CarrinhoCompra->transportadora_redespacho)){
            if(isset($CarrinhoCompra->transportadoraRedespacho)){
                $transportadora_redespacho = str_replace(' - __.___.___/____-__', '', trim($CarrinhoCompra->transportadoraRedespacho->nome) . ' - ' . trim($CarrinhoCompra->transportadoraRedespacho->cnpj));
            }else{
                $transportadora_redespacho = '';
            }
        }else{
            $transportadora_redespacho = '';
        }

        $itens = [
            'id' => encrypt($CarrinhoCompra->id),
            'transportadora_nome' => $transportadora,
            'transportadora_redespacho_nome' => $transportadora_redespacho,
            'transportadora_codigo' => $CarrinhoCompra->transportadora_codigo,
            'transportadora_resdespacho_codigo' => $CarrinhoCompra->transportadora_redespacho,
        ];  

        return view('programs.book_virtual_exibicao_new.modal.editar_transportadora')->with($itens);
    }

    public function editarTransportadora(BookVirtualEditarTransportadoraRequestNew $request){
        $campos = $request->only('id', 'transportadora_codigo', 'transportadora_resdespacho_codigo');

        try{
            $id = decrypt($campos['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $CarrinhoCompraObj = CarrinhoCompra::find($id);

        $request = new Request([
            'id' => $CarrinhoCompraObj->pedido_id,
            'carrinho' => true
        ]);
    
        $PedidoPortalController = new PedidoPortalController;
        $dados = $PedidoPortalController->formAdd($request);

        $dados['pedido']['nome_cliente'] = $dados['pedido']['cliente_descricao'];
        $dados['pedido']['transportadora_tipo_frete'] = $dados['pedido']['tipo_frete'];
        $dados['pedido']['transportadora_redespacho_tipo_frete'] = $dados['pedido']['tipo_frete_redespacho'];
        $dados['pedido']['transportadora'] = $campos['transportadora_codigo'];
        $dados['pedido']['transportadora_redespacho'] = $campos['transportadora_resdespacho_codigo'];
        $dados['pedido']['condicao_pagamento'] = $dados['pedido']['condicao_pagamento_codigo'];
        $dados['pedido']['nome_contato'] = $dados['pedido']['nome_comprador'];
        $dados['pedido']['email_contato'] = $dados['pedido']['email_comprador'];

        $PedidoPortalController = new PedidoPortalController;
        $retornoPedidoPortal = $PedidoPortalController->salvarNasajon($dados['pedido']);

        try{
            if($retornoPedidoPortal->getData()->error->error === true){
                throw new \Exception;
            }
        } catch (\Exception $e) {
            return $retornoPedidoPortal;
        }

        $CarrinhoCompraObj->transportadora_codigo = $campos['transportadora_codigo'];
        $CarrinhoCompraObj->transportadora_redespacho = $campos['transportadora_resdespacho_codigo'];
        $CarrinhoCompraObj->updated_by =  Auth::id();
        $CarrinhoCompraObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);

    }

    public function adicionarCliente(BookVirtualAdicionarClienteRequestNew $request){
        $campos = $request->only(
            'codigo_cliente',
            'nome_cliente',
            'transportadora',
            'transportadora_tipo_frete',
            'data_previsao_entrega',
            'dados_cliente',
            'condicao_pagamento',
            'condicao_pagamento_descr',
            'transportadora_redespacho',
            'transportadora_redespacho_tipo_frete',
            'valor_frete_redespacho',
            'valor_frete',
            'nome_contato',
            'email_contato',
            'no_pedido_compra',
            'cliente_telefone',
            'bater_amostra',
            'incluir_cartelas',
            'metragem_exata',
            'tipo_venda',
            'codigo_cliente_conta_e_ordem'
        );

        $DadosClientePedidoObj = new DadosClientePedido;
        $DadosClientePedidoObj->cliente_codigo = $campos['codigo_cliente'];
        $DadosClientePedidoObj->tipo_frete = $campos['transportadora_tipo_frete'];
        $DadosClientePedidoObj->condicao_pagamento = !empty($campos['condicao_pagamento']) ? $campos['condicao_pagamento'] : $campos['condicao_pagamento_descr'];
        $DadosClientePedidoObj->data_previsao_entrega = $campos['data_previsao_entrega'];
        $DadosClientePedidoObj->tipo_frete_redespacho = $campos['transportadora_redespacho_tipo_frete'];
        $DadosClientePedidoObj->valor_frete_redespacho = $campos['valor_frete_redespacho'];
        $DadosClientePedidoObj->valor_frete = $campos['valor_frete'];
        $DadosClientePedidoObj->nome_contato = $campos['nome_contato'];
        $DadosClientePedidoObj->email_contato = $campos['email_contato'];
        $DadosClientePedidoObj->no_pedido_compra = $campos['no_pedido_compra'];
        $DadosClientePedidoObj->cliente_telefone = $campos['cliente_telefone'];
        $DadosClientePedidoObj->bater_amostra = isset($campos['bater_amostra']) ? true : false;
        $DadosClientePedidoObj->incluir_cartelas = isset($campos['incluir_cartelas']) ? true : false;
        $DadosClientePedidoObj->metragem_exata = isset($campos['metragem_exata']) ? true : false;
        $DadosClientePedidoObj->tipo_venda = $campos['tipo_venda'];
        $DadosClientePedidoObj->codigo_cliente_conta_e_ordem = $campos['codigo_cliente_conta_e_ordem'];
        $DadosClientePedidoObj->created_by = Auth::id();
        $DadosClientePedidoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'cliente' => $DadosClientePedidoObj->id
            ]
        ];
        return response()->json($response, 200);

    }

    public function editarCliente(BookVirtualEditarClienteRequestNew $request){
        $campos = $request->only(
            'dados_cliente_id',
            'codigo_cliente',
            'nome_cliente',
            'transportadora',
            'transportadora_tipo_frete',
            'data_previsao_entrega',
            'dados_cliente',
            'condicao_pagamento',
            'transportadora_redespacho',
            'transportadora_redespacho_tipo_frete',
            'valor_frete_redespacho',
            'valor_frete',
            'nome_contato',
            'email_contato',
            'no_pedido_compra',
            'cliente_telefone',
            'bater_amostra',
            'incluir_cartelas',
            'metragem_exata',
            'codigo_cliente_conta_e_ordem',
            'tipo_venda'
        );

        $DadosClientePedidoObj = DadosClientePedido::find($campos['dados_cliente_id']);
        $DadosClientePedidoObj->cliente_codigo = $campos['codigo_cliente'];
        $DadosClientePedidoObj->tipo_frete = $campos['transportadora_tipo_frete'];
        $DadosClientePedidoObj->condicao_pagamento = $campos['condicao_pagamento'];
        $DadosClientePedidoObj->data_previsao_entrega = $campos['data_previsao_entrega'];
        $DadosClientePedidoObj->tipo_frete_redespacho = $campos['transportadora_redespacho_tipo_frete'];
        $DadosClientePedidoObj->valor_frete_redespacho = $campos['valor_frete_redespacho'] == '0,00' ? null : parserNumber($campos['valor_frete_redespacho']);
        $DadosClientePedidoObj->valor_frete = $campos['valor_frete'];
        $DadosClientePedidoObj->nome_contato = $campos['nome_contato'];
        $DadosClientePedidoObj->email_contato = $campos['email_contato'];
        $DadosClientePedidoObj->no_pedido_compra = $campos['no_pedido_compra'];
        $DadosClientePedidoObj->cliente_telefone = $campos['cliente_telefone'];
        $DadosClientePedidoObj->bater_amostra = isset($campos['bater_amostra']) ? true : false;
        $DadosClientePedidoObj->incluir_cartelas = isset($campos['incluir_cartelas']) ? true : false;
        $DadosClientePedidoObj->metragem_exata = isset($campos['metragem_exata']) ? true : false;
        $DadosClientePedidoObj->tipo_venda =  $campos['tipo_venda'];
        $DadosClientePedidoObj->codigo_cliente_conta_e_ordem = $campos['codigo_cliente_conta_e_ordem'];
        $DadosClientePedidoObj->updated_by = Auth::id();
        $DadosClientePedidoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);

    }

    public function modalEditarCarrinho(Request $request){
        
        $CarrinhoCompraObj = CarrinhoCompra::with(
         'pedido',
         'pedido.condicao_pagamento_detalhes',
         'pedido.itens_pedido',
         'dadosClientePedido.cliente',
         'carrinhoCompraItens'
         )
            ->where('created_by', Auth::id())
            ->where('finalizado', false)
            ->get();
        
        $dadosClientePedido = DadosClientePedido::where('created_by', Auth::id())
        ->first();

        if(empty($dadosClientePedido->id)){
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'itens' => false,
                ]
            ];
            return response()->json($response, 200);
        }
        
        $dados = [];
        $produtos = [];
        $valor_total = 0;

        $estabelecimentos = returnEmpresasNasajonView();

       if(count($CarrinhoCompraObj) > 0){
            foreach($CarrinhoCompraObj as $pedido){

                $cliente_pedido = 'Contribuinte';
                if (
                    $pedido->dadosClientePedido->cliente->inscricaoestadual == 'ISENTO' ||
                    intval($pedido->dadosClientePedido->cliente->indicadorinscricaoestadual) == 2 ||
                    intval($pedido->dadosClientePedido->cliente->indicadorinscricaoestadual) == 9
                ){
                    $cliente_pedido = 'Isento';
                }
                $ClienteBionexoObj = ClienteBionexo::where('cpf_cnpj', $pedido->dadosClientePedido->cliente->cpf_cnpj)->exists();
                if($ClienteBionexoObj == true){
                    $cliente_pedido .= ' - Bionexo';
                }

                $dados[] =[
                    'estabelecimento' => $estabelecimentos[(integer)$pedido->estabelecimento],
                    'pedido' => $pedido->pedido_id,
                    'id' => encrypt($pedido->id),
                    'uf' => $pedido->dadosClientePedido->cliente->uf,
                    'frete' => $pedido->pedido->tipo_frete != 'P' ? 'FOB' : 'CIF',
                    'cliente_pedido' => $cliente_pedido,
                    'prazo_medio' => is_null($pedido->pedido->condicao_pagamento_detalhes) ? '' : $pedido->pedido->condicao_pagamento_detalhes->media,
                    'valor' => parserValor($pedido->pedido->itens_pedido->sum('valor_total'))
                ];
                $valor_total += $pedido->pedido->itens_pedido->sum('valor_total');

                foreach($pedido->carrinhoCompraItens as $item){
                    $produtos[] =[
                        'id' => $item->id,
                        'estabelecimento' => $estabelecimentos[(integer)$pedido->estabelecimento],
                        'produto_codigo' => $item->produto_codigo,
                        'quantidade' => parserQtd($item->produto_quantidade),
                        'preco' => parserValor($item->produto_preco),
                        'pedido_id' => $pedido->pedido_id
                    ];
                }
            } 
       }else{
            $dados[] =[
                'estabelecimento' => '',
                'pedido' => '',
                'id' => encrypt($dadosClientePedido->id),
                'uf' => '',
                'frete' => '',
                'cliente_pedido' => '',
                'prazo_medio' => '',
                'valor' => ''
            ];
       }
        
        
        $request_modal = new Request([
            'dados_cliente_id' => $dadosClientePedido->id,
            'carrinho' => true,
            'visualizar_carrinho' => true
        ]); 
 
        $dadosCliente = $this->modalAdicionar($request_modal);

        $dadosCliente['dados'] = $dados;
        $dadosCliente['filtro'] = $produtos;
        $dadosCliente['valor_total'] = $valor_total > 0 ? parserValor($valor_total) : '';

        return view('programs.book_virtual_exibicao_new.modal.editar')->with($dadosCliente);
    }

    public function filtroCarrinho(Request $request){
        $CarrinhoCompraObj = CarrinhoCompra::with('carrinhoCompraItens')
        ->where('created_by', Auth::id())
        ->where('finalizado', false)
        ->get();

        $produtos = [];
        $estabelecimentos = returnEmpresasNasajonView();

        foreach($CarrinhoCompraObj as $pedido){
            foreach($pedido->carrinhoCompraItens as $item)
            $produtos[] =[
                'id' => $item->id,
                'estabelecimento' => $estabelecimentos[(integer)$pedido->estabelecimento],
                'produto_codigo' => $item->produto_codigo,
                'quantidade' => parserQtd($item->produto_quantidade),
                'preco' => parserValor($item->produto_preco),
                'pedido_id' => $pedido->pedido_id
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'produto' => $produtos,
            ]
        ];
        return response()->json($response, 200);
    }

    public function atualizarItensCarrinho(Request $request){
        $campo = $request->only('id', 'quantidade', 'preco', 'valor_total');

        $quantidade = parserNumber($campo['quantidade']);
        $preco = parserNumber($campo['preco']);

        if($quantidade == 0 && $preco == 0){
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'itens' => false,
                ]
            ];
            return response()->json($response, 200);
        }

        $CarrinhoCompraItem = CarrinhoCompraItem::with('carrinhoCompras')
        ->find($campo['id']);

        $PedidoItemPortalController = new PedidoItemPortalController;
        $pedidoPortal = PedidoPortal::with('itens_pedido', 'usuario_detalhes', 'condicao_pagamento_detalhes')->find($CarrinhoCompraItem->carrinhoCompras->pedido_id);

        $campos['id'] = $CarrinhoCompraItem->pedido_item_id;
        $campos['pedido'] = $CarrinhoCompraItem->carrinhoCompras->pedido_id;
        $campos['produto_codigo_base'] = null;
        $campos['produto_codigo_desenho'] = null;
        $campos['estabelecimento'] = (integer)$CarrinhoCompraItem->carrinhoCompras->estabelecimento;
        $campos['produto_codigo'] = $CarrinhoCompraItem->produto_codigo;
        $campos['carrinho'] = true;

        if($quantidade > 0 && $preco == 0){
            $campos['quantidade'] =  $campo['quantidade'];
            $campos['preco_unitario'] = parserValor($CarrinhoCompraItem->produto_preco);
            
            $CarrinhoCompraItem->produto_quantidade = $quantidade;
            $CarrinhoCompraItem->updated_by = Auth::id();
            $CarrinhoCompraItem->save();
            
        }elseif($quantidade == 0 && $preco > 0){
            $campos['quantidade'] =  parserValor($CarrinhoCompraItem->produto_quantidade);
            $campos['preco_unitario'] =  $campo['preco'];
            
            $CarrinhoCompraItem->produto_preco = $preco;
            $CarrinhoCompraItem->updated_by = Auth::id();
            $CarrinhoCompraItem->save();
        }else{
            $campos['quantidade'] = $campo['quantidade'];;
            $campos['preco_unitario'] =  $campo['preco'];

            $CarrinhoCompraItem->produto_preco = $preco;
            $CarrinhoCompraItem->produto_quantidade = $quantidade;
            $CarrinhoCompraItem->updated_by = Auth::id();
            $CarrinhoCompraItem->save();
        }

        $retornoPedidoItemPortal = $PedidoItemPortalController->editarProdutoNasajon($pedidoPortal, $campos);

        if(!empty($retornoPedidoItemPortal->getData()->id)){
            $pedidoAtualizado = PedidoPortal::with('itens_pedido')->find($CarrinhoCompraItem->carrinhoCompras->pedido_id);

            $valor = $pedidoAtualizado->itens_pedido->sum('valor_total');
            $valor_total = parserNumber($campo['valor_total']) - $pedidoPortal->itens_pedido->sum('valor_total');
            $valor_total = $valor_total + $valor;
            
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'valor' => parserValor($valor),
                    'valor_total' => parserValor($valor_total)
                ]
            ];
            return response()->json($response, 200);
        }

        try{
            
            if($retornoPedidoItemPortal->getData()->status === 'error'){
                throw new \Exception;
            }
            
        } catch (\Exception $e) {
            return $retornoPedidoItemPortal;
        }
    }

    public function finalizarCarrinho(Request $request){
        $CarrinhoCompraObj = CarrinhoCompra::with('dadosClientePedido', 'carrinhoCompraItens')
        ->where('created_by', Auth::id())
        ->where('finalizado', false)
        ->get();

        foreach($CarrinhoCompraObj as $pedido){
            if($pedido->carrinhoCompraItens->count('id') == 0){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Existe pedido(s) sem nenhum produto!',
                    'errors' => ['Existe pedido(s) sem nenhum produto!'],
                    'response' => []
                ],422);
            }
        }

        $PedidoPortalController = new PedidoPortalController;

        foreach($CarrinhoCompraObj as $pedido){

            $carrinho =  true;

            $retornoPedidoPortal = $PedidoPortalController->processarPedidoPortal($pedido->pedido_id, $carrinho);

            try{
                if($retornoPedidoPortal->getData()->status === 'error'){
                    throw new \Exception;
                }
            } catch (\Exception $e) {
                return $retornoPedidoPortal;
            }

            for ($i=0; $i < 1; $i++) { 
                $pedido->dadosClientePedido->deleted_by = Auth::id();
                $pedido->dadosClientePedido->save();
                $pedido->dadosClientePedido->delete();
            }

            $pedido->finalizado = true;
            $pedido->updated_by = Auth::id();
            $pedido->save();
            
        } 
        
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function modalDeletarCarrinho(Request $request){
        $campo = $request->only('id');

        $CarrinhoCompraItem = CarrinhoCompraItem::with('produtoEspecificacao')
        ->find($campo['id']);

        $item = [
            'id' => encrypt($CarrinhoCompraItem->id),
            'descricao' => $CarrinhoCompraItem->produtoEspecificacao->descricao,
            'codigo' => $CarrinhoCompraItem->produto_codigo,
            'deletar_item' => true
        ];

        $pedido = [
            'id' => encrypt($CarrinhoCompraItem->carrinho_compra_id),
            'pedido' => '',
            'cliente' => '',
            'cancelar_pedido' => false,
        ];

        $carrinho = [
            'pedido' =>  '',
            'cliente' => '',
            'excluir_carrinho' => false
        ];

        $cliente = [
            'id' => '',
            'cliente' => '',
            'deletar_cliente' => false,
        ];

        return view('programs.book_virtual_exibicao_new.modal.deletar')->with([
            'pedido' => $pedido,
            'item' => $item,
            'carrinho' => $carrinho,
            'cliente' => $cliente
        ]);

    }

    public function deletarItemCarrinho(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }

        $CarrinhoCompraItem = CarrinhoCompraItem::find($id);

        $request = new Request(
            [
                'id' => $CarrinhoCompraItem->pedido_item_id,
                'carrinho' => true
            ]
        );

        $pedidoItemPortal = PedidoItemPortal::find($CarrinhoCompraItem->pedido_item_id);

        $PedidoItemPortalController = new PedidoItemPortalController;
        $retorno = $PedidoItemPortalController->excluiProduto($request);

        if($retorno->getData()->success === 'success'){
            $CarrinhoCompraItem->deleted_by = Auth::id();
            $CarrinhoCompraItem->save();
            $CarrinhoCompraItem->delete();

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'valor' => $pedidoItemPortal->valor_total,
                    'pedido' => $pedidoItemPortal->pedido
                ]
            ];
            return response()->json($response, 200);
        }

        try{
            if($retorno->getData()->status === 'error'){
                throw new \Exception;
            }
        } catch (\Exception $e) {
            return $retorno;
        }
    }

    public function modalCancelarPedidoCarrinho(Request $request){
        $campo = $request->only('id');

        $CarrinhoCompra = CarrinhoCompra::with('dadosClientePedido.cliente')
            ->where('pedido_id', $campo['id'])
            ->first();

        $pedido = [
            'pedido' => $CarrinhoCompra->pedido_id,
            'cliente' => $CarrinhoCompra->dadosClientePedido->cliente->nome.' - '.$CarrinhoCompra->dadosClientePedido->cliente->cpf_cnpj,
            'cancelar_pedido' => true,
        ];

        $item = [
            'id' => encrypt($CarrinhoCompra->id),
            'descricao' => '',
            'codigo' => '',
            'deletar_item' => false
        ];

        $carrinho = [
            'pedido' => '',
            'cliente' => '',
            'excluir_carrinho' => false
        ];

        $cliente = [
            'id' => '',
            'cliente' => '',
            'deletar_cliente' => false,
        ];

        return view('programs.book_virtual_exibicao_new.modal.deletar')->with([
            'pedido' => $pedido,
            'item' => $item,
            'carrinho' => $carrinho,
            'deletar_cliente' => $cliente
        ]);
    }

    public function cancelarPedidoCarrinho(Request $request){
        $campo = $request->only('id');

        $request = new Request([
            'id' => $campo['id'],
            'carrinho' => true
        ]);

        $PedidoPortalController = new PedidoPortalController;
        $retorno = $PedidoPortalController->excluir($request);

        $CarrinhoCompra = CarrinhoCompra::with('carrinhoCompraItens', 'dadosClientePedido')
        ->where('created_by', Auth::id())
        ->where('finalizado', false)
        ->get();

        $pedidos_carrinho = $CarrinhoCompra->count('id');

        if($retorno->getData()->status === 'success'){
            foreach($CarrinhoCompra as $pedido){

                if($pedidos_carrinho == 1){
            
                    $pedido->dadosClientePedido->deleted_by = Auth::id();
                    $pedido->dadosClientePedido->save();
                    $pedido->dadosClientePedido->delete();
                }

                if($pedido->pedido_id == $campo['id']){
                    $itens_carrinho = $pedido->carrinhoCompraItens->count('id');

                    $pedido->deleted_by = Auth::id();
                    $pedido->save();
                    $pedido->delete();

                    foreach($pedido->carrinhoCompraItens as $item){
                        $item->deleted_by = Auth::id();
                        $item->save();
                        $item->delete();
                    }
                }
            }

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'contador' => $itens_carrinho,
                    'pedidos_carrinho' => $pedidos_carrinho
                ]
            ];
            return response()->json($response, 200);
        }

        try{
            if($retorno->getData()->status === 'error'){
                throw new \Exception;
            }
        } catch (\Exception $e) {
            return $retorno;
        }

    }

    public function modalExcluirCarrinho(Request $request){
        $campo = $request->only('id');

        $CarrinhoCompra = CarrinhoCompra::with('dadosClientePedido.cliente')
            ->where('pedido_id', $campo['id'])
            ->first();

        $pedido = [
            'pedido' => '',
            'cliente' => '',
            'cancelar_pedido' => false,
        ];

        $item = [
            'id' => encrypt($CarrinhoCompra->id),
            'descricao' => '',
            'codigo' => '',
            'deletar_item' => false
        ];

        $carrinho = [
            'cliente' => $CarrinhoCompra->dadosClientePedido->cliente->nome.' - '.$CarrinhoCompra->dadosClientePedido->cliente->cpf_cnpj,
            'excluir_carrinho' => true
        ];

        $cliente = [
            'id' => '',
            'cliente' => '',
            'deletar_cliente' => false,
        ];

        return view('programs.book_virtual_exibicao_new.modal.deletar')->with([
            'pedido' => $pedido,
            'item' => $item,
            'carrinho' => $carrinho,
            'cliente' => $cliente
        ]);

    }

    public function excluirCarrinho(Request $request){
        $CarrinhoCompra = CarrinhoCompra::where('created_by', Auth::id())
            ->where('finalizado', false)
            ->get();

       foreach($CarrinhoCompra as $pedido){

            $request = new Request(
                [
                    'id' => $pedido->pedido_id
                ]
            );
            $retorno = $this->cancelarPedidoCarrinho($request);

            if($retorno->getData()->status === 'success'){
                continue;
            }

            try{
                if($retorno->getData()->status === 'error'){
                    throw new \Exception;
                }
            } catch (\Exception $e) {
                return $retorno;
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    } 

    public function modalDeletarCliente(Request $request){
        $filtro = $request->only('id');

        try{
            $id = decrypt($filtro['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br/>Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }

        $dadosClientePedido = DadosClientePedido::find($id);

        $pedido = [
            'pedido' => '',
            'cliente' => '',
            'cancelar_pedido' => false,
        ];

        $item = [
            'id' => '',
            'descricao' => '',
            'codigo' => '',
            'deletar_item' => false
        ];

        $carrinho = [
            'cliente' => '',
            'excluir_carrinho' => false
        ];

        $cliente = [
            'id' => encrypt($id),
            'cliente' => $dadosClientePedido->cliente->nome.' - '.$dadosClientePedido->cliente->cpf_cnpj,
            'deletar_cliente' => true,
        ];

        return view('programs.book_virtual_exibicao_new.modal.deletar')->with([
            'pedido' => $pedido,
            'item' => $item,
            'carrinho' => $carrinho,
            'cliente' => $cliente
        ]);
    }

    public function deletarCliente(Request $request){
        $filtro = $request->only('id');

        try{
            $id = decrypt($filtro['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br/>Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }

        $dadosClientePedido = DadosClientePedido::find($id);
        $dadosClientePedido->deleted_by = Auth::id();
        $dadosClientePedido->save();
        $dadosClientePedido->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function filtroSegmetos(Request $request){       
        $SegmentosObj = Segmento::select()
        ->where('mostrar', true)
        ->orderBy('posicao', 'asc')
        ->get();

        foreach($SegmentosObj as $segmento){
            $segmentos[] = [
                'id' => encrypt($segmento->id),
                'descricao' => $segmento->descricao,
                'cor_codigo' => '#'.$segmento->cor_codigo,
                'route' => route('book_virtual_exibicao_new.busca', ['segmentos_id' => $segmento->id, 'marca' => 'false', 'linha' => 'false', 'navegacao' => 'true']),
                'pdf' => URL::asset('pdf/cores.pdf'),
                'pdf_pro' => URL::asset('pdf/LINHAMNPRO.pdf')
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'segmentos' => $segmentos
            ]
            
        ];
        return response()->json($response, 200);
    }
    public function bookLisos(Request $request){
        ini_set('memory_limit','2048M');
        set_time_limit(300);
        
        $campo = $request->only('id', 'marca', 'linha', 'codigo_produto', 'tipo_venda', 'pdf', 'campanha');

        $produtoGrupoObj = ProdutoGrupo::with('produtoEspecificacao.foto')->where('id', $campo['id']);

        if(!empty($campo['campanha'])){
            $produtoEstoque = ProdutosEstoque::where('campanha_id', $campo['campanha'])
            ->where('produto_grupos_id', $campo['id'])
            ->get()
            ->pluck('codigo_produto');
            $produtoGrupoObj = ProdutoGrupo::with(['produtoEspecificacao' => function ($query) use ($produtoEstoque){
                $query->whereIn('codigo_produto', $produtoEstoque);
            },'produtoEspecificacao.foto'])
            ->whereHas('produtoEspecificacao', function ($query) use ($produtoEstoque){
                $query->whereIn('codigo_produto', $produtoEstoque);
            });
        }
        if(!empty($campo['codigo_produto'])){
            $produtoGrupoObj = ProdutoGrupo::with(['produtoEspecificacao' => function ($query) use ($campo){
                $query->where('codigo_produto', $campo['codigo_produto']);
            },'produtoEspecificacao.foto'])
            ->whereHas('produtoEspecificacao', function ($query) use ($campo){
                $query->where('codigo_produto', $campo['codigo_produto']);
            });
        }
        if(!empty($campo['marca'])){
            $produtoGrupoObj->with(['produtoEspecificacao' => function ($query) use ($campo){
                $query->where('marca', $campo['marca']);
            },'produtoEspecificacao.foto']);
        }
        if(!empty($campo['linha'])){
            $produtoGrupoObj->with(['produtoEspecificacao' => function ($query) use ($campo){
                $query->where('linha', $campo['linha']);
            },'produtoEspecificacao.foto']);
        }

        $produtoGrupo = $produtoGrupoObj->first();
        
        if(!isset($campo['pdf'])){
            $dadosClientePedido = DadosClientePedido::with('cliente')->where('created_by', Auth::id())->first();
        }else{
            $dadosClientePedido = null;
        }

        if(!empty($dadosClientePedido->id)){
            $request = new Request([
                'codigo_cliente' => $dadosClientePedido->cliente_codigo,
                'in_codigos' => !empty($produtoGrupo) ? $produtoGrupo->produtoEspecificacao->pluck('codigo_produto') : [],
                'condicao_pagamento' => $dadosClientePedido->condicao_pagamento,
                'metragem_exata' => $dadosClientePedido->metragem_exata,
                'book' => true,
                'cliente' => true
            ]);

            $estoquePrecos = $this->filtroEstoquePrecos($request);
            $json = json_decode($estoquePrecos);

            if(!empty($json)){
                try{
                
                    if($json == null){
                        if($estoquePrecos->getData()->status == 'error'){
                            throw new \Exception;
                        }
                    }
                    
                } catch (\Exception $e) {
                    $response  = [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade!<br/>Tente novamente!',
                        'error' => $estoquePrecos->getData()->error,
                        'response' => '',
                    ];
        
                    return response()->json($response, 422);
                    
                }
            }
        }else{
            $request = new Request([
                'in_codigos' => !empty($produtoGrupo) ? $produtoGrupo->produtoEspecificacao->pluck('codigo_produto') : [],
                'book' => true,
                'cliente' => false
            ]);

            $estoquePrecos = $this->filtroEstoquePrecos($request);
            $json = json_decode($estoquePrecos);

            if(!empty($json)){

                try{
                    $json = json_decode($estoquePrecos);
                    if($json == null){
                        if($estoquePrecos->getData()->status == 'error'){
                            throw new \Exception;
                        }
                    }
                    
                } catch (\Exception $e) {
                    $response  = [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade!<br/>Tente novamente!',
                        'error' => $estoquePrecos->getData()->error,
                        'response' => '',
                    ];
        
                    return response()->json($response, 422);
                    
                }
            }
        }

        $produtos = [];

        foreach($estoquePrecos as $produto){
            if($campo['tipo_venda'] == 'pronta_entrega'){
                    if($produto['estoque_total'] > 0){

                    $imagemObj = $produtoGrupo->produtoEspecificacao
                        ->where('codigo_produto', $produto['codigo_produto'])
                        ->pluck('foto')->toArray();

                    if(isset($imagemObj[0]['filename'])){
                        $imagem = $imagemObj[0]['filename'];
                    }else{
                        $imagem = '';
                    }

                    $imagem_cor = !empty($imagem) ? Storage::url($this->path_produto_fotos.$imagem) : URL::asset('images/sem_imagem.jpg');

                    $produtos[] = [
                        'codigo_produto' => $produto['codigo_produto'],
                        'estoque_total' => $produto['estoque_total'] > 0 ? parserQtd($produto['estoque_total']) : '',
                        'compras' => $produto['compras'] > 0 ? parserQtd($produto['compras']) : '',
                        'imagem_cor' => $imagem_cor,
                        'preco' => isset($produto['preco']) ? $produto['preco'] : ''
                    ];
                }
            }
            if($campo['tipo_venda'] == 'pronta_entrega_futura'){
                if($produto['compras'] > 0){
                    $imagemObj = $produtoGrupo->produtoEspecificacao
                        ->where('codigo_produto', $produto['codigo_produto'])
                        ->pluck('foto')->toArray();

                    if(isset($imagemObj[0]['filename'])){
                        $imagem = $imagemObj[0]['filename'];
                    }else{
                        $imagem = '';
                    }

                    $imagem_cor = !empty($imagem) ? Storage::url($this->path_produto_fotos.$imagem) : URL::asset('images/sem_imagem.jpg');

                    $produtos[] = [
                        'codigo_produto' => $produto['codigo_produto'],
                        'estoque_total' => $produto['estoque_total'] > 0 ? parserQtd($produto['estoque_total']) : '',
                        'compras' => $produto['compras'] > 0 ? parserQtd($produto['compras']) : '',
                        'imagem_cor' => $imagem_cor,
                        'preco' => isset($produto['preco']) ? $produto['preco'] : ''
                    ];
                }
            }else{
                if($produto['estoque_total'] + $produto['compras'] > 0){
                    $imagemObj = $produtoGrupo->produtoEspecificacao
                    ->where('codigo_produto', $produto['codigo_produto'])
                    ->pluck('foto')->toArray();

                    if(isset($imagemObj[0]['filename'])){
                        $imagem = $imagemObj[0]['filename'];
                    }else{
                        $imagem = '';
                    }

                    $imagem_cor = !empty($imagem) ? Storage::url($this->path_produto_fotos.$imagem) : URL::asset('images/sem_imagem.jpg');

                    $produtos[] = [
                        'codigo_produto' => $produto['codigo_produto'],
                        'estoque_total' => $produto['estoque_total'] > 0 ? parserQtd($produto['estoque_total']) : '',
                        'compras' => $produto['compras'] > 0 ? parserQtd($produto['compras']) : '',
                        'imagem_cor' => $imagem_cor,
                        'preco' => isset($produto['preco']) ? $produto['preco'] : ''
                    ];
                }
            }
        }

        if(empty($produtoGrupo)){
            $produtoGrupo = ProdutoGrupo::find($campo['id']);
        }

        if(isset($produtoGrupo->imagem) && Storage::exists($this->grupo_patch.$produtoGrupo->imagem)){
            $imagem_grupo = Storage::url($this->grupo_patch.$produtoGrupo->imagem);
        }
        else{
            $imagem_grupo = URL::asset('images/sem_imagem.jpg');
        }

        if(isset($campo['pdf'])){
            return $produtos;
        }

        $pdf_nome = empty($produtoGrupo->descricao)? '' : strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/',"\t"], '', str_replace(' ', '_', $produtoGrupo->descricao))).'.pdf';
        $book_pdf = $book_pdf = Storage::exists($this->pathPdf.'book_'.$pdf_nome) ? Storage::url($this->pathPdf.'book_'.$pdf_nome) : '';

        if(empty($book_pdf)){
            if(isset($produtoGrupo->segmento->descricao)){
                if(Storage::exists($this->pathPdf.$produtoGrupo->segmento->descricao.'/book_'.$pdf_nome)){
                    $book_pdf = Storage::url($this->pathPdf.$produtoGrupo->segmento->descricao.'/book_'.$pdf_nome);
                }
            }
        }

        $request = new Request([
            'id' => empty($produtoGrupo)? null : $produtoGrupo->id,
            'pdf' => true,
            'codigo_produto' => isset($campo['codigo_produto']) ? $campo['codigo_produto'] : ''
        ]);

        $fichaTecnica = $this->modalFichaTecnica($request);


        $dadosGrupo = [
            'id' => empty($produtoGrupo)?  encrypt(null) : encrypt($produtoGrupo->id),
            'nome' => empty($produtoGrupo)? '' : $produtoGrupo->descricao,
            'imagem' => $imagem_grupo,
            'book_pdf' => !empty($book_pdf) ? $book_pdf : ''
        ];

        $grupo = array_merge($dadosGrupo,$fichaTecnica);

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'grupo' => $grupo,
                'produtos' => $produtos
            ]
            
        ];
        return response()->json($response, 200);
    }

    public function bookDesenho(Request $request){
        ini_set('memory_limit','2048M');
        set_time_limit(300);

        $campo = $request->only('id', 'marca', 'linha', 'codigo_produto_busca', 'tipo_venda', 'codigo_desenho', 'filtro', 'pdf', 'campanha');
        
        $produtoGrupoObj = ProdutoGrupo::with('produtoEspecificacao.foto', 'produtoGrupoDesenho')->where('id', $campo['id']);

        if(!empty($campo['campanha'])){
            $produtoEstoque = ProdutosEstoque::where('campanha_id', $campo['campanha'])
            ->where('produto_grupos_id', $campo['id'])
            ->get()
            ->pluck('codigo_produto');
            $produtoGrupoObj = ProdutoGrupo::with(['produtoEspecificacao' => function ($query) use ($produtoEstoque){
                $query->whereIn('codigo_produto', $produtoEstoque);
            },'produtoEspecificacao.foto', 'produtoGrupoDesenho'])
            ->whereHas('produtoEspecificacao', function ($query) use ($produtoEstoque){
                $query->whereIn('codigo_produto', $produtoEstoque);
            });
        }
        if(!empty($campo['codigo_produto_busca'])){
            $produtoGrupoObj = ProdutoGrupo::with(['produtoEspecificacao' => function ($query) use ($campo){
                $query->where('codigo_produto', $campo['codigo_produto_busca']);
            },'produtoEspecificacao.foto','produtoGrupoDesenho'])
            ->whereHas('produtoEspecificacao', function ($query) use ($campo){
                $query->where('codigo_produto', $campo['codigo_produto_busca']);
            },'produtoEspecificacao.foto');
        }
        if(!empty($campo['marca'])){
            $produtoGrupoObj->with(['produtoEspecificacao' => function ($query) use ($campo){
                $query->where('marca', $campo['marca']);
            },'produtoGrupoDesenho']);
        }
        if(!empty($campo['linha'])){
            $produtoGrupoObj->with(['produtoEspecificacao' => function ($query) use ($campo){
                $query->where('linha', $campo['linha']);
            },'produtoEspecificacao.foto', 'produtoGrupoDesenho']);
        }

        $produtoGrupo = $produtoGrupoObj->first();
        
        $result = [
            'estoques_desenho' => [],
            'desenhos' => [],
            'grupo' => [],
        ];

        $request = new Request([
            'in_codigos' => !empty($produtoGrupo) ? $produtoGrupo->produtoEspecificacao->pluck('codigo_produto') : [],
            'book' => true,
            'cliente' => false
        ]);

        $estoquePrecos = $this->filtroEstoquePrecos($request);
        $json = json_decode($estoquePrecos);

        if(!empty($json)){

            try{
                $json = json_decode($estoquePrecos);
                if($json == null){
                    if($estoquePrecos->getData()->status == 'error'){
                        throw new \Exception;
                    }
                }
                
            } catch (\Exception $e) {
                $response  = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade!<br/>Tente novamente!',
                    'error' => $estoquePrecos->getData()->error,
                    'response' => '',
                ];
                return response()->json($response, 422);
            }
            
        }

        foreach($estoquePrecos as $item){    
            
            if($campo['tipo_venda'] == 'pronta_entrega'){
                if($item['estoque_total'] > 0){

                    if($produtoGrupo->padrao_codigo == '6_10'){
                            if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($item['codigo_produto'], 6, 5)) === false){
                                $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($item['codigo_produto'], 6, 5)]));
                                $result['estoques_desenho'][substr($item['codigo_produto'], 6, 5)][] =  [
                                    'codigo_produto' => $item['codigo_produto']
                                ]; 
                            }else{
                                $result['estoques_desenho'][substr($item['codigo_produto'], 6, 5)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }
                    }
    
                    elseif($produtoGrupo->padrao_codigo == '7_11'){
                            if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($item['codigo_produto'], 7, 5)) === false){
                                $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($item['codigo_produto'], 7, 5)]));
                                $result['estoques_desenho'][substr($item['codigo_produto'], 7, 5)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
    
                            }else{
                                $result['estoques_desenho'][substr($item['codigo_produto'], 7, 5)][] =  [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }
                    }
    
                    elseif($produtoGrupo->padrao_codigo == '4_6'){
                            if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($item['codigo_produto'], 4, 6)) === false){
                                $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($item['codigo_produto'], 4, 6)])); 
                                $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }else{
                                $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }
                    }
    
                    elseif($produtoGrupo->padrao_codigo == '4_9'){
                            if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($item['codigo_produto'], 4, 6)) === false){
                                $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($item['codigo_produto'], 4, 6)]));
                                $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }else{
                                $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }
                        }
                }
            }
            elseif($campo['tipo_venda'] == 'pronta_entrega_futura'){
                if($item['compras'] > 0){

                    if($produtoGrupo->padrao_codigo == '6_10'){
                            if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($item['codigo_produto'], 6, 5)) === false){
                                $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($item['codigo_produto'], 6, 5)]));
                                $result['estoques_desenho'][substr($item['codigo_produto'], 6, 5)][] =  [
                                    'codigo_produto' => $item['codigo_produto']
                                ]; 
                            }else{
                                $result['estoques_desenho'][substr($item['codigo_produto'], 6, 5)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }
                    }
    
                    elseif($produtoGrupo->padrao_codigo == '7_11'){
                            if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($item['codigo_produto'], 7, 5)) === false){
                                $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($item['codigo_produto'], 7, 5)]));
                                $result['estoques_desenho'][substr($item['codigo_produto'], 7, 5)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
    
                            }else{
                                $result['estoques_desenho'][substr($item['codigo_produto'], 7, 5)][] =  [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }
                    }
    
                    elseif($produtoGrupo->padrao_codigo == '4_6'){
                            if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($item['codigo_produto'], 4, 6)) === false){
                                $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($item['codigo_produto'], 4, 6)])); 
                                $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }else{
                                $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }
                    }
    
                    elseif($produtoGrupo->padrao_codigo == '4_9'){
                            if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($item['codigo_produto'], 4, 6)) === false){
                                $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($item['codigo_produto'], 4, 6)]));
                                $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }else{
                                $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }
                        }
                }
            }else{
                if($item['estoque_total'] + $item['compras'] > 0){

                    if($produtoGrupo->padrao_codigo == '6_10'){
                            if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($item['codigo_produto'], 6, 5)) === false){
                                $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($item['codigo_produto'], 6, 5)]));
                                $result['estoques_desenho'][substr($item['codigo_produto'], 6, 5)][] =  [
                                    'codigo_produto' => $item['codigo_produto']
                                ]; 
                            }else{
                                $result['estoques_desenho'][substr($item['codigo_produto'], 6, 5)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }
                    }
    
                    elseif($produtoGrupo->padrao_codigo == '7_11'){
                            if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($item['codigo_produto'], 7, 5)) === false){
                                $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($item['codigo_produto'], 7, 5)]));
                                $result['estoques_desenho'][substr($item['codigo_produto'], 7, 5)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
    
                            }else{
                                $result['estoques_desenho'][substr($item['codigo_produto'], 7, 5)][] =  [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }
                    }
    
                    elseif($produtoGrupo->padrao_codigo == '4_6'){
                            if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($item['codigo_produto'], 4, 6)) === false){
                                $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($item['codigo_produto'], 4, 6)])); 
                                $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }else{
                                $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }
                    }
    
                    elseif($produtoGrupo->padrao_codigo == '4_9'){
                            if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($item['codigo_produto'], 4, 6)) === false){
                                $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($item['codigo_produto'], 4, 6)]));
                                $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }else{
                                $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = $result['estoques_desenho'][substr($item['codigo_produto'], 4, 6)][] = [
                                    'codigo_produto' => $item['codigo_produto']
                                ];
                            }
                        }
                }
            }
        }

        if(!empty($produtoGrupo)){

            $produtoGrupo->produtoGrupoDesenho->sortBy('codigo_desenho')->each(function ($desenho) use (&$result){
                if(isset($result['estoques_desenho'][$desenho['codigo_desenho']])){
                    $result['desenhos'][] = [
                        'codigos_produtos' => encrypt($result['estoques_desenho'][$desenho['codigo_desenho']]),
                        'codigo_desenho' => $desenho['codigo_desenho'],
                        'imagem_desenho_zoom' => isset($desenho['imagem_zoom']) ? Storage::url(substr_replace($this->grupo_patch.'desenhos/'.$desenho['imagem_zoom'],'_zoom.jpg', -4)) : URL::asset('images/sem_imagem.jpg'),
                        'imagem_desenho' => isset($desenho['imagem']) ? Storage::url($this->grupo_patch.'desenhos/'.$desenho['imagem']) : URL::asset('images/sem_imagem.jpg'),
                        'pdf_desenho' => Storage::exists($this->pathPdf.'desenhos/book_'.$desenho['codigo_desenho'].'.pdf') ? Storage::url($this->pathPdf.'desenhos/book_'.$desenho['codigo_desenho'].'.pdf') : ''
                    ];
                }
            });

            if(isset($campo['pdf'])){
                return $result['desenhos'];
            }
        }else{
            $produtoGrupo = ProdutoGrupo::find($campo['id']);
        }


        if(isset($produtoGrupo->imagem) && Storage::exists($this->grupo_patch .$produtoGrupo->imagem)){
            $imagem_grupo = Storage::url($this->grupo_patch .$produtoGrupo->imagem);
        }
        else{
            $imagem_grupo = URL::asset('images/sem_imagem.jpg');
        }

        $pdf_nome = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/',"\t"], '', str_replace(' ', '_', $produtoGrupo->descricao))).'.pdf';
        $book_pdf = $book_pdf = Storage::exists($this->pathPdf.'book_'.$pdf_nome) ? Storage::url($this->pathPdf.'book_'.$pdf_nome) : '';

        if(empty($book_pdf)){
            if(isset($produtoGrupo->segmento->descricao)){
                if(Storage::exists($this->pathPdf.$produtoGrupo->segmento->descricao.'/book_'.$pdf_nome)){
                    $book_pdf = Storage::url($this->pathPdf.$produtoGrupo->segmento->descricao.'/book_'.$pdf_nome);
                }
            }
        }

        $result['grupo'] = [
            'id' => encrypt($produtoGrupo->id),
            'nome' => $produtoGrupo->descricao,
            'imagem' => $imagem_grupo,
            'book_pdf' => !empty($book_pdf) ? $book_pdf : '', 
            'filtros' => isset($produtoGrupo->segmento->descricao) ? $produtoGrupo->segmento->descricao : $produtoGrupo->descricao
        ];

        $request = new Request([
            'id' => $produtoGrupo->id,
            'pdf' => true,
            'codigo_produto' => isset($campo['codigo_produto']) ? $campo['codigo_produto'] : ''
        ]);

        $fichaTecnica = $this->modalFichaTecnica($request);
        $grupo = array_merge($result['grupo'],$fichaTecnica);

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'grupo' => $grupo,
                'desenhos' => $result['desenhos']
            ]
        ];
        return response()->json($response, 200);
    }

    public function bookDesenhoProdutos(Request $request){
        $campo = $request->only('id','codigo_desenho', 'marca', 'linha', 'codigos_produtos', 'codigo_produto', 'tipo_venda', 'pdf');
        
        $codigos_produtos = decrypt($campo['codigos_produtos']);

        $produtoGrupoObj = ProdutoGrupo::with(['produtoEspecificacao','produtoEspecificacao.foto', 'produtoGrupoDesenho' => function ($query) use ($campo){
            $query->where('codigo_desenho', $campo['codigo_desenho']);
        }])
        ->where('id', $campo['id']);

        if(!empty($campo['codigo_produto'])){
            $produtoGrupoObj = ProdutoGrupo::with(['produtoEspecificacao' => function ($query) use ($campo){
                $query->where('codigo_produto', $campo['codigo_produto']);
            },'produtoEspecificacao.foto'])
            ->whereHas('produtoEspecificacao', function ($query) use ($campo){
                $query->where('codigo_produto', $campo['codigo_produto']);
            });
        }
        if(!empty($campo['marca'])){
            $produtoGrupoObj->with(['produtoEspecificacao' => function ($query) use ($campo){
                $query->where('marca', $campo['marca']);
            },'produtoEspecificacao.foto']);
        }
        if(!empty($campo['linha'])){
            $produtoGrupoObj->with(['produtoEspecificacao' => function ($query) use ($campo){
                $query->where('linha', $campo['linha']);
            },'produtoEspecificacao.foto']);
        }

        $produtoGrupo = $produtoGrupoObj->first();

        foreach($codigos_produtos as $codigo){     
            
            if(!empty($produtoGrupo)){
                if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($codigo['codigo_produto'], 6, 5)) === false && ($produtoGrupo->padrao_codigo == '6_10')){
                    $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($codigo['codigo_produto'], 6, 5)]));
                }
                else if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($codigo['codigo_produto'], 7, 5)) === false && ($produtoGrupo->padrao_codigo == '7_11' )){
                    $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($codigo['codigo_produto'], 7, 5)]));
                }
                else if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($codigo['codigo_produto'], 4, 6)) === false && ($produtoGrupo->padrao_codigo == '4_6')){
                    $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($codigo['codigo_produto'], 4, 6)]));
                }
                else if($produtoGrupo->produtoGrupoDesenho->pluck('codigo_desenho')->search(substr($codigo['codigo_produto'], 4, 6)) === false && ($produtoGrupo->padrao_codigo == '4_9')){
                    $produtoGrupo->produtoGrupoDesenho->push(collect(['codigo_desenho' => substr($codigo['codigo_produto'], 4, 6)]));
                }
            }
            
        }

        $dadosClientePedido = DadosClientePedido::with('cliente')->where('created_by', Auth::id())->first();
        
        $result = [
            'estoques_desenho' => [],
            'desenho' => [],
            'produtos' => [],
            'compras' => 0,
            'estoque' => 0
        ];
        
        if(!empty($dadosClientePedido->id)){
            $request = new Request([
                'codigo_cliente' => $dadosClientePedido->cliente_codigo,
                'in_codigos' => $codigos_produtos,
                'condicao_pagamento' => $dadosClientePedido->condicao_pagamento,
                'metragem_exata' => $dadosClientePedido->metragem_exata,
                'book' => true,
                'cliente' => true
            ]);

            $estoquePrecos = $this->filtroEstoquePrecos($request);
            $json = json_decode($estoquePrecos);

            if(!empty($json)){
                try{
                
                    if($json == null){
                        if($estoquePrecos->getData()->status == 'error'){
                            throw new \Exception;
                        }
                    }
                    
                } catch (\Exception $e) {
                    $response  = [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade!<br/>Tente novamente!',
                        'error' => $estoquePrecos->getData()->error,
                        'response' => '',
                    ];
        
                    return response()->json($response, 422);
                    
                }
            }
        }else{
            $request = new Request([
                'in_codigos' => $codigos_produtos,
                'book' => true,
                'cliente' => false
            ]);

            $estoquePrecos = $this->filtroEstoquePrecos($request);
            $json = json_decode($estoquePrecos);

            if(!empty($json)){

                try{
                    $json = json_decode($estoquePrecos);
                    if($json == null){
                        if($estoquePrecos->getData()->status == 'error'){
                            throw new \Exception;
                        }
                    }
                    
                } catch (\Exception $e) {
                    $response  = [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade!<br/>Tente novamente!',
                        'error' => $estoquePrecos->getData()->error,
                        'response' => '',
                    ];
        
                    return response()->json($response, 422);
                    
                }
            }
        }

        if(!empty($produtoGrupo)){
            $produtoGrupo->produtoGrupoDesenho->each(function ($desenho) use ($produtoGrupo, &$result, $estoquePrecos, &$campo){
                $result['estoques_desenho'] = $estoquePrecos->filter(function($estoque) use ($desenho, $produtoGrupo){
                    switch ($produtoGrupo->padrao_codigo) {
                        case "6_10":
                            if(substr($estoque['codigo_produto'], 6, 5) == $desenho['codigo_desenho']){
                                return $estoque;
                            }
                            break;
                        case "7_11":
                            if(substr($estoque['codigo_produto'], 7, 5) == $desenho['codigo_desenho']){
                                return $estoque;
                            }
                            break;
                        case "4_6":
                            if(substr($estoque['codigo_produto'], 4, 6) == $desenho['codigo_desenho']){
                                return $estoque;
                            }
                            break;
                        case "4_9":
                            if(substr($estoque['codigo_produto'], 4, 6) == $desenho['codigo_desenho']){
                                return $estoque;
                            }
                            break;
                    }
                });

                $result['desenho'] = [
                    'nome' => $desenho['codigo_desenho'],
                    'imagem' => !empty($desenho['imagem']) ? Storage::url($this->grupo_patch.'desenhos/'.$desenho['imagem']) : URL::asset('images/sem_imagem.jpg'),
                    'filtros' => isset($produtoGrupo->segmento->descricao) ? $produtoGrupo->segmento->descricao : $produtoGrupo->descricao,
                    'id' => encrypt($produtoGrupo->id)
                ];

                $request = new Request([
                    'id' => $produtoGrupo->id,
                    'desenho' => $desenho['codigo_desenho'],
                    'pdf' => true,
                    'codigo_produto' => isset($campo['codigo_produto']) ? $campo['codigo_produto'] : ''
                ]);

                $fichaTecnica = $this->modalFichaTecnica($request);
                unset($fichaTecnica['imagem']);
                unset($fichaTecnica['nome']);
                $result['desenho'] = array_merge($result['desenho'],$fichaTecnica);

                foreach($result['estoques_desenho'] as $item){
                    $imagemObj = $produtoGrupo->produtoEspecificacao->where('codigo_produto', $item['codigo_produto'])->pluck('foto')->toArray();

                    if(isset($imagemObj[0]['filename'])){
                        $imagem = $imagemObj[0]['filename'];
                    }else{
                        $imagem = '';
                    }

                    if($item['estoque_total'] + $item['compras'] > 0){
                            $result['produtos'][] = [
                                'codigo_produto' => $item['codigo_produto'],
                                'imagem_cor' => !empty($imagem) ? Storage::url($this->path_produto_fotos.$imagem) : URL::asset('images/sem_imagem.jpg'),
                                'estoque_total' => $item['estoque_total'] > 0 ? parserQtd($item['estoque_total'] ) : '',
                                'compras' => $item['compras'] > 0 ? parserQtd($item['compras']) : '',
                                'preco' => isset($item['preco']) ? $item['preco'] : '' 
                            ];
                    }
                }
            });
        }
        

        if(isset($campo['pdf'])){
            return $result['produtos'];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'desenho' => $result['desenho'],
                'produtos' => $result['produtos']
            ]
        ];
        return response()->json($response, 200);
    }

    public function modalFichaTecnica(Request $request){
        $campo = $request->only('id', 'desenho', 'pdf', 'codigo_produto');

        if(!empty($campo['codigo_produto'])){
            $produtoGrupoObj = ProdutoGrupo::with(['produtoEspecificacao' => function ($query) use ($campo){
                $query->where('codigo_produto', $campo['codigo_produto']);
            }],'produtoEspecificacao.foto', 'produtoGrupoDesenho')
            ->whereHas('produtoEspecificacao', function ($query) use ($campo){
                $query->where('codigo_produto', $campo['codigo_produto']);
            })
            ->first();
        }else{
            $produtoGrupoObj = ProdutoGrupo::with(['produtoEspecificacao', 'produtoEspecificacao.foto', 'produtoGrupoDesenho' => function ($query) use ($campo){
                if(!empty($campo['desenho'])){
                    $query->where('codigo_desenho', $campo['desenho']);
                }
            }])->find($campo['id']);
        }
        
        if(!empty($produtoGrupoObj->caminho) && Storage::exists($produtoGrupoObj->caminho)){
            $img_instrucoes_lavagem = Storage::url($produtoGrupoObj->caminho);
        }else{
            $img_instrucoes_lavagem = asset('images/sem-imagem-150-26.jpg');
        }

        if(!empty($campo['desenho'])){
            $imagem_desenho = $produtoGrupoObj->produtoGrupoDesenho->pluck('imagem_zoom')->filter()->implode('');
            $imagem = Storage::exists(substr_replace($this->grupo_patch.'desenhos/'.$imagem_desenho,'_zoom.jpg', -4)) ? Storage::url(substr_replace($this->grupo_patch.'desenhos/'.$imagem_desenho,'_zoom.jpg', -4)) : URL::asset('images/sem_imagem.jpg');
        }else{
            $imagem = empty($produtoGrupoObj)? null : (Storage::exists(substr_replace($this->grupo_patch.$produtoGrupoObj->imagem_zoom,'_zoom.jpg', -4)) ? Storage::url(substr_replace($this->grupo_patch.$produtoGrupoObj->imagem_zoom,'_zoom.jpg', -4)) : URL::asset('images/sem_imagem.jpg'));
        }

        $dados = [
            'imagem_zoom' => $imagem,
            'caracteristicas' => empty($produtoGrupoObj)? '' : (!is_null($produtoGrupoObj->caracteristicas) ? $produtoGrupoObj->caracteristicas : ''),
            'composicao' => empty($produtoGrupoObj)? '' : (isset($produtoGrupoObj->produtoEspecificacao->first()->produtoNasajon) && !is_null($produtoGrupoObj->produtoEspecificacao->first()->produtoNasajon->composicao) ? $produtoGrupoObj->produtoEspecificacao->first()->produtoNasajon->composicao : ''),
            'encolhimento' => empty($produtoGrupoObj)? '' : (!is_null($produtoGrupoObj->encolhimento) ? $produtoGrupoObj->encolhimento : ''),
            'pecas' => empty($produtoGrupoObj)? '' : (!is_null($produtoGrupoObj->pecas) ? $produtoGrupoObj->pecas : ''),
            'origem' => empty($produtoGrupoObj)? '' : (!is_null($produtoGrupoObj->origem) ? $produtoGrupoObj->origem : ''),
            'ncm' => empty($produtoGrupoObj)? '' : (isset($produtoGrupoObj->produtoEspecificacao->first()->produtoNasajon) ? $produtoGrupoObj->produtoEspecificacao->first()->produtoNasajon->ncm : ''),
            'gramatura_gm2' => empty($produtoGrupoObj)? '' : (!is_null($produtoGrupoObj->gramatura_gm2) ? $produtoGrupoObj->gramatura_gm2 : ''),
            'gramatura_linear' => empty($produtoGrupoObj)? '' : (!is_null($produtoGrupoObj->gramatura_gml) ? $produtoGrupoObj->gramatura_gml : ''),
            'largura' => empty($produtoGrupoObj)? '' : (!is_null($produtoGrupoObj->largura) ? $produtoGrupoObj->largura : ''),
            'rendimento' => empty($produtoGrupoObj)? '' : (!is_null($produtoGrupoObj->rendimento)? $produtoGrupoObj->rendimento : ''),
            'ean' => empty($produtoGrupoObj)? '' : (isset($produtoGrupoObj->produtoEspecificacao->first()->produtoNasajon) && !is_null($produtoGrupoObj->produtoEspecificacao->first()->produtoNasajon->codigodebarras) ? $produtoGrupoObj->produtoEspecificacao->first()->produtoNasajon->codigodebarras : ''),
            'unidade' => empty($produtoGrupoObj)? '' : (isset($produtoGrupoObj->produtoEspecificacao->first()->unidade) ? $produtoGrupoObj->produtoEspecificacao->first()->unidade : ''),
            'peso_bruto' => empty($produtoGrupoObj)? '' : (isset($produtoGrupoObj->produtoEspecificacao->first()->produtoNasajon) ? $produtoGrupoObj->produtoEspecificacao->first()->produtoNasajon->pesobruto : ''),
            'img_instrucoes_lavagem' => empty($produtoGrupoObj)? '' : (!is_null($img_instrucoes_lavagem) ? $img_instrucoes_lavagem : ''),
            'nome' => empty($produtoGrupoObj)? '' : (!is_null($produtoGrupoObj->descricao) ? $produtoGrupoObj->descricao : ''),
            'imagem_pdf' => empty($produtoGrupoObj)? '' : (!empty($produtoGrupoObj->imagem) ? Storage::url($this->grupo_patch.$produtoGrupoObj->imagem) : URL::asset('images/sem_imagem.jpg')),
        ];

        if(isset($campo['pdf'])){
            return $dados;
        }

        return view('programs.book_virtual_exibicao_new.modal.ficha_tecnica')->with($dados);
    }

    public function gerarPDFBookVirtual(){
        ini_set('memory_limit','2048M');
        ini_set('max_execution_time', 1800);
        ini_set('pcre.backtrack_limit', 1000000000);
       
        $produtosEstoqueObj = ProdutosEstoque::with('produtoGrupo.segmento', 'produtoGrupo') 
        ->select(DB::raw('sum(estoque) + sum(compras) as estoque_compras'),'produto_grupos_id')
        ->groupBy('produto_grupos_id')
        ->whereHas('produtoGrupo', function($query){
            $query->where('mostrar', true);
        })
        ->having(DB::raw('sum(estoque) + sum(compras)'), '>', 0)
        ->distinct('produto_grupos_id')
        ->get();

        $dados_liso = [];
        $dados_desenho = [];
        $dados_desenhos = [];

        foreach($produtosEstoqueObj as $produto){
            $pdf_nome = 'book_'.strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/',"\t"], '', str_replace(' ', '_', $produto->produtoGrupo->descricao))).'.pdf';
            if(is_null($produto->produtoGrupo->padrao_codigo)){
                $request = new Request([
                    'id' => $produto->produto_grupos_id,
                    'pdf' => true,
                    'tipo_venda' => null
                ]);

                try{
                    $fichaTecnica = $this->modalFichaTecnica($request);
                    $bookLisos['produtos'] = $this->bookLisos($request);
                }catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }

                $dados_liso = array_merge($fichaTecnica,$bookLisos);

                $title = $dados_liso['nome'];
                $pdf = PDF::loadView(
                    'pdf.book_virtual_liso_new', 
                    $dados_liso,
                    [], 
                    ['title' => $title, 'margin_bottom' => 1, 'orientation' => 'P', 'format' => 'A4']
                );

                if(isset($produto->produtoGrupo->segmento->descricao)){
                    $segmentos[$produto->produtoGrupo->segmento->descricao] = [
                        'nome' => $produto->produtoGrupo->segmento->descricao
                    ]; 
                    Storage::put($this->pathPdf.$produto->produtoGrupo->segmento->descricao.'/'.$pdf_nome, $pdf->output());
                }else{
                    Storage::put($this->pathPdf.$pdf_nome, $pdf->output());
                }
            }else{
                $request = new Request([
                    'id' => $produto->produto_grupos_id,
                    'pdf' => true,
                    'tipo_venda' => null
                ]);

                try{
                    $fichaTecnica = $this->modalFichaTecnica($request);
                    $bookDesenhos['desenhos'] = $this->bookDesenho($request);
                }catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }

                foreach($bookDesenhos['desenhos'] as $key => $desenho){
                    $request = new Request([
                        'id' => $produto->produto_grupos_id,
                        'pdf' => true,
                        'tipo_venda' => null,
                        'codigos_produtos' => $desenho['codigos_produtos'],
                        'codigo_desenho' => $desenho['codigo_desenho']
                    ]);

                    try{
                        $bookDesenhos['desenhos'][$key][$desenho['codigo_desenho']] = $this->bookDesenhoProdutos($request);
                        $desenho['produtos'] = $this->bookDesenhoProdutos($request);
                    }catch (\Exception $e) {
                        throw new \Exception($e->getMessage());
                    }

                    $dados_desenho = array_merge($fichaTecnica,$desenho);
                    unset($dados_desenho['imagem_pdf']);
                    unset($dados_desenho['nome']);

                    $pdf_nome_desenho = 'book_'.$desenho['codigo_desenho'].'.pdf';
                    $title = $desenho['codigo_desenho'];
                    $pdf = PDF::loadView(
                        'pdf.book_virtual_liso_new', 
                        $dados_desenho,
                        [], 
                        ['title' => $title, 'margin_bottom' => 1, 'orientation' => 'P', 'format' => 'A4']
                    );
                    Storage::put($this->pathPdf.'desenhos/'.$pdf_nome_desenho, $pdf->output());
                    
                    unset($desenho['produtos']);
                }

                $dados_desenhos = array_merge($fichaTecnica,$bookDesenhos);

                $title = $dados_desenhos['nome'];
                $pdf = PDF::loadView(
                    'pdf.book_virtual_estampado_new', 
                    $dados_desenhos,
                    [], 
                    ['title' => $title, 'margin_bottom' => 1, 'orientation' => 'P', 'format' => 'A4']
                );

                if(isset($produto->produtoGrupo->segmento->descricao)){
                    $segmentos[$produto->produtoGrupo->segmento->descricao] = [
                        'nome' => $produto->produtoGrupo->segmento->descricao
                    ]; 
                    Storage::put($this->pathPdf.$produto->produtoGrupo->segmento->descricao.'/'.$pdf_nome, $pdf->output());
                }else{
                    Storage::put($this->pathPdf.$pdf_nome, $pdf->output());
                }
            }
        }

        /*foreach($segmentos as $segmento){
            $diretorio = '\''.$segmento['nome'].'\'/*';
            $path = Storage::path($this->pathPdf);
            exec('cd '.$path.' && zip \''.$segmento['nome'].'\'.zip'.' '.$diretorio. " > /dev/null &");
        }
        */
    }

    public function modalBaixarEnviarPDF(Request $request){
        $campo = $request->only('segmentos_id', 'produto_grupos_id', 'desenho');
        
        try{
            if(empty($campo)){
                throw new \Exception;
            }
            
        } catch (\Exception $e) {
            $response  = [
                'status' => 'error',
                'message' => 'Não foi gerado o PDF deste book!',
                'error' => 'Não foi gerado o PDF deste book!',
                'response' => '',
            ];

            return response()->json($response, 422);
            
        }
       
        if(!empty($campo['segmentos_id'])){
            $filtro = 'segmento';
            $id = $campo['segmentos_id'];

            $segmento = Segmento::find(decrypt($id));
            $tamanho_arquivo = Storage::exists($this->pathPdf.$segmento->descricao.'.zip') ? parserTamanhoArquivo(Storage::size($this->pathPdf.$segmento->descricao.'.zip'), 0).'B' : '0MB';
            $quantidade_arquivos = Storage::exists($this->pathPdf.$segmento->descricao.'.zip') ? count(Storage::allFiles($this->pathPdf.$segmento->descricao)) : 0;
            $descricao = $segmento->descricao;

            try{
                if($tamanho_arquivo == '0MB'){
                    throw new \Exception;
                }
                
            } catch (\Exception $e) {
                $response  = [
                    'status' => 'error',
                    'message' => 'Não foi gerado o arquivo ziapdo deste segmento!',
                    'error' => 'Não foi gerado o arquivo zipado deste segmento!',
                    'response' => '',
                ];
    
                return response()->json($response, 422);
                
            }
        }
        if(!empty($campo['produto_grupos_id'])){
            $filtro = 'grupo';
            $id = $campo['produto_grupos_id'];

            $produtoGrupo = ProdutoGrupo::find(decrypt($id));
            $pdf_nome = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/',"\t"], '', str_replace(' ', '_', $produtoGrupo->descricao))).'.pdf';
            $descricao = $produtoGrupo->descricao;

            $arquivo = Storage::exists($this->pathPdf.'book_'.$pdf_nome) ? $this->pathPdf.'book_'.$pdf_nome : '';

            if(empty($arquivo)){
                if(isset($produtoGrupo->segmento->descricao)){
                    if(Storage::exists($this->pathPdf.$produtoGrupo->segmento->descricao.'/book_'.$pdf_nome)){
                        $arquivo = $this->pathPdf.$produtoGrupo->segmento->descricao.'/book_'.$pdf_nome;
                    }
                }
            }

            $tamanho_arquivo = !empty($arquivo) ? parserTamanhoArquivo(Storage::size($arquivo),0).'B' : '0MB';

        }
        if(!empty($campo['desenho'])){
            $filtro = 'desenho';
            $id = $campo['desenho'];
            $descricao = $campo['desenho'];

            $tamanho_arquivo = Storage::exists($this->pathPdf.'desenhos/book_'.$id.'.pdf') ? parserTamanhoArquivo(Storage::size($this->pathPdf.'desenhos/book_'.$id.'.pdf'), 0).'B' : '0MB';
            $id = encrypt($id);
        }

        if(isset($segmento)){
            $link = $segmento->descricao;
        }
        elseif(isset($produtoGrupo)){
            $link = $produtoGrupo->descricao;
        }else{
            $link = $descricao;
        }
        
        $dados = [
            'segmentos_id' => !empty($campo['segmentos_id']) ? $campo['segmentos_id'] : '',
            'produto_grupos_id' => !empty($campo['produto_grupos_id']) ? $campo['produto_grupos_id'] : '',
            'desenho' => !empty($campo['desenho']) ? $campo['desenho'] :'',
            'tamanho_arquivo' => $tamanho_arquivo,
            'quantidade_arquivos' => isset($quantidade_arquivos) ? $quantidade_arquivos : '',
            'descricao' => $descricao,
            'route' => route('book_virtual_exibicao_new.download', ['id' => decrypt($id), 'filter' => $filtro]),
            'link_pdf' => route('book_virtual_exibicao_new.link', ['id' => decrypt($id), 'filter' => $filtro, 'link' => $link])
        ];

        return view('programs.book_virtual_exibicao_new.modal.pdf')->with($dados);

    }

    public function modalEnviarPDF(Request $request){
        $campo = $request->only('quantidade_arquivos', 'segmentos_id', 'produto_grupos_id', 'desenho');
        return view('programs.book_virtual_exibicao_new.modal.email')->with([
            'quantidade_arquivos' => $campo['quantidade_arquivos'],
            'segmentos_id' => $campo['segmentos_id'],
            'produto_grupos_id' => $campo['produto_grupos_id'],
            'desenho' => $campo['desenho']
        ]);
    }

    public function downloadPDF($id, $filter, $link = 'false'){

        if($filter == 'segmento'){
            $segmento = Segmento::find($id);
            $arquivo = Storage::exists($this->pathPdf.$segmento->descricao.'.zip') ? Storage::path($this->pathPdf.$segmento->descricao.'.zip') : '';
        }
       
        if($filter == 'grupo'){
            $produtoGrupo = ProdutoGrupo::find($id);
            $pdf_nome = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/',"\t"], '', str_replace(' ', '_', $produtoGrupo->descricao))).'.pdf';
            $arquivo = Storage::exists($this->pathPdf.'book_'.$pdf_nome) ? Storage::path($this->pathPdf.'book_'.$pdf_nome) : '';
            
                
            if(empty($arquivo)){
                if(isset($produtoGrupo->segmento->descricao)){
                    if(Storage::exists($this->pathPdf.$produtoGrupo->segmento->descricao.'/book_'.$pdf_nome)){
                        $arquivo = Storage::path($this->pathPdf.$produtoGrupo->segmento->descricao.'/book_'.$pdf_nome);
                    }
                }
            }

        }

        if($filter == 'desenho'){
            $arquivo = Storage::exists($this->pathPdf.'desenhos/book_'.$id.'.pdf') ? Storage::path($this->pathPdf.'desenhos/book_'.$id.'.pdf') : ''; 
        }

        if($link != 'false'){
            return  response()->file($arquivo);
        }

        return response()->download($arquivo);
    }

    public function envioPDFEmail(BookVirtualEnviarPDFRequest $request){
        $campo = $request->only('email', 'segmentos_id', 'produto_grupos_id', 'desenho');
        
        if(!empty($campo['segmentos_id'])){

            try{
                $segmentos_id = decrypt($campo['segmentos_id']);
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br/>Tente novamente!',
                    'error' => [],
                    'response' => []
                ],422);
            }

            $produtoGrupo = ProdutoGrupo::with('segmento')->where('segmentos_id', $segmentos_id)->get();

            foreach($produtoGrupo as $grupo){
                $pdf_nome = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/',"\t"], '', str_replace(' ', '_', $grupo->descricao))).'.pdf';
                $arquivo = Storage::exists($this->pathPdf.$grupo->segmento->descricao.'/book_'.$pdf_nome) ? $this->pathPdf.$grupo->segmento->descricao.'/book_'.$pdf_nome : '';
            
                if(!empty($arquivo)){
                    $anexo = [$arquivo  => ['as' => basename($arquivo)]];
                    $variaveis = [
                        'texto' => 'Segue em anexo o book do grupo '.$grupo->descricao.'.'
                    ];

                    $retorno = $this->enviarPDFEmail($campo['email'],$variaveis,$anexo, 'envio_pdf');

                    try{
                        if($retorno['status'] == 'error'){
                            throw new \Exception;
                        }
                    }catch(\Exception $e){
                        return response()->json([
                            'status' => 'error',
                            'message' => $retorno['message'],
                            'error' => $retorno['error'],
                            'response' => []
                        ],422);
                    }
                }   
            }
        }

        if(!empty($campo['produto_grupos_id'])){

            try{
                $produto_grupos_id = decrypt($campo['produto_grupos_id']);
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br/>Tente novamente!',
                    'error' => [],
                    'response' => []
                ],422);
            }

            $produtoGrupo = ProdutoGrupo::find($produto_grupos_id);
            $pdf_nome = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/',"\t"], '', str_replace(' ', '_', $produtoGrupo->descricao))).'.pdf';
            $arquivo = Storage::exists($this->pathPdf.$produtoGrupo->segmento->descricao.'/book_'.$pdf_nome) ? $this->pathPdf.$produtoGrupo->segmento->descricao.'/book_'.$pdf_nome : '';
        
            if(!empty($arquivo)){
                $anexo = [$arquivo  => ['as' => basename($arquivo)]];
                $variaveis = [
                    'texto' => 'Segue em anexo o book do grupo '.$produtoGrupo->descricao.'.'
                ];

                $retorno = $this->enviarPDFEmail($campo['email'],$variaveis,$anexo, 'envio_pdf');
                
                try{
                    if($retorno['status'] == 'error'){
                        throw new \Exception;
                    }
                }catch(\Exception $e){
                    return response()->json([
                        'status' => 'error',
                        'message' => $retorno['message'],
                        'error' => $retorno['error'],
                        'response' => []
                    ],422);
                }
            }   
        }

        if(!empty($campo['desenho'])){
            $arquivo = Storage::exists($this->pathPdf.'desenhos/book_'.$campo['desenho'].'.pdf') ? $this->pathPdf.'desenhos/book_'.$campo['desenho'].'.pdf' : '';

            if(!empty($arquivo)){
                $anexo = [$arquivo  => ['as' => basename($arquivo)]];
                $variaveis = [
                    'texto' => 'Segue em anexo o book do desenho '.$campo['desenho'].'.'
                ];

                $retorno = $this->enviarPDFEmail($campo['email'],$variaveis,$anexo, 'envio_pdf');
                
                try{
                    if($retorno['status'] == 'error'){
                        throw new \Exception;
                    }
                }catch(\Exception $e){
                    return response()->json([
                        'status' => 'error',
                        'message' => $retorno['message'],
                        'error' => $retorno['error'],
                        'response' => []
                    ],422);
                }
            }   

        }

        $response = [
            "status" => 'success',
            "message" => $retorno['message'],
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    private function enviarPDFEmail($email,$variaveis,$anexo,$token){
        $EmailObj = new EmailController();
        $retorno = $EmailObj->sendEmailToken('00', $token, [$email], $variaveis, $anexo);

        try{
            if($retorno['status'] == 'error'){
                throw new \Exception;
            }
        }catch(\Exception $e){
            return [
                'status' => 'error',
                'message' => $retorno['message'],
                'error' => $retorno['error']->getMessage(),
                'response' => []
            ];
        }

        return $response = [
            "status" => 'success',
            "message" => $retorno['status'],
            "error" => [],
            "response" => []
        ];
    }

    public function avisoDechegadaProduto(){
        $importacao = Importacao::with('pedidoCompras', 'pedidoCompras.produto.produtoGrupo')
        ->Where('data_embarque_realizado', '>' , Carbon::now()->addDay(-30))
        ->where('pdf_enviado', false)
        ->get();


        foreach($importacao as $produto){
            $pdf_nome = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/',"\t"], '', str_replace(' ', '_', $produto->pedidoCompras->produto->produtoGrupo->descricao))).'.pdf';
            
            $arquivo = Storage::exists($this->pathPdf.'book_'.$pdf_nome) ? $this->pathPdf.'book_'.$pdf_nome : '';

            if(empty($arquivo)){
                if(isset($produto->pedidoCompras->produto->produtoGrupo->segmento->descricao)){
                    if(Storage::exists($this->pathPdf.$produto->pedidoCompras->produto->produtoGrupo->segmento->descricao.'/book_'.$pdf_nome)){
                        $arquivo = $this->pathPdf.$produto->pedidoCompras->produto->produtoGrupo->segmento->descricao.'/book_'.$pdf_nome;
                    }
                }
            }

            $anexo = [$arquivo  => ['as' => basename($arquivo)]];
            
            $user = User::whereIn('tipo_usuario_id', [16,13,14,19])
            ->get();

            foreach($user as $usuario){
                
                $variaveis = [
                    'grupo' => $produto->pedidoCompras->produto->produtoGrupo->descricao,
                    'nome' => $usuario->name
                ];

                $retorno = $this->enviarPDFEmail($usuario->email,$variaveis,$anexo, 'aviso:chegada_produto');
                
                try{
                    if($retorno['status'] == 'error'){
                        throw new \Exception;
                    }
                }catch(\Exception $e){
                    Log::error($retorno['error']);
                }
            }

            $produto->pdf_enviado = true;
            $produto->updated_by = 1;
            $produto->save();
        }
    }

    public function gerarPdfFichatecnica(Request $request){
        $campo = $request->only('id', 'desenho');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br/>Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }
        $produtoGrupo = ProdutoGrupo::find($id);

        $request = new Request([
            'id' => $id,
            'pdf' => true,
            'tipo_venda' => null
        ]);

        try{
            $dados = $this->modalFichaTecnica($request);
        }catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        if(!empty($campo['desenho'])){
            $dados['nome'] = $campo['desenho'];
            $title = $campo['desenho'].'.pdf';

            if(count($produtoGrupo->produtoGrupoDesenho->where('codigo_desenho', $campo['desenho'])->pluck('imagem')) > 0){
                $dados['imagem_pdf'] = Storage::url($this->grupo_patch.'desenhos/'.$produtoGrupo->produtoGrupoDesenho->where('codigo_desenho', $campo['desenho'])->pluck('imagem')->implode(''));
            }else{
                $dados['imagem_pdf'] = URL::asset('images/sem_imagem.jpg');
            }
        }else{
            $title = 'ficha_'.strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/',"\t"], '', str_replace(' ', '_', $produtoGrupo->descricao))).'.pdf';
        }

        $pdfFilePath = $title;
        
        $pdf = PDF::loadView(
            'pdf.book_virtual_ficha_new', 
            $dados,
            [], 
            ['title' => $title, 'margin_bottom' => 1, 'orientation' => 'P', 'format' => 'A4']
        );

        $pdf->save($pdfFilePath);

        return view('pdf.leitura')->with(['caminho' => '/'.$pdfFilePath]);

    }

    public function modalZoom(Request $request){
        $campo = $request->only('imagem');

        return view('programs.book_virtual_exibicao_new.modal.zoom')->with(['imagem' => $campo['imagem']]);
    }

    private function campanhas(){
        $campanhas = Campanha::where('ativo', true)->pluck('nome', 'id');
        return $campanhas;
    }
}
