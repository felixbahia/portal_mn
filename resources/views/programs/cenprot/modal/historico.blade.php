@extends('layouts.page-dialog')

@section('content')

<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog_historico_titulos">
        <thead>
            <tr>
                <th>status</th>
                <th class="sort-date">Data Hora</th>
                <th>criado por</th>
                <th>Observação</th>
                <th>Protocolo</th>
                <th>Data Protocolo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($historico as $linha)
            <tr>
                <td>{{ $linha['status'] }}</td>
                <td>{{ $linha['data_hora'] }}</td>
                <td>{{ $linha['criado_por'] }}</td>
                <td>{{ $linha['mensagem'] }}</td>
                <td>{{ $linha['protocolo'] }}</td>
                <td>{{ $linha['protocolo_data'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script type="text/javascript">

    $(document).ready(function(){
        table_filters_dialog_historico_titulos.draw();
    });
    
    table_filters_dialog_historico_titulos = $('#table-filters-dialog_historico_titulos').DataTable({
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
            { "class": "tb_date", targets: "sort-date" },
            { "class": "tb_icone", targets: "icone"}
        ],
        "order": [[ 1, 'asc' ]]
    });
</script>

@endSection