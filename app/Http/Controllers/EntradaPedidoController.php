<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

use App\Http\Controllers\UserController;

use App\PedidoVenda;
use App\PedidoPortal;
use App\PedidosVendaNasajon;
use App\User;

use Auth;

class EntradaPedidoController extends Controller
{
    public function index(Request $request){

        if(Auth::user()->hasPermissionTo("programas App\EntradaPedido") === false){
            return abort(403);
        }


        $request->session()->flash('model', 'App\EntradaPedido');

        $subordinados = UserController::varreSubordinados(Auth::id());

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[0]);
        unset($estabelecimentos[5]);

        $dropdown_usuarios = [];
        $dropdown_gerentes = [];
        $dropdown_diretores = [];
        if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false || strpos(strtolower(Auth::user()->tipo_usuario->nome), "Gerente Comercial") !== false || strpos(strtolower(Auth::user()->tipo_usuario->nome), "supervisor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === 'administrador' || Auth::user()->id == 92){
            //dd(strtolower(Auth::user()->tipo_usuario->nome) === "supervisor");
            if (Auth::user()->tipo_usuario->nome == "Gerente" || Auth::user()->tipo_usuario->nome == "Gerente Comercial"){
                $users = User::with('tipo_usuario')
                ->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')
                ->whereIn('id', UserController::varreSubordinados(Auth::id()))
                ->orderBy('name', 'asc')
                ->get();
            }
            else if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === 'administrador' || Auth::user()->id == 92){
                $users = User::with('tipo_usuario')
                ->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')
                // ->whereHas('tipo_usuario', function ($query){
                //     $query->where('nome', '!=', 'Vendedor Interno');
                // })
                ->orderBy('name', 'asc')
                ->get();

                $status_pedido['cancelados'] = 'Cancelados';
            }
            else if (strtolower(Auth::user()->tipo_usuario->nome) === "supervisor"){
                $users = User::with('tipo_usuario')
                ->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')
                ->whereIn('id', UserController::varreSubordinados(Auth::user()->responsavel))
                // ->whereHas('tipo_usuario', function ($query){
                //     $query->where('nome', '!=', 'Vendedor Interno');
                // })
                ->orderBy('name', 'asc')
                ->get();
            }
            foreach ($users as $user) {
                if(is_null($user->tipo_usuario)){
                    continue;
                }

                $id = Crypt::encrypt($user->id);

                if (!is_null($user->codigo_representante)){
                    $dropdown_usuarios[$id] = strtoupper($user->name);
                }
                if (strpos(strtolower($user->tipo_usuario->nome), "gerente") !== false){
                    $dropdown_gerentes[$id] = strtoupper($user->name);
                }
                else if (strpos(strtolower($user->tipo_usuario->nome), "diretor") !== false){
                    $dropdown_diretores[$id] = strtoupper($user->name);
                }
            }

        }

        $variaveis_view = [
            'estabelecimentos' => $estabelecimentos,
            'dropdown_usuarios' => $dropdown_usuarios,
            'dropdown_gerentes' => $dropdown_gerentes,
            'dropdown_diretores' => $dropdown_diretores
        ];

        return view('programs.entrada_pedido.index')->with($variaveis_view);

    }

    public function filtro(Request $request){

        $fields = $request->only(['diretor','gerente','vendedor', 'estabelecimento']);

    	$subordinados = UserController::varreSubordinados(Auth::id());

        $pedidoVendaDiaProntaEntrega = PedidosVendaNasajon::selectRaw('count(distinct id) pedidos_dia, count(distinct cliente) clientes_dia, sum(valor) valor_dia')
        ->where('emissao', date('Y-m-d'))
        ->where(function($query){
            $query->orWhereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA']);
            $query->orWhere(['grupodeoperacao' => NULL]);
        });
    
    	$pedidoVendaDiaProgramada = PedidoPortal::selectRaw("count(distinct id) pedidos_dia, count(distinct cod_cliente) clientes_dia, sum(valor_total_nota) valor_dia ")
    		->where('data_pedido', date('Y-m-d'))
    		->where('pedido_futuro', true)
    		->where('status_pedido', '8');
            
        $pedidoVendaMesProntaEntrega = PedidosVendaNasajon::selectRaw('count(distinct id) pedidos_mes, count(distinct cliente) clientes_mes, sum(valor) valor_mes')
        ->whereYear('emissao',date('Y'))
        ->whereMonth('emissao',date('m'))
        ->where(function($query){
            $query->orWhereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA']);
            $query->orWhere(['grupodeoperacao' => NULL]);
        });

    	$pedidoVendaMesProgramada = PedidoPortal::selectRaw("count(distinct id) pedidos_mes, count(distinct cod_cliente) clientes_mes, sum(valor_total_nota) valor_mes ")
            ->whereYear('data_pedido',date('Y'))
            ->whereMonth('data_pedido',date('m'))
            ->where('pedido_futuro', true)
            ->where('status_pedido', '8');
        
        $pedidoVendaAnoProntaEntrega = PedidosVendaNasajon::selectRaw('count(distinct id) pedidos_ano, count(distinct cliente) clientes_ano, sum(valor) valor_ano')
        ->whereYear('emissao',date('Y'))
        ->where(function($query){
            $query->orWhereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA']);
            $query->orWhere(['grupodeoperacao' => NULL]);
        });
    
    	$pedidoVendaAnoProgramada = PedidoPortal::selectRaw("count(distinct id) pedidos_ano, count(distinct cod_cliente) clientes_ano, sum(valor_total_nota) valor_ano ")
        ->whereYear('data_pedido',date('Y'))
        ->where('pedido_futuro', true)
        ->where('status_pedido', '8');

        if (!is_null($fields['vendedor'])){

            $user = Crypt::decrypt($fields['vendedor']);

            $userObj = User::find($user);
            $usuarios[] = $userObj->codigo_representante;        
        }
        else if (!is_null($fields['gerente'])){
            $gerente = Crypt::decrypt($fields['gerente']);
            $usuarios = UserController::varreSubordinados($gerente);

        }
        else if (!is_null($fields['diretor'])){
            $diretor = Crypt::decrypt($fields['diretor']);
            $usuarios = UserController::varreSubordinados($diretor);
        }


        if(isset($usuarios)){
            $pedidoVendaDiaProntaEntrega->whereIn('vendedor_codigo', $usuarios);
            $pedidoVendaDiaProgramada->whereIn('usuario', $usuarios);
            $pedidoVendaMesProntaEntrega->whereIn('vendedor_codigo', $usuarios);
            $pedidoVendaMesProgramada->whereIn('usuario', $usuarios);
            $pedidoVendaAnoProntaEntrega->whereIn('vendedor_codigo', $usuarios);
            $pedidoVendaAnoProgramada->whereIn('usuario', $usuarios);            
        }

        if(isset($fields['estabelecimento'])){
            $pedidoVendaDiaProntaEntrega->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
            $pedidoVendaDiaProgramada->where('estabelecimento', $fields['estabelecimento']);
            $pedidoVendaMesProntaEntrega->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
            $pedidoVendaMesProgramada->where('estabelecimento', $fields['estabelecimento']);
            $pedidoVendaAnoProntaEntrega->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
            $pedidoVendaAnoProgramada->where('estabelecimento', $fields['estabelecimento']);            
        }

    	// dd($pedidoVendaAnoProgramada->toSql());

        $diaProntaEntrega = $pedidoVendaDiaProntaEntrega->first();
        $diaProgramada = $pedidoVendaDiaProgramada->first();
        
        $mesProntaEntrega = $pedidoVendaMesProntaEntrega->first();
        $mesProgramada = $pedidoVendaMesProgramada->first();

        $anoProntaEntrega = $pedidoVendaAnoProntaEntrega->first();
		$anoProgramada = $pedidoVendaAnoProgramada->first();

		$return = [
			'pronta entrega' => [
				'pedidos_dia' => (string) $diaProntaEntrega->pedidos_dia??' ',
				'clientes_dia' => (string) $diaProntaEntrega->clientes_dia??' ',
				'valor_dia' => (string) parserValor($diaProntaEntrega->valor_dia)??' ',
				'pedidos_mes' => (string) $mesProntaEntrega->pedidos_mes??' ',
				'clientes_mes' => (string) $mesProntaEntrega->clientes_mes??' ',
				'valor_mes' => (string) parserValor($mesProntaEntrega->valor_mes)??' ',
				'pedidos_ano' => (string) $anoProntaEntrega->pedidos_ano??' ',
				'clientes_ano' => (string) $anoProntaEntrega->clientes_ano??' ',
				'valor_ano' => (string) parserValor($anoProntaEntrega->valor_ano)??' ',
                'hash' => Crypt::encrypt($usuarios??'')
			], 
			'programada' => [
				'pedidos_dia' => (string) $diaProgramada->pedidos_dia??' ',
				'clientes_dia' => (string) $diaProgramada->clientes_dia??' ',
				'valor_dia' => (string) parserValor($diaProgramada->valor_dia)??' ',
				'pedidos_mes' => (string) $mesProgramada->pedidos_mes??' ',
				'clientes_mes' => (string) $mesProgramada->clientes_mes??' ',
				'valor_mes' => (string) parserValor($mesProgramada->valor_mes)??' ',
				'pedidos_ano' => (string) $anoProgramada->pedidos_ano??' ',
				'clientes_ano' => (string) $anoProgramada->clientes_ano??' ',
				'valor_ano' => (string) parserValor($anoProgramada->valor_ano)??' ',
                'hash' => Crypt::encrypt($usuarios??'')
			]
		];

		return response()->json($return);

    }

    public function parserStatusPara($status){
        switch ($status){
            case "1":
                return 'Em Aprovação';
            break;
            case "2":
                return 'Em Andamento';
            break;
            case "3":
                return 'Em Andamento';
            break;
            case "4":
                return 'Separando';
            break;
            case "6":
                return 'Faturado Total';
            break;
            case "7":
                return 'Faturado Parcial';
            break;
            case "9":
                return 'Cancelado';
            break;
            case "A":
                return 'WEB - Em Andamento';
            break;
            case "E":
                return 'WEB - Em Aprovação';
            break;
            case "F":
                return 'WEB - Em Aprovação';
            break;
            case "N":
                return 'WEB - Reprovado';
            break;
            case "R":
                return 'WEB - Futuro';
            break;
            case "Z":
                return 'WEB - Reprovado';
            break;
            case "X":
                return 'WEB - Reprovado';
            break;
        }
    }

    public function todosPedidosAcumulados(Request $request){

        $fields = $request->only(['periodo', 'venda', 'hash']);

        $vendedores = Crypt::decrypt($fields['hash']);
        $estabelecimentos = returnEmpresasNasajonView();

        // dd($fields);
        
        $total = 0.0;

        if($fields['venda'] == 'pronta entrega'){
            $pedidosQueryNasajon = PedidosVendaNasajon::with(['cliente_detalhes', 'forma_pagamento'])
            ->where(function($query){
                $query->orWhereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA']);
                $query->orWhere(['grupodeoperacao' => NULL]);
            });
            
            if(!empty($vendedores)){
                $pedidosQueryNasajon->whereIn('vendedor_codigo', $vendedores);
            }
    
            if ($fields['periodo'] == 'dia'){
                $pedidosQueryNasajon->where('emissao', date('Y-m-d'));
            }
            else if ($fields['periodo'] == 'mes'){
                $pedidosQueryNasajon->whereYear('emissao',date('Y'))
                ->whereMonth('emissao',date('m'));
            }
            else if ($fields['periodo'] == 'ano'){
                $pedidosQueryNasajon->whereYear('emissao',date('Y'));
            }

            $pedidosObjNasajon = $pedidosQueryNasajon->get();
            foreach ($pedidosObjNasajon as $value){
                $return[] = [
                    'estabelecimento' => $estabelecimentos[(int) $value->estabelecimento_codigo],
                    'pedido' => utf8_encode($value->numero),
                    'pedido_number' => utf8_encode($value->id),
                    'cliente' => utf8_encode($value->cliente_detalhes->nome),
                    'emissao' => date("d/m/Y", strtotime($value->emissao)),
                    'valor' => parserValor($value->valor),
                    'condicao_pagamento' => empty($value->forma_pagamento)? '':$value->forma_pagamento->formapagamento_descricao,
                    'status' => $value->situacao_descricao,
                    'origem' =>'nasajon',
                    'cliente_codigo' =>$value->cliente_detalhes->codigo,
                ];
            }
            $total = $pedidosObjNasajon->sum('valor');
        }
        else if($fields['venda'] == 'programada'){
            $pedidosQuery = PedidoPortal::with(['cliente', 'condicao_pagamento_detalhes', 'status_pedido_detalhes'])
                ->where('pedido_futuro', true)
                ->where('status_pedido', '8');

            if(!empty($vendedores)){
                $pedidosQuery->whereIn('usuario', $vendedores);
            }
    
            if ($fields['periodo'] == 'dia'){
                $pedidosQuery->where('data_pedido', date('Y-m-d'));
            }
            else if ($fields['periodo'] == 'mes'){
                $pedidosQuery->whereYear('data_pedido',date('Y'))
                ->whereMonth('data_pedido',date('m'));
            }
            else if ($fields['periodo'] == 'ano'){
                $pedidosQuery->whereYear('data_pedido',date('Y'));
            }

            $pedidosObj = $pedidosQuery->get();

            foreach ($pedidosObj as $value){
                $return[] = [
                    'estabelecimento' => $estabelecimentos[(int) $value->estabelecimento],
                    'pedido' => utf8_encode($value->id),
                    'pedido_number' => utf8_encode($value->id),
                    'cliente' => empty($value->cliente)? '' : utf8_encode($value->cliente->nome),
                    'emissao' => date("d/m/Y", strtotime($value->data_pedido)),
                    'valor' => parserValor($value->valor_total_nota),
                    'condicao_pagamento' => utf8_decode(utf8_encode($value->condicao_pagamento_detalhes->descricao)),
                    'status' => $value->status_pedido_detalhes->status,
                    'origem' =>'portal',
                    'cliente_codigo' => empty($value->cliente)? '' : $value->cliente->codigo,
                ];
            }

            $total = $pedidosObj->sum('valor_total_nota');
        }

        return view('programs.pedidos_orcamentos.dialog')->with("dados", $return)->with("total", parserValor($total));

    }

    static function resetaBusca(){

        $subordinados = UserController::varreSubordinados(Auth::id());

        if(strtolower(Auth::user()->tipo_usuario->nome) == "diretor"){
            $userObj = User::with('tipo_usuario')->get();
        }
        else{
            $userObj = User::with('tipo_usuario')->whereIn('id', $subordinados)->get(); 
        }
        
        $userArray = [];
        $gerentes = [];
        $supervisores = [];
        
        foreach ($userObj as $key => $value){

            if(!is_null($value->codigo_representante) && (strtolower($value->tipo_usuario['nome']) === "representante" || strtolower($value->tipo_usuario['nome']) === "vendedor_interno") ) {
                $return['vendedor_representante'][Crypt::encrypt($value->id)] = $value->name; 
            }

            if(strtolower($value->tipo_usuario['nome']) === "gerente"){
                $return['gerentes'][Crypt::encrypt($value->id)] = $value->name;
            }

            else if(strtolower($value->tipo_usuario['nome']) === "supervisor"){
                $return['supervisores'][Crypt::encrypt($value->id)] = $value->name;
            }
        
        }


        return response()->json($return, 200);

    }
}
