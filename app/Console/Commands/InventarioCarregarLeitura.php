<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\InventarioNovoController;

use App\AtualizacaoCron;

use Carbon\Carbon;

class InventarioCarregarLeitura extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventario:carregar_leitura';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Carregar os dados da leitura';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'inventario:carregar_leitura')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            echo Carbon::now()."\n";
            $inventario = new InventarioNovoController();
            try {
                $inventario->carregarDadosDeLeitura();
                $inventario->importarInventarioProduto();

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
            echo Carbon::now();
        }
    }
}
