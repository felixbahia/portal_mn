<?php

namespace App\Console\Commands;

use App\Http\Controllers\GiroDeEstoqueController;
use App\AtualizacaoCron;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;


class GiroDeEstoque extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gerar_base:consumo_estoque';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gerar base da dados para consulta de estoque baixo e giro alto';

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

        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'giro_estoque')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try {
                $GiroDeEstoque = new GiroDeEstoqueController();
                $GiroDeEstoque->salvarDadosBaseGiroDeEstoque();
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
