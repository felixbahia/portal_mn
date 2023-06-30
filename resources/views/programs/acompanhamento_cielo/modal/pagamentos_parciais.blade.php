@extends('layouts.page-dialog')
@section('content')

<div class="tab-content" id="pedidosTabContent">
	<div class="tab-pane show active" id="pedidos" role="tabpanel" aria-labelledby="pedidos-tab">
		<div class="content-dialog-table">
			<div class="content-table">
				<table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-pagamentos-parciais">
					<thead>
						<tr>
							<th>Forma de Pagamento</th>
							<th>Parcelamento</th>
							<th>Código de Autorizacao</th>
							<th>Stone Id(TID)</th>
							<th>Bandeira</th>
							<th>Última Atualização</th>
							<th class="tb_number">Valor</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($transacoes as $transacao)
						<tr>
							<td><div><div data-toggle="tooltip" data-html="true" title="" data-placement="left" data-original-title="{{ $transacao["forma_pagamento"] }}">{{ $transacao['forma_pagamento'] }}</div></div></td>
                            <td>{{ $transacao['parcelamento'] }}</td>
							<td>{{ $transacao['codigo_autorizacao'] }}</td>
                            <td>{{ $transacao['tid'] }}</td>
							<td><div><div data-toggle="tooltip" data-html="true" title="" data-placement="left" data-original-title="{{ $transacao["bandeira"] }}">{{ $transacao['bandeira'] }}</div></div></td>
							<td><div><div data-toggle="tooltip" data-html="true" title="" data-placement="left" data-original-title="{{ $transacao["criado_por"] }}">{{ $transacao['criado_por'] }}</div></div></td>
							<td>{{ $transacao['valor'] }}</td>
						</tr>
						@endforeach
					</tbody>
					<tfoot>
						<tr>
							<td colspan="6">Total:</td>
							<td class="number_format">{{ $total }}</td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>
	</div>
</div>
<script>
    table_filters_pagamentos_parciais = {
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

	table_filters_acompanhamento_pedido = $(document).find("#table-filters-pagamentos-parciais").DataTable(table_filters_pagamentos_parciais);

	$(document).ready(function(){
		setTimeout(function() {
	    	table_filters_acompanhamento_pedido.draw();
		}, 500);

		$(document).find("#pedidosTab").find("a[data-toggle='tab']").off("shown.bs.tab");
		$(document).find("#pedidosTab").find("a[data-toggle='tab']").on("shown.bs.tab", function (e) {
	    	table_filters_acompanhamento_pedido.draw();
		});
		
	});
</script>
@endsection
