<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PedidosPrePagoController;

class CancelarPrePago extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:cancelar_prepago';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancelar pedidos prepago se for cancelado';

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
        $PedidosPrePagoControllerObj->cancelarPrepagos();
    }
}
