<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CenprotController;

class VerificacaoBaixaTituloCenprot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cenprot:verificacao_titulos_pagos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'verifica se os títulos que tiveram baixas, remove da cenprot';

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
        $cenprotControllerObj = new CenprotController();
        $cenprotControllerObj->verificacaoTitulosPagos();
    }
}
