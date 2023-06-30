@extends('layouts.page-dialog')
@section('content')
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link troca-aba active" id='campanha_header_tab' data-toggle="tab" href="#campanha_header" role="tab" aria-controls="campanha_header" aria-selected="true">Campanha</a>
	</li>
	<li class="nav-item">
		<a class="nav-link troca-aba" id="apuracao_vendedor_tab" data-toggle="tab" href="#apuracao_vendedor" role="tab" aria-controls="pedido_web_itens" aria-selected="false">Meta Vendedores/Representante</a>
	</li>
	<li class="nav-item">
		<a class="nav-link troca-aba" id="apuracao_gerente_tab" data-toggle="tab" href="#apuracao_gerente" role="tab" aria-controls="pedido_web_credito" aria-selected="false">Meta Gerentes</a>
	</li>
	<li class="nav-item">
		<a class="nav-link troca-aba" id="inserir_produtos_tab" data-toggle="tab" href="#inserir_produtos" role="tab" aria-controls="pedido_web_credito" aria-selected="false">Produtos</a>
	</li>
</ul>
<div class="tab-content pt-3" id="PedidoHeaderContainer">
	<div class="tab-pane show active" id="campanha_header" role="tabpanel" aria-labelledby="campanha_header_tab">
		<form action="" method="post" id="campanha_form" name="campanha_form" class="campanha_form" onsubmit="return false">
		    @csrf
			{!! Form::hidden('campanha_id', $retorno['id'], ["id" => 'campanha_id']) !!}
			<div class="form-row">

				<div class="form-group col-sm-6">
					{{ Form::label('nome_campanha', 'Nome da Capanha') }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
					{{ Form::text('nome_campanha', $retorno['nome'], ['id' => 'nome_campanha', 'class' => 'form-control essencial input-label', 'placeholder' => '','max-']) }}
                </div>
				<div class="form-group col-sm-3">
					{{ Form::label('data_inicio_campanha', 'Início', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
					{!! Form::text('data_inicio_campanha', $retorno['inicio_campanha'], ['id' => 'data_inicio_campanha', 'class' => 'form-control data_modal', 'maxlength' => '20', 'readonly']) !!}
				</div>
				<div class="form-group col-sm-3">
					{{ Form::label('data_fim_campanha', 'Fim', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
					{{ Form::text('data_fim_campanha', $retorno['fim_campanha'], ['id' => 'data_fim_campanha', 'class' => 'form-control data_modal', 'maxlength' => '20', 'readonly']) }}
				</div>
			</div>
			{{ Form::label('nome_estabelecimento', 'Estabelecimento') }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
			<div class="form-row">
							<div class="col-sm-2  ml-4">
								{{ Form::checkbox('todos', '',false, ['id' => 'todos', 'class' => 'form-check-input seleciona_estab_todos']) }}
								{{ Form::label('todos', 'TODOS', ['class' => 'form-check-label']) }}
							</div>
					@foreach ($estabelecimentos as $key => $estab)
						<div class="col-sm-2 ml-4">
							{{ Form::checkbox('estabelecimento_modal',  $key,false, ['id' => $key, 'class' => 'form-check-input seleciona_estab']) }}
							{{ Form::label($estab, $estab, ['class' => 'form-check-label']) }}
						</div>
					@endforeach

				
				</div>
			<div class="form-row">
				<div class="col-sm-2 col-lg-3">
					{{ Form::label('', 'Tipo de Comissão(%):', []) }}
					{{ Form::select("tipo_comissao_representante", $comissoes_tipo, $retorno['tipo_comissao_representante'], ["id" => "estabelecimento_filtro", "class" => "form-control",'disabled','readonly']) }}
				</div>
				<div class="form-group col-sm-6 col-lg-3 content-not-estabel">
					{{ Form::label('comissao_representante', 'Incentivo Comissão(%) Representante:', []) }}
					{{ Form::text('comissao_representante', $retorno['comissao_representante'], array('id' => 'comissao_representante', 'class' => 'form-control text-right', "maxlength" => "180",'readonly')) }}
				</div>
				<div class="col-sm-2 col-lg-3">
					{{ Form::label('', 'Tipo de Comissão(%):', []) }}
					{{ Form::select("tipo_comissao_vendedor_interno", $comissoes_tipo, $retorno['tipo_comissao_vendedor_interno'], ["id" => "estabelecimento_filtro", "class" => "form-control",'disabled','readonly']) }}
				</div>
				<div class="form-group col-sm-6 col-lg-3 content-not-estabel">
					{{ Form::label('comissao_vendedor_interno', 'Incentivo Comissão(%) Vendedor Interno:', []) }}
					{{ Form::text('comissao_vendedor_interno', $retorno['comissao_vendedor_interno'], array('id' => 'comissao_vendedor_interno', 'class' => 'form-control text-right', "maxlength" => "180",'readonly')) }}
				</div>
			</div>
			<div class="form-row">
				<div class="col-sm-2 col-lg-3">
					{{ Form::label('', 'Tipo de Comissão(%):', []) }}
					{{ Form::select("tipo_comissao_gerente", $comissoes_tipo, $retorno['tipo_comissao_gerente'], ["id" => "estabelecimento_filtro", "class" => "form-control",'disabled','readonly']) }}
				</div>
				<div class="form-group col-sm-6 col-lg-3 content-not-estabel">
					{{ Form::label('comissao_gerente', 'Incentivo Comissão(%) Gerente:', []) }}
					{{ Form::text('comissao_gerente', $retorno['comissao_gerente'], array('id' => 'comissao_gerente', 'class' => 'form-control text-right', "maxlength" => "180",'readonly')) }}
				</div>
			</div>
		    <div class="row">
				<div class="col-sm-12">
					<button type="button" id="bt_ir_para_apaurcao_vendedores" class="btn btn-info troca-aba float-right">Ir para Meta de resultado vendedores >></button>
				</div>
			</div>
	</div>
	<div class="tab-pane" id="apuracao_vendedor" role="tabpanel" aria-labelledby="apuracao_vendedor_tab">
		@if($tipo_periodo_vendedor > 0)
		{!! Form::hidden('contador_vendedor', $contador_vendedor = 0, ["id" => 'contador_vendedor']) !!}
			@foreach ($retorno['apuracao'] as $apuracao_dados_vendedores)
				@if($apuracao_dados_vendedores['tipo'] == 1)
					<div class="border border-dark rounded p-1 mb-1">
						<div class="row">
							<div class="form-group col-sm-2">
								{{ Form::label('data_inicio_apuracao_vendedor', 'Início do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
								{!! Form::text('data_inicio_apuracao_vendedor['.$contador_vendedor.']', $apuracao_dados_vendedores['inicio_periodo'], ['id' => 'data_inicio_apuracao_vendedor['.$contador_vendedor.']', 'class' => 'form-control  data_inicio_apuracao_vendedor'.$contador_vendedor, 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
							</div>
							<div class="form-group col-sm-2">
								{{ Form::label('data_fim_apuracao_vendedor', 'Fim do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
								{!! Form::text('data_fim_apuracao_vendedor['.$contador_vendedor.']', $apuracao_dados_vendedores['fim_periodo'], ['id' => 'data_fim_apuracao_vendedor['.$contador_vendedor.']', 'class' => 'form-control  data_fim_apuracao_vendedor'.$contador_vendedor, 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
							</div>
							<div class="form-group col-sm-2">
								{{ Form::label('meta_reais_representante', 'Meta em Reais:', []) }}
								{!! Form::text('meta_reais_representante['.$contador_vendedor.']', (!empty($apuracao_dados_vendedores['meta_reais'])) ? $apuracao_dados_vendedores['meta_reais'] : null, array('id' => 'meta_reais_representante['.$contador_vendedor.']', 'class' => 'verifica_reais_representante form-control meta_reais_representante'.$contador_vendedor, "maxlength" => "180", 'form' => 'campanha_form', 'readonly')) !!}
							</div>
							<div class="form-group col-sm-2">
								{{ Form::label('meta_metros_representante', 'Meta em Metros:', []) }}
								{!! Form::text('meta_metros_representante['.$contador_vendedor.']', (!empty($apuracao_dados_vendedores['meta_metros'])) ? $apuracao_dados_vendedores['meta_metros'] : null, array('id' => 'meta_metros_representante['.$contador_vendedor.']', 'class' => 'verifica_metros_representante form-control meta_metros_representante'.$contador_vendedor, "maxlength" => "180", 'form' => 'campanha_form', 'readonly')) !!}
							</div>
							<div class="form-group col-sm-3">
								{{ Form::label('usuario', 'Usuário', []) }}
								{!! Form::text('usuario['.$contador_vendedor.']', $apuracao_dados_vendedores['usuario'], ['id' => 'usuario['.$contador_vendedor.']', 'class' => 'form-control ', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
							</div>
						</div>
					</div>
					{!! Form::hidden('contador_vendedor_fim', $contador_vendedor =  $contador_vendedor + 1, ["id" => 'contador_vendedor_fim']) !!}
				@endif
			@endforeach
		@else
			<div class="border border-dark rounded p-1 mb-1">
				<div class="row">
					<div class="form-group col-sm-2">
						{{ Form::label('data_inicio_apuracao_vendedor', 'Início do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
						{!! Form::text('data_inicio_apuracao_vendedor[0]', '', ['id' => 'data_inicio_apuracao_vendedor[0]', 'class' => 'form-control data_inicio_apuracao_vendedor0', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form','readonly']) !!}
					</div>
					<div class="form-group col-sm-2">
						{{ Form::label('data_fim_apuracao_vendedor', 'Fim do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
						{!! Form::text('data_fim_apuracao_vendedor[0]', '', ['id' => 'data_fim_apuracao_vendedor[0]', 'class' => 'form-control data_fim_apuracao_vendedor0', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form','readonly']) !!}
					</div>
					<div class="form-group col-sm-2">
						{{ Form::label('meta_reais_representante', 'Meta em Reais:', []) }}
						{!! Form::text('meta_reais_representante[0]', '', array('id' => 'meta_reais_representante[0]', 'class' => 'form-control meta_reais_representante0', "maxlength" => "180", 'form' => 'campanha_form','readonly')) !!}
					</div>
					<div class="form-group col-sm-2">
						{{ Form::label('meta_metros_representante', 'Meta em Metros:', []) }}
						{!! Form::text('meta_metros_representante[0]', '', array('id' => 'meta_metros_representante[0]', 'class' => 'form-control meta_metros_representante0', "maxlength" => "180", 'form' => 'campanha_form','readonly')) !!}
					</div>
				</div>
			</div>
		@endif
		<br>
		<div class="row">
			<div class="col-sm-12">
				<button type="button" id="bt_ir_para_campanha" class="btn troca-aba btn-info"><< Voltar para Campanha</button>
				<button type="button" id="bt_ir_para_apaurcao_gerentes" class="btn btn-info troca-aba float-right">Ir para Meta de resultado gerentes >></button>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="apuracao_gerente" role="tabpanel" aria-labelledby="apuracao_gerente_tab">
		@if($tipo_periodo_gerente > 0)
		{!! Form::hidden('contador_gerente', $contador_gerente = 0, ["id" => 'contador_gerente']) !!}
			@foreach ($retorno['apuracao'] as $apuracao_dados_gerentes)
				@if($apuracao_dados_gerentes['tipo'] == 2)
					<div class="border border-dark rounded p-1 mb-1">
						<div class="row">
							<div class="form-group col-sm-2">
								{{ Form::label('data_inicio_apuracao_gerente', 'Início do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
								{!! Form::text('data_inicio_apuracao_gerente['.$contador_gerente.']', $apuracao_dados_gerentes['inicio_periodo'], ['id' => 'data_inicio_apuracao_gerente['.$contador_gerente.']', 'class' => 'form-control  data_inicio_apuracao_gerente'.$contador_gerente, 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
							</div>
							<div class="form-group col-sm-2">
								{{ Form::label('data_fim_apuracao_gerentes', 'Fim do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
								{!! Form::text('data_fim_apuracao_gerentes['.$contador_gerente.']', $apuracao_dados_gerentes['fim_periodo'], ['id' => 'data_fim_apuracao_gerentes['.$contador_gerente.']', 'class' => 'form-control  data_fim_apuracao_gerente'.$contador_gerente, 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
							</div>
							<div class="form-group col-sm-2 content-not-estabel">
								{{ Form::label('meta_reais_gerente', 'Meta em Reais:', []) }}
								{!! Form::text('meta_reais_gerente['.$contador_gerente.']', (!empty($apuracao_dados_gerentes['meta_reais'])) ? $apuracao_dados_gerentes['meta_reais'] : '', array('id' => 'meta_reais_gerente['.$contador_gerente.']', 'class' => 'verifica_reais_gerente form-control meta_reais_gerente'.$contador_gerente, "maxlength" => "180", 'form' => 'campanha_form', 'readonly')) !!}
							</div>
							<div class="form-group col-sm-2 content-not-estabel">
								{{ Form::label('meta_metros_gerente', 'Meta em Metros:', []) }}
								{!! Form::text('meta_metros_gerente['.$contador_gerente.']', (!empty($apuracao_dados_gerentes['meta_metros'])) ? $apuracao_dados_gerentes['meta_metros'] : '', array('id' => 'meta_metros_gerente['.$contador_gerente.']', 'class' => 'verifica_metros_gerente form-control meta_metros_gerente'.$contador_gerente, "maxlength" => "180", 'form' => 'campanha_form', 'readonly')) !!}
							</div>
							<div class="form-group col-sm-3">
								{{ Form::label('usuario', 'Usuário', []) }}
								{!! Form::text('usuario['.$contador_gerente.']', $apuracao_dados_gerentes['usuario'], ['id' => 'usuario['.$contador_gerente.']', 'class' => 'form-control ', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
							</div>
						</div>
					</div>
					{!! Form::hidden('contador_gerente_resultado', $contador_gerente = $contador_gerente + 1, ["id" => 'contador_gerente_resultado']) !!}
				@endif
			@endforeach
		@else
			<div class="border border-dark rounded p-1 mb-1">
				<div class="row">
					<div class="form-group col-sm-3">
						{{ Form::label('data_inicio_apuracao_gerente', 'Início do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
						{!! Form::text('data_inicio_apuracao_gerente[0]', '', ['id' => 'data_inicio_apuracao_gerente[0]', 'class' => 'form-control data_inicio_apuracao_gerente0', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form','readonly']) !!}
					</div>
					<div class="form-group col-sm-3">
						{{ Form::label('data_fim_apuracao_gerentes', 'Fim do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
						{!! Form::text('data_fim_apuracao_gerentes[0]', '', ['id' => 'data_fim_apuracao_gerentes[0]', 'class' => 'form-control data_fim_apuracao_gerente0', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form','readonly']) !!}
					</div>
					<div class="form-group col-sm-3 content-not-estabel">
						{{ Form::label('meta_reais_gerente', 'Meta em Reais:', []) }}
						{!! Form::text('meta_reais_gerente[0]', '', array('id' => 'meta_reais_gerente[0]', 'class' => 'form-control meta_reais_gerente0', "maxlength" => "180", 'form' => 'campanha_form','readonly')) !!}
					</div>
					<div class="form-group col-sm-3 content-not-estabel">
						{{ Form::label('meta_metros_gerente', 'Meta em Metros:', []) }}
						{!! Form::text('meta_metros_gerente[0]', '', array('id' => 'meta_metros_gerente[0]', 'class' => 'form-control meta_metros_gerente0', "maxlength" => "180", 'form' => 'campanha_form','readonly')) !!}
					</div>
				</div>
			</div>
		@endif
		<div class="adicionar-elemento-apuracao-gerente">
		</div>
		<br>
		<div class="row">
			<div class="col-sm-12">
				<button type="button" id="bt_ir_para_apaurcao_vendedores" class="btn troca-aba btn-info"><< Voltar para Meta Vendedores</button>
				<button type="button" id="bt_ir_para_produtos" class="btn btn-info troca-aba float-right">Ir para produtos >></button>
			</div>
		</div>
	</div>
	</form>
	<div class="tab-pane" id="inserir_produtos" role="tabpanel" aria-labelledby="inserir_produtos_tab">
		<div class="content-dialog-table">
			<div class="content-table mt-4">
				<table class="table table-striped" id="table-filters-pedidos-itens">
			        <thead>
			            <tr>
			                <th>Código</th>
			                <th>Descrição</th>
			                <th>Grupo</th>
			                <th>SubGrupo</th>
			                <th>Marca</th>
			                <th>Linha</th>
			                <th>Segmento</th>
							<th>Remover</th>
			            </tr>
			        </thead>
			        <tbody>
			        </tbody>
			    </table>
			</div>
			<div class="col-sm-12 mt-5" id="button-bottom">
				<button type="button" id="bt_ir_para_apaurcao_gerentes" class="btn troca-aba btn-info"><< Voltar para Meta Gerentes</button>
			</div>
        </div>
		<div class="content-dialog-table d-none">
			<div class="content-table mt-4">
				<table class="table table-striped" id="table-filters-pedidos-itens-hidden">
			        <thead>
			            <tr>
			                <th>Código</th>
			                <th>Descrição</th>
			                <th>Grupo</th>
			                <th>SubGrupo</th>
			                <th>Marca</th>
			                <th>Linha</th>
			                <th>Segmento</th>
							<th>Remover</th>
			            </tr>
			        </thead>
			        <tbody>
			        </tbody>
			    </table>
			</div>
        </div>
	</div>
</div>
<script type="text/javascript">
	var estabelecimentos_selecionados = [];

	$(document).ready(function(){
		$(document).find(".troca-aba").off("click");
		$(document).find(".troca-aba").on("click", function(e){
			e.preventDefault();
			if($(this).attr("id") === "bt_ir_para_apaurcao_vendedores" || $(this).attr("id") === "apuracao_vendedor_tab"){
				$(document).find('#campanha_header').hide();
				$(document).find('#apuracao_gerente').hide();
				$(document).find('#inserir_produtos').hide();
				$(document).find('#apuracao_vendedor').show();
				$(document).find('#campanha_header_tab').removeClass('active');
				$(document).find('#inserir_produtos_tab').removeClass('active');
				$(document).find('#apuracao_gerente_tab').removeClass('active');
				$(document).find('#apuracao_vendedor_tab').addClass('active');
			}
			else if($(this).attr("id") === "bt_ir_para_campanha" || $(this).attr("id") === "campanha_header_tab"){
				$(document).find('#campanha_header').show();
				$(document).find('#apuracao_gerente').hide();
				$(document).find('#inserir_produtos').hide();
				$(document).find('#apuracao_vendedor').hide();
				$(document).find('#campanha_header_tab').addClass('active');
				$(document).find('#inserir_produtos_tab').removeClass('active');
				$(document).find('#apuracao_gerente_tab').removeClass('active');
				$(document).find('#apuracao_vendedor_tab').removeClass('active');
			}
			else if($(this).attr("id") === "bt_ir_para_apaurcao_gerentes" || $(this).attr("id") === "apuracao_gerente_tab"){
				$(document).find('#campanha_header').hide();
				$(document).find('#apuracao_gerente').show();
				$(document).find('#inserir_produtos').hide();
				$(document).find('#apuracao_vendedor').hide();
				$(document).find('#campanha_header_tab').removeClass('active');
				$(document).find('#inserir_produtos_tab').removeClass('active');
				$(document).find('#apuracao_gerente_tab').addClass('active');
				$(document).find('#apuracao_vendedor_tab').removeClass('active');
			}
			else if($(this).attr("id") === "bt_ir_para_produtos" || $(this).attr("id") === "inserir_produtos_tab"){
				$(document).find('#campanha_header').hide();
				$(document).find('#apuracao_gerente').hide();
				$(document).find('#inserir_produtos').show();
				$(document).find('#apuracao_vendedor').hide();
				$(document).find('#campanha_header_tab').removeClass('active');
				$(document).find('#inserir_produtos_tab').addClass('active');
				$(document).find('#apuracao_gerente_tab').removeClass('active');
				$(document).find('#apuracao_vendedor_tab').removeClass('active');
			}
		});

		@foreach ($retorno['estabelecimento'] as $estabelecimento)

			$(document).find('#{{$estabelecimento['estabelecimento_codigo']}}').prop("checked", true);
			estabelecimentos_selecionados.push('{{$estabelecimento['estabelecimento_codigo']}}');
		@endforeach

        
		
		@if($tipo_periodo_vendedor > 0)
        	var x = {{ $tipo_periodo_vendedor }};
		@else
			var x = 1;
		@endif

		var max_fields_vendedor = 30;

		@if($tipo_periodo_vendedor > 0)
			var contador_anterior = {{ $tipo_periodo_vendedor - 1}};
		@else
			var contador_anterior = 0;
		@endif

		$(document).find('#adicionar_apuracao_vendedor').click(function(e){
			e.preventDefault(); 
			$data_inicio_campanha = $(document).find("#data_inicio_campanha").val();
			$data_fim_campanha = $(document).find("#data_fim_campanha").val();
			
			if($data_inicio_campanha == '' || $data_fim_campanha == ''){
				message("Atenção", "A campanha está com período vazio.");
				return false;
			}

			$verifica_inicio = $(document).find("#data_inicio_campanha").datepicker("getDate");

			$verifica_fim = $(document).find("#data_fim_campanha").datepicker("getDate");

			if(($verifica_fim) < ($verifica_inicio)){
				message("Atenção", "A data de fim da campanha é menor que o início.");
				return false;
			}

			if (x < max_fields_vendedor){
                var anterior = 0;
				var conteudo = 
                '<div class="border border-dark rounded p-1 mb-1 remove_periodo_vendedor'+x+'" id="adicionar-apuracao-vendedor-div-'+x+'">'+
                    '<div class="row">'+
                        '<div class="col-sm-3">'+
                            '{!! Form::label("data_inicio_apuracao_vendedor['+x+']", "Início do Período", ["class"=>"input-label"]) !!} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio">*</span>'+
                            '<input type="text" id="data_inicio_apuracao_vendedor['+x+']" name="data_inicio_apuracao_vendedor['+x+']" readonly class="form-control data_inicio_apuracao_vendedor'+x+'" form="campanha_form"  length="20">'+
                        '</div>'+
                        '<div class="col-sm-3">'+
                            '{!! Form::label("data_fim_apuracao_vendedor['+x+']", "Fim do Período", ["class"=>"input-label"]) !!} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio">*</span>'+
                            '<input type="text" id="data_fim_apuracao_vendedor['+x+']" name="data_fim_apuracao_vendedor['+x+']" readonly class="form-control data_fim_apuracao_vendedor'+x+'" form="campanha_form" length="20">'+
                        '</div>'+
                        '<div class="col-sm-3">'+
                            '{!! Form::label("meta_reais_representante['+x+']", "Meta em Reais:", ["class"=>"input-label"]) !!} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" form="campanha_form" class="campo_obrigatorio">*</span>'+
                            '<input type="text" id="meta_reais_representante['+x+']" name="meta_reais_representante['+x+']" class="form-control meta_reais_representante'+x+'"  length="20" form="campanha_form">'+
                        '</div>'+
                        '<div class="col-sm-3">'+
                            '{!! Form::label("meta_metros_representante['+x+']", "Meta em Metros:", ["class"=>"input-label"]) !!} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" form="campanha_form" class="campo_obrigatorio">*</span>'+
                            '<input type="text" id="meta_metros_representante['+x+']" name="meta_metros_representante['+x+']" class="form-control meta_metros_representante'+x+'"  length="20" form="campanha_form">'+
                        '</div>'+
                    '</div>'+
					'<div class="col-sm-3 mt-2">'+
						'<input type="button" id="remove_periodo_vendedor'+x+'" class="btn btn-danger remove_documento_vendedor" value="Remover">'+
					'</div>'+
                '</div>';

				if(x > 0){
					if(x > 1){
                    	anterior = x - 1;
					}else{
						anterior = 1;
					}

                    for(var i = 0; i <= x; i++){
                        var data_inicio_apuracao_vendedor = $(document).find(".data_inicio_apuracao_vendedor"+i).val();
                        var data_fim_apuracao_vendedor = $(document).find(".data_fim_apuracao_vendedor"+i).val();
                        var meta_reais_representante = $(document).find(".meta_reais_representante"+i).val();
                        var meta_metros_representante = $(document).find(".meta_metros_representante"+i).val();
                        
                        if( data_inicio_apuracao_vendedor == '' || 
							data_fim_apuracao_vendedor == ''  
						 ){
                            message("Atenção", "Preencha os campos de data para adicionar mais períodos.");
                            return false;
                        }

						if(meta_reais_representante && meta_metros_representante){
							message("Atenção", "Não pode haver metas em Reais e Metros ao mesmo tempo.");
                            return false;
						}

                    }

					$verifica_data_anterior = contador_anterior;
					
					$verificar_ultimo_inicio_periodo = $(document).find(".data_inicio_apuracao_vendedor"+$verifica_data_anterior).datepicker("getDate");
					
					$verificar_ultimo_fim_periodo = $(document).find(".data_fim_apuracao_vendedor"+$verifica_data_anterior).datepicker("getDate");
					
					if($verificar_ultimo_fim_periodo < $verificar_ultimo_inicio_periodo){
						message("Atenção", "O Último período inserido tem data final menor que inicial.");
						return false;
					}

					if($verificar_ultimo_inicio_periodo < $verifica_inicio || $verificar_ultimo_inicio_periodo > $verifica_fim){
						message("Atenção", "Data Inicial fora do período de campanha.");
						return false;
					}

					if($verificar_ultimo_fim_periodo < $verifica_inicio || $verificar_ultimo_fim_periodo > $verifica_fim){
						message("Atenção", "Data Final fora do período de campanha.");
						return false;
					}
					if($verifica_data_anterior > 0){
						$verifica_entepenultimo = $verifica_data_anterior - 1;
						
						$verificar_antepenultimo_fim_periodo = $(document).find(".data_fim_apuracao_vendedor"+$verifica_entepenultimo).datepicker("getDate");

						if($verificar_ultimo_inicio_periodo <= $verificar_antepenultimo_fim_periodo){
							message("Atenção", "O Último período inserido tem data final maior ou igual que a data inicial do período atual.");
							return false;
						}

						$verificar_antepenultimo_meta_reais_periodo = $(document).find(".meta_reais_representante"+$verifica_entepenultimo).val();
						$verificar_antepenultimo_meta_metros_periodo = $(document).find(".meta_metros_representante"+$verifica_entepenultimo).val();
						
						$verificar_ultimo_meta_reais_periodo = $(document).find(".meta_reais_representante"+$verifica_data_anterior).val();
						$verificar_ultimo_meta_metros_periodo = $(document).find(".meta_metros_representante"+$verifica_data_anterior).val();
						
						if($verificar_antepenultimo_meta_reais_periodo == '' && $verificar_ultimo_meta_reais_periodo ||
						$verificar_antepenultimo_meta_reais_periodo && $verificar_ultimo_meta_reais_periodo == ''){
							message("Atenção", "Sempre deve haver uma meta em reais quando existe uma anterior definida.");
							return false;
						}

						if(!$verificar_antepenultimo_meta_metros_periodo && $verificar_ultimo_meta_metros_periodo ||
						$verificar_antepenultimo_meta_metros_periodo && !$verificar_ultimo_meta_metros_periodo){
							message("Atenção", "Sempre deve haver uma meta em metros quando existe uma anterior definida.");
							return false;
						}
					}
					
                    $(document).find("#remove_periodo_vendedor"+anterior).hide();
                }
				
				$(document).find('.adicionar-elemento-apuracao-vendedor').append(conteudo);
				contador_anterior ++;
				x++;
			}else{
				message('Alerta','Limite Máximo de Períodos Atingido');
			}
		});

		$('.adicionar-elemento-apuracao-vendedor').on("click",".remove_documento_vendedor",function(e) {
			e.preventDefault();

			if(x > 1){
				var anterior = x - 2;
			}else{
				anterior = 1;
			}

			$(document).find("#remove_periodo_vendedor"+anterior).show();

			if(x > 1){
				x --; 
			}

			var id = $(this).attr('id');

			$(document).find('.'+ id).remove();

			contador_anterior --;
		});

        @if($tipo_periodo_gerente > 0)
        	var y = {{ $tipo_periodo_gerente }};
		@else
			var y = 1;
		@endif

		var max_fields_gerente = 30;

		@if($tipo_periodo_gerente > 0)
			var contador_anterior_gerente = {{ $tipo_periodo_gerente - 1 }};
		@else
			var contador_anterior_gerente = 0;
		@endif

		$('#adicionar_apuracao_gerente').click (function(e){
			e.preventDefault(); 
			$data_inicio_campanha = $(document).find("#data_inicio_campanha").val();
			$data_fim_campanha = $(document).find("#data_fim_campanha").val();

			if($data_inicio_campanha == '' || $data_fim_campanha == ''){
				message("Atenção", "A campanha está com período vazio.");
				return false;
			}

			$verifica_inicio = $(document).find("#data_inicio_campanha").datepicker("getDate");

			$verifica_fim = $(document).find("#data_fim_campanha").datepicker("getDate");

			if(($verifica_fim) < ($verifica_inicio)){
				message("Atenção", "A data de fim da campanha é menor que o início.");
				return false;
			}

			if (y < max_fields_vendedor){
                var anterior = 0;
				var conteudo = 
                '<div class="border border-dark rounded p-1 mb-1 remove_periodo_gerente'+y+'" id="adicionar-apuracao-vendedor-div-'+y+'">'+
                    '<div class="row">'+
                        '<div class="col-sm-3">'+
                            '{!! Form::label("data_inicio_apuracao_gerente['+y+']", "Início do Período", ["class"=>"input-label"]) !!} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio">*</span>'+
                            '<input type="text" id="data_inicio_apuracao_gerente['+y+']" name="data_inicio_apuracao_gerente['+y+']" readonly class="form-control data_inicio_apuracao_gerente'+y+'" form="campanha_form" length="20">'+
                        '</div>'+
                        '<div class="col-sm-3">'+
                            '{!! Form::label("data_fim_apuracao_gerentes['+y+']", "Fim do Período", ["class"=>"input-label"]) !!} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio">*</span>'+
                            '<input type="text" id="data_fim_apuracao_gerentes['+y+']" name="data_fim_apuracao_gerentes['+y+']" readonly class="form-control data_fim_apuracao_gerentes'+y+'" form="campanha_form" length="20">'+
                        '</div>'+
                        '<div class="col-sm-3">'+
                            '{!! Form::label("meta_reais_gerente['+y+']", "Meta em Reais:", ["class"=>"input-label"]) !!} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" form="campanha_form" class="campo_obrigatorio">*</span>'+
                            '<input type="text" id="meta_reais_gerente['+y+']" name="meta_reais_gerente['+y+']" class="form-control valor meta_reais_gerente'+y+'"  length="20" form="campanha_form">'+
                        '</div>'+
                        '<div class="col-sm-3">'+
                            '{!! Form::label("meta_metros_gerente['+y+']", "Meta em Metros:", ["class"=>"input-label"]) !!} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" form="campanha_form" class="campo_obrigatorio">*</span>'+
                            '<input type="text" id="meta_metros_gerente['+y+']" name="meta_metros_gerente['+y+']" class="form-control valor meta_metros_gerente'+y+'"  length="20" form="campanha_form">'+
                        '</div>'+
                    '</div>'+
					'<div class="col-sm-3 mt-2">'+
						'<input type="button" id="remove_periodo_gerente'+y+'" class="btn btn-danger remove_documento_gerente" value="Remover">'+
					'</div>'+
                '</div>';

				if(y > 0){
					if(y > 1){
                    	anterior = y - 1;
					}else{
						anterior = 1;
					}

                    for(var i = 0; i <= y; i++){
                        var data_inicio_apuracao_gerente = $(document).find(".data_inicio_apuracao_gerente"+i).val();
                        var data_fim_apuracao_gerente = $(document).find(".data_fim_apuracao_gerente"+i).val();
                        var meta_reais_gerente = $(document).find(".meta_reais_gerente"+i).val();
                        var meta_metros_gerente = $(document).find(".meta_metros_gerente"+i).val();
                        
                        if( data_inicio_apuracao_gerente == '' || 
							data_fim_apuracao_gerente == ''  
						 ){
                            message("Atenção", "Preencha os campos de data para adicionar mais períodos.");
                            return false;
                        }

						if(meta_reais_gerente && meta_metros_gerente){
							message("Atenção", "Não pode haver metas em Reais e Metros ao mesmo tempo.");
                            return false;
						}

                    }

					$verifica_data_anterior = contador_anterior_gerente;
					
					$verificar_ultimo_inicio_periodo = $(document).find(".data_inicio_apuracao_gerente"+$verifica_data_anterior).datepicker("getDate");
					
					$verificar_ultimo_fim_periodo = $(document).find(".data_fim_apuracao_gerente"+$verifica_data_anterior).datepicker("getDate");
					
					if($verificar_ultimo_fim_periodo < $verificar_ultimo_inicio_periodo){
						message("Atenção", "O Último período inserido tem data final menor que inicial.");
						return false;
					}

					if($verificar_ultimo_inicio_periodo < $verifica_inicio || $verificar_ultimo_inicio_periodo > $verifica_fim){
						message("Atenção", "Data Inicial fora do período de campanha.");
						return false;
					}

					if($verificar_ultimo_fim_periodo < $verifica_inicio || $verificar_ultimo_fim_periodo > $verifica_fim){
						message("Atenção", "Data Final fora do período de campanha.");
						return false;
					}
					
					if($verifica_data_anterior > 0){
						$verifica_entepenultimo = $verifica_data_anterior - 1;
						
						$verificar_antepenultimo_fim_periodo = $(document).find(".data_fim_apuracao_gerente"+$verifica_entepenultimo).datepicker("getDate");

						if($verificar_ultimo_inicio_periodo <= $verificar_antepenultimo_fim_periodo){
							message("Atenção", "O Último período inserido tem data final maior que a data inicial do período atual.");
							return false;
						}

						$verificar_antepenultimo_meta_reais_periodo = $(document).find(".meta_reais_gerente"+$verifica_entepenultimo).val();
						$verificar_antepenultimo_meta_metros_periodo = $(document).find(".meta_metros_gerente"+$verifica_entepenultimo).val();

						
						$verificar_ultimo_meta_reais_periodo = $(document).find(".meta_reais_gerente"+$verifica_data_anterior).val();
						$verificar_ultimo_meta_metros_periodo = $(document).find(".meta_metros_gerente"+$verifica_data_anterior).val();
						
						if(!$verificar_antepenultimo_meta_reais_periodo && $verificar_ultimo_meta_reais_periodo ||
						$verificar_antepenultimo_meta_reais_periodo && !$verificar_ultimo_meta_reais_periodo){
							message("Atenção", "Sempre deve haver uma meta em reais quando existe uma anterior definida.");
							return false;
						}

						if(!$verificar_antepenultimo_meta_metros_periodo && $verificar_ultimo_meta_metros_periodo ||
						$verificar_antepenultimo_meta_metros_periodo && !$verificar_ultimo_meta_metros_periodo){
							message("Atenção", "Sempre deve haver uma meta em metros quando existe uma anterior definida.");
							return false;
						}
					}
					
                    $(document).find("#remove_periodo_gerente"+anterior).hide();
                }
				
				$(document).find('.adicionar-elemento-apuracao-gerente').append(conteudo);
				contador_anterior_gerente ++;
				y++;
			}else{
				message('Alerta','Limite Máximo de Períodos Atingido');
			}
		});	

		$('.adicionar-elemento-apuracao-gerente').on("click",".remove_documento_gerente",function(e) {
			e.preventDefault();
			
			if(y > 1){
				var anterior = y - 2;
			}else{
				anterior = 1;
			}

			$(document).find("#remove_periodo_gerente"+anterior).show();

			if(y > 1){
				y --; 
			}

			var id = $(this).attr('id');
			$(document).find('.'+ id).remove();

			contador_anterior_gerente --;
		});

		var valores_temporarios;
		var linhas_retorno = [];

        @if(isset($retorno['produtos'][0]) && !empty($retorno['produtos'][0]))
            @foreach ($retorno['produtos'] as $key_produtos => $produtos_retorno)
                var estabelecimento_retorno = '{{ $produtos_retorno["estabelecimento_descricao"] }}'.replaceAll(' ','');
                valores_temporarios = [
                    "<input type='hidden' class='class_produto"+"{{ $produtos_retorno['codigo'] }}"+estabelecimento_retorno+"' id='"+"{{ $produtos_retorno['codigo'] }}"+"_"+"{{ $key_produtos }}"+"' name='produto_selecionado["+"{{ $key_produtos }}"+"]' value='"+"{{ $produtos_retorno['codigo'] }}"+"' form='campanha_form'>"+"{{ $produtos_retorno['codigo'] }}",
                    "<div><div data-toggle='tooltip' data-placement='left'  data-html='true' title='' data-original-title='" + "{{ $produtos_retorno["descricao"] }}" + "'>"+ "{{ $produtos_retorno["descricao"] }}"+"</div></div>",
                    "<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + "{{ $produtos_retorno["grupo"] }}" + "'>" + "{{ $produtos_retorno["grupo"] }}" + "</div></div>",
                    "<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + "{{ $produtos_retorno["subgrupo"] }}" + "'>" + "{{ $produtos_retorno["subgrupo"] }}" + "</div></div>",
                    "<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + "{{ $produtos_retorno["marca"] }}" + "'>" + "{{ $produtos_retorno["marca"] }}" + "</div></div>",
                    "<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + "{{ $produtos_retorno["linha"] }}" + "'>" + "{{ $produtos_retorno["linha"] }}" + "</div></div>",
                    "<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + "{{ $produtos_retorno["segmento"] }}" + "'>" + "{{ $produtos_retorno["segmento"] }}" + "</div></div>",
                    "<center>*</center>",
                ]; 

                linhas_retorno.push(valores_temporarios);
            @endforeach
            table_produtos_enviar.rows.add(linhas_retorno).draw();
        @endif
	});

	
	table_produtos_hidden = $('#table-filters-pedidos-itens-hidden')
	.on( 'error.dt', function ( e, settings, techNote, men ) {
		hide_loader();
		message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
	}).DataTable({
		"searching": true,
		"lengthChange": false,
		"info": false,
		"scrollY": "25vh",
		"scrollCollapse": true,
		"paging": false,
		"autowidth": false,
		"language": {
			"decimal":        ",",
			"emptyTable":     "Nenhum registro encontrado",
			"infoPostFix":    "",
			"thousands":      ".",
			"loadingRecords": "Carregando...",
			"processing":     "Processando...",
			"zeroRecords":    "Nenhum registro encontrado",
			"search": "Buscar: ",
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
		]
	});
	
	table_produtos_enviar = $('#table-filters-pedidos-itens')
	.on( 'error.dt', function ( e, settings, techNote, men ) {
		message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
	}).DataTable({
		"searching": true,
		"searching": true,
		"lengthChange": false,
		"info": false,
		"pageLength": 10,
		"orderMulti": false,
		"language": {
			"decimal":        ",",
			"emptyTable":     "Nenhum registro encontrado",
			"infoPostFix":    "",
			"thousands":      ".",
			"loadingRecords": "Carregando...",
			"processing":     "Processando...",
			"zeroRecords":    "Nenhum registro encontrado",
			"search": "Buscar: ",
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
		]
	}).draw();


</script>
@endsection
