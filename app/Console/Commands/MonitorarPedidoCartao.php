<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\PedidoPortal;
use App\Http\Controllers\PedidoCieloIntegracaoController;
use Carbon\Carbon;
use App\AtualizacaoCron;
use Exception;

class MonitorarPedidoCartao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:monitorar_cartao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitorar pedido bloqueado por cartão';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'pedido:monitorar_cartao')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();
            try{
                $PedidoCieloIntegracaoControllerObj = new PedidoCieloIntegracaoController(new PedidoPortal([]));
                $PedidoCieloIntegracaoControllerObj->monitoramentoPedido();
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
