@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped table-not-edit table-not-view" id="table-filters-historico_compras">
                <thead>
                    <tr>
                        <th>Estabelecimento</th>
                        <th>Pedido</th>
                        <th class="sort-date">Data <br/> de alteração</th>
                        <th class="sort-date">Previsão <br/>de Entrega</th>
                        <th class="tb_number">Quantidade <br/>Comprada</th>
                        <th class="tb_number">Quantidade <br/>Recebida</th>
                        <th class="tb_number">Diferença</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dados as $value)
                    <tr>
                        <td style="width: 15%"><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $value['estabelecimento'] }}">{{ $value['estabelecimento'] }}</div></div></td>
                        <td>
                            <a href='#' onclick="showPedidosDetalhes('{{ $value['pedido_id'] }}')"> {{ $value['pedido'] }}</a>
                        </td>
                        <td>{{ $value['data_alteracao'] }}</td>
                        <td>{{ $value['previsao_entrega'] }}</td>
                        <td>{{ $value['quantidade_comprada'] }}</td>
                        <td>{{ $value['quantidade_recebida'] }}</td>
                        <td>{{ $value['saldo'] }}</td>
                        <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["status"] }}">{{ $value["status"] }}</div></div></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
</div>
<script>
$('[data-toggle="tooltip"]').tooltip();

setTimeout(function(){
    $height = $(".modal-body").height() - 130;
    $.fn.dataTable.moment('DD/MM/YYYY');
    var table_filters_analitic = $('#table-filters-historico_compras')
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
        "pagingType": "full_numbers",
        "columnDefs": [
            {
                "class": "tb_number", 
                "targets": "tb_number"
            },
            { "class": "tb_date", targets: "sort-date" }
        ]
});
$('[data-toggle="tooltip"]').tooltip();
}, 100);

function showPedidosDetalhes(id){
    $.ajax({
        url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id: id,
        },
        success: function(body){
            createModal("AnaliseProdutoCompraDetalhe", "Detalhe do Pedido Compras", body, 'modal-lg');
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
