<?php

namespace App\Http\Controllers;

use App\ProdutosSaldoNegativoNasajon;

use Illuminate\Http\Request;

use Carbon\Carbon;

class ProdutosSaldoNegativoNasajonController extends Controller
{
    public function enviaEmail(){

        $inicio =  Carbon::now();

		$EmailObj = new EmailController();

        $produtosSaldoNegativoNasajonObj = ProdutosSaldoNegativoNasajon::with('produtoEspecificacao')->get();

        if($produtosSaldoNegativoNasajonObj->isNotEmpty()){

            $estabelecimentos = returnEmpresasNasajonView();

            $variaveis = [
                'data' => Carbon::now()->format('d/m/Y'),
                'produtos' => ''
            ];
            
            $produtosSaldoNegativoNasajonObj->each(function($produto) use (&$variaveis, $estabelecimentos){

                if(isset($produto->produtoEspecificacao) && !is_null($produto->produtoEspecificacao)){
                    $grupo = $produto->produtoEspecificacao->grupo;
                }
                else{
                    $grupo = '';
                }

                $variaveis['produtos'] .= '<p>
                    <b>Estabelecimento:</b> ' . $estabelecimentos[intval($produto->estabelecimento_codigo)] . '
                    <b>Código:</b> ' . $produto->produto_codigo . '
                    <b>Grupo:</b> ' . $grupo . '
                    <b>Descrição:</b> ' . $produto->produto_descricao . '
                    <b>Saldo:</b> ' . parserValor($produto->saldo) . '
                </p>';
            });

            $returnEmail = $EmailObj->sendEmailToken('00', "email_produtos_saldo_negativo", [], $variaveis);

            echo "Produtos com saldo negativo: " . $produtosSaldoNegativoNasajonObj->count() . PHP_EOL . 'Demorou ' . $inicio->diffForHumans() . PHP_EOL;

        }

    }
}
