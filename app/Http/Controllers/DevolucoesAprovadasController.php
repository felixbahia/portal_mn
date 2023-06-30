<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\DevolucaoNota;

class DevolucoesAprovadasController extends Controller
{
    public function DevolucaoAprovadas(){
        ini_set('memory_limmit', '2048M');

        $mailBody = '<table  border="1" cellpadding="0" cellspacing="0">'.
                    '<thead>'.
                        '<th style="width:150px"> Cliente </th>'.
                        '<th style="width:150px"> Nota Remessa</th>'.
                        '<th style="width:80px"> Tipo Devolução </th>'.
                        '<th style="width:100px"> Valor </th>'.
                        '<th style="width:150px"> Nr Nota Devolução  </th>'.
                    '</thead>'.
                    '<tbody>';

                    $dataInicial = Carbon::today();
                    $dataFinal = Carbon::now();

                    $DevolucaoNotaOjb = DevolucaoNota::with('nota_remessa_nasajon')
                    ->select('cliente_razao_social', 'valor_parcial','valor', 'nota_remessa', 'nota_fiscal')
                    ->whereHas('aprovadores', function($query) use ($dataInicial, $dataFinal){
                        $query->where('devolucao_nota_status_id', 2)
                        ->whereBetween('updated_at', [$dataInicial, $dataFinal]);
                    });

                    $DevolucaoNota = $DevolucaoNotaOjb->get();

                    foreach($DevolucaoNota as $devolucao){
                        $nota_remessa = '';
                        if(!empty($devolucao->nota_remessa_nasajon->numero)){
                            $nota_remessa = $devolucao->nota_remessa_nasajon->numero;
                        }
                        if($devolucao->valor_parcial === true){
                            $tipo_devolucao = 'PARCIAL';
                        }else{
                            $tipo_devolucao = 'TOTAL';
                        }
                        $mailBody .='<tr>'.
                            '<td>'.$devolucao->cliente_razao_social.'</td>'.
                            '<td>'.$nota_remessa.'</td>'.
                            '<td>'.$tipo_devolucao.'</td>'.
                            '<td>'.parserValor($devolucao->valor).'</td>'.
                            '<td>'.$devolucao->nota_fiscal.'</td>'.
                        '</tr>';
                    }
                    
    $mailBody .= '</tbody>'.
             '</table>';
    
        $emailControllerObj = new EmailController;
        $emailControllerObj->sendEmailToken('00', 'devolucao_aprovada', [], ['devolucoes' => $mailBody]);

        return  $DevolucaoNotaOjb->count();
    }
}
