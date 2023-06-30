@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-filters-dialog_pedidos">
        <thead>
            <tr>
                <th rowspan="2">Estabelecimento</th>
                <th rowspan="2">Pedido</th>
                <th rowspan="2">Proforma</th>
                <th rowspan="2">Fornecedor</th>
                <th rowspan="2">Previsão <br/>de Recebimento</th>
                <th rowspan="2">Data Recebimento</th>
                <th colspan="2">Quantidade</th>
                <th colspan="2">Valor</th>
            </tr>
            <tr>
                <th>Comprada</th>
                <th>Saldo</th>
                <th>Total</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $value)
            <tr>
                <td>
                    {{ $value["estabelecimento"] }}
                </td>
                <td class="tb_number">
                    <a href="#" class="modal-pedido" data-title="DETALHES DO PEDIDO: {{ $value["pedido"] }}" data-route='{{ route("pedidos_compras.pedidos_abertos.dialog") }}' data-id='{{ $value["id"] }}'>{{ $value['pedido'] }}</a>
                </td>
                <td>{{ $value["proforma"] }}</td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["fornecedor"] }}">{{ $value["fornecedor"] }}</div></div></td>
                <td class="tb_date">{{ $value["previsao_recebimento"] }}</td>
                <td class="tb_date">{{ $value["data_recebimento"] }}</td>
                <td class="tb_number">{{ $value["quantidade_compra"] }}</td>
                <td class="tb_number">{{ $value["saldo"] }}</td>
                <td class="tb_number">{{ $value["valor_compra"] }}</td>
                <td class="tb_number">{{ $value["valor"] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class='text-right'>Total:</td>
                <td class='tb_number'>{{ $total['pedido_quantidade']}}</td>
                <td class='tb_number' colspan="5">{{$total['quantidade_compra']}}</td>
                <td class='tb_number'>{{ $total['saldo'] }}</td>
                <td class='tb_number'>{{  $total['valor_compra']}}</td>
                <td class='tb_number'>{{ $total['valor']}}</td>
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "date_format", targets: "tb_date" }
            ],
    });    

    table_filters_dialog_pedidos.on('draw', function () {
        $(document).find(".modal-pedido").off("click");
        $(document).find(".modal-pedido").on("click", function(event){
            event.stopPropagation();
            showModalPedidos($(this));
        });
    });
    table_filters_dialog_pedidos.draw();
});

function showModalPedidos($this){
        var url = $($this).data("route");
        var title = $($this).data('title');
        var id = $($this).data('id');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", id: id},
            method: 'POST',
            success: function(body){
                createModal("modal_show_pedidos", title, body, 'modal-lg');
            }
        });
    }
</script>
@endSection