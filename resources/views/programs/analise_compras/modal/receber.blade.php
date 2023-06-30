@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped table-not-edit table-not-view" id="table-compras-receber-abertura">
                <thead>
                    <tr>
                        <th>Estabelecimento</th>
                        <th>PCMN</th>
                        <th>Proforma</th>
                        <th>Data Compra</th>
                        <th>Previsão de entrega</th>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dados as $dado)
                    <tr>
                        <td>{{ $dado['estabelecimento'] }}</td>
                        <td>{{ $dado['PCMN'] }}</td>
                        <td>{{ $dado['proforma'] }}</td>
                        <td>{{ $dado['compra'] }}</td>
                        <td>{{ $dado['previsao'] }}</td>
                        <td>{{ $dado['codigo'] }}</td>
                        <td>{{ $dado['nome'] }}</td>
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
                    <td>{{ $total }}</td>
                </tfoot> 
            </table>
        </div>
</div>
<script>
$('[data-toggle="tooltip"]').tooltip();

$(document).ready( function () {
    $height = $(".modal-body").height() - 130;
    $.fn.dataTable.moment('DD/MM/YYYY');
    var table_dialog_receber = $('#table-compras-receber-abertura')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "orderMulti": false,
        "ordering": true,
        "scrollX": false,
        "scrollY": "65vh",
        "scrollCollapse": true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                text: 'Analise de Compras - Receber',
                footer: true,
                customize: function ( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                },
                exportOptions: {
                    modifier: {
                        page: 'all'
                    },
                    format: {
                        body: function ( data, row, column, node ) {
                            console.log(column, data)
                            return (column === 1 || column === 2 || column === 3 || column === 4 || column === 5 || column === 6 || column === 7) ?
                                data.replace( /[$.]/g, '' ).replace( /[$,]/g, '.' ).toLocaleString('pt-BR') :
                                data;
                        }
                    }
                }
            },
        ],
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
                "targets": [1,7]
            },
            { "class": "tb_date", "targets": [3,4] }
        ]
});
$('[data-toggle="tooltip"]').tooltip();
setTimeout(function(){
    table_dialog_receber.draw();
}, 200);
});

</script>
@endsection        
