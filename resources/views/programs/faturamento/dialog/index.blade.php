@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view table-dialog-faturamento" id="table-dialog">
        <thead>
            <tr>
                <th>Cliente</th>
                <th class="tb_number">Valor Comprado</th>
                <th class="tb_number">Valor Devolvido</th>
                <th class="tb_number">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($return as $dado)
            <tr>
                <td><div><div data-toggle="tooltip" data-trigger="hover" data-placement="top" title="{{ $dado['cliente'] }}">{{ $dado['cliente'] }}</div></div></td>
                <td>{{ $dado['total_compra'] }}</td>
                <td>{{ $dado['total_devolucao'] }}</td>
                <td>{{ $dado['total'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        $height = $(document).find(".modal").height() - 200;
        $('#table-dialog').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollY": $height,
            "scrollCollapse": true,
            "paging": false,
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
            },
            "columnDefs": [
                {
                    "class": "tb_number", 
                    "type": 'num-fmt', 
                    "targets": "tb_number",
                    render: $.fn.dataTable.render.number( '.', ',', 2 )
                },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 3, 'desc' ]]
        });
    });
</script>
@endsection
