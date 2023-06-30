@extends('layouts.page-dialog')

@section('content')
@if(intval($dados["status"]) === 3)
<div class="alert alert-danger" role="alert">
	<b>{{ $dados["status_descricao"] }}</b> - Motivo informado para ser reprovado: {{ $dados["motivo_recusa"] }}
</div>
@endif
<form action="{{ route("cliente_novo.update") }}" method="post" id="cadastro_cliente_novo" name="cadastro_cliente_novo" onsubmit="return false">
	@csrf
	{!! Form::hidden("id", $dados["id"]) !!}
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
					<div class="col-md-12">
						<div class="row">
							<div class="col-md-12">
								<span class='campo_obrigatorio'>*</span> Já foi cliente?
							</div>
						</div>
						<div class='row mb-4'>
							<div class="form-check col-md-2 ml-3">
								{{ Form::radio('ja_foi_cliente', 'sim', ($dados['ja_foi_cliente'] == 'sim'), ['class' => 'form-check-input', 'id'=>'ja_foi_cliente_s']) }}
								{{ Form::label('ja_foi_cliente_s', 'Sim', ['class'=>'form-check-label']) }}
							</div>
							<div class="form-check col-md-2">
								{{ Form::radio('ja_foi_cliente', 'nao', ($dados['ja_foi_cliente'] == 'nao'), ['class' => 'form-check-input', 'id'=>'ja_foi_cliente_n']) }}
								{{ Form::label('ja_foi_cliente_n', 'Não', ['class'=>'form-check-label']) }}
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<div class="row">
							<div class="form-group col-md-12 ja_foi_cliente_s display_none">
								<span class='campo_obrigatorio'>*</span> {{ Form::label('ja_foi_cliente_quando', 'Quando?') }}
								{{ Form::text('ja_foi_cliente_quando', $dados['ja_foi_cliente_quando'], ['class' => 'form-control']) }}
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-2">
						<div class="row">
							<div class="form-check col-md-12">
								<span class='campo_obrigatorio'>*</span> Pessoa física ou jurídica?
							</div>
						</div>
						<div class="row">
							<div class="form-check col-md-5 ml-3">
								{{ Form::radio('fisica_juridica', 'juridica', (strtolower($dados['fisica_juridica']) === "j"), ['class' => 'form-check-input', 'id'=>'juridica']) }}
								{{ Form::label('juridica', 'Juridica', ['class'=>'form-check-label']) }}
							</div>
							<div class="form-check col-md-5">
								{{ Form::radio('fisica_juridica', 'fisica', (strtolower($dados['fisica_juridica']) === "f"), ['class' => 'form-check-input', 'id'=>'fisica']) }}
								{{ Form::label('fisica', 'Fisica', ['class'=>'form-check-label']) }}
							</div>
						</div>
					</div>
					<div class="form-group col-md-4 fisica">
						<span class='campo_obrigatorio'>*</span> {{ Form::label('cpf', 'CPF') }}
						{{ Form::text('cpf', (strtolower($dados['fisica_juridica']) === "f") ? $dados['cpf_cnpj'] : "", ['class' => 'form-control cpf', 'id' => 'cpf']) }}
					</div>
					<div class="form-group col-md-2 juridica">
						<span class='campo_obrigatorio'>*</span> {{ Form::label('cnpj', 'CNPJ') }}
						{{ Form::text('cnpj', $dados['cpf_cnpj'], ['class' => 'form-control cnpj', 'id' => 'cnpj']) }}
					</div>
					<div class="form-group col-md-2 juridica">
						<span class='campo_obrigatorio'>*</span> {{ Form::label('inscricao_estadual_indicador', 'Inscrição estadual indicador') }}
						{{ Form::select('inscricao_estadual_indicador', $inscricaoEstadual, $dados['inscricao_estadual_indicador'], ['class' => 'form-control', 'id' => 'inscricao_estadual_indicador']) }}
					</div>
					<div class="form-group col-md-2 juridica" id="inscricao_estadual_div">
						<span class='campo_obrigatorio'>*</span> {{ Form::label('inscricao_estadual', 'Inscrição estadual') }}
						{{ Form::text('inscricao_estadual', $dados['inscricao_estadual'], ['class' => 'form-control', 'maxlength' => 20]) }}
					</div>
					<div class="form-group col-md-2 juridica">
						{{ Form::label('inscricao_municipal', 'Inscrição municipal') }}
						{{ Form::text('inscricao_municipal', $dados['inscricao_municipal'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-2 juridica">
						{{ Form::label('suframa', 'SUFRAMA') }}
						{{ Form::text('suframa', $dados['suframa'], ['class' => 'form-control', 'id' => 'suframa', 'maxlength' => '15']) }}						
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-6">
						<span class='campo_obrigatorio'>*</span> {{ Form::label('nome_razao', 'Nome / Razão') }}
						{{ Form::text('nome_razao', $dados['nome_razao'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-6">
						{{ Form::label('guerra_apelido', 'Nome Fantasia') }}
						{{ Form::text('guerra_apelido', $dados['guerra_apelido'], ['class' => 'form-control']) }}
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-3">
						<span class='campo_obrigatorio'>*</span> {{ Form::label('telefone', 'Telefone') }}
						{{ Form::text('telefone', $dados['telefone'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-3">
						{{ Form::label('telefone_fax', 'Fax') }}
						{{ Form::text('telefone_fax', $dados['telefone_fax'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-6">
						<span class='campo_obrigatorio'>*</span> {{ Form::label('email', 'E-mail') }}
						{{ Form::text('email', $dados['email'], ['class' => 'form-control']) }}
					</div>
				</div>
				<div class="row">
					@if (Auth::user()->tipo_usuario_id !== 12)
					<div class="form-group col-md-6">
						<span class='campo_obrigatorio'>*</span> {{ Form::label('vendedor_codigo', 'Vendedor') }}
				        <div class="input-group" id="cod_group">
				        	{{ Form::text('vendedor_codigo', $dados['vendedor']['codigo'], ['id' => 'codigo', 'class' => 'form-control col-sm-3', 'data-route'=> route("vendedor.codigo_para_nome")]) }}
				        	{{ Form::text('vendedor', $dados['vendedor']['nome'], ['id' => 'nome', 'class' => 'form-control', 'disabled' => 'disabled']) }}
				        	<span class="input-group-addon border rounded-right bt-search" id="bt-search-vendedor" data-route="{{ route("vendedor.index.dialog") }}" data-title="Lista de Vendedores"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
						</div>
					</div>
					@else
					{{ Form::hidden('vendedor_codigo', Auth::user()->codigo_representante) }}
					@endif
					<div class="form-group col-md-6">
						<span class='campo_obrigatorio'>*</span> {{ Form::label('transportador_codigo', 'Transportador') }}
				        <div class="input-group" id="cod_group">
				        	{{ Form::text('transportador_codigo', $dados['transportador']['codigo'], ['id' => 'codigo', 'class' => 'form-control col-sm-3', 'data-route'=> route("transportador.codigo_para_nome")]) }}
				        	{{ Form::text('transportador', $dados['transportador']['nome'], ['id' => 'nome', 'class' => 'form-control', 'disabled' => 'disabled']) }}
				        	<span class="input-group-addon border rounded-right bt-search" id="bt-search-transportador" data-route="{{ route("transportador.index.dialog") }}" data-title="Lista de Transportadores"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-3">
						<div class="row">
							<div class="col-md-12">
								<span class='campo_obrigatorio'>*</span> Forte do cliente
							</div>
							<div class="form-group col-md-10">
								<div class="form-check form-check-inline">
									{{ Form::radio('forte_cliente', 'confecção', ($dados['forte_cliente'] === 'confecção'), ['class' => 'form-check-input', 'id'=>'forte_cliente_confeccao']) }}
									{{ Form::label('forte_cliente_confeccao', 'Confecção', ['class'=>'form-check-label']) }}
								</div>
								<div class="form-check form-check-inline">
									{{ Form::radio('forte_cliente', 'atacado', ($dados['forte_cliente'] === 'atacado'), ['class' => 'form-check-input', 'id'=>'forte_cliente_atacado']) }}
									{{ Form::label('forte_cliente_atacado', 'Atacado', ['class'=>'form-check-label']) }}
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-3">
						<div class="row">
							<div class="col-md-12">
								<span class='campo_obrigatorio'>*</span> Tamanho do cliente
							</div>
							<div class="form-group col-md-10">
								<div class="form-check form-check-inline">
									{{ Form::radio('tamanho_cliente', 'p', ($dados['tamanho_cliente'] === 'p'), ['class' => 'form-check-input', 'id'=>'tamanho_cliente_p']) }}
									{{ Form::label('tamanho_cliente_p', 'Pequeno', ['class'=>'form-check-label']) }}
								</div>
								<div class="form-check form-check-inline">
									{{ Form::radio('tamanho_cliente', 'm', ($dados['tamanho_cliente'] === 'm'), ['class' => 'form-check-input', 'id'=>'tamanho_cliente_m']) }}
									{{ Form::label('tamanho_cliente_m', 'Médio', ['class'=>'form-check-label']) }}
								</div>
								<div class="form-check form-check-inline">
									{{ Form::radio('tamanho_cliente', 'g', ($dados['tamanho_cliente'] === 'g'), ['class' => 'form-check-input', 'id'=>'tamanho_cliente_g']) }}
									{{ Form::label('tamanho_cliente_g', 'Grande', ['class'=>'form-check-label']) }}
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<div class="row">
							<div class="col-md-12">
								<span class='campo_obrigatorio'>*</span> Ramo de Atividade
							</div>
							<div class="form-group col-md-10">
								<div class="form-group  form-check form-check-inline">
									{{ Form::radio('ramo_atividade', 'COM.', ($dados['ramo_atividade'] === 'COM.'), ['class' => 'form-check-input', 'id' => 'ramo_atividade_com']) }}
									{{ Form::label('ramo_atividade_com', 'COM.', ['class'=>'form-check-label']) }}
								</div>
								<div class="form-group  form-check form-check-inline">
									{{ Form::radio('ramo_atividade', 'IND.', ($dados['ramo_atividade'] === 'IND.'), ['class' => 'form-check-input', 'id' => 'ramo_atividade_ind']) }}
									{{ Form::label('ramo_atividade_ind', 'IND.', ['class'=>'form-check-label']) }}
								</div>
								<div class="form-group  form-check form-check-inline">
									{{ Form::radio('ramo_atividade', 'Prest. Serv.', ($dados['ramo_atividade'] === 'Prest. Serv.'), ['class' => 'form-check-input', 'id' => 'ramo_atividade_pest']) }}
									{{ Form::label('ramo_atividade_pest', 'Prest. Serv.', ['class'=>'form-check-label']) }}
								</div>
							</div>
						</div>
					</div>
				</div>
				{{-- <div class="row">
					<div class="form-group col-md-3">
						{{ Form::label('numero_filiais', 'Número filiais') }}
						{{ Form::text('numero_filiais', $dados['numero_filiais'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-3">
						{{ Form::label('numero_empregados', 'Número de Empregados') }}
						{{ Form::text('numero_empregados', $dados['numero_empregados'], ['class' => 'form-control']) }}
					</div>
					<div class="col-md-6">
						<div class="row row-options">
							<label class="col-md-12">
								Pŕedio próprio
							</label>
							<div class="form-check col-md-2">
								{{ Form::radio('predio_proprio', 'sim', ($dados['predio_proprio'] === 'sim'), ['class' => 'form-check-input', 'id'=>'predio_proprio_s']) }}
								{{ Form::label('predio_proprio_s', 'Sim', ['class'=>'form-check-label']) }}
							</div>
							<div class="form-check col-md-2">
								{{ Form::radio('predio_proprio', 'nao', ($dados['predio_proprio'] === 'nao'), ['class' => 'form-check-input', 'id'=>'predio_proprio_n']) }}
								{{ Form::label('predio_proprio_n', 'Não', ['class'=>'form-check-label']) }}
							</div>
							<div class="form-group col-md-10 predio_proprio_n">
								{{ Form::label('aluguel', 'Aluguel') }}
								{{ Form::text('aluguel', $dados['aluguel'], ['class' => 'form-control']) }}
							</div>
						</div>
					</div>
				</div> --}}
				<div class="row">
					<div class="form-group col-md-3">
						<span class='campo_obrigatorio'>*</span> {{ Form::label('numero_filiais', 'Número filiais') }}
						{{ Form::text('numero_filiais', $dados['numero_filiais'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-3">
						<span class='campo_obrigatorio'>*</span> {{ Form::label('numero_empregados', 'Número de Empregados') }}
						{{ Form::text('numero_empregados', $dados['numero_empregados'], ['class' => 'form-control']) }}
					</div>
					<div class="col-md-2">
						<div class="row">
							<div class="col-md-12">
								<div class="row">
									<label class='col-md-12'>
										<span class='campo_obrigatorio'>*</span> Prédio próprio
									</label>
								</div>
								<div class="row mb-3">
									<div class="form-check col-md-4 ml-3">
										{{ Form::radio('predio_proprio', 'sim', ($dados['predio_proprio'] === 'sim'), ['class' => 'form-check-input', 'id'=>'predio_proprio_s']) }}
										{{ Form::label('predio_proprio_s', 'Sim', ['class'=>'form-check-label']) }}
									</div>
									<div class="form-check col-md-4">
										{{ Form::radio('predio_proprio', 'nao', ($dados['predio_proprio'] === 'nao'), ['class' => 'form-check-input', 'id'=>'predio_proprio_n']) }}
										{{ Form::label('predio_proprio_n', 'Não', ['class'=>'form-check-label']) }}
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-4">
						<div class="row">
							<div class="form-group col-md-12 predio_proprio_n display_none">
								<span class='campo_obrigatorio'>*</span> {{ Form::label('aluguel', 'Aluguel') }}
								{{ Form::text('aluguel', $dados['aluguel'], ['class' => 'form-control']) }}
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-6">
						{{ Form::label('sucessora_de', 'Sucessora de') }}
						{{ Form::text('sucessora_de', $dados['sucessora_de'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-6">
						{{ Form::label('ligacao_com', 'Ligações com outras firmas') }}
						{{ Form::text('ligacao_com', $dados['ligacao_com'], ['class' => 'form-control']) }}
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-6">
						<span class='campo_obrigatorio'>*</span> {{ Form::label('sugestao_credito', 'Sugestão de crédito avaliado pelo representante') }}
						{{ Form::text('sugestao_credito', $dados['sugestao_credito'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-6">
						<span class='campo_obrigatorio'>*</span> {{ Form::label('historico_cliente_praca', 'Histórico do cliente na praça') }}
						{{ Form::text('historico_cliente_praca', $dados['historico_cliente_praca'], ['class' => 'form-control', 'maxlength' => '500']) }}
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
						<span class="campo_obrigatorio">*</span>{{ Form::label('faturamento_cep', 'CEP') }}
						{{ Form::text('faturamento_cep', $dados['faturamento_cep'], ['class' => 'form-control cep', 'data-endereco'=>"faturamento"]) }}
					</div>
					<div class="form-group col-md-4">
						<span class="campo_obrigatorio">*</span>{{ Form::label('faturamento_logradouro', 'Logradouro') }}
						{{ Form::text('faturamento_logradouro', $dados['faturamento_logradouro'], array('class' => 'form-control')) }}
					</div>
					<div class="form-group col-md-3">
						<span class="campo_obrigatorio">*</span>{{ Form::label('faturamento_numero', 'Número') }}
						{{ Form::text('faturamento_numero', $dados['faturamento_numero'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-3">
						{{ Form::label('faturamento_complemento', 'Complemento') }}
						{{ Form::text('faturamento_complemento', $dados['faturamento_complemento'], ['class' => 'form-control']) }}
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-4">
						<span class="campo_obrigatorio">*</span>{{ Form::label('faturamento_bairro', 'Bairro') }}
						{{ Form::text('faturamento_bairro', $dados['faturamento_bairro'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-4">
						<span class="campo_obrigatorio">*</span>{{ Form::label('faturamento_cidade', 'Cidade') }}
						{{ Form::text('faturamento_cidade', $dados['faturamento_cidade'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-4">
						<span class="campo_obrigatorio">*</span>{{ Form::label('faturamento_estado', 'Estado') }}
						{{ Form::select('faturamento_estado', $estados, $dados['faturamento_estado'], ['class' => 'form-control']) }}
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-3">
						<span class="campo_obrigatorio">*</span>{{ Form::label('faturamento_telefone', 'Telefone') }}
						{{ Form::text('faturamento_telefone', $dados['faturamento_telefone'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-3">
						{{ Form::label('faturamento_telefone_fax', 'Fax') }}
						{{ Form::text('faturamento_telefone_fax', $dados['faturamento_telefone_fax'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-6">
						<span class="campo_obrigatorio">*</span>{{ Form::label('faturamento_email', 'E-mail') }}
						{{ Form::text('faturamento_email', $dados['faturamento_email'], ['class' => 'form-control']) }}
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
				<div class="row mb-4">
					<div class="form-check col-md-10 ml-3">
						{{ Form::checkbox('use_dados_faturamento', 'dados_faturamento', $dados['use_dados_faturamento'] , [ 'id' => 'use_dados_faturamento', 'class' => 'form-check-input']) }}
						{{ Form::label('use_dados_faturamento', 'Usar endereço de faturamento', ['class' => 'form-check-label ml-1']) }}
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-2">
						<span class="campo_obrigatorio">*</span>{{ Form::label('cobranca_cep', 'CEP') }}
						{{ Form::text('cobranca_cep', $dados['cobranca_cep'], ['class' => 'form-control cep'.($dados['use_dados_faturamento'] === true ? ' disabled' : '' ), 'data-endereco'=>"cobranca", 'disabled' => "{$dados['use_dados_faturamento']}"]) }}
					</div>
					<div class="form-group col-md-4">
						<span class="campo_obrigatorio">*</span>{{ Form::label('cobranca_logradouro', 'Logradouro') }}
						{{ Form::text('cobranca_logradouro', $dados['cobranca_logradouro'], ['class' => 'form-control'.($dados['use_dados_faturamento'] === true ? ' disabled' : '' ), 'disabled' => "{$dados['use_dados_faturamento']}"]) }}
					</div>
					<div class="form-group col-md-3">
						<span class="campo_obrigatorio">*</span>{{ Form::label('cobranca_numero', 'Número') }}
						{{ Form::text('cobranca_numero', $dados['cobranca_numero'], ['class' => 'form-control'.($dados['use_dados_faturamento'] === true ? ' disabled' : '' ), 'disabled' => "{$dados['use_dados_faturamento']}"]) }}
					</div>
					<div class="form-group col-md-3">
						{{ Form::label('cobranca_complemento', 'Complemento') }}
						{{ Form::text('cobranca_complemento', $dados['cobranca_complemento'], ['class' => 'form-control'.($dados['use_dados_faturamento'] === true ? ' disabled' : '' ), 'disabled' => "{$dados['use_dados_faturamento']}"]) }}
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-4">
						<span class="campo_obrigatorio">*</span>{{ Form::label('cobranca_bairro', 'Bairro') }}
						{{ Form::text('cobranca_bairro', $dados['cobranca_bairro'], ['class' => 'form-control'.($dados['use_dados_faturamento'] === true ? ' disabled' : '' ), 'disabled' => "{$dados['use_dados_faturamento']}"]) }}
					</div>
					<div class="form-group col-md-4">
						<span class="campo_obrigatorio">*</span>{{ Form::label('cobranca_cidade', 'Cidade') }}
						{{ Form::text('cobranca_cidade', $dados['cobranca_cidade'], ['class' => 'form-control'.($dados['use_dados_faturamento'] === true ? ' disabled' : '' ), 'disabled' => "{$dados['use_dados_faturamento']}"]) }}
					</div>
					<div class="form-group col-md-4">
						<span class="campo_obrigatorio">*</span>{{ Form::label('cobranca_estado', 'Estado') }}
						{{ Form::select('cobranca_estado', $estados, $dados['cobranca_estado'], ['class' => 'form-control'.($dados['use_dados_faturamento'] === true ? ' disabled' : '' ), 'disabled' => "{$dados['use_dados_faturamento']}"]) }}	    		
					</div>
				</div>
				<div class="row">
					<div class="form-group col-md-3">
						<span class="campo_obrigatorio">*</span>{{ Form::label('cobranca_telefone', 'Telefone') }}
						{{ Form::text('cobranca_telefone', $dados['cobranca_telefone'], ['class' => 'form-control'.($dados['use_dados_faturamento'] === true ? ' disabled' : '' ), 'disabled' => "{$dados['use_dados_faturamento']}"]) }}
					</div>
					<div class="form-group col-md-3">
						{{ Form::label('cobranca_telefone_fax', 'Fax') }}
						{{ Form::text('cobranca_telefone_fax', $dados['cobranca_telefone_fax'], ['class' => 'form-control'.($dados['use_dados_faturamento'] === true ? ' disabled' : '' ), 'disabled' => "{$dados['use_dados_faturamento']}"]) }}
					</div>
					<div class="form-group col-md-2">
						<span class="campo_obrigatorio">*</span>{{ Form::label('cobranca_banco', 'Banco') }}
						{{ Form::text('cobranca_banco', $dados['cobranca_banco'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-2">
						<span class="campo_obrigatorio">*</span>{{ Form::label('cobranca_agencia', 'Agência') }}
						{{ Form::text('cobranca_agencia', $dados['cobranca_agencia'], ['class' => 'form-control']) }}
					</div>
					<div class="form-group col-md-2">
						<span class="campo_obrigatorio">*</span>{{ Form::label('cobranca_conta', 'Conta') }}
						{{ Form::text('cobranca_conta', $dados['cobranca_conta'], ['class' => 'form-control']) }}
					</div>
				</div>
			</div>
		</div>
		<div class="tab-pane" id="cliente_novo_socios_referencias" role="tabpanel" aria-labelledby="socios_referencias-tab">
			<div class="content-tab">
				<div class="row">
					<div class="col-md-12">
						<h3><center><span class="campo_obrigatorio">*</span>Sócios ou Diretores</center></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div class="content-dialog-table">
							<table class="table table-striped" id="table_socio_diretor">
								<thead>
									<tr>
										<th><span class="campo_obrigatorio">*</span>Nome</th>
										<th><span class="campo_obrigatorio">*</span>CPF</th>
										<th><span class="campo_obrigatorio">*</span>PARTE %</th>
										<th>Editar</th>
										<th>Excluir</th>
									</tr>
								</thead>
								<tbody>
									@foreach($dados["socios"] as $value)
										<tr>
											<td>
												{!! Form::hidden("socio[".$value["id"]."][nome]", $value["nome"]) !!}
												{!! $value["nome"] !!}
											</td>
											<td>
												{!! Form::hidden("socio[".$value["id"]."][cpf]", $value["cpf"]) !!}
												{!! $value["cpf"] !!}
											</td>
											<td>
												{!! Form::hidden("socio[".$value["id"]."][parte]", $value["parte"]) !!}
												{!! $value["parte"] !!}
											</td>
											<td><a href="#" class="bt-edit" id="bt-edit" onclick="editLineSocioDiretor(this)"></a></td>
											<td><a href="#" class="bt-delete" id="bt-deleted" onclick="removeLineSocioDiretor(this)"></a></td>
										</tr>
									@endforeach
								</tbody>
								<tfoot>
									<tr class="row_new">
										<td><input type="text" placeholder="Nome" name="nome_socio_novo" id="nome_socio_novo" class="form-control" maxlength="50" /></td>
										<td><input type="text" placeholder="CPF" name="cpf_socio_novo" id="cpf_socio_novo" class="form-control cpf" maxlength="20" /></td>
										<td><input type="text" placeholder="PARTE %" name="parte_socio_novo" id="parte_socio_novo" class="form-control" maxlength="3" /></td>
										<td colspan="2">
											<button class="add_socio_novo btn btn-success" name="add_socio_novo" id="add_socio_novo">Adicionar</button>
											<button class="edit_socio_novo btn btn-primary display_none" name="edit_socio_novo" id="edit_socio_novo">Editar</button>
											<button class="cancelar_socio_novo btn btn-danger display_none" name="cancelar_socio_novo" id="cancelar_socio_novo">Cancelar</button>
										</td>
									</tr>
								</tfoot>
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
						<h3><center><span class="campo_obrigatorio">*</span>Referências Comerciais</center></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div class="content-dialog-table">
							<table class="table table-striped" id="table_referencia_comercial">
								<thead>
									<tr>
										<th><span class="campo_obrigatorio">*</span>Empresa</th>
										<th><span class="campo_obrigatorio">*</span>Contato</th>
										<th><span class="campo_obrigatorio">*</span>DDD</th>
										<th><span class="campo_obrigatorio">*</span>Telefone</th>
										<th><span class="campo_obrigatorio">*</span>Estado</th>
										<th><span class="campo_obrigatorio">*</span>Cidade</th>
										<th>Editar</th>
										<th>Excluir</th>
									</tr>
								</thead>
								<tbody>
									@foreach($dados["referencias"] as $value)
										<tr>
											<td>
												{!! Form::hidden("referencia[".$value["id"]."][empresa]", $value["empresa"]) !!}
												{!! $value["empresa"] !!}
											</td>
											<td>
												{!! Form::hidden("referencia[".$value["id"]."][contato]", $value["contato"]) !!}
												{!! $value["contato"] !!}
											</td>
											<td>
												{!! Form::hidden("referencia[".$value["id"]."][telefone_ddd]", $value["telefone_ddd"]) !!}
												{!! $value["telefone_ddd"] !!}
											</td>
											<td>
												{!! Form::hidden("referencia[".$value["id"]."][telefone]", $value["telefone"]) !!}
												{!! $value["telefone"] !!}
											</td>
											<td>
												{!! Form::hidden("referencia[".$value["id"]."][estado]", $value["estado"]) !!}
												{!! $value["estado"] !!}
											</td>
											<td>
												{!! Form::hidden("referencia[".$value["id"]."][cidade]", $value["cidade"]) !!}
												{!! $value["cidade"] !!}
											</td>
											<td><a href="#" class="bt-edit" id="bt-edit" onclick="editLineReferência(this)"></a></td>
											<td><a href="#" class="bt-delete" id="bt-deleted" onclick="removeLineRefencia(this)"></a></td>
										</tr>
									@endforeach
								</tbody>
								<tfoot>
									<tr>
										<td><input type="text" placeholder="Empresa" name="empresa_referencia_novo" id="empresa_referencia_novo" class="form-control" maxlength="50"></td>
										<td><input type="text" placeholder="Contato" name="contato_referencia_novo" id="contato_referencia_novo" class="form-control" maxlength="20" /></td>
										<td><input type="text" placeholder="DDD" name="telefone_ddd_referencia_novo" id="telefone_ddd_referencia_novo" class="form-control" maxlength="3" /></td>
										<td><input type="text" placeholder="Telefone" name="telefone_referencia_novo" id="telefone_referencia_novo" class="form-control" maxlength="20" /></td>
										<td>{{ Form::select('estado_referencia_novo', $estados, '', ['class' => 'form-control', 'id'=> 'estado_referencia_novo']) }}</td>
										<td>{{ Form::select('cidade_referencia_novo', [""=>"Selecione um estado"], '', ['class' => 'form-control', 'id'=> 'cidade_referencia_novo']) }}</td>
										<td colspan="2">
											<button class="add_referencia_novo btn btn-success" name="add_referencia_novo" id="add_referencia_novo">Adicionar</button>
											<button class="edit_referencia_novo btn btn-primary display_none" name="edit_referencia_novo" id="edit_referencia_novo">Editar</button>
											<button class="cancelar_referencia_novo btn btn-danger display_none" name="cancelar_referencia_novo" id="cancelar_referencia_novo">Cancelar</button>
										</td>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="content_buttons">
		<div class="form-group col-md-12">
			{{ Form::button('<< Dados Basicos', ['class' => 'btn float-left troca-aba btn-info', 'id' => 'voltar_basico']) }}
			{{ Form::button('<< Dados de Faturamento / Cobrança', ['class' => 'btn float-left troca-aba btn-info', 'id'=>'voltar_endereco_faturamento_cobranca']) }}
			{{ Form::button('Dados de Faturamento / Cobrança >>', ['class' => 'btn float-right troca-aba btn-info', 'id'=>'avancar_endereco_faturamento_cobranca']) }}
			{{ Form::button('Sócios e Referências >>', ['class' => 'btn float-right troca-aba btn-info', 'id'=>'avancar_socio_refencia']) }}
			{{ Form::submit('Salvar', ['class' => 'btn btn-success float-right', "id"=>"bt_salvar"]) }}
		</div>
	</div>
</form>
<script type="text/javascript">
	$(document).find('#cadastro_cliente_novo').find("#voltar_basico").hide();
	$(document).find('#cadastro_cliente_novo').find("#voltar_endereco_faturamento_cobranca").hide();
	$(document).find('#cadastro_cliente_novo').find("#avancar_socio_refencia").hide();
	$(document).find('#cadastro_cliente_novo').find("#bt_salvar").hide();
	@if($dados['ja_foi_cliente'] === 'sim')
	$(document).find('#cadastro_cliente_novo').find(".ja_foi_cliente_s").show();
	@else
	$(document).find('#cadastro_cliente_novo').find(".ja_foi_cliente_s").hide();
	@endif

	@if($dados['predio_proprio'] === "sim")
	$(document).find('#cadastro_cliente_novo').find("div.predio_proprio_n").hide();
	@endif

	$(document).find('#inscricao_estadual_indicador').on('change', function(){
		if($(this).val() == 2){
			$(document).find('#inscricao_estadual_div').hide();
		}
		else{
			$(document).find('#inscricao_estadual_div').show();
		}
	});
	
	var table_socio_diretor, table_referencia_comercial;
	setTimeout(function(){
		var $height = ($(document).find('#cadastro_cliente_novo').height() / 2) - 250;
		table_socio_diretor = $(document).find('#cadastro_cliente_novo').find("#table_socio_diretor").DataTable({
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
	            "emptyTable":     " ",
	            "infoPostFix":    " ",
	            "loadingRecords": "Carregando...",
	            "processing":     "Processando...",
	            "zeroRecords":    " ",
	            "columnDefs": [
	                {
	                    "targets": ($(document).find('#cadastro_cliente_novo').find('#table_socio_diretor thead th').length - 1),
	                    "orderable": false
	                },
	            ],
	            "paginate": {
	                "first":      "<<",
	                "last":       ">>",
	                "next":       ">",
	                "previous":   "<"
	            }
	        }
	    });
		table_referencia_comercial = $(document).find('#cadastro_cliente_novo').find("#table_referencia_comercial").DataTable({
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
	            "emptyTable":     " ",
	            "infoPostFix":    " ",
	            "loadingRecords": "Carregando...",
	            "processing":     "Processando...",
	            "zeroRecords":    " ",
	            "columnDefs": [
	                {
	                    "targets": ($(document).find('#cadastro_cliente_novo').find('#table_referencia_comercial thead th').length - 1),
	                    "orderable": false
	                },
	            ],
	            "paginate": {
	                "first":      "<<",
	                "last":       ">>",
	                "next":       ">",
	                "previous":   "<"
	            }
	        }
	    });
	}, 500);
	$(function(){
		$(document).find('#cadastro_cliente_novo').find('#use_dados_faturamento').off('change');
		$(document).find('#cadastro_cliente_novo').find('#use_dados_faturamento').on('change', function(){
			var $campos = ['cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado', 'telefone', 'telefone_fax'];
			var valores_faturamento = {};
			$.each($campos, function(){
				var $this = this;
				$(document).find('#cadastro_cliente_novo').find('#cobranca_'+$this).prop('disabled', false).removeClass('disabled');
				valores_faturamento[$this] = $(document).find('#cadastro_cliente_novo').find('#faturamento_'+$this).val();
			});
			
			if($(document).find('#cadastro_cliente_novo').find('#use_dados_faturamento').prop('checked') === true){
				$.each($campos, function(){
					var $this = this;
					$(document).find('#cadastro_cliente_novo').find('#cobranca_'+$this).prop('disabled', true).addClass('disabled').val(valores_faturamento[$this]);
					$(document).find('#cadastro_cliente_novo').find('#faturamento_'+$this).off('change');
					$(document).find('#cadastro_cliente_novo').find('#faturamento_'+$this).on('change', function(){
						$input = $(this);
						if($(document).find('#cadastro_cliente_novo').find('#use_dados_faturamento').prop('checked') === true){
							var name = ($input.attr('name')).replace('faturamento_', '');
							$(document).find('#cadastro_cliente_novo').find('#cobranca_'+$this).val($input.val());
						}
					});
				});
			}
		});
		
		$(document).find('#cadastro_cliente_novo').find("#add_referencia_novo").off("click");
		$(document).find('#cadastro_cliente_novo').find("#add_referencia_novo").on("click", function(){
			if(($(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#empresa_referencia_novo").val()).trim() === ""){
				$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#empresa_referencia_novo").focus();
				return false;
			}
			if(($(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#contato_referencia_novo").val()).trim() === ""){
				$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#contato_referencia_novo").focus();
				return false;
			}
			var empresa = ($(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#empresa_referencia_novo").val()).trim();
			var contato = ($(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#contato_referencia_novo").val()).trim();
			var ddd = ($(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#telefone_ddd_referencia_novo").val()).trim();
			var telefone = ($(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#telefone_referencia_novo").val()).trim();
			var estado = ($(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#estado_referencia_novo").val()).trim();
			var cidade = ($(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cidade_referencia_novo").find("option:selected").text()).trim();

			var count_table = $(document).find('#cadastro_cliente_novo').find("#table_referencia_comercial").find('tbody').find("tr td:not(.dataTables_empty)").length;
			count_table++;

			var line = [
				"<input type=\"hidden\" name=\"referencia["+count_table+"][empresa]\" value=\""+empresa+"\" />"+empresa,
				"<input type=\"hidden\" name=\"referencia["+count_table+"][contato]\" value=\""+contato+"\" />"+contato,
				"<input type=\"hidden\" name=\"referencia["+count_table+"][telefone_ddd]\" value=\""+ddd+"\" />"+ddd,
				"<input type=\"hidden\" name=\"referencia["+count_table+"][telefone]\" value=\""+telefone+"\" />"+telefone,
				"<input type=\"hidden\" name=\"referencia["+count_table+"][estado]\" value=\""+estado+"\" />"+estado,
				"<input type=\"hidden\" name=\"referencia["+count_table+"][cidade]\" value=\""+cidade+"\" />"+cidade,
				"<a href=\"#\" class=\"bt-edit\" id=\"bt-edit\" onclick=\"editLineReferência(this)\" ></a>",
				"<a href=\"#\" class=\"bt-delete\" id=\"bt-deleted\" onclick=\"removeLineRefencia(this)\" ></a>"
			];
			table_referencia_comercial.row.add(line).order([ 0, 'asc' ] ).draw().nodes();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#empresa_referencia_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#contato_referencia_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#telefone_ddd_referencia_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#telefone_referencia_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#estado_referencia_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cidade_referencia_novo").html("<option value=\"\">Selecione um estado</option>");
		});
		$(document).find('#cadastro_cliente_novo').find("#add_socio_novo").off("click");
		$(document).find('#cadastro_cliente_novo').find("#add_socio_novo").on("click", function(){
			if(($(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#nome_socio_novo").val()).trim() === ""){
				$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#nome_socio_novo").focus();
				return false;
			}
			var nome = ($(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#nome_socio_novo").val()).trim();
			var cpf = ($(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cpf_socio_novo").val()).trim();
			var parte = ($(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#parte_socio_novo").val()).trim();

			if(cpf === '..-'){
				cpf = "";
			}

			var count_table = $(document).find('#cadastro_cliente_novo').find("#table_socio_diretor").find('tbody').find("tr td:not(.dataTables_empty)").length;
			count_table++;

			var line = [
				"<input type=\"hidden\" name=\"socio["+count_table+"][nome]\" value=\""+nome+"\" />"+nome,
				"<input type=\"hidden\" name=\"socio["+count_table+"][cpf]\" value=\""+cpf+"\" />"+cpf,
				"<input type=\"hidden\" name=\"socio["+count_table+"][parte]\" value=\""+parte+"\" />"+parte,
				"<a href=\"#\" class=\"bt-edit\" id=\"bt-edit\" onclick=\"editLineSocioDiretor(this)\" ></a>",
				"<a href=\"#\" class=\"bt-delete\" id=\"bt-deleted\" onclick=\"removeLineSocioDiretor(this)\" ></a>"
			];
			table_socio_diretor.row.add(line).order([ 0, 'asc' ] ).draw().nodes();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#nome_socio_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cpf_socio_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#parte_socio_novo").val("");
		});
		$(document).find('#cadastro_cliente_novo').find('a[data-toggle="tab"]').off('shown.bs.tab');
		$(document).find('#cadastro_cliente_novo').find('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			table_socio_diretor.draw();
			table_referencia_comercial.draw();
			$(document).find('#cadastro_cliente_novo').find("#voltar_basico").hide();
			$(document).find('#cadastro_cliente_novo').find("#voltar_endereco_faturamento_cobranca").hide();
			$(document).find('#cadastro_cliente_novo').find("#avancar_endereco_faturamento_cobranca").hide();
			$(document).find('#cadastro_cliente_novo').find("#avancar_socio_refencia").hide();
			$(document).find('#cadastro_cliente_novo').find("#bt_salvar").hide();
			if($(e.target).attr("id") === 'enderecos-tab'){
				$(document).find('#cadastro_cliente_novo').find("#voltar_basico").show();
				$(document).find('#cadastro_cliente_novo').find("#avancar_socio_refencia").show();
			}
			if($(e.target).attr("id") === 'socios_referencias-tab'){
				$(document).find('#cadastro_cliente_novo').find("#voltar_endereco_faturamento_cobranca").show();
				$(document).find('#cadastro_cliente_novo').find("#bt_salvar").show();
			}
			if($(e.target).attr("id") === 'dados-tab'){
				$(document).find('#cadastro_cliente_novo').find("#avancar_endereco_faturamento_cobranca").show();
			}
		})

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

		$(document).find('#cadastro_cliente_novo').find('input[name="ja_foi_cliente"]').off("change");
		$(document).find('#cadastro_cliente_novo').find('input[name="ja_foi_cliente"]').on("change", function(){
			$("div.ja_foi_cliente_s").hide();
			if($(this).val() === "sim"){
				$("div."+$(this).attr("id")).show();
			}
		});
		$(document).find('#cadastro_cliente_novo').find('input[name="predio_proprio"]').off("change");
		$(document).find('#cadastro_cliente_novo').find('input[name="predio_proprio"]').on("change", function(){
			$("div.predio_proprio_n").hide();
			if($(this).val() === "nao"){
				$("div."+$(this).attr("id")).show();
			}
		});
		@if($dados['fisica_juridica'] === "j")
		$(document).find('#cadastro_cliente_novo').find("div.fisica").hide();
		@else
		$(document).find('#cadastro_cliente_novo').find("div.juridica").hide();
		@endif
		$(document).find('#cadastro_cliente_novo').find('input[name="fisica_juridica"]').off("change");
		$(document).find('#cadastro_cliente_novo').find('input[name="fisica_juridica"]').on("change", function(){
			$(document).find('#cadastro_cliente_novo').find("div.juridica").hide();
			$(document).find('#cadastro_cliente_novo').find("div.fisica").hide();
			@if(strtolower($dados['fisica_juridica']) === "f")
			$(document).find('#cadastro_cliente_novo').find("div.juridica").find("input").val("");
			@else
			$(document).find('#cadastro_cliente_novo').find("div.fisica").find("input").val("");
			@endif
			$(document).find('#cadastro_cliente_novo').find("div."+$(this).val()).show();
		});
		$(document).find('#cadastro_cliente_novo').find('.cpf').mask('999.999.999-99');
		$(document).find('#cadastro_cliente_novo').find('.cnpj').mask('99.999.999/9999-99');

		$(document).find('#cadastro_cliente_novo').find('#telefone_ddd_referencia_novo').mask('999');

		$(document).find('#cadastro_cliente_novo').find('.cep').mask('99999-999');

		$(document).find('#cadastro_cliente_novo').find('#estado_referencia_novo').off("change");
		$(document).find('#cadastro_cliente_novo').find('#estado_referencia_novo').on("change", function(e){
			var $this = $(this);
			if(($this.val()).trim() === "" || ($this.val()).trim() === "-"){
				$this.val("");
				return false;
			}
			$.ajax({
	            url: '{{ route('busca_cep.cidade') }}',
	            dataType: 'json',
	            method: 'POST',
	            data: {_token: "{{ csrf_token() }}", estado: $this.val()},
	            success: function(callback){
					if(callback.status === "success"){
						var data = callback.dados;
						$(document).find('#cadastro_cliente_novo').find("#cidade_referencia_novo").html("");
						var html = "<option value=\"\" disabled></option>";
						$.each(data,function(index, el) {
							html += "<option value=\""+index+"\">"+this+"</option>";
						});
						$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cidade_referencia_novo").html(html);
					}else{
						$this.focus();
						$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cidade_referencia_novo").html("<option value=\"\">Selecione um estado</option>");
						message("Atenção", callback.message);
					}
	            }
	        });
		});
		$(document).find('#cadastro_cliente_novo').find('.cep').off("blur");
		$(document).find('#cadastro_cliente_novo').find('.cep').on("blur", function(e){
			var $this = $(this);
			if(($this.val()).trim() === "" || ($this.val()).trim() === "-"){
				$this.val("");
				return false;
			}
			if($this.data("status") && $this.data("valor") && $this.data("status") === "error" && $this.data("valor") === $this.val()){
				$this.focus();
				return false;
			}
			$(document).find('#cadastro_cliente_novo').find('input[name="'+$this.data("endereco")+'_logradouro"]').val("");
			$(document).find('#cadastro_cliente_novo').find('input[name="'+$this.data("endereco")+'_bairro"]').val("");
			$(document).find('#cadastro_cliente_novo').find('input[name="'+$this.data("endereco")+'_cidade"]').val("");
			$(document).find('#cadastro_cliente_novo').find('select[name="'+$this.data("endereco")+'_estado"]').val("");
			$(document).find('#cadastro_cliente_novo').find('input[name="'+$this.data("endereco")+'_numero"]').val("");
			$(document).find('#cadastro_cliente_novo').find('input[name="'+$this.data("endereco")+'_complemento"]').val("");
			$.ajax({
	            url: '{{ route('busca_cep') }}',
	            dataType: 'json',
	            method: 'POST',
	            data: {_token: "{{ csrf_token() }}", cep: $this.val()},
	            success: function(callback){
	            	$this.data("status", callback.status);
	            	$this.data("valor", $this.val());
					if(callback.status === "success"){
						var data = callback.dados;
						$(document).find('#cadastro_cliente_novo').find('input[name="'+$this.data("endereco")+'_logradouro"]').val(data.tipo_logradouro + " " + data.logradouro );
						$(document).find('#cadastro_cliente_novo').find('input[name="'+$this.data("endereco")+'_bairro"]').val( data.bairro );
						$(document).find('#cadastro_cliente_novo').find('input[name="'+$this.data("endereco")+'_cidade"]').val( data.cidade );
						$(document).find('#cadastro_cliente_novo').find('select[name="'+$this.data("endereco")+'_estado"]').val( data.uf );
						$(document).find('#cadastro_cliente_novo').find('input[name="'+$this.data("endereco")+'_numero"]').val( "" );
						$(document).find('#cadastro_cliente_novo').find('input[name="'+$this.data("endereco")+'_numero"]').focus();
					}else{
						$this.focus();
						message("Atenção", callback.message);
					}
	            }
	        });
		});
		$(document).find('#cadastro_cliente_novo').find("input[type='text']").off("keyup");
		$(document).find('#cadastro_cliente_novo').find("input[type='text']").on("keyup", function(event){
			if(event.keyCode === 13){
				var campos = $(document).find('#cadastro_cliente_novo').find("input[type='text']:visible, button:not(.float-left):visible");
				var indice = campos.index(event.target) + 1;
				if($(campos[indice]).attr("type") === "text"){
					var seletor = $(campos[indice]).focus();
				}else{
					var seletor = $(campos[indice]).click();
					var campos = $(document).find('#cadastro_cliente_novo').find("input[type='text']:visible, button:visible");
					var seletor = $(campos[0]).focus();
				}
				if (seletor.length === 0) {
					event.target.focus();
				}
			}
		});
		$(document).find('#cadastro_cliente_novo').find('.bt-search').off("click");
		$(document).find('#cadastro_cliente_novo').find('.bt-search').on("click", function(){
			var $this = $(this);
			var url = $this.data("route");
			var title = $this.data("title");
			var id_modal = "modal_"+$(this).attr("id");
			$.ajax({
				url: url,
				method: 'POST',
				data: {_token: '{{ csrf_token() }}'},
				success: function(body){
					createModal(id_modal, title, body, 'modal-lg');
					var modal = $(document).find("#"+id_modal);
					$(document).ready( function () {
						table_dialog.on('draw', function () {
							modal.find('tbody').find("tr").off("click");
							modal.find('tbody').find("tr").on("click", function(){
								insertDados($this, $(this), modal);
							});
						});
					});
				}
			});
	    });
	    $(document).find('#cadastro_cliente_novo').find('input[name="vendedor_codigo"], input[name="transportador_codigo"]').off("change");
		$(document).find('#cadastro_cliente_novo').find('input[name="vendedor_codigo"], input[name="transportador_codigo"]').on("change", function(){
			var $this = $(this);
			var url = $this.data("route");
			$($this).parents("#cod_group").find("#nome").val("");
			if($($this).val() === ""){
				return false;
			}
			$.ajax({
				url: url,
				method: 'POST',
				data: {_token: '{{ csrf_token() }}', codigo: $($this).val()},
				success: function(callback){
					if(callback.status === "success"){
						$($this).parents("#cod_group").find("#nome").val(callback.data);
					}else{
						message("Atenção", "Código não encontrado");
						$($this).focus();
					}
				}
			});
		});
		$(document).find('#cadastro_cliente_novo').find("#bt_salvar").off('click');
		$(document).find('#cadastro_cliente_novo').find("#bt_salvar").on('click', function(event){
			event.stopPropagation();
			var form = $(document).find('#cadastro_cliente_novo');
			form.find("input").each(function(){
				if($(this).hasClass('cnpj') || $(this).hasClass('cpf') || $(this).hasClass('cep')){
					var temp_val = $(this).val().trim();
					temp_val = temp_val.replace("-","");
					temp_val = temp_val.replace(".","");
					temp_val = temp_val.replace(".","");
					temp_val = temp_val.replace(".","");
					temp_val = temp_val.replace("/","");
					if(temp_val.trim() === ""){
						$(this).val("");
					}
				}
			})
	        var form_data = form.serialize();
	        var url = form.attr("action");
	        $.ajax({
	            url: url,
	            dataType: 'json',
	            data: form_data,
	            method: 'POST',
	            success: function(callback){
	                if(callback.status === "success"){
	                	$(document).find('#cadastro_cliente_novo').parents(".modal").modal("hide");
	                	message("Atenção", "Dados salvos com sucesso!");
	                } else {
	                	message("Atenção", callback.message);
	                }
	            },
	            error: function(data){
	            	hide_loader();
	                var errors = data.responseJSON.errors;
	                form.find('.error-message').remove();
	                form.find('div, input, select').each(function(){
	                	if($(this).hasClass("is-invalid")){
	                		$(this).removeClass("is-invalid")
	                	}
	                	if($(this).hasClass("error-input")){
	                		$(this).removeClass("error-input")
	                	}
	                });
	                for(var field in errors){
	                    showErrorsInputs(form, field, errors[field])
	                }
	                if(form.find('.error-message').length){
	                	var id_tab = form.find('.error-message').eq(0).parents(".tab-pane").attr("aria-labelledby");
	                	$(document).find("#"+id_tab).tab('show');
	                	form.find('.error-message').eq(0).focus();
	                }
	            }
	        });
		});
	});
	function insertDados($campo, $dados, $modal){
		var campo_codigo = $($campo).parents('#cod_group').find("#codigo");
		var campo_nome = $($campo).parents('#cod_group').find("#nome");
		campo_codigo.val($($dados).find("td").eq(0).text());
		if(($($dados).find("td").eq(2)).length){
			campo_nome.val($($dados).find("td").eq(2).text());
		}else{
			campo_nome.val($($dados).find("td").eq(1).text());
		}
		$modal.modal("hide");
	}
	function removeLineRefencia($row){
		table_referencia_comercial.row($($row).parents("tr")).remove().draw().nodes();
	}
	function removeLineSocioDiretor($row){
		table_socio_diretor.row($($row).parents("tr")).remove().draw().nodes();
	}
	function editLineReferência($row){
		var $line = $($row).parents("tr");
		$($row).parents("tr").hide();
		table_referencia_comercial.draw();
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#add_referencia_novo").hide();
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#edit_referencia_novo").show();
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cancelar_referencia_novo").show();

		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#empresa_referencia_novo").val(($($line).find('td').eq(0).text()).trim());
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#contato_referencia_novo").val(($($line).find('td').eq(1).text()).trim());
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#telefone_ddd_referencia_novo").val(($($line).find('td').eq(2).text()).trim());
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#telefone_referencia_novo").val(($($line).find('td').eq(3).text()).trim());
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#estado_referencia_novo").val(($($line).find('td').eq(4).text()).trim());

		var $estado = ($($line).find('td').eq(4).text()).trim();
		if(($estado).trim() !== ""){
			$.ajax({
				url: '{{ route('busca_cep.cidade') }}',
				dataType: 'json',
				method: 'POST',
				data: {_token: "{{ csrf_token() }}", estado: $estado},
				success: function(callback){
					if(callback.status === "success"){
						var data = callback.dados;
						$(document).find('#cadastro_cliente_novo').find("#cidade_referencia_novo").html("");
						var html = "<option value=\"\" disabled></option>";
						$.each(data,function(index, el) {
							html += "<option value=\""+index+"\">"+this+"</option>";
						});
						$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cidade_referencia_novo").html(html);
					}
				}
			});
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cidade_referencia_novo").val(($($line).find('td').eq(5).text()).trim());
		}
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cancelar_referencia_novo").off("click");
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cancelar_referencia_novo").on("click", function(){
			$line.show();
			table_referencia_comercial.draw();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#add_referencia_novo").show();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#edit_referencia_novo").hide();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cancelar_referencia_novo").hide();

			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#empresa_referencia_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#contato_referencia_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#telefone_ddd_referencia_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#telefone_referencia_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#estado_referencia_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cidade_referencia_novo").html("<option value=\"\">Selecione um estado</option>");

		});
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#edit_referencia_novo").off("click");
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#edit_referencia_novo").on("click", function(){
			$line.show();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#add_referencia_novo").show();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#edit_referencia_novo").hide();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cancelar_referencia_novo").hide();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#add_referencia_novo").click();
			table_referencia_comercial.row($line).remove().draw().nodes();

		});
	}
	function editLineSocioDiretor($row){
		var $line = $($row).parents("tr");
		$($row).parents("tr").hide();
		table_socio_diretor.draw();
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#add_socio_novo").hide();
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#edit_socio_novo").show();
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cancelar_socio_novo").show();

		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#nome_socio_novo").val(($($line).find('td').eq(0).text()).trim());
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cpf_socio_novo").val(($($line).find('td').eq(1).text()).trim());
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#parte_socio_novo").val(($($line).find('td').eq(2).text()).trim());

		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cancelar_socio_novo").off("click");
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cancelar_socio_novo").on("click", function(){
			$line.show();
			table_socio_diretor.draw();

			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#nome_socio_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cpf_socio_novo").val("");
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#parte_socio_novo").val("");

			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#add_socio_novo").show();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#edit_socio_novo").hide();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cancelar_socio_novo").hide();
		});
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#edit_socio_novo").off("click");
		$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#edit_socio_novo").on("click", function(){
			$line.show();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#add_socio_novo").show();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#edit_socio_novo").hide();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#cancelar_socio_novo").hide();
			$(document).find('#cadastro_cliente_novo').find('.dataTables_scrollFoot').find("#add_socio_novo").click();
			table_socio_diretor.row($line).remove().draw().nodes();

		});
	}

	function showErrorsInputs(form, input, message){
		if(input === "vendedor_codigo" || input === "transportador_codigo"){
			var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"']").parents("#cod_group");
			$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
			$input.find("input, span").addClass('error-input');
		}else{
			var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"']");
			$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
			$input.addClass('is-invalid');
		}
	}
</script>
@endsection
