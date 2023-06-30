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

class ProdutoSemCustoContabilExport implements WithCustomValueBinder, ShouldAutoSize, FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $ProdutosEstoqueObj = ProdutosEstoque::
			with(['especificacao', 'custoDetalhes'])->
			where('estoque', '>', '0')->
			whereHas('especificacao', function($query){
				$query->where('ativo', true);
			})->
			get();
		$dados = collect([[
			'Estabelecimento',
			'Código',
			'Descrição',
			'Grupo',
			'Sub Grupo',
			'Marca',
			'Linha',
			'Estoque'
		]]);
		$ProdutosEstoqueObj->each(function($produto) use (&$dados){
			if(empty($produto->custoDetalhes)){
				$dados->push([
					$produto->estabelecimento,
					(string) $produto->codigo_produto,
					$produto->especificacao->descricao,
					$produto->especificacao->grupo,
					$produto->especificacao->subgrupo,
					$produto->especificacao->marca,
					$produto->especificacao->linha,
					$produto->estoque,
				]);
			}else if(empty($produto->custoDetalhes->custo_medio_contabil) || $produto->custoDetalhes->custo_medio_contabil <= 0){
				$dados->push([
					$produto->estabelecimento,
					(string) $produto->codigo_produto,
					$produto->especificacao->descricao,
					$produto->especificacao->grupo,
					$produto->especificacao->subgrupo,
					$produto->especificacao->marca,
					$produto->especificacao->linha,
					$produto->estoque,
				]);
			}
		});
		return $dados;
    }

    public function bindValue(Cell $cell, $value){
        if(!in_array($cell->getColumn(), ['H']) || $cell->getRow() == 1){
        	$cell->setValueExplicit($value, DataType::TYPE_STRING);
		}else{
        	$cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
		}

        return true;
    }
}
