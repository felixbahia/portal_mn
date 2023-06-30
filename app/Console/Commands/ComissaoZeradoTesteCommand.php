<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ComissaoDuplicatasController;
use Carbon\Carbon;
use App\AtualizacaoCron;

class ComissaoZeradoTesteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comissao:teste_comissao_zera_estoque';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Altera a comissão, pela linha do produto Campanha Zera Estoque';

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
        $comissaoDuplicatasControllerObj = new ComissaoDuplicatasController();
        $comissaoDuplicatasControllerObj->testeZerado();
    }
}
