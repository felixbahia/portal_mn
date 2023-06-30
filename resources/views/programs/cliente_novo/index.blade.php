@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <input type="text" name="nome" id="nome" value="" placeholder="Nome / Razão Social" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="nome_guerra" id="nome_guerra" value="" placeholder="Nome Fantasia / Apelido" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="cnpj_cpf" id="cnpj_cpf" value="" placeholder="CNPJ / CPF" maxlength="250" />
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
                <th>Nome / Razão Social</th>
                <th>Nome Fantasia / Apelido</th>
                <th>CNPJ / CPF</th>
                <th>Cidade</th>
                <th>Estado</th>
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
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
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
            $(document).find(".bt-delete").off("click");
            $(document).find(".bt-delete").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });
        $("#btn-create").on("click", function(){
            showModalCreate();
        });
    });

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
                createModal('modal_cliente_novo_edit_delete', title, body, modal_class);
                var modal = $("#modal_cliente_novo_edit_delete");
            }
        });
    }
    function showModalCreate(){
        $.ajax({
            url: '{{ route('cliente_novo.create') }}',
            method: 'GET',
            success: function(body){
            	var title = 'Cadastro de Cliente {{ CustomView::programaName() }}';
    			createModal('modal_cliente_novo_adicionar', title, body, "modal-lg");
            	var modal = $("#modal_cliente_novo_adicionar");
            }
        });
    }
    function createBtnEdit($url, $dados){
        if($dados.exibe_botao === false){
            return "";
        }
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"modal-lg\" data-title_modal=\"Editar {{ CustomView::programaName() }} - "+$dados.nome_razao+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar Cliente\"></a>";
        return $html;
    }
    function createBtnDelete($url, $dados){
        if($dados.exibe_botao === false){
            return "";
        }
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"\" data-title_modal=\"Excluir {{ CustomView::programaName() }} - "+$dados.nome_razao+"\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir Cliente\"></a>";
        return $html;
    }
    function filterAjax(data_form){
        var $return;
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('cliente_novo.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                var data = callback.response;
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].status_descricao,
                            data[field].nome_razao,
                            data[field].guerra_apelido,
                            data[field].cpf_cnpj,
                            data[field].cidade,
                            data[field].estado,
                            createBtnEdit("{{ route('cliente_novo.edit') }}", data[field]),
                            createBtnDelete("{{ route('cliente_novo.delete') }}", data[field])
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).order([ 1, 'asc' ] ).draw().nodes();
                }
            }
        });
    }
@endsection
