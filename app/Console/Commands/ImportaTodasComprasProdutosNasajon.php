<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\ImportacaoPrecoController; 

class ImportaTodasComprasProdutosNasajon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nasajon:importa_todas_compras_produtos_nasajon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa todas as compras de produto do Nasajon';

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
        $ImportacaoPrecoControllerObj = new ImportacaoPrecoController();
        $ImportacaoPrecoControllerObj->importaTodosValoresCompraProdutoNasajon();
    }
}
