<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Carbon\Carbon;

use App\AtualizacaoCron;

use App\Http\Controllers\TituloModificacaoDataMultaController;

class AlteracaoDataMultaTituloCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'titulo:alteracao_data_multa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alteração a Data da multa para o 11º depois do Vencimento';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'titulo:alteracao_data_multa')->first();
        $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
        $AtualizacaoCronObj->save();

        try {
            $atualizar = new TituloModificacaoDataMultaController();
            $atualizar->atualizarDataMulta();
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
