@extends('layouts.page-dialog')
@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit table-not-view" id="table_artigo_analise_compras">
            <thead>
                <tr>
                    <th rowspan="2">Estabelecimento</th>
                    <th rowspan="2" class="tb_number">Pedido</th>
                    <th rowspan="2" class="tb_number">Numero de Itens</th>
                    <th rowspan="2" class="tb_number">Regra SLA</th>
                    <th colspan="3">aprovacao</th>
                    <th colspan="3">faturamento</th>
                    <th rowspan="2" class="tb_number">tempo total (min)</th>
                    <th rowspan="2" class="tb_number">tempo excedido (min)</th>
                </tr>
                <tr>
                    <th class="td_date">inicio</th>
                    <th class="td_date">fim</th>
                    <th class="tb_number">tempo (min)</th>
                    <th class="td_date">inicio</th>
                    <th class="td_date">fim</th>
                    <th class="tb_number">tempo (min)</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($retorno as $item)
                <tr>
                    <td>{{ $item['estabelecimento_nome'] }}</td>
                    <td>{{ $item['pedido'] }}</td>
                    <td>{{ $item['numero_pecas'] }}</td>
                    <td>{{ $item['regra_sla'] }}</td>
                    <td>{{ $item['aprovacao']['inicio'] }}</td>
                    <td>{{ $item['aprovacao']['fim'] }}</td>
                    <td>{{ $item['aprovacao']['tempo'] }}</td>
                    <td>{{ $item['faturamento']['inicio'] }}</td>
                    <td>{{ $item['faturamento']['fim'] }}</td>
                    <td>{{ $item['faturamento']['tempo'] }}</td>
                    <td>{{ $item['tempo_total'] }}</td>
                    <td>{{ $item['tempo_diff'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<script type="text/javascript">
    table_dialog = [];
    table_dialog = $('#table_artigo_analise_compras')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "orderMulti": false,
        "scrollX": false,
        "scrollY": "80vh",
        "scrollCollapse": true,
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
            {
                "class": "tb_number", 
                "targets": "tb_number",
                render: $.fn.dataTable.render.number( '', ',', 0 )
            },
            {
                "class": "text_date", 
                "targets": "td_date"
            },
        ]
    });
    setTimeout(function(){
        table_dialog.draw();
    }, 200);
</script>
@endsection