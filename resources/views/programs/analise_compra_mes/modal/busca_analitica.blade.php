@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped table-not-edit table-not-view" id="table-filters-analitic-index">
                <thead>
                    <tr>
                        <th rowspan="2" class="tb_largura_15">Estabelecimento</th>
                        <th rowspan="2" class="tb_largura_15">Marca</th>
                        <th rowspan="2" class="tb_largura_15">Linha</th>
                        <th rowspan="2" class="tb_largura_15">Grupo</th>
                        <th rowspan="2" class="tb_largura_15">Código</th>
                        <th rowspan="2" class="tb_largura_15">Nome</th>
                        <th rowspan="2" class="tb_number">Estoque</th>
                        <th rowspan="2" class="tb_number">Compras</th>
                        <th colspan="3" class="tb_number">@if(isset($vendas)) {{ $vendas }} Meses @endif</th>
                        <th rowspan="2" class="tb_number">Necessidade @if(isset($estoque)) {{ $estoque }} Meses @endif</th>
                    </tr>
                    <tr>
                        <th id="vendas" class="tb_number">Vendas</th>
                        <th id="remessas" class="tb_number">Remessas</th>
                        <th id="mediavendas" class="tb_number">Média</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dados as $dado)
                        <tr>
                            <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['estabelecimento'] }}">{{ $dado['estabelecimento'] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['marca'] }}">{{ $dado['marca'] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['linha'] }}">{{ $dado['linha'] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['grupo'] }}">{{ $dado['grupo'] }}</div></div></td>
                            <td>{{ $dado['codigo'] }}</td>
                            <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['nome'] }}">{{ $dado['nome'] }}</div></div></td>
                            <td>  
                                <a href="#" class="modal-estoque" data-title='ANALITÍCO DE ESTOQUE' data-route="{{ route('produto.view') }}" data-id="{{ $dado['codigo'] }}" >{{ ($dado['estoque'] != '0') ? parserValor($dado['estoque']) : '' }}</a>
                            </td>
                            <td>
                                <a href="#" class="modal-compras" data-title='ANALITÍCO DE COMPRAS' data-route="{{ route('analise_compras_mes.modal.compras') }}" data-filter="{{ $dado['filter'] }}" >{{ ($dado['compras'] != '0') ? parserValor($dado['compras']) : '' }}</a>
                            </td>
                            <td>
                                <a href="#" class="modal-vendas" data-title='ANALITÍCO DE VENDAS' data-route="{{ route('analise_compras_mes.modal.vendas') }}" data-filter="{{ $dado['filter'] }}">{{ ($dado['vendas']) ?  parserValor($dado['vendas']) : '' }}</a>
                            </td>
                            <td>
                                <a href="#" class="modal-remessas" data-title='ANALITÍCO DE REMESSAS' data-route="{{ route('analise_compras_mes.modal.remessas') }}" data-filter="{{ $dado['filter'] }}">{{ ($dado['remessas']) ?  parserValor($dado['remessas']) : '' }}</a>
                            </td>
                            <td>{{ ($dado['media'] != '0') ? parserValor($dado['media']) : ''}}</td>
                            <td>{{ ($dado['necessidade'] != '0') ? parserValor($dado['necessidade']) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Total</td>
                    <td>{{ (!empty($totalEstoque)) ? parserValor($totalEstoque) : '' }}</td>
                    <td>{{ (!empty($totalCompra)) ? parserValor($totalCompra) : '' }}</td>
                    <td>{{ (!empty($totalVenda)) ? parserValor($totalVenda) : '' }}</td>
                    <td>{{ (!empty($totalRemessa)) ? parserValor($totalRemessa) : '' }}</td>
                    <td>{{ (!empty($totalMedia)) ? parserValor($totalMedia) : ''}}</td>
                    <td>{{ (!empty($totalNecessidade)) ? parserValor($totalNecessidade) : '' }}</td>
                </tfoot>  
            </table>
        </div>
</div>
<script>
    $(document).ready( function () {

        table_filters_analitic = $('#table-filters-analitic-index')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "autoWidth": false,
        "orderMulti": false,
        "pageLength": 20,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '',
                footer: true,
                customize: function( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('row c[r^="C"]', sheet).attr( 's', '2' );
                },
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            if(column === 6 || column === 7 || column === 8 || column === 9 || column === 10 || column === 11){
                                if(data != ''){
                                    numero = data.replace('.','').replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = '';
                                }
                            }
                            return data;
                        },
                        footer: function(data) {
                            data = $('<p>' + data + '</p>').text();
                            return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                        }
                    }
                },
            },
        ],
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
                "class": "tb_number", 
                "targets": "tb_number"
            },
            {
                "width" : "5%",
                "targets" : "tb_number"
            },
            {
                "width" : "15%",
                "targets" : "tb_largura_15"
            },
        ]
        });

        table_filters_analitic.on('draw', function (event) {
            $(document).find(".modal-estoque").off("click");
            $(document).find(".modal-estoque").on("click", function(event){
                event.stopPropagation();
                showModalDetalheAnaliticoEStoque($(this));
            });
            $(document).find(".modal-compras").off("click");
            $(document).find(".modal-compras").on("click", function(event){
                event.stopPropagation();
                showModalComprasAnalitico($(this));
            });
            $(document).find(".modal-vendas").off("click");
            $(document).find(".modal-vendas").on("click", function(event){
                event.stopPropagation();
                showModalVendasAnalitico($(this));
            });
            $(document).find(".modal-remessas").off("click");
            $(document).find(".modal-remessas").on("click", function(event){
                event.stopPropagation();
                showModalRemessas($(this));
            });
        });

        table_filters_analitic.draw();
    });

    function showModalDetalheAnaliticoEStoque($this){
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
                createModal("model_produto_view_analitico_estoque", title, body, 'modal-lg');
                var modal = $(document).find("#model_produto_view_analitico_estoque");
                modal.find('.content-view-cliente').find('.btn-view-estoque').off("click");
                modal.find('.content-view-cliente').find('.btn-view-estoque').on('click', function(){
                    showModalPecaPecaAnaliticoEStoque($(this));
                });
            }
        });
    }
    function showModalPecaPecaAnaliticoEStoque($this){
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
                createModal("model_pecapeca_view_analitico_estoque", title, body, 'modal-lg');
                var modal = $(document).find("#model_pecapeca_view_analitico_estoque");
                modal.find('#table_pedidos').find('.btn-view-pecas-pedidos').off("click");
                modal.find('#table_pedidos').find('.btn-view-pecas-pedidos').on('click', function(){
                    showModalPecaPecaPedidoAnaliticoEStoque($(this));
                });
                $("#model_pecapeca_view_analitico_estoque").off('hidden.bs.modal');
                $("#model_pecapeca_view_analitico_estoque").on('hidden.bs.modal', function (e) {
                    $(".modal-backdrop").css("z-index", 9);
                    $("#model_pecapeca_view_analitico_estoque").remove();
                });
            }
        });
    }
    function showModalPecaPecaPedidoAnaliticoEStoque($this){
        var url = $($this).data("url");
        var codigo = $($this).data("codigo");
        var estabel = $($this).data("estabel");
        var codigo_item = $($this).data("codigo_item");
        $('[data-toggle="popover"]').popover('hide');
        $('[data-toggle="tooltip"]').tooltip('hide');
        $("#model_pecapeca_pedido_view_analitico_estoque").modal("toggle");
        $("#model_pecapeca_pedido_view_analitico_estoque").find('.modal-title').html($($this).data('title'));
        $("#model_pecapeca_pedido_view_analitico_estoque").off('shown.bs.modal');
        $("#model_pecapeca_pedido_view_analitico_estoque").on('shown.bs.modal', function (event) {
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
        $("#model_pecapeca_pedido_view_analitico_estoque").off('hidden.bs.modal');
        $("#model_pecapeca_pedido_view_analitico_estoque").on('hidden.bs.modal', function (e) {
        $("#model_pecapeca_pedido_view_analitico_estoque").find('.modal-body').html('');
        $("#model_pecapeca_pedido_view_analitico_estoque").find('.modal-title').html('');
        });
    }
    function showModalComprasAnalitico($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters: filter},
            method: 'POST',
            success: function(body){
                createModal("model_analitico_view_analitico_compras", title, body, 'modal-lg');
            }
        });
    }
    function showModalVendasAnalitico($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var codigo = $($this).data('codigo');
        var estabelecimento_prods = $($this).data('estabelecimento_prods');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters : filter, codigos : codigo, estabelecimento_prod : estabelecimento_prods},
            method: 'POST',
            success: function(body){
                createModal("model_analitico_view_vendas_analitico", title, body, 'modal-lg');
            }
        });
    }

    function showModalRemessas($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var codigo = $($this).data('codigo');
        var estabelecimento_prods = $($this).data('estabelecimento_prods');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters : filter, codigos : codigo, estabelecimento_prod : estabelecimento_prods},
            method: 'POST',
            success: function(body){
                createModal("model_analitico_view_remessas_analitico", title, body, 'modal-lg');
            }
        });
    }

</script>
@endsection        
