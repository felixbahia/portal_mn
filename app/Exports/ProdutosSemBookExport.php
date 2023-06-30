<?php

namespace App\Exports;

use App\ProdutosEstoque;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class ProdutosSemBookExport implements WithCustomValueBinder, ShouldAutoSize, FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

      $ProdutosEstoqueObj = ProdutosEstoque::select('codigo_produto', DB::raw('sum(estoque) as estoque'))->with('precoDetalhes')->
            with(['especificacao' => function($query){
            $query->where('linha', 'not ilike', 'insumo');
            $query->where('linha', 'not ilike', '%MAO DE OBRA%');
        }])->
        whereHas('especificacao',function($query){
            $query->where('linha', 'not ilike', 'insumo');
            $query->where('linha', 'not ilike', '%MAO DE OBRA%');
        })->
        where('estoque', '>', '0')->
        whereDoesntHave('itensBook')->
        groupBy('codigo_produto')->get();

    $dados = collect([[
        'Código',
        'Descrição',
        'Grupo',
        'Sub Grupo',
        'Marca',
        'Linha',
        'Ultima_Compra',
        'Estoque',

    ]]);
   
    $ProdutosEstoqueObj->each(function($produto) use (&$dados){

            $dados->push([
                (string) $produto->codigo_produto,
                $produto->especificacao->descricao,
                $produto->especificacao->grupo,
                $produto->especificacao->subgrupo,
                $produto->especificacao->marca,
                $produto->especificacao->linha,
                empty($produto->precoDetalhes)? '' : parserData($produto->precoDetalhes->ultima_compra_real),
                $produto->estoque
  
            ]);

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