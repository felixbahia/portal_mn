@extends('layouts.page-dialog')
@section('content')
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link troca-aba active" id='campanha_header_tab' data-toggle="tab" href="#campanha_header" role="tab" aria-controls="campanha_header" aria-selected="true">Campanha</a>
	</li>
	<li class="nav-item">
		<a class="nav-link troca-aba" id="apuracao_vendedor_tab" data-toggle="tab" href="#apuracao_vendedor" role="tab" aria-controls="pedido_web_itens" aria-selected="false">Meta Vendedores/Representantes</a>
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
					{!! Form::text('data_inicio_campanha', $retorno['inicio_campanha'], ['id' => 'data_inicio_campanha', 'class' => 'form-control data_modal', 'style' =>'pointer-events:none', 'maxlength' => '20', 'readonly']) !!}
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
					{{ Form::select("tipo_comissao_representante", $comissoes_tipo, $retorno['tipo_comissao_representante'], ["id" => "estabelecimento_filtro", "class" => "form-control"]) }}
				</div>
				<div class="form-group col-sm-6 col-lg-3 content-not-estabel">
					{{ Form::label('comissao_representante', 'Incentivo Comissão(%) Representante:', []) }}
					{{ Form::text('comissao_representante', $retorno['comissao_representante'], array('id' => 'comissao_representante', 'class' => 'form-control valor text-right', "maxlength" => "180")) }}
				</div>
				<div class="col-sm-2 col-lg-3">
					{{ Form::label('', 'Tipo de Comissão(%):', []) }}
					{{ Form::select("tipo_comissao_vendedor_interno", $comissoes_tipo, $retorno['tipo_comissao_vendedor_interno'], ["id" => "estabelecimento_filtro", "class" => "form-control"]) }}
				</div>
				<div class="form-group col-sm-6 col-lg-3 content-not-estabel">
					{{ Form::label('comissao_vendedor_interno', 'Incentivo Comissão(%) Vendedor Interno:', []) }}
					{{ Form::text('comissao_vendedor_interno', $retorno['comissao_vendedor_interno'], array('id' => 'comissao_vendedor_interno', 'class' => 'form-control valor text-right', "maxlength" => "180")) }}
				</div>
			</div>
			<div class="form-row">
				<div class="col-sm-2 col-lg-3">
					{{ Form::label('', 'Tipo de Comissão(%):', []) }}
					{{ Form::select("tipo_comissao_gerente", $comissoes_tipo, $retorno['tipo_comissao_gerente'], ["id" => "estabelecimento_filtro", "class" => "form-control"]) }}
				</div>
				<div class="form-group col-sm-6 col-lg-3 content-not-estabel">
					{{ Form::label('comissao_gerente', 'Incentivo Comissão(%) Gerente:', []) }}
					{{ Form::text('comissao_gerente', $retorno['comissao_gerente'], array('id' => 'comissao_gerente', 'class' => 'form-control valor text-right', "maxlength" => "180")) }}
				</div>
			</div>
		    <div class="row">
				<div class="col-sm-12">
					<button type="button" id="bt_ir_para_apaurcao_vendedores" class="btn btn-info troca-aba float-right">Ir para Meta de resultado vendedores >></button>
				</div>
			</div>
	</div>
	<div class="tab-pane" id="apuracao_vendedor" role="tabpanel" aria-labelledby="apuracao_vendedor_tab">
		<div class="adicionar-elemento-apuracao-vendedor">
		@if($tipo_periodo_vendedor > 0)
			{!! Form::hidden('contador_vendedor', $contador_vendedor = 0, ["id" => 'contador_vendedor']) !!}
			@foreach ($retorno['apuracao'] as $apuracao_dados_vendedores)
				@if($apuracao_dados_vendedores['tipo'] == 1)
					<div class="border border-dark rounded p-1 mb-1 @if($apuracao_dados_vendedores['fim_apuracao'] === false) remove_periodo_vendedor{{$contador_vendedor}} @endif">
						<div class="row">
							{!! Form::hidden('apuracao_vendedores_id['.$contador_vendedor.']', $apuracao_dados_vendedores['id'], ["id" => 'apuracao_vendedores_id['.$contador_vendedor.']', 'form' => 'campanha_form']) !!}
							<div class="form-group col-sm-2">
								{{ Form::label('data_inicio_apuracao_vendedor', 'Início do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
								{!! Form::text('data_inicio_apuracao_vendedor['.$contador_vendedor.']', $apuracao_dados_vendedores['inicio_periodo'], ['id' => 'data_inicio_apuracao_vendedor['.$contador_vendedor.']', 'class' => 'form-control data_modal data_inicio_apuracao_vendedor'.$contador_vendedor, 'style' => (($contador_vendedor +1) != $tipo_periodo_vendedor || $contador_vendedor === 0) ? 'pointer-events:none' : 'pointer-events:auto', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
							</div>
							<div class="form-group col-sm-2">
								{{ Form::label('data_fim_apuracao_vendedor', 'Fim do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
								{!! Form::text('data_fim_apuracao_vendedor['.$contador_vendedor.']', $apuracao_dados_vendedores['fim_periodo'], ['id' => 'data_fim_apuracao_vendedor['.$contador_vendedor.']', 'class' => 'form-control data_modal data_fim_apuracao_vendedor'.$contador_vendedor, 'style' => (($contador_vendedor +1) != $tipo_periodo_vendedor) ? 'pointer-events:none' : 'pointer-events:auto', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
							</div>
							<div class="form-group col-sm-2">
								{{ Form::label('meta_reais_representante', 'Meta em Reais:', []) }}
								{!! Form::text('meta_reais_representante['.$contador_vendedor.']', (!empty($apuracao_dados_vendedores['meta_reais'])) ? $apuracao_dados_vendedores['meta_reais'] : null, array('id' => 'meta_reais_representante['.$contador_vendedor.']', 'class' => 'verifica_reais_representante form-control valor meta_reais_representante'.$contador_vendedor, "maxlength" => "180", 'form' => 'campanha_form')) !!}
							</div>
							<div class="form-group col-sm-2">
								{{ Form::label('meta_metros_representante', 'Meta em Metros:', []) }}
								{!! Form::text('meta_metros_representante['.$contador_vendedor.']', (!empty($apuracao_dados_vendedores['meta_metros'])) ? $apuracao_dados_vendedores['meta_metros'] : null, array('id' => 'meta_metros_representante['.$contador_vendedor.']', 'class' => 'verifica_metros_representante form-control valor meta_metros_representante'.$contador_vendedor, "maxlength" => "180", 'form' => 'campanha_form')) !!}
							</div>
							<div class="form-group col-sm-3">
								{{ Form::label('usuario', 'Usuário', []) }}
								{!! Form::text('usuario['.$contador_vendedor.']', $apuracao_dados_vendedores['usuario'], ['id' => 'usuario['.$contador_vendedor.']', 'class' => 'form-control data_modal', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
							</div>

							@if(($contador_vendedor +1) == $tipo_periodo_vendedor && $contador_vendedor != 0)
								<div class="col-sm-3 mt-2">
									<input type="button" id="remove_periodo_vendedor{{ $contador_vendedor }}" class="btn btn-danger remove_documento_vendedor" value="Remover">
								</div>
							@elseif($contador_vendedor == 0)
								<div class="col-sm-3 mt-2">
									<input type="button" id="limpar_campos_iniciais_vendedor" class="btn btn-danger limpar_campos_iniciais_vendedor" value="Remover">
								</div>
							@else
								<div class="col-sm-3 mt-2">
									<input type="button" id="remove_periodo_vendedor{{ $contador_vendedor }}" class="btn btn-danger remove_documento_vendedor" value="Remover" style="display: none;">
								</div>
							@endif
						</div>
					</div>
					{!! Form::hidden('contador_vendedor_fim', $contador_vendedor =  $contador_vendedor + 1, ["id" => 'contador_vendedor_fim']) !!}
				@endif
			@endforeach
		@else
			<div class="border border-dark rounded p-1 mb-1">
				<div class="row">
					<div class="form-group col-sm-3">
						{{ Form::label('data_inicio_apuracao_vendedor', 'Início do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
						{!! Form::text('data_inicio_apuracao_vendedor[0]', '', ['id' => 'data_inicio_apuracao_vendedor[0]', 'class' => 'form-control data_modal data_inicio_apuracao_vendedor0', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
					</div>
					<div class="form-group col-sm-3">
						{{ Form::label('data_fim_apuracao_vendedor', 'Fim do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
						{!! Form::text('data_fim_apuracao_vendedor[0]', '', ['id' => 'data_fim_apuracao_vendedor[0]', 'class' => 'form-control data_modal data_fim_apuracao_vendedor0', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
					</div>
					<div class="form-group col-sm-3">
						{{ Form::label('meta_reais_representante', 'Meta em Reais:', []) }}
						{!! Form::text('meta_reais_representante[0]', '', array('id' => 'meta_reais_representante[0]', 'class' => 'form-control valor meta_reais_representante0', "maxlength" => "180", 'form' => 'campanha_form')) !!}
					</div>
					<div class="form-group col-sm-3">
						{{ Form::label('meta_metros_representante', 'Meta em Metros:', []) }}
						{!! Form::text('meta_metros_representante[0]', '', array('id' => 'meta_metros_representante[0]', 'class' => 'form-control valor meta_metros_representante0', "maxlength" => "180", 'form' => 'campanha_form')) !!}
					</div>
					<div class="col-sm-3 mt-2">
						<input type="button" id="limpar_campos_iniciais_vendedor" class="btn btn-danger limpar_campos_iniciais_vendedor" value="Remover">
					</div>
				</div>
			</div>
		@endif
		</div>
		<div class="row mt-2">
			<div class="col-sm text-right" id='enviar-div'>
				{!! Form::button('Adicionar Período', ['id' => 'adicionar_apuracao_vendedor', 'class' => 'btn btn-success float-left']) !!}
			</div>
		</div>
		<br>
		<div class="row">
			<div class="col-sm-12">
				<button type="button" id="bt_ir_para_campanha" class="btn troca-aba btn-info"><< Voltar para Campanha</button>
				<button type="button" id="bt_ir_para_apaurcao_gerentes" class="btn btn-info troca-aba float-right">Ir para Meta de resultado gerentes >></button>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="apuracao_gerente" role="tabpanel" aria-labelledby="apuracao_gerente_tab">
		<div class="adicionar-elemento-apuracao-gerente">
		@if($tipo_periodo_gerente > 0)
		{!! Form::hidden('contador_gerente', $contador_gerente = 0, ["id" => 'contador_gerente']) !!}
			@foreach ($retorno['apuracao'] as $count_gerente => $apuracao_dados_gerentes)
				@if($apuracao_dados_gerentes['tipo'] == 2)
					<div class="border border-dark rounded p-1 mb-1 remove_periodo_gerente{{$contador_gerente}}">
						{!! Form::hidden('apuracao_gerente_id['.$contador_gerente.']', $apuracao_dados_gerentes['id'], ["id" => 'apuracao_gerente_id['.$contador_gerente.']', 'form' => 'campanha_form']) !!}
						<div class="row">
							<div class="form-group col-sm-2">
								{{ Form::label('data_inicio_apuracao_gerente', 'Início do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
								{!! Form::text('data_inicio_apuracao_gerente['.$contador_gerente.']', $apuracao_dados_gerentes['inicio_periodo'], ['id' => 'data_inicio_apuracao_gerente['.$contador_gerente.']', 'class' => 'form-control data_modal data_inicio_apuracao_gerente'.$contador_gerente, 'style' => (($contador_gerente +1) != $tipo_periodo_gerente || $contador_gerente == 0) ? 'pointer-events:none' : 'pointer-events:auto', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
							</div>
							<div class="form-group col-sm-2">
								{{ Form::label('data_fim_apuracao_gerentes', 'Fim do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
								{!! Form::text('data_fim_apuracao_gerentes['.$contador_gerente.']', $apuracao_dados_gerentes['fim_periodo'], ['id' => 'data_fim_apuracao_gerentes['.$contador_gerente.']', 'class' => 'form-control data_modal data_fim_apuracao_gerente'.$contador_gerente, 'style' => (($contador_gerente +1) != $tipo_periodo_gerente) ? 'pointer-events:none' : 'pointer-events:auto',  'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
							</div>
							<div class="form-group col-sm-2 content-not-estabel">
								{{ Form::label('meta_reais_gerente', 'Meta em Reais:', []) }}
								{!! Form::text('meta_reais_gerente['.$contador_gerente.']', (!empty($apuracao_dados_gerentes['meta_reais'])) ? $apuracao_dados_gerentes['meta_reais'] : '', array('id' => 'meta_reais_gerente['.$contador_gerente.']', 'class' => 'verifica_reais_gerente form-control valor meta_reais_gerente'.$contador_gerente, "maxlength" => "180", 'form' => 'campanha_form')) !!}
							</div>
							<div class="form-group col-sm-2 content-not-estabel">
								{{ Form::label('meta_metros_gerente', 'Meta em Metros:', []) }}
								{!! Form::text('meta_metros_gerente['.$contador_gerente.']', (!empty($apuracao_dados_gerentes['meta_metros'])) ? $apuracao_dados_gerentes['meta_metros'] : '', array('id' => 'meta_metros_gerente['.$contador_gerente.']', 'class' => 'verifica_metros_gerente form-control valor meta_metros_gerente'.$contador_gerente, "maxlength" => "180", 'form' => 'campanha_form')) !!}
							</div>
							<div class="form-group col-sm-3">
								{{ Form::label('usuario', 'Usuário', []) }}
								{!! Form::text('usuario['.$contador_gerente.']', $apuracao_dados_gerentes['usuario'], ['id' => 'usuario['.$contador_gerente.']', 'class' => 'form-control data_modal', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
							</div>

							@if(($contador_gerente +1) == $tipo_periodo_gerente && $contador_gerente != 0)
								<div class="col-sm-3 mt-2">
									<input type="button" id="remove_periodo_gerente{{ $contador_gerente }}" class="btn btn-danger remove_documento_gerente" value="Remover">
								</div>
							@elseif($contador_gerente == 0)
								<div class="col-sm-3 mt-2">
									<input type="button" id="limpar_campos_iniciais_gerente" class="btn btn-danger limpar_campos_iniciais_gerente" value="Remover">
								</div>
							@else
								<div class="col-sm-3 mt-2">
									<input type="button" id="remove_periodo_gerente{{ $contador_gerente }}" class="btn btn-danger remove_documento_gerente" value="Remover" style="display: none;">
								</div>
							@endif
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
						{!! Form::text('data_inicio_apuracao_gerente[0]', '', ['id' => 'data_inicio_apuracao_gerente[0]', 'class' => 'form-control data_modal data_inicio_apuracao_gerente0', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
					</div>
					<div class="form-group col-sm-3">
						{{ Form::label('data_fim_apuracao_gerentes', 'Fim do Período', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
						{!! Form::text('data_fim_apuracao_gerentes[0]', '', ['id' => 'data_fim_apuracao_gerentes[0]', 'class' => 'form-control data_modal data_fim_apuracao_gerente0', 'maxlength' => '20', 'readonly', 'form' => 'campanha_form']) !!}
					</div>
					<div class="form-group col-sm-3 content-not-estabel">
						{{ Form::label('meta_reais_gerente', 'Meta em Reais:', []) }}
						{!! Form::text('meta_reais_gerente[0]', '', array('id' => 'meta_reais_gerente[0]', 'class' => 'form-control valor meta_reais_gerente0', "maxlength" => "180", 'form' => 'campanha_form')) !!}
					</div>
					<div class="form-group col-sm-3 content-not-estabel">
						{{ Form::label('meta_metros_gerente', 'Meta em Metros:', []) }}
						{!! Form::text('meta_metros_gerente[0]', '', array('id' => 'meta_metros_gerente[0]', 'class' => 'form-control valor meta_metros_gerente0', "maxlength" => "180", 'form' => 'campanha_form')) !!}
					</div>
					<div class="col-sm-3 mt-2">
						<input type="button" id="limpar_campos_iniciais_gerente" class="btn btn-danger limpar_campos_iniciais_gerente" value="Remover">
					</div>
				</div>
			</div>
		@endif
		</div>
		<div class="row mt-2">
			<div class="col-sm text-right" id='enviar-div'>
				{!! Form::button('Adicionar Período', ['id' => 'adicionar_apuracao_gerente', 'class' => 'btn btn-success float-left']) !!}
			</div>
		</div>
		<br>
		<div class="row">
			<div class="col-sm-12">
				<button type="button" id="bt_ir_para_apaurcao_vendedores" class="btn troca-aba btn-info"><< Voltar para Meta Resultados Vendedores</button>
				<button type="button" id="bt_ir_para_produtos" class="btn btn-info troca-aba float-right">Ir para produtos >></button>
			</div>
		</div>
	</div>
	</form>
	<div class="tab-pane" id="inserir_produtos" role="tabpanel" aria-labelledby="inserir_produtos_tab">
		<div class="content-filter-dialog">	
	    	<form action="post" name="form_filter_pedidos_itens" class="cadPedido" id="form_filter_itens" onsubmit="return false;">
	    		<p>
					<strong>Inserir produtos</strong>
                </p>
		    	<div class="content-fields">
                    <div class="form-row text-center mb-0">
                        <div class="form-group col-sm-12 text-center">    
							<div class="content-buttons">
								<button name="btn-create" id="bt-search-produto-campanha" class="btn-create float-left mt-2">Inserir Produto</button>
							</div>                
                        </div>
					</div>
                </div>
				<label class="btn btn-upload" data-toggle="tooltip" data-placement="top" title="Importar arquivo">
					<input type="file" id='importar-csv' style='display:none;'>
					<a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="top" title="" data-original-title="O arquivo deve conter somente o Código do Produto, no arquivo .csv deve ter o código do produto na primeira coluna, e arquivo .txt separado por vírgula." style="color: black;"></a>
				</label>
            </form>
		</div>
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
				<button type="button" id="bt_ir_para_apaurcao_gerentes" class="btn troca-aba btn-info"><< Voltar para Meta Resultados Gerentes</button>
				<button type="button" id="gravar_campanha" class="btn btn-success float-right">Atualizar Campanha</button>
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
	var produtos_enviar_array = [];
	var produtos_carregados_array = [];

	$(document).ready(function(){
		setInterval(function(){
            dataMask();
            valorMask();
			copiarDataInialPeriodoCampanha();
		}
		, 1000);

		$(document).find('#importar-csv').on('change', function(){
            importarArquivo();
        });

		@if($tipo_periodo_vendedor > 1)
			$(document).find("#limpar_campos_iniciais_vendedor").hide();
		@endif

		
		@if($tipo_periodo_gerente > 1)
			$(document).find("#limpar_campos_iniciais_gerente").hide();
		@endif
		
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

		$(document).find(".seleciona_estab").off("click");
        $(document).find(".seleciona_estab").on("click", function(event){
			estabelecimentos_selecionados = [];
			$('.seleciona_estab:checked').each(function(i){
				estabelecimentos_selecionados.push([$(this).val()]);
			});
		
        });

		$(document).find(".seleciona_estab_todos").off("click");
        $(document).find(".seleciona_estab_todos").on("click", function(event){
			if($(document).find('.seleciona_estab_todos').is(":checked") == false){
				$(document).find('.seleciona_estab').prop("checked", false);
			}else{
				$(document).find('.seleciona_estab').prop("checked", true);
			}
			estabelecimentos_selecionados = [];
			$('.seleciona_estab:checked').each(function(i){
				estabelecimentos_selecionados.push([$(this).val()]);
			});
	
        });

		@foreach ($retorno['estabelecimento'] as $estabelecimento)
			$(document).find('#{{$estabelecimento['estabelecimento_codigo']}}').prop("checked", true);
			estabelecimentos_selecionados.push('{{$estabelecimento['estabelecimento_codigo']}}');
		@endforeach
        
		$(document).find("#gravar_campanha").off("click");
        $(document).find("#gravar_campanha").on("click", function(event){
			event.stopPropagation();
            atualizarCampanha();
        });

		$(document).find("#bt-search-produto-campanha").off("click");
        $(document).find("#bt-search-produto-campanha").on("click", function(event){
			event.stopPropagation();
			table_produtos_hidden.clear().draw();
            showModalBuscaProduto();
        });

		@if($tipo_periodo_vendedor > 0)
        	var x = {{ $tipo_periodo_vendedor }};
		@else
			var x = 1;
		@endif

		var max_fields_vendedor = 50;

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
                            '<input type="text" id="data_inicio_apuracao_vendedor['+x+']" name="data_inicio_apuracao_vendedor['+x+']" readonly class="form-control data_modal data_inicio_apuracao_vendedor'+x+'" form="campanha_form"  length="20">'+
                        '</div>'+
                        '<div class="col-sm-3">'+
                            '{!! Form::label("data_fim_apuracao_vendedor['+x+']", "Fim do Período", ["class"=>"input-label"]) !!} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio">*</span>'+
                            '<input type="text" id="data_fim_apuracao_vendedor['+x+']" name="data_fim_apuracao_vendedor['+x+']" readonly class="form-control data_modal data_fim_apuracao_vendedor'+x+'" form="campanha_form" length="20">'+
                        '</div>'+
                        '<div class="col-sm-3">'+
                            '{!! Form::label("meta_reais_representante['+x+']", "Meta em Reais:", ["class"=>"input-label"]) !!} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" form="campanha_form" class="campo_obrigatorio">*</span>'+
                            '<input type="text" id="meta_reais_representante['+x+']" name="meta_reais_representante['+x+']" class="form-control valor meta_reais_representante'+x+'"  length="20" form="campanha_form">'+
                        '</div>'+
                        '<div class="col-sm-3">'+
                            '{!! Form::label("meta_metros_representante['+x+']", "Meta em Metros:", ["class"=>"input-label"]) !!} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" form="campanha_form" class="campo_obrigatorio">*</span>'+
                            '<input type="text" id="meta_metros_representante['+x+']" name="meta_metros_representante['+x+']" class="form-control valor meta_metros_representante'+x+'"  length="20" form="campanha_form">'+
                        '</div>'+
                    '</div>'+
					'<div class="col-sm-3 mt-2">'+
						'<input type="button" id="remove_periodo_vendedor'+x+'" class="btn btn-danger remove_documento_vendedor" value="Remover">'+
					'</div>'+
                '</div>';

				var data_inicio_apuracao_vendedor_validacao = $(document).find(".data_inicio_apuracao_vendedor0").val();

				if(data_inicio_apuracao_vendedor_validacao !== '' && data_inicio_apuracao_vendedor_validacao !== $data_inicio_campanha){
					message('Atenção','<b class="text-danger"> A data inicial do primeiro período deve ser igual a data inicial da campanha.</b>');
					return false;
				}

				if(x > 0){
					if(x > 1){
                    	anterior = x - 1;
						$(document).find("#limpar_campos_iniciais_vendedor").hide();
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
					
					if($verificar_ultimo_fim_periodo <= $verificar_ultimo_inicio_periodo){
						message("Atenção", "O Último período inserido tem data final menor ou igual que inicial.");
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
							message("Atenção", "O Último período inserido tem data final maior ou igual a data inicial do período atual.");
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

						var $verificar_antepenultimo_fim_periodo_mais_um = $(document).find(".data_fim_apuracao_vendedor"+$verifica_entepenultimo).datepicker("getDate");
						$verificar_antepenultimo_fim_periodo_mais_um.setDate($verificar_antepenultimo_fim_periodo_mais_um.getDate() + 1);
						$verificar_antepenultimo_fim_periodo_mais_um = $verificar_antepenultimo_fim_periodo_mais_um.getDate()+'/'+$verificar_antepenultimo_fim_periodo_mais_um.getMonth() + 1 + "/" + $verificar_antepenultimo_fim_periodo_mais_um.getFullYear();
						$verificar_ultimo_inicio_periodo = $verificar_ultimo_inicio_periodo.getDate()+'/'+$verificar_ultimo_inicio_periodo.getMonth() + 1 + "/" + $verificar_ultimo_inicio_periodo.getFullYear();
						
						if($verificar_antepenultimo_fim_periodo_mais_um != $verificar_ultimo_inicio_periodo){
							message('Atenção','A data inial inserida do período atual não é a posterior imediata a data final do período antecessor.');
							return false;
						}
					}
					
                    $(document).find("#remove_periodo_vendedor"+anterior).hide();
                }

				$(document).find(".data_fim_apuracao_vendedor"+$verifica_data_anterior).css({"pointer-events": "none"});
				$(document).find(".data_inicio_apuracao_vendedor"+$verifica_data_anterior).css({"pointer-events": "none"});

				$(document).find('.adicionar-elemento-apuracao-vendedor').append(conteudo);
				contador_anterior ++;
				x++;
				$(document).find("#limpar_campos_iniciais_vendedor").hide();
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

			if(!$(document).find(".remove_periodo_vendedor"+anterior)[0]){
				message('Atenção','Não é possível remover o perído atual pois o anteior já venceu.');

				return false;
			}
			
			if((x - 2) == 0){
				$(document).find("#limpar_campos_iniciais_vendedor").show();
			}

			if(anterior != 0){
				$(document).find(".data_inicio_apuracao_vendedor"+anterior).css({"pointer-events": "auto"});
			}

			$(document).find(".data_fim_apuracao_vendedor"+anterior).css({"pointer-events": "auto"});
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

		var max_fields_gerente = 50;

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

			$verifica_inicio = $(document).find("#data_inicio_campanha").val();
			$dia_inicio = $verifica_inicio.substr(0, 2);
			$mes_inicio = $verifica_inicio.substr(3, 2);
			$ano_inicio = $verifica_inicio.substr(6, 4);
			$verifica_inicio = new Date($ano_inicio+'-'+$mes_inicio+'-'+$dia_inicio);

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
                            '<input type="text" id="data_inicio_apuracao_gerente['+y+']" name="data_inicio_apuracao_gerente['+y+']" readonly class="form-control data_modal data_inicio_apuracao_gerente'+y+'" form="campanha_form" length="20">'+
                        '</div>'+
                        '<div class="col-sm-3">'+
                            '{!! Form::label("data_fim_apuracao_gerentes['+y+']", "Fim do Período", ["class"=>"input-label"]) !!} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio">*</span>'+
                            '<input type="text" id="data_fim_apuracao_gerentes['+y+']" name="data_fim_apuracao_gerentes['+y+']" readonly class="form-control data_modal data_fim_apuracao_gerente'+y+'" form="campanha_form" length="20">'+
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

				var data_inicio_apuracao_gerente_validacao = $(document).find(".data_inicio_apuracao_gerente0").val();

				if(data_inicio_apuracao_gerente_validacao !== '' && data_inicio_apuracao_gerente_validacao !== $data_inicio_campanha){
					message('Atenção','<b class="text-danger"> A data inicial do primeiro período deve ser igual a data inicial da campanha.</b>');
					return false;
				}

				if(y > 0){
					if(y > 1){
                    	anterior = y - 1;
						$(document).find("#limpar_campos_iniciais_gerente").hide();
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
					
					if($verificar_ultimo_fim_periodo <= $verificar_ultimo_inicio_periodo){
						message("Atenção", "O Último período inserido tem data final menor ou igual que inicial.");
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
							message("Atenção", "O Último período inserido tem data final maior ou igual que a data inicial do período atual.");
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

						var $verificar_antepenultimo_fim_periodo_mais_um_gerente = $(document).find(".data_fim_apuracao_gerente"+$verifica_entepenultimo).datepicker("getDate");
						$verificar_antepenultimo_fim_periodo_mais_um_gerente.setDate($verificar_antepenultimo_fim_periodo_mais_um_gerente.getDate() + 1);
						$verificar_antepenultimo_fim_periodo_mais_um_gerente = $verificar_antepenultimo_fim_periodo_mais_um_gerente.getDate()+'/'+$verificar_antepenultimo_fim_periodo_mais_um_gerente.getMonth() + 1 + "/" + $verificar_antepenultimo_fim_periodo_mais_um_gerente.getFullYear();
						$verificar_ultimo_inicio_periodo = $verificar_ultimo_inicio_periodo.getDate()+'/'+$verificar_ultimo_inicio_periodo.getMonth() + 1 + "/" + $verificar_ultimo_inicio_periodo.getFullYear();
						
						if($verificar_antepenultimo_fim_periodo_mais_um_gerente != $verificar_ultimo_inicio_periodo){
							message('Atenção','A data inial inserida do período atual não é a posterior imediata a data final do período antecessor.');
							return false;
						}
					}
					
                    $(document).find(".data_fim_apuracao_gerente"+$verifica_data_anterior).css({"pointer-events": "none"});
					$(document).find(".data_inicio_apuracao_gerente"+$verifica_data_anterior).css({"pointer-events": "none"});
					$(document).find("#remove_periodo_gerente"+anterior).hide();
                }
				
				$(document).find('.adicionar-elemento-apuracao-gerente').append(conteudo);
				contador_anterior_gerente ++;
				y++;
				$(document).find("#limpar_campos_iniciais_gerente").hide();
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

			if((y - 2) == 0){
				$(document).find("#limpar_campos_iniciais_gerente").show();
			}

			if(anterior != 0){
				$(document).find(".data_inicio_apuracao_gerente"+anterior).css({"pointer-events": "auto"});
			}
			$(document).find(".data_fim_apuracao_gerente"+anterior).css({"pointer-events": "auto"});
			$(document).find("#remove_periodo_gerente"+anterior).show();

			if(y > 1){
				y --; 
			}

			var id = $(this).attr('id');
			
			$(document).find('.'+ id).remove();

			contador_anterior_gerente --;
		});

		$('#limpar_campos_iniciais_vendedor').click (function(e){
			limparCamposVendedor();
		});

		$('#limpar_campos_iniciais_gerente').click (function(e){
			limparCamposGerente();
		});

		var valores_temporarios;
		var linhas_retorno = [];

		@if(isset($retorno['produtos'][0]) && !empty($retorno['produtos'][0]))
			@foreach ($retorno['produtos'] as $key_produtos => $produtos_retorno)
				@if(!empty($produtos_retorno["codigo"]))
					var estabelecimento_retorno = '{{ $produtos_retorno["estabelecimento_descricao"] }}'.replaceAll(' ','');
					var produtos_tratado = '{{ $produtos_retorno["codigo"] }}'.replaceAll('/','');

					valores_temporarios = [
						"<input type='hidden' class='class_produto"+produtos_tratado+"' id='"+"{{ $produtos_retorno['codigo'] }}"+"_"+"{{ $key_produtos }}"+"' name='produto_selecionado_adicionado["+"{{ $key_produtos }}"+"]' value='"+"{{ $produtos_retorno['codigo'] }}"+"' >"+"{{ $produtos_retorno['codigo'] }}",
						"<div><div data-toggle='tooltip' data-placement='left'  data-html='true' title='' data-original-title='" + "{{ $produtos_retorno["descricao"] }}" + "'>"+ "{{ $produtos_retorno["descricao"] }}"+"</div></div>",
						"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + "{{ $produtos_retorno["grupo"] }}" + "'>" + "{{ $produtos_retorno["grupo"] }}" + "</div></div>",
						"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + "{{ $produtos_retorno["subgrupo"] }}" + "'>" + "{{ $produtos_retorno["subgrupo"] }}" + "</div></div>",
						"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + "{{ $produtos_retorno["marca"] }}" + "'>" + "{{ $produtos_retorno["marca"] }}" + "</div></div>",
						"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + "{{ $produtos_retorno["linha"] }}" + "'>" + "{{ $produtos_retorno["linha"] }}" + "</div></div>",
						"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + "{{ $produtos_retorno["segmento"] }}" + "'>" + "{{ $produtos_retorno["segmento"] }}" + "</div></div>",
							"<center><div class='" + "{{ $produtos_retorno["cor"] }} "  + " {{ $produtos_retorno["codigo"] }}" +"'>" + "{{ $produtos_retorno["situacao"] }}" +"<a href='#' class='bt-edit-direita'  onclick=\"situacaoProdutoCampanha('" + produtos_tratado +"','" + "{{ $produtos_retorno["codigo"] }}" + "','"  + "{{ $produtos_retorno["campanha"] }}" + "','" + "{{ $produtos_retorno["id_campanha"] }}" + "','"  + "{{ $produtos_retorno["mensagem_campanha_ativo"] }}" + "','"  + "{{ $produtos_retorno["mensagem_campanha_inativo"] }}" + "')\"></a></div></center>",
					]; 

					linhas_retorno.push(valores_temporarios);
					produtos_carregados_array.push("{{ $produtos_retorno["codigo"] }}"+"|"+estabelecimento_retorno);
				@endif
			@endforeach
			
			table_produtos_enviar.rows.add(linhas_retorno).draw();
		@endif
	});

	function reverseString(str) {
    var splitString = str.split("");
    var reverseArray = splitString.reverse();
    var joinArray = reverseArray.join("");
    return joinArray;
}

	function copiarDataInialPeriodoCampanha(){
		var data_inicio_campanha = $(document).find("#data_inicio_campanha").val();
		$(document).find(".data_inicio_apuracao_vendedor0").datepicker('setDate',data_inicio_campanha).val(data_inicio_campanha);
		$(document).find(".data_inicio_apuracao_gerente0").datepicker('setDate',data_inicio_campanha).val(data_inicio_campanha);
		$(document).find(".data_inicio_apuracao_vendedor0").css({"pointer-events": "none"});
		$(document).find(".data_inicio_apuracao_gerente0").css({"pointer-events": "none"});
	}

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
		"pageLength": 6,
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

	function valorMask(){
        $(document).find(".valor").maskMoney({thousands:'.', decimal:',', precision: 2});

		if($(document).find(".verifica_reais_representante").val() == '0,00'){
			$(document).find(".verifica_reais_representante").val('');
		}

		if($(document).find(".verifica_metros_representante").val() == '0,00'){
			$(document).find(".verifica_metros_representante").val('');
		}

		if($(document).find(".verifica_reais_gerente").val() == '0,00'){
			$(document).find(".verifica_reais_gerente").val('');
		}

		if($(document).find(".verifica_metros_gerente").val() == '0,00'){
			$(document).find(".verifica_metros_gerente").val('');
		}
		
    }

	function dataMask(){
        $('.data_modal').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            zIndex: 2000,
        	startDate: new Date(),
            autoHide: true
        });
    }

	function limparCamposVendedor(){
		$(document).find(".data_fim_apuracao_vendedor0").val("");
		$(document).find(".meta_reais_representante0").val("");
		$(document).find(".meta_metros_representante0").val("");
	}

	function limparCamposGerente(){
		$(document).find(".data_fim_apuracao_gerente0").val("");
		$(document).find(".meta_reais_gerente0").val("");
		$(document).find(".meta_metros_gerente0").val("");
	}

	function showModalBuscaProduto(){
        $.ajax({
            url: '{{ Route('campanha.modal.pesquisa_produto') }}',
            type: 'POST',
           data: {
                _token: '{{ csrf_token()}}',
				id_campanha: "{{ $retorno['id'] }}"
            },
            success: function(body) {
            	$(document).find('#table-modal-produtos-busca-campanha').remove();
                createModal('table-modal-produtos-busca-campanha', 'Pesquisa de Produtos para Campanha', body, 'modal-lg');

                var modal = $(document).find('#table-modal-produtos-busca-campanha');
                
				setInterval(function(){
					$(document).find('#enviar_todos_itens_busca').off("click");
					$(document).find('#enviar_todos_itens_busca').on('click', function(){
						adicionarTodosProdutos();
					});
					
					$(document).find('#enviar_itens_selecionados').off("click");
					$(document).find('#enviar_itens_selecionados').on('click', function(){
						adicionarProduto($(this));
					});


					$(document).find('.verifica_produto_selecionado').off("click");
					$(document).find('.verifica_produto_selecionado').on('click', function(){
						tranferirProdutos($(this));
					});
				}, 1000);
            },
        });
	}

	var contador = {{$contador}};
	
	function adicionarTodosProdutos(){
		loader();
		if(table_campanha_busca_produtos.data().count() <= 0){
			message('Atenção','Nenhuma busca realizada.');
			return false;
		};

		form = $(document).find('#form_filter_produtos');
		form.find('.error-message').remove();	
		form.find(".error-input").removeClass('error-input');
		
		$(document).find('#enviar_todos_itens_busca').text("Enviando...");
		$(document).find('#enviar_todos_itens_busca').prop("disabled",true);
		$(document).find('#btn-filterform-modal').prop("disabled",true);
		$(document).find('#enviar_itens_selecionados').prop("disabled",true);
		$(document).find('#btn-clearform').prop("disabled",true);

		$.ajax({
			url: '{{route('campanha.buscar_produtos')}}',
			data: form.serialize(),
			type: 'POST',
			success: function(data){
				hide_loader();
				var temp_line;
				var lines = [];
				var verifica_adicionado = false;

				for (var field in data.response.retorno){
					var estabelecimento = data.response.retorno[field].estabelecimento.replaceAll(' ','');
					estabelecimento = estabelecimento.replaceAll('/','');

					var cod_produto_tratado = data.response.retorno[field].codigo.replaceAll('/','');
					var verifica_duplicidade_tabela_principal = table_produtos_enviar.rows($(document).find('.class_produto'+cod_produto_tratado+estabelecimento).parents('tr')).count();
					
					if(verifica_duplicidade_tabela_principal <= 0 && data.response.retorno[field].ativo == '' && !produtos_carregados_array.includes(data.response.retorno[field].codigo+"|"+estabelecimento) && !produtos_enviar_array.includes(data.response.retorno[field].codigo)){
						temp_line = [
							"<input type='hidden' class='class_produto"+cod_produto_tratado+"' id='"+cod_produto_tratado+"_"+contador+"' name='produto_selecionado["+contador+"]' value='"+data.response.retorno[field].codigo+"' >"+data.response.retorno[field].codigo,
							"<div><div data-toggle='tooltip' data-placement='left'  data-html='true' title='' data-original-title='" + data.response.retorno[field].descricao + "'>"+ data.response.retorno[field].descricao+"</div></div>",
							"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + data.response.retorno[field].grupo + "'>"+ data.response.retorno[field].grupo+"</div></div>",
							"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + data.response.retorno[field].subgrupo + "'>"+ data.response.retorno[field].subgrupo+"</div></div>",
							"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + data.response.retorno[field].marca + "'>"+ data.response.retorno[field].marca+"</div></div>",
							"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + data.response.retorno[field].linha + "'>"+ data.response.retorno[field].linha+"</div></div>",
							"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + data.response.retorno[field].segmento + "'>"+ data.response.retorno[field].segmento+"</div></div>",
							"<center><div class='text-success "  +  cod_produto_tratado +"'>ATIVO<a href='#' class= 'bt-edit-direita' onclick=removerProdutoTemporario('"+data.response.retorno[field].codigo+"')></a></div></center>",
						];

						lines.push(temp_line);
						produtos_enviar_array.push(data.response.retorno[field].codigo);
						contador ++;
						verifica_adicionado = true;
					}
				}

				table_produtos_enviar.rows.add(lines).draw();

				if(verifica_adicionado){
					message('Atenção','Os Produtos foram adicionados.');
					table_campanha_busca_produtos.clear().draw();
				}else{
					message('Atenção','Nenhum produto adicionado.');
					table_campanha_busca_produtos.clear().draw();
				}
					
				$(document).find('#enviar_todos_itens_busca').text("Enviar Todos os Itens da Busca");
				$(document).find('#enviar_todos_itens_busca').prop("disabled",false);
				$(document).find('#btn-filterform-modal').prop("disabled",false);
				$(document).find('#enviar_itens_selecionados').prop("disabled",false);
				$(document).find('#btn-clearform').prop("disabled",false);
			},
			error: function(data){
				$(document).find('#enviar_todos_itens_busca').text("Enviar Todos os Itens da Busca");
				$(document).find('#enviar_todos_itens_busca').prop("disabled",false);
				$(document).find('#btn-filterform-modal').prop("disabled",false);
				$(document).find('#enviar_itens_selecionados').prop("disabled",false);
				$(document).find('#btn-clearform').prop("disabled",false);

				hide_loader();
				var errors = data.responseJSON.errors;
				for(var field in errors){
					showErrorsInputs(form, field, errors[field])
				}
			}
		});

		hide_loader();
    }

	function tranferirProdutos($this){
        var duplicidade = $($this).data('duplicidade');
		var verifica = $(document).find("#"+duplicidade+'_check').is(":checked");

		if(verifica == true){
			var verifica_check = adicionarProdutoTemporario($this);

			if(verifica_check === false){
				$(document).find("#"+duplicidade+'_check').prop('checked', false);
			}
		}else{
			removerProdutoTemporario(duplicidade);
		}
	}

	function adicionarProduto(){
		if(table_produtos_hidden.data().count() <= 0){
			message('Atenção','Nenhum item selecionado ou já adicionado.');
			return false;
		}
		var data = table_produtos_hidden.rows().data();
		var verifica_adicionados = false;

		data.each(function (value, index) {
			temp_fields = [];
			array_inserir = [];
			array_row = [];

			for(var campos in value){
				temp_fields.push([
					value[campos]
				]);
			}

			for(var inserir in temp_fields){
				for(var individual in temp_fields[inserir]){
					array_inserir.push(
						temp_fields[inserir][individual]
					);
				}
			}

			array_row.push(array_inserir);

			var produto_item = array_row[0][0];
			var busca_codigo = "";
			var busca_estabelecimento = "";
			var continuar_busca = 0;
			var continuar_busca_estabelecimento = 0;
			var linha_estabelecimento = array_row[0][6];

			for (let letra of produto_item){
				if (letra === '>'){
					continuar_busca = 1;
				}

				if(continuar_busca > 0){
					busca_codigo = busca_codigo + letra;
				}
			}

			for (let letra_estabelecimento of linha_estabelecimento){
				if (letra_estabelecimento === '>'){
					continuar_busca_estabelecimento = 1;
				}

				if(continuar_busca_estabelecimento > 0){

					if(letra_estabelecimento != '<' && letra_estabelecimento != '>'){
						busca_estabelecimento = busca_estabelecimento + letra_estabelecimento;
					}

					if(letra_estabelecimento === '<'){
						continuar_busca_estabelecimento = 0;
					}
				}
			}
			
			busca_estabelecimento = busca_estabelecimento.replaceAll('<','').replaceAll('>','').replaceAll(' ','');
			produtos_enviar_array.push(busca_codigo.slice(1));

			busca_codigo = busca_codigo.slice(1).replaceAll('/','');

			var verifica_duplicidade_tabela_principal = table_produtos_enviar.rows($(document).find('.class_produto'+busca_codigo).parents('tr')).count();
			var confirma_duplicidade = false;
			
			if(verifica_duplicidade_tabela_principal > 0){
				confirma_duplicidade = true;
			}

			if(!confirma_duplicidade){
				table_produtos_enviar.rows.add(array_row).draw();
				verifica_adicionados = true;
			}

		});

		if(verifica_adicionados){
			message('Atenção','Os Produtos foram adicionados.');
			table_produtos_hidden.clear().draw();
			$(document).find('.verifica_produto_selecionado').prop('checked', false).removeAttr('checked');
		}else{
			message('Atenção','Nenhum item adicionado.');
			table_produtos_hidden.clear().draw();
			$(document).find('.verifica_produto_selecionado').prop('checked', false).removeAttr('checked');
		}
	}

	function removerProdutoTemporario(duplicidade){
		table_produtos_hidden.rows($(document).find('.class_produto'+duplicidade).parents('tr')).remove().draw();
		table_produtos_enviar.rows($(document).find('.class_produto'+duplicidade).parents('tr')).remove().draw();

		produtos_enviar_array = removerProdutoArrayEnviar(produtos_enviar_array,duplicidade);
		produtos_carregados_array = removerProdutoArrayEnviar(produtos_carregados_array,duplicidade);
	}

	function removerProdutoCampanha(duplicidade,codigo,campanha,campanha_id,mensagem_campanha_ativo,mensagem_campanha_inativo){
		
		var $class = "dialog_option_deletar";
		var $name_option_sim = "aprovar_nova_busca_sim";
		var $option_sim = "Sim";
		var $name_option_nao = "aprovar_nova_busca_nao";
		var $option_nao = "Não";
		message_sim_nao_campanha("Atenção Confirma", mensagem_campanha_inativo, $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao,400,280);
		
		$(document).off("aprovar_nova_busca_nao");
		$(document).on("aprovar_nova_busca_nao", function(){
			return false;
		});

		$(document).off("aprovar_nova_busca_sim");
		$(document).on("aprovar_nova_busca_sim", function(){
			var produto_busca = codigo.replaceAll(' ','');

			removerProdutoCampanhaPermanente(codigo,campanha,campanha_id,mensagem_campanha_ativo,mensagem_campanha_inativo);

			
		});
	}
	function adicionarProdutoTemporario($this){
        var marca = $($this).data("marca");
        var linha = $($this).data("linha");
        var grupo = $($this).data('grupo');
        var codigo = $($this).data('codigo');
		var segmento = $($this).data('segmento');
        var subgrupo = $($this).data('subgrupo');
        var descricao = $($this).data('descricao');
        var campanha_id = $($this).data('campanha_id');
        var duplicidade = $($this).data('duplicidade');
        var campanha_nome = $($this).data('campanha_nome');
        var estabelecimento = $($this).data('estabelecimento');
        var duplicidade_array = $($this).data('duplicidade_array');
		var verificar_duplicidade = $(document).find('.class_produto'+duplicidade).val();

		if(verificar_duplicidade || produtos_enviar_array.includes(duplicidade_array) || produtos_carregados_array.includes(duplicidade_array)){
			message("Atenção","O Produto "+codigo+" já está adicionado.");
			return false;
		}
		
		temp_fields = [];
		temp_fields.push([
			"<input type='hidden' class='class_produto"+duplicidade+"' id='"+codigo+"_"+contador+"' name='produto_selecionado["+contador+"]' value='"+codigo+"' >"+codigo,
			"<div><div data-toggle='tooltip' data-placement='left'  data-html='true' title='' data-original-title='" + descricao + "''>"+ descricao+"</div></div>",
			"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + grupo + "''>"+ grupo+"</div></div>",
			"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + subgrupo + "''>"+ subgrupo+"</div></div>",
			"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + marca + "''>"+ marca+"</div></div>",
			"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + linha + "''>"+ linha+"</div></div>",
			"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + segmento + "''>"+ segmento+"</div></div>",
			"<center><div class='text-success "  +  codigo +"'>ATIVO<a href='#' class= 'bt-edit-direita' onclick=removerProdutoTemporario('"+codigo+"')></a></div></center>",
			
		]);

		table_produtos_hidden.rows.add(temp_fields).draw();

		contador ++;
	}

	function atualizarCampanha(){
		var form = $(document).find('#campanha_form');
		$(document).find('.error-message').remove();
		
		var form_data = new FormData(form[0]);
		form_data.append('estabelecimentos_selecionados',estabelecimentos_selecionados);
		form_data.append('produto_selecionado',produtos_enviar_array);

		$.ajax({
			url: '{{ route('campanha.editar')}}',
			dataType: 'json',
			data: form_data,
			processData: false,
			contentType: false,
			method: 'POST',
			success: function(data){
				if(data.status == 'success'){
					$(document).find('#modal_campanha_editar').modal('hide');
					message('Atenção','<div class="text-success">Campanha Alterada com sucesso.</div>');
				}else{
					message('Atenção','<div class="text-danger">Ocorreu um erro do Gravar a campanha, por favor contate o setor responsável.</div>');
				}
			},
			error: function(callback){
            	hide_loader();
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                	var errors = callback.responseJSON.error;

                    $.each(data, function(index, el) {
						if(el === 'Nenhum produto foi adicionado a campanha.'){
							message('Atenção','<div class="text-danger">Nenhum produto foi adicionado a campanha.</div>');
						}
                        form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                        form.find('input[name="'+index+'"]').eq(0).addClass('error');

						form.find('select[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                        form.find('select[name="'+index+'"]').eq(0).addClass('error');
                    });

                    form.find('input.error').eq(0).focus();

					for(var field in errors){
						showErrorsInputsModalNovo(form, field, errors[field]);
					}

					message('Atenção','<div class="text-danger">Existem pendências a serem preenchidas ou corrigidas.</div>');
                }
            }}
		).always(function() {
            hide_loader();
        });
	}

	function showErrorsInputsModalNovo(form, input, message){
        var inputexplode = input.split(".");
		if(inputexplode.length > 1){
			input = inputexplode[0]+"["+inputexplode[1]+"]";
			message = message;
			var $input = $(document).find("input[name='"+input+"']");
			$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
		}
    }

	function removerProdutoCampanhaPermanente(codigo,campanha,campanha_id,mensagem_campanha_ativo,mensagem_campanha_inativo){
		
        xhr = $.ajax({
            url: '{{ Route('campanha.remover_produto') }}',
            data: {_token: "{{ csrf_token() }}", codigo: codigo,  campanha: campanha, campanha_id: campanha_id},
            method: 'POST',
            success: function(body){
                if(body.status == 'success'){
				
					$(document).find('.'+codigo).removeClass('text-success').addClass('text-danger').html('').html("INATIVO<a href='#' class='bt-edit-direita'  onclick=\"situacaoProdutoCampanha('" + codigo +"','" + codigo + "','"  +campanha + "','" +campanha_id  +  "','"  + mensagem_campanha_ativo +   "','"  + mensagem_campanha_inativo + "')\"></a>");

					message('Atenção','Produto Desativado com sucesso.');
                }else{
                    message('Atenção','Algo ocorreu de errado na exclusão, por favor contate o setor responsável.');
                }
            },
            error: function(data){
                message('Atenção','Algo ocorreu de errado na exclusão, por favor contate o setor responsável.');
            }
        });
    }

	function removerProdutoArrayEnviar(array, value) {
		return array.filter(function(ele){ 
			return ele != value; 

		});
	}

	function situacaoProdutoCampanha(duplicidade,codigo,campanha,campanha_id,mensagem_campanha_ativo,mensagem_campanha_inativo){
		var situacao_texto = $(document).find('.'+codigo).text();

		if(situacao_texto == 'ATIVO'){
			removerProdutoCampanha(duplicidade,codigo,campanha,campanha_id,mensagem_campanha_ativo,mensagem_campanha_inativo);
		}else{
			ativarProdutoCampanha(duplicidade,codigo,campanha,campanha_id,mensagem_campanha_ativo,mensagem_campanha_inativo);
		}
	}

	function ativarProdutoCampanha(dublicidade,codigo,campanha,campanha_id,mensagem_campanha_ativo,mensagem_campanha_inativo){
		var $class = "dialog_option_deletar";
		var $name_option_sim = "aprovar_nova_busca_sim";
		var $option_sim = "Sim";
		var $name_option_nao = "aprovar_nova_busca_nao";
		var $option_nao = "Não";
		message_sim_nao_campanha("Atenção Confirma",mensagem_campanha_ativo, $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao,400,280);
		
		$(document).off("aprovar_nova_busca_nao");
		$(document).on("aprovar_nova_busca_nao", function(){
			return false;
		});

		$(document).off("aprovar_nova_busca_sim");
		$(document).on("aprovar_nova_busca_sim", function(){
			var produto_busca = codigo.replaceAll(' ','');

			produtos_enviar_array = removerProdutoArrayEnviar(produtos_enviar_array,produto_busca);
			produtos_carregados_array = removerProdutoArrayEnviar(produtos_carregados_array,produto_busca);
			ativarProdutoCampanhaPermanente(codigo,campanha,campanha_id,mensagem_campanha_ativo,mensagem_campanha_inativo);

			
		});
	}
	function ativarProdutoCampanhaPermanente(codigo,campanha,campanha_id,mensagem_campanha_ativo,mensagem_campanha_inativo){
        xhr = $.ajax({
            url: '{{ Route('campanha.ativar_produto') }}',
            data: {_token: "{{ csrf_token() }}", codigo: codigo,  campanha: campanha, campanha_id: campanha_id},
            method: 'POST',
            success: function(body){
                if(body.status == 'success'){
					
					$(document).find('.'+codigo).removeClass('text-danger').addClass('text-success').html('').html("ATIVO<a href='#' class='bt-edit-direita'  onclick=\"situacaoProdutoCampanha('" + codigo +"','" + codigo + "','"  +campanha + "','" +campanha_id + "','"  + mensagem_campanha_ativo + "','"  + mensagem_campanha_inativo + "')\"></a>");
					message('Atenção','Produto Ativado com sucesso.');
                }else{
                    message('Atenção','Algo ocorreu de errado ao ativar, por favor contate o setor responsável.');
                }
            },
            error: function(data){
                message('Atenção','Algo ocorreu de errado ao ativar, por favor contate o setor responsável.');
            }
        });
    }

	function importarArquivo(){

		var formdata = new FormData();

		formdata.append('_token', '{{ csrf_token() }}');
		formdata.append('campanha_id', '{{ $retorno['id'] }}');
		formdata.append('arquivo', $(document).find('#importar-csv').prop('files')[0], $(document).find('#importar-csv').val());

		$.ajax({
			url: '{{ route('campanha.importar_produtos') }}',
			type: 'POST',
			data: formdata,
			processData: false,
			contentType: false,
		})
		.done( function (data){
			
			var temp_line;
			var lines = [];
			var verifica_adicionado = false;
			var contador_adicionado = 0;

			for (var field in data.response.retorno){
				var estabelecimento = data.response.retorno[field].estabelecimento.replaceAll(' ','');
				estabelecimento = estabelecimento.replaceAll('/','');

				var cod_produto_tratado = data.response.retorno[field].codigo.replaceAll('/','').replaceAll(' ','');
				var verifica_duplicidade_tabela_principal = table_produtos_enviar.rows($(document).find('.class_produto'+cod_produto_tratado).parents('tr')).count();
				
				if(!produtos_enviar_array.includes(data.response.retorno[field].codigo) && !produtos_carregados_array.includes(data.response.retorno[field].codigo)){
					temp_line = [
						"<input type='hidden' class='class_produto"+cod_produto_tratado+"' id='"+cod_produto_tratado+"_"+contador+"' name='produto_selecionado["+contador+"]' value='"+data.response.retorno[field].codigo+"' >"+data.response.retorno[field].codigo,
						"<div><div data-toggle='tooltip' data-placement='left'  data-html='true' title='' data-original-title='" + data.response.retorno[field].descricao + "'>"+ data.response.retorno[field].descricao+"</div></div>",
						"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + data.response.retorno[field].grupo + "'>"+ data.response.retorno[field].grupo+"</div></div>",
						"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + data.response.retorno[field].subgrupo + "'>"+ data.response.retorno[field].subgrupo+"</div></div>",
						"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + data.response.retorno[field].marca + "'>"+ data.response.retorno[field].marca+"</div></div>",
						"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + data.response.retorno[field].linha + "'>"+ data.response.retorno[field].linha+"</div></div>",
						"<div><div data-toggle='tooltip' data-placement='left' data-html='true' title='' data-original-title='" + data.response.retorno[field].segmento + "'>"+ data.response.retorno[field].segmento+"</div></div>",
						"<center><div class='text-success "  +  cod_produto_tratado +"'>ATIVO<a href='#' class= 'bt-edit-direita' onclick=removerProdutoTemporario('"+data.response.retorno[field].codigo+"')></a></div></center>",
					];
					
					lines.push(temp_line);
					produtos_enviar_array.push(data.response.retorno[field].codigo);
					contador ++;
					contador_adicionado ++;
					verifica_adicionado = true;
				}
			}

			table_produtos_enviar.rows.add(lines).draw();

			var produtos_desativados = '';

			if(jQuery.isEmptyObject(data.response.produtos_desativados) !== true){
				produtos_desativados = '<br> Os seguintes produtos estão desativados na campanha:<br>'+data.response.produtos_desativados;
			}

			if(verifica_adicionado){
				message('Atenção',contador_adicionado+' Produtos foram importados para serem adicionados.' + produtos_desativados);
			}else{
				message('Atenção','Nenhum produto adicionado.' + produtos_desativados);
			}
		})
		.fail( function(data){
			$(document).find('#btn-importar-csv').val('');
			var erros = (data.responseJSON.errors);
			var mensagem_erro = '';
			for(var field in erros){
				mensagem_erro += erros[field] + "<br>";
			}
			message('Erro', mensagem_erro);
		});

	}

	function isEmptyObject(obj){
		return JSON.stringify(obj) === '{}'
	}
</script>
@endsection
