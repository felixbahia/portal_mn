<?php

namespace App\Console\Commands;

use App\Http\Controllers\RenegociacaoTituloController;
use Illuminate\Console\Command;

class ReenviarAprovacaoRenegociacaoCliente extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reenviar:aprovacao_cliente {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reenviar aprovação da renegociação de título pelo cliente';

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
        $id = $this->argument('id');
        $renegociacaoTitulo = new RenegociacaoTituloController;
        try{
            $renegociacaoTitulo->reenviarAprovacaoRenegociacaoCliente($id);
        }catch (\Exception $e){
            echo $e->getMessage().PHP_EOL;

        }
    }    
}
