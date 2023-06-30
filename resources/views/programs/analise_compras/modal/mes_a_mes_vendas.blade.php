@extends('layouts.page-dialog')

@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <table class="table-striped order-column" id="table_analise_mes_a_mes_vendas">
            <thead>
                <tr>
                    <th class='width-table-align-250'>Produto</th>
                    @foreach ($th as $row) 
                        <th class="tb_date width-table-align-120">{{ $row }}</th>
                    @endforeach
                    <th class="tb_number width-table-align-120">Total</th>
                </tr>
            </thead>
            <tbody>    
            @foreach($dados as $value_busca)
                <tr>
                    <td class='width-table-align-250'><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $value_busca['codigo'] }}">{{ $value_busca['codigo']}}</div></div></td>
                    @foreach($th as $key => $value)
                        @if(isset($value_busca['data'][$key]))
                            <td class="tb_number width-table-align-120">{{ parserValor($value_busca['data'][$key]) }}</td>
                        @else
                            <td></td>
                        @endif
                    @endforeach
                    <td class="tb_number width-table-align-120">{{ parserValor($value_busca['total_produto']) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th class='width-table-align-250'>Total</th>
                    @foreach ($footer as $fo)
                        <th class="tb_number width-table-align-120">{{ ($fo['valor'] > 0) ? parserValor($fo['valor']) : '' }}</th>
                    @endforeach
                    <th class="tb_number width-table-align-120">{{ $total['total'] }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>
$('[data-toggle="tooltip"]').tooltip();

$(document).ready( function () {
    var table_dialog_mes_a_mes_vendas = $('#table_analise_mes_a_mes_vendas')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "info": false,
        scrollY: "50vh",
        scrollX: true,
        scrollCollapse: false,
        "ordering" : false,
        paging:         false,
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
    });
$('[data-toggle="tooltip"]').tooltip();
setTimeout(function(){
    table_dialog_mes_a_mes_vendas.draw();
}, 200);
});

</script>
@endsection        
