<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\AtualizacaoCron;
use Carbon\Carbon;

use App\Http\Controllers\CampanhasController;

class CampanhaVerificaProdutosImportadosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campanha:verifica_produtos_importados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica se os produtos importados estão na campanha e atualiza para a campanha correspondente';

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
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'campanha:verificar_importacao')->first();

        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            try{
                $campanhas_objetos = new CampanhasController();
                $campanhas_objetos->verificaImportacaoProduto();

                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false;
                $AtualizacaoCronObj->save();

                $AtualizacaoCronObj2 = AtualizacaoCron::select()->where('token', 'estoque')->first();
                $AtualizacaoCronObj2->atualizacao = Carbon::now();
                $AtualizacaoCronObj2->erro = '';
                $AtualizacaoCronObj2->alerta_erro = false;
                $AtualizacaoCronObj2->save();
            }catch (\Exception $e){
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();

                $AtualizacaoCronObj2 = AtualizacaoCron::select()->where('token', 'estoque')->first();
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();
            }
        }
    }
}
