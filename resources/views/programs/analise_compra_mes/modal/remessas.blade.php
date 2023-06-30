@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-filters-analitic-modal-remessas">
            <thead>
                <tr>
                    <th class="td_produto">Produto</th>
                    <th>Código</th>
                    <th class="tb_number">Pedido</th>
                    <th class="tb_number">Nota</th>
                    <th class="tb_number">Serie</th>
                    <th class="tb_number">Quantidade</th>
                    <th class="tb_number">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dados as $dado)
                    <tr>
                        <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['produto'] }}">{{ $dado['produto'] }}</div></div></td>
                        <td>{{ $dado['codigo_produto'] }}</td>
                        <td>{{ $dado['pedido'] }}</td>
                        <td>{{ $dado['nota'] }}</td>
                        <td>{{ $dado['serie'] }}</td>
                        <td>{{ $dado['quantidade'] }}</td>
                        <td>{{ $dado['valor'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>Total</td>
                <td>{{ $total['quantidade'] }}</td>
                <td>{{ $total['valor'] }}</td>
            </tfoot>
        </table>
    </div>
</div>
<script>
$(document).ready( function () {
    table_filters_analitic = $('#table-filters-analitic-modal-remessas')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "autoWidth": false,
        "orderMulti": false,
        "pageLength": 20,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '',
                footer: true,
                customize: function( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('row c[r^="C"]', sheet).attr( 's', '2' );
                },
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            if(column === 5 || column === 6){
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
                        },
                        footer: function(data) {
                            data = $('<p>' + data + '</p>').text();
                            return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                        }
                    }
                },
            },
        ],
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
                "class": "tb_number",
                "targets": "tb_number"
            },
            {
                "width" : "15%",
                "targets" : "td_produto"
            },
        ]
    });

    table_filters_analitic.draw();
});

</script>
@endsection

