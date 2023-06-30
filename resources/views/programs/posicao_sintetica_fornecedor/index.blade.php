@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-sm-2 d-none">
            <input type="hidden" class="input-search-bt" name="codigo" id="codigo" value="" placeholder="Código de Cadastro" maxlength="250" />
        </div>
        <div class="col-sm-3">
			<div class="input-group">
                {{ Form::text('fornecedor', '', ['id' => 'fornecedor', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Fornecedor', 'maxlength' => '200']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route('fornecedor.busca.index') }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
            </div>
		</div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
		<input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
		<div id='info_cliente' class='float-right'></div>
    </div>
</form>
@endsection
@section('content')
<div class="content-cliente-posicao-sintetica">
	<div>
		<ul class="nav nav-tabs">
			<li class="nav-item">
				<a class="nav-link active" id='posicao-sintetica-header-tab' data-toggle="tab" href="#posicao_sintetica_header" role="tab" aria-controls="posicao_sintetica_header" aria-selected="true">Posição sintética</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="posicao-sintetica-duplicatas-tab" data-toggle="tab" href="#posicao_sintetica_titulos" role="tab" aria-controls="posicao_sintetica_titulos" aria-selected="false">Títulos</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="posicao-sintetica-renegociados-tab" data-toggle="tab" href="#posicao_sintetica_pedidos" role="tab" aria-controls="posicao_sintetica_pedidos" aria-selected="false">Pedidos</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="posicao-sintetica-renegociados-tab" data-toggle="tab" href="#titulos_a_vencer" role="tab" aria-controls="titulos_a_vencer" aria-selected="false">Títulos a Vencer</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="posicao-sintetica-renegociados-tab" data-toggle="tab" href="#posicao_sintetica_score" role="tab" aria-controls="posicao_sintetica_score" aria-selected="false">Score</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="posicao-sintetica-devolucao-tab" data-toggle="tab" href="#posicao_sintetica_devolucoes" role="tab" aria-controls="posicao_sintetica_devolucoes" aria-selected="false">Devoluções</a>
			</li>

			
		</ul>
	</div>
	<div class="tab-content" id="PedidoHeaderContainer">
		<div class="tab-pane show active" id="posicao_sintetica_header" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-table_posicao">
				<div class="row-header-table_posicao">
					<div class="cell-table_posicao">Em Aberto</div>
					<div class="cell-table_posicao">A Vencer</div>
					<div class="cell-table_posicao">Vencidos</div>
					<div class="cell-table_posicao">Total</div>
				</div>
				<div>
					<div class="row-table_posicao">
						<div class="cell-table_posicao">Títulos</div>
						<div class="cell-table_posicao" id="a_vencer-faturado"></div>
						<div class="cell-table_posicao" id="vencido-faturado"></div>
						<div class="cell-table_posicao" id="total-faturado"></div>
					</div>
					<div class="row-table_posicao">
						<div class="cell-table_posicao">Valor</div>
						<div class="cell-table_posicao" id="a_vencer-faturado-valor"></div>
						<div class="cell-table_posicao" id="vencido-faturado-valor"></div>
						<div class="cell-table_posicao" id="total-faturado-valor"></div>
					</div>
					<div class="row-table_posicao">
						<div class="cell-table_posicao">Pedidos de Compra</div>
						<div class="cell-table_posicao" id="a_vencer-pedidos"></div>
						<div class="cell-table_posicao" id="vencido-pedidos"></div>
						<div class="cell-table_posicao" id="total-pedidos"></div>
					</div>
				</div>
				<div class="row-table_posicao">
					<div class="cell-table_posicao">Valor</div>
					<div class="cell-table_posicao" id="a_vencer-pedidos-valor"></div>
					<div class="cell-table_posicao" id="vencido-pedidos-valor"></div>
					<div class="cell-table_posicao" id="total-pedidos-valor"></div>
				</div>
				<div class="line_limitcred_datedesde">
					<div class="cliente-desde">
						<div class="title-data">Fornecedor desde:</div>
						<div class="value-data" id="cliente-desde"></div>
					</div>
					<div class="ultima-alteracao">
						<div class="title-ultima-alteracao">Última Alteração:</div>
						<div class="valor-ultima-alteracao" id="ultima-alteracao"></div>
					</div>
				</div>
			</div>
			<div class="content-lateral-table">
				<div class="content-atrasos">
					<div class="title-atraso">Atrasos Na Entrega</div>
					<div class="header-content-atraso-vendas">
						<div>Data</div>
						<div>Dias</div>
					</div>
					<div class="content-ultimo-atraso">
						<label>Último</label>
						<div class="data-atraso" id="data-ultimo-atraso"></div>
						<div class="dias-atraso" id="dias-ultimo-atraso"></div>
					</div>
					<div class="content-maior-atraso">
						<label>Maior</label>
						<div class="data-atraso" id="data-maior-atraso"></div>
						<div class="dias-atraso" id="dias-maior-atraso"></div>
					</div>
				</div>
				<div class="content-vendas">
					<div class="title-vendas">Compras</div>
					<div class="header-content-atraso-vendas">
						<div>Data</div>
						<div>Valor</div>
					</div>
					<div class="content-ultimo-vendas">
						<label>Último</label>
						<div class="data-vendas" id="data-ultima-compra"></div>
						<div class="valor-vendas" id="valor-ultima-compra"></div>
					</div>
					<div class="content-maior-vendas">
						<label>Maior</label>
						<div class="data-vendas" id="data-maior-compra"></div>
						<div class="valor-vendas" id="valor-maior-compra"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="tab-pane show" id="posicao_sintetica_titulos" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="row mb-4">
					<div class="col-sm-3">
						{{ Form::label('data_inicio_titulos', 'Inicio do período (Vencimento)') }}
						{{ Form::text('data_inicio_titulos', date("d/m/Y", strtotime("-1 year")), ['id' => 'data_inicio_titulos', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-4">
						{{ Form::label('data_fim_titulos', 'Fim do período (Vencimento)') }}
						{{ Form::text('data_fim_titulos', date("d/m/Y"), ['id' => 'data_fim_titulos', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-1">
						<br>
						{{ Form::button('Filtrar', ['class' => 'btn btn-primary btn-filter-titulos']) }}
					</div>
				</div>
				<table class="table table-striped table-filter" id="table-filters-titulos">
			        <thead>
			            <tr>
							<th class="tb_width">Estabelecimento</th>
							<th>Titulo</th>
							<th class="tb_number">Parcela</th>
							<th class="tb_date">Data de Emissão</th>
							<th class="tb_date">Data de Vencimento</th>
							<th class="tb_number">Valor Original</th>
							<th class="tb_number">Valor (Saldo)</th>
							<th class="tb_number">Juros</th>
							<th class="tb_number">Juros Diários</th>
							<th class="tb_date">Início Juros</th>
							<th class="tb_number">Desconto</th>
							<th>Boleto</th>
							<th>Posição de Cobrança</th>
							<th>Banco</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="5" class='text-right'>Totais:</td>
							<td class='tb_number' id="total_valor"></td>
							<td class='tb_number' id="total_saldo"></td>
							<td class='tb_number' id="total_juros"></td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
		<div class="tab-pane show" id="posicao_sintetica_pedidos" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="row mb-4">
					<div class="col-sm-3">
						{{ Form::label('data_inicio_pedidos', 'Inicio do período (Emissão)') }}
						{{ Form::text('data_inicio_pedidos', date("d/m/Y", strtotime("-1 month")), ['id' => 'data_inicio_pedidos', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-3">
						{{ Form::label('data_fim_pedidos', 'Fim do período (Emissão)') }}
		                {{ Form::text('data_fim_pedidos', date("d/m/Y"), ['id' => 'data_fim_pedidos', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-3">
						{{ Form::label('situacao', 'Situação') }}
						{{ Form::select('situacao', $situacao, '', ['id' => 'situacao', 'class' => 'form-control', 'placeholder' => 'Todos','form' => 'form_filter'])}}
					</div>
					<div class="col-sm-1">
						<br>
		                {{ Form::button('Filtrar', ['class' => 'btn btn-primary btn-filter-pedidos']) }}
					</div>
				</div>
				<table class="table table-striped table-filter" id="table-filters-pedidos">
			        <thead>
						<tr>
							<th rowspan="2" class="tb_number">Pedido</th>
							<th rowspan="2" class="tb_date">Emissão</th>
							<th rowspan="2" class="tb_date">Previsão de Entrega</th>
							<th rowspan="2" class="tb_number">Valor Compra</th>
							<th colspan="3" >Quantidade</th>
						</tr>
						<tr>
							<th class="tb_number">Comprada</th>
							<th class="tb_number">Recebida</th>
							<th class="tb_number">Saldo</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="3" class='text-right'>Totais:</td>
							<td class='tb_number' id="total_valor_compra"></td>
							<td class='tb_number' id="total_comprada"></td>
							<td class='tb_number' id="total_recebida"></td>
							<td class='tb_number' id="total_saldo"></td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
		<div class="tab-pane show" id="posicao_sintetica_score" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<table class="table table-striped table-not-edit table-not-view" id="table_analise_score_fornecedor">
					<thead>
						<tr>
							<th class="tb_date">Data</th>
							<th>Estado</th>
							<th>Regime de Tributação</th>
							<th>Formulario</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
		<div class="tab-pane show" id="titulos_a_vencer" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<table class="table table-striped table-not-edit table-not-view" id="table_titulis_a_vencer">
					<thead>
						<tr>
							<th>Período</th>
							<th class="tb_number">Valor Original</th>
							<th class="tb_number">Valor (Saldo)</th>
							<th class="tb_number">Multa</th>
							<th class="tb_number">Juros Diários</th>
							<th class="tb_number">Desconto</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
					<tfoot>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
					</tfoot>
				</table>
			</div>
		</div>
		<div class="tab-pane" id="posicao_sintetica_devolucoes" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<table class="table table-striped table-not-edit" id="table-filters-devolucoes">
					<thead>
						<tr>
							<th class="text_estabelecimento">Estabelecimento</th>
							<th class="text_string">Fornecedor</th>
							<th class="text_number">Nº da nota</th>
							<th>Tipo de Operação</th>
							<th class="text_date">Data de Emissão</th>
							<th class="text_date">Data de saída</th>
							<th class="text_number">Valor</th>
												
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>

	</div>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {
    $(document).find("#fornecedor").val('');
    $(document).find("#fornecedor").autocomplete(optionsAutoCompleteFornecedor());
    $(document).find("#bt-search-fornecedor-busca").on("click", function(){
        showModalFornecedor($(this).data("route"), "Lista de Fornecedores");
    });

	$(document).find(".btn-filter").on("click", function(){
		getDados($(document).find("#form_filter").serialize());
	});

	$(document).find(".btn-filter-titulos").on("click", function(){
		filtroFornecedor($(document).find("#form_filter").serialize());
	});
	
	$(document).find(".btn-filter-pedidos").on("click", function(){
		filtroPedidos($(document).find("#form_filter").serialize());
	});

	$(document).find('.data').mask('00/00/0000');
	$(document).find('.data').datepicker({
	    language: 'pt-BR',
	    format: 'dd/mm/yyyy',
	    zIndex: 100,
	    autoHide: true
	});
	
	table_filters_titulos_a_vencer = $('#table_titulis_a_vencer').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 20,
        "autoWidth": false,
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
            { "class": "tb_number", targets: "tb_number" },
            { "class": "tb_date", targets: "tb_date" },
			{"width" : "30px", targets: "tb_width"}
        ]
    }).on('draw', function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();

		$(document).find(".bt-view-titulos-a-vencer").off("click");
		$(document).find(".bt-view-titulos-a-vencer").on("click", function(event){
			event.stopPropagation();
			showTitulosAvencer($(this));
		});
	});

	table_filters_titulo = $('#table-filters-titulos').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 20,
        "autoWidth": false,
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
            { "class": "tb_number", targets: "tb_number" },
            { "class": "tb_date", targets: "tb_date" },
			{"width" : "30px", targets: "tb_width"}
        ]
    }).on('draw', function(){
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
    });

	table_filters_pedidos = $('#table-filters-pedidos').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 20,
        "autoWidth": false,
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
            { "class": "tb_number", targets: "tb_number" },
            { "class": "tb_date", targets: "tb_date" },
			{"width" : "30px", targets: "tb_width"}
        ]
    }).on('draw', function(){
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
    });

	table_filters_score_fornecedor = $('#table_analise_score_fornecedor').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 20,
        "autoWidth": false,
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
            { "class": "tb_number", targets: "tb_number" },
            { "class": "tb_date", targets: "tb_date" },
			{"width" : "30px", targets: "tb_width"}
        ]
    }).on('draw', function(){
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
    });

	table_filters_devolucoes = $('#table-filters-devolucoes').DataTable({
			"searching": false,
			"lengthChange": false,
			"info": false,
			"autoWidth": false,
			"pageLength": 15,
			"orderMulti": false,
			"dom": 'Bfrtip',
			"buttons": [
				{
					extend: 'excelHtml5',
					text: ' ',
					title: '',
					footer: true,
					exportOptions: {
						columns: ':visible',
						format: {
							body: function(data, row, column, node) {
								data = $('<p>' + data + '</p>').text();
								if(column === 6){
									if(data != ''){
										numero = data.replace('.','').replace(',','');
										inteiro = Math.floor(numero.length - 2);
										decimal = Math.floor(numero.length);
										data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
									}else{
										data = '';
									}
								}
								return data;
							}
						}
					},
				},
			],
			"drawCallback": function(settings) {
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
	
				$(document).find("a.thumb").fancybox(
					{
						onComplete: function(){
							$('#fancybox-content')
								.on('mouseover', function(){
									$(this).children('#fancybox-img').css({'transform': 'scale(1.5)'});
								})
								.on('mouseout', function(){
									$(this).children('#fancybox-img').css({'transform': 'scale(1)'});
								})
								.on('mousemove', function(e){
									$(this).children('#fancybox-img').css({'transform-origin': ((e.pageX - $(this).offset().left) / $(this).width()) * 100 + '% ' + ((e.pageY - $(this).offset().top) / $(this).height()) * 100 +'%'});
							});
						}
					}
				);
	
			},
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
				}        },
			"columnDefs": [
				{
					"targets": "text_number",
					"className": 'number_format'
				},
				{
					"targets": "text_date",
					"className": 'date_format'
				},
				{
					"targets": "text_estabelecimento",
					"width": '15%'
				},
				{
					"targets": "text_string",
					"width": '30%'
				},
				{
					"targets": ["text_date", "td_acao"],
					"width": '5%'
				}
			],
			"order": [[ 3, 'desc' ]]
		});




});

function createLinkTitulos($this){
		var $html = "<a href=\"#\" data-route=\"{{ route('titulos_apagar.modal.titulos_abertura') }}\" data-filter=\""+$this.filter+"\" data-abertura='' data-fornecedor='true' data-abertura-geral='false' data-total='true' data-title='TITULOS EM ABERTO - FORNECEDORES' class='bt-fornecedor-titulos'>"+$this.quantidade+"</a>"
		return $html;
}

function createLinkTitulosValor($this){
	var $html = "<a href=\"#\" data-route=\"{{ route('titulos_apagar.modal.titulos_abertura') }}\" data-filter=\""+$this.filter+"\" data-abertura='' data-fornecedor='true' data-abertura-geral='false' data-total='true' data-title='TITULOS EM ABERTO - FORNECEDORES' class='bt-fornecedor-titulos'>"+$this.valor+"</a>"
	return $html;
}

function createLinkPedidos($this){
	var $html = "<a href=\"#\" class=\"bt-modal-open bt-fornecedor-pedidos\" data-title='Pedidos de Compras em Aberto' data-filter=\""+$this.filter+"\" data-route=\"{{ route('posicao_sintetica_fornecedor.modal.pedidos') }}\">"+$this.quantidade+"</a>";
	return $html;
}

function createLinkPedidosValor($this){
	var $html = "<a href=\"#\" class=\"bt-modal-open bt-fornecedor-pedidos\" data-title='Pedidos de Compras em Aberto' data-filter=\""+$this.filter+"\" data-route=\"{{ route('posicao_sintetica_fornecedor.modal.pedidos') }}\">"+$this.valor+"</a>";
	return $html;
}

function createLinkPedidoAtrasos($this, $form){
	var html = "";
	if($this.id_nota !== ""){
		html = "<a href='#' onclick=\"showPedidoDetalhes('" 
		+ $this.id_nota + "', '"
		+ "', '"
		+ "" 
		+ "')\">" + $this.data + "</a>";
	}
	return html;
}

function createLinkPedidosFiltro($this, $form){
	var html = "";
	if($this.id_nota !== ""){
		html = "<a href='#' onclick=\"showPedidoDetalhes('" 
		+ $this.id_nota + "', '"
		+ "', '"
		+ "" 
		+ "')\">" + $this.numero_pedido + "</a>";
	}
	return html;
}

function createLinkTitutlosAvencer($this){
	html = "<a href='#' class='bt-view-titulos-a-vencer' data-title='Títulos em Aberto' data-filtro=\""+$this.filtro+"\">" + $this.prazo + "</a>";
	return html;
}

function clearTela(){
	$(document).find('#a_vencer-pedidos-valor').html('');
	$(document).find('#vencido-pedidos-valor').html('');
	$(document).find('#total-pedidos-valor').html('');
	$(document).find('#a_vencer-faturado').html('');
	$(document).find('#vencido-faturado').html('');
	$(document).find('#total-faturado').html('');
	$(document).find('#a_vencer-faturado-valor').html('');
	$(document).find('#vencido-faturado-valor').html('');
	$(document).find('#total-faturado-valor').html('');
	$(document).find('#a_vencer-pedidos').html('');
	$(document).find('#vencido-pedidos').html('');
	$(document).find('#total-pedidos').html('');
	$(document).find('#data-ultimo-atraso').html('');
	$(document).find('#dias-ultimo-atraso').html('');
	$(document).find('#data-maior-atraso').html('');
	$(document).find('#dias-maior-atraso').html('');
	$(document).find('#data-ultima-compra').html('');
	$(document).find('#valor-ultima-compra').html('');
	$(document).find('#data-maior-compra').html('');
	$(document).find('#valor-maior-compra').html('');
}

function getDados(data_form){
	clearTela();
    $('label.error-message').remove();
	table_filters_score_fornecedor.clear().draw();
	table_filters_titulos_a_vencer.clear().draw();

	$(table_filters_titulos_a_vencer.column(0).footer()).html('');
	$(table_filters_titulos_a_vencer.column(1).footer()).html('');
	$(table_filters_titulos_a_vencer.column(2).footer()).html('');
	$(table_filters_titulos_a_vencer.column(3).footer()).html('');
	$(table_filters_titulos_a_vencer.column(4).footer()).html('');
	$(table_filters_titulos_a_vencer.column(5).footer()).html('');

	$.ajax({
		url: "{{ route('posicao_sintetica_fornecedor.filtro') }}",
		dataType: 'json',
		data: data_form,
		method: 'POST',
		success: function(callback){

			if(callback.response.response){
				$("#a_vencer-faturado").html(createLinkTitulos(callback.response.response.titulos.a_vencer));
				$("#vencido-faturado").html(createLinkTitulos(callback.response.response.titulos.vencidos));
				$("#total-faturado").html(createLinkTitulos(callback.response.response.titulos.total));
				
				$("#a_vencer-faturado-valor").html(createLinkTitulosValor(callback.response.response.titulos.a_vencer));
				$("#vencido-faturado-valor").html(createLinkTitulosValor(callback.response.response.titulos.vencidos));
				$("#total-faturado-valor").html(createLinkTitulosValor(callback.response.response.titulos.total));

				$("#a_vencer-pedidos").html(createLinkPedidos(callback.response.response.pedidos.a_vencer));
				$("#vencido-pedidos").html(createLinkPedidos(callback.response.response.pedidos.vencidos));
				$("#total-pedidos").html(createLinkPedidos(callback.response.response.pedidos.total));

				$("#a_vencer-pedidos-valor").html(createLinkPedidosValor(callback.response.response.pedidos.a_vencer));
				$("#vencido-pedidos-valor").html(createLinkPedidosValor(callback.response.response.pedidos.vencidos));
				$("#total-pedidos-valor").html(createLinkPedidosValor(callback.response.response.pedidos.total));

				$("#data-ultimo-atraso").html(createLinkPedidoAtrasos(callback.response.response.atraso_entrega.ultimo));
				$("#dias-ultimo-atraso").html(callback.response.response.atraso_entrega.ultimo.dias);
				$("#data-maior-atraso").html(createLinkPedidoAtrasos(callback.response.response.atraso_entrega.maior));
				$("#dias-maior-atraso").html(callback.response.response.atraso_entrega.maior.dias);

				$("#data-ultima-compra").html(createLinkPedidoAtrasos(callback.response.response.compras.ultimo));
				$("#valor-ultima-compra").html(callback.response.response.compras.ultimo.valor);
				$("#data-maior-compra").html(createLinkPedidoAtrasos(callback.response.response.compras.maior));
				$("#valor-maior-compra").html(callback.response.response.compras.maior.valor);
				
				$(document).find(".bt-fornecedor-titulos").off("click");
				$(document).find(".bt-fornecedor-titulos").on("click", function(event){
					event.stopPropagation();
					showModalFornecedorTitulo($(this));
				});

				$(document).find(".bt-fornecedor-pedidos").off("click");
				$(document).find(".bt-fornecedor-pedidos").on("click", function(event){
					event.stopPropagation();
					showModalFornecedorPedido($(this));
				});
				
			}
		
			if(callback.response.score){
				score_array = [];

				for (var fields in callback.response.score){
					temp_array = [
						callback.response.score[fields].data,
						callback.response.score[fields].estado,
						callback.response.score[fields].regime_trinutário,
						"<a href=\"#\" onclick='showNotasFormulario(\""+ callback.response.score[fields].id_formulario +"\",\""+ callback.response.score[fields].fornecedor +"\")'> <i class=\"bt-view\"></i> </a>"
					];
					score_array.push(temp_array);
				}
			
				table_filters_score_fornecedor.rows.add(score_array).draw();
			}
			if(callback.response.titulos_a_vencer){
				titylos_a_vencer_Array = [];

				for (var fields in callback.response.titulos_a_vencer){
					temp_array_titulos_a_vencer = [
						createLinkTitutlosAvencer(callback.response.titulos_a_vencer[fields]),
						callback.response.titulos_a_vencer[fields].valor_original,
						callback.response.titulos_a_vencer[fields].valor,
						callback.response.titulos_a_vencer[fields].multa,
						callback.response.titulos_a_vencer[fields].juros_diarios,
						callback.response.titulos_a_vencer[fields].desconto,
					];
					titylos_a_vencer_Array.push(temp_array_titulos_a_vencer);
				}

				
                $(table_filters_titulos_a_vencer.column(0).footer()).html('Total');
                $(table_filters_titulos_a_vencer.column(1).footer()).html(callback.response.titulos_a_vencer_total.valor_original);
                $(table_filters_titulos_a_vencer.column(2).footer()).html(callback.response.titulos_a_vencer_total.valor);
                $(table_filters_titulos_a_vencer.column(3).footer()).html(callback.response.titulos_a_vencer_total.multa);
                $(table_filters_titulos_a_vencer.column(4).footer()).html(callback.response.titulos_a_vencer_total.juros_diarios);
                $(table_filters_titulos_a_vencer.column(5).footer()).html(callback.response.titulos_a_vencer_total.desconto);

				table_filters_titulos_a_vencer.rows.add(titylos_a_vencer_Array).draw();
			}
		
			if(callback.response.devolucoes.length >0){
		
				
				
					var result_array = [];
					table_filters_devolucoes.clear().draw();
					for(var field in callback.response.devolucoes){
						
						var temp_array = [
							callback.response.devolucoes[field].estabelecimento_nome,
							ajusteTamanhoTable(callback.response.devolucoes[field].cliente),
							createLinkNf(callback.response.devolucoes[field]),
							callback.response.devolucoes[field].tipo_operacao,
							callback.response.devolucoes[field].dtemis,
							callback.response.devolucoes[field].dtsaida + ' ' + createBtEntrega(callback.response.devolucoes[field]),
							callback.response.devolucoes[field].valtotdoc,
						];
						result_array.push(temp_array);
					}
					table_filters_devolucoes.rows.add(result_array).draw();
				//table_filters_devolucoes.rows.add(fields_filter).draw().nodes();
			}
		},
        error: function(callback){
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

function filtroFornecedor(data_form){
	data_form.data_inicio_titulos = $("#data_inicio_titulos").html();
	data_form.data_fim_titulos = $("#data_fim_titulos").html();
    $('label.error-message').remove();
	table_filters_titulo.clear().draw();	

	$(document).find('#total_valor').html('');
	$(document).find('#total_saldo').html('');
	$(document).find('#total_juros').html('');

	$.ajax({
		url: "{{ route('posicao_sintetica_fornecedor.filtro_titulos') }}",
		dataType: 'json',
		data: data_form,
		method: 'POST',
		success: function(callback){
			if(callback.response.response){
				produtos = [];

				for (var fields in callback.response.response){
					temp_array = [
						"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + callback.response.response[fields].estabelecimento + "''>" + callback.response.response[fields].estabelecimento + "</div></div>",
						callback.response.response[fields].documento,
						callback.response.response[fields].parcela,
						callback.response.response[fields].data_emissao,
						callback.response.response[fields].data_vencimento,
						callback.response.response[fields].valor_original,
						callback.response.response[fields].valor,
						callback.response.response[fields].multa,
						callback.response.response[fields].juros_diarios,
						callback.response.response[fields].data_juros,
						callback.response.response[fields].desconto,
						callback.response.response[fields].numero_boleto,
						callback.response.response[fields].POSICAO_CR,
						callback.response.response[fields].banco,
					];
					produtos.push(temp_array)
				}

				$(document).find('#total_valor').html(callback.response.totalizadores.valor);
				$(document).find('#total_saldo').html(callback.response.totalizadores.saldo);
				$(document).find('#total_juros').html(callback.response.totalizadores.juros);

				table_filters_titulo.rows.add(produtos).draw();   
			}
		},
        error: function(callback){
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

function filtroPedidos(data_form){
	data_form.data_inicio_pedidos = $("#data_inicio_pedidos").html();
	data_form.data_fim_pedidos = $("#data_fim_pedidos").html();
	data_form.situacao = $('#situacao').find(":selected").text();

    $('label.error-message').remove();
	table_filters_pedidos.clear().draw();	

	$(document).find('#total_valor_compra').html('');
	$(document).find('#total_comprada').html('');
	$(document).find('#total_recebida').html('');
	$(document).find('#total_saldo').html('');

	$.ajax({
		url: "{{ route('posicao_sintetica_fornecedor.filtro_pedidos') }}",
		dataType: 'json',
		data: data_form,
		method: 'POST',
		success: function(callback){
			if(callback.response.response){
				produtos = [];

				for (var fields in callback.response.response){
					temp_array = [
						createLinkPedidosFiltro(callback.response.response[fields]),
						callback.response.response[fields].data_compra,
						callback.response.response[fields].previsao_entrega,
						callback.response.response[fields].preco_compra_total,
						callback.response.response[fields].quantidade,
						callback.response.response[fields].quantidade_recebida,
						callback.response.response[fields].quantidade_restante,
					];
					produtos.push(temp_array)
				}

				$(document).find('#total_valor_compra').html(callback.response.totalizadores.total_preco_compra_total);
				$(document).find('#total_comprada').html(callback.response.totalizadores.total_quantidade);
				$(document).find('#total_recebida').html(callback.response.totalizadores.total_quantidade_recebida);
				$(document).find('#total_saldo').html(callback.response.totalizadores.total_quantidade_restante);

				table_filters_pedidos.rows.add(produtos).draw();   
			}
		},
        error: function(callback){
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

function showModalFornecedorTitulo($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var abertura = $($this).data('abertura');
    var total = $($this).data('total');
    var $fornecedor = $($this).data("fornecedor");
    var $busca = $($this).data("busca");
    var $aberturageral = $($this).data("abertura-geral");

    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter, abertura : abertura, total : total, busca : $busca, aberturageral : $aberturageral},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view_fornecedor_titulos", title, body, 'modal-lg');
        }
    });
}

function showModalFornecedorPedido($this){
    var url = $($this).data("route");
    var title = $($this).data('title');
    var filter = $($this).data('filter');

    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filter : filter},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view_fornecedor_pedidos", title, body, 'modal-lg');
        }
    });
}

function showModalFornecedor(url, title){
    xhr = $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("fornecedor_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#fornecedor_show").find("tbody").find("tr").off("click");
                    $(document).find("#fornecedor_show").find("tbody").find("tr").on("click", function(){
                        returnDados($(this));
                    });
                });
                $(document).find("#fornecedor_show").find(".bt-selected").on("click", function(){
                    returnDados($(this));
                });
            });
        }
    });
}

function returnDados($dados){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#fornecedor_show").modal("hide");
    $("#form_filter").find("#fornecedor").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
    $.ajax({
        url: '{{ route('cliente.salvaClientePadrao') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            codcad: $dados.find("td").eq(0).text()
        }
    });
}

function optionsAutoCompleteFornecedor(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('fornecedor.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Nenhum fornecedor encontrado');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#fornecedor").val(ui.item.label);
            $.ajax({
                url: '{{ route('cliente.salvaClientePadrao') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    codcad: ui.item.value
                }
            });
            return false;
        }
    };
}


function showPedidoDetalhes(id, unidade, data){
	$.ajax({
		url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
		type: 'POST',
		data: {
			_token: '{{csrf_token()}}',
			id: id,
			unidade: unidade,
			data: data
		},
		success: function(body){
			createModal("detalhe_pedido", "Detalhe do Pedido Compras", body, 'modal-lg');
		},
		error: function(callback){
			if((callback.responseJSON)){
				var data = callback.responseJSON.error;
				message = '';
				$.each(data, function(index, el) {
					message += el+'<br />';
				});
				console.log(message);
			}
		}

	});
}

function showTitulosAvencer($this){
    var filter = $($this).data("filtro");
	var title = $($this).data("title");

	$.ajax({
		url: '{{ route('posicao_sintetica_fornecedor.modal.titulos_a_vencer')}}',
		type: 'POST',
		data: {
			_token: '{{csrf_token()}}',
			filtro: filter,
		},
		success: function(body){
			createModal("model_aberturas_titulos", title, body, 'modal-lg');
		},
	});
}

function showNotasFormulario($id_nota,$fornecedor){
    $.ajax({
        url: '{{ route('score_fornecedores_consulta.modal.formulario_respondido')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id_nota: $id_nota,
        },
        success: function(body){
            createModal("formulario", "Fornulário Respondido - "+$fornecedor, body, 'modal-lg');
        }

    });
}

function linkProcessoDevolucao($numero, $id){

	var $html = "<a href=\"#\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"visualizar_devolucao('"+$id+"');\">"+$numero+"</a>";

	return $html;
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
function createLinkNotaCliente($numero, $link){
	if($link.length > 0){
		var html = "<a href=\""+ $link +"\" target=\"_blank\">"+$numero+"</a>";
	}
	else{
		var html = $numero;
	}
	return html;
}
function showNotasDetalhes(estabelecimento, nota_fiscal, data){
	$.ajax({
		url: '{{ route('historico_vendas.dialog')}}',
		type: 'POST',
		data: {
			_token: '{{csrf_token()}}',
			estabelecimento: estabelecimento,
			documento: nota_fiscal,
			link_pedido: true,
			data: data,
			origem: 'PROLOGOS'
		},
		success: function(body){
			createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
		}

	});
}

function createLinkNf($this){
	var html = "";
	if($this.numero_nota !== ""){

		if($this.origem == 'prologos'){
			html = "<a href='#' onclick=\"showNotasDetalhes('" + $this.estabelecimento + "', '" + $this.num_doc + "', '" + $this.dt_doc + "')\">" + $this.numnfe + "</a>";
		}

		else if($this.origem == 'nasajon'){
			html = "<a href='#' onclick=\"showNotasDetalhesNasajon('" + $this.id_nota + "')\">" + $this.numnfe + "</a>";
		}

		else{
			html = $this.numero_nota;
		}
	}

	return html;
}

function createBodyPopOver($this){
	var $return = "";
	$return = "<img src='"+$this+"' width='250' class='rounded mx-auto d-block' alt='Canhoto'>";
	return $return;
}

function showNotasDetalhesNasajon($id_nota){
	$.ajax({
		url: '{{ route('notas_nasajon.modal.exibir')}}',
		type: 'POST',
		data: {
			_token: '{{csrf_token()}}',
			id_nota: $id_nota,
			link_pedido: true,
			origem: 'NASAJON'
		},
		success: function(body){
			createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
			$(".troca-aba").on("click", function(e){
				e.preventDefault();
				$(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
			})

		}

	});
}

function createBtView($this){
	if($this.origem == 'nasajon'){
		var html = "<a href=\"#\" class=\"bt-view\" data-toggle='tooltip' data-html='true' title='Ver NF & Boleto' onclick=\"verDocumentos('"+$this.id_nota+"');\"></a>";
	}
	else{
		html = '';
	}

	return html;
}

function createBtEntrega($this){
	if($this.dtsaida.length > 0 && $this.nota_id_ocorrencia === true){
		var html = "<a href=\"#\" class=\"bt-entrega\" data-toggle='tooltip' data-html='true' title='Ocorrência de Entrega' onclick=\"verOcorrencia('"+$this.id_nota+"');\"></a>";
	}else{
		html = '';
	}
	return html;
}

function verDocumentos($id_nota){

	$.ajax({
		url: '{{ route('notas_nasajon.modal.documentos')}}',
		type: 'POST',
		data: {
			_token: '{{ csrf_token() }}',
			id_nota: $id_nota
		},
		success: function(body){
			createModal("nota_documentos", "Documentos da DANFE", body, '');
		}
	});

}


function verOcorrencia($id_nota){
	$.ajax({
		url: '{{ route('ocorrencia_entrega.modal.abertura')}}',
		type: 'POST',
		data: {
			_token: '{{ csrf_token() }}',
			id_nota: $id_nota
		},
		success: function(body){
			createModal("ocorrencias_de_entrega", "Ocorrência de Entrega", body, 'modal-lg');
		}
	});

}

function ajusteTamanhoTable($value){
	$html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

	return $html;
}
@endsection
