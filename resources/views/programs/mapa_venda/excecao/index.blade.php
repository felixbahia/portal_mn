@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form-control', 'maxlength' => '40']) }}
        </div>
        <div class="col-lg-2">
            <input type="text" name="numero_nota" id="numero_nota" value="" placeholder="Nota Fiscal" maxlength="15"/>
        </div>
        <div class="form-group col-sm-2 col-xl-3">
            <div class="input-group">
                {{ Form::text('cliente_nome', '', ['id' => 'cliente_nome', 'class' => 'form-control input-label', 'placeholder' => 'Cliente']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
            {!! Form::hidden('codigo_cliente', '', ['id' => 'codigo_cliente']) !!}
        </div>
        <div class="col-lg-2">
            <input type="text" class="data" name="data_inicio" id="data_inicio" value="" placeholder="Data Início DD/MM/AAAA" maxlength="20"/>
        </div>
        <div class="col-lg-2">
            <input type="text" class="data" name="data_fim" id="data_fim" value="" placeholder="Data Fim DD/MM/AAAA" maxlength="20"/>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped table-not-edit" id="table-filters">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Cliente</th>
                <th class="tb_number">Nota Fiscal</th>
                <th class="sort-date">Data Emissão</th>
                <th>Equipe</th>
                <th class="td_acao">Editar</th>
                <th class="td_acao">Excluir</th>
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
    initMaskCamposAdd($(document).find('#form_filter'));
    form.find("#cliente_nome").autocomplete(optionsAutoCompleteCliente(form));

    form.find("#cliente_nome").off("change");
    form.find("#cliente_nome").on("change", function() {
        if(form.find("#cliente_nome").val() == ''){
            form.find("#codigo_cliente").val('');
        }
    })

    form.find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });
    form.find("#btn-clearform").on("click", function(){
        filterClear();
    });
    $("#btn-create").on("click", function(){
        showModalCreate();
    });
    table_filters.on('draw', function () {
        $(document).find(".bt-edit").off("click");
        $(document).find(".bt-edit").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
        $(document).find(".bt-delete").off("click");
        $(document).find(".bt-delete").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
    });

    form.find("#bt-search-cliente-busca").off("click");
    form.find("#bt-search-cliente-busca").on("click", function(event){
        event.stopPropagation();
        showModalClienteIndex($(this).data("route"));
        return false;
    });

    table_filters.destroy();
    table_filters = $('#table-filters').DataTable({
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "tb_date", targets: "sort-date" },
            { "class": "td_acao", targets: "td_acao" }
        ],
    });

});

function showModalCreate(){
    $.ajax({
        url: '{{ route('mapa_venda.excecao.modal.adicionar') }}',
        method: 'GET',
        success: function(body){
            var title = 'Cadastro de {{ CustomView::programaName() }}';
            createModal('modal_mapa_venda_excecao_add', title, body, '');
        }
    });
}

function filterAjax(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $('[data-toggle="popover"]').popover('hide');
    $('[data-toggle="tooltip"]').tooltip('hide');
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('mapa_venda.excecao.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            console.log(data);
            for (var fields in data.response){
                temp_array = [
                    data.response[fields].estabelecimento,
                    data.response[fields].cliente,
                    data.response[fields].nota,
                    data.response[fields].data_emissao,
                    data.response[fields].equipe,
                    createBtnEdit("{{ route('mapa_venda.excecao.modal.editar') }}", data.response[fields].id),
                    createBtnDelete("{{ route('mapa_venda.excecao.modal.deletar') }}", data.response[fields].id)
                ];
                produtos.push(temp_array)
            }
            table_filters.rows.add(produtos).draw();            

        },
        error: function(callback){
            if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                $.each(data, function(index, el) {
                    form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                    form.find('input[name="'+index+'"]').eq(0).addClass('error');
                });
                form.find('input.error').eq(0).focus();
            }
        }
    }).always(function() {
        hide_loader();
    });
}

function filterClear(){
    table_filters.clear().draw();
}

function createBtnEdit($url, $id){
    var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>";
    return $html;
}
function createBtnDelete($url, $id){
    var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Excluir {{ CustomView::programaName() }}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\"></a>";
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
            createModal('modal_grupo_edit_delete', title, body, modal_class);
            var modal = $("#modal_grupo_edit_delete");
        }
    });
}

function initMaskCamposAdd(form_modal_add){
    form_modal_add.find('.data').datepicker({ 
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        language: 'pt-BR',
        autoHide: true
    });
    form_modal_add.find('.data').mask('00/00/0000');
    $('#data_inicio').on('pick.datepicker', function (e) {
        if($('#data_fim').datepicker('getDate') < e.date){
            $('#data_fim').val('');
        }
        $('#data_fim').datepicker('setStartDate', e.date);
    });
}

function showModalClienteIndex(url){
    var title = "Busca de Clientes";
    $.ajax({
        url: url,
        method: 'POST',
        data: {_token: '{{ csrf_token() }}'},
        success: function(body){
            $(document).find('#cliente_searsh_show').remove();
            createModal("cliente_searsh_show", title, body, 'modal-lg');
            var modal = $(document).find("#cliente_searsh_show");
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    modal.find('tbody').find("td").not('.th_view').off("click");
                    modal.find('tbody').find("td").not('.th_view').on("click", function(event){
                        returnDadosClienteIndex($(this).parent('tr'), event);
                    });
                });
            });
        }
    });
}

function returnDadosClienteIndex($dados, event){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#cliente_nome").val($dados.find("td").eq(1).text());
    $(document).find("#codigo_cliente").val($dados.find("td").eq(0).text());
    $(document).find("#cliente_searsh_show").modal("hide");
}
function optionsAutoCompleteCliente(form){
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
            form.find("#cliente_nome").val(ui.item.label);
            form.find("#codigo_cliente").val(ui.item.cpf_cnpj);
            return false;
        }
    };
}
@endsection