@extends('layouts.page-dialog')

@section('content')
<div id="tudo">
	<div class="border-bottom" id="info-sintese" >
		<div class="row">
			<div class="row mx-1 border-bottom titulo-bootstrap">
				<div class="col-lg-12"><b>Pedido:</b> {!! $return['pedido'] !!} - <b>Cliente:</b> {!! $return['cliente'] !!} - <b>Valor:</b> R$ {!! $return['valor'] !!} - <b>Código do Vendedor:</b> {!! $return['vendedor']??'Sem código definido' !!} - <b>Tipo de venda:</b> {!! $return['tipo_venda'] !!}</div>
			</div>
			<hr>
			@if($return['mostrar_botao'] === true)
			<div class="row-botoes-aprovacao-pedidos">
				<div class="bt-reprove" data-toggle="tooltip" data-trigger='hover' title="Reprovar" onclick="recusarPedido('{{ $return['id_aprovacao'] }}')"></div>
				@if(
					strtolower(Auth::user()->tipo_usuario->nome) === "credito" ||
					strtolower(Auth::user()->tipo_usuario->nome) === "administrador"
				)
				<div class="bt-retaguarda" data-toggle="tooltip" data-trigger='hover' title="Retaguarda" onclick="retaguardaPedido('{{ $return['pedido'] }}')"></div>
				@endif
				<div class="bt-aprove" data-toggle="tooltip" data-trigger='hover' title="Aprovar" onclick="aprovarPedido('{{ $return['pedido'] }}')"></div>
			</div>
			<hr>
			@endif
			<div class="col-lg-6 my-6" id='posicao_financeira'>
				<div class="row">
					<div class="col-lg-12">
						<h5 class='titulo-credito'>Posição Financeira Atual <button id='btn-posicao-sintetica' type='button' class="btn btn-primary btn-xs ml-5" onclick="posicaoSinteticaModal()">Análise sintética</button> </h5>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6">
						<b>Médio de atraso</b>
					</div>
					<div class="col-lg-6">
						{!! $return['media_atraso'] !!}
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6">
						<b>Maior atraso</b>
					</div>
					<div class="col-lg-6">
						{!! $return['maior_atraso'] !!}
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6">
						<b>Inatividade</b>
					</div>
					<div class="col-lg-6">
						{!! $return['inatividade'] !!}
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6">
						<b>Limite de Crédito</b>
					</div>
					<div class="col-lg-6">
						{!! $return['limite_credito'] !!}
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6">
						<b>Crédito válido ate</b>
					</div>
					<div class="col-lg-6">
						{!! $return['data_limite_credito'] !!}
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6">
						<b>Limite de Crédito Disponível</b>
					</div>
					<div class="col-lg-6">
						{!! $return['limite_disponivel'] !!}
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6">
						<b>Pedidos em aberto</b>
					</div>
					<div class="col-lg-6">
						{!! $return['pedidos_em_aberto'] !!}
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6">
						<b>Duplicatas em aberto</b>
					</div>
					<div class="col-lg-6">
						{!! $return['duplicatas_em_aberto'] !!}
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6">
						<b>Duplicatas vencidas (dias)</b>
					</div>
					<div class="col-lg-6">
						{!! $return['duplicatas_vencidas']['dias'] !!}
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6">
						<b>Último atraso</b>
					</div>
					<div class="col-lg-6">
						{!! $return['ultimo_atraso'] !!}
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6">
						<b>Cheques em aberto - Lucros e perdas</b>
					</div>
					<div class="col-lg-6">
						{!! $return['lucros_e_perdas'] !!}
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6">
						<b>Cheques em negociação</b>
					</div>
					<div class="col-lg-6">
						{!! $return['negociacao'] !!}
					</div>
				</div>
				@if (!empty($return['notas_credito']))
				<div class="row">
					<div class="col-lg-6">
						<b>Notas de crédito</b>
					</div>
					<div class="col-lg-6">
						{!! $return['notas_credito'] !!}
					</div>
				</div>	
				@endif
				<div class="row">
					<div class="col-lg-6">
						<b>Titulos Prorrogados</b>
					</div>
					<div class="col-lg-6">
						{!! $return['prorrogacao'] !!}
					</div>
				</div>
				<div class="row">
					<div class="col-lg-6">
						<b>Titulos Pré pago</b>
					</div>
					<div class="col-lg-6">
						{!! $return['titulos_prepago'] !!}
					</div>
				</div>
			</div>
			<div class="col-lg-6 my-6">
				<div class="row border-bottom">
					<div class="col-lg-12">
						<div class="row">
							<div class="col-lg-12">
								<h5 class='titulo-credito'>Preços e condições</h5>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-4">
								<b>Condição de Pagamento</b> 
							</div>
							<div class="col-lg-8">
								{!! $return['condicao_pagamento'] !!}
							</div>
						</div>
						<div class="row">
							<div class="col-lg-4">
								<b>Preços</b> 
							</div>
							<div class="col-lg-8">
								{!! $return['precos'] !!}
							</div>
						</div>
						<div class="row">
							<div class="col-lg-4">
								<b>Natureza de Operação</b> 
							</div>
							<div class="col-lg-8">
								{!! $return['natureza_operacao'] !!}
							</div>
						</div>

						@if (isset($return['desconto_em_nota']))
						<div class="row">
							<div class="col-lg-4">
								<b>Desconto em nota</b> 
							</div>
							<div class="col-lg-8">
								{!! $return['desconto_em_nota'] !!}
							</div>
						</div>
						@endif
					
					</div>
				</div>
				<div class="row my-2">
					<div class="col-lg-12 border-bottom">
						<div class="row">
							<div class="col-lg-12">
								<h5 class='titulo-credito'>Informações Adicionais</h5>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-4">
								<b>Data de entrega</b>
							</div>
							<div class="col-lg-8">
								{!! $return['data_entrega'] !!}
							</div>					
						</div>
						<div class="row">
							<div class="col-lg-4">
								<b>Tipo de Frete</b>
							</div>
							<div class="col-lg-8">
								{!! $return['tipo_frete'] !!}
							</div>					
						</div>
					
						<div class="row">
							<div class="col-lg-4">
								<b>Vendedor</b>
							</div>
							<div class="col-lg-8">
								{!! $return['usuario'] !!}
							</div>					
						</div>

						@if (!empty($return['erro_integracao']))
						<div class="row">
							<div class="col-lg-4">
								<b>Erro de integração</b>
							</div>
							<div class="col-lg-8">
								{!! $return['erro_integracao'] !!}
							</div>					
						</div>
						@endif
						<div class="row">
							<div class="col-lg-4">
								<b>Preço da comparação</b>
							</div>
							<div class="col-lg-8">
								{!! strtoupper($return['preco']) !!}
							</div>
						</div>
						<div class="row">
							<div class="col-lg-4">
								<b>Localização do Cliente</b>
							</div>
							<div class="col-lg-8">
								{!! $return['cidade_estado'] !!}
							</div>
						</div>
					</div>
				</div>
				<div class="row my-2">
					<div class="col-lg-12">
						<div class="row">
							<div class="col-lg-12">
								<h5 class='titulo-credito'>Informações de prazo</h5>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-4">
								<b>Prazo médio do pedido</b>
							</div>
							<div class="col-lg-8">
								{!! $return['prazo_pedido'] !!}
							</div>
						</div>						
					</div>
				</div>
			</div>
		</div>
	</div>
	@if (count($return['itens']) > 0)
	<div class="row mt-3">
		<div class="col-lg-12">
			@if(!empty($return['campanhas']))
				<h5 class='titulo-credito'>Itens do Pedido</h5><b> Campanha(s): {{ $return['campanhas'] }}</b>
			@else
				<h5 class='titulo-credito'>Itens do Pedido</h5>
			@endif
		</div>
	</div>
	<div class="content-dialog-table" style="overflow: auto">
		<table class="table table-striped table-filter table-not-edit table-not-view " id="table-filters-itens">
			<thead>
				<tr>
					<th>Grupo</th>
					<th>Código</th>
					<th>Descrição</th>
					<th>Marca</th>
					<th>Linha</th>
					<th class="tb_number">VL Pedido</th>
					@if(empty($return['projeto']))
					<th class="tb_number">VL Tabela</th>
					@else
					<th class="tb_number">VL Projeto</th>
					@endif
					<th class="tb_number">% Desconto</th>
					<th class="tb_number">Comissão</th>
					@if(Auth::user()->tipo_usuario->nivel < 1)
						<th class="tb_number">Custo</th>
					
						<th class="tb_number">% Margem</th>
						<th class="tb_number">% Markup</th>
					@endif
					<th class="tb_number">Qtd.</th>
					<th class="tb_number">VL Total</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($return['itens'] as $value)
				<tr {{ $value['erro_preco']? "class=error-tr" :'' }}>
					@if(!empty($value['campanha_nome']))
						<td><b>{!! $value['grupo'] !!}</b></td>		
						<td><b>{!! $value['codigo'] !!}</b></td>
						<td><b>{!! $value['descricao'] !!}</b></td>
						<td><b>{!! $value['marca'] !!}</b></td>
						<td><b>{!! $value['linha'] !!}</b></td>
						<td><b>{!! $value['preco'] !!}</b></td>
						@if(empty($return['projeto']))
							<td><b>{!! $value['valor_minimo'] !!}</b></td>
							<td><b>{!! $value['desconto'] !!}</b></td>
						@else
							<td><b>{!! $value['valor_minimo_projeto'] !!}</b></td>
							<td><b>{!! $value['desconto_projeto'] !!}</b></td>
						@endif
						@if($value['comissao_tipo'] == 1)
							<td><i class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="Campanha {{ $value['campanha_nome'] }} {{ $value['campanha_informativo'] }} {{ $value['data_hora'] }}" style="color: black;"></i> <b>{!! $value['comissao'] !!}</b></td>
						@else
							<td><i class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="Campanha {{ $value['campanha_nome'] }} {{ $value['data_hora'] }}" style="color: black;"></i> <b>{!! $value['comissao'] !!}</b></td>
						@endif
						@if(Auth::user()->tipo_usuario->nivel < 1)
							<td><b>{!! $value['custo'] !!}</b></td>
							<td><b>{!! $value['margem'] !!}</b></td>
							<td><b>{!! $value['markup'] !!}</b></td>
						@endif
						<td><b>{!! $value['quantidade'] !!}</b></td>
						<td><b>{!! $value['valor_total']!!}</b></td>
					@else
						<td>{!! $value['grupo'] !!}</td>		
						<td>{!! $value['codigo'] !!}</td>
						<td>{!! $value['descricao'] !!}</td>
						<td>{!! $value['marca'] !!}</td>
						<td>{!! $value['linha'] !!}</td>
						<td>{!! $value['preco'] !!}</td>
						@if(empty($return['projeto']))
							<td>{!! $value['valor_minimo'] !!}</td>
							<td>{!! $value['desconto'] !!}</td>
						@else
							<td>{!! $value['valor_minimo_projeto'] !!}</td>
							<td>{!! $value['desconto_projeto'] !!}</td>
						@endif
							<td>{!! $value['comissao'] !!}</td>
						@if(Auth::user()->tipo_usuario->nivel < 1)
							<td>{!! $value['custo'] !!}</td>
							<td>{!! $value['margem'] !!}</td>
							<td>{!! $value['markup'] !!}</td>
						@endif
						<td>{!! $value['quantidade'] !!}</td>
						<td>{!! $value['valor_total']!!}</td>
					@endif
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	@endif
</div>
<script>
	
	table_filters_itens = $('#table-filters-itens').DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"orderMulti": false,
		'paging': false,
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
		],
		"order": [[ 2, 'asc' ]]
	});

	function posicaoSinteticaModal(){

		$.ajax({
			url: '{{ route('cliente.posicao_sintetica.modal') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				id_encriptada: '{{ $return['id_encriptada'] }}'
			},

		})
		.done(function(data) {
			$id = 'modal_analise_cliente';
			$title = 'Posição sintética do cliente';
			$body = data;
			$class = 'modal-lg';

			createModal($id, $title, $body, $class);
		});
	}
	function addCredito(){

		$.ajax({
			url: '{{ route('cliente.limite.modal.adicionar_aprovacao') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				cpf_cnpj: '{{ $return['cpf_cnpj'] }}'
			},

		})
		.done(function(data) {
			$id = 'modal_add_credito_cliente';
			$title = 'Revisar crédito cliente';
			$body = data;
			$class = '';

			createModal($id, $title, $body, $class);
		});

	}

</script>
@endsection