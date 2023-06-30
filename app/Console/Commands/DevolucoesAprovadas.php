<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\DevolucoesAprovadasController;

class DevolucoesAprovadas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'devolucao:aprovada';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Devolução aprovada no dia';

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
        $DevolucoesAprovada = new DevolucoesAprovadasController();
        echo $DevolucoesAprovada->DevolucaoAprovadas().' notas aprovadas'.PHP_EOL;
    }
}
