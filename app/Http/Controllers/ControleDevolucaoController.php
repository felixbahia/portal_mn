<?php

namespace App\Http\Controllers;

use Auth;

use Carbon\Carbon;

use Illuminate\Http\Request;
use App\Http\Requests\ControleDevolucaoFilterRequest;

use App\NotasImportadasEntrada;
use App\ClienteNasajon;
use App\DevolucaoNota;
use App\NotasNasajon;
use App\FornecedorNasajon;
use App\NasajonEstabelecimento;
use App\ConhecimentoTransporteNasajon;
use App\PedidoComprasAssociacaoNotaNasajon;
use App\NotasEntradasNasajon;
use App\ComprasNasajon;
use App\CfopNasajon;
use App\User;

use Illuminate\Support\Facades\DB;

class ControleDevolucaoController extends Controller
{
    
    private $cfop_devolucao_array = ["1201","1202","1203","1204","1208","1209","1410","1411","1503","1504","1505","1506","1553","1660","1661","1662","1918","1919","2201","2202","2203","2204","2208","2209","2410","2411","2503","2504","2505","2506","2553","2660","2661","2662","2918","2919","3201","3202","3211","3503","3553","5201","5202","5208","5209","5210","5410","5411","5412","5413","5503","5553","5555","5556","5660","5661","5662","5918","5919","5921","6201","6202","6208","6209","6210","6410","6411","6412","6413","6503","6553","6555","6556","6660","6661","6662","6918","6919","6921","6949","7201","7202","7210","7211","7553","7556"];
    
    public function __construct() {
        $this->middleware(['auth']);
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ControleDevolucao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ControleDevolucao');

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        return view('programs.controle_devolucao.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function filtro(ControleDevolucaoFilterRequest $request){
        set_time_limit(300);
        $fields = $request->only('estabelecimento', 'data_inicio', 'data_fim', 'cliente_nome');

        $notas_importadas = NotasImportadasEntrada::where(
            function($query){
                $query->whereIn('documento_cfop',$this->cfop_devolucao_array)
                ->orWhereHas('itensNotas', function($query){
                    $query->whereIn('codigo_cfop',$this->cfop_devolucao_array);
                });
            }
        )
        ->with('referenciaDevolucao','referenciaDevolucao.notasNasajonChave.devolucao','referenciaDevolucao.notasNasajonNumero.devolucao','itensNotas');

        if(!empty($fields['estabelecimento'])){
            $notas_importadas->where('estabelecimento',str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
        }

        if(!empty($fields['data_inicio']) && empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d').' 00:00:00';
            $notas_importadas->where('data_emissao','>=',$data_inicio);
        }else if(empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d').' 23:59:59';
            $notas_importadas->where('data_emissao','<=',$data_fim);
        }else if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d').' 00:00:00';
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d').' 23:59:59';
            $notas_importadas->whereBetween('data_emissao',[$data_inicio,$data_fim]);
        }


        $notas_importadas = $notas_importadas->get();
        $array_chave = [];
        $array_numero = [];

        if(!empty($fields['cliente_nome'])){
            $cliente = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'),'ilike', trim($fields['cliente_nome']))->first();
            
            if(!empty($cliente)){
                $chaves = [];
                foreach($notas_importadas as $notas){
                    foreach($notas->referenciaDevolucao as $referencias){
                        $chaves[] = $referencias->chave_numero_nota.' - '.$notas->estabelecimento;
                    }
                }

                $notas_nasajon = NotasNasajon::where(function ($query) use ($chaves){
                    $query->whereIn(DB::raw('CONCAT(numero, \' - \', estabelecimento_codigo)'),$chaves)
                    ->orWhereIn(DB::raw('CONCAT(chavene, \' - \', estabelecimento_codigo)'),$chaves);
                })
                ->where('cliente_documento',$cliente->cpf_cnpj)
                ->select(DB::raw('CONCAT(numero, \' - \', estabelecimento_codigo) as numero, chavene'))
                ->get();

                $array_chave = $notas_nasajon->pluck('chavene')->toArray();
                $array_numero = $notas_nasajon->pluck('numero')->toArray();

                if(empty($notas_nasajon)){
                    return response()->json([
                        'status' => 'sucess',
                        'message' => '',
                        'error' => '', 
                        'response' => '',
                    ]);
                }
            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => '',
                ]);
            }
        }

        $notas_importadas = $notas_importadas->filter(function($query){
            $verifica_cfop = true;

            foreach($query->itensNotas as $itens){
                if($itens->codigo_cfop == "6949" && $query->estabelecimento != '20'){
                    $verifica_cfop = false;
                }
            }

            if($query->estabelecimento != '20' && $query->documento_cfop == "6949"){
                $verifica_cfop = false;
            }

            return ($verifica_cfop);
        });

        $estabelecimentos = returnEmpresasNasajonView();

        $retorno = [];
        $total = [
            'notas_importadas' => 0,
            'com_processo' => 0,
            'sem_processo' => 0,
            'com_critica' => 0,
            'sem_critica' => 0,
            'filter' => encrypt([
                'estabelecimento' => $fields['estabelecimento'],
                'data_inicio' => $fields['data_inicio'],
                'data_fim' => $fields['data_fim'],
                'cliente_nome' => $fields['cliente_nome'],
            ]),
        ];

        foreach($notas_importadas as $notas){

            if(!empty($fields['cliente_nome'])){
                $nota_valida = false;
                foreach($notas->referenciaDevolucao as $devolucao){
                    $numero = $devolucao->chave_numero_nota.' - '.$notas->estabelecimento;
                    if(in_array($devolucao->chave_numero_nota, $array_chave) || in_array($numero,$array_numero)){
                        $nota_valida = true;
                    }
                }

                if(!$nota_valida){
                    continue;
                }
            }

            if(!isset($retorno[$notas->estabelecimento])){
                $retorno[$notas->estabelecimento] = [
                    'estabelecimento' => (!empty($notas->estabelecimento)) ? $estabelecimentos[(integer)$notas->estabelecimento] : 'Sem Estabelecimento',
                    'notas_importadas' => 0,
                    'com_processo' => 0,
                    'sem_processo' => 0,
                    'com_critica' => 0,
                    'sem_critica' => 0,
                    'filter' => encrypt([
                        'estabelecimento' => $notas->estabelecimento,
                        'data_inicio' => $fields['data_inicio'],
                        'data_fim' => $fields['data_fim'],
                        'cliente_nome' => $fields['cliente_nome'],
                    ]),
                ];
            }

            $retorno[$notas->estabelecimento]['notas_importadas'] ++;
            $total['notas_importadas'] ++;

            if($notas->referenciaDevolucao->isNotEmpty()){
                if($notas->estabelecimento == '20'){
                    foreach($notas->itensNotas as $itens){
                        if($itens->codigo_cfop != '6949'){
                            $retorno[$notas->estabelecimento]['com_critica'] ++;
                            $total['com_critica'] ++;
                            break;
                        }else{
                            $retorno[$notas->estabelecimento]['sem_critica'] ++;
                            $total['sem_critica'] ++;
                            break;
                        }
                    }
                }else{
                    $retorno[$notas->estabelecimento]['sem_critica'] ++;
                    $total['sem_critica'] ++;
                }

                foreach($notas->referenciaDevolucao as $chave_devolucao){
                    if(!empty($chave_devolucao->notasNasajonChave)){
                        if(!empty($chave_devolucao->notasNasajonChave->devolucao->id)){
                            $retorno[$notas->estabelecimento]['com_processo'] ++;
                            $total['com_processo'] ++;
                        }else{
                            $retorno[$notas->estabelecimento]['sem_processo'] ++;
                            $total['sem_processo'] ++;
                        }
                    }else if($chave_devolucao->notasNasajonNumero->isNotEmpty()){
                        foreach($chave_devolucao->notasNasajonNumero as $numero){
                            if($numero->estabelecimento_codigo == $notas->estabelecimento){
                                if(!empty($numero->devolucao)){
                                    $retorno[$notas->estabelecimento]['com_processo'] ++;
                                    $total['com_processo'] ++;
                                }else{
                                    $retorno[$notas->estabelecimento]['sem_processo'] ++;
                                    $total['sem_processo'] ++;
                                }
                            }
                        }
                    }else{
                        $retorno[$notas->estabelecimento]['sem_processo'] ++;
                        $total['sem_processo'] ++;
                    }
                }
            }else{
                $retorno[$notas->estabelecimento]['sem_processo'] ++;
                $total['sem_processo'] ++;

                if($notas->estabelecimento == '20'){
                    foreach($notas->itensNotas as $itens){
                        if($itens->codigo_cfop != '6949'){
                            $retorno[$notas->estabelecimento]['com_critica'] ++;
                            $total['com_critica'] ++;
                            break;
                        }else{
                            $retorno[$notas->estabelecimento]['sem_critica'] ++;
                            $total['sem_critica'] ++;
                            break;
                        }
                    }
                }else{
                    $retorno[$notas->estabelecimento]['sem_critica'] ++;
                    $total['sem_critica'] ++;
                }
            }

        }

        foreach($retorno as $key => $return){
            $retorno[$key]['notas_importadas'] = ($retorno[$key]['notas_importadas'] > 0) ? $retorno[$key]['notas_importadas'] : '';
            $retorno[$key]['com_processo'] = ($retorno[$key]['com_processo'] > 0) ? $retorno[$key]['com_processo'] : '';
            $retorno[$key]['sem_processo'] = ($retorno[$key]['sem_processo'] > 0) ? $retorno[$key]['sem_processo'] : '';
            $retorno[$key]['com_critica'] = ($retorno[$key]['com_critica'] > 0) ? $retorno[$key]['com_critica'] : '';
            $retorno[$key]['sem_critica'] = ($retorno[$key]['sem_critica'] > 0) ? $retorno[$key]['sem_critica'] : '';
        }

        asort($retorno);

        $total['notas_importadas'] = ($total['notas_importadas'] > 0) ? $total['notas_importadas'] : '';
        $total['com_processo'] = ($total['com_processo'] > 0) ? $total['com_processo'] : '';
        $total['sem_processo'] = ($total['sem_processo'] > 0) ? $total['sem_processo'] : '';
        $total['com_critica'] = ($total['com_critica'] > 0) ? $total['com_critica'] : '';
        $total['sem_critica'] = ($total['sem_critica'] > 0) ? $total['sem_critica'] : '';

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['retorno' => $retorno, 'total' => $total],
        ]);

    }

    public function modalNotas(Request $request){
        set_time_limit(300);
        $filter = $request->only(['filters']);
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
        
        $notas_importadas = NotasImportadasEntrada::where(
            function($query){
                $query->whereIn('documento_cfop',$this->cfop_devolucao_array)
                ->orWhereHas('itensNotas', function($query){
                    $query->whereIn('codigo_cfop',$this->cfop_devolucao_array);
                });
            }
        );

        if(!empty($fields['estabelecimento'])){
            $notas_importadas->where('estabelecimento',str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
        }

        if(!empty($fields['data_inicio']) && empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d').' 00:00:00';
            $notas_importadas->where('data_emissao','>=',$data_inicio);
        }else if(empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d').' 23:59:59';
            $notas_importadas->where('data_emissao','<=',$data_fim);
        }else if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');
            $notas_importadas->whereBetween('data_emissao',[$data_inicio,$data_fim]);
        }

        $notas_importadas = $notas_importadas->get();
        $array_chave = [];
        $array_numero = [];

        if(!empty($fields['cliente_nome'])){
            $cliente = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'),'ilike', trim($fields['cliente_nome']))->first();
            
            if(!empty($cliente)){
                $chaves = [];
                foreach($notas_importadas as $notas){
                    foreach($notas->referenciaDevolucao as $referencias){
                        $chaves[] = $referencias->chave_numero_nota.' - '.$notas->estabelecimento;
                    }
                }

                $notas_nasajon = NotasNasajon::where(function ($query) use ($chaves){
                    $query->whereIn(DB::raw('CONCAT(numero, \' - \', estabelecimento_codigo)'),$chaves)
                    ->orWhereIn(DB::raw('CONCAT(chavene, \' - \', estabelecimento_codigo)'),$chaves);
                })
                ->where('cliente_documento',$cliente->cpf_cnpj)
                ->select(DB::raw('CONCAT(numero, \' - \', estabelecimento_codigo) as numero, chavene'))
                ->get();

                $array_chave = $notas_nasajon->pluck('chavene')->toArray();
                $array_numero = $notas_nasajon->pluck('numero')->toArray();

                if(empty($notas_nasajon)){
                    return response()->json([
                        'status' => 'sucess',
                        'message' => '',
                        'error' => '', 
                        'response' => '',
                    ]);
                }
            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => '',
                ]);
            }
        }

        $notas_importadas = $notas_importadas->filter(function($query){
            $verifica_cfop = true;

            foreach($query->itensNotas as $itens){
                if($itens->codigo_cfop == "6949" && $query->estabelecimento != '20'){
                    $verifica_cfop = false;
                }
            }

            if($query->estabelecimento != '20' && $query->documento_cfop == "6949"){
                $verifica_cfop = false;
            }

            return ($verifica_cfop);
        });

        $estabelecimentos = NasajonEstabelecimento::selectRaw('concat(raizcnpj,ordemcnpj) as cnpj, nomefantasia')->get();
        $chavenotas = $notas_importadas->pluck('documento_chave');
        $fornecedorcnpj = $notas_importadas->pluck('fornecedor_cnpj');
        $numeronotas = $notas_importadas->pluck('documento_numero');
        $fornecedornotas = $notas_importadas->pluck('fornecedor_id');
        $ctelancadasquery = ConhecimentoTransporteNasajon::whereIn('numero',$numeronotas)->whereIn('idtransportador',$fornecedornotas)->get();
        $nfelancadasquery = NotasEntradasNasajon::whereIn('Chave NE',$chavenotas)->get();
        $idnotas = $nfelancadasquery->pluck('Identificador Documento');
        $associacaopedidos = PedidoComprasAssociacaoNotaNasajon::whereIn('id_nota',$idnotas)->get();
        $pedidos = ComprasNasajon::whereIn('fornecedor_cnpj', $fornecedorcnpj)->whereIn('situacao', ['Aberto','Aguardando Documento','Parcialmente Liquidado'])
        ->selectRaw('fornecedor_cnpj, estabelecimento, numero_pedido')
        ->groupBy('fornecedor_cnpj','estabelecimento','numero_pedido')
        ->get();
        $fornecedores = FornecedorNasajon::whereIn('cnpj_cpf',$fornecedorcnpj)->get();
        $descricaocfop = CfopNasajon::select('cfop_codigo','cfop_descricao')->get();
        $retorno = [];
        $cnpjfornecedores = [];
        $total = [
            'notas' => 0,
            'valor' => 0,
            'com_compras' => 0,
            'sem_compras' => 0,
        ];

        foreach($notas_importadas as $notas){
            $cfopvalue = '';
            $lancada = '';
            $totalpedido = '';
            $compraconfirmada = '';

            if(!empty($fields['cliente_nome'])){
                $nota_valida = false;
                foreach($notas->referenciaDevolucao as $devolucao){
                    $numero = $devolucao->chave_numero_nota.' - '.$notas->estabelecimento;
                    if(in_array($devolucao->chave_numero_nota, $array_chave) || in_array($numero,$array_numero)){
                        $nota_valida = true;
                    }
                }

                if(!$nota_valida){
                    continue;
                }
            }

            $ctequery = $ctelancadasquery->where('numero',$notas->documento_numero)->where('idtransportador',$notas->fornecedor_id)->first();
            $nfequery = (!empty($notas->documento_chave)) ? $nfelancadasquery->where('Chave NE',$notas->documento_chave)->where('Estabelecimento',$notas->estabelecimento)->first() : null;
            $associacaopedido = (!empty($nfequery)) ? $associacaopedidos->where('id_nota',$nfequery['Identificador Documento'])->first() : null;
            $fornecedor = $notas->fornecedor_cnpj;

            if($notas->tipo == 'nfe' && empty($nfequery) && !in_array($fornecedor,$cnpjfornecedores)){
                $totalpedido = $pedidos->where('fornecedor_cnpj',$fornecedor)->where('estabelecimento',$notas->estabelecimento)->count();
                $cnpjfornecedores[] = $fornecedor;
            }
            if(!empty($associacaopedido) && $notas->cobranca == 'true'){
                $compraconfirmada = 'ok';
            }
            if(!empty($ctequery) ||  !empty($nfequery)){
                $lancada = 'ok';
            }
            if($notas->tipo == 'cte'){
                $cfopvalue  =  $notas->documento_cfop.' - '.$descricaocfop->where('cfop_codigo',$notas->documento_cfop)->first()->cfop_descricao;
            }else if($notas->tipo == 'nfe'){
                foreach($notas->itensNotas as $cfop){
                    $termo = '/' . $cfop->codigo_cfop . '/';
                    if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                        $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop.' - '.$descricaocfop->where('cfop_codigo',$cfop->codigo_cfop)->first()->cfop_descricao : ' - '.$cfop->codigo_cfop.' - '.$descricaocfop->where('cfop_codigo',$cfop->codigo_cfop)->first()->cfop_descricao;
                    }
                }
            }
            if(isset($notas->itensNotas)){
                foreach($notas->itensNotas as $cfop){
                    $termo = '/' . $cfop->codigo_cfop . '/';
                    if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                        $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop : ' - '.$cfop->codigo_cfop;
                    }
                }
            }

            $fornecedor = $fornecedores->where('cnpj_cpf',$notas->fornecedor_cnpj)->first();
            $clientecnpj = preg_replace('/[^0-9]/', '', ($notas->tipo == 'nfe') ? $notas->destinatario_cpf_cnpj : $notas->remetente_cnpj);
            $estabelecimento = $estabelecimentos->where('cnpj',$clientecnpj)->first();

            if($notas->tipo == 'nfe') {
                if (!empty($estabelecimento->nomefantasia)) {
                    $destinatario = $estabelecimento->nomefantasia . ' - ' . mask($estabelecimento->cnpj,'##.###.###/####-##');
                }else{
                    $destinatario = $notas->destinatario_nome . ' - ' .$notas->destinatario_cpf_cnpj;
                }
            }else{
                $destinatario = $notas->destinatario_nome . ' - ' . $notas->destinatario_cpf_cnpj;
            }

            $retorno[] = [
                'nota' => $notas->documento_numero,
                'fornecedor' => $notas->fornecedor_nome.' - '.$notas->fornecedor_cnpj,
                'destinatario' => $destinatario,
                'natureza' => $cfopvalue,
                'valor' => ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga,
                'pedido_compra' => (!empty($totalpedido)) ? $totalpedido : '',
                'data_emissao' => (!empty($notas->data_emissao)) ? parserData($notas->data_emissao) : null,
                'id' => encrypt($notas->id),
                'tipo' => $notas->tipo,
                'compraconfirmada' => $compraconfirmada,
                'nome_transportadora' => (!empty($fornecedor)) ? $fornecedor->nome : $notas->fornecedor_nome,
                'estabelecimento' => $fields['estabelecimento'],
                'lancada' => (!empty($lancada)) ? $lancada : '',
                'filter' => encrypt([
                    'estabelecimento' => $fields['estabelecimento'],
                    'fornecedor' => null,
                    'natureza' => null,
                    'data_inicio' => $fields['data_inicio'],
                    'data_fim' => $fields['data_fim'],
                    'frete' => null,
                    'cobranca' => null,
                    'id_transportadora' => (!empty($notas->fornecedor_id)) ? $notas->fornecedor_id : null,
                    'nome_transportadora' => (!empty($notas->fornecedor_nome)) ? $notas->fornecedor_nome : null,
                ]),
            ];
            $total['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;
        }

        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        foreach($retorno as $key => $saida){
            $retorno[$key]['valor'] = ($retorno[$key]['valor'] > 0) ? parserValor($retorno[$key]['valor']) : '';
        }

        return view('programs.consulta_notas_importadas.modal.notas')->with(['retorno' => $retorno,'total' => $total]);
    }

    public function modalNotasComProcesso(Request $request){
        set_time_limit(300);
        $filter = $request->only(['filters']);
        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente mais tarde!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }
        
        $notas_importadas = NotasImportadasEntrada::where(
            function($query){
                $query->whereIn('documento_cfop',$this->cfop_devolucao_array)
                ->orWhereHas('itensNotas', function($query){
                    $query->whereIn('codigo_cfop',$this->cfop_devolucao_array);
                });
            }
        )
        ->with('referenciaDevolucao','referenciaDevolucao.notasNasajonChave.devolucao','referenciaDevolucao.notasNasajonNumero.devolucao');

        if(!empty($fields['estabelecimento'])){
            $notas_importadas->where('estabelecimento',str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
        }

        if(!empty($fields['data_inicio']) && empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d').' 00:00:00';
            $notas_importadas->where('data_emissao','>=',$data_inicio);
        }else if(empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d').' 23:59:59';
            $notas_importadas->where('data_emissao','<=',$data_fim);
        }else if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');
            $notas_importadas->whereBetween('data_emissao',[$data_inicio,$data_fim]);
        }

        $notas_importadas = $notas_importadas->get();
        $array_chave = [];
        $array_numero = [];

        if(!empty($fields['cliente_nome'])){
            $cliente = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'),'ilike', trim($fields['cliente_nome']))->first();
            
            if(!empty($cliente)){
                $chaves = [];
                foreach($notas_importadas as $notas){
                    foreach($notas->referenciaDevolucao as $referencias){
                        $chaves[] = $referencias->chave_numero_nota.' - '.$notas->estabelecimento;
                    }
                }

                $notas_nasajon = NotasNasajon::where(function ($query) use ($chaves){
                    $query->whereIn(DB::raw('CONCAT(numero, \' - \', estabelecimento_codigo)'),$chaves)
                    ->orWhereIn(DB::raw('CONCAT(chavene, \' - \', estabelecimento_codigo)'),$chaves);
                })
                ->where('cliente_documento',$cliente->cpf_cnpj)
                ->select(DB::raw('CONCAT(numero, \' - \', estabelecimento_codigo) as numero, chavene'))
                ->get();

                $array_chave = $notas_nasajon->pluck('chavene')->toArray();
                $array_numero = $notas_nasajon->pluck('numero')->toArray();

                if(empty($notas_nasajon)){
                    return response()->json([
                        'status' => 'sucess',
                        'message' => '',
                        'error' => '', 
                        'response' => '',
                    ]);
                }
            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => '',
                ]);
            }
        }

        $notas_importadas = $notas_importadas->filter(function($query){
            $verifica_cfop = true;

            foreach($query->itensNotas as $itens){
                if($itens->codigo_cfop == "6949" && $query->estabelecimento != '20'){
                    $verifica_cfop = false;
                }
            }

            if($query->estabelecimento != '20' && $query->documento_cfop == "6949"){
                $verifica_cfop = false;
            }

            return ($verifica_cfop);
        });

        $id_notas_devolucao = [];

        foreach($notas_importadas as $notas){

            if(!empty($fields['cliente_nome'])){
                $nota_valida = false;
                foreach($notas->referenciaDevolucao as $devolucao){
                    $numero = $devolucao->chave_numero_nota.' - '.$notas->estabelecimento;
                    if(in_array($devolucao->chave_numero_nota, $array_chave) || in_array($numero,$array_numero)){
                        $nota_valida = true;
                    }
                }

                if(!$nota_valida){
                    continue;
                }
            }

            if($notas->referenciaDevolucao->isNotEmpty()){
                foreach($notas->referenciaDevolucao as $chave_devolucao){
                    if(!empty($chave_devolucao->notasNasajonChave)){
                        if(!empty($chave_devolucao->notasNasajonChave->devolucao->id)){
                            $id_notas_devolucao[] = [$chave_devolucao->notasNasajonChave->id];
                        }
                    }else if($chave_devolucao->notasNasajonNumero->isNotEmpty()){
                        foreach($chave_devolucao->notasNasajonNumero as $numero){
                            if($numero->estabelecimento_codigo == $notas->estabelecimento){
                                if(!empty($numero->devolucao)){
                                    $id_notas_devolucao[] = [$numero->id];
                                }
                            }
                        }
                    }
                }
            }

        }

        $query = DevolucaoNota::with('nota_nasajon', 'status_detalhes')
        ->whereIn('nota_id',$id_notas_devolucao);
        $devolucoesObj = $query->get();

        $response = [];

        $estabelecimentos = returnEmpresasNasajonView();

        if(in_array(Auth::user()->tipo_usuario_id, [16, 12])){

            $devolucoesObj->load('nota_nasajon.revisao_vendedor_comissao');

            $users = User::
                where('id', Auth::id())
                ->whereNotNull('codigo_representante')
                ->first();

            $devolucoesObj = $devolucoesObj->filter(function($linha) use($users){
                return $users->codigo_representante == $linha->nota_nasajon['revisao_vendedor_comissao']['vendedor_codigo'];
            });
        }

        $devolucoesObj->each(function($devolucao) use (&$response, $estabelecimentos){
            $linha = [];

            $id = encrypt($devolucao->id);

            $linha['id_requisicao'] = $devolucao->id;
            $linha['id'] = $id;
            $linha['nota_fiscal'] = $devolucao->nota_fiscal;
            $linha['nota_id'] = $devolucao->nota_id;
            $linha['estabelecimento'] = $estabelecimentos[intval($devolucao->estabelecimento)];
            $linha['cliente'] = $devolucao->cliente_razao_social . ' - ' . $devolucao->cliente_cpf_cnpj;
            $linha['valor'] = $devolucao->valor;

            if(!empty($devolucao->motivo_devolucao)){
                $linha['motivo'] = $devolucao->motivo_devolucao->descricao;
            }
            else{
                $linha['motivo'] = '';
            }

            if(!is_null($devolucao->deleted_at)){
                $linha['status_exibir'] = 'Cancelada';
                $linha['status'] = 'cancelado';
            }
            else{
                $linha['status_exibir'] = $devolucao->status_detalhes->descricao;
                $linha['status'] = $devolucao->devolucao_nota_status_id;
            }

            if($devolucao->valor_parcial){
                $linha['valor_parcial'] = 'Parcial';
            }
            else{
                $linha['valor_parcial'] = 'Completo';
            }

            
            $response[] = $linha;
        });

        return view('programs.controle_devolucao.modal.notas_com_processo')->with(['retorno' => $response]);

    }

    public function modalNotasSemProcesso(Request $request){
        set_time_limit(300);
        $filter = $request->only(['filters']);
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
        
        $notas_importadas = NotasImportadasEntrada::where(
            function($query){
                $query->whereIn('documento_cfop',$this->cfop_devolucao_array)
                ->orWhereHas('itensNotas', function($query){
                    $query->whereIn('codigo_cfop',$this->cfop_devolucao_array);
                });
            }
        )
        ->with('referenciaDevolucao','referenciaDevolucao.notasNasajonChave.devolucao','referenciaDevolucao.notasNasajonNumero.devolucao');

        if(!empty($fields['estabelecimento'])){
            $notas_importadas->where('estabelecimento',str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
        }

        if(!empty($fields['data_inicio']) && empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d').' 00:00:00';
            $notas_importadas->where('data_emissao','>=',$data_inicio);
        }else if(empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d').' 23:59:59';
            $notas_importadas->where('data_emissao','<=',$data_fim);
        }else if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');
            $notas_importadas->whereBetween('data_emissao',[$data_inicio,$data_fim]);
        }

        $notas_importadas = $notas_importadas->get();
        $array_chave = [];
        $array_numero = [];

        if(!empty($fields['cliente_nome'])){
            $cliente = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'),'ilike', trim($fields['cliente_nome']))->first();
            if(!empty($cliente)){
                $chaves = [];
                foreach($notas_importadas as $notas){
                    foreach($notas->referenciaDevolucao as $referencias){
                        $chaves[] = $referencias->chave_numero_nota.' - '.$notas->estabelecimento;
                    }
                }

                $notas_nasajon = NotasNasajon::where(function ($query) use ($chaves){
                    $query->whereIn(DB::raw('CONCAT(numero, \' - \', estabelecimento_codigo)'),$chaves)
                    ->orWhereIn(DB::raw('CONCAT(chavene, \' - \', estabelecimento_codigo)'),$chaves);
                })
                ->where('cliente_documento',$cliente->cpf_cnpj)
                ->select(DB::raw('CONCAT(numero, \' - \', estabelecimento_codigo) as numero, chavene'))
                ->get();

                $array_chave = $notas_nasajon->pluck('chavene')->toArray();
                $array_numero = $notas_nasajon->pluck('numero')->toArray();

                if(empty($notas_nasajon)){
                    return response()->json([
                        'status' => 'sucess',
                        'message' => '',
                        'error' => '', 
                        'response' => '',
                    ]);
                }
            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => '',
                ]);
            }
        }

        $notas_importadas = $notas_importadas->filter(function($query){
            $verifica_cfop = true;

            foreach($query->itensNotas as $itens){
                if($itens->codigo_cfop == "6949" && $query->estabelecimento != '20'){
                    $verifica_cfop = false;
                }
            }

            if($query->estabelecimento != '20' && $query->documento_cfop == "6949"){
                $verifica_cfop = false;
            }

            return ($verifica_cfop);
        });

        $estabelecimentos = NasajonEstabelecimento::selectRaw('concat(raizcnpj,ordemcnpj) as cnpj, nomefantasia')->get();
        $chavenotas = $notas_importadas->pluck('documento_chave');
        $fornecedorcnpj = $notas_importadas->pluck('fornecedor_cnpj');
        $numeronotas = $notas_importadas->pluck('documento_numero');
        $fornecedornotas = $notas_importadas->pluck('fornecedor_id');
        $ctelancadasquery = ConhecimentoTransporteNasajon::whereIn('numero',$numeronotas)->whereIn('idtransportador',$fornecedornotas)->get();
        $nfelancadasquery = NotasEntradasNasajon::whereIn('Chave NE',$chavenotas)->get();
        $idnotas = $nfelancadasquery->pluck('Identificador Documento');
        $associacaopedidos = PedidoComprasAssociacaoNotaNasajon::whereIn('id_nota',$idnotas)->get();
        $pedidos = ComprasNasajon::whereIn('fornecedor_cnpj', $fornecedorcnpj)->whereIn('situacao', ['Aberto','Aguardando Documento','Parcialmente Liquidado'])
        ->selectRaw('fornecedor_cnpj, estabelecimento, numero_pedido')
        ->groupBy('fornecedor_cnpj','estabelecimento','numero_pedido')
        ->get();
        $fornecedores = FornecedorNasajon::whereIn('cnpj_cpf',$fornecedorcnpj)->get();
        $descricaocfop = CfopNasajon::select('cfop_codigo','cfop_descricao')->get();
        $retorno = [];
        $cnpjfornecedores = [];
        $total = [
            'notas' => 0,
            'valor' => 0,
            'com_compras' => 0,
            'sem_compras' => 0,
        ];

        foreach($notas_importadas as $notas){
            $cfopvalue = '';
            $lancada = '';
            $totalpedido = '';
            $compraconfirmada = '';
            $sem_processo = false;

            if(!empty($fields['cliente_nome'])){
                $nota_valida = false;
                foreach($notas->referenciaDevolucao as $devolucao){
                    $numero = $devolucao->chave_numero_nota.' - '.$notas->estabelecimento;
                    if(in_array($devolucao->chave_numero_nota, $array_chave) || in_array($numero,$array_numero)){
                        $nota_valida = true;
                    }
                }

                if(!$nota_valida){
                    continue;
                }
            }

            $ctequery = $ctelancadasquery->where('numero',$notas->documento_numero)->where('idtransportador',$notas->fornecedor_id)->first();
            $nfequery = (!empty($notas->documento_chave)) ? $nfelancadasquery->where('Chave NE',$notas->documento_chave)->where('Estabelecimento',$notas->estabelecimento)->first() : null;
            $associacaopedido = (!empty($nfequery)) ? $associacaopedidos->where('id_nota',$nfequery['Identificador Documento'])->first() : null;
            $fornecedor = $notas->fornecedor_cnpj;

            if($notas->referenciaDevolucao->isNotEmpty()){
                foreach($notas->referenciaDevolucao as $chave_devolucao){
                    if(!empty($chave_devolucao->notasNasajonChave)){
                        if(!empty($chave_devolucao->notasNasajonChave->devolucao->id)){
                            $sem_processo = true;
                        }
                    }else if($chave_devolucao->notasNasajonNumero->isNotEmpty()){
                        foreach($chave_devolucao->notasNasajonNumero as $numero){
                            if($numero->estabelecimento_codigo == $notas->estabelecimento){
                                if(!empty($numero->devolucao)){
                                    $sem_processo = true;
                                }
                            }
                        }
                    }
                }
            }

            if($sem_processo){
                continue;
            }

            if($notas->tipo == 'nfe' && empty($nfequery) && !in_array($fornecedor,$cnpjfornecedores)){
                $totalpedido = $pedidos->where('fornecedor_cnpj',$fornecedor)->where('estabelecimento',$notas->estabelecimento)->count();
                $cnpjfornecedores[] = $fornecedor;
            }
            if(!empty($associacaopedido) && $notas->cobranca == 'true'){
                $compraconfirmada = 'ok';
            }
            if(!empty($ctequery) ||  !empty($nfequery)){
                $lancada = 'ok';
            }
            if($notas->tipo == 'cte'){
                $cfopvalue  =  $notas->documento_cfop.' - '.$descricaocfop->where('cfop_codigo',$notas->documento_cfop)->first()->cfop_descricao;
            }else if($notas->tipo == 'nfe'){
                foreach($notas->itensNotas as $cfop){
                    $termo = '/' . $cfop->codigo_cfop . '/';
                    if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                        $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop.' - '.$descricaocfop->where('cfop_codigo',$cfop->codigo_cfop)->first()->cfop_descricao : ' - '.$cfop->codigo_cfop.' - '.$descricaocfop->where('cfop_codigo',$cfop->codigo_cfop)->first()->cfop_descricao;
                    }
                }
            }
            if(isset($notas->itensNotas)){
                foreach($notas->itensNotas as $cfop){
                    $termo = '/' . $cfop->codigo_cfop . '/';
                    if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                        $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop : ' - '.$cfop->codigo_cfop;
                    }
                }
            }

            $fornecedor = $fornecedores->where('cnpj_cpf',$notas->fornecedor_cnpj)->first();
            $clientecnpj = preg_replace('/[^0-9]/', '', ($notas->tipo == 'nfe') ? $notas->destinatario_cpf_cnpj : $notas->remetente_cnpj);
            $estabelecimento = $estabelecimentos->where('cnpj',$clientecnpj)->first();

            if($notas->tipo == 'nfe') {
                if (!empty($estabelecimento->nomefantasia)) {
                    $destinatario = $estabelecimento->nomefantasia . ' - ' . mask($estabelecimento->cnpj,'##.###.###/####-##');
                }else{
                    $destinatario = $notas->destinatario_nome . ' - ' .$notas->destinatario_cpf_cnpj;
                }
            }else{
                $destinatario = $notas->destinatario_nome . ' - ' . $notas->destinatario_cpf_cnpj;
            }

            $retorno[] = [
                'nota' => $notas->documento_numero,
                'fornecedor' => $notas->fornecedor_nome.' - '.$notas->fornecedor_cnpj,
                'destinatario' => $destinatario,
                'natureza' => $cfopvalue,
                'valor' => ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga,
                'pedido_compra' => (!empty($totalpedido)) ? $totalpedido : '',
                'data_emissao' => (!empty($notas->data_emissao)) ? parserData($notas->data_emissao) : null,
                'id' => encrypt($notas->id),
                'tipo' => $notas->tipo,
                'compraconfirmada' => $compraconfirmada,
                'nome_transportadora' => (!empty($fornecedor)) ? $fornecedor->nome : $notas->fornecedor_nome,
                'estabelecimento' => $fields['estabelecimento'],
                'lancada' => (!empty($lancada)) ? $lancada : '',
                'filter' => encrypt([
                    'estabelecimento' => $fields['estabelecimento'],
                    'fornecedor' => null,
                    'natureza' => null,
                    'data_inicio' => $fields['data_inicio'],
                    'data_fim' => $fields['data_fim'],
                    'frete' => null,
                    'cobranca' => null,
                    'id_transportadora' => (!empty($notas->fornecedor_id)) ? $notas->fornecedor_id : null,
                    'nome_transportadora' => (!empty($notas->fornecedor_nome)) ? $notas->fornecedor_nome : null,
                ]),
            ];
            $total['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;
        }

        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        foreach($retorno as $key => $saida){
            $retorno[$key]['valor'] = ($retorno[$key]['valor'] > 0) ? parserValor($retorno[$key]['valor']) : '';
        }

        return view('programs.consulta_notas_importadas.modal.notas')->with(['retorno' => $retorno,'total' => $total]);
    }

    public function modalNotasSemCritica(Request $request){
        set_time_limit(300);
        $filter = $request->only(['filters']);
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
        
        $notas_importadas = NotasImportadasEntrada::where(function($query){
            $query->whereIn('documento_cfop',$this->cfop_devolucao_array)
            ->orWhereHas('itensNotas', function($query){
                $query->whereIn('codigo_cfop',$this->cfop_devolucao_array);
            });
        })
        ->with('itensNotas');

        if(!empty($fields['estabelecimento'])){
            $notas_importadas->where('estabelecimento',str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
        }

        if(!empty($fields['data_inicio']) && empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d').' 00:00:00';
            $notas_importadas->where('data_emissao','>=',$data_inicio);
        }else if(empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d').' 23:59:59';
            $notas_importadas->where('data_emissao','<=',$data_fim);
        }else if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');
            $notas_importadas->whereBetween('data_emissao',[$data_inicio,$data_fim]);
        }

        $notas_importadas = $notas_importadas->get();
        $array_chave = [];
        $array_numero = [];

        if(!empty($fields['cliente_nome'])){
            $cliente = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'),'ilike', trim($fields['cliente_nome']))->first();
            
            if(!empty($cliente)){
                $chaves = [];
                foreach($notas_importadas as $notas){
                    foreach($notas->referenciaDevolucao as $referencias){
                        $chaves[] = $referencias->chave_numero_nota.' - '.$notas->estabelecimento;
                    }
                }

                $notas_nasajon = NotasNasajon::where(function ($query) use ($chaves){
                    $query->whereIn(DB::raw('CONCAT(numero, \' - \', estabelecimento_codigo)'),$chaves)
                    ->orWhereIn(DB::raw('CONCAT(chavene, \' - \', estabelecimento_codigo)'),$chaves);
                })
                ->where('cliente_documento',$cliente->cpf_cnpj)
                ->select(DB::raw('CONCAT(numero, \' - \', estabelecimento_codigo) as numero, chavene'))
                ->get();

                $array_chave = $notas_nasajon->pluck('chavene')->toArray();
                $array_numero = $notas_nasajon->pluck('numero')->toArray();

                if(empty($notas_nasajon)){
                    return response()->json([
                        'status' => 'sucess',
                        'message' => '',
                        'error' => '', 
                        'response' => '',
                    ]);
                }
            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => '',
                ]);
            }
        }

        $notas_importadas = $notas_importadas->filter(function($query){
            $verifica_cfop = true;

            foreach($query->itensNotas as $itens){
                if($itens->codigo_cfop == "6949" && $query->estabelecimento != '20'){
                    $verifica_cfop = false;
                }
            }

            if($query->estabelecimento != '20' && $query->documento_cfop == "6949"){
                $verifica_cfop = false;
            }

            return ($verifica_cfop);
        });

        $estabelecimentos = NasajonEstabelecimento::selectRaw('concat(raizcnpj,ordemcnpj) as cnpj, nomefantasia')->get();
        $chavenotas = $notas_importadas->pluck('documento_chave');
        $fornecedorcnpj = $notas_importadas->pluck('fornecedor_cnpj');
        $numeronotas = $notas_importadas->pluck('documento_numero');
        $fornecedornotas = $notas_importadas->pluck('fornecedor_id');
        $ctelancadasquery = ConhecimentoTransporteNasajon::whereIn('numero',$numeronotas)->whereIn('idtransportador',$fornecedornotas)->get();
        $nfelancadasquery = NotasEntradasNasajon::whereIn('Chave NE',$chavenotas)->get();
        $idnotas = $nfelancadasquery->pluck('Identificador Documento');
        $associacaopedidos = PedidoComprasAssociacaoNotaNasajon::whereIn('id_nota',$idnotas)->get();
        $pedidos = ComprasNasajon::whereIn('fornecedor_cnpj', $fornecedorcnpj)->whereIn('situacao', ['Aberto','Aguardando Documento','Parcialmente Liquidado'])
        ->selectRaw('fornecedor_cnpj, estabelecimento, numero_pedido')
        ->groupBy('fornecedor_cnpj','estabelecimento','numero_pedido')
        ->get();
        $fornecedores = FornecedorNasajon::whereIn('cnpj_cpf',$fornecedorcnpj)->get();
        $descricaocfop = CfopNasajon::select('cfop_codigo','cfop_descricao')->get();
        $retorno = [];
        $cnpjfornecedores = [];
        $total = [
            'notas' => 0,
            'valor' => 0,
            'com_compras' => 0,
            'sem_compras' => 0,
        ];

        foreach($notas_importadas as $notas){
            $cfopvalue = '';
            $lancada = '';
            $totalpedido = '';
            $compraconfirmada = '';
            $sem_critica = false;

            if(!empty($fields['cliente_nome'])){
                $nota_valida = false;
                foreach($notas->referenciaDevolucao as $devolucao){
                    $numero = $devolucao->chave_numero_nota.' - '.$notas->estabelecimento;
                    if(in_array($devolucao->chave_numero_nota, $array_chave) || in_array($numero,$array_numero)){
                        $nota_valida = true;
                    }
                }

                if(!$nota_valida){
                    continue;
                }
            }

            $ctequery = $ctelancadasquery->where('numero',$notas->documento_numero)->where('idtransportador',$notas->fornecedor_id)->first();
            $nfequery = (!empty($notas->documento_chave)) ? $nfelancadasquery->where('Chave NE',$notas->documento_chave)->where('Estabelecimento',$notas->estabelecimento)->first() : null;
            $associacaopedido = (!empty($nfequery)) ? $associacaopedidos->where('id_nota',$nfequery['Identificador Documento'])->first() : null;
            $fornecedor = $notas->fornecedor_cnpj;

            if($notas->estabelecimento == '20'){
                foreach($notas->itensNotas as $itens){
                    if($itens->codigo_cfop != '6949'){
                        $sem_critica = true;
                    }
                }
            }else if(!empty($notas->documento_cfop)){
                $sem_critica = true;
            }

            if($sem_critica){
                continue;
            }

            if($notas->tipo == 'nfe' && empty($nfequery) && !in_array($fornecedor,$cnpjfornecedores)){
                $totalpedido = $pedidos->where('fornecedor_cnpj',$fornecedor)->where('estabelecimento',$notas->estabelecimento)->count();
                $cnpjfornecedores[] = $fornecedor;
            }
            if(!empty($associacaopedido) && $notas->cobranca == 'true'){
                $compraconfirmada = 'ok';
            }
            if(!empty($ctequery) ||  !empty($nfequery)){
                $lancada = 'ok';
            }
            if($notas->tipo == 'cte'){
                $cfopvalue  =  $notas->documento_cfop.' - '.$descricaocfop->where('cfop_codigo',$notas->documento_cfop)->first()->cfop_descricao;
            }else if($notas->tipo == 'nfe'){
                foreach($notas->itensNotas as $cfop){
                    $termo = '/' . $cfop->codigo_cfop . '/';
                    if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                        $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop.' - '.$descricaocfop->where('cfop_codigo',$cfop->codigo_cfop)->first()->cfop_descricao : ' - '.$cfop->codigo_cfop.' - '.$descricaocfop->where('cfop_codigo',$cfop->codigo_cfop)->first()->cfop_descricao;
                    }
                }
            }
            if(isset($notas->itensNotas)){
                foreach($notas->itensNotas as $cfop){
                    $termo = '/' . $cfop->codigo_cfop . '/';
                    if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                        $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop : ' - '.$cfop->codigo_cfop;
                    }
                }
            }

            $fornecedor = $fornecedores->where('cnpj_cpf',$notas->fornecedor_cnpj)->first();
            $clientecnpj = preg_replace('/[^0-9]/', '', ($notas->tipo == 'nfe') ? $notas->destinatario_cpf_cnpj : $notas->remetente_cnpj);
            $estabelecimento = $estabelecimentos->where('cnpj',$clientecnpj)->first();

            if($notas->tipo == 'nfe') {
                if (!empty($estabelecimento->nomefantasia)) {
                    $destinatario = $estabelecimento->nomefantasia . ' - ' . mask($estabelecimento->cnpj,'##.###.###/####-##');
                }else{
                    $destinatario = $notas->destinatario_nome . ' - ' .$notas->destinatario_cpf_cnpj;
                }
            }else{
                $destinatario = $notas->destinatario_nome . ' - ' . $notas->destinatario_cpf_cnpj;
            }

            $retorno[] = [
                'nota' => $notas->documento_numero,
                'fornecedor' => $notas->fornecedor_nome.' - '.$notas->fornecedor_cnpj,
                'destinatario' => $destinatario,
                'natureza' => $cfopvalue,
                'valor' => ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga,
                'pedido_compra' => (!empty($totalpedido)) ? $totalpedido : '',
                'data_emissao' => (!empty($notas->data_emissao)) ? parserData($notas->data_emissao) : null,
                'id' => encrypt($notas->id),
                'tipo' => $notas->tipo,
                'compraconfirmada' => $compraconfirmada,
                'nome_transportadora' => (!empty($fornecedor)) ? $fornecedor->nome : $notas->fornecedor_nome,
                'estabelecimento' => $fields['estabelecimento'],
                'lancada' => (!empty($lancada)) ? $lancada : '',
                'filter' => encrypt([
                    'estabelecimento' => $fields['estabelecimento'],
                    'fornecedor' => null,
                    'natureza' => null,
                    'data_inicio' => $fields['data_inicio'],
                    'data_fim' => $fields['data_fim'],
                    'frete' => null,
                    'cobranca' => null,
                    'id_transportadora' => (!empty($notas->fornecedor_id)) ? $notas->fornecedor_id : null,
                    'nome_transportadora' => (!empty($notas->fornecedor_nome)) ? $notas->fornecedor_nome : null,
                ]),
            ];
            $total['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;
        }

        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        foreach($retorno as $key => $saida){
            $retorno[$key]['valor'] = ($retorno[$key]['valor'] > 0) ? parserValor($retorno[$key]['valor']) : '';
        }

        return view('programs.consulta_notas_importadas.modal.notas')->with(['retorno' => $retorno,'total' => $total]);
    }

    public function modalNotasComCritica(Request $request){
        set_time_limit(300);
        $filter = $request->only(['filters']);
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
        
        $notas_importadas = NotasImportadasEntrada::where(
            function($query){
                $query->whereIn('documento_cfop',$this->cfop_devolucao_array)
                ->orWhereHas('itensNotas', function($query){
                    $query->whereIn('codigo_cfop',$this->cfop_devolucao_array);
                });
            }
        )
        ->with('itensNotas');

        if(!empty($fields['estabelecimento'])){
            $notas_importadas->where('estabelecimento',str_pad($fields['estabelecimento'],2,'0',STR_PAD_LEFT));
        }

        if(!empty($fields['data_inicio']) && empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d').' 00:00:00';
            $notas_importadas->where('data_emissao','>=',$data_inicio);
        }else if(empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d').' 23:59:59';
            $notas_importadas->where('data_emissao','<=',$data_fim);
        }else if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');
            $notas_importadas->whereBetween('data_emissao',[$data_inicio,$data_fim]);
        }

        $notas_importadas = $notas_importadas->get();
        $array_chave = [];
        $array_numero = [];

        if(!empty($fields['cliente_nome'])){

            $cliente = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'),'ilike', trim($fields['cliente_nome']))->first();

            if(!empty($cliente)){
                $chaves = [];
                foreach($notas_importadas as $notas){
                    foreach($notas->referenciaDevolucao as $referencias){
                        $chaves[] = $referencias->chave_numero_nota.' - '.$notas->estabelecimento;
                    }
                }

                $notas_nasajon = NotasNasajon::where(function ($query) use ($chaves){
                    $query->whereIn(DB::raw('CONCAT(numero, \' - \', estabelecimento_codigo)'),$chaves)
                    ->orWhereIn(DB::raw('CONCAT(chavene, \' - \', estabelecimento_codigo)'),$chaves);
                })
                ->where('cliente_documento',$cliente->cpf_cnpj)
                ->select(DB::raw('CONCAT(numero, \' - \', estabelecimento_codigo) as numero, chavene'))
                ->get();

                $array_chave = $notas_nasajon->pluck('chavene')->toArray();
                $array_numero = $notas_nasajon->pluck('numero')->toArray();

                if(empty($notas_nasajon)){
                    return response()->json([
                        'status' => 'sucess',
                        'message' => '',
                        'error' => '', 
                        'response' => '',
                    ]);
                }
            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => '',
                ]);
            }

        }

        $notas_importadas = $notas_importadas->filter(function($query){
            $verifica_cfop = true;

            foreach($query->itensNotas as $itens){
                if($itens->codigo_cfop == "6949" && $query->estabelecimento != '20'){
                    $verifica_cfop = false;
                }
            }

            if($query->estabelecimento != '20' && $query->documento_cfop == "6949"){
                $verifica_cfop = false;
            }

            return ($verifica_cfop);
        });

        $estabelecimentos = NasajonEstabelecimento::selectRaw('concat(raizcnpj,ordemcnpj) as cnpj, nomefantasia')->get();
        $chavenotas = $notas_importadas->pluck('documento_chave');
        $fornecedorcnpj = $notas_importadas->pluck('fornecedor_cnpj');
        $numeronotas = $notas_importadas->pluck('documento_numero');
        $fornecedornotas = $notas_importadas->pluck('fornecedor_id');
        $ctelancadasquery = ConhecimentoTransporteNasajon::whereIn('numero',$numeronotas)->whereIn('idtransportador',$fornecedornotas)->get();
        $nfelancadasquery = NotasEntradasNasajon::whereIn('Chave NE',$chavenotas)->get();
        $pedidos = ComprasNasajon::whereIn('fornecedor_cnpj', $fornecedorcnpj)->whereIn('situacao', ['Aberto','Aguardando Documento','Parcialmente Liquidado'])
        ->selectRaw('fornecedor_cnpj, estabelecimento, numero_pedido')
        ->groupBy('fornecedor_cnpj','estabelecimento','numero_pedido')
        ->get();
        $fornecedores = FornecedorNasajon::whereIn('cnpj_cpf',$fornecedorcnpj)->get();
        $retorno = [];
        $cnpjfornecedores = [];
        $total = [
            'notas' => 0,
            'valor' => 0,
            'com_compras' => 0,
            'sem_compras' => 0,
        ];
        
        foreach($notas_importadas as $notas){
            $cfopvalue = '';
            $lancada = '';
            $totalpedido = '';
            $com_critica = false;
            $numero_nota_nasajon = '';

            if(!empty($fields['cliente_nome'])){
                $nota_valida = false;
                foreach($notas->referenciaDevolucao as $devolucao){
                    $numero = $devolucao->chave_numero_nota.' - '.$notas->estabelecimento;
                    if(in_array($devolucao->chave_numero_nota, $array_chave) || in_array($numero,$array_numero)){
                        $nota_valida = true;
                    }
                }

                if(!$nota_valida){
                    continue;
                }
            }

            $ctequery = $ctelancadasquery->where('numero',$notas->documento_numero)->where('idtransportador',$notas->fornecedor_id)->first();
            $nfequery = (!empty($notas->documento_chave)) ? $nfelancadasquery->where('Chave NE',$notas->documento_chave)->where('Estabelecimento',$notas->estabelecimento)->first() : null;
            $fornecedor = $notas->fornecedor_cnpj;

            if($notas->estabelecimento == '20'){
                foreach($notas->itensNotas as $itens){
                    if($itens->codigo_cfop == '6949'){
                        $com_critica = true;
                    }
                }
            }else{
                $com_critica = true;
            }

            if($com_critica){
                continue;
            }

            if($notas->referenciaDevolucao->isNotEmpty()){
                foreach($notas->referenciaDevolucao as $chave_devolucao){
                    if(!empty($chave_devolucao->notasNasajonChave)){
                            $numero_nota_nasajon = $chave_devolucao->notasNasajonChave->numero;
                    }else if($chave_devolucao->notasNasajonNumero->isNotEmpty()){
                        foreach($chave_devolucao->notasNasajonNumero as $numero){
                            $numero_nota_nasajon = $numero->notasNasajonChave->numero;
                        }
                    }
                }
            }

            if($notas->tipo == 'nfe' && empty($nfequery) && !in_array($fornecedor,$cnpjfornecedores)){
                $totalpedido = $pedidos->where('fornecedor_cnpj',$fornecedor)->where('estabelecimento',$notas->estabelecimento)->count();
                $cnpjfornecedores[] = $fornecedor;
            }
            if(!empty($ctequery) ||  !empty($nfequery)){
                $lancada = 'ok';
            }
            if($notas->tipo == 'cte'){
                $cfopvalue  =  $notas->documento_cfop;
            }else if($notas->tipo == 'nfe'){
                foreach($notas->itensNotas as $cfop){
                    $termo = '/' . $cfop->codigo_cfop . '/';
                    if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                        $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop : ' - '.$cfop->codigo_cfop;
                    }
                }
            }
            
            if(isset($notas->itensNotas)){
                foreach($notas->itensNotas as $cfop){
                    $termo = '/' . $cfop->codigo_cfop . '/';
                    if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                        $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop : ' - '.$cfop->codigo_cfop;
                    }
                }
            }

            $fornecedor = $fornecedores->where('cnpj_cpf',$notas->fornecedor_cnpj)->first();
            $clientecnpj = preg_replace('/[^0-9]/', '', ($notas->tipo == 'nfe') ? $notas->destinatario_cpf_cnpj : $notas->remetente_cnpj);
            $estabelecimento = $estabelecimentos->where('cnpj',$clientecnpj)->first();

            if($notas->tipo == 'nfe') {
                if (!empty($estabelecimento->nomefantasia)) {
                    $destinatario = $estabelecimento->nomefantasia . ' - ' . mask($estabelecimento->cnpj,'##.###.###/####-##');
                }else{
                    $destinatario = $notas->destinatario_nome . ' - ' .$notas->destinatario_cpf_cnpj;
                }
            }else{
                $destinatario = $notas->destinatario_nome . ' - ' . $notas->destinatario_cpf_cnpj;
            }

            $retorno[] = [
                'nota' => $notas->documento_numero,
                'nota_nasajon' => $numero_nota_nasajon,
                'fornecedor' => $notas->fornecedor_nome.' - '.$notas->fornecedor_cnpj,
                'destinatario' => $destinatario,
                'natureza' => $cfopvalue,
                'valor' => ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga,
                'nota_venda' => (!empty($totalpedido)) ? $totalpedido : '',
                'data_emissao' => (!empty($notas->data_emissao)) ? parserData($notas->data_emissao) : null,
                'id' => encrypt($notas->id),
                'tipo' => $notas->tipo,
                'critica' => 'Natureza de Operação Divergente',
                'estabelecimento' => $fields['estabelecimento'],
                'lancada' => (!empty($lancada)) ? $lancada : '',
                'filter' => encrypt([
                    'estabelecimento' => $fields['estabelecimento'],
                    'fornecedor' => null,
                    'natureza' => null,
                    'data_inicio' => $fields['data_inicio'],
                    'data_fim' => $fields['data_fim'],
                    'frete' => null,
                    'cobranca' => null,
                    'id_transportadora' => (!empty($notas->fornecedor_id)) ? $notas->fornecedor_id : null,
                    'nome_transportadora' => (!empty($notas->fornecedor_nome)) ? $notas->fornecedor_nome : null,
                ]),
            ];
            $total['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;
        }

        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        foreach($retorno as $key => $saida){
            $retorno[$key]['valor'] = ($retorno[$key]['valor'] > 0) ? parserValor($retorno[$key]['valor']) : '';
        }

        return view('programs.controle_devolucao.modal.notas_com_critica')->with(['retorno' => $retorno,'total' => $total]);
    }


}
