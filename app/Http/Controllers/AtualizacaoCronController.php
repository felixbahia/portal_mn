<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\AtualizacaoCron;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AtualizacaoCronController extends Controller
{
   
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AtualizacaoCron") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\AtualizacaoCron');
        $status =$this->situacao();
     
        return view('programs.atualizacao_cron.index')->with(['status' => $status]);
    }

    public function filtro(Request $request){
        
        $campo  = $request->only('descricao','data_inicio','data_fim', 'status');

        $atualizacaoCronObj = AtualizacaoCron::select();


        if(!empty($campo['descricao'])){
            $atualizacaoCronObj->where('descricao', 'ilike', '%'.$campo['descricao'].'%');
        }

        if(!empty($campo['data_inicio']) && !empty($campo['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $campo['data_inicio'])->setTime(0,0,0);
            $data_fim = Carbon::createFromFormat('d/m/Y', $campo['data_fim'])->setTime(23,59,59);
            $atualizacaoCronObj->whereBetween('inicio_atualizacao', [$data_inicio, $data_fim]);
        }else if(!empty($campos['data_inicio'])){
             $data_inicio = Carbon::CreateFromFormat("d/m/Y", $campos['data_inicio'])->setTime(0,0,0);;
             $atualizacaoCronObj->where('inicio_atualizacao', '>=', $data_inicio);
        }else if(!empty($campos['data_fim'])){
              $data_fim = Carbon::CreateFromFormat("d/m/Y", $campos['data_fim'])->setTime(23,59,59);;
              $atualizacaoCronObj->where('inicio_atualizacao', '<=', $data_fim);
        }
       
        if(!empty($campo['status'])){
           
               $atualizacaoCronObj->where('alerta_erro',$campo['status']);
        }
     
        $status =$this->situacao();

        $atualizacaoCrons = $atualizacaoCronObj->get();
        $saida = [];
     
        foreach($atualizacaoCrons as $atualizacaoCron){
            $tempo='';
            if(!empty($atualizacaoCron->inicio_atualizacao) && !empty($atualizacaoCron->atualizacao) ){
                    $dt_inicio = Carbon::parse($atualizacaoCron->inicio_atualizacao);
                    $dt_fim = Carbon::parse($atualizacaoCron->atualizacao);
                    $tempo = $dt_inicio->diffInMinutes($dt_fim);
            
            }

           $saida[] = [
                'id' => encrypt($atualizacaoCron->id),
                'descricao' => $atualizacaoCron->descricao,
                'status' => $status[$atualizacaoCron->alerta_erro],
                'inicio_atualizacao' => parserDataEHora($atualizacaoCron->inicio_atualizacao),
                'atualizacao' => parserDataEHora($atualizacaoCron->atualizacao),
                'tempo' => $tempo,
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $saida
        ];
        return response()->json($response, 200);
    }

    public function situacao(){

        $status = [
            "1" => 'Erro',
            "0" => 'Normal'
        ];

        return $status;
    }

    public function verificacao(){
        $atualizacaoCronObj = AtualizacaoCron::select();
        $atualizacaoCronObj->where('alerta_erro', true);
        $atualizacaoCrons = $atualizacaoCronObj->get();

        $mensagem = '';

        foreach($atualizacaoCrons as $atualizacaoCron){
            $mensagem .= 'token: '.$atualizacaoCron->token.'   Descrição: '.$atualizacaoCron->descricao.'   Data Inicial: '.parserDataEHora($atualizacaoCron->inicio_atualizacao).'<br><br><br>';
        }
        $this->emailDeErro($mensagem);
    }

    public function emailDeErro($corpo){
        $EmailObj = new EmailController();
            
        $variaveis = [
            'body' => $corpo,
        ];

        
        $retorno = $EmailObj->sendEmailToken('00', 'verificacao_cron', [], $variaveis);
    }

}
