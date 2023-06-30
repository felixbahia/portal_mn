@extends('layouts.page-dialog')

@section('content')

<form action="#" onsubmit="return false" id='adiciona_item'>
    
    <div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped table-filter-lancamentos" id="table-filters-modal">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Banco</th>
                        <th class='tb_number'>Agência</th>
                        <th class='tb_number'>Conta</th>
                        <th class='tb_number'>Número</th>
                        <th class='tb_decimal tb_number'>Valor</th>
                        <th class='tb_decimal tb_number'>Valor pago</th>
                        <th class='tb_decimal tb_number'>Saldo restante</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($itens as $item)
                    <tr>
                        <td>{{ $item->cheque->tipo }}</td>
                        <td>{{ $item->cheque->banco }}</td>
                        <td>{{ $item->cheque->agencia }}</td>
                        <td>{{ $item->cheque->conta }}</td>
                        <td>{{ $item->cheque->numero_cheque }}</td>
                        <td>{{ parserValor($item->cheque->valor) }}</td>
                        <td>{{ parserValor($item->valor_pago) }}</td>
                        <td>{{ parserValor($item->cheque->valor - $item->valor_pago) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</form>

<div class="rodape fixed-bottom text-right">
        <div class='float-right mr-3'><b>VALOR DO PEDIDO:</b> R$ {{ $total }}</div><br>
        <div class='float-right mr-3 mb-2'><b>VALOR FATURADO:</b> R$ <span id='valor_faturado'>{{ $valor_faturado }}</span></div>
</div>
<script>

    $(document).ready(function(){
        table_filters_lancamentos.draw();
    })

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
        'columnDefs': [
            {
                'targets': "tb_decimal",
                'type': 'num-fmt',
                'width': '70px'
            },
            {
                "class": "tb_number",
                'targets': "tb_number",
            }
        ]
    };

    table_filters_lancamentos = $('#table-filters-modal').DataTable(table_filters_pedidos_options);

</script>
@endsection