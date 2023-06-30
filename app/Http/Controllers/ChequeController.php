<?php

namespace App\Http\Controllers;

use App\Cheque;
use App\ClienteNasajon;
use App\PedidosPrePago;
use App\GrupoEmpresarial;
use App\ChequesPedidosPrepagos;

use App\Http\Requests\ChequeGravarRequest;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

use Illuminate\Contracts\Encryption\DecryptException;

use Auth;

use Carbon\Carbon;

class ChequeController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Cheque") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Cheque');

        return view("programs.cheque.index");
    }

    public function filter(Request $request){

        $fields = $request->only('cliente', 'data_inicio', 'data_fim', 'tipo', 'status', 'data_modificacao_inicio', 'data_modificacao_fim', 'vencer_vencidos', 'valor_de', 'valor_ate');

        $chequeSql = Cheque::with('pedidos_prepagos', 'cliente_detalhes');

        if(isset($fields['cliente']) && !empty($fields['cliente'])){
            $clientes = ClienteNasajon::where(DB::Raw("TRIM(CONCAT(TRIM(nome), ' - ', cpf_cnpj))"), 'ilike', '%'. $fields['cliente'] .'%')->get();

            $chequeSql->whereIn('cliente_cpf_cnpj', $clientes->pluck('cpf_cnpj'));
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $dataInicio = Carbon::createFromFormat("d/m/Y", $fields['data_inicio']);
            $chequeSql->where('bom_para', '>=', $dataInicio->format('Y-m-d'));

        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $dataFim = Carbon::createFromFormat("d/m/Y", $fields['data_fim']);
            $chequeSql->where('bom_para', '<=', $dataFim->format('Y-m-d'));
        }

        if(isset($fields['data_modificacao_inicio']) && !empty($fields['data_modificacao_inicio'])){
            $dataModificacaoInicio = Carbon::createFromFormat("d/m/Y", $fields['data_modificacao_inicio']);
            $chequeSql->where('updated_at', '>=', $dataModificacaoInicio->format('Y-m-d 00:00:00'));

        }

        if(isset($fields['data_modificacao_fim']) && !empty($fields['data_modificacao_fim'])){
            $dataModificacaoFim = Carbon::createFromFormat("d/m/Y", $fields['data_modificacao_fim']);
            $chequeSql->where('updated_at', '<=', $dataModificacaoFim->format('Y-m-d 23:59:59'));
        }

        if(isset($fields['tipo']) && !empty($fields['tipo'])){
            $chequeSql->where('tipo', $fields['tipo']);
        }

        if(isset($fields['status']) && !empty($fields['status'])){
            $chequeSql->where('status', $fields['status']);
        }

        if(isset($fields['vencer_vencidos']) && !empty($fields['vencer_vencidos'])){
            if($fields['vencer_vencidos'] == 'a_vencer'){
                $chequeSql->where('bom_para', '>=', date('Y-m-d'));
            }
            else if($fields['vencer_vencidos'] == 'vencidos'){
                $chequeSql->where('bom_para', '<', date('Y-m-d'));
            }
        }
        
        if(isset($fields['valor_de']) && !empty($fields['valor_de'])){
            $valor_de = parserNumber($fields['valor_de']);
            $chequeSql->where('valor', '>=', $valor_de);
        }
        
        if(isset($fields['valor_ate']) && !empty($fields['valor_ate'])){
            $valor_ate = parserNumber($fields['valor_ate']);
            $chequeSql->where('valor', '<=', $valor_ate);
        }

        $chequesObj = $chequeSql->get();

        $resposta = [];

        $status = [
            'aberto' => 'Em aberto', 
            'baixado' => 'Baixado', 
            'devolvido' => 'Devolvido'
        ];

        $tipo = [
            'cheque' => 'Cheque',
            'deposito' => 'Depósito',
            'dinheiro' => 'Dinheiro',
            'cancelamento' => 'Cancelamento'
        ];

        $saldo_cheques = 0;
        $saldo_deposito = 0;
        $saldo_dinheiro = 0;

        $vencido = 0;
        $a_vencer = 0;

        $chequesObj->each( function($cheque) use (&$resposta, $status, $tipo, &$saldo_cheques, &$saldo_deposito, &$saldo_dinheiro, &$vencido, &$a_vencer){

            $linha = [];

            $linha['id'] = Crypt::encrypt($cheque->id);
            $linha['tipo'] = $tipo[$cheque->tipo];
            $linha['cliente'] = $cheque->cliente_detalhes->nome . ' - ' . $cheque->cliente_detalhes->cpf_cnpj;

            if($cheque->tipo != 'dinheiro'){
                $linha['banco'] = $cheque->banco;
                $linha['agencia'] = $cheque->agencia;
                $linha['conta'] = $cheque->conta;
                $linha['numero_cheque'] = $cheque->numero_cheque;

                if($cheque->tipo == 'cheque'){

                    $linha['bom_para'] = parserData($cheque->bom_para);
                    
                    if($cheque->status != 'devolvido'){
                        $saldo_cheques += $cheque->saldo;
                        $linha['saldo'] = parserValor($cheque->saldo);
                        if($cheque->status == 'aberto'){
                            $bomPara = Carbon::parse($cheque->bom_para)->setTime(0,0,0);
                            $agoraCarbon = Carbon::Now()->setTime(0,0,0);
                            if($bomPara->gte($agoraCarbon)){
                                $a_vencer += $cheque->saldo;
                            }else{
                                $vencido += $cheque->saldo;
                            }
    
                        }
                    }
                    else{
                        $linha['saldo'] = '';
                    }
                }
                else{
                    $linha['bom_para'] = '';
                    $saldo_deposito += $cheque->saldo;
                    $linha['saldo'] = parserValor($cheque->saldo);
                }
            }
            else{
                $linha['banco'] = '';
                $linha['agencia'] = '';
                $linha['conta'] = '';
                $linha['numero_cheque'] = '';
                $linha['bom_para'] = '';
                $linha['saldo'] = parserValor($cheque->saldo);

                $saldo_dinheiro += $cheque->saldo;
            }

            $linha['valor'] = parserValor($cheque->valor);

            $linha['ultima_modificacao'] = parserData($cheque->updated_at);

            $linha['status'] = $status[$cheque->status];

            if($cheque->pedidos_prepagos->isNotEmpty()){
                $linha['mostrar_exclusao'] = false;
            }
            else{
                $linha['mostrar_exclusao'] = true;
            }

            $resposta[] = $linha;

        });

        $totais = ['saldo' => [], 'vencido' => '', 'a_vencer' => ''];

        if($saldo_cheques > 0){
            $totais['saldo'][] = 'Cheques: ' . parserValor($saldo_cheques);
        }
        if($saldo_deposito > 0){
            $totais['saldo'][] = 'Depósito: ' . parserValor($saldo_deposito);
        }
        if($saldo_dinheiro > 0){
            $totais['saldo'][] = 'Dinheiro: ' . parserValor($saldo_dinheiro);
        }
        
        if($vencido > 0){
            $totais['vencido'] = 'Cheques: ' . parserValor($vencido);
        }
        if($a_vencer > 0){
            $totais['a_vencer'] = 'Cheques: ' . parserValor($a_vencer);
        }

        $totais['saldo'] = implode(' - ', $totais['saldo']);

        $retorno = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'totais' => $totais,
                'data' => $resposta
            ]
        ];

        return response()->json($retorno, 200);

    }

    public function modalNovo(Request $request){

        $status = [
            'aberto' => 'Em aberto', 
            'devolvido' => 'Devolvido'
        ];

        $tipo = [
            'cheque' => 'Cheque',
            'deposito' => 'Depósito',
            'dinheiro' => 'Dinheiro'
        ];

        return view('programs.cheque.modal.novo')->with(['status' => $status, 'tipo' => $tipo]);
    }

    public function modalEditar(Request $request){

        try {
            $id = Crypt::decrypt($request->id);
        } catch (DecryptException $e) {
            return response()->json($retorno = [
                'status' => 'error',
                'message' => 'Lançamento inválido',
                'error' => [
                    'id' => 'Lançamento inválido'
                ],
                'response' => [
                ]
            ], 422);
        }

        $linha = Cheque::with(['pedidos_prepagos', 'pedidos_prepagos.pedido', 'pedidos_prepagos.pedido.pedidoNasajon' => function($query){ $query->where('grupodeoperacao', 'VENDA'); }, 'pedidos_prepagos.pedido.pedidoNasajon.nota'])->find($id);

        if(is_null($linha)){
            return response()->json($retorno = [
                'status' => 'error',
                'message' => 'Cheque inválido',
                'error' => [
                    'id' => 'Cheque inválido'
                ],
                'response' => [
                ]
            ], 422);
        }

        $cheque = [];

        $cheque['id'] = Crypt::encrypt($linha->id);
        $cheque['cliente'] = $linha->cliente_detalhes->nome . ' - ' . $linha->cliente_detalhes->cpf_cnpj;
        $cheque['banco'] = $linha->banco;
        $cheque['agencia'] = $linha->agencia;
        $cheque['conta'] = $linha->conta;
        $cheque['numero_cheque'] = $linha->numero_cheque;
        $cheque['valor'] = parserValor($linha->valor);

        if($linha->tipo == 'cheque'){
            $cheque['bom_para'] = parserData($linha->bom_para);
        }
        else{
            $cheque['bom_para'] = '';
        }

        $cheque['status_selecionado'] = $linha->status;
        $cheque['tipo_lancamento'] = $linha['tipo'];

        $tipo = [
            'cheque' => 'Cheque',
            'deposito' => 'Depósito',
            'dinheiro' => 'Dinheiro',
            'cancelamento' => 'Cancelamento'
        ];

        if($linha->pedidos_prepagos->isNotEmpty()){
            $cheque['tipo'][$linha['tipo']] = $tipo[$linha['tipo']];
        }
        else{
            $cheque['tipo'] = $tipo;
        }


        $titulos = [];

        foreach($linha->pedidos_prepagos as $titulo){

            $resultado = [];

            $resultado['id'] = $titulo->pedido_prepago_id;

            $resultado['nota'] = $titulo->pedido->pedidoNasajon->nota->numero;
            $resultado['saldo'] = parserValor($titulo->pedido->valor - $titulo->pedido->valor_pago);
            $resultado['created_at'] = parserData($titulo->pedido->created_at);

            $titulos[] = $resultado;
        }

        $cheque['titulos'] = $titulos;

        return view('programs.cheque.modal.editar')->with($cheque);
    
    }

    public function modalExcluir(Request $request){
        try {
            $id = Crypt::decrypt($request->id);
        } catch (DecryptException $e) {
            return response()->json($retorno = [
                'status' => 'error',
                'message' =>'Lançamento inválido',
                'error' => [
                    'id' => 'Lançamento inválido'
                ],
                'response' => [
                ]
            ], 422);
        }

        $linha = Cheque::with('pedidos_prepagos')->find($id);

        if(is_null($linha)){
            return response()->json($retorno = [
                'status' => 'error',
                'message' => 'Cheque inválido',
                'error' => [
                    'id' => 'Cheque inválido'
                ],
                'response' => [
                ]
            ], 422);
        }
        else if($linha->pedidos_prepagos->isNotEmpty()){
            return response()->json($retorno = [
                'status' => 'error',
                'message' => 'Lançamento já utilizado em um pedido',
                'error' => [
                    'id' => 'Lançamento já utilizado em um pedido'
                ],
                'response' => [
                ]
            ], 422);
        }


        $cheque = [];

        $cheque['id'] = Crypt::encrypt($linha->id);
        $cheque['cliente'] = $linha->cliente_detalhes->nome . ' - ' . $linha->cliente_detalhes->cpf_cnpj;
        $cheque['banco'] = $linha->banco;
        $cheque['agencia'] = $linha->agencia;
        $cheque['conta'] = $linha->conta;
        $cheque['numero_cheque'] = $linha->numero_cheque;
        $cheque['valor'] = parserValor($linha->valor);
        $cheque['bom_para'] = parserData($linha->bom_para);
        
        $status = [
            'aberto' => 'Em aberto',
            'baixado' => 'Baixado', 
            'devolvido' => 'Devolvido'
        ];

        $cheque['status'] = $status[$linha->status];

        return view('programs.cheque.modal.excluir')->with($cheque);
    }

    public function adicionar(ChequeGravarRequest $request){

        $fields = $request->only('tipo', 'cliente', 'banco', 'agencia', 'conta', 'numero_cheque', 'valor', 'bom_para', 'status', 'pedidos_prepagos');

        $chequeObj = new Cheque;

        $clienteObj = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) = \''. $fields['cliente'] . '\'')->first();

        $chequeObj->tipo = $fields['tipo'];

        $chequeObj->cliente_cpf_cnpj = $clienteObj->cpf_cnpj;

        if($fields['tipo'] != 'dinheiro'){
            $chequeObj->banco = $fields['banco'];
            $chequeObj->agencia = $fields['agencia'];
            $chequeObj->conta = $fields['conta'];
            $chequeObj->numero_cheque = $fields['numero_cheque'];

            if($fields['tipo'] == 'cheque'){
                $chequeObj->bom_para = $fields['bom_para'];
            }
            else{
                $chequeObj->bom_para = null;
            }
        }
        else{
            $chequeObj->banco = null;
            $chequeObj->agencia = null;
            $chequeObj->conta = null;
            $chequeObj->numero_cheque = null;
            $chequeObj->bom_para = null;
        }

        $chequeObj->valor = parserNumber($fields['valor']);
        $chequeObj->saldo = parserNumber($fields['valor']);

        if($fields['tipo'] == 'cheque'){
            $chequeObj->status = $fields['status'];
        }
        else{
            $chequeObj->status = 'aberto';
        }

        $chequeObj->created_by = Auth::user()->id;

        $chequeObj->save();

        $retorno = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'id' => $chequeObj->id
            ]
        ];

        return response()->json($retorno, 200);

    }

    public function editar(ChequeGravarRequest $request){

        $fields = $request->only('id', 'tipo', 'cliente', 'banco', 'agencia', 'conta', 'numero_cheque', 'valor', 'bom_para', 'status', 'pedidos_prepagos');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (DecryptException $e) {
            return response()->json($retorno = [
                'status' => 'error',
                'message' => 'Lançamento inválido',
                'error' => [
                    'id' => 'Lançamento inválido'
                ],
                'response' => [
                ]
            ], 422);
        }

        $chequeObj = Cheque::with('pedidos_prepagos', 'pedidos_prepagos.pedido')->find($id);

        $clienteObj = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) = \''. $fields['cliente'] . '\'')->first();

        $chequeObj->cliente_cpf_cnpj = $clienteObj->cpf_cnpj;
        $chequeObj->tipo = $fields['tipo'];

        if($fields['tipo'] != 'dinheiro'){
            $chequeObj->banco = $fields['banco'];
            $chequeObj->agencia = $fields['agencia'];
            $chequeObj->conta = $fields['conta'];
            $chequeObj->numero_cheque = $fields['numero_cheque'];

            if($fields['tipo'] == 'cheque'){
                $chequeObj->bom_para = $fields['bom_para'];
            }
            else{
                $chequeObj->bom_para = null;
            }
        }
        else{
            $chequeObj->banco = null;
            $chequeObj->agencia = null;
            $chequeObj->conta = null;
            $chequeObj->numero_cheque = null;
            $chequeObj->bom_para = null;
        }
        
        $chequeObj->valor = parserNumber($fields['valor']);
        $chequeObj->saldo = parserNumber($fields['valor']);
        $chequeObj->status = $fields['status'];

        $chequeObj->updated_by = Auth::user()->id;

        if($chequeObj->save()){
            if($chequeObj->status == 'devolvido'){
                $chequeObj->pedidos_prepagos->each(function ($pedido){

                    $pedido->pedido->valor_pago -= $pedido->valor_pago;
                    $pedido->pedido->save();

                });

                $chequeObj->pedidos_prepagos()->delete();
            }
            else{

                $pedidos_pre_pagos = $fields['pedidos_prepagos']??[];

                $chequeObj->pedidos_prepagos->each(function ($pedido) use($pedidos_pre_pagos){
                    if(!in_array($pedido->pedido_prepago_id, $pedidos_pre_pagos)){
                        $pedido->pedido->valor_pago -= $pedido->valor_pago;
                        $pedido->pedido->save();

                        ChequesPedidosPrepagos::where('cheque_id', $pedido->cheque_id)
                            ->where('pedido_prepago_id', $pedido->pedido_prepago_id)
                            ->delete();
                    }
                });
            }
        }

        $retorno = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'id' => $chequeObj->id
            ]
        ];

        return response()->json($retorno, 200);
    }

    public function excluir(Request $request){

        try {
            $id = Crypt::decrypt($request->id);
        } catch (\Exception $e) {
            return response()->json($retorno = [
                'status' => 'error',
                'message' => 'Lançamento inválido',
                'error' => [
                    'id' => 'Lançamento inválido'
                ],
                'response' => [
                ]
            ], 422);
        }

        $chequeObj = Cheque::find($id);

        if($chequeObj->pedidos_prepagos->isNotEmpty()){
            return response()->json($retorno = [
                'status' => 'error',
                'message' => 'Lançamento já utilizado em um pedido',
                'error' => [
                    'id' => 'Lançamento já utilizado em um pedido'
                ],
                'response' => [
                ]
            ], 422);
        }

        $chequeObj->deleted_by = Auth::id();
        $chequeObj->save();
        $chequeObj->delete();

        $retorno = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'id' => $chequeObj->id
            ]
        ];

        return response()->json($retorno, 200);
    }

    public function recuperaTitulosParaCheques(Request $request){

        $cliente = ClienteNasajon::where(DB::Raw("TRIM(CONCAT(TRIM(nome), ' - ', cpf_cnpj))"), $request->cliente)->get();

        $pedidosPrePagos = PedidosPrePago::with('pedidoNasajon', 'pedidoNasajon.nota')
            ->whereRaw('valor > valor_pago')
            ->whereHas('pedido', function($query) use ($cliente){
                $query->whereIn('cod_cliente', $cliente->pluck('codigo'));
            })
            ->get();

        $resposta = [];

        $pedidosPrePagos->each(function($pedido) use(&$resposta){
            $linha = [];

            $linha['id'] = $pedido->id;
            $linha['nota'] = $pedido->pedidoNasajon->nota['numero'];
            $linha['saldo'] = parserValor($pedido->valor - $pedido->valor_pago);
            $linha['created_at'] = parserData($pedido->created_at);

            $resposta[] = $linha;
        });

        $retorno = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'data' => $resposta
            ]
        ];

        return response()->json($retorno, 200);
    }
    
    function baixarTitulos($fields, $chequeObj){

        $pedidos_pre_pagos = $fields['pedidos_prepagos'];

        $valor = $chequeObj->valor;

        $pedidosPrePagosObj = PedidosPrePago::whereIn('id', $pedidos_pre_pagos)->orderBy('created_at', 'asc')->get();

        $vinculados = [];

        $pedidosPrePagosObj->each(function ($linha) use(&$valor, &$vinculados){

            if($valor >= $linha->valor_restante){
                $linha->valor_pago += $linha->valor_restante;
                $valor -= $linha->valor_restante;
                $linha->valor_restante = 0;
            }
            else{
                $linha->valor_pago += $valor;
                $linha->valor_restante -= $valor;
                $valor = 0;
            }

            $linha->save();
            $vinculados[] = $linha->id;

            if($valor == 0){
                return false;
            }

        });

        $chequeObj->pedidos_prepagos()->sync($vinculados);
    }

    public function modaChequesCliente(Request $request, $coluna){
        $fields = $request->only('codigo', 'unico');

        $codigo = $fields['codigo'];
        $cliente = ClienteNasajon::select()->where('codigo', $codigo);
        $cliente = $cliente->first();
        $cliente = $cliente->toArray();

        $cpf_cnpj = $cliente['cpf_cnpj'];

        if(strlen(trim($cpf_cnpj)) == 18){
            $cpf_cnpj = substr($cpf_cnpj, 0, 10);
        }

        $clientesNasajonQuery = ClienteNasajon::select('*');

        if(!isset($fields['unico']) || is_null($fields['unico']) || $fields['unico'] == 'false'){

            $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
            ->where('raiz_cnpj', 'like', $cpf_cnpj . '%')
            ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                $query->where('raiz_cnpj', 'like', $cpf_cnpj . '%');
            })->first();

            if(!is_null($grupoEmpresarialObj)){
                $clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
                    if(isset($grupoEmpresarialObj->participantes)){
                        foreach($grupoEmpresarialObj->participantes as $participante){
                            $query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
                        }
                        $grupo[] = $participante->raiz_cnpj;
                    }
                    $query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
                });
            }
            else{
                $clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
            }    
        }
        else{
            $clientesNasajonQuery->where('cpf_cnpj', $cliente['cpf_cnpj']);
        }

        $clientesNasajon = $clientesNasajonQuery->get();

        $ChequesObj = Cheque::with('pedidos_prepagos')->whereIn('cliente_cpf_cnpj', $clientesNasajon->pluck('cpf_cnpj'))->whereHas('pedidos_prepagos')->where('bom_para', '>', Carbon::Now()->format('Y-m-d'))->where('tipo', 'cheque')->get();

        $cheques = ['total' => [] , 'a_vencer' => [], 'vencido' => []];
        $ChequesObj->each(function($cheque) use(&$cheques){
            $bomPara = Carbon::parse($cheque->bom_para)->setTime(0,0,0);
            $agoraCarbon = Carbon::Now()->setTime(0,0,0);
            $linha = [
                'cliente' => $cheque->cliente_detalhes->nome . ' - '. $cheque->cliente_detalhes->cpf_cnpj,
                'banco' => $cheque->banco,
                'agencia' => $cheque->agencia,
                'conta' => $cheque->conta,
                'numero' => $cheque->numero_cheque,
                'saldo' => $cheque->saldo,
                'saldo_sem_formatacao' => $cheque->saldo,
                'valor' => $cheque->valor,
                'valor_sem_formatacao' => $cheque->valor,
                'bom_para' => $bomPara->format('d/m/Y'),
                'bom_para_sem_formatacao' => $bomPara->format('Y-m-d'),
                'vinculados' => $cheque->pedidos_prepagos->map(function($pedido){
                        return '<a href=# onclick="showInfoTituloPrePago(\'' . $pedido->pedido_prepago_id . '\')">'. $pedido->pedido->pedido_nasajon_numero . '</a>' . ' - ' . parserValor($pedido->valor_pago);
                    })->implode(', ')
            ];
            if($bomPara->gte($agoraCarbon)){
                $cheques['a_vencer'][] = $linha;
            }else{
                $cheques['vencido'][] = $linha;
            }
            $cheques['total'][] = $linha;
        });
        $total = ['saldo' => 0, 'valor' => 0];
        $cheques = $cheques[$coluna];

        foreach($cheques as $key => $cheque){
            $total['saldo'] += $cheque['saldo'];
            $total['valor'] += $cheque['valor'];
            $cheques[$key]['saldo'] = parserValor($cheque['saldo']);
            $cheques[$key]['valor'] = parserValor($cheque['valor']);
        }
        $total['saldo'] = parserValor($total['saldo']);
        $total['valor'] = parserValor($total['valor']);
        return view('programs.cheque.modal.cliente')->with(['cheques' => $cheques, 'total' => $total]);
    }
}
