<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CenprotController;

class CenprotEnviarTituloCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cenprot:atualizacao_status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar titulos que estão com mais 31 dias vencido para CENPROT';

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
        $cenprotControllerOjb = new CenprotController();
        $cenprotControllerOjb->atualizarStatusDosTitulos();
    }
}
