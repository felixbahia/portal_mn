<?php

namespace App\Exports;

use App\Campanha;
use App\PedidoPortal;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
class CampanhaExportarExcelExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $request;

	public function __construct($request){
		$this->request = $request->nome;
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
        set_time_limit(12000);
        ini_set('memory_limit','2048M');
        
        $nome = $this->request;
        $campanha = Campanha::where('nome',$nome)
        ->with(['periodoApuracao.tipoApuracao','estabelecimentosCampanha.estabelecimentoNasajon','produtosCampanha' => function($query){
            $query->withTrashed()
            ->with(['produtoEspecificacao.produtoGrupo.segmento','produtoEstoque']);
        },'tipoComissaoRepresentante','tipoComissaoVendedorInterno','tipoComissaoGerente','log.tipoAcoes','log.createdBy',
        'periodoApuracao.consultaMetaVendedoresPeriodo.campanhasConsultaMetaVendedores.periodoMetaProduto.produtosContagem' => function($query){
            $query->select(DB::raw('produto_codigo, campanhas_consulta_meta_vendedores_produto_id, (sum((metros + kilo + unidade)) - sum(devolucao_metragem)) as quantidade_vendida'))
            ->groupBy('produto_codigo','campanhas_consulta_meta_vendedores_produto_id');
        }])
        ->first();

        $retorno = collect();
        $retorno->push(['Nome da Campanha','Início da Campanha','Fim da Camopanha',
        'Comissão Representante','Tipo de Comissão Representante',
        'Comissão Vendedor','Tipo de Comissão Vendedor',
        'Comissão Gerentes','Tipo de Comissão Gerentes',
        'Ativa']);

        $periodos = [];

        $retorno->push([$campanha->nome,parserData($campanha->inicio_campanha),parserData($campanha->fim_campanha),
        (!empty($campanha->comissao_representante)) ? parserValor($campanha->comissao_representante) : parserValor(0),
        (!empty($campanha->tipoComissaoRepresentante->tipo_comissao)) ? $campanha->tipoComissaoRepresentante->tipo_comissao : 'N/D',
        (!empty($campanha->comissao_vendedor_interno)) ? parserValor($campanha->comissao_vendedor_interno) : parserValor(0),
        (!empty($campanha->tipoComissaoVendedorInterno->tipo_comissao)) ? $campanha->tipoComissaoVendedorInterno->tipo_comissao : 'N/D',
        (!empty($campanha->comissao_gerente)) ? parserValor($campanha->comissao_gerente) : parserValor(0),
        (!empty($campanha->tipoComissaoGerente->tipo_comissao)) ? $campanha->tipoComissaoGerente->tipo_comissao : 'N/D',
        ($campanha->ativo == true) ? 'Sim' : 'Não']);

        $retorno->push(['-','-','-','-','-','-','-','-','-','-']);
        $retorno->push(['Período de Meta','Início do Período','Fim do Período','Meta de Metros','Meta de Valor']);
        $periodos[0] = 'Código Produto';
        $periodos[1] = 'Descrição Produto';
        $periodos[2] = 'Segmento';
        $periodos[3] = 'Ativo';
        $periodos[4] = 'Saldo';
        $produtos_periodos = [];

        if(!empty($campanha->periodoApuracao[0])){
            $campanha->periodoApuracao->each(function($query) use (&$retorno,&$periodos,&$produtos_periodos){
                $periodos[parserData($query->inicio_periodo).'-'.parserData($query->fim_periodo)] = 'Período '.$query->tipoApuracao->tipo.' - '.parserData($query->inicio_periodo).' - '.parserData($query->fim_periodo).' QTD Vendida';
               
                if(!empty($query->consultaMetaVendedoresPeriodo)){
                    foreach($query->consultaMetaVendedoresPeriodo->campanhasConsultaMetaVendedores->periodoMetaProduto as $consulta_periodo){
                        foreach($consulta_periodo->produtosContagem as $produto_contagem){
                                if(!isset($produtos_periodos[$query->inicio_periodo.' - '.$query->fim_periodo][$produto_contagem->produto_codigo])){
                                    $produtos_periodos[$query->inicio_periodo.' - '.$query->fim_periodo][$produto_contagem->produto_codigo] = [
                                        'quantidade_vendida' => ($produto_contagem->quantidade_vendida)
                                    ];
                                }else{
                                    $produtos_periodos[$query->inicio_periodo.' - '.$query->fim_periodo][$produto_contagem->produto_codigo]['quantidade_vendida'] += $produto_contagem->quantidade_vendida;
                                }
                        }
                    }
                    
                }

                $retorno->push([$query->tipoApuracao->tipo,parserData($query->inicio_periodo),parserData($query->fim_periodo),
                (!empty($query->meta_metros)) ? parserValor($query->meta_metros) : parserValor(0),
                (!empty($query->meta_reais)) ? parserValor($query->meta_reais) : parserValor(0)]);
            });
        }

        $retorno->push(['-','-','-','-','-','-','-','-','-','-']);
        $retorno->push(['Estabelecimentos']);

        if(!empty($campanha->estabelecimentosCampanha[0])){
            $campanha->estabelecimentosCampanha->each(function($query) use (&$retorno){
                $retorno->push([$query->estabelecimento_codigo.' - '.$query->estabelecimentoNasajon->descricao]);
            });
        }

        $retorno->push(['-','-','-','-','-','-','-','-','-','-']);
        $retorno->push($periodos);

        $itens_portal = collect();
        $produtos_codigos = $campanha->produtosCampanha->unique('produto_codigo')->pluck('produto_codigo')->toArray();
        
        $pedido_portal = PedidoPortal::with(['itens_pedido'])
        ->whereNotIn('status_pedido', [3, 5, 7])
        ->whereHas('itens_pedido', function($query) use ($produtos_codigos){
            $query->whereIn("cod_produto", $produtos_codigos);
        })
        ->get();

        $pedido_portal->each(function($query) use (&$itens_portal){
            foreach($query->itens_pedido as $itens){
                $itens_portal->push([
                    'codigo_produto' =>  $itens->cod_produto,
                    'quantidade' => $itens->quantidade
                ]);
            }
        });

        unset($pedido_portal);

        if(!empty($campanha->produtosCampanha[0])){
            $campanha->produtosCampanha->each(function($query) use (&$retorno,$periodos,$campanha,$itens_portal,&$produtos_periodos){
                $quantidade = 0;
                $saldo_portal = ($itens_portal->where('codigo_produto',$query->produto_codigo)->sum('quantidade') > 0) ? $itens_portal->where('codigo_produto',$query->produto_codigo)->sum('quantidade') : 0;
                $saldo = $query->produtoEstoque->sum('estoque') - $saldo_portal;
                $produto_saida = [];
                $produto_saida[] = $query->produto_codigo;
                $produto_saida[] = (!empty($query->produtoEspecificacao->descricao)) ? $query->produtoEspecificacao->descricao : '';
                $produto_saida[] = (!empty($query->produtoEspecificacao->produtoGrupo->segmento)) ? $query->produtoEspecificacao->produtoGrupo->segmento->descricao : '';
                $produto_saida[] = (empty($query->deleted_at)) ? 'Sim' : 'Não';
                $produto_saida[] = parserValor($saldo);

                foreach($periodos as $key => $periodo){
                    if($key === 0 || $key === 1 || $key === 2 || $key === 3 || $key === 4){
                        continue;
                    }

                    $valor_periodo = 0;
                    $datas = explode('-',$key);
                    $data_inicial = Carbon::createFromFormat('d/m/Y',$datas[0])->format('Y-m-d 00:00:00');
                    $data_final = Carbon::createFromFormat('d/m/Y',$datas[1])->format('Y-m-d 00:00:00');

                    if(isset($produtos_periodos[$data_inicial.' - '.$data_final][$query->produto_codigo])){
                        $valor_periodo = $produtos_periodos[$data_inicial.' - '.$data_final][$query->produto_codigo]['quantidade_vendida'];
                    }

                    $produto_saida[] = ($valor_periodo > 0) ? $valor_periodo : '';
                 }

                $retorno->push($produto_saida);
            });
        }

        $retorno->push(['-','-','-','-','-','-','-','-','-','-']);
        $retorno->push(['Log', 'Ação', 'Cliente do Sistema','Data e Hora']);
        
        if(!empty($campanha->log[0])){
            $campanha->log->each(function($query) use (&$retorno){
                $retorno->push([$query->descricao,$query->tipoAcoes->acao,$query->createdBy->name,parserDataEHora($query->created_at)]);
            });
        }

        return $retorno;
    }
}
