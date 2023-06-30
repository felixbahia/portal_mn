<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\ContaContabilSaldo;
use App\SaldosContaContabil2Nasajon;

class ContaContabilController extends Controller
{
    public function atualizarSaldo($inicio_periodo, $fim_periodo){
        ini_set('memory_limit','2048M');

        $query_conta_contabil = ContaContabilSaldo::select();
        $query_conta_contabil->whereBetween("data", [$inicio_periodo, $fim_periodo]);
        $query_conta_contabil->delete();

        $query_saldo_conta_contabil = SaldosContaContabil2Nasajon::select();
        $query_saldo_conta_contabil->whereBetween("Data", [$inicio_periodo, $fim_periodo]);
        $result_saldo_conta_contabil = $query_saldo_conta_contabil->get();

        foreach($result_saldo_conta_contabil as $saldo_conta_contabil_nasajon){
            $contaContabilSaldoObj = new ContaContabilSaldo;
            $contaContabilSaldoObj->conta_classificacao = $saldo_conta_contabil_nasajon['Classificação da Conta'];
            $contaContabilSaldoObj->conta = $saldo_conta_contabil_nasajon['Conta'];
            $contaContabilSaldoObj->conta_nome = $saldo_conta_contabil_nasajon['Nome da Conta'];
            $contaContabilSaldoObj->movimentacao_antes_do_encerramento = $saldo_conta_contabil_nasajon['Movimentação (Antes do Enc.)'];
            $contaContabilSaldoObj->movimentacao = $saldo_conta_contabil_nasajon['Movimentação'];
            $contaContabilSaldoObj->saldo_antes_do_encerramento = $saldo_conta_contabil_nasajon['Saldo'];
            $contaContabilSaldoObj->saldo = $saldo_conta_contabil_nasajon['Saldo (Antes do Enc.)'];
            $contaContabilSaldoObj->ano_mes = $saldo_conta_contabil_nasajon['Ano/Mês'];
            $contaContabilSaldoObj->ano = $saldo_conta_contabil_nasajon['Ano'];
            $contaContabilSaldoObj->mes = $saldo_conta_contabil_nasajon['Mês'];
            $contaContabilSaldoObj->data = $saldo_conta_contabil_nasajon['Data'];
            $contaContabilSaldoObj->empresa = $saldo_conta_contabil_nasajon['Empresa'];
            $contaContabilSaldoObj->empresa_razao_social = $saldo_conta_contabil_nasajon['Razão Social da Empresa'];
            $contaContabilSaldoObj->empresa_cnpj = $saldo_conta_contabil_nasajon['CNPJ da Empresa'];
            $contaContabilSaldoObj->estabelecimento_codigo = $saldo_conta_contabil_nasajon['Estabelecimento'];
            $contaContabilSaldoObj->estabelecimento_nome = $saldo_conta_contabil_nasajon['Nome do Estabelecimento'];
            $contaContabilSaldoObj->estabelecimento_cnpj = $saldo_conta_contabil_nasajon['CNPJ do Estabelecimento'];
            $contaContabilSaldoObj->nivel = $saldo_conta_contabil_nasajon['Nível'];
            $contaContabilSaldoObj->created_by = 1;
            $contaContabilSaldoObj->save();
        }
    }
}
