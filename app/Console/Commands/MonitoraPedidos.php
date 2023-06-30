<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PedidosMonitoradoController;

class MonitoraPedidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedidos:monitorar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitoramento de Pedidos';

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
        $PedidosMonitoradoControllerObj = new PedidosMonitoradoController();
        $PedidosMonitoradoControllerObj->monitorar();
    }
}
