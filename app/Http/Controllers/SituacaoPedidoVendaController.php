<?php

namespace App\Http\Controllers;

use App\PrecosLog;
use Carbon\Carbon;
use App\PedidoPortal;
use App\PedidoItemPortal;
use Illuminate\Http\Request;

class SituacaoPedidoVendaController extends Controller
{



  public function pedidoProgramadoComPrecoAlterado(){

          ini_set('memory_limit','1024M');

          $data_inicio = Carbon::now()->setTime(00,00,00);

          $data_fim = Carbon::now()->setTime(23,59,59);
  
          $precosLogHoje = PrecosLog::select('codigo_produto','preco_real_antigo','preco_real_novo','preco_dolar_antigo','preco_dolar_novo')
          ->whereBetween("created_at", [$data_inicio, $data_fim])
          ->get();
          $produtos_aletrado =$precosLogHoje->pluck('codigo_produto')->filter()->toArray();

          $PedidoPortal = PedidoPortal::with(['usuario_detalhes', 'usuario_detalhes.supervisor', 'cliente', 'itens_pedido.especificacoes','itens_pedido'
          => function ($query)  use ($produtos_aletrado){
            $query->whereIn('cod_produto',$produtos_aletrado);
          }])
          ->where('status_pedido', 8)
          ->where('pedido_futuro',true)
          ->get();

          $estabelecimentos = returnEmpresasNasajonView();
       
          $pedidos = [];  

          foreach($PedidoPortal as $pedido){
              foreach($pedido->itens_pedido as $item){
                                
                      $codigo_representante = $pedido->usuario_detalhes->codigo_representante;
                      $codigo_supervidor = isset($pedido->usuario_detalhes->supervisor) ? $pedido->usuario_detalhes->supervisor->id : 1;
                      $pedidoVenda = $pedido->id;
                      $preco_logs = $precosLogHoje->firstWhere('codigo_produto',  $item->cod_produto);
                
                      if($pedido->estabelecimento =='03'){
                        $preco_anterior = $preco_logs->preco_dolar_antigo;
                        $preco_atual = $preco_logs->preco_dolar_novo;
                      }else{
                        $preco_anterior = $preco_logs->preco_real_antigo;
                        $preco_atual = $preco_logs->preco_real_novo;
                      }
         
                      if(!isset($pedidos[$codigo_supervidor][$codigo_representante][$pedidoVenda])){
          
                          $pedidos[$codigo_supervidor][$codigo_representante] [$pedidoVenda] = [
                              'estabelecimento' => $estabelecimentos[$pedido->estabelecimento],
                              'pedido_venda' => $pedido->id,
                              'cliente' => $pedido->cliente->nome,
                              'produto_codigo' => $item->cod_produto,
                              'descricao' => $item->especificacoes->descricao,
                              'preco_pedido' => $item->preco_unitario,
                              'preco_anterior' =>$preco_anterior,
                              'preco_atual' => $preco_atual,
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


          foreach($pedidos as $supervisor => $value){
              $mailBodySupervisor =  '<table  border="1" cellpadding="0" cellspacing="0">'.
                  '<thead>'.
                  '<th> Gerente </th>'.
                  '<th> Pedido Venda </th>'.
                  '<th> Cliente </th>'.
                  '<th> Código Produto </th>'.
                  '<th> Descrição </th>'.
                  '<th> Preço Pedido </th>'.
                  '<th> Preço Anterior </th>'.
                  '<th> Preço Atual </th>'.
                  '</thead>'.
              '<tbody>';
          
              foreach($pedidos[$supervisor] as $subordinados => $dados){
                  $mailBodyRepresentante = '<table  border="1" cellpadding="0" cellspacing="0">'.
                      '<thead>'.
                          '<th> Representante </th>'.
                          '<th> Pedido Venda </th>'.
                          '<th> Cliente </th>'.
                          '<th> Código Produto </th>'.
                          '<th> Descrição </th>'.
                          '<th> Preço Pedido </th>'.
                          '<th> Preço Anterior </th>'.
                          '<th> Preço Atual </th>'.
                  
                      '</thead>'.
                  '<tbody>';

                  foreach($pedidos[$supervisor][$subordinados] as $pedido_representante){
            
                      $email_supervisor = $pedido_representante['email_supervisor'];
                      $email_representante = $pedido_representante['email_representante'];
                      $nome_representante = $pedido_representante['nome_representante'];
                      $nome_supervisor = $pedido_representante['nome_supervisor'];

                    $mailBodySupervisor.='<tr>'.
                          '<td>'.$pedido_representante['nome_supervisor'].'</td>'.
                          '<td>'.$pedido_representante['pedido_venda'].'</td>'.
                          '<td>'.$pedido_representante['cliente'].'</td>'.
                          '<td>'.$pedido_representante['produto_codigo'].'</td>'.
                          '<td>'.$pedido_representante['descricao'].'</td>'.
                          '<td style= "text-align: right">'.parserValor($pedido_representante['preco_pedido']).'</td>'.
                          '<td style= "text-align: right">'.parserValor($pedido_representante['preco_anterior']).'</td>'.
                          '<td style= "text-align: right">'.parserValor($pedido_representante['preco_atual']).'</td>'.

                      '</tr>';
              
                      $mailBodyRepresentante.='<tr>'.
                          '<td>'.$pedido_representante['nome_representante'].'</td>'.
                          '<td>'.$pedido_representante['pedido_venda'].'</td>'.
                          '<td>'.$pedido_representante['cliente'].'</td>'.
                          '<td>'.$pedido_representante['produto_codigo'].'</td>'.
                          '<td>'.$pedido_representante['descricao'].'</td>'.
                          '<td style= "text-align: right">'.parserValor($pedido_representante['preco_pedido']).'</td>'.
                          '<td style= "text-align: right">'.parserValor($pedido_representante['preco_anterior']).'</td>'.
                          '<td style= "text-align: right">'.parserValor($pedido_representante['preco_atual']).'</td>'.
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
        $emailControllerObj->sendEmailToken('00', 'pedido_venda_programado_alterou_preco', [$email], ['pedidos' => $mailBody, 'nome' => $nome]);
    }


    public function pedidoLiberadoSeparacao($dias_anterior){

        ini_set('memory_limit','1024M');

        $data_inicio = Carbon::now()->setTime(00,00,00);
        $data_fim = Carbon::now()->setTime(23,59,59);
        $data_fim->addDays($dias_anterior);
    
        $PedidoPortal = PedidoPortal::with(['usuario_detalhes.unidadeNegocioMetaUserUltimo.detalhesUnidadeNegocioMeta.detalhesUnidadeNegocio',  'cliente'
        ])
        ->where('status_pedido', 10)
        ->whereBetween('data_previsao_entrega', [$data_inicio, $data_fim])
        ->get();
     
        $estabelecimentos = returnEmpresasNasajonView();
     
        $pedidos = [];  
   
        foreach($PedidoPortal as $pedido){
                                     
                    $codigo_representante = $pedido->usuario_detalhes->codigo_representante;
                    $codigo_supervidor = isset($pedido->usuario_detalhes->supervisor) ? $pedido->usuario_detalhes->supervisor->id : 1;
                    $pedidoVenda = $pedido->id;

       
                    if(!isset($pedidos[$codigo_supervidor][$codigo_representante][$pedidoVenda])){

                        switch($pedido->usuario_detalhes->unidadeNegocioMetaUserUltimo->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->users_id){
                            case 49:
                                $email_totais = ['vanessa.polacchi@tecidosmn.com.br', $pedido->usuario_detalhes->email];
                                break;
                            case 38:
                                $email_totais = ['leide.paixao@tecidosmn.com.br', $pedido->usuario_detalhes->email];
                                break;
                            case 28:
                                $email_totais = ['tatiane.barbosa@tecidosmn.com.br', 'deise.souza@tecidosmn.com.br', $pedido->usuario_detalhes->email];
                                break;
                            case 69:
                                $email_totais = ['rosangela.souza@tecidosmn.com.br', $pedido->usuario_detalhes->email];
                                break;
                            default:
                                $email_totais = [$pedido->usuario_detalhes->email];
                                break;
                        }
        
                        $pedidos[$codigo_supervidor][$codigo_representante] [$pedidoVenda] = [
                            'estabelecimento' => $estabelecimentos[$pedido->estabelecimento],
                            'pedido_venda' => $pedido->id,
                            'cliente' => $pedido->cliente->nome,
                            'codigo_representante' => $pedido->usuario_detalhes->codigo_representante,
                            'email_representante' => $email_totais,
                         
                            'nome_representante' => $pedido->usuario_detalhes->name,
                           
                            'representante' => $pedido->usuario_detalhes->codigo_representante.' - '.$pedido->usuario_detalhes->name,
                            'data_liberacao' => parserData($pedido->data_previsao_entrega),
                        ];
                    }
                  
            }


        foreach($pedidos as $supervisor => $value){
        
            foreach($pedidos[$supervisor] as $subordinados => $dados){
                $mailBodyRepresentante = '<table  border="1" cellpadding="0" cellspacing="0">'.
                    '<thead>'.
                        '<th> Representante </th>'.
                        '<th> Pedido Venda </th>'.
                        '<th> Data Liberação  </th>'.
                        '<th> Cliente </th>'.
               
                    '</thead>'.
                '<tbody>';

                foreach($pedidos[$supervisor][$subordinados] as $pedido_representante){
          
         
                    $email_representante = $pedido_representante['email_representante'];
                    $nome_representante = $pedido_representante['nome_representante'];
           
                  
                    $mailBodyRepresentante.='<tr>'.
                        '<td>'.$pedido_representante['nome_representante'].'</td>'.
                        '<td><center>'.$pedido_representante['pedido_venda'].'</center></td>'.
                        '<td><center>'.$pedido_representante['data_liberacao'].'</center></td>'.
                        '<td>'.$pedido_representante['cliente'].'</td>'.
                    '</tr>';
                }
                $mailBodyRepresentante .= '</tbody>'.
                    '</table>';
 
                    $this->enviarComunicadoSeparacao($email_representante, $mailBodyRepresentante, $nome_representante);
            } 

 
 
        }
}
private function enviarComunicadoSeparacao($email, $mailBody, $nome){
  
      $emailControllerObj = new EmailController;
    $emailControllerObj->sendEmailToken('00', 'pedido_venda_liberado_separacao', $email, ['pedidos' => $mailBody, 'nome' => $nome]);
}

}
