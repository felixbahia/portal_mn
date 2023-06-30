<?php

namespace App\Exports;

use App\ProdutoEspecificacao;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\FromCollection;

class ProdutoEstoqueSemLaudoExport implements FromCollection
{  /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
     


  
        $produtoEspecificacaos =  ProdutoEspecificacao::select('codigo_produto', 'descricao','marca', 'linha', 'grupo', 'subgrupo')
        ->where('ativo',true)
        ->whereDoesntHave('ficha_tecnica')
            ->whereHas('estoque', function($query){
                $query->where('estoque','>','0');
            })
	       ->get();

    $dados = collect([[
         'Codigo',
        'Descrição',
        'Grupo',
        'SubGrupo',
        'Marca',
        'Linha'
    ]]);
    $produtoEspecificacaos->each(function($produtoEspecificacao) use (&$dados){
    
            $dados->push([
                (string) $produtoEspecificacao->codigo_produto,
                $produtoEspecificacao->descricao,
                $produtoEspecificacao->grupo,
                $produtoEspecificacao->subgrupo,
                $produtoEspecificacao->marca,
                $produtoEspecificacao->linha,
      
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
