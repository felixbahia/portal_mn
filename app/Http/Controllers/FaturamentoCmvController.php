<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Auth;
use App\ClienteNasajon;
use App\Movimentacao;
use App\ContaContabilSaldo;
use Carbon\Carbon;

use App\Http\Requests\FaturamentoCmvFiltroRequest;

class FaturamentoCmvController extends Controller
{
    // private $cfop_vendas = ['5922', '5949', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101'];
    private $cfop_vendas = ['5922', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101', '6502', '5104', '6104'];

    private $cfop_devolucao = ['1201', '1202', '2201', '2202','2504'];

    private $cfop_icms = ['5102', '5101', '6102', '6101', '6123', '5123', '6108', '6905'];

    private $estabelecimentos = [];

    private $codigo_contabil_pis = ['3112002', '3114002', '3116002', '3118002', '3121003', '3122003'];
    private $codigo_contabil_cofins = ['3112001', '3114001', '3116001', '3118001', '3121002', '3122002'];
    private $codigo_contabil_icms = ['3112003', '3114003', '3116003', '3118003', '3121004', '3122004'];

    public function __construct() {
        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[0]);
        unset($estabelecimentos[20]);
        $this->estabelecimentos = $estabelecimentos;
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\FaturamentoVsCMV") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\FaturamentoVsCMV');
		$agrupadores = [
			'medio_gerencial' => 'Médio Gerencial',
			'contabil' => 'Médio Contábil',
			'gerencial' => 'Gerencial'
		];
        return view('programs.faturamento_cmv.index')->with(['estabelecimentos' => $this->estabelecimentos, 'agrupadores' => $agrupadores]);
    }

    public function filtro(FaturamentoCmvFiltroRequest $request){
        ini_set('memory_limit','1024M');
        $fields = $request->only(['ano', 'estabelecimento', 'grupo', 'subgrupo', 'marca', 'linha', 'agrupar', 'prepago']);

		$clientes_exluir = ClienteNasajon::select('codigo')
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
        ->get();
        $clientes_exluir = $clientes_exluir->pluck('codigo')->toArray();

        $MovimentacaoObj = Movimentacao::query()->select(DB::raw("EXTRACT(month from data_movimentacao) as mes, sum((quantidade * preco))::numeric(15,2) as valor, sum((quantidade * preco_pcmn))::numeric(15,2) as gerencial, sum((quantidade * custo_pcmn))::numeric(15,2) as medio_gerencial, sum((quantidade * custo_sem_imposto))::numeric(15,2) as contabil, sum(frete)::numeric(15,2) as frete, sum(ipi)::numeric(15,2) as ipi, sum(desconto)::numeric(15,2) as desconto, sum(seguro)::numeric(15,2) as seguro, sum(preco_prepago)::numeric(15,2) as prepago, sum(case when sinal = 'SAIDA' then ((((quantidade * preco)+ frete + ipi + seguro) - desconto ) * (aliquota / 100)) else 0 end)::numeric(15,2) AS valor_aliquota, sum(case when sinal = 'SAIDA' then ((((quantidade * preco)+ frete + ipi + seguro + case when preco_prepago is not null then preco_prepago else 0 end) - desconto ) * (aliquota / 100)) else 0 end)::numeric(15,2) AS valor_aliquota_prepago"), "estabelecimento", "cfop");

        $MovimentacaoObj->whereRaw("EXTRACT(year from data_movimentacao) = '{$fields['ano']}'");
        $MovimentacaoObj->whereNotIn('cliente_codigo', $clientes_exluir);
        if(!empty($fields['estabelecimento'])){
            $estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
            $MovimentacaoObj->where("estabelecimento", $estabelecimento);
        }

        if(!empty($fields['grupo']) || !empty($fields['subgrupo']) || !empty($fields['marca']) || !empty($fields['linha'])){
			if(!empty($fields['grupo'])){
				$MovimentacaoObj->where("grupo", 'ilike' , $fields['grupo']);
			}
			if(!empty($fields['subgrupo'])){
				$MovimentacaoObj->where("subgrupo", 'ilike', $fields['subgrupo']);
			}
			if(!empty($fields['marca'])){
				$MovimentacaoObj->where("marca", 'ilike', $fields['marca']);
			}
			if(!empty($fields['linha'])){
				$MovimentacaoObj->where("linha", 'ilike', $fields['linha']);
			}
        }
        $MovimentacaoObj->groupBy(DB::raw("EXTRACT(month from data_movimentacao), estabelecimento, cfop"));

        $retorno = [
            'movimento' => [],
            'devolucao' => [],
            'pis' => [],
            'cofins' => [],
            'icms' => [],
			'icms_beneficio' => [],
            'frete' => [],
            'ipi' => [],
            'seguro' => [],
            'desconto' => [],
            'resultado_movimento_devolucao' => [],
            'venda_portal' => [],
            'resultado_dif_venda' => [],
            'custo_contabil' => [],
            'margem_contabil' => [],
            'custo_medio_gerencial' => [],
            'margem_medio_gerencial' => [],
            'custo_gerencial' => [],
            'margem_gerencial' => [],
            'prepago' => [],
            'icms_prepago' => [],
            'resultado_prepago' => [],
            'custo_contabil_prepago' => [],
            'margem_contabil_prepago' => [],
            'custo_medio_gerencial_prepago' => [],
            'margem_medio_gerencial_prepago' => [],
            'custo_gerencial_prepago' => [],
            'margem_gerencial_prepago' => [],
            'despesas_operacionais' => [],
            'despesas_outras_receitas' => [],
            'despesas_outras' => [],
            'despesas_indedutivesis' => [],
            'despesas_imposto_renda_contribuicao_social' => [],
            'lucro_prejuizo' => [],
            'lucro_prejuizo_acumulado' => [],
        ];
        $movimentos = $MovimentacaoObj->get();
        $movimentos->each(function($movimento) use (&$retorno){
            if(in_array($movimento->cfop, $this->cfop_vendas)){
                if(!isset($retorno['movimento'][intval($movimento->mes)])){
                    $retorno['movimento'][intval($movimento->mes)] = 0;
                    $retorno['custo_medio_gerencial'][intval($movimento->mes)] = 0;
                    $retorno['custo_gerencial'][intval($movimento->mes)] = 0;
                    $retorno['custo_contabil'][intval($movimento->mes)] = 0;
                    $retorno['frete'][intval($movimento->mes)] = 0;
                    $retorno['ipi'][intval($movimento->mes)] = 0;
                    $retorno['desconto'][intval($movimento->mes)] = 0;
                    $retorno['seguro'][intval($movimento->mes)] = 0;
                    $retorno['devolucao'][intval($movimento->mes)] = 0;
                    $retorno['venda_portal'][intval($movimento->mes)] = 0;
                    $retorno['prepago'][intval($movimento->mes)] = 0;
                    $retorno['pis'][intval($movimento->mes)] = 0;
                    $retorno['cofins'][intval($movimento->mes)] = 0;
                    $retorno['icms'][intval($movimento->mes)] = 0;
                    $retorno['icms_prepago'][intval($movimento->mes)] = 0;
                    $retorno['icms_beneficio'][intval($movimento->mes)] = 0;
                }
                $retorno['movimento'][intval($movimento->mes)] += (float) $movimento->valor;
                $retorno['custo_medio_gerencial'][intval($movimento->mes)] += (float) $movimento->medio_gerencial;
                $retorno['custo_gerencial'][intval($movimento->mes)] += (float) $movimento->gerencial;
                $retorno['custo_contabil'][intval($movimento->mes)] += (float) $movimento->contabil;
                $retorno['frete'][intval($movimento->mes)] += (float) $movimento->frete;
                $retorno['ipi'][intval($movimento->mes)] += (float) $movimento->ipi;
                $retorno['desconto'][intval($movimento->mes)] += (float) $movimento->desconto;
                $retorno['seguro'][intval($movimento->mes)] += (float) $movimento->seguro;
                $retorno['prepago'][intval($movimento->mes)] += (float) $movimento->prepago;

                $retorno['venda_portal'][intval($movimento->mes)] += (float) ($movimento->valor + $movimento->frete + $movimento->seguro + $movimento->ipi) - $movimento->desconto;
            }else if(in_array($movimento->cfop, $this->cfop_devolucao)){
                if(!isset($retorno['devolucao'][intval($movimento->mes)])){
                    $retorno['movimento'][intval($movimento->mes)] = 0;
                    $retorno['custo_medio_gerencial'][intval($movimento->mes)] = 0;
                    $retorno['custo_gerencial'][intval($movimento->mes)] = 0;
                    $retorno['custo_contabil'][intval($movimento->mes)] = 0;
                    $retorno['frete'][intval($movimento->mes)] = 0;
                    $retorno['ipi'][intval($movimento->mes)] = 0;
                    $retorno['desconto'][intval($movimento->mes)] = 0;
                    $retorno['seguro'][intval($movimento->mes)] = 0;
                    $retorno['devolucao'][intval($movimento->mes)] = 0;
                    $retorno['venda_portal'][intval($movimento->mes)] = 0;
                    $retorno['prepago'][intval($movimento->mes)] = 0;
                    $retorno['icms'][intval($movimento->mes)] = 0;
                    $retorno['icms_prepago'][intval($movimento->mes)] = 0;
                    $retorno['icms_beneficio'][intval($movimento->mes)] = 0;
                }
                $retorno['devolucao'][intval($movimento->mes)] += (float) ($movimento->valor + $movimento->frete + $movimento->seguro + $movimento->ipi) - $movimento->desconto;
            }
            
            if(in_array($movimento->cfop, $this->cfop_icms)){
                if(!isset($retorno['devolucao'][intval($movimento->mes)])){
                    $retorno['movimento'][intval($movimento->mes)] = 0;
                    $retorno['custo_medio_gerencial'][intval($movimento->mes)] = 0;
                    $retorno['custo_gerencial'][intval($movimento->mes)] = 0;
                    $retorno['custo_contabil'][intval($movimento->mes)] = 0;
                    $retorno['frete'][intval($movimento->mes)] = 0;
                    $retorno['ipi'][intval($movimento->mes)] = 0;
                    $retorno['desconto'][intval($movimento->mes)] = 0;
                    $retorno['seguro'][intval($movimento->mes)] = 0;
                    $retorno['devolucao'][intval($movimento->mes)] = 0;
                    $retorno['venda_portal'][intval($movimento->mes)] = 0;
                    $retorno['prepago'][intval($movimento->mes)] = 0;
                    $retorno['icms'][intval($movimento->mes)] = 0;
                    $retorno['icms_prepago'][intval($movimento->mes)] = 0;
                }
                //$retorno['icms'][intval($movimento->mes)] += (float) $movimento->valor_aliquota;
                //$retorno['icms_prepago'][intval($movimento->mes)] += (float) $movimento->valor_aliquota_prepago;
            }
        });
        unset($movimentos);
        unset($MovimentacaoObj);

        $contabil_contas = ['5', '6', '7', '8', '9'];
        $contabil_contas = array_merge($contabil_contas, $this->codigo_contabil_pis);
        $contabil_contas = array_merge($contabil_contas, $this->codigo_contabil_cofins);
        $contabil_contas = array_merge($contabil_contas, $this->codigo_contabil_icms);

        for($i = 1; $i <= 12; $i++ ){
            $retorno['pis'][$i] = 0;
            $retorno['cofins'][$i] = 0;
            $retorno['icms'][$i] = 0;
            $retorno['icms_prepago'][$i] = 0;
            $retorno['despesas_operacionais'][$i] = 0;
            $retorno['despesas_outras_receitas'][$i] = 0;
            $retorno['despesas_outras'][$i] = 0;
            $retorno['despesas_indedutivesis'][$i] = 0;
            $retorno['despesas_imposto_renda_contribuicao_social'][$i] = 0;
        }

        $query_despesa = ContaContabilSaldo::select('data', 'conta', DB::Raw('sum(movimentacao) as total'));
        $query_despesa->where('ano', intval($fields['ano']));
        $query_despesa->whereIn('conta', $contabil_contas);
        $query_despesa->whereNotIn('empresa', ['25', '20']);
        $query_despesa->groupBy('data', 'conta');
        $result_despesa = $query_despesa->get();
        
        foreach($result_despesa as $despesa){
            $data_mes = $despesa->data;
            if($despesa->conta == 5){
                $retorno['despesas_operacionais'][$data_mes->month] = $despesa->total;
            }else if($despesa->conta == 6){
                $retorno['despesas_outras_receitas'][$data_mes->month] = $despesa->total;
            }else if($despesa->conta == 7){
                $retorno['despesas_outras'][$data_mes->month] = $despesa->total;
            }else if($despesa->conta == 8){
                $retorno['despesas_indedutivesis'][$data_mes->month] = $despesa->total;
            }else if($despesa->conta == 9){
                $retorno['despesas_imposto_renda_contribuicao_social'][$data_mes->month] = $despesa->total;
            }else if(in_array($despesa->conta, $this->codigo_contabil_pis)){
                if(!empty($fields['grupo']) || !empty($fields['subgrupo']) || !empty($fields['marca']) || !empty($fields['linha'])){
                    $retorno['pis'][$data_mes->month] = 0;
                }else{
                    if(empty($retorno['pis'][$data_mes->month])){
                        $retorno['pis'][$data_mes->month] = $despesa->total;
                    }else{
                        $retorno['pis'][$data_mes->month] += $despesa->total;
                    }  
                }
            }else if(in_array($despesa->conta, $this->codigo_contabil_cofins)){
                if(!empty($fields['grupo']) || !empty($fields['subgrupo']) || !empty($fields['marca']) || !empty($fields['linha'])){
                    $retorno['cofins'][$data_mes->month] = 0;
                }else{
                    if(empty($retorno['cofins'][$data_mes->month])){
                        $retorno['cofins'][$data_mes->month] = $despesa->total;
                    }else{
                        $retorno['cofins'][$data_mes->month] += $despesa->total;
                    } 
                }
            }else if(in_array($despesa->conta, $this->codigo_contabil_icms)){
                if(!empty($fields['grupo']) || !empty($fields['subgrupo']) || !empty($fields['marca']) || !empty($fields['linha'])){
                    $retorno['cofins'][$data_mes->month] = 0;
                }else{
                    if(empty($retorno['icms'][$data_mes->month])){
                        $retorno['icms'][$data_mes->month] = $despesa->total;
                        $retorno['icms_prepago'][$data_mes->month] = $despesa->total;
                    }else{
                        $retorno['icms'][$data_mes->month] += $despesa->total;
                        $retorno['icms_prepago'][$data_mes->month] += $despesa->total;
                    }
                }
            }
        }

        for($i = 1; $i <= 12; $i++ ){
            $movimento = $retorno['movimento'][$i] ?? 0;
            $devolucao = $retorno['devolucao'][$i] ?? 0;

            $custo_medio_gerencial = $retorno['custo_medio_gerencial'][$i] ?? 0;
            $retorno['custo_medio_gerencial_prepago'][$i] = $custo_medio_gerencial;
            
            $custo_gerencial = $retorno['custo_gerencial'][$i] ?? 0;
            $retorno['custo_gerencial_prepago'][$i] = $custo_gerencial;

            $custo_contabil = $retorno['custo_contabil'][$i] ?? 0;
            $retorno['custo_contabil_prepago'][$i] = $custo_contabil;

            $frete = $retorno['frete'][$i] ?? 0;
            $ipi = $retorno['ipi'][$i] ?? 0;
            $desconto = $retorno['desconto'][$i] ?? 0;
            $seguro = $retorno['seguro'][$i] ?? 0;
            $prepago = $retorno['prepago'][$i] ?? 0;

            $venda_portal = $retorno['venda_portal'][$i] ?? 0;
            $retorno['venda_portal'][$i] = $venda_portal - $devolucao;
            $venda_portal = $retorno['venda_portal'][$i] ?? 0;

            $valor = ($movimento - $devolucao);
            
            //$pis = ((1.65 / 100) * $valor);
            //$retorno['pis'][$i] = $pis;
            $pis = $retorno['pis'][$i];

            //$cofins = ((7.6 / 100) * $valor);
            //$retorno['cofins'][$i] = $cofins;
            $cofins = $retorno['cofins'][$i];

            $icms = $retorno['icms'][$i] ?? 0;
            $icms_prepago = $retorno['icms_prepago'][$i] ?? 0;

            $retorno['resultado_movimento_devolucao'][$i] = ($movimento + $frete + $ipi + $seguro) - ($devolucao + $desconto + $pis + $cofins + $icms);

            $retorno['resultado_movimento_devolucao_sem_impostos'][$i] = ($movimento + $frete + $ipi + $seguro) - ($devolucao + $desconto);
            $retorno['resultado_prepago'][$i] = ($movimento + $frete + $ipi + $seguro + $prepago) - ($devolucao + $desconto + $pis + $cofins + $icms_prepago);

            $retorno['resultado_dif_venda'][$i] = $venda_portal - $retorno['resultado_movimento_devolucao'][$i];
            if($custo_medio_gerencial > 0){
                $retorno['margem_medio_gerencial'][$i] = (($retorno['resultado_movimento_devolucao_sem_impostos'][$i] - $custo_medio_gerencial) / $retorno['resultado_movimento_devolucao_sem_impostos'][$i]) * 100;
                $retorno['margem_medio_gerencial_prepago'][$i] = ((($retorno['resultado_movimento_devolucao_sem_impostos'][$i] + $prepago) - $custo_medio_gerencial) / ($retorno['resultado_movimento_devolucao_sem_impostos'][$i] + $prepago)) * 100;
            }
            if($custo_medio_gerencial > 0){
                $retorno['margem_gerencial'][$i] = (($retorno['resultado_movimento_devolucao_sem_impostos'][$i] - $custo_gerencial) / $retorno['resultado_movimento_devolucao_sem_impostos'][$i]) * 100;
                $retorno['margem_gerencial_prepago'][$i] = ((($retorno['resultado_movimento_devolucao_sem_impostos'][$i] + $prepago) - $custo_gerencial) / ($retorno['resultado_movimento_devolucao_sem_impostos'][$i] + $prepago)) * 100;
            }
            if ($custo_contabil > 0){
                $retorno['margem_contabil'][$i] = (($retorno['resultado_movimento_devolucao'][$i] - $custo_contabil) / $retorno['resultado_movimento_devolucao'][$i]) * 100;
                $retorno['margem_contabil_prepago'][$i] = ((($retorno['resultado_movimento_devolucao'][$i] + $prepago) - $custo_contabil) / ($retorno['resultado_movimento_devolucao'][$i] + $prepago)) * 100;
            }
        }

        $index_anterior = '';
        if(isset($fields['prepago'])){
            $faturado_lucro_prejuizo = $retorno['resultado_prepago'];
        }else{
            $faturado_lucro_prejuizo = $retorno['resultado_movimento_devolucao'];
        }
        foreach($faturado_lucro_prejuizo as $index => $value){
            if(isset($fields['prepago'])){
                $custo_contabil_lucro_prejuizo = empty($retorno['custo_contabil_prepago'][$index])? 0 : $retorno['custo_contabil_prepago'][$index];
            }else{
                $custo_contabil_lucro_prejuizo = empty($retorno['custo_contabil'][$index])? 0 : $retorno['custo_contabil'][$index];
            }
            
            $despesas_operacionais_lucro_prejuizo = empty($retorno['despesas_operacionais'][$index])? 0 : $retorno['despesas_operacionais'][$index];
            $outras_receita_lucro_prejuizo = empty($retorno['despesas_outras_receitas'][$index])? 0 : $retorno['despesas_outras_receitas'][$index];
            $outras_despesas_lucro_prejuizo = empty($retorno['despesas_outras'][$index])? 0 : $retorno['despesas_outras'][$index];
            $despesas_indedutivesis_lucro_prejuizo = empty($retorno['despesas_indedutivesis'][$index])? 0 : $retorno['despesas_indedutivesis'][$index];
            $despesas_imposto_renda_contribuicao_social_lucro_prejuizo = empty($retorno['despesas_imposto_renda_contribuicao_social'][$index])? 0 : $retorno['despesas_imposto_renda_contribuicao_social'][$index];

            $retorno['lucro_prejuizo'][$index] = $value - $custo_contabil_lucro_prejuizo - $despesas_operacionais_lucro_prejuizo - $outras_receita_lucro_prejuizo - $outras_despesas_lucro_prejuizo - $despesas_indedutivesis_lucro_prejuizo - $despesas_imposto_renda_contribuicao_social_lucro_prejuizo;

            if(!empty($index_anterior)){
                $retorno['lucro_prejuizo_acumulado'][$index] = $retorno['lucro_prejuizo_acumulado'][$index_anterior] + $retorno['lucro_prejuizo'][$index];
            }else{
                $retorno['lucro_prejuizo_acumulado'][$index] = $retorno['lucro_prejuizo'][$index];
            }

            $index_anterior = $index;
        }
        $result = [];
        $i = 0;
        foreach($retorno as $key => $value){
            if($key == 'movimento'){
                $result[$key]['origem'] = 'Faturamento Movimentação';
            }else if($key == 'devolucao'){
                $result[$key]['origem'] = 'Devolução (-)';
            }else if($key == 'desconto'){
                $result[$key]['origem'] = 'Desconto (-)';
            }else if($key == 'frete'){
                $result[$key]['origem'] = 'Frete (+)';
            }else if($key == 'seguro'){
                $result[$key]['origem'] = 'Seguro (+)';
            }else if($key == 'ipi'){
                $result[$key]['origem'] = 'IPI (+)';
            }else if($key == 'prepago'){
                $result[$key]['origem'] = 'Valor Pre-pago venda (+)';
            }else if($key == 'resultado_movimento_devolucao'){
                $result[$key]['origem'] = '<b>Resultado</b>';
            }else if($key == 'resultado_prepago'){
                $result[$key]['origem'] = '<b>Resultado + Pre-pago</b>';
            }else if($key == 'margem_medio_gerencial'){
                $result[$key]['origem'] = '<b>Margem - Médio Gerencial</b>';
            }else if($key == 'margem_gerencial'){
                $result[$key]['origem'] = '<b>Margem - Gerencial</b>';
            }else if($key == 'margem_contabil'){
                $result[$key]['origem'] = '<b>Margem - Contábil</b>';
            }else if($key == 'margem_medio_gerencial_prepago'){
                $result[$key]['origem'] = '<b>Margem - Médio Gerencial + Pre-pago</b>';
            }else if($key == 'margem_gerencial_prepago'){
                $result[$key]['origem'] = '<b>Margem - Gerencial + Pre-pago</b>';
            }else if($key == 'margem_contabil_prepago'){
                $result[$key]['origem'] = '<b>Margem - Contábil + Pre-pago</b>';
            }else if($key == 'venda_portal'){
                $result[$key]['origem'] = 'Faturamento Portal';
            }else if($key == 'custo_medio_gerencial'){
                $result[$key]['origem'] = 'CMV - Médio Gerencial';
            }else if($key == 'custo_gerencial'){
                $result[$key]['origem'] = 'CMV - Gerencial';
            }else if($key == 'custo_contabil'){
                $result[$key]['origem'] = 'CMV - Contábil';
            }else if($key == 'custo_medio_gerencial_prepago'){
                $result[$key]['origem'] = 'CMV - Médio Gerencial';
            }else if($key == 'custo_gerencial_prepago'){
                $result[$key]['origem'] = 'CMV - Gerencial';
            }else if($key == 'custo_contabil_prepago'){
                $result[$key]['origem'] = 'CMV - Contábil';
            }else if($key == 'pis'){
                $result[$key]['origem'] ='<a href="#"  data-toggle="tooltip" data-html="true" data-ano='.$fields['ano'].' data-codigo_conta="'.encrypt($this->codigo_contabil_pis).'" title="Detalhes" data-title="PIS '.$fields['ano'].'" onclick="abriModalDespesaArrayContas($(this))">PIS (-)</a>';
            }else if($key == 'cofins'){
                $result[$key]['origem'] ='<a href="#"  data-toggle="tooltip" data-html="true" data-ano='.$fields['ano'].' data-codigo_conta="'.encrypt($this->codigo_contabil_cofins).'" title="Detalhes" data-title="COFINS '.$fields['ano'].'" onclick="abriModalDespesaArrayContas($(this))">COFINS (-)</a>';
            }else if($key == 'icms'){
                $result[$key]['origem'] ='<a href="#"  data-toggle="tooltip" data-html="true" data-ano='.$fields['ano'].' data-codigo_conta="'.encrypt($this->codigo_contabil_icms).'" title="Detalhes" data-title="ICMS '.$fields['ano'].'" onclick="abriModalDespesaArrayContas($(this))">ICMS (-)</a>';
            }else if($key == 'icms_beneficio'){
                $result[$key]['origem'] = 'ICMS (+) Beneficio RO + TO';
            }else if($key == 'despesas_operacionais'){
                $result[$key]['origem'] ='<a href="#"  data-toggle="tooltip" data-html="true" data-ano='.$fields['ano'].' data-codigo_conta="5" title="Detalhes" data-title="Despesas Operacionais '.$fields['ano'].'" onclick="abriModalDespesa($(this))">Despesas Operacionais</a>';
            }else if($key == 'despesas_outras_receitas'){
                $result[$key]['origem'] ='<a href="#"  data-toggle="tooltip" data-html="true" data-ano='.$fields['ano'].' data-codigo_conta="6" title="Detalhes" data-title="Outras Receitas '.$fields['ano'].'" onclick="abriModalDespesa($(this))">Outras Receitas</a>';
            }else if($key == 'despesas_outras'){
                $result[$key]['origem'] ='<a href="#"  data-toggle="tooltip" data-html="true" data-ano='.$fields['ano'].' data-codigo_conta="7" title="Detalhes" data-title="Outras Despesas '.$fields['ano'].'" onclick="abriModalDespesa($(this))">Outras Despesas</a>';
            }else if($key == 'despesas_indedutivesis'){
                $result[$key]['origem'] ='<a href="#"  data-toggle="tooltip" data-html="true" data-ano='.$fields['ano'].' data-codigo_conta="8" title="Detalhes" data-title="Despesas Indedutíveis '.$fields['ano'].'" onclick="abriModalDespesa($(this))">Despesas Indedutíveis</a>';
            }else if($key == 'despesas_imposto_renda_contribuicao_social'){
                $result[$key]['origem'] ='<a href="#"  data-toggle="tooltip" data-html="true" data-ano='.$fields['ano'].' data-codigo_conta="9" title="Detalhes" data-title="Despesas Operacionais '.$fields['ano'].'" onclick="abriModalDespesa($(this))">Imposto de Renda PJ e Contribuição Social</a>';
            }else if($key == 'lucro_prejuizo'){
                if(isset($fields['prepago'])){
                    $result[$key]['origem'] = 'Lucro/Prejuízo<div><a href="#" class="btn-informacao-sem-width" data-toggle="tooltip" data-placement="top" title="" data-original-title="Cálculo: (Resultado + Pré-Pago) - (CMV Contábil + Pré-Pago) - Despesas Operacionais - Outras Receitas - Outras Despesas - Despesas Indedutíveis - Imposto de Renda PJ e Contribuição Social"></a></div>';
                }else{
                    $result[$key]['origem'] = 'Lucro/Prejuízo<div><a href="#" class="btn-informacao-sem-width" data-toggle="tooltip" data-placement="top" title="" data-original-title="Cálculo: Resultado - CMV Contábil - Despesas Operacionais - Outras Receitas - Outras Despesas - Despesas Indedutíveis - Imposto de Renda PJ e Contribuição Social"></a></div>';
                }
            }else if($key == 'lucro_prejuizo_acumulado'){
                $result[$key]['origem'] = 'Lucro/Prejuízo Acumulado';
            }else{
                continue;
            }
            $total = 0;
            for($mes = 1; $mes <= 12; $mes++){
                $key_mes = 'mes_' . (string) $mes;
                $result[$key][$key_mes] = '';
                if(isset($value[$mes])){
                    $valor = $value[$mes];
                    $total += $valor;
                    if(in_array($key, ['despesas_operacionais', 'despesas_outras', 'despesas_outras_receitas', 'despesas_indedutivesis', 'despesas_imposto_renda_contribuicao_social', 'lucro_prejuizo', 'lucro_prejuizo_acumulado', 'pis', 'icms', 'cofins'])){
                        $result[$key][$key_mes] = empty($valor)? '' : parserValor($valor); 
                    }else{
                        $result[$key][$key_mes] = $this->createLink($key, $result[$key]['origem'], $mes, $valor, $fields);
                    }
                    
                }
            }

            if($key == 'lucro_prejuizo_acumulado'){
                $result[$key]['total'] = '';
            }else{
                $result[$key]['total'] = parserValor($total);
            }
        }
        unset($retorno);

        foreach($result as $key => $value){
            if($key == 'margem_gerencial' || $key == 'margem_gerencial_prepago' || $key == 'margem_medio_gerencial' || $key == 'margem_contabil' || $key == 'margem_contabil_prepago' || $key == 'margem_contabil_prepago'|| $key == 'margem_medio_gerencial_prepago'){
                $venda_portal = parserNumber($result['resultado_movimento_devolucao']['total']);
                $prepago = parserNumber($result['prepago']['total']);

                $custo_medio_gerencial = parserNumber($result['custo_medio_gerencial']['total']);
                $custo_contabil = parserNumber($result['custo_contabil']['total']);
                $custo_gerencial = parserNumber($result['custo_gerencial']['total']);

                if($key == 'margem_medio_gerencial' && $custo_medio_gerencial > 0){
                    $result['margem_medio_gerencial']['total'] = parserValor((($venda_portal - $custo_medio_gerencial) / $venda_portal) * 100);
                }
                if ($key == 'margem_contabil' && $custo_contabil > 0){
                    $result['margem_contabil']['total'] = parserValor((($venda_portal - $custo_contabil) / $venda_portal) * 100);
                }
                if($key == 'margem_gerencial' && $custo_gerencial > 0){
                    $result['margem_gerencial']['total'] = parserValor((($venda_portal - $custo_gerencial) / $venda_portal) * 100);
                }

                if($key == 'margem_medio_gerencial_prepago' && $custo_medio_gerencial > 0){
                    $result['margem_medio_gerencial_prepago']['total'] = parserValor(((($venda_portal + $prepago) - $custo_medio_gerencial) / ($venda_portal + $prepago)) * 100);
                }
                if ($key == 'margem_contabil_prepago' && $custo_contabil > 0){
                    $result['margem_contabil_prepago']['total'] = parserValor(((($venda_portal + $prepago) - $custo_contabil) / ($venda_portal + $prepago)) * 100);
                }
                if($key == 'margem_gerencial_prepago' && $custo_gerencial > 0){
                    $result['margem_gerencial_prepago']['total'] = parserValor(((($venda_portal + $prepago) - $custo_gerencial) / ($venda_portal + $prepago)) * 100);
                }
            }
        }

        foreach($result as $key => $value){
            if($key == 'venda_portal'){
                unset($result[$key]);
                continue;
            }
            if(!in_array($key, ['despesas_operacionais', 'despesas_outras', 'despesas_outras_receitas', 'despesas_indedutivesis', 'despesas_imposto_renda_contribuicao_social', 'lucro_prejuizo', 'lucro_prejuizo_acumulado'])){
                $result[$key]['total'] = $this->createLink($key, 'Total', 'total', $result[$key]['total'], $fields);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $this->tratarFiltro($result, $fields)
        ]);

    }

    private function createLink($chave, $title, $mes, $valor, $fields){
        $retorno = '';
        if(empty($valor) || $valor == '0,00'){
            return '';
        }
        if($mes !== 'total'){
            $valor = parserValor($valor);
        }

        if(
            $chave == 'margem_gerencial' ||
            $chave == 'margem_medio_gerencial' ||
            $chave == 'margem_contabil' ||
            $chave == 'margem_gerencial_prepago' ||
            $chave == 'margem_medio_gerencial_prepago' ||
            $chave == 'margem_contabil_prepago'
        ){
            $valor .= ' %';
        }
        $hash = encrypt($fields);
        if(
            $chave == 'margem_gerencial' ||
            $chave == 'margem_medio_gerencial' ||
            $chave == 'margem_contabil' ||
            $chave == 'margem_gerencial_prepago' ||
            $chave == 'margem_medio_gerencial_prepago' ||
            $chave == 'margem_contabil_prepago'
        ){
            $retorno .= $valor;
        }else{
            $retorno .= "<a href=\"#\" onclick=\"aberturaMes('Abertura: {$title} - mês: {$mes} ', '{$mes}', '{$chave}', '{$hash}');\" data-html=\"true\" data-toggle=\"tooltip\" title=\"{$title}\" >{$valor}</a>";
        }
        return $retorno;
    }

    public function modalAbertura(Request $request){
        $fields = $request->only(['mes', 'chave', 'hash']);
        $filtro = decrypt($fields['hash']);
        $mes = intval($fields['mes']);
        unset($fields['hash']);
        $retorno = [];
        switch ($fields['chave']) {
            case 'movimento':
                $retorno = $this->mostrarMovimento($filtro, $mes, 'movimento', 'faturamento');
            break;
            case 'ipi':
                $retorno = $this->mostrarMovimento($filtro, $mes, 'ipi', 'faturamento');
            break;
            case 'seguro':
                $retorno = $this->mostrarMovimento($filtro, $mes, 'seguro', 'faturamento');
            break;
            case 'desconto':
                $retorno = $this->mostrarMovimento($filtro, $mes, 'desconto', 'faturamento');
            break;
            case 'frete':
                $retorno = $this->mostrarMovimento($filtro, $mes, 'frete', 'faturamento');
            break;
            case 'resultado_movimento_devolucao':
                $retorno = $this->mostrarMovimento($filtro, $mes, '', '');
            break;
            case 'devolucao':
                $retorno = $this->mostrarMovimento($filtro, $mes, '', 'devolucao');
            break;
            case 'venda_portal':
                $retorno = $this->mostrarMovimento($filtro, $mes, '', '');
            break;
            case 'prepago':
                $retorno = $this->mostrarPrepago($filtro, $mes);
            break;
            case 'resultado_prepago':
                $retorno = $this->mostrarResultadoPrepago($filtro, $mes);
            break;
            case 'custo_gerencial':
            case 'custo_medio_gerencial':
            case 'custo_contabil':
            case 'custo_gerencial_prepago':
            case 'custo_medio_gerencial_prepago':
            case 'custo_contabil_prepago':
                $retorno = $this->mostrarCusto($filtro, $mes, $fields['chave']);
            break;
        }
        return $retorno;
    }

    private function mostrarMovimento($filtro, $mes, $parametro, $parametro_faturamento){
        set_time_limit(300);
        ini_set('memory_limit','10024M');
		$clientes_exluir = ClienteNasajon::select('codigo')
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
        ->get();
        $clientes_exluir = $clientes_exluir->pluck('codigo')->toArray();

        $MovimentacaoObj = Movimentacao::with(['cliente', 'fornecedor']);

		if($parametro_faturamento == 'devolucao'){
			$MovimentacaoObj->whereIn('cfop', $this->cfop_devolucao);
		}
		if($parametro_faturamento == 'faturamento' || empty($parametro_faturamento)){
			$MovimentacaoObj->whereIn('cfop', $this->cfop_vendas);
		}        

        $MovimentacaoObj->selectRaw('estabelecimento, data_movimentacao, documento, cliente_codigo, cfop, aliquota, unidade, sum(preco * quantidade)::numeric(15,2) as preco, sum(preco_composicao * quantidade)::numeric(15,2) as preco_composicao, sum(ipi)::numeric(15,2) as ipi, sum(seguro)::numeric(15,2) as seguro, sum(desconto)::numeric(15,2) as desconto, sum(frete)::numeric(15,2) as frete, sum(quantidade) as quantidade');
        $MovimentacaoObj->groupBy(DB::raw('estabelecimento, data_movimentacao, documento, cliente_codigo, cfop, aliquota, unidade'));
        $MovimentacaoObj->whereNotIn('cliente_codigo', $clientes_exluir);

        if(!empty($parametro_faturamento)){
            if($parametro_faturamento == 'devolucao'){
                $MovimentacaoObj->whereIn('cfop', $this->cfop_devolucao);
            }
            if($parametro_faturamento == 'faturamento' || empty($parametro_faturamento)){
                $MovimentacaoObj->whereIn('cfop', $this->cfop_vendas);
            }
        }

        $MovimentacaoObj->whereRaw("EXTRACT(year from data_movimentacao) = '{$filtro['ano']}'");
        if($mes != 'total'){
            $mes = str_pad($mes, 2, "0", STR_PAD_LEFT);
            $MovimentacaoObj->whereRaw("EXTRACT(month from data_movimentacao) = '{$mes}'");
        }
        
        if(!empty($filtro['estabelecimento'])){
            $estabelecimento = str_pad($filtro['estabelecimento'], 2, "0", STR_PAD_LEFT);
            $MovimentacaoObj->where("estabelecimento", $estabelecimento);
        }
        if(!empty($parametro)){
            if($parametro != 'movimento'){
                $MovimentacaoObj->whereRaw("{$parametro} > 0");
            }
        }

        if(!empty($filtro['grupo']) || !empty($filtro['subgrupo']) || !empty($filtro['marca']) || !empty($filtro['linha'])){
			if(!empty($filtro['grupo'])){
				$MovimentacaoObj->where("grupo", 'ilike' , $filtro['grupo']);
			}
			if(!empty($filtro['subgrupo'])){
				$MovimentacaoObj->where("subgrupo", 'ilike', $filtro['subgrupo']);
			}
			if(!empty($filtro['marca'])){
				$MovimentacaoObj->where("marca", 'ilike', $filtro['marca']);
			}
			if(!empty($filtro['linha'])){
				$MovimentacaoObj->where("linha", 'ilike', $filtro['linha']);
			}
        }

        $Movimentacao = $MovimentacaoObj->get();
        unset($MovimentacaoObj);
        unset($clientes_exluir);

        $movimentos = [];
        $Movimentacao->each(function($movimento) use (&$movimentos){
            $cliente_fornecedor = '';
            if(!empty($movimento->cliente)){
                $cliente_fornecedor = $movimento->cliente->nome . ' - ' . $movimento->cliente->cpf_cnpj;
            }
            if(!empty($movimento->fornecedor)){
                $cliente_fornecedor = $movimento->fornecedor->nome . ' - ' . $movimento->fornecedor->cnpj_cpf;
            }

            $documento = $movimento->documento;
            if(Carbon::parse($movimento->data_movimentacao)->gte('2019-07-07') && !empty($documento)){
                if(in_array($movimento->cfop, $this->cfop_devolucao)){
                    $documento = "<a class='exibir-nota-entrada' href='#' data-documento='" . $movimento->documento . "' data-estabelecimento='" . $movimento->estabelecimento . "'>" . $movimento->documento . "</a>";
                }else if(in_array($movimento->cfop, $this->cfop_vendas)){
                    $documento = "<a class='exibir-nota' href='#' data-documento='" . $movimento->documento . "' data-estabelecimento='" . $movimento->estabelecimento . "'>" . $movimento->documento . "</a>";
                }
            }
            else{
                $documento = "<a class='exibir-nota-prologos' href='#' data-estabelecimento='" . $movimento->estabelecimento . "' data-documento='" . $movimento->documento . "' data-data_movimentacao='" . $movimento->data_movimentacao . "'>" . $movimento->documento . "</a>";
            }
            $movimentos[] = [
                'estabelecimento' => $this->estabelecimentos[intval($movimento->estabelecimento)],
                'data' => parserData($movimento->data_movimentacao),
                'documento' => $documento,
                'cliente_fornecedor' => $cliente_fornecedor,
                'cfop' => $movimento->cfop,
                'preco' => parserValor($movimento->preco),
                'preco_composicao' => parserValor($movimento->preco_composicao),
                'aliquota' => $movimento->aliquota,
                'unidade' => $movimento->unidade,
                'ipi' => parserValor($movimento->ipi),
                'seguro' => parserValor($movimento->seguro),
                'desconto' => parserValor($movimento->desconto),
                'frete' => parserValor($movimento->frete),
                'quantidade' => parserValor($movimento->quantidade),
            ];
        });
        return view('programs.faturamento_cmv.dialog.movimento')->with(['movimentos' => $movimentos]);
    }


    private function mostrarPrepago($filtro, $mes){
        set_time_limit(300);
        ini_set('memory_limit','10024M');
		$clientes_exluir = ClienteNasajon::select('codigo')
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
        ->get();
        $clientes_exluir = $clientes_exluir->pluck('codigo')->toArray();

        $MovimentacaoObj = Movimentacao::with(['cliente', 'fornecedor']);
		$MovimentacaoObj->whereIn('cfop', array_merge($this->cfop_devolucao, $this->cfop_vendas));
        $MovimentacaoObj->where('preco_prepago', '>', '0');
        $MovimentacaoObj->selectRaw('estabelecimento, data_movimentacao, documento, cliente_codigo, cfop, aliquota, unidade, sum(preco * quantidade) as preco, sum(preco_composicao * quantidade) as preco_composicao, sum(ipi) as ipi, sum(seguro) as seguro, sum(desconto) as desconto, sum(frete) as frete, sum(preco_prepago) as preco_prepago, sum(quantidade) as quantidade');
        $MovimentacaoObj->groupBy(DB::raw('estabelecimento, data_movimentacao, documento, cliente_codigo, cfop, aliquota, unidade'));
        $MovimentacaoObj->whereNotIn('cliente_codigo', $clientes_exluir);
        $MovimentacaoObj->whereRaw("EXTRACT(year from data_movimentacao) = '{$filtro['ano']}'");
        if($mes != 'total'){
            $mes = str_pad($mes, 2, "0", STR_PAD_LEFT);
            $MovimentacaoObj->whereRaw("EXTRACT(month from data_movimentacao) = '{$mes}'");
        }
        
        if(!empty($filtro['estabelecimento'])){
            $estabelecimento = str_pad($filtro['estabelecimento'], 2, "0", STR_PAD_LEFT);
            $MovimentacaoObj->where("estabelecimento", $estabelecimento);
        }

        if(!empty($filtro['grupo']) || !empty($filtro['subgrupo']) || !empty($filtro['marca']) || !empty($filtro['linha'])){
			if(!empty($filtro['grupo'])){
				$MovimentacaoObj->where("grupo", 'ilike' , $filtro['grupo']);
			}
			if(!empty($filtro['subgrupo'])){
				$MovimentacaoObj->where("subgrupo", 'ilike', $filtro['subgrupo']);
			}
			if(!empty($filtro['marca'])){
				$MovimentacaoObj->where("marca", 'ilike', $filtro['marca']);
			}
			if(!empty($filtro['linha'])){
				$MovimentacaoObj->where("linha", 'ilike', $filtro['linha']);
			}
        }

        $Movimentacao = $MovimentacaoObj->get();
        unset($MovimentacaoObj);
        unset($clientes_exluir);

        $movimentos = [];
        $Movimentacao->each(function($movimento) use (&$movimentos){
            $cliente_fornecedor = '';
            if(!empty($movimento->cliente)){
                $cliente_fornecedor = $movimento->cliente->nome . ' - ' . $movimento->cliente->cpf_cnpj;
            }
            if(!empty($movimento->fornecedor)){
                $cliente_fornecedor = $movimento->fornecedor->nome . ' - ' . $movimento->fornecedor->cnpj_cpf;
            }

            $documento = $movimento->documento;
            if(Carbon::parse($movimento->data_movimentacao)->gte('2019-07-07') && !empty($documento)){
                if(in_array($movimento->cfop, $this->cfop_devolucao)){
                    $documento = "<a class='exibir-nota-entrada' href='#' data-documento='" . $movimento->documento . "' data-estabelecimento='" . $movimento->estabelecimento . "'>" . $movimento->documento . "</a>";
                }else if(in_array($movimento->cfop, $this->cfop_vendas)){
                    $documento = "<a class='exibir-nota' href='#' data-documento='" . $movimento->documento . "' data-estabelecimento='" . $movimento->estabelecimento . "'>" . $movimento->documento . "</a>";
                }
            }
            else{
                $documento = "<a class='exibir-nota-prologos' href='#' data-estabelecimento='" . $movimento->estabelecimento . "' data-documento='" . $movimento->documento . "' data-data_movimentacao='" . $movimento->data_movimentacao . "'>" . $movimento->documento . "</a>";
            }
            $movimentos[] = [
                'estabelecimento' => $this->estabelecimentos[intval($movimento->estabelecimento)],
                'data' => parserData($movimento->data_movimentacao),
                'documento' => $documento,
                'cliente_fornecedor' => $cliente_fornecedor,
                'cfop' => $movimento->cfop,
                'preco' => parserValor($movimento->preco),
                'preco_composicao' => parserValor($movimento->preco_composicao),
                'aliquota' => $movimento->aliquota,
                'unidade' => $movimento->unidade,
                'ipi' => parserValor($movimento->ipi),
                'seguro' => parserValor($movimento->seguro),
                'desconto' => parserValor($movimento->desconto),
                'frete' => parserValor($movimento->frete),
                'prepago' => parserValor($movimento->preco_prepago),
                'quantidade' => parserValor($movimento->quantidade),
            ];
        });
        return view('programs.faturamento_cmv.dialog.prepago')->with(['movimentos' => $movimentos]);
    }
    
    private function mostrarResultadoPrepago($filtro, $mes){
        set_time_limit(300);
        ini_set('memory_limit','10024M');
		$clientes_exluir = ClienteNasajon::select('codigo')
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
        ->get();
        $clientes_exluir = $clientes_exluir->pluck('codigo')->toArray();

        $MovimentacaoObj = Movimentacao::with(['cliente', 'fornecedor']);
		$MovimentacaoObj->whereIn('cfop', array_merge($this->cfop_devolucao, $this->cfop_vendas));
        $MovimentacaoObj->selectRaw('estabelecimento, data_movimentacao, documento, cliente_codigo, cfop, aliquota, unidade, sum(preco * quantidade) as preco, sum(preco_composicao * quantidade) as preco_composicao, sum(ipi) as ipi, sum(seguro) as seguro, sum(desconto) as desconto, sum(frete) as frete, sum(preco_prepago) as preco_prepago, sum(quantidade) as quantidade');
        $MovimentacaoObj->groupBy(DB::raw('estabelecimento, data_movimentacao, documento, cliente_codigo, cfop, aliquota, unidade'));
        $MovimentacaoObj->whereNotIn('cliente_codigo', $clientes_exluir);
        $MovimentacaoObj->whereRaw("EXTRACT(year from data_movimentacao) = '{$filtro['ano']}'");
        if($mes != 'total'){
            $mes = str_pad($mes, 2, "0", STR_PAD_LEFT);
            $MovimentacaoObj->whereRaw("EXTRACT(month from data_movimentacao) = '{$mes}'");
        }
        
        if(!empty($filtro['estabelecimento'])){
            $estabelecimento = str_pad($filtro['estabelecimento'], 2, "0", STR_PAD_LEFT);
            $MovimentacaoObj->where("estabelecimento", $estabelecimento);
        }

        if(!empty($filtro['grupo']) || !empty($filtro['subgrupo']) || !empty($filtro['marca']) || !empty($filtro['linha'])){
			if(!empty($filtro['grupo'])){
				$MovimentacaoObj->where("grupo", 'ilike' , $filtro['grupo']);
			}
			if(!empty($filtro['subgrupo'])){
				$MovimentacaoObj->where("subgrupo", 'ilike', $filtro['subgrupo']);
			}
			if(!empty($filtro['marca'])){
				$MovimentacaoObj->where("marca", 'ilike', $filtro['marca']);
			}
			if(!empty($filtro['linha'])){
				$MovimentacaoObj->where("linha", 'ilike', $filtro['linha']);
			}
        }

        $Movimentacao = $MovimentacaoObj->get();
        unset($MovimentacaoObj);
        unset($clientes_exluir);

        $movimentos = [];
        $Movimentacao->each(function($movimento) use (&$movimentos){
            $cliente_fornecedor = '';
            if(!empty($movimento->cliente)){
                $cliente_fornecedor = $movimento->cliente->nome . ' - ' . $movimento->cliente->cpf_cnpj;
            }
            if(!empty($movimento->fornecedor)){
                $cliente_fornecedor = $movimento->fornecedor->nome . ' - ' . $movimento->fornecedor->cnpj_cpf;
            }

            $documento = $movimento->documento;
            if(Carbon::parse($movimento->data_movimentacao)->gte('2019-07-07') && !empty($documento)){
                if(in_array($movimento->cfop, $this->cfop_devolucao)){
                    $documento = "<a class='exibir-nota-entrada' href='#' data-documento='" . $movimento->documento . "' data-estabelecimento='" . $movimento->estabelecimento . "'>" . $movimento->documento . "</a>";
                }else if(in_array($movimento->cfop, $this->cfop_vendas)){
                    $documento = "<a class='exibir-nota' href='#' data-documento='" . $movimento->documento . "' data-estabelecimento='" . $movimento->estabelecimento . "'>" . $movimento->documento . "</a>";
                }
            }
            else{
                $documento = "<a class='exibir-nota-prologos' href='#' data-estabelecimento='" . $movimento->estabelecimento . "' data-documento='" . $movimento->documento . "' data-data_movimentacao='" . $movimento->data_movimentacao . "'>" . $movimento->documento . "</a>";
            }
            $movimentos[] = [
                'estabelecimento' => $this->estabelecimentos[intval($movimento->estabelecimento)],
                'data' => parserData($movimento->data_movimentacao),
                'documento' => $documento,
                'cliente_fornecedor' => $cliente_fornecedor,
                'cfop' => $movimento->cfop,
                'preco' => parserValor($movimento->preco),
                'preco_composicao' => parserValor($movimento->preco_composicao),
                'aliquota' => $movimento->aliquota,
                'unidade' => $movimento->unidade,
                'ipi' => parserValor($movimento->ipi),
                'seguro' => parserValor($movimento->seguro),
                'desconto' => parserValor($movimento->desconto),
                'frete' => parserValor($movimento->frete),
                'prepago' => parserValor($movimento->preco_prepago),
                'quantidade' => parserValor($movimento->quantidade),
            ];
        });
        return view('programs.faturamento_cmv.dialog.resultado_prepago')->with(['movimentos' => $movimentos]);
    }

    private function mostrarCusto($filtro, $mes, $coluna){
        set_time_limit(300);
        ini_set('memory_limit','10024M');
		$clientes_exluir = ClienteNasajon::select('codigo')
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
        ->get();
        $clientes_exluir = $clientes_exluir->pluck('codigo')->toArray();

        $MovimentacaoObj = Movimentacao::select();
		$MovimentacaoObj->whereIn('cfop', array_merge($this->cfop_devolucao, $this->cfop_vendas));
        $MovimentacaoObj->select('grupo', 'subgrupo', 'marca', 'linha', 'segmento', 'cfop', DB::Raw('sum((quantidade * preco)::numeric(15,2))::numeric(15,2) as valor, sum((quantidade * custo_pcmn)::numeric(15,2))::numeric(15,2) as medio_gerencial, sum((quantidade * preco_pcmn)::numeric(15,2))::numeric(15,2) as gerencial, sum((quantidade * custo_sem_imposto)::numeric(15,2))::numeric(15,2) as contabil, sum(frete)::numeric(15,2) as frete, sum(ipi)::numeric(15,2) as ipi, sum(desconto)::numeric(15,2) as desconto, sum(seguro)::numeric(15,2) as seguro, sum(preco_prepago)::numeric(15,2) as prepago, sum(quantidade)::numeric(15, 2) as quantidade'));
        $MovimentacaoObj->groupBy('grupo', 'subgrupo', 'marca', 'linha', 'segmento', 'cfop');
        $MovimentacaoObj->orderBy('grupo', 'subgrupo', 'marca', 'linha', 'segmento');
        $MovimentacaoObj->whereNotIn('cliente_codigo', $clientes_exluir);
        $MovimentacaoObj->whereRaw("EXTRACT(year from data_movimentacao) = '{$filtro['ano']}'");
        if($mes != 'total'){
            $mes = str_pad($mes, 2, "0", STR_PAD_LEFT);
            $MovimentacaoObj->whereRaw("EXTRACT(month from data_movimentacao) = '{$mes}'");
        }
        
        if(!empty($filtro['estabelecimento'])){
            $estabelecimento = str_pad($filtro['estabelecimento'], 2, "0", STR_PAD_LEFT);
            $MovimentacaoObj->where("estabelecimento", $estabelecimento);
        }

        if(!empty($filtro['grupo']) || !empty($filtro['subgrupo']) || !empty($filtro['marca']) || !empty($filtro['linha'])){
			if(!empty($filtro['grupo'])){
				$MovimentacaoObj->where("grupo", 'ilike' , $filtro['grupo']);
			}
			if(!empty($filtro['subgrupo'])){
				$MovimentacaoObj->where("subgrupo", 'ilike', $filtro['subgrupo']);
			}
			if(!empty($filtro['marca'])){
				$MovimentacaoObj->where("marca", 'ilike', $filtro['marca']);
			}
			if(!empty($filtro['linha'])){
				$MovimentacaoObj->where("linha", 'ilike', $filtro['linha']);
			}
        }

        $Movimentacao = $MovimentacaoObj->get();
        unset($MovimentacaoObj);
        unset($clientes_exluir);
        $movimentos = [];
        $Movimentacao->each(function($movimento) use (&$movimentos){
            $key = $movimento->grupo.''.$movimento->subgrupo.''.$movimento->marca.''.$movimento->linha.''.$movimento->segmento;
            if(in_array($movimento->cfop, $this->cfop_vendas)){
                if(!isset($movimentos[$key])){
                    $movimentos[$key] = [
                        'grupo' => $movimento->grupo,
                        'subgrupo' => $movimento->subgrupo,
                        'marca' => $movimento->marca,
                        'linha' => $movimento->linha,
                        'segmento' => $movimento->segmento,
                        'quantidade' => 0,
                        'movimento' => 0,
                        'devolucao' => 0,
                        'frete' => 0,
                        'ipi' => 0,
                        'seguro' => 0,
                        'desconto' => 0,
                        'resultado_movimento_devolucao' => 0,
                        'venda_portal' => 0,
                        'resultado_dif_venda' => 0,
                        'custo_medio_gerencial' => 0,
                        'margem_medio_gerencial' => 0,
                        'custo_gerencial' => 0,
                        'margem_gerencial' => 0,
                        'custo_contabil' => 0,
                        'margem_contabil' => 0,
                        'prepago' => 0,
                        'resultado_prepago' => 0,
                        'custo_medio_gerencial_prepago' => 0,
                        'margem_medio_gerencial_prepago' => 0,
                        'custo_gerencial_prepago' => 0,
                        'margem_gerencial_prepago' => 0,
                        'custo_contabil_prepago' => 0,
                        'margem_contabil_prepago' => 0,
                        'valor_medio' => 0,
                        'valor_medio_prepago' => 0,
                    ];
                }
                $movimentos[$key]['quantidade'] += (float) $movimento->quantidade;
                $movimentos[$key]['movimento'] += (float) $movimento->valor;
                $movimentos[$key]['custo_medio_gerencial'] += (float) $movimento->medio_gerencial;
                $movimentos[$key]['custo_gerencial'] += (float) $movimento->gerencial;
                $movimentos[$key]['custo_contabil'] += (float) $movimento->contabil;
                $movimentos[$key]['frete'] += (float) $movimento->frete;
                $movimentos[$key]['ipi'] += (float) $movimento->ipi;
                $movimentos[$key]['desconto'] += (float) $movimento->desconto;
                $movimentos[$key]['seguro'] += (float) $movimento->seguro;
                $movimentos[$key]['prepago'] += (float) $movimento->prepago;
                $movimentos[$key]['venda_portal'] += (float) ($movimento->valor + $movimento->frete + $movimento->seguro + $movimento->ipi) - $movimento->desconto;
            }else if(in_array($movimento->cfop, $this->cfop_devolucao)){
                $key = 'devolucao';
                if(!isset($movimentos[$key])){
                    $movimentos[$key] = [
                        'grupo' => 'Devolução',
                        'subgrupo' => 'Devolução',
                        'marca' => 'Devolução',
                        'linha' => 'Devolução',
                        'segmento' => 'Devolução',
                        'quantidade' => 0,
                        'movimento' => 0,
                        'devolucao' => 0,
                        'frete' => 0,
                        'ipi' => 0,
                        'seguro' => 0,
                        'desconto' => 0,
                        'resultado_movimento_devolucao' => 0,
                        'venda_portal' => 0,
                        'resultado_dif_venda' => 0,
                        'custo_medio_gerencial' => 0,
                        'margem_medio_gerencial' => 0,
                        'custo_gerencial' => 0,
                        'margem_gerencial' => 0,
                        'custo_contabil' => 0,
                        'margem_contabil' => 0,
                        'prepago' => 0,
                        'resultado_prepago' => 0,
                        'custo_medio_gerencial_prepago' => 0,
                        'margem_medio_gerencial_prepago' => 0,
                        'custo_gerencial_prepago' => 0,
                        'margem_gerencial_prepago' => 0,
                        'custo_contabil_prepago' => 0,
                        'margem_contabil_prepago' => 0,
                        'valor_medio' => 0,
                        'valor_medio_prepago' => 0,
                    ];
                }
                $movimentos[$key]['quantidade'] += (float) $movimento->quantidade;
                $movimentos[$key]['devolucao'] += (float) ($movimento->valor + $movimento->frete + $movimento->seguro + $movimento->ipi) - $movimento->desconto;
            }
        });
		if(!isset($movimentos['devolucao'])){
			$movimentos['devolucao'] = [
				'grupo' => 'Devolução',
				'subgrupo' => 'Devolução',
				'marca' => 'Devolução',
				'linha' => 'Devolução',
                'segmento' => 'Devolução',
				'quantidade' => 0,
				'movimento' => 0,
				'devolucao' => 0,
				'frete' => 0,
				'ipi' => 0,
				'seguro' => 0,
				'desconto' => 0,
				'resultado_movimento_devolucao' => 0,
				'venda_portal' => 0,
				'resultado_dif_venda' => 0,
				'custo_medio_gerencial' => 0,
				'margem_medio_gerencial' => 0,
				'custo_gerencial' => 0,
				'margem_gerencial' => 0,
				'custo_contabil' => 0,
				'margem_contabil' => 0,
				'prepago' => 0,
				'resultado_prepago' => 0,
				'custo_medio_gerencial_prepago' => 0,
				'margem_medio_gerencial_prepago' => 0,
				'custo_gerencial_prepago' => 0,
				'margem_gerencial_prepago' => 0,
				'custo_contabil_prepago' => 0,
				'margem_contabil_prepago' => 0,
				'valor_medio' => 0,
				'valor_medio_prepago' => 0,
			];
		}
        unset($Movimentacao);
        foreach($movimentos as $key => $movimento){
            $movimento = $movimentos[$key]['movimento'] ?? 0;
            $devolucao = $movimentos[$key]['devolucao'] ?? 0;
            $quantidade = $movimentos[$key]['quantidade'] ?? 0;

            $custo_medio_gerencial = $movimentos[$key]['custo_medio_gerencial'] ?? 0;
            $movimentos[$key]['custo_medio_gerencial_prepago'] = $custo_medio_gerencial;

            $custo_gerencial = $movimentos[$key]['custo_gerencial'] ?? 0;
            $movimentos[$key]['custo_gerencial_prepago'] = $custo_gerencial;

            $custo_contabil = $movimentos[$key]['custo_contabil'] ?? 0;
            $movimentos[$key]['custo_contabil_prepago'] = $custo_contabil;

            $frete = $movimentos[$key]['frete'] ?? 0;
            $ipi = $movimentos[$key]['ipi'] ?? 0;
            $desconto = $movimentos[$key]['desconto'] ?? 0;
            $seguro = $movimentos[$key]['seguro'] ?? 0;
            $prepago = $movimentos[$key]['prepago'] ?? 0;

            $venda_portal = $movimentos[$key]['venda_portal'] ?? 0;
            $movimentos[$key]['venda_portal'] = $venda_portal;
            $venda_portal = $movimentos[$key]['venda_portal'] ?? 0;
            $movimentos[$key]['resultado_movimento_devolucao'] = ($movimento + $frete + $ipi + $seguro) - ($devolucao + $desconto);
            $movimentos[$key]['resultado_prepago'] = ($movimento + $frete + $ipi + $seguro + $prepago) - ($devolucao + $desconto);

			if($quantidade > 0){
				$movimentos[$key]['valor_medio'] = $movimentos[$key]['resultado_movimento_devolucao'] / $quantidade;
				$movimentos[$key]['valor_medio_prepago'] = $movimentos[$key]['resultado_prepago'] / $quantidade;
			}

            $movimentos[$key]['resultado_dif_venda'] = $venda_portal - $movimentos[$key]['resultado_movimento_devolucao'];
            if($custo_gerencial > 0){
                $movimentos[$key]['margem_gerencial'] = (($movimentos[$key]['resultado_movimento_devolucao'] - $custo_gerencial) / $movimentos[$key]['resultado_movimento_devolucao']) * 100;
                $movimentos[$key]['margem_gerencial_prepago'] = ((($movimentos[$key]['resultado_movimento_devolucao'] + $prepago) - $custo_gerencial) / ($movimentos[$key]['resultado_movimento_devolucao'] + $prepago)) * 100;
            }

            if($custo_medio_gerencial > 0){
                $movimentos[$key]['margem_medio_gerencial'] = (($movimentos[$key]['resultado_movimento_devolucao'] - $custo_medio_gerencial) / $movimentos[$key]['resultado_movimento_devolucao']) * 100;
                $movimentos[$key]['margem_medio_gerencial_prepago'] = ((($movimentos[$key]['resultado_movimento_devolucao'] + $prepago) - $custo_medio_gerencial) / ($movimentos[$key]['resultado_movimento_devolucao'] + $prepago)) * 100;
            }

            if ($custo_contabil > 0){
                $movimentos[$key]['margem_contabil'] = (($movimentos[$key]['resultado_movimento_devolucao'] - $custo_contabil) / $movimentos[$key]['resultado_movimento_devolucao']) * 100;
                $movimentos[$key]['margem_contabil_prepago'] = ((($movimentos[$key]['resultado_movimento_devolucao'] + $prepago) - $custo_contabil) / ($movimentos[$key]['resultado_movimento_devolucao'] + $prepago)) * 100;
            }
        }
        foreach($movimentos as $key => $movimento){
            foreach($movimento as $k => $value){
                if(!in_array($k, ['grupo', 'linha', 'marca', 'subgrupo', 'segmento'])){
                    // $movimentos[$key][$k] = parserValor($value);
                    if(in_array($k, ['margem_gerencial', 'margem_gerencial_prepago', 'margem_medio_gerencial', 'margem_medio_gerencial_prepago', 'margem_contabil', 'margem_contabil_prepago'])){
                        $movimentos[$key][$k] = parserValor($movimentos[$key][$k]).' %';
                    }
                }
            }
        }

        $texto_custo = '';
        switch($coluna){
            case 'custo_medio_gerencial':
            case 'custo_medio_gerencial_prepago':
                $texto_custo = 'Valor Médio Gerencial';
            break;
            case 'custo_gerencial':
            case 'custo_gerencial_prepago':
                $texto_custo = 'Valor Gerencial';
            break;
            case 'custo_contabil':
            case 'custo_contabil_prepago':
                $texto_custo = 'Valor Contábil';
            break;
        }
        $retorno = [];
        foreach($movimentos as $key => $movimento){
            foreach($movimento as $k => $value){
                if(in_array($k, ['grupo', 'linha', 'marca', 'subgrupo', 'quantidade', 'segmento'])){
                    $retorno[$key][$k] = $value;
                }
            }
            switch($coluna){
                case 'custo_gerencial':
                    $retorno[$key]['resultado'] = $movimento['resultado_movimento_devolucao'];
                    $retorno[$key]['custo'] = $movimento['custo_gerencial'];
                    $retorno[$key]['margem'] = $movimento['margem_gerencial'];
                    $retorno[$key]['valor_medio'] = $movimento['valor_medio'];
                break;
                case 'custo_medio_gerencial':
                    $retorno[$key]['resultado'] = $movimento['resultado_movimento_devolucao'];
                    $retorno[$key]['custo'] = $movimento['custo_medio_gerencial'];
                    $retorno[$key]['margem'] = $movimento['margem_medio_gerencial'];
                    $retorno[$key]['valor_medio'] = $movimento['valor_medio'];
                break;
                case 'custo_contabil':
                    $retorno[$key]['resultado'] = $movimento['resultado_movimento_devolucao'];
                    $retorno[$key]['custo'] = $movimento['custo_contabil'];
                    $retorno[$key]['margem'] = $movimento['margem_contabil'];
                    $retorno[$key]['valor_medio'] = $movimento['valor_medio'];
                break;
                case 'custo_medio_gerencial_prepago':
                    $retorno[$key]['resultado'] = $movimento['resultado_prepago'];
                    $retorno[$key]['custo'] = $movimento['custo_medio_gerencial_prepago'];
                    $retorno[$key]['margem'] = $movimento['margem_medio_gerencial_prepago'];
                    $retorno[$key]['valor_medio'] = $movimento['valor_medio_prepago'];
                break;
                case 'custo_gerencial_prepago':
                    $retorno[$key]['resultado'] = $movimento['resultado_prepago'];
                    $retorno[$key]['custo'] = $movimento['custo_gerencial_prepago'];
                    $retorno[$key]['margem'] = $movimento['margem_gerencial_prepago'];
                    $retorno[$key]['valor_medio'] = $movimento['valor_medio_prepago'];
                break;
                case 'custo_contabil_prepago':
                    $retorno[$key]['resultado'] = $movimento['resultado_prepago'];
                    $retorno[$key]['custo'] = $movimento['custo_contabil_prepago'];
                    $retorno[$key]['margem'] = $movimento['margem_contabil_prepago'];
                    $retorno[$key]['valor_medio'] = $movimento['valor_medio_prepago'];
                break;
            }
        }
        foreach($retorno as $key => $value){
            if(
                $key == 'devolucao'
            ){
                $retorno[$key]['quantidade'] = parserValor($retorno[$key]['quantidade']);
                $retorno[$key]['resultado'] = str_replace('-', '', parserValor($retorno[$key]['resultado']));
            }
        }
		$filtro['mes'] = $mes;
		$filtro['coluna'] = $coluna;
        return view('programs.faturamento_cmv.dialog.resultados')->with(['movimentos' => $retorno, 'custo' => $texto_custo, 'hash' => encrypt($filtro)]);

    }

	public function filtroModal(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','10024M');
		
		$fields = $request->only(['estabelecimento', 'codigo', 'grupo', 'subgrupo', 'marca', 'linha', 'segmento', 'hash']);

        $filtro = decrypt($fields['hash']);
		$mes = $filtro['mes'];
		$coluna = $filtro['coluna'];
		
		$clientes_exluir = ClienteNasajon::select('codigo')
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
        ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
        ->get();
        $clientes_exluir = $clientes_exluir->pluck('codigo')->toArray();

        $MovimentacaoObj = Movimentacao::select();
		$MovimentacaoObj->whereIn('cfop', array_merge($this->cfop_devolucao, $this->cfop_vendas));
		$groupBy = [
			'cfop'
		];
		$select  = [
			'cfop',
			DB::Raw('sum((quantidade * preco)::numeric(15,2))::numeric(15,2) as valor, sum((quantidade * custo_pcmn)::numeric(15,2))::numeric(15,2) as medio_gerencial, sum((quantidade * preco_pcmn)::numeric(15,2))::numeric(15,2) as gerencial, sum((quantidade * custo_sem_imposto)::numeric(15,2))::numeric(15,2) as contabil, sum(frete)::numeric(15,2) as frete, sum(ipi)::numeric(15,2) as ipi, sum(desconto)::numeric(15,2) as desconto, sum(seguro)::numeric(15,2) as seguro, sum(preco_prepago)::numeric(15,2) as prepago, sum(quantidade)::numeric(15, 2) as quantidade')
		];
        if(!empty($fields['estabelecimento'])){
			$select[] = 'estabelecimento';
			$groupBy[] = 'estabelecimento';
		}
        if(!empty($fields['codigo'])){
			$select[] = 'produto_codigo';
			$groupBy[] = 'produto_codigo';
		}
		if(!empty($fields['grupo'])){
			$select[] = 'grupo';
			$groupBy[] = 'grupo';
		}
		if(!empty($fields['subgrupo'])){
			$select[] = 'subgrupo';
			$groupBy[] = 'subgrupo';
		}
		if(!empty($fields['marca'])){
			$select[] = 'marca';
			$groupBy[] = 'marca';
		}
		if(!empty($fields['linha'])){
			$select[] = 'linha';
			$groupBy[] = 'linha';
		}
        if(!empty($fields['segmento'])){
			$select[] = 'segmento';
			$groupBy[] = 'segmento';
		}

        $MovimentacaoObj->select($select);
        $MovimentacaoObj->groupBy($groupBy);
        $MovimentacaoObj->groupBy('grupo', 'subgrupo', 'marca', 'linha', 'segmento', 'cfop');
        $MovimentacaoObj->orderBy('grupo', 'subgrupo', 'marca', 'linha', 'segmento');
        $MovimentacaoObj->whereNotIn('cliente_codigo', $clientes_exluir);
        $MovimentacaoObj->whereRaw("EXTRACT(year from data_movimentacao) = '{$filtro['ano']}'");
        if($mes != 'total'){
            $mes = str_pad($mes, 2, "0", STR_PAD_LEFT);
            $MovimentacaoObj->whereRaw("EXTRACT(month from data_movimentacao) = '{$mes}'");
        }
        
        if(!empty($filtro['estabelecimento'])){
            $estabelecimento = str_pad($filtro['estabelecimento'], 2, "0", STR_PAD_LEFT);
            $MovimentacaoObj->where("estabelecimento", $estabelecimento);
        }

        if(!empty($filtro['grupo']) || !empty($filtro['subgrupo']) || !empty($filtro['marca']) || !empty($filtro['linha'])){
			if(!empty($filtro['grupo'])){
				$MovimentacaoObj->where("grupo", 'ilike' , $filtro['grupo']);
			}
			if(!empty($filtro['subgrupo'])){
				$MovimentacaoObj->where("subgrupo", 'ilike', $filtro['subgrupo']);
			}
			if(!empty($filtro['marca'])){
				$MovimentacaoObj->where("marca", 'ilike', $filtro['marca']);
			}
			if(!empty($filtro['linha'])){
				$MovimentacaoObj->where("linha", 'ilike', $filtro['linha']);
			}
        }

        $Movimentacao = $MovimentacaoObj->get();
        unset($MovimentacaoObj);
        unset($clientes_exluir);
        $movimentos = [];
        $Movimentacao->each(function($movimento) use (&$movimentos){
            $key = $movimento->grupo.''.$movimento->subgrupo.''.$movimento->marca.''.$movimento->linha.''.$movimento->segmento;
            if(in_array($movimento->cfop, $this->cfop_vendas)){
                if(!isset($movimentos[$key])){
                    $movimentos[$key] = [
                        'estabelecimento' => $movimento->estabelecimento,
                        'codigo' => $movimento->produto_codigo,
                        'grupo' => $movimento->grupo,
                        'subgrupo' => $movimento->subgrupo,
                        'marca' => $movimento->marca,
                        'linha' => $movimento->linha,
                        'segmento' => $movimento->segmento,
                        'quantidade' => 0,
                        'movimento' => 0,
                        'devolucao' => 0,
                        'frete' => 0,
                        'ipi' => 0,
                        'seguro' => 0,
                        'desconto' => 0,
                        'resultado_movimento_devolucao' => 0,
                        'venda_portal' => 0,
                        'resultado_dif_venda' => 0,
                        'custo_medio_gerencial' => 0,
                        'margem_medio_gerencial' => 0,
                        'custo_gerencial' => 0,
                        'margem_gerencial' => 0,
                        'custo_contabil' => 0,
                        'margem_contabil' => 0,
                        'prepago' => 0,
                        'resultado_prepago' => 0,
                        'custo_medio_gerencial_prepago' => 0,
                        'margem_medio_gerencial_prepago' => 0,
                        'custo_gerencial_prepago' => 0,
                        'margem_gerencial_prepago' => 0,
                        'custo_contabil_prepago' => 0,
                        'margem_contabil_prepago' => 0,
                        'valor_medio' => 0,
                        'valor_medio_prepago' => 0,
                    ];
                }
                $movimentos[$key]['quantidade'] += (float) $movimento->quantidade;
                $movimentos[$key]['movimento'] += (float) $movimento->valor;
                $movimentos[$key]['custo_medio_gerencial'] += (float) $movimento->medio_gerencial;
                $movimentos[$key]['custo_gerencial'] += (float) $movimento->gerencial;
                $movimentos[$key]['custo_contabil'] += (float) $movimento->contabil;
                $movimentos[$key]['frete'] += (float) $movimento->frete;
                $movimentos[$key]['ipi'] += (float) $movimento->ipi;
                $movimentos[$key]['desconto'] += (float) $movimento->desconto;
                $movimentos[$key]['seguro'] += (float) $movimento->seguro;
                $movimentos[$key]['prepago'] += (float) $movimento->prepago;
                $movimentos[$key]['venda_portal'] += (float) ($movimento->valor + $movimento->frete + $movimento->seguro + $movimento->ipi) - $movimento->desconto;
            }else if(in_array($movimento->cfop, $this->cfop_devolucao)){
                $key = 'devolucao';
                if(!isset($movimentos[$key])){
                    $movimentos[$key] = [
                        'estabelecimento' => $movimento->estabelecimento,
                        'codigo' => 'Devolução',
                        'grupo' => 'Devolução',
                        'subgrupo' => 'Devolução',
                        'marca' => 'Devolução',
                        'linha' => 'Devolução',
                        'segmento' => 'Devolução',
                        'quantidade' => 0,
                        'movimento' => 0,
                        'devolucao' => 0,
                        'frete' => 0,
                        'ipi' => 0,
                        'seguro' => 0,
                        'desconto' => 0,
                        'resultado_movimento_devolucao' => 0,
                        'venda_portal' => 0,
                        'resultado_dif_venda' => 0,
                        'custo_medio_gerencial' => 0,
                        'margem_medio_gerencial' => 0,
                        'custo_gerencial' => 0,
                        'margem_gerencial' => 0,
                        'custo_contabil' => 0,
                        'margem_contabil' => 0,
                        'prepago' => 0,
                        'resultado_prepago' => 0,
                        'custo_medio_gerencial_prepago' => 0,
                        'margem_medio_gerencial_prepago' => 0,
                        'custo_gerencial_prepago' => 0,
                        'margem_gerencial_prepago' => 0,
                        'custo_contabil_prepago' => 0,
                        'margem_contabil_prepago' => 0,
                        'valor_medio' => 0,
                        'valor_medio_prepago' => 0,
                    ];
                }
                $movimentos[$key]['quantidade'] += (float) $movimento->quantidade;
                $movimentos[$key]['devolucao'] += (float) ($movimento->valor + $movimento->frete + $movimento->seguro + $movimento->ipi) - $movimento->desconto;
            }
        });
		if(!isset($movimentos['devolucao'])){
			$movimentos['devolucao'] = [
                'estabelecimento' => empty($movimento->estabelecimento)? '' : $movimento->estabelecimento,
                'codigo' => 'Devolução',
				'grupo' => 'Devolução',
				'subgrupo' => 'Devolução',
				'marca' => 'Devolução',
				'linha' => 'Devolução',
                'segmento' => 'Devolução',
				'quantidade' => 0,
				'movimento' => 0,
				'devolucao' => 0,
				'frete' => 0,
				'ipi' => 0,
				'seguro' => 0,
				'desconto' => 0,
				'resultado_movimento_devolucao' => 0,
				'venda_portal' => 0,
				'resultado_dif_venda' => 0,
				'custo_medio_gerencial' => 0,
				'margem_medio_gerencial' => 0,
				'custo_gerencial' => 0,
				'margem_gerencial' => 0,
				'custo_contabil' => 0,
				'margem_contabil' => 0,
				'prepago' => 0,
				'resultado_prepago' => 0,
				'custo_medio_gerencial_prepago' => 0,
				'margem_medio_gerencial_prepago' => 0,
				'custo_gerencial_prepago' => 0,
				'margem_gerencial_prepago' => 0,
				'custo_contabil_prepago' => 0,
				'margem_contabil_prepago' => 0,
				'valor_medio' => 0,
				'valor_medio_prepago' => 0,
			];
		}
        unset($Movimentacao);
        foreach($movimentos as $key => $movimento){
            $movimento = $movimentos[$key]['movimento'] ?? 0;
            $devolucao = $movimentos[$key]['devolucao'] ?? 0;
            $quantidade = $movimentos[$key]['quantidade'] ?? 0;

            $custo_medio_gerencial = $movimentos[$key]['custo_medio_gerencial'] ?? 0;
            $movimentos[$key]['custo_medio_gerencial_prepago'] = $custo_medio_gerencial;

            $custo_gerencial = $movimentos[$key]['custo_gerencial'] ?? 0;
            $movimentos[$key]['custo_gerencial_prepago'] = $custo_gerencial;

            $custo_contabil = $movimentos[$key]['custo_contabil'] ?? 0;
            $movimentos[$key]['custo_contabil_prepago'] = $custo_contabil;

            $frete = $movimentos[$key]['frete'] ?? 0;
            $ipi = $movimentos[$key]['ipi'] ?? 0;
            $desconto = $movimentos[$key]['desconto'] ?? 0;
            $seguro = $movimentos[$key]['seguro'] ?? 0;
            $prepago = $movimentos[$key]['prepago'] ?? 0;

            $venda_portal = $movimentos[$key]['venda_portal'] ?? 0;
            $movimentos[$key]['venda_portal'] = $venda_portal;
            $venda_portal = $movimentos[$key]['venda_portal'] ?? 0;
            $movimentos[$key]['resultado_movimento_devolucao'] = ($movimento + $frete + $ipi + $seguro) - ($devolucao + $desconto);
            $movimentos[$key]['resultado_prepago'] = ($movimento + $frete + $ipi + $seguro + $prepago) - ($devolucao + $desconto);

			if($quantidade > 0){
				$movimentos[$key]['valor_medio'] = $movimentos[$key]['resultado_movimento_devolucao'] / $quantidade;
				$movimentos[$key]['valor_medio_prepago'] = $movimentos[$key]['resultado_prepago'] / $quantidade;
			}

            $movimentos[$key]['resultado_dif_venda'] = $venda_portal - $movimentos[$key]['resultado_movimento_devolucao'];
            if($custo_gerencial > 0){
                $movimentos[$key]['margem_gerencial'] = (($movimentos[$key]['resultado_movimento_devolucao'] - $custo_gerencial) / $movimentos[$key]['resultado_movimento_devolucao']) * 100;
                $movimentos[$key]['margem_gerencial_prepago'] = ((($movimentos[$key]['resultado_movimento_devolucao'] + $prepago) - $custo_gerencial) / ($movimentos[$key]['resultado_movimento_devolucao'] + $prepago)) * 100;
            }

            if($custo_medio_gerencial > 0){
                $movimentos[$key]['margem_medio_gerencial'] = (($movimentos[$key]['resultado_movimento_devolucao'] - $custo_medio_gerencial) / $movimentos[$key]['resultado_movimento_devolucao']) * 100;
                $movimentos[$key]['margem_medio_gerencial_prepago'] = ((($movimentos[$key]['resultado_movimento_devolucao'] + $prepago) - $custo_medio_gerencial) / ($movimentos[$key]['resultado_movimento_devolucao'] + $prepago)) * 100;
            }

            if ($custo_contabil > 0){
                $movimentos[$key]['margem_contabil'] = (($movimentos[$key]['resultado_movimento_devolucao'] - $custo_contabil) / $movimentos[$key]['resultado_movimento_devolucao']) * 100;
                $movimentos[$key]['margem_contabil_prepago'] = ((($movimentos[$key]['resultado_movimento_devolucao'] + $prepago) - $custo_contabil) / ($movimentos[$key]['resultado_movimento_devolucao'] + $prepago)) * 100;
            }
        }
        foreach($movimentos as $key => $movimento){
            foreach($movimento as $k => $value){
                if(!in_array($k, ['grupo', 'linha', 'marca', 'subgrupo', 'segmento'])){
                    if(in_array($k, ['margem_gerencial', 'margem_gerencial_prepago', 'margem_medio_gerencial', 'margem_medio_gerencial_prepago', 'margem_contabil', 'margem_contabil_prepago'])){
                        $movimentos[$key][$k] = parserValor($movimentos[$key][$k]).' %';
                    }
                }
            }
        }
        $retorno = [];
        foreach($movimentos as $key => $movimento){
            foreach($movimento as $k => $value){
                if(in_array($k, ['estabelecimento', 'codigo', 'grupo', 'linha', 'marca', 'subgrupo', 'quantidade', 'segmento'])){
                    $retorno[$key][$k] = $value;
                }
            }
            switch($coluna){
                case 'custo_gerencial':
                    $retorno[$key]['resultado'] = $movimento['resultado_movimento_devolucao'];
                    $retorno[$key]['custo'] = $movimento['custo_gerencial'];
                    $retorno[$key]['margem'] = $movimento['margem_gerencial'];
                    $retorno[$key]['valor_medio'] = $movimento['valor_medio'];
                break;
                case 'custo_medio_gerencial':
                    $retorno[$key]['resultado'] = $movimento['resultado_movimento_devolucao'];
                    $retorno[$key]['custo'] = $movimento['custo_medio_gerencial'];
                    $retorno[$key]['margem'] = $movimento['margem_medio_gerencial'];
                    $retorno[$key]['valor_medio'] = $movimento['valor_medio'];
                break;
                case 'custo_contabil':
                    $retorno[$key]['resultado'] = $movimento['resultado_movimento_devolucao'];
                    $retorno[$key]['custo'] = $movimento['custo_contabil'];
                    $retorno[$key]['margem'] = $movimento['margem_contabil'];
                    $retorno[$key]['valor_medio'] = $movimento['valor_medio'];
                break;
                case 'custo_medio_gerencial_prepago':
                    $retorno[$key]['resultado'] = $movimento['resultado_prepago'];
                    $retorno[$key]['custo'] = $movimento['custo_medio_gerencial_prepago'];
                    $retorno[$key]['margem'] = $movimento['margem_medio_gerencial_prepago'];
                    $retorno[$key]['valor_medio'] = $movimento['valor_medio_prepago'];
                break;
                case 'custo_gerencial_prepago':
                    $retorno[$key]['resultado'] = $movimento['resultado_prepago'];
                    $retorno[$key]['custo'] = $movimento['custo_gerencial_prepago'];
                    $retorno[$key]['margem'] = $movimento['margem_gerencial_prepago'];
                    $retorno[$key]['valor_medio'] = $movimento['valor_medio_prepago'];
                break;
                case 'custo_contabil_prepago':
                    $retorno[$key]['resultado'] = $movimento['resultado_prepago'];
                    $retorno[$key]['custo'] = $movimento['custo_contabil_prepago'];
                    $retorno[$key]['margem'] = $movimento['margem_contabil_prepago'];
                    $retorno[$key]['valor_medio'] = $movimento['valor_medio_prepago'];
                break;
            }
        }
        foreach($retorno as $key => $value){
            if(
                $key == 'devolucao'
            ){
                $retorno[$key]['quantidade'] = parserValor($retorno[$key]['quantidade']);
                $retorno[$key]['resultado'] = str_replace('-', '', parserValor($retorno[$key]['resultado']));
            }
			foreach($value as $campo => $valor){
				if(is_null($valor)){
					$retorno[$key][$campo] = '';
				}
			}
        }

		$retorno = array_values($retorno);
		
		return response()->json([
			'status' => 'success',
			'message' => '',
			'error' => [],
			'response' => $retorno
		]);
	}

	private function tratarFiltro($retorno, $campos){
		$retorno_temp = [];
		foreach($retorno as $chave => $valor){
			if(in_array($chave, ['movimento', 'devolucao', 'pis', 'cofins', 'icms', 'frete', 'ipi', 'seguro', 'desconto', 'icms_beneficio', 'despesas_operacionais', 'despesas_outras', 'despesas_outras_receitas', 'despesas_indedutivesis', 'despesas_imposto_renda_contribuicao_social', 'lucro_prejuizo', 'lucro_prejuizo_acumulado'])){
				$retorno_temp[$chave] = $valor;
			}
			if($campos['agrupar'] == 'medio_gerencial'){
				if(isset($campos['prepago'])){
					if(in_array($chave, ['resultado_prepago', 'custo_medio_gerencial_prepago', 'margem_medio_gerencial_prepago'])){
						$retorno_temp[$chave] = $valor;
					}
				}else{
					if(in_array($chave, ['resultado_movimento_devolucao', 'custo_medio_gerencial', 'margem_medio_gerencial'])){
						$retorno_temp[$chave] = $valor;
					}
				}
			}
			if($campos['agrupar'] == 'contabil'){
				if(isset($campos['prepago'])){
					if(in_array($chave, ['resultado_prepago', 'custo_contabil_prepago', 'margem_contabil_prepago'])){
						$retorno_temp[$chave] = $valor;
					}
				}else{
					if(in_array($chave, ['resultado_movimento_devolucao', 'custo_contabil', 'margem_contabil'])){
						$retorno_temp[$chave] = $valor;
					}
				}
			}
			if($campos['agrupar'] == 'gerencial'){
				if(isset($campos['prepago'])){
					if(in_array($chave, ['resultado_prepago', 'custo_gerencial_prepago', 'margem_gerencial_prepago'])){
						$retorno_temp[$chave] = $valor;
					}
				}else{
					if(in_array($chave, ['resultado_movimento_devolucao', 'custo_medio_gerencial', 'margem_medio_gerencial'])){
						$retorno_temp[$chave] = $valor;
					}
				}
			}
		}
		return $retorno_temp;
	}
}
