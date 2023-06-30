<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotasNaoProcessadasController;

use Illuminate\Console\Command;

class EmailNotasNaoProcessadasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:notas_nao_processadas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia email com as notas não processadas';

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
        $notasNaoProcessadasControllerObj = new NotasNaoProcessadasController;
        $notasNaoProcessadasControllerObj->enviaEmail();
    }
}
