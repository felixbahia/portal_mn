<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\ComprasNasajon;
use App\PedidoPortal;
use App\PedidoItemPortal;
use Carbon\Carbon;

use App\Http\Controllers\EmailController;

class AtualizacaoDadosProgramadoController extends Controller
{

    public function atualizarDadosPedidos(){

        $pedidosAtualidos = $this->atualizaDatasComprasDiferentes();
        // $pedidosSemCompra = $this->pedidosSemCompra();
        $pedidosSemCompra = [];
        // $pedidosSemSaldo = $this->pedidosDeCompraSemSaldo();
        $pedidosSemSaldo = [];

        $EmailObj = new EmailController();
        $email_send = [];
        $variaveis = [
            'tabela_pedidos' => $this->geraDadosAtualizados($pedidosAtualidos),
            'tabela_sem_compra' => $this->geraDadosSemCompra($pedidosSemCompra),
            'tabela_sem_saldo' => $this->geraDadosSemSaldo($pedidosSemSaldo),
        ];
        $variaveis = ['html_pedidos'=>$this->geracaoHtml($variaveis)];
        $returnEmail = $EmailObj->sendEmailToken('00', "altecao_pedidos_programados", $email_send, $variaveis);

    }

    private function atualizaDatasComprasDiferentes(){

        $ComprasNasajonObj = ComprasNasajon::query();
        $ComprasNasajonObj->whereIn('situacao', ['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado']);

        $PedidoPortalObj = PedidoPortal::with('itens_pedido')->
            where('status_pedido', 8);

        $pedido_compras = [];
        $numero_pedido_compras = [];
        $datas_pedidos = [];
        $PedidoPortalObj = $PedidoPortalObj->get();
        foreach($PedidoPortalObj as $pedido){
            foreach($pedido->itens_pedido as $item){
                if(empty($item->numero_compra)){
                    continue;
                }
                if(!isset($pedido_compras[$item->numero_compra.''.$item->cod_produto])){
                    $pedido_compras[$item->numero_compra.''.$item->cod_produto] = [];
                    $numero_pedido_compras[] = $item->numero_compra;
                    $datas_pedidos[$item->numero_compra.''.$item->cod_produto][$pedido->id] = $pedido->data_previsao_entrega;
                }
                $pedido_compras[$item->numero_compra.''.$item->cod_produto][] = $item;
            }
        }
        unset($PedidoPortalObj);
        $ComprasNasajonObj->whereIn("numero_pedido", $numero_pedido_compras);
        $pedidos_atualizar = [];
        foreach($ComprasNasajonObj->get() as $compra){
            if(isset($datas_pedidos[$compra->numero_pedido.''.$compra->cod_produto])){
                $data_compra = Carbon::parse($compra->previsao_entrega);

                $data_quinzena = $data_compra->format('Y_m');
                if($data_compra->format('d') <= 15){
                    $data_quinzena .= '_1';
                }
                else{
                    $data_quinzena .= '_2';
                }

                foreach($datas_pedidos[$compra->numero_pedido.''.$compra->cod_produto] as $pedido => $data){
                    $data_pedido = Carbon::parse($data);
                    $data_quinzena_pedido = $data_pedido->format('Y_m');
                    if($data_pedido->format('d') <= 15){
                        $data_quinzena_pedido .= '_1';
                    }
                    else{
                        $data_quinzena_pedido .= '_2';
                    }
                    if($data_quinzena != $data_quinzena_pedido){
                        foreach($pedido_compras[$compra->numero_pedido.''.$compra->cod_produto] as $pedido){
                            if(!isset($pedidos_atualizar[$pedido->id])){
                                $pedidos_atualizar[$pedido->pedido] = [
                                    'pedido' => $pedido->pedido,
                                    'data_atualizar' => $data_compra->format('Y-m-d'),
                                    'data_antiga' => $data_pedido
                                ];
                            }
                            else{
                                $pedidos_atualizar[$pedido->pedido]['data_atualizar'] = $data_compra->format('Y-m-d');
                                $pedidos_atualizar[$pedido->pedido]['data_antiga'] = $data_pedido;
                            }
                        }
                    }
                }
            }
        }
        unset($ComprasNasajonObj);
        unset($numero_pedido_compras);
        unset($pedido_compras);
        unset($datas_pedidos);
        foreach($pedidos_atualizar as $atualiza_pedido){
            $PedidoPortalObj = PedidoPortal::find($atualiza_pedido['pedido']);
            $PedidoPortalObj->data_previsao_entrega = $atualiza_pedido['data_atualizar'];
            $PedidoPortalObj->save();
        }

        return $pedidos_atualizar;
    }

    private function pedidosSemCompra(){

        $ComprasNasajonObj = ComprasNasajon::query();
        $PedidoPortalObj = PedidoPortal::with('itens_pedido')->
            where('status_pedido', 8)->
            whereNotIn('estabelecimento', [6]);

        $pedido_compras = [];
        $numero_pedido_compras = [];
        $PedidoPortalObj = $PedidoPortalObj->get();
        foreach($PedidoPortalObj as $pedido){
            foreach($pedido->itens_pedido as $item){
                if(!isset($pedido_compras[$item->numero_compra][$item->pedido])){
                    $pedido_compras[$item->numero_compra][$item->pedido] = $pedido;
                    $numero_pedido_compras[] = $item->numero_compra;
                }
            }
        }
        unset($PedidoPortalObj);

        $ComprasNasajonObj->whereIn("numero_pedido", $numero_pedido_compras);
        $pedidos_sem_compra = [];
        foreach($ComprasNasajonObj->get() as $compra){
            if(!in_array($compra->situacao, ['Aguardando Documento', 'Parcialmente Liquidado'])){
                foreach($pedido_compras[$compra->numero] as $pedido){
                    $pedidos_sem_compra[] = $pedido;
                }
            }
        }
        unset($ComprasNasajonObj);
        unset($pedido_compras);
        unset($numero_pedido_compras);
        
        return $pedidos_sem_compra; 
    }

    private function pedidosDeCompraSemSaldo(){

        $ComprasNasajonObj = ComprasNasajon::query();
        $ComprasNasajonObj->whereIn('situacao', ['Aguardando Documento', 'Parcialmente Liquidado']);
        $pedidos_compras = [];
        foreach($ComprasNasajonObj->get() as $pedido_compra){
            if(!isset($pedidos_compras[$pedido_compra->numero_pedido])){
                $pedidos_compras[$pedido_compra->numero_pedido] = [];
            }
            if(!isset($pedidos_compras[$pedido_compra->numero_pedido][$pedido_compra->cod_produto])){
                $pedidos_compras[$pedido_compra->numero_pedido][$pedido_compra->cod_produto] = [
                    'quantidade_pedidos' => 0,
                    'quantidade_compras' => 0
                ];
            }
            $pedidos_compras[$pedido_compra->numero_pedido][$pedido_compra->cod_produto]['quantidade_compras'] += $pedido_compra->quantidade_restante;
            $pedido = PedidoPortal::query()->
                where('status_pedido', 8)->
                where('estabelecimento', intval($pedido_compra->estabelecimento))->
                where('cod_produto', $pedido_compra->cod_produto)->
                where('numero_compra', $pedido_compra->numero_pedido)->
                leftJoin('pedido_item', 'pedido_item.pedido', '=', 'pedido.id');
            foreach ($pedido->get() as $pedidos) {
                $pedidos_compras[$pedido_compra->numero_pedido][$pedido_compra->cod_produto]['quantidade_pedidos'] += floatval($pedidos->quantidade);
            }
            unset($pedido);
        }
        unset($ComprasNasajonObj);
        $pedidos_sem_saldo = [];
        foreach($pedidos_compras as $numero_pedido => $pedido){
            foreach($pedido as $codigo_produto => $quantidades){
                if($quantidades['quantidade_pedidos'] > $quantidades['quantidade_compras']){
                    $pedidos_sem_saldo[] = [
                        'numero' => $numero_pedido,
                        'produto' => (string) $codigo_produto
                    ];
                }
            }
        }
        unset($pedidos_compras);
        return $pedidos_sem_saldo;
    }

    private function geraDadosAtualizados($pedidos){
        $tabela = '<ul>';
        foreach($pedidos as $dado){
            $tabela .= '<li>'.
                '<b>Pedido Portal: </b>'.$dado['pedido'].
                ' <b>Data antigo: </b>'. $dado['data_antiga'] .
                ' <b>Data novo: </b>'. $dado['data_atualizar'].
                '</li>';
        }
        $tabela .= '</ul>';
        return $tabela;
    }

    private function geraDadosSemCompra($pedidos){
        $tabela = '<ul>';
        foreach($pedidos as $pedido){
            $tabela .= '<li>'.
                '<b>Pedido Portal: </b>'.$pedido->id.
                '</li>';
        }
        $tabela .= '</ul>';
        return $tabela;
    }

    private function geraDadosSemSaldo($pedidos){
        $tabela = '<ul>';
        foreach($pedidos as $pedido){
            $tabela .= '<li>'.
                '<b>PCMN: </b>'.$pedido['numero'].
                ' <b>Codigo do produto: </b>'.$pedido['produto'].
                '</li>';
        }
        $tabela .= '</ul>';
        return $tabela;
    }
    private function geracaoHtml($variaveis){
        $html = '';
        foreach($variaveis as $key => $values){
            if($values !== '<ul></ul>'){
                if($key === 'tabela_pedidos'){
                    $html .= 'Abaixo segue os pedidos que foram atualizados:<br />';
                }
                if($key === 'tabela_sem_compra'){
                    $html .= 'Abaixo segue os pedidos que estão sem compras:<br />';
                }
                if($key === 'tabela_sem_saldo'){
                    $html .= 'Abaixo segue os pedidos de compras que estão sem saldo para poder atender os pedidos:<br />';
                }
                $html .= $values;
                $html .= '<br /><hr /><br />';
            }
        }

        return $html;
    }
}
