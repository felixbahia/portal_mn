@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">
    <form action="#" name="form_filter_dialog_perfil" id="form_filter_dialog_perfil" onsubmit="return false;">
        @csrf
        <div class="content-fields">
            <div class="col-lg-3">
                {{ Form::text('nome', '', ['class' => 'form-control', 'id' => 'nome','placeholder' => 'Nome']) }}
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform-perfil-busca" id="btn-filterform-perfil-busca" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </form>
</div>
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-perfis">
        <thead>
            <tr>
                <th>Perfil</th>
                <th class="display_none"></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<script>
    table_dialog_perfis = [];
    $(document).ready( function () {
        $(document).find("#btn-filterform-perfil-busca").on("click", function(){
            filterAjaxPerfil($(document).find("#form_filter_dialog_perfil").serialize());
        });

        table_dialog_perfis = $(document).find("#table-filters-dialog-perfis").DataTable({
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

    function filterAjaxPerfil(data_form){
        var $return;
        table_dialog_perfis.clear().draw();
        $.ajax({
            url: "{{ route('perfil.filter_modal') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.response.length > 0){
                    var fields_filter_perfil = [];
                    for(var field in data.response){
                        var temp_field = [
                            data.response[field].name,
                            data.response[field].id
                        ];
                        fields_filter_perfil.push(temp_field);
                    }
                    table_dialog_perfis.rows.add(fields_filter_perfil).draw().nodes();
                }
            }
        });
    }
</script>
@endsection