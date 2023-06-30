<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

use App\TitulosPagosNasajon;
use App\TitulosEmAbertoNasajonPortal;
use App\VendedorComissaoNota;
use App\User;
use App\TitulosVendedor998Nasajon;

use Carbon\Carbon;

class AnaliseInadimplenciaController extends Controller
{
    protected $formasDePagamentoExcluidas = ['Dinheiro', 'Cartão Crédito', 'Cartão Débito', 'Deposito em conta', 'Usar Crédito'];

    public function __construct() {
        $this->middleware(['auth']);
        $estabelecimentosEmpty[''] = 'ESTABELECIMENTO';
        $estabelecimentosReturn = returnEmpresasNasajonView();
        unset($estabelecimentosReturn[20]);
        unset($estabelecimentosReturn[30]);
        $estabelecimentos = array_merge($estabelecimentosEmpty,$estabelecimentosReturn );
        $this->estabelecimentos = $estabelecimentos;
    }

    private function ReturnBancos(){
        $bancos = TitulosEmAbertoNasajonPortal::selectRaw('distinct banco_nome as banco')->where('banco_nome', '<>',null)->get();
        $returnBancos = [];
        $returnBancos[''] = 'BANCOS';
        foreach($bancos as $banco){
            $returnBancos[$banco->banco] = strtoupper($banco->banco);
        }
        //$returnBancos['NULL'] = 'CARTEIRA';
        $banco = array_merge($returnBancos, $returnBancos);
        ksort($banco);
        return $banco;
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AnaliseInadimplencia") === false){
            return abort(403);
        };

        $bancos = $this->ReturnBancos();
        $request->session()->flash('model', 'App\AnaliseInadimplencia');

        $gerentes = [];
        $supervisores = [];
        $vendedor_representante = [];

        $check_gerentes = false;
        $check_supervisores = false;
        $check_vendedor_representante = false;
        
        if(!in_array(Auth::user()->tipo_usuario->nome, ["Diretor", "Administrador", "Interno"])){
            $subordinadosObj = UserController::varreSubordinados(Auth::id());
            if(strtolower(Auth::user()->tipo_usuario->nome) === "supervisor"){
                $subordinadosObj = UserController::varreSubordinados(Auth::user()->responsavel);
            }
            $userObj = User::whereIn('id', $subordinadosObj)->get();
            $userObj = $userObj->sortBy('name');

            if(Auth::user()->tipo_usuario_id == 19 && !empty(Auth::user()->codigo_representante)){
                $vendedor_representante[Crypt::encrypt(Auth::user()->id)] = strtoupper(Auth::user()->name);
            }

            foreach ($userObj as $key => $user) {
                if(strtolower($user->tipo_usuario->nome) === "gerente comercial"){
                    $gerentes[Crypt::encrypt($user->id)] = strtoupper($user->name);
                }else if(strtolower($user->tipo_usuario->nome) === "vendedor interno" || strtolower($user->tipo_usuario->nome) === "representante" || strtolower(Auth::user()->tipo_usuario->nome) === "supervisor" && strtolower($user->tipo_usuario->nome) === "supervisor"){
                    $vendedor_representante[Crypt::encrypt($user->id)] = strtoupper($user->name);
                }
            }
            unset($subordinadosObj);
        } else {
            $subordinadosObj = User::with(["tipo_usuario"])->get();
            $subordinadosObj = $subordinadosObj->sortBy('name');

            foreach ($subordinadosObj as $key => $userObj) {
                if($userObj->id === Auth::id() || empty($userObj->tipo_usuario)){
                    continue;
                }
                if(strtolower($userObj->tipo_usuario->nome) === "gerente comercial"){
                    $gerentes[Crypt::encrypt($userObj->id)] = strtoupper($userObj->name);
                }else if(strtolower($userObj->tipo_usuario->nome) === "vendedor interno" || strtolower($userObj->tipo_usuario->nome) === "representante"){
                    $vendedor_representante[Crypt::encrypt($userObj->id)] = strtoupper($userObj->name);
                }
            }
            $gerentes[Crypt::encrypt(0)] = 'OUTROS';

            unset($subordinadosObj);
        }

        if(strtolower(Auth::user()->tipo_usuario->nome) === "gerente comercial"){
            $check_vendedor_representante = true;
            $gerentes = [];
        }else if(strtolower(Auth::user()->tipo_usuario->nome) === "supervisor"){
            $check_vendedor_representante = true;
            $gerentes = [];
        } else if(
            strtolower(Auth::user()->tipo_usuario->nome) !== "vendedor interno" &&
            strtolower(Auth::user()->tipo_usuario->nome) !== "representante"
        ){
            $check_gerentes = true;
            $check_supervisores = true;
            $check_vendedor_representante = true;
        }

        $variaveis = [
            'estabelecimentos'              => $this->estabelecimentos,
            'bancos'                        => $bancos,
            'gerentes'                      => $gerentes,
            'check_gerentes'                => $check_gerentes,
            'supervisores'                  => $supervisores,
            'check_supervisores'            => $check_supervisores,
            'vendedor_representante'        => $vendedor_representante,
            'check_vendedor_representante'  => $check_vendedor_representante,
        ];

    	return view("programs.analise_inadimplencia.index")->with($variaveis);
    }

    public function filter(Request $request){
        set_time_limit(200);
        ini_set('memory_limit','1024M');
        $fields = $request->only(['estabelecimento','banco', 'data_inicio', 'data_fim', 'armazen', 'intercompany', 'gerentes', 'vendedor_representante','sem_juros']);

        $users = [];
        $gerente = '';

        if(Auth::user()->tipo_usuario_id == 19){
            $fields['gerentes'] = Crypt::encrypt(Auth::user()->id);
        }

        if(in_array(Auth::user()->tipo_usuario->nome, ['Vendedor Interno', 'Representante'])){
            $users = [Auth::user()->codigo_representante];
        }
        else{
            if (
                (
                    (
                        isset($fields['gerentes']) && 
                        !is_null($fields['gerentes'])
                    ) ||
                    in_array(Auth::user()->tipo_usuario_id, [19, 13])
                ) &&
                (!isset($fields['vendedor_representante']) ||
                empty($fields['vendedor_representante']) )
            ) {
                if(Auth::user()->tipo_usuario_id == 19){
                    $gerente = Auth::id();
                }
                else if(Auth::user()->tipo_usuario_id == 13){
                    $gerente = Auth::user()->responsavel;
                }
                else{
                    $gerente = Crypt::decrypt($fields['gerentes']);
                }

                if($gerente == 0){
                    $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial'); })->get()->pluck('id');

                    $users = User::
                        where(function ($query) use($gerentes){
                            $query->where(function ($q) use ($gerentes){
                                    $q->whereNotIn('id', $gerentes)
                                        ->whereNotIn('responsavel', $gerentes);
                                })
                                ->orWhereDoesntHave('tipo_usuario', function($q){
                                        $q->where('nivel', 3);
                                });
                        })
                        ->get()
                        ->pluck('codigo_representante')
                        ->unique()
                        ->toArray();
                }
                else{
                    $gerente_subordinados = User::
                        with([
                            'subordinados' => function($query){
                                $query->whereHas('tipo_usuario', function($query){
                                    $query->where('nivel', 3);
                                });
                            }])
                        ->where('id', $gerente)
                        ->first();

                    $users = $gerente_subordinados->subordinados ? $gerente_subordinados->subordinados->pluck('codigo_representante')->filter()->toArray() : [];

                    if(!empty($gerente_subordinados->codigo_representante)){
                        $users[] = $gerente_subordinados->codigo_representante;
                    }
                }
            }
            else if (
                (isset($fields['vendedor_representante']) &&
                !empty($fields['vendedor_representante']) )
            ) {
                $usuario = Crypt::decrypt($fields['vendedor_representante']);
                $users = [User::find($usuario)->codigo_representante];
            }
        }
        
        if(empty($users) && Auth::user()->tipo_usuario->nivel > 0){
            return response()->json([
                'status' => 'sucess',
                'message' => '',
                'error' => '', 
                'response' => [
                    'response' => [],
                    'saida' => [
                        'total' => '',
                        'emitidos' => '',
                        'pagos' => '',
                        'cancelado' => '',
                        'pago_adiantado' => '',
                        'percentual_adiantado' => '',
                        'pago_atraso' => '',
                        'percentual_atraso' => '',
                        'juros_pagos' => '',
                        'inadimplencia' => '',
                        'percentual_inadimplencia' => '',
                        'renegociado' => '',
                    ]
                ],
            ]);
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

        if(!empty($fields['sem_juros'])){
            if($fields['sem_juros'] == "false"){
                $fields['sem_juros'] = null;
            }
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $titulosPagosNasajonObj = new TitulosPagosNasajon;
        $titulosEmAbertoNasajonObj = new TitulosEmAbertoNasajonPortal;
        $vendedorComissaoNotaObj = new VendedorComissaoNota;
        $TitulosVendedor998NasajonObj = new TitulosVendedor998Nasajon;

        if(!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998' || isset($users[0]) && $users[0] == '998'){
        
            $queryBusca = 'with titulos as(
                select codigo,saldotitulo, valor + juros as valor_com_juros , situacao, vencimento, juros,valor as valor_sem_juros,valor_titulo
                from ' . $TitulosVendedor998NasajonObj->getTable() . '
                where saldotitulo > 0 and codigo not in (\'25\', \'TREINAMENTO\', \'30\') ';

            if(isset($data_inicio)){
                $queryBusca .= 'AND vencimento >= \'' . $data_inicio->format('Y-m-d 00:00:00'). '\' ';
            }
                
            if(isset($data_fim)){
                $queryBusca .= 'AND vencimento <= \'' . $data_fim->format('Y-m-d 00:00:00'). '\' ';
            }

            if(!empty($users)){
                $queryBusca .= 'AND (vendedor_codigo in (\'' . implode('\', \'', $users). '\') ';
                
                if(in_array('001', $users)){
                    $queryBusca .= 'OR vendedor_codigo is null';
                }

                $queryBusca .= ') ';
            }
            
            if(isset($fields['estabelecimento'])){
                $queryBusca .= 'AND codigo = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\' ';
            }
            if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
                $queryBusca .= 'AND nome_banco = \'' . $fields['banco'] . '\' ';
            }
            if($fields['banco'] == 'Carteira'){
                $queryBusca .= 'AND (nome_banco is null OR nome_banco =\'Carteira\')';
            }

            $cnpj_excluir = [];

            if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
                $queryBusca .= 'AND codigo <>  \'20\' ';
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
                $queryBusca .= 'AND cod_cliente not in (\'' . implode('\', \'', $cnpj_excluir). '\') ';
            }

            $queryBusca .= ')
                select *
                    from titulos t';

            $estabelecimentos = returnEmpresasNasajonView();

            $titulos = DB::connection('nasajon')->select($queryBusca);
            $response = [];
            $out = [];
            $total = [
                'emitidos' => 0,
                'pagos' => 0,
                'cancelado' => 0,
                'pago_adiantado' => 0,
                'pago_atraso' => 0,
                'juros_pagos' => 0,
                'inadimplencia' => 0,
                'percentual_adiantado' => 0,
                'percentual_atraso' => 0,
                'percentual_inadimplencia' => 0,
                'renegociado' =>0,
            ];

            foreach ($titulos as $titulo){
                $pagos = 0;
                $cancelado = 0;
                $pago_adiantado = 0;
                $pago_atraso = 0;
                $juros_pagos = 0;
                $inadimplencia = 0;
                
                $valor_conta=0;
                if(empty($fields['sem_juros'])){
                   
                    $valor_conta=$titulo->saldotitulo;
                }else{
                    $valor_conta =$titulo->valor_sem_juros;
                }


                if($titulo->situacao == 'Aberto'){
                    $inadimplencia = $valor_conta;
                }else if($titulo->situacao != 'Aberto' && $titulo->valor < 0){
                    $cancelado = $valor_conta;
                }else if($titulo->situacao != 'Aberto' && $titulo->valor > 0){
                    $pagos =$valor_conta;
                    $juros_pagos = $titulo->juros;
                    if($dateVencimento->gt($datePagamento)){
                        $pago_adiantado = $valor_conta;
                    }else{
                        $pago_atraso = $valor_conta;
                    }
                }
                $response[] = [
                    'estabelecimento' => $estabelecimentos[(integer)$titulo->codigo],
                    'emitidos' =>$valor_conta,
                    'pagos' => $pagos,
                    'cancelado' => $cancelado,
                    'pago_adiantado' => $pago_adiantado,
                    'pago_atraso' => $pago_atraso,
                    'juros_pagos' => $juros_pagos,
                    'inadimplencia' => $inadimplencia,
                    'estabelecimentofilter' => $titulo->codigo,
                    'renegociado' => 0,
                ];    
            };
            unset($titulos);

        }else{
            $queryAbertos = 'with titulos as(
                select "codigo",saldotitulo, valor + juros as valor_com_juros, "nota_id","valor",valor as valor_sem_juros
                from ' . $titulosEmAbertoNasajonObj->getTable() . '
                where "saldotitulo" > 0 and "codigo" not in (\'25\', \'TREINAMENTO\', \'30\') ';

            $queryPagos = 'with titulos as(
                select "vencimento", "data_pagamento", "codigo", "valor_titulo", "valor", "documento_id", "valorjuros", "nome_banco", "nome_cliente", "numero", "emissao", "valordesconto"
                ,renegociado from ' . $titulosPagosNasajonObj->getTable() . '
                where 
                    "codigo" not in (\'25\', \'TREINAMENTO\', \'30\') ';

            if(isset($data_inicio)){
                $queryAbertos .= 'AND "vencimento" >= \'' . $data_inicio->format('Y-m-d 00:00:00'). '\' ';
                $queryPagos .= 'AND "vencimento" >= \'' . $data_inicio->format('Y-m-d 00:00:00'). '\' ';
            }
                
            if(isset($data_fim)){
                $queryAbertos .= 'AND "vencimento" <= \'' . $data_fim->format('Y-m-d 00:00:00'). '\' ';
                $queryPagos .= 'AND "vencimento" <= \'' . $data_fim->format('Y-m-d 00:00:00'). '\' ';
            }

            if(!empty($users)){
                $queryAbertos .= 'AND ("vendedor_codigo" in (\'' . implode('\', \'', $users). '\') ';
                $queryPagos .= 'AND ("vendedor_codigo" in (\'' . implode('\', \'', $users). '\') ';
                
                if(in_array('001', $users)){
                    $queryAbertos .= 'OR "vendedor_codigo" is null';
                    $queryPagos .= 'OR "vendedor_codigo" is null';
                }

                $queryAbertos .= ') ';
                $queryPagos .= ') ';
            }
            
            if(isset($fields['estabelecimento'])){
                $queryAbertos .= 'AND codigo = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\' ';
                $queryPagos .= 'AND codigo = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\' ';
            }
            if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
                $queryAbertos .= 'AND banco_nome = \'' . $fields['banco'] . '\' AND enviado_para_banco = true ';
                $queryPagos .= 'AND nome_banco = \'' . $fields['banco'] . '\' ';
            }
            if($fields['banco'] == 'Carteira'){
                $queryAbertos .= 'AND enviado_para_banco = false ';
                $queryPagos .= 'AND (nome_banco is null OR nome_banco ilike \'Carteira\')';
            }

            $cnpj_excluir = [];

            if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
                $queryAbertos .= 'AND \'codigo\' <>  \'20\' ';
                $queryPagos .= 'AND \'codigo\' <>  \'20\' ';
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
                $queryAbertos .= 'AND ("cod_cliente" not in (\'' . implode('\', \'', $cnpj_excluir). '\')) ';
                $queryPagos .= 'AND ("cod_cliente" not in (\'' . implode('\', \'', $cnpj_excluir). '\')) ';
            }

            $queryPagos .= ')
                select *
                    from titulos t';

            $queryAbertos .= ')
                select *
                    from titulos t';

            $estabelecimentos = returnEmpresasNasajonView();
        
            $titulos_abertos = DB::connection('nasajon')->select($queryAbertos);

            $response = [];
            $out = [];
            $total = [
                    'emitidos' => 0,
                    'pagos' => 0,
                    'cancelado' => 0,
                    'pago_adiantado' => 0,
                    'pago_atraso' => 0,
                    'juros_pagos' => 0,
                    'inadimplencia' => 0,
                    'percentual_adiantado' => 0,
                    'percentual_atraso' => 0,
                    'percentual_inadimplencia' => 0,
                    'renegociado' => 0,
                    ];
                   
          foreach ($titulos_abertos as $abertos){
                $valor_conta=0;
         
                if(empty($fields['sem_juros'])){
                   
                    $valor_conta=$abertos->valor_com_juros;
                }else{
                    $valor_conta =$abertos->valor_sem_juros;
                }

                $response[] = [
                    'estabelecimento' => $estabelecimentos[(integer)$abertos->codigo],
                    'emitidos' =>$valor_conta,
                    'pagos' => 0,
                    'cancelado' => 0,
                    'pago_adiantado' => 0,
                    'pago_atraso' => 0,
                    'juros_pagos' => 0,
                    'inadimplencia' =>$valor_conta,
                    'estabelecimentofilter' => $abertos->codigo,
                    'renegociado' =>0,
                ];    
            };
            unset($titulos_abertos);
         
            $titulos_baixados = DB::connection('nasajon')->select($queryPagos);

       
            foreach ($titulos_baixados as $baixados){
                $dateVencimento = Carbon::createFromFormat('Y-m-d',$baixados->vencimento);
                $datePagamento = (isset($baixados->data_pagamento)) ? Carbon::createFromFormat('Y-m-d',$baixados->data_pagamento) : null;
                if($dateVencimento->dayOfWeekIso == 6){ 
                    $dateVencimento->addDays(2);
                }
                if($dateVencimento->dayOfWeekIso == 7){ 
                    $dateVencimento->addDays(1);
                }
              
                $response[] = [
                    'estabelecimento' => $estabelecimentos[(integer)$baixados->codigo],
                    'emitidos' => $baixados->valor_titulo,
                    'pagos' => ($baixados->valor > 0) ? $baixados->valor_titulo : 0,
                    'cancelado' => ($baixados->valor <= 0) ? $baixados->valor_titulo : 0,
                    'pago_adiantado' => ($datePagamento != null && $dateVencimento->gt($datePagamento) && $baixados->valor > 0) ? $baixados->valor_titulo : 0,
                    'pago_atraso' => ($datePagamento != null && $dateVencimento->lt($datePagamento) && $baixados->valor > 0) ? $baixados->valor_titulo : 0,
                    'juros_pagos' =>$baixados->valorjuros,
                    'inadimplencia' => 0,
                    'estabelecimentofilter' => $baixados->codigo,
                    'renegociado' =>($baixados->renegociado == true) ? $baixados->valor_titulo : 0,
                ];    
            };
            unset($titulos_baixados);
        }
 
        foreach($response as $value){
            if(!isset($out[$value['estabelecimento']])){
                $out[$value['estabelecimento']] = [
                    'estabelecimento' => $value['estabelecimento'],
                    'emitidos' => 0,
                    'pagos' => 0,
                    'cancelado' => 0,
                    'pago_adiantado' => 0,
                    'pago_atraso' => 0,
                    'juros_pagos' => 0,
                    'inadimplencia' => 0,
                    'renegociado' => 0,
                    'filter' => encrypt([
                        'estabelecimento' => $value['estabelecimentofilter'],
                        'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
                        'data_inicio' => $fields['data_inicio'],
                        'data_fim' => $fields['data_fim'],
                        'armazen' => ((isset($fields['armazen'])) ? $fields['armazen'] : null),
                        'intercompany' => ((isset($fields['intercompany'])) ? $fields['intercompany'] : null),
                        'sem_juros' => ((isset($fields['sem_juros'])) ? $fields['sem_juros'] : null),
                        'users' => $users,
                    ]),
                ];
            }
            $out[$value['estabelecimento']]['emitidos'] += $value['emitidos'];
            $out[$value['estabelecimento']]['pagos'] += $value['pagos'];
            $out[$value['estabelecimento']]['cancelado'] += $value['cancelado'];
            $out[$value['estabelecimento']]['pago_adiantado'] += $value['pago_adiantado'];
            $out[$value['estabelecimento']]['pago_atraso'] += $value['pago_atraso'];
            $out[$value['estabelecimento']]['juros_pagos'] += $value['juros_pagos'];
            $out[$value['estabelecimento']]['inadimplencia'] += $value['inadimplencia'];
            $out[$value['estabelecimento']]['renegociado'] += $value['renegociado'];
      
        }
     
        unset($response);
        foreach($out as $key => $value){
            $percentual_inadimplencia = ($value['emitidos'] > 0) ? ((($value['inadimplencia'] + $value['renegociado']) * 100) / $value['emitidos']) : 0;
            $percentual_adiantado = ($value['pagos'] > 0) ? (($value['pago_adiantado'] * 100) / $value['pagos'])  : 0;
            $percentual_atraso = ($value['pagos'] > 0) ? (($value['pago_atraso'] * 100) / $value['pagos']) : 0;
            $out[$key] = [
                'estabelecimento' => $value['estabelecimento'],
                'emitidos' => ($value['emitidos'] > 0) ? parserValor($value['emitidos']) : '',
                'pagos' => ($value['pagos'] > 0) ? parserValor($value['pagos']) : '',
                'cancelado' => ($value['cancelado'] > 0) ? parserValor($value['cancelado']) : '',
                'pago_adiantado' => ($value['pago_adiantado'] > 0) ? parserValor($value['pago_adiantado']) : '',
                'pago_atraso' => ($value['pago_atraso'] > 0) ? parserValor($value['pago_atraso']) : '',
                'juros_pagos' => ($value['juros_pagos'] > 0) ? parserValor($value['juros_pagos']) : '',
                'inadimplencia' => ($value['inadimplencia'] > 0) ? parserValor($value['inadimplencia']) : '',
                'percentual_adiantado' => ($percentual_adiantado > 0) ? parserValor($percentual_adiantado) : '',
                'percentual_atraso' => ($percentual_atraso > 0) ? parserValor($percentual_atraso) : '',
                'percentual_inadimplencia' => ($percentual_inadimplencia > 0) ? parserValor($percentual_inadimplencia) : '',
                'renegociado' => ($value['renegociado'] > 0) ? parserValor($value['renegociado']) : '',
                'filter' => $value['filter'],
       
            ];
            $total['emitidos'] += $value['emitidos'];
            $total['pagos'] += $value['pagos'];
            $total['cancelado'] += $value['cancelado'];
            $total['pago_adiantado'] += $value['pago_adiantado'];
            $total['pago_atraso'] += $value['pago_atraso'];
            $total['juros_pagos'] += $value['juros_pagos'];
            $total['inadimplencia'] += $value['inadimplencia'];
            $total['percentual_adiantado'] += $percentual_adiantado;
            $total['percentual_atraso'] += $percentual_atraso;
            $total['percentual_inadimplencia'] += $percentual_inadimplencia;
            $total['renegociado'] += $value['renegociado'];
        }

        $total['percentual_adiantado'] = ($total['pagos'] > 0 && $total['pago_adiantado'] > 0) ? parserValor(($total['pago_adiantado'] * 100) / $total['pagos']) : '';
        $total['percentual_atraso'] = ($total['pagos'] > 0 && $total['pago_atraso'] > 0) ? parserValor(($total['pago_atraso'] * 100) / $total['pagos']) : '';
        $total['percentual_inadimplencia'] = ($total['emitidos'] > 0 && $total['inadimplencia'] > 0) ? parserValor(($total['inadimplencia'] * 100) / $total['emitidos']) : '';
        $total['emitidos'] = ($total['emitidos'] > 0) ? parserValor($total['emitidos']) : '';
        $total['pagos'] = ($total['pagos'] > 0) ? parserValor($total['pagos']) : '';
        $total['cancelado'] = ($total['cancelado'] > 0) ? parserValor($total['cancelado']) : '';
        $total['pago_adiantado'] =($total['pago_adiantado'] > 0) ? parserValor($total['pago_adiantado']) : '';
        $total['pago_atraso'] = ($total['pago_atraso'] > 0) ? parserValor($total['pago_atraso']) : '';
        $total['juros_pagos'] = ($total['juros_pagos'] > 0) ? parserValor($total['juros_pagos']) : '';
        $total['inadimplencia'] = ($total['inadimplencia'] > 0) ? parserValor($total['inadimplencia']) : '';
        $total['renegociado'] = ($total['renegociado'] > 0) ? parserValor($total['renegociado']) : '';
        $total['estabelecimento'] = 'TODOS OS ESTABELECIMENTOS';
        $total['filter'] = encrypt([
            'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
            'data_inicio' => $fields['data_inicio'],
            'data_fim' => $fields['data_fim'],
            'armazen' => ((isset($fields['armazen'])) ? $fields['armazen'] : null),
            'intercompany' => ((isset($fields['intercompany'])) ? $fields['intercompany'] : null),
            'sem_juros' => ((isset($fields['sem_juros'])) ? $fields['sem_juros'] : null),
            'users' => $users,
        ]);

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['response' => $out, 'saida' => $total],
        ]);
    }

    public function TitulosAbertura(Request $request){
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

        $users = [];

        if(isset($fields['users']) && !empty($fields['users'])){
            $users = $fields['users'];
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $titulosPagosNasajonObj = new TitulosPagosNasajon;
        $TitulosVendedor998NasajonObj = new TitulosVendedor998Nasajon;
        
        if(!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998' || isset($users[0]) && $users[0] == '998'){
            $queryBusca = 'with titulos as(
                select vencimento, data_pagamento, vencimento_original, codigo, valor_titulo, valor, documento_id, juros as valorjuros, banco_nome as nome_banco, nome_cliente, numero, emissao, desconto as valordesconto, enviado_para_banco,saldotitulo, valor_titulo + juros as valor_com_juros, multa, tem_prorrogacao,valor_titulo as valor_sem_juros
                from ' . $TitulosVendedor998NasajonObj->getTable() . '
                where 
                    "codigo" not in (\'25\', \'TREINAMENTO\', \'30\') 
                    and "valor_titulo" > 0';

            if(isset($data_inicio) && !is_null($data_inicio)){
                $queryBusca .= ' and vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
            }

            if(isset($data_fim) && !is_null($data_fim)){
                $queryBusca .= ' and vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
            }

            if(isset($fields['dias'])){
                $queryBusca .= 'and vencimento - emissao = \''.$fields['dias'] . '\'';
            }
                
            if(isset($fields['estabelecimento'])){
                $queryBusca .= ' and codigo = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\'';
            }
            if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
                $queryBusca .= ' and nome_banco \'' . $fields['banco'] . '\'';
            }

            $cnpj_excluir = [];

            if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
                $queryBusca .= ' and codigo <> \'20\'';
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
                $queryBusca .= ' and "cod_cliente" not in (\'' . implode('\', \'', $cnpj_excluir) . '\')';
            }
            if($fields['banco'] == 'Carteira'){
                $queryBusca .= ' and ("nome_banco" is null or "nome_banco" ilike \'Carteira\')';
            }

            if(!empty($users)){
                $queryBusca .= ' and ("vendedor_codigo" in (\'' . implode('\', \'', $users) . '\')';

                if(in_array('001', $users)){
                    $queryBusca .= ' or "vendedor_codigo" is null';
                }

                $queryBusca .= ')';

            }

            $queryBusca .= ')
                select *
                    from titulos t';

            $titulos = DB::connection('nasajon')->select($queryBusca);
            $estabelecimentos = returnEmpresasNasajonView();

            $response = [];
            $saida = [
                'valor_original' => 0,
                'valor_pago' => 0,
                'juros' => 0,
                'desconto' => 0
            ];

            foreach ($titulos as $aberto) {
                $linha = [
                    'banco' => ($aberto->enviado_para_banco == true && isset($aberto->banco_nome)) ? $aberto->banco_nome : 'CARTEIRA',
                    'estabelecimento' => (isset($aberto->codigo)) ? $estabelecimentos[(integer)$aberto->codigo] : 'Não tem',
                    'cliente' => $aberto->nome_cliente,
                    'titulo' => $aberto->numero,
                    'data_emissao' => $aberto->emissao,
                    'data_vencimento_sql' => $aberto->vencimento,
                    'data_vencimento' => parserData($aberto->vencimento),
                    'valor_original' => ($aberto->saldotitulo > 0) ? parserValor($aberto->saldotitulo) : '',
                    'valor_pago' => ($aberto->valor - $aberto->saldotitulo > 0) ? parserValor($aberto->valor - $aberto->saldotitulo) : '',
                    'juros' => ($aberto->multa > 0) ? parserValor($aberto->multa) : '',
                    'desconto' => ($aberto->valordesconto > 0) ? parserValor($aberto->valordesconto) : '',
                ];

                if($aberto->tem_prorrogacao === true ){
                    $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($aberto->vencimento_original).'"> *</a>';
                }

                $response[] = $linha;
                $saida['valor_original'] += $aberto->saldotitulo;
                $saida['valor_pago'] += ($aberto->valor - $aberto->saldotitulo > 0) ? ($aberto->valor - $aberto->saldotitulo) : 0;
                $saida['juros'] += $aberto->multa;
                $saida['desconto'] += $aberto->valordesconto;
            }
        }else{
            $queryPagos = 'with titulos as(
                select "vencimento", "data_pagamento", "codigo", "valor_titulo", "valor", "documento_id", "valorjuros", "nome_banco", "nome_cliente", "numero", "emissao", "valordesconto"
                from ' . $titulosPagosNasajonObj->getTable() . '
                where 
                    "codigo" not in (\'25\', \'TREINAMENTO\', \'30\') 
                    and "valor_titulo" > 0';

            $TitulosAbertoNasajonObj = TitulosEmAbertoNasajonPortal::
                whereNotIn('codigo', ['25','20','TREINAMENTO', '30'])
                ->where('saldotitulo', '>', 0);

            if(isset($data_inicio) && !is_null($data_inicio)){
                $queryPagos .= ' and vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
                $TitulosAbertoNasajonObj->where('vencimento', '>=', $data_inicio);
            }

            if(isset($data_fim) && !is_null($data_fim)){
                $queryPagos .= ' and vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
                $TitulosAbertoNasajonObj->where('vencimento', '<=', $data_fim);
            }

            if(isset($fields['dias'])){
                $queryPagos .= 'and vencimento - emissao = \''.$fields['dias'] . '\'';
                $TitulosAbertoNasajonObj->whereRaw('vencimento - titulo_emissao = '.$fields['dias']);
            }
                
            if(isset($fields['estabelecimento'])){
                $queryPagos .= ' and codigo = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\'';
                $TitulosAbertoNasajonObj->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
            }
            if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
                $queryPagos .= ' and nome_banco \'' . $fields['banco'] . '\'';
                $TitulosAbertoNasajonObj->where('banco_nome', $fields['banco']);
                $TitulosAbertoNasajonObj->where('enviado_para_banco', true);
            }

            $cnpj_excluir = [];

            if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
                $queryPagos .= ' and codigo <> \'20\'';
                $TitulosAbertoNasajonObj->where('codigo', "<>", 20);
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
                $queryPagos .= ' and "cod_cliente" not in (\'' . implode('\', \'', $cnpj_excluir) . '\')';
                $TitulosAbertoNasajonObj->whereNotIn("cod_cliente", $cnpj_excluir);
            }
            if($fields['banco'] == 'Carteira'){
                $queryPagos .= ' and ("nome_banco" is null or "nome_banco" ilike \'Carteira\')';
                $TitulosAbertoNasajonObj->where('enviado_para_banco', false);
            }

            if(!empty($users)){
                $queryPagos .= ' and ("vendedor_codigo" in (\'' . implode('\', \'', $users) . '\')';

                if(in_array('001', $users)){
                    $queryPagos .= ' or "vendedor_codigo" is null';
                }

                $queryPagos .= ')';

                $vendedorComissaoNotaObj = new VendedorComissaoNota;

                $TitulosAbertoNasajonObj->whereIn('vendedor_codigo', $users);

                if(in_array('001', $users)){
                    $TitulosAbertoNasajonObj->orWhereNull('vendedor_codigo');
                }
            }

            $queryPagos .= ')
                select *
                    from titulos t';

            $titulos_baixados = DB::connection('nasajon')->select($queryPagos);
            $titulos_aberto = $TitulosAbertoNasajonObj->get();
            $estabelecimentos = returnEmpresasNasajonView();

            $response = [];
            $saida = ['valor_original' => 0,
            'valor_pago' => 0,
            'juros' => 0,
            'desconto' => 0
            ];

            foreach ($titulos_baixados as $entrada) {
                $response[] = [
                    'banco' => (isset($entrada->banco_nome)) ? $entrada->banco_nome : 'CARTEIRA',
                    'estabelecimento' => (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem',
                    'cliente' => $entrada->nome_cliente,
                    'titulo' => $entrada->numero,
                    'data_emissao' => $entrada->emissao,
                    'data_vencimento_sql' => $entrada->vencimento,
                    'data_vencimento' => parserData($entrada->vencimento),
                    'valor_original' => ($entrada->valor_titulo > 0) ? parserValor($entrada->valor_titulo) : '',
                    'valor_pago' => ($entrada->valor > 0) ? parserValor($entrada->valor) : '',
                    'juros' => ($entrada->valorjuros > 0) ? parserValor($entrada->valorjuros) : '',
                    'desconto' => ($entrada->valordesconto > 0) ? parserValor($entrada->valordesconto) : '',
                ];

                $saida['valor_original'] += $entrada->valor_titulo;
                $saida['valor_pago'] += $entrada->valor;
                $saida['juros'] += $entrada->valorjuros;
                $saida['desconto'] += $entrada->valordesconto;
            }
      
            foreach ($titulos_aberto as $aberto) {
                $linha = [
                    'banco' => ($aberto->enviado_para_banco == true) ? $aberto->banco_nome : 'CARTEIRA',
                    'estabelecimento' => (isset($aberto->codigo)) ? $estabelecimentos[(integer)$aberto->codigo] : 'Não tem',
                    'cliente' => $aberto->nome_cliente,
                    'titulo' => $aberto->numero,
                    'data_emissao' => $aberto->titulo_emissao,
                    'data_vencimento_sql' => $aberto->vencimento,
                    'data_vencimento' => parserData($aberto->vencimento),
                    'valor_original' => ($aberto->valor > 0) ? parserValor($aberto->valor) : '',
                    'valor_pago' => ($aberto->valor - $aberto->saldotitulo > 0) ? parserValor($aberto->valor - $aberto->saldotitulo) : '',
                    'juros' => ($aberto->juros > 0) ? parserValor($aberto->juros) : '',
                    'desconto' => ($aberto->valordesconto > 0) ? parserValor($aberto->valordesconto) : '',
                ];

                if($aberto->tem_prorrogacao === true ){
                    $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($aberto->vencimento_original).'"> *</a>';
                }

                $response[] = $linha;
                $saida['valor_original'] += $aberto->valor;
                $saida['valor_pago'] += ($aberto->valor - $aberto->saldotitulo > 0) ? ($aberto->valor - $aberto->saldotitulo) : 0;
                $saida['juros'] += $aberto->juros;
                $saida['desconto'] += $aberto->valordesconto;
            }

        }

        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] != '0') ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';
       
        return view('programs.analise_inadimplencia.modal.abertura_titulos_emitidos')->with(["saida" => $saida, 'response' => $response]);
    }

    public function TitulosPagosAbertura(Request $request){
        $filter = $request->only(['filters','renegociado']);
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
        $users = [];

        if(isset($fields['users']) && !empty($fields['users'])){
            $users = $fields['users'];
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $titulosPagosNasajonObj = new TitulosPagosNasajon;

        $queryPagos = 'with titulos as(
            select "vencimento", "data_pagamento", "codigo", "valor_titulo", "valor", "documento_id", "valorjuros", "nome_banco", "nome_cliente", "numero", "emissao", "valordesconto"
            ,renegociado from ' . $titulosPagosNasajonObj->getTable() . '
            where 
                "codigo" not in (\'25\', \'TREINAMENTO\', \'30\') ';
                
        if(isset($data_inicio) && !is_null($data_inicio)){
            $queryPagos .= ' and vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
        }

        if(isset($data_fim) && !is_null($data_fim)){
            $queryPagos .= ' and vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
        }
    
        if(isset($fields['dias'])){

            $queryPagos .= ' and (
                CASE
                    when cast(extract(isodow from vencimento) as integer) = 6 then ( vencimento - emissao) + 2
                    when cast(extract(isodow from vencimento) as integer) = 7 then ( vencimento - emissao) + 1
                    else vencimento - emissao
                END
            ) = ' . $fields['dias'];
        }
        if(isset($fields['estabelecimento'])){
            $queryPagos .= ' and codigo = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\'';
        }
        if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
            $queryPagos .= ' and nome_banco = \'' . $fields['banco'] . '\'';
        }
        
        if($fields['banco'] == 'Carteira'){
            $queryPagos .= ' and (nome_banco is null or nome_banco ilike \'Carteira\')';
        }

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
            $queryPagos .= ' and codigo <> \'20\'';
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
            $queryPagos .= ' and "cod_cliente" not in (\'' . implode('\', \'', $cnpj_excluir) . '\')';
        }

        if(isset($filter['renegociado']) && !empty($filter['renegociado'])){
          
           
             $queryPagos .= ' and "renegociado" = \'' . $filter['renegociado'] . '\'';
              
                   
        }


        if(!empty($users)){
            $queryPagos .= ' and ("vendedor_codigo" in (\'' . implode('\', \'', $users) . '\')';

            if(in_array('001', $users)){
                $queryPagos .= ' or "vendedor_codigo" is null';
            }

            $queryPagos .= ')';
        }

        $queryPagos .= ')
            select *
                from titulos t';

        $titulos_baixados = DB::connection('nasajon')->select($queryPagos);
        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];
        $saida = ['valor_original' => 0,
        'valor_pago' => 0,
        'juros' => 0,
        'desconto' => 0
        ];

        foreach ($titulos_baixados as $entrada) {
            $response[] = [
                'banco' => (isset($entrada->banco_nome)) ? $entrada->banco_nome : 'CARTEIRA',
                'estabelecimento' => (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem',
                'cliente' => $entrada->nome_cliente,
                'titulo' => $entrada->numero,
                'data_emissao' => $entrada->emissao,
                'data_vencimento' => $entrada->vencimento,
                'data_pagamento' => $entrada->data_pagamento,
                'valor_original' => ($entrada->valor_titulo > 0) ? parserValor($entrada->valor_titulo) : '',
                'valor_pago' => ($entrada->valor_titulo > 0 && $entrada->renegociado ==false) ? parserValor($entrada->valor) : '',
                'juros' => ($entrada->valorjuros > 0) ? parserValor($entrada->valorjuros) : '',
                'desconto' => ($entrada->valordesconto > 0) ? parserValor($entrada->valordesconto) : '',
            ];
            $saida['valor_original'] += $entrada->valor_titulo;
            $saida['valor_pago'] +=  ($entrada->renegociado ==false) ? $entrada->valor :0;
            $saida['juros'] += $entrada->valorjuros;
            $saida['desconto'] += $entrada->valordesconto;
        }

        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] != '0' &&  empty($filter['renegociado'])) ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';

        return view('programs.analise_inadimplencia.modal.abertura_titulos_pagos')->with(["saida" => $saida, 'response' => $response]);
    }

    public function TitulosCanceladosAbertura(Request $request){
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

        $users = [];

        if(isset($fields['users']) && !empty($fields['users'])){
            $users = $fields['users'];
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $titulosPagosNasajonObj = new TitulosPagosNasajon;

        $queryPagos = 'with titulos as(
            select "vencimento", "data_pagamento", "codigo", "valor_titulo", "valor", "documento_id", "valorjuros", "nome_banco", "nome_cliente", "numero", "emissao", "valordesconto"
            from ' . $titulosPagosNasajonObj->getTable() . '
            where 
                "codigo" not in (\'25\', \'TREINAMENTO\', \'30\') 
                and valor  <= 0';
                
        if(isset($data_inicio) && !is_null($data_inicio)){
            $queryPagos .= ' and vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
        }

        if(isset($data_fim) && !is_null($data_fim)){
            $queryPagos .= ' and vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
        }

        if(isset($fields['dias'])){
            $queryPagos .= ' and vencimento - emissao = \'' . $fields['dias'] . '\'';
        }
        if(isset($fields['estabelecimento'])){
            $queryPagos .= ' and codigo = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\'';
        }
        if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
            $queryPagos .= ' and nome_banco \'' . $fields['banco'] . '\'';
        }

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
            $queryPagos .= ' and codigo <> \'20\'';
        }
        
        if($fields['banco'] == 'Carteira'){
            $queryPagos .= ' and ("nome_banco" is null or nome_banco ilike \'Carteira\')';
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
            $queryPagos .= ' and "cod_cliente" not in (\'' . implode('\', \'', $cnpj_excluir) . '\')';
        }

        if(!empty($users)){
            $queryPagos .= ' and ("vendedor_codigo" in (\'' . implode('\', \'', $users) . '\')';

            if(in_array('001', $users)){
                $queryPagos .= ' or "vendedor_codigo" is null';
            }

            $queryPagos .= ')';
        }

        $queryPagos .= ')
            select *
                from titulos t';
               
        $titulos_baixados = DB::connection('nasajon')->select($queryPagos);
        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];
        $saida = ['valor_original' => 0,
        'valor_pago' => 0,
        'juros' => 0,
        'desconto' => 0
        ];

        foreach ($titulos_baixados as $entrada) {
            $response[] = [
                'banco' => (isset($entrada->banco_nome)) ? $entrada->banco_nome : 'CARTEIRA',
                'estabelecimento' => (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem',
                'cliente' => $entrada->nome_cliente,
                'titulo' => $entrada->numero,
                'data_emissao' => $entrada->emissao,
                'data_vencimento' => $entrada->vencimento,
                'data_pagamento' => $entrada->data_pagamento,
                'valor_original' => ($entrada->valor_titulo > 0) ? parserValor($entrada->valor_titulo) : '',
                'valor_pago' => ($entrada->valor > 0) ? parserValor($entrada->valor) : '',
                'juros' => ($entrada->valorjuros > 0) ? parserValor($entrada->valorjuros) : '',
                'desconto' => ($entrada->valordesconto > 0) ? parserValor($entrada->valordesconto) : '',
            ];
            $saida['valor_original'] += $entrada->valor_titulo;
            $saida['valor_pago'] += $entrada->valor;
            $saida['juros'] += $entrada->valorjuros;
            $saida['desconto'] += $entrada->valordesconto;
        }

        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] != '0') ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';

        return view('programs.analise_inadimplencia.modal.abertura_titulos_cancelados')->with(["saida" => $saida, 'response' => $response]);
    }

    public function TitulosAdiantadoAbertura(Request $request){
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

        $users = [];

        if(isset($fields['users']) && !empty($fields['users'])){
            $users = $fields['users'];
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $titulosPagosNasajonObj = new TitulosPagosNasajon;

        $queryPagos = '
        with titulos as(
            select vencimento, data_pagamento, codigo, valor_titulo, valor, documento_id, valorjuros, nome_banco, nome_cliente, numero, emissao, valordesconto
            from ' . $titulosPagosNasajonObj->getTable() . '
            where 
                codigo not in (\'25\', \'TREINAMENTO\', \'30\') 
                and valor > 0
                AND 
                CASE  
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END > data_pagamento';

        if(isset($data_inicio) && !is_null($data_inicio)){
            $queryPagos .= ' and vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
        }

        if(isset($data_fim) && !is_null($data_fim)){
            $queryPagos .= ' and vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
        }

        if(isset($fields['dias'])){
            $queryPagos .= ' and vencimento - emissao = \'' . $fields['dias'] . '\'';
        }
        if(isset($fields['estabelecimento'])){
            $queryPagos .= ' and codigo = \''. str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\'';
        }
        if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
            $queryPagos .= ' and nome_banco = \'' . $fields['banco'] . '\'';
        }
        if($fields['banco'] == 'Carteira'){
            $queryPagos .= ' and (nome_banco is null or nome_banco ilike \'Carteira\')';
        }
        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
            $queryPagos .= " and codigo <> '20'";
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
            $queryPagos .= ' and cod_cliente not in (\'' . implode('\', \'', $cnpj_excluir) . '\')';
        }

        if(!empty($users)){
            $queryPagos .= ' and (vendedor_codigo in (\'' . implode('\', \'', $users) . '\')';

            if(in_array('001', $users)){
                $queryPagos .= ' or vendedor_codigo is null';
            }

            $queryPagos .= ')';
        }

        $queryPagos .= ')
            select *
                from titulos t';

        $titulos_baixados = DB::connection('nasajon')->select($queryPagos);
        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];
        $saida = [
            'valor_original' => 0,
            'valor_pago' => 0,
            'juros' => 0,
            'desconto' => 0
        ];

        foreach ($titulos_baixados as $entrada) {
            $response[] = [
                'banco' => (isset($entrada->banco_nome)) ? $entrada->banco_nome : 'CARTEIRA',
                'estabelecimento' => (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem',
                'cliente' => $entrada->nome_cliente,
                'titulo' => $entrada->numero,
                'data_emissao' => $entrada->emissao,
                'data_vencimento' => $entrada->vencimento,
                'data_pagamento' => $entrada->data_pagamento,
                'valor_original' => ($entrada->valor_titulo > 0) ? parserValor($entrada->valor_titulo) : '',
                'valor_pago' => ($entrada->valor > 0) ? parserValor($entrada->valor) : '',
                'juros' => ($entrada->valorjuros > 0) ? parserValor($entrada->valorjuros) : '',
                'desconto' => ($entrada->valordesconto > 0) ? parserValor($entrada->valordesconto) : '',
            ];
            $saida['valor_original'] += $entrada->valor_titulo;
            $saida['valor_pago'] += $entrada->valor;
            $saida['juros'] += $entrada->valorjuros;
            $saida['desconto'] += $entrada->valordesconto;
        }

        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] != '0') ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';

        return view('programs.analise_inadimplencia.modal.abertura_titulos_adiantado')->with(["saida" => $saida, 'response' => $response]);
    }

    public function TitulosAtrasadosAbertura(Request $request){
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

        $users = [];

        if(isset($fields['users']) && !empty($fields['users'])){
            $users = $fields['users'];
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $titulosPagosNasajonObj =  new TitulosPagosNasajon;

        $queryPagos = 'with titulos as(
            select "vencimento", "data_pagamento", "codigo", "valor_titulo", "valor", "documento_id", "valorjuros", "nome_banco", "nome_cliente", "numero", "emissao", "valordesconto"
            from ' . $titulosPagosNasajonObj->getTable() . '
            where 
                "codigo" not in (\'25\', \'TREINAMENTO\', \'30\') 
                AND (
                    CASE  
                        WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                        WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                        ELSE vencimento
                    END < data_pagamento
                )';
        if(isset($data_inicio) && !is_null($data_inicio)){
            $queryPagos .= ' and "vencimento" >= \'' . $data_inicio->format('Y-m-d') . '\'';
        }

        if(isset($data_fim) && !is_null($data_fim)){
            $queryPagos .= ' and "vencimento" <= \'' . $data_fim->format('Y-m-d') . '\'';
        }

        if(isset($fields['dias'])){
            $queryPagos .= ' and (
                CASE
                    when cast(extract(isodow from vencimento) as integer) = 6 then (data_pagamento - vencimento) + 2
                    when cast(extract(isodow from vencimento) as integer) = 7 then (data_pagamento - vencimento) + 1
                    else data_pagamento - vencimento
                END
            ) = ' . $fields['dias'];
        }
        if(isset($fields['estabelecimento'])){
            $queryPagos .= ' and "codigo" = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\'';
        }
        if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
            $queryPagos .= ' and "nome_banco" \'' . $fields['banco'] . '\'';
        }

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
            $queryPagos .= ' and "codigo" <> \'20\'';
        }
        if($fields['banco'] == 'Carteira'){
            $queryPagos .= ' and ("nome_banco" is null or "nome_banco" ilike \'Carteira\')';
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
            $queryPagos .= ' and "cod_cliente" not in (\'' . implode('\', \'', $cnpj_excluir) . '\')';
        }

        if(!empty($users)){
            $queryPagos .= 'and ("vendedor_codigo" in (\'' . implode('\', \'', $users) . '\')';

            if(in_array('001', $users)){
                $queryPagos .= ' or "vendedor_codigo" is null';
            }

            $queryPagos .= ')';
        }

        $queryPagos .= ')
            select *
                from titulos t';

        $titulos_baixados = DB::connection('nasajon')->select($queryPagos);
        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];
        $saida = ['valor_original' => 0,
        'valor_pago' => 0,
        'juros' => 0,
        'desconto' => 0
        ];

        foreach ($titulos_baixados as $entrada) {
            $response[] = [
                'banco' => (isset($entrada->banco_nome)) ? $entrada->banco_nome : 'CARTEIRA',
                'estabelecimento' => (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem',
                'cliente' => $entrada->nome_cliente,
                'titulo' => $entrada->numero,
                'data_emissao' => $entrada->emissao,
                'data_vencimento' => $entrada->vencimento,
                'data_pagamento' => $entrada->data_pagamento,
                'valor_original' => ($entrada->valor_titulo > 0) ? parserValor($entrada->valor_titulo) : '',
                'valor_pago' => ($entrada->valor > 0) ? parserValor($entrada->valor) : '',
                'juros' => ($entrada->valorjuros > 0) ? parserValor($entrada->valorjuros) : '',
                'desconto' => ($entrada->valordesconto > 0) ? parserValor($entrada->valordesconto) : '',
            ];
            $saida['valor_original'] += $entrada->valor_titulo;
            $saida['valor_pago'] += $entrada->valor;
            $saida['juros'] += $entrada->valorjuros;
            $saida['desconto'] += $entrada->valordesconto;
        }

        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] != '0') ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';

        return view('programs.analise_inadimplencia.modal.abertura_titulos_atraso')->with(["saida" => $saida, 'response' => $response]);
    }

    public function TitulosInadimplenciaAbertura(Request $request){
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

        $users = [];

        if(isset($fields['users']) && !empty($fields['users'])){
            $users = $fields['users'];
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $TitulosAReceberNasajonObj = TitulosEmAbertoNasajonPortal::whereNotIn('codigo', ['25','20','TREINAMENTO', '30'])
            ->where('saldotitulo', '>', 0);

        if(isset($data_inicio) && !is_null($data_inicio)){
            $TitulosAReceberNasajonObj->where('vencimento', '>=', $data_inicio);
        }

        if(isset($data_fim) && !is_null($data_fim)){
            $TitulosAReceberNasajonObj->where('vencimento', '<=', $data_fim);
        }
        
        if(isset($fields['dias'])){
            $TitulosAReceberNasajonObj->whereRaw('vencimento - titulo_emissao = '.$fields['dias']);
        }
        if(isset($fields['estabelecimento'])){
            $TitulosAReceberNasajonObj->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
            $TitulosAReceberNasajonObj->where('banco_nome', $fields['banco']);
            $TitulosAReceberNasajonObj->where('enviado_para_banco', true);
        }
        if($fields['banco'] == 'Carteira'){
            $TitulosAReceberNasajonObj->where('enviado_para_banco', false);
        }
        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
            $TitulosAReceberNasajonObj->where('codigo', "<>", 20);
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
        
        if(!empty($users)){
            $TitulosAReceberNasajonObj->where(function($query) use ($users){
                if($users[0] == '998'){
                    $query->whereHas('vendedorTitulo',function($vendedor){
                        $vendedor->where('vendedor_codigo','998');
                    });
                }else{
                    $query->whereIn('vendedor_codigo', $users);
                }
                if(in_array('001', $users)){
                    $query->orWhereNull('vendedor_codigo');
                }
            });
        }

        $titulos_baixados = $TitulosAReceberNasajonObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];
        $saida = ['valor_original' => 0,
                'valor_pago' => 0,
                'juros' => 0,
                'desconto' => 0
                ];

        foreach ($titulos_baixados as $entrada) {
            $linha = [
                'banco' => ($entrada->enviado_para_banco == true) ? $entrada->banco_nome : 'CARTEIRA',
                'estabelecimento' => (isset($entrada->codigo)) ? $estabelecimentos[(integer)$entrada->codigo] : 'Não tem',
                'cliente' => $entrada->nome_cliente,
                'titulo' => $entrada->numero,
                'data_emissao' => $entrada->titulo_emissao,
                'data_vencimento_sql' => $entrada->vencimento,
                'data_vencimento' => parserData($entrada->vencimento),
                'valor_original' => ($entrada->valor > 0) ? parserValor($entrada->valor) : '',
                'valor_pago' => ($entrada->saldotitulo > 0) ? parserValor($entrada->saldotitulo) : '',
                'juros' => ($entrada->valorjuros > 0) ? parserValor($entrada->valorjuros) : '',
                'desconto' => ($entrada->valordesconto > 0) ? parserValor($entrada->valordesconto) : '',
            ];

            if($entrada->tem_prorrogacao === true ){
                $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($entrada->vencimento_original).'"> *</a>';
            }

            $response[] = $linha;

            $saida['valor_original'] += $entrada->valor;
            $saida['valor_pago'] += ($entrada->saldotitulo > 0) ? $entrada->saldotitulo : 0;
            $saida['juros'] += $entrada->valorjuros;
            $saida['desconto'] += $entrada->valordesconto;
        }

        $saida['valor_original'] = ($saida['valor_original'] != '0') ? parserValor($saida['valor_original']) : '';
        $saida['valor_pago'] = ($saida['valor_pago'] > '0') ? parserValor($saida['valor_pago']) : '';
        $saida['juros'] = ($saida['juros'] != '0') ? parserValor($saida['juros']) : '';
        $saida['desconto'] = ($saida['desconto'] != '0') ? parserValor($saida['desconto']) : '';

        return view('programs.analise_inadimplencia.modal.abertura_titulos_inadimplencia')->with(["saida" => $saida, 'response' => $response]);
    }

    public function ModalEmitidos(Request $request){
        $filter = $request->only(['filters']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        if(isset($busca['estabelecimento'])){
            $fields['estabelecimento'] =  $busca['estabelecimento'];
        }
        $fields['banco'] =  $busca['banco'];
        $fields['data_inicio'] =  $busca['data_inicio'];
        $fields['data_fim'] =  $busca['data_fim'];
        $fields['armazen'] =  $busca['armazen'];
        $fields['intercompany'] =  $busca['intercompany'];
        $fields['users'] =  $busca['users'];
        $fields['sem_juros'] =  $busca['sem_juros'];

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

        if(!empty($fields['sem_juros'])){
            if($fields['sem_juros'] == "false"){
                $fields['sem_juros'] = null;
            }
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $titulosPagosNasajonObj = new TitulosPagosNasajon;
        $TitulosVendedor998NasajonObj = new TitulosVendedor998Nasajon;

        if(!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998' || isset($busca['users'][0]) && $busca['users'][0] == '998'){
            $queryBusca = 'with titulos as(
                select vencimento - emissao as dias, valor_titulo,valor, documento_id,  (valor + juros) as valor_com_juros, (valor) as valor_sem_juros
                from ' . $TitulosVendedor998NasajonObj->getTable() . '
                where 
                    "codigo" not in (\'25\', \'TREINAMENTO\', \'30\') 
                    and "saldotitulo" > 0';
            
            if(isset($data_inicio) && !is_null($data_inicio)){
                $queryBusca .= ' and "vencimento" >= \'' . $data_inicio->format('Y-m-d') . '\'';
            }

            if(isset($data_fim) && !is_null($data_fim)){
                $queryBusca .= ' and "vencimento" <= \'' . $data_fim->format('Y-m-d') . '\'';
            }

            if(isset($fields['estabelecimento'])){
                $queryBusca .= ' and "codigo" = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\'';
            }

            if(isset($fields['users']) && !empty($fields['users'])){
                $queryBusca .= 'and ("vendedor_codigo" in (\'' . implode('\', \'', $fields['users']) . '\')';
                if(in_array('001', $fields['users'])){
                    $queryBusca .= ' or "vendedor_codigo" is null';
                }
                $queryBusca .= ')';
            }

            if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
                $queryBusca .= ' or "nome_banco" = \'' . $fields['banco'] . '\'';
            }
            if($fields['banco'] == 'Carteira'){
                $queryBusca .= ' or ("nome_banco" is null or "nome_banco ilike \'Carteira\')';
            }

            $cnpj_excluir = [];

            if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
                $queryBusca .= ' and "codigo" <> \'20\'';
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
                $queryBusca .= ' and "cod_cliente" not in (\'' . implode('\', \'', $cnpj_excluir) . '\')';
            }

        
      
                $queryBusca .= ')
                select sum(valor_com_juros) as valor_com_juros,  sum(valor_sem_juros) as valor_sem_juros,count(valor_titulo) as qtd, dias
                    from titulos t 
                group by "dias"';

            $titulos = DB::connection('nasajon')->select($queryBusca);

            $response = [];
            $out = [];
            $totaltitulos = 0;
            $totalquantidade = 0;
            $acumulado = 0;

            foreach ($titulos as $titulo){
            
                if(empty($fields['sem_juros'])){
                    $valor_conta =$titulo->valor_com_juros;
                }else{
                    $valor_conta= $titulo->valor_sem_juros;
                }


                $totaltitulos +=  $valor_conta;
                $response[] = [
                    'dias' => $titulo->dias,
                    'quantidade' => $titulo->qtd,
                    'valor' => $valor_conta,
                ];
            }
        }else{
            $queryPagos = 'with titulos as(
                select vencimento - emissao as dias, valor_titulo,valor, documento_id
                from ' . $titulosPagosNasajonObj->getTable() . '
                where 
                    "codigo" not in (\'25\', \'TREINAMENTO\', \'30\') 
                    and "valor_titulo" > 0';
            
            $TitulosAbertoNasajonObj = TitulosEmAbertoNasajonPortal::selectRaw('vencimento - titulo_emissao as dias, count(saldotitulo) as qtd, sum(saldotitulo) as saldo,
            sum(valor + juros) as valor_com_juros, sum(valor) as valor_sem_juros'
            )->groupBy('dias')
                ->where('saldotitulo', '>', 0)
                ->whereNotIn('codigo', ['25','20','TREINAMENTO', '30']);


            if(isset($data_inicio) && !is_null($data_inicio)){
                $queryPagos .= ' and "vencimento" >= \'' . $data_inicio->format('Y-m-d') . '\'';
                $TitulosAbertoNasajonObj->where('vencimento', '>=', $data_inicio);
            }

            if(isset($data_fim) && !is_null($data_fim)){
                $queryPagos .= ' and "vencimento" <= \'' . $data_fim->format('Y-m-d') . '\'';
                $TitulosAbertoNasajonObj->where('vencimento', '<=', $data_fim);
            }

            if(isset($fields['estabelecimento'])){
                $queryPagos .= ' and "codigo" = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\'';
                $TitulosAbertoNasajonObj->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
            }

            if(isset($fields['users']) && !empty($fields['users'])){
                
                $queryPagos .= 'and ("vendedor_codigo" in (\'' . implode('\', \'', $fields['users']) . '\')';

                if(in_array('001', $fields['users'])){
                    $queryPagos .= ' or "vendedor_codigo" is null';
                    $TitulosAbertoNasajonObj->where(function ($query){
                        $query->whereIn('vendedor_codigo', $fields['users'])->orWhereNull('vendedor_codigo');
                    });
                }
                else{
                    $TitulosAbertoNasajonObj->whereIn('vendedor_codigo', $fields['users']);
                }

                $queryPagos .= ')';

            }

            if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
                $queryPagos .= ' or "nome_banco" = \'' . $fields['banco'] . '\'';
                $TitulosAbertoNasajonObj->where('enviado_para_banco', true);
                $TitulosAbertoNasajonObj->where('banco_nome', $fields['banco']);
            }
            if($fields['banco'] == 'Carteira'){
                $queryPagos .= ' or ("nome_banco" is null or "nome_banco" ilike \'Carteira\')';
                $TitulosAbertoNasajonObj->where('enviado_para_banco', false);
            }

            $cnpj_excluir = [];

            if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
                $queryPagos .= ' and "codigo" <> \'20\'';
                $TitulosAbertoNasajonObj->where('codigo', "<>", 20);
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
                $queryPagos .= ' and "cod_cliente" not in (\'' . implode('\', \'', $cnpj_excluir) . '\')';
                $TitulosAbertoNasajonObj->whereNotIn("cod_cliente", $cnpj_excluir);
            }

            $queryPagos .= ')
                select sum(valor_titulo) as saldo, count(valor_titulo) as qtd, dias
                    from titulos t 
                group by "dias"';

            $titulos_baixados = DB::connection('nasajon')->select($queryPagos);
            $titulos_abertos = $TitulosAbertoNasajonObj->get();

            $response = [];
            $out = [];
            $totaltitulos = 0;
            $totalquantidade = 0;
            $acumulado = 0;

            foreach ($titulos_abertos as $abertos){
                if(empty($fields['sem_juros'])){
                    $valor_conta=  $abertos->valor_com_juros;
                }else{
                    $valor_conta= $abertos->valor_sem_juros;
                }
          
                $totaltitulos += $valor_conta;
                $response[] = [
                    'dias' => $abertos->dias,
                    'quantidade' => $abertos->qtd,
                    'valor' => $valor_conta,
                ];    
            }
            foreach ($titulos_baixados as $baixados){
                $totaltitulos += $baixados->saldo;
                $response[] = [
                    'dias' => $baixados->dias,
                    'quantidade' => $baixados->qtd,
                    'valor' => $baixados->saldo,
                ];    
            }
        }
        asort($response);
        foreach($response as $value){
            $acumulado += $value['valor'];
            if(!isset($out[$value['dias']])){
                
                $filter = [
                    'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
                    'data_inicio' => $fields['data_inicio'],
                    'data_fim' => $fields['data_fim'],
                    'armazen' => (isset($fields['armazen'])) ? $fields['armazen'] : null,
                    'intercompany' => (isset($fields['intercompany'])) ? $fields['intercompany'] : null,
                    'sem_juros' => (isset($fields['sem_juros'])) ? $fields['sem_juros'] : null,
                    'dias' => $value['dias'],
                    'users' => $fields['users'],
                ];

                if(isset($fields['estabelecimento'])){
                    $filter['estabelecimento'] = $fields['estabelecimento'];
                }

                $out[$value['dias']] = [
                    'dias' => $value['dias'],
                    'percentual' => 0,
                    'quantidade' => 0,
                    'valor' => 0,
                    'acumulado' => 0,
                    'percentual_acumulado' => 0,
                    'filter' => encrypt($filter),
                ];
            }

            $out[$value['dias']]['percentual'] = (($value['valor'] * 100) / $totaltitulos);
            $out[$value['dias']]['quantidade'] += $value['quantidade'];
            $out[$value['dias']]['valor'] += $value['valor'];
            $out[$value['dias']]['acumulado'] = $acumulado;
            $out[$value['dias']]['percentual_acumulado'] = (($acumulado * 100) / $totaltitulos);
        }

        foreach($out as $key => $value){
            $out[$key]['dias'] = $out[$key]['dias'];
            $out[$key]['percentual'] = parserValor($out[$key]['percentual']);
            $out[$key]['quantidade'] = $out[$key]['quantidade'];
            $out[$key]['valor'] = parserValor($out[$key]['valor']);
            $out[$key]['acumulado'] = parserValor($out[$key]['acumulado']);
            $out[$key]['percentual_acumulado'] = parserValor($out[$key]['percentual_acumulado']);
            $out[$key]['filter'] = $out[$key]['filter'];

            $totalquantidade += $out[$key]['quantidade'];
        }

        $filter = [
            'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
            'data_inicio' => $fields['data_inicio'],
            'data_fim' => $fields['data_fim'],
            'armazen' => (isset($fields['armazen'])) ? $fields['armazen'] : null,
            'intercompany' => (isset($fields['intercompany'])) ? $fields['intercompany'] : null,
            'users' => $fields['users'],
        ];

        if(isset($fields['estabelecimento'])){
            $filter['estabelecimento'] = $fields['estabelecimento'];
        }

        $totais = [
            'titulos' => parserValor($totaltitulos),
            'quantidade' => $totalquantidade,
            'filter' => encrypt($filter)
        ];

        return view('programs.analise_inadimplencia.modal.emitidos')->with(['response' => $out, 'totais' => $totais]);
    }

    public function ModalPagos(Request $request){
        $filter = $request->only(['filters','renegociado']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        if(isset($busca['estabelecimento'])){
            $fields['estabelecimento'] =  $busca['estabelecimento'];
        }
        $fields['banco'] =  $busca['banco'];
        $fields['data_inicio'] =  $busca['data_inicio'];
        $fields['data_fim'] =  $busca['data_fim'];
        $fields['armazen'] =  $busca['armazen'];
        $fields['intercompany'] =  $busca['intercompany'];
        $fields['users'] =  $busca['users'];

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

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $titulosPagosNasajonObj = new TitulosPagosNasajon;

        $queryPagos = 'with titulos as(
            select cast(extract(DAY from
            CASE 
                when cast(extract(isodow from vencimento) as integer) = 6 then (vencimento + interval \'2\' day)
                when cast(extract(isodow from vencimento) as integer) = 7 then (vencimento + interval \'1\' day)
                else vencimento
            END - emissao) as integer) as dias , valor_titulo, documento_id 
            from ' . $titulosPagosNasajonObj->getTable() . '
            where 
                "codigo" not in (\'25\', \'TREINAMENTO\', \'30\')
                and valor_titulo > 0';

        if(isset($data_inicio) && !is_null($data_inicio)){
            $queryPagos .= ' and CASE 
                when cast(extract(isodow from vencimento) as integer) = 6 then (vencimento + interval \'2\' day)
                when cast(extract(isodow from vencimento) as integer) = 7 then (vencimento + interval \'1\' day)
                else vencimento
            END >= \'' . $data_inicio->format('Y-m-d') . '\'';
        }

        if(isset($data_fim) && !is_null($data_fim)){
            $queryPagos .= ' and CASE 
                when cast(extract(isodow from vencimento) as integer) = 6 then (vencimento + interval \'2\' day)
                when cast(extract(isodow from vencimento) as integer) = 7 then (vencimento + interval \'1\' day)
                else vencimento
            END <= \'' . $data_fim->format('Y-m-d') . '\'';
        }
        
        if(isset($fields['estabelecimento'])){
            $queryPagos .= ' and "codigo" = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\'';
        }
        if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
            $queryPagos .= ' and "nome_banco" = \'' . $fields['banco'] . '\'';
        }
        if($fields['banco'] == 'Carteira'){
            $queryPagos .= ' and ("nome_banco" is null or "nome_banco" ilike \'Carteira\')';
        }

        if(isset($fields['users']) && !empty($fields['users'])){
            $vendedorComissaoNotaObj = new VendedorComissaoNota;

            $queryPagos .= ' and ("vendedor_codigo" in (\'' . implode('\',\'', $fields['users']) . '\')';

            if(in_array('001', $fields['users'])){
                $queryPagos .= ' or "vendedor_codigo" is null';
            }

            $queryPagos .= ')';
        }

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
            $queryPagos .= ' and "codigo" <> \'20\'';
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
            $queryPagos .= ' and "cod_cliente" not in (\'' . implode('\',\'', $cnpj_excluir) . '\')';
        }

        $renegociar ='';
    
        if(isset($filter['renegociado']) && !empty($filter['renegociado'])){
            if($filter['renegociado'] ==true){
                $renegociar=true;
                $queryPagos .= ' and "renegociado" = \'' . $filter['renegociado'] . '\'';
            }
          
        }

        $queryPagos .= ')
            select sum(valor_titulo) as saldo, count(valor_titulo) as qtd, dias
                from titulos t
            group by "dias"';
           
    
        $titulos_baixados = DB::connection('nasajon')->select($queryPagos);

        $response = [];
        $out = [];
        $totaltitulos = 0;
        $totalquantidade = 0;
        $acumulado = 0;

        foreach ($titulos_baixados as $baixados){
            $totaltitulos += $baixados->saldo;
            $response[] = [
                'dias' => $baixados->dias,
                'quantidade' => $baixados->qtd,
                'valor' => $baixados->saldo,
            ];    
        }
        asort($response);
        foreach($response as $value){
            $acumulado += $value['valor'];
            if(!isset($out[$value['dias']])){

                $filter = [
                    'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
                    'data_inicio' => $fields['data_inicio'],
                    'data_fim' => $fields['data_fim'],
                    'armazen' => (isset($fields['armazen'])) ? $fields['armazen'] : null,
                    'intercompany' => (isset($fields['intercompany'])) ? $fields['intercompany'] : null,
                    'dias' => $value['dias'],
                    'users' => $fields['users'],
                ];

                if(isset($fields['estabelecimento'])){
                    $filter['estabelecimento'] = $fields['estabelecimento'];
                }

                $out[$value['dias']] = [
                    'dias' => $value['dias'],
                    'percentual' => 0,
                    'quantidade' => 0,
                    'valor' => 0,
                    'acumulado' => 0,
                    'percentual_acumulado' => 0,
                    'filter' => encrypt($filter),
                ];
            }
            $out[$value['dias']]['percentual'] = (($value['valor'] * 100) / $totaltitulos);
            $out[$value['dias']]['quantidade'] += $value['quantidade'];
            $out[$value['dias']]['valor'] += $value['valor'];
            $out[$value['dias']]['acumulado'] = $acumulado;
            $out[$value['dias']]['percentual_acumulado'] = (($acumulado * 100) / $totaltitulos);
        }

        foreach($out as $key => $value){
            $out[$key]['dias'] = $out[$key]['dias'];
            $out[$key]['percentual'] = parserValor($out[$key]['percentual']);
            $out[$key]['quantidade'] = $out[$key]['quantidade'];
            $out[$key]['valor'] = parserValor($out[$key]['valor']);
            $out[$key]['acumulado'] = parserValor($out[$key]['acumulado']);
            $out[$key]['percentual_acumulado'] = parserValor($out[$key]['percentual_acumulado']);
            $out[$key]['filter'] = $out[$key]['filter'];

            $totalquantidade += $out[$key]['quantidade'];
        }

        $filter = [
            'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
            'data_inicio' => $fields['data_inicio'],
            'data_fim' => $fields['data_fim'],
            'armazen' => (isset($fields['armazen'])) ? $fields['armazen'] : null,
            'intercompany' => (isset($fields['intercompany'])) ? $fields['intercompany'] : null,
            'users' => $fields['users'],
        ];

        if(isset($fields['estabelecimento'])){
            $filter['estabelecimento'] = $fields['estabelecimento'];
        }

        $totais = [
            'titulos' => parserValor($totaltitulos),
            'quantidade' => $totalquantidade,
            'renegociado' =>$renegociar,
            'filter' => encrypt($filter)
        ];
        return view('programs.analise_inadimplencia.modal.pagos')->with(['response' => $out, 'totais' => $totais]);
    }

    public function ModalCancelados(Request $request){
        set_time_limit(200);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters','renegociado']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        if(isset($busca['estabelecimento'])){
            $fields['estabelecimento'] =  $busca['estabelecimento'];
        }
        $fields['banco'] =  $busca['banco'];
        $fields['data_inicio'] =  $busca['data_inicio'];
        $fields['data_fim'] =  $busca['data_fim'];
        $fields['armazen'] =  $busca['armazen'];
        $fields['intercompany'] =  $busca['intercompany'];
        $fields['users'] =  $busca['users'];

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

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $titulosPagosNasajonObj = new TitulosPagosNasajon;

        $queryPagos = 'with titulos as(
            select vencimento - emissao as dias, valor_titulo, documento_id
            from ' . $titulosPagosNasajonObj->getTable() . '
            where 
                "codigo" not in (\'25\', \'TREINAMENTO\', \'30\') 
                and "valor" <= 0';

        if(isset($data_inicio) && !is_null($data_inicio)){
            $queryPagos .= ' and "vencimento" >= \'' . $data_inicio->format('Y-m-d') . '\'';
        }

        if(isset($data_fim) && !is_null($data_fim)){
            $queryPagos .= ' and "vencimento" <= \'' . $data_fim->format('Y-m-d') . '\'';
        }
        
        if(isset($fields['estabelecimento'])){
            $queryPagos .= ' and "codigo" = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\'';
        }
        if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
            $queryPagos .= ' and "nome_banco" \'' . $fields['banco'] . '\'';
        }
        if($fields['banco'] == 'Carteira'){
            $queryPagos .= ' and ("nome_banco" is null or "nome_banco" ilike \'Carteira\')';
        }

        if(isset($fields['users']) && !empty($fields['users'])){

            $queryPagos .= 'AND ("vendedor_codigo" in (\'' . implode('\', \'', $fields['users']). '\') ';
            
            if(in_array('001', $fields['$users'])){
                $queryPagos .= 'OR "vendedor_codigo" is null';
            }

            $queryPagos .= ') ';
        }

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
            $queryPagos .= ' and "codigo" <> \'20\'';
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
            $queryPagos .= 'AND "cod_cliente" not in (\'' . implode('\', \'', $cnpj_excluir). '\') ';
        }

 
        if(isset($filter['renegociado']) && !empty($filter['renegociado'])){
         
                $queryPagos .= ' and "renegociado" = \'' . $filter['renegociado'] . '\'';
               
        }

        $queryPagos .= ')
            select dias, count(valor_titulo) as qtd, sum(valor_titulo) as saldo
                from titulos t
            group by dias';
      
        $titulos_baixados = collect(DB::connection('nasajon')->select($queryPagos));

        $response = [];
        $out = [];
        $totaltitulos = 0;
        $totalquantidade = 0;
        $acumulado = 0;

        foreach ($titulos_baixados as $baixados){
            $totaltitulos += $baixados->saldo;
            $response[] = [
                'dias' => $baixados->dias,
                'quantidade' => $baixados->qtd,
                'valor' => $baixados->saldo,
            ];    
        }
        asort($response);
        foreach($response as $value){
            $acumulado += $value['valor'];
            if(!isset($out[$value['dias']])){

                $filter = [
                    'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
                    'data_inicio' => $fields['data_inicio'],
                    'data_fim' => $fields['data_fim'],
                    'armazen' => (isset($fields['armazen'])) ? $fields['armazen'] : null,
                    'intercompany' => (isset($fields['intercompany'])) ? $fields['intercompany'] : null,
                    'dias' => $value['dias'],
                    'users' => $fields['users'],
                ];

                if(isset($fields['estabelecimento'])){
                    $filter['estabelecimento'] = $fields['estabelecimento'];
                }

                $out[$value['dias']] = [
                    'dias' => $value['dias'],
                    'percentual' => 0,
                    'quantidade' => 0,
                    'valor' => 0,
                    'acumulado' => 0,
                    'percentual_acumulado' => 0,
                    'filter' => encrypt($filter),
                ];
            }
            $out[$value['dias']]['percentual'] = (($value['valor'] * 100) / $totaltitulos);
            $out[$value['dias']]['quantidade'] += $value['quantidade'];
            $out[$value['dias']]['valor'] += $value['valor'];
            $out[$value['dias']]['acumulado'] = $acumulado;
            $out[$value['dias']]['percentual_acumulado'] = (($acumulado * 100) / $totaltitulos);
        }

        foreach($out as $key => $value){
            $out[$key]['dias'] = $out[$key]['dias'];
            $out[$key]['percentual'] = parserValor($out[$key]['percentual']);
            $out[$key]['quantidade'] = $out[$key]['quantidade'];
            $out[$key]['valor'] = parserValor($out[$key]['valor']);
            $out[$key]['acumulado'] = parserValor($out[$key]['acumulado']);
            $out[$key]['percentual_acumulado'] = parserValor($out[$key]['percentual_acumulado']);
            $out[$key]['filter'] = $out[$key]['filter'];

            $totalquantidade += $out[$key]['quantidade'];
        }

        $filter = [
            'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
            'data_inicio' => $fields['data_inicio'],
            'data_fim' => $fields['data_fim'],
            'armazen' => (isset($fields['armazen'])) ? $fields['armazen'] : null,
            'intercompany' => (isset($fields['intercompany'])) ? $fields['intercompany'] : null,
            'users' => $fields['users'],
        ];

        if(isset($fields['estabelecimento'])){
            $filter['estabelecimento'] = $fields['estabelecimento'];
        }

        $totais = [
            'titulos' => parserValor($totaltitulos),
            'quantidade' => $totalquantidade,
            'filter' => encrypt($filter)
        ];

        return view('programs.analise_inadimplencia.modal.cancelados')->with(['response' => $out, 'totais' => $totais]);
    }

    public function ModalAtraso(Request $request){
        $filter = $request->only(['filters']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        if(isset($busca['estabelecimento'])){
            $fields['estabelecimento'] =  $busca['estabelecimento'];
        }
        $fields['banco'] =  $busca['banco'];
        $fields['data_inicio'] =  $busca['data_inicio'];
        $fields['data_fim'] =  $busca['data_fim'];
        $fields['armazen'] =  $busca['armazen'];
        $fields['intercompany'] =  $busca['intercompany'];
        $fields['sem_juros'] =  $busca['sem_juros'];
        $fields['users'] =  $busca['users'];

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
        if(!empty($fields['sem_juros'])){
            if($fields['sem_juros'] == "false"){
                $fields['sem_juros'] = null;
            }
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $titulosPagosNasajonObj =  new TitulosPagosNasajon;

        $queryPagos = 'with titulos as(
            select 
                CASE 
                    when cast(extract(isodow from vencimento) as integer) = 6 then (data_pagamento - vencimento) + 2
                    when cast(extract(isodow from vencimento) as integer) = 7 then (data_pagamento - vencimento) + 1
                    else data_pagamento - vencimento
                END as dias, valor_titulo,valor, documento_id
            from ' . $titulosPagosNasajonObj->getTable() . '
            where 
                "codigo" not in (\'25\', \'TREINAMENTO\', \'30\') 
                and "valor" > 0 and
                CASE  
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END < data_pagamento';

        if(isset($data_inicio) && !is_null($data_inicio)){
            $queryPagos .= ' and "vencimento"  >= \'' . $data_inicio->format('Y-m-d') . '\'';
        }

        if(isset($data_fim) && !is_null($data_fim)){
            $queryPagos .= ' and "vencimento"  <= \''. $data_fim->format('Y-m-d') . '\'';
        }
        
        if(isset($fields['estabelecimento'])){
            $queryPagos .= ' and "codigo" = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\'';
        }
        if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
            $queryPagos .= ' and "nome_banco" = \'' . $fields['banco'] . '\'';
        }
        if($fields['banco'] == 'Carteira'){
            $queryPagos .= ' and ("nome_banco" is null or "nome_banco" ilike \'Carteira\')';
        }

        if(isset($fields['users']) && !empty($fields['users'])){
            $queryPagos .= ' and ("vendedor_codigo" in (\'' . implode('\', \'', $fields['users']) . '\')';

            if(in_array('001', $fields['users'])){
                $queryPagos .= ' or "vendedor_codigo" is null';
            }

            $queryPagos .= ')';
        }

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
            $queryPagos .= ' and "codigo" <> \'20\'';
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
            $queryPagos .= ' and "cod_cliente" not in (\'' . implode('\', \'', $cnpj_excluir) . '\')';
        }

        $queryPagos .= ')
            select sum(valor_titulo) as saldo, count(valor_titulo) as qtd, dias
                from titulos t
            group by "dias"';

        $titulos_baixados = DB::connection('nasajon')->select($queryPagos);

        $response = [];
        $out = [];
        $totaltitulos = 0;
        $totalquantidade = 0;
        $acumulado = 0;

        foreach ($titulos_baixados as $baixados){
           


            $totaltitulos +=  $baixados->saldo;
            $response[] = [
                'dias' => $baixados->dias,
                'quantidade' => $baixados->qtd,
                'valor' =>  $baixados->saldo,
            ];    
        }
        asort($response);
        foreach($response as $value){
            $acumulado += $value['valor'];
            if(!isset($out[$value['dias']])){

                $filter = [
                    'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
                    'data_inicio' => $fields['data_inicio'],
                    'data_fim' => $fields['data_fim'],
                    'armazen' => (isset($fields['armazen'])) ? $fields['armazen'] : null,
                    'intercompany' => (isset($fields['intercompany'])) ? $fields['intercompany'] : null,
                    'sem_juros' => (isset($fields['sem_juros'])) ? $fields['sem_juros'] : null,
                    'dias' => $value['dias'],
                    'users' => $fields['users'],
                ];

                if(isset($fields['estabelecimento'])){
                    $filter['estabelecimento'] = $fields['estabelecimento'];
                }

                $out[$value['dias']] = [
                    'dias' => $value['dias'],
                    'percentual' => 0,
                    'quantidade' => 0,
                    'valor' => 0,
                    'acumulado' => 0,
                    'percentual_acumulado' => 0,
                    'filter' => encrypt($filter),
                ];
            }
            $out[$value['dias']]['percentual'] = (($value['valor'] * 100) / $totaltitulos);
            $out[$value['dias']]['quantidade'] += $value['quantidade'];
            $out[$value['dias']]['valor'] += $value['valor'];
            $out[$value['dias']]['acumulado'] = $acumulado;
            $out[$value['dias']]['percentual_acumulado'] = (($acumulado * 100) / $totaltitulos);
        }

        foreach($out as $key => $value){
            $out[$key]['dias'] = $out[$key]['dias'];
            $out[$key]['percentual'] = parserValor($out[$key]['percentual']);
            $out[$key]['quantidade'] = $out[$key]['quantidade'];
            $out[$key]['valor'] = parserValor($out[$key]['valor']);
            $out[$key]['acumulado'] = parserValor($out[$key]['acumulado']);
            $out[$key]['percentual_acumulado'] = parserValor($out[$key]['percentual_acumulado']);
            $out[$key]['filter'] = $out[$key]['filter'];

            $totalquantidade += $out[$key]['quantidade'];
        }

        $filter = [
            'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
            'data_inicio' => $fields['data_inicio'],
            'data_fim' => $fields['data_fim'],
            'armazen' => (isset($fields['armazen'])) ? $fields['armazen'] : null,
            'intercompany' => (isset($fields['intercompany'])) ? $fields['intercompany'] : null,
            'sem_juros' => (isset($fields['sem_juros'])) ? $fields['sem_juros'] : null,
            'users' => $fields['users'],
        ];

        if(isset($fields['estabelecimento'])){
            $filter['estabelecimento'] = $fields['estabelecimento'];
        }

        $totais = [
            'titulos' => parserValor($totaltitulos),
            'quantidade' => $totalquantidade,
            'filter' => encrypt($filter)
        ];

        return view('programs.analise_inadimplencia.modal.atraso')->with(['response' => $out, 'totais' => $totais]);
    }

    public function ModalAdiantado(Request $request){
        $filter = $request->only(['filters']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        if(isset($busca['estabelecimento'])){
            $fields['estabelecimento'] =  $busca['estabelecimento'];
        }
        $fields['banco'] =  $busca['banco'];
        $fields['data_inicio'] =  $busca['data_inicio'];
        $fields['data_fim'] =  $busca['data_fim'];
        $fields['armazen'] =  $busca['armazen'];
        $fields['intercompany'] =  $busca['intercompany'];
        $fields['users'] =  $busca['users'];

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

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $titulosPagosNasajonObj = new TitulosPagosNasajon;

        $queryPagos = 'with titulos as(
            select vencimento - emissao as dias, valor_titulo, documento_id
            from ' . $titulosPagosNasajonObj->getTable() . '
            where 
                "codigo" not in (\'25\', \'TREINAMENTO\', \'30\') 
                and "valor" > 0 and
                CASE  
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END > data_pagamento';

        if(isset($data_inicio) && !is_null($data_inicio)){
            $queryPagos .= ' and "vencimento" >= \'' . $data_inicio->format('Y-m-d'). '\'';
        }

        if(isset($data_fim) && !is_null($data_fim)){
            $queryPagos .= ' and "vencimento" <= \'' . $data_fim->format('Y-m-d') . '\'';
        }
        
        if(isset($fields['estabelecimento'])){
            $queryPagos .= ' and "codigo" = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT). '\'';
        }
        if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
            $queryPagos .= ' and "nome_banco" = \'' . $fields['banco'] . '\'';
        }
        if($fields['banco'] == 'Carteira'){
            $queryPagos .= ' and ("nome_banco" is null or "nome_banco" ilike \'Carteira\')';
        }

        if(isset($fields['users']) && !empty($fields['users'])){
            $queryPagos .= ' and ("vendedor_codigo" in (\'' . implode('\', \'', $fields['users']) . '\')';

            if(in_array('001', $fields['users'])){
                $queryPagos .= ' or "vendedor_codigo" is null';
            }

            $queryPagos .= ')';
        }

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
            $queryPagos .= ' and "codigo" <> \'20\'';
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
            $queryPagos .= ' and "cod_cliente" not in (\'' . implode('\', \'', $cnpj_excluir) . '\')';
        }

        $queryPagos .= ')
        select
            dias, count(valor_titulo) as qtd, sum(valor_titulo) as saldo
        from
            titulos t
        group by dias';

        $titulos_baixados = DB::connection('nasajon')->select($queryPagos);

        $response = [];
        $out = [];
        $totaltitulos = 0;
        $totalquantidade = 0;
        $acumulado = 0;

        foreach ($titulos_baixados as $baixados){
            $totaltitulos += $baixados->saldo;
            $response[] = [
                'dias' => $baixados->dias,
                'quantidade' => $baixados->qtd,
                'valor' => $baixados->saldo,
            ];    
        }
        asort($response);
        foreach($response as $value){
            $acumulado += $value['valor'];
            if(!isset($out[$value['dias']])){

                $filter = [
                    'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
                    'data_inicio' => $fields['data_inicio'],
                    'data_fim' => $fields['data_fim'],
                    'armazen' => (isset($fields['armazen'])) ? $fields['armazen'] : null,
                    'intercompany' => (isset($fields['intercompany'])) ? $fields['intercompany'] : null,
                    'dias' => $value['dias'],
                    'users' => $fields['users'],
                ];

                if(isset($fields['estabelecimento'])){
                    $filter['estabelecimento'] = $fields['estabelecimento'];
                }

                $out[$value['dias']] = [
                    'dias' => $value['dias'],
                    'percentual' => 0,
                    'quantidade' => 0,
                    'valor' => 0,
                    'acumulado' => 0,
                    'percentual_acumulado' => 0,
                    'filter' => encrypt($filter),
                ];
            }
            $out[$value['dias']]['percentual'] = (($value['valor'] * 100) / $totaltitulos);
            $out[$value['dias']]['quantidade'] += $value['quantidade'];
            $out[$value['dias']]['valor'] += $value['valor'];
            $out[$value['dias']]['acumulado'] = $acumulado;
            $out[$value['dias']]['percentual_acumulado'] = (($acumulado * 100) / $totaltitulos);
        }

        foreach($out as $key => $value){
            $out[$key]['dias'] = $out[$key]['dias'];
            $out[$key]['percentual'] = parserValor($out[$key]['percentual']);
            $out[$key]['quantidade'] = $out[$key]['quantidade'];
            $out[$key]['valor'] = parserValor($out[$key]['valor']);
            $out[$key]['acumulado'] = parserValor($out[$key]['acumulado']);
            $out[$key]['percentual_acumulado'] = parserValor($out[$key]['percentual_acumulado']);
            $out[$key]['filter'] = $out[$key]['filter'];

            $totalquantidade += $out[$key]['quantidade'];
        }

        $filter = [
            'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
            'data_inicio' => $fields['data_inicio'],
            'data_fim' => $fields['data_fim'],
            'armazen' => (isset($fields['armazen'])) ? $fields['armazen'] : null,
            'intercompany' => (isset($fields['intercompany'])) ? $fields['intercompany'] : null,
            'users' => $fields['users'],
        ];

        if(isset($fields['estabelecimento'])){
            $filter['estabelecimento'] = $fields['estabelecimento'];
        }

        $totais = [
            'titulos' => parserValor($totaltitulos),
            'quantidade' => $totalquantidade,
            'filter' => encrypt($filter)
        ];

        return view('programs.analise_inadimplencia.modal.adiantado')->with(['response' => $out, 'totais' => $totais]);
    }

    public function ModalInadimplencia(Request $request){
        $filter = $request->only(['filters']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        if(isset($busca['estabelecimento'])){
            $fields['estabelecimento'] =  $busca['estabelecimento'];
        }
        $fields['banco'] =  $busca['banco'];
        $fields['data_inicio'] =  $busca['data_inicio'];
        $fields['data_fim'] =  $busca['data_fim'];
        $fields['armazen'] =  $busca['armazen'];
        $fields['intercompany'] =  $busca['intercompany'];
        $fields['sem_juros'] =  $busca['sem_juros'];
        $fields['users'] =  $busca['users'];

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

        if(!empty($fields['sem_juros'])){
            if($fields['sem_juros'] == "false"){
                $fields['sem_juros'] = null;
            }
        }


        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $titulosEmAbertoNasajonObj = new TitulosEmAbertoNasajonPortal;

        $TitulosAbertoNasajonObj = TitulosEmAbertoNasajonPortal::selectRaw('vencimento - titulo_emissao as dias, sum(saldotitulo) as saldo, count(titulo_emissao) as qtd
        ,sum(saldotitulo - juros) as valor_sem_juros')->groupBy('dias')
            ->whereNotIn('codigo', ['25','20','TREINAMENTO', '30'])
            ->where('saldotitulo', '>', 0);

        if(isset($data_inicio) && !is_null($data_inicio)){
            $TitulosAbertoNasajonObj->where('vencimento', '>=', $data_inicio);
        }

        if(isset($data_fim) && !is_null($data_fim)){
            $TitulosAbertoNasajonObj->where('vencimento', '<=', $data_fim);
        }
        
        if(isset($fields['estabelecimento'])){
            $TitulosAbertoNasajonObj->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
            $TitulosAbertoNasajonObj->where('banco_nome', $fields['banco']);
            $TitulosAbertoNasajonObj->where('enviado_para_banco', true);
        }
        if($fields['banco'] == 'Carteira'){
            $TitulosAbertoNasajonObj->where('enviado_para_banco', false);
        }

        if(isset($fields['users']) && !empty($fields['users'])){
            $TitulosAbertoNasajonObj->where(function($query) use ($fields){
                if($fields['users'][0] == '998'){
                    $query->whereHas('vendedorTitulo',function($vendedor){
                        $vendedor->where('vendedor_codigo','998');
                    });
                }else{
                    $query->whereIn('vendedor_codigo', $fields['users']);
                }
                if(in_array('001', $fields['users'])){
                    $query->orWhereNull('vendedor_codigo');
                }
            });
        }

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
            $TitulosAbertoNasajonObj->where('codigo', "<>", 20);
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
            $TitulosAbertoNasajonObj->whereNotIn("cod_cliente", $cnpj_excluir);
        }

        $titulos_abertos = $TitulosAbertoNasajonObj->get();

        $response = [];
        $out = [];
        $totaltitulos = 0;
        $totalquantidade = 0;
        $acumulado = 0;
        $valor_original=0;

        foreach ($titulos_abertos as $abertos){
            $valor_conta =0;
            if(empty($fields['sem_juros'])){
                $valor_conta =$abertos->saldo;
            }else{
                $valor_conta= $abertos->valor_sem_juros;
            }
            $totaltitulos +=  $valor_conta;
            $response[] = [
                'dias' => $abertos->dias,
                'quantidade' => $abertos->qtd,
                'valor' =>$valor_conta,
            ];    
        }
        asort($response);
        foreach($response as $value){
            $acumulado += $value['valor'];
            if(!isset($out[$value['dias']])){

                $filter = [
                    'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
                    'data_inicio' => $fields['data_inicio'],
                    'data_fim' => $fields['data_fim'],
                    'armazen' => (isset($fields['armazen'])) ? $fields['armazen'] : null,
                    'intercompany' => (isset($fields['intercompany'])) ? $fields['intercompany'] : null,
                    'sem_juros' => (isset($fields['sem_juros'])) ? $fields['sem_juros'] : null,
                    'dias' => $value['dias'],
                    'users' => $fields['users'],
                ];

                if(isset($fields['estabelecimento'])){
                    $filter['estabelecimento'] = $fields['estabelecimento'];
                }

                $out[$value['dias']] = [
                    'dias' => $value['dias'],
                    'percentual' => 0,
                    'quantidade' => 0,
                    'valor' => 0,
                    'acumulado' => 0,
                    'percentual_acumulado' => 0,
                    'filter' => encrypt($filter),
                ];
            }
            $out[$value['dias']]['percentual'] = (($value['valor'] * 100) / $totaltitulos);
            $out[$value['dias']]['quantidade'] += $value['quantidade'];
            $out[$value['dias']]['valor'] += $value['valor'];
            $out[$value['dias']]['acumulado'] = $acumulado;
            $out[$value['dias']]['percentual_acumulado'] = (($acumulado * 100) / $totaltitulos);
        }

        foreach($out as $key => $value){

            $valor_original += ($out[$key]['valor']);
            $out[$key]['dias'] = $out[$key]['dias'];
            $out[$key]['percentual'] = parserValor($out[$key]['percentual']);
            $out[$key]['quantidade'] = $out[$key]['quantidade'];
            $out[$key]['valor'] = parserValor($out[$key]['valor']);
            $out[$key]['acumulado'] = parserValor($out[$key]['acumulado']);
            $out[$key]['percentual_acumulado'] = parserValor($out[$key]['percentual_acumulado']);
            $out[$key]['filter'] = $out[$key]['filter'];
            $totalquantidade += $out[$key]['quantidade'];
          
          
        }

        $filter = [
            'banco' => (isset($fields['banco'])) ? $fields['banco'] : null,
            'data_inicio' => $fields['data_inicio'],
            'data_fim' => $fields['data_fim'],
            'armazen' => (isset($fields['armazen'])) ? $fields['armazen'] : null,
            'intercompany' => (isset($fields['intercompany'])) ? $fields['intercompany'] : null,
            'sem_juros' => (isset($fields['sem_juros'])) ? $fields['sem_juros'] : null,
            'users' => $fields['users'],
        ];

        if(isset($fields['estabelecimento'])){
            $filter['estabelecimento'] = $fields['estabelecimento'];
        }

        $totais = [
            'quantidade' => $totalquantidade,
            'valor_original' => parserValor($valor_original),
            'filter' => encrypt($filter)
        ];

        return view('programs.analise_inadimplencia.modal.inadimplencia')->with(['response' => $out, 'totais' => $totais]);
    }

    public function pagosDiaADiaValores(Request $request){
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

        $users = [];

        if(isset($fields['users']) && !empty($fields['users'])){
            $users = $fields['users'];
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = $fields['data_inicio'];
            $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->setTime(0,0,0);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = $fields['data_fim'];
            $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->setTime(23,59,59);
        }

        $titulosPagosNasajonObj = new TitulosPagosNasajon;

        $queryPagos = 'with titulos as(
            select "vencimento", "data_pagamento", "codigo", "valor_titulo", "valor", "documento_id", "valorjuros", "nome_banco", "nome_cliente", "numero", "emissao", "valordesconto"
            from '. $titulosPagosNasajonObj->getTable() . '
            where 
                "codigo" not in (\'25\', \'TREINAMENTO\' ,\'30\') ';


        if(isset($data_inicio)){
            $queryPagos .= 'AND "vencimento" >= \'' . $data_inicio->format('Y-m-d'). '\' ';
        }
            
        if(isset($data_fim)){
            $queryPagos .= 'AND "vencimento" <= \'' . $data_fim->format('Y-m-d'). '\' ';
        }

        if(!empty($users)){
            $queryPagos .= 'AND ("vendedor_codigo" in (\'' . implode('\', \'', $users). '\') ';
            
            if(in_array('001', $users)){
                $queryPagos .= 'OR "vendedor_codigo" is null';
            }

            $queryPagos .= ') ';
        }
        
        if(isset($fields['estabelecimento'])){
            $queryPagos .= 'AND codigo = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\' ';
        }
        if(isset($fields['banco']) && $fields['banco'] != 'Carteira'){
            $queryPagos .= 'AND nome_banco = \'' . $fields['banco'] . '\' ';
        }
        if($fields['banco'] == 'Carteira'){
            $queryPagos .= 'AND (nome_banco is null or nome_banco ilike \'Carteira\') ';
        }

        $cnpj_excluir = [];

        if(!isset($fields['armazen']) && (!isset($fields['estabelecimento']) || $fields['estabelecimento'] != 20)){
            $queryPagos .= 'AND \'codigo\' <>  \'20\' ';
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
            $queryPagos .= 'AND ("cod_cliente" not in (\'' . implode('\', \'', $cnpj_excluir). '\')) ';
        }

        $queryPagos .= ')
            select *
                from titulos t';

        $titulos_baixados = collect(DB::connection('nasajon')->select($queryPagos));
        $titulos_baixados = $titulos_baixados->sortBy('data_pagamento');

        $retorno = [];

        $titulos_baixados->each(function ($titulo) use(&$retorno){

            $dataPagamentoCarbon = Carbon::parse($titulo->data_pagamento);

            if(!isset($retorno[$titulo->data_pagamento])){
                $retorno[$titulo->data_pagamento] = 0;
            }

            $retorno[$titulo->data_pagamento] += $titulo->valor_titulo;
        });

        foreach($retorno as $data => $linha){
            $retorno[$data] = parserValor($linha);
        }

        $total = parserValor($titulos_baixados->sum('valor_titulo'));

        return view('programs.analise_inadimplencia.modal.dia_a_dia')->with(['retorno' => $retorno, 'total' => $total]);
    }
}
