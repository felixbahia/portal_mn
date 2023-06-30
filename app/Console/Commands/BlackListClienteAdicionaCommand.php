<?php

namespace App\Console\Commands;


use App\AtualizacaoCron;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use App\Http\Controllers\ClienteBlackListController;

class BlackListClienteAdicionaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cliente:black_list_cliente_add {inicio_periodo=0} {fim_periodo=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'adiciona cliente na black list: descriptionAdicionar clientes com títulos baixados na conta " Perda Concretizada "  na blacklist, desde que eles nao estejam cadastrados.  O motivo DESISTÊNCIA DA COBRANÇA JUDICIAL';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'black_list_cliente_add')->first();
  
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();
            $inicio_periodo = $this->argument('inicio_periodo');
            $fim_periodo = $this->argument('fim_periodo');
            
            if(empty($inicio_periodo)){
                $inicio_periodo = Carbon::now()->startOfMonth()->subMonths(2)->setTime(0,0,0);
            }else{
                $inicio_periodo = Carbon::parse($inicio_periodo)->setTime(0,0,0);
            }

            if(empty($fim_periodo)){
                $fim_periodo = Carbon::now()->lastOfMonth()->setTime(23,59,59);
            }else{
                $fim_periodo = Carbon::parse($fim_periodo)->setTime(23,59,59);
            }

            try {
                $clienteBlackListController = new ClienteBlackListController();
                $clienteBlackListController->addCliente($inicio_periodo, $fim_periodo);
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
