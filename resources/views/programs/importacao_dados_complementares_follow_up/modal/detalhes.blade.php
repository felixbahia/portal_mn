@extends('layouts.page-dialog')
@section('content')
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link active" id="importacao-dados_gerais-tab" data-toggle="tab" href="#importacao_dados_gerais" role="tab" aria-controls="importacao_dados_gerais" aria-selected="false">Dados Gerais</a>
    </li>
	<li class="nav-item">
        <a class="nav-link" id="importacao-produtos-tab" data-toggle="tab" href="#importacao_produtos" role="tab" aria-controls="importacao_produtos" aria-selected="false">Produtos</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="importacao-follow_up-lead_time-tab" data-toggle="tab" href="#importacao_follow_up_lead_time" role="tab" aria-controls="importacao_follow_up_lead_time" aria-selected="false">Follow Up</a>
    </li>
</ul>
	  
<div class="tab-content pt-3" id="ImportacaoHeaderContainer">
    <div class="tab-pane show active" id="importacao_dados_gerais" role="tabpanel" aria-labelledby="dados-tab">
		<div class="col-lg-12">			
			<div class="row">
				<div class="col-lg-12">
					<h5><b>Proforma</b> {{ $dados['proforma'] }} - <b>PCMN:</b> {{ $dados['pcmn'] }}</h5>
				</div>
			</div>
            <hr>
            <div class='pedido_detalhes_content'>
				<div class="row">
					<div class="col-sm-6">
						<b>Fornecedor:</b><br>
						{{ $dados['fornecedor'] }}
					</div>
                    <div class="col-sm-6">
                        <b>Representante:</b><br>
                        {{ $dados['respresentante'] }}
                    </div>
                </div>
                
                <div class="row">
					<div class="col-sm-12">
						<b>Referência:</b><br>
						{{ $dados['referencia'] }}
					</div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
						<b>Data da Proforma:</b><br>
						{{ $dados['data_proforma'] }}
					</div>
                    <div class="col-sm-4">
						<b>Previsão da Carta Programa:</b><br>
						{{ $dados['data_previsao_carta_programa'] }}
					</div>
                    <div class="col-sm-4">
						<b>Previsão de Recebimento:</b><br>
						{{ $dados['data_previsao_recebimento'] }}
					</div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-12">
                        <b>Data Embarque:</b><br>
                        
                    </div>
                </div>
            </div>
		</div>
	</div>

    <div class="tab-pane" id="importacao_produtos" role="tabpanel" aria-labelledby="dados-tab">
		<div class="content-dialog-table">
            <div class="content-dialog-table">
                <table class="table table-striped table-filter-dialog" id="table-filters-produtos-proforma">
                    <thead>
                        <tr>
                            <th>Item nº</th>
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Composição</th>
                            <th class="tb_number">Gramatura GM2</th>
                            <th class="tb_number">Largura</th>
                            <th>Gramatura GML</th>
                            <th>Rend.</th>
                            <th>Inst. de Lavagem</th>
                            <th>Sta.</th>
                            <th class="tb_number">QTD Prev.</th>
                            <th class="tb_number">QTD Real.</th>
                            <th class="tb_number">Preço FOB Unitário</th>
                            <th class="tb_number">Valor FOB Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dados['itens'] as $item)
                        <tr>
                            <td>{{ $item['item'] }}</td>
                            <td>{!! $item['foto'] !!} {!! $item['codigo'] !!}</td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="{{ $item['produto'] }}">{{ $item['produto'] }}</div></div></td>
                            <td class="tb_number"><div><div data-toggle="tooltip" data-html="true" title="{{ $item['composicao'] }}">{{ $item['composicao'] }}</div></div><</td>
                            <td class="tb_number">{{ $item['gramatura_gm2'] }}</td>
                            <td>{{ $item['largura'] }}</td>
                            <td>{{ $item['gramatura_gml'] }}</td>
                            <td>{{ $item['rendimento'] }}</td>
                            <td>{{ $item['instrucao_lavagem'] }}</td>
                            <td class="tb_number">{{ $item['status'] }}</td>
                            <td class="tb_number">{{ $item['quantidade'] }}</td>
                            <td class="tb_number">{{ $item['quantidade_realizada'] }}</td>
                            <td class="tb_number">{{ $item['preco_unitario'] }}</td>
                            <td class="tb_number">{{ $item['preco_total'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="tb_number"></td>
                            <td class="tb_number"></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="tb_number">Total:</td>
                            <td class="tb_number">{{$dados['total']['quantidade']}}</td>
                            <td class="tb_number">{{$dados['total']['quantidade_realizada']}}</td>
                            <td class="tb_number"></td>
                            <td class="tb_number">{{$dados['total']['preco_fob']}}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

		</div>
	</div>

    <div class="tab-pane" id="importacao_follow_up_lead_time" role="tabpanel" aria-labelledby="dados-tab">
        @foreach($dados['dados_follow'] as $dados_follow)
            <h5>{{$dados_follow['produto']}} - Tipo: {{$dados_follow['tipo_cor']}}</h5><br>
            <table class="table">
                <tr>
                    <th width="12%"></th>
                    <th width="11%">Previsão Envio/Termino</th>
                    <th width="11%">Enviado/Termino</th>
                    <th width="11%">Transportadora</th>
                    <th width="11%">AWB</th>
                    <th width="11%">Recebido</th>
                    <th width="11%">Revisão</th>
                    <th width="11%">Data Apr.</th>
                    <th width="12%">Status Apr.</th>
                </tr>
                <tr>
                    <td>Envio da Cores</td>
                    <td>{{$dados_follow['envio_cor_data_previsao']}}</td>
                    <td>{{$dados_follow['envio_cor_data_envio']}}</td>
                    <td></td>
                    <td></td>
                    <td>{{$dados_follow['envio_cor_data_recebido']}}</td>
                    <td>{{$dados_follow['envio_cor_data_revisao']}}</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Quality Sample</td>
                    <td>{{$dados_follow['quality_sample_data_previsao_envio']}}</td>
                    <td>{{$dados_follow['quality_sample_data_envio']}}</td>
                    <td>{{$dados_follow['quality_sample_transportadora']}}</td>
                    <td>{{$dados_follow['quality_sample_awb']}}</td>
                    <td>{{$dados_follow['quality_sample_data_recebido']}}</td>
                    <td></td>
                    <td>{{$dados_follow['quality_sample_data_aprovacao']}}</td>
                    <td>{{$dados_follow['quality_sample_aprovacao']}}</td>
                </tr>
                <tr>
                    <td>Laboratório</td>
                    <td>{{$dados_follow['laboratorio_data_previsao_envio']}}</td>
                    <td>{{$dados_follow['laboratorio_data_envio']}}</td>
                    <td></td>
                    <td></td>
                    <td>{{$dados_follow['laboratorio_data_recebido']}}</td>
                    <td></td>
                    <td>{{$dados_follow['laboratorio_data_aprovacao']}}</td>
                    <td>{{$dados_follow['laboratorio_aprovacao']}}</td>
                </tr>
                <tr>
                    <td>Termino da Produção</td>
                    <td>{{$dados_follow['tempo_producao_previsao_termino']}}</td>
                    <td>{{$dados_follow['tempo_producao_termino']}}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Amostra Embarque</td>
                    <td>{{$dados_follow['amostra_embarque_data_previsao_envio']}}</td>
                    <td>{{$dados_follow['amostra_embarque_data_envio']}}</td>
                    <td>{{$dados_follow['amostra_embarque_transportadora']}}</td>
                    <td>{{$dados_follow['amostra_embarque_awb']}}</td>
                    <td>{{$dados_follow['amostra_embarque_data_recebido']}}</td>
                    <td></td>
                    <td>{{$dados_follow['amostra_embarque_data_aprovacao']}}</td>
                    <td>{{$dados_follow['amostra_embarque_aprovacao']}}</td>
                </tr>
                <tr>
                    <td>Autorização do Embarque</td>
                    <td>{{$dados_follow['autorizacao_embarque_data_previsao_envio']}}</td>
                    <td>{{$dados_follow['autorizacao_embarque_data_envio']}}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </table>
            </br>
            </br>
            <hr>
        @endforeach
    </div>

</div>
<script>
    $(document).ready( function () {
        table_produtos_proforma_options = {
            "searching": false,
            "paging": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum registro inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "tb_date" },
                
            ],
            "order": [[ 0, 'asc' ]]
        };
        table_produtos_proforma = '';
        table_produtos_proforma = $(document).find('#table-filters-produtos-proforma').DataTable(table_produtos_proforma_options);
        table_produtos_proforma.draw();

        table_outras_depesas_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "15vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum registro inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "tb_date" },
                {
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    'width': '5px',
                    "orderable": false
                }
            ],
            "order": [[ 0, 'asc' ]]
        };
        table_outras_depesas = '';
        table_outras_depesas = $(document).find('#table-outras_despesas').DataTable(table_outras_depesas_options);
        table_outras_depesas.draw();
    });
</script>
@endsection