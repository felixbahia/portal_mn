<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\MovimentacaoPrologos;
use App\ProdutosEstoque;
use App\Preco;
use App\Http\Controllers\EmailController;

class AtualizaUltimaEntradaCustoController extends Controller
{
    public function atualizarDados(){
        ini_set('memory_limit', '2048M');
        $ProdutosEstoqueObj = ProdutosEstoque::with('precos')
            ->select('codigo_produto')
            ->groupBy('codigo_produto')
            ->has('precos')
            ->get();

        $codigos_produtos = $ProdutosEstoqueObj->pluck('codigo_produto')->toArray();

        $codigos_clientes_excluidos = ['0050758840002', '0063112740002', '00507758840001','0063112740003'];

        $MovimentacaoPrologosObj = MovimentacaoPrologos::query()
            ->whereIn('produto_codigo', $codigos_produtos)
            ->whereNotIn('cliente_codigo', $codigos_clientes_excluidos)
            ->where('tipo_operacao', 'ilike', 'EC%')
            ->orderBy('produto_codigo', 'data_movimentacao', 'tipo_operacao', 'id')
            ->get();
        $movimentacao = [];
        foreach($MovimentacaoPrologosObj as $movimento){
            if(!isset($movimentacao[$movimento->produto_codigo])){
                $movimentacao[$movimento->produto_codigo] = 0;
            }
            $movimentacao[$movimento->produto_codigo] = $movimento->novo_custo;
        }
        $dados_atualizados = [];
        foreach($ProdutosEstoqueObj as $estoque){
            if(isset($movimentacao[$estoque->codigo_produto])){
                if($movimentacao[$estoque->codigo_produto] > $estoque->precos[0]->compra_real){
                    $dados_atualizados[] = [
                        'produto_codigo'    => $estoque->codigo_produto,
                        'valor_antigo'      => $estoque->precos[0]->compra_real,
                        'data_antigo'       => $estoque->precos[0]->ultima_compra_real,
                        'valor_novo'        => $movimentacao[$estoque->codigo_produto]
                    ];
                    $estoque->precos[0]->ultima_compra_real = '2019-07-07';
                    $estoque->precos[0]->compra_real = $movimentacao[$estoque->codigo_produto];
                    $estoque->precos[0]->updated_at = date('Y-m-d H:i:s');
                    $estoque->precos[0]->updated_by = '1';
                    $estoque->precos[0]->save();
                } 
            }
        }
        if(!empty($dados_atualizados)){

            $EmailObj = new EmailController();
            $email_send = [];
            $variaveis = [
                'tabela_produtos' => $this->dados_atualizados($dados_atualizados)
            ];
            $returnEmail = $EmailObj->sendEmailToken('00', "altecao_ultimaentrada_custo", $email_send, $variaveis);
            dd($returnEmail);
        }
    }

    private function dados_atualizados($produtos){
        $tabela = '<ul>';
        foreach($produtos as $dado){
            $tabela .= '<li>'.
                '<b>Código do produto: </b>'.$dado['produto_codigo'].
                ' <b>Valor antigo: </b>'.parserValor($dado['valor_antigo']).
                ' <b>Valor novo: </b>'.parserValor($dado['valor_novo']).
                '</li>';
        }
        $tabela .= '</ul>';
        return $tabela;
    }

}
