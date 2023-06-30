@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-view-adintados-abertura">
            <thead>
                <tr>
                    <th>CONDIÇÃO PAGAMENTO DIAS</th>
                    <th>QTD TITULOS</th>
                    <th>VALOR TITULOS</th>
                    <th>%</th>
                    <th>ACUMULADO</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($response as $dado)
                <tr>
                    <td>{{ $dado['dias'] }}</td>
                    <td><a href="#" data-route="{{ route('analise_inadimplencia.modal.abertura.adiantados') }}" data-filter="{{ $dado['filter'] }}" class="bt-view-adintados-abertura" data-toggle="popover" data-trigger='hover' title="Titulos Adiantados" data-title='Titulos Adiantados'>{{ $dado['quantidade'] }}</a></td>
                    <td>{{ $dado['valor'] }}</td>
                    <td>{{ $dado['percentual'] }}</td>
                    <td>{{ $dado['acumulado'] }}</td>
                    <td>{{ $dado['percentual_acumulado'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total:</td>
                    <td><a href="#" data-route="{{ route('analise_inadimplencia.modal.abertura.adiantados') }}" data-filter="{{ $totais['filter'] }}" class="bt-view-adintados-abertura" data-toggle="popover" data-trigger='hover' title="Todos os Titulos Adiantados" data-title='Todos os Titulos Adiantados'>{{ $totais['quantidade'] }}</a></td>
                    <td class='tb_number'>{{ $totais['titulos'] }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>
$(document).ready( function () {
    table_filters_analitic_adiantado = $('#table-view-adintados-abertura')
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
            }
        },
        "columnDefs": [
            {
                "class": "tb_number", 
                "targets": [0,1,2,3,4,5],
                "orderable": false
            },{ targets: 0, "orderable": false, width: '10px'},
        ],
    });    
    table_filters_analitic_adiantado.on('draw', function () {
        $(document).find(".bt-view-adintados-abertura").off("click");
        $(document).find(".bt-view-adintados-abertura").on("click", function(event){
            event.stopPropagation();
            showModalAdiantados($(this));
        });
    });    
    table_filters_analitic_adiantado.draw();
});

function showModalAdiantados($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter},
        method: 'POST',
        success: function(body){
            createModal("model_abertura_adiantados", title, body, 'modal-lg');
        }
    });
}
</script>
@endsection  