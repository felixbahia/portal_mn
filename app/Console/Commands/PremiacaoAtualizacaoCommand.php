<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\AtualizacaoCron;

use Carbon\Carbon;

use App\Http\Controllers\PremiacaoController;

class PremiacaoAtualizacaoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'premiacao:atualizacao {inicio=""} {fim=""}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualização dos dados da Premiação';

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
        
        $inicio = (!empty($this->argument('inicio'))) ? $this->argument('inicio') : null;
        $fim = (!empty($this->argument('fim'))) ? $this->argument('fim') : null;
        
        $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'premiacao:atualizacao')->first();
        
        $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
        $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

        if($inicio_atualizacao->lte($final_atualizacao)){
            $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
            $AtualizacaoCronObj->save();

            $premiacaoControllerObj = new PremiacaoController();

            try{
                $premiacaoControllerObj->atualizarPremiacao($inicio,$fim);

                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->erro = '';
                $AtualizacaoCronObj->alerta_erro = false ;
                $AtualizacaoCronObj->save();
            }catch (\Exception $e){
                $AtualizacaoCronObj->erro = $e->getMessage();
                $AtualizacaoCronObj->alerta_erro = true;
                $AtualizacaoCronObj->atualizacao = Carbon::now();
                $AtualizacaoCronObj->save();
            }
        }
    }
}
