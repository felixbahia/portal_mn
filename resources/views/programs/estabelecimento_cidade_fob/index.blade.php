@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <select name="estabelecimento" id="estabelecimento">
                <option value="">Estábelecimento</option>
                @foreach(returnEmpresasNasajonView() as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
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
                <th>Estabelecimento</th>
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
                createModal('modal_estabelecimento_cidade_fob_edit_delete', title, body, modal_class);
                var modal = $("#modal_estabelecimento_cidade_fob_edit_delete");
            }
        });
    }
    function showModalCreate(){
        $.ajax({
            url: '{{ route('estabelecimento_cidade_fob.modal.adicionar') }}',
            method: 'GET',
            success: function(body){
            	var title = 'Cadastro de {{ CustomView::programaName() }}';
    			createModal('modal_estabelecimento_cidade_fob', title, body, '');
            }
        });
    }
    function createBtnEdit($url, $dados){
        if($dados.exibe_botao === false){
            return "";
        }
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar {{ CustomView::programaName() }}\"></a>";
        return $html;
    }
    function createBtnDelete($url, $dados){
        if($dados.exibe_botao === false){
            return "";
        }
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"\" data-title_modal=\"Excluir {{ CustomView::programaName() }}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir {{ CustomView::programaName() }}\"></a>";
        return $html;
    }
    function filterAjax(data_form){
        var $return;
        $.ajax({
            url: "{{ route('estabelecimento_cidade_fob.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
                    var data = callback.response;
                    table_filters.clear().draw();
                    if(data.length > 0){
                        var fields_filter = [];
                        for(var field in data){
                            var temp_field = [
                                data[field].estabelecimento,
                                data[field].cidade,
                                data[field].uf,
                                createBtnEdit("{{ route('estabelecimento_cidade_fob.modal.editar') }}", data[field]),
                                createBtnDelete("{{ route('estabelecimento_cidade_fob.modal.deletar') }}", data[field])
                            ];
                            fields_filter.push(temp_field);
                        }
                        table_filters.rows.add(fields_filter).draw().nodes();
                    }
                }
            }
        });
    }
@endsection
