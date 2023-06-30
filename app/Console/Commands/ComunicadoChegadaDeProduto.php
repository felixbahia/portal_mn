<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\AtualizacaoCron;
use App\Http\Controllers\ComunicadoChegadaDeProdutoController;
use Carbon\Carbon;


class ComunicadoChegadaDeProduto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comunicar:chegada_de_produto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar e-mail comunicando a chegada de produto para o pedido programado';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'chegada_de_produto')->first();

        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            $ComunicadoChegadaDeProdutoControllerObj = new ComunicadoChegadaDeProdutoController;

            try{
                $objeto = $ComunicadoChegadaDeProdutoControllerObj->comunicadoChegadaDeProduto();
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false;
                $AtualizacaoCronObj->save();
            }catch (\Exception $e) {
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();
            }
        }
    }
}
