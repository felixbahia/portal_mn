@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">
    <form action="#" name="form_filter_dialog" id="form_filter_dialog" onsubmit="return false;">
        @csrf
        <div class="content-fields">
            <div class="col-lg-2">
                <input type="text" name="codigo" id="codigo" value="" placeholder="Código de Vendedor" maxlength="250" />
            </div>
            <div class="col-lg-3">
                <input type="text" name="nome" id="nome" value="" placeholder="Nome" maxlength="250" />
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </form>
</div>
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-vendedor">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nome</th>
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
        $('#table-filters-dialog-vendedor').find("td").off('mouseenter');
        $('#table-filters-dialog-vendedor').find("td").on('mouseenter', function(){
            var $this = $(this);
            if(this.offsetWidth < this.scrollWidth && !$this.attr('title')){
                $this.attr('data-original-title', $this.text());
            }
        });
        $('[data-toggle="tooltip"]').tooltip();
        table_dialog = $("#table-filters-dialog-vendedor").DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 10,
            "language": {
                "decimal":        ".",
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ",",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro encontrado",
                "columnDefs": [
                    {
                        "targets": ($('#table-filters-dialog-vendedor thead th').length - 1),
                        "orderable": false
                    },
                ],
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            }
        });
    });
    function changeTextOverflowTrs($dados){
        $.each($dados, function(k, line){
            $.each(line, function(k1, dado){
                $dados[k][k1] = "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+dado+"\">"+dado+"</div></div>";
            });
        });
        return $dados;
    }
    function filterAjaxDialog(data_form){
        var $return;
        table_dialog.clear().draw();
        $.ajax({
            url: "{{ route('vendedor.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status == "success"){
                    var data = callback.data;
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].codigo,
                            data[field].nome
                        ];
                        fields_filter.push(temp_field);
                    }
                    fields_filter = changeTextOverflowTrs(fields_filter);
                    table_dialog.rows.add(fields_filter).order([ 1, 'asc' ] ).draw().nodes();
                }
            }
        });
    }

</script>
@endsection