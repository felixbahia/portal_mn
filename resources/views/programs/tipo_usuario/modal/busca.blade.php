@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">
    <form action="#" name="form_filter_dialog_tipo_usuario" id="form_filter_dialog_tipo_usuario" onsubmit="return false;">
        @csrf
        <div class="content-fields">
            <div class="col-lg-3">
                {{ Form::text('nome', '', ['class' => 'form-control', 'id' => 'nome','placeholder' => 'Nome']) }}
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform-tipo-usuario-busca" id="btn-filterform-tipo-usuario-busca" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </form>
</div>
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-perfis">
        <thead>
            <tr>
                <th>Tipo Usuário</th>
                <th class="display_none"></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<script>
    table_dialog_tipo_usuario = [];
    $(document).ready( function () {
        $(document).find("#btn-filterform-tipo-usuario-busca").on("click", function(){
            filterAjaxTipoUsuario($(document).find("#form_filter_dialog_tipo_usuario").serialize());
        });

        table_dialog_tipo_usuario = $(document).find("#table-filters-dialog-perfis").DataTable({
            "searching": false,
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
                "targets": 'display_none',
                "class": "display_none"
            },
        ]
        });
    });

    function filterAjaxTipoUsuario(data_form){
        var $return;
        table_dialog_tipo_usuario.clear().draw();
        $.ajax({
            url: "{{ route('tipo_usuario.filter_modal') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.response.length > 0){
                    var fields_filter_tipo_usuario = [];
                    for(var field in data.response){
                        var temp_field = [
                            data.response[field].nome,
                            data.response[field].id
                        ];
                        fields_filter_tipo_usuario.push(temp_field);
                    }
                    table_dialog_tipo_usuario.rows.add(fields_filter_tipo_usuario).draw().nodes();
                }
            }
        });
    }
</script>
@endsection