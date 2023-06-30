<?php

namespace App\Http\Controllers;

use App\AliquotaPreco;
use App\PedidoPortal;
use App\PedidoItemPortal;
use App\ProdutoNasajon;
use App\ProdutosEstoque;

use App\Http\Controllers\ListagemDePrecosController;

use Illuminate\Http\Request;
use App\Http\Requests\ListaDePrecosRequest;
use App\Http\Requests\ImportacaoCodigoBarrasRequest;

use Auth;

class ImportacaoCodigoBarrasController extends Controller
{
    public function importacaoProdutoPedido(ImportacaoCodigoBarrasRequest $request){

        $fields = $request->only('pedido');
        $arquivo = $request->file('arquivo');
        $stream = fopen($arquivo->getPathName(), 'r');

        $codigo = [];

        while(($data = fgetcsv($stream, 0)) !== false){

            if(isset($codigo[$data[0]])){
                $codigo[$data[0]]++;
            }
            else{
                $codigo[$data[0]] = 1;
            }

        }
        
        $pedidoPortalObj = PedidoPortal::with('cliente', 'condicao_pagamento_detalhes', 'estabelecimentoDetalhes', 'usuario_detalhes')
        ->find($fields['pedido']);

        $produtoNasajonObj = ProdutoNasajon::whereIn('codigodebarras', array_keys($codigo))->get();

        $produtos = [];
        $verificacao_pelo_codigo__produto = false;
        $comissao = $pedidoPortalObj->usuario_detalhes->comissao_a;
        if(empty($produtoNasajonObj->count())){
            $produtoNasajonObj = ProdutoNasajon::whereIn('codigo', array_keys($codigo))->get();
            $verificacao_pelo_codigo__produto = true;
        }  

        if(!in_array(str_pad($pedidoPortalObj->estabelecimento, 2, '0', STR_PAD_LEFT), ['01', '02']) &&
            $pedidoPortalObj->estabelecimentoDetalhes->raizcnpj == str_replace('.', '', explode('/', $pedidoPortalObj->cliente->cpf_cnpj)[0])) {

            $produtoNasajonObj->load('estoques');
                
            $produtoNasajonObj->each( function($produto) use($pedidoPortalObj, $codigo, &$produtos, $verificacao_pelo_codigo__produto, $comissao) {
                
                $estoque = $produto->estoques->firstWhere('estabelecimento', $pedidoPortalObj->estabelecimento);

                $preco = $estoque->custo ?? 0;

                if($verificacao_pelo_codigo__produto){
                    $quantidade = $codigo[$produto->codigo];
                    $valor_total = $preco * $codigo[$produto->codigo];
                }else{
                    $quantidade = $codigo[$produto->codigodebarras];
                    $valor_total = $preco * $codigo[$produto->codigodebarras];
                }

                $produtos[] = [
                    'pedido' => $pedidoPortalObj->id,
                    'usuario' => Auth::user()->id,
                    'cod_produto' => $produto->codigo,
                    'quantidade' => $quantidade,
                    'preco_unitario'=> $preco,
                    'created_by' => Auth::user()->id,
                    'comissao' => $comissao,
                    'valor_total' => $valor_total
                ];

            });

        }else{
            if($pedidoPortalObj->estabelecimento == 3 && $pedidoPortalObj->pedido_futuro === true){
                $moeda = 'dolar';
            }
            else{
                $moeda = 'real';
            }
            $requestListaprecos = new ListaDePrecosRequest([
                'origem' => $pedidoPortalObj->origem,
                'produto' => $produtoNasajonObj->pluck('codigo'),
                'estado' => $pedidoPortalObj->cliente->uf,
                'moeda' => $moeda,
                'frete' => $pedidoPortalObj->frete_preco,
                'tipo_cliente' => (
                    $pedidoPortalObj->cliente->inscricaoestadual == 'ISENTO' ||
                    intval($pedidoPortalObj->cliente->indicadorinscricaoestadual) == 2 ||
                    intval($pedidoPortalObj->cliente->indicadorinscricaoestadual) == 9
                ) ? 'isento' : 'juridico',
                'prazo_medio' => $pedidoPortalObj->condicao_pagamento_detalhes->media ?? 0,
            ]);

            $use_auth = true;
            $salvar_pesquisa = false;
            $retornar_array = true;
            $aprovacao = true;
            $exportacao = true;
            $deletados = false;
            $olharEstoque = false;

            $listaDePrecosControllerObj = new ListagemDePrecosController;
    
            $listaPrecosObj = collect($listaDePrecosControllerObj->filter(
                $requestListaprecos,
                $use_auth,
                $salvar_pesquisa,
                $retornar_array,
                $aprovacao,
                $exportacao,
                $deletados,
                $olharEstoque
            ));

            $listaPrecosObj->each( function($item) use($pedidoPortalObj, $produtoNasajonObj, $codigo, &$produtos, $verificacao_pelo_codigo__produto, $comissao){
                $produtoNasajon = $produtoNasajonObj->firstWhere('codigo', $item['cod_produto']);

                $preco = str_replace(',','.',$item['coluna_a']);
    
                if(PedidoItemPortal::where('pedido', $pedidoPortalObj->id)->where('cod_produto', $item['cod_produto'])->exists()){
                    return;
                }

                if($verificacao_pelo_codigo__produto){
                    $quantidade = $codigo[$produtoNasajon->codigo];
                    $valor_total = $preco * $codigo[$produtoNasajon->codigo];
                }else{
                    $quantidade = $codigo[$produtoNasajon->codigodebarras];
                    $valor_total = $preco * $codigo[$produtoNasajon->codigodebarras];
                }
    
                $produtos[] = [
                    'pedido' => $pedidoPortalObj->id,
                    'usuario' => Auth::user()->id,
                    'cod_produto' => $item['cod_produto'],
                    'quantidade' => $quantidade,
                    'preco_unitario'=> $preco,
                    'created_by' => Auth::user()->id,
                    'comissao' => $comissao,
                    'valor_total' => $valor_total
                ];
            });
        }

        PedidoItemPortal::insert($produtos);

        $pedidoItemPortalObj = PedidoItemPortal::where('pedido', $pedidoPortalObj->id)->get();

        $itens = [];

        $pedidoItemPortalObj->each(function ($value) use(&$itens){
            $itens[] = [
                'id' => $value->id,
                'grupo' => $value->especificacoes->grupo,
                'codigo' => $value->especificacoes->codigo_produto,
                'descricao' => $value->especificacoes->descricao,
                'marca' => $value->especificacoes->marca,
                'linha' => $value->especificacoes->linha,
                'preco_unitario' => "<div><div class='preco' data-toggle='popover' data-placement='right' data-html='true' title='' data-content=''>" . parserValor($value->preco_unitario) . '</div></div>',
                'quantidade' => "<div><div class='estoque' data-toggle='popover' data-placement='left' data-html='true' title='' data-content=''>" . parserValor($value->quantidade) . '</div></div>',
                'valor_total' => parserValor($value->valor_total),
                'coluna' => $value->coluna,
                'comissao' => $value->comissao . "%",
            ];
        });

        return response()->json(['status' => 'success', 'message' => '', 'response' => ['itens' => $itens??[]]], 200);
    }
}
