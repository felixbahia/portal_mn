<?php

namespace App\Http\Controllers;

use App\NotasNasajon;
use App\NotasImportadasEntrada;
use App\NotasImportadasEntradaRelacaoNota;
use App\NasajonEstabelecimento;
use App\AliquotaPreco;
use App\FaturamentoNotaNasajon;
use App\TransportadorNasajon;
use App\FaturamentoOnline;

use App\Exports\FreteCobradoXPagoExport;
use App\Exports\FreteCobradoXPagoDetalheExport;

use Illuminate\Http\Request;
use Auth;

use Carbon\Carbon;

use App\Http\Requests\FreteCobradoXPagoRequest;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class FreteCobradoXPagoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\FreteCobradoXPago") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\FreteCobradoXPago');

        $estabelecimentos = returnEmpresasNasajonView();

        return view('programs.frete_cobrado_x_pago.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function filter(FreteCobradoXPagoRequest $request){

        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '600');

        $fields = $request->only('estabelecimento', 'data_inicio', 'data_fim', 'transportadora', 'cif', 'fob', 'export');

        $queryFretes = FaturamentoOnline::with(['nota','notaEntrada'])->select();

        $queryNotasImportadas = NotasImportadasEntrada::
			where('tipo', 'cte')->
            with(['notasEntradas','cteNfe.faturamentoOnline'])->
			where('cobranca', true);

        if((isset($fields['fob']) && !empty($fields['fob'])) XOR (isset($fields['cif']) && !empty($fields['cif']))){
            if(isset($fields['cif']) && !empty($fields['cif'])){
                $queryFretes->where('tipo_frete', 'CIF');
                $queryNotasImportadas->whereHas('cteNfe.faturamentoOnline', function($query){
                    $query->where('tipo_frete', 'CIF');
                });
            }
            else if (isset($fields['fob']) && !empty($fields['fob'])){
                $queryFretes->where('tipo_frete', 'FOB');
                $queryNotasImportadas->whereHas('cteNfe.faturamentoOnline', function($query){
                    $query->where('tipo_frete', 'FOB');
                });
            }
        }

        if(isset($fields['transportadora']) && !empty($fields['transportadora'])){
            $transportadoras = TransportadorNasajon::where(DB::Raw("TRIM(CONCAT(TRIM(nome), ' - ', cnpj))"), 'ilike', "%" . $fields['transportadora'] . "%")->get()->pluck('id');

            $queryFretes->whereIn('transportador_uuid', $transportadoras);
            $queryNotasImportadas->whereIn('fornecedor_id', $transportadoras);
        }

        if(isset($fields['estabelecimento']) && (!empty($fields['estabelecimento']) || $fields['estabelecimento'] === '0')){
            $queryFretes->where('estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
            $queryNotasImportadas->where('estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $queryFretes->where('data', '>=', $data_inicio->format('Y-m-d 00:00:00'));
            $queryNotasImportadas->where('data_emissao', '>=', $data_inicio->format('Y-m-d 00:00:00'));
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $queryFretes->where('data', '<=', $data_fim->format('Y-m-d 23:59:59'));
            $queryNotasImportadas->where('data_emissao', '<=', $data_fim->format('Y-m-d 23:59:59'));
        }

        $fretesObj = $queryFretes->get();

        $notasImportadasEntradaObj = $queryNotasImportadas->get();

        $resultado = [];
        $total = [
            'frete_cobrado' => 0,
            'frete_pago' => 0,
            'diferenca' => 0,
            'peso_cobrado' => 0,
            'peso_transportado' => 0,
        ];

        $vendas_total = $fretesObj->filter(function($frete){
            return (substr($frete->tipo_operacao, 0, 5) == "VENDA" || substr($frete->tipo_operacao, 0, 2) == 'SV' || $frete->tipo_operacao == "SIMPLESFATFUTURA");
        })->sum('valor_compra') + $fretesObj->filter(function($frete){
            return (substr($frete->tipo_operacao, 0, 5) == "VENDA" || substr($frete->tipo_operacao, 0, 2) == 'SV' || $frete->tipo_operacao == "SIMPLESFATFUTURA");
        })->sum('valor_frete') + $fretesObj->filter(function($frete){
            return (substr($frete->tipo_operacao, 0, 5) == "VENDA" || substr($frete->tipo_operacao, 0, 2) == 'SV' || $frete->tipo_operacao == "SIMPLESFATFUTURA");
        })->sum('valor_ipi') + $fretesObj->filter(function($frete){
            return (substr($frete->tipo_operacao, 0, 5) == "VENDA" || substr($frete->tipo_operacao, 0, 2) == 'SV' || $frete->tipo_operacao == "SIMPLESFATFUTURA");
        })->sum('valor_prepago') - $fretesObj->filter(function($frete){
            return (substr($frete->tipo_operacao, 0, 5) == "VENDA" || substr($frete->tipo_operacao, 0, 2) == 'SV' || $frete->tipo_operacao == "SIMPLESFATFUTURA");
        })->sum('valor_troco');
        
        $devolucoes_total = $fretesObj->filter(function($frete){
            return (substr($frete->tipo_operacao, 0, 3) == 'DEV' || substr($frete->tipo_operacao, 0, 2) == 'ED');
        })->sum('valor_compra');

        $faturamento_total = $vendas_total - $devolucoes_total;

        $estabelecimentos = returnEmpresasNasajonView();

        foreach($estabelecimentos as $codigo => $estabelecimento){

            $linha = [];

            $fretes = $fretesObj->where('estabelecimento', str_pad($codigo, 2, '0', STR_PAD_LEFT));
            $fretes_pago = $notasImportadasEntradaObj->where('estabelecimento', str_pad($codigo, 2, '0', STR_PAD_LEFT));

            if($fretes->isEmpty() && $fretes_pago->isEmpty()){
                continue;
            }
            
            $total_frete_cobrado = $fretes->sum('valor_frete_cobrado');
            $total_frete_pago = $fretes_pago->sum('valor_total_servico');
            $peso_transportado = 0;
            $fretes_pago->each(function($peso) use (&$peso_transportado,$fretes){
                foreach($peso->cteNfe as $cte){
                    if(isset($cte->faturamentoOnline->id)){
                        $frete = $fretes->where('id',$cte->faturamentoOnline->id)->first();
                        if(empty($frete)){
                            continue;
                        }
                        $peso_venda = 0;
                        $peso_devolucao = 0;

                        if(substr($frete->tipo_operacao, 0, 5) == "VENDA" || 
                        substr($frete->tipo_operacao, 0, 2) == 'SV' || 
                        $frete->tipo_operacao == "SIMPLESFATFUTURA"){
                            $peso = 0;
            
                            if(!empty($frete->nota->pesoliquido)){
                                $peso = $frete->nota->pesoliquido;
                            }else if(!empty($frete->notaEntrada["Peso Líquido"])){
                                $peso = $frete->notaEntrada["Peso Líquido"];
                            }
            
                            $peso_venda = $peso;
                        }

                        if(substr($frete->tipo_operacao, 0, 3) == 'DEV' || 
                        substr($frete->tipo_operacao, 0, 2) == 'ED'){
                            $peso = 0;
            
                            if(!empty($frete->nota->pesoliquido)){
                                $peso = $frete->nota->pesoliquido;
                            }else if(!empty($frete->notaEntrada["Peso Líquido"])){
                                $peso = $frete->notaEntrada["Peso Líquido"];
                            }
            
                            $peso_devolucao = $peso;
                        }

                        $peso_transportado += $peso_venda - $peso_devolucao;
                    }
                }
            });

            if($codigo != 20){
                $vendas = $fretes->filter(function($frete){
                    return (substr($frete->tipo_operacao, 0, 5) == "VENDA" || substr($frete->tipo_operacao, 0, 2) == 'SV' || $frete->tipo_operacao == "SIMPLESFATFUTURA");
                })->sum('valor_compra') + $fretes->filter(function($frete){
                    return (substr($frete->tipo_operacao, 0, 5) == "VENDA" || substr($frete->tipo_operacao, 0, 2) == 'SV' || $frete->tipo_operacao == "SIMPLESFATFUTURA");
                })->sum('valor_frete') + $fretes->filter(function($frete){
                    return (substr($frete->tipo_operacao, 0, 5) == "VENDA" || substr($frete->tipo_operacao, 0, 2) == 'SV' || $frete->tipo_operacao == "SIMPLESFATFUTURA");
                })->sum('valor_ipi') + $fretes->filter(function($frete){
                    return (substr($frete->tipo_operacao, 0, 5) == "VENDA" || substr($frete->tipo_operacao, 0, 2) == 'SV' || $frete->tipo_operacao == "SIMPLESFATFUTURA");
                })->sum('valor_prepago') - $fretes->filter(function($frete){
                    return (substr($frete->tipo_operacao, 0, 5) == "VENDA" || substr($frete->tipo_operacao, 0, 2) == 'SV' || $frete->tipo_operacao == "SIMPLESFATFUTURA");
                })->sum('valor_troco');

                $peso_cobrado_venda = $fretes->filter(function($frete){
                    return (substr($frete->tipo_operacao, 0, 5) == "VENDA" || substr($frete->tipo_operacao, 0, 2) == 'SV' || $frete->tipo_operacao == "SIMPLESFATFUTURA");
                })->sum('nota.pesoliquido');

                $peso_cobrado_venda += $fretes->filter(function($frete){
                    return (substr($frete->tipo_operacao, 0, 5) == "VENDA" || substr($frete->tipo_operacao, 0, 2) == 'SV' || $frete->tipo_operacao == "SIMPLESFATFUTURA");
                })->sum('notaEntrada["Peso Líquido"]');

                $peso_cobrado_devolucoes = $fretes->filter(function($frete){
                    return (substr($frete->tipo_operacao, 0, 3) == 'DEV' || substr($frete->tipo_operacao, 0, 2) == 'ED');
                })->sum('notaEntrada["Peso Líquido"]');

                $peso_cobrado_devolucoes += $fretes->filter(function($frete){
                    return (substr($frete->tipo_operacao, 0, 3) == 'DEV' || substr($frete->tipo_operacao, 0, 2) == 'ED');
                })->sum('nota.pesoliquido');

                $devolucoes = $fretes->filter(function($frete){
                    return (substr($frete->tipo_operacao, 0, 3) == 'DEV' || substr($frete->tipo_operacao, 0, 2) == 'ED');
                })->sum('valor_compra');
                
                $peso_cobado = abs($peso_cobrado_venda - $peso_cobrado_devolucoes);
                $faturamento = $vendas - $devolucoes;

                if($faturamento > 0){
                    $porcentagem_cobrado = ($total_frete_cobrado/$faturamento)*100;
                    $porcentagem_pago = ($total_frete_pago/$faturamento)*100;
                }
                else{
                    $porcentagem_cobrado = 0;
                    $porcentagem_pago = 0;
                }
            }
            else{
                $faturamento = 0;
                $peso_cobado = 0;
                $porcentagem_cobrado = 0;
                $porcentagem_pago = 0;
            }

            if(isset($fields['export']) && !empty($fields['export'])){
                $linha = [
                    'estabelecimento' => $estabelecimento,
                    'faturamento' => parserValor($faturamento),
                    'peso_cobrado' => parserValor($peso_cobado),
                    'frete_cobrado' => parserValor($total_frete_cobrado),
                    'porcentagem_cobrado' => parserValor($porcentagem_cobrado) . '%',
                    'frete_pago' => parserValor($total_frete_pago),
                    'peso_transportado' => parserValor(abs($peso_transportado)),
                    'porcentagem_pago' => parserValor($porcentagem_pago) . '%',
                    'diferenca' =>  parserValor($total_frete_cobrado - $total_frete_pago)
                ];
            }
            else{
                $linha = [
                    'estabelecimento' => $estabelecimento,
                    'estabelecimento_codigo' => $codigo,
                    'faturamento' => parserValor($faturamento),
                    'peso_cobrado' => parserValor($peso_cobado),
                    'peso_transportado' => parserValor($peso_transportado),
                    'frete_cobrado' => parserValor($total_frete_cobrado),
                    'porcentagem_cobrado' => parserValor($porcentagem_cobrado) . '%',
                    'frete_pago' => parserValor($total_frete_pago),
                    'porcentagem_pago' => parserValor($porcentagem_pago) . '%',
                    'diferenca' =>  parserValor($total_frete_cobrado - $total_frete_pago),
                    'hash' => encrypt([
                        'data_inicio' => $fields['data_inicio'],
                        'data_fim' => $fields['data_fim'],
                        'estabelecimento' => $codigo,
                        'fornecedor' => $fields['transportadora']??'',
                        'cif' => (isset($fields['cif']) && !empty($fields['cif'])),
                        'fob' => (isset($fields['fob']) && !empty($fields['fob'])),
                        'cobranca' => 'com_cobranca',
                        'natureza' => '',
                        'frete' => 'com_frete',
                        'lancadas' => ''
                    ])
                ];
            }

            $resultado [] = $linha;

            $total['frete_cobrado'] += $total_frete_cobrado;
            $total['frete_pago'] += $total_frete_pago;
            $total['peso_cobrado'] += $peso_cobado;
            $total['peso_transportado'] += $peso_transportado;
            $total['diferenca'] += $total_frete_cobrado - $total_frete_pago;
            
        }

        if($faturamento_total > 0){
            $porcentagem_cobrado_total = ($total['frete_cobrado']/$faturamento_total)*100;
            $porcentagem_pago_total = ($total['frete_pago']/$faturamento_total)*100;
        }
        else{
            $porcentagem_cobrado_total = 0;
            $porcentagem_pago_total = 0;
        }

        if(isset($fields['export']) && !empty($fields['export'])){
            $resultado[] = [
                'estabelecimento' => 'TOTAL',
                'faturamento' => parserValor($faturamento_total),
                'peso_cobrado' => parserValor($total['peso_cobrado']),
                'peso_transportado' => parserValor($total['peso_transportado']),
                'frete_cobrado' => parserValor($total['frete_cobrado']),
                'porcentagem_cobrado' => parserValor($porcentagem_cobrado_total) . '%',
                'frete_pago' => parserValor($total['frete_pago']),
                'porcentagem_pago' => parserValor($porcentagem_pago_total) . '%',
                'diferenca' => parserValor($total['diferenca'])
            ];

            return collect($resultado);
        }
        else{
            $total['faturamento'] = parserValor($faturamento_total);
            $total['peso_cobrado'] = parserValor($total['peso_cobrado']);
            $total['peso_transportado'] = parserValor($total['peso_transportado']);
            $total['frete_cobrado'] = parserValor($total['frete_cobrado']);
            $total['porcentagem_cobrado'] = parserValor($porcentagem_cobrado_total). '%';
            $total['frete_pago'] = parserValor($total['frete_pago']);
            $total['porcentagem_pago'] = parserValor($porcentagem_pago_total). '%';
            $total['diferenca'] = parserValor($total['diferenca']);
            $total['hash'] = encrypt([
                'data_inicio' => $fields['data_inicio'],
                'data_fim' => $fields['data_fim'],
                'fornecedor' => $fields['transportadora']??'',
                'cobranca' => 'com_cobranca',
                'natureza' => '',
                'frete' => 'com_frete',
                'lancadas' => ''
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Dados recuperados com sucesso',
                'error' => [],
                'response' => [
                    'resultado' => $resultado,
                    'total' => $total,
                    'fields' => $fields
                ]
            ]);
        }
    }

    public function export(FreteCobradoXPagoRequest $request){
        $fields = $request->only('data_inicio', 'data_fim', 'estabelecimento');

        $freteXLSX = new FreteCobradoXPagoExport($request);

        $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
        $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);

        $pdfFilePath = 'frete_pago'.$data_inicio->format('Ymd').'-'.$data_fim->format('Ymd').(isset($fields['estabelecimento'])?'-'.str_pad($fields['estabelecimento'],2,'0'.STR_PAD_LEFT):'').'.xlsx';
        return Excel::download(
            $freteXLSX, $pdfFilePath
        );
    }

    public function modal(Request $request){

        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '600');

        $fields = $request->only('estabelecimento', 'data_inicio', 'data_fim', 'transportadora', 'cif', 'fob', 'export');

        $queryFrete = FaturamentoOnline::
            with(
                'cliente',
                'transportador',
                'nota', 
                'notaEntrada', 
                'faturamentoNotaNasajon'
            );

        $queryNotasImportadas = NotasImportadasEntrada::with(['cteNfe.faturamentoOnline','cteNfe.NotasEntradasNasajon','cteNfe.notasEntradaImportadas.cfop'])->
			where('tipo', 'cte')->
			where('cobranca', true);


        if((isset($fields['fob']) && !empty($fields['fob'])) XOR (isset($fields['cif']) && !empty($fields['cif']))){
            
            if(isset($fields['cif']) && !empty($fields['cif'])){
                $queryFrete->where('tipo_frete', 'CIF');
                $queryNotasImportadas->whereHas('cteNfe.faturamentoOnline', function($query){
                    $query->where('tipo_frete', 'CIF');
                });
            }
            else if (isset($fields['fob']) && !empty($fields['fob'])){
                $queryFrete->where('tipo_frete', 'FOB');
                $queryNotasImportadas->whereHas('cteNfe.faturamentoOnline', function($query){
                    $query->where('tipo_frete', 'FOB');
                });
            }
        }

        if(isset($fields['transportadora']) && !empty($fields['transportadora'])){
            $transportadoras = TransportadorNasajon::where(DB::Raw("TRIM(CONCAT(TRIM(nome), ' - ', cnpj))"), 'ilike', "%" . $fields['transportadora'] . "%")->get()->pluck('id');

            $queryFrete->whereIn('transportador_uuid', $transportadoras);
            $queryNotasImportadas->whereIn('fornecedor_id', $transportadoras);
        }

        if(isset($fields['estabelecimento']) && !empty($fields['estabelecimento'])){
            $queryFrete->where('estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
            $queryNotasImportadas->where('estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $queryFrete->where('data', '>=', $data_inicio->format('Y-m-d 00:00:00'));
            $queryNotasImportadas->where('data_emissao', '>=', $data_inicio->format('Y-m-d 00:00:00'));
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $queryFrete->where('data', '<=', $data_fim->format('Y-m-d 23:59:59'));
            $queryNotasImportadas->where('data_emissao', '<=', $data_fim->format('Y-m-d 23:59:59'));
        }

        $fretesObj = $queryFrete->get();

        $notasImportadasEntradaObj = $queryNotasImportadas->get();

        $resultado = [];
        $total = [
            'frete_cobrado' => 0,
            'frete_pago' => 0,
            'diferenca' => 0,
            'peso_cobrado' => 0,
            'peso_transportado' => 0,
            'volume_cobrado' => 0,
            'volume_transportado' => 0
        ];
        $notas_sem_relacao = [];

        $estabelecimentos = returnEmpresasNasajonView();
        $cte_duplicada = [];

        $fretesObj->each(function($frete) use (&$resultado, &$total, $notasImportadasEntradaObj, $estabelecimentos, $fields,&$notas_sem_relacao,&$cte_duplicada){
            $notasImportadas = $notasImportadasEntradaObj->filter(function($nota_importada) use($frete){
                if(isset($nota_importada->cteNfe->first()->notas_importadas_entradas_id_nfe)){
                    return $nota_importada->cteNfe->first()->notas_importadas_entradas_id_nfe == $frete->nota_uuid;
                }
            });

            if(!empty($notasImportadas->first())){
                $frete_pago = $notasImportadas->sum('valor_total_servico');
                $notas_sem_relacao = array_merge($notasImportadas->pluck('id')->toArray(),$notas_sem_relacao);
            }
            else{
                $frete_pago = 0;
            }

            $frete_cobrado = $frete->valor_frete_cobrado;

            $porcentagem_frete_pago = parserValor((1 - (($frete->valor_compra - round($frete_pago, 2)) / $frete->valor_compra)) * 100);

            $linha = [];

            $linha['natureza_operacao'] = (!empty($frete->nota->naturezaoperacao)) ? "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $frete->nota->naturezaoperacao . "''>". $frete->nota->naturezaoperacao ."</div></div>" : '';

            if(isset($fields['export']) && !empty($fields['export'])){
                $linha['nota'] = $frete->nota_numero;
            }
            else{
                if(!empty($frete->nota)){
                    $linha['nota'] = "<a href=\"#\" data-id=\"".  $frete->nota_uuid ."\" data-route=\"" . route('notas_nasajon.modal.exibir') . "\" data-modal=\"modal-lg\" data-title_modal=\"Detalhes da nota:" . $frete->numero_documento . "\" onclick=\"showModalNota(this);\">" . $frete->numero_documento . "</a>";
                }
                else if(!empty($frete->notaEntrada) || (!empty($frete->faturamentoNotaNasajon) && $frete->faturamentoNotaNasajon['Descrição da Operação'] != 'Venda de Mercadorias (Origem SP)')){
                    $linha['nota'] = "<a href=\"#\" data-id=\"".  $frete->nota_uuid ."\" data-route=\"" . route('notas_entradas_nasajon.nota'). "\" data-modal=\"modal-lg\" data-title_modal=\"Detalhes da nota:" . $frete->numero_documento . "\" onclick=\"showModalNota(this);\">" . $frete->numero_documento . "</a>";
                }
                else{
                    $linha['nota'] = $frete->numero_documento;
                }
            }
            
            $peso_venda = 0;
            $peso_devolucao = 0;
            $peso_transportado = 0;
            $volume_venda = 0;
            $volume_devolucao = 0;
            $volume_transportado = 0;

            $notasImportadas->each(function($notas_entrada_importada) use (&$peso_transportado,&$volume_transportado,$frete){
                foreach($notas_entrada_importada->cteNfe as $cte){
                    if(isset($cte->faturamentoOnline->id) && $cte->faturamentoOnline->id == $frete->id){
                        $peso_venda = 0;
                        $peso_devolucao = 0;
                        $volume_venda = 0;
                        $volume_devolucao = 0;

                        if(substr($frete->tipo_operacao, 0, 5) == "VENDA" || 
                        substr($frete->tipo_operacao, 0, 2) == 'SV' || 
                        $frete->tipo_operacao == "SIMPLESFATFUTURA"){
                            $peso = 0;
            
                            if(!empty($frete->nota->pesoliquido)){
                                $peso = $frete->nota->pesoliquido;
                            }else if(!empty($frete->notaEntrada["Peso Líquido"])){
                                $peso = $frete->notaEntrada["Peso Líquido"];
                            }

                            $peso_venda = $peso;
                        }

                        if(substr($frete->tipo_operacao, 0, 3) == 'DEV' || 
                        substr($frete->tipo_operacao, 0, 2) == 'ED'){
                            $peso = 0;
            
                            if(!empty($frete->nota->pesoliquido)){
                                $peso = $frete->nota->pesoliquido;
                            }else if(!empty($frete->notaEntrada["Peso Líquido"])){
                                $peso = $frete->notaEntrada["Peso Líquido"];
                            }
            
                            $peso_devolucao = $peso;
                        }

                        $peso_transportado = $peso_venda - $peso_devolucao;
                    }
                }
                
                $volume_transportado += ($notas_entrada_importada->volume > 0) ? $notas_entrada_importada->volume : 0;
            });

            $volume_transportado = abs($volume_transportado);
            $peso_transportado = abs($peso_transportado);

            if(substr($frete->tipo_operacao, 0, 5) == "VENDA" || 
            substr($frete->tipo_operacao, 0, 2) == 'SV' || 
            $frete->tipo_operacao == "SIMPLESFATFUTURA"){
                $peso = 0;
                $volume = 0;

                if(!empty($frete->nota->pesoliquido)){
                    $peso = $frete->nota->pesoliquido;
                }else if(!empty($frete->notaEntrada["Peso Líquido"])){
                    $peso = $frete->notaEntrada["Peso Líquido"];
                }

                if(!empty($frete->nota->volumes)){
                    $volume = $frete->nota->volumes;
                }else if(!empty($frete->notaEntrada["Quantidade Volumes"])){
                    $volume = $frete->notaEntrada["Quantidade Volumes"];
                }


                $volume_venda = $volume;
                $peso_venda = $peso;
            }

            if(substr($frete->tipo_operacao, 0, 3) == 'DEV' || 
            substr($frete->tipo_operacao, 0, 2) == 'ED'){
                $peso = 0;
                $volume = 0;

                if(!empty($frete->nota->pesoliquido)){
                    $peso = $frete->nota->pesoliquido;
                }else if(!empty($frete->notaEntrada["Peso Líquido"])){
                    $peso = $frete->notaEntrada["Peso Líquido"];
                }

                if(!empty($frete->nota->volumes)){
                    $volume = $frete->nota->volumes;
                }else if(!empty($frete->notaEntrada["Quantidade Volumes"])){
                    $volume = $frete->notaEntrada["Quantidade Volumes"];
                }

                $peso_devolucao = $peso;
                $volume_devolucao = $volume;
            }

            $linha['peso_transportado'] = parserValor($peso_transportado);
            $linha['volume_transportado'] = parserValor($volume_transportado);
            $linha['peso_cobrado'] = parserValor(abs($peso_venda - $peso_devolucao));
            $linha['volume_cobrado'] = parserValor(abs($volume_venda - $volume_devolucao));

            $linha['cte'] = (!empty($notasImportadas->first()->documento_numero)) ? $notasImportadas->first()->documento_numero : '';
            $linha['cte_id'] = (!empty($notasImportadas->first()->id)) ? encrypt($notasImportadas->first()->id) : '';

            if(isset($fields['export']) && !empty($fields['export'])){
                $linha['cliente'] = $frete->cliente->nome . ' - ' . $frete->cliente->cpf_cnpj;
                $linha['origem'] = $estabelecimentos[intval($frete->estabelecimento)];
            }
            else{
                if(!empty($frete->cliente)){
                    $linha['cliente'] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $frete->cliente->nome . ' - ' . $frete->cliente->cpf_cnpj . "''>". $frete->cliente->nome . ' - ' . $frete->cliente_documento ."</div></div>";
                }
                else{
                    $linha['cliente'] = '';
                }
                $linha['origem'] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $estabelecimentos[intval($frete->estabelecimento)] . "''>". $estabelecimentos[intval($frete->estabelecimento)] ."</div></div>";
            }

            if(!empty($frete->cliente)){
                $linha['destino'] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $frete->cliente->uf.' - '.$frete->cliente->cidade . "''>". $frete->cliente->uf.' - '.$frete->cliente->cidade ."</div></div>";
            }
            else{
                $linha['destino'] = '';
            }

            if(!empty($frete->transportador)){
                if(isset($fields['export']) && !empty($fields['export'])){
                    $linha['transportador'] = $frete->transportador->nome . ' - ' . $frete->transportador->cnpj;
                }
                else{
                    $linha['transportador'] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $frete->transportador->nome . ' - ' . $frete->transportador->cnpj . "'>". $frete->transportador->nome . ' - ' . $frete->transportador->cnpj ."</div></div>";
                }
            }
            else{
                $linha['transportador'] = '';
            }

            $aliquota_frete = 0;

            if($frete->valor_compra > 0){
                $aliquota_frete = ($frete->valor_frete_cobrado * 100) / $frete->valor_compra;
            }

            $linha['valor_nota'] = parserValor($frete->valor_compra);
            
            if($aliquota_frete > 0){
                $linha['frete_adicional'] = parserValor($aliquota_frete) . "%";
            }
            else{
                $linha['frete_adicional'] = '';
            }

            $linha['frete_cobrado'] = parserValor($frete_cobrado);

            if((isset($fields['export']) && !empty($fields['export'])) || ($frete->aliquota_porcentagem <= $porcentagem_frete_pago)){
                $linha['porcentagem_frete_pago'] = $porcentagem_frete_pago . "%";
            }
            else{
                $linha['porcentagem_frete_pago'] = "<span class='text-danger'>" . $porcentagem_frete_pago . "% </span>";
            }
            $linha['frete_pago'] = parserValor($frete_pago);
            $linha['diferenca'] =  parserValor(($frete_cobrado) - $frete_pago);                
            
            $resultado[] = $linha;

            $total['frete_cobrado'] += $frete_cobrado;

            if(!empty($notasImportadas->first()) && !in_array($notasImportadas->first()->documento_numero.$notasImportadas->first()->valor_total_servico,$cte_duplicada)){
                $total['frete_pago'] += $frete_pago;
                $cte_duplicada[$notasImportadas->first()->documento_numero.$notasImportadas->first()->valor_total_servico] = $notasImportadas->first()->documento_numero.$notasImportadas->first()->valor_total_servico;
            }

            $total['peso_transportado'] += $peso_transportado;
            $total['peso_cobrado'] += $peso_venda - $peso_devolucao;
            $total['volume_cobrado'] += $volume_venda - $volume_devolucao;
            $total['volume_transportado'] += $volume_transportado;
            
        });

        $notasImportadasEntradaObj = $notasImportadasEntradaObj->whereNotIn('id',$notas_sem_relacao);

        $notasImportadasEntradaObj->each(function($query) use (&$resultado, &$total){
            $frete_pago = $query->valor_total_servico;
            $natureza_operacao = '';

            if(!empty($query->cteNfe[0]->NotasEntradasNasajon[0]['Descrição da Operação'])){
                $natureza_operacao = $query->cteNfe[0]->NotasEntradasNasajon[0]['Descrição da Operação'];
            }else if(!empty($query->cteNfe[0]->notasEntradaImportadas[0])){
                $natureza_operacao = $query->cteNfe[0]->notasEntradaImportadas[0]->cfop->cfop_descricao;
            }

            $linha = [];
            $linha['nota'] = '';
            $linha['peso_transportado'] = '';
            $linha['volume_transportado'] = '';
            $linha['peso_cobrado'] = '';
            $linha['volume_cobrado'] = '';
            $linha['natureza_operacao'] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $natureza_operacao . "''>". $natureza_operacao ."</div></div>";
            $linha['cte'] = $query->documento_numero;
            $linha['cte_id'] = encrypt($query->id);
            $linha['cliente'] = '';
            $linha['origem'] = '';
            $linha['destino'] = '';
            $linha['transportador'] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $query->fornecedor_nome . ' - ' . $query->fornecedor_cnpj . "''>".$query->fornecedor_nome . ' - ' . $query->fornecedor_cnpj."</div></div>";
            $linha['valor_nota'] = '';
            $linha['frete_adicional'] = '';
            $linha['frete_cobrado'] = '';
            $linha['porcentagem_frete_pago'] = '';
            $linha['frete_pago'] = parserValor($frete_pago);
            $linha['diferenca'] = '';

            $resultado[] = $linha;

            $total['frete_pago'] += $frete_pago;
        });

        if(isset($fields['export']) && !empty($fields['export'])){
            $resultado[] = [
                'nota' => 'TOTAL',
                'cte' => '',
                'cliente' => '',
                'origem' => '',
                'destino' => '',
                'transportador' => '',
                'total_produto' => '',
                'frete_adicional' => '',
                'peso_transportado' => parserValor($total['peso_transportado']),
                'peso_cobrado' => parserValor($total['peso_cobrado']),
                'frete_cobrado' => parserValor($total['frete_cobrado']),
                'frete_pago' => parserValor($total['frete_pago']),
                'diferenca' => parserValor($total['diferenca'])
            ];

            return collect($resultado);
        }
        else{

            $total['diferenca'] = parserValor($total['frete_cobrado'] -  $total['frete_pago']);
            $total['peso_transportado'] = parserValor($total['peso_transportado']);
            $total['peso_cobrado'] = parserValor($total['peso_cobrado']);
            $total['frete_cobrado'] = parserValor($total['frete_cobrado']);
            $total['frete_pago'] = parserValor($total['frete_pago']);
            $total['volume_cobrado'] = parserValor($total['volume_cobrado']);
            $total['volume_transportado'] = parserValor($total['volume_transportado']);
            
            return view('programs.frete_cobrado_x_pago.modal.index')->with(['total' => $total, 'resultado' => $resultado, 'fields' => $fields]);
        }
    }

    public function modalExport(Request $request){
        $fields = $request->only('data_inicio', 'data_fim', 'estabelecimento');

        $freteXLSX = new FreteCobradoXPagoDetalheExport($request);

        $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
        $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);

        $pdfFilePath = 'frete_pago_detalhes_'.$data_inicio->format('Ymd').'-'.$data_fim->format('Ymd').'-'.str_pad($fields['estabelecimento'],2,'0'.STR_PAD_LEFT).'.xlsx';
        return Excel::download(
            $freteXLSX, $pdfFilePath
        );
    }

    public function modalNotas(Request $request){

        $fields = $request->only('estabelecimento', 'data_inicio', 'data_fim', 'transportadora', 'cif', 'fob');

        $notasQuery = FaturamentoOnline::with('cliente', 'nota', 'nota', 'notaEntrada', 'faturamentoNotaNasajon');

        if(isset($fields['estabelecimento']) && !empty($fields['estabelecimento'])){
            $notasQuery->where('estabelecimento', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat("d/m/Y", $fields['data_inicio']);
            $notasQuery->where('data', '>=', $data_inicio->format('Y-m-d 00:00:00'));
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat("d/m/Y", $fields['data_fim']);
            $notasQuery->where('data', '<=', $data_fim->format('Y-m-d 23:59:59'));
        }

        if(isset($fields['transportadora']) && !empty($fields['transportadora'])){
            $transportadoras = TransportadorNasajon::where(DB::Raw("TRIM(CONCAT(TRIM(nome), ' - ', cnpj))"), 'ilike', "%" . $fields['transportadora'] . "%")->get()->pluck('id');

            $notasQuery->whereIn('transportador_uuid', $transportadoras);
        }        

        if((isset($fields['fob']) && !empty($fields['fob'])) XOR (isset($fields['cif']) && !empty($fields['cif']))){
            if(isset($fields['cif']) && !empty($fields['cif'])){
                $notasQuery->where('tipo_frete', 'CIF');
            }

            if(isset($fields['fob']) && !empty($fields['fob'])){
                $notasQuery->where('tipo_frete', 'FOB');
            }
        }

        $notasObj = $notasQuery->get();

        $resultado = [];
        $valor_total = 0;

        $estabelecimentos = returnEmpresasNasajonView();

        $notasObj->each(function($nota) use(&$resultado, &$valor_total, $estabelecimentos){

            $linha = [];

            $linha['estabelecimento'] = $estabelecimentos[intval($nota->estabelecimento)];
            $linha['id'] = $nota->nota_uuid;
            $linha['numero'] = $nota->numero_documento;
            $linha['cliente'] = $nota->cliente->nome??'' . ' - ' . $nota->cpf_cnpj??'';
            $linha['emissao'] = parserData($nota->data);

            if(!empty($nota->nota)){
                $valor = $nota->nota->valor;
                $route = route('notas_nasajon.modal.exibir');
                $natureza_de_operacao = $nota->nota->naturezaoperacao;
            }
            else if(!empty($nota->notaEntrada)){
                $valor = $nota->notaEntrada['Valor do Documento'];
                $linha['id'] = $nota->notaEntrada['Identificador Documento'];
                $route = route('notas_entradas_nasajon.nota');
                $natureza_de_operacao = $nota->notaEntrada['Descrição da Operação'];
            }
            else if(!empty($nota->faturamentoNotaNasajon)){

                $valor = $nota->faturamentoNotaNasajon['Valor do Documento'];
                $linha['id'] = $nota->faturamentoNotaNasajon->Id_Nota;
                $route = route('notas_entradas_nasajon.nota');
                $natureza_de_operacao = $nota->faturamentoNotaNasajon['Descrição da Operação'];

                if($nota->faturamentoNotaNasajon['Descrição da Operação'] == 'Venda de Mercadorias (Origem SP)'){
                    $route = '';
                }
            }
            else{
                $valor = $nota->valor_compra + $nota->valor_frete + $nota->valor_ipi - $nota->valor_troco;
                $route = '';
                $natureza_de_operacao = '';
            }

            if($valor - $nota->valor_frete_cobrado > 0){
                $aliquota_porcentagem = ($nota->valor_frete_cobrado / ($valor - $nota->valor_frete_cobrado)) * 100;
            }
            else{
                $aliquota_porcentagem = 0;
            }

            $linha['valor'] = parserValor($valor);
            $linha['frete'] = parserValor($nota->valor_frete_cobrado);
            $linha['natureza'] = $natureza_de_operacao;
            $linha['route'] = $route;
            $linha['aliquota_porcentagem'] = parserValor($aliquota_porcentagem) . "%";
            $resultado[] = $linha;
            $valor_total += $valor;
        });

        $total = [
            'valor' => '',
            'frete' => ''
        ];

        $frete_adicional_total = $notasObj->sum('valor_frete_cobrado');

        if($valor_total > 0){
            $total['valor'] = parserValor($valor_total);
        }

        if($frete_adicional_total > 0){
            $total['frete'] = parserValor($frete_adicional_total);
        }

        return view('programs.frete_cobrado_x_pago.modal.notas')->with(['resultado' => $resultado, 'total' => $total]);
    }
}
