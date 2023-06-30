<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Carbon\Carbon;

use App\AtualizacaoCron;

use App\Http\Controllers\TituloModificacaoDataMultaController;

class ZerarMultaJurosDoTitulo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'titulo:zerar_multa_juros';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Zerar a multa e juros dos títulos do Santander.';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'titulo:zerar_multa_juros')->first();
        $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
        $AtualizacaoCronObj->save();

        try {
            $atualizar = new TituloModificacaoDataMultaController();
            $atualizar->atualizarZeraMultaJuros();
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
