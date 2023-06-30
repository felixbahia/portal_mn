<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\AprovacaoDePedidoController;


class verificaPedidosPrologos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prologos:verifica_pedidos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica os pedidos não integrados na Prologos';

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
        return AprovacaoDePedidoController::verificaPedidosPrologos();
    }
}
