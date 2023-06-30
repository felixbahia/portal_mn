<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\LogColetor;
use App\RomaneioNasajon;
use App\IntesNotasNasajon;
use App\MotivoDivergencia;
use App\LogColetorRomaneio;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\NotasEntradasNasajon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RomaneioEntradaController extends Controller
{
    //

    public function index(Request $request){
        
        if(Auth::user()->hasPermissionTo("programas App\RomaneioEntrada") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\RomaneioEntrada');


        $estabelecimentos = returnEmpresasNasajonView();
        $situacoes = $this->situacao();

         $filteredArray = Arr::where($estabelecimentos, function ($value, $key) {
                return  $key >=3 &&  $key <=20;
            });

        return view("programs.romaneio_entrada.index")->with(['estabelecimentos' => $filteredArray,'situacoes' => $situacoes ]);
    }

    public function filtro(Request $request){

        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $estabelecimentos = returnTodasEmpresasView();
        $campos = $request->only('estabelecimento','data_inicio','data_fim','numero_nota','situacao');

        if(!empty($campos['estabelecimento'])){
           $estabelecimento =str_pad($campos['estabelecimento'], 2, "0", STR_PAD_LEFT);
        }


        $logColetorRomaneio = LogColetorRomaneio::select();

        if(!empty($campos['numero_nota'])){
            $logColetorRomaneio->where('numero_nota',$campos['numero_nota']);
        }else{
                if(!empty($campos['data_inicio']) && !empty($campos['data_fim'])){
                    $data_inicial = Carbon::CreateFromFormat("d/m/Y", $campos['data_inicio'])->setTime(0,0,0);;
                    $data_final = Carbon::CreateFromFormat("d/m/Y", $campos['data_fim'])->setTime(23,59,59);;
                    $logColetorRomaneio->whereBetween('data_nota', [$data_inicial, $data_final]);
                }else if(!empty($campos['data_inicio'])){
                    $data_inicial = Carbon::CreateFromFormat("d/m/Y", $campos['data_inicio'])->setTime(0,0,0);;
                    $logColetorRomaneio->where('data_nota', '>=', $data_inicial);
                }else if(!empty($campos['data_fim'])){
                    $data_final = Carbon::CreateFromFormat("d/m/Y", $campos['data_fim'])->setTime(23,59,59);;
                    $logColetorRomaneio->where('data_nota', '<=', $data_final);
                }
          }

        $logColetorRomaneios = $logColetorRomaneio->get();
          $nota_romaneio=  $logColetorRomaneios->pluck('numero_nota')->filter()->toArray();
        $romaneioNasajon = RomaneioNasajon::select('ra_data','fornecedor','entrada_origem_id','chavene','estabelecimento_codigo','documento_numero' ,'peca_id','peca_codigo', DB::RAW('count(peca_id) as quantidade_confere, sum(peca_quantidade) as qtde_pecas'));
         $romaneioNasajon->with(['coletorRomaneio' => function($query){
            $query->whereNotNull('peca_codigo')->orderBy('id');
        },'notaOrigem','nota']);
       $romaneioNasajon->where('ra_data', '>=', '2022-04-18');
  
    
        if(!empty($campos['estabelecimento'])){
            $romaneioNasajon->where('estabelecimento_codigo', '=',$estabelecimento );
        }
     
            $romaneioNasajon->whereIn('documento_numero',$nota_romaneio);

    
        $romaneioNasajon->groupBy('ra_data','fornecedor','entrada_origem_id','chavene','estabelecimento_codigo','documento_numero' ,'peca_id','peca_codigo');


        $romaneioNasajons = $romaneioNasajon->get();
       

        $pecas_romaneio=  $romaneioNasajons->pluck('peca_codigo')->filter()->toArray();
        $nota=  $romaneioNasajons->pluck('documento_numero')->filter()->toArray();
                $logColetor = LogColetor::select('estabelecimento','numero_nota' ,'operacao','peca_id','peca_codigo','confirmado','confirmacao_entrada_saida','produto_defeito_id', DB::RAW('max(id) as id_coletor, count(peca_codigo) as quantidade_confere, sum(quantidade) as qtde_pecas'))->where('alerta_erro',false);
         $logColetor->whereIn('peca_codigo', $pecas_romaneio);
         $logColetor->whereIn('numero_nota', $nota);

        if(!empty($campos['estabelecimento'])){
            $logColetor->where('estabelecimento', '=',str_pad($campos['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        $logColetor->groupBy('estabelecimento','numero_nota' ,'operacao','peca_id','peca_codigo','confirmado','confirmacao_entrada_saida','produto_defeito_id');
        $logColetor->orderBy('id_coletor');
        $logColetorsList = $logColetor->get();
     
        $logColetors=[];
        foreach($logColetorsList as $logColetor){

            if($logColetor->operacao =='Guarda'){
                $logColetors[$logColetor->peca_id] = [
                'estabelecimento' => $logColetor->estabelecimento,
                'numero_nota' => $logColetor->numero_nota,
                'operacao' => $logColetor->operacao,
                'peca_id' => $logColetor->peca_id,
                'peca_codigo' => $logColetor->peca_codigo,
                'confirmacao_entrada_saida' => $logColetor->confirmacao_entrada_saida,
                'confirmado'  =>empty($logColetor->confirmado) ? false : true,
                'quantidade_confere' => $logColetor->quantidade_confere,
                'qtde_pecas' => $logColetor->qtde_pecas,
                'produto_defeito_id' => $logColetor->produto_defeito_id,
            ];
           }else{

               $logColetors[$logColetor->peca_codigo] = [
                   'estabelecimento' => $logColetor->estabelecimento,
                   'numero_nota' => $logColetor->numero_nota,
                   'operacao' => $logColetor->operacao,
                   'peca_id' => $logColetor->peca_id,
                   'peca_codigo' => $logColetor->peca_codigo,
                   'confirmacao_entrada_saida' => $logColetor->confirmacao_entrada_saida,
                   'confirmado'  =>empty($logColetor->confirmado) ? false : true,
                   'quantidade_confere' => $logColetor->quantidade_confere,
                   'qtde_pecas' => $logColetor->qtde_pecas,
                   'produto_defeito_id' => $logColetor->produto_defeito_id,
               ];
           }

     }

       
     $saida = [];
     $natureza_operacao= ['0acc5b3f-bca6-44e3-8c3c-f5c9cf9389bf','29e89f67-4ce3-4ae6-ac54-801df96d67e6'];
     /* não buscar essas operações
             29e89f67-4ce3-4ae6-ac54-801df96d67e6  Retorno de Mercadorias Remetida para Industrialização Não Aplicada codigo operação  ENTREMESSAINDENCOMENDA
                0acc5b3f-bca6-44e3-8c3c-f5c9cf9389bf Outras Entradas  Remessa para Industrialização - Simbólica  codigo operação RETORNONAOAPLIC*/
       $romaneioNasajons->each(function ($romaneio) use(&$saida,$estabelecimentos,&$logColetors, &$natureza_operacao){
         $mostra_nota =true;
         if(!empty($romaneio->nota)){
           
            if(in_array($romaneio->nota['Identificador da Operação'], $natureza_operacao)){
             $mostra_nota =false;
            }
         }
         if($mostra_nota){
     
            if(!empty($romaneio->coletorRomaneio)){
       
                                $chave= $romaneio->chavene;

                                if(!isset($saida[$chave]['motivo_divergencia_id'])){
                                   
                                    $saida[$chave]['motivo_divergencia_id'] =$romaneio->coletorRomaneio->motivo_divergencia_id;
                                }
                                if(!isset($saida[$chave]['solucao']) && !empty($romaneio->coletorRomaneio->solucao) ){
                                   
                                    $saida[$chave]['solucao'] =$romaneio->coletorRomaneio->solucao->descricao;
                                }

                                if(!isset($saida[$chave]['id_coletor_romaneio'])){
                                   
                                    $saida[$chave]['id_coletor_romaneio'] =$romaneio->coletorRomaneio->id;
                                }
                                if(!isset($saida[$chave]['chave_nota'])){
                                    $saida[$chave]['chave_nota'] =$romaneio->chavene;
                                }


                                if(!isset($saida[$chave]['nota_coletor'])){
                                    $saida[$chave]['nota_coletor'] =$romaneio->documento_numero;
                                }
                                if(!isset($saida[$chave]['numero_remessa'])){
                                    $saida[$chave]['numero_remessa'] =$romaneio->documento_numero;
                                }

                                if(!isset($saida[$chave]['tem_nota_remessa'])){
                                    $saida[$chave]['tem_nota_remessa'] =false;
                                }
                                if(!isset($saida[$chave]['tem_nota_compra'])){
                                    $saida[$chave]['tem_nota_compra'] =false;
                                }

                                if(!isset($saida[$chave]['numero_nota'])){
                                    $saida[$chave]['numero_nota'] ='';
                                }

                                if(!isset($saida[$chave]['fornecedor'])){
                                    $saida[$chave]['fornecedor'] ='';
                                }

                                if(!isset($saida[$chave]['emissao'])){
                                    $saida[$chave]['emissao'] ='';
                                }
                                if(!isset($saida[$chave]['ra_data'])){
                                    $saida[$chave]['ra_data'] =$romaneio->ra_data;
                                }

                                if(!isset($saida[$chave]['entrada'])){
                                    $saida[$chave]['entrada'] ='';
                                }

                                if(!isset($saida[$chave]['id_nota'])){
                                    $saida[$chave]['id_nota'] ='';
                                }

                                $codigo_estabelecimento= str_pad($romaneio->estabelecimento_codigo, 2, "0", STR_PAD_LEFT);
                    
                                if(!empty($romaneio->nota)){
                                                $saida[$chave]['tem_nota_remessa'] =true;
                                                $saida[$chave]['numero_remessa'] =$romaneio->nota['Número do Documento'];
                                        
                                                $saida[$chave]['fornecedor'] =$romaneio->nota['Nome do Fornecedor'];
                                                $saida[$chave]['emissao'] = $romaneio->nota["Data de Emissão"];
                                                $saida[$chave]['entrada'] =$romaneio->nota["Data de Entrada"];
                                        
                                                if($codigo_estabelecimento !== '20'){
                                                    $saida[$chave]['numero_remessa']='';
                                                    $saida[$chave]['tem_nota_compra'] =true;
                                                    $saida[$chave]['tem_nota_remessa'] =false;
                                                
                                        
                                                    $saida[$chave]['numero_nota'] =$romaneio->documento_numero;
                                                
                                                }
                                        
                                            }else if ($codigo_estabelecimento == '20'){
                                                $saida[$chave]['numero_remessa'] = $romaneio->documento_numero;
                                          
                                        
                                            }
                                        
                                            if(!empty($romaneio->notaOrigem)){
                                                $saida[$chave]['numero_nota'] =$romaneio->notaOrigem['Número do Documento'];
                                                $saida[$chave]['fornecedor'] =$romaneio->notaOrigem['Nome do Fornecedor'];
                                            $saida[$chave]['emissao'] = $romaneio->notaOrigem["Data de Emissão"];
                                            if ($codigo_estabelecimento != '20'){
                                                  $saida[$chave]['entrada'] =$romaneio->notaOrigem["Data de Entrada"];
                                            }
                                            $saida[$chave]['id_nota'] =$romaneio->entrada_origem_id;
                                            $saida[$chave]['tem_nota_compra'] =true;

                                            }
                                            if( empty($saida[$chave]['fornecedor'])){
                                                $saida[$chave]['fornecedor'] = $romaneio->fornecedor;
                                    
                                            }
                                            if( empty($saida[$chave]['emissao'])){
                                                $saida[$chave]['emissao'] = $romaneio->ra_data;
                                            }
                                                
                                            

                                            if( $saida[$chave]['tem_nota_remessa'] ==false && $codigo_estabelecimento !== '20' ){
                                               
                                                $saida[$chave]['numero_remessa'] ='';
                                     
                                                $saida[$chave]['numero_nota'] =$romaneio->documento_numero;
                                            }
                                    
                                            if(!isset($saida[$chave]['estabelecimento_descricao'])){
                                                $saida[$chave]['estabelecimento_descricao'] = $estabelecimentos[$codigo_estabelecimento];
                                            }else{
                                                $saida[$chave]['estabelecimento_descricao'] = $estabelecimentos[$codigo_estabelecimento];
                                            }
                                            if(!isset($saida[$chave]['estabelecimento_codigo'])){
                                                $saida[$chave]['estabelecimento_codigo'] = $codigo_estabelecimento;
                                                
                                            }else{
                                                $saida[$chave]['estabelecimento_codigo'] = $codigo_estabelecimento;
                                            }
                                
                                            
                                        
                                                    if(!isset($saida[$chave]['qtde_romaneio_pecas'])){
                                                        $saida[$chave]['qtde_romaneio_pecas'] = $romaneio->quantidade_confere;
                                                    }else{
                                                        $saida[$chave]['qtde_romaneio_pecas'] +=$romaneio->quantidade_confere;
                                                    }

                                                    if(!isset($saida[$chave]['qtde_romaneio_qtde'])){
                                                        $saida[$chave]['qtde_romaneio_qtde'] =$romaneio->qtde_pecas;
                                                    }else{
                                                        $saida[$chave]['qtde_romaneio_qtde'] +=$romaneio->qtde_pecas;
                                                    }


                                

                                        
                                        

                                            if(!isset($saida[$chave]['qtde_enderecada_romaneio_pecas'])){
                                                $saida[$chave]['qtde_enderecada_romaneio_pecas'] =0;
                                            }

                                            if(!isset($saida[$chave]['qtde_enderecada_romaneio_qtde'])){
                                                $saida[$chave]['qtde_enderecada_romaneio_qtde'] =0;
                                            }
                                
                                            if(!isset($saida[$chave]['qtde_enderecada_nota_pecas'])){
                                                $saida[$chave]['qtde_enderecada_nota_pecas'] =0;
                                            }
                                            if(!isset($saida[$chave]['qtde_enderecada_nota_qtde'])){
                                                $saida[$chave]['qtde_enderecada_nota_qtde'] =0;
                                            }

                                            if(!isset($saida[$chave]['qtde_conferida_pecas'])){
                                                $saida[$chave]['qtde_conferida_pecas'] =0;
                                            }

                                            if(!isset($saida[$chave]['qtde_conferida_qtde'])){
                                                $saida[$chave]['qtde_conferida_qtde'] =0;
                                            }

                                            if(!isset($saida[$chave]['qtde_diferenca_pecas'])){
                                                $saida[$chave]['qtde_diferenca_pecas'] =0;
                                            }
                                            if(!isset($saida[$chave]['qtde_diferenca_qtde'])){
                                                $saida[$chave]['qtde_diferenca_qtde'] =0;
                                            }


                                            if(!isset($saida[$chave]['confirmacao_entrada_saida'])){
                                                $saida[$chave]['confirmacao_entrada_saida'] =false;
                                            }
                                            if(!isset($saida[$chave]['confirmado'])){
                                                $saida[$chave]['confirmado'] =true;
                                            }

                                            if(!isset($saida[$chave]['situacao'])){
                                                $saida[$chave]['situacao'] ='aberto';
                                            }
                                            if(!isset($saida[$chave]['situacao_compra'])){
                                               
                                                $saida[$chave]['situacao_compra'] ='aberto';
                                            }
                                            if(!isset($saida[$chave]['tem_defeito'])){
                                                $saida[$chave]['tem_defeito'] =false;
                                            }

                                            if(!empty($logColetors[$romaneio->peca_id])){
                                                if($logColetors[$romaneio->peca_id]['numero_nota'] ==$romaneio->documento_numero ){

                                                    if(!empty($logColetors[$romaneio->peca_id]['produto_defeito_id'])){
                                                        $saida[$chave]['tem_defeito'] =true;
                                                    }

                                                    $saida[$chave]['qtde_conferida_pecas'] +=$logColetors[$romaneio->peca_id]['quantidade_confere'];
                                                    $saida[$chave]['qtde_conferida_qtde']  +=$logColetors[$romaneio->peca_id]['qtde_pecas'];
                                                    if($logColetors[$romaneio->peca_id]['operacao'] == 'Guarda'){                       
                                                        if($logColetors[$romaneio->peca_id]['confirmacao_entrada_saida'] == true){

                                                            if($logColetors[$romaneio->peca_id]['confirmado'] == false){
                                                                $saida[$chave]['confirmado'] =false;
                                                            
                                                            }
                                                    
                                                            $saida[$chave]['qtde_enderecada_romaneio_pecas'] +=$logColetors[$romaneio->peca_id]['quantidade_confere'];
                                                            $saida[$chave]['qtde_enderecada_romaneio_qtde']  +=$logColetors[$romaneio->peca_id]['qtde_pecas'];
                                                    

                                                        }else{
                                                            $saida[$chave]['qtde_enderecada_nota_pecas'] += $logColetors[$romaneio->peca_id]['quantidade_confere'];
                                                            $saida[$chave]['qtde_enderecada_nota_qtde']  += $logColetors[$romaneio->peca_id]['qtde_pecas'];
                                                
                                                        }
                                                    }
                                                    
                                                    } 
                                            
                                        
                                             }else  if(isset($logColetors[$romaneio->peca_codigo])){

                                        
                                                if($logColetors[$romaneio->peca_codigo]['numero_nota'] ==$romaneio->documento_numero ){
                                                    if(!empty($logColetors[$romaneio->peca_id]['produto_defeito_id'])){
                                                        $saida[$chave]['tem_defeito'] =true;
                                                    }
                                                
                                                    $saida[$chave]['qtde_conferida_pecas'] +=$logColetors[$romaneio->peca_codigo]['quantidade_confere'];
                                                    $saida[$chave]['qtde_conferida_qtde']  +=$logColetors[$romaneio->peca_codigo]['qtde_pecas'];

                                                    if($logColetors[$romaneio->peca_codigo]['operacao'] == 'Guarda'){     

                                                        $saida[$chave]['qtde_enderecada_nota_pecas'] += $logColetors[$romaneio->peca_codigo]['quantidade_confere'];
                                                        $saida[$chave]['qtde_enderecada_nota_qtde']  += $logColetors[$romaneio->peca_codigo]['qtde_pecas'];
                                                    }
                                            
                                                    
                                                }
                                                
                                        
                                    
                                              }
                                         if(!isset($saida[$chave]['qtde_diferenca_pecas'])){
                                                $saida[$chave]['qtde_diferenca_pecas'] = $saida[$chave]['qtde_conferida_pecas'] -$saida[$chave]['qtde_romaneio_pecas']   ;
                                         }else{
                                                $saida[$chave]['qtde_diferenca_pecas']= $saida[$chave]['qtde_conferida_pecas'] - $saida[$chave]['qtde_romaneio_pecas']  ;
                                            }
    
                                         if(!isset($saida[$chave]['qtde_diferenca_qtde'])){
                                                $saida[$chave]['qtde_diferenca_qtde'] = $saida[$chave]['qtde_conferida_qtde'] -$saida[$chave]['qtde_romaneio_qtde'] ;
                                            }else{
                                                $saida[$chave]['qtde_diferenca_qtde']=  $saida[$chave]['qtde_conferida_qtde'] -$saida[$chave]['qtde_romaneio_qtde'];
                                            }

                                        if( $saida[$chave]['qtde_diferenca_pecas'] != $saida[$chave]['qtde_romaneio_pecas'] &&  $saida[$chave]['qtde_diferenca_pecas']<0
                                        )
                                        {
                                            $saida[$chave]['situacao'] ='divergente';
                                        }
                                        
                                        if(!empty($romaneio->coletorRomaneio) && $saida[$chave]['situacao'] !=='finalizado'
                                        )
                                             {
                                                    $saida[$chave]['situacao'] ='finalizado';
        
                                                }
        
                                               if(empty($saida[$chave]['numero_nota'])){
                                                 $saida[$chave]['situacao'] ='finalizado';
        
                                               }

                                               if(!empty($romaneio->coletorRomaneio->motivo_divergencia_id)){
                                                $saida[$chave]['situacao_compra'] ='divergente';
                                               }


                                    

                                }
                            }
            
         });


        if(!empty($campos['situacao'])){
        
            if($campos['situacao'] == 'aberto'){
                $saida = $this->filtraAberto($saida);
            }
            if($campos['situacao'] == 'divergente'){
                $saida = $this->filtraDivergente($saida);
            }
            if($campos['situacao'] == 'finalizado'){
                $saida = $this->filtraFinalizado($saida);
            }
            if($campos['situacao'] == 'guardar'){
                $saida = $this->filtraGuardar($saida);
            }
          
     
        }

 

        $total_romaneio_pecas = 0;
        $total_diferenca_pecas =   0;
        $total_enderecada_romaneio_pecas = 0;
        $total_enderecada_nota_pecas = 0;
        $total_geral_pecas = 0;
        $total_romaneio_qtde = 0;
        $total_conferida_qtde = 0;
        $total_diferenca_qtde =   0;
        $total_enderecada_romaneio_qtde = 0;
        $total_enderecada_nota_qtde = 0;
        $total_geral_qtde = 0;
   

        foreach($saida as $key => $dados){

            $total_romaneio_pecas +=  $saida[$key]['qtde_romaneio_pecas'] ;
            $total_diferenca_pecas += ($saida[$key]['qtde_diferenca_pecas']);
            $total_enderecada_romaneio_pecas += ($saida[$key]['qtde_enderecada_romaneio_pecas']);
            $total_enderecada_nota_pecas += $saida[$key]['qtde_enderecada_nota_pecas'];
   
       
            $total_romaneio_qtde +=  $saida[$key]['qtde_romaneio_qtde'];
            $total_diferenca_qtde += $saida[$key]['qtde_diferenca_qtde'];
            $total_enderecada_romaneio_qtde +=  $saida[$key]['qtde_enderecada_romaneio_qtde'];
            $total_enderecada_nota_qtde += $saida[$key]['qtde_enderecada_nota_qtde'];
            $total_conferida_qtde += $saida[$key]['qtde_conferida_qtde'];

  
         
        }
 
     $saida = $this->ajusteArrayParaValores( $saida);

    
        $pode_finalizar =false;
        if (in_array(Auth::user()->tipo_usuario_id, [1])){
            $pode_finalizar =true;
        }
   
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'dados' => $saida,
                'total_romaneio_pecas' =>   empty($total_romaneio_pecas) ? '' :parserValorInteiro($total_romaneio_pecas),
                'total_diferenca_pecas' => empty($total_diferenca_pecas) ? '' :parserValorInteiro($total_diferenca_pecas),
                'total_enderecada_romaneio_pecas' => empty($total_enderecada_romaneio_pecas) ? '' :parserValorInteiro($total_enderecada_romaneio_pecas),
                'total_enderecada_nota_pecas' => empty($total_enderecada_nota_pecas) ? '' :parserValorInteiro($total_enderecada_nota_pecas),
                'total_geral_pecas' => empty($total_geral_pecas) ? '' :parserValorInteiro($total_geral_pecas),
                'total_romaneio_qtde' =>  empty($total_romaneio_qtde) ? '' :parserValor($total_romaneio_qtde),
                'total_diferenca_qtde' => empty($total_diferenca_qtde) ? '' :parserValor($total_diferenca_qtde),
                'total_enderecada_romaneio_qtde' => empty($total_enderecada_romaneio_qtde) ? '' :parserValor($total_enderecada_romaneio_qtde),
                'total_enderecada_nota_qtde' => empty($total_enderecada_nota_qtde) ? '' :parserValor($total_enderecada_nota_qtde),
                'total_geral_qtde' => empty($total_geral_qtde) ? '' :parserValor($total_geral_qtde),
                'pode_finalizar' => $pode_finalizar,
             
            ]

              
        ];
    
        return response()->json($response, 200);
    }

    private function ajusteArrayParaValores($array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = $this->ajusteArrayParaValores($value);
                } else {
                    if (is_numeric($value) && !str_contains($key, "estabelecimento_codigo") && !str_contains($key, "numero_nota") && !str_contains($key, "numero_remessa") && !str_contains($key, "nota_coletor")
                    &&  !str_contains($key, "fornecedor") && !str_contains($key, "confirmacao_entrada_saida")  && !str_contains($key, "confirmado") 
                    && !str_contains($key, "tem_")
                    && !str_contains($key, "emissao") &&  !str_contains($key, "entrada") &&  !str_contains($key, "chave_nota")   &&  !str_contains($key, "ra_data")
                    &&  !str_contains($key, "id_coletor_romaneio") &&  !str_contains($key, "motivo_divergencia_id")   &&  !str_contains($key, "solucao")
                    
                    ) {

                        if (str_contains($key, "qtde_diferenca_qtde")){
                            $array[$key] = empty($value) ? 0 : parserValor($value);
                        }else
                                if (str_contains($key, "_qtde")) {
                                    $array[$key] = empty($value) ? '' : parserValor($value);
                                } else {
                                    $array[$key] = empty($value) ? '' : parserValorInteiro($value);
                                }
               
                    } else  if ( $key == 'emissao' ||  $key == 'entrada'  ||  $key == 'ra_data') { 
                       
                        $array[$key] = empty($value) ? '' : parserData($value);
                    }else{
                        $array[$key] = $value;
                    }
                }
            }
        }
        return $array;
    }


    private function filtraAberto($filteredArray)
    {
        if (is_array($filteredArray)) {
        
            $filteredArray = Arr::where($filteredArray, function ($value, $key) {
                return $value['situacao_compra'] ==  'aberto';
            });
        }
       
           return $filteredArray;
    }
    private function filtraDivergente($filteredArray)
    {
        if (is_array($filteredArray)) {
        
            $filteredArray = Arr::where($filteredArray, function ($value, $key) {
                return $value['situacao_compra'] ==  'divergente';
            });
        }
        return $filteredArray;
    }
    private function filtraGuardar($filteredArray)
    {
        if (is_array($filteredArray)) {
        
            $filteredArray = Arr::where($filteredArray, function ($value, $key) {
                return $value['confirmado'] == false;
            });
        }
        return $filteredArray;
    }
    private function filtraFinalizado($filteredArray)
    {
        if (is_array($filteredArray)) {
        
            $filteredArray = Arr::where($filteredArray, function ($value, $key) {
                return $value['situacao'] ==  'finalizado';
            });
        }
        return $filteredArray;
    }

    public function dialog(Request $request){
        $estabelecimentos = returnTodasEmpresasView();
        set_time_limit(300);
        ini_set('memory_limit','1024M');
       $campos = $request->only('chave_nota');


       $romaneioNajason = RomaneioNasajon::select()->with('especificacoes');

        if(!empty($campos['chave_nota'])){
            $romaneioNajason->where('chavene', '=',$campos['chave_nota']);
        }
       
  
        $romaneioNajasons = $romaneioNajason->get();

        $pecas_romaneio=  $romaneioNajasons->pluck('peca_codigo')->filter()->toArray();
        $nota =  $romaneioNajasons->pluck('documento_numero')->filter()->toArray();
        $logColetor = LogColetor::select('estabelecimento','numero_nota' ,'operacao','peca_id','peca_codigo','endereco_codigo','confirmacao_entrada_saida','quantidade')->where('alerta_erro',false);
        $logColetor->whereIn('peca_codigo', $pecas_romaneio);
        $logColetor->whereIn('numero_nota', $nota);
  
 
        $logColetor->whereNotNull('numero_nota');
        $logColetor->orderBy('operacao','desc');

        $logColetors = $logColetor->get();
        $notasEntradasNasajon = NotasEntradasNasajon::select('Identificador Documento')->where('Chave NE', $campos['chave_nota']);

        $notasEntradasNasajons = $notasEntradasNasajon->first();
    
        $intesNotasNasajon = IntesNotasNasajon::select()->where('id_nota', $notasEntradasNasajons['Identificador Documento']);
        $intesNotasNasajons = $intesNotasNasajon->get();
        $itens_nota = [];
     
        
        foreach ($intesNotasNasajons as $item){
            $itens_nota[$item->cod_produto] = [
                "codigo" => $item->cod_produto,
                "descricao" => $item->desc_produto,
                "quantidade" => $item->quantidade,
                "preco_unitario" =>$item->valor_unitario,
    
            ];
    
        }
    
        $total_romaneio = 0;
        $total_diferenca =   0;
        $total_coletor = 0;
        $total_peca_conferido = 0;
        $total_peca_enderecado = 0;
        $total_diferenca_valor=0;

        $romaneios = [];  
        foreach($romaneioNajasons as $romaneio){
            $coletor =  $logColetors->firstWhere('peca_codigo',$romaneio->peca_codigo);
            $conferido = false;
            $diferenca=0;
            if(!empty($itens_nota[$romaneio->item_codigo])){
                $valor_unitario =  $itens_nota[$romaneio->item_codigo]['preco_unitario'];
            }
            if(isset( $coletor)){
                $conferido =true;
                $diferenca = $coletor->quantidade - $romaneio->peca_quantidade ;

                $total_coletor +=$coletor->quantidade;
                $total_peca_conferido +=1;
            }else{
                $diferenca =  $romaneio->peca_quantidade *-1 ;
     
             }
            $total_romaneio +=$romaneio->peca_quantidade;
            $total_item = $diferenca *   $valor_unitario;
            $total_diferenca +=$diferenca;
            $total_diferenca_valor +=$total_item;
            if(!empty($coletor->endereco_codigo)){
                $total_peca_enderecado +=1;
            }
            $romaneios[] = [
                'fornecedor' => $romaneio->fornecedor,
                'estabelecimento_codigo' =>str_pad($romaneio->estabelecimento_codigo, 2, "0", STR_PAD_LEFT)  ,
                'estabelecimento_nome' => $romaneio->estabelecimento_nome,
                'documento_numero' => $romaneio->documento_numero,
                'peca_codigo' => $romaneio->peca_codigo,
                'peca_quantidade' =>   parserValor($romaneio->peca_quantidade) ,
                'conferido' =>   $conferido,
                'produto_codigo' => $romaneio->item_codigo,
                'produto_descricao' => $romaneio->especificacoes->descricao,
                 'endereco' => empty($coletor->endereco_codigo) ? '' :  $coletor->endereco_codigo,
                 'quantidade_coletor' =>   empty($coletor->quantidade) ? '' :  parserValor($coletor->quantidade) ,
                 'diferenca' =>   empty($diferenca) ? '' :  parserValor($diferenca) , 
                 'enderecado' =>  empty($coletor->endereco_codigo) ? false :  true,
                 'produto_preco' => empty($valor_unitario) ? '' :  parserValor($valor_unitario) ,
                 'total_item' => empty($total_item) ? '' :  parserValor($total_item) ,

              
            ];

        }
  
        $totais = [
            'total_romaneio' =>   empty($total_romaneio) ? '' :  parserValor($total_romaneio) , 
            'total_coletor' =>   empty($total_coletor) ? '' :  parserValor($total_coletor) , 
            'total_diferenca' =>   empty($total_diferenca) ? '' :  parserValor($total_diferenca) , 
            'total_peca_conferido' =>   empty($total_peca_conferido) ? '' :  parserNumber($total_peca_conferido) , 
            'total_peca_enderecado' =>   empty($total_peca_enderecado) ? '' :  parserNumber($total_peca_enderecado) , 
            'total_diferenca_valor' =>   empty($total_diferenca_valor) ? '' :  parserValor($total_diferenca_valor) , 
        ];

        
        return view('programs.romaneio_entrada.modal.dialog')->with(['dados' =>  $romaneios,'totais' =>  $totais]);
    }


   public function situacao(){

        $situacao = [
            "" => 'Geral',
              "divergente" => 'Divergente',
              "aberto" => 'Aberto',
            

        ];

        return $situacao;
    }


 
    public function modalSolucao(Request $request){
        $campo = $request->only('id');

       try{
            $id = ($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }
    
        $LogColetorRomaneioObj = LogColetorRomaneio::find($id);
    
        if(is_null($LogColetorRomaneioObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    $e,
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }
        $solucoes =$this->motivo_divergencia();
        $dados = [
            'id' => ($LogColetorRomaneioObj->id),
            'motivo_divergencia_id' => $LogColetorRomaneioObj->motivo_divergencia_id
        ];

        return view('programs.romaneio_entrada.modal.solucao')->with(['dados' => $dados,'solucoes' => $solucoes]);
    }

    public function solucao(Request $request){
        $campo = $request->only('id','solucoes');

        try{
            $id = ($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $LogColetorRomaneioObj = LogColetorRomaneio::find($id);
        $LogColetorRomaneioObj->motivo_divergencia_id =$campo['solucoes'];
        $LogColetorRomaneioObj->updated_by = Auth::id();
        $LogColetorRomaneioObj->save(); 

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

public function motivo_divergencia(){
    $solucoes=[];
    $motivoDivergencia = MotivoDivergencia::select();

    $motivoDivergencias =$motivoDivergencia->get();
    $defeitos[''] = 'Sem Defeito';

    foreach ($motivoDivergencias as $key => $solucao) {

           $solucoes[$solucao->id] = strtoupper($solucao->descricao);
         
                 
    }

    return $solucoes;

}


public function dialogDefeito(Request $request){
    $estabelecimentos = returnTodasEmpresasView();
    set_time_limit(300);
    ini_set('memory_limit','1024M');
   $campos = $request->only('chave_nota');


   $romaneioNajason = RomaneioNasajon::select()->with('especificacoes');

    if(!empty($campos['chave_nota'])){
        $romaneioNajason->where('chavene', '=',$campos['chave_nota']);
    }


    $romaneioNajasons = $romaneioNajason->get();

    $pecas_romaneio=  $romaneioNajasons->pluck('peca_codigo')->filter()->toArray();
    $nota =  $romaneioNajasons->pluck('documento_numero')->filter()->toArray();
    $logColetor = LogColetor::select('estabelecimento','numero_nota' ,'operacao','peca_id','peca_codigo','endereco_codigo','confirmacao_entrada_saida','produto_defeito_id','quantidade')->where('alerta_erro',false);
    $logColetor->whereIn('peca_codigo', $pecas_romaneio);
    $logColetor->whereIn('numero_nota', $nota);
    $logColetor->where('alerta_erro',false);

    $logColetor->whereNotNull('numero_nota');
    $logColetor->orderBy('operacao','desc');

    $logColetors = $logColetor->get();

    $notasEntradasNasajon = NotasEntradasNasajon::select('Identificador Documento')->where('Chave NE', $campos['chave_nota']);

    $notasEntradasNasajons = $notasEntradasNasajon->first();

    $intesNotasNasajon = IntesNotasNasajon::select()->where('id_nota', $notasEntradasNasajons['Identificador Documento']);
    $intesNotasNasajons = $intesNotasNasajon->get();
    $itens_nota = [];
 
    
    foreach ($intesNotasNasajons as $item){
        $itens_nota[$item->cod_produto] = [
            "codigo" => $item->cod_produto,
            "descricao" => $item->desc_produto,
            "quantidade" => $item->quantidade,
            "preco_unitario" =>$item->valor_unitario,

        ];

    }

    $total_romaneio = 0;
    $total_diferenca =   0;
    $total_coletor = 0;
    $total_peca_conferido = 0;
    $total_peca_enderecado = 0;
    $total_diferenca_valor=0;

    $romaneios = [];  
    foreach($romaneioNajasons as $romaneio){
        $coletor =  $logColetors->firstWhere('peca_codigo',$romaneio->peca_codigo);
        $valor_unitario =0;

        if(!empty($itens_nota[$romaneio->item_codigo])){
            $valor_unitario =  $itens_nota[$romaneio->item_codigo]['preco_unitario'];
        }
      
        $conferido = false;
        $diferenca=0;
        if(isset( $coletor)){
            $diferenca = $coletor->quantidade - $romaneio->peca_quantidade ;
            if(!empty($coletor->produto_defeito_id) ||  $diferenca != 0 ){
                $conferido =true;
              
                $total_coletor +=$coletor->quantidade;
                $total_peca_conferido +=1;


            }

        }else{
           $diferenca =  $romaneio->peca_quantidade *-1 ;

        }


        if(!isset( $coletor) || !empty($coletor->produto_defeito_id) ||  $diferenca != 0){
            if(!empty($coletor->endereco_codigo)){
                $total_peca_enderecado +=1;
            }
            $total_romaneio +=$romaneio->peca_quantidade;
            $total_item = $diferenca *   $valor_unitario;
            $total_diferenca +=$diferenca;
            $total_diferenca_valor +=$total_item;
        $romaneios[] = [
            'fornecedor' => $romaneio->fornecedor,
            'estabelecimento_codigo' =>str_pad($romaneio->estabelecimento_codigo, 2, "0", STR_PAD_LEFT)  ,
            'estabelecimento_nome' => $romaneio->estabelecimento_nome,
            'documento_numero' => $romaneio->documento_numero,
            'peca_codigo' => $romaneio->peca_codigo,
            'peca_quantidade' =>   parserValor($romaneio->peca_quantidade) ,
            'conferido' =>   $conferido,
            'produto_codigo' => $romaneio->item_codigo,
            'produto_descricao' => $romaneio->especificacoes->descricao,
             'endereco' => empty($coletor->endereco_codigo) ? '' :  $coletor->endereco_codigo,
             'quantidade_coletor' =>   empty($coletor->quantidade) ? '' :  parserValor($coletor->quantidade) ,
             'diferenca' =>   empty($diferenca) ? 0 :  parserValor($diferenca) , 
             'enderecado' =>  empty($coletor->endereco_codigo) ? false :  true,
             'produto_preco' => empty($valor_unitario) ? '' :  parserValor($valor_unitario) ,
             'total_item' => empty($total_item) ? '' :  parserValor($total_item) ,

          
        ];
    }

    }
    $totais = [
        'total_romaneio' =>   empty($total_romaneio) ? '' :  parserValor($total_romaneio) , 
        'total_coletor' =>   empty($total_coletor) ? '' :  parserValor($total_coletor) , 
        'total_diferenca' =>   empty($total_diferenca) ? '' :  parserValor($total_diferenca) , 
        'total_peca_conferido' =>   empty($total_peca_conferido) ? '' :  parserNumber($total_peca_conferido) , 
        'total_peca_enderecado' =>   empty($total_peca_enderecado) ? '' :  parserNumber($total_peca_enderecado) , 
        'total_diferenca_valor' =>   empty($total_diferenca_valor) ? '' :  parserValor($total_diferenca_valor) , 
    ];

    
    return view('programs.romaneio_entrada.modal.dialog')->with(['dados' =>  $romaneios,'totais' =>  $totais]);
}
}