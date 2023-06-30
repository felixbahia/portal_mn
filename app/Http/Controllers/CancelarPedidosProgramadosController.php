<?php

namespace App\Http\Controllers;

use App\PedidoPortal;
use App\ComprasNasajon;
use App\HistoricoPedido;
use App\UnidadeConversaoProdutoNasajon;
use App\PedidoMaiorEstoque;
use App\AtualizacaoCron;

use App\Http\Controllers\EmailController;

use Illuminate\Http\Request;

use Carbon\Carbon;

class CancelarPedidosProgramadosController extends Controller
{
    public function cancelarPedidos(){
        ini_set('max_execution_time', 500);
        ini_set('memory_limit', '2024M');
        
        $pedidos = $this->buscaPedidosPortalProgramados();
        $pedidos_chegou = [];
        $pedidos_cancelado = [];
        $pedidos_saldo = [];
        foreach ($pedidos as $numero_pedido => $produtos_codigo) {
            foreach ($produtos_codigo as $produto_codigo){
                $compra_cancelou = $this->validaCompraCancelou($produto_codigo, $numero_pedido);
                if($compra_cancelou === true){
                    $pedidos_cancelado[$numero_pedido] = $numero_pedido;
                }
                unset($compra_cancelou);

                $saldo = $this->buscaSaldoCompras($produto_codigo, $numero_pedido);
                if(!empty($saldo)){
                    $pedidos_saldo[$numero_pedido][$produto_codigo] = $saldo;
                }
            }
        }
        unset($pedidos);

        $pedidos_ultrapassou_saldo = [];
        foreach ($pedidos_saldo as $pedido_compra => $dados) {
            foreach ($dados as $produto => $saldo){
                $pedidos_ultrapassou_saldo[] = $this->buscaPedidosPortalSaldo($pedido_compra, $produto, $saldo);
            }
        }
        $pedidos_ultrapassou_saldo = $this->transformArrayPedidos($pedidos_ultrapassou_saldo);
        $this->avisarPedidosCancelou($pedidos_ultrapassou_saldo);

        foreach ($pedidos_cancelado as $key => $pedido) {
            $pedidos_cancelado[$key] = $this->buscaPedidosPortalDePedidosDeCompras($pedido);
        }
        $pedidos_cancelado = $this->transformArrayPedidos($pedidos_cancelado);
        $this->avisarPedidosCancelou($pedidos_cancelado);
        unset($pedidos_cancelado);
    }

    private function buscaSaldoCompras($produto_codigo, $numero_pedido){
        $ComprasNasajonObj = ComprasNasajon::query();
        $ComprasNasajonObj->where('cod_produto', $produto_codigo);
        $ComprasNasajonObj->where('numero_pedido', $numero_pedido);
        $ComprasNasajonObj->whereIn('situacao', ['Aguardando Documento', 'Parcialmente Liquidado']);
        $ComprasNasajonObj = $ComprasNasajonObj->first();
        if(!empty($ComprasNasajonObj)){
            $quantidade = (float) $ComprasNasajonObj->quantidade_restante;
            $UnidadeConversaoProdutoNasajonObj = UnidadeConversaoProdutoNasajon::where('codigo_produto', $ComprasNasajonObj->cod_produto)->get();

            $conversao = ($UnidadeConversaoProdutoNasajonObj->firstWhere('codigo_unidadeconversao', $ComprasNasajonObj->unidade_comercial)->razao) ?? 1;
            $quantidade = $quantidade * $conversao;

            return $quantidade;
        }
        return 0;
    }

    private function buscaPedidosPortalSaldo($pedido_compra, $produto, $saldo){
        $pedidos = PedidoPortal::with(['usuario_detalhes', 'itens_pedido' => function($query) use ($produto, $pedido_compra){
            $query->where('cod_produto', $produto);
            $query->where('numero_compra', $pedido_compra);
        }]);
        $pedidos->where('status_pedido', 8);
        $pedidos->whereHas('itens_pedido', function($query) use ($produto, $pedido_compra){
            $query->where('cod_produto', $produto);
            $query->where('numero_compra', $pedido_compra);
        });
        $pedidos = $pedidos->get();
        
        $saldoPedidos = 0;
        $pedidos_passou_saldo = [];
        foreach($pedidos as $pedido){
            foreach($pedido->itens_pedido as $item){
                if($item->quantidade + $saldoPedidos > $saldo){
                    $pedidos_passou_saldo[] = $pedido;
                }else{
                    $saldoPedidos += $item->quantidade;
                }
            }
        }
        return $pedidos_passou_saldo;
    }

    private function buscaPedidosPortalProgramados(){
        $pedidos = PedidoPortal::with(['itens_pedido'=>function($query){
            $query->where('cod_produto', '7214050000351');
        }]);
        $pedidos->where('status_pedido', 8);
        $pedidos = $pedidos->get();
        $numeros_pedidos = [];
        $pedidos->each(function($pedido) use (&$numeros_pedidos){
            $pedido->itens_pedido->each(function($item) use (&$numeros_pedidos){
                if(!isset($numeros_pedidos[$item->numero_compra])){
                    $numeros_pedidos[$item->numero_compra] = [];
                }
                if(!isset($numeros_pedidos[$item->numero_compra][$item->cod_produto])){
                    $numeros_pedidos[$item->numero_compra][$item->cod_produto] = $item->cod_produto;
                }
            });
        });
        return $numeros_pedidos;
    }

    private function validaCompraChegou($produto_codigo, $numero_pedido){
        $ComprasNasajonObj = ComprasNasajon::query();
        $ComprasNasajonObj->where('cod_produto', $produto_codigo);
        $ComprasNasajonObj->where('numero_pedido', $numero_pedido);
        $ComprasNasajonObj = $ComprasNasajonObj->first();
        if(!empty($ComprasNasajonObj) && strtolower($ComprasNasajonObj->situacao) == 'liquidado'){
            return true;
        }
        return false;
    }

    private function validaCompraCancelou($produto_codigo, $numero_pedido){
        $ComprasNasajonObj = ComprasNasajon::query();
        $ComprasNasajonObj->where('cod_produto', $produto_codigo);
        $ComprasNasajonObj->where('numero_pedido', $numero_pedido);
        $ComprasNasajonObj = $ComprasNasajonObj->first();
        if(!empty($ComprasNasajonObj) && strtolower($ComprasNasajonObj->situacao) != 'aguardando documento' && strtolower($ComprasNasajonObj->situacao) != 'parcialmente liquidado'){
            return true;
        }
        return false;
    }

    private function buscaPedidosPortalDePedidosDeCompras($numero_pedido){
        $pedidos = PedidoPortal::with(['usuario_detalhes']);
        $pedidos->where('status_pedido', 8);
        $pedidos->whereHas('itens_pedido', function($query) use ($numero_pedido){
            $query->where('numero_compra', $numero_pedido);
        });
        return $pedidos->get();
    }
    private function buscaPedidosPortalDePedidosDeComprasEstoque($numero_pedido){
        $pedidos = PedidoPortal::with(['usuario_detalhes']);
        $pedidos->where('status_pedido', 8);
        $pedidos->whereHas('itens_pedido', function($query) use ($numero_pedido){
            $query->where('numero_compra', $numero_pedido);
            $query->whereHas('estoque', function($q){
                $q->where('estoque', '>', 0);
            });
        });
        return $pedidos->get();
    }

    private function transformArrayPedidos($pedidos){
        $pedidos_retorno = [];
        foreach($pedidos as $pedidoschegou){
            foreach($pedidoschegou as $pedido){
                if(!isset($pedidos_retorno[$pedido->id])){
                    $pedidos_retorno[$pedido->id] = $pedido;
                }
            }
        }
        unset($pedidos);
        $pedidos_retorno = array_values($pedidos_retorno);
        return $pedidos_retorno;
    }

    private function avisarPedidosChegou($pedidos){
        foreach($pedidos as $pedido){
            $pedido->dias_cancelar = $pedido->dias_cancelar + 1;
            $pedido->save();
            if(intval($pedido->dias_cancelar) >= 3 && intval($pedido->dias_cancelar) < 5){
                $this->emailCancelar($pedido);
            }
            elseif(intval($pedido->dias_cancelar) == 5){
                if (!is_null($pedido->aprovacao)){
                    $pedido->aprovacao->delete();
                }
                $pedido->deleted_by = 1;
                $pedido->save();
                $pedido->delete();

                $HistoricoPedidoObj = new HistoricoPedido();
                $HistoricoPedidoObj->pedido = $pedido->id;
                $HistoricoPedidoObj->antigo = '';
                $HistoricoPedidoObj->natureza = 'cancelamento';
                $HistoricoPedidoObj->novo = 'Pedido programa cancelado pelo sistema por ter chego o pedido';
                $HistoricoPedidoObj->created_by = 1;
                $HistoricoPedidoObj->save();
            }
        }
    }
    private function avisarPedidosCancelou($pedidos){
        foreach($pedidos as $pedido){
            // $pedido->dias_cancelar = $pedido->dias_cancelar + 1;
            // $pedido->save();
            // if(intval($pedido->dias_cancelar) == 5){
                $this->emailCancelar($pedido);
                if (!is_null($pedido->aprovacao)){
                    $pedido->aprovacao->delete();
                }
                $pedido->deleted_by = 1;
                $pedido->save();
                $pedido->delete();

                $HistoricoPedidoObj = new HistoricoPedido();
                $HistoricoPedidoObj->pedido = $pedido->id;
                $HistoricoPedidoObj->antigo = '';
                $HistoricoPedidoObj->natureza = 'cancelamento_programado';
                $HistoricoPedidoObj->novo = 'Pedido programado cancelado pelo sistema por não ter materia-prima';
                $HistoricoPedidoObj->created_by = 1;
                $HistoricoPedidoObj->save();
            // }
        }
    }

    private function emailCancelar(PedidoPortal $pedido){
        $EmailObj = new EmailController();
		$email_send = [];
        $email_send = [$pedido->usuario_detalhes->email];
        if(!empty($pedido->usuario_detalhes->supervisor)){
            $email_send[] = $pedido->usuario_detalhes->supervisor->email;
        }
        $variaveis = [
            'numero_pedido' => $pedido->id,
            'nome' => $pedido->usuario_detalhes->name,
            'codigo_produto' => $pedido->itens_pedido[0]->especificacoes->grupo.' - ' . $pedido->itens_pedido[0]->cod_produto,
            'pedido_compra' => $pedido->itens_pedido[0]->numero_compra
        ];
        $returnEmail = $EmailObj->sendEmailToken('00', "pedido_futuro_cancelado", $email_send, $variaveis);
    }

    public function cancelarPedidoProgramadoComAtrasadoSuperiorTrintaDias(){

        $data_atual = Carbon::now();

        $data_menos_30 = $data_atual->subDays(30);

        $query = PedidoPortal::with(['cliente', 'condicao_pagamento_detalhes', 'status_pedido_detalhes','itens_pedido.comprasNasajon','itens_pedido']);
        $query->where('status_pedido', 8);
        $query->where('data_previsao_entrega', '<', $data_menos_30);
        $result = $query->get();

        $pedidos_cancelado = [];
        $str_pedido_cancelado = "";
        foreach($result as $pedido){
            $index = 0;
            $tamanho = count($result);
            while(empty($pedidos_cancelado[$pedido->id]) && $index < $tamanho){
                if(!empty($pedido->itens_pedido[$index])){
                    $estoque = empty($pedido->itens_pedido[$index]->pedidoMaiorEstoqueDetalhes)? 0 : $pedido->itens_pedido[$index]->pedidoMaiorEstoqueDetalhes->estoque;
                    $compras = empty($pedido->itens_pedido[$index]->pedidoMaiorEstoqueDetalhes)? 0 : $pedido->itens_pedido[$index]->pedidoMaiorEstoqueDetalhes->compras_aberto;

                    if($pedido->itens_pedido[$index]->quantidade > ($estoque + $compras)){
                        $pedidos_cancelado[$pedido->id] = $pedido;
                        $str_pedido_cancelado = str_replace(".", ", ", $str_pedido_cancelado).$pedido->id.".";
                    }
                }
                $index++;
            }
        }

        $this->avisarPedidosCancelou($pedidos_cancelado);

        return $str_pedido_cancelado;
    }
}
