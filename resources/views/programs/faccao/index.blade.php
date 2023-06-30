@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-4">
            <div class="input-group">
                {{ Form::text('faccao', '', ['id' => 'faccao', 'class' => 'form-control input-label', 'placeholder' => 'FACÇÃO']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-faccao-busca" data-route="{{ route("faccao.modal.buscar") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                {!! Form::hidden('codigo_faccao', '', ['id' => 'codigo_faccao']) !!}
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
    <table class="table table-striped" id="table-filters">
        <thead>
            <tr>
                <th>Facção</th>
                <th>CNPJ</th>
                <th>Editar</th>
                <th>Excluir</th>
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
    form.find("#faccao").autocomplete(optionsAutoCompleteFaccao());

    $(document).find("#bt-search-faccao-busca").on("click", function(){
        showModalFaccao($(this).data("route"), "Lista de Facções");
    });

    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
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
});

function showModalCreate(){
    $.ajax({
        url: '{{ route('faccao.modal.adicionar') }}',
        method: 'GET',
        success: function(body){
            var title = 'Cadastro de {{ CustomView::programaName() }}';
            createModal('modal_faccao_adicionar', title, body, '');
        }
    });
}

function filterAjax(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('faccao.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            
            for (var fields in data.response){
                temp_array = [
                    data.response[fields].descricao,
                    data.response[fields].cnpj,
                    createBtnEdit("{{ route('faccao.modal.editar') }}", data.response[fields].id),
                    createBtnDelete("{{ route('faccao.modal.deletar') }}", data.response[fields].id)
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
    var $codigo = $($this).data("codigo");
    var modal_class = $($this).data("modal");
    var title = $($this).data("title_modal");
    $.ajax({
        url: url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", id: $id, codigo: $codigo},
        success: function(body){
            createModal('modal_faccao_edit_delete', title, body, modal_class);
            var modal = $("#modal_faccao_edit_delete");
        }
    });
}

function showModalFaccao(url, title){
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
            return false;
        }
    };
}
@endsection