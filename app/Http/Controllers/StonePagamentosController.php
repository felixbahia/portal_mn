<?php

namespace App\Http\Controllers;

use Auth;

use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\StonePagamentosFiltroRequest;
use App\Http\Requests\StonePagamentosRegistrarRequest;

use App\PedidoPortal;
use App\CartoesContratosNasajon;
use App\CartoesMeiosEletronicosNasajon;
use App\CartoesOperadorasNasajon;
use App\CartoesBandeirasNasajon;
use App\FormaPagamentoNasajon;
use App\ParcelamentoNasajon;
use App\StoneCadastroMaquininha;
use App\StonePagamentosParciai;
use App\StoneTransacoesPedido;
use App\StoneErroTransacoesPedido;
use App\PedidoBloqueadoPagamentoNasajon;
use App\StoneTransacoesPagamentosRestante;
use App\ContasNasajon;
use App\User;
use App\PedidoFormaPagamentoNasajon;

use App\Http\Controllers\StoneTransacaoController;

use Exception;
use PhpParser\Node\Expr\FuncCall;

class StonePagamentosController extends Controller
{
    /*
    *
    * Bandeiras usadas no lançamento de pagamentos manuais
    */
    private $bandeiras_nasajon = [
        'Visa' => 'be5ffccb-86b1-422c-a1c2-0130f15992d7',
        'MasterCard' => '11228c0d-e9d1-4ea0-8e3e-bc53077b6362',
        'Amex' => '9a60b8bf-35cc-488f-b93e-52670c4b1ddb',
        'Elo' => 'b0f50491-0b45-4880-80f5-470e3a4e8fc3',
        'Hipercard' => '6d657fc3-5fa0-4343-b0f8-d4b924ba10ee',
        'Cabal' => '235cf0a2-2b0d-4f7a-b6ea-5e80339e6576'
    ];

    /*
    *
    * Formadas de pagamentos com o UUID usados no lançamento manual
    */
    private $forma_pagamento_descricao = [
        'cartao_credito' => 'c41decd7-935f-449a-9a60-fd1a2330661b',
        'cartao_debito' => 'b0444787-b579-422d-af2a-ce691cbff825',
        'dinheiro' => '04f167be-2b90-427d-ba3d-eb712c0e938b',
        'usar_credito' => 'f6661441-835e-41a5-88a5-9db2224aad4d'
    ];
    
    /*
    *
    * Operações de cartão buscadas na view CartoesOperadorasNasajon
    */
    private $operacao_cartao_nasajon = [
        'nao_identificado' => 'c4b95a55-71f1-4047-936f-8dced97cbc12'
    ];

    private $meios_eletronicos = [
        'stone' => 'Stone'
    ];

    private $cnpj_operadora = '16.501.555/0001-57';

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\StonePagamentos") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\StonePagamentos');

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        return view('programs.stone_pagamentos.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function filtro(StonePagamentosFiltroRequest $request){
        set_time_limit(900);
        ini_set('memory_limit','1024M');
        $campos = $request->only(['estabelecimento','cliente_id','pagamentos','numero_pedido','data_inicio','data_fim']);

        $data_inicio = Carbon::createFromFormat('d/m/Y', $campos['data_inicio'])->format('Y-m-d 00:00:00');
        $data_fim = Carbon::createFromFormat('d/m/Y', $campos['data_fim'])->format('Y-m-d 23:59:59');

        $transacoes_verificar = StoneTransacoesPedido::whereBetween('created_at',[$data_inicio,$data_fim])
        ->where('status_pre_transacao','1')
        ->orWhere('pagamento_parcial',true)
        ->select('pedido_id')
        ->get();

        $transacoes_finalizadas = $transacoes_verificar->pluck('pedido_id')->toArray();

        $pedidos = StoneTransacoesPedido::with(['pedido.cliente','maquininha','retornoTransacaoAvulsa','pagamentoRestante' => function($query){
            $query->where('pago',true);
        },'pagamentoRestante.pagamentosParciais','pedido.pedidoNasajon' => function($query){
            $query->where('situacao_descricao','<>','Cancelado')
            ->where(function($query){
                $query->where('grupodeoperacao','VENDA')
                ->orWhereNull('grupodeoperacao');
            });
        },'pedido' => Function($query) use ($campos){
            $query->whereNotIn('status_pedido',[7,13,5])
            ->where('cartao',true);

            if(!empty($campos['cliente_id'])){
                $query->where('cod_cliente',$campos['cliente_id']);
            }

            if(!empty($campos['numero_pedido'])){
                $query->where('id',trim($campos['numero_pedido']));
            }

            if(!empty($campos['estabelecimento'])){
                $query->where('estabelecimento',str_pad($campos['estabelecimento'],2,'0',STR_PAD_LEFT));
            }
    
        },'pedido.pedidoNasajon.valorTotalSeparacao','pagamentosParciais','pedido.pedidoNasajon.notaEmAberto','pedido.pedidoNasajon.nota','retornoTransacaoAvulsa'])
        ->whereHas('pedido',Function($query) use ($campos){
            $query->whereNotIn('status_pedido',[7,13,5])
            ->where('cartao',true);

            if(!empty($campos['cliente_id'])){
                $query->where('cod_cliente',$campos['cliente_id']);
            }

            if(!empty($campos['numero_pedido'])){
                $query->where('id',trim($campos['numero_pedido']));
            }

            if(!empty($campos['estabelecimento'])){
                $query->where('estabelecimento',str_pad($campos['estabelecimento'],2,'0',STR_PAD_LEFT));
            }
    
        });

        $pedidos->whereBetween('created_at',[$data_inicio,$data_fim]);

        if($campos['pagamentos'] == 'sem_pagamentos'){
            $pedidos->where(function($query){
                $query->where('status_pre_transacao','0')
                ->whereNull('pagamento_parcial');
            });
        }else if($campos['pagamentos'] == 'com_pagamentos'){
            $pedidos->where(function($query){
                $query->where('status_pre_transacao','1')
                ->orWhere('pagamento_parcial', true);
            });
        }
        
        $pedidos = $pedidos->get();
        
        $estabelecimentos = returnEmpresasNasajonView();
        $retorno = [];
        $verifica_pedidos_repetidos = $pedidos->where('status_pre_transacao','1')->where('stone_transaction_id','<>','')->pluck('pedido_id')->toArray();
        $pedidos_manuais_repetidos = $pedidos->where('pagamento_parcial',true)->pluck('pedido_id')->toArray();
        $pedidos_duplicados = [];
        
        foreach($pedidos as $query_pedido){
            if(empty($query_pedido->pagamento_parcial) &&  in_array($query_pedido->pedido_id,$pedidos_manuais_repetidos) && empty($query_pedido->pos_serial_number)){
                continue;
            }

            if(empty($query_pedido->stone_transaction_id) && in_array($query_pedido->pedido_id,$verifica_pedidos_repetidos)){
                continue;
            }

            if(empty($query_pedido->pagamento_parcial) && in_array($query_pedido->pedido_id,$transacoes_finalizadas) && $query_pedido->status_pre_transacao == '0'){
                continue;
            }

            if(in_array($query_pedido->pedido_id,$pedidos_duplicados) && $query_pedido->status_pre_transacao == '0' || in_array($query_pedido->pedido_id,$pedidos_duplicados) && $query_pedido->status_pre_transacao == ''){
                continue;
            }

            if($query_pedido->status_pre_transacao == '0' && empty($query_pedido->pagamento_parcial) || $query_pedido->status_pre_transacao == '' && empty($query_pedido->pagamento_parcial)){
                $pedidos_duplicados[$query_pedido->pedido_id] = $query_pedido->pedido_id;
            }

                $modo_pagamento = '';
                
                if($query_pedido->pagamento_restante === true && $query_pedido->pago === false){
                    $modo_pagamento = 'restante';
                }else if($query_pedido->pagamento_restante === true && $query_pedido->pago === true && !empty($query_pedido->pagamentoRestante->pagamento_parcial) && $query_pedido->pagamentoRestante->pagamento_parcial === true){
                    $modo_pagamento = 'restante_pago_manual';
                }else if($query_pedido->pagamento_restante === true && $query_pedido->pago === true && !empty($query_pedido->pagamentoRestante->pagamento_parcial) && $query_pedido->pagamentoRestante->pagamento_parcial !== true){
                    $modo_pagamento = 'automatico';
                }else if($query_pedido->pagamento_parcial == true){
                    $modo_pagamento = 'manual';
                }else if($query_pedido->status_pre_transacao == '1' && $query_pedido->pagamento_restante !== true){
                    $modo_pagamento = 'automatico';
                }else if($query_pedido->pago === true && $query_pedido->pagamento_restante !== true){
                    $modo_pagamento = 'automatico';
                }else if($query_pedido->pago === false && $query_pedido->pagamento_restante !== true && $query_pedido->pagamento_parcial === null){
                    $modo_pagamento = 'automatico';
                }

                $tipo_abertura = '';

                if(!empty($query_pedido->pedido->pedidoNasajon->situacao_descricao) && $query_pedido->pedido->pedidoNasajon->situacao_descricao == 'Faturado' && $query_pedido->payment_type == 1){
                    $tipo_abertura = 'debito_com_nota';
                }else if(!empty($query_pedido->pedido->pedidoNasajon->situacao_descricao) && $query_pedido->pedido->pedidoNasajon->situacao_descricao == 'Faturado' && $query_pedido->payment_type == 2){
                    $tipo_abertura = 'credito_com_nota';
                }else if(!empty($query_pedido->pedido->pedidoNasajon->situacao_descricao) && $query_pedido->pedido->pedidoNasajon->situacao_descricao != 'Faturado' && $query_pedido->payment_type == 1){
                    $tipo_abertura = 'debito_sem_nota';
                }else if(!empty($query_pedido->pedido->pedidoNasajon->situacao_descricao) && $query_pedido->pedido->pedidoNasajon->situacao_descricao != 'Faturado' && $query_pedido->payment_type == 2){
                    $tipo_abertura = 'credito_sem_nota';
                }

                if(!empty($query_pedido->pedido->pedidoNasajon->situacao_descricao) && $query_pedido->pedido->pedidoNasajon->situacao_descricao == 'Faturado' && !empty($query_pedido->retornoTransacaoAvulsa[0]) && $query_pedido->retornoTransacaoAvulsa[0]->metadata_account_funding_source == 'Debit'){
                    $tipo_abertura = 'debito_com_nota';
                }else if(!empty($query_pedido->pedido->pedidoNasajon->situacao_descricao) && $query_pedido->pedido->pedidoNasajon->situacao_descricao == 'Faturado' && !empty($query_pedido->retornoTransacaoAvulsa[0]) && $query_pedido->retornoTransacaoAvulsa[0]->metadata_account_funding_source == 'Credit'){
                    $tipo_abertura = 'credito_com_nota';
                }else if(!empty($query_pedido->pedido->pedidoNasajon->situacao_descricao) && $query_pedido->pedido->pedidoNasajon->situacao_descricao != 'Faturado' && !empty($query_pedido->retornoTransacaoAvulsa[0]) && $query_pedido->retornoTransacaoAvulsa[0]->metadata_account_funding_source == 'Debit'){
                    $tipo_abertura = 'debito_sem_nota';
                }else if(!empty($query_pedido->pedido->pedidoNasajon->situacao_descricao) && $query_pedido->pedido->pedidoNasajon->situacao_descricao != 'Faturado' && !empty($query_pedido->retornoTransacaoAvulsa[0]) && $query_pedido->retornoTransacaoAvulsa[0]->metadata_account_funding_source == 'Credit'){
                    $tipo_abertura = 'credito_sem_nota';
                }

                $status = '';
                $valor_restante = 0;
                $valor_faturado = 0;
                $valor_pago_total = 0;
                $valor_separacao = 0;
                
                if($query_pedido->pedido->cod_cliente == '0000010069999'){
                    if(!empty($query_pedido->pedido->pedidoNasajon->valorTotalSeparacao->separacao)){
                        $valor_separacao = $query_pedido->pedido->pedidoNasajon->valorTotalSeparacao->separacao;
                    }
                }else if(isset($query_pedido->pedido->pedidoNasajon->notaEmAberto->valor)){
                    $valor_separacao = floatVal($query_pedido->pedido->pedidoNasajon->notaEmAberto->valor);
                }else if(isset($query_pedido->pedido->pedidoNasajon->nota->valor)){
                    $valor_separacao = floatVal($query_pedido->pedido->pedidoNasajon->nota->valor);
                }else if ($valor_separacao <= 0 && $query_pedido->pedido->cod_cliente != '0000010069999' && !empty($query_pedido->pedido->pedidoNasajon->valorTotalSeparacao->separacao)){
                    $valor_separacao = $query_pedido->pedido->pedidoNasajon->valorTotalSeparacao->separacao;
                }

                if($query_pedido->status_pre_transacao == '0' && !in_array($query_pedido->pedido_id,$transacoes_finalizadas) && empty($query_pedido->pagamento_parcial) && $query_pedido->pagamento_restante !== true && $query_pedido->pago === null || 
                    $query_pedido->status_pre_transacao == null && !in_array($query_pedido->pedido_id,$transacoes_finalizadas) && empty($query_pedido->pagamento_parcial) && $query_pedido->pagamento_restante !== true && $query_pedido->pago === null){
                    $status = '<center>
                        <div><div data-toggle="tooltip" data-html="true" title="" data-placement="top" data-original-title="Sem Confirmação">
                            <i class="bt-bloqueado" aria-hidden="true"></i>
                        </div></div>
                    </center>';
                }else if($query_pedido->status_pre_transacao == '1' && $query_pedido->pagamento_restante === true  && $query_pedido->pago === false || $query_pedido->status_pre_transacao == '0' && $query_pedido->pagamento_restante === true  && $query_pedido->pago === false ){
                    $status = '<center>
                        <div>
                            <div>
                                <i data-toggle="tooltip" data-html="true" title="" data-placement="top" data-original-title="Aguardando Pagamento da Diferença" class="bt-bloqueado" aria-hidden="true"></i>
                            </div>
                        </div>
                    </center>';
                }else if($query_pedido->status_pre_transacao == '1' && $query_pedido->credito === true || $query_pedido->credito === true && $query_pedido->pago === true ){
                    $status = '<center>
                        <div data-toggle="tooltip" data-html="true" title="" data-original-title="Confirmado com Crédito Gerado">
                            <i class="fa fa-check check-icon-aprovado" aria-hidden="true"></i>
                        </div>
                    </center>';
                }else if($query_pedido->status_pre_transacao == '1' && $valor_separacao > 0 || $query_pedido->pagamento_parcial === true && $query_pedido->pago === true || $query_pedido->pago === true){
                    $status = '<center>
                        <div><div data-toggle="tooltip" data-html="true" title="" data-placement="top" data-original-title="Pagamento Completo Confirmado">
                            <i class="fa fa-check check-icon" aria-hidden="true"></i>
                        </div></div>
                    </center>';
                }else if($query_pedido->status_pre_transacao == '1' && $valor_separacao <= 0 || $query_pedido->pagamento_parcial === true && $query_pedido->pago === false || $query_pedido->pago === false && $valor_separacao > 0){
                    $status = '<center>
                        <div><div data-toggle="tooltip" data-html="true" title="" data-placement="top" data-original-title="Pagamento Confirmado e Aguardando Separação">
                            <i class="fa fa-check check-icon" aria-hidden="true"></i>
                        </div></div>
                    </center>';
                }else if($valor_separacao <= 0){
                    $status = '<center>
                        <div>
                            <div>
                                <i data-toggle="tooltip" data-html="true" title="" data-placement="top" data-original-title="Pago e Aguardando Separação" class="bt-bloqueado" aria-hidden="true"></i>
                            </div>
                        </div>
                    </center>';
                }else if($query_pedido->pago === false && $query_pedido->pagamento_restante === true){
                    $status = '<center>
                        <div>
                            <div>
                                <i data-toggle="tooltip" data-html="true" title="" data-placement="top" data-original-title="Aguardando Pagamento da Diferença" class="bt-bloqueado" aria-hidden="true"></i>
                            </div>
                        </div>
                    </center>';
                }


                if($valor_separacao > 0 && empty($query_pedido->retornoTransacaoAvulsa)){
                    $valor_faturado = $valor_separacao;

                    $valor_restante = ($query_pedido->transaction_amount > 0) ? $valor_faturado - $query_pedido->transaction_amount : 0;
                    
                    if(isset($query_pedido->pagamentoRestante->id) && $valor_separacao > 0){
                        $valor_pago_total = ((int) number_format($query_pedido->transaction_amount * 100, 0, "", "") / 100);

                        if(!empty($query_pedido->pagamentoRestante->pagamentosParciais->first())){
                            foreach($query_pedido->pagamentoRestante->pagamentosParciais as $parcial_restante){
                                $valor_pago_total += ((int) number_format($parcial_restante->valor * 100, 0, "", "") / 100);
                            }
                        }else{
                            $valor_pago_total += ((int) number_format($query_pedido->pagamentoRestante->transaction_amount * 100, 0, "", "") / 100);
                        }

                    }else if(isset($query_pedido->pagamentoRestante->id)){
                        $valor_pago_total = ((int) number_format(($query_pedido->transaction_amount + $query_pedido->pagamentoRestante->transaction_amount) * 100, 0, "", "") / 100);
                    }

                }

                $valor_parcial = 0;

                if($query_pedido->pagamento_parcial == true && !empty($query_pedido->pagamentosParciais)){
                    foreach($query_pedido->pagamentosParciais as $valor_pagamentos_parciais){
                        $valor_parcial += $valor_pagamentos_parciais->valor;
                    }
                }else if(!empty($query_pedido->transaction_amount)){
                    $valor_parcial = $query_pedido->transaction_amount;
                }

                $valor_transacao_connect = 0;
                
                if(!empty($query_pedido->retornoTransacaoAvulsa)){
                    $valor_transacao_connect += ($query_pedido->retornoTransacaoAvulsa->where('data_status','paid')->sum('data_paid_amount') / 100);

                    $valor_faturado = $valor_separacao;
                    
                    $valor_restante = ($valor_transacao_connect > 0) ? $valor_faturado - ($valor_transacao_connect + $valor_parcial) : 0;
                    
                    $valor_pago_total = ($valor_pago_total + $valor_parcial);

                    if(!empty($query_pedido->pagamentoRestante->pagamentosParciais[0])){
                        foreach($query_pedido->pagamentoRestante->pagamentosParciais as $pagamento_restante){
                            $valor_pago_total += $pagamento_restante->valor;
                        }
                    }
                }

                if($valor_pago_total !== $valor_transacao_connect){
                    $valor_pago_total += $valor_transacao_connect;
                }

                $cliente = '';

                if(!empty($query_pedido->pedido->cliente->nome)){
                    $cliente = str_replace(["\"","'"],'',(string)($query_pedido->pedido->cliente->nome.' - '.$query_pedido->pedido->cliente->cpf_cnpj));
                }
                
                $retorno[] = [
                    'estabelecimento' => $estabelecimentos[(int)$query_pedido->pedido->estabelecimento],
                    'pedido_nasajon' => (!empty($query_pedido->pedido->pedidoNasajon->numero)) ? $query_pedido->pedido->pedidoNasajon->numero : '',
                    'pedido' => $query_pedido->pedido_id,
                    'cliente' => $cliente,
                    'data' => (!empty($query_pedido->created_at)) ? parserDataEHora($query_pedido->created_at) : '',
                    'nota_id' => (!empty($query_pedido->pedido->pedidoNasajon->notafiscal_id)) ? $query_pedido->pedido->pedidoNasajon->notafiscal_id : '',
                    'nota_numero' => (!empty($query_pedido->pedido->pedidoNasajon->notafiscal_numero)) ? $query_pedido->pedido->pedidoNasajon->notafiscal_numero : '',
                    'status' => $status,
                    'maquininha' => (!empty($query_pedido->maquininha->descricao)) ? $query_pedido->maquininha->descricao : '',
                    'valor' => parserValor($query_pedido->pedido->valor_total_produtos),
                    'valor_transacao' => ($valor_pago_total > 0) ? parserValor($valor_pago_total) : parserValor($valor_parcial),
                    'valor_faturado' => parserValor($valor_faturado),
                    'modo_pagamento' => $modo_pagamento,
                    'id_stone_transacao_pedido' => $query_pedido->id,
                    'status_pre_transacao' => $query_pedido->status_pre_transacao,
                    'tipo_abertura' => $tipo_abertura,
                    'valor_pago' => ($valor_pago_total > 0) ? parserValor($valor_pago_total) : '',
                    'valor_restante' => parserValor($valor_restante),
                    'valor_pago_total' => parserValor($valor_pago_total)
                ];
        }
        
        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'retorno'=> $retorno
            ]
        ],200);
    }

    public function modalRegistrarPagamento(Request $request){
        $campos = $request->only('pedido_id');
        $id_bandeiras = $this->bandeiras_nasajon;
        $id_formas_pagamentos = $this->forma_pagamento_descricao;
        $bandeiras = [];
        $forma_pagamento = [];
        $parcelamentos = [];
        $maquininha = [];

        $bandeiras_nasajon = CartoesBandeirasNasajon::whereIn('bandeiracartao',$id_bandeiras)->select('bandeiracartao','codigo')->get();
        $bandeiras_nasajon->each(function($query) use (&$bandeiras){
            $bandeiras[$query->bandeiracartao] = $query->codigo;
        });

        $forma_pagamento_nasajon = FormaPagamentoNasajon::whereIn('formapagamento',$id_formas_pagamentos)->orderBy('descricao')->get();
        $forma_pagamento_nasajon->each(function($query) use (&$forma_pagamento){
            $forma_pagamento[$query->formapagamento] = $query->descricao;
        });
        
        $parcelamento_nasajon = ParcelamentoNasajon::where('nome','ilike','%CARTAO%')->orderBy('nome')->get();
        $parcelamento_nasajon->each(function($query) use (&$parcelamentos){
            $parcelamentos[$query->parcelamento] = $query->nome;
        });
        unset($parcelamentos['2931da42-bb7b-44d5-acb7-01e26d10ca4c']);
        $maquininhas_stone = StoneCadastroMaquininha::get();
        $maquininhas_stone->each(function($query) use (&$maquininha){
            $maquininha[$query->id] = $query->descricao;
        });

        return view('programs.stone_pagamentos.modal.registrar_pagamentos')->with([
            'bandeiras' => $bandeiras,
            'pedido_id' => $campos['pedido_id'],
            'forma_pagamento' => $forma_pagamento,
            'parcelamentos' => $parcelamentos,
            'maquininhas' => $maquininha
        ]);
    }

    public function registrar(Request $request){
        set_time_limit(900);
        ini_set('memory_limit','2048M');

        $campos = $request->only(['pedido_id','bandeiras','codigo_autorizacao','forma_pagamento','parcelamento','data_autorizacao','valor','tid']);
        
        $pedido = PedidoPortal::with(['pagamentosStone','pedidoNasajon','estabelecimentoDetalhes','pagamentosStoneTransacoesAvulsas'])
        ->whereHas('pagamentosStone', function($query) use ($campos){
            $query->where('id',$campos['pedido_id'])
            ->whereNull('pago')
            ->whereNull('pagamento_parcial');
        })
        ->first();
        
        if(empty($pedido)){
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido não encontrado.',
                'error' => [],
                'response' => []
            ],422);
        }

        $verificar_valor = 0;
        
        foreach($campos['valor'] as $campo_valor){
            $verificar_valor += parserNumber($campo_valor);
        }

        
        if(!empty($pedido->pagamentosStoneTransacoesAvulsas[0])){
            $verificar_valor += $pedido->pagamentosStoneTransacoesAvulsas->sum('data_paid_amount');
        }

        $valor_separado = 0;

        if($pedido->cod_cliente == '0000010069999'){// verifica cliente balcão
            if(!empty($pedido->pedidoNasajon->valorTotalSeparacao->separacao)){
                $valor_separado = $pedido->pedidoNasajon->valorTotalSeparacao->separacao;
            }
        }else if(isset($pedido->pedidoNasajon->notaEmAberto->valor)){
            $valor_separado = floatVal($pedido->pedidoNasajon->notaEmAberto->valor);
        }else if(isset($pedido->pedidoNasajon->nota->valor)){
            $valor_separado = floatVal($pedido->pedidoNasajon->nota->valor);
        }

        if($pedido->cod_cliente != '0000010069999' && $valor_separado === 0){
            if(!empty($pedido->pedidoNasajon->valorTotalSeparacao->separacao)){
                $valor_separado = $pedido->pedidoNasajon->valorTotalSeparacao->separacao;
            }
        }

        $valor_pedido = parserValor($pedido->valor_total_produtos);

        $valor_pedido_nota = parserValor($pedido->valor_total_nota);

        $diferenca_pagamento = $verificar_valor - $pedido->valor_total_produtos;

        if(parserValor($verificar_valor) !=  parserValor($valor_separado) && $valor_pedido != parserValor($verificar_valor) && $valor_pedido_nota != parserValor($verificar_valor) && $diferenca_pagamento < 0.01){
            return response()->json([
                'status' => 'error',
                'message' => 'O valor do(s) pagamento(s) inserido(s) é diferente do separado ou total do pedido.',
                'error' => [],
                'response' => []
            ],422);
        }
        
        foreach($campos['forma_pagamento'] as $key_campos => $campos_array){
            $valor = parserNumber($campos['valor'][$key_campos]);

            if($valor <= 0){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Valor Inválido.',
                    'error' => [],
                    'response' => []
                ],422);
            }

            $data_autorizacao = Carbon::createFromFormat('d/m/Y', $campos['data_autorizacao'][$key_campos])->format('Y-m-d');

            if($campos['forma_pagamento'][$key_campos] != 'desconto'){
                $forma_pagamento_nasajon = FormaPagamentoNasajon::where('formapagamento',$campos['forma_pagamento'][$key_campos])->first();

                if(!isset($forma_pagamento_nasajon->tipo)){
                    return  response()->json([
                        'status' => 'error',
                        'message' => 'Erro ao integrar com Nasajon, forma de pagamento não encontrada.',
                        'error' => [],
                        'response' => []
                    ], 422);
                }
            }

            $tipo_pagamento = '';

            if($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d'){
                $parcelamento_nasajon = ParcelamentoNasajon::where('parcelamento',$campos['parcelamento'][$key_campos])->first();
                
                if($parcelamento_nasajon->parcelamento == '99e8ff10-c34a-4391-a735-d5d78a41d737' || $campos['forma_pagamento'][$key_campos] == 'b0444787-b579-422d-af2a-ce691cbff825'){
                    $tipo_pagamento = 1;
                }else if($parcelamento_nasajon->parcelamento == '2931da42-bb7b-44d5-acb7-01e26d10ca4c' || $parcelamento_nasajon->parcelamento == 'a755fefa-8e42-4d63-84bd-9644bfc134f5'){
                    $tipo_pagamento = 2;
                }else if($campos['forma_pagamento'][$key_campos] != 'desconto'){
                    $tipo_pagamento = 3;
                }

                $id_operadora = CartoesOperadorasNasajon::whereIn('operadoracartao',$this->operacao_cartao_nasajon)->first();
                $id_bandeira = CartoesBandeirasNasajon::where('bandeiracartao',$campos['bandeiras'][$key_campos])->first();
                $id_meio_eletronico = CartoesMeiosEletronicosNasajon::whereIn('codigo',$this->meios_eletronicos)->first();

                $contrato_cartao_nasajon = CartoesContratosNasajon::where('estabelecimento',$pedido->estabelecimentoDetalhes->estabelecimento)
                ->where('bandeiracartao',$id_bandeira->bandeiracartao)
                ->where('tipooperacao',$tipo_pagamento)
                ->where('meioeletronicocartao',$id_meio_eletronico->meioeletronicocartao)
                ->first();
                
                $parcelamento = '';

                if(!isset($contrato_cartao_nasajon->contratocartao)){
                    return  response()->json([
                        'status' => 'error',
                        'message' => 'Contrato de cartão inválido.',
                        'error' => [],
                        'response' => []
                    ], 422);
                }
                
                if($campos['forma_pagamento'][$key_campos] == 'b0444787-b579-422d-af2a-ce691cbff825'){
                    $parcelamento = "99e8ff10-c34a-4391-a735-d5d78a41d737";
                }else{
                    $parcelamento = $campos['parcelamento'][$key_campos];
                }

            }
            
            if($campos['forma_pagamento'][$key_campos] == 'b0444787-b579-422d-af2a-ce691cbff825'){
                $parcelamento = '99e8ff10-c34a-4391-a735-d5d78a41d737';
            }else if($campos['forma_pagamento'][$key_campos] == '04f167be-2b90-427d-ba3d-eb712c0e938b'){
                $parcelamento = '04f167be-2b90-427d-ba3d-eb712c0e938b';
            }else if($campos['forma_pagamento'][$key_campos] == 'f6661441-835e-41a5-88a5-9db2224aad4d'){
                $parcelamento = 'f6661441-835e-41a5-88a5-9db2224aad4d';
            }
            
            if($campos['forma_pagamento'][$key_campos] == '04f167be-2b90-427d-ba3d-eb712c0e938b' || $campos['forma_pagamento'][$key_campos] == 'f6661441-835e-41a5-88a5-9db2224aad4d'){
                $tipo_pagamento = 1;
            }

            $pagamentos_parciais = new StonePagamentosParciai;
            $pagamentos_parciais->stone_transacoes_pedido_id = $campos['pedido_id'];
            $pagamentos_parciais->forma_pagamento = (!empty($forma_pagamento_nasajon->formapagamento)) ? $forma_pagamento_nasajon->formapagamento : null;
            $pagamentos_parciais->parcelamento = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d') ? $parcelamento : null;
            $pagamentos_parciais->valor = parserNumber($campos['valor'][$key_campos]);
            $pagamentos_parciais->codigo_autoriazacao = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d') ?  $campos['codigo_autorizacao'][$key_campos] : null;
            $pagamentos_parciais->data_autorizacao = $data_autorizacao;
            $pagamentos_parciais->documento_cartao = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d') ? $campos['tid'][$key_campos] : null;
            $pagamentos_parciais->contrato_cartao = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d') ? $contrato_cartao_nasajon->contratocartao : null;
            $pagamentos_parciais->cnpj_operadora = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d') ? $this->cnpj_operadora : null;
            $pagamentos_parciais->meio_eletronico = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d') ? $id_meio_eletronico->meioeletronicocartao : null;
            $pagamentos_parciais->operadora = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d') ? $id_operadora->operadoracartao : null;
            $pagamentos_parciais->bandeira = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d') ? $id_bandeira->bandeiracartao : null;
            $pagamentos_parciais->tipo_operacao = ($campos['forma_pagamento'][$key_campos] != 'desconto') ? (int)$tipo_pagamento : null;
            $pagamentos_parciais->created_by = Auth::user()->id;
            $pagamentos_parciais->api_nasajon = false;
            $pagamentos_parciais->save();

        }

        $valor_pago = 0;

        $atualizar_transacao = StoneTransacoesPedido::with(['pagamentosParciais','pedido','retornoTransacaoAvulsa'])->findOrFail($campos['pedido_id']);
        $atualizar_transacao->status_pre_transacao = '1';
        $atualizar_transacao->payment_type = ($tipo_pagamento == 3) ? 2 : $tipo_pagamento;
        $atualizar_transacao->data_transacao = $data_autorizacao;
        $atualizar_transacao->transaction_amount = $verificar_valor;
        $atualizar_transacao->installments_number = 'Parcial';
        $atualizar_transacao->transaction_authorization_code = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d') ? $campos['codigo_autorizacao'][$key_campos] : null;
        $atualizar_transacao->stone_transaction_id =  ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d') ? $campos['tid'][$key_campos] : null;
        $atualizar_transacao->card_brand = 'Parcial';
        $atualizar_transacao->pagamento_parcial = true;
        $atualizar_transacao->updated_by = Auth::user()->id;
        $atualizar_transacao->save();

        if($atualizar_transacao->pago === null){
            $verifica_valor_pedido = (int)str_replace([',','.'],'',parserValor($atualizar_transacao->pedido->valor_total_produtos)) / 100;
            $valor_parcial = (int)str_replace([',','.'],'',parserValor($atualizar_transacao->pagamentosParciais->sum('valor'))) / 100;
            $valor_transacao = ($atualizar_transacao->retornoTransacaoAvulsa->where('data_status','paid')->sum('data_paid_amount') > 0) ? $atualizar_transacao->retornoTransacaoAvulsa->where('data_status','paid')->sum('data_paid_amount') / 100 : 0;

            $valor_pago = $valor_transacao + $valor_parcial;
            $diferenca = $valor_pago - $verifica_valor_pedido;

            if($diferenca == 0 || $diferenca > -1 && $diferenca < 1){// diferença paga inferior a 0.99
                $atualizar_transacao->pago = false;
                $atualizar_transacao->save();
                $integrar_nadajon = new AprovacaoDePedidoController;

                $integrar_nadajon->processaIntegracaoPedidoNasajon($pedido,null);

                $pedido = PedidoPortal::find($pedido->id);
                $pedido->status_pedido = 3;
                $pedido->save();

                if(isset($pedido->pagamentosStone[0]->orders[0]) && !empty($pedido->pagamentosStone[0]->orders[0])){
                    $virifica_transacao_finalizada = false;
        
                    foreach($pedido->pagamentosStone[0]->orders as $transacao_avulsa){
                        foreach($pedido->pagamentosStone as $orders){
                            foreach($orders->orders as $retorno_order){
                                $cancelado = true;
                                if(!empty($retorno_order->retornoTransacoes[0])){
                                    $order = $retorno_order->order_id;
                                    $transacao_controller = new StoneTransacaoController();
                                    $transacao_controller->finalizarTransacao($pedido,$order,'canceled');
                                }else{
                                    foreach($retorno_order->retornoTransacoes as $transacao_order){
                                        if($transacao_order->data_status == 'paid'){
                                            $cancelado = false;
                                        }
                                    }
        
                                    if($cancelado === false){
                                        $order = $retorno_order->order_id;
                                        $transacao_controller = new StoneTransacaoController();
                                        $transacao_controller->finalizarTransacao($pedido,$order,'paid');
                                    }else{
                                        $order = $retorno_order->order_id;
                                        $transacao_controller = new StoneTransacaoController();
                                        $transacao_controller->finalizarTransacao($pedido,$order,'canceled');
                                    }
                                }
                            }
        
                        }
                    }
        
                }
            }
        }else{
            $atualizar_transacao->pago = false;
            $atualizar_transacao->save();

            $integrar_nadajon = new AprovacaoDePedidoController;
            $integrar_nadajon->processaIntegracaoPedidoNasajon($pedido,null);
            $pedido = PedidoPortal::find($pedido->id);
            $pedido->status_pedido = 3;
            $pedido->save();
        }
        

        return  response()->json([
            'status' => 'success',
            'message' => 'Pagamento(s) registrado(s) com sucesso.',
            'error' => [],
            'response' => []
        ], 200);
    }

    public function modalEditarPagamento(Request $request){
        $campos = $request->only(['pedido_id']);

        $pedido = PedidoPortal::with(['pagamentosStone' => function($query) use ($campos){
            $query->where('id',$campos['pedido_id']);
        },'pagamentosStone.pagamentosParciais'])
        ->whereHas('pagamentosStone', function($query) use ($campos){
            $query->where('id',$campos['pedido_id']);
        })
        ->whereHas('pagamentosStone.pagamentosParciais')
        ->first();
        
        $retorno = [];
        $total = 0;

        $pedido->pagamentosStone->each(function($query) use (&$retorno,&$total){
            foreach($query->pagamentosParciais as $pagamento){
                $retorno[] = [
                    'forma_pagamento' => (empty($pagamento->forma_pagamento)) ? 'desconto' : $pagamento->forma_pagamento,
                    'parcelamento' => $pagamento->parcelamento,
                    'autorizacao' => $pagamento->codigo_autoriazacao,
                    'data_autorizacao' => parserData($pagamento->data_autorizacao),
                    'documento_cartao' => $pagamento->documento_cartao,
                    'bandeira' => $pagamento->bandeira,
                    'valor'=> parserValor($pagamento->valor),
                    'id_pagamento_nasajon' => encrypt($pagamento->id_pagamento_nasajon),
                    'id' => encrypt($pagamento->id)
                ];

                $total += $pagamento->valor;
            }
        });

        $contador = count($retorno);
        $total = parserValor($total);
        
        $id_bandeiras = $this->bandeiras_nasajon;
        $id_formas_pagamentos = $this->forma_pagamento_descricao;
        $bandeiras = [];
        $forma_pagamento = [];
        $parcelamentos = [];
        $maquininha = [];

        $bandeiras_nasajon = CartoesBandeirasNasajon::whereIn('bandeiracartao',$id_bandeiras)->select('bandeiracartao','codigo')->get();
        $bandeiras_nasajon->each(function($query) use (&$bandeiras){
            $bandeiras[$query->bandeiracartao] = $query->codigo;
        });

        $forma_pagamento_nasajon = FormaPagamentoNasajon::whereIn('formapagamento',$id_formas_pagamentos)->orderBy('descricao')->get();
        $forma_pagamento_nasajon->each(function($query) use (&$forma_pagamento){
            $forma_pagamento[$query->formapagamento] = $query->descricao;
        });

        $parcelamento_nasajon = ParcelamentoNasajon::where('nome','ilike','%CARTAO%')->orderBy('nome')->get();
        $parcelamento_nasajon->each(function($query) use (&$parcelamentos){
            $parcelamentos[$query->parcelamento] = $query->nome;
        });

        $maquininhas_stone = StoneCadastroMaquininha::get();
        $maquininhas_stone->each(function($query) use (&$maquininha){
            $maquininha[$query->id] = $query->descricao;
        });

        return view('programs.stone_pagamentos.modal.editar_pagamentos')->with([
            'pagamentos' => $retorno,
            'contador' => $contador,
            'total' => $total,
            'bandeiras' => $bandeiras,
            'pedido_id' => $campos['pedido_id'],
            'forma_pagamento' => $forma_pagamento,
            'parcelamentos' => $parcelamentos,
            'maquininhas' => $maquininha
        ]);
    }

    public function editarPagamento(Request $request){
        set_time_limit(900);
        ini_set('memory_limit','2048M');

        $campos = $request->only(['pedido_id','bandeiras','codigo_autorizacao','forma_pagamento','parcelamento','data_autorizacao','valor','tid','id_pagamento_nasajon','id']);
        
        $pedido = PedidoPortal::with(['pagamentosStone','pedidoNasajon','estabelecimentoDetalhes'])
        ->whereHas('pagamentosStone', function($query) use ($campos){
            $query->where('id',$campos['pedido_id']);
        })
        ->first();

        $verificar_valor = 0;

        foreach($campos['valor'] as $campo_valor){
            $verificar_valor += parserNumber($campo_valor);
        }
        return;
        if(empty($pedido)){
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido não encontrado.',
                'error' => [],
                'response' => []
            ],422);
        }

        $valor_separado = 0;

        if($pedido->cod_cliente == '0000010069999'){// verifica cliente balcão
            if(!empty($pedido->pedidoNasajon->valorTotalSeparacao->separacao)){
                $valor_separado = $pedido->pedidoNasajon->valorTotalSeparacao->separacao;
            }
        }else if(isset($pedido->pedidoNasajon->notaEmAberto->valor)){
            $valor_separado = floatVal($pedido->pedidoNasajon->notaEmAberto->valor);
        }else if(isset($pedido->pedidoNasajon->nota->valor)){
            $valor_separado = floatVal($pedido->pedidoNasajon->nota->valor);
        }
        
        if(parserValor($verificar_valor) !=  parserValor($valor_separado) || $valor_separado <= 0){
            return response()->json([
                'status' => 'error',
                'message' => 'O valor do(s) pagamento(s) inserido(s) é diferente do separado.',
                'error' => [],
                'response' => []
            ],422);
        }

        foreach($campos['forma_pagamento'] as $key_campos => $campos_array){
            $valor = parserNumber($campos['valor'][$key_campos]);
            
            if($valor <= 0){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Valor Inválido.',
                    'error' => [],
                    'response' => []
                ],422);
            }

            try{
                $id_pagamento_parcial = decrypt($campos['id'][$key_campos]);
            }catch(Exception $e){
                return  response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao receber ID do pagamento parcial, contate o setor responsável.',
                    'error' => [$e],
                    'response' => []
                ], 422);
            }

            if($campos['forma_pagamento'][$key_campos] != 'desconto'){
                $forma_pagamento_nasajon = FormaPagamentoNasajon::where('formapagamento',$campos['forma_pagamento'][$key_campos])->first();

                if(!isset($forma_pagamento_nasajon->tipo)){
                    return  response()->json([
                        'status' => 'error',
                        'message' => 'Erro ao integrar com Nasajon, forma de pagamento não encontrada.',
                        'error' => [],
                        'response' => []
                    ], 422);
                }
            }

            $data_autorizacao = Carbon::createFromFormat('d/m/Y', $campos['data_autorizacao'][$key_campos])->format('Y-m-d');
                
            if($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' && $campos['forma_pagamento'][$key_campos] != 'desconto'){
                $parcelamento_nasajon = ParcelamentoNasajon::where('parcelamento',$campos['parcelamento'][$key_campos])->first();
                $tipo_pagamento = '';

                if($parcelamento_nasajon->parcelamento == '99e8ff10-c34a-4391-a735-d5d78a41d737' || $campos['forma_pagamento'][$key_campos] == 'b0444787-b579-422d-af2a-ce691cbff825' || $campos['forma_pagamento'][$key_campos] == 'f6661441-835e-41a5-88a5-9db2224aad4d'){
                    $tipo_pagamento = 1;
                }else if($parcelamento_nasajon->parcelamento == '2931da42-bb7b-44d5-acb7-01e26d10ca4c' || $parcelamento_nasajon->parcelamento == 'a755fefa-8e42-4d63-84bd-9644bfc134f5'){
                    $tipo_pagamento = 2;
                }else{
                    $tipo_pagamento = 3;
                }

                $id_operadora = CartoesOperadorasNasajon::whereIn('operadoracartao',$this->operacao_cartao_nasajon)->first();
                $id_bandeira = CartoesBandeirasNasajon::where('bandeiracartao',$campos['bandeiras'][$key_campos])->first();
                $id_meio_eletronico = CartoesMeiosEletronicosNasajon::whereIn('codigo',$this->meios_eletronicos)->first();

                $contrato_cartao_nasajon = CartoesContratosNasajon::where('estabelecimento',$pedido->estabelecimentoDetalhes->estabelecimento)
                ->where('bandeiracartao',$id_bandeira->bandeiracartao)
                ->where('tipooperacao',$tipo_pagamento)
                ->where('meioeletronicocartao',$id_meio_eletronico->meioeletronicocartao)
                ->first();

                $parcelamento = '';

                if(!isset($contrato_cartao_nasajon->contratocartao)){
                    return  response()->json([
                        'status' => 'error',
                        'message' => 'Contrato de cartão inválido.',
                        'error' => [],
                        'response' => []
                    ], 422);
                }
                
                if($campos['forma_pagamento'][$key_campos] == 'b0444787-b579-422d-af2a-ce691cbff825'){
                    $parcelamento = "99e8ff10-c34a-4391-a735-d5d78a41d737";
                }else{
                    $parcelamento = $campos['parcelamento'][$key_campos];
                }
            }

            if($campos['forma_pagamento'][$key_campos] == 'b0444787-b579-422d-af2a-ce691cbff825'){
                $parcelamento = '99e8ff10-c34a-4391-a735-d5d78a41d737';
            }else if($campos['forma_pagamento'][$key_campos] == '04f167be-2b90-427d-ba3d-eb712c0e938b'){
                $parcelamento = '04f167be-2b90-427d-ba3d-eb712c0e938b';
            }else if($campos['forma_pagamento'][$key_campos] == 'f6661441-835e-41a5-88a5-9db2224aad4d'){
                $parcelamento = 'f6661441-835e-41a5-88a5-9db2224aad4d';
            }else if($campos['forma_pagamento'][$key_campos] != 'desconto'){
                $parcelamento = $parcelamento_nasajon->parcelamento;
            }

            if($campos['forma_pagamento'][$key_campos] == '04f167be-2b90-427d-ba3d-eb712c0e938b' || $campos['forma_pagamento'][$key_campos] == 'desconto' || $campos['forma_pagamento'][$key_campos] == 'f6661441-835e-41a5-88a5-9db2224aad4d'){
                $tipo_pagamento = 1;
            }

            $pagamentos_parciais = StonePagamentosParciai::find($id_pagamento_parcial);
            $pagamentos_parciais->stone_transacoes_pedido_id = (int)$campos['pedido_id'];
            $pagamentos_parciais->forma_pagamento = ($campos['forma_pagamento'][$key_campos] != 'desconto') ? $forma_pagamento_nasajon->formapagamento : null;
            $pagamentos_parciais->parcelamento = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $parcelamento : null;
            $pagamentos_parciais->valor = parserNumber($campos['valor'][$key_campos]);
            $pagamentos_parciais->codigo_autoriazacao = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $campos['codigo_autorizacao'][$key_campos] : null;
            $pagamentos_parciais->data_autorizacao = $data_autorizacao;
            $pagamentos_parciais->documento_cartao = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $campos['tid'][$key_campos] : null;
            $pagamentos_parciais->contrato_cartao = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $contrato_cartao_nasajon->contratocartao : null;
            $pagamentos_parciais->cnpj_operadora = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $this->cnpj_operadora : null;
            $pagamentos_parciais->meio_eletronico = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $id_meio_eletronico->meioeletronicocartao : null;
            $pagamentos_parciais->operadora =($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $id_operadora->operadoracartao : null;
            $pagamentos_parciais->bandeira = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $id_bandeira->bandeiracartao : null;
            $pagamentos_parciais->tipo_operacao = ($campos['forma_pagamento'][$key_campos] != 'desconto') ? (int)$tipo_pagamento : null;
            $pagamentos_parciais->updated_by = Auth::user()->id;
            $pagamentos_parciais->updated_at = Carbon::now();
            $pagamentos_parciais->save();
        }

        $atualizar_transacao = StoneTransacoesPedido::findOrFail((int)$campos['pedido_id']);
        $atualizar_transacao->status_pre_transacao = '1';
        $atualizar_transacao->transaction_amount = $verificar_valor;
        $atualizar_transacao->api_nasajon = null;
        $atualizar_transacao->updated_by = Auth::user()->id;
        $atualizar_transacao->updated_at = Carbon::now();
        $atualizar_transacao->save();
        
        return  response()->json([
            'status' => 'success',
            'message' => 'Pagamento(s) alterado(s) com sucesso.',
            'error' => [],
            'response' => []
        ], 200);
    }

    public function modalRegistrarPagamentoRestante(Request $request){
        $campos = $request->only('pedido_id');
        $id_bandeiras = $this->bandeiras_nasajon;
        $id_formas_pagamentos = $this->forma_pagamento_descricao;
        $bandeiras = [];
        $forma_pagamento = [];
        $parcelamentos = [];
        $maquininha = [];
        $transacao_paga = [];

        try{
            $transacao = StoneTransacoesPedido::findOrFail((int)$campos['pedido_id']);
        }catch(Exception $e){
            return  response()->json([
                'status' => 'error',
                'message' => 'Transação não encontrada.',
                'error' => [$e],
                'response' => []
            ], 422);
        }

        $forma_pagamento_pago = '';

        if($transacao->payment_type == 1){
            $forma_pagamento_pago = 'Débito';
        }else if($transacao->payment_type == 2){
            $forma_pagamento_pago = 'Crédito';
        }

        $transacao_paga_valor = (!empty($transacao->retornoTransacaoAvulsa) && (float)($transacao->retornoTransacaoAvulsa->sum('data_paid_amount') / 100)) ? (float)($transacao->retornoTransacaoAvulsa->sum('data_paid_amount') / 100) : $transacao->transaction_amount;

        $transacao_paga['forma_pagamento_pago'] = (!empty($forma_pagamento_pago)) ? $forma_pagamento_pago : 'Multiplos Pagamentos';
        $transacao_paga['bandeiras_pago'] = $transacao->card_brand;
        $transacao_paga['codigo_autorizacao_pago'] = $transacao->transaction_authorization_code;
        $transacao_paga['parcelamento_pago'] = $transacao->installments_number;
        $transacao_paga['data_autorizacao_pago'] = (!empty($transacao->data_transacao)) ? parserData($transacao->data_transacao) : 'Multiplos Pagamentos';
        $transacao_paga['valor_pago'] = parserValor($transacao_paga_valor);
        $transacao_paga['stine_id_pago'] = $transacao->stone_transaction_id;
        
        $bandeiras_nasajon = CartoesBandeirasNasajon::whereIn('bandeiracartao',$id_bandeiras)->select('bandeiracartao','codigo')->get();
        $bandeiras_nasajon->each(function($query) use (&$bandeiras){
            $bandeiras[$query->bandeiracartao] = $query->codigo;
        });

        $forma_pagamento_nasajon = FormaPagamentoNasajon::whereIn('formapagamento',$id_formas_pagamentos)->orderBy('descricao')->get();
        $forma_pagamento_nasajon->each(function($query) use (&$forma_pagamento){
            $forma_pagamento[$query->formapagamento] = $query->descricao;
        });
        
        $parcelamento_nasajon = ParcelamentoNasajon::where('nome','ilike','%CARTAO%')->orderBy('nome')->get();
        $parcelamento_nasajon->each(function($query) use (&$parcelamentos){
            $parcelamentos[$query->parcelamento] = $query->nome;
        });

        $maquininhas_stone = StoneCadastroMaquininha::get();
        $maquininhas_stone->each(function($query) use (&$maquininha){
            $maquininha[$query->id] = $query->descricao;
        });

        return view('programs.stone_pagamentos.modal.registrar_pagamento_restante')->with([
            'bandeiras' => $bandeiras,
            'pedido_id' => $campos['pedido_id'],
            'forma_pagamento' => $forma_pagamento,
            'parcelamentos' => $parcelamentos,
            'maquininhas' => $maquininha,
            'transacao_paga' => $transacao_paga
        ]);
    }

    public function registrarRestante(Request $request){
        set_time_limit(900);
        ini_set('memory_limit','2048M');

        $campos = $request->only(['pedido_id','bandeiras','codigo_autorizacao','forma_pagamento','parcelamento','data_autorizacao','valor','tid']);
        
        $pedido = PedidoPortal::with(['pagamentosStone' => function($query) use ($campos){
            $query->where('id',$campos['pedido_id'])
            ->with('orders.retornoTransacoes')
            ->where('pago',false)
            ->where('pagamento_restante',true);
        },'pedidoNasajon','estabelecimentoDetalhes','pagamentosStone.retornoTransacaoAvulsa'])
        ->whereHas('pagamentosStone', function($query) use ($campos){
            $query->where('id',$campos['pedido_id'])
            ->where('pagamento_restante',true);
        })
        ->first();

        if(empty($pedido)){
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido não encontrado.',
                'error' => [],
                'response' => []
            ],422);
        }

        $verificar_valor = 0;
        $valor_pago = $pedido->pagamentosStone[0]->transaction_amount;

        foreach($campos['valor'] as $campo_valor){
            $verificar_valor += parserNumber($campo_valor);
        }

        $valor_separado = 0;

        if($pedido->cod_cliente == '0000010069999'){// verifica cliente balcão
            if(!empty($pedido->pedidoNasajon->valorTotalSeparacao->separacao)){
                $valor_separado = $pedido->pedidoNasajon->valorTotalSeparacao->separacao;
            }
        }else if(isset($pedido->pedidoNasajon->notaEmAberto->valor)){
            $valor_separado = floatVal($pedido->pedidoNasajon->notaEmAberto->valor);
        }else if(isset($pedido->pedidoNasajon->nota->valor)){
            $valor_separado = floatVal($pedido->pedidoNasajon->nota->valor);
        }

        $verifica_transacao_automatica = StoneTransacoesPagamentosRestante::whereHas('orders.retornoTransacoes',function($query){
            $query->whereNull('finalizado');
        })
        ->with(['orders.retornoTransacoes' => function($query){
            $query->whereNull('finalizado');
        }])
        ->where('stone_transacoes_pedido_id',$campos['pedido_id'])
        ->first();

        if(!empty($verifica_transacao_automatica)){
            $valor_transacao_automatica = ($verifica_transacao_automatica->orders[0]->retornoTransacoes->where('data_status','paid')->sum('data_paid_amount') > 0) ? $verifica_transacao_automatica->orders[0]->retornoTransacoes->where('data_status','paid')->sum('data_paid_amount') / 100 : 0;
            $valor_pago = $valor_pago + $valor_transacao_automatica;
        }

        if(isset($pedido->pagamentosStone[0]->retornoTransacaoAvulsa[0]) && !empty($pedido->pagamentosStone[0]->retornoTransacaoAvulsa[0])){
            $valor_pago += (float)$pedido->pagamentosStone[0]->retornoTransacaoAvulsa->sum('data_paid_amount') / 100;
        }

        if(parserValor(($verificar_valor + $valor_pago)) !=  parserValor($valor_separado) || $valor_separado <= 0){
            return response()->json([
                'status' => 'error',
                'message' => 'O valor do(s) pagamento(s) inserido(s) é diferente do separado.',
                'error' => [],
                'response' => []
            ],422);
        }

        foreach($campos['forma_pagamento'] as $key_campos => $campos_array){
            $valor = parserNumber($campos['valor'][$key_campos]);

            if($valor <= 0){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Valor Inválido.',
                    'error' => [],
                    'response' => []
                ],422);
            }

            $data_autorizacao = Carbon::createFromFormat('d/m/Y', $campos['data_autorizacao'][$key_campos])->format('Y-m-d');

            if($campos['forma_pagamento'][$key_campos] != 'desconto'){
                $forma_pagamento_nasajon = FormaPagamentoNasajon::where('formapagamento',$campos['forma_pagamento'][$key_campos])->first();

                if(!isset($forma_pagamento_nasajon->tipo)){
                    return  response()->json([
                        'status' => 'error',
                        'message' => 'Erro ao integrar com Nasajon, forma de pagamento não encontrada.',
                        'error' => [],
                        'response' => []
                    ], 422);
                }
            }

            $tipo_pagamento = '';

            if($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' && $campos['forma_pagamento'][$key_campos] != 'desconto'){
                $parcelamento_nasajon = ParcelamentoNasajon::where('parcelamento',$campos['parcelamento'][$key_campos])->first();

                if($parcelamento_nasajon->parcelamento == '99e8ff10-c34a-4391-a735-d5d78a41d737' || $campos['forma_pagamento'][$key_campos] == 'b0444787-b579-422d-af2a-ce691cbff825'){
                    $tipo_pagamento = 1;
                }else if($parcelamento_nasajon->parcelamento == '2931da42-bb7b-44d5-acb7-01e26d10ca4c' || $parcelamento_nasajon->parcelamento == 'a755fefa-8e42-4d63-84bd-9644bfc134f5'){
                    $tipo_pagamento = 2;
                }else if($campos['forma_pagamento'][$key_campos] != 'desconto'){
                    $tipo_pagamento = 3;
                }

                $id_operadora = CartoesOperadorasNasajon::whereIn('operadoracartao',$this->operacao_cartao_nasajon)->first();
                $id_bandeira = CartoesBandeirasNasajon::where('bandeiracartao',$campos['bandeiras'][$key_campos])->first();
                $id_meio_eletronico = CartoesMeiosEletronicosNasajon::whereIn('codigo',$this->meios_eletronicos)->first();

                $contrato_cartao_nasajon = CartoesContratosNasajon::where('estabelecimento',$pedido->estabelecimentoDetalhes->estabelecimento)
                ->where('bandeiracartao',$id_bandeira->bandeiracartao)
                ->where('tipooperacao',$tipo_pagamento)
                ->where('meioeletronicocartao',$id_meio_eletronico->meioeletronicocartao)
                ->first();
                
                $parcelamento = '';

                if(!isset($contrato_cartao_nasajon->contratocartao)){
                    return  response()->json([
                        'status' => 'error',
                        'message' => 'Contrato de cartão inválido.',
                        'error' => [],
                        'response' => []
                    ], 422);
                }
                
                if($campos['forma_pagamento'][$key_campos] == 'b0444787-b579-422d-af2a-ce691cbff825'){
                    $parcelamento = "99e8ff10-c34a-4391-a735-d5d78a41d737";
                }else{
                    $parcelamento = $campos['parcelamento'][$key_campos];
                }

            }
            
            if($campos['forma_pagamento'][$key_campos] == 'b0444787-b579-422d-af2a-ce691cbff825'){
                $parcelamento = '99e8ff10-c34a-4391-a735-d5d78a41d737';
            }else if($campos['forma_pagamento'][$key_campos] == '04f167be-2b90-427d-ba3d-eb712c0e938b'){
                $parcelamento = '04f167be-2b90-427d-ba3d-eb712c0e938b';
            }else if($campos['forma_pagamento'][$key_campos] == 'f6661441-835e-41a5-88a5-9db2224aad4d'){
                $parcelamento = 'f6661441-835e-41a5-88a5-9db2224aad4d';
            }
            
            if($campos['forma_pagamento'][$key_campos] == '04f167be-2b90-427d-ba3d-eb712c0e938b' || $campos['forma_pagamento'][$key_campos] == 'f6661441-835e-41a5-88a5-9db2224aad4d'){
                $tipo_pagamento = 1;
            }

            $atualizar_transacao = StoneTransacoesPagamentosRestante::with('retornoTransacaoAvulsa')->where('stone_transacoes_pedido_id',$campos['pedido_id'])->first();

            $pagamentos_parciais = new StonePagamentosParciai;
            $pagamentos_parciais->forma_pagamento = ($campos['forma_pagamento'][$key_campos] != 'desconto') ? $forma_pagamento_nasajon->formapagamento : null;
            $pagamentos_parciais->parcelamento = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' && $campos['forma_pagamento'][$key_campos] != 'desconto') ? $parcelamento : null;
            $pagamentos_parciais->valor = parserNumber($campos['valor'][$key_campos]);
            $pagamentos_parciais->codigo_autoriazacao = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ?  $campos['codigo_autorizacao'][$key_campos] : null;
            $pagamentos_parciais->data_autorizacao = $data_autorizacao;
            $pagamentos_parciais->documento_cartao = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $campos['tid'][$key_campos] : null;
            $pagamentos_parciais->contrato_cartao = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $contrato_cartao_nasajon->contratocartao : null;
            $pagamentos_parciais->cnpj_operadora = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $this->cnpj_operadora : null;
            $pagamentos_parciais->meio_eletronico = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $id_meio_eletronico->meioeletronicocartao : null;
            $pagamentos_parciais->operadora = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $id_operadora->operadoracartao : null;
            $pagamentos_parciais->bandeira = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $id_bandeira->bandeiracartao : null;
            $pagamentos_parciais->tipo_operacao = ($campos['forma_pagamento'][$key_campos] != 'desconto') ? (int)$tipo_pagamento : null;
            $pagamentos_parciais->created_by = Auth::user()->id;
            $pagamentos_parciais->api_nasajon = false;
            $pagamentos_parciais->stone_transacoes_pagamento_restantes_id = $atualizar_transacao->id;
            $pagamentos_parciais->save();

            $atualizar_transacao->status_pre_transacao = '1';
            $atualizar_transacao->payment_type = ($tipo_pagamento == 3) ? 2 : $tipo_pagamento;
            $atualizar_transacao->data_transacao = $data_autorizacao;
            $atualizar_transacao->transaction_amount = $verificar_valor;
            $atualizar_transacao->installments_number = 'Parcial';
            $atualizar_transacao->pago = true;
            $atualizar_transacao->transaction_authorization_code = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d') ? $campos['codigo_autorizacao'][$key_campos] : null;
            $atualizar_transacao->stone_transaction_id =  ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d') ? $campos['tid'][$key_campos] : null;
            $atualizar_transacao->card_brand = 'Parcial';
            $atualizar_transacao->pagamento_parcial = true;
            $atualizar_transacao->updated_by = Auth::user()->id;
            $atualizar_transacao->save();
        }

        $transacao = StoneTransacoesPedido::findOrFail((int)$campos['pedido_id']);
        $transacao->status_pre_transacao = '1';
        $transacao->pago = true;
        $transacao->save();

        $verifica_pedido_nao_pago = StoneTransacoesPedido::where('pedido_id',$pedido->id)
        ->where('pago','<>',true)
        ->first();

        if(!empty($verifica_pedido_nao_pago)){
            $verifica_pedido_nao_pago->delete();
        }
        
        $pedido->status_pedido = 3;
        $pedido->save();

        $verifica_pedido_bloqueado = PedidoBloqueadoPagamentoNasajon::where('id_docfis',$pedido->pedidoNasajon->id)->exists();
    
        if($verifica_pedido_bloqueado == true){
            $sql_api_validacao = "select * from integracoes.desbloquear_pedido('".$pedido->pedidoNasajon->id."')";

            try{
                $insert_nasajon = DB::connection('nasajon')->select($sql_api_validacao);
            }catch(\Exception $e){
                $erro_transacao = new StoneErroTransacoesPedido;
                $erro_transacao->erro_msg = 'Pedido '.$atualizar_transacao->id.' erro ao liberar pedido manual - '.(string)$e->getMessage();
                $erro_transacao->pedido_id = $atualizar_transacao->pedido->id;
                $erro_transacao->stone_cadastro_maquininha_id = $atualizar_transacao->maquininha->id;
                $erro_transacao->created_by = 1;
                $erro_transacao->save();

                $EmailObj = new EmailController();
                $email_send = [];
                $variaveis = [
                    'erro' => 'Pedido '.$atualizar_transacao->pedido->id.' erro ao consultar transação - '.(string)$e->getMessage()
                ];
                $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
            }
        }

        if(isset($pedido->pagamentosStone[0]->orders[0]) && !empty($pedido->pagamentosStone[0]->orders[0])){
            $virifica_transacao_finalizada = false;

            foreach($pedido->pagamentosStone[0]->orders as $transacao_avulsa){
                foreach($pedido->pagamentosStone as $orders){
                    foreach($orders->orders as $retorno_order){
                        $cancelado = true;
                        if(!empty($retorno_order->retornoTransacoes[0])){
                            $order = $retorno_order->order_id;
                            $transacao_controller = new StoneTransacaoController();
                            $transacao_controller->finalizarTransacao($pedido,$order,'canceled');
                        }else{
                            foreach($retorno_order->retornoTransacoes as $transacao_order){
                                if($transacao_order->data_status == 'paid'){
                                    $cancelado = false;
                                }
                            }

                            if($cancelado === false){
                                $order = $retorno_order->order_id;
                                $transacao_controller = new StoneTransacaoController();
                                $transacao_controller->finalizarTransacao($pedido,$order,'paid');
                            }else{
                                $order = $retorno_order->order_id;
                                $transacao_controller = new StoneTransacaoController();
                                $transacao_controller->finalizarTransacao($pedido,$order,'canceled');
                            }
                        }
                    }

                }
            }

        }
        
        return  response()->json([
            'status' => 'success',
            'message' => 'Pagamento(s) registrado(s) com sucesso.',
            'error' => [],
            'response' => []
        ], 200);
    }

    public function modalEditarPagamentoRestante(Request $request){
        $campos = $request->only(['pedido_id']);
        $id = (int)$campos['pedido_id'];
        $retorno = [];
        $transacao_paga = [];
        $total = 0;

        try{
            $transacao = StoneTransacoesPedido::with(['pagamentoRestante.pagamentosParciais','retornoTransacaoAvulsa','pagamentosParciais'])->where('id',$id)->get();
        }catch(Exception $e){
            return  response()->json([
                'status' => 'error',
                'message' => 'Transação não encontrada.',
                'error' => [$e],
                'response' => []
            ], 422);
        }

        $transacao->each(function($query) use (&$retorno,&$total,&$transacao_paga){
            $forma_pagamento_pago = 'Várias Formas';

            if($query->payment_type == 1){
                $forma_pagamento_pago = 'Débito';
            }else if($query->payment_type == 2){
                $forma_pagamento_pago = 'Crédito';
            }

            $valor_pago = 0;

            if(!empty($query->pagamentosParciais->sum('valor')) && $query->pagamentosParciais->sum('valor') > 0){
                $valor_pago += $query->pagamentosParciais->sum('valor');
            }

            if(!empty($query->retornoTransacaoAvulsa->sum('data_paid_amount'))){
                $valor_pago += (float)($query->retornoTransacaoAvulsa->sum('data_paid_amount') / 100);
            }

            $transacao_paga['forma_pagamento_pago'] = $forma_pagamento_pago;
            $transacao_paga['bandeiras_pago'] = $query->card_brand;
            $transacao_paga['codigo_autorizacao_pago'] = $query->transaction_authorization_code;
            $transacao_paga['parcelamento_pago'] = $query->installments_number;
            $transacao_paga['data_autorizacao_pago'] = (!empty($query->data_transacao)) ? parserData($query->data_transacao) : 'Várias Formas';
            $transacao_paga['valor_pago'] = parserValor($valor_pago);
            $transacao_paga['stine_id_pago'] = $query->stone_transaction_id;

            foreach($query->pagamentoRestante->pagamentosParciais as $pagamento){

                $retorno[] = [
                    'forma_pagamento' => (empty($pagamento->forma_pagamento)) ? 'desconto' : $pagamento->forma_pagamento,
                    'parcelamento' => $pagamento->parcelamento,
                    'autorizacao' => $pagamento->codigo_autoriazacao,
                    'data_autorizacao' => parserData($pagamento->data_autorizacao),
                    'documento_cartao' => $pagamento->documento_cartao,
                    'bandeira' => $pagamento->bandeira,
                    'valor'=> parserValor($pagamento->valor),
                    'id_pagamento_nasajon' => encrypt($pagamento->id_pagamento_nasajon),
                    'id' => encrypt($pagamento->id)
                ];

                $total += $pagamento->valor;
            }
        });

        $contador = count($retorno);
        $total = parserValor($total);
        
        $id_bandeiras = $this->bandeiras_nasajon;
        $id_formas_pagamentos = $this->forma_pagamento_descricao;
        $bandeiras = [];
        $forma_pagamento = [];
        $parcelamentos = [];
        $maquininha = [];

        $bandeiras_nasajon = CartoesBandeirasNasajon::whereIn('bandeiracartao',$id_bandeiras)->select('bandeiracartao','codigo')->get();
        $bandeiras_nasajon->each(function($query) use (&$bandeiras){
            $bandeiras[$query->bandeiracartao] = $query->codigo;
        });

        $forma_pagamento_nasajon = FormaPagamentoNasajon::whereIn('formapagamento',$id_formas_pagamentos)->orderBy('descricao')->get();
        $forma_pagamento_nasajon->each(function($query) use (&$forma_pagamento){
            $forma_pagamento[$query->formapagamento] = $query->descricao;
        });

        $parcelamento_nasajon = ParcelamentoNasajon::where('nome','ilike','%CARTAO%')->orderBy('nome')->get();
        $parcelamento_nasajon->each(function($query) use (&$parcelamentos){
            $parcelamentos[$query->parcelamento] = $query->nome;
        });

        $maquininhas_stone = StoneCadastroMaquininha::get();
        $maquininhas_stone->each(function($query) use (&$maquininha){
            $maquininha[$query->id] = $query->descricao;
        });

        return view('programs.stone_pagamentos.modal.editar_pagamento_restante')->with([
            'pagamentos' => $retorno,
            'contador' => $contador,
            'total' => $total,
            'bandeiras' => $bandeiras,
            'pedido_id' => $campos['pedido_id'],
            'forma_pagamento' => $forma_pagamento,
            'parcelamentos' => $parcelamentos,
            'maquininhas' => $maquininha,
            'transacao_paga' => $transacao_paga
        ]);
    }

    
    public function editarPagamentoRestante(Request $request){
        set_time_limit(900);
        ini_set('memory_limit','2048M');

        $campos = $request->only(['pedido_id','bandeiras','codigo_autorizacao','forma_pagamento','parcelamento','data_autorizacao','valor','tid','id_pagamento_nasajon','id']);
        
        $pedido = PedidoPortal::with(['pagamentosStone' => function($query) use ($campos){
            $query->where('id',$campos['pedido_id'])
            ->where('pagamento_restante',true)
            ->whereNull('pagamento_parcial');
        },'pedidoNasajon','estabelecimentoDetalhes'])
        ->whereHas('pagamentosStone', function($query) use ($campos){
            $query->where('id',$campos['pedido_id'])
            ->where('pagamento_restante',true)
            ->whereNull('pagamento_parcial');
        })
        ->first();
        return;
        $verificar_valor = 0;
        $valor_pago = $pedido->pagamentosStone[0]->transaction_amount;

        foreach($campos['valor'] as $campo_valor){
            $verificar_valor += parserNumber($campo_valor);
        }
        
        if(empty($pedido)){
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido não encontrado.',
                'error' => [],
                'response' => []
            ],422);
        }

        $valor_separado = 0;

        if($pedido->cod_cliente == '0000010069999'){// verifica cliente balcão
            if(!empty($pedido->pedidoNasajon->valorTotalSeparacao->separacao)){
                $valor_separado = $pedido->pedidoNasajon->valorTotalSeparacao->separacao;
            }
        }else if(isset($pedido->pedidoNasajon->notaEmAberto->valor)){
            $valor_separado = floatVal($pedido->pedidoNasajon->notaEmAberto->valor);
        }else if(isset($pedido->pedidoNasajon->nota->valor)){
            $valor_separado = floatVal($pedido->pedidoNasajon->nota->valor);
        }

        $valor_pedido = parserValor($pedido->valor_total_produtos);
        
        if(parserValor($verificar_valor) !=  parserValor($valor_separado) && $valor_pedido != parserValor($verificar_valor) || $valor_separado <= 0){
            return response()->json([
                'status' => 'error',
                'message' => 'O valor do(s) pagamento(s) inserido(s) é diferente do separado ou do pedido.',
                'error' => [],
                'response' => []
            ],422);
        }

        foreach($campos['forma_pagamento'] as $key_campos => $campos_array){
            $valor = parserNumber($campos['valor'][$key_campos]);
            
            if($valor <= 0){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Valor Inválido.',
                    'error' => [],
                    'response' => []
                ],422);
            }

            try{
                $id_pagamento_parcial = decrypt($campos['id'][$key_campos]);
            }catch(Exception $e){
                return  response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao receber ID do pagamento parcial, contate o setor responsável.',
                    'error' => [$e],
                    'response' => []
                ], 422);
            }

            if($campos['forma_pagamento'][$key_campos] != 'desconto'){
                $forma_pagamento_nasajon = FormaPagamentoNasajon::where('formapagamento',$campos['forma_pagamento'][$key_campos])->first();

                if(!isset($forma_pagamento_nasajon->tipo)){
                    return  response()->json([
                        'status' => 'error',
                        'message' => 'Erro ao integrar com Nasajon, forma de pagamento não encontrada.',
                        'error' => [],
                        'response' => []
                    ], 422);
                }
            }

            $data_autorizacao = Carbon::createFromFormat('d/m/Y', $campos['data_autorizacao'][$key_campos])->format('Y-m-d');
                
            if($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' && $campos['forma_pagamento'][$key_campos] != 'desconto'){
                $parcelamento_nasajon = ParcelamentoNasajon::where('parcelamento',$campos['parcelamento'][$key_campos])->first();
                $tipo_pagamento = '';

                if($parcelamento_nasajon->parcelamento == '99e8ff10-c34a-4391-a735-d5d78a41d737' || $campos['forma_pagamento'][$key_campos] == 'b0444787-b579-422d-af2a-ce691cbff825' || $campos['forma_pagamento'][$key_campos] == 'f6661441-835e-41a5-88a5-9db2224aad4d'){
                    $tipo_pagamento = 1;
                }else if($parcelamento_nasajon->parcelamento == '2931da42-bb7b-44d5-acb7-01e26d10ca4c' || $parcelamento_nasajon->parcelamento == 'a755fefa-8e42-4d63-84bd-9644bfc134f5'){
                    $tipo_pagamento = 2;
                }else{
                    $tipo_pagamento = 3;
                }

                $id_operadora = CartoesOperadorasNasajon::whereIn('operadoracartao',$this->operacao_cartao_nasajon)->first();
                $id_bandeira = CartoesBandeirasNasajon::where('bandeiracartao',$campos['bandeiras'][$key_campos])->first();
                $id_meio_eletronico = CartoesMeiosEletronicosNasajon::whereIn('codigo',$this->meios_eletronicos)->first();

                $contrato_cartao_nasajon = CartoesContratosNasajon::where('estabelecimento',$pedido->estabelecimentoDetalhes->estabelecimento)
                ->where('bandeiracartao',$id_bandeira->bandeiracartao)
                ->where('tipooperacao',$tipo_pagamento)
                ->where('meioeletronicocartao',$id_meio_eletronico->meioeletronicocartao)
                ->first();

                $parcelamento = '';

                if(!isset($contrato_cartao_nasajon->contratocartao)){
                    return  response()->json([
                        'status' => 'error',
                        'message' => 'Contrato de cartão inválido.',
                        'error' => [],
                        'response' => []
                    ], 422);
                }
                
                if($campos['forma_pagamento'][$key_campos] == 'b0444787-b579-422d-af2a-ce691cbff825'){
                    $parcelamento = "99e8ff10-c34a-4391-a735-d5d78a41d737";
                }else{
                    $parcelamento = $campos['parcelamento'][$key_campos];
                }
            }

            if($campos['forma_pagamento'][$key_campos] == 'b0444787-b579-422d-af2a-ce691cbff825'){
                $parcelamento = '99e8ff10-c34a-4391-a735-d5d78a41d737';
            }else if($campos['forma_pagamento'][$key_campos] == '04f167be-2b90-427d-ba3d-eb712c0e938b'){
                $parcelamento = '04f167be-2b90-427d-ba3d-eb712c0e938b';
            }else if($campos['forma_pagamento'][$key_campos] == 'f6661441-835e-41a5-88a5-9db2224aad4d'){
                $parcelamento = 'f6661441-835e-41a5-88a5-9db2224aad4d';
            }

            if($campos['forma_pagamento'][$key_campos] == '04f167be-2b90-427d-ba3d-eb712c0e938b' || $campos['forma_pagamento'][$key_campos] == 'f6661441-835e-41a5-88a5-9db2224aad4d'){
                $tipo_pagamento = 1;
            }

            $pagamentos_parciais = StonePagamentosParciai::find($id_pagamento_parcial);
            $pagamentos_parciais->forma_pagamento = ($campos['forma_pagamento'][$key_campos] != 'desconto') ? $forma_pagamento_nasajon->formapagamento : null;
            $pagamentos_parciais->parcelamento = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $parcelamento : null;
            $pagamentos_parciais->valor = parserNumber($campos['valor'][$key_campos]);
            $pagamentos_parciais->codigo_autoriazacao = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $campos['codigo_autorizacao'][$key_campos] : null;
            $pagamentos_parciais->data_autorizacao = $data_autorizacao;
            $pagamentos_parciais->documento_cartao = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $campos['tid'][$key_campos] : null;
            $pagamentos_parciais->contrato_cartao = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $contrato_cartao_nasajon->contratocartao : null;
            $pagamentos_parciais->cnpj_operadora = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $this->cnpj_operadora : null;
            $pagamentos_parciais->meio_eletronico = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $id_meio_eletronico->meioeletronicocartao : null;
            $pagamentos_parciais->operadora =($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $id_operadora->operadoracartao : null;
            $pagamentos_parciais->bandeira = ($campos['forma_pagamento'][$key_campos] != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $campos['forma_pagamento'][$key_campos] != 'f6661441-835e-41a5-88a5-9db2224aad4d' &&  $campos['forma_pagamento'][$key_campos] != 'desconto') ? $id_bandeira->bandeiracartao : null;
            $pagamentos_parciais->tipo_operacao = ($campos['forma_pagamento'][$key_campos] != 'desconto') ? (int)$tipo_pagamento : null;
            $pagamentos_parciais->updated_by = Auth::user()->id;
            $pagamentos_parciais->updated_at = Carbon::now();
            $pagamentos_parciais->save();
        }

        $pagamento_restante = StoneTransacoesPagamentosRestante::where('stone_transacoes_pedido_id',(int)$campos['pedido_id'])->first();
        $pagamento_restante->api_nasajon = null;
        $pagamento_restante->save();

        if($valor_separado < $verificar_valor && $pedido->cod_cliente !== '0000010069999'){
            $credito =  $verificar_valor - $valor_separado;
            
            $observacao_credito = "Título de Crédito Gerado Automático pelo Sistema, pela diferença de separação do pedido: ".$pedido->pedidoNasajon->numero." no estabelecimento: ".$pedido->pedidoNasajon->estabelecimento_codigo;
            $estabelecimento_uuid_nasajon = $pedido->pedidoNasajon->estabelecimento;
            $cliente_uuid_nasajon = $pedido->pedidoNasajon->cliente;
            $data_emissao = Carbon::parse($pedido->pedidoNasajon->emissao);
            $data_emissao_baixa = Carbon::now();
            $data_vencimento = Carbon::now()->addYear();
            $numero_titulo = $pedido->id;
            $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;

            $contasNasajonObj = ContasNasajon::select()->where('codigo','ilike', '000')->first();
            $conta_uuid_nasajon = $contasNasajonObj->conta;

            $pedidoFormaPagamentoNasajonObj = PedidoFormaPagamentoNasajon::select()->where('formapagamento_descricao', 'ilike', 'Usar Crédito')->first();
            $forma_pagamento_uuid_nasajon = $pedidoFormaPagamentoNasajonObj->formapagamento;

            $sql_titulo_credito = "select * from integracoes.api_titulorecebermovo(
                uuid_generate_v4(), /* id */
                '{$estabelecimento_uuid_nasajon}', /* estabelecimento */
                '{$cliente_uuid_nasajon}', /* cliente */
                '{$credito}', /* valor */
                '{$data_emissao->format('Y-m-d')}', /* emissao */
                '{$data_vencimento->format('Y-m-d')}', /* vencimento */
                '{$numero_titulo}.1CRD', /* numero */
                '{$forma_pagamento_uuid_nasajon}', /* forma pagamento */
                '{$conta_uuid_nasajon}', /* conta */
                NULL, /* layout */
                NULL, /* data_multa */
                0.0, /* percentual multa */
                0.0, /* juros diarios */
                '{$usuario_cadastro_uuid}', /* usuario */
                '{$observacao_credito}', /* observacao */
                true /* tipo título crédito */
            );";

            try{
                $titulo_credito = DB::connection('nasajon')->select($sql_titulo_credito);
                $mensagem_nasajon = $titulo_credito[0]->mensagem;
                $mensagem_nasajon = json_decode($mensagem_nasajon, true);  
                $titulo_credito_uuid_nasajon = $mensagem_nasajon['mensagem'];
            }catch(\Exception $e){
                $erro_transacao = new StoneErroTransacoesPedido;
                $erro_transacao->erro_msg = 'Pedido '.$pedido->id.' erro ao gerar crédito manual restante - '.(string)$e->getMessage();
                $erro_transacao->pedido_id = $pedido->id;
                $erro_transacao->stone_cadastro_maquininha_id = $pedido->id;
                $erro_transacao->created_by = 1;
                $erro_transacao->save();

                $EmailObj = new EmailController();
                $email_send = [];
                $variaveis = [
                    'erro' => 'Pedido '.$pedido->id.' erro ao gerar crédito manual restante - '.(string)$e->getMessage()
                ];
                $returnEmail = $EmailObj->sendEmailToken('00', "erro_verifica_transacao_estone", $email_send, $variaveis);
            }

            $this->baixaTituloCredito($titulo_credito_uuid_nasajon,$conta_uuid_nasajon,$data_emissao_baixa,$credito);

            $atualizar_transacao = StoneTransacoesPagamentosRestante::findOrFail($campos['pedido_id']);
            $atualizar_transacao->credito = true;
            $atualizar_transacao->save();
        }

        $pedido = PedidoPortal::find($pedido->id);
        $pedido->status_pedido = 3;
        $pedido->save();

        return  response()->json([
            'status' => 'success',
            'message' => 'Pagamento(s) alterado(s) com sucesso.',
            'error' => [],
            'response' => []
        ], 200);
    }

    public function baixaTituloCredito($titulo_credito_uuid_nasajon, $conta_uuid_nasajon, $data_baixa, $valor){
        $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;
        $observacao_baixa_credito = "Baixa Total de Título de Crédito pelo Portal, usuário: Sistema data: ".date('Y-m-d H:i:s').".";
        $quitartitulo = 'true';

        $sql_baixa_titulo = "select * from integracoes.api_baixartituloreceber(
            '".$titulo_credito_uuid_nasajon."',
            '".$conta_uuid_nasajon."',
            '".$data_baixa->format('Y-m-d')."',
            ".$valor.",
            0.0,
            0.0,
            0.0, 
            0.0,
            0.0,
            0.0,
            '".$observacao_baixa_credito."',
            '".$usuario_cadastro_uuid."',
            ".$quitartitulo."
        );";

        try{
            $baixar_nasajon = DB::connection('nasajon')->select($sql_baixa_titulo);
        }catch(\Exception $e){
            return  response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$e],
                'response' => []
            ], 422);
        }
    }

}
