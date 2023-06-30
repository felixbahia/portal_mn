<?php

namespace App\Http\Controllers;

use App\Exports\ListagemTitulosAtrasadosSemJuducialExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ListagemTitulosAtrasadosSemJudicialController extends Controller
{
    private $emails = [
        'eliane@tecidosmn.com.br',
        'cobranca@tecidosmn.com.br',
        'alessandro@ragazzi.adv.br',
        'mauricio@ragazzi.adv.br',
        'ana.alves@ragazzi.adv.br',
        'thalita.lopes@ragazzi.adv.br'
    ];
    
    public function enviarTitulosJudicial(){
        Excel::store(new ListagemTitulosAtrasadosSemJuducialExport, 'titulos_vencido_sem_judicial.xls');
		$EmailObj = new EmailController();
	 	$returnEmail = $EmailObj->sendEmailToken('00', "titulos_vencidos_judicial", $this->emails, [], ['titulos_vencido_sem_judicial.xls' => ['as' => 'titulos_vencido_sem_judicial.xls', 'mime' => 'application/vnd.ms-excel']], []);
        Storage::delete('titulos_vencido_sem_judicial.xls');
    }
    
}
