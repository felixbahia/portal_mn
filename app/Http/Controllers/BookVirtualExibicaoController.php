<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Auth;

use App\BookVirtual;
use App\CarrinhoCompra;
use App\CarrinhoCompraItem;
use App\CepEstado;
use App\ClienteBionexo;
use App\ClienteNasajon;
use App\Segmento;
use App\ItensBookVirtual;
use App\Familia;
use App\Composicao;
use App\CondicoesPagamentoWeb;
use App\Construcao;
use App\DadosClientePedido;
use App\EstabelecimentoCidadeFob;
use App\Sazonalidade;

use App\Http\Requests\BookVirtualBuscaProdutoRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PedidoPortalController;
use App\Http\Requests\BookVirtualAdicionarClienteRequest;
use App\Http\Requests\BookVirtualCarrinhoAdicionarRequest;
use App\Http\Requests\BookVirtualEditarClienteRequest;
use App\Http\Requests\BookVirtualEditarTransportadoraRequest;
use App\Http\Requests\ListaDePrecosRequest;
use App\PedidoItemPortal;
use App\PedidoPortal;
use App\ProdutoEspecificacao;
use App\ProdutosEstoque;
use Carbon\Carbon;
use PDF;

class BookVirtualExibicaoController extends Controller
{

    public $tipos_materiais = [
        'estampados' => 'Estampados',
        'fio_tinto' => 'Fio tinto',
        'lisos' => 'Lisos',
        'lisos_jacquard' => 'Liso Jacquard',
        'lisos_dobby' => 'Liso Dobby'
    ];

    public $categorias = [
        'pronta_entrega' => 'Pronta entrega',
        'lancamento' => 'Lançamento',
        'confeccionados' => 'Confeccionados',
        'outlet' => 'Outlet'
    ];

    public $path = 'public/book_virtual/';
    public $segmentos_path = 'public/segmentos/';

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\BookVirtualExibicao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BookVirtualExibicao');

        $SegmentosObj = Segmento::select();
        $familias = Familia::orderBy('descricao', 'ASC')->pluck('descricao', 'id');
        $segmentos = $SegmentosObj->orderBy('posicao')->pluck('descricao', 'id');
        $segmentos_imagens = $SegmentosObj->get();
        $composicao = Composicao::orderBy('descricao')->pluck('descricao', 'id');
        $construcao = Construcao::orderBy('descricao')->pluck('descricao', 'id');
        $sazonalidade = Sazonalidade::orderBy('descricao')->pluck('descricao', 'id');

        foreach($segmentos_imagens as $segmento){
            $segmentos_imagens_caminho[encrypt($segmento->id)] = [
                'imagem' => Storage::url($this->segmentos_path.$segmento->imagem),
                'descricao' => $segmento->descricao
            ];
        }

        $bookVirtualControllerObj = new BookVirtualController;

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
        $dadosCliente = !empty($dadosClientePedido->id) ? $dadosClientePedido->id : '';
        
        $visualizar_carrinho = "<a href='#' data-cliente='' data-book_id='' data-filtro='' class='bt-carrinho-book'></a>";
        $contador = "<span id='contador' style='font-size: 16px;color: white;'>".$quantidade."</span>";

        return view('programs.book_virtual_exibicao.index')
            ->with([
                'tipos_materiais' => $this->tipos_materiais,
                'segmentos' => $segmentos,
                'segmentos_imagens' => $segmentos_imagens_caminho,
                'categorias' => $this->categorias,
                'familias' => $familias,
                'tipos_materiais' => $bookVirtualControllerObj->tipos_materiais,
                'padroes_codigo' => $bookVirtualControllerObj->padroes_codigo,
                'tipo_gramatura' => $bookVirtualControllerObj->tipo_gramatura,
                'tipo_generos' => $bookVirtualControllerObj->tipo_generos,
                'composicao' => $composicao,
                'construcao' => $construcao,
                'sazonalidade' => $sazonalidade,
                'visualizar_carrinho' => $visualizar_carrinho,
                'contador' => $contador,
            ]);
    }

    public function busca(Request $request, $segmentos_id, $categoria, $filter = null){
        if(Auth::user()->hasPermissionTo("programas App\BookVirtualExibicao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BookVirtualExibicao');

        $segmentos = Segmento::select()->orderBy('posicao')->pluck('descricao', 'id');
        $familias = Familia::orderBy('descricao', 'ASC')->pluck('descricao', 'id');
        $composicaos = Composicao::orderBy('descricao')->pluck('descricao', 'id');
        $construcaos = Construcao::orderBy('descricao')->pluck('descricao', 'id');
        $sazonalidades = Sazonalidade::orderBy('descricao')->pluck('descricao', 'id');
        $bookVirtualControllerObj = new BookVirtualController;

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
        $dadosCliente = !empty($dadosClientePedido->id) ? $dadosClientePedido->id : '';
        
        $visualizar_carrinho = "<a href='#' data-cliente='' data-book_id='' data-filtro='' class='bt-carrinho-book'></a>";
        $contador = "<span id='contador' style='font-size: 16px;color: white;'>".$quantidade."</span>";

        $return = [
            'tipos_materiais' => $this->tipos_materiais,
            'segmentos' => $segmentos,
            'categoria' => $categoria,
            'segmentos_id' => $segmentos_id == 'busca_avancada' ? 'busca_avancada' : decrypt($segmentos_id),
            'categorias' => $this->categorias,
            'familias' => $familias,
            'tipo_gramaturas' => $bookVirtualControllerObj->tipo_gramatura,
            'tipo_generos' => $bookVirtualControllerObj->tipo_generos,
            'composicaos' => $composicaos,
            'construcaos' => $construcaos,
            'sazonalidades' => $sazonalidades,
            'visualizar_carrinho' => $visualizar_carrinho,
            'contador' => $contador,
        ];

        if($filter != null){
            $filtro = decrypt($filter);

            if(isset($filtro['segmentos_id']) && !empty($filtro['segmentos_id'])){
                $return['segmentos_id'] = $filtro['segmentos_id'];
            }
            if(isset($filtro['categoria']) && !empty($filtro['categoria'])){
                $return['categoria'] = $filtro['categoria'];
            }

            $return['familias_id'] = $filtro['familias_id'];
            $return['tipo_material'] = $filtro['tipo_material'];
            $return['num_book'] = $filtro['num_book'];
            $return['artigo'] = $filtro['artigo'];
            $return['nome'] = $filtro['nome'];
            $return['codigo_produto'] = $filtro['codigo_produto'];
            $return['tipo_genero'] = $filtro['tipo_genero'];
            $return['gramatura_tipo'] = $filtro['gramatura_tipo'];
            $return['composicao'] = $filtro['composicao'];
            $return['construcao'] = $filtro['construcao'];
            $return['sazonal'] = $filtro['sazonal'];
        }
        else{
            $return['familias_id'] = '';
            $return['tipo_material'] = '';
            $return['num_book'] = '';
            $return['artigo'] = '';
            $return['nome'] = '';
            $return['codigo_produto'] = '';
            $return['tipo_genero'] = '';
            $return['gramatura_tipo'] = '';
            $return['composicao'] = '';
            $return['construcao'] = '';
            $return['sazonal'] = '';
        }

        return view('programs.book_virtual_exibicao.busca')->with($return);
    }

    public function filter(Request $request){

        $fields = $request->only('segmentos_id', 'familias_id', 'categoria', 'tipo_material', 'num_book', 'artigo', 'nome', 'codigo_produto', 'sazonal', 'construcao', 'composicao', 'gramatura_tipo', 'tipo_genero');

        $bookVirtualQuery = BookVirtual::where('estoque', true);

        if(isset($fields['segmentos_id']) && !empty($fields['segmentos_id'])){
            $bookVirtualQuery->where('segmentos_id', $fields['segmentos_id']);
        }

        if(isset($fields['familias_id']) && !empty($fields['familias_id'])){
            $bookVirtualQuery->where('familias_id', $fields['familias_id']);
        }

        if(isset($fields['tipo_material']) && !empty($fields['tipo_material'])){
            $bookVirtualQuery->where('tipo_material', $fields['tipo_material']);
        }

        if(isset($fields['num_book']) && !empty($fields['num_book'])){
            $bookVirtualQuery->where('num_book', 'ilike', '%' . $fields['num_book'] . '%');
        }

        if(isset($fields['artigo']) && !empty($fields['artigo'])){
            $bookVirtualQuery->where('artigo', 'ilike', '%' . $fields['artigo'] . '%');
        }

        if(isset($fields['nome']) && !empty($fields['nome'])){
            $bookVirtualQuery->where('nome', 'ilike', '%' . $fields['nome'] . '%');
        }

        if(isset($fields['categoria']) && !empty($fields['categoria'])){
            switch ($fields['categoria']) {
                case 'pronta_entrega':
                    $bookVirtualQuery->whereHas('itens_book_virtual', function($query){
                        $query->whereHas('estoque', function($query){
                            $query->where('estoque', '>', 0);
                        })
                        ->whereDoesntHave('produto', function($query){
                            $query->where('linha', 'PROMOCAO');
                        });
                    });
                    break;
                case 'lancamento':
                    $bookVirtualQuery->whereHas('itens_book_virtual', function($query){
                        $query->whereHas('estoque', function($query){
                            $query->where('compras', '>', 0);
                        })
                        ->whereDoesntHave('estoque', function($query){
                            $query->where('estoque', '>', 0);
                        });
                    });
                    break;
                case 'promocional':
                    $bookVirtualQuery->whereHas('itens_book_virtual.produto', function($query){
                        $query->where('linha', 'PROMOCAO');
                    });
                    break;
                case 'confeccionados':
                    $bookVirtualQuery->whereHas('segmento', function($query){
                        $query->where('descricao', 'CONFECCIONADOS');
                    });
                    break;
                case 'outlet':
                    $bookVirtualQuery->whereHas('itens_book_virtual.produto', function($query){
                        $query->where('linha', 'OUTLET');
                    });
                    break;
            }
        }

        if(isset($fields['codigo_produto']) && !empty($fields['codigo_produto'])){
            $bookVirtualQuery->whereHas('itens_book_virtual', function($item) use ($fields){
                $item->where('cod_produto', $fields['codigo_produto']);
            });
        }

        if(isset($fields['sazonal']) && !empty($fields['sazonal'])){
            $bookVirtualQuery->where('sazonalidades_id', $fields['sazonal']);
        }
        if(isset($fields['construcao']) && !empty($fields['construcao'])){
            $bookVirtualQuery->where('construcaos_id', $fields['construcao']);
        }
        if(isset($fields['composicao']) && !empty($fields['composicao'])){
            $bookVirtualQuery->where('composicaos_id', $fields['composicao']);
        }
        if(isset($fields['tipo_genero']) && !empty($fields['tipo_genero'])){
            $bookVirtualQuery->where('genero', $fields['tipo_genero']);
        }
        if(isset($fields['gramatura_tipo']) && !empty($fields['gramatura_tipo'])){
            $bookVirtualQuery->where('gramatura_tipo', $fields['gramatura_tipo']);
        }

        $bookVirtualObj = $bookVirtualQuery
            ->orderBy('artigo', 'asc')
            ->orderBy('num_book', 'asc')
            ->get();

        $dados = [];

        $filtro = encrypt($fields);

        $bookVirtualObj->each(function($book) use(&$dados, $fields, $filtro){
            
            $linha = "<a class='link-book-virtual-card' href='" . route('book_virtual_exibicao.exibicao', ['id' => $book->id, 'filter' => $filtro]) . "'><div class='book'><div class='book-body-div'><p class='book-titulo'>" . $book->artigo . " - " . $book->nome . "</p><p class='book-descricao'>Book " . $book->num_book . "</p></div></div></a>";

            $dados[] = $linha;

        });

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $dados
        ];
        return response()->json($response);
    }

    public function bookVirtualExibicao(Request $request, $filtro, $id){
        ini_set('memory_limit','2048M');
        set_time_limit(300);

        if(Auth::user()->hasPermissionTo("programas App\BookVirtualExibicao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BookVirtualExibicao');
        $fields = $request->only('pdf');


        $filter = decrypt($filtro);

        $bookVirtualQuery = BookVirtual::with('segmento');
        
        if(isset($filter['categoria']) && !empty($filter['categoria'])){
            switch ($filter['categoria']) {
                case 'pronta_entrega':
                    $bookVirtualQuery->with([
                        'itens_book_virtual' => function($query){
                            $query->whereHas('estoque', function($query){
                                $query->where('estoque', '>', 0);
                            })
                            ->whereDoesntHave('produto', function($query){
                                $query->where('linha', 'PROMOCAO');
                            });
                        }
                    ]);
                    break;
                case 'lancamento':
                    $bookVirtualQuery->with(['itens_book_virtual' => function($query){
                        $query->whereHas('estoque', function($query){
                            $query->where('compras', '>', 0);
                        })
                        ->whereDoesntHave('estoque', function($query){
                            $query->where('estoque', '>', 0);
                        });
                    }]);
                    break;
                case 'promocional':
                    $bookVirtualQuery->with(['itens_book_virtual' => function($query){
                            $query->whereHas('produto', function($query){
                                $query->where('linha', 'PROMOCAO');
                            });
                        }
                    ]);
                    break;
                case 'confeccionados':
                    $bookVirtualQuery->whereHas('segmento', function($query){
                        $query->where('descricao', 'CONFECCIONADOS');
                    });
                    break;
                case 'outlet':
                    $bookVirtualQuery->with(['itens_book_virtual' => function($query){
						$query->whereHas('produto', function($query){
								$query->where('linha', 'OUTLET');
							});
						}
					]);
                    break;
            }
        }

        $bookVirtualObj = $bookVirtualQuery->find($id);

        $posicao_book = $this->localizarBook($filter, $bookVirtualObj->num_book);

        if($posicao_book['antes'] != false){
            $link_anterior = "<a class='btn-esquerda' href='" . route('book_virtual_exibicao.exibicao', ['id' => $posicao_book['antes'], 'filter' => $filtro]) . "'></a>";
        }
        else{
            $link_anterior = "";
        }
        if($posicao_book['depois'] != false){
            $link_proximo = "<a class='btn-direita' href='" . route('book_virtual_exibicao.exibicao', ['id' => $posicao_book['depois'], 'filter' => $filtro]) . "'></a>";
        }
        else{
            $link_proximo = "";
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
        $dadosCliente = !empty($dadosClientePedido->id) ? $dadosClientePedido->id : '';
        
        $botao_pdf = "<a target='_blank' href='" . route('book_virtual_exibicao.pdf', ['id' => $id, 'filter' => $filtro]) . "'><button class='btn btn-primary'>Gerar PDF</button></a>";
        $informar_cliente = "<a href='#' id='btn-informar-cliente' data-cliente='".$cliente ."' data-title='Adicionar Carrinho' ". $cliente ."' data-id='". $id ."' data-dados_cliente='". $dadosCliente."'  data-filtro='". $filtro ."' data-pedido_id='". $pedido_id ."' class='btn btn-primary'>Informar Cliente</a>";
        $visualizar_carrinho = "<a href='#' data-cliente='".$cliente."' data-book_id='".$id."' data-filtro='".$filtro."' class='bt-carrinho-book'></a>";
        $contador = "<span id='contador'>".$quantidade."</span>";

        $request = new Request([
            'in_codigos' => $bookVirtualObj->itens_book_virtual->pluck('cod_produto'),
            'book' => true,
            'cliente' => false
        ]);

        $estoques = $this->filtroEstoquePrecos($request);
        $json = json_decode($estoques);

        if(!empty($json)){
            try{
                $json = json_decode($estoques);
                if($json == null){
                    if($estoques->getData()->status == 'error'){
                        throw new \Exception;
                    }
                }
                
            } catch (\Exception $e) {
                $response  = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade!<br/>Tente novamente!',
                    'error' => $estoques->getData()->error,
                    'response' => '',
                ];
    
                return response()->json($response, 422);
                
            }
        }
       
        $bookVirtualObj->load('desenhos', 'segmento');      

        $bookVirtualObj->itens_book_virtual->load(
                'produtoNasajon',
                'produto',
                'produto.foto',
                'produto.produtoGrupo'
            );

        $result = [
            'grupo' => '',
            'marca' => '',
            'linha' => '',
            'descricao' => '',
            'codigo_produto' => ''
        ];

        $result['link_anterior'] = $link_anterior;
        $result['link_proximo'] = $link_proximo;

        $result['categoria'] = $filter['categoria'];
        $result['filtro'] = $filtro;
        
        if(!empty($bookVirtualObj->segmento) && $filter['categoria'] == 'busca_avancada'){
            $result['segmento'] = $bookVirtualObj->segmento->descricao;
            $result['segmento_id'] = $bookVirtualObj->segmento->id;
        }
        else{
            $result['segmento'] = '';
            $result['segmento_id'] = '';
        }
        $result['numero_book'] = $bookVirtualObj->num_book;
        $result['artigo_numero'] = $bookVirtualObj->artigo;
        $result['artigo_nome'] = $bookVirtualObj->nome;

        $result['pecas'] = $bookVirtualObj->itens_book_virtual->first()->produto->produtoGrupo->pecas??'';

        if(isset($bookVirtualObj->img_instrucoes_lavagem) && Storage::exists($bookVirtualObj->img_instrucoes_lavagem)){
            $result['img_instrucoes_lavagem'] = Storage::url($bookVirtualObj->img_instrucoes_lavagem);
        }
        else{
            $result['img_instrucoes_lavagem'] = '';
        }

        $result['desenhos'] = [];
        $result['produtos'] = [];
        
        $segmentos = Segmento::select()->orderBy('posicao')->pluck('descricao', 'id');
        $familias = Familia::orderBy('descricao', 'ASC')->pluck('descricao', 'id');
        $composicaos = Composicao::orderBy('descricao')->pluck('descricao', 'id');
        $construcaos = Construcao::orderBy('descricao')->pluck('descricao', 'id');
        $sazonalidades = Sazonalidade::orderBy('descricao')->pluck('descricao', 'id');

        $bookVirtualControllerObj = new BookVirtualController;

        $result['tipos_materiais'] = $this->tipos_materiais;
        $result['composicaos'] = $composicaos;
        $result['segmentos'] = $segmentos;
        $result['construcaos'] = $construcaos;
        $result['sazonalidades'] = $sazonalidades;
        $result['tipo_generos'] = $bookVirtualControllerObj->tipo_generos;
        $result['tipo_gramaturas'] = $bookVirtualControllerObj->tipo_gramatura;
        $result['familias'] = $familias;
        $result['categorias'] = $this->categorias;
        
        if(!empty($bookVirtualObj->padrao_codigo) ||(!empty($bookVirtualObj->tipo_material) && in_array($bookVirtualObj->tipo_material, ['estampados', 'fio_tinto']))){
            $bookVirtualObj->itens_book_virtual->each(function ($item) use(&$bookVirtualObj){            
                
                if($bookVirtualObj->desenhos->pluck('codigo_desenho')->search(substr($item->cod_produto, 6, 5)) === false && ($bookVirtualObj->padrao_codigo == '6_10' || (is_null($bookVirtualObj->padrao_codigo) && $bookVirtualObj->tipo_material == 'fio_tinto'))){
                    $bookVirtualObj->desenhos->push(collect(['codigo_desenho' => substr($item->cod_produto, 6, 5)]));
                }
                else if($bookVirtualObj->desenhos->pluck('codigo_desenho')->search(substr($item->cod_produto, 7, 5)) === false && ($bookVirtualObj->padrao_codigo == '7_11' || (is_null($bookVirtualObj->padrao_codigo) && $bookVirtualObj->tipo_material == 'estampados'))){
                    $bookVirtualObj->desenhos->push(collect(['codigo_desenho' => substr($item->cod_produto, 7, 5)]));
                }
                else if($bookVirtualObj->desenhos->pluck('codigo_desenho')->search(substr($item->cod_produto, 4, 6)) === false && ($bookVirtualObj->padrao_codigo == '4_6' || (is_null($bookVirtualObj->padrao_codigo) && $bookVirtualObj->segmento->descricao == 'CONFECCIONADOS'))){
                    $bookVirtualObj->desenhos->push(collect(['codigo_desenho' => substr($item->cod_produto, 4, 6)]));
                }
            });

            $bookVirtualObj->desenhos->sortBy('codigo_desenho')->each(function ($desenho) use (&$result, $bookVirtualObj, $estoques, $fields){
                $artigo_desenho = $bookVirtualObj->artigo . $desenho['codigo_desenho'];

                $item = $bookVirtualObj->itens_book_virtual->filter(function ($item) use ($artigo_desenho){
                    return substr($item->cod_produto, 0, strlen($artigo_desenho)) == $artigo_desenho; 
                });

                $estoques_desenho = $estoques->filter(function($estoque) use($artigo_desenho){
                    return substr($estoque['codigo_produto'], 0, strlen($artigo_desenho)) == $artigo_desenho;
                });

                if(count($estoques_desenho) == 0){
                    if(isset($fields['pdf']) && $fields['pdf'] == true){
                        return null;
                    }else{
                        $linha = [];
                    }
                }

                $compras = 0;
                $estoque = 0;
                
                foreach($estoques_desenho as $pronta_entrega){
                    $compras += $pronta_entrega['compras'];
                    $estoque += $pronta_entrega['estoque_total'];
                }


                if($estoque <= 0 && $compras <= 0){
                    if(isset($fields['pdf']) && $fields['pdf'] == true){
                        return null;
                    }else{
                        $linha = [];
                    }
                    
                }else{
                    $linha = [];
                
                    $linha['estoque'] = parserQtd($estoque);
                    $linha['compras'] = parserQtd($compras);

                    $linha['codigo_desenho'] = $desenho['codigo_desenho'];
                    $linha['artigo'] = $bookVirtualObj->artigo;
                    $linha['id'] = '';

                    if(isset($desenho->imagem) && Storage::exists($this->path . $bookVirtualObj->id . '/desenhos/' .  $desenho->imagem)){
                        $linha['imagem'] = Storage::url($this->path . $bookVirtualObj->id . '/desenhos/' .  $desenho->imagem);
                    }
                    else{
                        $linha['imagem'] = $linha['imagem'] = asset('images/sem-imagem.jpg');
                    }

                    if(isset($item->first()->produto->produtoGrupo) && !empty($item->first()->produto->produtoGrupo)){
                        $linha['largura'] = $item->first()->produto->produtoGrupo->largura??'';
                        $linha['gramatura'] = $item->first()->produto->produtoGrupo->gramatura_gml??'';
                        $linha['rendimento'] = $item->first()->produto->produtoGrupo->rendimento??'';
                    }
                    else{
                        $linha['largura'] = '';
                        $linha['gramatura'] = '';
                        $linha['rendimento'] = '';
                    }

                    if(!empty($item->first()->produtoNasajon)){
                        $linha['ncm'] = $item->pluck('produtoNasajon')->pluck('ncm')->unique()->implode(', ');
                        $linha['ean'] = $item->first()->produtoNasajon->codigodebarras;
                        $linha['composicao'] = $item->first()->produtoNasajon->composicao??'';
                    }
                    else{
                        $linha['ncm'] = '';
                        $linha['ean'] = '';
                        $linha['composicao'] = '';
                    }

                    $linha['cliente'] = false;
                    
                    if(Auth::user()->tipo_usuario_id == 21){
                        $linha['cliente'] = true;
                    }
                }

                $result['desenhos'][] = $linha;
            });

            $result['botao_pdf'] = $botao_pdf;
            $result['visualizar_carrinho'] = $visualizar_carrinho;
            $result['informar_cliente'] = $informar_cliente;
            $result['cliente'] = $cliente;
            $result['pedido_id'] = $pedido_id;
            $result['id'] = $id;
            $result['dadosCliente'] = $dadosCliente;
            $result['contador'] = $contador;
            $result['adicionar_automatica_no_carrinho'] = false;

            if(isset($fields['pdf']) && $fields['pdf'] == true){
                return $result;
            }
            else{
                return view('programs.book_virtual_exibicao.book')->with($result);
            }
        }
        else{

            $item = $bookVirtualObj->itens_book_virtual;

            $estoques = $estoques->filter(function($estoque) use ($bookVirtualObj){
                return $bookVirtualObj->itens_book_virtual->pluck('cod_produto')->contains($estoque['codigo_produto']);
            });

            $compras = 0;
            $estoque = 0;
            
            foreach($estoques as $pronta_entrega){
                $compras += $pronta_entrega['compras'];
                $estoque += $pronta_entrega['estoque_total'];
            }

            $linha = [];

            $estoque = isset($estoque) ? $estoque : 0;
            
            $linha['estoque'] = parserQtd($estoque);
            $linha['compras'] = parserQtd($compras);

            $linha['artigo'] = '';
            $linha['id'] = '';
            $linha['codigo_desenho'] = '';

            if(!empty($bookVirtualObj->imagem_tamanho_real) && Storage::exists($this->path . '/' . $bookVirtualObj->id . '/desenhos/' . $bookVirtualObj->imagem_tamanho_real)){
                $linha['imagem'] = Storage::url($this->path . '/' . $bookVirtualObj->id . '/desenhos/' . $bookVirtualObj->imagem_tamanho_real);
            }
            else{
                $linha['imagem'] = $linha['imagem'] = asset('images/sem-imagem.jpg');
            }


            if(isset($item->first()->produto->produtoGrupo) && !empty($item->first()->produto->produtoGrupo)){
                $linha['largura'] = $item->first()->produto->produtoGrupo->largura??'';
                $linha['gramatura'] = $item->first()->produto->produtoGrupo->gramatura_gml??'';
                $linha['rendimento'] = $item->first()->produto->produtoGrupo->rendimento??'';
            }
            else{
                $linha['largura'] = '';
                $linha['gramatura'] = '';
                $linha['rendimento'] = '';
            }

            if(!empty($item->first()->produtoNasajon)){
                $linha['ncm'] = $item->pluck('produtoNasajon')->pluck('ncm')->unique()->implode(', ');
                $linha['ean'] = $item->first()->produtoNasajon->codigodebarras;
                $linha['composicao'] = $item->first()->produtoNasajon->composicao??'';
            }
            else{
                $linha['ncm'] = '';
                $linha['ean'] = '';
                $linha['composicao'] = '';
            }

            if(Auth::user()->tipo_usuario_id == 21){
                $linha['cliente'] = true;
            }
            else{
                $linha['cliente'] = false;
            }

            $result['desenhos'][] = $linha;

            $dadosClientePedido = DadosClientePedido::where('created_by', Auth::id())->first();

            if(!empty($dadosClientePedido->id)){
                $request = new Request([
                    'codigo_cliente' => $dadosClientePedido->cliente_codigo,
                    'in_codigos' => $bookVirtualObj->itens_book_virtual->pluck('cod_produto'),
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
                    'in_codigos' => $bookVirtualObj->itens_book_virtual->pluck('cod_produto'),
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

            foreach($bookVirtualObj->itens_book_virtual->sortBy('cod_produto') as $item){

                $estoque = $estoquePrecos->firstWhere('codigo_produto', $item->cod_produto);

                $linha = [];
                if(isset($estoque['estoque_total'])){
                    if($estoque['estoque_total']  + $estoque['compras'] > 0){
                    
                        if(isset($item->produto->foto) && Storage::exists('public/produto_fotos/' . $item->produto->foto->filename)){
                            $linha['imagem'] = Storage::url('public/produto_fotos/' . $item->produto->foto->filename);
                        }
                        else{
                            $linha['imagem'] = $linha['imagem'] = asset('images/sem-imagem.jpg');
                        }
                        $linha['codigo_produto'] = $item->produto->codigo_produto;
    
                        if(Auth::user()->tipo_usuario_id == 21){
                            $linha['cliente'] = true;
                        }
                        else{
                            $linha['cliente'] = false;
                        }
    
    
                        if(isset($item->produto->produtoGrupo) && !empty($item->produto->produtoGrupo)){
                            $linha['largura'] = $item->produto->produtoGrupo->largura??'';
                            $linha['gramatura'] = $item->produto->produtoGrupo->gramatura_gml??'';
                            $linha['rendimento'] = $item->produto->produtoGrupo->rendimento??'';
                        }
                        else{
                            $linha['largura'] = '';
                            $linha['gramatura'] = '';
                            $linha['rendimento'] = '';
                        }
    
                        
                        $linha['composicao'] = $item->produtoNasajon->composicao??'';
                        $linha['ncm'] = $item->produtoNasajon->ncm;
                        $linha['ean'] = $item->produtoNasajon->codigodebarras;
    
                        $linha['estoque'] = parserQtd($estoque['estoque_total']);
                        $linha['compras'] = parserQtd($estoque['compras']);
                        $linha['preco'] = isset($estoque['preco']) ? $estoque['preco'] : '';
                        
                        $linha['grupo'] = $item->produto->grupo;
                        $linha['marca'] = $item->produto->marca;
                        $linha['linha'] = $item->produto->linha;
                        $linha['descricao'] = $item->produto->descricao;
                        $linha['caracteristicas'] = $item->produto->produtoGrupo->caracteristicas??'';
                        $linha['origem'] = $item->produto->produtoGrupo->origem??'';
    
                        $result['produtos'][] = $linha;
                    }
                }
            }

            $result['botao_pdf'] = $botao_pdf;
            $result['visualizar_carrinho'] = $visualizar_carrinho;
            $result['informar_cliente'] = $informar_cliente;
            $result['cliente'] = $cliente;
            $result['pedido_id'] = $pedido_id;
            $result['id'] = $id;
            $result['filtro'] = $filtro;
            $result['dadosCliente'] = $dadosCliente;
            $result['contador'] = $contador;
            $result['adicionar_automatica_no_carrinho'] = false;

            if(isset($fields['pdf']) && $fields['pdf'] == true){
                return $result;
            }
            else{
                return view('programs.book_virtual_exibicao.book_lisos')->with($result);
            }
        }
    }

    public function retornaInfoProdutos(Request $request){

        $fields = $request->only('id', 'artigo', 'desenho', 'array', 'grupo', 'marca', 'linha', 'descricao', 'codigo_produto', 'id', 'filtro');

        $bookVirtualQuery = ItensBookVirtual::
            with(
                'produtoNasajon',
                'produto',
                'produto.foto',
                'produto.produtoGrupo'
            );

        if((isset($fields['artigo']) && !empty($fields['artigo'])) && (isset($fields['desenho']) && !empty($fields['desenho']))){
            $bookVirtualQuery->where('cod_produto', 'ilike', $fields['artigo'] . $fields['desenho'] . '%')
                ->whereHas('bookVirtual', function($query) use ($fields){
                    $query->where('artigo', $fields['artigo']);
                });
        }
        else if(isset($fields['id']) && !empty($fields['id'])){
            $bookVirtualQuery->whereHas('bookVirtual', function($query) use ($fields){
                $query->where('id', $fields['id']);
            });
        }

        $filtro = decrypt($fields['filtro']);

        if(
            isset($filtro['grupo']) && !is_null($filtro['grupo']) ||
            isset($filtro['marca']) && !is_null($filtro['marca']) ||
            isset($filtro['linha']) && !is_null($filtro['linha']) ||
            isset($filtro['descricao']) && !is_null($filtro['descricao']) ||
            isset($filtro['codigo_produto']) && !is_null($filtro['codigo_produto'])
        ){
            $bookVirtualQuery->whereHas('produto', function($query) use ($filtro){
                if(isset($filtro['grupo']) && !is_null($filtro['grupo'])){
                    $query->where('grupo', 'ilike', '%' . $filtro['grupo'] . '%');
                }
    
                if(isset($filtro['marca']) && !is_null($filtro['marca'])){
                    $query->where('marca', 'ilike', '%' . $filtro['marca'] . '%');
                }
    
                if(isset($filtro['linha']) && !is_null($filtro['linha'])){
                    $query->where('linha', 'ilike', '%' . $filtro['linha'] . '%');
                }
    
                if(isset($filtro['descricao']) && !is_null($filtro['descricao'])){
                    $query->where('descricao', 'ilike', '%' . $filtro['descricao'] . '%');
                }
    
                if(isset($filtro['codigo_produto']) && !is_null($filtro['codigo_produto'])){
                    $query->where('codigo_produto', 'ilike', '%' . $filtro['codigo_produto'] . '%');
                }
            });
        }        
        
        $bookVirtualItens = $bookVirtualQuery->orderBy('cod_produto', 'asc')->get();

        $result = [];

        $dadosClientePedido = DadosClientePedido::where('created_by', Auth::id())->first();

        if(!empty($dadosClientePedido->id)){
            $request = new Request([
                'codigo_cliente' => $dadosClientePedido->cliente_codigo,
                'in_codigos' => $bookVirtualItens->pluck('cod_produto'),
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
                'in_codigos' => $bookVirtualItens->pluck('cod_produto'),
                'book' => true,
                'cliente' => false
            ]);

            $estoquePrecos = $this->filtroEstoquePrecos($request);

            if(!empty($estoquePrecos)){
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

        $bookVirtualItens->unique('cod_produto')->each(function ($item) use (&$result, $fields, $estoquePrecos){

            $linha = [];

            $estoque = $estoquePrecos->firstWhere('codigo_produto', $item->cod_produto);

            if(!isset($item->produto->foto) || empty($item->produto->foto) || !Storage::exists('public/produto_fotos/' . $item->produto->foto->filename)){
                $linha['imagem'] = asset('images/sem-imagem.jpg');
            }
            else{
                $linha['imagem'] = Storage::url('public/produto_fotos/' . $item->produto->foto->filename) . "?" . time();
            }
        
            if($estoque['estoque_total']  + $estoque['compras'] > 0){
                $linha['estoque'] = parserQtd($estoque['estoque_total']);
                $linha['compras'] = parserQtd($estoque['compras']);
                $linha['preco'] = isset($estoque['preco']) ? $estoque['preco'] : '';
            }
            else{
                if(isset($fields['array']) && $fields['array'] == true){
                    return null;
                }else{
                    $linha['estoque'] = parserQtd(0);
                    $linha['compras'] = parserQtd(0);
                    $linha['preco'] = '';
                }
                
            }

            $linha['codigo_produto'] = $item->cod_produto;

            if(isset($item->produto->produtoGrupo) && !empty($item->produto->produtoGrupo)){
                $linha['largura'] = $item->produto->produtoGrupo->largura??'';
                $linha['gramatura'] = $item->produto->produtoGrupo->gramatura_gml??'';
                $linha['rendimento'] = $item->produto->produtoGrupo->rendimento??'';
            }
            else{
                $linha['largura'] = '';
                $linha['gramatura'] = '';
                $linha['rendimento'] = '';
            }

            if(!empty($item->produtoNasajon)){
                $linha['ncm'] = $item->produtoNasajon->ncm;
                $linha['ean'] = $item->produtoNasajon->codigodebarras;
                $linha['composicao'] = $item->produtoNasajon->composicao;
            }
            else{
                $linha['ncm'] = '';
                $linha['ean'] = '';
                $linha['composicao'] = '';
            }

            if(Auth::user()->tipo_usuario_id != 21){
                $linha['cliente'] = false;
            }

            $linha['grupo'] = $item->produto->grupo;
            $linha['marca'] = $item->produto->marca;
            $linha['linha'] = $item->produto->linha;
            $linha['descricao'] = $item->produto->descricao;
            $linha['caracteristicas'] = $item->produto->produtoGrupo->caracteristicas??'';
            $linha['origem'] = $item->produto->produtoGrupo->origem??'';

            if(isset($fields['id']) && !empty($fields['id'])){
                $linha['id'] = $fields['id'];
            }
            else{
                $linha['id'] = '';
            }

            $result[] = $linha;
        });

        if(isset($fields['array']) && $fields['array'] == true){
            return $result;
        }
        else{
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => $result
            ];
            
            return response()->json($response);
        }

    }

    private function localizarBook($fields, $num_book){

        $bookVirtualQuery = BookVirtual::where('estoque', true);

        if(isset($fields['segmentos_id']) && !empty($fields['segmentos_id'])){
            $bookVirtualQuery->where('segmentos_id', $fields['segmentos_id']);
        }

        if(isset($fields['tipo_material']) && !empty($fields['tipo_material'])){
            $bookVirtualQuery->where('tipo_material', $fields['tipo_material']);
        }

        if(isset($fields['artigo']) && !empty($fields['artigo'])){
            $bookVirtualQuery->where('artigo', 'ilike', '%' . $fields['artigo'] . '%');
        }

        if(isset($fields['nome']) && !empty($fields['nome'])){
            $bookVirtualQuery->where('nome', 'ilike', '%' . $fields['nome'] . '%');
        }

        if(isset($fields['categoria']) && !empty($fields['categoria'])){
            switch ($fields['categoria']) {
                case 'pronta_entrega':
                    $bookVirtualQuery->whereHas('itens_book_virtual', function($query){
                        $query->whereHas('estoque', function($query){
                            $query->where('estoque', '>', 0);
                        })
                        ->whereDoesntHave('produto', function($query){
                            $query->where('linha', 'PROMOCAO');
                        });
                    });
                    break;
                case 'lancamento':
                    $bookVirtualQuery->whereHas('itens_book_virtual', function($query){
                        $query->whereHas('estoque', function($query){
                            $query->where('compras', '>', 0);
                        })
                        ->whereDoesntHave('estoque', function($query){
                            $query->where('estoque', '>', 0);
                        });
                    });
                    break;
                case 'promocional':
                    $bookVirtualQuery->whereHas('itens_book_virtual.produto', function($query){
                        $query->where('linha', 'PROMOCAO');
                    });
                    break;
                case 'confeccionados':
                    $bookVirtualQuery->whereHas('segmento', function($query){
                        $query->where('descricao', 'CONFECCIONADOS');
                    });
                    break;
                case 'outlet':
                    $bookVirtualQuery->whereHas('itens_book_virtual.produto', function($query){
                        $query->where('linha', 'OUTLET');
                    });
                    break;
            }
        }

        if(isset($fields['codigo_produto']) && !empty($fields['codigo_produto'])){
            $bookVirtualQuery->whereHas('itens_book_virtual', function($item) use ($fields){
                $item->where('cod_produto', $fields['codigo_produto']);
            });
        }

        $bookVirtualObj = $bookVirtualQuery
            ->orderBy('num_book')
            ->get();

        $pagina = $bookVirtualObj->search(function($book) use($num_book){
            return $book->num_book == $num_book;
        });

        return [
            'pagina' => $pagina,
            'antes' => $bookVirtualObj[$pagina - 1]->id??false,
            'depois' =>  $bookVirtualObj[$pagina + 1]->id??false
        ];
    }

    public function bookVirtualPdf(Request $request, $filtro, $id){
        ini_set('memory_limit','2048M');

        $request['pdf'] = true;

        if($id == 'busca'){
            $dados = $this->buscaProdutos($request, $filtro);
            $title = 'Exportação do resultado da busca';
            $dados['numero_book'] = 'busca';
        }
        else{
            $dados = $this->bookVirtualExibicao($request, $filtro, $id);
            $title = 'Book '.$dados['numero_book'] . ' - ' . $dados['artigo_nome'];
        }

        if(empty($dados['produtos'])){

            $dados['produtos'] = [];

            foreach($dados['desenhos'] as $desenho){
                $infoProduto = $this->retornaInfoProdutos(new Request(['array' => true, 'artigo' => $dados['artigo_numero'], 'id' => $desenho['id'], 'desenho' => $desenho['codigo_desenho'], 'filtro' => $filtro]));

                if(!empty($infoProduto)){
                    $dados['produtos'] = array_merge($dados['produtos'], $infoProduto);
                }
                else{
                    $key = array_search($desenho, $dados['desenhos']);
                    if($key!==false){
                        unset($dados['desenhos'][$key]);
                    }
                }
            }
        }

        $pdfFilePath = 'book_'.$dados['numero_book'] . '.pdf';
		$pdf = PDF::loadView(
			'pdf.book_virtual', 
			$dados,
			[], 
			['title' => $title, 'margin_bottom' => 1, 'orientation' => 'P', 'format' => 'A4']
		);
        return $pdf->download($pdfFilePath);
    }

    public function gerarLinkBusca(Request $request){

        $fields = $request->only('categoria', 'segmentos_id', 'familias_id', 'categoria', 'tipo_material', 'num_book', 'artigo', 'nome', 'codigo_produto',  'cod_produto', 'tipo_genero', 'gramatura_tipo', 'composicao', 'construcao', 'sazonal');
        $segmento = 'busca_avancada';
        $categoria = 'busca_avancada';
        $filter = encrypt($fields);

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'link' => route('book_virtual_exibicao.busca', ['segmento' => $segmento, 'categoria' => $categoria, 'filter' => $filter])
            ]
        ];
        
        return response()->json($response);
    }

    public function gerarLinkBuscaProdutos(BookVirtualBuscaProdutoRequest $request){

        $fields = $request->only('grupo', 'marca', 'linha', 'descricao', 'codigo_produto', 'adicionar_automatica_no_carrinho');
        $categoria = 'busca_avancada';
        $filter = encrypt($fields);

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'link' => route('book_virtual_exibicao.busca_produtos', ['filter' => $filter])
            ]
        ];
        
        return response()->json($response);
    }

    public function buscaProdutos(Request $request, $filtro){

        if(Auth::user()->hasPermissionTo("programas App\BookVirtualExibicao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BookVirtualExibicao');

        $fields = $request->only('pdf');
        $campos = decrypt($filtro);

        $itensBookQuery = ItensBookVirtual::with([
                'produto',
                'produto.estoque',
                'produto.produtoGrupo',
                'produtoNasajon',
                'bookVirtual',
                'bookVirtual.desenhos',
				'bookVirtual.itens_book_virtual',
				'bookVirtual.itens_book_virtual.produtoNasajon',
				'bookVirtual.itens_book_virtual.produto',
				'bookVirtual.itens_book_virtual.produto.produtoGrupo'
            ]);

        $itensBookQuery->whereHas('produto', function($query) use ($campos){

            if(isset($campos['grupo']) && !is_null($campos['grupo'])){
                $query->where('grupo', 'ilike', '%' . $campos['grupo'] . '%');
            }

            if(isset($campos['marca']) && !is_null($campos['marca'])){
                $query->where('marca', 'ilike', '%' . $campos['marca'] . '%');
            }

            if(isset($campos['linha']) && !is_null($campos['linha'])){
                $query->where('linha', 'ilike', '%' . $campos['linha'] . '%');
            }

            if(isset($campos['descricao']) && !is_null($campos['descricao'])){
                $query->where('descricao', 'ilike', '%' . $campos['descricao'] . '%');
            }

            if(isset($campos['codigo_produto']) && !is_null($campos['codigo_produto'])){
                $query->where('codigo_produto', 'ilike', '%' . $campos['codigo_produto'] . '%');
            }
			$query->where('ativo', true);
        });

        $itens = $itensBookQuery->get();

        $CarrinhoCompraObj = CarrinhoCompra::with('dadosClientePedido.cliente')
        ->where('created_by', Auth::id())
        ->where('finalizado', false)
        ->orderBy('id', 'desc');

        $quantidade = 0;
        $pedido = '';

        $CarrinhoCompraObj->each(function($item) use(&$quantidade, &$pedido){
            $quantidade += $item->carrinhoCompraItens->count('id');
            $pedido = $item->pedido_id;
        });

        $dadosClientePedido = DadosClientePedido::with('cliente')->where('created_by', Auth::id())->first();

        $cliente = isset($dadosClientePedido->dadosClientePedido->cliente->nome) ? $dadosClientePedido->cliente->nome.' - '.$dadosClientePedido->cliente->cpf_cnpj : '';
        $pedido_id = !empty($pedido) ? $pedido : '';
        $dadosCliente = !empty($dadosClientePedido->id) ? $dadosClientePedido->id : '';
        $id = 'busca';
        
        $botao_pdf = "<a target='_blank' href='" . route('book_virtual_exibicao.pdf', ['id' => $id, 'filter' => $filtro]) . "'><button class='btn btn-primary'>Gerar PDF</button></a>";
        $informar_cliente = "<a href='#' id='btn-informar-cliente' data-cliente='".$cliente ."' data-title='Adicionar Carrinho' ". $cliente ."' data-id='". $id ."' data-dados_cliente='". $dadosCliente."'  data-filtro='". $filtro ."' data-pedido_id='". $pedido_id ."' class='btn btn-primary'>Informar Cliente</a>";
        $visualizar_carrinho = "<a href='#' data-cliente='".$cliente."' data-book_id='".$id."' data-filtro='".$filtro."' class='bt-carrinho-book'></a>";
        $contador = "<span id='contador'>".$quantidade."</span>";


        if(!empty($dadosClientePedido->id)){
            $request = new Request([
                'codigo_cliente' => $dadosClientePedido->cliente_codigo,
                'in_codigos' => $itens->pluck('cod_produto'),
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
                'in_codigos' => $itens->pluck('cod_produto'),
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

        $estoques = $estoquePrecos->filter(function($estoque) use ($itens){
            return $itens->pluck('cod_produto')->contains($estoque['codigo_produto']);
        });

        $compras = 0;
        
        foreach($estoques as $pronta_entrega){
            $compras += $pronta_entrega['compras'];
        }

        
        $result = [
            'grupo' => '',
            'marca' => '',
            'linha' => '',
            'descricao' => '',
            'codigo_produto' => ''
        ];

        $result['filtro'] = $filtro;

        $titulo = [];

        if(isset($campos['grupo']) && !is_null($campos['grupo'])){
            $result['grupo'] = $campos['grupo'];
            $titulo[] = $campos['grupo'];
        }

        if(isset($campos['marca']) && !is_null($campos['marca'])){
            $result['marca'] = $campos['marca'];
            $titulo[] = $campos['marca'];
        }
        if(isset($campos['linha']) && !is_null($campos['linha'])){
            $result['linha'] = $campos['linha'];
            $titulo[] = $campos['linha'];
        }
        if(isset($campos['descricao']) && !is_null($campos['descricao'])){
            $result['descricao'] = $campos['descricao'];
            $titulo[] = $campos['descricao'];
        }
        if(isset($campos['codigo_produto']) && !is_null($campos['codigo_produto'])){
            $produtoEspecificacoes = ProdutoEspecificacao::where('codigo_produto', 'ilike', '%' . $campos['codigo_produto'] . '%')->first();
            $result['codigo_produto'] = $campos['codigo_produto'];
            $titulo[] = $produtoEspecificacoes->grupo;
        }

        $result['titulo'] = implode(' - ', $titulo);

        $linha['filtros'] = $campos;
        $estoque = 0;

        foreach($estoquePrecos as $item){
            if(!isset($item['estoque_total'])){
                $estoque += 0;
            }else{
                $estoque += $item['estoque_total'];
            }
            
        }

        $linha['estoque'] = parserQtd($estoque + $compras);
        $linha['imagem'] = asset('images/sem-imagem.jpg');

        $linha['largura'] = '';
        $linha['gramatura'] = '';
        $linha['rendimento'] = '';
      
        if(!empty($itens->first()->produtoNasajon)){
            $linha['ncm'] = $itens->pluck('produtoNasajon')->pluck('ncm')->unique()->implode(', ');
            $linha['ean'] = $itens->first()->produtoNasajon->codigodebarras;
            $linha['composicao'] = $itens->first()->produtoNasajon->composicao;
        }
        else{
            $linha['composicao'] = '';
            $linha['ncm'] = '';
            $linha['ean'] = '';
        }

        if(Auth::user()->tipo_usuario_id == 21){
            $linha['cliente'] = true;
        }
        else{
            $linha['cliente'] = false;
        }
        
        $tipo_book = '';
        $artigo_numero = '';
        $num_book = '';
        $pecas = $itens->first()->produtoGrupo->pecas??'';
        $img_instrucoes_lavagem = '';

        $itens->pluck('bookVirtual')->unique()->each(function ($book) use (&$result, $itens, $estoques, &$tipo_book, 
            &$artigo_numero, &$num_book, &$pecas, &$img_instrucoes_lavagem){
            
            if(!empty($book->padrao_codigo) ||(!empty($book->tipo_material) && in_array($book->tipo_material, ['estampados', 'fio_tinto']))){
                $tipo_book = 'estampado';

                $book->desenhos->sortBy('codigo_desenho')->each(function ($desenho) use (&$result, $book, $estoques, 
                    &$artigo_numero, &$num_book, &$pecas, &$img_instrucoes_lavagem){

                    $artigo_desenho = $book->artigo . $desenho->codigo_desenho;
    
                    $item = $book->itens_book_virtual->filter(function ($item) use ($artigo_desenho){
                        return substr($item->cod_produto, 0, strlen($artigo_desenho)) == $artigo_desenho; 
                    });
    
                    $estoques_desenho = $estoques->filter(function($estoque) use($artigo_desenho){
                        return substr($estoque['codigo_produto'], 0, strlen($artigo_desenho)) == $artigo_desenho;
                    });

                    $compras = 0;
                    $estoque = 0;

                    $artigo_numero = $book->artigo;
                    $num_book = $book->num_book;

                    if(isset($book->img_instrucoes_lavagem) && Storage::exists($book->img_instrucoes_lavagem)){
                        $img_instrucoes_lavagem = Storage::url($book->img_instrucoes_lavagem);
                    }
                    else{
                        $img_instrucoes_lavagem = '';
                    }
                    
                    foreach($estoques_desenho as $pronta_entrega){
                        $compras += $pronta_entrega['compras'];
                        $estoque += $pronta_entrega['estoque_total'];
                    }

                    if($estoque <= 0 && $compras <= 0){
                        if(isset($fields['pdf']) && $fields['pdf'] == true){
                            return null;
                        }else{
                            $linha = [];
                        }
                    }else{
                        $linha = [];

                        $linha['estoque'] = parserQtd($estoque);
                        $linha['compras'] = parserQtd($compras);
                        $linha['artigo'] = $book->artigo;
                        $linha['cod_produto'] = '';
                        $linha['codigo_desenho'] = $desenho->codigo_desenho;
                        $linha['id'] = $desenho->id;
        
                        if(!empty($book->imagem_tamanho_real) && Storage::exists($this->path . '/' . $book->id . '/desenhos/' . $book->imagem_tamanho_real)){
                            $linha['imagem'] = Storage::url($this->path . '/' . $book->id . '/desenhos/' . $book->imagem_tamanho_real);
                        }
                        else{
                            $linha['imagem'] = asset('images/sem-imagem.jpg');
                        }
        
                        if(isset($item->first()->produto->produtoGrupo) && !empty($item->first()->produto->produtoGrupo)){
                            $linha['largura'] = $item->first()->produto->produtoGrupo->largura??'';
                            $linha['gramatura'] = $item->first()->produto->produtoGrupo->gramatura_gml??'';
                            $linha['rendimento'] = $item->first()->produto->produtoGrupo->rendimento??'';
                        }
                        else{
                            $linha['largura'] = '';
                            $linha['gramatura'] = '';
                            $linha['rendimento'] = '';
                        }
        
                        if(!empty($item->first()->produtoNasajon)){
                            $linha['ncm'] = $item->pluck('produtoNasajon')->pluck('ncm')->unique()->implode(', ');
                            $linha['ean'] = $item->first()->produtoNasajon->codigodebarras;
                            $linha['composicao'] = $item->first()->produtoNasajon->composicao;
                        }
                        else{
                            $linha['ncm'] = '';
                            $linha['ean'] = '';
                            $linha['composicao'] = '';
                        }
        
                        $linha['cliente'] = false;

                    }
                    $result['desenhos'][$desenho->codigo_desenho] = $linha;
                });
              
				if(isset($result['desenhos'])){
                	$result['desenhos'] = collect($result['desenhos'])->unique()->toArray();
				}else{
					$result['desenhos'] = [];
				}
            }
            else{
                $tipo_book = 'liso';
				if(!isset($result['desenhos'])){
                	$result['desenhos'] = [];
				}
                $item = $itens->where('num_book', $book->num_book)->first();

                $compras = 0;
                $estoque = 0;
                
                foreach($estoques as $pronta_entrega){
                    $compras += $pronta_entrega['compras'];
                    $estoque += $pronta_entrega['estoque_total'];
                }

                $linha = [];

                $artigo_numero = $book->artigo;
                $num_book = $book->num_book;

                if(isset($book->img_instrucoes_lavagem) && Storage::exists($book->img_instrucoes_lavagem)){
                    $img_instrucoes_lavagem = Storage::url($book->img_instrucoes_lavagem);
                }
                else{
                    $img_instrucoes_lavagem = '';
                }

                $linha['estoque'] = parserQtd($estoque);
                $linha['compras'] = parserQtd($compras);

                $linha['artigo'] = '';
                $linha['id'] = '';
                $linha['codigo_desenho'] = '';

                if(!empty($book->imagem_tamanho_real) && Storage::exists($this->path . '/' . $book->id . '/desenhos/' . $book->imagem_tamanho_real)){
                    $linha['imagem'] = Storage::url($this->path . '/' . $book->id . '/desenhos/' . $book->imagem_tamanho_real);
                }
                else{
                    $linha['imagem'] = asset('images/sem-imagem.jpg');
                }

                if(isset($item->produto->produtoGrupo) && !empty($item->produto->produtoGrupo)){
                    $linha['largura'] = $item->produto->produtoGrupo->largura??'';
                    $linha['gramatura'] = $item->produto->produtoGrupo->gramatura_gml??'';
                    $linha['rendimento'] = $item->produto->produtoGrupo->rendimento??'';
                }
                else{
                    $linha['largura'] = '';
                    $linha['gramatura'] = '';
                    $linha['rendimento'] = '';
                }

                if(!empty($item->produtoNasajon)){
                    $linha['ncm'] = $item->produtoNasajon->ncm;
                    $linha['ean'] = $item->produtoNasajon->codigodebarras;
                    $linha['composicao'] = $item->produtoNasajon->composicao;
                }
                else{
                    $linha['ncm'] = '';
                    $linha['ean'] = '';
                    $linha['composicao'] = '';
                }
                unset($item);

                $linha['cliente'] = false;

                $result['desenhos'] = [$linha];
				$result['produtos'] = [];
				return false;
				
            }
        });


        $result['link_anterior'] = '';
        $result['link_proximo'] = '';
        $result['img_instrucoes_lavagem'] = $img_instrucoes_lavagem;
        $result['artigo_numero'] = $artigo_numero;
        $result['numero_book'] = $num_book;
        $result['segmento'] = '';
        $result['pecas'] = $pecas;

        $result['botao_pdf'] = $botao_pdf;
        $result['visualizar_carrinho'] = $visualizar_carrinho;
        $result['informar_cliente'] = $informar_cliente;
        $result['cliente'] = $cliente;
        $result['pedido_id'] = $pedido_id;
        $result['dadosCliente'] = $dadosCliente;
        $result['contador'] = $contador;

        $segmentos = Segmento::select()->orderBy('posicao')->pluck('descricao', 'id');
        $familias = Familia::orderBy('descricao', 'ASC')->pluck('descricao', 'id');
        $composicaos = Composicao::orderBy('descricao')->pluck('descricao', 'id');
        $construcaos = Construcao::orderBy('descricao')->pluck('descricao', 'id');
        $sazonalidades = Sazonalidade::orderBy('descricao')->pluck('descricao', 'id');

        $bookVirtualControllerObj = new BookVirtualController;

        $result['tipos_materiais'] = $this->tipos_materiais;
        $result['segmentos'] = $segmentos;
        $result['composicaos'] = $composicaos;
        $result['segmentos'] = $segmentos;
        $result['construcaos'] = $construcaos;
        $result['sazonalidades'] = $sazonalidades;
        $result['tipo_generos'] = $bookVirtualControllerObj->tipo_generos;
        $result['tipo_gramaturas'] = $bookVirtualControllerObj->tipo_gramatura;
        $result['familias'] = $familias;
        $result['categorias'] = $this->categorias;
        $result['id'] = '';
        $result['adicionar_automatica_no_carrinho'] = isset($campos['adicionar_automatica_no_carrinho'])? $campos['adicionar_automatica_no_carrinho'] : false;
        $result['codigo_produto'] = isset($campos['codigo_produto'])? $campos['codigo_produto'] : '';
        
        if(!empty($result['desenhos'])){
            if(isset($fields['pdf']) && $fields['pdf'] == true){
                return $result;
            }
            else{
				return view('programs.book_virtual_exibicao.book')->with($result);
            }
        }
        else{
            return view('programs.book_virtual_exibicao.erro')
                ->with([
                    'tipos_materiais' => $this->tipos_materiais,
                    'segmentos' => $segmentos,
                    'categorias' => $this->categorias,
                    'filtros' => $campos,
                    'adicionar_automatica_no_carrinho' => isset($campos['adicionar_automatica_no_carrinho'])? $campos['adicionar_automatica_no_carrinho'] : false,
                    'codigo_produto' => isset($campos['codigo_produto'])? $campos['codigo_produto'] : '',
                ]);
        }

    }

    public function autocomplete(Request $request){

        $fields = $request->only('campo', 'term', 'filter');
      
        $itensBookQuery = ItensBookVirtual::with(['produto'=>function($query) use ($fields){
			$query->where('ativo', true);
            $query->orderBy('descricao');
		}])
        ->distinct('cod_produto');

        parse_str($fields['filter'], $filter);
		$itensBookQuery->whereHas('estoque', function($query){
			$query->where('estoque', '>', 0);
			$query->orwhere('compras', '>', 0);
		});
        $itensBookQuery->whereHas('produto', function($query) use ($fields, $filter){
            
            if(isset($filter['grupo']) && !empty($filter['grupo']) && $fields['campo'] != 'grupo'){
                $query->where('grupo', 'ilike', $filter['grupo'] . '%');
            }

            if(isset($filter['marca']) && !empty($filter['marca']) && !in_array($fields['campo'], ['grupo', 'marca'])){
                $query->where('marca', 'ilike', $filter['marca'] . '%');
            }

            if(isset($filter['linha']) && !empty($filter['linha']) && !in_array($fields['campo'], ['grupo', 'marca', 'linha'])){
                $query->where('linha', 'ilike', $filter['linha'] . '%');
            }

            $query->where($fields['campo'], 'ilike', $fields['term'] . '%');
			$query->where('ativo', true);
        });

        $itensBookObj = $itensBookQuery->get();
        $response = $itensBookObj->pluck('produto.' . $fields['campo'])->toArray();
		$response = array_unique(array_values($response));
		sort($response);
        return response()->json($response);

    } 

    public function modalAdicionar(Request $request){
        $campo = $request->only(
            'produto_codigo',
            'produto',
            'carrinho',
            'book_id',
            'filtro',
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
        }
        elseif(isset($campo['produto'])){
            $dados['produto_codigo'] = $campo['produto'];
        }else{
            $dados['produto_codigo'] = '';
        }

        $dados['book_id'] = isset($campo['book_id']) ? $campo['book_id'] : '';
        $dados['filtro'] = isset($campo['filtro']) ? $campo['filtro'] : '';
        $dados['dados_cliente_id'] = $campo['dados_cliente_id'];
        $dados['informar_cliente'] = isset($campo['informar_cliente']) ? $campo['informar_cliente'] : '';
       
        unset($dados['pedido']['tipo_venda_lista']['pedido_futuro_venda']);
        unset($dados['pedido']['tipo_venda_lista']['pedido_futuro_triangular']);
        unset($dados['pedido']['tipo_venda_lista']['pedido_orgaopublico']);
        unset($dados['pedido']['tipo_venda_lista']['pedido_pilotagem']);
        unset($dados['pedido']['tipo_venda_lista']['remessa_faturamento']);

        $dadosClientePedido = DadosClientePedido::with('cliente', 'clienteContaEOrdem')
            ->find($campo['dados_cliente_id']);

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

            return view('programs.book_virtual_exibicao.modal.adicionar')->with($dados);
        }
        elseif(!empty($campo['id'])){
            return view('programs.book_virtual_exibicao.modal.adicionar')->with($dados);
        }
        else{
            return view('programs.book_virtual_exibicao.modal.adicionar')->with($dados);
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
            $produtoEstoque = ProdutosEstoque::select('*', DB::raw('
                (CASE 
                    WHEN estabelecimento = \'03\' or estabelecimento = \'04\' THEN estoque - reserva - (saldo_fiscal + saldo_movimento_nao_efetivado)
                     ELSE estoque - reserva
                END) as estoque_total'))
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
                $estoquePrecos[$produto->codigo_produto]['compras'] += $produto->compras_aberto;
                $estoquePrecos[0]['compras'] = 0;
                $estoquePrecos[0]['codigo_produto'] = 0;
            }
            return collect($estoquePrecos);
        }else{
            $preco_frete = $this->fretePreco($campos);
            $frete = $preco_frete['preco'];
            $ClienteNasajon = ClienteNasajon::where('codigo', $campos['codigo_cliente'])->first();
            $estadoObj = CepEstado::find($ClienteNasajon->uf);
            $codigo_produto = isset($campos['in_codigos']) ? $campos['in_codigos'] : [$campos['produto_codigo']];

            $produtoEstoque = ProdutosEstoque::select('*', DB::raw('
                (CASE 
                    WHEN estabelecimento = \'03\' or estabelecimento = \'04\' THEN estoque - reserva - (saldo_fiscal + saldo_movimento_nao_efetivado)
                     ELSE estoque - reserva
                END) as estoque
            '))
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
                    $CondicoesPagamentoWeb = CondicoesPagamentoWeb::find($campos['condicao_pagamento']);
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
                        'estoque' => $produto->estoque > 0 ? parserQtd($produto->estoque) : parserQtd(0)
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
                    $estoquePrecos[$produto->codigo_produto]['estoque_total'] += $produto->estoque > 0 ? $produto->estoque : 0;
                    $estoquePrecos[$produto->codigo_produto]['compras'] += $produto->compras_aberto;
                    $estoquePrecos[0]['compras'] = 0;
                    $estoquePrecos[0]['codigo_produto'] = 0;
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

    public function adicionarCarrinho(BookVirtualCarrinhoAdicionarRequest $request){
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
            'adicionar_transportadora'
        );

        $CarrinhoCompra = CarrinhoCompra::with('carrinhoCompraItens')
            ->where('estabelecimento', $campos['estabelecimento'])
            ->where('finalizado', false)
            ->where('created_by', Auth::id())
            ->first();
            

        $campos['estabelecimento'] = (integer)$campos['estabelecimento'];

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
        return view('programs.book_virtual_exibicao.modal.adicionar_transportadora');
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

        return view('programs.book_virtual_exibicao.modal.editar_transportadora')->with($itens);
    }

    public function editarTransportadora(BookVirtualEditarTransportadoraRequest $request){
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

    public function adicionarCliente(BookVirtualAdicionarClienteRequest $request){
        $campos = $request->only(
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
            'tipo_venda',
            'codigo_cliente_conta_e_ordem'
        );

        $DadosClientePedidoObj = new DadosClientePedido;
        $DadosClientePedidoObj->cliente_codigo = $campos['codigo_cliente'];
        $DadosClientePedidoObj->tipo_frete = $campos['transportadora_tipo_frete'];
        $DadosClientePedidoObj->condicao_pagamento = $campos['condicao_pagamento'];
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

    public function editarCliente(BookVirtualEditarClienteRequest $request){
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

        if(count($CarrinhoCompraObj) == 0){
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

        foreach($CarrinhoCompraObj as $pedido){

            $dados_cliente_id = $pedido->dadosClientePedido->id;
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

        $request_modal = new Request([
            'dados_cliente_id' => $dados_cliente_id,
            'carrinho' => true,
            'visualizar_carrinho' => true
        ]); 
 
        $dadosCliente = $this->modalAdicionar($request_modal);

        $dadosCliente['dados'] = $dados;
        $dadosCliente['filtro'] = $produtos;
        $dadosCliente['valor_total'] = parserValor($valor_total);

        return view('programs.book_virtual_exibicao.modal.editar')->with($dadosCliente);
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

        return view('programs.book_virtual_exibicao.modal.deletar')->with(['pedido' => $pedido, 'item' => $item, 'carrinho' => $carrinho]);

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
        return view('programs.book_virtual_exibicao.modal.deletar')->with(['pedido' => $pedido, 'item' => $item, 'carrinho' => $carrinho]);
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
        return view('programs.book_virtual_exibicao.modal.deletar')->with(['pedido' => $pedido, 'item' => $item, 'carrinho' => $carrinho]);

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
}
