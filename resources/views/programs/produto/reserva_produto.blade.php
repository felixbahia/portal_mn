@extends('layouts.page-dialog')
   

@section('content')
    <div class="row_title_pecapeca">
        <div>
            <label>Estabelecimento:</label>
            <span>{{ $dados["estabelecimento"] }}</span>
        </div>
        <div>
            <label>Código Produto:</label>
            <span>{{ $dados["fields"]["codigo_item"] }}</span>
        </div>
        <div>
            <label>Unidade:</label>
            <span>{{ $dados["unidade"] }}</span>
        </div>
        <div>
            <label>Número do pedido:</label>
            <span>{{ $dados["fields"]["codigo"] }}</span>
        </div>
    </div>
    <div class="row_table_pecapeca">
        <table class="table table-striped" id="table_pedidos_pecas">
            <thead>
                <tr>
                    <th>Volume</th>
                    <th>Data de entrada</th>
                    <th>Documento de entrada</th>
                    <th>Quantidade</th>
                    <th>Localização</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dados["peca_peca"] as $value)
                    <tr>
                        <td class="number_format">
                            <div>
                                <div data-toggle="tooltip" data-html="true" title="{{ $value["volume"] }}">
                                    {{ $value["volume"] }}
                                </div>
                            </div>
                        </td>
                        <td>{{ $value["data_entrada"] }}</td>
                        <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["documento_entrada"] }}">{{ $value["documento_entrada"] }}</div></div></td>
                        <td class="number_format"><div><div data-toggle="tooltip" data-html="true" title="{{ $value["quantidade"] }}" >{{ $value["quantidade"] }}</div></div></td>
                        <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["localizacao"] }}">{{ $value["localizacao"] }}</div></div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <script>
        $(document).find('[data-toggle="tooltip"]').tooltip({placement:'right'});

        var table_lancamentos = $("#table_pedidos_pecas").DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": -1,
            "scrollY":"400px",
            "scrollCollapse": true,
            "language": {
                "decimal":        ".",
                "emptyTable":     "Nenhum pedido encontrado",
                "infoPostFix":    "",
                "thousands":      ",",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum pedido encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { type: 'date-uk', targets: 1 }
            ],
            "order": [[ 1, 'asc' ]]
        });
    </script>
@endsection