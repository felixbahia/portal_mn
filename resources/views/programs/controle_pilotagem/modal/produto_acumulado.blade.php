@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-view-produto-acumulado">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Itens Enviados</th>
                    <th>Itens Vendidos</th>
                    <th>Efetividade%</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($response as $dado)
                <tr>
                    <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $dado['produto'] }}">{{ $dado['produto'] }}</div></div></td>
                    <td><a href="#" class="bt-produto-acumulado" data-total='false' data-produto_total="{{ $total_filtro }}" data-title='ANALITÍCO POR PRODUTO' data-route="{{ route('controle_pilotagem.modal.produto') }}" data-filtro="{{ $dado['filtro'] }}" >{{ $dado['produtos_pilotagem'] }}</a></td>
                    <td>{{ $dado['produtos_venda'] }}</td>
                    <td>{{ $dado['efetividade'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total:</td>
                    <td><a href="#" class="bt-produto-acumulado" data-total='true' data-produto_total="{{ $total_filtro }}" data-title='ANALITÍCO POR PRODUTO' data-route="{{ route('controle_pilotagem.modal.produto') }}" data-filtro="{{ $total['filtro'] }}" >{{ $total['produtos_pilotagem'] }}</a></td>
                    <td>{{ $total['produtos_venda'] }}</td>
                    <td>{{ $total['efetividade'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>
$(document).ready( function () {
    table_filters_produto_acumulado = $('#table-view-produto-acumulado')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 15,
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
        "columnDefs": [
            {
                "class": "tb_number", 
                "targets": [1,2,3]
            },
            {
                "width": "35%", 
                "targets": [0]
            },
        ],
    });    

    table_filters_produto_acumulado.on('draw', function (event) {
        $(document).find(".bt-produto-acumulado").off("click");
        $(document).find(".bt-produto-acumulado").on("click", function(event){
            event.stopPropagation();
            showModalAberturaProdutoAcumulado($(this));
        });
    });

    table_filters_produto_acumulado.draw();
});

function showModalAberturaProdutoAcumulado($this){
    var url = $($this).data("route");
    var filtro = $($this).data("filtro");
    var title = $($this).data('title');
    var total = $($this).data('total');
    var produto = 'true';
    var produto_total = $($this).data('produto_total');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filtro: filtro, total : total, produto_total : produto_total, produto : produto},
        method: 'POST',
        success: function(body){
            createModal("modal_produto_acumulado", title, body, 'modal-lg');
        }
    });
}

</script>
@endsection  