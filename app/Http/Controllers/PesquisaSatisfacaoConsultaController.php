<?php

namespace App\Http\Controllers;

use Auth;

use Carbon\Carbon;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Crypt;

use App\User;
use App\CepEstado;
use App\PesquisaSatisfacaoCliente;
use App\NotasNasajon;
use App\ClienteNasajon;
use App\PesquisaSatisfacaoFormularioResposta;

use App\Http\Requests\PesquisaSatisfacaoConsultaFiltroRequest;

class PesquisaSatisfacaoConsultaController extends Controller
{
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\PesquisaSatisfacaoConsulta") === false){
            return abort(403);
        }

        $estabelecimentos = returnEmpresasNasajonView();

        $request->session()->flash('model', 'App\PesquisaSatisfacaoConsulta');

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
            'estabelecimentos'              => $estabelecimentos,
            'gerentes'                      => $gerentes,
            'check_gerentes'                => $check_gerentes,
            'supervisores'                  => $supervisores,
            'check_supervisores'            => $check_supervisores,
            'vendedor_representante'        => $vendedor_representante,
            'check_vendedor_representante'  => $check_vendedor_representante
        ];

        return view('programs.pesquisa_satisfacao_consulta.index')->with($variaveis);
    }

    public function filtro(PesquisaSatisfacaoConsultaFiltroRequest $request){
        $campos = $request->only(["estabelecimento","gerentes","vendedor_representante","cliente_id","tipo_pesquisa","data_inicio_pesquisa_satisfacao","data_fim_pesquisa_satisfacao"]);
        
        $data_inicio = Carbon::createFromFormat('d/m/Y', $campos['data_inicio_pesquisa_satisfacao'])->format('Y-m-d 00:00:00');
        $data_fim = Carbon::createFromFormat('d/m/Y', $campos['data_fim_pesquisa_satisfacao'])->format('Y-m-d 23:59:59');
        
        $users = [];
        $gerente = '';
        $id_nota_nasajon = [];
        $documento_clientes = [];
        $notas_nasajon = null;
        $tipo_usuario_id = Auth::user()->tipo_usuario_id;

        $pesquisa_satisfacao = PesquisaSatisfacaoCliente::with(['clienteNotas','clienteNotas.notasNasajon.revisao_vendedor_comissao.usuario.supervisor','formulariosRespondidos' => function($query) use($data_inicio,$data_fim){
            $query->where('pesquisa_satisfacao_formulario_tipo_respostas_id','<>','2');
            $query->whereBetween('created_at',[$data_inicio,$data_fim]);
        }])
        ->where(function($query) use ($campos,$data_inicio,$data_fim){
            if(isset($campos['tipo_pesquisa'])){
                $query->whereBetween('created_at',[$data_inicio,$data_fim]);
                $query->orWhereHas('formulariosRespondidos',function($query_respondidos) use ($data_inicio,$data_fim){
                    $query_respondidos->whereBetween('created_at',[$data_inicio,$data_fim]);
                });
            }else{
                $query->whereHas('formulariosRespondidos',function($query_respondidos) use ($data_inicio,$data_fim){
                    $query_respondidos->whereBetween('created_at',[$data_inicio,$data_fim]);
                });
            }
        })
        ->get();

        $pesquisa_satisfacao->each(function($query) use (&$id_nota_nasajon,&$documento_clientes){
            $documento_clientes[] = $query->documento;
            foreach($query->clienteNotas as $notas){
                $id_nota_nasajon[] = $notas->nota_nasajon_id;
            }
        });

        if(!empty($campos['estabelecimento'])){
            $notas_nasajon = NotasNasajon::whereIn('id',$id_nota_nasajon)
            ->where('estabelecimento_codigo', str_pad($campos['estabelecimento'], 2, "0", STR_PAD_LEFT))
            ->get();

            $id_notas = $notas_nasajon->pluck('id');
            $id_notas = $id_notas->toArray();

            $pesquisa_satisfacao = $pesquisa_satisfacao->filter(function($pesquisas) use ($id_notas){
                foreach($pesquisas->clienteNotas as $notas){
                    return (in_array($notas->nota_nasajon_id,$id_notas));
                }
            });
        }

        if(!empty($campos['cliente_id'])){
            $clientes_documentos = ClienteNasajon::whereIn('cpf_cnpj',$documento_clientes)
            ->where('codigo','like',$campos['cliente_id'])
            ->get()
            ->pluck('cpf_cnpj')
            ->toArray();

            $pesquisa_satisfacao = $pesquisa_satisfacao->filter(function($pesquisas) use ($clientes_documentos){
                return (in_array($pesquisas->documento,$clientes_documentos));
            });
        }

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


        $retorno = [];
        $respondidos = [
            'nao_respondidos' => 0
        ];

        foreach ($pesquisa_satisfacao as $pesquisas_satisfacoes){
            if(!empty($users)){
                $codigo_representante = '';
                $id_gerente = '';

                if(isset($pesquisas_satisfacoes->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->supervisor)){if(!empty($campos['gerentes']) && empty($campos['vendedor_representante'])){
                    $codigo_representante = $pesquisas_satisfacoes->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->supervisor->codigo_representante;
                }else if(!empty($campos['vendedor_representante'])){
                    $codigo_representante = $pesquisas_satisfacoes->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->codigo_representante;
                }
                }else if(isset($pesquisas_satisfacoes->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->tipo_usuario_id) && $pesquisas_satisfacoes->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->tipo_usuario_id == 19){
                    $codigo_representante = $pesquisas_satisfacoes->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->id;
                }else{
                    $codigo_representante = '';
                }

                if(!empty($campos['gerentes'])){
                    $id_gerente = Crypt::decrypt($campos['gerentes']);
                }

                if(empty($codigo_representante)){
                    continue;
                }

                if(in_array($codigo_representante,$users) || $id_gerente == $codigo_representante){
                }else{
                    continue;
                }

            }

            if(!empty($pesquisas_satisfacoes->formulariosRespondidos[0])) {

                foreach($pesquisas_satisfacoes->formulariosRespondidos as $respostas) {

                    if(!isset($retorno[$respostas->pergunta])){
                        $retorno[$respostas->pergunta] = [
                            'resposta' => [],
                        ];
                    }

                    if(!isset($retorno[$respostas->pergunta]['resposta'][$respostas->respostas])){
                        $retorno[$respostas->pergunta]['resposta'][$respostas->respostas] = [
                            'alternativa' => $respostas->respostas,
                            'quantidade' => 0,
                            'pecentual' => '',
                            'filtro' => encrypt([
                                    'campos' => $campos,
                                    'pergunta' => $respostas->pergunta,
                                    'alternativa' => $respostas->respostas,
                                ])
                            ];
                    }

                    $retorno[$respostas->pergunta]['resposta'][$respostas->respostas]['quantidade'] ++;

                }

            }else{

                if(isset($campos['tipo_pesquisa'])){
                    $respondidos['nao_respondidos'] ++;
                }
                
            }
            
        }
        
        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => '',
            'response' => ['retorno' => $retorno,'nao_respondidos' => $respondidos]
        ]);

    }

    public function aberturaClientes(Request $request){
        set_time_limit(500);
        $filtro = $request->only(["filters","total","respondidos"]);

        try{
            $dados = decrypt($filtro['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }

        $campos = $dados['campos'];
        
        $data_inicio = Carbon::createFromFormat('d/m/Y', $campos['data_inicio_pesquisa_satisfacao'])->format('Y-m-d 00:00:00');
        $data_fim = Carbon::createFromFormat('d/m/Y', $campos['data_fim_pesquisa_satisfacao'])->format('Y-m-d 23:59:59');

        $users = [];
        $gerente = '';
        $id_nota_nasajon = [];
        $documento_clientes = [];
        $notas_nasajon = null;
        $tipo_usuario_id = Auth::user()->tipo_usuario_id;

        $pesquisa_satisfacao = PesquisaSatisfacaoCliente::where(function($query) use ($campos,$data_inicio,$data_fim){
            if(isset($campos['tipo_pesquisa'])){
                $query->whereBetween('created_at',[$data_inicio,$data_fim]);
                $query->orWhereHas('formulariosRespondidos',function($query_respondidos) use ($data_inicio,$data_fim){
                    $query_respondidos->whereBetween('created_at',[$data_inicio,$data_fim]);
                });
            }else{
                $query->whereHas('formulariosRespondidos',function($query_respondidos) use ($data_inicio,$data_fim){
                    $query_respondidos->whereBetween('created_at',[$data_inicio,$data_fim]);
                });
            }
        })
        ->with('clienteNotas.notasNasajon.revisao_vendedor_comissao.usuario.supervisor');
        
        if(!isset($campos['tipo_pesquisa'])){
            $pesquisa_satisfacao->whereHas('formulariosRespondidos', function ($querys) use ($dados,$filtro){
                $querys->where('pergunta',$dados['pergunta']);

                if($filtro['total'] == 'false') {
                    $querys->where('respostas', $dados['alternativa']);
                }
            })
            ->with(['clienteNotas','formulariosRespondidos']);

        }else{

            if($filtro['total'] == 'true'){
                $pesquisa_satisfacao->with(['clienteNotas','formulariosRespondidos' => function ($querys) use ($dados,$data_inicio,$data_fim){
                    $querys->where('pergunta',$dados['pergunta']);
                    $querys->whereBetween('created_at',[$data_inicio,$data_fim]);
                }]);
            }else if($filtro['respondidos'] == 'true'){
                $pesquisa_satisfacao->doesntHave('formulariosRespondidos')
                ->with(['clienteNotas']);
            }else{
                $pesquisa_satisfacao->whereHas('formulariosRespondidos', function ($querys) use ($dados,$filtro){
                    $querys->where('pergunta',$dados['pergunta'])
                    ->where('respostas', $dados['alternativa']);
                })
                ->with(['clienteNotas','formulariosRespondidos' => function ($querys) use ($dados,$data_inicio,$data_fim){
                    $querys->where('pergunta',$dados['pergunta']);
                    $querys->whereBetween('created_at',[$data_inicio,$data_fim]);
                }]);
            }

        }

        $pesquisa_satisfacao = $pesquisa_satisfacao->get();

        $pesquisa_satisfacao->each(function($query) use (&$id_nota_nasajon,&$documento_clientes){
            $documento_clientes[] = $query->documento;
            foreach($query->clienteNotas as $notas){
                $id_nota_nasajon[] = $notas->nota_nasajon_id;
            }
        });
        if(!empty($campos['estabelecimento'])){
            $notas_nasajon = NotasNasajon::whereIn('id',$id_nota_nasajon)
                ->where('estabelecimento_codigo', str_pad($campos['estabelecimento'], 2, "0", STR_PAD_LEFT))
                ->get();

            $id_notas = $notas_nasajon->pluck('id');
            $id_notas = $id_notas->toArray();

            $pesquisa_satisfacao = $pesquisa_satisfacao->filter(function($pesquisas) use ($id_notas){
                foreach($pesquisas->clienteNotas as $notas){
                    return (in_array($notas->nota_nasajon_id,$id_notas));
                }
            });
        }

        if(!empty($campos['cliente_id'])){
            $clientes_documentos = ClienteNasajon::whereIn('cpf_cnpj',$documento_clientes)
            ->where('codigo','like',$campos['cliente_id'])
            ->get()
            ->pluck('cpf_cnpj')
            ->toArray();

            $pesquisa_satisfacao = $pesquisa_satisfacao->filter(function($pesquisas) use ($clientes_documentos){
                return (in_array($pesquisas->documento,$clientes_documentos));
            });
        }

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


        $retorno = [];
        $estabelecimentos = returnEmpresasNasajonView();
        
        foreach ($pesquisa_satisfacao as $pesquisa){
            $notas = '';
            $gerentes = '';
            $vendedor = '';
            $notas_array = [];
            $comentarios = '';

            if(!empty($users)){
                $codigo_representante = '';
                $id_gerente = '';

                if(!empty($campos['gerentes'])){
                    $id_gerente = Crypt::decrypt($campos['gerentes']);
                }

                if(isset($pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->supervisor)){
                    if(!empty($campos['gerentes']) && empty($campos['vendedor_representante'])){
                        $codigo_representante = $pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->supervisor->codigo_representante;
                    }else if(!empty($campos['vendedor_representante'])){
                        $codigo_representante = $pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->codigo_representante;
                    }
                }else if(isset($pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->tipo_usuario_id) && $pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->tipo_usuario_id == 19){
                    $codigo_representante = $pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->id;
                }else{
                    $codigo_representante = '';
                }

                if(empty($codigo_representante)){
                    continue;
                }

                if(in_array($codigo_representante,$users) || $id_gerente == $codigo_representante){
                }else{
                    continue;
                }

            }

            if(isset($pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->name)){
                if(!empty($pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->codigo_representante)){
                    $vendedor = $pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->codigo_representante.' - '.$pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->name;
                }else{
                    $vendedor = $pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->name;
                }
            }

            if(isset($pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->supervisor)){
                $gerentes = $pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->supervisor->name;
            }else if(isset($pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->tipo_usuario_id) && $pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->tipo_usuario_id == 19){
                $gerentes = $pesquisa->clienteNotas[0]->notasNasajon->revisao_vendedor_comissao->usuario->name;
            }else{
                $gerentes = '';
            }

            foreach($pesquisa->formulariosRespondidos as $repostas){
                if($repostas->pergunta == 'Você tem algum comentário de como podemos melhorar nossos produtos e serviços?'){
                    $comentarios = $repostas->respostas;
                }
            }

            foreach ($pesquisa->clienteNotas as $nota){
                if(!empty($notas)){
                    $notas .= ' / ';
                }

                $notas .= $nota->numero;
                $notas_array[] = [
                    'id' => $nota->nota_nasajon_id,
                    'numero' => $nota->numero
                ];
            }

            $retorno[] = [
                'estabelecimento' => $estabelecimentos[(integer)$pesquisa->clienteNotas[0]->notasNasajon->estabelecimento_codigo],
                'cliente' => $pesquisa->nome.' - '.$pesquisa->documento,
                'gerente' => $gerentes,
                'vendedor' => $vendedor,
                'notas' => $notas,
                'notas_array' => $notas_array,
                'comentario' => $comentarios,
                'email' => $pesquisa->email,
                'data' => (isset($pesquisa->formulariosRespondidos[0]->created_at)) ? parserDataEHora($pesquisa->formulariosRespondidos[0]->created_at) : '',
                'id' => encrypt($pesquisa->id)
            ];
        }

        return view('programs.pesquisa_satisfacao_consulta.modal.listagem_clientes')->with(['retorno' => $retorno]);
    }

    public function aberturaFormulario(Request $request){
        $id = $request->only(["id"]);

        try{
            $id = decrypt($id['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }

        $formularios = PesquisaSatisfacaoFormularioResposta::where('pesquisa_satisfacao_clientes_id',$id)->get();

        $retorno = [];

        foreach ($formularios as $formulario){
            $retorno[] = [
                'pergunta' => $formulario->pergunta,
                'resposta' => str_replace("_"," ",$formulario->respostas),
            ];
        }

        return view('programs.pesquisa_satisfacao_consulta.modal.formulario')->with(['retorno' => $retorno]);

    }

    public function buscarPesquisaPosicaoSinteticaCliente(PesquisaSatisfacaoConsultaFiltroRequest $request){
        $fields = $request->only('codigo', 'data_inicio_pesquisa_satisfacao','data_fim_pesquisa_satisfacao');
        
        $cliente = ClienteNasajon::select()->where('codigo', $fields['codigo'])->first();

        if(!empty($cliente)){
            $pesquisa_satisfacao_query = PesquisaSatisfacaoCliente::where('documento',$cliente['cpf_cnpj'])
            ->with('formulariosRespondidos','clienteNotas')
            ->whereHas('formulariosRespondidos')
            ->get();

            $pesquisa_satisfacao = [];

            foreach($pesquisa_satisfacao_query as $pesquisa){
                $notas_pesquisa = '';

                foreach($pesquisa->clienteNotas as $notaClientePesquisa){
                    if(!empty($notas_pesquisa)){
                        $notas_pesquisa .= ' / ';
                    }
                    $notas_pesquisa .= $notaClientePesquisa->numero;
                }
                
                $pesquisa_satisfacao[] = [
                    'data' => (isset($pesquisa->formulariosRespondidos[0]->created_at)) ? parserDataEHora($pesquisa->formulariosRespondidos[0]->created_at) : '',
                    'email' => $pesquisa->email,
                    'cliente' => $pesquisa->nome,
                    'notas' => $notas_pesquisa,
                    'id_formulario' => encrypt($pesquisa->id)
                ];
            }
            
            
            return response()->json([
                'status' => 'success',
                'message' => '',
                'error' => '',
                'response' => ['retorno' => $pesquisa_satisfacao]
            ]);

        }else{
            return response()->json([
                'status' => 'success',
                'message' => '',
                'error' => '',
                'response' => ['retorno' => '']
            ]);
        }

    }

}
