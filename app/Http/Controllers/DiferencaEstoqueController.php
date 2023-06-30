<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DiferencaEstoqueController extends Controller
{

	public function createDadosDiferenca(){
        ini_set('memory_limit', '2024M');
		$this->limpaTabelas();

		$buscaEstoqueDisponivel = $this->buscaEstoqueDisponivel();
		$this->insertProdutoEstoque($buscaEstoqueDisponivel);
		unset($buscaEstoqueDisponivel);

		$this->buscaInserEstoquePecaPeca();
		$this->createDiferenca();

		return $this->createFile();
	}

	private function limpaTabelas(){
		DB::table('temp_produto_disponivel')->truncate();
		DB::table('temp_produto_pecapeca')->truncate();
		DB::table('temp_produto_diferenca')->truncate();
	}

	private function createDiferenca(){
		$sql_busca = "SELECT
				temp_produto_disponivel.codprd,
				temp_produto_disponivel.descricao,
				temp_produto_disponivel.marca,
				temp_produto_disponivel.linha,
				temp_produto_disponivel.grupo,
				temp_produto_disponivel.subgrupo,
				temp_produto_disponivel.estoque_disponivel_00,
				temp_produto_disponivel.estoque_disponivel_01,
				( SELECT
					quantidade
				FROM
					temp_produto_pecapeca
				WHERE
					temp_produto_disponivel.codprd = temp_produto_pecapeca.codprd
					AND temp_produto_pecapeca.empresa = '01' ) AS estoque_pecapeca_01,
				temp_produto_disponivel.estoque_disponivel_02,
				( SELECT
					quantidade
				FROM
					temp_produto_pecapeca
				WHERE
					temp_produto_disponivel.codprd = temp_produto_pecapeca.codprd
					AND temp_produto_pecapeca.empresa = '02' ) AS estoque_pecapeca_02,
				temp_produto_disponivel.estoque_disponivel_03,
				( SELECT
					quantidade
				FROM
					temp_produto_pecapeca
				WHERE
					temp_produto_disponivel.codprd = temp_produto_pecapeca.codprd
					AND temp_produto_pecapeca.empresa = '03' ) AS estoque_pecapeca_03,
				temp_produto_disponivel.estoque_disponivel_04,
				( SELECT
					quantidade
				FROM
					temp_produto_pecapeca
				WHERE
					temp_produto_disponivel.codprd = temp_produto_pecapeca.codprd
					AND temp_produto_pecapeca.empresa = '04' ) AS estoque_pecapeca_04
			FROM
				temp_produto_disponivel
			ORDER BY
				temp_produto_disponivel.codprd";
		$busca = DB::select($sql_busca);
		foreach ($busca as $key => $value) {
			if(
				floatval($value->estoque_disponivel_00) !== 0 &&
				floatval($value->estoque_disponivel_01) === floatval($value->estoque_pecapeca_01) &&
				floatval($value->estoque_disponivel_02) === floatval($value->estoque_pecapeca_02) &&
				floatval($value->estoque_disponivel_03) === floatval($value->estoque_pecapeca_03) &&
				floatval($value->estoque_disponivel_04) === floatval($value->estoque_pecapeca_04)
			){
				unset($busca[$key]);
				continue;
			}
			if(floatval($value->estoque_disponivel_00) <> 0){

				$produtos_insert = [];
				$produtos_insert = [
					"empresa" => "00",
					"codprd" => utf8_encode($value->codprd),
					"descricao" => $value->descricao,
					"marca" => $value->marca,
					"linha" => $value->linha,
					"grupo" => $value->grupo,
					"subgrupo" => $value->subgrupo,
					"estoque_disponivel" => (string) number_format($value->estoque_disponivel_00, 2, '.', ''),
					"estoque_pecapeca" => (string) 0.0
				];
				DB::table('temp_produto_diferenca')->insert($produtos_insert);
			}
			if(floatval($value->estoque_disponivel_01) <> floatval($value->estoque_pecapeca_01)){
				$produtos_insert = [];
				$produtos_insert = [
					"empresa" => "01",
					"codprd" => utf8_encode($value->codprd),
					"descricao" => $value->descricao,
					"marca" => $value->marca,
					"linha" => $value->linha,
					"grupo" => $value->grupo,
					"subgrupo" => $value->subgrupo,
					"estoque_disponivel" => (string) number_format($value->estoque_disponivel_01, 2, '.', ''),
					"estoque_pecapeca" => (string) number_format($value->estoque_pecapeca_01, 2, '.', '')
				];
				DB::table('temp_produto_diferenca')->insert($produtos_insert);
			}
			if(floatval($value->estoque_disponivel_02) <> floatval($value->estoque_pecapeca_02)){
				$produtos_insert = [];
				$produtos_insert = [
					"empresa" => "02",
					"codprd" => utf8_encode($value->codprd),
					"descricao" => $value->descricao,
					"marca" => $value->marca,
					"linha" => $value->linha,
					"grupo" => $value->grupo,
					"subgrupo" => $value->subgrupo,
					"estoque_disponivel" => (string) number_format($value->estoque_disponivel_02, 2, '.', ''),
					"estoque_pecapeca" => (string) number_format($value->estoque_pecapeca_02, 2, '.', '')
				];
				DB::table('temp_produto_diferenca')->insert($produtos_insert);
			}
			if(floatval($value->estoque_disponivel_03) <> floatval($value->estoque_pecapeca_03)){
				$produtos_insert = [];
				$produtos_insert = [
					"empresa" => "03",
					"codprd" => utf8_encode($value->codprd),
					"descricao" => $value->descricao,
					"marca" => $value->marca,
					"linha" => $value->linha,
					"grupo" => $value->grupo,
					"subgrupo" => $value->subgrupo,
					"estoque_disponivel" => (string) number_format($value->estoque_disponivel_03, 2, '.', ''),
					"estoque_pecapeca" => (string) number_format($value->estoque_pecapeca_03, 2, '.', '')
				];
				DB::table('temp_produto_diferenca')->insert($produtos_insert);
			}
			if(floatval($value->estoque_disponivel_04) <> floatval($value->estoque_pecapeca_04)){
				$produtos_insert = [];
				$produtos_insert = [
					"empresa" => "04",
					"codprd" => utf8_encode($value->codprd),
					"descricao" => $value->descricao,
					"marca" => $value->marca,
					"linha" => $value->linha,
					"grupo" => $value->grupo,
					"subgrupo" => $value->subgrupo,
					"estoque_disponivel" => (string) number_format($value->estoque_disponivel_04, 2, '.', ''),
					"estoque_pecapeca" => (string) number_format($value->estoque_pecapeca_04, 2, '.', '')
				];
				DB::table('temp_produto_diferenca')->insert($produtos_insert);
			}
			unset($busca[$key]);
		}
		unset($produtos_insert);
		unset($busca);
	}

	private function createFile(){
		$dados = DB::table('temp_produto_diferenca')->orderBy('empresa')->get();
		$header_file = "\"CODPRD\";\"GRUPO\";\"LINHA\";\"MARCA\";\"SUBGRUPO\";\"ESTOQUE PROLOGOS\";\"ESTOQUE PEÇA PEÇA\"\n";
		$files = [
			0 => $header_file,
			1 => $header_file,
			2 => $header_file,
			3 => $header_file,
			4 => $header_file,
		];
		foreach ($dados as $key => $value) {
			$value = (array) $value;
			$files[intval($value["empresa"])] .= "\"{$value['codprd']}\";\"{$value['grupo']}\";\"{$value['linha']}\";\"{$value['marca']}\";\"{$value['subgrupo']}\";\"".number_format(floatval($value['estoque_disponivel']),2,",","")."\";\"".number_format(floatval($value['estoque_pecapeca']),2,",","")."\"\n";
			unset($dados[$key]);
		}
		unset($dados);
		$returnNames = [];
		foreach ($files as $key => $value) {
			$name_file = "arquivos_diferencas/{$key}_".date("d-m-Y_H:i").".csv";
			$returnNames[($name_file)] =  [
				'as' => "{$key}_".date("d-m-Y_H:i").".csv",
				'mime' => 'text/csv',
			];
        	Storage::put($name_file, $value);
        }
        return $returnNames;
	}

	private function buscaEstoqueDisponivel(){
		$sql_busca = "SELECT
			TBPRD1.CODPRD,
			TBPRD1.GRUPO,
			TBPRD1.SUBGRUPO,
			TBPRD1.DESCR,
			TBPRD1.LINHA,
			TBPRD1.MARCA,
			(SELECT
				(estoque00.EST_PRATELEIRA + estoque00.EST_DEPOSITO - estoque00.RESERVA)
			FROM
				dbo.TBEST2 AS estoque00
			WHERE
				estoque00.CODPRD = TBPRD1.CODPRD AND
				estoque00.ESTABEL = '00') AS estoque_00,
			(SELECT
				(estoque01.EST_PRATELEIRA + estoque01.EST_DEPOSITO - estoque01.RESERVA)
			FROM
				dbo.TBEST2 AS estoque01
			WHERE
				estoque01.CODPRD = TBPRD1.CODPRD AND
				estoque01.ESTABEL = '01') AS estoque_01,
			(SELECT
				(estoque02.EST_PRATELEIRA + estoque02.EST_DEPOSITO - estoque02.RESERVA)
			FROM
				dbo.TBEST2 AS estoque02
			WHERE
				estoque02.CODPRD = TBPRD1.CODPRD AND
				estoque02.ESTABEL = '02') AS estoque_02,
			(SELECT
				(estoque03.EST_PRATELEIRA + estoque03.EST_DEPOSITO - estoque03.RESERVA)
			FROM
				dbo.TBEST2 AS estoque03
			WHERE
				estoque03.CODPRD = TBPRD1.CODPRD AND
				estoque03.ESTABEL = '03') AS estoque_03,
			(SELECT
				(estoque04.EST_PRATELEIRA + estoque04.EST_DEPOSITO - estoque04.RESERVA)
			FROM
				dbo.TBEST2 AS estoque04
			WHERE
				estoque04.CODPRD = TBPRD1.CODPRD AND
				estoque04.ESTABEL = '04') AS estoque_04
		FROM
			TBPRD1
			LEFT JOIN (
				SELECT
					CODPRD as cod_produto,
					EST_PRATELEIRA as prateleira,
					EST_DEPOSITO as deposito,
					EMPENHO as empenho,
					ESTABEL as estabel
				FROM
					TBEST2
			) AS estoque ON (estoque.cod_produto = TBPRD1.CODPRD)
		WHERE
			DESCR != 'DESATIVADO' AND
			(estoque.prateleira + estoque.deposito) > 0
		GROUP BY
			CODPRD,
			GRUPO,
			SUBGRUPO,
			DESCR,
			LINHA,
			MARCA";
		return DB::connection('srv_prologos')->select($sql_busca);
	}

	private function insertProdutoEstoque($produtos){
		foreach ($produtos as $key => $produto) {
			$produtos_insert = [];
			$produtos_insert = [
				"codprd" => utf8_encode($produto->CODPRD),
				"descricao" => utf8_encode($produto->DESCR),
				"marca" => utf8_encode($produto->MARCA),
				"linha" => utf8_encode($produto->LINHA),
				"grupo" => utf8_encode($produto->GRUPO),
				"subgrupo" => utf8_encode($produto->SUBGRUPO),
				"estoque_disponivel_00" => (string) number_format($produto->estoque_00, 2, '.', ''),
				"estoque_disponivel_01" => (string) number_format($produto->estoque_01, 2, '.', ''),
				"estoque_disponivel_02" => (string) number_format($produto->estoque_02, 2, '.', ''),
				"estoque_disponivel_03" => (string) number_format($produto->estoque_03, 2, '.', ''),
				"estoque_disponivel_04" => (string) number_format($produto->estoque_04, 2, '.', ''),
				"estoque_disponivel_05" => 0
			];
			DB::table('temp_produto_disponivel')->insert($produtos_insert);
		}
	}

	private function buscaInserEstoquePecaPeca(){
		$almirante = $this->getEstoqueAlmirante();
		$this->insertProdutoPecaPeca($almirante);
		unset($almirante);

		$botelho = $this->getEstoqueBotelho();
		$this->insertProdutoPecaPeca($botelho);
		unset($botelho);

		$armazen = $this->getEstoqueArmazen();
		$this->insertProdutoPecaPeca($armazen);
		unset($armazen);
	}

    private function getEstoqueAlmirante(){
    	$sql_busca = "SELECT
				SUM(QTDE_NO_VOLUME) AS quantidade,
				CODPRD,
				'01' AS empresa
			FROM
				dbo.TBVOL1
			WHERE
				DATA_SAIDA IS NULL
			GROUP BY
				CODPRD";
		return DB::connection('srv_almirante')->select($sql_busca);
    }

    private function getEstoqueBotelho(){
    	$sql_busca = "SELECT
				SUM(QTDE_NO_VOLUME) AS quantidade,
				CODPRD,
				'02' AS empresa
			FROM
				dbo.TBVOL1
			WHERE
				DATA_SAIDA IS NULL
			GROUP BY
				CODPRD";
		return DB::connection('srv_botelho')->select($sql_busca);
    }

    private function getEstoqueXavantes(){
    	$sql_busca = "SELECT
				SUM(QTDE_NO_VOLUME) AS quantidade,
				CODPRD,
				'05' AS empresa
			FROM
				dbo.TBVOL1
			WHERE
				DATA_SAIDA IS NULL
			GROUP BY
				CODPRD";
		return DB::connection('srv_xavantes')->select($sql_busca);
    }

    private function getEstoqueArmazen(){
    	$sql_busca = "SELECT
				SUM( QTDE_NO_VOLUME ) AS quantidade,
				CODPRD,
				CAST( CASE
					WHEN DONO = '0063112740002' THEN '03'
					ELSE '04'
				END AS CHAR ) AS empresa
			FROM
				dbo.TBVOL3
			WHERE
				DATA_RETORNO IS NULL
			GROUP BY
				CODPRD,
				DONO";
		return DB::connection('srv_armazen')->select($sql_busca);
    }

	private function insertProdutoPecaPeca($produtos){
		$produtos_insert = [];
		foreach ($produtos as $key => $produto) {
			$produtos_insert[] = [
				"codprd" => utf8_decode($produto->CODPRD),
				"empresa" => $produto->empresa,
				"quantidade" => $produto->quantidade
			];
		}
		DB::table('temp_produto_pecapeca')->insert($produtos_insert);
	}
}
