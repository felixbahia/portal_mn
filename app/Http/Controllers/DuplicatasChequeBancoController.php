<?php

namespace App\Http\Controllers;

use Auth;
use App\User;
use Carbon\Carbon;
use App\ChequeNasajon;
use App\ClienteNasajon;
use App\GrupoEmpresarial;
use App\TitulosPagosNasajon;
use Illuminate\Http\Request;
use App\ChequesEmAbertoNasajon;
use App\TitulosVendedor998Nasajon;
use Illuminate\Support\Facades\DB;
use App\TitulosEmAbertoNasajonPortal;
use Illuminate\Support\Facades\Crypt;

class DuplicatasChequeBancoController extends Controller
{
    private $formasDePagamentoExcluidas = ['Dinheiro', 'Cartão Crédito', 'Cartão Débito', 'Deposito em conta', 'Usar Crédito'];
    
    public function __construct() {
        $this->middleware(['auth']);
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\DuplicatasChequeBanco") === false){
            return abort(403);
        }

        $request->session()->flash('model', 'App\DuplicatasChequeBanco');
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
        $data_filtro = $this->dataFiltro();

        $variaveis_view = [
            'gerentes'                      => $gerentes,
            'check_gerentes'                => $check_gerentes,
            'check_supervisores'            => $check_supervisores,
            'vendedor_representante'        => $vendedor_representante,
            'check_vendedor_representante'  => $check_vendedor_representante,
            'representantes'  => $vendedor_representante,
            'data_filtro'  => $data_filtro,
        ];



    	return view("programs.duplicatas_cheque_banco.index", $variaveis_view);
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
        ini_set('memory_limit', '256M');
        set_time_limit(300);
        $fields  = $request->only('gerentes', 'vendedor_representante', 'cliente_nome','data_inicio','data_fim','cheque','data_filtro');
        $cnpj = $this->cnpjINtercompany();
        
        $TitulosVendedor998NasajonObj = new TitulosVendedor998Nasajon;
        $titulosEmAbertoNasajonObj = new TitulosEmAbertoNasajonPortal;

        $titulosPagosNasajonObj = new TitulosPagosNasajon;

     

        if(!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998'){
            $queryAbertos = 'with titulos as(
                select codigo, saldotitulo, null as nota_id, numero, parcela, emissao as  titulo_emissao,  vencimento, tem_prorrogacao, valor, multa, nome_cliente, juros, datainiciomulta, desconto, nossonumero, banco_nome, observacao
                from ' . $TitulosVendedor998NasajonObj->getTable() . '
                where saldotitulo > 0  and situacao = \'Aberto\'';
        }else{
            $queryAbertos = 'with titulos as(
                select "codigo", "saldotitulo", "nota_id",  "numero", "parcela",  "titulo_emissao", "vencimento", "tem_prorrogacao", "valor", "multa", "nome_cliente", "cnpj", "juros", "datainiciomulta", "desconto", "nossonumero", "banco_nome", "banco_codigo", "observacao"
                from ' . $titulosEmAbertoNasajonObj->getTable() . '
                where "saldotitulo" > 0 ';

                $queryPagos = 'with titulos as(
                    select "vencimento", "data_pagamento", "codigo", "valor_titulo", "valor", "documento_id", "valorjuros", "nome_banco", "nome_cliente", "numero", "emissao", "valordesconto"
                    from ' . $titulosPagosNasajonObj->getTable() . '
                    where 
                        "codigo" not in (\'25\', \'TREINAMENTO\') ';
        }

        $ChequesAberto = ChequesEmAbertoNasajon::selectRaw('banco, data_vencimento, sum("valor") as valor');
        $semrelacaocheque = (isset($fields['cheque'])) ? ChequesEmAbertoNasajon::whereDoesntHave('cheques')->get()->pluck('cod_cliente')->toArray() : null;
           if($fields['data_filtro'] =='vencimento') {

               if(!empty($fields['data_inicio'])){
                    $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
                    $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
                    $queryPagos .= ' and vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
                    $ChequesAberto->where('data_vencimento','>=', $data_inicio->format('Y-m-d'));
                }
                
                if(!empty($fields['data_fim'])){
                    $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
                    $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
                    $queryPagos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
                    $ChequesAberto->where('data_vencimento','<=', $data_fim->format('Y-m-d'));
                }
         }else{
            if(!empty($fields['data_inicio'])){
                $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
                $queryAbertos .= ' AND titulo_emissao >= \'' . $data_inicio->format('Y-m-d') . '\'';
                $queryPagos .= ' AND emissao >= \'' . $data_inicio->format('Y-m-d') . '\'';
                $ChequesAberto->where('data_entrada','>=', $data_inicio->format('Y-m-d'));
            }else{
                $data_inicio = Carbon::now()->setTime(00,00,00)->addDays(-30);
                $queryPagos .= ' AND emissao >= \'' . $data_inicio->format('Y-m-d') . '\'';
                $queryAbertos .= ' AND titulo_emissao >= \'' . $data_inicio->format('Y-m-d') . '\'';

            }
            
            if(!empty($fields['data_fim'])){
                $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
                $queryAbertos .= ' AND titulo_emissao <= \'' . $data_fim->format('Y-m-d') . '\'';
                $queryPagos .= ' AND emissao <= \'' . $data_fim->format('Y-m-d') . '\'';
                $ChequesAberto->where('data_entrada','<=', $data_fim->format('Y-m-d'));
            }else{

                $data_fim = Carbon::now()->setTime(23,59,59);
                $queryPagos .= ' AND emissao <= \'' . $data_fim->format('Y-m-d') . '\'';
                $queryAbertos .= ' AND titulo_emissao <= \'' . $data_fim->format('Y-m-d') . '\'';
            }
         }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\') AND cod_cliente not in (\''. implode('\',\'', $cnpj) . '\')';
        $queryPagos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\') AND cod_cliente not in (\''. implode('\',\'', $cnpj) . '\')';
        $ChequesAberto->groupBy('banco','data_vencimento')
        ->whereNotIn("cod_cliente", $cnpj);
            
        if(strtolower(Auth::user()->tipo_usuario_id) === "19" && empty($fields['vendedor_representante'])){
            $usuariogerente = Auth::user()->id;
            $representantes = User::where(function($query) use($usuariogerente){
                    $query->where('responsavel',$usuariogerente)
                        ->orWhere('id', Auth::id());
                })
                ->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
                $queryPagos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';

                $queryPagos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                where vdv.tituloreceber  = vw_titulospagos_portal_vendedor.id_titulo  
                and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
            )';
            }

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
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
            $representantes = User::where('responsavel',$responsavel)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
                $queryPagos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';

                $queryPagos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                where vdv.tituloreceber  = vw_titulospagos_portal_vendedor.id_titulo   
                and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
            )';
            }

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
            }
        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
                $queryPagos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
                $queryPagos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                where vdv.tituloreceber  = vw_titulospagos_portal_vendedor.id_titulo  
                and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
            )';
            }

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
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
                $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial'); })->get()->pluck('id');

                $representantes = User::
                    where(function ($query) use($gerentes){
                        $query->whereIn('responsavel', $gerentes)
                            ->orWhereIn('id', $gerentes);
                    })
                    ->pluck('codigo_representante')
                    ->toArray();

                $queryAbertos .= ' AND (vendedor_codigo not in (\'' . implode('\',\'', $representantes) . '\') or vendedor_codigo is null)';
                $queryPagos .= ' AND (vendedor_codigo not in (\'' . implode('\',\'', $representantes) . '\') or vendedor_codigo is null)';

                $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                    $querycheque->whereHas('notas',function ($querys) use ($representantes){
                        $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                            $query_vendedor->whereNotIn('vendedor_codigo',$representantes)
                            ->orWhereNull('vendedor_codigo');
                        })
                        ->orWhereDoesntHave('revisao_vendedor_comissao');
                    });
                });
                if(!empty($semrelacaocheque)){
                    $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                        $query->whereIn('codigo',$semrelacaocheque);
                        $query->whereHas('notasVenda', function($querynota) use ($representantes){
                            $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                                $queryvendedor->whereNotIn('vendedor_codigo',$representantes)
                                ->orWhereNull('vendedor_codigo');
                            })
                            ->orWhereDoesntHave('revisao_vendedor_comissao');
                        });
                    });
                }
            }
            else{
                $gerente_representante = User::with('subordinados')->find($gerente);
                $representantes = $gerente_representante->subordinados->pluck('codigo_representante')->filter()->toArray();

                if(!empty($gerente_representante->codigo_representante)){
                    $representantes[] = $gerente_representante->codigo_representante;
                }

                if(!in_array('998', $representantes)){
                    $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
                    $queryPagos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
                }else{
                    $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                        where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                        and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                    )';
                    $queryPagos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  =vw_titulospagos_portal_vendedor.id_titulo  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
                }

                $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                    $querycheque->whereHas('notas',function ($querys) use ($representantes){
                        $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                            $query_vendedor->whereIn('vendedor_codigo',$representantes);
                        }); 
                    });
                });
                if(!empty($semrelacaocheque)){
                    $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                        $query->whereIn('codigo',$semrelacaocheque);
                        $query->whereHas('notasVenda', function($querynota) use ($representantes){
                            $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                                $queryvendedor->whereIn('vendedor_codigo',$representantes);
                            });
                        });
                    });
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
                $queryPagos .= ' AND cod_cliente in (\'' . implode('\',\'', $clientecodigo) . '\')';
                $ChequesAberto->whereIn('cod_cliente', $clientecodigo);
            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
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
            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';

                $queryPagos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
                $queryPagos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                where vdv.tituloreceber  =vw_titulospagos_portal_vendedor.id_titulo 
                and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
            )';
            }

            if(in_array('001', $representantes)){
                $queryAbertos .= ' or vendedor_codigo is null';
                $queryPagos .= ' or vendedor_codigo is null';
            }

            $queryAbertos .= ')';
            
            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->where(function($querys) use($representantes){
                        $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                            $query_vendedor->whereIn('vendedor_codigo',$representantes);
                            
                            if(in_array('001', $representantes)){
                                $query_vendedor->orwhereNull('vendedor_codigo');
                            }
                            if(in_array('001', $representantes)){
                                $querys->orWhereDoesntHave('revisao_vendedor_comissao');
                            }
                        });
                        if(in_array('001', $representantes)){
                            $querys->orWhereDoesntHave('notasVenda');
                        }
                    });
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque)
                        ->where(function($query) use($representantes){
                            $query->whereHas('notasVenda', function($querynota) use ($representantes){
                                $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                                    $queryvendedor->whereIn('vendedor_codigo',$representantes);

                                    if(in_array('001', $representantes)){
                                        $queryvendedor->orwhereNull('vendedor_codigo');
                                    }
                                });
                                if(in_array('001', $representantes)){
                                    $querynota->orWhereDoesntHave('revisao_vendedor_comissao');
                                } 
                            });
                        });
                        if(in_array('001', $representantes)){
                            $query->orWhereDoesntHave('notasVenda');
                        }
                });
            }
        }


        $queryAbertos .= ')
            select *
                from titulos t';

                $queryPagos .= ')
                select *
                    from titulos t';

        $TitulosAberto = collect(DB::connection('nasajon')->select($queryAbertos));
        if($fields['data_filtro'] =='emissao') {

            $titulosPago = collect(DB::connection('nasajon')->select($queryPagos));
        }
        $ChequesAberto = (isset($fields['cheque'])) ? $ChequesAberto->get() : null;
        $estabelecimentos = returnEmpresasNasajonView();
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
                'banco' => ($titulo->banco_nome === null) ? 'Carteira' : $titulo->banco_nome,
                'aberto' => ($data_vencimento->gte($data)) ? $titulo->saldotitulo : 0,
                'vencido' => ($data_vencimento->lt($data)) ? $titulo->saldotitulo : 0,
                'total' => $titulo->saldotitulo,
                'pago' =>0  ,
            ];
        }  
        if($fields['data_filtro'] =='emissao') {
        foreach($titulosPago as $titulo){

            $data_vencimento = Carbon::createFromFormat('Y-m-d',$titulo->vencimento);
            $data = Carbon::now();    
            if($data_vencimento->dayOfWeekIso == 6){ 
                $data_vencimento->addDays(2);
            }
            if($data_vencimento->dayOfWeekIso == 7){ 
                $data_vencimento->addDays(1);
            }

           
            $retorno[] = [
                'banco' => ($titulo->nome_banco === null) ? 'Carteira' : $titulo->nome_banco,
                'aberto' =>  0,
                'vencido' =>  0,
                'total' => $titulo->valor,
                'pago' => $titulo->valor,  
            ];
  
        }
        
        unset($titulosPago);
    }
        unset($TitulosAberto);
        if(isset($fields['cheque'])){
            foreach($ChequesAberto as $titulo){
                $data_vencimento = Carbon::createFromFormat('Y-m-d',$titulo->data_vencimento);
                $data = Carbon::now();    
                if($data_vencimento->dayOfWeekIso == 6){ 
                    $data_vencimento->addDays(2);
                }
                if($data_vencimento->dayOfWeekIso == 7){ 
                    $data_vencimento->addDays(1);
                }
                $retorno[] = [
                    'banco' => 'Cheque',
                    'aberto' => ($data_vencimento->gte($data)) ? $titulo->valor : 0,
                    'vencido' => ($data_vencimento->lt($data)) ? $titulo->valor : 0,
                    'total' => $titulo->valor,
                    'pago' =>$titulo->saldotitulo -$titulo->valor  ,
                ];
            }
        }

        unset($ChequesAberto);
        $saida = [];
        $total = [
            'aberto' => 0,
            'vencido' => 0,
            'total' => 0,
            'pago' => 0,
            'filter' => encrypt([
                'banco' => null,
                'gerentes' => (isset($fields['gerentes'])) ? $fields['gerentes'] : null,
                'vendedor_representante' => (isset($fields['vendedor_representante'])) ? $fields['vendedor_representante'] : null,
                'cliente_nome' => $fields['cliente_nome'],
                'data_inicio' => $fields['data_inicio'],
                'data_fim' => $fields['data_fim'],
                'cheque' =>  (isset($fields['cheque'])) ? true : null,
            ])
        ];

        foreach($retorno as $resp){
            if(!isset($saida[$resp['banco']])){
                $saida[$resp['banco']] = [
                    'banco' => $resp['banco'],
                    'aberto' => 0,
                    'vencido' => 0,
                    'total' => 0,
                    'pago' => 0,
                    'filter' => encrypt([
                        'banco' => $resp['banco'],
                        'gerentes' => (isset($fields['gerentes'])) ? $fields['gerentes'] : null,
                        'vendedor_representante' => (isset($fields['vendedor_representante'])) ? $fields['vendedor_representante'] : null,
                        'cliente_nome' => $fields['cliente_nome'],
                        'data_inicio' => $fields['data_inicio'],
                        'data_fim' => $fields['data_fim'],
                        'cheque' =>  (isset($fields['cheque'])) ? true : null,
                    ]),
                ];
            }
            $saida[$resp['banco']]['aberto'] += $resp['aberto'];
            $saida[$resp['banco']]['vencido'] += $resp['vencido'];
            $saida[$resp['banco']]['total'] += $resp['total'];
            $saida[$resp['banco']]['pago'] += $resp['pago'];
            $total['aberto'] += $resp['aberto'];
            $total['vencido'] += $resp['vencido'];
            $total['total'] += $resp['total'];
            $total['pago'] += $resp['pago'];
        }

        unset($retorno);
        foreach($saida as $key => $row){
            $saida[$key]['aberto'] = ($row['aberto'] > 0) ? parserValor($row['aberto']) : '';
            $saida[$key]['vencido'] = ($row['vencido'] > 0) ? parserValor($row['vencido']) : '';
            $saida[$key]['total'] = ($row['total'] > 0) ? parserValor($row['total']) : '';
            $saida[$key]['pago'] = ($row['pago'] > 0) ? parserValor($row['pago']) : '';
        }

        $total['aberto'] = ($total['aberto'] > 0) ? parserValor($total['aberto']) : '';
        $total['vencido'] = ($total['vencido'] > 0) ? parserValor($total['vencido']) : '';
        $total['total'] = ($total['total'] > 0) ? parserValor($total['total']) : '';
        $total['pago'] = ($total['pago'] > 0) ? parserValor($total['pago']) : '';

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['response' => $saida, 'total' => $total],
        ]);
    }

    public function modalCLiente(Request $request){
        ini_set('memory_limit', '512M');
        set_time_limit(300);
        $filter = $request->only(['filters','abertura','total','aberturageral','data_filtro']);
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
        
        $titulosEmAbertoNasajonObj = new TitulosEmAbertoNasajonPortal;
        $TitulosVendedor998NasajonObj = new TitulosVendedor998Nasajon;

        if(!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998'){
            $queryAbertos = 'with titulos as(
                select codigo, vencimento, cliente_cnpj as cnpj, nome_cliente, banco_nome, cod_cliente, saldotitulo as saldo, multa, \'\' as nota_id, numero
                from ' . $TitulosVendedor998NasajonObj->getTable() . '
                where "saldotitulo" > 0  and situacao = \'Aberto\'';
        }else{
            $queryAbertos = 'with titulos as(
                select codigo, vencimento, banco_codigo, banco_nome, cnpj, nome_cliente, cod_cliente, saldotitulo as saldo, multa, nota_id, numero
                from ' . $titulosEmAbertoNasajonObj->getTable() . '
                where "saldotitulo" > 0 ';

        }

        $ChequesAberto = ChequesEmAbertoNasajon::selectRaw('banco, data_vencimento, cnpj, agencia, nome_cliente, cod_cliente, valor');
        $semrelacaocheque = (isset($fields['cheque'])) ? ChequesEmAbertoNasajon::whereDoesntHave('cheques')->get()->pluck('cod_cliente')->toArray() : null;
        
        if($filter['data_filtro'] =='vencimento') {

            if(!empty($fields['data_inicio'])){
                 $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
                 $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
                 $ChequesAberto->where('data_vencimento','>=', $data_inicio->format('Y-m-d'));
             }
             
             if(!empty($fields['data_fim'])){
                 $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
                 $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
                 $ChequesAberto->where('data_vencimento','<=', $data_fim->format('Y-m-d'));
             }
      }else{
         if(!empty($fields['data_inicio'])){
             $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
             $queryAbertos .= ' AND titulo_emissao >= \'' . $data_inicio->format('Y-m-d') . '\'';
             $ChequesAberto->where('data_entrada','>=', $data_inicio->format('Y-m-d'));
         }
         
         if(!empty($fields['data_fim'])){
             $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
             $queryAbertos .= ' AND titulo_emissao <= \'' . $data_fim->format('Y-m-d') . '\'';
             $ChequesAberto->where('data_entrada','<=', $data_fim->format('Y-m-d'));
         }
      }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\') AND cod_cliente not in (\''. implode('\',\'', $cnpj) . '\')';

        $ChequesAberto->whereNotIn("cod_cliente", $cnpj);

        if($filter['total'] !== 'true' && $fields['banco'] !== 'Cheque'){ 
            if($fields['banco'] === 'Carteira'){
                $queryAbertos .= ' AND (banco_nome = \''. $fields['banco'] .  '\' OR banco_nome is null)';
            }else{
                $queryAbertos .= ' AND banco_nome = \'' . $fields['banco'].'\'';
            }
        }


        if(strtolower(Auth::user()->tipo_usuario_id) === "19" && empty($fields['vendedor_representante'])){
            $usuariogerente = Auth::user()->id;
            $representantes = User::where(function($query) use($usuariogerente){
                    $query->where('responsavel',$usuariogerente)
                        ->orWhere('id', Auth::id());
                })
                ->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
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
            $representantes = User::where('responsavel',$responsavel)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
            }
        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
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
                $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial'); })->get()->pluck('id');

                $representantes = User::with(['tipo_usuario'])
                    ->where(function ($query) use($gerentes){
                        $query->whereIn('responsavel', $gerentes)
                            ->orWhereIn('id', $gerentes);
                    })
                    ->pluck('codigo_representante')
                    ->toArray();

                $queryAbertos .= ' AND (vendedor_codigo not in (\'' . implode('\',\'', $representantes) . '\') or vendedor_codigo is null)';

                
                $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                    $querycheque->whereHas('notas',function ($querys) use ($representantes){
                        $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                            $query_vendedor->whereNotIn('vendedor_codigo',$representantes)
                            ->orWhereNull('vendedor_codigo');
                        })
                        ->orWhereDoesntHave('revisao_vendedor_comissao');
                    })
                    ->orWhereDoesntHave('notas');
                });
                if(!empty($semrelacaocheque)){
                    $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                        $query->whereIn('codigo',$semrelacaocheque);
                        $query->whereHas('notasVenda', function($querynota) use ($representantes){
                            $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                                $queryvendedor->whereNotIn('vendedor_codigo',$representantes)
                                ->orWhereNull('vendedor_codigo');
                            })
                            ->orWhereDoesntHave('revisao_vendedor_comissao');
                        })
                        ->orWhereDoesntHave('notasVenda');
                    });
                }
            }
            else{
                $gerente_representante = User::with('subordinados')->find($gerente);
                $representantes = $gerente_representante->subordinados->pluck('codigo_representante')->filter()->toArray();

                if(!empty($gerente_representante->codigo_representante)){
                    $representantes[] = $gerente_representante->codigo_representante;
                }
            }

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
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
                $ChequesAberto->whereIn('cod_cliente', $clientecodigo);
            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
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
            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }
            if(in_array('001', $representantes)){
                $queryAbertos .= ' or vendedor_codigo is null';
            }

            $queryAbertos .= ')';

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);

                        if(in_array('001', $representantes)){
                            $query_vendedor->orWhereNull('vendedor_codigo');
                        }
                    }); 

                    if(in_array('001', $representantes)){
                        $query_vendedor->orWhereDoesntHave('revisao_vendedor_comissao');
                    }
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
    
                            if(in_array('001', $representantes)){
                                $querynota->orWhereNull('vendedor_codigo');
                            }
                        });

                        if(in_array('001', $representantes)){
                            $querynota->orWhereDoesntHave('revisao_vendedor_comissao');
                        }
                    });
                });
            }
        }

        $estabelecimentos = returnEmpresasNasajonView();
        

        $queryAbertos .= ')
            select *
                from titulos t';

        $TitulosAberto = collect(DB::connection('nasajon')->select($queryAbertos));
        $ChequesAberto = (isset($fields['cheque'])) ? $ChequesAberto->get() : null;
        $retorno = [];
        $total = [
            'aberto' => 0,
            'vencido' => 0,
            'total' => 0,
        ];

        if($fields['banco'] !== 'Cheque'){
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
                            'banco' => ($titulo->banco_nome === null) ? 'Carteira' : $titulo->banco_nome,
                            'aberto' => 0,
                            'vencido' => 0,
                            'total' => 0,
                            'filter' => encrypt([
                                'banco' => ($titulo->banco_nome === null) ? 'Carteira' : $titulo->banco_nome,
                                'gerentes' => (isset($fields['gerentes'])) ? $fields['gerentes'] : null,
                                'vendedor_representante' => (isset($fields['vendedor_representante'])) ? $fields['vendedor_representante'] : null,
                                'cliente_nome' => $fields['cliente_nome'],
                                'cod_cliente' => $titulo->cod_cliente,
                                'data_inicio' => $fields['data_inicio'],
                                'data_fim' => $fields['data_fim'],
                                'cheque' =>  (isset($fields['cheque'])) ? true : null,
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
        }

        if($fields['cheque'] !== null && $fields['banco'] === 'Cheque' || $fields['banco'] === null && isset($ChequesAberto)){
            foreach($ChequesAberto as $titulo){
                if(!empty($titulo->valor)){
                    $data_vencimento = Carbon::createFromFormat('Y-m-d',$titulo->data_vencimento);
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
                            'banco' => 'Cheque',
                            'aberto' => 0,
                            'vencido' => 0,
                            'total' => 0,
                            'filter' => encrypt([
                                'banco' => 'Cheque',
                                'gerentes' => (isset($fields['gerentes'])) ? $fields['gerentes'] : null,
                                'vendedor_representante' => (isset($fields['vendedor_representante'])) ? $fields['vendedor_representante'] : null,
                                'cliente_nome' => $fields['cliente_nome'],
                                'cod_cliente' => $titulo->cod_cliente,
                                'data_inicio' => $fields['data_inicio'],
                                'data_fim' => $fields['data_fim'],
                                'cheque' =>  (isset($fields['cheque'])) ? true : null,
                            ]),
                        ];
                    }
                    $retorno[$titulo->cod_cliente]['aberto'] += ($data_vencimento->gte($data)) ? $titulo->valor : 0;
                    $retorno[$titulo->cod_cliente]['vencido'] += ($data_vencimento->lt($data)) ? $titulo->valor : 0;
                    $retorno[$titulo->cod_cliente]['total'] += $titulo->valor;
                    $total['aberto'] += ($data_vencimento->gte($data)) ? $titulo->valor : 0;
                    $total['vencido'] += ($data_vencimento->lt($data)) ? $titulo->valor : 0;
                    $total['total'] += $titulo->valor;
                }
            }   
        }
        unset($ChequesAberto);
        foreach($retorno as $key => $row){
            $retorno[$key]['aberto'] = ($row['aberto'] > 0) ? parserValor($row['aberto']) : '';
            $retorno[$key]['vencido'] = ($row['vencido'] > 0) ? parserValor($row['vencido']) : '';
            $retorno[$key]['total'] = ($row['total'] > 0) ? parserValor($row['total']) : '';
        }

        $total['aberto'] = ($total['aberto'] > 0) ? parserValor($total['aberto']) : '';
        $total['vencido'] = ($total['vencido'] > 0) ? parserValor($total['vencido']) : '';
        $total['total'] = ($total['total'] > 0) ? parserValor($total['total']) : '';
        
        return view("programs.duplicatas_cheque_banco.modal.cliente")->with(["total" => $total, "retorno" => $retorno, "aberturageral" => $filter['aberturageral']]);
    }

    public function modalTitulos(Request $request){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $filter = $request->only(['filters','abertura','total','cliente','busca','aberturageral','data_filtro']);
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
                
        $titulosEmAbertoNasajonObj = new TitulosEmAbertoNasajonPortal;
        $TitulosVendedor998NasajonObj = new TitulosVendedor998Nasajon;

        if((!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998') || (isset($representante) && $representante == '998')){
            $queryAbertos = 'with titulos as(
                select codigo, saldotitulo, null as nota_id, documento_numero as nota_numero, numero, parcela, emissao as titulo_emissao, vencimento, tem_prorrogacao, valor, multa, nome_cliente, cliente_cnpj as cnpj, juros, percentualjurosdiario, datainiciomulta, desconto, nossonumero, banco_nome, observacao, vencimento_original
                from ' . $TitulosVendedor998NasajonObj->getTable() . '
                where saldotitulo > 0 and situacao = \'Aberto\'';
        }else{
            $queryAbertos = 'with titulos as(
                select "codigo", "saldotitulo", "nota_id", "numero", "nota_numero", "parcela", "titulo_emissao", "vencimento", "tem_prorrogacao", "valor", "multa", "nome_cliente", "cnpj", "juros", "percentualjurosdiario", "datainiciomulta", "desconto", "nossonumero", "banco_nome", "observacao", "vencimento_original"
                from ' . $titulosEmAbertoNasajonObj->getTable() . '
                where "saldotitulo" > 0 ';
        }

        $ChequesAberto = ChequeNasajon::with('chequeTitulo')
            ->whereNotIn("cliente_codigo", $cnpj)
            ->whereNotIn('status', ["Compensado", 'Depositado', 'Devolvido (Tratado)']);

        $semrelacaocheque = (isset($fields['cheque'])) ? ChequesEmAbertoNasajon::whereDoesntHave('cheques')->get()->pluck('cod_cliente')->toArray() : null;

        if($fields['data_filtro'] =='vencimento') {

            if(!empty($fields['data_inicio'])){
                 $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
                 $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
                 $ChequesAberto->where('data_vencimento','>=', $data_inicio->format('Y-m-d'));
             }
             
             if(!empty($fields['data_fim'])){
                 $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
                 $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
                 $ChequesAberto->where('data_vencimento','<=', $data_fim->format('Y-m-d'));
             }
      }else{
         if(!empty($fields['data_inicio'])){
             $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
             $queryAbertos .= ' AND titulo_emissao >= \'' . $data_inicio->format('Y-m-d') . '\'';
             $ChequesAberto->where('data_entrada','>=', $data_inicio->format('Y-m-d'));
         }
         
         if(!empty($fields['data_fim'])){
             $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
             $queryAbertos .= ' AND titulo_emissao <= \'' . $data_fim->format('Y-m-d') . '\'';
             $ChequesAberto->where('data_entrada','<=', $data_fim->format('Y-m-d'));
         }
      }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\') AND cod_cliente not in (\''. implode('\',\'', $cnpj) . '\')';
        
        if($filter['cliente'] === 'true' && $filter['aberturageral'] === 'false' && $fields['banco'] !== 'Cheque'){
            if($fields['banco'] === 'Carteira'){
                $queryAbertos .= ' AND (banco_nome = \''. $fields['banco'] .  '\' OR banco_nome is null)';
            }else{
                $queryAbertos .= ' AND banco_nome = \'' . $fields['banco'].'\'';
            }
        }
        $data = Carbon::now();
        if($filter['abertura'] == 'aberto'){    
            $queryAbertos .= ' AND (
                CASE  
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END >= \''. $data->format('Y-m-d') .'\')';

            $ChequesAberto->whereRaw('(
                CASE
                    WHEN date_part(\'isodow\', data_vencimento) = 6 THEN (data_vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', data_vencimento) = 7 THEN (data_vencimento + interval \'1\' day)
                    ELSE data_vencimento
                END >= \''. $data->format('Y-m-d') .'\')'
            );

        }else if($filter['abertura'] == 'vencido'){
            $queryAbertos .= ' AND (
                CASE
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END < \''. $data->format('Y-m-d') .'\')';

            $ChequesAberto->whereRaw('(
                CASE
                    WHEN date_part(\'isodow\', data_vencimento) = 6 THEN (data_vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', data_vencimento) = 7 THEN (data_vencimento + interval \'1\' day)
                    ELSE data_vencimento
                END < \''. $data->format('Y-m-d') .'\')'
            );

        }

        if(strtolower(Auth::user()->tipo_usuario_id) === "19" && empty($fields['vendedor_representante'])){
            $usuariogerente = Auth::user()->id;
            $representantes = User::where(function($query) use($usuariogerente){
                    $query->where('responsavel',$usuariogerente)
                        ->orWhere('id', Auth::id());
                })
                ->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
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
            $representantes = User::where('responsavel',$responsavel)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
            }
        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
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
                $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial'); })->get()->pluck('id');

                $representantes = User::with(['tipo_usuario'])
                    ->where(function ($query) use($gerentes){
                        $query->whereIn('responsavel', $gerentes)
                            ->orWhereIn('id', $gerentes);
                    })
                    ->pluck('codigo_representante')
                    ->toArray();

            $queryAbertos .= ' AND (vendedor_codigo not in (\'' . implode('\',\'', $representantes) . '\') or vendedor_codigo is null)';

                $ChequesAberto->whereHas('cheques', function ($query) use ($representantes){ 
                    $query->where(function($querycheque) use($representantes){
                        $querycheque->whereHas('notas',function ($querys) use ($representantes){
                            $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                                $query_vendedor->whereNotIn('vendedor_codigo',$representantes)
                                ->orWhereNull('vendedor_codigo');
                            })
                            ->orWhereDoesntHave('revisao_vendedor_comissao');
                        })
                        ->orWhereDoesntHave('notas');
                    });
                });
                if(!empty($semrelacaocheque)){
                    $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                        $query->whereIn('codigo',$semrelacaocheque)
                        ->where(function($query) use($representantes){
                            $query->whereHas('notasVenda', function($querynota) use ($representantes){
                                $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                                    $queryvendedor->whereNotIn('vendedor_codigo',$representantes)
                                    ->orWhereNull('vendedor_codigo');
                                })
                                ->orWhereDoesntHave('revisao_vendedor_comissao');
                            })
                            ->orWhereDoesntHave('notasVenda');
                        });
                    });
                }
            }
            else{
                $gerente_representante = User::with('subordinados')->find($gerente);
                $representantes = $gerente_representante->subordinados->pluck('codigo_representante')->filter()->toArray();

                if(!empty($gerente_representante->codigo_representante)){
                    $representantes[] = $gerente_representante->codigo_representante;
                }

                if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

                $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                    $querycheque->whereHas('notas',function ($querys) use ($representantes){
                        $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                            $query_vendedor->whereIn('vendedor_codigo',$representantes);
                        }); 
                    });
                });
                if(!empty($semrelacaocheque)){
                    $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                        $query->whereIn('codigo',$semrelacaocheque);
                        $query->whereHas('notasVenda', function($querynota) use ($representantes){
                            $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                                $queryvendedor->whereIn('vendedor_codigo',$representantes);
                            });
                        });
                    });
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
                $ChequesAberto->whereIn('cliente_codigo', $clientecodigo);
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
            $ChequesAberto->where(DB::raw('CONCAT(TRIM(cliente_nome), \' - \', cliente_cnpj)'), 'ilike', '%'.$filter['busca'].'%');
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
            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
            $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';

            if(in_array('001', $representantes)){
                $queryAbertos .= ' or vendedor_codigo is null';
            }

            $queryAbertos .= ')';

            $ChequesAberto->where(function($chequeTituloQuery) use($representantes){
                $chequeTituloQuery->whereHas('chequeTitulo', function ($querycheque) use ($representantes){ 
                    $querycheque->whereHas('nota',function ($querys) use ($representantes){
                        $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                            $query_vendedor->whereIn('vendedor_codigo',$representantes);

                            if(in_array('001', $representantes)){
                                $query_vendedor->orWhereNull('vendedor_codigo');
                            }
                        }); 

                        if(in_array('001', $representantes)){
                            $querys->orWhereDoesntHave('revisao_vendedor_comissao');
                        }
                    });
                });
                if(in_array('001', $representantes)){
                    $chequeTituloQuery->orWhereDoesntHave('chequeTitulo');
                }
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });

                        if(in_array('001', $representantes)){
                            $queryvendedor->orWhereNull('vendedor_codigo');
                        }
                    });

                    if(in_array('001', $representantes)){
                        $querynota->orWhereDoesntHave('revisao_vendedor_comissao');
                    }
                });
            }
        }

        $queryAbertos .= ')
            select *
                from titulos t';

        $TitulosAberto = collect(DB::connection('nasajon')->select($queryAbertos));
        $ChequesAberto = (isset($fields['cheque'])) ? $ChequesAberto->get() : null;
        $estabelecimentos = returnEmpresasNasajonView(  );
        $cnpjCliente = [];
        $retorno = [];
        $num_cheque = null;
        $total = [
            'valor' => 0,
            'saldo' => 0,
            'juros' => 0,
        ];
            
        if($fields['banco'] === 'Cheque' || $filter['aberturageral'] === 'true'){
            foreach($ChequesAberto as $titulo){
                if($filter['total'] === 'true'){
                    if(isset($titulo->cnpj)){
                        $cnpjCliente[] = [
                            'cnpj' =>  $titulo->cnpj,
                        ];
                    }
                }
                $num_cheque = $titulo->numero_cheque;
                $retorno[] = [
                    'banco' => $titulo->banco.' - Cheque',
                    'numero' => $titulo->chequeTitulo->pluck('titulo_numero')->implode(', '),
                    'numero_cheque' => $num_cheque,
                    'parcela' => '',
                    'data_emissao' => $titulo->data_entrada,
                    'data_vencimento_sql' => $titulo->data_vencimento,
                    'data_vencimento' => parserData($titulo->data_vencimento),
                    'valor_original' => $titulo->valor,
                    'valor' => $titulo->valor,
                    'multa' => '',
                    'nome_cliente' => $titulo->cliente_nome. ' - ' .$titulo->cliente_cnpj,
                    'nota_numero' => $titulo->chequeTitulo->pluck('documento_numero')->filter()->unique()->implode(', '),
                    'juros_cobrados' => '',
                    'percentual_juros_diarios' => '',
                    'data_juros' => '',
                    'desconto' => '',
                    'POSICAO_CR' => '',
                    'POSICAO_CR_DESCRICAO' => '',
                ];

                $total['valor'] += $titulo->valor;
                $total['saldo'] += $titulo->valor;
            }
        }
        
        if($fields['banco'] !== 'Cheque' || $filter['aberturageral'] === 'true'){
            foreach($TitulosAberto as $titulo){
                if($filter['total'] === 'true'){
                    if(isset($titulo->cnpj)){
                        $cnpjCliente[] = [
                            'cnpj' =>  $titulo->cnpj,
                        ];
                    }
                }
                $linha = [];

                $linha['banco'] = $titulo->banco_nome === null ? 'Carteira' : $titulo->banco_nome;
                $linha['numero'] = (empty($titulo->numero)) ? '': $titulo->numero;
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
                $linha['nota_numero'] = empty($titulo->nota_numero) ? '' : $titulo->nota_numero;
                $linha['POSICAO_CR'] = '';
                $linha['numero_cheque'] = '';
                $linha['POSICAO_CR_DESCRICAO'] = $titulo->nossonumero;
                $linha['observacao'] = $titulo->observacao;

                $retorno[] =  $linha;

                $total['valor'] += $titulo->valor;
                $total['saldo'] += $titulo->saldotitulo;
                $total['juros'] += $titulo->multa;
            }
        }

        $total = [
            'valor' => ($total['valor'] > 0) ? parserValor($total['valor']) : '',
            'saldo' => ($total['saldo'] > 0) ? parserValor($total['saldo']) : '',
            'juros' => ($total['juros'] > 0) ? parserValor($total['juros']) : '',
        ];
        
        return view('programs.duplicatas_cheque_banco.modal.titulos_faturados')->with(["dados"=> $retorno, "cod_cliente" => $cnpjCliente, "totalizadores" => $total, "cheque" => $num_cheque]);
    }

    public function modalRepresentante(Request $request){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $filter = $request->only(['filters','clientes','abertura','total','representante', 'aberturageral','data_filtro']);
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
        $saidatotal = ($filter['representante'] === 'false') ? 'true' : 'false';
                
        $titulosEmAbertoNasajonObj = new TitulosEmAbertoNasajonPortal;
        $TitulosVendedor998NasajonObj = new TitulosVendedor998Nasajon;

        if(!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998'){
            $queryAbertos = 'with titulos as(
                select codigo, saldotitulo, null as nota_id, numero, parcela, emissao as titulo_emissao, vencimento, tem_prorrogacao, valor, multa, nome_cliente, cliente_cnpj as cnpj, juros, datainiciomulta, desconto, nossonumero, observacao, vendedor_codigo
                from ' . $TitulosVendedor998NasajonObj->getTable() . '
                where saldotitulo > 0  and situacao = \'Aberto\'';
        }else{
            $queryAbertos = 'with titulos as(
                select "codigo", "saldotitulo", "nota_id", "numero", "banco_nome", "parcela", "titulo_emissao", "vencimento", "tem_prorrogacao", "valor", "multa", "nome_cliente", "cnpj", "juros", "datainiciomulta", "desconto", "nossonumero", "observacao", "vendedor_codigo"
                from ' . $titulosEmAbertoNasajonObj->getTable() . '
                where "saldotitulo" > 0 ';
        }

        $ChequesAberto = ChequesEmAbertoNasajon::whereNotIn("cod_cliente", $cnpj)
            ->with(['cheques.notas.revisao_vendedor_comissao']);
        $semrelacaocheque = (isset($fields['cheque'])) ? ChequesEmAbertoNasajon::whereDoesntHave('cheques')->get()->pluck('cod_cliente')->toArray() : null;
        
        if($filter['data_filtro'] =='vencimento') {

            if(!empty($fields['data_inicio'])){
                 $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
                 $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
                 $ChequesAberto->where('data_vencimento','>=', $data_inicio->format('Y-m-d'));
             }
             
             if(!empty($fields['data_fim'])){
                 $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
                 $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
                 $ChequesAberto->where('data_vencimento','<=', $data_fim->format('Y-m-d'));
             }
      }else{
         if(!empty($fields['data_inicio'])){
             $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
             $queryAbertos .= ' AND titulo_emissao >= \'' . $data_inicio->format('Y-m-d') . '\'';
             $ChequesAberto->where('data_entrada','>=', $data_inicio->format('Y-m-d'));
         }
         
         if(!empty($fields['data_fim'])){
             $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
             $queryAbertos .= ' AND titulo_emissao <= \'' . $data_fim->format('Y-m-d') . '\'';
             $ChequesAberto->where('data_entrada','<=', $data_fim->format('Y-m-d'));
         }
      }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\') AND cod_cliente not in (\''. implode('\',\'', $cnpj) . '\')';

        if($filter['total'] !== 'true' && $fields['banco'] !== 'Cheque'){ 
            if($fields['banco'] === 'Carteira'){
                $queryAbertos .= ' AND (banco_nome = \''. $fields['banco'] .  '\' OR banco_nome is null)';
            }else{
                $queryAbertos .= ' AND banco_nome = \'' . $fields['banco'].'\'';
            }
        }

        $data = Carbon::now();
        if($filter['abertura'] == 'aberto'){    
            $queryAbertos .= ' AND (
                CASE
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END >= \''. $data->format('Y-m-d') .'\')';
            
            $ChequesAberto->whereRaw('(
                CASE
                    WHEN date_part(\'isodow\', data_vencimento) = 6 THEN (data_vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', data_vencimento) = 7 THEN (data_vencimento + interval \'1\' day)
                    ELSE data_vencimento
                END >= \''. $data->format('Y-m-d') .'\')'
            );
            
        }else if($filter['abertura'] == 'vencido'){
            $queryAbertos .= ' AND (
                CASE
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END < \''. $data->format('Y-m-d') .'\')';

            $ChequesAberto->whereRaw('(
                CASE
                    WHEN date_part(\'isodow\', data_vencimento) = 6 THEN (data_vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', data_vencimento) = 7 THEN (data_vencimento + interval \'1\' day)
                    ELSE data_vencimento
                END < \''. $data->format('Y-m-d') .'\')'
            );

        }
        
        if(strtolower(Auth::user()->tipo_usuario_id) === "19" && empty($fields['vendedor_representante'])){
            $usuariogerente = Auth::user()->id;
            $representantes = User::where(function($query) use($usuariogerente){
                    $query->where('responsavel',$usuariogerente)
                        ->orWhere('id', Auth::id());
                })
                ->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
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
            $representantes = User::where('responsavel',$responsavel)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
            }
        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        if(!empty($representantes)){
                            $query_vendedor->whereIn('vendedor_codigo',$representantes);
                        }
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
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
                $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial'); })->get()->pluck('id');

                $representantes = User::with(['tipo_usuario'])
                    ->where(function ($query) use($gerentes){
                        $query->whereIn('responsavel', $gerentes)
                            ->orWhereIn('id', $gerentes);
                    })
                    ->pluck('codigo_representante')
                    ->toArray();

                    $queryAbertos .= ' AND (vendedor_codigo not in (\'' . implode('\',\'', $representantes) . '\') or vendedor_codigo is null)';


                $ChequesAberto->whereHas('cheques', function ($query) use ($representantes){ 
                    $query->where(function($querycheque) use ($representantes){
                        $querycheque->whereHas('notas',function ($querys) use ($representantes){
                            $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                                $query_vendedor->whereNotIn('vendedor_codigo',$representantes)
                                ->orWhereNull('vendedor_codigo');
                            })
                            ->orWhereDoesntHave('revisao_vendedor_comissao');
                        })
                        ->orWhereDoesntHave('notas');
                    });
                });
                if(!empty($semrelacaocheque)){
                    $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                        $query->whereIn('codigo',$semrelacaocheque)
                        ->where(function($query) use($representantes){
                            $query->whereHas('notasVenda', function($querynota) use ($representantes){
                                $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                                    $queryvendedor->whereNotIn('vendedor_codigo',$representantes)
                                    ->orWhereNull('vendedor_codigo');
                                })
                                ->orWhereDoesntHave('revisao_vendedor_comissao');
                            })
                            ->orWhereDoesntHave('notasVenda');
                        });
                    });
                }
            }
            else{
                $gerente_representante = User::with('subordinados')->find($gerente);
                $representantes = $gerente_representante->subordinados->pluck('codigo_representante')->filter()->toArray();

                if(!empty($gerente_representante->codigo_representante)){
                    $representantes[] = $gerente_representante->codigo_representante;
                }
            
                if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

                $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                    $querycheque->whereHas('notas',function ($querys) use ($representantes){
                        $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                            if(!empty($representantes)){
                                $query_vendedor->whereIn('vendedor_codigo',$representantes);
                            }
                        }); 
                    });
                });
                if(!empty($semrelacaocheque)){
                    $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                        $query->whereIn('codigo',$semrelacaocheque);
                        $query->whereHas('notasVenda', function($querynota) use ($representantes){
                            $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                                $queryvendedor->whereIn('vendedor_codigo',$representantes);
                            });
                        });
                    });
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
                $ChequesAberto->whereIn('cod_cliente', $clientecodigo);
            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
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
            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }
            if(in_array('001', $representantes)){
                $queryAbertos .= 'or revisao_vendedor_comissao is null';
            }
            
            $queryAbertos .= ')';

            $ChequesAberto->whereHas('cheques', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notas',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        if(!empty($representantes)){
                            $query_vendedor->whereIn('vendedor_codigo',$representantes);

                            if(in_array('001', $representantes)){
                                $query_vendedor->orWhereNull('vendedor_codigo');
                            }
                        }
                    }); 
                    if(in_array('001', $representantes)){
                        $querys->orWhereDoesntHave('revisao_vendedor_comissao');
                    }
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                            if(in_array('001', $representantes)){
                                $queryvendedor->orwhereNull('vendedor_codigo');
                            }
                            
                        });
                        if(in_array('001', $representantes)){
                            $querynota->orWhereDoesntHave('revisao_vendedor_comissao');
                        }
                    });
                });
            }
        }

        $queryAbertos .= ')
            select *
                from titulos t';

        $TitulosAberto = collect(DB::connection('nasajon')->select($queryAbertos));
        $nota_id = $TitulosAberto->pluck('nota_id')->toArray();
        $vendedor_998_array = [];
        $vendedor_998 = TitulosVendedor998Nasajon::whereIn('documento_id',$nota_id)->select('documento_id')->get();
        $vendedor_998->each(function($query) use (&$vendedor_998_array){
            $vendedor_998_array[] = $query->documento_id;
        });

        $ChequesAberto = (isset($fields['cheque'])) ? $ChequesAberto->get() : null;
        $retorno = [];
        $total = [
            'aberto' => 0,
            'vencido' => 0,
            'total' => 0,
        ];

        $estabelecimentos = returnEmpresasNasajonView();
        $cod = User::where('codigo_representante','001')->select('name','codigo_representante')->first();
        $cod001 = $cod['codigo_representante'].' - '.$cod['name'];

        if($fields['cheque'] !== null && $fields['banco'] === 'Cheque' || $fields['banco'] === null && isset($ChequesAberto)){
            foreach($ChequesAberto as $titulo){
                $data_vencimento = Carbon::createFromFormat('Y-m-d',$titulo->data_vencimento);
                $data = Carbon::now();    
                if($data_vencimento->dayOfWeekIso == 6){
                    $data_vencimento->addDays(2);
                }
                if($data_vencimento->dayOfWeekIso == 7){
                    $data_vencimento->addDays(1);
                }
                $usuariorepresentante = '';
                $codigorepresentante = '';
                $representante = (isset($titulo->cheques->notas[0]->revisao_vendedor_comissao->usuario) && $titulo->cheques->notas[0]->revisao_vendedor_comissao->vendedor_codigo != '001') ? $usuariorepresentante = $titulo->cheques->notas[0]->revisao_vendedor_comissao->usuario->name : $cod001;
                $vendedor = (!empty($usuariorepresentante)) ? $titulo->cheques->notas[0]->revisao_vendedor_comissao->usuario->codigo_representante : $cod['codigo_representante'];
                $representante = (!empty($usuariorepresentante)) ? $titulo->cheques->notas[0]->revisao_vendedor_comissao->usuario->codigo_representante.' - '.$titulo->cheques->notas[0]->revisao_vendedor_comissao->usuario->name : $cod001;

                if(!isset($retorno[$representante])){
                    $vededor = '';
                    if(in_array($titulo->nota_id,$vendedor_998_array)){
                        $vededor = ' - JURIDICO RAGAZZI';
                    }
                    $retorno[$representante] = [
                        'representante' => $representante.$vededor,
                        'banco' => 'Cheque',
                        'aberto' => 0,
                        'vencido' => 0,
                        'total' => 0,
                        'titulo_id' => '',
                        'filter' => encrypt([
                            'banco' => 'Cheque',
                            'gerentes' => (isset($fields['gerentes'])) ? $fields['gerentes'] : null,
                            'vendedor_representante' => (isset($fields['vendedor_representante'])) ? $fields['vendedor_representante'] : null,
                            'cliente_nome' => $fields['cliente_nome'],
                            'cod_vendedor' => $vendedor,
                            'data_inicio' => $fields['data_inicio'],
                            'data_fim' => $fields['data_fim'],
                            'cheque' =>  (isset($fields['cheque'])) ? true : null,
                        ]),
                    ];
                }
                $retorno[$representante]['aberto'] += ($data_vencimento->gte($data)) ? $titulo->valor : 0;
                $retorno[$representante]['vencido'] += ($data_vencimento->lt($data)) ? $titulo->valor : 0;
                $retorno[$representante]['total'] += $titulo->valor;
                $total['aberto'] += ($data_vencimento->gte($data)) ? $titulo->valor : 0;
                $total['vencido'] += ($data_vencimento->lt($data)) ? $titulo->valor : 0;
                $total['total'] += $titulo->valor;
            }
        }

        $usuarios = User::whereIn('codigo_representante', $TitulosAberto->pluck('vendedor_codigo'))->get();
        
        if($fields['banco'] !== 'Cheque'){
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
                $codigorepresentante = '';
                $representante = (!empty($usuario) && $usuario->codigo_representante != '001') ? $usuariorepresentante = $usuario->name : $cod001;
                $vendedor = (!empty($usuariorepresentante)) ? $usuario->codigo_representante : $cod['codigo_representante'];
                $representante = (!empty($usuariorepresentante)) ? $usuario->codigo_representante.' - '.$usuario->name : $cod001;
                
                if(!isset($retorno[$representante])){
                    $vededor = '';
                    if(in_array($titulo->nota_id,$vendedor_998_array)){
                        $vededor = ' - JURIDICO RAGAZZI';
                    }
                    $retorno[$representante] = [
                        'representante' => $representante.$vededor,
                        'banco' => ($titulo->banco_nome === null) ? 'Carteira' : $titulo->banco_nome,
                        'aberto' => 0,
                        'vencido' => 0,
                        'total' => 0,
                        'titulo_id' => '',
                        'filter' => encrypt([
                            'banco' => ($titulo->banco_nome === null) ? 'Carteira' : $titulo->banco_nome,
                            'gerentes' => (isset($fields['gerentes'])) ? $fields['gerentes'] : null,
                            'vendedor_representante' => (isset($fields['vendedor_representante'])) ? $fields['vendedor_representante'] : null,
                            'cliente_nome' => $fields['cliente_nome'],
                            'cod_vendedor' => $vendedor,
                            'data_inicio' => $fields['data_inicio'],
                            'data_fim' => $fields['data_fim'],
                            'cheque' =>  (isset($fields['cheque'])) ? true : null,
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
            'banco' => $fields['banco'],
            'gerentes' => (isset($fields['gerentes'])) ? $fields['gerentes'] : null,
            'vendedor_representante' => (isset($fields['vendedor_representante'])) ? $fields['vendedor_representante'] : null,
            'cliente_nome' => $fields['cliente_nome'],
            'data_inicio' => $fields['data_inicio'],
            'data_fim' => $fields['data_fim'],
            'cheque' =>  (isset($fields['cheque'])) ? true : null,
        ]);

        return view("programs.duplicatas_cheque_banco.modal.representante")->with(["total" => $total, "retorno" => $retorno, 'saidatotal' => $saidatotal, 'filter' => $filter, "aberturageral" => $filter['aberturageral']]);
    }

    public function modalTitulosRepresentante(Request $request){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $filter = $request->only(['filters','abertura','total','representante','codigo_representante', 'aberturageral','data_filtro']);
        
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

        $titulosEmAbertoNasajonObj = new TitulosEmAbertoNasajonPortal;
        $TitulosVendedor998NasajonObj = new TitulosVendedor998Nasajon;

        $queryAbertos = 'with titulos as(
            select "codigo", "saldotitulo", "nota_id", "nota_numero", "numero", "parcela", "banco_nome", "titulo_emissao", "vencimento", "tem_prorrogacao", "valor", "multa", "nome_cliente", "cnpj", "juros", "percentualjurosdiario", "datainiciomulta", "desconto", "nossonumero", "observacao", "vencimento_original"
            from ' . $titulosEmAbertoNasajonObj->getTable() . '
            where "saldotitulo" > 0 ';
        

        $ChequesAberto = ChequeNasajon::with('chequeTitulo')
            ->whereNotIn("cliente_codigo", $cnpj)
            ->whereNotIn('status', ["Compensado", 'Depositado', 'Devolvido (Tratado)']);

            if($filter['data_filtro'] =='vencimento') {

                if(!empty($fields['data_inicio'])){
                     $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
                     $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
                     $ChequesAberto->where('data_vencimento','>=', $data_inicio->format('Y-m-d'));
                 }
                 
                 if(!empty($fields['data_fim'])){
                     $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
                     $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
                     $ChequesAberto->where('data_vencimento','<=', $data_fim->format('Y-m-d'));
                 }
          }else{
             if(!empty($fields['data_inicio'])){
                 $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
                 $queryAbertos .= ' AND titulo_emissao >= \'' . $data_inicio->format('Y-m-d') . '\'';
                 $ChequesAberto->where('data_entrada','>=', $data_inicio->format('Y-m-d'));
             }
             
             if(!empty($fields['data_fim'])){
                 $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
                 $queryAbertos .= ' AND titulo_emissao <= \'' . $data_fim->format('Y-m-d') . '\'';
                 $ChequesAberto->where('data_entrada','<=', $data_fim->format('Y-m-d'));
             }
          }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\') AND cod_cliente not in (\''. implode('\',\'', $cnpj) . '\')';

        $data = Carbon::now();
        if($filter['abertura'] == 'aberto'){    
            $queryAbertos .= ' AND (
                CASE
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END >= \''. $data->format('Y-m-d') .'\')';

            $ChequesAberto->whereRaw('(
                CASE
                    WHEN date_part(\'isodow\', data_vencimento) = 6 THEN (data_vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', data_vencimento) = 7 THEN (data_vencimento + interval \'1\' day)
                    ELSE data_vencimento
                END >= \''. $data->format('Y-m-d') .'\')'
            );

        }else if($filter['abertura'] == 'vencido'){
            $queryAbertos .= ' AND (
                CASE 
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END < \''. $data->format('Y-m-d') .'\')';

            $ChequesAberto->whereRaw('(
                CASE
                    WHEN date_part(\'isodow\', data_vencimento) = 6 THEN (data_vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', data_vencimento) = 7 THEN (data_vencimento + interval \'1\' day)
                    ELSE data_vencimento
                END < \''. $data->format('Y-m-d') .'\')'
            );

        }

        if($filter['total'] === 'false' && $fields['banco'] !== 'Cheque'){
            if($fields['banco'] === 'Carteira'){
                $queryAbertos .= ' AND (banco_nome = \''. $fields['banco'] .  '\' OR banco_nome is null)';
            }else{
                $queryAbertos .= ' AND banco_nome = \'' . $fields['banco'].'\'';
            }
        }

        if(strtolower(Auth::user()->tipo_usuario_id) === "19" && empty($fields['vendedor_representante'])){
            $usuariogerente = Auth::user()->id;
            $representantes = User::where(function($query) use($usuariogerente){
                    $query->where('responsavel',$usuariogerente)
                        ->orWhere('id', Auth::id());
                })
                ->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
            
            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('chequeTitulo', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('nota',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                })
                ->orWhereDoesntHave('nota');
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
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
            $representantes = User::where('responsavel',$responsavel)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
            
            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('chequeTitulo', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('nota',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                })
                ->orWhereDoesntHave('nota');
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
            }
        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
            
            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('chequeTitulo', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('nota',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                })
                ->orWhereDoesntHave('nota');
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
            }
        }

        if($filter['representante'] == 'true'){
            $representantes = $fields['cod_vendedor'];
            
            $queryAbertos .= ' AND (vendedor_codigo = \'' . $representantes . '\'';

            if ($representantes == '001'){
               $queryAbertos .= ' or vendedor_codigo is null';
            }

            $queryAbertos .= ')';

            $ChequesAberto->where(function($chequeTituloQuery) use($representantes){
                $chequeTituloQuery->whereHas('chequeTitulo', function ($querycheque) use ($representantes){
                    $querycheque->where(function($querycheque) use($representantes){
                        $querycheque->whereHas('nota',function ($querys) use ($representantes){
                            $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                                if(!empty($representantes)){
                                    $query_vendedor->where('vendedor_codigo', $representantes);
                                } 
                                
                                if($representantes == '001'){
                                    $query_vendedor->orWhereNull('vendedor_codigo')
                                        ->orWhere('vendedor_codigo', '');
                                }
                            });
                            
                            if($representantes == '001'){
                                $querys->orWhereDoesntHave('revisao_vendedor_comissao');
                            }
                        });

                        if($representantes == '001'){
                            $querycheque->orWhereDoesntHave('nota');
                        }
                    });
                });

                if($representantes == '001'){
                    $chequeTituloQuery->orWhereDoesntHave('chequeTitulo');
                }
            });

            if(!empty($semrelacaocheque) && empty($fields['cliente_nome'])){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->where('vendedor_codigo',$representantes);

                            if($representantes == '001'){
                                $queryvendedor->orWhereNull('vendedor_codigo')
                                    ->orWhere('vendedor_codigo', '');
                            }
                        });

                        if($representantes == '001'){
                            $querynota->orWhereDoesntHave('revisao_vendedor_comissao');
                        }
                    });
                });
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
                $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial'); })->get()->pluck('id');

                $representantes = User::with(['tipo_usuario'])
                    ->where(function ($query) use($gerentes){
                        $query->whereIn('responsavel', $gerentes)
                            ->orWhereIn('id', $gerentes);
                    })
                    ->pluck('codigo_representante')
                    ->toArray();
                $queryAbertos .= ' AND (vendedor_codigo not in (\'' . implode('\',\'', $representantes) . '\') or vendedor_codigo is null)';

                $ChequesAberto->where(function($chequeTituloQuery) use($representantes){
                    $chequeTituloQuery->whereHas('chequeTitulo', function ($query) use ($representantes){ 
                        $query->where(function($querycheque) use($representantes){
                            $querycheque->whereHas('nota',function ($querys) use ($representantes){
                                $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                                    $query_vendedor->whereNotIn('vendedor_codigo',$representantes)
                                    ->orWhereNull('vendedor_codigo');
                                })
                                ->orWhereDoesntHave('revisao_vendedor_comissao');
                            })
                            ->orWhereDoesntHave('nota');
                        });
                    });

                    if($representantes == '001'){
                        $chequeTituloQuery->orWhereDoesntHave('chequeTitulo');
                    }
                });
                if(!empty($semrelacaocheque)){
                    $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                        $query->whereIn('codigo',$semrelacaocheque)
                        ->where(function($query) use($representantes){
                            $query->whereHas('notasVenda', function($querynota) use ($representantes){
                                $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                                    $queryvendedor->whereNotIn('vendedor_codigo',$representantes)
                                    ->orWhereNull('vendedor_codigo');
                                })
                                ->orWhereDoesntHave('revisao_vendedor_comissao');
                            })
                            ->orWhereDoesntHave('notasVenda');
                        });
                    });
                }
            }
            else{
                $gerente_representante = User::with('subordinados')->find($gerente);
                $representantes = $gerente_representante->subordinados->pluck('codigo_representante')->filter()->toArray();

                if(!empty($gerente_representante->codigo_representante)){
                    $representantes[] = $gerente_representante->codigo_representante;
                }
                if(!in_array('998', $representantes)){
                    $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
                }else{
                    $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                        where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                        and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                    )';
                }
                
                $ChequesAberto->whereHas('chequeTitulo', function ($querycheque) use ($representantes){ 
                    $querycheque->whereHas('nota',function ($querys) use ($representantes){
                        $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                            $query_vendedor->whereIn('vendedor_codigo',$representantes);
                        }); 
                    });
                });

                if(!empty($semrelacaocheque)){
                    $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                        $query->whereIn('codigo',$semrelacaocheque);
                        $query->whereHas('notasVenda', function($querynota) use ($representantes){
                            $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                                $queryvendedor->whereIn('vendedor_codigo',$representantes);
                            });
                        });
                    });
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
                $ChequesAberto->whereIn('cliente_codigo', $clientecodigo);
            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
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
            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
            
            if(in_array('998', $representantes) && $filter['total'] === 'false' || $filter['representante'] == 'true' && in_array('998', $representantes) || $filter['representante'] == 'false' && in_array('998', $representantes)){
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }else{
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }

            if(in_array('001', $representantes)){
                $queryAbertos .= ' or vendedor_codigo is null)';
            }

            $queryAbertos .= ')';
            
            $ChequesAberto->whereHas('chequeTitulo', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('nota',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                        if(in_array('001', $representantes)){
                            $query_vendedor->orWhereNull('vendedor_codigo');
                        }
                    }); 
                    if(in_array('001', $representantes)){
                        $querys->orWhereDoesntHave('revisao_vendedor_comissao');
                    }
                })
                ->orWhereDoesntHave('nota');
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                            if(in_array('001', $representantes)){
                                $queryvendedor->orWhereNull('vendedor_codigo');
                            }
                        });

                        if(in_array('001', $representantes)){
                            $querynota->orWhereDoesntHave('revisao_vendedor_comissao');
                        }
                    });
                });
            }
        }

        $queryAbertos .= ')
            select *
                from titulos t';

        $TitulosAberto = collect(DB::connection('nasajon')->select($queryAbertos));
        $ChequesAberto = (isset($fields['cheque'])) ? $ChequesAberto->get() : null;
        $estabelecimentos = returnEmpresasNasajonView();
        $cnpjCliente[] = ['cnpj' =>  'cnpj'];
        $cnpjCliente[] = ['result' =>  '2'];
        $retorno = [];
        $num_cheque = null;
        $total = [
            'valor' => 0,
            'saldo' => 0,
            'juros' => 0,
        ];

        if($fields['banco'] === 'Cheque' || $filter['aberturageral'] === 'true'){
            foreach($ChequesAberto as $titulo){
                $num_cheque = $titulo->numero_cheque;

                if(isset($fields['cod_vendedor']) &&  $fields['cod_vendedor'] == '001'){
                    $usuariorepresentante = '';
                    $representante = (isset($titulo->cheques->notas[0]->revisao_vendedor_comissao->usuario)) ? $usuariorepresentante = $titulo->cheques->notas[0]->revisao_vendedor_comissao->usuario->name : null;
                    $vendedor = (empty($usuariorepresentante) || $titulo->cheques->notas[0]->revisao_vendedor_comissao->vendedor_codigo == '001') ? true : false;

                    if($vendedor == true){
                        $retorno[] = [
                            'banco' => $titulo->banco.' - Cheque',
                            'numero_cheque' => $num_cheque,
                            'numero' => $titulo->chequeTitulo->pluck('titulo_numero')->implode(', '),
                            'parcela' => '',
                            'data_emissao' => $titulo->data_entrada,
                            'data_vencimento_sql' => $titulo->data_vencimento,
                            'data_vencimento' => parserData($titulo->data_vencimento),
                            'valor_original' => $titulo->valor,
                            'valor' => $titulo->valor,
                            'multa' => '',
                            'nome_cliente' => $titulo->cliente_nome. ' - ' .$titulo->cliente_cnpj,
                            'nota_numero' => $titulo->chequeTitulo->pluck('documento_numero')->filter()->unique()->implode(', '),
                            'juros_cobrados' => '',
                            'percentual_juros_diarios' => '',
                            'data_juros' => '',
                            'desconto' => '',
                            'POSICAO_CR' => '',
                            'POSICAO_CR_DESCRICAO' => '',
                        ];
                        $total['valor'] += $titulo->valor;
                        $total['saldo'] += $titulo->valor;
                    }
                }else{
                    $retorno[] = [
                        'banco' => 'Cheque',
                        'numero' => $titulo->chequeTitulo->pluck('titulo_numero')->implode(', '),
                        'numero_cheque' => $num_cheque,
                        'parcela' => '',
                        'data_emissao' => $titulo->data_entrada,
                        'data_vencimento_sql' => $titulo->data_vencimento,
                        'data_vencimento' => parserData($titulo->data_vencimento),
                        'valor_original' => $titulo->valor,
                        'valor' => $titulo->valor,
                        'multa' => '',
                        'nome_cliente' => $titulo->cliente_nome. ' - ' .$titulo->cliente_cnpj,
                        'nota_numero' => $titulo->chequeTitulo->pluck('documento_numero')->filter()->unique()->implode(', '),
                        'juros_cobrados' => '',
                        'percentual_juros_diarios' => '',
                        'data_juros' => '',
                        'desconto' => '',
                        'POSICAO_CR' => '',
                        'POSICAO_CR_DESCRICAO' => '',
                    ];
                    $total['valor'] += $titulo->valor;
                    $total['saldo'] += $titulo->valor;
                }
            }  
        }
        
        if($fields['banco'] !== 'Cheque' || $filter['aberturageral'] === 'true'){
            foreach($TitulosAberto as $titulo){
                if(isset($fields['cod_vendedor']) && $fields['cod_vendedor'] == '001'){
                    $usuariorepresentante = '';
                    $representante = (isset($titulo->notaDetalhes->revisao_vendedor_comissao->usuario)) ? $usuariorepresentante = $titulo->notaDetalhes->revisao_vendedor_comissao->usuario->name : null;
                    $vendedor = (empty($usuariorepresentante) || $titulo->notaDetalhes->revisao_vendedor_comissao->vendedor_codigo == '001') ? true : false;
                    
                    if($vendedor == true){
                        $linha = [];

                        $linha['banco'] = ($titulo->banco_nome === null) ? 'Carteira' : $titulo->banco_nome;
                        $linha['numero'] = (empty($titulo->numero)) ? '': $titulo->numero;
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
                        $linha['nota_numero'] = empty($titulo->nota_numero) ? '' : $titulo->nota_numero;
                        $linha['POSICAO_CR'] = '';
                        $linha['numero_cheque'] = '';
                        $linha['POSICAO_CR_DESCRICAO'] = $titulo->nossonumero;
                        $linha['observacao'] = $titulo->observacao;

                        $retorno[] =  $linha;

                        $total['valor'] += $titulo->valor;
                        $total['saldo'] += $titulo->saldotitulo;
                        $total['juros'] += $titulo->multa;
                    }
                }else{
                    $linha = [];

                    $linha['banco'] = ($titulo->banco_nome === null) ? 'Carteira' : $titulo->banco_nome;
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
                    $linha['nota_numero'] = empty($titulo->nota_numero) ? '' : $titulo->nota_numero;
                    $linha['juros_cobrados'] = $titulo->juros;
                    $linha['percentual_juros_diarios'] = $titulo->percentualjurosdiario;
                    $linha['data_juros'] = $titulo->datainiciomulta;
                    $linha['desconto'] = $titulo->desconto;
                    $linha['numero_cheque'] = '';
                    $linha['POSICAO_CR'] = '';
                    $linha['POSICAO_CR_DESCRICAO'] = $titulo->nossonumero;
                    $linha['observacao'] = $titulo->observacao;

                    $retorno[] =  $linha;

                    $total['valor'] += $titulo->valor;
                    $total['saldo'] += $titulo->saldotitulo;
                    $total['juros'] += $titulo->multa;
                }
            } 
        }

        $total = [
            'valor' => ($total['valor'] > 0) ? parserValor($total['valor']) : '',
            'saldo' => ($total['saldo'] > 0) ? parserValor($total['saldo']) : '',
            'juros' => ($total['juros'] > 0) ? parserValor($total['juros']) : '',
        ];
        
        return view('programs.duplicatas_cheque_banco.modal.titulos_faturados')->with(["dados"=> $retorno,"cod_cliente" => $cnpjCliente, "totalizadores" => $total, "cheque" => $num_cheque]);
    }

    public function modalTitulosAbertura(Request $request){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $filter = $request->only(['filters','abertura','total','data_filtro']);
        
        
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
                select codigo, saldotitulo, null as nota_id, documento_numero as nota_numero, numero, banco_nome, parcela, emissao as titulo_emissao, vencimento, tem_prorrogacao, valor, multa, nome_cliente, cliente_cnpj as cnpj, juros, percentualjurosdiario, datainiciomulta, desconto, nossonumero, observacao, vencimento_original
                from ' . $TitulosVendedor998NasajonObj->getTable() . '
                where saldotitulo > 0 and situacao = \'Aberto\'';
        }else{
            $queryAbertos = 'with titulos as(
                select "codigo", "saldotitulo", "nota_id", "numero", "nota_numero", "parcela", "banco_nome", "titulo_emissao", "vencimento", "tem_prorrogacao", "valor", "multa", "nome_cliente", "cnpj", "juros", "percentualjurosdiario", "datainiciomulta", "desconto", "nossonumero",  "observacao", "vencimento_original"
                from ' . $titulosEmAbertoNasajonObj->getTable() . '
                where "saldotitulo" > 0 ';
        }
        
        $ChequesAberto = ChequeNasajon::with('chequeTitulo')
            ->whereNotIn("cliente_codigo", $cnpj)
            ->whereNotIn('status', ["Compensado", 'Depositado', 'Devolvido (Tratado)']);

        $semrelacaocheque = (isset($fields['cheque'])) ? ChequesEmAbertoNasajon::whereDoesntHave('cheques')->get()->pluck('cod_cliente')->toArray() : null;

        if($filter['data_filtro'] =='vencimento') {

            if(!empty($fields['data_inicio'])){
                 $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
                 $queryAbertos .= ' AND vencimento >= \'' . $data_inicio->format('Y-m-d') . '\'';
                 $ChequesAberto->where('data_vencimento','>=', $data_inicio->format('Y-m-d'));
             }
             
             if(!empty($fields['data_fim'])){
                 $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
                 $queryAbertos .= ' AND vencimento <= \'' . $data_fim->format('Y-m-d') . '\'';
                 $ChequesAberto->where('data_vencimento','<=', $data_fim->format('Y-m-d'));
             }
      }else{
         if(!empty($fields['data_inicio'])){
             $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
             $queryAbertos .= ' AND titulo_emissao >= \'' . $data_inicio->format('Y-m-d') . '\'';
             $ChequesAberto->where('data_entrada','>=', $data_inicio->format('Y-m-d'));
         }
         
         if(!empty($fields['data_fim'])){
             $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
             $queryAbertos .= ' AND titulo_emissao <= \'' . $data_fim->format('Y-m-d') . '\'';
             $ChequesAberto->where('data_entrada','<=', $data_fim->format('Y-m-d'));
         }
      }

        $queryAbertos .= ' AND codigo not in (\'25\',\'20\',\'TREINAMENTO\') AND cod_cliente not in (\''. implode('\',\'', $cnpj) . '\')';

        if($filter['total'] !== 'true' && $fields['banco'] !== 'Cheque'){ 
            if($fields['banco'] === 'Carteira'){
                $queryAbertos .= ' AND (banco_nome = \''. $fields['banco'] .  '\' OR banco_nome is null)';
            }else{
                $queryAbertos .= ' AND banco_nome = \'' . $fields['banco'].'\'';
            }
        }
        $data = Carbon::now();
        if($filter['abertura'] == 'aberto'){    
            $queryAbertos .= ' AND (
                CASE
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END >= \''. $data->format('Y-m-d') .'\')';

            $ChequesAberto->whereRaw('(
                CASE
                    WHEN date_part(\'isodow\', data_vencimento) = 6 THEN (data_vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', data_vencimento) = 7 THEN (data_vencimento + interval \'1\' day)
                    ELSE data_vencimento
                END >= \''. $data->format('Y-m-d') .'\')'
            );

        }else if($filter['abertura'] == 'vencido'){
            $queryAbertos .= ' AND (
                CASE
                    WHEN date_part(\'isodow\', vencimento) = 6 THEN (vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', vencimento) = 7 THEN (vencimento + interval \'1\' day)
                    ELSE vencimento
                END < \''. $data->format('Y-m-d') .'\')';

            $ChequesAberto->whereRaw('(
                CASE
                    WHEN date_part(\'isodow\', data_vencimento) = 6 THEN (data_vencimento + interval \'2\' day)
                    WHEN date_part(\'isodow\', data_vencimento) = 7 THEN (data_vencimento + interval \'1\' day)
                    ELSE data_vencimento
                END < \''. $data->format('Y-m-d') .'\')'
            );

        }

        if(strtolower(Auth::user()->tipo_usuario_id) === "19" && empty($fields['vendedor_representante'])){
            $usuariogerente = Auth::user()->id;
            $representantes = User::where(function($query) use($usuariogerente){
                    $query->where('responsavel',$usuariogerente)
                        ->orWhere('id', Auth::id());
                })
                ->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

                if(!in_array('998', $representantes)){
                    $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
                }else{
                    $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                        where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                        and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                    )';
                }

            $ChequesAberto->whereHas('chequeTitulo', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('nota',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo', $representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo', $representantes);
                        });
                    });
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
            $representantes = User::where('responsavel',$responsavel)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }
            
            $ChequesAberto->whereHas('chequeTitulo', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('nota',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
            }
        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('chequeTitulo', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('nota',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
            }
        }
    
        if(strtolower(Auth::user()->tipo_usuario_id) === "19" && empty($fields['vendedor_representante'])){
            $usuariogerente = Auth::user()->id;
            $representantes = User::where(function($query) use($usuariogerente){
                    $query->where('responsavel',$usuariogerente)
                        ->orWhere('id', Auth::id());
                })
                ->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
                
            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('cheque', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('notaTitulo',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
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
            $representantes = User::where('responsavel',$responsavel)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }

            $ChequesAberto->whereHas('chequeTitulo', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('nota',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
            }
        }else if(strtolower(Auth::user()->tipo_usuario_id) == "16" && empty($fields['cliente_nome']) ||
            strtolower(Auth::user()->tipo_usuario_id) == "12" && empty($fields['cliente_nome'])){
            $userid = Auth::user()->id;
            $representantes = User::where('id',$userid)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();

            if(!in_array('998', $representantes)){
                $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
            }else{
                $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                    where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                    and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                )';
            }
            
            $ChequesAberto->whereHas('chequeTitulo', function ($querycheque) use ($representantes){ 
                $querycheque->whereHas('nota',function ($querys) use ($representantes){
                    $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                        $query_vendedor->whereIn('vendedor_codigo',$representantes);
                    }); 
                });
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                        });
                    });
                });
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
                $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial'); })->get()->pluck('id');

                $representantes = User::with(['tipo_usuario'])
                    ->where(function ($query) use($gerentes){
                        $query->whereIn('responsavel', $gerentes)
                            ->orWhereIn('id', $gerentes);
                    })
                    ->pluck('codigo_representante')
                    ->toArray();

                    $queryAbertos .= ' AND (vendedor_codigo not in (\'' . implode('\',\'', $representantes) . '\') or vendedor_codigo is null)';

                $ChequesAberto->where(function($chequeTituloQuery) use($representantes){
                    $chequeTituloQuery->whereHas('chequeTitulo', function ($query) use ($representantes){ 
                        $query->where(function($querycheque) use($representantes){
                            $querycheque->whereHas('nota',function ($querys) use ($representantes){
                                $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                                    $query_vendedor->whereNotIn('vendedor_codigo',$representantes)
                                    ->orWhereNull('vendedor_codigo');
                                })
                                ->orWhereDoesntHave('revisao_vendedor_comissao');
                            })
                            ->orWhereDoesntHave('notas');
                        });
                    })
                    ->orWhereDoesntHave('chequeTitulo');
                });
                if(!empty($semrelacaocheque)){
                    $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                        $query->whereIn('codigo',$semrelacaocheque)
                        ->where(function($query) use($representantes){
                            $query->whereHas('notasVenda', function($querynota) use ($representantes){
                                $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                                    $queryvendedor->whereNotIn('vendedor_codigo',$representantes)
                                    ->orWhereNull('vendedor_codigo');
                                })
                                ->orWhereDoesntHave('revisao_vendedor_comissao');
                            })
                            ->orWhereDoesntHave('notasVenda');
                        });
                    });
                }
            }
            else{
                $gerente_representante = User::with('subordinados')->find($gerente);
                $representantes = $gerente_representante->subordinados->pluck('codigo_representante')->filter()->toArray();

                if(!empty($gerente_representante->codigo_representante)){
                    $representantes[] = $gerente_representante->codigo_representante;
                }

                if(!in_array('998', $representantes)){
                    $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';
                }else{
                    $queryAbertos .= ' AND exists ( select * from integracoes.vw_titulosreceber_vendedores vdv
                        where vdv.tituloreceber  = vw_titulosemaberto_portal.titulo_id  
                        and vdv.vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\'
                    )';
                }
                
                $ChequesAberto->whereHas('chequeTitulo', function ($querycheque) use ($representantes){ 
                    $querycheque->whereHas('nota',function ($querys) use ($representantes){
                        $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                            $query_vendedor->whereIn('vendedor_codigo',$representantes);
                        }); 
                    });
                });
                if(!empty($semrelacaocheque)){
                    $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                        $query->whereIn('codigo',$semrelacaocheque);
                        $query->whereHas('notasVenda', function($querynota) use ($representantes){
                            $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                                $queryvendedor->whereIn('vendedor_codigo',$representantes);
                            });
                        });
                    });
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
                $ChequesAberto->whereIn('cliente_codigo', $clientecodigo);
            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
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
            $representantes = User::where('id',$vendedor)->whereNotNull('codigo_representante')->get()->pluck('codigo_representante')->toArray();
            
            $queryAbertos .= ' AND (vendedor_codigo in (\'' . implode('\',\'', $representantes) . '\')';

            if(in_array('001', $representantes)){
                $queryAbertos = ' or revisao_vendedor_comissao is null';
            }

            $queryAbertos .= ')';

            $ChequesAberto->where(function($chequeTituloQuery) use($representantes){
                $chequeTituloQuery->whereHas('chequeTitulo', function ($querycheque) use ($representantes){ 
                    $querycheque->whereHas('nota',function ($querys) use ($representantes){
                        $querys->whereHas('revisao_vendedor_comissao', function ($query_vendedor) use ($representantes){
                            $query_vendedor->whereIn('vendedor_codigo',$representantes);
                            if(in_array('001', $representantes)){
                                $query_vendedor->orWhereNull('vendedor_codigo');
                            }
                        }); 
                        if(in_array('001', $representantes)){
                            $querys->orWhereDoesntHave('revisao_vendedor_comissao');
                        }
                    });
                });
                if(in_array('001', $representantes)){
                    $chequeTituloQuery->orWhereDoesntHave('chequeTitulo');
                }
            });
            if(!empty($semrelacaocheque)){
                $ChequesAberto->whereHas('cliente', function($query) use ($semrelacaocheque,$representantes){
                    $query->whereIn('codigo',$semrelacaocheque);
                    $query->whereHas('notasVenda', function($querynota) use ($representantes){
                        $querynota->whereHas('revisao_vendedor_comissao', function($queryvendedor) use ($representantes){
                            $queryvendedor->whereIn('vendedor_codigo',$representantes);
                            if(in_array('001', $representantes)){
                                $queryvendedor->orWhereNull('vendedor_codigo');
                            }
                        });
                        if(in_array('001', $representantes)){
                            $querynota->orWhereDoesntHave('revisao_vendedor_comissao');
                        }
                    });
                });
            }
        }

        $queryAbertos .= ')
            select *
                from titulos t
                
            ';

        if((!empty(Auth::user()->codigo_representante) && Auth::user()->codigo_representante == '998') || (isset($representante) && $representante == '998')){
            $TitulosAberto = collect(DB::connection('nasajon')->select($queryAbertos))->unique();
        }else{
            $TitulosAberto = collect(DB::connection('nasajon')->select($queryAbertos));
        }

        $ChequesAberto = (isset($fields['cheque'])) ? $ChequesAberto->get() : null;
        $estabelecimentos = returnEmpresasNasajonView();
        $cnpjCliente = [];
        $cnpjCliente[] = ['cnpj' =>  'cnpj'];
        $cnpjCliente[] = ['result' =>  '2'];
        $retorno = [];
        $num_cheque = null;


        if($fields['cheque'] !== null && $fields['banco'] === 'Cheque' || $fields['banco'] === null && isset($ChequesAberto)){
            foreach($ChequesAberto as $titulo){
                $num_cheque = $titulo->numero_cheque;
                $retorno[] = [
                    'banco' => $titulo->banco.' - Cheque',
                    'numero' => $titulo->chequeTitulo->pluck('titulo_numero')->implode(', '),
                    'numero_cheque' => $num_cheque,
                    'parcela' => '',
                    'data_emissao' => $titulo->data_entrada,
                    'data_vencimento_sql' => $titulo->data_vencimento,
                    'data_vencimento' => parserData($titulo->data_vencimento),
                    'valor_original' => $titulo->valor,
                    'valor' => $titulo->valor,
                    'multa' => '',
                    'nome_cliente' => $titulo->cliente_nome. ' - ' .$titulo->cliente_cnpj,
                    'nota_numero' =>  $titulo->chequeTitulo->pluck('documento_numero')->filter()->unique()->implode(', '),
                    'juros_cobrados' => '',
                    'percentual_juros_diarios' => '',
                    'data_juros' => '',
                    'desconto' => '',
                    'POSICAO_CR' => '',
                    'POSICAO_CR_DESCRICAO' => '',
                ];
            }  
        }
        
        if($fields['banco'] !== 'Cheque'){
            foreach($TitulosAberto as $titulo){
                $linha = [];

                $linha['banco'] = $titulo->banco_nome === null ? 'Carteira' : $titulo->banco_nome;
                $linha['nota_numero'] = (!empty($titulo->nota_numero)) ? $titulo->nota_numero : '';
                $linha['parcela'] = $titulo->parcela;
                $linha['data_emissao'] = $titulo->titulo_emissao;
                $linha['data_vencimento_sql'] = $titulo->vencimento;
                $linha['data_vencimento'] = parserData($titulo->vencimento);
                $linha['valor_original'] = $titulo->valor;
                if($titulo->tem_prorrogacao === true ){
                    $linha['data_vencimento'] .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($titulo->vencimento_original).'"> *</a>';
                }
                $linha['valor'] = $titulo->saldotitulo;
                $linha['multa'] = $titulo->multa;
                $linha['nome_cliente'] = $titulo->nome_cliente. ' - ' .$titulo->cnpj;
                $linha['numero'] = $titulo->numero;
                $linha['juros_cobrados'] = $titulo->juros;
                $linha['percentual_juros_diarios'] = $titulo->percentualjurosdiario;
                $linha['data_juros'] = $titulo->datainiciomulta;
                $linha['desconto'] = $titulo->desconto;
                $linha['POSICAO_CR'] = '';
                $linha['numero_cheque'] = '';
                $linha['POSICAO_CR_DESCRICAO'] = $titulo->nossonumero;
                $linha['observacao'] = $titulo->observacao;

                $retorno[] = $linha;

            }   
        } 

        $total = [
            'valor' => 0,
            'saldo' => 0,
            'juros' => 0,
        ];

        if($fields['banco'] !== 'Cheque'){
            $valor = $TitulosAberto->sum('valor');
            $saldo = $TitulosAberto->sum('saldotitulo');
            $juros = $TitulosAberto->sum('juros');
        }

        if($fields['cheque'] !== null && $fields['banco'] === 'Cheque'){
            $valor = $ChequesAberto->sum('valor');
            $saldo = $ChequesAberto->sum('valor');
            $juros = $ChequesAberto->sum('juros');
        }

        if($filter['total'] === 'true' && isset($ChequesAberto)){
            $valor += $ChequesAberto->sum('valor');
            $saldo += $ChequesAberto->sum('valor');
        }

        unset($TitulosAberto);
        unset($ChequesAberto);
        $total = [
            'valor' => ($valor > 0) ? parserValor($valor) : '',
            'saldo' => ($saldo > 0) ? parserValor($saldo) : '',
            'juros' => ($juros > 0) ? parserValor($juros) : '',
        ];
        
        return view('programs.duplicatas_cheque_banco.modal.titulos_faturados')->with(["dados"=> $retorno,"cod_cliente" => $cnpjCliente, "totalizadores" => $total, "cheque" => $num_cheque]);
    }

    public function dataFiltro(){

        $data_filtro = [
            "vencimento" => 'Vencimento',
            "emissao" => 'Emissão',

        ];
        return $data_filtro;
    }
}
