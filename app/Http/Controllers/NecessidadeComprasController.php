<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\LancamentoProjeto;
use App\LancamentoProjetoProduto;
use App\LancamentoProjetoTecido;
use App\LancamentoProjetoInsumo;
use App\LancamentoProjetoFaccao;
use App\ProdutosEstoque;
use App\ClienteNasajon;
use App\NecessidadeCompras;
use App\NecessidadeComprasXProjeto;
use App\FornecedorNasajon;
use App\CondicoesPagamentoWeb;
use App\PedidosReservaProdutoNasajon;
use App\PedidoItemPortal;
use App\PedidoPortal;
use App\ComprasNasajon;
use App\UnidadeConversaoProdutoNasajon;
use App\ProdutoEspecificacao;
use App\RemessaProduto;
use App\EnvioProjetoFaccao;
use App\FichaTecnicaProduto;
use App\Preco;
use App\Faccao;
use App\FichaTecnicaProdutoTecido;
use App\FichaTecnicaProdutoServico;

use App\Http\Controllers\LancamentoProjetoController;
use App\Http\Controllers\RemessaItensController;

use App\Http\Requests\NecessidadeComprasEditarFornecedorRequest;
use App\Http\Requests\GeracaoPedidoCompraNecessidadeCompraRequest;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

class NecessidadeComprasController extends Controller
{
    private $tipo_servico = ['servico_produto', 'servico_tecido'];
    private $tipo_materia_prima = ['tecido', 'insumo'];

    private $margem_preco = 1.43;

    private $servico_minimo_350 = ['MDO014350'];
    private $servico_minimo_500 = ['MDO014500'];

    private $unidades_permitida_insumo = ['m', 'M', 'M...','METRO', 'METROS', 'MT', 'MTS', 'MTS.', 'UN', 'Kg', 'KG', 'KGS'];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ComprasProjeto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ComprasProjeto');

        $estabelecimentos = returnEmpresasNasajonView();

        return view('programs.necessidade_compras.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function filter(Request $request, $retorno_array = false){
        $fields = $request->only('num_projeto', 'nome_projeto', 'cliente', 'fornecedor','codigo_produto', 'nome_produto','estabelecimento', 'tipo');

        $query = NecessidadeCompras::select()->with(['necessidade_x_projetos', 'fornecedor_detalhes', 'produto_detalhes']);
        if(!empty($fields['codigo_produto'])){
            $query->where(function($query) use($fields){
                $query->where('produto_codigo', 'ilike', '%'.$fields['codigo_produto'].'%');
                $query->orWhereHas('necessidade_x_projetos', function($query) use($fields){
                    $query->whereHas('produto_acabado_projeto', function($query) use($fields){
                        $query->where('codigo_produto_acabado', 'ilike', '%'.$fields['codigo_produto'].'%');
                    });
                });
                $query->orWhereHas('necessidade_x_projetos', function($query) use($fields){
                    $query->whereHas('produto_acabado_projeto', function($query) use($fields){
                        $query->whereNull('codigo_produto_acabado');
                        $query->whereHas('produto', function($query) use($fields){
                            $query->where('codigo_produto', 'ilike', '%'.$fields['codigo_produto'].'%');
                        });
                    });
                });
            });
        }
        if(!empty($fields['nome_produto'])){
            $query->where(function($query) use($fields){
                $query->WhereHas('produto_detalhes', function($query) use($fields){
                    $query->where('descricao', 'ilike', '%'.$fields['nome_produto'].'%');
                });
                $query->orWhereHas('necessidade_x_projetos', function($query) use($fields){
                    $query->whereHas('produto_acabado_projeto', function($query) use($fields){
                        $query->whereNotNull('codigo_produto_acabado');
                        $query->whereHas('produto_acabado', function($query) use($fields){
                            $query->where('descricao', 'ilike', '%'.$fields['nome_produto'].'%');
                        });
                    });
                });
                $query->orWhereHas('necessidade_x_projetos', function($query) use($fields){
                    $query->whereHas('produto_acabado_projeto', function($query) use($fields){
                        $query->whereNull('codigo_produto_acabado');
                        $query->whereHas('produto', function($query) use($fields){
                            $query->where('descricao', 'ilike', '%'.$fields['nome_produto'].'%');
                        });
                    });
                });
            });
        }

        $query->whereHas('necessidade_x_projetos', function($query) use($fields){
            $query->with(['tecido_projeto', 'insumo_projeto', 'projeto'=> function($query){
                $query->whereIn('status', [4, 5]);
            }]);
            if(is_numeric($fields['num_projeto'])){
                $query->where('lancamento_projetos_id', $fields['num_projeto']);
            }
            if(!empty($fields['nome_projeto'])){
                $query->whereHas('projeto', function($query) use($fields){
                    $query->where('nome_projeto', 'ilike', '%'.$fields['nome_projeto'].'%');
                });
            }
            if(!empty($fields['cliente'])){
                $cliente_busca = ClienteNasajon::select('cpf_cnpj')
                    ->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($fields['cliente']).'%\'');
                $cliente_busca = $cliente_busca->get();
    
                $query->whereHas('projeto', function($query) use($cliente_busca){
                    $query->WhereIn('cliente_cpf_cnpj', $cliente_busca->pluck('cpf_cnpj'));
                });
            }
            if($fields['tipo'] != 'todos'){
                if($fields['tipo'] === "servico"){
                    $query->where('tipo', 'ilike', '%servico%');
                }else if($fields['tipo'] === "materia_prima"){
                    $query->whereIn('tipo', $this->tipo_materia_prima);
                }else{
                    $query->where('tipo', $fields['tipo']);
                }
            }
        });

        $query->with(['necessidade_x_projetos' => function($query) use($fields){
            $query->with(['tecido_projeto', 'insumo_projeto', 'projeto'=> function($query){
                $query->whereIn('status', [4, 5]);
            }]);
            if(is_numeric($fields['num_projeto'])){
                $query->where('lancamento_projetos_id', $fields['num_projeto']);
            }
            if(!empty($fields['nome_projeto'])){
                $query->whereHas('projeto', function($query) use($fields){
                    $query->where('nome_projeto', 'ilike', '%'.$fields['nome_projeto'].'%');
                });
            }
            if(!empty($fields['cliente'])){
                $cliente_busca = ClienteNasajon::select('cpf_cnpj')
                    ->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($fields['cliente']).'%\'');
                $cliente_busca = $cliente_busca->get();
    
                $query->whereHas('projeto', function($query) use($cliente_busca){
                    $query->WhereIn('cliente_cpf_cnpj', $cliente_busca->pluck('cpf_cnpj'));
                });
            }
            if($fields['tipo'] != 'todos'){
                if($fields['tipo'] === "servico"){
                    $query->where('tipo', 'ilike', '%servico%');
                }else if($fields['tipo'] === "materia_prima"){
                    $query->whereIn('tipo', $this->tipo_materia_prima);
                }
            }
        }]);

        if(!empty($fields['fornecedor'])){
            $fornecedor_busca = FornecedorNasajon::select('cnpj_cpf')
                ->whereRaw('CONCAT(TRIM(nome),\' - \', cnpj_cpf) ILIKE \'%'.($fields['fornecedor']).'%\'');
            $fornecedor_busca = $fornecedor_busca->get();

            $query->whereIn('fornecedor_cnpj_cpf', $fornecedor_busca->pluck('cnpj_cpf'));
        }

        $result = $query->get();

        $retorno = [];

        $estoque = $this->estoqueProduto($result->pluck('produto_codigo'), "", true);

        $remessas = $this->remessa($result);

        foreach($result as $necessidade_compras){
            if(empty($estoque[$necessidade_compras->produto_codigo])){
                $estoque[$necessidade_compras->produto_codigo] = [
                    'estoque' => 0,
                    'estoque_compras' => 0
                ];
            }
            $validar = true;
            if(!empty($necessidade_compras->necessidade_x_projetos[0])){
                if(substr_count($necessidade_compras->necessidade_x_projetos[0]->tipo, "servico") !== 0){
                    $tipo = "servico";
                }else if(substr_count($necessidade_compras->necessidade_x_projetos[0]->tipo, "pedido") !== 0){
                    $tipo = "pedido";
                }else{
                    $tipo = "materia_prima";
                }
            }else{
                $tipo = "materia_prima";
            }

            if($tipo !== "materia_prima"){
                $estoque[$necessidade_compras->produto_codigo]['estoque'] = 0;
                $estoque[$necessidade_compras->produto_codigo]['estoque_compras'] = 0;
            }

            if(empty($saldo[$necessidade_compras->produto_codigo])){
                $saldo[$necessidade_compras->produto_codigo] = $this->saldoNecessidadeCompras($necessidade_compras, $remessas, $fields);
            }else{
                $saldo[$necessidade_compras->produto_codigo] = $this->saldoNecessidadeCompras($necessidade_compras, $remessas, $fields, $saldo[$necessidade_compras->produto_codigo]);
            }
            
            $necessidade = $saldo[$necessidade_compras->produto_codigo]['saldo'] - $estoque[$necessidade_compras->produto_codigo]['estoque'] - $estoque[$necessidade_compras->produto_codigo]['estoque_compras'] ;
            $total = $estoque[$necessidade_compras->produto_codigo]['estoque'] + $estoque[$necessidade_compras->produto_codigo]['estoque_compras'] + $saldo[$necessidade_compras->produto_codigo]['remessa'];

            if(parserFloat10($necessidade) > 0 || $tipo === "servico" || $tipo === "pedido"){
                if($tipo === "servico" || $tipo === "pedido"){
                    $quantidade_projetos = 1;
                    $estoque_valor = '';
                    $compras_valor = '';
                    $total = '';
                    $necessidade_valor = parserValor($necessidade_compras->saldo);
                    $alteracao_fornecedor = false;
                    $numero_projeto = 1;
                    if($tipo === "pedido"){
                        $nome_projeto = '';
                        $alteracao_fornecedor = true;
                    }else if(empty($necessidade_compras->necessidade_x_projetos[0]->projeto)){
                        $lancamentoProjetoObj = LancamentoProjeto::find($necessidade_compras->necessidade_x_projetos[0]->lancamento_projetos_id);
                        $nome_projeto = $lancamentoProjetoObj->nome_projeto;
                    }else{
                        $nome_projeto = $necessidade_compras->necessidade_x_projetos[0]->projeto->nome_projeto;
                    }
                }else{
                    $quantidade_projetos = $saldo[$necessidade_compras->produto_codigo]['quantidade_projeto'];
                    $estoque_valor = empty($estoque[$necessidade_compras->produto_codigo]['estoque'])? 0 : parserValor($estoque[$necessidade_compras->produto_codigo]['estoque']);
                    $compras_valor = empty($estoque[$necessidade_compras->produto_codigo]['estoque_compras'])? 0 : parserValor($estoque[$necessidade_compras->produto_codigo]['estoque_compras']);
                    $necessidade_valor = parserValor($necessidade);
                    $alteracao_fornecedor = true;
                    $numero_projeto = '';
                    $nome_projeto = '';
                    if($validar){
                        if(!empty($this->verificarUnidadeDoInsumo($necessidade_compras->produto_codigo))){
                            $validar = false;
                        }else{
                            $validar = true;
                        }
                    }
                }
                if(empty($necessidade_compras->necessidade_x_projetos[0]->tipo === "servico")){
                    $codigo_produto = $necessidade_compras->produto_codigo;
                    $descricao = $necessidade_compras->produto_detalhes->descricao;
                }else{
                   $codigo_produto = empty($necessidade_compras->necessidade_x_projetos[0]->produto_acabado_projeto->tecido)? $necessidade_compras->necessidade_x_projetos[0]->produto_acabado_projeto->produto->codigo_produto : $necessidade_compras->necessidade_x_projetos[0]->produto_acabado_projeto->codigo_produto_acabado; 
                   $descricao = empty($necessidade_compras->necessidade_x_projetos[0]->produto_acabado_projeto->tecido)? $necessidade_compras->necessidade_x_projetos[0]->produto_acabado_projeto->produto->descricao : $necessidade_compras->necessidade_x_projetos[0]->produto_acabado_projeto->produto_acabado->descricao;
                }

                $retorno [$codigo_produto.$necessidade_compras->fornecedor_cnpj_cpf] = [
                    'id_necessidade_compras' => encrypt($necessidade_compras->id),
                    'codigo_estabelecimento' => intval('04'),
                    'codigo' => $codigo_produto,
                    'produto' => $descricao,
                    'fornecedor_cnpj_cpf' => $necessidade_compras->fornecedor_cnpj_cpf,
                    'fornecedor' => empty($necessidade_compras->fornecedor_detalhes)? '' : $necessidade_compras->fornecedor_detalhes->nome." - ".$necessidade_compras->fornecedor_cnpj_cpf,
                    'total_projetos' => $quantidade_projetos,
                    'estoque' => empty($estoque_valor)? '' : $estoque_valor,
                    'compras' => empty($compras_valor)? '' : $compras_valor,
                    'necessidade' => $necessidade_valor,
                    'total' => empty($total)? '' : parserValor($total),
                    'alteracao_fornecedor' => $alteracao_fornecedor,
                    'gerar_pedido' => empty($necessidade_compras->fornecedor_detalhes)? false : true,
                    'tipo' => $tipo,
                    'numero_projeto' => $numero_projeto,
                    'nome_projeto' => $nome_projeto,
                    'validar' => $validar,
                    'quantidade_produto_enviado' =>  parserValor($saldo[$necessidade_compras->produto_codigo]['remessa']),
                ];
            }
        }
 
        if($retorno_array){
            return $retorno;
        }else{
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => $retorno
            ];
            return response()->json($response);
        }
        
    }

    public function estoqueProduto($codigo_produto, $estabelecimento, $todos = false, $separado = false){

        $estoque = 0.0;
        $estoque_compras = 0.0;
        $estabelecimentos = [
            "03",
            "04",
            "05",
            "06"
        ];
        
        if($separado){
            $estoque = [];

            $query_estoque = ProdutosEstoque::selectRaw('estabelecimento, cast(estoque as float), cast(compras_aberto as float)');
            $query_estoque->where('codigo_produto', $codigo_produto);
            $query_estoque->whereIn('estabelecimento', $estabelecimentos);
            $result_estoque = $query_estoque->get();

            foreach($result_estoque as $estoque_produto){
                $PedidosVendaNasajon = PedidosReservaProdutoNasajon::selectRaw('cast(sum(quantidade) as float) as quantidade');
                $PedidosVendaNasajon->where('codigo_produto', $codigo_produto);
                $PedidosVendaNasajon->where('codigo_estabelecimento', $estoque_produto->estabelecimento);
                $estoque_reserva = $PedidosVendaNasajon->first();

                $query_pedido_item = PedidoItemPortal::selectRaw('cast(sum(quantidade) as float) as quantidade ');
                $query_pedido_item->where('cod_produto', $codigo_produto);
                $query_pedido_item->whereHas('pedido_portal', function($query) use($estoque_produto){
                    $query->whereNotIn('status_pedido', [3, 5, 7]);
                    $query->where('estabelecimento', $estoque_produto->estabelecimento);
                });
                $quantidade_pedido_item = $query_pedido_item->sum('quantidade');

                $compras_nasajon = ComprasNasajon::where('cod_produto', $codigo_produto)->whereIn('situacao', ['Aguardando Documento', 'Parcialmente Liquidado'])->get();
                $estoque_compras = 0;
                foreach($compras_nasajon as $compra_nasajon){
                    if($compra_nasajon->quantidade_restante > 0){
                        $estoque_compras += $compra_nasajon->quantidade_restante;
                    }else{
                        $estoque_compras += $compra_nasajon->quantidade;
                    }
                }

                $query_pedido_item_futuro = PedidoItemPortal::selectRaw('estabelecimento, cast(sum(quantidade) as float) as quantidade ');
                $query_pedido_item_futuro->leftJoin('pedido', 'pedido_item.pedido', '=', 'pedido.id');
                $query_pedido_item_futuro->whereNotIn('pedido.status_pedido', [8]);
                $query_pedido_item_futuro->whereIn('pedido.estabelecimento', $estoque_produto->estabelecimento);
                $query_pedido_item_futuro->where('pedido_item.cod_produto', $codigo_produto);
                $query_pedido_item_futuro->whereNull('pedido.deleted_at');
                if(!empty($pedido)){
                    $query_pedido_item_futuro->where('pedido', '<>', $pedido);
                }
                $query_pedido_item_futuro->groupBy('pedido.estabelecimento');
                $result_pedidos_venda_futuro_portal = $query_pedido_item_futuro->get();

                foreach($result_pedidos_venda_futuro_portal as $pedidos_venda_futuro_portal){
                    $estoque_compras -= $pedidos_venda_futuro_portal->quantidade;
                }
                $estoque_compras = $estoque_compras < 0? 0 : $estoque_compras;

                $estoque[$estoque_produto->estabelecimento] = [
                    'estoque' => empty($estoque_reserva)? $estoque_produto->estoque: $estoque_produto->estoque - $estoque_reserva->quantidade - $quantidade_pedido_item,
                    'estoque_compras' => empty($estoque_compras)? 0 : $estoque_compras
                ];

                if($estoque[$estoque_produto->estabelecimento]['estoque'] < 0){
                    $estoque[$estoque_produto->estabelecimento]['estoque'] = 0;
                }
            }

            $retorno = $estoque;
        }else{
            if($todos){
                $estoque_produto = ProdutosEstoque::whereIn('codigo_produto', $codigo_produto);
                $estoque_produto->whereIn('estabelecimento', $estabelecimentos);
            }else{
                $estoque_produto = ProdutosEstoque::whereIn('codigo_produto', $codigo_produto);
                $estoque_produto->where('estabelecimento', $estabelecimento);
            }

            $result_produto_estoque = $estoque_produto->get();


            $compras_nasajon = ComprasNasajon::whereIn('cod_produto', $codigo_produto)->whereIn('situacao', ['Aguardando Documento', 'Parcialmente Liquidado'])->get();
            $estoque_compras = [];

            foreach($compras_nasajon as $compra_nasajon){
                if($compra_nasajon->quantidade_restante > 0){
                    $compras = $compra_nasajon->quantidade_restante;
                }else{
                    $compras = $compra_nasajon->quantidade;
                }

                if(empty($estoque_compras[$compra_nasajon->cod_produto])){
                    $estoque_compras[$compra_nasajon->cod_produto] = $compras;
                }else{
                    $estoque_compras[$compra_nasajon->cod_produto] += $compras;
                }
            }
            
            $query_pedido_item_futuro = PedidoItemPortal::selectRaw('estabelecimento, cast(sum(quantidade) as float) as quantidade ');
            $query_pedido_item_futuro->leftJoin('pedido', 'pedido_item.pedido', '=', 'pedido.id');
            $query_pedido_item_futuro->whereNotIn('pedido.status_pedido', [8]);
            if($todos){
                $query_pedido_item_futuro->whereIn('pedido.estabelecimento', $estabelecimentos);
            }else{
                $query_pedido_item_futuro->where('pedido.estabelecimento', $estabelecimento);
            }
            $query_pedido_item_futuro->whereIn('pedido_item.cod_produto', $codigo_produto);
            $query_pedido_item_futuro->whereNull('pedido.deleted_at');
            $query_pedido_item_futuro->groupBy('pedido.estabelecimento');
            $result_pedidos_venda_futuro_portal = $query_pedido_item_futuro->get();

            foreach($result_pedidos_venda_futuro_portal as $pedidos_venda_futuro_portal){
                if(!empty($estoque_compras[$pedidos_venda_futuro_portal->codigo_produto])){
                    $estoque_compras[$pedidos_venda_futuro_portal->cod_produto] -= $pedidos_venda_futuro_portal->quantidade;
                }
            }
    
            $PedidosVendaNasajon = PedidosReservaProdutoNasajon::select();
            $PedidosVendaNasajon->whereIn('codigo_produto', $codigo_produto);
            $PedidosVendaNasajon->whereIn('codigo_estabelecimento', $result_produto_estoque->pluck('estabelecimento'));
            $result_pedidos_venda_nasajon = $PedidosVendaNasajon->get();

            $reserva_nasajon = [];
            foreach($result_pedidos_venda_nasajon as $pedidos_venda_nasajon){
                if(empty($reserva_nasajon[intval($pedidos_venda_nasajon->codigo_estabelecimento)][$pedidos_venda_nasajon->codigo_produto])){
                    $reserva_nasajon[intval($pedidos_venda_nasajon->codigo_estabelecimento)][$pedidos_venda_nasajon->codigo_produto] = $pedidos_venda_nasajon->quantidade;
                }else{
                    $reserva_nasajon[intval($pedidos_venda_nasajon->codigo_estabelecimento)][$pedidos_venda_nasajon->codigo_produto] += $pedidos_venda_nasajon->quantidade;
                }
            }

            $query_pedido_item = PedidoItemPortal::select();
            $query_pedido_item->leftJoin('pedido', 'pedido_item.pedido', '=', 'pedido.id');
            $query_pedido_item->whereNotIn('pedido.status_pedido', [3, 5, 7]);
            $query_pedido_item->whereIn('pedido.estabelecimento', $result_produto_estoque->pluck('estabelecimento'));
            $query_pedido_item->whereIn('pedido_item.cod_produto', $codigo_produto);
            $query_pedido_item->whereNull('pedido.deleted_at');
            $result_pedidos_venda_portal = $query_pedido_item->get();

            $reserva_portal = [];
            foreach($result_pedidos_venda_portal as $pedidos_venda_portal){
                if(empty($reserva_portal[intval($pedidos_venda_portal->estabelecimento)][$pedidos_venda_portal->cod_produto])){
                    $reserva_portal[intval($pedidos_venda_portal->estabelecimento)][$pedidos_venda_portal->cod_produto] = $pedidos_venda_portal->quantidade;
                }else{
                    $reserva_portal[intval($pedidos_venda_portal->estabelecimento)][$pedidos_venda_portal->cod_produto] += $pedidos_venda_portal->quantidade;
                }
            }

            $estoque_estabelecimento = [];
            foreach($result_produto_estoque as $produto_estoque){
                $quantidade_reserva_portal = 0;
                if(!empty($reserva_portal[intval($produto_estoque->estabelecimento)][$produto_estoque->codigo_produto])){
                    $quantidade_reserva_portal = $reserva_portal[intval($produto_estoque->estabelecimento)][$produto_estoque->codigo_produto];
                }

                $quantidade_reserva_nasajon = 0;
                if(!empty($reserva_nasajon[intval($produto_estoque->estabelecimento)][$produto_estoque->codigo_produto])){
                    $quantidade_reserva_nasajon = $reserva_nasajon[intval($produto_estoque->estabelecimento)][$produto_estoque->codigo_produto];
                }

                if(empty($estoque_estabelecimento[$produto_estoque->codigo_produto])){
                    $estoque_estabelecimento[$produto_estoque->codigo_produto] = 0;
                }
                $estoque_estabelecimento[$produto_estoque->codigo_produto] += ($produto_estoque->estoque - $quantidade_reserva_portal - $quantidade_reserva_nasajon) < 0? 0 : $produto_estoque->estoque - $quantidade_reserva_portal - $quantidade_reserva_nasajon;
            }
            
            $retorno = [];

            foreach($estoque_estabelecimento as $key => $quantidade){
                $retorno[$key] =[
                    'estoque' => empty($quantidade)? 0 : number_format(parserFloat10($quantidade), 2, '.', ''),
                    'estoque_compras' => empty($estoque_compras[$key])? 0 : $estoque_compras[$key]
                ];
            }
    
        }
 
        return $retorno;
    }

    public function dialog(Request $request){
        $fields = $request->only('codigo_produto', 'total', 'tipo', 'num_projeto', 'nome_projeto', 'cliente', 'fornecedor', 'id_necessidade_compras', 'quantidade_produto_enviado', 'fornecedor_cnpj_cpf');
        $arr_materia_prima = [];
        $tipo = $fields['tipo'];
        $codigo_produto = $fields['codigo_produto'];
        if(!empty($fields['total'])){
            $total = floatval(str_replace(",", ".", str_replace(".", "",$fields['total'])));
        }else{
            $total = 0;
        }
        $estoque = $this->estoqueProduto([$codigo_produto], "", true);
        $query = NecessidadeCompras::select();
        if($fields['tipo'] === "servico"){
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
            $query->where('id', $id_necessidade_compras);
            $query->with(['necessidade_x_projetos']);
        }else{
            $query->where('produto_codigo', $codigo_produto);
            $query->where('fornecedor_cnpj_cpf', $fields['fornecedor_cnpj_cpf']);
            $query->with(['necessidade_x_projetos' => function($query) use($fields){
                if(is_numeric($fields['num_projeto'])){
                    $query->where('lancamento_projetos_id', $fields['num_projeto']);
                }
                if(!empty($fields['nome_projeto'])){
                    $query->whereHas('projeto', function($query) use($fields){
                        $query->where('nome_projeto', 'ilike', '%'.$fields['nome_projeto'].'%');
                    });
                }
                if(!empty($fields['cliente'])){
                    $cliente_busca = ClienteNasajon::select('cpf_cnpj')
                        ->whereRaw('CONCAT(TRIM(nome),\' - \', cpf_cnpj) ILIKE \'%'.($fields['cliente']).'%\'');
                    $cliente_busca = $cliente_busca->get();
                    $query->whereHas('projeto', function($query) use($cliente_busca){
                        $query->WhereIn('cliente_cpf_cnpj', $cliente_busca->pluck('cpf_cnpj'));
                    });
                }
                $query->whereHas('projeto', function($query){
                    $query->whereIn('status', [4, 5]);
                });
            }]);
            if(!empty($fields['fornecedor'])){
                $fornecedor_busca = FornecedorNasajon::select('cnpj_cpf')
                    ->whereRaw('CONCAT(TRIM(nome),\' - \', cnpj_cpf) ILIKE \'%'.($fields['fornecedor']).'%\'');
                $fornecedor_busca = $fornecedor_busca->get();
                $query->whereIn('fornecedor_cnpj_cpf', $fornecedor_busca->pluck('cnpj_cpf'));
            }
        }
        $result = $query->get();
        foreach($result as $necessidade_compra){
            foreach($necessidade_compra->necessidade_x_projetos as $necessidade_x_projetos){
                if(!empty($necessidade_x_projetos->tecido_projeto)){
                    $codigo_produto = $necessidade_x_projetos->tecido_projeto->produto->codigo_produto;
                    $produto = $necessidade_x_projetos->tecido_projeto->produto->descricao;
                    $codigo = $necessidade_compra->produto_codigo;
                    $descricao = $necessidade_compra->produto_detalhes->descricao;
                    $saldo = empty($necessidade_x_projetos->tecido_projeto->quantidade_enviada)? floatval($necessidade_x_projetos->tecido_projeto->consumo_total) : (floatval($necessidade_x_projetos->tecido_projeto->consumo_total) - floatval($necessidade_x_projetos->tecido_projeto->quantidade_enviada));
                }else if(!empty($necessidade_x_projetos->insumo_projeto)){
                    $codigo_produto = $necessidade_x_projetos->insumo_projeto->produto->codigo_produto;
                    $produto = $necessidade_x_projetos->insumo_projeto->produto->descricao;
                    $codigo = $necessidade_compra->produto_codigo;
                    $descricao = $necessidade_compra->produto_detalhes->descricao;
                    $saldo = empty($necessidade_x_projetos->insumo_projeto->quantidade_enviada)? floatval($necessidade_x_projetos->insumo_projeto->consumo_total) : (floatval($necessidade_x_projetos->insumo_projeto->consumo_total) - floatval($necessidade_x_projetos->insumo_projeto->quantidade_enviada));
                }else if(!empty($necessidade_x_projetos->produto_projeto)){
                    $codigo_produto = $necessidade_x_projetos->produto_projeto->servico_detalhes->tipo_servico_id;
                    $produto = $necessidade_x_projetos->produto_projeto->servico_detalhes->tipo_de_servico->descricao;
                    $codigo = $necessidade_compra->produto_codigo;
                    $descricao = $necessidade_compra->produto_detalhes->descricao;
                    $saldo = $necessidade_x_projetos->produto_projeto->quantidade;
                }else if(!empty($necessidade_x_projetos->produto_acabado_projeto)){
                    $codigo_produto = $necessidade_x_projetos->produto_acabado_projeto->tipo_servico_id;
                    $produto = $necessidade_x_projetos->produto_acabado_projeto->tipo_de_servico->descricao;
                    $codigo = $necessidade_compra->produto_codigo;
                    $descricao = $necessidade_compra->produto_detalhes->descricao;
                    $saldo = $necessidade_x_projetos->produto_acabado_projeto->quantidade;
                }
                if(empty($estoque[$codigo_produto])){
                    $estoque[$codigo_produto]=[
                        'estoque' => 0,
                        'estoque_compras' => 0
                    ];
                }
                $necessidade = $saldo - $estoque[$codigo_produto]['estoque'] - $estoque[$codigo_produto]['estoque_compras'];
				
                if($necessidade < 0){
                    $necessidade = '';
                }
                $arr_materia_prima[] = [
                    'num_projeto' => $necessidade_x_projetos->projeto->id,
                    'nome_projeto' => $necessidade_x_projetos->projeto->nome_projeto,
                    'cliente' => $necessidade_x_projetos->projeto->cliente->nome." - ".$necessidade_x_projetos->projeto->cliente->cpf_cnpj,
                    'codigo_produto' => $codigo_produto,
                    'produto' => $produto,
                    'codigo' => $codigo,
                    'descricao' => $descricao,
                    'saldo_pedido' =>  parserValor($saldo),
                    'estoque' =>  empty($estoque[$necessidade_compra->produto_codigo]['estoque'])? '' : parserValor($estoque[$necessidade_compra->produto_codigo]['estoque']),
                    'estoque_compras' =>  empty($estoque[$necessidade_compra->produto_codigo]['estoque_compras'])? '' : parserValor($estoque[$necessidade_compra->produto_codigo]['estoque_compras']),
                    'necessidade' =>  empty($necessidade)? '' : parserValor($necessidade),
                ];
            }
        }
        asort($arr_materia_prima);
        foreach($arr_materia_prima as $key => $materia_prima){
            $saldo_pedido = floatval(str_replace(",", ".", str_replace(".", "", $materia_prima['saldo_pedido'])));
            $total = $total - $saldo_pedido;
            $arr_materia_prima[$key]['total'] = $total < 0?  parserValor($total * (-1)) :  '';
        }
        asort($arr_materia_prima);
        if($total < 0 ){
            $total = parserValor($total * (-1));
        }else{
            $total = '';
        }
        return view('programs.necessidade_compras.modal.dialog')->with(['arr_materia_prima' => $arr_materia_prima, 'total' => $total, 'tipo' => $tipo]);
    }

    public function adicionarAtravesProjeto(LancamentoProjeto $projeto){
        $lancamentoProjetoControllerObj = new LancamentoProjetoController;

        foreach($projeto->faccoes as $servico){
            $necessidadeComprasObj = NecessidadeCompras::select()
                ->where('produto_codigo', $servico->tipo_servico_id)
                ->where('fornecedor_cnpj_cpf', $servico->faccao->cod_fornecedor)
                ->whereHas('necessidade_x_projetos', function($query) use($servico){
                    $query->where('lancamento_projeto_faccoes_id', $servico->lancamento_projetos_id);
                })
                ->first();
            if(empty($necessidadeComprasObj)){
                $necessidadeComprasObj = $this->adicionar($servico->tipo_servico_id, $servico->faccao->cod_fornecedor, $servico->quantidade);
                
                $lancamentoProjetoControllerObj->gravarHistoricoProjeto($servico->lancamento_projetos_id, 'necessidade_compras', 'Projeto: '.$servico->lancamento_projetos_id.' ID Necessida Compras: '.$necessidadeComprasObj->id.' ID Serviço: '.$servico->id.' Quantidade Adicionada: '.$servico->quantidade.' Saldo Atual: '.$necessidadeComprasObj->saldo, Auth::id());

                $necessidadeComprasXProjetoObj = $this->adicionarNecessidadeComprasXProjeto($necessidadeComprasObj->id, $servico->lancamento_projetos_id, "servico", $servico->id);
    
                $lancamentoProjetoControllerObj->gravarHistoricoProjeto($servico->lancamento_projetos_id, 'necessidade_compras', 'Projeto: '.$servico->lancamento_projetos_id.' ID Necessida Compras x Projeto: '.$necessidadeComprasXProjetoObj->id, Auth::id());
            }
        }

        foreach($projeto->tecidos as $tecido){
            $query = NecessidadeCompras::select()
                ->where('produto_codigo', $tecido->codigo_produto)
                ->whereNull('fornecedor_cnpj_cpf');
            $necessidadeComprasObj = $query->first();
            $verificar_duplicidade = $query->whereHas('necessidade_x_projetos', function($query) use($tecido){
                $query->where('lancamento_projeto_tecidos_id', $tecido->id);
            })->first();

            if(empty($necessidadeComprasObj)){
                $necessidadeComprasObj = $this->adicionar($tecido->codigo_produto, "", $tecido->consumo_total);
            }else if(empty($verificar_duplicidade)){
                $necessidadeComprasObj = $this->atualizacaoQuantidadeSaldo($necessidadeComprasObj, $tecido->consumo_total);

            }

            if(empty($verificar_duplicidade)){
                $lancamentoProjetoControllerObj->gravarHistoricoProjeto($tecido->lancamento_projetos_id, 'necessidade_compras', 'Projeto: '.$tecido->lancamento_projetos_id.' ID Necessida Compras: '.$necessidadeComprasObj->id.' ID Tecido: '.$tecido->id.' Quantidade'.$tecido->consumo_total.' Saldo Atual: '.$necessidadeComprasObj->saldo, Auth::id());

                $necessidadeComprasXProjetoObj = $this->adicionarNecessidadeComprasXProjeto($necessidadeComprasObj->id, $tecido->lancamento_projetos_id, "tecido", $tecido->id);
    
                $lancamentoProjetoControllerObj->gravarHistoricoProjeto($tecido->lancamento_projetos_id, 'necessidade_compras', 'Projeto: '.$tecido->lancamento_projetos_id.' ID Necessida Compras x Projeto: '.$necessidadeComprasXProjetoObj->id, Auth::id());
            }
        }

        foreach($projeto->insumos as $insumo){
            $query = NecessidadeCompras::select()
                ->where('produto_codigo', $insumo->codigo_produto)
                ->whereNull('fornecedor_cnpj_cpf');
            $necessidadeComprasObj = $query->first();
            $verificar_duplicidade = $query->whereHas('necessidade_x_projetos', function($query) use($insumo){
                $query->where('lancamento_projeto_insumos_id', $insumo->id);
            })->first();
            if(empty($necessidadeComprasObj)){
                $necessidadeComprasObj = $this->adicionar($insumo->codigo_produto, "", $insumo->consumo_total);
            }else if(empty($verificar_duplicidade)){
                $necessidadeComprasObj = $this->atualizacaoQuantidadeSaldo($necessidadeComprasObj, $insumo->consumo_total);
            }

            if(empty($verificar_duplicidade)){
                $lancamentoProjetoControllerObj->gravarHistoricoProjeto($insumo->lancamento_projetos_id, 'necessidade_compras', 'Projeto: '.$insumo->lancamento_projetos_id.' ID Necessida Compras: '.$necessidadeComprasObj->id.' ID Insumo: '.$insumo->id.' Quantidade'.$insumo->consumo_total.' Saldo Atual: '.$necessidadeComprasObj->saldo, Auth::id());

                $necessidadeComprasXProjetoObj = $this->adicionarNecessidadeComprasXProjeto($necessidadeComprasObj->id, $insumo->lancamento_projetos_id, "insumo", $insumo->id);

                $lancamentoProjetoControllerObj->gravarHistoricoProjeto($insumo->lancamento_projetos_id, 'necessidade_compras', 'Projeto: '.$insumo->lancamento_projetos_id.' ID Necessida Compras x Projeto: '.$necessidadeComprasXProjetoObj->id, Auth::id());
            }
        }
    }

    public function adicionar($codigo_produto, $fornecedor_cnpj_cpf, $quantidade){
        $necessidadeComprasObj = new NecessidadeCompras;
        $necessidadeComprasObj->produto_codigo = $codigo_produto;
        if(!empty($fornecedor_cnpj_cpf)){
            $necessidadeComprasObj->fornecedor_cnpj_cpf = $fornecedor_cnpj_cpf;
        }
        $necessidadeComprasObj->quantidade = $quantidade;
        $necessidadeComprasObj->quantidade_enviada = 0;
        $necessidadeComprasObj->saldo = $quantidade;
        $necessidadeComprasObj->created_by = Auth::id();
        $necessidadeComprasObj->save();

        return $necessidadeComprasObj;
    }

    public function adicionarNecessidadeComprasXProjeto($id_necessidade_compras, $id_principal, $tipo, $id_relacao){
        $necessidadeComprasXProjetoObj = new NecessidadeComprasXProjeto;
        $necessidadeComprasXProjetoObj->necessidades_compras_id = $id_necessidade_compras;
        if($tipo === "pedido"){
            $necessidadeComprasXProjetoObj->pedido_id = $id_principal;
        }else{
            $necessidadeComprasXProjetoObj->lancamento_projetos_id = $id_principal;
        }
        if($tipo === "servico_produto"){
            $necessidadeComprasXProjetoObj->lancamento_projeto_produtos_id = $id_relacao;
        }else if($tipo === "tecido"){
            $necessidadeComprasXProjetoObj->lancamento_projeto_tecidos_id = $id_relacao;
        }else if($tipo === "insumo"){
            $necessidadeComprasXProjetoObj->lancamento_projeto_insumos_id = $id_relacao;
        }else if($tipo === "servico_tecido"){
            $necessidadeComprasXProjetoObj->lancamento_projeto_faccoes_id = $id_relacao;
        }else if($tipo === "servico"){
            $necessidadeComprasXProjetoObj->lancamento_projeto_faccoes_id = $id_relacao;
        }else if($tipo === "pedido"){
            $necessidadeComprasXProjetoObj->pedido_item_id = $id_relacao;
        }
        $necessidadeComprasXProjetoObj->tipo = $tipo;
        $necessidadeComprasXProjetoObj->created_by = Auth::id();
        $necessidadeComprasXProjetoObj->save();

        return $necessidadeComprasXProjetoObj;
    }

    public function atualizacaoQuantidadeSaldo($NecessidadeCompras, $quantidade){
        $NecessidadeCompras->quantidade = $quantidade + $NecessidadeCompras->quantidade;
        $NecessidadeCompras->saldo = $quantidade + $NecessidadeCompras->saldo;
        $NecessidadeCompras->updated_by = Auth::id();
        $NecessidadeCompras->save();

        return $NecessidadeCompras;
    }

    public function modalEditarFornecedor(Request $request){
        $id_necessidade_compras = $request->only('id_necessidade_compras')['id_necessidade_compras'];

        try{
            $id_necessidade_compras = decrypt($id_necessidade_compras);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $necessidadeComprasObj = NecessidadeCompras::find($id_necessidade_compras);

        $dados=[
            'id_necessidade_compras' => encrypt($id_necessidade_compras),
            'fornecedor' => empty($necessidadeComprasObj->fornecedor_cnpj_cpf)? '' :  $necessidadeComprasObj->fornecedor_detalhes->nome.' - '.$necessidadeComprasObj->fornecedor_cnpj_cpf,
        ];
        
        return view('programs.necessidade_compras.modal.editar_fornecedor')->with(['dados' => $dados]);
    }

    public function editarFornecedor(NecessidadeComprasEditarFornecedorRequest $request){
        $fields = $request->only('fornecedor', 'id_necessidade_compras');

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

        $query_fornecedor = FornecedorNasajon::select();
        $query_fornecedor->whereRaw('nome || \' - \' || cnpj_cpf ILIKE \'%'.$fields['fornecedor'].'%\'');
        $result_fornecedor = $query_fornecedor->first();

        $necessidadeComprasObj = NecessidadeCompras::find($id_necessidade_compras);

        $query_verificar = NecessidadeCompras::select();
        $query_verificar->where('produto_codigo', $necessidadeComprasObj->produto_codigo);
        $query_verificar->where('fornecedor_cnpj_cpf', $result_fornecedor->cnpj_cpf);
        $result_verificar = $query_verificar->first();

        if(empty($result_verificar)){
            $necessidadeComprasObj->fornecedor_cnpj_cpf = $result_fornecedor->cnpj_cpf;
            $necessidadeComprasObj->updated_by = Auth::id();
            $necessidadeComprasObj->save();
        }else{
            $necessidadeComprasXProjetoObj = NecessidadeComprasXProjeto::select();
            $necessidadeComprasXProjetoObj->where('necessidades_compras_id', $necessidadeComprasObj->id);
            $necessidadeComprasXProjetoObj->update(['necessidades_compras_id' => $result_verificar->id]);

            $necessidadeComprasObj->deleted_by = Auth::id();
            $necessidadeComprasObj->save();
            $necessidadeComprasObj->delete();

            $this->atualizacaoQuantidadeSaldo($result_verificar, $necessidadeComprasObj->quantidade);
        }
        
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }
//gislene
    public function getProdutoPorFornecedor(Request $request){
        $fields = $request->only(['id_necessidade_compras', 'fornecedor_cnpj_cpf', 'todos_produtos', 'tipo']);

        $retorno = [];

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
        if($fields['todos_produtos'] === "true"){
            
            $query = NecessidadeCompras::select()->with(['produto_detalhes']);
            if($fields['tipo'] !== "pedido"){
                $query->where('fornecedor_cnpj_cpf', $fields['fornecedor_cnpj_cpf']);
            }
            $query->whereHas('necessidade_x_projetos', function($query) use($fields, $necessidadeComprasObj){
                if($fields['tipo'] === "servico"){
                    $query->where('tipo', 'ilike', '%servico%');
                    $query->where('lancamento_projetos_id', $necessidadeComprasObj->necessidade_x_projetos[0]->lancamento_projetos_id);
                }else if($fields['tipo'] === "pedido"){
                    $query->where('pedido_id', $necessidadeComprasObj->necessidade_x_projetos[0]->pedido_id);
                }else{
                    $query->whereIn('tipo', $this->tipo_materia_prima);
                }
            });
            $query->with(['necessidade_x_projetos.produto_acabado_projeto.produto_acabado']);
            $result = $query->get();

            $estoque = $this->estoqueProduto($result->pluck('produto_codigo'), "", true);
            $remessas = $this->remessa($result);
            foreach($result as $necessidade_compra){
                $necessidade = $necessidade_compra->saldo;
                $preco_servicos = 0;
                if(empty($necessidade_compra->necessidade_x_projetos[0]->tipo === "servico")){
                    $codigo_produto = $necessidade_compra->produto_codigo;
                    $descricao = $necessidade_compra->produto_detalhes->descricao;
                }else{
                   $codigo_produto = empty($necessidade_compra->necessidade_x_projetos[0]->produto_acabado_projeto->tecido)? $necessidade_compra->necessidade_x_projetos[0]->produto_acabado_projeto->produto->codigo_produto : $necessidade_compra->necessidade_x_projetos[0]->produto_acabado_projeto->codigo_produto_acabado; 
                   $descricao = empty($necessidade_compra->necessidade_x_projetos[0]->produto_acabado_projeto->tecido)? $necessidade_compra->necessidade_x_projetos[0]->produto_acabado_projeto->produto->descricao : $necessidade_compra->necessidade_x_projetos[0]->produto_acabado_projeto->produto_acabado->descricao;
                }
                if($fields['tipo'] === "servico"){
                   
                    $estoque[$necessidade_compra->produto_codigo] = [
                        'estoque' => 0,
                        'estoque_compras' => 0,
                    ];
                    $fichaTecnicaObjeto = '';
                   
                    if($necessidade_compra->necessidade_x_projetos[0]->tipo === "servico"){
                        
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
                        $total = '';
                        $custo_gerencial_servicos_total = 0;
                        $custo_gerencial = 0;
                        $servicos = [];
                        
                        $fichaTecnicaObjeto->servicos->each(function ($servico) use (&$servicos, &$custo_servicos_total, &$custo_gerencial_servicos_total, &$preco_servicos){        
    
                            $custo_gerencial = empty($servico->preco->compra_real)? $servico->preco->preco_real / 1.43 : $servico->preco->compra_real;             
                            $custo_gerencial_servicos_total += $custo_gerencial;
                            $preco_servicos = $custo_gerencial_servicos_total;     
                        });          
                         $necessidade = $necessidade_compra->quantidade;
                         $total = $preco_servicos * ($necessidade / $fator_conversao);
                    }else{
                        if(!empty($necessidade_compra->necessidade_x_projetos[0]->lancamento_projeto_produtos_id)){
                           
                            $fichaTecnicaObjeto = FichaTecnicaProduto::select();
                            $fichaTecnicaObjeto->where('codigo_produto',$codigo_produto);
                            $fichaTecnicaObjeto = $fichaTecnicaObjeto->first();
                       
                            $necessidade   = 1;
                            $fator_conversao = 1;
                            $total = '';
                            $custo_gerencial_servicos_total = 0;
                            $custo_gerencial = 0;
                            $servicos = [];
                        
                            $fichaTecnicaObjeto->servicos->each(function ($servico) use (&$servicos, &$custo_servicos_total, &$custo_gerencial_servicos_total, &$preco_servicos){        
    
                                $custo_gerencial = empty($servico->preco->compra_real)? $servico->preco->preco_real / 1.43 : $servico->preco->compra_real;             
                                $custo_gerencial_servicos_total += $custo_gerencial;
                                $preco_servicos = $custo_gerencial_servicos_total;     
                        });          
                         $necessidade = $necessidadeComprasObj->quantidade;
                         $total = $preco_servicos * ($necessidade / $fator_conversao);

                           
                           
                        }else{
                            $fichaTecnicaObjeto = FichaTecnicaProduto::select();
                            $fichaTecnicaObjeto->where('codigo_produto',$codigo_produto);
                            $fichaTecnicaObjeto = $fichaTecnicaObjeto->first();
                       
                            $necessidade   = 1;
                            $fator_conversao = 1;
                            $total = '';
                            $custo_gerencial_servicos_total = 0;
                            $custo_gerencial = 0;
                            $servicos = [];
                        
                            $fichaTecnicaObjeto->servicos->each(function ($servico) use (&$servicos, &$custo_servicos_total, &$custo_gerencial_servicos_total, &$preco_servicos){        
    
                                $custo_gerencial = empty($servico->preco->compra_real)? $servico->preco->preco_real / 1.43 : $servico->preco->compra_real;             
                                $custo_gerencial_servicos_total += $custo_gerencial;
                                $preco_servicos = $custo_gerencial_servicos_total;     
                        });          
                         $necessidade = $necessidadeComprasObj->quantidade;
                         $total = $preco_servicos * ($necessidade / $fator_conversao);
                           
                        }
                    }
                    
                    $unidade_compra = empty($necessidade_compra->produto_detalhes->produtoNasajon->unidade)? 'UN' : $necessidade_compra->produto_detalhes->produtoNasajon->unidade;
                    $fator_conversao = 1;
                    if(in_array($necessidade_compra->produto_codigo, $this->servico_minimo_500)){
                        if($necessidadeComprasObj->saldo < 500){
                            $total = $preco_servicos * 500;

                            $preco_compra = parserValor($total/$necessidade_compra->saldo);
                            $total = parserValor(parserNumber($preco_compra) * $necessidade_compra->saldo);
                        }else{
                            $preco_compra = parserValor($preco_servicos);
                            $total = parserValor($preco_servicos * $necessidade); 
                        }
                    }else if(in_array($necessidade_compra->produto_codigo, $this->servico_minimo_350)){
                        if($necessidade_compra->saldo < 350){
                            $total = $preco_servicos * 350;
                            $preco_compra = parserValor($total/$necessidade_compra->saldo);
                            $total = parserValor(parserNumber($preco_compra) * $necessidade_compra->saldo);
                        }else{
                            $preco_compra = parserValor($preco_servicos);
                            $total = parserValor($preco_servicos * $necessidade); 
                        }
                    }else{
                        $preco_compra = parserValor($preco_servicos);
                        $total = parserValor($preco_servicos * $necessidade);
                    }
                
                }else if($fields['tipo'] === "pedido"){
                    $fichaTecnicaObjeto = FichaTecnicaProduto::select();
                    $fichaTecnicaObjeto->where('codigo_produto',$codigo_produto);
                    $fichaTecnicaObjeto = $fichaTecnicaObjeto->first();

                    $estoque[$necessidade_compra->produto_codigo] = [
                        'estoque' => 0,
                        'estoque_compras' => 0,
                    ];
                    
                    $necessidade   = 1;
                    $fator_conversao = 1;
                    $total = '';
                    
                    $precoObj = Preco::select()->where('codigo_produto', 'MDOES001')->first();

                    $preco_servicos = empty($precoObj->compra_real)? $precoObj->preco_real / 1.43 : $precoObj->compra_real;
                    
                    $necessidade = $necessidade_compra->quantidade;
                    $total = $preco_servicos * ($necessidade / $fator_conversao);
                    
                    $unidade_compra = $necessidade_compra->produto_detalhes->produtoNasajon->unidade;
                    $fator_conversao = 1;
                    $preco_compra = parserValor($preco_servicos);
                    $total = parserValor($preco_servicos * $necessidade);
                }else{
                    if(empty($estoque[$necessidade_compra->produto_codigo])){
                        $estoque[$necessidade_compra->produto_codigo] = [
                            'estoque' => 0,
                            'estoque_compras' => 0,
                        ];
                    }
                    $saldo = $this->saldoNecessidadeCompras($necessidade_compra, $remessas);
                    $necessidade = $saldo['saldo'] - $estoque[$necessidade_compra->produto_codigo]['estoque'] - $estoque[$necessidade_compra->produto_codigo]['estoque_compras'] ;

                    $query_compras_nasajon = ComprasNasajon::select();
                    $query_compras_nasajon->where('cod_produto', $necessidadeComprasObj->produto_codigo);
                    $query_compras_nasajon->orderBy('data_compra', 'desc');
                    $result_compras_nasajon = $query_compras_nasajon->first();

                    if(!empty($result_compras_nasajon)){
                        $preco_compra = $result_compras_nasajon->preco_compra_unitario;
                        $unidade_compra = $result_compras_nasajon->unidade_comercial;
                        if($result_compras_nasajon->unidade_comercial === $necessidadeComprasObj->produto_detalhes->produtoNasajon->unidade){
                            $fator_conversao = 1;
                        }else{
                            $query_unidade_conversao = UnidadeConversaoProdutoNasajon::select();
                            $query_unidade_conversao->where('codigo_unidadepadrao', 'ilike', $necessidadeComprasObj->produto_detalhes->produtoNasajon->unidade);
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
                        $unidade_compra = $necessidadeComprasObj->produto_detalhes->produtoNasajon->unidade;
                        $fator_conversao = 1;
                        $total = '';
                    }
                }
                
                if($necessidade > 0){
                    $retorno [$codigo_produto] = [
                        'id_necessidade_compras' => encrypt($necessidade_compra->id),
                        'necessidade_compras' => $necessidade_compra->id,
                        'codigo' => $codigo_produto,
                        'produto' => $necessidade_compra->produto_detalhes->descricao,
                        'descricao' => $descricao,
                        'unidade' => empty($necessidade_compra->produto_detalhes->produtoNasajon->unidade)? 'UN' : $necessidade_compra->produto_detalhes->produtoNasajon->unidade,
                        'necessidade' => str_replace(".","", parserQtd($necessidade)),
                        'valor_unitario' => $preco_compra,
                        'quantidade' => str_replace(".","", parserQtd($necessidade)),
                        'valor_total' => $total,
                        'estoque' => empty($estoque[$necessidade_compra->produto_codigo]['estoque'])? '' : parserValor($estoque[$necessidade_compra->produto_codigo]['estoque']),
                        'compras' => empty($estoque[$necessidade_compra->produto_codigo]['estoque_compras'])? '' : parserValor($estoque[$necessidade_compra->produto_codigo]['estoque_compras']),
                        'total' => $estoque[$necessidade_compra->produto_codigo]['estoque'] + $estoque[$necessidade_compra->produto_codigo]['estoque_compras'],
                        'fornecedor_cnpj_cpf' => $necessidade_compra->fornecedor_cnpj_cpf,
                        'unidade_compra' => $unidade_compra,
                        'fator_conversao' => parserValor($fator_conversao),
                        'necessidade_compra' => parserQtd($necessidade / $fator_conversao),
                        'ficha_tecnnica_id' => empty($fichaTecnicaObjeto)? '' : $fichaTecnicaObjeto->id,
                    ];
                }
            }
        }else{
            $preco_servicos = 0;

            if($necessidadeComprasObj->necessidade_x_projetos[0]->tipo !== "servico" && $necessidadeComprasObj->necessidade_x_projetos[0]->tipo !== "pedido"){
                $codigo_produto = $necessidadeComprasObj->produto_codigo;
                $descricao = $necessidadeComprasObj->produto_detalhes->descricao;
            }else{
                $codigo_produto = empty($necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->tecido)? $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->produto->codigo_produto : $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->codigo_produto_acabado; 
                $descricao = empty($necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->tecido)? $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->produto->descricao : $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->produto_acabado->descricao;
            }
            if($fields['tipo'] === "servico"){
                $estoque[$necessidadeComprasObj->produto_codigo] = [
                    'estoque' => 0,
                    'estoque_compras' => 0,
                ];
                $necessidade = $necessidadeComprasObj->saldo;
                $fator_conversao = 1;
                $total='';
                $servicos = [];
        
                $custo_servicos_total = 0;
                $custo_gerencial_servicos_total = 0;   

                if($necessidadeComprasObj->necessidade_x_projetos[0]->tipo === "servico"){
                                        
                    $fichaTecnicaObjeto = FichaTecnicaProduto::select();
                    $fichaTecnicaObjeto->where('codigo_produto',$codigo_produto);
                    $fichaTecnicaObjeto = $fichaTecnicaObjeto->first();

                    if(empty($fichaTecnicaObjeto)){
                        $fichaTecnicaObjeto = FichaTecnicaProduto::select();
                        $fichaTecnicaObjeto->where('codigo_produto',$necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->produto->codigo_produto);
                        $fichaTecnicaObjeto = $fichaTecnicaObjeto->first();
                    }
    
                    $fichaTecnicaObjeto->servicos->each(function ($servico) use (&$servicos, &$custo_servicos_total, &$custo_gerencial_servicos_total, &$preco_servicos){        
    
                        $custo_gerencial = empty($servico->preco->compra_real)? $servico->preco->preco_real / 1.43 : $servico->preco->compra_real;             
                        $custo_gerencial_servicos_total += $custo_gerencial;
                        $preco_servicos = $custo_gerencial_servicos_total;     
                    });          
                    $necessidade = $necessidadeComprasObj->quantidade;
                    $total = $preco_servicos * ($necessidade / $fator_conversao);

                }else{
                    if(!empty($necessidadeComprasObj->necessidade_x_projetos[0]->lancamento_projeto_produtos_id)){
                        $fichaTecnicaObjeto = FichaTecnicaProduto::select();
                        $fichaTecnicaObjeto->where('codigo_produto',$codigo_produto);
                        $fichaTecnicaObjeto = $fichaTecnicaObjeto->first();
    
                        $fichaTecnicaObjeto->servicos->each(function ($servico) use (&$servicos, &$custo_servicos_total, &$custo_gerencial_servicos_total, &$preco_servicos){        
    
                            $custo_gerencial = empty($servico->preco->compra_real)? $servico->preco->preco_real / 1.43 : $servico->preco->compra_real;             
                            $custo_gerencial_servicos_total += $custo_gerencial;
                            $preco_servicos = $custo_gerencial_servicos_total;     
                         });          
                        $necessidade = $necessidadeComprasObj->quantidade;
                        $total = $preco_servicos * ($necessidade / $fator_conversao);
                    }else{
                        $fichaTecnicaObjeto = FichaTecnicaProduto::select();
                        $fichaTecnicaObjeto->where('codigo_produto',$codigo_produto);
                        $fichaTecnicaObjeto = $fichaTecnicaObjeto->first();
    
                        $fichaTecnicaObjeto->servicos->each(function ($servico) use (&$servicos, &$custo_servicos_total, &$custo_gerencial_servicos_total, &$preco_servicos){        
    
                            $custo_gerencial = empty($servico->preco->compra_real)? $servico->preco->preco_real / 1.43 : $servico->preco->compra_real;             
                            $custo_gerencial_servicos_total += $custo_gerencial;
                            $preco_servicos = $custo_gerencial_servicos_total;     
                        });          
                        $necessidade = $necessidadeComprasObj->quantidade;
                        $total = $preco_servicos * ($necessidade / $fator_conversao);
                    }
                }
                
                $unidade_compra = $necessidadeComprasObj->produto_detalhes->produtoNasajon->unidade;
                $fator_conversao = 1;
                if(in_array($necessidadeComprasObj->produto_codigo, $this->servico_minimo_500)){
                    if($necessidadeComprasObj->saldo < 500){
                        $total = $preco_servicos * 500;
                        $preco_compra = parserValor($total/$necessidadeComprasObj->saldo);
                        $total = parserValor(parserNumber($preco_compra) * $necessidadeComprasObj->saldo);
                    }else{
                        $preco_compra = parserValor($preco_servicos);
                        $total = parserValor($preco_servicos * $necessidade); 
                    }
                }else if(in_array($necessidadeComprasObj->produto_codigo, $this->servico_minimo_350)){
                    if($necessidadeComprasObj->saldo < 350){
                        $total = $preco_servicos * 350;
                        $preco_compra = parserValor($total/$necessidadeComprasObj->saldo);
                        $total = parserValor(parserNumber($preco_compra) * $necessidadeComprasObj->saldo);
                    }else{
                        $preco_compra = parserValor($preco_servicos);
                        $total = parserValor($preco_servicos * $necessidade); 
                    }
                }else{
                    $preco_compra = parserValor($preco_servicos);
                    $total = parserValor($preco_servicos * $necessidade);
                }
            }else if($fields['tipo'] === "pedido"){
                $fichaTecnicaObjeto = FichaTecnicaProduto::select();
                $fichaTecnicaObjeto->where('codigo_produto',$codigo_produto);
                $fichaTecnicaObjeto = $fichaTecnicaObjeto->first();
                
                $estoque[$necessidadeComprasObj->produto_codigo] = [
                    'estoque' => 0,
                    'estoque_compras' => 0,
                ];
                
                $necessidade = $necessidadeComprasObj->saldo;
                $fator_conversao = 1;
                $total='';  
        
                $precoObj = Preco::select()->where('codigo_produto', 'MDOES001')->first();

                $preco_servicos = empty($precoObj->compra_real)? $precoObj->preco_real / 1.43 : $precoObj->compra_real;

                $necessidade = $necessidadeComprasObj->quantidade;
                $total = $preco_servicos * ($necessidade / $fator_conversao);

                $unidade_compra = $necessidadeComprasObj->produto_detalhes->produtoNasajon->unidade;
                $fator_conversao = 1;
            
                $preco_compra = parserValor($preco_servicos);
                $total = parserValor($preco_servicos * $necessidade);
            }else{
                $estoque = $this->estoqueProduto([$necessidadeComprasObj->produto_codigo], "", true);
                $remessas = $this->remessa([$necessidadeComprasObj], true);
                $saldo = $this->saldoNecessidadeCompras($necessidadeComprasObj, $remessas);
                if(empty($estoque[$necessidadeComprasObj->produto_codigo]['estoque'])){
                    $estoque[$necessidadeComprasObj->produto_codigo] = [
                        'estoque' => 0,
                        'estoque_compras' => 0
                    ];
                }
                $necessidade = $saldo['saldo'] - $estoque[$necessidadeComprasObj->produto_codigo]['estoque'] - $estoque[$necessidadeComprasObj->produto_codigo]['estoque_compras'] ;

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
                        $query_unidade_conversao->where('codigo_unidadepadrao', 'ilike', $necessidadeComprasObj->produto_detalhes->produtoNasajon->unidade);
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
                    $unidade_compra = $necessidadeComprasObj->produto_detalhes->produtoNasajon->unidade;
                    $fator_conversao = 1;
                    $total = '';
                }
            }

            $retorno [] = [
                'id_necessidade_compras' => encrypt($id_necessidade_compras),
                'necessidade_compras' => $id_necessidade_compras,
                'codigo' => $codigo_produto,
                'produto' => $necessidadeComprasObj->produto_detalhes->descricao,
                'descricao' => $descricao,
                'unidade' => $necessidadeComprasObj->produto_detalhes->produtoNasajon->unidade,
                'necessidade' => str_replace(".","", parserQtd($necessidade)),
                'valor_unitario' => $preco_compra,
                'quantidade' => str_replace(".","", parserQtd($necessidade)),
                'estoque' => empty($estoque[$necessidadeComprasObj->produto_codigo]['estoque'])? '' : parserValor($estoque[$necessidadeComprasObj->produto_codigo]['estoque']),
                'compras' => empty($estoque[$necessidadeComprasObj->produto_codigo]['estoque_compras'])? '' : parserValor($estoque[$necessidadeComprasObj->produto_codigo]['estoque_compras']),
                'total' => $estoque[$necessidadeComprasObj->produto_codigo]['estoque'] + $estoque[$necessidadeComprasObj->produto_codigo]['estoque_compras'],
                'valor_total' => $total,
                'fornecedor_cnpj_cpf' => $necessidadeComprasObj->fornecedor_cnpj_cpf,
                'unidade_compra' => $unidade_compra,
                'fator_conversao' => parserValor($fator_conversao),
                'necessidade_compra' => parserQtd($necessidade / $fator_conversao),
                'ficha_tecnnica_id' => empty($fichaTecnicaObjeto)? '' : $fichaTecnicaObjeto->id,
            ];
        }
        //

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function gerarPedidoCompra(GeracaoPedidoCompraNecessidadeCompraRequest $request){
        $fields = $request->only(['id_necessidade_compras', 'fornecedor_cnpj_cpf', 'todos_produtos', 'var_itens', 'estabelecimento', 'condicao_pagamento', 'tipo', 'observacao_nota', 'data_previsao_entrega']);

        if($fields['tipo'] == 'pedido'){
            $query_fornecedor = FornecedorNasajon::select();
            $query_fornecedor->where(DB::raw('TRIM(CONCAT(nome,\' - \', cnpj_cpf))'), 'ILIKE', trim($fields['fornecedor_cnpj_cpf']));
            $result_fornecedor = $query_fornecedor->first();

            $fields['fornecedor_cnpj_cpf'] = $result_fornecedor->cnpj_cpf;
        }

        $valor_total = 0;

        $produtos = [];
        foreach($fields['var_itens'] as $item){
            if(parserNumber($item[1]) <= 0 || parserNumber($item[2]) <= 0){
                $error = [
                    'status' => 'error', /// success, error
                    'message' => 'Há valor(es) zerado(s), favor verificar.', /// mensagem
                    'error' => '',
                    'response' => []
                ];

                return response()->json($error, 442);
            }

            $necessidadeComprasObj = NecessidadeCompras::find($item[0]);

            if( parserNumber($item[2]) < parserNumber($item[4])){
                $error = [
                    'status' => 'error', /// success, error
                    'message' => 'A(s) quantidade(s) menor que a necessidade, favor verificar.', /// mensagem
                    'error' => '',
                    'response' => []
                ];

                return response()->json($error, 442);
            }

            if(!empty($necessidadeComprasObj->preco_detalhes)){
                $preco_compra = empty($necessidadeComprasObj->preco_detalhes->compra_real)? '': $necessidadeComprasObj->preco_detalhes->compra_real;
                $preco_compra = parserValor($preco_compra);
            }else{
                $preco_compra = '';
            }

            $valor_total = $valor_total + (parserNumber($item[1]) * parserNumber($item[2]));

            if(!empty($necessidadeComprasObj->necessidade_x_projetos[0]->lancamento_projeto_produtos_id)){
                $lancamento_projeto_produtos_id = $necessidadeComprasObj->necessidade_x_projetos[0]->lancamento_projeto_produtos_id;
                $lancamento_projeto_tecidos_id = '';
                $lancamento_projeto_faccoes_id = '';
                $faccao_id = '';
            }else if(!empty($necessidadeComprasObj->necessidade_x_projetos[0]->lancamento_projeto_tecidos_id)){
                $lancamento_projeto_produtos_id = '';
                $lancamento_projeto_tecidos_id = $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->lancamento_projeto_tecidos_id;
                $lancamento_projeto_faccoes_id = '';
                $faccao_id = '';
            }else if(!empty($necessidadeComprasObj->necessidade_x_projetos[0]->lancamento_projeto_faccoes_id)){
                $lancamento_projeto_produtos_id = $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->lancamento_projeto_produtos_id;
                $lancamento_projeto_tecidos_id = $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->lancamento_projeto_tecidos_id;
                $lancamento_projeto_faccoes_id = $necessidadeComprasObj->necessidade_x_projetos[0]->lancamento_projeto_faccoes_id;
                $faccao_id = $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->faccao_id;
            }else{
                $lancamento_projeto_produtos_id = '';
                $lancamento_projeto_tecidos_id = '';
                $lancamento_projeto_faccoes_id = '';
                $faccao_id = '';
            }

            if(empty($necessidadeComprasObj->necessidade_x_projetos[0]->tipo === "servico")){
                $codigo_produto = $necessidadeComprasObj->produto_codigo;
                $descricao = $necessidadeComprasObj->produto_detalhes->descricao;
                $produto_uuid = $necessidadeComprasObj->produto_detalhes->produtoNasajon->produto;
            }else{
               $codigo_produto = empty($necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->tecido)? $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->produto->codigo_produto : $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->codigo_produto_acabado; 
               $descricao = empty($necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->tecido)? $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->produto->descricao : $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->produto_acabado->descricao;
               $produto_uuid = empty($necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->tecido)? $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->produto->produto_detalhes->produtoNasajon->produto : $necessidadeComprasObj->necessidade_x_projetos[0]->produto_acabado_projeto->produto_acabado->produtoNasajon->produto;
            }
            
            $produtos [] = [
                'codigo' => $codigo_produto,
                'id_necessidades_compras' => $necessidadeComprasObj->id,
                'produto_uuid' => $produto_uuid,
                'lancamento_projeto_produtos_id' => $lancamento_projeto_produtos_id,
                'lancamento_projeto_tecidos_id' => $lancamento_projeto_tecidos_id,
                'lancamento_projeto_faccoes_id' => $lancamento_projeto_faccoes_id,
                'faccao_id' => $faccao_id,
                'produto_unidade'=> $item[6],
                'valor_unitario_pedido' => parserNumber($item[1]),
                'quantidade_pedido' => parserNumber($item[2]),
                'valor_unitario_original' => parserNumber($item[3]),
                'quantidade_original' => parserNumber($item[4]),
                'valor_total_original' => parserNumber($item[5]),
            ];
        }

        if($fields['tipo'] === "servico"){
            $necessidadeComprasObj = NecessidadeCompras::find($fields['var_itens'][0][0]);

            $id_projeto = $necessidadeComprasObj->necessidade_x_projetos[0]->lancamento_projetos_id;
            $numero_pedido_cliente = $necessidadeComprasObj->necessidade_x_projetos[0]->projeto->pedido;
            $data = !empty($fields['data_previsao_entrega'])? Carbon::createFromFormat('d/m/Y', $fields['data_previsao_entrega'])->setTime(0,0,0)->format('Y-m-d') : $necessidadeComprasObj->necessidade_x_projetos[0]->projeto->data_previsao_entrega;
            $observacao = "Projeto: ".$necessidadeComprasObj->necessidade_x_projetos[0]->projeto->id." - ".$necessidadeComprasObj->necessidade_x_projetos[0]->projeto->nome_projeto;
        }else if($fields['tipo'] === "pedido"){
            $id_projeto = $necessidadeComprasObj->necessidade_x_projetos[0]->pedido_id;
            $numero_pedido_cliente = "";
            $data = !empty($fields['data_previsao_entrega'])? Carbon::createFromFormat('d/m/Y', $fields['data_previsao_entrega'])->setTime(0,0,0)->format('Y-m-d') : $necessidadeComprasObj->necessidade_x_projetos[0]->detalhesPedido->data_previsao_entrega;
            $observacao = "Pedido: ".$necessidadeComprasObj->necessidade_x_projetos[0]->pedido_id;
        }else{
            $id_projeto = "";
            $numero_pedido_cliente = "";
            $data = date("Y-m-d");
            $observacao = "";
        }

        $condicoesPagamentoWebObj = CondicoesPagamentoWeb::select()->where('descricao', 'ilike', $fields['condicao_pagamento'])->where('nasajon', true)->where('ativo', true)->first();

        if(!empty($fields['observacao_nota'])){
            $observacao_nota = $fields['observacao_nota'];
        }else{
            $observacao_nota = '';
        }

        $dados_pedido = [
            'id_projeto' => $id_projeto,
            'estabelecimento' => str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT),
            'fornecedor_cnpj_cpf' => $fields['fornecedor_cnpj_cpf'],
            'condicao_pagamento' => $condicoesPagamentoWebObj->id,
            'tipo' => $fields['tipo'],
            'numero_pedido_cliente' => $numero_pedido_cliente,
            'data_pedido' => $data,
            'valor_total' => $valor_total,
            'observacao' => $observacao,
            'observacao_nota' => $observacao_nota
        ];

        $pedidosComprasNasajonControllerObj = new PedidosComprasNasajonController;

        $retorno_pedido_compra = $pedidosComprasNasajonControllerObj->processoGeracaoPedidoComprasNasajonNecessidadeCompra($dados_pedido, $produtos);

        if(!empty($retorno_pedido_compra)){
            return response()->json($retorno_pedido_compra, 442);
        }else{
            foreach($produtos as $produto){;
                $necessidadeComprasObj = NecessidadeCompras::find($produto['id_necessidades_compras']);
                if(!empty($produto['lancamento_projeto_faccoes_id'])){
                    $servicos = LancamentoProjetoFaccao::select();
                    if(!empty($produto['lancamento_projeto_tecidos_id'])){
                        $servicos->where('lancamento_projeto_tecidos_id', $produto['lancamento_projeto_tecidos_id']);
                    }else{
                        $servicos->where('lancamento_projeto_produtos_id', $produto['lancamento_projeto_produtos_id']);
                    }
                    $servicos->where('faccao_id', $produto['faccao_id']);
                    $servicos = $servicos->get();
                    foreach($servicos as $servico){
                        $necessidadeComprasXProjetoObj = NecessidadeComprasXProjeto::select();
                        $necessidadeComprasXProjetoObj->where('lancamento_projetos_id', $id_projeto);
                        $necessidadeComprasXProjetoObj->where('lancamento_projeto_faccoes_id', $servico->id);
                        $necessidadeComprasXProjetoObj = $necessidadeComprasXProjetoObj->get();
                        foreach($necessidadeComprasXProjetoObj as $value){
                            $value->deleted_by = Auth::id();
                            $value->save();
                            $value->delete();
                        }
                    }
                }
                $necessidadeComprasObj->deleted_by = Auth::id();
                $necessidadeComprasObj->save();
                $necessidadeComprasObj->delete();
            }

            if($fields['tipo'] === "servico"){
                $remessaItensControllerObj = new RemessaItensController;
                $status = $remessaItensControllerObj->verificarStatusProjeto($id_projeto);
 
                if($status === 6 ){
                    $lancamentoProjetoObj = LancamentoProjeto::find($id_projeto);
                    $lancamentoProjetoObj->status = $status;
                    $lancamentoProjetoObj->updated_by = Auth::id();
                    $lancamentoProjetoObj->save();

                    $lancamentoProjetoControllerObj = new LancamentoProjetoController;

                    $lancamentoProjetoControllerObj->gravarHistoricoProjeto($id_projeto, 'em_producao', 'Projeto: '.$id_projeto, Auth::id());
            
                    $remessaProjetoObj = EnvioProjetoFaccao::select()->where('lancamento_projetos_id', $id_projeto);
                    $remessaProjetoObj->deleted_by = Auth::id();
                    $remessaProjetoObj->delete();
                }  
            }else if($fields['tipo'] === "pedido"){
                $envioProjetoFaccao = new EnvioProjetoFaccao;
                $envioProjetoFaccao->pedido_id = $id_projeto;
                $envioProjetoFaccao->created_by = Auth::id();
                $envioProjetoFaccao->save();

                $FaccaoObj = Faccao::select()->where('cod_fornecedor', $result_fornecedor->cnpj_cpf)->first();

                $faccao_id = $FaccaoObj->id;

                foreach($produtos as $produto){
                    $pedidoItemPortal = PedidoItemPortal::select()->where('pedido', $id_projeto)->where('cod_produto', $produto['codigo'])->first();

                    $pedidoItemPortal->faccaos_id = $faccao_id;
                    $pedidoItemPortal->save();
                }
            }

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => ''
            ];
            return response()->json($response);
        }
    }

    public function consultaNecessidadeCompraProjeto($id_projeto){
        $necessidadeComprasProjetoObj = NecessidadeComprasXProjeto::select()->withTrashed();
        $quantidade_necessidade_compras = $necessidadeComprasProjetoObj->count();
        $necessidadeComprasProjetoObj->with(['necessidade_compras_detalhes']);
        $necessidadeComprasProjetoObj->where('lancamento_projetos_id', $id_projeto);
        $necessidadeComprasProjetoObj->whereHas('necessidade_compras_detalhes', function($query){
            $query->whereNull('deleted_at');
        });
        $necessidadeComprasProjetoObj->whereNull('deleted_at');
        $quantidade_necessidade_compras = $necessidadeComprasProjetoObj->count();
        $necessidadeComprasProjetoObj = $necessidadeComprasProjetoObj->get();
        $necessidades_compras = [];
        foreach($necessidadeComprasProjetoObj as $necessidade_compras_x_projeto){
            $estoque = $this->estoqueProduto([$necessidade_compras_x_projeto->necessidade_compras_detalhes->produto_codigo], "", true);
            if(empty($estoque[$necessidade_compras_x_projeto->necessidade_compras_detalhes->produto_codigo])){
                $estoque[$necessidade_compras_x_projeto->necessidade_compras_detalhes->produto_codigo] =[
                    'estoque' => 0,
                    'estoque_compras' => 0
                ]; 
            }
            $necessidade_compras = floatval($necessidade_compras_x_projeto->necessidade_compras_detalhes->saldo) - (floatval($estoque[$necessidade_compras_x_projeto->necessidade_compras_detalhes->produto_codigo]['estoque']) + floatval($estoque[$necessidade_compras_x_projeto->necessidade_compras_detalhes->produto_codigo]['estoque_compras']));
            
            if($necessidade_compras > 0){
                $necessidades_compras[] = [
                    'necessidade_compras' => $necessidade_compras,
                    'estoque' => $estoque[$necessidade_compras_x_projeto->necessidade_compras_detalhes->produto_codigo],
                    'saldo' => $necessidade_compras_x_projeto->necessidade_compras_detalhes->saldo,
                ];
            }
        }

        $tamanho_necessidades_compras = count($necessidades_compras);

        if($tamanho_necessidades_compras == 0){
            $posicao = "verde";
        }else if ($tamanho_necessidades_compras == $quantidade_necessidade_compras){
            $posicao = "vermelho";
        }else{
            $posicao = "amarelo";
        }

        $retorno = [
            'posicao' => $posicao,
            'necessidade_compra' => $necessidades_compras,
        ];

        return $retorno;
    }

    public function verificarUnidadeDoInsumo($codigo_produto){
        $retorno = "";

        $query = ProdutoEspecificacao::select();
        $query->where('codigo_produto', $codigo_produto);
        $query->where('linha', 'ilike', 'INSUMO');
        $result = $query->first();

        if(!empty($result)){
            if(!in_array($result->produtoNasajon->unidade, $this->unidades_permitida_insumo)){
                $retorno = "A unidade padrão do insumo está incorreta. Favor verificar com o setor responsável.";
            }
        }

        return $retorno;
    }
    
    public function viewDetalhesProjeto(Request $request){
        $projeto_id = $request->only('projeto_id')['projeto_id'];

        $query = NecessidadeCompras::select();

        $query->whereHas('necessidade_x_projetos', function($query) use($projeto_id){
            $query->where('lancamento_projetos_id', $projeto_id);
        });

        $result = $query->get();

        $necessidades_compras = [];

        foreach($result as $necessidade_compras){
            $estoque = $this->estoqueProduto([$necessidade_compras->produto_codigo], "", true);
            if(empty($estoque[$necessidade_compras->produto_codigo])){
                $estoque[$necessidade_compras->produto_codigo] = [
                    'estoque' => 0,
                    'estoque_compras' => 0
                ];
            }
            $necessidade = $necessidade_compras->saldo - $estoque[$necessidade_compras->produto_codigo]['estoque'] - $estoque[$necessidade_compras->produto_codigo]['estoque_compras'] ;
            $total = $estoque[$necessidade_compras->produto_codigo]['estoque'] + $estoque[$necessidade_compras->produto_codigo]['estoque_compras'];

            if(!empty($necessidade_compras->necessidade_x_projetos[0])){
                if($necessidade_compras->necessidade_x_projetos[0]->tipo === "servico_produto" || $necessidade_compras->necessidade_x_projetos[0]->tipo === "servico_tecido"){
                    $tipo = "servico";
                }else{
                    $tipo = "materia_prima";
                }
            }else{
                $tipo = "materia_prima";
            }

            if($necessidade > 0 || $tipo === "servico"){
                $quantidade_projetos = NecessidadeComprasXProjeto::select('necessidades_compras_id', 'lancamento_projetos_id')->where('necessidades_compras_id', $necessidade_compras->id)->distinct()->get()->count();

                if($tipo === "servico"){
                    $estoque_valor = '';
                    $compras_valor = '';
                    $total = '';
                    $necessidade_valor = parserValor($necessidade_compras->saldo);
                    $alteracao_fornecedor = false;
                    $numero_projeto = $necessidade_compras->necessidade_x_projetos[0]->projeto->id;
                    $nome_projeto = $necessidade_compras->necessidade_x_projetos[0]->projeto->nome_projeto;
                }else{
                    $estoque_valor = empty($estoque[$necessidade_compras->produto_codigo]['estoque'])? '' : parserValor($estoque[$necessidade_compras->produto_codigo]['estoque']);
                    $compras_valor = empty($estoque[$necessidade_compras->produto_codigo]['estoque_compras'])? '' : parserValor($estoque[$necessidade_compras->produto_codigo]['estoque_compras']);
                    $necessidade_valor = parserValor($necessidade);
                    $alteracao_fornecedor = true;
                    $numero_projeto = '';
                    $nome_projeto = '';
                }
                $necessidades_compras [] = [
                    'codigo' => $necessidade_compras->produto_codigo,
                    'produto' => empty($necessidade_compras->produto_detalhes)? '' : $necessidade_compras->produto_detalhes->descricao,
                    'fornecedor' => empty($necessidade_compras->fornecedor_detalhes)? '' : $necessidade_compras->fornecedor_detalhes->nome.' - '.$necessidade_compras->fornecedor_cnpj_cpf,
                    'estoque' => $estoque_valor,
                    'compras' => $compras_valor,
                    'necessidade' => $necessidade_valor,
                ];
            }
        }

        return view('programs.necessidade_compras.modal.detalhes_projeto')->with(['necessidades_compras' => $necessidades_compras]);
    }

    function saldoNecessidadeCompras($necessidade_compras, $remessas, $filtro = ['num_projeto' => ""], $anterior = 0){
        $saldo = 0;
        $quantidade_projeto = 0;
        $projetos = [];
        $remessa = 0;
        if(!empty($anterior)){
            $saldo = $anterior["saldo"];
            $remessa = $anterior["remessa"];
        }
        foreach($necessidade_compras->necessidade_x_projetos as $necessidade_x_projetos){
            if(is_numeric($filtro['num_projeto']) || empty($filtro['num_projeto'])){
                if($necessidade_x_projetos->lancamento_projetos_id === intval($filtro['num_projeto']) || empty($filtro['num_projeto'])){
                    if(!empty($necessidade_x_projetos->projeto)){
                        if(!empty($necessidade_x_projetos->tecido_projeto)){
                            $saldo += $necessidade_x_projetos->tecido_projeto->consumo_total;
                            if(!empty($remessas[$necessidade_x_projetos->tecido_projeto->codigo_produto][$necessidade_x_projetos->lancamento_projetos_id]) && !in_array($necessidade_x_projetos->lancamento_projetos_id, $projetos)){
                                $saldo -= $remessas[$necessidade_x_projetos->tecido_projeto->codigo_produto][$necessidade_x_projetos->lancamento_projetos_id];
                                $remessa += $remessas[$necessidade_x_projetos->tecido_projeto->codigo_produto][$necessidade_x_projetos->lancamento_projetos_id];
                            }   
                        }else if(!empty($necessidade_x_projetos->insumo_projeto)){
                            $saldo += $necessidade_x_projetos->insumo_projeto->consumo_total;
                            if(!empty($remessas[$necessidade_x_projetos->insumo_projeto->codigo_produto][$necessidade_x_projetos->lancamento_projetos_id]) && !in_array($necessidade_x_projetos->lancamento_projetos_id, $projetos)){
                                $saldo -= $remessas[$necessidade_x_projetos->insumo_projeto->codigo_produto][$necessidade_x_projetos->lancamento_projetos_id];
                                $remessa += $remessas[$necessidade_x_projetos->insumo_projeto->codigo_produto][$necessidade_x_projetos->lancamento_projetos_id];
                            }   
                        }
                        if(!in_array($necessidade_x_projetos->lancamento_projetos_id, $projetos)){
                            $projetos [] = $necessidade_x_projetos->lancamento_projetos_id;
                            $quantidade_projeto++; 
                        }           
                    }
                }
            }
        }
        
        $retorno = [
            'saldo' => $saldo,
            'quantidade_projeto' => $quantidade_projeto,
            'remessa' => $remessa
        ];

        return $retorno;
    }

    public function remessa($necessidade_compras, $item_unico = false){
        $projetos = [];
 
        foreach($necessidade_compras as $necessidade_compra){
            foreach($necessidade_compra->necessidade_x_projetos as $necessidade_x_projetos){
                if(!empty($necessidade_x_projetos->projeto)){
                    if(!in_array($necessidade_x_projetos->lancamento_projetos_id, $projetos)){
                        $projetos [] = $necessidade_x_projetos->lancamento_projetos_id;
                    }                
                }
            }
        }

        $produtos_codigo = $item_unico? [$necessidade_compras[0]->produto_codigo] : $necessidade_compras->pluck('produto_codigo') ;

        $remessas = RemessaProduto::select()->whereIn('produto_codigo', $produtos_codigo)
            ->whereIn('lancamento_projetos_id', $projetos)
            ->with(['pedidoRemessaNasajon.itens_pedido' => function($query) use($produtos_codigo){
                $query->whereIn('produto_codigo', $produtos_codigo);
            }])
            ->get();

        $valor_remessa = [];
        foreach($remessas as $remessa){
            if(empty($valor_remessa[$remessa->produto_codigo][$remessa->lancamento_projetos_id] )){
                $valor_remessa[$remessa->produto_codigo][$remessa->lancamento_projetos_id] = 0;
            }
            if(!empty($remessa->pedidoRemessaNasajon)){
                if($remessa->pedidoRemessaNasajon->situacao_descricao !== "Cancelado"){
                    if($remessa->pedidoRemessaNasajon->itens_pedido->count() > 0){
                        for($i = 0; $i < $remessa->pedidoRemessaNasajon->itens_pedido->count(); $i++){
                            if($remessa->pedidoRemessaNasajon->itens_pedido[$i]->produto_codigo === $remessa->produto_codigo){
                                $valor_remessa[$remessa->produto_codigo][$remessa->lancamento_projetos_id]  += empty($remessa->pedidoRemessaNasajon->itens_pedido[$i]->quantidade_faturada)?$remessa->pedidoRemessaNasajon->itens_pedido[$i]->quantidadecomercial : $remessa->pedidoRemessaNasajon->itens_pedido[$i]->quantidade_faturada;
                            }
                        }
                    }
                }
            }else{
                $valor_remessa[$remessa->produto_codigo][$remessa->lancamento_projetos_id]  += $remessa->quantidade_enviada;
            }
        }
        return $valor_remessa;
    }

    public function adicionarAtravesPedido(PedidoPortal $Pedido){

        foreach($Pedido->itens_pedido as $item){
            $necessidadeComprasObj = NecessidadeCompras::select()
                ->where('produto_codigo', $item->cod_produto)
                ->whereHas('necessidade_x_projetos', function($query) use($Pedido){
                    $query->where('pedido_id', $Pedido->id);
                })
                ->first();
            if(empty($necessidadeComprasObj)){
                $necessidadeComprasObj = $this->adicionar($item->cod_produto, '', $item->quantidade);

                $necessidadeComprasXProjetoObj = $this->adicionarNecessidadeComprasXProjeto($necessidadeComprasObj->id, $Pedido->id, "pedido", $item->id);
            }else{
                $necessidadeComprasObj->quantidade = $item->quantidade;
                $necessidadeComprasObj->saldo = $item->quantidade;
                $necessidadeComprasObj->updated_by = Auth::id();
                $necessidadeComprasObj->save();
            }

            $fichaTecnicaProdutoObj = FichaTecnicaProduto::select();
            $fichaTecnicaProdutoObj->where('codigo_produto', $item->cod_produto);
            $fichaTecnicaProdutoObj = $fichaTecnicaProdutoObj->first();

            if(empty($fichaTecnicaProdutoObj)){
                $fichaTecnicaProdutoObj = new FichaTecnicaProduto;
                $fichaTecnicaProdutoObj->codigo_produto = $item->cod_produto;
                $fichaTecnicaProdutoObj->created_by = Auth::id();
                $fichaTecnicaProdutoObj->save();

                $fichaTecnicaProdutoTecidoObj = new FichaTecnicaProdutoTecido;
                $fichaTecnicaProdutoTecidoObj->ficha_tecnica_produtos_id = $fichaTecnicaProdutoObj->id;
                $fichaTecnicaProdutoTecidoObj->codigo_produto = $item->codigo_tecidos_base;
                $fichaTecnicaProdutoTecidoObj->consumo_unitario = 1;
                $fichaTecnicaProdutoTecidoObj->created_by = Auth::id();
                $fichaTecnicaProdutoTecidoObj->save();

                $fichaTecnicaProdutoTecidoObj = new FichaTecnicaProdutoTecido;
                $fichaTecnicaProdutoTecidoObj->ficha_tecnica_produtos_id = $fichaTecnicaProdutoObj->id;
                $fichaTecnicaProdutoTecidoObj->codigo_produto = $item->codigo_desenho;
                $fichaTecnicaProdutoTecidoObj->consumo_unitario = 1;
                $fichaTecnicaProdutoTecidoObj->created_by = Auth::id();
                $fichaTecnicaProdutoTecidoObj->save();

                $fichaTecnicaProdutoServicoObj = new FichaTecnicaProdutoServico;
                $fichaTecnicaProdutoServicoObj->ficha_tecnica_produtos_id = $fichaTecnicaProdutoObj->id;
                $fichaTecnicaProdutoServicoObj->codigo_produto = 'MDOES001';
                $fichaTecnicaProdutoServicoObj->created_by = Auth::id();
                $fichaTecnicaProdutoServicoObj->save();
            }
        }
    }

    public function modalListarCompras(Request $request){
        $fields = $request->only(['produtos']);


        $compras_nasajon = ComprasNasajon::where('cod_produto', $fields['produtos'])
        ->whereIn('situacao', ['Aguardando Documento', 'Parcialmente Liquidado'])
        ->distinct()
        ->get();

        if($compras_nasajon->isEmpty()){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ],422);
        }

        $retorno = [];
        $estabelecimentos = returnEmpresasNasajonView();

        $compras_nasajon->each(function($query) use (&$retorno,$estabelecimentos){
            $retorno[] = [
                'estabelecimento' => $estabelecimentos[intval($query->estabelecimento)],
                'pedido' => $query->numero_pedido,
                'produto' => $query->descricao_produto,
                'data_compra' => (!empty($query->data_compra)) ? Carbon::parse($query->data_compra)->format('d/m/Y') : '',
                'previsao_entrega' => (!empty($query->previsao_entrega)) ? Carbon::parse($query->previsao_entrega)->format('d/m/Y') : '',
                'fornecedor' => $query->fornecedor_nome,
                'quantidade' => parserQtd($query->quantidade),
                'id_nota' => encrypt($query->id_nota)
            ];
        });

        return view('programs.necessidade_compras.modal.listagem_compras')->with(['retorno' => $retorno]);
    }   
}