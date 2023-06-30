<?php

namespace App\Console\Commands;
use Exception;
use Carbon\Carbon;
use App\AtualizacaoCron;
use Illuminate\Console\Command;
use App\Http\Controllers\ClienteNetrinApiController;


class AtualizaClienteViaNetrinCommand extends Command
{
     /**
         * The name and signature of the console command.
         *
         * @var string
         */
   protected $signature = 'netrin:atualiza_cliente';

   /**
    * The console command description.
    *
    * @var string
    */
   protected $description = 'Atualiza Cadastro de clientes Nasajon.';

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
    $clienteNetrinApi = new ClienteNetrinApiController();
 
       $AtualizacaoCronObj = AtualizacaoCron::select()->where('token', 'netrin:atualiza_cliente')->first();

       $inicio_atualizacao = Carbon::parse($AtualizacaoCronObj->inicio_atualizacao);
       $final_atualizacao = Carbon::parse($AtualizacaoCronObj->atualizacao);
     
       if($inicio_atualizacao->lte($final_atualizacao)){
       
           $AtualizacaoCronObj->inicio_atualizacao = Carbon::now();
           $AtualizacaoCronObj->save();

           $clienteNetrinApi = new ClienteNetrinApiController();

           try{
               $clienteNetrinApi->atualizaClienteAtivoNetrin();
               $clienteNetrinApi->atualizaClienteNasajon();
               $AtualizacaoCronObj->atualizacao = Carbon::now();
               $AtualizacaoCronObj->erro = '';
               $AtualizacaoCronObj->alerta_erro = false ;
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
