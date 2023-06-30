@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-logs">
        <thead>
            <tr>
                <th class='sort-date'>Data e Hora</th>
                <th>Ação</th>
                <th>Descrição</th>
                <th>Usuário</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td class='tb_date'>{{ $log['data'] }}</td>
                <td>{{ $log['acao'] }}</td>
                <td>{!! $log['mensagem'] !!}</td>
                <td>{{ $log['usuario'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>

    table_filters_logs = $(document).find('#table-filters-dialog-logs').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "scrollX": false,
        "scrollCollapse": true,
        "paging": false,
        "ordering": false,
        "autoWidth": true,
        "language": {
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
            "decimal":        ",",
            "loadingRecords": "Carregando...",
            "processing":     "Processando...",
            "zeroRecords":    "Nenhum registro encontrado",
            "paginate": {
                "first":      "<<",
                "last":       ">>",
                "next":       ">",
                "previous":   "<"
            }
        }
        }
    );

</script>
@endsection