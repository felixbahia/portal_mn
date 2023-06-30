<?php

namespace App\Console\Commands;

use App\AtualizacaoCron;
use App\Http\Controllers\PedidoPortalController;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AuditoriaComissao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auditoria:comissao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fazer a verificação das comissões dos pedidos que fazem partes das campanhas e informar que estão incorretas';

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

        $atualizacao_cron = AtualizacaoCron::select()->where('token', 'auditoria:comissao')->first();
        
        $inicio_atualizacao = Carbon::parse($atualizacao_cron->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($atualizacao_cron->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $atualizacao_cron->inicio_atualizacao = $data_atual;
            $atualizacao_cron->save();

            try{
                $pedidoPortalControllerObj = new PedidoPortalController;
                $pedidoPortalControllerObj->auditoriaComissao();

                $atualizacao_cron->atualizacao = $data_atual;
                $atualizacao_cron->save();
            }catch(\Exception $e){

                $data_atual = Carbon::now();

                $atualizacao_cron->erro = $e->getMessage();
                $atualizacao_cron->alerta_erro = true;
                $atualizacao_cron->atualizacao = $data_atual;
                $atualizacao_cron->save();
            }
        }
    }
}
