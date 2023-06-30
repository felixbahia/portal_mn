<?php

namespace App\Console\Commands;

use Exception;
use Carbon\Carbon;
use App\AtualizacaoCron;
use Illuminate\Console\Command;
use App\Http\Controllers\SituacaoPedidoVendaController;

class EmailSituacaoPedidoVendaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:pedido_programado_preco';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia emails com pedidos programados que tiveram preço alterado';

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
       

        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'pedido_venda_programado_alterou_preco')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try {
                $situacaoPedidoVenda = new SituacaoPedidoVendaController();
                $situacaoPedidoVenda->pedidoProgramadoComPrecoAlterado();
                $AtualizacaoCronObj->atualizacao =  Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false;
                $AtualizacaoCronObj->save();
            } catch (Exception $e) {
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();
            }
        }
    }
}
