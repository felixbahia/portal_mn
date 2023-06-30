@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-filters-fornecedor-pedidos">
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
            @foreach($dados as $value)
            <tr>
                <td class="tb_number">
                    <a href='#' onclick="showPedicoComprasModalDetalhes('{{ $value['id_nota'] }}', '', '')">{{ $value["numero_pedido"] }}</a>
                </td>
                <td class="tb_date">{{ $value["data_compra"] }}</td>
                <td class="tb_date">{{ $value["previsao_entrega"] }}</td>
                <td class="tb_number">{{ $value["preco_compra_total"] }}</td>
                <td class="tb_number">{{ $value["quantidade"] }}</td>
                <td class="tb_number">{{ $value["quantidade_recebida"] }}</td>
                <td class="tb_number">{{ $value['quantidade_restante'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class='text-left'>Total:</td>
                <td class='tb_number'>{{ $total['total_preco_compra_total'] }}</td>
                <td class='tb_number'>{{ $total['total_quantidade'] }}</td>
                <td class='tb_number'>{{ $total['total_quantidade_recebida'] }}</td>
                <td class='tb_number'>{{ $total['total_quantidade_restante'] }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">

$(document).ready(function(){
    $(document).find('[data-toggle="popover"]').popover({
        container: 'body',
        html: true,
        show: true,
        template: '<div class="popover popover-notas" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
    });
})

table_filters_dialog_pedidos = $('#table-filters-fornecedor-pedidos').DataTable({
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
        { "class": "tb_date", targets: "tb_date" }
    ]
}).on('draw', function(){
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();
});
    
function showPedicoComprasModalDetalhes(id, unidade, data){
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
			createModal("1", "Detalhe do Pedido Compras", body, 'modal-lg');
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
</script>
@endSection