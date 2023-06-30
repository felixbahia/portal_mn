<?php

namespace App\Http\Controllers;

use Auth;
use Xml;
use Exception;
use PDF;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

use App\NotasImportadasEntrada;
use App\CfopNasajon;
use App\NasajonEstabelecimento;
use App\NotasEntradasNasajon;
use App\ConhecimentoTransporteNasajon;
use App\NotasNasajon;
use App\FornecedorNasajon;
use App\NotasImportadasEntradaDevolucaoReferencia;
use App\Movimentacao;

class NotasImportadasEntradasController extends Controller
{
    public function __construct() {
        $this->middleware(['auth']);
        $estabelecimentos[''] = 'ESTABELECIMENTO';
        $estabelecimentosReturn = returnEmpresasNasajonView();
        foreach($estabelecimentosReturn as $key => $return){
            $estabelecimentos[$key] = $return;
        }
        $this->estabelecimentos = $estabelecimentos;
    }

    private $cfop_devolucao = ['5202','5208','5209','5210','5410','5411','5412','5413','5503','5553','5555','5556','5660'
    ,'5661','5662','5918','5919','5921','6201','6202','6208','6209','6210','6410','6411','6412','6413','6503','6553','6555','6556','6660'
    ,'6661','6662','6918','6919','6921','7201','7202','7210','7211','7553','7556'];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ConsultaNotasImportadasController") === false){
            return abort(403);
        }
        $estabelecimentos = $this->estabelecimentos;
        $request->session()->flash('model', 'App\ConsultaNotasImportadasController');
        return view("programs.consulta_notas_importadas.index")->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function filter(Request $request){
        set_time_limit(300);
        ini_set('memory_limit', '1024M');
        $fields = $request->only('estabelecimento', 'fornecedor', 'natureza', 'data_inicio', 'data_fim','cobranca','frete','lancadas');

        $notasimportadas = NotasImportadasEntrada::whereBetween('data_emissao', [Carbon::createFromFormat('d/m/Y',$fields['data_inicio'])->format('Y-m-d').' 00:00:00',Carbon::createFromFormat('d/m/Y',$fields['data_fim'])->format('Y-m-d').' 23:59:59'])
        ->with('itensNotas','cteNfe.faturamentoOnline','fornecedor','comprasQuantidade','notasEntradas','notasEntradas.nota_entrada');

        if(!empty($fields['estabelecimento']) || $fields['estabelecimento'] == '0'){
            $estabelecimento = str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT);
            $notasimportadas->where('estabelecimento',$estabelecimento);
        }
        if(!empty($fields['fornecedor'])){
            $cnpj_fornecedor = FornecedorNasajon::where(DB::raw('CONCAT(TRIM(nome), \' - \', cnpj_cpf)'), 'ilike', $fields['fornecedor'])->select('cnpj_cpf')->first();
            if(!empty($cnpj_fornecedor)){
                $notasimportadas->where('fornecedor_cnpj', 'ilike', $cnpj_fornecedor->cnpj_cpf);
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => '',
                    'error' => '',
                    'response' => ['saida' => null, 'total' => null],
                ]);
            }
        }
        if(!empty($fields['natureza'])){
            $notasimportadas->where(function($query) use ($fields){
                $query->where('documento_cfop',$fields['natureza'])
                ->orWhereHas('itensNotas', function($query) use ($fields){
                    $query->where('codigo_cfop',$fields['natureza']);
                });
            });
        }
        if($fields['cobranca'] == 'sem_cobranca'){
            $notasimportadas->where('cobranca',false);
        }
        if($fields['cobranca'] == 'com_cobranca' || $fields['lancadas'] == 'notas_nao_lancadas'){
            $notasimportadas->where('cobranca',true);
            $notasimportadas->where('estabelecimento','<>','20');
        }
        if($fields['frete'] == 'sem_frete'){
            $notasimportadas->where('tipo','nfe');
        }
        if($fields['frete'] == 'com_frete'){
            $notasimportadas->where('tipo','cte');
        }

        $notasimportadas = $notasimportadas->get();
        
        $fornecedor_documento =  $notasimportadas->pluck('fornecedor_cnpj');
        $numerodocumento = $notasimportadas->pluck('documento_numero');
        $ctelancadas = ConhecimentoTransporteNasajon::whereIn(DB::raw('cast(cast("numero" as int) as varchar)'),$numerodocumento)->whereIn('cnpjtransportador',$fornecedor_documento)->select(DB::raw('cast(cast("numero" as int) as varchar) as numero, cnpjtransportador'))->get();
        
        unset($fornecedor_documento);
        unset($numerodocumento);
        $retorno = [];
        $estabelecimentos = returnEmpresasNasajonView();
        $cnpjfornecedores = [];
        $total = [
            'notas' => 0,
            'valor' => 0,
            'lancadas_quantidade' => 0,
            'lancadas_valor' => 0,
            'pedido_quantidade' => 0,
            'pedido_valor' => 0,
            'com_compras' => 0,
            'com_compras_Valor' => 0,
            'sem_compras' => 0,
            'sem_compras_valor' => 0,
        ];

        $notasimportadas->each(function ($notas) use(&$retorno,$estabelecimentos,$fields,$ctelancadas,$cnpjfornecedores,&$total){
            if(!isset($retorno[$notas->estabelecimento])){
                $retorno[$notas->estabelecimento] = [
                    'estabelecimento' => (!empty($notas->estabelecimento)) ? $estabelecimentos[(integer)$notas->estabelecimento] : 'Sem Estabelecimento',
                    'notas' => 0,
                    'valor' => 0,
                    'lancadas_quantidade' => 0,
                    'lancadas_valor' => 0,
                    'pedido_quantidade' => 0,
                    'pedido_valor' => 0,
                    'com_compras' => 0,
                    'com_compras_Valor' => 0,
                    'sem_compras' => 0,
                    'sem_compras_valor' => 0,
                    'idnota' => $notas->idnota,
                    'filter' => encrypt([
                        'estabelecimento' => ($notas->estabelecimento) ? $notas->estabelecimento : 'Sem Estabelecimento',
                        'fornecedor' => $fields['fornecedor'],
                        'natureza' => $fields['natureza'],
                        'data_inicio' => $fields['data_inicio'],
                        'data_fim' => $fields['data_fim'],
                        'frete' => (isset($fields['frete'])) ? $fields['frete'] : null,
                        'cobranca' => (isset($fields['cobranca'])) ? $fields['cobranca'] : null,
                        'lancadas' => $fields['lancadas']
                    ]),
                ];
            }

            $verificalancadas = (!empty($notas->notasEntradas)) ? true : null;
            $ctelancada = ($notas->tipo == 'cte') ? $ctelancadas->where('numero',$notas->documento_numero)->where('cnpjtransportador',$notas->fornecedor_cnpj)->first() : null;
            $verificaassociacaopedidos = (!empty($notas->notasEntradas->nota_entrada[0])) ? true : null;
            $fornecedor = $notas->fornecedor_cnpj;

            if($fields['lancadas'] == 'notas_lancadas'){

                if(!empty($verificalancadas) || !empty($ctelancada)){
                    $retorno[$notas->estabelecimento]['notas'] += 1;
                    $retorno[$notas->estabelecimento]['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;

                    if($notas->tipo == 'nfe' && empty($verificalancadas) && !in_array($fornecedor,$cnpjfornecedores)){
                        $quantidadepedido = 0;
                        $valorpedido = 0;
                        $quantidadepedido = $notas->comprasQuantidade->count();
                        $valorpedido = $notas->comprasQuantidade->sum('preco_compra');
                        $retorno[$notas->estabelecimento]['pedido_quantidade'] += $quantidadepedido;
                        $retorno[$notas->estabelecimento]['pedido_valor'] += $valorpedido ;
                        $total['pedido_quantidade'] +=  $quantidadepedido;
                        $total['pedido_valor'] += $valorpedido;
                        $cnpjfornecedores[] = $fornecedor;
                    }   
        
                    $retorno[$notas->estabelecimento]['lancadas_quantidade'] += (!empty($verificalancadas) || !empty($ctelancada)) ? 1 : 0;
                    $retorno[$notas->estabelecimento]['lancadas_valor'] += (!empty($verificalancadas)  || !empty($ctelancada)) ? $notas->valor_total_carga : 0;
        
                    $retorno[$notas->estabelecimento]['com_compras'] += (!empty($verificaassociacaopedidos) && $notas->cobranca == 'true') ? 1 : 0;
                    $retorno[$notas->estabelecimento]['com_compras_Valor'] += (!empty($verificaassociacaopedidos) && $notas->cobranca == 'true') ? $notas->valor_total_carga : 0;
        
                    $retorno[$notas->estabelecimento]['sem_compras'] += (empty($verificaassociacaopedidos) && $notas->cobranca == 'true' && !empty($verificalancadas)) ? 1 : 0;
                    $retorno[$notas->estabelecimento]['sem_compras_valor'] += (empty($verificaassociacaopedidos) && $notas->cobranca == 'true') && !empty($verificalancadas) ? $notas->valor_total_carga : 0;
        
                    $total['notas'] += 1;
                    $total['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;
                    $total['lancadas_quantidade'] += (!empty($verificalancadas) || !empty($ctelancada)) ? 1 : 0;
                    $total['lancadas_valor'] += (!empty($verificalancadas)  || !empty($ctelancada)) ? $notas->valor_total_carga : 0;
                    $total['com_compras'] += (!empty($verificaassociacaopedidos) && $notas->cobranca == 'true') ? 1 : 0;
                    $total['com_compras_Valor'] += (!empty($verificaassociacaopedidos) && $notas->cobranca == 'true') ? $notas->valor_total_carga : 0;
                    $total['sem_compras'] += (empty($verificaassociacaopedidos) && $notas->cobranca == 'true' && !empty($verificalancadas)) ? 1 : 0;
                    $total['sem_compras_valor'] += (empty($verificaassociacaopedidos) && !empty($verificalancadas) && $notas->cobranca == 'true') ? $notas->valor_total_carga : 0;
                    $total['filter'] = encrypt([
                        'estabelecimento' => $fields['estabelecimento'],
                        'fornecedor' => $fields['fornecedor'],
                        'natureza' => $fields['natureza'],
                        'data_inicio' => $fields['data_inicio'],
                        'data_fim' => $fields['data_fim'],
                        'frete' => (isset($fields['frete'])) ? $fields['frete'] : null,
                        'cobranca' => (isset($fields['cobranca'])) ? $fields['cobranca'] : null,
                        'lancadas' => $fields['lancadas']
                    ]);

                }
                
            }else if($fields['lancadas'] == 'notas_nao_lancadas'){

                if(empty($verificalancadas) && empty($ctelancada)){

                    $retorno[$notas->estabelecimento]['notas'] += 1;
                    $retorno[$notas->estabelecimento]['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;

                    if($notas->tipo == 'nfe' && empty($verificalancadas) && !in_array($fornecedor,$cnpjfornecedores)){
                        $quantidadepedido = 0;
                        $valorpedido = 0;
                        $quantidadepedido = $notas->comprasQuantidade->count();
                        $valorpedido = $notas->comprasQuantidade->sum('preco_compra');
                        $retorno[$notas->estabelecimento]['pedido_quantidade'] += $quantidadepedido;
                        $retorno[$notas->estabelecimento]['pedido_valor'] += $valorpedido ;
                        $total['pedido_quantidade'] +=  $quantidadepedido;
                        $total['pedido_valor'] += $valorpedido;
                        $cnpjfornecedores[] = $fornecedor;
                    }   
        
                    $retorno[$notas->estabelecimento]['lancadas_quantidade'] += (!empty($verificalancadas) || !empty($ctelancada)) ? 1 : 0;
                    $retorno[$notas->estabelecimento]['lancadas_valor'] += (!empty($verificalancadas)  || !empty($ctelancada)) ? $notas->valor_total_carga : 0;
        
        
                    $retorno[$notas->estabelecimento]['com_compras'] += (!empty($verificaassociacaopedidos) && $notas->cobranca == 'true') ? 1 : 0;
                    $retorno[$notas->estabelecimento]['com_compras_Valor'] += (!empty($verificaassociacaopedidos) && $notas->cobranca == 'true') ? $notas->valor_total_carga : 0;
        
                    $retorno[$notas->estabelecimento]['sem_compras'] += (empty($verificaassociacaopedidos) && $notas->cobranca == 'true' && !empty($verificalancadas)) ? 1 : 0;
                    $retorno[$notas->estabelecimento]['sem_compras_valor'] += (empty($verificaassociacaopedidos) && $notas->cobranca == 'true') && !empty($verificalancadas) ? $notas->valor_total_carga : 0;
        
                    $total['notas'] += 1;
                    $total['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;
                    $total['lancadas_quantidade'] += (!empty($verificalancadas) || !empty($ctelancada)) ? 1 : 0;
                    $total['lancadas_valor'] += (!empty($verificalancadas)  || !empty($ctelancada)) ? $notas->valor_total_carga : 0;
                    $total['com_compras'] += (!empty($verificaassociacaopedidos) && $notas->cobranca == 'true') ? 1 : 0;
                    $total['com_compras_Valor'] += (!empty($verificaassociacaopedidos) && $notas->cobranca == 'true') ? $notas->valor_total_carga : 0;
                    $total['sem_compras'] += (empty($verificaassociacaopedidos) && $notas->cobranca == 'true' && !empty($verificalancadas)) ? 1 : 0;
                    $total['sem_compras_valor'] += (empty($verificaassociacaopedidos) && !empty($verificalancadas) && $notas->cobranca == 'true') ? $notas->valor_total_carga : 0;
                    $total['filter'] = encrypt([
                        'estabelecimento' => $fields['estabelecimento'],
                        'fornecedor' => $fields['fornecedor'],
                        'natureza' => $fields['natureza'],
                        'data_inicio' => $fields['data_inicio'],
                        'data_fim' => $fields['data_fim'],
                        'frete' => (isset($fields['frete'])) ? $fields['frete'] : null,
                        'cobranca' => (isset($fields['cobranca'])) ? $fields['cobranca'] : null,
                        'lancadas' => $fields['lancadas']
                    ]);

                }

            }else{
                
                $retorno[$notas->estabelecimento]['notas'] += 1;
                $retorno[$notas->estabelecimento]['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;
                
                if($notas->tipo == 'nfe' && empty($verificalancadas) && !in_array($fornecedor,$cnpjfornecedores)){
                    $quantidadepedido = 0;
                    $valorpedido = 0;
                    $quantidadepedido = $notas->comprasQuantidade->count();
                    $valorpedido = $notas->comprasQuantidade->sum('preco_compra');
                    $retorno[$notas->estabelecimento]['pedido_quantidade'] += $quantidadepedido;
                    $retorno[$notas->estabelecimento]['pedido_valor'] += $valorpedido ;
                    $total['pedido_quantidade'] +=  $quantidadepedido;
                    $total['pedido_valor'] += $valorpedido;
                    $cnpjfornecedores[] = $fornecedor;
                }   
    
                $retorno[$notas->estabelecimento]['lancadas_quantidade'] += (!empty($verificalancadas) || !empty($ctelancada)) ? 1 : 0;
                $retorno[$notas->estabelecimento]['lancadas_valor'] += (!empty($verificalancadas)  || !empty($ctelancada)) ? $notas->valor_total_carga : 0;
    
    
                $retorno[$notas->estabelecimento]['com_compras'] += (!empty($verificaassociacaopedidos) && $notas->cobranca == 'true') ? 1 : 0;
                $retorno[$notas->estabelecimento]['com_compras_Valor'] += (!empty($verificaassociacaopedidos) && $notas->cobranca == 'true') ? $notas->valor_total_carga : 0;
    
                $retorno[$notas->estabelecimento]['sem_compras'] += (empty($verificaassociacaopedidos) && $notas->cobranca == 'true' && !empty($verificalancadas)) ? 1 : 0;
                $retorno[$notas->estabelecimento]['sem_compras_valor'] += (empty($verificaassociacaopedidos) && $notas->cobranca == 'true') && !empty($verificalancadas) ? $notas->valor_total_carga : 0;
    
                $total['notas'] += 1;
                $total['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;
                $total['lancadas_quantidade'] += (!empty($verificalancadas) || !empty($ctelancada)) ? 1 : 0;
                $total['lancadas_valor'] += (!empty($verificalancadas)  || !empty($ctelancada)) ? $notas->valor_total_carga : 0;
                $total['com_compras'] += (!empty($verificaassociacaopedidos) && $notas->cobranca == 'true') ? 1 : 0;
                $total['com_compras_Valor'] += (!empty($verificaassociacaopedidos) && $notas->cobranca == 'true') ? $notas->valor_total_carga : 0;
                $total['sem_compras'] += (empty($verificaassociacaopedidos) && $notas->cobranca == 'true' && !empty($verificalancadas)) ? 1 : 0;
                $total['sem_compras_valor'] += (empty($verificaassociacaopedidos) && !empty($verificalancadas) && $notas->cobranca == 'true') ? $notas->valor_total_carga : 0;
                $total['filter'] = encrypt([
                    'estabelecimento' => $fields['estabelecimento'],
                    'fornecedor' => $fields['fornecedor'],
                    'natureza' => $fields['natureza'],
                    'data_inicio' => $fields['data_inicio'],
                    'data_fim' => $fields['data_fim'],
                    'frete' => (isset($fields['frete'])) ? $fields['frete'] : null,
                    'cobranca' => (isset($fields['cobranca'])) ? $fields['cobranca'] : null,
                    'lancadas' => $fields['lancadas']
                ]);
            }
            
            
        });

        unset($ctelancadas);

        foreach($retorno as $key => $saida){
            $retorno[$key]['pedido_quantidade'] = ($retorno[$key]['pedido_quantidade'] > 0) ? $retorno[$key]['pedido_quantidade'] : '';
            $retorno[$key]['pedido_valor'] = ($retorno[$key]['pedido_valor'] > 0) ? parserValor($retorno[$key]['pedido_valor']) : '';
            $retorno[$key]['lancadas_quantidade'] = ($retorno[$key]['lancadas_quantidade'] > 0) ? $retorno[$key]['lancadas_quantidade'] : '';
            $retorno[$key]['lancadas_valor'] = ($retorno[$key]['lancadas_valor'] > 0) ? parserValor($retorno[$key]['lancadas_valor']) : '';
            $retorno[$key]['notas'] = ($retorno[$key]['notas'] > 0) ? $retorno[$key]['notas'] : '';
            $retorno[$key]['valor'] = ($retorno[$key]['valor'] > 0) ? parserValor($retorno[$key]['valor']) : '';
            $retorno[$key]['com_compras'] = ($retorno[$key]['com_compras'] > 0) ? $retorno[$key]['com_compras'] : '';
            $retorno[$key]['sem_compras'] = ($retorno[$key]['sem_compras'] > 0) ? $retorno[$key]['sem_compras'] : '';
            $retorno[$key]['com_compras_Valor'] = ($retorno[$key]['com_compras_Valor'] > 0) ? parserValor($retorno[$key]['com_compras_Valor']) : '';
            $retorno[$key]['sem_compras_valor'] = ($retorno[$key]['sem_compras_valor'] > 0) ? parserValor($retorno[$key]['sem_compras_valor']) : '';
        }

        $total['com_compras'] = ($total['com_compras'] > 0) ? $total['com_compras'] : '';
        $total['com_compras_Valor'] = ($total['com_compras_Valor'] > 0) ? parserValor($total['com_compras_Valor']) : '';
        $total['sem_compras'] = ($total['sem_compras'] > 0) ? $total['sem_compras'] : '';
        $total['sem_compras_valor'] = ($total['sem_compras_valor'] > 0) ? parserValor($total['sem_compras_valor']) : '';
        $total['pedido_quantidade'] = ($total['pedido_quantidade'] > 0) ? $total['pedido_quantidade'] : '';
        $total['pedido_valor'] = ($total['pedido_valor'] > 0) ? parserValor($total['pedido_valor']) : '';
        $total['lancadas_quantidade'] = ($total['lancadas_quantidade'] > 0) ? $total['lancadas_quantidade'] : '';
        $total['lancadas_valor'] = ($total['lancadas_valor'] > 0) ? parserValor($total['lancadas_valor']) : '';
        $total['notas'] = ($total['notas'] > 0) ? $total['notas'] : '';
        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => ['saida' => $retorno, 'total' => $total],
        ]);
    }

    public function modalNotas(Request $request){
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '1024M');

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

        $notaslancadas = NotasImportadasEntrada::with('itensNotas', 'cteNfe', 'cteNfe.faturamentoOnline')
            ->whereBetween('data_emissao',[Carbon::createFromFormat('d/m/Y',$fields['data_inicio'])->format('Y-m-d').' 00:00:00',Carbon::createFromFormat('d/m/Y',$fields['data_fim'])->format('Y-m-d').' 23:59:59'])
            ->with('itensNotas','cteNfe.faturamentoOnline','fornecedor','comprasQuantidade','notasEntradas','notasEntradas.nota_entrada','cfop','devolucao');

        if(isset($fields['estabelecimento']) && (!empty($fields['estabelecimento']) || $fields['estabelecimento'] === '0')){
            if($fields['estabelecimento'] == 'Sem Estabelecimento'){
                $notaslancadas->whereNull('estabelecimento');
            }else{
                $estabelecimento = str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT);
                $notaslancadas->where('estabelecimento',$estabelecimento);
            }
        }

        if(!empty($fields['fornecedor'])){
            $cnpj_fornecedor = FornecedorNasajon::where(DB::raw('CONCAT(TRIM(nome), \' - \', cnpj_cpf)'), 'ilike', '%'.$fields['fornecedor'].'%')->select('cnpj_cpf')->get();
            $notaslancadas->whereIn('fornecedor_cnpj', $cnpj_fornecedor->pluck('cnpj_cpf'));
        }
        if(!empty($fields['natureza'])){
            $notaslancadas->where(function($query) use ($fields){
                $query->where('documento_cfop',$fields['natureza'])
                ->orWhereHas('itensNotas', function($query) use ($fields){
                    $query->where('codigo_cfop',$fields['natureza']);
                });
            });
        }
        if($fields['cobranca'] == 'sem_cobranca'){
            $notaslancadas->where('cobranca',false);
        }
        if($fields['cobranca'] == 'com_cobranca' || $fields['lancadas'] == 'notas_nao_lancadas'){
            $notaslancadas->where('cobranca',true);
        }
        if($fields['frete'] == 'sem_frete'){
            $notaslancadas->where('tipo','nfe');
        }
        if($fields['frete'] == 'com_frete'){
            $notaslancadas->where('tipo','cte');
        }

        if((isset($fields['cif']) && !empty($fields['cif'])) XOR (isset($fields['fob']) && !empty($fields['fob']))){
            $notaslancadas->whereHas('cteNfe.faturamentoOnline', function($query) use($fields){

                if(isset($fields['cif']) && !empty($fields['cif'])){
                    $query->where('tipo_frete', 'CIF');
                }

                if(isset($fields['fob']) && !empty($fields['fob'])){
                    $query->where('tipo_frete', 'FOB');
                }

            });
        }
       
        $notasimportadas = $notaslancadas->get();
        $estabelecimentos = NasajonEstabelecimento::selectRaw('concat(raizcnpj,ordemcnpj) as cnpj, nomefantasia')->get();
        $fornecedor_documento =  $notasimportadas->pluck('fornecedor_cnpj');
        $numerodocumento = $notasimportadas->pluck('documento_numero');
        $ctelancadasquery = ConhecimentoTransporteNasajon::whereIn(DB::raw('cast(cast("numero" as int) as varchar)'),$numerodocumento)->whereIn('cnpjtransportador',$fornecedor_documento)->select(DB::raw('cast(cast("numero" as int) as varchar) as numero, cnpjtransportador'))->get();
       
        $retorno = [];
        $cnpjfornecedores = [];
        $total = [
            'notas' => 0,
            'valor' => 0,
            'com_compras' => 0,
            'sem_compras' => 0,
        ];

        $notasimportadas->each(function($notas) use(&$retorno,$ctelancadasquery,$cnpjfornecedores,$estabelecimentos,$fields,&$total){
            
            $cfopvalue = '';
            $lancada = '';
            $compraconfirmada = '';
            $ctequery = $ctelancadasquery->where('numero',$notas->documento_numero)->where('cnpjtransportador',$notas->fornecedor_cnpj)->first();
            $nfequery = (!empty($notas->notasEntradas)) ? true : null;
            $associacaopedido = (!empty($notas->notasEntradas->nota_entrada[0])) ? true : null;
            $fornecedor = $notas->fornecedor_cnpj;
            $devolucao = '';
            
            if(!empty($notas->itensNotas[0]) && in_array($notas->itensNotas[0]->codigo_cfop,$this->cfop_devolucao) || in_array($notas->documento_cfop,$this->cfop_devolucao)){
                if(!empty($notas->devolucao)){
                    $devolucao = 'SIM';
                }else{
                    $devolucao = 'NÃO';
                }
            }
            
            if($notas->tipo == 'nfe' && empty($nfequery) && !in_array($fornecedor,$cnpjfornecedores)){
                $cnpjfornecedores[] = $fornecedor;
            }
            if(!empty($associacaopedido) && $notas->cobranca == 'true'){
                $compraconfirmada = 'ok';
            }
            if(!empty($ctequery) ||  !empty($nfequery)){
                $lancada = 'ok';
            }

            if($devolucao == 'SIM' && empty($lancada)){
                $devolucao = 'SIM';
            }else if(!empty($devolucao)){
                $devolucao = 'NÃO';
            }else{
                $devolucao = '';
            }

            if($fields['lancadas'] == 'notas_lancadas'){

                if($lancada == 'ok'){

                    if($notas->tipo == 'cte' && !empty($notas->cfop->cfop_descricao)){
                        $cfopvalue  =  $notas->documento_cfop.' - '.$notas->cfop->cfop_descricao;
                    }else if($notas->tipo == 'nfe' && !empty($notas->cfop->cfop_descricao)){
                        foreach($notas->itensNotas as $cfop){
                            $termo = '/' . $cfop->codigo_cfop . '/';
                            if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                                $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop.' - '.$notas->cfop->cfop_descricao : ' - '.$cfop->codigo_cfop.' - '.$notas->cfop->cfop_descricao;
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
        
                    $tipo_frete = $notas->cteNfe->first()->faturamentoOnline->tipo_frete??'';

        
                    $retorno[] = [
                        'nota' => $notas->documento_numero,
                        'fornecedor' => $notas->fornecedor_nome.' - '.$notas->fornecedor_cnpj,
                        'destinatario' => $destinatario,
                        'natureza' => $cfopvalue,
                        'valor' => ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga,
                        'pedido_compra' => (!empty($notas->comprasQuantidade) && $notas->comprasQuantidade->count() > 0) ? $notas->comprasQuantidade->count() : '',
                        'data_emissao' => (!empty($notas->data_emissao)) ? parserData($notas->data_emissao) : null,
                        'id' => encrypt($notas->id),
                        'tipo' => $notas->tipo,
                        'compraconfirmada' => $compraconfirmada,
                        'nome_transportadora' => (!empty($notas->fornecedor)) ? $notas->fornecedor->nome : $notas->fornecedor_nome,
                        'estabelecimento' => $fields['estabelecimento']??'',
                        'lancada' => (!empty($lancada)) ? $lancada : '',
                        'tipo_frete' => $tipo_frete,
                        'devolucao' => $devolucao,
                        'filter' => encrypt([
                            'estabelecimento' => $fields['estabelecimento']??'',
                            'fornecedor' => $fields['fornecedor'],
                            'natureza' => $fields['natureza'],
                            'data_inicio' => $fields['data_inicio'],
                            'data_fim' => $fields['data_fim'],
                            'frete' => (isset($fields['frete'])) ? $fields['frete'] : null,
                            'cobranca' => (isset($fields['cobranca'])) ? $fields['cobranca'] : null,
                            'id_transportadora' => (!empty($notas->fornecedor_id)) ? $notas->fornecedor_id : null,
                            'nome_transportadora' => (!empty($notas->fornecedor_nome)) ? $notas->fornecedor_nome : null,
                        ]),
                    ];
                    $total['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;
                    
                }

            }else if($fields['lancadas'] == 'notas_nao_lancadas'){

                if($lancada != 'ok'){

                    if($notas->tipo == 'cte' && !empty($notas->cfop->cfop_descricao)){
                        $cfopvalue  =  $notas->documento_cfop.' - '.$notas->cfop->cfop_descricao;
                    }else if($notas->tipo == 'nfe' && !empty($notas->cfop->cfop_descricao)){
                        foreach($notas->itensNotas as $cfop){
                            $termo = '/' . $cfop->codigo_cfop . '/';
                            if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                                $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop.' - '.$notas->cfop->cfop_descricao : ' - '.$cfop->codigo_cfop.' - '.$notas->cfop->cfop_descricao;
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
        
                    $tipo_frete = $notas->cteNfe->first()->faturamentoOnline->tipo_frete??'';
        
                    $retorno[] = [
                        'nota' => $notas->documento_numero,
                        'fornecedor' => $notas->fornecedor_nome.' - '.$notas->fornecedor_cnpj,
                        'destinatario' => $destinatario,
                        'natureza' => $cfopvalue,
                        'valor' => ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga,
                        'pedido_compra' => (!empty($notas->comprasQuantidade) && $notas->comprasQuantidade->count() > 0) ? $notas->comprasQuantidade->count() : '',
                        'data_emissao' => (!empty($notas->data_emissao)) ? parserData($notas->data_emissao) : null,
                        'id' => encrypt($notas->id),
                        'tipo' => $notas->tipo,
                        'compraconfirmada' => $compraconfirmada,
                        'nome_transportadora' => (!empty($notas->fornecedor)) ? $notas->fornecedor->nome : $notas->fornecedor_nome,
                        'estabelecimento' => $fields['estabelecimento']??'',
                        'lancada' => (!empty($lancada)) ? $lancada : '',
                        'tipo_frete' => $tipo_frete,
                        'devolucao' => $devolucao,
                        'filter' => encrypt([
                            'estabelecimento' => $fields['estabelecimento']??'',
                            'fornecedor' => $fields['fornecedor'],
                            'natureza' => $fields['natureza'],
                            'data_inicio' => $fields['data_inicio'],
                            'data_fim' => $fields['data_fim'],
                            'frete' => (isset($fields['frete'])) ? $fields['frete'] : null,
                            'cobranca' => (isset($fields['cobranca'])) ? $fields['cobranca'] : null,
                            'id_transportadora' => (!empty($notas->fornecedor_id)) ? $notas->fornecedor_id : null,
                            'nome_transportadora' => (!empty($notas->fornecedor_nome)) ? $notas->fornecedor_nome : null,
                        ]),
                    ];
                    $total['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;

                }

            }else{

                if($notas->tipo == 'cte' && !empty($notas->cfop->cfop_descricao)){
                    $cfopvalue  =  $notas->documento_cfop.' - '.$notas->cfop->cfop_descricao;
                }else if($notas->tipo == 'nfe' && !empty($notas->cfop->cfop_descricao)){
                    foreach($notas->itensNotas as $cfop){
                        $termo = '/' . $cfop->codigo_cfop . '/';
                        if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                            $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop.' - '.$notas->cfop->cfop_descricao : ' - '.$cfop->codigo_cfop.' - '.$notas->cfop->cfop_descricao;
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
    
                $tipo_frete = $notas->cteNfe->first()->faturamentoOnline->tipo_frete??'';
    
                $retorno[] = [
                    'nota' => $notas->documento_numero,
                    'fornecedor' => $notas->fornecedor_nome.' - '.$notas->fornecedor_cnpj,
                    'destinatario' => $destinatario,
                    'natureza' => $cfopvalue,
                    'valor' => ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga,
                    'pedido_compra' => (!empty($notas->comprasQuantidade) && $notas->comprasQuantidade->count() > 0) ? $notas->comprasQuantidade->count() : '',
                    'data_emissao' => (!empty($notas->data_emissao)) ? parserData($notas->data_emissao) : null,
                    'id' => encrypt($notas->id),
                    'tipo' => $notas->tipo,
                    'compraconfirmada' => $compraconfirmada,
                    'nome_transportadora' => (!empty($notas->fornecedor)) ? $notas->fornecedor->nome : $notas->fornecedor_nome,
                    'estabelecimento' => $fields['estabelecimento']??'',
                    'lancada' => (!empty($lancada)) ? $lancada : '',
                    'tipo_frete' => $tipo_frete,
                    'devolucao' => $devolucao,
                    'filter' => encrypt([
                        'estabelecimento' => $fields['estabelecimento']??'',
                        'fornecedor' => $fields['fornecedor'],
                        'natureza' => $fields['natureza'],
                        'data_inicio' => $fields['data_inicio'],
                        'data_fim' => $fields['data_fim'],
                        'frete' => (isset($fields['frete'])) ? $fields['frete'] : null,
                        'cobranca' => (isset($fields['cobranca'])) ? $fields['cobranca'] : null,
                        'id_transportadora' => (!empty($notas->fornecedor_id)) ? $notas->fornecedor_id : null,
                        'nome_transportadora' => (!empty($notas->fornecedor_nome)) ? $notas->fornecedor_nome : null,
                    ]),
                ];
                $total['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;

            }

        });

        unset($ctelancadasquery);
        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        foreach($retorno as $key => $saida){
            $retorno[$key]['valor'] = ($retorno[$key]['valor'] > 0) ? parserValor($retorno[$key]['valor']) : '';
        }

        return view('programs.consulta_notas_importadas.modal.notas')->with(['retorno' => $retorno,'total' => $total]);
    }

    public function modalNotasTotal(Request $request){
        set_time_limit(500);
        ini_set('memory_limit', '1024M');
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
        $notaslancadas = NotasImportadasEntrada::whereBetween('data_emissao',[Carbon::createFromFormat('d/m/Y',$fields['data_inicio'])->format('Y-m-d').' 00:00:00',Carbon::createFromFormat('d/m/Y',$fields['data_fim'])->format('Y-m-d').' 23:59:59'])
            ->with('itensNotas','cteNfe.faturamentoOnline','fornecedor','comprasQuantidade','notasEntradas','notasEntradas.nota_entrada','cfop','devolucao');

        if(!empty($fields['estabelecimento']) || $fields['estabelecimento'] == '0'){
            if($fields['estabelecimento'] == 'Sem Estabelecimento'){
                $notaslancadas->whereNull('estabelecimento');
            }else{
                $estabelecimento = str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT);
                $notaslancadas->where('estabelecimento',$estabelecimento);
            }
        }else{
            $notaslancadas->where('estabelecimento','<>','20');
        }

        if(!empty($fields['fornecedor'])){
            $cnpj_fornecedor = FornecedorNasajon::where(DB::raw('CONCAT(TRIM(nome), \' - \', cnpj_cpf)'), 'ilike', $fields['fornecedor'])->select('cnpj_cpf')->first();
            $notaslancadas->where('fornecedor_cnpj', 'ilike', $cnpj_fornecedor->cnpj_cpf);
        }
        if(!empty($fields['natureza'])){
            $notaslancadas->where(function($query) use ($fields){
                $query->where('documento_cfop',$fields['natureza'])
                ->orWhereHas('itensNotas', function($query) use ($fields){
                    $query->where('codigo_cfop',$fields['natureza']);
                });
            });
        }
        if($fields['cobranca'] == 'sem_cobranca'){
            $notaslancadas->where('cobranca',false);
        }
        if($fields['cobranca'] == 'com_cobranca' || $fields['lancadas'] == 'notas_nao_lancadas'){
            $notaslancadas->where('cobranca',true);
        }
        if($fields['frete'] == 'sem_frete'){
            $notaslancadas->where('tipo','nfe');
        }
        if($fields['frete'] == 'com_frete'){
            $notaslancadas->where('tipo','cte');
        }
   
        $notasimportadas = $notaslancadas->get();
        $estabelecimentos = NasajonEstabelecimento::selectRaw('concat(raizcnpj,ordemcnpj) as cnpj, nomefantasia')->get();
        $fornecedor_documento =  $notasimportadas->pluck('fornecedor_cnpj');
        $numerodocumento = $notasimportadas->pluck('documento_numero');
        $ctelancadasquery = ConhecimentoTransporteNasajon::whereIn(DB::raw('cast(cast("numero" as int) as varchar)'),$numerodocumento)->whereIn('cnpjtransportador',$fornecedor_documento)->select(DB::raw('cast(cast("numero" as int) as varchar) as numero, cnpjtransportador'))->get();
        
        unset($fornecfornecedor_documentoedor_id);
        unset($numerodocumento);
        $retorno = [];
        $cnpjfornecedores = [];
        $total = [
            'notas' => 0,
            'valor' => 0,
            'com_compras' => 0,
            'sem_compras' => 0,
        ];

        $notasimportadas->each(function($notas) use (&$retorno,$ctelancadasquery,$cnpjfornecedores,$estabelecimentos,$fields,&$total){
            $cfopvalue = '';
            $lancada = '';
            $compraconfirmada = '';
            $devolucao = '';
            
            if(!empty($notas->itensNotas[0]) && in_array($notas->itensNotas[0]->codigo_cfop,$this->cfop_devolucao) || in_array($notas->documento_cfop,$this->cfop_devolucao)){
                if(!empty($notas->devolucao)){
                    $devolucao = 'SIM';
                }else{
                    $devolucao = 'NÃO';
                }
            }

            $nfequery = (!empty($notas->notasEntradas)) ? true : null;
            $ctelancada = ($notas->tipo == 'cte') ? $ctelancadasquery->where('numero',$notas->documento_numero)->where('cnpjtransportador',$notas->fornecedor_cnpj)->first() : null;
            $associacaopedido = (!empty($notas->notasEntradas->nota_entrada[0])) ? true : null;
            $fornecedor = $notas->fornecedor_cnpj;

            if($notas->tipo == 'nfe' && empty($nfequery) && !in_array($fornecedor,$cnpjfornecedores)){
                $cnpjfornecedores[] = $fornecedor;
            }
            if(!empty($associacaopedido) && $notas->cobranca == 'true'){
                $compraconfirmada = 'ok';
            }
            if(!empty($ctelancada) ||  !empty($nfequery)){
                $lancada = 'ok';
            }
            
            if($devolucao == 'SIM' && empty($lancada)){
                $devolucao = 'SIM';
            }else if(!empty($devolucao)){
                $devolucao = 'NÃO';
            }else{
                $devolucao = '';
            }

            if($fields['lancadas'] == 'notas_lancadas'){

                if($lancada == 'ok'){

                    if($notas->tipo == 'cte' && !empty($notas->cfop->cfop_descricao)){
                        $cfopvalue  =  $notas->documento_cfop.' - '.$notas->cfop->cfop_descricao;
                    }else if($notas->tipo == 'nfe' && !empty($notas->cfop->cfop_descricao)){
                        foreach($notas->itensNotas as $cfop){
                            $termo = '/' . $cfop->codigo_cfop . '/';
                            if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                                $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop.' - '.$notas->cfop->cfop_descricao : ' - '.$cfop->codigo_cfop.' - '.$notas->cfop->cfop_descricao;
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
        
                    $tipo_frete = $notas->cteNfe->first()->faturamentoOnline->tipo_frete??'';
        
                    $retorno[] = [
                        'nota' => $notas->documento_numero,
                        'fornecedor' => $notas->fornecedor_nome.' - '.$notas->fornecedor_cnpj,
                        'destinatario' => $destinatario,
                        'natureza' => $cfopvalue,
                        'valor' => ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga,
                        'pedido_compra' => (!empty($notas->comprasQuantidade) && $notas->comprasQuantidade->count() > 0) ? $notas->comprasQuantidade->count() : '',
                        'data_emissao' => (!empty($notas->data_emissao)) ? parserData($notas->data_emissao) : null,
                        'id' => encrypt($notas->id),
                        'tipo' => $notas->tipo,
                        'compraconfirmada' => $compraconfirmada,
                        'nome_transportadora' => (!empty($notas->fornecedor)) ? $notas->fornecedor->nome : $notas->fornecedor_nome,
                        'estabelecimento' => $fields['estabelecimento']??'',
                        'lancada' => (!empty($lancada)) ? $lancada : '',
                        'tipo_frete' => $tipo_frete,
                        'devolucao' => $devolucao,
                        'filter' => encrypt([
                            'estabelecimento' => $fields['estabelecimento']??'',
                            'fornecedor' => $fields['fornecedor'],
                            'natureza' => $fields['natureza'],
                            'data_inicio' => $fields['data_inicio'],
                            'data_fim' => $fields['data_fim'],
                            'frete' => (isset($fields['frete'])) ? $fields['frete'] : null,
                            'cobranca' => (isset($fields['cobranca'])) ? $fields['cobranca'] : null,
                            'id_transportadora' => (!empty($notas->fornecedor_id)) ? $notas->fornecedor_id : null,
                            'nome_transportadora' => (!empty($notas->fornecedor_nome)) ? $notas->fornecedor_nome : null,
                        ]),
                    ];
                    $total['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;
                    
                }

            }else if($fields['lancadas'] == 'notas_nao_lancadas'){

                if($lancada != 'ok'){

                    if($notas->tipo == 'cte' && !empty($notas->cfop->cfop_descricao)){
                        $cfopvalue  =  $notas->documento_cfop.' - '.$notas->cfop->cfop_descricao;
                    }else if($notas->tipo == 'nfe' && !empty($notas->cfop->cfop_descricao)){
                        foreach($notas->itensNotas as $cfop){
                            $termo = '/' . $cfop->codigo_cfop . '/';
                            if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                                $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop.' - '.$notas->cfop->cfop_descricao : ' - '.$cfop->codigo_cfop.' - '.$notas->cfop->cfop_descricao;
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
        
                    $tipo_frete = $notas->cteNfe->first()->faturamentoOnline->tipo_frete??'';
        
                    $retorno[] = [
                        'nota' => $notas->documento_numero,
                        'fornecedor' => $notas->fornecedor_nome.' - '.$notas->fornecedor_cnpj,
                        'destinatario' => $destinatario,
                        'natureza' => $cfopvalue,
                        'valor' => ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga,
                        'pedido_compra' => (!empty($notas->comprasQuantidade) && $notas->comprasQuantidade->count() > 0) ? $notas->comprasQuantidade->count() : '',
                        'data_emissao' => (!empty($notas->data_emissao)) ? parserData($notas->data_emissao) : null,
                        'id' => encrypt($notas->id),
                        'tipo' => $notas->tipo,
                        'compraconfirmada' => $compraconfirmada,
                        'nome_transportadora' => (!empty($notas->fornecedor)) ? $notas->fornecedor->nome : $notas->fornecedor_nome,
                        'estabelecimento' => $fields['estabelecimento']??'',
                        'lancada' => (!empty($lancada)) ? $lancada : '',
                        'tipo_frete' => $tipo_frete,
                        'devolucao' => $devolucao,
                        'filter' => encrypt([
                            'estabelecimento' => $fields['estabelecimento']??'',
                            'fornecedor' => $fields['fornecedor'],
                            'natureza' => $fields['natureza'],
                            'data_inicio' => $fields['data_inicio'],
                            'data_fim' => $fields['data_fim'],
                            'frete' => (isset($fields['frete'])) ? $fields['frete'] : null,
                            'cobranca' => (isset($fields['cobranca'])) ? $fields['cobranca'] : null,
                            'id_transportadora' => (!empty($notas->fornecedor_id)) ? $notas->fornecedor_id : null,
                            'nome_transportadora' => (!empty($notas->fornecedor_nome)) ? $notas->fornecedor_nome : null,
                        ]),
                    ];
                    $total['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;

                }

            }else{

                if($notas->tipo == 'cte' && !empty($notas->cfop->cfop_descricao)){
                    $cfopvalue  =  $notas->documento_cfop.' - '.$notas->cfop->cfop_descricao;
                }else if($notas->tipo == 'nfe' && !empty($notas->cfop->cfop_descricao)){
                    foreach($notas->itensNotas as $cfop){
                        $termo = '/' . $cfop->codigo_cfop . '/';
                        if(empty($cfopvalue) || !preg_match($termo, $cfopvalue)){
                            $cfopvalue  .= (empty($cfopvalue)) ? $cfop->codigo_cfop.' - '.$notas->cfop->cfop_descricao : ' - '.$cfop->codigo_cfop.' - '.$notas->cfop->cfop_descricao;
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
    
                $tipo_frete = $notas->cteNfe->first()->faturamentoOnline->tipo_frete??'';
    
                $retorno[] = [
                    'nota' => $notas->documento_numero,
                    'fornecedor' => $notas->fornecedor_nome.' - '.$notas->fornecedor_cnpj,
                    'destinatario' => $destinatario,
                    'natureza' => $cfopvalue,
                    'valor' => ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga,
                    'pedido_compra' => (!empty($notas->comprasQuantidade) && $notas->comprasQuantidade->count() > 0) ? $notas->comprasQuantidade->count() : '',
                    'data_emissao' => (!empty($notas->data_emissao)) ? parserData($notas->data_emissao) : null,
                    'id' => encrypt($notas->id),
                    'tipo' => $notas->tipo,
                    'compraconfirmada' => $compraconfirmada,
                    'nome_transportadora' => (!empty($notas->fornecedor)) ? $notas->fornecedor->nome : $notas->fornecedor_nome,
                    'estabelecimento' => $fields['estabelecimento']??'',
                    'lancada' => (!empty($lancada)) ? $lancada : '',
                    'tipo_frete' => $tipo_frete,
                    'devolucao' => $devolucao,
                    'filter' => encrypt([
                        'estabelecimento' => $fields['estabelecimento']??'',
                        'fornecedor' => $fields['fornecedor'],
                        'natureza' => $fields['natureza'],
                        'data_inicio' => $fields['data_inicio'],
                        'data_fim' => $fields['data_fim'],
                        'frete' => (isset($fields['frete'])) ? $fields['frete'] : null,
                        'cobranca' => (isset($fields['cobranca'])) ? $fields['cobranca'] : null,
                        'id_transportadora' => (!empty($notas->fornecedor_id)) ? $notas->fornecedor_id : null,
                        'nome_transportadora' => (!empty($notas->fornecedor_nome)) ? $notas->fornecedor_nome : null,
                    ]),
                ];
                $total['valor'] += ($notas->tipo == 'cte') ? $notas->valor_frete : $notas->valor_total_carga;

            }
        });

        unset($ctelancadas);

        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        foreach($retorno as $key => $saida){
            $retorno[$key]['valor'] = ($retorno[$key]['valor'] > 0) ? parserValor($retorno[$key]['valor']) : '';
        }

        return view('programs.consulta_notas_importadas.modal.notas')->with(['retorno' => $retorno,'total' => $total]);
    }

    public function exibirNotaImportada(Request $request){
        $fields = $request->only('id');

        try{
            $id = decrypt($fields['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }

        $tipopagamento = [
            '01' => 'Dinheiro',
            '02' => 'Cheque',
            '03' => 'Cartão de Crédito',
            '04' => 'Cartão de Débito',
            '05' => 'Crédito Loja',
            '10' => 'Vale Alimentação',
            '11' => 'Vale Refeição',
            '12' => 'Vale Presente',
            '13' => 'Vale Combustível',
            '14' => 'Duplicata Mercantil',
            '15' => 'Boleto Bancário',
            '90' => 'Sem Pagamento',
            '99' => 'Outros',
        ];

        $descricaocfop = CfopNasajon::select('cfop_codigo','cfop_descricao')->get();
        $estabelecimentos = returnEmpresasNasajonView();
        $faturas_array = [];
        $total_fatura = 0;
        $nota = NotasImportadasEntrada::with('itensNotas','notasEntradas','itensNotas.produtoEspecicacoes','faturas.duplicatas')
        ->where('id',$id)->first();
        
        if(empty($nota)){
            return response()->json([
                'status' => 'error',
                'message' => 'Nota não encontrada!',
                'error' => '',
                'response' => '',
            ]);
        }
        
        if(!empty($nota->faturas->duplicatas)){
            foreach($nota->faturas->duplicatas as $duplicata){
                $faturas_array[] = [
                    'fatura' => $nota->faturas->fatura,
                    'duplicata' => $duplicata->duplicata,
                    'vencimento' => parserData($duplicata->vencimento),
                    'valor' => parserValor($duplicata->valor),
                ];
                $total_fatura += $duplicata->valor;
            }

            $total_fatura = parserValor($total_fatura);
        }

        $header_nota_array = [
            "nota_numero" => $nota['documento_numero'],
            "estabelecimento" => (isset($nota['estabelecimento'])) ? $estabelecimentos[(integer)$nota['estabelecimento']] : '',
            "origem" => 'apinasajon',
            "natureza" => (isset($descricaocfop->where('cfop_codigo',$nota->itensNotas[0]['codigo_cfop'])->first()->cfop_descricao)) ? $descricaocfop->where('cfop_codigo',$nota->itensNotas[0]['codigo_cfop'])->first()->cfop_descricao : '',
            "nome" => $nota['destinatario_nome'],
            "cpf_cnpj" => $nota['destinatario_cpf_cnpj'],
            'data_emissao' => parserData($nota['data_emissao']),
            'data_entrada' => null,
            'forma_pagamento' => (array_key_exists($nota['pagamento_tipo'],$tipopagamento)) ? $tipopagamento[$nota['pagamento_tipo']] : null,
            'pagamento_valor' => parserValor($nota['pagamento_valor']),
            'chave' => $nota['documento_chave'],

            'valor_icms_st' => parserValor($nota['icms_cst']),

            'valor_total_frete' => parserValor($nota['valor_frete']),
            'valor_seguro' => parserValor($nota['valor_seguro']),
            'valor_desconto' => parserValor($nota['valor_desconto']),
            'valor_outras_despesas' => parserValor($nota['valor_outros']),

            "valor_total" => parserValor($nota['valor_total_carga']),

            'transportadora_nome' => $nota['fornecedor_nome'],
            'transportadora_cnpj' => $nota['fornecedor_cnpj'],

            'qtd_volumes' => $nota['quantidade'],
            'peso_liquido' => parserQtd3CasaDecimais($nota['peso_liquido']) . ' KG',

            'valor_aframm' => '',
            'valor_ii' => '',
            'valor_pis' => '',
            'valor_cofins' => '',
        ];
        
        $itens_array = [];
        $valortotal = 0;

        foreach ($nota->itensNotas as $value){
            $itens_array_temp = [
                "codigo" => $value->codigo_produto,
                "descricao" => $value->produto_nome,
                "ncm" => $value->codigo_ncm,
                "cst" => ($value->icms_tributacao_cts > 0) ? parserValor($value->icms_tributacao_cts) : '',
                "cfo" => $value->codigo_cfop,
                "un" => $value->comercial_unidade,
                "quantidade" => parserValor($value->comercial_quantidade),
                "preco_unitario" => ($value->comercial_valor_unitario > 0) ? parserValor($value->comercial_valor_unitario) : '',
                "valor_total" => ($value->valor_total > 0) ? parserValor($value->valor_total) : '',
                "base_icms" => ($value->icms_modalidade_bc > 0) ? parserValor($value->icms_modalidade_bc) : '',
                "valor_icms" => ($value->icms_valor > 0) ? parserValor($value->icms_valor) : '',
                "valor_ipi" => '',
                "aliquota_icms" => ($value->icms_aliquota > 0) ? parserValor($value->icms_aliquota) : '',
                "aliquota_ipi" => '',
                "outras_despesas" => '',
                "aframm" => '',
                "pis" => '',
                "cofins" => '',
                "valor_ii" => '',
            ];
            $valortotal += $value->valor_total;
            $itens_array[] = $itens_array_temp;

        }

        $header_nota_array['valor_total'] = parserValor($valortotal);
        
        return view('programs.notas_entradas_nasajon.modal.dialog')->with(['header_nota_array' => $header_nota_array, 'itens_array' => $itens_array,'faturas' => $faturas_array,'total_fatura' => $total_fatura]);
    }

    public function exibirCfop(Request $request){
        $fields = $request->only('id');

        try{
            $id = decrypt($fields['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }

        $descricaocfop = CfopNasajon::select('cfop_codigo','cfop_descricao')->get();
        $estabelecimentos = returnEmpresasNasajonView();
        $nota = NotasImportadasEntrada::with('itensNotas','notasEntradas','itensNotas.produtoEspecicacoes','cteNfe.NotasEntradasNasajon')
        ->where('id',$id)->first();

        if(empty($nota)){
            return response()->json([
                'status' => 'error',
                'message' => 'Nota não encontrada!',
                'error' => '',
                'response' => '',
            ]);
        }

        $header_nota_array = [
            "nota_numero" => $nota['documento_numero'],
            "estabelecimento" => (isset($nota['estabelecimento'])) ? $estabelecimentos[(integer)$nota['estabelecimento']] : '',
            "origem" => 'apinasajon',
            "natureza" => (isset($descricaocfop->where('cfop_codigo',$nota->documento_cfop)->first()->cfop_descricao)) ? $descricaocfop->where('cfop_codigo',$nota->documento_cfop)->first()->cfop_descricao : '',
            "emitente" => $nota['fornecedor_nome'],
            "emitente_cpf_cnpj" => $nota['fornecedor_cnpj'],
            'data_emissao' => parserData($nota['data_emissao']),

            'valor_icms_st' => parserValor($nota['icms_cst']),
            'valor_icms_base_calculo' => parserValor($nota['icms_base_calculo']),
            'valor_icms_aliquota' => parserValor($nota['icms_aliquota']),
            'valor_icms_valor' => parserValor($nota['icms_valor']),

            'valor_total_frete' => parserValor($nota['valor_frete']),
            'valor_seguro' => parserValor($nota['valor_seguro']),
            'valor_desconto' => parserValor($nota['valor_desconto']),
            'valor_outras_despesas' => parserValor($nota['valor_outros']),
            'valor_ipi' => parserValor($nota['valor_ipi']),
            'valor_ipi_devolucao' => parserValor($nota['valor_ipi_devolucao']),
            'valor_pis' => parserValor($nota['valor_pis']),
            'valor_cofins' => parserValor($nota['valor_cofins']),
            'valor_total_tributos' => parserValor($nota['valor_total_tributos']),
            'valor_etc' => parserValor($nota['valor_etc']),
            'valor_despacho' => parserValor($nota['valor_despacho']),
            'valor_pedagio' => parserValor($nota['valor_pedagio']),
            'valor_gris' => parserValor($nota['valor_gris']),
            'valor_trt' => parserValor($nota['valor_trt']),
            'valor_tas' => parserValor($nota['valor_tas']),

            'unidade_base_calculo' => parserValor($nota['unidade_base_calculo']),
            'unidade_etc' => parserValor($nota['unidade_etc']),
            'unidade_peso_declarado' => parserValor($nota['unidade_base_calculo']),
            'unidade_peso_real' => parserValor($nota['unidade_etc']),

            'peso_declarado' => parserValor($nota['peso_declarado']),
            'peso_real' => parserValor($nota['peso_real']),
            'peso_liquido' => parserQtd3CasaDecimais($nota['peso_liquido']) . ' KG',
            'peso_bruto' => ($nota['peso_bruto'] > 0) ? parserQtd3CasaDecimais($nota['peso_bruto']) . ' KG' : parserQtd3CasaDecimais($nota['peso_base_calculo']) . ' KG',

            'remetente' => $nota['remetente_nome'],
            'remetente_cnpj' => $nota['remetente_cnpj'],

            'destinatario_nome' => $nota['destinatario_nome'],
            'destinatario_cnpj' => $nota['destinatario_cpf_cnpj'],

            'expedidor' => $nota['expedidor_nome'],
            'expedidor_cnpj' => $nota['expedidor_cnpj'],

            "frete_peso" => parserValor($nota['frete_peso']),
            "valor_total_servico" => parserValor($nota['valor_total_servico']),
            "valor_total_carga" => parserValor($nota['valor_total_carga']),

            "valor_total" => parserValor($nota['valor_total_carga']),

            'produto_predominante' => $nota['produto_predominante'],
            'caracteristica_carga' => $nota['caracteristica_carga'],

            'qtd_volumes' => parserQtd3CasaDecimais($nota['volume']),

            'valor_aframm' => '',
            'valor_ii' => '',
            'valor_pis' => '',
            'valor_cofins' => '',
			
        ];

        if(!empty($nota->notasEntradas->condicaoDePagamento)){
            $header_nota_array['forma_pagamento'] = $nota->notasEntradas->condicaoDePagamento->formapagamento_descricao;

            if($nota->notasEntradas->condicaoDePagamento->parcelas_documento == 1){
                $header_nota_array['forma_pagamento'] .= ' à vista';
            }else{
                $header_nota_array['forma_pagamento'] .= ' x' . $nota->notasEntradas->condicaoDePagamento->parcelas_documento . ' parcelas';
            }
        }else{
            $header_nota_array['forma_pagamento'] = '';
        }

        $valortotal = 0;

        $header_nota_array['valor_total'] = parserValor($valortotal);

        $notasvinculadas = $nota->cteNfe;
        if(!empty($notasvinculadas)) {
            $arraynotas = [];
            foreach ($notasvinculadas as $notas) {
                $arraynotas[] = $notas->notas_importadas_entradas_id_nfe;
            }
        }

        $relacao_notas = $nota->cteNfe;
        $chaves_nfe = [];
        $id_notas = [];

        foreach($relacao_notas as $notas){
            $id_notas[] = $notas->notas_importadas_entradas_id_nfe;
        }

        foreach($relacao_notas as $notas){
            $chaves_nfe[] = $notas->chave;
        }

        $notas_referencias = NotasImportadasEntrada::whereIn('documento_chave',$chaves_nfe)->get();
        $notasmn = NotasNasajon::whereIn('id', $id_notas)->get();
        $notas_devolucao = NotasImportadasEntradaDevolucaoReferencia::with('notasEntradaImportadas')->whereIn('chave_numero_nota',$chaves_nfe)->get();

        $itens_array = [];
        $valortotal = [
            'valor_nota' => 0,
            'peso_nota' => 0,
            'peso_real' => 0,
            'valor_frete' => 0,
            'pedagio' => 0,
            'gris' => 0,
        ];

        if(!empty($notasmn->first())){
            foreach ($notasmn as $value) {
                $itens_array_temp = [
                    "cliente" => $value->cliente_nome,
                    "nota" => $value->numero,
                    "valor_nota" => ($value->valor > 0) ? parserValor($value->valor) : '',
                    "peso_nota" => ($value->pesoliquido > 0) ? parserValor($value->pesoliquido) : '',
                    "peso_real" => parserValor($nota['peso_real']),
                    "valor_frete" => ($value->frete > 0) ? parserValor($value->frete) : '',
                    "pedagio" => ($nota['valor_pedagio'] > 0) ? parserValor($nota['valor_pedagio']) : '',
                    "gris" => ($nota['valor_gris'] > 0) ? parserValor($nota['valor_gris']) : '',
                    "id" => $value->id,
                    "tipo" => 'nasajon',
					"outras_despesas" => '',
					"aframm" => '',
					"pis" => '',
					"cofins" => '',
					"valor_ii" => '',
                ];
                $valortotal['valor_nota'] += ($value->valor > 0) ? $value->valor : 0;
                $valortotal['peso_nota'] += ($value->pesoliquido > 0) ? $value->pesoliquido : 0;
                $valortotal['peso_real'] += 0;
                $valortotal['valor_frete'] += ($value->frete > 0) ? $value->frete : 0;
                $valortotal['pedagio'] = '';
                $valortotal['gris'] = '';
                $itens_array[] = $itens_array_temp;
            }
            $valortotal['valor_nota'] = ($valortotal['valor_nota'] > 0) ? parserValor($valortotal['valor_nota']) : '';
            $valortotal['peso_nota'] = ($valortotal['peso_nota'] > 0) ? parserValor($valortotal['peso_nota']) : '';
            $valortotal['peso_real'] = '';
            $valortotal['valor_frete'] = ($valortotal['valor_frete'] > 0) ? parserValor($valortotal['valor_frete']) : '';

        }else if(!empty($notas_referencias->first())){
            
            foreach ($notas_referencias as $value) {
                $itens_array_temp = [
                    "cliente" => $value->destinatario_nome,
                    "nota" => $value->documento_numero,
                    "valor_nota" => ($value->valor_total_carga > 0) ? parserValor($value->valor_total_carga) : '',
                    "peso_nota" => ($value->peso_liquido > 0) ? parserValor($value->peso_liquido) : '',
                    "peso_real" => ($value->peso_bruto > 0) ? parserValor($value->peso_bruto) : '',
                    "valor_frete" => ($value->valor_frete > 0) ? parserValor($value->valor_frete) : '',
                    "pedagio" => ($value->valor_pedagio > 0) ? parserValor($value->valor_pedagio) : '',
                    "gris" => ($value->valor_gris > 0) ? parserValor($value->valor_gris) : '',
                    "id" => encrypt($value->id),
                    "tipo" => 'importada',
					"outras_despesas" => ($value->valor_outros > 0) ? parserValor($value->valor_outros) : '',
					"aframm" => '',
					"pis" => '',
					"cofins" => '',
					"valor_ii" => '',
                ];

                $valortotal['valor_nota'] += ($value->valor_total_carga > 0) ? $value->valor_total_carga : 0;
                $valortotal['peso_nota'] += ($value->peso_liquido > 0) ? $value->peso_liquido : 0;
                $valortotal['peso_real'] += ($value->peso_bruto > 0) ? $value->peso_bruto : 0;
                $valortotal['valor_frete'] += ($value->valor_frete > 0) ? $value->valor_frete : 0;
                $valortotal['pedagio'] += ($value->valor_pedagio > 0) ? $value->valor_pedagio : 0;
                $valortotal['gris'] += ($value->valor_gris > 0) ? $value->valor_gris : 0;

                $itens_array[] = $itens_array_temp;
            }

            $valortotal['valor_nota'] = ($valortotal['valor_nota'] > 0) ? parserValor($valortotal['valor_nota']) : '';
            $valortotal['peso_nota'] = ($valortotal['peso_nota'] > 0) ? parserValor($valortotal['peso_nota']) : '';
            $valortotal['peso_real'] = ($valortotal['peso_real'] > 0) ? parserValor($valortotal['peso_real']) : '';
            $valortotal['valor_frete'] = ($valortotal['valor_frete'] > 0) ? parserValor($valortotal['valor_frete']) : '';
            $valortotal['pedagio'] = ($valortotal['pedagio'] > 0) ? parserValor($valortotal['pedagio']) : '';
            $valortotal['gris'] = ($valortotal['gris'] > 0) ? parserValor($valortotal['gris']) : '';

        }else if(!empty($notas_devolucao->first())){
            $notas_devolucao->each(function($query) use (&$itens_array,&$valortotal){
                $itens_array_temp = [
                    "cliente" => $query->notasEntradaImportadas->destinatario_nome,
                    "nota" => $query->notasEntradaImportadas->documento_numero,
                    "valor_nota" => ($query->notasEntradaImportadas->valor_total_carga > 0) ? parserValor($query->notasEntradaImportadas->valor_total_carga) : '',
                    "peso_nota" => ($query->notasEntradaImportadas->peso_liquido > 0) ? parserValor($query->notasEntradaImportadas->peso_liquido) : '',
                    "peso_real" => ($query->notasEntradaImportadas->peso_bruto > 0) ? parserValor($query->notasEntradaImportadas->peso_bruto) : '',
                    "valor_frete" => ($query->notasEntradaImportadas->valor_frete > 0) ? parserValor($query->notasEntradaImportadas->valor_frete) : '',
                    "pedagio" => ($query->notasEntradaImportadas->valor_pedagio > 0) ? parserValor($query->notasEntradaImportadas->valor_pedagio) : '',
                    "gris" => ($query->notasEntradaImportadas->valor_gris > 0) ? parserValor($query->notasEntradaImportadas->valor_gris) : '',
                    "id" => encrypt($query->notasEntradaImportadas->id),
                    "tipo" => 'importada',
                    "outras_despesas" => ($query->notasEntradaImportadas->valor_outros > 0) ? parserValor($query->notasEntradaImportadas->valor_outros) : '',
                    "aframm" => '',
                    "pis" => '',
                    "cofins" => '',
                    "valor_ii" => '',
                ];
                $valortotal['valor_nota'] += ($query->notasEntradaImportadas->valor_total_carga > 0) ? $query->notasEntradaImportadas->valor_total_carga : 0;
                $valortotal['peso_nota'] += ($query->notasEntradaImportadas->peso_liquido > 0) ? $query->notasEntradaImportadas->peso_liquido : 0;
                $valortotal['peso_real'] += ($query->notasEntradaImportadas->peso_bruto > 0) ? $query->notasEntradaImportadas->peso_bruto : 0;
                $valortotal['valor_frete'] += ($query->notasEntradaImportadas->valor_frete > 0) ? $query->notasEntradaImportadas->valor_frete : 0;
                $valortotal['pedagio'] += ($query->notasEntradaImportadas->valor_pedagio > 0) ? $query->notasEntradaImportadas->valor_pedagio : 0;
                $valortotal['gris'] += ($query->notasEntradaImportadas->valor_gris > 0) ? $query->notasEntradaImportadas->valor_gris : 0;
                $itens_array[] = $itens_array_temp;
            });
            
            $valortotal['valor_nota'] = ($valortotal['valor_nota'] > 0) ? parserValor($valortotal['valor_nota']) : '';
            $valortotal['peso_nota'] = ($valortotal['peso_nota'] > 0) ? parserValor($valortotal['peso_nota']) : '';
            $valortotal['peso_real'] = ($valortotal['peso_real'] > 0) ? parserValor($valortotal['peso_real']) : '';
            $valortotal['valor_frete'] = ($valortotal['valor_frete'] > 0) ? parserValor($valortotal['valor_frete']) : '';
            $valortotal['pedagio'] = ($valortotal['pedagio'] > 0) ? parserValor($valortotal['pedagio']) : '';
            $valortotal['gris'] = ($valortotal['gris'] > 0) ? parserValor($valortotal['gris']) : '';
        }else if (!empty($nota->cteNfe[0]->NotasEntradasNasajon)){
            $nota->cteNfe->each(function($query) use (&$itens_array,&$valortotal){
                foreach($query->NotasEntradasNasajon as $nota){
                    $itens_array_temp = [
                        "cliente" => $nota['Nome do Fornecedor'],
                        "nota" => $nota['Número do Documento'],
                        "valor_nota" => ($nota['Valor do Documento'] > 0) ? parserValor($nota['Valor do Documento']) : '',
                        "peso_nota" => ($nota['Peso Líquido'] > 0) ? parserValor($nota['Peso Líquido']) : '',
                        "peso_real" => '',
                        "valor_frete" => ($nota['Valor Frete'] > 0) ? parserValor($nota['Valor Frete']) : '',
                        "pedagio" => '',
                        "gris" => '',
                        "id" => $nota['Identificador Documento'],
                        "tipo" => 'entrada',
                        "outras_despesas" => ($nota['Valor Outras Despesas'] > 0) ? parserValor($nota['Valor Outras Despesas']) : '',
                        "aframm" => '',
                        "pis" => '',
                        "cofins" => '',
                        "valor_ii" => '',
                    ];

                    $valortotal['valor_nota'] += ($nota['Valor do Documento'] > 0) ? $nota['Valor do Documento'] : 0;
                    $valortotal['peso_nota'] += ($nota['Peso Líquido'] > 0) ? $nota['Peso Líquido'] : 0;
                    $valortotal['peso_real'] += 0;
                    $valortotal['valor_frete'] += ($nota['Valor Frete'] > 0) ? $nota['Valor Frete'] : 0;
                    $valortotal['pedagio'] += 0;
                    $valortotal['gris'] += 0;
                    $itens_array[] = $itens_array_temp;
                }
            });
            
            $valortotal['valor_nota'] = ($valortotal['valor_nota'] > 0) ? parserValor($valortotal['valor_nota']) : '';
            $valortotal['peso_nota'] = ($valortotal['peso_nota'] > 0) ? parserValor($valortotal['peso_nota']) : '';
            $valortotal['peso_real'] = ($valortotal['peso_real'] > 0) ? parserValor($valortotal['peso_real']) : '';
            $valortotal['valor_frete'] = ($valortotal['valor_frete'] > 0) ? parserValor($valortotal['valor_frete']) : '';
            $valortotal['pedagio'] = ($valortotal['pedagio'] > 0) ? parserValor($valortotal['pedagio']) : '';
            $valortotal['gris'] = ($valortotal['gris'] > 0) ? parserValor($valortotal['gris']) : '';
        }

        return view('programs.notas_entradas_nasajon.modal.dialog_cte')->with(['header_nota_array' => $header_nota_array, 'itens_array' => $itens_array, 'total' => $valortotal]);
    }

    public function downloadXML(Request $request){
        $id = $request->id;
        $xml_chave = "";
        try{
            $xmlid = decrypt($id);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }
        $query = NotasImportadasEntrada::find($xmlid);
        $xml = $query->xml;
        $xml_chave = $query->documento_chave;

        return response()->make($xml, 200, [
            'Pragma' => 'public',
            'Expires' => '0',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Type' => 'text/xml',
            'Content-Disposition' => 'attachment;  filename="xml_'. $xml_chave .'.xml"',
            'Content-Transfer-Encoding' => 'binary',
        ]);
    }

    public function verificaCobrancaCte($inicio_periodo,$fim_periodo){
        set_time_limit(1200);
        $notas_importadas = NotasImportadasEntrada::whereBetween('data_emissao',[$inicio_periodo->format('Y-m-d 00:00:00'),$fim_periodo->format('Y-m-d 23:59:59')])
        ->where('tipo','cte')
        ->select('id','documento_chave','estabelecimento','documento_numero','fornecedor_cnpj')
        ->get();

        $notas_importadas->each(function ($notas){
            $cobranca = false;

            $conhecimento_transporte_nasajon = ConhecimentoTransporteNasajon::where(DB::raw('cast(cast("numero" as int) as varchar)'),$notas->documento_numero)->where('cnpjtransportador',$notas->fornecedor_cnpj)->first();
            $notas_entradas_nasajon = NotasEntradasNasajon::where('Chave NE',$notas->documento_chave)->where('Estabelecimento',$notas->estabelecimento)->first();
            
            if(!empty($conhecimento_transporte_nasajon) || !empty($notas_entradas_nasajon)){
                $cobranca = true;
            }

            unset($notas_entradas_nasajon);
            unset($conhecimento_transporte_nasajon);

            $notas_importadas = NotasImportadasEntrada::find($notas->id);
            $notas_importadas->cobranca = $cobranca;
            
            if(!$notas_importadas->save()){
                throw new Exception($notas_importadas->save());
            }
        });
    }

    public function gerarPdf(Request $request){
        $id = $request->only(['id']);
        
        try {
            $id = decrypt($id['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $query = NotasImportadasEntrada::with(['destinatario','itensNotas','fornecedor','transportadora','itensNotas.produtoEspecicacoes'])->find($id);

        if(empty($query)){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Nota não encontrada!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $naturea_operacao = CfopNasajon::where('cfop_codigo',$query->documento_cfop)->first();
        
        $dados = [
            'itens' => [],
            'destinatario' => [],
            'emitente' => [],
            'natureza_operacao' => '',
            'total_produtos' => parserValor($query->valor_total_carga)
        ];

        $dados['nota'] = [
            'tipo' => $query->tipo,
            'numero' => $query->documento_numero,
            'serie' => $query->documento_serie,
            'emissao' => parserData($query->data_emissao),
            'baseicms' => parserValor($query->icms_base_calculo),
            'basesubst' => 0,
            'valoricms' => parserValor($query->icms_valor),
            'seguro' => parserValor($query->valor_seguro),
            'outras' => parserValor($query->valor_outros),
            'frete' => parserValor($query->valor_frete),
            'valoricmsst' => parserValor($query->icms_cst),
            'total_produto' => parserValor($query->valor_total_carga),
            'total_desconto' => parserValor($query->valor_desconto),
            'valor' => parserValor($query->valor_total_servico),
            'ipi' => parserValor($query->valor_ipi),
            'chave' => str_replace('CTe','',$query->documento_chave),
            'informacao_complementar' => $query->informacao_complementar
        ];

        $dados['destinatario'] = [
            'nome' => $query->destinatario_nome,
            'cpf_cnpj' => $query->destinatario_cpf_cnpj,
            'endereco' => (!empty($query->destinatario)) ? $query->destinatario->tipologradouro . ' ' . $query->destinatario->logradouro . ' ' .$query->destinatario->numero : '',
            'bairro' => (!empty($query->destinatario)) ? $query->destinatario->bairro : '',
            'cep' => (!empty($query->destinatario)) ? $query->destinatario->cep : '',
            'municipio' => (!empty($query->destinatario)) ? $query->destinatario->cidade : '',
            'telefone' => (!empty($query->destinatario)) ? $query->destinatario->telefones : '',
            'uf' => (!empty($query->destinatario)) ? $query->destinatario->uf : '',
            'ie' => (!empty($query->destinatario)) ? $query->destinatario->inscricaoestadual : '',
        ];

        $emitente = '';

        if(!empty($query->fornecedor)){
            $emitente = $query->fornecedor;
        }
        

        $dados['emitente'] = [
            'nome' => $query->fornecedor_nome,
            'fantasia' => (!empty($emitente)) ? $emitente->nomefantasia : '',
            'endereco' => (!empty($emitente)) ? $emitente->logradouro : '',
            'numero' => '',
            'complemento' => '',
            'bairro' => (!empty($emitente)) ? $emitente->bairro : '',
            'municipio' => (!empty($emitente)) ? $emitente->municipio : '',
            'uf' => (!empty($emitente)) ? $emitente->uf : '',
            'cep' => (!empty($emitente)) ? $emitente->cep : '',
            'telefone' => (!empty($emitente)) ? '('.$emitente->ddd.') '.$emitente->telefone : '',
            'ie' => (!empty($emitente)) ? $emitente->inscricaoestadual : '',
            'cpf_cnpj' => $query->fornecedor_cnpj,
        ];

        $dados['natureza_operacao'] = (!empty($naturea_operacao)) ? $query->documento_cfop.' - '.$naturea_operacao->cfop_descricao : $query->natureza_operacao;
        $dados['cfop'] = $query->documento_cfop;

        if(!empty($query->itensNotas[0])){
            foreach($query->itensNotas as $itens){
                $dados['itens'][] = [
                    'codigo' => $itens->codigo_produto,
                    'descricao' => (!empty($itens->produtoEspecicacoes->descricao)) ? $itens->produtoEspecicacoes->descricao : '',
                    'ncm' => $itens->codigo_ncm,
                    'cst' => $itens->codigo_cest,
                    'cfop' => $itens->codigo_cfop,
                    'unidade' => $itens->comercial_unidade,
                    'quantidade' => parserValor($itens->comercial_quantidade),
                    'valor' => parserValor($itens->comercial_valor_unitario),
                    'desconto' => 0,
                    'total' => parserValor($itens->valor_total),
                    'base_calculo' => parserValor($itens->icms_modalidade_bc),
                    'icms' => parserValor($itens->icms_valor),
                    'pis' => parserValor($itens->pis_valor),
                    'quantidade_tributavel' => parserValor($itens->tributavel_quantidade),
                    'cofins_valor' => parserValor($itens->cofins_valor)
                ];
            }
        }
        
		$pdf = PDF::loadView('pdf.nota_entrada', 
        $dados,
        [],
        [
            'margin_top' => 10,
            'margin_bottom' => 10 ,
            'display_mode' => 'fullpage' ,
            'title' => $query->documento_numero.' - '.$query->documento_serie,
        ]);

        return $pdf->download();
    }

}
