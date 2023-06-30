<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\ControlePilotagemController;

class DescontoPilotagemRepresentante extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:desconto_pilotagem';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Desconto de pedidos de pilotagem da comissão';

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
        $ControlePilotagemControllerObj = new ControlePilotagemController();
        $ControlePilotagemControllerObj->descontoPilotagem();
    }
}
