<?php

namespace App\Exports;

use App\Http\Controllers\FluxoDeCaixaController;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

use App\Http\Requests\FluxoDeCaixaFilterRequest;

class FluxoDeCaixaExport implements FromCollection, ShouldAutoSize, WithHeadings, WithEvents, WithStrictNullComparison
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $request;

    public function __construct($request){
        $this->request = new FluxoDeCaixaFilterRequest($request->all());
    }

    public function headings(): array {
        return [
            'DATA',
            'A RECEBER',
            'A PAGAR',
            'SALDO',
            'SALDO ACUMULADO',
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

                $event->sheet->getDelegate()->getStyle('G2:G9999')
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }

    public function collection(){

        $FluxoDeCaixaObj = new FluxoDeCaixaController;
        $fluxo_caixa = collect($FluxoDeCaixaObj->filter($this->request, true));

        $fluxo_caixa->transform( function ($linha){
            $linha = collect($linha);
            
            return $linha->only('data', 'valor_a_receber', 'valor_a_pagar', 'valor_saldo', 'valor_saldo_acumulado');
        });

        return $fluxo_caixa;
        
    }
}

