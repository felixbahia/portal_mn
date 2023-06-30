<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\AtualizacaoCron;

use Carbon\Carbon;

use App\Http\Controllers\IcmsEstoqueArmazemController;


class AtualizarIcmsArmazemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'icms:movimentacao_armazem';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculo ICMS pelo  movimentação do Armazem.';

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

        $atualizacao_cron = AtualizacaoCron::select()->where('token', 'icms:movimentacao_armazem')->first();
        
        $inicio_atualizacao = Carbon::parse($atualizacao_cron->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($atualizacao_cron->atualizacao);


            $atualizacao_cron->inicio_atualizacao = $data_atual;
            $atualizacao_cron->save();

                $icmsEstoqueArmazemControllerObj = new IcmsEstoqueArmazemController;
                $icmsEstoqueArmazemControllerObj->atualizarMovimentacao20();
                $icmsEstoqueArmazemControllerObj->atualizarMovimentacoaIcms();
                $icmsEstoqueArmazemControllerObj->atualizarMovimentacoaValor();

                $atualizacao_cron->atualizacao = $data_atual;
                $atualizacao_cron->save();

    }
}