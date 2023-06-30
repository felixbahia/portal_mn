<?php
namespace App\Http\Controllers;

use Auth;

use App\Cliente;
use App\GrupoEmpresarial;
use App\ItensOrcamento;
use App\Orcamentos;
use App\PedidoPortal;
use App\PedidoVenda;
use App\PedidosVendaNasajon;
use App\User;
use App\ClienteNasajon;
use App\StatusPedido;
use App\CepEndereco;
use App\CondicoesPagamentoWeb;
use App\AprovacaoDePedido;
use App\CieloPedido;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

use Carbon\Carbon;

class PedidoVendaController extends Controller
{
    private $pedidos_nasajon_status_exibidos = ['Em Faturamento', 'Em Separação', 'Aberto', 'Liquidado'];
    private $pedidos_nasajon_status_valor_faturado = ['Em Faturamento'];

    private $status_lista = [
        'aberto'    => 'Aberto',
        'separando' => 'Separando',
        'a_faturar' => 'A faturar',
        'faturado'  => 'Faturado',
        'cancelado' => 'Cancelado'
    ];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Pedidos") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Pedidos');
        
        $estabelecimentos = returnEmpresasNasajonView();

        unset($estabelecimentos[0], $estabelecimentos[20]);
        $status = $this->status_lista;
        return view('programs.pedidos_orcamentos.index')->with(['estabelecimentos' => $estabelecimentos, 'status' => $status]);
    }

    public function filtro(Request $request){
        ini_set('memory_limit', '2024M');

        $fields = $request->only('estabelecimento', 'cliente_nome', 'pedido', 'data_inicio', 'data_fim', 'status');

        $pedidos_portal_status_exibidos = [3, 5, 6, 7];

        $empresas = returnEmpresasNasajonView();

        $return = [];
        
        $pedidoNasajonQuery = PedidosVendaNasajon::with(['forma_pagamento', 'nota'])
        ->select(
            'estabelecimento_codigo as estabelecimento',
            'numero as pedido',
            'numero as pedido_number',
            'emissao',
            'valor',
            'id',
            'situacao_descricao',
            'cliente',
            'cliente_nomefantasia',
            'notafiscal_id',
            DB::Raw("'nasajon' as origem"),
            'notafiscal_numero as nota_fiscal',
            'vendedor_codigo'
        )
        ->with('cliente_detalhes','forma_pagamento','forma_pagamento.condicao')
        ->where('rascunho', 'false')
        ->where(function($query){
            $query->where(function($query){
                $query->orWhereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA',])
                    ->orWhereNull('grupodeoperacao');
            })
            ->orWhere(function($query){
                $query->whereIn('operacao_codigo', ['PEDAMOSTRA','PEDAMOSTRAGRATIS']);
                $query->where(function($query){
                    $query->orWhereHas('nota', function($query){
                        $query->whereIn('operacao_codigo', ['REMESSAAMOSTRAGRATIS', 'REMESSAAMOSTRA']);
                    });
                    $query->orWhereHas('notaEmAberto', function($query){
                        $query->whereIn('operacao_codigo', ['REMESSAAMOSTRAGRATIS', 'REMESSAAMOSTRA']);
                    });
                });
            });
        })
        ->whereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA', 'REMESSA'])
        ->where('sinal', 0);

        $pedidoPortalQuery = PedidoPortal::with(['status_pedido_detalhes', 'cliente'])->select(
            'estabelecimento',
            'id as pedido',
            'id as pedido_number',
            'condicao_pagamento',
            'data_pedido as emissao',
            'valor_total_nota as valor',
            'status_pedido',
            'cod_cliente',
            DB::Raw("'portal' as origem_pedido")
        )
        ->whereIn('status_pedido', [1, 2, 9, 10])
        ->whereNull('pedido_gerado');

        if(isset($fields['estabelecimento']) && !empty($fields['estabelecimento']) ){
            $pedidoNasajonQuery->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
            $pedidoPortalQuery->where('estabelecimento', $fields['estabelecimento']);
        }

        if(Auth::user()->hasRole('Cliente')){
            $clienteNasajonObj = ClienteNasajon::select('codigo', 'cpf_cnpj')
                ->where(DB::raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), preg_replace("/[\._\/-]/", '', Auth::user()->username))
                ->first();

            $pedidoPortalQuery->where('cod_cliente', $clienteNasajonObj->codigo);
		}
        else if(isset($fields['cliente_nome']) && !empty($fields['cliente_nome'])){

            $clientes = ClienteNasajon::where(DB::raw('CONCAT(TRIM(nome), \' - \', cpf_cnpj)'), 'ilike', '%'.$fields['cliente_nome'].'%')->get();
            $pedidoNasajonQuery->whereIn('cliente', $clientes->pluck('id'));
            
            $pedidoPortalQuery->whereIn('cod_cliente', $clientes->pluck('codigo'));
        }

        if(isset($fields['pedido']) && !empty($fields['pedido']) && is_numeric($fields['pedido'])){
            $pedidoNasajonQuery->where('numero', $fields['pedido']);
            $pedidoPortalQuery->where('id', $fields['pedido']);
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){

            $data_inicio = Carbon::createFromFormat('m/Y', $fields['data_inicio'])->format('Y-m-01');

            $pedidoNasajonQuery->where('emissao', '>=', $data_inicio);
            $pedidoPortalQuery->where('data_pedido', '>=', $data_inicio);
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){

            $data_fim = Carbon::createFromFormat('m/Y', $fields['data_fim'])->format('Y-m-t');

            $pedidoNasajonQuery->where('emissao', '<=', $data_fim);
            $pedidoPortalQuery->where('data_pedido', '<=', $data_fim);
        }

        if(isset($fields['status']) && !empty($fields['status'])){

            $pedidoNasajonQuery->whereIn('situacao_descricao', $this->parserStatusDeNasajon($fields['status']));
            $pedidoPortalQuery->whereIn('status_pedido', $this->parserStatusDePortal($fields['status']));
            
        }

        if(Auth::user()->tipo_usuario_id === 12){
            if(!empty(Auth::user()->codigo_representante)){
                $pedidoNasajonQuery->where('vendedor_codigo', Auth::user()->codigo_representante);
                $pedidoPortalQuery->where('usuario', Auth::user()->id);
            }
            else{
                $pedidoNasajonQuery->where('vendedor_codigo', null);
                $pedidoPortalQuery->where('usuario', null);
            }
        }
        $pedidoNasajonObj = $pedidoNasajonQuery->get();
        $pedidoPortalObj = $pedidoPortalQuery->get();

        $subordinados = [];

        if(
            in_array(Auth::user()->tipo_usuario_id, [14, 18, 19])
            && Auth::id() == 69 
        ){
            $users = UserController::varreSubordinados(Auth::id());
            $subordinados = User::select('codigo_representante')->whereIn('id', $users)->get()->pluck('codigo_representante')->toArray();
        }
      
        $total = 0;

        $retornos = [];
        foreach($pedidoNasajonObj as $pedido_nasajon){
            
            $formapagamento = '';

            if (!isset($pedido_nasajon->forma_pagamento)){
                $formapagamento = '';
            }elseif(isset($pedido_nasajon->forma_pagamento) and !isset($pedido_nasajon->forma_pagamento->condicao)){
                $formapagamento = $pedido_nasajon->forma_pagamento->formapagamento_descricao;
            }else if(isset($pedido_nasajon->forma_pagamento)){
                $formapagamento = $pedido_nasajon->forma_pagamento->condicao->descricao;
            }

            if(!isset($pedido_nasajon->cliente_detalhes->codigo)){
                continue;
            }
            $total += $pedido_nasajon->valor;

            if(
                (Auth::user()->hasRole('Faturamento') || Auth::user()->hasRole('Faturamento Loja') || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Diretoria') || Auth::user()->hasRole('Diretoria Comercial') || Auth::user()->hasRole('Coordenadora Comercial'))
                &&
                in_array($pedido_nasajon->situacao_descricao, ['Aberto', 'Aguardando Documento', 'Liquidado'])
            ){
                $cancelar = true;
            }
            else{
                $cancelar = false;
            }

            $return[] = [
                "estabelecimento" => $empresas[intval($pedido_nasajon->estabelecimento)],
                "pedido" => $pedido_nasajon->pedido,
                "pedido_number" => $pedido_nasajon->pedido_number,
                "emissao" => parserData($pedido_nasajon->emissao),
                "valor" => parserValor($pedido_nasajon->valor),
                "condicao_pagamento" => $formapagamento,
                "status" => $this->parserStatusParaNasajon($pedido_nasajon->situacao_descricao),
                "cliente_codigo" => $pedido_nasajon->cliente_detalhes->codigo,
                "cliente" => $pedido_nasajon->cliente_detalhes->nome,
                "origem" => $pedido_nasajon->origem,
                'nota_fiscal' => $pedido_nasajon->nota_fiscal??'',
                'id' => $pedido_nasajon->id,
                'nota_fiscal_id' => $pedido_nasajon->notafiscal_id??'',
                'cancelar' => $cancelar,
            ];
        }

        foreach ($pedidoPortalObj as $pedido_portal){
            if (isset($pedido_portal->cliente->nome)){
                $cliente_nome = $pedido_portal->cliente->nome;
            }
            else{
                $cliente_nome = '';
            }

            $total += $pedido_portal->valor;
            $status_pedido_detalhes = $pedido_portal->status_pedido_detalhes->status;
            if($pedido_portal->status_pedido == 2){
                $aprovacaoQuery = AprovacaoDePedido::where('pedido_id', $pedido_portal->pedido)->first();
                if(!empty($aprovacaoQuery->credito) && !empty($aprovacaoQuery->preco) && empty($aprovacaoQuery->aprovacao_credito_user_id) && empty($aprovacaoQuery->aprovacao_preco_user_id)){
                    $status_pedido_detalhes = 'Aprovação Crédito / Comercial';
                }else if(!empty($aprovacaoQuery->credito) && empty($aprovacaoQuery->aprovacao_credito_user_id)){
                    $status_pedido_detalhes = 'Aprovação Crédito';
                }else if(!empty($aprovacaoQuery->preco) && empty($aprovacaoQuery->aprovacao_preco_user_id)){
                    $status_pedido_detalhes = 'Aprovação Comercial';
                }
            }

            $return[] = [
                "estabelecimento" => $empresas[intval($pedido_portal->estabelecimento)],
                "pedido" => $pedido_portal->pedido,
                "pedido_number" => $pedido_portal->pedido_number,
                "emissao" => parserData($pedido_portal->emissao),
                "valor" => parserValor($pedido_portal->valor),
                "condicao_pagamento" => $pedido_portal->condicao_pagamento_detalhes->descricao ?? '',
                "status" => $status_pedido_detalhes,
                "cliente_codigo" => $pedido_portal->cod_cliente,
                "cliente" => $cliente_nome,
                "origem" => $pedido_portal->origem_pedido,
                'nota_fiscal' => '',
                'nota_fiscal_id' => '',
                'cancelar' => false,
            ];
        }


        return response()->json(["status"=>"success",
            "prologos" => 0,
            'nasajon' => $pedidoNasajonObj->count(),
            'portal' => $pedidoPortalObj->count(),
            'total' => parserValor($total),
            "data" => $return
        ]);
    }

    public function parserStatusDePrologos($status){
        switch (($status)) {
            case "faturado":
                return [6, 7];
            break;
            case "cancelado":
                return [9];
            break;
            default:
                return [null];
        }
    }

    public function parserStatusDeNasajon($status){
        
        switch ($status){
            case 'aberto':
                return ["Aberto", 'Aguardando Documento', 'Em Expedição'];
            break;
            case "separando":
                return ['Em separação'];
            break;
            case "a_faturar":
                return ['Em Faturamento'];
            break;
            case "faturado":
                return ['Parcialmente Liquidado', 'Liquidado', 'Faturado', 'Faturado Parcialmente'];
            break;
            case "cancelado":
                return ['Cancelado'];
            break;
            default:
                return [];
        }
    }

    public function parserStatusDePortal($status){        
        switch ($status) {
            case 'aberto':
                return [1,5,2];
                break;
            case 'cancelado':
                return [7];
            default:
                return [];
                break;
        }
    }

    public function parserStatusPara($status){
        switch ($status){
            case "1":
                return 'Em Aprovação';
            break;
            case "2":
                return 'Aberto';
            break;
            case "3":
                return 'Aberto';
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
        }
    }

    public function parserStatusParaNasajon($status){
        switch ($status){
            case "Aberto":
                return 'Aberto';
            break;
            case "Em separação":
                return 'Separando';
            break;
            case "Faturado Parcialmente":
                return 'Faturado';
            break;
            case "Cancelado":
                return 'Cancelado';
            break;
            case "Faturado":
                return 'Faturado';
            case "Em Faturamento":
                return "A Faturar";
            case "Liquidado":
                return "Liquidado";
            break;
        }
    }

    public function parserStatusParaPortal($status){
        return StatusPedido::find($status)->status;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function pedidosAbertos(Request $request){
        ini_set('memory_limit', '1024M');

        $fields = $request->only('codigo', 'method', 'unico');

        $codigo = $fields['codigo'];
        $method = $fields['method'];

        $cliente_principal = ClienteNasajon::select()->where('codigo', $fields['codigo'])->first();

        $cpf_cnpj = $cliente_principal->cpf_cnpj;

        if(strlen(trim($cpf_cnpj)) == 18){
            $cpf_cnpj = substr($cpf_cnpj, 0, 10);
        }

        if(is_null($fields['unico']) || empty($fields['unico']) || $fields['unico'] == 'false'){

            $clientesQuery = ClienteNasajon::query();

            $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
            ->where('raiz_cnpj', 'like', $cpf_cnpj . '%')
            ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                $query->where('raiz_cnpj', 'like', $cpf_cnpj . '%');
            })->first();

            if(!is_null($grupoEmpresarialObj)){
                $clientesQuery->where(function($query) use ($grupoEmpresarialObj){
                    if (isset($grupoEmpresarialObj->participantes)){
                        foreach ($grupoEmpresarialObj->participantes as $participante){
                            $query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
                        }
                    }

                    $query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');

                });
            }
            else{
                $clientesQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
            }

            $cliente = $clientesQuery->get();
        
        }
        else{
            $cliente = collect([0 => $cliente_principal]);
        }

        $dados = [];
        if($method === "orcamento"){

            $empresas = returnEmpresasNasajonView();

            $pedidosPortal = PedidoPortal::
            whereIn('cod_cliente', $cliente->pluck('codigo'))
            ->whereNotIn('status_pedido', [3, 5, 7])
            ->get();

            $pedidosPortal->each(function($pedido) use($empresas, &$dados){
                
                $dados[] = [
                    "estabelecimento" => $empresas[intval($pedido->estabelecimento)],
                    "pedido" => $pedido->id,
                    "pedido_number" => $pedido->id,
                    "emissao" => parserData($pedido->data_pedido),
                    "valor" => parserValor($pedido->valor_total->total),
                    "valor_unmask" => $pedido->valor_total->total,
                    "condicao_pagamento" => $pedido->condicao_pagamento_detalhes->descricao,
                    "status" => $pedido->status_pedido_detalhes->status,
                    "cliente_codigo" => $pedido->cod_cliente,
                    "cliente" => $pedido->cliente->nome,
                    "origem" => "portal"
                ];
            });
        }
        else if($method === "carteira"){
            $dadosPedidos = PedidosVendaNasajon::
            with('forma_pagamento','forma_pagamento.condicao')
            ->whereIn('cliente', $cliente->pluck('id'))
            ->whereRaw('rascunho = false')
            ->where(function($query){
                $query->orWhere('grupodeoperacao', 'VENDA');
                $query->orWhere(['grupodeoperacao' => NULL]);
            })
            ->whereIn('situacao_descricao', $this->pedidos_nasajon_status_exibidos)
            ;


            $dados_temp = $dadosPedidos->get();
            $empresas = returnEmpresasNasajonView();
            foreach ($dados_temp as $key => $value) {

                $formapagamento = '';

                if (!isset($value->forma_pagamento)){
                    $formapagamento = '';
                }elseif(isset($value->forma_pagamento) and !isset($value->forma_pagamento->condicao)){
                    $formapagamento = $value->forma_pagamento->formapagamento_descricao;
                }else if(isset($value->forma_pagamento)){
                    $formapagamento = $value->forma_pagamento->condicao->descricao;
                }
    
                /*$formaPagamento = $value->forma_pagamento()->select("formapagamento_descricao")->first();*/

                $cliente = ClienteNasajon::where("id", $value->cliente)->first();
                $dados[] = [
                    "estabelecimento" => $empresas[intval($value->estabelecimento_codigo)],
                    "pedido" => $value->numero,
                    "pedido_number" => $value->id,
                    "emissao" => parserData($value->emissao),
                    "valor" => in_array($value->situacao_descricao, $this->pedidos_nasajon_status_valor_faturado)? parserValor($value->valorTotalFaturado->total_faturado) : parserValor($value->valor),
                    "valor_unmask" => in_array($value->situacao_descricao, $this->pedidos_nasajon_status_valor_faturado)? ($value->valorTotalFaturado->total_faturado) : ($value->valor),
                    "condicao_pagamento" => utf8_decode(utf8_encode($formapagamento)),
                    "status" => $this->parserStatusParaNasajon($value->situacao_descricao),
                    "cliente_codigo" => (!is_null($cliente)) ? $cliente->codigo : "",
                    "cliente" => (!is_null($cliente)) ? utf8_decode(utf8_encode($cliente->nome)) : "",
                    "origem" => "nasajon"
                ];
                /*unset($formaPagamento);*/
                unset($cliente);
            }
        }
        if($method === "total"){
            $dadosPedidos = PedidosVendaNasajon::with('cliente_detalhes')->whereNotNull("cliente")
            ->whereIn('cliente', $cliente->pluck('id'))
            ->whereRaw('rascunho = false')
            ->where(function($query){
                $query->orWhere('grupodeoperacao', 'VENDA');
                $query->orWhere(['grupodeoperacao' => NULL]);
            })
            ->where(function($query){
                $query->orWhereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA']);
            })
            ->whereIn('situacao_descricao', $this->pedidos_nasajon_status_exibidos);

            $dados_temp = $dadosPedidos->get();
            $empresas = returnEmpresasNasajonView();

            foreach ($dados_temp as $key => $value) {
                /*$formaPagamento = $value->forma_pagamento()->select("formapagamento_descricao")->first();*/
                $dados[] = [
                    "estabelecimento" => $empresas[intval($value->estabelecimento_codigo)],
                    "pedido" => $value->numero,
                    "pedido_number" => $value->id,
                    "emissao" => parserData($value->emissao),
                    "valor" => in_array($value->situacao_descricao, $this->pedidos_nasajon_status_valor_faturado)? parserValor($value->valorTotalFaturado->total_faturado) : parserValor($value->valor),
                    "condicao_pagamento" => $value->forma_pagamento->condicao->descricao,
                    "status" => $this->parserStatusParaNasajon($value->situacao_descricao),
                    "cliente_codigo" => $value->cliente->codigo??"",
                    "cliente" => $value->cliente->nome??"",
                    "valor_unmask" => in_array($value->situacao_descricao, $this->pedidos_nasajon_status_valor_faturado)? ($value->valorTotalFaturado->total_faturado) : ($value->valor),
                    "origem" => "nasajon"
                ];
               /* unset($formaPagamento);*/
            }

            $pedidosPortal = PedidoPortal::
            whereIn('cod_cliente', $cliente->pluck('codigo'))
            ->whereIn('status_pedido', [1,2,8,9])
            ->get();

            $pedidosPortal->each(function($pedido) use($empresas, &$dados){
                
                $dados[] = [
                    "estabelecimento" => $empresas[intval($pedido->estabelecimento)],
                    "pedido"            => $pedido->id,
                    "pedido_number"     => $pedido->id,
                    "emissao"           => parserData($pedido->data_pedido),
                    "valor"             => parserValor((isset($pedido->valor_total->total) ? $pedido->valor_total->total : 0)),
                    "valor_unmask"      => (isset($pedido->valor_total->total) ? $pedido->valor_total->total : 0),
                    "condicao_pagamento"=> $pedido->condicao_pagamento_detalhes->descricao,
                    "status"            => $pedido->status_pedido_detalhes->status,
                    "cliente_codigo"    => $pedido->cod_cliente,
                    "cliente"           => $pedido->cliente->nome,
                    "origem"            => "portal"
                ];
            });
        }
        $total = 0;

        foreach ($dados as $key => $value) {
            $total = floatval($total) + floatval($value["valor_unmask"]);
        }

        $total = parserValor($total);

        return view('programs.pedidos_orcamentos.dialog')->with("dados", $dados)->with("total", $total);
	}


    private function getPedidosEstabelMes($estabelecimento, $criterios){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $dados = [];
        $vendedor = [];
        $vendedor_nasajon = [];
        $PedidosNasajon = PedidosVendaNasajon::with(['userNasajon', 'cliente_detalhes','formasPagamentosMultiplos', 'formasPagamentosMultiplos.condicao'])
        ->where('rascunho', 'false')
        ->where(function($query){
            $query->orWhere('grupodeoperacao', 'VENDA')
            ->orWhereNull('grupodeoperacao');
        })
        ->whereIn('operacao_codigo', ['PEDBONIFICACAO', 'PEDBONIFICACAOTORO', 'PEDCONSERTO', 'PEDIDODEVCOMPRA', 'PEDIDODEVCOMPRATORO', 'PEDIDOFUTTORO', 'PEDIDOFUTUROSP', 'PEDIDOISENTO', 'PEDIDOISENTOTORO', 'PEDIDOPCP', 'PEDIDOPUBLICOSP', 'PEDIDOPUBLICOTORO', 'PEDIDOTRANSF', 'PEDIDOTRANSFSEMICMS', 'PEDIDOTRANSFTEXTIL', 'PEDIDOTRANSFTOROSP', 'PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDINDUSTRIA', 'PEDTRANSFCOMREMESSA', 'PEDTRIANGULAR', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO'])
        ->whereDoesntHave('nota', function($query){
            $query->whereIn('operacao_codigo', ['REMESSAAMOSTRA','REMESSAARMAZEM','REMESSABEMATIVO','REMESSABONIFICACAO','REMESSADEMONSTRACAO','REMESSAINDENCOMENDA','REMESSAORDEM','REMESSAORDEMSEMMOV','REMESSAORDEMTORO','REMESSAORDEMTOROTERC']);
        })
        ->where(function($query){
            $query->orWhereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA']);
        })
        ->whereIn('situacao_descricao', $criterios['statusNasaJon']);

        if( empty( $criterios['formaPagamento'] ) ){
            $PedidosNasajon
            ->whereHas('formasPagamentosMultiplos', function ($query){
                $query
                ->where('parcelamento_nome','NOT ILIKE' , 'Usar Credito')
                ->where('parcelamento_codigo','!=' , '608');
            });
        }
        

        $PedidoPortal = PedidoPortal::with('condicao_pagamento_detalhes', 'valor_total')
        ->whereIn('status_pedido', $criterios['statusPortal'])
        ->whereIn('tipo_venda', ['pronta_entrega_triangular', 'remessa_faturamento', 'pedido_orgaopublico', 'pre_pago_triangular', 'venda', 'pre_pago_futuro', 'pronta_entrega_venda', 'isento', 'pre_pago', 'producao', 'triangular', 'pedido_futuro_venda', 'pedido_futuro_triangular', 'pre_pago_rj_x_sp']);

		if(!empty($criterios["users"])){

            $PedidoPortal->whereIn('usuario', $criterios["users"]);

			foreach ($criterios["users"] as $key => $id) {

				$user = User::find($id);
                
                if(!empty($user->codigo_representante)){
                    $vendedor[] = $user->codigo_representante;
				}
			}
        }
        
        if(intval($estabelecimento) > -1){

            $PedidosNasajon->where('estabelecimento_codigo', str_pad($estabelecimento, 2, '0', STR_PAD_LEFT));
            $PedidoPortal->where('estabelecimento', $estabelecimento);

            if(!empty($vendedor)){
                $PedidosNasajon->whereIn('vendedor_codigo', $vendedor);
            }
			
        }else{

            if(!empty($vendedor)){
                $PedidosNasajon->whereIn('vendedor_codigo', $vendedor);
            }
			
        }

        if(!isset($criterios['intercompany']) || $criterios['intercompany'] === false){

            $clientesNasajonIntercompany = ClienteNasajon::select('id', 'codigo')->where('cpf_cnpj', 'like', '06.311.274%')
            ->orWhere('cpf_cnpj', 'like', '05.075.884%')->get();

            $ids_intercompany = $clientesNasajonIntercompany->pluck('id');
            $codigos_intercompany = $clientesNasajonIntercompany->pluck('codigo');

            $PedidosNasajon->whereNotIn('cliente', $ids_intercompany);
            $PedidoPortal->whereNotIn('cod_cliente', $codigos_intercompany);

        }
        switch ($criterios['mes']) {
            case -1:
                $data = Carbon::now()->format('Y-m-01');
                $PedidoPortal->where('data_previsao_entrega', '<', $data);
                $PedidosNasajon->where('emissao', '<', $data);
                break;
            case 0:
                $data = [Carbon::now()->format('Y-m-01'), Carbon::now()->format('Y-m-t')];
                $PedidoPortal->whereBetween('data_previsao_entrega', $data);
                $PedidosNasajon->whereBetween('emissao', $data);
                break;
            case 1:
                $data = [Carbon::now()->addMonthNoOverflow()->format('Y-m-01'), Carbon::now()->addMonthNoOverflow()->format('Y-m-t')];
                $PedidoPortal->whereBetween('data_previsao_entrega', $data);
                $PedidosNasajon->whereBetween('emissao', $data);
                break;
            case 2:
                $data = Carbon::now()->addMonthNoOverflow()->format('Y-m-t');
                $PedidoPortal->where('data_previsao_entrega', '>', $data);
                $PedidosNasajon->where('emissao', '>', $data);
                break;
        }
        
        $nasajon_temp = $PedidosNasajon->get();
        $portal_temp = $PedidoPortal->get();
        $empresas = returnEmpresasNasajonView();
        $dados = [];
        foreach ($nasajon_temp as $pedido_nasajon){ 
            if(isset($criterios['vendas']) && $criterios['vendas'] == true){
                if(!in_array($pedido_nasajon->operacao_codigo,['PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO', 'PEDVENDATORO','PEDIDOISENTO','PEDIDOISENTOTORO','PEDIDOPUBLICOSP','PEDIDOPUBLICOTORO','PEDTRIANGULAR'])){
                    continue;
                }
            }

            $formapagamento = '';

            if(isset($pedido_nasajon->formasPagamentosMultiplos[0])){
                foreach($pedido_nasajon->formasPagamentosMultiplos as $formas){
                    if(!empty($formapagamento)){
                        $formapagamento .= ', ';
                    }
                    if(!isset($formas->condicao)){
                        $formapagamento .= $formas->formapagamento_descricao;
                        
                    }else if(isset($formas->condicao)){
                        $formapagamento .= $formas->condicao->descricao;
                    }

                }
            }

            $dados[] = [
                "estabelecimento" => $empresas[intval($pedido_nasajon->estabelecimento_codigo)],
                "pedido" => $pedido_nasajon->numero,
                "pedido_number" => $pedido_nasajon->id,
                "emissao" => parserData($pedido_nasajon->emissao),
                "valor" => parserValor($pedido_nasajon->valor),
                "valor_unmask" => floatval($pedido_nasajon->valor),
                "condicao_pagamento" => $formapagamento,
                "status" => $this->parserStatusParaNasajon($pedido_nasajon->situacao_descricao),
                "vendedor_codigo" => isset($pedido_nasajon->userNasajon) ? $pedido_nasajon->userNasajon->pessoa : '',
                "vendedor" => isset($pedido_nasajon->userNasajon) ? $pedido_nasajon->userNasajon->nome : '',
                "cliente_codigo" => isset($pedido_nasajon->cliente_detalhes) ? $pedido_nasajon->cliente_detalhes->codigo : '',
                "cliente" => isset($pedido_nasajon->cliente_detalhes) ? $pedido_nasajon->cliente_detalhes->nome : '',
                "origem" => "nasajon"
            ];
        }
        
        foreach ($portal_temp as $pedido){
            if(isset($criterios['vendas']) && $criterios['vendas'] == true){
                if(!in_array($pedido->tipo_venda,['PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO'])){
                    continue;
                }
            }
            $dados[] = [
                "estabelecimento" => $empresas[intval($pedido->estabelecimento)],
                "pedido" => $pedido->id,
                "pedido_number" => $pedido->id,
                "emissao" => parserData($pedido->data_pedido),
                "valor" => parserValor((!empty($pedido->valor_total->total) ? $pedido->valor_total->total : 0)),
                "valor_unmask" => (!empty($pedido->valor_total->total)) ? $pedido->valor_total->total : 0,
                "condicao_pagamento" => $pedido->condicao_pagamento_detalhes->descricao,
                "status" => $pedido->status_pedido_detalhes->status,
                "vendedor_codigo" => $pedido->usuario_detalhes->id,
                "vendedor" => $pedido->usuario_detalhes->name,
                "cliente_codigo" => $pedido->cod_cliente,
                "cliente" => isset($pedido->cliente->CODCAD) ? utf8_encode($pedido->cliente->NOME) : $pedido->cliente->nome??'',
                "origem" => "portal",
            ];
        }


        return $dados;
    }
    
    public function pedidos(Request $request){
        ini_set('memory_limit', '1024M');
        $fields = $request->only('estabel', 'mes', 'coluna', 'criterio','produto');
        $criterios = Crypt::decrypt($fields['criterio']);
        $dados = [];
        $estabels = returnEmpresasNasajonView();

        $criterios['mes'] = $fields['mes'];

        if((intval($fields["mes"]) >= -1 && intval($fields["mes"]) <= 2) && (isset($estabels[intval($fields['estabel'])]) || intval($fields['estabel']) === -1) && in_array($fields["coluna"], ["pedidos", "clientes", "valor", "total"])){
            $where_mes = ["pedidos" => "", "orcamentos" => ""];

            if(isset($fields['produto']) && $fields['produto'] == true){
                $dados = $this->getPedidosEstabelMesProdutos($fields['estabel'], $criterios);
                $total_produtos = [
                    'total_quantidade' => 0,
                    'total_valor' => 0
                ];

                foreach($dados as $key => $dado){
                    $total_produtos['total_quantidade'] += $dado['quantidade_faturada'];
                    $total_produtos['total_valor'] += $dado['valor'];

                    $dados[$key]['quantidade_faturada'] = ($dado['quantidade_faturada'] > 0) ? parserQtd($dado['quantidade_faturada']) : '';
                    $dados[$key]['valor'] = ($dado['valor'] > 0) ? parserQtd($dado['valor']) : '';
                }

                $total_produtos['total_quantidade'] = ($total_produtos['total_quantidade'] > 0) ?  parserQtd($total_produtos['total_quantidade']) : '';
                $total_produtos['total_valor'] = ($total_produtos['total_valor'] > 0) ?  parserQtd($total_produtos['total_valor']) : '';

                return view('programs.carteira_pedidos.modal.produto')->with(['dados' => $dados, 'total' => $total_produtos]);
            }

            $dados = $this->getPedidosEstabelMes($fields['estabel'], $criterios);
            
            $total = 0;

            foreach ($dados as $key => $value) {
                $total = floatval($total) + floatval($value["valor_unmask"]);
            }

            $total = parserValor($total);
            
            return view('programs.pedidos_orcamentos.dialog')->with("dados", $dados)->with('estabel', $fields['estabel'])->with("total", $total);
        }else{    
            return abort(404);
        }

    }

    private function getPedidosEstabelMesProdutos($estabelecimento, $criterios){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        
        $dados = [];
        $vendedor = [];
        $vendedor_nasajon = [];
        
        $PedidosNasajon = PedidosVendaNasajon::with(['itens_pedido','itens_pedido.especificacao'])
        ->where('rascunho', 'false')
        ->where(function($query){
            $query->orWhere('grupodeoperacao', 'VENDA')
            ->orWhereNull('grupodeoperacao');
        })
        ->whereIn('operacao_codigo', ['PEDBONIFICACAO', 'PEDBONIFICACAOTORO', 'PEDCONSERTO', 'PEDIDODEVCOMPRA', 'PEDIDODEVCOMPRATORO', 'PEDIDOFUTTORO', 'PEDIDOFUTUROSP', 'PEDIDOISENTO', 'PEDIDOISENTOTORO', 'PEDIDOPCP', 'PEDIDOPUBLICOSP', 'PEDIDOPUBLICOTORO', 'PEDIDOTRANSF', 'PEDIDOTRANSFSEMICMS', 'PEDIDOTRANSFTEXTIL', 'PEDIDOTRANSFTOROSP', 'PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDINDUSTRIA', 'PEDTRANSFCOMREMESSA', 'PEDTRIANGULAR', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO'])
        ->whereDoesntHave('nota', function($query){
            $query->whereIn('operacao_codigo', ['REMESSAAMOSTRA','REMESSAARMAZEM','REMESSABEMATIVO','REMESSABONIFICACAO','REMESSADEMONSTRACAO','REMESSAINDENCOMENDA','REMESSAORDEM','REMESSAORDEMSEMMOV','REMESSAORDEMTORO','REMESSAORDEMTOROTERC']);
        })
        ->where(function($query){
            $query->orWhereIn('grupodeoperacao_pedido', ['VENDA', 'TRANSFERENCIA']);
        })
        ->whereIn('situacao_descricao', $criterios['statusNasaJon']);

        if( empty( $criterios['formaPagamento'] ) ){
            $PedidosNasajon
            ->whereHas('forma_pagamento', function ($query){
                $query
                ->where('parcelamento_nome','NOT ILIKE' , 'Usar Credito')
                ->where('parcelamento_codigo','!=' , '608')
                ->where('formapagamento_descricao','NOT ILIKE','Cartão Crédito');
            });
        }
        
        $PedidoPortal = PedidoPortal::with(['itens_pedido','itens_pedido.especificacoes'])
        ->whereIn('status_pedido', $criterios['statusPortal'])
        ->whereIn('tipo_venda', ['pronta_entrega_triangular', 'remessa_faturamento', 'pedido_orgaopublico', 'pre_pago_triangular', 'venda', 'pre_pago_futuro', 'pronta_entrega_venda', 'isento', 'pre_pago', 'producao', 'triangular', 'pedido_futuro_venda', 'pedido_futuro_triangular', 'pre_pago_rj_x_sp']);
        
		if(!empty($criterios["users"])){

            $PedidoPortal->whereIn('usuario', $criterios["users"]);

			foreach ($criterios["users"] as $key => $id) {

				$user = User::find($id);
                
                if(!empty($user->codigo_representante)){
                    $vendedor[] = $user->codigo_representante;
				}
			}
        }

        if(intval($estabelecimento) > -1){

            $PedidosNasajon->where('estabelecimento_codigo', str_pad($estabelecimento, 2, '0', STR_PAD_LEFT));
            $PedidoPortal->where('estabelecimento', $estabelecimento);

            if(!empty($vendedor)){
                $PedidosNasajon->whereIn('vendedor_codigo', $vendedor);
            }
			
        }else{

            if(!empty($vendedor)){
                $PedidosNasajon->whereIn('vendedor_codigo', $vendedor);
            }
			
        }

        if(!isset($criterios['intercompany']) || $criterios['intercompany'] === false){

            $clientesNasajonIntercompany = ClienteNasajon::select('id', 'codigo')->where('cpf_cnpj', 'like', '06.311.274%')
            ->orWhere('cpf_cnpj', 'like', '05.075.884%')->get();

            $ids_intercompany = $clientesNasajonIntercompany->pluck('id');
            $codigos_intercompany = $clientesNasajonIntercompany->pluck('codigo');

            $PedidosNasajon->whereNotIn('cliente', $ids_intercompany);
            $PedidoPortal->whereNotIn('cod_cliente', $codigos_intercompany);

        }
        switch ($criterios['mes']) {
            case -1:
                $data = Carbon::now()->format('Y-m-01');
                $PedidoPortal->where('data_previsao_entrega', '<', $data);
                $PedidosNasajon->where('emissao', '<', $data);
                break;
            case 0:
                $data = [Carbon::now()->format('Y-m-01'), Carbon::now()->format('Y-m-t')];
                $PedidoPortal->whereBetween('data_previsao_entrega', $data);
                $PedidosNasajon->whereBetween('emissao', $data);
                break;
            case 1:
                $data = [Carbon::now()->addMonthNoOverflow()->format('Y-m-01'), Carbon::now()->addMonthNoOverflow()->format('Y-m-t')];
                $PedidoPortal->whereBetween('data_previsao_entrega', $data);
                $PedidosNasajon->whereBetween('emissao', $data);
                break;
            case 2:
                $data = Carbon::now()->addMonthNoOverflow()->format('Y-m-t');
                $PedidoPortal->where('data_previsao_entrega', '>', $data);
                $PedidosNasajon->where('emissao', '>', $data);
                break;
        }

        $nasajon_temp = $PedidosNasajon->get();
        $portal_temp = $PedidoPortal->get();
        $empresas = returnEmpresasNasajonView();
        $dados = [];
        foreach ($nasajon_temp as $pedido_nasajon){ 
            if(isset($criterios['vendas']) && $criterios['vendas'] == true){
                if(!in_array($pedido_nasajon->operacao_codigo,['PEDIDOVENDA', 'PEDIDOVENDAAORDEM', 'PEDVENDAMIGRACAO', 'PEDVENDAORDEMTORO', 'PEDVENDATORO', 'PEDVENDATORO','PEDIDOISENTO','PEDIDOISENTOTORO','PEDIDOPUBLICOSP','PEDIDOPUBLICOTORO','PEDTRIANGULAR'])){
                    continue;
                }
            }

            foreach($pedido_nasajon->itens_pedido as $produtos_nasajon){
                if(isset($produtos_nasajon->especificacao->produtoGrupo->descricao) && !empty($produtos_nasajon->especificacao->produtoGrupo->descricao)){
                    if(!isset($dados[$produtos_nasajon->especificacao->produtoGrupo->descricao])){
                        $dados[$produtos_nasajon->especificacao->produtoGrupo->descricao] = [
                            "estabelecimento" => $empresas[intval($pedido_nasajon->estabelecimento_codigo)],
                            "grupo" => $produtos_nasajon->especificacao->produtoGrupo->descricao,
                            "linha" => (!empty($produtos_nasajon->especificacao->linha)) ? $produtos_nasajon->especificacao->linha : '',
                            "marca" => (!empty($produtos_nasajon->especificacao->marca)) ? $produtos_nasajon->especificacao->marca : '',
                            "quantidade_faturada" => 0,
                            "valor" => 0,
                        ];
                    }

                    $dados[$produtos_nasajon->especificacao->produtoGrupo->descricao]['quantidade_faturada'] += $produtos_nasajon->quantidadecomercial;
                    $dados[$produtos_nasajon->especificacao->produtoGrupo->descricao]['valor'] += $produtos_nasajon->valortotal;
                }
            }

        }
        
        foreach ($portal_temp as $pedido){
            if(isset($criterios['vendas']) && $criterios['vendas'] == true){
                if(!in_array($pedido->tipo_venda,['venda','pedido_futuro_venda','pronta_entrega_venda'])){
                    continue;
                }
            }
            foreach($pedido->itens_pedido as $produtos_portal){
                if(!isset($dados[$produtos_portal->especificacoes->grupo])){
                    $dados[$produtos_portal->especificacoes->grupo] = [
                        "estabelecimento" => $empresas[intval($pedido->estabelecimento_codigo)],
                        "grupo" => $produtos_portal->especificacoes->grupo,
                        "linha" => $produtos_portal->especificacoes->linha,
                        "marca" => $produtos_portal->especificacoes->marca,
                        "quantidade_faturada" => 0,
                        "valor" => 0,
                    ];
                }

                $dados[$produtos_portal->especificacoes->grupo]['quantidade_faturada'] += $produtos_portal->quantidade;
                $dados[$produtos_portal->especificacoes->grupo]['valor'] += $produtos_portal->valor_total;
                
            }
        }


        return $dados;
    }

    public function showPedidoCompleto(Request $request){
        ini_set('memory_limit', '1024M');
        $fields = $request->only(["origem", "pedido", "estabelecimento"]);
        $dados = [];

        if($fields["origem"] === "pedido"){
            return abort(404);
        }
        else if($fields["origem"] === "portal"){
            $PedidoPortalControllerObj = new PedidoPortalController;
            $request_array = new Request([
                'pedido_id' => $fields["pedido"]
            ]);
            return $PedidoPortalControllerObj->detalhes($request_array);
        }
        elseif($fields["origem"] === "nasajon"){
            $PedidosVendaNasajonControllerObj = new PedidosVendaNasajonController;
            return $PedidosVendaNasajonControllerObj->detalhes($fields["pedido"]);
        }
        else{
            return abort(404);
        }
        return view('programs.pedidos_orcamentos.show')->with(["dados" => $dados,"itens" => $itens,"tipo" => $tipo, "total_itens" => $total_itens]);
    }

    private function encodeDados($dados){
        if(is_null($dados)){
            return null;
        }
        foreach ($dados as $key => $value) {
            if(empty($value)){
                continue;
            }
            if(is_object($value)){
                $dados->{$key} = $this->encodeDados($value);
            } elseif(!empty($value)) {
                $dados->{$key} = utf8_encode($value);
            }
        }
        return $dados;
    }

    private function decodeDados($dados){
        foreach ($dados as $key => $value) {
            if(is_object($value)){
                $dados->{$key} = $this->decodeDados($value);
            } elseif(!empty($value)) {
                $dados->{$key} = utf8_decode($value);
            }
        }
        return $dados;
    }

    private function parserDadosPedido($pedido){
        $return = ["dados" => [], "itens" => []];
        $pedido = $this->encodeDados($pedido);
        $estabels = returnEmpresasPrologusView();
        $cliente = Cliente::where("CODCAD", $pedido->CODCAD)->first();
        $cliente = $this->encodeDados($cliente);
        $vendedor = $pedido->vendedor()->first();
        $vendedor = $this->encodeDados($vendedor);
        $transportador = $pedido->transportador()->first();
        $transportador = $this->encodeDados($transportador);
         
        $formaPagamento = utf8_encode($pedido->formaPagamento->DESCRICAO);
        $viaTransporte = $this->parserViaTran($pedido->VIATRAN);
        $tipoFrete = $this->parserTipoFrete($pedido->TIPO_FRETE);
        $tipoOperacao = $pedido->tipoOperacao()->first();
        $tipoOperacao = $this->encodeDados($tipoOperacao);

        $datahora_pedido = "";
        if(!empty($pedido->DATA_INI)){
            $datahora_pedido = explode(" ", $pedido->DATA_INI)[0];
            $datahora_pedido .= $pedido->HORA_INI.":00";   
        }

        $return["dados"] = [
            "estabelecimento" => $estabels[intval($pedido->ESTABEL)],
            "estabelecimento_codigo" => intval($pedido->ESTABEL),
            "status" => $this->parserStatusPara($pedido->SITATUAL),
            "status_codigo" => $pedido->SITATUAL,
            "numero_pedido" => $pedido->NUMPED,
            "data_pedido" => parserData($pedido->DATA_PEDIDO),
            "tipo_operacao" => utf8_encode($tipoOperacao->DESCRICAO),
            "tipo_operacao_codigo" => $tipoOperacao->TIPOPER,
            "cliente" => utf8_encode($cliente->NOME),
            "cliente_codigo" => $cliente->CODCAD,
            "vendedor" => utf8_encode($vendedor->NOME),
            "vendedor_codigo" => $vendedor->CODVND,
            "comissao_vendedor" => (!empty($pedido->COMISSAO_VND)) ? parserValor($pedido->COMISSAO_VND) : "",
            "atendente" => $pedido->ATENDENTE,
            "contato" => $pedido->CONTATO,
            "numero_pedido_cliente" => $pedido->SEUNUMPED,
            "transportador" => (!is_null($transportador)) ? $transportador->NOME : "",
            "transportador_codigo" => (!is_null($transportador)) ? $transportador->CODTRAN : "",
            "via_transporte" => $viaTransporte,
            "via_transporte_codigo" => $pedido->CODVCT,
            "local_entrega" => $pedido->LOCAL_ENTREGA,
            "condicao_pagamento" => utf8_encode($formaPagamento),
            "condicao_pagamento_codigo" => utf8_encode($formaPagamento),
            "desconto_geral" => (!empty($pedido->DESCGERAL)) ? parserValor($pedido->DESCGERAL) : "",
            "valor_total" => (!empty($pedido->VALTOTPED)) ? parserValor($pedido->VALTOTPED) : "",
            "saldo_pedido" => (!empty($pedido->SALDOTOTPED)) ? parserValor($pedido->SALDOTOTPED) : "",
            "desconto_real" => (!empty($pedido->DESCONTO_APLICADO)) ? parserValor($pedido->DESCONTO_APLICADO) : "",
            "tipo_frete" => $tipoFrete,
            "tipo_frete_codigo" => $pedido->TIPO_FRETE,
            "datahora_pedido" => (!empty($datahora_pedido) ) ? date("d/m/Y H:i", strtotime($datahora_pedido)) : "",
            "usuario_abriu" => $pedido->CODUSU_ABRIU,
            "usuario_alterou" => $pedido->CODUSU_ALTEROU,
            "situacao_analise" => $pedido->SIT_CRED,
            "indicador_empenho" => ($pedido->EMPENHO_OK === "S") ? "SIM" : "NÃO",
            "observacao" => utf8_encode($pedido->OBSINTERNA),
            "data_entrega" => parserData($pedido->DATA_ENTREGA),
            "separador" => $pedido->EXECUTOR1,
            "conferente" => $pedido->EXECUTOR2,
            "numero_ultima_nf" => $pedido->NUMULTNF
        ];
        unset($dados);
        unset($tipoFrete);
        unset($viaTransporte);
        unset($formaPagamento);
        unset($transportador);
        unset($cliente);
        unset($estabels);
        
        $itensPedido = $pedido->itensPedido()->get();
        $itensPedido = $this->encodeDados($itensPedido);
        $debug = [];
        $total_itens = [
            'quantidade_pedida' => 0,
            'quantidade_empenhada' => 0,
            'quantidade_faturada' => 0,
            'desconto' => 0,
            'valor_unitario' => 0,
            'valor_total' => 0,
        ];
        foreach ($itensPedido as $key => $item) {
            $produto = $item->produto()->first();
            $produto = $this->encodeDados($produto);
            if(intval($pedido->SITATUAL) >= 6){
                $valor = ($item->PU_ITEM_LIQ * ($item->QTDFAT));
            }else{
                $valor = ($item->PU_ITEM_LIQ * ($item->QTDPED));
            }
            $debug[] = ($valor);
            $return["itens"][] = [
                "numero_pedido" => $item->NUMPED,
                "codigo" => utf8_encode($produto->CODPRD),
                "descricao" => utf8_encode($produto->DESCR),
                "grupo" => utf8_encode($produto->GRUPO),
                "subgrupo" => utf8_encode($produto->SUBGRUPO),
                "marca" => utf8_encode($produto->MARCA),
                "linha" => utf8_encode($produto->LINHA),
                "unidade" => utf8_encode($item->UNIDADE),
                "quantidade_pedida" => (!empty($item->QTDPED)) ? parserValor($item->QTDPED) : "",
                "quantidade_empenhada" => (!empty($item->QTDEMP)) ? parserValor($item->QTDEMP) : "",
                "quantidade_faturada" => (!empty($item->QTDFAT)) ? parserValor($item->QTDFAT) : "",
                "desconto" => (!empty($item->PROCDESC)) ? parserValor($item->PROCDESC) : "",
                "valor_unitario" => parserValor($item->PU_ITEM_LIQ),
                "valor_total" => parserValor($valor),
                "situacao_faturamento" => $item->SIT_FAT
            ];
            
            $total_itens['quantidade_pedida'] += (!empty($item->QTDPED)) ? $item->QTDPED : 0;
            $total_itens['quantidade_empenhada'] += (!empty($item->QTDEMP)) ? $item->quantidadecomercial : 0;
            $total_itens['quantidade_faturada'] += (!empty($item->QTDFAT)) ? $item->QTDFAT : 0;
            $total_itens['desconto'] += (!empty($item->PROCDESC)) ? $item->PROCDESC : 0;
            $total_itens['valor_unitario'] += (!empty($item->PU_ITEM_LIQ)) ? $item->PU_ITEM_LIQ : 0;
            $total_itens['valor_total'] += (!empty($valor)) ? $valor : 0;
            unset($produto);
        }

        $total_itens['quantidade_pedida'] = ($total_itens['quantidade_pedida'] > 0) ? parserValor($total_itens['quantidade_pedida']) : '';
        $total_itens['quantidade_empenhada'] = ($total_itens['quantidade_empenhada'] > 0) ? parserValor($total_itens['quantidade_empenhada']) : '';
        $total_itens['quantidade_faturada'] = ($total_itens['quantidade_faturada'] > 0) ? parserValor($total_itens['quantidade_faturada']) : '';
        $total_itens['desconto'] = ($total_itens['desconto'] > 0) ? parserValor($total_itens['desconto']) : '';
        $total_itens['valor_unitario'] = ($total_itens['valor_unitario'] > 0) ? parserValor($total_itens['valor_unitario']) : '';
        $total_itens['valor_total'] = ($total_itens['valor_total'] > 0) ? parserValor($total_itens['valor_total']) : '';
        $return["total_itens"] = $total_itens;

        return $return;
    }

    private function parserViaTran($via_transporte){
        switch (intval($via_transporte)) {
            case 0:
                return "Nosso Carro";
            break;
            case 1:
                return "Rodoviário";
            break;
            case 2:
                return "Ferroviário";
            break;
            case 3:
                return "Aéreo";
            break;
            case 4:
                return "Fluvial";
            break;
            case 5:
                return "Maritímo";
            break;
            case 6:
                return "Retirada";
            break;
        }
    }

    private function parserTipoFrete($tipo_frete){
        switch ($tipo_frete) {
            case 'P':
                return "PAGO";
            break;
            case 'A':
                return "A PAGAR";
            break;
            case 'C':
                return "COBRADO";
            break;
            case 'T':
                return "TERCEIRO";
            break;
            case 'S':
                return "SEM FRETE";
            break;
        }
    }

    private function parserDadosOrcamento($orcamento){
        $return = ["dados" => [], "itens" => []];
        $orcamento = $this->encodeDados($orcamento);
        $estabels = returnEmpresasPrologusView();
        $cliente = Cliente::where("CGC_CPF", $orcamento->CODCLI)->first();
        $cliente = $this->encodeDados($cliente);
        $vendedor = $orcamento->vendedor()->first();
        $vendedor = $this->encodeDados($vendedor);

        $transportador = $orcamento->transportador()->first();
        $transportador = $this->encodeDados($transportador);

        $formaPagamento = $orcamento->formaPagamento()->first();
        if(!is_null($formaPagamento)){
            $formaPagamento = $this->encodeDados($formaPagamento);
        }
        $tipoFrete = $this->parserTipoFrete($orcamento->TIPO_FRETE);
        $return["dados"] = [
            "estabelecimento" => $estabels[intval($orcamento->ESTABEL)],
            "estabelecimento_codigo" => intval($orcamento->ESTABEL),
            "status" => $this->parserStatusPara($orcamento->SITORC),
            "status_codigo" => $orcamento->SITORC,
            "numero_pedido" => $orcamento->NUMORC,
            "cliente" => utf8_encode($cliente->NOME),
            "cliente_codigo" => $cliente->CODCAD,
            "vendedor" => utf8_encode($vendedor->NOME),
            "vendedor_codigo" => $vendedor->CODWEB,
            "comissao_vendedor" => (!empty($orcamento->COMISSAO)) ? parserValor($orcamento->COMISSAO) : "",
            "contato" => $orcamento->CONTATO,
            "numero_pedido_cliente" => $orcamento->SEUNUMPED,
            "transportador" => utf8_encode($transportador->NOME),
            "transportador_codigo" => utf8_encode($transportador->CODTRAN),
            "condicao_pagamento" => (!is_null($formaPagamento)) ? utf8_encode($formaPagamento->DESCRICAO) : "",
            "condicao_pagamento_codigo" => (!is_null($formaPagamento)) ? utf8_encode($formaPagamento->CODVCTO) : "",
            "tipo_frete" => $tipoFrete,
            "tipo_frete_codigo" => $orcamento->TIPO_FRETE,
            "datahora_pedido" => (!empty($orcamento->DH_ORC) ) ? date("d/m/Y H:i", strtotime($orcamento->DH_ORC)) : "",
            "usuario_abriu" => utf8_encode($vendedor->NOME),
            "usuario_abriu_codigo" => $vendedor->CODWEB,
            "observacao" => $orcamento->OBSINTERNA,
            "data_entrega" => (!empty($orcamento->DATA_ENTREGA) ? parserData($orcamento->DATA_ENTREGA) : "Pronta Entrega"),
            "numero_ultima_nf" => $orcamento->NUMULTDUE,
            "desconto_geral" => "",
            "indicador_empenho" => "",
            "usuario_alterou" => "",
            "usuario_alterou" => "",
            "valor_total" => 0.0,
            "saldo_pedido" => 0.0,
            "desconto_real" => 0.0,
            "via_transporte" => "",
            "via_transporte_codigo" => "",
            "local_entrega" => "",
            "atendente" => "",
            "data_pedido" => "",
            "tipo_operacao" => "",
            "tipo_operacao_codigo" => "",
            "separador" => "",
            "conferente" => ""
        ];
        unset($dados);
        unset($tipoFrete);
        unset($viaTransporte);
        unset($formaPagamento);
        unset($transportador);
        unset($cliente);
        unset($estabels);
        
        $valor_total = 0.0;

        $itensOrcamento = $orcamento->itensOrcamento()->get();
        if(!is_null($itensOrcamento)){
            $itensOrcamento = $this->encodeDados($itensOrcamento);
            if(count($itensOrcamento) > 0){
                foreach ($itensOrcamento as $key => $item) {
                    $produto = $item->produto()->first();
                    $produto = $this->encodeDados($produto);
                    $return["itens"][] = [
                        "numero_pedido" => $item->NUMORC,
                        "codigo" => utf8_encode($produto->CODPRD),
                        "descricao" => utf8_encode($produto->DESCR),
                        "grupo" => utf8_encode($produto->GRUPO),
                        "subgrupo" => utf8_encode($produto->SUBGRUPO),
                        "marca" => utf8_encode($produto->MARCA),
                        "linha" => utf8_encode($produto->LINHA),
                        "unidade" => utf8_encode($produto->UNIDADE_VND),
                        "quantidade_pedida" => (!empty($item->QTDPED)) ? parserValor($item->QTDPED) : "",
                        "quantidade_empenhada" => "",
                        "quantidade_faturada" => "",
                        "desconto" => (!empty($item->PROCDESC)) ? parserValor($item->PROCDESC) : "",
                        "valor_unitario" => parserValor($item->PU_LIQ),
                        "valor_total" => parserValor(($item->PU_LIQ * $item->QTDPED)),
                        "situacao_faturamento" => ""
                    ];
                    $valor_total += floatval(($item->PU_LIQ * $item->QTDPED));
                    unset($produto);
                    unset($itensOrcamento[$key]);
                }
            }
        }
        $return["dados"]["valor_total"] = parserValor($valor_total);
        return $return;
    }

}
