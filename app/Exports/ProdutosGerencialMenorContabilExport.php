<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use App\ProdutosEstoque;

class ProdutosGerencialMenorContabilExport implements WithCustomValueBinder, ShouldAutoSize, FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
		$ProdutosEstoqueObj = ProdutosEstoque::
			with(['especificacao', 'custoDetalhes', 'precoDetalhes'])->
			where('estoque', '>', '0')->
			whereHas('especificacao', function($query){
				$query->where('ativo', true);
			})->
			whereHas('precoDetalhes', function($query){
				$query->where('compra_real', '>', '0');
			})->
			get();
		$dados = collect([[
			'Código',
			'Descrição',
			'Grupo',
			'Sub Grupo',
			'Marca',
			'Linha',
			'Estabelecimento',
			'Custo Contabil',
			'Custo Gerencial'
		]]);

		$ProdutosEstoqueObj->each(function($produto) use (&$dados){
			$custo_gerencial = empty($produto->precoDetalhes)? 0 : $produto->precoDetalhes->compra_real;
			$custo_medio= empty($produto->custoDetalhes)? 0 : $produto->custoDetalhes->custo_medio_contabil;
			if(($custo_medio * 1.4) >= $custo_gerencial){
				$dados->push([
					(string) $produto->codigo_produto,
					$produto->especificacao->descricao,
					$produto->especificacao->grupo,
					$produto->especificacao->subgrupo,
					$produto->especificacao->marca,
					$produto->especificacao->linha,
					$produto->estabelecimento,
					$custo_medio,
					$custo_gerencial,
				]);
			}
		});
		return $dados;
    }

    public function bindValue(Cell $cell, $value){
		if(!in_array($cell->getColumn(), ['H', 'I']) || $cell->getRow() == 1){
        	$cell->setValueExplicit($value, DataType::TYPE_STRING);
		}else{
        	$cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
		}

        return true;
    }
}
