<?php

namespace App\Http\Controllers;

use App\Cotacoes;
use Carbon\Carbon;
use App\Importacao;
use App\ComprasNasajon;
use App\OrcamentoCompra;
use Illuminate\Http\Request;
use App\NotasEntradasNasajon;
use App\TitulosAPagarNasajon;
use App\CondicoesPagamentoNasajon;
use Illuminate\Support\Facades\DB;
use App\ParcelasParcelamentoNasajon;
use Illuminate\Support\Facades\Auth;

class AcompanhamentoComprasAnualController extends Controller
{
    public $cfop_devolucao = ['1201', '1202', '2201', '2202'];

    public $cfop_venda = ['5922', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101', '5104', '6104'];

    public $cfop_compras = ['1101', '1102', '1111', '1407', '1556', '1902', '1949', '2101', '2556', '2901', '2902', '3102'];

    private $cfop_remessas = ["5901", "5910", "5911", "5912", "5924", "6901", "6910", "6911", "6912", "6924"];

    private $codigos_fornecedores = ['0000304199999', '0000012009999', '60746948325500', '62232889000190', 'FINIMPBB', 'FINIMPITAU', 'BRADESCOFINIMP', '00000000504580'];
    private $codigos_fornecedores_itau = ['0000012009999', 'FINIMPITAU'];
    private $codigos_fornecedores_brasil = ['0000304199999', 'FINIMPBB', '00000000504580'];
    private $codigos_fornecedores_bradesco = ['60746948325500', 'BRADESCOFINIMP'];
    private $codigos_fornecedores_daycoval = ['62232889000190'];
    private $estabelecimentos_particular = ['20','25', 'MARCELO', 'MN_NAMURA', 'NAMURA_NN','NELSON'];

    public function index(Request $request)


    {

        if (Auth::user()->hasPermissionTo("programas App\AcompanhamentoComprasAnual") === false) {
            return abort(403);
        }
        $request->session()->flash('model', 'App\AcompanhamentoComprasAnual');

        $tipo_produto = [
            'nacional' => "Nacional",
            'importado' => "Importado",
            'uso_consumo' => "Uso Consumo",
        ];

        $mes_anterior = Carbon::now()->subMonth()->format('m/Y');

        return view("programs.acompanhamento_compras_anual.index")->with(['tipo_produto' => $tipo_produto, 'mes_anterior' => $mes_anterior]);
    }

    public function filtro(Request $request)
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);


        $fields = $request->only('ano', 'tipo_produto', 'margem_venda', 'prepago', 'caixa');

        $ano = intval($fields['ano']);
        $data_atual = Carbon::now();
        if ($ano < $data_atual->year) {
            $mes_atual = 13;
        } else {
            $mes_atual = $data_atual->month;
        }
        $primeiro_dia_do_ano = Carbon::parse($ano . "-01-01")->setTime(0, 0, 0);
        $ultimo_dia_do_ano = Carbon::parse($ano . "-12-31")->setTime(23, 59, 59);
   
        $cotacaoDoDiaObj = Cotacoes::wherehas('moeda', function ($query) {
            $query->where('codigo', '220');
        })
            ->orderBy('data', 'desc')->first();



        $retorno = [
            'orcamento_compras' => [
                'descricao' => 'Previsto',
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
                'inteiro_total_parcial' => 0,
            ],
            'orcamento_compras_caixa' => [
                'descricao' => 'Previsto',
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
                'inteiro_total_parcial' => 0,
            ],
            'compras_planejadas' => [
                'descricao' => 'Compras Em Aberto',
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
                'inteiro_total_parcial' => 0,
            ],
            'compras_realizadas' => [
                'descricao' => 'Realizada',
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
                'inteiro_total_parcial' => 0,
            ],
            'titulos_caixa_total' => [
                'descricao' => 'Total Titulos',
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
                'inteiro_total_parcial' => 0,
            ],
            'titulos_caixa_aberto' => [
                'descricao' => 'Titulos Em Aberto',
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
                'inteiro_total_parcial' => 0,
            ],
            'titulos_caixa_fechado' => [
                'descricao' => 'Titulos Pagos',
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
                'inteiro_total_parcial' => 0,
            ],
            'compras_diferenca' => [
                'descricao' => 'Diferença %',
                'inteiro_1' => 0,
                'inteiro_2' => 0,
                'inteiro_3' => 0,
                'inteiro_4' => 0,
                'inteiro_5' => 0,
                'inteiro_6' => 0,
                'inteiro_7' => 0,
                'inteiro_8' => 0,
                'inteiro_9' => 0,
                'inteiro_10' => 0,
                'inteiro_11' => 0,
                'inteiro_12' => 0,
                'inteiro_total' => 0,
                'inteiro_total_parcial' => 0,
            ],
            'ano_codigo' => $ano,
            'tipo_produto_codigo' => $fields['tipo_produto'],
            'estabelecimentos' => '',
        ];

        $query_orcamento = OrcamentoCompra::select();
        $query_orcamento->whereBetween('data', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        if(!isset($fields['caixa'])){
            $query_orcamento->where('modo', 'competencia');
        }else{
            $query_orcamento->where('modo', 'fluxo_caixa');
        }
        $result_orcamentos = $query_orcamento->get();

        $total_orcamento_compras = 0;
        foreach ($result_orcamentos as $orcamento) {
            $total = 0;
            $data_mes = Carbon::parse($orcamento->data);
            $liberado = false;
            if (empty($fields['tipo_produto'])) {
                if(in_array($orcamento->tipo, ['nacional', 'importado', 'uso_consumo'])){
                    $liberado = true;
                }
            } else if ($fields['tipo_produto'] == 'nacional') {
                if(in_array($orcamento->tipo, ['nacional'])){
                    $liberado = true;
                }
            } else if ($fields['tipo_produto'] == 'importado') {
                if(in_array($orcamento->tipo, ['importado'])){
                    $liberado = true;
                }
            } else {
                if(in_array($orcamento->tipo, ['uso_consumo'])){
                    $liberado = true;
                }
            }

            if($liberado){
                $retorno['orcamento_compras']["inteiro_" . $data_mes->month] += $orcamento->valor;
                $total_orcamento_compras += $orcamento->valor;

                if ($mes_atual > $data_mes->month) {
                    $retorno['orcamento_compras']['inteiro_total_parcial'] += $orcamento->valor;
                }
            }
        }

        $retorno['orcamento_compras']['inteiro_total'] = $total_orcamento_compras;

        $query_compras = ComprasNasajon::select('id_nota', 'numero_pedido',  'estabelecimento', 'previsao_entrega', 'situacao', DB::RAW('sum(quantidade_restante * preco_compra_unitario) as preco_compra_total'));
        $query_compras->whereBetween('previsao_entrega', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        $query_compras->whereIn('situacao', ['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado']);
        $query_compras->whereNotIn('situacao_item', ['Cancelado']);
        $query_compras->whereNotIn('estabelecimento', $this->estabelecimentos_particular);
        if (!empty($fields['tipo_produto'])) {
            if ($fields['tipo_produto'] == 'nacional') {
                $query_compras->whereNotIn('estabelecimento', ['03']);
            } else if ($fields['tipo_produto'] == 'importado') {
                $query_compras->where('estabelecimento', '03');
            } else {
                $query_compras->whereDoesntHave('produtoNasajon');
            }
        }
        $query_compras->groupBy('id_nota', 'numero_pedido',  'estabelecimento', 'previsao_entrega', 'situacao');

        $result_compras = $query_compras->get();

        $compras_importadas = [];
        $compras_parceladas = [];
        foreach ($result_compras as $compras) {

            if (isset($fields['caixa'])) {

            

                $condicoesPagamentoNasajon =  CondicoesPagamentoNasajon::select('parcelamento')->where('id_docfis', $compras->id_nota);
                $resultCondicao = $condicoesPagamentoNasajon->first();
                if (!empty($resultCondicao)) {
                    $parcelasParcelamentoNasajon =  ParcelasParcelamentoNasajon::select('quantidadediapagamento', 'percentualpagamento')->Where('parcelamento', $resultCondicao->parcelamento);
                    $resultParcelamento = $parcelasParcelamentoNasajon->get();
                    $i = 1;
                    foreach ($resultParcelamento as $compraParcelada) {
                        $datePagamento = Carbon::createFromFormat('Y-m-d', $compras->previsao_entrega)->addDays($compraParcelada->quantidadediapagamento);

                        if ($ano ==  $datePagamento->year) {
                            $compras_parceladas[] = [
                                'numero_pedido' => $compras->numero_pedido,
                                'estabelecimento' => $compras->estabelecimento,
                                'data_vencimento' => $datePagamento,
                                'preco_compra_parcela' => ($compras->preco_compra_total * $compraParcelada->percentualpagamento) / 100,
                                'parcela' => $i++,

                            ];
                        }
                    }
                } else {

                    $compras_parceladas[] = [
                        'numero_pedido' => $compras->numero_pedido,
                        'estabelecimento' => $compras->estabelecimento,
                        'data_vencimento' => $compras->previsao_entrega,
                        'preco_compra_parcela' =>   $compras->preco_compra_total,
                        'parcela' => 1,

                    ];
                }
            }

            $compras_importadas[] = [
                'numero_pedido' => $compras->numero_pedido,

            ];
        }

        $importados = Importacao::select('pedido_compras', 'id');
        $importados->whereIn('pedido_compras',  $compras_importadas);

        $result_importados =  $importados->get();

        if (isset($fields['caixa'])) {
            $total_compras_planejada = 0;

            foreach ($compras_parceladas as $compraParcela) {

                $compra_total = $compraParcela['preco_compra_parcela'];

                $id_importado = $result_importados->firstWhere('pedido_compras', $compraParcela['numero_pedido']);
                if (!empty($id_importado)) {
                    $compra_total =  $compra_total * 1.65;
                    $id_importado = $id_importado->id;
                }
                $data_mes = Carbon::parse($compraParcela['data_vencimento']);

                $retorno['compras_planejadas']["inteiro_" . $data_mes->month] += intval($compraParcela['estabelecimento']) == 3 ?   $compra_total * $cotacaoDoDiaObj->valor :  $compra_total;


                $total_compras_planejada += intval($compraParcela['estabelecimento']) == 3 ?   $compra_total * $cotacaoDoDiaObj->valor :   $compra_total;

                if ($mes_atual > $data_mes->month) {
                    $retorno['compras_planejadas']['inteiro_total_parcial'] += intval($compraParcela['estabelecimento']) == 3 ?  $compra_total * $cotacaoDoDiaObj->valor :   $compra_total;
                }
            }

            $retorno['compras_planejadas']['inteiro_total'] = $total_compras_planejada;
        } else {

            $total_compras_planejada = 0;
            foreach ($result_compras as $compras) {
                $compra_total = $compras->preco_compra_total;
                $id_importado = $result_importados->firstWhere('pedido_compras', $compras->numero_pedido);
                if (!empty($id_importado)) {
                    $compra_total = $compras->preco_compra_total * 1.65;
                    $id_importado = $id_importado->id;
                }
                $data_mes = Carbon::parse($compras->previsao_entrega);
                $retorno['compras_planejadas']["inteiro_" . $data_mes->month] += intval($compras->estabelecimento) == 3 ?   $compra_total * $cotacaoDoDiaObj->valor :  $compra_total;


                $total_compras_planejada += intval($compras->estabelecimento) == 3 ?   $compra_total * $cotacaoDoDiaObj->valor :   $compra_total;

                if ($mes_atual > $data_mes->month) {
                    $retorno['compras_planejadas']['inteiro_total_parcial'] += intval($compras->estabelecimento) == 3 ?  $compra_total * $cotacaoDoDiaObj->valor :   $compra_total;
                }
            }

            $retorno['compras_planejadas']['inteiro_total'] = $total_compras_planejada;
        }

        $query_titulos = TitulosAPagarNasajon::select('Fornecedor', DB::Raw('"Data do Vencimento" AS vencimento,"Data da Baixa","Situação do Título" as situacao, case 
                   when "Situação do Título" = \'Em Débito\' then SUM("Valor Líquido" - "Valor da Baixa")   else  SUM("Valor Líquido") end as valor_total, SUM("Valor") as "Valor",SUM("Valor da Baixa") as "Valor da Baixa"'))
            ->where('Data do Vencimento', '!=', null)
            ->whereIn('Situação do Título', ['Aberto', 'Em Débito', 'Quitado'])
            ->whereBetween(DB::Raw('case 
                when "Data da Baixa" is null then
                    "Data do Vencimento"
                else
                    "Data da Baixa"
                end'), [$primeiro_dia_do_ano, $ultimo_dia_do_ano])
            ->whereNotIn('Estabelecimento', $this->estabelecimentos_particular)
            ->groupBy('Fornecedor', 'Data do Vencimento', 'Data da Baixa', 'Situação do Título');
        $result_titulos = $query_titulos->get();

        $total_titulos_caixa_aberto = 0;
        $total_titulos_caixa_fechado = 0;
        foreach ($result_titulos as $titulo) {
            $data_mes = Carbon::parse($titulo->vencimento);

            $titulo_total = $titulo->valor_total;
            $retorno['titulos_caixa_total']["inteiro_" . $data_mes->month] +=  $titulo_total;
            if ($mes_atual > $data_mes->month) {
                $retorno['titulos_caixa_total']['inteiro_total_parcial'] +=   $titulo_total;
            }

            if ($titulo->situacao === 'Aberto' || $titulo->situacao === 'Em Débito') {
                $retorno['titulos_caixa_aberto']["inteiro_" . $data_mes->month] +=   $titulo_total;

                $total_titulos_caixa_aberto +=    $titulo_total;

                if ($mes_atual > $data_mes->month) {
                    $retorno['titulos_caixa_aberto']['inteiro_total_parcial'] +=   $titulo_total;
                }
            } else {
                $retorno['titulos_caixa_fechado']["inteiro_" . $data_mes->month] +=   $titulo_total;

                $total_titulos_caixa_fechado +=   $titulo_total;

                if ($mes_atual > $data_mes->month) {
                    $retorno['titulos_caixa_fechado']['inteiro_total_parcial'] +=    $titulo_total;
                }
            }
        }

        $retorno['titulos_caixa_aberto']['inteiro_total'] = $total_titulos_caixa_aberto;
        $retorno['titulos_caixa_fechado']['inteiro_total'] = $total_titulos_caixa_fechado;
        $retorno['titulos_caixa_total']['inteiro_total'] = $total_titulos_caixa_fechado + $total_titulos_caixa_aberto;

        $query_compras_realizada = NotasEntradasNasajon::select('Identificador Documento', DB::Raw('"Data de Entrada" as data_entrada, "Valor do Documento" as valor'));
        $query_compras_realizada->whereBetween('Data de Entrada', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        $query_compras_realizada->whereNotIn('Estabelecimento', $this->estabelecimentos_particular);
        if (!empty($fields['tipo_produto'])) {
            if ($fields['tipo_produto'] == 'nacional') {
                $query_compras_realizada->whereNotIn('Estabelecimento', ['03']);
            } else if ($fields['tipo_produto'] == 'importado') {
                $query_compras_realizada->where('Estabelecimento', '03');
            } else {
                $query_compras_realizada->whereHas('primeiroItemNota', function ($query_compras_realizada) {
                    $query_compras_realizada->whereDoesntHave('produtoDetalhesNasajon');
                });
            }
        }
        $query_compras_realizada->whereHas('nota_entrada');

        $result_compras_realizada = $query_compras_realizada->get();


        $total_compras_realizadas = 0;
        foreach ($result_compras_realizada as $compras_realizada) {
            $data_mes = Carbon::parse($compras_realizada->data_entrada);
            $retorno['compras_realizadas']["inteiro_" . $data_mes->month] += $compras_realizada->valor;

            $total_compras_realizadas += $compras_realizada->valor;

            if ($mes_atual > $data_mes->month) {
                $retorno['compras_realizadas']['inteiro_total_parcial'] += $compras_realizada->valor;
            }
        }
        $retorno['compras_realizadas']['inteiro_total'] = $total_compras_realizadas;

        foreach ($retorno['orcamento_compras'] as $index => $valor) {
            if (substr_count($index, "inteiro") !== 0) {
                $retorno['compras_diferenca'][$index] = empty($valor) ? '' : parserValor(((($retorno['compras_planejadas'][$index] + $retorno['compras_realizadas'][$index]) / $valor) - 1) * 100) . "%";
            }
        }
      
     

        $retorno = $this->ajusteArrayParaValores($retorno);
        $retorno['response']=  $this->estabelecimentos_particular;

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $retorno
        ], 200);
    }
    private function ajusteArrayParaValores($array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = $this->ajusteArrayParaValores($value);
                } else {
                    if (is_numeric($value)) {
                        if (substr_count($key, "codigo") === 0) {
                            if (substr_count($key, "porcetagem") === 0) {
                                if (substr_count($key, "inteiro") === 0) {
                                    $array[$key] = empty($value) ? '' : parserValor($value);
                                } else {
                                    $array[$key] = empty($value) ? '' : parserValorInteiro($value);
                                }
                            } else {
                                $array[$key] = empty($value) ? '' : parserValor($value) . '%';
                            }
                        }
                    } else {
                        $array[$key] = $value;
                    }
                }
            }
        }
        return $array;
    }

    public function modalComprasDetalhes(Request $request)
    {

        $fields = $request->only('ano', 'mes', 'tipo', 'tipo_produto');

        $estabelecimentos = returnEmpresasNasajonView();

        $cotacaoDoDiaObj = Cotacoes::wherehas('moeda', function ($query) {
            $query->where('codigo', '220');
        })
            ->orderBy('data', 'desc')->first();

        if (empty($fields['mes'])) {
            $primeiro_dia_do_mes = Carbon::parse($fields['ano'] . "-01-01")->setTime(0, 0, 0);
            $ultimo_dia_do_mes = Carbon::parse($fields['ano'] . "-12-31")->setTime(23, 59, 59);
        } else if ($fields['mes'] == 'parcial') {
            $primeiro_dia_do_mes = Carbon::parse($fields['ano'] . "-01-01")->setTime(0, 0, 0);
            $ultimo_dia_do_mes = Carbon::now()->subMonth()->setTime(23, 59, 59)->lastOfMonth();
        } else {
            $primeiro_dia_do_mes = Carbon::parse($fields['ano'] . "-" . $fields['mes'] . "-01")->setTime(0, 0, 0)->firstOfMonth();
            $ultimo_dia_do_mes = Carbon::parse($fields['ano'] . "-" . $fields['mes'] . "-01")->setTime(23, 59, 59)->lastOfMonth();
        }

        $linhas = [];
        $total = 0;
        $total_quantidade = [
            "comprada" => 0,
            "recebido" => 0,
            "restante" => 0,
        ];

        if ($fields['tipo'] == 'planejada') {
            $query_compras = ComprasNasajon::select('id_nota', 'estabelecimento', 'numero_pedido', 'fornecedor_nome', 'data_compra', 'previsao_entrega', 'situacao', DB::RAW('sum(quantidade_restante * preco_compra_unitario) as preco_compra_total, sum(quantidade) as quantidade_comprada, sum(quantidade - quantidade_restante) as quantidade_recebido, sum(quantidade_restante) as quantidade_restante'));
            $query_compras->whereBetween('previsao_entrega', [$primeiro_dia_do_mes, $ultimo_dia_do_mes]);
            $query_compras->whereIn('situacao', ['Aberto', 'Aguardando Documento', 'Parcialmente Liquidado']);
            $query_compras->whereNotIn('situacao_item', ['Cancelado']);
            $query_compras->whereNotIn('estabelecimento', $this->estabelecimentos_particular);
            if (!empty($fields['tipo_produto'])) {
                if ($fields['tipo_produto'] == 'nacional') {
                    $query_compras->whereNotIn('estabelecimento', ['03']);
                } else if ($fields['tipo_produto'] == 'importado') {
                    $query_compras->where('estabelecimento', '03');
                } else {
                    $query_compras->whereDoesntHave('produtoNasajon');
                }
            }
            $query_compras->groupBy('id_nota', 'estabelecimento', 'numero_pedido', 'fornecedor_nome', 'data_compra', 'previsao_entrega', 'situacao');
            $result_compras = $query_compras->get();

            $compras_importadas = [];

            foreach ($result_compras as $compras) {
                $compras_importadas[] = [
                    'numero_pedido' => $compras->numero_pedido,
                ];
            }

            $importados = Importacao::select('pedido_compras', 'id');
            $importados->whereIn('pedido_compras',  $compras_importadas);

            $result_importados =  $importados->get();

            foreach ($result_compras as $compras) {
                $compra_total = $compras->preco_compra_total;

                $id_importado = $result_importados->firstWhere('pedido_compras', $compras->numero_pedido);
                $importado = false;
                if (!empty($id_importado)) {
                    $compra_total = $compras->preco_compra_total * 1.65;
                    $id_importado = $id_importado->id;
                    $importado = true;
                }
                $linhas[] = [
                    "id_pedido" => encrypt($compras->id_nota),
                    "estabelecimento_codigo" => $compras->estabelecimento,
                    "estabelecimento" => $estabelecimentos[intval($compras->estabelecimento)],
                    "numero_pedido" => $compras->numero_pedido,
                    "id_importado" =>  encrypt($id_importado),
                    "importado" =>  $importado,
                    "fornecedor" => $compras->fornecedor_nome,
                    "data_compra" => parserData($compras->data_compra),
                    "data_entrega" => parserData($compras->previsao_entrega),
                    "valor" => intval($compras->estabelecimento) == 3 ? parserValor($compra_total * $cotacaoDoDiaObj->valor) : parserValor($compra_total),
                    "quantidade_comprada" => empty(floatval($compras->quantidade_comprada)) ? '' : parserQtd($compras->quantidade_comprada),
                    "quantidade_recebido" => empty(floatval($compras->quantidade_recebido)) ? '' : parserQtd($compras->quantidade_recebido),
                    "quantidade_restante" => empty(floatval($compras->quantidade_restante)) ? '' : parserQtd($compras->quantidade_restante),
                ];

                $total_quantidade["comprada"] += empty(floatval($compras->quantidade_comprada)) ? 0 : floatval($compras->quantidade_comprada);
                $total_quantidade["recebido"] += empty(floatval($compras->quantidade_recebido)) ? 0 : floatval($compras->quantidade_recebido);
                $total_quantidade["restante"] += empty(floatval($compras->quantidade_restante)) ? 0 : floatval($compras->quantidade_restante);
                $total += intval($compras->estabelecimento) == 3 ? $compra_total * $cotacaoDoDiaObj->valor : $compra_total;
            }
        } else {
            $query_compras_realizada = NotasEntradasNasajon::select('Identificador Documento', DB::Raw('"Número do Documento" as nota_numero, "Data de Entrada" as data_entrada, "Valor do Documento" as valor, "Estabelecimento" as estabelecimento,"Nome do Fornecedor" as nome_fornecedor'));
            $query_compras_realizada->whereBetween('Data de Entrada', [$primeiro_dia_do_mes, $ultimo_dia_do_mes]);
            $query_compras_realizada->whereNotIn('Estabelecimento', $this->estabelecimentos_particular);
            $query_compras_realizada->whereHas('nota_entrada');
            if (!empty($fields['tipo_produto'])) {
                if ($fields['tipo_produto'] == 'nacional') {
                    $query_compras_realizada->whereNotIn('Estabelecimento', ['03']);
                } else if ($fields['tipo_produto'] == 'importado') {
                    $query_compras_realizada->where('Estabelecimento', '03');
                } else {
                    $query_compras_realizada->whereHas('primeiroItemNota', function ($query_compras_realizada) {
                        $query_compras_realizada->whereDoesntHave('produtoDetalhesNasajon');
                    });
                }
            }
            $query_compras_realizada->with(['nota_entrada.pedido' => function ($query) {
                $query->select('id_nota', 'estabelecimento', 'numero_pedido', 'data_compra', 'previsao_entrega', 'situacao', DB::RAW('sum(quantidade_restante * preco_compra_unitario) as preco_compra_total, sum(quantidade) as quantidade_comprada, sum(quantidade - quantidade_restante) as quantidade_recebido, sum(quantidade_restante) as quantidade_restante'));
                $query->whereNotIn('situacao_item', ['Cancelado']);
                $query->groupBy('id_nota', 'estabelecimento', 'numero_pedido', 'data_compra', 'previsao_entrega', 'situacao');
            }]);
            $result_compras_realizada = $query_compras_realizada->get();
            $importado = false;
            foreach ($result_compras_realizada as $compras_realizada) {
                foreach ($compras_realizada->nota_entrada as $pedido) {
                    $linhas[] = [
                        "id_nota" => $pedido->id_nota,
                        "id_pedido" => empty($pedido->pedido) ? '' : encrypt($pedido->pedido->id_nota),
                        "estabelecimento_codigo" => $compras_realizada->estabelecimento,
                        "estabelecimento" => $estabelecimentos[intval($compras_realizada->estabelecimento)],
                        "numero_pedido" => empty($pedido->pedido) ? '' : $pedido->pedido->numero_pedido,
                        "importado" =>  $importado,
                        "fornecedor" => empty($pedido->pedido) ? '' : $compras_realizada->nome_fornecedor,
                        "data_compra" => empty($pedido->pedido) ? '' : parserData($pedido->pedido->data_compra),
                        "data_entrega" => empty($pedido->pedido) ? '' : parserData($compras_realizada->data_entrada),
                        "valor" => parserValor($compras_realizada->valor),
                        "quantidade_comprada" => empty($pedido->pedido) ? '' : parserQtd($pedido->pedido->quantidade_comprada),
                        "quantidade_recebido" => empty($pedido->pedido) ? '' : parserQtd($pedido->pedido->quantidade_recebido),
                        "quantidade_restante" => empty($pedido->pedido) ? '' : parserQtd($pedido->pedido->quantidade_restante),
                        "nota_codigo" => $compras_realizada->nota_numero,
                    ];
                }

                $total_quantidade["comprada"] += empty($pedido->pedido) ? 0 : floatval($pedido->pedido->quantidade_comprada);
                $total_quantidade["recebido"] += empty($pedido->pedido) ? 0 : floatval($pedido->pedido->quantidade_recebido);
                $total_quantidade["restante"] += empty($pedido->pedido) ? 0 : floatval($pedido->pedido->quantidade_restante);
                $total += $compras_realizada->valor;
            }
        }

        $total = empty($total) ? '' : parserValor($total);
        $total_quantidade["comprada"] = empty($total_quantidade["comprada"]) ? '' : parserQtd($total_quantidade["comprada"]);
        $total_quantidade["recebido"] = empty($total_quantidade["recebido"]) ? '' : parserQtd($total_quantidade["recebido"]);
        $total_quantidade["restante"] = empty($total_quantidade["restante"]) ? '' : parserQtd($total_quantidade["restante"]);

        return view("programs.acompanhamento_orcamentario.modal.compras_detalhes")->with(['linhas' => $linhas, 'total' => $total, 'tipo' => $fields['tipo'], 'total_quantidade' => $total_quantidade]);
    }

}
