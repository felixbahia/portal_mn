<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Exception;
use App\AtualizacaoCron;
use Carbon\Carbon;
use App\Http\Controllers\ImportacaoPrecoController;

class CustoGerencialZerado extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'exportar:produtos_sem_gerencial';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Enviar e-mail com produto sem custo gerencial e com estoque';

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
    $AtualizacaoCronObj = AtualizacaoCron::where('token', 'produtos_sem_gerencial')->first();

    $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
    $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);

    if($inicio_atualizacao->lte($final_atualizacao)){
      $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
      $AtualizacaoCronObj->save();
      $ImportacaoPrecoControllerObj = new ImportacaoPrecoController();
      try {
        try{
          $objeto = $ImportacaoPrecoControllerObj->produtosSemBook();
        } catch (Exception $e) {
          $AtualizacaoCronObj->erro = $e->getMessage();
          $AtualizacaoCronObj->alerta_erro = true;
          $AtualizacaoCronObj->atualizacao = Carbon::now();
          $AtualizacaoCronObj->save();
        }
        try{
          $objeto = $ImportacaoPrecoControllerObj->produtosSemGerencial();
        } catch (Exception $e) {
          $AtualizacaoCronObj->erro = $e->getMessage();
          $AtualizacaoCronObj->alerta_erro = true;
          $AtualizacaoCronObj->atualizacao = Carbon::now();
          $AtualizacaoCronObj->save();
        }
        try{
          $objeto = $ImportacaoPrecoControllerObj->produtosGerencialManorContabil();
        } catch (Exception $e) {
          $AtualizacaoCronObj->erro = $e->getMessage();
          $AtualizacaoCronObj->alerta_erro = true;
          $AtualizacaoCronObj->atualizacao = Carbon::now();
          $AtualizacaoCronObj->save();
        }
        try{
          $objeto = $ImportacaoPrecoControllerObj->produtosSemContabil();
        } catch (Exception $e) {
          $AtualizacaoCronObj->erro = $e->getMessage();
          $AtualizacaoCronObj->alerta_erro = true;
          $AtualizacaoCronObj->atualizacao = Carbon::now();
          $AtualizacaoCronObj->save();
        }
        try{
          $objeto = $ImportacaoPrecoControllerObj->produtosCustoContabilMenorCustoGerencial();

          $AtualizacaoCronObj->atualizacao = Carbon::now();
          $AtualizacaoCronObj->erro = '';
          $AtualizacaoCronObj->alerta_erro = false;
          $AtualizacaoCronObj->save();
        } catch (Exception $e) {
          $AtualizacaoCronObj->erro = $e->getMessage();
          $AtualizacaoCronObj->alerta_erro = true;
          $AtualizacaoCronObj->atualizacao = Carbon::now();
          $AtualizacaoCronObj->save();
        }
      } catch (Exception $e) {
        $AtualizacaoCronObj->erro = $e->getMessage();
        $AtualizacaoCronObj->alerta_erro = true;
        $AtualizacaoCronObj->atualizacao = Carbon::now();
        $AtualizacaoCronObj->save();
      }
    }
  }
}
