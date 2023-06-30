@extends('layouts.page-dialog')
@section('content')

<ul class="nav nav-tabs" id="pedidosTab" role="tablist">
	<li class="nav-item">
		<a class="nav-link active" id="pedidos-tab" data-toggle="tab" href="#pedidos" role="tab" aria-controls="pedidos-tab" aria-selected="true">Pedidos</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" id="bandeira-tab" data-toggle="tab" href="#bandeira" role="tab" aria-controls="bandeira-tab" aria-selected="false">Bandeira</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" id="parcelas-tab" data-toggle="tab" href="#parcelas" role="tab" aria-controls="parcelas-tab" aria-selected="false">Parcelas</a>
	</li>
</ul>
<div class="tab-content" id="pedidosTabContent">
	<div class="tab-pane show active" id="pedidos" role="tabpanel" aria-labelledby="pedidos-tab">
		<div class="content-dialog-table">
			<div class="content-table">
				<table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-acompanhamento_pedido">
					<thead>
						<tr>
							<th>Estabelecimento</th>
							<th>Presencial</th>
							<th>Pedido</th>
							<th>Nota</th>
							<th>Cliente</th>
							<th>numero do terminal</th>
							<th>codigo autorização</th>
							<th>NSU</th>
							<th>tipo pagamento</th>
							<th>parcela</th>
							<th>Numero Cartão</th>
							<th>bandeira Cartão</th>
							<th class="tb_number">Valor total</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($pedidos as $pedido)
						<tr>
							<td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $pedido["estabelecimento"] }}">{{ $pedido['estabelecimento'] }}</div></div></td>
							<td>{{ $pedido['presencial'] }}</td>
							<td><a href="#" onclick="abrirPedido({{ $pedido['pedido'] }})">{{ $pedido['pedido'] }}</a></td>
							<td><a href="#" onclick="abrirNota('{{ $pedido['nota_id'] }}', '{{ $pedido['nota_numero'] }}')">{{ $pedido['nota_numero'] }}</a></td>
							<td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $pedido["cliente"] }}">{{ $pedido['cliente'] }}</div></div></td>
							<td>{{ $pedido['terminal_numero'] }}</td>
							<td>{{ $pedido['codigo_autorizacao'] }}</td>
							<td>{{ $pedido['nsu'] }}</td>
							<td>{{ $pedido['tipo_pagamento'] }}</td>
							<td>{{ $pedido['parcela'] }}</td>
							<td>{{ $pedido['numero_cartao'] }}</td>
							<td>{{ $pedido['bandeira_cartao'] }}</td>
							<td>{{ $pedido['valor_pago'] }}</td>
						</tr>
						@endforeach
					</tbody>
					<tfoot>
						<tr>
							<td colspan="12">Total:</td>
							<td class="number_format">{{ parserValor($total['pedidos']) }}</td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="bandeira" role="tabpanel" aria-labelledby="bandeira-tab">
		<div class="content-dialog-table">
			<div class="content-table">
				<table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-acompanhamento_bandeiras">
					<thead>
						<tr>
							<th>Bandeira</th>
							<th class="tb_number">Valor total</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($bandeiras as $bandeira)
						<tr>
							<td>{{ $bandeira['bandeira'] }}</td>
							<td>{{ parserValor($bandeira['valor']) }}</td>
						</tr>
						@endforeach
					</tbody>
					<tfoot>
						<tr>
							<th>Total:</th>
							<td class="number_format">{{ parserValor($total['bandeira']) }}</td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="parcelas" role="tabpanel" aria-labelledby="parcelas-tab">
		<div class="content-dialog-table">
			<div class="content-table">
				<table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-acompanhamento_parcelas">
					<thead>
						<tr>
							<th>Quantidade parcelas</th>
							<th class="tb_number">Valor total</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($parcelas as $parcela)
						<tr>
							<td>{{ $parcela['parcela'] }}</td>
							<td>{{ parserValor($parcela['valor']) }}</td>
						</tr>
						@endforeach
					</tbody>
					<tfoot>
						<tr>
							<th>Total:</th>
							<td class="number_format">{{ parserValor($total['parcela']) }}</td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	</div>
</div>
<script>
    table_filters_acompanhamento_pedido_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "page": false,
		"paging": false,
        "pageLength": -1,
		"processing": false,
		"orderMulti": false,
		"scrollCollapse": true,
		"scrollY": "60vh",
		"autoWidth": false,
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
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Pedidos Pagos',
                footer: true,
                autoFilter: true,
                exportOptions: {
                    modifier: {
                        page: 'all'
                    },
                    columns: ':visible',
                    format: {
                        body: function ( data, row, column, node ) {
                            data = $('<p>' + data + '</p>').text();
                            if(column === 11){
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
                }
            },
        ],
        "columnDefs": [
            {
                "class": "tb_number", 
                "targets": "tb_number"
            },
        ],
        "order": [[ 2, 'asc' ]]
    };
    table_filters_acompanhamento_bandeira_parcela_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "page": false,
		"paging": false,
        "pageLength": -1,
		"processing": false,
		"orderMulti": false,
		"scrollCollapse": true,
		"scrollY": "60vh",
		"autoWidth": false,
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
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Pedidos Pagos',
                footer: true,
                autoFilter: true,
                exportOptions: {
                    modifier: {
                        page: 'all'
                    },
                    columns: ':visible',
                    format: {
                        body: function ( data, row, column, node ) {
                            data = $('<p>' + data + '</p>').text();
                            if(column === 2){
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
                }
            },
        ],
        "columnDefs": [
            {
                "class": "tb_number", 
                "targets": "tb_number"
            },
        ],
        "order": [[ 0, 'asc' ]]
    };
	table_filters_acompanhamento_pedido = $(document).find("#table-filters-acompanhamento_pedido").DataTable(table_filters_acompanhamento_pedido_options);
	table_filters_acompanhamento_bandeiras = $(document).find("#table-filters-acompanhamento_bandeiras").DataTable(table_filters_acompanhamento_bandeira_parcela_options);
	table_filters_acompanhamento_parcelas = $(document).find("#table-filters-acompanhamento_parcelas").DataTable(table_filters_acompanhamento_bandeira_parcela_options);

	$(document).ready(function(){
		setTimeout(function() {
	    	table_filters_acompanhamento_pedido.draw();
	    	table_filters_acompanhamento_bandeiras.draw();
	    	table_filters_acompanhamento_parcelas.draw();
		}, 500);

		$(document).find("#pedidosTab").find("a[data-toggle='tab']").off("shown.bs.tab");
		$(document).find("#pedidosTab").find("a[data-toggle='tab']").on("shown.bs.tab", function (e) {
	    	table_filters_acompanhamento_pedido.draw();
	    	table_filters_acompanhamento_bandeiras.draw();
	    	table_filters_acompanhamento_parcelas.draw();
		});
		
	});

    function abrirPedido($id){
        $.ajax({
            url: "{{ route("pedido_portal.detalhes") }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                pedido_id: $id
            },
            success: function(callback){
                $title = "Detalhes do pedido: "+$id; 
                $body = callback;
                $class = "modal-lg";
                createModal("view_pedido", $title, $body, $class);
            }
        });
    }
    function abrirNota($id, $nota){
        $.ajax({
            url: '{{ route('notas_nasajon.modal.exibir')}}',
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                id_nota: $id,
                link_pedido: true,
                origem: 'NASAJON'
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota: " + $nota, body, 'modal-lg');
                $(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                })

            }
        });
    }
</script>
@endsection
