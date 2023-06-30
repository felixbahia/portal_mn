<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use App\TitulosEmAbertoNasajon;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class TitulosVencidosExport implements WithCustomValueBinder, ShouldAutoSize, FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
     

      
        $fim_periodo = Carbon::now();
        $fim_periodo->addDays(-1);
        $inicio_periodo = Carbon::now();
        $inicio_periodo->addDays(-2);
  
        $titulosEmAbertoNasajon =  TitulosEmAbertoNasajon::select(
				'codigo',
				'numero',
				'nome_cliente',
				'valor',
				'titulo_emissao',
				'vencimento',
                'saldotitulo'		
			)
            ->where('banco_codigo','0')
            ->where('valor','>','0')
			->whereBetween('vencimento', [$inicio_periodo, $fim_periodo])
            ->get();

    $dados = collect([[
        'Estabelecimento',
        'Titulo',
        'Cliente',
        'Valor',
        'Data Emissão',
        'Data Vencto',
        'Saldo'
    ]]);
    $titulosEmAbertoNasajon->each(function($titulo) use (&$dados){
    
            $dados->push([
                (string) $titulo->codigo,
                $titulo->numero,
                $titulo->nome_cliente,
                'R$ ' . parserValor($titulo->valor),
                parserData($titulo->titulo_emissao),
                parserData($titulo->vencimento),
                'R$ ' . parserValor($titulo->saldotitulo),
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
