@extends('layouts.page-dialog')
@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped" id="table-filters-notas-lista">
            <thead>
                <tr>
                    <th>Estabelecimento</th>
                    <th>Nota</th>
                    <th>Entrega</th>
                    <th>Operação</th>
                    <th>Natureza</th>
                    <th>Fornecedor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($retorno as $value)
                <tr>
                    <td> {{ $value['estabelecimento'] }} </td>
                    <td> <a href="#" data-route="{{ route('notas_entradas_nasajon.nota') }}" data-id="{{ $value['identificador_documento'] }}" data-codido_produto="{{ $value['codido_produto'] }}" class="bt-view-show" data-trigger='hover' title="Nota de Entrada" data-title='Nota de Entrada'> {{ $value['numero'] }} </a></td>
                    <td> {{ $value['entrega'] }} </td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $value['operacao'] }}"> {{ $value['operacao'] }} </div></div> </td>
					<td> {{ $value['natureza'] }} </td>
					<td> {{ $value['fornecedor'] }} </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
$(document).ready( function () {	
	table_filters_itens = $("#table-filters-notas-lista").DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"autoWidth": false,
		"orderMulti": false,
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
				"targets": [2],
				"className": 'date_format'
			},
			{
				"targets": [4],
				"width": '5%'
			},
			{
				"targets": [3],
				"width": '25%'
			},
			{
				"targets": [0],
				"width": '15%'
			},
		]
	});

	table_filters_itens.on('draw', function () {
		$(document).find(".bt-view-show").off("click");
		$(document).find(".bt-view-show").on("click", function(e){
			event.stopPropagation();
			showNota($(this));
		});
	});  
	table_filters_itens.draw();
});
	function showNota($this){
		var url = $($this).data("route");
		var title = $($this).data('title');
		var id = $($this).data('id');
		var codido_produto = $($this).data('codido_produto');

		xhr = $.ajax({
			url: url,
			data: {_token: "{{ csrf_token() }}", id: id, codido_produto : codido_produto},
			method: 'POST',
			success: function(body){
				if(body.status === 'error'){
                    message('Erro',body.message,'');
                }else{
                	createModal("modal_nota_entrada", title, body, 'modal-lg');
                }
			}
		});
	}
</script>
@endsection