<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

use Auth;
use App\User;

use App\PedidoVenda;
use App\PedidoPortal;
use App\PedidosVendaNasajon;
use App\Cliente;
use App\ClienteNasajon;

use Carbon\Carbon;

class CarteiraPedidosController extends Controller
{
    private $pedidos_nasajon_status_exibidos = ['Em Faturamento', 'Em separação', 'Aberto'];

    private $status_lista = [
        'Aberto'         => 'Aberto',
        'Em separação'   => 'Em Separação',
        'Em Faturamento' => 'Em Faturamento'
    ];

    private $tipo_pedido = [
        'pronta_entrega' => 'Pronta-entrega',
        'pedido_futuro' => 'Pedido programado'
    ];

    public function index(Request $request){

        if(Auth::user()->hasPermissionTo("programas App\CarteiraPedidos") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\CarteiraPedidos');
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
            $userObj = User::whereIn('id', $subordinadosObj)->orderBy('name')->get();
            foreach ($userObj as $key => $user) {
                if(strtolower($user->tipo_usuario->nome) === "gerente comercial"){
                    $gerentes[Crypt::encrypt($user->id)] = strtoupper($user->name);
                }else if(strtolower($user->tipo_usuario->nome) === "vendedor interno" || strtolower($user->tipo_usuario->nome) === "representante"){
                    $vendedor_representante[Crypt::encrypt($user->id)] = strtoupper($user->name);
                }
            }
            unset($subordinadosObj);
        } else {
            $subordinadosObj = User::with(["tipo_usuario"])->orderBy('name')->get();
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
        
        $status_pedido = $this->status_lista;

        $tipo_pedido = $this->tipo_pedido;
        
        $variaveis_view = [
            'status_pedido'                 => $status_pedido,
            'gerentes'                      => $gerentes,
            'check_gerentes'                => $check_gerentes,
            'supervisores'                  => $supervisores,
            'check_supervisores'            => $check_supervisores,
            'vendedor_representante'        => $vendedor_representante,
            'check_vendedor_representante'  => $check_vendedor_representante,
            'tipo_pedido'                   => $tipo_pedido
        ];

        return view('programs.carteira_pedidos.index', $variaveis_view);
    }

    public function filter(Request $request){
        set_time_limit(300);

        $fields          = $request->only('gerentes', 'vendedor_representante', 'intercompany', 'status_pedido', 'pedido_programado', 'deposito_bancario','vendas');
        $statusNasaJon   = $this->pedidos_nasajon_status_exibidos;
        $statusPortal    = [];
        $formaPagamento  = null;
        $usersObj        = null;

        if ( !empty( $fields['deposito_bancario'] ) ){
            $formaPagamento = 'Usar Crédito';
        }

        if ( !empty($fields['status_pedido']) ) {
            $statusNasaJon = [$fields['status_pedido']];
        }

        if ( isset($fields['pedido_programado']) && !empty($fields['pedido_programado']) ){
            if($fields['pedido_programado'] == 'pedido_futuro'){
                $statusNasaJon = [null];
                $statusPortal = [8];
            }
        }
        else{
            $statusPortal = [8];
        }

        if(in_array(Auth::user()->tipo_usuario->nome, ['Vendedor Interno', 'Representante'])){
            $usersObj = collect([Auth::user()]);
        }
        else{
            if(Auth::user()->tipo_usuario->nome == 'Gerente Comercial'){
                $subordinados = UserController::varreSubordinados(Auth::user()->id);
                $usersObj = User::whereIn('id', $subordinados)->get();
            }

            if (
                isset($fields['gerentes']) && 
                !is_null($fields['gerentes']) &&
                (!isset($fields['vendedor_representante']) ||
                empty($fields['vendedor_representante']) )
            ) {
                $gerente = Crypt::decrypt($fields['gerentes']);

                $subordinados = UserController::varreSubordinados($gerente);
                $usersObj = User::whereIn('id', $subordinados)->get();
            }
            else if (
                (isset($fields['vendedor_representante']) &&
                !empty($fields['vendedor_representante']) )
            ) {
                $usuario = Crypt::decrypt($fields['vendedor_representante']);
                $usersObj = collect([User::find($usuario)]);
            }
        }

        $atraso = Carbon::Now()->format('Y-m-01');
        $mes = [Carbon::Now()->format('Y-m-01'), Carbon::Now()->format('Y-m-t')];
        $mesFuturo = [Carbon::Now()->addMonthNoOverflow()->format('Y-m-01'), Carbon::Now()->addMonthNoOverflow()->format('Y-m-t')];
        $futuro = Carbon::Now()->addMonthNoOverflow()->format('Y-m-t');

        if(empty($usersObj)){
            return $this->filterCarteira($fields);
        }

        $atrasosNasajonQuery = PedidosVendaNasajon::selectRaw('estabelecimento_codigo, id, count(id) as pedidos, sum(valor) as valor_total')
            ->whereIn('situacao_descricao', $statusNasaJon)    
            ->where('emissao', '<', $atraso)
            ->whereRaw('rascunho = false')
            ->where(function($query) use ($fields){
                $query->orWhere('grupodeoperacao', 'VENDA');
                $query->orWhere(['grupodeoperacao' => NULL]);
            })
            ->where(function($query) use ($fields){
                if(isset($fields['vendas']) && $fields['vendas'] == true){
                    $query->orWhereIn('grupodeoperacao_pedido', ['VENDA']);
                }else{
                    $query->orWhereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA']);
                }
            })
            ->with('forma_pagamento','forma_pagamento.condicao','itens_pedido')
            ->whereHas('forma_pagamento', function ($query) use ($formaPagamento){
                if( empty( $formaPagamento ) ){
                    $query
                    ->where('parcelamento_nome','NOT ILIKE' , 'Usar Credito')
                    ->where('parcelamento_codigo','!=' , '608')
                    ->where('formapagamento_descricao','NOT ILIKE','Cartão Crédito');
                }
            })
            ->groupBy('estabelecimento_codigo','id');

        if(isset($fields['vendas']) && $fields['vendas'] == true){
            $atrasosNasajonQuery->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO','PEDIDOISENTO','PEDIDOISENTOTORO','PEDIDOPUBLICOSP','PEDIDOPUBLICOTORO','PEDTRIANGULAR']);
        }else{
            $atrasosNasajonQuery->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDBONIFICACAO', 'PEDBONIFICACAOTORO', 'PEDCONSERTO', 'PEDIDODEVCOMPRA', 'PEDIDODEVCOMPRATORO', 'PEDIDOFUTTORO', 'PEDIDOFUTUROSP', 'PEDIDOISENTO', 'PEDIDOISENTOTORO', 'PEDIDOPCP', 'PEDIDOPUBLICOSP', 'PEDIDOPUBLICOTORO', 'PEDIDOTRANSF', 'PEDIDOTRANSFSEMICMS', 'PEDIDOTRANSFTEXTIL', 'PEDIDOTRANSFTOROSP', 'PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDINDUSTRIA', 'PEDTRANSFCOMREMESSA', 'PEDTRIANGULAR', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO']);
        }


        $mesNasajonQuery = PedidosVendaNasajon::selectRaw('estabelecimento_codigo, id, count(id) as pedidos, sum(valor) as valor_total')
            ->whereIn('situacao_descricao', $statusNasaJon)   
            ->whereBetween('emissao', $mes)
            ->whereRaw('rascunho = false')
            ->where(function($query) use ($fields){
                $query->orWhere('grupodeoperacao', 'VENDA');
                $query->orWhere(['grupodeoperacao' => NULL]);
            })
            ->where(function($query) use ($fields){
                if(isset($fields['vendas']) && $fields['vendas'] == true){
                    $query->orWhereIn('grupodeoperacao_pedido', ['VENDA']);
                }else{
                    $query->orWhereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA']);
                }
            })
            ->with('forma_pagamento','forma_pagamento.condicao','itens_pedido')
            ->whereHas('forma_pagamento', function ($query) use ($formaPagamento){
                if( empty( $formaPagamento ) ){
                    $query
                    ->where('parcelamento_nome','NOT ILIKE' , 'Usar Credito')
                    ->where('parcelamento_codigo','!=' , '608')
                    ->where('formapagamento_descricao','NOT ILIKE','Cartão Crédito');
                }
            })
            ->groupBy('estabelecimento_codigo','id');

        if(isset($fields['vendas']) && $fields['vendas'] == true){
            $mesNasajonQuery->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO','PEDIDOISENTO','PEDIDOISENTOTORO','PEDIDOPUBLICOSP','PEDIDOPUBLICOTORO','PEDTRIANGULAR']);
        }else{
            $mesNasajonQuery->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDBONIFICACAO', 'PEDBONIFICACAOTORO', 'PEDCONSERTO', 'PEDIDODEVCOMPRA', 'PEDIDODEVCOMPRATORO', 'PEDIDOFUTTORO', 'PEDIDOFUTUROSP', 'PEDIDOISENTO', 'PEDIDOISENTOTORO', 'PEDIDOPCP', 'PEDIDOPUBLICOSP', 'PEDIDOPUBLICOTORO', 'PEDIDOTRANSF', 'PEDIDOTRANSFSEMICMS', 'PEDIDOTRANSFTEXTIL', 'PEDIDOTRANSFTOROSP', 'PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDINDUSTRIA', 'PEDTRANSFCOMREMESSA', 'PEDTRIANGULAR', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO']);
        }

        $mesFuturoNasajonQuery = PedidosVendaNasajon::selectRaw('estabelecimento_codigo, id, count(id) as pedidos, sum(valor) as valor_total')         
            ->whereBetween('emissao', $mesFuturo)
            ->whereDoesntHave('nota', function($query){
                $query->whereIn('operacao_codigo', ['REMESSAAMOSTRA','REMESSAARMAZEM','REMESSABEMATIVO','REMESSABONIFICACAO','REMESSADEMONSTRACAO','REMESSAINDENCOMENDA','REMESSAORDEM','REMESSAORDEMSEMMOV','REMESSAORDEMTORO','REMESSAORDEMTOROTERC', 'PEDAMOSTRA', 'PEDAMOSTRAGRATIS', 'PEDIDODEMONSTRACAO']);
            })
            ->whereRaw('rascunho = false')
            ->where(function($query) use ($fields){
                $query->orWhere('grupodeoperacao', 'VENDA');
                $query->orWhere(['grupodeoperacao' => NULL]);
            })
            ->where(function($query) use ($fields){
                if(isset($fields['vendas']) && $fields['vendas'] == true){
                    $query->orWhereIn('grupodeoperacao_pedido', ['VENDA']);
                }else{
                    $query->orWhereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA']);
                }
            })
            ->whereIn('situacao_descricao', $statusNasaJon)
            ->with('forma_pagamento','forma_pagamento.condicao','itens_pedido')
            ->whereHas('forma_pagamento', function ($query) use ($formaPagamento){
                if( empty( $formaPagamento ) ){
                    $query
                    ->where('parcelamento_nome','NOT ILIKE' , 'Usar Credito')
                    ->where('parcelamento_codigo','!=' , '608')
                    ->where('formapagamento_descricao','NOT ILIKE','Cartão Crédito');
                }
            })
            ->groupBy('estabelecimento_codigo','id');

        if(isset($fields['vendas']) && $fields['vendas'] == true){
            $mesFuturoNasajonQuery->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO','PEDIDOISENTO','PEDIDOISENTOTORO','PEDIDOPUBLICOSP','PEDIDOPUBLICOTORO','PEDTRIANGULAR']);
        }else{
            $mesFuturoNasajonQuery->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDBONIFICACAO', 'PEDBONIFICACAOTORO', 'PEDCONSERTO', 'PEDIDODEVCOMPRA', 'PEDIDODEVCOMPRATORO', 'PEDIDOFUTTORO', 'PEDIDOFUTUROSP', 'PEDIDOISENTO', 'PEDIDOISENTOTORO', 'PEDIDOPCP', 'PEDIDOPUBLICOSP', 'PEDIDOPUBLICOTORO', 'PEDIDOTRANSF', 'PEDIDOTRANSFSEMICMS', 'PEDIDOTRANSFTEXTIL', 'PEDIDOTRANSFTOROSP', 'PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDINDUSTRIA', 'PEDTRANSFCOMREMESSA', 'PEDTRIANGULAR', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO']);
        }

        $futuroNasajonQuery = PedidosVendaNasajon::selectRaw('estabelecimento_codigo, id, count(id) as pedidos, sum(valor) as valor_total')
            ->where('emissao', '>', $futuro)
            ->whereRaw('rascunho = false')
            ->where(function($query) use ($fields){
                $query->orWhere('grupodeoperacao', 'VENDA');
                $query->orWhere(['grupodeoperacao' => NULL]);
            })
            ->where(function($query) use ($fields){
                if(isset($fields['vendas']) && $fields['vendas'] == true){
                    $query->orWhereIn('grupodeoperacao_pedido', ['VENDA']);
                }else{
                    $query->orWhereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA']);
                }
            })
            ->whereIn('situacao_descricao', $statusNasaJon)
            ->with('forma_pagamento','forma_pagamento.condicao','itens_pedido')
            ->whereHas('forma_pagamento', function ($query) use ($formaPagamento){
                if( empty( $formaPagamento ) ){
                    $query
                    ->where('parcelamento_nome','NOT ILIKE' , 'Usar Credito')
                    ->where('parcelamento_codigo','!=' , '608')
                    ->where('formapagamento_descricao','NOT ILIKE','Cartão Crédito');
                }
            })
            ->groupBy('estabelecimento_codigo','id');

        if(isset($fields['vendas']) && $fields['vendas'] == true){
            $futuroNasajonQuery->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO','PEDIDOISENTO','PEDIDOISENTOTORO','PEDIDOPUBLICOSP','PEDIDOPUBLICOTORO','PEDTRIANGULAR']);
        }else{
            $futuroNasajonQuery->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDBONIFICACAO', 'PEDBONIFICACAOTORO', 'PEDCONSERTO', 'PEDIDODEVCOMPRA', 'PEDIDODEVCOMPRATORO', 'PEDIDOFUTTORO', 'PEDIDOFUTUROSP', 'PEDIDOISENTO', 'PEDIDOISENTOTORO', 'PEDIDOPCP', 'PEDIDOPUBLICOSP', 'PEDIDOPUBLICOTORO', 'PEDIDOTRANSF', 'PEDIDOTRANSFSEMICMS', 'PEDIDOTRANSFTEXTIL', 'PEDIDOTRANSFTOROSP', 'PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDINDUSTRIA', 'PEDTRANSFCOMREMESSA', 'PEDTRIANGULAR', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO']);
        }

        $atrasosPortalQuery = PedidoPortal::selectRaw(
            "estabelecimento,
            COUNT(id) as pedidos,
            SUM(valor_total_nota) as valor_total,
            id")
            ->where('data_previsao_entrega', '<', $atraso)
            ->whereIn('status_pedido', $statusPortal)
            ->groupBy('estabelecimento','id')
            ->with(['itens_pedido']);

        if(isset($fields['vendas']) && $fields['vendas'] == true){
            $atrasosPortalQuery->whereIn('tipo_venda', ['venda','pedido_futuro_venda','pronta_entrega_venda']);
        }else{
            $atrasosPortalQuery->whereIn('tipo_venda', ['pronta_entrega_triangular', 'remessa_faturamento', 'pedido_orgaopublico', 'pre_pago_triangular', 'venda', 'pre_pago_futuro', 'pronta_entrega_venda', 'isento', 'pre_pago', 'producao', 'triangular', 'pedido_futuro_venda', 'pedido_futuro_triangular']);
        }

        $mesPortalQuery = PedidoPortal::selectRaw(
            "estabelecimento,
            COUNT(id) as pedidos,
            SUM(valor_total_nota) as valor_total,
            id")
            ->whereBetween('data_previsao_entrega', $mes)
            ->whereIn('status_pedido', $statusPortal)
            ->groupBy('estabelecimento','id')
            ->with(['itens_pedido']);

        if(isset($fields['vendas']) && $fields['vendas'] == true){
            $mesPortalQuery->whereIn('tipo_venda', ['venda','pedido_futuro_venda','pronta_entrega_venda']);
        }else{
            $mesPortalQuery->whereIn('tipo_venda', ['pronta_entrega_triangular', 'remessa_faturamento', 'pedido_orgaopublico', 'pre_pago_triangular', 'venda', 'pre_pago_futuro', 'pronta_entrega_venda', 'isento', 'pre_pago', 'producao', 'triangular', 'pedido_futuro_venda', 'pedido_futuro_triangular']);
        }
    
        $proximoMesPortalQuery = PedidoPortal::selectRaw(
            "estabelecimento,
            COUNT(id) as pedidos,
            SUM(valor_total_nota) as valor_total,
            id")
            ->whereBetween('data_previsao_entrega', $mesFuturo)
            ->whereIn('status_pedido', $statusPortal)
            ->groupBy('estabelecimento','id')
            ->with(['itens_pedido']);

        if(isset($fields['vendas']) && $fields['vendas'] == true){
            $proximoMesPortalQuery->whereIn('tipo_venda', ['venda','pedido_futuro_venda','pronta_entrega_venda']);
        }else{
            $proximoMesPortalQuery->whereIn('tipo_venda', ['pronta_entrega_triangular', 'remessa_faturamento', 'pedido_orgaopublico', 'pre_pago_triangular', 'venda', 'pre_pago_futuro', 'pronta_entrega_venda', 'isento', 'pre_pago', 'producao', 'triangular', 'pedido_futuro_venda', 'pedido_futuro_triangular']);
        }
        
        $futuroPortalQuery = PedidoPortal::selectRaw(
            "estabelecimento,
            COUNT(id) as pedidos,
            SUM(valor_total_nota) as valor_total,
            id")
            ->where('data_previsao_entrega', '>', $futuro)
            ->whereIn('status_pedido', $statusPortal)
            ->groupBy('estabelecimento','id')
            ->with(['itens_pedido']);

        if(isset($fields['vendas']) && $fields['vendas'] == true){
            $futuroPortalQuery->whereIn('tipo_venda', ['venda','pedido_futuro_venda','pronta_entrega_venda']);
        }else{
            $futuroPortalQuery->whereIn('tipo_venda', ['pronta_entrega_triangular', 'remessa_faturamento', 'pedido_orgaopublico', 'pre_pago_triangular', 'venda', 'pre_pago_futuro', 'pronta_entrega_venda', 'isento', 'pre_pago', 'producao', 'triangular', 'pedido_futuro_venda', 'pedido_futuro_triangular']);
        }

        if(isset($usersObj)){
            
            $atrasosNasajonQuery->whereIn('vendedor_codigo', $usersObj->pluck('codigo_representante'));
            $mesNasajonQuery->whereIn('vendedor_codigo', $usersObj->pluck('codigo_representante'));
            $mesFuturoNasajonQuery->whereIn('vendedor_codigo', $usersObj->pluck('codigo_representante'));
            $futuroNasajonQuery->whereIn('vendedor_codigo', $usersObj->pluck('codigo_representante'));

            $atrasosPortalQuery->whereIn('usuario', $usersObj->pluck('id'));            
            $mesPortalQuery->whereIn('usuario', $usersObj->pluck('id'));
            $proximoMesPortalQuery->whereIn('usuario', $usersObj->pluck('id'));
            $futuroPortalQuery->whereIn('usuario', $usersObj->pluck('id'));

            if(empty($usuarios_nasajon)){
                $usuarios_nasajon[] = null;
            }
        }
        else if (
            (isset($fields['vendedor_representante']) &&
            !empty($fields['vendedor_representante']) )
        ) {
            $usuario = Crypt::decrypt($fields['vendedor_representante']);
            $usersObj = User::where('id', $usuario)->with('pessoaNasajon')->first();

            if (!is_null($usersObj->pessoaNasajon)) {
                $usuarios_nasajon[] = $usersObj->pessoaNasajon->id;
            }
            else{
                $usuarios_nasajon[] = null;
            }
        }
        if(!isset($fields['intercompany'])){

            $clientesNasajonIntercompany = ClienteNasajon::where('cpf_cnpj', 'like', '06.311.274%')
            ->orWhere('cpf_cnpj', 'like', '05.075.884%')->get();

            $ids_intercompany = $clientesNasajonIntercompany->pluck('id');
            $codigos_intercompany = $clientesNasajonIntercompany->pluck('codigo');

            $atrasosNasajonQuery->whereNotIn('cliente', $ids_intercompany);
            $mesNasajonQuery->whereNotIn('cliente', $ids_intercompany);
            $mesFuturoNasajonQuery->whereNotIn('cliente', $ids_intercompany);
            $futuroNasajonQuery->whereNotIn('cliente', $ids_intercompany);

            $atrasosPortalQuery->whereNotIn('cod_cliente', $codigos_intercompany);
            $mesPortalQuery->whereNotIn('cod_cliente', $codigos_intercompany);
            $proximoMesPortalQuery->whereNotIn('cod_cliente', $codigos_intercompany);
            $futuroPortalQuery->whereNotIn('cod_cliente', $codigos_intercompany);
        }

        $atrasosNasajonObj = $atrasosNasajonQuery->get();
        $mesNasajonObj = $mesNasajonQuery->get();
        $mesFuturoNasajonObj = $mesFuturoNasajonQuery->get();
        $futuroNasajonObj = $futuroNasajonQuery->get();

        $atrasosPortalObj = $atrasosPortalQuery->get();
        $mesPortalObj = $mesPortalQuery->get();
        $proximoMesPortalObj = $proximoMesPortalQuery->get();
        $futuroPortalObj = $futuroPortalQuery->get();
        
        $empresas = returnEmpresasNasajonView();
        unset($empresas[20]);
        foreach($empresas as $codigo_empresa => $empresa){
            $clientesPortalAtraso = PedidoPortal::select('cod_cliente')
                ->whereIn('status_pedido', $statusPortal)
                ->where('estabelecimento', $codigo_empresa)
                ->where('data_previsao_entrega', '<', $atraso);

                if(isset($fields['vendas']) && $fields['vendas'] == true){
                    $clientesPortalAtraso->whereIn('tipo_venda', ['venda','pedido_futuro_venda','pronta_entrega_venda']);
                }else{
                    $clientesPortalAtraso->whereIn('tipo_venda', ['pronta_entrega_triangular', 'remessa_faturamento', 'pedido_orgaopublico', 'pre_pago_triangular', 'venda', 'pre_pago_futuro', 'pronta_entrega_venda', 'isento', 'pre_pago', 'producao', 'triangular', 'pedido_futuro_venda', 'pedido_futuro_triangular']);
                }
            
            $clientesPortalMes = PedidoPortal::select('cod_cliente')
                ->whereIn('status_pedido', $statusPortal)
                ->where('estabelecimento', $codigo_empresa)
                ->whereBetween('data_previsao_entrega', $mes);

                if(isset($fields['vendas']) && $fields['vendas'] == true){
                    $clientesPortalMes->whereIn('tipo_venda', ['venda','pedido_futuro_venda','pronta_entrega_venda']);
                }else{
                    $clientesPortalMes->whereIn('tipo_venda', ['pronta_entrega_triangular', 'remessa_faturamento', 'pedido_orgaopublico', 'pre_pago_triangular', 'venda', 'pre_pago_futuro', 'pronta_entrega_venda', 'isento', 'pre_pago', 'producao', 'triangular', 'pedido_futuro_venda', 'pedido_futuro_triangular']);
                }

            $clientesPortalMesFuturo = PedidoPortal::select('cod_cliente')
                ->whereIn('status_pedido', $statusPortal)
                 ->where('estabelecimento', $codigo_empresa)
                ->whereBetween('data_previsao_entrega', $mesFuturo);

                if(isset($fields['vendas']) && $fields['vendas'] == true){
                    $clientesPortalMesFuturo->whereIn('tipo_venda', ['venda','pedido_futuro_venda','pronta_entrega_venda']);
                }else{
                    $clientesPortalMesFuturo->whereIn('tipo_venda', ['pronta_entrega_triangular', 'remessa_faturamento', 'pedido_orgaopublico', 'pre_pago_triangular', 'venda', 'pre_pago_futuro', 'pronta_entrega_venda', 'isento', 'pre_pago', 'producao', 'triangular', 'pedido_futuro_venda', 'pedido_futuro_triangular']);
                }
            
            $clientesPortalFuturo = PedidoPortal::select('cod_cliente')
                ->whereIn('status_pedido', $statusPortal)
                ->where('estabelecimento', $codigo_empresa)
                ->where('data_previsao_entrega', '>', $futuro);

                if(isset($fields['vendas']) && $fields['vendas'] == true){
                    $clientesPortalFuturo->whereIn('tipo_venda', ['venda','pedido_futuro_venda','pronta_entrega_venda']);
                }else{
                    $clientesPortalFuturo->whereIn('tipo_venda', ['pronta_entrega_triangular', 'remessa_faturamento', 'pedido_orgaopublico', 'pre_pago_triangular', 'venda', 'pre_pago_futuro', 'pronta_entrega_venda', 'isento', 'pre_pago', 'producao', 'triangular', 'pedido_futuro_venda', 'pedido_futuro_triangular']);
                }
            
            $clientesNasajonAtraso = PedidosVendaNasajon::with('cliente_detalhes')
                ->select('cliente')
                ->whereIn('situacao_descricao', $statusNasaJon)
                ->where('estabelecimento_codigo', str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT))
                ->whereRaw('rascunho = false')
                ->where(function($query) use ($fields){
                    $query->orWhere('grupodeoperacao', 'VENDA');
                    $query->orWhere(['grupodeoperacao' => NULL]);
                })
                ->where(function($query) use ($fields){
                    if(isset($fields['vendas']) && $fields['vendas'] == true){
                        $query->orWhereIn('grupodeoperacao_pedido', ['VENDA']);
                    }else{
                        $query->orWhereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA']);
                    }
                })
                ->with('forma_pagamento','forma_pagamento.condicao','itens_pedido')
                ->whereHas('forma_pagamento', function ($query) use ($formaPagamento){
                    if( empty( $formaPagamento ) ){
                        $query
                        ->where('parcelamento_nome','NOT ILIKE' , 'Usar Credito')
                        ->where('parcelamento_codigo','!=' , '608')
                        ->where('formapagamento_descricao','NOT ILIKE','Cartão Crédito');
                    }
                })
                ->where('emissao', '<', $atraso);
            
            $clientesNasajonMes = PedidosVendaNasajon::with('cliente_detalhes')
                ->select('cliente')
                ->whereIn('situacao_descricao', $statusNasaJon)
                ->where('estabelecimento_codigo', str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT))
                ->whereRaw('rascunho = false')
                ->where(function($query) use ($fields){
                    $query->orWhere('grupodeoperacao', 'VENDA');
                    $query->orWhere(['grupodeoperacao' => NULL]);
                })
                ->where(function($query) use ($fields){
                    if(isset($fields['vendas']) && $fields['vendas'] == true){
                        $query->orWhereIn('grupodeoperacao_pedido', ['VENDA']);
                    }else{
                        $query->orWhereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA']);
                    }
                })
                ->with('forma_pagamento','forma_pagamento.condicao','itens_pedido')
                ->whereHas('forma_pagamento', function ($query) use ($formaPagamento){
                    if( empty( $formaPagamento ) ){
                        $query
                        ->where('parcelamento_nome','NOT ILIKE' , 'Usar Credito')
                        ->where('parcelamento_codigo','!=' , '608')
                        ->where('formapagamento_descricao','NOT ILIKE','Cartão Crédito');
                    }
                })
                ->whereBetween('emissao', $mes);

            if(isset($fields['vendas']) && $fields['vendas'] == true){
                $clientesNasajonMes->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO','PEDIDOISENTO','PEDIDOISENTOTORO','PEDIDOPUBLICOSP','PEDIDOPUBLICOTORO','PEDTRIANGULAR']);
            }else{
                $clientesNasajonMes->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDBONIFICACAO', 'PEDBONIFICACAOTORO', 'PEDCONSERTO', 'PEDIDODEVCOMPRA', 'PEDIDODEVCOMPRATORO', 'PEDIDOFUTTORO', 'PEDIDOFUTUROSP', 'PEDIDOISENTO', 'PEDIDOISENTOTORO', 'PEDIDOPCP', 'PEDIDOPUBLICOSP', 'PEDIDOPUBLICOTORO', 'PEDIDOTRANSF', 'PEDIDOTRANSFSEMICMS', 'PEDIDOTRANSFTEXTIL', 'PEDIDOTRANSFTOROSP', 'PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDINDUSTRIA', 'PEDTRANSFCOMREMESSA', 'PEDTRIANGULAR', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO']);
            }
            
            $clientesNasajonMesFuturo = PedidosVendaNasajon::with('cliente_detalhes')
                ->select('cliente')
                ->whereIn('situacao_descricao', $statusNasaJon)
                ->where('estabelecimento_codigo', str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT))
                 ->whereRaw('rascunho = false')
                ->where(function($query) use ($fields){
                    $query->orWhere('grupodeoperacao', 'VENDA');
                    $query->orWhere(['grupodeoperacao' => NULL]);
                })
                ->where(function($query){
                    if(isset($fields['vendas']) && $fields['vendas'] == true){
                        $query->orWhereIn('grupodeoperacao_pedido', ['VENDA']);
                    }else{
                        $query->orWhereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA']);
                    }
                })
                ->with('forma_pagamento','forma_pagamento.condicao','itens_pedido')
                ->whereHas('forma_pagamento', function ($query) use ($formaPagamento){
                    if( empty( $formaPagamento ) ){
                        $query
                        ->where('parcelamento_nome','NOT ILIKE' , 'Usar Credito')
                        ->where('parcelamento_codigo','!=' , '608')
                        ->where('formapagamento_descricao','NOT ILIKE','Cartão Crédito');
                    }
                })
                ->whereIn('emissao', $mesFuturo);

            if(isset($fields['vendas']) && $fields['vendas'] == true){
                $clientesNasajonMesFuturo->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO','PEDIDOISENTO','PEDIDOISENTOTORO','PEDIDOPUBLICOSP','PEDIDOPUBLICOTORO','PEDTRIANGULAR']);
            }else{
                $clientesNasajonMesFuturo->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDBONIFICACAO', 'PEDBONIFICACAOTORO', 'PEDCONSERTO', 'PEDIDODEVCOMPRA', 'PEDIDODEVCOMPRATORO', 'PEDIDOFUTTORO', 'PEDIDOFUTUROSP', 'PEDIDOISENTO', 'PEDIDOISENTOTORO', 'PEDIDOPCP', 'PEDIDOPUBLICOSP', 'PEDIDOPUBLICOTORO', 'PEDIDOTRANSF', 'PEDIDOTRANSFSEMICMS', 'PEDIDOTRANSFTEXTIL', 'PEDIDOTRANSFTOROSP', 'PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDINDUSTRIA', 'PEDTRANSFCOMREMESSA', 'PEDTRIANGULAR', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO']);
            }

            $clientesNasajonFuturo = PedidosVendaNasajon::with('cliente_detalhes')
                ->select('cliente')
                ->whereIn('situacao_descricao', $statusNasaJon)
                ->where('estabelecimento_codigo', str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT))
                ->whereRaw('rascunho = false')
                ->where(function($query) use ($fields){
                    if(isset($fields['vendas']) && $fields['vendas'] == true){
                        $query->where('grupodeoperacao', 'VENDA');
                    }else{
                        $query->orWhere('grupodeoperacao', 'VENDA');
                        $query->orWhere(['grupodeoperacao' => NULL]);
                    }
                })
                ->where(function($query) use ($fields){
                    if(isset($fields['vendas']) && $fields['vendas'] == true){
                        $query->orWhereIn('grupodeoperacao_pedido', ['VENDA']);
                    }else{
                        $query->orWhereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA']);
                    }
                })
                ->with('forma_pagamento','forma_pagamento.condicao')
                ->whereHas('forma_pagamento', function ($query) use ($formaPagamento){
                    if( empty( $formaPagamento ) ){
                        $query
                        ->where('parcelamento_nome','NOT ILIKE' , 'Usar Credito')
                        ->where('parcelamento_codigo','!=' , '608')
                        ->where('formapagamento_descricao','NOT ILIKE','Cartão Crédito');
                    }
                })
                ->where('emissao', '>', $futuro);

            if(isset($fields['vendas']) && $fields['vendas'] == true){
                $clientesNasajonFuturo->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO','PEDIDOISENTO','PEDIDOISENTOTORO','PEDIDOPUBLICOSP','PEDIDOPUBLICOTORO','PEDTRIANGULAR']);
            }else{
                $clientesNasajonFuturo->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDBONIFICACAO', 'PEDBONIFICACAOTORO', 'PEDCONSERTO', 'PEDIDODEVCOMPRA', 'PEDIDODEVCOMPRATORO', 'PEDIDOFUTTORO', 'PEDIDOFUTUROSP', 'PEDIDOISENTO', 'PEDIDOISENTOTORO', 'PEDIDOPCP', 'PEDIDOPUBLICOSP', 'PEDIDOPUBLICOTORO', 'PEDIDOTRANSF', 'PEDIDOTRANSFSEMICMS', 'PEDIDOTRANSFTEXTIL', 'PEDIDOTRANSFTOROSP', 'PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDINDUSTRIA', 'PEDTRANSFCOMREMESSA', 'PEDTRIANGULAR', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO']);
            }
            
            if(isset($usersObj)){

                $clientesPortalAtraso->whereIn('usuario', $usersObj->pluck('id'));
                $clientesPortalMes->whereIn('usuario', $usersObj->pluck('id'));
                $clientesPortalMesFuturo->whereIn('usuario', $usersObj->pluck('id'));
                $clientesPortalFuturo->whereIn('usuario', $usersObj->pluck('id'));
                
                $clientesNasajonAtraso->whereIn('vendedor_codigo', $usersObj->pluck('codigo_representante'));
                $clientesNasajonMes->whereIn('vendedor_codigo', $usersObj->pluck('codigo_representante'));
                $clientesNasajonMesFuturo->whereIn('vendedor_codigo', $usersObj->pluck('codigo_representante'));
                $clientesNasajonFuturo->whereIn('vendedor_codigo', $usersObj->pluck('codigo_representante'));
            }

            if(!isset($fields['intercompany'])){

                $clientesNasajonIntercompany = ClienteNasajon::where('cpf_cnpj', 'like', '06.311.274%')
                ->orWhere('cpf_cnpj', 'like', '05.075.884%')->get();
    
                $ids_intercompany = $clientesNasajonIntercompany->pluck('id');
                $codigos_intercompany = $clientesNasajonIntercompany->pluck('codigo');    
                
                $clientesPortalAtraso->whereNotIn('cod_cliente', $codigos_intercompany);
                $clientesPortalMes->whereNotIn('cod_cliente', $codigos_intercompany);
                $clientesPortalMesFuturo->whereNotIn('cod_cliente', $codigos_intercompany);
                $clientesPortalFuturo->whereNotIn('cod_cliente', $codigos_intercompany);
                
                $clientesNasajonAtraso->whereNotIn('cliente', $ids_intercompany);
                $clientesNasajonMes->whereNotIn('cliente', $ids_intercompany);
                $clientesNasajonMesFuturo->whereNotIn('cliente', $ids_intercompany);
                $clientesNasajonFuturo->whereNotIn('cliente', $ids_intercompany);

            }

            $estabelecimento = str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT);

            $clientesNasajonAtrasoObj = $clientesNasajonAtraso->get();
            $clientesNasajonMesObj = $clientesNasajonMes->get();
            $clientesNasajonMesFuturoObj = $clientesNasajonMesFuturo->get();
            $clientesNasajonFuturoObj = $clientesNasajonFuturo->get();

            $clientesAtraso = $clientesPortalAtraso->get()->pluck('cliente')->pluck('codigo')->toArray();
            $clientesMes = $clientesPortalMes->get()->pluck('cliente')->pluck('codigo')->toArray();
            $clientesMesFuturo = $clientesPortalMesFuturo->get()->pluck('cliente')->pluck('codigo')->toArray();
            $clientesFuturo = $clientesPortalFuturo->get()->pluck('cliente')->pluck('codigo')->toArray();

            $cnpjNasajonAtraso = [];
            $cnpjNasajonMes = [];
            $cnpjNasajonMesFuturo = [];
            $cnpjNasajonFuturo = [];

            $cnpjNasajonAtraso = $clientesNasajonAtrasoObj->pluck('cliente_detalhes')->pluck('codigo')->filter()->toArray();
            $cnpjNasajonMes = $clientesNasajonMesObj->pluck('cliente_detalhes')->pluck('codigo')->filter()->toArray();
            $cnpjNasajonMesFuturo = $clientesNasajonMesFuturoObj->pluck('cliente_detalhes')->pluck('codigo')->filter()->toArray();
            $cnpjNasajonFuturo = $clientesNasajonFuturoObj->pluck('cliente_detalhes')->pluck('codigo')->filter()->toArray();

            $clientes['atraso'][$estabelecimento] = array_unique(array_merge($cnpjNasajonAtraso, $clientesAtraso));

            $clientes['mes'][$estabelecimento] = array_unique(array_merge($cnpjNasajonMes, $clientesMes));

            $clientes['mes_futuro'][$estabelecimento] = array_unique(array_merge($cnpjNasajonMesFuturo, $clientesMesFuturo));

            $clientes['futuro'][$estabelecimento] = array_unique(array_merge($cnpjNasajonFuturo, $clientesFuturo));

            unset($cnpjNasajonAtraso);
            unset($cnpjNasajonMes);
            unset($cnpjNasajonMesFuturo);
            unset($cnpjNasajonFuturo);
        }

        foreach($empresas as $codigo_empresa => $empresa){
            $result[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)] = [
                "estabelecimento" => str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT),
                "passado_pedidos" => 0,
                "passado_clientes" => 0,
                "passado_quantidade_produtos" => [],
                "passado_valor_total" => 0,
                "mes_pedidos" => 0,
                "mes_clientes" => 0,
                "mes_quantidade_produtos" => [],
                "mes_valor_total" => 0,
                "mes_futuro_pedidos" => 0,
                "mes_futuro_clientes" => 0,
                "mes_futuro_quantidade_produtos" => [],
                "mes_futuro_valor_total" => 0,
                "futuro_pedidos" => 0,
                "futuro_clientes" => 0,
                "futuro_quantidade_produtos" => [],
                "futuro_valor_total" => 0,
            ];
        }

        foreach($clientes['atraso'] as $key => $value){
            $result[$key]["passado_clientes"] += count($value);
        }

        foreach($clientes['mes'] as $key => $value){
            $result[$key]["mes_clientes"] += count($value);            
        }

        foreach($clientes['mes_futuro'] as $key => $value){
            $result[$key]["mes_futuro_clientes"] += count($value);          
        }

        foreach($clientes['futuro'] as $key => $value){
            $result[$key]["futuro_clientes"] += count($value);
        }

        foreach ($atrasosNasajonObj as $linha){
            $produto_grupo = [];

            foreach($linha->itens_pedido as $produtos_nasajon){
                $produto_grupo[] = $produtos_nasajon->especificacao->grupo;
            }

            $estabelecimento = str_pad($linha->estabelecimento_codigo, 2, '0', STR_PAD_LEFT);
            $result[$estabelecimento]['passado_pedidos'] += $linha->pedidos;
            $result[$estabelecimento]['passado_valor_total'] += $linha->valor_total;
            $result[$estabelecimento]['passado_quantidade_produtos'][] = $produto_grupo;
            unset($estabelecimento);
        }

        foreach ($mesNasajonObj as $linha){
            $produto_grupo = [];

            foreach($linha->itens_pedido as $produtos_nasajon){
                $produto_grupo[] = $produtos_nasajon->especificacao->grupo;
            }
            $estabelecimento = str_pad($linha->estabelecimento_codigo, 2, '0', STR_PAD_LEFT);
            $result[$estabelecimento]['mes_pedidos'] += $linha->pedidos;
            $result[$estabelecimento]['mes_valor_total'] += $linha->valor_total;
            $result[$estabelecimento]['mes_quantidade_produtos'][] = $produto_grupo;
            unset($estabelecimento);
        }

        foreach ($mesFuturoNasajonObj as $linha){
            $produto_grupo = [];

            foreach($linha->itens_pedido as $produtos_nasajon){
                $produto_grupo[] = $produtos_nasajon->especificacao->grupo;
            }

            $estabelecimento = str_pad($linha->estabelecimento_codigo, 2, '0', STR_PAD_LEFT);
            $result[$estabelecimento]['mes_futuro_pedidos'] += $linha->pedidos;
            $result[$estabelecimento]['mes_futuro_valor_total'] += $linha->valor_total;
            $result[$estabelecimento]['mes_futuro_quantidade_produtos'][] = $produto_grupo;
            unset($estabelecimento);
        }

        foreach ($futuroNasajonObj as $linha){
            $produto_grupo = [];

            foreach($linha->itens_pedido as $produtos_nasajon){
                $produto_grupo[] = $produtos_nasajon->especificacao->grupo;
            }

            $estabelecimento = str_pad($linha->estabelecimento_codigo, 2, '0', STR_PAD_LEFT);
            $result[$estabelecimento]['futuro_pedidos'] += $linha->pedidos;
            $result[$estabelecimento]['futuro_valor_total'] += $linha->valor_total;
            $result[$estabelecimento]['futuro_quantidade_produtos'][] = $produto_grupo;
            unset($estabelecimento);
        }

        foreach ($atrasosPortalObj as $linha){
            $produto_grupo = [];

            foreach($linha->itens_pedido as $produtos_nasajon){
                $produto_grupo[] = $produtos_nasajon->especificacoes->grupo;
            }

            $result[str_pad($linha->estabelecimento, 2, '0', STR_PAD_LEFT)]['passado_pedidos'] += $linha->pedidos;
            $result[str_pad($linha->estabelecimento, 2, '0', STR_PAD_LEFT)]['passado_valor_total'] += $linha->valor_total;
            $result[str_pad($linha->estabelecimento, 2, '0', STR_PAD_LEFT)]['passado_quantidade_produtos'][] = $produto_grupo;
        }

        foreach ($mesPortalObj as $linha){
            $produto_grupo = [];

            foreach($linha->itens_pedido as $produtos_nasajon){
                $produto_grupo[] = $produtos_nasajon->especificacoes->grupo;
            }

            $result[str_pad($linha->estabelecimento, 2, '0', STR_PAD_LEFT)]['mes_pedidos'] += $linha->pedidos;
            $result[str_pad($linha->estabelecimento, 2, '0', STR_PAD_LEFT)]['mes_valor_total'] += $linha->valor_total;
            $result[str_pad($linha->estabelecimento, 2, '0', STR_PAD_LEFT)]['mes_quantidade_produtos'][] = $produto_grupo;
        }

        foreach ($proximoMesPortalObj as $linha){
            $produto_grupo = [];

            foreach($linha->itens_pedido as $produtos_nasajon){
                $produto_grupo[] = $produtos_nasajon->especificacoes->grupo;
            }

            $result[str_pad($linha->estabelecimento, 2, '0', STR_PAD_LEFT)]['mes_futuro_pedidos'] += $linha->pedidos;
            $result[str_pad($linha->estabelecimento, 2, '0', STR_PAD_LEFT)]['mes_futuro_valor_total'] += $linha->valor_total;
            $result[str_pad($linha->estabelecimento, 2, '0', STR_PAD_LEFT)]['mes_futuro_quantidade_produtos'][] = $produto_grupo;
        }

        foreach ($futuroPortalObj as $linha){
            $produto_grupo = [];

            foreach($linha->itens_pedido as $produtos_nasajon){
                $produto_grupo[] = $produtos_nasajon->especificacoes->grupo;
            }

            $result[str_pad($linha->estabelecimento, 2, '0', STR_PAD_LEFT)]['futuro_pedidos'] += $linha->pedidos;
            $result[str_pad($linha->estabelecimento, 2, '0', STR_PAD_LEFT)]['futuro_valor_total'] += $linha->valor_total;
            $result[str_pad($linha->estabelecimento, 2, '0', STR_PAD_LEFT)]['futuro_quantidade_produtos'][] = $produto_grupo;
        }

        $nome_estabelecimentos = returnEmpresasNasajonView();
        $valores_totais = ['passado_valor_total', 'mes_valor_total', 'mes_futuro_valor_total', 'futuro_valor_total'];
        $pedidos = ['passado_pedidos', 'mes_pedidos', 'mes_futuro_pedidos', 'futuro_pedidos'];
        $produtos = ['passado_quantidade_produtos', 'mes_quantidade_produtos', 'mes_futuro_quantidade_produtos', 'futuro_quantidade_produtos'];
        $clientes = ['passado_clientes', 'mes_clientes', 'mes_futuro_clientes', 'futuro_clientes'];

        $passado_array = ['passado_valor_total', 'passado_pedidos', 'passado_clientes','passado_quantidade_produtos'];
        $mes_array = ['mes_valor_total', 'mes_pedidos', 'mes_clientes','mes_quantidade_produtos'];
        $mes_futuro_array = ['mes_futuro_valor_total', 'mes_futuro_pedidos', 'mes_futuro_clientes','mes_futuro_quantidade_produtos'];
        $futuro_array = ['futuro_valor_total', 'futuro_pedidos', 'futuro_clientes','futuro_quantidade_produtos'];

        $resultado = array();
        $criterios = [];

        if(isset($usersObj)){
            $criterios["users"] = $usersObj->pluck('id');
        }
        $criterios['statusNasaJon'] = $statusNasaJon;
        $criterios['statusPortal']  = $statusPortal;
        $criterios['formaPagamento']  = $formaPagamento;
        $criterios['vendas']  = (isset($fields['vendas']) && $fields['vendas'] == 'true') ? true : false;
        
        $criterios['intercompany']  = (!isset($fields['intercompany'])) ? false : true;
        
        $criterios = Crypt::encrypt($criterios);
        foreach ($result as $key => $value){
            $value = (array) $value;
            foreach($value as $k => $v){
                if ($k == "estabelecimento"){
                    $resultado[(int)$value['estabelecimento']]['estabel_no'] = $v;
                    $resultado[(int)$value['estabelecimento']][$k] = $nome_estabelecimentos[(int)$v];
                }
                else {
                    if (isset($resultado[(int)$value['estabelecimento']][$k])){
                        $resultado[(int)$value['estabelecimento']][$k] += is_null($v)? 0:$v;
                    }
                    else{
                        $resultado[(int)$value['estabelecimento']][$k] = is_null($v)? 0:$v;
                    }
                }
            }
        }

        $totalPedidos   = ['passado_pedidos'  => 0, 'mes_pedidos'  => 0, 'mes_futuro_pedidos'  => 0, 'futuro_pedidos'  => 0];
        $totalClientes  = ['passado_clientes' => 0, 'mes_clientes' => 0, 'mes_futuro_clientes' => 0, 'futuro_clientes' => 0];
        $total          = ['passado_valor_total'   => 0, 'mes_valor_total'   => 0, 'mes_futuro_valor_total'   => 0, 'futuro_valor_total'   => 0];
        $totalProdutos  = ['passado_quantidade_produtos' => 0, 'mes_quantidade_produtos' => 0, 'mes_futuro_quantidade_produtos' => 0, 'futuro_quantidade_produtos' => 0];

        foreach ($resultado as $key => $value) {
            foreach ($value as $k => $v) {
                if (in_array($k, $passado_array)) {
                    if($k == 'passado_quantidade_produtos'){
                        $resultado[$key][$k] = count($v) == 0? '':"<a href='#' onclick=\"detalhe_produto('".$resultado[$key]['estabel_no']."',-1,";
                    }else{
                        $resultado[$key][$k] = $v == 0? '':"<a href='#' onclick=\"detalhe('".$resultado[$key]['estabel_no']."',-1,";
                    }
                }
                else if (in_array($k, $mes_array)) {
                    if($k == 'mes_quantidade_produtos'){
                        $resultado[$key][$k] = count($v) == 0? '':"<a href='#' onclick=\"detalhe_produto('".$resultado[$key]['estabel_no']."',0,";
                    }else{
                        $resultado[$key][$k] = $v == 0? '':"<a href='#' onclick=\"detalhe('".$resultado[$key]['estabel_no']."',0,";
                    }
                }
                else if (in_array($k, $mes_futuro_array)) {
                    if($k == 'mes_futuro_quantidade_produtos'){
                        $resultado[$key][$k] = count($v) == 0? '':"<a href='#' onclick=\"detalhe_produto('".$resultado[$key]['estabel_no']."',1,";
                    }else{
                        $resultado[$key][$k] = $v == 0? '':"<a href='#' onclick=\"detalhe('".$resultado[$key]['estabel_no']."',1,";
                    }
                }
                else if (in_array($k, $futuro_array)) {
                    if($k == 'futuro_quantidade_produtos'){
                        $resultado[$key][$k] = count($v) == 0? '':"<a href='#' onclick=\"detalhe_produto('".$resultado[$key]['estabel_no']."',2,";
                    }else{
                        $resultado[$key][$k] = $v == 0? '':"<a href='#' onclick=\"detalhe('".$resultado[$key]['estabel_no']."',2,";
                    }
                }

                if (in_array($k, $valores_totais)) {
                    $resultado[$key][$k] .= $v == 0? '': "'valor', '".$criterios."')\">" . parserValor($v)."</a>";

                    $total[$k] += $v;
                }
                else if (in_array($k, $pedidos)) {
                    $resultado[$key][$k] .= $v == 0? '':"'pedidos', '".$criterios."')\">" . $v . "</a>";

                    $totalPedidos[$k] += $v; 
                }
                else if (in_array($k, $produtos)) {
                    $contar_grupos = [];

                    foreach($v as $grupos_Array){
                        foreach($grupos_Array as $grupos){
                            $contar_grupos[] = $grupos;
                        }
                    }

                    $contar_grupos = array_unique($contar_grupos);

                    $resultado[$key][$k] .= count($contar_grupos) == 0? '':"'total', '".$criterios."')\">" . count($contar_grupos) . "</a>";

                    $totalProdutos[$k] += count($contar_grupos); 
                }
                else if (in_array($k, $clientes)) {
                    $resultado[$key][$k] .= $v == 0? '':"'clientes', '".$criterios."')\">" . $v . "</a>";
                    
                    $totalClientes[$k] += $v;
                }
                else if(!in_array($k, ['estabelecimento', 'estabel_no'])){
                    $resultado[$key][$k] .= $v == 0? '':"'produto', '".$criterios."')\">" . $v . "</a>";   
                }
            }
        }

        foreach ($total as $key => $value) {
            $total[$key] = (empty($value))?'':parserValor($value);
        }

        foreach ($totalPedidos as $key => $value) {
            $totalPedidos[$key] = (empty($value))?'':($value);
        }

        foreach ($totalProdutos as $key => $value) {
            $totalProdutos[$key] = (empty($value))?'':($value);
        }

        foreach ($totalClientes as $key => $value) {
            $totalClientes[$key] = (empty($value))?'':($value);
        }

        foreach ($resultado as $key => $value) {
            if(empty($value['passado_valor_total']) && empty($value['mes_valor_total']) &&  empty($value['mes_futuro_valor_total']) &&  empty($value['futuro_valor_total']) && empty($value['passado_pedidos']) &&  empty($value['mes_pedidos']) &&  empty($value['mes_futuro_pedidos']) &&  empty($value['futuro_pedidos']) && empty($value['passado_clientes']) &&  empty($value['mes_clientes']) && empty($value['mes_futuro_clientes']) &&  empty($value['futuro_clientes'])){

                unset($resultado[$key]);
            }
        }

        $resultado = array_values($resultado);
        
        $response['lines']          = $resultado;
        $response['total']          = $total;
        $response['totalPedidos']   = $totalPedidos;
        $response['totalProdutos'] = $totalProdutos;
        $response['totalClientes']  = $totalClientes;
        $response['criterios']      = $criterios;
        $return = [
            "status" => "success",
            "response" => $response
        ];
    	return response()->json($return);
    }

    private function filterCarteira($fields){
        set_time_limit(300);
        $statusNasaJon   = $this->pedidos_nasajon_status_exibidos;
        $statusPortal    = [];
        $formaPagamento  = [];

        if ( !empty( $fields['status_pedido'] ) ) {
            $statusNasaJon = [$fields['status_pedido']];
        }

        if ( isset($fields['pedido_programado']) && !empty($fields['pedido_programado']) ){
            if($fields['pedido_programado'] == 'pedido_futuro'){
                $statusNasaJon = [null];
                $statusPortal = [8];
            }
        }
        else{
            $statusPortal = [8];
        }

        if ( !empty( $fields['deposito_bancario'] ) ){
            $formaPagamento = 'Usar Crédito';
        }

        $pedidosVendasNasajon = PedidosVendaNasajon::query()
            ->select('numero', 'emissao', 'cliente', 'cliente_nomefantasia', 'estabelecimento_codigo', 'valor','operacao_codigo','id')
            ->where('rascunho' , 'false')
            ->where(function($query){
                $query->orWhere('grupodeoperacao', 'VENDA');
                $query->orWhereNull('grupodeoperacao');
            })
            ->where(function($query) use ($fields){
                if(isset($fields['vendas']) && $fields['vendas'] == true){
                    $query->orWhereIn('grupodeoperacao_pedido', ['VENDA']);
                }else{
                    $query->orWhereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA']);
                }
            })
            ->whereIn('situacao_descricao', $statusNasaJon)
            ->whereIn('operacao_codigo', ['PEDVENDAZFMTORO','PEDBONIFICACAO', 'PEDBONIFICACAOTORO', 'PEDCONSERTO', 'PEDIDODEVCOMPRA', 'PEDIDODEVCOMPRATORO', 'PEDIDOFUTTORO', 'PEDIDOFUTUROSP', 'PEDIDOISENTO', 'PEDIDOISENTOTORO', 'PEDIDOPCP', 'PEDIDOPUBLICOSP', 'PEDIDOPUBLICOTORO', 'PEDIDOTRANSF', 'PEDIDOTRANSFSEMICMS', 'PEDIDOTRANSFTEXTIL', 'PEDIDOTRANSFTOROSP', 'PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDINDUSTRIA', 'PEDTRANSFCOMREMESSA', 'PEDTRIANGULAR', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO'])
            ->with(['forma_pagamento','forma_pagamento.condicao','itens_pedido.especificacao']);
            
        if( empty( $formaPagamento ) ){
            $pedidosVendasNasajon
            ->whereHas('forma_pagamento', function ($query){
                $query
                ->where('parcelamento_nome','NOT ILIKE' , 'Usar Credito')
                ->where('parcelamento_codigo','!=' , '608')
                ->where('formapagamento_descricao','NOT ILIKE','Cartão Crédito');
            });
        }

        $PedidosPortal = PedidoPortal::with(array('itens_pedido'=>function($query){
                $query->select(DB::raw('pedido, tipo_venda, id, SUM(ROUND(preco_unitario*100)/100 * quantidade) as total'));
                $query->groupBy('pedido','tipo_venda','id');
            },'itens_pedido.especificacoes'))
            ->whereIn('tipo_venda', ['pronta_entrega_triangular', 'remessa_faturamento', 'pedido_orgaopublico', 'pre_pago_triangular', 'venda', 'pre_pago_futuro', 'pronta_entrega_venda', 'isento', 'pre_pago', 'producao', 'triangular', 'pedido_futuro_venda', 'pedido_futuro_triangular'])
            ->whereIn('status_pedido', $statusPortal); 

        if(!isset($fields['intercompany'])){

            $clientesNasajonIntercompany = ClienteNasajon::where('cpf_cnpj', 'like', '06.311.274%')
            ->orWhere('cpf_cnpj', 'like', '05.075.884%')->get();

            $ids_intercompany = $clientesNasajonIntercompany->pluck('id');
            $codigos_intercompany = $clientesNasajonIntercompany->pluck('codigo');

            $pedidosVendasNasajon->whereNotIn('cliente', $ids_intercompany);
            
            $PedidosPortal->whereNotIn('cod_cliente', $codigos_intercompany);

        }    
		
		$pedidosVendasNasajon = $pedidosVendasNasajon->get();
		$PedidosPortal = $PedidosPortal->get();

        $atraso = Carbon::Now()->format('Y-m-01');
        $mes = [Carbon::Now()->format('Y-m-01'), Carbon::Now()->format('Y-m-t')];
        $mesMais1 = [Carbon::Now()->addMonthNoOverflow()->format('Y-m-01'), Carbon::Now()->addMonthNoOverflow()->format('Y-m-t')];
		$futuro = Carbon::Now()->addMonthNoOverflow()->format('Y-m-t');
		
		$resultado_atraso = []; // [pedidos=>0,clientes=>[],valor=>0]
		$resultado_mes = []; // [pedidos=>0,clientes=>[],valor=>0]
		$resultado_mais1 = []; // [pedidos=>0,clientes=>[],valor=>0]
		$resultado_futuro = []; // [pedidos=>0,clientes=>[],valor=>0]

		foreach($PedidosPortal as $pedido_portal){
            if(isset($fields['vendas']) && $fields['vendas'] == true){
                if(!in_array($pedido_portal->tipo_venda,['venda','pedido_futuro_venda','pronta_entrega_venda'])){
                    continue;
                }
            }
            $produto_grupo = [];

            foreach($pedido_portal->itens_pedido as $produtos_portal){
                $produto_grupo[] = $produtos_portal->especificacoes->grupo;
            }

			/**
			 * atrasos
			 */
			if(strtotime($pedido_portal->data_previsao_entrega) < strtotime($atraso)){
				if(!isset($resultado_atraso[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)])){
					$resultado_atraso[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)] = ['pedidos' => 0, 'clientes' => [], 'valor' => 0 ,'quantidade_produtos' => []];
				}
				$resultado_atraso[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['pedidos'] += 1;
				$resultado_atraso[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['valor'] += floatval($pedido_portal->itens_pedido[0]->total);
                $resultado_atraso[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['quantidade_produtos'][] = $produto_grupo;
				if(!isset($resultado_atraso[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['clientes'][$pedido_portal->cod_cliente])){
					$resultado_atraso[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['clientes'][$pedido_portal->cod_cliente] = $pedido_portal->cod_cliente;
				}
			}
			/**
			 * mes
			 */
			elseif(
				strtotime($pedido_portal->data_previsao_entrega) >= strtotime($mes[0]) &&
				strtotime($pedido_portal->data_previsao_entrega) <= strtotime($mes[1])
			){
				if(!isset($resultado_mes[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)])){
					$resultado_mes[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)] = ['pedidos' => 0, 'clientes' => [], 'valor' => 0 ,'quantidade_produtos' => []];
				}
				$resultado_mes[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['pedidos'] += 1;
				$resultado_mes[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['valor'] += floatval($pedido_portal->itens_pedido[0]->total);
                $resultado_mes[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['quantidade_produtos'][] = $produto_grupo;
				if(!isset($resultado_mes[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['clientes'][$pedido_portal->cod_cliente])){
					$resultado_mes[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['clientes'][$pedido_portal->cod_cliente] = $pedido_portal->cod_cliente;
				}
			}
			/**
			 * mes mais 1
			 */
			elseif(
				strtotime($pedido_portal->data_previsao_entrega) >= strtotime($mesMais1[0]) &&
				strtotime($pedido_portal->data_previsao_entrega) <= strtotime($mesMais1[1])
			){
				if(!isset($resultado_mais1[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)])){
					$resultado_mais1[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)] = ['pedidos' => 0, 'clientes' => [], 'valor' => 0 ,'quantidade_produtos' => []];
				}
				$resultado_mais1[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['pedidos'] += 1;
				$resultado_mais1[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['valor'] += floatval($pedido_portal->itens_pedido[0]->total);
                $resultado_mais1[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['quantidade_produtos'][] = $produto_grupo;
				if(!isset($resultado_mais1[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['clientes'][$pedido_portal->cod_cliente])){
					$resultado_mais1[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['clientes'][$pedido_portal->cod_cliente] = $pedido_portal->cod_cliente;
				}
			}
			/**
			 * futuro
			 */
			elseif(
				strtotime($pedido_portal->data_previsao_entrega) > strtotime($futuro)
			){
				if(!isset($resultado_futuro[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)])){
					$resultado_futuro[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)] = ['pedidos' => 0, 'clientes' => [], 'valor' => 0 ,'quantidade_produtos' => []];
				}
				$resultado_futuro[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['pedidos'] += 1;
				$resultado_futuro[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['valor'] += floatval($pedido_portal->itens_pedido[0]->total);
                $resultado_futuro[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['quantidade_produtos'][] = $produto_grupo;
				if(!isset($resultado_futuro[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['clientes'][$pedido_portal->cod_cliente])){
					$resultado_futuro[str_pad($pedido_portal->estabelecimento, 2, '0', STR_PAD_LEFT)]['clientes'][$pedido_portal->cod_cliente] = $pedido_portal->cod_cliente;
				}
			}
		}

		foreach($pedidosVendasNasajon as $pedidoNasajon){
            if(isset($fields['vendas']) && $fields['vendas'] == true){
                if(!in_array($pedidoNasajon->operacao_codigo,['PEDVENDAZFMTORO','PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO','PEDIDOISENTO','PEDIDOISENTOTORO','PEDIDOPUBLICOSP','PEDIDOPUBLICOTORO','PEDTRIANGULAR'])){
                    continue;
                }
            }

            $produto_grupo = [];

            foreach($pedidoNasajon->itens_pedido as $produtos_nasajon){
                if(isset($produtos_nasajon->especificacao->produtoGrupo->descricao) && !empty($produtos_nasajon->especificacao->produtoGrupo->descricao)){
                    $produto_grupo[] = $produtos_nasajon->especificacao->produtoGrupo->descricao;
                }
            }

			/**
			 * atrasos
			 */
			if(strtotime($pedidoNasajon->emissao) < strtotime($atraso)){
				if(!isset($resultado_atraso[$pedidoNasajon->estabelecimento_codigo])){
					$resultado_atraso[$pedidoNasajon->estabelecimento_codigo] = ['pedidos' => 0, 'clientes' => [], 'valor' => 0, 'quantidade_produtos' => []];
				}
				$resultado_atraso[$pedidoNasajon->estabelecimento_codigo]['pedidos'] += 1;
				$resultado_atraso[$pedidoNasajon->estabelecimento_codigo]['valor'] += floatval($pedidoNasajon->valor);
                $resultado_atraso[$pedidoNasajon->estabelecimento_codigo]['quantidade_produtos'][] = $produto_grupo;
				if(!isset($resultado_atraso[$pedidoNasajon->estabelecimento_codigo]['clientes'][$pedidoNasajon->cliente])){
					$resultado_atraso[$pedidoNasajon->estabelecimento_codigo]['clientes'][$pedidoNasajon->cliente] = $pedidoNasajon->cliente_nomefantasia;
				}
			}
			/**
			 * mes
			 */
			elseif(
				strtotime($pedidoNasajon->emissao) >= strtotime($mes[0]) &&
				strtotime($pedidoNasajon->emissao) <= strtotime($mes[1])
			){
				if(!isset($resultado_mes[$pedidoNasajon->estabelecimento_codigo])){
					$resultado_mes[$pedidoNasajon->estabelecimento_codigo] = ['pedidos' => 0, 'clientes' => [], 'valor' => 0, 'quantidade_produtos' => []];
				}
				$resultado_mes[$pedidoNasajon->estabelecimento_codigo]['pedidos'] += 1;
				$resultado_mes[$pedidoNasajon->estabelecimento_codigo]['valor'] += floatval($pedidoNasajon->valor);
                $resultado_mes[$pedidoNasajon->estabelecimento_codigo]['quantidade_produtos'][] = $produto_grupo;
				if(!isset($resultado_mes[$pedidoNasajon->estabelecimento_codigo]['clientes'][$pedidoNasajon->cliente])){
					$resultado_mes[$pedidoNasajon->estabelecimento_codigo]['clientes'][$pedidoNasajon->cliente] = $pedidoNasajon->cliente_nomefantasia;
				}
			}
			/**
			 * mes mais 1
			 */
			elseif(
				strtotime($pedidoNasajon->emissao) >= strtotime($mesMais1[0]) &&
				strtotime($pedidoNasajon->emissao) <= strtotime($mesMais1[1])
			){
				if(!isset($resultado_mais1[$pedidoNasajon->estabelecimento_codigo])){
					$resultado_mais1[$pedidoNasajon->estabelecimento_codigo] = ['pedidos' => 0, 'clientes' => [], 'valor' => 0, 'quantidade_produtos' => [] ];
				}
				$resultado_mais1[$pedidoNasajon->estabelecimento_codigo]['pedidos'] += 1;
				$resultado_mais1[$pedidoNasajon->estabelecimento_codigo]['valor'] += floatval($pedidoNasajon->valor);
                $resultado_mais1[$pedidoNasajon->estabelecimento_codigo]['quantidade_produtos'][] = $produto_grupo;
				if(!isset($resultado_mais1[$pedidoNasajon->estabelecimento_codigo]['clientes'][$pedidoNasajon->cliente])){
					$resultado_mais1[$pedidoNasajon->estabelecimento_codigo]['clientes'][$pedidoNasajon->cliente] = $pedidoNasajon->cliente_nomefantasia;
				}
			}
			/**
			 * futuro
			 */
			elseif(
				strtotime($pedidoNasajon->emissao) > strtotime($futuro)
			){
				if(!isset($resultado_futuro[$pedidoNasajon->estabelecimento_codigo])){
					$resultado_futuro[$pedidoNasajon->estabelecimento_codigo] = ['pedidos' => 0, 'clientes' => [], 'valor' => 0, 'quantidade_produtos' => []];
				}
				$resultado_futuro[$pedidoNasajon->estabelecimento_codigo]['pedidos'] += 1;
				$resultado_futuro[$pedidoNasajon->estabelecimento_codigo]['valor'] += floatval($pedidoNasajon->valor);
				$resultado_futuro[$pedidoNasajon->estabelecimento_codigo]['quantidade_produtos'][] = $produto_grupo;
				if(!isset($resultado_futuro[$pedidoNasajon->estabelecimento_codigo]['clientes'][$pedidoNasajon->cliente])){
					$resultado_futuro[$pedidoNasajon->estabelecimento_codigo]['clientes'][$pedidoNasajon->cliente] = $pedidoNasajon->cliente_nomefantasia;
				}
			}
		}
		foreach($resultado_atraso as $estabelecimento => $resultado){
			$resultado_atraso[$estabelecimento]['clientes'] = count($resultado['clientes']);
		}
		foreach($resultado_mes as $estabelecimento => $resultado){
			$resultado_mes[$estabelecimento]['clientes'] = count($resultado['clientes']);
		}
		foreach($resultado_mais1 as $estabelecimento => $resultado){
			$resultado_mais1[$estabelecimento]['clientes'] = count($resultado['clientes']);
		}
		foreach($resultado_futuro as $estabelecimento => $resultado){
			$resultado_futuro[$estabelecimento]['clientes'] = count($resultado['clientes']);
		}

		$empresas = returnEmpresasNasajonView();
		unset($empresas[20]);
		$resultado = [];
        $criterios = [];
        $criterios['statusNasaJon']   = $statusNasaJon;
        $criterios['statusPortal']    = $statusPortal;
        $criterios['formaPagamento'] = $formaPagamento;
        $criterios['intercompany']  = (!isset($fields['intercompany'])) ? false : true;
        $criterios['vendas']  = (isset($fields['vendas']) && $fields['vendas'] == true) ? true : false;
        $criterios = Crypt::encrypt($criterios);
        
        $totalPedidos   = ['passado_pedidos'  => 0, 'mes_pedidos'  => 0, 'mes_futuro_pedidos'  => 0, 'futuro_pedidos'  => 0];
        $totalClientes  = ['passado_clientes' => 0, 'mes_clientes' => 0, 'mes_futuro_clientes' => 0, 'futuro_clientes' => 0];
        $totalProdutos  = ['passado_quantidade_produtos' => 0, 'mes_quantidade_produtos' => 0, 'mes_futuro_quantidade_produtos' => 0, 'futuro_quantidade_produtos' => 0];
        $total          = ['passado_valor_total'    => 0, 'mes_valor_total'    => 0, 'mes_futuro_valor_total'    => 0, 'futuro_valor_total'    => 0];

        foreach($empresas as $codigo_empresa => $empresa){
			$retorno = [
                "estabelecimento" => $empresa,
                "passado_pedidos" => '',
                "passado_clientes" => '',
                "passado_valor_total" => '',
                "passado_quantidade_produtos" =>'',
                "mes_pedidos" => '',
                "mes_clientes" => '',
                "mes_valor_total" => '',
                "mes_quantidade_produtos" => '',
                "mes_futuro_pedidos" => '',
                "mes_futuro_clientes" => '',
                "mes_futuro_valor_total" => '',
                "mes_futuro_quantidade_produtos" => '',
                "futuro_pedidos" => '',
                "futuro_clientes" => '',
                "futuro_valor_total" => '',
                "futuro_quantidade_produtos" => '',
                "futuro_quantidade_total" => '',
			];
			if(isset($resultado_atraso[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)])){
                $contar_grupos = [];

                foreach($resultado_atraso[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['quantidade_produtos'] as $grupos_Array){
                    foreach($grupos_Array as $grupos){
                        $contar_grupos[] = $grupos;
                    }
                }

                $contar_grupos = array_unique($contar_grupos);

				$retorno['passado_pedidos'] = $resultado_atraso[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['pedidos'];
                $retorno['passado_clientes'] = $resultado_atraso[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['clientes'];
				$retorno['passado_valor_total'] = parserValor($resultado_atraso[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['valor']);
                $retorno['passado_quantidade_produtos'] = count($contar_grupos);

				$retorno['passado_pedidos'] = "<a href='#' onclick=\"detalhe('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."',-1, 'pedidos', '".$criterios."')\">".$retorno['passado_pedidos']."</a>";
				$retorno['passado_clientes'] = "<a href='#' onclick=\"detalhe('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."',-1, 'clientes', '".$criterios."')\">".$retorno['passado_clientes']."</a>";
                $retorno['passado_quantidade_produtos'] = "<a href='#' onclick=\"detalhe_produto('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."',-1, 'total', '".$criterios."')\">".$retorno['passado_quantidade_produtos']."</a>";            
				$retorno['passado_valor_total'] = "<a href='#' onclick=\"detalhe('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."',-1, 'total', '".$criterios."')\">".$retorno['passado_valor_total']."</a>";
            
                $totalPedidos['passado_pedidos'] += ($resultado_atraso[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['pedidos']);
                $totalClientes['passado_clientes'] += ($resultado_atraso[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['clientes']);
                $totalProdutos['passado_quantidade_produtos'] += count($contar_grupos);
				$total['passado_valor_total'] += ($resultado_atraso[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['valor']);
                
            }
			if(isset($resultado_mes[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)])){
                $contar_grupos = [];

                foreach($resultado_mes[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['quantidade_produtos'] as $grupos_Array){
                    foreach($grupos_Array as $grupos){
                        $contar_grupos[] = $grupos;
                    }
                }

                $contar_grupos = array_unique($contar_grupos);

				$retorno['mes_pedidos'] = $resultado_mes[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['pedidos'];
				$retorno['mes_clientes'] = $resultado_mes[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['clientes'];
				$retorno['mes_valor_total'] = parserValor($resultado_mes[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['valor']);
                $retorno['mes_quantidade_produtos'] = count($contar_grupos);

				$retorno['mes_pedidos'] = "<a href='#' onclick=\"detalhe('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."', 0, 'pedidos', '".$criterios."')\">".$retorno['mes_pedidos']."</a>";
				$retorno['mes_clientes'] = "<a href='#' onclick=\"detalhe('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."', 0, 'clientes', '".$criterios."')\">".$retorno['mes_clientes']."</a>";
				$retorno['mes_quantidade_produtos'] = "<a href='#' onclick=\"detalhe_produto('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."',0, 'total', '".$criterios."')\">".$retorno['mes_quantidade_produtos']."</a>";            
				$retorno['mes_valor_total'] = "<a href='#' onclick=\"detalhe('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."', 0, 'total', '".$criterios."')\">".$retorno['mes_valor_total']."</a>";

                $totalPedidos['mes_pedidos']    += ($resultado_mes[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['pedidos']);
                $totalClientes['mes_clientes'] += ($resultado_mes[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['clientes']);
                $totalProdutos['mes_quantidade_produtos'] += count($contar_grupos);
				$total['mes_valor_total'] += ($resultado_mes[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['valor']);

			}
			if(isset($resultado_mais1[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)])){
                $contar_grupos = [];

                foreach($resultado_mais1[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['quantidade_produtos'] as $grupos_Array){
                    foreach($grupos_Array as $grupos){
                        $contar_grupos[] = $grupos;
                    }
                }

                $contar_grupos = array_unique($contar_grupos);

				$retorno['mes_futuro_pedidos'] = $resultado_mais1[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['pedidos'];
				$retorno['mes_futuro_clientes'] = $resultado_mais1[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['clientes'];
				$retorno['mes_futuro_valor_total'] = parserValor($resultado_mais1[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['valor']);
                $retorno['futuro_quantidade_produtos'] = count($contar_grupos);

				$retorno['mes_futuro_pedidos'] = "<a href='#' onclick=\"detalhe('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."', 1, 'pedidos', '".$criterios."')\">".$retorno['mes_futuro_pedidos']."</a>";
				$retorno['mes_futuro_clientes'] = "<a href='#' onclick=\"detalhe('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."', 1, 'clientes', '".$criterios."')\">".$retorno['mes_futuro_clientes']."</a>";
				$retorno['mes_futuro_quantidade_produtos'] = "<a href='#' onclick=\"detalhe_produto('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."',1, 'total', '".$criterios."')\">".$retorno['mes_futuro_quantidade_produtos']."</a>";            
				$retorno['mes_futuro_valor_total'] = "<a href='#' onclick=\"detalhe('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."', 1, 'total', '".$criterios."')\">".$retorno['mes_futuro_valor_total']."</a>";

                $totalPedidos['mes_futuro_pedidos']    += ($resultado_mais1[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['pedidos']);
                $totalClientes['mes_futuro_clientes'] += ($resultado_mais1[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['clientes']);
                $totalProdutos['mes_futuro_quantidade_produtos'] += count($contar_grupos);
				$total['mes_futuro_valor_total'] += ($resultado_mais1[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['valor']);

			}
			if(isset($resultado_futuro[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)])){
                $contar_grupos = [];

                foreach($resultado_futuro[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['quantidade_produtos'] as $grupos_Array){
                    foreach($grupos_Array as $grupos){
                        $contar_grupos[] = $grupos;
                    }
                }

                $contar_grupos = array_unique($contar_grupos);

				$retorno['futuro_pedidos'] = $resultado_futuro[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['pedidos'];
				$retorno['futuro_clientes'] = $resultado_futuro[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['clientes'];
				$retorno['futuro_valor_total'] = parserValor($resultado_futuro[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['valor']);
                $retorno['futuro_quantidade_produtos'] = count($contar_grupos);

				$retorno['futuro_pedidos'] = "<a href='#' onclick=\"detalhe('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."', 2, 'pedidos', '".$criterios."')\">".$retorno['futuro_pedidos']."</a>";
				$retorno['futuro_quantidade_produtos'] = "<a href='#' onclick=\"detalhe_produto('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."',2, 'total', '".$criterios."')\">".$retorno['futuro_quantidade_produtos']."</a>";            
				$retorno['futuro_clientes'] = "<a href='#' onclick=\"detalhe('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."', 2, 'clientes', '".$criterios."')\">".$retorno['futuro_clientes']."</a>";
				$retorno['futuro_valor_total'] = "<a href='#' onclick=\"detalhe('".str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)."', 2, 'total', '".$criterios."')\">".$retorno['futuro_valor_total']."</a>";

                $totalPedidos['futuro_pedidos']    += ($resultado_futuro[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['pedidos']);
                $totalClientes['futuro_clientes'] += ($resultado_futuro[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['clientes']);
				$totalProdutos['futuro_quantidade_produtos'] += count($contar_grupos);
				$total['futuro_valor_total']            += ($resultado_futuro[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]['valor']);
			}

			if(
				isset($resultado_atraso[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]) ||
				isset($resultado_mes[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]) ||
				isset($resultado_mais1[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)]) ||
				isset($resultado_futuro[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)])
			){
				$resultado[str_pad($codigo_empresa, 2, '0', STR_PAD_LEFT)] = $retorno;
			}
		}
		$resultado = array_values($resultado);
        
		unset($resultado_atraso);
		unset($resultado_mes);
		unset($resultado_mais1);
		unset($resultado_futuro);

        $totalPedidos['passado_pedidos']      = !empty($totalPedidos['passado_pedidos'])        ? $totalPedidos['passado_pedidos']                    : '';
        $totalClientes['passado_clientes']    = !empty($totalClientes['passado_clientes'])      ? $totalClientes['passado_clientes']                  : '';
        $total['passado_valor_total']         = !empty($total['passado_valor_total'])           ? parserValor($total['passado_valor_total'])          : '';
        $totalProdutos['passado_quantidade_produtos'] = !empty($totalProdutos['passado_quantidade_produtos'])   ? ($totalProdutos['passado_quantidade_produtos'])  : '';
        
        $totalPedidos['mes_pedidos']          = !empty($totalPedidos['mes_pedidos'])            ? $totalPedidos['mes_pedidos']                        : '';
        $totalClientes['mes_clientes']        = !empty($totalClientes['mes_clientes'])          ? $totalClientes['mes_clientes']                      : '';
        $total['mes_valor_total']             = !empty($total['mes_valor_total'])               ? parserValor($total['mes_valor_total'])              : '';
        $totalProdutos['mes_quantidade_produtos']     = !empty($totalProdutos['mes_quantidade_produtos'])       ? ($totalProdutos['mes_quantidade_produtos'])      : '';
        
        $totalPedidos['mes_futuro_pedidos']   = !empty($totalPedidos['mes_futuro_pedidos'])     ? $totalPedidos['mes_futuro_pedidos']                 : '';
        $totalClientes['mes_futuro_clientes'] = !empty($totalClientes['mes_futuro_clientes'])   ? $totalClientes['mes_futuro_clientes']               : '';
        $total['mes_futuro_valor_total']      = !empty($total['mes_futuro_valor_total'])         ? parserValor($total['mes_futuro_valor_total'])      : '';
        $totalProdutos['mes_futuro_quantidade_produtos']  = !empty($totalProdutos['mes_futuro_quantidade_produtos'])  ? ($totalProdutos['mes_futuro_quantidade_produtos'])  : '';
        
        $totalPedidos['futuro_pedidos']       = !empty($totalPedidos['futuro_pedidos'])         ? $totalPedidos['futuro_pedidos']                     : ''; 
        $totalClientes['futuro_clientes']     = !empty($totalClientes['futuro_clientes'])       ? $totalClientes['futuro_clientes']                   : ''; 
        $total['futuro_valor_total']          = !empty($total['futuro_valor_total'])            ? parserValor($total['futuro_valor_total'])           : '';
        $totalProdutos['futuro_quantidade_produtos']  = !empty($totalProdutos['futuro_quantidade_produtos'])    ? ($totalProdutos['futuro_quantidade_produtos'])   : '';
        
        $response['lines']          = $resultado;
        $response['total']          = $total;
        $response['totalPedidos']   = $totalPedidos;
        $response['totalProdutos'] = $totalProdutos;
        $response['totalClientes']  = $totalClientes;
        $response['criterios']      = $criterios;
        $return = [
            "status" => "success",
            "response" => $response
        ];
    	return response()->json($return);
    }
}
