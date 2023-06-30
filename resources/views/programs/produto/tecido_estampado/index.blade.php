@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-4">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="codigo_produto_final" id="codigo_produto_final" value="" placeholder="Codigo Tecido Estampado" maxlength="60"/>
                    <span class="input-group-addon border rounded-right" id="bt-search-produto_final"><i class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-8">
                    <input type="text" name="descricao_final" id="descricao_final" value="" placeholder="Descrição do Tecido Estampado" maxlength="120"/>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-2">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="codigo_produto_tecido_base" id="codigo_produto_tecido_base" value="" placeholder="Codigo Tecido Base" maxlength="60"/>
                    <span class="input-group-addon border rounded-right" id="bt-search-produto_tecido_base"><i class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-4">
                    <input type="text" name="descricao_tecido_base" id="descricao_tecido_base" value="" placeholder="Descrição do Tecido Base" maxlength="120"/>
            </div>
            <div class="col-lg-2">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="codigo_produto_desenho" id="codigo_produto_desenho" value="" placeholder="Codigo Desenho" maxlength="60"/>
                    <span class="input-group-addon border rounded-right" id="bt-search-produto_desenho"><i class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-4">
                    <input type="text" name="descricao_desenho" id="descricao_desenho" value="" placeholder="Descrição do Desenho" maxlength="120"/>
            </div>
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
    <table class="table table-striped table-not-view" id="table-filters-carteira">
        <thead>
            <tr>
                <th colspan="2" class="border-right text-center">Tecido Estampado</th>
                <th colspan="2" class="border-right text-center">Tecido Base</th>
                <th colspan="2" class="border-right text-center">Desenho</th>
                <th rowspan="2" class="td_acao"></th>
                <th rowspan="2" class="td_acao"></th>
            </tr>
            <tr>
                <th class="codigo_produto">Código</th>
                <th class="border-right text-center atraso-col">Descrição</th>
                <th class="codigo_produto">Código</th>
                <th class="border-right text-center atraso-col">Descrição</th>
                <th class="codigo_produto">Código</th>
                <th class="border-right text-center atraso-col">Descrição</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')

$(document).ready( function () {
    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });
    $("#btn-create").on("click", function(){
        showModalCreate();
    });


    table_filters = $('#table-filters-carteira').DataTable({
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
            { "class": "codigo_produto", targets: "codigo_produto", width: "200px"},
            {
                'targets': 'td_acao',
                'class': 'td_acao',
                "orderable": false
            }
        ],
    });

    form = $(document).find('#form_filter');
    form.find("#bt-search-produto_desenho").on('click', function(){
        showModalProduto(form, "grupo", "Desenho Estamparia Digital", "desenho");
    });
    form.find("#bt-search-produto_final").on('click', function(){
        showModalProduto(form, "", "", "final");
    });
    form.find("#bt-search-produto_tecido_base").on('click', function(){
        showModalProdutoTecidoBase(form);
    });

    form.find("#descricao_final").autocomplete(optionsAutoComplete("nome"));
    form.find("#descricao_tecido_base").autocomplete(optionsAutoCompleteTecidoBase());
    form.find("#descricao_desenho").autocomplete(optionsAutoCompleteLimitado("nome"));

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
});

function showModalCreate(){
    $.ajax({
        url: '{{ route('produto.tecido_estampado.modal.adicionar') }}',
        method: 'GET',
        success: function(body){
            var title = 'Cadastro de {{ CustomView::programaName() }}';
            createModal('modal_tecido_estampado_adicionar', title, body, '');
        }
    });
}

function filterAjax(){
    filterClear();
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('produto.tecido_estampado.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            
            for (var fields in data.response){
                temp_array = [
                    data.response[fields].codigo_produto_tecido_estampado,
                    ajusteTamanhoTable(data.response[fields].descricao_tecido_estampado),
                    data.response[fields].codigo_produto_tecido_base,
                    ajusteTamanhoTable(data.response[fields].descricao_tecido_base),
                    data.response[fields].codigo_produto_desenho,
                    ajusteTamanhoTable(data.response[fields].descricao_desenho),
                    createBtnEdit("{{ route('produto.tecido_estampado.modal.editar') }}", data.response[fields].id),
                    createBtnDelete("{{ route('produto.tecido_estampado.modal.deletar') }}", data.response[fields].id),
                ];

                produtos.push(temp_array)
            }
            table_filters.rows.add(produtos).draw();            

        }
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
            createModal('modal_tecido_estampado_edit_delete', title, body, modal_class);
            var modal = $("#modal_tecido_estampado_edit_delete");
        }
    });
}

function ajusteTamanhoTable($value){
    $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";

    return $html;
}

function optionsAutoCompleteLimitado($name){
    return {
        source: function (request, response) {
            request.name = $name;
            request.campo = "grupo";
            request.condicao = "Desenho Estamparia Digital";
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.autocompletelimitacao') }}", request, response);
        },
        delay: 700,
        minLength: 3,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($('#modal_tecido_estampado_adicionar').css('z-index')) + 1));
        },
        select: function( event, ui ) {
            event.stopPropagation();
            form.find('#descricao_desenho').val(ui.item.value)
            pesquisaProdutoDescricao(form.find('#descricao_desenho').val(), "grupo", "DESENHO ESTAMPARIA DIGITAL", "desenho");
            return false;
        }
    };
}

function optionsAutoComplete($name){
    return {
        source: function (request, response) {
            request.name = $name;
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($('#modal_tecido_estampado_adicionar').css('z-index')) + 1));
        },
        select: function( event, ui ) {
            event.stopPropagation();
            form.find('#descricao_final').val(ui.item.label);
            pesquisaProdutoDescricao(form.find('#descricao_final').val(), "", "", "final");
            return false;
        }
    };
}

function optionsAutoCompleteTecidoBase(){
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.tecido_base.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($('#modal_tecido_estampado_adicionar').css('z-index')) + 1));
        },
        select: function( event, ui ) {
            event.stopPropagation();
            form.find('#descricao_tecido_base').val(ui.item.value);
            return false;
        }
    };
}

function showModalProduto(form, campo, condicao, input){
    $.ajax({
        url: '{{ route('produto.modal_pesquisa_limitado') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            campo: campo,
            condicao: condicao
        },
        success: function (data){
            createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
            table_filters_produtos_busca.on('draw', function () {
                $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                    returnDadosProduto($(this), form, input);
                });

            });
        }
    });
}

function returnDadosProduto($dados, form,input){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#modal_search_produto").modal("hide");
    
    if(input == "desenho"){
        form.find('#codigo_produto_desenho').val($dados.find("td").eq(1).text());
    }else if(input == "final"){
        form.find('#codigo_produto_final').val($dados.find("td").eq(1).text());
    }
    
};

function showModalProdutoTecidoBase(form){
    $.ajax({
        url: '{{ route('produto.tecido_base.modal.buscar') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function (data){
            createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
            table_filters_produtos_busca.on('draw', function () {
                $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                    returnDadosProdutoTecidoBase($(this), form);
                });

            });
        }
    });
}

function returnDadosProdutoTecidoBase($dados, form){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#modal_search_produto").modal("hide");
    
    form.find('#codigo_produto_tecido_base').val($dados.find("td").eq(1).text());
};
@endsection