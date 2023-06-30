<?php

namespace App\Console\Commands;

use App\Http\Controllers\ComissaoTituloProtestadoController;

use Illuminate\Console\Command;

class ImportarTitulosProtestados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nasajon:importar_titulos_protestados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa títulos que foram protestados, apaga os que já foram quitados.';

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
        //
        $comissaoTituloProtestadoController = new ComissaoTituloProtestadoController;
        $comissaoTituloProtestadoController->importacaoTitulosProtestados();
        $comissaoTituloProtestadoController->apagaTitulosPagos();
        
    }
}
