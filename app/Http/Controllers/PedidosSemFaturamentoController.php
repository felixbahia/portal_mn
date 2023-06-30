<?php

namespace App\Http\Controllers;

use App\PedidosVendaNasajon;
use Carbon\Carbon;
use App\User;
use App\ClienteNasajon;

class PedidosSemFaturamentoController extends Controller
{
    public function PedidosSemFaturar($localEstoque){
        ini_set('memory_limmit', '2048M');

        $mailBody = '<table  border="1" cellpadding="0" cellspacing="0">'.
                    '<thead>'.
                        '<th style="width:10px"> Pedido </th>'.
                        '<th style="width:150px"> Cliente </th>'.
                        '<th style="width:150px"> Representante </th>'.
                        '<th style="width:150px"> Gerente </th>'.
                        '<th style="width:80px"> Emissão </th>'.
                        '<th style="width:150px"> Valor </th>'.
                        '<th style="width:20px"> Status </th>'.
                    '</thead>'.
                    '<tbody>';

                    $clientes_exluir = ClienteNasajon::select('id')
                    ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
                    ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
                    ->get();

                    $clientes_exluir = $clientes_exluir->pluck('id')->toArray();
                    
                    $pedidos = PedidosVendaNasajon::whereNotIn('situacao_descricao', ['Faturado', 'Cancelado'])
                    ->where('emissao', '<=', Carbon::now()->subDays(15))
                    ->where('estabelecimento_codigo', $localEstoque)
                    ->whereNotIn('cliente', $clientes_exluir)
                    ->where('rascunho', 'false')
                    ->where('operacao_codigo', 'PEDIDOVENDA')
                    ->orderBy('emissao', 'asc')
                    ->get(); 

                    foreach($pedidos->chunk(100) as $chunk){     
                        foreach($chunk as $pedido){
                            if(!empty($pedido->userPortal->responsavel)){
                                $usersObj = User::where('id', $pedido->userPortal->responsavel);
                                $user_id = $usersObj->first();
                                $vendedor = $pedido->userPortal->name;
                                $gerente = $user_id->name;
                            }elseif(!empty($pedido->userPortal->name)){
                                $vendedor = $pedido->userPortal->name;
                                $gerente = '';
                            }else{
                                $vendedor = '';
                                $gerente = '';
                            }
                            $mailBody .='<tr>'.
                                '<td>'.$pedido->numero.'</td>'.
                                '<td>'.$pedido->cliente_nomefantasia.'</td>'.
                                '<td>'.$vendedor.'</td>'.
                                '<td>'.$gerente.'</td>'.
                                '<td>'.parserData($pedido->emissao).'</td>'.
                                '<td>'.parserValor($pedido->valor).'</td>'.
                                '<td>'.$pedido->situacao_descricao.'</td>'.
                            '</tr>';
                        }
                    }

        $mailBody .= '</tbody>'.
             '</table>';
        $result = count($pedidos);
        if($result != ''){

            $emailControllerObj = new EmailController;
            $emailControllerObj->sendEmailToken($localEstoque, 'pedidos_sem_faturamento', [], ['pedidos' => $mailBody]);
        }
        return $result;
    }
}
