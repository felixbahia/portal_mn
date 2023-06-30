<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\AprovacaoDePedidoController;

class verificaComissaoPrologos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prologos:verifica_comissao {inicio_periodo} {fim_periodo} {--estabelecimento=} {--representante=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica comissões com erro na importação';

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
        $estabelecimento = $this->option('estabelecimento');
        $representante = $this->option('representante');

        AprovacaoDePedidoController::verificaComissaoPedido($inicio_periodo, $fim_periodo, $estabelecimento, $representante);
    }
}
