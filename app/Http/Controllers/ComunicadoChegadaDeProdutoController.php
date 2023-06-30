<?php

namespace App\Http\Controllers;

use App\PedidoPortal;
use Illuminate\Http\Request;

class ComunicadoChegadaDeProdutoController extends Controller
{
    public function comunicadoChegadaDeProduto(){
        ini_set('memory_limit','1024M');

        $PedidoPortal = PedidoPortal::with(['usuario_detalhes', 'usuario_detalhes.supervisor', 'cliente', 'itens_pedido',
        'itens_pedido.comprasNasajon' => function ($query){
            $query->whereIn('situacao_item', ['Liquidado', 'Cancelado']);
        }])
        ->whereHas('itens_pedido', function ($query){
            $query->whereNotNull('numero_compra');
        })
        ->where('status_pedido', 8)
        ->get();

        $estabelecimentos = returnEmpresasNasajonView();

        $pedidos = [];  

        foreach($PedidoPortal as $pedido){
            foreach($pedido->itens_pedido as $item){
                if(isset($item->comprasNasajon->situacao_item)){
                    $pedidoCompraVenda = $item->numero_compra.$pedido->id;
                    $codigo_representante = $pedido->usuario_detalhes->codigo_representante;
                    $codigo_supervidor = isset($pedido->usuario_detalhes->supervisor) ? $pedido->usuario_detalhes->supervisor->id : 1;
                    
                    if(!isset($pedidos[$codigo_supervidor][$codigo_representante][$pedidoCompraVenda])){
                        $pedidos[$codigo_supervidor][$codigo_representante][$pedidoCompraVenda] = [
                            'estabelecimento' => $estabelecimentos[$pedido->estabelecimento],
                            'pedido_venda' => $pedido->id,
                            'cliente' => $pedido->cliente->nome.' - '.$pedido->cliente->cpf_cnpj,
                            'pedido_compra' => $item->numero_compra,
                            'produto_codigo' => $item->cod_produto,
                            'status_compra' => $item->comprasNasajon->situacao_item,
                            'codigo_representante' => $pedido->usuario_detalhes->codigo_representante,
                            'email_representante' => $pedido->usuario_detalhes->email,
                            'email_supervisor' => isset($pedido->usuario_detalhes->supervisor) ? $pedido->usuario_detalhes->supervisor : 'mntecidos@mntecidos.com.br',
                            'nome_representante' => $pedido->usuario_detalhes->name,
                            'nome_supervisor' => isset($pedido->usuario_detalhes->supervisor) ? $pedido->usuario_detalhes->supervisor->name : 'Sistema',
                            'representante' => $pedido->usuario_detalhes->codigo_representante.' - '.$pedido->usuario_detalhes->name
                        ];
                    }
                }
            }
        }

        foreach($pedidos as $supervisor => $value){
            $mailBodySupervisor = '<table  border="1" cellpadding="0" cellspacing="0">'.
                '<thead>'.
                    '<th> Estabelecimento </th>'.
                    '<th> Pedido Venda </th>'.
                    '<th> Cliente </th>'.
                    '<th> Representante </th>'.
                    '<th> Pedido Compra </th>'.
                    '<th> Código Produto </th>'.
                    '<th> Status Compra </th>'.
                '</thead>'.
            '<tbody>';

            foreach($pedidos[$supervisor] as $subordinados => $dados){
                $mailBodyRepresentante = '<table  border="1" cellpadding="0" cellspacing="0">'.
                    '<thead>'.
                        '<th> Estabelecimento </th>'.
                        '<th> Pedido Venda </th>'.
                        '<th> Cliente </th>'.
                        '<th> Pedido Compra </th>'.
                        '<th> Código Produto </th>'.
                        '<th> Status Produto na Compra </th>'.
                    '</thead>'.
                '<tbody>';
                
                foreach($pedidos[$supervisor][$subordinados] as $pedido_representante){
                    $email_supervisor = $pedido_representante['email_supervisor'];
                    $email_representante = $pedido_representante['email_representante'];
                    $nome_representante = $pedido_representante['nome_representante'];
                    $nome_supervisor = $pedido_representante['nome_supervisor'];
                    
                    $mailBodySupervisor.='<tr>'.
                        '<td>'.$pedido_representante['estabelecimento'].'</td>'.
                        '<td>'.$pedido_representante['pedido_venda'].'</td>'.
                        '<td>'.$pedido_representante['cliente'].'</td>'.
                        '<td>'.$pedido_representante['representante'].'</td>'.
                        '<td>'.$pedido_representante['pedido_compra'].'</td>'.
                        '<td>'.$pedido_representante['produto_codigo'].'</td>'.
                        '<td>'.$pedido_representante['status_compra'].'</td>'.
                    '</tr>';

                    $mailBodyRepresentante.='<tr>'.
                        '<td>'.$pedido_representante['estabelecimento'].'</td>'.
                        '<td>'.$pedido_representante['pedido_venda'].'</td>'.
                        '<td>'.$pedido_representante['cliente'].'</td>'.
                        '<td>'.$pedido_representante['pedido_compra'].'</td>'.
                        '<td>'.$pedido_representante['produto_codigo'].'</td>'.
                        '<td>'.$pedido_representante['status_compra'].'</td>'.
                    '</tr>';
                }

                $mailBodyRepresentante .= '</tbody>'.
                    '</table>';
                    $this->enviarComunicado($email_representante, $mailBodyRepresentante, $nome_representante);
            } 

            $mailBodySupervisor .= '</tbody>'.
            '</table>';
            $this->enviarComunicado($email_supervisor, $mailBodySupervisor, $nome_supervisor);
        }
    }

    private function enviarComunicado($email, $mailBody, $nome){
        $emailControllerObj = new EmailController;
        $emailControllerObj->sendEmailToken('00', 'chegada_de_produto', [$email], ['pedidos' => $mailBody, 'nome' => $nome]);
    }
}
