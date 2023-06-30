@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_cliente_bionexo" id="form_cliente_bionexo" onsubmit="return false">
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="form-group col-sm-4 col-xl-3">
            <div class="input-group">
                {{ Form::text('cliente_nome', '', ['id' => 'cliente_nome', 'class' => 'form-control input-label', 'placeholder' => 'Cliente']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Novo Cliente</button>
    </div>
</form>

@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters">
        <thead>
            <tr>
                <th>Nome Cliente</th>
                <th class="tb_number">CPF / CNPJ</th>
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
$(document).ready(function(){

    $(document).find("#cliente_nome").autocomplete(optionsAutoCompleteCliente('cliente_nome'));

    $(document).find('#btn-filterform').on('click', function(event){
        event.stopPropagation();
        filtro();
    });
    $(document).find("#btn-create").off("click");
    $(document).find("#btn-create").on("click", function(){
        event.stopPropagation();
        showModalCreate();
    });
    $(document).find("#bt-search-cliente-busca").off("click");
    $(document).find("#bt-search-cliente-busca").on("click", function(event){
        event.stopPropagation();
        showModalClienteBusca($(this).data("route"));
        return false;
    });

    table_filters.on('draw', function () {
        $(document).find(".bt-edit").off("click");
        $(document).find(".bt-edit").on("click", function(){
            showModalEdit($(this).data('id'));
        });
        $(document).find(".bt-delete").off("click");
        $(document).find(".bt-delete").on("click", function(){
            showModalDelete($(this).data('id'));
        });
    });

});

function filtro(){
    table_filters.clear().draw();
    $.ajax({
        url: "{{ route('cliente_bionexo.filter') }}", 
        dataType: 'json',
        data: {
            _token: "{{ csrf_token() }}",
            cliente_nome: $(document).find('#cliente_nome').val()
        },
        method: 'POST',
        success: function(callback){
            dados = callback.response;
            table_filters.clear().draw();
            if(dados.length > 0){
                var fields_filter = [];
                for(var field in dados){
                    var temp_field = [
                        dados[field].nome_razao,
                        dados[field].cpf_cnpj,
                        createBtnEdit("{{ route('cliente_bionexo.modal.editar') }}", dados[field]),
                        createBtnDelete("{{ route('cliente_bionexo.modal.deletar') }}", dados[field])
                    ];
                    fields_filter.push(temp_field);
                }
                table_filters.rows.add(fields_filter).draw().nodes();
                
            }
        },
        error: function(callback){
            message('Atenção', 'Nenhum cliente localizado.');
        }
    });
}

function createBtnEdit($url, $dados){

    var $html = "<a href='#' data-id=\""+$dados.id+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar Cliente Bionexo\"></a>";
    return $html;
}


function createBtnDelete($url, $dados){
    var $html = "<a href='#' data-id=\""+$dados.id+"\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir Cliente Bionexo\"></a>";
    return $html;
}

function optionsAutoCompleteCliente($elemento){
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
            $('.ui-autocomplete').css("z-index", $("#" + $elemento).parents('.modal').css('z-index') + 1);
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
            $(document).find("#" + $elemento).val(ui.item.label);
            return false;
        }
    };
}

function showModalCreate($id = null) {
    $.ajax({
        url: '{{ route("cliente_bionexo.modal.adicionar") }}',
        data: {_token: '{{ csrf_token() }}'},
        method: 'GET',
        success: function(body){
            createModal("modal_cliente_bionexo", 'Novo Cliente Bionexo', body, '');
        },
        error: function(data){
            message('Alerta', data.responseJSON.error.msg.user);
        }
    });
}

function showModalEdit($id) {
    $.ajax({
        url: '{{ route("cliente_bionexo.modal.editar") }}',
        data: {_token: '{{ csrf_token() }}', id: $id},
        method: 'GET',
        success: function(body){
            createModal("modal_cliente_edit_bionexo", 'Editar Cliente Bionexo', body, '');
        },
        error: function(data){
            message('Alerta', data.responseJSON.error.msg.user);
        }
    });
}

function showModalDelete($id) {
    $.ajax({
        url: '{{ route("cliente_bionexo.modal.deletar") }}',
        data: {_token: '{{ csrf_token() }}', id: $id},
        method: 'GET',
        success: function(body){
            createModal("modal_cliente_delete_bionexo", 'Excluir Cliente Bionexo', body, '');
        },
        error: function(data){
            message('Alerta', data.responseJSON.error.msg.user);
        }
    });
}

function showModalClienteBusca(url){
    var title = "Busca de Clientes";
    $.ajax({
        url: url,
        method: "GET",
        data: {
            _token: "{{csrf_token()}}"
        },
        success: function(body){
            $(document).find("#cliente_searsh_show").remove();
            createModal("cliente_searsh_show", title, body, "modal-lg");
            var modal = $(document).find("#cliente_searsh_show");
            $(document).ready( function () {
                table_dialog.on("draw", function () {
                    modal.find("tbody").find("tr").off("click");
                    modal.find("tbody").find("tr").on("click", function(event){
                        returnDadosClienteBusca($(this), event);
                    });
                });
            });
        }
    });
}

function showModalClienteBusca(url){
    var title = "Busca de Clientes";
    $.ajax({
        url: url,
        method: "GET",
        data: {
            _token: "{{csrf_token()}}"
        },
        success: function(body){
            $(document).find("#cliente_searsh_show").remove();
            createModal("cliente_searsh_show", title, body, "modal-lg");
            var modal = $(document).find("#cliente_searsh_show");
            $(document).ready( function () {
                table_dialog.on("draw", function () {
                    modal.find("tbody").find("tr").off("click");
                    modal.find("tbody").find("tr").on("click", function(event){
                        returnDadosClienteBusca($(this), event);
                    });
                });
            });
        }
    });
}

function returnDadosClienteBusca($dados, event){
    if($dados.find("td").eq(0).hasClass("dataTables_empty")){
        return false;
    }
    $(document).find("#cliente_nome").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
    $(document).find("#cliente_searsh_show").modal("hide"); 
}
@endsection