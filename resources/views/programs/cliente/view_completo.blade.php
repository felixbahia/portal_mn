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
		<li class="nav-item">
			<a class="nav-link" id="documentos-tab" data-toggle="tab" href="#cliente_novo_documentos" role="tab" aria-controls="cliente_novo_documentos" aria-selected="false">Documentos</a>
		</li>
	</ul>
	<div class="tab-content" id="PedidoOrcamentoTabsContent">
		<div class="tab-pane show active content-view-cliente" id="cliente_novo_dados" role="tabpanel" aria-labelledby="dados-tab">
			<div class="content-tab">
				<div class="row-table">
					<div class="cel-table col-md-6">
                        <div class="title">Já foi cliente:</div>
                        <div class="value">{!! ($dados['ja_foi_cliente'] === 'nao' ? "Não" : "SIM") !!}</div>
					</div>
                    @if($dados['ja_foi_cliente'] !== 'nao')
					<div class="cel-table col-md-6">
                        <div class="title">Quando:</div>
                        <div class="value">{!! $dados['ja_foi_cliente_quando'] !!}</div>
                    </div>
                    @endif
                </div>
				<div class="row-table">
					<div class="cel-table col-md-2">
                        <div class="title">Fisica / Juridica: </div>
                        <div class="value">
                            @if(strtolower($dados['fisica_juridica']) == "j")
                            Juridica
                            @else
                            Fisica
                            @endif
                        </div>
					</div>
					@if($dados['fisica_juridica'] === "f")
					<div class="cel-table col-md-3">
						<div class="title">CPF: </div>
                        <div class="value">{!! $dados['cpf_cnpj'] !!}</div>
					</div>
					<div class="cel-table col-md-3">
						<div class="title">CPF: </div>
                        <div class="value">{!! $dados['indicadorinscricaoestadual'] !!}</div>
					</div>
					@else
					<div class="cel-table col-md-2">
						<div class="title">CNPJ: </div>
                        <div class="value">{!! $dados['cpf_cnpj'] !!}</div>
					</div>
					<div class="cel-table col-md-2">
						<div class="title">Inscrição estadual: </div>
                        <div class="value">{!! $dados['inscricao_estadual'] !!}</div>
					</div>
					<div class="cel-table col-md-2">
						<div class="title">Indicador da Inscrição Estadual: </div>
                        <div class="value">{!! $dados['indicadorinscricaoestadual'] !!}</div>
					</div>
					<div class="cel-table col-md-2">
						<div class="title">Inscrição municipal: </div>
                        <div class="value">{!! $dados['inscricao_municipal'] !!}</div>
					</div>
					<div class="cel-table col-md-2">
						<div class="title">SUFRAMA: </div>
                        <div class="value">{!! $dados['suframa'] !!}</div>
					</div>
					@endif
				</div>
				<div class="row-table">
					<div class="cel-table col-md-6">
						<div class="title">Nome / Razão: </div>
                        <div class="value">{!! $dados['nome_razao'] !!}</div>
					</div>
					<div class="cel-table col-md-6">
						<div class="title">Nome Fantasia / Apelido: </div>
                        <div class="value">{!! $dados['guerra_apelido'] !!}</div>
					</div>
				</div>
				<div class="row-table">
					<div class="cel-table col-md-3">
						<div class="title">Telefone: </div>
                        <div class="value">{!! $dados['telefone'] !!}</div>
					</div>
					<div class="cel-table col-md-3">
						<div class="title">Fax: </div>
                        <div class="value">{!! $dados['telefone_fax'] !!}</div>
					</div>
					<div class="cel-table col-md-6">
						<div class="title">E-mail: </div>
                        <div class="value">{!! $dados['email'] !!}</div>
					</div>
				</div>
				@foreach($dados['contatos'] as $contato)
					<div class="row-table">
						<div class="cel-table col-md-3">
							<div class="title">Contato: </div>
							<div class="value">{!! $contato['nome'] !!}</div>
						</div>
						<div class="cel-table col-md-3">
							<div class="title">Cargo: </div>
							<div class="value">{!! $contato['cargo'] !!}</div>
						</div>
						<div class="cel-table col-md-3">
							<div class="title">Telefone: </div>
							<div class="value">{!! $contato['telefone'] !!}</div>
						</div>
						<div class="cel-table col-md-3">
							<div class="title">E-mail: </div>
							<div class="value">{!! $contato['email'] !!}</div>
						</div>
					</div>
				@endforeach
				<div class="row-table">
					@if(!empty($dados['gerente']))
					<div class="cel-table col-md-3">
						<div class="title">Vendedor: </div>
                        <div class="value">{!! $dados['vendedor']['codigo'] !!} - {!! $dados['vendedor']['nome'] !!}</div>
					</div>
					<div class="cel-table col-md-3">
						<div class="title">Gerente: </div>
						<div class="value">{!! $dados['gerente'] !!}</div>
					</div>
					@else
					<div class="cel-table col-md-6">
						<div class="title">Vendedor: </div>
                        <div class="value">{!! $dados['vendedor']['codigo'] !!} - {!! $dados['vendedor']['nome'] !!}</div>
					</div>
					@endif
					<div class="cel-table col-md-6">
						<div class="title">Transportador: </div>
                        <div class="value">{!! $dados['transportador']['codigo'] !!} - {!! $dados['transportador']['nome'] !!}</div>
					</div>
				</div>
				<div class="row-table">
					<div class="cel-table col-md-3">
						<div class="title">Forte do cliente: </div>
                        <div class="value">
                            @if($dados['forte_cliente'] === 'confecção')
                            Confecção
                            @endif
                            @if($dados['forte_cliente'] === 'atacado')
                            Atacado
                            @endif
                        </div>
					</div>
                    <div class="cel-table col-md-3">
                        <div class="title">Tamanho do cliente: </div>
                        <div class="value">
                            @if($dados['tamanho_cliente'] === 'p')
                            Pequeno
                            @endif
                            @if($dados['tamanho_cliente'] === 'm')
                            Médio
                            @endif
                            @if($dados['tamanho_cliente'] === 'g')
                            Grande
                            @endif
                        </div>
					</div>
					<div class="cel-table col-md-6">
						<div class="title">Ramo de Atividade: </div>
                        <div class="value">
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
				</div>
				<div class="row-table">
					<div class="cel-table col-md-3">
						<div class="title">Número filiais: </div>
                        <div class="value">{!! $dados['numero_filiais'] !!}</div>
					</div>
					<div class="cel-table col-md-3">
						<div class="title">Número de Empregados: </div>
                        <div class="value">{!! $dados['numero_empregados'] !!}</div>
					</div>
					<div class="cel-table col-md-3">
						<div class="title">Prédio próprio</div>
                        <div class="value">
                            @if($dados['predio_proprio'] === 'sim')
                            Sim
                            @else
                            Não 
                            @endif
                        </div>
                    </div>
                    @if($dados['predio_proprio'] !== 'sim')
					<div class="cel-table col-md-3">
                        <div class="title">Aluguel:</div>
                        <div class="value">{!! $dados['aluguel'] !!}</div>
                    </div>
                    @endif
				</div>
				<div class="row-table">
					<div class="cel-table col-md-6">
						<div class="title">Sucessora de: </div>
                        <div class="value">{!! $dados['sucessora_de'] !!}</div>
					</div>
					<div class="cel-table col-md-6">
						<div class="title">Ligações com outras firmas: </div>
                        <div class="value">{!! $dados['ligacao_com'] !!}</div>
					</div>
				</div>
				<div class="row-table">
					<div class="cel-table col-md-6">
						<div class="title">Limite de crédito: </div>
                        <div class="value">{!! $dados['limite_credito'] !!}</div>
					</div>
					<div class="cel-table col-md-6">
						<div class="title">Histórico do cliente na praça: </div>
                        <div class="value">{!! $dados['historico_cliente_praca'] !!}</div>
					</div>
				</div>
                <div class="row-table">
                    <div class="cel-table col-md-6">
                        <div class="title">Mensagem de Alerta: </div>
                        <div class="value">{!! $dados["alerta"] !!}</div>
                    </div>
                    <div class="cel-table col-md-2">
                        <div class="title">Cliente Desde: </div>
                        <div class="value">{!! $dados["data_desde"] !!}</div>
                    </div>
				</div>
				@if(!empty($dados["contrato_caminho"]))
				<div class="row-table">
					<div class="cel-table col-lg-3">
						<div class="title">Contrato</div>
						<div class="value"><a href="{!! $dados["contrato_caminho"] !!}" target="_blanck" class="bt_manual_cliente text-right"><i class='btn-nota-pdf'></i>Contrato Fornecimento</a></div>                
					</div>
					<div class="cel-table col-lg-3">
						<div class="title">E-mail</div>
						<div class="value">{!! $dados["contrato_email"] !!}</div>                
					</div>
					<div class="cel-table col-lg-3">
						<div class="title">Ip</div>
						<div class="value">{!! $dados["contrato_ip"] !!}</div>                
					</div>
					<div class="cel-table col-lg-3">
						<div class="title">Data</div>
						<div class="value">{!! $dados["contrato_criacao"] !!}</div>                
					</div>
				</div>
        		@endif
			</div>
		</div>
		<div class="tab-pane content-view-cliente" id="cliente_novo_enderecos" role="tabpanel" aria-labelledby="enderecos-tab">
			<div class="content-tab">
				<div class="row">
					<div class="col-md-12">
						<h3><center>Dados para Faturamento</center></h3>
					</div>
				</div>
				<div class="row-table">
					<div class="cel-table col-md-2">
						<div class="title">CEP: </div>
                        <div class="value">{!! $dados['faturamento_cep'] !!} </div>
					</div>
					<div class="cel-table col-md-10">
						<div class="title">Endereço: </div>
                        <div class="value">{!! $dados['faturamento_endereco'] !!} </div>
					</div>
				</div>
				<div class="row-table">
					<div class="cel-table col-md-4">
						<div class="title">Bairro: </div>
                        <div class="value">{!! $dados['faturamento_bairro'] !!} </div>
					</div>
					<div class="cel-table col-md-4">
						<div class="title">Cidade: </div>
                        <div class="value">{!! $dados['faturamento_cidade'] !!} </div>
					</div>
					<div class="cel-table col-md-4">
						<div class="title">Estado: </div>
                        <div class="value">{!! $dados['faturamento_estado'] !!} </div>
					</div>
				</div>
				<div class="row-table">
					<div class="cel-table col-md-3">
						<div class="title">Telefone: </div>
                        <div class="value">{!! $dados['faturamento_telefone'] !!} </div>
					</div>
					<div class="cel-table col-md-3">
						<div class="title">Fax: </div>
                        <div class="value">{!! $dados['faturamento_telefone_fax'] !!} </div>
					</div>
					<div class="cel-table col-md-6">
						<div class="title">E-mail: </div>
                        <div class="value">{!! $dados['faturamento_email'] !!} </div>
					</div>
				</div>
				<div class="row-table">
					<div class="col-md-12">
						<hr>
					</div>
				</div>
				<div class="row-table">
					<div class="col-md-12">
						<h3><center>Dados para Cobrança</center></h3>
					</div>
				</div>
				<div class="row-table">
					<div class="cel-table col-md-2">
						<div class="title">CEP: </div>
                        <div class="value">{!! $dados['cobranca_cep'] !!} </div>
					</div>
					<div class="cel-table col-md-10">
						<div class="title">Endereço: </div>
                        <div class="value">{!! $dados['cobranca_endereco'] !!} </div>
					</div>
				</div>
				<div class="row-table">
					<div class="cel-table col-md-4">
						<div class="title">Bairro: </div>
                        <div class="value">{!! $dados['cobranca_bairro'] !!} </div>
					</div>
					<div class="cel-table col-md-4">
						<div class="title">Cidade: </div>
                        <div class="value">{!! $dados['cobranca_cidade'] !!} </div>
					</div>
					<div class="cel-table col-md-4">
						<div class="title">Estado: </div>
                        <div class="value">{!! $dados['cobranca_estado'] !!} </div>
					</div>
				</div>
				<div class="row-table">
					<div class="cel-table col-md-3">
						<div class="title">Telefone: </div>
                        <div class="value">{!! $dados['cobranca_telefone'] !!} </div>
					</div>
					<div class="cel-table col-md-3">
						<div class="title">Fax: </div>
                        <div class="value">{!! $dados['cobranca_telefone_fax'] !!} </div>
					</div>
					<div class="cel-table col-md-2">
						<div class="title">Banco: </div>
                        <div class="value">{!! $dados['cobranca_banco'] !!} </div>
					</div>
					<div class="cel-table col-md-2">
						<div class="title">Agência: </div>
                        <div class="value">{!! $dados['cobranca_agencia'] !!} </div>
					</div>
					<div class="cel-table col-md-2">
						<div class="title">Conta: </div>
                        <div class="value">{!! $dados['cobranca_conta'] !!} </div>
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
		<div class="tab-pane" id="cliente_novo_documentos" role="tabpanel" aria-labelledby="documentos-tab">
			<div class="content-tab">
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
									@foreach($dados["documentos"] as $value)
									<tr>
										<td>{{ $value["descricao"] }}</td>
										@if($value["extensao"] == 'jpg' || $value["extensao"] == 'png' || $value["extensao"] == 'PNG' || $value["extensao"] == 'JPG')
											<td><a href="{{ $value["documento"] }}" data-toggle="popover" data-trigger="hover" aria-readonly="true" title="Documento" data-content='<img src="{{ $value["documento"] }}" width="250" class="rounded mx-auto d-block" alt="Documento">' class="thumb" alt="Documento"><i class="btn-foto-canhoto"></i>Documento</a></td>
										@else
										<td><a href="{{ $value["documento"] }}" title="Documento" data-content="{{ $value["documento"] }}" target="blank"><i class="btn-download"></i>Documento</a></td>
										@endif
									</tr>
									@endforeach
								</tbody>
							</table>
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
			{{ Form::button('Documentos >>', ['class' => 'btn float-right troca-aba btn-info display_none', 'id'=>'avancar_documentos']) }}
			{{ Form::button('<< Sócios e Referências', ['class' => 'btn float-left troca-aba btn-info display_none', 'id' => 'voltar_socios_referencia']) }}
		</div>
	</div>
</form>
<script type="text/javascript">
$(document).ready(function(){
	$('#table-documentos').on( 'error.dt', function ( e, settings, techNote, men ) {
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
	}).draw();
});
	$(document).find('#cadastro_cliente_novo').find("#voltar_basico").hide();
	$(document).find('#cadastro_cliente_novo').find("#voltar_endereco_faturamento_cobranca").hide();
	$(document).find('#cadastro_cliente_novo').find("#avancar_socio_refencia").hide();
	$(document).find('#cadastro_cliente_novo').find("#avancar_documentos").hide();
	$(document).find('#cadastro_cliente_novo').find("#voltar_socios_referencia").hide();

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
			$(document).find('#cadastro_cliente_novo').find("#avancar_documentos").hide();
			$(document).find('#cadastro_cliente_novo').find("#voltar_socios_referencia").hide();
			if($(e.target).attr("id") === 'enderecos-tab'){
				$(document).find('#cadastro_cliente_novo').find("#voltar_basico").show();
				$(document).find('#cadastro_cliente_novo').find("#avancar_socio_refencia").show();
			}
			if($(e.target).attr("id") === 'socios_referencias-tab'){
				$(document).find('#cadastro_cliente_novo').find("#voltar_endereco_faturamento_cobranca").show();
				$(document).find('#cadastro_cliente_novo').find("#avancar_documentos").show();
			}
			if($(e.target).attr("id") === 'dados-tab'){
				$(document).find('#cadastro_cliente_novo').find("#avancar_endereco_faturamento_cobranca").show();
			}
			if($(e.target).attr("id") === 'documentos-tab'){
				$(document).find('#cadastro_cliente_novo').find("#voltar_socios_referencia").show();
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
		$(document).find('#cadastro_cliente_novo').find("#voltar_socios_referencia").off("click");
		$(document).find('#cadastro_cliente_novo').find("#voltar_socios_referencia").on("click", function(){
			$('#socios_referencias-tab').tab('show');
		});
		$(document).find('#cadastro_cliente_novo').find("#avancar_documentos").off("click");
		$(document).find('#cadastro_cliente_novo').find("#avancar_documentos").on("click", function(){
			$('#documentos-tab').tab('show');
		});
	});
</script>
@endsection
