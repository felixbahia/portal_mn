<?php

namespace App\Http\Controllers;

use App\PedidosPrePago;
use App\PedidoPrePagoLancamento;
use App\FaturamentoNotaNasajon;
use App\ClienteNasajon;
use App\GrupoEmpresarial;
use App\PedidosVendaNasajon;
use App\PedidoPortal;

use App\Cheque;
use App\ChequesPedidosPrepagos;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Http\Requests\PedidoPrePagoLancamentoRequest;
use Carbon\Carbon;

use Auth;

class PedidosPrePagoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\TitulosPrePago") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\TitulosPrePago');

        return view('programs.titulos_prepago.index');
    }

    public function filter(Request $request){

        $fields = $request->only('cliente', 'data_inicio', 'data_fim', 'abertos_encerrados', 'cpf_cnpjs');

        $pedidoPrePagoQuery = PedidosPrePago::with([
                'pedidoNasajon' => function($query){
                     $query->where('grupodeoperacao', 'VENDA');
                },
                'pedidoNasajon.faturamento',
                'pedidoNasajon.cliente_detalhes',
                'pedidoNasajon.nota',
                'pedido',
                'pedido.condicao_pagamento_detalhes.parcelas',
                'lancamentos',
                'lancamentos.cheque'
            ]);

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $pedidoPrePagoQuery->where('created_at', '>=', $data_inicio->format('Y-m-d'));
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $pedidoPrePagoQuery->where('created_at', '<=',$data_fim->format('Y-m-d'));
        }

        if(isset($fields['cpf_cnpjs']) && !empty($fields['cpf_cnpjs'])){
            $pedidoPrePagoQuery->whereHas('pedido', function($query) use ($fields){

                $clientesObj = ClienteNasajon::whereIn('cpf_cnpj', $fields['cpf_cnpjs'])->get();

                $query->whereIn('cod_cliente',$clientesObj->pluck('codigo'));

            });
        }
        if(isset($fields['cliente']) && !empty($fields['cliente'])){
            $pedidosNasajon = PedidosVendaNasajon::select('id')
                ->whereHas('cliente_detalhes', function ($query) use ($fields){
                    $query->where(DB::Raw("CONCAT(TRIM(nome), ' - ', cpf_cnpj)"), 'ilike', "%".addslashes($fields["cliente"]). "%");
                })
                ->get();

            $pedidoPrePagoQuery->whereIn('pedido_nasajon_id', $pedidosNasajon->pluck('id'));
        }

        $pedidoPrePagoObj = $pedidoPrePagoQuery->get();

        $data = Carbon::now();

        if(isset($fields['abertos_encerrados']) && $fields['abertos_encerrados'] == 'abertos'){

            $pedidoPrePagoObj = $pedidoPrePagoObj->filter(function($pedido) use($data){
                $total_lancamentos = $pedido->lancamentos->sum(function($lancamento) use ($data){
                        return $lancamento->valor_pago;
                    });
                return parserFloat10($pedido->valor) > parserFloat10($total_lancamentos);
            });
        }

        if(isset($fields['abertos_encerrados']) && $fields['abertos_encerrados'] == 'encerrados'){

            $pedidoPrePagoObj = $pedidoPrePagoObj->filter(function($pedido) use($data){
                $total_lancamentos = $pedido->lancamentos->sum(function($lancamento) use ($data){
                        return $lancamento->valor_pago;
                    });
                return parserFloat10($pedido->valor) <= parserFloat10($total_lancamentos);
            });
        }
        
        $pedidoPrePagoObj = $pedidoPrePagoObj->filter( function ($value){
            return !is_null($value->pedidoNasajon) && !is_null($value->pedidoNasajon->nota);
        });

        $linhas = [];

        $total_titulos = 0;
        $total_valor_pago = 0;
        $total_saldo = 0;

        $pedidoPrePagoObj->each(function($pedido) use (&$linhas, &$total_titulos, &$total_valor_pago, &$total_saldo, $data){

            $baixado = $pedido->lancamentos->sum("valor_pago");
 
            $saldo = $pedido->valor - $baixado;

            if(round($saldo, 2) > 0){
                $total_saldo += $saldo;
                $saldo = parserValor($saldo);
            }            
            else{
                $saldo = '';
            }

            if($baixado > 0){
                $valor_pago = parserValor($baixado);
                $total_valor_pago += $baixado;
            }
            else{
                $valor_pago = '';
            }

            $total_titulos += $pedido->valor;

            $linhas[] = [
                'pedido_nasajon'=> $pedido->pedidoNasajon->numero,
                'cliente' => $pedido->pedidoNasajon->cliente_detalhes->nome,
                'data_pedido_nasajon'=> parserData($pedido->pedidoNasajon->emissao),
                'nota_fiscal' => $pedido->pedidoNasajon->nota['numero']??'',
                'data_nota_fiscal' => isset($pedido->pedidoNasajon->nota['emissao'])? parserData($pedido->pedidoNasajon->nota['emissao']): '',
                'valor_titulo' => parserValor($pedido->valor),
                'valor_baixado' => $valor_pago,
                'saldo' => $saldo,

                'nome' => $pedido->pedidoNasajon->cliente_detalhes->nome,

                'id_pedido' => $pedido->pedidoNasajon->id,
                'id_nota' => $pedido->pedidoNasajon->nota['id'],
                'id' => $pedido->id   
            ];

        });

        if($total_titulos > 0){
            $titulos_total = parserValor($total_titulos);
        }
        else{
            $titulos_total = '';
        }

        if($total_valor_pago > 0){
            $valor_pago = parserValor($total_valor_pago);
        }
        else{
            $valor_pago = '';
        }

        if($total_saldo > 0){
            $saldo = parserValor($total_saldo);
        }
        else{
            $saldo = '';
        }

        $totais = [
            'titulos' => $titulos_total,
            'valor_pago' => $valor_pago,
            'saldo' => $saldo
        ];

        $retorno = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'data' => $linhas,
                'totalizadores' => $totais
            ]
        ];

        return response()->json($retorno, 200);
    }

    public function modal(Request $request){

        $fields = $request->only('id');

        $prePagoObj = PedidosPrePago::with('lancamentos', 'lancamentos.cheque', 'pedidoNasajon')->find($fields['id']);
        
        $lancamentos = $prePagoObj->lancamentos->filter(function($item){
            return !empty($item->cheque);
        });

        $valor_pago = $prePagoObj->lancamentos->sum("valor_pago");

        $valor_faturado = parserValor($valor_pago);
        $total = parserValor($prePagoObj->valor);

        return view('programs.titulos_prepago.modal.index')->with(['itens' => $lancamentos, 'id' => $fields['id'], 'total' => $total, 'valor_faturado' => $valor_faturado]);  

    }

    public function salvaLancamento(PedidoPrePagoLancamentoRequest $request){
        
        $fields = $request->only('id', 'banco', 'agencia', 'conta', 'numero_cheque', 'valor');
        
        $pedidoPrepagoLancamento = new PedidoPrePagoLancamento;

        
        $pedidoPrepagoLancamento->pedido_prepago_id = $fields['id'];
        $pedidoPrepagoLancamento->banco = $fields['banco'];
        $pedidoPrepagoLancamento->agencia = $fields['agencia'];
        $pedidoPrepagoLancamento->conta = $fields['conta'];
        $pedidoPrepagoLancamento->numero_cheque = $fields['numero_cheque'];
        $pedidoPrepagoLancamento->valor = str_replace(',', '', str_replace(',','.', $fields['valor']));
        $pedidoPrepagoLancamento->created_by = Auth::user()->id;
        
        $pedidoPrepagoLancamento->save();
        
        $pedidoPrePagoObj = PedidosPrePago::with('lancamentos')->find($fields['id']);

        $retorno = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'id' => $pedidoPrepagoLancamento->id,
                'valor' => parserValor($pedidoPrepagoLancamento->valor),
                'total' => parserValor($pedidoPrePagoObj->lancamentos->sum('valor'))
            ]
        ];

        return response()->json($retorno, 200);
    }

    public function apagarLancamento(Request $request){

        $fields = $request->only('id');

        $pedidoPrepagoLancamento = PedidoPrePagoLancamento::find($fields['id']);

        $pedidoPrepagoLancamento->delete();

        $retorno = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => ''
        ];

        return response()->json($retorno, 200);

    }

    public function modal_filtro(Request $request){

        $fields = $request->only('unico', 'codigo');

        $clienteNasajonObj = ClienteNasajon::where('codigo', $fields['codigo'])->first();

        if(isset($fields['unico']) && $fields['unico'] == 'true'){
            $clientes_codigo = [$clienteNasajonObj->codigo]; 
        }
        else{
            $clientesQuery = ClienteNasajon::query();

            $cpf_cnpj = substr($clienteNasajonObj->cpf_cnpj, 0, 10);
            
            $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
            ->where('raiz_cnpj', 'like', $cpf_cnpj . '%')
            ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                $query->where('raiz_cnpj', 'like', $cpf_cnpj . '%');
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
            
            $clientes = $clientesQuery->get();
            $clientes_codigo = $clientes->pluck('codigo');

        }
        $pedidoPrePagoObj = PedidosPrePago::
            with(['pedidoNasajon' => function($query){
                $query->where('grupodeoperacao', 'VENDA')
                    ->where('rascunho', false);
            }, 'pedidoNasajon.nota', 'pedidoNasajon.cliente_detalhes', 'lancamentos'])
            ->whereRaw('valor - valor_pago > 0')
            ->where(function($query) use ($clientes_codigo){

                $pedidosNasajon = PedidosVendaNasajon::select('id')
                ->whereHas('cliente_detalhes', function ($q) use ($clientes_codigo){
                    $q->whereIn("codigo", $clientes_codigo);
                })
                ->where('grupodeoperacao', 'VENDA')
                ->where('rascunho', false)
                ->get();

                $query->whereIn('pedido_nasajon_id', $pedidosNasajon->pluck('id'));
                
            })
            ->get();

        $linhas = [];
        $total_pago = 0;
        $total_titulo = 0;
        $total_saldo = 0;
        $pedidoPrePagoObj->each(function($pedido) use (&$linhas, &$total_pago, &$total_titulo, &$total_saldo){

            $baixado = $pedido->valor_pago;
            $saldo = $pedido->valor - $baixado;

            $total_pago += $baixado;
            $total_titulo += $pedido->valor;
            $total_saldo += $saldo;

            if($pedido->valor > 0){
                $valor_total = parserValor($pedido->valor);
            }
            else{
                $valor_total = '';
            }


            if($baixado > 0){
                $baixado = parserValor($baixado);
            }
            else{
                $baixado = '';
            }

            if ($saldo > 0){
                $saldo = parserValor($saldo); 
            }
            else{
                $saldo = '';
            }

            if(!empty($pedido->pedidoNasajon->nota['emissao'])){
                $emissao_nota = parserData($pedido->pedidoNasajon->nota['emissao']);
            }
            else{
                $emissao_nota = '';
            }
            $linhas[] = [
                'pedido_nasajon'=> $pedido->pedidoNasajon->numero,
                'data_pedido_nasajon'=> parserData($pedido->pedidoNasajon->emissao),
                'nota_fiscal' => $pedido->pedidoNasajon->nota['numero'],
                'data_nota_fiscal' => $emissao_nota,
                'valor_titulo' => $valor_total,
                'valor_baixado' => $baixado,
                'saldo' => $saldo,

                'id_pedido' => $pedido->pedidoNasajon->id,
                'id_nota' => $pedido->pedidoNasajon->nota['id'],
                'cliente' => $pedido->pedidoNasajon->cliente_detalhes->nome
            ];
        });

        if($total_pago > 0){
            $total_pago = parserValor($total_pago);
        }
        else{
            $total_pago = '';
        }

        if($total_titulo > 0){
            $total_titulo = parserValor($total_titulo);
        }
        else{
            $total_titulo = '';
        }

        if($total_saldo > 0){
            $total_saldo = parserValor($total_saldo);
        }
        else{
            $total_saldo = '';
        }

        $totais = [
            'total_titulo' => $total_titulo,
            'total_pago' => $total_pago,
            'total_saldo' => $total_saldo
        ];

        return view('programs.titulos_prepago.modal_filtro')->with(['linhas' => $linhas, 'totais' => $totais]);
    }

    public function detalhesModal(Request $request){
        $fields = $request->only('id');

        $pedidoPrePagoObj = PedidosPrePago::with([
                'pedidoNasajon' => function($query){ 
                    $query->where('grupodeoperacao', 'VENDA');
                },
                'pedidoNasajon.cliente_detalhes'
            ])
            ->find($fields['id']);

        $retorno = [
            'pedido_nasajon'=> $pedidoPrePagoObj->pedidoNasajon->numero,
            'cliente' => $pedidoPrePagoObj->pedidoNasajon->cliente_detalhes->nome,
            'data_pedido_nasajon'=> parserData($pedidoPrePagoObj->pedidoNasajon->emissao),
            'nota_fiscal' => $pedidoPrePagoObj->pedidoNasajon->nota['numero']??'',
            'data_nota_fiscal' => isset($pedidoPrePagoObj->pedidoNasajon->nota['emissao'])? parserData($pedidoPrePagoObj->pedidoNasajon->nota['emissao']): '',
            'valor_titulo' => parserValor($pedidoPrePagoObj->valor),
            'valor_baixado' => $pedidoPrePagoObj->valor_pago > 0 ? parserValor($pedidoPrePagoObj->valor_pago) : '',
            'saldo' => $pedidoPrePagoObj->valor - $pedidoPrePagoObj->valor_pago > 0 ? parserValor($pedidoPrePagoObj->valor - $pedidoPrePagoObj->valor_pago) : '',

            'nome' => $pedidoPrePagoObj->pedidoNasajon->cliente_detalhes->nome,

            'id_pedido' => $pedidoPrePagoObj->pedidoNasajon->id,
            'id_nota' => $pedidoPrePagoObj->pedidoNasajon->nota['id'],
            'id' => $pedidoPrePagoObj->id   
        ];

        return view('programs.titulos_prepago.modal.detalhes')->with(['retorno' => $retorno]);

    }

    public function cancelarPedidosPrePagosNotasCancelados(){

        $pedidosPrePagosCanceladosObj = PedidosPrePago::with(
                'lancamentos',
                'lancamentos.cheque',
                'pedidoNasajon',
                'pedidoNasajon.notaCancelada',
                'pedidoNasajon.cliente_detalhes'
            )
            ->whereDoesntHave('lancamentos.cheque', function($query){
                $query->where('tipo', 'cancelamento');
            })
            ->get()
            ->filter(function ($pedido){
                return isset($pedido->pedidoNasajon->notaCancelada);
            });

        $pedidosPrePagosCanceladosObj->each(function($pedido){
            $pedido->lancamentos->each(function($lancamento){
                $lancamento->cheque->saldo = floatval($lancamento->cheque->saldo) + $lancamento->valor_pago;
                $lancamento->cheque->status = 'aberto';
                $lancamento->cheque->updated_by = 1;
                $lancamento->cheque->save();

                ChequesPedidosPrepagos::
                    where('cheque_id',$lancamento->cheque_id)
                    ->where('pedido_prepago_id',$lancamento->pedido_prepago_id)
                    ->delete();
            });
            
            $cancelamento = new Cheque;
            $cancelamento->valor =  $pedido->valor;
            $cancelamento->banco = null;
            $cancelamento->agencia = null;
            $cancelamento->conta = null;
            $cancelamento->numero_cheque = null;
            $cancelamento->bom_para = null;
            $cancelamento->cliente_cpf_cnpj = $pedido->pedidoNasajon->cliente_detalhes->cpf_cnpj;
            $cancelamento->created_by = 1;
            $cancelamento->status = 'baixado';
            $cancelamento->tipo = 'cancelamento';

            $pedido->valor_pago = $cancelamento->valor;

            $cancelamento->save();
            $pedido->save();

            $chequePedido = new ChequesPedidosPrepagos;

            $chequePedido->cheque_id = $cancelamento->id;
            $chequePedido->pedido_prepago_id = $pedido->id;
            $chequePedido->valor_pago = $pedido->valor_pago;
            $chequePedido->save();
        });
    }

    public function atualizaValoresNota(){

        $agora = Carbon::now();
        echo 'Atualização de valores do pedido pré-pago:' . PHP_EOL;

        $prePagosObj = PedidosPrePago::with(['pedidoNasajon.nota']
            )
            ->where('atualizado', false)->get();

        $contador = 0;

        $prePagosObj->each(function ($pedido) use(&$contador){

            if(isset($pedido->pedidoNasajon->nota->valor) && $pedido->pedidoNasajon->nota->valor > 0 && $pedido->pedidoNasajon->situacao_descricao == 'Faturado'){
                $pedido->valor = $pedido->pedidoNasajon->nota->valor;
                $pedido->updated_by = 1;
                $pedido->atualizado = true;
                $pedido->save();

                $contador++;
            }
        });

        echo "Pedidos pré-pagos atualizados: " . $contador . PHP_EOL . 'Demorou ' . $agora->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;

    }

    public function modalPrepagosAbertosRepresentante(Request $request){

        $fields = $request->only('user_id');

        $pedidosPrePagosAbertos = PedidosPrePago::
            with('pedidoNasajon', 'pedidoNasajon.nota', 'pedidoNasajon.cliente_detalhes')
            ->whereHas('pedido', function($query) use ($fields){
                $query->where('usuario', $fields['user_id']);
            })
            ->whereColumn('valor', '>', 'valor_pago')
            ->get();
        
        $pedidosPrePagosAbertos = $pedidosPrePagosAbertos->filter(function($pedido){
            return isset($pedido->pedidoNasajon->nota);
        });
        
        $linhas = [];

        $pedidosPrePagosAbertos->each(function($pedido) use (&$linhas){

            if(!empty($pedido->pedidoNasajon->nota['emissao'])){
                $emissao_nota = parserData($pedido->pedidoNasajon->nota['emissao']);
            }
            else{
                $emissao_nota = '';
            }
            $linhas[] = [
                'pedido_nasajon'=> $pedido->pedidoNasajon->numero,
                'data_pedido_nasajon'=> parserData($pedido->pedidoNasajon->emissao),
                'nota_fiscal' => $pedido->pedidoNasajon->nota['numero'],
                'data_nota_fiscal' => $emissao_nota,
                'valor_titulo' => parserValor($pedido->valor),
                'valor_baixado' => parserValor($pedido->valor_pago),
                'saldo' => parserValor($pedido->valor - $pedido->valor_pago),

                'id_pedido' => $pedido->pedidoNasajon->id,
                'id_nota' => $pedido->pedidoNasajon->nota['id'],
                'cliente' => $pedido->pedidoNasajon->cliente_detalhes->nome
            ];

        });

        $totais = [
            'total_titulo' => parserValor($pedidosPrePagosAbertos->sum('valor')),
            'total_pago' => parserValor($pedidosPrePagosAbertos->sum('valor_pago')),
            'total_saldo' => parserValor($pedidosPrePagosAbertos->sum(function ($pedido){
                        return $pedido->valor - $pedido->valor_pago;
                    }
                )
            )
        ];
        
        return view('programs.titulos_prepago.modal_filtro')->with(['linhas' => $linhas, 'totais' => $totais]);
    }

    public function cancelarPrepagos(){
        $PedidosPrePagoObj = PedidosPrePago::with(['pedidoNasajon'=>function($query){
            $query->where('situacao_descricao', 'ilike', 'Cancelado');
        }])->get();
        $pedidos = [];
        $PedidosPrePagoObj->each(function($pedido) use(&$pedidos){
            if(!empty($pedido->pedidoNasajon)){
                if($pedido->valor_pago == 0){
                    $pedido->deleted_by = 1;
                    $pedido->save();
                    $pedido->delete();
                }
            }
        });
    }

    public function criaTitulosPrePagos(){

        $inicio = Carbon::now();
        
        $pedidosPrePagosSemTitulo = PedidoPortal::
            with(['pedidoNasajon' => function($query){
                $query->where('grupodeoperacao', 'VENDA')
                ->orWhere('grupodeoperacao', NULL);
            }])
            ->where('tipo_venda', 'ilike', 'pre_pago%')
            ->where('status_pedido', 3)
            ->whereNotNull('pedido_gerado')
            ->whereDoesntHave('pedidoPrePago')
            ->get();

        $pedidosPrePagosSemTitulo = $pedidosPrePagosSemTitulo->filter(function($pedido) {
            return !empty($pedido->pedidoNasajon) && $pedido->pedidoNasajon->situacao_descricao != 'Cancelado';
        });

        $pedidosPrePagosSemTitulo->each(function($pedido){

            $pedidosPrePagoObj = new PedidosPrePago;

            $pedidosPrePagoObj->pedido_id = $pedido->id;
            $pedidosPrePagoObj->pedido_nasajon_id = $pedido->pedidoNasajon->id;
            $pedidosPrePagoObj->pedido_nasajon_numero = $pedido->pedidoNasajon->numero;
            $pedidosPrePagoObj->valor = $pedido->pedidoNasajon->valor;
            $pedidosPrePagoObj->valor_pago = 0;
            $pedidosPrePagoObj->created_by = 1;
            $pedidosPrePagoObj->atualizado = false;

            $pedidosPrePagoObj->save();
        });

        echo 'Demorou ' . $inicio->diffForHumans() . ' para gerar ' . $pedidosPrePagosSemTitulo->count() . ' títulos pré-pagos.' . PHP_EOL;
    }
}
