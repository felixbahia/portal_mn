@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento_filtro', 'class' => 'form-control', 'placeholder' => 'Estabelecimento']) }}
        </div>
        @if(!Auth::user()->hasRole('Cliente'))
        <div class="col-lg-4">
            <div class="input-group">
				{{ Form::text('cliente_nome', CustomView::retornaClientePadraoNome(), ['id' => 'cliente_nome', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Cliente']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
        @endif
        <div class="col-lg-1">
            <input type="text" class="form-control" name="pedido" id="pedido" placeholder="Número do pedido">
        </div>
        <div class="col-lg-1">
            <input type="text" class="data-mes data_month" name="data_inicio" id="data_inicio" placeholder="Data Início MM/AAAA" value="{{ date("m/Y") }}" maxlength="20">
        </div>
        <div class="col-lg-1">
            <input type="text" class="data-mes data_month" name="data_fim" id="data_fim" placeholder="Data Fim MM/AAAA" value="{{ date("m/Y") }}" maxlength="20">
        </div>
        <div class="col-lg-2">
            {{ Form::select('status', $status, '', ['id' => 'status', 'class' => 'form-control', 'placeholder' => 'Status']) }}
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
                <th>Estabel</th>
                <th class="tb_number">Pedido</th>
                <th class="tb_number">Nota Fiscal</th>
                <th>Cliente</th>
                <th class="sort-date">Emissão</th>
                <th class="tb_number">Valor</th>
                <th>Condição Pagamento</th>
                <th>Status</th>
                @if((Auth::user()->hasRole('Faturamento') || Auth::user()->hasRole('Faturamento Loja') || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Diretoria') || Auth::user()->hasRole('Diretoria Comercial') || Auth::user()->hasRole('Coordenadora Comercial') || Auth::user()->hasRole('Caixa Interno')))
                <th></th>
                <th></th>
                @endif
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<div class="total_busca"><div id="total_texto_busca"><b>Total:</b> R$ </div><div id="total_valor_busca"></div></div>
@endsection

@section('script-footer')
    $(document).ready( function () {
        $('#pedido').mask('0000000000');

        $('.data_month').mask('00/0000');
        $('.data_month').datepicker({
            language: 'pt-BR',
            format: 'mm/yyyy',
            endDate: new Date(),
            zIndex: 100,
            autoHide: true
        });
        $(document).find("#btn-filterform").on("click", function(){
            getDados($(document).find("#form_filter").serialize());
        });
        $(document).find("#bt-search-cliente-busca").on("click", function(){
            showModalCliente($(this).data("route"), "Lista de Clientes");
        });
        $(document).find("#cliente_nome").autocomplete(optionsAutoCompleteCliente());

        $(".btn-clear").on("click", function(){

            $form = $(this).parents('form');

            $.ajax({
                url: '{{ route('cliente.apagaClientePadrao') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(){
                    $form.find('input, select').not('[class^=btn-]').not('[name=_token]').val('');
                }
            });
            
        });

        $("#form_filter").find(".data-mes").mask("99/9999");
        table_filters.destroy();
        table_filters = $("#table-filters").DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "language": {
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "decimal":        ".",
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
            "autoWidth": false,
            "columnDefs": [
                { "type":  "html" , targets: 1  },
                { "type":  "html" , targets: 2 },
                { "type":  "html" , targets: 3 },
                { "width": "100px", targets: 0 },
                { "width": "200px", targets: 6 },
                { "width": "100px", "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "width": "100px", "class": "tb_date", targets: "sort-date" },
                @if((Auth::user()->hasRole('Faturamento') || Auth::user()->hasRole('Faturamento Loja') || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Diretoria') || Auth::user()->hasRole('Diretoria Comercial') || Auth::user()->hasRole('Coordenadora Comercial') || Auth::user()->hasRole('Caixa Interno')))
                { "width": "1px", "orderable": false, targets: -1},
                @endif
                { "width": "1px", "orderable": false, targets: -1}
            ],
        });
        table_filters.on('draw', function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
    });

    function optionsAutoCompleteCliente(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.bloqueado = false;
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
                $.ajax({
                    url: '{{ route('cliente.salvaClientePadrao') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        codcad: ui.item.value
                    }
                });
                return false;
            }
        };
    }
    function showModalCliente(url, title){
        xhr = $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#cliente_searsh_show").find("tbody").find("tr").off("click");
                        $(document).find("#cliente_searsh_show").find("tbody").find("tr").on("click", function(){
                            returnDados($(this));
                        });
                    });
                    $(document).find("#cliente_searsh_show").find(".bt-selected").on("click", function(){
                        returnDados($(this));
                    });
                });
            }
        });
    }
    function returnDados($dados){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente_searsh_show").modal("hide");
        $("#form_filter").find("#cliente_nome").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $.ajax({
            url: '{{ route('cliente.salvaClientePadrao') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                codcad: $dados.find("td").eq(0).text()
            }
        });
    }
    function createLink($dados, $texto){
        var $html = "";

        if($dados.origem == 'nasajon'){
            $html = "<a href=\"#\" id=\"bt_link\" onclick=\"showItens('"+$dados.id+"', '"+$dados.origem+"', '"+$dados.estabelecimento+"')\">"+$texto+"</a>";
        }
        else{
            $html = "<a href=\"#\" id=\"bt_link\" onclick=\"showItens('"+$dados.pedido_number+"', '"+$dados.origem+"', '"+$dados.estabelecimento+"')\">"+$texto+"</a>";
        }
        return $html;
    }

    function createLinkNota($numero, $nota, $origem){

        if($origem == 'nasajon'){
            var title_modal = 'Detalhes da nota: ' + $numero;
    
            if($numero.length > 0 && $nota.length > 0){
                var html = "<a href=\"#\" data-id=\""+$nota+"\" data-modal=\"modal-lg\" data-title_modal=\""+title_modal+"\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Detalhes no Nasajon\" onclick=\"showModalNota(this);\">"+$numero+"</a>";
            }
            else if($numero.length > 0){
                var html = $numero;
            }
            else{
                var html = ''
            }
        }
        else{
            var html = $numero;
        }

        return html;
    }
    
    function showItens($pedido, $origem, $estabelecimento){
        var title = "Dados do pedido";
        
        if($origem != 'nasajon'){
            title += ": "+$pedido;
        }

        xhr = $.ajax({
            url: "{{ route('pedidos_orcamentos.show') }}",
            data: {
                _token: "{{ csrf_token() }}",
                origem: $origem,
                pedido: $pedido, 
                estabelecimento: $estabelecimento
            },
            method: 'POST',
            success: function(body){
                createModal("itens_pedido", title, body, 'modal-lg');
                var modal = $(document).find("#itens_pedido");
                modal.css("z-index", 11);
                $(".modal-backdrop").css("z-index", 9);
            }
        });
    }

    function showModalNota($this){
        var url = '{{ route('notas_nasajon.modal.exibir') }}';
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id_nota: $id},
            success: function(body){
                createModal('modal_message_edit', title, body, modal_class);
            }
        });
    }
    
    function getDados(data_form){
        table_filters.clear().draw();
        xhr = $.ajax({
            url: "{{ route('pedidos_orcamentos.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                table_filters.clear().draw();
                if(callback.status !== "success"){
                    message("Atenção", callback.message);
                    return false;
                }
                $(document).find(".total_busca").show();
                $(document).find(".total_busca").find("#total_valor_busca").html(callback.total);
                if(callback.data.length > 0){
                    var fields_filter = [];
                    for(var field in callback.data){
                        var temp_field = [
                            callback.data[field].estabelecimento,
                            createLink(callback.data[field], callback.data[field].pedido, callback.data[field].origem),
                            createLinkNota(callback.data[field].nota_fiscal, callback.data[field].nota_fiscal_id, callback.data[field].origem),
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+callback.data[field].cliente_codigo+" - " +callback.data[field].cliente+ "\">"+callback.data[field].cliente+"</div></div>",
                            callback.data[field].emissao,
                            callback.data[field].valor,
                            callback.data[field].condicao_pagamento,
                            callback.data[field].status,
                            @if((Auth::user()->hasRole('Faturamento') || Auth::user()->hasRole('Faturamento Loja') || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Diretoria') || Auth::user()->hasRole('Diretoria Comercial') || Auth::user()->hasRole('Coordenadora Comercial') || Auth::user()->hasRole('Caixa Interno')))
                            createLinkEditar(callback.data[field].id, callback.data[field].cancelar),
                            createLinkDeletar(callback.data[field].id, callback.data[field].cancelar)
                            @endif
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).order([ 0, 'asc' ]).draw().nodes();
                }
            }
        });
    }
    
    @if((Auth::user()->hasRole('Faturamento') || Auth::user()->hasRole('Faturamento Loja') || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Diretoria') || Auth::user()->hasRole('Diretoria Comercial') || Auth::user()->hasRole('Coordenadora Comercial') || Auth::user()->hasRole('Caixa Interno')))
    function createLinkEditar($id, $mostrar_botao){
        var html = '';

        if($mostrar_botao == true){

            html = "<a href=\"#\" class=\"bt-editar\" data-id=\""+$id+"\" data-modal=\"modal-lg\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar transportadora\" onclick=\"showModalEditarTransportadora(this);\"></a>"
        }

        return html;
    }
    function createLinkDeletar($id, $mostrar_botao){
        var html = '';

        if($mostrar_botao == true){

            html = "<a href=\"#\" class=\"bt-delete\" data-id=\""+$id+"\" data-modal=\"modal-lg\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Cancelar pedido\" onclick=\"showModalCancelamento(this);\"></a>"
        }

        return html;
    }

    function cancelarPedido($obj){

        $title = "Atenção";
        $text = "<p>Isso irá cancelar o pedido na base de dados da Nasajon. Deseja continuar?</p>";
        $class = '';
        $name_option_ok = "cancelar_nasajon";
        $class = "dialog_option_deletar";
        $name_option_cancelar = "cancelar";
   
        message_option($title, $text, $class, $name_option_ok, '', $name_option_cancelar, '');

        $(document).off("cancelar_nasajon");
        $(document).on("cancelar_nasajon", function(){
            $.ajax({
                url: "{{ route('pedidos_orcamentos.cancelar') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    pedido: $($obj).data("id"),
                },
                success: function(data){
                    if(data.status == 'success'){
                        getDados($(document).find("#form_filter").serialize());
                    }
                    else{
                        message('Erro', 'Ocorreu uma instabilidade, contate o setor responsavel!');
                    }
                },
                error: function(callback){
                    if(callback.responseJSON.message != ''){
                        message('Erro', callback.responseJSON.message);
                    }
                    else if(Object.values(callback.responseJSON.error).length > 0){
                        message('Erro', Object.values(callback.responseJSON.error).join('<br>'));
                    }

                    console.log(callback.responseJSON);
                }
            });

            $(".tooltip").tooltip("hide");

        });

        $(document).off("cancelar");
        $(document).on("cancelar", function(){
            $(".tooltip").tooltip("hide");
            return null;
        });

    }

    function showModalCancelamento($this){
        var $id = $($this).data("id");
        var title = "Cancelar Pedido";
        $.ajax({
            url: '{{ route('motivo_cancelamento_pedido.modal.cancelamento_pedido_nasajon') }}',
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_motivo_cancelamento_pedido', title, body, "");
                var modal = $("#modal_motivo_cancelamento_pedido");
            }
        });
    }

    function showModalEditarTransportadora($this){
        var $id = $($this).data("id");
        var title = "Editar Transportadora";
        $.ajax({
            url: '{{ route('pedidos_orcamentos.modal.editar_transportadora') }}',
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_editar_transportadora', title, body, "");
                var modal = $("#modal_editar_transportadora");
            }
        });
    }
    @endif

@endsection
