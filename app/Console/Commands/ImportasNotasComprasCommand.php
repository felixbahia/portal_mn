<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Exception;

use Carbon\Carbon;

use App\AtualizacaoCron;

use App\Http\Controllers\ImportarNotasComprasController;

class ImportasNotasComprasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importacao:notas_compras {inicio_periodo=0} {fim_periodo=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa notas de Compras ou Industrialização.';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'importacao:notas_compras')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            $inicio_periodo = $this->argument('inicio_periodo');
            $fim_periodo = $this->argument('fim_periodo');

            if(empty($inicio_periodo)){
                $inicio_periodo = Carbon::now()->subDays(4);
            }else{
                $inicio_periodo = Carbon::parse($inicio_periodo);
            }

            if(empty($fim_periodo)){
                $fim_periodo = Carbon::now();
            }else{
                $fim_periodo = Carbon::parse($fim_periodo);
            }
            
            $importar_compras = new ImportarNotasComprasController();

            try{
                $importar_compras->importar($inicio_periodo,$fim_periodo);

                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false;
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
