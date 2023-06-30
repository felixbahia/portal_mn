@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog_debito">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Cliente</th>
                <th>Titulo</th>
                <th class="tb_number">Parcela</th>
                <th class="sort-date">Data de Vencimento</th>
                <th class="tb_number">Valor</th>
                <th>Posição de Cobrança</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $value)
            <tr>
                <td>{{ $value["codigo"] }}</td>
                @if ($grupoCliente > 1 )
                <td> {{ $value["NOMECLIENTE"] }} </td>
                @endif
                <td>{{ $value["numero"] }}</td>
                <td>{{ $value["parcela"] }}</td>
                <td data-order="{{ $value["vencimento"] }}">{{ parserData($value["vencimento"]) }}</td>
                <td>{{ parserValor($value["valor"]) }}</td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["POSICAO_CR"]." - ".$value["POSICAO_CR_DESCRICAO"] }}">{{ $value["POSICAO_CR_DESCRICAO"] }}</div></div></td>
            </tr>
        	@endforeach
        </tbody>
    </table>
</div>
<script type="text/javascript">
    table_filters_dialog_debito = $('#table-filters-dialog_debito').DataTable({
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
    });
</script>
@endSection