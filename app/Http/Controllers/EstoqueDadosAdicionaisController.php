<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use App\NotasVenda;
use App\ProdutosEstoque;
use App\Movimentacao;

class EstoqueDadosAdicionaisController extends Controller
{
    public $cfop_venda = ['5922', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101', '5104', '6104'];

    public function dadosUltimaVenda(){
        ini_set('memory_limit','2024M');

        $estoques = ProdutosEstoque::select()->get();

        foreach($estoques as $estoque){
            $movimentacaoObj = Movimentacao::select();
            $movimentacaoObj->where('estabelecimento', $estoque->estabelecimento);
            $movimentacaoObj->where('produto_codigo', $estoque->codigo_produto);
            $movimentacaoObj->whereIn('cfop', $this->cfop_venda);

            $maior_data_movimentacao = $movimentacaoObj->max('data_movimentacao');
            
            $maior_data_movimentacao = Carbon::parse($maior_data_movimentacao);

            $produtosEstoqueObj = ProdutosEstoque::select(); 
            $produtosEstoqueObj->where('codigo_produto', $estoque->codigo_produto);
            $produtosEstoqueObj->where('estabelecimento', $estoque->estabelecimento);
            $produtosEstoqueObj->update(['data_ultima_venda' => $maior_data_movimentacao]);

        }
    }

    public function dadosMediaVenda(){
        ini_set('memory_limit','2024M');

        $data_atual = Carbon::now()->setTime(23,59,59)->lastOfMonth();
        $data_anterior = Carbon::now()->setTime(0,0,0)->firstOfMonth();

        $estoques = ProdutosEstoque::select()->get();

        foreach($estoques as $value){
            $data_anterior = Carbon::now()->setTime(0,0,0)->firstOfMonth();
            for($i = 1; $i < 13; $i++){
                $movimentacaoObj = Movimentacao::select();
                $movimentacaoObj->where('estabelecimento', $value->estabelecimento);
                $movimentacaoObj->where('produto_codigo', $value->codigo_produto);
                $movimentacaoObj->whereIn('cfop', $this->cfop_venda);
                $movimentacaoObj->whereBetween('data_movimentacao', [$data_anterior, $data_atual]);
    
                $quantidade = $movimentacaoObj->sum('quantidade');
 
                switch($i){
                    case 1:
                        $campo = "venda_mes_1";
                        break;
                    case 2:
                        $campo = "venda_mes_2";
                        break;
                    case 3:
                        $campo = "venda_mes_3";
                        break;
                    case 4:
                        $campo = "venda_mes_4";
                        break;
                    case 5:
                        $campo = "venda_mes_5";
                        break;
                    case 6:
                        $campo = "venda_mes_6";
                        break;
                    case 7:
                        $campo = "venda_mes_7";
                        break;
                    case 8:
                        $campo = "venda_mes_8";
                        break;
                    case 9:
                        $campo = "venda_mes_9";
                        break;
                    case 10:
                        $campo = "venda_mes_10";
                        break;
                    case 11:
                        $campo = "venda_mes_11";
                        break;
                    case 12:
                        $campo = "venda_mes_12";
                        break;
                }
                
                $produtosEstoqueObj = ProdutosEstoque::select(); 
                $produtosEstoqueObj->where('codigo_produto', $value->codigo_produto);
                $produtosEstoqueObj->where('estabelecimento', $value->estabelecimento);
                $produtosEstoqueObj->update([$campo => $quantidade]);
                
                $data_anterior = $data_anterior->subMonth();
            }
        }
    }
}
