@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog_cheques">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Banco</th>
                <th>Agência</th>
                <th>Conta</th>
                <th>Número</th>
                @if ($grupoCliente > 1)
                    <th>Cliente</th>
                @endif
                <th class="tb_number">Valor</th>
                <th>Obs</th>
                <th>Situação Atual</th>
                <th class="sort-date">Data Entrada</th>
                <th class="sort-date">Data Vencimento</th>
                <th>Títulos vinculados</th>
            </tr>
        </thead>
        <tbody>
        	@foreach($dadosNasajon as $value)
        	<tr>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["estabelecimento"] }}">{{ $value["estabelecimento"] }}</div></div></td>
                <td>{{ $value["banco"] }}</td>
                <td>{{ $value["agencia"] }}</td>
                <td>{{ $value["numero_conta"] }}</td>
                <td>{{ $value["numero_cheque"] }}</td>
                @if ($grupoCliente > 1 )
                    <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value['clientenome'] }}">{{ $value['clientenome'] }}</div></div></td>
                @endif
                <td>{{ $value["valor"] }}</td>
                <td>{{ $value['observacao'] }}</td>
                <td>{{ $value["situacao"] }}</td>
                <td data-order="{{ $value["data_entrada"] }}">{{ parserData($value["data_entrada"]) }}</td>
                <td data-order="{{ $value["data_vencimento"] }}">{{ parserData($value["data_vencimento"]) }}</td>
                <td>{!! $value['vinculados'] !!}</td>
        	</tr>
        	@endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan='@if ($grupoCliente > 1)6 @else 5 @endif'>Total:</td>
                <td>{{ $total }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    table_filters_dialog_cheques = $('#table-filters-dialog_cheques').DataTable({
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
            { "class": "tb_date", targets: "sort-date" }@if ($grupoCliente > 1 ),
            { 'targets': 5, 'width': '300px'}
            @endif
        ],
        "order": [[ 0, 'asc' ]]
    });

    function detalheTituloNasajon($id){
        var title = "Detalhes do título";
        $.ajax({
            url: '{{ route('cliente.posicao_sintetica.modal.titulo') }}',
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: $id
            },
            success: function(body){
                createModal("titulo-detalhes-modal", title, body, '');
            }
        });
    }
</script>
@endSection
