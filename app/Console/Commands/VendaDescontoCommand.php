<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\ProgramaMomentaneoController;

class VendaDescontoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'relatorio:pedido_venda_desconto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera Relatório de venda com desconto';

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
        $ProgramaMomentaneoController = new ProgramaMomentaneoController;
        $ProgramaMomentaneoController->VendaDesconto();
    }
}
