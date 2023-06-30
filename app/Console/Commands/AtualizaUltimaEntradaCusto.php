<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\AtualizaUltimaEntradaCustoController;

class AtualizaUltimaEntradaCusto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'produto:atualiza_dados_ultimaentrada';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza dados da ultima entrada para o custo calculado';

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
        $AtualizaUltimaEntradaCustoControllerObj = new AtualizaUltimaEntradaCustoController;
        $AtualizaUltimaEntradaCustoControllerObj->atualizarDados();
    }
}
