@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-equipes-campanha-modal">
        <thead>
            <tr>
                <th>REPRESENTANTE/VENDEDOR</th>
                <th>RANK</th>
                <th class="tb_number">V. BRUTO</th>
                <th class="tb_number">DEVOLUÇÃO</th>
            </tr>
        </thead>
        <tbody>
        	@foreach($equipes as $value)
        	<tr>
                <td>{{ $value['representante'] }}</td>
                <td>{!! $value["rank"] !!}</td>
                <td>{!! $value["bruto"] !!}</td>
                <td>{!! $value["devolucao"] !!}</td>
            </tr>
        	@endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td>Total:</td>
                <td>{{ $total_bruto }}</td>
                <td>{{ $total_devolucao }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script>
$(document).ready( function () {
    table_filters_equipe_campanha = $('#table-filters-equipes-campanha-modal').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": false,
        "pageLength": 15,
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 1, 'asc' ]]
    });

    table_filters_equipe_campanha.on('draw', function () {
		$(document).find(".view-bruto-campanha").off("click");
		$(document).find(".view-bruto-campanha").on("click", function(event){
			event.stopPropagation();
			showModalBruto($(this));
		});

		$(document).find(".view-devolucao-campanha").off("click");
		$(document).find(".view-devolucao-campanha").on("click", function(event){
			event.stopPropagation();
			showModalDevolucao($(this));
		});
	});

    setTimeout(function(){
        table_filters_equipe_campanha.draw().nodes();
    },1500);
});

function showModalBruto($this){
	var code = $($this).data("code");
	var campos = $($this).data("campos");

	xhr = $.ajax({
		url: "{{ route("campanha.modal.valor_bruto") }}",
		data: {_token: "{{ csrf_token() }}", code : code, campos : campos},
		method: 'POST',
		success: function(body){
			createModal("modal_pedidos_valor_bruto_equipes", 'Lista de Pedidos', body, 'modal-lg');
		}
	});
}

function showModalDevolucao($this){
	var code = $($this).data("code");
	var campos = $($this).data("campos");

	xhr = $.ajax({
		url: "{{ route("campanha.modal.lista_nota_devolucao") }}",
		data: {_token: "{{ csrf_token() }}", code : code, campos : campos},
		method: 'POST',
		success: function(body){
			createModal("modal_devolucao_campanha_segmento", 'Lista de Notas de Devolução', body, 'modal-lg');
		}
	});
}
</script>
@endSection
