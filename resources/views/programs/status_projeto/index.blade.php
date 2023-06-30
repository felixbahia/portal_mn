@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-4">
            <input type="text" name="status" id="status" value="" placeholder="Status" maxlength="250" />
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
                <th>Status</th>
                <th>Posição</th>
                <th>Editar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        $("#btn-filterform").on("click", function(){
            filtrarStatus($("#form_filter").serialize());
        });
        table_filters.on('draw', function () {
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
            $(document).find(".bt-edit").off("click");
            $(document).find(".bt-edit").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });
        $("#btn-create").on("click", function(){
            showModalCreate();
        });
    });
    function showModalCreate(){
        $.ajax({
            url: '{{ route('status_projeto.modal.adicionar') }}',
            method: 'GET',
            data: {_token: "{{ csrf_token() }}"},
            success: function(body){
            	var title = 'Cadastro de {{ CustomView::programaName() }}';
    			createModal('modtal_retornos_cobrancas_adicionar', title, body, "");
            	var modal = $("#modtal_retornos_cobrancas_adicionar");
            }
        });
    }
    function createBtnEdit($url, $id){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar {{ CustomView::programaName() }}\"></a>";
        return $html;
    }
    function showModal($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: url,
            method: 'GET',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_status_projeto_edit', title, body, modal_class);
                var modal = $("#modal_status_projeto_edit");
            }
        });
    }
    function filtrarStatus(data_form){
        var $return;
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('status_projeto.filtrar') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                var data = callback.response;
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].posicao,
                            data[field].status,
                            createBtnEdit("{{ route('status_projetos.modal.editar') }}", data[field].id)
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).order([ 0, 'asc' ] ).draw().nodes();
                }
            }
        });
    }
@endsection
