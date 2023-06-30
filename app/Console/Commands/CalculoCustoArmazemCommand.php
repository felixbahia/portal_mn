<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Console\Commands\Exception;
use App\AtualizacaoCron;
use Carbon\Carbon;
use App\Http\Controllers\CalculoCustoArmazemController;

class CalculoCustoArmazemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movimentacao:calculo_custo_medio_armazem {inicio_periodo=0} {fim_periodo=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcular custo medio por armazem: do valor do estoque  pelo valor unitario sem dedução de impostos da nota de remessa com cfop 2905 e 6905';

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
        //  


        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'calculo_custo_armazem')->first();

        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try {
                $calculoCustoArmazemController = new CalculoCustoArmazemController();
                $calculoCustoArmazemController->calculoCustoArmazem();
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
