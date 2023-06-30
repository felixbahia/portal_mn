<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Http\Controllers\StoneTransacaoController;

use App\AtualizacaoCron;

class IntegrarTransacoesPresenciaisStone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stone:integrar_nasajon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Integrar Transações Prsenciais com API Nasajon.';

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
        
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'integrar_transacoes_presenciais_nasajon')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);
        $data_atual = Carbon::now();
        $diferenca = $inicio_atualizacao->diffInMinutes($data_atual);
        
        if($inicio_atualizacao->lte($final_atualizacao) || $diferenca > 120){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            $transacoes = new StoneTransacaoController();
            try {
                $resultado_automatico = $transacoes->verificaTransacoesAutomaticas();
                    
                $resultado_manual = $transacoes->integrarTransacoesManuaisNasajon();

                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false;
                $AtualizacaoCronObj->save();
                
            } catch (\Exception $e) {
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();
            }
        }
    }
}
