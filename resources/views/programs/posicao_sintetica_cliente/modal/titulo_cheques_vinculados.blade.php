@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-titulo-cheques-vinculados">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Banco</th>
                <th>Agência</th>
                <th>Conta</th>
                <th>Número</th>
                <th>Cliente</th>
                <th class="tb_number">Valor</th>
                <th>Obs</th>
                <th>Situação Atual</th>
                <th class="sort-date">Data Entrada</th>
                <th class="sort-date">Data Vencimento</th>
            </tr>
        </thead>
        <tbody>
        	@foreach($cheques as $value)
        	<tr>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value['estabelecimento'] }}">{{ $value['estabelecimento'] }}</div></div></td>
                <td>{{ $value["banco"] }}</td>
                <td>{{ $value["agencia"] }}</td>
                <td>{{ $value["numero_conta"] }}</td>
                <td>{{ $value["numero_cheque"] }}</td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value['clientenome'] }}">{{ $value['clientenome'] }}</div></div></td>
                <td>{{ $value["valor"] }}</td>
                <td>{{ $value['observacao'] }}</td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value['situacao'] }}">{{ $value['situacao'] }}</div></div></td>
                <td data-order="{{ $value["data_entrada"] }}">{{ parserData($value["data_entrada"]) }}</td>
                <td data-order="{{ $value["data_vencimento"] }}">{{ parserData($value["data_vencimento"]) }}</td>
        	</tr>
        	@endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan='6'>Total:</td>
                <td>{{ $total }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script>
    table_filters_dialog_cheques = $('#table-filters-dialog-titulo-cheques-vinculados').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": false,
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "tb_date", targets: "sort-date" },
            { 'targets': 5, 'width': '300px'}
        ],
        "order": [[ 0, 'asc' ]]
    });
</script>
@endSection
