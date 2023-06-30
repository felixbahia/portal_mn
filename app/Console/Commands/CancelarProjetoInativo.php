<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\LancamentoProjetoController;

class CancelarProjetoInativo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projeto:cancelar_inativo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancelar Projetos que estão inativo por mais de 1 dia';

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
        LancamentoProjetoController::cancelarProjetoInativo();
    }
}
