@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-filter-devolucoes" id="table-filters-motivo">
            <thead>
                <tr>
                    <th>Motivo</th>
                    <th class='tb_number'>Quantidade</th>
                    <th class='tb_number'>Valor total</th>
                    <th class='lupa'></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($retorno as $linha)
                <tr>
                    <td>{{ $linha['motivo_descricao'] }}</td>
                    <td class='tb_number'>{{ $linha['quantidade'] }}</td>
                    <td class='tb_number'>{{ $linha['valor'] }}</td>
                    <td>
                        <a href="#" class="bt-view" data-status="{{ $linha['status'] }}" data-status-descricao="{{ $linha['status_descricao'] }}" data-hash="{{ $linha['hash'] }}" data-toggle="tooltip" data-placement="top" title="Por processos" onclick="showModalMotivo(this)"></a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>

    $(document).ready(function(){
        table_filters_motivo.draw();
    });

    table_filters_motivo_options = {
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
                "targets": 'lupa',
                'width': '25vw',
                'orderable': false
            },
            {
                "class": "tb_number",
                'targets': "tb_number",
            }
        ]
    };

    table_filters_motivo = $('#table-filters-motivo').DataTable(table_filters_motivo_options);

    function showModalMotivo($this){
        var devolucao_nota_status_id = $($this).data("status");
        var hash = $($this).data("hash");
        var status_descricao = $($this).data("status-descricao");
        $.ajax({
            url: "{{ route('consulta_devolucao.modal') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                 hash: hash,
                 devolucao_nota_status_id: devolucao_nota_status_id,
                 status_descricao: status_descricao
            },
            success: function(body){
                createModal('modal_devolucoes_motivo', "Processos de devolução: " + status_descricao, body, 'modal-lg');
                $(document).find('#modal_devolucoes_motivo').on('shown.bs.modal', function(){
                    table_filters_devolucoes_total.columns.adjust().draw()
                })
            }
        });
    }
</script>

@endsection