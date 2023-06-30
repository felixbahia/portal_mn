<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\AprovacaoDePedidoController;

class ValidacaoPedidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:validacao {pedido} {--queue=default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Valida Pedido para aprovação';

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
        $pedido = $this->argument('pedido');
        if(empty($pedido)){
            $this->error('Pedido não informado');
        }
        $AprovacaoDePedidoControllerObj = new AprovacaoDePedidoController();
        $AprovacaoDePedidoControllerObj->pedido = $pedido;
        if($AprovacaoDePedidoControllerObj->checkPedido()){
            $AprovacaoDePedidoControllerObj->aprovacaoPedido();
        }else{
            $this->error('Pedido não encontrado ou já validado');
        }
    }
}
