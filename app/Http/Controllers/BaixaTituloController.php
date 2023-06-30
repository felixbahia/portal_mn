<?php

namespace App\Http\Controllers;

use App\Cheque;
use App\ChequesPedidosPrepagos;
use App\ClienteNasajon;
use App\PedidosPrePago;
use App\PedidosVendaNasajon;
use App\TitulosEmAbertoNasajon;
use App\GrupoEmpresarial;
use App\TitulosPagosNasajon;
use App\User;
use App\BaixaTitulo;
use App\ContasNasajon;
use App\TitulosEmAbertoNasajonPortal;
use App\ClienteBlackList;
use App\ClienteBlackListTitulo;
use App\ClienteBlackListHistorico;

use App\Http\Requests\BaixaTitulosChequesRequest;
use App\Http\Requests\BaixarTituloNaNasajonRequest;

use Illuminate\Support\Facades\DB;

use Auth;
use Carbon\Carbon;

use Illuminate\Http\Request;

class BaixaTituloController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\BaixaTitulo") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BaixaTitulo');

        return view('programs.baixa_titulo.index');
    }

    public function recuperaTitulos(Request $request){

        $fields = $request->only('cliente');
        $cliente = ClienteNasajon::where(DB::Raw("CONCAT(TRIM(nome), ' - ', cpf_cnpj)"), $fields['cliente'])->first();

        if(!isset($fields['cliente']) || empty($fields['cliente']) || is_null($cliente)){
            $retorno = [
                'status' => 'error',
                'message' => '',
                'errors' => [
                    'cliente_nome_cpf_cnpj' => 'Cliente inválido'
                ],
                'response' => []
            ];
    
            return response()->json($retorno, 422);
        }


        $pedidoPrePagos = PedidosPrePago::with('pedido')
            ->whereColumn('valor_pago', '<', 'valor')
            ->whereHas('pedido', function ($query) use ($cliente){
                $query->where('cod_cliente', $cliente->codigo);
            })
            ->get();

        $cheques = Cheque::where('cliente_cpf_cnpj', $cliente->cpf_cnpj)
            ->where('saldo', '>', 0)
            ->whereNotIn('status', ['devolvido', 'baixado'])
            ->get();

        $pedidoPrePagos = $pedidoPrePagos->filter( function ($value){
            return !is_null($value->pedidoNasajon) && !is_null($value->pedidoNasajon->nota);
        });
    
        $resposta = [];

        $pedidoPrePagos->each(function ($pedido) use (&$resposta, $cliente){

            if(isset($pedido->pedidoNasajon->nota) && !is_null($pedido->pedidoNasajon->nota)){
                $nota_numero = $pedido->pedidoNasajon->nota->numero;
                $nota_id = $pedido->pedidoNasajon->nota->id;
                $emissao = parserData($pedido->pedidoNasajon->nota->emissao);
            }
            else{
                $nota_numero = 'Não emitida';
                $nota_id = '';
                $emissao = 'Não emitida';
            }


            $resposta[] = [
                'id' => $pedido->id,
                'pedido_numero' => $pedido->pedidoNasajon->numero,
                'pedido_id' => $pedido->pedidoNasajon->id,
                'nota_numero' => $nota_numero,
                'nota_id' => $nota_id,
                'emissao' => $emissao,
                'valor' => parserValor($pedido->valor),
                'saldo' => parserValor($pedido->valor - $pedido->valor_pago)
            ];
        });

        $retorno = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'pedidos' => $resposta,
                'total_saldo_cheques' => parserValor($cheques->sum('saldo')),
                'total_saldo_titulos' => parserValor($pedidoPrePagos->sum('valor') - $pedidoPrePagos->sum('valor_pago'))
            ]
        ];

        return response()->json($retorno, 200);
    }

    public function modalChequesBaixar(Request $request){

        $fields = $request->only('id');

        $titulo = PedidosPrePago::with('pedido', 'pedido.cliente')->find($fields['id']);

        $cliente = $titulo->pedido->cliente;

        $cheques = Cheque::where('cliente_cpf_cnpj', $cliente->cpf_cnpj)
            ->where('saldo', '>', 0)
            ->whereNotIn('status', ['devolvido', 'baixado'])
            ->get();

        $resposta = [];

        $info_titulo = [
            'id' => $titulo->id,
            'cliente' => $titulo->pedido->cliente->nome . ' - ' . $titulo->pedido->cliente->cpf_cnpj,
            'pedido_numero' => $titulo->pedidoNasajon->numero,
            'pedido_id' => $titulo->pedidoNasajon->id,
            'nota_id' => $titulo->pedidoNasajon->nota->id,
            'nota_numero' => $titulo->pedidoNasajon->nota->numero,
            'saldo_formatado' => parserValor($titulo->valor - $titulo->valor_pago),
            'saldo' => $titulo->valor - $titulo->valor_pago
        ];

        $tipo = [
            'cheque' => 'Cheque',
            'deposito' => 'Depósito',
            'dinheiro' => 'Dinheiro'
        ];

        $cheques->each(function ($cheque) use (&$resposta, $tipo){

            if($cheque->tipo == 'cheque'){
                $identificacao = $cheque->banco . '.' . $cheque->agencia . '.' . $cheque->conta . '.' . $cheque->numero_cheque;
                $bom_para = parserData($cheque->bom_para);
            }
            else if($cheque->tipo == 'deposito'){
                $identificacao = 'Banco: ' . $cheque->banco . ' - Agência: ' . $cheque->agencia . ' - Conta: ' . $cheque->conta;
                $bom_para = '';
            }
            else{
                $identificacao = '';
                $bom_para = '';
            }

            $resposta[] = [
                'id' => $cheque->id,
                'tipo' => $tipo[$cheque->tipo],
                'identificacao' => $identificacao,
                'valor' => parserValor($cheque->valor),
                'saldo' => parserValor($cheque->saldo),
                'bom_para' => $bom_para,
                'saldo_float' => $cheque->saldo,
            ];
        });

        return view('programs.baixa_titulo.modal.index')->with(['titulos' => $resposta, 'titulo' => $info_titulo]);

    }

    public function salvaCheques(BaixaTitulosChequesRequest $request){

        $fields = $request->only('titulo', 'cheque');

        $pedido = PedidosPrePago::find($fields['titulo']);
        $cheques = Cheque::with('pedidos_prepagos')->whereIn('id', $fields['cheque'])->get();

        $valor_restante = $pedido->valor - $pedido->valor_pago;

        $cheques->each(function ($cheque) use (&$pedido, &$valor_restante){

            $valor_pago = 0;

            if($valor_restante >= $cheque->saldo){
                
                $valor_pago = $cheque->saldo;

                $valor_restante -= $valor_pago;
                $pedido->valor_pago += $valor_pago;
                $cheque->saldo = 0;
                $cheque->status = 'baixado';

            } 
            else{
                $cheque->saldo -= $valor_restante;
                $pedido->valor_pago += $valor_restante;

                $valor_pago = $valor_restante;
                $valor_restante = 0;
            }

            $cheque->save();

            $chequePedido = ChequesPedidosPrepagos::where('cheque_id', $cheque->id)
                ->where('pedido_prepago_id', $pedido->id)
                ->first();

            if(!is_null($chequePedido)){
                $valor_pago += $chequePedido->valor_pago;

                ChequesPedidosPrepagos::where('cheque_id', $cheque->id)
                ->where('pedido_prepago_id', $pedido->id)->delete();
            }

            $chequePedido = new ChequesPedidosPrepagos;

            $chequePedido->cheque_id = $cheque->id;
            $chequePedido->pedido_prepago_id = $pedido->id;
            $chequePedido->valor_pago = $valor_pago;
            $chequePedido->created_by = Auth::id();
            $chequePedido->save();

            if($pedido->valor_pago == $pedido->valor){
                return false;
            }

        });

        $pedido->save();

        $retorno = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => ''
        ];

        return response()->json($retorno, 200);

    }

    public function modalTitulosParaBaixar(Request $request){
        $fields = $request->only('codcad', 'unico');

        $codigo = $fields['codcad'];
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
      
        $empresa = returnEmpresasNasajonView();
        $titulos_faturados = [];

        if(Auth::user()->hasRole('Juridico')){
            $titulosNasajon = TitulosEmAbertoNasajonPortal::select('*', DB::raw("regexp_replace(numero, '.[0-9]{5}.[0-9]{2}.', '') as nota"));
            $titulosNasajon->whereHas('tituloVendedor998');
        }else{
            $titulosNasajon = TitulosEmAbertoNasajon::select('titulo_id', 'codigo', 'nota_numero', 'parcela', 'titulo_emissao', 'vencimento', 'valor', 'saldotitulo', 'juros', 'nome_cliente', 'numero', 'percentualjurosdiario', 'datainiciomultaejuros', 'desconto', 'nossonumero', 'observacao', 'numero', 'cod_cliente', DB::raw("regexp_replace(numero, '.[0-9]{5}.[0-9]{2}.', '') as nota"));
            $titulosNasajon->groupBy('titulo_id', 'codigo', 'nota', 'nota_numero', 'parcela', 'titulo_emissao', 'vencimento', 'valor', 'saldotitulo', 'juros', 'nome_cliente', 'numero', 'percentualjurosdiario', 'datainiciomultaejuros', 'desconto', 'nossonumero', 'observacao', 'numero', 'cod_cliente');
            $titulosNasajon->distinct();
        }

        $titulosNasajon->with(['devolucoes' => function($query){
            $query->whereNotIn('devolucao_nota_status_id', [7, 8]);
        },'cenprot', 'cliente'])
        ->whereIn('cod_cliente', $clientesNasajon->pluck('codigo'));

        $titulosNasajon->orderBy('nota_numero')
            ->orderBy('parcela')
            ->get();

        $cnpjCliente = $clientesNasajon->pluck('codigo');

        $cliente     = false;

        $totalizadores = [
            'valor' => 0,
            'saldo' => 0,
            'juros' => 0,
        ];
        $titulosNasajon->each(function ($item) use (&$titulos_faturados, &$empresa, &$totalizadores, $titulosNasajon){       
            $nota_numero = '';
            
            if(empty($item->nota_numero)){
                $nota_numero = $item->nota;
            }else{
                $nota_numero = $item->nota_numero;
            }

            $value['titulo_id'] = $item->titulo_id;
            $value['estabelecimento'] = $item->codigo;
            $value['estabelecimento_nome']  = $empresa[(int) $item->codigo];
            $value['nota_numero']           = $nota_numero;
            $value['parcela']               = $item->parcela;
            $value['data_emissao']          = $item->titulo_emissao;
            $value['data_vencimento_sql']   = $item->vencimento;
            $value['data_vencimento']       = parserData($item->vencimento);
            $value['status']                = '';
            $value['valor_original']        = $item->valor;
            $value['valor']                 = $item->saldotitulo;
            $value['juros_cobrados']        = $item->juros;
            $value['nome_cliente']          = $item->nome_cliente .' - '. $item->cliente->cpf_cnpj;
            $value['numero']                = $item->numero;
            $value['percentual_juros_diarios']         = $item->percentualjurosdiario;
            $value['data_juros']            = $item->datainiciomultaejuros;
            $value['desconto']              = $item->desconto;
            $value['POSICAO_CR']            = $item->nossonumero;
            $value['POSICAO_CR_DESCRICAO']  = $item->nossonumero;
            $value['observacao']  = $item->observacao;

            $key = $item->numero . '' . $item->codigo;

            $titulos_faturados[$key] = $value;

            $totalizadores["valor"] += $item->valor;
            $totalizadores["saldo"] += $item->saldotitulo;
            $totalizadores["juros"] += $item->juros;

        });

        if($totalizadores['valor'] > 0){
            $totalizadores['valor'] = parserValor($totalizadores['valor']);
        }
        else{
            $totalizadores['valor'] = '';
        }
        
        if($totalizadores['saldo'] > 0){
            $totalizadores['saldo'] = parserValor($totalizadores['saldo']);
        }
        else{
            $totalizadores['saldo'] = '';
        }

        if($totalizadores['juros'] > 0){
            $totalizadores['juros'] = parserValor($totalizadores['juros']);
        }
        else{
            $totalizadores['juros'] = '';
        }

        return view('programs.baixa_titulo.modal.titulos_para_baixar')->with(["dados"=>$titulos_faturados,"cod_cliente" => $cnpjCliente, "totalizadores" => $totalizadores, "codigo" => $fields['codcad'], "unico" => $fields['unico']]);
    }

    public function filtroTitulosParaBaixar(Request $request){
        $fields = $request->only('codigo', 'unico', 'titulo');

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
      
        $empresa = returnEmpresasNasajonView();
        $titulos_faturados = [];

        if(Auth::user()->hasRole('Juridico')){
            $titulosNasajon = TitulosEmAbertoNasajonPortal::select('*', DB::raw("regexp_replace(numero, '.[0-9]{5}.[0-9]{2}.', '') as nota"));
            $titulosNasajon->whereHas('tituloVendedor998');
        }else{
            $titulosNasajon = TitulosEmAbertoNasajon::select('titulo_id', 'codigo', 'nota_numero', 'parcela', 'titulo_emissao', 'vencimento', 'valor', 'saldotitulo', 'juros', 'nome_cliente', 'numero', 'percentualjurosdiario', 'datainiciomultaejuros', 'desconto', 'nossonumero', 'observacao', 'numero', 'cod_cliente', DB::raw("regexp_replace(numero, '.[0-9]{5}.[0-9]{2}.', '') as nota"));
            $titulosNasajon->groupBy('titulo_id', 'codigo', 'nota', 'nota_numero', 'parcela', 'titulo_emissao', 'vencimento', 'valor', 'saldotitulo', 'juros', 'nome_cliente', 'numero', 'percentualjurosdiario', 'datainiciomultaejuros', 'desconto', 'nossonumero', 'observacao', 'numero', 'cod_cliente');
            $titulosNasajon->distinct();
        }

        $titulosNasajon->with(['devolucoes' => function($query){
            $query->whereNotIn('devolucao_nota_status_id', [7, 8]);
        },'cenprot', 'cliente'])
        ->whereIn('cod_cliente', $clientesNasajon->pluck('codigo'));

        if(!empty($fields['titulo'])){
            $titulosNasajon->where('numero', 'ilike', '%'.$fields['titulo'].'%');
        }

        $titulosNasajon->orderBy('nota_numero')
            ->orderBy('parcela')
            ->get();
        
        $cnpjCliente = $clientesNasajon->pluck('codigo');
       
        $cliente     = false;

        $totalizadores = [
            'valor' => 0,
            'saldo' => 0,
            'juros' => 0,
        ];
        $titulosNasajon->each(function ($item) use (&$titulos_faturados, &$empresa, &$totalizadores, $titulosNasajon){       
            $nota_numero = '';
            
            if(empty($item->nota_numero)){
                $nota_numero = $item->nota;
            }else{
                $nota_numero = $item->nota_numero;
            }

            $value['titulo_id'] = $item->titulo_id;
            $value['estabelecimento'] = $item->codigo;
            $value['estabelecimento_nome']  = $empresa[(int) $item->codigo];
            $value['nota_numero']           = $nota_numero;
            $value['parcela']               = $item->parcela;
            $value['data_emissao']          = parserData($item->titulo_emissao);
            $value['data_vencimento_sql']   = $item->vencimento;
            $value['data_vencimento']       = parserData($item->vencimento);
            $value['status']                = '';
            $value['valor_original']        = parserValor($item->valor);
            $value['valor']                 = parserValor($item->saldotitulo);
            $value['juros_cobrados']        = empty($item->juros)? '' : parserValor($item->juros);
            $value['nome_cliente']          = $item->nome_cliente .' - '. $item->cliente->cpf_cnpj;
            $value['numero']                = $item->numero;
            $value['percentual_juros_diarios']         = empty($item->percentualjurosdiario)? '' : parserValor($item->percentualjurosdiario);
            $value['data_juros']            = $item->datainiciomultaejuros;
            $value['desconto']              = empty($item->desconto)? '' : parserValor($item->desconto);
            $value['POSICAO_CR']            = $item->nossonumero;
            $value['POSICAO_CR_DESCRICAO']  = $item->nossonumero;
            $value['observacao']  = $item->observacao;

            $key = $item->numero . '' . $item->codigo;

            $titulos_faturados[$key] = $value;

            $totalizadores["valor"] += $item->valor;
            $totalizadores["saldo"] += $item->saldotitulo;
            $totalizadores["juros"] += $item->juros;

        });

        if($totalizadores['valor'] > 0){
            $totalizadores['valor'] = parserValor($totalizadores['valor']);
        }
        else{
            $totalizadores['valor'] = '';
        }
        
        if($totalizadores['saldo'] > 0){
            $totalizadores['saldo'] = parserValor($totalizadores['saldo']);
        }
        else{
            $totalizadores['saldo'] = '';
        }

        if($totalizadores['juros'] > 0){
            $totalizadores['juros'] = parserValor($totalizadores['juros']);
        }
        else{
            $totalizadores['juros'] = '';
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'titulos_faturados' => $titulos_faturados,
                'totalizadores' => $totalizadores,
            ],
        ];
        return response()->json($response);
    }

    public function modalBaixarTitulo(Request $request){
        $fields = $request->only('titulo_id');

        $titulo = TitulosEmAbertoNasajon::
            select('numero', 'valor', 'saldotitulo', 'juros', 'desconto', 'titulo_emissao', 'titulo_id', 'origem', DB::raw('(valor+juros-saldotitulo) as pago'))->where('titulo_id', $fields['titulo_id'])->first();

        $data_minima = Carbon::now()->subDays(30)->format('d/m/Y');

        $query_conta_bancaria = ContasNasajon::select();
        $query_conta_bancaria->orderBy('codigo');
        $result_conta_bancaria = $query_conta_bancaria->get();

        $contas_bancarias = [];
        foreach($result_conta_bancaria as $conta_bancaria){
            $contas_bancarias[$conta_bancaria->conta] = $conta_bancaria->codigo." - ".$conta_bancaria->nome;
        }

        $titulo_id = $fields['titulo_id'];
        $titulo_numero = $titulo->numero;
        $titulo_valor = parserValor($titulo->valor);
        $saldo_valor = empty($titulo->saldotitulo)? '' : parserValor($titulo->saldotitulo);
        $juros_valor = empty($titulo->juros)? '' : parserValor($titulo->juros);
        $desconto_valor = empty($titulo->desconto)? '' : parserValor($titulo->desconto);
        $pago_valor = empty(floatval($titulo->pago))? '' : parserValor($titulo->pago);

        $titulo_emissao = Carbon::parse($titulo->titulo_emissao);
        $verificacao_emissao = Carbon::parse('2022-07-01');

        if($titulo_emissao->lt($verificacao_emissao)){
            $data_emissao = $titulo->titulo_emissao;
        }else{
            $data_emissao = '2022-07-01';
        }        

        $conta_bancaria_ragazzi = $this->getContaBancariaRagazzi();

        if(!empty($titulo->tituloNovoRenegociado) || $titulo->origem == 25){
            $renegociado = true;
        }else{
            $renegociado = false;
        }

        return view('programs.baixa_titulo.modal.baixar_titulo')->with(['titulo_id' => $titulo_id, 'saldo_valor' => $saldo_valor, 'data_minima' => $data_minima, 'juros_valor' => $juros_valor, 'desconto_valor' => $desconto_valor, 'contas_bancarias' => $contas_bancarias, 'titulo_numero' => $titulo_numero, 'pago_valor' => $pago_valor, 'titulo_valor' => $titulo_valor, 'conta_bancaria_ragazzi' => $conta_bancaria_ragazzi, 'data_emissao' => $data_emissao, 'renegociado' => $renegociado]);
    }

    public function baixarTituloNaNasajon(BaixarTituloNaNasajonRequest $request){
        $fields = $request->only('titulo_id', 'tipo', 'conta_bancaria', 'valor_recebido_parcial', 'valor_recebido', 'juros', 'desconto', 'taxa_boleto_banco', 'honorarios_cliente', 'comissao_cobranca', 'data_baixa', 'tarifa_bancaria_mn', 'multa');

        $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;

        $titulo = TitulosEmAbertoNasajon::select()->where('titulo_id', $fields['titulo_id'])->first();

        $data_baixa = Carbon::createFromFormat('d/m/Y',$fields['data_baixa']);
        $conta_bancaria = ContasNasajon::select()->where('conta', $fields['conta_bancaria'])->first();

        $nota_emissao = Carbon::parse($titulo->titulo_emissao);
        $verificacao_emissao = Carbon::parse('2022-07-01');

        if($fields['tipo'] === "total"){
            $juros_titulos = empty($titulo->juros)? 0 : floatval($titulo->juros);
            $saldo_titulo = floatval($titulo->saldotitulo) - $juros_titulos; 
            $valor_recebido = parserNumber($fields['valor_recebido']);
            $tarifa_bancaria_mn = empty($fields['tarifa_bancaria_mn'])? 0 : parserNumber($fields['tarifa_bancaria_mn']);
            $taxa_boleto_banco_valor = empty($fields['taxa_boleto_banco'])? 0 : parserNumber($fields['taxa_boleto_banco']);
            $honorarios_cliente_valor = empty($fields['honorarios_cliente'])? 0 : parserNumber($fields['honorarios_cliente']);
            $comissao_cobranca_valor = empty($fields['comissao_cobranca'])? 0 : parserNumber($fields['comissao_cobranca']);
            $desconto_valor = empty($fields['desconto'])? 0 : parserNumber($fields['desconto']);
            if($nota_emissao->lt($verificacao_emissao)){
                $multa_valor = 0;
                $multa_porcetagem = 0;
                $multa = empty($fields['multa'])? 0 : parserNumber($fields['multa']);
                $juros_valor = empty($fields['juros'])? 0 : parserNumber($fields['juros']);
                $juros_valor += $multa + $taxa_boleto_banco_valor + $honorarios_cliente_valor + $tarifa_bancaria_mn;
                if(!empty($titulo->tituloNovoRenegociado) || $titulo->origem == 25){
                    $juros_valor = 0;
                }
            }else{
                $desconto_valor += $taxa_boleto_banco_valor + $honorarios_cliente_valor + $comissao_cobranca_valor + $tarifa_bancaria_mn;
                $multa_valor = 0;
                $multa_porcetagem = 0;
                $multa = empty($fields['multa'])? 0 : parserNumber($fields['multa']);
                $juros_valor = empty($fields['juros'])? 0 : parserNumber($fields['juros']);
                $juros_valor += $multa;
                $valor_recebido = $valor_recebido - $taxa_boleto_banco_valor - $honorarios_cliente_valor - $comissao_cobranca_valor - $tarifa_bancaria_mn; 
            }
            
            
            $observacao = "Baixa Total pelo Portal, usuário:".Auth::user()->name." data: ".date('Y-m-d H:i:s').".";
            $quitartitulo = 'true';
            $quitar_bool = true;
            
            $juros_porcetagem = empty($fields['juros'])? 0 : $juros_valor / $valor_recebido * 100;
            $desconto_porcetagem = empty($fields['desconto'])? 0 : $desconto_valor / $valor_recebido * 100;

            if($desconto_valor > $juros_valor){
                $this->verificarClienteBlackList($juros_valor, $desconto_valor, $titulo->cnpj, $titulo->numero, $titulo->titulo_id);
            }
        }else{
            $valor_recebido = parserNumber($fields['valor_recebido_parcial']);
            $desconto_valor = 0;
            $desconto_porcetagem = 0;
            $multa_valor = 0;
            $multa_porcetagem = 0;
            $juros_valor = 0;
            $juros_porcetagem = 0;
            $observacao = "Baixa Parcial pelo Portal, usuário:".Auth::user()->name." data: ".date('Y-m-d H:i:s').".";
            $quitartitulo = 'false';
            $quitar_bool = false;
        }
        
        try{
            $sql_baixa_titulo = "select * from integracoes.api_baixartituloreceber(
                '".$titulo->titulo_id."',
                '".$conta_bancaria->conta."',
                '".$data_baixa->format('Y-m-d')."',
                ".$valor_recebido.",
                ".$desconto_porcetagem.",
                ".$desconto_valor.",
                ".$juros_porcetagem.", 
                ".$juros_valor.",
                ".$multa_porcetagem.",
                ".$multa_valor.",
                '".$observacao."',
                '".$usuario_cadastro_uuid."',
                ".$quitartitulo."
                );";
            $baixar_nasajon = DB::connection('nasajon')->select($sql_baixa_titulo);
        }catch(\Exception $e){
            return  response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$e, $sql_baixa_titulo],
                'response' => []
            ], 422);
        }

        $retorno_nasajon = (array) reset($baixar_nasajon);
        $retorno_nasajon = $retorno_nasajon['mensagem'];
        $retorno_nasajon = \json_decode($retorno_nasajon, true);

        $baixaTituloObj = new BaixaTitulo();
        $baixaTituloObj->titulo_nasajon_id = $titulo->titulo_id;
        $baixaTituloObj->titulo_numero = $titulo->numero;
        $baixaTituloObj->data_baixa = $data_baixa;
        $baixaTituloObj->valor_recebido = empty($fields['valor_recebido'])? 0 : parserNumber($fields['valor_recebido']);
        $baixaTituloObj->desconto_valor = empty($fields['desconto'])? 0 : parserNumber($fields['desconto']);
        $baixaTituloObj->taxa_boleto_banco_valor = empty($fields['taxa_boleto_banco'])? 0 : parserNumber($fields['taxa_boleto_banco']);
        $baixaTituloObj->honorarios_cliente_valor = empty($fields['honorarios_cliente'])? 0 : parserNumber($fields['honorarios_cliente']);
        $baixaTituloObj->comissao_cobranca_valor = empty($fields['comissao_cobranca'])? 0 : parserNumber($fields['comissao_cobranca']);
        $baixaTituloObj->juros_valor = empty($fields['juros'])? 0 : parserNumber($fields['juros']);
        $baixaTituloObj->tarifa_bancaria_mn = empty($fields['tarifa_bancaria_mn'])? 0 : parserNumber($fields['tarifa_bancaria_mn']);
        $baixaTituloObj->multa = empty($fields['multa'])? 0 : parserNumber($fields['multa']);
        $baixaTituloObj->quitar = $quitar_bool;
        $baixaTituloObj->observacao = $observacao;
        $baixaTituloObj->retorno_nasajon = 'Codigo : '.$retorno_nasajon['codigo'].' - Mensagem: '.$retorno_nasajon['mensagem'].' - Tipo: '.$retorno_nasajon['tipo'];
        $baixaTituloObj->created_by = Auth::id();
        $baixaTituloObj->save();

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [],
        ]);
    }

    private function ajusteArrayParaValores($array){
        if(is_array($array)){
            foreach($array as $key => $value){
                if(is_array($value)){
                    $array[$key] = $this->ajusteArrayParaValores($value);
                }else{
                    if(is_numeric($value)){
                        if(substr_count($key, "codigo") === 0){
                            if(substr_count($key, "porcetagem") === 0){
                                $array[$key] = empty($value)? '': parserValor($value);
                            }else{
                                $array[$key] = empty($value)? '': parserValor($value).'%';
                            }                            
                        }
                    }else{
                        if(substr_count($key, "data") === 0){
                            $array[$key] = $value;
                        }else{
                            $array[$key] = parserData($value);
                        }                        
                    }
                }
            }
        }
        return $array;
    }


    private function getContaBancariaRagazzi(){
        $query_conta_bancaria = ContasNasajon::select();
        $query_conta_bancaria->where('codigo', '685813');
        $result_conta_bancaria = $query_conta_bancaria->first();

        return $result_conta_bancaria->conta;
    }

    private function verificarClienteBlackList($juros, $desconto, $cliente_cnpj, $titulo_numero, $titulo_id){
        $clienteBlackListObj = ClienteBlackList::select();
        $clienteBlackListObj->where('cpf_cnpj', $cliente_cnpj);
        $clienteBlackListObj = $clienteBlackListObj->first();

        if(empty($clienteBlackListObj)){
            $clienteBlackListObj = new ClienteBlackList;
            $clienteBlackListObj->cpf_cnpj = $cliente_cnpj;
            $clienteBlackListObj->created_by = Auth::id();
            $clienteBlackListObj->status_cliente_black_lists_id = 3;
            $clienteBlackListObj->save();
        }else{
            $clienteBlackListObj->cpf_cnpj = $cliente_cnpj;
            $clienteBlackListObj->created_by = Auth::id();
            $clienteBlackListObj->status_cliente_black_lists_id = 3;
            $clienteBlackListObj->save();
        }

        $clienteBlackListTituloObj = new ClienteBlackListTitulo;
        $clienteBlackListTituloObj->cliente_black_lists_id = $clienteBlackListObj->id;
        $clienteBlackListTituloObj->titulo_uuid_nasajon = $titulo_id;
        $clienteBlackListTituloObj->titulo_numero = $titulo_numero;
        $clienteBlackListTituloObj->created_by = Auth::id();
        $clienteBlackListTituloObj->save();

        $clienteBlackListHistoricoObj = new ClienteBlackListHistorico;
        $clienteBlackListHistoricoObj->cpf_cnpj = $cliente_cnpj;
        $clienteBlackListHistoricoObj->titulo = $titulo_numero;
        $clienteBlackListHistoricoObj->motivo = 'Baixa do Tìtulo Com Desconto';
        $clienteBlackListHistoricoObj->cliente_black_lists_id = $clienteBlackListObj->id;
        $clienteBlackListHistoricoObj->created_by = Auth::id();
        $clienteBlackListHistoricoObj->save();
    }
}
