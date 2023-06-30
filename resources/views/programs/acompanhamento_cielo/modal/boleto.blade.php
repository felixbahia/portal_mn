@extends('layouts.page-dialog')
@section('content')

<div class="tab-content" id="pedidosTabContent">
	<div class="tab-pane show active" id="pedidos" role="tabpanel" aria-labelledby="pedidos-tab">
		<div class="content-dialog-table">
			<div class="content-table">
				<table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-boleto-sem-nota">
					<thead>
						<tr>
							<th>Estabelecimento</th>
							<th>Pedido</th>
							<th>Status</th>
							<th>Cliente</th>
							<th>Transportador</th>
							<th class="tb_number">Valor total</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($pedidos as $pedido)
						<tr>
							<td><div><div data-toggle="tooltip" data-html="true" title="" data-placement="left" data-original-title="{{ $pedido["estabelecimento"] }}">{{ $pedido['estabelecimento'] }}</div></div></td>
                            <td><a href="#" onclick="abrirPedido({{ $pedido['pedido'] }})">{{ $pedido['pedido'] }}</a></td>
							<td>{{ $pedido['status_pedido'] }}</td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="" data-placement="left" data-original-title="{{ $pedido["cliente"] }}">{{ $pedido['cliente'] }}</div></div></td>
							<td><div><div data-toggle="tooltip" data-html="true" title="" data-placement="left" data-original-title="{{ $pedido["transportadora"] }}">{{ $pedido['transportadora'] }}</div></div></td>
							<td>{{ $pedido['valor_total_produtos'] }}</td>
						</tr>
						@endforeach
					</tbody>
					<tfoot>
						<tr>
							<td colspan="4">Total:</td>
							<td class="number_format">{{ parserValor($total['valor']) }}</td>
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

	table_filters_acompanhamento_pedido = $(document).find("#table-filters-boleto-sem-nota").DataTable(table_filters_acompanhamento_pedido_options);

	$(document).ready(function(){
		setTimeout(function() {
	    	table_filters_acompanhamento_pedido.draw();
		}, 500);

		$(document).find("#pedidosTab").find("a[data-toggle='tab']").off("shown.bs.tab");
		$(document).find("#pedidosTab").find("a[data-toggle='tab']").on("shown.bs.tab", function (e) {
	    	table_filters_acompanhamento_pedido.draw();
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
            success: function(data){
                $id = "view-pedido";
                $title = "Detalhes do pedido"; 
                $body = data;
                $class = "modal-lg";
                createModal($id, $title, $body, $class);
            }
        });
    }
</script>
@endsection
