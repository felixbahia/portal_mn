<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\LogColetor;
use App\ProdutoDefeito;
use Illuminate\Http\Request;
use App\NasajonEstabelecimento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class LogColetorController extends Controller
{


    public function index(Request $request){
        
        if(Auth::user()->hasPermissionTo("programas App\LogColetor") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\LogColetor');

        $coletores = [];
        $estabelecimentos = returnTodasEmpresasView();
        return view("programs.consulta_coletor.index")->with(['estabelecimentos' => $estabelecimentos ]);
    }

    public function filtro(Request $request){

        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $estabelecimentos = returnTodasEmpresasView();
        $campos = $request->only('estabelecimento','data_inicio','data_fim');

        $logColetor = LogColetor::select('estabelecimento', 'operacao', DB::RAW('count(id) as quantidade_confere'))->where('alerta_erro',false)->whereNotNull('estabelecimento');

        if(!empty($campos['estabelecimento'])){
            $logColetor->where('estabelecimento', '=',str_pad($campos['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }

        if(!empty($campos['data_inicio']) && !empty($campos['data_fim'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $campos['data_inicio'])->setTime(0,0,0);;
            $data_final = Carbon::CreateFromFormat("d/m/Y", $campos['data_fim'])->setTime(23,59,59);;
            $logColetor->whereBetween('created_at', [$data_inicial, $data_final]);
        }else if(!empty($campos['data_inicio'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $campos['data_inicio'])->setTime(0,0,0);;
            $logColetor->where('created_at', '>=', $data_inicial);
        }else if(!empty($campos['data_fim'])){
            $data_final = Carbon::CreateFromFormat("d/m/Y", $campos['data_fim'])->setTime(23,59,59);;
            $logColetor->where('created_at', '<=', $data_final);
        }
    
        $logColetor->groupBy('estabelecimento','operacao');
    
        $logColetors = $logColetor->get();
       
         $saida = [];
 

         $total_confere=0;
         $total_guarda=0;
         $total_separa=0;
         $estabe='';
       
          $logColetors->each(function ($coletor) use(&$saida,$estabelecimentos){

           
            $estabe=  $estabelecimentos[$coletor->estabelecimento];

            if(!isset($saida[$estabe]['estabelecimento_descricao'])){
                $saida[$estabe]['estabelecimento_descricao'] = $estabe;
            }else{
                $saida[$estabe]['estabelecimento_descricao'] = $estabe;
            }

            if(!isset($saida[$estabe]['estabelecimento_codigo'])){
                $saida[$estabe]['estabelecimento_codigo'] =$coletor->estabelecimento;
            }else{
                $saida[$estabe]['estabelecimento_codigo'] =$coletor->estabelecimento;
            }
            
            if(!isset($saida[$estabe]['Conferencia'])){
                $saida[$estabe]['Conferencia'] = 0;
            }
            if(!isset($saida[$estabe]['Guarda'])){
                $saida[$estabe]['Guarda'] =0;
            }
            if(!isset($saida[$estabe]['Separação'])){
                $saida[$estabe]['Separação'] =0;
            }

            if(!isset($saida[$estabe][$coletor->operacao])){
                $saida[$estabe][$coletor->operacao] = 0;
            }

            $saida[$estabe][$coletor->operacao] += $coletor->quantidade_confere;

            if(!isset($saida[$estabe]['total'])){
                $saida[$estabe]['total'] =$saida[$estabe][$coletor->operacao];
            }else{
                $saida[$estabe]['total'] += $saida[$estabe][$coletor->operacao] ;
            }
        });
       
        $total_confere = $logColetors->where('operacao','Conferencia')->sum('quantidade_confere');
        $total_guarda = $logColetors->where('operacao','Guarda')->sum('quantidade_confere');
        $total_separa = $logColetors->where('operacao','Separação')->sum('quantidade_confere');
        $total_geral = $logColetors->sum('quantidade_confere');
    
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'dados' => $saida,
                'total_confere' =>  parserNumber($total_confere) == 0? '' : parserNumber($total_confere),
                'total_guarda' =>parserNumber($total_guarda) == 0? '' : parserNumber($total_guarda),
                'total_separa' =>parserNumber($total_separa) == 0? '' : parserNumber($total_separa),
                'total_geral' =>parserNumber($total_geral) == 0? '' : parserNumber($total_geral),
            ]

              
        ];
    
 
        return response()->json($response, 200);
    }

    public function dialog(Request $request){
        $estabelecimentos = returnTodasEmpresasView();
        set_time_limit(300);
        ini_set('memory_limit','1024M');
       $campos = $request->only('codigo_estabelecimento', 'filtro','data_inicio','data_fim');


       $logColetor = LogColetor::select('user_name', 'operacao', DB::RAW('count(id) as quantidade_confere'))->where('alerta_erro',false)->whereNotNull('estabelecimento');

        if(!empty($campos['filtro'])){
            $logColetor->where('operacao', '=',$campos['filtro']);
        }

        if(!empty($campos['codigo_estabelecimento'])){
            $logColetor->where('estabelecimento', '=',str_pad($campos['codigo_estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        if(!empty($campos['data_inicio']) && !empty($campos['data_fim'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $campos['data_inicio'])->setTime(0,0,0);;
            $data_final = Carbon::CreateFromFormat("d/m/Y", $campos['data_fim'])->setTime(23,59,59);;
            $logColetor->whereBetween('created_at', [$data_inicial, $data_final]);
        }else if(!empty($campos['data_inicio'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $campos['data_inicio'])->setTime(0,0,0);;
            $logColetor->where('created_at', '>=', $data_inicial);
        }else if(!empty($campos['data_fim'])){
            $data_final = Carbon::CreateFromFormat("d/m/Y", $campos['data_fim'])->setTime(23,59,59);;
            $logColetor->where('created_at', '<=', $data_final);
        }
        $logColetor->groupBy('user_name','operacao');
   
        $logColetors = $logColetor->get();
       
        $saida = [];
 
     
         $logColetors->each(function ($coletor) use(&$saida){

          
           $colaborador= $coletor->user_name;

           if(!isset($saida[$colaborador]['colaborador'])){
               $saida[$colaborador]['colaborador'] = $colaborador;
           }else{
               $saida[$colaborador]['colaborador'] = $colaborador;
           }


           
           if(!isset($saida[$colaborador]['Conferencia'])){
               $saida[$colaborador]['Conferencia'] = 0;
           }
           if(!isset($saida[$colaborador]['Guarda'])){
               $saida[$colaborador]['Guarda'] =0;
           }
           if(!isset($saida[$colaborador]['Separação'])){
               $saida[$colaborador]['Separação'] =0;
           }

           if(!isset($saida[$colaborador][$coletor->operacao])){
               $saida[$colaborador][$coletor->operacao] = 0;
           }

           $saida[$colaborador][$coletor->operacao] += $coletor->quantidade_confere;

           if(!isset($saida[$colaborador]['total'])){
               $saida[$colaborador]['total'] =$saida[$colaborador][$coletor->operacao];
           }else{
               $saida[$colaborador]['total'] += $saida[$colaborador][$coletor->operacao] ;
           }
       });
      
   

 
        return view('programs.consulta_coletor.modal.dialog')->with(['dados' =>  $saida]);
    }


    public function modalOperacao(Request $request){
        $estabelecimentos = returnTodasEmpresasView();
        set_time_limit(300);
        ini_set('memory_limit','1024M');
       $campos = $request->only('codigo_estabelecimento', 'filtro','data_inicio','data_fim');


       $logColetor = LogColetor::select('id', 'ra_id', 'alerta_erro', 'estabelecimento', 'produto_codigo', 'peca_id', 'peca_codigo'
       , 'endereco_id', 'endereco_codigo', 'numero_pedido', 'numero_nota', 'numero_ra', 'tipo_operacao', 'created_by', 'user_name'
       , 'quantidade', 'created_at', 'updated_at', 'deleted_at', 'operacao', 'confirmado')->where('alerta_erro',false)->whereNotNull('estabelecimento');


        if(!empty($campos['filtro'])){
            $logColetor->where('operacao', '=',$campos['filtro']);
        }

        if(!empty($campos['codigo_estabelecimento'])){
            $logColetor->where('estabelecimento', '=',str_pad($campos['codigo_estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        if(!empty($campos['data_inicio']) && !empty($campos['data_fim'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $campos['data_inicio'])->setTime(0,0,0);;
            $data_final = Carbon::CreateFromFormat("d/m/Y", $campos['data_fim'])->setTime(23,59,59);;
            $logColetor->whereBetween('created_at', [$data_inicial, $data_final]);
        }else if(!empty($campos['data_inicio'])){
            $data_inicial = Carbon::CreateFromFormat("d/m/Y", $campos['data_inicio'])->setTime(0,0,0);;
            $logColetor->where('created_at', '>=', $data_inicial);
        }else if(!empty($campos['data_fim'])){
            $data_final = Carbon::CreateFromFormat("d/m/Y", $campos['data_fim'])->setTime(23,59,59);;
            $logColetor->where('created_at', '<=', $data_final);
        }
        $logColetor->orderBy('estabelecimento');
        $logColetor->orderBy('operacao');
        $logColetors = $logColetor->get();
       
         $saida = [];
      
         $operacao='';
         $documento='';
         $total_geral=0;
         foreach($logColetors  as $log){
            $operacao= $log->operacao;
           if(!empty($log->numero_nota)){
            $documento=$log->numero_nota;
           }else{
            $documento=$log->numero_ra;
           }

              
            $saida[] = [
                'colaborador' => $log->user_name,
                'operacao' => $operacao,
                'documento' => $documento,
                'data_hora' => parserData($log->created_at),
                'total_geral' => empty($total_geral)? '' : parserValor($total_geral),
     
            ];
        }
    
        return view('programs.consulta_coletor.modal.operacao')->with(['dados' => $saida]);
    }


    public function modalDefeito(Request $request){
        $estabelecimentos = returnTodasEmpresasView();
        set_time_limit(300);
        ini_set('memory_limit','1024M');
       $campos = $request->only('codigo_estabelecimento', 'numero_nota');


       $logColetor = LogColetor::select()->with('defeito')->where('alerta_erro',false);

       $logColetor->where('operacao', 'Guarda');
        if(!empty($campos['numero_nota'])){
            $logColetor->where('numero_nota', '=',$campos['numero_nota']);
        }

        $logColetors = $logColetor->get();
   
       
         $saida = [];
      
         $operacao='';
         $documento='';
         $total_geral=0;
         foreach($logColetors  as $log){
   
          

            $saida[] = [
                'id_coletor' =>$log->id,
                'colaborador' => $log->user_name,
                'documento' => $log->numero_nota,
                'produto_codigo' =>  $log->produto_codigo,
                'peca_codigo' =>  $log->peca_codigo,
                'produto_defeito_id' => empty($log->produto_defeito_id)? '' : Crypt::encrypt($log->produto_defeito_id),
                'data_hora' => parserData($log->created_at),
                'total_geral' => empty($total_geral)? '' : parserValor($total_geral),
                'defeito' =>  empty( $log->defeito->descricao)? 'Sem Defeito' :  $log->defeito->descricao,
     
            ];
        }

    $defeitos =$this->defeitos();

        return view('programs.consulta_coletor.modal.defeito')->with(['dados' => $saida ,'defeitos' => $defeitos]);
    }


    public function defeitos(){
        $defeitos=[];
        $proddutoDefeito = ProdutoDefeito::select();

        $proddutoDefeitos =$proddutoDefeito->get();
        $defeitos[''] = 'Sem Defeito';

        foreach ($proddutoDefeitos as $key => $defeito) {

               $defeitos[$defeito->id] = strtoupper($defeito->descricao);
             
                     
        }

        return $defeitos;

    }

    public function salvarDefeito(Request $request){
        $fields = $request->only(['id_coletor','id_defeito']);

        
        try{
            $id = $fields['id_coletor'];
            if(!empty($fields['id_defeito'])){
                $id_defeito = $fields['id_defeito'];
            }else{
                $id_defeito =null;
            }
         
      
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
      
        $logColetor = LogColetor::find($id);
        $logColetor->produto_defeito_id =$id_defeito;
        $logColetor->save(); 

    }


    public function modalConsultaDefeito(Request $request){
        $estabelecimentos = returnTodasEmpresasView();
        set_time_limit(300);
        ini_set('memory_limit','1024M');
       $campos = $request->only('codigo_estabelecimento', 'numero_nota');


       $logColetor = LogColetor::select()->with('defeito')->where('alerta_erro',false);

       $logColetor->where('operacao', 'Guarda');
       $logColetor->whereNotNull('produto_defeito_id');
        if(!empty($campos['numero_nota'])){
            $logColetor->where('numero_nota', '=',$campos['numero_nota']);
        }

        $logColetors = $logColetor->get();
   
       
         $saida = [];
      
         $operacao='';
         $documento='';
         $total_geral=0;
         foreach($logColetors  as $log){
   
          

            $saida[] = [
                'id_coletor' =>$log->id,
                'colaborador' => $log->user_name,
                'documento' => $log->numero_nota,
                'produto_codigo' =>  $log->produto_codigo,
                'peca_codigo' =>  $log->peca_codigo,
                'produto_defeito_id' => empty($log->produto_defeito_id)? '' : Crypt::encrypt($log->produto_defeito_id),
                'data_hora' => parserData($log->created_at),
                'total_geral' => empty($total_geral)? '' : parserValor($total_geral),
                'defeito' =>  empty( $log->defeito->descricao)? 'Sem Defeito' :  $log->defeito->descricao,
     
            ];
        }

        $defeitos =$this->defeitos();

        return view('programs.consulta_coletor.modal.consulta')->with(['dados' => $saida ,'defeitos' => $defeitos]);
    }


}
