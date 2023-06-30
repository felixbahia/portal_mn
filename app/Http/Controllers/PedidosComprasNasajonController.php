<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;

use App\AlteracaoDataRecebimentoLog;
use App\ComprasNasajon;
use App\PedidoPortal;
use App\ProdutoEspecificacao;
use App\LancamentoProjeto;
use App\LancamentoProjetoFaccao;
use App\FornecedorNasajon;
use App\UserNajason;
use App\OperacaoNasajon;
use App\HistoricoProjeto;
use App\PedidosComprasNasajon;
use App\AprovacaoDeProjeto;
use App\NecessidadeCompras;
use App\ProdutosEstoque;
use App\CondicoesPagamentoWeb;
use App\HistoricoPedidoCompra;
use App\HistoricoPedidoCompraItem;
use App\PedidoItemPortal;
use App\UnidadeConversaoProdutoNasajon;
use App\FichaTecnicaProduto;
use App\Preco;

use App\Exports\ComprasEmAbertoExport;
use App\Exports\ComprasEmAbertoDialogExport;

use App\Http\Controllers\EmailController;
use App\Http\Controllers\NecessidadeComprasController;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Auth;

class PedidosComprasNasajonController extends Controller
{
    private $margem_preco = 1.43;

    private $servico_minimo_350 = ['MDO014350'];
    private $servico_minimo_500 = ['MDO014500'];

    public function pedidoAberto(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ConsultaPedidosAbertosCompras") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ConsultaPedidosAbertosCompras');
        
        $estabelecimentos = returnEmpresasNasajonView();

        return view('programs.pedidos_compras.pedidos_abertos.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function exibirPedidos(Request $request){
        $filter = $request->only(['fornecedor','estabelecimento']);
        
        return view('programs.pedidos_compras.modal.pedidos')->with(['filter' => $filter]);
    }

    public function filterPedidoAberto(Request $request, $array = false){

        ini_set('max_execution_time', 500);
        ini_set('memory_limit', '2024M');
        $fields = $request->only('estabelecimento', 'pcmn', 'proforma', 'data', 'fornecedor', 'proforma_chk','grupos','porcentagem');
        

        $estabelecimentos = returnEmpresasNasajonView();

        if(!empty($fields['proforma_chk'])){
            if($fields['proforma_chk'] == "false"){
                $fields['proforma_chk'] = null;
            }
        }

        $queryProdutos = ComprasNasajon::with('produto')->
            select('numero_pedido', 'estabelecimento', 'cod_produto', 'id_nota', 'preco_compra_unitario', DB::raw("case when situacao = 'Parcialmente Liquidado' then quantidade_restante else quantidade end as quantidade"), 'previsao_entrega')->
            whereIn('situacao', ['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado']);
        if(!empty($fields['estabelecimento'])){
            $estabelecimento = str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT);
            $queryProdutos->where('estabelecimento', $estabelecimento);
        }
        if(!empty($fields['pcmn'])){
            $queryProdutos->where('numero_pedido', $fields['pcmn']);
        }
        if(!empty($fields['proforma'])){
            $queryProdutos->where('proforma', 'ilike', '%'.$fields['proforma'].'%');
        }
        if(!empty($fields['data'])){
            $queryProdutos->whereBetween('previsao_entrega', ['01/'.$fields['data'], $this->ultimoDiaMes('01/'.$fields['data'])]);
        }
        if(!empty($fields['fornecedor'])){
            $queryProdutos->where('fornecedor_nome', 'ilike', '%'.$fields['fornecedor'].'%');
        }
        $queryProdutos->whereRaw("case
            when situacao = 'Parcialmente Liquidado' then
                quantidade_restante > 0
            else
                quantidade > 0
            end");
        $grupo_nome = '';
        if (!empty($fields['grupos'])){
            $grupo_nome = $fields['grupos'];
        }
        $resultProdutos = $queryProdutos->get();
        $result_chunk = $resultProdutos->chunk(1000);
        $resultFuturo = collect([]);
        $result_chunk->each(function($itens) use (&$resultFuturo, $fields){
            $queryFuturo = PedidoPortal::with('itens_pedido', 'itens_pedido.especificacoes')->select('id','estabelecimento', 'data_previsao_entrega'); 
            $queryFuturo->where('pedido_futuro', 'true');
            $queryFuturo->where('status_pedido', 8);
            if(!empty($fields['estabelecimento'])){
                $estabelecimento = str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT);
                $queryFuturo->where('estabelecimento', $estabelecimento);
            }
            if(!empty($fields['data'])){
                $queryFuturo->whereBetween('data_previsao_entrega', ['01/'.$fields['data'], $this->ultimoDiaMes('01/'.$fields['data'])]);
            }
            $queryFuturo->whereHas('itens_pedido', function($query) use($itens){
                $query->
                    with('especificacoes')->
                    select('pedido','cod_produto', 'quantidade')->
                    whereIn('cod_produto', $itens->pluck('cod_produto'));
            }); 
            $resultFuturo = $resultFuturo->merge($queryFuturo->get());
        });

        $resultFuturo = $resultFuturo->unique();
        $array_quantidade_produto_futuro = [];
        $quantidade_produto_futuro = 0.0;
        foreach($resultFuturo as $pedido){
            $estabelecimento = str_pad($pedido->estabelecimento, 2, '0', STR_PAD_LEFT);
            foreach($pedido->itens_pedido as $itens_pedido){
                if(!isset($array_quantidade_produto_futuro[$itens_pedido->cod_produto][$itens_pedido->numero_compra])){
                    $array_quantidade_produto_futuro[$itens_pedido->cod_produto][$itens_pedido->numero_compra] = 0;
                }
                $array_quantidade_produto_futuro[$itens_pedido->cod_produto][$itens_pedido->numero_compra] += $itens_pedido['quantidade']; 
            }
        }

        $query_especificacao = [];
        $grupo = [];
        $preco_dolar = [];
        $preco_compra = [];
        $quantidade_produto_compras = [];
        $quantidade_produto_vendas = [];

        $resultado_especificacao = collect([]);

        $result_chunk->each(function($itens) use (&$resultado_especificacao, &$grupo_nome, $fields){
            $resultado_especificacao = $resultado_especificacao->merge(ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['preco', 'produtoGrupo'])->whereIn('codigo_produto',$itens->pluck('cod_produto'))->where(function($query) use($grupo_nome, $fields){
                if( !empty($grupo_nome)){
                   
                    $query->where('produto_grupos.descricao',$grupo_nome); 
             
              
                }
                if(!empty($fields['proforma_chk'])){
                    $query->whereIn('procedencia', [1,2,6,7]);
                }
            })->get());
        });
        
        $resultado_especificacao = $resultado_especificacao->unique('codigo_produto');

        $grupos = [];
        $quantidade_produto_compras = [];
		$quantidade_produto_vendas = [];
        $teste = [];
        $resultProdutos->each(function($value) use(&$grupos, &$resultado_especificacao, &$quantidade_produto_compras, &$quantidade_produto_vendas, &$array_quantidade_produto_futuro, &$teste, &$preco_dolar, &$preco_compra){
            foreach($resultado_especificacao as $query_especificacao){
                $nota = $value->id_nota;
                $estabelecimento = str_pad($value->estabelecimento, 2, '0', STR_PAD_LEFT);
                if(!empty($query_especificacao)){
                    if($value->cod_produto != $query_especificacao->codigo_produto){
                        continue;
					}
					$grupo = $query_especificacao->produtoGrupo->descricao;
                    $grupos[$nota][$grupo] = $query_especificacao->produtoGrupo->descricao;
                    $preco_dolar[$nota][$grupo] = empty($query_especificacao->preco->preco_dolar)? '':$query_especificacao->preco->preco_dolar;
                    if($value->estabelecimento === '03'){
                        $preco_compra[$nota][$grupo] = $value->preco_compra_unitario;
                    }

                    $compras = floatval($value->quantidade);
                    
					$quantidade_produto_futuro = !isset($array_quantidade_produto_futuro[$query_especificacao->codigo_produto][$value->numero_pedido]) ? 0.0 : $array_quantidade_produto_futuro[$query_especificacao->codigo_produto][$value->numero_pedido];

					$vendas = empty($quantidade_produto_vendas[$nota][$grupo]['valor_real']) ? $quantidade_produto_futuro : $quantidade_produto_vendas[$nota][$grupo]['valor_real']+$quantidade_produto_futuro;
                    if(!isset($quantidade_produto_compras[$nota][$grupo])){
                        $quantidade_produto_compras[$nota][$grupo] = floatval(0);
                    }
                    $quantidade_produto_compras[$nota][$grupo] += $compras;

                    $quantidade_produto_vendas[$nota][$grupo]['valor_real'] = $vendas;
                    if($quantidade_produto_futuro > $value->quantidade){
                        $quantidade_produto_vendas[$nota][$grupo]['limite_compras'] = empty($quantidade_produto_vendas[$nota][$grupo]['limite_compras'])? $value->quantidade : $quantidade_produto_vendas[$nota][$grupo]['limite_compras'] + $value->quantidade;
                    }else{
                        $quantidade_produto_vendas[$nota][$grupo]['limite_compras'] = empty($quantidade_produto_vendas[$nota][$grupo]['limite_compras'])? $quantidade_produto_futuro : $quantidade_produto_vendas[$nota][$grupo]['limite_compras'] + $quantidade_produto_futuro;
                    }
                }else{
                    $grupos[$nota][0] = '';
                    $preco_dolar[$nota][0] = 0.0;
                    if($value->estabelecimento === '03'){
                        $preco_compra[$nota][0] = $value->preco_compra_unitario;
                    }

                    $compras = empty($quantidade_produto_compras[$nota][0]['valor_real'])?floatval($value->quantidade):$quantidade_produto_compras[$nota][0]['valor_real']+floatval($value->quantidade);
                    $quantidade_produto_futuro = empty($array_quantidade_produto_futuro[$value->cod_produto][$estabelecimento])?0.0:$array_quantidade_produto_futuro[$value->cod_produto][$estabelecimento];
                    $vendas = empty($quantidade_produto_vendas[$nota][0])?$quantidade_produto_futuro:$quantidade_produto_vendas[$nota][0]+$quantidade_produto_futuro;
                    
                    $quantidade_produto_compras[$nota][0]['valor_real'] = $compras;
                    $quantidade_produto_vendas[$nota][0] = $vendas;
                    if($quantidade_produto_futuro > $value->quantidade){
                        $quantidade_produto_vendas[$nota][$grupo]['limite_compras'] = empty($quantidade_produto_vendas[$nota][$grupo]['limite_compras'])? $value->quantidade : $quantidade_produto_vendas[$nota][$grupo]['limite_compras'] + $value->quantidade;
                    }else{
                        $quantidade_produto_vendas[$nota][$grupo]['limite_compras'] = empty($quantidade_produto_vendas[$nota][$grupo]['limite_compras'])? $quantidade_produto_futuro : $quantidade_produto_vendas[$nota][$grupo]['limite_compras'] + $quantidade_produto_futuro;
                    }
                }
            };
		});
        
        $queryCompras = ComprasNasajon::select('id_nota','estabelecimento','numero_pedido','previsao_entrega', 'proforma','situacao', 'fornecedor_nome', 'fornecedor_cnpj');
        $queryCompras->distinct('id_nota');
        $queryCompras->whereIn('situacao', ['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado']);
        if(!empty($fields['estabelecimento'])){
            $estabelecimento = str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT);
            $queryCompras->where('estabelecimento', $estabelecimento);
        }
        if(!empty($fields['pcmn'])){
            $queryCompras->where('numero_pedido', $fields['pcmn']);
        }
        if(!empty($fields['proforma'])){
            $queryCompras->where('proforma', 'ilike', '%'.$fields['proforma'].'%');
        }
        if(!empty($fields['data'])){
            $queryCompras->whereBetween('previsao_entrega', ['01/'.$fields['data'], $this->ultimoDiaMes('01/'.$fields['data'])]);
        }
        if(!empty($fields['fornecedor'])){
            $queryCompras->where('fornecedor_nome', 'ilike', '%'.$fields['fornecedor'].'%');
        }
        if(!empty($fields['proforma_chk'])){
            $queryCompras->whereNotNull('proforma');
        }
        //$queryCompras->orderBy('numero_pedido');
        $resultCompras = $queryCompras->get();
        $retorno =[];

        foreach($resultCompras as $value){
            if (!empty($grupos[$value->id_nota])){
                foreach($grupos[$value->id_nota] as $grupo){
                    $fornecedor = $value->fornecedor_nome . ' - ' . $value->fornecedor_cnpj;
                    if(!empty($grupo)){
						$porcetagem = empty($quantidade_produto_vendas[$value->id_nota][$grupo]['limite_compras'])?'':$quantidade_produto_vendas[$value->id_nota][$grupo]['limite_compras'] / $quantidade_produto_compras[$value->id_nota][$grupo] * 100;
						if($porcetagem > 100){
							$porcetagem = 100;
                        }
                        if (!empty ($fields['porcentagem'])){
                            if($porcetagem >= $fields['porcentagem']){
                                $array['id'] = encrypt($value->id_nota);
                                $array['unidade'] = $estabelecimentos[intval($value->estabelecimento)];
                                $array['pcmn'] = $value->numero_pedido;

                                if(
                                    in_array(Auth::user()->tipo_usuario_id, [1,15]) ||
                                    Auth::user()->hasRole('pcp/produtos/preço') ||
                                    Auth::user()->hasRole('Analise Compras')
                                ){
                                    $array['proforma'] = $value->proforma;
                                    $array['fornecedor'] = $fornecedor;
                                }

                                $array['data_recebimento'] = parserData($value->previsao_entrega);
                                $array['grupo'] = empty($grupo)?'':$grupo;
                                $array['preco_compra'] = empty($preco_compra[$value->id_nota][$grupo])? '': parserValor($preco_compra[$value->id_nota][$grupo]);
                                $array['preco_dolar'] = empty($preco_dolar[$value->id_nota][$grupo])? '':parserValor($preco_dolar[$value->id_nota][$grupo]);
                                $array['qtde_comprada'] = parserValor($quantidade_produto_compras[$value->id_nota][$grupo]);
                                $array['qtde_vendida'] = empty($quantidade_produto_vendas[$value->id_nota][$grupo]['limite_compras'])?'':parserValor($quantidade_produto_vendas[$value->id_nota][$grupo]['limite_compras']);

                                $array['porcetagem'] = empty($porcetagem)?'':parserValor($porcetagem).' %';
                            
                                $retorno[] = $array;
                            }
                        }else{
                            $array['id'] = encrypt($value->id_nota);
                            $array['unidade'] = $estabelecimentos[intval($value->estabelecimento)];
                            $array['pcmn'] = $value->numero_pedido;

                            if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || Auth::user()->hasRole('pcp/produtos/preço') || Auth::user()->hasRole('Analise Compras') || in_array(Auth::id(), [272])){
                                $array['proforma'] = $value->proforma;
                                $array['fornecedor'] = $fornecedor;
                            }

                            $array['data_recebimento'] = parserData($value->previsao_entrega);
                            $array['grupo'] = empty($grupo)?'':$grupo;
                            $array['preco_compra'] = empty($preco_compra[$value->id_nota][$grupo])? '': parserValor($preco_compra[$value->id_nota][$grupo]);
                            $array['preco_dolar'] = empty($preco_dolar[$value->id_nota][$grupo])? '':parserValor($preco_dolar[$value->id_nota][$grupo]);
                            $array['qtde_comprada'] = parserValor($quantidade_produto_compras[$value->id_nota][$grupo]);
                            $array['qtde_vendida'] = empty($quantidade_produto_vendas[$value->id_nota][$grupo]['limite_compras'])?'':parserValor($quantidade_produto_vendas[$value->id_nota][$grupo]['limite_compras']);

                            $array['porcetagem'] = empty($porcetagem)?'':parserValor($porcetagem).' %';
                        
                            $retorno[] = $array; 
                        }
                    }else{
                        if (!empty ($fields['porcentagem'])){
                            if($porcetagem >= $fields['porcentagem']){
                                $porcetagem = empty($quantidade_produto_vendas[$value->id_nota][0]['limite_compras'])? '':$quantidade_produto_vendas[$value->id_nota][0]['limite_compras'] / $quantidade_produto_compras[$value->id_nota][0] * 100;
                                
                                $array['id'] = encrypt($value->id_nota);
                                $array['unidade'] = $estabelecimentos[intval($value->estabelecimento)];
                                $array['pcmn'] = $value->numero_pedido;

                                if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || Auth::user()->hasRole('pcp/produtos/preço') || Auth::user()->hasRole('Analise Compras') || in_array(Auth::id(), [272])){
                                    $array['proforma'] = $value->proforma;
                                    $array['fornecedor'] = $fornecedor;
                                }

                                $array['data_recebimento'] = parserData($value->previsao_entrega);
                                $array['grupo'] = empty($grupo)?'':$grupo;
                                $array['preco_compra'] = empty($preco_compra[$value->id_nota][0])? '': parserValor($preco_compra[$value->id_nota][0]);
                                $array['preco_dolar'] = empty($preco_dolar[$value->id_nota][0])? '':parserValor($preco_dolar[$value->id_nota][0]);
                                $array['qtde_comprada'] = parserValor($quantidade_produto_compras[$value->id_nota][0]);
                                $array['qtde_vendida'] = empty($quantidade_produto_vendas[$value->id_nota][0]['limite_compras'])?'':parserValor($quantidade_produto_vendas[$value->id_nota][0]['limite_compras']);

                                $array['porcetagem'] = empty($porcetagem)?'':parserValor($porcetagem).' %';
                                
                                $retorno[] = $array;
                            }
                        }else{
                            $porcetagem = empty($quantidade_produto_vendas[$value->id_nota][0]['limite_compras'])? '':$quantidade_produto_vendas[$value->id_nota][0]['limite_compras'] / $quantidade_produto_compras[$value->id_nota][0] * 100;
                                
                            $array['id'] = encrypt($value->id_nota);
                            $array['unidade'] = $estabelecimentos[intval($value->estabelecimento)];
                            $array['pcmn'] = $value->numero_pedido;

                            if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || Auth::user()->hasRole('pcp/produtos/preço') || Auth::user()->hasRole('Analise Compras') || in_array(Auth::id(), [272])){
                                $array['proforma'] = $value->proforma;
                                $array['fornecedor'] = $fornecedor;
                            }

                            $array['data_recebimento'] = parserData($value->previsao_entrega);
                            $array['grupo'] = empty($grupo)?'':$grupo;
                            $array['preco_compra'] = empty($preco_compra[$value->id_nota][0])? '': parserValor($preco_compra[$value->id_nota][0]);
                            $array['preco_dolar'] = empty($preco_dolar[$value->id_nota][0])? '':parserValor($preco_dolar[$value->id_nota][0]);
                            $array['qtde_comprada'] = empty($quantidade_produto_compras[$value->id_nota][0])? '' : parserValor($quantidade_produto_compras[$value->id_nota][0]);
                            $array['qtde_vendida'] = empty($quantidade_produto_vendas[$value->id_nota][0]['limite_compras'])?'':parserValor($quantidade_produto_vendas[$value->id_nota][0]['limite_compras']);

                            $array['porcetagem'] = empty($porcetagem)?'':parserValor($porcetagem).' %';
                            
                            $retorno[] = $array;
                        }
                    }
                }
            }
        }
        
        $export_excel = encrypt($retorno);
        $retorno = [
            'export' => $export_excel, 
            'retorno' => $retorno
        ];

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function dialogPedidoAberto(Request $request, $array_itens = false, $cabecalho = false){
        $fields = $request->only('id', 'unidade', 'data');
        $total_venda_limite_compras = 0;

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $estabelecimentos = returnEmpresasNasajonView();

        $queryProdutos = ComprasNasajon::select()
        ->where('id_nota', $id);
        $resultProdutos = $queryProdutos->get();
        $itens = [];
        $total = ['quantidade_recebida' => 0, 'quantidade_saldo' => 0];
        $teste = [];
        foreach($resultProdutos as $value){
			$quantidade_produto_futuro = 0.0;
            if($value->situacao != 'Cancelado'){
                    $queryFuturo = PedidoPortal::select('id','estabelecimento'); 
                    $queryFuturo->where('pedido_futuro', 'true');
                    $queryFuturo->where('status_pedido', 8);
                    $queryFuturo->where('estabelecimento', $value->estabelecimento);
                    $queryFuturo->with(['itens_pedido' => function($query) use($value){
                        $query->select('pedido','cod_produto', 'quantidade');
                        $query->where('cod_produto', $value->cod_produto);
                        $query->where('numero_compra', $value->numero_pedido);
                    }]);
                    $queryFuturo->whereHas('itens_pedido', function($query) use($value){
                        $query->where('cod_produto', $value->cod_produto);
                        $query->where('numero_compra', $value->numero_pedido);
                    });
                $resultFuturo = $queryFuturo->get()->toArray();
                foreach($resultFuturo as $pedido){
                    if(!empty($pedido['itens_pedido'])){
                        $quantidade_produto_futuro += $pedido['itens_pedido'][0]['quantidade']; 
                    }
                }
                if($quantidade_produto_futuro > floatval($value->quantidade)){
                    $quantidade_produto_futuro = floatval($value->quantidade);
                }
            }
            $preco_dolar_valor = 0;

            $query_especificacao = ProdutoEspecificacao::where('codigo_produto',$value->cod_produto)->with(['preco', 'produtoGrupo'])->first();
            if(empty($query_especificacao->preco->preco_dolar) && !empty($query_especificacao->preco->preco_real)){
                $preco_dolar_valor = $query_especificacao->preco->preco_real;
            }else if(!empty($query_especificacao->preco->preco_dolar)){
                $preco_dolar_valor = $query_especificacao->preco->preco_dolar;
            }
            $grupo = empty($query_especificacao)? '' : $query_especificacao->produtoGrupo->descricao;
			$preco_dolar = $preco_dolar_valor;
			$porcetagem = ($value->quantidade > 0) ? $quantidade_produto_futuro / floatval($value->quantidade) * 100 : 0;
            $preco_compra = '';
            if($value->estabelecimento === '03'){
                $preco_compra = $value->preco_compra_unitario;
            }
            if($porcetagem > 100){
                $porcetagem = 100;
                $total_venda_limite_compras += floatval($value->quantidade);
			}else{
                $total_venda_limite_compras += empty($quantidade_produto_futuro)? 0 : $quantidade_produto_futuro;
            }
            $quantidade_saldo = $value->quantidade;
            $quantidade_recebida = 0;
            $quantidade_recebida = ($value->situacao != 'Cancelado') ? ($value->quantidade - $value->quantidade_restante) : 0;
            $total['quantidade_recebida'] += $quantidade_recebida;
            if($value->situacao_item == 'Cancelado'){
                $quantidade_saldo = 0;
            }else{
                $quantidade_saldo = $value->quantidade_restante;
            }
            $total['quantidade_saldo'] += $quantidade_saldo;
            $teste[] = $value->quantidade_restante;
            $itens[] = [
                'cod_produto' => $value->cod_produto,
                'grupo' => empty($grupo) ? '': $grupo,
                'descricao_produto' => $value->descricao_produto,
                'situacao' => $value->situacao,
                'preco_compra' => empty($preco_compra)? parserValor($value->preco_compra_unitario) : parserValor($preco_compra),
                'preco_dolar' => floatval($preco_dolar) > 0 ? parserValor($preco_dolar) : '',
                'quantidade_comprada' => parserQtd(floatval($value->quantidade)),
                'quantidade_vendida' => empty($quantidade_produto_futuro) ? '':parserQtd($quantidade_produto_futuro),
                'porcetagem' => empty($porcetagem)? '':parserValor($porcetagem).'%',
                'quantidade_recebida' => (floatval($quantidade_recebida) > 0  && $value->situacao != 'Cancelado' )? parserQtd($quantidade_recebida) : '',
                'quantidade_saldo' => floatval($quantidade_saldo) > 0 ? parserQtd($quantidade_saldo) : '',
            ]; 
            $array_quantidade_produto_futuro [$value->cod_produto] =  $quantidade_produto_futuro;
		}

        $queryCompras = ComprasNasajon::select('cod_produto','numero_pedido','quantidade')->
            where('id_nota', $id)->
            orderBy('numero_pedido');
        $resultCompras = $queryCompras->get();
        $quantidade_produto_compras = [];
        foreach($resultCompras as $value){
            $compras = empty($quantidade_produto_compras[$value->numero_pedido])?floatval($value->quantidade):$quantidade_produto_compras[$value->numero_pedido]+floatval($value->quantidade);
            $quantidade_produto_futuro = empty($array_quantidade_produto_futuro[$value->cod_produto])?0.0:$array_quantidade_produto_futuro[$value->cod_produto];
            $vendas = empty($quantidade_produto_vendas[$value->numero_pedido])?$quantidade_produto_futuro:$quantidade_produto_vendas[$value->numero_pedido]+$quantidade_produto_futuro;
            
            $quantidade_produto_compras[$value->numero_pedido] = $compras;
            $quantidade_produto_vendas[$value->numero_pedido] = $vendas;
        }

        $queryCompras = ComprasNasajon::select('id_nota','estabelecimento','numero_pedido','situacao','data_entrega','previsao_entrega', 'proforma', 'fornecedor_nome', 'fornecedor_cnpj', 'preco_compra_total');
        $queryCompras->where('id_nota', $id);
        $queryCompras->orderBy('numero_pedido');
        $resultCompras = $queryCompras->first();
        $porcetagem = 0;
        $quantidade_comprada = 0;
        if(isset($quantidade_produto_compras[$resultCompras->numero_pedido])){
            $quantidade_comprada = $quantidade_produto_compras[$resultCompras->numero_pedido];
            $porcetagem = $total_venda_limite_compras/ $quantidade_produto_compras[$resultCompras->numero_pedido] * 100;
        }
		if($porcetagem > 100){
			$porcetagem = 100;
        }
        
        $dados =[
            'id' => encrypt($resultCompras->id_nota),
            'unidade' => $estabelecimentos[intval($resultCompras->estabelecimento)],
            'pcmn' => $resultCompras->numero_pedido,
            'situacao' => $resultCompras->situacao,
            'fornecedor' => $resultCompras->fornecedor_nome . ' - ' . $resultCompras->fornecedor_cnpj,
            'data_previsao_recebimento' => parserData($resultCompras->previsao_entrega),
            'data_recebimento' => (!empty($resultCompras->data_entrega)) ? parserData($resultCompras->data_entrega) : '',
            'qtde_comprada' => parserQtd($quantidade_comprada),
            'qtde_vendida' => empty($quantidade_produto_vendas[$resultCompras->numero_pedido])? '':parserQtd($quantidade_produto_vendas[$resultCompras->numero_pedido]),
            'porcetagem' => empty($porcetagem)? '':parserValor($porcetagem).'%',
            'proforma' => $resultCompras->proforma,
            'quantidade_recebida' => empty($total['quantidade_recebida'])? '' : parserValor($total['quantidade_recebida']),
            'quantidade_saldo' => empty($total['quantidade_saldo'])? '' : parserValor($total['quantidade_saldo']),
            'total_pedido' => empty($resultCompras->preco_compra_total)? '' : parserValor($resultCompras->preco_compra_total),
            'codigo_estabelecimento' => intval($resultCompras->estabelecimento),
        ];

        $export_excel = [
            'dados' => $dados,
            'itens' => $itens
        ];

        $export_excel = encrypt($export_excel);

        return view('programs.pedidos_compras.pedidos_abertos.dialog')->with(['dados' => $dados, 'itens' => $itens, 'export_excel' => $export_excel]);
    }

    function ultimoDiaMes($newData){
        list($newDia, $newMes, $newAno) = explode("/", $newData);
        return date("d/m/Y", mktime(0, 0, 0, $newMes+1, 0, $newAno));
    }

    public function exportarExcel(Request $request){

        return Excel::download(new ComprasEmAbertoExport($request), 'compras_abertos.xlsx');

    }

    public function dadosExportacao(Request $request){
        $fields = $request->only('export_excel');
        try{
            $export_excel = decrypt($fields['export_excel']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        foreach($export_excel as $key => $value){
            $export_excel[$key]['preco_compra'] = empty($export_excel[$key]['preco_compra'])? '' : floatval(str_replace(",", ".", str_replace(".", "", $export_excel[$key]['preco_compra'])));
            $export_excel[$key]['preco_dolar'] = empty($export_excel[$key]['preco_dolar'])? '' : floatval(str_replace(",", ".", str_replace(".", "", $export_excel[$key]['preco_dolar'])));
            $export_excel[$key]['qtde_comprada'] = empty($export_excel[$key]['qtde_comprada'])? '' : floatval(str_replace(",", ".", str_replace(".", "", $export_excel[$key]['qtde_comprada'])));
            $export_excel[$key]['qtde_vendida'] = empty($export_excel[$key]['qtde_vendida'])? '' : floatval(str_replace(",", ".", str_replace(".", "", $export_excel[$key]['qtde_vendida'])));
        }

        return $export_excel;
    }

    public function exportarExcelDialog(Request $request){
        return Excel::download(new ComprasEmAbertoDialogExport($request), 'compras_abertos_itens.xlsx');
    }

    public function dadosExportacaoDialog(Request $request){
        $fields = $request->only('export_excel');
        try{
            $export_excel = decrypt($fields['export_excel']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $itens = $export_excel['itens'];
        $total = $export_excel['dados'];

        foreach($itens as $key => $value){
            $itens[$key]['preco_compra'] = empty($itens[$key]['preco_compra'])? '' : floatval(str_replace(",", ".", str_replace(".", "", $itens[$key]['preco_compra'])));
            $itens[$key]['preco_dolar'] = empty($itens[$key]['preco_dolar'])? '' : floatval(str_replace(",", ".", str_replace(".", "", $itens[$key]['preco_dolar'])));
            $itens[$key]['quantidade_comprada'] = empty($itens[$key]['quantidade_comprada'])? '' : floatval(str_replace(",", ".", str_replace(".", "", $itens[$key]['quantidade_comprada'])));
            $itens[$key]['quantidade_vendida'] = empty($itens[$key]['quantidade_vendida'])? '' : floatval(str_replace(",", ".", str_replace(".", "", $itens[$key]['quantidade_vendida'])));
        }

        $total = [
            "cod_produto" => "",
            "grupo" => "",
            "descricao_produto" => "",
            "preco_compra" => "",
            "preco_dolar" => "Total",
            "quantidade_comprada" => empty($total['qtde_comprada'])? '' : floatval(str_replace(",", ".", str_replace(".", "", $total['qtde_comprada']))),
            "quantidade_vendida" => empty($total['qtde_vendida'])? '' : floatval(str_replace(",", ".", str_replace(".", "", $total['qtde_vendida']))),
            "porcetagem" => empty($total['porcetagem'])? '' : floatval(str_replace(",", ".", str_replace(".", "", $total['porcetagem']))).'%',
        ];

        $itens = array_merge($itens, [$total]);

        return $itens;

    }

    public function dadosExportacaoCabecalhoDialog(Request $request){
        $fields = $request->only('export_excel');
        try{
            $export_excel = decrypt($fields['export_excel']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $dados = $export_excel['dados'];
        if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || Auth::user()->hasRole('pcp/produtos/preço') || Auth::user()->hasRole('Analise Compras') || in_array(Auth::id(), [272])){

            if(empty($dados['proforma'])){                
                $cabecalho = [
                    'Fornecedor',
                    $dados['fornecedor'],
                    'Pedido',
                    $dados['pcmn'],
                    'Data de Recebimento',
                    $dados['data_recebimento'],
                ];
            }else{
                $cabecalho = [
                    'Fornecedor',
                    $dados['fornecedor'],
                    'Pedido',
                    $dados['pcmn'],
                    'Proforma',
                    $dados['proforma'],
                    'Data de Recebimento',
                    $dados['data_recebimento'],
                ];
            }
        }
        else{
               
            $cabecalho = [
                'Pedido',
                $dados['pcmn'],
                'Data de Recebimento',
                $dados['data_recebimento'],
            ];
        }    
        return $cabecalho;

    }

    public function alterarPrevisaoEntregaModal(Request $request){

        $fields = $request->only('id', 'unidade');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $comprasObj = ComprasNasajon::where('id_nota', $id)
        ->where('estabelecimento', substr($fields['unidade'], 0,2))
        ->get();

        if($comprasObj->isNotEmpty()){
            $data = Carbon::parse($comprasObj[0]->previsao_entrega);

            $pedido = collect([
                'estabelecimento' => $comprasObj[0]->estabelecimento,
                'numero_pedido' => $comprasObj[0]->numero_pedido,
                'previsao_entrega' => $data->format('d/m/Y'),
                'id' => $fields['id']
            ]);
        }

        return view('programs.pedidos_compras.pedidos_abertos.modal')->with(['pedido' => $pedido]);
    }

    public function alterarPrevisaoEntrega(Request $request){

        $fields = $request->only('id', 'previsao_entrega');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $comprasObj = ComprasNasajon::where('id_nota', $id)
        ->get();

        if($comprasObj->isNotEmpty()){

            
            $previsao_entrega = Carbon::createFromFormat('d/m/Y', $fields['previsao_entrega']);
            $dataAnterior = Carbon::parse($comprasObj[0]->previsao_entrega);

            if($previsao_entrega->equalTo($dataAnterior) === true){
                return response()->json(
                    ['error' => 
                        ['usr' => 'A data não deve ser a mesma que a atual.']
                ], 422);                
            }

            $log = new AlteracaoDataRecebimentoLog;

            $log->estabelecimento = $comprasObj[0]->estabelecimento;
            $log->numero_pedido = $comprasObj[0]->numero_pedido;
            $log->data_anterior = $comprasObj[0]->previsao_entrega;
            $log->usuario = Auth::id();
            
        }

 
        try {
            $previsao_entrega = Carbon::createFromFormat('d/m/Y', $fields['previsao_entrega']);
            $result = DB::connection('nasajon')->select("SELECT integracoes.api_pedidocompra_alterar( '". $id . "', '". $previsao_entrega->format("Y-m-d") . "')");
        } 
        catch (\QueryException $expection) {
            return response()->json(
                ['error' => 
                    ['usr' => 'Erro na atualização da data']
            ], 422);
        }
        catch(\InvalidArgumentException $expection){
            return response()->json(
                ['error' => 
                    ['usr' => 'Formato inválido de data']
            ], 422);
        }

        $log->data_atual = $previsao_entrega->format("Y-m-d");
        $log->save();

		$EmailObj = new EmailController();
		
        $variaveis = [
            'estabelecimento' => returnEmpresasNasajonView()[intval($comprasObj[0]->estabelecimento)],
            'numero_pedido' => $comprasObj[0]->numero_pedido,
            'grupos' => $comprasObj->pluck('produto')->unique('grupo')->implode('grupo', ', '),
            'data_anterior' => $dataAnterior->format('d/m/Y'),
            'data_atual' => $previsao_entrega->format("d/m/Y"),
        ];
        $returnEmail = $EmailObj->sendEmailToken($comprasObj[0]->estabelecimento, "mudanca_data_recebimento", [], $variaveis);

    }

    public function processoGeracaoPedidoComprasNasajon(LancamentoProjeto $Projeto, $fields){
		$estabelecimento = str_pad($Projeto->estabelecimento, 2, '0', STR_PAD_LEFT);

        $Projeto->status = 4;
        $Projeto->save();
        
        $LancamentoProjetoFaccao = LancamentoProjetoFaccao::select('faccao_id')->where('lancamento_projetos_id', $Projeto->id);
        $faccoes_id = $LancamentoProjetoFaccao->distinct()->get();

        foreach($faccoes_id as $faccao_id){
            $historicoProjetoObj = new HistoricoProjeto();
            $historicoProjetoObj->lancamento_projetos_id = $Projeto->id;
            $historicoProjetoObj->created_by = Auth::id();
            $historicoProjetoObj->users_id = Auth::id();

            $fornecedor_cnpj = $faccao_id->faccao->fornecedor->cnpj_cpf;
            $FornecedorNasajonObj = FornecedorNasajon::where('cnpj_cpf', $fornecedor_cnpj)->first();
            $fornecedor_uuid = "'".$FornecedorNasajonObj->id."'::uuid";

            $cliente_conta_e_ordem_uuid = 'NULL';
    
            $condicao_pagamento = $Projeto['condicoes_pagamento_web'];
            $formapagamento_uuid = "'".$condicao_pagamento['nasajon_forma_pagamento']."'";
            $parcelamento_uuid = "'".$condicao_pagamento['nasajon_parcela']."'";

            $transportadora_uuid = 'NULL';
    
            $transportadora_redespacho_uuid = 'NULL';

            $valor_total = 0;
            $query_produtos = LancamentoProjetoFaccao::select('lancamento_projeto_produtos_id', 'tipo_servico_id', 'quantidade', 'codigo_produto_acabado');
            $query_produtos->where('lancamento_projetos_id', $Projeto->id);
            $query_produtos->where('faccao_id', $faccao_id->faccao_id);
            $result_produtos = $query_produtos->get();
            foreach($result_produtos as $produto){
                if(empty($produto->codigo_produto_acabado)){
                    $codigo_produto = $produto->produto->codigo_produto;                    
                }else{
                    $codigo_produto = $produto->codigo_produto_acabado;
                }

                $preco_base = $produto->tipo_de_servico->preco->preco_real;

                if(empty($valor[$codigo_produto])){
                    $valor[$codigo_produto]['valor_unitario'] = round(($preco_base/$this->margem_preco), 2);
                    $valor[$codigo_produto]['valor_total'] = $produto->quantidade * round(($preco_base/$this->margem_preco), 2);
                }else{
                    $valor[$codigo_produto]['valor_unitario'] += round(($preco_base/$this->margem_preco), 2);
                    $valor[$codigo_produto]['valor_total'] += $produto->quantidade * round(($preco_base/$this->margem_preco), 2);
                }

                $valor_total += $produto->quantidade * round(($preco_base/$this->margem_preco), 2);
            }
            $desconto = floatval(0);

            $tipo_frete = '';
            switch($Projeto->tipo_frete){
                case 'FOB':
                    $tipo_frete = '1';
                    break;
                default:
                    $tipo_frete = '0';
            }

            $usuario_cadastro_uuid = UserNajason::where('nome', 'Mestre')->first()->usuario;

            $operacao = '';
            $codigo_operacao = 'PEDIDOCOMPRA';
    
            $operacao = OperacaoNasajon::where('codigo', $codigo_operacao)->first()->operacao;
            $operacao = "'".$operacao."'";
    
            $observacao = '';
    
            $valor_frete = floatval(empty($Projeto->valor_frete)? 0: $Projeto->valor_frete);
    
            $tipooperacao = '23';
            $numero_pedido_cliente = !empty($Projeto->pedido) ? $Projeto->pedido : '';
            $indicador_pagamento = '1'; // 0 = a vista | 1 = a prazo
            $modo_compra = '2';

            switch(strtoupper($Projeto->cliente->uf)){
                case "SP":
                    $cfop = '1902';
                    break;
                default:
                    $cfop = '2902';
                    break;
            }

            $data_pedido = date('Y-m-d', strtotime($Projeto->data_previsao_entrega));
            
            $observacao_nota = '';
            
            $sql_api_insercao = "select * from integracoes.api_pedidocompranovo (\n".
                "uuid_generate_v4(),\n".
                "current_date,\n".
                "(select estabelecimento from ns.estabelecimentos where codigo = '{$estabelecimento}'),\n".
                "{$fornecedor_uuid},\n".
                "'{$usuario_cadastro_uuid}',\n".
                "'{$observacao}',\n".
                "{$formapagamento_uuid},\n".
                "{$parcelamento_uuid},\n".
                "{$valor_total},\n".
                "{$desconto},\n".
                "{$transportadora_uuid},\n".
                "{$transportadora_redespacho_uuid},\n".
                "{$tipo_frete},\n".
                "{$valor_frete},\n".
                "'{$usuario_cadastro_uuid}',\n".
                "{$indicador_pagamento},\n".
                "'{$cfop}',\n".
                "{$tipooperacao},\n".
                "'{$numero_pedido_cliente}',\n".
                "{$modo_compra},\n".
                "{$cliente_conta_e_ordem_uuid},\n".
                "{$operacao},\n".
                "'{$data_pedido}',\n".
                "'{$observacao_nota}'\n".
            ");";

            try{
                $insert_nasajon = DB::connection('nasajon')->select($sql_api_insercao);
            }catch(\Exception $e){
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                    'error' => [$e, $sql_api_insercao],
                    'response' => []
                ];
            }            

            $mensagem_nasajon = $insert_nasajon[0]->mensagem;
            $mensagem_nasajon = json_decode($mensagem_nasajon, true);
            
            if($mensagem_nasajon['codigo'] !== 'OK'){
                $this->processoErroGeracaoPedidoComprasNasajon($Projeto, $mensagem_nasajon['mensagem'], $sql_api_insercao);
    
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => [$mensagem_nasajon['mensagem'], $sql_api_insercao],
                    'response' => []
                ];
            }
            $pedido_uuid = $mensagem_nasajon['mensagem'];
            $items = LancamentoProjetoFaccao::selectRaw('lancamento_projeto_produtos_id, faccao_id, codigo_produto_acabado, quantidade')
                ->where('lancamento_projetos_id', $Projeto->id)
                ->where('faccao_id', $faccao_id->faccao_id)
                ->groupBy('lancamento_projeto_produtos_id', 'faccao_id', 'codigo_produto_acabado', 'quantidade')
                ->distinct()
                ->get();

            foreach($items as $item){
                if(!empty($item->codigo_produto_acabado)){
                    $produto_uuid = $item->produto_acabado->produtoNasajon->produto;
                    $codigo_produto = $item->codigo_produto_acabado;
                }else{
                    $produto_uuid = $item->produto->produto_detalhes->produtoNasajon->produto;
                    $codigo_produto = $item->produto->codigo_produto;
                }

                
                $quantidade = floatval($item->quantidade);
                $unidade_uuid = DB::connection('nasajon')->table('estoque.produtos')->select('unidadedemedida')->where('produto', $produto_uuid)->first()->unidadedemedida;
                $unidade_uuid = "'".$unidade_uuid."'";
                $produto_uuid = "'".$produto_uuid."'";
    
                $valor_unitario = $valor[$codigo_produto]['valor_unitario'];
    
                $valor_desconto = floatval(0);
                
                $sql_api_insercao_itens = "select * from integracoes.api_pedidocompras_itemnovo (
                    uuid_generate_v4(),
                    '{$pedido_uuid}',
                    {$produto_uuid},
                    {$quantidade},
                    {$unidade_uuid},
                    {$valor_unitario},
                    {$valor_desconto}
                );";
                try{
                    $insercao_produto = DB::connection('nasajon')->select($sql_api_insercao_itens);
                }catch(\Exception $e){
                    return [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                        'error' => $e,
                        'response' => []
                    ];
                }
                $mensagem_nasajon = json_decode($insercao_produto[0]->mensagem, true);
                if($mensagem_nasajon['codigo'] !== 'OK'){
                    $this->processoErroGeracaoPedidoComprasNasajon($Projeto, $mensagem_nasajon['mensagem'], $sql_api_insercao_itens);
                    return [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                        'error' => [$mensagem_nasajon['mensagem']],
                        'response' => []
                    ];
                }
            }
            $sql_api_validacao = "select * from integracoes.api_pedidocompra_habilitar('".$pedido_uuid."')";
            try{
                $insert_nasajon = DB::connection('nasajon')->select($sql_api_validacao);
            }catch(\Exception $e){
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => $e,
                    'response' => []
                ];
            }
            $mensagem_nasajon = json_decode($insert_nasajon[0]->mensagem, true);
            if($mensagem_nasajon['codigo'] !== 'OK'){
                $this->processoErroGeracaoPedidoComprasNasajon($Projeto, $mensagem_nasajon['mensagem'], $sql_api_validacao );
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => [$mensagem_nasajon['mensagem']],
                    'response' => []
                ];
            }

            $historicoProjetoObj->natureza = 'pedido_compras';
            $historicoProjetoObj->motivo = 'Pedido Compras: '.$pedido_uuid;

            try{
                $pedido_compra = ComprasNasajon::select()->where('id_nota', $pedido_uuid)->first()->numero_pedido;
            }catch(\Exception $e){
                $this->processoErroGeracaoPedidoComprasNasajon($Projeto, $e);
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => $e,
                    'response' => []
                ];
            }
            

            $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::select(); 
            $lancamentoProjetoFaccaoObj->where('lancamento_projetos_id', $Projeto->id);
            $lancamentoProjetoFaccaoObj->where('faccao_id', $faccao_id->faccao_id);
            $lancamentoProjetoFaccaoObj->update(['pedido_compras_gerado_nasajon' => $pedido_compra]);
            
            $codigo_pedido = $pedido_uuid;
            $Projeto->pedido_compras_nasajon_gerado = $codigo_pedido;
            $Projeto->status = 4;
    
            $Projeto->save();

            $historicoProjetoObj->save();       
        }

        

        $Projeto->updated_by = Auth::id();
        $Projeto->save();

        $aprovacaoObj = AprovacaoDeProjeto::where('projeto_id', $Projeto->id)->first();

        if(!is_null($aprovacaoObj)){

            $Projeto->save();
            
            $aprovacaoObj->aprovador_id = Auth::id();
            $aprovacaoObj->save();
            $aprovacaoObj->delete();

            $historicoProjetoObj->save();

        }
        else{

            $aprovacaoObj = new AprovacaoDeProjeto;

            $aprovacaoObj->projeto_id = $Projeto->id;
            $aprovacaoObj->aprovador_id = 1;
            $aprovacaoObj->nivel_aprovacao = 1;
            $aprovacaoObj->credito = false;
            $aprovacaoObj->condicao_pagamento = false;
            $aprovacaoObj->preco = false;
            $aprovacaoObj->integracao = false;

            $aprovacaoObj->save();
            $aprovacaoObj->delete();

        }
        return '';
    }
    
    private function processoErroGeracaoPedidoComprasNasajon(LancamentoProjeto $Projeto, $mensagem, $query = ""){
        Log::error('Erro ao gerar pedido compras: '.$mensagem);
		$historicoProjetoObj = new HistoricoProjeto();
		$historicoProjetoObj->lancamento_projetos_id = $Projeto->id;
		$historicoProjetoObj->natureza = 'error_integracao_nasajon';
        $historicoProjetoObj->motivo = 'Erro ao gerar pedido compras: '.$mensagem.' Query: '.$query;
        $historicoProjetoObj->users_id = Auth::id();
		$historicoProjetoObj->created_by = Auth::id();
		$historicoProjetoObj->save();
			
		$Projeto->updated_by = 1;
		$Projeto->updated_at = date("Y-m-d H:i:s");
		$Projeto->save();

		$aprovacaoObj = AprovacaoDeProjeto::where('projeto_id', $Projeto->id)->get()->first();

		if(!is_null($aprovacaoObj)){
			$aprovacaoObj->integracao = true;
			$aprovacaoObj->save();
		}
		else{
			$aprovacaoObj = new AprovacaoDeProjeto;
			$aprovacaoObj->projeto = $Projeto->id;
			$aprovacaoObj->projeto->cod_usuario_autorizador = 1;
			$aprovacaoObj->aprovador_id = 1;
			$aprovacaoObj->nivel_aprovacao = 1;
			$aprovacaoObj->credito = false;
			$aprovacaoObj->condicao_pagamento = false;
			$aprovacaoObj->preco = false;
			$aprovacaoObj->integracao = true;
			$aprovacaoObj->save();
		}

    }
    
    public function alteracaoDataPedidoComprasNasajon($id_projeto, $pedido_uuid, $data_previsao_entrega){
        $sql_api_insercao = "select * from integracoes.api_pedidocompra_alterar(\n".
            "'{$pedido_uuid}'::uuid,\n".
            "'{$data_previsao_entrega}'\n".
            ");";

        try{
            $insert_nasajon = DB::connection('nasajon')->select($sql_api_insercao);
        }catch(\Exception $e){
            $historicoProjetoObj = new HistoricoProjeto();
            $historicoProjetoObj->lancamento_projetos_id = $id_projeto;
            $historicoProjetoObj->natureza = 'integracao_nasajon_alteracao_data';
            $historicoProjetoObj->motivo = 'sql: '.$sql_api_insercao;
            $historicoProjetoObj->users_id = Auth::id();
            $historicoProjetoObj->created_by = Auth::id();
            $historicoProjetoObj->save();
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [$e, $sql_api_insercao],
                'response' => []
            ];
        }
    }

    function modalGeracaoPedidoComprasNecessidades(Request $request){
        $fields = $request->only(['id_necessidade_compras', 'tipo']);

        $tipo = $fields['tipo'];

        try{
            $id_necessidade_compras = decrypt($fields['id_necessidade_compras']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }
        
        $necessidadeComprasObj = NecessidadeCompras::find($id_necessidade_compras);
        $necessidadeComprasControllerObj = new NecessidadeComprasController;
        $preco_servicos = 0;
        $data_previsao_entrega = "";

        if(empty($necessidadeComprasObj->necessidade_x_projetos[0]->tipo === "servico")){
            $codigo_produto = $necessidadeComprasObj->produto_codigo;
            $descricao = $necessidadeComprasObj->produto_detalhes->descricao;
        }else{
           $codigo_produto = empty($necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->tecido)? $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->produto->codigo_produto : $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->codigo_produto_acabado; 
           $descricao = empty($necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->tecido)? $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->produto->descricao : $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->produto_acabado->descricao;
        }
        
        $fichaTecnicaObjeto = '';

        $estoque['estoque'] = 0;
        $estoque['estoque_compras'] = 0;
        $estoque['total'] = 0;

        if($tipo === "servico"){
            $fichaTecnicaObjeto = FichaTecnicaProduto::select();
            $fichaTecnicaObjeto->where('codigo_produto',$codigo_produto);
            $fichaTecnicaObjeto = $fichaTecnicaObjeto->first();

            if(empty($fichaTecnicaObjeto)){
                $fichaTecnicaObjeto = FichaTecnicaProduto::select();
                $fichaTecnicaObjeto->where('codigo_produto',$necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->produto->codigo_produto);
                $fichaTecnicaObjeto = $fichaTecnicaObjeto->first();
            }

            $necessidade   = 1;
            $fator_conversao = 1;
            $total='';
            $unidade_compra = $necessidadeComprasObj->produto_detalhes->unidade;

            $tecidos = [];
            $insumos = [];
            $servicos = [];
        
            $custo_tecidos_total = 0;
            $custo_insumos_total = 0;
            $custo_gerencial_tecidos_total = 0;
            $custo_gerencial_insumos_total = 0;
            $custo_servicos_total = 0;
            $custo_gerencial_servicos_total = 0;
            $preco_compra = 0;

            $fichaTecnicaObjeto->servicos->each(function ($servico) use (&$servicos, &$custo_servicos_total, &$custo_gerencial_servicos_total, &$preco_compra){        
    
                $custo_gerencial = empty($servico->preco->compra_real)? $servico->preco->preco_real / 1.43 : $servico->preco->compra_real;             
                $custo_gerencial_servicos_total += $custo_gerencial;
                $preco_compra = $custo_gerencial_servicos_total;     
            });          
             $necessidade = $necessidadeComprasObj->quantidade;
             $total = $preco_compra * ($necessidade / $fator_conversao);
             $preco_compra = parserValor($preco_compra);
        
                  
        }else if($tipo === "pedido"){
            $fichaTecnicaObjeto = FichaTecnicaProduto::select();
            $fichaTecnicaObjeto->where('codigo_produto',$codigo_produto);
            $fichaTecnicaObjeto = $fichaTecnicaObjeto->first();
            
            $necessidade = $necessidadeComprasObj->quantidade;
            $fator_conversao = 1;
            $unidade_compra = $necessidadeComprasObj->produto_detalhes->unidade;
            $precoObj = Preco::select()->where('codigo_produto', 'MDOES001')->first();
            $preco_compra = empty($precoObj->compra_real)? $precoObj->preco_real / 1.43 : $precoObj->compra_real;
            $total = $preco_compra * ($necessidade / $fator_conversao);
            $preco_compra = parserValor($preco_compra);
        }else{
            $verificar = "";
            if(!empty($verificar)){
                return response()->json([
                    'status' => 'error',
                    'message' => $verificar,
                    'error' => '',
                    'response' => ''
                ],422);
            }
            
            $estoque = $necessidadeComprasControllerObj->estoqueProduto([$necessidadeComprasObj->produto_codigo], "", true);
            $remessas = $necessidadeComprasControllerObj->remessa([$necessidadeComprasObj], true);
            $saldo = $necessidadeComprasControllerObj->saldoNecessidadeCompras($necessidadeComprasObj, $remessas);
            
            $estoque = [
                'estoque' => (!empty($estoque[$necessidadeComprasObj->produto_codigo]['estoque'])) ?  $estoque[$necessidadeComprasObj->produto_codigo]['estoque'] : 0,
                'estoque_compras' => (!empty($estoque[$necessidadeComprasObj->produto_codigo]['estoque_compras'])) ? $estoque[$necessidadeComprasObj->produto_codigo]['estoque_compras'] : 0
            ];
            $estoque['total'] = $estoque['estoque'] + $estoque['estoque_compras'];
            $necessidade = $saldo['saldo']- $estoque['estoque'] - $estoque['estoque_compras'] ;

            $query_compras_nasajon = ComprasNasajon::select();
            $query_compras_nasajon->where('cod_produto', $necessidadeComprasObj->produto_codigo);
            $query_compras_nasajon->orderBy('data_compra', 'desc');
            $result_compras_nasajon = $query_compras_nasajon->first();
            
            if(!empty($result_compras_nasajon)){
                $preco_compra = $result_compras_nasajon->preco_compra_unitario;
                $unidade_compra = $result_compras_nasajon->unidade_comercial;
                if($result_compras_nasajon->unidade_comercial === $necessidadeComprasObj->produto_detalhes->unidade){
                    $fator_conversao = 1;
                }else{
                    $query_unidade_conversao = UnidadeConversaoProdutoNasajon::select();
                    $query_unidade_conversao->where('codigo_unidadepadrao', 'ilike', $necessidadeComprasObj->produto_detalhes->unidade);
                    $query_unidade_conversao->where('codigo_unidadeconversao', 'ilike', $result_compras_nasajon->unidade_comercial);
                    $query_unidade_conversao->where('codigo_produto', $necessidadeComprasObj->produto_codigo);
                    $result_unidade_conversao = $query_unidade_conversao->first();

                    if(empty($result_unidade_conversao)){
                        $fator_conversao = 1;
                    }else{
                        $fator_conversao = $result_unidade_conversao->razao;
                    }
                }

                $total = $preco_compra * ($necessidade / $fator_conversao);
                $preco_compra = parserValor($preco_compra);
                $total = parserValor($total);
                
            }else{
                $preco_compra = '';
                $unidade_compra = $necessidadeComprasObj->produto_detalhes->unidade;
                $fator_conversao = 1;
                $total = '';
            }
        }
        
        if(!empty($necessidadeComprasObj->necessidade_x_projetos[0]->projeto)){
            $data_previsao_entrega = $necessidadeComprasObj->necessidade_x_projetos[0]->projeto->data_previsao_entrega;
        }
        if($tipo !== 'pedido'){
            $fornecedor_cnpj_cpf = $necessidadeComprasObj->fornecedor_cnpj_cpf;
        }else{
            $fornecedor_cnpj_cpf = empty($necessidadeComprasObj->fornecedor_cnpj_cpf)? '' : $necessidadeComprasObj->fornecedor_detalhes->nome.' - '.$necessidadeComprasObj->fornecedor_detalhes->cnpj_cpf;
        }
        $dados = [
            'id_necessidade_compras' => encrypt($id_necessidade_compras),
            'necessidade_compras' => $id_necessidade_compras,
            'numero_projeto' => $necessidadeComprasObj->necessidade_x_projetos[0]->lancamento_projetos_id,
            'nome_projeto' => empty($necessidadeComprasObj->necessidade_x_projetos[0]->projeto)? '' : $necessidadeComprasObj->necessidade_x_projetos[0]->projeto->nome_projeto,
            'numero_pedido' => $necessidadeComprasObj->necessidade_x_projetos[0]->pedido_id,
            'codigo' => $codigo_produto,
            'descricao' => $descricao,
            'unidade' => $necessidadeComprasObj->produto_detalhes->unidade,
            'necessidade' => parserQtd($necessidade),
            'valor_unitario' => $preco_compra,
            'quantidade' => str_replace(".","", parserQtd($necessidade / $fator_conversao)),
            'total' => parserValor($total),
            'fornecedor_cnpj_cpf' => $fornecedor_cnpj_cpf,
            'tipo' => $tipo,
            'estoque' => empty($estoque['estoque'])? '': parserValor($estoque['estoque']),
            'estoque_compras' => empty($estoque['estoque_compras'])? '': parserValor($estoque['estoque_compras']),
            'estoque_total' => empty($estoque['total'])? '': parserValor($estoque['total']),
            'unidade_compra' => $unidade_compra,
            'fator_conversao' => parserValor($fator_conversao),
            'necessidade_compra' => parserQtd($necessidade / $fator_conversao),
            'data_previsao_entrega' => empty($data_previsao_entrega)? '' : parserData($data_previsao_entrega),
            'id_ficha_tecnica' => empty($fichaTecnicaObjeto)?'':$fichaTecnicaObjeto->id,
        ];

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[0]);
        unset($estabelecimentos[1]);
        unset($estabelecimentos[2]);
        unset($estabelecimentos[3]);
        unset($estabelecimentos[7]);
        unset($estabelecimentos[8]);
        unset($estabelecimentos[20]);
        
        if($tipo === "servico"){
            $estabelecimento = $necessidadeComprasObj->necessidade_x_projetos[0]->projeto->estabelecimento;
            $raiz_cnpj = substr($necessidadeComprasObj->necessidade_x_projetos[0]->projeto->cliente->cpf_cnpj,0,10);

            if(!in_array($raiz_cnpj,$this->cnpjIntercompany())){
                $condicao_pagamento = $necessidadeComprasObj->necessidade_x_projetos[0]->projeto->condicoes_pagamento_web->descricao;
            }else{
                $condicao_pagamento = '';
            }

        }else{
            unset($estabelecimentos[6]);
            $estabelecimento = '';
            $condicao_pagamento = '';
        }
        
        $observacao = "CONSTAR ORDEM DE COMPRA NA NOTA FISCAL\n".
                        "\n".
                        "SOLICITAR AGENDAMENTO PARA ENTREGA\n".
                        "\n".
                        "ENVIAR NOTAS FATURADAS PARA: NFEMATRIZ@TECIDOSMN.COM.BR";

        return view('programs.necessidade_compras.modal.pedido_compra')->with(['dados' => $dados, 'estabelecimentos' => $estabelecimentos, 'estabelecimento' => $estabelecimento,'condicao_pagamento' => $condicao_pagamento, "observacao" => $observacao]);
    }

    public function estoqueProduto($codigo_produto, $estabelecimento){
        $estoque_produto = ProdutosEstoque::where('codigo_produto', $codigo_produto)
            ->where('estabelecimento', $estabelecimento)
            ->first();
        if(!empty($estoque_produto)){
            $estoque = $estoque_produto->estoque;
            $estoque_compras = $estoque_produto->compras;
        }else{
            $estoque = 0.0;
            $estoque_compras = 0.0;
        }
        
        $retorno = [
            'estoque' => $estoque,
            'estoque_compras' => $estoque_compras
        ];

        return $retorno;
    }

    public function processoGeracaoPedidoComprasNasajonNecessidadeCompra($dados_pedido, $produtos){
        //Estabelecimento
        $estabelecimento = $dados_pedido['estabelecimento'];

        //Fornecedor
        $fornecedor_cnpj = $dados_pedido['fornecedor_cnpj_cpf'];
        $FornecedorNasajonObj = FornecedorNasajon::where('cnpj_cpf', $fornecedor_cnpj)->first();
        $fornecedor_uuid = "'".$FornecedorNasajonObj->id."'::uuid";

        //Cliente Conta e Ordem
        $cliente_conta_e_ordem_uuid = 'NULL';


        //Condição de Pagamento
        $condicao_pagamento = CondicoesPagamentoWeb::find($dados_pedido['condicao_pagamento']);
        $formapagamento_uuid = "'".$condicao_pagamento->nasajon_forma_pagamento."'";
        $parcelamento_uuid = empty($condicao_pagamento->nasajon_parcela)? NULL : "'".$condicao_pagamento->nasajon_parcela."'";

        //Transportadora
        $transportadora_uuid = 'NULL';

        $transportadora_redespacho_uuid = 'NULL';

        //Valores
        $valor_total = $dados_pedido['valor_total'];

        $desconto = floatval(0);

        $valor_frete = floatval(0);

        //Tipo Frete
        $tipo_frete = '3';

        //Analista de Compras
        $usuario_cadastro_uuid = UserNajason::where('nome', 'Mestre')->first()->usuario;

        //Operação
        $operacao = '';
        $codigo_operacao = 'PEDIDOCOMPRA';

        $operacao = OperacaoNasajon::where('codigo', $codigo_operacao)->first()->operacao;
        $operacao = "'".$operacao."'";

        $observacao = $dados_pedido['observacao'];

        $tipooperacao = '23';
        $numero_pedido_cliente = $dados_pedido['numero_pedido_cliente'];
        $indicador_pagamento = '1'; // 0 = a vista | 1 = a prazo
        $modo_compra = '2';

        //CFOP
        switch(strtoupper($dados_pedido['estabelecimento'])){
            case "05":
            case "06":
                if($dados_pedido['tipo'] == "servico"){
                    $cfop = '1902';
                }else{
                    $cfop = '1101';
                }
                break;
            default:
                if($dados_pedido['tipo'] == "servico"){
                    $cfop = '2902';
                }else{
                    $cfop = '2101';
                }
                break;
        }

        //Data de Entrega
        $data_pedido = $dados_pedido['data_pedido'];
        
        $observacao_nota = $dados_pedido['observacao_nota'];
        
        $sql_api_insercao = "select * from integracoes.api_pedidocompranovo (\n".
            "uuid_generate_v4(),\n".
            "current_date,\n".
            "(select estabelecimento from ns.estabelecimentos where codigo = '{$estabelecimento}'),\n".
            "{$fornecedor_uuid},\n".
            "'{$usuario_cadastro_uuid}',\n".
            "'{$observacao}',\n".
            "{$formapagamento_uuid},\n".
            "{$parcelamento_uuid},\n".
            "{$valor_total},\n".
            "{$desconto},\n".
            "{$transportadora_uuid},\n".
            "{$transportadora_redespacho_uuid},\n".
            "{$tipo_frete},\n".
            "{$valor_frete},\n".
            "'{$usuario_cadastro_uuid}',\n".
            "{$indicador_pagamento},\n".
            "'{$cfop}',\n".
            "{$tipooperacao},\n".
            "'{$numero_pedido_cliente}',\n".
            "{$modo_compra},\n".
            "{$cliente_conta_e_ordem_uuid},\n".
            "{$operacao},\n".
            "'{$data_pedido}',\n".
            "'{$observacao_nota}'\n".
        ");";

        try{
            $insert_nasajon = DB::connection('nasajon')->select($sql_api_insercao);
        }catch(\Exception $e){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$e, $sql_api_insercao],
                'response' => []
            ];
        }            
        
        $mensagem_nasajon = $insert_nasajon[0]->mensagem;
        $mensagem_nasajon = json_decode($mensagem_nasajon, true);
        
        if($mensagem_nasajon['codigo'] !== 'OK'){
            $this->processoErroGeracaoPedidoComprasNasajonNecessidadeCompras($dados_pedido['id_projeto'], $mensagem_nasajon['mensagem'], $sql_api_insercao);

            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [$mensagem_nasajon['mensagem'], $sql_api_insercao],
                'response' => []
            ];
        }

        $pedido_uuid = $mensagem_nasajon['mensagem'];
        $arr_historico['id_projeto'] = $dados_pedido['id_projeto'];
        $arr_historico['pedido_compra_uuid'] = $pedido_uuid;
        $arr_historico['pedido_compra_numero'] = '';
        $arr_historico['tipo'] = $dados_pedido['tipo'];
        $arr_historico['estabelecimento_codigo'] = $estabelecimento;
        $arr_historico['fornecedor_cnpj_cpf'] = $dados_pedido['fornecedor_cnpj_cpf'];
        $arr_historico['condicoes_pagamento_web_id'] = $dados_pedido['condicao_pagamento'];
        $arr_historico['forma_pagamento_uuid'] = $formapagamento_uuid;
        $arr_historico['parcelamento_uuid'] = $parcelamento_uuid;
        $arr_historico['indicador_pagamento'] = $indicador_pagamento;
        $arr_historico['cfop'] = $cfop;
        $arr_historico['tipo_operacao'] = $tipooperacao;
        $arr_historico['modo_compra'] = $modo_compra;
        $arr_historico['data_entrega'] = $data_pedido;
        $arr_historico['valor_total'] = $valor_total;

        $id_historicos_pedidos_compras = $this->adicionarHistoricoPedidoCompra($arr_historico);

        foreach($produtos as $produto){
            $produto_uuid = "'".$produto['produto_uuid']."'";
            $quantidade = floatval($produto['quantidade_pedido']);
            $unidade_uuid = DB::connection('nasajon')->table('estoque.produtos')->select('unidadedemedida')->where('produto', $produto['produto_uuid'])->first()->unidadedemedida;
            $unidade_uuid = "'".$unidade_uuid."'";

            $valor_unitario = floatval($produto['valor_unitario_pedido']);

            $valor_desconto = floatval(0);
            
            $sql_api_insercao_itens = "select * from integracoes.api_pedidocompras_itemnovo (
                uuid_generate_v4(),
                '{$pedido_uuid}',
                {$produto_uuid},
                {$quantidade},
                {$unidade_uuid},
                {$valor_unitario},
                {$valor_desconto}
            );";
            try{
                $insercao_produto = DB::connection('nasajon')->select($sql_api_insercao_itens);
            }catch(\Exception $e){
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => $e,
                    'response' => []
                ];
            }
            $mensagem_nasajon = json_decode($insercao_produto[0]->mensagem, true);
            if($mensagem_nasajon['codigo'] !== 'OK'){
                $this->processoErroGeracaoPedidoComprasNasajonNecessidadeCompras($dados_pedido['id_projeto'], $mensagem_nasajon['mensagem'], $sql_api_insercao_itens);
                return [
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => [$mensagem_nasajon['mensagem']],
                    'response' => []
                ];
            }
            
            $pedido_item_uuid = $mensagem_nasajon['mensagem'];

            $arr_produto['id_historicos_pedidos_compras'] = $id_historicos_pedidos_compras;
            $arr_produto['id_necessidades_compras'] = $produto['id_necessidades_compras'];
            $arr_produto['id_lancamento_projeto_produtos'] = $produto['lancamento_projeto_produtos_id'];
            $arr_produto['lancamento_projeto_tecidos_id'] = $produto['lancamento_projeto_tecidos_id'];
            $arr_produto['pedido_compra_item_uuid'] = $pedido_item_uuid;
            $arr_produto['produto_codigo'] = $produto['codigo'];
            $arr_produto['produto_unidade'] = $produto['produto_unidade'];
            $arr_produto['valor_unitario'] = $produto['valor_unitario_pedido'];
            $arr_produto['valor_unitario_original'] = $produto['valor_unitario_original'];
            $arr_produto['quantidade'] = $produto['quantidade_pedido'];
            $arr_produto['quantidade_original'] = $produto['quantidade_original'];
            $arr_produto['valor_total'] = $valor_unitario * $quantidade;
            $arr_produto['valor_total_original'] = $produto['valor_total_original'];

            $this->adicionarHistoricoPedidoCompraItem($arr_produto);

            $pedidoItemPortalObj = PedidoItemPortal::select();
            $pedidoItemPortalObj->where('pedido', $dados_pedido['id_projeto']);
            $pedidoItemPortalObj->where('cod_produto', $produto['codigo']);
            $pedidoItemPortalObj->whereNull('pedido_compras_uuid_nasajon');
            $pedidoItemPortalObj = $pedidoItemPortalObj->first();
            if(!empty($pedidoItemPortalObj)){
                $pedidoItemPortalObj->pedido_compras_uuid_nasajon = $pedido_uuid; 
                $pedidoItemPortalObj->save();
            }
        }
        $sql_api_validacao = "select * from integracoes.api_pedidocompra_habilitar('".$pedido_uuid."')";
        try{
            $insert_nasajon = DB::connection('nasajon')->select($sql_api_validacao);
        }catch(\Exception $e){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => $e,
                'response' => []
            ];
        }
        $mensagem_nasajon = json_decode($insert_nasajon[0]->mensagem, true);
        if($mensagem_nasajon['codigo'] !== 'OK'){
            $this->processoErroGeracaoPedidoComprasNasajonNecessidadeCompras($dados_pedido['id_projeto'], $mensagem_nasajon['mensagem'], $sql_api_validacao);
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => [$mensagem_nasajon['mensagem']],
                'response' => []
            ];
        }

        try{
            $pedido_compra = ComprasNasajon::select()->where('id_nota', $pedido_uuid)->first()->numero_pedido;
        }catch(\Exception $e){
            $this->processoErroGeracaoPedidoComprasNasajonNecessidadeCompras($dados_pedido['id_projeto'], $e);
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                'error' => $e,
                'response' => []
            ];
        }

        if(!empty($dados_pedido['id_projeto']) && $dados_pedido['tipo'] !== 'pedido'){
            $this->atualizacaoPedidoProgramadoEProjetoFaccao($dados_pedido['id_projeto'], $pedido_uuid, $pedido_compra, $produtos);
        }

        return '';
    }

    public function adicionarHistoricoPedidoCompra($arr_historico){
        $historicoPedidoCompraObj = new HistoricoPedidoCompra;
        if(!empty($arr_historico['id_projeto'])){
            if($arr_historico['tipo'] === 'pedido'){
                $historicoPedidoCompraObj->pedido_id = $arr_historico['id_projeto'];
            }else{
                $historicoPedidoCompraObj->lancamento_projetos_id = $arr_historico['id_projeto'];
            }
        }
        $historicoPedidoCompraObj->pedido_compra_uuid = $arr_historico['pedido_compra_uuid'];
        if(!empty($arr_historico['pedido_compra_numero'])){
            $historicoPedidoCompraObj->pedido_compra_numero = $arr_historico['pedido_compra_numero'];
        }
        $historicoPedidoCompraObj->tipo = $arr_historico['tipo'];
        $historicoPedidoCompraObj->estabelecimento_codigo = $arr_historico['estabelecimento_codigo'];
        $historicoPedidoCompraObj->fornecedor_cnpj_cpf = $arr_historico['fornecedor_cnpj_cpf'];
        $historicoPedidoCompraObj->condicoes_pagamento_web_id = $arr_historico['condicoes_pagamento_web_id'];
        $historicoPedidoCompraObj->forma_pagamento_uuid = $arr_historico['forma_pagamento_uuid'];
        $historicoPedidoCompraObj->parcelamento_uuid = $arr_historico['parcelamento_uuid'];
        $historicoPedidoCompraObj->indicador_pagamento = $arr_historico['indicador_pagamento'];
        $historicoPedidoCompraObj->cfop = $arr_historico['cfop'];
        $historicoPedidoCompraObj->tipo_operacao = $arr_historico['tipo_operacao'];
        $historicoPedidoCompraObj->modo_compra = $arr_historico['modo_compra'];
        $historicoPedidoCompraObj->data_entrega = $arr_historico['data_entrega'];
        $historicoPedidoCompraObj->valor_total = $arr_historico['valor_total'];
        $historicoPedidoCompraObj->created_by = Auth::id();
        $historicoPedidoCompraObj->save();

        return $historicoPedidoCompraObj->id; 
    }

    public function adicionarHistoricoPedidoCompraItem($arr_produto){
        $historicoPedidoCompraItemObj = new HistoricoPedidoCompraItem;
        $historicoPedidoCompraItemObj->historicos_pedidos_compras_id = $arr_produto['id_historicos_pedidos_compras'];
        $historicoPedidoCompraItemObj->necessidades_compras_id = $arr_produto['id_necessidades_compras'];
        if(!empty($arr_produto['id_lancamento_projeto_produtos'])){
            $historicoPedidoCompraItemObj->lancamento_projeto_produtos_id = $arr_produto['id_lancamento_projeto_produtos'];
        }
        if(!empty($arr_produto['lancamento_projeto_tecidos_id'])){
            $historicoPedidoCompraItemObj->lancamento_projeto_tecidos_id = $arr_produto['lancamento_projeto_tecidos_id'];
        }
        $historicoPedidoCompraItemObj->pedido_compra_item_uuid = $arr_produto['pedido_compra_item_uuid'];
        $historicoPedidoCompraItemObj->produto_codigo = $arr_produto['produto_codigo'];
        $historicoPedidoCompraItemObj->produto_unidade = $arr_produto['produto_unidade'];
        $historicoPedidoCompraItemObj->valor_unitario = $arr_produto['valor_unitario'];
        $historicoPedidoCompraItemObj->valor_unitario_original = $arr_produto['valor_unitario_original'];
        $historicoPedidoCompraItemObj->quantidade = $arr_produto['quantidade'];
        $historicoPedidoCompraItemObj->quantidade_original = $arr_produto['quantidade_original'];
        $historicoPedidoCompraItemObj->valor_total = $arr_produto['valor_total'];
        $historicoPedidoCompraItemObj->valor_total_original = $arr_produto['valor_total_original'];
        $historicoPedidoCompraItemObj->created_by = Auth::id();
        $historicoPedidoCompraItemObj->save();
    }

    private function processoErroGeracaoPedidoComprasNasajonNecessidadeCompras($id_projeto, $mensagem, $query = ""){
        Log::error('Erro ao gerar pedido compras: '.$mensagem);

        if(!empty($id_projeto)){
            $historicoProjetoObj = new HistoricoProjeto();
            $historicoProjetoObj->lancamento_projetos_id = $id_projeto;
            $historicoProjetoObj->natureza = 'error_integracao_nasajon';
            $historicoProjetoObj->motivo = 'Erro ao gerar pedido compras: '.$mensagem.' Query: '.$query;
            $historicoProjetoObj->users_id = Auth::id();
            $historicoProjetoObj->created_by = Auth::id();
            $historicoProjetoObj->save();
        }
    }

    private function atualizacaoPedidoProgramadoEProjetoFaccao($id_projeto, $pedido_uuid, $pedido_compra, $produtos){
        foreach ($produtos as $produto) {
            $lancamentoProjetoFaccaoObj = LancamentoProjetoFaccao::select();
            if($produto['lancamento_projeto_produtos_id']){
                $lancamentoProjetoFaccaoObj->where('lancamento_projeto_produtos_id', $produto['lancamento_projeto_produtos_id']);
                $lancamentoProjetoFaccaoObj->whereNull('lancamento_projeto_tecidos_id');
            }else{
                $lancamentoProjetoFaccaoObj->where('lancamento_projeto_tecidos_id', $produto['lancamento_projeto_tecidos_id']);
            }
            $lancamentoProjetoFaccaoObj->update(['pedido_compras_gerado_nasajon' => $pedido_compra]);

            $numero_pedido = LancamentoProjeto::find($id_projeto)->pedido_id;

            $pedidoItemPortalObj = PedidoItemPortal::select()->where('pedido', $numero_pedido)->where('cod_produto', $produto['codigo'])->first();
            if(!empty($pedidoItemPortalObj)){
                $pedidoItemPortalObj->numero_compra = $pedido_compra;
                $pedidoItemPortalObj->updated_by = Auth::id();
                $pedidoItemPortalObj->save();
            }
        }

        $historicoProjetoObj = new HistoricoProjeto();
        $historicoProjetoObj->lancamento_projetos_id = $id_projeto;
        $historicoProjetoObj->created_by = Auth::id();
        $historicoProjetoObj->users_id = Auth::id();
        $historicoProjetoObj->natureza = 'pedido_compras';
        $historicoProjetoObj->motivo = 'Pedido Compras: '.$pedido_uuid;
        $historicoProjetoObj->save(); 
    }

    public function verificarUnidadeDoInsumo($codigo_produto){
        $retorno = "";

        $query = ProdutoEspecificacao::select();
        $query->where('codigo_produto', $codigo_produto);
        $query->where('linha', 'ilike', 'INSUMO');
        $result = $query->first();

        if(!empty($result)){
            if($result->unidade !== "UN"){
                $retorno = "A unidade padrão do insumo está incorreta. Favor verificar com o setor responsável.";
            }
        }

        return $retorno;
    }
    
    public function viewDetalhesPorProjeto(Request $request){
        $projeto_id = $request->only('projeto_id')['projeto_id'];

        $query = LancamentoProjetoFaccao::select();
        $query->where('lancamento_projetos_id', $projeto_id);
        $query->whereNotNull('pedido_compras_gerado_nasajon');
        $result = $query->get();

        $estabelecimentos = returnEmpresasNasajonView();
        $pedidos = [];

        foreach($result as $faccao_pedido_compra){
            $query_pedido_compra = ComprasNasajon::select('estabelecimento', 'numero_pedido', 'data_compra', 'previsao_entrega', 'situacao', 'fornecedor_cnpj', 'fornecedor_nome');
            $query_pedido_compra->where('estabelecimento', $faccao_pedido_compra->projeto->estabelecimento_pad);
            $query_pedido_compra->where('numero_pedido', $faccao_pedido_compra->pedido_compras_gerado_nasajon);
            $query_pedido_compra->distinct();
            $result_pedido_compra = $query_pedido_compra->first();

            $pedidos [] = [
                'estabelecimento' => $estabelecimentos[$faccao_pedido_compra->projeto->estabelecimento],
                'numero_pedido' => $result_pedido_compra->numero_pedido,
                'data_compra' => parserData($result_pedido_compra->data_compra),
                'previsao_entrega' => parserData($result_pedido_compra->previsao_entrega),
                'situacao' => $result_pedido_compra->situacao,
                'fornecedor' => $result_pedido_compra->fornecedor_nome." - ".$result_pedido_compra->fornecedor_cnpj
            ];
        }

        return view('programs.pedidos_compras.modal.detalhes_por_projeto')->with(['pedidos' => $pedidos]);
    }

    private function cnpjIntercompany(){
        $raiz[] = '05.075.884';
        $raiz[] = '06.311.274';
        return $raiz;
    }
}