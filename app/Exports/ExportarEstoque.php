<?php

namespace App\Exports;

use App\Http\Controllers\ProdutoController;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExportarEstoque implements FromCollection, ShouldAutoSize, WithHeadings, WithEvents
{
    protected $request;

    public function __construct($request){
        $this->request = $request;
    }

    public function headings(): array {
        return [
            'GRUPO',
            'CÓDIGO',
            'DESCRIÇÃO',
            'MARCA',
            'LINHA',
            'GRAMATURA',
            'LARGURA',
            'UNIDADE',
            'ESTOQUE DISPONÍVEL',
            'POR UNIDADE',
        ];

    }

    public function registerEvents(): array {

        return [
            AfterSheet::class    => function(AfterSheet $event) {

                $event->sheet->getDelegate()->getStyle('A1:J1')->applyFromArray(
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

                $event->sheet->getDelegate()->getStyle('G2:G9999')
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }

    public function collection(){

        // dd($this->request);

        $ProdutoControllerObj = new ProdutoController;
        $estoque = collect($ProdutoControllerObj->filter($this->request, true));

        foreach($estoque as $key => $value){
            $value['estoque'] = parserNumber($value['estoque']);
            $estoque[$key] = $value;
        }

        $estoque->transform( function ($linha){
            $linha = collect($linha);
            
            return $linha->only('grupo', 'codigo', 'nome', 'marca', 'linha','gramatura', 'largura', 'unidade', 'estoque', 'total_popover');
        });

        return $estoque;
        
    }
}
