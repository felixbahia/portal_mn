@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-forma-pagamento-modal">
            <thead>
                <tr>
                    <th>Forma de Pagamento</th>
                    <th>Clientes</th>
                    <th>Notas</th>
                    <th>Valor</th>
                    <th>Ticket Médio</th>
                    <th>% Sobre Total</th>
                    <th>Prazo Médio</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($response as $dado)
                <tr>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['forma_pagamento'] }}">{{ $dado['forma_pagamento'] }}</div></div></td>
                    <td class='tb_number'>{{ $dado['cliente'] }}</td>
                    <td class='tb_number'>{{ $dado['notas'] }}</td>
                    <td class='tb_number'>{{ $dado['valor'] }}</td>
                    <td class='tb_number'>{{ $dado['ticket_medio'] }}</td>
                    <td class='tb_number'>{{ $dado['sobre_total'] }}</td>
                    <td class='tb_number'>{{ $dado['prazo_medio'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total:</td>
                    <td class='tb_number'>{{ $total['cliente'] }}</td>
                    <td class='tb_number'>{{ $total['notas'] }}</td>
                    <td class='tb_number'>{{ $total['valor'] }}</td>
                    <td class='tb_number'>{{ $total['ticket_medio'] }}</td>
                    <td class='tb_number'>{{ $total['sobre_total'] }}</td>
                    <td class='tb_number'>{{ $total['prazo_medio'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>
$(document).ready( function () {
    table_filters_analitic_atraso = $('#table-forma-pagamento-modal')
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
        "autoWidth": false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Análise de Produto',
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
                            if(column === 3 || column === 4 || column === 5 || column === 6){
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
        "columnDefs": [
            {
                "class": "tb_number", 
                "targets": "tb_number",
                "orderable": false
            },{ targets: 0, "orderable": false, width: '15%'},
        ],
    });    
    table_filters_analitic_atraso.on('draw', function () {
        $(document).find(".bt-view-atrasados-abertura").off("click");
        $(document).find(".bt-view-atrasados-abertura").on("click", function(event){
            event.stopPropagation();
            showModalAtraso($(this));
        });
    });    
    table_filters_analitic_atraso.draw();
});

function showModalAtraso($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter},
        method: 'POST',
        success: function(body){
            createModal("model_abertura_atraso", title, body, 'modal-lg');
        }
    });
}
</script>
@endsection  