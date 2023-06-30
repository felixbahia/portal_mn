<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\RenegociacaoTituloController;

class cancelamentoRenegociacaoTituloInativoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'renegociacao_titulo:cancela_inativos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Faz cancelamento de renegociações que estão inativos por mais de 7 dias ou vencidos';

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
        $renegociacaoTituloControllerObj = new RenegociacaoTituloController;
        $renegociacaoTituloControllerObj->cancelamentoPorInatividade();
    }
}
