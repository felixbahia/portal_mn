@extends('layouts.page-dialog')

@section('content')
<div class="row">
	<div class="col-sm-12">
		@if(in_array($dados['tipo'], ['Nacional', 'Importado', 'Uso e Consumo']))
			<b>Deseja realmente excluir está Previsão de Compras?</b>
		@else
			<b>Deseja realmente excluir está Previsão Bancos / Despesas?</b>
		@endif
	</div>
</div>
<br>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Tipo</b></div>
	<div class='col-sm-6'>{{ $dados['tipo'] }}</div>
</div>
@if(!empty($dados['fornecedor']))
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Fornecedor</b></div>
		<div class='col-sm-6'>{{ $dados['fornecedor'] }}</div>
	</div>
@endif
@if(in_array($dados['tipo'], ['Nacional', 'Importado', 'Uso e Consumo', 'Banco', 'Despesas']))
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Previsão da Compra</b></div>
		<div class='col-sm-6'>{{ $dados['compras_data'] }}</div>
	</div>	
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Condição de Pagamento</b></div>
		<div class='col-sm-6'>{{ $dados['condicao_pagamento'] }}</div>
	</div>
	</br>

	<ul class="nav nav-tabs">
		<li class="nav-item">
			<a class="nav-link active" id='orcamento_compras-competencia-tab' data-toggle="tab" href="#orcamento_compras_competencia" role="tab" aria-controls="orcamento_compras_competencia" aria-selected="true">Competência</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" id="orcamento_compras-fluxo_caixa-tab" data-toggle="tab" href="#orcamento_compras_fluxo_caixa" role="tab" aria-controls="orcamento_compras_fluxo_caixa" aria-selected="false">Fluxo Caixa</a>
		</li>
	</ul>
	<div class="tab-content pt-3" id="LancamentoProjetoHeaderContainer">
		<div class="tab-pane show active" id="orcamento_compras_competencia" role="tabpanel" aria-labelledby="dados-tab">
		
			<div class="row border-bottom">
				<div class='col-sm-6'><b>Mês/Ano</b></div>
				<div class='col-sm-6'>{{ $dados['mes_ano'] }}</div>
			</div>
			<div class="row border-bottom">
				<div class='col-sm-6'><b>Valor</b></div>
				<div class='col-sm-6'>{{ $dados['valor'] }}</div>
			</div>	
		</div>
		<div class="tab-pane" id="orcamento_compras_fluxo_caixa" role="tabpanel" aria-labelledby="dados-tab">
			@foreach($fluxos as $index => $fluxo)
				<div class="row border-bottom">
					<div class='col-sm-6'><b>Parcela</b></div>
					<div class='col-sm-6'>{{ $index + 1 }}</div>
				</div>
				<div class="row border-bottom">
					<div class='col-sm-6'><b>Data</b></div>
					<div class='col-sm-6'>{{ $fluxo['mes_ano'] }}</div>
				</div>
				<div class="row border-bottom">
					<div class='col-sm-6'><b>Valor</b></div>
					<div class='col-sm-6'>{{ $fluxo['valor'] }}</div>
				</div>
				</br>
				</br>
			@endforeach
		</div>
	</div>
@else
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Mês/Ano</b></div>
		<div class='col-sm-6'>{{ $dados['mes_ano'] }}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Valor</b></div>
		<div class='col-sm-6'>{{ $dados['valor'] }}</div>
	</div>
@endif
<br>
<div class="content-buttons float-right">
    <button name="btn-cancel" id="btn-cancel-delete" class="btn btn-primary text-right">Cancelar</button>
    <button name="btn-delete" id="btn-delete" class="btn btn-danger text-right">Excluir</button>
</div>
<script type="text/javascript">
	$(document).ready(function($) {
		$(document).find("#btn-cancel-delete").off("click");
		$(document).find("#btn-cancel-delete").on("click", function(event) {
			$(this).parents(".modal").modal("hide");
		});
		$(document).find("#btn-delete").off("click");
		$(document).find("#btn-delete").on("click", function(event) {
			var $this = $(this);
			$.ajax({
				url: "{{ route('orcamento_compras.deletar') }}",
				dataType: 'json',
				data: {_token: "{{ csrf_token() }}", id: '{{ $dados['id'] }}'},
				method: 'POST',
				success: function(callback){
					if(callback.status === "success"){
						$($this).parents(".modal").modal("hide");
                        filterAjax($(document).find("#form_filter").serialize());
						message("Atenção", "Orçamento Compras excluído com sucesso!");
					} else {
						message("Atenção", callback.message);
					}
				},
			});
		});
	});
</script>
@endsection