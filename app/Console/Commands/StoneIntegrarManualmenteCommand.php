<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Carbon\Carbon;

use App\AtualizacaoCron;
use App\PedidoPortal;
use App\StoneTransacoesPedido;

use App\Http\Controllers\StoneTransacaoController;
use App\Http\Controllers\AprovacaoDePedidoController;

use Exception;

class StoneIntegrarManualmenteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stone:integrar_pedido {pedido} {verifica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Integra pedidos presencial cartão com a Stone.';

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
        $id = (int)$this->argument('pedido');

        $verifica = $this->argument('verifica');

        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'comissao:integrar_pedido_stone')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try{
                $pedido = PedidoPortal::with(['pagamentosStone'])->where('cartao',true)->where('presencial',true)->find($id);

                if(!isset($pedido->id)){
                    $AtualizacaoCronObj->erro = 'Pedido '.$id.' não encontrado';
                    $AtualizacaoCronObj->alerta_erro = true;
                    $AtualizacaoCronObj->atualizacao = Carbon::now();
                    $AtualizacaoCronObj->save();
                    echo('Pedido '.$id.' não encontrado');
                    return;
                }

                if($verifica == 'sim'){
                    $pedido->status_pedido = 9;
                    $pedido->save();

                    $integrar_nadajon = new AprovacaoDePedidoController;
                    $integrar_nadajon->processaIntegracaoPedidoNasajon($pedido,null);

                    $pedido->status_pedido = 3;
                    $pedido->save();

                    $integrar = new StoneTransacaoController;
                    $integrar->desbloquearPedido($pedido->pagamentosStone[0]->id);
                }else{
                    $integrar = new StoneTransacaoController;
                    $integrar->enviarPedidoConnect20($pedido);
                }

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
