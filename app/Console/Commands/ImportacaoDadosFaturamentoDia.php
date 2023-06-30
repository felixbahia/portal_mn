<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ImportDadosPrologos;

use Carbon\Carbon;

use App\AtualizacaoCron;

class ImportacaoDadosFaturamentoDia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importacao:faturamento_dia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importação de faturamento para o portal';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'importacao:faturamento_dia')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        $data_atual = Carbon::now();

        $diferenca = $inicio_atualizacao->diffInMinutes($data_atual);

        if($inicio_atualizacao->lte($final_atualizacao) || $diferenca > 30){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();
            try{
                $ImportDadosPrologosObj = new ImportDadosPrologos();
                $files = $ImportDadosPrologosObj->FaturamentoDia();
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false;
                $AtualizacaoCronObj->save();
            }catch (Exception $e) {
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();
            }
        }
    }
}
