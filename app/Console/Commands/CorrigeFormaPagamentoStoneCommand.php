<?php

namespace App\Console\Commands;

use Exception;

use App\AtualizacaoCron;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

use App\Http\Controllers\StoneTransacaoController;
use App\Http\Controllers\EmailController;

class CorrigeFormaPagamentoStoneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stone:corrige_forma_pagamento';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige a forma de pagamento Stone';

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
        
        $atualiza_cron = AtualizacaoCron::select()->where('token', 'stone:corrige_forma_pagamento')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $atualiza_cron->inicio_atualizacao = Carbon::now();
            $atualiza_cron->save();

            $transacao_stone = new StoneTransacaoController();

            try{
                $transacao_stone->corrigeFormaPagamento();

                $atualiza_cron->atualizacao = Carbon::now();
                $atualiza_cron->erro = '';
                $atualiza_cron->alerta_erro = false;
                $atualiza_cron->save();
            }catch (Exception $e) {
                $EmailObj = new EmailController();
                $email_send = [];
                $variaveis = [
                    'erro' => $e->getMessage()
                ];
                $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
                
                $atualiza_cron->erro = $e->getMessage();
                $atualiza_cron->alerta_erro = true;
                $atualiza_cron->atualizacao = Carbon::now();
                $atualiza_cron->save();
            }
        }
    }
}
