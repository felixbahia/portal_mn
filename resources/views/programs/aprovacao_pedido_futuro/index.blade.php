@extends('layouts.app')
@section('content-filter')
<form action="#" name="form_filter" id="form_filter_pedido" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
                {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento_filtro', 'class' => 'form-control', 'placeholder' => 'Estabelecimento']) }}
            </div>
            <div class="col-lg-1">
                {{ Form::text('pedido', '', ['id' => 'pedido', 'class' => 'form-control', 'placeholder' => 'Pedido']) }}
            </div>
            <div class="col-lg-1">
                {{ Form::text('numero_pcmn', '', ['id' => 'numero_pcmn', 'class' => 'form-control', 'placeholder' => 'PCMN']) }}
            </div>
            <div class="col-sm-4">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="nome_cliente" id="nome_cliente" value="{{ CustomView::retornaClientePadraoNome() }}" placeholder="Nome / Razão Social" maxlength="250" />
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            @if (count($representantes) > 1)
            <div class="form-group col-lg-2">
                {{ Form::select('representantes', $representantes, '', ['class' => 'form-control', 'placeholder' => 'Todos', 'id' => 'representantes'])}}
            </div>
            @endif
            <div class="form-group col-lg-2">
                {{ Form::select('quinzenas', $quinzenas, '', ['class' => 'form-control', 'id' => 'quinzenas'])}}
            </div>
        </div>
        <div class="row">
            <div class="col-lg-2">
                <input type="text" name="linha" id="linha" value="" placeholder="Linha" maxlength="250" />
            </div>
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
    <table class="table table-striped table-filter-aprovacao table-not-edit table-not-view" id="table-filters-aprovacao" style="width:100%">
        <thead>
            <tr>
                <th class="th-reprove"></th>
                <th class='th-estabel'>Estabel.</th>
                <th class="cliente">Cliente</th>
                <th class="tb_number th-cod-pedido">Pedido</th>
                <th class="tb_number th-cod-pedido">PCMN</th>
                <th class="tb_number th-valor">Val.</th>
                <th class='th-cond-pag'>Cond. Pag.</th>
                <th class='th-vendedor'>Vend.</th>
                <th class='data_prevista'>Data prevista</th>
                <th class='icone'>Estoque</th>
                <th class='icone'>Compra</th>
                <th class='editar'></th>
                <th class='deletar'></th>
                <th class="th-aprove"></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready(function(){
        $("#linha").autocomplete(optionsAutoComplete("linha"));
        $("#btn-filterform").on("click", function(){
            table_filters.draw();
        });

		$(document).find("#bt-search-cliente-busca").off("click");
		$(document).find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
			showModalCliente($(this).data("route"));
            return false;
        });

        $(document).find("#nome_cliente").autocomplete(optionsAutoCompleteCliente());
        $(document).find(".btn-clear").on("click", function(){
            $form = $(this).parents('form');
            $.ajax({
                url: '{{ route('cliente.apagaClientePadrao') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(){
                    $form.find('input').not('[class^=btn-]').not('[name=_token]').val('');
                    $form.find('select').each(function(){
                        $(this).val($(this).find('option').eq(0).val());
                    });
                }
            });
        });
    });

    function optionsAutoComplete($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.draw();
                }, 100);
            }
        };
    }
    function showModalCliente(url){
		var title = "Busca de Clientes";
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
				$(document).find('#cliente_searsh_show').remove();
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(event){
                            returnDadosCliente($(this), event);
                        });
                    });
                });
            }
        });
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
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
                // $(document).find("#codigo_cliente").val(ui.item.value);
                $(document).find("#nome_cliente").val(ui.item.label);
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

    table_filters = $('#table-filters-aprovacao')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "processing": true,
        "serverSide": true,
        "orderMulti": false,
        "autowidth": false,
        "ajax": {
            "url": "{{ route('aprovacao_pedido_futuro.filtro') }}",
            "type": "POST",
            "data": function ( d ) {
                d.estabelecimento = $(document).find('#estabelecimento_filtro').val();
                d.nome_cliente = $(document).find('#nome_cliente').val();
                d.pedido = $(document).find('#pedido').val();
                @if (count($representantes) > 1)d.representantes = $(document).find('#representantes').val();
                @endif
                d.quinzenas = $(document).find('#quinzenas').val();
                d.numero_pcmn = $(document).find('#numero_pcmn').val();
                d.linha_produto = $(document).find('#linha').val();
                d._token = "{{ csrf_token() }}";
            },
            "dataSrc": function ( json ) {
                json.data = parserDataJson(json.data);
                $('[data-toggle="popover"]').off('show.bs.popover');
                $('[data-toggle="popover"]').popover('hide');
                return json.data;
            },
            "statusCode": {
                409: function() {
                    window.location.reload();
                },
                419: function() {
                    window.location.reload();
                }
            }
        },
        "drawCallback": function(settings) {
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
            { "data": "reprovar" },
            { "data": "estabelecimento" },
            { "data": "cliente" },
            { "data": "pedido" },
            { "data": "numero_pcmn" },
            { "data": "valor" },
            { "data": "condicao_pagamento" },
            { "data": "vendedor" },
            { "data": "data_previsao_entrega" },
            { "data": "estoque" },
            { "data": "compras" },
            { "data": "editar" },
            { "data": "deletar" },
            { "data": "aprovar" }
        ],
        "columnDefs": [
            {
                "targets": ['editar', 'th-aprove', 'th-reprove', 'deletar'],
                "orderable": false
            },
            { 
                "targets": "tb_number",
                "className": "tb_number", 
                "type": "num-fmt", 
            },
            {
                'targets': ["icone", "data_prevista"],
                "className": "text-center",
                "width": "10px"
            },
            {
                "targets": "cliente",
                "width": '20%'
            }
        ],
        "order": [[ 2, 'asc' ]]
    });

    function parserDataJson(data){
        var $return = [];
        $.each(data, function(index, el) {
            var temp = {
                "reprovar": createBtReprove(this),
                "estabelecimento": this.estabelecimento,
                "cliente": this.cliente,
                "pedido": createBtPedido(this),
                "numero_pcmn": createLinkPedidoCompra(this),
                "valor": this.valor,
                "condicao_pagamento": this.condicao_pagamento,
                "vendedor": this.vendedor,
                "data_previsao_entrega": this.data_previsao_entrega,
                "estoque": btnEstoque(this),
                "compras": trueOuFalse(this.estoque_futuro),
                "editar": createBtEdit(this),
                "deletar": createBtCancelar(this),
                "aprovar": createBtAprove(this)
            };
            $return.push(temp);
        });
        return $return;
    }

    function createBtAprove(obj){
        if (obj.mostrar_botao_aprovar == 'success'){
            var html = "<div class=\"bt-aprove\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Aprovar\" onclick=\"aprovarPedido('"+obj.pedido+"')\"></div>";
        }
        else{
            var html = '';
        }
        return html;
    }

    function createBtReprove(obj){
        var html = "<div class=\"bt-reprove\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Reprovar\" onclick=\"recusarPedido('"+obj.id+"')\"></div>";
        return html;
    }
    
    function createBtPedido(obj){
        var html = "<a href='#' class=\"bt-view_pedido\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Pedido\" onclick=\"abrirPedido('"+obj.pedido+"')\">"+obj.pedido+"</a>";
        return html;
    }

    function createBtCancelar($this){
        html = "<a href=\"#\" class=\"bt-delete\" data-toggle='tooltip' data-html='true' title='Cancelar' onclick=\"cancelarPedido("+$this.id+", "+$this.pedido_id+", "+$this.estabelecimento_cod+", '"+$this.cliente_nome+"')\"></a>";
        return html;
    }

    function aprovarPedido($id){
        $.ajax({
            url: '{{ route("aprovacao_pedido_futuro.aprovar")}}',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id,
                aprovacao_pedido_futuro: true
            },
            success: function(callback){
                table_filters.draw();
            },
            error: function(data){
                message('Atenção', data.responseJSON.error.msg.user);
            }
        });
    }

    function recusarPedido($id){
        $.ajax({
            url: '{{ route('aprovacao_pedido_futuro.modal.recusa_pedido') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}', 
                id: $id
            },
            success: function(data){
                createModal("recusa-pedido-modal", "Recusar Pedido", data, '')
                ajaxFormRecusar('#recusa-pedido-modal');
            }
        })
        
    }

    function ajaxFormRecusar($modal){
        $($modal).find('[type="submit"]').on("click", function(event){
            event.stopPropagation();
            var form = $(this).parents('form');
            var form_data = form.serialize();
            var url = form.attr("action");
            $.ajax({
                url: url,
                dataType: 'json',
                data: form_data,
                method: 'POST',
                success: function(data){
                    $($modal).modal('hide');
                    table_filters.draw();
                },
                error: function(data){
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }
            });
        });
    }
    
    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function modalDetalhes($id){
        $.ajax({
            url: '{{ route('parametros_aprovacao.modal') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                pedido_id: $id
            },
            success: function(data){
                createModal("credito-modal", "Detalhes do Crédito", data, 'modal-lg');
            }
        });
    }
    function btnEstoque($this){
        $bool = $this.estoque;
        if ($bool === 'success'){
            return "<i class='fa fa-check check-icon' aria-hidden='true'></i>";
        }
        else if ($bool === 'error'){
            return "<i class='fa fa-times error-icon'  aria-hidden='true'></i>";
        }
        else if ($bool === 'warning'){
            return "<a href='#' class=\"bt-view_pedido\" onclick=\"abrirPedido('"+$this.pedido+"')\"><div data-toggle=\"tooltip\" data-trigger='hover' title=\"Pedido atendido parcialmente. Verifique os estoques\"><i class='fa fa-exclamation-triangle warning-icon' aria-hidden='true'></i></div></a>";
        }
    }

    function trueOuFalse($bool){
        if ($bool === true){
            return "<i class='fa fa-check check-icon' aria-hidden='true'></i>";
        }
        else if ($bool === false){
            return "<i class='fa fa-times error-icon'  aria-hidden='true'></i>";
        }
    }

    function createBtEdit($this){

        html = "<a href=\"#\" class=\"bt-edit\" data-toggle='tooltip' data-html='true' title='Editar' onclick=\"showModalEdicao("+$this.pedido+")\"></a>";
        return html;
    }

    function showModalEdicao($id = null) {
        $.ajax({
            url: '{{ route('pedido_portal.modal.edicao_futuro') }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(data){
                createModal("modal_pedido_edit", "Edição de pedido: "+$id, data, 'modal-lg');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }
    
    function abrirPedido($id){
        $.ajax({
            url: '{{ route('pedido_portal.detalhes_futuro') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                pedido_id: $id 
            },
            success: function (data){
                createModal('detalhes', 'Detalhes do Pedido n° ' + $id, data, 'modal-lg');
            }
        });
    }
    
    function cancelarPedido($id, $pedido_id, $estabelecimento, $cliente_nome){
        var title = 'Cancelar Pedido';
        $.ajax({
            url: '{{ route('aprovacao_pedido_futuro.modal.deletar') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}", 
                id: $id,
                pedido_id: $pedido_id,
                estabelecimento: $estabelecimento,
                cliente_nome: $cliente_nome
            },
            success: function(body){
                createModal('modal_delete', title, body, '');
                var modal = $("#modal_delete");
            }
        });
    }

    function returnDadosCliente($dados, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#nome_cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
        $.ajax({
            url: '{{ route('cliente.salvaClientePadrao') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                codcad: $dados.find("td").eq(0).text()
            }
        });
	}
    function createLinkPedidoCompra($this){
        var html = "";
        $.each($this.numero_pcmn, function(key, value){
            if(value !== ""){
                if(html != ''){
                    html += " / ";
                }
                html += "<a href='#' onclick=\"showPedidoCompraDetalhes('" 
                + value
                + "')\">" + key + "</a>";
            }
        });
        return html;
    }
    function showPedidoCompraDetalhes(id){
        $.ajax({
            url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: id
            },
            success: function(body){
                createModal("pedidos_compras", "Detalhe do Pedido Compras", body, 'modal-lg');
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    message = '';
                    $.each(data, function(index, el) {
                        message += el+'<br />';
                    });
                    message("Atenção", message);
                }
            }
    
        });
    }
    
@endsection
