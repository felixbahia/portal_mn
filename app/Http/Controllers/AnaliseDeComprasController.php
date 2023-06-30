<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\ProdutoEspecificacao;
use App\Movimentacao;
use App\Http\Requests\AnaliseComprasConsultaRequest;

use Carbon\Carbon;

class AnaliseDeComprasController extends Controller
{
    private $cfop_remessas = ["5901","5910","5911","5912","5924","6901","6910","6911","6912","6924"];
    private $cfop_venda = ['5922', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101', '5104', '6104'];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AnaliseDeCompra") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\AnaliseDeCompra');
        $meses = [
            "atual" => parserNameMonth(date("m")),
            "mes_1" => parserNameMonth(date("m", strtotime("+1 months"))),
            "mes_2" => parserNameMonth(date("m", strtotime("+2 months")))
        ];
    	return view("programs.analise_compras.index")->with(["meses" => $meses]);
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

    public function filter(AnaliseComprasConsultaRequest $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only(['grupo','codigo','nome','marca','linha','qtd_meses', 'inativos']);
        $cnpj_intercompany = $this->cnpjINtercompany();

        $grupo = 'Todos';
        $marca = 'Todos';
        $linha = 'Todos';
        $codigos = 'Todos';
        $nome = 'Todos';
        $meses = $fields['qtd_meses'];

        $mesAtual = Carbon::now();
        $mesAtual->day = (int) $mesAtual->daysInMonth;
        $proximoMes = Carbon::now()->addMonthNoOverflow();
        $mesFuturo = Carbon::now()->addMonthsNoOverflow(2);

        $meses_anterios = $fields['qtd_meses'] - 1;
        $date = $mesAtual->subMonth($meses_anterios);
        $mes =  $date->format('m');
        $ano = $date->format('Y');


        $produtos = ProdutoEspecificacao::with([
            'produtoGrupo',
            'estoque' => function($query){
                $query->selectRaw('codigo_produto ,sum(estoque) as estoque')
                ->groupBy('codigo_produto');
            },
            'entradas' => function($query) use ($ano,$mes,$mesAtual){
                $query->selectRaw('codigo_produto ,sum(quantidade) as quantidade, max(data_entrada) as data_entrada')
                ->whereBetween('data_entrada', [$ano.'-'.$mes.'-01', $mesAtual->format('Y-m-d')])
                ->groupBy('codigo_produto');
            },
            'compras' => function($query) use ($mesAtual,$mesFuturo){
                $query->selectRaw("cod_produto, situacao, sum(quantidade) as quantidade, sum(quantidade_restante) as quantidade_restante, previsao_entrega")
                ->whereIn('situacao', ['Aberto', 'Aguardando Documento','Parcialmente Liquidado'])
                ->where('previsao_entrega', '<',  $mesFuturo->format('Y-m-t'))
                ->groupBy(DB::Raw("previsao_entrega,situacao, cod_produto"));
            },
            'movimentacao' => function($query) use ($ano,$mes,$cnpj_intercompany){
                $query->select(DB::raw('produto_codigo,cfop, sum(quantidade) as quantidade'))
                    ->where(function($query){
                        $query->whereIn('cfop',$this->cfop_remessas)
                        ->orWhereIn('cfop',$this->cfop_venda);
                    })
                    ->where('sinal', 'ilike', 'saida')
                    ->whereNotIn("cliente_codigo", $cnpj_intercompany)
                    ->where('data_movimentacao','>=', $ano.'-'.$mes.'-01')
                    ->groupBy("produto_codigo","cfop");
            }
        ])
        ->select('*');
      if(!empty($fields['grupo'])){
            $produtos->whereHas('produtoGrupo', function($query) use($fields){
                $query->where('descricao', strtoupper($fields['grupo']));
            });            
            $grupo = strtoupper($fields['grupo']);
        }
        if(!empty($fields['marca'])){
            $produtos->where('marca', strtoupper($fields['marca']));
            $marca = strtoupper($fields['marca']);
        }
        if(!empty($fields['linha'])){
            $produtos->where('linha', strtoupper($fields['linha']));
            $linha = strtoupper($fields['linha']);
        }
        if(!empty($fields['codigo'])){
            $produtos->where('codigo_produto', strtoupper($fields['codigo']));
            $codigos = strtoupper($fields['codigo']);
        }
        if(!empty($fields['nome'])){
            $produtos->where('produto_especificacaos.descricao', strtoupper($fields['nome']));
            $nome = strtoupper($fields['nome']);
        }
        if(isset($fields['inativos']) && !is_null($fields['inativos'])){
            $produtos->where('ativo', $fields['inativos']);
            $inativos = $fields['inativos'];
        }
        
        $produtos = $produtos->get();
        $codigo_produto = $produtos->pluck('codigo_produto')->toArray();
        $compras = 0;
        $vendas = 0;
        $remessas = 0;
        $datacompra = null;
        $estoque_disponivel = 0;
        $a_receber_mes_atual = 0;
        $a_receber_mes_mais1 = 0;
        $a_receber_mes_mais2 = 0;
        $vendas = 0;
        $remessas = 0;
        foreach($produtos as $produto){

            if(!empty($produto->movimentacao[0])){
                foreach($produto->movimentacao as $movimentacao){
                    if(in_array($movimentacao->cfop,$this->cfop_venda)){
                        $vendas += $movimentacao->quantidade;
                    }

                    if(in_array($movimentacao->cfop,$this->cfop_remessas)){
                        $remessas += $movimentacao->quantidade;
                    }
                }
            }

            $compras += (isset($produto->entradas[0]->quantidade)) ? $produto->entradas[0]->quantidade : 0;
            $estoque_disponivel += (isset($produto->estoque[0]->estoque)) ? $produto->estoque[0]->estoque : 0;
            $data = (isset($produto->entradas[0]->data_entrada)) ? Carbon::createFromFormat('Y-m-d', $produto->entradas[0]->data_entrada) : null;
            if(isset($data) && empty($datacompra)){
                $datacompra = Carbon::createFromFormat('Y-m-d', $produto->entradas[0]->data_entrada);
            }
            if(!empty($datacompra) && !empty($data) && $data->gte($datacompra)){
                $datacompra = Carbon::createFromFormat('Y-m-d', $produto->entradas[0]->data_entrada);
            }
            foreach($produto->compras as $compra){
                $previsao_entrega = Carbon::parse($compra->previsao_entrega);

                $a_receber_mes_atual += ($compra->situacao == 'Parcialmente Liquidado' && $previsao_entrega->lte($mesAtual)) ? $compra->quantidade_restante : 0;
                $a_receber_mes_atual += ($compra->situacao != 'Parcialmente Liquidado' && $previsao_entrega->lte($mesAtual)) ? $compra->quantidade : 0;

                $a_receber_mes_mais1 += ($compra->situacao == 'Parcialmente Liquidado' && $previsao_entrega->format('m') == $proximoMes->format('m')) ? $compra->quantidade_restante : 0;
                $a_receber_mes_mais1 += ($compra->situacao != 'Parcialmente Liquidado' && $previsao_entrega->format('m') == $proximoMes->format('m')) ? $compra->quantidade : 0;

                $a_receber_mes_mais2 += ($compra->situacao == 'Parcialmente Liquidado' && $previsao_entrega->format('m') == $mesFuturo->format('m')) ? $compra->quantidade_restante : 0;
                $a_receber_mes_mais2 += ($compra->situacao != 'Parcialmente Liquidado' && $previsao_entrega->format('m') == $mesFuturo->format('m')) ? $compra->quantidade : 0;
            }
        }

        $media_remessas = $remessas / $fields['qtd_meses'];
        $media_venda = $vendas / $fields['qtd_meses'];
        $anterior = $compras - ($estoque_disponivel + $vendas);
        $anterior = $anterior > 0 ? $anterior : 0;

        if(empty($produtos)){
            return response()->json([
                'status' => 'sucess',
                'message' => '',
                'error' => '', 
                'response' => ['out' => null, 'meses' => null],
            ]);
        }
        unset($produtos);
        $transito = DB::connection('nasajon')->table('integracoes.vw_saldos_em_transitos')->whereBetween('data', [$mesAtual->format('Y-m-1'), $mesFuturo->format('Y-m-t')])->whereIn('produto', $codigo_produto)->selectRaw("date_part('month', data) as mes, quantidade")->get();
        $retorno = [];
        $a_receber_mes_atual += $transito->Where('mes', $mesAtual->format('m'))->sum('quantidade');
        unset($transito);
        if(!empty($compras) || !empty($vendas) || !empty($estoque_disponivel) || !empty($media_venda) || !empty($anterior) || !empty($a_receber_mes_atual) || !empty($a_receber_mes_mais1) || !empty($a_receber_mes_mais2))
            $retorno[] = [
                'marca' => (empty($fields['codigo']) && empty($fields['nome'])) ? $marca : $produto->marca,
                'linha' => (empty($fields['codigo']) && empty($fields['nome'])) ? $linha : $produto->linha,
                'nome' => (empty($fields['codigo']) && empty($fields['nome'])) ? $nome : $produto->descricao,
                'grupo' => (empty($fields['codigo']) && empty($fields['nome'])) ? $grupo : $produto->produtoGrupo->descricao,
                'qtd_meses' => $fields['qtd_meses'],
                'filter' => encrypt($fields),
                "comprado" => (!empty($compras))?parserQtd($compras) : "",
                "data_compra" => (!empty($datacompra))?parserData($datacompra) : "",
                "remessas" => $remessas > 0 ? parserQtd($remessas) : "",
                "vendido" => $vendas > 0 ? parserQtd($vendas) : "",
                "estoque" => (floatval($estoque_disponivel) > 0) ? parserQtd($estoque_disponivel) : "",
                "media_remessas" => (floatval($media_remessas) > 0) ? parserQtd($media_remessas) : "",
                "media_venda" => (floatval($media_venda) > 0) ? parserQtd($media_venda) : "",
                "saldo_anterior" => (floatval($anterior) > 0) ? parserQtd($anterior) : "",
                "a_receber_atual" => (floatval($a_receber_mes_atual) > 0) ? parserQtd($a_receber_mes_atual) : "",
                "a_receber_mes_1" => (floatval($a_receber_mes_mais1) > 0) ? parserQtd($a_receber_mes_mais1) : "",
                "a_receber_mes_2" => (floatval($a_receber_mes_mais2) > 0) ? parserQtd($a_receber_mes_mais2) : ""
            ];
       
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '', 
            'response' => ['out' => $retorno, 'meses' => $meses],
        ]);
    }

    public function modalArtigos(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters']);
        $cnpj_intercompany = $this->cnpjINtercompany();
        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $meses = [
            "atual" => parserNameMonth(date("m")),
            "mes_1" => parserNameMonth(date("m", strtotime("+1 months"))),
            "mes_2" => parserNameMonth(date("m", strtotime("+2 months")))
        ];
        $mesqtd = $fields['qtd_meses'];
        $mesAtual = Carbon::now();
        $mesAtual->day = (int) $mesAtual->daysInMonth;
        $proximoMes = Carbon::now()->addMonthNoOverflow();
        $mesFuturo = Carbon::now()->addMonthsNoOverflow(2);

        $meses_anterios = $fields['qtd_meses'] - 1;
        $date = Carbon::now();
        $date = $date->subMonth($meses_anterios);
        $mes =  $date->format('m');
        $ano = $date->format('Y');
        
        $produtos = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with([
            'estoque' => function($query){
                $query->selectRaw('codigo_produto ,sum(estoque) as estoque')
                ->groupBy('codigo_produto');
            },
            'entradas' => function($query) use ($ano,$mes,$mesAtual){
                $query->selectRaw('codigo_produto ,sum(quantidade) as quantidade, max(data_entrada) as data_entrada')
                ->whereBetween('data_entrada', [$ano.'-'.$mes.'-01', $mesAtual->format('Y-m-d')])
                ->groupBy('codigo_produto');
            },
            'compras' => function($query) use ($mesAtual,$mesFuturo){
                $query->selectRaw("cod_produto, situacao, sum(quantidade) as quantidade, sum(quantidade_restante) as quantidade_restante,previsao_entrega")
                ->whereIn('situacao', ['Aberto', 'Aguardando Documento','Parcialmente Liquidado'])    
                ->where('previsao_entrega', '<', $mesFuturo->format('Y-m-t'))
                ->groupBy(DB::Raw("previsao_entrega,situacao, cod_produto"));
            },
            'movimentacao' => function($query) use ($ano,$mes,$cnpj_intercompany){
                $query->select(DB::raw('produto_codigo,cfop, sum(quantidade) as quantidade'))
                    ->where(function($query){
                        $query->whereIn('cfop',$this->cfop_remessas)
                        ->orWhereIn('cfop',$this->cfop_venda);
                    })
                    ->where('sinal', 'ilike', 'saida')
                    ->whereNotIn("cliente_codigo", $cnpj_intercompany)
                    ->where('data_movimentacao','>=', $ano.'-'.$mes.'-01')
                    ->groupBy("produto_codigo","cfop");
            }
        ])
        ->selectRaw('codigo_produto, produto_especificacaos.descricao as descricao ,produto_grupos.descricao as grupo,marca, linha');

        if(isset($fields['grupo']) && !is_null($fields['grupo'])){
           
            $produtos->where('produto_grupos.descricao', strtoupper($fields['grupo'])); 
        }
        if(isset($fields['marca']) && !is_null($fields['marca'])){
            $produtos->where('marca', $fields['marca']);
        }
        if(isset($fields['linha']) && !is_null($fields['linha'])){
            $produtos->where('linha', $fields['linha']);
        }
        if(isset($fields['codigo']) && !is_null($fields['codigo'])){
            $produtos->where('codigo_produto', $fields['codigo']);
        }
        if(isset($fields['nome']) && !is_null($fields['nome'])){
            $produtos->where('produto_especificacaos.descricao', $fields['nome']);
        }
        if(isset($fields['inativos']) && !is_null($fields['inativos'])){
            $produtos->where('ativo', $fields['inativos']);
        }
        $produtos = $produtos->get();

        $transito = DB::connection('nasajon')->table('integracoes.vw_saldos_em_transitos')->whereBetween('data', [$mesAtual->format('Y-m-1'), $mesFuturo->format('Y-m-t')])->whereIn('produto', $produtos->pluck('codigo_produto'))->selectRaw("produto, date_part('month', data) as mes, quantidade")->get();
            
        $retorno = [];
        $total = [
            "comprado" => 0,
            "vendido" => 0,
            "remessas" => 0,
            "estoque" => 0,
            "media_venda" => 0,
            "media_remessas" => 0,
            "saldo_anterior" => 0,
            "a_receber_atual" => 0,
            "a_receber_mes_1" => 0,
            "a_receber_mes_2" => 0,
        ];
        foreach ($produtos as $produto) {
            $compras = 0;
            $estoque_disponivel = 0;
            $media_venda = 0;
            $vendas = 0;
            $remessas = 0;
            $dataentrada = null;
            $a_receber_mes_atual = 0;
            $a_receber_mes_mais1 = 0;
            $a_receber_mes_mais2 = 0;

            foreach($produto->estoque as $estoque){
                $estoque_disponivel += $estoque->estoque;
            }
            foreach($produto->movimentacao as $venda){
                if(in_array($venda->cfop,$this->cfop_venda)){
                    $vendas += $venda->quantidade;
                }
            }
            foreach($produto->movimentacao as $remessa){
                if(in_array($venda->cfop,$this->cfop_remessas)){
                    $remessas += $remessa->quantidade;
                }
            }
            foreach($produto->entradas as $entradas){
                $compras += $entradas->quantidade;
                $dataentrada = $entradas->data_entrada;
            }
            foreach($produto->compras as $compra){
                $previsao_entrega = Carbon::parse($compra->previsao_entrega);

                $a_receber_mes_atual += ($compra->situacao == 'Parcialmente Liquidado' && $previsao_entrega->lte($mesAtual)) ? $compra->quantidade_restante : 0;
                $a_receber_mes_atual += ($compra->situacao != 'Parcialmente Liquidado' && $previsao_entrega->lte($mesAtual)) ? $compra->quantidade : 0;

                $a_receber_mes_mais1 += ($compra->situacao == 'Parcialmente Liquidado' && $previsao_entrega->format('m') == $proximoMes->format('m')) ? $compra->quantidade_restante : 0;
                $a_receber_mes_mais1 += ($compra->situacao != 'Parcialmente Liquidado' && $previsao_entrega->format('m') == $proximoMes->format('m')) ? $compra->quantidade : 0;

                $a_receber_mes_mais2 += ($compra->situacao == 'Parcialmente Liquidado' && $previsao_entrega->format('m') == $mesFuturo->format('m')) ? $compra->quantidade_restante : 0;
                $a_receber_mes_mais2 += ($compra->situacao != 'Parcialmente Liquidado' && $previsao_entrega->format('m') == $mesFuturo->format('m')) ? $compra->quantidade : 0;
            }

            $media_venda = $vendas / (integer)$fields['qtd_meses'];
            $media_remessas = $remessas / (integer)$fields['qtd_meses'];
            $anterior = ($compras > 0) ? $compras - ($estoque_disponivel + $vendas) : 0;
            if($anterior == $compras){
                $anterior = 0;
            }

            $a_receber_mes_atual += $transito->where('produto', $produto->codigo_produto)->sum('quantidade') ?? 0;
            
            if(empty($compras) && empty($vendas) && empty($estoque_disponivel) && empty($a_receber_mes_atual) && empty($a_receber_mes_mais1) && empty($a_receber_mes_mais2)){
                continue;
            }

            $total['comprado'] += floatval($compras);
            $total['vendido'] += floatval($vendas);
            $total['remessas'] += floatval($remessas);
            $total['estoque'] += floatval($estoque_disponivel);
            $total['saldo_anterior'] += (isset($anterior)) ? floatval($anterior) : 0;
            $total['a_receber_atual'] += floatval($a_receber_mes_atual);
            $total['a_receber_mes_1'] += floatval($a_receber_mes_mais1);
            $total['a_receber_mes_2'] += floatval($a_receber_mes_mais2);
            $retorno[] = [
                'marca' => $produto->marca,
                'linha' => $produto->linha,
                'grupo' => $produto->grupo,
                'produto' => $produto->descricao,
                'filter' => encrypt([
                    'marca' => $produto->marca,
                    'linha' => $produto->linha,
                    'grupo' => $produto->grupo,
                    'codigo' => $produto->codigo_produto,
                    'nome' => $produto->descricao,
                    'qtd_meses' => $fields['qtd_meses'],
                ]),
                "comprado" => ($compras > 0) ? parserValor($compras) : "",
                "data_entrada" => (!empty($dataentrada)) ? parserData($dataentrada) : "",
                "vendido" => ($vendas > 0) ? parserValor($vendas) : "",
                "remessas" => ($remessas > 0) ? parserValor($remessas) : "",
                "estoque" => ($estoque_disponivel > 0) ? parserValor($estoque_disponivel) : "",
                "media_venda" => ($media_venda != 0) ? parserValor($media_venda) : "",
                "media_remessas" => ($media_remessas != 0) ? parserValor($media_remessas) : "",
                "saldo_anterior" => ($anterior > 0) ? parserValor($anterior) : "",
                "a_receber_atual" => ($a_receber_mes_atual > 0) ? parserValor($a_receber_mes_atual) : "",
                "a_receber_mes_1" => ($a_receber_mes_mais1 > 0) ? parserValor($a_receber_mes_mais1) : "",
                "a_receber_mes_2" => ($a_receber_mes_mais2 > 0) ? parserValor($a_receber_mes_mais2) : ""
            ];
        }
        unset($transito);
        unset($produtos);
        $media_venda = floatval($total['vendido']) / (integer)$fields['qtd_meses'];
        $media_remessas = floatval($total['remessas']) / (integer)$fields['qtd_meses'];
        $saldoanterior = $total['comprado'] - ($total['vendido'] + $total['estoque']);
        $total = [
            "saldo_anterior" => ($saldoanterior > 0) ? parserQtd($saldoanterior) : '',
            "comprado" => ($total['comprado'] > 0) ? parserQtd($total['comprado']) : '',
            "vendido" => ($total['vendido'] > 0) ? parserQtd($total['vendido']) : '',
            "remessas" => ($total['remessas'] > 0) ? parserQtd($total['remessas']) : '',
            "estoque" => ($total['estoque'] > 0) ? parserQtd($total['estoque']) : '',
            "media_venda" => ($media_venda > 0) ? parserQtd($media_venda) : '',
            "media_remessas" => ($media_remessas > 0) ? parserQtd($media_remessas) : '',
            "a_receber_atual" => ($total['a_receber_atual'] > 0) ? parserQtd($total['a_receber_atual']) : '',
            "a_receber_mes_1" => ($total['a_receber_mes_1'] > 0) ? parserQtd($total['a_receber_mes_1']) : '',
            "a_receber_mes_2" => ($total['a_receber_mes_2'] > 0) ? parserQtd($total['a_receber_mes_2']) : '',
        ];
    	return view("programs.analise_compras.modal.artigos")->with(["meses" => $meses, "mes" => $mesqtd, "total" => $total, "retorno" => $retorno, 'grupo' => $fields['grupo'], 'mes' => $fields['qtd_meses']]);
    }

    public function modalComprados(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters']);
        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $mesAtual = Carbon::now();

        $meses_anterios = $fields['qtd_meses'] - 1;
        $date = Carbon::now();
        $date = $date->subMonth($meses_anterios);
        $mes =  $date->format('m');
        $ano = $date->format('Y');
        
        $produtos = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['entradas' => function($query) use ($ano,$mes,$mesAtual){
            $query->selectRaw('codigo_produto ,sum(quantidade) as quantidade, estabelecimento, nota_serie, nota, forncedor_codigo, pedido, proforma, data_entrada')
            ->whereBetween('data_entrada', [$ano.'-'.$mes.'-01', $mesAtual->format('Y-m-d')])
            ->groupBy('codigo_produto','estabelecimento','nota_serie','nota','forncedor_codigo','pedido','proforma','data_entrada');
        }])
        ->selectRaw('codigo_produto, produto_especificacaos.descricao as descricao,produto_grupos.descricao as grupo,marca, linha');
        if(isset($fields['grupo']) && !is_null($fields['grupo'])){
           
            $produtos->where('produto_grupos.descricao', strtoupper($fields['grupo'])); 
        }
        if(isset($fields['marca']) && !is_null($fields['marca'])){
            $produtos->where('marca', $fields['marca']);
        }
        if(isset($fields['linha']) && !is_null($fields['linha'])){
            $produtos->where('linha', $fields['linha']);
        }
        if(isset($fields['codigo']) && !is_null($fields['codigo'])){
            $produtos->where('codigo_produto', $fields['codigo']);
        }
        if(isset($fields['nome']) && !is_null($fields['nome'])){
            $produtos->where('produto_especificacaos.descricao', $fields['nome']);
        }
        if(isset($fields['inativos']) && !is_null($fields['inativos'])){
            $produtos->where('ativo', $fields['inativos']);
        }
        $produtos = $produtos->get();
        
        $retorno = [];
        $estabelecimentos = returnEmpresasNasajonView();
        $total = [
            "comprado" => 0,
        ];
        foreach ($produtos as $produto) {
            foreach($produto->entradas as $entrada){
                if($entrada->quantidade > 0){
                    $total['comprado'] += floatval($entrada->quantidade);
                    
                    $linha = [];
                    
                    $linha['estabelecimento'] = $estabelecimentos[(integer)$entrada->estabelecimento];
                    
                    if(!empty($entrada->nota_serie)){
                        $linha['nota'] = $entrada->nota . ' - ' . $entrada->nota_serie;
                    }
                    else{
                        $linha['nota'] = $entrada->nota;
                    }

                    $linha['fornecedor'] = $entrada->forncedor_codigo;
                    $linha['pedido'] = $entrada->pedido;
                    $linha['proforma'] = $entrada->proforma;
                    $linha['entrada'] = parserData($entrada->data_entrada);
                    $linha["codigo"] = $entrada->codigo_produto;
                    $linha["quantidade"] = parserValor($entrada->quantidade);

                    if(!empty($entrada->notaEntradaNasajon)){
                        $linha['nota_id'] = $entrada->notaEntradaNasajon['Identificador Documento'];
                    }
                    else{
                        $linha['nota_id'] = '';
                    }
                    $retorno[] = $linha;
                }
            }
        }
        unset($produtos);
        $total = [
            "comprado" => ($total['comprado'] > 0) ? parserQtd($total['comprado']) : '',
        ];

    	return view("programs.analise_compras.modal.comprados")->with(["total" => $total, "retorno" => $retorno, 'grupo' => $fields['grupo'], 'mes' => $fields['qtd_meses']]);
    }

    public function modalVendido(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters']);
        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }
        
        $cnpj_intercompany = $this->cnpjINtercompany();
        $meses_anterios = $fields['qtd_meses'] - 1;
        $date = Carbon::now();
        $date = $date->subMonth($meses_anterios);
        $mes =  $date->format('m');
        $ano = $date->format('Y');
        
        $produtos = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['movimentacao' => function($query) use ($ano,$mes,$cnpj_intercompany){
            $query->select(DB::raw('produto_codigo,estabelecimento, CAST (extract(year from data_movimentacao) as integer) AS ano_data_entrada, CAST (extract(month from data_movimentacao) as integer) AS mes_data_entrada, sum(quantidade) as quantidade'))
                ->where(function($query){
                    $query->whereIn('cfop',$this->cfop_venda);
                })
                ->where('sinal', 'ilike', 'saida')
                ->whereNotIn("cliente_codigo", $cnpj_intercompany)
                ->where('data_movimentacao','>=', $ano.'-'.$mes.'-01')
                ->groupBy("produto_codigo","estabelecimento",DB::raw('extract(year from data_movimentacao)'),DB::raw('extract(month from data_movimentacao)'));
        }])
        ->selectRaw('codigo_produto, produto_especificacaos.descricao as descricao,produto_grupos.descricao as grupo,marca, linha');
        if(isset($fields['grupo']) && !is_null($fields['grupo'])){
           
            $produtos->where('produto_grupos.descricao', strtoupper($fields['grupo'])); 
        }
        if(isset($fields['marca']) && !is_null($fields['marca'])){
            $produtos->where('marca', $fields['marca']);
        }
        if(isset($fields['linha']) && !is_null($fields['linha'])){
            $produtos->where('linha', $fields['linha']);
        }
        if(isset($fields['codigo']) && !is_null($fields['codigo'])){
            $produtos->where('codigo_produto', $fields['codigo']);
        }
        if(isset($fields['nome']) && !is_null($fields['nome'])){
            $produtos->where('produto_especificacaos.descricao', $fields['nome']);
        }
        if(isset($fields['inativos']) && !is_null($fields['inativos'])){
            $produtos->where('ativo', $fields['inativos']);
        }
        $produtos = $produtos->get();
        
        $retorno = [];
        $saida = [];
        $estabelecimentos = returnEmpresasNasajonView();
        $totalVenda = 0;
        $total = [
            "comprado" => 0,
        ];
        foreach($produtos as $produto){
            foreach($produto->movimentacao as $venda){
                $totalVenda += $venda->quantidade;
                $retorno[] = [
                    'estabelecimento' => $estabelecimentos[(integer)$venda->estabelecimento],
                    'ano' => $venda->ano_data_entrada,
                    'mes' => $venda->mes_data_entrada,
                    'vendas' => $venda->quantidade,
                ];
            }
        }
        foreach($retorno as $outs){
            if(isset($saida[$outs['estabelecimento']][$outs['ano']][$outs['mes']])){
                $saida[$outs['estabelecimento']][$outs['ano']][$outs['mes']] += $outs['vendas'];
            }
            else{
                $saida[$outs['estabelecimento']][$outs['ano']][$outs['mes']] = $outs['vendas'];
            }
        }
        unset($produtos);
        $total = [
            "comprado" => ($total['comprado'] > 0) ? parserQtd($total['comprado']) : '',
        ];

        return view('programs.produto.modal.busca_vendas')->with(["dados" => $saida, 'vendas' => $fields['qtd_meses'], 'totalVendas' => $totalVenda]);
    }

    public function modalReceber(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters','mes']);
        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $mesAtual = Carbon::now();
        $mesFuturo = Carbon::now()->addMonthsNoOverflow(2);

        $date = Carbon::now();
        $date = $date->subMonth($fields['qtd_meses']);
        
        $produtos = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['compras' => function($query) use ($mesAtual,$mesFuturo){
            $query->whereIn('situacao', ['Aberto', 'Aguardando Documento','Parcialmente Liquidado'])
            ->where('previsao_entrega', '<', $mesFuturo->format('Y-m-t'));
        }])
        ->selectRaw('codigo_produto, produto_especificacaos.descricao as descricao ,produto_grupos.descricao as grupo,marca, linha');
        if(isset($fields['grupo']) && !is_null($fields['grupo'])){
          
            $produtos->where('produto_grupos.descricao', strtoupper($fields['grupo'])); 
        }
        if(isset($fields['marca']) && !is_null($fields['marca'])){
            $produtos->where('marca', $fields['marca']);
        }
        if(isset($fields['linha']) && !is_null($fields['linha'])){
            $produtos->where('linha', $fields['linha']);
        }
        if(isset($fields['codigo']) && !is_null($fields['codigo'])){
            $produtos->where('codigo_produto', $fields['codigo']);
        }
        if(isset($fields['nome']) && !is_null($fields['nome'])){
            $produtos->where('produto_especificacaos.descricaodescricao', $fields['nome']);
        }
        if(isset($fields['inativos']) && !is_null($fields['inativos'])){
            $produtos->where('ativo', $fields['inativos']);
        }
        $produtos = $produtos->get();
        $transito = DB::connection('nasajon')->table('integracoes.vw_saldos_em_transitos')->whereBetween('data', [$mesAtual->format('Y-m-1'), $mesAtual->format('Y-m-t')])->whereIn('produto', $produtos->pluck('codigo_produto'))->get();
        
        $retorno = [];
        $total = 0;
        $estabelecimentos = returnEmpresasNasajonView();
        if($filter['mes'] === 'atual'){
            foreach($produtos as $produto){
                foreach($transito as $trans){
                    if($produto->codigo_produto == $trans->produto){
                        $retorno[] = [
                            'estabelecimento' => $estabelecimentos[(integer)$trans->estabelecimento_codigo],
                            'PCMN' => '',
                            'proforma' => '',
                            'compra' => '',
                            'previsao' => 'EM TRANSITO',
                            'codigo' => $trans->produto,
                            'nome' => $produto->descricao,
                            'compras' => parserQtd($trans->quantidade),
                        ];
                        $total += $trans->quantidade;
                    }
                }
            }
        }
        unset($transito);
        $mes = Carbon::now();
        $mes->day = (int) $mes->daysInMonth;
        $mesum = Carbon::now()->addMonth();
        $mesdois = Carbon::now()->addMonths(2);
        foreach ($produtos as $produtosaida) {  
            foreach($produtosaida->compras as $produto){
                $quantidade = ($produto->situacao === 'Parcialmente Liquidado') ? $produto->quantidade_restante: $produto->quantidade;
                if($quantidade == 0){
                    continue;
                }
                $carbonDate = Carbon::parse($produto->previsao_entrega);
                $getMes = $carbonDate->month;
                if($filter['mes'] === 'atual'){
                    if($carbonDate->lte($mes)){
                        $retorno[] = [
                            'estabelecimento' => $estabelecimentos[(integer)$produto->estabelecimento],
                            'PCMN' => $produto->numero_pedido,
                            'proforma' => $produto->proforma,
                            'compra' => parserData($produto->data_compra),
                            "previsao" => parserData($produto->previsao_entrega),
                            "codigo" => $produto->cod_produto,
                            "nome" => $produto->descricao_produto,
                            "compras" => ($produto->situacao === 'Parcialmente Liquidado') ? parserValor($produto->quantidade_restante) : parserValor($produto->quantidade),
                        ];

                        $total += ($produto->situacao === 'Parcialmente Liquidado') ? $produto->quantidade_restante: $produto->quantidade;
                    }
                }

                if($filter['mes'] === 'um'){
                    if($getMes === $mesum->month){
                        $retorno[] = [
                            'estabelecimento' => $estabelecimentos[(integer)$produto->estabelecimento],
                            'PCMN' => $produto->numero_pedido,
                            'proforma' => $produto->proforma,
                            'compra' => parserData($produto->data_compra),
                            "previsao" => parserData($produto->previsao_entrega),
                            "codigo" => $produto->cod_produto,
                            "nome" => $produto->descricao_produto,
                            "compras" => ($produto->situacao === 'Parcialmente Liquidado') ? parserValor($produto->quantidade_restante) : parserValor($produto->quantidade),
                        ];

                        $total += ($produto->situacao === 'Parcialmente Liquidado') ? $produto->quantidade_restante: $produto->quantidade;
                    }
                }

                if($filter['mes'] === 'dois'){
                    if($getMes === $mesdois->month){
                        $retorno[] = [
                            'estabelecimento' => $estabelecimentos[(integer)$produto->estabelecimento],
                            'PCMN' => $produto->numero_pedido,
                            'proforma' => $produto->proforma,
                            'compra' => parserData($produto->data_compra),
                            "previsao" => parserData($produto->previsao_entrega),
                            "codigo" => $produto->cod_produto,
                            "nome" => $produto->descricao_produto,
                            "compras" => ($produto->situacao === 'Parcialmente Liquidado') ? parserValor($produto->quantidade_restante) : parserValor($produto->quantidade),      
                        ];
    
                        $total += ($produto->situacao === 'Parcialmente Liquidado') ? $produto->quantidade_restante: $produto->quantidade;
                    }
                }
            }
        }
        unset($produtos);
        $total = parserValor($total);
    	return view("programs.analise_compras.modal.receber")->with(["total" => $total, "dados" => $retorno]);

    }

    public function modalAnaliseMes(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters','artigo']);
        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $cnpj_intercompany = $this->cnpjINtercompany();
        $mesAtual = Carbon::now();

        $meses_anterios = $fields['qtd_meses'] - 1;
        $date = Carbon::now();
        $date = $date->subMonth($meses_anterios);
        $mes =  $date->format('m');
        $ano = $date->format('Y');
        
        $produtos = ProdutoEspecificacao::select('produto_grupos.descricao as grupo','produto_especificacaos.descricao as descricao', 'codigo_produto', 
        'marca', 
        'linha', 
        'subgrupo', 
         'unidade', 
        'procedencia', 
        'peso', 
        'ativo', 
        'data_de_cadastro',
		'industrializado',
        'produto_especificacaos.segmentos_id',
        'produto_grupos_id')->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['entradas' => function($query) use ($ano,$mes,$mesAtual){
            $query->selectRaw("codigo_produto, DATE_PART('YEAR', data_entrada) AS ano, DATE_PART('MONTH', data_entrada) AS mes, sum(quantidade) as quantidade")
            ->whereBetween('data_entrada', [$ano.'-'.$mes.'-01', $mesAtual->format('Y-m-d')])
            ->groupBy('codigo_produto','ano','mes');
        },'movimentacao' => function($query) use ($ano,$mes,$cnpj_intercompany){
            $query->select(DB::raw("cfop,produto_codigo, DATE_PART('YEAR', data_movimentacao) AS ano, DATE_PART('MONTH', data_movimentacao) AS mes, sum(quantidade) as quantidade"))
                ->where(function($query){
                    $query->whereIn('cfop',$this->cfop_remessas)
                    ->orWhereIn('cfop',$this->cfop_venda);
                })
                ->where('sinal', 'ilike', 'saida')
                ->whereNotIn("cliente_codigo", $cnpj_intercompany)
                ->where('data_movimentacao','>=', $ano.'-'.$mes.'-01')
                ->groupBy("produto_codigo","data_movimentacao","cfop");
        }])
        ->selectRaw('codigo_produto, produto_especificacaos.descricao as descricao,produto_grupos.descricao as grupo,marca, linha');

        if(isset($fields['grupo']) && !is_null($fields['grupo'])){
         
            $produtos->where('produto_grupos.descricao', strtoupper($fields['grupo'])); 
        }
        if(isset($fields['marca']) && !is_null($fields['marca'])){
            $produtos->where('marca', $fields['marca']);
        }
        if(isset($fields['linha']) && !is_null($fields['linha'])){
            $produtos->where('linha', $fields['linha']);
        }
        if(isset($fields['codigo']) && !is_null($fields['codigo'])){
            $produtos->where('codigo_produto', $fields['codigo']);
        }
        if(isset($fields['nome']) && !is_null($fields['nome'])){
            $produtos->where('produto_especificacaos.descricao', $fields['nome']);
        }
        if(isset($fields['inativos']) && !is_null($fields['inativos'])){
            $produtos->where('ativo', $fields['inativos']);
        }

        $produtos = $produtos->get();

        $retorno = [];
        $dados = [];
        $total = [
            "comprado" => 0,
            "vendido" => 0,
            "remessas" => 0
        ];
        foreach($produtos as $produtosaida){
            foreach ($produtosaida->entradas as $produto) {
                $total['comprado'] += floatval($produto->quantidade);
                $retorno[] = [
                    'data' => str_pad($produto->mes,2,'0',STR_PAD_LEFT).$produto->ano,
                    'compra' => $produto->quantidade,
                    'venda' => 0,
                    'remessas' => 0,
                    'filter' => encrypt([
                        'marca' => $fields['marca'],
                        'linha' => $fields['linha'],
                        'grupo' => $fields['grupo'],
                        'codigo' => $fields['codigo'],
                        'nome' =>  $fields['nome'],
                        'qtd_meses' => $fields['qtd_meses'],
                    ]),
                ];
            }
            foreach ($produtosaida->movimentacao as $venda) {
                if(in_array($venda->cfop,$this->cfop_venda)){
                    $total['vendido'] += floatval($venda->quantidade);
                    $retorno[] = [
                        'data' => str_pad($venda->mes,2,'0',STR_PAD_LEFT).$venda->ano,
                        'compra' => 0,
                        'venda' => $venda->quantidade,
                        'remessas' => 0,
                        'qtd_meses' => $fields['qtd_meses'],
                        'filter' => encrypt([
                            'marca' => $fields['marca'],
                            'linha' => $fields['linha'],
                            'grupo' => $fields['grupo'],
                            'codigo' => $fields['codigo'],
                            'nome' =>  $fields['nome'],
                            'qtd_meses' => $fields['qtd_meses'],
                        ]),
                    ];
                }

            }
            foreach ($produtosaida->movimentacao as $movimentacao) {
                if(in_array($movimentacao->cfop,$this->cfop_remessas)){
                    $total['remessas'] += floatval($movimentacao->quantidade);
                    $retorno[] = [
                        'data' => str_pad($movimentacao->mes,2,'0',STR_PAD_LEFT).$movimentacao->ano,
                        'compra' => 0,
                        'venda' => 0,
                        'remessas' => $movimentacao->quantidade,
                        'qtd_meses' => $fields['qtd_meses'],
                        'filter' => encrypt([
                            'marca' => $fields['marca'],
                            'linha' => $fields['linha'],
                            'grupo' => $fields['grupo'],
                            'codigo' => $fields['codigo'],
                            'nome' =>  $fields['nome'],
                            'qtd_meses' => $fields['qtd_meses'],
                        ]),
                    ];
                }
            }
        }
        unset($produtos);
        $head = [];
        $meses = ($fields['qtd_meses'] <= 12) ? $fields['qtd_meses'] -1 : $fields['qtd_meses'];
        for($i = 0; $i <= $meses ; $i ++){
            $headData = Carbon::now()->subMonth($i);
            $data = $headData->format('mY');
            $show = $headData->format('m/Y');
            $head[$data] = $show;
        }
        foreach($retorno as $key => $row){
            if(!isset($dados[$row['data']])){
                $dados[$row['data']] = [
                    'data' => $row['data'],
                    'filter' => $row['filter'],
                    'compra' => 0,
                    'venda' => 0,
                    'remessas' => 0,
                ];
            }
            $dados[$row['data']]['compra'] += $row['compra'];
            $dados[$row['data']]['venda'] += $row['venda'];
            $dados[$row['data']]['remessas'] += $row['remessas'];
        }
        foreach($dados as $key => $dado){
            $dados[$key]['compra'] = ($dado['compra'] > 0 ) ? parserQtd($dado['compra']) : '';
            $dados[$key]['venda'] = ($dado['venda'] > 0 ) ? parserQtd($dado['venda']) : '';
            $dados[$key]['remessas'] = ($dado['remessas'] > 0 ) ? parserQtd($dado['remessas']) : '';
        }

        $total = [
            "comprado" => ($total['comprado'] > 0) ? parserQtd($total['comprado']) : '',
            "vendido" => ($total['vendido'] > 0) ? parserQtd($total['vendido']) : '',
            "remessas" => ($total['remessas'] > 0) ? parserQtd($total['remessas']) : '',
        ];

        $artigo = (isset($filter['artigo'])) ? $filter['artigo'] : null;

    	return view("programs.analise_compras.modal.mes_a_mes")->with(["total" => $total,"artigo" => $artigo, "dados" => $dados, 'th' => $head,'fields' => $fields, 'mes' => $fields['qtd_meses']]);
    }

    public function modalAnaliseMesAberturaCompras(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters']);
        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $meses_anterios = $fields['qtd_meses'] - 1;
        $date = Carbon::now();
        $date = $date->subMonth($meses_anterios);
        $mes =  $date->format('m');
        $ano = $date->format('Y');
        
        $produtos = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['entradas' => function($query) use ($ano,$mes){
            $query->selectRaw("codigo_produto, DATE_PART('YEAR', data_entrada) AS ano, DATE_PART('MONTH', data_entrada) AS mes, sum(quantidade) as quantidade")
            ->whereBetween('data_entrada', [$ano.'-'.$mes.'-01', Carbon::now()->format('Y-m-d')])
            ->groupBy("codigo_produto" , "ano","mes");
        }])
        ->selectRaw('codigo_produto, produto_especificacaos.descricao as descricao,produto_grupos.descricao as grupo,marca, linha');
        if(isset($fields['grupo']) && !is_null($fields['grupo'])){
            
            $produtos->where('produto_grupos.descricao', strtoupper($fields['grupo'])); 
        }
        if(isset($fields['marca']) && !is_null($fields['marca'])){
            $produtos->where('marca', $fields['marca']);
        }
        if(isset($fields['linha']) && !is_null($fields['linha'])){
            $produtos->where('linha', $fields['linha']);
        }
        if(isset($fields['codigo']) && !is_null($fields['codigo'])){
            $produtos->where('codigo_produto', $fields['codigo']);
        }
        if(isset($fields['nome']) && !is_null($fields['nome'])){
            $produtos->where('produto_especificacaos.descricao', $fields['nome']);
        }
        if(isset($fields['inativos']) && !is_null($fields['inativos'])){
            $produtos->where('ativo', $fields['inativos']);
        }
        $produtos = $produtos->get();
        
        $retorno = [];
        $total = 0;
        $footer = [];
        foreach($produtos as $produto){
            foreach($produto->entradas as $compra){
                if(!isset($retorno[$produto->codigo_produto])){
                    $retorno[$produto->codigo_produto] = [
                                    'codigo' => $produto->codigo_produto.' - '.$produto->descricao,
                                    'data' => [],
                                    'total_produto' => 0,
                                ];
                }

                $key = str_pad($compra->mes,2,'0',STR_PAD_LEFT).$compra->ano;
                $footer[] = ['data' => $key , 'valor' => $compra->quantidade];
                $retorno[$produto->codigo_produto]['data'][$key] = $compra->quantidade;
                $retorno[$produto->codigo_produto]['total_produto'] += $compra->quantidade;
                $total += $compra->quantidade;
            }
        }

        unset($produtos);
        $head = [];
        $meses = ($fields['qtd_meses'] <= 12) ? $fields['qtd_meses'] -1 : $fields['qtd_meses'];
        $meses = ($fields['qtd_meses'] <= 12) ? $fields['qtd_meses'] -1 : $fields['qtd_meses'];
        for($i = 0; $i <= $meses ; $i ++){
            $headData = Carbon::now()->subMonth($i);
            $data = $headData->format('mY');
            $show = $headData->format('m/Y');
            $head[$data] = $show;
        }
        $resultfooter = [];
        foreach($footer as $row){
            if(!isset($resultfooter[$row['data']])){
                $resultfooter[$row['data']] = [
                    'data' => $row['data'],
                    'valor' => 0
                    ];
            }
            $resultfooter[$row['data']]['valor'] += $row['valor'] ;
        }  
        $total = ($total > 0) ? parserQtd($total) : '';

    	return view("programs.analise_compras.modal.mes_a_mes_compras")->with(["total" => $total, "footer" => $resultfooter, "dados" => $retorno, 'th' => $head, 'mes' => $fields['qtd_meses']]);
    }

    public function modalAnaliseMesAberturaRemessas(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters']);
        $cnpj_intercompany = $this->cnpjINtercompany();
        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $meses_anterios = $fields['qtd_meses'] - 1;
        $date = Carbon::now();
        $date = $date->subMonth($meses_anterios);
        $mes =  $date->format('m');
        $ano = $date->format('Y');
        
        $produtos = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['movimentacao' => function($query) use ($ano,$mes,$cnpj_intercompany){
            $query->select(DB::raw("produto_codigo, DATE_PART('YEAR', data_movimentacao) AS ano, DATE_PART('MONTH', data_movimentacao) AS mes, sum(quantidade) as quantidade"))
                ->whereIn('cfop',$this->cfop_remessas)
                ->where('sinal', 'ilike', 'saida')
                ->whereNotIn("cliente_codigo", $cnpj_intercompany)
                ->where('data_movimentacao','>=', $ano.'-'.$mes.'-01')
                ->groupBy("produto_codigo","data_movimentacao");
        }])
        ->selectRaw('codigo_produto, produto_especificacaos.descricao as descricao,produto_grupos.descricao as grupo,marca, linha');

        if(isset($fields['grupo']) && !is_null($fields['grupo'])){
           
            $produtos->where('produto_grupos.descricao', strtoupper($fields['grupo'])); 
        }
        if(isset($fields['marca']) && !is_null($fields['marca'])){
            $produtos->where('marca', $fields['marca']);
        }
        if(isset($fields['linha']) && !is_null($fields['linha'])){
            $produtos->where('linha', $fields['linha']);
        }
        if(isset($fields['codigo']) && !is_null($fields['codigo'])){
            $produtos->where('codigo_produto', $fields['codigo']);
        }
        if(isset($fields['nome']) && !is_null($fields['nome'])){
            $produtos->where('produto_especificacaos.descricao', $fields['nome']);
        }
        if(isset($fields['inativos']) && !is_null($fields['inativos'])){
            $produtos->where('ativo', $fields['inativos']);
        }

        $produtos = $produtos->get();
        
        $retorno = [];
        $total = [
            "total" => 0,
        ];
        foreach($produtos as $produto){
            foreach($produto->movimentacao as $remessas){
                if(!isset($retorno[$produto->codigo_produto])){
                    $retorno[$produto->codigo_produto] = [
                                    'codigo' => $produto->codigo_produto.' - '.$produto->descricao,
                                    'data' => [],
                                    'total_produto' => 0,
                                ];
                }
                $key = str_pad($remessas->mes,2,'0',STR_PAD_LEFT).$remessas->ano;
                $retorno[$produto->codigo_produto]['data'][$key] = $remessas->quantidade;
                $retorno[$produto->codigo_produto]['total_produto'] += $remessas->quantidade;
                $total['total'] += $remessas->quantidade;
            }
        }

        unset($produtos);
        $head = [];
        $meses = ($fields['qtd_meses'] <= 12) ? $fields['qtd_meses'] -1 : $fields['qtd_meses'];
        for($i = 0; $i <= $meses ; $i ++){
            $headData = Carbon::now()->subMonth($i);
            $data = $headData->format('mY');
            $show = $headData->format('m/Y');
            $head[$data] = $show;
        }
        $footer = [];
        foreach($head as $data => $row){
            foreach($retorno as $key => $value){
                foreach($value['data'] as $chave => $valor){
                    if($data == $chave){
                        $footer[] = [
                            'data' => $data,
                            'valor' => $valor,
                        ];
                    }else{
                        $footer[] = [
                            'data' => $data,
                            'valor' => 0,
                        ];
                    }
                }
            }
        }
        $saida = [];
        foreach($footer as $fo){
            if(!isset($saida[$fo['data']])){
                $saida[$fo['data']] = [
                    'data' => $fo['data'],
                    'valor' => 0,
                ];
            }
            $saida[$fo['data']]['valor'] += $fo['valor'];
        }
        $total = [
            "total" => ($total['total'] > 0) ? parserQtd($total['total']) : '',
        ];
    	return view("programs.analise_compras.modal.mes_a_mes_remessas")->with(["total" => $total,"footer" => $saida, "dados" => $retorno, 'th' => $head, 'mes' => $fields['qtd_meses']]);
    }

    public function modalAnaliseMesAberturaVendas(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters']);
        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }
        
        $meses_anterios = $fields['qtd_meses'] - 1;
        $cnpj_intercompany = $this->cnpjINtercompany();
        $date = Carbon::now();
        $date = $date->subMonth($meses_anterios);
        $mes =  $date->format('m');
        $ano = $date->format('Y');
        
        $produtos = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['movimentacao' => function($query) use ($ano,$mes,$cnpj_intercompany){
            $query->select(DB::raw("produto_codigo, DATE_PART('YEAR', data_movimentacao) AS ano, DATE_PART('MONTH', data_movimentacao) AS mes, sum(quantidade) as quantidade"))
                ->where(function($query){
                    $query->whereIn('cfop',$this->cfop_venda);
                })
                ->where('sinal', 'ilike', 'saida')
                ->whereNotIn("cliente_codigo", $cnpj_intercompany)
                ->where('data_movimentacao','>=', $ano.'-'.$mes.'-01')
                ->groupBy("produto_codigo",DB::raw('DATE_PART(\'YEAR\', data_movimentacao)'),DB::raw('DATE_PART(\'MONTH\', data_movimentacao)'));
        }])
            ->selectRaw('codigo_produto, produto_especificacaos.descricao as descricao,produto_grupos.descricao as grupo,marca, linha');
        if(isset($fields['grupo']) && !is_null($fields['grupo'])){
          
            $produtos->where('produto_grupos.descricao', strtoupper($fields['grupo'])); 
        }
        if(isset($fields['marca']) && !is_null($fields['marca'])){
            $produtos->where('marca', $fields['marca']);
        }
        if(isset($fields['linha']) && !is_null($fields['linha'])){
            $produtos->where('linha', $fields['linha']);
        }
        if(isset($fields['codigo']) && !is_null($fields['codigo'])){
            $produtos->where('codigo_produto', $fields['codigo']);
        }
        if(isset($fields['nome']) && !is_null($fields['nome'])){
            $produtos->where('produto_especificacaos.descricao', $fields['nome']);
        }
        if(isset($fields['inativos']) && !is_null($fields['inativos'])){
            $produtos->where('ativo', $fields['inativos']);
        }
        $produtos = $produtos->get();
        
        $retorno = [];
        $total = [
            "total" => 0,
        ];
        foreach($produtos as $produto){
            foreach($produto->movimentacao as $venda){
                if(!isset($retorno[$produto->codigo_produto])){
                    $retorno[$produto->codigo_produto] = [
                                    'codigo' => $produto->codigo_produto.' - '.$produto->descricao,
                                    'data' => [],
                                    'total_produto' => 0,
                                ];
                }
                $key = str_pad($venda->mes,2,'0',STR_PAD_LEFT).$venda->ano;
                $retorno[$produto->codigo_produto]['data'][$key] = $venda->quantidade;
                $retorno[$produto->codigo_produto]['total_produto'] += $venda->quantidade;
                $total['total'] += $venda->quantidade;
            }
        }

        unset($produtos);
        $head = [];
        $meses = ($fields['qtd_meses'] <= 12) ? $fields['qtd_meses'] -1 : $fields['qtd_meses'];
        for($i = 0; $i <= $meses ; $i ++){
            $headData = Carbon::now()->subMonth($i);
            $data = $headData->format('mY');
            $show = $headData->format('m/Y');
            $head[$data] = $show;
        }
        $footer = [];
        foreach($head as $data => $row){
            foreach($retorno as $key => $value){
                foreach($value['data'] as $chave => $valor){
                    if($data == $chave){
                        $footer[] = [
                            'data' => $data,
                            'valor' => $valor,
                        ];
                    }else{
                        $footer[] = [
                            'data' => $data,
                            'valor' => 0,
                        ];
                    }
                }
            }
        }
        $saida = [];
        foreach($footer as $fo){
            if(!isset($saida[$fo['data']])){
                $saida[$fo['data']] = [
                    'data' => $fo['data'],
                    'valor' => 0,
                ];
            }
            $saida[$fo['data']]['valor'] += $fo['valor'];
        }
        $total = [
            "total" => ($total['total'] > 0) ? parserQtd($total['total']) : '',
        ];
    	return view("programs.analise_compras.modal.mes_a_mes_vendas")->with(["total" => $total,"footer" => $saida, "dados" => $retorno, 'th' => $head, 'mes' => $fields['qtd_meses']]);
    }

    public function modalAnaliseMesAberturaProdutos(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters']);
        $cnpj_intercompany = $this->cnpjINtercompany();
        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $meses_anterios = $fields['qtd_meses'] - 1;
        $date = Carbon::now();
        $date = $date->subMonth($meses_anterios);
        $mes =  $date->format('m');
        $ano = $date->format('Y');
        
        $produtos = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['entradas' => function($query) use ($ano,$mes){
                $query->selectRaw("codigo_produto, DATE_PART('YEAR', data_entrada) AS ano, DATE_PART('MONTH', data_entrada) AS mes, sum(quantidade) as quantidade")
                ->whereBetween('data_entrada', [$ano.'-'.$mes.'-01', Carbon::now()->format('Y-m-d')])
                ->groupBy("codigo_produto" , "ano","mes");
            },'movimentacao' => function($query) use ($ano,$mes,$cnpj_intercompany){
                $query->select(DB::raw("produto_codigo, cfop, DATE_PART('YEAR', data_movimentacao) AS ano, DATE_PART('MONTH', data_movimentacao) AS mes, sum(quantidade) as quantidade"))
                    ->where(function($query){
                        $query->whereIn('cfop',$this->cfop_remessas)
                        ->orWhereIn('cfop',$this->cfop_venda);
                    })
                    ->where('sinal', 'ilike', 'saida')
                    ->whereNotIn("cliente_codigo", $cnpj_intercompany)
                    ->where('data_movimentacao','>=', $ano.'-'.$mes.'-01')
                    ->groupBy("produto_codigo","cfop",DB::raw('DATE_PART(\'YEAR\', data_movimentacao)'),DB::raw('DATE_PART(\'MONTH\', data_movimentacao)'));
            }])
            ->selectRaw('codigo_produto,produto_especificacaos.descricao as descricao,produto_grupos.descricao as grupo,marca, linha');

        if(isset($fields['grupo']) && !is_null($fields['grupo'])){
           
            $produtos->where('produto_grupos.descricao', strtoupper($fields['grupo'])); 
        }
        if(isset($fields['marca']) && !is_null($fields['marca'])){
            $produtos->where('marca', $fields['marca']);
        }
        if(isset($fields['linha']) && !is_null($fields['linha'])){
            $produtos->where('linha', $fields['linha']);
        }
        if(isset($fields['codigo']) && !is_null($fields['codigo'])){
            $produtos->where('codigo_produto', $fields['codigo']);
        }
        if(isset($fields['nome']) && !is_null($fields['nome'])){
            $produtos->where('produto_especificacaos.descricao', $fields['nome']);
        }
        if(isset($fields['inativos']) && !is_null($fields['inativos'])){
            $produtos->where('ativo', $fields['inativos']);
        }
        $produtos = $produtos->get();
        
        $retorno = [];
        $total = [
            "total" => 0,
        ];
        foreach($produtos as $produto){
            foreach($produto->movimentacao as $venda){
                if(in_array($venda->cfop,$this->cfop_venda)){
                    if(!isset($retorno[$produto->codigo_produto])){
                        $retorno[$produto->codigo_produto] = [
                            'codigo' => $produto->codigo_produto.' - '.$produto->descricao,
                            'datavenda' => [],
                            'datacompra' => [],
                            'dataremessa' => [],
                            'total_vendas' => 0,
                            'total_remessas' => 0,
                            'total_compras' => 0,
                        ];
                    }
                    $key = str_pad($venda->mes,2,'0',STR_PAD_LEFT).$venda->ano;
                    if(!isset($retorno[$produto->codigo_produto]['datavenda'][$key])){
                        $retorno[$produto->codigo_produto]['datavenda'][$key] = 0;
                    }
                    $retorno[$produto->codigo_produto]['datavenda'][$key] += $venda->quantidade;
                    $retorno[$produto->codigo_produto]['total_vendas'] += $venda->quantidade;
                    $total['total'] += $venda->quantidade;
                }
            }
            foreach($produto->entradas as $compra){
                if(!isset($retorno[$produto->codigo_produto])){
                    $retorno[$produto->codigo_produto] = [
                        'codigo' => $produto->codigo_produto.' - '.$produto->descricao,
                        'datavenda' => [],
                        'datacompra' => [],
                        'dataremessa' => [],
                        'total_vendas' => 0,
                        'total_remessas' => 0,
                        'total_compras' => 0,
                    ];
                }
                $key = str_pad($compra->mes,2,'0',STR_PAD_LEFT).$compra->ano;
                $retorno[$produto->codigo_produto]['datacompra'][$key] = $compra->quantidade;
                $retorno[$produto->codigo_produto]['total_compras'] += $compra->quantidade;
                $total['total'] += $compra->quantidade;
            }
            foreach($produto->movimentacao as $remessas){
                if(in_array($remessas->cfop,$this->cfop_remessas)){
                    if(!isset($retorno[$produto->codigo_produto])){
                        $retorno[$produto->codigo_produto] = [
                            'codigo' => $produto->codigo_produto.' - '.$produto->descricao,
                            'datavenda' => [],
                            'datacompra' => [],
                            'dataremessa' => [],
                            'total_vendas' => 0,
                            'total_remessas' => 0,
                            'total_compras' => 0,
                        ];
                    }
                    
                    $key = str_pad($remessas->mes,2,'0',STR_PAD_LEFT).$remessas->ano;
                    if(!isset($retorno[$produto->codigo_produto]['dataremessa'][$key])){
                        $retorno[$produto->codigo_produto]['dataremessa'][$key] = 0;
                    }
                    $retorno[$produto->codigo_produto]['dataremessa'][$key] += $remessas->quantidade;
                    $retorno[$produto->codigo_produto]['total_remessas'] += $remessas->quantidade;
                    $total['total'] += $remessas->quantidade;
                }
            }
        }

        $head = [];
        $meses = ($fields['qtd_meses'] <= 12) ? $fields['qtd_meses'] -1 : $fields['qtd_meses'];

        for($i = 0; $i <= $meses ; $i ++){
            $headData = Carbon::now()->subMonth($i);
            $data = $headData->format('mY');
            $show = $headData->format('m/Y');
            $head[$data] = $show;
        }

        $total = [
            "total" => ($total['total'] > 0) ? parserQtd($total['total']) : '',
        ];

    	return view("programs.analise_compras.modal.mes_a_mes_produto")->with(['fields' => $fields,"total" => $total, "dados" => $retorno, 'th' => $head, 'mes' => $fields['qtd_meses']]);
    }

    public function modalAnaliseComprasRemessas(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $filter = $request->only(['filters']);
        $cnpj_intercompany = $this->cnpjINtercompany();

        try{
            $fields = decrypt($filter['filters']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }

        $meses_anterios = $fields['qtd_meses'] - 1;
        $date = Carbon::now();
        $date = $date->subMonth($meses_anterios)->format('Y-m-01');

        $produtos = ProdutoEspecificacao::join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id')->with(['movimentacao' => function($query) use ($date, $cnpj_intercompany){
            $query ->whereIn('cfop',$this->cfop_remessas)
                ->whereNotIn("cliente_codigo", $cnpj_intercompany)
                ->where('sinal', 'ilike', 'saida')
                ->where('data_movimentacao','>=', $date);
        }])
            ->selectRaw('codigo_produto, produto_especificacaos.descricao as descricao,produto_grupos.descricao as grupo,marca, linha');
        if(isset($fields['grupo']) && !is_null($fields['grupo'])){
        
            $produtos->where('produto_grupos.descricao', strtoupper($fields['grupo'])); 
        }
        if(isset($fields['marca']) && !is_null($fields['marca'])){
            $produtos->where('marca', $fields['marca']);
        }
        if(isset($fields['linha']) && !is_null($fields['linha'])){
            $produtos->where('linha', $fields['linha']);
        }
        if(isset($fields['codigo']) && !is_null($fields['codigo'])){
            $produtos->where('codigo_produto', $fields['codigo']);
        }
        if(isset($fields['nome']) && !is_null($fields['nome'])){
            $produtos->where('produto_especificacaos.descricao', $fields['nome']);
        }
        if(isset($fields['inativos']) && !is_null($fields['inativos'])){
            $produtos->where('ativo', $fields['inativos']);
        }
        $produtos = $produtos->get();

        $retorno = [];
        $saida = [];
        $estabelecimentos = returnEmpresasNasajonView();
        $totalRemessas = 0;

        foreach($produtos as $produto){
            foreach($produto->movimentacao as $remessa){
                $totalRemessas += $remessa->quantidade;
                $retorno[] = [
                    'estabelecimento' => $estabelecimentos[(integer)$remessa->estabelecimento],
                    'ano' => Carbon::parse($remessa->data_movimentacao)->format('Y'),
                    'mes' => Carbon::parse($remessa->data_movimentacao)->format('m'),
                    'remessas' => $remessa->quantidade,
                ];
            }
        }
        foreach($retorno as $outs){
            if(isset($saida[$outs['estabelecimento']][$outs['ano']][$outs['mes']])){
                $saida[$outs['estabelecimento']][$outs['ano']][$outs['mes']] += $outs['remessas'];
            }
            else{
                $saida[$outs['estabelecimento']][$outs['ano']][$outs['mes']] = $outs['remessas'];
            }
        }
        unset($produtos);

        return view('programs.analise_compras.modal.remessas')->with(["dados" => $saida, 'remessas' => $fields['qtd_meses'], 'totalRemessas' => $totalRemessas]);
    }

}
