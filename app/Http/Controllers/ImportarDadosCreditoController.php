<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Cliente;
use App\ClienteCredito;
use Carbon\Carbon;

class ImportarDadosCreditoController extends Controller
{
    public function importarDados(){
        ini_set('max_execution_time', 500);
        ini_set('memory_limit', '2024M');
        $regex = '/((([0-9]{2})|([0-9]))[-\/](([0-9]{2})|([0-9]))[-\/](([0-9]{4})|([0-9]{2})))/m';
        $clientes = Cliente::where('LIMCRED', '>', 0)->where('ATIVIDADE', '<', 51)->get();

        $codigosNaoColocadosLimite = [];

        foreach ($clientes as $key => $cliente) {
            $raiz_cnpj = $cliente->CGC_CPF;
            if(strlen(trim($raiz_cnpj)) == 18){
                $raiz_cnpj = substr($raiz_cnpj, 0, 10);
                if(empty($raiz_cnpj)){
                    $codigosNaoColocadosLimite[] = $cliente->CODCAD;
                    continue;
                }
            }
            $data = new Carbon('2018-01-01');
            $alerta = utf8_encode($cliente->ALERTA);
            preg_match_all($regex, $alerta, $matches, PREG_SET_ORDER, 0);
            if(count($matches) > 0){
                $data_regex = $matches[0][0];
                $data_transform = explode('-', $data_regex);
                if(count($data_transform) < 3){
                    $data_transform = explode('/', $data_regex);
                }
                if(count($data_transform) === 3){
                    try{
                        $data = new Carbon("{$data_transform[2]}-{$data_transform[1]}-{$data_transform[0]}");
                        if(strtotime($data) > strtotime(date('Y-m-d'))){
                            $data = new Carbon('2018-01-01');
                        }
                    }catch(\Exception $e){
                        //dd($data_transform, $data_transform, $alerta);
                    }
                }
                else{
                    $codigosNaoColocadosLimite[] = $cliente->CODCAD;
                }
            }
            $ClienteCreditoObj = ClienteCredito::where('raiz_cnpj', $raiz_cnpj)->first();
            if(is_null($ClienteCreditoObj)){
                $ClienteCreditoObj = new ClienteCredito();
                $ClienteCreditoObj->raiz_cnpj = $raiz_cnpj;
                $ClienteCreditoObj->data_atualizacao = $data;
                $ClienteCreditoObj->valor = $cliente->LIMCRED;
                $ClienteCreditoObj->created_by = 1;
                $ClienteCreditoObj->updated_by = 1;
                $ClienteCreditoObj->save();
            }else{
                $ClienteCreditoObj->data_atualizacao = $data;
                $ClienteCreditoObj->valor += $cliente->LIMCRED;
                $ClienteCreditoObj->created_by = 1;
                $ClienteCreditoObj->updated_by = 1;
                $ClienteCreditoObj->save();
            }
        }
        //var_dump($codigosNaoColocadosLimite);
    }
}
