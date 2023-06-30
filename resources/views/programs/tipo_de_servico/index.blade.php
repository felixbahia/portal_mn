@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <input type="text" name="tipo_de_servico" id="tipo_de_servico" value="" placeholder="TIPO DE SERVIÇO" maxlength="60"/>
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
                <th>Tipo de Serviço</th>
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
        url: '{{ route('tipo_de_servico.modal.adicionar') }}',
        method: 'GET',
        success: function(body){
            var title = 'Cadastro de {{ CustomView::programaName() }}';
            createModal('modal_tipo_de_servico_adicionar', title, body, '');
        }
    });
}

function filterAjax(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('tipo_de_servico.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            
            for (var fields in data.response){
                temp_array = [
                    data.response[fields].descricao,
                    createBtnEdit("{{ route('tipo_de_servico.modal.editar') }}", data.response[fields].id),
                    createBtnDelete("{{ route('tipo_de_servico.modal.deletar') }}", data.response[fields].id)
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
            createModal('modal_tipo_de_servico_edit_delete', title, body, modal_class);
            var modal = $("#modal_tipo_de_servico_edit_delete");
        }
    });
}
@endsection