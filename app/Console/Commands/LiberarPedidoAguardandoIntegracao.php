<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\AprovacaoDePedidoController; 

class LiberarPedidoAguardandoIntegracao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:liberar_pedido_data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Liberar pedido aguardando liberação';

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
        $AprovacaoDePedidoControllerObj = new AprovacaoDePedidoController();
        $AprovacaoDePedidoControllerObj->pedidoAguardandoLiberacao();
    }
}
