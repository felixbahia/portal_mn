@extends('layouts.page-dialog')

@section('content')
<form action="#" method="post" id="cadastro_cliente_novo" name="cadastro_cliente_novo" onsubmit="return false">
	@csrf
	<ul class="nav nav-tabs" id="PedidoOrcamentoTabs" role="tablist">
		<li class="nav-item">
			<a class="nav-link active" id="dados-tab" data-toggle="tab" href="#cliente_novo_dados" role="tab" aria-controls="cliente_novo_dados" aria-selected="true">Dados Básico</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" id="enderecos-tab" data-toggle="tab" href="#cliente_novo_enderecos" role="tab" aria-controls="cliente_novo_enderecos" aria-selected="false">Dados de Faturamento / Cobrança</a>
		</li>
		<li class="nav-item">
			<a class="nav-link" id="socios_referencias-tab" data-toggle="tab" href="#cliente_novo_socios_referencias" role="tab" aria-controls="cliente_novo_socios_referencias" aria-selected="false">Sócios e Referências</a>
		</li>
	</ul>
	<div class="tab-content" id="PedidoOrcamentoTabsContent">
		<div class="tab-pane show active" id="cliente_novo_dados" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-tab">
				<div class="row">
					<div class="form-group col-md-12">
						<b>Já foi cliente?</b> {!! ($dados['ja_foi_cliente'] === 'nao' ? "Não" : "SIM") !!}
						@if($dados['ja_foi_cliente'] === 'nao')
							{!! $dados['ja_foi_cliente_quando'] !!}
						@endif
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-2">
						@if(strtolower($dados['fisica_juridica']) == "j")
						<b>Fisica / Juridica: </b> Juridica
						@else
						<b>Fisica / Juridica: </b> Fisica
						@endif
					</div>
					@if($dados['fisica_juridica'] === "f")
					<div class="form-group col-md-4">
						<b>CPF: </b>
						{!! $dados['cpf_cnpj'] !!}
					</div>
					@else
					<div class="form-group col-md-2">
						<b>CNPJ: </b>
						{!! $dados['cpf_cnpj'] !!}
					</div>
					<div class="form-group col-md-3">
						<b>Inscrição estadual: </b>
						{!! $dados['inscricao_estadual'] !!}
					</div>
					<div class="form-group col-md-3">
						<b>Inscrição municipal: </b>
						{!! $dados['inscricao_municipal'] !!}
					</div>
					<div class="form-group col-md-2">
						<b>SUFRAMA: </b>
						{!! $dados['suframa'] !!}
					</div>
					@endif
				</div>
				<div class="row">
					<div class="form-group col-md-6">
						<b>Nome / Razão: </b>
						{!! $dados['nome_razao'] !!}
					</div>
					<div class="form-group col-md-6">
						<b>Nome Fantasia / Apelido: </b>
						{!! $dados['guerra_apelido'] !!}
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-3">
						<b>Telefone: </b>
						{!! $dados['telefone'] !!}
					</div>
					<div class="form-group col-md-3">
						<b>Fax: </b>
						{!! $dados['telefone_fax'] !!}
					</div>
					<div class="form-group col-md-6">
						<b>E-mail: </b>
						{!! $dados['email'] !!}
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-6">
						<b>Vendedor: </b>
				        {!! $dados['vendedor']['codigo'] !!} - {!! $dados['vendedor']['nome'] !!}
					</div>
					<div class="form-group col-md-6">
						<b>Transportador: </b>
				        {!! $dados['transportador']['codigo'] !!} - {!! $dados['transportador']['nome'] !!}
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-6">
						<b>Forte do cliente: </b>
						@if($dados['forte_cliente'] === 'confecção')
						Confecção
						@endif
						@if($dados['forte_cliente'] === 'atacado')
						Atacado
						@endif
						@if($dados['forte_cliente'] === 'p')
						P
						@endif
						@if($dados['forte_cliente'] === 'm')
						M
						@endif
						@if($dados['forte_cliente'] === 'g')
						M
						@endif
					</div>
					<div class="form-group col-md-6">
						<b>Ramo de Atividade: </b>
						@if($dados['ramo_atividade'] === 'COM.')
						COM.
						@endif
						@if($dados['ramo_atividade'] === 'IND.')
						IND.
						@endif
						@if($dados['ramo_atividade'] === 'Prest. Serv.')
						Prest. Serv.
						@endif
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-3">
						<b>Número filiais: </b>
						{!! $dados['numero_filiais'] !!}
					</div>
					<div class="form-group col-md-3">
						<b>Número de Empregados: </b>
						{!! $dados['numero_empregados'] !!}
					</div>
					<div class="form-group col-md-6">
						<b>Prédio próprio</b>
						@if($dados['predio_proprio'] === 'sim')
						Sim
						@else
						Não <b>Aluguel:</b> {!! $dados['aluguel'] !!}
						@endif
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-6">
						<b>Sucessora de: </b>
						{!! $dados['sucessora_de'] !!}
					</div>
					<div class="form-group col-md-6">
						<b>Ligações com outras firmas: </b>
						{!! $dados['ligacao_com'] !!}
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-6">
						<b>Sugestão de crédito avaliado pelo representante: </b>
						{!! $dados['sugestao_credito'] !!}
					</div>
					<div class="form-group col-md-6">
						<b>Histórico do cliente na praça: </b>
						{!! $dados['historico_cliente_praca'] !!}
					</div>
				</div>
			</div>
		</div>
		<div class="tab-pane" id="cliente_novo_enderecos" role="tabpanel" aria-labelledby="enderecos-tab">
			<div class="content-tab">
				<div class="row">
					<div class="col-md-12">
						<h3><center>Dados para Faturamento</center></h3>
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-2">
						<b>CEP: </b>
						{!! $dados['faturamento_cep'] !!}
					</div>
					<div class="form-group col-md-4">
						<b>Logradouro: </b>
						{!! $dados['faturamento_logradouro'] !!}
					</div>
					<div class="form-group col-md-3">
						<b>Número: </b>
						{!! $dados['faturamento_numero'] !!}
					</div>
					<div class="form-group col-md-3">
						<b>Complemento: </b>
						{!! $dados['faturamento_complemento'] !!}
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-4">
						<b>Bairro: </b>
						{!! $dados['faturamento_bairro'] !!}
					</div>
					<div class="form-group col-md-4">
						<b>Cidade: </b>
						{!! $dados['faturamento_cidade'] !!}
					</div>
					<div class="form-group col-md-4">
						<b>Estado: </b>
						{!! $dados['faturamento_estado'] !!}
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-3">
						<b>Telefone: </b>
						{!! $dados['faturamento_telefone'] !!}
					</div>
					<div class="form-group col-md-3">
						<b>Fax: </b>
						{!! $dados['faturamento_telefone_fax'] !!}
					</div>
					<div class="form-group col-md-6">
						<b>E-mail: </b>
						{!! $dados['faturamento_email'] !!}
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<hr>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<h3><center>Dados para Cobrança</center></h3>
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-2">
						<b>CEP: </b>
						{!! $dados['cobranca_cep'] !!}
					</div>
					<div class="form-group col-md-4">
						<b>Logradouro: </b>
						{!! $dados['cobranca_logradouro'] !!}
					</div>
					<div class="form-group col-md-3">
						<b>Número: </b>
						{!! $dados['cobranca_numero'] !!}
					</div>
					<div class="form-group col-md-3">
						<b>Complemento: </b>
						{!! $dados['cobranca_complemento'] !!}
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-4">
						<b>Bairro: </b>
						{!! $dados['cobranca_bairro'] !!}
					</div>
					<div class="form-group col-md-4">
						<b>Cidade: </b>
						{!! $dados['cobranca_cidade'] !!}
					</div>
					<div class="form-group col-md-4">
						<b>Estado: </b>
						{!! $dados['cobranca_estado'] !!}	    		
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-3">
						<b>Telefone: </b>
						{!! $dados['cobranca_telefone'] !!}
					</div>
					<div class="form-group col-md-3">
						<b>Fax: </b>
						{!! $dados['cobranca_telefone_fax'] !!}
					</div>
					<div class="form-group col-md-2">
						<b>Banco: </b>
						{!! $dados['cobranca_banco'] !!}
					</div>
					<div class="form-group col-md-2">
						<b>Agência: </b>
						{!! $dados['cobranca_agencia'] !!}
					</div>
					<div class="form-group col-md-2">
						<b>Conta: </b>
						{!! $dados['cobranca_conta'] !!}
					</div>
				</div>
			</div>
		</div>
		<div class="tab-pane" id="cliente_novo_socios_referencias" role="tabpanel" aria-labelledby="socios_referencias-tab">
			<div class="content-tab">
				<div class="row">
					<div class="col-md-12">
						<h3><center>Sócios ou Diretores</center></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div class="content-dialog-table">
							<table class="table table-striped table-not-edit table-not-view" id="table_socio_diretor_view">
								<thead>
									<tr>
										<th>Nome</th>
										<th>CPF</th>
										<th>PARTE %</th>
									</tr>
								</thead>
								<tbody>
									@foreach($dados["socios"] as $value)
									<tr>
										<td>{!! $value["nome"] !!}</td>
										<td>{!! $value["cpf"] !!}</td>
										<td>{!! $value["parte"] !!}</td>
									</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<hr>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<h3><center>Referências Comerciais</center></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div class="content-dialog-table">
							<table class="table table-striped table-not-edit table-not-view" id="table_referencia_comercial_view">
								<thead>
									<tr>
										<th>Empresa</th>
										<th>Contato</th>
										<th>DDD</th>
										<th>Telefone</th>
										<th>Estado</th>
										<th>Cidade</th>
									</tr>
								</thead>
								<tbody>
									@foreach($dados["referencias"] as $value)
									<tr>
										<td>{!! $value["empresa"] !!}</td>
										<td>{!! $value["contato"] !!}</td>
										<td>{!! $value["telefone_ddd"] !!}</td>
										<td>{!! $value["telefone"] !!}</td>
										<td>{!! $value["estado"] !!}</td>
										<td>{!! $value["cidade"] !!}</td>
									</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="content_buttons">
		<div class="form-group col-md-12">
			{!! Form::button('<< Dados Basicos', ['class' => 'btn float-left troca-aba btn-info', 'id' => 'voltar_basico']) !!}
			{!! Form::button('<< Dados de Faturamento / Cobrança', ['class' => 'btn float-left troca-aba btn-info', 'id'=>'voltar_endereco_faturamento_cobranca']) !!}
			{!! Form::button('Dados de Faturamento / Cobrança >>', ['class' => 'btn float-right troca-aba btn-info', 'id'=>'avancar_endereco_faturamento_cobranca']) !!}
			{!! Form::button('Sócios e Referências >>', ['class' => 'btn float-right troca-aba btn-info', 'id'=>'avancar_socio_refencia']) !!}
		</div>
	</div>
</form>
<script type="text/javascript">
	$(document).find('#cadastro_cliente_novo').find("#voltar_basico").hide();
	$(document).find('#cadastro_cliente_novo').find("#voltar_endereco_faturamento_cobranca").hide();
	$(document).find('#cadastro_cliente_novo').find("#avancar_socio_refencia").hide();

	var table_socio_diretor_view, table_referencia_comercial_view;
	setTimeout(function(){
		var $height = ($(document).find('#cadastro_cliente_novo').height() / 2) - 250;
		table_socio_diretor_view = $(document).find('#cadastro_cliente_novo').find("#table_socio_diretor_view").DataTable({
	        "searching": false,
	        "lengthChange": false,
	        "info": false,
	        "scrollX": false,
	        "scrollY": $height,
	        "scrollCollapse": true,
	        "paging": false,
	        "autoWidth": false,
	        "orderMulti": false,
	        "language": {
	            "decimal":        ".",
	            "thousands":      ",",
	            "emptyTable":     "Nenhum Sócio ou Diretor informado",
	            "infoPostFix":    " ",
	            "loadingRecords": "Carregando...",
	            "processing":     "Processando...",
	            "zeroRecords":    " ",
	        }
	    });
		table_referencia_comercial_view = $(document).find('#cadastro_cliente_novo').find("#table_referencia_comercial_view").DataTable({
	        "searching": false,
	        "lengthChange": false,
	        "info": false,
	        "scrollX": false,
	        "scrollY": $height,
	        "scrollCollapse": true,
	        "paging": false,
	        "autoWidth": false,
	        "orderMulti": false,
	        "language": {
	            "decimal":        ".",
	            "thousands":      ",",
	            "emptyTable":     "Nenhuma Referência Comercial informada",
	            "infoPostFix":    " ",
	            "loadingRecords": "Carregando...",
	            "processing":     "Processando...",
	            "zeroRecords":    " "
	        }
	    });
	}, 500);
	$(function(){
		$(document).find('#cadastro_cliente_novo').find('a[data-toggle="tab"]').off('shown.bs.tab');
		$(document).find('#cadastro_cliente_novo').find('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			table_socio_diretor_view.draw();
			table_referencia_comercial_view.draw();
			$(document).find('#cadastro_cliente_novo').find("#voltar_basico").hide();
			$(document).find('#cadastro_cliente_novo').find("#voltar_endereco_faturamento_cobranca").hide();
			$(document).find('#cadastro_cliente_novo').find("#avancar_endereco_faturamento_cobranca").hide();
			$(document).find('#cadastro_cliente_novo').find("#avancar_socio_refencia").hide();
			if($(e.target).attr("id") === 'enderecos-tab'){
				$(document).find('#cadastro_cliente_novo').find("#voltar_basico").show();
				$(document).find('#cadastro_cliente_novo').find("#avancar_socio_refencia").show();
			}
			if($(e.target).attr("id") === 'socios_referencias-tab'){
				$(document).find('#cadastro_cliente_novo').find("#voltar_endereco_faturamento_cobranca").show();
			}
			if($(e.target).attr("id") === 'dados-tab'){
				$(document).find('#cadastro_cliente_novo').find("#avancar_endereco_faturamento_cobranca").show();
			}
		});
		$(document).find('#cadastro_cliente_novo').find("#avancar_endereco_faturamento_cobranca, #voltar_endereco_faturamento_cobranca").off("click");
		$(document).find('#cadastro_cliente_novo').find("#avancar_endereco_faturamento_cobranca, #voltar_endereco_faturamento_cobranca").on("click", function(){
			$('#enderecos-tab').tab('show');
		});
		$(document).find('#cadastro_cliente_novo').find("#avancar_socio_refencia").off("click");
		$(document).find('#cadastro_cliente_novo').find("#avancar_socio_refencia").on("click", function(){
			$('#socios_referencias-tab').tab('show');
		});
		$(document).find('#cadastro_cliente_novo').find("#voltar_basico").off("click");
		$(document).find('#cadastro_cliente_novo').find("#voltar_basico").on("click", function(){
			$('#dados-tab').tab('show');
		});
	});
</script>
@endsection
