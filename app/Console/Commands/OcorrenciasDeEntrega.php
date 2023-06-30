<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\OcorrenciasDeEntregaController;
use Carbon\Carbon;

use App\AtualizacaoCron;

class OcorrenciasDeEntrega extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ocorrencias:entregas {path_file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Integração EDI Transportadoras';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'ocorrencias:entregas')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            $path_file = $this->argument('path_file');
            $OcorrenciasDeEntrega = new OcorrenciasDeEntregaController();
            try {
                $quandidate = $OcorrenciasDeEntrega->SalvarDadosTransportadora($path_file);
                
                if($quandidate > 0){
                    echo $quandidate.' ocorrencias de entrega registradas com sucesso!'.PHP_EOL;
                }else{
                    echo 'erro'.PHP_EOL;
                }
                
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
