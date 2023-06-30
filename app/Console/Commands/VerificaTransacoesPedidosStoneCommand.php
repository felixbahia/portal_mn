<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Exception;

use App\AtualizacaoCron;

use Illuminate\Support\Carbon;
use App\Http\Controllers\StoneTransacaoController;
use App\Http\Controllers\EmailController;

class VerificaTransacoesPedidosStoneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stone:verifica_transacoes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza Status das Transações de pedidos presenciais por cartão de débito e crédito.';

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
        $atualiza_cron = AtualizacaoCron::select()->where('token', 'transacoes:stone')->first();

        $inicio_atualizacao = Carbon::parse($atualiza_cron->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($atualiza_cron->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $atualiza_cron->inicio_atualizacao = Carbon::now();
            $atualiza_cron->save();
    
            $transacao_stone = new StoneTransacaoController();
    
            try{
                $transacao_stone->verificarValorSeparacao();
                $transacao_stone->monitorarTransacoesAbertas();
                $transacao_stone->verificaEnvioPagamentosDuplicados();
                
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
