@extends('layouts.page-dialog')

@section('content')

<form action="#" name="form_filter_ajax" id="form_filter_ajax" onsubmit="return false;">
    @csrf
    <input type="hidden" name="role_id" id="role_id" value="">
    <div class="content-fields">
        <div class="col-lg-4">
            <input type="text" name="nome" id="nome" value="" placeholder="Nome" maxlength="250" />
        </div>
        <div class="col-lg-4">
            <input type="email" name="email" id="email" value="" placeholder="E-mail" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="setor" id="setor" value="" placeholder="Setor" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <select name="tipo" id="tipo">
                <option value="">Tipo de usuário</option>
                @foreach($tipos as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
<div class="content-table">
    <table class="table table-striped" id="table-filters-ajax-user">
        <thead>
            <tr>
                <th></th>
                <th></th>
                <th>Nome</th>
                <th>Email</th>
                <th>Setor</th>
                <th>Tipo</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<script>
    var table_ajax_user;
    $(document).ready( function () {
        $("#form_filter_ajax").find("#btn-filterform").off("click");
        $("#form_filter_ajax").find("#btn-filterform").on("click", function(){
            filterAjaxUser($("#form_filter_ajax").serialize());
        });
        $('[data-toggle="tooltip"]').tooltip();
        table_ajax_user = $("#table-filters-ajax-user").DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
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
            }
        });
        table_ajax_user.column( 0 ).visible( false );
        table_ajax_user.column( 1 ).visible( false );
    });
    function filterAjaxUser(data_form){
        var $return;
        table_ajax_user.clear().draw();
        $.ajax({
            url: "{{ route('usuario.filter-ajax-role') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].id,
                            data[field].role_exist,
                            data[field].name,
                            data[field].email,
                            data[field].setor,
                            data[field].tipo_usuario
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_ajax_user.rows.add(fields_filter).draw().nodes();
                }
            }
        });
    }
</script>
@endsection