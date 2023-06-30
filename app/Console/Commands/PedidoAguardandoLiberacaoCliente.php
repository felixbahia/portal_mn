<?php

namespace App\Console\Commands;

use App\AtualizacaoCron;
use App\Http\Controllers\AprovacaoDePedidoController;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PedidoAguardandoLiberacaoCliente extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'liberar:pedido_assinado_cliente';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Liberar o pedido assinado pelo o cliente';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'liberar:pedido_assinado_cliente')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try{
                
                $AprovacaoDePedidoController = new AprovacaoDePedidoController;
                $AprovacaoDePedidoController->pedidoAguardandoLiberacaoCLiente();

                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false;
                $AtualizacaoCronObj->save();
            }catch (\Exception $e) {
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();
            }
        }
    }
}
