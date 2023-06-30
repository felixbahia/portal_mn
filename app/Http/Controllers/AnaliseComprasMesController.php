<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Http\Requests\AnaliseComprasMesRequest;
use App\ProdutoEspecificacao;
use App\Movimentacao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\PedidosVendaNasajon;

class AnaliseComprasMesController extends Controller
{
    private $cfop_remessas = ["5901","5910","5911","5912","5924","6901","6910","6911","6912","6924"];
    // private $cfop_vendas = ["5922", "5949", "6108", "6110", "6119", "6123", "6106", "5123", "6118", "5118",
    // "5122", "5551", "6551", "5102", "6102", "5106", "5101", "6101"];
    private $cfop_vendas = ["5922", "6108", "6110", "6119", "6123", "6106", "5123", "6118", "5118",
    "5122", "5551", "6551", "5102", "6102", "5106", "5101", "6101", '5104', '6104'];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware(['auth']);
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AnaliseComprasMes") === false){
            return abort(403);
        }
        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);
        $request->session()->flash('model', 'App\AnaliseComprasMes');
        return view("programs.analise_compra_mes.index")->with('estabelecimentos',$estabelecimentos);
    }

    private function cnpjINtercompany(){
        $cnpj_excluir[] = '05075884000167';
        $cnpj_excluir[] = '05075884000248';
        $cnpj_excluir[] = '06311274000269';
        $cnpj_excluir[] = '06311274000340';
        $cnpj_excluir[] = '08';
        $cnpj_excluir[] = '07';
        $cnpj_excluir[] = '06311274000501';
        $cnpj_excluir[] = '06311274000420';
        return $cnpj_excluir;
    }

    public function filter(AnaliseComprasMesRequest $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $busca = $request->only('grupo', 'codigo','nome', 'marca','linha', 'estabelecimento' , 'qtd_mes_estoque', 'qtd_mes_media','compra');

        $produtoQuery = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['estoque' => function($query) use ($busca){
            if(isset($busca['estabelecimento'])){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            }else{
                $query->select(DB::raw('codigo_produto, sum(compras_aberto) as compras_aberto, sum(estoque) as estoque'))->groupBy('codigo_produto');
            }
        }])
        ->with(['compras' => function($query) use ($busca){
            if(isset($busca['estabelecimento'])){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            }
            $query->whereIn('situacao', ['Aberto', 'Aguardando Documento','Parcialmente Liquidado']);
            $query->where(DB::raw("case
                when situacao = 'Parcialmente Liquidado' then
                    quantidade_restante
                else
                    quantidade 
                end"),'>',0);
        }])
        ->with(['movimentacao' => function($query) use ($busca){    
            $date = Carbon::now();
            $data_busca = $date->subMonth($busca['qtd_mes_media'] - 1)->format('Y-m-01');
            
            if(isset($busca['estabelecimento'])){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            }
            
            $query->where('data_movimentacao','>=',$data_busca)
            ->where(function($query){
                $query->oRwhereIn('cfop', $this->cfop_vendas)
                ->oRwhereIn('cfop',$this->cfop_remessas);
            })
            ->where('documento', '!=', ' ')
            ->where('sinal', 'ilike', 'saida')
            ->whereNotNull('data_movimentacao')
            ->whereNotIn("cliente_codigo", $this->cnpjINtercompany());
        }]);

        if(
            empty($busca['grupo']) &&
            empty($busca['codigo']) &&
            empty($busca['nome']) &&
            empty($busca['marca']) &&
            empty($busca['linha']) &&
            empty($busca['codigo']) &&
            !empty($busca['estabelecimento'])
        ){
            $produtoQuery->orWhereHas('estoque',function($query) use ($busca){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            });
            $produtoQuery->orWhereHas('vendas',function($query) use ($busca){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            });
        }
        if(!empty($busca['marca'])){
            $produtoQuery->where('marca', 'ilike', "%" . strtoupper($busca['marca']) . "%");
        }
        if(!empty($busca['linha'])){
            $produtoQuery->where('linha', 'ilike', "%" . strtoupper($busca['linha']) . "%");
        }
        if(!empty($busca['grupo'])){
            
            $produtoQuery->where('produto_grupos.descricao','ilike', "%" . strtoupper($busca['grupo']) . "%");
        }
        if(!empty($busca['codigo'])){
            $produtoQuery->where('codigo_produto', 'ilike', strtoupper($busca['codigo']));
        }
        if(!empty($busca['nome'])){
            $produtoQuery->where('produto_especificacaos.descricao', 'ilike', $busca['nome']);
        }

        $result = $produtoQuery->get();
        $codigo = $result->pluck('codigo_produto');

        $totalTransitos = $this->ComprasTransitos($codigo);
        $out = [];
        $estabelecimento = null;
        $total = ['estoque' => 0,'compras' => 0,'remessas' => 0,'vendas' => 0,'media' => 0,'necessidade' => 0];
        $estabelecimentos = returnEmpresasNasajonView();

        foreach($result as $produto){
            $marca = $produto->marca;
            if(empty($busca['marca'])){
                $marca = 'todos';
            }
            $linha = $produto->linha;
            if(empty($busca['linha'])){
                $linha = 'todos';
            }
            $grupo = $produto->grupo;
            if(empty($busca['grupo'])){
                $grupo = 'todos';
            }
            $totalVenda = $produto->movimentacao->whereIn('cfop',$this->cfop_vendas)->sum('quantidade');
            $totalestoque = 0;
            $totalcompra = 0;

            $codestabelecimento = null;

            foreach($produto->estoque as $estoque){
                $estabelecimento = $estabelecimentos[(integer)$estoque->estabelecimento];
                $codestabelecimento = (isset($estoque->estabelecimento)) ? ((integer)$estoque->estabelecimento) : null;
                $totalestoque += $estoque->estoque;
            }

            $totalcompra = $totalTransitos->where('produto', $produto->codigo_produto)->sum('quantidade');

            foreach($produto->compras as $compras){
                if($compras->situacao === 'Parcialmente Liquidado'){
                    $totalcompra += $compras->quantidade_restante;
                }else{
                    $totalcompra += $compras->quantidade;
                }
            }

            if(empty($busca['estabelecimento'])){
                $estabelecimento = 'todos';
            }

            $quantidade_remessa = ($produto->movimentacao->whereIn('cfop',$this->cfop_remessas)->sum('quantidade') > 0) ? $produto->movimentacao->whereIn('cfop',$this->cfop_remessas)->sum('quantidade') : 0;
            $necessidade = ((((($totalVenda + $quantidade_remessa) /$busca['qtd_mes_media']) * $busca['qtd_mes_estoque']) - ($totalestoque + $totalcompra)) > 0) ? (((($totalVenda + $quantidade_remessa) /$busca['qtd_mes_media']) * $busca['qtd_mes_estoque']) - ($totalestoque + $totalcompra)) : 0;

            if(!empty($estabelecimento)){
                $chave = $estabelecimento.$marca.$linha.$grupo;
                if(!empty($busca['compra'])){
                    if($necessidade > 0){
                        if(!isset($out[$chave])){
                            $out[$chave] = [
                                'vendasMes' => $busca['qtd_mes_media'],
                                'estoqueMes' => $busca['qtd_mes_estoque'],
                                'estabelecimento' => $estabelecimento,
                                'marca' => $marca,
                                'linha' => $linha,
                                'grupo' => $grupo,
                                'remessas' => 0,
                                'estoque' => 0,
                                'compras' => 0,
                                'vendas' => 0,
                                'media' => 0,
                                'necessidade' => 0,
                                'filter' => encrypt([
                                    'estabelecimento' => (isset($busca['estabelecimento'])) ? str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT) : null,
                                    'marca' => $marca,
                                    'linha' => $linha,
                                    'grupo' => $grupo,
                                    'codigo' => $busca['codigo'],
                                    'nome' => $busca['nome'],
                                    'qtd_mes_estoque' => $busca['qtd_mes_estoque'],
                                    'qtd_mes_media' => $busca['qtd_mes_media'],
                                    'compra' => 'ok',
                                ]),
                            ];
                        }
                        $out[$chave]['remessas'] += ($quantidade_remessa > 0) ? $quantidade_remessa : 0;
                        $out[$chave]['estoque'] += $totalestoque;
                        $out[$chave]['compras'] += $totalcompra;
                        $out[$chave]['vendas'] += $totalVenda;
                        $out[$chave]['media'] += ($totalVenda + $quantidade_remessa)/$busca['qtd_mes_media'];
                        $out[$chave]['necessidade'] += $necessidade;
                        $total['remessas'] += ($quantidade_remessa > 0) ? $quantidade_remessa : 0;
                        $total['estoque'] += $totalestoque;
                        $total['compras'] += $totalcompra;
                        $total['vendas'] += $totalVenda;
                        $total['media'] += ($totalVenda + $quantidade_remessa)/$busca['qtd_mes_media'];
                        $total['necessidade'] += $necessidade;
                    }
                }else{
                    if(!isset($out[$chave])){
                        $out[$chave] = [
                            'vendasMes' => $busca['qtd_mes_media'],
                            'estoqueMes' => $busca['qtd_mes_estoque'],
                            'estabelecimento' => $estabelecimento,
                            'estabelecimento_prod' => '',
                            'marca' => $marca,
                            'linha' => $linha,
                            'grupo' => $grupo,
                            'codigo_produto' => '',
                            'remessas' => 0,
                            'estoque' => 0,
                            'compras' => 0,
                            'vendas' => 0,
                            'media' => 0,
                            'necessidade' => 0,
                            'filter' => encrypt([
                                'estabelecimento' => (isset($busca['estabelecimento'])) ? str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT) : null,
                                'marca' => $marca,
                                'linha' => $linha,
                                'grupo' => $grupo,
                                'codigo' => $busca['codigo'],
                                'nome' => $busca['nome'],
                                'qtd_mes_estoque' => $busca['qtd_mes_estoque'],
                                'qtd_mes_media' => $busca['qtd_mes_media'],
                                'compra' => 'null',
                            ]),
                        ];
                    }
                    $out[$chave]['remessas'] += ($quantidade_remessa > 0) ? $quantidade_remessa : 0;
                    $out[$chave]['estoque'] += $totalestoque;
                    $out[$chave]['compras'] += $totalcompra;
                    $out[$chave]['vendas'] += $totalVenda;
                    $out[$chave]['media'] += ($totalVenda + $quantidade_remessa)/$busca['qtd_mes_media'];
                    $out[$chave]['necessidade'] += $necessidade;
                    $total['estoque'] += $totalestoque;
                    $total['remessas'] += ($quantidade_remessa > 0) ? $quantidade_remessa : 0;
                    $total['compras'] += $totalcompra;
                    $total['vendas'] += $totalVenda;
                    $total['media'] += ($totalVenda + $quantidade_remessa)/$busca['qtd_mes_media'];
                    $total['necessidade'] += $necessidade;
                }
            }
        }

        foreach($out as $key => $value){
            $out[$key]['estoque'] = ($out[$key]['estoque'] != '0') ? parserQtd($out[$key]['estoque']) : '';
            $out[$key]['remessas'] = ($out[$key]['remessas'] != '0') ? parserQtd($out[$key]['remessas']) : '';
            $out[$key]['compras'] = ($out[$key]['compras'] != '0') ? parserQtd($out[$key]['compras']) : '';
            $out[$key]['vendas'] = ($out[$key]['vendas'] != '0') ? parserValor($out[$key]['vendas']) : '';
            $out[$key]['media'] = ($out[$key]['media'] != '0') ? parserValor($out[$key]['media']) : '';
            $out[$key]['necessidade'] = (parserValor($out[$key]['necessidade']) > 0) ? parserValor($out[$key]['necessidade']) : '';
        }

        $total['estoque'] = ($total['estoque'] != '0') ? parserQtd($total['estoque']) : '';
        $total['compras'] = ($total['compras'] != '0') ? parserQtd($total['compras']) : '';
        $total['vendas'] = ($total['vendas'] != '0') ? parserValor($total['vendas']) : '';
        $total['remessas'] = ($total['remessas'] != '0') ? parserQtd($total['remessas']) : '';
        $total['media'] = ($total['media'] != '0') ? parserValor($total['media']) : '';
        $total['necessidade'] = (parserValor($total['necessidade']) > 0) ? parserValor($total['necessidade']) : '';

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => ['saida' => $out, 'total' => $total],
        ]);
    }

    public function abertura(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }

        $produtoQuery = null;

        $produtoQuery = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['estoque' => function($query) use ($busca){
                if(isset($busca['estabelecimento'])){
                    $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
                }else{
                    $query->select(DB::raw('codigo_produto, sum(compras_aberto) as compras_aberto, sum(estoque) as estoque'))->groupBy('codigo_produto');
                }
            }])
            ->with(['compras' => function($query) use ($busca){
                if(isset($busca['estabelecimento'])){
                    $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
                }
                $query->whereIn('situacao', ['Aberto', 'Aguardando Documento','Parcialmente Liquidado']);
                $query->where(DB::raw("case
                when situacao = 'Parcialmente Liquidado' then
                    quantidade_restante
                else
                    quantidade 
                end"),'>',0);
            }])
            ->with(['movimentacao' => function($query) use ($busca){    
                $date = Carbon::now();
                $data_busca = $date->subMonth($busca['qtd_mes_media'] - 1)->format('Y-m-01');
                
                if(isset($busca['estabelecimento'])){
                    $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
                }
                
                $query->where('data_movimentacao','>=',$data_busca)
                ->where(function($query){
                    $query->oRwhereIn('cfop', $this->cfop_vendas)
                    ->oRwhereIn('cfop',$this->cfop_remessas);
                })
                ->where('documento', '!=', ' ')
                ->where('sinal', 'ilike', 'saida')
                ->whereNotNull('data_movimentacao')
                ->whereNotIn("cliente_codigo", $this->cnpjINtercompany());
            }]);

        if( $busca['grupo'] == 'todos' &&
            empty($busca['codigo']) &&
            empty($busca['nome'])&&
            $busca['marca'] == 'todos' &&
            $busca['linha'] == 'todos' &&
            !empty($busca['estabelecimento'])){
            $produtoQuery->orWhereHas('estoque',function($query) use ($busca){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            });
            $produtoQuery->orWhereHas('vendas',function($query) use ($busca){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            });
        }

        if ($busca['marca'] != 'todos'){
            $produtoQuery->where('marca', 'ilike', strtoupper($busca['marca']));
        }
        if ($busca['linha'] != 'todos'){
            $produtoQuery->where('linha', 'ilike', strtoupper($busca['linha']));
        }
        if ($busca['grupo'] != 'todos'){
           
            $produtoQuery->where('produto_grupos.descricao','ilike', "%" . strtoupper($busca['grupo']) . "%");
        }
        if (!empty($busca['codigo'])){
            $produtoQuery->where('codigo_produto', 'ilike', strtoupper($busca['codigo']));
        }
        if (!empty($busca['nome'])){
            $produtoQuery->where('produto_especificacaos.descricao', 'ilike', $busca['nome']);
        }

        $result = $produtoQuery->get();
        $codigo = $result->pluck('codigo_produto');

        $totalTransitos = $this->ComprasTransitos($codigo);
        $out= [];
        $totalEstoque = 0;
        $totalCompra = 0;
        $totalVenda = 0;
        $totalMedia = 0;
        $totalRemessas = 0;
        $totalNecessidade = 0;
        $estabelecimentos = returnEmpresasNasajonView();
        $estabelecimento = null;

        foreach($result as $produto){
            $saidaestoque = 0;
            $saidacompra = 0;
            $venda = 0;
            $remessas = 0;
            $codestabelecimento = null;
            $grupo = null;
            foreach($produto->estoque as $estoque){
                $estabelecimento = $estabelecimentos[(integer)$estoque->estabelecimento];
                if(empty($busca['estabelecimento'])){
                    $estabelecimento = 'todos';
                }
                $saidaestoque += $estoque->estoque;
                $codestabelecimento = (isset($estoque->estabelecimento)) ? ((integer)$estoque->estabelecimento) : null;
            }

            $saidacompra = $totalTransitos->where('produto', $produto->codigo_produto)->sum('quantidade');
            foreach($produto->compras as $compras){
                if(empty($busca['estabelecimento'])){
                    $estabelecimento = 'todos';
                }
                if($compras->situacao === 'Parcialmente Liquidado'){
                    $saidacompra += $compras->quantidade_restante;
                }else{
                    $saidacompra += $compras->quantidade;
                }
            }
            $grupo = $produto->grupo;

            $quantidade_remessa = $produto->movimentacao->whereIn('cfop',$this->cfop_remessas)->sum('quantidade');

            if($quantidade_remessa > 0){
                $remessas += $quantidade_remessa;
            }

            $venda = ($produto->movimentacao->whereIn('cfop',$this->cfop_vendas)->sum('quantidade') > 0) ? $produto->movimentacao->whereIn('cfop',$this->cfop_vendas)->sum('quantidade') : 0;

            if($busca['compra'] == 'ok'){
                if((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra)) > 0){
                    if(!isset($out[$grupo])){
                        $out[$grupo] = [
                            'estabelecimento' => (!empty($busca['estabelecimento'])) ? $estabelecimentos[(integer)$busca['estabelecimento']] : 'Todos',
                            'marca' => (!empty($busca['marca'])) ? $busca['marca'] : 'Todos',
                            'linha' => (!empty($busca['linha'])) ? $busca['linha'] : 'Todos',
                            'grupo' => $grupo,
                            'compra' => 'ok',
                            'estoque' => 0,
                            'remessas' => 0,
                            'compras' => 0,
                            'vendas' => 0,
                            'media' => 0,
                            'necessidade' => 0,
                            'filter' => encrypt([
                                'estabelecimento' => (!empty($busca['estabelecimento'])) ? $busca['estabelecimento']: null,
                                'marca' => (!empty($busca['marca'])) ? $busca['marca'] : 'Todos',
                                'linha' => (!empty($busca['linha'])) ? $busca['linha'] : 'Todos',
                                'grupo' => $grupo,
                                'codigo' => $busca['codigo'],
                                'nome' => $busca['nome'],
                                'qtd_mes_estoque' => $busca['qtd_mes_estoque'],
                                'qtd_mes_media' => $busca['qtd_mes_media'],
                                'compra' => 'ok',
                            ]),
                        ];
                    }
                    $out[$grupo]['estoque'] += $saidaestoque;
                    $out[$grupo]['compras'] += $saidacompra;
                    $out[$grupo]['vendas'] += $venda;
                    $out[$grupo]['remessas'] += ($quantidade_remessa > 0) ? $quantidade_remessa : 0;
                    $out[$grupo]['media'] += (($venda + $remessas)/$busca['qtd_mes_media']);
                    $out[$grupo]['necessidade'] += ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra)) > 0) ? ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra))) : '0';
                    $totalEstoque += $saidaestoque;
                    $totalCompra += $saidacompra;
                    $totalRemessas += ($quantidade_remessa > 0) ? $quantidade_remessa : 0;
                    $totalVenda += $venda;
                    $totalMedia += (($venda + $remessas)/$busca['qtd_mes_media']);
                    $totalNecessidade += ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra)) > 0) ? ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra))) : '0';
                }
            }
            else{
                if(!isset($out[$grupo])){
                    $out[$grupo] = [
                        'estabelecimento' => (!empty($busca['estabelecimento'])) ? $estabelecimentos[(integer)$busca['estabelecimento']] : 'Todos',
                        'marca' => (!empty($busca['marca'])) ? $busca['marca'] : 'Todos',
                        'linha' => (!empty($busca['linha'])) ? $busca['linha'] : 'Todos',
                        'grupo' => $grupo,
                        'compra' => 'null',
                        'estoque' => 0,
                        'compras' => 0,
                        'vendas' => 0,
                        'remessas' => 0,
                        'media' => 0,
                        'necessidade' => 0,
                        'filter' => encrypt([
                            'estabelecimento' => (!empty($busca['estabelecimento'])) ? $busca['estabelecimento'] : null,
                            'marca' => (!empty($busca['marca'])) ? $busca['marca'] : 'Todos',
                            'linha' => (!empty($busca['linha'])) ? $busca['linha'] : 'Todos',
                            'grupo' => $grupo,
                            'codigo' => $busca['codigo'],
                            'nome' => $busca['nome'],
                            'qtd_mes_estoque' => $busca['qtd_mes_estoque'],
                            'qtd_mes_media' => $busca['qtd_mes_media'],
                            'compra' => 'null',
                        ]),
                    ];
                }
                $out[$grupo]['estoque'] += $saidaestoque;
                $out[$grupo]['compras'] += $saidacompra;
                $out[$grupo]['vendas'] += $venda;
                $out[$grupo]['remessas'] += ($quantidade_remessa > 0) ? $quantidade_remessa : 0;
                $out[$grupo]['media'] += (($venda + $remessas)/$busca['qtd_mes_media']);
                $out[$grupo]['necessidade'] += ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra)) > 0) ? ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra))) : '0';
                $totalEstoque += $saidaestoque;
                $totalCompra += $saidacompra;
                $totalRemessas += ($quantidade_remessa > 0) ? $quantidade_remessa : 0;
                $totalVenda += $venda;
                $totalMedia += (($venda + $remessas)/$busca['qtd_mes_media']);
                $totalNecessidade += ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra)) > 0) ? ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra))) : '0';
            }
        }
        unset($result);
        $saida = [];
        foreach($out as $key => $outs){
            if(!isset($saida[$key])){
                $saida[$key] = [
                    'estabelecimento' => $outs['estabelecimento'],
                    'marca' => $outs['marca'],
                    'linha' => $outs['linha'],
                    'grupo' => $outs['grupo'],
                    'estoque' => 0,
                    'compras' => 0,
                    'vendas' => 0,
                    'remessas' => 0,
                    'media' => 0,
                    'necessidade' => 0,
                    'filter' => $outs['filter'],
                ];
            }
            $saida[$key]['estoque'] = ($outs['estoque'] > 0) ? parserValor($outs['estoque']) : '';
            $saida[$key]['compras'] = ($outs['compras'] > 0) ? parserValor($outs['compras']) : '';
            $saida[$key]['vendas'] = ($outs['vendas'] > 0) ? parserValor($outs['vendas']) : '';
            $saida[$key]['remessas'] = ($outs['remessas'] > 0) ? parserValor($outs['remessas']) : '';
            $saida[$key]['media'] = ($outs['media'] > 0) ? parserValor($outs['media']) : '';
            $saida[$key]['necessidade'] = ($outs['necessidade'] > 0) ? parserValor($outs['necessidade']) : '';
        }
        $out = $saida;
        unset($saida);

        return view('programs.analise_compra_mes.modal.abertura')->with(["dados" => $out, 'vendas' => $busca['qtd_mes_media'], 'estoque' => $busca['qtd_mes_estoque'],'totalEstoque' => $totalEstoque,'totalCompra' => $totalCompra, 'totalVenda' => $totalVenda, 'totalMedia' => $totalMedia,'totalNecessidade' => $totalNecessidade,'totalRemessa' => $totalRemessas]);
    }

    public function show(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $filter = $request->only(['filters']);
        try{
            $busca = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }

        $produtoQuery = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['estoque' => function($query) use ($busca){
                if(!empty($busca['estabelecimento'])){
                    $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
                }else{
                    $query->select(DB::raw('codigo_produto, sum(compras_aberto) as compras_aberto, sum(estoque) as estoque'))->groupBy('codigo_produto');
                }
            }])
            ->with(['compras' => function($query) use ($busca){
                if(isset($busca['estabelecimento'])){
                    $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
                }
                $query->whereIn('situacao', ['Aberto', 'Aguardando Documento','Parcialmente Liquidado']);
                $query->where(DB::raw("case
                when situacao = 'Parcialmente Liquidado' then
                    quantidade_restante
                else
                    quantidade 
                end"),'>',0);
            }])
            ->with(['movimentacao' => function($query) use ($busca){    
                $date = Carbon::now();
                $data_busca = $date->subMonth($busca['qtd_mes_media'] - 1)->format('Y-m-01');
                
                if(isset($busca['estabelecimento'])){
                    $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
                }
                
                $query->where('data_movimentacao','>=',$data_busca)
                ->where(function($query){
                    $query->oRwhereIn('cfop', $this->cfop_vendas)
                    ->oRwhereIn('cfop',$this->cfop_remessas);
                })
                ->where('documento', '!=', ' ')
                ->where('sinal', 'ilike', 'saida')
                ->whereNotNull('data_movimentacao')
                ->whereNotIn("cliente_codigo", $this->cnpjINtercompany());
            }]);

        if( $busca['grupo'] == 'todos' &&
            empty($busca['codigo']) &&
            empty($busca['nome'])&&
            $busca['marca'] == 'todos' &&
            $busca['linha'] == 'todos' &&
            !empty($busca['estabelecimento'])){
            $produtoQuery->orWhereHas('estoque',function($query) use ($busca){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            });
            $produtoQuery->orWhereHas('vendas',function($query) use ($busca){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            });
        }

        if ($busca['marca'] != 'todos'){
            $produtoQuery->where('marca', 'ilike', strtoupper($busca['marca']));
        }
        if ($busca['linha'] != 'todos'){
            $produtoQuery->where('linha', 'ilike', strtoupper($busca['linha']));
        }
        if ($busca['grupo'] != 'todos'){
           
            $produtoQuery->where('produto_grupos.descricao','ilike', "%" . strtoupper($busca['grupo']) . "%");
        }
        if (!empty($busca['codigo'])){
            $produtoQuery->where('codigo_produto', 'ilike', strtoupper($busca['codigo']));
        }
        if (!empty($busca['nome'])){
            $produtoQuery->where('produto_especificacaos.descricao', 'ilike', $busca['nome']);
        }

        $result = $produtoQuery->get();
        $codigo = $result->pluck('codigo_produto');
        $totalTransitos = $this->ComprasTransitos($codigo);
        $out= [];
        $totalEstoque = 0;
        $totalCompra = 0;
        $totalVenda = 0;
        $totalMedia = 0;
        $totalRemessas = 0;
        $totalNecessidade = 0;
        $estabelecimentos = returnEmpresasNasajonView();
        $estabelecimento = null;

        foreach($result as $produto){
            $saidaestoque = 0;
            $saidacompra = 0;
            $venda = 0;
            $remessas = 0;
            $codestabelecimento = null;
            foreach($produto->estoque as $estoque){
                $estabelecimento = $estabelecimentos[(integer)$estoque->estabelecimento];
                if(empty($busca['estabelecimento'])){
                    $estabelecimento = 'todos';
                }
                $saidaestoque += $estoque->estoque;
                $codestabelecimento = (isset($estoque->estabelecimento)) ? ((integer)$estoque->estabelecimento) : null;
            }

            foreach($produto->compras as $compras){
                if($compras->situacao === 'Parcialmente Liquidado'){
                    $saidacompra += $compras->quantidade_restante;
                }else{
                    $saidacompra += $compras->quantidade;
                }
            }

            $venda = ($produto->movimentacao->whereIn('cfop',$this->cfop_vendas)->sum('quantidade') > 0) ? $produto->movimentacao->whereIn('cfop',$this->cfop_vendas)->sum('quantidade') : 0;
            $saidacompra += $totalTransitos->where('produto', $produto->codigo_produto)->sum('quantidade');
            $quantidade_remessa = $produto->movimentacao->whereIn('cfop',$this->cfop_remessas)->sum('quantidade');

            if($quantidade_remessa > 0){
                $remessas += $quantidade_remessa;
            }

            if($busca['compra'] == 'ok' && !is_null($estabelecimento)){
                if((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra)) > 0){
                    $out[] = [
                        'estabelecimento' => $estabelecimento,
                        'marca' => $produto->marca,
                        'linha' => $produto->linha,
                        'grupo' => $produto->grupo,
                        'codigo' => $produto->codigo_produto,
                        'nome' => $produto->descricao,
                        'compra' => 'ok',
                        'estoque' => ($saidaestoque != '0') ? ($saidaestoque) : 0,
                        'compras' => ($saidacompra != '0') ? ($saidacompra) : 0,
                        'vendas' => ($venda != '0') ? ($venda) : 0,
                        'remessas' => ($quantidade_remessa > 0) ? $quantidade_remessa : 0,
                        'media' => (((($venda + $remessas))/$busca['qtd_mes_media']) != '0') ? ((($venda + $remessas))/$busca['qtd_mes_media']) : 0,
                        'necessidade' => ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra)) > 0) ? ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra))) : 0,
                        'filter' => encrypt([
                            'estabelecimento' => $codestabelecimento,
                            'marca' => $produto->marca,
                            'linha' => $produto->linha,
                            'grupo' => $produto->grupo,
                            'codigo' => $produto->codigo_produto,
                            'nome' => $produto->descricao,
                            'qtd_mes_estoque' => $busca['qtd_mes_estoque'],
                            'qtd_mes_media' => $busca['qtd_mes_media'],
                            'compra' => 'ok',
                        ]),
                    ];
                    $totalEstoque += $saidaestoque;
                    $totalCompra += $saidacompra;
                    $totalVenda += $venda;
                    $totalRemessas += ($quantidade_remessa > 0) ? $quantidade_remessa : 0;
                    $totalMedia += (($venda + $remessas)/$busca['qtd_mes_media']);
                    $totalNecessidade += ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra)) > 0) ? ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra))) : '0';
                }
            }else{
                $out[] = [
                    'estabelecimento' => $estabelecimento,
                    'marca' => $produto->marca,
                    'linha' => $produto->linha,
                    'grupo' => $produto->grupo,
                    'codigo' => $produto->codigo_produto,
                    'nome' => $produto->descricao,
                    'compra' => 'null',
                    'estoque' => ($saidaestoque != '0') ? $saidaestoque : 0,
                    'compras' => ($saidacompra != '0') ? $saidacompra : 0,
                    'vendas' => ($venda != '0') ? ($venda) : 0,
                    'remessas' => ($quantidade_remessa > 0) ? $quantidade_remessa : 0,
                    'media' => ((($venda + $remessas)/$busca['qtd_mes_media']) != '0') ? (($venda + $remessas)/$busca['qtd_mes_media']) : 0,
                    'necessidade' => ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra)) > 0) ? ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra))) : 0,
                    'filter' => encrypt([
                        'estabelecimento' => $codestabelecimento,
                        'marca' => $produto->marca,
                        'linha' => $produto->linha,
                        'grupo' => $produto->grupo,
                        'codigo' => $produto->codigo_produto,
                        'nome' => $produto->descricao,
                        'qtd_mes_estoque' => $busca['qtd_mes_estoque'],
                        'qtd_mes_media' => $busca['qtd_mes_media'],
                        'compra' => 'null',
                    ]),
                ];
                $totalEstoque += $saidaestoque;
                $totalCompra += $saidacompra;
                $totalVenda += $venda ;
                $totalRemessas += ($quantidade_remessa > 0) ? $quantidade_remessa : 0;
                $totalMedia += (($venda + $remessas)/$busca['qtd_mes_media']);
                $totalNecessidade += ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra)) > 0) ? ((($busca['qtd_mes_estoque'] * (($venda + $remessas)/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra))) : '0';
            }
        }
        unset($result);
        if (strlen($busca['estabelecimento']) <= 0){
            $saida = [];
            foreach($out as $key => $outs){
                if(!isset($saida[$outs['codigo']])){
                    $saida[$outs['codigo']] = [
                        'estabelecimento' => 'Todos',
                        'marca' => $outs['marca'],
                        'linha' => $outs['linha'],
                        'grupo' => $outs['grupo'],
                        'codigo' => $outs['codigo'],
                        'nome' => $outs['nome'],
                        'estoque' => 0,
                        'compras' => 0,
                        'vendas' => 0,
                        'remessas' => 0,
                        'media' => 0,
                        'necessidade' => 0,
                        'filter' => encrypt([
                            'estabelecimento' => null,
                            'marca' => $outs['marca'],
                            'linha' => $outs['linha'],
                            'grupo' => $outs['grupo'],
                            'codigo' => $outs['codigo'],
                            'nome' => $outs['nome'],
                            'qtd_mes_estoque' => $busca['qtd_mes_estoque'],
                            'qtd_mes_media' => $busca['qtd_mes_media'],
                            'compra' => $outs['compra'],
                        ]),
                    ];
                }
                $saida[$outs['codigo']]['estoque'] += $outs['estoque'];
                $saida[$outs['codigo']]['compras'] += $outs['compras'];
                $saida[$outs['codigo']]['vendas'] += $outs['vendas'];
                $saida[$outs['codigo']]['remessas'] += $outs['remessas'];
                $saida[$outs['codigo']]['media'] += $outs['media'];
                $saida[$outs['codigo']]['necessidade'] += $outs['necessidade'];
            }
            $out = $saida;
            unset($saida);
        }
        return view('programs.analise_compra_mes.modal.busca_analitica')->with(["dados" => $out, 'vendas' => $busca['qtd_mes_media'], 'estoque' => $busca['qtd_mes_estoque'],'totalEstoque' => $totalEstoque,'totalCompra' => $totalCompra, 'totalVenda' => $totalVenda, 'totalMedia' => $totalMedia,'totalNecessidade' => $totalNecessidade, 'totalRemessa' => $totalRemessas]);
    }

    public function ShowEstoque(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $filter = $request->only(['filters']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }
        $produtoQuery = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['movimentacao' => function($query) use ($busca){    
                $date = Carbon::now();
                $data_busca = $date->subMonth($busca['qtd_mes_media'] - 1)->format('Y-m-01');
                
                if(isset($busca['estabelecimento'])){
                    $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
                }
                
                $query->where('data_movimentacao','>=',$data_busca)
                ->where(function($query){
                    $query->oRwhereIn('cfop', $this->cfop_vendas)
                    ->oRwhereIn('cfop', $this->cfop_remessas);
                })
                ->where('documento', '!=', ' ')
                ->where('sinal', 'ilike', 'saida')
                ->whereNotNull('data_movimentacao')
                ->whereNotIn("cliente_codigo", $this->cnpjINtercompany());
            },'compras' => function($query) use ($busca){
                if(isset($busca['estabelecimento'])){
                    $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
                }
                $query->whereIn('situacao', ['Aberto', 'Aguardando Documento','Parcialmente Liquidado']);
                $query->where(DB::raw("case
                when situacao = 'Parcialmente Liquidado' then
                    quantidade_restante
                else
                    quantidade 
                end"),'>',0);
            },'estoque' => function($query) use ($busca){
                if(isset($busca['estabelecimento'])){
                    $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
                }else{
                    $query->select(DB::raw('estabelecimento, codigo_produto, sum(compras_aberto) as compras_aberto, sum(estoque) as estoque'))->groupBy('estabelecimento', 'codigo_produto');
                }
            }])
            ->whereHas('estoque' , function($query) use ($busca){
                $query->where('estoque', '>', 0);
            })->has('estoque');

        if ($busca['marca'] != 'todos'){
            $produtoQuery->where('marca', 'ilike', strtoupper($busca['marca']));
        }
        if ($busca['linha'] != 'todos'){
            $produtoQuery->where('linha', 'ilike', strtoupper($busca['linha']));
        }
        if ($busca['grupo'] != 'todos'){
           
            $produtoQuery->where('produto_grupos.descricao','ilike', "%" . strtoupper($busca['grupo']) . "%");
        }
        if (!empty($busca['codigo'])){
            $produtoQuery->where('codigo_produto', 'ilike', strtoupper($busca['codigo']));
        }
        if (!empty($busca['nome'])){
            $produtoQuery->where('produto_especificacaos.descricao', 'like', $busca['nome']);
        }

        $result = $produtoQuery->get();
        $codigo = $result->pluck('codigo_produto');
        $totalTransitos = $this->ComprasTransitos($codigo);
        $out = [];
        $estabelecimentos = returnEmpresasNasajonView();
        $totalEStoque = 0;

        foreach($result as $produto){
            $popOver = '';
            $totalestoque = 0;
            $totalVenda = 0;
            $totalcompra = 0;
            foreach($produto->estoque as $estoque){
                $popOver .= '<p>'.$estabelecimentos[(integer)$estoque->estabelecimento].' - '.parserQtd($estoque->estoque).'</p>';
            }
            if($busca['compra'] == 'ok'){
                $totalVenda = ($produto->movimentacao->whereIn('cfop',$this->cfop_vendas)->sum('quantidade') > 0) ? $produto->movimentacao->whereIn('cfop',$this->cfop_vendas)->sum('quantidade') : 0;
                foreach($produto->estoque as $estoque){
                    $estabelecimento = $estabelecimentos[(integer)$estoque->estabelecimento];
                    $codestabelecimento = (isset($estoque->estabelecimento)) ? ((integer)$estoque->estabelecimento) : null;
                    if(empty($busca['estabelecimento'])){
                        $estabelecimento = 'todos';
                    }
                    $totalestoque += $estoque->estoque;
                }

                $totalcompra = $totalTransitos->where('produto', $produto->codigo_produto)->sum('quantidade');
                foreach($produto->compras as $compras){
                    if($compras->situacao === 'Parcialmente Liquidado'){
                        $totalcompra += $compras->quantidade_restante;
                    }else{
                        $totalcompra += $compras->quantidade;
                    }
                }

                $quantidade_remessa = $produto->movimentacao->whereIn('cfop',$this->cfop_remessas)->sum('quantidade');

                if($quantidade_remessa > 0){
                    $totalVenda += $quantidade_remessa;
                }

                if(((($totalVenda/$busca['qtd_mes_media']) * $busca['qtd_mes_estoque']) - ($totalestoque + $totalcompra)) > 0) {
                    foreach($produto->estoque as $estoquenec){
                        if(!isset($out[$estoquenec->codigo_produto])){
                            $out[$estoquenec->codigo_produto] = [
                                'marca' => $produto->marca,
                                'linha' => $produto->linha,
                                'grupo' => $produto->grupo,
                                'codigo' => $produto->codigo_produto,
                                'nome' => $produto->descricao,
                                'estoque' => 0,
                                'popover' => $popOver,
                            ];
                        }
                        $out[$estoquenec->codigo_produto]['estoque'] += $estoquenec->estoque;
                        $totalEStoque += $estoquenec->estoque;
                    }
                }
            }
            else{
                foreach($produto->estoque as $estoq){
                    if(!isset($out[$estoq->codigo_produto])){
                        $out[$estoq->codigo_produto] = [
                            'marca' => $produto->marca,
                            'linha' => $produto->linha,
                            'grupo' => $produto->grupo,
                            'codigo' => $produto->codigo_produto,
                            'nome' => $produto->descricao,
                            'estoque' => 0,
                            'popover' => $popOver,
                        ];
                    }
                    $out[$estoq->codigo_produto]['estoque'] += $estoq->estoque;
                    $totalEStoque += $estoq->estoque;
                }
            }
        }
        return view('programs.produto.modal.busca_estoque')->with(["dados" => $out, 'vendas' => $busca['qtd_mes_media'], 'estoque' => $busca['qtd_mes_estoque'],'totalEStoque' => $totalEStoque]);
    }

    public function ShowCompras(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $filter = $request->only(['filters']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }
        $produtoQuery = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['estoque' => function($query) use ($busca){
                if(isset($busca['estabelecimento'])){
                    $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
                }
                $query->select(DB::raw("codigo_produto, sum(estoque) as estoque, sum(compras_aberto) as compras_aberto"))
                    ->groupBy('codigo_produto');
            }])
            ->with(['compras' => function($query) use ($busca){
                if(isset($busca['estabelecimento'])){
                    $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
                }
                $query->whereIn('situacao', ['Aberto', 'Aguardando Documento','Parcialmente Liquidado']);
                $query->where(DB::raw("case
                when situacao = 'Parcialmente Liquidado' then
                    quantidade_restante
                else
                    quantidade 
                end"),'>',0);
            }])
            ->with(['movimentacao' => function($query) use ($busca){    
                $date = Carbon::now();
                $data_busca = $date->subMonth($busca['qtd_mes_media'] - 1)->format('Y-m-01');
                
                if(isset($busca['estabelecimento'])){
                    $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
                }
                
                $query->where('data_movimentacao','>=',$data_busca)
                ->where(function($query){
                    $query->oRwhereIn('cfop', $this->cfop_vendas)
                    ->oRwhereIn('cfop', $this->cfop_remessas);
                })
                ->where('documento', '!=', ' ')
                ->where('sinal', 'ilike', 'saida')
                ->whereNotNull('data_movimentacao')
                ->whereNotIn("cliente_codigo", $this->cnpjINtercompany());
            }]);

        if( $busca['grupo'] == 'todos' &&
            empty($busca['codigo']) &&
            empty($busca['nome'])&&
            $busca['marca'] == 'todos' &&
            $busca['linha'] == 'todos' &&
            !empty($busca['estabelecimento'])){
            $produtoQuery->orWhereHas('estoque',function($query) use ($busca){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            });
            $produtoQuery->orWhereHas('vendas',function($query) use ($busca){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            });
        }

        if ($busca['marca'] != 'todos'){
            $produtoQuery->where('marca', 'ilike', strtoupper($busca['marca']));
        }
        if ($busca['linha'] != 'todos'){
            $produtoQuery->where('linha', 'ilike', strtoupper($busca['linha']));
        }
        if ($busca['grupo'] != 'todos'){
            
            $produtoQuery->where('produto_grupos.descricao','ilike', "%" . strtoupper($busca['grupo']) . "%");
        }
        if (!empty($busca['codigo'])){
            $produtoQuery->where('codigo_produto', 'ilike', strtoupper($busca['codigo']));
        }
        if (!empty($busca['nome'])){
            $produtoQuery->where('produto_especificacaos.descricao', 'ilike', $busca['nome']);
        }
        if (strlen($busca['estabelecimento']) > 0){
            $produtoQuery->whereHas('estoque', function ($query) use($busca){
                $query->where('estabelecimento', '=', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            });
        }

        $result = $produtoQuery->get();
        $codigo = $produtoQuery->pluck('codigo_produto');
        $totalTransitos = $this->ComprasTransitos($codigo);
        $out= [];
        $estabelecimentos = returnEmpresasNasajonView();
        $totalCompras = 0;

        foreach($result as $produto){
            $comprasTotal = 0;
            $transito = $totalTransitos->where('produto', $produto->codigo_produto);
            $estoqueTotal = 0;
            $Vendas = 0;
            foreach($produto->estoque as $estoque){
                $estoqueTotal += $estoque->estoque;
            }
            $Vendas = ($produto->movimentacao->whereIn('cfop',$this->cfop_vendas)->sum('quantidade') > 0) ? $produto->movimentacao->whereIn('cfop',$this->cfop_vendas)->sum('quantidade') : 0;
            $comprasTotal = $totalTransitos->where('produto', $produto->codigo_produto)->sum('quantidade');
            foreach($produto->compras as $compras){
                if($compras->situacao === 'Parcialmente Liquidado'){
                    $comprasTotal += $compras->quantidade_restante;
                }else{
                    $comprasTotal += $compras->quantidade;
                }
            }

            $quantidade_remessa = $produto->movimentacao->whereIn('cfop',$this->cfop_remessas)->sum('quantidade');

            if($quantidade_remessa > 0){
                $Vendas += $quantidade_remessa;
            }

            if($busca['compra'] == 'ok'){
                if(((($busca['qtd_mes_estoque'] * ($Vendas/$busca['qtd_mes_media'])) - ($estoqueTotal + $comprasTotal)) > 0)){
                    foreach ($produto->compras as $compra) {
                        if($compra->situacao === 'Parcialmente Liquidado'){
                            $quantidade = floatval($compra->quantidade_restante);
                        }else{
                            $quantidade = floatval($compra->quantidade);
                        }
                        $out[] = [
                            'estabelecimento' => $estabelecimentos[(integer)$compra->estabelecimento],
                            'PCMN' => $compra->numero_pedido,
                            'proforma' => $compra->proforma,
                            'previsao' => parserData($compra->previsao_entrega),
                            'codigo' => $produto->codigo_produto,
                            'nome' => $produto->descricao,
                            'compras' => parserQtd($quantidade),
                            'id' => encrypt($compra->id_nota),
                            'nota' => '',
                        ];
                        $totalCompras += $quantidade;
                    }
                    foreach ($transito as $compra) {
                        $quantidade = floatval($compra->quantidade);
                        $out[] = [
                            'estabelecimento' => $estabelecimentos[(integer)$compra->estabelecimento_codigo],
                            'PCMN' => '',
                            'proforma' => '',
                            'previsao' => 'EM TRANSITO',
                            'codigo' => $produto->codigo_produto,
                            'nome' => $produto->descricao,
                            'compras' => parserQtd($quantidade),
                            'id' => '',
                            'nota' => $compra->remessa_numero,
                        ];
                        $totalCompras += $quantidade;
                    }
                }
            }
            else{
                foreach ($produto->compras as $compra) {
                    if($compra->situacao === 'Parcialmente Liquidado'){
                        $quantidade = floatval($compra->quantidade_restante);
                    }else{
                        $quantidade = floatval($compra->quantidade);
                    }
                    $out[] = [
                        'estabelecimento' => $estabelecimentos[(integer)$compra->estabelecimento],
                        'PCMN' => $compra->numero_pedido,
                        'proforma' => $compra->proforma,
                        'previsao' => parserData($compra->previsao_entrega),
                        'codigo' => $produto->codigo_produto,
                        'nome' => $produto->descricao,
                        'compras' => parserQtd($quantidade),
                        'id' => encrypt($compra->id_nota),
                        'nota' => '',
                    ];
                    $totalCompras += $quantidade;
                }
                foreach ($transito as $compra) {
                    $quantidade = floatval($compra->quantidade);
                    $out[] = [
                        'estabelecimento' => $estabelecimentos[(integer)$compra->estabelecimento_codigo],
                        'PCMN' => '',
                        'proforma' => '',
                        'previsao' => 'EM TRANSITO',
                        'codigo' => $produto->codigo_produto,
                        'nome' => $produto->descricao,
                        'compras' => parserQtd($quantidade),
                        'id' => '',
                        'nota' => $compra->remessa_numero,
                    ];
                    $totalCompras += $quantidade;
                }
            }
        }
        unset($result);
        unset($resultNasajon);
        unset($totalTransitos);
        return view('programs.produto.modal.busca_compras')->with(["dados" => $out, 'totalCompras' => $totalCompras]);
    }

    private function ComprasTransitos($codigos){
        $saida = DB::connection('nasajon')->table('integracoes.vw_saldos_em_transitos')->whereIn('produto', $codigos)->get();
        return $saida;
    }

    public function ShowVendas(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $filter = $request->only(['filters']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }
        $produtoQuery = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['estoque' => function($query) use ($busca){
            if(isset($busca['estabelecimento'])){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            }
            $query->select(DB::raw("codigo_produto, sum(estoque) as estoque, sum(compras_aberto) as compras_aberto"))
                ->groupBy('codigo_produto');
        }])
        ->with(['compras' => function($query) use ($busca){
            if(isset($busca['estabelecimento'])){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            }
            $query->whereIn('situacao', ['Aberto', 'Aguardando Documento','Parcialmente Liquidado']);
            $query->where(DB::raw("case
            when situacao = 'Parcialmente Liquidado' then
                quantidade_restante > 0
            else
                quantidade > 0
            end"));
        }])
        ->with(['movimentacao' => function($query) use ($busca){    
            $date = Carbon::now();
            $data_busca = $date->subMonth($busca['qtd_mes_media'] - 1)->format('Y-m-01');
            
            if(isset($busca['estabelecimento'])){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            }
            
            $query->where('data_movimentacao','>=',$data_busca)
            ->where(function($query){
                $query->oRwhereIn('cfop', $this->cfop_vendas)
                ->oRwhereIn('cfop', $this->cfop_remessas);
            })
            ->where('documento', '!=', ' ')
            ->where('sinal', 'ilike', 'saida')
            ->whereNotNull('data_movimentacao')
            ->whereNotIn("cliente_codigo", $this->cnpjINtercompany());
        }]);

        if($busca['grupo'] == 'todos' && empty($busca['codigo']) && empty($busca['nome'])&& $busca['marca'] == 'todos'
            && $busca['linha'] == 'todos' && !empty($busca['estabelecimento'])){
            $produtoQuery->orWhereHas('estoque',function($query) use ($busca){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            });
            $produtoQuery->orWhereHas('vendas',function($query) use ($busca){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            });
        }

        if ($busca['marca'] != 'todos'){
            $produtoQuery->where('marca', 'ilike', strtoupper($busca['marca']));
        }
        if ($busca['linha'] != 'todos'){
            $produtoQuery->where('linha', 'ilike', strtoupper($busca['linha']));
        }
        if ($busca['grupo'] != 'todos'){
           
            $produtoQuery->where('produto_grupos.descricao','ilike', "%" . strtoupper($busca['grupo']) . "%");
        }
        if (!empty($busca['codigo'])){
            $produtoQuery->where('codigo_produto', 'ilike', strtoupper($busca['codigo']));
        }
        if (!empty($busca['nome'])){
            $produtoQuery->where('produto_especificacaos.descricao', 'ilike', $busca['nome']);
        }

        $result = $produtoQuery->get();
        $codigo = $produtoQuery->pluck('codigo_produto');
        $totalTransitos = $this->ComprasTransitos($codigo);
        $out= [];
        $totalVenda = 0;
        $estabelecimentos = returnEmpresasNasajonView();
        $estabelecimento = null;

        foreach($result as $produto){
            $saidaestoque = (isset($produto->estoque[0]->estoque)) ? $produto->estoque[0]->estoque : 0;
            $saidacompra = 0;
            $valor = 0;
            $remessas = 0;
            $saidacompra = $totalTransitos->where('produto', $produto->codigo_produto)->sum('quantidade');

            foreach($produto->compras as $compras){
                if(empty($busca['estabelecimento'])){
                    $estabelecimento = 'todos';
                }
                if($compras->situacao === 'Parcialmente Liquidado'){
                    $saidacompra += $compras->quantidade_restante;
                }else{
                    $saidacompra += $compras->quantidade;
                }
            }

            $valor += ($produto->movimentacao->whereIn('cfop',$this->cfop_vendas)->sum('quantidade') > 0) ? $produto->movimentacao->whereIn('cfop',$this->cfop_vendas)->sum('quantidade') : 0;
        
            $quantidade_remessa = $produto->movimentacao->whereIn('cfop',$this->cfop_remessas)->sum('quantidade');

            if($quantidade_remessa > 0){
                $remessas += $quantidade_remessa;
            }

            if($busca['compra'] == 'ok' && $valor != 0){
                if((($busca['qtd_mes_estoque'] * (($valor + $remessas) /$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra)) > 0){
                    foreach($produto->movimentacao->whereIn('cfop',$this->cfop_vendas) as $resultTotal){
                        $ano = Carbon::parse($resultTotal->data_movimentacao)->year;
                        $mes = Carbon::parse($resultTotal->data_movimentacao)->month;
                        $out[] = [
                            'estabelecimento' => (isset($busca['estabelecimento'])) ? $estabelecimentos[(integer)$resultTotal->estabelecimento] : 'Todos',
                            'ano' => $ano,
                            'mes' => $mes,
                            'vendas' => $resultTotal->quantidade,
                        ];
                        $totalVenda += $resultTotal->quantidade;
                    }
                }
            }
            else if($valor != 0){
                foreach($produto->movimentacao->whereIn('cfop',$this->cfop_vendas) as $resultTotal){
                    $ano = Carbon::parse($resultTotal->data_movimentacao)->year;
                    $mes = Carbon::parse($resultTotal->data_movimentacao)->month;
                    $out[] = [
                        'estabelecimento' => (isset($busca['estabelecimento'])) ? $estabelecimentos[(integer)$resultTotal->estabelecimento] : 'Todos',
                        'ano' => $ano,
                        'mes' => $mes,
                        'vendas' => $resultTotal->quantidade,
                    ];
                    $totalVenda += $resultTotal->quantidade;
                }
            }

        }
        $saida = [];
        foreach($out as $outs){
            if(isset($saida[$outs['estabelecimento']][$outs['ano']][$outs['mes']])){
                $saida[$outs['estabelecimento']][$outs['ano']][$outs['mes']] += $outs['vendas'];
            }
            else{
                $saida[$outs['estabelecimento']][$outs['ano']][$outs['mes']] = $outs['vendas'];
            }
        }
        return view('programs.produto.modal.busca_vendas')->with(["dados" => $saida, 'vendas' => $busca['qtd_mes_media'], 'estoque' => $busca['qtd_mes_estoque'],'totalVendas' => $totalVenda]);
    }

    public function ShowRemessas(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters']);

        try{
            $busca = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }

        $produtoQuery = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['estoque' => function($query) use ($busca){
            if(!empty($busca['estabelecimento'])){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            }else{
                $query->select(DB::raw('codigo_produto, sum(compras_aberto) as compras_aberto, sum(estoque) as estoque'))->groupBy('codigo_produto');
            }
        }])
        ->with(['compras' => function($query) use ($busca){
            if(isset($busca['estabelecimento'])){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            }
            $query->whereIn('situacao', ['Aberto', 'Aguardando Documento','Parcialmente Liquidado']);
            $query->where(DB::raw("case
            when situacao = 'Parcialmente Liquidado' then
                quantidade_restante > 0
            else
                quantidade > 0
            end"));
        }])
        ->whereHas('movimentacao')
        ->with(['movimentacao' => function($query) use ($busca){    
            $date = Carbon::now();
            $data_busca = $date->subMonth($busca['qtd_mes_media'] - 1)->format('Y-m-01');
            
            if(isset($busca['estabelecimento'])){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            }
            
            $query->where('data_movimentacao','>=',$data_busca)
            ->where(function($query){
                $query->oRwhereIn('cfop', $this->cfop_vendas)
                ->oRwhereIn('cfop', $this->cfop_remessas);
            })
            ->with('notasNasajon.pedido')
            ->where('documento', '!=', ' ')
            ->where('sinal', 'ilike', 'saida')
            ->whereNotNull('data_movimentacao')
            ->whereNotIn("cliente_codigo", $this->cnpjINtercompany());
        }]);

        if( $busca['grupo'] == 'todos' &&
            empty($busca['codigo']) &&
            empty($busca['nome'])&&
            $busca['marca'] == 'todos' &&
            $busca['linha'] == 'todos' &&
            !empty($busca['estabelecimento'])){
            $produtoQuery->orWhereHas('estoque',function($query) use ($busca){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            });
            $produtoQuery->orWhereHas('vendas',function($query) use ($busca){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            });
        }

        if ($busca['marca'] != 'todos'){
            $produtoQuery->where('marca', 'ilike', strtoupper($busca['marca']));
        }
        if ($busca['linha'] != 'todos'){
            $produtoQuery->where('linha', 'ilike', strtoupper($busca['linha']));
        }
        if ($busca['grupo'] != 'todos'){

            $produtoQuery->where('produto_grupos.descricao','ilike', "%" . strtoupper($busca['grupo']) . "%");
        }
        if (!empty($busca['codigo'])){
            $produtoQuery->where('codigo_produto', 'ilike', strtoupper($busca['codigo']));
        }
        if (!empty($busca['nome'])){
            $produtoQuery->where('produto_especificacaos.descricao', 'ilike', $busca['nome']);
        }

        $result = $produtoQuery->get();
        $codigo = $result->pluck('codigo_produto');
        $totalTransitos = $this->ComprasTransitos($codigo);
        $out = [];
        $total = ['quantidade' => 0, 'valor' => 0];
        $estabelecimentos = returnEmpresasNasajonView();
        $estabelecimento = null;

        foreach($result as $produto){
            $saidaestoque = 0;
            $saidacompra = 0;
            $venda = 0;
            $codestabelecimento = null;
            foreach($produto->estoque as $estoque){
                $estabelecimento = $estabelecimentos[(integer)$estoque->estabelecimento];
                if(empty($busca['estabelecimento'])){
                    $estabelecimento = 'todos';
                }
                $saidaestoque += $estoque->estoque;
                $codestabelecimento = (isset($estoque->estabelecimento)) ? ((integer)$estoque->estabelecimento) : null;
            }
            foreach($produto->vendas as $vend){
                $estabelecimento = $estabelecimentos[(integer)$vend->estabelecimento];
                if(empty($busca['estabelecimento'])){
                    $estabelecimento = 'todos';
                }
                $venda += ($vend->codigo_produto == $produto->codigo_produto) ? $vend->valor : 0;
                $codestabelecimento = (isset($estoque->estabelecimento)) ? ((integer)$estoque->estabelecimento) : null;
            }
            $saidacompra = $totalTransitos->where('produto', $produto->codigo_produto)->sum('quantidade');
            foreach($produto->compras as $compras){
                if($compras->situacao === 'Parcialmente Liquidado'){
                    $saidacompra += $compras->quantidade_restante;
                }else{
                    $saidacompra += $compras->quantidade;
                }
            }
            $quantidade_remessa = $produto->movimentacao->whereIn('cfop',$this->cfop_remessas)->sum('quantidade');
            if($quantidade_remessa > 0){
                $venda += $quantidade_remessa;
            }

            if($busca['compra'] == 'ok' && !is_null($estabelecimento) && $quantidade_remessa > 0){
                if((($busca['qtd_mes_estoque'] * ($venda/$busca['qtd_mes_media'])) - ($saidaestoque + $saidacompra)) > 0){
                    foreach($produto->movimentacao->whereIn('cfop',$this->cfop_remessas) as $remessa) {
                        $out[] = [
                            'codigo_produto' => $remessa->produto_codigo,
                            'produto' => $produto->descricao,
                            'quantidade' => parserQtd($remessa['quantidade']),
                            'pedido' => $remessa->notasNasajon->pedido->numero,
                            'nota' => $remessa->documento,
                            'serie' => $remessa->notasNasajon->serie,
                            'valor' => parserValor($remessa->preco),
                        ];

                        $total['quantidade'] += $remessa->quantidade;
                        $total['valor'] += $remessa->preco;
                    }
                }
            }else if($quantidade_remessa > 0){
                foreach($produto->movimentacao->whereIn('cfop',$this->cfop_remessas) as $remessa) {
                    $out[] = [
                        'codigo_produto' => $remessa->produto_codigo,
                        'produto' => $produto->descricao,
                        'quantidade' => parserQtd($remessa->quantidade),
                        'pedido' => $remessa->notasNasajon->pedido->numero,
                        'nota' => $remessa->documento,
                        'serie' => $remessa->notasNasajon->serie,
                        'valor' => parserValor($remessa->preco),
                    ];

                    $total['quantidade'] += $remessa->quantidade;
                    $total['valor'] += $remessa->preco;
                }
            }
        }

        $total['quantidade'] = parserQtd($total['quantidade']);
        $total['valor'] = parserValor($total['valor']);

        return view('programs.analise_compra_mes.modal.remessas')->with(["dados" => $out,"total" => $total]);
    }

}