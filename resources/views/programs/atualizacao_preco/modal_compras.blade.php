@extends('layouts.page-dialog')

@section('content')
    <div class="content-dialog-table">
        <table class='table table-striped table-not-edit' id='table-ultimas-compras'>
            <thead>
                <tr>
                    <th class='number_format'>PCMN</th>
                    <th class='date_format'>Data de Lançamento</th>
                    <th class='number_format'>Proforma</th>
                    <th>Fornecedor</th>
                    <th class='number_format'>Quantidade.</th>
                    <th class='number_format'>Valor Us$</th>
                    <th class='number_format'>Valor FOB</th>
                    <th class='number_format'>Custo Gerencial</th>
                    <th>Usuário</th>
                </tr>
            </thead>
            <tbody>
                @foreach($compras as $compra)
                <tr>
                    <td>{{ $compra['pcmn'] }}</td>
                    <td>{{ $compra['ultima_modificação'] }}</td>
                    <td>{{ $compra['proforma'] }}</td>
                    <td>{{ $compra['fornecedor'] }}</td>
                    <td>{{ $compra['qtde'] }}</td>
                    <td>{{ $compra['dolar'] }}</td>
                    <td>{{ $compra['real'] }}</td>
                    <td>{{ $compra['custo_gerencial'] }}</td>
                    <td>{{ $compra['usuario'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
    table_dialog_ultimas_compras = $('#table-ultimas-compras').DataTable({
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
                "targets": 'number_format',
                "class": 'number_format',
                "width": '5%' 
            },
            { 
                "targets": 'date_format',
                "class": 'date_format',
                "width": '5%' 
            },
        ],
        "order": [[ 1, "desc" ]]

    });
    
    </script>

@endsection