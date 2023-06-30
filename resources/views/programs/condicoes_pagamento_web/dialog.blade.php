@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">
    <form action="#" name="form_filter_dialog" id="form_filter_dialog" onsubmit="return false;">
        @csrf
        <input type="hidden" name="estabelecimento" value="{{ $estabelecimento }}">
         <div class="content-fields">
            <div class="col-lg-2">
                <input type="text" name="descricao" id="descricao" value="" placeholder="Descrição" maxlength="250" />
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </form>
</div>
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-condicao-pagamento">
        <thead>
            <tr>
                <th>Código</th>
                <th>Descrição</th>
                <th>Vencimentos</th>
                <th>Média</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<script>
    table_dialog = [];
    $(document).ready( function () {
        $("#form_filter_dialog").find("#btn-filterform").on("click", function(){
            filterAjaxDialog($("#form_filter_dialog").serialize());
        });
        table_dialog = $("#table-filters-dialog-condicao-pagamento").DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
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
    });
    function filterAjaxDialog(data_form){
        var $return;
        table_dialog.clear().draw();
        $.ajax({
            url: "{{ route('condicoes_pagamento_web.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].id,
                            data[field].descricao,
                            data[field].vencimentos,
                            data[field].media
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_dialog.rows.add(fields_filter).order([ 1, 'asc' ] ).draw().nodes();
                }
            }
        });
    }
</script>
@endsection