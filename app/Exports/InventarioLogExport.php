<?php

namespace App\Exports;

use App\Http\Controllers\InventarioNovoController;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class InventarioLogExport implements FromCollection, ShouldAutoSize, WithHeadings, WithEvents, WithCustomValueBinder
{
    protected $dados;

    public function __construct($request){
        $this->dados = $request;
    }

    public function headings(): array {
        return [
            'Peça',
            'Contagem',
            'Endereço',
            'Usuário',
            'Horário'
        ];

    }

    public function registerEvents(): array {

        return [
            AfterSheet::class    => function(AfterSheet $event) {

                $event->sheet->getDelegate()->getStyle('A1:E1')->applyFromArray(
                    [
                        'font'  => [
                            'bold'  => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'type' => Fill::FILL_SOLID,
                            'color' => ['rgb' => '005c8d']
                        ]
                    ]
                )
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FF005c8d');

                $event->sheet->getDelegate()->getStyle('A2:A9999')
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $event->sheet->getDelegate()->getStyle('U2:G9999')
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }

    public function collection(){
        $InventarioNovoControllerObj = new InventarioNovoController;
        $estoque = collect($this->dados);

        $estoque->transform( function ($linha){
            $linha = collect($linha);

            return $linha->only('fracao_codigo', 'contagem', 'endereco', 'usuario', 'criacao');
        });
        
        return $estoque;
    }

    public function bindValue(Cell $cell, $value){

        $cell->setValueExplicit($value, DataType::TYPE_STRING);
    
        return true;
    }
}
