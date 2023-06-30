<?php

namespace App\Console\Commands;

use Carbon\Carbon;

use App\AtualizacaoCron;

use App\Http\Controllers\PagamentoPixNasajonController;

use Illuminate\Console\Command;

class PagamentoPixAutomaticoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nasajon:criarTituloCreditoPix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica se existe pix em aberto e verifica se tem pedido aguardando pagamento do cliente que fez o pix, e gera um titulo de credito';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'nasajon:criarTituloCreditoPix')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);
        $data_atual = Carbon::now();
        $diferenca = $inicio_atualizacao->diffInMinutes($data_atual);

        if($inicio_atualizacao->lte($final_atualizacao) || $diferenca > 20){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try{
                
                $PagamentoPixNasajonControllerObj = new PagamentoPixNasajonController;
                $PagamentoPixNasajonControllerObj->criarTituloCreditoPix();

                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false;
                $AtualizacaoCronObj->save();
            }catch (\Exception $e) {
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();
            }
        }

    }
}
