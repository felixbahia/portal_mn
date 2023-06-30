<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PedidoUsarCreditoController;
use App\Http\Controllers\AprovacaoDePedidoController;

use Carbon\Carbon;

use App\AtualizacaoCron;

class PedidoUsarCreditoLiberacaoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:pedido_usar_credito_liberacao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Libera os pedidos que a condição é usar crédito e se o cliente tiver crédito no estabelecimento';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'pedido:pedido_usar_credito_liberacao')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();
            try{
                $pedidoUsarCreditoControllerObj = new PedidoUsarCreditoController();
                $pedidoUsarCreditoControllerObj->liberarPedidoUsarCredito();
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
