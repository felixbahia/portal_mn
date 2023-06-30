<?php

namespace App\Console\Commands;

use Exception;
use App\AtualizacaoCron;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use App\Http\Controllers\PesquisaSatisfacaoClienteController;

class EnviaEmailPesquisaSatisfacao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:pesquisia_satisfacao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia E-mail de pesquisa de satisfação para clientes com entregas efetuadas.';

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
        $atualiza_cron = AtualizacaoCron::select()->where('token', 'email:pesquisia_satisfacao')->first();

        $inicio_atualizacao = Carbon::parse($atualiza_cron->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($atualiza_cron->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $atualiza_cron->inicio_atualizacao = Carbon::now();
            $atualiza_cron->save();

            $pesquisa = new PesquisaSatisfacaoClienteController;

            try{
                $pesquisa->verificaNotasSaida();
                $pesquisa->reenviarPesquisaNaoRespondida();

                $atualiza_cron->atualizacao = Carbon::now();
                $atualiza_cron->erro = '';
                $atualiza_cron->alerta_erro = false;
                $atualiza_cron->save();
            }catch (Exception $e) {
                $atualiza_cron->erro = $e->getMessage();
                $atualiza_cron->alerta_erro = true;
                $atualiza_cron->atualizacao = Carbon::now();
                $atualiza_cron->save();
            }
        }
    }
}
