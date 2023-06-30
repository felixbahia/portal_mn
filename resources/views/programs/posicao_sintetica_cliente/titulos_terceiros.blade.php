@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog_titulos">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                @if (count($cod_cliente)>1)
                <th>Cliente</th>
                @endif
                <th>Em nome de</th>
                <th>Titulo</th>
                <th class="tb_number">Parcela</th>
                <th class="sort-date">Data de Emissão</th>
                <th class="sort-date">Data de Vencimento</th>
                <th class="tb_number">Valor</th>
                <th>Boleto</th>
                <th>Posição de Cobrança</th>
                <th>Banco</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $value)
            <tr>
                <td>{{ $value["ESTABEL"] }}</td>
                @if (count($cod_cliente)>1)
                    <td>{{ $value["NOMECLIENTE"] }}</td>
                @endif
                <td>{{ $value["nome_terceiro"] }}</td>
                <td>{{ $value["NUMDOC"] }}</td>
                <td>{{ $value["NPARC"] }}</td>
                <td data-order="{{ $value["DTEMIS"] }}">{{ parserData($value["DTEMIS"]) }}</td>
                <td data-order="{{ $value["DTVCTO"] }}">{{ parserData($value["DTVCTO"]) }}</td>
                <td>{{ parserValor($value["VALOR"]) }}</td>
                <td>{{ $value["NUMDUPBCO"] }}</td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["POSICAO_CR"]." - ".$value["POSICAO_CR_DESCRICAO"] }}">{{ $value["POSICAO_CR_DESCRICAO"] }}</div></div></td>
                <td>{{ $value['banco'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<script type="text/javascript">
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
    });
</script>
@endSection