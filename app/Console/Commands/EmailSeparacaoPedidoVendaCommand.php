<?php

namespace App\Console\Commands;

use Exception;
use Carbon\Carbon;
use App\AtualizacaoCron;
use Illuminate\Console\Command;
use App\Http\Controllers\SituacaoPedidoVendaController;

class EmailSeparacaoPedidoVendaCommand extends Command
{
      /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:pedido_venda_liberado_separacao  {dias_anterior=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia emails com pedidos Liberados Com 48 horas antes e 24 horas antes';

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
        $dias_anterior = $this->argument('dias_anterior');

        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'pedido_venda_liberado_separacao')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try {
                $situacaoPedidoVenda = new SituacaoPedidoVendaController();
                if(!empty($dias_anterior)){
                    $situacaoPedidoVenda->pedidoLiberadoSeparacao($dias_anterior);
                }else{
                    
                    $situacaoPedidoVenda->pedidoLiberadoSeparacao(1);
                    $situacaoPedidoVenda->pedidoLiberadoSeparacao(2);
                }
            
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
