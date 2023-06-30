@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
	
	{!! Form::hidden('dados_devolucao', '', ['id' => 'dados_devolucao']) !!}
	
    <div class="content-fields">
        <div class="col-sm-2 d-none">
            <input type="hidden" class="input-search-bt" name="codigo" id="codigo" value="{{CustomView::retornaClientePadraoId()}}" placeholder="Código de Cadastro" maxlength="250" />
        </div>
        <div class="col-sm-3">
			<div class="input-group">
				<input type="text" class="form-control input-label" name="nome" id="nome" value="{{CustomView::retornaClientePadraoNome()}}" placeholder="Nome / Razão Social" maxlength="250" />
				<span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
			</div>
		</div>
		<div id="cpf-cnpj-unico-div" class="col-sm-3 cpf-cnpj-unico-div d-none">
                {{ Form::select('cpf_cnpj_unico', [], '', ['id' => 'cpf_cnpj_unico', 'class' => 'form-control', 'placeholder' => 'Todos']) }}
		</div>
        <div class="col-sm-2 d-none">
            <input type="hidden" name="cpf_cnpj" id="cpf_cnpj" value="{{CustomView::retornaClientePadraoCPFCNPJ()}}" placeholder="CPF / CNPJ" maxlength="250" disabled="" />
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
		<input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
		<div id='info_cliente' class='float-right'></div>
    </div>
</form>
@endsection
@section('content')
<div class="content-cliente-posicao-sintetica">
	<div>
		<ul class="nav nav-tabs">
			<li class="nav-item">
				<a class="nav-link active" id='posicao-sintetica-header-tab' data-toggle="tab" href="#posicao_sintetica_header" role="tab" aria-controls="posicao_sintetica_header" aria-selected="true">Posição sintética</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="dados-cliente-tab" data-toggle="tab" href="#dados_cliente" onclick="buscarDadosCliente()" role="tab" aria-controls="dados_cliente" aria-selected="false">Dados do Cliente</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="posicao-sintetica-duplicatas-tab" data-toggle="tab" href="#posicao_sintetica_titulos_pagos" role="tab" aria-controls="posicao_sintetica_titulos_pagos" aria-selected="false">Títulos Pagos</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="posicao-sintetica-renegociados-tab" data-toggle="tab" href="#posicao_sintetica_titulos_renegociados" role="tab" aria-controls="posicao_sintetica_titulos_renegociados" aria-selected="false">Títulos Renegociados</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="posicao-sintetica-historico-tab" data-toggle="tab" href="#posicao_sintetica_historico" role="tab" aria-controls="posicao_sintetica_historico" aria-selected="false">Histórico Cobrança</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="posicao-sintetica-devolucoes-tab" data-toggle="tab" href="#posicao_sintetica_devolucoes" onclick="dadosDevolucao()" role="tab" aria-controls="posicao_sintetica_devolucoes" aria-selected="false">Devoluções</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="posicao-sintetica-devolucoes-tab" data-toggle="tab" href="#posicao_sintetica_forma_pagamento" role="tab" aria-controls="posicao_sintetica_forma_pagamento" aria-selected="false">Forma de Pagamento</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="posicao-sintetica-negociacao-titulos-tab" data-toggle="tab" href="#posicao_sintetica_negociacao_titulos" role="tab" aria-controls="posicao_sintetica_negociacao_titulos" aria-selected="false"><div id="aba_acompanhamento_regenociacao">Acomp. Renegociação</div></a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="posicao-sintetica-pesquisa-satisfacao" data-toggle="tab" href="#posicao_sintetica_pesquisa_satisfacao" role="tab" aria-controls="posicao_sintetica_pesquisa_satisfacao" aria-selected="false">Pesquisa de Satisfação</a>
			</li>
		</ul>
	</div>
	<div class="tab-content" id="PedidoHeaderContainer">
		<div class="tab-pane show active" id="posicao_sintetica_header" role="tabpanel" aria-labelledby="dados-tab">

			<div class="content-table_posicao">
				<div class="row-header-table_posicao">
					<div class="cell-table_posicao"></div>
					<div class="cell-table_posicao">A Vencer</div>
					<div class="cell-table_posicao">Vencidos</div>
					<div class="cell-table_posicao">Total</div>
				</div>
				<div>
					<div class="row-table_posicao">
						<div class="cell-table_posicao">Títulos Faturados</div>
						<div class="cell-table_posicao" id="a_vencer-faturado"></div>
						<div class="cell-table_posicao" id="vencido-faturado"></div>
						<div class="cell-table_posicao" id="total-faturado"></div>
					</div>
					<div class="row-table_posicao d-none" id='row-terceiros'>
						<div class="cell-table_posicao">Títulos de terceiros</div>
						<div class="cell-table_posicao" id='a_vencer-terceiros'></div>
						<div class="cell-table_posicao" id='vencido-terceiros'></div>
						<div class="cell-table_posicao" id="total-terceiros"></div>
					</div>
					<div class="row-table_posicao">
						<div class="cell-table_posicao">Créditos</div>
						<div class="cell-table_posicao"></div>
						<div class="cell-table_posicao"></div>
						<div class="cell-table_posicao" id="total-notas_credito"></div>
					</div>
					<div class="row-table_posicao">
						<div class="cell-table_posicao">Notas de Débito</div>
						<div class="cell-table_posicao" id="a_vencer-notas_debito"></div>
						<div class="cell-table_posicao" id="vencido-notas_debito"></div>
						<div class="cell-table_posicao" id="total-notas_debito"></div>
					</div>
					<div class="row-table_posicao d-none">
						<div class="cell-table_posicao">Títulos pré pagos</div>
						<div class="cell-table_posicao" id="a_vencer-pedidos-pre-pagos"></div>
						<div class="cell-table_posicao"></div>
						<div class="cell-table_posicao" id="total-pedidos-pre-pagos"></div>
					</div>
					<div class="row-table_posicao">
						<div class="cell-table_posicao">Saldo Atual</div>
						<div class="cell-table_posicao" id="a_vencer-total"></div>
						<div class="cell-table_posicao" id="vencido-total"></div>
						<div class="cell-table_posicao" id="total-total"></div>
					</div>
				</div>
				<div class="pedidos">
					<div class="title-pedidos-pedidos">Pedidos</div>
					<div class="pedidos-orcamentos">
						<div class="title-pedidos">Orçamentos:</div>
						<div class="valor-pedidos" id="pedidos-orcamentos"></div>
					</div>
					<div class="pedidos-carteira">
						<div class="title-pedidos">Carteira:</div>
						<div class="valor-pedidos" id="pedidos-carteira"></div>
					</div>
					<div class="pedidos-total">
						<div class="title-pedidos">Total:</div>
						<div class="valor-pedidos" id="pedidos-total"></div>
					</div>
				</div>
				<div class="line_limitcred_datedesde">
					<div class="cliente-desde">
						<div class="title-data">Cliente desde:</div>
						<div class="value-data" id="cliente-desde"></div>
					</div>
					<div class="ultima-alteracao">
						<div class="title-ultima-alteracao">Última Alteração:</div>
						<div class="valor-ultima-alteracao" id="ultima-alteracao"></div>
					</div>
					<div class="limite-credito">
						<div class="title-limite-credito">Limite de Crédito:</div>
						<div class="valor-limite-credito" id="limite-credito"></div>
					</div>
					<div class="vencimento-credito">
						<div class="title-vencimento-credito">Valido até:</div>
						<div class="valor-vencimento-credito" id="vencimento-credito"></div>
					</div>
				</div>
				<div class="line_consulta_serasa_motivo_reavaliacao">
					<div class="ultima_consulta_serasa">
						<div class="title">Última consulta no SERASA:
						<br>
						<div class="title">Blacklist:</div><div class="valor" id="status_blacklist"></div></div>
						<div class="valor" id="ultima_consulta_serasa"></div>
					</div>
					<div class="motivo_reavaliacao">
						<div class="title">Motivo da reavaliação do crédito:</div>
						<div class="valor" id="motivo_reavaliacao"></div>
					</div>
				</div>
				<div class="message-salva-cliente" id="mensagem">
				</div>
				<div class="observacao_agrupado"></div>
			</div>
			<div class="content-lateral-table">
				<div class="content-atrasos">
					<div class="title-atraso">Atrasos</div>
					<div class="header-content-atraso-vendas">
						<div>Data</div>
						<div>Dias</div>
					</div>
					<div class="content-ultimo-atraso">
						<label>Último</label>
						<div class="data-atraso" id="data-ultimo-atraso"></div>
						<div class="dias-atraso" id="dias-ultimo-atraso"></div>
					</div>
					<div class="content-maior-atraso">
						<label>Maior</label>
						<div class="data-atraso" id="data-maior-atraso"></div>
						<div class="dias-atraso" id="dias-maior-atraso"></div>
					</div>
				</div>
				<div class="content-vendas">
					<div class="title-vendas">Vendas</div>
					<div class="header-content-atraso-vendas">
						<div>Data</div>
						<div>Valor</div>
					</div>
					<div class="content-ultimo-vendas">
						<label>Último</label>
						<div class="data-vendas" id="data-ultima-venda"></div>
						<div class="valor-vendas" id="valor-ultima-venda"></div>
					</div>
					<div class="content-maior-vendas">
						<label>Maior</label>
						<div class="data-vendas" id="data-maior-venda"></div>
						<div class="valor-vendas" id="valor-maior-venda"></div>
					</div>
				</div>
				<div class="content-total-pago">
					<div class="title-total-vendas">Total pago ult 12 meses</div>
					<div class="valor-total-vendas" id="total-vendas-um-ano"></div>
				</div>
			</div>
		</div>
		<div class="tab-pane show" id="dados_cliente" role="tabpanel" aria-labelledby="dados-tab">

			<div class="content-view-cliente">
				<div class="row-table">
					<div class="cel-table col-lg-3">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Código de cadastro</div></div>
						<div class="value" id="dados_cliente_codigo"></div>
					</div>
					<div class="cel-table col-lg-3">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">CPF / CNPJ</div></div>
						<div class="value" id="dados_cliente_documento"></div>
					</div>
					<div class="cel-table col-lg-3">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Inscrição estadual</div></div>
						<div class="value" id="dados_cliente_inscricao_estadual"></div>
					</div>
					<div class="cel-table col-lg-3">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Indicador da Inscrição Estadual</div></div>
						<div class="value" id="dados_cliente_indicador_inscricao_estadual"></div>
					</div>
				</div>
				<div class="row-table">
					<div class="cel-table col-lg-6">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Nome / Razão Social</div></div>
						<div class="value" id="dados_cliente_nome_razao_social"></div>
					</div>
					<div class="cel-table col-lg-6">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Nome Fantasia / Apelido</div></div>
						<div class="value" id="dados_cliente_nome_fatasia"></div>
					</div>
				</div>
				<div class="row-table">
					<div class="cel-table col-lg-12">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Telefone</div></div>
						<div class="value" id="dados_cliente_telefones"></div>
					</div>
				</div>
				<div class="row-table">
					<div class="cel-table col-lg-4">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">E-mail (NFe)</div></div>
						<div class="value" id="dados_cliente_email"></div>
					</div>
				</div>

				<div class="row-table d-none" id="dados_cliente_contatos">
				</div>

				<div class="row-table">
					<div class="cel-table col-lg-1">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">CEP</div></div>
						<div class="value" id="dados_cliente_cep"></div>
					</div>
					<div class="cel-table col-lg-3">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Endereço</div></div>
						<div class="value" id="dados_cliente_endereco"></div>
					</div>
					<div class="cel-table col-lg-1">
							<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Nº</div></div>
							<div class="value" id="dados_cliente_numero"></div>
						</div>
					<div class="cel-table col-lg-3">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Bairro</div></div>
						<div class="value" id="dados_cliente_bairro"></div>
					</div>
					<div class="cel-table col-lg-3">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Cidade</div></div>
						<div class="value" id="dados_cliente_cidade"></div>
					</div>
					<div class="cel-table col-lg-1">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Estado</div></div>
						<div class="value" id="dados_cliente_estado"></div>
					</div>
				</div>

				<div class="row-table" id="dados_clientes_comercial">
					<div class="cel-table col-lg-4">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Representante</div></div>
						<div class="value" id="dados_clientes_representante"></div>                
					</div>

					<div class="cel-table col-lg-3">
						<div class="title">Gerente</div>
						<div class="value" id="dados_clientes_gerentes"></div>
					</div>

					<div class="cel-table col-lg-2">
						<div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Cliente Desde</div></div>
						<div class="value" id="dados_clientes_desde"></div>
					</div>
				</div>

				<div class="row-table d-none" id="dados_clientes_contratos">
					<div class="cel-table col-lg-3">
						<div class="title">Contrato</div>
						<div class="value" id="dados_clientes_contrato"></div>                
					</div>
					<div class="cel-table col-lg-3">
						<div class="title">E-mail</div>
						<div class="value" id="dados_clientes_contrato_email"></div>                
					</div>
					<div class="cel-table col-lg-3">
						<div class="title">Ip</div>
						<div class="value" id="dados_clientes_contrato_ip"></div>                
					</div>
					<div class="cel-table col-lg-3">
						<div class="title">Data</div>
						<div class="value" id="dados_clientes_contrato_data"></div>                
					</div>
				</div>

				<div class="content-tab d-none" id="dados_cliente_documentos">
					<div class="row">
						<div class="col-md-12">
							<h3><center>Documentos</center></h3>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="content-dialog-table">
								<table class="table table-striped table-not-edit table-not-view" id="table-documentos">
									<thead>
										<tr>
											<th>Descrição</th>
											<th>Documento</th>
										</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>

			</div>

		</div>
		<div class="tab-pane show" id="posicao_sintetica_titulos_pagos" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="row mb-4">
					<div class="col-sm-3">
						{{ Form::label('data_inicio_titulos_pagos', 'Inicio do período (Pagamento)') }}
						{{ Form::text('data_inicio_titulos_pagos', date("d/m/Y", strtotime("-1 year")), ['id' => 'data_inicio_titulos_pagos', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-4">
						{{ Form::label('data_fim_titulos_pagos', 'Fim do período (Pagamento)') }}
		                {{ Form::text('data_fim_titulos_pagos', date("d/m/Y"), ['id' => 'data_fim_titulos_pagos', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-1">
						<br>
		                {{ Form::button('Filtrar', ['class' => 'btn btn-primary btn-filter-titulos-pagos']) }}
					</div>
				</div>
				<div class="row">
					<div class="col-sm-3"><strong>Títulos: </strong><span id="titulos-contador"></span></div>
					<div class="col-sm-3"><strong>Valor: </strong><span id="titulos-valor"></span></div>
					<div class="col-sm-3"><strong>Em Atraso: </strong><span id="titulos-atraso"></span></div>
					<div class="col-sm-3"><strong>Dias Médios de Atraso: </strong><span id="titulos-media-atraso"></span></div>
				</div>
				<table class="table table-striped table-filter table-filter-clientes" id="table-filters-titulos-pagos">
			        <thead>
			            <tr>
							<th>Estab</th>
							<th class="cliente">Cliente</th>
							<th>Título</th>
							<th>Data Emissao</th>
							<th>Data Vencto</th>
							<th>Data pagto</th>
							<th>Dias atraso</th>
							<th>Valor Original</th>
							<th>Valor Pago</th>
							<th>Juros</th>
							<th>Desconto</th>
							<th>Port</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
		<div class="tab-pane show" id="posicao_sintetica_titulos_renegociados" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="row mb-4">
					<div class="col-sm-3">
						{{ Form::label('data_inicio_renegociacao', 'Inicio do período (Vencimento)') }}
						{{ Form::text('data_inicio_renegociacao', date("d/m/Y", strtotime("-1 year")), ['id' => 'data_inicio_renegociacao', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-4">
						{{ Form::label('data_fim_renegociacao', 'Fim do período (Vencimento)') }}
		                {{ Form::text('data_fim_renegociacao', date("d/m/Y"), ['id' => 'data_fim_renegociacao', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-1">
						<br>
		                {{ Form::button('Filtrar', ['class' => 'btn btn-primary btn-filter-titulos-renegociados']) }}
					</div>
				</div>
				<div class="row">
					<div class="col-sm-3"><strong>Títulos: </strong><span id="titulos-contador-renegociados"></span></div>
					<div class="col-sm-3"><strong>Valor: </strong><span id="titulos-valor-renegociados"></span></div>
				</div>
				<table class="table table-striped table-filter table-filter-clientes" id="table-filters-titulos-renegociados">
			        <thead>
			            <tr>
							<th>Estabelecimento</th>
							<th class="cliente">Cliente</th>
							<th>Título</th>
							<th class="date_format">Data Emissao</th>
							<th class="date_format">Data Vencimento</th>
							<th class="date_format">Data Renegociação</th>
							<th class="tb_number">Valor Original</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
		<div class="tab-pane show" id="posicao_sintetica_historico" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="row mb-1 mt-1">
					<div class="col align-self-end">
						{{ Form::button('Adicionar', ['class' => 'btn btn-success btn-adicionar-historico float-right']) }}
					</div>
				</div>
				<table class="table table-striped table-filter" id="table-filters-historico">
			        <thead>
			            <tr>
							<th class="tb_date">Data / Hora</th>
							<th>Usuário</th>
							<th>Contato</th>
							<th>Retorno</th>
							<th>Titulos</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
		<div class="tab-pane" id="posicao_sintetica_devolucoes" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<table class="table table-striped table-not-edit" id="table-filters-devolucoes">
					<thead>
						<tr>
							<th class='tb_number'>Processo</th>
							<th class='tb_number'>Nota venda</th>
							<th class='tb_number'>Nota devolução</th>
							<th class='cliente tb_name'>Cliente</th>
							<th>Tipo Devolução</th>
							<th class='tb_name'>Motivo</th>
							<th class='tb_number'>Valor</th>
							<th class='tb_name'>Fase</th>
							<th class='tb_number'>Dias fase</th>
							<th class='tb_number'>Dias aberto</th>
							<th>Origem</th>
							<th class='tb_date'>Data requisição</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
		<div class="tab-pane" id="posicao_sintetica_forma_pagamento" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="row mb-4">
					<div class="col-sm-3">
						{{ Form::label('data_inicio_forma_pagamento', 'Inicio do período (Vencimento)') }}
						{{ Form::text('data_inicio_forma_pagamento', date("d/m/Y", strtotime("-1 year")), ['id' => 'data_inicio_forma_pagamento', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-4">
						{{ Form::label('data_fim_forma_pagamento', 'Fim do período (Vencimento)') }}
		                {{ Form::text('data_fim_forma_pagamento', date("d/m/Y"), ['id' => 'data_fim_forma_pagamento', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-1">
						<br>
		                {{ Form::button('Filtrar', ['class' => 'btn btn-primary btn-filter-forma-pagamento']) }}
					</div>
				</div>
				<table class="table table-striped table-not-edit" id="table-filters-forma-pagamento">
					<thead>
						<tr>
							<th>Forma</th>
							<th class='tb_number'>Títulos</th>
							<th class='tb_number'>Valor</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
					<tfoot>
						<td></td>
						<td></td>
						<td></td>
					</tfoot>    
				</table>
			</div>
		</div>
		<div class="tab-pane" id="posicao_sintetica_negociacao_titulos" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="row mb-4">
					<div class="col-sm-3">
						{{ Form::label('data_inicio_negociacao_titulos', 'Inicio do período') }}
						{{ Form::text('data_inicio_negociacao_titulos', date("d/m/Y", strtotime("-1 year")), ['id' => 'data_inicio_negociacao_titulos', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-4">
						{{ Form::label('data_fim_negociacao_titulos', 'Fim do período') }}
		                {{ Form::text('data_fim_negociacao_titulos', date("d/m/Y"), ['id' => 'data_fim_negociacao_titulos', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-1">
						<br>
		                {{ Form::button('Filtrar', ['class' => 'btn btn-primary', 'id' => 'btn_filtro_renegociacao']) }}
					</div>
				</div>
				<table class="table table-striped table-not-edit" id="table-filters-renegociacao-titulos">
					<thead>
						<tr>
							<th class='tb_date'>Data</th>
							<th>Titulos</th>
							<th class="tb_number">Valor dos Títulos</th>
							<th class="tb_number">Juros Atual.</th>
							<th class="tb_number">Valor Atual.</th>
							<th class="tb_number">Juros por Mês</th>
							<th class="tb_number">Valor Renegociação</th>
							<th class="tb_number">Parcelas</th>
							<th class="tb_number">Período(dias)</th>
							<th>status</th>
							<th class="td_acao"></th>
							<th class="td_acao"></th>
							<th class="td_acao"></th>
						</tr>
					</thead>
					<tbody>
					</tbody>
					<tfoot>
						<td></td>
						<td></td>
						<td></td>
					</tfoot>    
				</table>
			</div>
		</div>
		<div class="tab-pane" id="posicao_sintetica_pesquisa_satisfacao" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-dialog-table">
				<div class="row mb-4">
					<div class="col-sm-3">
						{{ Form::label('data_inicio_pesquisa_satisfacao', 'Inicio do período') }}
						{{ Form::text('data_inicio_pesquisa_satisfacao', date("d/m/Y", strtotime("-1 year")), ['id' => 'data_inicio_pesquisa_satisfacao', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-4">
						{{ Form::label('data_fim_pesquisa_satisfacao', 'Fim do período') }}
		                {{ Form::text('data_fim_pesquisa_satisfacao', date("d/m/Y"), ['id' => 'data_fim_pesquisa_satisfacao', 'class' => 'form-control data', 'form' => 'form_filter']) }}
					</div>
					<div class="col-sm-1">
						<br>
		                {{ Form::button('Filtrar', ['class' => 'btn btn-primary', 'id' => 'btn_filtro_pesquisa_satisfacao']) }}
					</div>
				</div>
				<table class="table table-striped table-not-edit" id="table-filters-pesquisa-satisfacao">
					<thead>
						<tr>
							<th class='tb_date w-25'>Data</th>
							<th>E-mail</th>
							<th>Notas</th>
							<th class="w-25">Formulario</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
					<tfoot>
						<td></td>
						<td></td>
						<td></td>
					</tfoot>    
				</table>
			</div>
		</div>
	</div>
</div>
@endsection
@section('script-footer')

    table_filters_titulos_pagos = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "pageLength": 10,
        "processing": true,
        "orderMulti": false,
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
        'columnDefs': [
			{
				"targets": [6,7,8,9,10],
                "className": 'number_format',
			},
			{
				"targets": [3,4,5],
                "className": 'date_format',
			},
			{
				"targets": [0,1,2,11],
                "className": 'text_format',
			},
            
        ]
    };

	table_filters_titulos = $("#table-filters-titulos-pagos").DataTable(table_filters_titulos_pagos);

	table_filters_titulos_renegociados = $('#table-filters-titulos-renegociados').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "pageLength": 10,
        "processing": true,
        "orderMulti": false,
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
				"targets": "tb_number",
				"class": "tb_number",
			},
			{
				"targets": "date_format",
				"class": "date_format",
			}
		]
	});
	
	table_filters_forma_pagamento = $('#table-filters-forma-pagamento').DataTable({
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
				"targets": "tb_number",
				"class": "tb_number",
			},
		]
	});

	table_filters_devolucoes = $('#table-filters-devolucoes').DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"orderMulti": false,
		'paging': false,
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
			{
				"class": "tb_number", 
				"targets": "tb_number"
			},
			{ "class": "tb_date", targets: "tb_date" },
			{ targets: 'tb_name', width: '20%'}
		],
		"order": [[ 11, 'desc' ]]
	});

    table_historico_opt = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": -1,
        "processing": true,
        "orderMulti": false,
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
			{ "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
			{ "class": "tb_date", targets: "tb_date" }
		],
		"order": [0, 'desc']
    };

	table_historico = $("#table-filters-historico").DataTable(table_historico_opt);
	
	table_filters_renegociacao_titulo = $('#table-filters-renegociacao-titulos').DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"orderMulti": false,
		"autoWidth": false,
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
				"targets": "tb_number",
				"class": "tb_number",
			},
			{
				"targets": "tb_date",
				"class": "tb_date",
			}
		]
	});

	table_filters_pesquisa_satisfacao = $('#table-filters-pesquisa-satisfacao').DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"orderMulti": false,
		"autoWidth": false,
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
				"targets": "tb_number",
				"class": "tb_number",
			},
			{
				"targets": "tb_date",
				"class": "tb_date",
			}
		]
	});

	table_filters_pesquisa_satisfacao.on('draw', function () {
		$(document).find(".bt-view-formulario").off("click");
		$(document).find(".bt-view-formulario").on("click", function(event){
			event.stopPropagation();
			showModalFormulario($(this));
		});
	});

	var table_documentos = '';

var contador_forma_pagamento = 0;

$(document).ready( function () {
	$(document).find('.btn-filter-titulos-pagos').on('click', function(){
		titulosPagos();
	});
	$(document).find('.btn-filter-titulos-renegociados').on('click', function(){
		titulosRenegociados();
	});
	$(document).find('.btn-filter-forma-pagamento').on('click', function(){
		titulosFormaPagamento();
	});
	$(document).find('.btn-adicionar-historico').on('click', function(){
		modalAdicionarHistorico();
	});
	$(document).find(".btn-filter").on("click", function(){
		getDados($(document).find("#form_filter").serialize());
	});
	$(document).find("#btn_filtro_renegociacao").on("click", function(){
        filterAjax();
    });
	$(document).find("#btn_filtro_pesquisa_satisfacao").on("click", function(){
        pesquisaSatisfacaoFilterAjax();
    });
	$(document).find("#form_filter").find("#bt-search-cliente-busca").on("click", function(){
		showModal($(this).data("route"), "Lista de Clientes");
	});
	$(document).find(".bt-modal-open").on("click",function(){
		showModalOpen($(this));
	});
    $(document).find("#codigo").on('change', function(){
		codParaNome($(this).val());
	});
    $(document).find("#codigo").on("focus", function(){
        $(this).data('oldvalue', $(this).val());
	});
	
	$(document).find('.data').mask('00/00/0000');
	$(document).find('.data').datepicker({
	    language: 'pt-BR',
	    format: 'dd/mm/yyyy',
	    zIndex: 100,
	    autoHide: true
	});

    $(document).find("#nome").autocomplete(optionsAutoCompleteCliente());

	$(document).find("#form_filter").find("#btn-clearform").on("click", function(){
		clearTela();
        $form = $(this).parents('form');
        $.ajax({
            url: '{{ route('cliente.apagaClientePadrao') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(){
                $form.find('input, select').not('[class^=btn-]').not('[name=_token]').val('');
            }
        });
	});

	
});

function chamarTitulosPagos(){
	if($(document).find("#titulos-contador").html().length > 0){
		return false;
	}else{
		titulosPagos();
	}
}

function chamarTitulosRenegociados(){
	if($(document).find("#titulos-contador-renegociados").html().length > 0){
		return false;
	}else{
		titulosRenegociados();
	}
}

function chamarFormaPagamento(){
	if(contador_forma_pagamento > 0){
		return false;
	}else{
		contador_forma_pagamento ++;
		titulosFormaPagamento();
	}
}

function titulosPagos(){
	table_filters_titulos.clear().draw();
    $('label.error-message').remove();
	$.ajax({
        url: '{{ route('cliente.posicao_sintetica.titulos_pagos') }}',
        type: 'post',
        dataType: 'json',
        data: {
			_token: '{{ csrf_token() }}',
			data_inicio_titulos_pagos: $(document).find("#data_inicio_titulos_pagos").val(),
			data_fim_titulos_pagos: $(document).find("#data_fim_titulos_pagos").val(),
			nome: $(document).find("#nome").val(),
			cpf_cnpj_unico: $(document).find("#cpf_cnpj_unico").val(),
			codigo: $(document).find("#codigo").val()
		},
        success: function(callback){
			var data = callback.response;
			var fields_filter = [];
			for(var field in data.titulos_pagos){
				var temp_field = [
					data.titulos_pagos[field].estabelecimento,
					data.titulos_pagos[field].clientenome,
					data.titulos_pagos[field].titulo,
					data.titulos_pagos[field].data_emissao,
					data.titulos_pagos[field].data_vencimento,
					data.titulos_pagos[field].data_pagamento,
					data.titulos_pagos[field].atraso,
					data.titulos_pagos[field].valor_titulo,
					data.titulos_pagos[field].valor,
					data.titulos_pagos[field].juros,
					data.titulos_pagos[field].desconto,
					data.titulos_pagos[field].portador
				];
				
				fields_filter.push(temp_field);
			}
			if (data.grupoCliente == 1){
				table_filters_titulos.columns('.cliente').visible(false);
			}else{
				table_filters_titulos.columns('.cliente').visible(true);
			}
			table_filters_titulos.rows.add(fields_filter).draw().nodes();

			$("#titulos-contador").html(data.titulos);
			$("#titulos-valor").html(data.valor_total);
			$("#titulos-atraso").html(data.em_atraso);
			$("#titulos-media-atraso").html(data.media_atraso);
        },
        error: function(callback) {
			if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                $.each(data, function(index, el) {
                    $(document).find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                    $(document).find('input[name="'+index+'"]').eq(0).addClass('error');
                });
                $(document).find('input.error').eq(0).focus();
            }
        }
    });
}

function titulosRenegociados(){
	table_filters_titulos_renegociados.clear().draw();
    $('label.error-message').remove();
	$.ajax({
        url: '{{ route('cliente.posicao_sintetica.titulos_renegociados') }}',
        type: 'post',
        dataType: 'json',
        data: {
			_token: '{{ csrf_token() }}',
			data_inicio_renegociacao: $(document).find("#data_inicio_renegociacao").val(),
			data_fim_renegociacao: $(document).find("#data_fim_renegociacao").val(),
			nome: $(document).find("#nome").val(),
			cpf_cnpj_unico: $(document).find("#cpf_cnpj_unico").val(),
			codigo: $(document).find("#codigo").val()
		},
        success: function(callback){
			var data = callback.response;
			
			if(data.titulos_renegociados_array.length > 0){
				var fields_filter = [];
				for(var field in data.titulos_renegociados_array){

					var temp_field = [
						"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" +data.titulos_renegociados_array[field].estabelecimento+ "''>" + data.titulos_renegociados_array[field].estabelecimento + "</div></div>",
						"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" +data.titulos_renegociados_array[field].clientenome+ "''>" + data.titulos_renegociados_array[field].clientenome + "</div></div>",
						data.titulos_renegociados_array[field].titulo,
						data.titulos_renegociados_array[field].data_emissao,
						data.titulos_renegociados_array[field].data_vencimento,
						data.titulos_renegociados_array[field].data_pagamento,
						data.titulos_renegociados_array[field].valor_titulo,
					];
					
					fields_filter.push(temp_field);
				}
				if (data.grupoCliente == 1){
					table_filters_titulos_renegociados.columns('.cliente').visible(false);
				}else{
					table_filters_titulos_renegociados.columns('.cliente').visible(true);
				}
				table_filters_titulos_renegociados.rows.add(fields_filter).draw().nodes();
			}
			
			$(document).find("#titulos-contador-renegociados").html(data.titulos_renegociados);
			$(document).find("#titulos-valor-renegociados").html(data.valor_total_renegociados);
        },
        error: function(callback) {
			if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                $.each(data, function(index, el) {
                    $(document).find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                    $(document).find('input[name="'+index+'"]').eq(0).addClass('error');
                });
                $(document).find('input.error').eq(0).focus();
            }
        }
    });
}

function titulosFormaPagamento(){
	table_filters_forma_pagamento.clear().draw();
    $('label.error-message').remove();
	$.ajax({
        url: '{{ route('cliente.posicao_sintetica.titulos_forma_pagamento') }}',
        type: 'post',
        dataType: 'json',
        data: {
			_token: '{{ csrf_token() }}',
			data_inicio_forma_pagamento: $(document).find("#data_inicio_forma_pagamento").val(),
			data_fim_forma_pagamento: $(document).find("#data_fim_forma_pagamento").val(),
			nome: $(document).find("#nome").val(),
			cpf_cnpj_unico: $(document).find("#cpf_cnpj_unico").val(),
			codigo: $(document).find("#codigo").val()
		},
        success: function(callback){
			var data = callback.response;
			
			$(table_filters_forma_pagamento.column(0).footer()).html('');
			$(table_filters_forma_pagamento.column(1).footer()).html('');
			$(table_filters_forma_pagamento.column(2).footer()).html('');
			if(data.forma_pagamento != null){
				var fields_filter_forma_pagamento = [];
				for(var field in data.forma_pagamento){
					var temp_forma_pagamento = [
						data.forma_pagamento[field].forma_pagamento,
						data.forma_pagamento[field].titulo,
						data.forma_pagamento[field].valor,
					];
					fields_filter_forma_pagamento.push(temp_forma_pagamento);
				}
				$(table_filters_forma_pagamento.column(0).footer()).html('Total');
				$(table_filters_forma_pagamento.column(1).footer()).html(data.total_forma_pagamento.titulo);
				$(table_filters_forma_pagamento.column(2).footer()).html(data.total_forma_pagamento.valor);
				table_filters_forma_pagamento.rows.add(fields_filter_forma_pagamento).draw().nodes();
				
			}
        },
        error: function(callback) {
			if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                $.each(data, function(index, el) {
                    $(document).find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                    $(document).find('input[name="'+index+'"]').eq(0).addClass('error');
                });
                $(document).find('input.error').eq(0).focus();
            }
        }
    });
}

function modalCobrancaJudicial(){
	var $cliente_codigo = $(document).find("#codigo").val();
	var $cliente_nome = $(document).find("#nome").val();
	$title = 'Selecione os Títulos para Renegociação: '+$cliente_nome;
    $.ajax({
        url: '{{ route('cliente.posicao_sintetica.cobranca_ragazzi') }}',
        type: 'post',
        data: {
			_token: '{{ csrf_token() }}',
			codigo_cliente: $(document).find("#codigo").val()
		},
        success: function(callback){
			createModal("cobranca_judicial_ragazzi", $title, callback, 'modal-lg');
        },
        error: function(data) {
        }
    });
}



function buscarDadosCliente(){
	var data_form = $(document).find("#form_filter").serialize();
	if($(document).find("#dados_cliente_codigo").html().length > 0 || $(document).find("#dados_cliente_documento").html().length > 0){
		return false;
	}

    $.ajax({
        url: '{{ route('cliente.posicao_sintetica.dados_clientes') }}',
		dataType: 'json',
		data: data_form,
		method: 'POST',
        success: function(callback){
            var data = callback.response;

			if(data.dados){
				$("#dados_cliente_codigo").html(data.dados.codigo);
				$("#dados_cliente_documento").html(data.dados.cpf_cnpj);
				if(data.dados.inscricao_estadual){
					$("#dados_cliente_inscricao_estadual").html(data.dados.inscricao_estadual);
				}else{
					$("#dados_cliente_inscricao_estadual").html(data.dados.inscricaoestadual);
				}

				$("#dados_cliente_indicador_inscricao_estadual").html(data.dados.indicadorinscricaoestadual);
				if(data.dados.nome_razao){
					$("#dados_cliente_nome_razao_social").html(data.dados.nome_razao);
				}else{
					$("#dados_cliente_nome_razao_social").html(data.dados.nome);
				}
				if(data.dados.guerra_apelido){
					$("#dados_cliente_nome_fatasia").html(data.dados.guerra_apelido);
				}else{
					$("#dados_cliente_nome_fatasia").html(data.dados.nomefantasia);
				}
				if(data.dados.telefone){
					$("#dados_cliente_telefones").html(data.dados.telefone);
				}else{
					$("#dados_cliente_telefones").html(data.dados.telefones);
				}
				$("#dados_cliente_email").html(data.dados.email);

				if(data.dados.contatos != null){
					var html_contato = "";

					for(var contato in data.dados.contatos){
						if(data.dados.contatos[contato].nome != null){
							html_contato += 
							"<div class='cel-table col-md-3'>"+
								"<div class='title'>Contato: </div>"+
								"<div class='value'>"+ data.dados.contatos[contato].nome +"</div>"+
							"</div>";
						}
						if(data.dados.contatos[contato].cargo != null){
							html_contato += 
							"<div class='cel-table col-md-3'>"+
								"<div class='title'>Cargo: </div>"+
								"<div class='value'>"+ data.dados.contatos[contato].cargo +"</div>"+
							"</div>";
						}
						if(data.dados.contatos[contato].telefone != null){
							html_contato += 
							"<div class='cel-table col-md-3'>"+
								"<div class='title'>Telefone: </div>"+
								"<div class='value'>"+ data.dados.contatos[contato].telefone +"</div>"+
							"</div>";
						}
						if(data.dados.contatos[contato].email != null){
							html_contato += 
							"<div class='cel-table col-md-3'>"+
								"<div class='title'>E-mail: </div>"+
								"<div class='value'>"+ data.dados.contatos[contato].email +"</div>"+
							"</div>";
						}
					}
					$('#dados_cliente_contatos').removeClass('d-none');
					$("#dados_cliente_contatos").html(html_contato);
					
				}else{
					$("#dados_cliente_contatos").html("");
					$('#dados_cliente_contatos').addClass('d-none');
				}

				if(data.dados.cobranca_cep){
					$("#dados_cliente_cep").html(data.dados.cobranca_cep);
				}else{
					$("#dados_cliente_cep").html(data.dados.cep);
				}

				if(data.dados.cobranca_logradouro){
					$("#dados_cliente_endereco").html(data.dados.cobranca_logradouro);
				}else{
					$("#dados_cliente_endereco").html(data.dados.logradouro);
				}
				if(data.dados.cobranca_numero){
					$("#dados_cliente_numero").html(data.dados.cobranca_numero);
				}else{
					$("#dados_cliente_numero").html(data.dados.numero);
				}
				if(data.dados.cobranca_bairro){
					$("#dados_cliente_bairro").html(data.dados.cobranca_bairro);
				}else{
					$("#dados_cliente_bairro").html(data.dados.bairro);
				}
				if(data.dados.cobranca_cidade){
					$("#dados_cliente_cidade").html(data.dados.cobranca_cidade);
				}else{
					$("#dados_cliente_cidade").html(data.dados.cidade);
				}
				if(data.dados.cobranca_estado){
					$("#dados_cliente_estado").html(data.dados.cobranca_estado);
				}else{
					$("#dados_cliente_estado").html(data.dados.uf);
				}
				$("#dados_clientes_desde").html(data.dados.data_desde);

				if(data.dados.vendedor != null || data.dados.gerente != null || data.dados.cliente_desde != null || data.dados.data_desde != null || data.dados.vendedor.codigo != null || data.dados.data_desde){

					$('#dados_clientes_comercial').removeClass('d-none');
					if(data.dados.vendedor.codigo){
						$("#dados_clientes_representante").html(data.dados.vendedor.codigo + ' ' + data.dados.vendedor.nome);
					}else{
						$("#dados_clientes_representante").html(data.dados.vendedor);
					}
					$("#dados_clientes_gerentes").html(data.dados.gerente);

					if(data.dados.data_desde){
						$("#dados_clientes_desde").html(data.dados.data_desde);
					}else{
						$("#dados_clientes_desde").html(data.dados.cliente_desde);
					}
				}else{
					$('#dados_clientes_comercial').addClass('d-none');
				}

				if(data.dados.contrato_caminho != '' || data.dados.contrato_email != '' || data.dados.contrato_ip != '' || data.dados.contrato_criacao != ''){
					$('#dados_clientes_contratos').removeClass('d-none');
					var link_contato = "<a href="+ data.dados.contrato_caminho +" target='_blanck' class='bt_manual_cliente text-right'><i class='btn-nota-pdf'></i>Contrato Fornecimento</a>";
					$("#dados_clientes_contrato").html(link_contato);
					$("#dados_clientes_contrato_email").html(data.dados.contrato_email);
					$("#dados_clientes_contrato_ip").html(data.dados.contrato_ip);
					$("#dados_clientes_contrato_data").html(data.dados.contrato_criacao);
				}else{
					$('#dados_clientes_contratos').addClass('d-none');
				}

				if(data.dados.documentos.length > 0){
					var html_documentos = "";
					$('#dados_cliente_documentos').removeClass('d-none');

					if(table_documentos == ''){
						table_documentos = $('#table-documentos').on( 'error.dt', function ( e, settings, techNote, men ) {
							hide_loader();
							message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
						}).DataTable({
						"show": function(event, ui) {
							var oTable = $('div.dataTables_scrollBody>table.display', ui.panel).dataTable();
							if ( oTable.length > 0 ) {
								oTable.fnAdjustColumnSizing();
							}
						},
						"searching": false,
						"lengthChange": false,
						"info": false,
						"autoWidth": true,
						"pageLength": 15,
						"orderMulti": false,
						"sScrollY": "200px",
						"bScrollCollapse": true,
						"bPaginate": false,
						"bJQueryUI": true,
						"drawCallback": function(settings) {
							$('[data-toggle="popover"]').popover({
								container: 'body',
								html: true,
								show: true,
								template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
							});
							$('[data-toggle="popover"]').on('show.bs.popover', function () {
								var $this = $(this);
								$('.popover').not($this).each(function(){
									$("[aria-describedby='"+$(this).attr("id")+"']").popover('hide');
								});
								$("body").on("keyup", function(e){
									if(e.keyCode == 27){
										$($this).popover('hide');
									}
								});
							});
				
							$(document).find("a.thumb").fancybox(
								{
									onComplete: function(){
										$('#fancybox-content')
											.on('mouseover', function(){
												$(this).children('#fancybox-img').css({'transform': 'scale(1.5)'});
											})
											.on('mouseout', function(){
												$(this).children('#fancybox-img').css({'transform': 'scale(1)'});
											})
											.on('mousemove', function(e){
												$(this).children('#fancybox-img').css({'transform-origin': ((e.pageX - $(this).offset().left) / $(this).width()) * 100 + '% ' + ((e.pageY - $(this).offset().top) / $(this).height()) * 100 +'%'});
										});
									}
								}
							);
				
						},
						"language": {
							"decimal":        ".",
							"emptyTable":     "Nenhum Documento Encontrado",
							"infoPostFix":    "",
							"thousands":      ",",
							"loadingRecords": "Carregando...",
							"processing":     "Processando...",
							"zeroRecords":    "Nenhum Documento Encontrado",
							"paginate": {
								"first":      "<<",
								"last":       ">>",
								"next":       ">",
								"previous":   "<"
							}
						},
					});
				}

				var campos_documentos = [];

				for(var documentos in data.dados.documentos){

					var link_documento = '';

					if(data.dados.documentos[documentos].extensao == 'jpg' || data.dados.documentos[documentos].extensao == 'png' || data.dados.documentos[documentos].extensao == 'PNG' || data.dados.documentos[documentos].extensao == 'JPG'){
						link_documento = "<a href=" + data.dados.documentos[documentos].documento +" data-toggle='popover' data-trigger='hover' aria-readonly='true' title='Documento' data-content=\"<img src='" + data.dados.documentos[documentos].documento +"' width='250' class='rounded mx-auto d-block' alt='Document'>\" class='thumb' alt='Documento'><i class='btn-foto-canhoto'></i>Documento</a>";
					}else{
						link_documento = "<a href=" + data.dados.documentos[documentos].documento +" title=\"Documento\" data-content=" + data.dados.documentos[documentos].documento +" target=\"blank\"><i class=\"btn-download\"></i>Documento</a>";
					}

					var temp_documentos = [
						data.dados.documentos[documentos].descricao,
						link_documento,
					];
					
					campos_documentos.push(temp_documentos);

				}

				table_documentos.rows.add(campos_documentos).draw().nodes();
					
				}else{
					$('#dados_cliente_documentos').addClass('d-none');
					if(table_documentos != ''){
						table_documentos.clear();
					}
				}
				
			}

        },
        error: function(data) {
			message('Atenção', 'Houve um erro ao tentar buscar os dados do cliente, por favor tente mais tarde.');
        }
    });

}

function optionsAutoCompleteCliente(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
			clearTela();
			$(document).find(".error-message").remove();
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('clientes.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Nenhum cliente encontrado');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#codigo").val(ui.item.value);
            $(document).find("#nome").val(ui.item.label);
            return false;
        }
    };
}
function codParaNome($id){
    form = $(document).find('#cadCondicao');
    
    form.find('.error-message').remove();
    $(document).find('#codigo').removeClass('error-input');
    $(document).find('#nome').removeClass('error-input');
    $(document).find('#bt-search-cliente-busca').removeClass('error-input');

    $.ajax({
        url: '{{ route('cliente.codparanome') }}',
        type: 'post',
        dataType: 'json',
        data: {_token: '{{ csrf_token() }}', codigo: $id},
        success: function(callback){
            $(document).find("#nome").val(callback.response.nome);
            $(document).find("#cpf_cnpj").val(callback.response.cpf_cnpj);
            $(document).find('#codigo').data('oldvalue', $id);

        },
        error: function(data) {
            $(document).find('#add-clientes-button').after("<label class='error-message' for='bt-search-cliente-busca'>Cliente não encontrado</label>");
            $(document).find('#codigo').addClass('error-input');
            $(document).find('#nome').addClass('error-input');
            $(document).find('#bt-search-cliente-busca').addClass('error-input');
            
            $(document).find("#codigo").val($(document).find('#codigo').data('oldvalue'));
            $(document).find("#codigo").focus();

            setTimeout(function(){
                $(document).find('#codigo').removeClass('error-input');
                $(document).find('#nome').removeClass('error-input');
                $(document).find('#bt-search-cliente-busca').removeClass('error-input');
                $(document).find("#cod_cliente_group").find('.error-message').fadeOut('slow', function(){ $('this').remove(); });
            }, 2000)
        }
    });
}
function showModal(url, title){
	$.ajax({
		url: url,
		method: 'GET',
		success: function(body){
			createModal("cliente_searsh_show", title, body, 'modal-lg');
			$(document).ready( function () {
				table_dialog.on('draw', function () {
					$(document).find("#cliente_searsh_show").find('tbody').find("tr").off("click");
					$(document).find("#cliente_searsh_show").find('tbody').find("tr").on("click", function(){
						returnDados($(this));
					});
				});
			});
		}
	});
}
function showModalOpen($this){
	var $url = $($this).data("route");
	var $codigo = $($this).data("codigo");
	var $title = $($this).data("title-modal");
	var $unico = $($this).data("unico");

	$.ajax({
		url: $url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", codigo: $codigo, unico: $unico},
		success: function(body){
			createModal("cliente_analise_open_modal", $title, body, 'modal-lg');
		}
	});
}
function returnDados($dados){
	if($dados.find("td").eq(0).hasClass('dataTables_empty')){
		return false;
	}
	$(document).find("#cliente_searsh_show").modal("hide");
	$("#codigo").val($dados.find("td").eq(0).text());
	$("#nome").val($dados.find("td").eq(1).text());
	$("#cpf_cnpj").val($dados.find("td").eq(3).text());
	getDados($("#form_filter").serialize());
}
function showPedidos($method, $codigo, $unico){
	var title = "Pedidos a faturar do Cliente: " + $(document).find('#modal_nome').html();
	
	$.ajax({
		url: "{{ route('pedidos_orcamentos.pedidos_abertos') }}",
		data: {_token: '{{ csrf_token() }}', codigo: $codigo, method: $method, unico: $unico},
		method: 'POST',
		success: function(body){
			createModal("show_pedidos", title, body, 'modal-lg');
		}
	});
}
function drawPopOver(){
	$('[data-toggle="popover"]').popover({
		container: 'body',
		html: true,
		show: true,
		template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
	});
	$('[data-toggle="popover"]').on('show.bs.popover', function () {
		var $this = $(this);
		$('.popover').not($this).each(function(){
			$("[aria-describedby='"+$(this).attr("id")+"']").popover('hide');
		});
		$("body").on("keyup", function(e){
			if(e.keyCode == 27){
				$($this).popover('hide');
			}
		});
	});
};
function getDados(data_form){
	clearTela();

	$('#cpf_cnpj_unico').find('option').remove();

	data_form.data_inicio = $("#data_inicio").val();
	data_form.data_fim = $("#data_fim").val();

	data_form.data_inicio_forma_pagamento = $("#data_inicio_forma_pagamento").val();
	data_form.data_fim_forma_pagamento = $("#data_fim_forma_pagamento").val();

	data_form.data_inicio_renegociacao = $("#data_inicio_renegociacao").val();
	data_form.data_fim_renegociacao = $("#data_fim_renegociacao").val();

	$.ajax({
		url: "{{ route('cliente.posicao_sintetica.return') }}",
		dataType: 'json',
		data: data_form,
		method: 'POST',
		success: function(callback){
			if(callback.status === "success"){
				$.ajax({
					url: '{{ route('cliente.salvaClientePadrao') }}',
					type: 'POST',
					data: {
						_token: '{{ csrf_token() }}',
						codcad: $("#codigo").val()
					}
				});
				var data = callback.data;
				
				if(data.cnpj_array.length > 0){
					$('#cpf-cnpj-unico-div').removeClass('d-none');
					$('#cpf_cnpj_unico').append("<option value='' >Todos</option>");
					for (var option in data.cnpj_array){
						$('#cpf_cnpj_unico').append("<option value='" + data.cnpj_array[option].codcad + "' " + ((data.cnpj_array[option].selecionado == true) ? "selected" : "") + ">"+ data.cnpj_array[option].label +"</option>");
					}
				}
				else{
					$('#cpf-cnpj-unico-div').addClass('d-none');
				}


				$('.observacao_agrupado').html(data.messagem_agrupada_cnpjs);
				$("#mensagem").html(data.mensagem_alerta);
				$("#vencimento-credito").html(data.vencimento_credito);
				$("#ultima-alteracao").html(data.ultima_atualizacao);
				$("#limite-credito").html(data.limite_credito+'&nbsp '+createLinkLogLimiteCreditos(data));
				$("#cliente-desde").html(data.cliente_desde);
				drawPopOver();
				$("#valor-maior-venda").html(data.vendas.maior.valor);
				$("#data-maior-venda").html(data.vendas.maior.data);
				$("#valor-ultima-venda").html(data.vendas.ultima.valor);
				$("#data-ultima-venda").html(data.vendas.ultima.data);

				$("#dias-maior-atraso").html(data.atraso.maior.quantidade);
				$("#data-maior-atraso").html(data.atraso.maior.data);
				$("#dias-ultimo-atraso").html(data.atraso.ultima.quantidade);
				$("#data-ultimo-atraso").html(data.atraso.ultima.data);

				$("#total-total").html((data.total.total));
				$("#vencido-total").html((data.total.vencidas));
				$("#a_vencer-total").html((data.total.a_vencer));

				if(data.cliente_pre_pago === true || data.pedidos_pre_pagos.total.length > 0){
					$('#a_vencer-pedidos-pre-pagos').html(createLinkPedidosPrePagos(data.pedidos_pre_pagos.total));
					$('#total-pedidos-pre-pagos').html(createLinkPedidosPrePagos(data.pedidos_pre_pagos.total));
					$('#total-pedidos-pre-pagos').parent().removeClass('d-none');
				}
				else{
					$('#total-pedidos-pre-pagos').parent().addClass('d-none');
				}

				$("#total-notas_debito").html(createLinkNotasDebitoTotal(data.notas_debito.total));
				$("#vencido-notas_debito").html(createLinkNotasDebitoVencidos(data.notas_debito.vencidas));
				$("#a_vencer-notas_debito").html(createLinkNotasDebitoAvencer(data.notas_debito.a_vencer));

				$("#total-notas_credito").html(createLinkNotasCreditoTotal(data.notas_credito));

				$("#total-faturado").html(createLinkTitulosFaturadosTotal(data.titulos_faturados.total));
				$("#vencido-faturado").html(createLinkTitulosFaturadosVencidos(data.titulos_faturados.vencidas));
				$("#a_vencer-faturado").html(createLinkTitulosFaturadosAvencer(data.titulos_faturados.a_vencer));

				if(data.titulos_faturados.vencidas == "" && data.titulos_faturados.ha_titulos_vencidas){
					$("#vencido-faturado").html(createLinkTitulosFaturadosVencidos("0,00"));
				}
				if(data.titulos_faturados.a_vencer == "" && data.titulos_faturados.ha_titulos_a_vencer){
					$("#a_vencer-faturado").html(createLinkTitulosFaturadosAvencer("0,00"));
				}
				if(data.titulos_faturados.total == "" && (data.titulos_faturados.ha_titulos_vencidas || data.titulos_faturados.ha_titulos_a_vencer)){
					$("#total-faturado").html(createLinkTitulosFaturadosTotal("0,00"));
				}				

				if(data.titulos_terceiros.total.length > 0 ){

					$("#row-terceiros").removeClass("d-none");

					$("#total-terceiros").html(createLinkTitulosTerceirosTotal(data.titulos_terceiros.total));
					$("#vencido-terceiros").html(createLinkTitulosTerceirosVencidos(data.titulos_terceiros.vencidas));
					$("#a_vencer-terceiros").html(createLinkTitulosTerceirosAvencer(data.titulos_terceiros.a_vencer));
				}
				else{
					$("#row-terceiros").addClass("d-none");
				}

				$("#valor-afaturar").html(data.valores_a_faturar);
				$("#total-vendas-um-ano").html(data.pago_ultimo_12_meses);

				@if(Auth::user()->hasRole('Administradores') || in_array(Auth::id(), [57, 682, 42, 97, 27]) || Auth::user()->hasRole('Juridico') || Auth::user()->hasRole('FINANCEIRO - CTS A RECEBER - BLACKLIST'))
				$('#info_cliente').html(data.baixar_titulos_link+" "+data.renegociacao_titulo_link+" "+data.cobranca_jucicial);
				@endif

				if(data.renegociacao_titulos_quantidade > 0){
					$('#aba_acompanhamento_regenociacao').html("Acomp. Renegociação ("+data.renegociacao_titulos_quantidade+")");
				}else{
					$('#aba_acompanhamento_regenociacao').html("Acomp. Renegociação");
				}

				$('#ultima_consulta_serasa').html(data.consulta_serasa);
				$('#motivo_reavaliacao').html(data.motivo_reavaliacao);
				$('#status_blacklist').html(data.blacklist);

				var link_pedido_orcamento = "";
				var link_pedido_carteira = "";
				var link_pedido_total = "";
				if((data.pedidos.orcamentos).length){
					link_pedido_orcamento = "<a href=\"#\" onclick=\"showPedidos('orcamento', '" +  data.cliente.codigo + "', " + data.cliente.unico + ")\">"+data.pedidos.orcamentos+"</a>";
				}
				if((data.pedidos.carteira).length){
					link_pedido_carteira = "<a href=\"#\" onclick=\"showPedidos('carteira', '" +  data.cliente.codigo + "', " + data.cliente.unico + ")\">"+data.pedidos.carteira+"</a>";
				}
				if((data.pedidos.total).length){
					link_pedido_total = "<a href=\"#\" onclick=\"showPedidos('total', '" +  data.cliente.codigo + "', " + data.cliente.unico + ")\">"+data.pedidos.total+"</a>";
				}

				if(data.devolucoes.length > 0){

					var fields_filter = [];
                    for(var field in data.devolucoes){

						var temp_field = [
							linkProcessoDevolucao(data.devolucoes[field].devolucao_numero, data.devolucoes[field].id),
							createLinkNotaDevolucao(data.devolucoes[field].nota_numero, data.devolucoes[field].nota_id),
							createLinkNotaCliente(data.devolucoes[field].nota_devolucao, data.devolucoes[field].nota_devolucao_arquivo),
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.devolucoes[field].cliente + "'>" + data.devolucoes[field].cliente + "</div></div>",
							data.devolucoes[field].parcial,
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.devolucoes[field].motivo + "'>" + data.devolucoes[field].motivo + "</div></div>",
							data.devolucoes[field].valor,
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.devolucoes[field].status + "'>" + data.devolucoes[field].status + "</div></div>",
							data.devolucoes[field].dias_fase,
							data.devolucoes[field].dias_aberto,
							data.devolucoes[field].origem,
							data.devolucoes[field].entrada,
						];
						
						fields_filter.push(temp_field);
					}
					if (data.grupoCliente == 1){
						table_filters_devolucoes.columns('.cliente').visible(false);
					}else{
						table_filters_devolucoes.columns('.cliente').visible(true);
					}
					table_filters_devolucoes.rows.add(fields_filter).draw().nodes();
				}

				$("#pedidos-orcamentos").html(link_pedido_orcamento);
				$("#pedidos-carteira").html(link_pedido_carteira);
				$("#pedidos-total").html(link_pedido_total);
				$(document).find(".bt-modal-open").off("click");
				$(document).find(".bt-modal-open").on("click",function(){
					showModalOpen($(this));
				});
				buscaHistorico();
			}
		},
		error: function callback(data){
			message('Atenção!', data.responseJSON.message)
		}
	});
}
function createLinkTitulosFaturadosAvencer($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Titulos Faturados - À vencer - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.titulos_faturados', ['coluna'=>'a_vencer']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function createLinkTitulosFaturadosVencidos($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Titulos Faturados - Vencidos - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.titulos_faturados', ['coluna'=>'vencidos']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function createLinkTitulosFaturadosTotal($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Titulos Faturados - Total - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.titulos_faturados', ['coluna'=>'total']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function createLinkTitulosTerceirosAvencer($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").find('option:selected').html();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Titulos Faturados - À vencer - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.titulos_terceiros', ['coluna'=>'a_vencer']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function createLinkTitulosTerceirosVencidos($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").find('option:selected').html();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Titulos Faturados - Vencidos - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.titulos_terceiros', ['coluna'=>'vencidos']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function createLinkTitulosTerceirosTotal($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").find('option:selected').html();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Titulos Faturados - Total - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.titulos_terceiros', ['coluna'=>'total']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function createLinkNotasCreditoTotal($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo").val();			
			var $unico = false;
		}
		
		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Créditos - Total - " + $(document).find('#modal_nome').html() + " \" data-route=\"{{ route('cliente.posicao_sintetica.notas_credito', ['coluna'=>'total']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}

function createLinkNotasDebitoAvencer($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo").val();			
			var $cliente_nome = $(document).find("#nome").val();
			var $unico = false;
		}

		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Notas de Débito - À vencer - " + $(document).find('#modal_nome').html() +" \" data-route=\"{{ route('cliente.posicao_sintetica.notas_debito', ['coluna'=>'a_vencer']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function createLinkNotasDebitoVencidos($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Notas de Débito - Vencidos - " + $(document).find('#modal_nome').html() +" \" data-route=\"{{ route('cliente.posicao_sintetica.notas_debito', ['coluna'=>'vencidos']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function createLinkNotasDebitoTotal($texto){
	if(($texto).trim() != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Notas de Débito - Total - " + $(document).find('#modal_nome').html() +" \" data-route=\"{{ route('cliente.posicao_sintetica.notas_debito', ['coluna'=>'total']) }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}

function createLinkLogLimiteCreditos($this){
	if($this.limite_credito_obj.length > 0){
	var $html = "<a href=\"#\" class=\"bt-view float-right\" data-content=\""+createBodyPopOver($this.limite_credito_obj)+"\" data-toggle=\"popover\" data-trigger='hover' data-title=\"Atualizações no Limite de Crédito\"></a>";
	}else{
		$html = '';
	}
	return $html;
}
function createBodyPopOver($this){
	var $return = "";
	$.each($this,function(index, el) {
		if(index > 0){
			$return += "<hr>";
		}
		$return += "<p> Usuário: "+el.user+"</p><br><p>Data de Atualização: "+el.data+"</p>";
		
	});
	return $return;
}
function createLinkPedidosPrePagos($texto){
	if($texto != ""){
		
		if ($(document).find("#cpf_cnpj_unico").val() != null && $(document).find("#cpf_cnpj_unico").val().length > 0){
			var $cliente_codigo = $(document).find("#cpf_cnpj_unico").val();
			var $unico = true;
		}
		else{
			var $cliente_codigo = $(document).find("#codigo").val();			
			var $unico = false;
		}

		var $cliente_nome = $(document).find("#nome").val();
		var $html = "<a href=\"#\" class=\"bt-modal-open\" data-codigo=\""+$cliente_codigo+"\" data-unico="+$unico+" data-title-modal=\"Títulos Pré Pagos - Total - " + $(document).find('#modal_nome').html() +" \" data-route=\"{{ route('titulos_prepago.modal.filtro') }}\">"+$texto+"</a>";
		return $html;
	}
	return "";
}
function clearTela(){
	contador_forma_pagamento = 0;
	$('.nav-tabs a[href="#posicao_sintetica_header"]').tab('show');
	$(table_filters_forma_pagamento.column(0).footer()).html('');
	$(table_filters_forma_pagamento.column(1).footer()).html('');
	$(table_filters_forma_pagamento.column(2).footer()).html('');
	table_filters_forma_pagamento.clear().draw();
	table_filters_titulos.clear().draw();
	table_historico.clear().draw();
	table_filters_devolucoes.clear().draw();
	table_filters_renegociacao_titulo.clear().draw();
	$(document).find('#cpf_cnpj_unico').find('option').remove();
	$(document).find('#cpf-cnpj-unico-div').addClass('d-none');
	$(document).find('#cpf_cnpj').val('');
	$(document).find(".observacao_agrupado").html("");
	$(document).find("#mensagem").html("");
	$(document).find("#vencimento-credito").html("");
	$(document).find("#ultima-alteracao").html("");
	$(document).find("#limite-credito").html("");
	$(document).find("#cliente-desde").html("");
	$(document).find("#valor-maior-venda").html("");
	$(document).find("#data-maior-venda").html("");;
	$(document).find("#valor-ultima-venda").html("");
	$(document).find("#data-ultima-venda").html("");
	$(document).find("#dias-maior-atraso").html("");
	$(document).find("#data-maior-atraso").html("");
	$(document).find("#dias-ultimo-atraso").html("");
	$(document).find("#data-ultimo-atraso").html("");
	$(document).find("#total-total").html("");
	$(document).find("#vencido-total").html("");
	$(document).find("#a_vencer-total").html("");
	$(document).find("#vencidos-cheches_receber").html("");
	$(document).find("#total-cheches_receber").html("");
	$(document).find('#a_vencer-pedidos-pre-pagos').html("");
	$(document).find('#total-pedidos-pre-pagos').html("");
	$(document).find('#total-pedidos-pre-pagos').parent().addClass('d-none');
	$(document).find("#a_vencer-chques_pre").html("");
	$(document).find("#total-chques_pre").html("");
	$(document).find('#total-chques_pre').parent().addClass('d-none');
	$(document).find("#total-notas_debito").html("");
	$(document).find("#vencido-notas_debito").html("");
	$(document).find("#a_vencer-notas_debito").html("");
	$(document).find("#total-notas_credito").html("");
	$(document).find("#total-faturado").html("");
	$(document).find("#vencido-faturado").html("");
	$(document).find("#a_vencer-faturado").html("");
	$(document).find("#a_vencer-terceiros").html("");
	$(document).find("#vencido-terceiros").html("");
	$(document).find("#total-terceiros").html("");
	$(document).find("#row-terceiros").addClass('d-none');
	$(document).find("#valor-afaturar").html("");
	$(document).find("#total-vendas-um-ano").html("");
	$(document).find("#pedidos-orcamentos").html("");
	$(document).find("#pedidos-carteira").html("");
	$(document).find("#pedidos-total").html("");
	$(document).find("#info_cliente").html("");
	$(document).find('#ultima_consulta_serasa').html("");
	$(document).find('#motivo_reavaliacao').html("");
	$(document).find('#titulos-contador').html("");
	$(document).find('#titulos-valor').html("");
	$(document).find('#titulos-atraso').html("");
	$(document).find('#titulos-media-atraso').html("");
	$(document).find('#titulos-contador-renegociados').html("");
	$(document).find('#titulos-valor-renegociados').html("");
	$("#dados_cliente_codigo").html("");
	$("#dados_cliente_documento").html("");
	$("#dados_cliente_inscricao_estadual").html("");
	$("#dados_cliente_indicador_inscricao_estadual").html("");
	$("#dados_cliente_nome_razao_social").html("");
	$("#dados_cliente_nome_fatasia").html("");
	$("#dados_cliente_telefones").html("");
	$("#dados_cliente_email").html("");
	$("#dados_cliente_contatos").html("");
	$("#dados_cliente_cep").html("");
	$("#dados_cliente_endereco").html("");
	$("#dados_cliente_numero").html("");
	$("#dados_cliente_bairro").html("");
	$("#dados_cliente_cidade").html("");
	$("#dados_cliente_estado").html("");
	$("#dados_clientes_representante").html("");
	$("#dados_clientes_gerentes").html("");
	$("#dados_clientes_desde").html("");
	$('#dados_clientes_contratos').addClass('d-none');
	$('#dados_cliente_contatos').addClass('d-none');
	if(table_documentos != ''){
		table_documentos.clear();
	}
	$('#dados_cliente_documentos').addClass('d-none');
	$(document).find("#dados_devolucao").val('');
}

function modal_info_cliente($id){
	$.ajax({
		url: '{{ route('cliente.view')}}',
		type: 'POST',
		data: {
			_token: '{{ csrf_token() }}',
			codcad: $(document).find('#codigo').val()
		},
	})
	.done(function(data) {
		$id = 'info-cliente-modal';
		$title = 'Informações do cliente';
		$body = data;
		$class = 'modal-lg';
		createModal($id, $title, $body, $class);
	});
}

function modalAdicionarHistorico(){
	var $cliente_codigo = $(document).find("#codigo").val();
	var $cliente_nome = $(document).find("#nome").val();
	$title = 'Adicionar Historico Cliente: '+$cliente_nome;
    $.ajax({
        url: '{{ route('historico_financeiro.modal.adicionar') }}',
        type: 'post',
        data: {
			_token: '{{ csrf_token() }}',
			cliente: $cliente_codigo,
			cliente_nome: $cliente_nome
		},
        success: function(callback){
			createModal("historico_adicionar", $title, callback, 'modal-lg');
        },
        error: function(data) {
        }
    });
}

function buscaHistorico(){
	var $cliente_codigo = $(document).find("#codigo").val();
	table_historico.clear().draw();
    $.ajax({
        url: '{{ route('historico_financeiro.busca') }}',
        type: 'post',
        data: {
			_token: '{{ csrf_token() }}',
			cliente: $cliente_codigo
		},
        success: function(callback){
			if(callback.status === "success"){
				var dados = callback.response;
				if(dados.length > 0){
					var fields_filter = [];
					for(var field in dados){
						var temp_field = [
							"<span style='display: none;'>"+dados[field].string_data_hora+"</span>"+dados[field].data_hora,
							dados[field].usuario,
							dados[field].contato,
							dados[field].retorno,
							dados[field].titulos
						];
						fields_filter.push(temp_field);
					}
					table_historico.rows.add(fields_filter).draw().nodes();
					$(document).find(".bt-historico-titulos").off("click");
					$(document).find(".bt-historico-titulos").on("click",function(){
						showModalTitulos($(this));
					});
				}
			}
        }
    });
}

function showModalTitulos(campo){
	var $cliente_nome = $(document).find("#nome").val();
	$title = 'Titulos Historico Cliente: '+$cliente_nome;
    $.ajax({
        url: '{{ route('historico_financeiro.modal.titulos') }}',
        type: 'post',
        data: {
			_token: '{{ csrf_token() }}',
			id: campo.data('id')
		},
        success: function(callback){
			createModal("historico_Titulos", $title, callback, 'modal-lg');
        }
    });
}

function linkProcessoDevolucao($numero, $id){

	var $html = "<a href=\"#\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"visualizar_devolucao('"+$id+"');\">"+$numero+"</a>";

	return $html;
}

function visualizar_devolucao($id){
	$.ajax({
		method: 'POST',
		data: {
			_token: '{{ csrf_token() }}',
			id: $id
		},
		url: '{{ route('devolucao_nota.modal.visualizar') }}',
		success: function(data){
			createModal('editar-devolucao-modal', 'Detalhes da requisição de devolução', data, 'modal-lg');
		},
		error: function callback(data){
			message('Atenção!', data.responseJSON.message)
		}
	});
}

function createLinkNotaDevolucao($numero, $nota){

	if($nota.length > 0){
		var html = "<a href=\"#\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Detalhes no Nasajon\" onclick=\"showModalNotaDevolucao('"+$nota+"');\">"+$numero+"</a>";
	}
	else{
		var html = $numero;
	}
	return html;
}

function showModalNotaDevolucao($id){
	var url = '{{ route('notas_nasajon.modal.exibir') }}';
	var modal_class = 'modal-lg';
	var title = 'Detalhes da nota';
	$.ajax({
		url: url,
		method: 'POST',
		data: {_token: "{{ csrf_token() }}", id_nota: $id},
		success: function(body){
			createModal('modal_message_edit', title, body, modal_class);
		}
	});
}
	
function createLinkNotaCliente($numero, $link){
	if($link.length > 0){
		var html = "<a href=\""+ $link +"\" target=\"_blank\">"+$numero+"</a>";
	}
	else{
		var html = $numero;
	}
	return html;
}

@if(in_array(Auth::id(), [57, 682, 42, 97, 27]) || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Juridico') || Auth::user()->hasRole('FINANCEIRO - CTS A RECEBER - BLACKLIST'))
function baixarTitulos($id, $title, $unico){
	$.ajax({
		url: '{{ route('baixar_titulo.modal.titulos_para_baixar')}}',
		type: 'POST',
		data: {
			_token: '{{ csrf_token() }}',
			codcad: $(document).find('#codigo').val(),
			unico: $unico,
		},
	})
	.done(function(data) {
		$id = 'baixar_titulo_modal';
		$body = data;
		$class = 'modal-lg';
		createModal($id, $title, $body, $class);
	});
}
@endif

function modalSelecionarTituloParaNegociacao(){
	var $cliente_codigo = $(document).find("#codigo").val();
	var $cliente_nome = $(document).find("#nome").val();
	$title = 'Selecione os Títulos para Renegociação: '+$cliente_nome;
    $.ajax({
        url: '{{ route('renegociacao_titulo.modal.selecionar_titulo') }}',
        type: 'post',
        data: {
			_token: '{{ csrf_token() }}',
			cliente: $cliente_codigo,
			cliente_nome: $cliente_nome
		},
        success: function(callback){
			createModal("selecionar_negociacao_titulo", $title, callback, 'modal-lg');
        },
        error: function(callback) {
            message("Atenção", callback.responseJSON.message);
        }
    });
}

function filterAjax(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
	table_filters_renegociacao_titulo.clear().draw();	
    $.ajax({
        url: '{{ route('renegociacao_titulo.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            
            for (var fields in data.response){
                temp_array = [
                    data.response[fields].data,
                    createBtView(data.response[fields]),
                    data.response[fields].valor_titulos,
					data.response[fields].juros_atualizado,
					data.response[fields].valor_atualizado,
                    data.response[fields].juros_por_mes,
                    data.response[fields].valor_renegociacao,
                    data.response[fields].parcelas,
					data.response[fields].periodo_dias,
					ajusteTamanhoTable(statusRenegociacaoTitulo(data.response[fields])),
					createBtReenvioRenegociacaoTitulo(data.response[fields]),
					createBtEditarProdutoRenegociacaoTitulo(data.response[fields]),
					createBtExcluirRenegociacaoTitulo(data.response[fields]),
                ];
                produtos.push(temp_array)
            }
            table_filters_renegociacao_titulo.rows.add(produtos).draw();            

        }
	});
	
	esconderPopoverTooltip();
}

function createBtView($value){
    html = "<a href=\"#\" class=\"btn-pedido\" data-toggle='tooltip' data-html='true' data-renegociacao_titulos_id=\""+$value.id+"\" data-cliente=\""+$value.cliente+"\" title='Visualizar' onclick=\"showDialogRenegociacaoTitulo($(this))\"></a>";

    return html;
}

function statusRenegociacaoTitulo($value){
	if($value.motivo === ''){
		html = $value.status;
	}else{
		html = "<div>"+
			"<div data-toggle='tooltip' placement='right' data-html='true' title='' data-original-title='"+$value.motivo+"'>"+
				$value.status+
			"</div>"+
		"</div>";
	}

	return html;
}

function showDialogRenegociacaoTitulo($value){
    var title = "Detalhes da Renegociação de Títulos - "+$value.data("cliente");
    var id = $value.data("renegociacao_titulos_id");
    $.ajax({
        url: '{{ route('renegociacao_titulo.modal.renegociacao_titulo') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
			id: id,
			tipo: 'normal',
        },
        success: function (body){
            createModal('dialog_renegociacao',  title, body, 'modal-lg');
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
        }
    }); 
}

function esconderPopoverTooltip(){
	$('[data-toggle="tooltip"]').tooltip('hide');
	$('[data-toggle="popover"]').popover('hide');
}

function createBtEditarProdutoRenegociacaoTitulo($value){
	if($value.motivo === ''){
		html = '';
		if($value.status_id === 8 || $value.status_id === 6){
			html = "<a href=\"#\" class=\"bt-edit\" title='Editar' data-id=\""+$value.id+"\" onclick=\"modalEditarRenegociacaoTitulo($(this))\"></a>";
		}
	}else{
		html = "<a href=\"#\" class=\"bt-edit\" title='Editar' data-id=\""+$value.id+"\" onclick=\"modalEditarRenegociacaoTitulo($(this))\"></a>";
	} 
	
	return html;
}

function createBtExcluirRenegociacaoTitulo($value){
	if($value.motivo === ''){
		html = '';
		if($value.status_id != 3){
			html = "<a href=\"#\" class=\"bt-delete\" title='Excluir' data-id=\""+$value.id+"\" onclick=\"modalDeletarRenegociacaoTitulo($(this))\"></a>";
		}
	}else{
		html = "<a href=\"#\" class=\"bt-delete\" title='Excluir' data-id=\""+$value.id+"\" onclick=\"modalDeletarRenegociacaoTitulo($(this))\"></a>";
	} 
	
	return html;
}

function modalEditarRenegociacaoTitulo($this){
	var $cliente_codigo = $(document).find("#codigo").val();
	var $cliente_nome = $(document).find("#nome").val();
    var title = "Editar Renegociação de Títulos: "+$cliente_nome;
    var id = $this.data("id");
    $.ajax({
        url: '{{ route('renegociacao_titulo.modal.editar') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
			id: id,
			cliente_nome: $cliente_nome,
			cliente_codigo: $cliente_codigo
        },
        success: function (body){
            createModal('editar_renegociacao',  title, body, '');
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
        }
    }); 
}

function modalDeletarRenegociacaoTitulo($this){
	var $cliente_codigo = $(document).find("#codigo").val();
	var $cliente_nome = $(document).find("#nome").val();
    var title = "Deletar Renegociação de Títulos: "+$cliente_nome;
    var id = $this.data("id");
    $.ajax({
        url: '{{ route('renegociacao_titulo.modal.deletar') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
			id: id
        },
        success: function (body){
            createModal('deletar_renegociacao',  title, body, '');
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
        }
    }); 
}

function modalHistoricoBlackList($this){
	var title = "Histórico Black List";
	var cpf_cnpj = $this.data("cpf_cnpj");
    $.ajax({
        url: '{{ route('cliente_black_list.modal.historico') }}',
        type: 'POST',
        data: {
			_token: '{{ csrf_token() }}',
			cpf_cnpj: cpf_cnpj,
        },
        success: function (body){
            createModal('historico_black_list',  title, body, 'modal-lg');
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
        }
    }); 
}

function showModalFormulario($this){
	var url = $($this).data("route");
	var id = $($this).data("id");
	var title = $($this).data('title');

	xhr = $.ajax({
		url: url,
		data: {_token: "{{ csrf_token() }}", id : id},
		method: 'POST',
		success: function(body){
			createModal("modal_formulario_pesquisa_cliente", title, body, 'modal-lg');
		}
	});
}

function pesquisaSatisfacaoFilterAjax(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
	$(document).find('.error-message').remove();
	table_filters_pesquisa_satisfacao.clear().draw();	
    $.ajax({
        url: '{{ route('pesquisa_satisfacao_consulta.filtro.posicao_sintetica')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            
            for (var fields in data.response.retorno){
                temp_array = [
					data.response.retorno[fields].data,
					data.response.retorno[fields].email,
					data.response.retorno[fields].notas,
					createBtViewFormularioPesquisaSatisfacao(data.response.retorno[fields])
                ];
                produtos.push(temp_array)
            }
            table_filters_pesquisa_satisfacao.rows.add(produtos).draw();            

        },
        error: function(callback){
            if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                $.each(data, function(index, el) {
                    $('#'+index).eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>');
                    $('#'+index).eq(0).addClass('error');
                });
                $('input.error').eq(0).focus();
            }
        }
	});
	
	esconderPopoverTooltip();
}

function createBtViewFormularioPesquisaSatisfacao($this,nao_respondidos){
    var html = "<a href=\"#\" data-route=\"{{ route('pesquisa_satisfacao_consulta.modal.abertura_formulario') }}\" data-id=\""+$this.id_formulario+"\" data-title='Formulario Respondido Cliente - \""+$this.cliente+"\"' class='bt-view bt-view-formulario'></a>"
    return html;
}

function ajusteTamanhoTable($value){
    $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";

    return $html;
}

function createBtReenvioRenegociacaoTitulo($value){
	if($value.reenvio){
		html = "<a href=\"#\" class=\"btn-send_email\" title='Reenvio' data-id=\""+$value.id+"\" onclick=\"reenvioEmailRenegociacaoTitulos($(this))\"></a>";
	}else{
		html = "";
	} 
	
	return html;
}

function reenvioEmailRenegociacaoTitulos($this){
	var id_renegocicao = $this.data("id");
	var id_avalista = "";
	var $class_reenvio_email_renegociacao_titulo = "dialog_option_reenvio_email_renegociacao_titulo";
	var $name_option_ok_reenvio_email_renegociacao_titulo = "ok_reenvio_email_renegociacao_titulo";
	var $name_option_cancelar_reenvio_email_renegociacao_titulo = "cancelar_reenvio_email_renegociacao_titulo"; 

	$(document).off("ok_reenvio_email_renegociacao_titulo");
	$(document).on("ok_reenvio_email_renegociacao_titulo", function(){
		esconderPopoverTooltip();
		$.ajax({
			url: '{{ route('renegociacao_titulo.reenvio_email_avaslista') }}',
			type: 'POST',
			async: false,
			data: {
				_token: '{{ csrf_token() }}',
				id_renegocicao: id_renegocicao,
				id_avalista: id_avalista,
			},
			success: function (body){
				hide_loader();
				filterAjax();
				message("Atenção", "E-mails Reenviado com Sucesso!");
			},
			error: function (callback){
				message("Atenção", callback.responseJSON.message);
			}
		});
	});

	$(document).off("cancelar_reenvio_email_renegociacao_titulo");
	$(document).on("cancelar_reenvio_email_renegociacao_titulo", function(){
		return null; 
	});

	message_option("Atenção", "Deseja Reenviar os E-mails para os Avalistas?", $class_reenvio_email_renegociacao_titulo, $name_option_ok_reenvio_email_renegociacao_titulo, '', $name_option_cancelar_reenvio_email_renegociacao_titulo, '');
}

function dadosDevolucao(){
	if($(document).find("#dados_devolucao").val() != ''){
		return false;
	}
	table_filters_devolucoes.clear().draw();

	var data_form = $(document).find("#form_filter").serialize();
	data_form.data_inicio = $("#data_inicio").val();
	data_form.data_fim = $("#data_fim").val();

	data_form.data_inicio_forma_pagamento = $("#data_inicio_forma_pagamento").val();
	data_form.data_fim_forma_pagamento = $("#data_fim_forma_pagamento").val();

	data_form.data_inicio_renegociacao = $("#data_inicio_renegociacao").val();
	data_form.data_fim_renegociacao = $("#data_fim_renegociacao").val();

	$.ajax({
		url: "{{ route('cliente.posicao_sintetica.devolucao_dados') }}",
		dataType: 'json',
		data: data_form,
		method: 'POST',
		success: function(callback){
			if(callback.status === "success"){
				var data = callback.data;
				
				if(data.devolucoes.length > 0){

					var fields_filter = [];
                    for(var field in data.devolucoes){

						var temp_field = [
							linkProcessoDevolucao(data.devolucoes[field].devolucao_numero, data.devolucoes[field].id),
							createLinkNotaDevolucao(data.devolucoes[field].nota_numero, data.devolucoes[field].nota_id),
							createLinkNotaCliente(data.devolucoes[field].nota_devolucao, data.devolucoes[field].nota_devolucao_arquivo),
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.devolucoes[field].cliente + "'>" + data.devolucoes[field].cliente + "</div></div>",
							data.devolucoes[field].parcial,
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.devolucoes[field].motivo + "'>" + data.devolucoes[field].motivo + "</div></div>",
							data.devolucoes[field].valor,
							"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.devolucoes[field].status + "'>" + data.devolucoes[field].status + "</div></div>",
							data.devolucoes[field].dias_fase,
							data.devolucoes[field].dias_aberto,
							data.devolucoes[field].origem,
							data.devolucoes[field].entrada,
						];
						
						fields_filter.push(temp_field);
					}
					if (data.grupoCliente == 1){
						table_filters_devolucoes.columns('.cliente').visible(false);
					}else{
						table_filters_devolucoes.columns('.cliente').visible(true);
					}
					table_filters_devolucoes.rows.add(fields_filter).draw().nodes();
				}

				$(document).find("#dados_devolucao").val('ok');
			}
		}
	});
}
@endsection
