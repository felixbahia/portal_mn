<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\DevolucaoNotaAprovacaoController;

class DevolucaoNotaCobrarNotaClienteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'devolucao:cobrar_nota_cliente';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cobra do cliente a nota para o processo de devolução';

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
        $DevolucaoNotaAprovacaoObj = new DevolucaoNotaAprovacaoController;
        $DevolucaoNotaAprovacaoObj->enviaEmailClienteNota();
    }
}
