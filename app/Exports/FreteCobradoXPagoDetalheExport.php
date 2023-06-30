<?php

namespace App\Exports;

use App\Http\Controllers\FreteCobradoXPagoController;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

use Illuminate\Http\Request;

class FreteCobradoXPagoDetalheExport implements FromCollection, ShouldAutoSize, WithHeadings, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $request;

    public function __construct($request){
        $this->request = $request;
    }

    public function registerEvents(): array {

        return [
            AfterSheet::class    => function(AfterSheet $event) {

                $event->sheet->getDelegate()->getStyle('A1:L1')->applyFromArray(
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

                $event->sheet->getDelegate()->getStyle('F2:F9999')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $event->sheet->getDelegate()->getStyle('G2:G9999')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $event->sheet->getDelegate()->getStyle('H2:H9999')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $event->sheet->getDelegate()->getStyle('I2:I9999')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $event->sheet->getDelegate()->getStyle('J2:J9999')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $event->sheet->getDelegate()->getStyle('K2:K9999')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $event->sheet->getDelegate()->getStyle('L2:L9999')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }

    public function headings(): array {
        return [
            'NOTA',
            'CTE',
            'CLIENTE',
            'ORIGEM',
            'DESTINO',
            'TRANSPORTADOR',
            'VALOR TOTAL DOS PRODUTOS',
            'FRETE COBRADO %',
            'FRETE COBRADO',
            'FRETE PAGO %',
            'FRETE PAGO',
            'DIFERENÇA'
        ];
    }
    
    public function collection()
    {
        $this->request->request->add(['export' => 'true']);
        $freteCobradoXPagoControllerObj = new FreteCobradoXPagoController;
        $resultado = $freteCobradoXPagoControllerObj->modal($this->request);
        return $resultado;
    }
}
