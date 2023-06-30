<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\CancelarPedidosProgramadosController;

use Carbon\Carbon;

use App\AtualizacaoCron;

class CancelarPedidoProgramadoAcimaTrintaDiasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:cancelar_pedido_programado_atraso_30';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancelar pedidos programados com vencimento maior que 30 e que não tiver estoque e nem compras que de cobertura.';

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

        $data_atual = Carbon::now();

        $atualizacao_cron = AtualizacaoCron::select()->where('token', 'cancelamento_pedido_programado_atrasado')->first();
        
        $inicio_atualizacao = Carbon::parse($atualizacao_cron->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($atualizacao_cron->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $atualizacao_cron->inicio_atualizacao = $data_atual;
            $atualizacao_cron->save();

            try{
                $CancelarPedidosProgramadosControllerObj = new CancelarPedidosProgramadosController();
                echo $CancelarPedidosProgramadosControllerObj->cancelarPedidoProgramadoComAtrasadoSuperiorTrintaDias();
            
                $data_atual = Carbon::now();

                $atualizacao_cron->atualizacao = $data_atual;
                $atualizacao_cron->save();

            }catch (Exception $e) {

                $data_atual_finalizado = Carbon::now();

                $atualizacao_cron = AtualizacaoCron::select()->where('token', 'cancelamento_pedido_programado_atrasado')->first();
                $atualizacao_cron->erro = $e->getMessage();
                $atualizacao_cron->alerta_erro = true;
                $atualizacao_cron->atualizacao = $data_atual_finalizado;
                $atualizacao_cron->save();

                echo $e->getMessage(), "\n";
            }
        }
    }
}
