<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\PedidoUsarCreditoController;

use Carbon\Carbon;

use App\AtualizacaoCron;

class MonitorarPedidoUsarCredito extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:monitorar_usar_credito';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitorar pedido bloqueado por usar Credito';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'pedido:monitorar_usar_credito')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();
            try{
                $AtualizacaoCronObj2 = AtualizacaoCron::select()->where('token', 'pedido:pedido_usar_credito_liberacao')->first();
                $AtualizacaoCronObj2->atualizacao = Carbon::now();
                $AtualizacaoCronObj2->erro = '';
                $AtualizacaoCronObj2->alerta_erro = false;
                $AtualizacaoCronObj2->save();

                $pedidoUsarCreditoControllerObj = new PedidoUsarCreditoController();
                $pedidoUsarCreditoControllerObj->monitoramentoPedido();

                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false;
                $AtualizacaoCronObj->save();
            }catch (Exception $e) {
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();
            }
        }
    }
}
