<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use App\Movimentacao;
use App\NotasNasajon;

use App\DevolucaoNota;
use App\ComprasNasajon;
use App\ScoreFornecedore;
use App\FornecedorNasajon;

use Illuminate\Http\Request;
use App\TitulosAPagarNasajon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\PosicaoSinteticaFornecedorBuscaPedidosRequest;
use App\Http\Requests\PosicaoSinteticaFornecedorBuscaTitulosRequest;

class PosicaoSinteticaFornecedorController extends Controller
{
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\PosicaoSinteticaFornecedor") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\PosicaoSinteticaFornecedor');

        $situacao = [
            'Aberto' => 'Aberto',
            'Cancelado' => 'Cancelado',
            'Liquidado' => 'Liquidado',
        ];

        return view('programs.posicao_sintetica_fornecedor.index')->with(['situacao' => $situacao]);
    }

    public function filtro(Request $request){
        set_time_limit(500);
        ini_set('memory_limit','1024M');
        $filtro = $request->only(['fornecedor']);

        $fornecedor_nasajon = FornecedorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj_cpf))'), 'ilike','%' . $filtro['fornecedor'] . '%')->first();
        $devolucaoNotas = new DevolucaoNotaController;
       
        $devolucoes = $devolucaoNotas->modalDevolucoesFornecedor($fornecedor_nasajon->cnpj_cpf);

  
        $retorno = [
            'titulos' => [
                'a_vencer' => [
                    'valor' => 0,
                    'quantidade' => 0,
                    'filter' => encrypt([
                        'estabelecimento' => null,
                        'fornecedor_nome' => trim($filtro['fornecedor']),
                        'fornecedor_id' => $fornecedor_nasajon->id,
                        'data_inicio' => null,
                        'data_fim' => null,
                        'vencimento' => 'a_vencer'
                    ])
                ],
                'vencidos' => [
                    'valor' => 0,
                    'quantidade' => 0,
                    'filter' => encrypt([
                        'estabelecimento' => null,
                        'fornecedor_nome' => trim($filtro['fornecedor']),
                        'fornecedor_id' => $fornecedor_nasajon->id,
                        'data_inicio' => null,
                        'data_fim' => null,
                        'vencimento' => 'vencidos'
                    ])
                ],
                'total' => [
                    'valor' => 0,
                    'quantidade' => 0,
                    'filter' => encrypt([
                        'estabelecimento' => null,
                        'fornecedor_nome' => trim($filtro['fornecedor']),
                        'fornecedor_id' => $fornecedor_nasajon->id,
                        'data_inicio' => null,
                        'data_fim' => null,
                        'vencimento' => 'total'
                    ])
                ],
            ],
            'pedidos' => [
                'a_vencer' => [
                    'valor' => 0,
                    'quantidade' => 0,
                    'filter' => encrypt([
                        'fornecedor_nome' => trim($filtro['fornecedor']),
                        'vencimento' => 'a_vencer'
                    ])
                ],
                'vencidos' => [
                    'valor' => 0,
                    'quantidade' => 0,
                    'filter' => encrypt([
                        'fornecedor_nome' => trim($filtro['fornecedor']),
                        'vencimento' => 'vencidos'
                    ])
                ],
                'total' => [
                    'valor' => 0,
                    'quantidade' => 0,
                    'filter' => encrypt([
                        'fornecedor_nome' => trim($filtro['fornecedor']),
                        'vencimento' => 'total'
                    ])
                ],
            ],
            'atraso_entrega' => [
                'ultimo' => [
                    'data' => '',
                    'dias' => '',
                    'id_nota' => ''
                ],
                'maior' => [
                    'data' => '',
                    'dias' => '',
                    'id_nota' => ''
                ]
            ],
            'compras' => [
                'ultimo' => [
                    'data' => '',
                    'valor' => '',
                    'id_nota' => ''
                ],
                'maior' => [
                    'data' => '',
                    'valor' => '',
                    'id_nota' => ''
                ]
                ]
              
        ];

        if(isset($fornecedor_nasajon->codigo) && !empty($fornecedor_nasajon)){
            $fornecedor_codigo = [];
            $codigo = (!empty($fornecedor_nasajon )) ? $fornecedor_nasajon->codigo: null;

            $FornecedorNasajonQuery = FornecedorNasajon::where('codigo', 'like', $codigo . '%');
            $fornecedores = $FornecedorNasajonQuery->get();
            foreach($fornecedores  as $fornecedor){
                $fornecedor_codigo[] = [$fornecedor->codigo];
            }
            
            $titulos = TitulosAPagarNasajon::whereIn('Fornecedor',$fornecedor_codigo)
            ->whereIn('Situação do Título',['Aberto','Em Débito'])
            ->whereNotNull('Data do Vencimento')
            ->where('Estabelecimento', '!=', '25')
            ->get();

            $compras = ComprasNasajon::where('fornecedor_id',$fornecedor_nasajon->id)
            ->whereIn('situacao',['Aberto','Aguardando Documento','Parcialmente Liquidado','Cancelado'])
            ->get();

        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $data_atual = Carbon::now()->format('Y-m-d');
        $array_pedidos_cancelados = [];
        $pedidos_cancelados = $compras->where('situacao','Cancelado')->unique('numero_pedido');

        $pedidos_cancelados->each(function($query) use (&$array_pedidos_cancelados){
            $array_pedidos_cancelados[] = $query->numero_pedido;
        });

        unset($pedidos_cancelados);
        $compras = $compras->whereNotIn('numero_pedido',$array_pedidos_cancelados);
        $titulos_a_vencer = [];
        $titulos_a_vencer_total = [
            'valor_original' => 0,
            'valor' => 0,
            'multa' => 0,
            'juros_diarios' => 0,
            'desconto' => 0
        ];

        $titulos->each(function($query) use ($data_atual,&$retorno,&$titulos_a_vencer,&$titulos_a_vencer_total,$fornecedor_codigo){
            $data_atual = Carbon::parse($data_atual);
            $data = Carbon::parse($query['Data do Vencimento']);
            
            if($data->lt($data_atual)){

                $retorno['titulos']['vencidos']['quantidade'] ++;
                $retorno['titulos']['vencidos']['valor'] += $query['Valor'];

            }else if($data->gte($data_atual)){

                $retorno['titulos']['a_vencer']['quantidade'] ++;
                $retorno['titulos']['a_vencer']['valor'] +=  $query['Valor'];

                if($data_atual->diffInDays($data) <= 15){
                    if(!isset($titulos_a_vencer['15_dias'])){
                        $titulos_a_vencer['15_dias'] = [
                            'prazo' => '1 à 15 dias',
                            'valor_original' => 0,
                            'valor' => 0,
                            'multa' => 0,
                            'juros_diarios' => 0,
                            'desconto' => 0,
                            'filtro' => encrypt([
                                'fornecedor_id' => $fornecedor_codigo,
                                'data_inicial' => Carbon::now(),
                                'data_final' => Carbon::now()->addDays(15)
                            ])
                        ];
                    }

                    $valor_titulo = 0;

                    if ($query['Situação do Título'] === 'Aberto') {
                        $valor_titulo = $query['Valor'];
                    }else{
                        $valor_titulo = $query['Valor'] - $query['Valor da Baixa'];
                    }

                    $titulos_a_vencer['15_dias']['valor_original'] += $query['Valor'];
                    $titulos_a_vencer['15_dias']['valor'] += $valor_titulo;
                    $titulos_a_vencer['15_dias']['multa'] += $query['Multa'];
                    $titulos_a_vencer['15_dias']['juros_diarios'] += $query->percentualjurosdiario;
                    $titulos_a_vencer['15_dias']['desconto'] += $query['Desconto'];

                }else if($data_atual->diffInDays($data) > 15 && $data_atual->diffInDays($data) <= 30){
                    if(!isset($titulos_a_vencer['16_30'])){
                        $titulos_a_vencer['16_30'] = [
                            'prazo' => '16 à 30 dias',
                            'valor_original' => 0,
                            'valor' => 0,
                            'multa' => 0,
                            'juros_diarios' => 0,
                            'desconto' => 0,
                            'filtro' => encrypt([
                                'fornecedor_id' => $fornecedor_codigo,
                                'data_inicial' => Carbon::now()->addDays(16),
                                'data_final' => Carbon::now()->addDays(30)
                            ])
                        ];
                    }

                    $valor_titulo = 0;

                    if ($query['Situação do Título'] === 'Aberto') {
                        $valor_titulo = $query['Valor'];
                    }else{
                        $valor_titulo = $query['Valor'] - $query['Valor da Baixa'];
                    }

                    $titulos_a_vencer['16_30']['valor_original'] += $query['Valor'];
                    $titulos_a_vencer['16_30']['valor'] += $valor_titulo;
                    $titulos_a_vencer['16_30']['multa'] += $query['Multa'];
                    $titulos_a_vencer['16_30']['juros_diarios'] += $query->percentualjurosdiario;
                    $titulos_a_vencer['16_30']['desconto'] += $query['Desconto'];
                }else if($data_atual->diffInDays($data) > 30 && $data_atual->diffInDays($data) <= 45){
                    if(!isset($titulos_a_vencer['31_45'])){
                        $titulos_a_vencer['31_45'] = [
                            'prazo' => '31 à 45 dias',
                            'valor_original' => 0,
                            'valor' => 0,
                            'multa' => 0,
                            'juros_diarios' => 0,
                            'desconto' => 0,
                            'filtro' => encrypt([
                                'fornecedor_id' => $fornecedor_codigo,
                                'data_inicial' => Carbon::now()->addDays(31),
                                'data_final' => Carbon::now()->addDays(45)
                            ])
                        ];
                    }

                    $valor_titulo = 0;

                    if ($query['Situação do Título'] === 'Aberto') {
                        $valor_titulo = $query['Valor'];
                    }else{
                        $valor_titulo = $query['Valor'] - $query['Valor da Baixa'];
                    }

                    $titulos_a_vencer['31_45']['valor_original'] += $query['Valor'];
                    $titulos_a_vencer['31_45']['valor'] += $valor_titulo;
                    $titulos_a_vencer['31_45']['multa'] += $query['Multa'];
                    $titulos_a_vencer['31_45']['juros_diarios'] += $query->percentualjurosdiario;
                    $titulos_a_vencer['31_45']['desconto'] += $query['Desconto'];
                }else if($data_atual->diffInDays($data) > 45 && $data_atual->diffInDays($data) <= 60){
                    if(!isset($titulos_a_vencer['46_60'])){
                        $titulos_a_vencer['46_60'] = [
                            'prazo' => '36 à 60 dias',
                            'valor_original' => 0,
                            'valor' => 0,
                            'multa' => 0,
                            'juros_diarios' => 0,
                            'desconto' => 0,
                            'filtro' => encrypt([
                                'fornecedor_id' => $fornecedor_codigo,
                                'data_inicial' => Carbon::now()->addDays(46),
                                'data_final' => Carbon::now()->addDays(60)
                            ])
                        ];
                    }

                    $valor_titulo = 0;

                    if ($query['Situação do Título'] === 'Aberto') {
                        $valor_titulo = $query['Valor'];
                    }else{
                        $valor_titulo = $query['Valor'] - $query['Valor da Baixa'];
                    }

                    $titulos_a_vencer['46_60']['valor_original'] += $query['Valor'];
                    $titulos_a_vencer['46_60']['valor'] += $valor_titulo;
                    $titulos_a_vencer['46_60']['multa'] += $query['Multa'];
                    $titulos_a_vencer['46_60']['juros_diarios'] += $query->percentualjurosdiario;
                    $titulos_a_vencer['46_60']['desconto'] += $query['Desconto'];
                }else if($data_atual->diffInDays($data) > 60 && $data_atual->diffInDays($data) <= 75){
                    if(!isset($titulos_a_vencer['61_75'])){
                        $titulos_a_vencer['61_75'] = [
                            'prazo' => '61 à 75 dias',
                            'valor_original' => 0,
                            'valor' => 0,
                            'multa' => 0,
                            'juros_diarios' => 0,
                            'desconto' => 0,
                            'filtro' => encrypt([
                                'fornecedor_id' => $fornecedor_codigo,
                                'data_inicial' => Carbon::now()->addDays(61),
                                'data_final' => Carbon::now()->addDays(75)
                            ])
                        ];
                    }

                    $valor_titulo = 0;

                    if ($query['Situação do Título'] === 'Aberto') {
                        $valor_titulo = $query['Valor'];
                    }else{
                        $valor_titulo = $query['Valor'] - $query['Valor da Baixa'];
                    }

                    $titulos_a_vencer['61_75']['valor_original'] += $query['Valor'];
                    $titulos_a_vencer['61_75']['valor'] += $valor_titulo;
                    $titulos_a_vencer['61_75']['multa'] += $query['Multa'];
                    $titulos_a_vencer['61_75']['juros_diarios'] += $query->percentualjurosdiario;
                    $titulos_a_vencer['61_75']['desconto'] += $query['Desconto'];
                }else if($data_atual->diffInDays($data) > 75 && $data_atual->diffInDays($data) <= 90){
                    if(!isset($titulos_a_vencer['76_90'])){
                        $titulos_a_vencer['76_90'] = [
                            'prazo' => '76 à 90 dias',
                            'valor_original' => 0,
                            'valor' => 0,
                            'multa' => 0,
                            'juros_diarios' => 0,
                            'desconto' => 0,
                            'filtro' => encrypt([
                                'fornecedor_id' => $fornecedor_codigo,
                                'data_inicial' => Carbon::now()->addDays(76),
                                'data_final' => Carbon::now()->addDays(90)
                            ])
                        ];
                    }

                    $valor_titulo = 0;

                    if ($query['Situação do Título'] === 'Aberto') {
                        $valor_titulo = $query['Valor'];
                    }else{
                        $valor_titulo = $query['Valor'] - $query['Valor da Baixa'];
                    }

                    $titulos_a_vencer['76_90']['valor_original'] += $query['Valor'];
                    $titulos_a_vencer['76_90']['valor'] += $valor_titulo;
                    $titulos_a_vencer['76_90']['multa'] += $query['Multa'];
                    $titulos_a_vencer['76_90']['juros_diarios'] += $query->percentualjurosdiario;
                    $titulos_a_vencer['76_90']['desconto'] += $query['Desconto'];
                }else if($data_atual->diffInDays($data) > 90){
                    if(!isset($titulos_a_vencer['90'])){
                        $titulos_a_vencer['90'] = [
                            'prazo' => 'Maior que 90 dias',
                            'valor_original' => 0,
                            'valor' => 0,
                            'multa' => 0,
                            'juros_diarios' => 0,
                            'desconto' => 0,
                            'filtro' => encrypt([
                                'fornecedor_id' => $fornecedor_codigo,
                                'data_inicial' => Carbon::now()->addDays(91)
                            ])
                        ];
                    }

                    $valor_titulo = 0;

                    if($query['Situação do Título'] === 'Aberto'){
                        $valor_titulo = $query['Valor'];
                    }else{
                        $valor_titulo = $query['Valor'] - $query['Valor da Baixa'];
                    }

                    $titulos_a_vencer['90']['valor_original'] += $query['Valor'];
                    $titulos_a_vencer['90']['valor'] += $valor_titulo;
                    $titulos_a_vencer['90']['multa'] += $query['Multa'];
                    $titulos_a_vencer['90']['juros_diarios'] += $query->percentualjurosdiario;
                    $titulos_a_vencer['90']['desconto'] += $query['Desconto'];
                }

                $valor_titulo = 0;

                if ($query['Situação do Título'] === 'Aberto') {
                    $valor_titulo = $query['Valor'];
                }else{
                    $valor_titulo = $query['Valor'] - $query['Valor da Baixa'];
                }

                $titulos_a_vencer_total['valor_original'] += $query['Valor'];
                $titulos_a_vencer_total['valor'] += $valor_titulo;
                $titulos_a_vencer_total['multa'] += $query['Multa'];
                $titulos_a_vencer_total['juros_diarios'] += $query->percentualjurosdiario;
                $titulos_a_vencer_total['desconto'] += $query['Desconto'];

            }

            $retorno['titulos']['total']['quantidade'] ++;
            $retorno['titulos']['total']['valor'] += $query['Valor'];
        });

        $retorno['titulos']['vencidos']['quantidade'] = ($retorno['titulos']['vencidos']['quantidade'] > 0) ? $retorno['titulos']['vencidos']['quantidade'] : '';
        $retorno['titulos']['vencidos']['valor'] = ($retorno['titulos']['vencidos']['valor'] > 0) ? parserValor($retorno['titulos']['vencidos']['valor']) : '';
        
        $retorno['titulos']['a_vencer']['quantidade'] = ($retorno['titulos']['a_vencer']['quantidade'] > 0) ? $retorno['titulos']['a_vencer']['quantidade'] : '';
        $retorno['titulos']['a_vencer']['valor'] = ($retorno['titulos']['a_vencer']['valor'] > 0) ? parserValor($retorno['titulos']['a_vencer']['valor']) : '';
        
        $retorno['titulos']['total']['quantidade'] = $retorno['titulos']['total']['quantidade'] > 0 ? $retorno['titulos']['total']['quantidade'] : '';
        $retorno['titulos']['total']['valor'] = $retorno['titulos']['total']['valor'] > 0 ? parserValor($retorno['titulos']['total']['valor']) : '';

        $pedidos_fornecedor = collect();

        $compras->each(function($query) use (&$retorno,&$pedidos_fornecedor,$data_atual){
            $data_atual = Carbon::parse($data_atual);
            $data_entrega = (!empty($query->data_entrega)) ? Carbon::parse($query->data_entrega) : null;
            $previsao_entrega = Carbon::parse($query->previsao_entrega);

            if(!empty($data_entrega)){

                if($data_entrega->gt($previsao_entrega) || $previsao_entrega->lt($data_atual)){
                    $pedidos_fornecedor->push([
                        'pedido' => $query->numero_pedido,
                        'valor' => $query->preco_compra,
                        'tipo' => 'vencido'
                    ]);

                    if($data_entrega->gt($previsao_entrega)){

                        if(empty($retorno['atraso_entrega']['ultimo']['data'])){
                            $retorno['atraso_entrega']['ultimo']['data'] = $data_entrega->format('Y-m-d');
                            $retorno['atraso_entrega']['ultimo']['dias'] = $previsao_entrega->diffInDays($data_entrega);
                            $retorno['atraso_entrega']['ultimo']['id_nota'] = encrypt($query->id_nota);
                        }else{
                            $ultima_entrega = Carbon::parse($retorno['atraso_entrega']['ultimo']['data']);
                            if($ultima_entrega->lte($data_entrega)){
                                $retorno['atraso_entrega']['ultimo']['dias'] = $previsao_entrega->diffInDays($data_entrega);
                                $retorno['atraso_entrega']['ultimo']['data'] = $data_entrega->format('Y-m-d');
                                $retorno['atraso_entrega']['ultimo']['id_nota'] = encrypt($query->id_nota);
                            }
                        }
                        
                        if(empty($retorno['atraso_entrega']['maior']['dias'])){
                            $retorno['atraso_entrega']['maior']['dias'] = $previsao_entrega->diffInDays($data_entrega);
                            $retorno['atraso_entrega']['maior']['data'] = $data_entrega->format('Y-m-d');
                            $retorno['atraso_entrega']['maior']['id_nota'] = encrypt($query->id_nota);
                        }else{
                            $ultima_diferenca = $retorno['atraso_entrega']['maior']['dias'];
                            $diferenca = $previsao_entrega->diffInDays($data_entrega);
                            if($ultima_diferenca < $diferenca){
                                $retorno['atraso_entrega']['maior']['data'] = $data_entrega->format('Y-m-d');
                                $retorno['atraso_entrega']['maior']['dias'] = $diferenca;
                                $retorno['atraso_entrega']['maior']['id_nota'] = encrypt($query->id_nota);
                            }
                        }

                    }
                }else if($data_entrega->lte($previsao_entrega) || $previsao_entrega->gte($data_atual)){
                    $pedidos_fornecedor->push([
                        'pedido' => $query->numero_pedido,
                        'valor' => $query->preco_compra,
                        'tipo' => 'a_vencer'
                    ]);
                }
    
            }else if($previsao_entrega->gte($data_atual)){
                $pedidos_fornecedor->push([
                    'pedido' => $query->numero_pedido,
                    'valor' => $query->preco_compra,
                    'tipo' => 'a_vencer'
                ]);
            }else if($previsao_entrega->lt($data_atual)){
                $pedidos_fornecedor->push([
                    'pedido' => $query->numero_pedido,
                    'valor' => $query->preco_compra,
                    'tipo' => 'vencido'
                ]);
            }

            $data_pedido = Carbon::parse($query->data_compra);

            if(empty($retorno['compras']['ultimo']['data'])){
                $retorno['compras']['ultimo']['data'] = $data_pedido->format('Y-m-d');
                $retorno['compras']['ultimo']['valor'] = $query->preco_compra_total;
                $retorno['compras']['ultimo']['id_nota'] = encrypt($query->id_nota);
            }else{
                $ultima_entrega = Carbon::parse($retorno['compras']['ultimo']['data']);
                if($ultima_entrega->lte($data_pedido)){
                    $retorno['compras']['ultimo']['valor'] = $query->preco_compra_total;
                    $retorno['compras']['ultimo']['data'] = $data_pedido->format('Y-m-d');
                    $retorno['compras']['ultimo']['id_nota'] = encrypt($query->id_nota);
                }
            }

            if(empty($retorno['compras']['maior']['valor'])){
                $retorno['compras']['maior']['valor'] = $query->preco_compra_total;
                $retorno['compras']['maior']['data'] = $data_pedido->format('Y-m-d');
                $retorno['compras']['maior']['id_nota'] = encrypt($query->id_nota);
            }else{
                $ultimo_valor = $retorno['compras']['maior']['valor'];
                $valor = $query->preco_compra_total;
                if($ultimo_valor < $valor){
                    $retorno['compras']['maior']['data'] = $data_pedido->format('Y-m-d');
                    $retorno['compras']['maior']['valor'] = $valor;
                    $retorno['compras']['maior']['id_nota'] = encrypt($query->id_nota);
                }
            }
        });

        $retime_tributario = [
            '0' => 'Indefinido',
            '1' => 'Optante',
            '2' => 'Não Optante'
        ];

        $score = ScoreFornecedore::whereHas('scoreFornecedores')
        ->with(['fornecedor','scoreFornecedores'])
        ->where('fornecedor_nasajon_id',$fornecedor_nasajon->id);
        $score = $score->get();
        $retorno_score = [];

        $score->each(function($query) use (&$retorno_score,$retime_tributario){
            $retorno_score[] = [
                'estado' =>  $query->fornecedor->uf,
                'data' => parserDataEHora($query->created_at),
                'fornecedor' => $query->fornecedor->nome.' - '.$query->fornecedor->cnpj_cpf,
                'regime_trinutário' =>  (isset($query->fornecedor->tiposimples)) ? $retime_tributario[$query->fornecedor->tiposimples] : '',
                'id_formulario' => encrypt($query->id)
            ];
        });

        $retorno['pedidos']['vencidos']['valor'] = ($pedidos_fornecedor->where('tipo','vencido')->sum('valor') > 0) ? parserValor($pedidos_fornecedor->where('tipo','vencido')->sum('valor')) : '';
        $retorno['pedidos']['vencidos']['quantidade'] = ($pedidos_fornecedor->where('tipo','vencido')->unique('pedido')->count() > 0) ? $pedidos_fornecedor->where('tipo','vencido')->unique('pedido')->count() : '';
        
        $retorno['pedidos']['a_vencer']['valor'] = ($pedidos_fornecedor->where('tipo','a_vencer')->sum('valor') > 0) ? parserValor($pedidos_fornecedor->where('tipo','a_vencer')->sum('valor')) : '';
        $retorno['pedidos']['a_vencer']['quantidade'] = ($pedidos_fornecedor->where('tipo','a_vencer')->unique('pedido')->count() > 0) ? $pedidos_fornecedor->where('tipo','a_vencer')->unique('pedido')->count() : '';
        
        $retorno['pedidos']['total']['valor'] = ($pedidos_fornecedor->sum('valor') > 0) ? parserValor($pedidos_fornecedor->sum('valor')) : '';
        $retorno['pedidos']['total']['quantidade'] = ($pedidos_fornecedor->unique('pedido')->count() > 0) ? $pedidos_fornecedor->unique('pedido')->count() : '';
        
        $retorno['pedidos']['a_vencer']['valor'] = ($retorno['pedidos']['a_vencer']['valor'] > 0) ? $retorno['pedidos']['a_vencer']['valor'] : '';
        $retorno['pedidos']['vencidos']['valor'] = ($retorno['pedidos']['vencidos']['valor'] > 0) ? $retorno['pedidos']['vencidos']['valor'] : '';
        $retorno['pedidos']['total']['valor'] = ($retorno['pedidos']['total']['valor'] > 0) ? $retorno['pedidos']['total']['valor'] : '';

        $retorno['titulos']['a_vencer'] = ($retorno['titulos']['a_vencer'] > 0) ? $retorno['titulos']['a_vencer'] :  '';
        $retorno['titulos']['vencidos'] = ($retorno['titulos']['vencidos'] > 0) ? $retorno['titulos']['vencidos'] :  '';
        $retorno['titulos']['total'] = ($retorno['titulos']['total'] > 0) ? $retorno['titulos']['total'] :  '';
        $retorno['pedidos']['a_vencer'] = ($retorno['pedidos']['a_vencer'] > 0) ? $retorno['pedidos']['a_vencer'] :  '';
        $retorno['pedidos']['vencidos'] = ($retorno['pedidos']['vencidos'] > 0) ? $retorno['pedidos']['vencidos'] :  '';
        $retorno['pedidos']['total'] = ($retorno['pedidos']['total'] > 0) ? $retorno['pedidos']['total'] :  '';
        
        $retorno['atraso_entrega']['maior']['data'] = (!empty($retorno['atraso_entrega']['maior']['data'])) ? Carbon::createFromFormat('Y-m-d',$retorno['atraso_entrega']['maior']['data']) :  '';
        $retorno['atraso_entrega']['maior']['data'] = (!empty($retorno['atraso_entrega']['maior']['data'])) ? $retorno['atraso_entrega']['maior']['data']->format('d/m/Y') : '';
        $retorno['atraso_entrega']['ultimo']['data'] = (!empty($retorno['atraso_entrega']['ultimo']['data'])) ? Carbon::createFromFormat('Y-m-d',$retorno['atraso_entrega']['ultimo']['data']) :  '';
        $retorno['atraso_entrega']['ultimo']['data'] = (!empty($retorno['atraso_entrega']['ultimo']['data'])) ? $retorno['atraso_entrega']['ultimo']['data']->format('d/m/Y') : '';

        $retorno['compras']['maior']['data'] = (!empty($retorno['compras']['maior']['data'])) ? Carbon::createFromFormat('Y-m-d',$retorno['compras']['maior']['data']) :  '';
        $retorno['compras']['maior']['data'] = (!empty($retorno['compras']['maior']['data'])) ? $retorno['compras']['maior']['data']->format('d/m/Y') : '';
        $retorno['compras']['maior']['valor'] = (!empty($retorno['compras']['maior']['valor'])) ? parserValor($retorno['compras']['maior']['valor']) :  '';

        $retorno['compras']['ultimo']['data'] = (!empty($retorno['compras']['ultimo']['data'])) ? Carbon::createFromFormat('Y-m-d',$retorno['compras']['ultimo']['data']) :  '';
        $retorno['compras']['ultimo']['data'] = (!empty($retorno['compras']['ultimo']['data'])) ? $retorno['compras']['ultimo']['data']->format('d/m/Y') : '';
        $retorno['compras']['ultimo']['valor'] = (!empty($retorno['compras']['ultimo']['valor'])) ? parserValor($retorno['compras']['ultimo']['valor']) :  '';
        
        foreach($titulos_a_vencer as $key_titulos => $valor_titulo){
            $titulos_a_vencer[$key_titulos]['valor_original'] = ($titulos_a_vencer[$key_titulos]['valor_original'] > 0) ? parserValor($titulos_a_vencer[$key_titulos]['valor_original']) : '';
            $titulos_a_vencer[$key_titulos]['valor'] = ($titulos_a_vencer[$key_titulos]['valor'] > 0) ? parserValor($titulos_a_vencer[$key_titulos]['valor']) : '';
            $titulos_a_vencer[$key_titulos]['multa'] = ($titulos_a_vencer[$key_titulos]['multa'] > 0) ? parserValor($titulos_a_vencer[$key_titulos]['multa']) : '';
            $titulos_a_vencer[$key_titulos]['juros_diarios'] = ($titulos_a_vencer[$key_titulos]['juros_diarios'] > 0) ? parserValor($titulos_a_vencer[$key_titulos]['juros_diarios']) : '';
            $titulos_a_vencer[$key_titulos]['desconto'] = ($titulos_a_vencer[$key_titulos]['desconto'] > 0) ? parserValor($titulos_a_vencer[$key_titulos]['desconto']) : '';
        }

        $titulos_a_vencer_total['valor_original'] = ($titulos_a_vencer_total['valor_original'] > 0) ? parserValor($titulos_a_vencer_total['valor_original']) : '';
        $titulos_a_vencer_total['valor'] = ($titulos_a_vencer_total['valor'] > 0) ? parserValor($titulos_a_vencer_total['valor']) : '';
        $titulos_a_vencer_total['multa'] = ($titulos_a_vencer_total['multa'] > 0) ? parserValor($titulos_a_vencer_total['multa']) : '';
        $titulos_a_vencer_total['juros_diarios'] = ($titulos_a_vencer_total['juros_diarios'] > 0) ? parserValor($titulos_a_vencer_total['juros_diarios']) : '';
        $titulos_a_vencer_total['desconto'] = ($titulos_a_vencer_total['desconto'] > 0) ? parserValor($titulos_a_vencer_total['desconto']) : '';

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['response' => $retorno,'score' => $retorno_score,'titulos_a_vencer' => $titulos_a_vencer,'titulos_a_vencer_total' => $titulos_a_vencer_total,  'devolucoes' => $devolucoes
       ],
        ]);
    }

    public function modalPedidos(Request $request){
        $filter = $request->only(['filter']);

        try{
            $fields = decrypt($filter['filter']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $fornecedor_nasajon = FornecedorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj_cpf))'), 'ilike','%' . trim($fields['fornecedor_nome']) . '%')->first();        

        if(empty($fornecedor_nasajon)){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $compras = ComprasNasajon::where('fornecedor_id',$fornecedor_nasajon->id)
        ->whereIn('situacao',['Aberto','Aguardando Documento','Parcialmente Liquidado','Cancelado'])
        ->select('id_nota','numero_pedido','situacao','data_compra','previsao_entrega','preco_compra_total',DB::raw('sum(preco_compra) as preco_compra,sum(quantidade) as quantidade, sum(quantidade - quantidade_restante) as quantidade_recebida, sum(quantidade_restante) as quantidade_restante'))
        ->groupBy('id_nota','numero_pedido','data_compra','previsao_entrega','preco_compra_total','situacao');

        if($fields['vencimento'] == 'a_vencer'){
            $compras->where(function($query){
                $query->where(DB::raw('case when data_entrega is null then previsao_entrega::date else data_entrega::date end'),'<=',DB::raw('previsao_entrega::date'));
                $query->where(DB::raw('previsao_entrega::date'),'>=',DB::raw('now()::date'));
            });
        }else if($fields['vencimento'] == 'vencidos'){
            $compras->where(function($query){
                $query->where(DB::raw('case when data_entrega is null then previsao_entrega::date else data_entrega::date end'),'>',DB::raw('previsao_entrega::date'));
                $query->orWhere(DB::raw('previsao_entrega::date'),'<',DB::raw('now()::date'));
            });
        }

        $compras = $compras->get();

        $array_pedidos_cancelados = [];
        $pedidos_cancelados = $compras->where('situacao','Cancelado')->unique('numero_pedido');

        $pedidos_cancelados->each(function($query) use (&$array_pedidos_cancelados){
            $array_pedidos_cancelados[] = $query->numero_pedido;
        });

        unset($pedidos_cancelados);
        $compras = $compras->whereNotIn('numero_pedido',$array_pedidos_cancelados);

        $retorno = [];
        $total = [
            'total_preco_compra_total' => 0,
            'total_quantidade' => 0,
            'total_quantidade_recebida' => 0,
            'total_quantidade_restante' => 0,
        ];

        foreach($compras as $compra){
            $retorno[] = [
                'numero_pedido' => $compra->numero_pedido,
                'data_compra' => parserData($compra->data_compra),
                'previsao_entrega' => parserData($compra->previsao_entrega),
                'preco_compra_total' => parserValor($compra->preco_compra),
                'quantidade' => ($compra->quantidade > 0) ? parserQtd($compra->quantidade) : '',
                'quantidade_recebida' => ($compra->quantidade_recebida > 0) ? parserValor($compra->quantidade_recebida) : '',
                'quantidade_restante' => ($compra->quantidade_restante > 0) ? parserQtd($compra->quantidade_restante) : '',
                'id_nota' => encrypt($compra->id_nota)

            ];

            $total['total_preco_compra_total'] += $compra->preco_compra;
            $total['total_quantidade'] += $compra->quantidade;
            $total['total_quantidade_recebida'] += $compra->quantidade_recebida;
            $total['total_quantidade_restante'] += $compra->quantidade_restante;
        };

        $total['total_preco_compra_total'] = ($total['total_preco_compra_total'] > 0) ? parserValor($total['total_preco_compra_total']) : '';
        $total['total_quantidade'] = ($total['total_quantidade'] > 0) ? parserValor($total['total_quantidade']) : '';
        $total['total_quantidade_recebida'] = ($total['total_quantidade_recebida'] > 0) ? parserValor($total['total_quantidade_recebida']) : '';
        $total['total_quantidade_restante'] = ($total['total_quantidade_restante'] > 0) ? parserValor($total['total_quantidade_restante']) : '';

        return view('programs.posicao_sintetica_fornecedor.modal.pedidos')->with(['dados' => $retorno,'total' => $total]);
    }

    public function filtroTitulos(PosicaoSinteticaFornecedorBuscaTitulosRequest $request){
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $filter = $request->only(['fornecedor','data_inicio_titulos','data_fim_titulos']);
        
        $TitulosAPagarNasajon = TitulosAPagarNasajon::where('Data do Vencimento', '!=', null)
        ->where('Estabelecimento', '!=', '25')
        ->with('notaEntrada');

        if(!empty($filter['data_inicio_titulos']) && !empty($filter['data_fim_titulos'])){
            $TitulosAPagarNasajon->whereBetween('Data do Vencimento', [$filter['data_inicio_titulos'],$filter['data_fim_titulos']]);
        }else if(!empty($filter['data_inicio_titulos']) && empty($filter['data_fim_titulos'])){
            $TitulosAPagarNasajon->where('Data do Vencimento','>=', $filter['data_inicio_titulos']);
        }else if(empty($filter['data_inicio_titulos']) && !empty($filter['data_fim_titulos'])){
            $TitulosAPagarNasajon->where('Data do Vencimento','<=', $filter['data_fim_titulos']);
        }

        if(!empty($filter['fornecedor']) ){
            $fornecedor = FornecedorNasajon::where(DB::raw('CONCAT(TRIM(nome), \' - \', cnpj_cpf)'),'ilike',$filter['fornecedor'])
            ->first();
            $fornecedorcodigo = [];
            $cnpj_cpf = (!empty($fornecedor )) ? $fornecedor->cnpj_cpf: null;
            if(!empty($cnpj_cpf)){
                if(strlen(trim($cnpj_cpf )) == 18){
                    $cnpj_cpf = substr($cnpj_cpf , 0, 10);
                }
                $FornecedorNasajonQuery = FornecedorNasajon::where('cnpj_cpf', 'like', $cnpj_cpf . '%');
                    $fornecedores = $FornecedorNasajonQuery->get();
                    foreach($fornecedores  as $fornecedor){
                        $fornecedorcodigo[] = [$fornecedor->codigo];
                    }
                    $TitulosAPagarNasajon->whereIn('Fornecedor', $fornecedorcodigo);
            }else{
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '', 
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
        }

        $TitulosAPagar = $TitulosAPagarNasajon->get();
        $estabelecimentos = returnTodasEmpresasView();
        $retorno = [];

        $TitulosAPagar->each(function($query) use (&$retorno,$estabelecimentos){
                if($query['Situação do Título'] === 'Aberto'){
                    $valor_titulo = $query['Valor'];
                }else{
                    $valor_titulo = $query['Valor'] - $query['Valor da Baixa'];
                }

                $linha = [];
                $linha['estabelecimento'] = $estabelecimentos[$query['Estabelecimento']];
                $linha['documento'] = (empty($titulo['Número Nota'])) ? $query->notaEntrada['Número do Documento'] : $query['Número Nota'];
                $linha['parcela'] = $query['Parcela'];
                $linha['data_emissao'] = parserData($query['Data de Emissão']);
                $linha['data_vencimento'] = parserData($query['Data do Vencimento']);
                $linha['valor_original'] = ($query['Valor'] > 0) ? parserValor($query['Valor']) : '';
                $linha['valor'] = ($valor_titulo > 0) ? parserValor($valor_titulo) : '';
                $linha['multa'] = ($query['Multa'] > 0) ? parserValor($query['Multa']) : '';
                $linha['numero_boleto'] = $query['Número do Título'];
                $linha['juros_diarios'] = ($query->percentualjurosdiario > 0) ? parserValor($query->percentualjurosdiario) : '';
                $linha['data_juros'] = '';
                $linha['desconto'] = ($query['Desconto'] > 0) ? parserValor($query['Desconto']) : '';
                $linha['POSICAO_CR'] = (!empty($query['Nosso número'])) ? $query['Nosso número'] : '';
                $linha['banco'] = '';

                $retorno[] = $linha;
        });

        $total = [
            'valor' => 0,
            'saldo' => 0,
            'juros' => 0,
        ];
        $valor = $TitulosAPagar->sum('Valor');
        $saldotitulo = $TitulosAPagar->sum('Valor da Baixa');
        $juros = $TitulosAPagar->sum('Multa');

        $saldo = $valor - $saldotitulo;

        unset($TitulosAPagar);
        $total = [
            'valor' => ($valor > 0) ? parserValor($valor) : '',
            'saldo' => ($saldo > 0) ? parserValor($saldo) : '',
            'juros' => ($juros > 0) ? parserValor($juros) : '',
        ];

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['response' => $retorno, "totalizadores" => $total],
        ]);
    }

    public function filtroPedidos(PosicaoSinteticaFornecedorBuscaPedidosRequest $request){
        
        $filter = $request->only(['data_inicio_pedidos','data_fim_pedidos','fornecedor','situacao']);
        
        $fornecedor_nasajon = FornecedorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj_cpf))'), 'ilike','%' . trim($filter['fornecedor']) . '%')->first();        

        if(empty($fornecedor_nasajon)){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $data_inicio = Carbon::createFromFormat('d/m/Y', $filter['data_inicio_pedidos'])->setTime(0,0,0);
        $data_fim = Carbon::createFromFormat('d/m/Y', $filter['data_fim_pedidos'])->setTime(23,59,59);
        
        $compras = ComprasNasajon::where('fornecedor_id',$fornecedor_nasajon->id)
        ->whereBetween('data_compra',[$data_inicio,$data_fim])
        ->select('id_nota','numero_pedido','data_compra','previsao_entrega','situacao',DB::raw('sum(preco_compra) as preco_compra,sum(quantidade) as quantidade, sum(quantidade - quantidade_restante) as quantidade_recebida, sum(quantidade_restante) as quantidade_restante'))
        ->groupBy('id_nota','numero_pedido','data_compra','previsao_entrega','situacao');

        if(!empty($filter['situacao'])){
            if($filter['situacao'] == 'Aberto'){
                $compras->whereIn('situacao',['Aberto','Aguardando Documento','Parcialmente Liquidado','Cancelado']);
            }else{
                $compras->where('situacao',$filter['situacao']);
            }
        }

        $compras = $compras->get();

        if(!empty($filter['situacao'])){
            if($filter['situacao'] == 'Aberto'){
                $array_pedidos_cancelados = [];
                $pedidos_cancelados = $compras->where('situacao','Cancelado')->unique('numero_pedido');

                $pedidos_cancelados->each(function($query) use (&$array_pedidos_cancelados){
                    $array_pedidos_cancelados[] = $query->numero_pedido;
                });

                unset($pedidos_cancelados);
                $compras = $compras->whereNotIn('numero_pedido',$array_pedidos_cancelados);
            }
        }

        $retorno = [];
        $total = [
            'total_preco_compra_total' => 0,
            'total_quantidade' => 0,
            'total_quantidade_recebida' => 0,
            'total_quantidade_restante' => 0,
        ];

        $compras->each(function($query) use (&$retorno,&$total){
            $retorno[] = [
                'numero_pedido' => $query->numero_pedido,
                'data_compra' => parserData($query->data_compra),
                'previsao_entrega' => parserData($query->previsao_entrega),
                'preco_compra_total' => parserValor($query->preco_compra),
                'quantidade' => ($query->quantidade > 0) ? parserQtd($query->quantidade) : '',
                'quantidade_recebida' => ($query->quantidade_recebida > 0) ? parserValor($query->quantidade_recebida) : '',
                'quantidade_restante' => ($query->quantidade_restante > 0) ? parserQtd($query->quantidade_restante) : '',
                'id_nota' => encrypt($query->id_nota)

            ];

            $total['total_preco_compra_total'] += $query->preco_compra;
            $total['total_quantidade'] += $query->quantidade;
            $total['total_quantidade_recebida'] += $query->quantidade_recebida;
            $total['total_quantidade_restante'] += $query->quantidade_restante;
        });

        $total['total_preco_compra_total'] = ($total['total_preco_compra_total'] > 0) ? parserValor($total['total_preco_compra_total']) : '';
        $total['total_quantidade'] = ($total['total_quantidade'] > 0) ? parserValor($total['total_quantidade']) : '';
        $total['total_quantidade_recebida'] = ($total['total_quantidade_recebida'] > 0) ? parserValor($total['total_quantidade_recebida']) : '';
        $total['total_quantidade_restante'] = ($total['total_quantidade_restante'] > 0) ? parserValor($total['total_quantidade_restante']) : '';

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['response' => $retorno, "totalizadores" => $total],
        ]);
    }

    public function titulosAvencer(Request $request){
        $filtro = $request->only(['filtro']);

        try{
            $fields = decrypt($filtro['filtro']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $TitulosAPagarNasajon = TitulosAPagarNasajon::whereIn('Situação do Título', ['Aberto', 'Em Débito'])
        ->where('Data do Vencimento', '!=', null)
        ->whereIn('Fornecedor',$fields['fornecedor_id']);
        
        if(isset($fields['data_inicial']) && isset($fields['data_final'])){
            $data_inicial = $fields['data_inicial']->format('Y-m-d');
            $data_final = $fields['data_final']->format('Y-m-d');
            $TitulosAPagarNasajon->whereBetween('Data do Vencimento', [$data_inicial, $data_final]);
        }else if(isset($fields['data_inicial']) && !isset($fields['data_final'])){
            $data_inicial = $fields['data_inicial']->format('Y-m-d');
            $TitulosAPagarNasajon->where('Data do Vencimento','>=',$data_inicial);
        }

        $TitulosAPagarNasajon->where('Estabelecimento', '!=', '25');

        $TitulosAPagar = $TitulosAPagarNasajon->get();
        $estabelecimentos = returnTodasEmpresasView();
        $cnpjFornecedor = [];
        $cnpjFornecedor[] = ['cnpj' =>  'cnpj'];
        $cnpjFornecedor[] = ['result' =>  '2'];
        $retorno = [];

        foreach ($TitulosAPagar->chunk(100) as $chunk) {
            foreach ($chunk as $titulo) {

                if ($titulo['Situação do Título'] === 'Aberto') {
                    $valor_titulo = $titulo['Valor'];
                } else {
                    $valor_titulo = $titulo['Valor'] - $titulo['Valor da Baixa'];
                }

                $linha = [];

                $linha['estabelecimento'] = $estabelecimentos[$titulo['Estabelecimento']];
                $linha['documento'] = $titulo['Número Nota'];
                $linha['parcela'] = $titulo['Parcela'];
                $linha['data_emissao'] = $titulo['Data de Emissão'];
                $linha['data_vencimento'] = parserData($titulo['Data do Vencimento']);
                $linha['valor_original'] = $titulo['Valor'];
                $linha['valor'] = $valor_titulo;
                $linha['multa'] = $titulo['Multa'];
                $linha['nome_fornecedor'] = $titulo['Razão Social do Fornecedor'] . ' - ' . $titulo['CNPJ/CPF do Fornecedor'];
                $linha['numero_boleto'] = $titulo['Número do Título'];
                $linha['juros_diarios'] = $titulo->percentualjurosdiario;
                $linha['data_juros'] = '';
                $linha['desconto'] = $titulo['Desconto'];
                $linha['POSICAO_CR'] = $titulo['Nosso número'];
                $linha['POSICAO_CR_DESCRICAO'] = '';
                $linha['banco'] = '';

                $retorno[] = $linha;
            }
        }

        $total = [
            'valor' => 0,
            'saldo' => 0,
            'juros' => 0,
        ];

        $valor = $TitulosAPagar->sum('Valor');
        $saldotitulo = $TitulosAPagar->sum('Valor da Baixa');
        $juros = $TitulosAPagar->sum('Multa');

        $saldo = $valor - $saldotitulo;

        unset($TitulosAPagar);
        $total = [
            'valor' => ($valor > 0) ? parserValor($valor) : '',
            'saldo' => ($saldo > 0) ? parserValor($saldo) : '',
            'juros' => ($juros > 0) ? parserValor($juros) : '',
        ];
 
        return view('programs.titulos_apagar.modal.titulos_faturados')->with(["dados" => $retorno, "cod_fornecedor" => $cnpjFornecedor, "totalizadores" => $total]);
    }

   
}
