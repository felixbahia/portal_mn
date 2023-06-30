@extends('layouts.page-dialog')

@section('content')
<div style="overflow: hidden;">
    <div class="content-dialog-table">
        <div class="row">
            <div class="col">
                <table class="table-striped order-column" id="table_analise_mes_a_mes_compras">
                    <thead>
                        <tr>
                            <th class='width-table-align-250'>Produto</th>
                            @foreach ($th as $row) 
                                <th class="width-table-align-120">{{ $row }}</th>
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
                                    <td class="tb_number width-table-align-120"> {{ parserValor($value_busca['data'][$key]) }}</td>
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
                            <th class="width-table-align-250">Total</th>
                            @foreach ($th as $keys => $row) 
                                <th class="tb_number width-table-align-120">
                                    @foreach($footer as $key => $fo)
                                        @if($keys == $key)
                                            {{ parserValor($fo['valor']) }}
                                        @endif
                                    @endforeach
                                </th>
                            @endforeach
                            <th class="tb_number width-table-align-120">{{ $total }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>    
    </div>
</div>
<script>
$(document).ready( function () {
    var table_dialog_mes_a_mes_compras = $('#table_analise_mes_a_mes_compras')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "info": false,
        scrollY:        "50vh",
        scrollX:        true,
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
    table_dialog_mes_a_mes_compras.draw();
}, 200);
});

</script>
@endsection        
