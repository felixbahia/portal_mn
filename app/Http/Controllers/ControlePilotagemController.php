<?php

namespace App\Http\Controllers;

use Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

use App\User;
use App\Movimentacao;
use App\ClienteNasajon;
use App\GrupoEmpresarial;
use App\NotasNasajon;
use App\PedidosVendaNasajon;
use App\PedidoPortal;
use App\LancamentoDebCredVendedor;

use App\Http\Controllers\UnidadeNegocioMetaController;

use App\Http\Requests\ControlePilotagemConsultaRequest;

use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class ControlePilotagemController extends Controller
{
    private $limite_credito_pilotagem = 300000;
    private $limite_credito_pilotagem_teto = 600000;
    private $limite_credito_pilotagem_porcentagem = 0.05;

    public function __construct() {
        $this->middleware(['auth']);
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ControlePilotagemController") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ControlePilotagemController');

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
                }else if(strtolower($user->tipo_usuario->nome) === "vendedor interno" || strtolower($user->tipo_usuario->nome) === "representante"){
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
            'gerentes'                      => $gerentes,
            'check_gerentes'                => $check_gerentes,
            'supervisores'                  => $supervisores,
            'check_supervisores'            => $check_supervisores,
            'vendedor_representante'        => $vendedor_representante,
            'check_vendedor_representante'  => $check_vendedor_representante,
        ];


        return view("programs.controle_pilotagem.index")->with($variaveis);
    }

    public function filter(ControlePilotagemConsultaRequest $request){
        set_time_limit(300);
        $fields = $request->only(['gerentes','vendedor_representante', 'cliente_nome', 'produto', 'grupo', 'nome', 'marca', 'linha', 'data_inicio', 'data_fim']);

        $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
        $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');
        
        $produtos_pilotagem = Movimentacao::whereIn('cfop',['5911','6911'])
        ->whereBetween('data_movimentacao',[$data_inicio,$data_fim])
        ->where('sinal','SAIDA');
        $users = [];
        
        if(in_array(Auth::user()->tipo_usuario->nome, ['Vendedor Interno', 'Representante'])){
            $users = [Auth::user()->codigo_representante];
        }else{
            if(isset($fields['gerentes']) && 
                !is_null($fields['gerentes']) &&
                (!isset($fields['vendedor_representante']) ||
                empty($fields['vendedor_representante']) )
            ){
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
                    $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial')->orWhere('nome', 'ilike', 'Diretor Comercial'); })->get()->pluck('id'); 
                    
                    $representantes = User::with(['tipo_usuario'])
                    ->whereNotNull('codigo_representante')
                    ->whereNotIn('responsavel', $gerentes)
                    ->orWhereDoesntHave('tipo_usuario', function($query){
                        $query->where('nivel', 3);
                    })
                    ->get();

                    $representantes = $representantes->whereNotIn('responsavel',$gerentes)->pluck('id');

                    $users = User::whereIn('id', $representantes)->get()->pluck('codigo_representante')->filter()->toArray();
                }else{
                    $subordinados = UserController::varreSubordinados($gerente);
                    $users = User::whereIn('id', $subordinados)->get()->pluck('codigo_representante')->filter()->toArray();
                }

            }else if((isset($fields['vendedor_representante']) &&
                     !empty($fields['vendedor_representante']))
            ){
                try{
                    $usuario = decrypt($fields['vendedor_representante']);
                }catch(Exception $e){
                    $return = [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                        'error' => '', 
                        'response' => '',
                    ];
                    return response()->json($return);
                }
                $users = [User::find($usuario)->codigo_representante];
            }
        }

        if(isset($fields['cliente_nome']) && !empty($fields['cliente_nome'])){

            $clienteObj = ClienteNasajon::where(DB::Raw('CONCAT(TRIM(nome), \' - \', cpf_cnpj)'),'ilike',$fields['cliente_nome'])->first();

            if(!is_null($clienteObj)){
                if(strlen($clienteObj->cpf_cnpj) == 18){
                    $cpf_cnpj = substr($clienteObj->cpf_cnpj, 0, 10);
                }
                else{
                    $cpf_cnpj = $clienteObj->cpf_cnpj;
                }

                $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
                ->where('raiz_cnpj', $cpf_cnpj)
                ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                    $query->where('raiz_cnpj', $cpf_cnpj);
                })
                ->first();
            }else{
                $return = [
                    "status" => 'error',
                    "message" => 'Cliente não encontrado',
                    "error" => ['cliente' => 'Cliente não encontrado'],
                    'response' => []
                ];
        
                return response()->json($return, 422);
            }

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
            }
            else {
                $clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
            }

            $clientesNasajonObj = $clientesNasajonQuery->orderBy('cpf_cnpj')
                ->groupBy('nome', 'codigo', 'cpf_cnpj', 'id')
                ->get();

            $produtos_pilotagem->whereIn('cliente_codigo', $clientesNasajonObj->pluck('codigo'));

            unset($clientesNasajonObj, $clientesNasajonQuery, $clienteObj);
        }

        if(isset($fields['produto']) && !empty($fields['produto'])){
            $produtos_pilotagem->where('produto_codigo', $fields['produto']);
        }
        if(isset($fields['grupo']) && !empty($fields['grupo'])){
            $produtos_pilotagem->where('grupo','ilike',$fields['grupo']);
        }
        if(isset($fields['nome']) && !empty($fields['nome'])){
            $produtos_pilotagem->where('descricao','ilike','%'.$fields['nome'].'%');
        }
        if(isset($fields['marca']) && !empty($fields['marca'])){
            $produtos_pilotagem->where('marca','ilike',$fields['marca']);
        }
        if(isset($fields['linha']) && !empty($fields['linha'])){
            $produtos_pilotagem->where('linha','ilike',$fields['linha']);
        }

        if(!empty($users)){
            $produtos_pilotagem->where(function ($query) use ($users){
                $query->whereIn('vendedor',$users)
                ->orWhereNull('vendedor');
            });
        }

        $produtos_pilotagem = $produtos_pilotagem->get();


        $estabelecimentos = returnEmpresasNasajonView();
        $produtos = $produtos_pilotagem->pluck('produto_codigo')->unique();
        $clientes = $produtos_pilotagem->pluck('cliente_codigo')->unique();
        $estabelecimentos_palitagem = $produtos_pilotagem->pluck('estabelecimento')->unique();
        $produtos_venda_query = Movimentacao::whereNotIn('cfop',['5911','6911'])
        ->where('data_movimentacao','>',$data_inicio)
        ->whereIn('produto_codigo',$produtos)
        ->whereIn('cliente_codigo',$clientes)
        ->whereIn('estabelecimento',$estabelecimentos_palitagem)
        ->where('sinal','SAIDA')
        ->get();
        $dados = [];
        $retorno = [];
        $total = [
            'clientes' => 0,
            'representantes' => 0,
            'gerentes' => 0,
            'produtos_pilotagem' => 0,
            'produtos_venda' => 0,
            'efetividade' => 0,
            'filtro' => ''
        ];

        foreach($produtos_pilotagem as $palitagem){
            if(!isset($dados[$palitagem->estabelecimento])){
                $dados[$palitagem->estabelecimento] = [
                    'estabelecimento' => $palitagem->estabelecimento,
                    'clientes' => [],
                    'produtos_pilotagem' => 0,
                    'produtos_venda' => 0,
                    'efetividade' => 0
                ];
            }
            $produtos_venda = $produtos_venda_query->where('produto_codigo',$palitagem->produto_codigo)
            ->where('cliente_codigo',$palitagem->cliente_codigo)
            ->where('data_movimentacao','>',$palitagem->data_movimentacao)
            ->where('estabelecimento',$palitagem->estabelecimento)
            ->count();

            $dados[$palitagem->estabelecimento]['clientes'][] = $palitagem->cliente_codigo;
            $dados[$palitagem->estabelecimento]['produtos_pilotagem'] += (!empty($palitagem->produto_codigo)) ? 1 : 0;
            $dados[$palitagem->estabelecimento]['produtos_venda'] += (!empty($produtos_venda)) ? $produtos_venda : 0;
        }

        unset($produtos_pilotagem);

        foreach($dados as $dado){
            $array_cliente = array_unique($dado['clientes']);
            $retorno[] = [
                'estabelecimento' => $estabelecimentos[(integer)$dado['estabelecimento']],
                'clientes' => (count($array_cliente) > 0) ? count($array_cliente) : '',
                'produtos_pilotagem' => ($dado['produtos_pilotagem'] > 0) ? $dado['produtos_pilotagem'] : '',
                'produtos_venda' => ($dado['produtos_venda'] > 0) ? $dado['produtos_venda'] : '',
                'efetividade' => ($dado['produtos_pilotagem'] > 0 && $dado['produtos_venda'] > 0) ? parserValor(($dado['produtos_venda'] / $dado['produtos_pilotagem']) * 100) : '',
                'filtro' => encrypt([
                    'gerentes' => $fields['gerentes'],
                    'vendedor_representante' => $fields['vendedor_representante'], 
                    'cliente_nome' => $fields['cliente_nome'], 
                    'produto' => $fields['produto'], 
                    'data_inicio' => $fields['data_inicio'], 
                    'data_fim' => $fields['data_fim'],
                    'grupo' => $fields['grupo'],
                    'nome' => $fields['nome'],
                    'marca' => $fields['marca'],
                    'linha' => $fields['linha'],
                    'estabelecimento' => $dado['estabelecimento'],
                ])
            ];
            $total['clientes'] += count($array_cliente);
            $total['produtos_pilotagem'] += $dado['produtos_pilotagem'];
            $total['produtos_venda'] += $dado['produtos_venda'];
            $total['efetividade'] += ($dado['produtos_pilotagem'] > 0) ? (($dado['produtos_venda'] / $dado['produtos_pilotagem']) * 100) : 0;
            unset($gerentes);
        }

        sort($retorno);
        $total['clientes'] = ($total['clientes'] > 0) ? $total['clientes'] : '';
        $total['filtro'] = encrypt([
            'gerentes' => $fields['gerentes'],
            'vendedor_representante' => $fields['vendedor_representante'], 
            'cliente_nome' => $fields['cliente_nome'], 
            'produto' => $fields['produto'], 
            'data_inicio' => $fields['data_inicio'], 
            'data_fim' => $fields['data_fim'],
            'grupo' => $fields['grupo'],
            'nome' => $fields['nome'],
            'marca' => $fields['marca'],
            'linha' => $fields['linha']
        ]);

        $total['efetividade'] = ($total['produtos_pilotagem'] > 0 && $total['produtos_venda'] > 0) ? parserValor($total['produtos_venda'] / $total['produtos_pilotagem'] * 100) : '';
        $total['produtos_pilotagem'] = ($total['produtos_pilotagem'] > 0) ? $total['produtos_pilotagem'] : '';
        $total['produtos_venda'] = ($total['produtos_venda'] > 0) ? $total['produtos_venda'] : '';
       
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['response' => $retorno, 'total' => $total],
        ]);
    }

    public function aberturaProduto(Request $request){
        set_time_limit(300);
        $filters = $request->only(['filtro','total','cliente_total','cliente','produto_total','produto']);

        try{
            $fields = decrypt($filters['filtro']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
        $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');
        $produtos_pilotagem = Movimentacao::whereIn('cfop',['5911','6911'])
        ->where('sinal','SAIDA')
        ->with('cliente','detalhesVendedor','produto')
        ->whereBetween('data_movimentacao',[$data_inicio,$data_fim]);
        if($filters['total'] == 'false' && $fields['estabelecimento'] != null){
            $produtos_pilotagem->where('estabelecimento',$fields['estabelecimento']);
        }else if(isset($filters['cliente'])){
            if($filters['cliente_total'] == null){
                $produtos_pilotagem->where('estabelecimento',$fields['estabelecimento']);
            }
        }else if(isset($filters['produto'])){
            if($filters['produto_total'] == null){
                $produtos_pilotagem->where('estabelecimento',$fields['estabelecimento']);
            }
        }

        if(isset($fields['cliente_codigo']) && $filters['total'] == 'false'){
            $produtos_pilotagem->where('cliente_codigo',$fields['cliente_codigo']);
        }

        if(isset($fields['produto_codigo']) && $filters['total'] == 'false'){
            $produtos_pilotagem->where('produto_codigo',$fields['produto_codigo']);
        }

        $users = [];

        if(in_array(Auth::user()->tipo_usuario->nome, ['Vendedor Interno', 'Representante'])){
            $users = [Auth::user()->codigo_representante];
        }else{
            if(isset($fields['gerentes']) && 
                !is_null($fields['gerentes']) &&
                (!isset($fields['vendedor_representante']) ||
                empty($fields['vendedor_representante']) )
            ){
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
                    $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial')->orWhere('nome', 'ilike', 'Diretor Comercial'); })->get()->pluck('id'); 

                    $representantes = User::with(['tipo_usuario'])
                    ->whereNotNull('codigo_representante')
                    ->whereNotIn('responsavel', $gerentes)
                    ->orWhereDoesntHave('tipo_usuario', function($query){
                        $query->where('nivel', 3);
                    })
                    ->get();

                    $representantes = $representantes->whereNotIn('responsavel',$gerentes)->pluck('id');
                    
                    $users = User::whereIn('id', $representantes)->get()->pluck('codigo_representante')->filter()->toArray();
                }else{
                    $subordinados = UserController::varreSubordinados($gerente);
                    $users = User::whereIn('id', $subordinados)->get()->pluck('codigo_representante')->filter()->toArray();
                }
            }else if((isset($fields['vendedor_representante']) &&
                     !empty($fields['vendedor_representante']))
            ){
                try{
                    $usuario = decrypt($fields['vendedor_representante']);
                }catch(Exception $e){
                    $return = [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                        'error' => '', 
                        'response' => '',
                    ];
                    return response()->json($return);
                }
                $users = [User::find($usuario)->codigo_representante];
            }
        }

        if(isset($fields['cliente_nome']) && !empty($fields['cliente_nome'])){

            $clienteObj = ClienteNasajon::where(DB::Raw('CONCAT(TRIM(nome), \' - \', cpf_cnpj)'),'ilike',$fields['cliente_nome'])->first();

            if(!is_null($clienteObj)){
                if(strlen($clienteObj->cpf_cnpj) == 18){
                    $cpf_cnpj = substr($clienteObj->cpf_cnpj, 0, 10);
                }
                else{
                    $cpf_cnpj = $clienteObj->cpf_cnpj;
                }

                $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
                ->where('raiz_cnpj', $cpf_cnpj)
                ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                    $query->where('raiz_cnpj', $cpf_cnpj);
                })
                ->first();
            }else{
                $return = [
                    "status" => 'error',
                    "message" => 'Cliente não encontrado',
                    "error" => ['cliente' => 'Cliente não encontrado'],
                    'response' => []
                ];
        
                return response()->json($return, 422);
            }

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
            }
            else {
                $clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
            }

            $clientesNasajonObj = $clientesNasajonQuery->orderBy('cpf_cnpj')
                ->groupBy('nome', 'codigo', 'cpf_cnpj', 'id')
                ->get();

            $produtos_pilotagem->whereIn('cliente_codigo', $clientesNasajonObj->pluck('codigo'));

            unset($clientesNasajonObj, $clientesNasajonQuery, $clienteObj);
        }

        if(isset($fields['produto']) && !empty($fields['produto'])){
            $produtos_pilotagem->where('produto_codigo', $fields['produto']);
        }
        if(isset($fields['grupo']) && !empty($fields['grupo'])){
            $produtos_pilotagem->where('grupo','ilike',$fields['grupo']);
        }
        if(isset($fields['nome']) && !empty($fields['nome'])){
            $produtos_pilotagem->where('descricao','ilike','%'.$fields['nome'].'%');
        }
        if(isset($fields['marca']) && !empty($fields['marca'])){
            $produtos_pilotagem->where('marca','ilike',$fields['marca']);
        }
        if(isset($fields['linha']) && !empty($fields['linha'])){
            $produtos_pilotagem->where('linha','ilike',$fields['linha']);
        }

        if(!empty($users)){
            $produtos_pilotagem->where(function ($query) use ($users){
                $query->whereIn('vendedor',$users)
                ->orWhereNull('vendedor');
            });
        }

        $produtos_pilotagem = $produtos_pilotagem->get();

        $produtos = $produtos_pilotagem->pluck('produto_codigo')->unique();
        $clientes = $produtos_pilotagem->pluck('cliente_codigo')->unique();
        $documento = $produtos_pilotagem->pluck('documento')->unique();
        $estabelecimentos_palitagem = $produtos_pilotagem->pluck('estabelecimento')->unique();
        $notas_nasajon = NotasNasajon::whereIn('numero',$documento)->whereIn('estabelecimento_codigo',$estabelecimentos_palitagem)->get();
        $produtos_venda_query = Movimentacao::whereNotIn('cfop',['5911','6911'])
        ->where('data_movimentacao','>',$data_inicio)
        ->whereIn('produto_codigo',$produtos)
        ->whereIn('cliente_codigo',$clientes)
        ->whereIn('estabelecimento',$estabelecimentos_palitagem)
        ->where('sinal','SAIDA')
        ->get();

        $retorno = [];
        $dados = [];
        $total = [
            'quantidade_vendas' => 0,
            'quantidade_produtos' => 0,
        ];

        foreach($produtos_pilotagem as $pilotagem){
            $gerente = (isset($pilotagem->detalhesVendedor->responsavel)) ? User::where('id',$pilotagem->detalhesVendedor->responsavel)->first()->name : '';
            $produto = (isset($pilotagem->produto->descricao)) ? $pilotagem->produto->descricao.' - '.$pilotagem->produto->codigo_produto : '';
            $cliente = (isset($pilotagem->cliente->nome)) ? $pilotagem->cliente->nome.' - '.$pilotagem->cliente->cpf_cnpj : '';
            $representante = (isset($pilotagem->detalhesVendedor->name)) ? $pilotagem->vendedor.' - '.$pilotagem->detalhesVendedor->name : '';
            $chave = $produto.$cliente.$pilotagem->documento;
            $id_nota = $notas_nasajon->where('numero',$pilotagem->documento)->where('estabelecimento_codigo',$pilotagem->estabelecimento)->first();
            $produtos_venda = $produtos_venda_query->where('produto_codigo',$pilotagem->produto_codigo)
            ->where('cliente_codigo',$pilotagem->cliente_codigo)
            ->where('data_movimentacao','>',$pilotagem->data_movimentacao)
            ->where('estabelecimento',$pilotagem->estabelecimento)
            ->count();

            if(!isset($dados[$chave])){
                $dados[$chave] = [
                    'cliente' => $cliente,
                    'produto' => $produto,
                    'representante' => $representante,
                    'gerente' => $gerente,
                    'quantidade_produto' => 0,
                    'quantidade_vendas' => 0,
                    'documento' => $pilotagem->documento,
                    'id_nota' => (!empty($id_nota)) ? $id_nota->id : ''
                ];
            }

            $dados[$chave]['quantidade_produto'] += (!empty($pilotagem->produto_codigo)) ? 1 : 0;
            $dados[$chave]['quantidade_vendas'] += (!empty($produtos_venda)) ? $produtos_venda : 0;
            
            $total['quantidade_produtos'] += (!empty($pilotagem->produto_codigo)) ? 1 : 0;
            $total['quantidade_vendas'] += (!empty($produtos_venda)) ? $produtos_venda : 0;
        }

        foreach($dados as $dado){
            $retorno[] = [
                'cliente' => $dado['cliente'],
                'produto' => $dado['produto'],
                'representante' => $dado['representante'],
                'gerente' => $dado['gerente'],
                'quantidade_produto' => ($dado['quantidade_produto'] > 0) ? $dado['quantidade_produto'] : '',
                'quantidade_vendas' => ($dado['quantidade_vendas'] > 0) ? $dado['quantidade_vendas'] : '',
                'id_nota' => $dado['id_nota'],
                'documento' => $dado['documento']
            ];
        }

        unset($produtos_pilotagem);

        $total['quantidade_produtos'] = ($total['quantidade_produtos'] > 0) ? $total['quantidade_produtos'] : '';
        $total['quantidade_vendas'] = ($total['quantidade_vendas'] > 0) ? $total['quantidade_vendas'] : '';

        return view('programs.controle_pilotagem.modal.produto')->with(['total' => $total, 'response' => $retorno]);
    }

    public function aberturaCliente(Request $request){
        set_time_limit(300);
        $filters = $request->only(['filtro','total']);

        try{
            $fields = decrypt($filters['filtro']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
        $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');
        $total_filtro = false;
        
        $produtos_pilotagem = Movimentacao::whereIn('cfop',['5911','6911'])
        ->where('sinal','SAIDA')
        ->with('cliente')
        ->whereBetween('data_movimentacao',[$data_inicio,$data_fim]);
        if($filters['total'] == 'false'){
            $produtos_pilotagem->where('estabelecimento',$fields['estabelecimento']);
        }else{
            $total_filtro = true;
        }
        $users = [];

        if(in_array(Auth::user()->tipo_usuario->nome, ['Vendedor Interno', 'Representante'])){
            $users = [Auth::user()->codigo_representante];
        }else{
            if(isset($fields['gerentes']) && 
                !is_null($fields['gerentes']) &&
                (!isset($fields['vendedor_representante']) ||
                empty($fields['vendedor_representante']) )
            ){
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
                    $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial')->orWhere('nome', 'ilike', 'Diretor Comercial'); })->get()->pluck('id'); 

                    $representantes = User::with(['tipo_usuario'])
                    ->whereNotNull('codigo_representante')
                    ->whereNotIn('responsavel', $gerentes)
                    ->orWhereDoesntHave('tipo_usuario', function($query){
                        $query->where('nivel', 3);
                    })
                    ->get();

                    $representantes = $representantes->whereNotIn('responsavel',$gerentes)->pluck('id');

                    $users = User::whereIn('id', $representantes)->get()->pluck('codigo_representante')->filter()->toArray();
                }else{
                    $subordinados = UserController::varreSubordinados($gerente);
                    $users = User::whereIn('id', $subordinados)->get()->pluck('codigo_representante')->filter()->toArray();
                }
            }else if((isset($fields['vendedor_representante']) &&
                     !empty($fields['vendedor_representante']))
            ){
                try{
                    $usuario = decrypt($fields['vendedor_representante']);
                }catch(Exception $e){
                    $return = [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                        'error' => '', 
                        'response' => '',
                    ];
                    return response()->json($return);
                }
                $users = [User::find($usuario)->codigo_representante];
            }
        }

        if(isset($fields['cliente_nome']) && !empty($fields['cliente_nome'])){

            $clienteObj = ClienteNasajon::where(DB::Raw('CONCAT(TRIM(nome), \' - \', cpf_cnpj)'),'ilike',$fields['cliente_nome'])->first();

            if(!is_null($clienteObj)){
                if(strlen($clienteObj->cpf_cnpj) == 18){
                    $cpf_cnpj = substr($clienteObj->cpf_cnpj, 0, 10);
                }
                else{
                    $cpf_cnpj = $clienteObj->cpf_cnpj;
                }

                $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
                ->where('raiz_cnpj', $cpf_cnpj)
                ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                    $query->where('raiz_cnpj', $cpf_cnpj);
                })
                ->first();
            }else{
                $return = [
                    "status" => 'error',
                    "message" => 'Cliente não encontrado',
                    "error" => ['cliente' => 'Cliente não encontrado'],
                    'response' => []
                ];
        
                return response()->json($return, 422);
            }

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
            }
            else {
                $clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
            }

            $clientesNasajonObj = $clientesNasajonQuery->orderBy('cpf_cnpj')
                ->groupBy('nome', 'codigo', 'cpf_cnpj', 'id')
                ->get();

            $produtos_pilotagem->whereIn('cliente_codigo', $clientesNasajonObj->pluck('codigo'));

            unset($clientesNasajonObj, $clientesNasajonQuery, $clienteObj);
        }

        if(isset($fields['produto']) && !empty($fields['produto'])){
            $produtos_pilotagem->where('produto_codigo', $fields['produto']);
        }
        if(isset($fields['grupo']) && !empty($fields['grupo'])){
            $produtos_pilotagem->where('grupo','ilike',$fields['grupo']);
        }
        if(isset($fields['nome']) && !empty($fields['nome'])){
            $produtos_pilotagem->where('descricao','ilike','%'.$fields['nome'].'%');
        }
        if(isset($fields['marca']) && !empty($fields['marca'])){
            $produtos_pilotagem->where('marca','ilike',$fields['marca']);
        }
        if(isset($fields['linha']) && !empty($fields['linha'])){
            $produtos_pilotagem->where('linha','ilike',$fields['linha']);
        }

        if(!empty($users)){
            $produtos_pilotagem->where(function ($query) use ($users){
                $query->whereIn('vendedor',$users)
                ->orWhereNull('vendedor');
            });
        }

        $produtos_pilotagem = $produtos_pilotagem->get();

        $produtos = $produtos_pilotagem->pluck('produto_codigo')->unique();
        $clientes = $produtos_pilotagem->pluck('cliente_codigo')->unique();
        $estabelecimentos_palitagem = $produtos_pilotagem->pluck('estabelecimento')->unique();
        $produtos_venda_query = Movimentacao::whereNotIn('cfop',['5911','6911'])
        ->where('data_movimentacao','>',$data_inicio)
        ->whereIn('produto_codigo',$produtos)
        ->whereIn('cliente_codigo',$clientes)
        ->whereIn('estabelecimento',$estabelecimentos_palitagem)
        ->where('sinal','SAIDA')
        ->get();

        $retorno = [];
        $dados = [];
        $total = [
            'representantes' => 0,
            'gerentes' => 0,
            'produtos_pilotagem' => 0,
            'produtos_venda' => 0,
            'efetividade' => 0,
            'filtro' => '',
        ];

        foreach($produtos_pilotagem as $pilotagem){
            $cliente = (isset($pilotagem->cliente->nome)) ? $pilotagem->cliente->nome.' - '.$pilotagem->cliente->cpf_cnpj : '';
            if(!isset($dados[$pilotagem->cliente_codigo])){
                $dados[$pilotagem->cliente_codigo] = [
                    'cliente' => $cliente,
                    'produtos_pilotagem' => 0,
                    'produtos_venda' => 0,
                    'cliente_codigo' => $pilotagem->cliente_codigo,
                    'filtro' => encrypt([
                        'gerentes' => $fields['gerentes'],
                        'vendedor_representante' => $fields['vendedor_representante'], 
                        'cliente_nome' => $fields['cliente_nome'], 
                        'cliente_codigo' => $pilotagem->cliente_codigo,
                        'produto' => $fields['produto'], 
                        'data_inicio' => $fields['data_inicio'], 
                        'data_fim' => $fields['data_fim'],
                        'grupo' => $fields['grupo'],
                        'nome' => $fields['nome'],
                        'marca' => $fields['marca'],
                        'linha' => $fields['linha'],
                        'estabelecimento' => (isset($fields['estabelecimento'])) ? $fields['estabelecimento'] : null,
                    ])
                ];
            }
            $produtos_venda = $produtos_venda_query->where('produto_codigo',$pilotagem->produto_codigo)
            ->where('cliente_codigo',$pilotagem->cliente_codigo)
            ->where('data_movimentacao','>',$pilotagem->data_movimentacao)
            ->where('estabelecimento',$pilotagem->estabelecimento)
            ->count();

            $dados[$pilotagem->cliente_codigo]['produtos_pilotagem'] += (!empty($pilotagem->produto_codigo)) ? 1 : 0;
            $dados[$pilotagem->cliente_codigo]['produtos_venda'] += (!empty($produtos_venda)) ? $produtos_venda : 0;
        }
        
        unset($produtos_pilotagem);

        foreach($dados as $dado){

            $retorno[] = [
                'cliente' => $dado['cliente'],
                'produtos_pilotagem' => ($dado['produtos_pilotagem'] > 0) ? $dado['produtos_pilotagem'] : '',
                'produtos_venda' => ($dado['produtos_venda'] > 0) ? $dado['produtos_venda'] : '',
                'efetividade' => ($dado['produtos_pilotagem'] > 0 && $dado['produtos_venda'] > 0) ? parserValor(($dado['produtos_venda'] / $dado['produtos_pilotagem']) * 100) : '',
                'filtro' => $dado['filtro'],
                
            ];
            $total['filtro'] = encrypt([
                'gerentes' => $fields['gerentes'],
                'vendedor_representante' => $fields['vendedor_representante'], 
                'cliente_nome' => $fields['cliente_nome'], 
                'cliente_codigo' => $pilotagem->cliente_codigo,
                'produto' => $fields['produto'], 
                'data_inicio' => $fields['data_inicio'], 
                'data_fim' => $fields['data_fim'],
                'grupo' => $fields['grupo'],
                'nome' => $fields['nome'],
                'marca' => $fields['marca'],
                'linha' => $fields['linha'],
                'estabelecimento' => (isset($fields['estabelecimento'])) ? $fields['estabelecimento'] : null,
            ]);
            $total['produtos_pilotagem'] += $dado['produtos_pilotagem'];
            $total['produtos_venda'] += $dado['produtos_venda'];
            $total['efetividade'] += ($dado['produtos_pilotagem'] > 0) ? (($dado['produtos_venda'] / $dado['produtos_pilotagem']) * 100) : 0;

        }

        $total['efetividade'] = ($total['produtos_pilotagem'] > 0 && $total['produtos_venda'] > 0) ? parserValor($total['produtos_venda'] / $total['produtos_pilotagem'] * 100) : '';
        $total['produtos_pilotagem'] = ($total['produtos_pilotagem'] > 0) ? $total['produtos_pilotagem'] : '';
        $total['produtos_venda'] = ($total['produtos_venda'] > 0) ? $total['produtos_venda'] : '';
        
        return view('programs.controle_pilotagem.modal.cliente')->with(['total' => $total, 'response' => $retorno, 'total_filtro' => $total_filtro]);
    }

    public function aberturaProdutoAcumulado(Request $request){
        set_time_limit(300);
        $filters = $request->only(['filtro','total']);
        $total_filtro = false;

        try{
            $fields = decrypt($filters['filtro']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
        $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');
        
        $produtos_pilotagem = Movimentacao::whereIn('cfop',['5911','6911'])
        ->where('sinal','SAIDA')
        ->with('produto')
        ->whereBetween('data_movimentacao',[$data_inicio,$data_fim]);
        if($filters['total'] == 'false'){
            $produtos_pilotagem->where('estabelecimento',$fields['estabelecimento']);
        }else{
            $total_filtro = true;
        }
        $users = [];

        if(in_array(Auth::user()->tipo_usuario->nome, ['Vendedor Interno', 'Representante'])){
            $users = [Auth::user()->codigo_representante];
        }else{
            if(isset($fields['gerentes']) && 
                !is_null($fields['gerentes']) &&
                (!isset($fields['vendedor_representante']) ||
                empty($fields['vendedor_representante']) )
            ){
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
                    $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial')->orWhere('nome', 'ilike', 'Diretor Comercial'); })->get()->pluck('id'); 

                    $representantes = User::with(['tipo_usuario'])
                    ->whereNotNull('codigo_representante')
                    ->whereNotIn('responsavel', $gerentes)
                    ->orWhereDoesntHave('tipo_usuario', function($query){
                        $query->where('nivel', 3);
                    })
                    ->get();

                    $representantes = $representantes->whereNotIn('responsavel',$gerentes)->pluck('id');

                    $users = User::whereIn('id', $representantes)->get()->pluck('codigo_representante')->filter()->toArray();
                }else{
                    $subordinados = UserController::varreSubordinados($gerente);
                    $users = User::whereIn('id', $subordinados)->get()->pluck('codigo_representante')->filter()->toArray();
                }
            }else if((isset($fields['vendedor_representante']) &&
                     !empty($fields['vendedor_representante']))
            ){
                try{
                    $usuario = decrypt($fields['vendedor_representante']);
                }catch(Exception $e){
                    $return = [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                        'error' => '', 
                        'response' => '',
                    ];
                    return response()->json($return);
                }
                $users = [User::find($usuario)->codigo_representante];
            }
        }

        if(isset($fields['cliente_nome']) && !empty($fields['cliente_nome'])){

            $clienteObj = ClienteNasajon::where(DB::Raw('CONCAT(TRIM(nome), \' - \', cpf_cnpj)'),'ilike',$fields['cliente_nome'])->first();

            if(!is_null($clienteObj)){
                if(strlen($clienteObj->cpf_cnpj) == 18){
                    $cpf_cnpj = substr($clienteObj->cpf_cnpj, 0, 10);
                }
                else{
                    $cpf_cnpj = $clienteObj->cpf_cnpj;
                }

                $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
                ->where('raiz_cnpj', $cpf_cnpj)
                ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                    $query->where('raiz_cnpj', $cpf_cnpj);
                })
                ->first();
            }else{
                $return = [
                    "status" => 'error',
                    "message" => 'Cliente não encontrado',
                    "error" => ['cliente' => 'Cliente não encontrado'],
                    'response' => []
                ];
        
                return response()->json($return, 422);
            }

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
            }
            else {
                $clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
            }

            $clientesNasajonObj = $clientesNasajonQuery->orderBy('cpf_cnpj')
                ->groupBy('nome', 'codigo', 'cpf_cnpj', 'id')
                ->get();

            $produtos_pilotagem->whereIn('cliente_codigo', $clientesNasajonObj->pluck('codigo'));

            unset($clientesNasajonObj, $clientesNasajonQuery, $clienteObj);
        }

        if(isset($fields['produto']) && !empty($fields['produto'])){
            $produtos_pilotagem->where('produto_codigo', $fields['produto']);
        }
        if(isset($fields['grupo']) && !empty($fields['grupo'])){
            $produtos_pilotagem->where('grupo','ilike',$fields['grupo']);
        }
        if(isset($fields['nome']) && !empty($fields['nome'])){
            $produtos_pilotagem->where('descricao','ilike','%'.$fields['nome'].'%');
        }
        if(isset($fields['marca']) && !empty($fields['marca'])){
            $produtos_pilotagem->where('marca','ilike',$fields['marca']);
        }
        if(isset($fields['linha']) && !empty($fields['linha'])){
            $produtos_pilotagem->where('linha','ilike',$fields['linha']);
        }

        if(!empty($users)){
            $produtos_pilotagem->where(function ($query) use ($users){
                $query->whereIn('vendedor',$users)
                ->orWhereNull('vendedor');
            });
        }

        $produtos_pilotagem = $produtos_pilotagem->get();

        $produtos = $produtos_pilotagem->pluck('produto_codigo')->unique();
        $clientes = $produtos_pilotagem->pluck('cliente_codigo')->unique();
        $estabelecimentos_palitagem = $produtos_pilotagem->pluck('estabelecimento')->unique();
        $produtos_venda_query = Movimentacao::whereNotIn('cfop',['5911','6911'])
        ->where('data_movimentacao','>',$data_inicio)
        ->whereIn('produto_codigo',$produtos)
        ->whereIn('cliente_codigo',$clientes)
        ->whereIn('estabelecimento',$estabelecimentos_palitagem)
        ->where('sinal','SAIDA')
        ->get();

        $retorno = [];
        $dados = [];
        $total = [
            'representantes' => 0,
            'gerentes' => 0,
            'produtos_pilotagem' => 0,
            'produtos_venda' => 0,
            'efetividade' => 0,
            'filtro' => ''
        ];

        foreach($produtos_pilotagem as $pilotagem){
            $produto = (isset($pilotagem->produto->descricao)) ? $pilotagem->produto->descricao.' - '.$pilotagem->produto->codigo_produto : '';
            if(!isset($dados[$produto])){
                $dados[$produto] = [
                    'produto' => $produto,
                    'produtos_pilotagem' => 0,
                    'produtos_venda' => 0,
                    'filtro' => encrypt([
                        'gerentes' => $fields['gerentes'],
                        'vendedor_representante' => $fields['vendedor_representante'], 
                        'cliente_nome' => $fields['cliente_nome'], 
                        'produto_codigo' => $pilotagem->produto_codigo,
                        'produto' => $fields['produto'], 
                        'data_inicio' => $fields['data_inicio'], 
                        'data_fim' => $fields['data_fim'],
                        'grupo' => $fields['grupo'],
                        'nome' => $fields['nome'],
                        'marca' => $fields['marca'],
                        'linha' => $fields['linha'],
                        'estabelecimento' => (isset($fields['estabelecimento'])) ? $fields['estabelecimento'] : null,
                    ])
                ];
            }
            $produtos_venda = $produtos_venda_query->where('produto_codigo',$pilotagem->produto_codigo)
            ->where('cliente_codigo',$pilotagem->cliente_codigo)
            ->where('data_movimentacao','>',$pilotagem->data_movimentacao)
            ->where('estabelecimento',$pilotagem->estabelecimento)
            ->count();

            $dados[$produto]['produtos_pilotagem'] += (!empty($pilotagem->quantidade)) ? 1 : 0;
            $dados[$produto]['produtos_venda'] += (!empty($produtos_venda)) ? $produtos_venda : 0;
        }
        
        unset($produtos_pilotagem);

        foreach($dados as $dado){
            $retorno[] = [
                'produto' => $dado['produto'],
                'produtos_pilotagem' => ($dado['produtos_pilotagem'] > 0) ? $dado['produtos_pilotagem'] : '',
                'produtos_venda' => ($dado['produtos_venda'] > 0) ? $dado['produtos_venda'] : '',
                'efetividade' => ($dado['produtos_pilotagem'] > 0 && $dado['produtos_venda'] > 0) ? parserValor(($dado['produtos_venda'] / $dado['produtos_pilotagem']) * 100) : '',
                'filtro' => $dado['filtro'],
                
            ];
            $total['produtos_pilotagem'] += $dado['produtos_pilotagem'];
            $total['produtos_venda'] += $dado['produtos_venda'];
            $total['efetividade'] += ($dado['produtos_pilotagem'] > 0) ? (($dado['produtos_venda'] / $dado['produtos_pilotagem']) * 100) : 0;
        }

        $total['efetividade'] = ($total['produtos_pilotagem'] > 0 && $total['produtos_venda'] > 0) ? parserValor($total['produtos_venda'] / $total['produtos_pilotagem'] * 100) : '';
        $total['produtos_pilotagem'] = ($total['produtos_pilotagem'] > 0) ? $total['produtos_pilotagem'] : '';
        $total['produtos_venda'] = ($total['produtos_venda'] > 0) ? $total['produtos_venda'] : '';
        $total['filtro'] = encrypt([
            'gerentes' => $fields['gerentes'],
            'vendedor_representante' => $fields['vendedor_representante'], 
            'cliente_nome' => $fields['cliente_nome'], 
            'produto_codigo' => $pilotagem->produto_codigo,
            'produto' => $fields['produto'], 
            'data_inicio' => $fields['data_inicio'], 
            'data_fim' => $fields['data_fim'],
            'grupo' => $fields['grupo'],
            'nome' => $fields['nome'],
            'marca' => $fields['marca'],
            'linha' => $fields['linha'],
            'estabelecimento' => (isset($fields['estabelecimento'])) ? $fields['estabelecimento'] : null,
        ]);

        return view('programs.controle_pilotagem.modal.produto_acumulado')->with(['total' => $total, 'response' => $retorno, 'total_filtro' => $total_filtro]);
    }

    public function descontoPilotagem(){
        $data = Carbon::now()->setTime(0,0,0);
        $UnidadeNegocioMetaControllerObj = new UnidadeNegocioMetaController();
        $PedidosVendaNasajonObj = PedidosVendaNasajon::with(['valorTotalFaturado', 'nota' => function($query){
                $query->where('estabelecimento_codigo', '!=', '20');
                $query->orderBy('estabelecimento_codigo');
            }])
            ->whereIn('operacao_codigo', ['PEDAMOSTRA', 'PEDAMOSTRAGRATIS'])
            ->whereBetween('emissao', [$data->copy()->format('Y-m-01'), $data->format('Y-m-d')])
            ->where('situacao_descricao', '=', 'Faturado')
            ->where('grupodeoperacao', '=', 'REMESSA')
            ->whereNotNull('vendedor_codigo')
            ->whereNotIn('vendedor_codigo', ['001'])
            ->orderBy('vendedor_codigo')
            ->get();
        $meta_negocio = $UnidadeNegocioMetaControllerObj->getVendedorMetaFaturamento($data->copy()->subMonth()->format('m/Y'), $PedidosVendaNasajonObj->pluck('vendedor_codigo')->unique()->toArray());
        $usuarios = User::whereIn('codigo_representante', $PedidosVendaNasajonObj->pluck('vendedor_codigo')->unique())->get();
        unset($UnidadeNegocioMetaControllerObj);
        $usuarios->each(function($usuario, $key) use ($meta_negocio, &$usuarios){
            $codigo_representante = $usuario->codigo_representante;
            $limite_valor = 0;
            if(isset($meta_negocio[$codigo_representante])){
                $meta_negocio_vendedor = reset($meta_negocio[$codigo_representante]);
                if(parserFloat10($meta_negocio_vendedor['valor']) > $this->limite_credito_pilotagem){
                    if(parserFloat10($meta_negocio_vendedor['valor']) > parserFloat10($this->limite_credito_pilotagem_teto)){
                        $limite_valor = parserFloat10($this->limite_credito_pilotagem_teto) * ($this->limite_credito_pilotagem_porcentagem / 100);
                    }else{
                        $limite_valor = parserFloat10($meta_negocio_vendedor['valor']) * ($this->limite_credito_pilotagem_porcentagem / 100);
                    }
                }
            }
            $usuarios[$key]->limite_valor = $limite_valor;
        });
        $PedidosVendaNasajonObj->each(function($pedido_venda) use($meta_negocio, $data, $usuarios){
            if(isset($pedido_venda->nota->id)){
                $LancamentoDebCredVendedorObjBusca = LancamentoDebCredVendedor::where('nota_uuid', $pedido_venda->nota->id);

                $meta = false;
                $codigo_representante = $pedido_venda->vendedor_codigo;
                $venvedor = $usuarios->firstWhere('codigo_representante', $codigo_representante);
                $limite_valor = $venvedor->limite_valor;
                if($limite_valor > 0){
                    $meta = true;
                }

                $valor_pedido = (float) $pedido_venda->valorTotalFaturado->total_faturado;
                if($LancamentoDebCredVendedorObjBusca->doesntExist()){
                    $LancamentoDebCredVendedorObj = new LancamentoDebCredVendedor;
                    $LancamentoDebCredVendedorObj->data_lancamento = $pedido_venda->nota->emissao;
                    $LancamentoDebCredVendedorObj->nota_uuid = $pedido_venda->nota->id;
                    $LancamentoDebCredVendedorObj->num_documento = '';
                    $LancamentoDebCredVendedorObj->codigo_vendedor = $venvedor->id;
                    $LancamentoDebCredVendedorObj->codigo_motivo = 1;
                    $LancamentoDebCredVendedorObj->tipo = "D";
                    $LancamentoDebCredVendedorObj->created_by = 1;
                    if($meta == true){
                        $saldo = $limite_valor - $valor_pedido;
                        $venvedor->limite_valor = $saldo;
                        if($saldo < 0){
                            $LancamentoDebCredVendedorObj->valor = (-1) * $saldo;
                            $LancamentoDebCredVendedorObj->save();
                        }
                    }else{
                        $LancamentoDebCredVendedorObj->valor = $valor_pedido;
                        $LancamentoDebCredVendedorObj->save();
                    }
                }else{
                    $venvedor->limite_valor = $limite_valor - $valor_pedido;
                }
            }
        });
        unset($meta_negocio);
        unset($PedidosVendaNasajonObj);
    }
}
