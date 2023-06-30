@extends('layouts.page-dialog')

@section('content')
<div class="content-table-ajax">
    <table class="table table-striped table-exportFilter" id="table-users-role">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Usuário</th>
            </tr>
        </thead>
        <tbody>
            @foreach($usuarios as $key => $value)
            <tr>
                <td>{!! $value["nome"] !!}</td>
                <td>{!! $value["usuario"] !!}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <button name="add_user" id="add_user" class="btn btn-add-user-permission">Adicionar Usuário</button>
</div>
<script>
    $('[data-toggle="tooltip"]').tooltip();
    var table_lancamentos = $("#table-users-role").DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        //"responsive": true,
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
                "targets": ($('#table-users-role thead th').length - 1),
                "orderable": false
            }
        ]
    });
</script>
@endsection