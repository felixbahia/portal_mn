<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ImportDadosPrologos;

class VerificaDadosFaturamento extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importacao:validacao_faturamento';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Varifica as importações do dia se houve cancelamento';

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
        $ImportDadosPrologosObj = new ImportDadosPrologos();
        // $files = $ImportDadosPrologosObj->checkFaturamentoCanceladosDiaPrologos();
        $files = $ImportDadosPrologosObj->checkFaturamentoCanceladosDiaNasajon();
        //$files = $ImportDadosPrologosObj->checkFaturamentoCanceladosCompletoPrologos();
    }
}
