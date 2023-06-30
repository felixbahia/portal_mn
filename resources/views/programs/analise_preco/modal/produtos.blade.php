@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table" style='float: none;'>
        <table class='table table-striped table-filter-agrupamento table-not-edit' id='table-filter-agrupamento'>
            <thead>
                <tr>
                    <th>Código do Produto</th>
                    <th>Descrição</th>
                    <th class="tb_number">Estoque</th>
                    <th class="tb_number">Valor Ult. Compra</th>
                    <th class="tb_number">Preço Venda</th>
                    <th class="tb_number">Markup Lista</th>
                    <th class="tb_number">Preço Med VD</th>
                </tr>
            </thead>
            <tbody>
                @foreach($produtos as $produto)
                <tr>
                    <td>{{ $produto['codigo_produto'] }}</td>
                    <td>{{ $produto['descricao'] }}</td>
                    <td>{{ $produto['estoque'] }}</td>
                    <td>{{ $produto['ultima_compra'] }}</td>                    
                    <td>{{ $produto['preco_venda'] }}</td>
                    <td>{{ $produto['markup_lista'] }}</td>
                    <td>{{ $produto['preco_medio'] }}</td>
                </tr>
                @endforeach 
            </tbody>
        </table>
    </div>

    <script>
        table_dialog_agrupamento = $('#table-filter-agrupamento').DataTable({
            "searching": false,
            "paging": true,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
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
                    "targets": 'tb_number',
                    "class": 'tb_number',
                    "width": '5px' 
                },
                {
                    "targets": "tb_estabelecimento",
                    "width": '5%',
                    "orderable": false,
                },
                {
                    "targets": 1,
                    "width": "15%"
                }
            ],
            "order": [[ 1, "asc" ], [ 0, "asc" ], [2, "asc" ], [3, "asc" ], [4, "asc" ]]

        });
    </script>
@endsection