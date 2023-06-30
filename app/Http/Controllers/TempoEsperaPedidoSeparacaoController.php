<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

use Auth;


use App\PedidoPortal;

class TempoEsperaPedidoSeparacaoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\TempoEsperaPedidoSeparacao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\TempoEsperaPedidoSeparacao');
     
        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);
        $situacao = $this->situacao();
        return view('programs.tempo_espera_pedido_separacao.index')->with(['estabelecimentos' => $estabelecimentos,'situacao'=>$situacao]);
    }

    public function filtro(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','2048M');
       
        $campos  = $request->only(['estabelecimento','numero_pedido','data_inicio','data_fim','situacao']);
   
        $data_inicio = Carbon::createFromFormat('d/m/Y', $campos['data_inicio'])->format('Y-m-d 00:00:00');
        $data_fim = Carbon::createFromFormat('d/m/Y', $campos['data_fim'])->format('Y-m-d 23:59:59');

        $pedido_portal = PedidoPortal::whereBetween('created_at',[$data_inicio,$data_fim])
        ->whereNotNull('pedido_gerado')
        ->whereNotIn('status_pedido',[5,7,6,2])
        ->with(['pedidoNasajon','tempoEspera','cliente']);
   
        if(!empty($campos['estabelecimento'])){
            $pedido_portal->where('estabelecimento',str_pad($campos['estabelecimento'],2,'0',STR_PAD_LEFT));
        }

        if(!empty($campos['numero_pedido'])){
            $pedido_portal->where('id',$campos['numero_pedido']);
        }
 
        $pedido_portal = $pedido_portal->get();
     
        $retorno = [];
        $estabelecimentos = returnEmpresasNasajonView();
        $sit =$campos['situacao'];
        $pedido_portal->each(function($query) use (&$retorno,$estabelecimentos, $sit){
            $total_sempo_separacao=0;
            if(!empty($query->tempoEspera->em_faturamento_nasajon)){
                    $cliente = (!empty($query->cliente->nome)) ? $query->cliente->nome.' '.$query->cliente->cpf_cnpj : '';
                    $status = '';

            
                    $inicio_separacao = Carbon::parse($query->tempoEspera->inicio_separacao_manual);
                    $final_separacao = Carbon::parse($query->tempoEspera->fim_separacao_nasajon);
                    $diferenca_separacao = $inicio_separacao->diffInMinutes($final_separacao);
                    $total_sempo_separacao = $total_sempo_separacao + $diferenca_separacao;

                   $inicio_separacao = Carbon::parse($query->tempoEspera->inicio_separacao_manual);
                    $final_separacao = Carbon::parse($query->tempoEspera->fim_separacao_nasajon);
                    $diferenca_separacao = $inicio_separacao->diffInMinutes($final_separacao);
                    $total_sempo_separacao = $total_sempo_separacao + $diferenca_separacao;
                    
                    $inicio_separacao = Carbon::parse($query->tempoEspera->fim_separacao_nasajon);
                    $final_separacao = Carbon::parse($query->tempoEspera->em_faturamento_nasajon);
                    $diferenca_separacao = $inicio_separacao->diffInMinutes($final_separacao);
                    $total_sempo_separacao = $total_sempo_separacao + $diferenca_separacao;


                    $inicio_separacao = Carbon::parse($query->tempoEspera->em_faturamento_nasajon);
                    $final_separacao = Carbon::parse($query->tempoEspera->updated_at);
                    $diferenca_separacao = $inicio_separacao->diffInMinutes($final_separacao);
                    $total_sempo_separacao = $total_sempo_separacao + $diferenca_separacao;

                    $inicio_separacao = Carbon::parse($query->tempoEspera->inicio_separacao_manual);
                    $final_separacao = Carbon::parse($query->tempoEspera->em_faturamento_nasajon);
                    $diferenca_separacao = $inicio_separacao->diffInMinutes($final_separacao);
                    $total_sempo_separacao = $total_sempo_separacao + $diferenca_separacao;
                
                    $inicio_estimativa = Carbon::parse($query->tempoEspera->inicio_separacao_manual);
                    $final_estimativa = Carbon::parse($query->tempoEspera->fim_separacao_manual);
                    $diferenca_estimativa = $inicio_estimativa->diffInMinutes($final_estimativa);
                 
                    if( $diferenca_separacao <= $diferenca_estimativa + 1){
                        $status = 'OK';
                    }else if($diferenca_separacao >= $diferenca_estimativa + 1 && $diferenca_separacao <= $diferenca_estimativa +5){
                        $status = 'ATENÇÃO';
                    }else if($diferenca_separacao > $diferenca_estimativa +5){
                        $status = 'ATRASO';
                    }
                   
                   if(!empty($sit)){
                        if( $sit == $status){
                            $retorno[] = [
                                'estabelecimento' => $estabelecimentos[(int)$query->estabelecimento],
                                'pedido' => $query->id,
                                'cliente' => $cliente,
                                'status' => $status,
                                'data_entrada' => (!empty($query->tempoEspera->inicio_separacao_manual)) ? parserDataEHora($query->tempoEspera->inicio_separacao_manual) : '',
                                'data_estimada' => (!empty($query->tempoEspera->fim_separacao_manual)) ? parserDataEHora($query->tempoEspera->fim_separacao_manual) : '',
                                'data_separacao' =>  (!empty($query->tempoEspera->fim_separacao_nasajon )) ? parserDataEHora($query->tempoEspera->fim_separacao_nasajon ) : '',
                                'data_faturamento' =>  (!empty($query->tempoEspera->em_faturamento_nasajon)) ? parserDataEHora($query->tempoEspera->em_faturamento_nasajon) : '',
                                'data_retirada' => (!empty($query->tempoEspera->updated_at)) ? parserDataEHora($query->tempoEspera->updated_at) : '',
                                'tempo_total' => (!empty($total_sempo_separacao)) ? $total_sempo_separacao : '',
                            ];
                        }

                   }else{
                      
                        $retorno[] = [
                            'estabelecimento' => $estabelecimentos[(int)$query->estabelecimento],
                            'pedido' => $query->id,
                            'cliente' => $cliente,
                            'status' => $status,
                            'data_entrada' => (!empty($query->tempoEspera->inicio_separacao_manual)) ? parserDataEHora($query->tempoEspera->inicio_separacao_manual) : '',
                            'data_estimada' => (!empty($query->tempoEspera->fim_separacao_manual)) ? parserDataEHora($query->tempoEspera->fim_separacao_manual) : '',
                            'data_separacao' =>  (!empty($query->tempoEspera->fim_separacao_nasajon )) ? parserDataEHora($query->tempoEspera->fim_separacao_nasajon ) : '',
                            'data_faturamento' =>  (!empty($query->tempoEspera->em_faturamento_nasajon)) ? parserDataEHora($query->tempoEspera->em_faturamento_nasajon) : '',
                            'data_retirada' => (!empty($query->tempoEspera->updated_at)) ? parserDataEHora($query->tempoEspera->updated_at) : '',
                            'tempo_total' => (!empty($total_sempo_separacao)) ? $total_sempo_separacao : '',
                        ];
                }

             
             }
        });

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'retorno'=> $retorno
            ]
        ],200);
    }

    public function situacao(){

        $status_separacao = [
            "OK" => 'OK',
            "ATENÇÃO" => 'ATENÇÃO',
            "ATRASO" => 'ATRASO',
    
        ];

        return $status_separacao;
    }
}
