<?php

namespace App\Http\Controllers;

use App\HistoricoPedido;
use App\ProdutoEspecificacao;
use App\PedidoPortal;
use App\PedidoItemPortal;

use App\Http\Requests\PedidoSalvarRequest;
use App\Http\Requests\PedidoItemPortalRequest;

use App\Http\Controllers\AprovacaoDePedidoController;
use App\Http\Controllers\PedidoPortalController;
use App\Http\Controllers\PedidoItemPortalController;
use App\Http\Controllers\ProdutoController;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

class VendeTodoEstoqueController extends Controller
{
    protected $estabelecimento_cliente = ['01' => '06311274000188', '02' => '07'];
    protected $estabelecimento_nome_cnpj = ['01' => 'TEXTIL MN COMERCIO DE TECIDOS E CONFECCOES LTDA. - 06.311.274/0006-92', '02' => 'BOTELHO - 06311274000773'];

    public function vendeTodosItens($estabelecimento){

        ini_set('memory_limit', '99999M');
        $agora = Carbon::now();

        $estabelecimento = str_pad($estabelecimento, 2, '0', STR_PAD_LEFT);

        $todosProdutosObj = ProdutoEspecificacao::with('produtoNasajon')
        ->whereHas('estoque', function ($query) use ($estabelecimento){
            $query->where('estabelecimento', $estabelecimento)
            ->where('estoque', '>', 0)
            ->where(function($q){
                $q->where('custo', '=', 0)
                ->orWhereNull('custo');
            });
        })
        ->orderBy('grupo')
        ->get();

        echo "Produtos localizados: " . $todosProdutosObj->count() . PHP_EOL;

        $pedido_item = [];

        $x = 0;
        $peso = 0;

        $todosProdutosObj->each(function($item) use (&$pedido_item, $estabelecimento,&$x, &$peso){

            $empenhoPortal = 0;

            $estoque = $item->estoque->firstWhere('estabelecimento', $estabelecimento);

            $peso_itens = $estoque->produtoNasajon->pesobruto * $estoque->estoque;

            if($peso + $peso_itens >= 25000){
                $x++;
                $peso = $peso_itens;
            }
            else{
                $peso += $peso_itens;
            }

            $pedido_item[$x][] = [
                'codigo_produto' => $item->codigo_produto,
                'quantidade' => $estoque->estoque,
                'preco_unitario' => $estoque->custo??0,
            ];
            echo "Pedidos: " . count($pedido_item) . PHP_EOL;

        });

        $aprovacaoDePedidoController = new AprovacaoDePedidoController;
        $pedidoPortalController = new PedidoPortalController;
        $pedidoItemPortalController = new PedidoItemPortalController;
        
        Auth::loginUsingId(1);

        foreach ($pedido_item as $pedido) {

            if(!empty($pedido)){
                
                $pedidoPortal = new PedidoPortal([
                    'tipo_venda' => 'venda',
                    'estabelecimento' => $estabelecimento,
                    'pedido_futuro' => false,
                    'cod_cliente' => $this->estabelecimento_cliente[$estabelecimento],
                    'nome_cliente' => $this->estabelecimento_nome_cnpj[$estabelecimento],
                    'condicao_pagamento' => '3469',
                    'transportadora' => '0026',
                    'tipo_frete' => 'P',
                    'transportadora_redespacho' => null,
                    'transportadora_redespacho_tipo_frete' => null,
                    'valor_frete_redespacho' => null,
                    'nome_contato' => null,
                    'email_contato' => null,
                    'no_pedido_compra' => null,
                    'observacao' => 'Teste de transferência de estoque',
                    'codigo_cliente_conta_e_ordem' => null,
                    'nasajon' => true,
                    'migracao' => true,
                    'usuario' => 1,
                    'status_pedido' => 1,
                    'data_pedido' => date('Y-m-d'),
                    'codigo_operacao' => 'PEDVENDAMIGRACAO',
                    'created_by' => 1
                ]);

                $pedidoPortal->save();

                foreach ($pedido as $item){
    
                    $itemPedido = new PedidoItemPortal(['pedido' => $pedidoPortal->id,
                        'cod_produto' => $item['codigo_produto'],
                        'quantidade' => $item['quantidade'],
                        'preco_unitario' =>  $item['preco_unitario'],
                        'estabelecimento' => $estabelecimento,
                        'cliente' => $this->estabelecimento_cliente[$estabelecimento],
                        'usuario' => 1,
                        'coluna' => 'a',
                        'comissao' => '0',
                        'preco_base' => $item['preco_unitario'],
                        'coluna_a' => 0,
                        'coluna_b' => 0,
                        'coluna_c' => 0,
                    ]);

                    $itemPedido->save();
                }

            }

            $historicoObj = new HistoricoPedido;

            $historicoObj->pedido = $pedidoPortal->id;
            $historicoObj->natureza = 'Pedido gerado para transferência';
            $historicoObj->novo = 'Pedido gerado para transferência de estoque - Estabelecimento ' . $estabelecimento ;
            $historicoObj->created_by = 1;

            $historicoObj->save();

            // Enviar pedido para aprovação
            $fields = ['id' => $pedidoPortal->id];
            $result = $aprovacaoDePedidoController->processaIntegracaoPedidoNasajon($pedidoPortal, $fields);

        }

        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%H horas, %i minutos e %s segundos') . PHP_EOL;

    }

    public function pedidosTocantinsArmazem(){

        ini_set('memory_limit', '99999M');
        $agora = Carbon::now();

        Auth::loginUsingId(1);

        $pedidosTransferenciaMigracaoObj = PedidosTransferenciaMigracao::all();

        foreach($pedidosTransferenciaMigracaoObj as $transferencia){

            $pedidoRequest = new PedidoSalvarRequest([
                'tipo_venda' => 'venda',
                'estabelecimento' => 4,
                'pedido_futuro' => 'false',
                'codigo_cliente' => '0975448480001',
                'condicao_pagamento' => '2704',
                'transportadora' => '0026',
                'transportadora_tipo_frete' => 'P',
                'transportadora_redespacho' => null,
                'transportadora_redespacho_tipo_frete' => null,
                'valor_frete_redespacho' => null,
                'nome_contato' => null,
                'email_contato' => null,
                'no_pedido_compra' => null,
                'observacao' => 'Teste de transferência de estoque',
                'codigo_cliente_conta_e_ordem' => null,
            ]);

            $id = $pedidoPortalController->salvar($pedidoRequest)->getData()->response->pedido;

            foreach($transferencia->pedido->itens_pedido as $item){

                $itemRequest = new PedidoItemPortalRequest(['pedido' => $id,
                    'cod_produto' => $item->cod_produto,
                    'quantidade' => $item->quantidade,
                    'preco_unitario' =>  parserValor($item->preco_unitario),
                    'estabelecimento' => '04',
                    'cliente' => '3725804280000' // Trocar pro CNPJ do armazém
                ]);

                $resposta = $pedidoItemPortalController->adicionarProduto($itemRequest);

                if (is_object($resposta)){

                    $resposta = $resposta->getData();

                    if($resposta->status == 'error'){
                        var_dump($resposta, $item->cod_produto, $item->quantidade);
                    }

                }

            }

            $transferencia->pedido_transferencia_armazem = $id;
            $transferencia->data_transferencia_armazem = date('Y-m-d');
            $transferencia->save();

            // Enviar pedido para aprovação;
            $request = new Request(['id' => $id]);
            $aprovacaoDePedidoController->aprovaPedido($request);

        }

        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%H horas, %i minutos e %s segundos') . PHP_EOL;

    }

    public function pedidosArmazemUnidade(){

        ini_set('memory_limit', '99999M');
        $agora = Carbon::now();

        Auth::loginUsingId(1);

        $estabelecimento = [1 => '0063112740001', 2 => '0063112740005']; // corrigir para os códigos certos

        $pedidosTransferenciaMigracaoObj = PedidosTransferenciaMigracao::all();

        foreach($pedidosTransferenciaMigracaoObj as $transferencia){

            $pedidoRequest = new PedidoSalvarRequest([
                'tipo_venda' => 'venda',
                'estabelecimento' => '20',
                'pedido_futuro' => 'false',
                'codigo_cliente' => $estabelecimento[$transferencia->pedido->estabelecimento],
                'condicao_pagamento' => '2704',
                'transportadora' => '0026',
                'transportadora_tipo_frete' => 'P',
                'transportadora_redespacho' => null,
                'transportadora_redespacho_tipo_frete' => null,
                'valor_frete_redespacho' => null,
                'nome_contato' => null,
                'email_contato' => null,
                'no_pedido_compra' => null,
                'observacao' => 'Teste de transferência de estoque',
                'codigo_cliente_conta_e_ordem' => null,
            ]);

            $id = $pedidoPortalController->salvar($pedidoRequest)->getData()->response->pedido;

            foreach($transferencia->pedido_armazem->itens_pedido as $item){

                $itemRequest = new PedidoItemPortalRequest(['pedido' => $id,
                    'cod_produto' => $item->cod_produto,
                    'quantidade' => $item->quantidade,
                    'preco_unitario' =>  parserValor($item->preco_unitario),
                    'estabelecimento' => '04',
                    'cliente' => '3725804280000' // Trocar pro CNPJ do armazém
                ]);

                $resposta = $pedidoItemPortalController->adicionarProduto($itemRequest);

                if (is_object($resposta)){

                    $resposta = $resposta->getData();

                    if($resposta->status == 'error'){
                        var_dump($resposta, $item->cod_produto, $item->quantidade);
                    }

                }

            }

            $transferencia->pedido_transferencia_nasajon = $id;
            $transferencia->data_transferencia_nasajon = date('Y-m-d');
            $transferencia->save();

            // Enviar pedido para aprovação;
            // $request = new Request(['id' => $id]);
            // $aprovacaoDePedidoController->aprovaPedido($request);

        }

        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%H horas, %i minutos e %s segundos') . PHP_EOL;

    }

}
