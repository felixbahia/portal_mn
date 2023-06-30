<?php

namespace App\Console\Commands;

use App\AtualizacaoCron;
use App\Http\Controllers\AssinaturaEletronicaClicksignController;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AtualizarDocumentoClicksign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'atualizar:documento_clicksign';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualização do status dos documentos enviados para Clicksign';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'atualizar:documento_clicksign')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        $data_atual = Carbon::now();

        $diferenca = $inicio_atualizacao->diffInMinutes($data_atual);

        if($inicio_atualizacao->lte($final_atualizacao) || $diferenca > 30){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            $AssinaturaEletronicaClicksign = new AssinaturaEletronicaClicksignController;
            $objeto = $AssinaturaEletronicaClicksign->atualizarDocumento();

            try{
                $objeto = $AssinaturaEletronicaClicksign->atualizarDocumento();
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->erro = null;
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
