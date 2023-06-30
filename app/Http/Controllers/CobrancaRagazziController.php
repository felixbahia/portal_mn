<?php

namespace App\Http\Controllers;

use App\User;
use Carbon\Carbon;

use App\CenprotTitulo;
use App\ChequeNasajon;
use App\ClienteNasajon;
use App\PedidosPrePago;
use App\GrupoEmpresarial;
use Illuminate\Http\Request;
use App\VendedorTituloNasajon;
use App\TitulosEmAbertoNasajon;
use App\RenegociacaoTituloTitulo;
use App\TitulosVendedor998Nasajon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\TitulosEmAbertoNasajonPortal;
use Illuminate\Support\Facades\Crypt;

class CobrancaRagazziController extends Controller
{
   
    public function __construct() {
        $this->middleware(['auth']);
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\CobrancaRagazzi") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\CobrancaRagazzi');
        $gerentes = [];
        $vendedor_representante = [];

        $check_gerentes = false;
        $check_supervisores = false;
        $check_vendedor_representante = false;

        if(!in_array(Auth::user()->tipo_usuario_id, ["18","15", "1", "11", "20"])){
            $subordinadosObj = UserController::varreSubordinados(Auth::id());
            if(strtolower(Auth::user()->tipo_usuario_id) === "13"){
                $subordinadosObj = UserController::varreSubordinados(Auth::user()->responsavel);
            }

            $userObj = User::whereIn('id', $subordinadosObj)->get();
            if(Auth::user()->tipo_usuario_id == 19 && !empty(Auth::user()->codigo_representante)){
                $vendedor_representante[Crypt::encrypt(Auth::user()->id)] = strtoupper(Auth::user()->name);
            }

            foreach ($userObj as $key => $user) {
                if(strtolower($user->tipo_usuario_id) === "19"){
                    $gerentes[Crypt::encrypt($user->id)] = strtoupper($user->name);
                }else if(strtolower($user->tipo_usuario_id) === "16" && !empty($user->codigo_representante) || 
                strtolower($user->tipo_usuario_id) === "12" && !empty($user->codigo_representante) || $user->id == 1){
                    $vendedor_representante[Crypt::encrypt($user->id)] = strtoupper($user->name);
                }
            }

            unset($subordinadosObj);
        } else {
            $subordinadosObj = User::with(["tipo_usuario"])->get();
            foreach ($subordinadosObj as $key => $userObj) {
                if(strtolower($userObj->tipo_usuario_id) === "19"){
                    $gerentes[Crypt::encrypt($userObj->id)] = strtoupper($userObj->name);
                }else if(!empty($userObj->codigo_representante)){
                    $vendedor_representante[Crypt::encrypt($userObj->id)] = strtoupper($userObj->name);
                }
            }
            $gerentes[Crypt::encrypt(0)] = 'OUTROS';

            asort($vendedor_representante);
            unset($subordinadosObj);
        }

        if(strtolower(Auth::user()->tipo_usuario_id) == '19'){
            $check_vendedor_representante = true;
            $gerentes = [];
        }else if(strtolower(Auth::user()->tipo_usuario_id) == '13'){
            $check_vendedor_representante = true;
            $gerentes = [];
        }else if(
            strtolower(Auth::user()->tipo_usuario_id) !== "16" &&
            strtolower(Auth::user()->tipo_usuario_id) !== "12"
        ){
            $check_gerentes = true;
            $check_supervisores = true;
            $check_vendedor_representante = true;
        }
        
        $variaveis_view = [
            'gerentes'                      => $gerentes,
            'check_gerentes'                => $check_gerentes,
            'check_supervisores'            => $check_supervisores,
            'vendedor_representante'        => $vendedor_representante,
            'check_vendedor_representante'  => $check_vendedor_representante,
            'representantes'  => $vendedor_representante,
        ];
    
    	return view("programs.cobranca_ragazzi.index", $variaveis_view);
    }

    private function cnpjINtercompany(){
        $cnpj_excluir[] = '05075884000167';
        $cnpj_excluir[] = '05075884000248';
        $cnpj_excluir[] = '06311274000269';
        $cnpj_excluir[] = '06311274000340';
        $cnpj_excluir[] = '08';
        $cnpj_excluir[] = '07';
        $cnpj_excluir[] = '06311274000501';
        $cnpj_excluir[] = '06311274000420';
        return $cnpj_excluir;
    }
    public function filter(Request $request){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $fields  = $request->only('gerentes', 'vendedor_representante', 'cliente_nome','data_inicio','data_fim', 'titulos', 'cheque', 'titulos_pre', 'cheques_pre');
        $cnpj = $this->cnpjINtercompany();
        $empresas = returnTodasEmpresasView();

        if(!empty($fields['vendedor_representante'])){
            try{
                $vendedor = decrypt($fields['vendedor_representante']);
            }catch(Exception $e){
                $return = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                    'error' => '', 
                    'response' => '',
                ];
                return response()->json($return);
            }
            $representante = User::where('id',$vendedor)->whereNotNull('codigo_representante')->first()->codigo_representante;
        }
        
        $TitulosVendedor998NasajonObj = new TitulosVendedor998Nasajon;
        $titulosEmAbertoNasajonObj = new TitulosEmAbertoNasajonPortal;

        if((!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998') || (isset($representante) && $representante == '998')){
            $queryAbertos = 'with titulos as(
                select  titulo_id,codigo, saldotitulo, null as nota_id, numero, parcela, emissao as  titulo_emissao, vencimento, tem_prorrogacao, valor, multa, nome_cliente, juros, datainiciomulta, desconto, nossonumero, enviado_para_banco, banco_codigo, observacao
               ,origem_texto from ' . $TitulosVendedor998NasajonObj->getTable() . ' b
                where saldotitulo > 0  and situacao = \'Aberto\'';
        }else{
            $queryAbertos = 'with titulos as(
                select "titulo_id", "codigo", "saldotitulo", "nota_id", "numero", "parcela", "titulo_emissao", "vencimento", "tem_prorrogacao", "valor", "multa", "nome_cliente", "cnpj", "juros", "datainiciomulta", "desconto", "nossonumero", "enviado_para_banco", "banco_codigo", "observacao"
                ,origem_texto from ' . $titulosEmAbertoNasajonObj->getTable() . ' b
                where "saldotitulo" > 0 ';
        }



        $hoje = Carbon::now()->format('Y-m-d');

        $pedidosPreQuery = PedidosPrePago::with([
                'pedidoNasajon' => function($query){ $query->where('grupodeoperacao', 'VENDA'); },
                'pedidoNasajon.vendedor_detalhes',
                'pedidoNasajon.nota'
            ]);

       
         $data_inicio='';
        if(!empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
          
            $pedidosPreQuery->where('created_at', '>=', $data_inicio->format('Y-m-d'));
 
        }
        $data_fim='';
        if(!empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
         
            $pedidosPreQuery->where('created_at', '<=', $data_fim->format('Y-m-d'));
          
        }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\') AND cod_cliente not in (\''. implode('\',\'', $cnpj) . '\')';

  
        $pedidosPreObj = isset($fields['titulos_pre']) ? $pedidosPreQuery->get() : collect();
        $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use($fields){
            return !is_null($pedido->pedidoNasajon) && !is_null($pedido->pedidoNasajon->nota);
        });

   
        
        if(!empty($fields['vendedor_representante']) && (isset($representante) && $representante != '998')){

            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){

                $queryAbertos .= ' AND (vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';

                if(in_array('001', $representantes)){
                    $queryAbertos .= ' or vendedor_codigo is null';
                }

                $queryAbertos .= ')';
            }
            
 
        }
        else if(strtolower(Auth::user()->tipo_usuario_id) === "19" && empty($fields['vendedor_representante'])){
            $usuariogerente = Auth::user()->id;
            $representantes = User::where(function($query) use($usuariogerente){
                    $query->where('responsavel',$usuariogerente)
                        ->orWhere('id', Auth::id());
                })
                ->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
            }

 

            $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($representantes){
                return in_array($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes);
            });



        }else if(strtolower(Auth::user()->tipo_usuario_id) === "13" && empty($fields['vendedor_representante'])){
            $responsavel = Auth::user()->responsavel;
            if(empty($responsavel)){
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Nenhum registro encontrado',
                    'error' => '', 
                    'response' => ['response' => '', 'total' => ''],
                ]);
            }
            $buscagerente = User::find($responsavel);
            if($buscagerente->tipo_usuario_id != '19'){
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Nenhum registro encontrado',
                    'error' => '', 
                    'response' => ['response' => '', 'total' => ''],
                ]);
            }
            $representantes = User::where(function($query) use ($responsavel){
                    $query->where('responsavel',$responsavel)
                        ->orWhere('id', $responsavel);
                })
                ->whereNotNull('codigo_representante')
                ->get()
                ->pluck('codigo_representante')
                ->toArray();
            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
            }



            $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($representantes){
                return in_array($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes);
            });



        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
    
            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
            }



            $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($representantes){
                return in_array($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes);
            });


        }
        else if(!empty($fields['gerentes']) && empty($fields['vendedor_representante'])){
            try{
                $gerente = decrypt($fields['gerentes']);
            }catch(Exception $e){
                $return = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                    'error' => '', 
                    'response' => '',
                ];
                return response()->json($return);
            }
            if($gerente == 0){
                $gerentes = User::where('tipo_usuario_id', '19')->get()->pluck('id');

                $representantes = User::
                    whereIn('responsavel', $gerentes)
                    ->orWhereIn('id', $gerentes)
                    ->get()
                    ->pluck('codigo_representante')
                    ->toArray();
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                    $queryAbertos .= ' AND (vendedor_codigo not in(\'' . implode('\',\'', $representantes) . '\') or vendedor_codigo is null)';
                }


                $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($representantes){
                    return !in_array($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes) ||
                        empty($pedido->pedidoNasajon->vendedor_detalhes->codigo) ||
                        !isset($pedido->pedidoNasajon->vendedor_detalhes->codigo);
                });
    
  

            }
            else{
                $representantes = User::where(function($query) use ($gerente){
                    $query->where('responsavel', $gerente)
                        ->orWhere('id', $gerente);
                })
                ->whereNotNull('codigo_representante')
                ->get()
                ->pluck('codigo_representante')
                ->toArray();

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                    $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
                }

     

                $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($representantes){
                    return in_array($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes);
                });
    
     

            }
        }
        $clientecodigo = [];
        if(!empty($fields['cliente_nome']) ){
            $cliente = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'),'ilike', trim($fields['cliente_nome']))->first();
            
            $cpf_cnpj = (!empty($cliente)) ? $cliente->cpf_cnpj : null;
            if(!empty($cpf_cnpj)){
                if(strlen(trim($cpf_cnpj)) == 18){
                    $cpf_cnpj = substr($cpf_cnpj, 0, 10);
                }
                $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
                ->where('raiz_cnpj', $cpf_cnpj)
                ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                    $query->where('raiz_cnpj', $cpf_cnpj);
                })
                ->first();
                
                $clientesNasajonQuery = ClienteNasajon::select('nome', 'codigo', 'cpf_cnpj', 'id');

                if (!is_null($grupoEmpresarialObj)){
                    $clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
                        if(isset($grupoEmpresarialObj->participantes)){
                            foreach($grupoEmpresarialObj->participantes as $participante){
                                $query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
                            }
                        }
                        $query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
                    });
                }else{
                    $clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
                }
                $clientes = $clientesNasajonQuery->get();
                foreach($clientes as $cliente){
                    $clientecodigo[] = $cliente->codigo;
                }
                $queryAbertos .= ' AND cod_cliente in (\'' . implode('\',\'', $clientecodigo) . '\')';
            

                $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($clientes){
                    return $clientes->pluck('cpf_cnpj')->contains($pedido->pedidoNasajon->cliente_detalhes->cpf_cnpj);
                });
  

            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
        }

        if(!empty($fields['vendedor_representante']) && (isset($representante) && $representante != '998')){

            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            $queryAbertos .= ' AND (vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';

            if(in_array('001', $representantes)){
                $queryAbertos .= ' or vendedor_codigo is null';
            }

            $queryAbertos .= ')';
            


            $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($representantes){
                if(in_array('001', $representantes)){
                    return in_array($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes) || 
                        empty($pedido->pedidoNasajon->vendedor_detalhes->codigo) ||
                        !isset($pedido->pedidoNasajon->vendedor_detalhes->codigo);
                }
                else{
                    return in_array($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes);
                }
            });


        }

        $queryAbertos .= ')
            select *
                from titulos t';

        $TitulosAberto = collect(DB::connection('nasajon')->select($queryAbertos));

      
        $cenprot  =CenprotTitulo::select()->with(['titulosEmAbertoNasajon' => function($query) use ($clientecodigo,$data_inicio,$data_fim){
            $query->where('saldotitulo', '>',0);
            if(!empty($clientecodigo)){
                $query->whereIn('cod_cliente', $clientecodigo);
            }
            if(!empty($data_inicio)){
                $query->where('vencimento','>=',$data_inicio->format('Y-m-d'));
            }
            if(!empty($data_fim)){
                $query->where('vencimento','<=',$data_fim->format('Y-m-d'));
            }
            
        }]); 
         $cenprot->where('cenprot_status', '<>', 'PAGO');
        $cenprots =  $cenprot->get();
        $cenprot_titulos = $cenprots->pluck('titulo_id')->toArray();

        $renegociacaoTituloTitulo  =RenegociacaoTituloTitulo::select()->with(['detalhesRenegociacao' => function($query){
            $query->where('status_renegociacao_titulos_id',3);
             
        },'detalhesTituloAberto' => function($query) use ($clientecodigo,$data_inicio,$data_fim){
            $query->where('saldotitulo', '>',0);
            if(!empty($clientecodigo)){
                $query->whereIn('cod_cliente', $clientecodigo);
            }
            if(!empty($data_inicio)){
                $query->where('vencimento','>=',$data_inicio->format('Y-m-d'));
            }
            if(!empty($data_fim)){
                $query->where('vencimento','<=',$data_fim->format('Y-m-d'));
            }
            
        }]); 
   
        $renegociacaoTituloTitulos =  $renegociacaoTituloTitulo->get();
        $renegociacaoTitulos = $renegociacaoTituloTitulos->pluck('titulo_uuid_nasajon')->toArray();
        
        $retorno = [];
     
       
     
     
        $data = Carbon::now()->setTime(0, 0, 0);
     
        $coluna_total=0;
        if(isset($fields['titulos'])){
        foreach($TitulosAberto as $titulo){
          
            if(!in_array($titulo->titulo_id, $cenprot_titulos)){
          
                $dias_atrasos = "";
                $data_vencimento = Carbon::createFromFormat('Y-m-d',$titulo->vencimento)->setTime(0, 0, 0);
                  
                $coluna_total =$titulo->saldotitulo;
           
                $dias_atrasos =$data->diffInDays($data_vencimento);
                    $renegociado ='N';
                    if($titulo->origem_texto =='Renegociação' || in_array($titulo->titulo_id, $renegociacaoTitulos)){

                      array_push($renegociacaoTitulos,$titulo->titulo_id);
                        
                        if ($data_vencimento->lt($data)){
                           
                            $renegociado ='vencido';
                        }else{
                            $renegociado ='aberto';
                        }
                       
                    }

                    $judicial ='';
                    if($titulo->banco_codigo =='PROCESSOS JUDICIAIS RAGAZZI'){
                        $judicial =true;
        
                      
                       
                    }
                    if($data_vencimento->gte($data)){
                        $dias_atrasos =0;
           
                      
                    }
                 



                    
                    $retorno [] = [
                        'estabelecimento' => $titulo->codigo,
                        'aberto' => ($data_vencimento->gte($data)) ?  $titulo->saldotitulo : 0,
                        'vencido_1' =>$dias_atrasos  >=1 && $dias_atrasos  <=10 ?  $titulo->saldotitulo : 0,
                        'vencido_2' => $dias_atrasos  >=11 && $dias_atrasos  <=30 ?  $titulo->saldotitulo : 0,
                        'vencido_3' =>  $dias_atrasos  >=31 && $dias_atrasos  <=60 ?  $titulo->saldotitulo : 0,
                        'vencido_4' =>  $dias_atrasos  >60 ?  $titulo->saldotitulo : 0,
                        'cenprot' =>0,
                        'renegociado_aberto' => $renegociado =='aberto' ?  $titulo->saldotitulo : 0,
                        'renegociado_vencido' =>$renegociado =='vencido' ?  $titulo->saldotitulo : 0,
                        'cobranca_judicial' => !empty($judicial) ?  $titulo->saldotitulo : 0,
                        'total' =>  $coluna_total,
                    ];
      
    
        
             
             }     
            
        }
    }

        unset($TitulosAberto);



        foreach($cenprots as $tituloaberto){
            if(!empty($tituloaberto->titulosEmAbertoNasajon)){
                $titulo=   $tituloaberto->titulosEmAbertoNasajon;
             
            $dias_atrasos = "";
       
                
                $retorno [] = [
                    'estabelecimento' => $titulo->codigo,
                    'aberto' => 0,
                    'vencido_1' =>0,
                    'vencido_2' =>  0,
                    'vencido_3' => 0,
                    'vencido_4' => 0,
                    'cenprot' =>  $titulo->saldotitulo ,
                    'renegociado_aberto' =>  0,
                    'renegociado_vencido' => 0,
                    'cobranca_judicial' => 0,
                    'total' => $titulo->saldotitulo,
                ];
    
         
            }
        
    
}
    

  if(isset($fields['titulos_pre'])){

            $pedidosPreObj->each(function($pedidoPre) use (&$retorno){

                $data = Carbon::Now();
              
                $pago = $pedidoPre->lancamentos->sum("valor_pago");
                $valor_vencido = $pedidoPre->valor - $pago;

                if(empty($valor_vencido) || round($valor_vencido, 2) <= 0){
                    return null;
                }


                $retorno [] = [
                    'estabelecimento' => $pedidoPre->pedidoNasajon->estabelecimento_codigo,
                    'aberto' => 0,
                    'vencido_1' =>$valor_vencido,
                    'vencido_2' => 0,
                    'vencido_3' =>   0,
                    'vencido_4' =>   0,
                    'cenprot' => 0,
                    'renegociado_aberto' =>  0,
                    'renegociado_vencido' => 0,
                    'cobranca_judicial' =>  0,
                    'total' => $valor_vencido,
                ];


   
            });
        }

        unset($pedidosPreObj);
    


        $saida = [];
        $total = [
            'aberto' => 0,
            'vencido_1' =>  0,
            'vencido_2' =>  0,
            'vencido_3' => 0,
            'vencido_4' => 0,
            'cenprot' => 0,
            'renegociado_aberto' => 0,
            'renegociado_vencido' => 0,
            'cobranca_judicial' =>0,
            'total' => 0,
            'filter' => encrypt([
                'estabelecimento' => null,
                'gerentes' => (isset($fields['gerentes'])) ? $fields['gerentes'] : null,
                'vendedor_representante' => (isset($fields['vendedor_representante'])) ? $fields['vendedor_representante'] : null,
                'cliente_nome' => $fields['cliente_nome'],
                'data_inicio' => $fields['data_inicio'],
                'data_fim' => $fields['data_fim'],
                'titulos' =>  (isset($fields['titulos'])) ? true : null,
                'cheque' =>  (isset($fields['cheque'])) ? true : null,
                'titulos_pre' =>  (isset($fields['titulos_pre'])) ? true : null,
                'cheques_pre' =>  (isset($fields['cheques_pre'])) ? true : null,
                'cenprot_titulos' => $cenprot_titulos,
                'renegociacao_titulos' => $renegociacaoTitulos
         
            ])
        ];

        foreach($retorno as $resp){
            if(!isset($saida[$resp['estabelecimento']])){
                $saida[$resp['estabelecimento']] = [
                    'estabelecimento' => $empresas[$resp['estabelecimento']],
                    'aberto' => 0,
                    'vencido_1' =>  0,
                    'vencido_2' =>  0,
                    'vencido_3' => 0,
                    'vencido_4' => 0,
                    'cenprot' => 0,
                    'renegociado_aberto' => 0,
                    'renegociado_vencido' => 0,
                    'cobranca_judicial' =>0,
                    'total' => 0,
                    'filter' => encrypt([
                        'estabelecimento' => $resp['estabelecimento'],
                        'gerentes' => (isset($fields['gerentes'])) ? $fields['gerentes'] : null,
                        'vendedor_representante' => (isset($fields['vendedor_representante'])) ? $fields['vendedor_representante'] : null,
                        'cliente_nome' => $fields['cliente_nome'],
                        'data_inicio' => $fields['data_inicio'],
                        'data_fim' => $fields['data_fim'],
                        'titulos' =>  (isset($fields['titulos'])) ? true : null,
                        'cheque' =>  (isset($fields['cheque'])) ? true : null,
                        'titulos_pre' =>  (isset($fields['titulos_pre'])) ? true : null,
                        'cheques_pre' =>  (isset($fields['cheques_pre'])) ? true : null,
                        'cenprot_titulos' => $cenprot_titulos,
                        'renegociacao_titulos' => $renegociacaoTitulos
               
                    ]),
                ];
            }
            $saida[$resp['estabelecimento']]['aberto'] += $resp['aberto'];
            $saida[$resp['estabelecimento']]['vencido_1'] += $resp['vencido_1'];
            $saida[$resp['estabelecimento']]['vencido_2'] += $resp['vencido_2'];
            $saida[$resp['estabelecimento']]['vencido_3'] += $resp['vencido_3'];
            $saida[$resp['estabelecimento']]['vencido_4'] += $resp['vencido_4'];
            $saida[$resp['estabelecimento']]['cenprot'] += $resp['cenprot'];
            $saida[$resp['estabelecimento']]['renegociado_aberto'] += $resp['renegociado_aberto'];
            $saida[$resp['estabelecimento']]['renegociado_vencido'] += $resp['renegociado_vencido'];
            $saida[$resp['estabelecimento']]['cobranca_judicial'] += $resp['cobranca_judicial'];
            $saida[$resp['estabelecimento']]['total'] += $resp['total'];
            $total['aberto'] += $resp['aberto'];
            $total['vencido_1'] += $resp['vencido_1'];
            $total['vencido_2'] += $resp['vencido_2'];
            $total['vencido_3'] += $resp['vencido_3'];
            $total['vencido_4'] += $resp['vencido_4'];
            $total['cenprot'] += $resp['cenprot'];
            $total['renegociado_aberto'] += $resp['renegociado_aberto'];
            $total['renegociado_vencido'] += $resp['renegociado_vencido'];
            $total['cobranca_judicial'] += $resp['cobranca_judicial'];
            $total['total'] += $resp['total'];
        }

        unset($retorno);
        foreach($saida as $key => $row){
            $saida[$key]['aberto'] = ($row['aberto'] > 0) ? parserValor($row['aberto']) : '';
            $saida[$key]['vencido_1'] = ($row['vencido_1'] > 0) ? parserValor($row['vencido_1']) : '';
            $saida[$key]['vencido_2'] = ($row['vencido_2'] > 0) ? parserValor($row['vencido_2']) : '';
            $saida[$key]['vencido_3'] = ($row['vencido_3'] > 0) ? parserValor($row['vencido_3']) : '';
            $saida[$key]['vencido_4'] = ($row['vencido_4'] > 0) ? parserValor($row['vencido_4']) : '';
            $saida[$key]['cenprot'] = ($row['cenprot'] > 0) ? parserValor($row['cenprot']) : '';
            $saida[$key]['renegociado_aberto'] = ($row['renegociado_aberto'] > 0) ? parserValor($row['renegociado_aberto']) : '';
            $saida[$key]['renegociado_vencido'] = ($row['renegociado_vencido'] > 0) ? parserValor($row['renegociado_vencido']) : '';
            $saida[$key]['cobranca_judicial'] = ($row['cobranca_judicial'] > 0) ? parserValor($row['cobranca_judicial']) : '';
            $saida[$key]['total'] = ($row['total'] > 0) ? parserValor($row['total']) : '';
        }

        $total['aberto'] = ($total['aberto'] > 0) ? parserValor($total['aberto']) : '';
        $total['vencido_1'] = ($total['vencido_1'] > 0) ? parserValor($total['vencido_1']) : '';
        $total['vencido_2'] = ($total['vencido_2'] > 0) ? parserValor($total['vencido_2']) : '';
        $total['vencido_3'] = ($total['vencido_3'] > 0) ? parserValor($total['vencido_3']) : '';
        $total['vencido_4'] = ($total['vencido_4'] > 0) ? parserValor($total['vencido_4']) : '';
        $total['cenprot'] = ($total['cenprot'] > 0) ? parserValor($total['cenprot']) : '';
        $total['renegociado_aberto'] = ($total['renegociado_aberto'] > 0) ? parserValor($total['renegociado_aberto']) : '';
        $total['renegociado_vencido'] = ($total['renegociado_vencido'] > 0) ? parserValor($total['renegociado_vencido']) : '';
        $total['cobranca_judicial'] = ($total['cobranca_judicial'] > 0) ? parserValor($total['cobranca_judicial']) : '';
        $total['total'] = ($total['total'] > 0) ? parserValor($total['total']) : '';

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['response' => $saida, 'total' => $total],
        ]);
    }
    
    
   

    public function modalTitulosAbertura(Request $request){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $filter = $request->only(['filters','abertura','total']);

        $cnpj = $this->cnpjINtercompany();
        try{
            $fields = decrypt($filter['filters']);
       

           
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }
        
        if(!empty($fields['vendedor_representante'])){
            try{
                $vendedor = decrypt($fields['vendedor_representante']);
            }catch(Exception $e){
                $return = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                    'error' => '', 
                    'response' => '',
                ];
                return response()->json($return);
            }
            $representante = User::where('id',$vendedor)->whereNotNull('codigo_representante')->first()->codigo_representante;
        }
        
        $TitulosVendedor998NasajonObj = new TitulosVendedor998Nasajon;
        $titulosEmAbertoNasajonObj = new TitulosEmAbertoNasajonPortal;

        if((!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998') || (isset($representante) && $representante == '998')){
            $queryAbertos = 'with titulos as(
                select codigo, saldotitulo, null as nota_id, documento_numero as nota_numero, numero, parcela, emissao as titulo_emissao, vencimento, tem_prorrogacao, valor, multa, nome_cliente, cliente_cnpj as cnpj, juros, percentualjurosdiario, datainiciomulta, desconto, nossonumero, enviado_para_banco, banco_codigo, observacao, vencimento_original
                from ' . $TitulosVendedor998NasajonObj->getTable() . ' b
                where saldotitulo > 0 and situacao = \'Aberto\'';
        }else{
            $queryAbertos = 'with titulos as(
                select "codigo", "saldotitulo", "nota_id", "numero", "nota_numero", "parcela", "titulo_emissao", "vencimento", "tem_prorrogacao", "valor", "multa", "nome_cliente", "cnpj", "juros", "percentualjurosdiario", "datainiciomulta", "desconto", "nossonumero", "enviado_para_banco", "banco_codigo", "observacao", "vencimento_original"
                from ' . $titulosEmAbertoNasajonObj->getTable() . ' b
                where "saldotitulo" > 0 ';
        }
        


        $hoje = Carbon::now()->format('Y-m-d');

        $pedidosPreQuery = PedidosPrePago::with([
            'pedidoNasajon' => function($query){ $query->where('grupodeoperacao', 'VENDA'); },
            'pedidoNasajon.vendedor_detalhes',
            'pedidoNasajon.nota',
            'pedidoNasajon.cliente_detalhes'
    
        ]);


        
        if(!empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
           
            $pedidosPreQuery->where('created_at', '>=', $data_inicio->format('Y-m-d'));
        
        }
        
        if(!empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
           
            $pedidosPreQuery->where('created_at', '<=', $data_fim->format('Y-m-d'));
           
        }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\') AND cod_cliente not in (\''. implode('\',\'', $cnpj) . '\')';
       
         if(!empty($fields['cenprot_titulos']) && $filter['total'] == 'excluir_titulos'){
            $queryAbertos .= ' AND titulo_id not in (\''. implode('\',\'',$fields['cenprot_titulos']) . '\')';
        }
    
        $pedidosPreObj = !empty($fields['titulos_pre']) ? $pedidosPreQuery->get() : collect();
     

        $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use($fields){
            return !is_null($pedido->pedidoNasajon) && !is_null($pedido->pedidoNasajon->nota);
        });

        if(!empty($fields['estabelecimento'])){
            $queryAbertos .= ' AND codigo = \'' . $fields['estabelecimento'] . '\'';
          
            
            $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use($fields){
                return $pedido->pedidoNasajon->estabelecimento_codigo == $fields['estabelecimento'];
            });


        }
        
        $data = Carbon::now();


        if($filter['abertura'] == 'aberto'){    
            $queryAbertos .= ' and vencimento >= CURRENT_DATE';
        }
        if($filter['abertura'] == 'vencido_1'){ 
            $queryAbertos .= ' and concat((CURRENT_DATE - vencimento),\'days\')::interval  >=\'1 days\'::interval   and concat((CURRENT_DATE - vencimento),\'days\')::interval <=\'10 days\'::interval ';
           

        }
        if($filter['abertura'] == 'vencido_2'){ 
            $queryAbertos .= ' and concat((CURRENT_DATE - vencimento),\'days\')::interval >=\'11 days\'::interval   and concat((CURRENT_DATE - vencimento),\'days\')::interval <=\'30 days\'::interval';
          
    
        }
        if($filter['abertura'] == 'vencido_3'){ 
            $queryAbertos .= ' and concat((CURRENT_DATE - vencimento),\'days\')::interval >=\'31 days\'::interval   and concat((CURRENT_DATE - vencimento),\'days\')::interval <=\'60 days\'::interval' ;
           
    
        }
        if($filter['abertura'] == 'vencido_4'){ 
            $queryAbertos .= ' and concat((CURRENT_DATE - vencimento),\'days\')::interval >\'60 days\'::interval';
          
    
        }

     
        if($filter['abertura'] == 'cobranca_judicial'){ 
            $queryAbertos .=  ' and banco_codigo =\'PROCESSOS JUDICIAIS RAGAZZI\'';
        }
        $queryRenegocado='';
        if($filter['abertura'] == 'renegociado_aberto'){ 
            if(!empty($fields['renegociacao_titulos'])){
                $queryRenegocado .= ' AND titulo_id  in (\''. implode('\',\'',$fields['renegociacao_titulos']) . '\')';
                $queryRenegocado .= ' and ( \'' .  $hoje . '\' <= vencimento)';
             
            }else{
                $queryRenegocado .= ' and ( \'' .  $hoje . '\' <= vencimento)  and origem_texto =\'Renegociação\'';
            }
          
          
        }
        if($filter['abertura'] == 'renegociado_vencido'){ 
            if(!empty($fields['renegociacao_titulos'])){
                $queryRenegocado .= ' AND titulo_id  in (\''. implode('\',\'',$fields['renegociacao_titulos']) . '\')';
                $queryRenegocado .= ' and ( \'' . $hoje . '\' > vencimento)';
              
            }else{
                $queryRenegocado .= ' and ( \'' . $hoje . '\' > vencimento)  and origem_texto =\'Renegociação\'';

            }
           
        
        }
        
        if(!empty($queryRenegocado)){
            $queryAbertos .=  $queryRenegocado;
        }
   
        $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use($fields){
            $valor_vencido = $pedido->valor - $pedido->lancamentos->sum("valor_pago");
            return $valor_vencido > 0;
        });
            
        if(strtolower(Auth::user()->tipo_usuario_id) === "19" && empty($fields['vendedor_representante'])){
            $usuariogerente = Auth::user()->id;
            $representantes = User::where(function($query) use($usuariogerente){
                    $query->where('responsavel',$usuariogerente)
                        ->orWhere('id', Auth::id());
                })
                ->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
            
            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
            }



            $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($representantes){
                return in_array($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes);
            });



        }else if(strtolower(Auth::user()->tipo_usuario_id) === "13" && empty($fields['vendedor_representante'])){
            $responsavel = Auth::user()->responsavel;
            if(empty($responsavel)){
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Nenhum registro encontrado',
                    'error' => '', 
                    'response' => ['response' => '', 'total' => ''],
                ]);
            }
            $buscagerente = User::find($responsavel);
            if($buscagerente->tipo_usuario_id != '19'){
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Nenhum registro encontrado',
                    'error' => '', 
                    'response' => ['response' => '', 'total' => ''],
                ]);
            }
            $representantes = User::where(function($query) use ($responsavel){
                $query->where('responsavel',$responsavel)
                    ->orWhere('id', $responsavel);
            })
            ->whereNotNull('codigo_representante')
            ->get()
            ->pluck('codigo_representante')
            ->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
            }



            $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($representantes){
                return in_array($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes);
            });



        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
            }



            $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($representantes){
                return in_array($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes);
            });



        }

        if(!empty($fields['gerentes']) && empty($fields['vendedor_representante'])){
            try{
                $gerente = decrypt($fields['gerentes']);
            }catch(Exception $e){
                $return = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                    'error' => '', 
                    'response' => '',
                ];
                return response()->json($return);
            }
            if($gerente == 0){
                $gerentes = User::where('tipo_usuario_id', '19')->get()->pluck('id');

                $representantes = User::
                    whereIn('responsavel', $gerentes)
                    ->orWhereIn('id', $gerentes)
                    ->get()
                    ->pluck('codigo_representante')
                    ->toArray();

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                    $queryAbertos .= ' AND (vendedor_codigo not in(\'' . implode('\',\'', $representantes) . '\') or vendedor_codigo is null)';
                }


                $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($representantes){
                    return !in_array($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes) ||
                        empty($pedido->pedidoNasajon->vendedor_detalhes->codigo) ||
                        !isset($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes);
                });
    
  
            }
            else{
                $representantes = User::where(function($query) use ($gerente){
                    $query->where('responsavel', $gerente)
                        ->orWhere('id', $gerente);
                })
                ->whereNotNull('codigo_representante')
                ->get()
                ->pluck('codigo_representante')
                ->toArray();

                if(!empty($gerente_representante->codigo_representante)){
                    $representantes[] = $gerente_representante->codigo_representante;
                }

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                    $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
                }

    

                $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($representantes){
                    return in_array($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes);
                });
    


            }
        }
        else if(!empty($fields['vendedor_representante']) && (isset($representante) && $representante != '998')){
            try{
                $vendedor = decrypt($fields['vendedor_representante']);
            }catch(Exception $e){
                $return = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                    'error' => '', 
                    'response' => '',
                ];
                return response()->json($return);
            }
            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
            
            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
               $queryAbertos .= ' AND (vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
    
                if(in_array('001', $representantes)){
                    $queryAbertos = ' or vendedor_codigo is null';
                }
    
                $queryAbertos .= ')';
            }



            $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($representantes){
                return in_array($pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes) ||
                    empty($pedido->pedidoNasajon->vendedor_detalhes->codigo) ||
                    !isset($pedido->pedidoNasajon->vendedor_detalhes->codigo);
            });



        }

        if(!empty($fields['cliente_nome']) ){
            $cliente = ClienteNasajon::where(DB::raw('CONCAT(TRIM(nome), \' - \', cpf_cnpj)'),'ilike',$fields['cliente_nome'])->first();
            $clientecodigo = [];
            $cpf_cnpj = (!empty($cliente)) ? $cliente->cpf_cnpj : null;

            if(!empty($cpf_cnpj)){
                if(strlen(trim($cpf_cnpj)) == 18){
                    $cpf_cnpj = substr($cpf_cnpj, 0, 10);
                }
                $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
                ->where('raiz_cnpj', $cpf_cnpj)
                ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                    $query->where('raiz_cnpj', $cpf_cnpj);
                })
                ->first();
                $clientesNasajonQuery = ClienteNasajon::select('nome', 'codigo', 'cpf_cnpj', 'id');

                if (!is_null($grupoEmpresarialObj)){
                    $clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
                        if(isset($grupoEmpresarialObj->participantes)){
                            foreach($grupoEmpresarialObj->participantes as $participante){
                                $query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
                            }
                        }
                        $query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
                    });
                }else{
                    $clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
                }
                $clientes = $clientesNasajonQuery->get();
                foreach($clientes as $cliente){
                    $clientecodigo[] = $cliente->codigo;
                }
                $queryAbertos .= ' AND cod_cliente in (\'' . implode('\',\'', $clientecodigo) . '\')';
             
                $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use ($clientes){
                    return $clientes->pluck('cpf_cnpj')->contains($pedido->pedidoNasajon->cliente_detalhes->cpf_cnpj);
                });
    

            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
        }


  

        $queryAbertos .= ')
            select *
                from titulos t';


                $TitulosAberto = (!empty($fields['titulos'])) ? collect(DB::connection('nasajon')->select($queryAbertos)): null;
       
        $estabelecimentos = returnTodasEmpresasView();
        $cnpjCliente = [];
        $cnpjCliente[] = ['cnpj' =>  'cnpj'];
        $cnpjCliente[] = ['result' =>  '2'];
        $retorno = [];

        
        if(!empty($fields['titulos'])){
            foreach($TitulosAberto as $titulo){
                $linha = [];
                $dias_atrasos =$data->diffInDays($titulo->vencimento);
                $linha['estabelecimento'] = $estabelecimentos[$titulo->codigo];
                $linha['nota_numero'] = (!empty($titulo->nota_numero)) ? $titulo->nota_numero : '';
                $linha['parcela'] = $titulo->parcela;
                $linha['data_emissao'] = $titulo->titulo_emissao;
                $linha['data_vencimento'] = parserData($titulo->vencimento);
                $linha['data_vencimento_sql'] = $titulo->vencimento;
                $linha['valor_original'] = $titulo->valor;
                if($titulo->tem_prorrogacao === true ){
                    $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($titulo->vencimento_original).'"> *</a>';
                }
                $linha['valor'] = $titulo->saldotitulo;
                $linha['multa'] = $titulo->multa;
                $linha['nome_cliente'] = $titulo->nome_cliente. ' - ' .$titulo->cnpj;
                $linha['numero'] = $titulo->numero;
                $linha['cheque'] = $dias_atrasos;
                $linha['juros_cobrados'] = $titulo->juros;
                $linha['percentual_juros_diarios'] = $titulo->percentualjurosdiario;
                $linha['data_juros'] = $titulo->datainiciomulta;
                $linha['desconto'] = $titulo->desconto;
                $linha['POSICAO_CR'] = '';
                $linha['POSICAO_CR_DESCRICAO'] = $titulo->nossonumero;
                $linha['banco'] = ($titulo->enviado_para_banco == true) ? $titulo->banco_codigo : 'CARTEIRA';
                $linha['observacao'] = $titulo->observacao;

                $retorno[] = $linha;
            }
        }

        if(!empty($fields['titulos_pre'])){
            $pedidosPreObj->each(function($pedido) use(&$retorno, $estabelecimentos)
            {

                $data = Carbon::Now();

                $valor_pago = $pedido->lancamentos->sum("valor_pago");

                if(round($pedido->valor - $valor_pago, 2) <= 0){
                    return null;
                }

                $linha = [];

                $linha['estabelecimento'] = $estabelecimentos[$pedido->pedidoNasajon->estabelecimento_codigo];
                $linha['numero'] = $pedido->id;
                $linha['cheque'] = '';
                $linha['parcela'] = '';
                $linha['data_vencimento'] = '';
                $linha['data_vencimento_sql'] = '';
                $linha['data_emissao'] = $pedido->created_at;
                $linha['valor_original'] = $pedido->valor;
                $linha['valor'] = $pedido->valor - $valor_pago;
                $linha['multa'] = '';
                $linha['nome_cliente'] = $pedido->pedidoNasajon->cliente_detalhes->nome . ' - ' . $pedido->pedidoNasajon->cliente_detalhes->cpf_cnpj;
                $linha['nota_numero'] = $pedido->pedidoNasajon->nota->numero??'';
                $linha['juros_cobrados'] = '';
                $linha['percentual_juros_diarios'] = '';
                $linha['data_juros'] = '';
                $linha['desconto'] = '';
                $linha['POSICAO_CR'] = '';
                $linha['POSICAO_CR_DESCRICAO'] = '';
                $linha['banco'] =  'CARTEIRA - PEDIDO PRÉ';
                $linha['observacao'] = $pedido->pedidoNasajon->observacao;

                $retorno[] = $linha;
            });
        }


        $valor = 0;
        $saldo = 0;
        $juros = 0;

        if(!empty($fields['titulos'])){
            $valor += $TitulosAberto->sum('valor');
            $saldo += $TitulosAberto->sum('saldotitulo');
            $juros += $TitulosAberto->sum('juros');
        }



        if(!empty($fields['titulos_pre'])){
            $valor += $pedidosPreObj->sum('valor');

            $data = Carbon::Now();

            $valor_pago = $pedidosPreObj->pluck('lancamentos')->flatten()->sum("valor_pago");

            $saldo += $pedidosPreObj->sum('valor') - $valor_pago;
        }
        


        unset($TitulosAberto);
       
        unset($pedidosPreObj);
   
        $total = [
            'valor' => ($valor > 0) ? parserValor($valor) : '',
            'saldo' => ($saldo > 0) ? parserValor($saldo) : '',
            'juros' => ($juros > 0) ? parserValor($juros) : '',
        ];
           
        return view('programs.cobranca_ragazzi.modal.titulos')->with(["dados"=> $retorno,"cod_cliente" => $cnpjCliente, "totalizadores" => $total]);
    }


    public function modalTitulosCenprot(Request $request){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $filter = $request->only(['filters','abertura','total']);

        $cnpj = $this->cnpjINtercompany();
        try{
            $fields = decrypt($filter['filters']);

  
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }
        
        if(!empty($fields['vendedor_representante'])){
            try{
                $vendedor = decrypt($fields['vendedor_representante']);
            }catch(Exception $e){
                $return = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                    'error' => '', 
                    'response' => '',
                ];
                return response()->json($return);
            }
            $representante = User::where('id',$vendedor)->whereNotNull('codigo_representante')->first()->codigo_representante;
        }
        
   

        if((!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998') || (isset($representante) && $representante == '998')){
            $queryAbertos =  TitulosVendedor998Nasajon::select();    
            $queryAbertos->with(['cenprotTitulos' => function($query){
                $query->where('cenprot_status', '<>', 'PAGO');
            }]); 
            $queryAbertos->where('saldotitulo', '>',0);
            $queryAbertos->where('situacao','Aberto');
         
        }else{
            $queryAbertos =  TitulosEmAbertoNasajon::select();    
            $queryAbertos->with(['cenprot' => function($query){
                $query->where('cenprot_status', '<>', 'PAGO');
            }]); 
            $queryAbertos->where('saldotitulo', '>',0);
        
        }
    


        $hoje = Carbon::now()->format('Y-m-d');




        
        if(!empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
  
            $queryAbertos->where('vencimento', '>=', $data_inicio->format('Y-m-d') );
  
        
        }
        
        if(!empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);

            $queryAbertos->where('vencimento', '<=', $data_inicio->format('Y-m-d') );
           
        }

     

        $queryAbertos->whereNotIn('codigo',['25','20','TREINAMENTO']);
        $queryAbertos->whereNotIn('cod_cliente', $cnpj );


     
        if(!empty($fields['estabelecimento'])){
     

            $queryAbertos->where('codigo', '=', $fields['estabelecimento'] );
           
        }

        
        $data = Carbon::now();


            
        if(strtolower(Auth::user()->tipo_usuario_id) === "19" && empty($fields['vendedor_representante'])){
            $usuariogerente = Auth::user()->id;
            $representantes = User::where(function($query) use($usuariogerente){
                    $query->where('responsavel',$usuariogerente)
                        ->orWhere('id', Auth::id());
                })
                ->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
            
            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
               
                $queryAbertos->whereIn('vendedor_codigo', $representantes );
            }





        }else if(strtolower(Auth::user()->tipo_usuario_id) === "13" && empty($fields['vendedor_representante'])){
            $responsavel = Auth::user()->responsavel;
            if(empty($responsavel)){
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Nenhum registro encontrado',
                    'error' => '', 
                    'response' => ['response' => '', 'total' => ''],
                ]);
            }
            $buscagerente = User::find($responsavel);
            if($buscagerente->tipo_usuario_id != '19'){
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Nenhum registro encontrado',
                    'error' => '', 
                    'response' => ['response' => '', 'total' => ''],
                ]);
            }
            $representantes = User::where(function($query) use ($responsavel){
                $query->where('responsavel',$responsavel)
                    ->orWhere('id', $responsavel);
            })
            ->whereNotNull('codigo_representante')
            ->get()
            ->pluck('codigo_representante')
            ->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
              
                $queryAbertos->whereIn('vendedor_codigo', $representantes );
            }





        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
          
                $queryAbertos->whereIn('vendedor_codigo', $representantes );
            }




        }

        if(!empty($fields['gerentes']) && empty($fields['vendedor_representante'])){
            try{
                $gerente = decrypt($fields['gerentes']);
            }catch(Exception $e){
                $return = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                    'error' => '', 
                    'response' => '',
                ];
                return response()->json($return);
            }
            if($gerente == 0){
                $gerentes = User::where('tipo_usuario_id', '19')->get()->pluck('id');

                $representantes = User::
                    whereIn('responsavel', $gerentes)
                    ->orWhereIn('id', $gerentes)
                    ->get()
                    ->pluck('codigo_representante')
                    ->toArray();

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                    
                     $queryAbertos->orwhereNotIn('vendedor_codigo', $representantes );
                     $queryAbertos->whereNull('vendedor_codigo');
                }


   
  
            }
            else{
                $representantes = User::where(function($query) use ($gerente){
                    $query->where('responsavel', $gerente)
                        ->orWhere('id', $gerente);
                })
                ->whereNotNull('codigo_representante')
                ->get()
                ->pluck('codigo_representante')
                ->toArray();

                if(!empty($gerente_representante->codigo_representante)){
                    $representantes[] = $gerente_representante->codigo_representante;
                }

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                  
                    $queryAbertos->whereIn('vendedor_codigo', $representantes );
                }

    



            }
        }
        else if(!empty($fields['vendedor_representante']) && (isset($representante) && $representante != '998')){
            try{
                $vendedor = decrypt($fields['vendedor_representante']);
            }catch(Exception $e){
                $return = [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                    'error' => '', 
                    'response' => '',
                ];
                return response()->json($return);
            }
            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
            
            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
             
               $queryAbertos->orwhereIn('vendedor_codigo', $representantes );
                if(in_array('001', $representantes)){
                  
                    $queryAbertos->whereNull('vendedor_codigo');
                }
    
                $queryAbertos .= ')';
            }






        }

        if(!empty($fields['cliente_nome']) ){
            $cliente = ClienteNasajon::where(DB::raw('CONCAT(TRIM(nome), \' - \', cpf_cnpj)'),'ilike',$fields['cliente_nome'])->first();
            $clientecodigo = [];
            $cpf_cnpj = (!empty($cliente)) ? $cliente->cpf_cnpj : null;

            if(!empty($cpf_cnpj)){
                if(strlen(trim($cpf_cnpj)) == 18){
                    $cpf_cnpj = substr($cpf_cnpj, 0, 10);
                }
                $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
                ->where('raiz_cnpj', $cpf_cnpj)
                ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                    $query->where('raiz_cnpj', $cpf_cnpj);
                })
                ->first();
                $clientesNasajonQuery = ClienteNasajon::select('nome', 'codigo', 'cpf_cnpj', 'id');

                if (!is_null($grupoEmpresarialObj)){
                    $clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
                        if(isset($grupoEmpresarialObj->participantes)){
                            foreach($grupoEmpresarialObj->participantes as $participante){
                                $query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
                            }
                        }
                        $query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
                    });
                }else{
                    $clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
                }
                $clientes = $clientesNasajonQuery->get();
                foreach($clientes as $cliente){
                    $clientecodigo[] = $cliente->codigo;
                }
             
                $queryAbertos->whereIn('cod_cliente',$clientecodigo );
             
  
    

            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
        }




                $TitulosAberto = $queryAbertos->get();
       
        $estabelecimentos = returnTodasEmpresasView();
        $cnpjCliente = [];
        $cnpjCliente[] = ['cnpj' =>  'cnpj'];
        $cnpjCliente[] = ['result' =>  '2'];
        $retorno = [];
        $total_valor_titulo = 0;
        $total_valor_juro =0;
        $total_valor_saldo = 0;
        
        if(!empty($fields['titulos'])){
            foreach($TitulosAberto as $titulo){

                if( !empty($titulo->cenprot) ||  !empty($titulo->cenprotTitulos) ){
                $linha = [];

                $linha['estabelecimento'] = $estabelecimentos[$titulo->codigo];
                $linha['nota_numero'] = (!empty($titulo->nota_numero)) ? $titulo->nota_numero : '';
                $linha['parcela'] = $titulo->parcela;
                $linha['data_emissao'] = $titulo->titulo_emissao;
                $linha['data_vencimento'] = parserData($titulo->vencimento);
                $linha['data_vencimento_sql'] = $titulo->vencimento;
                $linha['valor_original'] = $titulo->valor;
                if($titulo->tem_prorrogacao === true ){
                    $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($titulo->vencimento_original).'"> *</a>';
                }
                $linha['valor'] = $titulo->saldotitulo;
                $linha['multa'] = $titulo->multa;
                $linha['nome_cliente'] = $titulo->nome_cliente. ' - ' .$titulo->cnpj;
                $linha['numero'] = $titulo->numero;
                $linha['cheque'] = '';
                $linha['juros_cobrados'] = $titulo->juros;
                $linha['percentual_juros_diarios'] = $titulo->percentualjurosdiario;
                $linha['data_juros'] = $titulo->datainiciomulta;
                $linha['desconto'] = $titulo->desconto;
                $linha['POSICAO_CR'] = '';
                $linha['POSICAO_CR_DESCRICAO'] = $titulo->nossonumero;
                $linha['banco'] = ($titulo->enviado_para_banco == true) ? $titulo->banco_codigo : 'CARTEIRA';
                $linha['observacao'] = $titulo->observacao;

                $retorno[] = $linha;

                $total_valor_titulo += $titulo->valor;
                $total_valor_juro += $titulo->juros;
                $total_valor_saldo += $titulo->saldotitulo;
            }
            }
        }




        $valor = 0;
        $saldo = 0;
        $juros = 0;

     
            $valor +=  $total_valor_titulo;
            $saldo += $total_valor_saldo;
            $juros += $total_valor_juro ;
      





        unset($TitulosAberto);

   
        $total = [
            'valor' => ($valor > 0) ? parserValor($valor) : '',
            'saldo' => ($saldo > 0) ? parserValor($saldo) : '',
            'juros' => ($juros > 0) ? parserValor($juros) : '',
        ];
           
        return view('programs.cobranca_ragazzi.modal.titulos')->with(["dados"=> $retorno,"cod_cliente" => $cnpjCliente, "totalizadores" => $total]);
    }

}