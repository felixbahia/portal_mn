<?php

namespace App\Http\Controllers;

use App\User;
use Exception;
use App\BaixaTitulo;

use App\TitulosPagosNasajon;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\CondicoesPagamentoNasajon;
use App\ContasReceberBaixadoNasajon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class AnaliseContasReceberController extends Controller
{
    private $estabelecimentos = [];

    public function __construct() {
        $this->middleware(['auth']);
        $estabelecimentosEmpty[''] = 'ESTABELECIMENTO';
        $estabelecimentosReturn = returnEmpresasNasajonView();
        unset($estabelecimentosReturn[20]);
        $estabelecimentos = array_merge($estabelecimentosEmpty,$estabelecimentosReturn );
        $this->estabelecimentos = $estabelecimentos;
    }

    private function ReturnBancos(){
        $bancos = ContasReceberBaixadoNasajon::selectRaw('distinct banco_nome as banco')->where('banco_nome', '<>',null)->get();
        $returnBancos = [];
        $returnBancos[''] = 'BANCOS';
        foreach($bancos as $banco){
            $returnBancos[$banco->banco] = strtoupper($banco->banco);
        }
        $returnBancos['NULL'] = 'CARTEIRA';
        $banco = array_merge($returnBancos, $returnBancos);
        ksort($banco);
        return $banco;
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AnaliseContasReceber") === false){
            return abort(403);
        }
        $estabelecimentos = $this->estabelecimentos;
        $bancos = $this->ReturnBancos();
        $request->session()->flash('model', 'App\AnaliseContasReceber');
        $data_filtro = $this->dataFiltro();

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
                strtolower($user->tipo_usuario_id) === "12" && !empty($user->codigo_representante) || $user->id == 1 || strtolower(Auth::user()->tipo_usuario_id) === "13" && strtolower($user->tipo_usuario_id) === "13"){
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

     
    	return view("programs.analise_contas_receber.index", $variaveis_view)->with(['estabelecimentos' => $estabelecimentos, 'bancos' => $bancos, 'data_filtro' => $data_filtro]);
    }


    private function ReturnTitulos($usuariosRagazzi){
        $titulos = BaixaTitulo::select('titulo_nasajon_id','created_by')->wherein('created_by',$usuariosRagazzi)->get();
        $returnTitulos = [];
        foreach($titulos as $titulo){
            $returnTitulos[] = $titulo->titulo_nasajon_id;
        }

        //$banco = array_merge($returnBancos, $returnBancos);
        //ksort($banco);
        return $returnTitulos;
    }

    public function filter(Request $request){
        set_time_limit(200);
        ini_set('memory_limit','1024M');
        $fields = $request->only(['estabelecimento','banco', 'data_inicio', 'data_fim', 'armazen', 'intercompany','ragazzi','data_filtro','vendedor_representante','gerentes']);
    


        if(!empty($fields['armazen'])){
            if($fields['armazen'] == "false"){
                $fields['armazen'] = null;
            }
        }
        if(!empty($fields['intercompany'])){
            if($fields['intercompany'] == "false"){
                $fields['intercompany'] = null;
            }
        }

        $usuRagazzi = [];
        $titulosRagazzi=null;
        if(!empty($fields['ragazzi'])){
            if($fields['ragazzi'] == "false"){
                $fields['ragazzi'] = null;
            }else{
                $usuRagazzi[] = '1377';
                $usuRagazzi[] = '1384';
                $usuRagazzi[] = '1386';
                $usuRagazzi[] = '1387';
                $usuRagazzi[] = '1388';
                $usuRagazzi[] = '1397';
                $usuRagazzi[] = '1398';
                $usuRagazzi[] = '1442';
                $usuRagazzi[] = '1464';
                $usuRagazzi[] = '8313';

                $titulosRagazzi = $this->ReturnTitulos($usuRagazzi);
            }
        }

        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);

        if(!empty($fields['data_filtro'])) {
              $campo_filtro=$fields['data_filtro'];
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
        if(!empty($fields['gerentes'])){
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
    }
           $TitulosAReceberNasajonObj = ContasReceberBaixadoNasajon::with('baixarPortal');
             


        $condicoesPagamentoNasajonObj = new CondicoesPagamentoNasajon;
        if(count($usuRagazzi) > 0){
          
            
            if($fields['data_filtro'] =='vencimento') {
                 $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
            }else{
                $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
            }

            $TitulosAReceberNasajonObj->whereNotNull('data_pagamento')
                        ->where('codigo', '<>', '25')
            ->orderBy('banco_nome');
        }else{
       
            if($fields['data_filtro'] =='vencimento') {
                 $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
            }else{
                $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
            }
      
            $TitulosAReceberNasajonObj->join($condicoesPagamentoNasajonObj->getTable(), 'integracoes.vw_df_formaspagamentos.id_docfis', 'documento_id')
            ->whereNotNull('data_pagamento')
            ->whereNotIn('formapagamento_descricao', ['Dinheiro', 'Cartão Crédito', 'Cartão Débito', 'Deposito em conta', 'Usar Crédito'])
            ->where('codigo', '<>', '25')
            ->orderBy('banco_nome');
      
        }
           
        if(isset($fields['estabelecimento'])){
            $TitulosAReceberNasajonObj->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        
        if(isset($fields['banco']) && $fields['banco'] != 'NULL'){
            $TitulosAReceberNasajonObj->where('banco_nome', $fields['banco']);
        }
    


        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && $fields['estabelecimento'] != 20){
            $TitulosAReceberNasajonObj->where('codigo', "!=", 20);
        }
        if(!isset($fields['intercompany'])){
            $cnpj_excluir[] = '05075884000167';
            $cnpj_excluir[] = '05075884000248';
            $cnpj_excluir[] = '06311274000269';
            $cnpj_excluir[] = '06311274000340';
            $cnpj_excluir[] = '08';
            $cnpj_excluir[] = '07';
            $cnpj_excluir[] = '06311274000501';
            $cnpj_excluir[] = '06311274000420';
        }
        if(count($cnpj_excluir) > 0){
            $TitulosAReceberNasajonObj->whereNotIn("cod_cliente", $cnpj_excluir);
        }

        if(count($usuRagazzi) > 0){
            $TitulosAReceberNasajonObj->whereIn("id_titulo", $titulosRagazzi);
        }


            if(!empty($fields['vendedor_representante']) && (isset($representante) && $representante != '998')){

                $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){

                    $TitulosAReceberNasajonObj->whereHas('revisao_vendedor_comissao', function($query) use($representantes){
                        $query->whereIn('vendedor_codigo', $representantes);
                        if(in_array('001', $representantes)){
                            $query->whereOrNull('vendedor_codigo');
                        }
                    });
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
                    $TitulosAReceberNasajonObj->whereHas('revisao_vendedor_comissao', function($query) use($representantes){
                        $query->whereIn('vendedor_codigo', $representantes);
                    });
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
                
                    $TitulosAReceberNasajonObj->whereHas('revisao_vendedor_comissao', function($query) use($representantes){
                        $query->whereIn('vendedor_codigo', $representantes);
                    });
                }



            }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
                strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
                $userid = Auth::user()->id;
                $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
        
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){

                    $TitulosAReceberNasajonObj->whereHas('revisao_vendedor_comissao', function($query) use($representantes){
                        $query->whereIn('vendedor_codigo', $representantes);
                    });
                }

            }           
            else if(!empty($fields['gerentes']) && empty($fields['vendedor_representante'])){

                if($gerente == 0){
                    $gerentes = User::where('tipo_usuario_id', '19')->get()->pluck('id');

                    $representantes = User::
                        whereIn('responsavel', $gerentes)
                        ->orWhereIn('id', $gerentes)
                        ->get()
                        ->pluck('codigo_representante')
                        ->toArray();
                    if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                  
                        $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                        $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');

                        $TitulosAReceberNasajonObj->whereHas('revisao_vendedor_comissao', function($query) use($representantes){
                            $query->whereIn('vendedor_codigo', $representantes);
                            $query->whereOrNull('vendedor_codigo');
                        });
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

                    if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                    
                        $TitulosAReceberNasajonObj->whereHas('revisao_vendedor_comissao', function($query) use($representantes){
                            $query->whereIn('vendedor_codigo', $representantes);
                        });
                    }




                }
            }
         
    $contas_a_receber_nasajon = $TitulosAReceberNasajonObj->get();
           
    $response = [];

    foreach ($contas_a_receber_nasajon as $entrada) {
        $idTitulo     = $entrada->id_titulo;
        
        $dateVencimento = Carbon::createFromFormat('Y-m-d',$entrada->vencimento);
     
            $datePagamento = Carbon::createFromFormat('Y-m-d',$entrada->data_pagamento);


        $devolucao = ($entrada->valor <= 0) ? $entrada->valor_titulo : '0';
        if($dateVencimento->dayOfWeekIso == 6){ 
            $dateVencimento->addDays(2);
        }
        if($dateVencimento->dayOfWeekIso == 7){ 
            $dateVencimento->addDays(1);
        }

        $dias_atrasos =$datePagamento->diffInDays($dateVencimento);
        if($dateVencimento->gt($datePagamento)){
            $dias_atrasos =0;
        }
        $multa =0;
        if(!empty($entrada->baixarPortal)){
            $multa =$entrada->baixarPortal->multa;
        
        }
       
        $valorPago = ($entrada->valor < $entrada->valor_titulo) ? $entrada->valor : $entrada->valor_titulo;
        if(!isset($response[$entrada->banco_nome] )){
            $response[$entrada->banco_nome] = [
                'banco' => (isset($entrada->banco_nome)) ? $entrada->banco_nome : 'CARTEIRA',
                'valor_principal' => 0,
                'desconto' => 0,
                'devolucao' => 0,
                'liquidacoes_cartorio' => 0,
                'liquidacoes_vencido_1' => 0,
                'liquidacoes_vencido_2' => 0,
                'liquidacoes_vencido_3' => 0,
                'titulos_dia' => 0,
                'liquidacoes_antecipadas' => 0,
                'juros' => 0,
                'multa' => 0,
                'valor_total' => 0,
                'filter' => encrypt([
                    'estabelecimento' => (isset($fields['estabelecimento'])) ? $fields['estabelecimento'] : null,
                    'banco' => $entrada->banco_nome,
                    'data_inicio' => $fields['data_inicio'],
                    'data_fim' => $fields['data_fim'],
                    'armazen' => (isset($fields['armazen'])) ? $fields['armazen'] : null,
                    'intercompany' => (isset($fields['intercompany'])) ? $fields['intercompany'] : null,
                    'ragazzi' => (isset($fields['ragazzi'])) ? $fields['ragazzi'] : null,
                    'data_filtro' =>$fields['data_filtro'],
                    'vendedor_representante' => (isset($fields['vendedor_representante'])) ? $fields['vendedor_representante'] : null,
                    'gerentes'  => (isset($fields['gerentes'])) ? $fields['gerentes'] : null

          
                ]),
            ];    
        }
        $response[$entrada->banco_nome]['valor_principal'] += $entrada->valor_titulo;
        $response[$entrada->banco_nome]['desconto'] += $entrada->valordesconto;
        $response[$entrada->banco_nome]['devolucao'] += $devolucao;
        $response[$entrada->banco_nome]['liquidacoes_vencido_1'] += $dias_atrasos  >=1 && $dias_atrasos  <=10  && $entrada->valor > 0 ?  $entrada->valor_titulo : '0';
        $response[$entrada->banco_nome]['liquidacoes_vencido_2'] +=  $dias_atrasos  >=11 && $dias_atrasos  <=30  && $entrada->valor > 0?  $entrada->valor_titulo : '0';
        $response[$entrada->banco_nome]['liquidacoes_vencido_3'] +=   $dias_atrasos  >30 && $entrada->valor > 0?  $entrada->valor_titulo : '0';
        $response[$entrada->banco_nome]['titulos_dia'] += ($dateVencimento->eq($datePagamento) && $entrada->valor > 0) ? $entrada->valor_titulo : '0';
        $response[$entrada->banco_nome]['liquidacoes_antecipadas'] += ($dateVencimento->gt($datePagamento) && $entrada->valor > 0) ? $entrada->valor_titulo : '0';
        $response[$entrada->banco_nome]['juros'] += $entrada->valorjuros;
        $response[$entrada->banco_nome]['multa'] += $multa;
        $response[$entrada->banco_nome]['valor_total'] += (($entrada->valor_titulo + $entrada->valorjuros) - $entrada->valordesconto - $devolucao);
    }

    $saida = ['valor_principal' => 0,
            'desconto' => 0,
            'devolucao' => 0,
            'liquidacoes_cartorio' => 0,
            'liquidacoes_vencido_1' => 0,
            'liquidacoes_vencido_2' => 0,
            'liquidacoes_vencido_3' => 0,
            'titulos_dia' => 0,
            'liquidacoes_antecipadas' => 0,
            'juros' => 0,
            'multa' => 0,
            'valor_total' => 0
            ];

            foreach($response as $key => $resp){
                $response[$key]['banco'] = $resp['banco'];
                $response[$key]['valor_principal'] = ($resp['valor_principal'] != '0') ? parserValor($resp['valor_principal']) : '';
                $response[$key]['desconto'] = ($resp['desconto'] != '0') ? parserValor($resp['desconto']) : '';
                $response[$key]['devolucao'] = ($resp['devolucao'] > 0) ? parserValor($resp['devolucao']) : '';
                $response[$key]['liquidacoes_cartorio'] = ($resp['liquidacoes_cartorio'] != '0') ? parserValor($resp['liquidacoes_cartorio']) : '';
                $response[$key]['liquidacoes_vencido_1'] = ($resp['liquidacoes_vencido_1'] != '0') ? parserValor($resp['liquidacoes_vencido_1']) : '';
                $response[$key]['liquidacoes_vencido_2'] = ($resp['liquidacoes_vencido_2'] != '0') ? parserValor($resp['liquidacoes_vencido_2']) : '';
                $response[$key]['liquidacoes_vencido_3'] = ($resp['liquidacoes_vencido_3'] != '0') ? parserValor($resp['liquidacoes_vencido_3']) : '';
                $response[$key]['titulos_dia'] = ($resp['titulos_dia'] != '0') ? parserValor($resp['titulos_dia']) : '';
                $response[$key]['liquidacoes_antecipadas'] = ($resp['liquidacoes_antecipadas'] != '0') ? parserValor($resp['liquidacoes_antecipadas']) : '';
                $response[$key]['juros'] = ($resp['juros'] != '') ? parserValor($resp['juros']) : '';
                $response[$key]['multa'] = ($resp['multa'] != '') ? parserValor($resp['multa']) : '';
                $response[$key]['valor_total'] = ($resp['valor_total'] != '0') ? parserValor($resp['valor_total']) : '';
                $saida['valor_principal'] += $resp['valor_principal'];
                $saida['desconto'] += $resp['desconto'];
                $saida['devolucao'] += $resp['devolucao'];
                $saida['liquidacoes_cartorio'] += $resp['liquidacoes_cartorio'];
                $saida['liquidacoes_vencido_1'] += $resp['liquidacoes_vencido_1'];
                $saida['liquidacoes_vencido_2'] += $resp['liquidacoes_vencido_2'];
                $saida['liquidacoes_vencido_3'] += $resp['liquidacoes_vencido_3'];
                $saida['titulos_dia'] += $resp['titulos_dia'];
                $saida['liquidacoes_antecipadas'] += $resp['liquidacoes_antecipadas'];
                $saida['juros'] += $resp['juros'];
                $saida['multa'] += $resp['multa'];
                $saida['valor_total'] += $resp['valor_total'];
            }

            $saida['valor_principal'] = ($saida['valor_principal'] != '0') ? parserValor($saida['valor_principal']) : '';
            $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';
            $saida['devolucao'] = ($saida['devolucao'] != '0') ? parserValor($saida['devolucao']) : '';
            $saida['liquidacoes_cartorio'] = ($saida['liquidacoes_cartorio'] != '0') ? parserValor($saida['liquidacoes_cartorio']) : '';
            $saida['liquidacoes_vencido_1'] = ($saida['liquidacoes_vencido_1'] != '0') ? parserValor($saida['liquidacoes_vencido_1']) : '';
            $saida['liquidacoes_vencido_2'] = ($saida['liquidacoes_vencido_2'] != '0') ? parserValor($saida['liquidacoes_vencido_2']) : '';
            $saida['liquidacoes_vencido_3'] = ($saida['liquidacoes_vencido_3'] != '0') ? parserValor($saida['liquidacoes_vencido_3']) : '';
            $saida['titulos_dia'] = ($saida['titulos_dia'] != '0') ? parserValor($saida['titulos_dia']) : '';
            $saida['liquidacoes_antecipadas'] = ($saida['liquidacoes_antecipadas'] != '0') ? parserValor($saida['liquidacoes_antecipadas']) : '';
            $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
            $saida['multa'] = ($saida['multa'] != '0') ? parserValor($saida['multa']) : '';
            
            $saida['valor_total'] = ($saida['valor_total'] != '0') ? parserValor($saida['valor_total']) : '';
            
            
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => ['response' => $response, 'saida' => $saida],
                ]);
    
    }

    public function show(Request $request){
        $filter = $request->only(['filters']);
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
        if(!empty($fields['gerentes'])){
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
    }

        if(!empty($fields['armazen'])){
            if($fields['armazen'] == "false"){
                $fields['armazen'] = null;
            }
        }
        if(!empty($fields['intercompany'])){
            if($fields['intercompany'] == "false"){
                $fields['intercompany'] = null;
            }
        }

        $usuRagazzi = [];

        if(!empty($fields['ragazzi'])){
            if($fields['ragazzi'] == "false"){
                $fields['ragazzi'] = null;
            }else{
                $usuRagazzi[] = '1377';
                $usuRagazzi[] = '1384';
                $usuRagazzi[] = '1386';
                $usuRagazzi[] = '1387';
                $usuRagazzi[] = '1388';
                $usuRagazzi[] = '1397';
                $usuRagazzi[] = '1398';
                $usuRagazzi[] = '1442';
                $usuRagazzi[] = '1464';
                $usuRagazzi[] = '8313';

                $titulosRagazzi = $this->ReturnTitulos($usuRagazzi);
            }
        }

        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);

        $condicoesPagamentoNasajonObj = new CondicoesPagamentoNasajon;
        $TitulosAReceberNasajonObj = ContasReceberBaixadoNasajon::with('baixarPortal');
        if(count($usuRagazzi) > 0){
           // $TitulosAReceberNasajonObj = ContasReceberBaixadoNasajon::with('baixarPortal');

            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
           $TitulosAReceberNasajonObj->where('codigo', '<>', '25')
                        ->whereNotNull('data_pagamento')
            ->orderBy('banco_nome');
        }else{
          //  $TitulosAReceberNasajonObj = ContasReceberBaixadoNasajon::with('baixarPortal');
            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
           $TitulosAReceberNasajonObj->join($condicoesPagamentoNasajonObj->getTable(), 'integracoes.vw_df_formaspagamentos.id_docfis', 'documento_id')
            ->whereNotNull('data_pagamento')
            ->whereNotIn('formapagamento_descricao', ['Dinheiro', 'Cartão Crédito', 'Cartão Débito', 'Deposito em conta', 'Usar Crédito'])
            ->where('codigo', '<>', '25')
            ->orderBy('banco_nome');
        }
            
        if(isset($fields['estabelecimento'])){
            $TitulosAReceberNasajonObj->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        if(isset($fields['banco'])){
            $TitulosAReceberNasajonObj->where('banco_nome', $fields['banco']);
        }


        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && $fields['estabelecimento'] != 20){
            $TitulosAReceberNasajonObj->where('codigo', "!=", 20);
        }
        if(!isset($fields['intercompany'])){
            $cnpj_excluir[] = '05075884000167';
            $cnpj_excluir[] = '05075884000248';
            $cnpj_excluir[] = '06311274000269';
            $cnpj_excluir[] = '06311274000340';
            $cnpj_excluir[] = '08';
            $cnpj_excluir[] = '07';
            $cnpj_excluir[] = '06311274000501';
            $cnpj_excluir[] = '06311274000420';
        }
        if(count($cnpj_excluir) > 0){
            $TitulosAReceberNasajonObj->whereNotIn("cod_cliente", $cnpj_excluir);
        }

        if(count($usuRagazzi) > 0){
            $TitulosAReceberNasajonObj->whereIn("id_titulo", $titulosRagazzi);
        }


        if(!empty($fields['vendedor_representante']) && (isset($representante) && $representante != '998')){

            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){

                $TitulosAReceberNasajonObj->whereHas('revisao_vendedor_comissao', function($query) use($representantes){
                    $query->whereIn('vendedor_codigo', $representantes);
                    if(in_array('001', $representantes)){
                        $query->whereOrNull('vendedor_codigo');
                    }
                });

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
                $TitulosAReceberNasajonObj->whereHas('revisao_vendedor_comissao', function($query) use($representantes){
                    $query->whereIn('vendedor_codigo', $representantes);
                });
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
            
                $TitulosAReceberNasajonObj->whereHas('revisao_vendedor_comissao', function($query) use($representantes){
                    $query->whereIn('vendedor_codigo', $representantes);
                });
            }



        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
            
                $TitulosAReceberNasajonObj->whereHas('revisao_vendedor_comissao', function($query) use($representantes){
                    $query->whereIn('vendedor_codigo', $representantes);
                });
            }

        }  
        else if(!empty($fields['gerentes']) && empty($fields['vendedor_representante'])){

            if($gerente == 0){
                $gerentes = User::where('tipo_usuario_id', '19')->get()->pluck('id');

                $representantes = User::
                    whereIn('responsavel', $gerentes)
                    ->orWhereIn('id', $gerentes)
                    ->get()
                    ->pluck('codigo_representante')
                    ->toArray();
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                    $TitulosAReceberNasajonObj->whereHas('revisao_vendedor_comissao', function($query) use($representantes){
                        $query->whereIn('vendedor_codigo', $representantes);
                        $query->whereOrNull('vendedor_codigo');
                    });
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

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                    $TitulosAReceberNasajonObj->whereHas('revisao_vendedor_comissao', function($query) use($representantes){
                        $query->whereIn('vendedor_codigo', $representantes);
                    });
                }




            }
        }


        $contas_a_receber_nasajon = $TitulosAReceberNasajonObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];

        foreach ($contas_a_receber_nasajon as $entrada) {
            $usuarioBaixa = '';
            $idTitulo = $entrada->id_titulo;

            $queryBaixa = BaixaTitulo::select('*');
            $queryBaixa->where('titulo_nasajon_id', $idTitulo);
            $queryBaixa->with(['criadoPor']);
            $resultBaixa = $queryBaixa->get();

            foreach ($resultBaixa as $baixa) {
                $usuarioBaixa = $baixa->criadoPor->name;
            }
                       
            $dateVencimento = Carbon::createFromFormat('Y-m-d',$entrada->vencimento);

            
            if($dateVencimento->dayOfWeekIso == 6){
                $dateVencimento->addDays(2);
            }
            if($dateVencimento->dayOfWeekIso == 7){
                $dateVencimento->addDays(1);
            }

          
                $datePagamento = Carbon::createFromFormat('Y-m-d',$entrada->data_pagamento);
  

            $multa =0;
            if(!empty($entrada->baixarPortal)){
                $multa =$entrada->baixarPortal->multa;
            
            }
            $linha = [];

            $linha['banco'] = (isset($entrada->banco_nome)) ? $entrada->banco_nome : 'CARTEIRA';
            $linha['estabelecimento'] = (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem';
            $linha['cliente'] = $entrada->nome_cliente;
            $linha['titulo'] = $entrada->numero;
            $linha['data_emissao'] = parserData($entrada->emissao);
            $linha['data_lancamento'] = parserData($entrada->data_lancamento);
            $linha['usuario'] = $usuarioBaixa;
            $linha['data_vencimento'] = parserData($entrada->vencimento);
            if($entrada->tem_prorrogacao === true ){
                $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($entrada->vencimento_original).'"> *</a>';            
            }
            $linha['data_considerada'] = parserData($dateVencimento);
            $linha['data_pagamento'] = parserData($entrada->data_pagamento);
            $linha['dias_atraso'] = ($dateVencimento->diffInDays($datePagamento,false) > 0) ? $dateVencimento->diffInDays($datePagamento) : '0';
            $linha['valor_original'] = $entrada->valor_titulo;
            $linha['valor_pago'] = $entrada->valor;
            $linha['juros'] = $entrada->valorjuros;
            $linha['multa'] = $multa;
            $linha['desconto'] = $entrada->valordesconto;

            $response[] = $linha;
        }

        $saida = ['valor_original' => 0,
                'valor_pago' => 0,
                'juros' => 0,
                'multa' => 0,
                'desconto' => 0
                ];

        foreach($response as $resp){
            $saida['valor_original'] += $resp['valor_original'];
            $saida['valor_pago'] += $resp['valor_pago'];
            $saida['juros'] += $resp['juros'];
            $saida['multa'] += $resp['multa'];
            $saida['desconto'] += $resp['desconto'];
        }
        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] != '0') ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['multa'] = ($saida['multa'] != '0') ? parserValor($saida['multa']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';

        return view('programs.analise_contas_receber.modal.analitico_modal_index')->with(["saida" => $saida, 'response' => $response]);
    }

    public function showRecebido(Request $request){
        $filter = $request->only(['filters']);
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
        if(!empty($fields['gerentes'])){
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
    }
        if(!empty($fields['armazen'])){
            if($fields['armazen'] == "false"){
                $fields['armazen'] = null;
            }
        }
        
        $usuRagazzi = [];

        if(!empty($fields['ragazzi'])){
            if($fields['ragazzi'] == "false"){
                $fields['ragazzi'] = null;
            }else{
                $usuRagazzi[] = '1377';
                $usuRagazzi[] = '1384';
                $usuRagazzi[] = '1386';
                $usuRagazzi[] = '1387';
                $usuRagazzi[] = '1388';
                $usuRagazzi[] = '1397';
                $usuRagazzi[] = '1398';
                $usuRagazzi[] = '1442';
                $usuRagazzi[] = '1464';
                $usuRagazzi[] = '8313';

                $titulosRagazzi = $this->ReturnTitulos($usuRagazzi);
            }
        }

        if(!empty($fields['intercompany'])){
            if($fields['intercompany'] == "false"){
                $fields['intercompany'] = null;
            }
        }


        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);

        $condicoesPagamentoNasajonObj = new CondicoesPagamentoNasajon;
        $TitulosAReceberNasajonObj = TitulosPagosNasajon::with('baixarPortal');
        if(count($usuRagazzi) > 0){
          
            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
           $TitulosAReceberNasajonObj->where('codigo', '<>', '25')
                        ->whereNotNull('data_pagamento')
            ->orderBy('nome_banco');
        }else{
           
            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
            
           $TitulosAReceberNasajonObj->join($condicoesPagamentoNasajonObj->getTable(), 'integracoes.vw_df_formaspagamentos.id_docfis', 'documento_id')
            ->whereNotNull('data_pagamento')
            ->whereNotIn('formapagamento_descricao', ['Dinheiro', 'Cartão Crédito', 'Cartão Débito', 'Deposito em conta', 'Usar Crédito'])
            ->where('codigo', '<>', '25')
            ->orderBy('nome_banco');
        }
            
        if(isset($fields['estabelecimento'])){
            $TitulosAReceberNasajonObj->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        if(isset($fields['banco'])){
            $TitulosAReceberNasajonObj->where('nome_banco', $fields['banco']);
        }


        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && $fields['estabelecimento'] != 20){
            $TitulosAReceberNasajonObj->where('codigo', "!=", 20);
        }
        if(!isset($fields['intercompany'])){
            $cnpj_excluir[] = '05075884000167';
            $cnpj_excluir[] = '05075884000248';
            $cnpj_excluir[] = '06311274000269';
            $cnpj_excluir[] = '06311274000340';
            $cnpj_excluir[] = '08';
            $cnpj_excluir[] = '07';
            $cnpj_excluir[] = '06311274000501';
            $cnpj_excluir[] = '06311274000420';
        }
        if(count($cnpj_excluir) > 0){
            $TitulosAReceberNasajonObj->whereNotIn("cod_cliente", $cnpj_excluir);
        }

        if(count($usuRagazzi) > 0){
            $TitulosAReceberNasajonObj->whereIn("id_titulo", $titulosRagazzi);
        }
       

        if(!empty($fields['vendedor_representante']) && (isset($representante) && $representante != '998')){

            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){

                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);

                if(in_array('001', $representantes)){
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
                }

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
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
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
            
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
            }



           }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
        strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
        $userid = Auth::user()->id;
        $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

        if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
        
            $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
        }

    }
        else if(!empty($fields['gerentes']) && empty($fields['vendedor_representante'])){

            if($gerente == 0){
                $gerentes = User::where('tipo_usuario_id', '19')->get()->pluck('id');

                $representantes = User::
                    whereIn('responsavel', $gerentes)
                    ->orWhereIn('id', $gerentes)
                    ->get()
                    ->pluck('codigo_representante')
                    ->toArray();
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
              
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
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

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                }




            }
        }

        $contas_a_receber_nasajon = $TitulosAReceberNasajonObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];

        foreach ($contas_a_receber_nasajon as $entrada) {
            $usuarioBaixa = '';
            $idTitulo = $entrada->id_titulo;

            $queryBaixa = BaixaTitulo::select('*');
            $queryBaixa->where('titulo_nasajon_id', $idTitulo);
            $queryBaixa->with(['criadoPor']);
            $resultBaixa = $queryBaixa->get();

            foreach ($resultBaixa as $baixa) {
                $usuarioBaixa = $baixa->criadoPor->name;
            }

            $dateVencimento = Carbon::createFromFormat('Y-m-d',$entrada->vencimento);

            if($dateVencimento->dayOfWeekIso == 6){
                $dateVencimento->addDays(2);
            }
            if($dateVencimento->dayOfWeekIso == 7){
                $dateVencimento->addDays(1);
            }
     
                $datePagamento = Carbon::createFromFormat('Y-m-d',$entrada->data_pagamento);


            $multa =0;
            if(!empty($entrada->baixarPortal)){
                $multa =$entrada->baixarPortal->multa;
            
            }

            $linha = [];

            $linha['banco'] = (isset($entrada->nome_banco)) ? $entrada->nome_banco : 'CARTEIRA';
            $linha['estabelecimento'] = (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem';
            $linha['cliente'] = $entrada->nome_cliente;
            $linha['titulo'] = $entrada->numero;
            $linha['data_emissao'] = parserData($entrada->emissao);
            $linha['data_lancamento'] = parserData($entrada->data_lancamento);
            $linha['usuario'] = $usuarioBaixa;
            $linha['data_vencimento'] = parserData($entrada->vencimento);
            if($entrada->tem_prorrogacao === true ){
                $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($entrada->vencimento_original).'"> *</a>';
            
            }
            $linha['data_considerada'] = parserData($dateVencimento);
            $linha['data_pagamento'] = parserData($entrada->data_pagamento);
            $linha['dias_atraso'] = ($dateVencimento->diffInDays($datePagamento,false) > 0) ? $dateVencimento->diffInDays($datePagamento) : '0';
            $linha['valor_original'] = $entrada->valor_titulo;
            $linha['valor_pago'] = $entrada->valor;
            $linha['juros'] = $entrada->valorjuros;
            $linha['multa'] =$multa;
            $linha['desconto'] = $entrada->valordesconto;

            $response[] = $linha;
        }

        $saida = ['valor_original' => 0,
                'valor_pago' => 0,
                'juros' => 0,
                'multa' => 0,
                'desconto' => 0
                ];

        foreach($response as $resp){
            $devolucao = ($resp['valor_pago'] <= 0) ? $resp['valor_original'] : '0';
            $saida['valor_original'] += ($resp['valor_original'] + $resp['juros']) - $resp['desconto'] - $devolucao;

            $saida['valor_pago'] += $resp['valor_pago'];
            $saida['juros'] += $resp['juros'];
            $saida['multa'] += $resp['multa'];
            $saida['desconto'] += $resp['desconto'];
        }
        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] != '0') ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['multa'] = ($saida['multa'] != '0') ? parserValor($saida['multa']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';

        return view('programs.analise_contas_receber.modal.analitico_modal_index')->with(["saida" => $saida, 'response' => $response]);
    }


    public function showDesconto(Request $request){
        $filter = $request->only(['filters']);
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
        if(!empty($fields['gerentes'])){
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
    }
        if(!empty($fields['armazen'])){
            if($fields['armazen'] == "false"){
                $fields['armazen'] = null;
            }
        }
        if(!empty($fields['intercompany'])){
            if($fields['intercompany'] == "false"){
                $fields['intercompany'] = null;
            }
        }

        $usuRagazzi = [];

        if(!empty($fields['ragazzi'])){
            if($fields['ragazzi'] == "false"){
                $fields['ragazzi'] = null;
            }else{
                $usuRagazzi[] = '1377';
                $usuRagazzi[] = '1384';
                $usuRagazzi[] = '1386';
                $usuRagazzi[] = '1387';
                $usuRagazzi[] = '1388';
                $usuRagazzi[] = '1397';
                $usuRagazzi[] = '1398';
                $usuRagazzi[] = '1442';
                $usuRagazzi[] = '1464';
                $usuRagazzi[] = '8313';

                $titulosRagazzi = $this->ReturnTitulos($usuRagazzi);
            }
        }

        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        
        $condicoesPagamentoNasajonObj = new CondicoesPagamentoNasajon;
        $TitulosAReceberNasajonObj = TitulosPagosNasajon::select();

        if(count($usuRagazzi) > 0){
         

            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
           $TitulosAReceberNasajonObj->where('codigo', '<>', '25')
                        ->whereNotNull('data_pagamento')
            ->orderBy('nome_banco');
        }else{

            
            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
           $TitulosAReceberNasajonObj->join($condicoesPagamentoNasajonObj->getTable(), 'integracoes.vw_df_formaspagamentos.id_docfis', 'documento_id')
            ->whereNotNull('data_pagamento')
            ->whereNotIn('formapagamento_descricao', ['Dinheiro', 'Cartão Crédito', 'Cartão Débito', 'Deposito em conta', 'Usar Crédito'])
            ->where('codigo', '<>', '25')
            ->orderBy('nome_banco');
        }
            
        if(isset($fields['estabelecimento'])){
            $TitulosAReceberNasajonObj->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        if(isset($fields['banco'])){
            $TitulosAReceberNasajonObj->where('nome_banco', $fields['banco']);
        }
        if($fields['banco'] == null){
            $TitulosAReceberNasajonObj->where('nome_banco', null);
        }

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && $fields['estabelecimento'] != 20){
            $TitulosAReceberNasajonObj->where('codigo', "!=", 20);
        }
        if(!isset($fields['intercompany'])){
            $cnpj_excluir[] = '05075884000167';
            $cnpj_excluir[] = '05075884000248';
            $cnpj_excluir[] = '06311274000269';
            $cnpj_excluir[] = '06311274000340';
            $cnpj_excluir[] = '08';
            $cnpj_excluir[] = '07';
            $cnpj_excluir[] = '06311274000501';
            $cnpj_excluir[] = '06311274000420';
        }
        if(count($cnpj_excluir) > 0){
            $TitulosAReceberNasajonObj->whereNotIn("cod_cliente", $cnpj_excluir);
        }

        if(count($usuRagazzi) > 0){
            $TitulosAReceberNasajonObj->whereIn("id_titulo", $titulosRagazzi);
        }


        if(!empty($fields['vendedor_representante']) && (isset($representante) && $representante != '998')){

            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){

                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);

                if(in_array('001', $representantes)){
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
                }

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
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
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
            
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
            }



        }
        else if(!empty($fields['gerentes']) && empty($fields['vendedor_representante'])){

            if($gerente == 0){
                $gerentes = User::where('tipo_usuario_id', '19')->get()->pluck('id');

                $representantes = User::
                    whereIn('responsavel', $gerentes)
                    ->orWhereIn('id', $gerentes)
                    ->get()
                    ->pluck('codigo_representante')
                    ->toArray();
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
              
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
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

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                }




            }
        }

        $contas_a_receber_nasajon = $TitulosAReceberNasajonObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];

        foreach ($contas_a_receber_nasajon as $entrada) {
            $usuarioBaixa = '';
            $idTitulo = $entrada->id_titulo;

            $queryBaixa = BaixaTitulo::select('*');
            $queryBaixa->where('titulo_nasajon_id', $idTitulo);
            $queryBaixa->with(['criadoPor']);
            $resultBaixa = $queryBaixa->get();

            foreach ($resultBaixa as $baixa) {
                $usuarioBaixa = $baixa->criadoPor->name;
            }

            $dateVencimento = Carbon::createFromFormat('Y-m-d',$entrada->vencimento);

            if($dateVencimento->dayOfWeekIso == 6){
                $dateVencimento->addDays(2);
            }
            if($dateVencimento->dayOfWeekIso == 7){
                $dateVencimento->addDays(1);
            }
       
                $datePagamento = Carbon::createFromFormat('Y-m-d',$entrada->data_pagamento);
     
            if($entrada->valordesconto > 0){
                $multa =0;
                if(!empty($entrada->baixarPortal)){
                    $multa =$entrada->baixarPortal->multa;
                
                }
                $linha = [];
                
                $linha['estabelecimento'] = (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem';
                $linha['cliente'] = $entrada->nome_cliente;
                $linha['titulo'] = $entrada->numero;
                $linha['data_emissao'] = parserData($entrada->emissao);
                $linha['data_lancamento'] = parserData($entrada->data_lancamento);
                $linha['usuario'] = $usuarioBaixa;
                $linha['data_vencimento'] = parserData($entrada->vencimento);
                if($entrada->tem_prorrogacao === true ){
                    $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($entrada->vencimento_original).'"> *</a>';
                
                }
                $linha['data_considerada'] = parserData($dateVencimento);
                $linha['data_pagamento'] = parserData($entrada->data_pagamento);
                $linha['dias_atraso'] = ($dateVencimento->diffInDays($datePagamento,false) > 0) ? $dateVencimento->diffInDays($datePagamento) : '0';
                $linha['valor_original'] = $entrada->valor_titulo;
                $linha['valor_pago'] = $entrada->valor;
                $linha['juros'] = $entrada->valorjuros;
                $linha['multa'] =$multa;
                $linha['desconto'] = $entrada->valordesconto;

                $response[] = $linha;
            }
        }

        $saida = ['valor_original' => 0,
                'valor_pago' => 0,
                'juros' => 0,
                'multa' => 0,
                'desconto' => 0
                ];

        foreach($response as $resp){
            $saida['valor_original'] += $resp['valor_original'];
            $saida['valor_pago'] += $resp['valor_pago'];
            $saida['juros'] += $resp['juros'];
            $saida['multa'] += $resp['multa'];
            $saida['desconto'] += $resp['desconto'];
        }
        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] != '0') ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['multa'] = ($saida['multa'] != '0') ? parserValor($saida['multa']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';

        return view('programs.analise_contas_receber.modal.analitico_modal_index')->with(["saida" => $saida, 'response' => $response]);
    }

    public function showDevolucao(Request $request){
        $filter = $request->filters;
        try{
            $fields = decrypt($filter);
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
        if(!empty($fields['gerentes'])){
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
    }

        if(!empty($fields['armazen'])){
            if($fields['armazen'] == "false"){
                $fields['armazen'] = null;
            }
        }
        if(!empty($fields['intercompany'])){
            if($fields['intercompany'] == "false"){
                $fields['intercompany'] = null;
            }
        }

        $usuRagazzi = [];

        if(!empty($fields['ragazzi'])){
            if($fields['ragazzi'] == "false"){
                $fields['ragazzi'] = null;
            }else{
                $usuRagazzi[] = '1377';
                $usuRagazzi[] = '1384';
                $usuRagazzi[] = '1386';
                $usuRagazzi[] = '1387';
                $usuRagazzi[] = '1388';
                $usuRagazzi[] = '1397';
                $usuRagazzi[] = '1398';
                $usuRagazzi[] = '1442';
                $usuRagazzi[] = '1464';
                $usuRagazzi[] = '8313';

                $titulosRagazzi = $this->ReturnTitulos($usuRagazzi);
            }
        }

        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        
        $condicoesPagamentoNasajonObj = new CondicoesPagamentoNasajon;
        $TitulosAReceberNasajonObj = TitulosPagosNasajon::select();
      
        if(count($usuRagazzi) > 0){
   
            
            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
           $TitulosAReceberNasajonObj->where('codigo', '<>', '25')
                        ->whereNotNull('data_pagamento')
            ->orderBy('nome_banco');
        }else{
 
            
            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
           $TitulosAReceberNasajonObj->join($condicoesPagamentoNasajonObj->getTable(), 'integracoes.vw_df_formaspagamentos.id_docfis', 'documento_id')
            ->whereNotNull('data_pagamento')
            ->whereNotIn('formapagamento_descricao', ['Dinheiro', 'Cartão Crédito', 'Cartão Débito', 'Deposito em conta', 'Usar Crédito'])
            ->where('codigo', '<>', '25')
            ->orderBy('nome_banco');
        }
            
        if(isset($fields['estabelecimento'])){
            $TitulosAReceberNasajonObj->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        if(isset($fields['banco'])){
            $TitulosAReceberNasajonObj->where('nome_banco', $fields['banco']);
        }

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && $fields['estabelecimento'] != 20){
            $TitulosAReceberNasajonObj->where('codigo', "!=", 20);
        }
        if(!isset($fields['intercompany'])){
            $cnpj_excluir[] = '05075884000167';
            $cnpj_excluir[] = '05075884000248';
            $cnpj_excluir[] = '06311274000269';
            $cnpj_excluir[] = '06311274000340';
            $cnpj_excluir[] = '08';
            $cnpj_excluir[] = '07';
            $cnpj_excluir[] = '06311274000501';
            $cnpj_excluir[] = '06311274000420';
        }
        if(count($cnpj_excluir) > 0){
            $TitulosAReceberNasajonObj->whereNotIn("cod_cliente", $cnpj_excluir);
        }

        if(count($usuRagazzi) > 0){
            $TitulosAReceberNasajonObj->whereIn("id_titulo", $titulosRagazzi);
        }
        ///modificar

        if(!empty($fields['vendedor_representante']) && (isset($representante) && $representante != '998')){

            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){

                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);

                if(in_array('001', $representantes)){
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
                }

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
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
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
            
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
            }



        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
        strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
        $userid = Auth::user()->id;
        $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

        if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
        
            $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
        }

    }
        else if(!empty($fields['gerentes']) && empty($fields['vendedor_representante'])){

            if($gerente == 0){
                $gerentes = User::where('tipo_usuario_id', '19')->get()->pluck('id');

                $representantes = User::
                    whereIn('responsavel', $gerentes)
                    ->orWhereIn('id', $gerentes)
                    ->get()
                    ->pluck('codigo_representante')
                    ->toArray();
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
              
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
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

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                }




            }
        }

        $contas_a_receber_nasajon = $TitulosAReceberNasajonObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];

        foreach ($contas_a_receber_nasajon as $entrada) {
            $usuarioBaixa = '';
            $idTitulo = $entrada->id_titulo;

            $queryBaixa = BaixaTitulo::select('*');
            $queryBaixa->where('titulo_nasajon_id', $idTitulo);
            $queryBaixa->with(['criadoPor']);
            $resultBaixa = $queryBaixa->get();

            foreach ($resultBaixa as $baixa) {
                $usuarioBaixa = $baixa->criadoPor->name;
            }

            $dateVencimento = Carbon::createFromFormat('Y-m-d',$entrada->vencimento);

            if($dateVencimento->dayOfWeekIso == 6){
                $dateVencimento->addDays(2);
            }
            if($dateVencimento->dayOfWeekIso == 7){
                $dateVencimento->addDays(1);
            }

          
                $datePagamento = Carbon::createFromFormat('Y-m-d',$entrada->data_pagamento);
   
            if($entrada->valor <= 0){

                $linha = [];

                $linha['estabelecimento'] = (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem';
                $linha['cliente'] = $entrada->nome_cliente;
                $linha['titulo'] = $entrada->numero;
                $linha['data_emissao'] = parserData($entrada->emissao);
                $linha['data_lancamento'] = parserData($entrada->data_lancamento);
                $linha['usuario'] = $usuarioBaixa;
                $linha['data_vencimento'] = parserData($entrada->vencimento);
                if($entrada->tem_prorrogacao === true ){
                    $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($entrada->vencimento_original).'"> *</a>';
                }
                $linha['data_considerada'] = parserData($dateVencimento);
                $linha['data_pagamento'] = parserData($entrada->data_pagamento);
                $linha['dias_atraso'] = ($dateVencimento->diffInDays($datePagamento,false) > 0) ? $dateVencimento->diffInDays($datePagamento) : '0';
                $linha['valor_original'] = $entrada->valor_titulo;
                $linha['valor_pago'] = $entrada->valor;
                $linha['juros'] = $entrada->valorjuros;
                $linha['multa'] = 0;
                $linha['desconto'] = $entrada->valordesconto;

                $response[] = $linha;

            }
        }

        $saida = ['valor_original' => 0,
                'valor_pago' => 0,
                'juros' => 0,
                'multa' => 0,
                'desconto' => 0
                ];

        foreach($response as $resp){
            $saida['valor_original'] += $resp['valor_original'];
            $saida['valor_pago'] += $resp['valor_pago'];
            $saida['juros'] += $resp['juros'];
            $saida['multa'] += $resp['multa'];
            $saida['desconto'] += $resp['desconto'];
        }
        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] != '0') ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['multa'] = ($saida['multa'] != '0') ? parserValor($saida['multa']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';

        return view('programs.analise_contas_receber.modal.analitico_modal_index')->with(["saida" => $saida, 'response' => $response]);
    }


    public function showVencido(Request $request){

        $filter = $request->only(['filters','abertura']);

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
        if(!empty($fields['gerentes'])){
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
    }
        if(!empty($fields['armazen'])){
            if($fields['armazen'] == "false"){
                $fields['armazen'] = null;
            }
        }
        if(!empty($fields['intercompany'])){
            if($fields['intercompany'] == "false"){
                $fields['intercompany'] = null;
            }
        }

        $usuRagazzi = [];

        if(!empty($fields['ragazzi'])){
            if($fields['ragazzi'] == "false"){
                $fields['ragazzi'] = null;
            }else{
                $usuRagazzi[] = '1377';
                $usuRagazzi[] = '1384';
                $usuRagazzi[] = '1386';
                $usuRagazzi[] = '1387';
                $usuRagazzi[] = '1388';
                $usuRagazzi[] = '1397';
                $usuRagazzi[] = '1398';
                $usuRagazzi[] = '1442';
                $usuRagazzi[] = '1464';
                $usuRagazzi[] = '8313';

                $titulosRagazzi = $this->ReturnTitulos($usuRagazzi);
            }
        }

        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
       
        $condicoesPagamentoNasajonObj = new CondicoesPagamentoNasajon;
        $TitulosAReceberNasajonObj = TitulosPagosNasajon::with('baixarPortal');

        if(count($usuRagazzi) > 0){
      
            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
            
            
           $TitulosAReceberNasajonObj->where('codigo', '<>', '25')
                        ->whereNotNull('data_pagamento')
            ->orderBy('nome_banco');
        }else{
           
            
            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
           $TitulosAReceberNasajonObj->join($condicoesPagamentoNasajonObj->getTable(), 'integracoes.vw_df_formaspagamentos.id_docfis', 'documento_id')
            ->whereNotNull('data_pagamento')
            ->whereNotIn('formapagamento_descricao', ['Dinheiro', 'Cartão Crédito', 'Cartão Débito', 'Deposito em conta', 'Usar Crédito'])
            ->where('codigo', '<>', '25')
            ->orderBy('nome_banco');
        }
            
        if(isset($fields['estabelecimento'])){
            $TitulosAReceberNasajonObj->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        if(isset($fields['banco'])){
            $TitulosAReceberNasajonObj->where('nome_banco', $fields['banco']);
        }

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && $fields['estabelecimento'] != 20){
            $TitulosAReceberNasajonObj->where('codigo', "!=", 20);
        }
        if(!isset($fields['intercompany'])){
            $cnpj_excluir[] = '05075884000167';
            $cnpj_excluir[] = '05075884000248';
            $cnpj_excluir[] = '06311274000269';
            $cnpj_excluir[] = '06311274000340';
            $cnpj_excluir[] = '08';
            $cnpj_excluir[] = '07';
            $cnpj_excluir[] = '06311274000501';
            $cnpj_excluir[] = '06311274000420';
        }
        if(count($cnpj_excluir) > 0){
            $TitulosAReceberNasajonObj->whereNotIn("cod_cliente", $cnpj_excluir);
        }

        if(count($usuRagazzi) > 0){
            $TitulosAReceberNasajonObj->whereIn("id_titulo", $titulosRagazzi);
        }
      

        if(!empty($fields['vendedor_representante']) && (isset($representante) && $representante != '998')){

            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){

                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);

                if(in_array('001', $representantes)){
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
                }

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
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
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
            
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
            }



        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
        strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
        $userid = Auth::user()->id;
        $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

        if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
        
            $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
        }

    }
        else if(!empty($fields['gerentes']) && empty($fields['vendedor_representante'])){

            if($gerente == 0){
                $gerentes = User::where('tipo_usuario_id', '19')->get()->pluck('id');

                $representantes = User::
                    whereIn('responsavel', $gerentes)
                    ->orWhereIn('id', $gerentes)
                    ->get()
                    ->pluck('codigo_representante')
                    ->toArray();
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
              
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
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

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                }




            }
        }

        $contas_a_receber_nasajon = $TitulosAReceberNasajonObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];

        foreach ($contas_a_receber_nasajon as $entrada) {
            $usuarioBaixa = '';
            $idTitulo = $entrada->id_titulo;

            $queryBaixa = BaixaTitulo::select('*');
            $queryBaixa->where('titulo_nasajon_id', $idTitulo);
            $queryBaixa->with(['criadoPor']);
            $resultBaixa = $queryBaixa->get();

            foreach ($resultBaixa as $baixa) {
                $usuarioBaixa = $baixa->criadoPor->name;
            }

            $dateVencimento = Carbon::createFromFormat('Y-m-d',$entrada->vencimento);

            if($dateVencimento->dayOfWeekIso == 6){ 
                $dateVencimento->addDays(2);
            }
            if($dateVencimento->dayOfWeekIso == 7){ 
                $dateVencimento->addDays(1);
            }

         
             $datePagamento = Carbon::createFromFormat('Y-m-d',$entrada->data_pagamento);
    
            $dias_atrasos =$datePagamento->diffInDays($dateVencimento);
            if($dateVencimento->gt($datePagamento)){
                $dias_atrasos =0;
            }
            $base_dia_1 =0;
            $base_dia_2 =0;
            if($filter['abertura'] == 'vencido_1'){
                $base_dia_1 =1;
            $base_dia_2 =10;
            }
            if($filter['abertura'] == 'vencido_2'){
                $base_dia_1 =11;
            $base_dia_2 =30;
            }
            if($filter['abertura'] == 'vencido_3'){
                $base_dia_1 =31;
            $base_dia_2 =10000;
            }
      
            if($dateVencimento->lt($datePagamento) && $entrada->valor > 0 && $dias_atrasos >= $base_dia_1  && $dias_atrasos <= $base_dia_2){
                $multa =0;
                if(!empty($entrada->baixarPortal)){
                    $multa =$entrada->baixarPortal->multa;

                }
                $linha = [];

                $linha['estabelecimento'] = (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem';
                $linha['cliente'] = $entrada->nome_cliente;
                $linha['titulo'] = $entrada->numero;
                $linha['data_emissao'] = parserData($entrada->emissao);
                $linha['data_lancamento'] = parserData($entrada->data_lancamento);
                $linha['usuario'] = $usuarioBaixa;
                $linha['data_vencimento'] = parserData($entrada->vencimento);
                if($entrada->tem_prorrogacao === true ){
                    $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($entrada->vencimento_original).'"> *</a>';
                }
                $linha['data_considerada'] = parserData($dateVencimento);
                $linha['data_pagamento'] = parserData($entrada->data_pagamento);
                $linha['dias_atraso'] =  $dias_atrasos;
                $linha['valor_original'] = $entrada->valor_titulo;
                $linha['valor_pago'] = $entrada->valor;
                $linha['juros'] = $entrada->valorjuros;
                $linha['multa'] = $multa;
                $linha['desconto'] = $entrada->valordesconto;

                $response[] = $linha;
            }
        }

        $saida = ['valor_original' => 0,
                'valor_pago' => 0,
                'juros' => 0,
                'multa' => 0,
                'desconto' => 0
                ];

        foreach($response as $resp){
            $saida['valor_original'] += $resp['valor_original'];
            $saida['valor_pago'] += $resp['valor_pago'];
            $saida['juros'] += $resp['juros'];
            $saida['multa'] += $resp['multa'];
            $saida['desconto'] += $resp['desconto'];
        }
        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] != '0') ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['multa'] = ($saida['multa'] != '0') ? parserValor($saida['multa']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';

        return view('programs.analise_contas_receber.modal.analitico_modal_index')->with(["saida" => $saida, 'response' => $response]);
    }

    public function showDia(Request $request){
        $filter = $request->only(['filters']);
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
        if(!empty($fields['gerentes'])){
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
    }
        if(!empty($fields['armazen'])){
            if($fields['armazen'] == "false"){
                $fields['armazen'] = null;
            }
        }
        if(!empty($fields['intercompany'])){
            if($fields['intercompany'] == "false"){
                $fields['intercompany'] = null;
            }
        }

        $usuRagazzi = [];

        if(!empty($fields['ragazzi'])){
            if($fields['ragazzi'] == "false"){
                $fields['ragazzi'] = null;
            }else{
                $usuRagazzi[] = '1377';
                $usuRagazzi[] = '1384';
                $usuRagazzi[] = '1386';
                $usuRagazzi[] = '1387';
                $usuRagazzi[] = '1388';
                $usuRagazzi[] = '1397';
                $usuRagazzi[] = '1398';
                $usuRagazzi[] = '1442';
                $usuRagazzi[] = '1464';
                $usuRagazzi[] = '8313';

                $titulosRagazzi = $this->ReturnTitulos($usuRagazzi);
            }
        }

        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        
        $condicoesPagamentoNasajonObj = new CondicoesPagamentoNasajon;
        
  
        if(count($usuRagazzi) > 0){
            $TitulosAReceberNasajonObj = TitulosPagosNasajon::select();
           if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
           $TitulosAReceberNasajonObj->where('codigo', '<>', '25')
                        ->whereNotNull('data_pagamento')
            ->orderBy('nome_banco');
        }else{
            $TitulosAReceberNasajonObj = TitulosPagosNasajon::whereBetween('vencimento', [$data_inicio, $data_fim])
            ->join($condicoesPagamentoNasajonObj->getTable(), 'integracoes.vw_df_formaspagamentos.id_docfis', 'documento_id')
            ->whereNotNull('data_pagamento')
            ->whereNotIn('formapagamento_descricao', ['Dinheiro', 'Cartão Crédito', 'Cartão Débito', 'Deposito em conta', 'Usar Crédito'])
            ->where('codigo', '<>', '25')
            ->orderBy('nome_banco');
        }
            
        if(isset($fields['estabelecimento'])){
            $TitulosAReceberNasajonObj->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        if(isset($fields['banco'])){
            $TitulosAReceberNasajonObj->where('nome_banco', $fields['banco']);
        }


        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && $fields['estabelecimento'] != 20){
            $TitulosAReceberNasajonObj->where('codigo', "!=", 20);
        }
        if(!isset($fields['intercompany'])){
            $cnpj_excluir[] = '05075884000167';
            $cnpj_excluir[] = '05075884000248';
            $cnpj_excluir[] = '06311274000269';
            $cnpj_excluir[] = '06311274000340';
            $cnpj_excluir[] = '08';
            $cnpj_excluir[] = '07';
            $cnpj_excluir[] = '06311274000501';
            $cnpj_excluir[] = '06311274000420';
        }
        if(count($cnpj_excluir) > 0){
            $TitulosAReceberNasajonObj->whereNotIn("cod_cliente", $cnpj_excluir);
        }

        if(count($usuRagazzi) > 0){
            $TitulosAReceberNasajonObj->whereIn("id_titulo", $titulosRagazzi);
        }


        if(!empty($fields['vendedor_representante']) && (isset($representante) && $representante != '998')){

            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){

                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);

                if(in_array('001', $representantes)){
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
                }

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
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
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
            
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
            }



        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
        strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
        $userid = Auth::user()->id;
        $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

        if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
        
            $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
        }

    }
        else if(!empty($fields['gerentes']) && empty($fields['vendedor_representante'])){

            if($gerente == 0){
                $gerentes = User::where('tipo_usuario_id', '19')->get()->pluck('id');

                $representantes = User::
                    whereIn('responsavel', $gerentes)
                    ->orWhereIn('id', $gerentes)
                    ->get()
                    ->pluck('codigo_representante')
                    ->toArray();
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
              
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
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

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                }




            }
        }

        $contas_a_receber_nasajon = $TitulosAReceberNasajonObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];

        foreach ($contas_a_receber_nasajon as $entrada) {
            $usuarioBaixa = '';
            $idTitulo = $entrada->id_titulo;

            $queryBaixa = BaixaTitulo::select('*');
            $queryBaixa->where('titulo_nasajon_id', $idTitulo);
            $queryBaixa->with(['criadoPor']);
            $resultBaixa = $queryBaixa->get();

            foreach ($resultBaixa as $baixa) {
                $usuarioBaixa = $baixa->criadoPor->name;
            }

            $dateVencimento = Carbon::createFromFormat('Y-m-d',$entrada->vencimento);

            if($dateVencimento->dayOfWeekIso == 6){ 
                $dateVencimento->addDays(2);
            }
            if($dateVencimento->dayOfWeekIso == 7){ 
                $dateVencimento->addDays(1);
            }

         
                $datePagamento = Carbon::createFromFormat('Y-m-d',$entrada->data_pagamento);
  
            if($dateVencimento->eq($datePagamento) && $entrada->valor > 0){
                $multa =0;
                if(!empty($entrada->baixarPortal)){
                    $multa =$entrada->baixarPortal->multa;
                
                }
                $linha = [];

                $linha['estabelecimento'] = (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem';
                $linha['cliente'] = $entrada->nome_cliente;
                $linha['titulo'] = $entrada->numero;
                $linha['data_emissao'] = parserData($entrada->emissao);
                $linha['data_lancamento'] = parserData($entrada->data_lancamento);
                $linha['usuario'] = $usuarioBaixa;
                $linha['data_vencimento'] = parserData($entrada->vencimento);
                if($entrada->tem_prorrogacao === true ){
                    $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($entrada->vencimento_original).'"> *</a>';
                }
                $linha['data_considerada'] = parserData($dateVencimento);
                $linha['data_pagamento'] = parserData($entrada->data_pagamento);
                $linha['dias_atraso'] = ($dateVencimento->diffInDays($datePagamento,false) > 0) ? $dateVencimento->diffInDays($datePagamento) : '0';
                $linha['valor_original'] = $entrada->valor_titulo;
                $linha['valor_pago'] = $entrada->valor;
                $linha['juros'] = $entrada->valorjuros;
                $linha['multa'] =$multa;
                $linha['desconto'] = $entrada->valordesconto;

                $response[] = $linha;

            }
        }

        $saida = ['valor_original' => 0,
                'valor_pago' => 0,
                'juros' => 0,
                'multa' => 0,
                'desconto' => 0
                ];

        foreach($response as $resp){
            $saida['valor_original'] += $resp['valor_original'];
            $saida['valor_pago'] += $resp['valor_pago'];
            $saida['juros'] += $resp['juros'];
            $saida['multa'] += $resp['multa'];
            $saida['desconto'] += $resp['desconto'];
        }
        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] != '0') ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['multa'] = ($saida['multa'] != '0') ? parserValor($saida['multa']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';

        return view('programs.analise_contas_receber.modal.analitico_modal_index')->with(["saida" => $saida, 'response' => $response]);
    }

    public function showAntecipadas(Request $request){
        $filter = $request->only(['filters']);
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
        if(!empty($fields['gerentes'])){
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
    }
        if(!empty($fields['armazen'])){
            if($fields['armazen'] == "false"){
                $fields['armazen'] = null;
            }
        }
        if(!empty($fields['intercompany'])){
            if($fields['intercompany'] == "false"){
                $fields['intercompany'] = null;
            }
        }

        $usuRagazzi = [];

        if(!empty($fields['ragazzi'])){
            if($fields['ragazzi'] == "false"){
                $fields['ragazzi'] = null;
            }else{
                $usuRagazzi[] = '1377';
                $usuRagazzi[] = '1384';
                $usuRagazzi[] = '1386';
                $usuRagazzi[] = '1387';
                $usuRagazzi[] = '1388';
                $usuRagazzi[] = '1397';
                $usuRagazzi[] = '1398';
                $usuRagazzi[] = '1442';
                $usuRagazzi[] = '1464';
                $usuRagazzi[] = '8313';

                $titulosRagazzi = $this->ReturnTitulos($usuRagazzi);
            }
        }

        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        
        $condicoesPagamentoNasajonObj = new CondicoesPagamentoNasajon;
        $TitulosAReceberNasajonObj = TitulosPagosNasajon::select();
        
        if(count($usuRagazzi) > 0){
            
            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
           $TitulosAReceberNasajonObj->where('codigo', '<>', '25')
                        ->whereNotNull('data_pagamento')
            ->orderBy('nome_banco');
        }else{
           
            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
           $TitulosAReceberNasajonObj->join($condicoesPagamentoNasajonObj->getTable(), 'integracoes.vw_df_formaspagamentos.id_docfis', 'documento_id')
            ->whereNotNull('data_pagamento')
            ->whereNotIn('formapagamento_descricao', ['Dinheiro', 'Cartão Crédito', 'Cartão Débito', 'Deposito em conta', 'Usar Crédito'])
            ->where('codigo', '<>', '25')
            ->orderBy('nome_banco');
        }
            
        if(isset($fields['estabelecimento'])){
            $TitulosAReceberNasajonObj->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        if(isset($fields['banco'])){
            $TitulosAReceberNasajonObj->where('nome_banco', $fields['banco']);
        }

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && $fields['estabelecimento'] != 20){
            $TitulosAReceberNasajonObj->where('codigo', "!=", 20);
        }
        if(!isset($fields['intercompany'])){
            $cnpj_excluir[] = '05075884000167';
            $cnpj_excluir[] = '05075884000248';
            $cnpj_excluir[] = '06311274000269';
            $cnpj_excluir[] = '06311274000340';
            $cnpj_excluir[] = '08';
            $cnpj_excluir[] = '07';
            $cnpj_excluir[] = '06311274000501';
            $cnpj_excluir[] = '06311274000420';
        }
        if(count($cnpj_excluir) > 0){
            $TitulosAReceberNasajonObj->whereNotIn("cod_cliente", $cnpj_excluir);
        }

        if(count($usuRagazzi) > 0){
            $TitulosAReceberNasajonObj->whereIn("id_titulo", $titulosRagazzi);
        }


        if(!empty($fields['vendedor_representante']) && (isset($representante) && $representante != '998')){

            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){

                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);

                if(in_array('001', $representantes)){
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
                }

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
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
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
            
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
            }



        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
        strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
        $userid = Auth::user()->id;
        $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

        if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
        
            $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
        }

    }
        else if(!empty($fields['gerentes']) && empty($fields['vendedor_representante'])){

            if($gerente == 0){
                $gerentes = User::where('tipo_usuario_id', '19')->get()->pluck('id');

                $representantes = User::
                    whereIn('responsavel', $gerentes)
                    ->orWhereIn('id', $gerentes)
                    ->get()
                    ->pluck('codigo_representante')
                    ->toArray();
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
              
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
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

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                }




            }
        }

        $contas_a_receber_nasajon = $TitulosAReceberNasajonObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];

        foreach ($contas_a_receber_nasajon as $entrada) {
            $usuarioBaixa = '';
            $idTitulo = $entrada->id_titulo;

            $queryBaixa = BaixaTitulo::select('*');
            $queryBaixa->where('titulo_nasajon_id', $idTitulo);
            $queryBaixa->with(['criadoPor']);
            $resultBaixa = $queryBaixa->get();

            foreach ($resultBaixa as $baixa) {
                $usuarioBaixa = $baixa->criadoPor->name;
            }

            $dateVencimento = Carbon::createFromFormat('Y-m-d',$entrada->vencimento);

            if($dateVencimento->dayOfWeekIso == 6){ 
                $dateVencimento->addDays(2);
            }
            if($dateVencimento->dayOfWeekIso == 7){ 
                $dateVencimento->addDays(1);
            }
           
                $datePagamento = Carbon::createFromFormat('Y-m-d',$entrada->data_pagamento);
        
            if($dateVencimento->gt($datePagamento) && $entrada->valor > 0){
                
                $linha = [];

                $linha['estabelecimento'] = (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem';
                $linha['cliente'] = $entrada->nome_cliente;
                $linha['titulo'] = $entrada->numero;
                $linha['data_emissao'] = parserData($entrada->emissao);
                $linha['data_lancamento'] = parserData($entrada->data_lancamento);
                $linha['usuario'] = $usuarioBaixa;
                $linha['data_vencimento'] = parserData($entrada->vencimento);
                if($entrada->tem_prorrogacao === true ){
                    $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($entrada->vencimento_original).'"> *</a>';
                }
                $linha['data_considerada'] = parserData($dateVencimento);
                $linha['data_pagamento'] = parserData($entrada->data_pagamento);
                $linha['dias_atraso'] = ($dateVencimento->diffInDays($datePagamento,false) > 0) ? $dateVencimento->diffInDays($datePagamento) : '0';
                $linha['valor_original'] = $entrada->valor_titulo;
                $linha['valor_pago'] = $entrada->valor;
                $linha['juros'] = $entrada->valorjuros;
                $linha['multa'] = 0;
                $linha['desconto'] = $entrada->valordesconto;
                
                $response[] = $linha;
            }
        }

        $saida = ['valor_original' => 0,
                'valor_pago' => 0,
                'juros' => 0,
                'multa' => 0,
                'desconto' => 0
                ];

        foreach($response as $resp){
            $saida['valor_original'] += $resp['valor_original'];
            $saida['valor_pago'] += $resp['valor_pago'];
            $saida['juros'] += $resp['juros'];
            $saida['desconto'] += $resp['desconto'];
        }
        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] != '0') ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['multa'] = ($saida['multa'] != '0') ? parserValor($saida['multa']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';

        return view('programs.analise_contas_receber.modal.analitico_modal_index')->with(["saida" => $saida, 'response' => $response]);
    }

    public function showJuros(Request $request){
        $filter = $request->only(['filters','abertura']);
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
        if(!empty($fields['gerentes'])){
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
    }
        if(!empty($fields['armazen'])){
            if($fields['armazen'] == "false"){
                $fields['armazen'] = null;
            }
        }
        if(!empty($fields['intercompany'])){
            if($fields['intercompany'] == "false"){
                $fields['intercompany'] = null;
            }
        }

        $usuRagazzi = [];

        if(!empty($fields['ragazzi'])){
            if($fields['ragazzi'] == "false"){
                $fields['ragazzi'] = null;
            }else{
                $usuRagazzi[] = '1377';
                $usuRagazzi[] = '1384';
                $usuRagazzi[] = '1386';
                $usuRagazzi[] = '1387';
                $usuRagazzi[] = '1388';
                $usuRagazzi[] = '1397';
                $usuRagazzi[] = '1398';
                $usuRagazzi[] = '1442';
                $usuRagazzi[] = '1464';
                $usuRagazzi[] = '8313';

                $titulosRagazzi = $this->ReturnTitulos($usuRagazzi);
            }
        }

        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        
        $condicoesPagamentoNasajonObj = new CondicoesPagamentoNasajon;
        $TitulosAReceberNasajonObj = TitulosPagosNasajon::with('baixarPortal');

        if(count($usuRagazzi) > 0){
          
            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
           $TitulosAReceberNasajonObj->where('codigo', '<>', '25')
                        ->whereNotNull('data_pagamento')
            ->orderBy('nome_banco');
        }else{

            if($fields['data_filtro'] =='vencimento') {
                $TitulosAReceberNasajonObj->whereBetween('vencimento', [$data_inicio, $data_fim]);
           }else{
               $TitulosAReceberNasajonObj->whereBetween('data_pagamento', [$data_inicio, $data_fim]);
           }
           $TitulosAReceberNasajonObj->join($condicoesPagamentoNasajonObj->getTable(), 'integracoes.vw_df_formaspagamentos.id_docfis', 'documento_id')
            ->whereNotNull('data_pagamento')
            ->whereNotIn('formapagamento_descricao', ['Dinheiro', 'Cartão Crédito', 'Cartão Débito', 'Deposito em conta', 'Usar Crédito'])
            ->where('codigo', '<>', '25')
            ->orderBy('nome_banco');
        }
            
        if(isset($fields['estabelecimento'])){
            $TitulosAReceberNasajonObj->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        if(isset($fields['banco'])){
            $TitulosAReceberNasajonObj->where('nome_banco', $fields['banco']);
        }
  

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && $fields['estabelecimento'] != 20){
            $TitulosAReceberNasajonObj->where('codigo', "!=", 20);
        }
        if(!isset($fields['intercompany'])){
            $cnpj_excluir[] = '05075884000167';
            $cnpj_excluir[] = '05075884000248';
            $cnpj_excluir[] = '06311274000269';
            $cnpj_excluir[] = '06311274000340';
            $cnpj_excluir[] = '08';
            $cnpj_excluir[] = '07';
            $cnpj_excluir[] = '06311274000501';
            $cnpj_excluir[] = '06311274000420';
        }
        if(count($cnpj_excluir) > 0){
            $TitulosAReceberNasajonObj->whereNotIn("cod_cliente", $cnpj_excluir);
        }

        if(count($usuRagazzi) > 0){
            $TitulosAReceberNasajonObj->whereIn("id_titulo", $titulosRagazzi);
        }


        if(!empty($fields['vendedor_representante']) && (isset($representante) && $representante != '998')){

            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){

                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);

                if(in_array('001', $representantes)){
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
                }

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
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
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
            
                $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
            }



        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
        strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
        $userid = Auth::user()->id;
        $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

        if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
        
            $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
        }

    }
        else if(!empty($fields['gerentes']) && empty($fields['vendedor_representante'])){

            if($gerente == 0){
                $gerentes = User::where('tipo_usuario_id', '19')->get()->pluck('id');

                $representantes = User::
                    whereIn('responsavel', $gerentes)
                    ->orWhereIn('id', $gerentes)
                    ->get()
                    ->pluck('codigo_representante')
                    ->toArray();
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
              
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                    $TitulosAReceberNasajonObj->whereOrNull('vendedor_codigo');
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

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                
                    $TitulosAReceberNasajonObj->whereIn('vendedor_codigo', $representantes);
                }




            }
        }

        $contas_a_receber_nasajon = $TitulosAReceberNasajonObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];

        foreach ($contas_a_receber_nasajon as $entrada) {
            $usuarioBaixa = '';
            $idTitulo = $entrada->id_titulo;

            $queryBaixa = BaixaTitulo::select('*');
            $queryBaixa->where('titulo_nasajon_id', $idTitulo);
            $queryBaixa->with(['criadoPor']);
            $resultBaixa = $queryBaixa->get();

            foreach ($resultBaixa as $baixa) {
                $usuarioBaixa = $baixa->criadoPor->name;
            }

            $dateVencimento = Carbon::createFromFormat('Y-m-d',$entrada->vencimento);
           
            if($dateVencimento->dayOfWeekIso == 6){ 
                $dateVencimento->addDays(2);
            }
            if($dateVencimento->dayOfWeekIso == 7){ 
                $dateVencimento->addDays(1);
            }
            
                $datePagamento = Carbon::createFromFormat('Y-m-d',$entrada->data_pagamento);
      
            $multa=0;
            if(!empty($entrada->baixarPortal)){
                $multa =$entrada->baixarPortal->multa;
            
            }

            if($filter['abertura'] == 'multa'){
                $mostra = $multa;
            }else{
                $mostra =$entrada->valorjuros;

            }
           
            if( $mostra  > 0){
                $linha = [];
              
                $linha['estabelecimento'] = (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem';
                $linha['cliente'] = $entrada->nome_cliente;
                $linha['titulo'] = $entrada->numero;
                $linha['data_emissao'] = parserData($entrada->emissao);
                $linha['data_lancamento'] = parserData($entrada->data_lancamento);
                $linha['usuario'] = $usuarioBaixa;
                $linha['data_vencimento'] = parserData($entrada->vencimento);
                if($entrada->tem_prorrogacao === true ){
                    $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($entrada->vencimento_original).'"> *</a>';
                }
                $linha['data_considerada'] = parserData($dateVencimento);
                $linha['data_pagamento'] = parserData($entrada->data_pagamento);
                $linha['dias_atraso'] = ($dateVencimento->diffInDays($datePagamento,false) > 0) ? $dateVencimento->diffInDays($datePagamento) : '0';
                $linha['valor_original'] = $entrada->valor_titulo;
                $linha['valor_pago'] = $entrada->valor;
                $linha['juros'] = $entrada->valorjuros;
                $linha['multa'] = $multa;
                $linha['desconto'] = $entrada->valordesconto;
                
                $response[] = $linha;

            }
        }

        $saida = ['valor_original' => 0,
                'valor_pago' => 0,
                'juros' => 0,
                'multa' => 0,
                'desconto' => 0
                ];

        foreach($response as $resp){
            $saida['valor_original'] += $resp['valor_original'];
            $saida['valor_pago'] += $resp['valor_pago'];
            $saida['juros'] += $resp['juros'];
            $saida['multa'] += $resp['multa'];
            $saida['desconto'] += $resp['desconto'];
        }
        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] != '0') ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['multa'] = ($saida['multa'] != '0') ? parserValor($saida['multa']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';

        return view('programs.analise_contas_receber.modal.analitico_modal_index')->with(["saida" => $saida, 'response' => $response]);
    }

    public function dataFiltro(){

        $data_filtro = [
            "pagamento" => 'Pagamento',
            "vencimento" => 'Vencimento',
            

        ];
        return $data_filtro;
    }


  
}



