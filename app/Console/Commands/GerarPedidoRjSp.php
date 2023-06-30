<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\AtualizacaoCron;
use Carbon\Carbon;
use App\Http\Controllers\PedidoRjSpController;

class GerarPedidoRjSp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:gerar_rj_sp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gerar Pedidos de transferencia e programado para operação RJ SP';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'pedido_rj_sp')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            $resposta =  new PedidoRjSpController;

            try{
                $resposta->processarPedidos();
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false ;
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
