@extends('layouts.page-dialog')
@section('content')
<div class="col-lg-12">
	<div class="row">
		<div class="col-lg-12">
			<h5>Detalhes - Pedido nº {{ $pedido['id'] }} - Vendedor: {{ $pedido['vendedor'] }} @if(!empty($pedido['supervisor'])) - Gerente: {{ $pedido['supervisor'] }}@endif - Tipo de venda: {!! $pedido['tipo_venda'] !!}</h5>
			<input type="button" id="bt_imprimir" name="bt_imprimir" onclick="imprimir('{{ $pedido['id'] }}')" value="Imprimir Pedido" />
		</div>
	</div>
	<hr>
	<div class='pedido_detalhes_content'>
		@if (!is_null($pedido['pedido_gerado']))
		<div class="row">
			<div class="col-sm-2">
				<b>Pedido gerado: </b><br>
				{{ $pedido['pedido_gerado'] }}
			</div>
			@if (!empty($pedido['pedido_assinatura']))
			<div class="col-sm-2">
				<b>Pedido Assinado: </b><br>
				<a href="{{ asset($pedido['pedido_assinatura']) }}" target="_blank"><i class="btn-pdf"></i></a>
			</div>
			@endif
			@if(!empty($pedido['pedido_compras']))
				<div class="col-sm-2">
					<b>Pedido Compra: </b><br>
					{{ $pedido['pedido_compras'] }}
				</div>
			@endif
			@if(!empty($pedido['pedido_remessa']))
				<div class="col-sm-2">
					<b>Pedido Remessa: </b><br>
					{{ $pedido['pedido_remessa'] }}
				</div>
			@endif
		</div>
		@elseif(!empty($pedido['pedido_assinatura']))
		<div class="row">
			<div class="col-sm-2">
				<b>Pedido Assinado: </b><br>
				<a href="{{ asset($pedido['pedido_assinatura']) }}" target="_blank"><i class="btn-pdf"></i></a>
			</div>
		</div>
		@endif
		
		
		<div class="row">
			<div class="col-sm-2">
				<b>Estabelecimento:</b><br>
				{{ $pedido['estabelecimento'] }}
			</div>
			<div class="col-sm-4">
				<b>Cliente:</b><br>
				{{ $pedido['cliente']['nome'] }}
			</div>
			<div class="col-sm-2">
				<b>Destino:</b><br>
				{{ $pedido['cliente']['cidade_uf'] }}
			</div>
			<div class="col-sm-2">
				<b>Tipo de venda:</b><br>
				{!! $pedido['tipo_venda'] !!}
			</div>
			<div class="col-sm-2">
				<b>Status do pedido:</b><br>
				{{ $pedido['status'] }}
			</div>
		</div>
		@if ($pedido['conta_e_ordem'] == 'Sim')
		<div class="row ">
			<div class="col-sm-12">
				<b>Cliente da conta e ordem</b><br>
				{{ $pedido['cliente_conta_e_ordem'] }}
			</div>
		</div>
		@endif
		<div class="row ">
			<div class="col-sm-3">
				<b>Data do pedido:</b><br>
				{{ $pedido['data_pedido'] }}
			</div>
			<div class="col-sm-3">
				@if($pedido['pedido_futuro'] === true)
				<b>Pedido futuro</b><br>
				@else
				<b>Pronta entrega</b><br>
				@endif
			</div>
			<div class="col-sm-3">
				<b>Previsão de entrega / Data para Liberação:</b><br>
				{!! $pedido['data_previsao_entrega'] !!}
			</div>
			@if(!empty($pedido['aprovacao']))
			<div class="col-sm-3">
				<b>Aguardando decisão de:</b><br>
				{{ $pedido['aprovacao'] }}
			</div>
			@endif
		</div>
		<div class="row ">
			<div class="col-sm-3">
				<b>Condição de pagamento:</b><br>
				{{ $pedido['condicao_pagamento_descr'] }}
			</div>
			@if(!empty($pedido['tipo_pagamento']))
			<div class="col-sm-3">
				<b>{{ $pedido['tipo_pagamento'] }}</b><br />
				@if(!empty($pedido['motivo_recusa']))
				<b>Motivo recusa:</b> {{ $pedido['motivo_recusa'] }}<br />
				@endif
				@if(!empty($pedido['link_pagamento']))
				@if($pedido['link_pagamento'] != 'pago')
				<div class="input-group">
					<input type="text" id="link_pagamento" name="link_pagamento" value="{{ $pedido['link_pagamento'] }}" class="form-control">
					<div class="input-group-append">
						<span class="input-group-text" id="basic-addon2"><a href="#" onclick="copiarlink()" class="bt-duplicar" data-toggle="tooltip" data-html="true" title="Copiar Link "></a></span>
					</div>
				</div>
				@else
				<b>Pedido Pago</b>
				@endif
				@endif
				@if($pedido['retorno_log_pagarme'] != '')
					<a href="#" class="d-inline-block" data-toggle="popover" data-html="true" data-trigger="hover" data-content="{{ $pedido['retorno_log_pagarme'] }}"><i class="fas fa-money-check-alt"></i></a>
				@endif
			</div>
			@endif
			@if(!empty($pedido['link_proposta']))
			<div class="col-sm-3">
				<b>Proposta</b>
				<div class="input-group">
					<input type="text" id="link_proposta" name="link_proposta" value="{{ $pedido['link_proposta'] }}" class="form-control">
					<div class="input-group-append">
						<span class="input-group-text" id="basic-addon2"><a href="#" onclick="copiarlinkproposta()" class="bt-duplicar" data-toggle="tooltip" data-html="true" title="Copiar Link "></a></span>
					</div>
				</div>
			</div>
			@endif
			@if($pedido['exibir_credito'])
			<div class="col-sm-3">
				<b>Créditos:</b><br>
				{{ $pedido['credito_do_cliente'] }}
			</div>
			@endif
			<div class="col-sm-3">
				<b>Peso total do pedido:</b><br>
				{{ $pedido['peso_total'] }}
			</div>
			<div class="col-sm-3">
				<b>Frete usado para calcular os preços:</b><br>
				{{ $pedido['frete_preco'] }}
			</div>
		</div>
		<div class="row ">
			<div class="col-sm-6">
				<b>Transportadora:</b><br>
				{{ $pedido['transportadora']['nome'] }}
			</div>
			<div class="col-sm-3">
				<b>Tipo de Frete:</b><br>
				{{ $pedido['transportadora']['tipo_frete'] }}
			</div>
			@if($pedido['transportadora']['valor_frete'] != '0,00')
			<div class="col-sm-3">
				<b>Valor do Frete:</b><br>
				{{ $pedido['transportadora']['valor_frete'] }}
			</div>
			@endif
		</div>
		@if(!empty($pedido['transportadora_redespacho']['nome']))
		<div class="row ">
			<div class="col-sm-6">
				<b>Transportadora Redespacho:</b><br>
				{{ $pedido['transportadora_redespacho']['nome'] }}
			</div>
			<div class="col-sm-3">
				<b>Tipo de Frete:</b><br>
				{{ $pedido['transportadora_redespacho']['tipo_frete'] }}
			</div>
			@if($pedido['transportadora_redespacho']['valor_frete'] != '0,00')
			<div class="col-sm-3">
				<b>Valor do Frete:</b><br>
				{{ $pedido['transportadora_redespacho']['valor_frete'] }}
			</div>
			@endif
		</div>
		@endif
		<div class="row ">
			<div class="col-sm-5">
				<b>Nome do contato:</b><br>
				{!! $pedido['nome_comprador'] !!}
			</div>    
			<div class="col-sm-5">
				<b>Email do contato:</b><br>
				{!! $pedido['email_comprador'] !!}
			</div>
		</div>
		@if(!empty($pedido['agente_venda']))
		<div class="row ">
			<div class="col-sm-6">
				<b>Agente de venda:</b><br>
				{{ $pedido['agente_venda'] }}
			</div>
		</div>
		@endif
		<div class="row">
			<div class="col-sm-3">
				<b>Criado por:</b> <br>
				{!! $pedido['created_by'] !!}
			</div>
			<div class="col-sm-3">
				<b>Criado em:</b> <br>
				{!! $pedido['created_at'] !!}
			</div>
			<div class="col-sm-3">
				<b>Atualizado por:</b><br>
				{!! $pedido['updated_by'] !!}
			</div>
			<div class="col-sm-3">
				<b>Atualizado em:</b><br>
				{!! $pedido['updated_at'] !!}
			</div>
			@if(!empty($pedido['deleted_by']))
			<div class="col-sm-3">
				<b>Cancelado por:</b><br>
				{!! $pedido['deleted_by'] !!}
			</div>
			<div class="col-sm-3">
				<b>Cancelado em:</b><br>
				{!! $pedido['deleted_at'] !!}
			</div>
			@endif
		</div>

		@if(!empty($pedido['aprovador_credito']) || !empty($pedido['aprovador_preco']))
		<div class="row">
			@if(!empty($pedido['aprovador_credito']))
			<div class="col-sm-3">
				<b>Aprovador de crédito:</b> <br>
				{!! $pedido['aprovador_credito'] !!}
			</div>
			@endif
			@if(!empty($pedido['aprovador_preco']))
			<div class="col-sm-3">
				<b>Aprovador de preços:</b> <br>
				{!! $pedido['aprovador_preco'] !!}
			</div>
			@endif
		</div>
		@endif

		<div class="row ">
			<div class="col-sm-3">
				<b>Observação: </b><br>
				{!! $pedido['observacao'] !!}
			</div>
			@if(!empty($pedido['no_pedido_compra']))
			<div class="col-sm-3">
				<b>Pedido compra: </b><br>
				{{ $pedido['no_pedido_compra'] }}
			</div>
			@endif
		</div>
	</div>
</div>
<hr>
<div class="content-dialog-table">
	<div class="content-table">
		<table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-pedidos-itens">
			<thead>
				<tr>
					<th>Código</th>
					<th>Descrição</th>
					<th class="tb_number">Quantidade</th>
					<th class="tb_number">Peso total</th>
					<th class="tb_number">Comissão</th>
					@if($pedido['metragem_exata'] == true)
					<th class="tb_number">Preço Digitado</th>
					@endif
					<th class="tb_number">Preço unitário</th>
					<th class="tb_number">Valor total</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($pedido['itens'] as $value)
				<tr>
					@if(!empty($value['campanha_nome']))
						<td><b>{{ $value['cod_produto'] }}</b></td>
						<td><b>{{ $value['descricao'] }}</b></td>
						<td><b>{{ $value['quantidade'] }}</b></td>
						<td><b>{{ $value['peso_total'] }}</b></td>
						<td><b>{!! $value['comissao'] !!}</b><i class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="Campanha {{ $value['campanha_nome'] }} {{ $value['campanha_informativo'] }}" style="color: black;"></i></td>
						@if($pedido['metragem_exata'] == true)
							<td><b>{{ $value['preco_original'] }}</b></td>
						@endif
						<td><b>{{ $value['preco_unitario'] }}@if($value['tem_ipi'] == true) <span class="text-danger" data-toggle="tooltip" data-placement="left" data-html="true" title="Preço com IPI" data-content="">*</span>@endif</b></td>
						<td><b>{{ $value['valor_total'] }}</b></td>
					@else
						<td>{{ $value['cod_produto'] }}</td>
						<td>{{ $value['descricao'] }}</td>
						<td>{{ $value['quantidade'] }}</td>
						<td>{{ $value['peso_total'] }}</td>
						<td>{!! $value['comissao'] !!}</td>
						@if($pedido['metragem_exata'] == true)
							<td>{{ $value['preco_original'] }}</td>
						@endif
						<td>{{ $value['preco_unitario'] }}@if($value['tem_ipi'] == true) <span class="text-danger" data-toggle="tooltip" data-placement="left" data-html="true" title="Preço com IPI" data-content="">*</span>@endif</td>
						<td>{{ $value['valor_total'] }}</td>
					@endif
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
</div>
<div class="container m-4 w-100 mw-100" style="clear: both;">
	<div class='row mr-3'>
		<div class="col-sm-8"></div>
		<div class='col-sm-2'>Total dos Produtos</div>
		<div class='col-sm-2 text-right'>{{ $pedido['valor_total_itens'] }}</div>
	</div>
	<div class='row mr-3'>
		<div class="col-sm-8"></div>
		<div class='col-sm-2'>Total de Frete</div>
		<div class='col-sm-2 text-right'>{{ $pedido['valor_frete'] }}</div>
	</div>
	<div class='row mr-3'>
		<div class="col-sm-8"></div>
		<div class='col-sm-2'>Desconto em nota</div>
		<div class='col-sm-2 text-right'>{{ $pedido['valor_desconto'] }}</div>
	</div>
	<div class='row mr-3'>
		<div class="col-sm-8"></div>
		<div class='col-sm-2 border-top'><b>Total do Pedido</b></div>
		<div class='col-sm-2 text-right border-top'><b>{{ $pedido['valor_total_nota'] }}</b></div>
	</div>
</div>
<script>
    table_filters_produtos_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": 15,
        "processing": true,
        "orderMulti": false,
        "scrollCollapse": true,
        "scrollY": "35vh",
        "drawCallback": function(settings) {
            $(document).find('.estoque, .preco').popover({
                container: 'body',
                html: true,
                show: true,
                trigger: 'manual'
            });
        },
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
        "columnDefs": [
            {
                "class": "tb_number", 
                "targets": "tb_number"
            },
        ],
        "order": [[ 2, 'asc' ]]
    };
	table_filters_pedido_itens = $(document).find("#table-filters-pedidos-itens").DataTable(table_filters_produtos_options);
	$(document).ready(function(){
		setTimeout(function() {
	    	table_filters_pedido_itens.draw();
		}, 500);
		$(document).find('[data-toggle="popover"]').popover()
	})
	function imprimir($pedido){
		event.stopPropagation();
		var $form = document.createElement('form');
		$form.name = 'form_imprimir';
		$form.method = 'POST';
		$form.target = '_blanck';
		$form.action = '{{ route("pedido_portal.detalhes.imprimir") }}';
		
		var $input_form = document.createElement('INPUT');
		$input_form.type = 'TEXT';
		$input_form.name = 'pedido';
		$input_form.value = $pedido;
		$form.appendChild($input_form);

		var $input_form = document.createElement('INPUT');
		$input_form.type = 'TEXT';
		$input_form.name = '_token';
		$input_form.value = '{{ csrf_token() }}';
		$form.appendChild($input_form);

		document.body.appendChild($form);
		$form.submit();
		$form.remove();
	}
	function copiarlink() {
		var textoCopiado = document.getElementById("link_pagamento");
		textoCopiado.select();
		document.execCommand("Copy");
	}
	function copiarlinkproposta() {
		var textoCopiado = document.getElementById("link_proposta");
		textoCopiado.select();
		document.execCommand("Copy");
	}
</script>
@endsection
