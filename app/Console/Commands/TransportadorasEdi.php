<?php

namespace App\Console\Commands;

use App\Http\Controllers\TransportadorasEdiController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TransportadorasEdi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enviar:notas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envio de Notas Emitidas para Transportadoras EDI';

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
    public function handle()
    {
        $TransportadorasEdi = new TransportadorasEdiController();
        try {
            echo $TransportadorasEdi->enviarArquivoTxt();
        } catch (\Exception $e) {
            Log::error($e);
            echo 'A excecução não pode ser realizada: ',  $e->getMessage(), "\n";
        }
    }
}
