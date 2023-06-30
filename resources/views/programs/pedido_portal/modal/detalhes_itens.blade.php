@extends('layouts.page-dialog')
@section('content')
<div class="content-dialog-table">
	<div class="content-table">
		<table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-pedidos-itens">
			<thead>
				<tr>
					<th>Código</th>
					<th>Descrição</th>
					<th class="tb_number">Quantidade</th>
					<th class="tb_number">Peso total</th>
					<th class="tb_number">Preço unitário</th>
					<th class="tb_number">Valor total</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($itens_pedido as $item)
				<tr>
					<td>{{ $item['codigo'] }}</td>
					<td>{{ $item['descricao'] }}</td>
					<td>{{ $item['quantidade'] }}</td>
					<td>{{ $item['peso_total'] }}</td>
					<td>{{ $item['preco_unitario'] }}</td>
					<td>{{ $item['valor_total'] }}</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
<script>
    table_filters_produtos_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": 15,
        "processing": true,
        "orderMulti": false,
        "scrollCollapse": true,
        "scrollY": "35vh",
        "drawCallback": function(settings) {
            $(document).find('.estoque, .preco').popover({
                container: 'body',
                html: true,
                show: true,
                trigger: 'manual'
            });
        },
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
            {
                "class": "tb_number", 
                "targets": "tb_number"
            },
        ],
        "order": [[ 2, 'asc' ]]
    };
	table_filters_pedido_itens = $(document).find("#table-filters-pedidos-itens").DataTable(table_filters_produtos_options);
	$(document).ready(function(){
		setTimeout(function() {
	    	table_filters_pedido_itens.draw();
		}, 500);
	});
</script>
@endsection