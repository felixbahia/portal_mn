@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit" id="table-dialog-compras">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Pedido</th>
                <th>Produto</th>
                <th class="sort-date">Data Compra</th>
                <th class="sort-date">Previsão Entrega</th>
                <th>Fornecedor</th>
                <th class="tb_number_150">Quantidade Comprada</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($retorno as $compra)
                <tr>
                    <td>{{ $compra['estabelecimento'] }}</td>
                    <td><a href='#' class="tb_number" onclick="mostrarPedidoComprasDetalhes('{{$compra['id_nota']}}')">{{ $compra['pedido'] }}</a></td>
                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $compra['produto'] }}'>{{ $compra['produto'] }}</div></div></td>
                    <td class="sort-date">{{ $compra['data_compra'] }}</td>
                    <td class="sort-date">{{ $compra['previsao_entrega'] }}</td>
                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $compra['fornecedor'] }}'>{{ $compra['fornecedor'] }}</div></div></td>
                    <td class="tb_number_150">{{ $compra['quantidade'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<script>
    $(document).ready( function () {
        $(document).find(".bt-aprove").on("click", function(event){
            event.stopPropagation();
            aprovarPremio($(this));
        });

        $(document).find('#table-dialog-compras').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
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
                    'targets': 0,
                    'min-width': '200px'
                },
                { "class": "tb_number_150", type: 'num-fmt', targets: "tb_number_150"},
                { "class": "tb_date",  targets: "sort-date"},
            ],
            "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
        });
    });

    function mostrarPedidoComprasDetalhes(id){
        $.ajax({
            url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
            },
            success: function(body){
                createModal("modal_compras_detalhes", "Detalhe do Pedido Compras", body, 'modal-lg');
                var modal = $("#modal_compras_detalhes");
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
</script>
@endsection