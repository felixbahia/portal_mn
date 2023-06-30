@extends('layouts.app')
@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento_filtro', 'class' => 'form-control', 'placeholder' => 'Estabelecimento']) }}
        </div>
        <div class="col-lg-4">
            <div class="input-group">
                {{ Form::text('cliente_nome', '', ['id' => 'cliente_nome', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Cliente']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-lg-2">
            {{ Form::select('status', $status, '', ['class' => 'form-control', 'placeholder' => 'Todos'])}}
        </div>
    </div>
    <div class="content-buttons row">
        <div class="col-xs-12 col-sm-6">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="row">
                <div class="col-6"><b>Total de pedidos:</b> <span id='total_pedidos'></span></div>
                <div class="col-6"><b>Valor total de pedidos:</b> <span id='total_valor'></span></div>
            </div>
        </div>
    </div>
</form>

@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped table-filter-aprovacao table-not-edit table-not-view" id="table-filters-aprovacao" style="width:100%">
        <thead>
            <tr>
                <th rowspan="2" class="th-reprove"></th>
                <th rowspan="2" class='th-estabel'>Estabel</th>
                <th rowspan="2" class='th-aprovador'></th>
                <th rowspan="2">Cliente</th>
                <th rowspan="2" class='tb_date th-data'>Data</th>
                <th rowspan="2" class="tb_number th-cod-pedido">Cod</th>
                <th rowspan="2" class="tb_number th-valor">Val</th>
                <th rowspan="2" class='th-cond-pag'>Cond Pag</th>
                <th rowspan="2" class='th-tipo_pedido'>T P</th>
                <th rowspan="2" class='th-vendedor'>Vend</th>
                <th colspan="3" class='th-criterios-titulo' style="text-align: center;">Critérios</th>
                <th rowspan="2" class="th-aprove"></th>
            </tr>
            <tr>
                <th class="th-criterio">Crédito</th>
                <th class="th-criterio">Preços </th>
                <th class="th-retaguarda">Retaguarda</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready(function(){
        
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });
        filterAjax($("#form_filter").serialize());
        setInterval(function(){
            filterAjax($("#form_filter").serialize());
        }, 180000);

        abreviar();

        $(window).on('resize', function(){
            abreviar();

        });
		$(document).find("#bt-search-cliente-busca").off("click");
		$(document).find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
			showModalCliente($(this).data("route"));
            return false;
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
    });

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
                $(document).find('[data-toggle="tooltip"], .tooltip').tooltip("hide");
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
                            $(document).find('[data-toggle="tooltip"], .tooltip').tooltip("hide");
                        });
                    });
                });
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

    function abreviar(){

        if (window.innerWidth < 1450){

            $(document).find('th.th-reprove').html('');
            $(document).find('th.th-reprove').width(50);
            $(document).find('th.th-aprove').html('');
            $(document).find('th.th-aprove').width(50);
            $(document).find('th.th-estabel').html('Estabel.');
            $(document).find('th.th-aprovador').html('Aprova');
            $(document).find('th.th-cod-pedido').html('Ped.');
            $(document).find('th.th-valor').html('Val.');
            $(document).find('th.th-vendedor').html('Vend.');
            table_filters.draw();
        }
        else{
            $(document).find('th.th-reprove').html('');
            $(document).find('th.th-aprove').html('');
            $(document).find('th.th-reprove').width(58);
            $(document).find('th.th-aprove').width(58);
            $(document).find('th.th-estabel').html('Estabelecimento');
            $(document).find('th.th-aprovador').html('Aprovador');
            $(document).find('th.th-cod-pedido').html('Pedido');
            $(document).find('th.th-valor').html('Valor');
            $(document).find('th.th-vendedor').html('Vendedor');
            table_filters.draw();
        }

    }
    table_filters = [];
    table_filters = $('#table-filters-aprovacao').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": -1,
        "paging": false,
        "orderMulti": false,
        "language": {
            "decimal":        ",",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
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
        "columnDefs": [
            {
                "targets": ['th-criterio', 'th-retaguarda', 'th-aprove', 'th-reprove'],
                "orderable": false
            },
            {
                "targets": ['th-criterio', 'th-retaguarda'],
                "width": "10px",
                "className": "text-center"
            },
            {
                "targets": ['th-estabel', 'th-cod-pedido', 'th-valor'],
                "width": "10px"
            },
            {
                "targets": ['th-tipo_pedido'],
                "width": "10px"
            },
            {
                "targets": ['th-cond-pag', 'th-aprovador'],
                "width": "100px"
            },
            {
                "targets": ['th-data'],
                "width": "85px"
            },
            { "class": "tb_number", targets: "tb_number" },
            { "class": "tb_date", type: 'html-date', targets: "tb_date" },
        ],
        "order": [[ 4, 'asc' ],[ 5, 'asc' ]]
    });

    function createBtAprove(obj){

        if (obj.mostrar_botao_aprovar){
            var html = "<div class=\"bt-aprove\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Aprovar\" onclick=\"aprovarPedido('"+obj.pedido+"')\"></div>";
        }
        else{
            var html = '';
        }

        return html;
    }

    function createBtAproveProjeto(obj){

        if (obj.mostrar_botao_aprovar){
            var html = "<div class=\"bt-aprove\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Aprovar\" onclick=\"aprovarProjeto('"+obj.pedido+"')\"></div>";
        }
        else{
            var html = '';
        }

        return html;
    }

    function createBtReprove(obj){
        
        if (obj.mostrar_botao_reprovar){
            var html = "<div class=\"bt-reprove\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Reprovar\" onclick=\"recusarPedido('"+obj.id+"')\"></div>";
        }
        else{
            var html = '';
        }

        return html;
    }

    function createBtReproveProjeto(obj){
        
        if (obj.mostrar_botao_reprovar){
            var html = "<div class=\"bt-reprove\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Reprovar\" onclick=\"recusarProjeto('"+obj.projeto+"')\"></div>";
        }
        else{
            var html = '';
        }

        return html;
    }

    function createBtCredito(obj){

        if (obj.criterio_credito=="Não"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'> <a href='#' class=\"bt-criterio-credito falha-validacao\" title=\"Crédito\" onclick=\"modalDetalhes('"+obj.id+"')\"><div><i class='fa fa-times error-icon'  aria-hidden='true'></i></div></a></div></div>";
        }
        else if (obj.criterio_credito=="Sim"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'> <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>";
        }
        else if (obj.criterio_credito=="Aprovado"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Aprovado por " + obj.aprovador_credito + "'><i class='fa fa-check check-icon-aprovado' aria-hidden='true'></i></div></div>";
        }

        return html;
    }

    function createBtCreditoProjeto(obj){

        if (obj.criterio_credito=="Não"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'> <a href='#' class=\"bt-criterio-credito falha-validacao\" title=\"Crédito\" onclick=\"modalDetalhesProjeto('"+obj.id+"')\"><div><i class='fa fa-times error-icon'  aria-hidden='true'></i></div></a></div></div>";
        }
        else if (obj.criterio_credito=="Sim"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'> <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>";
        }
        else if (obj.criterio_credito=="Aprovado"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Aprovado por " + obj.aprovador_credito + "'><i class='fa fa-check check-icon-aprovado' aria-hidden='true'></i></div></div>";
        }

        return html;
    }
    function createBtCondicaoPagamento(obj){
        if (obj.criterio_condicao_pagamento=="Não"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'> <a href='#' class=\"bt-criterio-condicaopagamento falha-validacao\" title=\"Condição de Pagamento\" onclick=\"modalDetalhes('"+obj.id+"')\"><div><i class='fa fa-times error-icon'  aria-hidden='true'></i></div></a></div></div>";
        }
        else if (obj.criterio_condicao_pagamento=="Sim"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'> <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>";
        }
        else if (obj.criterio_condicao_pagamento=="Aprovado"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Aprovado'> <i class='fa fa-check check-icon-aprovado' aria-hidden='true'></i></div></div>";
        }

        return html;
    }
    function createBtPreco(obj){
        
        if (obj.criterio_preco=="Não"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'> <a href='#' class=\"bt-criterio-preco falha-validacao\" title=\"Preço\" onclick=\"modalDetalhes('"+obj.id+"')\"><div><i class='fa fa-times error-icon'  aria-hidden='true'></i></div></a></div></div>";
        }
        else if (obj.criterio_preco=="Sim"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'> <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>";
        }
        else if (obj.criterio_preco=="Aprovado"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Aprovado por " + obj.aprovador_preco + "'> <i class='fa fa-check check-icon-aprovado' aria-hidden='true'></i></div></div>";
        }

        return html;
    }

    function createBtPrecoProjeto(obj){
        
        if (obj.criterio_preco=="Não"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'> <a href='#' class=\"bt-criterio-preco falha-validacao\" title=\"Preço\" onclick=\"modalDetalhesProjeto('"+obj.id+"')\"><div><i class='fa fa-times error-icon'  aria-hidden='true'></i></div></a></div></div>";
        }
        else if (obj.criterio_preco=="Sim"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'> <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>";
        }
        else if (obj.criterio_preco=="Aprovado"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Aprovado por " + obj.aprovador_preco + "'> <i class='fa fa-check check-icon-aprovado' aria-hidden='true'></i></div></div>";
        }

        return html;
    }

    function createBtRetaguarda(obj){
        if (obj.retaguarda=="Sim"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'> <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>";
        }else{
            var html = '';
            if (obj.retaguarda1=="Não"){
                html += "<a href='#' class=\"bt-criterio-preco falha-validacao\" data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK' onclick=\"modalDetalhes('"+obj.id+"')\"><i class='fa fa-times error-icon'  aria-hidden='true'></i></a>";
            }
            else if (obj.retaguarda1=="Aprovado"){
                html += "<i class='fa fa-check check-icon-aprovado' aria-hidden='true' data-toggle='tooltip' data-html='true' title='' data-original-title='Aprovado por " + obj.user_retaguarda1 + "'></i>";
            }

            if (obj.retaguarda2=="Não"){
                html += "&nbsp;&nbsp;<a href='#' class=\"bt-criterio-preco falha-validacao\" data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK' onclick=\"modalDetalhes('"+obj.id+"')\"><i class='fa fa-times error-icon'  aria-hidden='true'></i></a>";
            }
            else if (obj.retaguarda2=="Aprovado"){
                html += "&nbsp;&nbsp;<i data-toggle='tooltip' data-html='true' title='' data-original-title='Aprovado por " + obj.user_retaguarda2 + "' class='fa fa-check check-icon-aprovado' aria-hidden='true'></i>";
            }
        }
        return html;
    }

    function createBtRetaguardaProjeto(obj){
        
        if (obj.retaguarda=="Não"){
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'> <a href='#' class=\"bt-criterio-preco falha-validacao\" title=\"Retaguarda\" onclick=\"modalDetalhesProjeto('"+obj.id+"')\"><div><i class='fa fa-times error-icon'  aria-hidden='true'></i></div></a></div></div>";
        }
        else{
            var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'> <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>";
        }

        return html;
    }

    function createBtPedido(obj){

        var html = "<a href='#' class=\"bt-view_pedido\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Pedido\" onclick=\"abrirPedido('"+obj.pedido+"')\">"+obj.pedido+"</a>";

        return html;
    }

    function createBtProjeto(obj){

        var html = "<a href='#' class=\"bt-view_pedido\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Projeto\" onclick=\"abrirProjeto('"+obj.pedido+"')\">"+obj.pedido+"</a>";

        return html;
    }

    function abrirPedido($id){
        $.ajax({
            url: '{{ route('pedido_portal.detalhes') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                pedido_id: $id 
            },
            success: function (data){
                createModal('detalhes', 'Detalhes do Pedido', data, 'modal-lg');
            }
        });
    }
    function abrirProjeto($id){
        $.ajax({
            url: '{{ route('lancamento_projeto.modal.view') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id_projeto: $id 
            },
            success: function (data){
                createModal('detalhes', 'Detalhes do Projeto', data, 'modal-lg');
            }
        });
    }
    function filterAjax(data_form){
        var $return;
        var $form = $("#form_filter");
        table_filters.clear().draw();
        $(document).find('#total_pedidos').html('');
        $(document).find('#total_valor').html('');
        $form.find('.error-message').remove();
        $(document).find('[data-toggle="tooltip"], .tooltip').tooltip("hide");
        $.ajax({
            url: "{{ route('aprovacao_pedido.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                table_filters.clear().draw();
                $(document).find('[data-toggle="tooltip"], .tooltip').tooltip("hide");
                if(callback.status != 'success'){
                    alert(callback.message);
                }else{
                    var data = callback.response.pedidos;
                    if(data.length > 0){
                        var fields_filter = [];
                        for(var field in data){
                            if(data[field].tipo == 'pedido'){
                                var temp_field = [
                                    createBtReprove(data[field]),
                                    data[field].estabelecimento,
                                    data[field].status,
                                    data[field].cliente,
                                    data[field].data,
                                    createBtPedido(data[field]),
                                    data[field].valor,
                                    data[field].condicao_pagamento,
                                    data[field].tipo_pedido,
                                    data[field].vendedor,
                                    createBtCredito(data[field]),
                                    createBtPreco(data[field]),
                                    createBtRetaguarda(data[field]),
                                    createBtAprove(data[field]),
                                ];
                            }else{
                                var temp_field = [
                                    createBtReproveProjeto(data[field]),
                                    data[field].estabelecimento,
                                    data[field].status,
                                    data[field].cliente,
                                    data[field].data,
                                    createBtProjeto(data[field]),
                                    data[field].valor,
                                    data[field].condicao_pagamento,
                                    data[field].tipo_pedido,
                                    data[field].vendedor,
                                    createBtCreditoProjeto(data[field]),
                                    createBtPrecoProjeto(data[field]),
                                    createBtRetaguardaProjeto(data[field]),
                                    createBtAproveProjeto(data[field]),
                                ];
                            }
                            
                            fields_filter.push(temp_field);
                        }
                        table_filters.rows.add(fields_filter).draw();
                    }
                    var total = callback.response.total;
                    $(document).find('#total_pedidos').html(total.pedidos);
                    $(document).find('#total_valor').html(total.valor);
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    if(typeof data != 'undefined'){
                        $.each(data, function(index, el) {
                            $form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                            $form.find('input[name="'+index+'"]').eq(0).addClass('error');
                        });
                        $form.find('input.error').eq(0).focus();
                    }else{
                        message('Atenção', callback.responseJSON.message);
                    }
                }
            }
        }).always(function() {
            hide_loader();
        });
    }

    function aprovarPedido($id){
        $.ajax({
            url: '{{ route("aprovacao_pedido.aprova_pedido")}}',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            success: function(callback){
                if(callback.status === 'success'){
                    $(document).find('#credito-modal').modal('hide');
                    filterAjax($("#form_filter").serialize());
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON;
                    message('Atenção', data.message);
                }
            }
        });
    }


    function retaguardaPedido($id){
        $.ajax({
            url: '{{ route("aprovacao_pedido.retaguarda")}}',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            success: function(callback){
                if(callback.status === 'success'){
                    $(document).find('#credito-modal').modal('hide');
                    filterAjax($("#form_filter").serialize());
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON;
                    message('Atenção', data.message);
                }
            }
        });
    }

    function aprovarProjeto($id){
        $.ajax({
            url: "{{ route('lancamento_projeto.aprovacao') }}", 
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}', 
                id: $id
            },
            method: 'POST',
            async: false,
            success: function(callback){
                $(document).find('#credito-modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
                var dados = callback.responseJSON;
            }
        });
    }

    function recusarPedido($id){
        $.ajax({
            url: '{{ route('aprovacao_pedido.modal.recusa_pedido') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}', 
                id: $id
            },
            success: function(data){
                createModal("recusa-pedido-modal", "Recusar Pedido", data, '')
                ajaxForm('#recusa-pedido-modal');
            }
        })
        
    }

    function recusarProjeto($id){
        $.ajax({
            url: '{{ route('lancamento_projeto.modal.recusar') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}', 
                id_projeto: $id
            },
            success: function(data){
                createModal("recusa-projeto-modal", "Recusar Projeto", data, '')
                ajaxForm('#recusa-projeto-modal');
            }
        })
        
    }

    function ajaxForm($modal){
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
                    $(document).find('#credito-modal').modal('hide');
                    filterAjax($("#form_filter").serialize());
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
                $(document).find('[data-toggle="tooltip"], .tooltip').tooltip("hide");
                createModal("credito-modal", "Detalhes do Crédito", data, 'modal-lg');
            }
        });
    }

    function modalDetalhesProjeto($id){
        
        $.ajax({
            url: '{{ route('lancamento_projeto.modal') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                projeto_id: $id
            },
            success: function(data){
                $(document).find('[data-toggle="tooltip"], .tooltip').tooltip("hide");
                createModal("credito-modal", "Detalhes do Crédito", data, 'modal-lg');
            }
        });
    }
    
@endsection