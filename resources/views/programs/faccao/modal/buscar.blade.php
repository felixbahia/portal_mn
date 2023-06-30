@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">
    <form action="#" name="form_filter_dialog" id="form_filter_dialog" onsubmit="return false;">
        @csrf
        {!! Form::hidden('tipo_de_servico', $tipo_de_servico, ['id'=>'tipo_de_servico']) !!}
        <div class="content-fields">
            <div class="col-lg-2">
                <input type="text" name="codigo_cad" id="codigo_cad" value="" placeholder="Código de cadastro" maxlength="250" />
            </div>
            <div class="col-lg-3">
                <input type="text" name="razao" id="razao" value="" placeholder="Nome / Razão Social" maxlength="250" />
            </div>
            <div class="col-lg-3">
                <input type="text" name="fantasia" id="fantasia" value="" placeholder="Nome Fantasia / Apelido" maxlength="250" />
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
                    <th>País</th>
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
        limparMesagemErro();
        var $return;
        table_dialog.clear().draw();
        $.ajax({
            url: "{{ route('faccao.busca.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                var fields_filter = [];
                for(var field in data.response){
                    var temp_field = [
                        data.response[field].codigo,
                        data.response[field].nome,
                        data.response[field].nomefantasia,
                        data.response[field].cnpj_cpf,
                        data.response[field].municipio,
                        data.response[field].estado,
                        data.response[field].pais
                    ];
                    fields_filter.push(temp_field);
                }
                fields_filter = changeTextOverflowTrs(fields_filter);
                table_dialog.rows.add(fields_filter).order([ 1, 'asc' ] ).draw().nodes();
            }
            ,
            error: function(callback){
                mensagemErro(callback.responseJSON);
            }

        });
    }

    function mensagemErro(json_error){
        var form_modal = $(document).find("#form_filter_dialog");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputs(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputs(form_modal, input, message){
        var $input = form_modal.find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function limparMesagemErro(){     
        var form_modal = $("#form_filter_dialog");
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span').removeClass('error-input');

    }
</script>
@endsection
