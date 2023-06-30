<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\TitulosAbertosNasajonViradaController;

class TitulosAbertosNasajonViradaSalvarCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nasajon:snapshot_titulos_abertos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa todos os títulos abertos ou vencidos a até 15 dias do Nasajon para o banco do portal';

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
        $TitulosAbertosNasajonViradaControllerObj->snapshotTitulosAbertos();
        $TitulosAbertosNasajonViradaControllerObj->duplicaTitulosDeVendedoresParaGerentes();
        $TitulosAbertosNasajonViradaControllerObj->atualizaComissoesTitulosAbertosVirada();
    }
}
