@extends('layouts.page-dialog')

@section('content')
    <div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped table-not-edit table-not-view" id="table-filters-produtos">
                <thead>
                    <tr>
                        <th>Marca</th>
                        <th>Linha</th>
                        <th>Grupo</th>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>Estoque</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dados as $dado)
                    <tr>
                        <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['marca'] }}">{{ $dado['marca'] }}</div></div></td>
                        <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['linha'] }}">{{ $dado['linha'] }}</div></div></td>
                        <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['grupo'] }}">{{ $dado['grupo'] }}</div></div></td>
                        <td>{{ $dado['codigo'] }}</td>
                        <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['nome'] }}">{{ $dado['nome'] }}</div></div></td>
                        <td><a href="#" data-route="{{ route('produto.view') }}" data-id="{{ $dado['codigo'] }}" class="bt-view-analitico-estoque bt-view" data-toggle="popover" data-trigger='hover' title="Estoque por empresa" data-content="{{ $dado['popover'] }}" data-title='Descrição de Estoque'>{{ parserValor($dado['estoque']) }}</a></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Total</td>
                    <td>{{ parserValor($totalEStoque) }}</td>
                </tfoot> 
            </table>
        </div>
    </div>    
<script>
    $(document).ready( function () {
        table_filters_analitic_estoque = $('#table-filters-produtos')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": true,
            "orderMulti": false,
            "pageLength": 20,
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
            "columnDefs": [
                {
                    "class": "tb_number", 
                    "targets": [5]
                },
            ],
            "drawCallback": function(settings) {
                $('[data-toggle="popover"]').popover({
                    container: 'body',
                    html: true,
                    show: true,
                    template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
                });
                $('[data-toggle="popover"]').on('show.bs.popover', function () {
                    var $this = $(this);
                    $('.popover').not($this).each(function(){
                        $("[aria-describedby='"+$(this).attr("id")+"']").popover('hide');
                    });
                    $("body").on("keyup", function(e){
                        if(e.keyCode == 27){
                            $($this).popover('hide');
                        }
                    });
                });
            },
        });    
        table_filters_analitic_estoque.on('draw', function (event) {
            $(document).find(".bt-view-analitico-estoque").off("click");
            $(document).find(".bt-view-analitico-estoque").on("click", function(event){
                event.stopPropagation();
                showModalDetalhe($(this));
            });
        });
        table_filters_analitic_estoque.draw();
    });
    function showModalDetalhe($this){
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
    function createBodyPopOver($this){
        var $return = "";
        $.each($this,function(index, el) {
            $return += "<p>"+this+"</p>";
        });
        return $return;
    }

</script>
@endsection        
