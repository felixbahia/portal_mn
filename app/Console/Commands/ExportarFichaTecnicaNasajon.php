<?php

namespace App\Console\Commands;
use App\AtualizacaoCron;
use Carbon\Carbon;

use Illuminate\Console\Command;
use App\Http\Controllers\IntegracaoFichaTecnicaProdutoNasajon;
use Exception;

class ExportarFichaTecnicaNasajon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processar:ficha_tecnica_nasajon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processar Ficha Técnica para Nasajon';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'ficha_tecnica_nasajon')->first();

        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            $resposta =  new IntegracaoFichaTecnicaProdutoNasajon;

            try{
                $resposta->exportarFichaTecnica();
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
