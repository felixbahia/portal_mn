<?php

namespace App\Console\Commands;

use Exception;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Http\Controllers\ImportarNotasEntradasController;
use App\Http\Controllers\EmailController;

use App\AtualizacaoCron;

class ExtrairDuplicatasNfeEntradaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extrair:duplicatas_nfe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extrair duplicatas de 7 dias das NFe de entrada.';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'extrair:duplicatas_nfe')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            $retorno = new ImportarNotasEntradasController();
            
            try{
                $retorno->verificarDuplicatas();
                
                $retorno->verificaNotasLancadas();

                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false ;
                $AtualizacaoCronObj->save();
            }catch (Exception $e) {
                $emailControllerObj = new EmailController;

                $mail_result = $emailControllerObj->sendEmailToken('01', 'erro_api_multinotas', ['ti_contratos@tecidosmn.com.br'], ['erro' => $e->getMessage()]);
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();
            }
        }
    }
}
