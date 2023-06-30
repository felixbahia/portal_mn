<?php

namespace App\Http\Controllers;

use App\Movimentacao;
use App\ProdutosCusto;
use Carbon\Carbon;

class CalculoCustoArmazemController extends Controller
{

    private $cfop_movimento = ['6905'];


    public function calculoCustoArmazem()
    {
        ini_set('memory_limit', '8200M');
        $movimentacaos = Movimentacao::select()
            ->whereIn('estabelecimento', ['03', '04'])
            ->whereNotIn('cfop', ['3102', '2102', '2201', '2202', '2152'])
            ->whereBetween("data_movimentacao", ['2019-07-07', Carbon::now()])
            ->orderBy('estabelecimento', 'asc')
            ->orderBy('produto_codigo', 'asc')
            ->orderBy('data_movimentacao', 'asc')
            ->orderBy('tipo_operacao', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $saldo_anterior_qtde = 0;
        $saldo_anterior_valor = 0;
        $custo_armazem = 0.0;
        $produto_codigo = '';
        $estabelecimento = '';
        $custo_ultimo = 0.0;

        foreach ($movimentacaos as $movimento) {

            if (($produto_codigo !=  $movimento->produto_codigo) || ($estabelecimento !=  $movimento->estabelecimento)) {

                $this->atualizaProdutoCusto($produto_codigo, $estabelecimento, $custo_armazem,$custo_ultimo, $saldo_anterior_qtde);

                $saldo_anterior_qtde = 0;
                $saldo_anterior_valor = 0;

                $custo_armazem = 0;
                $produto_codigo = $movimento->produto_codigo;
                $estabelecimento = $movimento->estabelecimento;
            }
            if ($movimento->cfop === '6905') {
                if ($movimento->sinal === 'ENTRADA') {

                    if ($custo_armazem  <= 0) {
                        $custo_armazem = $movimento->preco;
                    } else {

                        $custo_armazem =  ($saldo_anterior_valor + ($movimento->quantidade * $movimento->preco)) / ($movimento->quantidade +$saldo_anterior_qtde);
                    }
                    $custo_ultimo=$movimento->preco;
                
                }
            }
         
            $saldo_anterior_qtde = $movimento->saldo_movimentos;
            if($saldo_anterior_qtde >0 && $custo_armazem===0 ){
                $movimento->custo_armazem =$movimento->preco;
            }else{
                $movimento->custo_armazem = $custo_armazem;
            }
           
            $saldo_anterior_valor = $movimento->saldo_movimentos * $custo_armazem;
            $movimento->custo_armazem_saldo = $saldo_anterior_valor;
            $movimento->save();
        }

        $this->atualizaProdutoCusto($produto_codigo, $estabelecimento, $custo_armazem,$custo_ultimo, $saldo_anterior_qtde);
    }

    private function atualizaProdutoCusto($codigo, $estab, $custo_medio,$custo,$saldo)
    {
        if ($codigo != '') {
            if($saldo > 0 && $custo_medio ===0){
                $custo_medio =$custo;
            }

            $produto_custo = ProdutosCusto::select()
                ->where('produto_codigo', $codigo)
                ->where('estabelecimento', $estab)
                ->first();

            if (empty($produto_custo)) {
                $produto_custo = new ProdutosCusto();
                $produto_custo->custo_medio_contabil = 0.0;
                $produto_custo->custo_medio_gerencial = 0.0;
                $produto_custo->data_atualizacao =  Carbon::now();
                $produto_custo->produto_codigo = $codigo;
                $produto_custo->estabelecimento = $estab;
                $produto_custo->custo_medio_armazem = $custo_medio;
                $produto_custo->custo_armazem = $custo;
                $produto_custo->save();
            } else {
                $produto_custo = ProdutosCusto::select()
                    ->where('produto_codigo', $codigo)
                    ->where('estabelecimento', $estab);
                $produto_custo->update(['custo_medio_armazem' => $custo_medio
                ,'custo_armazem' => $custo]);
            }
        }
    }
}
