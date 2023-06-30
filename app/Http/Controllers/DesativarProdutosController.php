<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Movimentacao;
use App\ProdutoEspecificacao;
use App\LogProduto;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DesativarProdutosController extends Controller
{
    public function Desativar(){
        ini_set('memory_limmit', '2048M');

        $mailBody = '<table border="1">'.
                    '<thead>'.
                        '<th> Código </th>'.
                        '<th> Descrição </th>'.
                    '</thead>'.    
                    '<tbody>';

        $produtos_desativar = ProdutoEspecificacao::whereDoesntHave('Movimentacao', function (Builder $query){
            $query->whereBetween('data_movimentacao', [Carbon::now()->subMonths(6), Carbon::now()]);
            $query->where('documento', '!=', '');
        })
        ->where(function($query){
            $query->doesntHave('estoque');
            $query->orWhereHas('estoque',function($query){
                $query->where('estoque',0)
                ->where('empenho',0)
                ->where('saldo_fiscal',0)
                ->where('reserva',0)
                ->where('saldo_em_terceiros',0)
                ->where('compras_aberto',0)
                ->where('compras',0)
                ->where('saldo_armazem',0)
                ->where('saldo_movimento_nao_efetivado',0);
            });
        })
        ->whereNotIn('linha',['MAO DE OBRA','INSUMO'])
        ->whereNotIn('produto_grupos_id',[2872, 5770])
        ->select('codigo_produto','descricao','data_ativacao')
        ->where('ativo','true')
        ->where('data_de_cadastro', '<', Carbon::now()->subDays(180))
        ->orderBy('descricao')
        ->get();
        
        foreach($produtos_desativar->chunk(500) as $chunk){
            foreach($chunk as $produto_desativar){
                if(!empty($produto_desativar->data_ativacao)){
                    $data_hoje = Carbon::now();
                    $data_ativacao = Carbon::parse($produto_desativar->data_ativacao);
                    $diferenca = $data_ativacao->diffInDays($data_hoje);
                    if($diferenca < 30){
                        continue;
                    }
                }
                try {  
                    $produto_desativar->ativo = 'false';
                    $produto_desativar->save();    
                    
                    $log_produto = new LogProduto;
                    $log_produto->acao = 'desativar produto';
                    $log_produto->produto = $produto_desativar->codigo_produto;
                    $log_produto->created_by = 1;
                    $log_produto->updated_by = 1;
                    $log_produto->save();

                    $mailBody .= '<tr><td>'.$produto_desativar->codigo_produto.'</td><td>'.$produto_desativar->descricao.'</td></tr>';
                }catch (\QueryException $e) {
                    echo 'Erro na importação: ' . $e->message . ' ' . printf($e->getSql(), $e->getBindings()) . PHP_EOL;
                    DB::rollback();
                    return null;
                }
            }
        }

        $produtos = ProdutoEspecificacao::with(['produtoNasajon'])
        ->select('codigo_produto','descricao')
        ->where('ativo','true')
        ->orderBy('descricao')
        ->get();
   
        foreach($produtos->chunk(500) as $chunk){
            foreach($chunk as $produto){
                if(empty($produto->produtoNasajon)){
                    try {
                        $produto->ativo = 'false';
                        $produto->save();
                        $mailBody .= '<tr><td>'.$produto->codigo_produto.'</td><td>'.$produto->descricao.'</td></tr>';
                    }catch (\QueryException $e) {
                        echo 'Erro na importação: ' . $e->message . ' ' . printf($e->getSql(), $e->getBindings()) . PHP_EOL;
                        DB::rollback();
                        return null;
                    }
                }
            }
        }
            
        $mailBody .= '</tbody>'.
             '</table>';
        $emailControllerObj = new EmailController;
        $emailControllerObj->sendEmailToken('00', 'desativacao_produto', [], ['produto' => $mailBody]);

        $ProdutoEspecificacao = ProdutoEspecificacao::select()->where('ativo', false)->whereIn('produto_grupos_id',[2872, 5770])->get();

        foreach($ProdutoEspecificacao as $produto){
            $produto->ativo = true;
            $produto->save();
        }

        return count($produtos_desativar) + count($produtos);
    }

}