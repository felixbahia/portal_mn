<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Carbon\Carbon;

use App\AtualizacaoCron;

use App\Http\Controllers\ContaContabilController;

class ContaContabilAtualizarSaldoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conta_contabil:atualizar_saldo {inicio_periodo=0} {fim_periodo=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualizar o Saldo da Conta Contábil, por padrão do mês atual e dois meses anteriores, por um data personalizada.';

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

        $atualizacao_cron = AtualizacaoCron::select()->where('token', 'conta_contabil_atualizar_saldo')->first();

        $inicio_atualizacao = Carbon::parse($atualizacao_cron->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($atualizacao_cron->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $atualizacao_cron->inicio_atualizacao = $data_atual;
            $atualizacao_cron->save();

            try{
                $inicio_periodo = $this->argument('inicio_periodo');
                $fim_periodo = $this->argument('fim_periodo');
                
                if(empty($inicio_periodo)){
                    $inicio_periodo = Carbon::now()->startOfMonth()->subMonths(2)->setTime(0,0,0);
                }else{
                    $inicio_periodo = Carbon::parse($inicio_periodo)->setTime(0,0,0);
                }
        
                if(empty($fim_periodo)){
                    $fim_periodo = Carbon::now()->lastOfMonth()->setTime(23,59,59);
                }else{
                    $fim_periodo = Carbon::parse($fim_periodo)->setTime(23,59,59);
                }

                $contaContabilControllerObj = new ContaContabilController;
                $contaContabilControllerObj->atualizarSaldo($inicio_periodo, $fim_periodo);

                $data_atual = Carbon::now();

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
