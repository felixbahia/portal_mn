<?php

namespace App\Console\Commands;

use App\Http\Controllers\PedidosPrePagoController;

use Illuminate\Console\Command;

class BaixarTitulosPrePagosCanceladosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nasajon:baixar_pre_pagos_cancelados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancela pedidos pré-pagos vinculados a notas canceladas e devolve o saldo dos lançamentos.';

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
        $PedidosPrePagoControllerObj = new PedidosPrePagoController;
        $PedidosPrePagoControllerObj->cancelarPedidosPrePagosNotasCancelados();
    }
}
