<?php

namespace App\Console\Commands;

use Exception;

use App\AtualizacaoCron;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use App\Http\Controllers\EmailTitulosController;

class EmailTitulosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nasajon:email_titulos_abertos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia emails com instruções para baixar A DaNFe e boletos';

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
       

        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'email_titulos_abertos')->first();

        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try {
                $emailTitulosController = new EmailTitulosController();
                $emailTitulosController->enviarEmailDocumentos();
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
