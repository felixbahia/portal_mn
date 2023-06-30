<?php

namespace App\Console\Commands;

use Exception;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Http\Controllers\ImportarNotasEntradasController;
use App\Http\Controllers\EmailController;

use App\AtualizacaoCron;

class ImportacaoNotasEntradas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importacao:notas_entradas {inicio_periodo=0} {fim_periodo=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importação de Notas Fiscais e CTEs de Entrada';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'importacao:notas_entradas')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            $retorno = new ImportarNotasEntradasController();

            $inicio_periodo = $this->argument('inicio_periodo');
            $fim_periodo = $this->argument('fim_periodo');
            
            if(empty($inicio_periodo)){
                $inicio_periodo = Carbon::now()->subDays(2);
            }else{
                $inicio_periodo = Carbon::parse($inicio_periodo);
            }

            if(empty($fim_periodo)){
                $fim_periodo = Carbon::now();
            }else{
                $fim_periodo = Carbon::parse($fim_periodo);
            }
            
            try{
                $contador = 0;
                $contador_fim = 3;
                
                while($inicio_periodo->lte($fim_periodo)){
                    $data_adicional = $this->argument('inicio_periodo');
                    $contador_fim --;
                    
                    if(empty($data_adicional)){
                        $data_adicional = Carbon::now()->subDays($contador_fim);
                    }else{
                        $data_adicional = Carbon::parse($data_adicional)->addDays($contador);
                    }

                    $retorno->verificarNotasCanceladasSemLancamento();
                    $retorno->importarNotas($inicio_periodo,$data_adicional);
                    $contador ++;
                    $inicio_periodo->addDays(1);
                }

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
