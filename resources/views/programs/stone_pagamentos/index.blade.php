@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="form-group col-lg-2 col-xl-2">   
            {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form form-control', 'placeholder' => 'Estabelecimento']) !!}
        </div>
        <div class="form-group col-lg-3 col-xl-2">
            <div class="input-group">
                {{ Form::text("cliente", '', ["id" => "cliente_filtro", "class" => "form-control input-label", "placeholder" => "Nome do Cliente",]) }}
                {{ Form::hidden("cliente_id", '', ["id" => "cliente_id_filtro", "class" => "form-control", "placeholder" => "Código do Cliente"]) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="form-group col-lg-2 col-xl-2">   
            {!! Form::select('pagamentos', ['sem_pagamentos' => 'Pagamentos a Confirmar','com_pagamentos' => 'Pagamentos Confirmados'], '', ['id' => 'pagamentos', 'class' => 'form form-control', 'placeholder' => 'Todos']) !!}
        </div>
        <div class="form-group col-lg-2 col-xl-2">
            {{ Form::text("numero_pedido", "", ["id" => "numero_pedido", "class" => "form-control", "placeholder" => "Pedido"]) }}
        </div>
        <div class="form-group col-lg-2 col-xl-2">
            {{ Form::text("data_inicio", date("d/m/Y"), ["id" => "data_inicio", "class" => "form-control data", "placeholder" => "Data Inicial"]) }}
        </div>
        <div class="form-group col-lg-2 col-xl-2">
            {{ Form::text("data_fim", date("d/m/Y"), ["id" => "data_fim", "class" => "form-control data", "placeholder" => "Data Final"]) }}
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
    <table class="table table-striped" id="table-filters-pagamentos-stone">
        <thead>
            <tr>
                <th rowspan="2">Estabelecimento</th>
                <th rowspan="2">Pedido</th>
                <th rowspan="2">Nota</th>
                <th rowspan="2">Cliente</th>
                <th rowspan="2" class="sort-date">Data</th>
                <th colspan="3">Valor</th>
                <th rowspan="2">Status Pagamento</th>
                <th rowspan="2">Lançamento</th>
            </tr>
            <tr>
                <th class="tb_number">Pedido</th>
                <th class="tb_number">Transação</th>
                <th class="tb_number">Separação</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')

$(document).ready(function(){$('.data').mask('00/00/0000');
    $(document).find("#bt-search-cliente-busca").off("click");
    $(document).find("#bt-search-cliente-busca").on("click", function(event){
        event.stopPropagation();
        showModalClienteBusca($(this).data("route"));
        return false;
    });
    $(document).find("#cliente_filtro").autocomplete(optionsAutoCompleteClienteFiltro());
    
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        autoHide: true
    });

    $(document).find('#btn-filterform').on('click', function(){
        buscarPedidos();
    });


    $(document).find("#btn-clearform").on("click", function(){
        table_filters_maquinihas.clear().draw();
    });
    setInterval(function(){
        clearFieldIdCliente();
    }
    , 1000);
});

function clearFieldIdCliente(){
    if($(document).find("#cliente_filtro").val() == ''){
        $(document).find("#cliente_id_filtro").val('');
    }
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
            return false;
        }
    };
}

table_filters_maquinihas = $("#table-filters-pagamentos-stone").DataTable({
    "searching": false,
    "lengthChange": false,
    "info": false,
    "scrollX": false,
    "scrollCollapse": true,
    "paging": false,
    "autoWidth": true,
    "language": {
        "emptyTable":     "Nenhum registro encontrado",
        "infoPostFix":    "",
        "thousands":      ".",
        "decimal":        ",",
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
            "class": "tb_number", 
            "type": 'num-fmt', 
            "targets": "tb_number"
        },
        { "targets": [-1, -2], "orderable": false},
        { "class": "tb_date", targets: "sort-date" }
    ],
    "order": [[ 0, 'asc' ]]
    }
);

function buscarPedidos(){
    table_filters_maquinihas.clear().draw();
    form = $(document).find('#form_filter');
    $('label.error-message').remove();

    $.ajax({
        url: '{{ route("stone_pagamentos.filtro") }}',
        dataType: 'json',
        method: 'POST',
        data: form.serialize(),
        success: function(data){

            var response = data.response.retorno;
            var linhas = [];

            for(var field in response){
                
                titulo_linha = [
                    "<div><div data-toggle='tooltip' data-html='true' title='' data-placement='right' data-original-title='" + response[field].estabelecimento + "''>" + response[field].estabelecimento + "</div></div>",
                    createBtnPedido(response[field].pedido),
                    createBtnNota(response[field].nota_id,response[field].nota_numero),
                    "<div><div data-toggle='tooltip' data-html='true' title='' data-placement='right' data-original-title='" + response[field].cliente + "''>" + response[field].cliente + "</div></div>",
                    response[field].data,
                    response[field].valor,
                    response[field].valor_transacao,
                    response[field].valor_faturado,
                    response[field].status,
                    createBtnPagamento(response[field].pedido,response[field].modo_pagamento,response[field].cliente,response[field].id_stone_transacao_pedido,response[field].tipo_abertura,response[field].valor, response[field].valor_faturado, response[field].valor_pago, response[field].valor_restante,response[field].valor_pago_total)
                ];

                linhas.push(titulo_linha);

            }

            table_filters_maquinihas.rows.add(linhas).nodes().draw();

        },
        error: function (data){
            var errors = data.responseJSON.error;
            for(var field in errors){
                showErrorsInputs(form, field, errors[field])
            }
        }
    });
}

function createBtnPagamento($id,$status,$cliente,$id_stone_transacao_pedido,$tipo_abertura,$valor,$valor_faturado,$valo_pago,$valor_restante,$valor_pago_total){
    $html = '';

    if($status == ''){
        $html = "<a href=\"#\" class=\"btn-create\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"lancarPagamento('"+$id_stone_transacao_pedido+"','"+$cliente+"','"+$valor+"','"+$valor_faturado+"','"+$id+"');\">Manual</a>";
    }else if($status == 'automatico'){
        $html = "<a href=\"#\" class=\"bt-view\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"visualizarPagamento('"+$id+"','"+$cliente+"','"+$id_stone_transacao_pedido+"','"+$tipo_abertura+"');\">Automático</a>";
    }else if($status == 'manual'){
        $html = "<a href=\"#\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"editarPagamento('"+$id_stone_transacao_pedido+"','"+$cliente+"','"+$valor+"','"+$valor_faturado+"','"+$id+"');\">Manual</a>";
    }else if($status == 'restante'){
        $html = "<a href=\"#\" class=\"bt-create\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"lancarPagamentoRestante('"+$id_stone_transacao_pedido+"','"+$cliente+"','"+$valor+"','"+$valor_faturado+"','"+$id+"','"+$valo_pago+"','"+$valor_restante+"');\">Manual</a>";
    }else if($status == 'restante_pago_manual'){
        $html = "<a href=\"#\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"editarPagamentoRestante('"+$id_stone_transacao_pedido+"','"+$cliente+"','"+$valor+"','"+$valor_faturado+"','"+$id+"','"+$valor_pago_total+"');\">Manual</a>";
    }

    return $html;
}

function createBtnNota($id_nota,$numero_nota){
    var $html = "<a href=\"#\" class=\"btn-create\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"abrirNota('"+$id_nota+"','"+$numero_nota+"');\">"+$numero_nota+"</a>";
    return $html;
}

function createBtnPedido($id){
    var $html = "<a href=\"#\" class=\"btn-create\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"abrirPedido('"+$id+"');\">"+$id+"</a>";
    return $html;
}

function visualizarPagamento($id,$cliente,$id_stone_transacao_pedido,$tipo_abertura){
    console.log($tipo_abertura);
    $.ajax({
        url: "{{ route("acompanhamento_cielo.modal.venda_presencial") }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            estabelecimento: '',
            id_transacao_stone: $id_stone_transacao_pedido,
            tipo_abertura: $tipo_abertura
        },
        success: function(callback){
            $title = "Visualizar Transação do Pedido "+$id+" - "+$cliente; 
            $body = callback;
            $class = "modal-lg";
            createModal("visualizar-pagamentos", $title, $body, $class);
        }
    });
}

function editarPagamento($id,$cliente,$valor,$valor_faturado,$id_pedido){
    $.ajax({
        url: "{{ route("stone_pagamentos.modal.editar_pagamentos") }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            pedido_id: $id
        },
        success: function(callback){
            $title = "Editar Pagamento Manual no Pedido: "+$id_pedido+" - Cliente: "+$cliente + " - Valor Pedido: "+$valor+ " - Valor Separação: "+$valor_faturado; 
            $body = callback;
            $class = "modal-lg";
            createModal("editar-pagamentos", $title, $body, $class);
        }
    });
}

function editarPagamentoRestante($id,$cliente,$valor,$valor_faturado,$id_pedido,$valor_pago_total){
    $.ajax({
        url: "{{ route("stone_pagamentos.modal.editar_pagamento_restante") }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            pedido_id: $id
        },
        success: function(callback){
            $title = "Editar Pagamento Manual no Pedido: "+$id_pedido+" - Cliente: "+$cliente + " - Valor Pedido: "+$valor+ " - Valor Separação: "+$valor_faturado+ " - Valor Pago: "+$valor_pago_total; 
            $body = callback;
            $class = "modal-lg";
            createModal("editar-pagamentos", $title, $body, $class);
        }
    });
}

function lancarPagamento($id,$cliente,$valor,$valor_faturado,$id_pedido){
    $.ajax({
        url: "{{ route("stone_pagamentos.modal.registrar_pagamentos") }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            pedido_id: $id
        },
        success: function(callback){
            $title = "Associar Pagamento no Pedido: "+$id_pedido+" - Cliente: "+$cliente + " - Valor Pedido: "+$valor+ " - Valor Separação: "+$valor_faturado; 
            $body = callback;
            $class = "modal-lg";
            createModal("registrar-pagamentos", $title, $body, $class);
        }
    });
}

function lancarPagamentoRestante($id,$cliente,$valor,$valor_faturado,$id_pedido,$valor_pago,$valor_restante){
    $.ajax({
        url: "{{ route("stone_pagamentos.modal.registrar_pagamento_restante") }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            pedido_id: $id
        },
        success: function(callback){
            $title = "Associar Pagamento no Pedido: "+$id_pedido+" - Cliente: "+$cliente + " - Valor Pedido: "+$valor+ " - Valor Separação: "+$valor_faturado+ " - Valor pago: "+$valor_pago+ " - Valor restante: "+$valor_restante; 
            $body = callback;
            $class = "modal-lg";
            createModal("registrar-pagamentos", $title, $body, $class);
        }
    });
}

function abrirPedido($id){
    $.ajax({
        url: "{{ route("pedido_portal.detalhes") }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            pedido_id: $id
        },
        success: function(callback){
            $title = "Detalhes do pedido: "+$id; 
            $body = callback;
            $class = "modal-lg";
            createModal("view_pedido", $title, $body, $class);
        }
    });
}

function abrirNota($id, $nota){
    $.ajax({
        url: '{{ route('notas_nasajon.modal.exibir')}}',
        type: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            id_nota: $id,
            link_pedido: true,
            origem: 'NASAJON'
        },
        success: function(body){
            createModal("nota_detalhes", "Detalhes da nota: " + $nota, body, 'modal-lg');
            $(".troca-aba").on("click", function(e){
                e.preventDefault();
                $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
            })

        }
    });
}

function showErrorsInputs(form, input, messagen){
    var $input = $(form).find("input[name='"+input+"']");
    message('Atenção',messagen);
    $input.parent().children().addClass('error-input');
}
@endsection