<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProdutoEspecificacao;
use App\ProdutosEstoque;
use Carbon\Carbon;

class AtivacaoProdutoController extends Controller
{
    public function ativar(){
        ini_set('memory_limmit', '2048M');

        $produtos = ProdutoEspecificacao::where('ativo','false')
            ->orderBy('descricao')
            ->get();
   
        foreach($produtos->chunk(500) as $chunk){
            foreach($chunk as $produto){
                try {
                    if(!empty($produto->produtoNasajon)){
                        $estoque = ProdutosEstoque::where('codigo_produto', $produto->codigo_produto)
                            ->where(function($query){
                                $query->orWhere('estoque', '>', 0)
                                ->orWhere('empenho', '>', 0)
                                ->orWhere('saldo_fiscal', '>', 0)
                                ->orWhere('reserva', '>', 0)
                                ->orWhere('saldo_em_terceiros', '>', 0)
                                ->orWhere('compras_aberto', '>', 0)
                                ->orWhere('compras', '>', 0)
                                ->orWhere('saldo_armazem', '>', 0)
                                ->orWhere('saldo_movimento_nao_efetivado', '>', 0);
                            })->first();
                        if(!empty($estoque)){
                            $produto->ativo = 'true';
                            $produto->save();
                        }
                    }
                }catch (\QueryException $e) {
                    DB::rollback();
                    return null;
                }
            }
        }

        $produtos = ProdutoEspecificacao::
            with(['produtoNasajon'])
            ->where('ativo','false')
            ->where('data_de_cadastro', '>=', Carbon::now()->subDays(180))
            ->orderBy('descricao')
            ->get();

        foreach($produtos->chunk(500) as $chunk){
            foreach($chunk as $produto){
                try {
                    if(!empty($produto->produtoNasajon)){
                        $produto->ativo = 'true';
                        $produto->save();
                    } 
                }catch (\QueryException $e) {
                    DB::rollback();
                    return null;
                }
            }
        }
        return count($produtos);
    }
}
