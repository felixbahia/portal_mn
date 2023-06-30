@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-sm-3">
            <input type="text" class="input-search-bt" name="cliente_nome" id="cliente_nome" value="" placeholder="Nome / Razão Social" maxlength="250" />
            <a href="#" id="bt-search" class="bt-view" data-route="{{ route("cliente.index.dialog") }}"></a>
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
    <table class="table table-striped" id="table-filters2">
        <thead>
            <tr>
                <th>Raiz de CNPJ</th>
                <th class="tb_number">Valor Limite</th>
                <th class="tb_date">Data do crédito</th>
                <th>Usuário alterou</th>
                <th class="td_acao"></th>
                <th class="td_acao"></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
    function parserDataJson(data){
        var $return = [];
        $.each(data, function(index, el) {
            var temp = {
                "raiz_cnpj" : this.raiz_cnpj,
                "valor_limite" : this.valor_limite,
                "data_credito" : this.data_credito,
                "usuario_cadastrou_alterou" : this.usuario_cadastrou_alterou,
                "editar" : createBtnEdit("{{ route('cliente.limite.modal.editar') }}", this),
                "deletar" : createBtnDelete("{{ route('cliente.limite.modal.deletar') }}", this)
            };
            $return.push(temp);
        });
        return $return;
    }
    table_filters = $('#table-filters2')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "processing": true,
        "serverSide": true,
        "orderMulti": false,
        "ajax": {
            "url": "{{ route('cliente.limite.filter') }}",
            "type": "POST",
            "data": function ( d ) {
                d.cliente_nome = $(document).find('#cliente_nome').val();
                d._token = "{{ csrf_token() }}";
            },
            "dataSrc": function ( json ) {
                json.data = parserDataJson(json.data);
                return json.data;
            }
        },
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
        "columns": [
            { "data": "raiz_cnpj" },
            { "data": "valor_limite" },
            { "data": "data_credito" },
            { "data": "usuario_cadastrou_alterou" },
            { "data": "editar" },
            { "data": "deletar" }
        ],
        "columnDefs": [
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "text_date", targets: "tb_date" },
            {
                'targets': 'td_acao',
                'class': 'td_acao',
                'width': '5px',
                "orderable": false
            },
        ],
        "order": [[ 0, 'asc' ]]
    }).on('draw', function () {
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

    $(document).ready( function () {

        $(document).find("#form_filter").find("#bt-search").on("click", function(){
            showModalClientes($(this).data("route"), "Lista de Clientes");
        });

        $(document).find("#cliente_nome").autocomplete(optionsAutoCompleteCliente());
        
        $(document).find("#btn-filterform").on("click", function(){
            table_filters.draw();
        });
        $(document).find("#btn-create").on("click", function(){
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

    function showModalClientes(url, title){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#cliente_searsh_show").find('tbody').find("tr").off("click");
                        $(document).find("#cliente_searsh_show").find('tbody').find("tr").on("click", function(){
                            returnDadosCliente($(this));
                        });
                    });
                });
            }
        });
    }

    function returnDadosCliente($dados){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente_searsh_show").modal("hide");
        $(document).find("#cliente_nome").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
        table_filters.draw();
    }
    
    function optionsAutoCompleteCliente(){
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
                $(document).find("#cliente_nome").val(ui.item.label);
                return false;
            }
        };
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
                createModal('modal_cliente_limite_edit_delete', title, body, modal_class);
                var modal = $("#modal_cliente_limite_edit_delete");
            }
        });
    }

    function showModalCreate(){
        $.ajax({
            url: '{{ route('cliente.limite.modal.adicionar') }}',
            method: 'GET',
            success: function(body){
            	var title = 'Cadastro de {{ CustomView::programaName() }}';
    			createModal('modal_cliente_limite', title, body, '');
            }
        });
    }
    
    function createBtnEdit($url, $dados){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"\" data-title_modal=\"Editar\" class=\"bt-edit\" title=\"Editar\"></a>";
        return $html;
    }

    function createBtnDelete($url, $dados){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"\" data-title_modal=\"Excluir\" class=\"bt-delete\" title=\"Excluir\"></a>";
        return $html;
    }

    function filterAjax(data_form){
        table_filters.draw();
    }
@endsection
