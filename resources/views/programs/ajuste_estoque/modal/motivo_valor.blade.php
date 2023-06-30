@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-dialog">
        <thead>
            <tr>
                <th>Motivo</th>
                <th class="tb_number">Positivo</th>
                <th class="tb_number">Negativo</th>
                <th class="tb_number">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dados as $ajuste_estoque)
             <tr>
                <td>
                    <a href="#" onclick="abrirAjustes('{{ $ajuste_estoque['filtros'] }}')">{{ $ajuste_estoque['motivo'] }}</a>
                </td>
                <td class="tb_number">{{ $ajuste_estoque['valor_positivo'] }}</td>
                <td class="tb_number">{{ $ajuste_estoque['valor_negativo'] }}</td>
                <td class="tb_number">{{ $ajuste_estoque['valor_total'] }}</td>
             </tr>   
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total:</td>
                <td class="tb_number">{{ $total['valor_positivo'] }}</td>
                <td class="tb_number">{{ $total['valor_negativo'] }}</td>
                <td class="tb_number">{{ $total['valor_total'] }}</td>
             </tr>   
        </tfoot>
    </table>
</div>
<script>
    table_dialog = $('#table-dialog').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": 15,
        "processing": true,
        "orderMulti": true,
        "scrollCollapse": true,
        "scrollY": "60vh",
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
        "order": [[ 5, "desc" ], [ 1, "asc" ]]
    });

    setTimeout(function(){
        table_dialog.draw(false);
    }, 200);

    function abrirAjustes($filtros){
        $.ajax({
            url: "{{ route("produto.estoque.ajuste_estoque.dialog_valor") }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                filtros: $filtros
            },
            success: function(data){
                $id = "view-pedido";
                $title = "Detalhes do Ajuste por valor"; 
                $body = data;
                $class = "modal-lg";
                createModal($id, $title, $body, $class);
            }
        });
    }
</script>
@endsection
