@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-4">
            <input type="text" name="motivo" id="motivo" value="" placeholder="Motivo" maxlength="250" />
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
                <th>Motivo</th>                
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
        $("#btn-create").off("click");
        $("#btn-create").on("click", function(){
            showModalCreate();
        });

        $("#btn-filterform").off("click");
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });
    });

    function showModalCreate(){
        $.ajax({
            url: '{{ route('produto.ajuste_estoque.motivo.modal.adicionar') }}',
            method: 'POST',
            data: {_token: "{{ csrf_token() }}"},
            success: function(body){
            	var title = 'Cadastro de {{ CustomView::programaName() }}';
    			createModal('modal_motivo_ajuste_estoque_adicionar', title, body, "");
            	var modal = $("#modal_motivo_ajuste_estque_adicionar");
            }
        });
    }

    function filterAjax(data_form){
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('produto.ajuste_estoque.motivo.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                var data = callback.response;
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].motivo,
                            createBtnEdit("{{ route('produto.ajuste_estoque.motivo.modal.editar') }}", data[field].id),
                            createBtnDelete("{{ route('produto.ajuste_estoque.motivo.modal.deletar') }}", data[field].id)
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).draw().nodes();
                }
            }
        });
    }

    function createBtnEdit($url, $id){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\" onclick=\"showModal($(this))\"></a>";
        return $html;
    }
    function createBtnDelete($url, $id){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Excluir {{ CustomView::programaName() }}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\" onclick=\"showModal($(this))\"></a>";
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
                createModal('modal_motivo_ajuste_estoque_edit_delete', title, body, modal_class);
                var modal = $("#modal_motivo_ajuste_estoque_edit_delete");
            }
        });
    }
@endsection