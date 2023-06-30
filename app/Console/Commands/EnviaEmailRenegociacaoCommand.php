<?php

namespace App\Console\Commands;

use App\AtualizacaoCron;
use Illuminate\Console\Command;

use App\Http\Controllers\EmailTitulosController;
use Carbon\Carbon;

class EnviaEmailRenegociacaoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nasajon:emails_renegociacao {data_inicio} {data_fim}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'E-mails de renegociação';

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
        $atualiza_cron = AtualizacaoCron::select()->where('token', 'nasajon:emails_renegociacao')->first();

        $inicio_atualizacao = Carbon::parse($atualiza_cron->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($atualiza_cron->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $atualiza_cron->inicio_atualizacao = Carbon::now();
            $atualiza_cron->save();

            $fields = $this->arguments();
            $enviarEmailDocumentosRenegociado = new  EmailTitulosController;

            try{
                $enviarEmailDocumentosRenegociado->enviarEmailDocumentosRenegociados($fields);

                $atualiza_cron->atualizacao = Carbon::now();
                $atualiza_cron->erro = '';
                $atualiza_cron->alerta_erro = false;
                $atualiza_cron->save();
            }catch (\Exception $e) {
                $atualiza_cron->erro = $e->getMessage();
                $atualiza_cron->alerta_erro = true;
                $atualiza_cron->atualizacao = Carbon::now();
                $atualiza_cron->save();
            }
        }
    }
}
