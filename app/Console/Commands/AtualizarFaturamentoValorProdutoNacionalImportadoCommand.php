<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\AtualizacaoCron;

use Carbon\Carbon;

use App\Http\Controllers\AcompanhamentoOrcamentarioController;

class AtualizarFaturamentoValorProdutoNacionalImportadoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faturamento:valor_nacional_importado';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Separa o valor pedido faturado, em valores nacional e importado, conforme itens nacionais e importado que há no pedido.';

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

        $atualizacao_cron = AtualizacaoCron::select()->where('token', 'faturamento_valor_nacional_importado')->first();
        
        $inicio_atualizacao = Carbon::parse($atualizacao_cron->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($atualizacao_cron->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $atualizacao_cron->inicio_atualizacao = $data_atual;
            $atualizacao_cron->save();

            try{
                $acompanhamentoOrcamentarioControllerObj = new AcompanhamentoOrcamentarioController;
                $acompanhamentoOrcamentarioControllerObj->atualizarFaturamentoValorProdutoNacionalImportado();

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
