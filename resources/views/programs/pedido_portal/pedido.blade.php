@extends('layouts.page-dialog')
@section('content')
@if (!empty($pedido['motivo_rejeicao']))
    @if($pedido['status_pedido_id'] === 20)
        <div class="alert alert-warning" role="alert">
            <p>Este pedido precisa ser revisado .<br>
            Motivo: {{$pedido['motivo_rejeicao']}}</p> 
            @if($pedido['revisar_mensagem'])
                Observação: {{$pedido['revisar_mensagem']}}</p> 
            @endif
        </div>
    @else
        <div class="alert alert-danger" role="alert">
            <p>Este pedido foi rejeitado.<br>
            Motivo: {{$pedido['motivo_rejeicao']}}</p> 
        </div>
    @endif
@endif
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link active" id='pedido-web-header-tab' data-toggle="tab" href="#pedido_web_header" role="tab" aria-controls="pedido_web_header" aria-selected="true">Dados do pedido</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" id="pedido-web-itens-tab" data-toggle="tab" href="#pedido_web_itens" role="tab" aria-controls="pedido_web_itens" aria-selected="false">Itens do pedido</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" id="pedido-web-credito-tab" data-toggle="tab" href="#pedido_web_credito" role="tab" aria-controls="pedido_web_credito" aria-selected="false">Crédito</a>
	</li>
</ul>
<div class="tab-content pt-3" id="PedidoHeaderContainer">
	<div class="tab-pane show active" id="pedido_web_header" role="tabpanel" aria-labelledby="dados-tab">
		<form action="{{ route("pedido_portal.salvar") }}" method="post" id="cadPedido" name="cadpedido" class="cadPedido" onsubmit="return false">
		    @csrf
			{{ Form::hidden('id', $pedido['id'], ['id' => 'id'])}}
			{{ Form::hidden('id_cliente_encriptada', $pedido['id_cliente_encriptada'], ['id' => 'id_cliente_encriptada'])}}
			{{ Form::hidden('check_cliente_balcao', $pedido['cliente_balcao'], ['id' => 'check_cliente_balcao'])}}
			{{ Form::hidden('cartao', $pedido['cartao'], ['id' => 'cartao']) }}
			{{ Form::hidden('presencial', $pedido['presencial'], ['id' => 'presencial']) }}
			{{ Form::hidden('cliente_sem_telefone', $pedido['cliente_sem_telefone'], ['id' => 'cliente_sem_telefone']) }}
			<div class="form-row">
				<div class="form-group col-sm-6 col-lg-3 content_estabelecimento">
					{{ Form::label('estabelecimento', 'Estabelecimento', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                    @if(empty($pedido['estabelecimento'])):
                    {{ Form::select('estabelecimento', $pedido['lista_estabelecimentos'], $pedido['estabelecimento'], array('class' => 'form-control essencial')) }}
                    @else:
                    {{ Form::hidden('estabelecimento', $pedido['estabelecimento'], []) }}
                    {{ Form::text('estabelecimento_view', $pedido['lista_estabelecimentos'][$pedido['estabelecimento']], array('id'=> 'estabelecimento_view', 'class' => 'form-control', 'disabled' => 'disabled')) }}
                    @endif
				</div>
				<div class="form-group col-sm-6">
					{{ Form::label('nome_cliente', 'Cliente') }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
					<div class="input-group" id="cod_cliente_group">
						{{ Form::text('nome_cliente', $pedido['cliente_descricao'], ['id' => 'nome_cliente', 'class' => 'form-control essencial input-label', 'placeholder' => '']) }}
						{{ Form::hidden('codigo_cliente', $pedido['cliente_codigo'], ['id' => 'codigo_cliente', 'class' => '']) }}
						<span class="input-group-addon border rounded-right" id="bt-search-cliente" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
					</div>
				</div>
				<div class="form-group col-md-6 col-lg-3 content-not-estabel cliente_not_balcao">
                    {{ Form::label('tipo_venda', "Tipo de venda", []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span><br>
                    @if(empty($data_previsao_entrega_original))
                    <select id="tipo_venda" class="form-control" name="tipo_venda">
                    @else
                    <select id="tipo_venda" class="form-control" name="tipo_venda">
                    @endif
                        @foreach ($pedido['tipo_venda_lista'] as $key => $value)
                        <option value="{{ $key }}" @if( $pedido['tipo_venda'] === $key) selected="selected" @endif >{{ $value }}</option>
                        @endforeach
                        @foreach ($pedido['condicao_especial'] as $key => $value)
                        <option value="{{ $key }}" @if( $pedido['tipo_venda'] === $key) selected="selected" @endif class="condicao_especial">{{ $value }}</option>
                        @endforeach
                    </select>
				</div>
				<div class="form-group col-sm-12" id="cliente_conta_e_ordem_div">
					{{ Form::label('nome_cliente_conta_e_ordem', 'Cliente da conta e ordem') }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
					<div class="input-group" id="cod_cliente_conta_e_ordem_group">
						{{ Form::text('nome_cliente_conta_e_ordem', $pedido['cliente_conta_e_ordem_descricao'], ['id' => 'nome_cliente_conta_e_ordem', 'class' => 'form-control input-label', 'placeholder' => '']) }}
						{{ Form::hidden('codigo_cliente_conta_e_ordem', $pedido['cliente_conta_e_ordem_codigo'], ['id' => 'codigo_cliente_conta_e_ordem', 'class' => '']) }}
						<span class="input-group-addon border rounded-right" id="bt-search-cliente-conta-e-ordem" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
					</div>
				</div>
				<div class="form-group col-sm-6 content-not-estabel cliente_not_balcao">
					{{ Form::label('data_previsao_entrega', "Previsão de entrega / Data para Liberação", []) }}  <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio d-none' id='previsao_entrega_obrigatorio'>*</span>
					@if(empty($data_previsao_entrega_ultima))
                        {{ Form::text('data_previsao_entrega', $pedido['data_previsao_entrega'], ['id' => 'data_previsao_entrega', 'class' => 'form-control']) }}
                    @else
                        {{ Form::text('data_previsao_entrega', $pedido['data_previsao_entrega'], ['id' => 'data_previsao_entrega', 'class' => 'form-control', 'disabled']) }}
                    @endif
				</div>
				<div class="form-group col-sm-12 col-lg-6 content-not-estabel cliente_balcao condicao_pagamento">
					{{ Form::label('condicao_pagamento_descr', 'Condição de pagamento', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
					<div class="input-group cliente_not_balcao" id="condicao_pagamento_group">
						{{ Form::text('condicao_pagamento_descr', $pedido['condicao_pagamento_descricao'], array('id' => 'condicao_pagamento_descr', 'class' => 'form-control essencial')) }}
						{{ Form::hidden('condicao_pagamento', $pedido['condicao_pagamento_codigo'], ['id' => 'condicao_pagamento', 'class' => ''])}}
						<span class="input-group-addon border rounded-right" id="bt-search-condicao_pagamento" data-route="{{ route("condicoes_pagamento_web.dialog") }}"><i id="bt-view-condicao" class="bt-view m-2"></i></span>
					</div>
					<div class="input-group cliente_balcao" id="condicao_pagamento">
                        {{ Form::select('condicao_pagamento', $pedido['condicao_pagamento_balcao'], $pedido['condicao_pagamento_codigo'], ['class' => 'form-control essencial']) }}
					</div>
					<div class="input-group cliente_balcao" id="condicao_pagamento_blacklist">
                        {{ Form::select('condicao_pagamento', $pedido['condicao_pagamento_blacklist'], $pedido['condicao_pagamento_codigo'], ['class' => 'form-control essencial']) }}
					</div>
				</div>
			</div>
			<div class="form-row content-not-estabel cliente_not_balcao">
				<div class="form-group col-sm-6">
					{{ Form::label('transportadora_nome', 'Transportadora', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
					<div class="input-group" id="transporadora_group">
						{{ Form::text('transportadora_nome', $pedido['transportadora_descricao'], ['id' => 'transportadora_nome', 'class' => 'form-control essencial input-label']) }}
						{{ Form::hidden('transportadora', $pedido['transportadora_codigo'], ['id' => 'transportadora', 'class' => 'form-control']) }}
						<span class="input-group-addon border rounded-right" id="bt-search-transportadora" data-route="{{ route("transportadora_estabelecimento.modal.dialog") }}"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
					</div>
				</div>
				<div class="form-group col-sm-2">
					{{ Form::label('transportadora_tipo_frete', 'Tipo de frete', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
					{{ Form::select('transportadora_tipo_frete', $tipo_frete, $pedido['tipo_frete'], ['id' => 'transportadora_tipo_frete', 'class' => 'form-control essencial', 'onChange' => 'checkCamposTransportadora()']) }}
				</div>
				<div class="form-group col-sm-2">
					{{ Form::label('preco_cif_fob_exibir', 'Preço', []) }}
					{{ Form::text('preco_cif_fob_exibir', $pedido['preco_cif_fob'], ['id' => 'preco_cif_fob_exibir', 'class' => 'form-control', 'disabled' => true]) }}
				</div>
		    	<div class="form-group col-sm-2">
					{{ Form::label('valor_frete', 'Valor do Frete', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
			        {{ Form::text('valor_frete', $pedido['valor_frete'], ['id' => 'valor_frete', 'class' => 'form-control text-right']) }}
		    	</div>
			</div>
			<div class="form-row cliente_not_balcao">
				<div class="form-group hide-on-start-redespacho col-sm-8">
					{{ Form::label('transportadora_redespacho_nome', 'Transportadora Redespacho', []) }}
					<div class="input-group" id="cod_cliente_group">
			        	{{ Form::text('transportadora_redespacho_nome', $pedido['transportadora_redespacho_descricao'], ['id' => 'transportadora_redespacho_nome', 'class' => 'form-control input-label']) }}
						{{ Form::hidden('transportadora_redespacho', $pedido['transportadora_redespacho_codigo'], ['id' => 'transportadora_redespacho', 'class' => 'form-control']) }}
						<span class="input-group-addon border rounded-right" id="bt-search-transportadora_redespacho" data-route="{{ route("transportador.index.dialog") }}"><i id="bt-view-transportadora-redespacho" class="bt-view m-2"></i></span>
		    		</div>
		    	</div>
		    	<div class="form-group hide-on-start-redespacho col-sm-2">
					{{ Form::label('transportadora_redespacho_tipo_frete', 'Tipo de frete', []) }}
			        {{ Form::select('transportadora_redespacho_tipo_frete', $tipo_frete, $pedido['tipo_frete_redespacho'], ['id' => 'transportadora_redespacho_tipo_frete', 'class' => 'form-control']) }}
		    	</div>
		    	<div class="form-group col-sm-2">
					{{ Form::label('valor_frete_redespacho', 'Valor do Frete', []) }}
			        {{ Form::text('valor_frete_redespacho', $pedido['valor_frete_redespacho'], ['id' => 'valor_frete_redespacho', 'class' => 'form-control text-right']) }}
		    	</div>
		    </div>
		    <div class="form-row">
		        <div class="form-group col-sm-4">
                    @if($pedido['cliente_balcao'] == true)
					{{ Form::label('nome_contato', 'Nome do contato', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                    @else
                    {{ Form::label('nome_contato', 'Nome do cliente', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                    @endif
			        {{ Form::text('nome_contato', $pedido['nome_comprador'], ['id' => 'nome_contato', 'class' => 'form-control', 'maxlength' => '50']) }}
		    	</div>
		    	<div class="form-group col-sm-6 col-lg-3">
					{{ Form::label('email_contato', 'Email do contato', []) }}
			        {{ Form::text('email_contato', $pedido['email_comprador'], ['id' => 'email_contato', 'class' => 'form-control', 'maxlength' => '50']) }}
		    	</div>
		    	<div class="form-group col-sm-12 col-lg-3">
                    @if($pedido['cliente_balcao'] == true)
                    {{ Form::label('no_pedido_compra', 'CPF', []) }}
                    @else
                    {{ Form::label('no_pedido_compra', 'Pedido compra', []) }}
                    @endif
			        {{ Form::text('no_pedido_compra', $pedido['no_pedido_compra'], ['id' => 'no_pedido_compra', 'class' => 'form-control', 'maxlength' => '50']) }}
		    	</div>
		    	<div class="form-group col-sm-12 col-lg-2">
					{{ Form::label('cliente_telefone', 'Telefone cliente', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class="campo_obrigatorio cliente_telefone">*</span>
			        {{ Form::text('cliente_telefone', $pedido['cliente_telefone'], ['id' => 'cliente_telefone', 'class' => 'form-control']) }}
		    	</div>
		    </div>
		    <div class="form-row hide-on-start">
				<div class="col-sm-2 cliente_balcao">
					<div class="form-check">
						{!! Form::checkbox('enfestar', 'true', $pedido['enfestar'], ['id' => 'enfestar', 'class' => 'form-check-input']) !!}
						{!! Form::label('enfestar', 'Enfestar produto', ['class' => 'form-check-label']) !!}
					</div>
				</div>
				<div class="col-sm-2 venda_observacao">
					<div class="form-check">
					{!! Form::checkbox('bater_amostra', 'true', $pedido['bater_amostra'], ['id' => 'bater_amostra', 'class' => 'form-check-input']) !!}
					{!! Form::label('bater_amostra', 'Bater Amostra', ['class' => 'form-check-label']) !!}
					</div>
				</div>
				<div class="col-sm-2 venda_observacao">
					<div class="form-check">
						{!! Form::checkbox('incluir_cartelas', 'true', $pedido['incluir_cartelas'], ['id' => 'incluir_cartelas', 'class' => 'form-check-input']) !!}
						{!! Form::label('incluir_cartelas', 'Incluir Cartela', ['class' => 'form-check-label']) !!}
					</div>
				</div>
				@if(empty($pedido['estabelecimento']) || $pedido['estabelecimento'] != '3')
				<div class="col-sm-2 metragem_exata">
					<div class="form-check">
						{!! Form::checkbox('metragem_exata', 'true', $pedido['metragem_exata'], ['id' => 'metragem_exata', 'class' => 'form-check-input']) !!}
						{!! Form::label('metragem_exata', 'Metragem Exata', ['class' => 'form-check-label']) !!}
					</div>
				</div>
				@endif
		    </div>
		    <div class="form-row hide-on-start">
				<div class="col-sm-2 transportadora_retira">
					{{ Form::label('', 'Retirada:', []) }}
					<div class="form-check">
						{{ Form::radio('transportadora_retira_imediato', 'true', $pedido['transportadora_retira_imediato'], ['class' => 'form-check-input', 'id'=>'transportadora_retira_imediato']) }}
						{{ Form::label('transportadora_retira_imediato', 'Imediata', ['class'=>'form-check-label']) }}
					</div>
					<div class="form-check">
						{{ Form::radio('transportadora_retira_imediato', 'false', $pedido['transportadora_retira_imediato'], ['class' => 'form-check-input', 'id'=>'transportadora_retira_imediato_false']) }}
						{{ Form::label('transportadora_retira_imediato_false', 'Agendada', ['class'=>'form-check-label']) }}
					</div>
				</div>
				<div class="col-sm-2 transportadora_retira_horario">
					{{ Form::label('transportadora_retira_horario', "Horario de retirada", []) }} 
					{{ Form::text('transportadora_retira_horario', $pedido['transportadora_retira_horario'], ['id' => 'transportadora_retira_horario', 'class' => 'form-control horario']) }}
				</div>
		    </div>
			{{ Form::hidden('observacao', $pedido['observacao'], ['id' => 'observacao'])}}
		    <div class="row">
				<div class="col-sm-12">
					<button type="button" id="bt_ir_para_produtos" class="btn btn-info troca-aba float-right">Ir para produtos >></button>
				</div>
			</div>
		</form>
	</div>
	<div class="tab-pane" id="pedido_web_itens" role="tabpanel" aria-labelledby="dados-tab">
		<div class="content-filter-dialog">	
	    	<form action="post" name="form_filter_pedidos_itens" class="cadPedido" id="form_filter_itens" onsubmit="return false;">
	    		<p><strong>Inserir produtos</strong>
                    @if(Auth::user()->tipo_usuario->nome != 'Representante')
                    <label class="btn btn-upload" data-toggle="tooltip" data-placement="top" title="Importar arquivo">
                        <input type="file" id='importar-csv' style='display:none;'>
                        <a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="top" title="" data-original-title="O arquivo deve conter somente o Código de Barra do Produto, quantidade é pela repetição do Código de Barra." style="color: black;"></a>
                    </label>
                    @endif
                </p>
		    	<div class="content-fields">
					<div class="form-row mb-0">
						<div class="col-sm-2">
							<strong><span id="estabelecimento-exibicao" class='ml-2'>{{ !empty($pedido['estabelecimento']) ? $pedido['lista_estabelecimentos'][intval($pedido['estabelecimento'])] : '' }}</span></strong>
						</div>
						<div class="col-sm-1">
							<strong><span id="venda-futura-exibicao">{{ $pedido['pedido_futuro']?'Venda futura - entrega: ' . ($pedido['data_previsao_entrega']??'') : 'Pronta entrega' }}</span></strong>
						</div>
						<div class="col-sm-2">
                            <strong>
							<span id="cliente_pedido">{{ $pedido['cliente_pedido']??'' }}</span></strong>
						</div>
						<div class="col-sm-1">
							<strong>UF: </strong>
							<span id="estado_destino">{{ $pedido['estado_destino']??'' }}</span>
                        </div>
						<div class="col-sm-1">
							<strong>Prazo médio: </strong>
							<span id="media_condicao_pagamento">{{ $pedido['media_condicao_pagamento']??'' }}</span>
                        </div>
                        <div class="col-sm-1">
                            <strong>Preço: </strong>
                            <span id="preco_cif_fob">{{ $pedido['preco_cif_fob'] ?? '' }}</span>
                        </div>
                        <div class="col-sm-1">
                            <strong>Frete: </strong>
                            <span id="cif_fob">{{ $pedido['cif_fob'] ?? '' }}</span>
						</div>
                        <div class="col-sm-2">
							<strong><span id="texto_metragem_exata">@if($pedido['metragem_exata'] == true)Metragem Exata (+10%) @endif</span></strong>
						</div>
                        <div class="col-sm-1">
							<strong><span id="texto_rj_x_sp">@if($pedido['rj_x_sp'] == true)Operação RJ X SP @endif</span></strong>
						</div>
					</div>
					<div class="form-row text-center mb-0 border-bottom display_none" id="content_desenhos">
                        <div class="form-group col-sm-2">
                            {{ Form::label('produto_codigo_base', 'Código do produto base', []) }}
                            <div class="input-group" id="cod_produto_group_base">
                                {{ Form::text('produto_codigo_base', '', ['id' => 'produto_codigo_base', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Código', "maxlength" => "250"]) }}
                                <span class="input-group-addon border rounded-right" id="bt-search-produto_base"><i class="bt-view m-2"></i></span>
                            </div>
                        </div>
                        <div class="form-group col-sm-4 text-center">
                            {{ Form::label('produto_descricao_base', 'Descrição do produto base', []) }}
                            {{ Form::text('produto_descricao_base', '', ['id' => 'produto_descricao_base', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Nome do produto']) }}                            
                        </div>
                        <div class="form-group col-sm-2">
                            {{ Form::label('produto_codigo_desenho', 'Código do desenho', []) }}
                            <div class="input-group" id="cod_produto_group_desenho">
                                {{ Form::text('produto_codigo_desenho', '', ['id' => 'produto_codigo_desenho', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Código do desenho', "maxlength" => "250"]) }}
                                <span class="input-group-addon border rounded-right" id="bt-search-produto_desenho"><i class="bt-view m-2"></i></span>
                            </div>
                        </div>
                        <div class="form-group col-sm-4 text-center">
                            {{ Form::label('produto_descricao_desenho', 'Descrição do desenho', []) }}
                            {{ Form::text('produto_descricao_desenho', '', ['id' => 'produto_descricao_desenho', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Descrição do desenho']) }}                            
                        </div>
					</div>
                    <div class="form-row text-center mb-0">
						{{ Form::hidden('id', '', ['id' => 'produto_pedido_id'])}}
						{{ Form::hidden('produto-codigo-salvo', '', ['id' => 'produto-codigo-salvo'])}}
                        {{ Form::hidden('produto_codigo', '', ['id' => 'produto_codigo']) }}
                        {{ Form::hidden('pecas_tamanho', '', ['id' => 'pecas_tamanho']) }}       
                        <div class="form-group col-sm-5 text-center">
                            {{ Form::label('produto_descricao', 'Descrição', []) }}
			    			<div class="input-group" id="cod_produto_group">
                                {{ Form::text('produto_descricao', '', ['id' => 'produto_descricao', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Nome do produto']) }}       
                                <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
							</div>                     
                        </div>
                        <div id='ultimas-vendas-div' class='text-center'>
                            <br>
                            <button type="button" class='btn btn-light btn-sm border border-dark' title='Últimas vendas do cliente' id="btn-ultimas-vendas"><i class="bt-invoice-dollar" style='width:21px;height:21px;margin-top:4px;'></i></button>
                        </div>
				    	<div class="form-group col-sm text-center">
							{{ Form::label('quantidade', 'Quantidade', []) }}
				        	{{ Form::text('quantidade', '', ['id' => 'quantidade', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Quantidade', 'maxlength' => '9']) }}
				    	</div>
						<div class="form-group col-sm text-center">
							{{ Form::label('preco_unitario', 'Preço', []) }}
                            {{ Form::text('preco_unitario', '', ['id' => 'preco_unitario', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Valor unitário', 'maxlength' => '8']) }}
							{{ Form::label('preco_unitario', '* ', ['id' => 'alerta_preco_pedido', 'style' => 'display:inline-block']) }}
				    	</div>
						<div class="form-group col-sm text-center">
							{{ Form::label('estoque_disponivel', 'Estoque', []) }}
				        	{{ Form::text('estoque_disponivel', '', ['id' => 'estoque_disponivel', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Estoque', 'disabled' => 'disabled']) }}
				    	</div>
						<div class="form-group col-sm-1 text-center">
                        <span id="campanha"></span>
                        {{ Form::label('coluna', '%', []) }}
                        {{ Form::text('coluna', '', ['id' => 'coluna', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => '%', 'disabled' => 'disabled']) }}
                        </div>
                        {{ Form::hidden('preco_base_antes', '', ['id' => 'preco_base_antes' ]) }}
                        {{ Form::hidden('preco_antes', '', ['id' => 'preco_antes' ]) }}
                        {{ Form::hidden('comissao_antes', '', ['id' => 'comissao_antes' ]) }}
					</div>
                </div>
                <div class="content-buttons">
					<div class="content-total-pedido">
						<b>Itens:</b> <span id="total_itens">{{ $pedido['total_itens'] }}</span>
						<b class='ml-5'>Vl. Bruto:</b> <span id="total_produtos">{{ $pedido['valor_total_produtos'] }}</span>
						<b class='ml-5'>Vl. Frete:</b> <span id="total_frete">{{ $pedido['valor_total_frete'] }}</span>
						<b class='ml-5'>Vl. Líquido:</b> <span id="total_pedido" data-toggle='popover' data-placement='right' data-html='true' title='' data-content=''>{{ $pedido['valor_total_pedido'] }}</span>
						<b class='ml-5'>Peso:</b> <span id="peso_total" data-toggle='popover' data-placement='right' data-html='true' title='' data-content=''>{{ $pedido['peso_total'] }}</span>
                    </div>
			        <button name="btn-create" id="btn-create-itens-pedido" class="btn-create">Inserir produto</button>
                    <button name="btn-cancel" id="btn-cancel-itens-pedido" class="btn-cancel">Cancelar</button>
                </div>
            </form>
            <div class="content_pecas">
                <div class="content-dialog-table">
                    <div class="content-table">
                        <table class="table table-striped" id="table_pecas_pedido">
                            <thead>
                                <tr>
                                    <th class="tb_number">Peça</th>
                                    <th class="tb_number">Quantidade</th>
                                    <th class="tb_number">Local de estoque</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
		</div>
		<div class="content-dialog-table">
			<div class="content-table">
				<table class="table table-striped table-filter-pedido-itens" id="table-filters-pedidos-itens">
			        <thead>
			            <tr>
			                <th class="td_codigo_produto">Código</th>
			                <th>Descrição</th>
			                <th class="tb_number td_quantidade">Quantidade</th>
			                <th class="tb_number td_preco">Preço unitário</th>
			                <th class="tb_number td_total">Valor total</th>
			                <th class="tb_number td_comissao">%</th>
			                <th class="td_acao"></th>
			                <th class="td_acao"></th>
			            </tr>
			        </thead>
			        <tbody>
						@if(!empty($pedido['itens']))
                            @foreach ($pedido['itens'] as $item)
                            <tr>
                                @if(!empty($item['campanha_nome']))
                                    <td><b class="codigo">{{ $item['codigo'] }}</b></td>
                                    <td><b class="descricao">{!! $item['descricao_pecas'] !!}</b></td>
                                    <td><b>{!! $item['quantidade'] !!}</b></td>
                                    <td><b>{!! $item['preco_unitario'] !!}</b></td>
                                    <td><b>{{ $item['valor_total'] }}</b></td>
                                    <td><a href="#" class="btn-informacao" data-toggle="tooltip" data-placement="right" title="" data-original-title='Campanha {{ $item['campanha_nome'] }} {{ $item['campanha_informativo'] }}' style="color: black;"></a><b class="comissao"> {!! $item['comissao'] !!} </b></td>
                                    <td><a href="#" class="bt-edit" title='Editar' data-id="{{ $item['id'] }}" onclick="editarProduto($(this).parents('tr'), '{{ $item['id'] }}')"></a></td>
                                    <td><a href="#" class="bt-delete" title='Excluir' onclick="produtoExcluir($(this).parents('tr'), '{{ $item['id'] }}')"></a></td>
                                @else
                                    <td><span class="codigo">{{ $item['codigo'] }}</span></td>
                                    <td><span class="descricao">{!! $item['descricao_pecas'] !!}</span></td>
                                    <td>{!! $item['quantidade'] !!}</td>
                                    <td>{!! $item['preco_unitario'] !!}</td>
                                    <td>{{ $item['valor_total'] }}</td>
                                    <td><span class="comissao">{!! $item['comissao'] !!}</span></td>
                                    <td><a href="#" class="bt-edit" title='Editar' data-id="{{ $item['id'] }}" onclick="editarProduto($(this).parents('tr'), '{{ $item['id'] }}')"></a></td>
                                    <td><a href="#" class="bt-delete" title='Excluir' onclick="produtoExcluir($(this).parents('tr'), '{{ $item['id'] }}')"></a></td>
                                @endif
                            </tr>
                            @endforeach
						@endif
			        </tbody>
			    </table>
			</div>
			<div class="col-sm-12 mt-5" id="button-bottom">
				<button type="button" id="bt_ir_para_cabecalho" class="btn troca-aba btn-info"><< Voltar para o cabeçalho</button>
				<button type="button" id="enviar_pedido_aprovacao" class="btn btn-success float-right">Enviar pedido</button>
                <button type="button" id="enviar_proposta_aprovacao" class="btn btn-success float-right" style="margin-right: 5px; margin-left: 5px;">Enviar proposta</button>
			</div>
        </div>
	</div>
	<div class="tab-pane" id="pedido_web_credito" role="tabpanel" aria-labelledby="dados-tab">
        @include('programs.posicao_sintetica_cliente.modal')
	</div>
</div>
<script type="text/javascript">
	table_produtos = '';
	table_pecas_pedido = '';
    
    var condicoes_especiais = {!! json_encode($condicoes_especiais) !!};
    var tipo_pronta_entrega = {!! json_encode($tipo_pronta_entrega) !!};
    var tipo_vendas_futuro = {!! json_encode($tipo_vendas_futuro) !!};
	var campos_not_validate = ['enfestar', 'bater_amostra', 'incluir_cartelas', 'metragem_exata', 'transportadora_retira_imediato', 'transportadora_retira_horario'];
	var campos_check_not_validate = ['enfestar', 'bater_amostra', 'incluir_cartelas', 'metragem_exata', 'transportadora_retira_imediato'];
    init();

	function init(){
		initFunctionsOn();
		initMaskCampos();
		checkTipoVenda();
		initAutoCompletes();
        exibirValorFreteRedespacho();

        hideClienteBalcao();
        @if($pedido['cliente_balcao'] == true)
        showClienteBalcao();
        @endif
        @if (empty($pedido['id']))
        hideCamposEmpresa();
        @else
        $(document).find("#codigo_modal_analise").val('{{ $pedido["cliente_codigo"] }}');
        @endif

		@if (!empty($pedido['mensagem']))
		message('Alerta', '{{ $pedido['mensagem'] }}');
        @endif

        @if($pedido['producao'] == true)
        showDesenho();
        @endif
        
        @if($pedido['tipo_venda'] === 'pedido_pilotagem' || $pedido['tipo_venda'] === 'remessa_faturamento' )
        hideCondicaoPagamento();
        @endif

        @if($pedido['transferencia'])
        $(document).find('#preco_unitario').attr('readonly', 'readonly');
        $(document).find('#preco_unitario').attr('disabled', 'disabled');
        $(document).find('#tipo_venda').parent().hide();
        $(document).find('#data_previsao_entrega').parent().hide();
        hideCondicaoPagamento();
        @endif
        @if($pedido['blacklist'] == true)
        showClienteBloqueado();
        @endif

        $(document).find('.producao').popover({
            container: 'body',
            html: true,
            show: true,
            trigger: 'hover click'
        });

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
            "autoWidth": false,
			"drawCallback": function(settings) {
				$(document).find('.estoque, .preco, #total_pedido').popover({
					container: 'body',
					html: true,
					show: true,
					trigger: 'manual'
				});
                $(document).find('.producao').popover({
                    container: 'body',
                    html: true,
                    show: true,
                    trigger: 'hover click'
                });
			},
			"language": {
				"decimal":        ",",
				"thousands":      ".",
				"emptyTable":     "Nenhum produto inserido",
				"infoPostFix":    "",
				"loadingRecords": "Carregando...",
				"processing":     "Processando...",
				"zeroRecords":    "Nenhum  produto inserido",
				"paginate": {
					"first":      "<<",
					"last":       ">>",
					"next":       ">",
					"previous":   "<"
				}
			},
			"columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                {
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    'width': '5px',
                    "orderable": false
                },
                {
                    'targets': ['td_quantidade', 'td_preco', 'td_total'],
                    'width': '120px'
                },
                {
                    'targets': ['td_comissao', 'td_coluna'],
                    'width': '50px'
                },
                {
                    'targets': 'td_codigo_produto',
                    'width': '100px'
                },
                
			],
			"order": [[ 1, 'asc' ]]
		};
		table_produtos = '';
		table_produtos = $(document).find('#table-filters-pedidos-itens').DataTable(table_filters_produtos_options);
		table_produtos.draw();

		table_pecas_pedido_options = {
			"searching": false,
			"lengthChange": false,
			"info": false,
			"paging": false,
			"processing": true,
			"orderMulti": false,
			"scrollCollapse": true,
            "autoWidth": false,
			"language": {
				"decimal":        ",",
				"thousands":      ".",
				"emptyTable":     "Nenhum peça encontrada",
				"infoPostFix":    "",
				"loadingRecords": "Carregando...",
				"processing":     "Processando...",
				"zeroRecords":    "Nenhum peça encontrada"
			},
			"columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                
			],
			"order": [[ 0, 'asc' ]]
		};
		table_pecas_pedido = '';
		table_pecas_pedido = $(document).find('#table_pecas_pedido').DataTable(table_pecas_pedido_options);
		table_pecas_pedido.draw();

		table_produtos.on('draw', function(){
			if(table_produtos.data().any() === true){
				totalizadores();
			}
			exibirBtnEnviarPedido();
        });
        
		$(document).find(".nav-tabs").find('a[data-toggle="tab"]').on("shown.bs.tab", function(e){
			if($(e.target).attr("id") === 'pedido-web-itens-tab'){
				table_produtos.draw();
				$(document).find(".estoque").popover('toggle');
				$(document).find(".preco").popover('toggle');
				$(document).find("#total_pedido").popover('toggle');
				$(document).find("#produto_descricao").focus();
				$(document).find('.producao').popover({
					container: 'body',
					html: true,
					show: true,
					trigger: 'hover click'
				});
			}
		});

		if(checkCamposIniciais() === false){
			$(document).find('.hide-on-start').hide();
			disabledTabs();
		}
		else {
			$(document).find('.hide-on-start').show();
			removeDisabledTabs();
		}

        @if (!empty($pedido["message_limite"]))
        messageLimitedeCredito('Informações de crédito', '{!! $pedido["message_limite"] !!}');
        $('.modal-backdrop').eq(0).css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
        @endif

        $(document).find(".content_pecas").hide();

		$(document).find(".venda_observacao").hide();
        @if(in_array($pedido["estabelecimento"],[5, 8]))
		$(document).find(".venda_observacao").show();
		@endif
		$(document).find(".transportadora_retira").hide();
		$(document).find(".transportadora_retira_horario").hide();
		@if($pedido['transportadora_retira'] == true)
		$(document).find(".transportadora_retira").show();
		$(document).find('#transportadora_tipo_frete').attr('readonly', 'readonly');
		@if($pedido['transportadora_retira_imediato'] == 'false')
		$(document).find(".transportadora_retira_horario").show();
		@endif
		@endif
        hideObservacao();
        @if(in_array($pedido['estabelecimento'], [5, 8]))
        showObservacao();
        @endif
		hideTelefone();
        @if($pedido['cliente_sem_telefone'] == 'true')
        showTelefone();
        @endif 

        $('#transportadora_tipo_frete').children('option:not(:first)').remove();
        @if($pedido['transportadora_frete'] == 'CIF')
            $("#transportadora_tipo_frete").append('<option @if($pedido["tipo_frete"] === "P") selected @endif value="P">PAGO - CIF</option>');
        @elseif($pedido['transportadora_frete'] == 'FOB')
            $("#transportadora_tipo_frete").append('<option @if($pedido["tipo_frete"] === "A") selected @endif value="A">A PAGAR - FOB</option>');
            $("#transportadora_tipo_frete").append('<option @if($pedido["tipo_frete"] === "C") selected @endif value="C">COBRADO - FOB</option>');
            $("#transportadora_tipo_frete").append('<option @if($pedido["tipo_frete"] === "T") selected @endif value="T">TERCEIRO - FOB</option>');
            $("#transportadora_tipo_frete").append('<option @if($pedido["tipo_frete"] === "S") selected @endif value="S">SEM FRETE - FOB</option>');
        @else
            $("#transportadora_tipo_frete").append('<option @if($pedido["tipo_frete"] === "P") selected @endif value="P">PAGO - CIF</option>');
            $("#transportadora_tipo_frete").append('<option @if($pedido["tipo_frete"] === "A") selected @endif value="A">A PAGAR - FOB</option>');
            $("#transportadora_tipo_frete").append('<option @if($pedido["tipo_frete"] === "C") selected @endif value="C">COBRADO - FOB</option>');
            $("#transportadora_tipo_frete").append('<option @if($pedido["tipo_frete"] === "T") selected @endif value="T">TERCEIRO - FOB</option>');
            $("#transportadora_tipo_frete").append('<option @if($pedido["tipo_frete"] === "S") selected @endif value="S">SEM FRETE - FOB</option>');
        @endif

        $('#transportadora_redespacho_tipo_frete').children('option:not(:first)').remove();
        @if($pedido['transportadora_redespacho_frete'] == 'CIF')
            $("#transportadora_redespacho_tipo_frete").append('<option selected value="P">PAGO - CIF</option>');
        @elseif($pedido['transportadora_redespacho_frete'] == 'FOB')
            $("#transportadora_redespacho_tipo_frete").append('<option @if($pedido["tipo_frete_redespacho"] === "A") selected @endif value="A">A PAGAR - FOB</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option @if($pedido["tipo_frete_redespacho"] === "C") selected @endif value="C">COBRADO - FOB</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option @if($pedido["tipo_frete_redespacho"] === "T") selected @endif value="T">TERCEIRO - FOB</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option @if($pedido["tipo_frete_redespacho"] === "S") selected @endif value="S">SEM FRETE - FOB</option>');
        @else
            $("#transportadora_redespacho_tipo_frete").append('<option @if($pedido["tipo_frete_redespacho"] === "P") selected @endif value="P">PAGO - CIF</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option @if($pedido["tipo_frete_redespacho"] === "A") selected @endif value="A">A PAGAR - FOB</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option @if($pedido["tipo_frete_redespacho"] === "C") selected @endif value="C">COBRADO - FOB</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option @if($pedido["tipo_frete_redespacho"] === "T") selected @endif value="T">TERCEIRO - FOB</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option @if($pedido["tipo_frete_redespacho"] === "S") selected @endif value="S">SEM FRETE - FOB</option>');
        @endif
    }
    
    function showClienteBalcao(){
        $(document).find('.cliente_balcao').show();
        $(document).find('.cliente_not_balcao').hide();
        $(document).find('label[for="nome_contato"]').html('Nome do cliente');
        $(document).find('label[for="no_pedido_compra"]').html('CPF');
        $(document).find('input[name="condicao_pagamento"]').attr('disabled', 'disabled');
        $(document).find('select[name="condicao_pagamento"]').removeAttr('disabled');
        $(document).find('#condicao_pagamento_blacklist>select[name="condicao_pagamento"]').attr('disabled', 'disabled');

        $(document).find('#data_previsao_entrega').parent().hide();
        $(document).find('#condicao_pagamento_blacklist').hide();
        $(document).find('#condicao_pagamento_group').hide();
    }
    function hideClienteBalcao(){
        $(document).find('.cliente_balcao').hide();
        $(document).find('.cliente_not_balcao').show();
        $(document).find('.content-not-estabel').show();
        $(document).find('label[for="nome_contato"]').html('Nome do contato');
        $(document).find('label[for="no_pedido_compra"]').html('Pedido compra');
        $(document).find('select[name="condicao_pagamento"]').attr('disabled', 'disabled');
        $(document).find('input[name="condicao_pagamento"]').removeAttr('disabled');
        $(document).find('#condicao_pagamento_blacklist>select[name="condicao_pagamento"]').attr('disabled', 'disabled');

        $(document).find('#data_previsao_entrega').parent().show();
        $(document).find('#condicao_pagamento_blacklist').hide();
        $(document).find('#condicao_pagamento_group').show();
    }

    function showCamposEmpresa(){
        if($(document).find("#check_cliente_balcao").val() == 'true'){
            showClienteBalcao();
        }else{
            $(document).find('.content-not-estabel').show();
        }
    }
    function hideCamposEmpresa(){
        $(document).find('.content-not-estabel').hide();
    }
    
	function disabledTabs(){
		$(document).find("#pedido-web-itens-tab").addClass('disabled');
	}
	function removeDisabledTabs(){
		$(document).find("#pedido-web-itens-tab").removeClass('disabled');
	}

	function initMaskCampos(){

       datepicker_options = {
			format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
			startDate: new Date(),
		};

		if (
            tipo_vendas_futuro.indexOf($(document).find('#tipo_venda').val()) < 0
        ){
			var limite = new Date();
			limite.setDate(limite.getDate() + 30);
			datepicker_options.endDate = limite;
			$('#previsao_entrega_obrigatorio').addClass('d-none');
		}
		else if ($('#pedido_futuro_sim').is(':checked')){
			$('#previsao_entrega_obrigatorio').removeClass('d-none');
		}

        $(document).find("#data_previsao_entrega").datepicker(datepicker_options);
		$(document).find("#data_previsao_entrega").mask("00/00/0000");

        $(document).find("#quantidade").maskMoney({thousands:'', decimal:','});
        $(document).find("#preco_unitario").maskMoney({thousands:'', decimal:','});
        $(document).find("#valor_frete").maskMoney({thousands:'', decimal:','});
        $(document).find("#valor_frete_redespacho").maskMoney({thousands:'', decimal:','});

		$(document).find(".horario").mask("00:00");

        var optionsMaskTelefone =  {
			onKeyPress: function(telefone, e, field, options) {
				var masks = ['(00) 0000-00009', '(00) 00000-0000'];
				var mask = (telefone.length>14) ? masks[1] : masks[0];
				$(document).find('#cliente_telefone').mask(mask, options);
			}
		};
        @if(empty($pedido['cliente_telefone']) || strlen($pedido['cliente_telefone']) < 14)
        $(document).find("#cliente_telefone").mask("(00) 0000-0000", optionsMaskTelefone);
		@else
        $(document).find("#cliente_telefone").mask("(00) 0000-00009", optionsMaskTelefone);
		@endif
	}

	function data_entrega(){
        $(document).find("#data_previsao_entrega").datepicker('destroy');
        datepicker_options = {
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
            startDate: new Date()
        };

        if (
            tipo_vendas_futuro.indexOf($(document).find('#tipo_venda').val()) < 0
        ){
            var limite = new Date();
            limite.setDate(limite.getDate() + 30)
            datepicker_options.endDate = limite;
            $('#previsao_entrega_obrigatorio').addClass('d-none')
        }
        else {
            $('#previsao_entrega_obrigatorio').removeClass('d-none');
        }

        $(document).find("#data_previsao_entrega").datepicker(datepicker_options);
        $(document).find("#data_previsao_entrega").mask("00/00/0000");
	}

	function initFunctionsOn(){
        checkCamposTransportadora();
        $(document).find("#btn-cancel-itens-pedido").hide();
		$(document).find("#bt-search-cliente").off("click");
		$(document).find("#bt-search-cliente").on("click", function(event){
            event.stopPropagation();
			showModalCliente($(this).data("route"));
            return false;
		});
		$(document).find("#bt-search-cliente-conta-e-ordem").off("click");
		$(document).find("#bt-search-cliente-conta-e-ordem").on("click", function(event){
            event.stopPropagation();
			showModalClienteContaEOrdem($(this).data("route"));
            return false;
		});
        $(document).find("#bt-view-condicao").off("click");
        $(document).find("#bt-view-condicao").on("click", function(event){
            event.stopPropagation();
            modalCondicao($(this).parent());
            return false;
        });
        $(document).find("#bt-view-transportadora").off("click");
        $(document).find("#bt-view-transportadora").on("click", function(event){
            event.stopPropagation();
            modalTransportador();
            return false;
        });
        $(document).find("#bt-view-transportadora-redespacho").off("click");
        $(document).find("#bt-view-transportadora-redespacho").on("click", function(event){
            event.stopPropagation();
            modalTransportadorRedespacho();
            return false;
        });
		$(document).find(".troca-aba").off("click");
        $(document).find(".troca-aba").on("click", function(e){
            e.preventDefault();
            if($(this).attr("id") === "bt_ir_para_produtos"){
                if($(document).find("#pedido-web-itens-tab").hasClass('disabled')){
                    validaCamposIniciais();
                }else{
                    $(document).find("#pedido-web-itens-tab").tab("show");
                }
            }else{
                $(document).find("#pedido-web-header-tab").tab("show");
            }

            $(document).find(".popover").each(function(index, el) {
                $(document).find("[aria-describedby="+$(this).attr('id')+"]").popover('hide');
            });
		});
		
        $(document).find('#pedido_web_header').off('keydown', 'input, select, textarea');
        $(document).find('#pedido_web_header').on('keydown', 'input, select, textarea', function(e) {
            var self = $(this)
              , form = self.parents('form:eq(0)')
              , focusable
              , next;
            if (e.keyCode == 13) {
                focusable = form.find('input,a,select,button,textarea').filter(':visible');
                next = focusable.eq(focusable.index(this)+1);
                if (next.length) {
                    next.focus();
                } else {
                    focusable.eq(focusable.index(0)).focus();
                }
                return false;
            }
        });

		$(document).find('#pedido_web_header').find('input, textarea, select').off('change');
		$(document).find('#pedido_web_header').find('input, textarea, select').on('change', function(){
			saveOnChange($(this));
		});

        exibirValorFrete();
        exibirValorFreteRedespacho();

		$(document).find("#tipo_venda").off('change');
		$(document).find("#tipo_venda").on('change', function(event){
            if($(this).attr('readonly')){
                $(document).find('#tipo_venda').val('pronta_entrega_venda');
                return false;
            }
			checkTipoVenda();
			saveOnChange($(document).find("#tipo_venda"));
		});

        $(document).find('#transportadora_redespacho_tipo_frete').off('change');
        $(document).find('#transportadora_redespacho_tipo_frete').on('change', function(){
            exibirValorFreteRedespacho();
			saveOnChange($(document).find('#transportadora_redespacho_tipo_frete'));
		});

        $(document).find("#bt-search-produto").off("click");
        $(document).find("#bt-search-produto").on("click", function(){
            showModalPedido();
        });
        $(document).find("#produto_descricao").off('blur');
        $(document).find("#produto_descricao").on('blur', function(){
            retornaInformacoesPreco();
        });
        
        $(document).find("#produto_codigo_desenho").off('blur');
        $(document).find("#produto_codigo_desenho").on('blur', function(){
            retornaInformacoesPrecoDigital($(document).find("#produto_codigo_desenho"));
        });
        $(document).find("#produto_codigo_base").off('blur');
        $(document).find("#produto_codigo_base").on('blur', function(){
            retornaInformacaoProduto($(document).find("#produto_codigo_base"), $(document).find("#produto_descricao_base"));''
        });
        
		$(document).find("#btn-cancel-itens-pedido").off('click');
		$(document).find("#btn-cancel-itens-pedido").on('click', function() {
			$(document).find("#btn-create-itens-pedido").html('Inserir produto');
			$(document).find("#btn-cancel-itens-pedido").hide();
			$(document).find("#produto_codigo").val('');
            $(document).find('#campanha').html('');
            $(document).find("#pecas_tamanho").val('');
			$(document).find("#produto_descricao").val('');
			$(document).find("#quantidade").val('');
			$(document).find("#preco_unitario").val('');
            $(document).find("#coluna").val('');
            $(document).find("#estoque_disponivel").val('');
			table_produtos.row.add(window.temp_row).draw();
            $(document).find(".content_pecas").hide();
			table_pecas_pedido.clear().draw();
			exibirBtnEnviarPedido();
		});

        $(document).find("#btn-create-itens-pedido").off("click");
        $(document).find("#btn-create-itens-pedido").on("click", function () {
            if ($(document).find("#produto_pedido_id").val() == ''){
                salvarProdutoNoPedido();
            }
            else{
                salvaEdicaoProduto();
            }
		});
        
        $(document).find("#enviar_pedido_aprovacao").off('click');
        $(document).find("#enviar_pedido_aprovacao").on('click', function(){
            salvarPedido();
		});

        $(document).find("#enviar_proposta_aprovacao").off('click');
        $(document).find("#enviar_proposta_aprovacao").on('click', function(){
            propostaLink();
		});
		
        $(document).find(".essencial").off('change');
        $(document).find(".essencial").on('change', function(event){
            validarRecalcular($(this));
        });

        $(document).find('#transportadora_tipo_frete').off('change');
        $(document).find('#transportadora_tipo_frete').on('change', function(){
            validarRecalcular($(this));
            exibirValorFrete();
		});
		
        $(document).find('#alerta_preco_pedido').hide();
        $(document).find('#alerta_preco_pedido').text("*");

        $(document).find('#ultimas-vendas-div').hide();

        $(document).find('#btn-ultimas-vendas').on('click', function(){ modalUltimasVendasClientes(); });

        @if(Auth::user()->tipo_usuario->nome != 'Representante')
        $(document).find('#importar-csv').on('change', function(){
            importarArquivo();
        })
        @endif

        $(document).find("#bt-search-produto_base").on('click', function(){
            var form_modal = $(document).find('#form_filter_itens');
            showModalProdutoTecidoBaseModal(form_modal);
        });
        $(document).find("#bt-search-produto_desenho").on('click', function(){
            var form_modal = $(document).find('#form_filter_itens');
            showModalProdutoModal(form_modal, "grupo", "Desenho Estamparia Digital", "desenho");
        });

        $(document).find('#transportadora_nome').off('keypress');
        $(document).find('#transportadora_nome').on('keypress', function(){
        	$(document).find('#transportadora').val('');
        });

        $(document).find('#transportadora_redespacho_nome').off('keypress');
        $(document).find('#transportadora_redespacho_nome').on('keypress', function(){
        	$(document).find('#transportadora_redespacho').val('');    
        });

        @if(Auth::user()->tipo_usuario->nome != 'Representante')
        $(document).find('#quantidade').off('change');
        $(document).find('#quantidade').on('change', function(){
            mostrarPecas($(this));
		});
        @endif
        $(document).find('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if($(e.target).attr('id') == 'pedido-web-credito-tab'){
                getDados($(document).find("#form_filter_modal").serialize());
            }
        });

        $(document).find("input[name='transportadora_retira_imediato']").on('change', function(event){
            validaRedespacho($(this).val());
        });

        $(document).find('#estabelecimento').off('change');
        $(document).find('#estabelecimento').on('change', function(){
        	$(document).find('#transportadora').val('');
            $(document).find('#transportadora_nome').val('');
            $(document).find('#transportadora_tipo_frete').val('');
            $(document).find('#transportadora_redespacho').val('');
            $(document).find('#transportadora_redespacho_nome').val('');
            $(document).find('#transportadora_redespacho_tipo_frete').val('');
            validarRecalcular($(this));
            exibirValorFrete();
        });
	}

	function initAutoCompletes(){
        $(document).find("#nome_cliente").autocomplete(optionsAutoCompleteCliente());
        $(document).find("#nome_cliente_conta_e_ordem").autocomplete(optionsAutoCompleteClienteContaEOrdem());
        $(document).find("#transportadora_nome").autocomplete(optionsAutoCompleteTransportador());
        $(document).find("#transportadora_redespacho_nome").autocomplete(optionsAutoCompleteTransportadorRedespacho());
        $(document).find("#condicao_pagamento_descr").autocomplete(optionsAutoCompleteCondicoes());
        $(document).find("#produto_descricao").autocomplete(optionsAutoCompleteProdutoDescricao());

        $(document).find("#produto_descricao_base").autocomplete(optionsAutoCompleteProdutoBaseDescricao());
        $(document).find("#produto_descricao_desenho").autocomplete(optionsAutoCompleteProdutoDsenhoDescricao());
	}
	/****************************************************/
	/*******************AUTOCOMPLETE*********************/
	/****************************************************/

    function optionsAutoCompleteCliente(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.estabelecimento = $(document).find('#estabelecimento').val();
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
                $(document).find("#codigo_cliente").val(ui.item.value);
                $(document).find("#nome_cliente").val(ui.item.label);
				checkNomeCliente($(document).find("#nome_cliente"));
                return false;
            }
        };
    }
    function optionsAutoCompleteClienteContaEOrdem(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.conta_ordem = "true";
                request.estabelecimento = $(document).find('#estabelecimento').val();
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
            },
            response: function( event, ui ) {
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#codigo_cliente_conta_e_ordem").val(ui.item.value);
                $(document).find("#nome_cliente_conta_e_ordem").val(ui.item.label);
                return false;
            }
        };
    }
    function optionsAutoCompleteTransportador(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.estabelecimento = $(document).find('#estabelecimento').val();
                request.nome_cliente = $(document).find('#nome_cliente').val();
                request.nome_cliente_conta_e_ordem = $(document).find('#nome_cliente_conta_e_ordem').val();
                request.redespacho = "false";
                $.post("{{ route('transportadora_estabelecimento.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma transportadora encontrada');
                    event.stopPropagation();
                    $(document).find("#transportadora_nome").focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#transportadora").val(ui.item.value);
                $(document).find("#transportadora_nome").val(ui.item.label);
                $('#transportadora_tipo_frete').children('option:not(:first)').remove();
                if(ui.item.tipo_frete == 'CIF'){
                    $("#transportadora_tipo_frete").append('<option value="P">PAGO - CIF</option>');
                }else if(ui.item.tipo_frete == 'FOB'){
                    $("#transportadora_tipo_frete").append('<option value="A">A PAGAR - FOB</option>');
                    $("#transportadora_tipo_frete").append('<option value="C">COBRADO - FOB</option>');
                    $("#transportadora_tipo_frete").append('<option value="T">TERCEIRO - FOB</option>');
                    $("#transportadora_tipo_frete").append('<option value="S">SEM FRETE - FOB</option>');
                }else{
                    $("#transportadora_tipo_frete").append('<option value="P">PAGO - CIF</option>');
                    $("#transportadora_tipo_frete").append('<option value="A">A PAGAR - FOB</option>');
                    $("#transportadora_tipo_frete").append('<option value="C">COBRADO - FOB</option>');
                    $("#transportadora_tipo_frete").append('<option value="T">TERCEIRO - FOB</option>');
                    $("#transportadora_tipo_frete").append('<option value="S">SEM FRETE - FOB</option>');
                }
                return false;
            }
        };
    }
    function optionsAutoCompleteTransportadorRedespacho(){
        $(document).find(".error-message").remove();

        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.estabelecimento = $(document).find('#estabelecimento').val();
                request.nome_cliente = $(document).find('#nome_cliente').val();
                request.nome_cliente_conta_e_ordem = $(document).find('#nome_cliente_conta_e_ordem').val();
                request.redespacho = "true";
                $.post("{{ route('transportadora_estabelecimento.autocomplete_redespacho') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma transportadora encontrada');
                    event.stopPropagation();
                    $(document).find("#transportadora_redespacho_nome").focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                $(document).find("#transportadora_redespacho").val(ui.item.value);
                $(document).find("#transportadora_redespacho_nome").val(ui.item.label);
                $('#transportadora_redespacho_tipo_frete').children('option:not(:first)').remove();
                if(ui.item.tipo_frete == 'CIF'){
                    $("#transportadora_redespacho_tipo_frete").append('<option value="P">PAGO - CIF</option>');
                }else if(ui.item.tipo_frete == 'FOB'){
                    $("#transportadora_redespacho_tipo_frete").append('<option value="A">A PAGAR - FOB</option>');
                    $("#transportadora_redespacho_tipo_frete").append('<option value="C">COBRADO - FOB</option>');
                    $("#transportadora_redespacho_tipo_frete").append('<option value="T">TERCEIRO - FOB</option>');
                    $("#transportadora_redespacho_tipo_frete").append('<option value="S">SEM FRETE - FOB</option>');
                }else{
                    $("#transportadora_redespacho_tipo_frete").append('<option value="P">PAGO - CIF</option>');
                    $("#transportadora_redespacho_tipo_frete").append('<option value="A">A PAGAR - FOB</option>');
                    $("#transportadora_redespacho_tipo_frete").append('<option value="C">COBRADO - FOB</option>');
                    $("#transportadora_redespacho_tipo_frete").append('<option value="T">TERCEIRO - FOB</option>');
                    $("#transportadora_redespacho_tipo_frete").append('<option value="S">SEM FRETE - FOB</option>');
                }
                return false;
            }
        };
    }
    function optionsAutoCompleteCondicoes(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.estabelecimento = $(document).find('#estabelecimento').val();
                $.post("{{ route('condicoes_pagamento_web.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#condicao_pagamento").val(ui.item.value);
                $(document).find("#condicao_pagamento_descr").val(ui.item.label);
                return false;
            }
        };
    }
    
    function optionsAutoCompleteProdutoDescricao(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.pedido = $(document).find("#id").val();
                $.post("{{ route('produto.autocompletepedido') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $(document).find('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
            },
            classes: {
                "ui-autocomplete": "highlight-produtos"
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum produto encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                $(document).find("#produto_codigo").val(ui.item.value);
                $(document).find("#pecas_tamanho").val(ui.item.pecas);
                $(document).find("#produto_descricao").trigger('change');
                retornaInformacoesPreco();
                $(document).find("#quantidade").focus();
                return false;
            }
        };
    }

    function optionsAutoCompleteProdutoBaseDescricao(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.pedido = $(document).find("#id").val();
                $.post("{{ route('produto.tecido_base.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum produto encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                $(document).find("#produto_descricao_base").val(ui.item.label);
                $(document).find("#produto_codigo_base").val(ui.item.value);
                $(document).find("#produto_codigo_desenho").focus();
                return false;
            }
        };
    }

    function optionsAutoCompleteProdutoDsenhoDescricao(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request.campo = "grupo";
                request.condicao = "Desenho Estamparia Digital";
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocompletelimitacao') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum produto encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                $(document).find("#produto_descricao_desenho").val(ui.item.label);
                $(document).find("#produto_codigo_desenho").val(ui.item.value);
                $(document).find("#quantidade").focus();
                retornaInformacoesPrecoDigital();
                return false;
            }
        };
    }

	/****************************************************/
	/*******************AUTOCOMPLETE*********************/
	/****************************************************/


	/****************************************************/
	/************************MODAL***********************/
	/****************************************************/
    
	function showModalPedido(){
        $.ajax({
            url: '{{ Route('produto.modal_pesquisa_pedido') }}',
            type: 'POST',
           data: {
                _token: '{{ csrf_token()}}',
                pedido: $(document).find("#id").val()
            },
            success: function(body) {
            	$(document).find('#table-modal-produtos-busca').remove();
                createModal('table-modal-produtos-busca', 'Pesquisa de Produtos com Estoque No Estabelecimento', body, 'modal-lg');
                var modal = $(document).find('#table-modal-produtos-busca');
                $(document).ready( function () {
                	modal.find('#nome_modal_busca').autocomplete('destroy');
   					$(document).find('#codigo_modal_busca').val($(document).find('#produto-codigo-salvo').val());
					if($('#codigo_modal_busca').val().length > 0) {
						$(document).find("#btn-filterform-modal").trigger('click');
                    }
                    
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(event){
							returnDadosProduto($(this));
							retornaInformacoesPreco();
                        });
                    });
                });
            },
        });
	}
    function returnDadosProduto($dados){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }

        var pecas = $dados.find("td").eq(6).text();
        pecas = pecas.replace(/[^0-9]/g, '');

        if(pecas != "" || !$.isEmptyObject(pecas)){
            pecas = parseFloat(pecas);
        }else{
            pecas = 0;
        }

        $('#produto_codigo').val($dados.find("td").eq(1).text());
        $('#produto_descricao').val($dados.find("td").eq(2).text());
        $('#pecas_tamanho').val(pecas);
        $(document).find('#produto-codigo-salvo').val($(document).find('#codigo_modal_busca').val());
        $(document).find("#table-modal-produtos-busca").modal("hide");
    }

    function showModalCliente(url){
		var title = "Busca de Clientes";
        $.ajax({
            url: url,
            type: 'POST',
           data: {_token: '{{ csrf_token() }}', estabelecimento: $(document).find('#estabelecimento').val()},
            success: function(body){
				$(document).find('#cliente_searsh_show').remove();
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("td").not('.th_view').off("click");
                        modal.find('tbody').find("td").not('.th_view').on("click", function(event){
                            returnDadosCliente($(this).parent('tr'), event);
                        });
                    });
                });
            }
        });
    }
    function returnDadosCliente($dados, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#codigo_cliente").val($dados.find("td").eq(0).text());
        $(document).find("#nome_cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
        checkNomeCliente($(document).find("#nome_cliente"));
		setTimeout(validarRecalcular($(document).find("#nome_cliente")), 1000);

	}
	
    function showModalClienteContaEOrdem(url){
		var title = "Busca de Clientes conta e ordem ";
        $.ajax({
            url: url,
            type: 'POST',
           data: {_token: '{{ csrf_token() }}', conta_ordem: true, estabelecimento: $(document).find('#estabelecimento').val()},
            success: function(body){
				$(document).find('#cliente_searsh_show').remove();
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(event){
                            returnDadosClienteContaEOrdem($(this), event);
                        });
                    });
                });
            }
        });
    }
    function returnDadosClienteContaEOrdem($dados, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#codigo_cliente_conta_e_ordem").val($dados.find("td").eq(0).text());
        $(document).find("#nome_cliente_conta_e_ordem").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
		saveOnChange($(document).find("#codigo_cliente_conta_e_ordem"));
    }

    function modalCondicao($this){
        $.ajax({
            url: $this.data('route'),
            type: 'POST',
           data: {_token: '{{ csrf_token() }}', estabelecimento: $(document).find('#estabelecimento').val()},
            success: function(data){
				$(document).find("#modal_busca_condicao").remove();
                createModal('modal_busca_condicao', "Busca de condição de pagamento", data, 'modal-lg');
                var modal = $(document).find("#modal_busca_condicao");
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(){
                            returnDadosCondicao($(this));
                        });
                    });
                });
            }
        });
    }
    function returnDadosCondicao($dados){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        
        $(document).find("#condicao_pagamento_descr").data('oldvalue', $(document).find("#condicao_pagamento_descr").val());
		$(document).find("#condicao_pagamento").val($dados.find("td:eq(0)").text() );
		$(document).find("#condicao_pagamento_descr").val($dados.find("td:eq(1)").text() );
        $(document).find("#modal_busca_condicao").modal("hide");

        setTimeout(validarRecalcular($(document).find("#condicao_pagamento_descr")), 1000);

	}
	
    function modalTransportador(){
        $.ajax({
            url: '{{ Route('transportadora_estabelecimento.modal.dialog') }}',
            type: 'POST',
           data: {
                _token: '{{ csrf_token() }}', 
                estabelecimento: $(document).find('#estabelecimento').val(),
                nome_cliente: $(document).find('#nome_cliente').val(),
                nome_cliente_conta_e_ordem: $(document).find('#nome_cliente_conta_e_ordem').val(),
                redespacho: false,
            },
            success: function(data){
                $(document).find('#modal_busca_transportador').remove();
                createModal('modal_busca_transportador', "Busca de transporadora", data, 'modal-lg');
                var modal = $(document).find("#modal_busca_transportador");
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(){
                            returnDadosTransportador($(this), modal);
                        });
                    });
                });
            }

        });
	}
	function returnDadosTransportador($dados, modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
		$(document).find("#transportadora").val($dados.find("td:eq(0)").text());
		$(document).find("#transportadora_nome").val($dados.find("td:eq(1)").text() + " - " + $dados.find("td:eq(2)").text());
		modal.modal('hide');
		
		setTimeout(validarRecalcular($(document).find("#transportadora_nome")), 1000);

        $('#transportadora_tipo_frete').children('option:not(:first)').remove();
        if($dados.find("td:eq(6)").text() == 'CIF'){
            $("#transportadora_tipo_frete").append('<option value="P">PAGO - CIF</option>');
        }else if($dados.find("td:eq(6)").text() == 'FOB'){
            $("#transportadora_tipo_frete").append('<option value="A">A PAGAR - FOB</option>');
            $("#transportadora_tipo_frete").append('<option value="C">COBRADO - FOB</option>');
            $("#transportadora_tipo_frete").append('<option value="T">TERCEIRO - FOB</option>');
            $("#transportadora_tipo_frete").append('<option value="S">SEM FRETE - FOB</option>');
        }else{
            $("#transportadora_tipo_frete").append('<option value="P">PAGO - CIF</option>');
            $("#transportadora_tipo_frete").append('<option value="A">A PAGAR - FOB</option>');
            $("#transportadora_tipo_frete").append('<option value="C">COBRADO - FOB</option>');
            $("#transportadora_tipo_frete").append('<option value="T">TERCEIRO - FOB</option>');
            $("#transportadora_tipo_frete").append('<option value="S">SEM FRETE - FOB</option>');
        }
	}
	
    function modalTransportadorRedespacho(){
        $.ajax({
            url: '{{ Route('transportadora_estabelecimento.modal.dialog') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}', 
                estabelecimento: $(document).find('#estabelecimento').val(),
                nome_cliente: $(document).find('#nome_cliente').val(),
                nome_cliente_conta_e_ordem: $(document).find('#nome_cliente_conta_e_ordem').val(),
                redespacho: true,
            },
            success: function(data){
				$(document).find("#modal_busca_transportador_redespacho").remove();
                createModal('modal_busca_transportador_redespacho', "Busca de transporadora para redespacho", data, 'modal-lg');
                var modal = $(document).find("#modal_busca_transportador_redespacho");
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(){
							returnDadosTransportadorRedespacho($(this), modal);
                        });
                    });
                });
            }
        });
	}
	function returnDadosTransportadorRedespacho($dados, modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
		$(document).find("#transportadora_redespacho").val($dados.find("td:eq('0')").text() );
		$(document).find("#transportadora_redespacho_nome").val($dados.find("td:eq(1)").text() + " - " + $dados.find("td:eq(2)").text());
		modal.modal('hide');
		saveOnChange($(document).find("#transportadora_redespacho"));

        $('#transportadora_redespacho_tipo_frete').children('option:not(:first)').remove();
        if($dados.find("td:eq(6)").text() == 'CIF'){
            $("#transportadora_redespacho_tipo_frete").append('<option value="P">PAGO - CIF</option>');
        }else if($dados.find("td:eq(6)").text() == 'FOB'){
            $("#transportadora_redespacho_tipo_frete").append('<option value="A">A PAGAR - FOB</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option value="C">COBRADO - FOB</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option value="T">TERCEIRO - FOB</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option value="S">SEM FRETE - FOB</option>');
        }else{
            $("#transportadora_redespacho_tipo_frete").append('<option value="P">PAGO - CIF</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option value="A">A PAGAR - FOB</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option value="C">COBRADO - FOB</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option value="T">TERCEIRO - FOB</option>');
            $("#transportadora_redespacho_tipo_frete").append('<option value="S">SEM FRETE - FOB</option>');
        }
	}
	
	/****************************************************/
	/************************MODAL***********************/
	/****************************************************/

	function checkCamposIniciais(){
		var estabelecimento = $(document).find('#estabelecimento').val();
		var cliente = $(document).find('#codigo_cliente').val();
		var tipo_venda = $(document).find('#tipo_venda').val();
		var condicao_pagamento = $(document).find('#condicao_pagamento').val();
		var transporadora = $(document).find('#transportadora').val();
		var transportadora_tipo_frete = $(document).find('#transportadora_tipo_frete').val();
		var valor_frete = $(document).find('#valor_frete').val();
        var nome_contato = $(document).find('#nome_contato').val();
		if(
			(estabelecimento.trim() != '' &&
			cliente.trim() != '' &&
			tipo_venda.trim() != '' &&
			transporadora.trim() != '' &&
			transportadora_tipo_frete.trim() != '' &&
            nome_contato.trim() != '')
		){
			if(transportadora_tipo_frete == 'C'){
				if(valor_frete.trim() == '' || valor_frete == '0,00'){
					return false;
				}
			}
			return true;
		}else{
			return false;
		}
    }

    function checkCamposTransportadora(){

		var transporadora = $(document).find('#transportadora').val();
		var transportadora_tipo_frete = $(document).find('#transportadora_tipo_frete').val();
		if(
			(transporadora.trim() != '' &&
			transportadora_tipo_frete.trim() != '')
		){
			$(document).find('.hide-on-start-redespacho').show();
		}else{
			$(document).find('.hide-on-start-redespacho').hide();;
		}
    }

    function validaCamposIniciais(){
		var $camposIniciais = [
            $(document).find("#modal_pedido_edit").find('#estabelecimento'),
            $(document).find("#modal_pedido_edit").find('#codigo_cliente'),
            $(document).find("#modal_pedido_edit").find('#tipo_venda'),
            $(document).find("#modal_pedido_edit").find('#transportadora'),
            $(document).find("#modal_pedido_edit").find('#transportadora_tipo_frete'),
            $(document).find("#modal_pedido_edit").find('#valor_frete')
        ];
		var $form = $(document).find("#modal_pedido_edit").find('#cadPedido');
        $form.find('.error-message').remove();
		$form.find(".error-input").removeClass('error-input');
        $.each($camposIniciais, function(inice, campo){
            if($(this).val() === ""){
                mensagem = "Campo obrigatório!";
                showErrorsInputs($form, $(this).attr("id"), mensagem);
            }
        });
    }

    function checkTipoVenda(){
    	if(
            $(document).find('#tipo_venda').val() == 'pronta_entrega_triangular' ||
            $(document).find('#tipo_venda').val() == 'pedido_futuro_triangular' ||
            $(document).find('#tipo_venda').val() == 'pre_pago_triangular' ||
            $(document).find('#tipo_venda').val() == 'rj_x_sp_triangular' ||
            $(document).find('#tipo_venda').val() == 'producao_triangular' ||
            $(document).find('#tipo_venda').val() == 'rj_x_sp_triangular_futuro'
        ){
    		$(document).find('#cliente_conta_e_ordem_div').show();
        }
        else {
    		$(document).find('#cliente_conta_e_ordem_div').hide();
        }
        data_entrega();
    }

    function exibirValorFrete(){
        if ($(document).find('#transportadora_tipo_frete').val() == 'C'){
            $(document).find("#valor_frete").parent().show();
            $(document).find("#valor_frete").addClass('essencial')
        }
        else {
            $(document).find("#valor_frete").parent().hide();
            $(document).find("#valor_frete").removeClass('essencial');
        }
    }

    function exibirValorFreteRedespacho(){
        if ($(document).find('#transportadora_redespacho_tipo_frete').val() == 'C'){
            $(document).find("#valor_frete_redespacho").parent().show();
        }
        else {
            $(document).find("#valor_frete_redespacho").parent().hide();
        }
	}

    function checkNomeCliente($this){
        if($this.val() === ''){
            $(document).find("#codigo_cliente").val("");
            $(document).find("#nome_cliente").focus();
            return false;
        }
		hideClienteBloqueado();
        $.ajax({
            url: '{{ Route('cliente.name_to_cod') }}',
            type: 'POST',
           data: {
                _token: '{{ csrf_token() }}',
                name: $(document).find("#nome_cliente").val(),
                estabelecimento: $(document).find("#estabelecimento").val()
            },
            success: function(callback){
                if(callback.status == 'success'){
                    $(document).find("#codigo_cliente").val(callback.response.id);
                    $(document).find("#codigo_modal_analise").val(callback.response.id);
                    $(document).find("#cpf_cnpj_unico").val('');
					$(document).find("#nome_cliente").val(callback.response.nome);
                    $(document).find("#cliente_titulo").html('- ' + callback.response.nome);
                    $(document).find("#id_cliente_encriptada").val(callback.response.id_ecrypt);
                }
                else {
                    $(document).find("#codigo_cliente").val('');
					return false;
                }
                if(callback.response.cliente_balcao !== true){
                
                    var htmlLimiteCredito = ''+
                            '<div class="content-informacoes-credito">'+
                                '<div class="content-informacoes-credito-row">'+
                                    '<div class="content-informacoes-credito-row-titles">'+
                                        '<b>Limite de crédito</b>'+
                                    '</div>'+
                                    '<div class="content-informacoes-credito-row-infos">'+
                                        '<div class="content-informacoes-credito-row-infos-text">'+
                                            '<b>Valor:</b>'+
                                        '</div>'+
                                        '<div class="content-informacoes-credito-row-infos-valor">'+
                                            callback.response.limite_credito+
                                        '</div>'+
                                    '</div>'+
                                    '<div class="content-informacoes-credito-row-infos">'+
                                        '<div class="content-informacoes-credito-row-infos-text">'+
                                            '<b>Válido até:</b>'+
                                        '</div>'+
                                        '<div class="content-informacoes-credito-row-infos-valor">'+
                                            callback.response.data_valida_limite+
                                        '</div>'+
                                    '</div>'+
                                    '<div class="content-informacoes-credito-row-infos">'+
                                        '<div class="content-informacoes-credito-row-infos-text">'+
                                            '<b>Valor disponivel:</b>'+
                                        '</div>'+
                                        '<div class="content-informacoes-credito-row-infos-valor">'+
                                            callback.response.limite_credito_disponivel+
                                        '</div>'+
                                    '</div>'+
                                '</div>';
                    
                    if (callback.response.pedido_pronta_entrega.valor.length > 0 || callback.response.pedido_pronta_entrega.quantidade.length > 0){
                        htmlLimiteCredito += '' +
                            '<div class="content-informacoes-credito-row">'+
                                '<div class="content-informacoes-credito-row-titles">'+
                                    '<b>Pedidos pronta entrega:</b>'+ 
                                '</div>'+
                                '<div class="content-informacoes-credito-row-infos">'+
                                    '<div class="content-informacoes-credito-row-infos-text">'+
                                        '<b>Quantidade: </b>'+
                                    '</div>'+
                                    '<div class="content-informacoes-credito-row-infos-valor">'+
                                        callback.response.pedido_pronta_entrega.quantidade+
                                    '</div>'+
                                '</div>'+
                                '<div class="content-informacoes-credito-row-infos">'+
                                    '<div class="content-informacoes-credito-row-infos-text">'+
                                        '<b>Valor: </b>'+
                                    '</div>'+
                                    '<div class="content-informacoes-credito-row-infos-valor">'+
                                        callback.response.pedido_pronta_entrega.valor+
                                    '</div>'+
                                '</div>'+
                            '</div>';
                    }
                    if (callback.response.pedido_futuro.valor.length > 0 || callback.response.pedido_futuro.quantidade.length > 0){
                        htmlLimiteCredito += "<br />Pedidos futuros <b>Qtd: </b>" + callback.response.pedido_futuro.quantidade + " <b>Valor: </b>" + callback.response.pedido_futuro.valor;
                    }
                    if (callback.response.titulos_em_aberto.total.length > 0 ){
                        htmlLimiteCredito += ''+
                            '<div class="content-informacoes-credito-row">'+
                                '<div class="content-informacoes-credito-row-titles">'+
                                    '<b>Titulos em aberto</b>'+
                                '</div>';
                        if(callback.response.titulos_em_aberto.a_vencer != ''){
                            htmlLimiteCredito += ''+
                                '<div class="content-informacoes-credito-row-infos">'+
                                    '<div class="content-informacoes-credito-row-infos-text">'+
                                        '<b>A vencer: </b>'+
                                    '</div>'+
                                    '<div class="content-informacoes-credito-row-infos-valor">'+
                                        callback.response.titulos_em_aberto.a_vencer+
                                    '</div>'+
                                '</div>';
                        }
                        if(callback.response.titulos_em_aberto.vencidas != ''){
                            htmlLimiteCredito += ''+
                                '<div class="content-informacoes-credito-row-infos">'+
                                    '<div class="content-informacoes-credito-row-infos-text">'+
                                        '<b>Vencidas: </b>'+
                                    '</div>'+
                                    '<div class="content-informacoes-credito-row-infos-valor">'+
                                        callback.response.titulos_em_aberto.vencidas+
                                    '</div>'+
                                '</div>';
                        }
                        htmlLimiteCredito += ''+
                            '<div class="content-informacoes-credito-row-infos">'+
                                '<div class="content-informacoes-credito-row-infos-text">'+
                                    '<b>Total: </b>'+
                                '</div>'+
                                '<div class="content-informacoes-credito-row-infos-valor">'+
                                    callback.response.titulos_em_aberto.total+
                                '</div>'+
                            '</div>'+
                        '</div>';
                    }
                    if (callback.response.notas_debito.total.length > 0 ){
                        htmlLimiteCredito += ''+
                            '<div class="content-informacoes-credito-row">'+
                                '<div class="content-informacoes-credito-row-titles">'+
                                    '<b>Notas de Débito</b>'+
                                '</div>';
                        if(callback.response.notas_debito.a_vencer != ''){
                            htmlLimiteCredito += ''+
                                '<div class="content-informacoes-credito-row-infos">'+
                                    '<div class="content-informacoes-credito-row-infos-text">'+
                                        '<b>A vencer: </b>'+
                                    '</div>'+
                                    '<div class="content-informacoes-credito-row-infos-valor">'+
                                        callback.response.notas_debito.a_vencer+
                                    '</div>'+
                                '</div>';
                        }
                        if(callback.response.notas_debito.vencidas != ''){
                            htmlLimiteCredito += ''+
                                '<div class="content-informacoes-credito-row-infos">'+
                                    '<div class="content-informacoes-credito-row-infos-text">'+
                                        '<b>Vencidas: </b>'+
                                    '</div>'+
                                    '<div class="content-informacoes-credito-row-infos-valor">'+
                                        callback.response.notas_debito.vencidas+
                                    '</div>'+
                                '</div>';
                        }
                        htmlLimiteCredito += ''+
                                '<div class="content-informacoes-credito-row-infos">'+
                                    '<div class="content-informacoes-credito-row-infos-text">'+
                                        '<b>Total: </b>'+
                                    '</div>'+
                                    '<div class="content-informacoes-credito-row-infos-valor">'+
                                        callback.response.notas_debito.total+
                                    '</div>'+
                                '</div>'+
                            '</div>';
                    }
                    if (callback.response.notas_credito.length > 0 ){
                        htmlLimiteCredito += ''+
                            '<div class="content-informacoes-credito-row">'+
                                '<div class="content-informacoes-credito-row-titles">'+
                                    '<b>Notas de Crédito</b>'+
                                '</div>'+
                                '<div class="content-informacoes-credito-row-infos">'+
                                    '<div class="content-informacoes-credito-row-infos-text">'+
                                        '<b>Total: </b>'+
                                    '</div>'+
                                    '<div class="content-informacoes-credito-row-infos-valor">'+
                                            callback.response.notas_credito +
                                    '</div>'+
                                '</div>'+
                            '</div>';
                    }
                    if(callback.response.alerta.length > 0){
                        htmlLimiteCredito += ''+
                            '<div class="content-informacoes-credito-row">'+
                                '<div class="content-informacoes-credito-row-titles">'+
                                    '<p class="alert-message">' + callback.response.alerta + '</p>'+
                                '</div>'+
                            '</div>';
					}
                    if(callback.response.blacklist.length > 0){
                        htmlLimiteCredito += ''+
							'<div class="content-informacoes-credito-row"><b>Blacklist:</b> '+
								callback.response.blacklist+
                            '</div>';
					}
                    htmlLimiteCredito += '</div>';
                    messageLimitedeCredito('Informações de crédito', htmlLimiteCredito);
                    $('.modal-backdrop').eq(1).css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
                }
				$.ajax({
					url: '{{ route('cliente.salvaClientePadrao') }}',
					type: 'POST',
					data: {
						_token: '{{ csrf_token() }}',
						codcad: callback.response.id
					}
                });
                $.ajax({
                    url: '{{ route('pedido_portal.ultimos_dados') }}',
                    type: 'post',
                   data: {
                        _token: '{{ csrf_token() }}',
						cod_cliente: callback.response.id,
						estabelecimento: $(document).find('#cadPedido').find("#estabelecimento").val()
                    },
                    success: function(callback_ultimos_dados){
                        $(document).find("#email_contato").val(callback_ultimos_dados.email_contato);
                        $(document).find("#transportadora_tipo_frete").val(callback_ultimos_dados.transportadora_tipo_frete);
                        $(document).find("#transportadora").val(callback_ultimos_dados.transportadora_codigo);
                        $(document).find("#transportadora_nome").val(callback_ultimos_dados.transportadora_descricao);
                        if($(document).find("#tipo_venda").val() !== 'pedido_pilotagem' && $(document).find("#tipo_venda").val() !== 'remessa_faturamento'){
                            {{--  $(document).find("#condicao_pagamento").val(callback_ultimos_dados.condicao_pagamento_codigo);
                            $(document).find("#condicao_pagamento_descr").val(callback_ultimos_dados.condicao_pagamento_descricao);  --}}
                            showCondicaoPagamento();
                        }else{
                            hideCondicaoPagamento();
                        }

                        condicao_especial = callback_ultimos_dados.condicao_especial;
                        $(document).find("#tipo_venda").find(".condicao_especial").remove();
                        var condicao_especial_html = '';
                        $.each(condicao_especial, function(key, value){
                            condicao_especial_html += '<option value="'+key+'" class="condicao_especial">'+value+'</option>';
                        });
                        $(document).find("#tipo_venda").append(condicao_especial_html);

                        $(document).find("#check_cliente_balcao").val(callback_ultimos_dados.cliente_balcao);
                        if(callback_ultimos_dados.cliente_balcao == true){
                            $(document).find("#tipo_venda").val(callback_ultimos_dados.tipo_venda);
                        }else{
                            hideClienteBalcao();

							hideClienteBloqueado();
							if(callback_ultimos_dados.blacklist == true){
								showClienteBloqueado();
							}
                        }

                        if ($(document).find('#transportadora_tipo_frete').val() == 'C'){
                            $(document).find("#valor_frete").parent().show();
                            $(document).find("#valor_frete").addClass('essencial')
                        } else {
                            $(document).find("#valor_frete").parent().hide();
                            $(document).find("#valor_frete").removeClass('essencial');
                        }
						$(document).find('#cliente_sem_telefone').val(callback_ultimos_dados.cliente_sem_telefone);
						$(document).find('#cliente_telefone').val(callback_ultimos_dados.cliente_telefone);

                        if(callback_ultimos_dados.transferencia == true){
                            $(document).find('#tipo_venda').val('pronta_entrega_venda');
                            $(document).find('#tipo_venda').parent().hide();
                            $(document).find('#data_previsao_entrega').parent().hide();
							saveOnChange($(document).find('#codigo_cliente'));
                        }else{
                            $(document).find('#tipo_venda').parent().show();
                            $(document).find('#data_previsao_entrega').parent().show();

							if($(document).find("#tipo_venda").val() === 'pedido_pilotagem' || $(document).find("#tipo_venda").val() === 'remessa_faturamento'){
								hideCondicaoPagamento();
							}
							saveOnChange($(document).find('#codigo_cliente'));
                        }
                    }
                });
                
            }
        });

    }
	
    function messageLimitedeCredito($title, $text){
        $class_tratado = "message-alert-credito";
        createModal("messageLimiteCredito", $title, $text, '');
        var mensagem = $(document).find("#messageLimiteCredito");
        $(document).ready( function () {
            mensagem.css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 2));
        });
        $(document).on("keyup", function(event){
            var $this = $(this);
            if(event.which == 27 || event.which == 13){
                mensagem.dialog("close");
            }
        });
    }
	function saveOnChange($campo, $atualiza_preco = false){
        validarEstabelecimento($(document).find('#estabelecimento'));
		if(checkCamposIniciais() === false){
			$(document).find('.hide-on-start').hide();
			disabledTabs();
			return false;
		}

        if(checkCamposTransportadora() === false){
			$(document).find('.hide-on-start-redespacho').hide();
		}else {
			$(document).find('.hide-on-start-redespacho').show();
		}

		$(document).find('.hide-on-start').show();
        removeDisabledTabs();
        hideObservacao();
        if(
            $(document).find('#estabelecimento').val() == '5' || 
            $(document).find('#estabelecimento').val() == '8'
        ){
            showObservacao();
        }
		showMetragemExata();
		if($(document).find('#estabelecimento').val() == '3'){
			hideMetragemExata();
		}

        if($(document).find("#transportadora_redespacho_nome").val().length == 0){
            $(document).find("#transportadora_redespacho").val('');
        }
        if($(document).find("#nome_cliente_conta_e_ordem").val().length == 0){
            $(document).find("#codigo_cliente_conta_e_ordem").val('');
        }

		var form = $(document).find('#cadPedido');
		var form_data = form.serialize();
        var url = form.attr("action");

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');
		if(campos_not_validate.includes($($campo).attr('name')) == true){
			$nome = $($campo).attr('name');
			form_data = {
				_token: form.find("input[name='_token']").val(),
				pedido: form.find("input[name='id']").val()
			};
			$valor = $($campo).val();
			if(campos_check_not_validate.includes($($campo).attr('name')) == true){
				if($($campo).is(':checked')){
					$valor = true;
				}else{
					$valor = false;
				}
			}
			if($nome == 'metragem_exata'){
				if($valor == true && $(document).find('#estabelecimento').val() != 3){
					$(document).find("#texto_metragem_exata").html('Metragem Exata (+10%)');
				}else{
					$(document).find("#texto_metragem_exata").html('');
				}
			}
			form_data[$nome] = $valor;
			$.ajax({
				url: '{{ route("pedido_portal.salvar_campos") }}',
				dataType: 'json',
				data: form_data,
				method: 'POST',
				success: function(callback){
					if($nome == 'metragem_exata' && $(document).find('#estabelecimento').val() != 3){
                    	recalcularTodosItens();
					}
				},
				error: function(callback){
                	message('Atenção', callback.responseJSON.message);
				}
			});
			return false;
		}
		$(document).find(".venda_observacao").hide();
		$(document).find(".transportadora_retira").hide();
		$(document).find(".transportadora_retira_horario").hide();

        if($(document).find("#tipo_venda").val() === 'pedido_pilotagem' || $(document).find("#tipo_venda").val() === 'remessa_faturamento'){
            $(document).find("#condicao_pagamento").val('');
            $(document).find("#condicao_pagamento_descr").val('');
            hideCondicaoPagamento();
        }
		$(document).find('#transportadora_tipo_frete').removeAttr('readonly');

		$.ajax({
			url: url,
			dataType: 'json',
			data: form_data,
			method: 'POST',
			success: function(callback){
				$(document).find('#id').val(callback.response.pedido);
                $(document).find('#media_condicao_pagamento').html(callback.response.media_condicao_pagamento);
                $(document).find('#cif_fob').html(callback.response.cif_fob);
                $(document).find('#preco_cif_fob').html(callback.response.preco_cif_fob);
                $(document).find('#preco_cif_fob_exibir').val(callback.response.preco_cif_fob);
                $(document).find('#estado_destino').html(callback.response.estado_destino);
				$(document).find('#estabelecimento-exibicao').html(callback.response.estabelecimento);
                $(document).find('#venda-futura-exibicao').html(callback.response.pedido_futuro_status);
                $(document).find('#cliente_pedido').html(callback.response.cliente_pedido);
                $(document).find('#cartao').val(callback.response.cartao);
                $(document).find('#data-entrega-exibicao').html($(document).find('#data_previsao_entrega').val());
                if(callback.response.mensagem_pedido != ''){
                    messageLimitedeCredito('Informações de pilotagem', callback.response.mensagem_pedido);
                }

                if(callback.response.transferencia == true){
                    $(document).find('#preco_unitario').attr('readonly', 'readonly');
                    $(document).find('#preco_unitario').attr('disabled', 'disabled');

                    $(document).find('#tipo_venda').val('pronta_entrega_venda');
                    $(document).find('#tipo_venda').parent().hide();
                    $(document).find('#data_previsao_entrega').parent().hide();

                    hideCondicaoPagamento();
                }else{

                    if(callback.response.mensagem_presencial == true){
                        mensagemCartao();
                    }

                    $(document).find('#preco_unitario').removeAttr('readonly');
                    $(document).find('#preco_unitario').removeAttr('disabled');

                    $(document).find('#tipo_venda').parent().show();
                    $(document).find('#data_previsao_entrega').parent().show();

                    if(callback.response.cliente_balcao == true){
                        showClienteBalcao();
                    }else{
                        hideClienteBalcao();

						hideClienteBloqueado();
						if(callback.response.blacklist == true){
							showClienteBloqueado();
						}
                    }

                    if($(document).find("#tipo_venda").val() !== 'pedido_pilotagem' && $(document).find("#tipo_venda").val() !== 'remessa_faturamento'){
                        showCondicaoPagamento();
                    }else{
                        hideCondicaoPagamento();
                    }
					if(callback.response.venda_observacao == true){
                		$(document).find(".venda_observacao").show();
					}
					if(callback.response.transportadora_retira == true){
                		$(document).find(".transportadora_retira").show();
						$(document).find('#transportadora_tipo_frete').val('S');
						$(document).find('#transportadora_tipo_frete').attr('readonly', 'readonly');
						if(callback.request.transportadora_retira_imediato == 'true'){
							validaRedespacho(callback.request.transportadora_retira_imediato);
						}
					}
					
                }
                $(document).find("#coluna").val('');
                $(document).find("#estoque_disponivel").val('');
                $(document).find("#produto_codigo").val('');
                $(document).find('#campanha').html('');
                $(document).find("#pecas_tamanho").val('');
                $(document).find("#preco_unitario").val('');
                $(document).find("#produto_codigo").data('oldvalue', '');
                $(document).find("#produto_descricao").val('');
                if($atualiza_preco == true){
                    recalcularTodosItens();
                }
                if(callback.response.recalcular_itens == true){
                    recalcularTodosItens();
                }
				hideTelefone();
				if(
					$(document).find("#cartao").val() == 'true' &&
					$(document).find("#presencial").val() == 'false'
				){
					showTelefone();
				}
				if(callback.response.rj_x_sp == true){
					$(document).find("#texto_rj_x_sp").html('Operação RJ X SP ');
				}else{
					$(document).find("#texto_rj_x_sp").html('');
				}
			},
			error: function(data){
				var errors = data.responseJSON.errors;
				form.find('.error-message').remove();
				for(var field in errors){
					showErrorsInputs(form, field, errors[field]);
				}
			}
        });
        if(condicoes_especiais[form.find('#tipo_venda').val()]){
            showDesenho();
        }else{
            hideDesenho();
        }

		form.find(".error-message").remove();
		form.find(".error-input").removeClass('error-input');

		return true;
	}


    function showErrorsInputs(form, input, message){
        campos_cliente = ['nome_cliente', 'codigo_cliente']
        campos_cliente_conta_e_ordem = ['nome_cliente_conta_e_ordem', 'codigo_cliente_conta_e_ordem'];
        campos_transportadora = ['transportadora_nome', 'transportadora'];
        campos_transportadora_redespacho = ['transportadora_redespacho_nome', 'transportadora_redespacho'];
        campos_condicao_pagamento = ['condicao_pagamento', 'condicao_pagamento_descr'];
        campos_compostos = ['transportadora', 'transportadora_redespacho', 'transportadora_nome', 'transportadora_redespacho_nome', 'condicao_pagamento', 'produto_descricao', 'produto_descricao_base', 'produto_descricao_desenho'];

        if(campos_cliente.indexOf(input) != -1){
	        $(document).find('#bt-search-cliente').after("<label class='error-message' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#bt-search-cliente').addClass('error-input');
	        $(document).find('#nome_cliente').addClass('error-input');
	        $(document).find('#codigo_cliente').addClass('error-input');
			$(document).find('.hide-on-start').hide();
			disabledTabs();
        }
        else if(campos_cliente_conta_e_ordem.indexOf(input) != -1){
	        $(document).find('#bt-search-cliente-conta-e-ordem').after("<label class='error-message' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#bt-search-cliente-conta-e-ordem').addClass('error-input');
	        $(document).find('#nome_cliente_conta_e_ordem').addClass('error-input');
	        $(document).find('#codigo_cliente_conta_e_ordem').addClass('error-input');
			$(document).find('.hide-on-start').hide();
			disabledTabs();
        }
        else if(campos_transportadora.indexOf(input) != -1){
	        $(document).find('#bt-search-transportadora').after("<label class='error-message' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#bt-search-transportadora').addClass('error-input');
	        $(document).find('#transportadora_nome').addClass('error-input');
	        $(document).find('#transportadora').addClass('error-input');
			$(document).find('.hide-on-start').hide();
			disabledTabs();
        }
        else if(campos_transportadora_redespacho.indexOf(input) != -1){
	        $(document).find('#bt-search-transportadora_redespacho').after("<label class='error-message' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#bt-search-transportadora_redespacho').addClass('error-input');
	        $(document).find('#transportadora_redespacho_nome').addClass('error-input');
	        $(document).find('#transportadora_redespacho').addClass('error-input');
            $(document).find('.hide-on-start').hide();
            $(document).find('#transportadora_redespacho').parent().parent().show();
			disabledTabs();
        }
        else if(campos_condicao_pagamento.indexOf(input) != -1){
	        $(document).find('#bt-search-condicao_pagamento').after("<label class='error-message' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#bt-search-condicao_pagamento').addClass('error-input');
	        $(document).find('#condicao_pagamento').addClass('error-input');
	        $(document).find('#condicao_pagamento_descr').addClass('error-input');
			$(document).find('.hide-on-start').hide();
			disabledTabs();
        }
    	else{
    		if (input == 'data_previsao_entrega' || input == 'estabelecimento' || input == 'tipo_venda'){
				disabledTabs();
    		}

	        if (campos_compostos.indexOf(input) != -1){
	            var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']").parent();
        	}
        	else if (input == 'pedido_futuro'){
	            var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']").parent().parent();
        	}
        	else {
	            var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
	        }
	        
	        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
	        $input.addClass('error-input');
        }

        exibirBtnEnviarPedido();
    }
    function exibirBtnEnviarPedido(){
        if ($(document).find("#table-filters-pedidos-itens").find("tbody").find(".dataTables_empty").length == 0) {
            $(document).find("#enviar_proposta_aprovacao").show();
            $(document).find("#enviar_pedido_aprovacao").show();
        } else {
            $(document).find("#enviar_pedido_aprovacao").hide();
            $(document).find("#enviar_proposta_aprovacao").hide();
        }
    }

    function retornaInformacoesPreco(){
        if ($(document).find("#produto_codigo").data('oldvalue') != $(document).find("#produto_codigo").val()){
            $(document).find('.error-message').remove();
            if ($(document).find("#produto_codigo").val() == ''){
                $(document).find("#preco_unitario").val('');
                $(document).find("#produto_descricao").val('');
                $(document).find("#coluna").val('');
                $(document).find("#estoque_disponivel").val('');
                $(document).find("#produto_codigo").val('');
                $(document).find('#campanha').html('');
                $(document).find("#pecas_tamanho").val('');
                $(document).find("#produto_codigo").data('oldvalue', '');
                $(document).find('#alerta_preco_pedido').hide();
                $(document).find('#alerta_preco_pedido').text("*");
                $(document).find('#ultimas-vendas-div').hide();

                return null;
            }else{
                codigo_produto = $(document).find("#produto_codigo").val();
            }

            $(document).find(".content_pecas").hide();
            table_pecas_pedido.clear().draw();

            $(document).find('#alerta_preco_pedido').hide();
            $(document).find('#alerta_preco_pedido').text("*");
            $(document).find("#produto_codigo").data('oldvalue', $(document).find("#produto_codigo").val());
            $.ajax({
                url: '{{ route('produto.retorna_informacoes_preco') }}',
                type: 'POST',
               data: {
                    _token: '{{ csrf_token()}}', 
                    codprd: $(document).find("#produto_codigo").val(),
                    pedido: $(document).find('#id').val(),
                    preco_base_antes: $(document).find('#preco_base_antes').val(),
                    preco_antes: $(document).find('#preco_antes').val(),
                    comissao_antes: $(document).find('#comissao_antes').val()
                },
                success: function(callback) {
                    $(document).find("#produto_descricao").val(callback.descricao_pecas);
                    $(document).find("#coluna").val(callback.coluna);
                    $(document).find("#estoque_disponivel").val(callback.estoque_disponivel);

                    if($(document).find('#codigo_cliente').val() != '0000010069999'){
                        $(document).find('#ultimas-vendas-div').show();
                    }

                    if(callback.promocional !== false){
                        $(document).find('#alerta_preco_pedido').show();
                        $(document).find('#alerta_preco_pedido').text($(document).find('#alerta_preco_pedido').text() + " Preço promocional");
                    }

                    if(callback.ipi !== false){
                        if(callback.estabelecimento === 3){
                            $(document).find('#alerta_preco_pedido').show();
                            $(document).find('#alerta_preco_pedido').text($(document).find('#alerta_preco_pedido').text() + " Acréscimo de " + callback.ipi_valor + "% de IPI");
                        }else{
                            $(document).find('#alerta_preco_pedido').show();
                            $(document).find('#alerta_preco_pedido').text($(document).find('#alerta_preco_pedido').text() + " Preço com IPI");
                        }
                    }

                    if (!$(document).find("#btn-cancel-itens-pedproduto_produtoido").is(":visible")){
                        $(document).find("#preco_unitario").val(callback.preco_unitario);
                    }

                    $(document).find('#quantidade').focus(); 

                    $(document).find('#preco_base_antes').val(callback.preco_base);

					if(callback.linha != ''){
                        $(document).find("label[for='produto_descricao']").html('Descrição <span class="font-weight-bold text-danger">* linha '+callback.linha+'</span>');					
					}else{
						$(document).find("label[for='produto_descricao']").html('Descrição');
					}
                    if(callback.nome_campanha != ''){
                        $(document).find('#campanha').html('<i class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="Campanha '+callback.nome_campanha+' '+callback.coluna_informativo+'" style="color: black;"></i>');
                    }
                },
                error: function(data){
                    if(data.responseJSON.msg){
                        $(document).find('#produto_descricao').parent().after("<label class='error-message' for='bt-search'>"+codigo_produto+" - "+data.responseJSON.msg+"</label>");
                    }else{
                        $(document).find('#produto_descricao').parent().after("<label class='error-message' for='bt-search'>"+codigo_produto+" - Ocorreu um erro inesperado contate o TI</label>");
                    }
                    $(document).find('#produto_codigo').addClass('error-input');
                    $(document).find('#bt-search-produto').addClass('error-input');

                    $(document).find('#produto_descricao').addClass('error-input');
                    
                    $(document).find("#coluna").val('');
                    $(document).find("#estoque_disponivel").val('');

                    $(document).find("#produto_codigo").val('');
                    $(document).find('#campanha').html('');
                    $(document).find("#pecas_tamanho").val('');
                    $(document).find("#produto_codigo").data('oldvalue', '');

                    $(document).find("#produto_descricao").val('');
                }
            });
        }
    }

    function retornaInformacoesPrecoDigital(){

        form = $(document).find("#form_filter_itens");

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');
        if (
            $(document).find("#produto_codigo_base").data('oldvalue') != $(document).find("#produto_codigo_base").val() ||
            $(document).find("#produto_codigo_desenho").data('oldvalue') != $(document).find("#produto_codigo_desenho").val()
            ){
            if (
                $(document).find("#produto_codigo_base").val() == '' ||
                $(document).find("#produto_codigo_desenho").val() == ''
            ){

                $(document).find("#preco_unitario").val('');
                $(document).find("#produto_descricao").val('');
                $(document).find("#coluna").val('');
                $(document).find("#estoque_disponivel").val('');
                $(document).find("#produto_codigo").val('');
                $(document).find('#campanha').html('');
                $(document).find("#pecas_tamanho").val('');
                $(document).find("#produto_descricao").val('');
                $(document).find('#alerta_preco_pedido').hide();
                $(document).find('#alerta_preco_pedido').text("*");
                $(document).find('#ultimas-vendas-div').hide();

                return null;
            }else{
                codigo_base = $(document).find("#produto_codigo_base").val();
                codigo_desenho = $(document).find("#produto_codigo_desenho").val();
            }
            $(document).find("#produto_codigo_base").data('oldvalue', $(document).find("#produto_codigo_base").val());
            $(document).find("#produto_codigo_desenho").data('oldvalue', $(document).find("#produto_codigo_desenho").val());

            $.ajax({
                url: '{{ Route('produto.retorna_informacoes_preco_producao') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token()}}', 
                    codigo_base: $(document).find("#produto_codigo_base").val(),
                    codigo_desenho: $(document).find("#produto_codigo_desenho").val(),
                    pedido: $(document).find('#id').val()
                },
                success: function(callback) {
                    var dados = callback.response;
                    $(document).find("#produto_codigo").val(dados.final.codigo);
                    $(document).find("#produto_descricao").val(dados.final.descricao);

                    $(document).find("#produto_descricao_base").val(dados.base.descricao);
                    $(document).find("#produto_descricao_desenho").val(dados.desenho.descricao);

                    $(document).find("#coluna").val(dados.coluna);
                    $(document).find("#estoque_disponivel").val(dados.final.estoque);

                    if(dados.promocional !== false){
                        $(document).find('#alerta_preco_pedido').show();
                        $(document).find('#alerta_preco_pedido').text($(document).find('#alerta_preco_pedido').text() + " Preço promocional");
                    }
                    else{
                        $(document).find('#alerta_preco_pedido').hide();
                        $(document).find('#alerta_preco_pedido').text("*");
                    }

                    if (!$(document).find("#btn-cancel-itens-pedproduto_produtoido").is(":visible")){
                        $(document).find("#preco_unitario").val(dados.final.preco);
                    }
                    if(dados.sem_estoque.check == true){
                        $(document).find("#produto_descricao").after("<label class='error-message' for='bt-search'>"+dados.sem_estoque.mensagem+"</label>");
                    }

					if(dados.linha != '' && !$.isEmptyObject(dados.linha)){
						$(document).find("label[for='produto_descricao']").html('Descrição <span class="font-weight-bold text-danger">* linha '+dados.linha+'</span>');
					}else{
						$(document).find("label[for='produto_descricao']").html('Descrição');
					}
                    if(dados.nome_campanha != ''){
                        $(document).find('#campanha').html('<i class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="Campanha '+dados.nome_campanha+' '+dados.coluna_informativo+'" style="color: black;"></i>');
                    }

                    $(document).find('#quantidade').val('');
                    $(document).find('#quantidade').focus();
                },
                error: function(data){
                    if(data.responseJSON.status == 'error'){
                        erros = data.responseJSON.error;
                        $.each(erros, function(key){
                            if(key == 'codigo_base'){
                                $(document).find('#produto_descricao_base').after("<label class='error-message' for='bt-search'>"+codigo_base+" - "+this+"</label>");
                                $(document).find('#produto_codigo_base').addClass('error-input');
                                $(document).find('#produto_descricao_base').addClass('error-input');
                            }
                            if(key == 'codigo_desenho'){
                                $(document).find('#produto_descricao_desenho').after("<label class='error-message' for='bt-search'>"+codigo_desenho+" - "+this+"</label>");
                                $(document).find('#produto_codigo_desenho').addClass('error-input');
                                $(document).find('#produto_descricao_desenho').addClass('error-input');
                            }
                        });
                    }

                    $(document).find('#bt-search-produto').addClass('error-input');
                    
                    $(document).find("#coluna").val('');
                    $(document).find("#estoque_disponivel").val('');

                    $(document).find("#produto_codigo").val('');
                    $(document).find('#campanha').html('');
                    $(document).find("#pecas_tamanho").val('');
                    $(document).find("#produto_codigo").data('oldvalue', '');

                    $(document).find("#produto_descricao").val('');
                }
            });
        }
        
    }
    function createBtEditarProduto($this){
        var html = "<a href=\"#\" class=\"bt-edit\" title='Editar' data-id='"+$this.id+"' onclick=\"editarProduto($(this).parents('tr'), "+$this.id+")\"></a>";
        return html;
    }

    function createBtExcluirProduto($this){
        var html = "<a href=\"#\" class=\"bt-delete\" title='Excluir' onclick=\"produtoExcluir($(this).parents('tr'), "+$this.id+")\"></a>";
        return html;
	}
	
    function salvarProdutoNoPedido(){
        form = $(document).find("#form_filter_itens");

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        var estabelecimento = $(document).find("#estabelecimento").val();

        if(estabelecimento == '31'){

            pecas_tamanho = $(document).find("#pecas_tamanho").val();

            if(pecas_tamanho != "" || !$.isEmptyObject(pecas_tamanho)){
                pecas_tamanho = parseFloat(pecas_tamanho);
            }else{
                pecas_tamanho = parseFloat("0.0");
            }
            
            if(pecas_tamanho > 0){
                quantidade = $(document).find("#quantidade").val();
                if(quantidade != "" || !$.isEmptyObject(pecas_tamanho)){
                    quantidade = parseFloat(quantidade);
                }else{
                    quantidade = pecas_tamanho;
                }

                if(quantidade%pecas_tamanho == 0){
                    $.ajax({
                        url: '{{ Route("pedido_portal.item.adicionar") }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            pedido: $(document).find("#id").val(),
                            estabelecimento: $(document).find("#estabelecimento").val(),
                            cliente: $(document).find("#codigo_cliente").val(),
                            produto_codigo: $(document).find("#produto_codigo").val(),
                            produto_codigo_base: $(document).find("#produto_codigo_base").val(),
                            produto_codigo_desenho: $(document).find("#produto_codigo_desenho").val(),
                            produto_descricao: $(document).find("#produto_descricao").val(),
                            quantidade: $(document).find("#quantidade").val(),
                            preco_unitario: $(document).find("#preco_unitario").val(),
                            tipo: $(document).find("#coluna").val(),
                        },
                        success: function(data) {
                            if(data.campanha_nome.length > 0){
                                var field = [
                                    '<div><b class="codigo">'+data.codigo+'</b></div>',
                                    '<div><b class="descricao">>'+data.descricao+'</b></div>',
                                    '<div><b>'+data.quantidade+'</b></div>',
                                    '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="" style="font-weight: bold">'+data.preco_unitario+'</div></div>',
                                    '<div><b>'+data.valor_total+'</b></div>',
                                    '<div><b><a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="Campanha '+data.campanha_nome+' '+data.campanha_informativo+'" style="color: black;"></a>' +data.comissao+'</b></div>',
                                    createBtEditarProduto(data),
                                    createBtExcluirProduto(data),
                                ];
                            }else{
                                var field = [
                                    data.codigo,
                                    data.descricao,
                                    data.quantidade,
                                    '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="">'+data.preco_unitario+'</div></div>',
                                    data.valor_total,
                                    data.comissao,
                                    createBtEditarProduto(data),
                                    createBtExcluirProduto(data),
                                ];
                            }
                            table_produtos.row.add(field).draw();

                            $(document).find('#preco_antes').val(data.preco_unitario);
                            $(document).find('#comissao_antes').val(data.comissao);

                            $(document).find("#produto_codigo_base").val('');
                            $(document).find("#produto_descricao_base").val('');
                            $(document).find("#produto_codigo_desenho").val('');
                            $(document).find("#produto_descricao_desenho").val('');

                            $(document).find("#produto_codigo").val('');
                            $(document).find('#campanha').html('');
                            $(document).find("#pecas_tamanho").val('');
                            $(document).find("#produto_descricao").val('');
                            $(document).find("#quantidade").val('');
                            $(document).find("#preco_unitario").val('');
                            $(document).find("#estoque_disponivel").val('');
                            $(document).find("#coluna").val('');

                            $(document).find('#ultimas-vendas-div').hide();
                            $(document).find('#alerta_preco_pedido').hide();
                            $(document).find('#alerta_preco_pedido').text("*");
                            
                            $(document).find("label[for='produto_descricao']").html('Descrição');

                            exibirBtnEnviarPedido();

                            $(document).find(".content_pecas").hide();
                            table_pecas_pedido.clear().draw();
                        },
                        error: function(data){
                            var errors = data.responseJSON.errors;
                            form.find('.error-message').remove();
                            for(var field in errors){
                                if (field == 'produto_codigo' && errors[field] == "1"){
                                    showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                                }
                                else if (field == 'produto_codigo' && errors[field] == "2"){
                                    showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                                }
                                else if (field == 'produto_codigo'){
                                    showErrorsInputs(form, 'produto_descricao', errors[field])
                                }
                                else if (field == 'produto_codigo_base'){
                                    showErrorsInputs(form, 'produto_descricao_base', errors[field])
                                }
                                else if (field == 'produto_codigo_desenho'){
                                    showErrorsInputs(form, 'produto_descricao_desenho', errors[field])
                                }
                                else{
                                    showErrorsInputs(form, field, errors[field])
                                }
                            }
                        }
                    }).always( function(){
                        $(document).find('#produto_descricao').focus();
                    });
                }else{
                    var $class = "dialog_option_verificacao_fracionamento_peca";
                    var $name_option_ok_verificacao_fracionamento_peca = "ok_verificacao_fracionamento_peca";
                    var $name_option_cancelar_verificacao_fracionamento_peca = "cancelar_verificacao_fracionamento_peca";

                    $(document).off("ok_verificacao_fracionamento_peca");
                    $(document).on("ok_verificacao_fracionamento_peca", function(){
                        esconderPopoverTooltip();
                        $.ajax({
                            url: '{{ Route("pedido_portal.item.adicionar") }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                pedido: $(document).find("#id").val(),
                                estabelecimento: $(document).find("#estabelecimento").val(),
                                cliente: $(document).find("#codigo_cliente").val(),
                                produto_codigo: $(document).find("#produto_codigo").val(),
                                produto_codigo_base: $(document).find("#produto_codigo_base").val(),
                                produto_codigo_desenho: $(document).find("#produto_codigo_desenho").val(),
                                produto_descricao: $(document).find("#produto_descricao").val(),
                                quantidade: $(document).find("#quantidade").val(),
                                preco_unitario: $(document).find("#preco_unitario").val(),
                                tipo: $(document).find("#coluna").val(),
                            },
                            success: function(data) {
                                if(data.campanha_nome.length > 0){
                                var field = [
                                    '<div><b class="codigo">'+data.codigo+'</b></div>',
                                    '<div><b class="descricao">'+data.descricao+'</b></div>',
                                    '<div><b>'+data.quantidade,
                                    '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="" style="font-weight: bold">'+data.preco_unitario+'</div></div>',
                                    '<div><b>'+data.valor_total+'</b></div>',
                                    '<div><b><a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="Campanha '+data.campanha_nome+' '+data.campanha_informativo+'" style="color: black;"></a>' +data.comissao+'</b></div>',
                                    createBtEditarProduto(data),
                                    createBtExcluirProduto(data),
                                ];
                            }else{
                                var field = [
                                    data.codigo,
                                    data.descricao,
                                    data.quantidade,
                                    '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="">'+data.preco_unitario+'</div></div>',
                                    data.valor_total,
                                    data.comissao,
                                    createBtEditarProduto(data),
                                    createBtExcluirProduto(data),
                                ];
                            }
                                table_produtos.row.add(field).draw();

                                $(document).find('#preco_antes').val(data.preco_unitario);
                                $(document).find('#comissao_antes').val(data.comissao);

                                $(document).find("#produto_codigo_base").val('');
                                $(document).find("#produto_descricao_base").val('');
                                $(document).find("#produto_codigo_desenho").val('');
                                $(document).find("#produto_descricao_desenho").val('');

                                $(document).find("#produto_codigo").val('');
                                $(document).find('#campanha').html('');
                                $(document).find("#pecas_tamanho").val('');
                                $(document).find("#produto_descricao").val('');
                                $(document).find("#quantidade").val('');
                                $(document).find("#preco_unitario").val('');
                                $(document).find("#estoque_disponivel").val('');
                                $(document).find("#coluna").val('');

                                $(document).find('#ultimas-vendas-div').hide();
                                $(document).find('#alerta_preco_pedido').hide();
                                $(document).find('#alerta_preco_pedido').text("*");
                                
                                $(document).find("label[for='produto_descricao']").html('Descrição');

                                exibirBtnEnviarPedido();

                                $(document).find(".content_pecas").hide();
                                table_pecas_pedido.clear().draw();
                            },
                            error: function(data){
                                var errors = data.responseJSON.errors;
                                form.find('.error-message').remove();
                                for(var field in errors){
                                    if (field == 'produto_codigo' && errors[field] == "1"){
                                        showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                                    }
                                    else if (field == 'produto_codigo' && errors[field] == "2"){
                                        showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                                    }
                                    else if (field == 'produto_codigo'){
                                        showErrorsInputs(form, 'produto_descricao', errors[field])
                                    }
                                    else if (field == 'produto_codigo_base'){
                                        showErrorsInputs(form, 'produto_descricao_base', errors[field])
                                    }
                                    else if (field == 'produto_codigo_desenho'){
                                        showErrorsInputs(form, 'produto_descricao_desenho', errors[field])
                                    }
                                    else{
                                        showErrorsInputs(form, field, errors[field])
                                    }
                                }
                            }
                        }).always( function(){
                            $(document).find('#produto_descricao').focus();
                        });
                    });

                    $(document).off("cancelar_verificacao_fracionamento_peca");
                    $(document).on("cancelar_verificacao_fracionamento_peca", function(){
                        return null; 
                    });
                    message_option("Atenção", "A peça será francionada, deseja continuar?", $class, $name_option_ok_verificacao_fracionamento_peca, '', $name_option_cancelar_verificacao_fracionamento_peca, '');
                    esconderPopoverTooltip();
                }
            }else{
                $.ajax({
                    url: '{{ Route("pedido_portal.item.adicionar") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        pedido: $(document).find("#id").val(),
                        estabelecimento: $(document).find("#estabelecimento").val(),
                        cliente: $(document).find("#codigo_cliente").val(),
                        produto_codigo: $(document).find("#produto_codigo").val(),
                        produto_codigo_base: $(document).find("#produto_codigo_base").val(),
                        produto_codigo_desenho: $(document).find("#produto_codigo_desenho").val(),
                        produto_descricao: $(document).find("#produto_descricao").val(),
                        quantidade: $(document).find("#quantidade").val(),
                        preco_unitario: $(document).find("#preco_unitario").val(),
                        tipo: $(document).find("#coluna").val(),
                    },
                    success: function(data) {
                        if(data.campanha_nome.length > 0){
                                var field = [
                                    '<div><b class="codigo">'+data.codigo+'</b></div>',
                                    '<div><b class="descricao">'+data.descricao+'</b></div>',
                                    '<div><b>'+data.quantidade,
                                    '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="" style="font-weight: bold">'+data.preco_unitario+'</div></div>',
                                    '<div><b>'+data.valor_total+'</b></div>',
                                    '<div><b><a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="Campanha '+data.campanha_nome+' '+data.campanha_informativo+'" style="color: black;"></a>'+data.comissao+'</b></div>',
                                    createBtEditarProduto(data),
                                    createBtExcluirProduto(data),
                                ];
                            }else{
                                var field = [
                                    data.codigo,
                                    data.descricao,
                                    data.quantidade,
                                    '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="">'+data.preco_unitario+'</div></div>',
                                    data.valor_total,
                                    data.comissao,
                                    createBtEditarProduto(data),
                                    createBtExcluirProduto(data),
                                ];
                            }
                        table_produtos.row.add(field).draw();

                        $(document).find('#preco_antes').val(data.preco_unitario);
                        $(document).find('#comissao_antes').val(data.comissao);

                        $(document).find("#produto_codigo_base").val('');
                        $(document).find("#produto_descricao_base").val('');
                        $(document).find("#produto_codigo_desenho").val('');
                        $(document).find("#produto_descricao_desenho").val('');

                        $(document).find("#produto_codigo").val('');
                        $(document).find('#campanha').html('');
                        $(document).find("#pecas_tamanho").val('');
                        $(document).find("#produto_descricao").val('');
                        $(document).find("#quantidade").val('');
                        $(document).find("#preco_unitario").val('');
                        $(document).find("#estoque_disponivel").val('');
                        $(document).find("#coluna").val('');

                        $(document).find('#ultimas-vendas-div').hide();
                        $(document).find('#alerta_preco_pedido').hide();
                        $(document).find('#alerta_preco_pedido').text("*");
                        
                        $(document).find("label[for='produto_descricao']").html('Descrição');

                        exibirBtnEnviarPedido();

                        $(document).find(".content_pecas").hide();
                        table_pecas_pedido.clear().draw();
                    },
                    error: function(data){
                        var errors = data.responseJSON.errors;
                        form.find('.error-message').remove();
                        for(var field in errors){
                            if (field == 'produto_codigo' && errors[field] == "1"){
                                showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                            }
                            else if (field == 'produto_codigo' && errors[field] == "2"){
                                showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                            }
                            else if (field == 'produto_codigo'){
                                showErrorsInputs(form, 'produto_descricao', errors[field])
                            }
                            else if (field == 'produto_codigo_base'){
                                showErrorsInputs(form, 'produto_descricao_base', errors[field])
                            }
                            else if (field == 'produto_codigo_desenho'){
                                showErrorsInputs(form, 'produto_descricao_desenho', errors[field])
                            }
                            else{
                                showErrorsInputs(form, field, errors[field])
                            }
                        }
                    }
                }).always( function(){
                    $(document).find('#produto_descricao').focus();
                });
            }
        }else{
            $.ajax({
                url: '{{ Route("pedido_portal.item.adicionar") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    pedido: $(document).find("#id").val(),
                    estabelecimento: $(document).find("#estabelecimento").val(),
                    cliente: $(document).find("#codigo_cliente").val(),
                    produto_codigo: $(document).find("#produto_codigo").val(),
                    produto_codigo_base: $(document).find("#produto_codigo_base").val(),
                    produto_codigo_desenho: $(document).find("#produto_codigo_desenho").val(),
                    produto_descricao: $(document).find("#produto_descricao").val(),
                    quantidade: $(document).find("#quantidade").val(),
                    preco_unitario: $(document).find("#preco_unitario").val(),
                    tipo: $(document).find("#coluna").val(),
                },
                success: function(data) {
                    if(data.campanha_nome.length > 0){
                                var field = [
                                    '<div><b class="codigo">'+data.codigo+'</b></div>',
                                    '<div><b class="descricao">'+data.descricao+'</b></div>',
                                    '<div><b>'+data.quantidade,
                                    '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="" style="font-weight: bold">'+data.preco_unitario+'</div></div>',
                                    '<div><b>'+data.valor_total+'</b></div>',
                                    '<div><b><a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="Campanha '+data.campanha_nome+' '+data.campanha_informativo+'" style="color: black;"></a>' +data.comissao+'</b></div>',
                                    createBtEditarProduto(data),
                                    createBtExcluirProduto(data),
                                ];
                            }else{
                                var field = [
                                    data.codigo,
                                    data.descricao,
                                    data.quantidade,
                                    '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="">'+data.preco_unitario+'</div></div>',
                                    data.valor_total,
                                    data.comissao,
                                    createBtEditarProduto(data),
                                    createBtExcluirProduto(data),
                                ];
                            }
                    table_produtos.row.add(field).draw();

                    $(document).find('#preco_antes').val(data.preco_unitario);
                    $(document).find('#comissao_antes').val(data.comissao);

                    $(document).find("#produto_codigo_base").val('');
                    $(document).find("#produto_descricao_base").val('');
                    $(document).find("#produto_codigo_desenho").val('');
                    $(document).find("#produto_descricao_desenho").val('');

                    $(document).find("#produto_codigo").val('');
                    $(document).find('#campanha').html('');
                    $(document).find("#pecas_tamanho").val('');
                    $(document).find("#produto_descricao").val('');
                    $(document).find("#quantidade").val('');
                    $(document).find("#preco_unitario").val('');
                    $(document).find("#estoque_disponivel").val('');
                    $(document).find("#coluna").val('');

                    $(document).find('#ultimas-vendas-div').hide();
                    $(document).find('#alerta_preco_pedido').hide();
                    $(document).find('#alerta_preco_pedido').text("*");
                    
                    $(document).find("label[for='produto_descricao']").html('Descrição');

                    exibirBtnEnviarPedido();

                    $(document).find(".content_pecas").hide();
                    table_pecas_pedido.clear().draw();
                },
                error: function(data){
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        if (field == 'produto_codigo' && errors[field] == "1"){
                            showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                        }
                        else if (field == 'produto_codigo' && errors[field] == "2"){
                            showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                        }
                        else if (field == 'produto_codigo'){
                            showErrorsInputs(form, 'produto_descricao', errors[field])
                        }
                        else if (field == 'produto_codigo_base'){
                            showErrorsInputs(form, 'produto_descricao_base', errors[field])
                        }
                        else if (field == 'produto_codigo_desenho'){
                            showErrorsInputs(form, 'produto_descricao_desenho', errors[field])
                        }
                        else{
                            showErrorsInputs(form, field, errors[field])
                        }
                    }
                }
            }).always( function(){
                $(document).find('#produto_descricao').focus();
            });
        }
	}

    function editarProduto(obj, id){
        if($(document).find('#codigo_cliente').val() != '0000010069999'){
            $(document).find('#ultimas-vendas-div').show();
        }

        $(document).find("#btn-cancel-itens-pedido").off('click');
        $(document).find("#btn-cancel-itens-pedido").on('click', function() {
            $(document).find("#btn-create-itens-pedido").html('Inserir produto');
            $(document).find("#btn-cancel-itens-pedido").hide();

            $(document).find("#produto_pedido_id").val('');
            
            $(document).find("#produto_codigo_base").val('');
            $(document).find("#produto_descricao_base").val('');
            $(document).find("#produto_codigo_desenho").val('');
            $(document).find("#produto_descricao_desenho").val('');

            $(document).find("#produto_codigo").val('');
            $(document).find('#campanha').html('');
            $(document).find("#pecas_tamanho").val('');
            $(document).find("#produto_descricao").val('');

            $(document).find("#quantidade").val('');
            $(document).find("#preco_unitario").val('');
            $(document).find("#coluna").val('');
            $(document).find("#estoque_disponivel").val('');

            $(document).find('#ultimas-vendas-div').hide();

            table_produtos.row.add(window.temp_row).draw();
            exibirBtnEnviarPedido();
            form = $(document).find("#form_filter_itens");
            form.find('.error-message').remove();
            form.find('.error-input').removeClass('error-input');

            $(document).find(".content_pecas").hide();
            table_pecas_pedido.clear().draw();

        });
        if ($(document).find("#btn-cancel-itens-pedido").is(":visible")){
            table_produtos.row.add(window.temp_row).draw();
        }

        form = $(document).find("#form_filter_itens");
        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $(document).find("#btn-create-itens-pedido").html('Editar produto');
        $(document).find("#btn-cancel-itens-pedido").show();

        var row = table_produtos.row(obj);

        window.temp_row = row.data();
        comissao = window.temp_row[5];
        if(condicoes_especiais[$(document).find('#cadPedido').find('#tipo_venda').val()]){
            $(document).find("#produto_pedido_id").val(id);
            returnDadosEditar($(document).find("#produto_pedido_id").val());
        }else{
            if(comissao.indexOf("div") != -1){
                comissao = comissao.replace($(window.temp_row[5]).html() , '');
                comissao = comissao.replace('<div></div>' , '');
            }
    
            preco = obj.find('.preco').html();
            if(preco.indexOf("span") != -1){
                preco = preco.replace(/(<([^>]+)>)/ig,"");
                preco = preco.replace('*', '');
                preco = preco.replace(' ', '');
            }
            preco_original = obj.find('.preco').data('preco_original');
			if(preco_original != '0,00'){
				preco = preco_original;
			}


            $(document).find("#produto_pedido_id").val(id);
            $(document).find("#produto_codigo").val(obj.find('.codigo').html());
            $(document).find("#produto_descricao").val(obj.find('.descricao').html());
            $(document).find("#quantidade").val(obj.find('.estoque').html().replace(".", "").replace(".", ","));
            $(document).find("#preco_unitario").val(preco.replace(".", "").replace(".", ","));
            $(document).find("#coluna").val(obj.find('.comissao').html());
            
            retornaInformacoesPreco();
        }
        obj.find('.estoque').popover('hide');
        obj.find('.preco').popover('hide');
        row.remove().draw();
        exibirBtnEnviarPedido();
    }

    function totalizadores(){
		$.ajax({
			url: '{{ route('pedido_portal.totalizadores') }}',
			type: 'POST',
            async: false,
			data: {
				_token: '{{ csrf_token() }}',
				id: $(document).find('#id').val()
			},
			success: function(data){
				$(document).find("#total_itens").html(data.total_itens);
				$(document).find("#peso_total").html(data.peso_total);
				$(document).find("#total_produtos").html(data.valor_total_produtos);
				$(document).find("#total_frete").html(data.valor_total_frete);
				$(document).find("#valor_desconto").html(data.valor_desconto);                                
				$(document).find("#total_pedido").html(data.valor_total_pedido);
			}
		});
	}
	
    function produtoExcluir(obj, $id){
        $.ajax({
            url: '{{ Route("pedido_portal.item.excluir") }}',
            type: 'POST',
           data: {
                _token: '{{csrf_token()}}',
                id: $id,
            },
            success: function(){
                $(document).find("#modal_item_excluir").modal('hide');
                table_produtos.row(obj).remove().draw();
                exibirBtnEnviarPedido();
                totalizadores();
            },
            error: function(data){
                message('Atenção', data.responseJSON.message);
            }
        });
    }
    function recalcularTodosItens($mudanca = null){
        var form = $(document).find('#cadPedido');
        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');
        $.ajax({
            url: '{{ route('pedido_portal.recalcular_todos_itens') }}',
            type: 'POST',
           data: {
            	_token: '{{ csrf_token() }}',
                id: $(document).find("#id").val(),
                mudanca: null
            },
            success: function(data){
                table_produtos.clear().draw();
                message('Atenção', 'Perante as alterações do pedido o sistema vai recalcular valores, verificar antes de confirmar!');
				var fields_filter = [];
            	for(var field in data.response.itens){
                    var temp_field = [
                        data.response.itens[field].codigo,
                        data.response.itens[field].descricao,
                        data.response.itens[field].quantidade,
                        '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="">'+data.response.itens[field].preco_unitario+'</div></div>',
                        data.response.itens[field].valor_total,
                        data.response.itens[field].comissao,
                        createBtEditarProduto(data.response.itens[field]),
                        createBtExcluirProduto(data.response.itens[field])
                    ];
	                fields_filter.push(temp_field);
                }
                table_produtos.rows.add(fields_filter).draw().nodes();
                totalizadores();
            },
            error: function(data){
                if($mudanca != null){
                    showErrorsInputs(form, 'pedido_futuro', data.responseJSON.error.msg.pedido_futuro);
                    if($mudanca == 1){
                        $(document).find("#pedido_futuro_nao").prop('checked', true);
                    }
                    else if($mudanca == 2){
                        $(document).find("#pedido_futuro_sim").prop('checked', true);
                    }
                }
            }
        });
    }

    function apagarTodosItens(){
        $.ajax({
            url: '{{ route('pedido_portal.apagar_todos_itens') }}',
            type: 'POST',
           data: {_token: '{{ csrf_token() }}',
                id: $(document).find("#id").val()},
            success: function(){
                table_produtos.clear().draw();
				totalizadores();
            }
        });
    }

    function salvaEdicaoProduto(){
        form = $(document).find("#form_filter_itens");

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        var estabelecimento = $(document).find("#estabelecimento").val();

        if(estabelecimento == '31'){
            pecas_tamanho = $(document).find("#pecas_tamanho").val();

            if(pecas_tamanho != "" || !$.isEmptyObject(pecas_tamanho)){
                pecas_tamanho = parseFloat(pecas_tamanho);
            }else{
                pecas_tamanho = parseFloat("0.0");
            }

            if(pecas_tamanho > 0){
                quantidade = $(document).find("#quantidade").val();
                if(quantidade != "" || !$.isEmptyObject(pecas_tamanho)){
                    quantidade = parseFloat(quantidade);
                }else{
                    quantidade = pecas_tamanho;
                }

                if(quantidade%pecas_tamanho == 0){
                    $.ajax({
                        url: '{{ Route("pedido_portal.item.editar") }}',
                        type: 'POST',
                        data: {
                            _token: '{{csrf_token()}}',
                            id: $(document).find("#produto_pedido_id").val(),
                            produto_codigo: $(document).find("#produto_codigo").val(),
                            produto_codigo_base: $(document).find("#produto_codigo_base").val(),
                            produto_codigo_desenho: $(document).find("#produto_codigo_desenho").val(),
                            produto_descricao: $(document).find("#produto_descricao").val(),
                            estabelecimento: $(document).find("#estabelecimento").val(),
                            cliente: $(document).find("#codigo_cliente").val(),
                            pedido: $(document).find("#id").val(),
                            quantidade: $(document).find("#quantidade").val(),
                            preco_unitario: $(document).find("#preco_unitario").val()
                        },
                        success: function(data) {
                            if(data.campanha_nome.length > 0){
                                var field = [
                                    '<div><b class="codigo">'+data.codigo+'</b></div>',
                                    '<div><b class="descricao">'+data.descricao+'</b></div>',
                                    '<div><b>'+data.quantidade+'</b></div>',
                                    '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="" style="font-weight: bold">'+data.preco_unitario+'</div></div>',
                                    '<div><b>'+data.valor_total+'</b></div>',
                                    '<div><b><a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="Campanha '+data.campanha_nome+' '+data.campanha_informativo+'" style="color: black;"></a>' +data.comissao+'</b></div>',
                                    createBtEditarProduto(data),
                                    createBtExcluirProduto(data),
                                ];
                            }else{
                                var field = [
                                    data.codigo,
                                    data.descricao,
                                    data.quantidade,
                                    '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="">'+data.preco_unitario+'</div></div>',
                                    data.valor_total,
                                    data.comissao,
                                    createBtEditarProduto(data),
                                    createBtExcluirProduto(data),
                                ];
                            }
                            table_produtos.row.add(field).draw();
                    
                            $(document).find("#btn-create-itens-pedido").html('Inserir produto');
                            $(document).find("#btn-cancel-itens-pedido").hide();

                            $(document).find("#produto_codigo_base").val('');
                            $(document).find("#produto_descricao_base").val('');
                            $(document).find("#produto_codigo_desenho").val('');
                            $(document).find("#produto_descricao_desenho").val('');

                            $(document).find("#produto_pedido_id").val('');
                            $(document).find("#produto_codigo").val('');
                            $(document).find('#campanha').html('');
                            $(document).find("#pecas_tamanho").val('');
                            $(document).find("#produto_descricao").val('');
                            $(document).find("#quantidade").val('');
                            $(document).find("#preco_unitario").val('');
                            $(document).find("#estoque_disponivel").val('');
                            $(document).find("#coluna").val('');

                            $(document).find('#ultimas-vendas-div').hide();
                            $(document).find('#alerta_preco_pedido').hide();
                            $(document).find('#alerta_preco_pedido').text("*");
                            $(document).find("label[for='produto_descricao']").html('Descrição');

                            exibirBtnEnviarPedido();
                        },
                        error: function(data){
                            var errors = data.responseJSON.errors;
                            form.find('.error-message').remove();
                            form.find('.error-input').removeClass('error-input');
                            for(var field in errors){
                                if (field == 'produto_codigo' && errors[field] == "1"){
                                    showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                                }
                                else if (field == 'produto_codigo' && errors[field] == "2"){
                                    showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                                }
                                else if (field == 'produto_codigo'){
                                    showErrorsInputs(form, 'produto_descricao', errors[field])
                                }
                                else if (field == 'produto_codigo_base'){
                                    showErrorsInputs(form, 'produto_descricao_base', errors[field])
                                }
                                else if (field == 'produto_codigo_desenho'){
                                    showErrorsInputs(form, 'produto_descricao_desenho', errors[field])
                                }
                                else{
                                    showErrorsInputs(form, field, errors[field])
                                }
                            }
                        }
                    }).always( function(){
                        $(document).find('#produto_descricao').focus();
                    });
                }else{
                    var $class = "dialog_option_verificacao_fracionamento_peca";
                    var $name_option_ok_verificacao_fracionamento_peca = "ok_verificacao_fracionamento_peca";
                    var $name_option_cancelar_verificacao_fracionamento_peca = "cancelar_verificacao_fracionamento_peca";

                    $(document).off("ok_verificacao_fracionamento_peca");
                    $(document).on("ok_verificacao_fracionamento_peca", function(){
                        esconderPopoverTooltip();
                        $.ajax({
                            url: '{{ Route("pedido_portal.item.editar") }}',
                            type: 'POST',
                            data: {
                                _token: '{{csrf_token()}}',
                                id: $(document).find("#produto_pedido_id").val(),
                                produto_codigo: $(document).find("#produto_codigo").val(),
                                produto_codigo_base: $(document).find("#produto_codigo_base").val(),
                                produto_codigo_desenho: $(document).find("#produto_codigo_desenho").val(),
                                produto_descricao: $(document).find("#produto_descricao").val(),
                                estabelecimento: $(document).find("#estabelecimento").val(),
                                cliente: $(document).find("#codigo_cliente").val(),
                                pedido: $(document).find("#id").val(),
                                quantidade: $(document).find("#quantidade").val(),
                                preco_unitario: $(document).find("#preco_unitario").val()
                            },
                            success: function(data) {
                                if(data.campanha_nome.length > 0){
                                    var field = [
                                        '<div><b class="codigo">'+data.codigo+'</b></div>',
                                        '<div><b class"descricao">'+data.descricao+'</b></div>',
                                        '<div><b>'+data.quantidade+'</b></div>',
                                        '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="" style="font-weight: bold">'+data.preco_unitario+'</b></div></div>',
                                        '<div><b>'+data.valor_total+'</b></div>',
                                        '<div><b><a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="Campanha '+data.campanha_nome+' '+data.campanha_informativo+'" style="color: black;"></a>' +data.comissao+'</b></div>',
                                        createBtEditarProduto(data),
                                        createBtExcluirProduto(data),
                                    ];
                                }else{
                                    var field = [
                                        data.codigo,
                                        data.descricao,
                                        data.quantidade,
                                        '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="">'+data.preco_unitario+'</div></div>',
                                        data.valor_total,
                                        data.comissao,
                                        createBtEditarProduto(data),
                                        createBtExcluirProduto(data),
                                    ];
                                }
                                table_produtos.row.add(field).draw();
                        
                                $(document).find("#btn-create-itens-pedido").html('Inserir produto');
                                $(document).find("#btn-cancel-itens-pedido").hide();

                                $(document).find("#produto_codigo_base").val('');
                                $(document).find("#produto_descricao_base").val('');
                                $(document).find("#produto_codigo_desenho").val('');
                                $(document).find("#produto_descricao_desenho").val('');

                                $(document).find("#produto_pedido_id").val('');
                                $(document).find("#produto_codigo").val('');
                                $(document).find('#campanha').html('');
                                $(document).find("#pecas_tamanho").val('');
                                $(document).find("#produto_descricao").val('');
                                $(document).find("#quantidade").val('');
                                $(document).find("#preco_unitario").val('');
                                $(document).find("#estoque_disponivel").val('');
                                $(document).find("#coluna").val('');

                                $(document).find('#ultimas-vendas-div').hide();
                                $(document).find('#alerta_preco_pedido').hide();
                                $(document).find('#alerta_preco_pedido').text("*");
                                $(document).find("label[for='produto_descricao']").html('Descrição');

                                exibirBtnEnviarPedido();
                            },
                            error: function(data){
                                var errors = data.responseJSON.errors;
                                form.find('.error-message').remove();
                                form.find('.error-input').removeClass('error-input');
                                for(var field in errors){
                                    if (field == 'produto_codigo' && errors[field] == "1"){
                                        showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                                    }
                                    else if (field == 'produto_codigo' && errors[field] == "2"){
                                        showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                                    }
                                    else if (field == 'produto_codigo'){
                                        showErrorsInputs(form, 'produto_descricao', errors[field])
                                    }
                                    else if (field == 'produto_codigo_base'){
                                        showErrorsInputs(form, 'produto_descricao_base', errors[field])
                                    }
                                    else if (field == 'produto_codigo_desenho'){
                                        showErrorsInputs(form, 'produto_descricao_desenho', errors[field])
                                    }
                                    else{
                                        showErrorsInputs(form, field, errors[field])
                                    }
                                }
                            }
                        }).always( function(){
                            $(document).find('#produto_descricao').focus();
                        });
                    });

                    $(document).off("cancelar_verificacao_fracionamento_peca");
                    $(document).on("cancelar_verificacao_fracionamento_peca", function(){
                        return null; 
                    });
                    message_option("Atenção", "A peça será francionada, deseja continuar?", $class, $name_option_ok_verificacao_fracionamento_peca, '', $name_option_cancelar_verificacao_fracionamento_peca, '');
                    esconderPopoverTooltip();
                }
            }else{
                $.ajax({
                    url: '{{ Route("pedido_portal.item.editar") }}',
                    type: 'POST',
                    data: {
                        _token: '{{csrf_token()}}',
                        id: $(document).find("#produto_pedido_id").val(),
                        produto_codigo: $(document).find("#produto_codigo").val(),
                        produto_codigo_base: $(document).find("#produto_codigo_base").val(),
                        produto_codigo_desenho: $(document).find("#produto_codigo_desenho").val(),
                        produto_descricao: $(document).find("#produto_descricao").val(),
                        estabelecimento: $(document).find("#estabelecimento").val(),
                        cliente: $(document).find("#codigo_cliente").val(),
                        pedido: $(document).find("#id").val(),
                        quantidade: $(document).find("#quantidade").val(),
                        preco_unitario: $(document).find("#preco_unitario").val()
                    },
                    success: function(data) {
                        if(data.campanha_nome.length > 0){
                                var field = [
                                    '<div><b class="codigo">'+data.codigo+'</b></div>',
                                    '<div><b class"descricao">'+data.descricao+'</b></div>',
                                    '<div><b>'+data.quantidade+'</b></div>',
                                    '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="" style="font-weight: bold">'+data.preco_unitario+'</div></div>',
                                    '<div><b>'+data.valor_total+'</b></div>',
                                    '<div><b><a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="Campanha '+data.campanha_nome+' '+data.campanha_informativo+'" style="color: black;"></a>' +data.comissao+'</b></div>',
                                    createBtEditarProduto(data),
                                    createBtExcluirProduto(data),
                                ];
                            }else{
                                var field = [
                                    data.codigo,
                                    data.descricao,
                                    data.quantidade,
                                    '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="">'+data.preco_unitario+'</div></div>',
                                    data.valor_total,
                                    data.comissao,
                                    createBtEditarProduto(data),
                                    createBtExcluirProduto(data),
                                ];
                            }
                        table_produtos.row.add(field).draw();
                
                        $(document).find("#btn-create-itens-pedido").html('Inserir produto');
                        $(document).find("#btn-cancel-itens-pedido").hide();

                        $(document).find("#produto_codigo_base").val('');
                        $(document).find("#produto_descricao_base").val('');
                        $(document).find("#produto_codigo_desenho").val('');
                        $(document).find("#produto_descricao_desenho").val('');

                        $(document).find("#produto_pedido_id").val('');
                        $(document).find("#produto_codigo").val('');
                        $(document).find('#campanha').html('');
                        $(document).find("#pecas_tamanho").val('');
                        $(document).find("#produto_descricao").val('');
                        $(document).find("#quantidade").val('');
                        $(document).find("#preco_unitario").val('');
                        $(document).find("#estoque_disponivel").val('');
                        $(document).find("#coluna").val('');

                        $(document).find('#ultimas-vendas-div').hide();
                        $(document).find('#alerta_preco_pedido').hide();
                        $(document).find('#alerta_preco_pedido').text("*");
                        $(document).find("label[for='produto_descricao']").html('Descrição');

                        exibirBtnEnviarPedido();
                    },
                    error: function(data){
                        var errors = data.responseJSON.errors;
                        form.find('.error-message').remove();
                        form.find('.error-input').removeClass('error-input');
                        for(var field in errors){
                            if (field == 'produto_codigo' && errors[field] == "1"){
                                showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                            }
                            else if (field == 'produto_codigo' && errors[field] == "2"){
                                showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                            }
                            else if (field == 'produto_codigo'){
                                showErrorsInputs(form, 'produto_descricao', errors[field])
                            }
                            else if (field == 'produto_codigo_base'){
                                showErrorsInputs(form, 'produto_descricao_base', errors[field])
                            }
                            else if (field == 'produto_codigo_desenho'){
                                showErrorsInputs(form, 'produto_descricao_desenho', errors[field])
                            }
                            else{
                                showErrorsInputs(form, field, errors[field])
                            }
                        }
                    }
                }).always( function(){
                    $(document).find('#produto_descricao').focus();
                });
            }
        }else{
            $.ajax({
                url: '{{ Route("pedido_portal.item.editar") }}',
                type: 'POST',
                data: {
                    _token: '{{csrf_token()}}',
                    id: $(document).find("#produto_pedido_id").val(),
                    produto_codigo: $(document).find("#produto_codigo").val(),
                    produto_codigo_base: $(document).find("#produto_codigo_base").val(),
                    produto_codigo_desenho: $(document).find("#produto_codigo_desenho").val(),
                    produto_descricao: $(document).find("#produto_descricao").val(),
                    estabelecimento: $(document).find("#estabelecimento").val(),
                    cliente: $(document).find("#codigo_cliente").val(),
                    pedido: $(document).find("#id").val(),
                    quantidade: $(document).find("#quantidade").val(),
                    preco_unitario: $(document).find("#preco_unitario").val()
                },
                success: function(data) {
                    if(data.campanha_nome.length > 0){
                        var field = [
                            '<div><b class="codigo">'+data.codigo+'</b></div>',
                            '<div><b class"descricao">'+data.descricao+'</b></div>',
                            '<div><b>'+data.quantidade+'</b></div>',
                            '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="" style="font-weight: bold">'+data.preco_unitario+'</div></div>',
                            '<div><b>'+data.valor_total+'</b></div>',
                            '<div><b><a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="" data-original-title="Campanha '+data.campanha_nome+' '+data.campanha_informativo+'" style="color: black;"></a>' +data.comissao+'</b></div>',
                            createBtEditarProduto(data),
                            createBtExcluirProduto(data),
                        ];
                        }else{
                            var field = [
                                data.codigo,
                                data.descricao,
                                data.quantidade,
                                '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="">'+data.preco_unitario+'</div></div>',
                                data.valor_total,
                                data.comissao,
                                createBtEditarProduto(data),
                                createBtExcluirProduto(data),
                            ];
                        }
                    table_produtos.row.add(field).draw();
            
                    $(document).find("#btn-create-itens-pedido").html('Inserir produto');
                    $(document).find("#btn-cancel-itens-pedido").hide();

                    $(document).find("#produto_codigo_base").val('');
                    $(document).find("#produto_descricao_base").val('');
                    $(document).find("#produto_codigo_desenho").val('');
                    $(document).find("#produto_descricao_desenho").val('');

                    $(document).find("#produto_pedido_id").val('');
                    $(document).find("#produto_codigo").val('');
                    $(document).find('#campanha').html('');
                    $(document).find("#pecas_tamanho").val('');
                    $(document).find("#produto_descricao").val('');
                    $(document).find("#quantidade").val('');
                    $(document).find("#preco_unitario").val('');
                    $(document).find("#estoque_disponivel").val('');
                    $(document).find("#coluna").val('');

                    $(document).find('#ultimas-vendas-div').hide();
                    $(document).find('#alerta_preco_pedido').hide();
                    $(document).find('#alerta_preco_pedido').text("*");
                    $(document).find("label[for='produto_descricao']").html('Descrição');

                    exibirBtnEnviarPedido();
                },
                error: function(data){
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    form.find('.error-input').removeClass('error-input');
                    for(var field in errors){
                        if (field == 'produto_codigo' && errors[field] == "1"){
                            showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                        }
                        else if (field == 'produto_codigo' && errors[field] == "2"){
                            showErrorsInputs(form, 'produto_descricao', "Este produto já está no pedido. Para modificar seus valores, edite-o.")
                        }
                        else if (field == 'produto_codigo'){
                            showErrorsInputs(form, 'produto_descricao', errors[field])
                        }
                        else if (field == 'produto_codigo_base'){
                            showErrorsInputs(form, 'produto_descricao_base', errors[field])
                        }
                        else if (field == 'produto_codigo_desenho'){
                            showErrorsInputs(form, 'produto_descricao_desenho', errors[field])
                        }
                        else{
                            showErrorsInputs(form, field, errors[field])
                        }
                    }
                }
            }).always( function(){
                $(document).find('#produto_descricao').focus();
            });
        }
    }

    function salvarPedido(){
        form = $(document).find('#cadPedido');
        table = $(document).find('#table-filters-pedidos-itens');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route('pedido_portal.salvarPedido') }}',
            type: 'POST',
            data: form.serialize(),
            async: false,
            success: function(callback){
                $(document).find('#modal_pedido_edit').modal('hide');
                message('Sucesso', callback.message);
            },
            error: function(callback){
                var errors = callback.responseJSON.errors;
                form.find('.error-message').remove();
                form.find('.error-input').removeClass('error-input');
				if(callback.responseJSON.message != ''){
                    if(Object.keys(errors).length > 0){
                        for(var field in errors){
                            for(var codigo in errors[field]){
                                message("Atenção", errors[field][codigo]);
                            }
                        }
                    }else{
                        message('Atenção', callback.responseJSON.message);
                    }
				}
                count = 0;
                for(var field in errors){
                    if (field == 'estoque'){
                        for (var estoque_erro in errors['estoque']){
                            linha = table.find("tr:contains('"+estoque_erro+"')");
                            linha.addClass('error-tr');
                            estoque_celula = linha.find('.estoque');
                            estoque_celula.attr('data-original-title', 'Sem estoque')
                            estoque_celula.attr('data-content', errors['estoque'][estoque_erro]);
                        }
                    }
                    else if (field == 'preco'){
                        for (var preco_erro in errors['preco']){
                            linha = table.find("tr:contains('"+preco_erro+"')");
                            linha.addClass('error-tr');
                            preco_celula = linha.find('.preco');
                            preco_celula.attr('data-original-title', 'Preço inválido')
                            preco_celula.attr('data-content', errors['preco'][preco_erro]);
                        }
                    }
                    else if (field == 'total_pedido'){
                        $(document).find('#total_pedido').attr('data-original-title', 'Valor inválido');
                        $(document).find('#total_pedido').attr('data-content', 'O valor da nota deve ser maior que zero');
                    }
                    else{
						$(document).find("#pedido-web-header-tab").tab('show');
                        showErrorsInputs(form, field, errors[field]);
                        count++;
                    }
                }
                $(document).find('.estoque').popover('show');
                $(document).find('.preco').popover('show');
                $(document).find('#total_pedido').popover('show');

                $(document).find('.popover').on('click', function(){
                    $(document).find("[aria-describedby="+$(this).attr('id')+"]").popover('hide');
                });
            }
        });
    }

    function validarEstabelecimento(elemento){
        if(elemento.val() !== ''){
            $valor = elemento.val();
            if($valor == '5'){
                if(!$(document).find("#tipo_venda").not(".condicao_especial")){
                    $(document).find("#tipo_venda").find(".condicao_especial").remove();
                    var condicao_especial_html = '';
                    $.each(condicoes_especiais, function(key, value){
                        condicao_especial_html += '<option value="'+key+'" class="condicao_especial">'+value+'</option>';
                    });
                    $(document).find("#tipo_venda").append(condicao_especial_html);
                }
            }
            hideObservacao();
            if(
                $valor == '5' || 
                $valor == '8'
            ){
                showObservacao();
            }
            showCamposEmpresa();
        }else{
            hideCamposEmpresa();
        }
    }
    
    function modalUltimasVendasClientes(){
        $.ajax({
            url: '{{ route('consulta_ultimas_vendas_produto_cliente.dialog') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				hash_cliente: $(document).find("#id_cliente_encriptada").val(),
                codigo: $(document).find("#produto_codigo").val()
            }
        })
        .done(function(data) {
            $id = 'modal_ultimas_vendas';
            $title = 'Últimas vendas do Cliente: '+$(document).find("#nome_cliente").val();
            $body = data;
            $class = 'modal-lg';

            createModal($id, $title, $body, $class);
        });
    }

    @if(Auth::user()->tipo_usuario->nome != 'Representante')

    function importarArquivo(){

        var formdata = new FormData();

        formdata.append('_token', '{{ csrf_token() }}');
        formdata.append('pedido', $(document).find("#id").val());
        formdata.append('arquivo', $(document).find('#importar-csv').prop('files')[0], $(document).find('#importar-csv').val());

        $.ajax({
            url: '{{ route('codigo_barras.produto.pedido') }}',
			type: 'POST',
            data: formdata,
            processData: false,
            contentType: false,
        })
        .done( function (data){
            
            $(document).find('#btn-importar-csv').val('');
            
            table_produtos.clear().draw();
            var fields_filter = [];
            for(var field in data.response.itens){
                var temp_field = [
                    data.response.itens[field].codigo,
                    data.response.itens[field].descricao,
                    data.response.itens[field].quantidade,
                    '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="">'+data.response.itens[field].preco_unitario+'</div></div>',
                    data.response.itens[field].valor_total,
                    data.response.itens[field].comissao,
                    createBtEditarProduto(data.response.itens[field]),
                    createBtExcluirProduto(data.response.itens[field])
                ];
                fields_filter.push(temp_field);
            }
            table_produtos.rows.add(fields_filter).draw().nodes();
            totalizadores();
        })
        .fail( function(data){
            var erros = (data.responseJSON.errors);
            var mensagem_erro = '';
            for(var field in erros){
                mensagem_erro += erros[field] + "<br>";
            }
            message('Erro', mensagem_erro);
        });

    }
    @endif

    function showDesenho(){
        $(document).find("#content_desenhos").show();
        $(document).find('#produto_codigo').prop('disabled', 'disabled');
        $(document).find('#produto_descricao').prop('disabled', 'disabled');
        $(document).find('#cod_produto_group').addClass('produto_display_desenho');
        $(document).find('#pedido_futuro_sim').parent().hide();
        $(document).find('#data_previsao_entrega').val('');
        var data = moment().add(2, 'days');
        if(data.format("d") == '6'){
            data.add(2, 'days');
        }
        if(data.format("d") == '0'){
            data.add(1, 'days');
        }
        $(document).find('#data_previsao_entrega').val(data.format('DD/MM/YYYY'));
    }

    function hideDesenho(){
        $(document).find("#content_desenhos").hide();
        $(document).find('#produto_codigo').prop('disabled', '');
        $(document).find('#produto_descricao').prop('disabled', '');
        $(document).find('#cod_produto_group').removeClass('produto_display_desenho');
        $(document).find('#pedido_futuro_sim').parent().show();
    }

    function returnDadosEditar(id_item){
        $.ajax({
            url: '{{ route('pedido_portal.item.returnDados') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: id_item
            },
            success: function(callback){
                if(callback.status === 'success'){
                    dados = callback.response;
                    $(document).find("#produto_codigo_base").val(dados.tecidos_base.codigo);
                    $(document).find("#produto_descricao_base").val(dados.tecidos_base.descricao);
                    $(document).find("#produto_codigo_desenho").val(dados.desenho.codigo);
                    $(document).find("#produto_descricao_desenho").val(dados.desenho.descricao);

                    $(document).find("#produto_codigo").val(dados.final.codigo);
                    $(document).find("#produto_descricao").val(dados.final.descricao);
                    $(document).find("#quantidade").val(dados.quantidade);
                    $(document).find("#preco_unitario").val(dados.preco);
                    $(document).find("#estoque_disponivel").val(dados.estoque);
                    $(document).find("#coluna").val(dados.coluna);
                    $(document).find("#produto_pedido_id").val(dados.id);
                }
            },
            error: function(callback){
                var errors = callback.responseJSON.errors;
                form.find('.error-message').remove();
                form.find('.error-input').removeClass('error-input');
            }
        });
    }
    
    function showModalProdutoTecidoBaseModal(form_modal){
        $.ajax({
            url: '{{ route('produto.tecido_base.modal.buscar') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
                table_filters_produtos_busca.on('draw', function () {
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                        returnDadosProdutoTecidoBaseModal($(this), form_modal);
                    });

                });
            }
        });
    }

    function returnDadosProdutoTecidoBaseModal($dados, form_modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        form_modal.find('#produto_codigo_base').val($dados.find("td").eq(1).text());
        form_modal.find('#produto_descricao_base').val($dados.find("td").eq(2).text());
        retornaInformacoesPrecoDigital();
    }

    function showModalProdutoModal(form_modal, campo, condicao, input){
        $.ajax({
            url: '{{ route('produto.modal_pesquisa_limitado') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                campo: campo,
                condicao: condicao
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
                table_filters_produtos_busca.on('draw', function () {
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                        returnDadosProdutoModal($(this), form_modal, input);
                    });

                });
            }
        });
    }

    function returnDadosProdutoModal($dados, form_modal,input){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        if(input == "desenho"){
            form_modal.find('#produto_codigo_desenho').val($dados.find("td").eq(1).text());
            form_modal.find('#produto_descricao_desenho').val($dados.find("td").eq(2).text());
            retornaInformacoesPrecoDigital();
        }        
    }

	function validarRecalcular(elemento){
		if($(document).find('#table-filters-pedidos-itens > tbody').find("td:not(.dataTables_empty)").length > 0){
			saveOnChange($(document).find('#codigo_cliente'), true);
		} else {
			saveOnChange($(document).find('#codigo_cliente'), false);
		}
	}

    function retornaInformacaoProduto(campo_produto, campo_descricao){
        if (
            $(campo_produto).data('oldvalue') != $(campo_produto).val() &&
            $(campo_produto).val() != ''
        ){
            $(campo_descricao).val('');
            $.ajax({
                url: '{{ route('produto.pesquisaprodutocodigoespecificacao') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    codigo_produto: $(campo_produto).val()
                },
                success: function (callback){
                    if(callback.status == 'sucess'){
                        $(campo_descricao).val(callback.response.descricao);
                        retornaInformacoesPrecoDigital(campo_produto);
                    }
                }
            });
        }
    }
    function mostrarPecas($campo_quantidade){
        $quantidade = $($campo_quantidade).val();
        $estabelecimento = parseInt($(document).find('#estabelecimento').val());
        $estabelecimento_pecas = [{{ implode(', ', $estabelecientos_pecas) }}];
        $produto = $(document).find("#produto_codigo").val();
        $operacao = ['pronta_entrega_venda', 'pronta_entrega_triangular'];
        $(document).find(".content_pecas").hide();
        if(
            $quantidade <= "{{ $quantidade_ver_pecas }}" &&
            $estabelecimento_pecas.indexOf($estabelecimento) > -1 &&
            $produto != '' &&
            $operacao.indexOf($(document).find('#tipo_venda').val()) > -1
        ){
            $.ajax({
                url: '{{ route('produto.pecas_pedidos') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    produto: $produto,
                    estabelecimento: $estabelecimento,
                    quantidade: $quantidade
                },
                success: function (callback){
			        table_pecas_pedido.clear().draw();
                    if(callback.status == 'success'){
                        message('Atenção', 'Existem peças fracionadas');
                        var dados = callback.response;
                        $(document).find(".content_pecas").show();
                        var fields_filter = [];
                        for(var field in dados){
                            var temp_field = [
                                dados[field].codigo,
                                dados[field].quantidade,
                                dados[field].localização
                            ];
                            fields_filter.push(temp_field);
                        }
                        table_pecas_pedido.rows.add(fields_filter).draw().nodes();
                    }
                }
            });
        }
    }
	function showCondicaoPagamento(){
		$(document).find('.condicao_pagamento').show();
	}
	function hideCondicaoPagamento(){
		$(document).find('.condicao_pagamento').hide();
	}
	function mensagemCartao(){
		var $class = "dialog_option_deletar";
		var $name_option_sim = "mensagem_presencial_sim";
		var $option_sim = "";
		var $name_option_nao = "mensagem_presencial_nao";
		var $option_nao = "";

		$(document).find('#cartao').val('true');
		message_sim_nao("Atenção", "Venda presencial?", $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao);

		$(document).off("mensagem_presencial_sim");
		$(document).on("mensagem_presencial_sim", function(){
			$(document).find('#presencial').val('true');
			saveOnChange($(document).find('#presencial'));
		});

		$(document).off("mensagem_presencial_nao");
		$(document).on("mensagem_presencial_nao", function(){
			$(document).find('#presencial').val('false');
			saveOnChange($(document).find('#presencial'));
		});
	}
	
	function showClienteBloqueado(){
		$(document).find('#condicao_pagamento_blacklist').show();
		$(document).find('.cliente_not_balcao#condicao_pagamento_group').hide();
		$(document).find('input[name="condicao_pagamento"]').attr('disabled', 'disabled');
		$(document).find('select[name="condicao_pagamento"]').removeAttr('disabled');
	}
	
	function hideClienteBloqueado(){
		$(document).find('#condicao_pagamento_blacklist').hide();
		$(document).find('.cliente_not_balcao#condicao_pagamento_group').show();
		$(document).find('select[name="condicao_pagamento"]').attr('disabled', 'disabled');
		$(document).find('input[name="condicao_pagamento"]').removeAttr('disabled');
	}

	function validaRedespacho(elemento){
		if(elemento == 'true'){
			$(document).find('.transportadora_retira_horario').hide();
		} else {
			$(document).find('.transportadora_retira_horario').show();
		}
	}
	function showObservacao(){
		$(document).find('.observacao_pedido').show();
	}
	function hideObservacao(){
		$(document).find('.observacao_pedido').hide();
	}
	function showTelefone(){
		$(document).find('.cliente_telefone').show();
	}
	function hideTelefone(){
		$(document).find('.cliente_telefone').hide();
	}
	function showMetragemExata(){
		$(document).find('.metragem_exata').show();
	}
	function hideMetragemExata(){
		$(document).find('.metragem_exata').hide();
	}

    function propostaLink(){
        form = $(document).find('#cadPedido');
        table = $(document).find('#table-filters-pedidos-itens');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route('pedido_portal.gerar_proposta') }}',
            type: 'POST',
            data: form.serialize(),
            success: function(callback){
                $(document).find('#modal_pedido_edit').modal('hide');
                propostaLinkModal();
            },
            error: function(callback){
                var errors = callback.responseJSON.errors;
                form.find('.error-message').remove();
                form.find('.error-input').removeClass('error-input');
				if(callback.responseJSON.message != ''){
                	message('Atenção', callback.responseJSON.message);
				}
                count = 0;
                for(var field in errors){
                    if (field == 'estoque'){
                        for (var estoque_erro in errors['estoque']){
                            linha = table.find("tr:contains('"+estoque_erro+"')");
                            linha.addClass('error-tr');
                            estoque_celula = linha.find('.estoque');
                            estoque_celula.attr('data-original-title', 'Sem estoque')
                            estoque_celula.attr('data-content', errors['estoque'][estoque_erro]);
                        }
                    }
                    else if (field == 'preco'){
                        for (var preco_erro in errors['preco']){
                            linha = table.find("tr:contains('"+preco_erro+"')");
                            linha.addClass('error-tr');
                            preco_celula = linha.find('.preco');
                            preco_celula.attr('data-original-title', 'Preço inválido')
                            preco_celula.attr('data-content', errors['preco'][preco_erro]);
                        }
                    }
                    else if (field == 'total_pedido'){
                        $(document).find('#total_pedido').attr('data-original-title', 'Valor inválido');
                        $(document).find('#total_pedido').attr('data-content', 'O valor da nota deve ser maior que zero');
                    }
                    else{
						$(document).find("#pedido-web-header-tab").tab('show');
                        showErrorsInputs(form, field, errors[field]);
                        count++;
                    }
                }
                $(document).find('.estoque').popover('show');
                $(document).find('.preco').popover('show');
                $(document).find('#total_pedido').popover('show');

                $(document).find('.popover').on('click', function(){
                    $(document).find("[aria-describedby="+$(this).attr('id')+"]").popover('hide');
                });
            }
        });
    }

    function propostaLinkModal(){
        form = $(document).find('#cadPedido');
        $.ajax({
            url: '{{ route('pedido_portal.gerar_proposta_link') }}',
            method: 'GET',
            data: form.serialize(),
            success: function(body){
                var title = 'Proposta Link';
                createModal('modal_proposta_link', title, body, '');
            }
        });
    }

</script>
@endsection
