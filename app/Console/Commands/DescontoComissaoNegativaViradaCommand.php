<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\LancamentoDebCredVendedorController;

class DescontoComissaoNegativaViradaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comissao:desconto_comissao_negativa_virada';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera saldo negativo para o próximo mês caso a comissão do vendedor no mês seja menor que zero na virada.';

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
        $lancamentoDebCredVendedorControllerObj = new LancamentoDebCredVendedorController;
        $lancamentoDebCredVendedorControllerObj->lancarDebitoComissaoNegativa();
    }
}
