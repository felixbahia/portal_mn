<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\Importacao;
use App\Red;
use App\ImportacaoValorPadrao;

class PendenciaPagamentoProcessoImportacaoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\PendenciaPagamentoProcessoImportacao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\PendenciaPagamentoProcessoImportacao');

        $data_atual = Carbon::now()->setTime(0,0,0)->firstOfMonth();
        $datas = [];
        $datas[0] = parserNameMonth($data_atual->format('m')).'/'.$data_atual->format('Y');

        for($i = 1; $i < 12; $i++){
            $data_atual->addMonth();
            $datas[$i] = parserNameMonth($data_atual->format('m')).'/'.$data_atual->format('Y');
        }

        $data_atual = Carbon::now()->setTime(0,0,0)->firstOfMonth();
        $datas_dados = [];
        $datas_dados[0] = $data_atual->format('Y-m-d');
        for($i = 1; $i < 12; $i++){
            $data_atual->addMonth();
            $datas_dados[$i] = $data_atual->format('Y-m-d');
        }

        return view('programs.pendencia_pagamento_processo_importacao.index')->with(['datas' => $datas, 'datas_dados' => $datas_dados]);
    }

    public function filtro(Request $request){
        $fields = $request->only('fornecedor_filtro');
        
        $importacaoObj = Importacao::select();
        if(!empty($fields['fornecedor_filtro'])){
            $importacaoObj->with(['fornecedor' => function($query) use($fields){
                $query->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor_filtro']));
            }]);
        }
        $importacaoObj->whereHas('financeiro', function($query){
            $query->whereHas('lancamentos', function($query){
                $query->where('previsto', true);
                $query->whereNull('importacao_financeiro_lancamentos_id_baixa');
            });
        });
        $importacaoObj->with(['financeiro.lancamentos' => function($query){
            $query->whereNull('importacao_financeiro_lancamentos_id_baixa');
            $query->where('previsto', true);
            $query->with(['detalhesRedsUtilizado']);
        }]);
        $importacaoObj = $importacaoObj->get();

        $importacaoValorPadraoObj = ImportacaoValorPadrao::select()->first();

        $proformas = [
            'red' => [
                'descricao' => "Hedge(US$)",
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
            ],
            'antecipacao' => [
                'descricao' => "Antecipação à Pagar(Sinal)(US$)",
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
            ],
            'normal' => [
                'descricao' => "Fornecedor DI(US$)",
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
            ],
            'carta_x' => [
                'descricao' => "Fornecedor Carta X(US$)",
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
            ],
            'imposto' => [
                'descricao' => "Imposto/Taxa(R$)",
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
            ],
            'total_geral_real' => [
                'descricao' => "Total Geral(R$)",
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
            ],
            'total_geral_dolar' => [
                'descricao' => "Total Geral(US$)",
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
            ],
            'total_geral_convertido' => [
                'descricao' => 'Total Geral Convertido(R$)<a href="#" class="btn-informacao" data-toggle="tooltip" data-placement="top" title="" data-original-title="Cotação do Dolar: '.parserValor4CasasDecimais($importacaoValorPadraoObj->dolar_referencia).'" style="color: black;"></a>',
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
            ],
        ];

        $data_hoje = Carbon::now()->setTime(0,0,0)->firstOfMonth();

        foreach($importacaoObj as $importacao){
            foreach($importacao->financeiro->lancamentos as $lancamento){
                if($lancamento->modalidade === "imposto" || $lancamento->modalidade === "carta_x"){
                    $modalidade = $lancamento->modalidade;                  
                }else if($lancamento->modalidade === "antecipado"){
                    $modalidade = "antecipacao";  
                }else{
                    $modalidade = "normal";  
                }
                
                $data_verificacao = empty($lancamento->cambio_data)? '' : $lancamento->cambio_data->setTime(0,0,0)->firstOfMonth();
                if(empty($data_verificacao)){
                    $index = "inteiro_1";
                }else if($data_hoje->gte($data_verificacao)){
                    $index = "inteiro_1";
                }else{
                    $diferenca = $data_hoje->diffInMonths($data_verificacao) + 1;
                    $index = "inteiro_".$diferenca;
                }

                if($lancamento->modalidade === "imposto"){
                    $valor = $lancamento->real_valor;
                    $proformas["total_geral_real"][$index] += $valor;
                    $proformas["total_geral_real"]["inteiro_total"] += $valor;
                }else{
                    $valor = $lancamento->cambio_valor;
                    $proformas["total_geral_dolar"][$index] += $valor;
                    $proformas["total_geral_convertido"][$index] += $valor * $importacaoValorPadraoObj->dolar_referencia;
                    $proformas["total_geral_dolar"]["inteiro_total"] += $valor;
                    $proformas["total_geral_convertido"]["inteiro_total"] += $valor * $importacaoValorPadraoObj->dolar_referencia;
                }

                $proformas[$modalidade][$index] += $valor;
                $proformas[$modalidade]["inteiro_total"] += $valor;
            }
        }

        $RedObj = Red::select();
        $RedObj->where('saldo', '>', 0);
        $RedObj = $RedObj->get();

        foreach($RedObj as $red){
            $data_verificacao = empty($red->vencimento)? '' : $red->vencimento->setTime(0,0,0)->firstOfMonth();
            if(empty($data_verificacao)){
                $index = "inteiro_1";
            }else if($data_hoje->gte($data_verificacao)){
                $index = "inteiro_1";
            }else{
                $diferenca = $data_hoje->diffInMonths($data_verificacao) + 1;
                $index = "inteiro_".$diferenca;
            }

            $proformas['red'][$index] += $red->saldo;
            $proformas['red']["inteiro_total"] += $valor;
        }

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'proformas' => $this->ajusteArrayParaValores($proformas),
            ],
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
                                //$array[$key] = empty($value)? '': parserValor($value);
                                if($key == 'diferenca_meta'){
                                    $array[$key] = empty($value)? '': parserValor($value);
                                }else{
                                    $array[$key] = $value > 0.01 ?  parserValor($value) : '';
                                }
                            }else{
                                $array[$key] = empty($value)? '': parserValor($value).'%';
                            }                            
                        }
                    }else{
                        $array[$key] = $value;
                    }
                }
            }
        }
        return $array;
    }

    public function modalDetalhes(Request $request){
        $fields = $request->only(['data', 'modalidade']);
        if($fields['modalidade'] == 'red'){
            $data_atual = Carbon::now()->setTime(0,0,0)->firstOfMonth();
            $data_escolhida_inicial = Carbon::parse($fields['data'])->setTime(0,0,0)->firstOfMonth();
            $data_escolhida_final = Carbon::parse($fields['data'])->setTime(23,59,59)->lastOfMonth();

            $RedObj = Red::select();
            $RedObj->where('saldo', '>', 0);
            if($data_atual->gte($data_escolhida_inicial)){
                $RedObj->where('vencimento', "<=", $data_escolhida_final);
            }else{
                $RedObj->whereBetween('vencimento', [$data_escolhida_inicial, $data_escolhida_final]);
            }
            $RedObj = $RedObj->get();

            $reds = [];
            $total = [
                "valor" => 0,
                "saldo" => 0,
            ];
            foreach($RedObj as $red){
                $reds[] = [
                    "id" => encrypt($red->id),
                    "numero_documento" => $red->numero_documento,
                    "valor" => parserValor($red->valor),
                    "taxa_cambio" => parserValor4CasasDecimais($red->taxa_cambio),
                    "vencimento" => parserData($red->vencimento),
                    "saldo" => parserValor($red->saldo),
                ];

                $total["valor"] += $red->valor;
                $total["saldo"] += $red->saldo;
            }

            $total["valor"] = parserValor($total["valor"]);
            $total["saldo"] = parserValor($total["saldo"]);

            return view('programs.pendencia_pagamento_processo_importacao.modal.detalhes_red')->with(['reds' => $reds, 'total' => $total]);
        }else{
            $data_atual = Carbon::now()->setTime(0,0,0)->firstOfMonth();
            $data_escolhida_inicial = Carbon::parse($fields['data'])->setTime(0,0,0)->firstOfMonth();
            $data_escolhida_final = Carbon::parse($fields['data'])->setTime(23,59,59)->lastOfMonth();

            if($fields['modalidade'] === "imposto" || $fields['modalidade'] === "carta_x"){
                $modalidade = $fields['modalidade'];                  
            }else if($fields['modalidade'] === "antecipacao"){
                $modalidade = "antecipado";  
            }else{
                $modalidade = "normal";  
            }

            $importacaoObj = Importacao::select();
            $importacaoObj->whereHas('financeiro', function($query) use($fields, $data_atual, $data_escolhida_inicial, $data_escolhida_final, $modalidade){
                $query->whereHas('lancamentos', function($query) use($fields, $data_atual, $data_escolhida_inicial, $data_escolhida_final, $modalidade){
                    $query->where('previsto', true);
                    if(!in_array($modalidade, ['imposto', 'carta_x', 'antecipado'])){
                        $query->whereNotIn('modalidade', ['imposto', 'carta_x', 'antecipado']);
                    }else{
                        $query->where('modalidade', $modalidade);
                    }                    
                    $query->whereNull('importacao_financeiro_lancamentos_id_baixa');
                    if($data_atual->gte($data_escolhida_inicial)){
                        $query->where(function ($query) use($fields, $data_atual, $data_escolhida_inicial, $data_escolhida_final, $modalidade){
                            $query->where('cambio_data', "<=", $data_escolhida_final);
                            $query->orWhereNull('cambio_data');
                        });
                    }else{
                        $query->whereBetween('cambio_data', [$data_escolhida_inicial, $data_escolhida_final]);
                    }
                });
            });
            $importacaoObj->with(['fornecedor', 'financeiro.lancamentos' => function($query) use($fields, $data_atual, $data_escolhida_inicial, $data_escolhida_final, $modalidade){
                $query->whereNull('importacao_financeiro_lancamentos_id_baixa');
                $query->where('previsto', true);
                if(!in_array($modalidade, ['imposto', 'carta_x', 'antecipado'])){
                    $query->whereNotIn('modalidade', ['imposto', 'carta_x', 'antecipado']);
                }else{
                    $query->where('modalidade', $modalidade);
                }    
                if($data_atual->gte($data_escolhida_inicial)){
                    $query->where(function ($query) use($fields, $data_atual, $data_escolhida_inicial, $data_escolhida_final, $modalidade){
                        $query->where('cambio_data', "<=", $data_escolhida_final);
                        $query->orWhereNull('cambio_data');
                    });
                }else{
                    $query->whereBetween('cambio_data', [$data_escolhida_inicial, $data_escolhida_final]);
                }
            }]);

            $importacaoObj = $importacaoObj->get();
            $proformas = [];
            $total = [
                "valor" => 0,
            ];
            foreach($importacaoObj as $importacao){
                foreach($importacao->financeiro->lancamentos as $lancamento){                    
                    if($lancamento->modalidade === "imposto"){
                        $valor = $lancamento->real_valor;
                    }else{
                        $valor = $lancamento->cambio_valor;
                    }
                    
                    if($valor > 1){
                        if(empty($proformas[$importacao->id])){
                            $proformas[$importacao->id] = [
                                'id' => encrypt($importacao->id),
                                'proforma' => $importacao->numero_proforma,
                                'valor' => 0,
                                'pcmn_codigo' => $importacao->pedido_compras,
                                'fornecedor' => $importacao->fornecedor->nome,
                                'titulo' => "PCMN: ".$importacao->pedido_compras." Proforma: ".$importacao->numero_proforma." Fornecedor: ".$importacao->fornecedor->nome,
                            ];
                        }
                        $proformas[$importacao->id]['valor'] += $valor;
    
                        $total["valor"] += $valor;
                    }
                    
                }
            }
        }
        
        $total["valor"] = parserValor($total["valor"]);
        return view('programs.pendencia_pagamento_processo_importacao.modal.detalhes')->with(['proformas' => $this->ajusteArrayParaValores($proformas), 'total' => $total]);
    }
}
