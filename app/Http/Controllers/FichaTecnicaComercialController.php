<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use PDF;

use Illuminate\Support\Facades\Storage;

use App\ProdutoEspecificacao;
use App\AliquotaPreco;
use App\ComprasNasajon;
use App\ProdutosEstoque;
use App\CarrinhoCompra;
use App\PedidoPortal;
use App\EstabelecimentoCidadeFob;

use App\Http\Controllers\UserCamposSalvoController;
use App\Http\Controllers\ListagemDePrecosController;

use App\Http\Requests\ListaDePrecosRequest;
use App\Produto;
use App\ProdutoGrupo;
use Illuminate\Support\Facades\DB;

class FichaTecnicaComercialController extends Controller
{
    public function modalDetalhes(Request $request){
        $fields = $request->only('codigo', 'estabelecimentos', 'estoque_produto_entrega', 'carrinho');

        $UserCamposSalvoControllerObj = new UserCamposSalvoController('App\ListagemDePrecos');
        $campos_salvos = $UserCamposSalvoControllerObj->returnCamposSalvos();

        $precoObj = new ListagemDePrecosController;

        $estabelecimentos = explode("-", $fields['estabelecimentos']);

        $estoque_produto_entrega = explode(";", $fields['estoque_produto_entrega']);
        

        $estabelecimento_descricao = returnEmpresasNasajonView();

        $precos = [];
        $precos_programado = [];

        $dados_carrinhos = $this->verificarCarrinho(true);

        if(!empty($fields['estabelecimentos'])){
            foreach($estabelecimentos as $index => $estabelecimento){
                $estoque_produto_entrega[$index] = str_replace($estabelecimento_descricao[intval($estabelecimento)], "", $estoque_produto_entrega[$index]);
                $estoque_produto_entrega[$index] = str_replace("-", "", $estoque_produto_entrega[$index]);
                $estoque_produto_entrega[$index] = trim($estoque_produto_entrega[$index]);
                if(!empty($estabelecimento)){
                    switch($estabelecimento){
                        case "03": 
                            $origem = "RO";
                            break;
                        case "04": 
                            $origem = "TO";
                            break;
                        default:
                            $origem = "SP";
                            break;
                    }
                    
                    if(!empty($dados_carrinhos['carinho'])){
                        $arr['origem'] = $origem;
                        $arr['produto'] = $fields['codigo'];
                        $arr['moeda'] = 'real';
                        if(substr_count( $dados_carrinhos['coluna'], "prazo_") !== 0){
                            $arr['prazo_medio'] = intval(substr( $dados_carrinhos['coluna'], 5, 2));
                        }else{
                            $arr['prazo_medio'] = '';
                        }
                        $arr['frete'] = empty($dados_carrinhos['frete'])? 'cif' : $dados_carrinhos['frete'];
                        $arr['estado'] = empty($dados_carrinhos['estado'])? 'SP' : $dados_carrinhos['estado'];
                        $arr['tipo_cliente'] = empty($dados_carrinhos['tipo_cliente'])? 'normal' : $dados_carrinhos['tipo_cliente'];
                        $arr['coluna'] = empty($dados_carrinhos['coluna'])? 'coluna_a' :$dados_carrinhos['coluna'] ;
                    }else{
                        $arr['origem'] = $origem;
                        $arr['produto'] = $fields['codigo'];
                        $arr['moeda'] = 'real';
                        if(!empty( $campos_salvos['coluna'])){
                            if(substr_count( $campos_salvos['coluna'], "prazo_") !== 0){
                                $arr['prazo_medio'] = intval(substr( $campos_salvos['coluna'], 5, 2));
                            }else{
                                $arr['prazo_medio'] = '';
                            }
                        }
                        $arr['frete'] = empty($campos_salvos['frete'])? 'cif' : $campos_salvos['frete'];
                        $arr['estado'] = empty($campos_salvos['estado'])? 'SP' : $campos_salvos['estado'];
                        $arr['tipo_cliente'] = empty($campos_salvos['tipo_cliente'])? 'normal' : $campos_salvos['tipo_cliente'];
                        $arr['coluna'] = empty($campos_salvos['coluna'])? 'coluna_a' :$campos_salvos['coluna'] ;
                    }
    
                    $items = new ListaDePrecosRequest($arr);
                    
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]] = $precoObj->filter($items, false, false, true, true, false, false, false);
                    if(!empty($precos[$estabelecimento_descricao[intval($estabelecimento)]])){
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]] = $precos[$estabelecimento_descricao[intval($estabelecimento)]][0];
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]]['estoque'] = $estoque_produto_entrega[$index];
                    }else{
                        $arr['moeda'] = 'dolar';
                        $items = new ListaDePrecosRequest($arr);
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]] = $precoObj->filter($items, false, false, true, true, false, false, false);
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]] = $precos[$estabelecimento_descricao[intval($estabelecimento)]][0];
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]]['estoque'] = $estoque_produto_entrega[$index];

                        $precos[$estabelecimento_descricao[intval($estabelecimento)]]['coluna_a'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['coluna_a'];
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]]['coluna_b'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['coluna_b'];
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]]['coluna_c'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['coluna_c'];
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_vista'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_vista'];
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_15'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_15'];
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_30'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_30'];
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_45'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_45'];
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_60'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_60'];
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_75'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_75'];
                        $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_90'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_90'];
                    }                    
                }
            }
    
            $query_compras = ProdutosEstoque::select();
            $query_compras->where('codigo_produto', $fields['codigo']);
            $query_compras->where('compras', '>', 0);
            $result_compras = $query_compras->get();
    
            foreach($result_compras as $value){
                switch($value->estabelecimento){
                    case "03": 
                        $origem = "RO";
                        break;
                    case "04": 
                        $origem = "TO";
                        break;
                    default:
                        $origem = "SP";
                        break;
                }
    
                $arr['origem'] = $origem;
                $arr['produto'] = $fields['codigo'];
                $arr['moeda'] = $value->estabelecimento === "03"? 'dolar' : 'real';
    
                if(!empty($dados_carrinhos['carinho'])){
                    if(substr_count( $dados_carrinhos['coluna'], "prazo_") !== 0){
                        $arr['prazo_medio'] = intval(substr( $dados_carrinhos['coluna'], 5, 2));
                    }else{
                        $arr['prazo_medio'] = '';
                    }
                    $arr['frete'] = empty($dados_carrinhos['frete'])? 'cif' : $dados_carrinhos['frete'];
                    $arr['estado'] = empty($dados_carrinhos['estado'])? 'SP' : $dados_carrinhos['estado'];
                    $arr['tipo_cliente'] = empty($dados_carrinhos['tipo_cliente'])? 'normal' : $dados_carrinhos['tipo_cliente'];
                    $arr['coluna'] = empty($dados_carrinhos['coluna'])? 'coluna_a' :$dados_carrinhos['coluna'] ;
                }else{
                    if(!empty($campos_salvos['coluna'])){
                        if(substr_count( $campos_salvos['coluna'], "prazo_") !== 0){
                            $arr['prazo_medio'] = intval(substr( $campos_salvos['coluna'], 5, 2));
                        }else{
                            $arr['prazo_medio'] = '';
                        }
                    }                    
                    $arr['frete'] = empty($campos_salvos['frete'])? 'cif' : $campos_salvos['frete'];
                    $arr['estado'] = empty($campos_salvos['estado'])? 'SP' : $campos_salvos['estado'];
                    $arr['tipo_cliente'] = empty($campos_salvos['tipo_cliente'])? 'normal' : $campos_salvos['tipo_cliente'];
                    $arr['coluna'] = empty($campos_salvos['coluna'])? 'coluna_a' :$campos_salvos['coluna'] ;
                }
        
                $items = new ListaDePrecosRequest($arr);
        
                $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]] = $precoObj->filter($items, false, false, true, true, false, false, false)[0];
    
                $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['estoque'] = parserValor($value->compras);
    
                if($value->estabelecimento === "03"){
                    $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['coluna_a'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['coluna_a'];
                    $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['coluna_b'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['coluna_b'];
                    $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['coluna_c'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['coluna_c'];
                    $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_vista'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_vista'];
                    $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_15'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_15'];
                    $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_30'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_30'];
                    $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_45'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_45'];
                    $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_60'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_60'];
                    $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_75'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_75'];
                    $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_90'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_90'];
                }
            }     
        }
        
        $produtoEspecificacaoObj = ProdutoEspecificacao::select();
        $produtoEspecificacaoObj->with(['foto', 'produtoNasajon', 'itemBookVirtual.bookVirtual', 'produtoGrupo', 'ficha_tecnica', 'ficha_tecnica.tecidos.tecido_detalhes', 'ficha_tecnica.insumos.insumo_detalhes',
        'compras' => function($query){
            $query->orderBy('data_compra','desc');
        },'compras.notas.notasentradas']);
        $produtoEspecificacaoObj->where('codigo_produto', $fields['codigo']);
        $produtoEspecificacaoObj = $produtoEspecificacaoObj->first();
        
        $id_nota_compra = (!empty($produtoEspecificacaoObj->compras[0]->notas[0]->id_nota)) ? $produtoEspecificacaoObj->compras[0]->notas[0]->id_nota : '';
        $numero_nota_compra = (!empty($produtoEspecificacaoObj->compras[0]->notas[0]->notasentradas[0]['Número do Documento'])) ? $produtoEspecificacaoObj->compras[0]->notas[0]->notasentradas[0]['Número do Documento'] : '';
        
        if(isset($produtoEspecificacaoObj->foto) && Storage::exists('public/produto_fotos/' . $produtoEspecificacaoObj->foto->filename)){
            $imagem = Storage::url('public/produto_fotos/' . $produtoEspecificacaoObj->foto->filename);
        }else{
            $imagem = asset('images/sem-imagem-300-300.jpg');
        }

        if(!empty($produtoEspecificacaoObj->produtoGrupo->caminho) && Storage::exists($produtoEspecificacaoObj->produtoGrupo->caminho)){
            $img_instrucoes_lavagem = Storage::url($produtoEspecificacaoObj->produtoGrupo->caminho);
        }else{
            $img_instrucoes_lavagem = asset('images/sem-imagem-150-26.jpg');
        }

        $composicao = [];

        if(!empty($produtoEspecificacaoObj->ficha_tecnica)){
            if(!empty($produtoEspecificacaoObj->ficha_tecnica->tecidos)){
                foreach($produtoEspecificacaoObj->ficha_tecnica->tecidos as $tecido){
                    $composicao[] = [
                        'codigo' => $tecido->codigo_produto,
                        'descricao' => $tecido->tecido_detalhes->descricao,
                        'quantidade' => parserValor($tecido->consumo_unitario),
                    ];
                }
            }

            if(!empty($produtoEspecificacaoObj->ficha_tecnica->insumos)){
                foreach($produtoEspecificacaoObj->ficha_tecnica->insumos as $insumo){
                    $composicao[] = [
                        'codigo' => $insumo->codigo_produto,
                        'descricao' => $insumo->insumo_detalhes->descricao,
                        'quantidade' => parserValor($insumo->consumo_unitario),
                    ];
                }
            }
        }

        $composicao = [];
        
        $produto = [
            'grupo' => $produtoEspecificacaoObj->grupo,
            'produto' => $produtoEspecificacaoObj->codigo_produto.' - '.$produtoEspecificacaoObj->descricao,
            'caracteristica' => $produtoEspecificacaoObj->produtoGrupo->caracteristicas,
            'pecas_de' => $produtoEspecificacaoObj->produtoGrupo->pecas,
            'origem' => $produtoEspecificacaoObj->produtoGrupo->origem,
            'ncm' => $produtoEspecificacaoObj->produtoNasajon->ncm,
            'gramatura' => empty($produtoEspecificacaoObj->produtoGrupo->gramatura_gm2)? '' : $produtoEspecificacaoObj->produtoGrupo->gramatura_gm2,
            'gramatura_linear' => empty($produtoEspecificacaoObj->produtoGrupo->gramatura_gml)? '' : $produtoEspecificacaoObj->produtoGrupo->gramatura_gml,
            'largura' => empty($produtoEspecificacaoObj->produtoGrupo->largura)? '' : $produtoEspecificacaoObj->produtoGrupo->largura,
            'rendimento' => empty($produtoEspecificacaoObj->produtoGrupo->rendimento)? '' : $produtoEspecificacaoObj->produtoGrupo->rendimento,
            'ean' => $produtoEspecificacaoObj->produtoNasajon->codigodebarras,
            'img_instrucoes_lavagem' => $img_instrucoes_lavagem,
            'imagem' => $imagem,
            'encolhimento' => empty($produtoEspecificacaoObj->produtoGrupo->encolhimento)? '' : $produtoEspecificacaoObj->produtoGrupo->encolhimento,
            'composicao' => $composicao,
            'composicao_nasajon' => $produtoEspecificacaoObj->produtoNasajon->composicao,
            'unidade' => $produtoEspecificacaoObj->produtoNasajon->unidade,
            'peso_bruto' => parserValor($produtoEspecificacaoObj->produtoNasajon->pesobruto),
            'peso_liquido' => parserValor($produtoEspecificacaoObj->produtoNasajon->pesoliquido),
            'titulo_trama' => empty($produtoEspecificacaoObj->produtoGrupo->titulo_trama)? '' : $produtoEspecificacaoObj->produtoGrupo->titulo_trama,
            'titulo_urdume' => empty($produtoEspecificacaoObj->produtoGrupo->titulo_urdume)? '' : $produtoEspecificacaoObj->produtoGrupo->titulo_urdume,
        ];

        $campos_salvos['frete'] = empty($campos_salvos['frete'])? 'cif' : $campos_salvos['frete'];
        $campos_salvos['estado'] = empty($campos_salvos['estado'])? 'SP' : $campos_salvos['estado'];
        $campos_salvos['tipo_cliente'] = empty($campos_salvos['tipo_cliente'])? 'normal' : $campos_salvos['tipo_cliente'];
        $campos_salvos['coluna'] = empty($campos_salvos['coluna'])? 'coluna_a' :$campos_salvos['coluna'] ;

        $estabelecimento_descricao = encrypt($estabelecimento_descricao);

        $botao_pdf = "<a target='_blank' href='" . route('ficha_tecnica_comercial.gerar_pdf_produto', ['codigo' => $produtoEspecificacaoObj->codigo_produto]) . "'><button class='btn btn-primary' style='float: right !important; margin-right: 1px;'>Gerar PDF</button></a>";

        return view('programs.ficha_tecnica_comercial.modal.detalhes')->with(
            [
                'produto' => $produto, 
                'origem' => empty($origem)? '' : $origem, 
                'campos_salvos' => $campos_salvos, 
                'precos' => $precos, 
                'estabelecimentos' => $fields['estabelecimentos'], 
                'estoque_produto_entrega' => $fields['estoque_produto_entrega'], 
                'codigo' => $fields['codigo'], 
                'precos_programado' => $precos_programado, 
                'estabelecimento_descricao' => $estabelecimento_descricao,
                'estilo_layout' => isMobile(),
                'carrinho' => str_replace("color: black;", "color: #007fff;", $fields['carrinho']), 
                'logo_tipo' => asset('images/logomn.jpg'),
                'dados_carrinhos' => $dados_carrinhos,
                'botao_pdf' => $botao_pdf,
                'id_nota_compra' => $id_nota_compra,
                'numero_nota' => $numero_nota_compra
            ]
        );
    }

    public function buscarFiltroDetalhes(Request $request){
        $fields = $request->only(
            'codigo', 
            'coluna', 
            'estado', 
            'frete', 
            'tipo_cliente', 
            'estabelecimentos', 
            'estoque_produto_entrega', 
            'codigo', 
            'estabelecimento_descricao'
        );
        
        $precoObj = new ListagemDePrecosController;

        $estabelecimentos = explode("-", $fields['estabelecimentos']);

        $estoque_produto_entrega = explode(";", $fields['estoque_produto_entrega']);
        

        $estabelecimento_descricao = decrypt($fields['estabelecimento_descricao']);

        $precos = [];

        foreach($estabelecimentos as $index => $estabelecimento){
            $estoque_produto_entrega[$index] = str_replace($estabelecimento_descricao[intval($estabelecimento)], "", $estoque_produto_entrega[$index]);
            $estoque_produto_entrega[$index] = str_replace("-", "", $estoque_produto_entrega[$index]);
            $estoque_produto_entrega[$index] = trim($estoque_produto_entrega[$index]);
            if(!empty($estabelecimento)){
                switch($estabelecimento){
                    case "03": 
                        $origem = "RO";
                        break;
                    case "04": 
                        $origem = "TO";
                        break;
                    default:
                        $origem = "SP";
                        break;
                }

                $arr['origem'] = $origem;
                $arr['produto'] = $fields['codigo'];
                $arr['moeda'] = 'real';
                if(substr_count( $fields['coluna'], "prazo_") !== 0){
                    $arr['prazo_medio'] = intval(substr( $fields['coluna'], 5, 2));
                }else{
                    $arr['prazo_medio'] = '';
                }
                $arr['frete'] = empty($fields['frete'])? 'cif' : $fields['frete'];
                $arr['estado'] = empty($fields['estado'])? 'SP' : $fields['estado'];
                $arr['tipo_cliente'] = empty($fields['tipo_cliente'])? 'normal' : $fields['tipo_cliente'];
                $arr['coluna'] = empty($fields['coluna'])? 'coluna_a' :$fields['coluna'] ;

                $items = new ListaDePrecosRequest($arr);

                $precos[$estabelecimento_descricao[intval($estabelecimento)]] = $precoObj->filter($items, false, false, true, true, false, false, false);
                if(!empty($precos[$estabelecimento_descricao[intval($estabelecimento)]])){
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]] = $precos[$estabelecimento_descricao[intval($estabelecimento)]][0];
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]]['estoque'] = $estoque_produto_entrega[$index];
                }else{
                    $arr['moeda'] = 'dolar';
                    $items = new ListaDePrecosRequest($arr);
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]] = $precoObj->filter($items, false, false, true, true, false, false, false);
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]] = $precos[$estabelecimento_descricao[intval($estabelecimento)]][0];
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]]['estoque'] = $estoque_produto_entrega[$index];

                    $precos[$estabelecimento_descricao[intval($estabelecimento)]]['coluna_a'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['coluna_a'];
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]]['coluna_b'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['coluna_b'];
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]]['coluna_c'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['coluna_c'];
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_vista'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_vista'];
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_15'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_15'];
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_30'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_30'];
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_45'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_45'];
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_60'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_60'];
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_75'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_75'];
                    $precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_90'] = 'U$ '.$precos[$estabelecimento_descricao[intval($estabelecimento)]]['prazo_90'];
                }  
            }
        }

        $precos_programado = [];
        
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'precos' => $precos,
                'precos_programado' => $precos_programado,
            ]
        ];

        return response()->json($response);
    }
    
    public function buscarFiltroDetalhesProgramado(Request $request){
        $fields = $request->only('codigo', 'coluna', 'estado', 'frete', 'tipo_cliente', 'estabelecimentos', 'estoque_produto_entrega', 'codigo');

        $precoObj = new ListagemDePrecosController;

        $estabelecimentos = explode("-", $fields['estabelecimentos']);

        $estoque_produto_entrega = explode(";", $fields['estoque_produto_entrega']);
        

        $estabelecimento_descricao = returnEmpresasNasajonView();

        $precos = [];

        $query_compras = ProdutosEstoque::select();
        $query_compras->where('codigo_produto', $fields['codigo']);
        $query_compras->where('compras', '>', 0);
        $result_compras = $query_compras->get();

        $precos_programado = [];

        foreach($result_compras as $value){
            switch($value->estabelecimento){
                case "03": 
                    $origem = "RO";
                    break;
                case "04": 
                    $origem = "TO";
                    break;
                default:
                    $origem = "SP";
                    break;
            }

            $arr['origem'] = $origem;
            $arr['produto'] = $fields['codigo'];
            $arr['moeda'] = $value->estabelecimento === "03"? 'dolar' : 'real';
            if(substr_count( $fields['coluna'], "prazo_") !== 0){
                $arr['prazo_medio'] = intval(substr( $fields['coluna'], 5, 2));
            }else{
                $arr['prazo_medio'] = '';
            }
            $arr['frete'] = empty($fields['frete'])? 'cif' : $fields['frete'];
            $arr['estado'] = empty($fields['estado'])? 'SP' : $fields['estado'];
            $arr['tipo_cliente'] = empty($fields['tipo_cliente'])? 'normal' : $fields['tipo_cliente'];
            $arr['coluna'] = empty($fields['coluna'])? 'coluna_a' :$fields['coluna'] ;
    
            $items = new ListaDePrecosRequest($arr);
    
            $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]] = $precoObj->filter($items, false, false, true, true, false, false, false)[0];

            $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['estoque'] = parserValor($value->compras);

            if($value->estabelecimento === "03"){
                $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['coluna_a'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['coluna_a'];
                $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['coluna_b'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['coluna_b'];
                $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['coluna_c'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['coluna_c'];
                $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_vista'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_vista'];
                $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_15'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_15'];
                $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_30'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_30'];
                $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_45'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_45'];
                $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_60'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_60'];
                $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_75'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_75'];
                $precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_90'] = 'U$ '.$precos_programado[$estabelecimento_descricao[intval($value->estabelecimento)]]['prazo_90'];
            }
        }
        
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'precos' => $precos,
                'precos_programado' => $precos_programado,
            ]
        ];
        
        return response()->json($response);
    }

    public function salvarFiltroDetalhes(Request $request){
        $fields = $request->only('codigo', 'coluna', 'estado', 'frete', 'tipo_cliente', 'estabelecimentos', 'estoque_produto_entrega', 'codigo');

        $UserCamposSalvoControllerObj = new UserCamposSalvoController('App\ListagemDePrecos');
        $UserCamposSalvoControllerObj->salvarCampo('coluna', $fields['coluna']);
        $UserCamposSalvoControllerObj->salvarCampo('estado', $fields['estado']);
        $UserCamposSalvoControllerObj->salvarCampo('frete', $fields['frete']);
        $UserCamposSalvoControllerObj->salvarCampo('tipo_cliente', $fields['tipo_cliente']);
        
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => ''
        ];
        
        return response()->json($response);
    }
	
    public function modalGrupo(Request $request){
        $fields = $request->only('id');
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
    
        $produtoGrupoObj = ProdutoGrupo::find($id);
    
        if(!empty($produtoGrupoObj->caminho) && Storage::exists($produtoGrupoObj->caminho)){
            $img_instrucoes_lavagem = Storage::url($produtoGrupoObj->caminho);
        }
        else{
            $img_instrucoes_lavagem = asset('images/sem-imagem.jpg');
        }
    
        $produto = [
            'grupo' => $produtoGrupoObj->descricao,
            'caracteristica' => $produtoGrupoObj->caracteristicas,
            'tamanho_pecas' => $produtoGrupoObj->pecas,
            'origem' => $produtoGrupoObj->origem,
            'gramatura' => parserQtd($produtoGrupoObj->gramatura_gml),
            'rendimento' => $produtoGrupoObj->rendimento,
            'encolhimento' => $produtoGrupoObj->encolhimento,
            'informacao_adicional' => $produtoGrupoObj->informacao_adicional,
            'img_instrucoes_lavagem' => $img_instrucoes_lavagem,
            'titulo_trama' => $produtoGrupoObj->titulo_trama,
            'titulo_urdume' => $produtoGrupoObj->titulo_urdume,
            'ligamento' => $produtoGrupoObj->ligamento,
            'construcao' => $produtoGrupoObj->construcao,
            'logo_ficha' =>  asset('images/icons/logotipo_azul_64.png'),
         
        ];
        return view('programs.ficha_tecnica_comercial.modal.grupo')->with(['produto' => $produto]);
    }

    public function verificarCarrinho($retorno_array = false){
        $carrinhoCompraObj = CarrinhoCompra::select();
        $carrinhoCompraObj->with(['dadosClientePedido.cliente', 'carrinhoCompraItens', 'pedido']);
        $carrinhoCompraObj->where('finalizado', false);
        $carrinhoCompraObj->where('created_by', Auth::id());
        $carrinhoCompraObj = $carrinhoCompraObj->first();

        $cliente = "";
        $quantidade = 0;
        $valor = 0;
        $tipo_cliente = '';
        $estado = '';
        $coluna = '';
        $frete = '';

        if(!empty($carrinhoCompraObj)){
            if(!empty($carrinhoCompraObj->dadosClientePedido->cliente)){
                $cliente = $carrinhoCompraObj->dadosClientePedido->cliente->nome.' - '.$carrinhoCompraObj->dadosClientePedido->cliente->cpf_cnpj;

                if($carrinhoCompraObj->dadosClientePedido->cliente->inscricaoestadual === 'ISENTO' || intval($carrinhoCompraObj->dadosClientePedido->cliente->indicadorinscricaoestadual) === 2 || intval($carrinhoCompraObj->dadosClientePedido->cliente->indicadorinscricaoestadual) === 9){  
                    $tipo_cliente = 'isento';
                }else{
                    $tipo_cliente = 'normal';
                }
                
                if(!empty($carrinhoCompraObj->dadosClientePedido->cliente->uf)){
                    $estado = $carrinhoCompraObj->dadosClientePedido->cliente->uf;
                }
            }
            
            if(!empty($carrinhoCompraObj->carrinhoCompraItens)){
                $carrinhoCompraObj->carrinhoCompraItens->each(function($item) use(&$valor, &$quantidade){
                    $valor += $item->produto_preco * $item->produto_quantidade;
                    $quantidade++;
                });

                $valor = parserValor($valor);
            }else{
                $valor = '';
            }

            if(!empty($carrinhoCompraObj->pedido)){
                if(!empty($carrinhoCompraObj->pedido->condicao_pagamento_detalhes)){
                    $media = $carrinhoCompraObj->pedido->condicao_pagamento_detalhes->media;

                    if($media == 0){
                        $coluna = 'prazo_vista';
                    }else if($media <= 15){
                        $coluna = 'prazo_15';
                    }else if($media <= 30){
                        $coluna = 'prazo_30';
                    }else if($media <= 45){
                        $coluna = 'prazo_45';
                    }else if($media <= 60){
                        $coluna = 'prazo_60';
                    }else if($media <= 75){
                        $coluna = 'prazo_75';
                    }else if($media <= 90){
                        $coluna = 'prazo_90';
                    }else{
                        $coluna = 'prazo_90';
                    }
                }else{
                    $coluna = '';
                }
                $frete = $this->fretePreco($carrinhoCompraObj->pedido);
                $frete = strtolower($frete['frete']);
            }else{
                $coluna = '';
            }
        }
        
        $visualizar_carrinho = "<a href='#' data-cliente='' data-book_id='' data-filtro='' class='bt-carrinho-book'></a>";
        $contador = $quantidade;

        $retorno = [
            'carrinho' => empty($carrinhoCompraObj)? false : true,
            'cliente' => $cliente,
            'visualizar_carrinho' => $visualizar_carrinho,
            'contador' => $contador,
            'valor' => $valor,
            'tipo_cliente' => $tipo_cliente,
            'estado' => $estado,
            'coluna' => $coluna,
            'frete' => $frete,
        ];

        if($retorno_array){
            return $retorno;
        }else{
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => $retorno
            ];
    
            return response()->json($response);
        }
    }

    public function fretePreco(PedidoPortal $PedidoPortal){
        $return = [
            'preco' => '',
            'frete' => ''
        ];

        if (!empty($PedidoPortal->transportadora_redespacho)){

            $return['preco'] = 'FOB';

            if ($PedidoPortal->tipo_frete =='P'){
                $return['frete'] = 'CIF';
            }
            else{
                $return['frete'] = 'FOB';
            }
        }
        else if(!empty($PedidoPortal->tipo_frete) && empty($PedidoPortal->transportadora_redespacho)){
            if(empty($PedidoPortal->cod_cliente_conta_e_ordem)){
                $estabelecimentoCidadeFobObj = EstabelecimentoCidadeFob::where('uf', $PedidoPortal->cliente->uf)
                ->where('cidade', $PedidoPortal->cliente->cidade)
                ->first();
            }else{
                $estabelecimentoCidadeFobObj = EstabelecimentoCidadeFob::where('uf', $PedidoPortal->cliente_conta_e_ordem->uf)
                ->where('cidade', $PedidoPortal->cliente_conta_e_ordem->cidade)
                ->first();
            }
    
            if ($PedidoPortal->tipo_frete =='P' && is_null($estabelecimentoCidadeFobObj))  {
                $return['frete'] = 'CIF';
                $return['preco'] = 'CIF';
            }
            else if ($PedidoPortal->tipo_frete == 'P' && !is_null($estabelecimentoCidadeFobObj))  {
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

    public function fichaProdutoPdf(Request $request){
        ini_set('memory_limit','2048M');

        $fields = $request->only('codigo');

        $produtoEspecificacaoObj = ProdutoEspecificacao::select();
        $produtoEspecificacaoObj->with(['produtoGrupo','foto', 'produtoNasajon', 'itemBookVirtual.bookVirtual', 'produtoGrupo', 'ficha_tecnica', 'ficha_tecnica.tecidos.tecido_detalhes', 'ficha_tecnica.insumos.insumo_detalhes']);
        $produtoEspecificacaoObj->where('codigo_produto', $fields['codigo']);
        $produtoEspecificacaoObj = $produtoEspecificacaoObj->first();

        if(isset($produtoEspecificacaoObj->foto) && Storage::exists('public/produto_fotos/' . $produtoEspecificacaoObj->foto->filename)){
            $imagem = Storage::url('public/produto_fotos/' . $produtoEspecificacaoObj->foto->filename);
        }
        else{
            $imagem = asset('images/sem-imagem.jpg');
        }

        if(!empty($produtoEspecificacaoObj->itemBookVirtual->bookVirtual->thumb_instrucoes_lavagem) && Storage::exists($produtoEspecificacaoObj->itemBookVirtual->bookVirtual->thumb_instrucoes_lavagem)){
            $img_instrucoes_lavagem = Storage::url($produtoEspecificacaoObj->itemBookVirtual->bookVirtual->thumb_instrucoes_lavagem);
        }else{
            $img_instrucoes_lavagem = asset('images/sem-imagem-150-26.jpg');
        }

        $composicao = [];

        if(!empty($produtoEspecificacaoObj->ficha_tecnica)){
            if(!empty($produtoEspecificacaoObj->ficha_tecnica->tecidos)){
                foreach($produtoEspecificacaoObj->ficha_tecnica->tecidos as $tecido){
                    $composicao[] = [
                        'codigo' => $tecido->codigo_produto,
                        'descricao' => $tecido->tecido_detalhes->descricao,
                        'quantidade' => parserValor($tecido->consumo_unitario),
                    ];
                }
            }

            if(!empty($produtoEspecificacaoObj->ficha_tecnica->insumos)){
                foreach($produtoEspecificacaoObj->ficha_tecnica->insumos as $insumo){
                    $composicao[] = [
                        'codigo' => $insumo->codigo_produto,
                        'descricao' => $insumo->insumo_detalhes->descricao,
                        'quantidade' => parserValor($insumo->consumo_unitario),
                    ];
                }
            }
        }

        $composicao = [];

        $produto = [
            'grupo' => $produtoEspecificacaoObj->grupo,
            'produto' => $produtoEspecificacaoObj->codigo_produto.' - '.$produtoEspecificacaoObj->descricao,
            'caracteristica' => $produtoEspecificacaoObj->produtoGrupo->caracteristicas,
            'pecas_de' => $produtoEspecificacaoObj->produtoGrupo->pecas,
            'origem' => $produtoEspecificacaoObj->produtoGrupo->origem,
            'ncm' => $produtoEspecificacaoObj->produtoNasajon->ncm,
            'gramatura' => empty($produtoEspecificacaoObj->produtoGrupo->gramatura_gm2)? '' : $produtoEspecificacaoObj->produtoGrupo->gramatura_gm2,
            'gramatura_linear' => empty($produtoEspecificacaoObj->produtoGrupo->gramatura_gml)? '' : $produtoEspecificacaoObj->produtoGrupo->gramatura_gml,
            'largura' => empty($produtoEspecificacaoObj->produtoGrupo->largura)? '' : $produtoEspecificacaoObj->produtoGrupo->largura,
            'rendimento' => empty($produtoEspecificacaoObj->produtoGrupo->rendimento)? '' : $produtoEspecificacaoObj->produtoGrupo->rendimento,
            'ean' => $produtoEspecificacaoObj->produtoNasajon->codigodebarras,
            'img_instrucoes_lavagem' => $img_instrucoes_lavagem,
            'imagem' => $imagem,
            'encolhimento' => empty($produtoEspecificacaoObj->produtoGrupo->encolhimento)? '' : $produtoEspecificacaoObj->produtoGrupo->encolhimento,
            'composicao' => $composicao,
            'composicao_nasajon' => $produtoEspecificacaoObj->produtoNasajon->composicao,
            'unidade' => $produtoEspecificacaoObj->produtoNasajon->unidade,
            'peso_bruto' => parserValor($produtoEspecificacaoObj->produtoNasajon->pesobruto),
            'peso_liquido' => parserValor($produtoEspecificacaoObj->produtoNasajon->pesoliquido),
            'titulo_trama' => empty($produtoEspecificacaoObj->produtoGrupo->titulo_trama)? '' : $produtoEspecificacaoObj->produtoGrupo->titulo_trama,
            'titulo_urdume' => empty($produtoEspecificacaoObj->produtoGrupo->titulo_urdume)? '' : $produtoEspecificacaoObj->produtoGrupo->titulo_urdume,
        ];

        $dados['produto'] = $produto;
        $dados['logo_tipo'] = asset('images/logomn.jpg');
        $title = "Ficha Técninca Comercial";

        $pdfFilePath = 'ficha_tecnica_comercial_'.$produtoEspecificacaoObj->codigo_produto.'.pdf';
		$pdf = PDF::loadView(
			'pdf.ficha_comercial_produto', 
			$dados,
			[], 
			['title' => $title, 'margin_bottom' => 1, 'orientation' => 'P', 'format' => 'A4']
		);

        $pdf->save($pdfFilePath);

        return view('pdf.leitura')->with(['caminho' => '/'.$pdfFilePath]);
    }
}
