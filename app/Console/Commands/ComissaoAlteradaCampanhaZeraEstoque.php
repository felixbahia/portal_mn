<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ComissaoDuplicatasController;
use App\Http\Controllers\CampanhasController;
use Carbon\Carbon;
use App\AtualizacaoCron;

class ComissaoAlteradaCampanhaZeraEstoque extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comissao:alterar_comissao_zera_estoque';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Altera a comissão, pela linha do produto Campanha Zera Estoque';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'comissao:alterar_comissao_zera_estoque')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);


            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try {
                $comissaoDuplicatasControllerObj = new ComissaoDuplicatasController();
                //$comissaoDuplicatasControllerObj->atualizarComissaoZerada();
                $campanhasControllerObj = new CampanhasController;
                $campanhasControllerObj->CorrecaoComissao();
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
