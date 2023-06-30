<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\VendasSantistaController;

class ImportacaoVendasSantista extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nasajon:importa_vendas_santista';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa os itens da marca Santista vendidos para a base do portal';

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
        $vendasSantistaControllerObj = new VendasSantistaController;
        $vendasSantistaControllerObj->importacao();
        $vendasSantistaControllerObj->exportarCSV();
    }
}
