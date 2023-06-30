<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\AtualizacaoCron;
use App\Http\Controllers\ClienteNovoController;

class ReenvioEmailClienteNovoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cliente:reenvio_usuario';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Será reenviado e-mail com usuario para todos os clientes que não assinaram o contrato';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'cliente:reenvio_usuario')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try {
                $clienteNovoController = new ClienteNovoController();
                $clienteNovoController->reenvioEmail();
                $AtualizacaoCronObj->atualizacao =  Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false;
                $AtualizacaoCronObj->save();
            } catch (Exception $e) {
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();
            }
        }
    }
}
