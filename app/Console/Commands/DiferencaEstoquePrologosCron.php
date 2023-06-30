<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\DiferencaEstoqueController;
use App\Mail\DiferencaEstoqueEmail;
use Illuminate\Support\Facades\Mail;

class DiferencaEstoquePrologosCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diferenca_estoque:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica as diferenças no estoque e envia por e-mail';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        $DiferencaEstoqueControllerObj = new DiferencaEstoqueController();
        $files = $DiferencaEstoqueControllerObj->createDadosDiferenca();
        $OjbEmailSend = new \stdClass();
        $OjbEmailSend->sender = 'victor.nogueira@tecidosmn.com.br';
        $OjbEmailSend->receiver = 'portal@tecidosmn.com.br';
        $OjbEmailSend->attachments_send = $files;
 
        Mail::to(["ti_contratos@tecidosmn.com.br"])->send(new DiferencaEstoqueEmail($OjbEmailSend));
    }
}
