<?php

namespace App\Exports;

use App\Http\Controllers\PedidosComprasNasajonController;

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

class ComprasEmAbertoDialogExport implements FromCollection, ShouldAutoSize, WithHeadings, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $request;

    public function __construct($request){
        $this->request = $request;
    }

    public function headings(): array {
        $PedidosComprasNasajonControllerObj = new PedidosComprasNasajonController;
        $cabecalho = $PedidosComprasNasajonControllerObj->dadosExportacaoCabecalhoDialog($this->request);

        return [
            $cabecalho,
            [],
            [
                'CÓDIGO',
                'GRUPO',
                'DESCRIÇÃO',
                'FOB PREÇO DOLAR',
                'PREÇO VENDA DOLAR',
                'QUANTIDADE COMPRADA',
                'QUANTIDADE VENDIDA',
                '%COMPRA/VENDA'
            ]
            
        ];

    }

    public function registerEvents(): array {

        return [
            AfterSheet::class    => function(AfterSheet $event) {

                $event->sheet->getDelegate()->getStyle('A3:H3')->applyFromArray(
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

        $PedidosComprasNasajonControllerObj = new PedidosComprasNasajonController;
        $compras = collect($PedidosComprasNasajonControllerObj->dadosExportacaoDialog($this->request));

        $compras->transform( function ($linha){
            $linha = collect($linha);
            
            return $linha->only('cod_produto', 'grupo', 'descricao_produto', 'preco_compra', 'preco_dolar', 'quantidade_comprada', 'quantidade_vendida', 'porcetagem');
        });

        return $compras;    
    }
}
