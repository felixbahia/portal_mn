<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

use App\ComprasPrevistoFluxoCaixa;
use App\TituloAPagarInformacaoMes;
use App\OrcamentoCompra;

class ComprasPrevistoFluxoCaixaController extends Controller
{
    public function geracaoDados(){
        $data_atual = Carbon::now()->setTime(0, 0, 0);
        $proximo_ano = $data_atual->year + 1;
        $anterior_ano = $data_atual->year - 1;

        $tituloInformacaoObj = TituloAPagarInformacaoMes::select(DB::raw('diferenca_mes, sum(quantidade)as quantidade, sum(valor) as valor'));
        $tituloInformacaoObj->orWhere(function($query) use($data_atual){
            $query->where('ano_emissao', $data_atual->year);
            $query->where('mes_emissao', '<', $data_atual->month);
        });
        $tituloInformacaoObj->orWhere(function($query) use($data_atual){
            $query->where('ano_emissao', $data_atual->year - 1);
            $query->where('mes_emissao', '>=', $data_atual->month);
        });
        $tituloInformacaoObj->groupBy('diferenca_mes');
        $tituloInformacaoObj = $tituloInformacaoObj->get();

        $total_quantidade = 0;
        $total_valor = 0;
        
        foreach($tituloInformacaoObj as $value){
            $total_quantidade += $value->quantidade;
            $total_valor += $value->valor;
        }

        $porcetagem_30 = [];
        $porcetagem_total_30 = 0;
        $porcetagem_60 = [];
        $porcetagem_total_60 = 0;
        $porcetagem_90 = [];
        $porcetagem_total_90 = 0;
        $porcetagem_120 = [];
        $porcetagem_total_120 = 0;
        $porcetagem_150 = [];
        $porcetagem_total_150 = 0;
        $porcetagem_180 = [];
        $porcetagem_total_180 = 0;
        $porcetagem_210 = [];
        $porcetagem_total_210 = 0;
        $porcetagem_240 = [];
        $porcetagem_total_240 = 0;
        $porcetagem_270 = [];
        $porcetagem_total_270 = 0;
        $porcetagem_300 = [];
        $porcetagem_total_300 = 0;
        $porcetagem_330 = [];
        $porcetagem_total_330 = 0;
        $porcetagem_360 = [];
        $porcetagem_total_360 = 0;
        foreach($tituloInformacaoObj as $value){
            if($value->diferenca_mes == 0){
                $porcetagem_30[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                $porcetagem_total_30 += $porcetagem_30[$value->diferenca_mes];
            }else if($value->diferenca_mes == 1){
                $porcetagem_60[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                $porcetagem_total_60 += $porcetagem_60[$value->diferenca_mes];
            }else if($value->diferenca_mes == 2){
                $porcetagem_90[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                $porcetagem_total_90 += $porcetagem_90[$value->diferenca_mes];
            }else if($value->diferenca_mes == 3){
                $porcetagem_120[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                $porcetagem_total_120 += $porcetagem_120[$value->diferenca_mes];
            }else if($value->diferenca_mes == 4){
                $porcetagem_150[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                $porcetagem_total_150 += $porcetagem_150[$value->diferenca_mes];
            }else if($value->diferenca_mes == 5){
                $porcetagem_180[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                $porcetagem_total_180 += $porcetagem_180[$value->diferenca_mes];
            }else if($value->diferenca_mes == 6){
                $porcetagem_210[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                $porcetagem_total_210 += $porcetagem_210[$value->diferenca_mes];
            }else if($value->diferenca_mes == 7){
                $porcetagem_240[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                $porcetagem_total_240 += $porcetagem_240[$value->diferenca_mes];
            }else if($value->diferenca_mes == 8){
                $porcetagem_270[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                $porcetagem_total_270 += $porcetagem_270[$value->diferenca_mes];
            }else if($value->diferenca_mes == 9){
                $porcetagem_300[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                $porcetagem_total_300 += $porcetagem_300[$value->diferenca_mes];
            }else if($value->diferenca_mes == 10){
                $porcetagem_330[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                $porcetagem_total_330 += $porcetagem_330[$value->diferenca_mes];
            }else if($value->diferenca_mes == 11){
                $porcetagem_360[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                $porcetagem_total_360 += $porcetagem_360[$value->diferenca_mes];
            }
        }

        $data_atual = $data_atual->firstOfMonth();

        $query_faturamento_previsto = OrcamentoCompra::select();
        $query_faturamento_previsto->where('data', $data_atual);
        $result_faturamento_previsto = $query_faturamento_previsto->first();

        $total_previsto = ($result_faturamento_previsto->nacional_valor + $result_faturamento_previsto->importado_valor);

        $faturamentoPrevistoFluxoCaixaobj = ComprasPrevistoFluxoCaixa::select()->where('mes_ano', $data_atual)->first();
        if(empty($faturamentoPrevistoFluxoCaixaobj)){
            $faturamentoPrevistoFluxoCaixaobj = new ComprasPrevistoFluxoCaixa;
        }
        $faturamentoPrevistoFluxoCaixaobj->mes_ano = $data_atual;
        $faturamentoPrevistoFluxoCaixaobj->valor = $total_previsto;
        $faturamentoPrevistoFluxoCaixaobj->compras_previsto = $total_previsto;
        $faturamentoPrevistoFluxoCaixaobj->mes_atual_porcetagem = $porcetagem_total_30;
        $faturamentoPrevistoFluxoCaixaobj->mes_atual_valor = ($total_previsto * ($porcetagem_total_30 / 100));
        $faturamentoPrevistoFluxoCaixaobj->mes_1_porcetagem = $porcetagem_total_60;
        $faturamentoPrevistoFluxoCaixaobj->mes_1_valor = ($total_previsto * ($porcetagem_total_60 / 100));
        $faturamentoPrevistoFluxoCaixaobj->mes_2_porcetagem = $porcetagem_total_90;
        $faturamentoPrevistoFluxoCaixaobj->mes_2_valor = ($total_previsto * ($porcetagem_total_90 / 100));
        $faturamentoPrevistoFluxoCaixaobj->mes_3_porcetagem = $porcetagem_total_120;
        $faturamentoPrevistoFluxoCaixaobj->mes_3_valor = ($total_previsto * ($porcetagem_total_120 / 100));
        $faturamentoPrevistoFluxoCaixaobj->mes_4_porcetagem = $porcetagem_total_150;
        $faturamentoPrevistoFluxoCaixaobj->mes_4_valor = ($total_previsto * ($porcetagem_total_150 / 100));
        $faturamentoPrevistoFluxoCaixaobj->mes_5_porcetagem = $porcetagem_total_180;
        $faturamentoPrevistoFluxoCaixaobj->mes_5_valor = ($total_previsto * ($porcetagem_total_180 / 100));
        $faturamentoPrevistoFluxoCaixaobj->mes_6_porcetagem = $porcetagem_total_210;
        $faturamentoPrevistoFluxoCaixaobj->mes_6_valor = ($total_previsto * ($porcetagem_total_210 / 100));
        $faturamentoPrevistoFluxoCaixaobj->mes_7_porcetagem = $porcetagem_total_240;
        $faturamentoPrevistoFluxoCaixaobj->mes_7_valor = ($total_previsto * ($porcetagem_total_240 / 100));
        $faturamentoPrevistoFluxoCaixaobj->mes_8_porcetagem = $porcetagem_total_270;
        $faturamentoPrevistoFluxoCaixaobj->mes_8_valor = ($total_previsto * ($porcetagem_total_270 / 100));
        $faturamentoPrevistoFluxoCaixaobj->mes_9_porcetagem = $porcetagem_total_300;
        $faturamentoPrevistoFluxoCaixaobj->mes_9_valor = ($total_previsto * ($porcetagem_total_300 / 100));
        $faturamentoPrevistoFluxoCaixaobj->mes_10_porcetagem = $porcetagem_total_330;
        $faturamentoPrevistoFluxoCaixaobj->mes_10_valor = ($total_previsto * ($porcetagem_total_330 / 100));
        $faturamentoPrevistoFluxoCaixaobj->mes_11_porcetagem = $porcetagem_total_360;
        $faturamentoPrevistoFluxoCaixaobj->mes_11_valor = ($total_previsto * ($porcetagem_total_360 / 100));
        $faturamentoPrevistoFluxoCaixaobj->created_by = 1;
        $faturamentoPrevistoFluxoCaixaobj->save();

        while($data_atual->year != $proximo_ano){
            $query_faturamento_previsto = OrcamentoCompra::select();
            $query_faturamento_previsto->where('data', $data_atual);
            $result_faturamento_previsto = $query_faturamento_previsto->first();
    
            $total_previsto = ($result_faturamento_previsto->nacional_valor + $result_faturamento_previsto->importado_valor);
    
            $faturamentoPrevistoFluxoCaixaobj = ComprasPrevistoFluxoCaixa::select()->where('mes_ano', $data_atual)->first();
            if(empty($faturamentoPrevistoFluxoCaixaobj)){
                $faturamentoPrevistoFluxoCaixaobj = new ComprasPrevistoFluxoCaixa;
            }
            $faturamentoPrevistoFluxoCaixaobj->mes_ano = $data_atual;
            $faturamentoPrevistoFluxoCaixaobj->valor = $total_previsto;
            $faturamentoPrevistoFluxoCaixaobj->compras_previsto = $total_previsto;
            $faturamentoPrevistoFluxoCaixaobj->mes_atual_porcetagem = $porcetagem_total_30;
            $faturamentoPrevistoFluxoCaixaobj->mes_atual_valor = ($total_previsto * ($porcetagem_total_30 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_1_porcetagem = $porcetagem_total_60;
            $faturamentoPrevistoFluxoCaixaobj->mes_1_valor = ($total_previsto * ($porcetagem_total_60 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_2_porcetagem = $porcetagem_total_90;
            $faturamentoPrevistoFluxoCaixaobj->mes_2_valor = ($total_previsto * ($porcetagem_total_90 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_3_porcetagem = $porcetagem_total_120;
            $faturamentoPrevistoFluxoCaixaobj->mes_3_valor = ($total_previsto * ($porcetagem_total_120 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_4_porcetagem = $porcetagem_total_150;
            $faturamentoPrevistoFluxoCaixaobj->mes_4_valor = ($total_previsto * ($porcetagem_total_150 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_5_porcetagem = $porcetagem_total_180;
            $faturamentoPrevistoFluxoCaixaobj->mes_5_valor = ($total_previsto * ($porcetagem_total_180 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_6_porcetagem = $porcetagem_total_210;
            $faturamentoPrevistoFluxoCaixaobj->mes_6_valor = ($total_previsto * ($porcetagem_total_210 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_7_porcetagem = $porcetagem_total_240;
            $faturamentoPrevistoFluxoCaixaobj->mes_7_valor = ($total_previsto * ($porcetagem_total_240 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_8_porcetagem = $porcetagem_total_270;
            $faturamentoPrevistoFluxoCaixaobj->mes_8_valor = ($total_previsto * ($porcetagem_total_270 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_9_porcetagem = $porcetagem_total_300;
            $faturamentoPrevistoFluxoCaixaobj->mes_9_valor = ($total_previsto * ($porcetagem_total_300 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_10_porcetagem = $porcetagem_total_330;
            $faturamentoPrevistoFluxoCaixaobj->mes_10_valor = ($total_previsto * ($porcetagem_total_330 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_11_porcetagem = $porcetagem_total_360;
            $faturamentoPrevistoFluxoCaixaobj->mes_11_valor = ($total_previsto * ($porcetagem_total_360 / 100));
            $faturamentoPrevistoFluxoCaixaobj->created_by = 1;
            $faturamentoPrevistoFluxoCaixaobj->save();

            $data_atual = $data_atual->addMonth();
        }
    }

    public function geracaoDadosAnoAnterior(){
        $data_atual = Carbon::now()->setTime(0, 0, 0);
        $data_atual = $data_atual->firstOfMonth();
        $data_atual = $data_atual->subMonth();


        while($data_atual->year != 2019){
            $tituloInformacaoObj = TituloAPagarInformacaoMes::select(DB::raw('diferenca_mes, sum(quantidade)as quantidade, sum(valor) as valor'));
            $tituloInformacaoObj->orWhere(function($query) use($data_atual){
                $query->where('ano_emissao', $data_atual->year);
                $query->where('mes_emissao', '<', $data_atual->month);
            });
            $tituloInformacaoObj->orWhere(function($query) use($data_atual){
                $query->where('ano_emissao', $data_atual->year - 1);
                $query->where('mes_emissao', '>=', $data_atual->month);
            });
            $tituloInformacaoObj->groupBy('diferenca_mes');
            $tituloInformacaoObj = $tituloInformacaoObj->get();
    
            $total_quantidade = 0;
            $total_valor = 0;
            
            foreach($tituloInformacaoObj as $value){
                $total_quantidade += $value->quantidade;
                $total_valor += $value->valor;
            }

            $porcetagem_30 = [];
            $porcetagem_total_30 = 0;
            $porcetagem_60 = [];
            $porcetagem_total_60 = 0;
            $porcetagem_90 = [];
            $porcetagem_total_90 = 0;
            $porcetagem_120 = [];
            $porcetagem_total_120 = 0;
            $porcetagem_150 = [];
            $porcetagem_total_150 = 0;
            $porcetagem_180 = [];
            $porcetagem_total_180 = 0;
            $porcetagem_210 = [];
            $porcetagem_total_210 = 0;
            $porcetagem_240 = [];
            $porcetagem_total_240 = 0;
            $porcetagem_270 = [];
            $porcetagem_total_270 = 0;
            $porcetagem_300 = [];
            $porcetagem_total_300 = 0;
            $porcetagem_330 = [];
            $porcetagem_total_330 = 0;
            $porcetagem_360 = [];
            $porcetagem_total_360 = 0;
            foreach($tituloInformacaoObj as $value){
                if($value->diferenca_mes == 0){
                    $porcetagem_30[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                    $porcetagem_total_30 += $porcetagem_30[$value->diferenca_mes];
                }else if($value->diferenca_mes == 1){
                    $porcetagem_60[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                    $porcetagem_total_60 += $porcetagem_60[$value->diferenca_mes];
                }else if($value->diferenca_mes == 2){
                    $porcetagem_90[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                    $porcetagem_total_90 += $porcetagem_90[$value->diferenca_mes];
                }else if($value->diferenca_mes == 3){
                    $porcetagem_120[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                    $porcetagem_total_120 += $porcetagem_120[$value->diferenca_mes];
                }else if($value->diferenca_mes == 4){
                    $porcetagem_150[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                    $porcetagem_total_150 += $porcetagem_150[$value->diferenca_mes];
                }else if($value->diferenca_mes == 5){
                    $porcetagem_180[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                    $porcetagem_total_180 += $porcetagem_180[$value->diferenca_mes];
                }else if($value->diferenca_mes == 6){
                    $porcetagem_210[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                    $porcetagem_total_210 += $porcetagem_210[$value->diferenca_mes];
                }else if($value->diferenca_mes == 7){
                    $porcetagem_240[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                    $porcetagem_total_240 += $porcetagem_240[$value->diferenca_mes];
                }else if($value->diferenca_mes == 8){
                    $porcetagem_270[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                    $porcetagem_total_270 += $porcetagem_270[$value->diferenca_mes];
                }else if($value->diferenca_mes == 9){
                    $porcetagem_300[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                    $porcetagem_total_300 += $porcetagem_300[$value->diferenca_mes];
                }else if($value->diferenca_mes == 10){
                    $porcetagem_330[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                    $porcetagem_total_330 += $porcetagem_330[$value->diferenca_mes];
                }else if($value->diferenca_mes == 11){
                    $porcetagem_360[$value->diferenca_mes] = $value->valor / $total_valor * 100;
                    $porcetagem_total_360 += $porcetagem_360[$value->diferenca_mes];
                }
            }

            $query_faturamento_previsto = OrcamentoCompra::select();
            $query_faturamento_previsto->where('data', $data_atual);
            $result_faturamento_previsto = $query_faturamento_previsto->first();

            $total_previsto = ($result_faturamento_previsto->nacional_valor + $result_faturamento_previsto->importado_valor);

            $faturamentoPrevistoFluxoCaixaobj = ComprasPrevistoFluxoCaixa::select()->where('mes_ano', $data_atual)->first();
            if(empty($faturamentoPrevistoFluxoCaixaobj)){
                $faturamentoPrevistoFluxoCaixaobj = new ComprasPrevistoFluxoCaixa;
            }
            $faturamentoPrevistoFluxoCaixaobj->mes_ano = $data_atual;
            $faturamentoPrevistoFluxoCaixaobj->valor = $total_previsto;
            $faturamentoPrevistoFluxoCaixaobj->compras_previsto = $total_previsto;
            $faturamentoPrevistoFluxoCaixaobj->mes_atual_porcetagem = $porcetagem_total_30;
            $faturamentoPrevistoFluxoCaixaobj->mes_atual_valor = ($total_previsto * ($porcetagem_total_30 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_1_porcetagem = $porcetagem_total_60;
            $faturamentoPrevistoFluxoCaixaobj->mes_1_valor = ($total_previsto * ($porcetagem_total_60 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_2_porcetagem = $porcetagem_total_90;
            $faturamentoPrevistoFluxoCaixaobj->mes_2_valor = ($total_previsto * ($porcetagem_total_90 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_3_porcetagem = $porcetagem_total_120;
            $faturamentoPrevistoFluxoCaixaobj->mes_3_valor = ($total_previsto * ($porcetagem_total_120 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_4_porcetagem = $porcetagem_total_150;
            $faturamentoPrevistoFluxoCaixaobj->mes_4_valor = ($total_previsto * ($porcetagem_total_150 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_5_porcetagem = $porcetagem_total_180;
            $faturamentoPrevistoFluxoCaixaobj->mes_5_valor = ($total_previsto * ($porcetagem_total_180 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_6_porcetagem = $porcetagem_total_210;
            $faturamentoPrevistoFluxoCaixaobj->mes_6_valor = ($total_previsto * ($porcetagem_total_210 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_7_porcetagem = $porcetagem_total_240;
            $faturamentoPrevistoFluxoCaixaobj->mes_7_valor = ($total_previsto * ($porcetagem_total_240 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_8_porcetagem = $porcetagem_total_270;
            $faturamentoPrevistoFluxoCaixaobj->mes_8_valor = ($total_previsto * ($porcetagem_total_270 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_9_porcetagem = $porcetagem_total_300;
            $faturamentoPrevistoFluxoCaixaobj->mes_9_valor = ($total_previsto * ($porcetagem_total_300 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_10_porcetagem = $porcetagem_total_330;
            $faturamentoPrevistoFluxoCaixaobj->mes_10_valor = ($total_previsto * ($porcetagem_total_330 / 100));
            $faturamentoPrevistoFluxoCaixaobj->mes_11_porcetagem = $porcetagem_total_360;
            $faturamentoPrevistoFluxoCaixaobj->mes_11_valor = ($total_previsto * ($porcetagem_total_360 / 100));
            $faturamentoPrevistoFluxoCaixaobj->created_by = 1;
            $faturamentoPrevistoFluxoCaixaobj->save();

            $data_atual = $data_atual->subMonth();
        }
    }

    public function somatoriaDados(){
        $faturamentoPrevistoFluxoCaixaObj = ComprasPrevistoFluxoCaixa::select()->get();

        $dados = [];
        foreach($faturamentoPrevistoFluxoCaixaObj as $value){
            $dados[$value->mes_ano->format('Y-m-d')] = [
                "valor" => $value->valor,
                "compras_previsto" => $value->compras_previsto,
                "mes_atual_porcetagem" => $value->mes_atual_porcetagem,
                "mes_atual_valor" => $value->mes_atual_valor,
                "mes_1_porcetagem" => $value->mes_1_porcetagem,
                "mes_1_valor" => $value->mes_1_valor,
                "mes_2_porcetagem" => $value->mes_2_porcetagem,
                "mes_2_valor" => $value->mes_2_valor,
                "mes_3_porcetagem" => $value->mes_3_porcetagem,
                "mes_3_valor" => $value->mes_3_valor,
                "mes_4_porcetagem" => $value->mes_4_porcetagem,
                "mes_4_valor" => $value->mes_4_valor,
                "mes_5_porcetagem" => $value->mes_5_porcetagem,
                "mes_5_valor" => $value->mes_5_valor,
                "mes_6_porcetagem" => $value->mes_6_porcetagem,
                "mes_6_valor" => $value->mes_6_valor,
                "mes_7_porcetagem" => $value->mes_7_porcetagem,
                "mes_7_valor" => $value->mes_7_valor,
                "mes_8_porcetagem" => $value->mes_8_porcetagem,
                "mes_8_valor" => $value->mes_8_valor,
                "mes_9_porcetagem" => $value->mes_9_porcetagem,
                "mes_9_valor" => $value->mes_9_valor,
                "mes_10_porcetagem" => $value->mes_10_porcetagem,
                "mes_10_valor" => $value->mes_10_valor,
                "mes_11_porcetagem" => $value->mes_11_porcetagem,
                "mes_11_valor" => $value->mes_11_valor,
            ];
        }

        foreach($faturamentoPrevistoFluxoCaixaObj as $value){
            $total = 0;
            $total =  $dados[$value->mes_ano->format('Y-m-d')]['mes_atual_valor'];
            $data = $value->mes_ano;
            for($i = 0; $i < 11; $i++){
                $data = $data->subMonth();
                if(!empty( $dados[$data->format('Y-m-d')])){
                    switch($i){
                        case 0:
                            $total += $dados[$data->format('Y-m-d')]['mes_atual_valor'];
                            break;
                        case 1:
                            $total += $dados[$data->format('Y-m-d')]['mes_1_valor'];
                            break;
                        case 2:
                            $total += $dados[$data->format('Y-m-d')]['mes_2_valor'];
                            break;
                        case 3:
                            $total += $dados[$data->format('Y-m-d')]['mes_3_valor'];
                            break;
                        case 4:
                            $total += $dados[$data->format('Y-m-d')]['mes_4_valor'];
                            break;
                        case 5:
                            $total += $dados[$data->format('Y-m-d')]['mes_5_valor'];
                            break;
                        case 6:
                            $total += $dados[$data->format('Y-m-d')]['mes_6_valor'];
                            break;
                        case 7:
                            $total += $dados[$data->format('Y-m-d')]['mes_7_valor'];
                            break;
                        case 8:
                            $total += $dados[$data->format('Y-m-d')]['mes_8_valor'];
                            break;
                        case 9:
                            $total += $dados[$data->format('Y-m-d')]['mes_9_valor'];
                            break;
                        case 10:
                            $total += $dados[$data->format('Y-m-d')]['mes_10_valor'];
                            break;
                        case 11:
                            $total += $dados[$data->format('Y-m-d')]['mes_11_valor'];
                            break;
                    }
                }
            }
            $value->valor = $total;
            $value->save();
        }
    }

    public function modalDetalhes(Request $request){
        $fields = $request->only('ano');

        $data = Carbon::parse($fields['ano']);
        $data_anterior = Carbon::parse($fields['ano'])->subYear()->addMonth();
        $data_demostracao = Carbon::parse($fields['ano']);

        $faturamentoPrevistoFluxoCaixaobj = ComprasPrevistoFluxoCaixa::select()
            ->whereBetween('mes_ano', [$data_anterior, $data])
            ->get();

        $dados = [];

        foreach($faturamentoPrevistoFluxoCaixaobj as $value){
            $dados[$value->mes_ano->format('Y-m-d')] = [
                "valor" => $value->valor,
                "compras_previsto" => $value->compras_previsto,
                "mes_atual_porcetagem" => $value->mes_atual_porcetagem,
                "mes_atual_valor" => $value->mes_atual_valor,
                "mes_1_porcetagem" => $value->mes_1_porcetagem,
                "mes_1_valor" => $value->mes_1_valor,
                "mes_2_porcetagem" => $value->mes_2_porcetagem,
                "mes_2_valor" => $value->mes_2_valor,
                "mes_3_porcetagem" => $value->mes_3_porcetagem,
                "mes_3_valor" => $value->mes_3_valor,
                "mes_4_porcetagem" => $value->mes_4_porcetagem,
                "mes_4_valor" => $value->mes_4_valor,
                "mes_5_porcetagem" => $value->mes_5_porcetagem,
                "mes_5_valor" => $value->mes_5_valor,
                "mes_6_porcetagem" => $value->mes_6_porcetagem,
                "mes_6_valor" => $value->mes_6_valor,
                "mes_7_porcetagem" => $value->mes_7_porcetagem,
                "mes_7_valor" => $value->mes_7_valor,
                "mes_8_porcetagem" => $value->mes_8_porcetagem,
                "mes_8_valor" => $value->mes_8_valor,
                "mes_9_porcetagem" => $value->mes_9_porcetagem,
                "mes_9_valor" => $value->mes_9_valor,
                "mes_10_porcetagem" => $value->mes_10_porcetagem,
                "mes_10_valor" => $value->mes_10_valor,
                "mes_11_porcetagem" => $value->mes_11_porcetagem,
                "mes_11_valor" => $value->mes_11_valor,
            ];
        }

        $i = 0;
        $retorno = [];
        $divisao_mes_atual = [];
        while(!empty($dados[$data->format('Y-m-d')])){
            $porcetagem = 0;
            $valor = 0;

            switch($i){
                case 0:
                    $divisao_mes_atual = $dados[$data->format('Y-m-d')];
                    $porcetagem = $dados[$data->format('Y-m-d')]['mes_atual_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['mes_atual_valor'];
                    break;
                case 1:
                    $porcetagem = $dados[$data->format('Y-m-d')]['mes_1_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['mes_1_valor'];
                    break;
                case 2:
                    $porcetagem = $dados[$data->format('Y-m-d')]['mes_2_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['mes_2_valor'];
                    break;
                case 3:
                    $porcetagem = $dados[$data->format('Y-m-d')]['mes_3_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['mes_3_valor'];
                    break;
                case 4:
                    $porcetagem = $dados[$data->format('Y-m-d')]['mes_4_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['mes_4_valor'];
                    break;
                case 5:
                    $porcetagem = $dados[$data->format('Y-m-d')]['mes_5_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['mes_5_valor'];
                    break;
                case 6:
                    $porcetagem = $dados[$data->format('Y-m-d')]['mes_6_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['mes_6_valor'];
                    break;
                case 7:
                    $porcetagem = $dados[$data->format('Y-m-d')]['mes_7_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['mes_7_valor'];
                    break;
                case 8:
                    $porcetagem = $dados[$data->format('Y-m-d')]['mes_8_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['mes_8_valor'];
                    break;
                case 9:
                    $porcetagem = $dados[$data->format('Y-m-d')]['mes_9_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['mes_9_valor'];
                    break;
                case 10:
                    $porcetagem = $dados[$data->format('Y-m-d')]['mes_10_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['mes_10_valor'];
                    break;
                case 11:
                    $porcetagem = $dados[$data->format('Y-m-d')]['mes_11_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['mes_11_valor'];
                    break;
            }

            $retorno[$data->format('m/Y')] = [
                'faturamento_venda' => parserValor($dados[$data->format('Y-m-d')]['compras_previsto']),
                'porcetagem_referente_mes_atual' => parserValor($porcetagem)."%",
                'valor_referente_mes_atual' => parserValor($valor),
            ];
            $data = $data->subMonth();
            $i++;
        }

        $divisao_retorno = [];
        $mes_atual = $divisao_mes_atual;

        $data_tabela = [];
        for($i = 0; $i < 12; $i++){
            switch($i){
                case 0:
                    $porcetagem = $divisao_mes_atual['mes_atual_porcetagem'];
                    $valor = $divisao_mes_atual['mes_atual_valor'];
                    break;
                case 1:
                    $porcetagem = $divisao_mes_atual['mes_1_porcetagem'];
                    $valor = $divisao_mes_atual['mes_1_valor'];
                    break;
                case 2:
                    $porcetagem = $divisao_mes_atual['mes_2_porcetagem'];
                    $valor = $divisao_mes_atual['mes_2_valor'];
                    break;
                case 3:
                    $porcetagem = $divisao_mes_atual['mes_3_porcetagem'];
                    $valor = $divisao_mes_atual['mes_3_valor'];
                    break;
                case 4:
                    $porcetagem = $divisao_mes_atual['mes_4_porcetagem'];
                    $valor = $divisao_mes_atual['mes_4_valor'];
                    break;
                case 5:
                    $porcetagem = $divisao_mes_atual['mes_5_porcetagem'];
                    $valor = $divisao_mes_atual['mes_5_valor'];
                    break;
                case 6:
                    $porcetagem = $divisao_mes_atual['mes_6_porcetagem'];
                    $valor = $divisao_mes_atual['mes_6_valor'];
                    break;
                case 7:
                    $porcetagem = $divisao_mes_atual['mes_7_porcetagem'];
                    $valor = $divisao_mes_atual['mes_7_valor'];
                    break;
                case 8:
                    $porcetagem = $divisao_mes_atual['mes_8_porcetagem'];
                    $valor = $divisao_mes_atual['mes_8_valor'];
                    break;
                case 9:
                    $porcetagem = $divisao_mes_atual['mes_9_porcetagem'];
                    $valor = $divisao_mes_atual['mes_9_valor'];
                    break;
                case 10:
                    $porcetagem = $divisao_mes_atual['mes_10_porcetagem'];
                    $valor = $divisao_mes_atual['mes_10_valor'];
                    break;
                case 11:
                    $porcetagem = $divisao_mes_atual['mes_11_porcetagem'];
                    $valor = $divisao_mes_atual['mes_11_valor'];
                    break;
            }
            $data_tabela[] = [
                'mes_ano' => $data_demostracao->format('m/Y'),
                'porcetagem' => parserValor($porcetagem)."%",
                'valor' =>parserValor($valor),
            ];
            $data_demostracao->addMonth();
        }

        return view('programs.compras_previsto_fluxo_caixa.modal.detalhes')->with(
            [
                'retorno' => $retorno, 
                'divisao_retorno' => $divisao_retorno,
                'data_tabela' => $data_tabela,
                'mes_atual' => $mes_atual,
            ]
        );
    }
}
