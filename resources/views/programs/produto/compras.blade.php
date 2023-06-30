@extends('layouts.page-dialog')

@section('content')
    <div class="row_title_pecapeca">
        <div>
            <label>Estabelecimento:</label>
            <span>{{ $dados["estabelecimento"] }}</span>
        </div>
        <div>
            <label>Código Produto:</label>
            <span>{{ $dados["codigo"] }}</span>
        </div>
        <div>
            <label>Total de Compra:</label>
            <span>{{ $dados["total_compra"] }}</span>
        </div>
    </div>
    <div class="row_title_pecapeca">
        <div>
            <label>Unidade:</label>
            <span>{{ $dados["unidade"] }}</span>
        </div>
    </div>
    <div class="row_table_pecapeca">
        <table class="table table-striped" id="table_compras">
            <thead>
                <tr>
                    <th class="tb_number">PCMN</th>
                    <th class="sort-date">Data Compra</th>
                    <th class="tb_number">Proforma</th>
                    <th class="tb_number">Nota</th>
                    <th class="sort-date">Previsão chegada</th>
                    <th class="tb_number">Quantidade</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dados["compras"] as $value)
                    <tr>
                        <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["cod_pedido"] }}">{{ $value["cod_pedido"] }}</div></div></td>
                        <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["data_compra"] }}">{{ $value["data_compra"] }}</div></div></td>
                        <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["proforma"] }}">{{ $value["proforma"] }}</div></div></td>
                        <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["nota"] }}">{{ $value["nota"] }}</div></div></td>
                        <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["data_previsao"] }}">{{ $value["data_previsao"] }}</div></div></td>
                        <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["quantidade"] }}">{{ $value["quantidade"] }}</div></div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <script>
        $('[data-toggle="tooltip"]').tooltip();
        
        var table_compras = $("#table_compras").DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": -1,
            "scrollY":"400px",
            "scrollCollapse": true,
            "language": {
                "decimal":        ".",
                "emptyTable":     "Nenhuma compra encontrada",
                "infoPostFix":    "",
                "thousands":      ",",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhuma compra encontrada",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 1, 'asc' ]]
        });
    </script>
@endsection