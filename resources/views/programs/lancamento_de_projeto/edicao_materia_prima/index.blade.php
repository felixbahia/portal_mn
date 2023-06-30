@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-3">
                {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'placeholder' => 'Estabelecimento' ]) !!}
            </div>
            <div class="col-lg-2">
                <input type="text" name="num_projeto" id="num_projeto" value="" placeholder="NÚMERO PROJETO" maxlength="40"/>
            </div>
            <div class="col-lg-3">
                <input type="text" name="nome_projeto" id="nome_projeto" value="" placeholder="NOME PROJETO" maxlength="40"/>
            </div>

            <div class="col-lg-4">
                {!! Form::select('representante', $representantes, '', ['id' => 'representante', 'placeholder'=> 'Todos Representantes']) !!}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="cliente" id="cliente" value="" placeholder="Cliente" maxlength="250" />
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="input-group">
                    {{ Form::text('faccao', '', ['id' => 'faccao', 'class' => 'form-control input-label', 'placeholder' => 'FACÇÃO']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-faccao-busca" data-route="{{ route("faccao.modal.buscar") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                    {!! Form::hidden('codigo_faccao', '', ['id' => 'codigo_faccao']) !!}
                </div>
            </div>
            <div class="col-lg-2">
                <input type="text" name="linha" id="linha" placeholder="LINHA" value="" maxlength="250">
            </div>
            <div class="col-lg-2">
                {!! Form::select('estado', $estados, '', ['id' => 'estado', 'placeholder'=> 'Todos os Status']) !!}
            </div>
        </div>
        
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped table-filter table-not-edit" id="table-filter">
        <thead>
            <tr>
                <th class="tb_number">Num. Projeto</th>
                <th>Projeto</th>
                <th>Cliente</th>
                <th>Status</th>
                <th class="tb_acao"></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
$(document).ready( function () {
    form = $(document).find('#form_filter');
    form.find("#linha").autocomplete(optionsAutoCompleteLinha());
    form.find("#faccao").autocomplete(optionsAutoCompleteFaccao());
    form.find("#cliente").autocomplete(optionsAutoCompleteCliente());

    form.find("#faccao").off('change');
    form.find("#faccao").on('change', function(){
        if(form.find("#faccao").val() == ''){
            form.find("#codigo_faccao").val('');
        }
    });

    $(document).find("#bt-search-cliente-busca").off("click");
    $(document).find("#bt-search-cliente-busca").on("click", function(event){
        event.stopPropagation();
        showModalCliente($(this).data("route"));
        return false;
    });

    $(document).find("#bt-search-faccao-busca").on("click", function(){
        showModalFaccao($(this).data("route"), "Lista de Facções");
    });

    table_filters = $('#table-filter').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "autoWidth": false,
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number", width: "100px" },
            { "class": "tb_date", targets: "sort-date", width: "100px" }
        ],
    });

    table_filters.on('draw', function () {
        $(document).find(".bt-edit").off("click");
        $(document).find(".bt-edit").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
    });

    $(document).find("#btn-filterform").off("click");
    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });

    $(document).find("#btn-clearform").off("click");
    $(document).find("#btn-clearform").on("click", function(){
        filterClear();
    });
});

function showModalFaccao(url, title){
    esconderPopoverTooltip();
    $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("faccao_search_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#faccao_search_show").find('tbody').find("tr").off("click");
                    $(document).find("#faccao_search_show").find('tbody').find("tr").on("click", function(){
                        returnDadosFaccao($(this));
                    });
                });
            });
        }
    });
}

function returnDadosFaccao($this){
    if($this.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#faccao_search_show").modal("hide");
    $(document).find("#faccao").val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
    $(document).find("#codigo_faccao").val($this.find("td").eq(0).text());
}

function optionsAutoCompleteFaccao(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('faccao.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($('#form_filter').css('z-index')) + 1));
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Nenhum fornecedor encontrado');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#faccao").val(ui.item.label);
            $(document).find("#codigo_faccao").val(ui.item.value);
            return false;
        }
    };
}

function optionsAutoCompleteCliente(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('clientes.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Nenhum cliente encontrado');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#cliente").val(ui.item.label);
            return false;
        }
    }
}

function showModalCliente(url){
    var title = "Busca de Clientes";
    esconderPopoverTooltip();
    $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            $(document).find('#cliente_searsh_show').remove();
            createModal("cliente_searsh_show", title, body, 'modal-lg');
            var modal = $(document).find("#cliente_searsh_show");
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    modal.find('tbody').find("tr").off("click");
                    modal.find('tbody').find("tr").on("click", function(event){
                        returnDadosCliente($(this), event);
                    });
                });
            });
        }
    });
}

function returnDadosCliente($dados, event){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
    $(document).find("#cliente_searsh_show").modal("hide");
}

function filterClear(){
    table_filters.clear().draw();
}

function filterAjax(){
    esconderPopoverTooltip();
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    limparMesagemErro(form);
    filterClear();
    $.ajax({
        url: '{{ route('lancamento_projeto.edicao_materia_prima.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            for (var fields in data.response){
                temp_array = [
                    data.response[fields].num_projeto,
                    data.response[fields].nome_projeto,
                    data.response[fields].cliente,
                    data.response[fields].status,
                    createBtnEdit("{{ route('lancamento_projeto.edicao_materia_prima.modal.editar_materia_prima') }}", data.response[fields].id),
                ];

                table_filters.row.add(temp_array).draw();
            }
        },
        error: function(data){
            var errors = data.responseJSON.error;
            for(var field in errors){
                showErrorsInputs(form, field, errors[field])
            }
        }
    });
}

function esconderPopoverTooltip(){
    $('[data-toggle="tooltip"]').tooltip('hide');
    $('[data-toggle="popover"]').popover('hide');
}

function showErrorsInputs(form, input, message){
    if (input == 'faccao'){
        var $input = $(form).find("#bt-search-faccao-busca");
        $input.after("<label class='error-message' for='descricao'>"+message+"</label>");
        $(form).find("#faccao").addClass('error-input');
    }else if (input == 'cliente'){
        var $input = $(form).find("#bt-search-cliente-busca");
        $input.after("<label class='error-message' for='descricao'>"+message+"</label>");
        $(form).find("#cliente").addClass('error-input');
    }
    else{
        var $input = $(form).find("input[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');

        var $select = $(form).find("select[name='"+input+"']");
        $select.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $select.addClass('error-input');
    }
}

function limparMesagemErro(form){   
    form.find('.error-message').remove();
    form.find('input, select, span').removeClass('error-input');
}

function optionsAutoCompleteLinha(){
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.linha.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
        },
        select: function( event, ui ) {
            setTimeout(function(){
                table_filters.draw();
            }, 100);
        }
    };
}

function createBtnEdit($url, $id){
    var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"modal-lg\" data-title_modal=\"Edição de Matéria-Prima\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>";
    return $html;
}

function showModal($this){
    var url = $($this).data("route");
    var $id = $($this).data("id");
    var modal_class = $($this).data("modal");
    var title = $($this).data("title_modal");
    $.ajax({
        url: url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", id: $id},
        success: function(body){
            createModal('modal_editar_materia_prima', title, body, modal_class);
            var modal = $("#modal_editar_materia_prima");
        }
    });
}

@endsection