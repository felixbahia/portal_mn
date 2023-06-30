@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <input type="text" name="titulo" id="titulo" value="" placeholder="Titulo" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="enviado_por" id="enviado_por" value="" placeholder="Enviado por" maxlength="250" />
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
    <table class="table table-striped table-not-edit" id="table-filters">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Titulo</th>
                <th>Enviado por</th>
                <th>Assunto</th>
                <th>Conteudo</th>
                <th>Editar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
<script>
@section('script-footer')
    $(document).ready( function () {
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });
        table_filters.on('draw', function () {
            $(document).find(".bt-edit").off("click");
            $(document).find(".bt-edit").on("click", function(event){
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
                createModal('modal_message_edit', title, body, modal_class);
            }
        });
    }

    function createBtnEdit($url, $id, $nome){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"modal-lg\" data-title_modal=\"Editar {{ CustomView::programaName() }} - "+$nome+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>";
        return $html;
    }

    function filterAjax(data_form){
        var $return;
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('email.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
                    var data = callback.response;
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].estabelecimento,
                            data[field].titulo,
                            data[field].enviado_por,
                            data[field].assunto,
                            "<div><div>"+data[field].conteudo+"</div></div>",
                            createBtnEdit("{{ route('email.edita') }}", data[field].id, data[field].titulo),
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).order([ 0, 'asc' ],[ 1, 'asc' ] ).draw().nodes();
                }
            }
        });
    }
@endsection
</script>