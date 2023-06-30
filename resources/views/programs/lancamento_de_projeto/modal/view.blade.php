@extends('layouts.page-dialog')
@section('content')
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link active" id='projeto-header-tab' data-toggle="tab" href="#projeto_header" role="tab" aria-controls="projeot_header" aria-selected="true">Dados do Projeto</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" id="projeto-itens-tab" data-toggle="tab" href="#projeto_detalhes" role="tab" aria-controls="projeto_detalhes" aria-selected="false">Detalhes de Produção</a>
	</li>
	@if(!empty($ha_arquivos))
	<li class="nav-item">
		<a class="nav-link" id="projeto-arquivos-tab" data-toggle="tab" href="#projeto_arquivos" role="tab" aria-controls="projeto_arquivos" aria-selected="false">Documentos/Imagens</a>
	</li>
	@endif
	<li class="nav-item">
		<a class="nav-link" id="projeto-linha-tempo-tab" data-toggle="tab" href="#projeto_linha_tempo" role="tab" aria-controls="projeto_linha_tempo" aria-selected="false">Linha do Tempo</a>
	</li>
	
	@if($dados['codigo_status'] >= 4)
	<li class="nav-item">
		<a class="nav-link" id="projeto-documentos-tab" data-toggle="tab" href="#projeto_documentos" role="tab" aria-controls="projeto_documentos" aria-selected="false">Documentos</a>
	</li>
	@endif
</ul>
	  
<div class="tab-content pt-3" id="ProjetoHeaderContainer">
	<div class="tab-pane show active" id="projeto_header" role="tabpanel" aria-labelledby="dados-tab">
		<div class="col-lg-12">			<div class="row">
				<div class="col-lg-12">
					<h5><b>Detalhes - Projeto nº</b> {{ $dados['id'] }} - <b>Nome do Projeto:</b> {{ $dados['nome_projeto'] }}@if(!empty($dados['estabelecimento'])) - <b>Estabelecimento:</b> {{ $dados['estabelecimento'] }}@endif - <b>Vendedor:</b> {{ $dados['vendedor'] }}</h5>
					<input type="button" id="bt_imprimir" name="bt_imprimir" onclick="imprimir('{{ $dados['id'] }}')" value="Imprimir Projeto" />
				</div>
			</div>
			<hr>
			<div class='pedido_detalhes_content'>
				<div class="row">
					<div class="col-sm-4">
						<b>Cliente:</b><br>
						{{ $dados['cliente_cnpj'] }} - {{ $dados['cliente_nome'] }}
					</div>
					@if(!empty($dados['pedido']))
					<div class="col-sm-2">
						<b>Pedido do Cliente:</b><br>
						{{ $dados['pedido'] }}
					</div>
					@endif
					<div class="col-sm-2">
						<b>Destino:</b><br>
						{{ $dados['cliente_cidade_uf'] }}
					</div>
					<div class="col-sm-2">
						<b>Status do Projeto:</b><br>
						{{ $dados['status'] }}				
					</div>
					@if(!empty($dados['tipo_producao']))
					<div class="col-sm-2">
						<b>Tipo de Produção:</b><br>
						{{ $dados['tipo_producao'] }}				
					</div>
					@endif
				</div>

				@if(!empty($dados['nome_contato']))
				<div class="row ">
					<div class="col-sm-6">
						<b>Nome Contato:</b><br>
						{{ $dados['nome_contato'] }}
					</div>
					<div class="col-sm-6">
						<b>Email Contato:</b><br>
						{{ $dados['email_contato'] }}
					</div>
				</div>
				@endif

				<div class="row ">
					<div class="col-sm-3">
						<b>Data do projeto:</b><br>
						{{ $dados['data'] }}
					</div>
				</div>
				<div class="row ">
					<div class="col-sm-3">
						<b>Condição de pagamento:</b><br>
						{{ $dados['condicao_pagamento_descr'] }}
					</div>
				</div>
				<div class="row ">
					<div class="col-sm-3">
						<b>Tipo de Frete:</b><br>
						{{ $dados['tipo_frete'] }}
					</div>
				</div>
				<div class="row ">
					<div class="col-sm-2">
						<b>Valor Total do Pedido:</b>
						<div class="text-right">{{ $dados['total_do_pedido'] }}</div>
					</div>
					<div class="col-sm-2">
						<b>Quantidade Total:</b>
						<div class="text-right">{{ $dados['quantidade_total'] }}</div>
					</div>
					<div class="col-sm-2">
						<b>Comissao:</b>
						<div class="text-right">{{ $dados['comissao'] }}</div>
					</div>
					<div class="col-sm-2">
						<b>Desconto:</b>
						<div class="text-right">{{ $dados['desconto'] }}</div>
					</div>
					<div class="col-sm-2">
						<b>Preço Médio de Venda:</b>
						<div class="text-right">{{ $dados['preco_medio_venda'] }}</div>
					</div>
				</div>
				<div class="row ">
					<div class="col-sm-2">
						<b>Valor Total do Preço:</b>
						<div class="text-right">{{ $dados['custo_total'] }}</div>
					</div>
					<div class="col-sm-2">
						<b>Preço do Tecido:</b>
						<div class="text-right">{{ $dados['total_tecido'] }}</div>
					</div>
					<div class="col-sm-2">
						<b>Preço do Insumo:</b>
						<div class="text-right">{{ $dados['total_insumo'] }}</div>
					</div>
					<div class="col-sm-2">
						<b>Preço Mão de Obra:</b>
						<div class="text-right">{{ $dados['total_faccao'] }}</div>
					</div>
					<div class="col-sm-2">
						<b>Preço Unitário MN:</b>
						<div class="text-right">{{ $dados['custo_unitario_mn'] }}</div>
					</div>
					<div class="col-sm-2">
						<b>Frete Adicional:</b>
						<div class="text-right">{{ $dados['frete_adicional'] }}</div>
					</div>
				</div>
			</div>
		</div>
		<div class="content-dialog-table">
			<div class="content-table">
				<table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-projetos-itens">
					<thead>
						<tr>
							<th>Código</th>
							<th>Descrição</th>
							<th>Detalhe de Produção</th>
							<th class="tb_number">Quantidade</th>
							<th class="tb_number">Preço de Venda</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($dados['produtos'] as $produto)
						<tr>
							<td>{{ $produto['codigo'] }}@if(!empty($produto['id_ficha_tecnica']))<a href="#" data-id='{{ $produto['id_ficha_tecnica'] }}' class="btn-prancheta" data-toggle="tooltip" data-placement="top" title="Ficha Técnica" onclick="showModalFichaTecnica($(this))"></a>@endif</td>
							<td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $produto['nome'] }}'>{{ $produto['nome'] }}</div></div></td>
							<td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $produto['detalhes'] }}'>{{ $produto['detalhes'] }}</div></div></td>
							<td>{{ $produto['quantidade'] }}</td>
							<td>{{ $produto['preco_venda'] }}</td>
						</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="projeto_detalhes" role="tabpanel" aria-labelledby="dados-tab">
		<div class="content-dialog-table">
			@if(!empty($dados['tecidos']))
				<label>Tecidos</label>
				<table class="table table-striped table-not-edit" id="table-dialog-tecidos">
					<thead>
						<tr>
							<th>Código</th>
							<th>Descrição</th>
							<th>Ref. Produto</th>
							<th class="tb_number">Consumo por Peça</th>
							<th class="tb_number">Consumo Total</th>
							<th class="tb_number">Preço Unitário</th>
							<th class="tb_number">Preço Total</th>
						</tr>
					</thead>
					<tbody>
						@foreach($dados['tecidos'] as $tecido)
						<tr>
							<td>{{ $tecido['codigo'] }}</td>
							<td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $tecido['descricao'] }}'>{{ $tecido['descricao'] }}</div></div></td>
							<td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $tecido['referencia_produto'] }}'>{{ $tecido['referencia_produto'] }}</div></div></td>
							<td class="tb_number">{{ $tecido['consumo_por_peca'] }}</td>
							<td class="tb_number">{{ $tecido['consumo_total'] }}</td>
							<td class="tb_number">{{ $tecido['custo_unitario'] }}</td>
							<td class="tb_number">{{ $tecido['custo_total'] }}</td>
						</tr>
						@endforeach
					</tbody>
					<tfoot>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td class="tb_number">Total: </td>
							<td class="tb_number">{{ $dados['total_tecido'] }}</td>	
						</tr>
					</tfoot>
				</table>
				<br>
			@endif

			@if(!empty($dados['insumos']))
				<label>Insumos/Acessórios</label>
				<table class="table table-striped table-not-edit table-not-view" id="table-dialog-insumos">
					<thead>
						<tr>
							<th>Código</th>
							<th>Descrição</th>
							<th>Ref. Produto</th>
							<th class="tb_number">Consumo Total</th>
							<th class="tb_number">Preço Unitário</th>
							<th class="tb_number">Preço Total</th>
						</tr>
					</thead>
					<tbody>
						@foreach($dados['insumos'] as $insumo)
						<tr>
							<td>{{ $insumo['codigo'] }}</td>
							<td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $insumo['descricao'] }}'>{{ $insumo['descricao'] }}</div></div></td>
							<td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $insumo['referencia_produto'] }}'>{{ $insumo['referencia_produto'] }}</div></div></td>
							<td class="tb_number">{{ $insumo['consumo_total'] }}</td>
							<td class="tb_number">{{ $insumo['custo_unitario'] }}</td>
							<td class="tb_number">{{ $insumo['custo_total'] }}</td>
						</tr>
						@endforeach
					</tbody>
					<tfoot>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td class="tb_number">Total: </td>
							<td class="tb_number">{{ $dados['total_insumo'] }}</td>	
						</tr>
					</tfoot>
				</table>
				<br>
			@endif

			@if(!empty($dados['faccoes']))
				<label>Serviços</label>
				<table class="table table-striped table-not-edit table-not-view" id="table-dialog-faccoes">
					<thead>
						<tr>
							@if(!empty($dados['com_faccao']) && (Auth::user()->tipo_usuario_id != 12 && Auth::user()->tipo_usuario_id != 16))
								<th>CNPJ</th>
								<th>Facção</th>
							@endif
							<th>Tipo</th>
							<th>Ref. Produto</th>
							<th>Tipo de Serviço</th>
							<th class="tb_number">Preço Unitário</th>
							<th class="tb_number">Quantidade</th>
							<th class="tb_number">Preço Total</th>
						</tr>
					</thead>
					<tbody>
						@foreach($dados['faccoes'] as $faccao)
						<tr>
							
							@if(!empty($dados['com_faccao']) && (Auth::user()->tipo_usuario_id != 12 && Auth::user()->tipo_usuario_id != 16))
								<td>{{ $faccao['cnpj'] }}</td>
								<td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $faccao['faccao'] }}'>{{ $faccao['faccao'] }}</div></div></td>
							@endif
							<td>{{ $faccao['tipo'] }}</td>
							@if(empty($faccao['tecido']))
							<td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $faccao['referencia_produto'] }}'>{{ $faccao['referencia_produto'] }}</div></div></td>
							@else
							<td><div><div  data-toggle="popover" data-placement="top" data-title="Detalhes" data-content="<p><b>Tecido:</b> {{ $faccao['tecido'] }}<p><b>Ref. Produto:</b> {{ $faccao['referencia_produto'] }} <p><b>Produto Acabado:</b> {{ $faccao['produto_acabado'] }}"><a href="#" class="bt-detalhe"></a>{{ $faccao['tecido'] }}</div></div></td>
							@endif
							<td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $faccao['tipo_de_servico'] }}'>{{ $faccao['tipo_de_servico'] }}</div></div></td>
							<td class="tb_number">{{ $faccao['custo_unitario'] }}</td>
							<td class="tb_number">{{ $faccao['quantidade'] }}</td>
							<td class="tb_number">{{ $faccao['custo_total'] }}</td>
						</tr>
						@endforeach
					</tbody>
					<tfoot>
						<tr>
							@if(!empty($dados['com_faccao']) && (Auth::user()->tipo_usuario_id != 12 && Auth::user()->tipo_usuario_id != 16))
							<td></td>
							<td></td>
							@endif
							<td></td>
							@if(empty($faccao['tecido']))
							<td></td>
							@else
							<td></td>
							@endif
							<td></td>
							<td></td>
							<td class="tb_number">Total: </td>
							<td class="tb_number">{{ $dados['total_faccao'] }}</td>	
						</tr>
					</tfoot>
				</table>
				<br>
				<div class="row">
				@if($dados['numero_status'] >= 4)
					@foreach($dados['id_faccoes'] as $id_faccao)
						<div class="col-lg-3">
							<input type="button" id="bt_imprimir"  class="form-control" data-id_projeto="{{ $dados['id_projeto'] }}" data-id_faccao="{{ $id_faccao['id_faccao'] }}" name="bt_imprimir_resumo_faccao" value="Imprimir Resumo {{ $id_faccao['nome'] }}" onclick="imprimirResumoFaccaoModal($(this))" />
						</div>
					@endforeach
				@endif
				</div>
			@endif
		</div>
	</div>
	@if(!empty($ha_arquivos))
	<div class="tab-pane" id="projeto_arquivos" role="tabpanel" aria-labelledby="dados-tab">
		@foreach($dados['produtos'] as $produto)
			@if(!empty($produto['arquivos']))
				<h5>{{ $produto['codigo'] }} - {{ $produto['nome'] }}</h5><br>
				<div class="row">
				@foreach($produto['arquivos'] as $arquivo)
					@if($arquivo['extensao'] === 'jpg' || $arquivo['extensao'] === 'jpeg')
						<div class="box-exibicao-100">
							<div data-toggle='tooltip' data-html='true' data-placement='right' title="{{ $arquivo['nome'] }}">
								<a href={{ $arquivo['caminho'] }} class="foto-produto"> 
									<img src={{ $arquivo['caminho'] }} style="width: 100px; height: 100px">
								</a>
							</div>
							<div style="text-align: center;">
								<div data-toggle='tooltip' data-html='true' data-placement='right' title="{{ $arquivo['nome'] }}">
									<a href={{ $arquivo['caminho'] }} class="foto-produto">
										{{ $arquivo['nome_resumido'] }}
									</a>
								</div>
							</div>
						</div>
					@elseif($arquivo['extensao'] === 'pdf')
						<div class="box-exibicao-100">
							<div data-toggle='tooltip' data-html='true' data-placement='right' title="{{ $arquivo['nome'] }}" style="text-align: center;">
								<a href={{ $arquivo['caminho'] }} class="icon-pdf" target="_blank"></a>
							</div>
							<div data-toggle='tooltip' data-html='true' data-placement='right' title="{{ $arquivo['nome'] }}" style="text-align: center;">
								<a href={{ $arquivo['caminho'] }} target="_blank">
									{{ $arquivo['nome_resumido'] }}
								</a>
							</div>
						</div>
					@else
					<div class="box-exibicao-100">
						<div data-toggle='tooltip' data-html='true' data-placement='right' title="{{ $arquivo['nome'] }}" style="text-align: center;">
							<a href={{ $arquivo['caminho'] }} class="icon-documento" target="_blank"></a>
						</div>
						<div data-toggle='tooltip' data-html='true' data-placement='right' title="{{ $arquivo['nome'] }}" style="text-align: center;">
							<a href={{ $arquivo['caminho'] }} target="_blank">
								{{ $arquivo['nome_resumido'] }}
							</a>
						</div>
					</div>
					@endif
				@endforeach
				</div>
				<hr>
			@endif
		@endforeach
	</div>
	@endif
	<div class="tab-pane" id="projeto_linha_tempo" role="tabpanel" aria-labelledby="dados-tab">
		<div id="my-timeline" class="center_content_absolute"></div>
	</div>

	@if($dados['codigo_status'] >= 4)
	<div class="tab-pane" id="projeto_documentos" role="tabpanel" aria-labelledby="dados-tab">
		<form action="post" name="form_documentos" id="form_documentos" onsubmit="return false;">
			{!! Form::hidden('id_projeto', $dados['id_projeto'], ['id' => 'id_projeto']) !!}
			<div class="content-table">
				<div id="conteudo_documentos"></div>
			</div>
		</form>
	</div>
	@endif

</div>
<script>
	$(document).ready(function(){
		var myEvents = [
			@foreach ($timeline as $historico)
				{
					date:"{{ $historico['natureza'] }}",
					@if(is_array($historico['motivo']))
					content:"<div class=\"timeline_usuario\">{{ $historico['usuario'] }}</div><div class=\"timeline_motivo\">{!! $historico['motivo'][0] !!}<br>{!! $historico['motivo'][1] !!}</div><div class=\"timeline_data\">{{ $historico['data'] }} - {{ $historico['hora'] }}</div>"
					@else
					content:"<div class=\"timeline_usuario\">{{ $historico['usuario'] }}@if(!empty($historico['motivo']))</div><div class=\"timeline_motivo\">{!! $historico['motivo'] !!}@endif</div><div class=\"timeline_data\">{{ $historico['data'] }} - {{ $historico['hora'] }}</div>"
					@endif
				},
			@endforeach
		];


		$('#my-timeline').roadmap(myEvents,{
			eventsPerSlide: 10,
			slide: 1,
			prevArrow:'<div class="bt-seta-esquerda"></div>',
			nextArrow:'<div class="bt-seta-direita"></div>',
			orientation:'auto'
		});

		$('[data-toggle="popover"]').off('show.bs.popover');
		$('[data-toggle="popover"]').popover('hide');
	
		$('[data-toggle="popover"]').popover({
			container: 'body',
			html: true,
			show: true,
			trigger: 'hover',
			placement: 'right',
			template: '<div class="popover popover-estoque" role="popover"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
		});

		table_filters_produtos_options = {
			"searching": false,
			"lengthChange": false,
			"info": false,
			"paging": false,
			"pageLength": 15,
			"processing": true,
			"orderMulti": false,
			"scrollCollapse": false,
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
		table_filters_projeto_itens = $(document).find("#table-filters-projetos-itens").DataTable(table_filters_produtos_options);
		$(".troca-aba").on("click", function(e){
	        e.preventDefault();
	        $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
	    });
		setTimeout(function() {
			table_filters_projeto_itens.draw();
		}, 500);
		$('#table-dialog-tecidos').DataTable({
            "searching": false,
			"lengthChange": false,
			"info": false,
			"paging": false,
			"processing": true,
			"orderMulti": false,
			"scrollCollapse": true,
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
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[3, 'asc'],[ 0, 'asc' ],[ 1, 'asc' ]]
		});
		$('#table-dialog-insumos').DataTable({
            "searching": false,
			"lengthChange": false,
			"info": false,
			"paging": false,
			"processing": true,
			"orderMulti": false,
			"scrollCollapse": true,
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
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[3, 'asc'],[ 0, 'asc' ],[ 1, 'asc' ]]
		});
		$('#table-dialog-faccoes').DataTable({
            "searching": false,
			"lengthChange": false,
			"info": false,
			"paging": false,
			"processing": true,
			"orderMulti": false,
			"scrollCollapse": true,
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
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[3, 'asc'],[ 0, 'asc' ],[ 1, 'asc' ]]
		});
		
		var form_documentos = $(document).find("#form_documentos");

		$(document).find("#projeto-documentos-tab").off("click");
		$(document).find("#projeto-documentos-tab").on("click", function(){
            carregarDocumentosProjeto(form_documentos.find("#id_projeto").val());
		});
		
		$(document).find(".foto-produto").fancybox(
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
	});

	function imprimir($id_projeto){
		event.stopPropagation();
		var $form = document.createElement('form');
		$form.name = 'form_imprimir';
		$form.method = 'POST';
		$form.target = '_blanck';
		$form.action = '{{ route("lancamento_projeto.imprimir") }}';
		
		var $input_form = document.createElement('INPUT');
		$input_form.type = 'TEXT';
		$input_form.name = 'id_projeto';
		$input_form.value = $id_projeto;
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

	function imprimirResumoFaccaoModal($this){
		var id_projeto = $this.data("id_projeto");
		var id_faccao = $this.data("id_faccao");
	
		event.stopPropagation();
		var $form = document.createElement('form');
		$form.name = 'form_imprimir';
		$form.method = 'POST';
		$form.target = '_blanck';
		$form.action = '{{ route("lancamento_projeto.imprimir_resumo_faccao") }}';
		
		var $input_form = document.createElement('INPUT');
		$input_form.type = 'TEXT';
		$input_form.name = 'id_projeto';
		$input_form.value = id_projeto;
		$form.appendChild($input_form);
	
		var $input_form = document.createElement('INPUT');
		$input_form.type = 'TEXT';
		$input_form.name = 'id_faccao';
		$input_form.value = id_faccao;
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

	function carregarDocumentosProjeto(id_projeto){
		$.ajax({
			url: '{{ Route("lancamento_projeto.carregar_documentos") }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				id_projeto: id_projeto
			},
			success: function(data) {
				var html = "";
				$(document).find("#conteudo_documentos").html(html);

				

				html = html + "<div class=\"content-dialog-table\">";
				html = html + "<h6><b>Pedido de Venda</b></h6>";
				html = html + "<table class=\"table table-striped table-not-edit\" id=\"table-dialog-pedidos_venda\">";
				html = html + "<thead>";
				html = html + "<tr>";
				html = html + "<th class=\"tb_number\" style=\"width: 150px;\">Número Pedido</th>";
				html = html + "<th class=\"tb_number\" style=\"width: 150px;\">Pedido Gerado</th>";
				html = html + "<th>cliente</th>";
				html = html + "<th style=\"width: 200px;\">Status</th>";
				html = html + "<th class=\"tb_date\" style=\"width: 150px;\">Data Emissão</th>";
				html = html + "<th class=\"tb_date\" style=\"width: 150px;\">Previsão Entrega</th>";
				html = html + "</tr>";
				html = html + "</thead>";
				html = html + "<tbody>";
				if(data.response.pedidos_venda.length > 0){
					for (var index_pedidos_venda in data.response.pedidos_venda){
						html = html + "<tr>";
						html = html + "<td class=\"tb_number\" style=\"width: 150px;\"><a href='#' class=\"bt-view_pedido\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Pedido\" onclick=\"abrirPedido('"+data.response.pedidos_venda[index_pedidos_venda].pedido+"')\">"+data.response.pedidos_venda[index_pedidos_venda].pedido+"</a></td>";
						html = html + "<td class=\"tb_number\" style=\"width: 150px;\">"
							+ "<a href=\"#\" id=\"bt_link\" "
							+ "data-numero_pedido = \""+data.response.pedidos_venda[index_pedidos_venda].pedido_gerado_id+"\""
							+ "data-origem=\"nasajon\""
							+ "data-estabelecimento=\""+data.response.pedidos_venda[index_pedidos_venda].estabelecimento+"\""
							+ "onclick=\"showItens($(this))\">"
							+ data.response.pedidos_venda[index_pedidos_venda].pedido_gerado
							+ "</a>"
							+ "</td>";
						html = html + "<td>"+data.response.pedidos_venda[index_pedidos_venda].cliente+"</td>";
						html = html + "<td style=\"width: 200px;\">"+data.response.pedidos_venda[index_pedidos_venda].status+"</td>";
						html = html + "<td class=\"tb_date\" style=\"width: 150px;\">"+data.response.pedidos_venda[index_pedidos_venda].data_emissao+"</td>";
						html = html + "<td class=\"tb_date\" style=\"width: 150px;\">"+data.response.pedidos_venda[index_pedidos_venda].data_previsao+"</td>";
						html = html + "</tr>";
					}
				}else{
					html = html + "<tr>";
					html = html + "<td valign=\"top\" colspan=\"6\" class=\"dataTables_empty\">Nenhum registro encontrado</td>";
					html = html + "</tr>";
				}
				html = html + "</tbody>";
				html = html + "</table>"
				html = html + "<hr>";
				html = html + "</div>";

				html = html + "<div class=\"content-dialog-table\">";
				html = html + "<h6><b>Pedido de Compras</b></h6>";
				html = html + "<table class=\"table table-striped table-not-edit\" id=\"table-dialog-pedidos_compras\">";
				html = html + "<thead>";
				html = html + "<tr>";
				html = html + "<th class=\"tb_number\" style=\"width: 150px;\">Número Pedido</th>";
				html = html + "<th>Fornecedor</th>";
				html = html + "<th style=\"width: 200px;\">Situacao</th>";
				html = html + "<th class=\"tb_date\" style=\"width: 150px;\">Data Emissão</th>";
				html = html + "<th class=\"tb_date\" style=\"width: 150px;\">Previsão Entrega</th>";
				html = html + "</tr>";
				html = html + "</thead>";
				html = html + "<tbody>";
				if(data.response.pedidos_compras.pedidos.length > 0){
					for (var index_pedidos_compras in data.response.pedidos_compras.pedidos){
						html = html + "<tr>";
						html = html + "<td class=\"tb_number\" style=\"width: 150px;\"><a href='#' onclick=\"mostrarPedidoComprasDetalhes('"+data.response.pedidos_compras.pedidos[index_pedidos_compras].id+"')\">"+data.response.pedidos_compras.pedidos[index_pedidos_compras].numero_pedido+"</a></td>";
						html = html + "<td>"+data.response.pedidos_compras.pedidos[index_pedidos_compras].fornecedor+"</td>";
						html = html + "<td style=\"width: 200px;\">"+data.response.pedidos_compras.pedidos[index_pedidos_compras].situacao+"</td>";
						html = html + "<td class=\"tb_date\" style=\"width: 150px;\">"+data.response.pedidos_compras.pedidos[index_pedidos_compras].data_compra+"</td>";
						html = html + "<td class=\"tb_date\" style=\"width: 150px;\">"+data.response.pedidos_compras.pedidos[index_pedidos_compras].previsao_entrega+"</td>";
						html = html + "</tr>";
					}
				}else{
					html = html + "<tr>";
					html = html + "<td valign=\"top\" colspan=\"5\" class=\"dataTables_empty\">Nenhum registro encontrado</td>";
					html = html + "</tr>";
				}
				
				html = html + "</tbody>";
				html = html + "</table>"
				html = html + "<hr>";
				html = html + "</div>";

				html = html + "<div class=\"content-dialog-table\">";
				html = html + "<h6><b>Pedidos de Remessa</b></h6>";
				html = html + "<table class=\"table table-striped table-not-edit\" id=\"table-dialog-pedido_remessa\">";
				html = html + "<thead>";
				html = html + "<tr>";
				html = html + "<th class=\"tb_number\" style=\"width: 150px;\">Número do Pedido</th>";
				html = html + "<th class=\"tb_number\" style=\"width: 150px;\">Nota</th>";
				html = html + "<th>Fornecedor</th>";
				html = html + "<th style=\"width: 200px;\">Situação</th>";
				html = html + "<th class=\"tb_date\" style=\"width: 150px;\">Data da Emissão</th>";
				html = html + "</tr>";
				html = html + "</thead>";
				html = html + "<tbody>";
				if(data.response.pedidos_remessas.pedidos.length > 0){
					for(var index_pedido_remessa in data.response.pedidos_remessas.pedidos){
						html = html + "<tr>";
						html = html + "<td class=\"tb_number\" style=\"width: 150px;\">"
							+ "<a href=\"#\" id=\"bt_link\" "
							+ "data-numero_pedido = \""+data.response.pedidos_remessas.pedidos[index_pedido_remessa].id+"\""
							+ "data-origem=\"nasajon\""
							+ "data-estabelecimento=\""+data.response.pedidos_remessas.pedidos[index_pedido_remessa].estabelecimento+"\""
							+ "onclick=\"showItens($(this))\">"
							+ data.response.pedidos_remessas.pedidos[index_pedido_remessa].numero_pedido
							+ "</a>"
							+ "</td>";
						html = html + "<td class=\"tb_number\" style=\"width: 150px;\">"+data.response.pedidos_remessas.pedidos[index_pedido_remessa].nota+"</td>";
						html = html + "<td>"+data.response.pedidos_remessas.pedidos[index_pedido_remessa].fornecedor+"</td>";
						html = html + "<td style=\"width: 200px;\">"+data.response.pedidos_remessas.pedidos[index_pedido_remessa].situacao+"</td>";
						html = html + "<td class=\"tb_date\" style=\"width: 150px;\">"+data.response.pedidos_remessas.pedidos[index_pedido_remessa].data_emissao+"</td>";
						html = html + "</tr>";
					}
				}else{
					html = html + "<tr>";
					html = html + "<td valign=\"top\" colspan=\"5\" class=\"dataTables_empty\">Nenhum registro encontrado</td>";
					html = html + "</tr>";
				}
				html = html + "</tbody>";
				html = html + "</table>";
				html = html + "<hr>";
				html = html + "</div>";

				if(data.response.pedidos_transferencia.verificar === true){
					html = html + "<div class=\"content-dialog-table\">";
					html = html + "<h6><b>Pedidos de Transferência</h6></b>";
					html = html + "<table class=\"table table-striped table-not-edit\" id=\"table-dialog-pedido_transferencia\">";
					html = html + "<thead>";
					html = html + "<tr>";
					html = html + "<th class=\"tb_number\" style=\"width: 150px;\">Número do Pedido</th>";
					html = html + "<th class=\"tb_number\" style=\"width: 150px;\">Nota</th>";
					html = html + "<th>Fornecedor</th>";
					html = html + "<th style=\"width: 200px;\">Situação</th>";
					html = html + "<th class=\"tb_date\" style=\"width: 150px;\">Data da Emissão</th>";
					html = html + "</tr>";
					html = html + "</thead>";
					html = html + "<tbody>";
					for(var index_pedido_transferencia in data.response.pedidos_transferencia.pedidos){
						html = html + "<tr>";
						html = html + "<td class=\"tb_number\" style=\"width: 150px;\">"
							+ "<a href=\"#\" id=\"bt_link\" "
							+ "data-numero_pedido = \""+data.response.pedidos_transferencia.pedidos[index_pedido_transferencia].id+"\""
							+ "data-origem=\"nasajon\""
							+ "data-estabelecimento=\""+data.response.pedidos_transferencia.pedidos[index_pedido_transferencia].estabelecimento+"\""
							+ "onclick=\"showItens($(this))\">"
							+ data.response.pedidos_transferencia.pedidos[index_pedido_transferencia].numero_pedido
							+ "</a>"
							+ "</td>";
						html = html + "<td class=\"tb_number\" style=\"width: 150px;\">"+data.response.pedidos_transferencia.pedidos[index_pedido_transferencia].nota+"</td>";
						html = html + "<td>"+data.response.pedidos_transferencia.pedidos[index_pedido_transferencia].fornecedor+"</td>";
						html = html + "<td style=\"width: 200px;\">"+data.response.pedidos_transferencia.pedidos[index_pedido_transferencia].situacao+"</td>";
						html = html + "<td class=\"tb_date\" style=\"width: 150px;\">"+data.response.pedidos_transferencia.pedidos[index_pedido_transferencia].data_emissao+"</td>";
						html = html + "</tr>";
					}
					html = html + "</tbody>";
					html = html + "</table>";
					html = html + "<hr>";
					html = html + "</div>";
				}

				$(document).find("#conteudo_documentos").html(html);
			}
		});
	}

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
	
	function showItens($value){
		var title = "Dados do pedido";
		var id = $value.data("numero_pedido");
		var origem = $value.data("origem");
		var estabelecimento = $value.data("estabelecimento");
        
        if(origem != 'nasajon'){
            title += ": "+id;
        }

        xhr = $.ajax({
            url: "{{ route('pedidos_orcamentos.show') }}",
            data: {
                _token: "{{ csrf_token() }}",
                origem: origem,
                pedido: id, 
                estabelecimento: estabelecimento
            },
            method: 'POST',
            success: function(body){
                createModal("itens_pedido", title, body, 'modal-lg');
                var modal = $(document).find("#detalhes_projeto").find("#itens_pedido");
                modal.css("z-index", 11);
                $(".modal-backdrop").css("z-index", 9);
            }
        });
	}

	function showModalFichaTecnica($this){
		var id = $this.data('id');
	
		$.ajax({
			data: {
				id: id,
				_token: '{{ csrf_token() }}',
				exibicao_custo_fixo: true,
			},
			url: '{{ route('ficha_tecnica.visualizacao.modal') }}',
			method: 'POST',
			success: function(data){
				var title = 'Ficha técnica do produto: ';
				createModal('modal_ficha_tecnica_exibir', title, data, "modal-lg");
	
				$(document).find('#modal_ficha_tecnica_exibir').on('shown.bs.modal', function(){
					table_filters_composicao.columns.adjust().draw();
					table_filters_servicos.columns.adjust().draw();
				});
			},
			error: function(callback){
			}
		});
	}

	function mostrarPedidoComprasDetalhes(id){
		$.ajax({
			url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
			type: 'POST',
			data: {
				_token: '{{csrf_token()}}',
				id: id,
			},
			success: function(body){
				createModal("modal_compras_detalhes", "Detalhe do Pedido Compras", body, 'modal-lg');
				var modal = $("#modal_compras_detalhes");
			},
			error: function(callback){
				if((callback.responseJSON)){
					var data = callback.responseJSON.error;
					message = '';
					$.each(data, function(index, el) {
						message += el+'<br />';
					});
					message("Atenção", message);
				}
			}
	
		});
	}
</script>
@endsection