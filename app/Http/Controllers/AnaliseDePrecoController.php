<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Auth;
use Carbon\Carbon;
use App\ProdutoEspecificacao;
use App\Http\Requests\AnaliseDePrecoRequest;
use Illuminate\Http\Request;




class AnaliseDePrecoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AnaliseDePreco") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\AnaliseDePreco');
    	return view("programs.analise_preco.index");
    }

    public function filter(AnaliseDePrecoRequest $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $busca = $request->only('grupo', 'codigo','nome', 'marca', 'linha', 'markup_abaixo', 'markup_acima');
        $ProdutoEspecificacaoObj = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['estoque' => function($query){
            $query->select('codigo_produto', DB::Raw('SUM(estoque) as estoque'))
            ->groupBy('codigo_produto');
        }])
        ->with(['preco' => function($query){
            $query->select('codigo_produto', DB::Raw('max(preco_real) as preco_real, max(preco_dolar) as preco_dolar, max(codigo_produto) as codigo_produto, 
            max(compra_real) as compra_real, max(compra_dolar) as compra_dolar, max(ultima_compra_real) as ultima_compra_real'))
            ->groupBy('codigo_produto');

        }])
        ->with(['custos' => function($query){
            $query->select('produto_codigo', 'custo_medio_gerencial')
             ->groupBy('produto_codigo', 'estabelecimento', 'custo_medio_gerencial');
        }])
        ->with(['vendas' => function($query) {
            $date = Carbon::now();
            $date = $date->subMonth(6);
            $mes =  $date->format('m');
            $ano = $date->format('Y');
            $query->where(DB::Raw("cast(concat(ano_data_entrada,'-', lpad(mes_data_entrada,2,'0'),'-01') as date)"), '>=', $ano."-".$mes."-01")
            ->groupBy('codigo_produto')
            ->select('codigo_produto', DB::Raw('sum(quantidade) as valor'));
        }])
        ->with(['movimentacao' => function($query) {
            $cnpj_excluir = [];
            $cnpj_excluir[] = '05075884000167';
            $cnpj_excluir[] = '05075884000248';
            $cnpj_excluir[] = '06311274000269';
            $cnpj_excluir[] = '06311274000340';
            $cnpj_excluir[] = '08';
            $cnpj_excluir[] = '07';
            $cnpj_excluir[] = '06311274000501';
            $cnpj_excluir[] = '06311274000420';

            $date = Carbon::now();
            $date = $date->subMonth(6);
            $mes =  $date->format('m');
            $ano = $date->format('Y');

            $query->select('produto_codigo', 'data_movimentacao', 'quantidade', DB::Raw('(quantidade * preco) as soma_preco'))
            ->where('sinal', 'SAIDA')
            ->whereNotIn('cliente_codigo', $cnpj_excluir)
            ->where('documento', '!=', ' ')
            ->where('data_movimentacao', '!=', null)
            // ->whereIn('cfop', [5922, 5949, 6108, 6110, 6119, 6123, 6106, 5123, 6118, 5118, 5122, 5551, 6551, 5102, 6102, 5106, 5101, 6101])
            ->whereIn('cfop', [5922, 6108, 6110, 6119, 6123, 6106, 5123, 6118, 5118, 5122, 5551, 6551, 5102, 6102, 5106, 5101, 6101])
            ->where('data_movimentacao', '>=', $ano."-".$mes."-01")
            ->groupBy('produto_codigo', 'data_movimentacao', 'quantidade', 'preco');
        }])
        ->where('ativo', true);    
        

        if(!empty($busca['marca'])){
            $ProdutoEspecificacaoObj->where('marca', 'ilike', "%" . strtoupper($busca['marca']) . "%");
        }
        if(!empty($busca['linha'])){
            $ProdutoEspecificacaoObj->where('linha', 'ilike', "%" . strtoupper($busca['linha']) . "%");
        }
        if(!empty($busca['grupo'])){
            $ProdutoEspecificacaoObj->where('produto_grupos.descricao', 'ilike', "%" . strtoupper($busca['grupo']) . "%");
        }
        if(!empty($busca['codigo'])){
            $ProdutoEspecificacaoObj->where(DB::Raw("lower(codigo_produto)"), strtolower($busca['codigo']));
        }
        if(!empty($busca['nome'])){
            $ProdutoEspecificacaoObj->where('produto_especificacaos.descricao', 'ilike', $busca['nome']);
        }
        if (isset($busca['markup_acima']) && !is_null($busca['markup_acima'])){
            $markup = str_replace(',', '.', str_replace('.', '', $busca['markup_acima']));
            $ProdutoEspecificacaoObj->whereHas('preco', function($query) use($markup){
                $query->where('compra_real', '>', 0);
                $query->where(DB::Raw("( ( (preco_real / compra_real) - 1 ) * 100 )"), '>=', $markup); 
            });
        }
        if (isset($busca['markup_abaixo']) && !is_null($busca['markup_abaixo'])){
            $markup = str_replace(',', '.', str_replace('.', '', $busca['markup_abaixo']));
            $ProdutoEspecificacaoObj->whereHas('preco', function($query) use($markup){
                $query->where('compra_real', '>', 0);
                $query->where(DB::Raw("( ( (preco_real / compra_real) - 1 ) * 100 )"), '<=', $markup);
            });
        }

        $ProdutoEspecificacao = $ProdutoEspecificacaoObj->get();
      
        $saida = [];

        foreach($ProdutoEspecificacao as $produto){
            $subgrupo = $produto->subgrupo;
            $marca =  $produto->marca;
            $linha = $produto->linha;
            $grupo = $produto->grupo;

            $totalEstoque =  $produto->estoque->sum('estoque');
            $totalVenda = $produto->vendas->sum('valor');
            $somaPreco = $produto->movimentacao->sum('soma_preco');
            $somaQtd = $produto->movimentacao->sum('quantidade');

            $media_vendas = $totalVenda/6;
            
            $ultima_compra = $produto->preco->compra_real;
            $preco_venda = $produto->preco->preco_real;
            $data_ultima_compra = $produto->preco->ultima_compra_real;
            $custo_gerencial_medio = $produto->custos->max('custo_medio_gerencial');

            $chave = $grupo.$marca.$subgrupo.$linha.$ultima_compra.$preco_venda;

            if(!isset($saida[$chave])){
                $filter = [
                    'estabelecimento' => null,
                ];
                $saida[$chave] = [
                    'subgrupo' => $subgrupo,
                    'marca' => $marca,
                    'linha' => $linha,
                    'grupo' => $grupo,
                    'markup_lista' => $produto->preco->compra_real > 0 ? (parserValor((($produto->preco->preco_real / $produto->preco->compra_real)-1)*100).'%'):'',
                    'markup_venda' => 0,
                    'ultima_compra' => empty($ultima_compra) ? 0 : $ultima_compra,
                    'data_ultima_compra' => $data_ultima_compra,
                    'custo_gerencial_medio' => (empty($custo_gerencial_medio)) ? '' : parserValor($custo_gerencial_medio),
                    'preco_venda' => (empty($preco_venda)) ? '' : parserValor($preco_venda),
                    'preco_medio' => 0,
                    'dias_parado' => 0,
                    'unidade' => $produto->unidade,
                    'meses_estoque' => 0,
                    'total_itens' => 0,
                    'estoque' => 0,
                    'somaPrecoTotal' => 0,
                    'somaQtdTotal' => 0,
                    'media' => 0,
                    'filters' => $filter
                ];
            }
            $saida[$chave]['filters']['produtos_codigo'][] = $produto->codigo_produto;
            $saida[$chave]['hash']['produtos_codigo'][] = $produto->codigo_produto;
            $saida[$chave]['estoque'] += $totalEstoque;
            $saida[$chave]['media'] += $media_vendas;
            $saida[$chave]['total_itens'] += 1;
            $saida[$chave]['somaPrecoTotal'] += $somaPreco;
            $saida[$chave]['somaQtdTotal'] += $somaQtd;
            $saida[$chave]['dias_parado'] = Carbon::parse($produto->movimentacao->max('data_movimentacao'))->diffInDays();
        }

       

        foreach($saida as $key => $value){
            $meses_estoque = ($saida[$key]['media'] > 0 ) ? $saida[$key]['estoque']/$saida[$key]['media'] : '';
            $preco_medio = $saida[$key]['somaQtdTotal'] > 0 ? ($saida[$key]['somaPrecoTotal']/$saida[$key]['somaQtdTotal']): 0;
            $compra_real = $saida[$key]['ultima_compra'];
            $saida[$key]['ultima_compra'] = $saida[$key]['ultima_compra'] > 0 ? parserValor($saida[$key]['ultima_compra']) : '';
            $saida[$key]['meses_estoque'] = ($meses_estoque == 0) ? '' : parserQtd($meses_estoque);
            $saida[$key]['filters'] = encrypt($saida[$key]['filters']);
            $saida[$key]['hash'] = encrypt($saida[$key]['hash']);
            $saida[$key]['preco_medio'] = $preco_medio > 0 ? parserValor($preco_medio) : '';
            $saida[$key]['estoque'] = ($saida[$key]['estoque'] == 0) ? '' :parserQtd($saida[$key]['estoque']);
            $saida[$key]['media'] = ($saida[$key]['media'] == 0) ? '' : parserQtd($saida[$key]['media']);
            $saida[$key]['dias_parado'] = $saida[$key]['dias_parado'] > 0 ? $saida[$key]['dias_parado'] : '';
            $saida[$key]['data_ultima_compra'] = ($saida[$key]['data_ultima_compra'] != '') ? parserData($saida[$key]['data_ultima_compra']) : '';
            $saida[$key]['markup_venda'] = ($preco_medio > 0 && $compra_real > 0) ? (parserValor(((($preco_medio/$compra_real)-1)*100))).'%' : '';
        }

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['saida' => $saida],
        ]);
    }

    public function produtoEstoque(Request $request){
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

        $produtoQuery = ProdutoEspecificacao::with(['estoque' => function($query) use ($busca){
            if(isset($busca['estabelecimento'])){
                $query->where('estabelecimento', str_pad($busca['estabelecimento'],2,'0', STR_PAD_LEFT));
            }else{
                $query->selectRaw('estabelecimento, codigo_produto, sum(compras_aberto) as compras_aberto, sum(estoque) as estoque')->groupBy('estabelecimento', 'codigo_produto');
            }
        }])
        ->whereHas('estoque' , function($query) use ($busca){
            $query->where('estoque', '>', 0);
        })->has('estoque')
        ->whereIn('codigo_produto', $busca['produtos_codigo']);

        $result = $produtoQuery->get();
        $out = [];
        $estabelecimentos = returnEmpresasNasajonView();
        $totalEStoque = 0;

        foreach($result as $produto){
            $popOver = '';

            foreach($produto->estoque as $estoque){
                $popOver .= '<p>'.$estabelecimentos[(integer)$estoque->estabelecimento].' - '.parserQtd($estoque->estoque).'</p>';
            }

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
        return view('programs.produto.modal.busca_estoque')->with(["dados" => $out, 'totalEStoque' => $totalEStoque]);
    }

    public function modalProdutos(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('hash');
        $busca = decrypt($fields['hash']);
        $ProdutoEspecificacaoObj = ProdutoEspecificacao::with(['estoque' => function($query){
            $query->select('codigo_produto', DB::Raw('SUM(estoque) as estoque'))
            ->groupBy('codigo_produto');
        }])
        ->with(['preco' => function($query){
            $query->select('codigo_produto', DB::Raw('max(preco_real) as preco_real, max(preco_dolar) as preco_dolar, max(codigo_produto) as codigo_produto, 
            max(compra_real) as compra_real, max(compra_dolar) as compra_dolar, max(ultima_compra_real) as ultima_compra_real'))
            ->groupBy('codigo_produto');

        }])
        ->with(['movimentacao' => function($query) {
            $cnpj_excluir = [];
            $cnpj_excluir[] = '05075884000167';
            $cnpj_excluir[] = '05075884000248';
            $cnpj_excluir[] = '06311274000269';
            $cnpj_excluir[] = '06311274000340';
            $cnpj_excluir[] = '08';
            $cnpj_excluir[] = '07';
            $cnpj_excluir[] = '06311274000501';
            $cnpj_excluir[] = '06311274000420';

            $date = Carbon::now();
            $date = $date->subMonth(6);
            $mes =  $date->format('m');
            $ano = $date->format('Y');

            $query->select('produto_codigo', 'data_movimentacao', 'quantidade', DB::Raw('(quantidade * preco) as soma_preco'))
            ->where('sinal', 'SAIDA')
            ->whereNotIn('cliente_codigo', $cnpj_excluir)
            ->where('documento', '!=', ' ')
            ->where('data_movimentacao', '!=', null)
            //->whereIn('cfop', [5922, 5949, 6108, 6110, 6119, 6123, 6106, 5123, 6118, 5118, 5122, 5551, 6551, 5102, 6102, 5106, 5101, 6101])
            ->whereIn('cfop', [5922, 6108, 6110, 6119, 6123, 6106, 5123, 6118, 5118, 5122, 5551, 6551, 5102, 6102, 5106, 5101, 6101])
            ->where('data_movimentacao', '>=', $ano."-".$mes."-01")
            ->groupBy('produto_codigo', 'data_movimentacao', 'quantidade', 'preco');
        }])
        ->where('ativo', true)
        ->whereIn('codigo_produto', $busca['produtos_codigo']);


        $ProdutoEspecificacao = $ProdutoEspecificacaoObj->get();
      
        $saida = [];

        foreach($ProdutoEspecificacao as $produto){
            $totalEstoque =  $produto->estoque->sum('estoque');
            $somaPreco = $produto->movimentacao->sum('soma_preco');
            $somaQtd = $produto->movimentacao->sum('quantidade');

            $ultima_compra = $produto->preco->compra_real;
            $preco_venda = $produto->preco->preco_real;
            
            $saida[] = [
                'descricao' => $produto->descricao,
                'codigo_produto' => $produto->codigo_produto,
                'markup_lista' => $produto->preco->compra_real > 0 ? (parserValor((($produto->preco->preco_real / $produto->preco->compra_real)-1)*100).'%'):'',
                'ultima_compra' => (empty($ultima_compra)) ? '' : parserValor($ultima_compra),
                'preco_venda' => (empty($preco_venda)) ? '' : parserValor($preco_venda),
                'preco_medio' => ($somaQtd && $somaPreco > 0) ? (parserValor($somaPreco/$somaQtd)) : '',
                'estoque' => $totalEstoque > 0 ? parserQtd($totalEstoque) : '',
            ];
           
        }

        return view('programs.analise_preco.modal.produtos')->with(['produtos' => $saida]);
    }
}
