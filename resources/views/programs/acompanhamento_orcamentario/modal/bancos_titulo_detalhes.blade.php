@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-filters-dialog_titulos">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Fornecedor</th>
                <th>Titulo</th>
                <th class="tb_number">Parcela</th>
                <th class="sort-date">Data de Emissão</th>
                <th class="sort-date">Data de Vencimento</th>
                <th class="sort-date">Data de Pagamento</th>
                <th class="tb_number">Valor Original</th>
                <th class="tb_number">Valor (Saldo)</th>
                <th class="tb_number">Juros</th>
                <th class="tb_number">Juros Diários</th>
                <th class="sort-date">Início Juros</th>
                <th class="tb_number">Desconto</th>
                <th>Boleto</th>
                <th>Posição de Cobrança</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados['titulos'] as $titulo)
            <tr>
                <td>
                    <div><div data-toggle="tooltip" data-html="true" title="{{ $titulo["estabelecimento"] }}">{{ $titulo["estabelecimento"] }}</div></div>
                </td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $titulo["nome_fornecedor"] }}">{{ $titulo["nome_fornecedor"] }}</div></div></td>
                <td>{{ $titulo["documento"] }}</td>
                <td>{{ $titulo["parcela"] }}</td>
                <td>{{ $titulo["data_emissao"] }}</td>
                <td>{!! $titulo["data_vencimento"] !!}</td>
                <td>{{ $titulo["data_pagamento"] }}</td>
                <td>{{ $titulo["valor_original"] }}</td>
                <td>{{ $titulo["valor"] }}</td>
                <td>{{ $titulo["multa"] >0?parserValor($titulo["multa"]):'' }}</td>
                <td>{{ $titulo["juros_diarios"] >0?parserValor($titulo["juros_diarios"]):'' }}</td>
                <td>{{ !empty($titulo["data_juros"])?parserData($titulo["data_juros"]):'' }}</td>
                <td>{{ $titulo["desconto"] >0?parserValor($titulo["desconto"]):'' }}</td>
                <td>{{ $titulo["numero_boleto"] }}</td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $titulo["POSICAO_CR"] }}">{{ $titulo["POSICAO_CR"] }}</div></div></td>
                <td>{{ $titulo['status'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
        </tfoot>
    </table>
</div>
<script type="text/javascript">

    $(document).ready(function(){
        $(document).find('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            template: '<div class="popover popover-notas" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });
    })
    
    table_filters_dialog_titulos = $('#table-filters-dialog_titulos').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 1, 'asc' ]]
    }).on('draw', function(){
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
    });
</script>
@endSection