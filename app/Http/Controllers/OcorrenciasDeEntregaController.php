<?php

namespace App\Http\Controllers;

use App\CepEstado;
use App\Http\Requests\ConsultaFaturaTransportadoraRequest;
use App\NasajonEstabelecimento;
use App\NotasEntradasNasajon;
use App\NotasNasajon;
use App\OcorrenciasDeEntrega;
use App\NotasTransportadoraHeader;
use App\NotasTransportadoraItem;
use App\TransportadorNasajon;
use App\AliquotaPreco;
use App\NotasTransportadoraHeaderAprovacaoFatura;
use App\LogImportacaoEdi;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Auth;
use Illuminate\Support\Facades\DB;

class OcorrenciasDeEntregaController extends Controller
{

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ConsultaFaturaTransportadora") === false){
            return abort(403);
        }
        $estadosObj = CepEstado::select('uf', 'estado')->get();

        $estados = [];

        $estadosObj->each(function ($estado) use(&$estados){
            $estados[$estado->uf] = $estado->estado;
        });

        $request->session()->flash('model', 'App\ConsultaFaturaTransportadora');
    	return view("programs.consulta_fatura_transportadora.index")->with(['uf' => $estados]);
    }

    public function filtro(ConsultaFaturaTransportadoraRequest $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $campo = $request->only('transportadora', 'data_inicio', 'data_fim', 'uf_destinatario','divergencia','situacao','fatura','nota');

        $FaturasTransportadoraObj = NotasTransportadoraHeader::select('id','transportadora_cnpj', 'documento_cobranca', DB::raw('sum(valor_total) as valor_total, count(documento_cobranca) as documento_quantidade'))
        ->with(['notasFaturaSoma' => function($query) use ($campo){
            if(!empty($campo['uf_destinatario'])){
                $query->where('uf_destinatario', $campo['uf_destinatario']);
            }
        },'nomeTransportadora','faturaSituacao','notasFatura' => function($query) use ($campo){
            if(!empty($campo['uf_destinatario'])){
                $query->where('uf_destinatario', $campo['uf_destinatario']);
            }
            $query->with('notasSaida.estabelecimento_detalhes.cidadeDetalhes');
        }])
        ->groupBy('transportadora_cnpj', 'documento_cobranca','id');

        if(!empty($campo['uf_destinatario'])){
            $FaturasTransportadoraObj->whereHas('notasFatura', function($query) use ($campo){
                $query->where('uf_destinatario', $campo['uf_destinatario']);
            });
        }

        if(!empty($campo['transportadora'])){
            $TransportadorNasajonObj = TransportadorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj))'), 'ilike', trim($campo['transportadora']))->get();
            $FaturasTransportadoraObj->whereIn('transportadora_cnpj', $TransportadorNasajonObj->pluck('cnpj'));
        }

        if(!empty($campo['data_inicio']) && !empty($campo['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $campo['data_inicio'])->format('Y-m-d');
            $data_fim = Carbon::createFromFormat('d/m/Y', $campo['data_fim'])->format('Y-m-d');

            $FaturasTransportadoraObj->whereBetween('data_emissao', [$data_inicio, $data_fim]);
        }
        
        if(!empty($campo['situacao'])){
            if($campo['situacao'] == 'pendentes'){
                $FaturasTransportadoraObj->doesntHave('faturaSituacao');
            }else{
                $FaturasTransportadoraObj->whereHas('faturaSituacao', function($query) use ($campo){
                    if($campo['situacao'] == 'aprovadas'){
                        $query->where('situacao', true);
                    }else{
                        $query->where('situacao', false);
                    }
                });
            }
        }

        if(!empty($campo['fatura'])){
            $FaturasTransportadoraObj->where('documento_cobranca','LIKE','%'.$campo['fatura'].'%');
        }

        if(!empty($campo['nota'])){
            $notas_nasajon_id = NotasNasajon::where('numero','LIKE','%'.$campo['nota'].'%')->get()->pluck('id')->toArray();
            $FaturasTransportadoraObj->whereHas('notasFatura',function($query) use ($notas_nasajon_id){
                $query->whereIn('nota_id',$notas_nasajon_id);
            });
        }
        

        $FaturasTransportadora = $FaturasTransportadoraObj->get();
        $aliquota = AliquotaPreco::get();
        
        $total = [
            'fatura' => 0,
            'valor_fatura' => 0,
            'peso_fatura' => 0,
            'notas_fatura' => 0,
            'notas' => 0,
            'valor_notas' => 0,
            'peso_notas' => 0,
            'percentual_frete' => 0,
            'filters' =>  [
                'transportadora' => $campo['transportadora'],
                'transportadora_cnpj' => '',
                'uf_destinatario' => $campo['uf_destinatario'],
                'data_inicio' => $campo['data_inicio'],
                'data_fim' => $campo['data_fim'],
                'divergencia' => $campo['divergencia'],
                'situacao' => $campo['situacao'],
                'fatura' => $campo['fatura'],
                'nota' => $campo['nota'],
            ]
        ];

        foreach($FaturasTransportadora as $fatura){
            $fatura_quantidade = $fatura->documento_quantidade;
            $documento_cobranca = $fatura->documento_cobranca;
            $peso_fatura = 0;
            $notas = 0;
            $notas_fatura = 0;
            $valor_fatura = 0;
            $divergencia = true;

            if($campo['divergencia'] == 'com_divergencia'){
                foreach($fatura->notasFatura as $fatura_nota){
                    if(isset($fatura_nota->notasSaida)){
                        $uf_origem = $fatura_nota->notasSaida->estabelecimento_detalhes->cidadeDetalhes->uf;
                        $percentual_aliquota = $aliquota->where('origem',$uf_origem)->where('estado',$fatura_nota->uf_destinatario)->first();

                        if(!empty($percentual_aliquota)){
                            if(!empty($campo['uf_destinatario'])){
                                $valor_fatura = $fatura->notasFaturaSoma->total_frete;
                            }else{
                                $valor_fatura = $fatura->valor_total;
                            }
                            
                            $peso_fatura = !empty($fatura->notasFaturaSoma->peso_total) ? $fatura->notasFaturaSoma->peso_total:0;
                            $notas = !empty($fatura->notasFaturaSoma->nota_quantidade) ? $fatura->notasFaturaSoma->nota_quantidade:0;
                            $notas_fatura =!empty($fatura->notasFaturaSoma->valor_total) ? $fatura->notasFaturaSoma->valor_total:0; 

                            $percentual_frete = ($notas_fatura > 0) ? $valor_fatura / $notas_fatura * 100 : 0;

                            if($percentual_frete > $percentual_aliquota->frete_adicional){
                                $divergencia = true;
                            }else{
                                $divergencia = false;
                            }

                        }else{
                            $divergencia = false;
                        }
                    }else{
                        $divergencia = false;
                    }
                }

                if($divergencia == false){
                    continue;
                }

            }if($campo['divergencia'] == 'sem_divergencia'){
                foreach($fatura->notasFatura as $fatura_nota){
                    if(isset($fatura_nota->notasSaida)){
                        $uf_origem = $fatura_nota->notasSaida->estabelecimento_detalhes->cidadeDetalhes->uf;
                        $percentual_aliquota = $aliquota->where('origem',$uf_origem)->where('estado',$fatura_nota->uf_destinatario)->first();

                        if(!empty($percentual_aliquota)){
                            if(!empty($campo['uf_destinatario'])){
                                $valor_fatura = $fatura->notasFaturaSoma->total_frete;
                            }else{
                                $valor_fatura = $fatura->valor_total;
                            }
                            
                            $peso_fatura =!empty($fatura->notasFaturaSoma->peso_total) ? $fatura->notasFaturaSoma->peso_total:0; 
                            $notas = !empty($fatura->notasFaturaSoma->nota_quantidade) ? $fatura->notasFaturaSoma->nota_quantidade:0;
                            $notas_fatura = !empty($fatura->notasFaturaSoma->valor_total) ? $fatura->notasFaturaSoma->valor_total:0;

                            $percentual_frete = ($notas_fatura > 0) ? $valor_fatura / $notas_fatura * 100 : 0;

                            if($percentual_frete <= $percentual_aliquota->frete_adicional){
                                $divergencia = false;
                            }else{
                                $divergencia = true;
                            }

                        }else{
                            $divergencia = true;
                        }
                    }else{
                        $divergencia = true;
                    }
                }

                if($divergencia == true){
                    continue;
                }

            }else{
                if(!empty($campo['uf_destinatario'])){
                    $valor_fatura = $fatura->notasFaturaSoma->total_frete;
                }else{
                    $valor_fatura = $fatura->valor_total;
                }

                foreach($fatura->notasFatura as $fatura_nota){
                    if(isset($fatura_nota->notasSaida)){
                        $uf_origem = $fatura_nota->notasSaida->estabelecimento_detalhes->cidadeDetalhes->uf;
                        $percentual_aliquota = $aliquota->where('origem',$uf_origem)->where('estado',$fatura_nota->uf_destinatario)->first();

                        if(!empty($percentual_aliquota)){
                            $notas = !empty($fatura->notasFaturaSoma->nota_quantidade) ? $fatura->notasFaturaSoma->nota_quantidade:0;
                            $notas_fatura = !empty($fatura->notasFaturaSoma->valor_total) ? $fatura->notasFaturaSoma->valor_total:0;

                            $percentual_frete = ($notas_fatura > 0) ? $valor_fatura / $notas_fatura * 100 : 0;

                            if($percentual_frete > $percentual_aliquota->frete_adicional){
                                $divergencia = true;
                            }else{
                                $divergencia = false;
                            }

                        }
                    }
                }
                
                $peso_fatura = !empty($fatura->notasFaturaSoma->peso_total) ? $fatura->notasFaturaSoma->peso_total:0;
                $notas = !empty($fatura->notasFaturaSoma->nota_quantidade) ? $fatura->notasFaturaSoma->nota_quantidade:0;
                $notas_fatura = !empty($fatura->notasFaturaSoma->valor_total) ? $fatura->notasFaturaSoma->valor_total:0;
            }
            
            if(!isset($saida[$fatura->transportadora_cnpj])){
                $saida[$fatura->transportadora_cnpj] = [
                    'transportadora' => isset($fatura->nomeTransportadora->nome) ? $fatura->nomeTransportadora->nome.' - '.$fatura->transportadora_cnpj : $fatura->transportadora_cnpj,
                    'fatura' => 0,
                    'valor_fatura' => 0,
                    'peso_fatura' => 0,
                    'notas_fatura' => 0,
                    'notas' => 0,
                    'valor_notas' => 0,
                    'peso_notas' => 0,
                    'divergencia' => $divergencia,
                    'status_verifica' => [],
                    'status' => '',
                    'filters' => encrypt([
                        'transportadora_cnpj' => $fatura->transportadora_cnpj,
                        'uf_destinatario' => $campo['uf_destinatario'],
                        'data_inicio' => $campo['data_inicio'],
                        'data_fim' => $campo['data_fim'],
                        'divergencia' => $campo['divergencia'],
                        'situacao' => $campo['situacao'],
                        'fatura' => $campo['fatura'],
                        'nota' => $campo['nota'],
                    ])
                ];
            }
            
            $saida[$fatura->transportadora_cnpj]['status_verifica'][] = !empty($fatura->faturaSituacao) ? 'Pendente' : 'Finalizada';
            $saida[$fatura->transportadora_cnpj]['fatura'] += $fatura_quantidade;
            $saida[$fatura->transportadora_cnpj]['valor_fatura'] += $valor_fatura;
            $saida[$fatura->transportadora_cnpj]['peso_fatura'] += $peso_fatura;
            $saida[$fatura->transportadora_cnpj]['notas_fatura'] += $notas_fatura;
            $saida[$fatura->transportadora_cnpj]['notas'] += $notas;
            $saida[$fatura->transportadora_cnpj]['documento_cobranca_array'][] = $documento_cobranca;
            $saida[$fatura->transportadora_cnpj]['percentual_frete'] = $saida[$fatura->transportadora_cnpj]['notas_fatura'] > 0 ? ($saida[$fatura->transportadora_cnpj]['valor_fatura']/$saida[$fatura->transportadora_cnpj]['notas_fatura'])*100 : '';

            $total['valor_fatura'] += $valor_fatura;
            $total['peso_fatura'] += $peso_fatura;
            $total['notas'] += $notas;
            $total['fatura'] += $fatura_quantidade;
            $total['notas_fatura'] += $notas_fatura;
            $total['percentual_frete'] = $total['notas_fatura'] > 0 ? ($total['valor_fatura']/$total['notas_fatura'])*100: '';
            $total['filters']['transportadora_cnpj']= $fatura->transportadora_cnpj;
        }

        if(!isset($saida)){
            return response()->json([
                'status' => 'sucess',
                'message' => '',
                'error' => '', 
                'response' => ['saida' => null, 'total' => 'null'],
            ]);    
        }

        foreach($saida as $key => $value){
            $NotasTransportadoraObj = NotasTransportadoraItem::whereIn('documento_cobranca_header', $saida[$key]['documento_cobranca_array'])
            ->whereNotNull('nota_id')
            ->get();

            $NotasSaidaObj = NotasNasajon::whereIn('id', $NotasTransportadoraObj->pluck('nota_id'));

            $NotasNasajon = $NotasSaidaObj->get();
            $valor_nota_saida = $NotasNasajon->pluck('valor')->sum();
            $peso_nota_saida = $NotasNasajon->pluck('pesoliquido')->sum();

            $NotasEntradaObj = NotasEntradasNasajon::whereIn('Identificador Documento', $NotasTransportadoraObj->pluck('nota_id'));
            $NotasNasajon = $NotasEntradaObj->get();
            $valor_nota_entrada = $NotasNasajon->pluck('Valor do Documento')->sum();
            $peso_nota_entrada = $NotasNasajon->pluck('Peso Líquido')->sum();

            $valor_nota = $valor_nota_saida + $valor_nota_entrada;
            $peso_nota = $peso_nota_saida + $peso_nota_entrada;

            $saida[$key]['status'] = (in_array('Finalizada', $saida[$key]['status_verifica'])) ? 'Pendente' : 'Finalizada';
            $saida[$key]['peso_notas'] = $peso_nota;
            $saida[$key]['valor_notas'] = $valor_nota;
            $saida[$key]['valor_fatura'] = parserValor($saida[$key]['valor_fatura']);
            $saida[$key]['peso_fatura'] = parserQtd($saida[$key]['peso_fatura']);
            $saida[$key]['notas_fatura'] = parserValor($saida[$key]['notas_fatura']);
            $saida[$key]['peso_notas'] = $saida[$key]['peso_notas'] > 0 ? parserQtd($saida[$key]['peso_notas']) : '';
            $saida[$key]['valor_notas'] = $saida[$key]['valor_notas'] > 0 ? parserValor($saida[$key]['valor_notas']) : '';
            $saida[$key]['percentual_frete'] = parserValor($saida[$key]['percentual_frete']).'%';

            $total['valor_notas'] += $valor_nota;
            $total['peso_notas'] += $peso_nota;
        }

        $total['valor_fatura'] = parserValor($total['valor_fatura']);
        $total['peso_fatura'] = parserQtd($total['peso_fatura']);
        $total['peso_notas'] = parserQtd($total['peso_notas']);
        $total['notas_fatura'] = parserValor($total['notas_fatura']);
        $total['valor_notas'] = parserValor($total['valor_notas']);
        $total['percentual_frete'] = parserValor($total['percentual_frete']).'%';
        $total['filters'] = encrypt($total['filters']);

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['saida' => $saida, 'total' => $total]
        ]);

    }

    public function modalFaturas(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        
        $filter = $request->only(['filters', 'total']);
		
        try{
            $fields = decrypt($filter['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $FaturasTransportadoraObj = NotasTransportadoraHeader::with(['notasFaturaSoma' => function($query) use ($fields){
            if(!empty($fields['uf_destinatario'])){
                $query->where('uf_destinatario', $fields['uf_destinatario']);
            }
        },'nomeTransportadora','faturaSituacao','notasFatura' => function($query) use ($fields){
            if(!empty($fields['uf_destinatario'])){
                $query->where('uf_destinatario', $fields['uf_destinatario']);
            }
            $query->with('notasSaida.estabelecimento_detalhes.cidadeDetalhes');
        }]);

        if(!empty($fields['uf_destinatario'])){
            $FaturasTransportadoraObj->whereHas('notasFaturaSoma', function($query) use ($fields){
                $query->where('uf_destinatario', $fields['uf_destinatario']);
            });
        }

        if($filter['total'] != 'true' || !empty($fields['transportadora'])){
            $FaturasTransportadoraObj->where('transportadora_cnpj', $fields['transportadora_cnpj']);
        }

        if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');

            $FaturasTransportadoraObj->whereBetween('data_emissao', [$data_inicio, $data_fim]);
        }

        if(!empty($fields['situacao'])){
            if($fields['situacao'] == 'pendentes'){
                $FaturasTransportadoraObj->doesntHave('faturaSituacao');
            }else{
                $FaturasTransportadoraObj->whereHas('faturaSituacao', function($query) use ($fields){
                    if($fields['situacao'] == 'aprovadas'){
                        $query->where('situacao', true);
                    }else{
                        $query->where('situacao', false);
                    }
                });
            }
        }

        if(!empty($fields['fatura'])){
            $FaturasTransportadoraObj->where('documento_cobranca','LIKE','%'.$fields['fatura'].'%');
        }

        
        if(!empty($fields['nota'])){
            $notas_nasajon_id = NotasNasajon::where('numero','LIKE','%'.$fields['nota'].'%')->get()->pluck('id')->toArray();
            $FaturasTransportadoraObj->whereHas('notasFatura',function($query) use ($notas_nasajon_id){
                $query->whereIn('nota_id',$notas_nasajon_id);
            });
        }

        $FaturasTransportadora = $FaturasTransportadoraObj->get();
        $aliquota = AliquotaPreco::get();

        $total = [
            'fatura_quantidade' => 0,
            'notas_fatura' => 0,
            'valor_fatura' => 0,
            'peso_fatura' => 0,
            'notas' => 0,
            'valor_notas' => 0,
            'peso_notas' => 0,
            'percentual_frete' => 0
        ];

        foreach($FaturasTransportadora as $fatura){
            $fatura_quantidade = $fatura->documento_quantidade;
            $peso_fatura = 0;
            $notas = 0;
            $notas_fatura = 0;
            $valor_fatura = 0;
            $divergencia = true;
            $situacao = '';
            $status = '';

            if(isset($fatura->faturaSituacao) && !empty($fatura->faturaSituacao)){
                $situacao = ($fatura->faturaSituacao->situacao ==  true) ? 'Aprovada' : 'Reprovada';
            }

            if(empty($fatura->faturaSituacao)){
                $status = 'Pendente';
            }else{
                $status = 'Finalizada';
            }

            if($fields['divergencia'] == 'com_divergencia'){
                foreach($fatura->notasFatura as $fatura_nota){
                    if(isset($fatura_nota->notasSaida)){
                        $uf_origem = $fatura_nota->notasSaida->estabelecimento_detalhes->cidadeDetalhes->uf;
                        $percentual_aliquota = $aliquota->where('origem',$uf_origem)->where('estado',$fatura_nota->uf_destinatario)->first();

                        if(!empty($percentual_aliquota)){
                            if(!empty($fields['uf_destinatario'])){
                                $valor_fatura = $fatura->notasFaturaSoma->total_frete;
                            }else{
                                $valor_fatura = $fatura->valor_total;
                            }
                            
                            $peso_fatura =!empty($fatura->notasFaturaSoma->peso_total) ? $fatura->notasFaturaSoma->peso_total:0;
                            $notas = !empty($fatura->notasFaturaSoma->nota_quantidade) ? $fatura->notasFaturaSoma->nota_quantidade:0;
                            $notas_fatura = !empty($fatura->notasFaturaSoma->valor_total) ? $fatura->notasFaturaSoma->valor_total:0;

                            $percentual_frete = ($notas_fatura > 0) ? $valor_fatura / $notas_fatura * 100 : 0;

                            if($percentual_frete > $percentual_aliquota->frete_adicional){
                                $divergencia = true;
                            }else{
                                $divergencia = false;
                            }

                        }else{
                            $divergencia = false;
                        }
                    }else{
                        $divergencia = false;
                    }
                }

                if($divergencia == false){
                    continue;
                }

            }if($fields['divergencia'] == 'sem_divergencia'){
                foreach($fatura->notasFatura as $fatura_nota){
                    if(isset($fatura_nota->notasSaida)){
                        $uf_origem = $fatura_nota->notasSaida->estabelecimento_detalhes->cidadeDetalhes->uf;
                        $percentual_aliquota = $aliquota->where('origem',$uf_origem)->where('estado',$fatura_nota->uf_destinatario)->first();

                        if(!empty($percentual_aliquota)){
                            if(!empty($fields['uf_destinatario'])){
                                $valor_fatura = $fatura->notasFaturaSoma->total_frete;
                            }else{
                                $valor_fatura = $fatura->valor_total;
                            }
                            
                            $peso_fatura = !empty($fatura->notasFaturaSoma->peso_total) ? $fatura->notasFaturaSoma->peso_total:0;
                            $notas = !empty($fatura->notasFaturaSoma->nota_quantidade) ? $fatura->notasFaturaSoma->nota_quantidade:0;
                            $notas_fatura = !empty($fatura->notasFaturaSoma->valor_total) ? $fatura->notasFaturaSoma->valor_total:0;

                            $percentual_frete = ($notas_fatura > 0) ? $valor_fatura / $notas_fatura * 100 : 0;

                            if($percentual_frete <= $percentual_aliquota->frete_adicional){
                                $divergencia = false;
                            }else{
                                $divergencia = true;
                            }

                        }else{
                            $divergencia = true;
                        }
                    }else{
                        $divergencia = true;
                    }
                }

                if($divergencia == true){
                    continue;
                }

            }else{
                if(!empty($fields['uf_destinatario'])){
                    $valor_fatura = $fatura->notasFaturaSoma->total_frete;
                }else{
                    $valor_fatura = $fatura->valor_total;
                }

                foreach($fatura->notasFatura as $fatura_nota){
                    if(isset($fatura_nota->notasSaida)){
                        $uf_origem = $fatura_nota->notasSaida->estabelecimento_detalhes->cidadeDetalhes->uf;
                        $percentual_aliquota = $aliquota->where('origem',$uf_origem)->where('estado',$fatura_nota->uf_destinatario)->first();

                        if(!empty($percentual_aliquota)){
                            $notas = !empty($fatura->notasFaturaSoma->nota_quantidade) ? $fatura->notasFaturaSoma->nota_quantidade:0;
                            $notas_fatura = !empty($fatura->notasFaturaSoma->valor_total) ? $fatura->notasFaturaSoma->valor_total:0;

                            $percentual_frete = ($notas_fatura > 0) ? $valor_fatura / $notas_fatura * 100 : 0;

                            if($percentual_frete > $percentual_aliquota->frete_adicional){
                                $divergencia = true;
                            }else{
                                $divergencia = false;
                            }

                        }else{
                            $divergencia = null;
                        }
                    }else{
                        $divergencia = null;
                    }
                }
                
                $peso_fatura = !empty($fatura->notasFaturaSoma->peso_total) ? $fatura->notasFaturaSoma->peso_total:0;
                $notas = !empty($fatura->notasFaturaSoma->nota_quantidade) ? $fatura->notasFaturaSoma->nota_quantidade:0;
                $notas_fatura = !empty($fatura->notasFaturaSoma->valor_total) ? $fatura->notasFaturaSoma->valor_total:0;
            }


            $saida[] = [
                'transportadora' => isset($fatura->nomeTransportadora->nome) ? $fatura->nomeTransportadora->nome.' - '.$fatura->transportadora_cnpj : $fatura->transportadora_cnpj,
                'fatura' => $fatura->documento_cobranca,
                'valor_fatura' => parserValor($valor_fatura),
                'peso_fatura' => $peso_fatura,
                'notas' => $notas,
                'notas_fatura' => parserValor($notas_fatura),
                'valor_notas' => 0,
                'peso_notas' =>  0,
                'situacao' => $situacao,
                'status' => $status,
                'id' => encrypt($fatura->id),
                'divergencia' => $divergencia,
                'percentual_frete' => $notas_fatura > 0 ? (parserValor(($valor_fatura/$notas_fatura)*100)).'%' : '',
                'filters' => encrypt([
                    'fatura' => $fatura->documento_cobranca,
                ])
            ];

            $total['fatura_quantidade'] += $fatura_quantidade;
            $total['valor_fatura'] += $valor_fatura;
            $total['peso_fatura'] += $peso_fatura;
            $total['notas'] += $notas;
            $total['notas_fatura'] += $notas_fatura;
            $total['percentual_frete'] = $total['notas_fatura'] > 0 ? ($total['valor_fatura']/$total['notas_fatura'])*100 : '';
        }

        foreach($saida as $key => $value){
            $NotasTransportadoraObj = NotasTransportadoraItem::where('documento_cobranca_header', $saida[$key]['fatura'])
            ->whereNotNull('nota_id')
            ->get();

            $NotasSaidaObj = NotasNasajon::whereIn('id', $NotasTransportadoraObj->pluck('nota_id'));

            $NotasNasajon = $NotasSaidaObj->get();
            $valor_notas_saida = $NotasNasajon->pluck('valor')->sum();
            $peso_notas_saida = $NotasNasajon->pluck('pesoliquido')->sum();
        
            $NotasEntradaObj = NotasEntradasNasajon::whereIn('Identificador Documento', $NotasTransportadoraObj->pluck('nota_id'));
            $NotasNasajon = $NotasEntradaObj->get();
            $valor_notas_entrada = $NotasNasajon->pluck('Valor do Documento')->sum();
            $peso_notas_entrada = $NotasNasajon->pluck('Peso Líquido')->sum();

            $valor_notas = $valor_notas_saida + $valor_notas_entrada;
            $peso_notas = $peso_notas_saida + $peso_notas_entrada;

            $saida[$key]['peso_fatura'] = $saida[$key]['peso_fatura'] > 0 ? parserQtd($saida[$key]['peso_fatura']) : '';
            $saida[$key]['peso_notas'] = $peso_notas;
            $saida[$key]['valor_notas'] = ($valor_notas > 0 ) ? parserValor($valor_notas) : '';
            $saida[$key]['peso_notas'] = $saida[$key]['peso_notas'] > 0 ? parserQtd($saida[$key]['peso_notas']) : '';

            $total['valor_notas'] += $valor_notas;
            $total['peso_notas'] += $peso_notas;
        }
        
        return view('programs.consulta_fatura_transportadora.modal.faturas')->with(['dados' => $saida, 'total' => $total]);

    }

    public function modalExibirFatura(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        try{
            $field = $request->only(['fatura']);
       }catch(\Exception $e){
           $return = [
               'status' => 'error',
               'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
               'error' => '', 
               'response' => '',
           ];
           return response()->json($return);
       }

        $FaturasTransportadoraObj = NotasTransportadoraHeader::with('nomeTransportadora')
        ->where('documento_cobranca', $field['fatura']);

        $dados = $FaturasTransportadoraObj->first();

        if ($dados->tipo_documento_cobranca === '0'){
            $filial_emissora_documento = 'Nota Fiscal Fatura';
        }
        elseif ($dados->tipo_documento_cobranca === '1'){
            $filial_emissora_documento = 'Romaneio';
        }else{
            $filial_emissora_documento = '';
        }
        if($dados->tipo_cobranca === 'BCO'){
            $tipo_cobranca = 'Cobrança Bancária';
        }
        elseif ($dados->tipo_cobranca === 'CAR'){
            $tipo_cobranca = 'Carteira';
        }else{
            $tipo_cobranca = '';
        }
        if($dados->acao_documento === 'I'){
            $acao_documento = 'Incluir';
        }
        elseif ($dados->acao_documento === 'E'){
            $acao_documento = 'Excluir/Cancelar';
        }else{
            $acao_documento = '';
        }

        $header_faturas_array = [
                'transportadora_cnpj' => $dados->transportadora_cnpj,
                'transportadora_nome' => (!empty($dados->nomeTransportadora->nome)) ? $dados->nomeTransportadora->nome : '',
                'filial_emissora_documento' => $dados->filial_emissora_documento,
                'tipo_documento_cobranca' => $filial_emissora_documento,
                'documento_cobranca_serie' => $dados->documento_cobranca.' '.$dados->documento_cobranca_serie,
                'data_emissao' => !empty($dados->data_emissao) ? parserData($dados->data_emissao) : '',
                'data_vencimento' => !empty($dados->data_vencimento) ? parserData($dados->data_vencimento) : '',
                'valor_total' => parserValor($dados->valor_total),
                'tipo_cobranca' => $tipo_cobranca,
                'percentual_multa_atraso' => parserValor($dados->percentual_multa_atraso),
                'valor_juros_dia_atraso' =>  parserValor($dados->valor_juros_dia_atraso),
                'data_limite_pagamento_desconto' =>  !empty($dados->data_limite_pagamento_desconto) ? parserData($dados->data_limite_pagamento_desconto) : '',
                'valor_desconto' =>  parserValor($dados->valor_desconto),
                'codigo_banco' => $dados->codigo_banco,
                'nome_banco' => $dados->nome_banco,
                'numero_agencia' => $dados->numero_agencia,
                'agencia_digito' => $dados->agencia_digito,
                'conta_corrente' => $dados->conta_corrente,
                'conta_corrente_digito' => $dados->conta_corrente_digito,
                'acao_documento' => $acao_documento,
                'identificacao_pre_fatura_cliente' => $dados->identificacao_pre_fatura_cliente,
                'identificacao_complementar_pre_fatura_cliente' => $dados->identificacao_complementar_pre_fatura_cliente,
                'cfop' => $dados->cfop,
                'chave_acesso_nf' => $dados->chave_acesso_nf,
                'chave_acesso_nf_com_dv' => $dados->chave_acesso_nf_com_dv,
                'numero_protocolo_nf' => $dados->numero_protocolo_nf,
                'valor_total_icms' => parserValor($dados->valor_total_icms),
                'aliquota_icms' => parserValor($dados->aliquota_icms),
                'base_calculo_icms' => parserValor($dados->base_calculo_icms),
                'valor_total_iss' => parserValor($dados->valor_total_iss),
                'aliquota_iss' => parserValor($dados->aliquota_iss),
                'base_calculo_iss_st' => parserValor($dados->base_calculo_iss_st),
                'valor_total_icms_st' => parserValor($dados->valor_total_icms_st),
                'aliquota_iss_st' => parserValor($dados->aliquota_iss_st),
                'aliquota_icms_st' => parserValor($dados->aliquota_icms_st),
                'base_calculo_icms_st' => parserValor($dados->base_calculo_icms_st),
                'valor_total_ir' => parserValor($dados->valor_total_ir),
                'valor_total_nota' => 0,
                'total_nota' => 0,
                'peso' => 0
            ];


            $NotasTransportadoraObj = NotasTransportadoraItem::with(['notasEntrada', 'notasSaida'])
            ->with(['cliente', 'fornecedor'])
            ->where('documento_cobranca_header', $field['fatura']);

            $NotasTransportadora = $NotasTransportadoraObj->get();

            $total = [
                'nota' => 0,
                'valor_fatura' => 0,
                'peso_fatura' => 0,
                'valor_nota' => 0,
                'peso_nota' => 0,
                'frete' => 0
            ];

            $estabelecimentos = returnEmpresasNasajonView();

            foreach($NotasTransportadora as $nota){
                $valor_fatura = $nota->valor_nota;
                $peso = $nota->peso_nota;

                $nota_quantidade = 1;

                if(isset($nota->notasSaida->valor)){
                    $valor_nota = $nota->notasSaida->valor;
                }elseif(isset($nota->notasEntrada['Valor do Documento'])){
                    $valor_nota = $nota->notasEntrada['Valor do Documento'];
                }else{
                    $valor_nota = 0;
                }

                if(isset($nota->notasSaida->pesoliquido)){
                    $peso_nota = $nota->notasSaida->pesoliquido;
                    $peso_nota = intval($peso_nota * 100) / 100;
                }elseif(isset($nota->notasEntrada['Peso Líquido'])){
                    $peso_nota = $nota->notasEntrada['Peso Líquido'];
                    $peso_nota = intval($peso_nota * 100) / 100;
                }else{
                    $peso_nota = 0;
                }

                $estabelecimento_cnpj = NasajonEstabelecimento::where(DB::raw('CONCAT(raizcnpj,ordemcnpj)'), str_replace(['-', '/', '.'], '',$nota->destinatario_cnpj))
                ->exists();

                if($estabelecimento_cnpj === true){
                    if(isset($nota->fornecedor->cnpj_cpf)){
                        $cliente_fornecedor = $nota->fornecedor->nome.' - '.$nota->fornecedor->cnpj_cpf;
                    }
                    elseif(isset($nota->cliente->cpf_cnpj)){
                        $cliente_fornecedor = $nota->cliente->nome.' - '.$nota->cliente->cpf_cnpj;
                    }else{
                        $cliente_fornecedor = $nota->emissor_cnpj;
                    }
                }else{
                    if(isset($nota->cliente->nome)){
                        $cliente_fornecedor = $nota->cliente->nome.' - '.$nota->cliente->cpf_cnpj;
                    }else{
                        $cliente_fornecedor = $nota->destinatario_cnpj;
                    }
                }

                if(isset($nota->notasSaida->estabelecimento_codigo)){
                    $estabelecimento = $estabelecimentos[(integer)$nota->notasSaida->estabelecimento_codigo];
                }elseif(isset($nota->notasEntrada['Estabelecimento'])){
                    $estabelecimento = $estabelecimentos[(integer)$nota->notasEntrada['Estabelecimento']];
                }else{
                    $estabelecimento = $estabelecimento_cnpj === true ? $nota->cliente->nome.' - '.$nota->cliente->cpf_cnpj : $nota->fornecedor->nome.' - '.$nota->fornecedor->cnpj_cpf;
                }

                if($nota->devolucao_nota == '3' || $nota->devolucao_nota == '1' || $nota->devolucao_nota == 'S'){
                    $tipo = 'D';
                }else{
                    $tipo = 'N';
                }

                if($tipo == 'N'){
                    $tipo_descricao = 'Normal';
                }else{
                    $tipo_descricao =  'Devolução';
                }

                $itens_array[] = [
                    'estabelecimento' => $estabelecimento,
                    'nota' => $nota->numero_nota,
                    'tipo' => $tipo,
                    'tipo_descricao' => $tipo_descricao,
                    'cliente_fornecedor' => $cliente_fornecedor,
                    'valor_fatura' => parserValor($valor_fatura),
                    'peso_fatura' => parserQtd($peso),
                    'nota_id_entrada' => (isset($nota->notasEntrada['Identificador Documento']) && !empty($nota->fornecedor) || isset($nota->notasEntrada['Identificador Documento']) && $tipo_descricao == 'Devolução') ? $nota->notasEntrada['Identificador Documento'] : '',
                    'nota_id_saida' => (isset($nota->notasSaida->id) && $tipo_descricao == 'Normal') ? $nota->notasSaida->id : '',
                    'valor_nota' => $valor_nota > 0 ? parserValor($valor_nota) : '',
                    'peso_nota' => $peso_nota > 0 ? parserQtd($peso_nota) : '',
                    'frete' => parserValor($nota->valor_frete)
                ];

                $total['nota'] += $nota_quantidade;
                $total['valor_fatura'] += $valor_fatura;
                $total['peso_fatura'] += $peso;
                $total['valor_nota'] += $valor_nota ;
                $total['peso_nota'] += $peso_nota;
                $total['frete'] += $nota->valor_frete;

            }

            $header_faturas_array['valor_total_nota'] = parserValor($total['valor_fatura']);
            $header_faturas_array['total_nota'] = $total['nota'];
            $header_faturas_array['peso'] = parserQtd($total['peso_fatura']);
            $header_faturas_array['frete'] = parserValor($total['frete']);
            $total['frete'] = parserValor($total['frete']);

        return view('programs.consulta_fatura_transportadora.modal.exibir_fatura')->with(['header_fatura_array' => $header_faturas_array, 'itens_array' => $itens_array, 'quantidade_total' => $total]);

    }


    private function formatarValor($linha, $posicao, $formato){
        $valor = substr($linha, $posicao, $formato);
        $posicao_inicial = substr($valor, 0, 1);
        while ($posicao_inicial == 0) {
            if($formato > 0){
                $valor = substr($valor,1);
                $posicao_inicial = substr($valor, 0, 1);
                $formato -= 1;
            }else{
                $posicao_inicial = -1;
            }
        }
        if(strlen($valor) > 0){
            $valor = substr_replace($valor, '.', -2, 0);
        }else{
            $valor = '';
        }
        return $valor;
    }

    private function formatarData($linha, $posicao, $formato){
        $data = substr($linha, $posicao, $formato);
        $data_br = mask($data,'##/##/####');
        if(validateDate($data_br, 'd/m/Y') == true){
            $formato_data = mask($data,'##-##-####');
            $data = Carbon::createFromFormat('d-m-Y', $formato_data)->format('Y-m-d');
        }
        if($data_br === '00/00/0000'){
            $data = '';
        }

        return $data;
    }

    private function formatarDataHora($linha, $posicao, $formato){
        $dataHora = substr($linha, $posicao, $formato);
        $tamanho = $formato - 4;
        $data_br = substr($linha, $posicao, $tamanho);
        $data_br = mask($data_br,'##/##/####');
        if(validateDate($data_br, 'd/m/Y') == true){
            $formatoDataHora = mask($dataHora,'##-##-#### ##:##');
            $dataHora = Carbon::createFromFormat('d-m-Y H:i', $formatoDataHora)->format('Y-m-d H:i');
        }else{
            $dataHora = '';
        }

        return $dataHora;
    }

    private function formatarNotaSerie($linha, $posicao, $formato){
        $codigo = substr($linha,$posicao,$formato);
        $codigo = trim($codigo);

        $tamanho = strlen($codigo);

        while ($tamanho < $formato){
            $codigo = substr_replace($codigo,'0'.$codigo,0);
            $tamanho = strlen($codigo);
        }
        return $codigo;
    }
    
    public function LeituraArquivoTxt($path_file){

        try {
            $dados = file($path_file);
        } catch (\Exception $e) {
            return  'erro';
        }

        rename($path_file, $path_file.'.pro');

        foreach($dados as $linha){
            $linha = trim($linha);
            $registro = substr($linha,0,3);

            if($registro == 541){
                $transportadoraCnpj = substr($linha,3,14);
                $transportadoraCnpj = mask($transportadoraCnpj, '##.###.###/####-##');

                $transportadora_entrega = [
                    'registro' => $registro,
                    'transportadora_cnpj' => $transportadoraCnpj
                ];
            }

            if($registro == 542){
                $cnpj = substr($linha,3,14);
                $ordem_cnpj = substr($linha,11,6);
                $serie = $this->formatarNotaSerie($linha,17,3);
                $nota = $this->formatarNotaSerie($linha,20,9);

                $codigo_ocorrencia = substr($linha,29,3);
                $dataHora = $this->formatarDataHora($linha,32,12);

                $codigo_observacao = substr($linha,44,2);

                if($codigo_observacao == '00'){
                    $codigo_observacao = '03';
                }

                $numero_romaneio = substr($linha,46,20);
                $numero_sap_shipment = substr($linha,66,20);
                $numero_sap_account = substr($linha,86,20);
                $outro_numero_sap_account = substr($linha,106,20);
                $filial_emissora = substr($linha,126,10);
                $serie_do_conhecimento = substr($linha,136,5);
                $numero_do_conhecimento = substr($linha,141,12);
                $indicacao_tipo_entrega = substr($linha,153,1);
                $cod_emp_emissora_nf = substr($linha,154,5);
                $cod_filial_emp_emissora_nf = substr($linha,159,5);

                $dataHora_destino_nf = $this->formatarDataHora($linha,164,12);
                $dataHora_InicioDescarregamento = $this->formatarDataHora($linha,176,12);
                $dataHora_TerminoDescarregamento = $this->formatarDataHora($linha,188,12);
                $dataHora_data_saida_destino = $this->formatarDataHora($linha,200,12); 

                $cnpj_emissor_nf_devolucao = substr($linha,212,14);
                $cnpj_emissor_nf_devolucao = trim($cnpj_emissor_nf_devolucao);

                if($cnpj_emissor_nf_devolucao != '00000000000000' && strlen($cnpj_emissor_nf_devolucao) == 14){
                    $cnpj_emissor_nf_devolucao = mask($cnpj_emissor_nf_devolucao, '##.###.###/####-##');
                }else{
                    $cnpj_emissor_nf_devolucao = '';
                }
                
                $serie_nf_devolucao =  substr($linha,226,3);
                $numero_nf_devolucao = substr($linha,229,9);
                $numero_nf_devolucao = trim($numero_nf_devolucao);
                
                if($numero_nf_devolucao != '000000000' && strlen($numero_nf_devolucao) == 9){
                    $numero_nf_devolucao;
                }else{
                    $numero_nf_devolucao = '';
                }

                $saida[] = array_merge($transportadora_entrega, [
                    'cnpj' => $cnpj,
                    'ordem_cnpj' => $ordem_cnpj,
                    'serie' => $serie,
                    'nota' => $nota,
                    'codigo_ocorrencia' => $codigo_ocorrencia,
                    'dataHora' => $dataHora,
                    'codigo_observacao' => $codigo_observacao,
                    'numero_romaneio' => $numero_romaneio,
                    'numero_sap_shipment' => $numero_sap_shipment,
                    'numero_sap_account' => $numero_sap_account,
                    'outro_numero_sap_account' => $outro_numero_sap_account,
                    'filial_emissora' => $filial_emissora,
                    'serie_do_conhecimento' => $serie_do_conhecimento,
                    'numero_do_conhecimento' => $numero_do_conhecimento,
                    'indicacao_tipo_entrega' => $indicacao_tipo_entrega,
                    'cod_emp_emissora_nf' => $cod_emp_emissora_nf,
                    'cod_filial_emp_emissora_nf' => $cod_filial_emp_emissora_nf,
                    'dataHora_chegada_destino_nf' => $dataHora_destino_nf,
                    'dataHora_inicio_descarregamento_destino' => $dataHora_InicioDescarregamento,
                    'dataHora_termino_descarregamento_destino' => $dataHora_TerminoDescarregamento,
                    'dataHora_data_saida_destino' => $dataHora_data_saida_destino,
                    'cnpj_emissor_nf_devolucao' =>$cnpj_emissor_nf_devolucao,
                    'serie_nf_devolucao' => $serie_nf_devolucao,
                    'numero_nf_devolucao' => $numero_nf_devolucao
                ]);
            }

            if($registro == 551){
                $transportadora_cnpj = substr($linha,3,14);
                $transportadora_cnpj = mask($transportadora_cnpj, '##.###.###/####-##');

                $transportadora = [
                    'registro' => $registro,
                    'transportadora_cnpj' => $transportadora_cnpj
                ];

            }
            if($registro == 552){
                $filial_emissora_documento = substr($linha,3,10);
                $tipo_documento = substr($linha,13,1);
                $documento_cobranca_serie = substr($linha,14,3);
                $documento_cobranca = substr($linha,17,10);
                $data_emissao = $this->formatarData($linha,27,8);
                $data_vencimento = $this->formatarData($linha,35,8);
                $valor_total = $this->formatarValor($linha,43,15);
                $tipo_cobrança = substr($linha,58,3);
                $percentual_multa_atraso = $this->formatarValor($linha,61,4);
                $valor_juros_dia_atraso = $this->formatarValor($linha,65,15);
                $data_limite_pagamento_desconto = $this->formatarData($linha,80,8);
                $valor_desconto = $this->formatarValor($linha,88,15);
                $codigo_banco = substr($linha,103,5);

                if($codigo_banco == '00000'){
                    $codigo_banco = '';
                }

                $nome_banco = substr($linha,108,30);
                $numero_agencia = substr($linha,138,4);

                if($numero_agencia == '0000'){
                    $numero_agencia = '';
                }

                $agencia_digito = substr($linha,142,1);
                $conta_corrente = substr($linha,143,10);

                if($conta_corrente == '0000000000'){
                    $conta_corrente = '';
                }

                $conta_corrente_digito = substr($linha,153,2);
                $acao_documento = substr($linha,155,1);
                $identificacao_pre_fatura_cliente = substr($linha,156,10);

                if($identificacao_pre_fatura_cliente == '0000000000'){
                    $identificacao_pre_fatura_cliente = '';
                }

                $identificacao_complementar_pre_fatura_cliente = substr($linha,166,20);
                $cfop = substr($linha,186,5);
                $chave_acesso_nf = substr($linha,191,9);

                if($chave_acesso_nf == '000000000'){
                    $chave_acesso_nf = '';
                }

                $chave_acesso_nf_com_dv = substr($linha,200,45);
                $numero_protocolo_nf = substr($linha,245,15);

                $registro_header = array_merge($transportadora, [
                    'filial_emissora_documento' => $filial_emissora_documento,
                    'tipo_documento_cobranca' => $tipo_documento,
                    'documento_cobranca_serie' => $documento_cobranca_serie,
                    'documento_cobranca' => $documento_cobranca,
                    'data_emissao' => $data_emissao,
                    'data_vencimento' => $data_vencimento,
                    'valor_total' => $valor_total,
                    'tipo_cobranca' => $tipo_cobrança,
                    'percentual_multa_atraso' => $percentual_multa_atraso,
                    'valor_juros_dia_atraso' => $valor_juros_dia_atraso,
                    'data_limite_pagamento_desconto' => $data_limite_pagamento_desconto,
                    'valor_desconto' => $valor_desconto ,
                    'codigo_banco' => $codigo_banco,
                    'nome_banco' => $nome_banco,
                    'numero_agencia' => $numero_agencia,
                    'agencia_digito' => $agencia_digito,
                    'conta_corrente' => $conta_corrente,
                    'conta_corrente_digito' => $conta_corrente_digito,
                    'acao_documento' => $acao_documento,
                    'identificacao_pre_fatura_cliente' => $identificacao_pre_fatura_cliente,
                    'identificacao_complementar_pre_fatura_cliente' => $identificacao_complementar_pre_fatura_cliente,
                    'cfop' => $cfop,
                    'chave_acesso_nf' => $chave_acesso_nf,
                    'chave_acesso_nf_com_dv' => $chave_acesso_nf_com_dv,
                    'numero_protocolo_nf' => $numero_protocolo_nf
                ]);
            }

            if($registro == 553){
                $valor_total_icms = $this->formatarValor($linha,3,15);
                $aliquota_icms = $this->formatarValor($linha,18,5);
                $base_calculo_icms = $this->formatarValor($linha,23,15);
                $valor_total_iss = $this->formatarValor($linha,38,15);
                $aliquota_iss = $this->formatarValor($linha,53,5);
                $base_calculo_iss = $this->formatarValor($linha,58,15);
                $valor_total_icms_st = $this->formatarValor($linha,73,15);
                $aliquota_icms_st = $this->formatarValor($linha,88,5);
                $base_calculo_icms_st = $this->formatarValor($linha,93,15);
                $valor_total_ir = $this->formatarValor($linha,108,15);

                $saida[] = array_merge($registro_header, [
                    'valor_total_icms' => $valor_total_icms,
                    'aliquota_icms' => $aliquota_icms,
                    'base_calculo_icms' => $base_calculo_icms,
                    'valor_total_iss' => $valor_total_iss,
                    'aliquota_iss' => $aliquota_iss,
                    'base_calculo_iss_st' => $base_calculo_iss,
                    'valor_total_icms_st' => $valor_total_icms_st,
                    'aliquota_iss_st' => $valor_total_icms_st,
                    'aliquota_icms_st' => $aliquota_icms_st,
                    'base_calculo_icms_st' => $base_calculo_icms_st,
                    'valor_total_ir' => $valor_total_ir
                ]);
            }

            if($registro == 555){
                $filial_emissora_documento = substr($linha,3,10);
                $conhecimento_serie = substr($linha,13,5);
                $numero_conhecimento = substr($linha,18,12);
                $valor_frete = $this->formatarValor($linha,31,14);
                $data_emissao_conhecimento = $this->formatarData($linha,45,8);
                $emissor_cnpj = substr($linha,53,14);

                $destinatario_cnpj = substr($linha,67,14);

                $verifica_estabelecimento = NasajonEstabelecimento::where(DB::raw('concat(raizcnpj,ordemcnpj)'),'ilike',$destinatario_cnpj)
                ->orWhere(DB::raw('concat(raizcnpj,ordemcnpj)'),'ilike',$emissor_cnpj)
                ->first();

                if($emissor_cnpj != '00000000000000'){
                    $emissor_cnpj = mask($emissor_cnpj, '##.###.###/####-##');
                }

                $estabelecimento_cnpj = (!empty($verifica_estabelecimento)) ? $verifica_estabelecimento->raizcnpj.$verifica_estabelecimento->ordemcnpj : '';

                if($destinatario_cnpj != '00000000000000'){
                    $destinatario_cnpj = mask($destinatario_cnpj, '##.###.###/####-##');
                }

                $ordem_cnpj = (!empty($estabelecimento_cnpj)) ? substr($estabelecimento_cnpj,8) : '';
                $filial_transportadora_cnpj = substr($linha,81,14);

                if($filial_transportadora_cnpj != '00000000000000'){
                    $filial_transportadora_cnpj = mask($filial_transportadora_cnpj, '##.###.###/####-##');
                }

                $uf_local_coleta = substr($linha,95,2);
                $uf_unidade_emissora = substr($linha,97,2);
                $uf_destinatario = substr($linha,99,2);
                $conta_razao = substr($linha,101,10);
                $codigo_iva = substr($linha,111,2);
                $numero_romaneio_conhecimento = substr($linha,113,20);
                $numero_sap_conhecimento = substr($linha,133,20);
                $numero_sap_shipment_conhecimento = substr($linha,153,20);
                $numero_sap_account_conhecimento = substr($linha,173,20);
                $devolucao  = substr($linha,193,1);

                $registro_itens = [
                    'registro' => $registro,
                    'filial_emissora_documento' => $filial_emissora_documento,
                    'conhecimento_serie' => $conhecimento_serie,
                    'numero_conhecimento' => $numero_conhecimento,
                    'valor_frete' => $valor_frete,
                    'data_emissao_conhecimento' => $data_emissao_conhecimento,
                    'emissor_cnpj' => $emissor_cnpj,
                    'destinatario_cnpj' => $destinatario_cnpj,
                    'estabelecimento_cnpj' => $estabelecimento_cnpj,
                    'ordem_cnpj' => $ordem_cnpj,
                    'filial_transportadora_cnpj' => $filial_transportadora_cnpj,
                    'uf_local_coleta' => $uf_local_coleta,
                    'uf_unidade_emissora' => $uf_unidade_emissora,
                    'uf_destinatario' => $uf_destinatario,
                    'conta_razao' => $conta_razao,
                    'codigo_iva' => $codigo_iva,
                    'numero_romaneio_conhecimento' => $numero_romaneio_conhecimento,
                    'numero_sap_conhecimento' => $numero_sap_conhecimento,
                    'numero_sap_shipment_conhecimento' => $numero_sap_shipment_conhecimento,
                    'numero_sap_account_conhecimento' => $numero_sap_account_conhecimento,
                    'devolucao' => $devolucao
                ];
            }

            if($registro == 556){
                $nota_serie = $this->formatarNotaSerie($linha,3,3);
                $numero_nota = substr($linha,6,9);
                $data_emissao = $this->formatarData($linha,15,8);
                $peso_nota = $this->formatarValor($linha,23,7);
                $valor_nota = $this->formatarValor($linha,30,15);
                $romaneio_nota = substr($linha,59,20);
                $numero_sap_shipment_nota = substr($linha,79,20);
                $numero_sap_account_nota = substr($linha,99,20);
                $outro_numero_sap_account_nota = substr($linha,119,20);
                $devolucao_nota = substr($linha,139,1);

                $saida[] = array_merge($registro_itens, [
                    'nota_serie' => $nota_serie,
                    'numero_nota' => $numero_nota,
                    'data_emissao' => $data_emissao,
                    'peso_nota' => $peso_nota,
                    'valor_nota' => $valor_nota,
                    'romaneio_nota' => $romaneio_nota,
                    'numero_sap_shipment_nota' => $numero_sap_shipment_nota,
                    'numero_sap_account_nota' => $numero_sap_account_nota,
                    'outro_numero_sap_account_nota' => $outro_numero_sap_account_nota,
                    'devolucao_nota' => $devolucao_nota,
                    'documento_cobranca_header' => $documento_cobranca
                ]);
            }
        }

        rename($path_file.'.pro', $path_file.'.bkp');

        foreach($saida as $key => $array){
            $retorno[] = array_map('trim', $array);
            if($saida[$key]['registro'] == 555){
                $numero_conhecimento = trim($saida[$key]['numero_conhecimento']);
                if(isset($divisor[$numero_conhecimento])){
                    $divisor[$numero_conhecimento] += 1;
                }else{
                   $divisor[$numero_conhecimento] = 1;
                }
             }
        }

        foreach($retorno as $chave => $value){
            if($retorno[$chave]['registro'] == 555){
                if(isset($divisor[$retorno[$chave]['numero_conhecimento']])){
                    $retorno[$chave]['valor_frete'] = $retorno[$chave]['valor_frete']/$divisor[$retorno[$chave]['numero_conhecimento']];
                }
            }
        }

        return $retorno;
    }

    public function SalvarDadosTransportadora($path_file){

        $arquivo_text = $this->LeituraArquivoTxt($path_file);
        
        if(empty($arquivo_text)){
            throw new \Exception('erro: arquivo txt não encontrado!');
        }
        
        foreach($arquivo_text as $key => $value){
            if($value['registro'] == 541){
                $NotasNasajonObj = NotasNasajon::where('numero', $value['nota'])
                ->where('estabelecimento_cnpj', $value['cnpj']);

                $NasajonEstabelecimento = NasajonEstabelecimento::where('ordemcnpj', $value['ordem_cnpj'])
                ->first();

                if(isset($NasajonEstabelecimento->estabelecimento)){
                    $NotasEntradaObj = NotasEntradasNasajon::where('Número do Documento', $value['nota'])
                    ->where('Identificador Estabelecimento', $NasajonEstabelecimento->estabelecimento)
                    ->first();
                }
                
                $OcorrenciasDeEntregaObj = new OcorrenciasDeEntrega;
                $OcorrenciasDeEntregaObj->nota_serie = $value['serie'];
                $OcorrenciasDeEntregaObj->numero_nota = $value['nota'];
                $OcorrenciasDeEntregaObj->codigo_ocorrencia = $value['codigo_ocorrencia'];
                
                if(!empty($value['dataHora'])){
                    $OcorrenciasDeEntregaObj->data_ocorrencia = $value['dataHora'];
                }

                if($NotasNasajonObj->exists()){
                    $NotasSaida = $NotasNasajonObj->first();
                    try{
                        $OcorrenciasDeEntregaObj->nota_id = $NotasSaida->id;
                    }catch (\Exception $e) {
                        $linha = $key +4;
                        Log::error($path_file.' Linha: '.$linha);
                    }
                }else{
                    try{
                        $OcorrenciasDeEntregaObj->nota_id = $NotasEntradaObj['Identificador Documento'];
                    }catch (\Exception $e) {
                        $linha = $key +4;
                        Log::error($path_file.' Linha: '.$linha);
                    }
                }
                
                $OcorrenciasDeEntregaObj->codigo_observacao = $value['codigo_observacao'];
                $OcorrenciasDeEntregaObj->numero_romaneio = $value['numero_romaneio'];
                $OcorrenciasDeEntregaObj->numero_sap_shipment = $value['numero_sap_shipment'];
                $OcorrenciasDeEntregaObj->numero_sap_account = $value['numero_sap_account'];
                $OcorrenciasDeEntregaObj->outro_numero_sap_account = $value['outro_numero_sap_account'];
                $OcorrenciasDeEntregaObj->filial_emissora = $value['filial_emissora'];
                $OcorrenciasDeEntregaObj->serie_do_conhecimento = $value['serie_do_conhecimento'];
                $OcorrenciasDeEntregaObj->numero_do_conhecimento = $value['numero_do_conhecimento'];
                $OcorrenciasDeEntregaObj->indicacao_tipo_entrega = $value['indicacao_tipo_entrega'];
                $OcorrenciasDeEntregaObj->cod_emp_emissora_nf = $value['cod_emp_emissora_nf'];
                $OcorrenciasDeEntregaObj->cod_filial_emp_emissora_nf = $value['cod_filial_emp_emissora_nf'];

                if(!empty($value['dataHora_chegada_destino_nf'])){
                    $OcorrenciasDeEntregaObj->data_chegada_destino_nf = $value['dataHora_chegada_destino_nf'];
                }
                if(!empty($value['dataHora_inicio_descarregamento_destino'])){
                    $OcorrenciasDeEntregaObj->data_inicio_descarregamento_destino = $value['dataHora_inicio_descarregamento_destino'];
                }
                if(!empty($value['dataHora_termino_descarregamento_destino'])){
                    $OcorrenciasDeEntregaObj->data_termino_descarregamento_destino = $value['dataHora_termino_descarregamento_destino'];
                }
                if(!empty($value['dataHora_data_saida_destino'])){
                    $OcorrenciasDeEntregaObj->data_saida_destino = $value['dataHora_data_saida_destino'];
                }

                $OcorrenciasDeEntregaObj->cnpj_emissor_nf_devolucao = $value['cnpj_emissor_nf_devolucao'];
                $OcorrenciasDeEntregaObj->serie_nf_devolucao = $value['serie_nf_devolucao'];
                $OcorrenciasDeEntregaObj->numero_nf_devolucao = $value['numero_nf_devolucao'];
                $OcorrenciasDeEntregaObj->transportadora_cnpj = $value['transportadora_cnpj'];

                $gravar_log = new LogImportacaoEdi;
                $gravar_log->processo = 'Leitura PHP Ocorrencia';
                $gravar_log->arquivo = $path_file;

                $verifica_log = LogImportacaoEdi::where('arquivo',$path_file)->where('processo','ilike','Leitura PHP Ocorrencia')->exists();

                try{
                    $OcorrenciasDeEntregaObj->save();     

                    if($verifica_log == false){
                        $gravar_log->save(); 
                    }
                    
                }catch (\Exception $e) {
                    $linha = $key +4;
                    Log::error($path_file.' Linha: '.$linha);
                }
            }

            if($value['registro'] == 551){
                $NotasHeaderObj = new NotasTransportadoraHeader;
                $faturaObj = $NotasHeaderObj->where('transportadora_cnpj', $value['transportadora_cnpj'])
                ->where('documento_cobranca', $value['documento_cobranca']);

                $fatura = $faturaObj->exists();    

                if($fatura === false){
                    $NotasHeaderObj->transportadora_cnpj = $value['transportadora_cnpj'];
                    $NotasHeaderObj->filial_emissora_documento = $value['filial_emissora_documento'];
                    $NotasHeaderObj->documento_cobranca_serie = $value['documento_cobranca_serie'];
                    $NotasHeaderObj->tipo_documento_cobranca = $value['tipo_documento_cobranca'];
                    $NotasHeaderObj->documento_cobranca = $value['documento_cobranca'];

                    if(!empty($value['data_emissao'])){
                        $NotasHeaderObj->data_emissao = $value['data_emissao'];

                    }
                    if(!empty($value['data_vencimento'])){
                        $NotasHeaderObj->data_vencimento = $value['data_vencimento'];

                    }
                    if(!empty($value['valor_total'])){
                        $NotasHeaderObj->valor_total = $value['valor_total'];

                    }
                    $NotasHeaderObj->tipo_cobranca = $value['tipo_cobranca'];
                    if(!empty($value['percentual_multa_atraso'])){
                        $NotasHeaderObj->percentual_multa_atraso = $value['percentual_multa_atraso'];

                    }
                    if(!empty($value['valor_juros_dia_atraso'])){
                        $NotasHeaderObj->valor_juros_dia_atraso = $value['valor_juros_dia_atraso'];

                    }
                    if(!empty($value['data_limite_pagamento_desconto'])){
                        $NotasHeaderObj->data_limite_pagamento_desconto = $value['data_limite_pagamento_desconto'];

                    }
                    if(!empty($value['valor_desconto'])){
                        $NotasHeaderObj->valor_desconto = $value['valor_desconto'];

                    }
                    $NotasHeaderObj->codigo_banco = $value['codigo_banco'];
                    $NotasHeaderObj->nome_banco = $value['nome_banco'];
                    $NotasHeaderObj->numero_agencia = $value['numero_agencia'];
                    $NotasHeaderObj->agencia_digito = $value['agencia_digito'];
                    $NotasHeaderObj->conta_corrente = $value['conta_corrente'];
                    $NotasHeaderObj->conta_corrente_digito = $value['conta_corrente_digito'];
                    $NotasHeaderObj->acao_documento = $value['acao_documento'];
                    $NotasHeaderObj->identificacao_pre_fatura_cliente = $value['identificacao_pre_fatura_cliente'];
                    $NotasHeaderObj->identificacao_complementar_pre_fatura_cliente = $value['identificacao_complementar_pre_fatura_cliente'];
                    $NotasHeaderObj->cfop = $value['cfop'];
                    $NotasHeaderObj->chave_acesso_nf = $value['chave_acesso_nf'];
                    $NotasHeaderObj->chave_acesso_nf_com_dv = $value['chave_acesso_nf_com_dv'];
                    $NotasHeaderObj->numero_protocolo_nf = $value['numero_protocolo_nf'];
                    $NotasHeaderObj->caminho_arquivo = $path_file;

                    $gravar_log = new LogImportacaoEdi;
                    $gravar_log->processo = 'Leitura PHP Cobranca';
                    $gravar_log->arquivo = $path_file;

                    $verifica_log = LogImportacaoEdi::where('arquivo',$path_file)->where('processo','ilike','Leitura PHP Cobranca')->exists();
                    try{
                        $NotasHeaderObj->save();  

                        if($verifica_log == false){
                            $gravar_log->save();  
                        }
                    }catch (\Exception $e) {
                            $linha = $key +4;
                            Log::error($path_file.' Linha: '.$linha);
                    }

                }
            }

            if($value['registro'] == 555){
                $NasajonEstabelecimento = NasajonEstabelecimento::where('ordemcnpj', $value['ordem_cnpj'])
                ->first();
                
                $NotasSaidaObj = NotasNasajon::where('numero', $value['numero_nota'])
                ->where(function($query) use ($value){
                    $query->where('cliente_documento', $value['destinatario_cnpj'])
                    ->where('estabelecimento_cnpj', $value['estabelecimento_cnpj']);
                });

                if(isset($NasajonEstabelecimento->estabelecimento)){
                    $NotasEntradaObj = NotasEntradasNasajon::where('Número do Documento', $value['numero_nota'])
                    ->where('Identificador Estabelecimento', $NasajonEstabelecimento->estabelecimento)
                    ->first();
                }else{
                    $NotasEntradaObj = NotasEntradasNasajon::where('Número do Documento', $value['numero_nota'])
                    ->where('CNPJ/CPF do Fornecedor', $value['emissor_cnpj'])
                    ->first();
                }
    
                $NotasItenObj = new NotasTransportadoraItem;
                $notaObj = $NotasItenObj->where('numero_nota', $value['numero_nota'])
                ->where('documento_cobranca_header', $value['documento_cobranca_header']);

                $nota = $notaObj->exists();    

                if($nota === false){
                    $NotasItenObj->nota_serie = $value['nota_serie'];
                    $NotasItenObj->numero_nota = $value['numero_nota'];

                    if($NotasSaidaObj->exists()){
                        $NotasSaida = $NotasSaidaObj->first();
                        try{
                            $NotasItenObj->nota_id = $NotasSaida->id;
                        }catch (\Exception $e) {
                            $linha = $key +4;
                            Log::error($path_file.' Linha: '.$linha);
                        }
                    }else{
                        try{
                            $NotasItenObj->nota_id = $NotasEntradaObj['Identificador Documento'];
                        }catch (\Exception $e) {
                            $linha = $key +4;
                            Log::error($path_file.' Linha: '.$linha);
                        }
                    }
        
                    if(!empty($value['data_emissao'])){
                        $NotasItenObj->data_emissao = $value['data_emissao'];
                    }
                    if(!empty($value['peso_nota'])){
                        $NotasItenObj->peso_nota = $value['peso_nota'];
                    }
                    if(!empty($value['valor_nota'])){
                        $NotasItenObj->valor_nota = $value['valor_nota'];
                    }
                    $NotasItenObj->romaneio_nota = $value['romaneio_nota'];
                    $NotasItenObj->numero_sap_shipment_nota = $value['numero_sap_shipment_nota'];
                    $NotasItenObj->numero_sap_account_nota = $value['numero_sap_account_nota'];
                    $NotasItenObj->outro_numero_sap_account_nota = $value['outro_numero_sap_account_nota'];
                    $NotasItenObj->devolucao_nota = $value['devolucao_nota'];
                    $NotasItenObj->filial_emissora_documento = $value['filial_emissora_documento'];
                    $NotasItenObj->conhecimento_serie = $value['conhecimento_serie'];
                    $NotasItenObj->numero_conhecimento = $value['numero_conhecimento'];
                    
                    if(!empty($value['valor_frete'])){
                        $NotasItenObj->valor_frete = $value['valor_frete'];
                    }
                    if(!empty($value['data_emissao_conhecimento'])){
                        $NotasItenObj->data_emissao_conhecimento = $value['data_emissao_conhecimento'];
                    }
                    $NotasItenObj->destinatario_cnpj = $value['destinatario_cnpj'];
                    $NotasItenObj->emissor_cnpj = $value['emissor_cnpj'];
                    $NotasItenObj->filial_transportadora_cnpj = $value['filial_transportadora_cnpj'];
                    $NotasItenObj->uf_local_coleta = $value['uf_local_coleta'];
                    $NotasItenObj->uf_unidade_emissora = $value['uf_unidade_emissora'];
                    $NotasItenObj->uf_destinatario = $value['uf_destinatario'];
                    $NotasItenObj->conta_razao = $value['conta_razao'];
                    $NotasItenObj->codigo_iva = $value['codigo_iva'];
                    $NotasItenObj->numero_romaneio_conhecimento = $value['numero_romaneio_conhecimento'];
                    $NotasItenObj->numero_sap_conhecimento = $value['numero_sap_conhecimento'];
                    $NotasItenObj->numero_sap_shipment_conhecimento = $value['numero_sap_shipment_conhecimento'];
                    $NotasItenObj->numero_sap_account_conhecimento = $value['numero_sap_account_conhecimento'];
                    $NotasItenObj->devolucao = $value['devolucao'];
                    $NotasItenObj->documento_cobranca_header = $value['documento_cobranca_header'];
        
                    try{
                        $NotasItenObj->save();   
                    }catch (\Exception $e) {
                            $linha = $key +4;
                            Log::error($path_file.' Linha: '.$linha);
                    }
                }
            }
        }
        return count($arquivo_text);
    }

    public function modalOcorrenciaEntrega(Request $request){
		set_time_limit(300);
		ini_set('memory_limit','1024M');
		
        try{
            $filter = $request->only(['id_nota']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu um erro de instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
		}
		
		$OcorrenciasDeEntregaObj = OcorrenciasDeEntrega::with(['ocorrenciasDescricao', 'observacaoDescricao'])
		->where('nota_id', $filter['id_nota'])
		->get();

		$saida = [];
		foreach($OcorrenciasDeEntregaObj as $ocorrencia){
			$saida[] = [
				'dataHora_ocorrencia' => parserDataEHora($ocorrencia->data_ocorrencia),
				'envento' => !empty($ocorrencia->ocorrenciasDescricao->descricao) ? $ocorrencia->ocorrenciasDescricao->descricao : '',
				'observacao' => !empty($ocorrencia->observacaoDescricao->descricao) ? $ocorrencia->observacaoDescricao->descricao :  '',
				'romaneio' => $ocorrencia->numero_romaneio
			];
		}	
		return view('programs.historico_vendas.modal.ocorrencia_entrega')->with(['dados' => $saida]);
	}

    public function aprovarFatura(Request $request){
        $filtro = $request->only(['id','situacao']);

        try{
            $id = decrypt($filtro['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }

        $faturas_aprovadas = NotasTransportadoraHeaderAprovacaoFatura::where('notas_transportadora_headers_id',$id)->with('fatura')->first();

        if(empty($faturas_aprovadas)){
            $faturas_aprovadas = new NotasTransportadoraHeaderAprovacaoFatura;
            $faturas_aprovadas->situacao = $filtro['situacao'];
            $faturas_aprovadas->notas_transportadora_headers_id = $id;
            $faturas_aprovadas->created_by = Auth::id();
            $faturas_aprovadas->save();

            if($faturas_aprovadas){
                $return = [
                    'status' => 'success',
                    'message' => '',
                    'error' => '',
                    'response' => '',
                ];
            }else{
                $return = [
                    'status' => 'error',
                    'message' => 'Houve um erro ao gravar, tente novamente mais tarde.',
                    'error' => '',
                    'response' => '',
                ];
            }
        }else{
            $sutiacao = ($faturas_aprovadas->situacao == true) ? 'aprovada' : 'reprovada';
            $return = [
                'status' => 'error',
                'message' => 'Á fatura '.$faturas_aprovadas->fatura->documento_cobranca.' já está '.$sutiacao.'.',
                'error' => '',
                'response' => '',
            ];
        }

        return response()->json($return);
    }

    public function verificaCobrancaSemNota(){
        set_time_limit(900);
        ini_set('memory_limit','1024M');
        $notas = NotasTransportadoraItem::whereNull('nota_id')
        ->with('fatura')
        ->get();
        
        $notas->each(function($query){
            if(!empty($query->fatura) && !empty($query->fatura->caminho_arquivo)){
                $arquivo = $query->fatura->caminho_arquivo.'.bkp';
                $arquivo = str_replace(trim('\ '),'/',$arquivo);
                $array_caminho = explode('/',$arquivo);
                $arquivo = end($array_caminho);
                $dados = '';

                if(file_exists('storage/app/ocorrencias/'.$arquivo)){
                    $dados = file('storage/app/ocorrencias/'.$arquivo);
                }

                if(!empty($dados)){
                    foreach($dados as $linha){
                        $linha = trim($linha);
                        $registro = substr($linha,0,3);
            
                        if($registro == 541){
                            $transportadoraCnpj = substr($linha,3,14);
                            $transportadoraCnpj = mask($transportadoraCnpj, '##.###.###/####-##');
            
                            $transportadora_entrega = [
                                'registro' => $registro,
                                'transportadora_cnpj' => $transportadoraCnpj
                            ];
                        }
            
                        if($registro == 542){
                            $cnpj = substr($linha,3,14);
                            $ordem_cnpj = substr($linha,11,6);
                            $serie = $this->formatarNotaSerie($linha,17,3);
                            $nota = $this->formatarNotaSerie($linha,20,9);
            
                            $codigo_ocorrencia = substr($linha,29,3);
                            $dataHora = $this->formatarDataHora($linha,32,12);
            
                            $codigo_observacao = substr($linha,44,2);
            
                            if($codigo_observacao == '00'){
                                $codigo_observacao = '03';
                            }
            
                            $numero_romaneio = substr($linha,46,20);
                            $numero_sap_shipment = substr($linha,66,20);
                            $numero_sap_account = substr($linha,86,20);
                            $outro_numero_sap_account = substr($linha,106,20);
                            $filial_emissora = substr($linha,126,10);
                            $serie_do_conhecimento = substr($linha,136,5);
                            $numero_do_conhecimento = substr($linha,141,12);
                            $indicacao_tipo_entrega = substr($linha,153,1);
                            $cod_emp_emissora_nf = substr($linha,154,5);
                            $cod_filial_emp_emissora_nf = substr($linha,159,5);
            
                            $dataHora_destino_nf = $this->formatarDataHora($linha,164,12);
                            $dataHora_InicioDescarregamento = $this->formatarDataHora($linha,176,12);
                            $dataHora_TerminoDescarregamento = $this->formatarDataHora($linha,188,12);
                            $dataHora_data_saida_destino = $this->formatarDataHora($linha,200,12); 
            
                            $cnpj_emissor_nf_devolucao = substr($linha,212,14);
                            $cnpj_emissor_nf_devolucao = trim($cnpj_emissor_nf_devolucao);
            
                            if($cnpj_emissor_nf_devolucao != '00000000000000' && strlen($cnpj_emissor_nf_devolucao) == 14){
                                $cnpj_emissor_nf_devolucao = mask($cnpj_emissor_nf_devolucao, '##.###.###/####-##');
                            }else{
                                $cnpj_emissor_nf_devolucao = '';
                            }
                            
                            $serie_nf_devolucao =  substr($linha,226,3);
                            $numero_nf_devolucao = substr($linha,229,9);
                            $numero_nf_devolucao = trim($numero_nf_devolucao);
                            
                            if($numero_nf_devolucao != '000000000' && strlen($numero_nf_devolucao) == 9){
                                $numero_nf_devolucao;
                            }else{
                                $numero_nf_devolucao = '';
                            }
            
                            $saida[] = array_merge($transportadora_entrega, [
                                'cnpj' => $cnpj,
                                'ordem_cnpj' => $ordem_cnpj,
                                'serie' => $serie,
                                'nota' => $nota,
                                'codigo_ocorrencia' => $codigo_ocorrencia,
                                'dataHora' => $dataHora,
                                'codigo_observacao' => $codigo_observacao,
                                'numero_romaneio' => $numero_romaneio,
                                'numero_sap_shipment' => $numero_sap_shipment,
                                'numero_sap_account' => $numero_sap_account,
                                'outro_numero_sap_account' => $outro_numero_sap_account,
                                'filial_emissora' => $filial_emissora,
                                'serie_do_conhecimento' => $serie_do_conhecimento,
                                'numero_do_conhecimento' => $numero_do_conhecimento,
                                'indicacao_tipo_entrega' => $indicacao_tipo_entrega,
                                'cod_emp_emissora_nf' => $cod_emp_emissora_nf,
                                'cod_filial_emp_emissora_nf' => $cod_filial_emp_emissora_nf,
                                'dataHora_chegada_destino_nf' => $dataHora_destino_nf,
                                'dataHora_inicio_descarregamento_destino' => $dataHora_InicioDescarregamento,
                                'dataHora_termino_descarregamento_destino' => $dataHora_TerminoDescarregamento,
                                'dataHora_data_saida_destino' => $dataHora_data_saida_destino,
                                'cnpj_emissor_nf_devolucao' =>$cnpj_emissor_nf_devolucao,
                                'serie_nf_devolucao' => $serie_nf_devolucao,
                                'numero_nf_devolucao' => $numero_nf_devolucao
                            ]);
                        }
            
                        if($registro == 551){
                            $transportadora_cnpj = substr($linha,3,14);
                            $transportadora_cnpj = mask($transportadora_cnpj, '##.###.###/####-##');
            
                            $transportadora = [
                                'registro' => $registro,
                                'transportadora_cnpj' => $transportadora_cnpj
                            ];
            
                        }
                        if($registro == 552){
                            $filial_emissora_documento = substr($linha,3,10);
                            $tipo_documento = substr($linha,13,1);
                            $documento_cobranca_serie = substr($linha,14,3);
                            $documento_cobranca = substr($linha,17,10);
                            $data_emissao = $this->formatarData($linha,27,8);
                            $data_vencimento = $this->formatarData($linha,35,8);
                            $valor_total = $this->formatarValor($linha,43,15);
                            $tipo_cobrança = substr($linha,58,3);
                            $percentual_multa_atraso = $this->formatarValor($linha,61,4);
                            $valor_juros_dia_atraso = $this->formatarValor($linha,65,15);
                            $data_limite_pagamento_desconto = $this->formatarData($linha,80,8);
                            $valor_desconto = $this->formatarValor($linha,88,15);
                            $codigo_banco = substr($linha,103,5);
            
                            if($codigo_banco == '00000'){
                                $codigo_banco = '';
                            }
            
                            $nome_banco = substr($linha,108,30);
                            $numero_agencia = substr($linha,138,4);
            
                            if($numero_agencia == '0000'){
                                $numero_agencia = '';
                            }
            
                            $agencia_digito = substr($linha,142,1);
                            $conta_corrente = substr($linha,143,10);
            
                            if($conta_corrente == '0000000000'){
                                $conta_corrente = '';
                            }
            
                            $conta_corrente_digito = substr($linha,153,2);
                            $acao_documento = substr($linha,155,1);
                            $identificacao_pre_fatura_cliente = substr($linha,156,10);
            
                            if($identificacao_pre_fatura_cliente == '0000000000'){
                                $identificacao_pre_fatura_cliente = '';
                            }
            
                            $identificacao_complementar_pre_fatura_cliente = substr($linha,166,20);
                            $cfop = substr($linha,186,5);
                            $chave_acesso_nf = substr($linha,191,9);
            
                            if($chave_acesso_nf == '000000000'){
                                $chave_acesso_nf = '';
                            }
            
                            $chave_acesso_nf_com_dv = substr($linha,200,45);
                            $numero_protocolo_nf = substr($linha,245,15);
            
                            $registro_header = array_merge($transportadora, [
                                'filial_emissora_documento' => $filial_emissora_documento,
                                'tipo_documento_cobranca' => $tipo_documento,
                                'documento_cobranca_serie' => $documento_cobranca_serie,
                                'documento_cobranca' => $documento_cobranca,
                                'data_emissao' => $data_emissao,
                                'data_vencimento' => $data_vencimento,
                                'valor_total' => $valor_total,
                                'tipo_cobranca' => $tipo_cobrança,
                                'percentual_multa_atraso' => $percentual_multa_atraso,
                                'valor_juros_dia_atraso' => $valor_juros_dia_atraso,
                                'data_limite_pagamento_desconto' => $data_limite_pagamento_desconto,
                                'valor_desconto' => $valor_desconto ,
                                'codigo_banco' => $codigo_banco,
                                'nome_banco' => $nome_banco,
                                'numero_agencia' => $numero_agencia,
                                'agencia_digito' => $agencia_digito,
                                'conta_corrente' => $conta_corrente,
                                'conta_corrente_digito' => $conta_corrente_digito,
                                'acao_documento' => $acao_documento,
                                'identificacao_pre_fatura_cliente' => $identificacao_pre_fatura_cliente,
                                'identificacao_complementar_pre_fatura_cliente' => $identificacao_complementar_pre_fatura_cliente,
                                'cfop' => $cfop,
                                'chave_acesso_nf' => $chave_acesso_nf,
                                'chave_acesso_nf_com_dv' => $chave_acesso_nf_com_dv,
                                'numero_protocolo_nf' => $numero_protocolo_nf
                            ]);
                        }
            
                        if($registro == 553){
                            $valor_total_icms = $this->formatarValor($linha,3,15);
                            $aliquota_icms = $this->formatarValor($linha,18,5);
                            $base_calculo_icms = $this->formatarValor($linha,23,15);
                            $valor_total_iss = $this->formatarValor($linha,38,15);
                            $aliquota_iss = $this->formatarValor($linha,53,5);
                            $base_calculo_iss = $this->formatarValor($linha,58,15);
                            $valor_total_icms_st = $this->formatarValor($linha,73,15);
                            $aliquota_icms_st = $this->formatarValor($linha,88,5);
                            $base_calculo_icms_st = $this->formatarValor($linha,93,15);
                            $valor_total_ir = $this->formatarValor($linha,108,15);
            
                            $saida[] = array_merge($registro_header, [
                                'valor_total_icms' => $valor_total_icms,
                                'aliquota_icms' => $aliquota_icms,
                                'base_calculo_icms' => $base_calculo_icms,
                                'valor_total_iss' => $valor_total_iss,
                                'aliquota_iss' => $aliquota_iss,
                                'base_calculo_iss_st' => $base_calculo_iss,
                                'valor_total_icms_st' => $valor_total_icms_st,
                                'aliquota_iss_st' => $valor_total_icms_st,
                                'aliquota_icms_st' => $aliquota_icms_st,
                                'base_calculo_icms_st' => $base_calculo_icms_st,
                                'valor_total_ir' => $valor_total_ir
                            ]);
                        }
            
                        if($registro == 555){
                            $filial_emissora_documento = substr($linha,3,10);
                            $conhecimento_serie = substr($linha,13,5);
                            $numero_conhecimento = substr($linha,18,12);
                            $valor_frete = $this->formatarValor($linha,31,14);
                            $data_emissao_conhecimento = $this->formatarData($linha,45,8);
                            $emissor_cnpj = substr($linha,53,14);
            
                            $destinatario_cnpj = substr($linha,67,14);
            
                            $verifica_estabelecimento = NasajonEstabelecimento::where(DB::raw('concat(raizcnpj,ordemcnpj)'),'ilike',$destinatario_cnpj)
                            ->orWhere(DB::raw('concat(raizcnpj,ordemcnpj)'),'ilike',$emissor_cnpj)
                            ->first();
            
                            if($emissor_cnpj != '00000000000000'){
                                $emissor_cnpj = mask($emissor_cnpj, '##.###.###/####-##');
                            }
            
                            $estabelecimento_cnpj = (!empty($verifica_estabelecimento)) ? $verifica_estabelecimento->raizcnpj.$verifica_estabelecimento->ordemcnpj : '';
            
                            if($destinatario_cnpj != '00000000000000'){
                                $destinatario_cnpj = mask($destinatario_cnpj, '##.###.###/####-##');
                            }
            
                            $ordem_cnpj = (!empty($estabelecimento_cnpj)) ? substr($estabelecimento_cnpj,8) : '';
                            $filial_transportadora_cnpj = substr($linha,81,14);
            
                            if($filial_transportadora_cnpj != '00000000000000'){
                                $filial_transportadora_cnpj = mask($filial_transportadora_cnpj, '##.###.###/####-##');
                            }
            
                            $uf_local_coleta = substr($linha,95,2);
                            $uf_unidade_emissora = substr($linha,97,2);
                            $uf_destinatario = substr($linha,99,2);
                            $conta_razao = substr($linha,101,10);
                            $codigo_iva = substr($linha,111,2);
                            $numero_romaneio_conhecimento = substr($linha,113,20);
                            $numero_sap_conhecimento = substr($linha,133,20);
                            $numero_sap_shipment_conhecimento = substr($linha,153,20);
                            $numero_sap_account_conhecimento = substr($linha,173,20);
                            $devolucao  = substr($linha,193,1);
            
                            $registro_itens = [
                                'registro' => $registro,
                                'filial_emissora_documento' => $filial_emissora_documento,
                                'conhecimento_serie' => $conhecimento_serie,
                                'numero_conhecimento' => $numero_conhecimento,
                                'valor_frete' => $valor_frete,
                                'data_emissao_conhecimento' => $data_emissao_conhecimento,
                                'emissor_cnpj' => $emissor_cnpj,
                                'destinatario_cnpj' => $destinatario_cnpj,
                                'estabelecimento_cnpj' => $estabelecimento_cnpj,
                                'ordem_cnpj' => $ordem_cnpj,
                                'filial_transportadora_cnpj' => $filial_transportadora_cnpj,
                                'uf_local_coleta' => $uf_local_coleta,
                                'uf_unidade_emissora' => $uf_unidade_emissora,
                                'uf_destinatario' => $uf_destinatario,
                                'conta_razao' => $conta_razao,
                                'codigo_iva' => $codigo_iva,
                                'numero_romaneio_conhecimento' => $numero_romaneio_conhecimento,
                                'numero_sap_conhecimento' => $numero_sap_conhecimento,
                                'numero_sap_shipment_conhecimento' => $numero_sap_shipment_conhecimento,
                                'numero_sap_account_conhecimento' => $numero_sap_account_conhecimento,
                                'devolucao' => $devolucao
                            ];
                        }
            
                        if($registro == 556){
                            $nota_serie = $this->formatarNotaSerie($linha,3,3);
                            $numero_nota = substr($linha,6,9);
                            $data_emissao = $this->formatarData($linha,15,8);
                            $peso_nota = $this->formatarValor($linha,23,7);
                            $valor_nota = $this->formatarValor($linha,30,15);
                            $romaneio_nota = substr($linha,59,20);
                            $numero_sap_shipment_nota = substr($linha,79,20);
                            $numero_sap_account_nota = substr($linha,99,20);
                            $outro_numero_sap_account_nota = substr($linha,119,20);
                            $devolucao_nota = substr($linha,139,1);
            
                            $saida[] = array_merge($registro_itens, [
                                'nota_serie' => $nota_serie,
                                'numero_nota' => $numero_nota,
                                'data_emissao' => $data_emissao,
                                'peso_nota' => $peso_nota,
                                'valor_nota' => $valor_nota,
                                'romaneio_nota' => $romaneio_nota,
                                'numero_sap_shipment_nota' => $numero_sap_shipment_nota,
                                'numero_sap_account_nota' => $numero_sap_account_nota,
                                'outro_numero_sap_account_nota' => $outro_numero_sap_account_nota,
                                'devolucao_nota' => $devolucao_nota,
                                'documento_cobranca_header' => $documento_cobranca
                            ]);
                        }
                    }
            
            
                    foreach($saida as $key => $array){
                        $retorno[] = array_map('trim', $array);
                        if($saida[$key]['registro'] == 555){
                            $numero_conhecimento = trim($saida[$key]['numero_conhecimento']);
                            if(isset($divisor[$numero_conhecimento])){
                                $divisor[$numero_conhecimento] += 1;
                            }else{
                            $divisor[$numero_conhecimento] = 1;
                            }
                        }
                    }
            
                    foreach($retorno as $chave => $value){
                        if($retorno[$chave]['registro'] == 555){
                            if(isset($divisor[$retorno[$chave]['numero_conhecimento']])){
                                $retorno[$chave]['valor_frete'] = $retorno[$chave]['valor_frete']/$divisor[$retorno[$chave]['numero_conhecimento']];
                            }
                        }
                    }

                    $collect_dados = collect($retorno);

                    foreach($collect_dados->where('registro','555') as $dado){
                        
                        $NasajonEstabelecimento = NasajonEstabelecimento::where('ordemcnpj', $dado['ordem_cnpj'])
                        ->first();

                        $nota_item = NotasTransportadoraItem::where('id',$query->id)
                        ->where('numero_nota',$dado['numero_nota'])
                        ->where('destinatario_cnpj',$dado['destinatario_cnpj'])
                        ->first();

                        $NotasSaidaObj = NotasNasajon::where('numero', $dado['numero_nota'])
                        ->where(function($query) use ($dado){
                            $query->where('cliente_documento', $dado['destinatario_cnpj'])
                            ->where('estabelecimento_cnpj', $dado['estabelecimento_cnpj']);
                        });

                        if(isset($NasajonEstabelecimento->estabelecimento)){
                            $NotasEntradaObj = NotasEntradasNasajon::where('Número do Documento', $value['numero_nota'])
                            ->where('Identificador Estabelecimento', $NasajonEstabelecimento->estabelecimento)
                            ->first();
                        }else{
                            $NotasEntradaObj = NotasEntradasNasajon::where('Número do Documento', $value['numero_nota'])
                            ->where('CNPJ/CPF do Fornecedor', $value['emissor_cnpj'])
                            ->first();
                        }

                        if(!empty($nota_item)){
                            if($NotasSaidaObj->exists()){
                                $NotasSaida = $NotasSaidaObj->first();
                                try{
                                    $nota_item->nota_id = $NotasSaida->id;
                                    $nota_item->save();
                                }catch (\Exception $e) {
                                    $linha = $key +4;
                                    Log::error($e);
                                }
                            }else{
                                try{
                                    $nota_item->nota_id = $NotasEntradaObj['Identificador Documento'];
                                    $nota_item->save();
                                }catch (\Exception $e) {
                                    $linha = $key +4;
                                    Log::error($e);
                                }
                            }
                        }
                    }
                }
            }
        });
    }
}
