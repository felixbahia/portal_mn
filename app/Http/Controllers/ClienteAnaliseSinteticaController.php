<?php

namespace App\Http\Controllers;

use Auth;

use App\PedidoPortal;
use App\ClienteNasajon;
use App\PedidosVendaNasajon;
use App\PosicaoClienteNasajon;
use App\NotasDebitoReceberNasajon;
use App\ContasNasajon;
use App\ChequesRecebidoNasajon;
use App\TitulosEmAbertoNasajon;
use App\TitulosEmAbertoNasajonPortal;
use App\ClienteCredito;
use App\GrupoEmpresarial;
use App\NotasCreditoReceberNasajon;
use App\PedidosPrePago;
use App\Cheque;
use App\ClientePrePago;
use App\ChequeNasajon;
use App\ChequeTituloNasajon;
use App\TitulosPagosNasajon;
use App\TituloPagamentoNasajon;
use App\RenegociacaoTitulo;
use App\ClienteBlackList;
use App\LogAlterarTitulosJudiciai;
use App\PagamentoPixNasajon;
use App\FinancasTitulosNasajon;
use App\GrupoEmpresarialParticipante;

use App\Http\Requests\PosicaoSinteticaClienteTitulosPagosRequest;
use App\Http\Requests\PosicaoSinteticaClienteTitulosRenegociadosRequest;
use App\Http\Requests\PosicaoSinteticaClienteFormaPagamentoRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DevolucaoNotaController;
use App\RenegociacaoTituloParcela;
use Carbon\Carbon;

class ClienteAnaliseSinteticaController extends Controller
{
    private $pedidos_nasajon_status_exibidos = ['Em Faturamento', 'Em separação', 'Aberto'];
    private $codigo_cliente_balcao = ['0000010069999'];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\PosicaoSintetica") === false){
            return abort(403);
                                                                                                                                                                                                                                                                                                                                                                                                				    }
        $request->session()->flash('model', 'App\PosicaoSintetica');
        return view('programs.posicao_sintetica_cliente.index');
    }

    public function returnDados(Request $request, $array = false){
        ini_set('memory_limit', '1024M');
        set_time_limit(600);
        $fields = $request->only('codigo', 'data_inicio', 'data_fim', 'cpf_cnpj_unico','data_inicio_forma_pagamento','data_fim_forma_pagamento','data_inicio_renegociacao','data_fim_renegociacao');
        $codcad = $fields['codigo'];
        if(in_array($codcad, $this->codigo_cliente_balcao) || empty($codcad)){
            if($array === true){
                return [
                    "pedidos" => [
                        "orcamentos"  => "",
                        "carteira"  => "",
                        "total"     => ""
                    ],
                    "notas_debito" => [
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "notas_credito" => "",
                    "titulos_faturados" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> "",
                        "ha_titulos_a_vencer" => false,
                        "ha_titulos_vencidas" => false,
                    ],
                    "titulos_terceiros" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "cheques_a_receber" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "atraso" => [
                        "ultima" => [
                            "data"         => "",
                            "quantidade"   => ""
                        ],
                        "maior" =>  [
                            "data"         => "",
                            "quantidade"   => ""
                        ]
                    ],
                    "vendas" => [
                        "ultima" => [
                            "data"     => "",
                            "valor"    => ""
                        ],
                        "maior" =>  [
                            "data"     => "",
                            "valor"    => ""
                        ]
                    ],
                    "total" => [
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "pago_ultimo_12_meses" => '',
                    "cliente_desde" => '',
                    "limite_credito" => '',
                    "mensagem_alerta" => '',
                    "messagem_agrupada" => '',
                    "messagem_agrupada_cnpjs" => '',
                    'cliente' => [
                        'codigo' => '',
                        'nome' => '',
                        'unico' => ''
                    ],
                    "cnpj_array" => '',
                    "titulos_pagos" => '',
                    "titulos" => '',
                    "valor_total" => '',
                    'em_atraso' => '',
                    "dias_total_atraso" => '',
                    "media_atraso" => '',
                    'vencimento_credito' => '',
                    "ultima_atualizacao" => '',
        
                    "titulo_modal" => '',

                    "consulta_serasa" => '',
                    "motivo_reavaliacao" => ''
                ];
            }
            else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados não encontrados',
                    'error' => [],
                    'response' => []
                ], 422);
            }
        }
        if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat("d/m/Y", $fields['data_inicio']);
            $data_fim = Carbon::createFromFormat("d/m/Y", $fields['data_fim']);        
        }else{
            $data_inicio = new Carbon;
            $data_fim = new Carbon;
        }

        if(!empty($fields['data_inicio_renegociacao']) && !empty($fields['data_fim_renegociacao'])){
            $data_inicio_renegociacao = Carbon::createFromFormat("d/m/Y", $fields['data_inicio_renegociacao']);
            $data_fim_renegociacao = Carbon::createFromFormat("d/m/Y", $fields['data_fim_renegociacao']);        
        }else{
            $data_inicio_renegociacao = new Carbon;
            $data_fim_renegociacao = new Carbon;
        }

        if(!empty($fields['data_inicio_forma_pagamento']) && !empty($fields['data_fim_forma_pagamento'])){
            $data_inicio_forma_pagamento = Carbon::createFromFormat("d/m/Y", $fields['data_inicio_forma_pagamento']);
            $data_fim_forma_pagamento = Carbon::createFromFormat("d/m/Y", $fields['data_fim_forma_pagamento']);        
        }else{
            $data_inicio_forma_pagamento = new Carbon;
            $data_fim_forma_pagamento = new Carbon;
        }
        if(empty($fields['cpf_cnpj_unico'])){
            $cliente = ClienteNasajon::select()->where('codigo', $codcad);
        }
        else{
            $cliente = ClienteNasajon::select()->where('codigo', $fields['cpf_cnpj_unico']);
        }
        $cliente->where('cpf_cnpj', '<>', '');
        $cliente->whereNotNull('cpf_cnpj');
        $cliente = $cliente->first();
        
        if(!is_null($cliente) && !in_array($codcad, $this->codigo_cliente_balcao)){            
            $cliente = $cliente->toArray();
        }else{
            if($array === true){
                return [
                    "pedidos" => [
                        "orcamentos"  => "",
                        "carteira"  => "",
                        "total"     => ""
                    ],
                    "notas_debito" => [
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "notas_credito" => "",
                    "titulos_faturados" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> "",
                        "ha_titulos_a_vencer" => false,
                        "ha_titulos_vencidas" => false,
                    ],
                    "titulos_terceiros" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "cheques_a_receber" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "atraso" => [
                        "ultima" => [
                            "data"         => "",
                            "quantidade"   => ""
                        ],
                        "maior" =>  [
                            "data"         => "",
                            "quantidade"   => ""
                        ]
                    ],
                    "vendas" => [
                        "ultima" => [
                            "data"     => "",
                            "valor"    => ""
                        ],
                        "maior" =>  [
                            "data"     => "",
                            "valor"    => ""
                        ]
                    ],
                    "total" => [
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "pago_ultimo_12_meses" => '',
                    "cliente_desde" => '',
                    "limite_credito" => '',
                    "mensagem_alerta" => '',
                    "messagem_agrupada" => '',
                    "messagem_agrupada_cnpjs" => '',
                    'cliente' => [
                        'codigo' => '',
                        'nome' => '',
                        'unico' => ''
                    ],
                    "cnpj_array" => '',
                    "titulos_pagos" => '',
                    "titulos" => '',
                    "valor_total" => '',
                    'em_atraso' => '',
                    "dias_total_atraso" => '',
                    "media_atraso" => '',
                    'vencimento_credito' => '',
                    "ultima_atualizacao" => '',
        
                    "titulo_modal" => '',

                    "consulta_serasa" => '',
                    "motivo_reavaliacao" => '',

                    "cheques_pre" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                ];
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados não encontrados',
                    'error' => [],
                    'response' => []
                ], 422);
            }
        }

        if(empty($cliente["cpf_cnpj"])){
            return response()->json([
                'status' => 'error',
                'message' => 'Cliente não possui CPF / CNPJ',
                'error' => [],
                'response' => []
            ], 422);
        }

        foreach ($cliente as $key => $value) {
            $cliente[$key] = utf8_encode($value);
        }
        $cpf_cnpj = $cliente["cpf_cnpj"];
        $cnpjs = [$cpf_cnpj];
        $codigos = [$cliente["codigo"]];
        $cnpjs_nome = [ ["cnpj" => $cpf_cnpj, "nome" => $cliente["nome"], 'codcad' => $cliente['codigo']]];
        $id_clientes = [];

        if(strlen(trim($cpf_cnpj)) == 18){
            $cpf_cnpj = substr($cpf_cnpj, 0, 10);
        }

        $grupoEmpresarialObj = GrupoEmpresarial::select()
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
					$grupo[] = $participante->raiz_cnpj;
				}
				$query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
			});
        }
        else {
			$clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
        }

		unset($clientesQuery);
        $clientesNasajon = $clientesNasajonQuery->orderBy('cpf_cnpj')
        ->groupBy('nome', 'codigo', 'cpf_cnpj', 'id')
        ->get();
        
		unset($clientesNasajonQuery);

        $blacklist = $this->statusBlackList($clientesNasajon->toArray());
        
        if(!isset($fields['cpf_cnpj_unico']) || is_null($fields['cpf_cnpj_unico'])){

            $codigos = $clientesNasajon->pluck('codigo')->toArray();
            $cnpjs = $clientesNasajon->pluck('cpf_cnpj')->toArray();
        }
        $id_clientes = $clientesNasajon->pluck('id')->toArray();

        foreach ($clientesNasajon as $key => $value) {
            $cnpjs_nome[] = ["cnpj"=>$value["cpf_cnpj"], "nome"=>$value["nome"], 'codcad' => $value['codigo']];
            $clientes[$value["codigo"]] = $value;
            unset($clientesNasajon[$key]);
        }

        $clientesNajason = ClienteNasajon::whereIn('cpf_cnpj', $cnpjs)->get();

        $cnpjs_nome = array_map("unserialize", array_unique(array_map("serialize", $cnpjs_nome)));

        sort($cnpjs_nome);

        $raiz_cnpj = $cpf_cnpj;
        $data_atulizacao_limite = '';

        if(!empty($cliente["DTULTATR"])){
            $ultimoAtrasoCarbon = Carbon::createFromFormat('Y-m-d h:i:s', $cliente["DTULTATR"]);
            $DTULTATR = $ultimoAtrasoCarbon->format('Y-m-d');
        }else {
            $DTULTATR = '';
        }

        $cliente["DTULTATR"] = $DTULTATR;
        $DIASULTATR = '';
        $DTMAIORATR = '';
        $DIASMAIORATR = '';
        $DTULTVND = '';
        $VLULTVND = '';
        $DTMAIORVND = '';
        $VLMAIORVND = '';
        $DTDESDE = $cliente["cliente_desde"];
        $ultima_atualizacao = $cliente["lastupdate"];
        
        if(!isset($fields['cpf_cnpj_unico']) || is_null($fields['cpf_cnpj_unico'])){
            foreach ($clientes as $key => $value) {
                if(!empty($value["DTULTATR"]) && strtotime($value["DTULTATR"]) > strtotime($DTULTATR)){
                    $DTULTATR = $value["DTULTATR"];
                }
                if(!empty($value["DIASULTATR"]) && intval($value["DIASULTATR"]) > intval($DIASULTATR)){
                    $DIASULTATR = $value["DIASULTATR"];
                }
                if(!empty($value["DTMAIORATR"]) && strtotime($value["DTMAIORATR"]) > strtotime($DTMAIORATR)){
                    $DTMAIORATR = $value["DTMAIORATR"];
                }
                if(!empty($value["DIASMAIORATR"]) && intval($value["DIASMAIORATR"]) > intval($DIASMAIORATR)){
                    $DIASMAIORATR = $value["DIASMAIORATR"];
                }
                if(!empty($value["DTULTVND"]) && strtotime($value["DTULTVND"]) > strtotime($DTULTVND)){
                    $DTULTVND = $value["DTULTVND"];
                }
                if(!empty($value["VLULTVND"]) && intval($value["VLULTVND"]) > floatval($VLULTVND)){
                    $VLULTVND = $value["VLULTVND"];
                }
                if(!empty($value["DTMAIORVND"]) && strtotime($value["DTMAIORVND"]) > strtotime($DTMAIORVND)){
                    $DTMAIORVND = $value["DTMAIORVND"];
                }
                if(!empty($value["VLMAIORVND"]) && intval($value["VLMAIORVND"]) > floatval($VLMAIORVND)){
                    $VLMAIORVND = $value["VLMAIORVND"];
                }
                if(!empty($value["cliente_desde"]) && strtotime($value["cliente_desde"]) < strtotime($DTDESDE)){
                    $DTDESDE = $value["cliente_desde"];
                }
            }
            $unico = false;
        }
        else{
            $unico = true;
        }
        
        $unico = false;

        $limite_de_credito = 0;
        
        $raiz_cnpj = $cpf_cnpj;

        if(!empty($cliente["DTULTATR"])){
            $ultimoAtrasoCarbon = Carbon::createFromFormat('Y-m-d', $cliente["DTULTATR"]);
            $DTULTATR = $ultimoAtrasoCarbon->format('Y-m-d');
        }
        else {
            $DTULTATR = '';
        }
        $cliente["DTULTATR"] = $DTULTATR;
        $cliente["DIASULTATR"] = $DIASULTATR;
        $cliente["DTMAIORATR"] = $DTMAIORATR;
        $cliente["DIASMAIORATR"] = $DIASMAIORATR;
        $cliente["DTULTVND"] = $DTULTVND;
        $cliente["VLULTVND"] = $VLULTVND;
        $cliente["DTMAIORVND"] = $DTMAIORVND;
        $cliente["VLMAIORVND"] = $VLMAIORVND;
        $cliente["DTDESDE"] = $DTDESDE;

    	$cheques_a_receber = ["a_vencer" => [], "vencidas" => [], "total"=>[]];

        $total_pago = 0.0;

        $data_atulizacao_limite = '';

        if((!isset($fields['cpf_cnpj_unico']) || is_null($fields['cpf_cnpj_unico'])) && isset($grupoEmpresarialObj)){
            $grupo_cnpj = array_merge([$grupoEmpresarialObj->raiz_cnpj], $grupoEmpresarialObj->participantes->pluck('raiz_cnpj')->toArray());
            $ClienteCreditoObj = ClienteCredito::with('createdby','updatedby')->whereIn('raiz_cnpj', $grupo_cnpj)->get();
        }
        else{
            $ClienteCreditoObj = ClienteCredito::with('createdby','updatedby')->where('raiz_cnpj', $raiz_cnpj)->get();
        }

        $consulta_serasa = '';
        $motivo_reavaliacao = '';
        $limite_credito_obj = [];

        if(!empty($ClienteCreditoObj)){
            foreach($ClienteCreditoObj as $limite_credito){
                $user = '';
                
                if(!empty($limite_credito->updatedby->name)){
                    $user = $limite_credito->updatedby->name;
                }else if(!empty($limite_credito->createdby->name)){
                    $user = $limite_credito->createdby->name;
                }

                $limite_credito_obj[] = [
                    'user' => $user,
                    'data' => parserData($limite_credito->data_atualizacao)
                ];
            }
        }

        if(is_null($ClienteCreditoObj)){
            $check_limite_credito = false;
        }
        else{
            $limite_de_credito = (float) $ClienteCreditoObj->sum('valor');
            if(!is_null($ClienteCreditoObj->min('ultima_consulta_serasa'))){
                $consulta_serasa = new Carbon($ClienteCreditoObj->min('ultima_consulta_serasa'));
                $consulta_serasa = $consulta_serasa->format('d/m/Y');
            }
            
            $motivo_reavaliacao = (!empty($ClienteCreditoObj[0]->motivo_reavaliacao)) ? $ClienteCreditoObj[0]->motivo_reavaliacao : '';

            $data_atulizacao_limite = new Carbon($ClienteCreditoObj->min('data_atualizacao'));
            $data_atulizacao_limite->addMonth(6);
            $data_atulizacao_limite = $data_atulizacao_limite->format('d/m/Y');
        }
    	$notas_credito = [];
    	$notas_debito = ["a_vencer" => [], "vencidas" => [], "total"=>[]];
    	$titulos_faturados = ["a_vencer" => [], "vencidas" => [], "total"=>[], "ha_titulos_a_vencer" => false, "ha_titulos_vencidas" => false];
    	$titulos_terceiros = ["a_vencer" => [], "vencidas" => [], "total"=>[]];
        $titulosNasajon = TitulosEmAbertoNasajonPortal::whereIn('cod_cliente', $codigos);
        if(Auth::user()->hasRole('Juridico') || Auth::user()->codigo_representante == '998'){
            $TitulosEmAbertoNasajonPortal = TitulosEmAbertoNasajonPortal::select()
                ->whereHas('vendedorTitulo', function($query){
                    $query->where('vendedor_codigo','998');
                })
                ->where('origem_texto', '<>', 'Renegociação')
                ->whereNotIn('codigo', [30])
                ->first();

            if(empty($TitulosEmAbertoNasajonPortal)){
                $titulosNasajon->whereHas('vendedorTitulo', function($query){
                    $query->where('vendedor_codigo','998');
                })
                ->whereNotIn('codigo', [30]);
            }            
        }
        $titulosNasajon = $titulosNasajon->get();
        
        $titulosNasajon->each(function ($item) use (&$titulos_faturados, &$titulos_terceiros){
            $agoraCarbon = Carbon::Now()->setTime(0,0,0);
            $vencimentoCarbon = Carbon::createFromFormat('Y-m-d', $item->vencimento)->setTime(0,0,0);

            $key = $item->numero . '' . $item->codigo;
            if(!isset($titulos_faturados["total"][$key])){
                $titulos_faturados["total"][$key] = floatval($item->saldotitulo);
                if($item->titulo_de_terceiro === false){
                    if($vencimentoCarbon->gte($agoraCarbon)) {
                        $titulos_faturados["a_vencer"][] = floatval($item->saldotitulo);
                        $titulos_faturados["ha_titulos_a_vencer"] = true;
                    }
                    else{
                        $titulos_faturados["vencidas"][] = floatval($item->saldotitulo);
                        $titulos_faturados["ha_titulos_vencidas"] = true;
                    }
                }
                else if($item->titulo_de_terceiro === true){
                    if($vencimentoCarbon->gte($agoraCarbon)) {
                        $titulos_terceiros["a_vencer"][] = floatval($item->saldotitulo);
                    }
                    else{
                        $titulos_terceiros["vencidas"][] = floatval($item->saldotitulo);
                    }
                }
            }
        });
        
        $pedidos = [ "orcamentos" => 0.0, "carteira" => 0.0, "total" => 0.0 ];

        $posicao_cliente = PosicaoClienteNasajon::whereIn('cod_cliente', $codigos)->get()->toArray();
        foreach ($posicao_cliente as $key => $value) {
            
            if(!empty($value['total_pago_12_meses'])){
                $total_pago += floatval($value['total_pago_12_meses']);
            }
            if(!empty($value['creditos_a_vencer'])){
                $notas_credito[] = floatval($value['creditos_a_vencer']);
            }
            if(!empty($value['creditos_vencidos'])){
                $notas_credito[] = floatval($value['creditos_vencidos']);
            }

            if($value['dias_maior_titulo_atraso'] > $cliente['DIASMAIORATR']){
                $cliente['DTMAIORATR'] = $value['data_maior_titulo_atraso'];
                $cliente['DIASMAIORATR'] = $value['dias_maior_titulo_atraso'];
            }

            if(!empty($value['data_ultimo_titulo_atraso'])){
                $ultimoAtrasoLinha = Carbon::createFromFormat('Y-m-d', $value['data_ultimo_titulo_atraso']);
                $cliente['DTULTATR'] = $ultimoAtrasoLinha->format('Y-m-d');
            }else{
                $cliente['DTULTATR'] = '';
            }
            
            $cliente['DIASULTATR'] = $value['dias_ultimo_titulo_atraso'];

            if(!empty($value['emissao_ultima_venda'])){
                if ( $cliente['DTULTVND'] <  $value['emissao_ultima_venda']){
                    $ultimaVendaLinha = Carbon::createFromFormat('Y-m-d', $value['emissao_ultima_venda']);
                    $cliente['DTULTVND'] = $ultimaVendaLinha->format('Y-m-d H:i:s');
                    $cliente['VLULTVND'] = $value['valor_ultima_venda'];
                }
            }

            if($cliente['VLMAIORVND'] < $value['valor_maior_venda']){
                $cliente['VLMAIORVND'] = $value['valor_maior_venda'];
                $cliente['DTMAIORVND'] = $value['emissao_maior_venda'];
            }
            
            $pedidos['total'] += $pedidos["carteira"] += 0;
        }

        // Contas a receber Nasajon
        $NotasDebitoReceberObj = NotasDebitoReceberNasajon::whereIn('cod_cliente', $codigos)->get();
        
        foreach($NotasDebitoReceberObj as $notas){

            $agoraCarbon = Carbon::now();
            $vencimentoCarbon = Carbon::createFromFormat('Y-m-d', $notas['vencimento']);

            if($vencimentoCarbon->gt($agoraCarbon)){
                $notas_debito['vencidas'] += floatval($notas['valor']);

                if($agoraCarbon->diffInDays($vencimentoCarbon) > $cliente['DIASMAIORATR']){
                    $cliente['DTMAIORATR'] = $agoraCarbon->format('Y-m-d');
                    $cliente['DIASMAIORATR'] = $agoraCarbon->diffInDays($vencimentoCarbon);
                }

                $cliente['DTULTATR'] = $agoraCarbon->format('Y-m-d');
                $cliente['DIASULTATR'] = $agoraCarbon->diffInDays($vencimentoCarbon);

            }else{
                $notas_debito["a_vencer"] += floatval($notas['valor']);
            }
            $notas_debito["total"] += floatval($notas['valor']);
        }
        unset($NotasDebitoReceberObj);

        $total_pedidos_em_aberto = 0.0;

        $PedidoPortalEmAberto = PedidoPortal::
            whereIn('cod_cliente', $codigos)
            ->whereNotIn('status_pedido', [3, 5, 7])
            ->get();

        foreach($PedidoPortalEmAberto as $pedido){
            if(!isset($pedido->valor_total->total)){
                continue;
            }
            $total_pedidos_em_aberto += $pedido->valor_total->total;
        }
        unset($PedidoPortalEmAberto);
        $pedidos["orcamentos"] = $total_pedidos_em_aberto;

        $pedidoVendasObj = PedidosVendaNasajon::query()
        ->whereIn('cliente', $id_clientes)
        ->whereIn('situacao_descricao', $this->pedidos_nasajon_status_exibidos)
        ->where(function ($query){
            $query->where('grupodeoperacao', 'VENDA')
                ->orWhereNull('grupodeoperacao');
        })
        ->where('rascunho', false)
        ->with('valorTotalFaturado')
        ->get();
        
        foreach($pedidoVendasObj as $pedido_venda){
            $pedidos['carteira'] += !empty($pedido_venda->valorTotalFaturado) ? floatval($pedido_venda->valorTotalFaturado->total_faturado) : 0;
            $pedidos['total'] += !empty($pedido_venda->valorTotalFaturado) ? floatval($pedido_venda->valorTotalFaturado->total_faturado) : 0;
        }


        $pedidos["total"] = floatval($pedidos["carteira"]) + floatval($pedidos["orcamentos"]);

    	$notas_debito["vencidas"] = array_sum($notas_debito["vencidas"]);
    	$notas_debito["a_vencer"] = array_sum($notas_debito["a_vencer"]);
    	$notas_debito["total"] = floatval($notas_debito["a_vencer"]) + floatval($notas_debito["vencidas"]);

    	$cheques_a_receber["vencidas"] = array_sum($cheques_a_receber["vencidas"]);
    	$cheques_a_receber["a_vencer"] = array_sum($cheques_a_receber["a_vencer"]);
    	$cheques_a_receber["total"] = floatval($cheques_a_receber["a_vencer"]) + floatval($cheques_a_receber["vencidas"]);

    	$notas_credito = array_sum($notas_credito);
    	$titulos_faturados["vencidas"] = array_sum($titulos_faturados["vencidas"]);
    	$titulos_faturados["a_vencer"] = array_sum($titulos_faturados["a_vencer"]);
        $titulos_faturados["total"] = floatval($titulos_faturados["a_vencer"]) + floatval($titulos_faturados["vencidas"]);
        
        $titulos_terceiros["vencidas"] = array_sum($titulos_terceiros["vencidas"]);
    	$titulos_terceiros["a_vencer"] = array_sum($titulos_terceiros["a_vencer"]);
    	$titulos_terceiros["total"] = floatval($titulos_terceiros["a_vencer"]) + floatval($titulos_terceiros["vencidas"]);

        $total["a_vencer"] = (floatval($notas_debito["a_vencer"]) + floatval($cheques_a_receber["a_vencer"]) + floatval($titulos_faturados["a_vencer"] ) + floatval($titulos_terceiros['a_vencer'] ));
        $total["vencidas"] = (floatval($notas_debito["vencidas"]) + floatval($cheques_a_receber["vencidas"]) + floatval($titulos_faturados["vencidas"] ) + floatval($titulos_terceiros['vencidas'] ));
        $total["total"] = (floatval($notas_debito["total"]) + floatval($cheques_a_receber["total"]) + floatval($titulos_faturados["total"]) + floatval($titulos_terceiros['total'] ));
        $messagem_agrupada = "";
        $messagem_agrupada_cnpjs = "";

        $periodo = [$data_inicio->format('Y-m-d 00:00:00') ?? date("Y-m-d 00:00:00", strtotime('-1 year')), $data_fim->format('Y-m-d 23:59:59')??date("Y-m-d 23:59:59")];
        $periodo_forma_pagamento = [$data_inicio_forma_pagamento->format('Y-m-d 00:00:00') ?? date("Y-m-d", strtotime('-1 year')), $data_fim_forma_pagamento->format('Y-m-d 23:59:59')??date("Y-m-d")];
        $periodo_renegociacao = [$data_inicio_renegociacao->format('Y-m-d 00:00:00') ?? date("Y-m-d", strtotime('-1 year')), $data_fim_renegociacao->format('Y-m-d 23:59:59')??date("Y-m-d")];

        $estabelecimentos = returnEmpresasNasajonView();

        $busca_estabelecimentos = ['00', '01', '02', '03', '04', '05', '06', '07', '08'];

        if(count($cnpjs_nome) > 1){
            if(!isset($fields['cpf_cnpj_unico']) || empty($fields['cpf_cnpj_unico'])){
                if (!is_null($grupoEmpresarialObj)){
                    $messagem_agrupada_cnpjs = "<p>Valores agrupados pelo grupo empresarial <span id='modal_nome'>" .  $grupoEmpresarialObj->nome . '</p>';
                    
                    foreach($cnpjs_nome as $cnpj_nome){
                        $messagem_agrupada_cnpjs .= $cnpj_nome['cnpj'] . ' - ' . $cnpj_nome['nome'] . '<br>';
                    }

                    $titulo_modal = $grupoEmpresarialObj->nome;
                }
                else{
                    $messagem_agrupada_cnpjs = "<p>Valores agrupados pela raiz de CNPJ <span id='modal_nome'>" . $raiz_cnpj . '</p>';
                    
                    foreach($cnpjs_nome as $cnpj_nome){
                        $messagem_agrupada_cnpjs .= $cnpj_nome['cnpj'] . ' - ' . $cnpj_nome['nome'] . '<br>';
                    }

                    $titulo_modal = $raiz_cnpj;
                }
            }
            else{            
                $messagem_agrupada_cnpjs = "<div>Mostrando valores individuais do CNPJ: <span id='modal_nome'>" . $cliente['cpf_cnpj'] . " - " . $cliente['nome'] . "</span></div>";

                $titulo_modal = $cliente['cpf_cnpj'] . " - " . $cliente['nome'];
            }

            foreach ($cnpjs_nome as $key => $value) {
                $cnpj_array[] = ['codcad' => $value["codcad"], 'label' => $value["nome"] . ' - ' . $value['cnpj'], 'selecionado' => (isset($fields['cpf_cnpj_unico']) && $value['codcad'] == $fields['cpf_cnpj_unico']) ? true : false];
                $cnpjs[] = $value['cnpj'];
            }

        }
        else{

            $messagem_agrupada_cnpjs = "<div>Mostrando valores individuais do CNPJ: <span id='modal_nome'>" . $cliente['cpf_cnpj'] . " - " . $cliente['nome'] . "</span></div>";

            $titulo_modal = $cliente['cpf_cnpj'] . " - " . $cliente['nome'];

        }
        
        

        $titulos = 0;
        $valor_total = 0;
        $em_atraso = 0;
        $dias_total_atraso = 0;
        $media_atraso = 0;

        if ($em_atraso > 0){
            $media_atraso = $dias_total_atraso / $em_atraso;
        }
        else{
            $media_atraso = '';
        }

        $maiorAtrasoEmAbertoNasajon = TitulosEmAbertoNasajonPortal::selectRaw('extract(days from now() - vencimento) as atraso, titulo_emissao')
        ->whereIn('cod_cliente', $clientesNajason->pluck('codigo'))
        ->where('vencimento', '<', Carbon::Now() )
        ->orderBy(DB::Raw('extract(days from now() - vencimento)'), 'desc')
        ->first();
        
        if (!empty($cliente['DTMAIORATR'])){
            if(!empty($maiorAtrasoEmAbertoNasajon->atraso)){
                if($maiorAtrasoEmAbertoNasajon->atraso > $cliente['DTMAIORATR']){
                    $cliente['DTMAIORATR'] = $maiorAtrasoEmAbertoNasajon->titulo_emissao;
                    $cliente['DIASMAIORATR'] = $maiorAtrasoEmAbertoNasajon->atraso;
                }
            }
        }
        else{
            $cliente['DTMAIORATR'] = empty($maiorAtrasoEmAbertoNasajon->titulo_emissao)? '':$maiorAtrasoEmAbertoNasajon->titulo_emissao;
            $cliente['DIASMAIORATR'] = empty($maiorAtrasoEmAbertoNasajon->atraso)? '':$maiorAtrasoEmAbertoNasajon->atraso;
        }
        $ultimoAtrasoEmAbertoNasajon = TitulosEmAbertoNasajonPortal::selectRaw('extract(days from now() - vencimento) as atraso, titulo_emissao')
        ->whereIn('cod_cliente', $clientesNajason->pluck('codigo'))
        ->where('vencimento', '<', Carbon::Now() )
        ->orderBy('titulo_emissao', 'desc')
        ->first();

        if(!empty($ultimoAtrasoEmAbertoNasajon)){

            if(!empty($cliente['DTULTATR'])){
                $ultimoAtraso = Carbon::createFromFormat("Y-m-d", $cliente['DTULTATR']);
                $ultimoAtrasoNasajon = Carbon::createFromFormat("Y-m-d", $ultimoAtrasoEmAbertoNasajon->titulo_emissao);
                
                if($ultimoAtrasoNasajon->gt($ultimoAtraso)){
                    $cliente['DTULTATR'] = $ultimoAtrasoNasajon->format('Y-m-d');
                    $cliente['DIASULTATR'] = $ultimoAtrasoEmAbertoNasajon->atraso;
                }
            }
            else{
                $ultimoAtrasoNasajon = Carbon::createFromFormat("Y-m-d", $ultimoAtrasoEmAbertoNasajon->titulo_emissao);

                $cliente['DTULTATR'] = $ultimoAtrasoNasajon->format('Y-m-d');
                $cliente['DIASULTATR'] = $ultimoAtrasoEmAbertoNasajon->atraso;
            }
        }

        //Nota de Credito
        $notas_credito = 0;

        if(isset($fields['cpf_cnpj_unico']) && !empty($fields['cpf_cnpj_unico'])){
            $clientesCredito = $clientesNajason->where('codigo', $fields['cpf_cnpj_unico']);
        }
        else{
            $clientesCredito = $clientesNajason;
        }

        $notasCredito = NotasCreditoReceberNasajon::
            whereIn('cod_cliente', $clientesCredito->pluck('codigo'))
            ->get();

        $notas_credito += $notasCredito->sum('valor');

        //Notas de Débito
        $agoraCarbon = Carbon::Now();

        $NotasDebitoReceberObj = NotasDebitoReceberNasajon::select()->whereIn('cod_cliente', $clientesNajason->pluck('codigo'))->get();
        $notas_debito["vencidas"] = $NotasDebitoReceberObj->where('vencimento', '<', $agoraCarbon)->sum('valor');
        $notas_debito["a_vencer"] = $NotasDebitoReceberObj->where('vencimento', '>=', $agoraCarbon)->sum('valor');
        $notas_debito["total"] = $NotasDebitoReceberObj->sum('valor');

        $total_pre_pagos = 0;
        $chque_pre = 0;
        //Pedidos pré-pagos
        $pedidosPrePagosObj = PedidosPrePago::with(['pedidoNasajon' => function($query){
            $query->where('grupodeoperacao', 'VENDA')
                ->where('rascunho', false);
        }, 'pedidoNasajon.nota'])
            ->whereRaw('valor - valor_pago > 0')
            ->where(function($query) use ($clientesNajason, $fields, $pedidoVendasObj){
                if(empty($pedidoVendasObj)){
                    $pedidoVendasObj = PedidosVendaNasajon::select('id')
                    ->where(function ($query) use ($clientesNajason, $fields){

                        if(isset($fields['cpf_cnpj_unico']) && !empty($fields['cpf_cnpj_unico'])){
                            $query->where("cliente_codigo", $fields['cpf_cnpj_unico']);
                        }
                        else{
                            $query->whereIn("cliente", $clientesNajason->pluck('id'));
                        }
                    })
                    ->where('grupodeoperacao', 'VENDA')
                    ->where('rascunho', false)
                    ->get();
                }
                

                $query->whereIn('pedido_nasajon_id', $pedidoVendasObj->pluck('id'));
            })
            ->get();
        
        $valor_total_prepago = $pedidosPrePagosObj->sum('valor')??0;
        $valor_baixado_prepago = $pedidosPrePagosObj->sum('valor_pago')??0;
        $total_pre_pagos = $valor_total_prepago - $valor_baixado_prepago;

        $chque_pre = 0;

        $renegociacao_titulos_quantidade = RenegociacaoTitulo::select();
        if(isset($fields['cpf_cnpj_unico']) && !empty($fields['cpf_cnpj_unico'])){
            $renegociacao_titulos_quantidade->where("cliente_cpf_cnpj", $cliente['cpf_cnpj']);
        }
        else{
            $renegociacao_titulos_quantidade->whereIn('cliente_cpf_cnpj', $clientesNajason->pluck('cpf_cnpj'));
        }
        $renegociacao_titulos_quantidade->whereNotIn('status_renegociacao_titulos_id', [3]);
        $renegociacao_titulos_quantidade = $renegociacao_titulos_quantidade->count();

        $cliente_pre = '';
        $ClientePrePagoObj = ClientePrePago::whereIn('cpf_cnpj', $clientesNajason->pluck('cpf_cnpj'))->exists();
        if($ClientePrePagoObj == true){
            $cliente_pre = ' - <b class="text-danger">Liberado Pedido Pré</b>';
        }

        // Cheques devolvidos
        $ChequesDevolvidosPortalObj = [];

        $ChequeDevolvidosNasajonObj = [];

        // Cheques devolvidos tratados
        $cheque_devolvido = [
            'quantidade' => 0,
            'valor' => 0,
        ];
        
        // Notas devolvidas
        //$devolucaoNotas = new DevolucaoNotaController;
        //$devolucoes = $devolucaoNotas->modalDevolucoes($cnpjs, $codigos);
        $devolucoes = [];
        //
        $total["a_vencer"] += ($total_pre_pagos + $chque_pre);
        $total["total"] += ($total_pre_pagos + $chque_pre - $notas_credito);

    	$return = [
            "pedidos" => [
                "orcamentos"  => (floatval($pedidos["orcamentos"]) > 0.0) ? parserValor($pedidos["orcamentos"]) : "",
                "carteira"  => (floatval($pedidos["carteira"]) > 0.0) ? parserValor($pedidos["carteira"]) : "",
                "total"     => (floatval($pedidos["total"]) > 0.0) ? parserValor($pedidos["total"]) : ""
            ],
			"notas_debito" => [
				"a_vencer" 	=> (floatval($notas_debito["a_vencer"]) > 0.0) ? parserValor($notas_debito["a_vencer"]) : "",
				"vencidas" 	=> (floatval($notas_debito["vencidas"]) > 0.0) ? parserValor($notas_debito["vencidas"]) : "",
				"total"		=> (floatval($notas_debito["total"]) > 0.0) ? parserValor($notas_debito["total"]) : ""
			],
            "notas_credito" => (floatval($notas_credito) > 0.0) ? parserValor($notas_credito) : "",
			"titulos_faturados" =>[
				"a_vencer" 	=> (floatval($titulos_faturados["a_vencer"]) > 0.0) ? parserValor($titulos_faturados["a_vencer"]) : "",
				"vencidas" 	=> (floatval($titulos_faturados["vencidas"]) > 0.0) ? parserValor($titulos_faturados["vencidas"]) : "",
				"total"		=> (floatval($titulos_faturados["total"]) > 0.0) ? parserValor($titulos_faturados["total"]) : "",
                "ha_titulos_a_vencer" => isset($titulos_faturados["ha_titulos_a_vencer"])? $titulos_faturados["ha_titulos_a_vencer"] : false,
                "ha_titulos_vencidas" => isset($titulos_faturados["ha_titulos_vencidas"])? $titulos_faturados["ha_titulos_vencidas"] : false,
            ],
            "titulos_terceiros" =>[
                "a_vencer" 	=> (floatval($titulos_terceiros["a_vencer"]) > 0.0) ? parserValor($titulos_terceiros["a_vencer"]) : "",
				"vencidas" 	=> (floatval($titulos_terceiros["vencidas"]) > 0.0) ? parserValor($titulos_terceiros["vencidas"]) : "",
				"total"		=> (floatval($titulos_terceiros["total"]) > 0.0) ? parserValor($titulos_terceiros["total"]) : ""
            ],
			"cheques_a_receber" =>[
				"a_vencer" 	=> (floatval($cheques_a_receber["a_vencer"]) > 0.0) ? parserValor($cheques_a_receber["a_vencer"]) : "",
				"vencidas" 	=> (floatval($cheques_a_receber["vencidas"]) > 0.0) ? parserValor($cheques_a_receber["vencidas"]) : "",
				"total"		=> (floatval($cheques_a_receber["total"]) > 0.0) ? parserValor($cheques_a_receber["total"]) : ""
            ],
            "pedidos_pre_pagos" => [
                'total' => ($total_pre_pagos > 0) ? parserValor($total_pre_pagos) : ''
            ],
			"atraso" => [
				"ultima" => [
					"data"         => (!empty($cliente["DTULTATR"]) && date('Y-m-d', strtotime($cliente["DTULTATR"])) != '1900-01-01') ? parserData($cliente["DTULTATR"]) : "",
					"quantidade"   => (!empty($cliente["DIASULTATR"])) ? intval($cliente["DIASULTATR"]) : ""
				],
				"maior" =>  [
					"data"         => (!empty($cliente["DTMAIORATR"]) && date('Y-m-d', strtotime($cliente["DTMAIORATR"])) != '1900-01-01') ? parserData($cliente["DTMAIORATR"]) : "",
					"quantidade"   => (!empty($cliente["DIASMAIORATR"])) ? intval($cliente["DIASMAIORATR"]) : ""
				]
			],
			"vendas" => [
				"ultima" => [
					"data"     => (!empty($cliente["DTULTVND"]) && date('Y-m-d', strtotime($cliente["DTULTVND"])) != '1900-01-01') ? parserData($cliente["DTULTVND"]) : "",
					"valor"    => (!empty($cliente["VLULTVND"])) ? "R$ ".parserValor($cliente["VLULTVND"]) : ""
				],
				"maior" =>  [
					"data"     => (!empty($cliente["DTMAIORVND"]) && date('Y-m-d', strtotime($cliente["DTMAIORVND"])) != '1900-01-01') ? parserData($cliente["DTMAIORVND"]) : "",
					"valor"    => (!empty($cliente["VLMAIORVND"])) ? "R$ ".parserValor($cliente["VLMAIORVND"]) : ""
				]
            ],
			"total" => [
				"a_vencer" 	=> (floatval($total["a_vencer"]) > 0.0) ? parserValor($total["a_vencer"]) : "",
				"vencidas" 	=> (floatval($total["vencidas"]) > 0.0) ? parserValor($total["vencidas"]) : "",
				"total"		=> (floatval($total["total"]) > 0.0) ? parserValor($total["total"]) : ""
			],
			"pago_ultimo_12_meses" => (!empty($total_pago)) ? "R$ ".parserValor($total_pago) : "",
			"cliente_desde" => parserData($cliente["DTDESDE"]),
            "limite_credito" => "R$ ".parserValor($limite_de_credito) . $cliente_pre,
            "limite_credito_obj" => $limite_credito_obj,
            "mensagem_alerta" => '',
            "messagem_agrupada" => $messagem_agrupada,
			"messagem_agrupada_cnpjs" => $messagem_agrupada_cnpjs,
            "ultima_atualizacao" => (!empty($ultima_atualizacao)) ? parserData($ultima_atualizacao) : '',
            'cliente' => [
                'codigo' => $cliente['codigo'],
                'nome' => $cliente['nome'],
                'unico' => $unico
            ],
            "cnpj_array" => $cnpj_array??[],
            "titulos" => $titulos,
            "valor_total" => 'R$ ' . parserValor($valor_total),
            'em_atraso' => $em_atraso,
            "dias_total_atraso" => $dias_total_atraso,
            "media_atraso" => round(intval($media_atraso)*100)/100,
            'vencimento_credito' => $data_atulizacao_limite,
            "baixar_titulos_link" => '<button class="btn azul-sistema" onclick="baixarTitulos(\'' . $cliente['codigo'] . '\', \'Baixar Títulos - '.$cliente['cpf_cnpj'] . " - " . str_replace("'", " ", $cliente['nome']).'\', \''.$unico.'\')">Baixar Títulos</button>',
            "renegociacao_titulo_link" => '<button class="btn azul-sistema btn-adicionar-negociacao" type="button" onclick="modalSelecionarTituloParaNegociacao()">Renegociação</button>',
            "cobranca_jucicial" => (in_array(Auth::id(), [57, 682, 42, 97, 27]) || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Juridico') || Auth::user()->codigo_representante == '998') ? '<button class="btn azul-sistema btn-adicionar-negociacao" type="button" onclick="modalCobrancaJudicial()">Cob. Judicial</button>' : '',
            "renegociacao_titulos_quantidade" => $renegociacao_titulos_quantidade,
            "informacoes_cliente_link" => '<button class="btn azul-sistema" onclick="modal_info_cliente(\'' . $cliente['codigo'] . '\')">Dados do Cliente</button>',

            "titulo_modal" => $titulo_modal,
            
            "consulta_serasa" => $consulta_serasa,
            "motivo_reavaliacao" => $motivo_reavaliacao,

            "cheques_pre" =>[
                "total"		=> $chque_pre > 0 ? parserValor($chque_pre) : ''
            ],
            'cheques_devolvidos' => [
                'quantidade' => ($cheque_devolvido['quantidade'] > 0? $cheque_devolvido['quantidade']:''),
                'valor' => $cheque_devolvido['valor'] > 0? '<a href=\'#\' onclick="showChequesDevolvidos(\'' . Crypt::encrypt($cnpjs) . '\', true)">' . parserValor($cheque_devolvido['valor']) . '</a>': ''
            ],
            'cheques_devolvidos_nao_negociados' => [
                'quantidade' => '',
                'valor' => ''
            ],
            'cliente_pre_pago' => $ClientePrePagoObj,
            'devolucoes' => $devolucoes,
            'blacklist' => $blacklist
        ];
        
        if ($array === true){
           return $return;
        }
        else{
    	   return response()->json(["status" => "success", "data" => $return]);
        }

    }

    public function modal(Request $request){
        try{
            $codigo = (string) Crypt::decrypt($request->id_encriptada);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $request = new Request([
            'codigo' => $codigo
        ]);

        $dados = [
            'cliente' => [
                'codigo' => $codigo,
                'unico' => '',
            ],
            'informacoes_cliente_link' => '',
            'cnpj_array' => [],
            'titulo_modal' => '',
            'titulos_faturados' => '',
            'titulos_faturados' => [
                'a_vencer' => '',
                'vencidas' => '',
                'total' => '',
            ],
            'notas_debito' => [
                'a_vencer' => '',
                'vencidas' => '',
                'total' => '',
            ],
            'cheques_a_receber' => [
                'a_vencer' => '',
                'vencidas' => '',
                'total' => '',
            ],
            'total' => [
                'a_vencer' => '',
                'vencidas' => '',
                'total' => '',
            ],
            'notas_credito' => '',
            'unico' => '',
            'pedidos' => [
                'orcamentos' => '',
                'carteira' => '',
                'total' => '',
            ],
            'cliente_desde' => '',
            'limite_credito' => '',
            'vencimento_credito' => '',
            'blacklist' => '',
            'consulta_serasa' => '',
            'motivo_reavaliacao' => '',
            'messagem_agrupada_cnpjs' => '',
            'atraso' => [
                'ultima' => [
                    'data' => '',
                    'quantidade' => '',
                    'valor' => '',
                ],
                'maior' => [
                    'data' => '',
                    'quantidade' => '',
                    'valor' => '',
                ],
            ],
            'vendas' => [
                'ultima' => [
                    'data' => '',
                    'quantidade' => '',
                    'valor' => '',
                ],
                'maior' => [
                    'data' => '',
                    'quantidade' => '',
                    'valor' => '',
                ],
            ],
            'valores_a_faturar' => '',
        ];

        return view('programs.posicao_sintetica_cliente.modal')->with(['dados' => $dados]);
    }

    public function returnTitulosFaturados(Request $request, $coluna){
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
      
        $empresa = returnEmpresasNasajonView();
        $titulos_faturados = ["a_vencer"=>[], "vencidos"=>[], "total"=>[]];

        $titulosNasajon = TitulosEmAbertoNasajon::with(['devolucoes' => function($query){
                $query->whereNotIn('devolucao_nota_status_id', [7, 8,11]);
            },'cenprot', 'cliente'])
            ->selectRaw("*, regexp_replace(numero, '.[0-9]{5}.[0-9]{2}.', '') as nota");
        $titulosNasajon->with(['vendedorTitulo']);
        if(Auth::user()->hasRole('Juridico') || Auth::user()->codigo_representante == '998'){
            $TitulosEmAbertoNasajonPortal = TitulosEmAbertoNasajonPortal::select()
                ->whereHas('vendedorTitulo', function($query){
                    $query->where('vendedor_codigo','998');
                })
                ->where('origem_texto', '<>', 'Renegociação')
                ->whereNotIn('codigo', [30])
                ->first();

            if(empty($TitulosEmAbertoNasajonPortal)){
                $titulosNasajon->whereHas('vendedorTitulo', function($query){
                    $query->where('vendedor_codigo','998');
                })
                ->whereNotIn('codigo', [30]);
            }  
        }
        $titulosNasajon->whereIn('cod_cliente', $clientesNasajon->pluck('codigo'))
            ->where('titulo_de_terceiro', false)
            //->where('saldotitulo', '>', 0)
            ->orderBy('nota_numero')
            ->orderBy('parcela')
            ->get();

        $cnpjCliente = $clientesNasajon->pluck('codigo');
       
        $cliente     = false;

        $totalizadores = [
            'valor' => [
                'a_vencer' => 0, 
                'vencidos' => 0,
                'total' => 0
            ],

            'saldo' => [
                'a_vencer' => 0, 
                'vencidos' => 0,
                'total' => 0
            ],
            'juros' => [
                'a_vencer' => 0, 
                'vencidos' => 0,
                'total' => 0
            ],
            'pagamento_parcial' => [
                'a_vencer' => 0, 
                'vencidos' => 0,
                'total' => 0
            ],
            'valor_atual' => [
                'a_vencer' => 0, 
                'vencidos' => 0,
                'total' => 0
            ],
        ];

        $titulosNasajon->each(function ($item) use (&$titulos_faturados, &$empresa, &$totalizadores){

                $agoraCarbon = Carbon::Now()->setTime(0,0,0);
                $vencimentoCarbon = Carbon::createFromFormat('Y-m-d', $item->vencimento)->setTime(0,0,0);
        
                $nota_numero = '';
                
                if(empty($item->nota_numero)){
                    $nota_numero = $item->nota;
                }else{
                    $nota_numero = $item->nota_numero;
                }

                $value['estabelecimento'] = $item->codigo;
                $value['estabelecimento_nome']  = $empresa[(int) $item->codigo];
                $value['nota_numero']           = $nota_numero;
                $value['id_titulo']             = $item->titulo_id;
                $value['titulo_renegociado']    = isset($item->tituloNovoRenegociado) ? $item->tituloNovoRenegociado->renegociacao_liberada : false;
                $value['parcela']               = $item->parcela;
                $value['data_emissao']          = $item->titulo_emissao;
                $value['data_vencimento_sql']   = $item->vencimento;
                $value['data_vencimento']       = parserData($item->vencimento);
                $value['status']                = '';
                $value['valor_original']        = $item->valor;
                $value['pagamento_parcial']     = $item->valor - ($item->saldotitulo - $item->juros) > 0 ? $item->valor - ($item->saldotitulo - $item->juros) : 0;
                $value['valor_atual']           = $item->saldotitulo - $item->juros > 0 ? $item->saldotitulo - $item->juros : 0;
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
                $value['numero_titulo_renegociado'] = '';
                $value['vencimento_titulo_renegociado'] = '';

                if(!empty($item->numero_titulo_renegociado) || !empty($item->vencimento_titulo_renegociado)){
                    $value['numero_titulo_renegociado'] = $item->numero_titulo_renegociado;
                    $value['vencimento_titulo_renegociado'] = !empty($item->vencimento_titulo_renegociado)?parserData($item->vencimento_titulo_renegociado):'';
                }else{
                    if (strpos($item->observacao, '### Titulo ') !== false){
                        preg_match('/\d+\.\d+(\.\d)*/', $item->observacao, $titulo_original);
                        if(isset($titulo_original[0])) {
                            $tituloPagoObj = TitulosPagosNasajon::where('numero', $titulo_original[0])->first();
                            
                            if(!empty($tituloPagoObj)){
                                $value['numero_titulo_renegociado'] = $tituloPagoObj->numero;
                                $value['vencimento_titulo_renegociado'] = !empty($tituloPagoObj->vencimento)?parserData($tituloPagoObj->vencimento):'';
                            }
                        }
                    }
                }

                $value['banco'] = ($item->enviado_para_banco == true)|| ( $item->banco_codigo == 'Ragazzi COB ADM')? $item->banco_codigo : 'CARTEIRA';

                // Status do título
                if($item->tem_prorrogacao === true){
                    $value['status'] .= "<a href='#' class='status-titulo status-verde' data-toggle='popover' data-html='true' title='Prorrogado' data-content='Vencimento original " . parserData($item->vencimento_original) . "'><span data-toggle='tooltip' data-html='true' title='Prorrogado'>P</span></a>";
                }
                if($agoraCarbon->gt(Carbon::parse($item->vencimento)->addDays(30))){
                    $value['status'] .= "<a href='#' class='status-titulo status-roxo' data-toggle='tooltip' data-html='true' title='Cobrança Jurídica'>CJ</a>";
                }
                else if($agoraCarbon->gt(Carbon::parse($item->vencimento)->addDays(10))){
                    $verificacao_administrativa_ragazzi = false;

                    foreach($item->vendedorTitulo as $vendedor){
                        if($vendedor->vendedor_codigo == '998'){
                            $verificacao_administrativa_ragazzi = true;
                        }
                    }

                    if($verificacao_administrativa_ragazzi){
                        $value['status'] .= "<a href='#' class='status-titulo status-amarelo' data-toggle='tooltip' data-html='true' title='Cobrança Administrativa Ragazzi'>CA</a>";
                    }else{
                        $value['status'] .= "<a href='#' class='status-titulo status-laranja' data-toggle='tooltip' data-html='true' title='Cobrança Administrativa MN'>CM</a>";
                    }
                    
                }
                if($item->devolucoes->isNotEmpty()){
                    $value['status'] .= ' <a href="#" class="status-titulo status-azul devolucoes_link" data-toggle="popover" data-html="true" title="Processos de Devolução" data-content="<b>Processos de devolução:</b><br>'. $item->devolucoes->map(function($devolucao){
                        return '<a href=\'#\' class=\'devolucoes_alert\' data-id=\'' . Crypt::encrypt($devolucao->id) . '\'>' . $devolucao->id . '</a> - Iniciado ' . $devolucao->created_at->format('d/m/Y') . '<br>';
                    }
                    )->implode(', ').'"><span data-toggle=\'tooltip\' data-html=\'true\' title=\'Processo de devolução\'>D</span></a>';
                }
                if($item->enviado_para_cartorio === true){
                    $value['status'] .= "<a href='#' class='status-titulo status-vermelho' data-toggle='popover' data-html='true' title='Enviado para cartório' data-content='Enviado para cartório: " . parserData($item->enviado_para_cartorio_data) . "'><span data-toggle='tooltip' data-html='true' title='Enviado para cartório'>C</span></a>";
                }
                
                $dias_atrasos = 0;
                if($agoraCarbon->gt(Carbon::parse($item->vencimento))){
                    $dias_atrasos = $agoraCarbon->diffInDays(Carbon::parse($item->vencimento));
                }

                if($dias_atrasos > 30 && $dias_atrasos <= 365){
                    if(empty($item->cenprot)){
                        $value['cenprot'] = "<a href='#' class='status-titulo status-verde' data-html='true' title='Enviar Título Para Protesto - CENPROT - Título: ".$item->numero."'><span data-toggle='tooltip' data-html='true' title='Enviar Título Para Protesto - CENPROT - Título: ".$item->numero."' data-titulo='".$item->numero."'  data-cenprot_id='' onclick='enviarCenprot($(this))'>E</span></a>";
                    }else{
                        if($item->cenprot->cenprot_status === "REMOVIDO"){
                            $value['cenprot'] = "<a href='#' class='status-titulo status-verde' data-html='true' title='Enviar Título Para Protesto - CENPROT - Título: ".$item->numero."'><span data-toggle='tooltip' data-html='true' title='Enviar Título Para Protesto - CENPROT - Título: ".$item->numero."' data-titulo='".$item->numero."'  data-cenprot_id='".encrypt($item->cenprot->id)."' onclick='enviarCenprot($(this))'>E</span></a>";
                        }else{
                            $value['cenprot'] = "<a href='#' class='status-titulo status-vermelho' data-html='true' title='Remover Título do Protesto - CENPROT - Título: ".$item->numero."'><span data-toggle='tooltip' data-html='true' title='Remover Título do Protesto - CENPROT - Título: ".$item->numero."' data-titulo='".$item->numero."' data-cenprot_id='".encrypt($item->cenprot->id)."' onclick='removerCenprot($(this))'>R</span></a>";
                        }
                        $value['cenprot'] .= "  <a href='#' class='status-titulo status-roxo' data-html='true' title='Histórico do Título - CENPROT - Título: ".$item->numero."'><span data-toggle='tooltip' data-html='true' title='Histórico do Título - CENPROT - Título: ".$item->numero."' data-titulo='".$item->numero."' data-cenprot_id='".encrypt($item->cenprot->id)."' data-cliente='".$item->nome_cliente ." - ". $item->cod_cliente."' onclick='modalCenprotHistorico($(this))'>H</span></a>";
                    }
                }else{
                    $value['cenprot'] = '';
                }

                $key = $item->numero . '' . $item->codigo;
                if(!isset($titulos_faturados["total"][$key])){            
                    $titulos_faturados["total"][$key] = $value;
                    if($vencimentoCarbon->gte($agoraCarbon)) {
                        $titulos_faturados["a_vencer"][] = $value;

                        $totalizadores["valor"]["a_vencer"] += $item->valor;
                        $totalizadores["saldo"]["a_vencer"] += $item->saldotitulo;
                        $totalizadores["juros"]["a_vencer"] += $item->juros;
                        $totalizadores['pagamento_parcial']['a_vencer'] += $value['pagamento_parcial'];
                        $totalizadores['valor_atual']['a_vencer'] += $value['valor_atual'];
                    }
                
                    else{
                        $titulos_faturados["vencidos"][] = $value;

                        $totalizadores["valor"]["vencidos"] += $item->valor;
                        $totalizadores["saldo"]["vencidos"] += $item->saldotitulo;
                        $totalizadores["juros"]["vencidos"] += $item->juros;
                        $totalizadores['pagamento_parcial']['vencidos'] += $value['pagamento_parcial'];
                        $totalizadores['valor_atual']['vencidos'] += $value['valor_atual'];
                    }

                    $totalizadores['pagamento_parcial']['total'] += $value['pagamento_parcial'];
                    $totalizadores['valor_atual']['total'] += $value['valor_atual'];
                    $totalizadores["valor"]["total"] += $item->valor;
                    $totalizadores["saldo"]["total"] += $item->saldotitulo;
                    $totalizadores["juros"]["total"] += $item->juros;
                }
            
        });

        if($totalizadores['valor'][$coluna] > 0){
            $totalizadores['valor'] = parserValor($totalizadores['valor'][$coluna]);
        }
        else{
            $totalizadores['valor'] = '';
        }
        
        if($totalizadores['saldo'][$coluna] > 0){
            $totalizadores['saldo'] = parserValor($totalizadores['saldo'][$coluna]);
        }
        else{
            $totalizadores['saldo'] = '';
        }

        if($totalizadores['juros'][$coluna] > 0){
            $totalizadores['juros'] = parserValor($totalizadores['juros'][$coluna]);
        }
        else{
            $totalizadores['juros'] = '';
        }

        if($totalizadores['pagamento_parcial'][$coluna] > 0){
            $totalizadores['pagamento_parcial'] = parserValor($totalizadores['pagamento_parcial'][$coluna]);
        }
        else{
            $totalizadores['pagamento_parcial'] = '';
        }

        if($totalizadores['valor_atual'][$coluna] > 0){
            $totalizadores['valor_atual'] = parserValor($totalizadores['valor_atual'][$coluna]);
        }
        else{
            $totalizadores['valor_atual'] = '';
        }

        return view('programs.posicao_sintetica_cliente.titulos_faturados')->with(["dados"=>$titulos_faturados[$coluna],"cod_cliente" => $cnpjCliente, "totalizadores" => $totalizadores, "codigo" => $fields['codigo'], "unico" => $fields['unico'], "coluna" => $coluna]);
    }

    public function returnTitulosTerceiros(Request $request, $coluna){

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

        $empresa = returnEmpresasNasajonView();
        $titulos_faturados = ["a_vencer"=>[], "vencidos"=>[], "total"=>[]];

        $titulosNasajon = TitulosEmAbertoNasajon::with('cliente')->selectRaw("*, regexp_replace(numero, '.[0-9]{5}.[0-9]{2}.', '') as nota")
            ->whereIn('cod_cliente', $clientesNasajon->pluck('codigo'))
            ->where('titulo_de_terceiro', true)
            ->orderBy('nota_numero')
            ->orderBy('parcela')
            ->get();

        $cnpjCliente = $clientesNasajon->pluck('codigo');
        $terceiros = ClienteNasajon::whereIn('cpf_cnpj', $titulosNasajon->pluck('documento_terceiro'))->get();

        $cliente     = false;
        $titulosNasajon->each(function ($item) use (&$titulos_faturados, &$empresa, $terceiros){
            $agoraCarbon = Carbon::Now()->setTime(0,0,0);
            $vencimentoCarbon = Carbon::createFromFormat('Y-m-d', $item->vencimento)->setTime(0,0,0);
       
            $nota_numero = '';
            
            if(empty($item->nota_numero)){
                $nota_numero = $item->nota;
            }else{
                $nota_numero = $item->nota_numero;
            }

            $terceiro = $terceiros->search(function($linha) use ($item){
                return str_replace('/', '', str_replace('-', '', str_replace('.', '', $linha->cpf_cnpj))) == $item->documento_terceiro;
            });
            $nome_terceiro = '';
            if(!empty($terceiros) && !empty($terceiro) && isset($terceiros[$terceiro])){
                $nome_terceiro = $terceiros[$terceiro]->nome . ' - ' . $terceiros[$terceiro]->cpf_cnpj;
            }

            $value['ESTABEL']              = $empresa[(int) $item->codigo];
            $value['NUMDOC']               = $nota_numero;
            $value['NPARC']                = $item->parcela;
            $value['DTEMIS']               = $item->titulo_emissao;
            $value['DTVCTO']               = $item->vencimento;
            $value['VALOR']                = $item->saldotitulo;
            $value['NOMECLIENTE']          = $item->nome_cliente .' - '. $item->cliente->cpf_cnpj;
            $value['nome_terceiro']        = $nome_terceiro;
            $value['NUMDUPBCO']            = $item->numero;
            $value['POSICAO_CR']           = '';
            $value['POSICAO_CR_DESCRICAO'] = '';

            if($item->banco_codigo != '0'){
                $value['banco'] = $item->banco_codigo;
            }
            else{
                $value['banco'] = '';
            }

            $key = $item->numero . '' . $item->codigo;
            if(!isset($titulos_faturados["total"][$key])){
                $titulos_faturados["total"][$key] = $value;
                if($vencimentoCarbon->gte($agoraCarbon)) {
                    $titulos_faturados["a_vencer"][] = $value;
                }
                else{
                    $titulos_faturados["vencidos"][] = $value;
                }
            }
        });

        return view('programs.posicao_sintetica_cliente.titulos_terceiros')->with(["dados"=>$titulos_faturados[$coluna],"cod_cliente" => $cnpjCliente]);
    }

    private function parserSituacaoCR($codigo){
        switch ($codigo) {
            case '?':
                return "Indefinida";
            break;
            case 'C':
                return "Cobrança em carteira Conf. Portador";
            break;
            case 'B':
                return "Cobrança Bancaria - Emissão Boleto";
            break;
            case 'M':
                return "Cobrança Bancaria - Manual";
            break;
            case 'E':
                return "Cobrança Bancaria - Escritural";
            break;
            case 'O':
                return "Cobrança Bancaria - On-line";
            break;
            case 'I':
                return "Cobrança Bancaria - Boleto / NF (Imediata)";
            break;
        }
    }

    private function parserPosicaoCr($codigo){
        switch ($codigo) {
            case '0':
                return "Não Executado";
            break;
            case '1':
                return "Já Executado";
            break;
            case '2':
                return "Já Efetivado";
            break;
            case '3':
                return "Nada Contra o Cliente";
            break;
            case '4':
                return "Nada Contra o Banco";
            break;
            case '9':
                return "Enviado ao Banco";
            break;
        }

    }

    private function parserTipreg($codigo){
        switch ($codigo) {
            case 'R':
                return "Normal com NF";
            break;
            case 'P':
                return "Normal com N.Pedido";
            break;
            case 'S':
                return "Normal com NF Serviço";
            break;
            case 'D':
                return "Nota de Débito";
            break;
            case 'C':
                return "Nota de Crédito";
            break;
            case 'X':
                return "Particular";
            break;
            case 'N':
                return "Nota Promissoria";
            break;
        }
    }

    public function returnNotasCredito(Request $request, $coluna){

        $fields = $request->only('codigo', 'unico');

        $codcad = $fields['codigo'];

        $cliente = ClienteNasajon::select()->where("codigo", $codcad);
        $cliente = $cliente->first();
        $cliente = $cliente->toArray();
        
        foreach ($cliente as $key => $value) {
            $cliente[$key] = $value;
        }

        $cpf_cnpj = $cliente["cpf_cnpj"];
        $codcads = [$cliente["codigo"]];

        if(strlen(trim($cpf_cnpj)) == 18){
            $cpf_cnpj = substr($cpf_cnpj, 0, 10);
        }

        $clientesQuery = ClienteNasajon::select();

        if(!isset($fields['unico']) || is_null($fields['unico']) || $fields['unico'] == 'false'){

            $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
            ->where('raiz_cnpj', 'like', $cpf_cnpj . '%')
            ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                $query->where('raiz_cnpj', 'ilike', $cpf_cnpj . '%');
            })->first();

            if(!is_null($grupoEmpresarialObj)){
                $clientesQuery->where(function($query) use ($grupoEmpresarialObj){
                    if (isset($grupoEmpresarialObj->participantes)){
                        foreach ($grupoEmpresarialObj->participantes as $participante){
                            $query->orWhere('cpf_cnpj', 'ilike', $participante->raiz_cnpj . '%');
                        }
                    }

                    $query->orWhere('cpf_cnpj', 'ilike', $grupoEmpresarialObj->raiz_cnpj . '%');

                });
            }
            else{
                $clientesQuery->where('cpf_cnpj', 'ilike', $cpf_cnpj . '%');
            }
        }
        else{
            $clientesQuery->where('cpf_cnpj', $cliente['cpf_cnpj']);
        }

        $clientes = $clientesQuery->get();

        $credito = [];

        $query_credito = NotasCreditoReceberNasajon::select()
            ->whereIn('cod_cliente', $clientes->pluck('codigo'));
        $query_credito = $query_credito->get()->toArray();
        $cnpjCliente   = $clientes->pluck('codigo');
        $empresa       = returnEmpresasNasajonView();
        $notas_credito = ["a_vencer"=>[], "vencidos"=>[], "total"=>[]];

        
        foreach ($query_credito as $key => $value) {            

            $value["SITUACAO_CR_DESCRICAO"] = $this->parserSituacaoCR("");//$this->parserSituacaoCR($value["SITUACAO_CR"]);
            $value["POSICAO_CR_DESCRICAO"]  = $this->parserPosicaoCr("");//$this->parserPosicaoCr($value["POSICAO_CR"]);
            $value["TIPREG_DESCRICAO"]      = $this->parserTipreg("");//$this->parserTipreg($value["TIPREG"]);
            $value["POSICAO_CR"]            = "";
            $value['NOMECLIENTE']           = $value['nome_cliente'] .' - '. $value['cod_cliente'];

            $tituloObs = " ";

            $queryObs = FinancasTitulosNasajon::select('*')->where('numero', $value['numero'])->get();
                foreach($queryObs as $obsResult){
                    $tituloObs = $obsResult->observacao;
                } 
            $value['Observacao']           = $tituloObs;

            $value["codigo"]                = $empresa[intval($value["codigo"])];
            if(strtotime($value["vencimento"]) >= strtotime(date("Y-m-d 00:00:00")) ){
                $value["vencimento"] = $value["vencimento"];
                $notas_credito["a_vencer"][] = $value;
            }elseif(strtotime($value["vencimento"]) < strtotime(date("Y-m-d 00:00:00")) ){
                $value["vencimento"] = $value["vencimento"];
                $notas_credito["vencidos"][] = $value;
            }
            $notas_credito["total"][] = $value;
            unset($query_credito[$key]);
        }

        
        $notas_credito[$coluna] = array_merge($notas_credito[$coluna], $credito);

        return view('programs.posicao_sintetica_cliente.notas_credito')->with(["dados"=>$notas_credito[$coluna],'grupoCliente'=>count($cnpjCliente)]);
    }

    public function returnNotasDebito(Request $request, $coluna){
        
        $fields = $request->only('codigo', 'unico');

        $codcad = $fields['codigo'];

        $cliente = ClienteNasajon::select()->where("codigo", $codcad);
        $cliente = $cliente->first();

        $cpf_cnpj = $cliente->cpf_cnpj;

        if(strlen(trim($cpf_cnpj)) == 18){
            $cpf_cnpj = substr($cpf_cnpj, 0, 10);
        }

        if(!isset($fields['unico']) || is_null($fields['unico']) || $fields['unico'] === false){

            $clientesQuery = ClienteNasajon::select();

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
            $cliente = collect([$cliente]);
        }

        $query_debito = NotasDebitoReceberNasajon::select()
            ->whereIn('cod_cliente', $cliente->pluck('codigo'));
        $query_debito = $query_debito->get()->toArray();
        $cnpjCliente = $cliente->pluck('codigo');
        $empresa = returnEmpresasNasajonView();
        $notas_debito = ["a_vencer"=>[], "vencidos"=>[], "total"=>[]];
        foreach ($query_debito as $key => $value) {

            $value["SITUACAO_CR_DESCRICAO"] = $this->parserSituacaoCR("");//$this->parserSituacaoCR($value["SITUACAO_CR"]);
            $value["POSICAO_CR_DESCRICAO"] = $this->parserPosicaoCr("");//$this->parserPosicaoCr($value["POSICAO_CR"]);
            $value['NOMECLIENTE']          = $value['nome_cliente'] .' - '. $value['cod_cliente'];
            $value["TIPREG_DESCRICAO"] = $this->parserTipreg("");//$this->parserTipreg($value["TIPREG"]);
            $value["codigo"] = $empresa[intval($value["codigo"])];

            if(strtotime($value["vencimento"]) >= strtotime(date("Y-m-d 00:00:00")) ){
                $notas_debito["a_vencer"][] = $value;
            }elseif(strtotime($value["vencimento"]) < strtotime(date("Y-m-d 00:00:00")) ){
                $notas_debito["vencidos"][] = $value;
            }
            $notas_debito["total"][] = $value;
            unset($contas_a_receber[$key]);
        }

       

        return view('programs.posicao_sintetica_cliente.notas_debito')->with(["dados"=>$notas_debito[$coluna],'grupoCliente' => count($cnpjCliente)]);
    }

    public function returnChequesRaceber(Request $request, $coluna){

        $fields = $request->only('codigo', 'unico');

        $codigo = $fields['codigo'];
        $cliente = ClienteNasajon::select()->where('codigo', $codigo);
        $cliente = $cliente->first();
        $cliente = $cliente->toArray();

        $cpf_cnpj = $cliente['cpf_cnpj'];

        if(strlen(trim($cpf_cnpj)) == 18){
            $cpf_cnpj = substr($cpf_cnpj, 0, 10);
        }
  
        $clientesNasajonQuery = ClienteNasajon::query();

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

        $cheques_a_receber = ["a_vencer"=>[], "vencidos"=>[], "total"=>[]];

        $cheques_em_aberto = ChequeNasajon::
            with('chequeTitulo')
            ->whereIn('cliente_codigo', $clientesNasajon->pluck('codigo'))
            ->whereNotIn('status', ["Compensado", 'Depositado', 'Devolvido (Tratado)'])
            ->get();

        $cheques_a_receber_nasajon = ["a_vencer"=>[], "vencidos"=>[], "total"=>[]];
        $total = ["a_vencer"=>0, "vencidos"=>0, "total"=>0];
        $cnpjCliente = $clientesNasajon->pluck('codigo');
        $cheques_em_aberto->each(function ($cheque) use (&$titulo, &$total, &$cheques_a_receber_nasajon, $empresa) {
            $titulo = [
                'estabelecimento' => $empresa[intval($cheque->estabelecimento)],
                'clientenome'   => $cheque->cliente_nome.' - '. $cheque->cliente_cnpj,
                'banco' => $cheque->banco,
                'agencia' => $cheque->agencia,
                'numero_conta' => $cheque->numero_conta,
                'numero_cheque' => $cheque->numero_cheque,
                'valor' => parserValor($cheque->valor),
                'observacao' => $cheque->observacao,
                'situacao' => (strtotime($cheque['data_vencimento']) >= strtotime(date('Y-m-d'))? 'A Vencer':'Vencido'),
                'data_entrada' => $cheque->data_entrada,
                'data_vencimento' => $cheque->data_vencimento,
                'vinculados' => $cheque->chequeTitulo->map(function($titulo) use ($cheque){
                    if(!empty($titulo->titulo_numero)){
                        return '<a href="#" onclick="detalheTituloNasajon(\''. $titulo->titulo_numero .'\')">'. $titulo->titulo_numero . '</a> - ' . parserValor($cheque->valor);
                    }
                    else{
                        return null;
                    }
                })->filter()->implode(', ')
            ];

            if(strtotime($cheque['data_vencimento']) >= strtotime(date('Y-m-d'))){
                $cheques_a_receber_nasajon["a_vencer"][] = $titulo;
                $total["a_vencer"] += $cheque->valor;

                $cheques_a_receber_nasajon["total"][] = $titulo;
                $total["total"] += $cheque->valor;
            }
            else{
                $cheques_a_receber_nasajon["vencidos"][] = $titulo;
                $total["vencidos"] += $cheque->valor;

                $cheques_a_receber_nasajon["total"][] = $titulo;
                $total["total"] += $cheque->valor;
            }

        });
        
        return view('programs.posicao_sintetica_cliente.cheques_receber')->with([ "dados" => $cheques_a_receber[$coluna], 'total' => parserValor($total[$coluna]), "dadosNasajon" => $cheques_a_receber_nasajon[$coluna],'grupoCliente' => count($cnpjCliente) ]);
    }

    public function returnCheques(Request $request){
        $fields = $request->only('hash', 'negociados');

        try{
            $cnpjs = Crypt::decrypt($fields['hash']);
        }
        catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Cliente inválido',
                'error' => [],
                'response' => []
            ], 422);
        }

        $retorno = [];
        $totais = ['valor' => 0];

        if($fields['negociados'] == 'true'){
            $ChequesDevolvidosObj = Cheque::with([
                    'cliente_detalhes', 
                    'pedidos_prepagos' => function($query) {
                        $query->withTrashed();
                    },
                    'pedidos_prepagos.pedido'
                ])
                ->whereIn('cliente_cpf_cnpj', $cnpjs)
                ->where('status', 'devolvido')
                ->where('tipo', 'cheque')->get();

            
            $ChequesDevolvidosObj->each(function ($cheque) use (&$retorno){
                $linha = [];

                $linha['cliente'] = $cheque->cliente_detalhes->nome . ' - ' . $cheque->cliente_detalhes->cpf_cnpj;
                $linha['banco'] = $cheque->banco;
                $linha['agencia'] = $cheque->agencia;
                $linha['conta'] = $cheque->conta;
                $linha['numero_cheque'] = $cheque->numero_cheque;
                $linha['valor'] = parserValor($cheque->valor);
                $linha['bom_para'] = $cheque->bom_para;
                $linha['created_at'] = $cheque->created_at->format('Y-m-d');
                $linha['devolvido'] = $cheque->updated_at->format('Y-m-d');
                $linha['vinculados'] = $cheque->pedidos_prepagos->map(function($pedido){
                        return '<a href=# onclick="showInfoTituloPrePago(\'' . $pedido->pedido_prepago_id . '\')">'. $pedido->pedido->pedido_nasajon_numero . '</a>' . ' - ' . parserValor($pedido->valor_pago);
                    })->implode(', ');
                $linha['origem'] = 'Cheque pré-pago';

                $retorno[] = $linha;
            });
    
            $totais['valor'] += $ChequesDevolvidosObj->sum('valor');

        }

        $queryChequesNasajon = ChequeNasajon::
            with('chequeTitulo')
            ->whereIn('cliente_cnpj', $cnpjs);

        if($fields['negociados'] == 'true'){
            $queryChequesNasajon->where('status', 'Devolvido (Tratado)');
        }
        else if($fields['negociados'] == 'false'){
            $queryChequesNasajon->where('status', 'Devolvido (Não tratado)');
        }
            
        $ChequeDevolvidosNasajonObj = $queryChequesNasajon->get();
        
        $ChequeDevolvidosNasajonObj->each(function ($cheque) use (&$retorno){
            $linha = [];

            $linha['cliente'] = $cheque->cliente_nome . ' - ' . $cheque->cliente_cnpj;
            $linha['banco'] = $cheque->banco;
            $linha['agencia'] = $cheque->agencia;
            $linha['conta'] = $cheque->numero_conta;
            $linha['numero_cheque'] = $cheque->numero_cheque;
            $linha['valor'] = parserValor($cheque->valor);
            $linha['bom_para'] = $cheque->data_vencimento;
            $linha['created_at'] = $cheque->data_entrada;
            $linha['devolvido'] = $cheque->data_pagamento;
            $linha['vinculados'] = $cheque->chequeTitulo->map(function($titulo) use ($cheque){
                    if(!empty($titulo->titulo_numero)){
                        return '<a href="#" onclick="detalheTituloNasajon(\''. $titulo->titulo_numero .'\')">'. $titulo->titulo_numero . '</a> - ' . parserValor($cheque->valor);
                    }
                    else{
                        return null;
                    }
                })->filter()->implode(', ');

            $linha['origem'] = 'Nasajon';

            $retorno[] = $linha;
        });

        $totais['valor'] += $ChequeDevolvidosNasajonObj->sum('valor');

        $totais['valor'] = parserValor($totais['valor']);

        return view('programs.posicao_sintetica_cliente.cheques_devolvidos')->with(['retorno' => $retorno, 'totais' => $totais, 'clientes' => count($cnpjs)]);

    }

    public function detalhesTituloModal(Request $request){
        $fields = $request->only('id');

        $tituloObj = ChequeTituloNasajon::
            with('nota', 'nota.pedido', 'nota.cliente', 'titulosAbertos', 'titulosPagos', 'cheque')
            ->where('titulo_numero', $fields['id'])
            ->whereNotNull('titulo_id')
            ->first();

        $retorno = [];

        $retorno['nome']  = $tituloObj->cheque->cliente_nome . ' - ' . $tituloObj->cheque->cliente_cnpj;
        $retorno['cliente']  = $tituloObj->cheque->cliente_nome;
        $retorno['id']  = $tituloObj->titulo_id;
        $retorno['valor_titulo']  = parserValor($tituloObj->valor_titulo);

        $retorno['valor_baixado'] = '';

        if(!empty($tituloObj->titulosAbertos)){
            if($tituloObj->valor_titulo - $tituloObj->titulosAbertos->saldotitulo > 0){
                $retorno['valor_baixado'] = parserValor($tituloObj->valor_titulo - $tituloObj->titulosAbertos->saldotitulo);
            }
        }
        else if(!empty($tituloObj->titulosPagos)){
            $retorno['valor_baixado'] = parserValor($tituloObj->valor_titulo);
        }

        $retorno['saldo']  = !empty($tituloObj->titulosAbertos) ? parserValor($tituloObj->titulosAbertos->saldotitulo) : '';
        
        if(!empty($tituloObj->nota)){

            if(!empty($tituloObj->nota->pedido)){
                $retorno['id_pedido']  = $tituloObj->nota->pedido->id;
                $retorno['pedido_nasajon'] = $tituloObj->nota->pedido->numero??'';
                $retorno['data_pedido_nasajon'] = parserData($tituloObj->nota->pedido->emissao);
            }
            else{
                $retorno['id_pedido']  = '';
                $retorno['pedido_nasajon'] = '';
                $retorno['data_pedido_nasajon'] = '';
            }
            $retorno['id_nota']  = $tituloObj->nota->id;
            $retorno['nota_fiscal']  = $tituloObj->nota->numero??'';
            $retorno['data_nota_fiscal']  = isset($tituloObj->nota->emissao)? parserData($tituloObj->nota->emissao): '';
        }
        else{
            $retorno['id_pedido'] = '';
            $retorno['id_nota'] =  '';
            $retorno['pedido_nasajon'] =  '';
            $retorno['data_pedido_nasajon'] =  '';
            $retorno['nota_fiscal'] =  '';
            $retorno['data_nota_fiscal'] =  '';
        }

        return view('programs.posicao_sintetica_cliente.modal.detalhes_titulo')->with(['retorno' => $retorno]);
    }

    public function tituloChequesVinculados(Request $request){
        $fields = $request->only('id');

        $tituloObj = ChequeTituloNasajon::
            with('nota', 'nota.pedido', 'nota.cliente', 'cheque')
            ->where('titulo_id', $fields['id'])
            ->get();

        $empresa = returnEmpresasNasajonView();

        $cheques = [];
        $total = 0;
        
        $tituloObj->pluck('cheque')->each(function($cheque) use (&$cheques, &$total, $empresa) {
            $titulo = [
                'estabelecimento' => $empresa[intval($cheque->estabelecimento)],
                'clientenome'   => $cheque->cliente_nome.' - '. $cheque->cliente_cnpj,
                'banco' => $cheque->banco,
                'agencia' => $cheque->agencia,
                'numero_conta' => $cheque->numero_conta,
                'numero_cheque' => $cheque->numero_cheque,
                'valor' => parserValor($cheque->valor),
                'observacao' => $cheque->observacao,
                'situacao' => $cheque->status,
                'data_entrada' => $cheque->data_entrada,
                'data_vencimento' => $cheque->data_vencimento
            ];

            $cheques[] = $titulo;
            $total += $cheque->valor;
        });

        return view('programs.posicao_sintetica_cliente.modal.titulo_cheques_vinculados')->with(['cheques' => $cheques, 'total' => parserValor($total)]);

    }

    public function filterTitulosFaturados(Request $request){
        $fields = $request->only('codigo', 'unico', 'coluna');

        $coluna = $fields['coluna'];

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
        $titulos_faturados = ["a_vencer"=>[], "vencidos"=>[], "total"=>[]];

        $titulosNasajon = TitulosEmAbertoNasajon::with(['devolucoes' => function($query){
                $query->whereNotIn('devolucao_nota_status_id', [7, 8]);
            }])
            ->with(['cenprot'])
            ->selectRaw("*, regexp_replace(numero, '.[0-9]{5}.[0-9]{2}.', '') as nota")
            ->whereIn('cod_cliente', $clientesNasajon->pluck('codigo'))
            ->where('titulo_de_terceiro', false)
            ->orderBy('nota_numero')
            ->orderBy('parcela')
            ->get();

        $cnpjCliente = $clientesNasajon->pluck('codigo');
       
        $cliente     = false;

        $totalizadores = [
            'valor' => [
                'a_vencer' => 0, 
                'vencidos' => 0,
                'total' => 0
            ],

            'saldo' => [
                'a_vencer' => 0, 
                'vencidos' => 0,
                'total' => 0
            ],
            'juros' => [
                'a_vencer' => 0, 
                'vencidos' => 0,
                'total' => 0
            ],
        ];
        $titulosNasajon->each(function ($item) use (&$titulos_faturados, &$empresa, &$totalizadores, $titulosNasajon){
            $agoraCarbon = Carbon::Now()->setTime(0,0,0);
            $vencimentoCarbon = Carbon::createFromFormat('Y-m-d', $item->vencimento)->setTime(0,0,0);
       
            $nota_numero = '';
            
            if(empty($item->nota_numero)){
                $nota_numero = $item->nota;
            }else{
                $nota_numero = $item->nota_numero;
            }

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
            $value['nome_cliente']          = $item->nome_cliente .' - '. $item->cod_cliente;
            $value['numero']                = $item->numero;
            $value['percentual_juros_diarios']         = $item->percentualjurosdiario;
            $value['data_juros']            = $item->datainiciomultaejuros;
            $value['desconto']              = $item->desconto;
            $value['POSICAO_CR']            = $item->nossonumero;
            $value['POSICAO_CR_DESCRICAO']  = $item->nossonumero;
            $value['observacao']  = $item->observacao;
            $value['numero_titulo_renegociado'] = '';
            $value['vencimento_titulo_renegociado'] = '';

            if(!empty($item->numero_titulo_renegociado) || !empty($item->vencimento_titulo_renegociado)){
                $value['numero_titulo_renegociado'] = $item->numero_titulo_renegociado;
                $value['vencimento_titulo_renegociado'] = !empty($item->vencimento_titulo_renegociado)?parserData($item->vencimento_titulo_renegociado):'';
            }else{
                if (strpos($item->observacao, '### Titulo ') !== false){
                    preg_match('/\d+\.\d+(\.\d)*/', $item->observacao, $titulo_original);
                    if(isset($titulo_original[0])) {
                        $tituloPagoObj = TitulosPagosNasajon::where('numero', $titulo_original[0])->first();
                        
                        if(!empty($tituloPagoObj)){
                            $value['numero_titulo_renegociado'] = $tituloPagoObj->numero;
                            $value['vencimento_titulo_renegociado'] = !empty($tituloPagoObj->vencimento)?parserData($tituloPagoObj->vencimento):'';
                        }
                    }
                }
            }

            $value['banco'] = ($item->enviado_para_banco == true) ? $item->banco_codigo : 'CARTEIRA';

            // Status do título
            if($item->tem_prorrogacao === true){
                $value['status'] .= "<a href='#' class='status-titulo status-verde' data-toggle='popover' data-html='true' title='Prorrogado' data-content='Vencimento original " . parserData($item->vencimento_original) . "'><span data-toggle='tooltip' data-html='true' title='Prorrogado'>P</span></a>";
            }
            if($agoraCarbon->gt(Carbon::parse($item->vencimento)->addDays(30))){
                $value['status'] .= "<a href='#' class='status-titulo status-roxo' data-toggle='tooltip' data-html='true' title='Cobrança Jurídica'>CJ</a>";
            }
            else if($agoraCarbon->gt(Carbon::parse($item->vencimento)->addDays(10))){
                $value['status'] .= "<a href='#' class='status-titulo status-amarelo' data-toggle='tooltip' data-html='true' title='Cobrança Administrativa'>CA</a>";
            }
            if($item->devolucoes->isNotEmpty()){
                $value['status'] .= ' <a href="#" class="status-titulo status-azul devolucoes_link" data-toggle="popover" data-html="true" title="Processos de Devolução" data-content="<b>Processos de devolução:</b><br>'. $item->devolucoes->map(function($devolucao){
                    return '<a href=\'#\' class=\'devolucoes_alert\' data-id=\'' . Crypt::encrypt($devolucao->id) . '\'>' . $devolucao->id . '</a> - Iniciado ' . $devolucao->created_at->format('d/m/Y') . '<br>';
                }
                )->implode(', ').'"><span data-toggle=\'tooltip\' data-html=\'true\' title=\'Processo de devolução\'>D</span></a>';
            }
            if($item->enviado_para_cartorio === true){
                $value['status'] .= "<a href='#' class='status-titulo status-vermelho' data-toggle='popover' data-html='true' title='Enviado para cartório' data-content='Enviado para cartório: " . parserData($item->enviado_para_cartorio_data) . "'><span data-toggle='tooltip' data-html='true' title='Enviado para cartório'>C</span></a>";
            }
            
            $dias_atrasos = 0;
            if($agoraCarbon->gt(Carbon::parse($item->vencimento))){
                $dias_atrasos = $agoraCarbon->diffInDays(Carbon::parse($item->vencimento));
            }

            if($dias_atrasos > 30){
                if(empty($item->cenprot)){
                    $value['cenprot'] = "<a href='#' class='status-titulo status-verde' data-html='true' title='Enviar Título Para Protesto - CENPROT - Título: ".$item->numero."'><span data-toggle='tooltip' data-html='true' title='Enviar Título Para Protesto - CENPROT - Título: ".$item->numero."' data-titulo='".$item->numero."' data-cenprot_id='' onclick='enviarCenprot($(this))'>E</span></a>";
                }else{
                    if($item->cenprot->cenprot_status === "REMOVIDO"){
                        $value['cenprot'] = "<a href='#' class='status-titulo status-verde' data-html='true' title='Enviar Título Para Protesto - CENPROT - Título: ".$item->numero."'><span data-toggle='tooltip' data-html='true' title='Enviar Título Para Protesto - CENPROT - Título: ".$item->numero."' data-titulo='".$item->numero."' data-cenprot_id='".encrypt($item->cenprot->id)."' onclick='enviarCenprot($(this))'>E</span></a>";
                    }else{
                        $value['cenprot'] = "<a href='#' class='status-titulo status-vermelho' data-html='true' title='Remover Título do Protesto - CENPROT - Título: ".$item->numero."'><span data-toggle='tooltip' data-html='true' title='Remover Título do Protesto - CENPROT - Título: ".$item->numero."' data-titulo='".$item->numero."' data-cenprot_id='".encrypt($item->cenprot->id)."' onclick='removerCenprot($(this))'>R</span></a>";
                    }
                    $value['cenprot'] .= "  <a href='#' class='status-titulo status-roxo' data-html='true' title='Histórico do Título - CENPROT - Título: ".$item->numero."'><span data-toggle='tooltip' data-html='true' title='Histórico do Título - CENPROT - Título: ".$item->numero."' data-titulo='".$item->numero."' data-cenprot_id='".encrypt($item->cenprot->id)."' data-cliente='".$item->nome_cliente ." - ". $item->cod_cliente."' onclick='modalCenprotHistorico($(this))'>H</span></a>";
                }
            }else{
                $value['cenprot'] = '';
            }

            $key = $item->numero . '' . $item->codigo;
            if(!isset($titulos_faturados["total"][$key])){            
                $titulos_faturados["total"][$key] = $value;
                if($vencimentoCarbon->gte($agoraCarbon)) {
                    $titulos_faturados["a_vencer"][] = $value;

                    $totalizadores["valor"]["a_vencer"] += $item->valor;
                    $totalizadores["saldo"]["a_vencer"] += $item->saldotitulo;
                    $totalizadores["juros"]["a_vencer"] += $item->juros;
                }
                else{
                    $titulos_faturados["vencidos"][] = $value;

                    $totalizadores["valor"]["vencidos"] += $item->valor;
                    $totalizadores["saldo"]["vencidos"] += $item->saldotitulo;
                    $totalizadores["juros"]["vencidos"] += $item->juros;
                }


                $totalizadores["valor"]["total"] += $item->valor;
                $totalizadores["saldo"]["total"] += $item->saldotitulo;
                $totalizadores["juros"]["total"] += $item->juros;
            }

        });

        if($totalizadores['valor'][$coluna] > 0){
            $totalizadores['valor'] = parserValor($totalizadores['valor'][$coluna]);
        }
        else{
            $totalizadores['valor'] = '';
        }
        
        if($totalizadores['saldo'][$coluna] > 0){
            $totalizadores['saldo'] = parserValor($totalizadores['saldo'][$coluna]);
        }
        else{
            $totalizadores['saldo'] = '';
        }

        if($totalizadores['juros'][$coluna] > 0){
            $totalizadores['juros'] = parserValor($totalizadores['juros'][$coluna]);
        }
        else{
            $totalizadores['juros'] = '';
        }
        
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $titulos_faturados[$coluna]
        ];
        return response()->json($response);

    }

    public function statusBlackList($array_clientes, $return_codigo = false){
        $retorno = '';

        foreach ($array_clientes as $value) {
            $cnpjs[] = $value["cpf_cnpj"];
        }

        $query = ClienteBlackList::select();
        $query->whereIn('cpf_cnpj', $cnpjs);
        $result = $query->get();

        if(empty($result)){
			if($return_codigo === false){
				$retorno = " <a href='#' class='status-titulo status-verde' data-toggle='tooltip' data-placement='top' data-html='true' title='Nenhum Título Na Black List'><span data-toggle='tooltip' data-html='true' title='Nenhum Título Na Black List'></span></a>";
			}else{
				$retorno = 1;
			}
        }else{
            $status_id = 1;
            foreach($result as $blacklist){
                if($blacklist->status_cliente_black_lists_id > $status_id){
                    $status_id = $blacklist->status_cliente_black_lists_id;
                }
            }

			if($return_codigo === false){
				if($status_id === 3){
					$retorno = " <a href='#' class='status-titulo status-preto' data-cpf_cnpj ='".encrypt($cnpjs)."' data-toggle='tooltip' data-placement='top' data-html='true' title='Black List(Bloqueado)' onclick='modalHistoricoBlackList($(this))'><span data-toggle='tooltip' data-html='true' title='Black List(Bloqueado)'></span></a>";
				}else if($status_id === 2){
					$retorno = " <a href='#' class='status-titulo status-amarelo' data-cpf_cnpj ='".encrypt($cnpjs)."' data-toggle='tooltip' data-placement='top' data-html='true' title='Black List(Liberado)' onclick='modalHistoricoBlackList($(this))'><span data-toggle='tooltip' data-html='true' title='Black List(Liberado)'></span></a>";
				}else{
					$retorno = " <a href='#' class='status-titulo status-verde' data-toggle='tooltip' data-placement='top' data-html='true' title='Nenhum Título Na Black List'><span data-toggle='tooltip' data-html='true' title='Nenhum Título Na Black List'></span></a>";
				}
			}else{
				$retorno = $status_id;
			}
        }


        return $retorno;
    }

    public function dadosCliente(Request $request){
        $codigo_cliente = $request->only('codigo');
        $cliente = ClienteNasajon::select()->where('codigo', $codigo_cliente)->first();
      
        $dados_cliente = new ClienteController();
        $reques_dados_cliente = new Request();
        $reques_dados_cliente->merge([
            'codcad' => (!empty($cliente->codigo)) ? $cliente->codigo : null,
        ]);
        $retorno_dados_cliente = $dados_cliente->show($reques_dados_cliente);
        $retorno_dados_cliente = $retorno_dados_cliente->getData();
   
        $return = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $retorno_dados_cliente
        ];

        return response()->json($return);
    }

    public function cobrancaRagazzi(Request $request){
        set_time_limit(300);
        $campos = $request->only('codigo_cliente','abertura','titulo_cobranca_ragazzi');
        
        $titulos = TitulosEmAbertoNasajon::where('cod_cliente',$campos['codigo_cliente']);

        if(!empty($campos['titulo_cobranca_ragazzi'])){
            $titulos->where('numero','ilike','%'.$campos['titulo_cobranca_ragazzi'].'%');
        }

        $titulos->where(function($query){
            $query->where(DB::raw('(vencimento + 90)'),'<',DB::raw('now()'))
            ->where('banco_nome','!=','PROCESSOS JUDICIAIS RAGAZZI');
        });
    
        $empresa = returnEmpresasNasajonView();
        $query_titulos = $titulos->get();
        $retorno = [];

        $query_titulos->each(function($query) use (&$retorno,$empresa){
            $nota_numero = '';
            $atraso_90_dias = Carbon::parse($query->vencimento)->addMonths(3);
            $hoje = Carbon::now(); 
            $judicial = false;

            if($atraso_90_dias->lt($hoje)){
                $judicial = true;
            }
                
            if(empty($query->nota_numero)){
                $nota_numero = $query->nota;
            }else{
                $nota_numero = $query->nota_numero;
            }

            $link_judicial = '';

            if($judicial == true && $query->banco_nome != 'PROCESSOS JUDICIAIS RAGAZZI'){
                $link_judicial = "<input id='titulos_selecionado' class='titulos_selecionados_juridico' name='titulos_selecionado[]' type='checkbox' value=".encrypt($query->titulo_id)." autocomplete='off'>";
            }

            $retorno[] = [
                'link'                      => $link_judicial,
                'estabelecimento'           => $query->codigo,
                'estabelecimento_nome'      => $empresa[(int) $query->codigo],
                'nota_numero'               => $nota_numero,
                'parcela'                   => $query->parcela,
                'data_emissao'              => parserData($query->titulo_emissao),
                'data_vencimento'           => parserData($query->vencimento),
                'valor_original'            => parserValor($query->valor),
                'valor'                     => ($query->saldotitulo > 0) ? parserValor($query->saldotitulo) : '',
                'juros_cobrados'            => ($query->juros > 0) ? parserValor($query->juros) : '',
                'nome_cliente'              => $query->nome_cliente .' - '. $query->cliente->cpf_cnpj,
                'numero'                    => $query->numero,
                'banco'                     => $query->banco_nome,
                'percentual_juros_diarios'  => $query->percentualjurosdiario,
                'data_juros'                => parserData($query->datainiciomultaejuros),
                'desconto'                  => ($query->desconto > 0) ? parserValor($query->desconto) : '',
                'POSICAO_CR'                => $query->nossonumero,
                'POSICAO_CR_DESCRICAO'      => $query->nossonumero,
                'observacao'                => $query->observacao,
            ];
        });
        
        if(isset($campos['abertura']) && $campos['abertura'] == true){
            $return = [
                'status' => 'success',
                'message' => '',
                'error' => [],
                'response' => $retorno
            ];
            return response()->json($return);
        }
        return view('programs.posicao_sintetica_cliente.modal.cobranca_juridico')->with(["dados"=>$retorno]);
    }

    public function titulosPagos(PosicaoSinteticaClienteTitulosPagosRequest $request){
        $campos = $request->only('data_inicio_titulos_pagos','data_fim_titulos_pagos','codigo','nome','cpf_cnpj_unico');
        
        $codcad = $campos['codigo'];
        if(empty($fields['cpf_cnpj_unico'])){
            $cliente = ClienteNasajon::select()->where('codigo', $codcad);
        }
        else{
            $cliente = ClienteNasajon::select()->where('codigo', $campos['cpf_cnpj_unico']);
        }
        $cliente = $cliente->first();
        
        if(!is_null($cliente) && !in_array($codcad, $this->codigo_cliente_balcao)){            
            $cliente = $cliente->toArray();
        }

        foreach ($cliente as $key => $value) {
            $cliente[$key] = utf8_encode($value);
        }
        $cpf_cnpj = $cliente["cpf_cnpj"];
        $cnpjs = [$cpf_cnpj];
        $codigos = [$cliente["codigo"]];
        $cnpjs_nome = [ ["cnpj" => $cpf_cnpj, "nome" => $cliente["nome"], 'codcad' => $cliente['codigo']]];
        $id_clientes = [];

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
					$grupo[] = $participante->raiz_cnpj;
				}
				$query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
			});
        }
        else {
			$clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
        }

		unset($clientesQuery);
        $clientesNasajon = $clientesNasajonQuery->orderBy('cpf_cnpj')
        ->groupBy('nome', 'codigo', 'cpf_cnpj', 'id')
        ->get();
        
		unset($clientesNasajonQuery);

        if(!isset($campos['cpf_cnpj_unico']) || is_null($campos['cpf_cnpj_unico'])){

            $cnpjs = $clientesNasajon->pluck('cpf_cnpj')->toArray();
        }


        $clientesNajason = ClienteNasajon::whereIn('cpf_cnpj', $cnpjs)->get();

        foreach ($clientesNasajon as $key => $value) {
            $cnpjs_nome[] = ["cnpj"=>$value["cpf_cnpj"], "nome"=>$value["nome"], 'codcad' => $value['codigo']];
            $clientes[$value["codigo"]] = $value;
            unset($clientesNasajon[$key]);
        }
        $cnpjs_nome = array_map("unserialize", array_unique(array_map("serialize", $cnpjs_nome)));

        sort($cnpjs_nome);

        

        $busca_estabelecimentos = ['00', '01', '02', '03', '04', '05', '06', '07', '08'];

        $dataInicio = Carbon::createFromFormat("d/m/Y", $campos['data_inicio_titulos_pagos']);
        $dataFim = Carbon::createFromFormat("d/m/Y", $campos['data_fim_titulos_pagos']);
        
        if(count($cnpjs_nome) > 1){
            
            $pagamentosTitulosNasajon = TituloPagamentoNasajon::
                whereBetween('data_pagamento', [$dataInicio,$dataFim])
                ->whereIn('cod_cliente', $clientesNajason->pluck('codigo'))
                ->whereIn('codigo', $busca_estabelecimentos)
                ->where('sinal', 0)
                ->where(function($query){
                    $query->where(function($query){
                        $query->where('pagamento_com_credito', true)
                        ->where('formapagamento_descricao', 'Usar Crédito');
                    })
                    ->orWhere('formapagamento_descricao', '!=', 'Usar Crédito');
                })
                ->with(['tituloAberto', 'tituloBaixado','comissaoVendedorTitulo' => function ($query){
                    $query->where('vendedor_codigo', '998');
                    $query->with('contaReceberBaixado');
                }])
                ->orderBy('data_pagamento')
                ->distinct()
                ->select('valor','valordesconto','banco_nome','banco_codigo','conta_nome','conta_codigo','id_titulo','formapagamento_descricao',
                'formapagamento_codigo','pagamento_com_credito','documento_id','documento_numero','sinal','valor_titulo','data_lancamento_pagamento','data_pagamento',
                'vencimento','emissao','parcela','numero','nome_cliente','cod_cliente','cliente_id','codigo')
                ->get();

            $pagamentosTitulosNasajon->load('tituloAberto', 'tituloBaixado');
            
            $titulos_pagos_nasajon = $pagamentosTitulosNasajon->filter(function ($titulo){
                return empty($titulo->tituloBaixado) || $titulo->tituloBaixado->renegociado === false;
            });

            $cheques_pagos_nasajon = ChequesRecebidoNasajon::query()
                ->whereBetween('data_entrada', [$dataInicio,$dataFim])
                ->whereIn('cod_cliente', $clientesNajason->pluck('codigo'))
                ->whereIn('estabelecimento', $busca_estabelecimentos)
                ->get();

        }else{
            $pagamentosTitulosNasajon = TituloPagamentoNasajon::
            whereBetween('data_pagamento', [$dataInicio,$dataFim])
                ->whereIn('cod_cliente', $clientesNajason->pluck('codigo'))
                ->whereIn('codigo', $busca_estabelecimentos)
                ->where('sinal', 0)
                ->where(function($query){
                    $query->where(function($query){
                        $query->where('pagamento_com_credito', true)
                        ->where('formapagamento_descricao', 'Usar Crédito');
                    })
                    ->orWhere('pagamento_com_credito', false);
                })
                ->with(['tituloAberto', 'tituloBaixado','comissaoVendedorTitulo' => function ($query){
                    $query->where('vendedor_codigo', '998');
                    $query->with('contaReceberBaixado');
                }])
                ->distinct()
                ->select('valor','valordesconto','banco_nome','banco_codigo','conta_nome','conta_codigo','id_titulo','formapagamento_descricao',
                'formapagamento_codigo','pagamento_com_credito','documento_id','documento_numero','sinal','valor_titulo','data_lancamento_pagamento','data_pagamento',
                'vencimento','emissao','parcela','numero','nome_cliente','cod_cliente','cliente_id','codigo')
                ->get();

            $titulos_pagos_nasajon = $pagamentosTitulosNasajon->filter(function ($titulo){
                return empty($titulo->tituloBaixado) || $titulo->tituloBaixado->renegociado === false;
            });

            $cheques_pagos_nasajon = ChequesRecebidoNasajon::query()
            ->whereBetween('data_entrada', [$dataInicio,$dataFim])
            ->whereIn('cod_cliente', $clientesNajason->pluck('codigo'))
            ->whereIn('estabelecimento', $busca_estabelecimentos)
            ->get();
        }

        $titulos = 0;
        $valor_total = 0;
        $em_atraso = 0;
        $dias_total_atraso = 0;
        $media_atraso = 0;
        $estabelecimentos = returnEmpresasNasajonView();
        $titulos_pagos_array = [];
        $cnpjCliente    = [];
        
        $titulos_pagos_nasajon->each(function ($item) use (&$titulos_pagos_array, &$em_atraso, &$cliente, &$dias_total_atraso, &$titulos, &$valor_total, $estabelecimentos, &$cnpjCliente){

            $vencimentoCarbon = Carbon::parse($item->vencimento);
            $emissaoCarbon = Carbon::parse($item->emissao);
            if(!empty($item->data_pagamento)){
                $baixaCarbon = Carbon::parse($item->data_pagamento);
            }else{
                $baixaCarbon = Carbon::parse($item->emissao);
            }

            if (!isset($cliente["DTULTATR"]) || empty($cliente["DTULTATR"])){
                $ultimoAtrasoCarbon = Carbon::parse('1900-01-01');
            } 
            else{
                $ultimoAtrasoCarbon = Carbon::parse($cliente["DTULTATR"]);
            }

            if(!isset($cliente["DIASMAIORATR"]) || empty($cliente["DIASMAIORATR"]) ){
                $cliente["DIASMAIORATR"] = 0;
            }

            if (!in_array($item->cod_cliente, $cnpjCliente) && empty($cnpjCliente)){
                $cnpjCliente[] = $item->cod_cliente;
            }

            $atraso = 0;

            if($baixaCarbon->gt($vencimentoCarbon)){

                $em_atraso++;
                $dias_total_atraso += $atraso = $vencimentoCarbon->diffInDays($baixaCarbon);

                if($vencimentoCarbon->gt($ultimoAtrasoCarbon)){
                    $cliente["DTULTATR"] = $vencimentoCarbon->format('Y-m-d');
                    $cliente["DIASULTATR"] = $atraso;
                }

                if($cliente["DIASMAIORATR"] < $atraso){
                    $cliente["DTMAIORATR"] = $vencimentoCarbon->format('Y-m-d');
                    $cliente["DIASMAIORATR"] = $atraso;
                }

                
            }

            if($item->valorjuros > 0){
                $juros = parserValor($item->valorjuros);
            }
            else{
                $juros = '';
            }

            if(!empty($item->comissaoVendedorTitulo[0])){
                foreach($item->comissaoVendedorTitulo as $comissao_ragazzi){
                    if(empty($titulos_pagos_array[$comissao_ragazzi->tituloreceber]) && !empty($comissao_ragazzi->contaReceberBaixado)){
                        $titulos_pagos_array[$comissao_ragazzi->tituloreceber] = [
                            'estabelecimento' => $estabelecimentos[intval($item->codigo)],
                            'clientenome'   => $item->nome_cliente.' - '.$item->cod_cliente,
                            'titulo' => $comissao_ragazzi->contaReceberBaixado->numero,
                            'data_emissao' => $emissaoCarbon->format("d/m/Y"),
                            'data_vencimento' => $vencimentoCarbon->format("d/m/Y"),
                            'data_pagamento' => $baixaCarbon->format("d/m/Y"),
                            'atraso' => empty($atraso)? '' : $atraso,
                            'valor' => parserValor($comissao_ragazzi->contaReceberBaixado->valor),
                            'valor_titulo' => parserValor($comissao_ragazzi->contaReceberBaixado->valor_titulo),
                            'juros' => (($item->valor+$item->valordesconto)-$item->valor_titulo) < 0.01? '' : parserValor(($item->valor+$item->valordesconto)-$item->valor_titulo),
                            'desconto' => empty($item->valordesconto)? '' : parserValor($item->valordesconto),
                            'portador' => ''
                        ];
                        
                        $valor_total += $comissao_ragazzi->contaReceberBaixado->valor_titulo;
                        $titulos++;
                    }
                }

            }else{

                if(empty($titulos_pagos_array[$item->numero])){
                    $titulos_pagos_array[$item->numero] = [
                        'estabelecimento' => $estabelecimentos[intval($item->codigo)],
                        'clientenome'   => $item->nome_cliente.' - '.$item->cod_cliente,
                        'titulo' => $item->numero,
                        'data_emissao' => $emissaoCarbon->format("d/m/Y"),
                        'data_vencimento' => $vencimentoCarbon->format("d/m/Y"),
                        'data_pagamento' => $baixaCarbon->format("d/m/Y"),
                        'atraso' => empty($atraso)? '' : $atraso,
                        'valor' => parserValor($item->valor),
                        'valor_titulo' => parserValor($item->valor_titulo),
                        'juros' => (($item->valor+$item->valordesconto)-$item->valor_titulo) < 0.01? '' : parserValor(($item->valor+$item->valordesconto)-$item->valor_titulo),
                        'desconto' => empty($item->valordesconto)? '' : parserValor($item->valordesconto),
                        'portador' => ''
                    ];
                }else{
                    $titulos_pagos_array[$item->numero]['data_pagamento'] = $baixaCarbon->format("d/m/Y");
                    $titulos_pagos_array[$item->numero]['atraso'] = empty($atraso)? '' : $atraso;
                    $titulos_pagos_array[$item->numero]['valor'] = parserValor(parserNumber($titulos_pagos_array[$item->numero]['valor']) + $item->valor);
                    $titulos_pagos_array[$item->numero]['desconto'] = empty(parserNumber($titulos_pagos_array[$item->numero]['desconto']) + $item->valordesconto)? '' : parserValor(parserNumber($titulos_pagos_array[$item->numero]['desconto']) + $item->valordesconto);
                }

                $valor_total += $item->valor;
                $titulos++;
            }

        });

        $cheques_pagos_nasajon->each(function ($item) use (&$titulos_pagos_array, &$valor_total, &$titulos, $estabelecimentos, &$cnpjCliente){
            if (!in_array($item->cod_cliente, $cnpjCliente)){
                $cnpjCliente[] = $item->cod_cliente;
            }

            if (isset($item->data_entrada) && !is_null($item->data_entrada)){
                $emissao = Carbon::createFromFormat('Y-m-d', $item->data_entrada)->format("d/m/Y");
            }
            else{
                $emissao = '';
            }

            if (isset($item->data_pagamento) && !is_null($item->data_pagamento)){
                $baixa = Carbon::createFromFormat('Y-m-d', $item->data_pagamento)->format("d/m/Y"); 
            }
            else{
                $baixa = '';
            }

            $titulos_pagos_array[$item->numero] = [
                'estabelecimento' => $estabelecimentos[intval($item->estabelecimento)],
                'clientenome'   => $item->nome_cliente.' - '.$item->cod_cliente,
                'titulo' => 'CH ' . $item->banco . ' '. $item->agencia . ' ' . $item->numero_conta . ' ' . $item->numero_cheque,
                'data_emissao' => $emissao,
                'data_vencimento' => '',
                'data_pagamento' => $baixa,
                'atraso' => '',
                'valor' => parserValor($item->valor),
                'portador' => $item->banco .' - Agencia: '. $item->agencia . ' Conta: ' . $item->numero_conta,
                'valor_titulo' => '',
                'juros' => '',
                'desconto' => ''
            ];

            $valor_total += $item->valor;
            $titulos++;

        });

        if ($em_atraso > 0){
            $media_atraso = $dias_total_atraso / $em_atraso;
        }else{
            $media_atraso = '';
        }
        
        return [
            'status' => 'success',
            'message' => 'Título(s) alterado(s) com sucesso.',
            'error' => '',
            'response' => [
                'titulos_pagos' => $titulos_pagos_array,
                'grupoCliente' => $cnpjCliente,
                'titulos' => $titulos,
                'valor_total' => ($valor_total > 0) ? parserValor($valor_total) : 0,
                'em_atraso' => $em_atraso,
                "media_atraso" => round(intval($media_atraso)*100)/100,
            ]
        ];
    }

    public function alterarTituloJudicial(Request $request){
        set_time_limit(300);
        $campos = $request->only('titulos_selecionado');

        $titulos = $campos['titulos_selecionado'];
        
        foreach($titulos as $titulo){
            try{
                $titulo_id = decrypt($titulo);
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao tentar alterar o(s) título(s).',
                    'error' => [],
                    'response' => []
                ]);
            }

            $conta_ragazzi = ContasNasajon::where('codigo','JUDICIAL RAGAZZI')->first();

            if(empty($conta_ragazzi)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Conta Ragazzi não localizada.',
                    'error' => [],
                    'response' => []
                ]);
            }

            $consulta_titulos = TitulosEmAbertoNasajon::where('titulo_id',$titulo_id)->first();

            if(empty($consulta_titulos)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Título não localizado.',
                    'error' => [],
                    'response' => []
                ]);
            }

            $log_alterar_conta = new LogAlterarTitulosJudiciai;

            $uuid = $conta_ragazzi->conta;
            
            $api_alterar_banco = DB::connection("nasajon")->select("SELECT * FROM integracoes.api_tituloreceber_alterarconta('".$titulo_id."','8498ff13-9f7b-42e1-bff0-9589ae20af14','".$uuid."',null,true,null)");
            
            $mensagem_nasajon = $api_alterar_banco[0]->mensagem;
            $mensagem_nasajon = json_decode($mensagem_nasajon, true);
            
            $log_alterar_conta->titulo = $consulta_titulos->numero;
            $log_alterar_conta->uuid_titulo = $consulta_titulos->titulo_id;
            $log_alterar_conta->uuid_cliente = $consulta_titulos->id_cliente;
            $log_alterar_conta->conta_anterior = $consulta_titulos->banco_nome;
            $log_alterar_conta->created_by = Auth::id();
            $log_alterar_conta->save();

            if(!$mensagem_nasajon['codigo'] == 'OK'){
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => $mensagem_nasajon,
                    'response' => []
                ];
            }
        }

        return [
            'status' => 'success',
            'message' => 'Título(s) alterado(s) com sucesso.',
            'error' => '',
            'response' => []
        ];

    }

    public function titulosRenegociados(PosicaoSinteticaClienteTitulosRenegociadosRequest $request){
        $campos = $request->only('data_inicio_renegociacao','data_fim_renegociacao','codigo','nome','cpf_cnpj_unico');
        
        $codcad = $campos['codigo'];
        if(empty($fields['cpf_cnpj_unico'])){
            $cliente = ClienteNasajon::select()->where('codigo', $codcad);
        }else{
            $cliente = ClienteNasajon::select()->where('codigo', $campos['cpf_cnpj_unico']);
        }
        $cliente = $cliente->first();
        
        if(!is_null($cliente) && !in_array($codcad, $this->codigo_cliente_balcao)){            
            $cliente = $cliente->toArray();
        }

        foreach ($cliente as $key => $value) {
            $cliente[$key] = utf8_encode($value);
        }
        $cpf_cnpj = $cliente["cpf_cnpj"];
        $cnpjs = [$cpf_cnpj];
        $codigos = [$cliente["codigo"]];
        $cnpjs_nome = [ ["cnpj" => $cpf_cnpj, "nome" => $cliente["nome"], 'codcad' => $cliente['codigo']]];
        $id_clientes = [];

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
					$grupo[] = $participante->raiz_cnpj;
				}
				$query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
			});
        }else{
			$clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
        }

		unset($clientesQuery);
        $clientesNasajon = $clientesNasajonQuery->orderBy('cpf_cnpj')
        ->groupBy('nome', 'codigo', 'cpf_cnpj', 'id')
        ->get();
        
		unset($clientesNasajonQuery);

        if(!isset($campos['cpf_cnpj_unico']) || is_null($campos['cpf_cnpj_unico'])){

            $cnpjs = $clientesNasajon->pluck('cpf_cnpj')->toArray();
        }

        $clientesNajason = ClienteNasajon::whereIn('cpf_cnpj', $cnpjs)->get();

        $busca_estabelecimentos = ['00', '01', '02', '03', '04', '05', '06', '07', '08'];

        $dataInicio = Carbon::createFromFormat("d/m/Y", $campos['data_inicio_renegociacao']);
        $dataFim = Carbon::createFromFormat("d/m/Y", $campos['data_fim_renegociacao']);
        
        $pagamentosTitulosNasajon = TitulosPagosNasajon::whereBetween('vencimento', [$dataInicio,$dataFim])
        ->whereIn('cod_cliente', $clientesNajason->pluck('codigo')->toArray())
        ->whereIn('codigo', $busca_estabelecimentos)
        ->where('sinal', 0)
        ->where('renegociado', true)
        ->distinct()
        ->get();

        $titulos_pagos_nasajon_renegociados = $pagamentosTitulosNasajon;
        
        $titulos_renegociados = 0;
        $valor_total_renegociados = 0;
        $em_atraso_renegociados = 0;
        $dias_total_atraso_renegociados = 0;
        $media_atraso_renegociados = 0;
        $estabelecimentos = returnEmpresasNasajonView();

        $titulos_renegociados_array = [];
        $cnpjCliente    = [];

        $titulos_pagos_nasajon_renegociados->each(function ($item) use (&$titulos_renegociados_array, &$em_atraso_renegociados, &$cliente, &$dias_total_atraso_renegociados, &$titulos_renegociados, &$valor_total_renegociados, $estabelecimentos, &$cnpjCliente){

            $vencimentoCarbon = Carbon::parse($item->vencimento);
            $emissaoCarbon = Carbon::parse($item->emissao);
            if(!empty($item->data_pagamento)){
                $baixaCarbon = Carbon::parse($item->data_pagamento);
            }else{
                $baixaCarbon = Carbon::parse($item->vencimento);
            }

            if (!isset($cliente["DTULTATR"]) || empty($cliente["DTULTATR"])){
                $ultimoAtrasoCarbon = Carbon::parse('1900-01-01');
            } 
            else{
                $ultimoAtrasoCarbon = Carbon::parse($cliente["DTULTATR"]);
            }

            if(!isset($cliente["DIASMAIORATR"]) || empty($cliente["DIASMAIORATR"]) ){
                $cliente["DIASMAIORATR"] = 0;
            }

            if (!in_array($item->cod_cliente, $cnpjCliente)){
                $cnpjCliente[] = $item->cod_cliente;
            }

            $atraso = 0;

            if($baixaCarbon->gt($vencimentoCarbon)){

                $em_atraso_renegociados++;
                $dias_total_atraso_renegociados += $atraso = $vencimentoCarbon->diffInDays($baixaCarbon);

                if($vencimentoCarbon->gt($ultimoAtrasoCarbon)){
                    $cliente["DTULTATR"] = $vencimentoCarbon->format('Y-m-d');
                    $cliente["DIASULTATR"] = $atraso;
                }

                if($cliente["DIASMAIORATR"] < $atraso){
                    $cliente["DTMAIORATR"] = $vencimentoCarbon->format('Y-m-d');
                    $cliente["DIASMAIORATR"] = $atraso;
                }

                
            }

            if($item->valorjuros > 0){
                $juros = parserValor($item->valorjuros);
            }
            else{
                $juros = '';
            }

            if($item->valor_titulo > $item->valor){
                $desconto = parserValor($item->valor_titulo - $item->valor);
            }
            else{
                $desconto = '';
            }

            $titulos_renegociados_array[] = [
                'estabelecimento' => $estabelecimentos[intval($item->codigo)],
                'clientenome'   => $item->nome_cliente.' - '.$item->cod_cliente,
                'titulo' => $item->numero,
                'data_emissao' => $emissaoCarbon->format("d/m/Y"),
                'data_vencimento' => $vencimentoCarbon->format("d/m/Y"),
                'data_pagamento' => $baixaCarbon->format("d/m/Y"),
                'atraso' => $atraso,
                'valor' => parserValor($item->valor),
                'valor_titulo' => parserValor($item->valor_titulo),
                'juros' => $juros,
                'desconto' => $desconto,
                'portador' => ''
            ];

            $valor_total_renegociados += $item->valor;
            $titulos_renegociados++;
        });

        if ($em_atraso_renegociados > 0){
            $media_atraso_renegociados = $dias_total_atraso_renegociados / $em_atraso_renegociados;
        }
        else{
            $media_atraso_renegociados = '';
        }
        
        return [
            'status' => 'success',
            'message' => 'Título(s) alterado(s) com sucesso.',
            'error' => '',
            'response' => [
                'titulos_renegociados' => $titulos_renegociados,
                'valor_total_renegociados' => 'R$ ' . parserValor($valor_total_renegociados),
                "em_atraso_renegociados" => $em_atraso_renegociados,
                "dias_total_atraso_renegociados" => $dias_total_atraso_renegociados,
                "titulos_renegociados_array" => $titulos_renegociados_array,
                "media_atraso_renegociados" => round(intval($media_atraso_renegociados)*100)/100,
                'grupoCliente'  => count($cnpjCliente),
            ]
        ];
    }

    public function titulosFormaPagamento(PosicaoSinteticaClienteFormaPagamentoRequest $request){
        $campos = $request->only('data_inicio_forma_pagamento','data_fim_forma_pagamento','codigo','nome','cpf_cnpj_unico');
        
        $codcad = $campos['codigo'];
        if(empty($fields['cpf_cnpj_unico'])){
            $cliente = ClienteNasajon::select()->where('codigo', $codcad);
        }
        else{
            $cliente = ClienteNasajon::select()->where('codigo', $campos['cpf_cnpj_unico']);
        }
        $cliente = $cliente->first();
        
        if(!is_null($cliente) && !in_array($codcad, $this->codigo_cliente_balcao)){            
            $cliente = $cliente->toArray();
        }

        foreach ($cliente as $key => $value) {
            $cliente[$key] = utf8_encode($value);
        }
        $cpf_cnpj = $cliente["cpf_cnpj"];
        $cnpjs = [$cpf_cnpj];
        $codigos = [$cliente["codigo"]];
        $cnpjs_nome = [ ["cnpj" => $cpf_cnpj, "nome" => $cliente["nome"], 'codcad' => $cliente['codigo']]];
        $id_clientes = [];

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
					$grupo[] = $participante->raiz_cnpj;
				}
				$query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
			});
        }
        else {
			$clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
        }

		unset($clientesQuery);
        $clientesNasajon = $clientesNasajonQuery->orderBy('cpf_cnpj')
        ->groupBy('nome', 'codigo', 'cpf_cnpj', 'id')
        ->get();
        
		unset($clientesNasajonQuery);

        if(!isset($campos['cpf_cnpj_unico']) || is_null($campos['cpf_cnpj_unico'])){

            $cnpjs = $clientesNasajon->pluck('cpf_cnpj')->toArray();
        }


        $clientesNajason = ClienteNasajon::whereIn('cpf_cnpj', $cnpjs)->get();

        foreach ($clientesNasajon as $key => $value) {
            $cnpjs_nome[] = ["cnpj"=>$value["cpf_cnpj"], "nome"=>$value["nome"], 'codcad' => $value['codigo']];
            $clientes[$value["codigo"]] = $value;
            unset($clientesNasajon[$key]);
        }
        $cnpjs_nome = array_map("unserialize", array_unique(array_map("serialize", $cnpjs_nome)));

        sort($cnpjs_nome);

        

        $busca_estabelecimentos = ['00', '01', '02', '03', '04', '05', '06', '07', '08'];

        $dataInicio = Carbon::createFromFormat("d/m/Y", $campos['data_inicio_forma_pagamento']);
        $dataFim = Carbon::createFromFormat("d/m/Y", $campos['data_fim_forma_pagamento']);
        
        if(count($cnpjs_nome) > 1){
            
            $pagamentosTitulosNasajon = TituloPagamentoNasajon::
                whereBetween('vencimento', [$dataInicio,$dataFim])
                ->whereIn('cod_cliente', $clientesNajason->pluck('codigo'))
                ->whereIn('codigo', $busca_estabelecimentos)
                ->where('sinal', 0)
                ->where(function($query){
                    $query->where(function($query){
                        $query->where('pagamento_com_credito', true)
                        ->where('formapagamento_descricao', 'Usar Crédito');
                    })
                    ->orWhere('formapagamento_descricao', '!=', 'Usar Crédito');
                })
                ->with(['tituloAberto', 'tituloBaixado','comissaoVendedorTitulo' => function ($query){
                    $query->where('vendedor_codigo', '998');
                    $query->with('contaReceberBaixado');
                }])
                ->orderBy('data_pagamento')
                ->distinct()
                ->select('valor','valordesconto','banco_nome','banco_codigo','conta_nome','conta_codigo','id_titulo','formapagamento_descricao',
                'formapagamento_codigo','pagamento_com_credito','documento_id','documento_numero','sinal','valor_titulo','data_lancamento_pagamento','data_pagamento',
                'vencimento','emissao','parcela','numero','nome_cliente','cod_cliente','cliente_id','codigo')
                ->get();

            $pagamentosTitulosNasajon->load('tituloAberto', 'tituloBaixado');
            
        }else{
            $pagamentosTitulosNasajon = TituloPagamentoNasajon::
            whereBetween('vencimento', [$dataInicio,$dataFim])
                ->whereIn('cod_cliente', $clientesNajason->pluck('codigo'))
                ->whereIn('codigo', $busca_estabelecimentos)
                ->where('sinal', 0)
                ->where(function($query){
                    $query->where(function($query){
                        $query->where('pagamento_com_credito', true)
                        ->where('formapagamento_descricao', 'Usar Crédito');
                    })
                    ->orWhere('pagamento_com_credito', false);
                })
                ->with(['tituloAberto', 'tituloBaixado','comissaoVendedorTitulo' => function ($query){
                    $query->where('vendedor_codigo', '998');
                    $query->with('contaReceberBaixado');
                }])
                ->distinct()
                ->select('valor','valordesconto','banco_nome','banco_codigo','conta_nome','conta_codigo','id_titulo','formapagamento_descricao',
                'formapagamento_codigo','pagamento_com_credito','documento_id','documento_numero','sinal','valor_titulo','data_lancamento_pagamento','data_pagamento',
                'vencimento','emissao','parcela','numero','nome_cliente','cod_cliente','cliente_id','codigo')
                ->get();

        }

        $forma_pagamento = [];
        $total_forma_pagamento = [
            'titulo' => 0,
            'valor' => 0
        ];

        if(!empty($pagamentosTitulosNasajon)){
            $data_prologos = Carbon::parse('2019-07-07');
            foreach($pagamentosTitulosNasajon as $titulo){
                $emissao = Carbon::parse($titulo->emissao);
                if($emissao->lte($data_prologos) && empty($titulo->formapagamento_descricao)){
                    $forma = 'Boleto Bancário';
                }else if($emissao->gt($data_prologos) && empty($titulo->formapagamento_descricao)){
                    $forma = 'NOTA DE DÉBITO';
                }else{
                    $forma = $titulo->formapagamento_descricao;
                }
                if(!isset($forma_pagamento[$forma])){
                    $forma_pagamento[$forma] = [
                        'forma_pagamento' => $forma,
                        'titulo' => 0,
                        'valor' => 0,
                    ];
                }

                $forma_pagamento[$forma]['titulo'] += 1;
                $forma_pagamento[$forma]['valor'] += $titulo->valor;

                $total_forma_pagamento['titulo'] += 1;
                $total_forma_pagamento['valor'] += $titulo->valor;
            }

            foreach($forma_pagamento as $key => $valor){
                $forma_pagamento[$key]['valor'] = parserValor($forma_pagamento[$key]['valor']);
            }
    
            $total_forma_pagamento['valor'] = parserValor($total_forma_pagamento['valor']);
        }else{
            $total_forma_pagamento = [
                'titulo' => '',
                'valor' => ''
            ];
        }
        
        return [
            'status' => 'success',
            'message' => 'Título(s) alterado(s) com sucesso.',
            'error' => '',
            'response' => [
                "forma_pagamento" => $forma_pagamento,
                "total_forma_pagamento" => $total_forma_pagamento,
            ]
        ];
    }

    public function detalhesDevolucao(Request $request, $array = false){
        ini_set('memory_limit', '1024M');
        set_time_limit(600);
        $fields = $request->only('codigo', 'data_inicio', 'data_fim', 'cpf_cnpj_unico','data_inicio_forma_pagamento','data_fim_forma_pagamento','data_inicio_renegociacao','data_fim_renegociacao');
        $codcad = $fields['codigo'];
        if(in_array($codcad, $this->codigo_cliente_balcao) || empty($codcad)){
            if($array === true){
                return [
                    "pedidos" => [
                        "orcamentos"  => "",
                        "carteira"  => "",
                        "total"     => ""
                    ],
                    "notas_debito" => [
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "notas_credito" => "",
                    "titulos_faturados" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "titulos_terceiros" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "cheques_a_receber" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "atraso" => [
                        "ultima" => [
                            "data"         => "",
                            "quantidade"   => ""
                        ],
                        "maior" =>  [
                            "data"         => "",
                            "quantidade"   => ""
                        ]
                    ],
                    "vendas" => [
                        "ultima" => [
                            "data"     => "",
                            "valor"    => ""
                        ],
                        "maior" =>  [
                            "data"     => "",
                            "valor"    => ""
                        ]
                    ],
                    "total" => [
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "pago_ultimo_12_meses" => '',
                    "cliente_desde" => '',
                    "limite_credito" => '',
                    "mensagem_alerta" => '',
                    "messagem_agrupada" => '',
                    "messagem_agrupada_cnpjs" => '',
                    'cliente' => [
                        'codigo' => '',
                        'nome' => '',
                        'unico' => ''
                    ],
                    "cnpj_array" => '',
                    "titulos_pagos" => '',
                    "titulos" => '',
                    "valor_total" => '',
                    'em_atraso' => '',
                    "dias_total_atraso" => '',
                    "media_atraso" => '',
                    'vencimento_credito' => '',
                    "ultima_atualizacao" => '',
        
                    "titulo_modal" => '',

                    "consulta_serasa" => '',
                    "motivo_reavaliacao" => ''
                ];
            }
            else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados não encontrados',
                    'error' => [],
                    'response' => []
                ], 422);
            }
        }
        if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat("d/m/Y", $fields['data_inicio']);
            $data_fim = Carbon::createFromFormat("d/m/Y", $fields['data_fim']);        
        }else{
            $data_inicio = new Carbon;
            $data_fim = new Carbon;
        }

        if(!empty($fields['data_inicio_renegociacao']) && !empty($fields['data_fim_renegociacao'])){
            $data_inicio_renegociacao = Carbon::createFromFormat("d/m/Y", $fields['data_inicio_renegociacao']);
            $data_fim_renegociacao = Carbon::createFromFormat("d/m/Y", $fields['data_fim_renegociacao']);        
        }else{
            $data_inicio_renegociacao = new Carbon;
            $data_fim_renegociacao = new Carbon;
        }

        if(!empty($fields['data_inicio_forma_pagamento']) && !empty($fields['data_fim_forma_pagamento'])){
            $data_inicio_forma_pagamento = Carbon::createFromFormat("d/m/Y", $fields['data_inicio_forma_pagamento']);
            $data_fim_forma_pagamento = Carbon::createFromFormat("d/m/Y", $fields['data_fim_forma_pagamento']);        
        }else{
            $data_inicio_forma_pagamento = new Carbon;
            $data_fim_forma_pagamento = new Carbon;
        }
        if(empty($fields['cpf_cnpj_unico'])){
            $cliente = ClienteNasajon::select()->where('codigo', $codcad);
        }
        else{
            $cliente = ClienteNasajon::select()->where('codigo', $fields['cpf_cnpj_unico']);
        }
        $cliente = $cliente->first();
        
        if(!is_null($cliente) && !in_array($codcad, $this->codigo_cliente_balcao)){            
            $cliente = $cliente->toArray();
        }
        else{
            if($array === true){
                return [
                    "pedidos" => [
                        "orcamentos"  => "",
                        "carteira"  => "",
                        "total"     => ""
                    ],
                    "notas_debito" => [
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "notas_credito" => "",
                    "titulos_faturados" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "titulos_terceiros" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "cheques_a_receber" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "atraso" => [
                        "ultima" => [
                            "data"         => "",
                            "quantidade"   => ""
                        ],
                        "maior" =>  [
                            "data"         => "",
                            "quantidade"   => ""
                        ]
                    ],
                    "vendas" => [
                        "ultima" => [
                            "data"     => "",
                            "valor"    => ""
                        ],
                        "maior" =>  [
                            "data"     => "",
                            "valor"    => ""
                        ]
                    ],
                    "total" => [
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                    "pago_ultimo_12_meses" => '',
                    "cliente_desde" => '',
                    "limite_credito" => '',
                    "mensagem_alerta" => '',
                    "messagem_agrupada" => '',
                    "messagem_agrupada_cnpjs" => '',
                    'cliente' => [
                        'codigo' => '',
                        'nome' => '',
                        'unico' => ''
                    ],
                    "cnpj_array" => '',
                    "titulos_pagos" => '',
                    "titulos" => '',
                    "valor_total" => '',
                    'em_atraso' => '',
                    "dias_total_atraso" => '',
                    "media_atraso" => '',
                    'vencimento_credito' => '',
                    "ultima_atualizacao" => '',
        
                    "titulo_modal" => '',

                    "consulta_serasa" => '',
                    "motivo_reavaliacao" => '',

                    "cheques_pre" =>[
                        "a_vencer" 	=> "",
                        "vencidas" 	=> "",
                        "total"		=> ""
                    ],
                ];
            }
            else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados não encontrados',
                    'error' => [],
                    'response' => []
                ], 422);
            }
        }

        foreach ($cliente as $key => $value) {
            $cliente[$key] = utf8_encode($value);
        }
        $cpf_cnpj = $cliente["cpf_cnpj"];
        $cnpjs = [$cpf_cnpj];
        $codigos = [$cliente["codigo"]];
        // Notas devolvidas
        $devolucaoNotas = new DevolucaoNotaController;
        $devolucoes = $devolucaoNotas->modalDevolucoes($cnpjs, $codigos);
        $return = [
            'devolucoes' => $devolucoes,
        ];

        return response()->json(["status" => "success", "data" => $return]);
    }

    public function liberarTituloRenegociado(Request $request){
        $campo = $request->only('id_titulo');

        $renegociacaoTituloParcela = RenegociacaoTituloParcela::where('titulo_id_nasajon', $campo['id_titulo'])->first();
        $renegociacaoTituloParcela->renegociacao_liberada = true;
        $renegociacaoTituloParcela->updated_by = Auth::id();
        $renegociacaoTituloParcela->save();

        $response  = [
            'status' => 'success',
            'message' => '',
            'error' => '',
            'response' => [],
        ];

        return response()->json($response, 200);
    }
}
