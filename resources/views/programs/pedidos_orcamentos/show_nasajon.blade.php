@extends('layouts.page-dialog')

@section('content')
<ul class="nav nav-tabs" id="PedidoOrcamentoTabs" role="tablist">
	<li class="nav-item">
		<a class="nav-link active" id="dados-tab" data-toggle="tab" href="#pedido_orcamento_dados" role="tab" aria-controls="pedido_orcamento_dados" aria-selected="true">Dados</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" id="produtos-tab" data-toggle="tab" href="#pedido_orcamento_produtos" role="tab" aria-controls="pedido_orcamento_produtos" aria-selected="false">Produtos</a>
	</li>
</ul>
<div class="tab-content" id="PedidoOrcamentoTabsContent">
	<div class="tab-pane show active" id="pedido_orcamento_dados" role="tabpanel" aria-labelledby="dados-tab">
		<div class="content-tab">
			<div class="row-tab">
				<div class="field-tab">
					<div class="title">Estabelecimento</div>
					<div class="value">{{ $dados["estabelecimento"] }}</div>
				</div>
				<div class="field-tab">
					<div class="title">Número do Pedido</div>
					<div class="value">{{ $dados["numero_pedido"] }}</div>
				</div>
				<div class="field-tab">
					<div class="title">Status</div>
					<div class="value">{{ $dados["status"] }}</div>
				</div>
				<div class="field-tab">
					<div class="title">Tipo de Operação</div>
					<div class="value">{{ $dados["tipo_operacao"] }}</div>
				</div>
				<div class="field-tab">
					<div class="title">Data e hora do pedido</div>
					<div class="value text_date">{{ $dados["datahora_pedido"] }}</div>
				</div>
			</div>
			<div class="row-tab">
				<div class="field-tab">
					<div class="title">Cliente</div>
					<div class="value">{{ $dados["cliente"] }}</div>
				</div>
				@if(isset($dados["cidade_uf"]))
				<div class="field-tab">
					<div class="title">Destino</div>
					<div class="value">{{ $dados["cidade_uf"] }}</div>
				</div>
				@endif
			</div>
			<div class="row-tab">
				<div class="field-tab">
					<div class="title">Vendedor</div>
					<div class="value"><div title="{{ $dados["vendedor_codigo"]." - ".$dados["vendedor"] }}">{{ $dados["vendedor"] }}</div></div>
				</div>
				<div class="field-tab">
					<div class="title">Gerado pelo:</div>
					<div class="value">{!! $dados["gerado"] !!}</div>
				</div>
			</div>
			<div class="row-tab">
				<div class="field-tab">
					<div class="title">Transportador</div>
					<div class="value"><div title="{{ $dados["transportador_codigo"]." - ".$dados["transportador"] }}">{{ $dados["transportador"] }}</div></div>
				</div>
				@if(isset($dados["frete_preco"]))
				<div class="field-tab">
					<div class="title">Frete aplicado no preço</div>
					<div class="value">{{ $dados["frete_preco"] }}</div>
				</div>
				@endif
			</div>
			<div class="row-tab">
				<div class="field-tab">
					<div class="title">Condição de pagamento</div>
					<div class="value">{{ $dados["condicao_pagamento"] }}</div>
				</div>
				<div class="field-tab">
					<div class="title">Desconto Geral</div>
					<div class="value text_number">{{ $dados["desconto_geral"] }}</div>
				</div>
				<div class="field-tab">
					<div class="title">Valor Total</div>
					<div class="value text_number">{{ $dados["valor_total"] }}</div>
				</div>
			</div>
			<div class="row-tab">
				<div class="field-tab">
					<div class="title">Observação</div>
					<div class="value">{!! $dados["observacao"] !!}</div>
				</div>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="pedido_orcamento_produtos" role="tabpanel" aria-labelledby="produtos-tab">
		<div class="content-dialog-table">
			<table class="table table-striped table-dialog-pedido-itens" id="table-itens-pedido-show">
				<thead>
					<tr>
						<th rowspan="2" class="align-middle">Grupo</th>
						<th rowspan="2" class="align-middle">Produto</th>
						<th rowspan="2" class="align-middle">Sub-Grupo</th>
						<th rowspan="2" class="align-middle">Unid.</th>
						<th colspan="2" class="text-center">Quantidade</th>
						<th rowspan="2" class="align-middle tb_number">Des.%</th>
						<th colspan="2" class="text-center">Valor</th>
					</tr>
					<tr>
						<th class="tb_number td_quantidade_pedido">Pedida</th>
						<th class="tb_number td_quantidade_pedido">Faturada</th>
						<th class="tb_number td_valor_pedido">Unitario</th>
						<th class="tb_number td_valor_pedido">Total</th>
					</tr>
				</thead>
				<tbody>
					@foreach($itens as $iten)
					<tr>
						<td><div><div data-toggle="tooltip" data-placement="bottom" data-html="true" title="">{{ $iten["grupo"] }}&nbsp;&nbsp;</div></div></td>
						<td>{{ $iten["codigo"] }}&nbsp;&nbsp;</td>
						<td><div><div data-toggle="tooltip" data-placement="bottom" data-html="true" title="">{{ $iten["subgrupo"] }}&nbsp;&nbsp;</div></div></td>
						<td><div><div data-toggle="tooltip" data-placement="bottom" data-html="true" title="">{{ $iten["unidade"] }}&nbsp;&nbsp;</div></div></td>
						<td class="class-quantidade-pedido" data-order="{{ $iten["quantidade_pedida"] }}"><div><div data-toggle="tooltip" data-placement="bottom" data-html="true" title="">{{ $iten["quantidade_pedida"] }}&nbsp;&nbsp;</div></div></td>
						<td class="class-quantidade-pedido" data-order="{{ $iten["quantidade_faturada"] }}"><div><div data-toggle="tooltip" data-placement="bottom" data-html="true" title="">{{ $iten["quantidade_faturada"] }}&nbsp;&nbsp;</div></div></td>
						<td><div><div data-toggle="tooltip" data-placement="bottom" data-html="true" title="">{{ $iten["desconto"] }}&nbsp;&nbsp;</div></div></td>
						<td class="class-valor-pedido" data-order="{{ $iten["valor_unitario"] }}"><div><div data-toggle="tooltip" data-placement="bottom" data-html="true" title="">{{ $iten["valor_unitario"] }}&nbsp;&nbsp;</div></div></td>
						<td class="class-valor-pedido" data-order="{{ $iten["valor_total"] }}"><div><div data-toggle="tooltip" data-placement="bottom" data-html="true" title="">{{ $iten["valor_total"] }}&nbsp;&nbsp;</div></div></td>
					</tr>
					@endforeach
				</tbody>
				<tfoot>
					<tr>
						<td></td>
						<td></td>
						<td></td>
						<td>Total:</td>
						<td>{{$total_itens['quantidade_pedida']}}</td>
						<td>{{$total_itens['quantidade_faturada']}}</td>
						<td>{{$total_itens['desconto']}}</td>
						<td>{{$total_itens['valor_unitario']}}</td>
						<td>{{$total_itens['valor_total']}}</td>
					</tr>
				</tfoot>    
			</table>
		</div>
	</div>
</div>
<script type="text/javascript">
	
    table_dialog = [];
    var $height = 200;
    $('#table-itens-pedido-show').find("td").find("div").find("div").off('mouseover');
    $(document).ready(function () {
        setTimeout(function(){
            $height = $(".modal-body").height() - 130;
            table_dialog = $("#table-itens-pedido-show").DataTable({
                "searching": false,
                "lengthChange": false,
                "info": false,
                "scrollY": $height,
                "scrollCollapse": true,
                "paging": false,
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
                    { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                    { "class": "class-valor-pedido", targets: "td_valor_pedido" },
                    { "class": "class-quantidade-pedido", targets: "td_quantidade_pedido" }
                ]
            });
            $('[data-toggle="tooltip"]').tooltip({placement: "bottom"});
        }, 100);
        $('#table-itens-pedido-show').find("td").find("div").find("div").on('mouseover', function(){
            var $this = $(this);
            if(this.offsetWidth < this.scrollWidth && $($this).attr('data-original-title') === ""){
            	var texto = $this.text();
            	texto = texto.replace("&nbsp;", "").trim();
                $this.attr('data-original-title', texto);
                $('[data-toggle="tooltip"]').tooltip({placement: "bottom"});
                $($this).tooltip('show');
            }
        });
		$(document).find('#PedidoOrcamentoTabs').find('a[data-toggle="tab"]').off('shown.bs.tab');
		$(document).find('#PedidoOrcamentoTabs').find('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			table_dialog.draw();
            $('[data-toggle="tooltip"]').tooltip({placement: "bottom"});
		});
	});
	
    function abrirPedido($id){
        $.ajax({
            url: '{{ route('pedido_portal.detalhes') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                pedido_id: $id 
            },
            success: function (data){
                createModal('detalhes', 'Detalhes do Pedido', data, 'modal-lg');
            }
        });
    }
</script>
@endsection