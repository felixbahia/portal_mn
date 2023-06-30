@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-filter-devolucoes" id="table-filters-devolucoes-total">
            <thead>
                <tr>
                    <th>Nº Processo</th>
                    <th>Nota Fiscal</th>
                    <th class='tb_data'>Data de Emissão</th>
                    <th>Tipo de Devolução</th>
                    <th class='tb_number'>Valor</th>
                    <th class='texto'>Cliente</th>
                    <th class='texto'>Representante</th>
                    <th class='lupa'></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($retorno as $linha)
                <tr>
                <td><a href='#' onclick="showModalLog('{{ $linha['id'] }}');">{{ $linha['processo'] }}</a></td>
                    <td>{{ $linha['nota_fiscal'] }}</td>
                    <td class='date_format'>{{ $linha['data_emissao'] }}</td>
                    <td>{{ $linha['total_parcial'] }}</td>
                    <td>{{ $linha['valor'] }}</td>
                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $linha['cliente'] }}' >{{ $linha['cliente'] }}</div></div></td>
                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $linha['representante'] }}' >{{ $linha['representante'] }}</div></div></td>
                    <td><a href='#' class="bt-view" onclick="visualizar_nota('{{ $linha['id'] }}');"></a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>

    $(document).ready(function(){
        table_filters_devolucoes_total.draw();
    });

    table_filters_devolucoes_total_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
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
            {
                'targets': 'lupa',
                'width': '1px',
                'orderable': false
            },
            {
                'targets': 'texto',
                'width': '20vw'
            },
            {
                "class": "tb_number",
                'targets': "tb_number",
            }
        ]
    };

    table_filters_devolucoes_total = $('#table-filters-devolucoes-total').DataTable(table_filters_devolucoes_total_options);

    function visualizar_nota($id){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            url: '{{ route('devolucao_nota.modal.visualizar') }}',
            success: function(data){
                createModal('nota-nasajon-modal', 'Detalhes da requisição de devolução', data, 'modal-lg');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

    function showModalLog($id){
        var url = '{{ route('devolucao_nota.modal.logs') }}';
        var modal_class = 'modal-lg';
        var title = 'Movimentos da requisição';
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_log_requisicao', title, body, modal_class);
            }
        });
    }
</script>

@endsection