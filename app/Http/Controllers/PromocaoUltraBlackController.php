<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\ProdutoEspecificacao;
use App\PedidoPortal;
use App\ProdutoNasajon;
use App\CepEstado;
use App\AliquotaPreco;
use App\ParametrosAprovacao;
use App\MargemPrazo;
use App\ProdutoPromocional;
use App\ParametrosPedido;
use App\PedidoItemPortal;

use App\Http\Requests\PedidoItemPortalRequest;
use App\Http\Requests\ListaDePrecosRequest;

class PromocaoUltraBlackController extends Controller
{
    //VOIL MAIORCA SLUB PRINTED E006-003
    private $codigo_cliente_balcao = ['0000010069999'];

    private $codigo_grupo_textil_mn = ['0050758840003', '05075884000167', '05075884000167', '05075884000248', '06', '06', '06311274000188', '06311274000188', '06311274000269', '06311274000340', '06311274000420', '06311274000501', '07', '08'];

    private $estabelecimentos_prologos = [];

    private $grupos_nao_comissao = [
        'tecnosport',
        'oxford gold',
        'ligatex',
        'bistrech',
        'bulgatti liso',
        'padova',
        'pucci plus'
    ];

    private $grupos_especiais = [];

    private $desconto_especiais = 4;

	private $porcentagem_metragem_exata = 10;

    private $codigo_cliente_red_d_or_sao_luiz = [
        '06047087007656',
        '06047087001291',
        '00060470870050',
        '00060470870010',
        '06047087000210',
        '06047087000805',
        '06047087009780',
        '0060470870042',
        '06047087000139',
        '0060470870003',
        '00060470870032',
        '06047087004126',
        '06047087000724',
        '00060470870009',
        '0060470870077',
        '06047087007907',
        '06047087007222',
        '00060470870033',
        '06047087003820'
    ];

    private $unidade_metros = ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME', 'mt', 'm', 'metro', 'metros', 'mts', 'me'];

    private $unidade_kg = ['kg', 'Kg', 'KG'];

    public function modalAdicionar(Request $request){
        $fields = $request->only(['produto_codigo', 'pedido', 'estabelecimento', 'cliente', 'tipo', 'editar']);

        $produto_verificacao_grupo = ProdutoEspecificacao::where('codigo_produto', $fields['produto_codigo'])
            ->first();

        $retorno_preco = [];

        if($fields['editar'] == "true"){
            $produtoEspecificacaoObj = ProdutoEspecificacao::with(['produtoGrupo'])
            ->where('produto_grupos_id', $produto_verificacao_grupo->produto_grupos_id)
            ->where('linha', 'ULTRA BLACK')
            ->get();
        
            foreach($produtoEspecificacaoObj as $produto){
                $pedidoItemPortalObj = PedidoItemPortal::select()
                    ->where('pedido', $fields['pedido'])
                    ->where('cod_produto', $produto->codigo_produto)
                    ->where('token_promocional', 'ultra_black')
                    ->first();

                if(!empty($pedidoItemPortalObj)){
                    $verificao_preco = [
                        'codigo' => $produto->codigo_produto,
                        'descricao' => $produto->descricao,
                        'grupo' => $produto->produtoGrupo->descricao,
                        'estoque_disponivel' => parserValor($pedidoItemPortalObj->quantidade),
                        'estoque_segundario' => parserValor($pedidoItemPortalObj->promocional_quantidade),
                        'preco_primario' => parserValor($pedidoItemPortalObj->preco_unitario),
                        'preco_segundario' => parserValor($pedidoItemPortalObj->promocional_preco_unitario),
                        'verificador_unidade' => $pedidoItemPortalObj->promocional_unidade,
                        'gml' => $pedidoItemPortalObj->gml,
                        'unidade' => $pedidoItemPortalObj->unidade,
                        'descricao_pecas' => empty($produto->produtoGrupo->pecas)? $produto->descricao : $produto->descricao." - Pecas: ".$produto->produtoGrupo->pecas,
                    ]; 
                    $retorno_preco[] = $verificao_preco; 
                }                         
            }
        }else{
            $produtoEspecificacaoObj = ProdutoEspecificacao::with(['produtoGrupo'])
            ->where('produto_grupos_id', $produto_verificacao_grupo->produto_grupos_id)
            ->where('linha', 'ULTRA BLACK')
            ->get();
        
        
            foreach($produtoEspecificacaoObj as $produto){
                $produtoControllerObj = new ProdutoController;

                $arr = [];
                $arr['codprd'] = $produto->codigo_produto;
                $arr['pedido'] = $fields['pedido'];
                $arr['preco_base_antes'] = '';
                $arr['preco_antes'] = '';
                $arr['comissao_antes'] = '';

                $arr_request = new Request($arr);

                $verificao_preco = $produtoControllerObj->retornaInformacoesPreco($arr_request, true);
        
                if(is_array($verificao_preco)){
                    if(in_array($verificao_preco['unidade'], $this->unidade_metros)){
                        if(floatval($produto->produtoGrupo->gramatura_gml) <= 0){
                            return response()
                                ->json(
                                    [
                                        'status' => 'error', 
                                        'message' => 'Grupo sem gramatura para conversão. Favor, verificar com setor responsável.',
                                        'error' => [],
                                        'response' => []
                                    ],
                                422);
                        }
                        $verificao_preco['verificador_unidade'] = 'metros';
                        $estoque_kg = parserNumber($verificao_preco['estoque_disponivel']) * floatval($produto->produtoGrupo->gramatura_gml) / 1000;
                        $verificao_preco['estoque_segundario'] = parserValor($estoque_kg);
                        $preco_segundario = parserNumber($verificao_preco['preco_unitario']) * parserNumber($verificao_preco['estoque_disponivel']) / parserNumber($verificao_preco['estoque_segundario']);
                        $verificao_preco['preco_segundario'] = parserValor($preco_segundario * 0.75);
                        $verificao_preco['preco_primario'] = parserValor(parserNumber($verificao_preco['preco_unitario']) * 0.75);
                        $verificao_preco['gml'] = $produto->produtoGrupo->gramatura_gml;
                    }else if(in_array($verificao_preco['unidade'], $this->unidade_kg)){
                        $verificao_preco['verificador_unidade'] = 'quilo';
                        $estoque_mt = parserNumber($verificao_preco['estoque_disponivel']) / floatval($produto->produtoGrupo->gramatura_gml) * 1000;
                        $verificao_preco['estoque_segundario'] = parserValor($estoque_mt);
                        $preco_segundario = parserNumber($verificao_preco['preco_unitario']) * parserNumber($verificao_preco['estoque_disponivel']) / parserNumber($verificao_preco['estoque_segundario']);
                        $verificao_preco['preco_segundario'] = parserValor($preco_segundario * 0.75);
                        $verificao_preco['preco_primario'] = parserValor(parserNumber($verificao_preco['preco_unitario']) * 0.75);
                        $verificao_preco['gml'] = $produto->produtoGrupo->gramatura_gml;
                    }else{
                        return response()
                                ->json(
                                    [
                                        'status' => 'error', 
                                        'message' => 'Produto com unidade incorreta para conversão. Favor, verificar com setor responsável.',
                                        'error' => [],
                                        'response' => []
                                    ],
                                422);
                    }
                    
                    $retorno_preco[] = $verificao_preco;
                }        
            }
        }
        

        return view('programs.pedido_portal.modal.promocao_ultra_black')
            ->with(
                [
                    "produtos" => $retorno_preco, 
                    'estabelecimento' => $fields['estabelecimento'],
                    'pedido' => $fields['pedido'],
                    'cliente' => $fields['cliente'],
                    'tipo' => $fields['tipo'],
                    'editar' => $fields['editar'],
                ]
            );
    }

    public function adicionar(Request $request){
        $fields = $request->only(['pedido', 'estabelecimento', 'cliente', 'array_produtos', 'tipo', 'editar_ultra_black']);

        $produto_verificacao_grupo = ProdutoEspecificacao::where('codigo_produto', $fields['array_produtos'][0][0])
        ->first();

        $produtoEspecificacaoObj = ProdutoEspecificacao::with(['produtoGrupo'])
        ->where('produto_grupos_id', $produto_verificacao_grupo->produto_grupos_id)
        ->where('linha', 'ULTRA BLACK')
        ->get();
    
        foreach($produtoEspecificacaoObj as $produto){
            $pedidoItemPortalObj = PedidoItemPortal::select()
                ->where('pedido', $fields['pedido'])
                ->where('cod_produto', $produto->codigo_produto)
                ->first();
            if(!empty($pedidoItemPortalObj)){
                $arr = [];
                $arr['id'] = $pedidoItemPortalObj->id;

                $arr_request = new Request($arr);

                $pedidoItemPortalControllerObj = new PedidoItemPortalController;
                $pedidoItemPortalControllerObj->excluiProduto($arr_request);
            }            
        }

        $retorno_produtos = [];

        foreach($fields['array_produtos'] as $produto){
            $arr = [];
            $arr['estabelecimento'] = $fields['estabelecimento'];
            $arr['produto_codigo'] = $produto[0];
            $arr['pedido'] = $fields['pedido'];
            $arr['cliente'] = $fields['cliente'];
            $arr['preco_unitario'] = $produto[1];
            $arr['quantidade'] = $produto[2];
            $arr['produto_codigo_base'] = '';
            $arr['produto_codigo_desenho'] = '';
            $arr['descricao'] = $produto[3];
            $arr['preco_segundario'] = $produto[4];
            $arr['estoque_segundario'] = $produto[5];
            $arr['gml'] = $produto[6];
            $arr[ 'unidade'] = $produto[7];
            $arr['promocional_unidade'] = $produto[8];

            $arr_request = new PedidoItemPortalRequest($arr);

            $verificao_conteudo = $this->adicionarProduto($arr_request, true);

            if(!is_array($retorno_produtos)){
                $produtoEspecificacaoObj = ProdutoEspecificacao::where('codigo_produto', $produto[0])
                ->first();

                $arr = [];
                $arr['grupo_id'] = $produtoEspecificacaoObj->produto_grupos_id;
                $arr['pedido'] = $fields['pedido'];

                $arr_request = new Request($arr);

                $this->excluir($arr_request);

                return $verificao_conteudo;
            }

            $retorno_produtos[] = $verificao_conteudo;
        }

        $itens = [];
        
        $pedidoPortalObj = PedidoPortal::with('itens_pedido', 'itens_pedido.especificacoes.produtoGrupo')->find($fields['pedido']);
        $pedidoPortalObj->itens_pedido->each(function($item) use(&$itens) {
            $preco_original = $item->preco_unitario;
            $itens[] = [
                'id' => $item->id,
                'codigo' => $item->especificacoes->codigo_produto,
                'descricao' => empty($item->especificacoes->produtoGrupo->pecas)? $item->especificacoes->descricao : $item->especificacoes->descricao." - Pecas: ".$item->especificacoes->produtoGrupo->pecas,
                'preco_unitario' => "<div><div class='preco' data-preco_original='".parserValor($preco_original)."'  data-toggle='popover' data-placement='right' data-html='true' title='' data-content=''>" . parserValor($item->preco_unitario) . '</div></div>',
                'quantidade' => "<div><div class='estoque' data-toggle='popover' data-placement='left' data-html='true' title='' data-content=''>" . parserValor($item->quantidade) . '</div></div>',
                'valor_total' => parserValor($item->valor_total),
                'coluna' => $item->coluna,
                'comissao' => $item->comissao . "%",
                'token_promocional' => $item->token_promocional,
                'grupo_descricao' => $item->especificacoes->produtoGrupo->descricao,
                'grupo_id' => $item->especificacoes->produtoGrupo->id,
            ];

        });

        return response()->json(['status' => 'success', 'message' => '', 'response' => ['itens' => $itens??[]]], 200);
    }

    public function adicionarProduto(PedidoItemPortalRequest $request){
        $fields = $request->only(
            [
                'estabelecimento', 
                'produto_codigo', 
                'pedido', 
                'cliente', 
                'preco_unitario', 
                'quantidade', 
                'produto_codigo_base', 
                'produto_codigo_desenho', 
                'produto_descricao',
                'unidade',
                'gml',
                'estoque_segundario',
                'preco_segundario',
                'promocional_unidade',
            ]
        );

        $pedidoObj = PedidoPortal::with(['usuario_detalhes', 'condicao_pagamento_detalhes', 'itens_pedido', 'itens_pedido.especificacoes'])->find($fields['pedido']);

        return $this->adicionarProdutoNasajon($pedidoObj, $fields);
    }

    public function adicionarProdutoNasajon(PedidoPortal $pedido, $campos){
        $produtoObj = ProdutoNasajon::where('codigo', strtoupper($campos['produto_codigo']))->first();
        if(
            is_null($produtoObj) &&
            isset($campos['produto_codigo_base']) &&
            !empty($campos['produto_codigo_base']) &&
            isset($campos['produto_codigo_desenho']) &&
            !empty($campos['produto_codigo_desenho'])
        ){
            $return = $this->criarProdutoProducao($campos);
            $produtoObj = ProdutoNasajon::where('codigo', strtoupper($campos['produto_codigo']))->first();
        }else{
            if(in_array($pedido->tipo_venda, ['producao', 'producao_triangular', 'pre_pago_producao', 'pre_pago_producao_triangular'])){
                $return = $this->atualizaProdutos($campos);
            }
        }
        $tem_ipi = false;
        if(!empty($produtoObj->ipi)){
            $tem_ipi = true;
        }
        $produtosEspecificacoes = ProdutoEspecificacao::find(strtoupper($campos['produto_codigo']));
        $estadoObj = CepEstado::find($pedido->cliente->uf);

        switch ($campos['estabelecimento']) {
            case '3':
                $origem = "RO";
                break;
            case '4':
                $origem = "TO";
                break;
            default:
                $origem = "SP";
                break;
        }
		if($pedido->rj_x_sp == true){
			$origem = 'SP';
		}
        $internacional = false;
        
        $aliquotaObj = AliquotaPreco::where("origem", $origem)
            ->where('estado', $pedido->cliente->uf)
            ->where('internacional', $internacional)->first();

        $razao_cnpj_textil = '06311274';

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $pedido->cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        $preco_custo = false;
        if(
            !in_array(str_pad($pedido->estabelecimento, 2, '0', STR_PAD_LEFT), ['01', '02']) &&
            $razao_cnpj_textil === $raiz_cnpj
        ){
            $preco_custo = true;
        }

        $produto_sem_estoque = false;
        if(in_array($pedido->tipo_venda, ['producao', 'producao_triangular', 'pre_pago_producao', 'pre_pago_producao_triangular'])){
            $validacaoProdutosProducao = $this->validarProdutoNasajonProducao($pedido, strtoupper($campos['produto_codigo']), strtoupper($campos['produto_codigo_base']), strtoupper($campos['produto_codigo_desenho']), $campos['preco_unitario'], $campos['quantidade'], $estadoObj->regiao, $preco_custo);
            if ($validacaoProdutosProducao['status'] === 'error'){
                return response()->json($validacaoProdutosProducao, 422);
            }else{
                $produto_sem_estoque = $validacaoProdutosProducao['response']['produto_sem_estoque'];
            }
        }else{
            $erros = $this->validarProdutoNasajon($origem, strtoupper($campos['produto_codigo']), $pedido->cliente->uf, null, $estadoObj->regiao, $pedido, $campos['preco_unitario'], $campos['quantidade'], '', $preco_custo);
            if (!empty($erros)){
                return response()->json($erros, 422);
            }
        }

        $preco_promocao = false;
        if($preco_custo === true){
            $valor_frete = 0;
            $base_calculo_icms = 0;
            $valor_icms = 0;
            $coluna_preco = 0;
            
            $coluna_c = 0;
            $coluna_b = 0;
            $coluna_a = 0;

            $comissao_porcentagem = 0;
            $coluna_preco = 0;

        } else {
            $frete = $pedido->frete_preco;

            $items = new ListaDePrecosRequest([
                'origem' => $origem,
                'produto' => strtoupper($campos['produto_codigo']),
                'moeda' => ($pedido->pedido_futuro === true && $origem == 'RO') ? 'dolar' : 'real',
                'prazo_medio' => $pedido->condicao_pagamento_detalhes['media'] ?? 0,
                'frete' => $frete,
                'estado' => $estadoObj->uf, 
                'cidade' => $pedido->cliente->cidade,
                'tipo_cliente' => (
                    $pedido->cliente->inscricaoestadual == 'ISENTO' ||
                    intval($pedido->cliente->indicadorinscricaoestadual) == 2 ||
                    intval($pedido->cliente->indicadorinscricaoestadual) == 9
                ) ? 'isento' : 'juridico',
                'promocao' => false,
                'cliente' => $pedido->cod_cliente,
                'codigo_vendedor' => $pedido->usuario_detalhes->codigo_representante,
            ]);

            $precoObj = new ListagemDePrecosController;
            $precos = $precoObj->filter($items, true, false, true, false, false, false, false, false, false);

            $parametrosAprovacaoObj = ParametrosAprovacao::with('tipoUsuario')->where('estabelecimento', str_pad($pedido->estabelecimento, 2, '0', STR_PAD_LEFT))->get();
            $margemPrazoObj = MargemPrazo::where('estabelecimento', $pedido->estabelecimento)->first();

            $desconto_maximo_gerente = 1 - $parametrosAprovacaoObj[($parametrosAprovacaoObj->search(function ($item, $key){ return strtolower($item->tipoUsuario->nome) == 'gerente'; }))]->percentual_desconto / 100;

            $fator_comissao = $margemPrazoObj->preco_b - 1;
            
            $infoProduto = new ProdutoController;
            $promocional = $infoProduto->promocional(strtoupper($campos['produto_codigo']), $pedido->estabelecimento, $pedido->usuario_detalhes->codigo_representante, $pedido->cod_cliente, $frete);

            $precos_promocao = [];
            if($promocional === true){
                $items = new ListaDePrecosRequest([
                    'origem' => $origem,
                    'produto' => strtoupper($campos['produto_codigo']),
                    'moeda' => ($pedido->pedido_futuro === true && $origem == 'RO') ? 'dolar' : 'real',
                    'prazo_medio' => $pedido->condicao_pagamento_detalhes['media'] ?? 0,
                    'frete' => $frete,
                    'estado' => $estadoObj->uf, 
                    'cidade' => $pedido->cliente->cidade,
                    'tipo_cliente' => (
                        $pedido->cliente->inscricaoestadual == 'ISENTO' ||
                        intval($pedido->cliente->indicadorinscricaoestadual) == 2 ||
                        intval($pedido->cliente->indicadorinscricaoestadual) == 9
                    ) ? 'isento' : 'juridico',
                    'promocao' => true,
                    'estabelecimento' => $pedido->rj_x_sp == true? 7 : $pedido->estabelecimento,
                    'cliente' => $pedido->cod_cliente,
                    'codigo_vendedor' => $pedido->usuario_detalhes->codigo_representante,
                ]);
                $precos_promocao = $precoObj->filter($items, true, false, true);
                $precos_promocao = reset($precos_promocao);
            }

            $coluna_c = $precos[0][2];
            $coluna_b = $precos[0][1];
            $coluna_a = $precos[0][0];
            $preco_unitario = parserNumber($campos['preco_unitario']);
            $infoProduto;

            if(!empty($pedido->usuario_detalhes->detalhesModelHasRoles)){
                $perfil_acesso = $pedido->usuario_detalhes->detalhesModelHasRoles->detalhesRoles->name;
            }else{
                $perfil_acesso = '';
            }

            if($perfil_acesso === 'REP.Playstation'){
                $coluna_preco = '0';
                if ($coluna_a <= $preco_unitario){

                    for($x = 0; $x <= 12; $x++ ){
                        if($precos[0][$x] <= $preco_unitario){
                            $acrescimo_comissao = $x;
                        }
                    }

                    $comissao_porcentagem = $pedido->usuario_detalhes["comissao_a"] + $acrescimo_comissao;
                    if ($comissao_porcentagem > 15){
                        $comissao_porcentagem = 15;
                    }
                }
                else if(
                    $coluna_a * (1 + $fator_comissao) > $preco_unitario &&
                    $coluna_a * $desconto_maximo_gerente <= $preco_unitario
                ){
                    $comissao_porcentagem = $pedido->usuario_detalhes["comissao_a"];
                }
                else if($coluna_a * $desconto_maximo_gerente > $preco_unitario) {
                    $porcentagem_desconto = 1 - (parserNumber($campos['preco_unitario']) / parserNumber($precos[0]['coluna_a']));
                    $desconto_a_mais = $porcentagem_desconto * 100 - (1 - $desconto_maximo_gerente) * 100;
                    $desconto_comissao = ceil($desconto_a_mais / 1.5) * 0.5;
                    $comissao_porcentagem = $pedido->usuario_detalhes->comissao_a - $desconto_comissao;
                    $comissao_porcentagem = $comissao_porcentagem >= 5 ? $comissao_porcentagem : 5;
                    $coluna_preco = '0';
                }

                $desconto_permitido = 9.01;
                if($pedido->cliente->cidade == 'Nova Friburgo'){
                    $desconto_permitido = 14.01;
                }
                if($coluna_a > $preco_unitario){
                    $desconto = ((1 - ($preco_unitario / $coluna_a)) * 100);
                    if(
                        $desconto <= $desconto_permitido
                    ){
                        $comissao_porcentagem = 6;
                    }
                    else{
                        $comissao_porcentagem = 5;
                        if($promocional === true){
                            $comissao_porcentagem = $precos_promocao['promocional']['comissao'];
                            if (
                                isset($precos_promocao['promocional']['sem_desconto_adicional']) &&
                                $precos_promocao['promocional']['sem_desconto_adicional'] === true &&
                                $preco_unitario < parserNumber($precos_promocao['coluna_a'])
                            ){
                                $error['preco_unitario'] = 'Preço abaixo da promoção, não permitida';
                                $error_return = ['status' => 'error', 'errors' => $error];
                                return response()->json($error_return, 422);
                            }
                            $preco_promocao = true;
                        }
                    }
                }

            }else if (strtolower(Auth::user()->tipo_usuario->nome) != 'vendedor interno'){
                $coluna_preco = '0';
                if (($coluna_a * 0.75) <= $preco_unitario){

                    for($x = 0; $x <= 12; $x++ ){
                        if(($precos[0][$x] * 0.75) <= $preco_unitario){
                            $acrescimo_comissao = $x;
                        }
                    }

                    $comissao_porcentagem = $pedido->usuario_detalhes["comissao_a"] + $acrescimo_comissao;
                    if ($comissao_porcentagem > 15){
                        $comissao_porcentagem = 15;
                    }
                }
                else if(
                    ($coluna_a * 0.75) * (1 + $fator_comissao) > $preco_unitario &&
                    ($coluna_a * 0.75) * $desconto_maximo_gerente <= $preco_unitario
                ){
                    $comissao_porcentagem = $pedido->usuario_detalhes["comissao_a"];
                }
                else if(($coluna_a * 0.75) * $desconto_maximo_gerente > $preco_unitario) {
                    $porcentagem_desconto = 1 - (parserNumber($campos['preco_unitario']) / (parserNumber($precos[0]['coluna_a']) * 0.75));
                    $desconto_a_mais = $porcentagem_desconto * 100 - (1 - $desconto_maximo_gerente) * 100;
                    $desconto_comissao = ceil($desconto_a_mais / 1.5) * 0.5;
                    $comissao_porcentagem = $pedido->usuario_detalhes->comissao_a - $desconto_comissao;
                    $comissao_porcentagem = $comissao_porcentagem >= 2 ? $comissao_porcentagem : 2;
                    $coluna_preco = '0';
                }

                // if(!in_array(strtolower($produtosEspecificacoes->grupo), $this->grupos_nao_comissao)){
                //     if($coluna_a > $preco_unitario){
                //         $desconto = ((1 - ($preco_unitario / $coluna_a)) * 100);
                //         if(
                //             $desconto <= 9.01
                //         ){
                //             $comissao_porcentagem = 3;
                //         }
                //         else{
                //             $comissao_porcentagem = 2;
                //             if($promocional === true){
                //                 $comissao_porcentagem = $precos_promocao['promocional']['comissao'];
                //                 if (
                //                     isset($precos_promocao['promocional']['sem_desconto_adicional']) &&
                //                     $precos_promocao['promocional']['sem_desconto_adicional'] === true &&
                //                     $preco_unitario < parserNumber($precos_promocao['coluna_a'])
                //                 ){
                //                     $error['preco_unitario'] = 'Preço abaixo da promoção, não permitida';
                //                     $error_return = ['status' => 'error', 'errors' => $error];
                //                     return response()->json($error_return, 422);
                //                 }
                //                 $preco_promocao = true;
                //             }
                //         }
                //     }
                // }

                // if(in_array(intval($pedido->estabelecimento), [3, 4])){
					$desconto_permitido = 9.01;
					if($pedido->cliente->cidade == 'Nova Friburgo'){
						$desconto_permitido = 14.01;
					}
                    if(($coluna_a * 0.75) > $preco_unitario){
                        $desconto = ((1 - ($preco_unitario / $coluna_a * 0.75)) * 100);
                        if(
                            $desconto <= $desconto_permitido
                        ){
                            $comissao_porcentagem = 3;
                        }
                        else{
                            $comissao_porcentagem = 2;
                            if($promocional === true){
                                $comissao_porcentagem = $precos_promocao['promocional']['comissao'];
                                if (
                                    isset($precos_promocao['promocional']['sem_desconto_adicional']) &&
                                    $precos_promocao['promocional']['sem_desconto_adicional'] === true &&
                                    $preco_unitario < parserNumber($precos_promocao['coluna_a'])
                                ){
                                    $error['preco_unitario'] = 'Preço abaixo da promoção, não permitida';
                                    $error_return = ['status' => 'error', 'errors' => $error];
                                    return response()->json($error_return, 422);
                                }
                                $preco_promocao = true;
                            }
                        }
                    }
                // }else{
                //     if($promocional === true){
                //         $comissao_porcentagem = $precos_promocao[0]['promocional']['comissao'];
                //         $preco_promocao = true;
                //     }
                // }

                if($pedido->bionexo == true){
                    if($comissao_porcentagem > 2){
                        $comissao_porcentagem -= 1;
                        if($comissao_porcentagem < 2){
                            $comissao_porcentagem = 2;
                        }
                    }
                }
            }else{
                $coluna_preco = '0';
                $comissao_porcentagem = $pedido->usuario_detalhes["comissao_a"];
            }

            if($frete == 'cif'){
                $frete_aliquota = ( $aliquotaObj->frete_adicional / 100 ) + 1;
                $valor_frete = ( parserNumber($campos['preco_unitario']) * parserNumber(['quantidade']) ) * $frete_aliquota;
            }
            else{
                $valor_frete = 0;
            }
            $valor_icms = (
                (
                    parserNumber($campos['preco_unitario']) * parserNumber($campos['quantidade'])
                ) * ( $aliquotaObj->aliquota / 100 )
            );
            $base_calculo_icms = (
                (
                    parserNumber($campos['preco_unitario']) * parserNumber($campos['quantidade'])
                ) * (
                    1 - ( $aliquotaObj->aliquota / 100 ) )
                );
        }

		$preco_original = 0;
		$preco_unitario = parserNumber($campos['preco_unitario']);
		if($pedido->metragem_exata === true && $pedido->estabelecimento != 3){
			$preco_original = $preco_unitario;
			$preco_unitario = $preco_unitario * (1 + ($this->porcentagem_metragem_exata / 100));
		}
		if($pedido->status_pedido != 1){
			if($pedido->status_pedido == 2){
				$pedido->aprovacao->delete();
			}
			$pedido->status_pedido = 1;
			$pedido->updated_by = Auth::id();
			$pedido->save();
		}
		
        /*
		if(strtolower($produtosEspecificacoes->linha) == 'outlet' && $pedido->outlet == false){
			$validadeOutlet = $this->validadeOutlet($pedido);
			if($validadeOutlet == true){
				$pedido->outlet = true;
				$pedido->save();
			}else{
				$error['produto_codigo'] = 'Pedido não pode misturar produtos com linha outlet';
				$error_return = ['status' => 'error', 'errors' => $error];
				return response()->json($error_return, 422);
			}

		}elseif(strtolower($produtosEspecificacoes->linha) != 'outlet' && $pedido->outlet == true){
			$error['produto_codigo'] = 'Pedido não pode misturar produtos com linha outlet';
			$error_return = ['status' => 'error', 'errors' => $error];
			return response()->json($error_return, 422);
		}*/

        $excecao_comissao = ProdutoPromocional::select();
        $excecao_comissao->whereNull('codigo_cliente');
        $excecao_comissao->whereNull('codigo_produto');
        $excecao_comissao->whereNull('codigo_estabelecimento');
        $excecao_comissao->whereNull('tipo_frete');
        $excecao_comissao->whereNull('codigo_cliente');
        $excecao_comissao->whereNull('grupo');
        $excecao_comissao->where('codigo_vendedor', $pedido->usuario);
        $excecao_comissao->where('tipo_promocional', 'pedido');
        $excecao_comissao = $excecao_comissao->first();
        if(!empty($excecao_comissao)){
            $comissao_porcentagem = $excecao_comissao->comissao;
        }

        if($pedido->usuario == 135 && in_array($pedido->cod_cliente, $this->codigo_cliente_red_d_or_sao_luiz)){
            $comissao_porcentagem = 3;
        }

        $pedido_item = new PedidoItemPortal;

        $pedido_item->pedido = $pedido->id;
    	$pedido_item->usuario = Auth::id();
        $pedido_item->cod_produto = strtoupper($campos['produto_codigo']);
    	$pedido_item->quantidade = parserNumber($campos['quantidade']);
    	$pedido_item->preco_unitario = $preco_unitario;
    	$pedido_item->created_by = Auth::id();
        $pedido_item->valor_icms = $valor_icms;
        $pedido_item->base_calculo_icms = $base_calculo_icms;
        $pedido_item->valor_ipi = 0;
        $pedido_item->aliquota_icms = $aliquotaObj->aliquota;
        $pedido_item->aliquota_ipi = 0;
        $pedido_item->valor_frete = $valor_frete;
        $pedido_item->valor_total = round( ( $preco_unitario * parserNumber($campos['quantidade']) ), 2);
        $pedido_item->coluna = $coluna_preco;
        $pedido_item->comissao = $comissao_porcentagem;
        $pedido_item->ipi_produto = $produtoObj->ipi;
        $pedido_item->preco_base = $produtoObj->precovenda;
        $pedido_item->coluna_a = $coluna_a;
        $pedido_item->coluna_b = $coluna_b;
        $pedido_item->coluna_c = $coluna_c;
        $pedido_item->preco_promocao = $preco_promocao;
        $pedido_item->produto_sem_estoque = $produto_sem_estoque;
        
        $pedido_item->codigo_tecidos_base = $campos['produto_codigo_base'];
        $pedido_item->codigo_desenho = $campos['produto_codigo_desenho'];

        $pedido_item->preco_original = $preco_original;

        $pedido_item->tem_ipi = $tem_ipi;

        $pedido_item->token_promocional = "ultra_black";
        $pedido_item->promocional_unidade = $campos['promocional_unidade'];
        $pedido_item->promocional_preco_unitario = parserNumber($campos['preco_segundario']);
        $pedido_item->promocional_quantidade = parserNumber($campos['estoque_segundario']);
        $pedido_item->unidade = $campos['unidade'];
        $pedido_item->gml = $campos['gml'];

        $pedido_item->save();

        $promocao = '';
        if($preco_promocao === true){
            $promocao = ' <div><div class="text-danger" data-toggle="tooltip" data-placement="left" data-html="true" title="Preço promocinal" data-content="">*</div></div> ';
        }
        $ipi = '';
        if($tem_ipi === true){
            $ipi = ' <span class="text-danger" data-toggle="tooltip" data-placement="left" data-html="true" title="Preço com IPI" data-content="">*</span>';
        }
        $descricao = $pedido_item->info_produtoNasjon->especificacao;
        if(in_array($pedido->tipo_venda, ['producao', 'producao_triangular', 'pre_pago_producao', 'pre_pago_producao_triangular'])){
            $desenho = '<b>Desenho: </b> ' . $pedido_item->desenho->codigo_produto. ' - ' . $pedido_item->desenho->descricao;
            $base = '<b>Tecido Base: </b> ' . $pedido_item->tecidosBase->codigo_produto. ' - ' . $pedido_item->tecidosBase->descricao;
            $descricao = '<div><div class="producao" data-toggle="popover" data-placement="left" data-html="true" title="Produção" data-content="'.$base.'<br>'.$desenho.'">' . $descricao . '</div></div>';
        }
        $result = [
        	'id' => $pedido_item->id,
	        'codigo' => utf8_encode($pedido_item->info_produtoNasjon->codigo),
	        'descricao' => empty($pedido_item->especificacoes->produtoGrupo->pecas)? $descricao : $descricao.' - Pecas: '.$pedido_item->especificacoes->produtoGrupo->pecas,
            'quantidade' => "<div><div class='estoque' data-toggle='popover' data-placement='left' data-html='true' title='' data-content=''>" . parserValor($pedido_item->quantidade) . "</div></div>",
            'peso_total' => parserValor($pedido_item->quantidade * $pedido_item->info_produtoNasjon->pesoliquido) . ' kg',
            'preco_unitario' => "<div><div class='preco' data-preco_original='".parserValor($preco_original)."'  data-toggle='popover' data-placement='right' data-html='true' title='' data-content=''>" . !empty($produtoObj->ipi) && intval($campos['estabelecimento']) == 3? parserValor($pedido_item->preco_unitario * (1 + ($produtoObj->ipi/100))) : parserValor($pedido_item->preco_unitario) . $ipi .  "</div></div>",
	        'valor_total' => !empty($produtoObj->ipi) && intval($campos['estabelecimento']) == 3? parserValor($pedido_item->valor_total * (1 + ($produtoObj->ipi/100))) : parserValor($pedido_item->valor_total),
            'coluna' => $pedido_item->coluna,
            'comissao' => $promocao.str_replace('.', ',', $pedido_item['comissao']) . '%',
            'valor_ipi' => $pedido_item->ipi_produto,

            'total_itens' => $pedido->itens_pedido->count(),
            'valor_total_produtos' => parserValor($pedido->valor_total->total),
            'valor_total_frete' => parserValor($pedido->valor_total_frete_produto->total),
            'valor_desconto' => parserValor($pedido->valor_desconto),
            'valor_total_pedido' => parserValor($pedido->valor_total->total + ($pedido->tipo_frete == 'C' ? $pedido->valor_frete : 0) - $pedido->valor_desconto),
            'ipi' => $ipi,
			'preco_original' => $preco_original,
            'grupo_descricao' => $pedido_item->especificacoes->produtoGrupo->descricao,
            'grupo_id' => $pedido_item->especificacoes->produtoGrupo->id,
        ];
        return $result;

    }
    
    public function validarProdutoNasajon($origem, $cod_produto, $estado, $aliquota, $regiao, $pedidoObj, $preco_unitario, $quantidade, $cidade, $preco_custo = false){

        $razao_cnpj_textil = '06311274';

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $pedidoObj->cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        $produtoObj = ProdutoEspecificacao::where('codigo_produto', strtoupper($cod_produto))->first();

        $media_prazo = $pedidoObj->condicao_pagamento_detalhes->media ?? 0;

        $frete = $pedidoObj->frete_preco;

        $arr['origem'] = $origem;
        $arr['produto'] = strtoupper($cod_produto);
        $arr['estado'] = $estado;
        $arr['cidade'] = $cidade;
        $arr['moeda'] = ($pedidoObj->pedido_futuro === true && $origem == 'RO') ? 'dolar' : 'real';
        $arr['frete'] = $frete;
        $arr['regiao'] = $regiao;
        $arr['tipo_cliente'] = (
            $pedidoObj->cliente->inscricaoestadual == 'ISENTO' ||
            intval($pedidoObj->cliente->indicadorinscricaoestadual) == 2 ||
            intval($pedidoObj->cliente->indicadorinscricaoestadual) == 9
        ) ? 'isento' : 'juridico';
        $arr['prazo_medio'] = $pedidoObj->condicao_pagamento_detalhes->media ?? 0;

        $promocao = false;
        if($pedidoObj->pedido_futuro === false){
            $promocao = true;
        }
        $arr['promocao'] = $promocao;
        if($pedidoObj->rj_x_sp == true){
            $arr['estabelecimento'] = 7;
        }else{
            $arr['estabelecimento'] = $pedidoObj->estabelecimento;
        }
        
        $arr['cliente'] = $pedidoObj->cod_cliente;
        $arr['codigo_vendedor'] = $pedidoObj->usuario_detalhes->codigo_representante;

        $listaDePrecosObj = new ListagemDePrecosController();
        $filter_request = new ListaDePrecosRequest($arr);

        $arr_result = $listaDePrecosObj->filter($filter_request, true, false, true, false, false, false, false, false);
        $parametrosPedidoObj = ParametrosPedido::where('estabelecimento', $pedidoObj->estabelecimento)->get()->first();

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $pedidoObj->cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        $preco_custo = false;
        if(
            $razao_cnpj_textil !== $raiz_cnpj
        ){
            if(in_array($produtoObj->grupo, $this->grupos_especiais)){
                if(parserNumber($arr_result[0]['coluna_a']) > parserNumber($preco_unitario)){
                    $desconto = ((1 - (parserNumber($preco_unitario) / parserNumber($arr_result[0]['coluna_a']))) * 100);
                    if($desconto > $this->desconto_especiais){
                        $preco_unitario_erro = "desconto máximo para este produto é {$this->desconto_especiais}%";
                    }
                }
            }
        }

        $valor_minimo = ceil(((parserNumber($arr_result[0]['coluna_a']) * 0.75) * (1 - $parametrosPedidoObj->valor_minimo_porcentagem/100) ) * 100) / 100;

        $arr['promocao'] = false;
        $filter_request = new ListaDePrecosRequest($arr);
        $arr_result_max = $listaDePrecosObj->filter($filter_request, true, false, true, false, false, false, false, false);

        $valor_maximo = ceil(((parserNumber($arr_result_max[0]['coluna_c']) * 0.75) * (1 + $parametrosPedidoObj->valor_maximo_porcentagem/100) ) * 100) / 100;
        if(
            $razao_cnpj_textil !== $raiz_cnpj
        ){
            if (floatval($valor_minimo) > parserNumber($preco_unitario)){
                $preco_unitario_erro = 'O preço unitário do item, está abaixo do mínimo permitido. Favor verificar' . in_array($pedidoObj->cod_cliente, $this->codigo_grupo_textil_mn);
            }
            

            if (floatval($valor_maximo) < parserNumber($preco_unitario)){
                $preco_unitario_erro = 'O preço unitário do item está acima do máximo permitido. Favor verificar';
            }
        }

        if(isset($preco_unitario_erro)){
            $error['preco_unitario'] = $preco_unitario_erro;
            return [
                'status' => 'error', 
                'errors' => $error,
                'codigo_produto' => $cod_produto,
            ];
        }    
        
        $infoProduto = new ProdutoController();
        try {
            $result_estoque = $infoProduto->retornarDadosEstoqueNasajon($produtoObj, str_pad($pedidoObj->estabelecimento, 2, "0", STR_PAD_LEFT), $pedidoObj, $preco_custo);
        } catch (Exception $e) {
            return response()
            ->json(
                [
                    'status' => 'error', 
                    'message' => 'Erro na execução: ' . $e->message . ' - ' . var_dump($result_estoque),
                    'error' => [],
                    'response' => []
                ],
            422);
        }
        if(empty($result_estoque)){
            $quantidade_erro = 'O produto não tem estoque para completar o pedido. Favor verificar.';

            ProdutosSemEstoque::create([
                'pedido' => $pedidoObj->id, 
                'cod_produto'=> strtoupper($cod_produto),
                'user' => Auth::id(),
                'qtd' => parserNumber($quantidade),
                'cliente' => $pedidoObj->cod_cliente,
                'pedido_futuro' => false,
                'data_pedido' => $pedidoObj->data_pedido
            ]);

        }
        else {

            if ($pedidoObj->pedido_futuro === true){

                $coluna_quinzena = date('\k_Y_m_', strtotime($pedidoObj->data_previsao_entrega)) . (date('j', strtotime($pedidoObj->data_previsao_entrega)) <= 15? "1": "2");
                 
                if (parserNumber($result_estoque['quinzenas'][$coluna_quinzena]['quantidade']) < parserNumber($quantidade)) {
                    $quantidade_erro = ['O produto só tem '. $result_estoque['quinzenas'][$coluna_quinzena]['quantidade'] .' ' . $produtoObj->unidade . ' disponível na compra da quinzena e não atenderá o pedido. Favor verificar.'];

                    ProdutosSemEstoque::create([
                        'pedido' => $pedidoObj->id, 
                        'cod_produto'=> strtoupper($cod_produto), 
                        'user' => Auth::id(),
                        'qtd' => floatval(parserNumber($quantidade) - parserNumber($result_estoque['quinzenas'][$coluna_quinzena]['quantidade'])),
                        'pedido_futuro' => true,
                        'data_entrega' => $pedidoObj->data_previsao_entrega,
                        'cliente' => $pedidoObj->cod_cliente
                    ]);
                }

            }
            else if($pedidoObj->pedido_futuro === false){
                if (parserNumber($result_estoque['pronta_entrega']) < parserNumber($quantidade)) {
                    $quantidade_erro = ['O produto só tem '. $result_estoque['pronta_entrega'] .' ' . $produtoObj->unidade . ' em estoque e não atenderá o pedido. Favor verificar.'];

                    ProdutosSemEstoque::create([
                        'pedido' => $pedidoObj->id, 
                        'cod_produto'=> strtoupper($cod_produto), 
                        'user' => Auth::id(),
                        'qtd' => floatval(parserNumber($quantidade) - parserNumber($result_estoque['pronta_entrega'])),
                        'pedido_futuro' => false,
                        'data_pedido' => $pedidoObj->data_pedido,
                        'cliente' => $pedidoObj->cod_cliente
                    ]);
                }
            }
            else{
                $quantidade_erro = [$pedidoObj->pedido_futuro];
            }
        }

        if(isset($quantidade_erro)){
            $error['quantidade'] = $quantidade_erro;    
        }
        if(isset($error)){
            return ['status' => 'error', 'errors' => $error,];      
        }
    }

    public function validarProdutoNasajonProducao($PedidoPortalObj, $produto_codigo, $produto_codigo_base, $produto_codigo_desenho, $preco_unitario, $quantidade, $regiao, $preco_custo = false){
        switch ($PedidoPortalObj->estabelecimento) {
            case '3':
                $origem = "RO";
            break;
            case '4':
                $origem = "TO";
            break;
            default:
                $origem = "SP";
            break;
        }
        $estado = $PedidoPortalObj->cliente->uf;
        $cidade = $PedidoPortalObj->cliente->cidade;

        $media_prazo = $PedidoPortalObj->condicao_pagamento_detalhes->media ?? 0;

        $frete = $PedidoPortalObj->frete_preco;

        $arr['origem'] = $origem;
        $arr['produto'] = strtoupper($produto_codigo);
        $arr['estado'] = $estado;
        $arr['cidade'] = $cidade;
        $arr['moeda'] = ($PedidoPortalObj->pedido_futuro === true && $origem == 'RO') ? 'dolar' : 'real';
        $arr['frete'] = $frete;
        $arr['regiao'] = $regiao;
        $arr['tipo_cliente'] = (
            $PedidoPortalObj->cliente->inscricaoestadual == 'ISENTO' ||
            intval($PedidoPortalObj->cliente->indicadorinscricaoestadual) == 2 ||
            intval($PedidoPortalObj->cliente->indicadorinscricaoestadual) == 9
        ) ? 'isento' : 'juridico';
        $arr['prazo_medio'] = $PedidoPortalObj->condicao_pagamento_detalhes->media ?? 0;

        $promocao = false;
        if($PedidoPortalObj->pedido_futuro === false){
            $promocao = true;
        }
        $arr['promocao'] = $promocao;
        if($PedidoPortalObj->rj_x_sp == true){
			$arr['estabelecimento'] = 7;
		}else{
            $arr['estabelecimento'] = $PedidoPortalObj->estabelecimento;
        }
        
        $arr['cliente'] = $PedidoPortalObj->cod_cliente;
        $arr['codigo_vendedor'] = $PedidoPortalObj->usuario_detalhes->codigo_representante;

        if($PedidoPortalObj->rj_x_sp == true){
			$arr['estabelecimento'] = 7;
		}else{
            $arr['estabelecimento'] = $PedidoPortalObj->estabelecimento;
        }

        $listaDePrecosObj = new ListagemDePrecosController();
        $filter_request = new ListaDePrecosRequest($arr);

        $retultado_preco = $listaDePrecosObj->filter($filter_request, true, false, true, false, false, false, false, false);
        $parametrosPedidoPortalObj = ParametrosPedido::where('estabelecimento', $PedidoPortalObj->estabelecimento)->get()->first();
        $retultado_preco = reset($retultado_preco);

        $valor_minimo = ceil((parserNumber($retultado_preco['coluna_a']) * (1 - $parametrosPedidoPortalObj->valor_minimo_porcentagem/100) ) * 100) / 100;

        $arr['promocao'] = false;
        $filter_request = new ListaDePrecosRequest($arr);
        $resultado_preco_maxio = $listaDePrecosObj->filter($filter_request, true, false, true, false, false, false, false, false);
        $resultado_preco_maxio = reset($resultado_preco_maxio);

        $valor_maximo = ceil((parserNumber($resultado_preco_maxio['coluna_c']) * (1 + $parametrosPedidoPortalObj->valor_maximo_porcentagem/100) ) * 100) / 100;
        if(!in_array($PedidoPortalObj->cod_cliente, $this->codigo_grupo_textil_mn)){
            if (floatval($valor_minimo) > parserNumber($preco_unitario)){
                $preco_unitario_erro = 'O preço unitário do item, está abaixo do mínimo permitido. Favor verificar' . in_array($PedidoPortalObj->cod_cliente, $this->codigo_grupo_textil_mn);
            }

            if (floatval($valor_maximo) < parserNumber($preco_unitario)){
                $preco_unitario_erro = 'O preço unitário do item está acima do máximo permitido. Favor verificar';
            }
        }

        if(isset($preco_unitario_erro)){
            $error['preco_unitario'] = $preco_unitario_erro;
            return ['status' => 'error', 'errors' => $error];
        }    
        
        $infoProduto = new ProdutoController();
        $produtoObj = ProdutoEspecificacao::where('codigo_produto', strtoupper($produto_codigo))->first();

        try {
            $result_estoque = $infoProduto->retornarDadosEstoqueNasajon($produtoObj, str_pad($PedidoPortalObj->estabelecimento, 2, "0", STR_PAD_LEFT), $PedidoPortalObj, $preco_custo);
        } catch (Exception $e) {
            return response()
            ->json(
                [
                    'status' => 'error', 
                    'message' => 'Erro na execução: ' . $e->message . ' - ' . var_dump($result_estoque),
                    'error' => [],
                    'response' => []
                ],
            422);
        }

        $produto_sem_estoque = false;
        if(empty($result_estoque['pronta_entrega']) || floatval($result_estoque['pronta_entrega']) <= floatval($quantidade  )){
            $produto_sem_estoque = true;
        }
        
        return ['status' => 'success', 'response' => ['produto_sem_estoque' => $produto_sem_estoque]];
    }

    public function excluir(Request $request){
        $fields = $request->only(['grupo_id', 'pedido']);

        $produtoEspecificacaoObj = ProdutoEspecificacao::with(['produtoGrupo'])
            ->where('produto_grupos_id', $fields['grupo_id'])
            ->where('linha', 'ULTRA BLACK')
            ->get();
        
        foreach($produtoEspecificacaoObj as $produto){
            $pedidoItemPortalObj = PedidoItemPortal::select()
                ->where('pedido', $fields['pedido'])
                ->where('cod_produto', $produto->codigo_produto)
                ->where('token_promocional', 'ultra_black')
                ->first();
            if(!empty($pedidoItemPortalObj)){
                $arr = [];
                $arr['id'] = $pedidoItemPortalObj->id;

                $arr_request = new Request($arr);

                $pedidoItemPortalControllerObj = new PedidoItemPortalController;
                $pedidoItemPortalControllerObj->excluiProduto($arr_request);
            }            
        }

        $itens = [];
        
        $pedidoPortalObj = PedidoPortal::with('itens_pedido', 'itens_pedido.especificacoes.produtoGrupo')->find($fields['pedido']);
        $pedidoPortalObj->itens_pedido->each(function($item) use(&$itens) {
            $preco_original = $item->preco_unitario;
            $itens[] = [
                'id' => $item->id,
                'codigo' => $item->especificacoes->codigo_produto,
                'descricao' => empty($item->especificacoes->produtoGrupo->pecas)? $item->especificacoes->descricao : $item->especificacoes->descricao." - Pecas: ".$item->especificacoes->produtoGrupo->pecas,
                'preco_unitario' => "<div><div class='preco' data-preco_original='".parserValor($preco_original)."'  data-toggle='popover' data-placement='right' data-html='true' title='' data-content=''>" . parserValor($item->preco_unitario) . '</div></div>',
                'quantidade' => "<div><div class='estoque' data-toggle='popover' data-placement='left' data-html='true' title='' data-content=''>" . parserValor($item->quantidade) . '</div></div>',
                'valor_total' => parserValor($item->valor_total),
                'coluna' => $item->coluna,
                'comissao' => $item->comissao . "%",
                'token_promocional' => $item->token_promocional,
                'grupo_descricao' => $item->especificacoes->produtoGrupo->descricao,
                'grupo_id' => $item->especificacoes->produtoGrupo->id,
            ];

        });

        return response()->json(['status' => 'success', 'message' => '', 'response' => ['itens' => $itens??[]]], 200);
    }
}
