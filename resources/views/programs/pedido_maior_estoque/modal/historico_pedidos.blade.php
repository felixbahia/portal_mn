@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped table-not-edit table-not-view" id="table-filters-historico_pedidos">
                <thead>
                    <tr>
                        <th>Estabelecimento</th>
                        <th>Nota</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th class="sort-date">Emissão <br/>Nota</th>
                        <th class="sort-date">Emissão <br/>Pedido</th>
                        <th class="tb_number">Quantidade <br/>Pedida</th>
                        <th class="tb_number">Quantidade <br/>Faturada</th>
                        <th class="tb_number">Diferença</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dados as $value)
                    <tr>
                        <td style="width: 15%"><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $value['estabelecimento'] }}">{{ $value['estabelecimento'] }}</div></div></td>
                        <td>
                            <a href="#" class="modal-nota" data-title="DETALHES DA NOTA: {{ $value['nota'] }}"  data-route="{{ route('notas_nasajon.modal.exibir') }}" data-nota_id='{{ $value["nota_id"] }}'>{{ $value['nota'] }}</a>
                        </td>
                        <td>
                            <a href="#" class="modal-pedido" data-title="DADOS DO PEDIDO" data-origem="nasajon"  data-route="{{ route('pedidos_orcamentos.show') }}" data-pedido_id='{{ $value["pedido_id"] }}'>{{ $value['pedido'] }}</a>
                        </td>
                        <td><div><div data-toggle="tooltip" data-html="true" title="" data-original-title="{{ $value['cliente'] }}">{{ $value['cliente'] }}</div></div></td>
                        <td>{{ $value['emissao_nota'] }}</td>
                        <td>{{ $value['emissao_pedido'] }}</td>
                        <td>{{ $value['quantidade_pedida']}}</td>
                        <td>{{ $value['quantidade_faturada']}}</td>
                        <td>{{ $value['saldo']}}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Total</td>
                    <td class='text-right'>{{ parserQtd($total['quantidade_pedida']) }}</td>
                    <td class='text-right'>{{ parserQtd($total['quantidade_faturada']) }}</td>
                    @if($total['saldo'] != 0)
                        <td class='text-right'>{{ parserQtd($total['saldo']) }}</td>
                    @else
                        <td></td>
                    @endif
                </tfoot> 
            </table>
        </div>
</div>
<script>
$(document).ready(function (){
	$(document).find(".modal-nota").off("click");
	$(document).find(".modal-nota").on("click", function(event){
		event.stopPropagation();
		exibirModalNotaDetalhes($(this));
	});
	$(document).find(".modal-pedido").off("click");
	$(document).find(".modal-pedido").on("click", function(event){
		event.stopPropagation();
		exibirModalPedidoDetalhes($(this));
	});
});

setTimeout(function(){
    $height = $(".modal-body").height() - 130;
    $.fn.dataTable.moment('DD/MM/YYYY');
    var table_filters_analitic = $('#table-filters-historico_pedidos')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 20,
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
                "targets": "tb_number"
            },
            { "class": "tb_date", targets: "sort-date" }
        ]
});
$('[data-toggle="tooltip"]').tooltip();
}, 100);

function exibirModalPedidoDetalhes($this){
    var $url = $($this).data("route");
    var $pedido_id = $($this).data("pedido_id");
    var $title = $($this).data("title");
    var $origem = $($this).data("origem");

	$.ajax({
		url: $url,
		data: {_token: "{{ csrf_token() }}", origem: $origem, pedido: $pedido_id},
		method: 'POST',
		success: function(body){
			createModal("itens_pedido", $title, body, 'modal-lg');
		}
	});
}

function exibirModalNotaDetalhes($this){
    var $url = $($this).data("route");
    var $nota_id = $($this).data("nota_id");
    var $title = $($this).data("title");

    $.ajax({
        url: $url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", id_nota : $nota_id},
        success: function(body){
            createModal("exibir_modal_nota_detalhes", $title, body, 'modal-lg');
        }
    });
}
</script>
@endsection        
