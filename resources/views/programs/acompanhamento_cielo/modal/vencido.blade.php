@extends('layouts.page-dialog')
@section('content')

<div class="content-dialog-table">
	<div class="content-table">
		<table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-acompanhamento_pedido">
			<thead>
				<tr>
					<th>Estabelecimento</th>
					<th>Presencial</th>
					<th>Pedido</th>
					<th>Cliente</th>
					<th class="tb_number">Valor total</th>
					<th class="tb_action">Enviar E-mail<br>novamente</th>
					<th class="tb_action">Copiar Link</th>
					<th class="tb_action">Liberar Pedido</th>
					<th class="tb_action">Cancelar Pedido</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($pedidos as $pedido)
				<tr>
					<td>{{ $pedido['estabelecimento'] }}</td>
					<td>{{ $pedido['presencial'] }}</td>
					<td><a href="#" onclick="abrirPedido({{ $pedido['pedido'] }})">{{ $pedido['pedido'] }}</a></td>
					<td>{{ $pedido['cliente'] }}</td>
					<td>{{ $pedido['valor_total'] }}</td>
					<td>@if($pedido['presencial_bool'] == false)<a href="#" class="btn-send_email" data-toggle="tooltip" data-html="true" title="Enviar email para cliente" onclick="envairEmail({{ $pedido['pedido'] }})"></a>@endif</td>
					<td>{!! Form::text('link_pedido_'.$pedido['pedido'], $pedido['link_pedido'], ['id' => 'link_pedido_'.$pedido['pedido'], 'style'=>'width: 0px;padding: 0;border: 0;height: 0;float: left;']) !!}<a href="#" class="bt-duplicar" data-toggle="tooltip" data-html="true" title="Copiar Link" onclick="copiarLink('link_pedido_{{ $pedido['pedido'] }}')"></a></td>
					<td><a href="#" class="bt-duplicar" data-toggle="tooltip" data-html="true" title="liberar Pedido" onclick="liberarPedido({{ $pedido['pedido'] }})"></a></td>
					<td>@if($pedido['cancelar_pedido'] == true)<a href="#" class="bt-delete" data-toggle="tooltip" data-html="true" title="Cancelar Pedido" onclick="cancelar({{ $pedido['pedido'] }})"></a>@endif</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
<script>
    table_filters_acompanhamento_pedido_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
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
        "columnDefs": [
            {
                "class": "tb_number", 
                "targets": "tb_number"
            },
        ],
        "order": [[ 2, 'asc' ]]
    };
	table_filters_acompanhamento_pedido = $(document).find("#table-filters-acompanhamento_pedido").DataTable(table_filters_acompanhamento_pedido_options);
	$(document).ready(function(){
		setTimeout(function() {
	    	table_filters_acompanhamento_pedido.draw();
		}, 500);
	});

    function cancelar($id){
        $.ajax({
            url: "{{ route("acompanhamento_cielo.cacelar_pedido") }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                pedido: $id
            },
            success: function(callback){
                if(callback.status === 'success'){
                    message("Atenção", callback.message);
                }
            },
			error: function(data){
				var errors = data.responseJSON.message;
                message("Atenção", errors);
			}
        });
    }

    
    function envairEmail($id){
        $.ajax({
            url: "{{ route("acompanhamento_cielo.envair_email") }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                pedido: $id
            },
            success: function(callback){
                if(callback.status === 'success'){
                    message("Atenção", callback.message);
                }
            },
			error: function(data){
				var errors = data.responseJSON.message;
                message("Atenção", errors);
			}
        });
    }

    function liberarPedido($id){
        $.ajax({
            url: "{{ route("acompanhamento_cielo.liberar_pedido") }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                pedido: $id
            },
            success: function(callback){
                if(callback.status === 'success'){
                    message("Atenção", callback.message);
                }
            },
			error: function(data){
				var errors = data.responseJSON.message;
                message("Atenção", errors);
			}
        });
    }

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