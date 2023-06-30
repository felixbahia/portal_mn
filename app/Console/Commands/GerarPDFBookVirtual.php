<?php

namespace App\Console\Commands;

use App\AtualizacaoCron;
use App\Http\Controllers\BookVirtualExibicaoControllerNew;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GerarPDFBookVirtual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "gerar:pdf_book";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gerar PDF do book virtual por grupo';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'gerar_pdf_book')->first();

        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            $bookVirtualExibicaoController = new BookVirtualExibicaoControllerNew;

            try{
                $objeto = $bookVirtualExibicaoController->gerarPDFBookVirtual();
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
