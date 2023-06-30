<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ImportacaoPrecoController;
use App\AtualizacaoCron;
use Carbon\Carbon;
use Exception;

class atualizaValoresComprasNoProduto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'produto:atualiza_valor_de_compra';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria ou atualiza todos os valores de compra das linhas da tabela de preço, segundo o que está na tabela de importação.';

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
    {$AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'produto:atualiza_valor_de_compra')->first();

        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try {
                    $importacaoPrecoController = new ImportacaoPrecoController;
                    $importacaoPrecoController->atualizaValorCompraProduto();
                } catch (Exception $e) {
                    $AtualizacaoCronObj->erro = $e->getMessage();
                    $AtualizacaoCronObj->alerta_erro = true;
                    $AtualizacaoCronObj->atualizacao = Carbon::now();
                    $AtualizacaoCronObj->save();
                }
            }
    }
}
