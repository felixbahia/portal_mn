<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;

use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ComissaoDuplicataXLSXExport implements WithCustomValueBinder, FromView, ShouldAutoSize
{

    public function __construct($array){
        $this->informacoes = $array;
    }

    public function bindValue(Cell $cell, $value){
        $cell->setValueExplicit($value, DataType::TYPE_STRING);

        return true;
    }

    public function view(): View{
        return view('programs.desconto_representante.comissao_vencidos_desconto', $this->informacoes);
    }
}
