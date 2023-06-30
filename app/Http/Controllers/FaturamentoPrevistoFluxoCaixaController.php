<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

use App\FaturamentoPrevistoFluxoCaixa;
use App\TituloInformacaoMes;
use App\FaturamentoPrevisto;
use App\TitulosPagosNasajon;
use App\TitulosEmAbertoNasajon;
use App\FaturamentoOnline;
use App\PedidosPrePago;

class FaturamentoPrevistoFluxoCaixaController extends Controller
{
    public function geracaoDados(){
        $retorno = [];
        $data_atual = Carbon::now()->setTime(0, 0, 0);
        $data_mes_anterior = Carbon::now()->setTime(0, 0, 0)->subMonth();

        $primeiro_dia_do_mes= Carbon::now()->setTime(0,0,0)->subMonth()->firstOfMonth();
        $ultimo_dia_do_mes= Carbon::now()->setTime(23,59,59)->subMonth()->lastOfMonth();

        $cnpj = $this->cnpjINtercompany();
        $query_faturamento_caixa = TitulosEmAbertoNasajon::select('titulo_emissao', DB::Raw("sum(saldotitulo) as total"));
        $query_faturamento_caixa->whereBetween('vencimento', [$primeiro_dia_do_mes, $ultimo_dia_do_mes])
            ->where('saldotitulo', '>', '0')
            ->whereNotIn("codigo", ['25', 'TREINAMENTO'])
            ->whereNotIn("cod_cliente", $cnpj);
        $query_faturamento_caixa->groupBy('titulo_emissao');

        $query_faturamento_caixa_recebido = TitulosPagosNasajon::select('emissao', DB::Raw("sum(valor_titulo) as total"));
        $query_faturamento_caixa_recebido->whereBetween('vencimento', [$primeiro_dia_do_mes, $ultimo_dia_do_mes])
            ->where('valor_titulo', '>', '0')
                ->whereNotIn("codigo", ['25', 'TREINAMENTO'])
            ->whereNotIn("cod_cliente", $cnpj);
        $query_faturamento_caixa_recebido->groupBy('emissao');

        $result_faturamento_caixa = $query_faturamento_caixa->get();

        $result_faturamento_caixa_recebido = $query_faturamento_caixa_recebido->get();

        foreach($result_faturamento_caixa as $value){
            $data_titulo = Carbon::parse($value->titulo_emissao);
            $primeiro_dia = Carbon::parse($value->titulo_emissao)->setTime(0,0,0)->firstOfMonth();
            $ultimo_dia = Carbon::parse($value->titulo_emissao)->setTime(23,59,59)->lastOfMonth();
            
            if(empty($retorno[$data_titulo->format('m/Y')])){
                $retorno[$data_titulo->format('m/Y')] = [
                    'faturamento_venda' => 0,
                    'porcetagem_referente_mes_atual' => 0,
                    'valor_referente_mes_atual' => 0,
                    'venda_realizada' => 0,
                    'porcetagem_referente_mes_atual_realizada' => 0,
                    'valor_referente_mes_atual_realizada' => $value->total,
                    'diferenca' => 0,
                    'ordenacao' => $data_titulo->format('Y-m-d'),
                ];
            }else{
                $retorno[$data_titulo->format('m/Y')]['valor_referente_mes_atual_realizada'] += $value->total;
            }

            if(empty($retorno[$data_titulo->format('m/Y')]['venda_realizada'])){
                $query_faturamento_realizado = FaturamentoOnline::select(DB::Raw("SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) - SUM( valor_troco ) as total"));
                $query_faturamento_realizado->whereBetween('data', [$primeiro_dia, $ultimo_dia]);
                $query_faturamento_realizado->where(function ($query) {
                    $query->orWhere("tipo_operacao", "ilike", "VENDA%");
                    $query->orWhere("tipo_operacao", "ilike", "SIMPLESFATFUTURA");
                    $query->orWhere("tipo_operacao", "ilike", "DEV%");
                });
                $query_faturamento_realizado->where("nasajon", true);
                $result_faturamento_realizado = $query_faturamento_realizado->first();

                $retorno[$data_titulo->format('m/Y')]['venda_realizada'] = empty($result_faturamento_realizado->total)? 0 : $result_faturamento_realizado->total;
            }
        }

        foreach($result_faturamento_caixa_recebido as $value){
            $data_titulo = Carbon::parse($value->emissao);
            $primeiro_dia = Carbon::parse($value->emissao)->setTime(0,0,0)->firstOfMonth();
            $ultimo_dia = Carbon::parse($value->emissao)->setTime(23,59,59)->lastOfMonth();
          
            if(empty($retorno[$data_titulo->format('m/Y')])){
                $retorno[$data_titulo->format('m/Y')] = [
                    'faturamento_venda' => 0,
                    'porcetagem_referente_mes_atual' => 0,
                    'valor_referente_mes_atual' => 0,
                    'venda_realizada' => 0,
                    'porcetagem_referente_mes_atual_realizada' => 0,
                    'valor_referente_mes_atual_realizada' => $value->total,
                    'diferenca' => 0,
                    'ordenacao' => $data_titulo->format('Y-m-d'),
                ];
            }else{
                $retorno[$data_titulo->format('m/Y')]['valor_referente_mes_atual_realizada'] += $value->total;
            }

            if(empty($retorno[$data_titulo->format('m/Y')]['venda_realizada'])){
                $query_faturamento_realizado = FaturamentoOnline::select(DB::Raw("SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) - SUM( valor_troco ) as total"));
                $query_faturamento_realizado->whereBetween('data', [$primeiro_dia, $ultimo_dia]);
                $query_faturamento_realizado->where(function ($query) {
                    $query->orWhere("tipo_operacao", "ilike", "VENDA%");
                    $query->orWhere("tipo_operacao", "ilike", "SIMPLESFATFUTURA");
                    $query->orWhere("tipo_operacao", "ilike", "DEV%");
                });
                $query_faturamento_realizado->where("nasajon", true);
                $result_faturamento_realizado = $query_faturamento_realizado->first();

                $retorno[$data_titulo->format('m/Y')]['venda_realizada'] = empty($result_faturamento_realizado->total)? 0 : $result_faturamento_realizado->total;
            }
        }

        $query_aberto = PedidosPrePago::select();
        $query_aberto->whereBetween('created_at', [$primeiro_dia_do_mes, $ultimo_dia_do_mes]);
        $result_aberto = $query_aberto->get();

        foreach ($result_aberto as $index => $valor) {
            $data_mes = Carbon::parse($valor->created_at);

            $retorno[$data_mes->format('m/Y')]['venda_realizada'] +=  $valor->valor_pago;
        }

        $total = [
            'realizado_venda' => 0,
        ];

        foreach($retorno as $index => $value){
            $retorno[$index]['porcetagem_referente_mes_atual_realizada'] = empty($value['venda_realizada'])? '' : (($value['valor_referente_mes_atual_realizada'] / $value['venda_realizada']) * 100);
            $retorno[$index]['diferenca'] =  empty($value['valor_referente_mes_atual'])? '' : ((($value['valor_referente_mes_atual_realizada'] / $value['valor_referente_mes_atual']) - 1) * 100);
            
            $total['realizado_venda'] +=  $retorno[$index]['valor_referente_mes_atual_realizada'];

            $retorno[$index]['faturamento_venda'] = empty($value['faturamento_venda'])? '' : $retorno[$index]['faturamento_venda'];
            $retorno[$index]['porcetagem_referente_mes_atual'] = empty($value['porcetagem_referente_mes_atual'])? '' : $retorno[$index]['porcetagem_referente_mes_atual'];
            $retorno[$index]['valor_referente_mes_atual'] = empty($value['valor_referente_mes_atual'])? '' : $retorno[$index]['valor_referente_mes_atual'];
            $retorno[$index]['venda_realizada'] = empty($value['venda_realizada'])? '' : $retorno[$index]['venda_realizada'];
            $retorno[$index]['valor_referente_mes_atual_realizada'] = empty($value['valor_referente_mes_atual_realizada'])? '' : $retorno[$index]['valor_referente_mes_atual_realizada'];
        }

        $proximo_ano = $data_atual->year + 1;
        $data_atual = $data_atual->firstOfMonth();

        $query_faturamento_previsto = FaturamentoPrevisto::select();
        $query_faturamento_previsto->where('data', $data_atual);
        $result_faturamento_previsto = $query_faturamento_previsto->first();

        $total_previsto = ($result_faturamento_previsto->nacional_valor + $result_faturamento_previsto->importado_valor);

        $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()->where('mes_ano', $data_atual)->first();
        if(empty($faturamentoPrevistoFluxoCaixaobj)){
            $faturamentoPrevistoFluxoCaixaobj = new FaturamentoPrevistoFluxoCaixa;
        }
        $faturamentoPrevistoFluxoCaixaobj->mes_ano = $data_atual;
        $faturamentoPrevistoFluxoCaixaobj->valor = $total_previsto;
        $faturamentoPrevistoFluxoCaixaobj->faturamento_previstos_valor = $total_previsto;

        $faturamentoPrevistoFluxoCaixaobj->faturamento_30_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
        $faturamentoPrevistoFluxoCaixaobj->faturamento_30_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));
        
        $data_mes_anterior = $data_mes_anterior->subMonth();
        $faturamentoPrevistoFluxoCaixaobj->faturamento_60_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
        $faturamentoPrevistoFluxoCaixaobj->faturamento_60_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

        $data_mes_anterior = $data_mes_anterior->subMonth();
        $faturamentoPrevistoFluxoCaixaobj->faturamento_90_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
        $faturamentoPrevistoFluxoCaixaobj->faturamento_90_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

        $data_mes_anterior = $data_mes_anterior->subMonth();
        $faturamentoPrevistoFluxoCaixaobj->faturamento_120_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
        $faturamentoPrevistoFluxoCaixaobj->faturamento_120_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

        $data_mes_anterior = $data_mes_anterior->subMonth();
        $faturamentoPrevistoFluxoCaixaobj->faturamento_150_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
        $faturamentoPrevistoFluxoCaixaobj->faturamento_150_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

        $data_mes_anterior = $data_mes_anterior->subMonth();
        $faturamentoPrevistoFluxoCaixaobj->faturamento_180_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
        $faturamentoPrevistoFluxoCaixaobj->faturamento_180_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

        $data_mes_anterior = $data_mes_anterior->subMonth();
        $faturamentoPrevistoFluxoCaixaobj->faturamento_210_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
        $faturamentoPrevistoFluxoCaixaobj->faturamento_210_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

        $data_mes_anterior = $data_mes_anterior->subMonth();
        $faturamentoPrevistoFluxoCaixaobj->faturamento_240_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
        $faturamentoPrevistoFluxoCaixaobj->faturamento_240_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

        $data_mes_anterior = $data_mes_anterior->subMonth();
        $faturamentoPrevistoFluxoCaixaobj->faturamento_270_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
        $faturamentoPrevistoFluxoCaixaobj->faturamento_270_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

        $data_mes_anterior = $data_mes_anterior->subMonth();
        $faturamentoPrevistoFluxoCaixaobj->faturamento_300_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
        $faturamentoPrevistoFluxoCaixaobj->faturamento_300_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

        $data_mes_anterior = $data_mes_anterior->subMonth();
        $faturamentoPrevistoFluxoCaixaobj->faturamento_330_dias_porcetagem = empty($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
        $faturamentoPrevistoFluxoCaixaobj->faturamento_330_dias_valor = empty($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

        $data_mes_anterior = $data_mes_anterior->subMonth();
        $faturamentoPrevistoFluxoCaixaobj->faturamento_360_dias_porcetagem = empty($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
        $faturamentoPrevistoFluxoCaixaobj->faturamento_360_dias_valor = empty($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

        $faturamentoPrevistoFluxoCaixaobj->created_by = 1;
        $faturamentoPrevistoFluxoCaixaobj->save();

        while($data_atual->year <= $proximo_ano){
            $data_mes_anterior = Carbon::now()->setTime(0, 0, 0)->subMonth();

            $query_faturamento_previsto = FaturamentoPrevisto::select();
            $query_faturamento_previsto->where('data', $data_atual);
            $result_faturamento_previsto = $query_faturamento_previsto->first();

            $total_previsto = 0;
            if(!empty($result_faturamento_previsto)){
                $total_previsto = ($result_faturamento_previsto->nacional_valor + $result_faturamento_previsto->importado_valor);
            }else{
                $data_primeira = ($data_atual->year - 1).'-'.$data_atual->format('m').'-'.'01';
                $data_segunda = ($data_atual->year - 2).'-'.$data_atual->format('m').'-'.'01';
                $query_faturamento_previsto = FaturamentoPrevisto::select(DB::Raw('avg(importado_valor+nacional_valor) as media'));
                $query_faturamento_previsto->where('data', $data_primeira); 
                $query_faturamento_previsto->orWhere('data', $data_segunda);
                $result_faturamento_previsto = $query_faturamento_previsto->first();

                $total_previsto = $result_faturamento_previsto->media;
            }         
    
            $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()->where('mes_ano', $data_atual)->first();
            if(empty($faturamentoPrevistoFluxoCaixaobj)){
                $faturamentoPrevistoFluxoCaixaobj = new FaturamentoPrevistoFluxoCaixa;
            }
            $faturamentoPrevistoFluxoCaixaobj->mes_ano = $data_atual;
            $faturamentoPrevistoFluxoCaixaobj->valor = $total_previsto;
            $faturamentoPrevistoFluxoCaixaobj->faturamento_previstos_valor = $total_previsto;

            $faturamentoPrevistoFluxoCaixaobj->faturamento_30_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_30_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));
            
            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_60_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_60_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_90_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_90_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_120_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_120_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_150_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_150_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_180_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_180_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_210_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_210_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_240_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_240_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_270_dias_porcetagem = empty($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_270_dias_valor = empty($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_300_dias_porcetagem = $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_300_dias_valor = ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_330_dias_porcetagem = empty($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'])? 0: $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_330_dias_valor = empty($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_360_dias_porcetagem = empty($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_360_dias_valor = empty($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $faturamentoPrevistoFluxoCaixaobj->created_by = 1;
            $faturamentoPrevistoFluxoCaixaobj->save();

            $data_atual = $data_atual->addMonth();
        }
    }

    public function geracaoDadosAnoAnterior(){
        $data_verificacao = Carbon::now()->setTime(0, 0, 0);

        $i = 1;

        while($data_verificacao->year != 2019){
            $retorno = [];
            $data_atual = Carbon::now()->setTime(0, 0, 0)->subMonths($i);
            $data_mes_anterior = Carbon::now()->setTime(0, 0, 0)->subMonths($i + 1);

            $primeiro_dia_do_mes= Carbon::now()->setTime(0,0,0)->subMonths($i + 1)->firstOfMonth();
            $ultimo_dia_do_mes= Carbon::now()->setTime(23,59,59)->subMonths($i + 1)->lastOfMonth();

            $cnpj = $this->cnpjINtercompany();
            $query_faturamento_caixa = TitulosEmAbertoNasajon::select('titulo_emissao', DB::Raw("sum(saldotitulo) as total"));
            $query_faturamento_caixa->whereBetween('vencimento', [$primeiro_dia_do_mes, $ultimo_dia_do_mes])
                ->where('saldotitulo', '>', '0')
                ->whereNotIn("codigo", ['25', 'TREINAMENTO'])
                ->whereNotIn("cod_cliente", $cnpj);
            $query_faturamento_caixa->groupBy('titulo_emissao');

            $query_faturamento_caixa_recebido = TitulosPagosNasajon::select('emissao', DB::Raw("sum(valor_titulo) as total"));
            $query_faturamento_caixa_recebido->whereBetween('vencimento', [$primeiro_dia_do_mes, $ultimo_dia_do_mes])
                ->where('valor_titulo', '>', '0')
                    ->whereNotIn("codigo", ['25', 'TREINAMENTO'])
                ->whereNotIn("cod_cliente", $cnpj);
            $query_faturamento_caixa_recebido->groupBy('emissao');

            $result_faturamento_caixa = $query_faturamento_caixa->get();

            $result_faturamento_caixa_recebido = $query_faturamento_caixa_recebido->get();

            foreach($result_faturamento_caixa as $value){
                $data_titulo = Carbon::parse($value->titulo_emissao);
                $primeiro_dia = Carbon::parse($value->titulo_emissao)->setTime(0,0,0)->firstOfMonth();
                $ultimo_dia = Carbon::parse($value->titulo_emissao)->setTime(23,59,59)->lastOfMonth();
                
                if(empty($retorno[$data_titulo->format('m/Y')])){
                    $retorno[$data_titulo->format('m/Y')] = [
                        'faturamento_venda' => 0,
                        'porcetagem_referente_mes_atual' => 0,
                        'valor_referente_mes_atual' => 0,
                        'venda_realizada' => 0,
                        'porcetagem_referente_mes_atual_realizada' => 0,
                        'valor_referente_mes_atual_realizada' => $value->total,
                        'diferenca' => 0,
                        'ordenacao' => $data_titulo->format('Y-m-d'),
                    ];
                }else{
                    $retorno[$data_titulo->format('m/Y')]['valor_referente_mes_atual_realizada'] += $value->total;
                }

                if(empty($retorno[$data_titulo->format('m/Y')]['venda_realizada'])){
                    $query_faturamento_realizado = FaturamentoOnline::select(DB::Raw("SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) - SUM( valor_troco ) as total"));
                    $query_faturamento_realizado->whereBetween('data', [$primeiro_dia, $ultimo_dia]);
                    $query_faturamento_realizado->where(function ($query) {
                        $query->orWhere("tipo_operacao", "ilike", "VENDA%");
                        $query->orWhere("tipo_operacao", "ilike", "SIMPLESFATFUTURA");
                        $query->orWhere("tipo_operacao", "ilike", "DEV%");
                    });
                    $query_faturamento_realizado->where("nasajon", true);
                    $result_faturamento_realizado = $query_faturamento_realizado->first();

                    $retorno[$data_titulo->format('m/Y')]['venda_realizada'] = empty($result_faturamento_realizado->total)? 0 : $result_faturamento_realizado->total;
                }
            }

            foreach($result_faturamento_caixa_recebido as $value){
                $data_titulo = Carbon::parse($value->emissao);
                $primeiro_dia = Carbon::parse($value->emissao)->setTime(0,0,0)->firstOfMonth();
                $ultimo_dia = Carbon::parse($value->emissao)->setTime(23,59,59)->lastOfMonth();
            
                if(empty($retorno[$data_titulo->format('m/Y')])){
                    $retorno[$data_titulo->format('m/Y')] = [
                        'faturamento_venda' => 0,
                        'porcetagem_referente_mes_atual' => 0,
                        'valor_referente_mes_atual' => 0,
                        'venda_realizada' => 0,
                        'porcetagem_referente_mes_atual_realizada' => 0,
                        'valor_referente_mes_atual_realizada' => $value->total,
                        'diferenca' => 0,
                        'ordenacao' => $data_titulo->format('Y-m-d'),
                    ];
                }else{
                    $retorno[$data_titulo->format('m/Y')]['valor_referente_mes_atual_realizada'] += $value->total;
                }

                if(empty($retorno[$data_titulo->format('m/Y')]['venda_realizada'])){
                    $query_faturamento_realizado = FaturamentoOnline::select(DB::Raw("SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) - SUM( valor_troco ) as total"));
                    $query_faturamento_realizado->whereBetween('data', [$primeiro_dia, $ultimo_dia]);
                    $query_faturamento_realizado->where(function ($query) {
                        $query->orWhere("tipo_operacao", "ilike", "VENDA%");
                        $query->orWhere("tipo_operacao", "ilike", "SIMPLESFATFUTURA");
                        $query->orWhere("tipo_operacao", "ilike", "DEV%");
                    });
                    $query_faturamento_realizado->where("nasajon", true);
                    $result_faturamento_realizado = $query_faturamento_realizado->first();

                    $retorno[$data_titulo->format('m/Y')]['venda_realizada'] = empty($result_faturamento_realizado->total)? 0 : $result_faturamento_realizado->total;
                }
            }

            $query_aberto = PedidosPrePago::select();
            $query_aberto->whereBetween('created_at', [$primeiro_dia_do_mes, $ultimo_dia_do_mes]);
            $result_aberto = $query_aberto->get();

            foreach ($result_aberto as $index => $valor) {
                $data_mes = Carbon::parse($valor->created_at);

                $retorno[$data_mes->format('m/Y')]['venda_realizada'] +=  $valor->valor_pago;
            }

            $total = [
                'realizado_venda' => 0,
            ];

            foreach($retorno as $index => $value){
                $retorno[$index]['porcetagem_referente_mes_atual_realizada'] = empty($value['venda_realizada'])? '' : (($value['valor_referente_mes_atual_realizada'] / $value['venda_realizada']) * 100);
                $retorno[$index]['diferenca'] =  empty($value['valor_referente_mes_atual'])? '' : ((($value['valor_referente_mes_atual_realizada'] / $value['valor_referente_mes_atual']) - 1) * 100);
                
                $total['realizado_venda'] +=  $retorno[$index]['valor_referente_mes_atual_realizada'];

                $retorno[$index]['faturamento_venda'] = empty($value['faturamento_venda'])? '' : $retorno[$index]['faturamento_venda'];
                $retorno[$index]['porcetagem_referente_mes_atual'] = empty($value['porcetagem_referente_mes_atual'])? '' : $retorno[$index]['porcetagem_referente_mes_atual'];
                $retorno[$index]['valor_referente_mes_atual'] = empty($value['valor_referente_mes_atual'])? '' : $retorno[$index]['valor_referente_mes_atual'];
                $retorno[$index]['venda_realizada'] = empty($value['venda_realizada'])? '' : $retorno[$index]['venda_realizada'];
                $retorno[$index]['valor_referente_mes_atual_realizada'] = empty($value['valor_referente_mes_atual_realizada'])? '' : $retorno[$index]['valor_referente_mes_atual_realizada'];
            }

            $data_atual = $data_atual->firstOfMonth();

            $query_faturamento_previsto = FaturamentoPrevisto::select();
            $query_faturamento_previsto->where('data', $data_atual);
            $result_faturamento_previsto = $query_faturamento_previsto->first();

            $total_previsto = empty($result_faturamento_previsto)? 0 : ($result_faturamento_previsto->nacional_valor + $result_faturamento_previsto->importado_valor);

            $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()->where('mes_ano', $data_atual)->first();
            if(empty($faturamentoPrevistoFluxoCaixaobj)){
                $faturamentoPrevistoFluxoCaixaobj = new FaturamentoPrevistoFluxoCaixa;
            }
            $faturamentoPrevistoFluxoCaixaobj->mes_ano = $data_atual;
            $faturamentoPrevistoFluxoCaixaobj->valor = $total_previsto;
            $faturamentoPrevistoFluxoCaixaobj->faturamento_previstos_valor = $total_previsto;
            
            $faturamentoPrevistoFluxoCaixaobj->faturamento_30_dias_porcetagem =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_30_dias_valor =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));
        
            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_60_dias_porcetagem =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_60_dias_valor =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_90_dias_porcetagem =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_90_dias_valor =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_120_dias_porcetagem =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_120_dias_valor =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_150_dias_porcetagem =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_150_dias_valor =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_180_dias_porcetagem =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_180_dias_valor =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_210_dias_porcetagem =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_210_dias_valor =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_240_dias_porcetagem =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_240_dias_valor =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_270_dias_porcetagem =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_270_dias_valor =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_300_dias_porcetagem =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_300_dias_valor =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_330_dias_porcetagem =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_330_dias_valor =  empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $data_mes_anterior = $data_mes_anterior->subMonth();
            $faturamentoPrevistoFluxoCaixaobj->faturamento_360_dias_porcetagem = empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : $retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'];
            $faturamentoPrevistoFluxoCaixaobj->faturamento_360_dias_valor = empty($retorno[$data_mes_anterior->format('m/Y')])? 0 : ($total_previsto * ($retorno[$data_mes_anterior->format('m/Y')]['porcetagem_referente_mes_atual_realizada'] / 100));

            $faturamentoPrevistoFluxoCaixaobj->created_by = 1;
            $faturamentoPrevistoFluxoCaixaobj->save();

            $data_verificacao = $data_verificacao->subMonth();
            $i++;
        }
    }

    public function geracaoDadosAnoPosterior(){
        $this->geracaoDadosAnoPosteriorFevereiro();
        $this->geracaoDadosAnoPosteriorMarco();
        $this->geracaoDadosAnoPosteriorAbril();
    }

    public function geracaoDadosAnoPosteriorFevereiro(){
        $retorno = [];
        
        $data_demostracao = Carbon::parse('2022-02-01');
        
        $total_previsto = 0;

        $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()
            ->whereBetween('mes_ano', ['2021-03-01', '2022-02-01'])
            ->get();

        $dados = [];

        foreach($faturamentoPrevistoFluxoCaixaobj as $value){
            $dados[$value->mes_ano->format('Y-m-d')] = [
                "valor" => $value->valor,
                "faturamento_previstos_valor" => $value->faturamento_previstos_valor,
                "faturamento_30_dias_porcetagem" => $value->faturamento_30_dias_porcetagem,
                "faturamento_30_dias_valor" => $value->faturamento_30_dias_valor,
                "faturamento_60_dias_porcetagem" => $value->faturamento_60_dias_porcetagem,
                "faturamento_60_dias_valor" => $value->faturamento_60_dias_valor,
                "faturamento_90_dias_porcetagem" => $value->faturamento_90_dias_porcetagem,
                "faturamento_90_dias_valor" => $value->faturamento_90_dias_valor,
                "faturamento_120_dias_porcetagem" => $value->faturamento_120_dias_porcetagem,
                "faturamento_120_dias_valor" => $value->faturamento_120_dias_valor,
                "faturamento_150_dias_porcetagem" => $value->faturamento_150_dias_porcetagem,
                "faturamento_150_dias_valor" => $value->faturamento_150_dias_valor,
                "faturamento_180_dias_porcetagem" => $value->faturamento_180_dias_porcetagem,
                "faturamento_180_dias_valor" => $value->faturamento_180_dias_valor,
                "faturamento_210_dias_porcetagem" => $value->faturamento_210_dias_porcetagem,
                "faturamento_210_dias_valor" => $value->faturamento_210_dias_valor,
                "faturamento_240_dias_porcetagem" => $value->faturamento_240_dias_porcetagem,
                "faturamento_240_dias_valor" => $value->faturamento_240_dias_valor,
                "faturamento_270_dias_porcetagem" => $value->faturamento_270_dias_porcetagem,
                "faturamento_270_dias_valor" => $value->faturamento_270_dias_valor,
                "faturamento_300_dias_porcetagem" => $value->faturamento_300_dias_porcetagem,
                "faturamento_300_dias_valor" => $value->faturamento_300_dias_valor,
                "faturamento_330_dias_porcetagem" => $value->faturamento_330_dias_porcetagem,
                "faturamento_330_dias_valor" => $value->faturamento_330_dias_valor,
                "faturamento_360_dias_porcetagem" => $value->faturamento_360_dias_porcetagem,
                "faturamento_360_dias_valor" => $value->faturamento_360_dias_valor,
            ];
        }

        $i = 0;
        $retorno = [];
        $divisao_mes_atual = [];
        $data = Carbon::parse('2022-02-01');
        while(!empty($dados[$data->format('Y-m-d')])){
            $porcetagem = 0;
            $valor = 0;

            switch($i){
                case 0:
                    $divisao_mes_atual = $dados[$data->format('Y-m-d')];
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_30_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_30_dias_valor'];
                    break;
                case 1:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_60_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_60_dias_valor'];
                    break;
                case 2:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_90_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_90_dias_valor'];
                    break;
                case 3:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_120_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_120_dias_valor'];
                    break;
                case 4:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_150_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_150_dias_valor'];
                    break;
                case 5:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_180_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_180_dias_valor'];
                    break;
                case 6:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_210_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_210_dias_valor'];
                    break;
                case 7:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_240_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_240_dias_valor'];
                    break;
                case 8:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_270_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_270_dias_valor'];
                    break;
                case 9:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_300_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_300_dias_valor'];
                    break;
                case 10:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_330_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_330_dias_valor'];
                    break;
                case 11:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_360_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_360_dias_valor'];
                    break;
            }

            $retorno[$data->format('m/Y')] = [
                'faturamento_venda' => parserValor($dados[$data->format('Y-m-d')]['faturamento_previstos_valor']),
                'porcetagem_referente_mes_atual' => parserValor($porcetagem)."%",
                'valor_referente_mes_atual' => parserValor($valor),
                'data_string' => $data->format('Ymd'),
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
                    $porcetagem = empty($divisao_mes_atual['faturamento_30_dias_porcetagem'])? 0 : $divisao_mes_atual['faturamento_30_dias_porcetagem'];
                    $valor = empty($divisao_mes_atual['faturamento_30_dias_valor'])? 0 : $divisao_mes_atual['faturamento_30_dias_valor'];
                    break;
                case 1:
                    $porcetagem = $divisao_mes_atual['faturamento_60_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_60_dias_valor'];
                    break;
                case 2:
                    $porcetagem = $divisao_mes_atual['faturamento_90_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_90_dias_valor'];
                    break;
                case 3:
                    $porcetagem = $divisao_mes_atual['faturamento_120_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_120_dias_valor'];
                    break;
                case 4:
                    $porcetagem = $divisao_mes_atual['faturamento_150_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_150_dias_valor'];
                    break;
                case 5:
                    $porcetagem = $divisao_mes_atual['faturamento_180_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_180_dias_valor'];
                    break;
                case 6:
                    $porcetagem = $divisao_mes_atual['faturamento_210_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_210_dias_valor'];
                    break;
                case 7:
                    $porcetagem = $divisao_mes_atual['faturamento_240_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_240_dias_valor'];
                    break;
                case 8:
                    $porcetagem = $divisao_mes_atual['faturamento_270_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_270_dias_valor'];
                    break;
                case 9:
                    $porcetagem = $divisao_mes_atual['faturamento_300_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_300_dias_valor'];
                    break;
                case 10:
                    $porcetagem = $divisao_mes_atual['faturamento_330_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_330_dias_valor'];
                    break;
                case 11:
                    $porcetagem = $divisao_mes_atual['faturamento_360_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_360_dias_valor'];
                    break;
            }
            $data_tabela[] = [
                'mes_ano' => $data_demostracao->format('Y-m-d'),
                'porcetagem' => parserValor($porcetagem)."%",
                'valor' =>$valor,
                'data_string' => $data_demostracao->format('Ymd'),
            ];
            $data_demostracao->addMonth();
        }

        $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()->where('mes_ano',  $data_tabela[11]['mes_ano'])->first();
        if(empty($faturamentoPrevistoFluxoCaixaobj)){
            $faturamentoPrevistoFluxoCaixaobj = new FaturamentoPrevistoFluxoCaixa;
        }
        $faturamentoPrevistoFluxoCaixaobj->mes_ano = $data_tabela[11]['mes_ano'];
        $faturamentoPrevistoFluxoCaixaobj->valor = $data_tabela[11]['valor'];

        $faturamentoPrevistoFluxoCaixaobj->faturamento_30_dias_porcetagem = 0;
        $faturamentoPrevistoFluxoCaixaobj->faturamento_30_dias_valor = $data_tabela[11]['valor'];


        $faturamentoPrevistoFluxoCaixaobj->created_by = 1;
        $faturamentoPrevistoFluxoCaixaobj->save();

    }

    public function geracaoDadosAnoPosteriorMarco(){
        $retorno = [];
        
        $data_demostracao = Carbon::parse('2022-03-01');
        
        $total_previsto = 0;

        $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()
            ->whereBetween('mes_ano', ['2021-04-01', '2022-03-01'])
            ->get();

        $dados = [];

        foreach($faturamentoPrevistoFluxoCaixaobj as $value){
            $dados[$value->mes_ano->format('Y-m-d')] = [
                "valor" => $value->valor,
                "faturamento_previstos_valor" => $value->faturamento_previstos_valor,
                "faturamento_30_dias_porcetagem" => $value->faturamento_30_dias_porcetagem,
                "faturamento_30_dias_valor" => $value->faturamento_30_dias_valor,
                "faturamento_60_dias_porcetagem" => $value->faturamento_60_dias_porcetagem,
                "faturamento_60_dias_valor" => $value->faturamento_60_dias_valor,
                "faturamento_90_dias_porcetagem" => $value->faturamento_90_dias_porcetagem,
                "faturamento_90_dias_valor" => $value->faturamento_90_dias_valor,
                "faturamento_120_dias_porcetagem" => $value->faturamento_120_dias_porcetagem,
                "faturamento_120_dias_valor" => $value->faturamento_120_dias_valor,
                "faturamento_150_dias_porcetagem" => $value->faturamento_150_dias_porcetagem,
                "faturamento_150_dias_valor" => $value->faturamento_150_dias_valor,
                "faturamento_180_dias_porcetagem" => $value->faturamento_180_dias_porcetagem,
                "faturamento_180_dias_valor" => $value->faturamento_180_dias_valor,
                "faturamento_210_dias_porcetagem" => $value->faturamento_210_dias_porcetagem,
                "faturamento_210_dias_valor" => $value->faturamento_210_dias_valor,
                "faturamento_240_dias_porcetagem" => $value->faturamento_240_dias_porcetagem,
                "faturamento_240_dias_valor" => $value->faturamento_240_dias_valor,
                "faturamento_270_dias_porcetagem" => $value->faturamento_270_dias_porcetagem,
                "faturamento_270_dias_valor" => $value->faturamento_270_dias_valor,
                "faturamento_300_dias_porcetagem" => $value->faturamento_300_dias_porcetagem,
                "faturamento_300_dias_valor" => $value->faturamento_300_dias_valor,
                "faturamento_330_dias_porcetagem" => $value->faturamento_330_dias_porcetagem,
                "faturamento_330_dias_valor" => $value->faturamento_330_dias_valor,
                "faturamento_360_dias_porcetagem" => $value->faturamento_360_dias_porcetagem,
                "faturamento_360_dias_valor" => $value->faturamento_360_dias_valor,
            ];
        }

        $i = 0;
        $retorno = [];
        $divisao_mes_atual = [];
        $data = Carbon::parse('2022-03-01');
        while(!empty($dados[$data->format('Y-m-d')])){
            $porcetagem = 0;
            $valor = 0;

            switch($i){
                case 0:
                    $divisao_mes_atual = $dados[$data->format('Y-m-d')];
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_30_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_30_dias_valor'];
                    break;
                case 1:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_60_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_60_dias_valor'];
                    break;
                case 2:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_90_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_90_dias_valor'];
                    break;
                case 3:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_120_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_120_dias_valor'];
                    break;
                case 4:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_150_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_150_dias_valor'];
                    break;
                case 5:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_180_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_180_dias_valor'];
                    break;
                case 6:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_210_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_210_dias_valor'];
                    break;
                case 7:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_240_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_240_dias_valor'];
                    break;
                case 8:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_270_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_270_dias_valor'];
                    break;
                case 9:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_300_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_300_dias_valor'];
                    break;
                case 10:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_330_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_330_dias_valor'];
                    break;
                case 11:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_360_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_360_dias_valor'];
                    break;
            }

            $retorno[$data->format('m/Y')] = [
                'faturamento_venda' => parserValor($dados[$data->format('Y-m-d')]['faturamento_previstos_valor']),
                'porcetagem_referente_mes_atual' => parserValor($porcetagem)."%",
                'valor_referente_mes_atual' => parserValor($valor),
                'data_string' => $data->format('Ymd'),
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
                    $porcetagem = empty($divisao_mes_atual['faturamento_30_dias_porcetagem'])? 0 : $divisao_mes_atual['faturamento_30_dias_porcetagem'];
                    $valor = empty($divisao_mes_atual['faturamento_30_dias_valor'])? 0 : $divisao_mes_atual['faturamento_30_dias_valor'];
                    break;
                case 1:
                    $porcetagem = $divisao_mes_atual['faturamento_60_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_60_dias_valor'];
                    break;
                case 2:
                    $porcetagem = $divisao_mes_atual['faturamento_90_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_90_dias_valor'];
                    break;
                case 3:
                    $porcetagem = $divisao_mes_atual['faturamento_120_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_120_dias_valor'];
                    break;
                case 4:
                    $porcetagem = $divisao_mes_atual['faturamento_150_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_150_dias_valor'];
                    break;
                case 5:
                    $porcetagem = $divisao_mes_atual['faturamento_180_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_180_dias_valor'];
                    break;
                case 6:
                    $porcetagem = $divisao_mes_atual['faturamento_210_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_210_dias_valor'];
                    break;
                case 7:
                    $porcetagem = $divisao_mes_atual['faturamento_240_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_240_dias_valor'];
                    break;
                case 8:
                    $porcetagem = $divisao_mes_atual['faturamento_270_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_270_dias_valor'];
                    break;
                case 9:
                    $porcetagem = $divisao_mes_atual['faturamento_300_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_300_dias_valor'];
                    break;
                case 10:
                    $porcetagem = $divisao_mes_atual['faturamento_330_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_330_dias_valor'];
                    break;
                case 11:
                    $porcetagem = $divisao_mes_atual['faturamento_360_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_360_dias_valor'];
                    break;
            }
            $data_tabela[] = [
                'mes_ano' => $data_demostracao->format('Y-m-d'),
                'porcetagem' => parserValor($porcetagem)."%",
                'valor' =>$valor,
                'data_string' => $data_demostracao->format('Ymd'),
            ];
            $data_demostracao->addMonth();
        }

        $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()->where('mes_ano',  $data_tabela[11]['mes_ano'])->first();
        if(empty($faturamentoPrevistoFluxoCaixaobj)){
            $faturamentoPrevistoFluxoCaixaobj = new FaturamentoPrevistoFluxoCaixa;
        }
        $faturamentoPrevistoFluxoCaixaobj->mes_ano = $data_tabela[11]['mes_ano'];
        $faturamentoPrevistoFluxoCaixaobj->valor = $data_tabela[11]['valor'];

        $faturamentoPrevistoFluxoCaixaobj->faturamento_30_dias_porcetagem = 0;
        $faturamentoPrevistoFluxoCaixaobj->faturamento_30_dias_valor = $data_tabela[11]['valor'];


        $faturamentoPrevistoFluxoCaixaobj->created_by = 1;
        $faturamentoPrevistoFluxoCaixaobj->save();

        $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()->where('mes_ano',  $data_tabela[10]['mes_ano'])->first();
        if(empty($faturamentoPrevistoFluxoCaixaobj)){
            $faturamentoPrevistoFluxoCaixaobj = new FaturamentoPrevistoFluxoCaixa;
        }
        $faturamentoPrevistoFluxoCaixaobj->mes_ano = $data_tabela[10]['mes_ano'];
        $faturamentoPrevistoFluxoCaixaobj->valor = $data_tabela[10]['valor'];

        $faturamentoPrevistoFluxoCaixaobj->faturamento_60_dias_porcetagem = 0;
        $faturamentoPrevistoFluxoCaixaobj->faturamento_60_dias_valor = $data_tabela[10]['valor'];


        $faturamentoPrevistoFluxoCaixaobj->created_by = 1;
        $faturamentoPrevistoFluxoCaixaobj->save();
    }

    public function geracaoDadosAnoPosteriorAbril(){
        $retorno = [];
        
        $data_demostracao = Carbon::parse('2022-04-01');
        
        $total_previsto = 0;

        $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()
            ->whereBetween('mes_ano', ['2021-05-01', '2022-04-01'])
            ->get();

        $dados = [];

        foreach($faturamentoPrevistoFluxoCaixaobj as $value){
            $dados[$value->mes_ano->format('Y-m-d')] = [
                "valor" => $value->valor,
                "faturamento_previstos_valor" => $value->faturamento_previstos_valor,
                "faturamento_30_dias_porcetagem" => $value->faturamento_30_dias_porcetagem,
                "faturamento_30_dias_valor" => $value->faturamento_30_dias_valor,
                "faturamento_60_dias_porcetagem" => $value->faturamento_60_dias_porcetagem,
                "faturamento_60_dias_valor" => $value->faturamento_60_dias_valor,
                "faturamento_90_dias_porcetagem" => $value->faturamento_90_dias_porcetagem,
                "faturamento_90_dias_valor" => $value->faturamento_90_dias_valor,
                "faturamento_120_dias_porcetagem" => $value->faturamento_120_dias_porcetagem,
                "faturamento_120_dias_valor" => $value->faturamento_120_dias_valor,
                "faturamento_150_dias_porcetagem" => $value->faturamento_150_dias_porcetagem,
                "faturamento_150_dias_valor" => $value->faturamento_150_dias_valor,
                "faturamento_180_dias_porcetagem" => $value->faturamento_180_dias_porcetagem,
                "faturamento_180_dias_valor" => $value->faturamento_180_dias_valor,
                "faturamento_210_dias_porcetagem" => $value->faturamento_210_dias_porcetagem,
                "faturamento_210_dias_valor" => $value->faturamento_210_dias_valor,
                "faturamento_240_dias_porcetagem" => $value->faturamento_240_dias_porcetagem,
                "faturamento_240_dias_valor" => $value->faturamento_240_dias_valor,
                "faturamento_270_dias_porcetagem" => $value->faturamento_270_dias_porcetagem,
                "faturamento_270_dias_valor" => $value->faturamento_270_dias_valor,
                "faturamento_300_dias_porcetagem" => $value->faturamento_300_dias_porcetagem,
                "faturamento_300_dias_valor" => $value->faturamento_300_dias_valor,
                "faturamento_330_dias_porcetagem" => $value->faturamento_330_dias_porcetagem,
                "faturamento_330_dias_valor" => $value->faturamento_330_dias_valor,
                "faturamento_360_dias_porcetagem" => $value->faturamento_360_dias_porcetagem,
                "faturamento_360_dias_valor" => $value->faturamento_360_dias_valor,
            ];
        }

        $i = 0;
        $retorno = [];
        $divisao_mes_atual = [];
        $data = Carbon::parse('2022-04-01');
        while(!empty($dados[$data->format('Y-m-d')])){
            $porcetagem = 0;
            $valor = 0;

            switch($i){
                case 0:
                    $divisao_mes_atual = $dados[$data->format('Y-m-d')];
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_30_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_30_dias_valor'];
                    break;
                case 1:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_60_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_60_dias_valor'];
                    break;
                case 2:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_90_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_90_dias_valor'];
                    break;
                case 3:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_120_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_120_dias_valor'];
                    break;
                case 4:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_150_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_150_dias_valor'];
                    break;
                case 5:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_180_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_180_dias_valor'];
                    break;
                case 6:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_210_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_210_dias_valor'];
                    break;
                case 7:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_240_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_240_dias_valor'];
                    break;
                case 8:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_270_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_270_dias_valor'];
                    break;
                case 9:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_300_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_300_dias_valor'];
                    break;
                case 10:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_330_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_330_dias_valor'];
                    break;
                case 11:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_360_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_360_dias_valor'];
                    break;
            }

            $retorno[$data->format('m/Y')] = [
                'faturamento_venda' => parserValor($dados[$data->format('Y-m-d')]['faturamento_previstos_valor']),
                'porcetagem_referente_mes_atual' => parserValor($porcetagem)."%",
                'valor_referente_mes_atual' => parserValor($valor),
                'data_string' => $data->format('Ymd'),
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
                    $porcetagem = empty($divisao_mes_atual['faturamento_30_dias_porcetagem'])? 0 : $divisao_mes_atual['faturamento_30_dias_porcetagem'];
                    $valor = empty($divisao_mes_atual['faturamento_30_dias_valor'])? 0 : $divisao_mes_atual['faturamento_30_dias_valor'];
                    break;
                case 1:
                    $porcetagem = $divisao_mes_atual['faturamento_60_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_60_dias_valor'];
                    break;
                case 2:
                    $porcetagem = $divisao_mes_atual['faturamento_90_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_90_dias_valor'];
                    break;
                case 3:
                    $porcetagem = $divisao_mes_atual['faturamento_120_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_120_dias_valor'];
                    break;
                case 4:
                    $porcetagem = $divisao_mes_atual['faturamento_150_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_150_dias_valor'];
                    break;
                case 5:
                    $porcetagem = $divisao_mes_atual['faturamento_180_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_180_dias_valor'];
                    break;
                case 6:
                    $porcetagem = $divisao_mes_atual['faturamento_210_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_210_dias_valor'];
                    break;
                case 7:
                    $porcetagem = $divisao_mes_atual['faturamento_240_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_240_dias_valor'];
                    break;
                case 8:
                    $porcetagem = $divisao_mes_atual['faturamento_270_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_270_dias_valor'];
                    break;
                case 9:
                    $porcetagem = $divisao_mes_atual['faturamento_300_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_300_dias_valor'];
                    break;
                case 10:
                    $porcetagem = $divisao_mes_atual['faturamento_330_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_330_dias_valor'];
                    break;
                case 11:
                    $porcetagem = $divisao_mes_atual['faturamento_360_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_360_dias_valor'];
                    break;
            }
            $data_tabela[] = [
                'mes_ano' => $data_demostracao->format('Y-m-d'),
                'porcetagem' => parserValor($porcetagem)."%",
                'valor' =>$valor,
                'data_string' => $data_demostracao->format('Ymd'),
            ];
            $data_demostracao->addMonth();
        }

        $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()->where('mes_ano',  $data_tabela[11]['mes_ano'])->first();
        if(empty($faturamentoPrevistoFluxoCaixaobj)){
            $faturamentoPrevistoFluxoCaixaobj = new FaturamentoPrevistoFluxoCaixa;
        }
        $faturamentoPrevistoFluxoCaixaobj->mes_ano = $data_tabela[11]['mes_ano'];
        $faturamentoPrevistoFluxoCaixaobj->valor = $data_tabela[11]['valor'];

        $faturamentoPrevistoFluxoCaixaobj->faturamento_30_dias_porcetagem = 0;
        $faturamentoPrevistoFluxoCaixaobj->faturamento_30_dias_valor = $data_tabela[11]['valor'];


        $faturamentoPrevistoFluxoCaixaobj->created_by = 1;
        $faturamentoPrevistoFluxoCaixaobj->save();

        $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()->where('mes_ano',  $data_tabela[10]['mes_ano'])->first();
        if(empty($faturamentoPrevistoFluxoCaixaobj)){
            $faturamentoPrevistoFluxoCaixaobj = new FaturamentoPrevistoFluxoCaixa;
        }
        $faturamentoPrevistoFluxoCaixaobj->mes_ano = $data_tabela[10]['mes_ano'];
        $faturamentoPrevistoFluxoCaixaobj->valor = $data_tabela[10]['valor'];

        $faturamentoPrevistoFluxoCaixaobj->faturamento_60_dias_porcetagem = 0;
        $faturamentoPrevistoFluxoCaixaobj->faturamento_60_dias_valor = $data_tabela[10]['valor'];


        $faturamentoPrevistoFluxoCaixaobj->created_by = 1;
        $faturamentoPrevistoFluxoCaixaobj->save();

        $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()->where('mes_ano',  $data_tabela[9]['mes_ano'])->first();
        if(empty($faturamentoPrevistoFluxoCaixaobj)){
            $faturamentoPrevistoFluxoCaixaobj = new FaturamentoPrevistoFluxoCaixa;
        }
        $faturamentoPrevistoFluxoCaixaobj->mes_ano = $data_tabela[9]['mes_ano'];
        $faturamentoPrevistoFluxoCaixaobj->valor = $data_tabela[9]['valor'];

        $faturamentoPrevistoFluxoCaixaobj->faturamento_60_dias_porcetagem = 0;
        $faturamentoPrevistoFluxoCaixaobj->faturamento_60_dias_valor = $data_tabela[9]['valor'];


        $faturamentoPrevistoFluxoCaixaobj->created_by = 1;
        $faturamentoPrevistoFluxoCaixaobj->save();

    }

    public function somatoriaDados(){
        $faturamentoPrevistoFluxoCaixaObj = FaturamentoPrevistoFluxoCaixa::select()->get();

        $dados = [];
        foreach($faturamentoPrevistoFluxoCaixaObj as $value){
            $dados[$value->mes_ano->format('Y-m-d')] = [
                "valor" => $value->valor,
                "faturamento_previstos_valor" => $value->faturamento_previstos_valor,
                "faturamento_30_dias_porcetagem" => $value->faturamento_30_dias_porcetagem,
                "faturamento_30_dias_valor" => $value->faturamento_30_dias_valor,
                "faturamento_60_dias_porcetagem" => $value->faturamento_60_dias_porcetagem,
                "faturamento_60_dias_valor" => $value->faturamento_60_dias_valor,
                "faturamento_90_dias_porcetagem" => $value->faturamento_90_dias_porcetagem,
                "faturamento_90_dias_valor" => $value->faturamento_90_dias_valor,
                "faturamento_120_dias_porcetagem" => $value->faturamento_120_dias_porcetagem,
                "faturamento_120_dias_valor" => $value->faturamento_120_dias_valor,
                "faturamento_150_dias_porcetagem" => $value->faturamento_150_dias_porcetagem,
                "faturamento_150_dias_valor" => $value->faturamento_150_dias_valor,
                "faturamento_180_dias_porcetagem" => $value->faturamento_180_dias_porcetagem,
                "faturamento_180_dias_valor" => $value->faturamento_180_dias_valor,
                "faturamento_210_dias_porcetagem" => $value->faturamento_210_dias_porcetagem,
                "faturamento_210_dias_valor" => $value->faturamento_210_dias_valor,
                "faturamento_240_dias_porcetagem" => $value->faturamento_240_dias_porcetagem,
                "faturamento_240_dias_valor" => $value->faturamento_240_dias_valor,
                "faturamento_270_dias_porcetagem" => $value->faturamento_270_dias_porcetagem,
                "faturamento_270_dias_valor" => $value->faturamento_270_dias_valor,
                "faturamento_300_dias_porcetagem" => $value->faturamento_300_dias_porcetagem,
                "faturamento_300_dias_valor" => $value->faturamento_300_dias_valor,
                "faturamento_330_dias_porcetagem" => $value->faturamento_330_dias_porcetagem,
                "faturamento_330_dias_valor" => $value->faturamento_330_dias_valor,
                "faturamento_360_dias_porcetagem" => $value->faturamento_360_dias_porcetagem,
                "faturamento_360_dias_valor" => $value->faturamento_360_dias_valor,
            ];
        }

        foreach($faturamentoPrevistoFluxoCaixaObj as $value){
            $total = 0;
            $total =  $dados[$value->mes_ano->format('Y-m-d')]['faturamento_30_dias_valor'];
            $data = $value->mes_ano;
            for($i = 1; $i < 11; $i++){
                $data = $data->subMonth();
                if(!empty( $dados[$data->format('Y-m-d')])){
                    switch($i){
                        case 1:
                            $total += $dados[$data->format('Y-m-d')]['faturamento_60_dias_valor'];
                            break;
                        case 2:
                            $total += $dados[$data->format('Y-m-d')]['faturamento_90_dias_valor'];
                            break;
                        case 3:
                            $total += $dados[$data->format('Y-m-d')]['faturamento_120_dias_valor'];
                            break;
                        case 4:
                            $total += $dados[$data->format('Y-m-d')]['faturamento_150_dias_valor'];
                            break;
                        case 5:
                            $total += $dados[$data->format('Y-m-d')]['faturamento_180_dias_valor'];
                            break;
                        case 6:
                            $total += $dados[$data->format('Y-m-d')]['faturamento_210_dias_valor'];
                            break;
                        case 7:
                            $total += $dados[$data->format('Y-m-d')]['faturamento_240_dias_valor'];
                            break;
                        case 8:
                            $total += $dados[$data->format('Y-m-d')]['faturamento_270_dias_valor'];
                            break;
                        case 9:
                            $total += $dados[$data->format('Y-m-d')]['faturamento_300_dias_valor'];
                            break;
                        case 10:
                            $total += $dados[$data->format('Y-m-d')]['faturamento_330_dias_valor'];
                            break;
                        case 11:
                            $total += $dados[$data->format('Y-m-d')]['faturamento_360_dias_valor'];
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

        $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()
            ->whereBetween('mes_ano', [$data_anterior, $data])
            ->get();

        $dados = [];

        foreach($faturamentoPrevistoFluxoCaixaobj as $value){
            $dados[$value->mes_ano->format('Y-m-d')] = [
                "valor" => $value->valor,
                "faturamento_previstos_valor" => $value->faturamento_previstos_valor,
                "faturamento_30_dias_porcetagem" => $value->faturamento_30_dias_porcetagem,
                "faturamento_30_dias_valor" => $value->faturamento_30_dias_valor,
                "faturamento_60_dias_porcetagem" => $value->faturamento_60_dias_porcetagem,
                "faturamento_60_dias_valor" => $value->faturamento_60_dias_valor,
                "faturamento_90_dias_porcetagem" => $value->faturamento_90_dias_porcetagem,
                "faturamento_90_dias_valor" => $value->faturamento_90_dias_valor,
                "faturamento_120_dias_porcetagem" => $value->faturamento_120_dias_porcetagem,
                "faturamento_120_dias_valor" => $value->faturamento_120_dias_valor,
                "faturamento_150_dias_porcetagem" => $value->faturamento_150_dias_porcetagem,
                "faturamento_150_dias_valor" => $value->faturamento_150_dias_valor,
                "faturamento_180_dias_porcetagem" => $value->faturamento_180_dias_porcetagem,
                "faturamento_180_dias_valor" => $value->faturamento_180_dias_valor,
                "faturamento_210_dias_porcetagem" => $value->faturamento_210_dias_porcetagem,
                "faturamento_210_dias_valor" => $value->faturamento_210_dias_valor,
                "faturamento_240_dias_porcetagem" => $value->faturamento_240_dias_porcetagem,
                "faturamento_240_dias_valor" => $value->faturamento_240_dias_valor,
                "faturamento_270_dias_porcetagem" => $value->faturamento_270_dias_porcetagem,
                "faturamento_270_dias_valor" => $value->faturamento_270_dias_valor,
                "faturamento_300_dias_porcetagem" => $value->faturamento_300_dias_porcetagem,
                "faturamento_300_dias_valor" => $value->faturamento_300_dias_valor,
                "faturamento_330_dias_porcetagem" => $value->faturamento_330_dias_porcetagem,
                "faturamento_330_dias_valor" => $value->faturamento_330_dias_valor,
                "faturamento_360_dias_porcetagem" => $value->faturamento_360_dias_porcetagem,
                "faturamento_360_dias_valor" => $value->faturamento_360_dias_valor,
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
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_30_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_30_dias_valor'];
                    break;
                case 1:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_60_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_60_dias_valor'];
                    break;
                case 2:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_90_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_90_dias_valor'];
                    break;
                case 3:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_120_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_120_dias_valor'];
                    break;
                case 4:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_150_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_150_dias_valor'];
                    break;
                case 5:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_180_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_180_dias_valor'];
                    break;
                case 6:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_210_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_210_dias_valor'];
                    break;
                case 7:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_240_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_240_dias_valor'];
                    break;
                case 8:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_270_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_270_dias_valor'];
                    break;
                case 9:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_300_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_300_dias_valor'];
                    break;
                case 10:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_330_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_330_dias_valor'];
                    break;
                case 11:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_360_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_360_dias_valor'];
                    break;
            }

            $retorno[$data->format('m/Y')] = [
                'faturamento_venda' => parserValor($dados[$data->format('Y-m-d')]['faturamento_previstos_valor']),
                'porcetagem_referente_mes_atual' => parserValor($porcetagem)."%",
                'valor_referente_mes_atual' => parserValor($valor),
                'data_string' => $data->format('Ymd'),
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
                    $porcetagem = $divisao_mes_atual['faturamento_30_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_30_dias_valor'];
                    break;
                case 1:
                    $porcetagem = $divisao_mes_atual['faturamento_60_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_60_dias_valor'];
                    break;
                case 2:
                    $porcetagem = $divisao_mes_atual['faturamento_90_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_90_dias_valor'];
                    break;
                case 3:
                    $porcetagem = $divisao_mes_atual['faturamento_120_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_120_dias_valor'];
                    break;
                case 4:
                    $porcetagem = $divisao_mes_atual['faturamento_150_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_150_dias_valor'];
                    break;
                case 5:
                    $porcetagem = $divisao_mes_atual['faturamento_180_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_180_dias_valor'];
                    break;
                case 6:
                    $porcetagem = $divisao_mes_atual['faturamento_210_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_210_dias_valor'];
                    break;
                case 7:
                    $porcetagem = $divisao_mes_atual['faturamento_240_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_240_dias_valor'];
                    break;
                case 8:
                    $porcetagem = $divisao_mes_atual['faturamento_270_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_270_dias_valor'];
                    break;
                case 9:
                    $porcetagem = $divisao_mes_atual['faturamento_300_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_300_dias_valor'];
                    break;
                case 10:
                    $porcetagem = $divisao_mes_atual['faturamento_330_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_330_dias_valor'];
                    break;
                case 11:
                    $porcetagem = $divisao_mes_atual['faturamento_360_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_360_dias_valor'];
                    break;
            }
            $data_tabela[] = [
                'mes_ano' => $data_demostracao->format('m/Y'),
                'porcetagem' => parserValor($porcetagem)."%",
                'valor' =>parserValor($valor),
                'data_string' => $data_demostracao->format('Ymd'),
            ];
            $data_demostracao->addMonth();
        }

        return view('programs.faturamento_previsto_fluxo_caixa.modal.detalhes')->with(
            [
                'retorno' => $retorno, 
                'divisao_retorno' => $divisao_retorno,
                'data_tabela' => $data_tabela,
                'mes_atual' => $mes_atual,
            ]
        );
    }

    public function modalDetalhesComparativo(Request $request){
        $fields = $request->only('ano');

        $data = Carbon::parse($fields['ano']);
        $data_anterior = Carbon::parse($fields['ano'])->subYear()->addMonth();
        $data_demostracao = Carbon::parse($fields['ano']);
        $primeiro_dia_do_mes= Carbon::parse($fields['ano'])->setTime(0,0,0)->firstOfMonth();
        $ultimo_dia_do_mes= Carbon::parse($fields['ano'])->setTime(23,59,59)->lastOfMonth();

        $faturamentoPrevistoFluxoCaixaobj = FaturamentoPrevistoFluxoCaixa::select()
            ->whereBetween('mes_ano', [$data_anterior, $data])
            ->get();

        $dados = [];

        foreach($faturamentoPrevistoFluxoCaixaobj as $value){
            $dados[$value->mes_ano->format('Y-m-d')] = [
                "valor" => $value->valor,
                "faturamento_previstos_valor" => $value->faturamento_previstos_valor,
                "faturamento_30_dias_porcetagem" => $value->faturamento_30_dias_porcetagem,
                "faturamento_30_dias_valor" => $value->faturamento_30_dias_valor,
                "faturamento_60_dias_porcetagem" => $value->faturamento_60_dias_porcetagem,
                "faturamento_60_dias_valor" => $value->faturamento_60_dias_valor,
                "faturamento_90_dias_porcetagem" => $value->faturamento_90_dias_porcetagem,
                "faturamento_90_dias_valor" => $value->faturamento_90_dias_valor,
                "faturamento_120_dias_porcetagem" => $value->faturamento_120_dias_porcetagem,
                "faturamento_120_dias_valor" => $value->faturamento_120_dias_valor,
                "faturamento_150_dias_porcetagem" => $value->faturamento_150_dias_porcetagem,
                "faturamento_150_dias_valor" => $value->faturamento_150_dias_valor,
                "faturamento_180_dias_porcetagem" => $value->faturamento_180_dias_porcetagem,
                "faturamento_180_dias_valor" => $value->faturamento_180_dias_valor,
                "faturamento_210_dias_porcetagem" => $value->faturamento_210_dias_porcetagem,
                "faturamento_210_dias_valor" => $value->faturamento_210_dias_valor,
                "faturamento_240_dias_porcetagem" => $value->faturamento_240_dias_porcetagem,
                "faturamento_240_dias_valor" => $value->faturamento_240_dias_valor,
                "faturamento_270_dias_porcetagem" => $value->faturamento_270_dias_porcetagem,
                "faturamento_270_dias_valor" => $value->faturamento_270_dias_valor,
                "faturamento_300_dias_porcetagem" => $value->faturamento_300_dias_porcetagem,
                "faturamento_300_dias_valor" => $value->faturamento_300_dias_valor,
                "faturamento_330_dias_porcetagem" => $value->faturamento_330_dias_porcetagem,
                "faturamento_330_dias_valor" => $value->faturamento_330_dias_valor,
                "faturamento_360_dias_porcetagem" => $value->faturamento_360_dias_porcetagem,
                "faturamento_360_dias_valor" => $value->faturamento_360_dias_valor,
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
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_30_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_30_dias_valor'];
                    break;
                case 1:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_60_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_60_dias_valor'];
                    break;
                case 2:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_90_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_90_dias_valor'];
                    break;
                case 3:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_120_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_120_dias_valor'];
                    break;
                case 4:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_150_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_150_dias_valor'];
                    break;
                case 5:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_180_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_180_dias_valor'];
                    break;
                case 6:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_210_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_210_dias_valor'];
                    break;
                case 7:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_240_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_240_dias_valor'];
                    break;
                case 8:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_270_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_270_dias_valor'];
                    break;
                case 9:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_300_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_300_dias_valor'];
                    break;
                case 10:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_330_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_330_dias_valor'];
                    break;
                case 11:
                    $porcetagem = $dados[$data->format('Y-m-d')]['faturamento_360_dias_porcetagem'];
                    $valor = $dados[$data->format('Y-m-d')]['faturamento_360_dias_valor'];
                    break;
            }

            $retorno[$data->format('m/Y')] = [
                'faturamento_venda' => $dados[$data->format('Y-m-d')]['faturamento_previstos_valor'],
                'porcetagem_referente_mes_atual' => $porcetagem,
                'valor_referente_mes_atual' => $valor,
                'venda_realizada' => 0,
                'porcetagem_referente_mes_atual_realizada' => 0,
                'valor_referente_mes_atual_realizada' => 0,
                'diferenca' => 0,
                'ordenacao' => $data->format('Y-m-d'),
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
                    $porcetagem = $divisao_mes_atual['faturamento_30_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_30_dias_valor'];
                    break;
                case 1:
                    $porcetagem = $divisao_mes_atual['faturamento_60_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_60_dias_valor'];
                    break;
                case 2:
                    $porcetagem = $divisao_mes_atual['faturamento_90_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_90_dias_valor'];
                    break;
                case 3:
                    $porcetagem = $divisao_mes_atual['faturamento_120_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_120_dias_valor'];
                    break;
                case 4:
                    $porcetagem = $divisao_mes_atual['faturamento_150_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_150_dias_valor'];
                    break;
                case 5:
                    $porcetagem = $divisao_mes_atual['faturamento_180_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_180_dias_valor'];
                    break;
                case 6:
                    $porcetagem = $divisao_mes_atual['faturamento_210_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_210_dias_valor'];
                    break;
                case 7:
                    $porcetagem = $divisao_mes_atual['faturamento_240_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_240_dias_valor'];
                    break;
                case 8:
                    $porcetagem = $divisao_mes_atual['faturamento_270_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_270_dias_valor'];
                    break;
                case 9:
                    $porcetagem = $divisao_mes_atual['faturamento_300_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_300_dias_valor'];
                    break;
                case 10:
                    $porcetagem = $divisao_mes_atual['faturamento_330_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_330_dias_valor'];
                    break;
                case 11:
                    $porcetagem = $divisao_mes_atual['faturamento_360_dias_porcetagem'];
                    $valor = $divisao_mes_atual['faturamento_360_dias_valor'];
                    break;
            }
            $data_tabela[] = [
                'mes_ano' => $data_demostracao->format('m/Y'),
                'porcetagem' => parserValor($porcetagem)."%",
                'valor' =>parserValor($valor),
            ];
            $data_demostracao->addMonth();
        }

        $cnpj = $this->cnpjINtercompany();
        $query_faturamento_caixa = TitulosEmAbertoNasajon::select('titulo_emissao', DB::Raw("sum(saldotitulo) as total"));
        $query_faturamento_caixa->whereBetween('vencimento', [$primeiro_dia_do_mes, $ultimo_dia_do_mes])
            ->where('saldotitulo', '>', '0')
            ->whereNotIn("codigo", ['25', 'TREINAMENTO'])
            ->whereNotIn("cod_cliente", $cnpj);
        $query_faturamento_caixa->groupBy('titulo_emissao');

        $query_faturamento_caixa_recebido = TitulosPagosNasajon::select('emissao', DB::Raw("sum(valor_titulo) as total"));
        $query_faturamento_caixa_recebido->whereBetween('vencimento', [$primeiro_dia_do_mes, $ultimo_dia_do_mes])
            ->where('valor_titulo', '>', '0')
                ->whereNotIn("codigo", ['25', 'TREINAMENTO'])
            ->whereNotIn("cod_cliente", $cnpj);
        $query_faturamento_caixa_recebido->groupBy('emissao');

        $result_faturamento_caixa = $query_faturamento_caixa->get();

        $result_faturamento_caixa_recebido = $query_faturamento_caixa_recebido->get();

        foreach($result_faturamento_caixa as $value){
            $data_titulo = Carbon::parse($value->titulo_emissao);
            $primeiro_dia = Carbon::parse($value->titulo_emissao)->setTime(0,0,0)->firstOfMonth();
            $ultimo_dia = Carbon::parse($value->titulo_emissao)->setTime(23,59,59)->lastOfMonth();
            
            if(empty($retorno[$data_titulo->format('m/Y')])){
                $retorno[$data_titulo->format('m/Y')] = [
                    'faturamento_venda' => 0,
                    'porcetagem_referente_mes_atual' => 0,
                    'valor_referente_mes_atual' => 0,
                    'venda_realizada' => 0,
                    'porcetagem_referente_mes_atual_realizada' => 0,
                    'valor_referente_mes_atual_realizada' => $value->total,
                    'diferenca' => 0,
                    'ordenacao' => $data_titulo->format('Y-m-d'),
                ];
            }else{
                $retorno[$data_titulo->format('m/Y')]['valor_referente_mes_atual_realizada'] += $value->total;
            }

            if(empty($retorno[$data_titulo->format('m/Y')]['venda_realizada'])){
                $query_faturamento_realizado = FaturamentoOnline::select(DB::Raw("SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) - SUM( valor_troco ) as total"));
                $query_faturamento_realizado->whereBetween('data', [$primeiro_dia, $ultimo_dia]);
                $query_faturamento_realizado->where(function ($query) {
                    $query->orWhere("tipo_operacao", "ilike", "VENDA%");
                    $query->orWhere("tipo_operacao", "ilike", "SIMPLESFATFUTURA");
                    $query->orWhere("tipo_operacao", "ilike", "DEV%");
                });
                $query_faturamento_realizado->where("nasajon", true);
                $result_faturamento_realizado = $query_faturamento_realizado->first();

                $retorno[$data_titulo->format('m/Y')]['venda_realizada'] = empty($result_faturamento_realizado->total)? 0 : $result_faturamento_realizado->total;
            }
        }

        foreach($result_faturamento_caixa_recebido as $value){
            $data_titulo = Carbon::parse($value->emissao);
            $primeiro_dia = Carbon::parse($value->emissao)->setTime(0,0,0)->firstOfMonth();
            $ultimo_dia = Carbon::parse($value->emissao)->setTime(23,59,59)->lastOfMonth();
          
            if(empty($retorno[$data_titulo->format('m/Y')])){
                $retorno[$data_titulo->format('m/Y')] = [
                    'faturamento_venda' => 0,
                    'porcetagem_referente_mes_atual' => 0,
                    'valor_referente_mes_atual' => 0,
                    'venda_realizada' => 0,
                    'porcetagem_referente_mes_atual_realizada' => 0,
                    'valor_referente_mes_atual_realizada' => $value->total,
                    'diferenca' => 0,
                    'ordenacao' => $data_titulo->format('Y-m-d'),
                ];
            }else{
                $retorno[$data_titulo->format('m/Y')]['valor_referente_mes_atual_realizada'] += $value->total;
            }

            if(empty($retorno[$data_titulo->format('m/Y')]['venda_realizada'])){
                $query_faturamento_realizado = FaturamentoOnline::select(DB::Raw("SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) - SUM( valor_troco ) as total"));
                $query_faturamento_realizado->whereBetween('data', [$primeiro_dia, $ultimo_dia]);
                $query_faturamento_realizado->where(function ($query) {
                    $query->orWhere("tipo_operacao", "ilike", "VENDA%");
                    $query->orWhere("tipo_operacao", "ilike", "SIMPLESFATFUTURA");
                    $query->orWhere("tipo_operacao", "ilike", "DEV%");
                });
                $query_faturamento_realizado->where("nasajon", true);
                $result_faturamento_realizado = $query_faturamento_realizado->first();

                $retorno[$data_titulo->format('m/Y')]['venda_realizada'] = empty($result_faturamento_realizado->total)? 0 : $result_faturamento_realizado->total;
            }
        }

        $query_aberto = PedidosPrePago::select();
        $query_aberto->whereBetween('created_at', [$primeiro_dia_do_mes, $ultimo_dia_do_mes]);
        $result_aberto = $query_aberto->get();

        foreach ($result_aberto as $index => $valor) {
            $data_mes = Carbon::parse($valor->created_at);

            $retorno[$data_mes->format('m/Y')]['venda_realizada'] +=  $valor->valor_pago;
        }

        $total = [
            'realizado_venda' => 0,
        ];

        foreach($retorno as $index => $value){
            $retorno[$index]['porcetagem_referente_mes_atual_realizada'] = empty($value['venda_realizada'])? '' : parserValor(($value['valor_referente_mes_atual_realizada'] / $value['venda_realizada']) * 100) . "%";
            $retorno[$index]['diferenca'] =  empty($value['valor_referente_mes_atual'])? '' : parserValor((($value['valor_referente_mes_atual_realizada'] / $value['valor_referente_mes_atual']) - 1) * 100) . "%";
            
            $total['realizado_venda'] +=  $retorno[$index]['valor_referente_mes_atual_realizada'];

            $retorno[$index]['faturamento_venda'] = empty($value['faturamento_venda'])? '' : parserValor($retorno[$index]['faturamento_venda']);
            $retorno[$index]['porcetagem_referente_mes_atual'] = empty($value['porcetagem_referente_mes_atual'])? '' : parserValor($retorno[$index]['porcetagem_referente_mes_atual']) . "%";
            $retorno[$index]['valor_referente_mes_atual'] = empty($value['valor_referente_mes_atual'])? '' : parserValor($retorno[$index]['valor_referente_mes_atual']);
            $retorno[$index]['venda_realizada'] = empty($value['venda_realizada'])? '' : parserValor($retorno[$index]['venda_realizada']);
            $retorno[$index]['valor_referente_mes_atual_realizada'] = empty($value['valor_referente_mes_atual_realizada'])? '' : parserValor($retorno[$index]['valor_referente_mes_atual_realizada']);
        }

        return view('programs.faturamento_previsto_fluxo_caixa.modal.detalhes_comparativo')->with(
            [
                'retorno' => $retorno, 
                'divisao_retorno' => $divisao_retorno,
                'data_tabela' => $data_tabela,
                'mes_atual' => $mes_atual,
                'total' => $total
            ]
        );
    }

    private function cnpjINtercompany(){
        $cnpj_excluir[] = '05075884000167';
        $cnpj_excluir[] = '05075884000248';
        $cnpj_excluir[] = '06311274000269';
        $cnpj_excluir[] = '06311274000340';
        $cnpj_excluir[] = '08';
        $cnpj_excluir[] = '07';
        $cnpj_excluir[] = '06311274000501';
        $cnpj_excluir[] = '06311274000420';
        return $cnpj_excluir;
    }
}
