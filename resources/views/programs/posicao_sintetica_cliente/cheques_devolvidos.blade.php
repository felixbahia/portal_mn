@extends('layouts.page-dialog')

@section('content')
    
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-filter-cheques-devolvidos" id="table-filters-cheques-devolvidos">
            <thead>
                <tr>
                    @if($clientes > 1)
                    <th>Cliente</th>
                    @endif
                    <th>Banco</th>
                    <th>Agência</th>
                    <th>Conta</th>
                    <th>Número</th>
                    <th class='tb_number'>Valor</th>
                    <th class='sort-date'>Bom para</th>
                    <th class='sort-date'>Devolvido</th>
                    <th>Origem</th>
                    <th>Títulos vinculados</th>
                </tr>
            </thead>
            <tbody>
                @foreach($retorno as $item)
                <tr>
                    @if($clientes > 1)
                    <td><div><div data-toggle="tooltip" data-html="true" title="{{ $item['cliente'] }}">{{ $item['cliente'] }}</div></div></td>
                    @endif
                    <td>{{ $item['banco'] }}</td>
                    <td>{{ $item['agencia'] }}</td>
                    <td>{{ $item['conta'] }}</td>
                    <td>{{ $item['numero_cheque'] }}</td>
                    <td>{{ $item['valor'] }}</td>
                    <td data-order="{{ $item['bom_para'] }}">{{ parserData($item['bom_para']) }}</td>
                    <td data-order="{{ $item['devolvido'] }}">{{ parserData($item['devolvido']) }}</td>
                    <td>{{ $item['origem'] }}</td>
                    <td>{!! $item['vinculados'] !!}</td>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan=@if($clientes > 1)"5"@else"4"@endif><b>Total:</b></td>
                    <td>{{ $totais['valor'] }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>

    $(document).ready(function(){
        table_filters_cheques_devolvidos.draw();
    })

    table_filters_cheques_devolvidos_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": 15,
        "processing": true,
        "orderMulti": false,
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
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
        'columnDefs': [
            { "class": "tb_number", 'targets': "tb_number" },
            { "class": "tb_date", targets: "sort-date" }@if($clientes > 1),
            { 'targets': 5, 'width': '300px'}
            @endif
        ]
    };

    table_filters_cheques_devolvidos = $(document).find('#table-filters-cheques-devolvidos').DataTable(table_filters_cheques_devolvidos_options);

    function showInfoTituloPrePago($id){
        var title = "Detalhes do título";
        $.ajax({
            url: '{{ route('titulos_prepago.modal.detalhes') }}',
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
@endsection