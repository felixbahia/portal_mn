<?php

namespace App\Http\Controllers;

use Auth;

use App\Preco;
use App\Cotacoes;

use Carbon\Carbon;
use App\Importacao;
use App\Movimentacao;
use App\BancoPrevisto;
use App\ClienteNasajon;
use App\ComprasNasajon;
use App\ImportacaoCusto;
use App\OrcamentoCompra;
use App\ProdutosEstoque;
use App\DespesaPlanejada;
use App\FaturamentoOnline;
use App\ContaContabilSaldo;
use App\FaturamentoPrevisto;
use App\TitulosPagosNasajon;
use Illuminate\Http\Request;
use App\NotasEntradasNasajon;
use App\ProdutoEspecificacao;
use App\TitulosAPagarNasajon;
use App\TituloPagamentoNasajon;
use App\TitulosEmAbertoNasajon;
use App\CondicoesPagamentoNasajon;
use Illuminate\Support\Facades\DB;
use App\ParcelasParcelamentoNasajon;
use App\MovimentoAgrupadoOperacaoMes;
use App\FaturamentoPrevistoFluxoCaixa;
use App\NotasImportadasEntradasTitulo;
use App\PedidosPrePago;
use App\TituloAPagar;
use App\PedidoComprasAbertoTituloFuturo;

class AcompanhamentoOrcamentarioController extends Controller
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
    private $estabelecimentos_particular = ['00', '20','25', '30', 'MARCELO', 'MN_NAMURA', 'NAMURA_NN','NELSON'];

    private $modos = [
        'competencia' => 'Competência',
        'fluxo_caixa' => 'Fluxo de Caixa'
    ];

    public function index(Request $request){

        if (Auth::user()->hasPermissionTo("programas App\AcompanhamentoOrcamentario") === false) {
            return abort(403);
        }
        $request->session()->flash('model', 'App\AcompanhamentoOrcamentario');
        $PedidoComprasAbertoTituloFuturoController = new PedidoComprasAbertoTituloFuturoController;

        $tituloAPagarObj =  Carbon::parse(TituloAPagar::select()->max('updated_at'));

        $horario = "Última Atualização: ".$tituloAPagarObj->format('d/m H:i');
        
        $tipo_produto = [
            'nacional' => "Nacional",
            'importado' => "Importado",
            'uso_consumo' => "Uso Consumo",
        ];

        $mes_anterior = Carbon::now()->subMonth()->format('m/Y');

        $PedidoComprasAbertoTituloFuturoController = new PedidoComprasAbertoTituloFuturoController;
        $PedidoComprasAbertoTituloFuturoController->verificarNaoLancado();

        return view("programs.acompanhamento_orcamentario.index")->with(['tipo_produto' => $tipo_produto, 'mes_anterior' => $mes_anterior, 'horario' => $horario, 'modos' => $this->modos]);
    }

    public function filtro(Request $request){
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);

        $fields = $request->only('ano', 'tipo_produto', 'margem_venda', 'prepago', 'caixa', 'modo');

        $ano = intval($fields['ano']);
        $data_atual = Carbon::now();
        if ($ano != $data_atual->year) {
            $mes_atual = 13;
        } else {
            $mes_atual = $data_atual->month;
        }
        $primeiro_dia_do_ano = Carbon::parse($ano . "-01-01")->setTime(0, 0, 0);
        $ultimo_dia_do_ano = Carbon::parse($ano . "-12-31")->setTime(23, 59, 59);
        $this->atualizarFaturamentoValorProdutoNacionalImportado();
        $cotacaoDoDiaObj = Cotacoes::wherehas('moeda', function ($query) {
            $query->where('codigo', '220');
        })
            ->orderBy('data', 'desc')->first();

        $margem_venda = empty($fields['margem_venda']) ? 32.00 : parserNumber($fields['margem_venda']);

        $retorno = [
            'faturamento_previsto' => [
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
                'flag_1' => false,
                'flag_2' => false,
                'flag_3' => false,
                'flag_4' => false,
                'flag_5' => false,
                'flag_6' => false,
                'flag_7' => false,
                'flag_8' => false,
                'flag_9' => false,
                'flag_10' => false,
                'flag_11' => false,
                'flag_12' => false,
                'inteiro_total' => 0,
                'inteiro_total_parcial' => 0,
            ],
            'faturamento_realizado' => [
                'descricao' => 'Realizado',
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
            'faturamento_previsto_caixa' => [
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
            'faturamento_caixa_aberto' => [
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
            'faturamento_caixa_fechado' => [
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
            'faturamento_caixa' => [
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
            'faturamento_diferenca' => [
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
            'compras_caixa_titulos_nao_lancados' => [
                'descricao' => 'Títulos Não Lançados',
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
                'filtro_ano' => encrypt($fields['ano'])
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
            'faturamento_compras_planejado' => [
                'descricao' => 'Previsto %',
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
            'faturamento_compras_realizado' => [
                'descricao' => 'Realizado %',
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
            'despesa_planejada' => [
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
            'despesa_realizada' => [
                'descricao' => 'Realizado',
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
                'conta_codigo' => '5',
                'inteiro_total_parcial' => 0,
            ],
            'despesa_diferenca' => [
                'descricao' => 'Diferenca %',
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
            'vendas_menos_compras_menos_despesas_previsto' => [
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
            'vendas_menos_compras_menos_despesas_realizado' => [
                'descricao' => 'Realizado',
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
            'vendas_menos_compras_menos_despesas_previsao' => [
                'descricao' => 'Previsão',
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
            'vendas_menos_compras_menos_despesas_diferenca' => [
                'descricao' => 'Diferenca %',
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
            'estoque_entrada' => [
                'descricao' => 'Entrada',
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
                'outros_anos' => 0,
            ],
            'estoque_saida' => [
                'descricao' => 'Saída',
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
                'outros_anos' => 0,
            ],
            'estoque_final' => [
                'descricao' => 'Final',
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
            'estoque_previsao' => [
                'descricao' => 'Previsão',
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
            'banco_previsto' => [
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
            'banco_realizado' => [
                'descricao' => 'Realizado',
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
            'banco_diferenca' => [
                'descricao' => 'Diferenca %',
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
            'dados_faturamento_previsto_fluxo_caixa' => [
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
            ],
            'dados_faturamento_previsto_fluxo_caixa_titulo' => [
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
            ],
            'dados_compras_previsto_fluxo_caixa' => [
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
            ],
            'dados_compras_previsto_fluxo_caixa_titulo' => [
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
            ],
            'pedidos_compras_abertos_titulos_futuros' => [
                'descricao' => 'Pedidos Abertos',
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
                'inteiro_total_parcial' => 0,
                'inteiro_total' => 0,
            ],
            'ano_codigo' => $ano,
            'tipo_produto_codigo' => $fields['tipo_produto'],
            'estabelecimentos' =>'',
        ];
    
        if($fields['modo'] == 'competencia'){
            $query_faturamento_previsto = FaturamentoPrevisto::select();
            $query_faturamento_previsto->whereBetween('data', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
            $result_faturamento_previsto = $query_faturamento_previsto->get();
    
            $total_faturamento_previsto = 0;
            foreach ($result_faturamento_previsto as $faturamento_previsto) {
                $total = 0;
                if (empty($fields['tipo_produto'])) {
                    $total = ($faturamento_previsto->nacional_valor + $faturamento_previsto->importado_valor);
                } else if ($fields['tipo_produto'] == 'nacional') {
                    $total = $faturamento_previsto->nacional_valor;
                } else if ($fields['tipo_produto'] == 'importado') {
                    $total = $faturamento_previsto->importado_valor;
                } else {
                    $total = ($faturamento_previsto->nacional_valor + $faturamento_previsto->importado_valor);
                }
    
                $retorno['faturamento_previsto']["inteiro_" . $faturamento_previsto->data->month] = $total;
                $total_faturamento_previsto += $total;
    
                if ($mes_atual > $faturamento_previsto->data->month) {
                    $retorno['faturamento_previsto']['inteiro_total_parcial'] += $total;
                }
            }
            $retorno['faturamento_previsto']['inteiro_total'] = $total_faturamento_previsto;
        }else{
            $query_faturamento_previsto = FaturamentoPrevistoFluxoCaixa::select();
            $query_faturamento_previsto->whereBetween('mes_ano', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
            $query_faturamento_previsto->orderBy('mes_ano');
            $result_faturamento_previsto = $query_faturamento_previsto->get();

            $total_faturamento_previsto = 0;

            $flag = false;
            foreach ($result_faturamento_previsto as $faturamento_previsto) {
                if ($fields['tipo_produto'] == 'nacional') {
                    $retorno['faturamento_previsto']["inteiro_" . $faturamento_previsto->mes_ano->month] = $faturamento_previsto->valor * 0.48;
                    $total_faturamento_previsto += $faturamento_previsto->valor * 0.48;

                    $retorno['dados_faturamento_previsto_fluxo_caixa']["inteiro_" . $faturamento_previsto->mes_ano->month] = $faturamento_previsto->mes_ano->format('Y-m-d');
                    $retorno['dados_faturamento_previsto_fluxo_caixa_titulo']["inteiro_" . $faturamento_previsto->mes_ano->month] = $faturamento_previsto->mes_ano->format('m/Y');

                    if ($mes_atual > $faturamento_previsto->mes_ano->month) {
                        $retorno['faturamento_previsto']['inteiro_total_parcial'] += $faturamento_previsto->valor * 0.48;
                    }
                } else if ($fields['tipo_produto'] == 'importado') {
                    $retorno['faturamento_previsto']["inteiro_" . $faturamento_previsto->mes_ano->month] = $faturamento_previsto->valor * 0.52;
                    $total_faturamento_previsto += $faturamento_previsto->valor * 0.52;

                    $retorno['dados_faturamento_previsto_fluxo_caixa']["inteiro_" . $faturamento_previsto->mes_ano->month] = $faturamento_previsto->mes_ano->format('Y-m-d');
                    $retorno['dados_faturamento_previsto_fluxo_caixa_titulo']["inteiro_" . $faturamento_previsto->mes_ano->month] = $faturamento_previsto->mes_ano->format('m/Y');

                    if ($mes_atual > $faturamento_previsto->mes_ano->month) {
                        $retorno['faturamento_previsto']['inteiro_total_parcial'] += $faturamento_previsto->valor * 0.52;
                    }
                } else {

                    /*if($flag == false){
                        $teste_existe = FaturamentoPrevisto::select();
                        $teste_existe->where('data', $faturamento_previsto->mes_ano);
                        $teste_existe = $teste_existe->first();

                        if(empty($teste_existe)){
                            $flag = true;

                            $retorno['faturamento_previsto']["flag_" . $faturamento_previsto->mes_ano->month] = true;
                        }
                    }else{
                        $retorno['faturamento_previsto']["flag_" . $faturamento_previsto->mes_ano->month] = true;
                    }*/

                    $retorno['faturamento_previsto']["inteiro_" . $faturamento_previsto->mes_ano->month] = $faturamento_previsto->valor;
                    $total_faturamento_previsto += $faturamento_previsto->valor;

                    $retorno['dados_faturamento_previsto_fluxo_caixa']["inteiro_" . $faturamento_previsto->mes_ano->month] = $faturamento_previsto->mes_ano->format('Y-m-d');
                    $retorno['dados_faturamento_previsto_fluxo_caixa_titulo']["inteiro_" . $faturamento_previsto->mes_ano->month] = $faturamento_previsto->mes_ano->format('m/Y');

                    if ($mes_atual > $faturamento_previsto->mes_ano->month) {
                        $retorno['faturamento_previsto']['inteiro_total_parcial'] += $faturamento_previsto->valor;
                    }
                }
                
            }
            $retorno['faturamento_previsto']['inteiro_total'] = $total_faturamento_previsto;
        }

        if($fields['modo'] == 'fluxo_caixa'){
            $faturas_notas = NotasImportadasEntradasTitulo::where(function($query){
                $query->whereNull('lancado')
                ->orWhere('lancado',false);
            })
            ->where('estabelecimento','<>','20')
            ->with(['notasEntradas'])
            ->whereHas('duplicatas',function($query) use ($primeiro_dia_do_ano,$ultimo_dia_do_ano){
                $query->whereBetween('vencimento',[$primeiro_dia_do_ano,$ultimo_dia_do_ano]);
            })
            ->whereDoesntHave('notaImportada.itensNotas',function($query){
                $cfop = ['2913','5202'];
                $query->whereIn('codigo_cfop',$cfop);
            })
            ->whereDoesntHave('notaImportada',function($query){
                $query->where('fornecedor_documento','34.279.857/0001-04');
                $query->orWhere('fornecedor_documento','57.142.978/0001-05');
            })
            ->with(['duplicatas' => function($query) use ($primeiro_dia_do_ano,$ultimo_dia_do_ano){
                $query->whereBetween('vencimento',[$primeiro_dia_do_ano,$ultimo_dia_do_ano]);
            }])
            ->get();

            $faturas_notas->each(function($query) use (&$retorno,$mes_atual){

                if(empty($query->notasEntradas)){
                    foreach($query->duplicatas as $value){
                        $retorno['compras_caixa_titulos_nao_lancados']["inteiro_" . $value->vencimento->month] += $value->valor;
                        $retorno['compras_caixa_titulos_nao_lancados']["inteiro_total"] += $value->valor;
                        if($mes_atual > $value->vencimento->month){
                            $retorno['compras_caixa_titulos_nao_lancados']["inteiro_total_parcial"] += $value->valor;
                        }
                    }
                }

            });
        }
        
        if (!empty($fields['tipo_produto'])) {
            if ($fields['tipo_produto'] == 'nacional') {
                $faturamento_calculo_realizado = "SUM( valor_produto_nacional )";
            } else if ($fields['tipo_produto'] == 'importado') {
                $faturamento_calculo_realizado = "SUM( valor_produto_importado )";
            } else {
                $faturamento_calculo_realizado = 'SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) - SUM( valor_troco )';
            }
        } else {
            $faturamento_calculo_realizado = 'SUM( valor_compra ) + SUM( valor_frete ) + SUM( valor_ipi ) - SUM( valor_troco )';
        }

        if (isset($fields['prepago'])) {
            $faturamento_calculo_realizado .= '+ SUM( valor_prepago )';
        }
        $total_faturamento_realizado = 0;
        if($fields['modo'] == 'competencia'){
            $query_faturamento_realizado = FaturamentoOnline::select('data', 'tipo_operacao', DB::Raw($faturamento_calculo_realizado . " as total"));
            $query_faturamento_realizado->whereBetween('data', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
            $query_faturamento_realizado->where(function ($query) {
                $query->orWhere("tipo_operacao", "ilike", "VENDA%");
                $query->orWhere("tipo_operacao", "ilike", "SIMPLESFATFUTURA");
                $query->orWhere("tipo_operacao", "ilike", "DEV%");
            });
            $query_faturamento_realizado->where("nasajon", true);
            $query_faturamento_realizado->groupBy('data', 'tipo_operacao');
            $result_faturamento_realizado = $query_faturamento_realizado->get();


            foreach ($result_faturamento_realizado as $faturamento_realizado) {
                $data_mes = Carbon::parse($faturamento_realizado->data);
                if (substr_count($faturamento_realizado->tipo_operacao, "DEV") === 0) {
                    $retorno['faturamento_realizado']["inteiro_" . $data_mes->month] += $faturamento_realizado->total;
                    $total_faturamento_realizado += $faturamento_realizado->total;
                    if ($mes_atual > $data_mes->month) {
                        $retorno['faturamento_realizado']['inteiro_total_parcial'] += $faturamento_realizado->total;
                    }
                } else {
                    $retorno['faturamento_realizado']["inteiro_" . $data_mes->month] -= $faturamento_realizado->total;
                    $total_faturamento_realizado -= $faturamento_realizado->total;
                    if ($mes_atual > $data_mes->month) {
                        $retorno['faturamento_realizado']['inteiro_total_parcial'] -= $faturamento_realizado->total;
                    }
                }
            }
            $retorno['faturamento_realizado']['inteiro_total'] = $total_faturamento_realizado;

            foreach ($retorno['faturamento_realizado'] as $index => $valor) {
                if (substr_count($index, "inteiro") !== 0) {
                    $retorno['faturamento_diferenca'][$index] = empty($retorno['faturamento_previsto'][$index]) ? '' : parserValor((($valor / $retorno['faturamento_previsto'][$index]) - 1) * 100) . "%";
                }
            }
            $retorno['faturamento_diferenca']['inteiro_total'] = empty($total_faturamento_previsto) ? '' : parserValor((($total_faturamento_realizado / $total_faturamento_previsto) - 1) * 100) . "%";
        } else {
            //

            $cnpj = $this->cnpjINtercompany();
            $query_faturamento_caixa = TitulosEmAbertoNasajon::select('vencimento', 'nota_numero', DB::Raw("sum(saldotitulo) as total"));
            if ($fields['tipo_produto'] == 'nacional' || $fields['tipo_produto']  == 'importado') {
                $query_faturamento_caixa->with(['detalhesFaturamentoOnline']);
            }            
            $query_faturamento_caixa->whereBetween('vencimento', [$primeiro_dia_do_ano, $ultimo_dia_do_ano])
                ->where('saldotitulo', '>', '0')
                ->whereNotIn("codigo", ['25', 'TREINAMENTO'])
                ->whereNotIn("cod_cliente", $cnpj);
            $query_faturamento_caixa->groupBy('vencimento', 'nota_numero');

            $query_faturamento_caixa_recebido = TitulosPagosNasajon::select('vencimento', 'documento_numero', DB::Raw("sum(valor_titulo) as total"));
            $query_faturamento_caixa_recebido->whereBetween('vencimento', [$primeiro_dia_do_ano, $ultimo_dia_do_ano])
                ->where('valor_titulo', '>', '0')
                  ->whereNotIn("codigo", ['25', 'TREINAMENTO'])
                ->whereNotIn("cod_cliente", $cnpj);
            $query_faturamento_caixa_recebido->groupBy('vencimento', 'documento_numero');

            $result_faturamento_caixa = $query_faturamento_caixa->get();

            $result_faturamento_caixa_recebido = $query_faturamento_caixa_recebido->get();


            $total_faturamento_caixa = 0;
            $total_faturamento_caixa_aberto = 0;
            $total_faturamento_caixa_fechado = 0;
            foreach ($result_faturamento_caixa as $faturamento_caixa) {
                $data_mes = Carbon::parse($faturamento_caixa->vencimento);
                if ($fields['tipo_produto'] == 'nacional' || $fields['tipo_produto']  == 'importado') {
                    if($fields['tipo_produto'] == 'nacional'){
                        $valor = empty($faturamento_caixa->detalhesFaturamentoOnline)? $faturamento_caixa->total : (((100 * $faturamento_caixa->detalhesFaturamentoOnline->valor_produto_nacional) / $faturamento_caixa->detalhesFaturamentoOnline->valor_compra) / 100) * $faturamento_caixa->total;
                    }else{
                        $valor = empty($faturamento_caixa->detalhesFaturamentoOnline)? 0 : (((100 * $faturamento_caixa->detalhesFaturamentoOnline->valor_produto_importado) / $faturamento_caixa->detalhesFaturamentoOnline->valor_compra) / 100) * $faturamento_caixa->total;
                    }
                    $total_faturamento_caixa_aberto +=    $valor;
                    $retorno['faturamento_caixa_aberto']["inteiro_" . $data_mes->month] += $valor;
    
                    if ($mes_atual > $data_mes->month) {
                        $retorno['faturamento_caixa_aberto']['inteiro_total_parcial'] += $valor;
                    }
                    $retorno['faturamento_caixa']["inteiro_" . $data_mes->month] += $valor;
                    $retorno['faturamento_realizado']["inteiro_" . $data_mes->month] += $valor;
                    $total_faturamento_caixa += $valor;
                    if ($mes_atual > $data_mes->month) {
                        $retorno['faturamento_caixa']['inteiro_total_parcial'] += $valor;
                        $retorno['faturamento_realizado']['inteiro_total_parcial'] += $valor;
                    }     
                }else{
                    $total_faturamento_caixa_aberto +=    $faturamento_caixa->total;
                    $retorno['faturamento_caixa_aberto']["inteiro_" . $data_mes->month] += $faturamento_caixa->total;
    
                    if ($mes_atual > $data_mes->month) {
                        $retorno['faturamento_caixa_aberto']['inteiro_total_parcial'] += $faturamento_caixa->total;
                    }
                    $retorno['faturamento_caixa']["inteiro_" . $data_mes->month] += $faturamento_caixa->total;
                    $retorno['faturamento_realizado']["inteiro_" . $data_mes->month] += $faturamento_caixa->total;
                    $total_faturamento_caixa += $faturamento_caixa->total;
                    if ($mes_atual > $data_mes->month) {
                        $retorno['faturamento_caixa']['inteiro_total_parcial'] += $faturamento_caixa->total;
                        $retorno['faturamento_realizado']['inteiro_total_parcial'] += $faturamento_caixa->total;
                    }
                }                
            }
            $retorno['faturamento_caixa_aberto']['inteiro_total'] = $total_faturamento_caixa_aberto;
            foreach ($result_faturamento_caixa_recebido as $faturamento_caixa) {
                $data_mes = Carbon::parse($faturamento_caixa->vencimento);
                if ($fields['tipo_produto'] == 'nacional' || $fields['tipo_produto']  == 'importado') {
                    if($fields['tipo_produto'] == 'nacional'){
                        $valor = 0.48 * $faturamento_caixa->total;
                    }else{
                        $valor = 0.52 * $faturamento_caixa->total;
                    }

                    $total_faturamento_caixa_fechado +=    $valor;

                    $retorno['faturamento_caixa_fechado']["inteiro_" . $data_mes->month] += $valor;
                    $retorno['faturamento_realizado']["inteiro_" . $data_mes->month] += $valor;
    
                    if ($mes_atual > $data_mes->month) {
                        $retorno['faturamento_caixa_fechado']['inteiro_total_parcial'] += $valor;
                        $retorno['faturamento_realizado']['inteiro_total_parcial'] += $valor;
                    }
                    $retorno['faturamento_caixa']["inteiro_" . $data_mes->month] += $valor;
                    $total_faturamento_caixa += $valor;
                    if ($mes_atual > $data_mes->month) {
                        $retorno['faturamento_caixa']['inteiro_total_parcial'] += $valor;
                    }
                }else{
                    $total_faturamento_caixa_fechado +=    $faturamento_caixa->total;

                    $retorno['faturamento_caixa_fechado']["inteiro_" . $data_mes->month] += $faturamento_caixa->total;
                    $retorno['faturamento_realizado']["inteiro_" . $data_mes->month] += $faturamento_caixa->total;
    
                    if ($mes_atual > $data_mes->month) {
                        $retorno['faturamento_caixa_fechado']['inteiro_total_parcial'] += $faturamento_caixa->total;
                        $retorno['faturamento_realizado']['inteiro_total_parcial'] += $faturamento_caixa->total;
                    }
                    $retorno['faturamento_caixa']["inteiro_" . $data_mes->month] += $faturamento_caixa->total;
                    $total_faturamento_caixa += $faturamento_caixa->total;
                    if ($mes_atual > $data_mes->month) {
                        $retorno['faturamento_caixa']['inteiro_total_parcial'] += $faturamento_caixa->total;
                    }
                }                
            }
            $retorno['faturamento_caixa_fechado']['inteiro_total'] =  $total_faturamento_caixa_fechado;
            $retorno['faturamento_caixa']['inteiro_total'] = $total_faturamento_caixa;
            $retorno['faturamento_realizado']['inteiro_total'] = $total_faturamento_caixa_fechado;

            if (isset($fields['prepago'])) {
                $query_aberto = PedidosPrePago::select();
                $query_aberto->whereBetween('created_at', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
                $result_aberto = $query_aberto->get();

                foreach ($result_aberto as $index => $valor) {
                    $data_mes = Carbon::parse($valor->created_at);
                    $total_faturamento_caixa_fechado +=    $valor->valor_pago;

                    $retorno['faturamento_caixa_fechado']["inteiro_" . $data_mes->month] += $valor->valor_pago;
                    $retorno['faturamento_realizado']["inteiro_" . $data_mes->month] += $valor->valor_pago;

                    if ($mes_atual > $data_mes->month) {
                        $retorno['faturamento_caixa_fechado']['inteiro_total_parcial'] += $valor->valor_pago;
                        $retorno['faturamento_realizado']['inteiro_total_parcial'] += $valor->valor_pago;
                    }
                    $retorno['faturamento_caixa']["inteiro_" . $data_mes->month] += $valor->valor_pago;
                    $total_faturamento_caixa += $valor->valor_pago;
                    if ($mes_atual > $data_mes->month) {
                        $retorno['faturamento_caixa']['inteiro_total_parcial'] += $valor->valor_pago;
                    }
                }
            }

            foreach ($retorno['faturamento_realizado'] as $index => $valor) {
                if (substr_count($index, "inteiro") !== 0) {
                    $retorno['faturamento_diferenca'][$index] = empty($retorno['faturamento_previsto'][$index]) ? '' : parserValor((($valor / $retorno['faturamento_previsto'][$index]) - 1) * 100) . "%";
                }
            }
            $retorno['faturamento_diferenca']['inteiro_total'] = empty($total_faturamento_previsto) ? '' : parserValor((($total_faturamento_caixa / $total_faturamento_previsto) - 1) * 100) . "%";
        }
        
        $query_orcamento = OrcamentoCompra::select();
        $query_orcamento->whereBetween('data', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        if($fields['modo'] == 'competencia'){
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

            if($fields['modo'] == 'competencia'){
                $condicoesPagamentoNasajon =  CondicoesPagamentoNasajon::select('parcelamento')->where('id_docfis', $compras->id_nota);
                $resultCondicao = $condicoesPagamentoNasajon->first();
                if (!empty($resultCondicao)) {
                    $parcelasParcelamentoNasajon =  ParcelasParcelamentoNasajon::select('quantidadediapagamento', 'percentualpagamento')->Where('parcelamento', $resultCondicao->parcelamento);
                    $resultParcelamento = $parcelasParcelamentoNasajon->get();
                    $i = 1;
                    foreach ($resultParcelamento as $compraParcelada) {
                        try{
                            $datePagamento = Carbon::createFromFormat('Y-m-d', $compras->previsao_entrega)->addDays($compraParcelada->quantidadediapagamento);
                        }catch(\Exception $e){
                            $datePagamento = Carbon::now();
                        }
            
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

        if($fields['modo'] == 'competencia'){
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
        $cnpj = $this->cnpjINtercompany();
        
        $query_titulos = TituloAPagar::select(DB::Raw('estabelecimento_codigo,tipo,titulo_vencimento,titulo_situacao,
            case 
                when titulo_situacao = \'Em Débito\' then 
                    SUM(titulo_valor_liquido - titulo_valor_baixa)   
                else  
                    SUM(titulo_valor_liquido) 
            end as valor_total, 
            SUM(titulo_valor) as valor,
            SUM(titulo_valor_baixa) as titulo_valor_baixa'))
            ->where('titulo_vencimento', '!=', null)
            ->whereIn('titulo_situacao', ['Aberto', 'Em Débito', 'Quitado'])
            ->whereBetween(DB::Raw('case 
                when titulo_data_baixa is null then
                titulo_vencimento
                else
                    titulo_data_baixa
                end'), [$primeiro_dia_do_ano, $ultimo_dia_do_ano])
            ->whereNotIn("fornecedor_codigo", $cnpj)
            ->groupBy('estabelecimento_codigo', 'tipo', 'titulo_situacao', 'titulo_vencimento');

        $result_titulos = $query_titulos->get();

        $total_titulos_caixa_aberto = 0;
        $total_titulos_caixa_fechado = 0;
        foreach ($result_titulos as $titulo) {
            if ($titulo->tipo == 'compras' || $titulo->tipo == 'compras_internacional') {
                $liberado = false;
                if ($fields['tipo_produto'] == 'nacional') {
                    $liberado = $titulo->estabelecimento_codigo === '03'? false : true;
                }else if ($fields['tipo_produto'] == 'importado') {
                    $liberado = $titulo->estabelecimento_codigo === '03'? true : false;
                }else if ($fields['tipo_produto'] == 'uso_consumo') {
                    $liberado = false;
                }else{
                    
                    $liberado = true;
                }

                if($liberado){
                    $data_mes = Carbon::parse($titulo->titulo_vencimento);

                    $titulo_total = $titulo->valor_total;
                    $retorno['titulos_caixa_total']["inteiro_" . $data_mes->month] +=  $titulo_total;
                    if ($mes_atual > $data_mes->month) {
                        $retorno['titulos_caixa_total']['inteiro_total_parcial'] +=   $titulo_total;
                    }
    
                    if ($titulo->titulo_situacao === 'Aberto' || $titulo->titulo_situacao === 'Em Débito') {
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
                if($fields['modo'] == 'competencia'){
                    $retorno['compras_diferenca'][$index] = empty($valor) ? '' : parserValor(((($retorno['compras_planejadas'][$index] + $retorno['compras_realizadas'][$index]) / $valor) - 1) * 100) . "%";
                }else{
                    $retorno['compras_diferenca'][$index] = empty($valor) ? '' : parserValor(((($retorno['titulos_caixa_total'][$index]) / $valor) - 1) * 100) . "%";
                }
            }
        }
        $retorno['faturamento_diferenca']['inteiro_total'] = empty($total_faturamento_previsto) ? '' : parserValor((($total_faturamento_realizado / $total_faturamento_previsto) - 1) * 100) . "%";

        foreach ($retorno['faturamento_previsto'] as $index => $valor) {
            if ($index != 'descricao' && substr_count($index, "flag_") === 0) {
                $retorno['faturamento_compras_planejado'][$index] = empty($valor) ? '' : parserValor((($retorno['orcamento_compras'][$index] / $valor)) * 100) . "%";
            }
        }

        foreach ($retorno['faturamento_realizado'] as $index => $valor) {
            if ($index != 'descricao') {
                $retorno['faturamento_compras_realizado'][$index] = empty($valor) ? '' : parserValor((($retorno['compras_realizadas'][$index] / $valor)) * 100) . "%";
            }
        }

        $total_despesa_planejada = 0;
        $testes = [];
        foreach ($result_orcamentos as $despesa_planejada) {
            $liberado = false;
            if(in_array($despesa_planejada->tipo, ['despesas'])){
                $liberado = true;
            }
            
            if($liberado){
                $retorno['despesa_planejada']["inteiro_" . $despesa_planejada->data->month] += $despesa_planejada->valor;
                $total_despesa_planejada += $despesa_planejada->valor;

                if ($mes_atual > $despesa_planejada->data->month) {
                    $retorno['despesa_planejada']['inteiro_total_parcial'] += $despesa_planejada->valor;
                }
            }
            
        }
        $retorno['despesa_planejada']['inteiro_total'] = $total_despesa_planejada;

        $total_despesa_realizada = 0;
        foreach ($result_titulos as $titulo) {
            if ($titulo->tipo == 'despesas') {
                $data_mes = Carbon::parse($titulo->titulo_vencimento);

                $total_despesa_realizada += $titulo->valor_total;
                $retorno['despesa_realizada']["inteiro_" . $data_mes->month] +=  $titulo->valor_total;
                if ($mes_atual > $data_mes->month) {
                    $retorno['despesa_realizada']['inteiro_total_parcial'] +=   $titulo->valor_total;
                }
            }
        }
        $retorno['despesa_realizada']['inteiro_total'] = $total_despesa_realizada;

        foreach ($retorno['despesa_realizada'] as $index => $valor) {
            if ($index != 'descricao') {
                $retorno['despesa_diferenca'][$index] = empty($retorno['despesa_planejada'][$index]) ? '' : parserValor((($valor / $retorno['despesa_planejada'][$index]) - 1) * 100) . "%";
            }
        }
        $retorno['despesa_diferenca']['inteiro_total'] = empty($total_despesa_planejada) ? '' : parserValor((($total_despesa_realizada / $total_despesa_planejada) - 1) * 100) . "%";

        $total_banco_previsto = 0;
        foreach ($result_orcamentos as $banco_previsto) {
            $liberado = false;
            if(in_array($banco_previsto->tipo, ['banco'])){
                $liberado = true;
            }
            
            if($liberado){
                $retorno['banco_previsto']["inteiro_" . $banco_previsto->data->month] += $banco_previsto->valor;
                $total_banco_previsto += $banco_previsto->valor;

                if ($mes_atual > $banco_previsto->data->month) {
                    $retorno['banco_previsto']['inteiro_total_parcial'] += $banco_previsto->valor;
                }
            }
        }
        $retorno['banco_previsto']['inteiro_total'] = $total_banco_previsto;

        $total_banco_realizado = 0;

        foreach ($result_titulos  as $banco_realizado) {
            if ($banco_realizado->tipo == 'banco') {       
                $data_mes = empty($banco_realizado->titulo_data_baixa) ? Carbon::parse($banco_realizado->titulo_vencimento) : Carbon::parse($banco_realizado->titulo_data_baixa);
                $retorno['banco_realizado']["inteiro_" . $data_mes->month] += $banco_realizado->valor_total;
                $total_banco_realizado += $banco_realizado->valor_total;

                if ($mes_atual > $data_mes->month) {
                    $retorno['banco_realizado']['inteiro_total_parcial'] += $banco_realizado->valor_total;
                }
            }
        }

        $retorno['banco_realizado']['inteiro_total'] = $total_banco_realizado;

        foreach ($retorno['banco_realizado'] as $index => $valor) {
            if ($index != 'descricao') {
                $retorno['banco_diferenca'][$index] = empty($retorno['banco_previsto'][$index]) ? '' : parserValor((($valor / $retorno['banco_previsto'][$index]) - 1) * 100) . "%";
            }
        }
        $retorno['despesa_diferenca']['inteiro_total'] = empty($total_despesa_planejada) ? '' : parserValor((($total_despesa_realizada / $total_despesa_planejada) - 1) * 100) . "%";


        $total_vendas_menos_compras_menos_despesas_previsao = 0;
        foreach ($retorno['faturamento_previsto'] as $index => $valor) {
            if ($index != 'descricao' && substr_count($index, "flag_") === 0) {

                $retorno['vendas_menos_compras_menos_despesas_previsto'][$index] = $valor - $retorno['orcamento_compras'][$index] - $retorno['despesa_planejada'][$index] - $retorno['banco_previsto'][$index];
                if ($mes_atual <= intval(str_replace('inteiro_', '', $index)) || $ano > $data_atual->year) {
                    $retorno['vendas_menos_compras_menos_despesas_previsao'][$index] = $valor - $retorno['orcamento_compras'][$index] - $retorno['despesa_planejada'][$index] - $retorno['banco_previsto'][$index];
                    $total_vendas_menos_compras_menos_despesas_previsao += $retorno['vendas_menos_compras_menos_despesas_previsao'][$index];
                }
            }
        }
        if($ano > $data_atual->year){
            $retorno['vendas_menos_compras_menos_despesas_previsao']['inteiro_total'] = $retorno['vendas_menos_compras_menos_despesas_previsto']['inteiro_total'];
        }else{
            $retorno['vendas_menos_compras_menos_despesas_previsao']['inteiro_total'] = $total_vendas_menos_compras_menos_despesas_previsao;
        }

        if($fields['modo'] == 'competencia'){
            foreach ($retorno['faturamento_realizado'] as $index => $valor) {
                if ($index != 'descricao') {

                    $retorno['vendas_menos_compras_menos_despesas_realizado'][$index] = $valor - $retorno['compras_realizadas'][$index] - $retorno['despesa_realizada'][$index] - $retorno['banco_realizado'][$index];
                }
            }
        } else {
            foreach ($retorno['faturamento_caixa_fechado'] as $index => $valor) {
                if ($index != 'descricao') {
                    $retorno['vendas_menos_compras_menos_despesas_realizado'][$index] = $valor  - $retorno['titulos_caixa_total'][$index]  - $retorno['despesa_realizada'][$index] - $retorno['banco_realizado'][$index];
                }
            }
        }
        $PrecoObj = new Preco;
        $ProdutosEstoqueObj = new ProdutosEstoque();

        $query_estoque_geral =  ProdutosEstoque::select('estabelecimento', 'compra_real', 'estoque', DB::Raw('produtos_estoques.codigo_produto as codigo_produto'));
        $query_estoque_geral->leftJoin($PrecoObj->getTable(), "{$PrecoObj->getTable()}.codigo_produto", "{$ProdutosEstoqueObj->getTable()}.codigo_produto");
        $query_estoque_geral->where('estoque', '>', 0);
        $query_estoque_geral->whereNotIn('estabelecimento', $this->estabelecimentos_particular);
        if (!empty($fields['tipo_produto'])) {
                $query_estoque_geral->whereHas('especificacao', function ($query) use ($fields) {
                if ($fields['tipo_produto'] == 'nacional') {
                    $query->whereIn('procedencia', ['0', '3', '4', '5']);
                } else if ($fields['tipo_produto'] == 'importado') {
                    $query->whereIn('procedencia', ['1', '2', '6', '7']);
                }
            });
        }

        $query_estoque_geral->distinct();

        $result_estoque_geral  = $query_estoque_geral->get();

        if ($mes_atual > 12) {
            $mes_atual = 12;
        }

        $estoque_proximo_ano = 0;
        foreach ($result_estoque_geral as $estoque_geral) {
            if (!empty($estoque_geral->compra_real)) {
                $ultima_compra = $estoque_geral->compra_real;
            } else {
                $ultima_compra = 0;
            }

            if($ano <=  $data_atual->year){
                $retorno['estoque_final']["inteiro_" . $mes_atual] += $ultima_compra * $estoque_geral->estoque;
            }else{
                $estoque_proximo_ano += $ultima_compra * $estoque_geral->estoque;
            }
        }

        $cfops = array_merge($this->cfop_venda, $this->cfop_devolucao);
        $cfops = array_merge($cfops, $this->cfop_compras);
        $cfops = array_merge($cfops, $this->cfop_remessas);

        $query_movimentacao = Movimentacao::select('cfop', 'data_movimentacao', DB::Raw("sum(quantidade * custo_pcmn) as total"));
        $query_movimentacao->where('data_movimentacao', '>=', $primeiro_dia_do_ano);
        $query_movimentacao->whereNotIn('estabelecimento', $this->estabelecimentos_particular);
        $query_movimentacao->whereIn('cfop', $cfops);
        $query_movimentacao->where('documento', '<>', '');
        if (!empty($fields['tipo_produto'])) {
            if ($fields['tipo_produto'] == 'nacional') {
                $query_movimentacao->where('produto_procedencia', 'ilike', 'nacional');
            } else if ($fields['tipo_produto'] == 'importado') {
                $query_movimentacao->where('produto_procedencia', 'ilike', 'importado');
            }
        }
        $query_movimentacao->groupBy('data_movimentacao', 'cfop');

        $result_movimentacao  = $query_movimentacao->get();

        foreach ($result_movimentacao as $movimentacao) {
            if ($ano == $movimentacao->data_movimentacao->year) {
                if (in_array($movimentacao->cfop, $this->cfop_venda)) {
                    $retorno['estoque_saida']["inteiro_" . $movimentacao->data_movimentacao->month] += $movimentacao->total;
                    $retorno['estoque_saida']['inteiro_total'] += $movimentacao->total;
                    if ($mes_atual > $movimentacao->data_movimentacao->month) {
                        $retorno['estoque_saida']['inteiro_total_parcial'] += $movimentacao->total;
                    }
                } else {
                    $retorno['estoque_entrada']["inteiro_" . $movimentacao->data_movimentacao->month] += $movimentacao->total;
                    $retorno['estoque_entrada']['inteiro_total'] += $movimentacao->total;
                    if ($mes_atual > $movimentacao->data_movimentacao->month) {
                        $retorno['estoque_entrada']['inteiro_total_parcial'] += $movimentacao->total;
                    }
                }
            } else {
                if (in_array($movimentacao->cfop, $this->cfop_venda)) {
                    $retorno['estoque_saida']['outros_anos'] += $movimentacao->total;
                } else {
                    $retorno['estoque_entrada']['outros_anos'] += $movimentacao->total;
                }
            }
        }

        $retorno['estoque_final']["inteiro_" . $mes_atual] += ($retorno['estoque_saida']['outros_anos'] - $retorno['estoque_entrada']['outros_anos']);
        $estoque_final = $retorno['estoque_final']["inteiro_" . $mes_atual];
        $retorno['estoque_final']['inteiro_total'] += $estoque_final;
        for ($i = $mes_atual - 1; $i > 0; $i--) {
            $estoque_final += $retorno['estoque_saida']["inteiro_" . ($i+1)];
            $estoque_final -= $retorno['estoque_entrada']["inteiro_" . ($i+1)];
            $retorno['estoque_final']["inteiro_" . $i] = $estoque_final;
        }

        $estoque_previsao = $retorno['estoque_final']["inteiro_" . $mes_atual];
        if($ano <=  $data_atual->year){
            for ($i = $mes_atual + 1; $i <= 12; $i++) {
                $estoque_previsao -= $retorno['faturamento_previsto']["inteiro_" . $i] - ($retorno['faturamento_previsto']["inteiro_" . $i] * $margem_venda / 100);
                $estoque_previsao += $retorno['orcamento_compras']["inteiro_" . $i];
                $retorno['estoque_previsao']["inteiro_" . $i] = $estoque_previsao;
            }
        }else{
            if($data_atual->month < 12){
                $primeiro_dia_atual = '01/'.$data_atual->addMonth()->month.'/'.$data_atual->year;
                $ultimo_dia_atual= Carbon::parse($data_atual->year . "-12-31")->setTime(23, 59, 59);

                $query_faturamento_previsto = FaturamentoPrevisto::select();
                $query_faturamento_previsto->whereBetween('data', [$primeiro_dia_atual, $ultimo_dia_atual]);
                $result_faturamento_previsto = $query_faturamento_previsto->get();

                if (empty($fields['tipo_produto'])) {
                    $total_faturamento = ($result_faturamento_previsto->sum('nacional_valor') + $result_faturamento_previsto->sum('importado_valor'));
                } else if ($fields['tipo_produto'] == 'nacional') {
                    $total_faturamento = $result_faturamento_previsto->sum('nacional_valor');
                } else if ($fields['tipo_produto'] == 'importado') {
                    $total_faturamento = $result_faturamento_previsto->sum('importado_valor');
                } else {
                    $total_faturamento = ($result_faturamento_previsto->sum('nacional_valor') + $result_faturamento_previsto->sum('importado_valor'));
                }

                $estoque_proximo_ano -= $total_faturamento - ($total_faturamento * $margem_venda / 100);

                $query_orcamento = OrcamentoCompra::select();
                $query_orcamento->whereBetween('data', [$primeiro_dia_atual, $ultimo_dia_atual]);
                $result_orcamentos = $query_orcamento->get();

                if (empty($fields['tipo_produto'])) {
                    $total_orcamento = $result_orcamentos->sum('nacional_valor') + $result_orcamentos->sum('importado_valor');
                } else if ($fields['tipo_produto'] == 'nacional') {
                    $total_orcamento = $$result_orcamentos->sum('nacional_valor');
                } else if ($fields['tipo_produto'] == 'importado') {
                    $total_orcamento = $result_orcamentos->sum('importado_valor');
                } else {
                    $total_orcamento = $result_orcamentos->sum('nacional_valor') + $result_orcamentos->sum('importado_valor');
                }

                $estoque_proximo_ano += $total_orcamento;
            }        

            $estoque_previsao = $estoque_proximo_ano;
            for ($i = 1; $i <= 12; $i++) {
                $estoque_previsao -= $retorno['faturamento_previsto']["inteiro_" . $i] - ($retorno['faturamento_previsto']["inteiro_" . $i] * $margem_venda / 100);
                $estoque_previsao += $retorno['orcamento_compras']["inteiro_" . $i];
                $retorno['estoque_previsao']["inteiro_" . $i] = $estoque_previsao;
            }
        }

        $retorno['estoque_final']['inteiro_total'] = $retorno['estoque_final']["inteiro_" . $mes_atual];
        if ($mes_atual > 1) {
            $retorno['estoque_final']['inteiro_total_parcial'] += $retorno['estoque_final']["inteiro_" . ($mes_atual - 1)];
        }
        
        foreach($retorno['vendas_menos_compras_menos_despesas_previsto'] as $index => $value){
            if ($index != 'descricao' && $value > 0) {
                $retorno['vendas_menos_compras_menos_despesas_diferenca'][$index] = parserValor(((($retorno['vendas_menos_compras_menos_despesas_realizado'][$index])  / $value) - 1) * 100)."%";
            }
        }

        if($fields['modo'] != 'competencia'){
            $PedidoComprasAbertoTituloFuturoObj = PedidoComprasAbertoTituloFuturo::select();
            $PedidoComprasAbertoTituloFuturoObj->whereBetween('parcela_data', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
            $PedidoComprasAbertoTituloFuturoObj->where(function($query){
                $query->whereNull('nao_lancado')
                ->orWhere('nao_lancado',false);
            });
            $PedidoComprasAbertoTituloFuturoObj = $PedidoComprasAbertoTituloFuturoObj->get();

            $total_pedido_compras_aberto_titulo_futuro = 0;
            foreach ($PedidoComprasAbertoTituloFuturoObj as $value) {
                $retorno['pedidos_compras_abertos_titulos_futuros']["inteiro_" . $value->parcela_data->month] += $value->valor;
                $total_faturamento_previsto += $value->valor;
    
                if ($mes_atual > $value->parcela_data->month) {
                    $retorno['pedidos_compras_abertos_titulos_futuros']['inteiro_total_parcial'] += $value->valor;
                }

                $total_pedido_compras_aberto_titulo_futuro += $value->valor;
            }
            $retorno['pedidos_compras_abertos_titulos_futuros']['inteiro_total'] = $total_pedido_compras_aberto_titulo_futuro;
        }

        $retorno = $this->ajusteArrayParaValores($retorno);
        $retorno['estabelecimentos']=$this->estabelecimentos_particular;
        
        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $retorno
        ], 200);
    }

    private function ajusteArrayParaValores($array){
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

    public function clientesExcluido(){
        $clientes_exluir = ClienteNasajon::select('codigo')
            ->orWhere(DB::raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), 'ILIKE', '06311274%')
            ->orWhere(DB::raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), 'ILIKE', '05075884%')
            ->get();
        $clientes_exluir = $clientes_exluir->pluck('codigo')->toArray();

        return $clientes_exluir;
    }

    public function modalComprasDetalhes(Request $request){

        $fields = $request->only('ano', 'mes', 'tipo', 'tipo_produto','estabelecimentos');

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
            $verificacao = [];
            foreach ($result_compras_realizada as $compras_realizada) {
                foreach ($compras_realizada->nota_entrada as $pedido) {
                    $linhas[$pedido->pedido->id_nota.$pedido->id_nota] = [
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
                    if(empty($verificacao[$pedido->id_nota])){
                        $verificacao[$pedido->id_nota][] = $pedido->pedido->id_nota;
                    }else{
                        $verificacao[$pedido->id_nota][] = $pedido->pedido->id_nota;
                        foreach($verificacao[$pedido->id_nota] as $pedido_id){
                            $linhas[$pedido_id.$pedido->id_nota]['valor'] = parserValor($compras_realizada->valor/count($verificacao[$pedido->id_nota]));
                        }
                    }
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

    public function atualizarFaturamentoValorProdutoNacionalImportado(){
        ini_set('memory_limit', '1024M');

        $query_faturamento = FaturamentoOnline::select();
        $query_faturamento->whereNull('valor_produto_nacional');
        $query_faturamento->whereNull('valor_produto_importado');
        $query_faturamento->where('nasajon', true);
        $query_faturamento->whereNotNull('nota_uuid');
        $query_faturamento->with(['itensFaturamentoNotaNasajon.produtoDetalhes']);
        $resultado_faturamento = $query_faturamento->get();

        foreach ($resultado_faturamento as $faturamento) {
            $nacional = 0;
            $importado = 0;
            foreach ($faturamento->itensFaturamentoNotaNasajon as $item) {
                if (!empty($item->produtoDetalhes)) {
                    if (in_array($item->produtoDetalhes->procedencia, [0, 3, 4, 5])) {
                        $nacional += $item["Item - Valor Total"];
                        $nacional += $item["Valor Frete"];
                        $nacional += $item["Valor IPI"];
                        $nacional -= $item["Desconto"];
                    } else {
                        $importado += $item["Item - Valor Total"];
                        $importado += $item["Valor Frete"];
                        $importado += $item["Valor IPI"];
                        $importado -= $item["Desconto"];
                    }
                }
            }
            $faturamento->valor_produto_nacional = $nacional;
            $faturamento->valor_produto_importado = $importado;
            $faturamento->save();
        }
    }

    public function detalhesDespesa(Request $request){
        $fields = $request->only('conta_codigo', 'ano');

        $retorno = [];

        $ano = $fields['ano'];
        $primeiro_dia_do_ano = Carbon::parse($ano . "-01-01")->setTime(0, 0, 0);
        $ultimo_dia_do_ano = Carbon::parse($ano . "-12-31")->setTime(23, 59, 59);

        $tamanho = 0;
        switch (strlen($fields['conta_codigo'])) {
            case 1:
                $tamanho = 3;
                $class = "filho_button";
                break;
            case 3:
                $tamanho = 4;
                $class = "neto_button";
                break;
            case 4:
                $tamanho = 7;
                $class = '';
                break;
        }
        $query_despesa_realizada = ContaContabilSaldo::select('data', 'conta_nome', 'conta', DB::Raw('sum(movimentacao) as total'));
        $query_despesa_realizada->whereBetween('data', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        $query_despesa_realizada->whereNotIn('estabelecimento_codigo', $this->estabelecimentos_particular);
        $query_despesa_realizada->where('conta', 'ilike', $fields['conta_codigo'] . '%');
        $query_despesa_realizada->where('movimentacao', '>', 0);
        $query_despesa_realizada->whereNotIn('empresa', ['25', '20']);
        $query_despesa_realizada->groupBy('data', 'conta_nome', 'conta');
        $query_despesa_realizada->having(DB::Raw('char_length(conta)'), '=', $tamanho);
        $result_despesa_realizada = $query_despesa_realizada->get();

        $total_despesa_realizada = [];
        foreach ($result_despesa_realizada as $despesa_realizada) {
            if (empty($retorno[$despesa_realizada->conta])) {
                if (!empty($class)) {
                    $descricao = "<div class='d-lg-inline-flex'><a href='#' class='bt-plus-azul sinal " . $class . " d-lg-inline' data-conta_codigo=\"" . $despesa_realizada->conta . "\" data-ano=\"" . $ano . "\"></a><div class='display-inline'>" . $despesa_realizada->conta_nome . "</div></div>";
                } else {
                    $descricao = $despesa_realizada->conta_nome;
                }
                $retorno[$despesa_realizada->conta] = [
                    'descricao' => $descricao,
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                    6 => 0,
                    7 => 0,
                    8 => 0,
                    9 => 0,
                    10 => 0,
                    11 => 0,
                    12 => 0,
                    'total' => 0,
                    'conta_codigo' => $despesa_realizada->conta,
                ];
            }

            $data_mes = $despesa_realizada->data;
            $retorno[$despesa_realizada->conta][$data_mes->month] = $despesa_realizada->total;
            if (empty($total_despesa_realizada[$despesa_realizada->conta])) {
                $total_despesa_realizada[$despesa_realizada->conta] = $despesa_realizada->total;
            } else {
                $total_despesa_realizada[$despesa_realizada->conta] += $despesa_realizada->total;
            }
        }

        foreach ($total_despesa_realizada as $index => $total) {
            $retorno[$index]['total'] = $total;
        }

        $retorno = $this->ajusteArrayParaValores($retorno);

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function modalDespesaDetalhes(Request $request){
        $fields = $request->only('conta_codigo', 'ano', 'codigo_conta');

        $dados = [];

        $ano = $fields['ano'];
        $primeiro_dia_do_ano = Carbon::parse($ano . "-01-01")->setTime(0, 0, 0);
        $ultimo_dia_do_ano = Carbon::parse($ano . "-12-31")->setTime(23, 59, 59);

        $query_despesa_realizada = ContaContabilSaldo::select('data', 'conta_nome', 'conta', DB::Raw('sum(movimentacao) as total'));
        $query_despesa_realizada->whereBetween('data', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        $query_despesa_realizada->whereNotIn('estabelecimento_codigo', $this->estabelecimentos_particular);
        $query_despesa_realizada->where('conta', 'ilike', $fields['codigo_conta'] . '%');
        $query_despesa_realizada->where('movimentacao', '>', 0);
        $query_despesa_realizada->whereNotIn('empresa', ['25', '20']);
        $query_despesa_realizada->orderBy('conta');
        $query_despesa_realizada->groupBy('data', 'conta_nome', 'conta');
        $query_despesa_realizada->having(DB::Raw('char_length(conta)'), '>=', 4);
        $result_despesa_realizada = $query_despesa_realizada->get();

        $despesas_realizadas = [];
        $total = [
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
        ];

        foreach ($result_despesa_realizada as $despesa_realizada) {
            $data_mes = $despesa_realizada->data;

            if (strlen($despesa_realizada->conta) == 4) {
                if (empty($despesas_realizadas[$despesa_realizada->conta])) {
                    $despesas_realizadas[$despesa_realizada->conta] = [
                        'descricao' => $despesa_realizada->conta_nome,
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
                        'conta_codigo' => $despesa_realizada->conta,
                        'itens' => []
                    ];
                }

                $despesas_realizadas[$despesa_realizada->conta]['inteiro_' . $data_mes->month] += $despesa_realizada->total;
                $despesas_realizadas[$despesa_realizada->conta]['inteiro_total'] += $despesa_realizada->total;
                $total['inteiro_' . $data_mes->month] += $despesa_realizada->total;
                $total['inteiro_total'] += $despesa_realizada->total;
            } else if (strlen($despesa_realizada->conta) == 7) {
                $index_1 = substr($despesa_realizada->conta, 0, 4);
                $index_2 = $despesa_realizada->conta;
                if (empty($despesas_realizadas[$index_1]['itens'][$index_2])) {
                    $despesas_realizadas[$index_1]['itens'][$index_2] = [
                        'descricao' => $despesa_realizada->conta_nome,
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
                        'conta_codigo' => $despesa_realizada->conta,
                    ];
                }

                $despesas_realizadas[$index_1]['itens'][$index_2]['inteiro_' . $data_mes->month] += $despesa_realizada->total;
                $despesas_realizadas[$index_1]['itens'][$index_2]['inteiro_total'] += $despesa_realizada->total;
            }
        }

        $dados = [
            'despesas_realizadas' => $this->ajusteArrayParaValores($despesas_realizadas),
            'total' => $this->ajusteArrayParaValores($total),
        ];

        return view("programs.acompanhamento_orcamentario.modal.despesa_detalhes")->with(['dados' => $dados]);
    }

    public function modalBancoDetalhes(Request $request){
        $fields = $request->only('conta_codigo', 'ano');

        $dados = [];
        $cnpj = $this->cnpjINtercompany();

        $estabelecimentos_particular = ['20','25', '30', 'MARCELO', 'MN_NAMURA', 'NAMURA_NN','NELSON'];

        $ano = $fields['ano'];
        $primeiro_dia_do_ano = Carbon::parse($ano . "-01-01")->setTime(0, 0, 0);
        $ultimo_dia_do_ano = Carbon::parse($ano . "-12-31")->setTime(23, 59, 59);
        $query_banco_realizado = TitulosAPagarNasajon::select();
        $query_banco_realizado->whereIn('Fornecedor', $this->codigos_fornecedores);
        $query_banco_realizado->whereNotIn('Estabelecimento', $estabelecimentos_particular);
        $query_banco_realizado->whereNotIn("Fornecedor", $cnpj);
        $query_banco_realizado->whereBetween(DB::Raw('case 
            when "Data da Baixa" is null then
                "Data do Vencimento"
            else
                "Data da Baixa"
            end'), [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        $result_banco_realizado = $query_banco_realizado->get();

        $bancos_realizados = [];
        $total = [
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
        ];
        foreach ($result_banco_realizado as $banco_realizado) {
            $index = '';
            if (in_array($banco_realizado['Fornecedor'], $this->codigos_fornecedores_itau)) {
                $index = 'itau';
            } else if (in_array($banco_realizado['Fornecedor'], $this->codigos_fornecedores_brasil)) {
                $index = 'brasil';
            } else if (in_array($banco_realizado['Fornecedor'], $this->codigos_fornecedores_bradesco)) {
                $index = 'bradesco';
            } else if (in_array($banco_realizado['Fornecedor'], $this->codigos_fornecedores_daycoval)) {
                $index = 'daycoval';
            }
            if (empty($bancos_realizados[$index])) {
                $bancos_realizados[$index] = [
                    'fornecedor' => $banco_realizado['Razão Social do Fornecedor'],
                    'fornecedor_codigo' => $index,
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
                    'abertos' => [
                        'descricao' => 'Aberto',
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
                    ],
                    'quitados' => [
                        'descricao' => 'Quitado',
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
                    ],
                ];
            }

            $data_mes = empty($banco_realizado['Data da Baixa']) ? Carbon::parse($banco_realizado['Data do Vencimento']) : Carbon::parse($banco_realizado['Data da Baixa']);
            $bancos_realizados[$index]['inteiro_' . $data_mes->month] += empty($banco_realizado['Data da Baixa']) ? $banco_realizado['Valor'] : $banco_realizado['Valor da Baixa'];
            $bancos_realizados[$index]['inteiro_total'] += empty($banco_realizado['Data da Baixa']) ? $banco_realizado['Valor'] : $banco_realizado['Valor da Baixa'];
            $total['inteiro_' . $data_mes->month] += empty($banco_realizado['Data da Baixa']) ? $banco_realizado['Valor'] : $banco_realizado['Valor da Baixa'];
            $total['inteiro_total'] += empty($banco_realizado['Data da Baixa']) ? $banco_realizado['Valor'] : $banco_realizado['Valor da Baixa'];

            if (empty($banco_realizado['Data da Baixa'])) {
                $bancos_realizados[$index]['abertos']['inteiro_' . $data_mes->month] += empty($banco_realizado['Data da Baixa']) ? $banco_realizado['Valor'] : $banco_realizado['Valor da Baixa'];
                $bancos_realizados[$index]['abertos']['inteiro_total'] += empty($banco_realizado['Data da Baixa']) ? $banco_realizado['Valor'] : $banco_realizado['Valor da Baixa'];
            } else {
                $bancos_realizados[$index]['quitados']['inteiro_' . $data_mes->month] += empty($banco_realizado['Data da Baixa']) ? $banco_realizado['Valor'] : $banco_realizado['Valor da Baixa'];
                $bancos_realizados[$index]['quitados']['inteiro_total'] += empty($banco_realizado['Data da Baixa']) ? $banco_realizado['Valor'] : $banco_realizado['Valor da Baixa'];
            }
        }

        $dados = [
            'bancos_realizados' => $this->ajusteArrayParaValores($bancos_realizados),
            'total' => $this->ajusteArrayParaValores($total),
            'ano' => $ano,
        ];

        return view("programs.acompanhamento_orcamentario.modal.bancos_detalhes")->with(['dados' => $dados]);
    }

    public function modalBancoTituloDetalhes(Request $request){
        $fields = $request->only('status', 'ano', 'mes', 'fornecedor_codigo');

        $dados = [];

        $cnpj = $this->cnpjINtercompany();

        $estabelecimentos = returnTodasEmpresasView();

        $ano = $fields['ano'];
        $mes = $fields['mes'];
        if (empty($mes)) {
            $primeiro_dia_do_ano = Carbon::parse($ano . "-01-01")->setTime(0, 0, 0);
            $ultimo_dia_do_ano = Carbon::parse($ano . "-12-31")->setTime(23, 59, 59);
        } else {
            $primeiro_dia_do_ano = Carbon::parse($ano . "-" . $mes . "-01")->setTime(0, 0, 0)->firstOfMonth();
            $ultimo_dia_do_ano = Carbon::parse($ano . "-" . $mes . "-01")->setTime(23, 59, 59)->lastOfMonth();
        }

        if ($fields['fornecedor_codigo'] == 'itau') {
            $codigos_fornecedores = $this->codigos_fornecedores_itau;
        } else if ($fields['fornecedor_codigo'] == 'brasil') {
            $codigos_fornecedores = $this->codigos_fornecedores_brasil;
        } else if ($fields['fornecedor_codigo'] == 'bradesco') {
            $codigos_fornecedores = $this->codigos_fornecedores_bradesco;
        } else if ($fields['fornecedor_codigo'] == 'daycoval') {
            $codigos_fornecedores = $this->codigos_fornecedores_daycoval;
        } else {
            $codigos_fornecedores = $this->codigos_fornecedores;
        }

        $estabelecimentos_particular = ['20','25', '30', 'MARCELO', 'MN_NAMURA', 'NAMURA_NN','NELSON'];

        $query_banco_realizado = TitulosAPagarNasajon::select();
        $query_banco_realizado->whereIn('Fornecedor', $codigos_fornecedores);
        $query_banco_realizado->whereNotIn('Estabelecimento', $estabelecimentos_particular);
        $query_banco_realizado->whereNotIn('Origem', ['Nota Serviços Publicos']);
        $query_banco_realizado->whereNotIn("Fornecedor", $cnpj);
        $query_banco_realizado->with('notaEntrada');
        $query_banco_realizado->whereBetween(DB::Raw('case 
            when "Data da Baixa" is null then
                "Data do Vencimento"
            else
                "Data da Baixa"
            end'), [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        if (!empty($fields['status'])) {
            $query_banco_realizado->where('Situação do Título', $fields['status']);
        }
        $result_banco_realizado = $query_banco_realizado->get();

        $titulos = [];
        foreach ($result_banco_realizado as $titulo) {
            if ($titulo['Situação do Título'] === 'Aberto') {
                $valor_titulo = $titulo['Valor'];
            } else {
                $valor_titulo = $titulo['Valor'] - $titulo['Valor da Baixa'];
            }

            $linha = [];

            $linha['estabelecimento'] = $estabelecimentos[$titulo['Estabelecimento']];
            $linha['documento'] = (empty($titulo->notaEntrada)) ? $titulo['Número Nota'] : $titulo->notaEntrada['Número do Documento'];
            $linha['parcela'] = $titulo['Parcela'];
            $linha['data_emissao'] = parserData($titulo['Data de Emissão']);
            $linha['data_vencimento'] = parserData($titulo['Data do Vencimento']);
            $linha['data_pagamento'] = empty($titulo['Data da Baixa']) ? '' : parserData($titulo['Data da Baixa']);
            $linha['valor_original'] = empty($titulo['Valor']) ? '' : parserValor($titulo['Valor']);
            $linha['valor'] = empty($valor_titulo) ? '' : parserValor($valor_titulo);
            $linha['multa'] = $titulo['Multa'];
            $linha['nome_fornecedor'] = empty($titulo['CNPJ/CPF do Fornecedor']) ? $titulo['Razão Social do Fornecedor'] . ' - ' . $titulo['Fornecedor'] : $titulo['Razão Social do Fornecedor'] . ' - ' . $titulo['CNPJ/CPF do Fornecedor'];
            $linha['numero_boleto'] = $titulo['Número do Título'];
            $linha['juros_diarios'] = $titulo->percentualjurosdiario;
            $linha['data_juros'] = '';
            $linha['desconto'] = $titulo['Desconto'];
            $linha['POSICAO_CR'] = $titulo['Nosso número'];
            $linha['status'] = $titulo['Situação do Título'];

            $titulos[] = $linha;
        }

        $dados = [
            'titulos' => $titulos
        ];

        return view("programs.acompanhamento_orcamentario.modal.bancos_titulo_detalhes")->with(['dados' => $dados]);
    }

    public function modalDespesaArrayContasDetalhes(Request $request){
        $fields = $request->only('conta_codigo', 'ano', 'codigo_conta');

        $dados = [];

        $ano = $fields['ano'];
        $primeiro_dia_do_ano = Carbon::parse($ano . "-01-01")->setTime(0, 0, 0);
        $ultimo_dia_do_ano = Carbon::parse($ano . "-12-31")->setTime(23, 59, 59);

        $query_despesa_realizada = ContaContabilSaldo::select('data', 'conta_nome', 'conta', DB::Raw('sum(movimentacao) as total'));
        $query_despesa_realizada->whereBetween('data', [$primeiro_dia_do_ano, $ultimo_dia_do_ano]);
        $query_despesa_realizada->whereNotIn('estabelecimento_codigo', $this->estabelecimentos_particular);
        $query_despesa_realizada->whereIn('conta', decrypt($fields['codigo_conta']));
        $query_despesa_realizada->where('movimentacao', '>', 0);
        $query_despesa_realizada->whereNotIn('empresa', ['25', '20']);
        $query_despesa_realizada->orderBy('conta');
        $query_despesa_realizada->groupBy('data', 'conta_nome', 'conta');
        $result_despesa_realizada = $query_despesa_realizada->get();

        $despesas_realizadas = [];
        $total = [
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
        ];

        foreach ($result_despesa_realizada as $despesa_realizada) {
            $data_mes = $despesa_realizada->data;

            if (empty($despesas_realizadas[$despesa_realizada->conta])) {
                $despesas_realizadas[$despesa_realizada->conta] = [
                    'descricao' => $despesa_realizada->conta_nome,
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
                    'conta_codigo' => $despesa_realizada->conta,
                    'itens' => []
                ];
            }

            $despesas_realizadas[$despesa_realizada->conta]['inteiro_' . $data_mes->month] += $despesa_realizada->total;
            $despesas_realizadas[$despesa_realizada->conta]['inteiro_total'] += $despesa_realizada->total;
            $total['inteiro_' . $data_mes->month] += $despesa_realizada->total;
            $total['inteiro_total'] += $despesa_realizada->total;
        }

        $dados = [
            'despesas_realizadas' => $this->ajusteArrayParaValores($despesas_realizadas),
            'total' => $this->ajusteArrayParaValores($total),
        ];

        return view("programs.acompanhamento_orcamentario.modal.despesa_conta_unica_detalhes")->with(['dados' => $dados]);
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
        $cnpj_excluir[] = '97544848000202';
        return $cnpj_excluir;
    }

    public function modalComprasPrevistoDetalhes(Request $request){
        $fields = $request->only('ano', 'mes', 'tipo', 'tipo_produto','estabelecimentos');

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
        $total_previsto = 0;
        $total_quantidade = [
            "comprada" => 0,
            "recebido" => 0,
            "restante" => 0,
        ];


        $query_compras = ComprasNasajon::select('fornecedor_nome', 'numero_pedido', 'estabelecimento', DB::RAW('sum(quantidade_restante * preco_compra_unitario) as preco_compra_total, sum(quantidade) as quantidade_comprada, sum(quantidade - quantidade_restante) as quantidade_recebido, sum(quantidade_restante) as quantidade_restante'));
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
        $query_compras->groupBy('fornecedor_nome', 'numero_pedido', 'estabelecimento');
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
            if(empty($linhas[$compras->fornecedor_nome] )){
                $linhas[$compras->fornecedor_nome] = [
                    "fornecedor" => $compras->fornecedor_nome,
                    "valor" => intval($compras->estabelecimento) == 3 ? $compra_total * $cotacaoDoDiaObj->valor : $compra_total,
                    "previsto" => 0,
                ];
            }else{
                $linhas[$compras->fornecedor_nome]["valor"] += intval($compras->estabelecimento) == 3 ? $compra_total * $cotacaoDoDiaObj->valor : $compra_total;
            }
            $total += intval($compras->estabelecimento) == 3 ? $compra_total * $cotacaoDoDiaObj->valor : $compra_total;
        }

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
                if(empty($linhas[$compras_realizada->nome_fornecedor] )){
                    $linhas[$compras_realizada->nome_fornecedor] = [
                        "fornecedor" => empty($pedido->pedido) ? '' : $compras_realizada->nome_fornecedor,
                        "valor" => $compras_realizada->valor,
                        "previsto" => 0,
                    ];
                }else{
                    $linhas[$compras_realizada->nome_fornecedor]["valor"] += $compras_realizada->valor;
                }
            }

            $total += $compras_realizada->valor;
        }

        $query_previsto = OrcamentoCompra::select();
        $query_previsto->whereBetween('data', [$primeiro_dia_do_mes, $ultimo_dia_do_mes]);
        $query_previsto->where('modo', 'competencia');
        if (!empty($fields['tipo_produto'])) {
            if ($fields['tipo_produto'] == 'nacional') {
                $query_previsto->whereIn('tipo', ['nacional']);
            } else if ($fields['tipo_produto'] == 'importado') {
                $query_previsto->whereIn('tipo', ['importado']);
            }else{
                $query_previsto->whereIn('tipo', ['uso_consumo']);
            }
        }else{
            $query_previsto->whereIn('tipo', ['nacional', 'importado', 'uso_consumo']);
        }
        $result_previsto = $query_previsto->get();

        foreach ($result_previsto as $previsto) {
            $fornecedor = empty($previsto->detalhesFornecedor)? '' : $previsto->detalhesFornecedor->nome;
            if(empty($linhas[$fornecedor] )){
                $linhas[$fornecedor] = [
                    "fornecedor" => empty($fornecedor) ? '' : $fornecedor,
                    "valor" => 0,
                    "previsto" => $previsto->valor,
                ];
            }else{
                $linhas[$fornecedor]["previsto"] += $previsto->valor;
            }

            $total_previsto += $previsto->valor;
        }

        $linhas = $this->ajusteArrayParaValores($linhas);
        $total = empty($total) ? '' : parserValor($total);
        $total_previsto = empty($total_previsto) ? '' : parserValor($total_previsto);

        return view("programs.acompanhamento_orcamentario.modal.compras_previsto_detalhes")->with(['linhas' => $linhas, 'total' => $total, 'tipo' => $fields['tipo'], 'total_quantidade' => $total_quantidade, 'total_previsto' => $total_previsto]);
    }

    public function modalTituloNaoLancado(Request $request){
        $campos = $request->only(['mes','filtro_ano']);

        try{
            $filtro_ano = decrypt($campos['filtro_ano']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        if($campos['mes'] == '6_meses'){
            $data_inicial = Carbon::parse($filtro_ano.'-01-01');
            $data_final = Carbon::parse($filtro_ano.'-06-30');
        }else if($campos['mes'] == '12_meses'){
            $data_inicial = Carbon::parse($filtro_ano.'-01-01');
            $data_final = Carbon::parse($filtro_ano.'-12-31');
        }else{
            $data_inicial = Carbon::parse($filtro_ano.'-'.$campos['mes'].'-01');
            $data_final = Carbon::parse($filtro_ano.'-'.$campos['mes'].'-01')->endOfMonth();
        }

        $retorno = [];
        $total = 0;

        $faturas_notas = NotasImportadasEntradasTitulo::with(['notaImportada','duplicatas' => function($query) use ($data_inicial,$data_final){
            $query->whereBetween('vencimento',[$data_inicial,$data_final]);
        }])
        ->where('estabelecimento','<>','20')
        ->with('notasEntradas')
        ->whereHas('duplicatas',function($query) use ($data_inicial,$data_final){
            $query->whereBetween('vencimento',[$data_inicial,$data_final]);
        })
        ->whereDoesntHave('notaImportada.itensNotas',function($query){
            $cfop = ['2913','5202'];
            $query->whereIn('codigo_cfop',$cfop);
        })
        ->whereDoesntHave('notaImportada',function($query){
            $query->where('fornecedor_documento','34.279.857/0001-04');
            $query->orWhere('fornecedor_documento','57.142.978/0001-05');
        })
        ->where(function($query){
            $query->whereNull('lancado')
            ->orWhere('lancado',false);
        })
        ->get();

        $estabelecimentos = returnEmpresasNasajonView();

        $faturas_notas->each(function($query) use (&$retorno,&$total,$estabelecimentos){
            if(empty($query->notasEntradas)){
                foreach($query->duplicatas as $valores){
                    $retorno[] = [
                        'estabelecimento' => $estabelecimentos[(integer)$query->estabelecimento],
                        'fornecedor' => $query->fornecedor_nome.' - '.$query->fornecedor_documento,
                        'fatura' => $query->fatura,
                        'nota' => $query->notaImportada->documento_numero,
                        'id' => encrypt($query->notaImportada->id),
                        'id_fatura' => encrypt($query->id),
                        'vencimento' => parserData($valores->vencimento),
                        'valor' => parserValor($valores->valor),
                    ];
                    $total += $valores->valor;
                }
            }
        });

        $total = parserValor($total);

        return view('programs.acompanhamento_orcamentario.modal.titulos_nao_lancados')->with(['retorno' => $retorno,'total' => $total]);
    }

    public function modalTituloDuplicatas(Request $request){
        $campos = $request->only(['id']);

        try{
            $id = decrypt($campos['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $retorno = [];
        $total = 0;

        $faturas_notas = NotasImportadasEntradasTitulo::with(['duplicatas'])
        ->find($id);

        $faturas_notas->duplicatas->each(function($query) use (&$retorno,&$total){
            $retorno[] = [
                'duplicata' => $query->duplicata,
                'vencimento' => parserData($query->vencimento),
                'valor' => parserValor($query->valor),
            ];

            $total += $query->valor;
        });

        $total = parserValor($total);

        return view('programs.acompanhamento_orcamentario.modal.duplicata')->with(['retorno' => $retorno,'total' => $total]);
    }
}
