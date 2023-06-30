@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-dialog">
        <thead>
            <tr>
                <th>Código Produto</th>
                <th>Produto</th>
                <th>Tipo</th>
                <th>Quantidade</th>
                <th>Custo Geren.</th>
                <th class="tb_number">Valor</th>
                <th>Motivo</th>
                <th>Usuário</th>
                <th class="tb_date">Data Hora</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dados as $ajuste_estoque)
             <tr>
                <td>{{ $ajuste_estoque['codigo_produto'] }}</td>
                <td><div><div data-toggle="tooltip" data-html="true" title="" data-placement="left" data-original-title="{{ $ajuste_estoque['descricao_produto'] }}">{{ $ajuste_estoque['descricao_produto'] }}</div></div></td>
                <td>{{ $ajuste_estoque['tipo'] }}</td>
                <td class="tb_number">{{ $ajuste_estoque['quantidade'] }}</td>
                <td class="tb_number">{{ $ajuste_estoque['custo_gerencial'] }}</td>
                <td class="tb_number">{{ $ajuste_estoque['valor'] }}</td>
                <td>{{ $ajuste_estoque['motivo'] }}</td>
                <td>{{ $ajuste_estoque['usuario'] }}</td>
                <td class="tb_date">{{ $ajuste_estoque['data_hora'] }}</td>
             </tr>   
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td>Total:</td>
                <td class="tb_number">{{ $total['quantidade'] }}</td>
                <td class="tb_number"></td>
                <td class="tb_number">{{ $total['valor'] }}</td>
                <td></td>
                <td></td>
                <td class="tb_date"></td>
             </tr>   
        </tfoot>
    </table>
    
    <div class="row-table">
        <div class="cel-table col-lg-12 border border-secondary rounded">
            <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Valor Positivo: {!! $total["valor_positivo"] !!}</div></div>
            <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Valor Negativo: {!! $total["valor_negativo"] !!}</div></div>
            <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Total: {!! $total["valor_total"] !!}</div></div>
        </div>
    </div>
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
            "decimal":        ",",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
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
</script>
@endsection
