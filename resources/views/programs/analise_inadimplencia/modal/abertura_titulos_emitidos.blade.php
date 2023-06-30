@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-modal-abertura-emitidos">
            <thead>
                <tr>
                    <th>Banco</th>
                    <th>Estabelecimento</th>
                    <th>Cliente</th>
                    <th>Título</th>
                    <th>Data Emissao</th>
                    <th>Data Vencto</th>
                    <th>Valor Original</th>
                    <th>Valor Pago</th>
                    <th>Juros</th>
                    <th>Desconto</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($response as $dado)
                <tr>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['banco'] }}">{{ $dado['banco'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['estabelecimento'] }}">{{ $dado['estabelecimento'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['cliente'] }}">{{ $dado['cliente'] }}</div></div></td>
                    <td>{{ $dado['titulo'] }}</td>
                    <td data-order="{{ $dado['data_emissao'] }}">{{ parserData($dado['data_emissao']) }}</td>
                    <td data-order="{{ $dado['data_vencimento_sql'] }}">{!! $dado['data_vencimento'] !!}</td>
                    <td>{{ $dado['valor_original']}}</td>
                    <td>{{ $dado['valor_pago'] }}</td>
                    <td>{{ $dado['juros'] }}</td>
                    <td>{{ $dado['desconto'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>Total</td>
                <td>{{ $saida['valor_original'] }}</td>
                <td>{{ $saida['valor_pago'] }}</td>
                <td>{{ $saida['juros'] }}</td>
                <td>{{ $saida['desconto'] }}</td>
            </tfoot> 
        </table>
    </div>
</div>
<script>
$(document).ready( function () {

    $(document).find('[data-toggle="popover"]').popover({
        container: 'body',
        html: true,
        show: true,
        template: '<div class="popover popover-notas" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
    });

    table_filters_analitic_abertura_titulos = $('#table-modal-abertura-emitidos')
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
                "targets": [6,7,8,9]
            },
            {
                "class": "text_date", 
                "targets": [4,5]
            },
            {
                "width": "5%", 
                "targets": [0,1]
            },
            {
                "width": "15%", 
                "targets": [2]
            },
        ],
        "drawCallback": function( settings ) {
            $('[data-toggle="popover"]').popover('hide');
        }
    });    
    table_filters_analitic_abertura_titulos.draw();
});
</script>
@endsection  