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

class InventarioExport implements FromCollection, ShouldAutoSize, WithHeadings, WithEvents, WithCustomValueBinder
{
    protected $dados;

    public function __construct($dados){
        $this->dados = $dados;
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
            'Peça',
            'Peça Lida',
            'Existe No Sistema',
            'Status',
            'Qtde. Pç.',
            '1a. Cont. Qtde. Lida',
            '2a. Cont. Qtde. Lida',
            '3a. Cont. Qtde. Lida',
            'Dif. Qtde. (1a.-Qtde. Pç.)',
            'Dif. Qtde. (2a.-Qtde. Pç.)',
            'Dif. Qtde. (3a.-Qtde. Pç.)',
            'Endereço Sistema',
            '1a. Cont. End. Lido',
            '2a. Cont. End. Lido',
            '3a. Cont. End. Lido',
            '1ª Contagem',
            '2ª Contagem',
            '3ª Contagem',
        ];

    }

    public function registerEvents(): array {

        return [
            AfterSheet::class    => function(AfterSheet $event) {

                $event->sheet->getDelegate()->getStyle('A1:Y1')->applyFromArray(
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
        set_time_limit(60000000);
        ini_set('memory_limit','8192M');
        
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
            'unidade',
            'fracao_codigo',
            'codigo_lido',
            'lido',
            'status',
            'saldo', 
            'cont_qtde_lida_1',
            'cont_qtde_lida_2',
            'cont_qtde_lida_3',
            'dif_qtde_1',
            'dif_qtde_2',
            'dif_qtde_3',                 
            'endereco',
            'endereco_lido',
            'endereco_lido_2',
            'endereco_lido_3',
            'lido_contagem_1',
            'lido_contagem_2',
            'lido_contagem_3');
        });

        return $estoque;
    }


    public function bindValue(Cell $cell, $value){

            $cell->setValueExplicit($value, DataType::TYPE_STRING);

    
        return true;
    }
}
