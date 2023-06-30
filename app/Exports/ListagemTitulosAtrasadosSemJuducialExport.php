<?php

namespace App\Exports;

use App\TitulosEmAbertoNasajon;

use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

use Illuminate\Support\Facades\DB;

use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ListagemTitulosAtrasadosSemJuducialExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $titulos_abertos = TitulosEmAbertoNasajon::with('cliente')
        ->where(DB::raw('vencimento + 90'),'<',DB::raw('now()'))
        ->where('banco_nome','!=','PROCESSOS JUDICIAIS RAGAZZI')
        ->get();

        $dados = collect([[
            'Cliente',
            'Banco',
            'Titulo',
            'Valor',
            'Data Emissão',
            'Data Vencto'
        ]]);
        
        $titulos_abertos->each(function($query) use (&$dados){
            $dados->push([
                $query->nome_cliente,
                $query->banco_nome,
                $query->numero,
                $query->valor,
                parserData($query->titulo_emissao),
                parserData($query->vencimento),
            ]);
        });

        return $dados;
    }

    public function bindValue(Cell $cell, $value){
		if(!in_array($cell->getColumn(), ['D']) || $cell->getRow() == 1){
        	$cell->setValueExplicit($value, DataType::TYPE_STRING);
		}else{
        	$cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
		}

        return true;
    }
    
}
