@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped table-not-edit table-not-view" id="table-filters-analitic">
                <thead>
                    <tr>
                        <th>Estabelecimento</th>
                        <th>PCMN</th>
                        <th>Proforma</th>
                        <th>Nota</th>
                        <th class="sort-date" style="width: 10%">Previsão de entrega</th>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dados as $dado)
                    <tr>
                        <td style="width: 15%"><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['estabelecimento'] }}">{{ $dado['estabelecimento'] }}</div></div></td>
                        <td>
                            <a href='#' onclick="showPedidosDetalhes('{{ $dado['id'] }}')"> {{ $dado['PCMN'] }}</a>
                        </td>
                        <td>{{ $dado['proforma'] }}</td>
                        <td>{{ $dado['nota'] }}</td>
                        <td>{{ $dado['previsao'] }}</td>
                        <td>{{ $dado['codigo'] }}</td>
                        <td style="width: 30%"><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['nome'] }}">{{ $dado['nome'] }}</div></div></td>
                        <td>{{ $dado['compras'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Total</td>
                    <td>{{ parserValor($totalCompras) }}</td>
                </tfoot> 
            </table>
        </div>
</div>
<script>
$('[data-toggle="tooltip"]').tooltip();

setTimeout(function(){
    $height = $(".modal-body").height() - 130;
    $.fn.dataTable.moment('DD/MM/YYYY');
    var table_filters_analitic = $('#table-filters-analitic')
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
                "targets": [1,7,3,2]
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
            unidade: '',
            data: ''
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
