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

class InventarioProdutoExport implements FromCollection, ShouldAutoSize, WithHeadings, WithEvents, WithCustomValueBinder
{
    protected $dados;

    public function __construct($request){
        $this->dados = $request;
    }

    public function headings(): array {
        return [
            'Estabelcimento',
            'Código',
            'Descrição',
            'Grupo',
            'Marca',
            'Linha',
            'Unidade',
            'Qtde. Est.',
            'Qtde. Soma Pç.',
            'Vol. Pç.',
            '1a. Cont. Qtde.',
            '2a. Cont. Qtde.',
            '3a. Cont. Qtde.',
            'Dif. Qtde. Est. (1a.-Sld.)',
            'Dif. Qtde. Est. (2a.-Sld.)',
            'Dif. Qtde. Est. (3a.-Sld.)',
            'Dif. Qtde. Soma Pç. (1a.-Sld.)',
            'Dif. Qtde. Soma Pç. (2a.-Sld.)',
            'Dif. Qtde. Soma Pç. (3a.-Sld.)',
            '1a. Cont. Vol.',
            '2a. Cont. Vol.',
            '3a. Cont. Vol.',
            'Dif. Vol. (1a.-Sld.)',
            'Dif. Vol. (2a.-Sld.)',
            'Dif. Vol. (3a.-Sld.)'
        ];

    }

    public function registerEvents(): array {

        return [
            AfterSheet::class    => function(AfterSheet $event) {

                $event->sheet->getDelegate()->getStyle('A1:Z1')->applyFromArray(
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

            return $linha->only('estabelecimento_posse',
                'produto_codigo',
                'produto_descricao',
                'grupo',
                'marca',
                'linha',
                'unidade' ,
                'saldo_estoque',
                'saldo',
                'volume',
                'contagem_1_estoque',
                'contagem_2_estoque',
                'contagem_3_estoque',
                'diferenca_1_saldo_estoque',
                'diferenca_2_saldo_estoque',
                'diferenca_3_saldo_estoque',
                'diferenca_1_estoque',
                'diferenca_2_estoque',
                'diferenca_3_estoque',
                'contagem_1_volume',
                'contagem_2_volume',
                'contagem_3_volume',          
                'diferenca_1_volume', 
                'diferenca_2_volume',
                'diferenca_3_volume'
            );
        });

        return $estoque;
    }

    public function bindValue(Cell $cell, $value){
        if(!in_array($cell->getColumn(), ['H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y']) || $cell->getRow() == 1){
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
        }else{
            if($value == 'ZERO'){
                $cell->setValueExplicit(0.0, DataType::TYPE_NUMERIC);
            }else{
                $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
            }
        }
    
        return true;
    }
}
