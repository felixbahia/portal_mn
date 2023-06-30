@extends('layouts.page-dialog')

@section('content')
    <div class="content-dialog-table">
        <table class='table table-striped table-not-edit' id='table-historico-atualizacao'>
            <thead>
                <tr>
                    <th class='date_format'>Data de Alteração</th>
                    <th>Usuario</th>
                    <th class='number_format'>Valor Antigo</th>
                    <th class='number_format'>Valor Novo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($retorno as $valor)
                <tr>
                    <td>{{ $valor['data_alteracao'] }}</td>
                    <td>{{ $valor['usuario'] }}</td>
                    <td>{{ $valor['valor_antigo'] }}</td>
                    <td>{{ $valor['valor_novo'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
    table_dialog_ultimas_compras = $('#table-historico-atualizacao').DataTable({
        "searching": false,
        "paging": true,
        "lengthChange": false,
        "info": false,
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
            { 
                "targets": 'number_format',
                "class": 'number_format',
                "width": '5%' 
            },
            { 
                "targets": 'date_format',
                "class": 'date_format',
                "width": '5%' 
            },
        ],
        "order": [[ 0, "desc" ]]

    });
    
    </script>

@endsection