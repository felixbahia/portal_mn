<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\VendasSantistaController;

class EmailExportacaoVendasSantista extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exportacao:notas_santista';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exporta todas as vendas da promoção Tecido Premiado e envia por e-mail';

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
        $vendasSantistaControllerObj->exportarCSV();
    }
}
