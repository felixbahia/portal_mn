<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use Carbon\Carbon;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

use App\User;
use App\TituloExcluido;
use App\ClienteNasajon;
use App\GrupoEmpresarial;

class TituloExcluidoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\TituloExcluido") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\TituloExcluido');
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
        }else{
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

    	return view("programs.titulo_excluido.index", $variaveis_view);
    }

    public function filtro(Request $request){
        ini_set('memory_limit', '256M');
        set_time_limit(300);
        $fields  = $request->only('gerentes', 'vendedor_representante', 'cliente_nome','data_inicio','data_fim', 'titulos', 'cheque', 'titulos_pre', 'cheques_pre');

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
        
        $titulosEmAbertoNasajonObj = new TituloExcluido;

        $queryAbertos = 'with titulos as(
            select "codigo", "saldotitulo", "nota_id", "numero", "parcela", "titulo_emissao", "vencimento", "tem_prorrogacao", "valor", "multa", "nome_cliente", "cnpj", "juros", "datainiciomulta", "desconto", "nossonumero", "enviado_para_banco", "banco_codigo", "observacao"
            from ' . $titulosEmAbertoNasajonObj->getTable() . ' b
            where "saldotitulo" > 0 ';

        $hoje = Carbon::now()->format('Y-m-d');                
        
        if(!empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
        }
        
        if(!empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
        }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\')';
        
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
        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
    
            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
            }
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

            }
        }

        if(!empty($fields['cliente_nome']) ){
            $cliente = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'),'ilike', trim($fields['cliente_nome']))->first();
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

            $chequesPreObj = $chequesPreObj->filter(function($cheque) use ($representantes){
                if(in_array('001', $representantes)){
                    return in_array($cheque->pedidos_prepagos->first()->pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes) ||
                        empty($cheque->pedidos_prepagos->first()->pedido->pedidoNasajon->vendedor_detalhes->codigo) ||
                        !isset($cheque->pedidos_prepagos->first()->pedido->pedidoNasajon->vendedor_detalhes->codigo);
                }
                else{
                    return in_array($cheque->pedidos_prepagos->first()->pedido->pedidoNasajon->vendedor_detalhes->codigo, $representantes);
                }
            });
        }

        $queryAbertos .= ')
            select *
                from titulos t';

        $TitulosAberto = collect(DB::connection('pgsql')->select($queryAbertos));
        $estabelecimentos = returnTodasEmpresasView();
        $retorno = [];

        foreach($TitulosAberto as $titulo){
            $data_vencimento = Carbon::createFromFormat('Y-m-d',$titulo->vencimento);
            $data = Carbon::now();    
            if($data_vencimento->dayOfWeekIso == 6){ 
                $data_vencimento->addDays(2);
            }
            if($data_vencimento->dayOfWeekIso == 7){ 
                $data_vencimento->addDays(1);
            }
            $retorno[] = [
                'estabelecimento' => $titulo->codigo,
                'aberto' => ($data_vencimento->gte($data)) ? $titulo->saldotitulo : 0,
                'vencido' => ($data_vencimento->lt($data)) ? $titulo->saldotitulo : 0,
                'total' => $titulo->saldotitulo,
            ];
        }
        unset($TitulosAberto);

        $saida = [];
        $total = [
            'aberto' => 0,
            'vencido' => 0,
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
            ])
        ];

        foreach($retorno as $resp){
            if(!isset($saida[$resp['estabelecimento']])){
                $saida[$resp['estabelecimento']] = [
                    'estabelecimento' => $estabelecimentos[$resp['estabelecimento']],
                    'aberto' => 0,
                    'vencido' => 0,
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
                    ]),
                ];
            }
            $saida[$resp['estabelecimento']]['aberto'] += $resp['aberto'];
            $saida[$resp['estabelecimento']]['vencido'] += $resp['vencido'];
            $saida[$resp['estabelecimento']]['total'] += $resp['total'];
            $total['aberto'] += $resp['aberto'];
            $total['vencido'] += $resp['vencido'];
            $total['total'] += $resp['total'];
        }

        unset($retorno);
        foreach($saida as $key => $row){
            $saida[$key]['aberto'] = ($row['aberto'] > 0) ? parserValor($row['aberto']) : '';
            $saida[$key]['vencido'] = ($row['vencido'] > 0) ? parserValor($row['vencido']) : '';
            $saida[$key]['total'] = ($row['total'] > 0) ? parserValor($row['total']) : '';
        }

        $total['aberto'] = ($total['aberto'] > 0) ? parserValor($total['aberto']) : '';
        $total['vencido'] = ($total['vencido'] > 0) ? parserValor($total['vencido']) : '';
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
        
        $titulosEmAbertoNasajonObj = new TituloExcluido;

        $queryAbertos = 'with titulos as(
            select "codigo", "saldotitulo", "nota_id", "numero", "nota_numero", "parcela", "titulo_emissao", "vencimento", "tem_prorrogacao", "valor", "multa", "nome_cliente", "cnpj", "juros", "percentualjurosdiario", "datainiciomulta", "desconto", "nossonumero", "enviado_para_banco", "banco_codigo", "observacao", "vencimento_original"
            from ' . $titulosEmAbertoNasajonObj->getTable() . ' b
            where "saldotitulo" > 0 ';

        $hoje = Carbon::now()->format('Y-m-d');
        
        if(!empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
        }
        
        if(!empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
        }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\')';

        $pedidosPreObj = !empty($fields['titulos_pre']) ? $pedidosPreQuery->get() : collect();
        $chequesPreObj = !empty($fields['cheques_pre']) ? $chequesPreQuery->get() : collect();

        $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use($fields){
            return !is_null($pedido->pedidoNasajon) && !is_null($pedido->pedidoNasajon->nota);
        });

        if($filter['total'] === 'false'){
            $queryAbertos .= ' AND codigo = \'' . $fields['estabelecimento'] . '\'';
        }
        
        $data = Carbon::now();
        if($filter['abertura'] == 'aberto'){    
            $queryAbertos .= ' AND (
                CASE
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END >= \''. $data->format('Y-m-d') .'\')';

        }else if($filter['abertura'] == 'vencido'){
            $queryAbertos .= ' AND (
                CASE
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END < \''. $data->format('Y-m-d') .'\')';

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

        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
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
                    $queryAbertos .= ' AND (vendedor_codigo not in(\'' . implode('\',\'', $representantes) . '\') or vendedor_codigo is null)';
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
                    $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
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
               $queryAbertos .= ' AND (vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
    
                if(in_array('001', $representantes)){
                    $queryAbertos = ' or vendedor_codigo is null';
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
                $queryAbertos .= ' AND cod_cliente in (\'' . implode('\',\'', $clientecodigo) . '\')';

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

        $TitulosAberto = collect(DB::connection('pgsql')->select($queryAbertos));
        $estabelecimentos = returnTodasEmpresasView();
        $cnpjCliente = [];
        $cnpjCliente[] = ['cnpj' =>  'cnpj'];
        $cnpjCliente[] = ['result' =>  '2'];
        $retorno = [];

        foreach($TitulosAberto as $titulo){
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
        }

        $valor = 0;
        $saldo = 0;
        $juros = 0;

        $valor += $TitulosAberto->sum('valor');
        $saldo += $TitulosAberto->sum('saldotitulo');
        $juros += $TitulosAberto->sum('juros');


        unset($TitulosAberto);

        $total = [
            'valor' => ($valor > 0) ? parserValor($valor) : '',
            'saldo' => ($saldo > 0) ? parserValor($saldo) : '',
            'juros' => ($juros > 0) ? parserValor($juros) : '',
        ];
        
        return view('programs.titulo_excluido.modal.titulos')->with(["dados"=> $retorno,"cod_cliente" => $cnpjCliente, "totalizadores" => $total]);
    }

    public function modalCLiente(Request $request){
        ini_set('memory_limit', '512M');
        set_time_limit(300);
        $filter = $request->only(['filters','abertura','total','aberturageral']);
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
        
        $titulosEmAbertoNasajonObj = new TituloExcluido;

        $queryAbertos = 'with titulos as(
            select codigo, vencimento, cnpj, nome_cliente, cod_cliente, saldotitulo as saldo, multa, nota_id, numero
            from ' . $titulosEmAbertoNasajonObj->getTable() . ' b
            where "saldotitulo" > 0 ';

        $hoje = Carbon::now()->format('Y-m-d');
        
        if(!empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
        }
        
        if(!empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
        }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\')';

        if($filter['total'] !== 'true'){
            $queryAbertos .= ' AND codigo = \'' . $fields['estabelecimento'] . '\'';
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
            }
            else{
                $gerente_representante = User::with('subordinados')->find($gerente);
                $representantes = $gerente_representante->subordinados->pluck('codigo_representante')->filter()->toArray();

                if(!empty($gerente_representante->codigo_representante)){
                    $representantes[] = $gerente_representante->codigo_representante;
                }
                
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                   $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
                }
            }

        }
        else if(isset($representante) && $representante != '998'){
            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
            
            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                $queryAbertos .= ' AND (vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
            
                if(in_array('001', $representantes)){
                    $queryAbertos .= ' or vendedor_codigo is null';
                }

                $queryAbertos .= ')';

            }
        }
        else{
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


            }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
                strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
                $userid = Auth::user()->id;
                $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
    
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                    $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
                }
            }
        }

        if(!empty($fields['cliente_nome']) ){
            $cliente = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'),'ilike',trim($fields['cliente_nome']))->first();
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

            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
        }

        $estabelecimentos = returnTodasEmpresasView();

        $queryAbertos .= ')
            select *
                from titulos t';

        $TitulosAberto = collect(DB::connection('pgsql')->select($queryAbertos));
        $retorno = [];
        $total = [
            'aberto' => 0,
            'vencido' => 0,
            'total' => 0,
        ];

        foreach($TitulosAberto as $titulo){
            if(!empty($titulo->saldo)){
                $data_vencimento = Carbon::createFromFormat('Y-m-d',$titulo->vencimento);
                $data = Carbon::now();    
                if($data_vencimento->dayOfWeekIso == 6){ 
                    $data_vencimento->addDays(2);
                }
                if($data_vencimento->dayOfWeekIso == 7){ 
                    $data_vencimento->addDays(1);
                }
                if(!isset($retorno[$titulo->cod_cliente])){
                    $retorno[$titulo->cod_cliente] = [
                        'cliente' => $titulo->nome_cliente.' - '.$titulo->cnpj,
                        'estabelecimento' => $estabelecimentos[$titulo->codigo],
                        'aberto' => 0,
                        'vencido' => 0,
                        'total' => 0,
                        'filter' => encrypt([
                            'estabelecimento' => $titulo->codigo,
                            'gerentes' => (isset($fields['gerentes'])) ? $fields['gerentes'] : null,
                            'vendedor_representante' => (isset($fields['vendedor_representante'])) ? $fields['vendedor_representante'] : null,
                            'cliente_nome' => $fields['cliente_nome'],
                            'cod_cliente' => $titulo->cod_cliente,
                            'data_inicio' => $fields['data_inicio'],
                            'data_fim' => $fields['data_fim'],
                            'titulos' =>  (isset($fields['titulos'])) ? true : null,
                            'cheque' =>  (isset($fields['cheque'])) ? true : null,
                            'cheques_pre' =>  (isset($fields['cheques_pre'])) ? true : null,
                            'titulos_pre' =>  (isset($fields['titulos_pre'])) ? true : null,
                        ]),
                    ];
                }
                $retorno[$titulo->cod_cliente]['aberto'] += ($data_vencimento->gte($data)) ? $titulo->saldo : 0;
                $retorno[$titulo->cod_cliente]['vencido'] += ($data_vencimento->lt($data)) ? $titulo->saldo : 0;
                $retorno[$titulo->cod_cliente]['total'] += $titulo->saldo;
                $total['aberto'] += ($data_vencimento->gte($data)) ? $titulo->saldo : 0;
                $total['vencido'] += ($data_vencimento->lt($data)) ? $titulo->saldo : 0;
                $total['total'] += $titulo->saldo;
            }
        }    
        unset($TitulosAberto);

        foreach($retorno as $key => $row){
            $retorno[$key]['aberto'] = ($row['aberto'] > 0) ? parserValor($row['aberto']) : '';
            $retorno[$key]['vencido'] = ($row['vencido'] > 0) ? parserValor($row['vencido']) : '';
            $retorno[$key]['total'] = ($row['total'] > 0) ? parserValor($row['total']) : '';
        }

        $total['aberto'] = ($total['aberto'] > 0) ? parserValor($total['aberto']) : '';
        $total['vencido'] = ($total['vencido'] > 0) ? parserValor($total['vencido']) : '';
        $total['total'] = ($total['total'] > 0) ? parserValor($total['total']) : '';
        
        return view("programs.titulo_excluido.modal.cliente")->with(["total" => $total, "retorno" => $retorno, "aberturageral" => $filter['aberturageral']]);
    }

    public function modalRepresentante(Request $request){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $filter = $request->only(['filters','clientes','abertura','total','representante']);
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
        $saidatotal = ($filter['representante'] === 'false') ? 'true' : 'false';

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
                
        $titulosEmAbertoNasajonObj = new TituloExcluido;

        $queryAbertos = 'with titulos as(
            select "codigo", "saldotitulo", "nota_id", "numero", "parcela", "titulo_emissao", "vencimento", "tem_prorrogacao", "valor", "multa", "nome_cliente", "cnpj", "juros", "datainiciomulta", "desconto", "nossonumero", "enviado_para_banco", "banco_codigo", "observacao", vendedor_codigo as vendedor_codigo
            from ' . $titulosEmAbertoNasajonObj->getTable() . ' b 
            where "saldotitulo" > 0 ';

        $hoje = Carbon::now()->format('Y-m-d');


        if(!empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
        }
        
        if(!empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
        }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\')';


        if($filter['total'] !== 'true'){
            $queryAbertos .= ' AND codigo = \'' . $fields['estabelecimento'] . '\'';
        }

        $data = Carbon::now();
        if($filter['abertura'] == 'aberto'){    
            $queryAbertos .= ' AND (
                CASE
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END >= \''. $data->format('Y-m-d') .'\')';
            
        }else if($filter['abertura'] == 'vencido'){
            $queryAbertos .= ' AND (
                CASE
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END < \''. $data->format('Y-m-d') .'\')';
        }

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
        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
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
                    $queryAbertos .= ' AND (vendedor_codigo not in(\'' . implode('\',\'', $representantes) . '\') or vendedor_codigo is null)';
                }
            }
            else{
                $gerente_representante = User::with('subordinados')->find($gerente);
                $representantes = $gerente_representante->subordinados->pluck('codigo_representante')->filter()->toArray();

                if(!empty($gerente_representante->codigo_representante)){
                    $representantes[] = $gerente_representante->codigo_representante;
                }
            
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                    $queryAbertos .= ' AND vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
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
                $queryAbertos .= ' AND (vendedor_codigo = ANY(ARRAY[ (\'' . implode('\',\'', $representantes) . '\')])';

                if(in_array('001', $representantes)){
                    $queryAbertos .= 'or vendedor_codigo is null';
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
                $queryAbertos .= ' AND cod_cliente in (\'' . implode('\',\'', $clientecodigo) . '\')';

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

        $TitulosAberto = collect(DB::connection('pgsql')->select($queryAbertos));
        $retorno = [];
        $total = [
            'aberto' => 0,
            'vencido' => 0,
            'total' => 0,
        ];

        $estabelecimentos = returnTodasEmpresasView();
        $cod = User::where('codigo_representante','001')->select('name','codigo_representante')->first();
        $cod001 = $cod['codigo_representante'].' - '.$cod['name'];

        $usuarios = User::whereIn('codigo_representante', $TitulosAberto->pluck('vendedor_codigo'))->get();

        foreach($TitulosAberto as $titulo){
            $data_vencimento = Carbon::createFromFormat('Y-m-d',$titulo->vencimento);
            $data = Carbon::now();    
            if($data_vencimento->dayOfWeekIso == 6){
                $data_vencimento->addDays(2);
            }
            if($data_vencimento->dayOfWeekIso == 7){
                $data_vencimento->addDays(1);
            }

            $usuario = $usuarios->firstWhere('codigo_representante', $titulo->vendedor_codigo);
            $usuariorepresentante = '';
            $representante = (!empty($usuario) && $usuario->codigo_representante != '001') ? $usuariorepresentante = $usuario->name : $cod001;
            $vendedor = (!empty($usuariorepresentante)) ? $usuario->codigo_representante : $cod['codigo_representante'];
            $representante = (!empty($usuariorepresentante)) ? $usuario->codigo_representante.' - '.$usuario->name : $cod001;
            
            if(!isset($retorno[$representante])){
                $retorno[$representante] = [
                    'representante' => $representante,
                    'estabelecimento' => $estabelecimentos[$titulo->codigo],
                    'aberto' => 0,
                    'vencido' => 0,
                    'total' => 0,
                    'titulo_id' => '',
                    'filter' => encrypt([
                        'estabelecimento' => $titulo->codigo,
                        'gerentes' => (isset($fields['gerentes'])) ? $fields['gerentes'] : null,
                        'vendedor_representante' => (isset($fields['vendedor_representante'])) ? $fields['vendedor_representante'] : null,
                        'cliente_nome' => $fields['cliente_nome'],
                        'cod_vendedor' => $vendedor,
                        'data_inicio' => $fields['data_inicio'],
                        'data_fim' => $fields['data_fim'],
                        'titulos' =>  (isset($fields['titulos'])) ? true : null,
                        'cheque' =>  (isset($fields['cheque'])) ? true : null,
                        'titulos_pre' =>  (isset($fields['titulos_pre'])) ? true : null,
                        'cheques_pre' =>  (isset($fields['cheques_pre'])) ? true : null,
                    ]),
                ];
            }
            $retorno[$representante]['aberto'] += ($data_vencimento->gte($data)) ? $titulo->saldotitulo : 0;
            $retorno[$representante]['vencido'] += ($data_vencimento->lt($data)) ? $titulo->saldotitulo : 0;
            $retorno[$representante]['total'] += $titulo->saldotitulo;
            $total['aberto'] += ($data_vencimento->gte($data)) ? $titulo->saldotitulo : 0;
            $total['vencido'] += ($data_vencimento->lt($data)) ? $titulo->saldotitulo : 0;
            $total['total'] += $titulo->saldotitulo;
        }
        unset($TitulosAberto);


        foreach($retorno as $key => $resp){
            $retorno[$key]['aberto'] = ($resp['aberto'] > 0) ? parserValor($resp['aberto']) : '';
            $retorno[$key]['vencido'] = ($resp['vencido'] > 0) ? parserValor($resp['vencido']) : '';
            $retorno[$key]['total'] = ($resp['total'] > 0) ? parserValor($resp['total']) : '';
        }
        $total['aberto'] = ($total['aberto'] > 0) ? parserValor($total['aberto']) : '';
        $total['vencido'] = ($total['vencido'] > 0) ? parserValor($total['vencido']) : '';
        $total['total'] = ($total['total'] > 0) ? parserValor($total['total']) : '';

        $total['filter'] = encrypt([
            'estabelecimento' => $fields['estabelecimento'],
            'gerentes' => (isset($fields['gerentes'])) ? $fields['gerentes'] : null,
            'vendedor_representante' => (isset($fields['vendedor_representante'])) ? $fields['vendedor_representante'] : null,
            'cliente_nome' => $fields['cliente_nome'],
            'data_inicio' => $fields['data_inicio'],
            'data_fim' => $fields['data_fim'],
            'titulos' => (isset($fields['titulos']))? true : null,
            'cheque' =>  (isset($fields['cheque'])) ? true : null,
            'titulos_pre' =>  (isset($fields['titulos_pre'])) ? true : null,
            'cheques_pre' =>  (isset($fields['cheques_pre'])) ? true : null,
        ]);

        return view("programs.titulo_excluido.modal.representante")->with(["total" => $total, "retorno" => $retorno, 'saidatotal' => $saidatotal, 'filter' => $filter]);
    }

    public function modalTitulos(Request $request){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $filter = $request->only(['filters','abertura','total','cliente','busca','aberturageral']);
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
                
        $titulosEmAbertoNasajonObj = new TituloExcluido;

        $queryAbertos = 'with titulos as(
            select "codigo", "saldotitulo", "nota_id", "numero", "nota_numero", "parcela", "titulo_emissao", "vencimento", "tem_prorrogacao", "valor", "multa", "nome_cliente", "cnpj", "juros", "percentualjurosdiario", "datainiciomulta", "desconto", "nossonumero", "enviado_para_banco", "banco_codigo", "observacao", "vencimento_original"
            from ' . $titulosEmAbertoNasajonObj->getTable() . ' b
            where "saldotitulo" > 0 ';
    
        $hoje = Carbon::now()->format('Y-m-d');    

        if(!empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
        }
        
        if(!empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
        }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\')';

        if($filter['cliente'] === 'true' && $filter['aberturageral'] === 'false'){
            $queryAbertos .= ' AND codigo = \'' . $fields['estabelecimento'] . '\'';  
        }

        $data = Carbon::now();
        if($filter['abertura'] == 'aberto'){    
            $queryAbertos .= ' AND (
                CASE  
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END >= \''. $data->format('Y-m-d') .'\')';

        }else if($filter['abertura'] == 'vencido'){
            $queryAbertos .= ' AND (
                CASE
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END < \''. $data->format('Y-m-d') .'\')';
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
            }
            else{
                $gerente_representante = User::with('subordinados')->find($gerente);
                $representantes = $gerente_representante->subordinados->pluck('codigo_representante')->filter()->toArray();

                if(!empty($gerente_representante->codigo_representante)){
                    $representantes[] = $gerente_representante->codigo_representante;
                }

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                    $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
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

        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();


            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
            }

        }


        if(!empty($fields['cliente_nome']) ){
            $cliente = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'),'ilike',trim($fields['cliente_nome']))->first();
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

            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
        }

        if(!empty($filter['busca'])){
            if((!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998') || (isset($representante) && $representante == '998')){
                $queryAbertos .= ' AND CONCAT(TRIM(nome_cliente), \' - \', cliente_cnpj) ilike \'%'.$filter['busca'].'%\'';
            }else{
                $queryAbertos .= ' AND CONCAT(TRIM(nome_cliente), \' - \', cnpj) ilike \'%'.$filter['busca'].'%\'';
            }
        }

        $queryAbertos .= ')
            select *
                from titulos t';

        $TitulosAberto = collect(DB::connection('pgsql')->select($queryAbertos));
        $estabelecimentos = returnTodasEmpresasView();
        $cnpjCliente = [];
        $retorno = [];

        foreach($TitulosAberto as $titulo){
            if($filter['total'] === 'true'){
                if(isset($titulo->cnpj)){
                    $cnpjCliente[] = [
                        'cnpj' =>  $titulo->cnpj,
                    ];
                }
            }
            $linha = [];

            $linha['estabelecimento'] = $estabelecimentos[$titulo->codigo];
            $linha['nota_numero'] = (!empty($titulo->nota_numero)) ? $titulo->nota_numero : '';
            $linha['parcela'] = $titulo->parcela;
            $linha['data_emissao'] = $titulo->titulo_emissao;
            $linha['data_vencimento'] = parserData($titulo->vencimento);
            $linha['data_vencimento_sql'] = $titulo->vencimento;
            if($titulo->tem_prorrogacao === true ){
                $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($titulo->vencimento_original).'"> *</a>';
            }
            $linha['valor_original'] = $titulo->valor;
            $linha['valor'] = $titulo->saldotitulo;
            $linha['multa'] = $titulo->multa;
            $linha['nome_cliente'] = $titulo->nome_cliente. ' - ' .$titulo->cnpj;
            $linha['numero'] = $titulo->numero;
            $linha['cheque'] = '';
            $linha['juros_cobrados'] = $titulo->juros;
            $linha['percentual_juros_diarios'] = $titulo->percentualjurosdiario;
            $linha['data_juros'] = $titulo->datainiciomulta;
            $linha['desconto'] = $titulo->desconto;
            $linha['POSICAO_CR'] = $titulo->nossonumero;
            $linha['POSICAO_CR_DESCRICAO'] = '';
            $linha['banco'] = ($titulo->enviado_para_banco == true) ? $titulo->banco_codigo : 'CARTEIRA';
            $linha['observacao'] = $titulo->observacao;

            $retorno[] = $linha;

        }

        $valor = 0;
        $saldo = 0;
        $juros = 0;
        
        $valor += $TitulosAberto->sum('valor');
        $saldo += $TitulosAberto->sum('saldotitulo');
        $juros += $TitulosAberto->sum('juros');

        $total = [
            'valor' => ($valor > 0) ? parserValor($valor) : '',
            'saldo' => ($saldo > 0) ? parserValor($saldo) : '',
            'juros' => ($juros > 0) ? parserValor($juros) : '',
        ];

        return view('programs.titulo_excluido.modal.titulos')->with(["dados"=> $retorno, "cod_cliente" => $cnpjCliente, "totalizadores" => $total]);
    }

    public function modalTitulosRepresentante(Request $request){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $filter = $request->only(['filters','abertura','total','representante','codigo_representante']);
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

        $titulosEmAbertoNasajonObj = new TituloExcluido;

        $queryAbertos = 'with titulos as(
            select "codigo", "saldotitulo", "nota_id", "nota_numero", "numero", "parcela", "titulo_emissao", "vencimento", "tem_prorrogacao", "valor", "multa", "nome_cliente", "cnpj", "juros", "percentualjurosdiario", "datainiciomulta", "desconto", "nossonumero", "enviado_para_banco", "banco_codigo", "observacao", "vencimento_original"
            from ' . $titulosEmAbertoNasajonObj->getTable() . ' b
            where "saldotitulo" > 0 ';

        $hoje = Carbon::now()->format('Y-m-d');

        if(!empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
        }

        if(!empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
        }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\')';

        if(in_array($filter['abertura'], ['aberto', 'total'])){    
            $chequesPreObj = isset($fields['cheques_pre']) ? $chequesPreQuery->get() : collect();
        }
        else{
            $chequesPreObj = collect();
        }

        if(in_array($filter['abertura'], ['vencido', 'total'])){
            $pedidosPreObj = isset($fields['titulos_pre']) ? $pedidosPreQuery->get() : collect();
            $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use($fields){
                $valor_vencido = $pedido->valor - $pedido->lancamentos->sum("valor_pago");
                return $valor_vencido > 0 && !is_null($pedido->pedidoNasajon) && !is_null($pedido->pedidoNasajon->nota);
            });
        }
        else{
            $pedidosPreObj = collect();
        }

        $data = Carbon::now();
        if($filter['abertura'] == 'aberto'){    
            $queryAbertos .= ' AND (
                CASE
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END >= \''. $data->format('Y-m-d') .'\')';
        }else if($filter['abertura'] == 'vencido'){
            $queryAbertos .= ' AND (
                CASE 
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END < \''. $data->format('Y-m-d') .'\')';
        }

        $pedidosPreObj = $pedidosPreObj->filter(function($pedido) use($fields){
            $valor_vencido = $pedido->valor - $pedido->lancamentos->sum("valor_pago");
            return $valor_vencido > 0;
        });

        if($filter['total'] === 'false'){
            $queryAbertos .= ' AND codigo = \'' . $fields['estabelecimento'] . '\'';
        }

        if($filter['representante'] == 'true'){
            $representantes = $fields['cod_vendedor'];

            if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                $queryAbertos .= ' AND (vendedor_codigo = \'' . $representantes . '\'';

                if ($representantes == '001'){
                    $queryAbertos .= ' or vendedor_codigo is null';
                }
                
                $queryAbertos .= ')';

            }                    
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

            }
            else{
                $gerente_representante = User::with('subordinados')->find($gerente);
                $representantes = $gerente_representante->subordinados->pluck('codigo_representante')->filter()->toArray();

                if(!empty($gerente_representante->codigo_representante)){
                    $representantes[] = $gerente_representante->codigo_representante;
                }

                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                    $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
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
                $queryAbertos .= ' AND (vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
 
                if(in_array('001', $representantes)){
                    $queryAbertos .= ' or vendedor_codigo is null)';
                }
    
                $queryAbertos .= ')';
            }
        }
        else{
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
            }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
                strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
                $userid = Auth::user()->id;
                $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
    
                if((empty(Auth::user()->codigo_representante) || Auth::user()->codigo_representante != '998') && (!isset($representante) || $representante != '998')){
                    $queryAbertos .= ' AND vendedor_codigo = ANY(ARRAY[\'' . implode('\',\'', $representantes) . '\'])';
                }
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
                $queryAbertos .= ' AND cod_cliente in (\'' . implode('\',\'', $clientecodigo) . '\')';

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

        $TitulosAberto = collect(DB::connection('pgsql')->select($queryAbertos));
        $estabelecimentos = returnTodasEmpresasView();
        $cnpjCliente[] = ['cnpj' =>  'cnpj'];
        $cnpjCliente[] = ['result' =>  '2'];
        $retorno = [];

        foreach($TitulosAberto as $titulo){
            if(isset($fields['cod_vendedor']) && $fields['cod_vendedor'] == '001'){
                $usuariorepresentante = '';
                $representante = (isset($titulo->notaDetalhes->revisao_vendedor_comissao->usuario)) ? $usuariorepresentante = $titulo->notaDetalhes->revisao_vendedor_comissao->usuario->name : null;
                $vendedor = (empty($usuariorepresentante) || $titulo->notaDetalhes->revisao_vendedor_comissao->vendedor_codigo == '001') ? true : false;
                
                $linha = [];

                if($vendedor == true){

                    $linha['estabelecimento'] = $estabelecimentos[$titulo->codigo];
                    $linha['nota_numero'] = (!empty($titulo->numero)) ? $titulo->numero : '';
                    $linha['numero'] = (empty($titulo->numero)) ? $titulo->nota : $titulo->numero;
                    $linha['cheque'] = '';
                    $linha['parcela'] = $titulo->parcela;
                    $linha['data_emissao'] = $titulo->titulo_emissao;
                    $linha['data_vencimento_sql'] = $titulo->vencimento;
                    $linha['data_vencimento'] = parserData($titulo->vencimento);
                    if($titulo->tem_prorrogacao === true ){
                        $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($titulo->vencimento_original).'"> *</a>';
                    }
                    $linha['valor_original'] = $titulo->valor;
                    $linha['valor'] = $titulo->saldotitulo;
                    $linha['multa'] = $titulo->multa;
                    $linha['nome_cliente'] = $titulo->nome_cliente. ' - ' .$titulo->cnpj;
                    $linha['numero'] = $titulo->numero;
                    $linha['juros_cobrados'] = $titulo->juros;
                    $linha['percentual_juros_diarios'] = $titulo->percentualjurosdiario;
                    $linha['data_juros'] = $titulo->datainiciomulta;
                    $linha['desconto'] = $titulo->desconto;
                    $linha['POSICAO_CR'] = '';
                    $linha['POSICAO_CR_DESCRICAO'] = $titulo->nossonumero;
                    $linha['banco'] = ($titulo->enviado_para_banco == true) ? $titulo->banco_codigo : 'CARTEIRA';
                    $linha['observacao'] = $titulo->observacao;
                }
            }
            else{

                $linha['estabelecimento'] = $estabelecimentos[$titulo->codigo];
                $linha['nota_numero'] = (!empty($titulo->numero)) ? $titulo->numero : '';
                $linha['parcela'] = $titulo->parcela;
                $linha['data_emissao'] = $titulo->titulo_emissao;
                $linha['data_vencimento_sql'] = $titulo->vencimento;
                $linha['data_vencimento'] = parserData($titulo->vencimento);
                if($titulo->tem_prorrogacao === true ){
                    $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($titulo->vencimento_original).'"> *</a>';
                }
                $linha['valor_original'] = $titulo->valor;
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
                
            }

            $retorno[] =  $linha;
        }    

        $valor = 0;
        $saldo = 0;
        $juros = 0;
        
        $valor += $TitulosAberto->sum('valor');
        $saldo += $TitulosAberto->sum('saldotitulo');
        $juros += $TitulosAberto->sum('juros');

        $total = [
            'valor' => ($valor > 0) ? parserValor($valor) : '',
            'saldo' => ($saldo > 0) ? parserValor($saldo) : '',
            'juros' => ($juros > 0) ? parserValor($juros) : '',
        ];
        
        return view('programs.titulo_excluido.modal.titulos')->with(["dados"=> $retorno,"cod_cliente" => $cnpjCliente, "totalizadores" => $total]);
    }
}
