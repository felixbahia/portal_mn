<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\AtualizacaoCron;
use App\Http\Controllers\TituloInformacaoController;
use App\Http\Controllers\PedidoComprasAbertoTituloFuturoController;

class TituloInformacaoDiarioCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'titulo_informacao:gerar_diario';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Popular a tabela titulo_informacaos total';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'titulo_informacao:gerar_diario')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try {
                $clienteNovoController = new TituloInformacaoController();
                $clienteNovoController->gerarTituloAPagarInformacaoDiario();
                $clienteNovoController->gerarTituloInformacaoDiario();
                $clienteNovoController->gerarTituloInformacaoGrupoDiario();
                $clienteNovoController->gerarTituloInformacaoEquipe();
                $clienteNovoController->gerarTituloAPagarInformacaoTotal();
                $clienteNovoController->gerarTituloAPagarInformacaoTotalBI();
                $PedidoComprasAbertoTituloFuturoController = new PedidoComprasAbertoTituloFuturoController;
                $PedidoComprasAbertoTituloFuturoController->verificarNaoLancado();
                $PedidoComprasAbertoTituloFuturoController->atualizarPedidoComprasAbertoTituloFuturo();
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
