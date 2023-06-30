<?php

namespace App\Console\Commands;

use Exception;

use Carbon\Carbon;

use Illuminate\Console\Command;

use App\Http\Controllers\NotasImportadasEntradasController;

use App\AtualizacaoCron;

class VerificaCobrancaCteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importacao:verifica_cobranca_cte {inicio_periodo=0} {fim_periodo=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica os XML das CTEs se é cobrança.';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'importacao:verifica_cobranca_cte')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();
    
            $retorno = new NotasImportadasEntradasController();
    
            $inicio_periodo = $this->argument('inicio_periodo');
            $fim_periodo = $this->argument('fim_periodo');
            
            if(empty($inicio_periodo)){
                $inicio_periodo = Carbon::now()->subDays(30);
            }else{
                $inicio_periodo = Carbon::parse($inicio_periodo);
            }
    
            if(empty($fim_periodo)){
                $fim_periodo = Carbon::now();
            }else{
                $fim_periodo = Carbon::parse($fim_periodo);
            }
    
            try{
                $contador = 1;
                $contador_fim = 29;
                while($inicio_periodo->lessThan($fim_periodo)){
                    $data_adicional = $this->argument('inicio_periodo');
                    $contador_fim --;
                    if(empty($data_adicional)){
                        $data_adicional = Carbon::now()->subDays($contador_fim);
                    }else{
                        $data_adicional = Carbon::parse($data_adicional)->addDays($contador);
                    }
                    
                    $retorno->verificaCobrancaCte($inicio_periodo,$data_adicional);
                    $contador ++;
                    $inicio_periodo->addDays(1);
                }
    
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false ;
                $AtualizacaoCronObj->save();
            }catch (Exception $e) {
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();
            }
        }
        
    }
}
