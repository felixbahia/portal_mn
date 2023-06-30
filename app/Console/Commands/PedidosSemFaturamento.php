<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PedidosSemFaturamentoController;

class PedidosSemFaturamento extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:nao_faturado';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pedidos sem faturamento acima de 15 dias';

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
        $pedidos_sem_faturar = new PedidosSemFaturamentoController();
        $estabelecimentos = returnEmpresasNasajonView();
        $codigo = [];
        foreach($estabelecimentos as $key => $value){
            $codigo = str_pad($key, 2, "0", STR_PAD_LEFT);
            if ($pedidos_sem_faturar->PedidosSemFaturar($codigo) != ''){
                echo 'Pedidos - estabelecimento '. $codigo.' enviados com sucesso!'. PHP_EOL;
            }
        }
    }
}
