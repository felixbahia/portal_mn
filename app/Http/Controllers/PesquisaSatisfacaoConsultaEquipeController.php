<?php

namespace App\Http\Controllers;

use Auth;

use Carbon\Carbon;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Crypt;

use App\User;
use App\ClienteNasajon;
use App\PesquisaSatisfacaoCliente;

class PesquisaSatisfacaoConsultaEquipeController extends Controller
{
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\PesquisaSatisfacaoConsultaEquipes") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\PesquisaSatisfacaoConsultaEquipes');

        $gerentes = [];
        $supervisores = [];
        $vendedor_representante = [];

        $check_gerentes = false;
        $check_supervisores = false;
        $check_vendedor_representante = false;
        $tipo_usuario_id = Auth::user()->tipo_usuario_id;
        
        if(!in_array(Auth::user()->tipo_usuario->nome, ["Diretor", "Administrador", "Interno"])){
            $subordinadosObj = UserController::varreSubordinados(Auth::id());
            if(strtolower(Auth::user()->tipo_usuario->nome) === "supervisor"){
                $subordinadosObj = UserController::varreSubordinados(Auth::user()->responsavel);
            }
            $userObj = User::whereIn('id', $subordinadosObj)->get();
            $userObj = $userObj->sortBy('name');

            if($tipo_usuario_id == 19 && !empty(Auth::user()->codigo_representante)){
                $vendedor_representante[Crypt::encrypt(Auth::user()->id)] = strtoupper(Auth::user()->name);
            }

            foreach ($userObj as $user) {
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

        return view('programs.pesquisa_Satisfacao_consulta_equipes.index')->with($variaveis);
    }

    public function filtro(Request $request){
        ini_set('memory_limit','1024M');

        $campos = $request->only(["gerentes","vendedor_representante"]);
        
        $data_inicio = Carbon::now()->subMonths(12)->format('Y-m-d 00:00:00');
        $data_fim = Carbon::now()->format('Y-m-d 23:59:59');
        
        $users = [];
        $gerente = '';
        $id_nota_nasajon = [];
        $documento_clientes = [];
        $tipo_usuario_id = Auth::user()->tipo_usuario_id;

        $pesquisa_satisfacao = PesquisaSatisfacaoCliente::with(['clienteNotas','formulariosRespondidos' => function($query){
            $query->where('pesquisa_satisfacao_formulario_tipo_respostas_id','<>','2');
        }])
        ->whereHas('formulariosRespondidos')
        ->whereBetween('created_at',[$data_inicio,$data_fim])
        ->orderBy('created_at')
        ->get();

        $pesquisa_satisfacao->each(function($query) use (&$id_nota_nasajon,&$documento_clientes){
            $documento_clientes[] = $query->documento;
            foreach($query->clienteNotas as $notas){
                $id_nota_nasajon[] = $notas->nota_nasajon_id;
            }
        });

        if($tipo_usuario_id == 19){
            $campos['gerentes'] = Crypt::encrypt(Auth::user()->id);
        }

        if(in_array(Auth::user()->tipo_usuario->nome, ['Vendedor Interno', 'Representante'])){
            $users = [Auth::user()->codigo_representante];
        }else{

            if(((isset($campos['gerentes']) && !is_null($campos['gerentes'])) || in_array($tipo_usuario_id, [19, 13])) && (!isset($campos['vendedor_representante']) || empty($campos['vendedor_representante']))){

                if($tipo_usuario_id == 19){
                    $gerente = Auth::id();
                }else if($tipo_usuario_id == 13){
                    $gerente = Auth::user()->responsavel;
                }else{
                    $gerente = Crypt::decrypt($campos['gerentes']);
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
                }else{
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

            }else if((isset($campos['vendedor_representante']) && !empty($campos['vendedor_representante']))) {
                $usuario = Crypt::decrypt($campos['vendedor_representante']);
                $users = [User::find($usuario)->codigo_representante];
            }

        }

        if(!empty($users)){
            $clientes = ClienteNasajon::whereIn('vendedor_codigo',$users)->get()->pluck('cpf_cnpj')->toArray();
            unset($users);
            $pesquisa_satisfacao = $pesquisa_satisfacao->filter(function($pesquisa) use ($clientes){
                return (in_array($pesquisa->documento,$clientes));
            });
            unset($clientes);
        }

        $retorno = [];

        $pesquisa_satisfacao->each(function($query) use (&$retorno){
            foreach($query->formulariosRespondidos as $respondidos){
                $data = Carbon::parse($respondidos->created_at)->format('Y-m-01');
                $pergunta = $respondidos->pergunta;


                if(!isset($retorno[$pergunta])){
                    $retorno[$pergunta] = [
                        'pergunta' => $respondidos->pergunta,
                        'respostas' => [],
                    ];
                }

                if(!isset($retorno[$pergunta]['respostas'][$data])){

                    $retorno[$pergunta]['respostas'][$data] = [
                        'data' => $data,
                        'resposta' => []
                    ];
                }

                if(!isset($retorno[$pergunta]['respostas'][$data]['resposta'][$respondidos->respostas])){
                    $retorno[$pergunta]['respostas'][$data]['resposta'][$respondidos->respostas] = [
                        'quantidade' => 0
                    ];
                }

                $retorno[$pergunta]['respostas'][$data]['resposta'][$respondidos->respostas]['quantidade'] ++;

            }
            
        });

        unset($pesquisa_satisfacao);
        $saida = [];

        foreach($retorno as $pergunta => $respostas){

            if(!isset($saida[$pergunta])){
                $saida[$pergunta] = [
                    'pergunta' => $pergunta,
                    'respostas' => [],
                    'datas' => []
                ];
            }

            foreach($respostas['respostas'] as $data => $resposta){

                if(!isset($saida[$pergunta]['respostas'][$data])){
                    $saida[$pergunta]['respostas'][$data] = [];
                }

                foreach($resposta['resposta'] as $resposta_saida => $quantidade){

                    if(!isset($saida[$pergunta]['respostas'][$data][$resposta_saida])){
                        $saida[$pergunta]['respostas'][$data][$resposta_saida] = 0;
                    }

                    $saida[$pergunta]['respostas'][$data][$resposta_saida] = $quantidade['quantidade'];
                }
            }

            foreach($saida[$pergunta]['respostas'] as $data_saida => $respostas_saida){
                foreach($respostas_saida as $resposta_anterior => $quantidade_saida){

                    if($quantidade_saida > 0){
                        foreach($saida[$pergunta]['respostas'] as $data2 => $repostas_posterior){
                            
                            if(!array_key_exists($resposta_anterior, $repostas_posterior)){
                                $saida[$pergunta]['respostas'][$data2][$resposta_anterior] = 0;
                            }
                            
                        }
                    }

                }
            }

            ksort($saida[$pergunta]['respostas']);

            foreach($saida[$pergunta]['respostas'] as $key_respostas => $pergunta_resposta){
                foreach($pergunta_resposta as $key_quantidade => $valor){

                    if(!isset($saida[$pergunta]['datas'][$key_quantidade][$key_respostas])){
                        $saida[$pergunta]['datas'][$key_quantidade][$key_respostas] = [
                            'data' => '',
                            'quantidade' => 0
                        ];
                    }

                    $saida[$pergunta]['datas'][$key_quantidade][$key_respostas]['data'] = $key_respostas;
                    $saida[$pergunta]['datas'][$key_quantidade][$key_respostas]['quantidade'] = $valor;

                }
            }
            
        }
        
        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => '',
            'response' => ['retorno' => $saida]
        ]);
    }


}
