@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-filters-dialog_pedidos">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class="tb_number">Pedido</th>
                <th>Cliente</th>
                @if (isset($quantidade))
                <th class="tb_number">Quantidade</th>
                @else
                <th class="tb_number">Valor</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $value)
            <tr>
                <td>
                    <div><div data-toggle="tooltip" data-html="true" title="{{ $value["estabelecimentos"] }}">{{ $value["estabelecimentos"] }}</div></div>
                </td>
               <td>
                    <a href="#" class="modal-pedidos" data-title="DADOS DO PEDIDO" data-route='{{ route("pedidos_orcamentos.show") }}' data-origem='nasajon' data-pedido='{{ $value["pedido_id"] }}' data-estabelecimento='{{ $value["estabelecimento"] }}'>{{ $value['pedido_numero'] }}</a>
                </td>
               <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["cliente"] }}">{{ $value["cliente"] }}</div></div></td>
               <td class='tb_number'> @if (isset($value['valor'])) {{ parserValor($value['valor']) }} @else {{ parserQtd($value['quantidade']) }} @endif</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class='text-right'>Total:</td>
                <td class='tb_number'>{{ $total['pedido_quantidade'] }}</td>
                <td></td>
                <td class='tb_number'> @if (isset($total['valor_total'])) {{ parserValor($total['valor_total']) }} @else {{ parserQtd($total['quantidade_total']) }} @endif</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
$(document).ready( function () {
    table_filters_dialog_pedidos = $('#table-filters-dialog_pedidos').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 20,
        "autoWidth": false,
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" }
            ],
    });    

    table_filters_dialog_pedidos.on('draw', function () {
        $(document).find(".modal-pedidos").off("click");
        $(document).find(".modal-pedidos").on("click", function(event){
            event.stopPropagation();
            showModalPedido($(this));
        });
    });
    table_filters_dialog_pedidos.draw();
});

function showModalPedido($this){
    var $url = $($this).data("route");
    var $origem = $($this).data("origem");
    var $pedido = $($this).data("pedido");
    var $title = $($this).data("title");
    var $estabelecimento = $($this).data("estabelecimento");

    $.ajax({
        url: $url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", origem: $origem, pedido : $pedido, estabelecimento : $estabelecimento},
        success: function(body){
            createModal("table-itens-pedido-show", $title, body, 'modal-lg');
        }
    });
}
</script>
@endSection