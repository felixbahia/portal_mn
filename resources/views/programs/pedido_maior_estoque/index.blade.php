@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
                {!! Form::select("estabelecimento", $estabelecimentos, '', ["id" => "estabelecimento", "class"=>"form-control", "placeholder"=>"Estabelecimento"]) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('codigo', '', ['id' => 'codigo', 'class' => 'form-control', 'placeholder' => 'Código Produto']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('descricao', '', ['id' => 'descricao', 'class' => 'form-control', 'placeholder' => 'Produto Descrição']) !!}
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
    <table class="table table-striped table-not-edit table-not-view" id="table-filters-pedido_maior_estoque">
        <thead>
            <tr>
                <th rowspan="2">Estabelecimento</th> 
                <th rowspan="2">Código</th>
                <th rowspan="2">Descrição</th>
                <th rowspan="2">UN</th>
                <th rowspan="2" class="tb_number">Estoque</th>
                <th rowspan="2" class="tb_number">Estoque<br/>Em Trânsito</th>
                <th colspan="2">Compras</th>
                <th rowspan="2" class="tb_number">Pedidos<br/>Abertos</th>
            </tr>
            <tr>
                <th class="tb_number">Em Aberto</th>
                <th class="tb_number">Necessidade</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

@endsection
@section('script-footer')
$(document).ready( function () {
    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filtro();
    });
    $(document).find("#descricao").autocomplete(optionsAutoComplete("nome"));
});

var table_filters_pedido_maior_estoque = $(document).find('#table-filters-pedido_maior_estoque').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "processing": true,
        "orderMulti": false,
        "scrollCollapse": false,
        "autoWidth": false,
        "dom": 'Bfrtip',
        "buttons": [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Pedido com Saldo Maior que Estoque',
                footer: true,
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            if(column > 3){
                                if(data.indexOf(".") !== -1){
                                    numero = data.replace(/\./g,'').replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : '';
                                }
                            }
                            return data;
                        }
                    }
                },
            },
        ],
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum produto inserido",
            "infoPostFix":    "",
            "loadingRecords": "Carregando...",
            "processing":     "Processando...",
            "zeroRecords":    "Nenhum  produto inserido",
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
            "targets": "tb_number"
            },
        ]
    }
);

table_filters_pedido_maior_estoque.on('draw', function () {
    $('[data-toggle="tooltip"]').tooltip();
    $(document).find(".bt-modal").off("click");
    $(document).find(".bt-modal").on("click", function(event){
        event.stopPropagation();
        abrirModal($(this));
    });
    $(document).find(".bt-modal-estoque").off("click");
    $(document).find(".bt-modal-estoque").on("click", function(event){
        event.stopPropagation();
        showModalEstoque($(this));
    });
});

function filtro(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('pedido_maior_estoque.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            for (var fields in data.response){
                temp_array = [
                    data.response[fields].estabelecimento,
                    data.response[fields].produto_codigo,
                    "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response[fields].produto_descricao + "''>" + data.response[fields].produto_descricao + "</div></div>",
                    data.response[fields].unidade,
                    criarLinkEstoque(data.response[fields]),
                    data.response[fields].estoque_em_transito,
                    criarLinkCompras(data.response[fields])+ ' ' + criarBtHistoricoCompras(data.response[fields]),
                    data.response[fields].necessidade_compras,
                    criarLinkPedidosAbertos(data.response[fields])+ ' ' + criarBtHistoricoPedidos(data.response[fields]),
                ];
                produtos.push(temp_array)
            }
            table_filters_pedido_maior_estoque.rows.add(produtos).draw();   
            
        },
        error: function(data){
            var errors = data.responseJSON.error;
            for(var field in errors){
                showErrorsInputs(form, field, errors[field])
            }
        }
    });
}

function filterClear(){
    table_filters_pedido_maior_estoque.clear().draw();
}

function criarLinkEstoque($this){
    var html = "<a href=\"#\" data-route=\"{{ route('produto.view') }}\" data-id=\""+$this.produto_codigo+"\" data-title='"+$this.produto_codigo+" - "+$this.produto_descricao+"' class='bt-modal-estoque'>"+$this.estoque +"</a>"
    return html;
}

function criarLinkCompras($this){
    var html = "<a href=\"#\" data-route=\"{{ route('pedido_maior_estoque.modal.compras') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE COMPRAS "+$this.produto_codigo+" - "+$this.produto_descricao+"' class='bt-modal'>"+$this.compras_aberto +"</a>"
    return html;
}

function criarLinkPedidosAbertos($this){
    var html = "<a href=\"#\" data-route=\"{{ route('pedido_maior_estoque.modal.pedidos') }}\" data-filter=\""+$this.filters+"\" data-title='PEDIDOS ABERTOS "+$this.produto_codigo+" - "+$this.produto_descricao+"' class='bt-modal'>"+$this.pedidos_aberto +"</a>"
    return html;
}

function criarBtHistoricoCompras($this){
    var html = "<a href=\"#\" class=\"bt-historico\" data-toggle='tooltip' data-html='true' title='Histórico Compras' onclick=\"abrirIconeHistoricoCompras('"+$this.filters+"', '{{ route('pedido_maior_estoque.modal.historico_compras') }}', 'HISTÓRICO DE COMPRAS "+$this.produto_codigo+" - "+$this.produto_descricao+" - ÚLTIMOS 180 DIAS');\"></a>";
    return html;
}

function criarBtHistoricoPedidos($this){
    var html = "<a href=\"#\" class=\"bt-historico\" data-toggle='tooltip' data-html='true' title='Histórico Pedidos' onclick=\"abrirIconeHistoricoPedidos('"+$this.filters+"', '{{ route('pedido_maior_estoque.modal.historico_pedidos') }}', 'HISTÓRICO DE PEDIDOS "+$this.produto_codigo+" - "+$this.produto_descricao+" - ÚLTIMOS 30 DIAS');\"></a>";
    return html;
}

function showErrorsInputs(form, input, message){
    var $input = $(form).find("input[name='"+input+"']");
    $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
    $input.addClass('error-input');
}

function optionsAutoComplete($name){
    return {
        source: function (request, response) {
            request.name = $name;
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3
    };
}

function abrirModal($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    xhr = $.ajax({
        url: url,
        data: {
            _token: "{{ csrf_token() }}", filters: filter},
        method: 'POST',
        success: function(body){
            createModal("modal_produtos", title, body, 'modal-lg');
        }
    });
}

function abrirIconeHistoricoCompras($filtro, $url, $title){
    $.ajax({
        url: $url,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            filters: $filtro
        },
        success: function(body){
            createModal("modal_historico_compras", $title, body, 'modal-lg');
        }
    });
}

function abrirIconeHistoricoPedidos($filtro, $url, $title){
    $.ajax({
        url: $url,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            filters: $filtro
        },
        success: function(body){
            createModal("modal_pedidos_abertos", $title, body, 'modal-lg');
        }
    });
}

function showModalEstoque($this){
    var url = $($this).data("route");
    var id = $($this).data("id");
    $('[data-toggle="popover"]').popover('hide');
    $('[data-toggle="tooltip"]').tooltip('hide');
    var title = "Visualizar Produto - " + $($this).data('title');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", codprd: id},
        method: 'POST',
        success: function(body){
            createModal("model_produto_view", title, body, 'modal-lg');
            var modal = $(document).find("#model_produto_view");
            modal.find('.content-view-cliente').find('.btn-view-estoque').off("click");
            modal.find('.content-view-cliente').find('.btn-view-estoque').on('click', function(){
                showModalPecaPeca($(this));
            });
        }
    });
}

function showModalPecaPeca($this){
    var url = $($this).data("url");
    var codigo = $($this).data("codigo");
    var estabel = $($this).data("estabel");
    $('[data-toggle="popover"]').popover('hide');
    $('[data-toggle="tooltip"]').tooltip('hide');
    var title = $($this).data('title');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", codigo: codigo, estabel: estabel},
        method: 'POST',
        success: function(body){
            createModal("model_pecapeca_view", title, body, 'modal-lg');
            var modal = $(document).find("#model_pecapeca_view");
            modal.find('#table_pedidos').find('.btn-view-pecas-pedidos').off("click");
            modal.find('#table_pedidos').find('.btn-view-pecas-pedidos').on('click', function(){
                showModalPecaPecaPedido($(this));
            });
            $("#model_pecapeca_view").off('hidden.bs.modal');
            $("#model_pecapeca_view").on('hidden.bs.modal', function (e) {
                $(".modal-backdrop").css("z-index", 9);
                $("#model_pecapeca_view").remove();
            });
        }
    });
}
function showModalPecaPecaPedido($this){
    var url = $($this).data("url");
    var codigo = $($this).data("codigo");
    var estabel = $($this).data("estabel");
    var codigo_item = $($this).data("codigo_item");
    $('[data-toggle="popover"]').popover('hide');
    $('[data-toggle="tooltip"]').tooltip('hide');
    $("#model_pecapeca_pedido_view").modal("toggle");
    $("#model_pecapeca_pedido_view").find('.modal-title').html($($this).data('title'));
    $("#model_pecapeca_pedido_view").off('shown.bs.modal');
    $("#model_pecapeca_pedido_view").on('shown.bs.modal', function (event) {
        var modal = $(this);
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", codigo: codigo, estabel: estabel, codigo_item: codigo_item},
            method: 'POST',
            success: function(data){
                modal.find('.modal-body').html(data);
            }
        });
    });
    $("#model_pecapeca_pedido_view").off('hidden.bs.modal');
    $("#model_pecapeca_pedido_view").on('hidden.bs.modal', function (e) {
    $("#model_pecapeca_pedido_view").find('.modal-body').html('');
    $("#model_pecapeca_pedido_view").find('.modal-title').html('');
    });
}

@endsection
