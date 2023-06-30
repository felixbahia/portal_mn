@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">	
    <form action="#" name="form_filter_ajax" id="form_filter_ajax" onsubmit="return false;">
        @csrf

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
</div>
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped" id="table-filters-user">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Código Representante</th>
                    <th>Email</th>
                    <th>Setor</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
<script>
    table_modal_buscar_user = $("#table-filters-user").DataTable({
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
    $(document).ready( function () {
        $("#form_filter_ajax").find("#btn-filterform").off("click");
        $("#form_filter_ajax").find("#btn-filterform").on("click", function(){
            filterModalBuscar($("#form_filter_ajax").serialize());
        });
        $('[data-toggle="tooltip"]').tooltip();;
    });
    function filterModalBuscar(data_form){
        var $return;
        table_modal_buscar_user.clear().draw();
        $.ajax({
            url: "{{ route('usuario.filtro_buscar') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                var fields_filter = [];
                for(var field in data.response){
                    var temp_field = [
                        data.response[field].nome,
                        data.response[field].codigo_representante,
                        data.response[field].email,
                        data.response[field].setor,
                        data.response[field].tipo_usuario
                    ];
                    fields_filter.push(temp_field);
                }
                table_modal_buscar_user.rows.add(fields_filter).draw().nodes();
            }
        });
    }
</script>
@endsection