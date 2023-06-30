<?php

namespace App\Console\Commands;

use Exception;
use Carbon\Carbon;
use App\AtualizacaoCron;
use Illuminate\Console\Command;
use App\Http\Controllers\OcorrenciasDeEntregaController;

class VerificarFaturasSemNotasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fatura:verifica_sem_nota_edi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar itens da fatura sem nota associada apra fazer associação.';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'verificar_faturas_sem_notas')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();
    
            $verificar_notas = new OcorrenciasDeEntregaController();
    
            try {
                $verificar_notas->verificaCobrancaSemNota();
    
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
