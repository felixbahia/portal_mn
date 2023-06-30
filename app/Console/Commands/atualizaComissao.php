<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\AprovacaoDePedidoController;

class atualizaComissao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prologos:atualiza_comissao {inicio_periodo} {fim_periodo} {estabelecimento} {batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $inicio_periodo = $this->argument('inicio_periodo');
        $fim_periodo = $this->argument('fim_periodo');
        $estabelecimento = $this->argument('estabelecimento');
        $batch = $this->argument('batch');

        AprovacaoDePedidoController::atualizaComissao($inicio_periodo, $fim_periodo, $estabelecimento, $batch);
    }
}
