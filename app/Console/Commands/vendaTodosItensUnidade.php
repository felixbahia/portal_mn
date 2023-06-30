<?php

namespace App\Console\Commands;

use App\Http\Controllers\VendeTodoEstoqueController;

use Illuminate\Console\Command;

use Carbon\Carbon;

class vendaTodosItensUnidade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedidos:vendaTodosItensUnidade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vende todos os itens das unidades 1 e 2 ';

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
        define('PRECISION', .01); 
        setlocale(LC_ALL, 'en-US.UTF-8');

        $agora = Carbon::now();

        $pedidoPortalController = new VendeTodoEstoqueController;
        
        $pedidoPortalController->vendeTodosItens(2);

        echo 'Tempo total das duas gerações: ' . $agora->diff(Carbon::now())->format('%H horas, %i minutos e %s segundos') . PHP_EOL;

    }
}
