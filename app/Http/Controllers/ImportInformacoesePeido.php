<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\InformacaoAdicionalProduto;
use App\Produto;

class ImportInformacoesePeido extends Controller
{
    public function createRelatorios(){
        $estabelecimentos = returnEmpresasPrologusView();
        unset($estabelecimentos[0]);
        unset($estabelecimentos[5]);
        $header_lines = ['Código', 'Descrição', 'Unidade', 'Quantidade', 'Preço Ultuma Compra', 'Custo C/ ICMS', 'Total Quantidade Ultima Compra', 'Total Quantidade Custo C/ ICMS'];
        foreach ($estabelecimentos as $key => $value) {
            $estabelecimento = str_pad($key, 2, "0", STR_PAD_LEFT);
            $produtos = [$header_lines];
            $busca_2018_12 = DB::table('estoque_2018_12')
            ->select('codigo_produto', 'estoque_2018_12.estoque', 'estabelecimento', 'valor_ultima_compra', 'estoque.pmedio_cicm')
            ->join('informacao_adicional_produtos', function($join){
                $join->on('informacao_adicional_produtos.cod_produto', 'estoque_2018_12.codigo_produto');
            })
            ->join('estoque', function($join) use ($estabelecimento){
                $join->on('estoque.codprd', 'estoque_2018_12.codigo_produto');
                $join->where('estoque.estabel', $estabelecimento);
            })
            ->where('estabelecimento', $estabelecimento)
            ->where('estoque_2018_12.estoque', '!=', '0')
            ->get();
            foreach ($busca_2018_12 as $key => $value) {
                $value = (array) $value;
                $estoque = (float) $value['estoque'];
                $custo = (float) $value['pmedio_cicm'];
                $valor_ultima_compra = (float) $value['valor_ultima_compra'];
                $produto = Produto::find(utf8_decode($value['codigo_produto']));
                if(is_null($produto)){
                    dd($value['codigo_produto']);
                }
                $produtos[] = [
                    $value['codigo_produto'],
                    utf8_encode($produto->DESCR),
                    utf8_encode($produto->UNIDADE_VND),
                    number_format($estoque, 2, ',', ''),
                    number_format($valor_ultima_compra, 2, ',', ''),
                    number_format($custo, 2, ',', ''),
                    number_format(($estoque * $valor_ultima_compra), 2, ',', ''),
                    number_format(($estoque * $custo), 2, ',', ''),
                ];
            }
            foreach ($produtos as $key => $value) {
                $produtos[$key] = implode(';', $value);
            }
            $lines = implode("\n",$produtos);
            $name_file = 'relatorio_'.$estabelecimento.'_2018_12_'.date('Y-m-d_His').'.csv';
            Storage::put($name_file, $lines);

            $produtos = [$header_lines];

            $busca_2019_01 = DB::table('estoque_2019_01')
            ->select('codigo_produto', 'estoque_2019_01.estoque', 'estabelecimento', 'valor_ultima_compra', 'estoque.pmedio_cicm')
            ->join('informacao_adicional_produtos', function($join){
                $join->on('informacao_adicional_produtos.cod_produto', 'estoque_2019_01.codigo_produto');
            })
            ->join('estoque', function($join) use ($estabelecimento){
                $join->on('estoque.codprd', 'estoque_2019_01.codigo_produto');
                $join->where('estoque.estabel', $estabelecimento);
            })
            ->where('estabelecimento', $estabelecimento)
            ->where('estoque_2019_01.estoque', '!=', '0')
            ->get();
            foreach ($busca_2019_01 as $key => $value) {
                $value = (array) $value;
                $estoque = (float) $value['estoque'];
                $custo = (float) $value['pmedio_cicm'];
                $valor_ultima_compra = (float) $value['valor_ultima_compra'];
                $produto = Produto::find(utf8_decode($value['codigo_produto']));
                if(is_null($produto)){
                    dd($value['codigo_produto']);
                }
                $produtos[] = [
                    $value['codigo_produto'],
                    utf8_encode($produto->DESCR),
                    utf8_encode($produto->UNIDADE_VND),
                    number_format($estoque, 2, ',', ''),
                    number_format($valor_ultima_compra, 2, ',', ''),
                    number_format($custo, 2, ',', ''),
                    number_format(($estoque * $valor_ultima_compra), 2, ',', ''),
                    number_format(($estoque * $custo), 2, ',', ''),
                ];
            }
            foreach ($produtos as $key => $value) {
                $produtos[$key] = implode(';', $value);
            }
            $lines = implode("\n",$produtos);
            $name_file = 'relatorio_'.$estabelecimento.'_2019_01_'.date('Y-m-d_His').'.csv';
            Storage::put($name_file, $lines);
        }
    }

    public function getInformacoesDePreco(){
        ini_set('memory_limit', '1024M');
		$data_ini = '2018-08-01 00:00:00';
		$data_end = date('Y-m-d 23:59:59');

		$data = date('Y-m-d', strtotime("-1 months", strtotime($data_ini)));
		$estabelecimentos = returnEmpresasPrologusView();
		do{
			$data = date('Y-m-d', strtotime("+1 months", strtotime($data)));
			foreach($estabelecimentos as $key => $value){
                $estabelecimento = str_pad($key, 2, "0", STR_PAD_LEFT);
                $busca = $this->createSql($estabelecimento, $data);
				if (!empty($busca)){
                    foreach ($busca as $key => $value) {
                        $value = (array) $value;
                        if(empty($value['CODPRD'])){
                            continue;
                        }
                        $produto = InformacaoAdicionalProduto::where('cod_produto', utf8_encode($value['CODPRD']))->first();
                        if(is_null($produto)){
                            $produto = new InformacaoAdicionalProduto;
                        }
                        $produto->cod_produto = utf8_encode($value['CODPRD']);
                        $produto->data_ultima_compra = $value['DTEMIS'];
                        $produto->valor_ultima_compra = $value['PRCUSTO_COMICMS'];
                        $produto->created_by = '1';
                        
                        $produto->save();
                    }
                }
            }
		}while(strtotime($data) <= strtotime($data_end));
    }

    private function createSql($estabelecimento, $data){
		$timestamp = strtotime($data);

		$table_dum = 'DUM' . str_pad($estabelecimento, 2, "0", STR_PAD_LEFT) . "_" .  date("y", $timestamp) . date("m", $timestamp) . "2";
        $table_dui = 'DUI' . str_pad($estabelecimento, 2, "0", STR_PAD_LEFT) . "_" .  date("y", $timestamp) . date("m", $timestamp) . "2";
        if (DB::connection('srv_prologos')->getSchemaBuilder()->hasTable($table_dum)){
            return DB::connection('srv_prologos')->select("SELECT
                {$table_dum}.DTEMIS,
                {$table_dui}.CODPRD,
                {$table_dui}.PRCUSTO_COMICMS
            FROM
                {$table_dum}
                LEFT JOIN {$table_dui} ON ({$table_dum}.CODCAD = {$table_dui}.CODCAD AND {$table_dum}.NUMDOC = {$table_dui}.NUMDOC)
            WHERE
                LEFT({$table_dum}.TIPOPER, 2) = 'EC'
                AND ({$table_dum}.TIPOPER <> 'ECI' OR {$table_dum}.TIPOPER <> 'ECJ' OR {$table_dum}.TIPOPER <> 'ECP' OR {$table_dum}.TIPOPER <> 'ECS' OR {$table_dum}.TIPOPER <> 'ECT' OR {$table_dum}.TIPOPER <> 'ECO')
                AND {$table_dum}.STATDOC <> 'C'
                AND ({$table_dum}.CODCAD <> '0050758840002' AND {$table_dum}.CODCAD <> '0063112740002' AND {$table_dum}.CODCAD <> '00507758840001' AND {$table_dum}.CODCAD <> '0050758840001' AND {$table_dum}.CODCAD <> '0050758840002'AND {$table_dum}.CODCAD <> '0063112740001') ;");
        }
        return false;
    }
}
