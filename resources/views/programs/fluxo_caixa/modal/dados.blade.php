@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view table-dialog-faturamento" id="table-dados">
        <thead>
            <tr>
                <th>{{ $cliente_fornecedor }}</th>
                <th class="tb_number">Número do titulo</th>
                <th class="tb_number">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($response as $dado)
            <tr>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $dado["cliente"] }}">{{ $dado["cliente"] }}</div></div></td>
                <td>{{ $dado['documento'] }}</td>
                <td>{{ $dado['valor'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td class="tb_number">Total:</td>
                <td class="tb_number">{{ $total }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        $('#table-dados').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
            "orderMulti": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '{{ $data }}',
                    exportOptions: {
                        modifier: {
                            page: 'all',
                            search: 'applied',
                            order: 'applied'
                        },
                        format: {
                            body: function (data, row, column, node ) {
                                data = $('<p>' + data + '</p>').text();
                                if(column === 2 ){
                                    if(data != ''){
                                        numero = data.replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            }
                        }
                    },
                },
            ],
            "language": {
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
                "decimal":        ",",
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
                    "class": "tb_number", 
                    "targets": "tb_number"
                },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ]]
        });
    });
</script>
@endsection
