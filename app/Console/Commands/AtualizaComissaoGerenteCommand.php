<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\TitulosAbertosNasajonViradaController;

class AtualizaComissaoGerenteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nasajon:atualiza_comissao_devolucao';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza a comissão das devoluções conforme descrito na tabela';

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
        $TitulosAbertosNasajonViradaControllerObj = new TitulosAbertosNasajonViradaController;
        $TitulosAbertosNasajonViradaControllerObj->atualizaComissoesTitulosAbertosVirada();
    }
}
