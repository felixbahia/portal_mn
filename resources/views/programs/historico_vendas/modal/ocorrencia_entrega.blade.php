@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-filters-dialog_ocorrencias">
        <thead>
            <tr>
                <th>Data</th>
                <th>Evento</th>
                <th>Observação</th>
                <th>Nº Romaneio</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $value)
            <tr>
                <td class="tb_date"><div><div data-toggle="tooltip" data-order="{{ $value["dataHora_ocorrencia"] }}" data-html="true" title="{{ $value["dataHora_ocorrencia"] }}">{{ $value["dataHora_ocorrencia"] }}</div></div></td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["envento"] }}">{{ $value["envento"] }}</div></div></td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["observacao"] }}">{{ $value["observacao"] }}</div></div></td>
                <td class="tb_number">{{ $value["romaneio"] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<script type="text/javascript">
$(document).ready( function () {
    table_filters_dialog_ocorrencias = $('#table-filters-dialog_ocorrencias')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ]]
    });   
});
</script>
@endSection