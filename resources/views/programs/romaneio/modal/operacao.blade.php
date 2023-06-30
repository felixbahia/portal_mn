@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-dialog">
        <thead>
            <tr>
                <th>Colaborador</th>
                <th>Operação</th>
                <th>dOCUMENTO</th>
                <th class="tb_date">Data</th>
            </tr>
        </thead>
        <tbody>
           
            @foreach ($dados as $coletor_estoque)
             <tr>
              
                <td>{{ $coletor_estoque['colaborador'] }}</td>
                <td>{{ $coletor_estoque['operacao'] }}</td>
                <td>{{ $coletor_estoque['documento'] }}</td>
                <td class="tb_date">{{ $coletor_estoque['data_hora'] }}</td>
             </tr>   
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td class="tb_date"></td>
             </tr>   
        </tfoot>
    </table>
</div>
<script>
    table_dialog = $('#table-dialog').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 20,
        "orderMulti": false,

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
                "targets": "tb_number"
            },
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 0, 'asc' ]]
    });

    setTimeout(function(){
        table_dialog.draw(false);
    }, 200);
</script>
@endsection
