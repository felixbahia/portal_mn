<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ImportarNotasAssociacaoController;

class ImportarNotasAssociadasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importar:notas_associadas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar Notas Associadas de Devolução de Entrada do XML';

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
        $importarNotasAssociadas = new ImportarNotasAssociacaoController;
        echo $importarNotasAssociadas->importarNotasAssociadas().PHP_EOL;
    }
}
