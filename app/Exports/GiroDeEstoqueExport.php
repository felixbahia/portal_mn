<?php

namespace App\Exports;


use App\Http\Controllers\GiroDeEstoqueController;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

use Illuminate\Http\Request;

class GiroDeEstoqueExport implements FromCollection, ShouldAutoSize, WithHeadings, WithEvents
{
    protected $request;

	public function __construct($request){
		$this->request = $request;
	}

	public function registerEvents(): array {

		return [
			AfterSheet::class    => function(AfterSheet $event) {

				if($this->request->produto_grupo == 'grupo'){
					$event->sheet->getDelegate()->getStyle('A1:T1')->applyFromArray(
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
					->getStartColor();
				}else{
					$event->sheet->getDelegate()->getStyle('A1:X1')->applyFromArray(
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
					->getStartColor();
				}
			},
		];
	}

	public function headings(): array {
		
		$meses = [
			"atual" => parserNameMonth(date("m")),
			"mes_1" => parserNameMonth(Carbon::now()->addMonth()->format('m')),
			"mes_2" => parserNameMonth(Carbon::now()->addMonths(2)->format('m'))
		];
		if($this->request->produto_grupo == 'grupo'){
			$retorno = [
				'Grupo',
				'Marca',
				'Linha',
				'UN',
				'Data Últ. compra',
				'Estoque',
				'Em Trânsito',
				'% Part.',
				$meses['atual'],
				$meses['mes_1'],
				$meses['mes_2'],
				'Prox',
				'Pedidos Abertos',
				'Saldo',
				'Vendas Total',
				'Vendas Media Dias',
				'Vendas % Part.',
				'Média Mês',
				'Estoque Meses',
				'Neces. Comp. Dias',
				'Giro',
			];
		}else{
			$retorno = [
				'Grupo',
				'Marca',
				'Linha',
				'Código',
				'Descrição',
				'UN',
				'Data Últ. compra',
				'Custo Gerencial',
				'Preço Venda',
				'Estoque',
				'Em Trânsito',
				'% Part.',
				$meses['atual'],
				$meses['mes_1'],
				$meses['mes_2'],
				'Prox',
				'Pedidos Abertos',
				'Saldo',
				'Vendas Total',
				'Vendas Media Dias',
				'Vendas % Part.',
				'Média Mês',
				'Estoque Meses',
				'Neces. Comp. Dias',
				'Giro',
			];
		}
		return $retorno;
	}

	public function collection() {
		$this->request->request->add(['export' => 'true']);
		$GiroDeEstoqueControllerObj = new GiroDeEstoqueController;
		$resultado = $this->tratarResultado($GiroDeEstoqueControllerObj->filtroTelaGiroDeEstoque($this->request));
		return $resultado;
	}

	private function tratarResultado($resultado_temp){
		$resultado = [];
		$total = [
			'estoque' => 0,
			'estoque_transito' => 0,
			'participacao_estoque' => 0,
			'compras_mes_atual' => 0,
			'compras_proximo_mes' => 0,
			'compras_mes_seguinte' => 0,
			'compras_proximos_meses' => 0,
			'pedidos_aberto' => 0,
			'saldo' => 0,
			'vendas' => 0,
			'media_diaria' => 0,
			'participacao_vendas' => 0,
			'media_mes' => 0,
			'estoque_meses' => 0,
			'compras_necessidade' => 0,
		];
		foreach($resultado_temp as $value){
			$linha = [];
			if($this->request->produto_grupo == 'grupo'){
				$linha = [
					$value['grupo'],
					$value['marca'],
					$value['linha'],
					$value['unidade'],
					$value['ultima_compra'],
					parserNumber($value['estoque']),
					parserNumber($value['estoque_transito']),
					parserNumber($value['participacao_estoque']),
					parserNumber($value['compras_mes_atual']),
					parserNumber($value['compras_proximo_mes']),
					parserNumber($value['compras_mes_seguinte']),
					parserNumber($value['compras_proximos_meses']),
					parserNumber($value['pedidos_aberto']),
					parserNumber($value['saldo']),
					parserNumber($value['vendas']),
					parserNumber($value['media_diaria']),
					parserNumber($value['participacao_vendas']),
					parserNumber($value['media_mes']),
					parserNumber($value['estoque_meses']),
					parserNumber($value['compras_necessidade']),
					strip_tags($value['giro'])
				];
			}else{
				$linha = [
					$value['grupo'],
					$value['marca'],
					$value['linha'],
					$value['codigo'],
					$value['descricao'],
					$value['unidade'],
					$value['ultima_compra'],
					parserNumber($value['custo_gerencial']),
					parserNumber($value['preco_venda']),
					parserNumber($value['estoque']),
					parserNumber($value['estoque_transito']),
					parserNumber($value['participacao_estoque']),
					parserNumber($value['compras_mes_atual']),
					parserNumber($value['compras_proximo_mes']),
					parserNumber($value['compras_mes_seguinte']),
					parserNumber($value['compras_proximos_meses']),
					parserNumber($value['pedidos_aberto']),
					parserNumber($value['saldo']),
					parserNumber($value['vendas']),
					parserNumber($value['media_diaria']),
					parserNumber($value['participacao_vendas']),
					parserNumber($value['media_mes']),
					parserNumber($value['estoque_meses']),
					parserNumber($value['compras_necessidade']),
					strip_tags($value['giro'])
				];
			}

			$total['estoque'] += empty($value['estoque'])? 0 : parserNumber($value['estoque']);
			$total['estoque_transito'] += empty($value['estoque_transito'])? 0 : parserNumber($value['estoque_transito']);
			$total['participacao_estoque'] += empty($value['participacao_estoque'])? 0 : parserNumber($value['participacao_estoque']);
			$total['compras_mes_atual'] += empty($value['compras_mes_atual'])? 0 : parserNumber($value['compras_mes_atual']);
			$total['compras_proximo_mes'] += empty($value['compras_proximo_mes'])? 0 : parserNumber($value['compras_proximo_mes']);
			$total['compras_mes_seguinte'] += empty($value['compras_mes_seguinte'])? 0 : parserNumber($value['compras_mes_seguinte']);
			$total['compras_proximos_meses'] += empty($value['compras_proximos_meses'])? 0 : parserNumber($value['compras_proximos_meses']);
			$total['pedidos_aberto'] += empty($value['pedidos_aberto'])? 0 : parserNumber($value['pedidos_aberto']);
			$total['saldo'] += empty($value['saldo'])? 0 : parserNumber($value['saldo']);
			$total['vendas'] += empty($value['vendas'])? 0 : parserNumber($value['vendas']);
			$total['media_diaria'] += empty($value['media_diaria'])? 0 : parserNumber($value['media_diaria']);
			$total['participacao_vendas'] += empty($value['participacao_vendas'])? 0 : parserNumber($value['participacao_vendas']);
			$total['media_mes'] += empty($value['media_mes'])? 0 : parserNumber($value['media_mes']);
			$total['estoque_meses'] += empty($value['estoque_meses'])? 0 : parserNumber($value['estoque_meses']);
			$total['compras_necessidade'] += empty($value['compras_necessidade'])? 0 : parserNumber($value['compras_necessidade']);

			$resultado[] = $linha;
		}

		if($this->request->produto_grupo == 'grupo'){
			$linha = [
				"",
				"",
				"",
				"",
				"",
				$total['estoque'],
				$total['estoque_transito'],
				$total['participacao_estoque'],
				$total['compras_mes_atual'],
				$total['compras_proximo_mes'],
				$total['compras_mes_seguinte'],
				$total['compras_proximos_meses'],
				$total['pedidos_aberto'],
				$total['saldo'],
				$total['vendas'],
				$total['media_diaria'],
				$total['participacao_vendas'],
				$total['media_mes'],
				$total['estoque_meses'],
				$total['compras_necessidade'],
				""
			];
		}else{
			$linha = [
				"",
				"",
				"",
				"",
				"",
				"",
				"",
				"",
				"",
				$total['estoque'],
				$total['estoque_transito'],
				$total['participacao_estoque'],
				$total['compras_mes_atual'],
				$total['compras_proximo_mes'],
				$total['compras_mes_seguinte'],
				$total['compras_proximos_meses'],
				$total['pedidos_aberto'],
				$total['saldo'],
				$total['vendas'],
				$total['media_diaria'],
				$total['participacao_vendas'],
				$total['media_mes'],
				$total['estoque_meses'],
				$total['compras_necessidade'],
				""
			];
		}

		$resultado[] = $linha;
		return collect($resultado);
	}
}
