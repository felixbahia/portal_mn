<?php

namespace App\Http\Controllers;

use App\ComprasNasajon;
use App\GiroDeEstoque;
use App\Http\Requests\GiroDeEstoqueConsultaRequest;
use App\Movimentacao;
use App\NasajonEstabelecimento;
use App\PedidoPortal;
use App\ProdutoEspecificacao;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Auth;
use Illuminate\Http\Request;
use App\Exports\GiroDeEstoqueExport;

class GiroDeEstoqueController extends Controller
{
    private $cfop_remessas = ["5901","5910","5911","5912","5924","6901","6910","6911","6912","6924"];
    private $cfop_venda = ['5922', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101', '5104', '6104'];

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\GiroDeEstoque") === false){
            return abort(403);
        }
        $dias_media = [
			'30' => '30 Dias',
			'60' => '60 Dias',
			'90' => '90 Dias',
			'120' => '120 Dias',
			'150' => '150 Dias',
			'180' => '180 Dias',
			'210' => '210 Dias',
			'240' => '240 Dias',
			'270' => '270 Dias',
			'300' => '300 Dias',
			'330' => '330 Dias',
			'360' => '360 Dias'
        ];

        $produto_grupo = [
			'grupo' => 'Por Grupo',
			'produto' => 'Por Produto'
        ];
        $origem = [
			'NACIONAL' => 'Nacional',
			'IMPORTADO' => 'Importados'
        ];

        $request->session()->flash('model', 'App\GiroDeEstoque');

        $meses = [
			"atual" => parserNameMonth(date("m")),
			"mes_1" => parserNameMonth(Carbon::now()->addMonth()->format('m')),
			"mes_2" => parserNameMonth(Carbon::now()->addMonths(2)->format('m'))
        ];
        
        return view('programs.giro_de_estoque.index')->with([
			'dias_media' => $dias_media,
			'meses' => $meses,
			'produto_grupo' => $produto_grupo,
			'origem' => $origem
		]);
    }

    private function diasMedia($dias_media){
        switch ($dias_media) {
            case 30:
                $mes = '01';
                break;
            case 60:
                $mes = '02';
                break;
            case 90:
                $mes = '03';
                break;
            case 120:
                $mes = '04';
                break; 
            case 150:
                $mes = '05';
                break;
            case 180:
                $mes = '06';
                break;
            case 210:
                $mes = '07';
                break;
            case 240:
                $mes = '08';
                break;
            case 270:
                $mes = '09';
                break;
            case 300:
                $mes = '10';
                break;
            case 330:
                $mes = '11';
                break;
            case 360:
                $mes = '12';
                break;
        }
        return $mes;
    }

    private function cnpjINtercompany(){
        $NasajonEstabelecimento = NasajonEstabelecimento::select('raizcnpj','ordemcnpj', 'codigo')
        ->whereNotNull('raizcnpj')
        ->whereNotNull('ordemcnpj')
        ->get();

        foreach($NasajonEstabelecimento as $estabelecimento){
            $cnpj_estabelecimento[] = $estabelecimento->raizcnpj.$estabelecimento->ordemcnpj;
            $codigo[] = $estabelecimento->codigo;
        }

        $cnpj_intercompany = array_merge($cnpj_estabelecimento, $codigo);

        return $cnpj_intercompany;
    }


    public function filtroTelaGiroDeEstoque(GiroDeEstoqueConsultaRequest $request){
        ini_set('memory_limit','1024M');
        set_time_limit(300);
        $campo = $request->only('grupo', 'marca', 'linha', 'descricao', 'codigo', 'dias_media', 'necessidade_dias', 
        'alto_giro_percentual', 'baixo_giro_percentual', 'alto_giro', 'baixo_giro','giro_normal', 'produto_grupo', 'origem', 'export');
        $mes = $this->diasMedia($campo['dias_media']);

        $meses = (integer)$mes;
        
        $GiroDeEstoqueObj = GiroDeEstoque::select('grupo', 'marca', 'linha',
        'unidade', 'descricao', 'codigo_produto', 'preco_venda', 'custo_gerencial',
        DB::raw('sum(percentual_estoque) as participacao,
        sum(consumo_'.$mes.'_mes) as vendas,
        sum(consumo_01_mes) as ultimos_30_dias,
        sum(compras_mes_atual) as compras_mes_atual,
        sum(compras_proximo_mes) as compras_proximo_mes,
        sum(compras_mes_seguinte) as compras_mes_seguinte,
        sum(compras_proximos_meses) as compras_proximos_meses, 
        sum(estoque) as estoque_total,
        max(ultima_compra) as data_ultima_compra,
        sum(vendas_aberto) as total_vendas_aberto'))
        ->with(['produtoEstoque'])
        ->groupBy('grupo', 'marca', 'linha', 'unidade', 'preco_venda', 'custo_gerencial','descricao', 'codigo_produto');

        if(!empty($campo["descricao"])){
            $GiroDeEstoqueObj->where('descricao', $campo["descricao"]);
        }
        if(!empty($campo["codigo"])){
            $GiroDeEstoqueObj->where('codigo_produto', $campo["codigo"]);
        } 
        if(!empty($campo["origem"])){
            $GiroDeEstoqueObj->where('origem', $campo["origem"]);
        }
        if(!empty($campo["grupo"])){
            $GiroDeEstoqueObj->where('grupo', $campo["grupo"]);
        }
        if(!empty($campo["marca"])){
            $GiroDeEstoqueObj->where('marca', $campo["marca"]);

        }
        if(!empty($campo["linha"])){
            $GiroDeEstoqueObj->where('linha', $campo["linha"]);
        } 
       
         $alto_giro_percentual = !empty($campo["alto_giro_percentual"]) ? $campo["alto_giro_percentual"] : 0;
         $baixo_giro_percentual = !empty($campo["baixo_giro_percentual"]) ? -$campo["baixo_giro_percentual"] : 0;

         if(!empty($campo["alto_giro"])){
            $GiroDeEstoqueObj->orHaving(DB::raw('
            (CASE
                WHEN sum(consumo_'.$mes.'_mes)/'.$meses.' > 0 THEN (sum(consumo_01_mes) - (sum(consumo_'.$mes.'_mes)/'.$meses.'))*(100/(sum(consumo_'.$mes.'_mes)/'.$meses.'))
             ELSE -100
            END)'), '>=', $alto_giro_percentual);
         }
         if(!empty($campo["baixo_giro"])){
            $GiroDeEstoqueObj->orHaving(DB::raw('
            (CASE
                WHEN sum(consumo_'.$mes.'_mes)/'.$meses.' > 0 THEN (sum(consumo_01_mes) - (sum(consumo_'.$mes.'_mes)/'.$meses.'))*(100/(sum(consumo_'.$mes.'_mes)/'.$meses.'))
             ELSE -100 
            END)'), '<=', $baixo_giro_percentual);
         }
         if(!empty($campo["giro_normal"])){
            $GiroDeEstoqueObj->orHaving(DB::raw('
            (CASE
                WHEN sum(consumo_'.$mes.'_mes)/'.$meses.' > 0 THEN (sum(consumo_01_mes) - (sum(consumo_'.$mes.'_mes)/'.$meses.'))*(100/(sum(consumo_'.$mes.'_mes)/'.$meses.'))
             ELSE -100
            END)'), '<', $alto_giro_percentual) 
            ->having(DB::raw('
            (CASE
                WHEN sum(consumo_'.$mes.'_mes)/'.$meses.' > 0 THEN (sum(consumo_01_mes) - (sum(consumo_'.$mes.'_mes)/'.$meses.'))*(100/(sum(consumo_'.$mes.'_mes)/'.$meses.'))
             ELSE -100
            END)'), '>', $baixo_giro_percentual);
         }

         $GiroDeEstoque = $GiroDeEstoqueObj->get();
         $cnpj_intercompany = $this->cnpjINtercompany();
 
         $date = Carbon::now();
         $date = $date->subMonth($meses);
         $periodo =  $date->format('Y-m-d');
 
         $totalVendas = Movimentacao::where('sinal', 'SAIDA')
         ->where('documento', '!=', ' ')
         ->whereNotIn('cliente_codigo', $cnpj_intercompany)
         ->whereNotNull('data_movimentacao')
         ->where('data_movimentacao', '>=', $periodo)
         ->whereIn('cfop', [5922, 5949, 6108, 6110, 6119, 6123, 6106, 5123, 6118, 5118, 5122, 5551, 6551, 5102, 6102, 5106, 5101, 6101])
         ->sum('quantidade');

         $total = [
            'estoque' => 0,
            'estoque_transito' => 0,
            'participacao_estoque' => 0,
            'compras_mes_atual' => 0,
            'compras_proximo_mes' => 0,
            'compras_mes_seguinte' => 0,
            'compras_proximos_meses' => 0,
            'pedidos_aberto' => 0,
            'saldo' => 0,
            'vendas' => 0,
            'media_diaria' => 0,
            'participacao_vendas' => 0,
            'compras_necessidades' => 0,
            'media_venda' => 0,
            'filters' => [
                'estabelecimento' => null,
                'produto_grupo' => $campo['produto_grupo'],
                'dias_media' => $campo['dias_media'],
                'grupo' => $campo['grupo'],
                'marca' => $campo['marca'],
                'linha' => $campo['linha'],
                'codigo' => $campo['codigo'],
                'descricao' => $campo['descricao'],
                'origem' => $campo['origem']
            ]
        ];

         foreach($GiroDeEstoque as $produto){
            if($totalVendas > 0){
                $participacao_vendas = $produto->vendas/$totalVendas;
            }else{
                $participacao_vendas = 0;
            }

            if($campo["produto_grupo"] == 'grupo'){
                $chave = $produto->grupo.$produto->marca.$produto->linha;
            }else{
                $chave = $produto->codigo_produto;
            }
            $transito = 0;
            $totalCompras = $produto->compras_mes_atual + $produto->compras_proximo_mes + $produto->compras_mes_seguinte + $produto->compras_proximos_meses;

           if(!isset($saida[$chave])){
                $saida[$chave] = [
                    'grupo' => $produto->grupo,
                    'marca' => $produto->marca,
                    'linha' => $produto->linha,
                    'descricao' => $produto->descricao,
                    'codigo' => $produto->codigo_produto,
                    'unidade' => $produto->unidade,
                    'ultima_compra' => [],
                    'custo_gerencial' => !is_null($produto->custo_gerencial) ? parserValor($produto->custo_gerencial) : '',
                    'preco_venda' => !is_null($produto->preco_venda) ? parserValor($produto->preco_venda) : '',
                    'estoque' => 0,
                    'estoque_transito' => 0,
                    'estoque_meses' => 0,
                    'participacao_estoque' => 0,
                    'compras_mes_atual' => 0,
                    'compras_proximo_mes' => 0,
                    'compras_mes_seguinte' => 0,
                    'compras_proximos_meses' => 0,
                    'pedidos_aberto' => 0,
                    'saldo' => 0,
                    'vendas' => 0,
                    'media_diaria' => 0,
                    'media_mes' => 0,
                    'participacao_vendas' => 0, 
                    'compras_necessidade' => 0,
                    'percentual_venda' => 0,
                    'giro' => '',
                    'media_venda' => 0,
                    'filters' => [
                        'estabelecimento' => null,
                        'grupo' => $produto->grupo,
                        'marca' => $produto->marca,
                        'linha' => $produto->linha,
                        'descricao' => $produto->descricao,
                        'campo_codigo' => $campo['codigo'],
                        'campo_descricao' => $campo['descricao'],
                        'codigo' => $produto->codigo_produto,
                        'produto_grupo' => $campo["produto_grupo"],
                        'dias_media' => $campo['dias_media'],
                        'origem' => $campo['origem']
                    ]
                ];
            }
            
            if(isset($produto->produtoEstoque[0])){
                foreach($produto->produtoEstoque as $saldo_transito){
                    if($saldo_transito->estabelecimento == '03' || $saldo_transito->estabelecimento == '04'){
                        if($saldo_transito->saldo_fiscal >= 0 && $saldo_transito->saldo_movimento_nao_efetivado >= 0){
                            $transito +=  $saldo_transito->saldo_fiscal + $saldo_transito->saldo_movimento_nao_efetivado;
                        }
                    }
                }
            }

            $saida[$chave]['ultima_compra'][] = $produto->data_ultima_compra;
            $saida[$chave]['filters']['produtos_codigo'][] = $produto->codigo_produto;
            $saida[$chave]['estoque'] += $produto->estoque_total;
            $saida[$chave]['estoque_transito'] += $transito;
            $saida[$chave]['participacao_estoque'] += $produto->participacao; 
            $saida[$chave]['compras_mes_atual'] += $produto->compras_mes_atual;
            $saida[$chave]['compras_proximo_mes'] += $produto->compras_proximo_mes;
            $saida[$chave]['compras_mes_seguinte'] +=  $produto->compras_mes_seguinte;
            $saida[$chave]['compras_proximos_meses'] += $produto->compras_proximos_meses;
            $saida[$chave]['pedidos_aberto'] += $produto->total_vendas_aberto;
            $saida[$chave]['saldo'] += ($produto->estoque_total + $totalCompras + $transito) - $produto->total_vendas_aberto;
            $saida[$chave]['vendas'] += $produto->vendas;
            $saida[$chave]['media_diaria'] += $produto->vendas > 0 ? $produto->vendas/$campo["dias_media"] : 0;
            $saida[$chave]['media_mes'] = $saida[$chave]['media_diaria'] * 30;
            $saida[$chave]['estoque_meses'] = $saida[$chave]['media_mes'] > 0 ? $saida[$chave]['saldo']/$saida[$chave]['media_mes'] : 0;
            
            $saida[$chave]['participacao_vendas'] += ($participacao_vendas)*100;
            $saida[$chave]['compras_necessidade'] += ($produto->vendas/$campo["dias_media"])*$campo["necessidade_dias"] - ($produto->estoque_total + $totalCompras + $transito) > 0 ? ($produto->vendas/$campo["dias_media"])*$campo["necessidade_dias"] - ($produto->estoque_total + $totalCompras + $transito) : 0;
            $saida[$chave]['percentual_venda'] = $produto->vendas/$meses > 0 ? ($produto->ultimos_30_dias - ($produto->vendas/$meses)) * (100/($produto->vendas/$meses)) : -100;
            $saida[$chave]['media_venda']  += $produto->vendas /$campo['dias_media'];

            $total['estoque'] += $produto->estoque_total;
            $total['estoque_transito'] += $transito;
            $total['participacao_estoque'] += $produto->participacao; 
            $total['compras_mes_atual'] += $produto->compras_mes_atual;
            $total['compras_proximo_mes'] += $produto->compras_proximo_mes;
            $total['compras_mes_seguinte'] +=  $produto->compras_mes_seguinte;
            $total['compras_proximos_meses'] += $produto->compras_proximos_meses;
            $total['pedidos_aberto'] += $produto->total_vendas_aberto;
            $total['saldo'] += ($produto->estoque_total + $totalCompras + $transito) - $produto->total_vendas_aberto;
            $total['vendas'] += $produto->vendas;
            $total['media_diaria'] += $produto->vendas > 0 ? $produto->vendas/$campo["dias_media"] : 0;
            $total['participacao_vendas'] += ($participacao_vendas)*100;
            $total['compras_necessidades'] += ($produto->vendas/$campo["dias_media"])*$campo["necessidade_dias"] - ($produto->estoque_total + $totalCompras + $transito) > 0 ? ($produto->vendas/$campo["dias_media"])*$campo["necessidade_dias"] - ($produto->estoque_total + $totalCompras + $transito) : 0;
            $total['filters']['produtos_codigo'][] = $produto->codigo_produto;
            $total['media_venda']  += $produto->vendas /$campo['dias_media'];
            
         }

         $total['estoque'] = $total['estoque'] > 0 ? parserQtd($total['estoque']) : '';
         $total['estoque_transito'] = $total['estoque_transito'] > 0 ? parserQtd($total['estoque_transito']) : '';
         $total['pedidos_aberto'] = $total['pedidos_aberto'] > 0 ? parserQtd($total['pedidos_aberto']) : '';
         $total['participacao_estoque'] = $total['participacao_estoque'] <= 0.005 ? '' : parserQtd($total['participacao_estoque']);
         $total['compras_mes_atual'] = $total['compras_mes_atual'] > 0 ? parserQtd($total['compras_mes_atual']) : '';
         $total['compras_proximo_mes'] = $total['compras_proximo_mes'] > 0 ? parserQtd($total['compras_proximo_mes']) : '';
         $total['compras_mes_seguinte'] = $total['compras_mes_seguinte'] > 0 ? parserQtd($total['compras_mes_seguinte']) : '';
         $total['compras_proximos_meses'] = $total['compras_proximos_meses'] > 0 ? parserQtd($total['compras_proximos_meses']) : '';
         $total['saldo'] = ($total['saldo'] > 0) ? parserQtd($total['saldo']) : '';
         $total['vendas'] = $total['vendas'] > 0 ? parserQtd($total['vendas']) : '';
         $total['media_diaria'] = $total['media_diaria'] > 0 ? parserQtd($total['media_diaria']) : '';
         $total['participacao_vendas'] = $total['participacao_vendas'] <= 0.005 ? '' : parserQtd($total['participacao_vendas']);
         $total['compras_necessidades'] = $total['compras_necessidades'] > 0 ? parserQtd($total['compras_necessidades']) : '';
         $total['filters'] = encrypt($total['filters']);
         $total['media_venda'] = $total['media_venda'] > 0 ? parserQtd($total['media_venda']) : '';

		if(!isset($saida)){
			if(!isset($campo["export"])){
				return response()->json([
					'status' => 'sucess',
					'message' => '',
					'error' => '', 
					'response' => ['saida' => null, 'total' => 'null'],
				],200);
			}else{
				return [];
			}
		}

		foreach($saida as $key => $value){
            $saida[$key]['compras_mes_atual'] = $saida[$key]['compras_mes_atual'] > 0 ? parserQtd($saida[$key]['compras_mes_atual']) : '';
            $saida[$key]['compras_proximo_mes'] = $saida[$key]['compras_proximo_mes'] > 0 ? parserQtd($saida[$key]['compras_proximo_mes']) : '';
            $saida[$key]['compras_mes_seguinte'] = $saida[$key]['compras_mes_seguinte'] > 0 ? parserQtd($saida[$key]['compras_mes_seguinte']) : '';
            $saida[$key]['compras_proximos_meses'] = $saida[$key]['compras_proximos_meses'] > 0 ? parserQtd($saida[$key]['compras_proximos_meses']) : '';
            $saida[$key]['compras_necessidade'] = $saida[$key]['compras_necessidade'] > 0 ? parserQtd($saida[$key]['compras_necessidade']) : '';
            $saida[$key]['estoque'] = $saida[$key]['estoque'] > 0 ? parserQtd($saida[$key]['estoque']) : '';
            $saida[$key]['estoque_transito'] = $saida[$key]['estoque_transito'] > 0 ? parserQtd($saida[$key]['estoque_transito']) : '';
            $saida[$key]['participacao_estoque'] = $saida[$key]['participacao_estoque'] <= 0.005 ? '' : parserQtd($saida[$key]['participacao_estoque']);
            $saida[$key]['pedidos_aberto'] = $saida[$key]['pedidos_aberto'] > 0 ? parserQtd($saida[$key]['pedidos_aberto']) : '';
            $saida[$key]['saldo'] = $saida[$key]['saldo'] <= 0 ? '' : parserQtd($saida[$key]['saldo']);
            $saida[$key]['vendas'] = $saida[$key]['vendas'] > 0 ? parserQtd($saida[$key]['vendas']) : '';
            $saida[$key]['participacao_vendas'] = $saida[$key]['participacao_vendas'] <= 0.005 ? '' : parserQtd($saida[$key]['participacao_vendas']);
            $saida[$key]['media_diaria'] = $saida[$key]['media_diaria'] > 0 ? parserQtd($saida[$key]['media_diaria']) : '';
            $saida[$key]['media_mes'] = $saida[$key]['media_mes'] > 0 ? parserQtd($saida[$key]['media_mes']) : '';
            $saida[$key]['estoque_meses'] = $saida[$key]['estoque_meses'] > 0 ? parserQtd($saida[$key]['estoque_meses']) : '';
            $saida[$key]['ultima_compra'] = max($saida[$key]['ultima_compra']);
            $saida[$key]['ultima_compra'] = !empty($saida[$key]['ultima_compra']) ? parserData($saida[$key]['ultima_compra']) : '';
            $saida[$key]['filters'] = encrypt($saida[$key]['filters']);

            if($saida[$key]['percentual_venda'] >= $alto_giro_percentual){
                $saida[$key]['giro'] = "<span class='text-primary'> Alto </span>";;
            }
            elseif($saida[$key]['percentual_venda'] <= $baixo_giro_percentual){
                $saida[$key]['giro'] = "<span class='text-danger'> Baixo </span>";
            }else{
                $saida[$key]['giro'] = "<span class='text-success'> Normal </span>";
            }
            $saida[$key]['media_venda'] = $saida[$key]['media_venda'] > 0 ? parserQtd($saida[$key]['media_venda']) : '';
        }
		$saida = array_values($saida);
		if(!isset($campo["export"])){
			return response()->json([
				'status' => 'sucess',
				'message' => '',
				'error' => '', 
				'response' => [
					'saida' => $saida, 
					'total' => $total,
					'produto_grupo' => $campo["produto_grupo"]
				]
			],200);
		}else{
			return $saida;
		}
    }

    public function modalCompras(Request $request){
        ini_set('memory_limit','1024M');
        set_time_limit(300);

        $filter = $request->only(['filters', 'mes_atual', 'proximo_mes', 'mes_seguinte', 'proximos_meses']);
        try{
            $busca = decrypt($filter['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $data_atual = Carbon::now()->format('Y-m');
        $proximo_mes = Carbon::now()->addMonth(1)->format('Y-m');
        $mes_seguinte = Carbon::now()->addMonth(2)->format('Y-m');
        $proximos_meses = Carbon::now()->addMonth(3)->format('Y-m');

       if(isset($filter['mes_atual'])){
           $ComprasNasajonObj = ComprasNasajon::whereIn('cod_produto', $busca['produtos_codigo'])
           ->whereIn('situacao',['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado'])
           ->where('situacao_item','<>','Cancelado')
           ->where(DB::raw("case
           when situacao = 'Parcialmente Liquidado' then
               quantidade_restante
           else
               quantidade
           end"), '>', 0)
            ->where(DB::raw("TO_CHAR(previsao_entrega, 'YYYY-MM')"), $data_atual);
       }
       if(isset($filter['proximo_mes'])){
            $ComprasNasajonObj = ComprasNasajon::whereIn('cod_produto', $busca['produtos_codigo'])
            ->whereIn('situacao',['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado'])
            ->where('situacao_item','<>','Cancelado')
            ->where(DB::raw("case
            when situacao = 'Parcialmente Liquidado' then
                quantidade_restante
            else
                quantidade
            end"), '>', 0)
            ->where(DB::raw("TO_CHAR(previsao_entrega, 'YYYY-MM')"), $proximo_mes);
        } 
        if(isset($filter['mes_seguinte'])){
            $ComprasNasajonObj = ComprasNasajon::whereIn('cod_produto', $busca['produtos_codigo'])
            ->whereIn('situacao',['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado'])
            ->where('situacao_item','<>','Cancelado')
            ->where(DB::raw("case
            when situacao = 'Parcialmente Liquidado' then
                quantidade_restante
            else
                quantidade
            end"), '>', 0)
            ->where(DB::raw("TO_CHAR(previsao_entrega, 'YYYY-MM')"), $mes_seguinte);
        } 
        if(isset($filter['proximos_meses'])){
            $ComprasNasajonObj = ComprasNasajon::whereIn('situacao',['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado'])
            ->whereIn('cod_produto', $busca['produtos_codigo'])
            ->where('situacao_item','<>','Cancelado')
            ->where(DB::raw("case
            when situacao = 'Parcialmente Liquidado' then
                quantidade_restante
            else
                quantidade
            end"), '>', 0)
            ->where(DB::raw("TO_CHAR(previsao_entrega, 'YYYY-MM')"), '>=', $proximos_meses);
        }

        $ComprasNasajon = $ComprasNasajonObj->get();
        $estabelecimentos = returnEmpresasNasajonView();

        $totalCompras = 0;
        $saida = [];
        foreach($ComprasNasajon as $produto){
            $compra_quantidade = 0;

            if($produto->situacao_item == 'Parcialmente Liquidado'){
                $compra_quantidade = $produto->quantidade_restante;
            }else{
                $compra_quantidade = $produto->quantidade;
            }

            $saida[] = [
                'estabelecimento' => $estabelecimentos[(integer)$produto->estabelecimento],
                'PCMN' => $produto->numero_pedido,
                'proforma' => $produto->proforma,
                'nota' => '',
                'id' => encrypt($produto->id_nota),
                'previsao' => parserData($produto->previsao_entrega),
                'codigo' => $produto->cod_produto,
                'nome' => $produto->descricao_produto,
                'compras' => $compra_quantidade > 0 ? parserQtd($compra_quantidade) : ''
            ];
            $totalCompras += $compra_quantidade;
        }
        return view('programs.produto.modal.busca_compras')->with(["dados" => $saida, 'totalCompras' => $totalCompras]);
    }

    public function modalVendas(Request $request){
        $filter = $request->only(['filters', 'total_vendas']);
        try{
            $busca = decrypt($filter['filters']);
        }catch(\Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return,422);
        }

        $GiroDeEstoqueObj = GiroDeEstoque::select(DB::raw('
            sum(consumo_01_mes) as ultimos_30_dias,
            sum(consumo_02_mes) as ultimos_60_dias,
            sum(consumo_03_mes) as ultimos_90_dias,
            sum(consumo_04_mes) as ultimos_120_dias,
            sum(consumo_05_mes) as ultimos_150_dias,
            sum(consumo_06_mes) as ultimos_180_dias,
            sum(consumo_07_mes) as ultimos_210_dias,
            sum(consumo_08_mes) as ultimos_240_dias,
            sum(consumo_09_mes) as ultimos_270_dias,
            sum(consumo_10_mes) as ultimos_300_dias,
            sum(consumo_11_mes) as ultimos_330_dias,
            sum(consumo_12_mes) as ultimos_360_dias'));
            
        if(!empty($busca['origem'])){
            $GiroDeEstoqueObj->where('origem', $busca['origem']);
        }

        if($filter['total_vendas'] == 'true'
         && (!empty($busca['grupo'])
         || !empty($busca['marca'])
         || !empty($busca['linha'])
         || !empty($busca['descricao'])
         || !empty($busca['codigo']))){
            if(!empty($busca['grupo'])){
                $GiroDeEstoqueObj->where('grupo', $busca['grupo'])
                ->groupBy('grupo');
            }
            if(!empty($busca['marca'])){
                $GiroDeEstoqueObj->where('marca', $busca['marca'])
                ->groupBy('marca');
            }
            if(!empty($busca['linha'])){
                $GiroDeEstoqueObj->where('linha', $busca['linha'])
                ->groupBy('linha');
            }
            if(!empty($busca['codigo'])){
                $GiroDeEstoqueObj->where('codigo_produto', $busca['codigo'])
                ->groupBy('codigo_produto');
            }
            if(!empty($busca['descricao'])){
                $GiroDeEstoqueObj->where('descricao', $busca['descricao'])
                ->groupBy('descricao');
            }
         }

         if($busca['produto_grupo'] == 'grupo' && $filter['total_vendas'] == 'false' && empty($busca['campo_codigo']) && empty($busca['campo_descricao'])){
            $GiroDeEstoqueObj->select('grupo', 'marca', 'linha', DB::raw('
            sum(consumo_01_mes) as ultimos_30_dias,
            sum(consumo_02_mes) as ultimos_60_dias,
            sum(consumo_03_mes) as ultimos_90_dias,
            sum(consumo_04_mes) as ultimos_120_dias,
            sum(consumo_05_mes) as ultimos_150_dias,
            sum(consumo_06_mes) as ultimos_180_dias,
            sum(consumo_07_mes) as ultimos_210_dias,
            sum(consumo_08_mes) as ultimos_240_dias,
            sum(consumo_09_mes) as ultimos_270_dias,
            sum(consumo_10_mes) as ultimos_300_dias,
            sum(consumo_11_mes) as ultimos_330_dias,
            sum(consumo_12_mes) as ultimos_360_dias'))
            ->where('grupo', $busca['grupo'])
            ->where('linha', $busca['linha'])
            ->where('marca', $busca['marca'])
            ->groupBy('grupo', 'marca', 'linha');
         }

        if($busca['produto_grupo'] == 'produto' && $filter['total_vendas'] == 'false' || $busca['produto_grupo'] == 'grupo' && (!empty($busca['campo_codigo']) || !empty($busca['campo_descricao']))){
            $GiroDeEstoqueObj->select('grupo', 'marca', 'linha', 'codigo_produto', 'descricao', 
            'consumo_01_mes as ultimos_30_dias',
            'consumo_02_mes as ultimos_60_dias',
            'consumo_03_mes as ultimos_90_dias',
            'consumo_04_mes as ultimos_120_dias',
            'consumo_05_mes as ultimos_150_dias',
            'consumo_06_mes as ultimos_180_dias',
            'consumo_07_mes as ultimos_210_dias',
            'consumo_08_mes as ultimos_240_dias',
            'consumo_09_mes as ultimos_270_dias',
            'consumo_10_mes as ultimos_300_dias',
            'consumo_11_mes as ultimos_330_dias',
            'consumo_12_mes as ultimos_360_dias')
            ->where('codigo_produto', $busca['codigo']);
        }
  
        $GiroDeEstoque =  $GiroDeEstoqueObj->first();

        if($filter['total_vendas'] == 'true'
         && (!empty($busca['grupo'])
         || !empty($busca['marca'])
         || !empty($busca['linha']))){
            $grupo = !empty($busca['grupo']) ? $busca['grupo'] : 'TODOS';
            $marca = !empty($busca['marca']) ? $busca['marca'] : 'TODAS';
            $linha = !empty($busca['linha']) ? $busca['linha'] : 'TODAS';
        }

        if($filter['total_vendas'] == 'true'
        && empty($busca['grupo'])
        && empty($busca['marca'])
        && empty($busca['linha'])
        && empty($busca['codigo'])
        && empty($busca['descricao'])){
            $grupo = 'TODOS';
            $marca = 'TODAS';
            $linha = 'TODAS';
        }
        

        $produto_grupo = [
            'grupo' => isset($grupo) ? $grupo : $GiroDeEstoque->codigo_produto,
            'marca' => isset($marca) ? $marca : $GiroDeEstoque->marca,
            'linha' => isset($linha) ? $linha: $GiroDeEstoque->linha,
            'codigo' => $busca['produto_grupo'] == 'produto' ? $GiroDeEstoque->codigo_produto : '',
            'descricao' => $busca['produto_grupo'] == 'produto' ? $GiroDeEstoque->descricao : ''
        ];

        $quantidade_60_dias = $GiroDeEstoque->ultimos_60_dias == $GiroDeEstoque->ultimos_30_dias ? 0 : $GiroDeEstoque->ultimos_60_dias;
        $quantidade_90_dias = $GiroDeEstoque->ultimos_90_dias == $GiroDeEstoque->ultimos_60_dias ? 0 : $GiroDeEstoque->ultimos_90_dias;
        $quantidade_120_dias = $GiroDeEstoque->ultimos_120_dias == $GiroDeEstoque->ultimos_90_dias ? 0 : $GiroDeEstoque->ultimos_120_dias;
        $quantidade_150_dias = $GiroDeEstoque->ultimos_150_dias == $GiroDeEstoque->ultimos_120_dias ? 0 : $GiroDeEstoque->ultimos_150_dias;
        $quantidade_180_dias = $GiroDeEstoque->ultimos_180_dias == $GiroDeEstoque->ultimos_150_dias ? 0 : $GiroDeEstoque->ultimos_180_dias;
        $quantidade_210_dias = $GiroDeEstoque->ultimos_210_dias == $GiroDeEstoque->ultimos_180_dias ? 0 : $GiroDeEstoque->ultimos_210_dias;
        $quantidade_240_dias = $GiroDeEstoque->ultimos_240_dias == $GiroDeEstoque->ultimos_210_dias ? 0 : $GiroDeEstoque->ultimos_240_dias;
        $quantidade_270_dias = $GiroDeEstoque->ultimos_270_dias == $GiroDeEstoque->ultimos_240_dias ? 0 : $GiroDeEstoque->ultimos_270_dias;
        $quantidade_300_dias = $GiroDeEstoque->ultimos_300_dias == $GiroDeEstoque->ultimos_270_dias ? 0 : $GiroDeEstoque->ultimos_300_dias;
        $quantidade_330_dias = $GiroDeEstoque->ultimos_330_dias == $GiroDeEstoque->ultimos_300_dias ? 0 : $GiroDeEstoque->ultimos_330_dias;
        $quantidade_360_dias = $GiroDeEstoque->ultimos_360_dias == $GiroDeEstoque->ultimos_330_dias ? 0 : $GiroDeEstoque->ultimos_360_dias;
        

        $quantidade_vendas = [
            'quantidade_30_dias' => $busca['dias_media'] >= 30 ? $GiroDeEstoque->ultimos_30_dias : 0,
            'quantidade_60_dias' => $busca['dias_media'] >= 60 ?  $quantidade_60_dias: 0, 
            'quantidade_90_dias' => $busca['dias_media'] >= 90 ? $quantidade_90_dias : 0, 
            'quantidade_120_dias' => $busca['dias_media'] >= 120 ? $quantidade_120_dias: 0,
            'quantidade_150_dias' => $busca['dias_media'] >= 150 ? $quantidade_150_dias :0,
            'quantidade_180_dias' => $busca['dias_media'] >= 180 ? $quantidade_180_dias: 0,
            'quantidade_210_dias' => $busca['dias_media'] >= 210 ? $quantidade_210_dias: 0,
            'quantidade_240_dias' => $busca['dias_media'] >= 240 ? $quantidade_240_dias : 0,
            'quantidade_270_dias' => $busca['dias_media'] >= 270 ? $quantidade_270_dias : 0,
            'quantidade_300_dias' => $busca['dias_media'] >= 300 ? $quantidade_300_dias : 0,
            'quantidade_330_dias' => $busca['dias_media'] >= 330 ? $quantidade_330_dias : 0,
            'quantidade_360_dias' => $busca['dias_media'] >= 360 ? $quantidade_360_dias : 0
        ];

        $media_vendas = [
            'media_30_dias' => $busca['dias_media'] >= 30 ? parserQtd($GiroDeEstoque->ultimos_30_dias/30): 0,
            'media_60_dias' => $busca['dias_media'] >= 60 ? parserQtd($quantidade_60_dias/60) : 0,
            'media_90_dias' => $busca['dias_media'] >= 90 ? parserQtd($quantidade_90_dias/90) : 0,
            'media_120_dias' => $busca['dias_media'] >= 120 ? parserQtd($quantidade_120_dias/120) : 0,
            'media_150_dias' => $busca['dias_media'] >= 150 ? parserQtd($quantidade_150_dias/150) : 0,
            'media_180_dias' => $busca['dias_media'] >= 180 ? parserQtd($quantidade_180_dias/180) : 0,
            'media_210_dias' => $busca['dias_media'] >= 210 ? parserQtd($quantidade_210_dias/210) : 0,
            'media_240_dias' => $busca['dias_media'] >= 240 ? parserQtd($quantidade_240_dias/240) : 0,
            'media_270_dias' => $busca['dias_media'] >= 270 ? parserQtd($quantidade_270_dias/270) : 0,
            'media_300_dias' => $busca['dias_media'] >= 300 ? parserQtd($quantidade_300_dias/300) : 0,
            'media_330_dias' => $busca['dias_media'] >= 330 ? parserQtd($quantidade_330_dias/330) : 0,
            'media_360_dias' => $busca['dias_media'] >= 360 ? parserQtd($quantidade_360_dias/360) : 0,
        ];
    
        $totalQuantidade = max($quantidade_vendas);
        $totalQuantidade = parserQtd($totalQuantidade);

        $quantidade_vendas['quantidade_30_dias'] = $quantidade_vendas['quantidade_30_dias'] > 0 ? parserQtd($quantidade_vendas['quantidade_30_dias']) : '';
        $quantidade_vendas['quantidade_60_dias'] = $quantidade_vendas['quantidade_60_dias'] > 0 ? parserQtd($quantidade_vendas['quantidade_60_dias']) : '';
        $quantidade_vendas['quantidade_90_dias'] = $quantidade_vendas['quantidade_90_dias'] > 0 ? parserQtd($quantidade_vendas['quantidade_90_dias']) : '';
        $quantidade_vendas['quantidade_120_dias'] = $quantidade_vendas['quantidade_120_dias'] > 0 ? parserQtd($quantidade_vendas['quantidade_120_dias']) : '';
        $quantidade_vendas['quantidade_150_dias'] = $quantidade_vendas['quantidade_150_dias'] > 0 ? parserQtd($quantidade_vendas['quantidade_150_dias']) : '';
        $quantidade_vendas['quantidade_180_dias'] = $quantidade_vendas['quantidade_180_dias'] > 0 ? parserQtd($quantidade_vendas['quantidade_180_dias']) : '';
        $quantidade_vendas['quantidade_210_dias'] = $quantidade_vendas['quantidade_210_dias'] > 0 ? parserQtd($quantidade_vendas['quantidade_210_dias']) : '';
        $quantidade_vendas['quantidade_240_dias'] = $quantidade_vendas['quantidade_240_dias'] > 0 ? parserQtd($quantidade_vendas['quantidade_240_dias']) : '';
        $quantidade_vendas['quantidade_270_dias'] = $quantidade_vendas['quantidade_270_dias'] > 0 ? parserQtd($quantidade_vendas['quantidade_270_dias']) : '';
        $quantidade_vendas['quantidade_300_dias'] = $quantidade_vendas['quantidade_300_dias'] > 0 ? parserQtd($quantidade_vendas['quantidade_300_dias']) : '';
        $quantidade_vendas['quantidade_330_dias'] = $quantidade_vendas['quantidade_330_dias'] > 0 ? parserQtd($quantidade_vendas['quantidade_330_dias']) : '';
        $quantidade_vendas['quantidade_360_dias'] = $quantidade_vendas['quantidade_360_dias'] > 0 ? parserQtd($quantidade_vendas['quantidade_360_dias']) : '';
   
        $data_atual = Carbon::now();
        $retorno[$data_atual->format('mY')] = [
            'data' => $data_atual->format('mY'),
            'compra' => 0,
            'venda' => parserQtd($GiroDeEstoque->ultimos_30_dias),
            'remessas' => 0,
            'qtd_meses' => 12 ,
   
        ];
        $data_atual = Carbon::now();
        $retorno[$data_atual->subMonths(1)->format('mY')] = [
            'data' => $data_atual->subMonths(1)->format('mY'),
            'compra' => 0,
            'venda' => parserQtd($GiroDeEstoque->ultimos_60_dias),
            'remessas' => 0,
            'qtd_meses' => 12 ,
   
        ];
        $data_atual = Carbon::now();
        $retorno[$data_atual->subMonths(2)->format('mY')] = [
            'data' => $data_atual->subMonths(2)->format('mY'),
            'compra' => 0,
            'venda' => parserQtd($GiroDeEstoque->ultimos_90_dias),
            'remessas' => 0,
            'qtd_meses' => 12 ,
   
        ];
        $data_atual = Carbon::now();
        $retorno[$data_atual->subMonths(3)->format('mY')] = [
            'data' => $data_atual->subMonths(3)->format('mY'),
            'compra' => 0,
            'venda' => parserQtd($GiroDeEstoque->ultimos_120_dias),
            'remessas' => 0,
            'qtd_meses' => 12 ,
   
        ];
        $data_atual = Carbon::now();
        $retorno[$data_atual->subMonths(4)->format('mY')] = [
            'data' => $data_atual->subMonths(4)->format('mY'),
            'compra' => 0,
            'venda' => parserQtd($GiroDeEstoque->ultimos_150_dias),
            'remessas' => 0,
            'qtd_meses' => 12 ,
   
        ];
        $data_atual = Carbon::now();
        $retorno[$data_atual->subMonths(5)->format('mY')] = [
            'data' => $data_atual->subMonths(5)->format('mY'),
            'compra' => 0,
            'venda' => parserQtd($GiroDeEstoque->ultimos_180_dias),
            'remessas' => 0,
            'qtd_meses' => 12 ,
   
        ];
        $data_atual = Carbon::now();
        $retorno[$data_atual->subMonths(6)->format('mY')] = [
            'data' => $data_atual->subMonths(6)->format('mY'),
            'compra' => 0,
            'venda' => parserQtd($GiroDeEstoque->ultimos_210_dias),
            'remessas' => 0,
            'qtd_meses' => 12 ,
   
        ];
        $data_atual = Carbon::now();
        $retorno[$data_atual->subMonths(7)->format('mY')] = [
            'data' => $data_atual->subMonths(7)->format('mY'),
            'compra' => 0,
            'venda' => parserQtd($GiroDeEstoque->ultimos_240_dias),
            'remessas' => 0,
            'qtd_meses' => 12 ,
   
        ];
        $data_atual = Carbon::now();
        $retorno[$data_atual->subMonths(8)->format('mY')] = [
            'data' => $data_atual->subMonths(8)->format('mY'),
            'compra' => 0,
            'venda' => parserQtd($GiroDeEstoque->ultimos_270_dias),
            'remessas' => 0,
            'qtd_meses' => 12 ,
   
        ];
        $data_atual = Carbon::now();
        $retorno[$data_atual->subMonths(9)->format('mY')] = [
            'data' => $data_atual->subMonths(9)->format('mY'),
            'compra' => 0,
            'venda' => parserQtd($GiroDeEstoque->ultimos_300_dias),
            'remessas' => 0,
            'qtd_meses' => 12 ,
   
        ];
        $data_atual = Carbon::now();
        $retorno[$data_atual->subMonths(10)->format('mY')] = [
            'data' => $data_atual->subMonths(10)->format('mY'),
            'compra' => 0,
            'venda' => parserQtd($GiroDeEstoque->ultimos_330_dias),
            'remessas' => 0,
            'qtd_meses' => 12 ,
   
        ];
        $data_atual = Carbon::now();
        $retorno[$data_atual->subMonths(11)->format('mY')] = [
            'data' => $data_atual->subMonths(11)->format('mY'),
            'compra' => 0,
            'venda' => parserQtd($GiroDeEstoque->ultimos_360_dias),
            'remessas' => 0,
            'qtd_meses' => 12 ,
   
        ];
        $head = [];
        
        for($i = 0; $i <= 11 ; $i ++){
            $headData = Carbon::now()->subMonth($i);
            $data = $headData->format('mY');
            $show = $headData->format('m/Y');
            $head[$data] = $show;
        }

        $total_giro =$GiroDeEstoque->ultimos_30_dias +$GiroDeEstoque->ultimos_60_dias+$GiroDeEstoque->ultimos_90_dias +$GiroDeEstoque->ultimos_120_dias
        +$GiroDeEstoque->ultimos_150_dias +$GiroDeEstoque->ultimos_180_dias+$GiroDeEstoque->ultimos_210_dias +$GiroDeEstoque->ultimos_240_dias
        +$GiroDeEstoque->ultimos_270_dias +$GiroDeEstoque->ultimos_300_dias+$GiroDeEstoque->ultimos_330_dias +$GiroDeEstoque->ultimos_360_dias;
        $total = [
           
            "vendido" => ( $total_giro > 0) ? parserQtd( $total_giro) : '',
           
        ];

        return view('programs.giro_de_estoque.modal.vendas')->with([
            'produto' => $produto_grupo,
            'quantidade' => $quantidade_vendas,
            'media' => $media_vendas,
            'totalQuantidade' => $totalQuantidade,
            'dados' => $retorno,
            'th' =>$head,
            'mes' => 12,
            'fields' => $busca,
            'total' =>$total
        ]);
    }

    public function filtroBaseGiroDeEstoque(){
        ini_set('memory_limit','3072M');

        $cnpj_intercompany = $this->cnpjINtercompany();

        $ProdutoEspecificacaoObj = ProdutoEspecificacao::with(['estoque' => function($query){
            $query->select('codigo_produto', DB::Raw('sum(estoque) as estoque'))
            ->groupBy('codigo_produto');
            
        }])
        ->with(['movimentacao' => function($query) use ($cnpj_intercompany) {
            $date = Carbon::now();
            $date = $date->subMonth(12);
            $um_ano_atras =  $date->format('Y-m-d');

            $query->select('produto_codigo', 'data_movimentacao', 'quantidade')
            ->where('sinal', 'SAIDA')
            ->where('documento', '!=', ' ')
            ->whereNotIn('cliente_codigo', $cnpj_intercompany)
            ->whereNotNull('data_movimentacao')
            ->where('data_movimentacao', '>=', $um_ano_atras)
            // ->whereIn('cfop', [5922, 5949, 6108, 6110, 6119, 6123, 6106, 5123, 6118, 5118,
            //  5122, 5551, 6551, 5102, 6102, 5106, 5101, 6101, 5901,5910,5911,5912,5924,6901,6910,6911,6912,692]);
            ->whereIn('cfop', [5922, 6108, 6110, 6119, 6123, 6106, 5123, 6118, 5118,
             5122, 5551, 6551, 5102, 6102, 5106, 5101, 6101, 5901,5910,5911,5912,5924,6901,6910,6911,6912,692]);
        }])
        ->with('preco','produtoGrupo')
        ->with(['compras' => function($query){
            $query->whereIn('situacao',['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado'])
            ->where('situacao_item','<>','Cancelado')
            ->where(DB::raw("case
            when situacao = 'Parcialmente Liquidado' then
                quantidade_restante
            else
                quantidade
            end"), '>', 0);
        }])
        ->with(['reserva' => function($query) use ($cnpj_intercompany){
                foreach($cnpj_intercompany as $cnpj){
                    if(strlen($cnpj) == 14){
                        $estabelecimento_cnpj_formatado[] = mask($cnpj, '##.###.###/####-##');
                    }
                }

            $query->with(['pedidosVendaNasajon' => function($filtroPedidosNasajon) use ($cnpj_intercompany){
                $filtroPedidosNasajon->whereNotIn('cliente_codigo', $cnpj_intercompany);
            }])
            ->with(['notasNasajon' => function($filtroNotasNasajon) use ($estabelecimento_cnpj_formatado){
                $filtroNotasNasajon->whereNotIn('cliente_documento', $estabelecimento_cnpj_formatado);
            }]);
        }]) 
        ->where('ativo', true);

        $ProdutoEspecificacao = $ProdutoEspecificacaoObj->get();

        $pedidoPortal = PedidoPortal::with(['itens_pedido' => function($query) use ($ProdutoEspecificacaoObj){
            $query->whereIn('cod_produto', $ProdutoEspecificacaoObj->pluck('codigo_produto'));
        }])
        ->whereNotIn('status_pedido', [3, 5, 7])
        ->whereNotIn('cod_cliente', $cnpj_intercompany)
        ->whereHas('itens_pedido', function($query) use ($ProdutoEspecificacaoObj){
            $query->whereIn('cod_produto', $ProdutoEspecificacaoObj->pluck('codigo_produto'));
        })
        ->get();

        $totalEstoque = 0;
       
        foreach($ProdutoEspecificacao->chunk(500) as $chunk){
            foreach($chunk as $produto){
                try{
                    switch ($produto->procedencia) {
                        case 0:
                        case 3:
                        case 4:
                        case 5:
                            $procedencia = "Nacional";
                            break;
                        case 1:
                        case 2:
                        case 6:
                        case 7:
                            $procedencia = "Importado";
                            break;
                    }

                    $estoque = $produto->estoque->sum('estoque');
                    $totalEstoque += $estoque;

                    $saida[$produto->codigo_produto] = [
                        'codigo_produto' => $produto->codigo_produto,
                        'descricao' => $produto->descricao,
                        'grupo' => (!empty($produto->produtoGrupo->descricao)) ? $produto->produtoGrupo->descricao : $produto->grupo,
                        'linha' => $produto->linha,
                        'marca' => $produto->marca,
                        'origem' => $procedencia,
                        'unidade' => $produto->unidade,
                        'ultima_compra' => isset($produto->preco->ultima_compra_real) ?  $produto->preco->ultima_compra_real : null,
                        'preco_venda' => isset($produto->preco->preco_real) ? $produto->preco->preco_real : null,
                        'custo_gerencial' => isset($produto->preco->compra_real) ? $produto->preco->compra_real : null,
                        'compras_mes_atual' => 0,
                        'compras_proximo_mes' => 0,
                        'compras_mes_seguinte' => 0,
                        'compras_proximos_meses' => 0,
                        'compras_aberto_vendas' => 0,
                        'estoque' => $estoque,
                        'percentual_estoque' => 0,
                        'pedidos_venda' => 0,
                        'vendas_aberto' => 0,
                        'consumo_01_mes' => 0,
                        'consumo_02_mes' => 0,
                        'consumo_03_mes' => 0,
                        'consumo_04_mes' => 0,
                        'consumo_05_mes' => 0,
                        'consumo_06_mes' => 0,
                        'consumo_07_mes' => 0,
                        'consumo_08_mes' => 0,
                        'consumo_09_mes' => 0,
                        'consumo_10_mes' => 0,
                        'consumo_11_mes' => 0,
                        'consumo_12_mes' => 0
                    ];

                    foreach($produto->compras as $compra){
                        $mes_chegada = Carbon::parse($compra->previsao_entrega)->format('Y-m');
                        $compra_quantidade = 0;
                        $mes_atual = Carbon::now()->format('Y-m');
                        $proximo_mes = Carbon::now()->addMonth(1)->format('Y-m');
                        $mes_seguinte = Carbon::now()->addMonth(2)->format('Y-m');
                        $proximos_meses = Carbon::now()->addMonth(3)->format('Y-m');

                        if($compra->situacao_item == 'Parcialmente Liquidado'){
                            $compra_quantidade = $compra->quantidade_restante;
                        }else{
                            $compra_quantidade = $compra->quantidade;
                        }

                        $saida[$compra->cod_produto]['compras_mes_atual'] += $mes_chegada == $mes_atual ? $compra_quantidade : 0;
                        $saida[$compra->cod_produto]['compras_proximo_mes'] += $mes_chegada == $proximo_mes ? $compra_quantidade : 0;
                        $saida[$compra->cod_produto]['compras_mes_seguinte'] += $mes_chegada == $mes_seguinte ? $compra_quantidade : 0;
                        $saida[$compra->cod_produto]['compras_proximos_meses'] += $mes_chegada >= $proximos_meses ? $compra_quantidade : 0;

                        if($compra->situacao_item == 'Aguardando Documento' || $compra->situacao_item == 'Parcialmente Liquidado'){
                            $saida[$compra->cod_produto]['compras_aberto_vendas'] +=  $compra->quantidade;
                        }
                    }

                    foreach($produto->movimentacao as $movimentacao){
                        $data_movimentacao = Carbon::parse($movimentacao->data_movimentacao);
                    
                        $mes_01 = Carbon::now()->addMonth(-1)->format('Y-m-d');
                        $mes_02 = Carbon::now()->addMonth(-2)->format('Y-m-d');
                        $mes_03 = Carbon::now()->addMonth(-3)->format('Y-m-d');
                        $mes_04 = Carbon::now()->addMonth(-4)->format('Y-m-d');
                        $mes_05 = Carbon::now()->addMonth(-5)->format('Y-m-d');
                        $mes_06 = Carbon::now()->addMonth(-6)->format('Y-m-d');
                        $mes_07 = Carbon::now()->addMonth(-7)->format('Y-m-d');
                        $mes_08 = Carbon::now()->addMonth(-8)->format('Y-m-d');
                        $mes_09 = Carbon::now()->addMonth(-9)->format('Y-m-d');
                        $mes_10 = Carbon::now()->addMonth(-10)->format('Y-m-d');
                        $mes_11 = Carbon::now()->addMonth(-11)->format('Y-m-d');
                        $mes_12  = Carbon::now()->addMonth(-12)->format('Y-m-d');
                
                        $saida[$movimentacao->produto_codigo]['consumo_01_mes'] += $data_movimentacao->gte($mes_01) ? $movimentacao->quantidade : 0;
                        $saida[$movimentacao->produto_codigo]['consumo_02_mes'] += $data_movimentacao->gte($mes_02) ? $movimentacao->quantidade : 0;
                        $saida[$movimentacao->produto_codigo]['consumo_03_mes'] += $data_movimentacao->gte($mes_03) ? $movimentacao->quantidade : 0;
                        $saida[$movimentacao->produto_codigo]['consumo_04_mes'] += $data_movimentacao->gte($mes_04) ? $movimentacao->quantidade : 0;
                        $saida[$movimentacao->produto_codigo]['consumo_05_mes'] += $data_movimentacao->gte($mes_05) ? $movimentacao->quantidade : 0;
                        $saida[$movimentacao->produto_codigo]['consumo_06_mes'] += $data_movimentacao->gte($mes_06) ? $movimentacao->quantidade : 0;
                        $saida[$movimentacao->produto_codigo]['consumo_07_mes'] += $data_movimentacao->gte($mes_07) ? $movimentacao->quantidade : 0;
                        $saida[$movimentacao->produto_codigo]['consumo_08_mes'] += $data_movimentacao->gte($mes_08) ? $movimentacao->quantidade : 0;
                        $saida[$movimentacao->produto_codigo]['consumo_09_mes'] += $data_movimentacao->gte($mes_09) ? $movimentacao->quantidade : 0;
                        $saida[$movimentacao->produto_codigo]['consumo_10_mes'] += $data_movimentacao->gte($mes_10) ? $movimentacao->quantidade : 0;
                        $saida[$movimentacao->produto_codigo]['consumo_11_mes'] += $data_movimentacao->gte($mes_11) ? $movimentacao->quantidade : 0;
                        $saida[$movimentacao->produto_codigo]['consumo_12_mes'] += $data_movimentacao->gte($mes_12) ? $movimentacao->quantidade : 0;
                    }

                    foreach($produto->reserva as $reserva){
                        if($reserva->eh_pedido == false){
                            if(isset($saida[$reserva->codigo_produto])){
                                $saida[$reserva->codigo_produto]['pedidos_venda'] += isset($reserva->notasNasajon->id) ? 1 : 0;
                                $saida[$reserva->codigo_produto]['vendas_aberto'] += isset($reserva->notasNasajon->id) ? $reserva->quantidade : 0;
                            }
                        }else{
                            if(isset($saida[$reserva->codigo_produto])){
                                $saida[$reserva->codigo_produto]['pedidos_venda'] += isset($reserva->pedidosVendaNasajon->id) ? 1 : 0;
                                $saida[$reserva->codigo_produto]['vendas_aberto'] += isset($reserva->pedidosVendaNasajon->id) ? $reserva->quantidade : 0;
                            }
                        }
                    }
                }catch (\Exception $e) {
                    Log::error($e->getMessage());
                }
            }
        }

        if(!isset($saida)){
            $saida = [];
        }

        foreach($pedidoPortal as $pedido){
            foreach($pedido->itens_pedido as $item){
                if(isset($saida[$item->cod_produto])){
                    $saida[$item->cod_produto]['pedidos_venda'] += !empty($item->pedido) ? 1 : 0;
                    $saida[$item->cod_produto]['vendas_aberto'] += $item->quantidade;
                }
            }
        }

        foreach($saida as $key => $value){
            $saida[$key]['percentual_estoque'] = $totalEstoque > 0 ? ($saida[$key]['estoque']/$totalEstoque)*100 : 0;
        }
        return $saida;
    }

    public function salvarDadosBaseGiroDeEstoque(){
        ini_set('memory_limit','3072M');
        $produtos = $this->filtroBaseGiroDeEstoque();

        GiroDeEstoque::truncate();
        
        foreach($produtos as $produto){ 
            try{
                $GiroDeEstoqueInsert = new GiroDeEstoque;
                $GiroDeEstoqueInsert->codigo_produto = $produto['codigo_produto'];
                $GiroDeEstoqueInsert->descricao = $produto['descricao'];
                $GiroDeEstoqueInsert->grupo = $produto['grupo'];
                $GiroDeEstoqueInsert->linha = $produto['linha'];
                $GiroDeEstoqueInsert->marca = $produto['marca'];
                $GiroDeEstoqueInsert->origem = strtoupper($produto['origem']);
                $GiroDeEstoqueInsert->estoque = $produto['estoque'];
                $GiroDeEstoqueInsert->ultima_compra = $produto['ultima_compra'];
                $GiroDeEstoqueInsert->consumo_01_mes = $produto['consumo_01_mes'];
                $GiroDeEstoqueInsert->consumo_02_mes = $produto['consumo_02_mes'];
                $GiroDeEstoqueInsert->consumo_03_mes = $produto['consumo_03_mes'];
                $GiroDeEstoqueInsert->consumo_04_mes = $produto['consumo_04_mes'];
                $GiroDeEstoqueInsert->consumo_05_mes = $produto['consumo_05_mes'];
                $GiroDeEstoqueInsert->consumo_06_mes = $produto['consumo_06_mes'];
                $GiroDeEstoqueInsert->consumo_07_mes = $produto['consumo_07_mes'];
                $GiroDeEstoqueInsert->consumo_08_mes = $produto['consumo_08_mes'];
                $GiroDeEstoqueInsert->consumo_09_mes = $produto['consumo_09_mes'];
                $GiroDeEstoqueInsert->consumo_10_mes = $produto['consumo_10_mes'];
                $GiroDeEstoqueInsert->consumo_11_mes = $produto['consumo_11_mes'];
                $GiroDeEstoqueInsert->consumo_12_mes = $produto['consumo_12_mes'];
                $GiroDeEstoqueInsert->percentual_estoque = $produto['percentual_estoque'];
                $GiroDeEstoqueInsert->pedidos_venda = $produto['pedidos_venda'];
                $GiroDeEstoqueInsert->vendas_aberto = $produto['vendas_aberto'];
                $GiroDeEstoqueInsert->unidade = $produto['unidade'];
                $GiroDeEstoqueInsert->compras_mes_atual = $produto['compras_mes_atual'];
                $GiroDeEstoqueInsert->compras_proximo_mes = $produto['compras_proximo_mes'];
                $GiroDeEstoqueInsert->compras_mes_seguinte = $produto['compras_mes_seguinte'];
                $GiroDeEstoqueInsert->compras_proximos_meses = $produto['compras_proximos_meses'];
                $GiroDeEstoqueInsert->preco_venda = $produto['preco_venda'];
                $GiroDeEstoqueInsert->custo_gerencial = $produto['custo_gerencial'];
                $GiroDeEstoqueInsert->compras_aberto_vendas = $produto['compras_aberto_vendas'];
                $GiroDeEstoqueInsert->save(); 
            }catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }
        if(empty($produtos)){
            $produtos = [];
        }
        return count($produtos);
    }

    public function export(GiroDeEstoqueConsultaRequest $request){
        $fields = $request->only('grupo', 'marca', 'linha', 'descricao', 'codigo', 'dias_media', 'necessidade_dias', 
        'alto_giro_percentual', 'baixo_giro_percentual', 'alto_giro', 'baixo_giro','giro_normal', 'produto_grupo', 'origem', 'export');

        $freteXLSX = new GiroDeEstoqueExport($request);

        $pdfFilePath = 'giro_de_estoque.xlsx';
        return Excel::download(
            $freteXLSX, $pdfFilePath
        );
    }


}
    

