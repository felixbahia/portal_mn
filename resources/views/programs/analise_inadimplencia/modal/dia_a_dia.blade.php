@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-filters-analitic-modal">
            <thead>
                <tr>
                    <th class='tb_date'>Data da entrada</th>
                    <th class='tb_number'>Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($retorno as $data => $valor)
                <tr>
                    <td data-order="{{ $data }}">{{ parserData($data) }}</td>
                    <td>{{ $valor }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td><b>Total:</b></td>
                    <td class='tb_number'>{{ $total }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>
$(document).ready( function () {
    table_filters_analitic_inadimplencia = $('#table-filters-analitic-modal')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 20,
        "autoWidth": false,
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
            },
        },
        "columnDefs": [
            {
                "targets": "tb_date",
                "class": "tb_date"
            },
            {
                "targets": "tb_number",
                "class": "tb_number"
            },
        ]
    });        
    table_filters_analitic_inadimplencia.on('draw', function () {
        $(document).find(".bt-view-inadimplente-abertura").off("click");
        $(document).find(".bt-view-inadimplente-abertura").on("click", function(event){
            event.stopPropagation();
            showModalInadimplencia($(this));
        });
    });
    table_filters_analitic_inadimplencia.draw();
});

function showModalInadimplencia($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');

    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter},
        method: 'POST',
        success: function(body){
            createModal("model_abertura_emitidos", title, body, 'modal-lg');
        }
    });
}
</script>
@endsection  