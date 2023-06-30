<?php

namespace App\Console\Commands;

use App\Http\Controllers\HistoricoComprasPedidoMaiorEstoqueController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class HistoricoComprasPedidoMaiorEstoque extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gerar_base:historico_compras_pedidos_maior_estoque';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gerar base historico_compras e pedidos_maior_estoque';

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
        $HistoricoComprasPedidoMaiorEstoque = new HistoricoComprasPedidoMaiorEstoqueController();
        try {
            echo $HistoricoComprasPedidoMaiorEstoque->salvarDadosNasBases();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            echo $e->getMessage(), "\n";
        }
    }
}
