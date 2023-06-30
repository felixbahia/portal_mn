<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\PedidosPrePagoController;

class AtualizaPrePago extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:atualiza_valor_pre_pago';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza valor do título pré-pago dependendo do valor da nota';

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
        $pedidosPrePagoControllerObj->atualizaValoresNota();
    }
}
