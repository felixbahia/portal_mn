<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\PedidosPrePagoController;

class CriaTitulosPedidosPrePagosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:cria_titulos_pre_pagos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria títulos pré-pagos para pedidos pré-pagos que não criaram automaticamente';

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
        $pedidosPrePagoControllerObj = new PedidosPrePagoController;
        $pedidosPrePagoControllerObj->criaTitulosPrePagos();
    }
}
