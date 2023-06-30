@extends("layouts.app")
@section("content-filter")
<form action="#" name="form_filter_pedidos_pedidos" id="form_filter_pedidos" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="filtro_colapsado">
        <div class="content-fields">
            <div class="row">
                <div class="form-group col-lg-2 col-xl-2">
                    {{ Form::select("estabelecimento", $estabelecimentos, "", ["id" => "estabelecimento_filtro", "class" => "form-control", "placeholder" => "Estabelecimento"]) }}
                </div>
                <div class="form-group col-lg-4 col-xl-4">
                    <div class="input-group">
                        {{ Form::text("cliente", CustomView::retornaClientePadraoNome(), ["id" => "cliente_filtro", "class" => "form-control input-label", "placeholder" => "Nome do Cliente"]) }}
                        {{ Form::hidden("cliente_id", CustomView::retornaClientePadraoId(), ["id" => "cliente_id_filtro", "class" => "form-control", "placeholder" => "Código do Cliente"]) }}
                        <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                    </div>
                </div>
                <div class="form-group col-lg-2 col-xl-2">
                    {{ Form::select("status", $status_pedido, "todos", ["id" => "status_filtro", "class" => "form-control", "placeholder" => "Status"]) }}
                </div>
                <div class="form-group col-lg-2 col-xl-1">
                    {{ Form::text("data_inicio", date("d/m/Y"), ["id" => "data_inicio", "class" => "form-control data", "placeholder" => "Início do período"]) }}
                </div>
                <div class="form-group col-lg-2 col-xl-1">
                    {{ Form::text("data_fim", date("d/m/Y"), ["id" => "data_fim", "class" => "form-control data", "placeholder" => "Fim do período"]) }}
                </div>
            </div>
            <div class="row">
                @if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === "administrador")
                <div class="form-group col-sm-6 col-xl-2">
                    {{ Form::select("diretor", $dropdown_diretores, "", ["id" => "diretor_filtro", "class" => "form-control", "placeholder" => "Todos os diretores", "onchange" => "retornaGerentesEVendedores($(this).val())" ])}}
                </div>
                <div class="form-group col-sm-6 col-xl-2">
                    {{ Form::select("gerente", $dropdown_gerentes, "", ["id" => "gerente_filtro", "class" => "form-control", "placeholder" => "Todos os gerentes", "onchange" => "retornaVendedores($(this).val())"])}}
                </div>
                @endif
                @if (count($dropdown_usuarios) > 0)
                <div class="form-group col-sm-6 col-xl-2">
                    {{ Form::select("usuario", $dropdown_usuarios, "", ["id" => "usuario_filtro", "class" => "form-control", "placeholder" => "Todos os vendedores"])}}
                </div>
                @endif
                <div class="form-group col-sm-6 col-xl-2">
                    {{ Form::text("id_filtro", "", ["id" => "id_filtro", "class" => "form-control", "placeholder" => "ID do pedido Web"]) }}
                </div>
                <div class="form-group col-sm-6 col-xl-2">
                    {{ Form::text("pedido_gerado", "", ["id" => "pedido_gerado", "class" => "form-control", "placeholder" => "Pedido gerado"]) }}
                </div>
                <div class="form-group col-sm-6 col-xl-2">
                    {{ Form::text("cpf_balcao", "", ["id" => "cpf_balcao", "class" => "form-control", "placeholder" => "Cliente Balcao CPF"]) }}
                </div>
            </div>
        </div>
    </div>
    <div class="filtro-linha">
        <div id="row">
            <div class="col-lg-8" id="filtros-show-param">
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Gerar novo pedido</button>
    </div>
</form>	
@endsection
@section("content")
<div class="content-table">
	    <table class="table table-striped table-filter-pedido-portal table-not-edit table-not-view" id="table-filters-pedidos">
        <thead>
            <tr>
                <th class="tb_number">ID Portal</th>
                <th class="tb_number">Pedido Gerado</th>
                <th class="tb_number">Estab.</th>
                <th class="td_cliente">Cliente</th>
                <th class="tb_date">Data</th>
                <th class="tb_number">Valor</th>
                <th class="td_status">Status</th>
                <th class="td_condicao_pagamento">Condição Pagto.</th>
                @if (count($dropdown_usuarios) > 0)<th class="td_users">Vendedor</th>@endif
                <th class="td_acao"></th>
                <th class="td_acao"></th>
                <th class="td_acao"></th>
                <th class="td_acao"></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section("script-footer")
    table_filters_pedidos_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": 15,
        "processing": true,
        "orderMulti": false,
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
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
                "targets": "tb_date",
                "className": "date_format",
                "width": "70px"
            },
            { "class": "tb_number", type: "num-fmt", targets: "tb_number", width: "70px" },
            {
                "targets": "td_acao",
                "class": "td_acao",
                "width": "5px",
                "orderable": false
            },
            {
                "targets": "td_cliente",
                "width": "250px"
            },
            {
                "targets": "td_condicao_pagamento",
                "width": "150px"
            },
            {
                "targets": "td_users",
                "width": "100px"
            },
            {
                "targets": "td_status",
                "width": "130px"
            },
        ]
    };

    table_filters_pedidos = $("#table-filters-pedidos").DataTable(table_filters_pedidos_options);
    if($(document).width() > 962){
        filterAjax($("#form_filter_pedidos").serialize());
    }
    else{
        escondeBusca();
    }
	$(document).ready( function () {
        $(document).find("#bt-search-cliente-busca").off("click");
        $(document).find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
            showModalClienteBusca($(this).data("route"));
            return false;
        });
        $(document).find("#cliente_filtro").autocomplete(optionsAutoCompleteClienteFiltro());

        $(document).find(".data").mask("00/00/0000");
        $(document).find(".data").datepicker({
            language: "pt-BR",
            format: "dd/mm/yyyy",
            endDate: new Date(),
            zIndex: 100,
            autoHide: true
        });

        $(document).find("#btn-create").on("click", function(){
            showModal();
        });


        $(document).find("#btn-filterform").on("click", function(){
            if($(document).width() > 962){
                filterAjax($(document).find("#form_filter_pedidos").serialize());
            }else{
                if($(document).find(".filtro_colapsado").is(":visible") === true){
                    filterAjax($(document).find("#form_filter_pedidos").serialize());
                }else{
                    mostrarBusca();
                }
            }
        });
        $(document).find(".btn-clear").on("click", function(){
            $form = $(this).parents("form");
            $.ajax({
                url: "{{ route("cliente.apagaClientePadrao") }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(){
                    $form.find("input, select").not("[class^=btn-]").not("[name=_token]").val("");
                }
            });
            
        });
    });
    function escondeBusca(){
        $(document).find(".filtro_colapsado").hide();
        $(document).find(".btn-clear").hide();
        $(document).find(".filtro-linha").show();
    }
    function mostrarBusca(){
        $(document).find(".filtro_colapsado").show();
        $(document).find(".btn-clear").show();
        $(document).find(".filtro-linha").hide();
    }

    function optionsAutoCompleteClienteFiltro(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route("clientes.autocomplete") }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message("Atenção", "Nenhum cliente encontrado");
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#cliente_id_filtro").val(ui.item.value);
                $(document).find("#cliente_filtro").val(ui.item.label);
                $.ajax({
                    url: "{{ route("cliente.salvaClientePadrao") }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        codcad: ui.item.value
                    }
                });
                filterAjax($("#form_filter_pedidos").serialize());
                return false;
            }
        };
    }
    function showModal($id = null, $this = 0) {
        if($this != 0){
            $status_pedido = $($this).data("status_pedido");

            if($status_pedido == "8"){
                var $class = "dialog_option_pedido_futuro";
                var $name_option_ok_pedido_futuro = "ok_pedido_futuro";
                var $name_option_cancelar_pedido_futuro = "cancelar_pedido_futuro";
                
                $(document).off("ok_pedido_futuro");
                $(document).on("ok_pedido_futuro", function(){
                    chamadaModalPeido($id);
                });

                $(document).off("cancelar_pedido_futuro");
                $(document).on("cancelar_pedido_futuro", function(){
                    return null; 
                });

                message_option("Atenção", "Este pedido é programado e se editado perderá toda reserva, você realmente quer fazer isto?", $class, $name_option_ok_pedido_futuro, '', $name_option_cancelar_pedido_futuro, '');
            }else{
                chamadaModalPeido($id);
            }
        }else{
            chamadaModalPeido($id);
        }
        
    }

    function ajaxForm($modal){
        $($modal).find("[type=\"submit\"]").on("click", function(event){
            event.stopPropagation();
            var form = $(this).parents("form");
            var form_data = form.serialize();
            var url = form.attr("action");
            $.ajax({
                url: url,
                dataType: "json",
                data: form_data,
                method: "POST",
                success: function(data){
                    $($modal).modal("hide");
                    filterAjax($("#form_filter_pedidos").serialize());
                    message("Atenção", "Dados salvos com sucesso!");
                },
                error: function(callback){
                    if(callback.status === 422){
                        var errors = callback.responseJSON.errors;
                        form.find(".error-message").remove();
                        for(var field in errors){
                            showErrorsInputs(form, field, errors[field])
                        }
                    }else{
                        window.location.reload();
                    }
                }
            });
        });
    }
    function viewModal($id){
        $.ajax({
            url: "{{ route("pedido_portal.detalhes") }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                pedido_id: $id
            },
            success: function(data){
                $id = "view-pedido";
                $title = "Detalhes do pedido"; 
                $body = data;
                $class = "modal-lg";
                createModal($id, $title, $body, $class);
            }
        });
    }
    function filterAjax(data_form){
        table_filters_pedidos.clear().draw();
        if($(document).width() <= 962){
            escondeBusca();
        }
        $.ajax({
            url: "{{ route("pedido_portal.filter") }}",
            dataType: "json",
            data: data_form,
            method: "POST",
            success: function(data){
                table_filters_pedidos.clear().draw();
                var fields_filter = [];
                for(var field in data){
                    var temp_field = [
                        data[field].id,
                        data[field].pedido_gerado,
                        data[field].estabelecimento,
                        data[field].cliente,
                        data[field].data_pedido,
                        data[field].valor_total,
                        data[field].status_pedido_detalhes,
                        data[field].condicao_pagamento_detalhes,
                        @if (count($dropdown_usuarios) > 0)data[field].usuario_nome,@endif
                        createBtDuplicarPedido(data[field]),
                        createBtViewPedido(data[field]),
                        createBtEditPedido(data[field]),
                        createBtDeletePedido(data[field])
                    ];
                    fields_filter.push(temp_field);
                }
                table_filters_pedidos.rows.add(fields_filter).draw().nodes();
                $(document).find("#table-filters").find(".bt-edit").off("click");
                $(document).find("#table-filters").find(".bt-edit").on("click", function(event){
                    event.stopPropagation();
                });
                $(document).find("#modal_pedido_edit").find("#total_produtos").val(data.valor_total_pedido);
            },
            error: function(callback){
                if(callback.status === 422){
                    if(callback.responseJSON.errors){
                        var errors = callback.responseJSON.errors;
        
                        form.find(".error-message").remove();
                        for(var field in errors){
                            showErrorsInputs(form, field, errors[field])
                        }
                    }else{
                        message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
                    }
                }else{
                    window.location.reload();
                }
            },
            statusCode: {
                409: function() {
                    window.location.reload();
                },
                419: function() {
                    window.location.reload();
                }
            }
        });
    }
    function createBtEditPedido($this){
        var pedidos_nao_digitaveis = [0, 3, 11, 8];
        var html = "";
        if (pedidos_nao_digitaveis.indexOf($this.status_pedido) == -1 && $this.stone === false){
            html = "<a href=\"#\" class=\"bt-edit\" data-toggle=\"tooltip\" data-html=\"true\" data-status_pedido=\""+$this.status_pedido+"\" title=\"Editar\" onclick=\"showModal("+$this.id+", $(this))\"></a>";
        }

        if ($this.status_pedido == 20){
            html = "<a href=\"#\" class=\"bt-edit\" data-toggle=\"tooltip\" data-html=\"true\" data-status_pedido=\""+$this.status_pedido+"\" title=\"Editar\" onclick=\"showModal("+$this.id+", $(this))\"></a>";
        }


        if ($this.status_pedido_codigo === 9 && $this.stone === false && $this.condicao_pagamento_codigo === "4247"){
            html = "<a href=\"#\" class=\"bt-edit\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Editar\" onclick=\"showModal("+$this.id+", $(this))\"></a>";
        }
        return html;
    }
    function createBtDuplicarPedido($this){
        var html = "";
        html = "<a href=\"#\" class=\"bt-duplicar\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Duplicar\" onclick=\"duplicar_pedido("+$this.id+")\"></a>";
        return html;
    }
    function createBtViewPedido($this){
        var pedidos_nao_digitaveis = [1, 5, 7];
        var html = "";
        if (pedidos_nao_digitaveis.indexOf($this.status_pedido) == -1){
            html = "<a href=\"#\" class=\"bt-view\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Visualizar\" onclick=\"viewModal("+$this.id+")\"></a>";
        }
        return html;
    }
    function createBtDeletePedido($this){
        var pedidos_deletaveis = [1, 5, 7, 8, 10, 20];
        var pedidos_deletaveis_pagamento = [9];
        var pedidos_deletaveis_pagamento_antifraude = [11];
        var html = "";
        if (pedidos_deletaveis.indexOf($this.status_pedido) !== -1 || (pedidos_deletaveis_pagamento.indexOf($this.status_pedido) !== -1 && $this.cartao === false)){
            var html = "<a href=\"#\" class=\"bt-delete\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Excluir\" onclick=\"modalExcluirPedido($(this).parents('tr'), "+$this.id+")\"></a>";
        }
        if (pedidos_deletaveis_pagamento.indexOf($this.status_pedido) !== -1 && $this.cartao === true && $this.presencial === false){
            var html = "<a href=\"#\" class=\"bt-delete\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Cancelar Pagamento\" onclick=\"cancelarPagamento($(this).parents('tr'), "+$this.id+")\"></a>";
        }

        if (
			pedidos_deletaveis_pagamento_antifraude.indexOf($this.status_pedido) !== -1 &&
			$this.cartao === true &&
			$this.status_pedido_cartao === 1
		){
            var html = "<a href=\"#\" class=\"bt-delete\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Cancelar Pagamento\" onclick=\"cancelarPagamento($(this).parents('tr'), "+$this.id+")\"></a>";
        }

        if ($this.status_pedido_codigo === 9 && $this.condicao_pagamento_codigo === "4247" || $this.status_pedido_codigo === 9 && $this.presencial === true && $this.cartao === true && $this.pago_presencial_cartao === false){
            var html = "<a href=\"#\" class=\"bt-delete\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Excluir Pedido\" onclick=\"modalExcluirPedido($(this).parents('tr'), "+$this.id+")\"></a>";
        }

        if($this.status_pedido_codigo === 9 && $this.presencial === true && $this.cartao === true && $this.pago_presencial_cartao === true){
            var html = "<a href=\"#\" class=\"bt-criterio-credito falha-validacao\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Corrigir Status\" onclick=\"corrigirStatus("+$this.id+")\"><i class='fa fa-times error-icon' aria-hidden='true'></i></a>";
        }

        @if((Auth::user()->hasRole('Faturamento') || Auth::user()->hasRole('Faturamento Loja') || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Diretoria') || Auth::user()->hasRole('Diretoria Comercial')))
            if ($this.nasajon_cancelar){
                var html = "<a href=\"#\" class=\"bt-delete\" data-toggle=\"tooltip\" data-id=\""+$this.nasajon_id+"\" data-html=\"true\" title=\"Cancelar Pedido\" onclick=\"showModalCancelamentoPedidoNasajon($(this))\"></a>";
            }
        @endif

        return html;
    }
    function corrigirStatus($id){
        $.ajax({
            url: "{{ Route("pedido_portal.corrigir_status_pedido_pago") }}",
            type: "POST",
            data: {
                _token: "{{csrf_token()}}",
                id: $id
            },
            success: function(data){
                if(data.status === 'success'){
                    filterAjax($("#form_filter_pedidos").serialize());
                    message("Alerta", 'Status Corrigido com Sucesso.');
                }
                filterAjax($("#form_filter_pedidos").serialize());
            },
            error: function(callback){
                if(callback.status === 422){
                    if(callback.responseJSON.error){
                        message("Alerta", callback.error);
                    }else{
                        message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
                    }
                }
            }
        });
    }
    function modalExcluirPedido(obj, $id){
        $.ajax({
            url: "{{ Route("pedido_portal.modal_excluir") }}",
            type: "POST",
            data: {
                _token: "{{csrf_token()}}",
                id: $id
            },
            success: function(data){
                createModal("modal_pedido_excluir", "Excluir pedido", data, "");
                $(document).find("#btn-item-delete").on("click", function(){
                    botao = $(this);
                    $.ajax({
                        url: "{{ Route("pedido_portal.excluir") }}",
                        type: "POST",
                        data: {
                            _token: "{{csrf_token()}}",
                            id: $id
                        },
                        success: function(){

                            $(document).find("#modal_item_excluir").modal("hide");
                            table_filters_pedidos.row(obj).remove().draw();
                            botao.parents(".modal").modal("hide");
                        }
                    })
                });
                $(document).find("#btn-cancel-delete").on("click", function(){
                    $(this).parents(".modal").modal("hide");
                });
            }
        });
    }
    function cancelarPagamento(obj, $id){
        $.ajax({
            url: "{{ Route("pedido_portal.modal_excluir") }}",
            type: "POST",
            data: {
                _token: "{{csrf_token()}}",
                id: $id
            },
            success: function(data){
                createModal("modal_pedido_excluir", "Excluir pedido", data, "");
                $(document).find("#btn-item-delete").on("click", function(){
                    botao = $(this);
                    $.ajax({
                        url: "{{ route("acompanhamento_cielo.cacelar_pedido") }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            pedido: $id
                        },
                        success: function(callback){
                            if(callback.status === 'success'){
                                $(document).find("#modal_item_excluir").modal("hide");
                                table_filters_pedidos.row(obj).remove().draw();
                                botao.parents(".modal").modal("hide");
                            }else{
                                message("Atenção", callback.message);
                            }
                        }
                    })
                });
                $(document).find("#btn-cancel-delete").on("click", function(){
                    $(this).parents(".modal").modal("hide");
                });
            }
        });
    }
    function showModalClienteBusca(url){
        var title = "Busca de Clientes";
        $.ajax({
            url: url,
            method: "POST",
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
        $(document).find("#cliente_id_filtro").val($dados.find("td").eq(0).text());
        $(document).find("#cliente_filtro").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
        $.ajax({
            url: "{{ route("cliente.salvaClientePadrao") }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                codcad: $dados.find("td").eq(0).text()
            }
        });
    }
    @if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") === false)
    function retornaGerentesEVendedores($diretor){
        $.ajax({
            url: "{{ route("usuario.gerentes_vendedores") }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                diretor: $diretor
            },
        })
        .done(function(data) {
            $("#gerente_filtro").empty();
            $("#usuario_filtro").empty();
            $("#gerente_filtro").append("<option value=\"\">Todos os gerentes</option>");
            $("#usuario_filtro").append("<option value=\"\">Todos os vendedores</option>");
            for (var fields in data.gerentes){
                $("#gerente_filtro").append("<option value=\""+fields+"\">"+data.gerentes[fields]+"</option>");
            }
            for (var fields in data.vendedores){
                $("#usuario_filtro").append("<option value=\""+fields+"\">"+data.vendedores[fields]+"</option>");
            }
        });
    }
    function retornaVendedores($gerente){
        $.ajax({
            url: "{{ route("usuario.vendedores") }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                gerente: $gerente
            },
        })
        .done(function(data) {
            $("#usuario_filtro").empty();
            $("#usuario_filtro").append("<option value=\"\">Todos os vendedores</option>");
            for (var fields in data.vendedores){
                $("#usuario_filtro").append("<option value=\""+fields+"\">"+data.vendedores[fields]+"</option>");
            }
        });
    }
    @endif
    function duplicar_pedido($pedido){
        var title = "Duplicar Pedido";
        $.ajax({
            url: "{{ route("pedido_portal.duplicar") }}",
            method: "POST",
            data: {
                _token: "{{csrf_token()}}",
                pedido: $pedido
            },
            success: function(body){
                createModal("duplicacao_pedido", title, body, "");
            }
        });
    }
    
    function chamadaModalPeido($id){
        $.ajax({
            url: "{{ route("pedido_portal.formAdd") }}",
            data: {_token: "{{ csrf_token() }}", id: $id},
            method: "POST",
            success: function(body){
                if($(body).find("#nome_cliente").val().length > 0 ){
                    $titulo = "Pedido de venda "+ $id +":  <span id=\"cliente_titulo\">- " +  $(body).find("#nome_cliente").val() + "</span>";
                }
                else{
                    $titulo = "Pedido de venda <span id=\"cliente_titulo\"><>";
                }
                createModal("modal_pedido_edit", $titulo, body, "modal-lg");
            },
            error: function(callback){
                if(callback.status === 422){
                    if(callback.responseJSON.error){
                        message("Alerta", callback.responseJSON.error.msg.user);
                    }else{
                        message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
                    }
                }else{
                    window.location.reload();
                }
            },
            statusCode: {
                409: function() {
                    window.location.reload();
                },
                419: function() {
                    window.location.reload();
                }
            }
        });
    }

    function showModalCancelamentoPedidoNasajon($this){
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
@endsection
