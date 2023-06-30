@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">
    <form action="#" name="form_filter_dialog" id="form_filter_dialog" onsubmit="return false;">
        @csrf
        <div class="content-fields">
            <div class="col-lg-2">
                <input type="text" name="cdcad" id="cdcad" value="" placeholder="Código de cadastro" maxlength="250" />
            </div>
            <div class="col-lg-3">
                <input type="text" name="nome" id="nome2" value="" placeholder="Nome / Razão Social" maxlength="250" />
            </div>
            <div class="col-lg-3">
                <input type="text" name="nome_guerra" id="nome_guerra" value="" placeholder="Nome Fantasia / Apelido" maxlength="250" />
            </div>
            <div class="col-lg-2">
                <input type="text" name="cnpj_cpf" id="cnpj_cpf" value="" placeholder="CNPJ / CPF" maxlength="250" />
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </form>
</div>
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-cliente">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nome / Razão Social</th>
                <th>Nome Fantasia / Apelido</th>
                <th>CNPJ / CPF</th>
                <th>Cidade</th>
                <th>Estado</th>
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
        $('#table-filters-dialog-cliente').find("td").off('mouseenter');
        $('#table-filters-dialog-cliente').find("td").on('mouseenter', function(){
            var $this = $(this);
            if(this.offsetWidth < this.scrollWidth && !$this.attr('title')){
                $this.attr('data-original-title', $this.text());
            }
        });
        $('[data-toggle="tooltip"]').tooltip();
        table_dialog = $("#table-filters-dialog-cliente").DataTable({
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
                        "targets": ($('#table-filters-dialog-cliente thead th').length - 1),
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
    function createBtSelect($this){
        return "<a href=\"#\" data-dados='"+JSON.stringify($this)+"' class=\"bt-selected\"></a>";
    }
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
            url: "{{ route('cliente.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].codcad,
                            data[field].nome,
                            data[field].guerra,
                            data[field].cgc_cpf,
                            data[field].cidade,
                            data[field].estado
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