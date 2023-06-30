<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

use App\ProdutosSemEstoque;
use App\PedidoPortal;

class ProdutosSemEstoqueController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ProdutosSemEstoque") === false){
            return abort(403);
        }
        
        $request->session()->flash('model', 'App\ProdutosSemEstoque');

        $estabelecimentos = returnEmpresasNasajonView();

    	return view("programs.produtos_sem_estoque.index")->with(['estabelecimento' => $estabelecimentos]);

    }

    public function filter(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $fields = $request->only('estabelecimento', 'data_inicio', 'data_fim','outlet');

        $produtoQuery = ProdutosSemEstoque::with('pedido_detalhes', 'produto_detalhes', 'cliente_detalhes', 'user_detalhe','pedido_item_detalhes');

        if (isset($fields['estabelecimento'])){
            $produtoQuery->whereHas('pedido_detalhes', function ($query) use ($fields){
                $query->where('estabelecimento', $fields['estabelecimento']);
            });
            $produtoQuery->with(['pedido_detalhes' => function ($query) use ($fields){
                $query->where('estabelecimento', $fields['estabelecimento']);
            }]);
        }

        if(isset($fields['data_inicio'])){

            if(isset($fields['data_fim'])){

                $produtoQuery->where(
                    function($query) use ($fields){
                        $query->whereBetween('data_pedido', [$fields['data_inicio'], $fields['data_fim']])
                            ->where('pedido_futuro', false);
                    })
                    ->orWhere(function($query) use ($fields){
                        $query->whereBetween('data_entrega', [$fields['data_inicio'], $fields['data_fim']])
                            ->where('pedido_futuro', true);
                       });
            }

        }

       $produtoQuery->whereHas('pedido_detalhes',
            function($query) use ($fields){
                if(isset($fields['outlet'])){
                $query->where('outlet', true);
                }else{
                    $query->where('outlet', false);
                }
            });
            

        $result = $produtoQuery->get();

        $estabelecimentos = returnEmpresasNasajonView();

        $response = [];
        $chave ='';
        foreach ($result as $value) {
            
            $descricao = $value->produto_detalhes['descricao'];
            $cliente = $value->cliente_detalhes['nome'];

            if(is_null($value->pedido_detalhes)){
                continue;
            }
            $qtd_atendida='';
            if(!empty($value->pedido_item_detalhes)){
               $qtd_atendida= $value->pedido_item_detalhes->quantidade;
            }
            if( $chave != $value->pedido . $value->cod_produto){
                $chave= $value->pedido . $value->cod_produto;
      
           

                $response[] =[

                    'estabelecimento' => $estabelecimentos[$value->pedido_detalhes->estabelecimento],
                    'pedido' => $value->pedido,
                    'codigo' =>$value->cod_produto,
                    'descricao' => "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $descricao . "'>" . $descricao . "</div></div>",
                    'cliente' => "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $cliente . "'>" . $cliente . "</div></div>",
                    'data_pedido' => ($value->data_pedido != '') ? date("d/m/Y", strtotime($value->data_pedido)) : '',
                    'data_entrega' => ($value->data_entrega != '') ? date("d/m/Y", strtotime($value->data_entrega)) : '',
                    'pedido_futuro' => $value->pedido_futuro?'Sim':'Não',
                    'qtd' => parserValor($value->qtd),
                    'qtd_atentida' =>!empty($qtd_atendida) ? parserValor($qtd_atendida):'',
                    'user' => "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $value->user_detalhe['name'] . "'>" . $value->user_detalhe['name'] . "</div></div>",
                    'detalhes_link' => "<a href=# data-url=\"". route('pedido_portal.detalhes') . "\" data-id=\"" . $value->pedido . "\" class=\"bt-view\" data-html='true' title='Ver detalhes do pedido' onclick=\"showModal($(this))\"></a>"
                ];
         
          }
        }

        return response()->json($response, 200);

    }

}
