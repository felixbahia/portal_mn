<div class="content-cliente-posicao-sintetica content-cliente-posicao-sintetica-modal">
	<form action="#" name='form_filter_modal' id="form_filter_modal" onsubmit="return false;">
		@csrf
		{!! Form::hidden('dados_devolucao', '', ['id' => 'dados_devolucao']) !!}
		<input type="hidden" class="input-search-bt" name="codigo" id="codigo_modal_analise" value="{{ $dados['cliente']['codigo'] }}"/>
		<input type="hidden" class="input-search-bt" name="codigo" id="codigo_modal_analise" value="{{ $dados['cliente']['codigo'] }}" form = 'form_filter'/>
		<div id='info_cliente' class='text-center float-right info-cliente'>
			{!! $dados['informacoes_cliente_link'] !!}
		</div>
		<div id="cpf-cnpj-unico-div" class="row cpf-cnpj-unico-div mb-3 @if (!is_array($dados['cnpj_array'])) d-none @endif" >
			<div class="col-sm-3">
				<select name='cpf_cnpj_unico' id ='cpf_cnpj_unico' class='form-control'>
					@if (is_array($dados['cnpj_array']))
						<option value=''>Todos</option>
						@foreach ($dados['cnpj_array'] as $value)
						<option value='{{ $value['codcad'] }}' {{ $value['selecionado']?"'selected'":"" }}>{{ $value['label'] }}</option> >
						@endforeach
					@endif
				</select>
			</div>
			<div class="col-sm-3 content-buttons">
				<button name="btn-filter" id="btn-filter" class="btn azul-sistema btn-filter">Buscar</button>	
			</div>
		</div>
		<div>
			<ul class="nav nav-tabs">
				<li class="nav-item">
					<a class="nav-link active" id='posicao-sintetica-header-tab' data-toggle="tab" href="#posicao_sintetica_header" role="tab" aria-controls="posicao_sintetica_header" aria-selected="true">Posição sintética</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="posicao-sintetica-duplicatas-tab" data-toggle="tab" onclick="chamarTitulosPagos()" href="#posicao_sintetica_titulos_pagos" role="tab" aria-controls="posicao_sintetica_titulos_pagos" aria-selected="false">Títulos Pagos</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="posicao-sintetica-renegociados-tab" data-toggle="tab" onclick="chamarTitulosRenegociados()" href="#posicao_sintetica_titulos_renegociados" role="tab" aria-controls="posicao_sintetica_titulos_renegociados" aria-selected="false">Títulos Renegociados</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="posicao-sintetica-historico-tab" data-toggle="tab" href="#posicao_sintetica_historico" role="tab" aria-controls="posicao_sintetica_historico" aria-selected="false">Histórico Cobrança</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="posicao-sintetica-devolucoes-tab" data-toggle="tab" href="#posicao_sintetica_devolucoes"  onclick="dadosDevolucao()" role="tab" aria-controls="posicao_sintetica_devolucoes" aria-selected="false">Devoluções</a>
				</li>	
				<li class="nav-item">
					<a class="nav-link" id="posicao-sintetica-devolucoes-tab" data-toggle="tab" onclick="chamarFormaPagamento()" href="#posicao_sintetica_forma_pagamento" role="tab" aria-controls="posicao_sintetica_forma_pagamento" aria-selected="false">Titulos Pagos por Forma de Pagamento</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" id="posicao-sintetica-negociacao-titulos-tab" data-toggle="tab" href="#posicao_sintetica_negociacao_titulos" role="tab" aria-controls="posicao_sintetica_negociacao_titulos" aria-selected="false"><div id="aba_acompanhamento_regenociacao">Acomp. Renegociação</div></a>
				</li>
			</ul>
		</div>
	</form>
	<div class="tab-content" id="PedidoHeaderContainer">
		<div class="tab-pane show active" id="posicao_sintetica_header" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-table_posicao">
				<div class="row-header-table_posicao">
					<div class="cell-table_posicao"></div>
					<div class="cell-table_posicao">A Vencer</div>
					<div class="cell-table_posicao">Vencidos</div>
					<div class="cell-table_posicao">Total</div>
				</div>
				<div>
					<div class="row-table_posicao">
						<div class="cell-table_posicao">Títulos Faturados</div>
						<div class="cell-table_posicao" id="a_vencer-faturado"><a href="#" class="bt-modal-open" data-codigo="{!! $dados['cliente']['codigo'] !!}" data-title-modal="Titulos Faturados - À vencer - {!! $dados['titulo_modal'] !!}" data-route="{{ route('cliente.posicao_sintetica.titulos_faturados', ['coluna'=>'a_vencer']) }}">{!! $dados['titulos_faturados']['a_vencer'] !!}</a></div>
						<div class="cell-table_posicao" id="vencido-faturado"><a href="#" class="bt-modal-open" data-codigo="{!! $dados['cliente']['codigo'] !!}" data-title-modal="Titulos Faturados - Vencidos - {!! $dados['titulo_modal'] !!}" data-route="{{ route('cliente.posicao_sintetica.titulos_faturados', ['coluna'=>'vencidos']) }}">{!! $dados['titulos_faturados']['vencidas'] !!}</a></div>
						<div class="cell-table_posicao" id="total-faturado"><a href="#" class="bt-modal-open" data-codigo="{!! $dados['cliente']['codigo'] !!}" data-title-modal="Titulos Faturados - Total - {!! $dados['titulo_modal'] !!}" data-route="{{ route('cliente.posicao_sintetica.titulos_faturados', ['coluna'=>'total']) }}">{!! $dados['titulos_faturados']['total'] !!}</a></div>
					</div>
					<div class="row-table_posicao d-none" id='row-terceiros'>
						<div class="cell-table_posicao">Títulos de terceiros</div>
						<div class="cell-table_posicao" id='a_vencer-terceiros'></div>
						<div class="cell-table_posicao" id='vencido-terceiros'></div>
						<div class="cell-table_posicao" id="total-terceiros"></div>
					</div>
					<div class="row-table_posicao">
						<div class="cell-table_posicao">Créditos</div>
						<div class="cell-table_posicao"></div>
						<div class="cell-table_posicao"></div>
						<div class="cell-table_posicao" id="total-notas_credito"><a href="#" class="bt-modal-open" data-codigo="{!! $dados['cliente']['codigo'] !!}" data-title-modal="Créditos - Total - {!! $dados['titulo_modal'] !!}" data-route="{{ route('cliente.posicao_sintetica.notas_credito', ['coluna'=>'total']) }}">{!! $dados['notas_credito'] !!}</a></div>
					</div>
					<div class="row-table_posicao">
						<div class="cell-table_posicao">Notas de Débito</div>
						<div class="cell-table_posicao" id="a_vencer-notas_debito"><a href="#" class="bt-modal-open" data-codigo="{!! $dados['cliente']['codigo'] !!}" data-title-modal="Notas de Débito - A Vencer - {!! $dados['titulo_modal'] !!}" data-route="{{ route('cliente.posicao_sintetica.notas_debito', ['coluna'=>'a_vencer']) }}">{!! $dados['notas_debito']['a_vencer'] !!}</a></div>
						<div class="cell-table_posicao" id="vencido-notas_debito"><a href="#" class="bt-modal-open" data-codigo="{!! $dados['cliente']['codigo'] !!}" data-title-modal="Notas de Débito - Vencidas - {!! $dados['titulo_modal'] !!}" data-route="{{ route('cliente.posicao_sintetica.notas_debito', ['coluna'=>'vencidos']) }}">{!! $dados['notas_debito']['vencidas'] !!}</a></div>
						<div class="cell-table_posicao" id="total-notas_debito"><a href="#" class="bt-modal-open" data-codigo="{!! $dados['cliente']['codigo'] !!}" data-title-modal="Notas de Débito - Total - {!! $dados['titulo_modal'] !!}" data-route="{{ route('cliente.posicao_sintetica.notas_debito', ['coluna'=>'total']) }}">{!! $dados['notas_debito']['total'] !!}</a></div>
					</div>
					<div class="row-table_posicao d-none">
						<div class="cell-table_posicao">Títulos pré pagos</div>
						<div class="cell-table_posicao" id="a_vencer-pedidos-pre-pagos"></div>
						<div class="cell-table_posicao"></div>
						<div class="cell-table_posicao" id="total-pedidos-pre-pagos"></div>
					</div>
					<div class="row-table_posicao">
						<div class="cell-table_posicao">Saldo Atual</div>
						<div class="cell-table_posicao" id="a_vencer-total">{!! $dados['total']['a_vencer'] !!}</div>
						<div class="cell-table_posicao" id="vencido-total">{!! $dados['total']['vencidas'] !!}</div>
						<div class="cell-table_posicao" id="total-total">{!! $dados['total']['total'] !!}</div>
					</div>
				</div>
				<div class="pedidos">
					<div class="title-pedidos-pedidos">Pedidos</div>
					<div class="pedidos-orcamentos">
						<div class="title-pedidos">Orçamentos:</div>
						<div class="valor-pedidos" id="pedidos-orcamentos"><a href="#" onclick="showPedidos('orcamento', '{{ $dados['cliente']['codigo'] }}', '{{ $dados['cliente']['unico'] }}')"> {!! $dados['pedidos']['orcamentos']??'' !!}</a></div>
					</div>
					<div class="pedidos-carteira">
						<div class="title-pedidos">Carteira:</div>
						<div class="valor-pedidos" id="pedidos-carteira"><a href="#" onclick="showPedidos('carteira', '{{ $dados['cliente']['codigo'] }}', '{{ $dados['cliente']['unico'] }}')"> {!! $dados['pedidos']['carteira'] !!}</a></div>
					</div>
					<div class="pedidos-total">
						<div class="title-pedidos">Total:</div>
						<div class="valor-pedidos" id="pedidos-total"><a href="#" onclick="showPedidos('total', '{{ $dados['cliente']['codigo'] }}', '{{ $dados['cliente']['unico'] }}')"> {!! $dados['pedidos']['total'] !!}</a></div>
					</div>
				</div>
				<div class="line_limitcred_datedesde">
					<div class="cliente-desde">
						<div class="title-data">Cliente desde:</div>
						<div class="value-data" id="cliente-desde">{!! $dados['cliente_desde'] !!}</div>
					</div>
					<div class="ultima-alteracao">
						<div class="title-ultima-alteracao">Última Alteração:</div>
						<div class="valor-ultima-alteracao" id="ultima-alteracao"></div>
					</div>
					<div class="limite-credito">
						<div class="title-limite-credito">Limite de Crédito:</div>
						<div class="valor-limite-credito" id="limite-credito">{!! $dados['limite_credito'] !!}</div>
					</div>
					<div class="vencimento-credito">
						<div class="title-vencimento-credito">Valido até:</div>
						<div class="valor-vencimento-credito" id="vencimento-credito">{!! $dados['vencimento_credito'] !!}</div>
					</div>
				</div>
				<div class="line_consulta_serasa_motivo_reavaliacao">
					<div class="ultima_consulta_serasa">
					<div class="title">Última consulta no SERASA:
					<br>
					<div class="title">Blacklist:</div><div class="valor" id="status_blacklist">{!! $dados['blacklist'] !!}</div></div>
					<div class="valor" id="ultima_consulta_serasa">{!! $dados['consulta_serasa'] !!}</div>
					</div>
					<div class="motivo_reavaliacao">
						<div class="title">Motivo da reavaliação do crédito:</div>
						<div class="valor" id="motivo_reavaliacao">{!! $dados['motivo_reavaliacao'] !!}</div>
					</div>
				</div>
				<div class="message-salva-cliente" id="mensagem">
				</div>
				<div class="observacao_agrupado">
					{!! $dados['messagem_agrupada_cnpjs'] !!}
				</div>
			</div>
			<div class="content-lateral-table">
				<div class="content-atrasos">
					<div class="title-atraso">Atrasos</div>
					<div class="header-content-atraso-vendas">
						<div>Data</div>
						<div>Dias</div>
					</div>
					<div class="content-ultimo-atraso">
						<label>Último</label>
						<div class="data-atraso" id="data-ultimo-atraso">{!! $dados['atraso']['ultima']['data'] !!}</div>
						<div class="dias-atraso" id="dias-ultimo-atraso">{!! $dados['atraso']['ultima']['quantidade'] !!}</div>
					</div>
					<div class="content-maior-atraso">
						<label>Maior</label>
						<div class="data-atraso" id="data-maior-atraso">{!! $dados['atraso']['maior']['data'] !!}</div>
						<div class="dias-atraso" id="dias-maior-atraso">{!! $dados['atraso']['maior']['quantidade'] !!}</div>
					</div>
				</div>
				<div class="content-vendas">
					<div class="title-vendas">Vendas</div>
					<div class="header-content-atraso-vendas">
						<div>Data</div>
						<div>Valor</div>
					</div>
					<div class="content-ultimo-vendas">
						<label>Último</label>
						<div class="data-vendas" id="data-ultima-venda">{!! $dados['vendas']['ultima']['data'] !!}</div>
						<div class="valor-vendas" id="valor-ultima-venda">{!! $dados['vendas']['ultima']['valor'] !!}</div>
					</div>
					<div class="content-maior-vendas">
						<label>Maior</label>
						<div class="data-vendas" id="data-maior-venda"> {!! $dados['vendas']['maior']['data'] !!}</div>
						<div class="valor-vendas" id="valor-maior-venda"> {!! $dados['vendas']['maior']['valor'] !!}</div>
					</div>
				</div>
				<div class="content-total-pago">
					<div class="title-total-vendas">Total pago ult 12 meses</div>
					<div class="valor-total-vendas" id="total-vendas-um-ano">{!! $dados['valores_a_faturar']??'' !!}</div>
				</div>
			</div>
		</div>
		<div class="tab-pane show" id="posicao_sintetica_titulos_pagos" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="row mb-4">
					<div class="col-sm-3">
						{{ Form::label('data_inicio_titulos_pagos', 'Inicio do período (Vencimento)') }}
						{{ Form::text('data_inicio_titulos_pagos', date("d/m/Y", strtotime("-1 year")), ['id' => 'data_inicio_titulos_pagos', 'class' => 'form-control data']) }}
					</div>
					<div class="col-sm-4">
						{{ Form::label('data_fim_titulos_pagos', 'Fim do período (Vencimento)') }}
						{{ Form::text('data_fim_titulos_pagos', date("d/m/Y"), ['id' => 'data_fim_titulos_pagos', 'class' => 'form-control data']) }}
					</div>
					<div class="col-sm-1">
						<br>
						{{ Form::button('Filtrar', ['class' => 'btn btn-primary btn-filter-titulos-pagos azul-sistema']) }}
					</div>
				</div>
				<div class="row">
					<div class="col-sm-3"><strong>Títulos: </strong><span id="titulos-contador"></span></div>
					<div class="col-sm-3"><strong>Valor: </strong><span id="titulos-valor"></span></div>
					<div class="col-sm-3"><strong>Em Atraso: </strong><span id="titulos-atraso"></span></div>
					<div class="col-sm-3"><strong>Dias Médios de Atraso: </strong><span id="titulos-media-atraso"></span></div>
				</div>
				<table class="table table-striped table-filter table-filter-clientes" id="table-filters-titulos-pagos">
					<thead>
						<tr>
							<th>Estab</th>
							<th class="cliente">Cliente</th>
							<th>Título</th>
							<th>Data Emissao</th>
							<th>Data Vencto</th>
							<th>Data pagto</th>
							<th>Dias atraso</th>
							<th>Valor Original</th>
							<th>Valor Pago</th>
							<th>Juros</th>
							<th>Desconto</th>
							<th>Port</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
		<div class="tab-pane show" id="posicao_sintetica_titulos_renegociados" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="row mb-4">
					<div class="col-sm-3">
						{{ Form::label('data_inicio_renegociacao', 'Inicio do período (Vencimento)') }}
						{{ Form::text('data_inicio_renegociacao', date("d/m/Y", strtotime("-1 year")), ['id' => 'data_inicio_renegociacao', 'class' => 'form-control data', 'form' => 'form_filter_modal']) }}
					</div>
					<div class="col-sm-4">
						{{ Form::label('data_fim_renegociacao', 'Fim do período (Vencimento)') }}
		                {{ Form::text('data_fim_renegociacao', date("d/m/Y"), ['id' => 'data_fim_renegociacao', 'class' => 'form-control data', 'form' => 'form_filter_modal']) }}
					</div>
					<div class="col-sm-1">
						<br>
		                {{ Form::button('Filtrar', ['class' => 'btn btn-primary btn-filter-titulos-renegociados']) }}
					</div>
				</div>
				<div class="row">
					<div class="col-sm-3"><strong>Títulos: </strong><span id="titulos-contador-renegociados"></span></div>
					<div class="col-sm-3"><strong>Valor: </strong><span id="titulos-valor-renegociados"></span></div>
				</div>
				<table class="table table-striped table-filter table-filter-clientes" id="table-filters-titulos-renegociados">
			        <thead>
			            <tr>
							<th>Estabelecimento</th>
							<th class="cliente">Cliente</th>
							<th>Título</th>
							<th class="date_format">Data Emissao</th>
							<th class="date_format">Data Vencimento</th>
							<th class="date_format">Data Renegociação</th>
							<th class="tb_number">Valor Original</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
		<div class="tab-pane show" id="posicao_sintetica_historico" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="row mb-1 mt-1">
					<div class="col align-self-end">
						{{ Form::button('Adicionar', ['class' => 'btn btn-success btn-adicionar-historico float-right']) }}
					</div>
				</div>
				<table class="table table-striped table-filter" id="table-filters-historico">
			        <thead>
			            <tr>
							<th data-sort='YYYYMMDDHHII' class="sort-date">Data / Hora</th>
							<th>Usuário</th>
							<th>Contato</th>
							<th>Retorno</th>
							<th>Titulos</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
		<div class="tab-pane" id="posicao_sintetica_devolucoes" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<table class="table table-striped table-not-edit" id="table-filters-devolucoes">
					<thead>
						<tr>
							<th class='tb_number'>Processo</th>
							<th class='tb_number'>Nota venda</th>
							<th class='tb_number'>Nota devolução</th>
							<th class='cliente tb_name'>Cliente</th>
							<th>Tipo Devolução</th>
							<th class='tb_name'>Motivo</th>
							<th class='tb_number'>Valor</th>
							<th class='tb_name'>Fase</th>
							<th class='tb_number'>Dias fase</th>
							<th class='tb_number'>Dias aberto</th>
							<th>Origem</th>
							<th class='tb_date'>Data requisição</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
		<div class="tab-pane" id="posicao_sintetica_forma_pagamento" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="row mb-4">
					<div class="col-sm-3">
						{{ Form::label('data_inicio_forma_pagamento', 'Inicio do período (Vencimento)') }}
						{{ Form::text('data_inicio_forma_pagamento', date("d/m/Y", strtotime("-1 year")), ['id' => 'data_inicio_forma_pagamento', 'class' => 'form-control data', 'form' => 'form_filter_modal']) }}
					</div>
					<div class="col-sm-4">
						{{ Form::label('data_fim_forma_pagamento', 'Fim do período (Vencimento)') }}
		                {{ Form::text('data_fim_forma_pagamento', date("d/m/Y"), ['id' => 'data_fim_forma_pagamento', 'class' => 'form-control data', 'form' => 'form_filter_modal']) }}
					</div>
					<div class="col-sm-1">
						<br>
		                {{ Form::button('Filtrar', ['class' => 'btn btn-primary btn-filter-forma-pagamento']) }}
					</div>
				</div>
				<table class="table table-striped table-not-edit" id="table-filters-forma-pagamento">
					<thead>
						<tr>
							<th>Forma</th>
							<th class='tb_number'>Títulos</th>
							<th class='tb_number'>Valor</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
					<tfoot>
						<td></td>
						<td></td>
						<td></td>
					</tfoot>    
				</table>
			</div>
		</div>
		<div class="tab-pane" id="posicao_sintetica_negociacao_titulos" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="row mb-4">
					<div class="col-sm-3">
						{{ Form::label('data_inicio_negociacao_titulos', 'Inicio do período') }}
						{{ Form::text('data_inicio_negociacao_titulos', date("d/m/Y", strtotime("-1 year")), ['id' => 'data_inicio_negociacao_titulos', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-4">
						{{ Form::label('data_fim_negociacao_titulos', 'Fim do período') }}
		                {{ Form::text('data_fim_negociacao_titulos', date("d/m/Y"), ['id' => 'data_fim_negociacao_titulos', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-1">
						<br>
		                {{ Form::button('Filtrar', ['class' => 'btn btn-primary', 'id' => 'btn_filtro_renegociacao']) }}
					</div>
				</div>
				<table class="table table-striped table-not-edit" id="table-filters-renegociacao-titulos">
					<thead>
						<tr>
							<th class='tb_date'>Data</th>
							<th>Titulos</th>
							<th class="tb_number">Valor dos Títulos</th>
							<th class="tb_number">Juros Atual.</th>
							<th class="tb_number">Valor Atual.</th>
							<th class="tb_number">Juros por Mês</th>
							<th class="tb_number">Valor Renegociação</th>
							<th class="tb_number">Parcelas</th>
							<th class="tb_number">Período(dias)</th>
							<th>status</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
					<tfoot>
						<td></td>
						<td></td>
						<td></td>
					</tfoot>    
				</table>
			</div>
		</div>
	</div>

</div>
<script type="text/javascript">

table_filters_titulos_pagos = {
	"searching": false,
	"lengthChange": false,
	"info": false,
	"paging": true,
	"pageLength": 10,
	"processing": true,
	"orderMulti": false,
	"language": {
		"decimal":        ",",
		"thousands":      ".",
		"emptyTable":     "Nenhum registro encontrado",
		"infoPostFix":    "",
		"loadingRecords": "Carregando...",
		"processing":     "Processando...",
		"zeroRecords":    "Nenhum registro encontrado",
		"paginate": {
			"first":      "<<",
			"last":       ">>",
			"next":       ">",
			"previous":   "<"
		}
	},
	'columnDefs': [
		{
			"targets": [6,7],
			"className": 'number_format',
		},
		{
			"targets": [3,4,5],
			"className": 'date_format',
		},
		{
			"targets": [0,1,2,-3,-2],
			"className": 'text_format',
		},
		
	]
};

table_filters_titulos = $("#table-filters-titulos-pagos").DataTable(table_filters_titulos_pagos);

table_filters_titulos_renegociados = $('#table-filters-titulos-renegociados').DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"orderMulti": false,
		"autoWidth": false,
		'paging': false,
		"language": {
			"decimal":        ",",
			"emptyTable":     "Nenhum registro encontrado",
			"infoPostFix":    "",
			"thousands":      ".",
			"loadingRecords": "Carregando...",
			"processing":     "Processando...",
			"zeroRecords":    "Nenhum registro encontrado",
			"paginate": {
				"first":      "<<",
				"last":       ">>",
				"next":       ">",
				"previous":   "<"
			}
		},
		"columnDefs": [
			{
				"targets": "tb_number",
				"class": "tb_number",
			},
			{
				"targets": "date_format",
				"class": "date_format",
			},
			{
				"targets": [0],
				"width": '10%',
				"class" : "text_format",
			},
		]
	});

table_filters_devolucoes = $('#table-filters-devolucoes').DataTable({
	"searching": false,
	"lengthChange": false,
	"info": false,
	"pageLength": 15,
	"orderMulti": false,
	'paging': false,
	"language": {
		"decimal":        ".",
		"emptyTable":     "Nenhum registro encontrado",
		"infoPostFix":    "",
		"thousands":      ",",
		"loadingRecords": "Carregando...",
		"processing":     "Processando...",
		"zeroRecords":    "Nenhum registro encontrado",
		"paginate": {
			"first":      "<<",
			"last":       ">>",
			"next":       ">",
			"previous":   "<"
		}
	},
	"columnDefs": [
		{
			"class": "tb_number", 
			"targets": "tb_number"
		},
		{ "class": "tb_date", targets: "tb_date" },
		{ targets: 'tb_name', width: '20%'}
	],
	"order": [[ 11, 'desc' ]]
});

table_filters_forma_pagamento = $('#table-filters-forma-pagamento').DataTable({
	"searching": false,
	"lengthChange": false,
	"info": false,
	"pageLength": 15,
	"orderMulti": false,
	'paging': false,
	"language": {
		"decimal":        ",",
		"emptyTable":     "Nenhum registro encontrado",
		"infoPostFix":    "",
		"thousands":      ".",
		"loadingRecords": "Carregando...",
		"processing":     "Processando...",
		"zeroRecords":    "Nenhum registro encontrado",
		"paginate": {
			"first":      "<<",
			"last":       ">>",
			"next":       ">",
			"previous":   "<"
		}
	},
	"columnDefs": [
		{
			"targets": "tb_number",
			"class": "tb_number",
		},
	]
});

table_historico_opt = {
	"searching": false,
	"lengthChange": false,
	"info": false,
	"paging": false,
	"pageLength": -1,
	"processing": true,
	"orderMulti": false,
	"language": {
		"decimal":        ",",
		"thousands":      ".",
		"emptyTable":     "Nenhum registro encontrado",
		"infoPostFix":    "",
		"loadingRecords": "Carregando...",
		"processing":     "Processando...",
		"zeroRecords":    "Nenhum registro encontrado",
		"paginate": {
			"first":      "<<",
			"last":       ">>",
			"next":       ">",
			"previous":   "<"
		}
	},
	"columnDefs": [
		{ "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
		{ "class": "tb_date", "type": "date", targets: "sort-date" }
	],
	"order": [0, 'desc']
};

table_historico = $("#table-filters-historico").DataTable(table_historico_opt);

table_filters_renegociacao_titulo = $('#table-filters-renegociacao-titulos').DataTable({
	"searching": false,
	"lengthChange": false,
	"info": false,
	"pageLength": 15,
	"orderMulti": false,
	"autoWidth": false,
	'paging': false,
	"language": {
		"decimal":        ",",
		"emptyTable":     "Nenhum registro encontrado",
		"infoPostFix":    "",
		"thousands":      ".",
		"loadingRecords": "Carregando...",
		"processing":     "Processando...",
		"zeroRecords":    "Nenhum registro encontrado",
		"paginate": {
			"first":      "<<",
			"last":       ">>",
			"next":       ">",
			"previous":   "<"
		}
	},
	"columnDefs": [
		{
			"targets": "tb_number",
			"class": "tb_number",
		},
		{
			"targets": "tb_date",
			"class": "tb_date",
		}
	]
});

var contador_forma_pagamento = 0;

$(document).ready( function () {
	$(document).find('.btn-filter-titulos-pagos').on('click', function(){
		titulosPagos();
	});
	$(document).find('.btn-filter-titulos-renegociados').on('click', function(){
		titulosRenegociados();
	});
	$(document).find('.btn-filter-forma-pagamento').on('click', function(){
		titulosFormaPagamento();
	});
	setTimeout( function() {
		if($(document).find('.content-cliente-posicao-sintetica').is(":visible")){
			getDados($(document).find("#form_filter_modal").serialize());
		}
	}, 300);

	$(document).find('.content-cliente-posicao-sintetica').find(".btn-filter").on("click", function(){
		getDados($(document).find("#form_filter_modal").serialize());
	});
	$(document).find(".bt-modal-open").on("click",function(){
		showModalOpen($(this));
	});
	$(document).find('.data').mask('00/00/0000');
	$(document).find('.data').datepicker({
	    language: 'pt-BR',
	    format: 'dd/mm/yyyy',
	    zIndex: 100,
	    autoHide: true
	});
	$(document).find('.btn-adicionar-historico').on('click', function(){
		modalAdicionarHistorico();
	});
	$(document).find("#btn_filtro_renegociacao").on("click", function(){
        filterAjaxRenegociacao();
    });
});
function chamarTitulosPagos(){
	if($(document).find("#titulos-contador").html().length > 0){
		return false;
	}else{
		titulosPagos();
	}
}
function chamarTitulosRenegociados(){
	if($(document).find("#titulos-contador-renegociados").html().length > 0){
		return false;
	}else{
		titulosRenegociados();
	}
}
function chamarFormaPagamento(){
	if(contador_forma_pagamento > 0){
		return false;
	}else{
		contador_forma_pagamento ++;
		titulosFormaPagamento();
	}
}
function titulosPagos(){
	table_filters_titulos.clear().draw();
	$.ajax({
        url: '{{ route('cliente.posicao_sintetica.titulos_pagos') }}',
        type: 'post',
        dataType: 'json',
        data: {
			_token: '{{ csrf_token() }}',
			data_inicio_titulos_pagos: $(document).find("#data_inicio_titulos_pagos").val(),
			data_fim_titulos_pagos: $(document).find("#data_fim_titulos_pagos").val(),
			nome: $(document).find("#nome").val(),
			cpf_cnpj_unico: $(document).find("#cpf_cnpj_unico").val(),
			codigo: $(document).find("#codigo_modal_analise").val()
		},
        success: function(callback){
			table_filters_titulos.clear().draw();
			var data = callback.response;
			var fields_filter = [];
			for(var field in data.titulos_pagos){
				var temp_field = [
					data.titulos_pagos[field].estabelecimento,
					data.titulos_pagos[field].clientenome,
					data.titulos_pagos[field].titulo,
					data.titulos_pagos[field].data_emissao,
					data.titulos_pagos[field].data_vencimento,
					data.titulos_pagos[field].data_pagamento,
					data.titulos_pagos[field].atraso,
					data.titulos_pagos[field].valor_titulo,
					data.titulos_pagos[field].valor,
					data.titulos_pagos[field].juros,
					data.titulos_pagos[field].desconto,
					data.titulos_pagos[field].portador
				];
				
				fields_filter.push(temp_field);
			}
			if (data.grupoCliente == 1){
				table_filters_titulos.columns('.cliente').visible(false);
			}else{
				table_filters_titulos.columns('.cliente').visible(true);
			}
			table_filters_titulos.rows.add(fields_filter).draw().nodes();

			
			$(document).find("#titulos-contador").html(data.titulos);
			$(document).find("#titulos-valor").html(data.valor_total);
			$(document).find("#titulos-atraso").html(data.em_atraso);
			$(document).find("#titulos-media-atraso").html(data.media_atraso);
        },
        error: function(callback) {
			message('Atenção.','Ocorreu um erro, por favor tente mais tarte');
        }
    });
}
function titulosRenegociados(){
	table_filters_titulos_renegociados.clear().draw();
	$.ajax({
        url: '{{ route('cliente.posicao_sintetica.titulos_renegociados') }}',
        type: 'post',
        dataType: 'json',
        data: {
			_token: '{{ csrf_token() }}',
			data_inicio_renegociacao: $(document).find("#data_inicio_renegociacao").val(),
			data_fim_renegociacao: $(document).find("#data_fim_renegociacao").val(),
			nome: $(document).find("#nome").val(),
			cpf_cnpj_unico: $(document).find("#cpf_cnpj_unico").val(),
			codigo: $(document).find("#codigo_modal_analise").val()
		},
        success: function(callback){
			var data = callback.response;
			
			if(data.titulos_renegociados_array.length > 0){
				var fields_filter = [];
				for(var field in data.titulos_renegociados_array){

					var temp_field = [
						"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" +data.titulos_renegociados_array[field].estabelecimento+ "''>" + data.titulos_renegociados_array[field].estabelecimento + "</div></div>",
						"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" +data.titulos_renegociados_array[field].clientenome+ "''>" + data.titulos_renegociados_array[field].clientenome + "</div></div>",
						data.titulos_renegociados_array[field].titulo,
						data.titulos_renegociados_array[field].data_emissao,
						data.titulos_renegociados_array[field].data_vencimento,
						data.titulos_renegociados_array[field].data_pagamento,
						data.titulos_renegociados_array[field].valor_titulo,
					];
					
					fields_filter.push(temp_field);
				}
				if (data.grupoCliente == 1){
					table_filters_titulos_renegociados.columns('.cliente').visible(false);
				}else{
					table_filters_titulos_renegociados.columns('.cliente').visible(true);
				}
				table_filters_titulos_renegociados.rows.add(fields_filter).draw().nodes();
			}
			
			$(document).find("#titulos-contador-renegociados").html(data.titulos_renegociados);
			$(document).find("#titulos-valor-renegociados").html(data.valor_total_renegociados);
        },
        error: function(callback) {
			if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                $.each(data, function(index, el) {
                    $(document).find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                    $(document).find('input[name="'+index+'"]').eq(0).addClass('error');
                });
                $(document).find('input.error').eq(0).focus();
            }
        }
    });
}

function titulosFormaPagamento(){
	table_filters_forma_pagamento.clear().draw();
    $('label.error-message').remove();
	$.ajax({
        url: '{{ route('cliente.posicao_sintetica.titulos_forma_pagamento') }}',
        type: 'post',
        dataType: 'json',
        data: {
			_token: '{{ csrf_token() }}',
			data_inicio_forma_pagamento: $(document).find("#data_inicio_forma_pagamento").val(),
			data_fim_forma_pagamento: $(document).find("#data_fim_forma_pagamento").val(),
			nome: $(document).find("#nome").val(),
			cpf_cnpj_unico: $(document).find("#cpf_cnpj_unico").val(),
			codigo: $(document).find("#codigo_modal_analise").val()
		},
        success: function(callback){
			var data = callback.response;
			
			$(table_filters_forma_pagamento.column(0).footer()).html('');
			$(table_filters_forma_pagamento.column(1).footer()).html('');
			$(table_filters_forma_pagamento.column(2).footer()).html('');
			if(data.forma_pagamento != null){
				var fields_filter_forma_pagamento = [];
				for(var field in data.forma_pagamento){
					var temp_forma_pagamento = [
						data.forma_pagamento[field].forma_pagamento,
						data.forma_pagamento[field].titulo,
						data.forma_pagamento[field].valor,
					];
					fields_filter_forma_pagamento.push(temp_forma_pagamento);
				}
				$(table_filters_forma_pagamento.column(0).footer()).html('Total');
				$(table_filters_forma_pagamento.column(1).footer()).html(data.total_forma_pagamento.titulo);
				$(table_filters_forma_pagamento.column(2).footer()).html(data.total_forma_pagamento.valor);
				table_filters_forma_pagamento.rows.add(fields_filter_forma_pagamento).draw().nodes();
				
			}
        },
        error: function(callback) {
			if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                $.each(data, function(index, el) {
                    $(document).find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                    $(document).find('input[name="'+index+'"]').eq(0).addClass('error');
                });
                $(document).find('input.error').eq(0).focus();
            }
        }
    });
}
function showModalOpen($this){
	var $url = $($this).data("route");
	var $codigo = $($this).data("codigo");
	var $title = $($this).data("title-modal");
	var $unico = $($this).data("unico");

	$.ajax({
		url: $url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", codigo: $codigo, unico: $unico},
		success: function(body){
			createModal("cliente_analise_open_modal", $title, body, 'modal-lg');
		}
	});
}
function drawPopOver(){
	$('[data-toggle="popover"]').popover({
		container: 'body',
		html: true,
		show: true,
		template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
	});
	$('[data-toggle="popover"]').on('show.bs.popover', function () {
		var $this = $(this);
		$('.popover').not($this).each(function(){
			$("[aria-describedby='"+$(this).attr("id")+"']").popover('hide');
		});
		$("body").on("keyup", function(e){
			if(e.keyCode == 27){
				$($this).popover('hide');
			}
		});
	});
};
function showPedidos($method, $codigo, $unico){
	var title = "Pedidos a faturar do Cliente: " + $(document).find('#modal_nome').html();
	
	$.ajax({
		url: "{{ route('pedidos_orcamentos.pedidos_abertos') }}",
		data: {_token: '{{ csrf_token() }}', codigo: $codigo, method: $method, unico: $unico},
		method: 'POST',
		success: function(body){
			createModal("show_pedidos", title, body, 'modal-lg');
		}
	});
}
function getDados(data_form){

	clearTela();

	data_form.data_inicio = $(document).find("#data_inicio").val();
	data_form.data_fim = $(document).find("#data_fim").val();
	
	data_form.data_inicio_forma_pagamento = $(document).find("#data_inicio_forma_pagamento").val();
	data_form.data_fim_forma_pagamento = $(document).find("#data_fim_forma_pagamento").val();

	data_form.data_inicio_renegociacao = $(document).find("#data_inicio_renegociacao").val();
	data_form.data_fim_renegociacao = $(document).find("#data_fim_renegociacao").val();

	data_form._token = '{{  csrf_token() }}';

	$.ajax({
		url: "{{ route('cliente.posicao_sintetica.return') }}",
		dataType: 'json',
		data: data_form,
		method: 'POST',
		success: function(callback){
			if(callback.status === "success"){
				$.ajax({
					url: '{{ route('cliente.salvaClientePadrao') }}',
					type: 'POST',
					data: {
						_token: '{{ csrf_token() }}',
						codcad: $("#codigo").val()
					}
				});
				var data = callback.data;
				
				if(data.cnpj_array.length > 0){
					$(document).find('#cpf-cnpj-unico-div').removeClass('d-none');
					$(document).find('#cpf_cnpj_unico').append("<option value='' >Todos</option>");
					for (var option in data.cnpj_array){
						$(document).find('#cpf_cnpj_unico').append("<option value='" + data.cnpj_array[option].codcad + "' " + ((data.cnpj_array[option].selecionado == true) ? "selected" : "") + ">"+ data.cnpj_array[option].label +"</option>");
					}
				}
				else{
					$(document).find('#cpf-cnpj-unico-div').addClass('d-none');
				}
				$(document).find('.observacao_agrupado').html(data.messagem_agrupada_cnpjs);
				$(document).find("#mensagem").html(data.mensagem_alerta);
				$(document).find("#vencimento-credito").html(data.vencimento_credito);
				$(document).find("#limite-credito").html(data.limite_credito+'&nbsp '+createLinkLogLimiteCreditos(data));
				$(document).find("#ultima-alteracao").html(data.ultima_atualizacao);
				$(document).find("#cliente-desde").html(data.cliente_desde);
				drawPopOver();
				$(document).find("#valor-maior-venda").html(data.vendas.maior.valor);
				$(document).find("#data-maior-venda").html(data.vendas.maior.data);
				$(document).find("#valor-ultima-venda").html(data.vendas.ultima.valor);
				$(document).find("#data-ultima-venda").html(data.vendas.ultima.data);

				$(document).find("#dias-maior-atraso").html(data.atraso.maior.quantidade);
				$(document).find("#data-maior-atraso").html(data.atraso.maior.data);
				$(document).find("#dias-ultimo-atraso").html(data.atraso.ultima.quantidade);
				$(document).find("#data-ultimo-atraso").html(data.atraso.ultima.data);

				$(document).find("#total-total").html((data.total.total));
				$(document).find("#vencido-total").html((data.total.vencidas));
				$(document).find("#a_vencer-total").html((data.total.a_vencer));

				if(data.cliente_pre_pago === true || data.pedidos_pre_pagos.total.length > 0){
					$(document).find('#a_vencer-pedidos-pre-pagos').html(createLinkPedidosPrePagos(data.pedidos_pre_pagos.total));
					$(document).find('#total-pedidos-pre-pagos').html(createLinkPedidosPrePagos(data.pedidos_pre_pagos.total));
					$(document).find('#total-pedidos-pre-pagos').parent().removeClass('d-none');
				}
				else{
					$(document).find('#total-pedidos-pre-pagos').parent().addClass('d-none');
				}

				$(document).find("#total-notas_debito").html(createLinkNotasDebitoTotal(data.notas_debito.total));
				$(document).find("#vencido-notas_debito").html(createLinkNotasDebitoVencidos(data.notas_debito.vencidas));
				$(document).find("#a_vencer-notas_debito").html(createLinkNotasDebitoAvencer(data.notas_debito.a_vencer));

				$(document).find("#total-notas_credito").html(createLinkNotasCreditoTotal(data.notas_credito));

				$(document).find("#total-faturado").html(createLinkTitulosFaturadosTotal(data.titulos_faturados.total));
				$(document).find("#vencido-faturado").html(createLinkTitulosFaturadosVencidos(data.titulos_faturados.vencidas));
				$(document).find("#a_vencer-faturado").html(createLinkTitulosFaturadosAvencer(data.titulos_faturados.a_vencer));

				if(data.titulos_terceiros.total.length > 0 ){

					$(document).find("#row-terceiros").removeClass("d-none");

					$(document).find("#total-terceiros").html(createLinkTitulosTerceirosTotal(data.titulos_terceiros.total));
					$(document).find("#vencido-terceiros").html(createLinkTitulosTerceirosVencidos(data.titulos_terceiros.vencidas));
					$(document).find("#a_vencer-terceiros").html(createLinkTitulosTerceirosAvencer(data.titulos_terceiros.a_vencer));
				}
				else{
					$(document).find("#row-terceiros").addClass("d-none");
				}

				$(document).find("#valor-afaturar").html(data.valores_a_faturar);
				$(document).find("#total-vendas-um-ano").html(data.pago_ultimo_12_meses);

				$(document).find('#info_cliente').html(data.informacoes_cliente_link);

				
				$(document).find("#titulos-contador-renegociados").html(data.titulos_renegociados);
				$(document).find("#titulos-valor-renegociados").html(data.valor_total_renegociados);

				$(document).find('#ultima_consulta_serasa').html(data.consulta_serasa);
				$(document).find('#motivo_reavaliacao').html(data.motivo_reavaliacao);
				$(document).find('#status_blacklist').html(data.blacklist);

				var link_pedido_orcamento = "";
				var link_pedido_carteira = "";
				var link_pedido_total = "";
				if((data.pedidos.orcamentos).length){
					link_pedido_orcamento = "<a href=\"#\" onclick=\"showPedidos('orcamento', '" +  data.cliente.codigo + "', " + data.cliente.unico + ")\">"+data.pedidos.orcamentos+"</a>";
				}
				if((data.pedidos.carteira).length){
					link_pedido_carteira = "<a href=\"#\" onclick=\"showPedidos('carteira', '" +  data.cliente.codigo + "', " + data.cliente.unico + ")\">"+data.pedidos.carteira+"</a>";
				}
				if((data.pedidos.total).length){
					link_pedido_total = "<a href=\"#\" onclick=\"showPedidos('total', '" +  data.cliente.codigo + "', " + data.cliente.unico + ")\">"+data.pedidos.total+"</a>";
				}

				if(data.devolucoes.length > 0){

					var fields_filter = [];
					for(var field in data.devolucoes){

						var temp_field = [
							linkProcessoDevolucao(data.devolucoes[field].devolucao_numero, data.devolucoes[field].id),
							createLinkNotaDevolucao(data.devolucoes[field].nota_numero, data.devolucoes[field].nota_id),
							createLinkNotaCliente(data.devolucoes[field].nota_devolucao, data.devolucoes[field].nota_devolucao_arquivo),
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.devolucoes[field].cliente + "'>" + data.devolucoes[field].cliente + "</div></div>",
							data.devolucoes[field].parcial,
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.devolucoes[field].motivo + "'>" + data.devolucoes[field].motivo + "</div></div>",
							data.devolucoes[field].valor,
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.devolucoes[field].status + "'>" + data.devolucoes[field].status + "</div></div>",
							data.devolucoes[field].dias_fase,
							data.devolucoes[field].dias_aberto,
							data.devolucoes[field].origem,
							data.devolucoes[field].entrada,
						];
						
						fields_filter.push(temp_field);
					}
					if (data.grupoCliente == 1){
						table_filters_devolucoes.columns('.cliente').visible(false);
					}else{
						table_filters_devolucoes.columns('.cliente').visible(true);
					}
					table_filters_devolucoes.rows.add(fields_filter).draw().nodes();

				}

				$(document).find("#pedidos-orcamentos").html(link_pedido_orcamento);
				$(document).find("#pedidos-carteira").html(link_pedido_carteira);
				$(document).find("#pedidos-total").html(link_pedido_total);
				$(document).find(".bt-modal-open").off("click");
				$(document).find(".bt-modal-open").on("click",function(){
					showModalOpen($(this));
				});
				buscaHistorico();
			}
		}
	});
}
function createLinkTitulosFaturadosAvencer($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo_modal_analise").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Titulos Faturados - À vencer - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.titulos_faturados', ['coluna'=>'a_vencer']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function createLinkTitulosFaturadosVencidos($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo_modal_analise").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Titulos Faturados - Vencidos - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.titulos_faturados', ['coluna'=>'vencidos']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function createLinkTitulosFaturadosTotal($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo_modal_analise").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo="+$cliente_codigo+" data-unico="+$unico+" data-title-modal=\"Titulos Faturados - Total - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.titulos_faturados', ['coluna'=>'total']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}

function createLinkTitulosTerceirosAvencer($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo_modal_analise").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Titulos Faturados - À vencer - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.titulos_terceiros', ['coluna'=>'a_vencer']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}

function createLinkTitulosTerceirosVencidos($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo_modal_analise").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Titulos Faturados - Vencidos - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.titulos_terceiros', ['coluna'=>'vencidos']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}

function createLinkTitulosTerceirosTotal($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo_modal_analise").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Titulos Faturados - Total - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.titulos_terceiros', ['coluna'=>'total']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}

function createLinkNotasCreditoTotal($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo_modal_analise").val();			
			var $unico = false;
		}
		
		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Notas de Crédito - Total - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.notas_credito', ['coluna'=>'total']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}

function createLinkNotasDebitoAvencer($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo_modal_analise").val();			
			var $cliente_nome = $(document).find("#nome").val();
			var $unico = false;
		}

		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Notas de Débito - À vencer - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.notas_debito', ['coluna'=>'a_vencer']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function createLinkNotasDebitoVencidos($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo_modal_analise").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Notas de Débito - Vencidos - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.notas_debito', ['coluna'=>'vencidos']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function createLinkNotasDebitoTotal($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo_modal_analise").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Notas de Débito - Total - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.notas_debito', ['coluna'=>'total']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}

function createLinkLogLimiteCreditos($this){
	if($this.limite_credito_obj.length > 0){
	var $html = "<a href=\"#\" class=\"bt-view float-right\" data-content=\""+createBodyPopOver($this.limite_credito_obj)+"\" data-toggle=\"popover\" data-trigger='hover' data-title=\"Atualizações no Limite de Crédito\"></a>";
	}else{
		$html = '';
	}
	return $html;
}
function createBodyPopOver($this){
	var $return = "";
	$.each($this,function(index, el) {
		if(index > 0){
			$return += "<hr>";
		}
		$return += "<p> Usuário: "+el.user+"</p><br><p>Data de Atualização: "+el.data+"</p>";
		
	});
	return $return;
}
function clearTela(){
	contador_forma_pagamento = 0;
	table_filters_titulos.clear().draw();
	table_filters_devolucoes.clear().draw();
	table_filters_renegociacao_titulo.clear().draw();
	$('#cpf_cnpj_unico').find('option').remove();
	$('#cpf-cnpj-unico-div').addClass('d-none');
	$(".observacao_agrupado").html("");
	$("#mensagem").html("");
	$("#vencimento-credito").html("");
	$("#limite-credito").html("");
	$("#ultima-alteracao").html("");
	$("#cliente-desde").html("");
	$("#valor-maior-venda").html("");
	$("#data-maior-venda").html("");;
	$("#valor-ultima-venda").html("");
	$("#data-ultima-venda").html("");
	$("#dias-maior-atraso").html("");
	$("#data-maior-atraso").html("");
	$("#dias-ultimo-atraso").html("");
	$("#data-ultimo-atraso").html("");
	$("#total-total").html("");
	$("#vencido-total").html("");
	$("#a_vencer-total").html("");
	$("#total-cheches_receber").html("");
	$('#a_vencer-pedidos-pre-pagos').html("");
	$('#total-pedidos-pre-pagos').html("");
	$('#total-pedidos-pre-pagos').parent().addClass('d-none');
	$('#a_vencer-chques_pre').html("");
	$('#total-chques_pre').html("");
	$('#total-chques_pre').parent().addClass('d-none');
	$("#vencido-cheches_receber").html("");
	$("#a_vencer-cheches_receber").html("");
	$("#total-notas_debito").html("");
	$("#vencido-notas_debito").html("");
	$("#a_vencer-notas_debito").html("");
	$("#total-notas_credito").html("");
	$("#total-faturado").html("");
	$("#vencido-faturado").html("");
	$("#a_vencer-faturado").html("");
	$("#a_vencer-terceiros").html("");
	$("#vencido-terceiros").html("");
	$("#total-terceiros").html("");
	$("#row-terceiros").addClass('d-none');
	$("#valor-afaturar").html("");
	$("#total-vendas-um-ano").html("");
	$("#pedidos-orcamentos").html("");
	$("#pedidos-carteira").html("");
	$("#pedidos-total").html("");
	$("#info_cliente").html("");
	$('#ultima_consulta_serasa').html("");
	$('#motivo_reavaliacao').html("");
	$(document).find('#titulos-contador-renegociados').html("");
	$(document).find('#titulos-valor-renegociados').html("");
	$(document).find("#dados_devolucao").val('');
}

function modal_info_cliente($id){
	$.ajax({
		url: '{{ route('cliente.view')}}',
		type: 'POST',
		data: {
			_token: '{{ csrf_token() }}',
			codcad: $id
		},
	})
	.done(function(data) {
		$id = 'info-cliente-modal';
		$title = 'Informações do cliente';
		$body = data;
		$class = 'modal-lg';
		createModal($id, $title, $body, $class);
	});
	
}

function createLinkPedidosPrePagos($texto){
	if($texto != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo_modal_analise").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Títulos Pré Pagos - Total - " + $(document).find('#modal_nome').html() +" \" data-route=\"{{ route('titulos_prepago.modal.filtro') }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function modalAdicionarHistorico(){
	var $cliente_codigo = $(document).find("#codigo_modal_analise").val();
	var $cliente_nome = $(document).find("#nome").val();
	$title = 'Adicionar Historico Cliente: '+$cliente_nome;
    $.ajax({
        url: '{{ route('historico_financeiro.modal.adicionar') }}',
        type: 'post',
        data: {
			_token: '{{ csrf_token() }}',
			cliente: $cliente_codigo,
			cliente_nome: $cliente_nome
		},
        success: function(callback){
			createModal("historico_adicionar", $title, callback, 'modal-lg');
        },
        error: function(data) {
        }
    });
}

function buscaHistorico(){
	var $cliente_codigo = $(document).find("#codigo_modal_analise").val();
	table_historico.clear().draw();
    $.ajax({
        url: '{{ route('historico_financeiro.busca') }}',
        type: 'post',
        data: {
			_token: '{{ csrf_token() }}',
			cliente: $cliente_codigo
		},
        success: function(callback){
			if(callback.status === "success"){
				var dados = callback.response;
				if(dados.length > 0){
					var fields_filter = [];
					for(var field in dados){
						var temp_field = [
							dados[field].data_hora,
							dados[field].usuario,
							dados[field].contato,
							dados[field].retorno,
							dados[field].titulos
						];
						fields_filter.push(temp_field);
					}
					table_historico.rows.add(fields_filter).draw().nodes();
					$(document).find(".bt-historico-titulos").off("click");
					$(document).find(".bt-historico-titulos").on("click",function(){
						showModalTitulos($(this));
					});
				}
			}
        }
    });
}

function showModalTitulos(campo){
	var $cliente_nome = $(document).find("#nome").val();
	$title = 'Titulos Historico Cliente: '+$cliente_nome;
    $.ajax({
        url: '{{ route('historico_financeiro.modal.titulos') }}',
        type: 'post',
        data: {
			_token: '{{ csrf_token() }}',
			id: campo.data('id')
		},
        success: function(callback){
			createModal("historico_Titulos", $title, callback, 'modal-lg');
        }
    });
}

function linkProcessoDevolucao($numero, $id){

	var $html = "<a href=\"#\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"visualizar_devolucao('"+$id+"');\">"+$numero+"</a>";

	return $html;
}

function visualizar_devolucao($id){
	$.ajax({
		method: 'POST',
		data: {
			_token: '{{ csrf_token() }}',
			id: $id
		},
		url: '{{ route('devolucao_nota.modal.visualizar') }}',
		success: function(data){
			createModal('editar-devolucao-modal', 'Detalhes da requisição de devolução', data, 'modal-lg');
		},
		error: function callback(data){
			message('Atenção!', data.responseJSON.message)
		}
	});
}

function createLinkNotaDevolucao($numero, $nota){

	if($nota.length > 0){
		var html = "<a href=\"#\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Detalhes no Nasajon\" onclick=\"showModalNotaDevolucao('"+$nota+"');\">"+$numero+"</a>";
	}
	else{
		var html = $numero;
	}
	return html;
}

function showModalNotaDevolucao($id){
	var url = '{{ route('notas_nasajon.modal.exibir') }}';
	var modal_class = 'modal-lg';
	var title = 'Detalhes da nota';
	$.ajax({
		url: url,
		method: 'POST',
		data: {_token: "{{ csrf_token() }}", id_nota: $id},
		success: function(body){
			createModal('modal_message_edit', title, body, modal_class);
		}
	});
}

function createLinkNotaCliente($numero, $link){
	if($link.length > 0){
		var html = "<a href=\""+ $link +"\" target=\"_blank\">"+$numero+"</a>";
	}
	else{
		var html = $numero;
	}
	return html;
}

function modalHistoricoBlackList($this){
	var title = "Histórico Black List";
	var cpf_cnpj = $this.data("cpf_cnpj");
    $.ajax({
        url: '{{ route('cliente_black_list.modal.historico') }}',
        type: 'POST',
        data: {
			_token: '{{ csrf_token() }}',
			cpf_cnpj: cpf_cnpj,
        },
        success: function (body){
            createModal('historico_black_list_modal',  title, body, 'modal-lg');
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
        }
    }); 
}
function filterAjaxRenegociacao(){
    form = $(document).find("#form_filter_modal");
    data_form = form.serialize();
	table_filters_renegociacao_titulo.clear().draw();	
    $.ajax({
        url: '{{ route('renegociacao_titulo.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            
            for (var fields in data.response){
                temp_array = [
                    data.response[fields].data,
                    createBtView(data.response[fields]),
                    data.response[fields].valor_titulos,
					data.response[fields].juros_atualizado,
					data.response[fields].valor_atualizado,
                    data.response[fields].juros_por_mes,
                    data.response[fields].valor_renegociacao,
                    data.response[fields].parcelas,
					data.response[fields].periodo_dias,
					statusRenegociacaoTitulo(data.response[fields]),
                ];
                produtos.push(temp_array)
            }
            table_filters_renegociacao_titulo.rows.add(produtos).draw();            

        }
	});
	
	esconderPopoverTooltip();
}

function createBtView($value){
    html = "<a href=\"#\" class=\"btn-pedido\" data-toggle='tooltip' data-html='true' data-renegociacao_titulos_id=\""+$value.id+"\" data-cliente=\""+$value.cliente+"\" title='Visualizar' onclick=\"showDialogRenegociacaoTitulo($(this))\"></a>";

    return html;
}

function showDialogRenegociacaoTitulo($value){
    var title = "Detalhes da Renegociação de Títulos - "+$value.data("cliente");
    var id = $value.data("renegociacao_titulos_id");
    $.ajax({
        url: '{{ route('renegociacao_titulo.modal.renegociacao_titulo') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
			id: id,
			tipo: 'normal',
        },
        success: function (body){
            createModal('dialog_renegociacao',  title, body, 'modal-lg');
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
        }
    }); 
}

function statusRenegociacaoTitulo($value){
	if($value.motivo === ''){
		html = $value.status;
	}else{
		html = "<div>"+
			"<div data-toggle='tooltip' placement='right' data-html='true' title='' data-original-title='"+$value.motivo+"'>"+
				$value.status+
			"</div>"+
		"</div>";
	}

	return html;
}

function dadosDevolucao(){
	if($(document).find("#dados_devolucao").val() != ''){
		return false;
	}
	table_filters_devolucoes.clear().draw();

	var data_form = $(document).find("#form_filter").serialize();
	
	data_form.data_inicio = $("#data_inicio").val();
	data_form.data_fim = $("#data_fim").val();

	data_form.data_inicio_forma_pagamento = $("#data_inicio_forma_pagamento").val();
	data_form.data_fim_forma_pagamento = $("#data_fim_forma_pagamento").val();

	data_form.data_inicio_renegociacao = $("#data_inicio_renegociacao").val();
	data_form.data_fim_renegociacao = $("#data_fim_renegociacao").val();

	data_form.codigo = "{{$dados['cliente']['codigo']}}";

	$.ajax({
		url: "{{ route('cliente.posicao_sintetica.devolucao_dados') }}",
		dataType: 'json',
		data: data_form,
		method: 'POST',
		success: function(callback){
			if(callback.status === "success"){
				var data = callback.data;
				
				if(data.devolucoes.length > 0){

					var fields_filter = [];
                    for(var field in data.devolucoes){

						var temp_field = [
							linkProcessoDevolucao(data.devolucoes[field].devolucao_numero, data.devolucoes[field].id),
							createLinkNotaDevolucao(data.devolucoes[field].nota_numero, data.devolucoes[field].nota_id),
							createLinkNotaCliente(data.devolucoes[field].nota_devolucao, data.devolucoes[field].nota_devolucao_arquivo),
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.devolucoes[field].cliente + "'>" + data.devolucoes[field].cliente + "</div></div>",
							data.devolucoes[field].parcial,
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.devolucoes[field].motivo + "'>" + data.devolucoes[field].motivo + "</div></div>",
							data.devolucoes[field].valor,
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.devolucoes[field].status + "'>" + data.devolucoes[field].status + "</div></div>",
							data.devolucoes[field].dias_fase,
							data.devolucoes[field].dias_aberto,
							data.devolucoes[field].origem,
							data.devolucoes[field].entrada,
						];
						
						fields_filter.push(temp_field);
					}
					if (data.grupoCliente == 1){
						table_filters_devolucoes.columns('.cliente').visible(false);
					}else{
						table_filters_devolucoes.columns('.cliente').visible(true);
					}
					table_filters_devolucoes.rows.add(fields_filter).draw().nodes();
				}

				$(document).find("#dados_devolucao").val('ok');
			}
		}
	});
}
</script>
