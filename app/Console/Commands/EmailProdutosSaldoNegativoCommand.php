<?php

namespace App\Console\Commands;

use App\Http\Controllers\ProdutosSaldoNegativoNasajonController;

use Illuminate\Console\Command;

class EmailProdutosSaldoNegativoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:produtos_saldo_negativo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia um e-mail informando quais produtos se encontram com saldo negativo';

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
        $produtosSaldoNegativoNasajonControllerObj = new ProdutosSaldoNegativoNasajonController;
        $produtosSaldoNegativoNasajonControllerObj->enviaEmail();
    }
}
