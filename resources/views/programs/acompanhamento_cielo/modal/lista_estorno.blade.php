@extends('layouts.page-dialog')
@section('content')

<div class="content-dialog-table">
	<div class="content-table">
		<table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-lista-estorno">
			<thead>
				<tr>
					<th>Responsável Estorno</th>
					<th class="tb_number">Valor Transação</th>
					<th class="tb_number">Valor Estorno</th>
					<th class="tb_date">Data</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($dados as $pedido)
				<tr>
					<td><div><div data-toggle="tooltip" data-html="true" title="" data-placement="left" data-original-title="{{ $pedido['usuario_sistema'] }}">{{ $pedido['usuario_sistema'] }}</div></div></td>
					<td>{{ $pedido['valor_transacao'] }}</td>
					<td>{{ $pedido['valor_estorno'] }}</td>
					<td>{{ $pedido['data'] }}</td>
					<td>
                        @if($pedido['estorno_parcial'] === true)
                            <i data-toggle="tooltip" onclick="estornarRestante('{{ $pedido['id_cielo'] }}')" data-html="true" title="" data-original-title="Realizar Estorno Total" data-placement="left" class="bt-estorno"></i>
                        @endif
                    </td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
<script>
    table_filters_lista_estorno = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "page": false,
        "pageLength": -1,
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
            {
                "class": "tb_date", 
                "targets": "tb_date"
            },
        ],
        "order": [[ 2, 'asc' ]]
    };
	table_filters_lista_estorno_draw = $(document).find("#table-filters-lista-estorno").DataTable(table_filters_lista_estorno);
	$(document).ready(function(){
		setTimeout(function() {
	    	table_filters_lista_estorno_draw.draw();
		}, 500);
	});

    function estornarRestante($id){
        $.ajax({
            url: "{{ route("acompanhamento_cielo.modal.estorno") }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                pedido_id: $id,
                total: true
            },
            success: function(data){
                $id = "view-estorno-restante";
                $title = "Estorno Pedido Portal"; 
                $body = data;
                $class = "modal-lg";
                createModal($id, $title, $body, $class);
                
            }
        });
    }
</script>
@endsection