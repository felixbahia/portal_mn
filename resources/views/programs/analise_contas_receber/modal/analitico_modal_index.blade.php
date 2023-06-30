@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-filters-analitic-modal">
            <thead>
                <tr>
                    <th>Estabelecimento</th>
                    <th>Cliente</th>
                    <th>Título</th>
                    <th>Data Emissao</th>
                    <th>Data Vencto Original</th>
                    <th>Data Vencto Considerada</th>
                    <th>Data pagto</th>
                    <th>Data Lançamento</th>
                    <th>Usuário</th>
                    <th>Dias atraso</th>
                    <th>Valor Original</th>
                    <th>Valor Pago</th>
                    <th>Juros</th>
                    <th>Multa</th>
                    <th>Desconto</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($response as $dado)
                <tr>
                    <td style="width: 5%"><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['estabelecimento'] }}">{{ $dado['estabelecimento'] }}</div></div></td>
                    <td style="width: 15%"><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['cliente'] }}">{{ $dado['cliente'] }}</div></div></td>
                    <td>{{ $dado['titulo'] }}</td>
                    <td style="text-align: center">{{ $dado['data_emissao'] }}</td>
                    <td style="text-align: center">{!! $dado['data_vencimento'] !!}</td>
                    <td style="text-align: center">{{ $dado['data_considerada'] }}</td>
                    <td style="text-align: center">{{ $dado['data_pagamento'] }}</td>
                    <td style="text-align: center">{{ $dado['data_lancamento'] }}</td>
                    <td style="text-align: center">{{ $dado['usuario'] }}</td>
                    <td>{{ ($dado['dias_atraso'] != '0') ? $dado['dias_atraso'] : '' }}</td>
                    <td>{{ ($dado['valor_original'] != '0') ? parserValor($dado['valor_original']) : '' }}</td>
                    <td>{{ ($dado['valor_pago'] != '0') ? parserValor($dado['valor_pago']) : '' }}</td>
                    <td>{{ ($dado['juros'] != '0') ? parserValor($dado['juros']) : '' }}</td>
                    <td>{{ ($dado['multa'] != '0') ? parserValor($dado['multa']) : '' }}</td>
                    <td>{{ ($dado['desconto'] != '0') ? parserValor($dado['desconto']) : '' }}</td>
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
                <td></td>
                <td></td>
                <td></td>
                <td>Total</td>
                <td>{{ $saida['valor_original'] }}</td>
                <td>{{ $saida['valor_pago'] }}</td>
                <td>{{ $saida['juros'] }}</td>
                <td>{{ $saida['multa'] }}</td>
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
    
    table_filters_analitic_estoque = $('#table-filters-analitic-modal')
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
                "targets": [7,8,9,10,11,12,13,14]
            },
        ],
    });    
    table_filters_analitic_estoque.draw();
});
</script>

@endsection  