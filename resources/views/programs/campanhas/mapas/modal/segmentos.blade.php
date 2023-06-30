@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-equipes-campanha">
        <thead>
            <tr>
                <th>Grupo</th>
                <th class="tb_number">VOLUME VENDIDO</th>
                <th class="tb_number">v. líquido</th>
            </tr>
        </thead>
        <tbody>
        	@foreach($segmentos as $value)
        	<tr>
                <td>{{ $value['grupo'] }}</td>
                <td>{{ $value["quantidade"] }}</td>
                <td>{{ $value["valor"] }}</td>
            </tr>
        	@endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total:</td>
                <td>{{ $total_quantidade }}</td>
                <td>{{ $total_valor }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script>
    table_filters_dialog_segmentos = $('#table-filters-dialog-equipes-campanha').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": false,
        "pageLength": 15,
        "ordering": true,
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
            { "class": "tb_number", targets: "tb_number" }
        ],
        order: [[1, 'desc']],
    });
    
    setTimeout(function(){
        table_filters_dialog_segmentos.draw();
    },2500);
</script>
@endSection
