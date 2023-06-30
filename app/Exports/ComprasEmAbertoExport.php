<?php

namespace App\Exports;

use App\Http\Controllers\PedidosComprasNasajonController;

use Auth;

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

class ComprasEmAbertoExport implements FromCollection, ShouldAutoSize, WithHeadings, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $request;

    public function __construct($request){
        $this->request = $request;
    }

    public function headings(): array {
        if(in_array(Auth::user()->tipo_usuario_id, [1,15])){
            return [
                'ESTABELECIMENTO',
                'PEDIDO',
                'PROFORMA',
                'FORNECEDOR',
                'DATA RECEBIMENTO',
                'GRUPO',
                'FOB COMPRA DOLAR',
                'PRECO VENDA DOLAR',
                'QUANTIDADE COMPRADA',
                'QUANTIDADE VENDIDA',
                '%COMPRA/VENDA'
            ];
        }
        else{
            return [
                'ESTABELECIMENTO',
                'PEDIDO',
                'DATA RECEBIMENTO',
                'GRUPO',
                'FOB COMPRA DOLAR',
                'PRECO VENDA DOLAR',
                'QUANTIDADE COMPRADA',
                'QUANTIDADE VENDIDA',
                '%COMPRA/VENDA'
            ];
        }

    }

    public function registerEvents(): array {

        return [
            AfterSheet::class    => function(AfterSheet $event) {

                if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || Auth::user()->hasRole('pcp/produtos/preço') || Auth::user()->hasRole('Analise Compras') || in_array(Auth::id(), [272])){
                    $range = 'A1:K1';
                }
                else{
                    $range = 'A1:I1';                    
                }
                $event->sheet->getDelegate()->getStyle($range)->applyFromArray(
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
        $compras = collect($PedidosComprasNasajonControllerObj->dadosExportacao($this->request));

        $compras->transform( function ($linha){
            $linha = collect($linha);
            
            if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || Auth::user()->hasRole('pcp/produtos/preço') || Auth::user()->hasRole('Analise Compras') || in_array(Auth::id(), [272])){
                return $linha->only('unidade', 'pcmn', 'proforma', 'fornecedor', 'data_recebimento', 'grupo', 'preco_compra','preco_dolar','qtde_comprada','qtde_vendida','porcetagem');
            }
            else{
                return $linha->only('unidade', 'pcmn', 'data_recebimento', 'grupo', 'preco_compra', 'preco_dolar','qtde_comprada','qtde_vendida','porcetagem');
            }
        });

        return $compras;
        
    }
}
