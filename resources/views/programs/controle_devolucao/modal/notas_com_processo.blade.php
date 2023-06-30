@extends('layouts.page-dialog')
@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit table-not-view" id="table_notas_com_processos">
            <thead>
                <tr>
                    <th><span data-toggle="tooltip" data-placement="top" title="Ordem de devolução" data-original-title="Ordem de devolução">O.D.</span></th>
                    <th>Estabelecimento</th>
                    <th>Cliente</th>
                    <th>Nota</th>
                    <th class='tb_number'>Valor</th>
                    <th>Tipo</th>
                    <th>Motivo</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($retorno as $item)
                <tr>
                    <td><a href="#" data-toggle="tooltip" data-placement="top" title="Movimentos da requisição" onclick="showModalLog('{{ $item['id'] }}')">{{ $item['id_requisicao'] }}</a></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['estabelecimento'] }}">{{ $item['estabelecimento'] }}</div></div></td>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $item['cliente'] }}">{{ $item['cliente'] }}</div></div></td>
                    <td class='tb_number'><a href="#" data-toggle="tooltip" data-placement="top" title="Detalhes no Nasajon" onclick="showModalNota('{{ $item['nota_id'] }}')">{{ $item['nota_fiscal'] }}</a></td>
                    <td class='tb_number'>{{ $item['valor'] }}</td>
                    <td>{{ $item['valor_parcial'] }}</td>
                    <td>{{ $item['motivo'] }}</td>
                    <td>{{ $item['status_exibir'] }}</td>
                    <td><a href="#" class="bt-view" data-toggle="tooltip" data-placement="top" onclick="visualizarNota('{{ $item['id'] }}')"></a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<script type="text/javascript">

$(document).ready( function () {
        table_filters_notas = $("#table_notas_com_processos").DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "scrollX": false,
        "scrollCollapse": true,
        "paging": false,
        "autoWidth": true,
        "language": {
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
            "decimal":        ",",
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
                'targets': 0,
                'class': 'tb_number',
                'width': '1vw',
            },
            {
                "class": "tb_number", 
                "type": 'num-fmt', 
                "targets": "tb_number",
                render: $.fn.dataTable.render.number( '.', ',', 2 )
            },
            { "targets": [-1, -2], "orderable": false},
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 0, 'asc' ]]
        }
    );
    setTimeout(function(){
        table_filters_notas.draw();
    }, 200);
});

function visualizarNota($id){
    $.ajax({
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id: $id
        },
        url: '{{ route('devolucao_nota.modal.visualizar') }}',
        success: function(data){
            createModal('editar-devolucao-modal', 'Detalhes da requisição de devolução', data, 'modal-lg');
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

function showModalNota($id){
    var url = '{{ route('notas_nasajon.modal.exibir') }}';
    var modal_class = 'modal-lg';
    var title = 'Detalhes da nota';
    $.ajax({
        url: url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", id_nota: $id},
        success: function(body){
            createModal('modal_message_edit', title, body, modal_class);
        }
    });
}

</script>
@endsection