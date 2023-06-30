@extends('layouts.page-dialog')

@section('content')
@if (!empty($dados['motivo']))
<div class="alert alert-danger" role="alert">
	<p>Este projeto foi rejeitado.<br>
	Motivo: {{$dados['motivo']}}</p> 
</div>
@endif
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link active" id='lancamento-projeto-header-tab' data-toggle="tab" href="#lancamento_projeto_header" role="tab" aria-controls="lancamento_projeto_header" aria-selected="true">Projeto</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" id="lancamento-projeto-produto-tab" data-toggle="tab" href="#lancamento_projeto_produto" role="tab" aria-controls="lancamento_projeto_produto" aria-selected="false">Produto</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" id="lancamento-projeto-tecido-tab" data-toggle="tab" href="#lancamento_projeto_tecido" role="tab" aria-controls="lancamento_projeto_tecido" aria-selected="false">Tecidos</a>
    </li>
    <li class="nav-item">
		<a class="nav-link" id='lancamento-projeto-insumo-tab' data-toggle="tab" href="#lancamento_projeto_insumo" role="tab" aria-controls="lancamento_projeto_insumo" aria-selected="false">Insumos/Acessórios</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" id="lancamento-projeto-servico-tab" data-toggle="tab" href="#lancamento_projeto_servico" role="tab" aria-controls="lancamento_projeto_servico" aria-selected="false">Serviços</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" id="lancamento-projeto-resultado-tab" data-toggle="tab" href="#lancamento_projeto_resultado" role="tab" aria-controls="lancamento_projeto_resultado" aria-selected="false">Resultado</a>
	</li>
</ul>

<div class="tab-content pt-3" id="LancamentoProjetoHeaderContainer">

	<div class="tab-pane show active" id="lancamento_projeto_header" role="tabpanel" aria-labelledby="dados-tab">
		<form action="#" method="post" id="cadprojeto" name="cadprojeto" class="cadPedido" onsubmit="return false">
            @csrf
            {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
            {!! Form::hidden('id_representante', $dados['representante'], ['id' => 'id_representante']) !!}
            {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
            <div class="form-row">
                <div class="form-group col-sm-2 content-not-estabel">
                    {!! Form::label('numero_projeto', 'Número do Projeto') !!}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                    {!! Form::text('numero_projeto', $dados['numero_projeto'], ['id' => 'numero_projeto', 'class' => 'form-control essencial input-label text-right', 'disabled' => 'disabled']) !!}
                </div>
                <div class="form-group col-sm-5 content-not-estabel">
                    {!! Form::label('nome_projeto', 'Nome do Projeto') !!}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                    {!! Form::text('nome_projeto', $dados['nome_projeto'], ['id' => 'nome_projeto', 'class' => 'form-control essencial input-label']) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-9 content-not-estabel">
					{{ Form::label('nome_cliente', 'Cliente') }} <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
					<div class="input-group" id="cod_cliente_group">
						{{ Form::text('nome_cliente', $dados['cliente_descricao'], ['id' => 'nome_cliente', 'class' => 'form-control essencial input-label', 'placeholder' => '']) }}
						{{ Form::hidden('codigo_cliente', $dados['cliente'], ['id' => 'codigo_cliente', 'class' => '']) }}
						<span class="input-group-addon border rounded-right" id="bt-search-cliente" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
					</div>
                </div>
                <div class="form-group col-sm-3 content-not-estabel">
                    {{ Form::label('num_pedido', 'Pedido do Cliente') }}
                    {{ Form::text('num_pedido', $dados['pedido'], ['id' => 'num_pedido', 'class' => 'form-control essencial input-label', 'placeholder' => '']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-9">
                    {{ Form::label('condicao_pagamento_descr', 'Condição de Pagamento', []) }}	 <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                    <div class="input-group" id="condicao_pagamento_group">
                        {{ Form::text('condicao_pagamento_descr', $dados['pagamento_descricao'], array('id' => 'condicao_pagamento_descr', 'class' => 'form-control essencial')) }}
                        {{ Form::hidden('condicao_pagamento', $dados['pagamento'], ['id' => 'condicao_pagamento', 'class' => ''])}}
                        <span class="input-group-addon border rounded-right" id="bt-search-condicao_pagamento" data-route="{{ route("condicoes_pagamento_web.dialog") }}"><i id="bt-view-condicao" class="bt-view m-2"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 mt-5" id="button-bottom">
                    <button type="button" id="bt_ir_para_produtos" class="btn btn-info troca-aba float-right">Ir para produtos >></button>
            </div>
            <div class="col-sm-12 mt-5" id="button-bottom">
                <button type="button" id="btn_reprovar_projeto" class="btn btn-danger float-left">Reprovar Projeto</button>
                @if(!empty($mostrar_botao_aprovar))
                <button type="button" id="btn_aprovar_projeto" class="btn btn-success float-right">Aprovar Projeto</button>
                @endif
            </div>
		</form>
    </div>
    
    <div class="tab-pane" id="lancamento_projeto_produto" role="tabpanel" aria-labelledby="dados-tab">
        <div class="content-filter-dialog">	
            <form action="post" name="form_filter_projeto_produto" class="cadPedido" id="form_filter_projeto_produto" onsubmit="return false;">
                <p><strong>Inserir Produto</strong></p>
                @csrf
                {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
                {!! Form::hidden('id_produto', '', ['id' => 'id_produto']) !!}
                {!! Form::hidden('produto_ficha', '', ['id' => 'produto_ficha']) !!}
                {!! Form::hidden('id_codigo', '', ['id' => 'id_codigo']) !!}
                {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
                <div class="form-row condicao_media_itens_pedido">
                    <div class="col-sm-2">
                        <strong><span id="num_projeto" class='ml-2'>Nr. Projeto: {{ $dados['numero_projeto'] }}</span></strong>
                    </div>
                </div>
                <div class="form-row mt-3">
                    <div class="form-group col-sm-3">
                        {{ Form::label('produtos_codigo', 'Código do produto', []) }}
                        <div class="input-group" id="cod_produto_group">
                            {{ Form::text('produto_codigo', '', ['id' => 'produto_codigo', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Código', "maxlength" => "60"]) }}
                            <span class="input-group-addon border rounded-right" id="bt-buscar-produto"><i class="bt-view m-2"></i></span>
                        </div>
                    </div>
                    <div class="form-group col-sm-4">
                        {{ Form::label('produto_descricao', 'Descrição', []) }}
                        {{ Form::text('produto_descricao', '', ['id' => 'produto_descricao', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Nome do produto', 'maxlength' => '120']) }}
                        {{ Form::hidden('produto_descricao_hidden', '', ['id' => 'produto_descricao_hidden']) }}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('produto_preco_venda', 'Preço de Venda', []) }}
                        {{ Form::text('produto_preco_venda', '', ['id' => 'produto_preco_venda', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Preço de Venda', 'maxlength' => '8']) }}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('produto_quantidade', 'Quantidade', []) }}
                        {{ Form::text('produto_quantidade', '', ['id' => 'produto_quantidade', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Quantidade', 'maxlength' => '8']) }}
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-2">
                        {{ Form::label('produto_ncm', 'NCM', []) }}
                        {{ Form::text('produto_ncm', '', ['id' => 'produto_ncm', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'NCM', 'maxlength' => '8']) }}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('produto_peso', 'Peso', []) }}
                        {{ Form::text('produto_peso', '', ['id' => 'produto_peso', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Peso', 'maxlength' => '8']) }}
                    </div>
                    <div class="form-group col-sm-8">
                        {{ Form::label('produto_detalhes', 'Detalhes de Produção', []) }}
                        {{ Form::text('produto_detalhes', '', ['id' => 'produto_detalhes', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Detalhes de Produção', 'maxlength' => '250']) }}
                    </div>
                </div>
                <div class="content-buttons">
                    <button name="btn-create" id="btn-create-produto-projeto" class="btn-create">Inserir produto</button>
                    <button name="btn-cancel" id="btn-cancel-produto-projeto" class="btn-cancel">Cancelar</button>
                </div>
            </form>
        </div>
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped" id="table-filters-produtos">
                    <thead>
                        <tr>
                            <th class="td_codigo_produto">Código</th>
                            <th>Descrição</th>
                            <th class="tb_number">NCM</th>
                            <th class="tb_number">Peso</th>
                            <th class="tb_number">Preço Venda</th>
                            <th class="tb_number">Quantidade</th>
                            <th class="td_acao"></th>
                            <th class="td_acao"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($produtos_tabela))
                            @foreach($produtos_tabela as $produto)
                                <tr>
                                    <td>{{ $produto['codigo'] }}</td>
                                    <td class="detalhe_produto">{{ $produto['descricao'] }} @if(!empty($produto['detalhe_producao']))<a href="#" class="bt-detalhe" data-toggle="popover" data-placement="top" data-title="Detalhes de Produção" data-content="<p>{{ $produto['detalhe_producao'] }}"></a>@endif</td>
                                    <td class="tb_number">{{ $produto['ncm'] }}</td>
                                    <td class="tb_number">{{ $produto['peso'] }}</td>
                                    <td class="tb_number">{{ $produto['preco_venda'] }}</td>
                                    <td class="tb_number">{{ $produto['quantidade'] }}</td>
                                    <td><a href="#" class="bt-edit" title='Editar' onclick="editarProdutoNoProjeto($(this).parents('tr'), '{{ $produto['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                    <td><a href="#" class="bt-delete" title='Excluir' onclick="excluirProdutoNoProjeto($(this).parents('tr'), '{{ $produto['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="td_codigo_produto"></th>
                            <th></th>
                            <th class="tb_number">Total:</th>
                            <td class="tb_number" id='produto_total_exibicao'>{{ $produto_total_ex }}</td>
                            <th class="td_acao"></th>
                            <th class="td_acao"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-sm-12 mt-5" id="button-bottom">
                <button type="button" id="bt_ir_para_cabecalho" class="btn troca-aba btn-info"><< Voltar para o cabeçalho</button>
                <button type="button" id="bt_ir_para_tecidos" class="btn troca-aba btn-info float-right">Avançar para o tecido >></button>
            </div>
            <div class="col-sm-12 mt-1" id="button-bottom">
                <button type="button" id="btn_reprovar_projeto_produto" class="btn btn-danger float-left">Reprovar Projeto</button>
                @if(!empty($mostrar_botao_aprovar))
                <button type="button" id="btn_aprovar_projeto_produto" class="btn btn-success float-right">Aprovar Projeto</button>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-pane" id="lancamento_projeto_tecido" role="tabpanel" aria-labelledby="dados-tab">
        <div class="content-filter-dialog">	
            <form action="post" name="form_filter_projeto_tecido" class="cadPedido" id="form_filter_projeto_tecido" onsubmit="return false;">
                <p><strong>Inserir Tecidos</strong></p>
                <div class="form-row condicao_media_itens_pedido">
                    <div class="col-sm-2">
                        <strong><span id="num_projeto" class='ml-2'>Nr. Projeto: {{ $dados['numero_projeto'] }}</span></strong>
                    </div>
                    <div class="col-sm-2">
                        <strong><span id="estabelecimento_exibicao" class='ml-2'>{{ $dados['estabelecimento'] }}</span></strong>
                    </div>
                    <div class="col-sm-2">
                        <strong>UF destino:   </strong>
                        <span id="estado_destino">{{ $dados['estado_destino'] }}<br></span>
                    </div>
                    <div class="col-sm-2">
                        <strong>Prazo médio:   </strong>
                        <span id="media_condicao_pagamento">{{ $dados['media_condicao_pagamento'] }}</span>
                    </div>
                    <div class="col-sm-1">
                        <strong>Preço:   </strong>
                        <span id="preco_cif_fob">{{ $dados['preco_cif_fob'] }}</span>
                    </div>
                    <div class="col-sm-1">
                        <strong>Frete:   </strong>
                        <span id="cif_fob">{{ $dados['cif_fob'] }}</span>
                    </div>
                </div>
                @csrf
                {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
                {!! Form::hidden('id_tecido', '', ['id' => 'id_tecido']) !!}
                {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
                <div class="form-row mt-3">
                    <div class="form-group col-sm-2">
                        {{ Form::label('tecidos_codigo', 'Código do Tecido', []) }}
                        <div class="input-group" id="cod_tecido_group">
                            {{ Form::text('tecido_codigo', '', ['id' => 'tecido_codigo', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Código', "maxlength" => "250"]) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-tecido"  data-route="{{ route("produto.tecido.modal.buscar") }}"><i class="bt-view m-2"></i></span>
                        </div>
                    </div>
                    <div class="form-group col-sm-3">
                        {{ Form::label('tecido_descricao', 'Descrição', []) }}
                        {{ Form::text('tecido_descricao', '', ['id' => 'tecido_descricao', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Nome do tecido']) }}
                    </div>
                    <div class="form-group col-sm-3">
                        {!! Form::label('tecido_produto', 'Produto') !!}
                        {!! Form::select('tecido_produto', $produto_select, '', ['id' => 'tecido_produto', 'class' => 'form-control']) !!}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('tecido_consumo', 'Consumo por Peça', []) }}
                        {{ Form::text('tecido_consumo', '', ['id' => 'tecido_consumo', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Consumo', 'maxlength' => '8']) }}
                    </div>
                </div>
                <div class="content-dialog-table">
                    <div class="content-table">
                        <table class="table table-striped"  id="table-estabelecimentos-tecido">
                            <thead>
                                <tr>
                                    <th>Estabelecimento</th>
                                    <th class="tb_number">Estoque</th>
                                    <th class="tb_number">Compras</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="form-row mt-1">
                    <div class="form-group col-sm-2">
                        {{ Form::label('tecido_preco_unitario', 'Custo Unitário', []) }}
                        {{ Form::text('tecido_preco_unitario', '', ['id' => 'tecido_preco_unitario', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Custo unitário', 'maxlength' => '8', 'disabled' => 'disabled']) }}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('tecido_quantidade', 'Quantidade', []) }}
                        {{ Form::text('tecido_quantidade', '', ['id' => 'tecido_quantidade', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Quantidade do Produto', 'maxlength' => '8', 'disabled' => 'disabled']) }}
                    </div>
                    <div class="form-group col-sm-3">
                        {{ Form::label('tecido_consumo_total', 'Consumo Total') }}
                        {{ Form::text('tecido_consumo_total', '', ['id' => 'tecido_consumo_total', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Custo Total', 'disabled' => 'disabled']) }}
                    </div>
                    <div class="form-group col-sm-3">
                        {{ Form::label('tecido_valor_total', 'Custo Total') }}
                        {{ Form::text('tecido_valor_total', '', ['id' => 'tecido_valor_total', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Custo Total', 'disabled' => 'disabled']) }}
                    </div>
                </div>
                <div class="content-buttons">
                    <button name="btn-create" id="btn-create-tecido-projeto" class="btn-create">Inserir tecido</button>
                    <button name="btn-cancel" id="btn-cancel-tecido-projeto" class="btn-cancel">Cancelar</button>
                </div>
            </form>
        </div>
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped table-filters-tecidos" id="table-filters-tecidos">
                    <thead>
                        <tr>
                            <th class="td_codigo_produto">Código</th>
                            <th>Descrição</th>
                            <th>Ref. Produto</th>
                            <th class="tb_number">Custo unitário</th>
                            <th class="tb_number">Consumo por Peça</th>
                            <th class="tb_number">Consumo Total</th>
                            <th class="tb_number">Custo Total</th>
                            <th class="td_acao"></th>
                            <th class="td_acao"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($tecidos_tabela))
                            @foreach($tecidos_tabela as $tecido)
                                <tr>
                                    <td>{{ $tecido['codigo'] }}</td>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $tecido['descricao'] }}'>{{ $tecido['descricao'] }}</div></div></td>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $tecido['produto'] }}'>{{ $tecido['produto'] }}</div></div></td>
                                    <td>{{ $tecido['preco_unitario'] }}</td>
                                    <td>{{ $tecido['consumo_unitario'] }}</td>
                                    <td>{{ $tecido['consumo_total'] }}</td>
                                    <td>{{ $tecido['total_custo'] }}</td>
                                    <td><a href="#" class="bt-edit" title='Editar' onclick="editarTecidoNoProjeto($(this).parents('tr'), '{{ $tecido['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                    <td><a href="#" class="bt-delete" title='Excluir' onclick="excluirTecidoNoProjeto($(this).parents('tr'), '{{ $tecido['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="td_codigo_produto"></td>
                            <td></td>
                            <td></td>
                            <td class="tb_number"></td>
                            <td class="tb_number"></td>
                            <td class="tb_number">Total:</td>
                            <td class="tb_number" id='tecido_total_exibicao'>{{ $tecido_total_ex }}</td>
                            <td class="td_acao"></td>
                            <td class="td_acao"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-sm-12 mt-5" id="button-bottom">
                <button type="button" id="bt_ir_para_produtos" class="btn troca-aba btn-info"><< Voltar para o produto</button>
                <button type="button" id="bt_ir_para_insumos" class="btn troca-aba btn-info float-right">Avançar para o insumos/acessórios>></button>
            </div>
            <div class="col-sm-12 mt-1" id="button-bottom">
                <button type="button" id="btn_reprovar_projeto_tecido" class="btn btn-danger float-left">Reprovar Projeto</button>
                @if(!empty($mostrar_botao_aprovar))
                <button type="button" id="btn_aprovar_projeto_tecido" class="btn btn-success float-right">Aprovar Projeto</button>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-pane" id="lancamento_projeto_insumo" role="tabpanel" aria-labelledby="dados-tab">
        <div class="content-filter-dialog">	
            <form action="post" name="form_filter_projeto_insumo" class="cadPedido" id="form_filter_projeto_insumo" onsubmit="return false;">
                <p><strong>Inserir Insumos/Acessórios</strong></p>
                <div class="form-row condicao_media_itens_pedido">
                    <div class="col-sm-2">
                        <strong><span id="num_projeto" class='ml-2'>Nr. Projeto: {{ $dados['numero_projeto'] }}</span></strong>
                    </div>
                    <div class="col-sm-2">
                        <strong><span id="estabelecimento_exibicao" class='ml-2'>{{ $dados['estabelecimento'] }}</span></strong>
                    </div>
                    <div class="col-sm-2">
                        <strong>UF destino:   </strong>
                        <span id="estado_destino">{{ $dados['estado_destino'] }}<br></span>
                    </div>
                    <div class="col-sm-2">
                        <strong>Prazo médio:   </strong>
                        <span id="media_condicao_pagamento">{{ $dados['media_condicao_pagamento'] }}</span>
                    </div>
                    <div class="col-sm-1">
                        <strong>Preço:   </strong>
                        <span id="preco_cif_fob">{{ $dados['preco_cif_fob'] }}</span>
                    </div>
                    <div class="col-sm-1">
                        <strong>Frete:   </strong>
                        <span id="cif_fob">{{ $dados['cif_fob'] }}</span>
                    </div>
                </div>
                @csrf
                {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
                {!! Form::hidden('id_insumo', '', ['id' => 'id_insumo']) !!}
                {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
                <div class="form-row mt-3">
                    <div class="form-group col-sm-2">
                        {{ Form::label('insumo_codigo', 'Código do Insumo/Acessórios', []) }}
                        <div class="input-group" id="cod_insumo_group">
                            {{ Form::text('insumo_codigo', '', ['id' => 'insumo_codigo', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Código', "maxlength" => "250"]) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-insumo" data-route="{{ route("produto.insumo.modal.buscar") }}"><i class="bt-view m-2"></i></span>
                        </div>
                    </div>
                    <div class="form-group col-sm-3">
                        {{ Form::label('insumo_descricao', 'Descrição', []) }}
                        {{ Form::text('insumo_descricao', '', ['id' => 'insumo_descricao', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Nome do insumo']) }}
                    </div>
                    <div class="form-group col-sm-3">
                        {!! Form::label('insumo_produto', 'Produto') !!}
                        {!! Form::select('insumo_produto', $produto_select, '', ['id' => 'insumo_produto', 'class' => 'form-control']) !!}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('insumo_consumo', 'Consumo Total', []) }}
                        {{ Form::text('insumo_consumo', '', ['id' => 'insumo_consumo', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Consumo', 'maxlength' => '8']) }}
                    </div>
                </div>
                <div class="content-dialog-table">
                    <div class="content-table">
                        <table class="table table-striped"  id="table-estabelecimentos-insumo">
                            <thead>
                                <tr>
                                    <th>Estabelecimento</th>
                                    <th class="tb_number">Estoque</th>
                                    <th class="tb_number">Compras</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="form-row mt-3">
                    <div class="form-group col-sm-2">
                        {{ Form::label('insumo_preco_unitario', 'Custo Unitário', []) }}
                        {{ Form::text('insumo_preco_unitario', '', ['id' => 'insumo_preco_unitario', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Custo unitário', 'maxlength' => '8', 'disabled' => 'disabled']) }}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('insumo_quantidade', 'Quantidade de Produto', []) }}
                        {{ Form::text('insumo_quantidade', '', ['id' => 'insumo_quantidade', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Custo unitário', 'maxlength' => '8', 'disabled' => 'disabled']) }}
                    </div>
                    <div class="form-group col-sm-3">
                        {{ Form::label('insumo_valor_total', 'Custo Total') }}
                        {{ Form::text('insumo_valor_total', '', ['id' => 'insumo_valor_total', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Custo Total', 'disabled' => 'disabled']) }}
                    </div>
                </div>
                <div class="content-buttons">
                    <button name="btn-create" id="btn-create-insumo-projeto" class="btn-create">Inserir insumo/Acessório</button>
                    <button name="btn-cancel" id="btn-cancel-insumo-projeto" class="btn-cancel">Cancelar</button>
                </div>
            </form>
        </div>
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped table-filter-projeto-insumos" id="table-filters-projeto-insumos">
                    <thead>
                        <tr>
                            <th class="td_codigo_produto">Código</th>
                            <th>Descrição</th>
                            <th>Ref. Produto</th>
                            <th class="tb_number">Custo unitário</th>
                            <th class="tb_number">Consumo Total</th>
                            <th class="tb_number">Custo Total</th>
                            <th class="td_acao"></th>
                            <th class="td_acao"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($insumos_tabela))
                            @foreach($insumos_tabela as $insumo)
                                <tr>
                                    <td>{{ $insumo['codigo'] }}</td>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $insumo['descricao'] }}'>{{ $insumo['descricao'] }}</div></div></td>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $insumo['produto'] }}'>{{ $insumo['produto'] }}</div></div></td>
                                    <td>{{ $insumo['preco_unitario'] }}</td>
                                    <td>{{ $insumo['consumo_total'] }}</td>
                                    <td>{{ $insumo['total_custo'] }}</td>
                                    <td><a href="#" class="bt-edit" title='Editar' onclick="editarInsumoNoProjeto($(this).parents('tr'), '{{ $insumo['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                    <td><a href="#" class="bt-delete" title='Excluir' onclick="excluirInsumoNoProjeto($(this).parents('tr'), '{{ $insumo['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="td_codigo_produto"></td>
                            <td></td>
                            <td></td>
                            <td class="tb_number"></td>
                            <td class="tb_number">Total:</td>
                            <td class="tb_number" id='insumo_total_exibicao'>{{ $insumo_total_ex }}</td>
                            <td class="td_acao"></td>
                            <td class="td_acao"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-sm-12 mt-5" id="button-bottom">
                <button type="button" id="bt_ir_para_tecidos" class="btn troca-aba btn-info"><< Voltar para o tecido</button>
                <button type="button" id="bt_ir_para_servicos" class="btn troca-aba btn-info float-right">Avançar para o serviço >></button>
            </div>
            <div class="col-sm-12 mt-1" id="button-bottom">
                <button type="button" id="btn_reprovar_projeto_insumo" class="btn btn-danger float-left">Reprovar Projeto</button>
                @if(!empty($mostrar_botao_aprovar))
                    <button type="button" id="btn_aprovar_projeto_insumo" class="btn btn-success float-right">Aprovar Projeto</button>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-pane" id="lancamento_projeto_servico" role="tabpanel" aria-labelledby="dados-tab">
        <div class="content-filter-dialog">	
            <form action="post" name="form_filter_projeto_servico" class="cadPedido" id="form_filter_projeto_servico" onsubmit="return false;">
                @csrf
                {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
                {!! Form::hidden('id_servico', '', ['id' => 'id_servico']) !!}
                {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
                <p><strong>Inserir serviços</strong></p>
                <div class="form-row condicao_media_itens_pedido">
                    <div class="col-sm-2">
                        <strong><span id="num_projeto" class='ml-2'>Nr. Projeto: {{ $dados['numero_projeto'] }}</span></strong>
                    </div>
                    <div class="col-sm-2">
                        <strong><span id="estabelecimento_exibicao" class='ml-2'>{{ $dados['estabelecimento'] }}</span></strong>
                    </div>
                    <div class="col-sm-2">
                        <strong>UF destino:   </strong>
                        <span id="estado_destino">{{ $dados['estado_destino'] }}<br></span>
                    </div>
                    <div class="col-sm-2">
                        <strong>Prazo médio:   </strong>
                        <span id="media_condicao_pagamento">{{ $dados['media_condicao_pagamento'] }}</span>
                    </div>
                    <div class="col-sm-1">
                        <strong>Preço:   </strong>
                        <span id="preco_cif_fob">{{ $dados['preco_cif_fob'] }}</span>
                    </div>
                    <div class="col-sm-1">
                        <strong>Frete:   </strong>
                        <span id="cif_fob">{{ $dados['cif_fob'] }}</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-3">
                        {{ Form::label('faccao', 'Facção', []) }}
                        <div class="input-group">
                            {{ Form::text('faccao', '', ['id' => 'faccao', 'class' => 'form-control input-label', 'placeholder' => 'Facção', 'maxlength' => '250', 'onkeyup' => 'optionsFaccao();']) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-faccao-busca" data-route="{{ route("faccao.modal.buscar") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                            {{ Form::hidden('codigo_faccao', '', ['id' => 'codigo_faccao']) }}
                        </div>
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('servico_codigo', 'Código do Serviço', []) }}
                        <div class="input-group" id="cod_servico_group">
                            {{ Form::text('servico_codigo', '', ['id' => 'servico_codigo', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Código', "maxlength" => "250"]) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-servico" data-route="{{ route("produto.insumo.modal.buscar") }}"><i class="bt-view m-2"></i></span>
                        </div>
                    </div>
                    <div class="form-group col-sm-4">
                        {{ Form::label('servico_descricao', 'Serviço', []) }}
                        {{ Form::text('servico_descricao', '', ['id' => 'servico_descricao', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Nome do servico']) }}
                    </div>
                    <div class="form-group col-sm-3">
                        {!! Form::label('servico_produto', 'Produto') !!}
                        {!! Form::select('servico_produto', $produto_select, '', ['id' => 'servico_produto', 'class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-3">
                        {!! Form::label('servico_tecido', 'Tecido') !!}
                        {!! Form::select('servico_tecido', $tecido_select, '', ['id' => 'servico_tecido', 'class' => 'form-control', 'disabled' => 'disabled', 'placeholder' => 'Selecione o Tecido']) !!}
                    </div>
                    <div class="form-group col-sm-3">
                            {!! Form::label('servico_codigo_produto_acabado', 'Código Produto Acabado') !!}
                            <div class="input-group" id="cod_servico_group">
                                    {!! Form::text('servico_codigo_produto_acabado', '', ['id' => 'servico_codigo_produto_acabado', 'class' => 'form-control', 'placeholder' => 'Código Produto Acabado', 'disabled' => 'disabled']) !!}
                                <span class="input-group-addon border rounded-right" id="bt-search-produto" data-route="{{ route("produto.insumo.modal.buscar") }}"><i class="bt-view m-2"></i></span>
                            </div>
                    </div>
                    <div class="form-group col-sm-4">
                        {!! Form::label('servico_produto_acabado', 'Produto Acabado') !!}
                        {!! Form::text('servico_produto_acabado', '', ['id' => 'servico_produto_acabado', 'class' => 'form-control', 'placeholder' => 'Nome Produto Acabado', 'disabled' => 'disabled']) !!}
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-2">
                        {{ Form::label('servico_preco', 'Custo Unitário', []) }}
                        {!! Form::text("servico_preco", '', ["id"=> "servico_preco", "class"=>"form-control text-right", "maxlength"=>"8", "disabled" => "disabled"]) !!}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('servico_quantidade', 'Quantidade', []) }}
                        {!! Form::text("servico_quantidade", '', ["id"=> "servico_quantidade", "class"=>"form-control text-right", "maxlength"=>"8", "disabled" => "disabled"]) !!}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('servico_custo_total', 'Custo Total', []) }}
                        {!! Form::text("servico_custo_total", '', ["id"=> "servico_custo_total", "class"=>"form-control text-right", "maxlength"=>"8", "disabled" => "disabled"]) !!}
                    </div>
                </div>
                <div class="content-buttons">
                    <button name="btn-create" id="btn-create-servico-projeto" class="btn-create">Inserir Facção</button>
                    <button name="btn-cancel" id="btn-cancel-servico-projeto" class="btn-cancel">Cancelar</button>
                </div>
            </form>
        </div>
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped table-filters-projeto-servico" id="table-filters-projeto-servico">
                    <thead>
                        <tr>
                            <th>CNPJ</th>
                            <th>Facção</th>
                            <th>Tipo</th>
                            <th>Ref. Produto</th>
                            <th>Tipo de Serviço</th>
                            {{-- <th>Tecido</th>
                            <th>Produto Acabado</th> --}}
                            <th class="tb_number td_preco">Custo unitário</th>
                            <th class="tb_number td_preco">Quantidade</th>
                            <th class="tb_number td_preco">Custo Total</th>
                            <th class="td_acao"></th>
                            <th class="td_acao"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($faccoes_tabela))
                            @foreach($faccoes_tabela as $faccao)
                                <tr>
                                    <td><div><div>{{ $faccao['cnpj'] }}</div></div></td>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $faccao['faccao'] }}'>{{ $faccao['faccao'] }}</div></div></td>
                                    @if(empty($faccao['tecido']))
                                        <td>PRODUTO</td>
                                        <td>
                                            <div>
                                                <div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $faccao['produto'] }}'>
                                                    {{ $faccao['produto'] }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $faccao['tipo_de_servico'] }}'>
                                                    {{ $faccao['tipo_de_servico'] }}
                                                </div>
                                            </div>
                                        </td>
                                    @else
                                    <td>TECIDO</td>
                                    <td>
                                        <div>
                                            <div data-toggle="popover" data-placement="top" data-title="Detalhes" data-content="<p><b>Tecido:</b> {{ $faccao['tecido'] }}<p><b>Ref. Produto:</b> {{ $faccao['produto'] }} <p><b>Produto Acabado:</b> {{ $faccao['produto_acabado'] }}">
                                                <a href="#" class="bt-detalhe" ></a>
                                                {{ $faccao['tecido'] }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $faccao['tipo_de_servico'] }}'>
                                                {{ $faccao['tipo_de_servico'] }}
                                            </div>
                                        </div>
                                    </td>
                                    @endif
                                    <td class="tb_number td_preco">{{ $faccao['preco_unitario'] }}</td>
                                    <td class="tb_number td_preco">{{ $faccao['quantidade'] }}</td>
                                    <td class="tb_number td_preco">{{ $faccao['custo_total'] }}</td>
                                    <td class="td_acao"><a href="#" class="bt-edit" title='Editar' onclick="editarServicoNoProjeto($(this).parents('tr'), '{{ $faccao['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                    <td class="td_acao"><a href="#" class="bt-delete" title='Excluir' onclick="excluirServicoNoProjeto($(this).parents('tr'), '{{ $faccao['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="tb_number td_preco"></td>
                            <td class="tb_number td_preco">Total: </td>
                            <td class="tb_number" id='servico_total_exibicao'>{{ $faccao_total_ex }}</td>
                            <td class="td_acao"></td>
                            <td class="td_acao"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-sm-12 mt-5" id="button-bottom">
                <button type="button" id="bt_ir_para_insumos" class="btn troca-aba btn-info"><< Voltar para o insumo</button>
                <button type="button" id="bt_ir_para_resultado" class="btn troca-aba btn-info float-right">Avançar para o resultado >></button>
            </div>
            <div class="col-sm-12 mt-1" id="button-bottom">
                <button type="button" id="btn_reprovar_projeto_servico" class="btn btn-danger float-left">Reprovar Projeto</button>
                @if(!empty($mostrar_botao_aprovar))
                    <button type="button" id="btn_aprovar_projeto_servico" class="btn btn-success float-right">Aprovar Projeto</button>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-pane" id="lancamento_projeto_resultado" role="tabpanel" aria-labelledby="dados-tab">
        <form action="post" name="form_projeto_resultado" class="cadPedido" id="form_projeto_resultado" onsubmit="return false;">
            @csrf
            {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
                <div class="form-row condicao_media_itens_pedido">
                    <div class="col-sm-2">
                        <strong><span id="num_projeto" class='ml-2'>Nr. Projeto: {{ $dados['numero_projeto'] }}</span></strong>
                    </div>
                    <div class="col-sm-2">
                        <strong><span id="estabelecimento_exibicao" class='ml-2'>{{ $dados['estabelecimento'] }}</span></strong>
                    </div>
                    <div class="col-sm-2">
                        <strong>UF destino:   </strong>
                        <span id="estado_destino">{{ $dados['estado_destino'] }}<br></span>
                    </div>
                    <div class="col-sm-2">
                        <strong>Prazo médio:   </strong>
                        <span id="media_condicao_pagamento">{{ $dados['media_condicao_pagamento'] }}</span>
                    </div>
                    <div class="col-sm-1">
                        <strong>Preço:   </strong>
                        <span id="preco_cif_fob">{{ $dados['preco_cif_fob'] }}</span>
                    </div>
                    <div class="col-sm-1">
                        <strong>Frete:   </strong>
                        <span id="cif_fob">{{ $dados['cif_fob'] }}</span>
                    </div>
                </div>
            <table class="table">
                <tr>
                    <th colspan="2" id="resultado_descricao_projeto" class="text-center border-right"></th>
                    <th colspan="2" class="text-center">CUSTOS</th>
                </tr>
                <tr>
                    <td width="30%">Valor Total do Pedido</td>
                    <td width="20%" class="text-right border-right" id="resultado_total_pedido">0</td>
                    <td width="30%">Custo do Tecido</td>
                    <td width="20%" class="text-right" id="resultado_custo_tecido">0</td>
                </tr>
                <tr>
                    <td>Quantidade Total</td>
                    <td class="text-right border-right" id="resultado_quantidade_total">0</td>
                    <td>Custo do Insumo</td>
                    <td class="text-right" id="resultado_custo_insumo">0</td>
                </tr>
                <tr>
                    <td>Comissão</td>
                    <td class="text-right border-right" id="resultado_comissao">0</td>
                    <td>Mão de Obra</td>
                    <td class="text-right" id="resultado_mao_de_obra">0</td>
                </tr>
                <tr>
                    <td>Desconto</td>
                    <td class="text-right border-right" id="resultado_desconto">0,00 %</td>
                    <td>Custo Unitário MN</td>
                    <td class="text-right" id="resultado_custo_unitario">0</td>
                </tr>
                <tr>
                    <td class="border-bottom">Preço Médio de Venda</td>
                    <td class="text-right border-right" id="resultado_preco_venda"></td>
                    <td class="border-bottom" id="frete_adicional">Frete Adicional</td>
                    <td class="text-right" id="resultado_frete_adicional"></td>
                </tr>
                <tr>
                    <td class="border-bottom"></td>
                    <td class="text-right border-right border-bottom"></td>
                    <td class="border-bottom">Total de Custo</td>
                    <td class="text-right border-bottom" id="resultado_custo_total"></td>
                </tr>      
            </table>

            <div class="col-sm-12 mt-1" id="button-bottom">
                    <button type="button" id="bt_ir_para_servicos" class="btn troca-aba btn-info"><< Voltar para o serviço</button>
            </div>
            <div class="col-sm-12 mt-1" id="button-bottom">
                <button type="button" id="btn_reprovar_projeto_resultado" class="btn btn-danger float-left">Reprovar Projeto</button>
                @if(!empty($mostrar_botao_aprovar))
                    <button type="button" id="btn_aprovar_projeto_resultado" class="btn btn-success float-right">Aprovar Projeto</button>
                @endif
            </div>
        </form>
    </div>
    
</div>

<script>

    $(document).ready( function () {
        init();
        @if(!empty($produtos_tabela))
            removeDisabledTabs();
        @else
            disabledTabs();
        @endif

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
    });

    function init(){
        initAutoCompletes();
        initFunctionsOn();
        initTable();
        initMaskCampos();
    }
    
    function initAutoCompletes(){
        form_projeto_add = $(document).find("#cadprojeto");
        form_projeto_add.find("#condicao_pagamento_descr").autocomplete(optionsAutoCompleteCondicoes());
        form_projeto_add.find("#nome_cliente").autocomplete(optionsAutoCompleteCliente());

        form_produto_add = $(document).find('#form_filter_projeto_produto');
        form_produto_add.find("#produto_descricao").autocomplete(optionsAutoCompleteProduto(form_produto_add));

        form_tecido_add = $(document).find('#form_filter_projeto_tecido');
        form_tecido_add.find("#tecido_descricao").autocomplete(optionsAutoCompleteTecido(form_tecido_add));

        form_insumo_add = $(document).find('#form_filter_projeto_insumo');
        form_insumo_add.find("#insumo_descricao").autocomplete(optionsAutoCompleteInsumo(form_insumo_add));

        form_servico_add = $(document).find('#form_filter_projeto_servico');
        form_servico_add.find("#servico_descricao").autocomplete(optionsAutoCompleteServico(form_servico_add));
form_servico_add.find("#servico_produto_acabado").autocomplete(optionsAutoCompleteServicoProdutoAcabado(form_servico_add));
    }

    function optionsFaccao(){
        form_servico_add = $(document).find('#form_filter_projeto_servico');
        form_servico_add.find("#faccao").autocomplete(optionsAutoCompleteFaccaoFixo(form_servico_add));        
    }

    function initFunctionsOn(){
        form_projeto_add = $(document).find('#cadprojeto');

        form_projeto_add.find("#bt-view-condicao").off("click");
        form_projeto_add.find("#bt-view-condicao").on("click", function(event){
            event.stopPropagation();
            modalCondicao($(this).parent());
            return false;
        });
        form_projeto_add.find("#bt-search-cliente").off("click");
        form_projeto_add.find("#bt-search-cliente").on("click", function(event){
            event.stopPropagation();
            showModalCliente($(this).data("route"));
            return false;
        });
        form_projeto_add.find("#nome_projeto").off("change");
        form_projeto_add.find("#nome_projeto").on("change", function(event){
            saveOnChange();
        });
        form_projeto_add.find("#nome_cliente").off("change");
        form_projeto_add.find("#nome_cliente").on("change", function(event){
            saveOnChange();
        });
        form_projeto_add.find("#num_pedido").off("change");
        form_projeto_add.find("#num_pedido").on("change", function(event){
            saveOnChange();
        });
        form_projeto_add.find("#condicao_pagamento_descr").off("change");
        form_projeto_add.find("#condicao_pagamento_descr").on("change", function(event){
            saveOnChange();
        });
        form_projeto_add.find("#btn_reprovar_projeto").off("click");
        form_projeto_add.find("#btn_reprovar_projeto").on("click", function(){
            recusarProjetoAdd();
        });
        form_projeto_add.find("#btn_aprovar_projeto").off("click");
        form_projeto_add.find("#btn_aprovar_projeto").on("click", function(){
            revisaoProjeto();
        });


        form_produto_add = $(document).find('#form_filter_projeto_produto');

        form_produto_add.find("#bt-buscar-produto").off("click");
        form_produto_add.find("#bt-buscar-produto").on("click", function(){
            showModalProdutoAdd();
        });
        form_produto_add.find("#produto_codigo").off('blur');
        form_produto_add.find("#produto_codigo").on('blur', function(){
            if(form_produto_add.find("#produto_codigo").val() != ''){
                retornarDescricaoProduto(form_produto_add);
            }
        });
        form_produto_add.find("#produto_descricao").off('change');
        form_produto_add.find("#produto_descricao").on('change', function(){
            if(form_produto_add.find("#produto_descricao").val() != ''){
                pesquisaProdutoDescricao(form_produto_add);
            }
        });
        form_produto_add.find("#btn-create-produto-projeto").off("click");
        form_produto_add.find("#btn-create-produto-projeto").on("click", function () {
            salvarProdutoNoProjeto();
        });
        form_produto_add.find("#btn-cancel-produto-projeto").hide();
        form_produto_add.find("#btn-cancel-produto-projeto").off("click");
        form_produto_add.find("#btn-cancel-produto-projeto").on("click", function () {
            if(form_produto_add.find("#id_produto").val() == ''){
                limparMesagemErroAdd(form_produto_add);
                limparCamposProduto();
            }else{
                cancelarEdicaoProduto();
            }
            
        });
        $(document).find("#btn_reprovar_projeto_produto").off("click");
        $(document).find("#btn_reprovar_projeto_produto").on("click", function(){
            recusarProjetoAdd();
        });
        $(document).find("#btn_aprovar_projeto_produto").off("click");
        $(document).find("#btn_aprovar_projeto_produto").on("click", function(){
            revisaoProjeto();
        });

        form_tecido_add = $(document).find('#form_filter_projeto_tecido');

        $(document).find("#btn-create-tecido-projeto").off("click");
        $(document).find("#btn-create-tecido-projeto").on("click", function () {
            salvarTecidoNoProjeto();
        });
        form_tecido_add.find("#bt-search-tecido").off("click");
		form_tecido_add.find("#bt-search-tecido").on("click", function(event){
            event.stopPropagation();
            form_tecido_add.find("#tecido_consumo").val('');
			showModalTecido($(this).data("route"), 'Lista de Tecidos');
            return false;
        });
        form_tecido_add.find("#tecido_codigo").off('blur');
        form_tecido_add.find("#tecido_codigo").on('blur', function(){
            form_tecido_add.find("#tecido_consumo").val('');
            retornarDescricaoTecido(form_tecido_add);
        });
        form_tecido_add.find("#tecido_descricao").off('blur');
        form_tecido_add.find("#tecido_descricao").on('blur', function(){
            retornarDescricaoTecido(form_tecido_add);
        });
        form_tecido_add.find("#tecido_produto").off('change');
        form_tecido_add.find("#tecido_produto").on('change', function(){
            if(form_tecido_add.find("#tecido_produto").val() != 0){
                form_tecido_add.find("#tecido_quantidade").val(retornarQuantidadeProduto(form_tecido_add.find("#tecido_produto").val()));
            }else{
                form_tecido_add.find("#tecido_quantidade").val('');
            }
        });
        form_tecido_add.find("#tecido_consumo").off('keyup');
        form_tecido_add.find("#tecido_consumo").on('keyup', function(){
            calculosTecido(form_tecido_add);
        });
        form_tecido_add.find("#btn-cancel-tecido-projeto").hide();
        form_tecido_add.find("#btn-cancel-tecido-projeto").off('click');
        form_tecido_add.find("#btn-cancel-tecido-projeto").on('click', function(){
            if(form_tecido_add.find("#id_tecido").val() == ''){
                limparMesagemErroAdd(form_tecido_add);
                limparCamposTecido();
            }else{
                cancelarEdicaoTecido();
            }
        });
        $(document).find("#btn_reprovar_projeto_tecido").off("click");
        $(document).find("#btn_reprovar_projeto_tecido").on("click", function(){
            recusarProjetoAdd();
        });
        $(document).find("#btn_aprovar_projeto_tecido").off("click");
        $(document).find("#btn_aprovar_projeto_tecido").on("click", function(){
            revisaoProjeto();
        });
        
        form_insumo_add = $(document).find('#form_filter_projeto_insumo');

        form_insumo_add.find("#bt-search-insumo").off("click");
		form_insumo_add.find("#bt-search-insumo").on("click", function(event){
            event.stopPropagation();
            form_insumo_add.find("#insumo_consumo").val('');
			showModalInsumo($(this).data("route"), 'Lista de Insumos');
            return false;
        });
        form_insumo_add.find("#insumo_codigo").off('blur');
        form_insumo_add.find("#insumo_codigo").on('blur', function(){
            if(form_insumo_add.find("#insumo_codigo").val() != ''){
                retornarDescricaoInsumo(form_insumo_add);
            }
        });
        form_insumo_add.find("#insumo_descricao").off('blur');
        form_insumo_add.find("#insumo_descricao").on('blur', function(){
            if(form_insumo_add.find("#insumo_codigo").val() != ''){
                retornarDescricaoInsumo(form_insumo_add);
            }
        });
        form_insumo_add.find("#insumo_produto").off('change');
        form_insumo_add.find("#insumo_produto").on('change', function(){
            if(form_insumo_add.find("#insumo_produto").val() != 0){
                form_insumo_add.find("#insumo_quantidade").val(retornarQuantidadeProduto(form_insumo_add.find("#insumo_produto").val()));
            }else{
                form_insumo_add.find("#insumo_quantidade").val('');
            }
        });
        form_insumo_add.find("#insumo_consumo").off('keyup');
        form_insumo_add.find("#insumo_consumo").on('keyup', function(){
            calculosInsumo(form_insumo_add);
        });
        form_insumo_add.find("#btn-cancel-insumo-projeto").hide();
        form_insumo_add.find("#btn-cancel-insumo-projeto").off('click');
        form_insumo_add.find("#btn-cancel-insumo-projeto").on('click', function(){
            if(form_insumo_add.find("#id_insumo").val() == ''){
                limparMesagemErroAdd(form_insumo_add);
                limparCamposInsumo();
            }else{
                cancelarEdicaoInsumo();
            }
        });
        form_insumo_add.find("#btn-create-insumo-projeto").off("click");
        form_insumo_add.find("#btn-create-insumo-projeto").on("click", function () {
            salvarInsumoNoProjeto();
        });
        $(document).find("#btn_reprovar_projeto_insumo").off("click");
        $(document).find("#btn_reprovar_projeto_insumo").on("click", function(){
            recusarProjetoAdd();
        });
        $(document).find("#btn_aprovar_projeto_insumo").off("click");
        $(document).find("#btn_aprovar_projeto_insumo").on("click", function(){
            revisaoProjeto();
        });

        form_servico_add = $(document).find('#form_filter_projeto_servico');

        form_servico_add.find("#bt-search-servico").off("click");
		form_servico_add.find("#bt-search-servico").on("click", function(event){
            event.stopPropagation();
			showModalServico($(this).data("route"), 'Lista de Serviços');
            return false;
        });
        form_servico_add.find("#servico_codigo").off('blur');
        form_servico_add.find("#servico_codigo").on('blur', function(){
            if(form_servico_add.find("#servico_codigo").val() != ''){
                retornarDescricaoServico(form_servico_add);
            }
        });
        form_servico_add.find("#servico_codigo_produto_acabado").off('blur');
        form_servico_add.find("#servico_codigo_produto_acabado").on('blur', function(){
            if(form_servico_add.find("#servico_codigo_produto_acabado").val() != ''){
                retornarDescricaoProdutoAcabado(form_servico_add, form_servico_add.find("#servico_codigo_produto_acabado").val());
            }
        });
        form_servico_add.find("#servico_descricao").off('blur');
        form_servico_add.find("#servico_descricao").on('blur', function(){
            if(form_servico_add.find("#servico_descricao").val() == ''){
                form_servico_add.find("#servico_preco").val('');
                form_servico_add.find("#servico_quantidade").val('');
                form_servico_add.find("#servico_custo_total").val('');
            }else{
                retornarDescricaoServico(form_servico_add);
            }
        });
        form_servico_add.find("#servico_produto").off('change');
        form_servico_add.find("#servico_produto").on('change', function(){
            if(form_servico_add.find("#servico_produto").val() != 0){
                form_servico_add.find("#servico_tecido").removeAttr('disabled');
                form_servico_add.find("#servico_codigo_produto_acabado").removeAttr('disabled');
                form_servico_add.find("#servico_produto_acabado").removeAttr('disabled');
                form_servico_add.find("#servico_quantidade").val(retornarQuantidadeProduto(form_servico_add.find("#servico_produto").val()));
                calculosServico(form_servico_add);
                getTecido(form_servico_add.find("#servico_produto").val(), 0);
            }else{
                form_servico_add.find("#servico_quantidade").val('');
                form_servico_add.find("#servico_custo_total").val('');
                form_servico_add.find("#servico_tecido").val(0);
                form_servico_add.find("#servico_codigo_produto_acabado").val('');
                form_servico_add.find("#servico_produto_acabado").val('');
                form_servico_add.find("#servico_tecido").attr("disabled", "disabled");
                form_servico_add.find("#servico_codigo_produto_acabado").attr("disabled", "disabled");
                form_servico_add.find("#servico_produto_acabado").attr("disabled", "disabled");
            }
        });
        form_servico_add.find("#servico_tecido").off('change');
        form_servico_add.find("#servico_tecido").on('change', function(){
            if(form_servico_add.find("#servico_tecido").val() != 0){
                form_servico_add.find("#servico_quantidade").val(retornarQuantidadeTecido(form_servico_add.find("#servico_tecido").val()));
                calculosServico(form_servico_add);
            }else{
                form_servico_add.find("#servico_tecido").removeAttr('disabled');
                form_servico_add.find("#servico_codigo_produto_acabado").removeAttr('disabled');
                form_servico_add.find("#servico_produto_acabado").removeAttr('disabled');
                form_servico_add.find("#servico_quantidade").val(retornarQuantidadeProduto(form_servico_add.find("#servico_produto").val()));
                calculosServico(form_servico_add);
                getTecido(form_servico_add.find("#servico_produto").val(), 0);
            }
        });
        form_servico_add.find("#tipo_de_servico").off("change");
        form_servico_add.find("#tipo_de_servico").on("change", function () {
            if(form_servico_add.find("#tipo_de_servico") == ''){
                form_servico_add.find("#servico_preco").val('');
                form_servico_add.find("#servico_custo_total").val('');
                form_servico_add.find("#servico_preco").attr("disabled", "disabled");
            }else{
                getPrecoServico();
            }
        });
        form_servico_add.find("#servico_preco").off("keyup");
        form_servico_add.find("#servico_preco").on("keyup", function () {
            if(form_servico_add.find("#servico_preco") == ''){
                form_servico_add.find("#servico_custo_total").val('');
            }else{
                calculosServico(form_servico_add);
            }
        });
        form_servico_add.find("#btn-create-servico-projeto").off("click");
        form_servico_add.find("#btn-create-servico-projeto").on("click", function () {
            salvarServicoNoProjeto();
        });
        $(document).find("#btn_reprovar_projeto_servico").off("click");
        $(document).find("#btn_reprovar_projeto_servico").on("click", function(){
            recusarProjetoAdd();
        });
        $(document).find("#btn_aprovar_projeto_servico").off("click");
        $(document).find("#btn_aprovar_projeto_servico").on("click", function(){
            revisaoProjeto();
        });
        form_servico_add.find("#btn-cancel-servico-projeto").hide();
        form_servico_add.find("#btn-cancel-servico-projeto").off('click');
        form_servico_add.find("#btn-cancel-servico-projeto").on('click', function(){
            if(form_servico_add.find("#id_servico").val() == ''){
                limparMesagemErroAdd(form_servico_add);
                limparCamposServico();
            }else{
                cancelarEdicaoServico();
            }
        });
        form_servico_add.find("#bt-search-faccao-busca").on("click", function(){
            showModalFaccao($(this).data("route"), "Lista de Facções");
        });
        form_servico_add.find("#bt-search-produto").off("click");
        form_servico_add.find("#bt-search-produto").on("click", function(){
            showModalProdutoAcabado();
        });


        form_resultado_add = $(document).find("#form_projeto_resultado");

        form_resultado_add.find('#modificar_desconto').off('click');
        form_resultado_add.find('#modificar_desconto').on('click', function(){
            modalMudarDesconto(form_resultado_add.find('#id_projeto').val());
        });

        form_resultado_add.find("#btn_calcular_margem").off("click");
        form_resultado_add.find("#btn_calcular_margem").on("click", function(){
            if(form_resultado_add.find("#margem").val() != ''){
                resultadoDaMargem();
            }else{
                form_resultado_add.find('#resultado_custo_total_margem').html('0');
                form_resultado_add.find('#resultado_mark_up_real').html('0');
                form_resultado_add.find('#resultado_acima_tabela').html('0');
            }
        });
        form_resultado_add.find("#btn_reprovar_projeto_resultado").off("click");
        form_resultado_add.find("#btn_reprovar_projeto_resultado").on("click", function(){
            recusarProjetoAdd();
        });
        form_resultado_add.find("#btn_aprovar_projeto_resultado").off("click");
        form_resultado_add.find("#btn_aprovar_projeto_resultado").on("click", function(){
            revisaoProjeto();
        });

        //Troca de Aba
        $(document).find(".troca-aba").off("click");
        $(document).find(".troca-aba").on("click", function(e){
            e.preventDefault();
            if($(this).attr("id") === "bt_ir_para_produtos"){
                $(document).find("#lancamento-projeto-produto-tab").tab("show");
                table_produto.draw(false);
            }else if($(this).attr("id") === "bt_ir_para_tecidos"){
                $(document).find("#lancamento-projeto-tecido-tab").tab("show");
                table_tecido.draw(false);
            }else if($(this).attr("id") === "bt_ir_para_insumos"){
                $(document).find("#lancamento-projeto-insumo-tab").tab("show");
                table_insumo.draw(false);
            }else if($(this).attr("id") === "bt_ir_para_servicos"){
                $(document).find("#lancamento-projeto-servico-tab").tab("show");
                table_servico.draw(false);
            }else if($(this).attr("id") === "bt_ir_para_resultado"){
                $(document).find("#lancamento-projeto-resultado-tab").tab("show"); 
                resultado();
            }else{
                $(document).find("#lancamento-projeto-header-tab").tab("show");
            }

            $(document).find(".tooltip").each(function(index, el) {
                $(document).find("[aria-describedby="+$(this).attr('id')+"]").tooltip('hide');
            });
        });

        $(document).find("#lancamento-projeto-produto-tab").off("click");
        $(document).find("#lancamento-projeto-produto-tab").on("click", function(){
            setTimeout(function(){
                table_produto.draw(false);
            }, 100);
        });
        $(document).find("#lancamento-projeto-tecido-tab").off("click");
        $(document).find("#lancamento-projeto-tecido-tab").on("click", function(){
            setTimeout(function(){
                table_tecido.draw(false);
            }, 100);
        });
        $(document).find("#lancamento-projeto-insumo-tab").off("click");
        $(document).find("#lancamento-projeto-insumo-tab").on("click", function(){
            setTimeout(function(){
                table_insumo.draw(false);
            }, 100);
        });
        $(document).find("#lancamento-projeto-servico-tab").off("click");
        $(document).find("#lancamento-projeto-servico-tab").on("click", function(){
            setTimeout(function(){
                table_servico.draw(false);
            }, 100);
        });
        $(document).find("#lancamento-projeto-resultado-tab").off("click");
        $(document).find("#lancamento-projeto-resultado-tab").on("click", function(){
            resultado();
        });
    }

    function initMaskCampos(){
        form_produto_add = $(document).find("#form_filter_projeto_produto");
        form_produto_add.find("#produto_preco_venda").maskMoney({thousands:'', decimal:','}); 
        form_produto_add.find("#produto_quantidade").maskMoney({thousands:'', decimal:','});
        form_produto_add.find("#produto_peso").maskMoney({thousands:'', decimal:','});

        form_tecido_add = $(document).find('#form_filter_projeto_tecido');
        form_tecido_add.find("#tecido_consumo").maskMoney({thousands:'', decimal:',', precision: 3});

        form_insumo_add = $(document).find('#form_filter_projeto_insumo');
        form_insumo_add.find("#insumo_consumo").maskMoney({thousands:'', decimal:','});

        form_servico_add = $(document).find('#form_filter_projeto_servico');
        form_servico_add.find("#servico_preco").maskMoney({thousands:'', decimal:','});

        form_resultado_add = $(document).find("#form_projeto_resultado");
        form_resultado_add.find("#resultado_desconto").maskMoney({thousands:'', decimal:','});
    }

    //AutoComplete

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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo_edit_delete').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#condicao_pagamento").val(ui.item.value);
                $(document).find("#condicao_pagamento_descr").val(ui.item.label);
                return false;
            }
        };
    }

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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo_edit_delete').css('z-index')) + 1));
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
                form_projeto_add = $(document).find('#cadprojeto');
                form_projeto_add.find("#codigo_cliente").val(ui.item.cpf_cnpj);
                form_projeto_add.find("#nome_cliente").val(ui.item.label);
                return false;
            }
        };
    }

    function optionsAutoCompleteProduto(form_produto_add){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.estabelecimento = form_tecido_add.find("#tecido_estabelecimento").val();
                $.post("{{ route('produto.tecido.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo_edit_delete').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    form_produto_add.find("#produto_codigo").val('');
                    form_produto_add.find("#produto_ficha").val("");
                    form_produto_add.find("#id_codigo").val("");
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_produto_add.find("#produto_descricao").val(ui.item.label);
                form_produto_add.find("#produto_codigo").val(ui.item.value);
                form_produto_add.find("#produto_ficha").val(ui.item.value);
                form_produto_add.find("#id_codigo").val("CODIGO");
                return false;
            }
        };
    }

    function optionsAutoCompleteServicoProdutoAcabado(form_produto_add){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.estabelecimento = form_tecido_add.find("#tecido_estabelecimento").val();
                $.post("{{ route('produto.tecido.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo_edit_delete').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    form_produto_add.find("#produto_codigo").val('');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_produto_add.find("#servico_produto_acabado").val(ui.item.label);
                form_produto_add.find("#servico_codigo_produto_acabado").val(ui.item.value);
                return false;
            }
        };
    }

    function pesquisaProdutoDescricao(){
        form_produto_add = $(document).find('#form_filter_projeto_produto');
        descricao = form_produto_add.find('#produto_descricao').val();
        $.ajax({
            url: '{{ route('produto.pesquisaprodutodescricao')}}',
            data: {
                _token : "{{ csrf_token() }}",
                descricao: descricao
            },
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
            },
            error: function(callback){
                if(form_produto_add.find('#produto_descricao').val() != ''){
                    form_produto_add.find('#codigo_descricao').val('');
                }
            }
        });
    }

    function optionsAutoCompleteTecido(form_tecido_add){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.estabelecimento = form_tecido_add.find("#tecido_estabelecimento").val();
                $.post("{{ route('produto.tecido.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo_edit_delete').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum tecido encontrado');
                    form_tecido_add.find("#tecido_codigo").val('');
                    form_tecido_add.find("#tecido_descricao").val('');
                    form_tecido_add.find("#tecido_consumo").val('');
                    form_tecido_add.find("#tecido_preco_unitario").val('');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_tecido_add.find("#tecido_descricao").val(ui.item.label);
                form_tecido_add.find("#tecido_codigo").val(ui.item.value);
                form_tecido_add.find("#tecido_preco_unitario").val(ui.item.preco);
                return false;
            }
        };
    }

    function optionsAutoCompleteInsumo(form_insumo_add){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.estabelecimento = form_insumo_add.find("#insumo_estabelecimento").val();
                request.linha = "INSUMO";
                $.post("{{ route('produto.insumo.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo_edit_delete').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum insumo encontrado');
                    form_insumo_add.find("#insumo_codigo").val('');
                    form_insumo_add.find("#insumo_descricao").val('');
                    form_insumo_add.find("#insumo_consumo").val('');
                    form_insumo_add.find("#insumo_preco_unitario").val('');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_insumo_add.find("#insumo_descricao").val(ui.item.label);
                form_insumo_add.find("#insumo_codigo").val(ui.item.value);
                form_insumo_add.find("#insumo_preco_unitario").val(ui.item.preco);
                return false;
            }
        };
    }

    function optionsAutoCompleteFaccao($this){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.codigo_servico = $this.find("#tipo_de_servico").val();
                $.post("{{ route('faccao.autocomplete_servico') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo_edit_delete').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma Facção encontrado para esse Serviço');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#faccao").val(ui.item.label);
                $(document).find("#codigo_faccao").val(ui.item.value);
                return false;
            }
        };
    }

    function optionsAutoCompleteFaccaoFixo(form_faccao_add){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('faccao.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo_edit_delete').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma fação encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_faccao_add.find("#faccao").val(ui.item.label);
                form_faccao_add.find("#codigo_faccao").val(ui.item.value);
                form_faccao_add.find("#tipo_de_servico").focus();
                if(form_faccao_add.find("#id_servico").val() == ''){
                    selectTipoServico();
                }
                return false;
            }
        };
    }

    function optionsAutoCompleteServico(form_servico_add){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.linha = "MAO DE OBRA";
                $.post("{{ route('produto.insumo.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            async: false,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo_edit_delete').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum serviço encontrado');
                    limparCamposServico();
                    form_servico_add.find("#servico_descricao").focus();
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_servico_add.find("#servico_descricao").val(ui.item.label);
                form_servico_add.find("#servico_codigo").val(ui.item.value);
                form_servico_add.find("#servico_preco").val(ui.item.preco);
                retornarDescricaoServico(form_servico_add);
                if(form_servico_add.find("#custo_unitario").val() != ''){
                    calculosServico(form_servico_add);
                }
                return false;
            }
        };
    }

    //Modal
    function showModalCliente(url){
		var title = "Busca de Clientes";
        $.ajax({
            url: url,
            type: 'POST',
            data: {_token: '{{ csrf_token() }}'},
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
        form_projeto_add = $(document).find('#cadprojeto');
        form_projeto_add.find("#codigo_cliente").val($dados.find("td").eq(3).text());
        form_projeto_add.find("#nome_cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
	}
    function modalCondicao($this){
        $.ajax({
            url: $this.data('route'),
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', estabelecimento: $(document).find('#estabelecimento').val()},
            success: function(data){
                $(document).find("#modal_busca_condicao").remove();
                createModal('modal_busca_condicao', "Busca de condição de Pagamento", data, 'modal-lg');
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
        form_projeto_add = $(document).find('#cadprojeto');
        form_projeto_add.find("#condicao_pagamento_descr").data('oldvalue', $(document).find("#condicao_pagamento_descr").val());
        form_projeto_add.find("#condicao_pagamento").val($dados.find("td:eq(0)").text() );
        form_projeto_add.find("#condicao_pagamento_descr").val($dados.find("td:eq(1)").text() );
        $(document).find("#modal_busca_condicao").modal("hide");
    }

    function showModalProdutoAdd(){
        $.ajax({
            url: '{{ route('lancamento_projeto.modal.buscar_projeto_produto') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto ficha tecnica", data, 'modal-lg');
                var modal = $(document).find("#modal_search_produto");
                $(document).ready( function () {
                    produtos_table.on('draw', function () {
                        modal.find('tbody').find("td").not('.th_view').off("click");
                        modal.find('tbody').find("td").not('.th_view').on("click", function(event){
                            returnDadosProdutoAdd($(this).parent('tr'));
                        });
                    });
                });
            }
        });
    }
    function returnDadosProdutoAdd($dados){
		form_modal_add = $(document).find("#form_filter_projeto_produto");
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){ 
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");

        form_modal_add.find('#produto_codigo').val($dados.find("td").eq(1).text());
        form_modal_add.find('#produto_descricao').val($dados.find("td").eq(2).text());
        form_modal_add.find('#produto_descricao_hidden').val($dados.find("td").eq(2).text());
        form_modal_add.find("#produto_ficha").val($dados.find("td").eq(1).text());
        form_modal_add.find("#id_codigo").val("CODIGO");
        form_modal_add.find('#produto_preco_venda').focus();
    };

    function showModalProdutoAcabado(){
        $.ajax({
            url: '{{ route('analise.produto.infoadicional.produtosSemDetalhes') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto ficha tecnica", data, 'modal-lg');
                var modal = $(document).find("#modal_search_produto");
                $(document).ready( function () {
                    produtos_table.on('draw', function () {
                        modal.find('tbody').find("td").not('.th_view').off("click");
                        modal.find('tbody').find("td").not('.th_view').on("click", function(event){
                            returnDadosProdutoAcabado($(this).parent('tr'));
                        });
                    });
                });
            }
        });
    }
    function returnDadosProdutoAcabado($dados){
		form_modal_add = $(document).find("#form_filter_projeto_servico");
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){ 
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        form_modal_add.find('#servico_codigo_produto_acabado').val($dados.find("td").eq(1).text());
        form_modal_add.find('#servico_produto_acabado').val($dados.find("td").eq(2).text());
    };


    function showModalTecido(url, title){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("tecido_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog_tecido_busca.on('draw', function () {
                        $(document).find("#tecido_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#tecido_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosTecido($(this));
                        });
                    });
                });
            }
        });
    }

    function returnDadosTecido($this){
        if($this.find("td").eq(1).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#tecido_search_show").modal("hide");
        form_modal_add = $(document).find('#form_filter_projeto_tecido');
        form_modal_add.find("#tecido_descricao").val($this.find("td").eq(2).text());
        form_modal_add.find("#tecido_codigo").val($this.find("td").eq(1).text());
        retornarDescricaoTecido(form_modal_add);
        calculosTecido(form_modal_add);
    }

    function showModalInsumo(url, title){
        $.ajax({
            url: url,
            method: 'GET',
            data: {
                _token: '{{csrf_token()}}',
                linha: 'INSUMO'
            },
            success: function(body){
                createModal("insumo_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog_insumo_busca.on('draw', function () {
                        $(document).find("#insumo_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#insumo_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosInsumo($(this));
                        });
                    });
                });
            }
        });
    }

    function returnDadosInsumo($this){
        if($this.find("td").eq(1).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#insumo_search_show").modal("hide");
        $(document).find('#form_filter_projeto_insumo').find("#insumo_descricao").val($this.find("td").eq(2).text());
        $(document).find('#form_filter_projeto_insumo').find("#insumo_codigo").val($this.find("td").eq(1).text());
        calculosInsumo($(document).find('#form_filter_projeto_insumo'));
        retornarDescricaoInsumo($(document).find('#form_filter_projeto_insumo'));
    }

    function showModalServico(url, title){
        $.ajax({
            url: url,
            method: 'GET',
            data: {
                _token: '{{csrf_token()}}',
                linha: 'MAO DE OBRA'
            },
            success: function(body){
                createModal("servico_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog_insumo_busca.on('draw', function () {
                        $(document).find("#servico_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#servico_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosServico($(this));
                        });
                    });
                });
            }
        });
    }
	
	function returnDadosServico($this){
        if($this.find("td").eq(1).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#servico_search_show").modal("hide");
        $(document).find('#form_filter_projeto_servico').find("#servico_descricao").val($this.find("td").eq(2).text());
        $(document).find('#form_filter_projeto_servico').find("#servico_codigo").val($this.find("td").eq(1).text());
        retornarDescricaoServico($(document).find('#form_filter_projeto_servico'));
    }

    function showModalFaccao(url, title){
        if(form_servico_add.find("#id_servico").val() == ''){
            $.ajax({
                url: url,
                method: 'GET',
                success: function(body){
                    createModal("faccao_search_show", title, body, 'modal-lg');
                    $(document).ready( function () {
                        table_dialog.on('draw', function () {
                            $(document).find("#faccao_search_show").find('tbody').find("tr").off("click");
                            $(document).find("#faccao_search_show").find('tbody').find("tr").on("click", function(){
                                returnDadosFaccao($(this));
                            });
                        });
                    });
                }
            });
        }else{
            form_modal_add = $(document).find("#form_filter_projeto_servico");
            tipo_de_servico = form_modal_add.find("#tipo_de_servico").val();
            $.ajax({
                url: url,
                method: 'GET',
                data:{
                    _token: '{{csrf_token()}}',
                    tipo_de_servico: tipo_de_servico
                },
                success: function(body){
                    createModal("faccao_search_show", title, body, 'modal-lg');
                    $(document).ready( function () {
                        table_dialog.on('draw', function () {
                            $(document).find("#faccao_search_show").find('tbody').find("tr").off("click");
                            $(document).find("#faccao_search_show").find('tbody').find("tr").on("click", function(){
                                returnDadosFaccao($(this));
                            });
                        });
                    });
                }
            });
        }
    }
    function returnDadosFaccao($this){
        if($this.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#faccao_search_show").modal("hide");
        $(document).find('#form_filter_projeto_servico').find("#faccao").val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
        $(document).find('#form_filter_projeto_servico').find("#codigo_faccao").val($this.find("td").eq(0).text());
    }

    //Tabela
    function initTable(){
        table_filters_produto_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "30vh",
            "autoWidth": false,
            "drawCallback": function(settings) {
                $(document).find('.detalhe_produto').tooltip({
                    container: 'body',
                    html: true,
                    show: true,
                    trigger: 'manual'
                });
            },
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Produto inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Produto inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" }
            ]
        };
        table_produto = '';
        table_produto = $(document).find('#table-filters-produtos').DataTable(table_filters_produto_options);
        table_produto.draw();

        table_filters_tecido_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "25vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum tecido inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum tecido inserido",
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
                }
                
            ],
            "order": [[ 1, 'asc' ]]
        };
        table_tecido = '';
        table_tecido = $(document).find('#table-filters-tecidos').DataTable(table_filters_tecido_options);
        table_tecido.draw();

        table_filters_tecido_estabelecimento_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "25vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "",
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
                }
                
            ],
            "order": [[ 1, 'asc' ]]
        };
        table_tecido_estabelecimento = '';
        table_tecido_estabelecimento = $(document).find('#table-estabelecimentos-tecido').DataTable(table_filters_tecido_estabelecimento_options);
        table_tecido_estabelecimento.draw();

        table_filters_insumo_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "25vh",
            "autoWidth": false,
            "drawCallback": function(settings) {
                $(document).find('.estoque, .preco, #total_pedido').tooltip({
                    container: 'body',
                    html: true,
                    show: true,
                    trigger: 'manual'
                });
            },
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum insumo inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum insumo inserido",
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
        table_insumo = '';
        table_insumo = $(document).find('#table-filters-projeto-insumos').DataTable(table_filters_insumo_options);
        table_insumo.draw();

        table_filters_insumo_estabelecimento_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "25vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "",
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
                }
        
            ],
            "order": [[ 1, 'asc' ]]
        };
        table_insumo_estabelecimento = '';
        table_insumo_estabelecimento = $(document).find('#table-estabelecimentos-insumo').DataTable(table_filters_insumo_estabelecimento_options);
        table_insumo_estabelecimento.draw();

        table_filters_servico_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "30vh",
            "autoWidth": false,
            "drawCallback": function(settings) {
                $(document).find('.estoque, .preco, #total_pedido').tooltip({
                    container: 'body',
                    html: true,
                    show: true,
                    trigger: 'manual'
                });
            },
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum serviço inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum  serviço inserido",
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
        table_servico = '';
        table_servico = $(document).find('#table-filters-projeto-servico').DataTable(table_filters_servico_options);
        table_servico.draw();
    }

    // Outros
    function retornarDescricaoProduto(form_modal_add){
        data_form_modal_add = form_modal_add.serialize();
        $.ajax({
            url: '{{ route('lancamento_projeto.produto.retorna_descricao')}}',
            data: data_form_modal_add,
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoAdd(produto, form_modal_add);
            },
            error: function(callback){
                if(form_modal_add.find('#produto_codigo').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_add.find('#produto_codigo').val("")
                    form_modal_add.find('#produto_codigo').focus();
                    form_modal_add.find("#produto_ficha").val("");
                    form_modal_add.find("#id_codigo").val("");
                    limparMesagemErroAdd(form_modal_add);
                    mensagemErroAdd(dados, form_modal_add);
                }
                form_modal_add.find("#produto_descricao").val('');
                form_modal_add.find("#produto_quantidade").val('');
                form_modal_add.find("#produto_ficha").val("");
                form_modal_add.find("#id_codigo").val("");
            }
        });
    }
    function dadosRetornoAdd(produto, form_modal_add){
        if(form_modal_add.find('#produto_codigo').val() === ''){
            form_modal_add.find('#produto_descricao').focus();
            form_modal_add.find("#produto_ficha").val("");
            form_modal_add.find("#id_codigo").val("");
        }else{
            form_modal_add.find('#produto_descricao').val(produto.nome);
            form_modal_add.find('#produto_descricao_hidden').val(produto.nome);
            form_modal_add.find("#produto_ficha").val(produto.codigo);
            form_modal_add.find("#id_codigo").val("ID");
        }
    }

    function retornarDescricaoTecido(form_modal_add){
        data_form_modal_add = form_modal_add.serialize();
        
        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ route('produto.tecido.tecido_descricao')}}',
            data: data_form_modal_add,
            method: 'POST',
            async: false,
            success: function(callback){
                var produto = callback;
                table_tecido_estabelecimento.clear().draw();
                if(produto.estoque != ''){
                    dadosRetornoTecidoDescricao(produto, form_modal_add);  
                    estoques = [];
                    
                    for (var estoque in produto.estoque){
                        var field = [
                            produto.estoque[estoque].estabelecimento,
                            produto.estoque[estoque].estoque,
                            produto.estoque[estoque].compras
                        ]; 
    
                        estoques.push(field);
                    }
                    table_tecido_estabelecimento.rows.add(estoques).draw();
                }else{
                    message("Atenção", produto.codigo + ' - Tecido ' + produto.nome + ' está sem estoque');
                    form_modal_add.find('#tecido_codigo').val('');
                    form_modal_add.find('#tecido_descricao').val('');
                }
                
            },
            error: function(callback){
                if(form_modal_add.find('#tecido_codigo').val() != ''){
                    var dados = callback.responseJSON;
                    limparMesagemErroAdd(form_modal_add);
                    mensagemErroAdd(dados, form_modal_add);
                }
            }
        });
    }
    function dadosRetornoTecidoDescricao(produto, form_modal_add){
        form_modal_add.find('#tecido_descricao').val(produto.nome);
        form_modal_add.find('#tecido_preco_unitario').val(produto.preco);
        form_modal_add.find('#tecido_produto').focus();
        calculosTecido(form_modal_add);
    }

    function validarDescricaoTecido(form_modal_add){
        data_form_modal_add = form_modal_add.serialize();

        $.ajax({
            url: '{{ route('produto.tecido.validar_descricao')}}',
            data: data_form_modal_add,
            method: 'POST',
            success: function(callback){
            },
            error: function(callback){
                message("Atenção", 'Nenhum tecido encontrado');
                if(form_modal_add.find('#tecido_descricao').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_add.find('#tecido_descricao').focus();
                }
            }
        });
    }

    function retornarDescricaoInsumo(form_modal_add){
        data_form_modal_add = form_modal_add.serialize();
        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ route('produto.insumo.insumo_descricao')}}',
            data: data_form_modal_add,
            method: 'POST',
            async: false,
            success: function(callback){
                var produto = callback;
                table_insumo_estabelecimento.clear().draw();
                if(produto.estoque != ''){
                    dadosRetornoInsumoDescricao(produto, form_modal_add);

                    estoques = [];
                    
                    for (var estoque in produto.estoque){
                        var field = [
                            produto.estoque[estoque].estabelecimento,
                            produto.estoque[estoque].estoque,
                            produto.estoque[estoque].compras
                        ]; 
    
                        estoques.push(field);
                    }
                    table_insumo_estabelecimento.rows.add(estoques).draw();
                }else{
                    message("Atenção", produto.codigo + ' - Insumo ' + produto.nome + ' está sem estoque');
                    form_modal_add.find('#insumo_codigo').val('');
                    form_modal_add.find('#insumo_descricao').val('');
                }
                
            },
            error: function(callback){
                if(form_modal_add.find('#insumo_codigo').val() != ''){
                    var dados = callback.responseJSON;
                    limparMesagemErroAdd(form_modal_add);
                    mensagemErroAdd(dados, form_modal_add);
                }
            }
        });
    }
    function dadosRetornoInsumoDescricao(produto, form_modal_add){
        form_modal_add.find('#insumo_descricao').val(produto.nome);
        form_modal_add.find('#insumo_preco_unitario').val(produto.preco);
        form_modal_add.find('#insumo_produto').focus();
        calculosInsumo(form_modal_add);
    }
    function retornarDescricaoServico(form_modal_add){
        form_modal_add = $(document).find("#form_filter_projeto_servico");
        data_form_modal_add = form_modal_add.serialize();

        $.ajax({
            url: '{{ route('produto.insumo.servico_descricao')}}',
            data: data_form_modal_add,
            method: 'POST',
            async: false,
            success: function(callback){
                var produto = callback;
                dadosRetornoServicoDescricao(produto, form_modal_add);
            },
            error: function(callback){
                if(form_modal_add.find('#servico_codigo').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_add.find('#servico_codigo').val('');
                    form_modal_add.find('#servico_codigo').focus();
                }
            }
        });
    }
    function dadosRetornoServicoDescricao(produto, form_modal_add){
        form_modal_add.find('#servico_descricao').val(produto.nome);
        form_modal_add.find('#servico_preco').val(produto.preco);
        form_modal_add.find('#servico_produto').focus();
        calculosServico(form_modal_add);
    }

    function retornarDescricaoProdutoAcabado(form_modal_add, $value){
        data_form_modal_add = form_modal_add.serialize();
        $.ajax({
            url: '{{ route('lancamento_projeto.produto.retorna_descricao')}}',
            data: {
                _token: '{{csrf_token()}}',
                produto_codigo: $value
            },
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoProdutoAcabado(produto, form_modal_add);
            },
            error: function(callback){

            }
        });
    }

    function dadosRetornoProdutoAcabado(produto, form_modal_add){
        if(form_modal_add.find('#servico_codigo_produto_acabado').val() === ''){
            form_modal_add.find('#servico_produto_acabado').focus();
        }else{
            form_modal_add.find('#servico_produto_acabado').val(produto.nome);
        }
    }

    function quantidadeOutrasTela($value){
        form_tecido_add = $(document).find("#form_filter_projeto_tecido");
        form_tecido_add.find("#tecido_quantidade").val($value);
        form_insumo_add = $(document).find("#form_filter_projeto_insumo");
        form_insumo_add.find("#insumo_quantidade").val($value);
        form_servico_add = $(document).find("#form_filter_projeto_servico");
        form_servico_add.find("#servico_quantidade").val($value);
        form_servico_add.find("#servico_quantidade_exibicao").val($value);
        alterarQuantidadeOutrasTelas($value);
    }

    
    function retornarQuantidadeProduto($value){
        $retorno = '';
        $.ajax({
            url: '{{ route('lancamento_projeto.produto.get_quantidade')}}',
            data: {
                _token: '{{csrf_token()}}',
                id: $value
            },
            method: 'POST',
            async: false,
            success: function(callback){
                $retorno = callback.response.quantidade;
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });

        return $retorno;
    }

    function retornarQuantidadeTecido($value){
        $retorno = '';
        $.ajax({
            url: '{{ route('lancamento_projeto.tecido.get_quantidade')}}',
            data: {
                _token: '{{csrf_token()}}',
                id: $value
            },
            method: 'POST',
            async: false,
            success: function(callback){
                $retorno = callback.response.quantidade;
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });

        return $retorno;
    }

    //Adicionar, Editar e Excluir

    function saveOnChange(){
        form_projeto_add = $(document).find('#cadprojeto');
        data_form_modal_add = form_projeto_add.serialize();
        limparMesagemErroAdd(form_projeto_add);
        $.ajax({
            url: '{{ route('lancamento_projeto.adicionar_on_change')}}',
            data: data_form_modal_add,
            method: 'POST',
            async: true,
            success: function(data){
                liberarTabs();
                var produto = data;
                if(data.response.tecidos != ''){
                    tecidos = [];
                    table_tecido.clear().draw();
                    for (var tecido in data.response.tecidos.tabela){
                        var field = [
                            data.response.tecidos.tabela[tecido].codigo,
                            ajusteTamanhoTable(data.response.tecidos.tabela[tecido].descricao),
                            ajusteTamanhoTable(data.response.tecidos.tabela[tecido].produto),
                            data.response.tecidos.tabela[tecido].preco_unitario,
                            data.response.tecidos.tabela[tecido].consumo_unitario,
                            data.response.tecidos.tabela[tecido].consumo_total,
                            data.response.tecidos.tabela[tecido].total_custo,
                            createBtEditarTecido(data.response.tecidos.tabela[tecido].id, data.response.tecidos.tabela[tecido].projeto_id),
                            createBtExcluirTecido(data.response.tecidos.tabela[tecido].id, data.response.tecidos.tabela[tecido].projeto_id)
                        ]; 
                        tecidos.push(field);
                    }
                    table_tecido.rows.add(tecidos).draw();
                    $('.dataTables_scrollFootInner').find('#tecido_total_exibicao').html(data.response.tecidos.total);
                }
                

                if(data.response.insumos != ''){
                    insumos = [];
                    table_insumo.clear().draw();
                    for (var insumo in data.response.insumos.tabela){
                        var field = [
                            data.response.insumos.tabela[insumo].codigo,
                            ajusteTamanhoTable(data.response.insumos.tabela[insumo].descricao),
                            ajusteTamanhoTable(data.response.insumos.tabela[insumo].produto),
                            data.response.insumos.tabela[insumo].preco_unitario,
                            data.response.insumos.tabela[insumo].consumo_total,
                            data.response.insumos.tabela[insumo].total_custo,
                            createBtEditarInsumo(data.response.insumos.tabela[insumo].id, data.response.insumos.tabela[insumo].projeto_id),
                            createBtExcluirInsumo(data.response.insumos.tabela[insumo].id, data.response.insumos.tabela[insumo].projeto_id)
                        ];
                        insumos.push(field);
                    }
                    table_insumo.rows.add(insumos).draw();
                    $('.dataTables_scrollFootInner').find('#insumo_total_exibicao').html(data.response.insumos.total);
                }

                if(data.response.servicos != ''){
                    servicos = [];
                    table_servico.clear().draw();
                    for (var servico in data.response.servicos.tabela){
                        var field = [
                            data.response.servicos.tabela[servico].cnpj,
                            ajusteTamanhoTable(data.response.servicos.tabela[servico].faccao),
                            data.response.servicos.tabela[servico].tipo,
                            produtoOuTecido(data.response.servicos.tabela[servico]),
                            ajusteTamanhoTable(data.response.servicos.tabela[servico].tipo_de_servico),
                            data.response.servicos.tabela[servico].preco_unitario,
                            data.response.servicos.tabela[servico].quantidade,
                            data.response.servicos.tabela[servico].total_custo,
                            createBtEditarServico(data.response.servicos.tabela[servico].id, data.response.servicos.tabela[servico].id_projeto),
                            createBtExcluirServico(data.response.servicos.tabela[servico].id, data.response.servicos.tabela[servico].id_projeto)
                        ];
                        servicos.push(field);
                    }
                    table_servico.rows.add(servicos).draw();
                    chamadaPopover();

                    $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.servicos.total);
                }

                if(data.response.info != ''){
                    form_tecido_add = $(document).find('#form_filter_projeto_tecido');
                    form_insumo_add = $(document).find('#form_filter_projeto_insumo');
                    form_servico_add = $(document).find('#form_filter_projeto_servico');

                    form_tecido_add.find("#estabelecimento_exibicao").html(data.response.info.estabelecimento);
                    form_tecido_add.find("#estado_destino").html(data.response.info.estado_destino);
                    form_tecido_add.find("#media_condicao_pagamento").html(data.response.info.media_condicao_pagamento);
                    form_tecido_add.find("#preco_cif_fob").html(data.response.info.preco_cif_fob);
                    form_tecido_add.find("#cif_fob").html(data.response.info.cif_fob);

                    form_insumo_add.find("#estabelecimento_exibicao").html(data.response.info.estabelecimento);
                    form_insumo_add.find("#estado_destino").html(data.response.info.estado_destino);
                    form_insumo_add.find("#media_condicao_pagamento").html(data.response.info.media_condicao_pagamento);
                    form_insumo_add.find("#preco_cif_fob").html(data.response.info.preco_cif_fob);
                    form_insumo_add.find("#cif_fob").html(data.response.info.cif_fob);

                    form_servico_add.find("#estabelecimento_exibicao").html(data.response.info.estabelecimento);
                    form_servico_add.find("#estado_destino").html(data.response.info.estado_destino);
                    form_servico_add.find("#media_condicao_pagamento").html(data.response.info.media_condicao_pagamento);
                    form_servico_add.find("#preco_cif_fob").html(data.response.info.preco_cif_fob);
                    form_servico_add.find("#cif_fob").html(data.response.info.cif_fob);
                }
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd(form_projeto_add);
                mensagemErroAdd(dados, form_projeto_add);
            }
        });
    }

    function salvarProdutoNoProjeto(){
        if ($(document).find("#id_produto").val() != ''){
            salvarEdicaoProdutoNoProjeto();
        }else{
            form_modal_add = $(document).find("#form_filter_projeto_produto");
    
            data_form = form_modal_add.serialize();
            limparMesagemErroAdd(form_modal_add);
            $.ajax({
                url: '{{ Route("lancamento_projeto.produto.adicionar") }}',
                type: 'POST',
                data: data_form,
                success: function(data) {
                    var field = [
                        data.response.codigo,
                        getDetalheProduto(data.response.descricao, data.response.detalhes),
                        data.response.ncm,
                        data.response.peso,
                        data.response.preco_venda,
                        data.response.quantidade,
                        createBtEditarProduto(data),
                        createBtExcluirProduto(data)
                    ];
                    table_produto.row.add(field).draw();
                    chamadaPopover();
                    $('.dataTables_scrollFootInner').find('#produto_total_exibicao').html(data.response.total_exibicao);
                    $('[data-toggle="tooltip"]').off('show.bs.tooltip');
                    $('[data-toggle="tooltip"]').tooltip('hide');

                    $('[data-toggle="tooltip"]').tooltip({
                        container: 'body',
                        html: true,
                        show: true,
                        template: '<div class="tooltip tooltip-estoque" role="tooltip"><div class="arrow"></div><h3 class="tooltip-header"></h3><div class="tooltip-body"></div></div>'
                    });
                    limparCamposProduto();
                    getProduto();
                    liberarTabs();
                    if(data.response.ficha_tecnica.tecidos != ''){
                        tecidos = [];
                        for (var tecido in data.response.ficha_tecnica.tecidos.tabela){
                            var field = [
                                data.response.ficha_tecnica.tecidos.tabela[tecido].codigo,
                                ajusteTamanhoTable(data.response.ficha_tecnica.tecidos.tabela[tecido].descricao),
                                ajusteTamanhoTable(data.response.ficha_tecnica.tecidos.tabela[tecido].produto),
                                data.response.ficha_tecnica.tecidos.tabela[tecido].preco_unitario,
                                data.response.ficha_tecnica.tecidos.tabela[tecido].consumo_unitario,
                                data.response.ficha_tecnica.tecidos.tabela[tecido].consumo_total,
                                data.response.ficha_tecnica.tecidos.tabela[tecido].total_custo,
                                createBtEditarTecido(data.response.ficha_tecnica.tecidos.tabela[tecido].id, data.response.ficha_tecnica.tecidos.tabela[tecido].projeto_id),
                                createBtExcluirTecido(data.response.ficha_tecnica.tecidos.tabela[tecido].id, data.response.ficha_tecnica.tecidos.tabela[tecido].projeto_id)
                            ]; 
        
                            tecidos.push(field);
                        }
                        table_tecido.rows.add(tecidos).draw();
                        $('.dataTables_scrollFootInner').find('#tecido_total_exibicao').html(data.response.ficha_tecnica.tecidos.total);
                    }
                    if(data.response.ficha_tecnica.insumos != ''){
                        insumos = [];
                        for (var insumo in data.response.ficha_tecnica.insumos.tabela){
                            var field = [
                                data.response.ficha_tecnica.insumos.tabela[insumo].codigo,
                                ajusteTamanhoTable(data.response.ficha_tecnica.insumos.tabela[insumo].descricao),
                                ajusteTamanhoTable(data.response.ficha_tecnica.insumos.tabela[insumo].produto),
                                data.response.ficha_tecnica.insumos.tabela[insumo].preco_unitario,
                                data.response.ficha_tecnica.insumos.tabela[insumo].consumo_total,
                                data.response.ficha_tecnica.insumos.tabela[insumo].total_custo,
                                createBtEditarInsumo(data.response.ficha_tecnica.insumos.tabela[insumo].id, data.response.ficha_tecnica.insumos.tabela[insumo].projeto_id),
                                createBtExcluirInsumo(data.response.ficha_tecnica.insumos.tabela[insumo].id, data.response.ficha_tecnica.insumos.tabela[insumo].projeto_id)
                            ];
                            insumos.push(field);
                        }
                        table_insumo.rows.add(insumos).draw();
                        $('.dataTables_scrollFootInner').find('#insumo_total_exibicao').html(data.response.ficha_tecnica.insumos.total);
                    }
                    if(data.response.ficha_tecnica.servicos != ''){
                        servicos = [];
                        for (var servico in data.response.ficha_tecnica.servicos.tabela){
                            var field = [
                                data.response.ficha_tecnica.servicos.tabela[servico].cnpj,
                                ajusteTamanhoTable(data.response.ficha_tecnica.servicos.tabela[servico].faccao),
                                data.response.ficha_tecnica.servicos.tabela[servico].tipo,
                                produtoOuTecido(data.response.ficha_tecnica.servicos.tabela[servico]),
                                data.response.ficha_tecnica.servicos.tabela[servico].tipo_de_servico,
                                data.response.ficha_tecnica.servicos.tabela[servico].preco_unitario,
                                data.response.ficha_tecnica.servicos.tabela[servico].quantidade,
                                data.response.ficha_tecnica.servicos.tabela[servico].total_custo,
                                createBtEditarServico(data.response.ficha_tecnica.servicos.tabela[servico].id, data.response.ficha_tecnica.servicos.tabela[servico].id_projeto),
                                createBtExcluirServico(data.response.ficha_tecnica.servicos.tabela[servico].id, data.response.ficha_tecnica.servicos.tabela[servico].id_projeto)
                            ];
                            servicos.push(field);
                        }
                        table_servico.rows.add(servicos).draw();
                        chamadaPopover();
    
                        $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.ficha_tecnica.servicos.total);
                    }
                },
                error: function(data){
                    var dados = data.responseJSON;
                    limparMesagemErroAdd(form_modal_add);
                    mensagemErroAdd(dados, form_modal_add);
                }
            });
        }
    }

    function salvarEdicaoProdutoNoProjeto(){
        form_modal_add = $(document).find("#form_filter_projeto_produto");

        data_form = form_modal_add.serialize();
        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.produto.editar") }}',
            type: 'POST',
            data: data_form,
            success: function(data) {
                var field = [
                    data.response.codigo,
                    getDetalheProduto(data.response.descricao, data.response.detalhes),
                    data.response.ncm,
                    data.response.peso,
                    data.response.preco_venda,
                    data.response.quantidade,
                    createBtEditarProduto(data),
                    createBtExcluirProduto(data)
                ];
                table_produto.row.add(field).draw();
                chamadaPopover();
                alterarQuantidadeOutrasTelas(form_modal_add.find("#id_produto").val());
                form_modal_add.find("#id_produto").val('');
                $('.dataTables_scrollFootInner').find('#produto_total_exibicao').html(data.response.total_exibicao);
                form_modal_add.find("#btn-create-produto-projeto").html('Inserir produto');
                form_modal_add.find("#btn-cancel-produto-projeto").hide();
                limparCamposProduto();
                
                $('[data-toggle="tooltip"]').off('show.bs.tooltip');
                $('[data-toggle="tooltip"]').tooltip('hide');

                $('[data-toggle="tooltip"]').tooltip({
                    container: 'body',
                    html: true,
                    show: true,
                    template: '<div class="tooltip tooltip-estoque" role="tooltip"><div class="arrow"></div><h3 class="tooltip-header"></h3><div class="tooltip-body"></div></div>'
                });
                getProduto();
                liberarTabs();
            },
            error: function(data){
                var dados = data.responseJSON;
                limparMesagemErroAdd(form_modal_add);
                mensagemErroAdd(dados, form_modal_add);
            }
        });
    }

    function cancelarEdicaoProduto(){
        form_modal_add = $(document).find("#form_filter_projeto_produto");

        data_form = form_modal_add.serialize();
        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.produto.cancelar_edicao") }}',
            type: 'POST',
            data: data_form,
            success: function(data) {
                var field = [
                    data.response.codigo,
                    getDetalheProduto(data.response.descricao, data.response.detalhes),
                    data.response.ncm,
                    data.response.peso,
                    data.response.preco_venda,
                    data.response.quantidade,
                    createBtEditarProduto(data),
                    createBtExcluirProduto(data)
                ];
                table_produto.row.add(field).draw();
                chamadaPopover();
                form_modal_add.find("#id_produto").val('');
                $('.dataTables_scrollFootInner').find('#produto_total_exibicao').html(data.response.total_exibicao);
                form_modal_add.find("#btn-create-produto-projeto").html('Inserir produto');
                form_modal_add.find("#btn-cancel-produto-projeto").show();
                limparCamposProduto();
                $('[data-toggle="tooltip"]').off('show.bs.tooltip');
                $('[data-toggle="tooltip"]').tooltip('hide');

                $('[data-toggle="tooltip"]').tooltip({
                    container: 'body',
                    html: true,
                    show: true,
                    template: '<div class="tooltip tooltip-estoque" role="tooltip"><div class="arrow"></div><h3 class="tooltip-header"></h3><div class="tooltip-body"></div></div>'
                });
                liberarTabs();
            },
            error: function(data){
                var dados = data.responseJSON;
                limparMesagemErroAdd(form_modal_add);
                mensagemErroAdd(dados, form_modal_add);
            }
        });
    }

    function createBtEditarProduto($this){
        var html = "<a href=\"#\" class=\"bt-edit\" title='Editar' onclick=\"editarProdutoNoProjeto($(this).parents('tr'), '"+$this.response.id+"','"+$this.response.projeto_id+"')\"></a>";
        return html;
    }

    function createBtExcluirProduto($this){
        var html = "<a href=\"#\" class=\"bt-delete\" title='Excluir' onclick=\"excluirProdutoNoProjeto($(this).parents('tr'), '"+$this.response.id+"','"+$this.response.projeto_id+"')\"></a>";
        return html;
    }

    function editarProdutoNoProjeto(obj, $id_produto, $id_projeto){
        form_modal_add = $(document).find("#form_filter_projeto_produto");

        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.produto.get_editar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_produto: $id_produto,
                id_projeto: $id_projeto
            },
            success: function(data){
                table_produto.row(obj).remove().draw();

                form_modal_add.find("#produto_codigo").val(data.response.codigo);
                form_modal_add.find("#produto_descricao").val(data.response.descricao);
                form_modal_add.find("#produto_descricao_hidden").val(data.response.descricao);
                form_modal_add.find("#produto_preco_venda").val(data.response.preco_venda);
                form_modal_add.find("#produto_quantidade").val(data.response.quantidade);
                form_modal_add.find("#id_produto").val(data.response.id);
                form_modal_add.find("#produto_detalhes").val(data.response.detalhe_producao);
                form_modal_add.find("#produto_ncm").val(data.response.ncm);
                form_modal_add.find("#produto_peso").val(data.response.peso);

                form_modal_add.find("#btn-create-produto-projeto").html('Editar produto');
                form_modal_add.find("#btn-cancel-produto-projeto").show();

                $('.dataTables_scrollFootInner').find('#produto_total_exibicao').html(data.response.total_exibicao);
                {{-- quantidadeOutrasTela(data.response.total_exibicao); --}}
                if(data.response.total_exibicao == ''){
                    liberarTabs();
                }else{
                    liberarTabs();
                }
            }
        });
    }

    function excluirProdutoNoProjeto(obj, $id_produto, $id_projeto){
        form_modal_add = $(document).find("#form_filter_projeto_produto");
    
        id_revisor = form_modal_add.find("#id_revisor").val();
        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.produto.deletar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_produto: $id_produto,
                id_projeto: $id_projeto,
                id_revisor: id_revisor
            },
            success: function(data){
                getProduto();
                table_produto.row(obj).remove().draw();
                $('.dataTables_scrollFootInner').find('#produto_total_exibicao').html(data.response.total_exibicao);
                if(data.response.total_exibicao == ''){
                    liberarTabs();
                }else{
                    liberarTabs();
                }
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }

    function salvarTecidoNoProjeto(){
        form_modal_add = $(document).find("#form_filter_projeto_tecido");
        if (form_modal_add.find("#id_tecido").val() == ''){
            codigo = form_modal_add.find("#tecido_codigo").val();
            descricao = form_modal_add.find("#tecido_descricao").val();
            consumo = form_modal_add.find("#tecido_consumo").val();
            preco_unitario = form_modal_add.find("#tecido_preco_unitario").val();
            consumo_total = form_modal_add.find("#tecido_consumo_total").val();
            quantidade = form_modal_add.find("#tecido_quantidade").val();
            custo_total = form_modal_add.find("#tecido_valor_total").val();
            id_projeto = form_modal_add.find("#id_projeto").val();
            id_produto = form_modal_add.find("#tecido_produto").val();
            id_revisor = form_modal_add.find("#id_revisor").val();

            limparMesagemErroAdd(form_modal_add);
            $.ajax({
                url: '{{ Route("lancamento_projeto.tecido.adicionar") }}',
                type: 'POST',
                data: {
                    _token: '{{csrf_token()}}',
                    codigo: codigo,
                    descricao: descricao,
                    consumo: consumo,
                    preco_unitario: preco_unitario,
                    custo_total: custo_total,
                    quantidade: quantidade,
                    consumo_total: consumo_total,
                    id_projeto: id_projeto,
                    produto: id_produto,
                    id_revisor: id_revisor
                },
                success: function(data) {
					var field = [
                        data.response.codigo,
                        ajusteTamanhoTable(data.response.descricao),
                        ajusteTamanhoTable(data.response.produto),
                        data.response.preco_unitario,
                        data.response.consumo_unitario,
                        data.response.consumo_total,
                        data.response.total_custo,
                        createBtEditarTecido(data.response.id, data.response.projeto_id),
                        createBtExcluirTecido(data.response.id, data.response.projeto_id)
                    ];
                    table_tecido.row.add(field).draw();

                    $('.dataTables_scrollFootInner').find('#tecido_total_exibicao').html(data.response.tecido_total);

                    form_modal_add.find("#tecido_codigo").focus();

                    limparCamposTecido();
                },
                error: function(data){
                    var dados = data.responseJSON;
                    limparMesagemErroAdd(form_modal_add);
                    mensagemErroAdd(dados, form_modal_add);
                }
            });
        }else{
            salvarEdicaoTecidoNoProjeto();
        }
    }

    function salvarEdicaoTecidoNoProjeto(){
        form_modal_add = $(document).find("#form_filter_projeto_tecido");

        codigo = form_modal_add.find("#tecido_codigo").val();
        descricao = form_modal_add.find("#tecido_descricao").val();
        consumo = form_modal_add.find("#tecido_consumo").val();
        preco_unitario = form_modal_add.find("#tecido_preco_unitario").val();
        consumo_total = form_modal_add.find("#tecido_consumo_total").val();
        quantidade = form_modal_add.find("#tecido_quantidade").val();
        custo_total = form_modal_add.find("#tecido_valor_total").val();
        id_projeto = form_modal_add.find("#id_projeto").val();
        id_tecido = form_modal_add.find("#id_tecido").val();
        id_produto = form_modal_add.find("#tecido_produto").val();
        id_revisor = form_modal_add.find("#id_revisor").val();

        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.tecido.editar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                codigo: codigo,
                descricao: descricao,
                consumo: consumo,
                preco_unitario: preco_unitario,
                custo_total: custo_total,
                quantidade: quantidade,
                consumo_total: consumo_total,
                id_projeto: id_projeto,
                id_tecido: id_tecido,
                produto: id_produto,
                id_revisor: id_revisor
            },
            success: function(data) {
                var field = [
                    data.response.codigo,
                    ajusteTamanhoTable(data.response.descricao),
                    ajusteTamanhoTable(data.response.produto),
                    data.response.preco_unitario,
                    data.response.consumo_unitario,
                    data.response.consumo_total,
                    data.response.total_custo,
                    createBtEditarTecido(data.response.id, data.response.projeto_id),
                    createBtExcluirTecido(data.response.id, data.response.projeto_id)
                ];
                table_tecido.row.add(field).draw();
                alterarQuantidadeOutrasTelas(data.response.produto_id);
                $(document).find("#form_filter_projeto_tecido").find("#id_tecido").val('');
                $(document).find("#form_filter_projeto_tecido").find("#btn-create-tecido-projeto").html('Inserir Tecido');
                $(document).find("#form_filter_projeto_tecido").find("#btn-cancel-tecido-projeto").hide();
                $('.dataTables_scrollFootInner').find('#tecido_total_exibicao').html(data.response.tecido_total);

                form_modal_add.find("#tecido_codigo").focus();

                limparCamposTecido();
            },
            error: function(data){
                var dados = data.responseJSON;
                limparMesagemErroAdd(form_modal_add);
                mensagemErroAdd(dados, form_modal_add);
            }
        });
    }

    function cancelarEdicaoTecido(){
        form_modal_add = $(document).find("#form_filter_projeto_tecido");
        data_form = form_modal_add.serialize();
        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.tecido.cancelar_edicao") }}',
            type: 'POST',
            data: data_form,
            success: function(data){
                var field = [
                    data.response.codigo,
                    ajusteTamanhoTable(data.response.descricao),
                    ajusteTamanhoTable(data.response.produto),
                    data.response.preco_unitario,
                    data.response.consumo_unitario,
                    data.response.consumo_total,
                    data.response.total_custo,
                    createBtEditarTecido(data.response.id, data.response.projeto_id),
                    createBtExcluirTecido(data.response.id, data.response.projeto_id)
                ];
                table_tecido.row.add(field).draw();
                $(document).find("#form_filter_projeto_tecido").find("#id_tecido").val('');
                $(document).find("#form_filter_projeto_tecido").find("#btn-create-tecido-projeto").html('Inserir Tecido');
                $(document).find("#form_filter_projeto_tecido").find("#btn-cancel-tecido-projeto").hide();
                $('.dataTables_scrollFootInner').find('#tecido_total_exibicao').html(data.response.total_custo);

                limparCamposTecido();
            }
        });
    }

    function createBtEditarTecido($id, $projeto_id){
        var html = "<a href=\"#\" class=\"bt-edit\" title='Editar' onclick=\"editarTecidoNoProjeto($(this).parents('tr'), '"+$id+"','"+$projeto_id+"')\"></a>";
        return html;
    }

    function createBtExcluirTecido ($id, $projeto_id){
        var html = "<a href=\"#\" class=\"bt-delete\" title='Excluir' onclick=\"excluirTecidoNoProjeto($(this).parents('tr'), '"+$id+"','"+$projeto_id+"')\"></a>";
        return html;
    }

    function editarTecidoNoProjeto(obj, $id_tecido, $id_projeto){
        form_modal_add = $(document).find("#form_filter_projeto_tecido");
    
        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.tecido.get_editar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_tecido: $id_tecido, 
                id_projeto: $id_projeto
            },
            async: false,
            success: function(data){
                table_tecido.row(obj).remove().draw();

                form_modal_add.find("#id_tecido").val(data.response.id);
                form_modal_add.find("#tecido_codigo").val(data.response.codigo);
                form_modal_add.find("#tecido_consumo").val(data.response.consumo_unitario);
                form_modal_add.find("#tecido_valor_total").val(data.response.custo_total);
                form_modal_add.find("#tecido_produto").val(data.response.produto_id);
                form_modal_add.find("#tecido_quantidade").val(retornarQuantidadeProduto(form_modal_add.find("#tecido_produto").val()));

                form_modal_add.find("#btn-create-tecido-projeto").html('Editar Tecido');
                form_modal_add.find("#btn-cancel-tecido-projeto").show();

                $('.dataTables_scrollFootInner').find('#tecido_total_exibicao').html(data.response.total_custo_total);
                
                retornarDescricaoTecido(form_modal_add);
                calculosTecido(form_modal_add);
            }
        });
    }

    function excluirTecidoNoProjeto(obj, $id_tecido, $id_projeto){
        form_modal_add = $(document).find("#form_filter_projeto_tecido");
        id_revisor = form_modal_add.find("#id_revisor").val();

        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.tecido.deletar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_tecido: $id_tecido, 
                id_projeto: $id_projeto,
                id_revisor: id_revisor
            },
            success: function(data){
                table_tecido.row(obj).remove().draw();

                $('.dataTables_scrollFootInner').find('#tecido_total_exibicao').html(data.response.total_exibicao);
            }
        });
    }

    
    function salvarInsumoNoProjeto(){
        form_modal_add = $(document).find("#form_filter_projeto_insumo");
        if (form_modal_add.find("#id_insumo").val() == ''){
            codigo = form_modal_add.find("#insumo_codigo").val();
            descricao = form_modal_add.find("#insumo_descricao").val();
            consumo = form_modal_add.find("#insumo_consumo").val();
            preco_unitario = form_modal_add.find("#insumo_preco_unitario").val();
            consumo_total = form_modal_add.find("#insumo_consumo_total").val();
            quantidade = form_modal_add.find("#insumo_quantidade").val();
            custo_total = form_modal_add.find("#insumo_valor_total").val();
            id_projeto = form_modal_add.find("#id_projeto").val();
            id_produto = form_modal_add.find("#insumo_produto").val();
            id_revisor = form_modal_add.find("#id_revisor").val();

            limparMesagemErroAdd(form_modal_add);
            $.ajax({
                url: '{{ Route("lancamento_projeto.insumo.adicionar") }}',
                type: 'POST',
                data: {
                    _token: '{{csrf_token()}}',
                    codigo: codigo,
                    descricao: descricao,
                    consumo: consumo,
                    preco_unitario: preco_unitario,
                    custo_total: custo_total,
                    quantidade: quantidade,
                    consumo_total: consumo_total,
                    id_projeto: id_projeto,
                    produto: id_produto,
                    id_revisor: id_revisor
                },
                success: function(data) {
                    var field = [
                        data.response.codigo,
                        ajusteTamanhoTable(data.response.descricao),
                        ajusteTamanhoTable(data.response.produto),
                        data.response.preco_unitario,
                        data.response.consumo_total,
                        data.response.total_custo,
                        createBtEditarInsumo(data.response.id, data.response.projeto_id),
                        createBtExcluirInsumo(data.response.id, data.response.projeto_id)
                    ];
                    table_insumo.row.add(field).draw();

                    form_modal_add.find("#insumo_codigo").focus();

                    $('.dataTables_scrollFootInner').find('#insumo_total_exibicao').html(data.response.insumo_total);

                    limparCamposInsumo();
                },
                error: function(data){
                    var dados = data.responseJSON;
                    limparMesagemErroAdd(form_modal_add);
                    mensagemErroAdd(dados, form_modal_add);
                }
            });
        }else{
            salvarEdicaoInsumoNoProjeto();
        }
    }

    function salvarEdicaoInsumoNoProjeto(){
        form_modal_add = $(document).find("#form_filter_projeto_insumo");

        codigo = form_modal_add.find("#insumo_codigo").val();
        descricao = form_modal_add.find("#insumo_descricao").val();
        consumo = form_modal_add.find("#insumo_consumo").val();
        preco_unitario = form_modal_add.find("#insumo_preco_unitario").val();
        consumo_total = form_modal_add.find("#insumo_consumo_total").val();
        quantidade = form_modal_add.find("#insumo_quantidade").val();
        custo_total = form_modal_add.find("#insumo_valor_total").val();
        id_projeto = form_modal_add.find("#id_projeto").val();
        id_insumo = form_modal_add.find("#id_insumo").val();
        id_produto = form_modal_add.find("#insumo_produto").val();
        id_revisor = form_modal_add.find("#id_revisor").val();

        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.insumo.editar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                codigo: codigo,
                descricao: descricao,
                consumo: consumo,
                preco_unitario: preco_unitario,
                custo_total: custo_total,
                quantidade: quantidade,
                consumo_total: consumo_total,
                id_projeto: id_projeto,
                id_insumo: id_insumo,
                produto: id_produto,
                id_revisor: id_revisor
            },
            success: function(data) {
                var field = [
                    data.response.codigo,
                    ajusteTamanhoTable(data.response.descricao),
                    ajusteTamanhoTable(data.response.produto),
                    data.response.preco_unitario,
                    data.response.consumo_total,
                    data.response.total_custo,
                    createBtEditarInsumo(data.response.id, data.response.projeto_id),
                    createBtExcluirInsumo(data.response.id, data.response.projeto_id)
                ];
                table_insumo.row.add(field).draw();
                $(document).find("#form_filter_projeto_insumo").find("#id_insumo").val('');
                $(document).find("#form_filter_projeto_insumo").find("#btn-create-insumo-projeto").html('Inserir insumo/acessório');
                $(document).find("#form_filter_projeto_insumo").find("#btn-cancel-insumo-projeto").hide();
                $('.dataTables_scrollFootInner').find('#insumo_total_exibicao').html(data.response.insumo_total);

                form_modal_add.find("#insumo_codigo").focus();

                limparCamposInsumo();
            },
            error: function(data){
                var dados = data.responseJSON;
                limparMesagemErroAdd(form_modal_add);
                mensagemErroAdd(dados, form_modal_add);
            }
        });
    }

    function cancelarEdicaoInsumo(){
        form_modal_add = $(document).find("#form_filter_projeto_insumo");
        data_form = form_modal_add.serialize();
        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.insumo.cancelar_edicao") }}',
            type: 'POST',
            data: data_form,
            success: function(data){
                var field = [
                    data.response.codigo,
                    ajusteTamanhoTable(data.response.descricao),
                    ajusteTamanhoTable(data.response.produto),
                    data.response.preco_unitario,
                    data.response.consumo_total,
                    data.response.total_custo,
                    createBtEditarInsumo(data.response.id, data.response.projeto_id),
                    createBtExcluirInsumo(data.response.id, data.response.projeto_id)
                ];
                table_insumo.row.add(field).draw();
                $(document).find("#form_filter_projeto_insumo").find("#id_insumo").val('');
                $(document).find("#form_filter_projeto_insumo").find("#btn-create-insumo-projeto").html('Inserir insumo/acessório');
                $(document).find("#form_filter_projeto_insumo").find("#btn-cancel-insumo-projeto").hide();
                $('.dataTables_scrollFootInner').find('#tecido_total_exibicao').html(data.response.total_custo);

                limparCamposInsumo();
            }
        });
    }

    function createBtEditarInsumo($id, $projeto_id){
        var html = "<a href=\"#\" class=\"bt-edit\" title='Editar' onclick=\"editarInsumoNoProjeto($(this).parents('tr'), '"+$id+"','"+$projeto_id+"')\"></a>";
        return html;
    }

    function createBtExcluirInsumo ($id, $projeto_id){
        var html = "<a href=\"#\" class=\"bt-delete\" title='Excluir' onclick=\"excluirInsumoNoProjeto($(this).parents('tr'), '"+$id+"','"+$projeto_id+"')\"></a>";
        return html;
    }

    function editarInsumoNoProjeto(obj, $id_insumo, $id_projeto){
        form_modal_add = $(document).find("#form_filter_projeto_insumo");
    
        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.insumo.get_editar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_insumo: $id_insumo, 
                id_projeto: $id_projeto
            },
            async: false,
            success: function(data){
                table_insumo.row(obj).remove().draw();

                form_modal_add.find("#id_insumo").val(data.response.id);
                form_modal_add.find("#insumo_codigo").val(data.response.codigo);
                form_modal_add.find("#insumo_consumo").val(data.response.consumo_total);
                form_modal_add.find("#insumo_valor_total").val(data.response.custo_total);
                form_modal_add.find("#insumo_produto").val(data.response.produto_id);
                form_modal_add.find("#insumo_quantidade").val(retornarQuantidadeProduto(form_modal_add.find("#insumo_produto").val()));

                form_modal_add.find("#btn-create-insumo-projeto").html('Editar insumo/acessório');
                form_modal_add.find("#btn-cancel-insumo-projeto").show();

                $('.dataTables_scrollFootInner').find('#insumo_total_exibicao').html(data.response.total_custo_total);

                retornarDescricaoInsumo(form_modal_add);
                calculosInsumo(form_modal_add);  
            }
        });
    }

    function excluirInsumoNoProjeto(obj, $id_insumo, $id_projeto){
        form_modal_add = $(document).find("#form_filter_projeto_insumo");
        id_revisor = form_modal_add.find("#id_revisor").val();

        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.insumo.deletar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_insumo: $id_insumo, 
                id_projeto: $id_projeto,
                id_revisor: id_revisor
            },
            success: function(data){
                table_insumo.row(obj).remove().draw();

                $('.dataTables_scrollFootInner').find('#insumo_total_exibicao').html(data.response.total_exibicao);
            }
        });
    }


    function salvarServicoNoProjeto(){
        form_modal_add = $(document).find("#form_filter_projeto_servico");
        if (form_modal_add.find("#id_servico").val() == ''){
            faccao = form_modal_add.find("#codigo_faccao").val();
            id_projeto = form_modal_add.find("#id_projeto").val();
            tipo_de_servico = form_modal_add.find("#servico_codigo").val();
            custo_unitario = form_modal_add.find("#servico_preco").val();
            quantidade = form_modal_add.find("#servico_quantidade").val();
            custo_total = form_modal_add.find("#servico_custo_total").val();
            id_produto = form_modal_add.find("#servico_produto").val();
            id_revisor = form_modal_add.find("#id_revisor").val();

            servico_produto = form_modal_add.find("#servico_produto").val();
            servico_descricao = form_modal_add.find("#servico_descricao").val();
            servico_codigo = form_modal_add.find("#servico_codigo").val();

            servico_tecido = form_modal_add.find("#servico_tecido").val();
            servico_codigo_produto_acabado = form_modal_add.find("#servico_codigo_produto_acabado").val();
            servico_produto_acabado = form_modal_add.find("#servico_produto_acabado").val();

            limparMesagemErroAdd(form_modal_add);
            $.ajax({
                url: '{{ Route("lancamento_projeto.servico.adicionar") }}',
                type: 'POST',
                data: {
                    _token: '{{csrf_token()}}',
                    tipo_de_servico : tipo_de_servico,
                    custo_unitario : custo_unitario,
                    quantidade : quantidade,
                    custo_total : custo_total,
                    id_projeto : id_projeto,
                    faccao : faccao,
                    id_produto: id_produto,
                    id_revisor: id_revisor,
		    servico_produto : servico_produto,
                    servico_descricao : servico_descricao,
                    servico_codigo : servico_codigo,

                    servico_tecido : servico_tecido,
                    servico_codigo_produto_acabado : servico_codigo_produto_acabado,
                    servico_produto_acabado : servico_produto_acabado,
                },
                success: function(data) {
                    var field = [     
                        data.response.cnpj,
                        ajusteTamanhoTable(data.response.faccao),
						data.response.tipo,
                        produtoOuTecido(data.response),
                        ajusteTamanhoTable(data.response.tipo_de_servico),
                        {{-- ajusteTamanhoTable(data.response.tecido),
                        ajusteTamanhoTable(data.response.produto_acabado), --}}
                        data.response.preco_unitario,
                        data.response.quantidade,
                        data.response.custo_total,
                        createBtEditarServico(data.response.id, data.response.id_projeto),
                        createBtExcluirServico(data.response.id, data.response.id_projeto)
                    ];

                    table_servico.row.add(field).draw();
                    chamadaPopover();

                    $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.total);
                    limparCamposServico();

                    chamadaPopover();
                    form_modal_add.find("#servico_codigo").focus();
                },
                error: function(data){
                    var dados = data.responseJSON;
                    mensagemErroAdd(dados, form_modal_add);
                }
            });
        }else{
            salvarEdicaoServicoNoProjeto();
        }
    }

    function salvarEdicaoServicoNoProjeto(){
        form_modal_add = $(document).find("#form_filter_projeto_servico");

        id_servico = form_modal_add.find("#id_servico").val(); 
        id_projeto = form_modal_add.find("#id_projeto").val();
        tipo_de_servico = form_modal_add.find("#servico_codigo").val();
        custo_unitario = form_modal_add.find("#servico_preco").val();
        quantidade = form_modal_add.find("#servico_quantidade").val();
        custo_total = form_modal_add.find("#servico_custo_total").val();
        id_produto = form_modal_add.find("#servico_produto").val();
        faccao = form_modal_add.find("#codigo_faccao").val();
        id_revisor = form_modal_add.find("#id_revisor").val();

        servico_tecido = form_modal_add.find("#servico_tecido").val();
        servico_codigo_produto_acabado = form_modal_add.find("#servico_codigo_produto_acabado").val();
        servico_produto_acabado = form_modal_add.find("#servico_produto_acabado").val();

        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.servico.editar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                tipo_de_servico : tipo_de_servico,
                custo_unitario : custo_unitario,
                quantidade : quantidade,
                custo_total : custo_total,
                id_projeto : id_projeto,
                id_servico : id_servico,
                faccao : faccao,
                id_produto : id_produto,
                id_revisor : id_revisor,

                servico_tecido : servico_tecido,
                servico_codigo_produto_acabado : servico_codigo_produto_acabado,
                servico_produto_acabado : servico_produto_acabado,
            },
            success: function(data) {
                var field = [     
                    data.response.cnpj,
                    ajusteTamanhoTable(data.response.faccao),
                    data.response.tipo,
                    produtoOuTecido(data.response),
                    ajusteTamanhoTable(data.response.tipo_de_servico),
                    {{-- ajusteTamanhoTable(data.response.tecido),
                    ajusteTamanhoTable(data.response.produto_acabado), --}}
                    data.response.preco_unitario,
                    data.response.quantidade,
                    data.response.custo_total,
                    createBtEditarServico(data.response.id, data.response.id_projeto),
                    createBtExcluirServico(data.response.id, data.response.id_projeto)
                ];

                table_servico.row.add(field).draw();
                chamadaPopover();
                $(document).find("#form_filter_projeto_servico").find("#btn-create-servico-projeto").html("inserir serviço");
                $(document).find("#form_filter_projeto_servico").find("#btn-cancel-servico-projeto").hide();
                $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.total);
                limparCamposServico();
                form_modal_add.find("#servico_codigo").focus();
            },
            error: function(data){
                var dados = data.responseJSON;
                mensagemErroAdd(dados, form_modal_add);
            }
        });
    }

    function cancelarEdicaoServico(){
        form_modal_add = $(document).find("#form_filter_projeto_servico");
        data_form = form_modal_add.serialize();
        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.faccao.cancelar_edicao") }}',
            type: 'POST',
            data: data_form,
            success: function(data){
                var field = [         
                    data.response.cnpj,
                    ajusteTamanhoTable(data.response.faccao),
                    data.response.tipo,
                    produtoOuTecido(data.response),
                    ajusteTamanhoTable(data.response.tipo_de_servico),
                    data.response.preco_unitario,
                    data.response.quantidade,
                    data.response.custo_total,
                    createBtEditarServico(data.response.id, data.response.id_projeto),
                    createBtExcluirServico(data.response.id, data.response.id_projeto)
                ];

                table_servico.row.add(field).draw();
                chamadaPopover();
                $(document).find("#form_filter_projeto_servico").find("#btn-create-servico-projeto").html("inserir serviço");
                $(document).find("#form_filter_projeto_servico").find("#btn-cancel-servico-projeto").hide();
                $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.total);
                limparCamposServico();
            }
        });
    }

    function createBtEditarServico($id, $projeto_id){
        var html = "<a href=\"#\" class=\"bt-edit\" title='Editar' onclick=\"editarServicoNoProjeto($(this).parents('tr'), '"+$id+"','"+$projeto_id+"')\"></a>";
        return html;
    }

    function createBtExcluirServico ($id, $projeto_id){
        var html = "<a href=\"#\" class=\"bt-delete\" title='Excluir' onclick=\"excluirServicoNoProjeto($(this).parents('tr'), '"+$id+"','"+$projeto_id+"')\"></a>";
        return html;
    }

    function editarServicoNoProjeto(obj, $id_servico, $id_projeto){
        form_modal_add = $(document).find("#form_filter_projeto_servico");
    
        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.servico.get_editar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_servico: $id_servico, 
                id_projeto: $id_projeto
            },
            async: false,
            success: function(data){
                table_servico.row(obj).remove().draw();

                form_modal_add.find("#servico_tecido").removeAttr('disabled');
                form_modal_add.find("#servico_codigo_produto_acabado").removeAttr('disabled');
                form_modal_add.find("#servico_produto_acabado").removeAttr('disabled');

                form_modal_add.find("#servico_codigo_produto_acabado").val(data.response.codigo_produto_acabado);
                form_modal_add.find("#servico_produto_acabado").val(data.response.produto_acabado);

                form_modal_add.find("#faccao").val(data.response.faccao);
                form_modal_add.find("#codigo_faccao").val(data.response.codigo_faccao);
                form_modal_add.find("#id_servico").val(data.response.id);
                form_modal_add.find("#servico_codigo").val(data.response.tipo_de_servico);
                form_modal_add.find("#servico_preco").val(data.response.custo_unitario);
                form_modal_add.find("#servico_custo_total").val(data.response.custo_total);
                form_modal_add.find("#servico_produto").val(data.response.produto_id);
                form_modal_add.find("#servico_quantidade").val(retornarQuantidadeProduto(form_modal_add.find("#servico_produto").val()));
                getTecido(form_modal_add.find("#servico_produto").val(), data.response.tecido);

                form_modal_add.find("#btn-create-servico-projeto").html("editar serviço");
                form_modal_add.find("#btn-cancel-servico-projeto").show();

                $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.total);
                retornarDescricaoServico(form_modal_add);
            }
        });
    }
 
    function excluirServicoNoProjeto(obj, $id_servico, $id_projeto){
        form_modal_add = $(document).find("#form_filter_projeto_servico");
        id_revisor = form_modal_add.find("#id_revisor").val();
        $.ajax({
            url: '{{ Route("lancamento_projeto.servico.deletar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_servico: $id_servico, 
                id_projeto: $id_projeto,
                id_revisor: id_revisor
            },
            success: function(data){
                table_servico.row(obj).remove().draw();

                $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.total_exibicao);
            }
        });
    }

    function salvarFaccao($codigo, $this){
        id = $this.find("#tipo_de_servico").val();
        id_faccao = $codigo;
        $.ajax({
            url: '{{ Route("lancamento_projeto.faccao.salvar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_faccao: id_faccao, 
                id: id
            },
            success: function(data){

            }
        });
    }

    //Calculo

    function calculosTecido(form_tecido_add){
        limparMesagemErroAdd(form_tecido_add);

        preco_unitario = form_tecido_add.find("#tecido_preco_unitario").val();
        consumo = form_tecido_add.find("#tecido_consumo").val();
        quantidade = form_tecido_add.find("#tecido_quantidade").val();

        $.ajax({
            url: '{{ route('produto.tecido.calculo')}}',
            data: {
                _token: '{{ csrf_token() }}',
                preco_unitario: preco_unitario,
                consumo: consumo,
                quantidade: quantidade
            },
            method: 'POST',
            async: false,
            success: function(callback){
                var produto = callback;
                form_tecido_add.find('#tecido_consumo_total').val(produto.consumo_total);
                form_tecido_add.find('#tecido_valor_total').val(produto.valor_total);
            },
            error: function(callback){
                var dados = callback.responseJSON;
                form_tecido_add.find('#tecido_consumo_total').val(callback.responseJSON.response.consumo_total);
                form_tecido_add.find('#tecido_valor_total').val(callback.responseJSON.response.valor_total);
                limparMesagemErroAdd(form_tecido_add);
                mensagemErroAdd(dados, form_tecido_add);
            }
        });
    }

    function calculosInsumo(form_insumo_add){
        limparMesagemErroAdd(form_insumo_add);

        preco_unitario = form_insumo_add.find("#insumo_preco_unitario").val();
        quantidade = form_insumo_add.find("#insumo_quantidade").val();
        consumo = form_insumo_add.find("#insumo_consumo").val();
        $.ajax({
            url: '{{ route('produto.insumo.calculo')}}',
            data: {
                _token: '{{ csrf_token() }}',
                preco_unitario: preco_unitario,
                quantidade: quantidade,
                consumo: consumo
            },
            method: 'POST',
            async: false,
            success: function(callback){
                var produto = callback;
                form_insumo_add.find('#insumo_consumo_total').val(produto.consumo_total);
                form_insumo_add.find('#insumo_valor_total').val(produto.valor_total);
            },
            error: function(callback){
                var dados = callback.responseJSON;
                form_insumo_add.find('#insumo_valor_total').val(callback.responseJSON.response.valor_total);
                limparMesagemErroAdd(form_insumo_add);
                mensagemErroAdd(dados, form_insumo_add);
            }
        });
    }

    function calculosServico(form_servico_add){
        limparMesagemErroAdd(form_servico_add);

        quantidade = form_servico_add.find("#servico_quantidade").val();
        preco = form_servico_add.find("#servico_preco").val();

        $.ajax({
            url: '{{ route('lancamento_projeto.faccao.calculo')}}',
            data: {
                _token: '{{ csrf_token() }}',
                preco: preco,
                quantidade: quantidade,
            },
            method: 'POST',
            success: function(callback){
                form_servico_add.find('#servico_custo_total').val(callback.response.total);
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd(form_servico_add);
                mensagemErroAdd(dados, form_servico_add);
            }
        });
    }

    function getPrecoServico(){
        form_servico_add = $(document).find('#form_filter_projeto_servico');

        tipo_de_servico = form_servico_add.find("#tipo_de_servico").val();
        quantidade = form_servico_add.find("#servico_quantidade").val();

        $.ajax({
            url: '{{ route('faccao_tipo_de_servico.get_preco')}}',
            data: {
                _token: '{{ csrf_token() }}',
                tipo_de_servico: tipo_de_servico,
                quantidade: quantidade
            },
            method: 'POST',
            success: function(data){
                form_servico_add.find("#servico_preco").val(data.response.preco);
                form_servico_add.find("#servico_custo_total").val(data.response.custo_total);
                form_servico_add.find('#servico_preco').removeAttr("disabled");
            },
            error: function(callback){
                form_servico_add.find("#servico_preco").attr("disabled", "disabled");
            }
        });
    }

    function resultado(){
        form_resultado_add = $(document).find("#form_projeto_resultado");
        data_form = form_resultado_add.serialize();
        $.ajax({
            url: '{{ route('lancamento_projeto.resultado')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                form_resultado_add = $(document).find("#form_projeto_resultado");
                form_resultado_add.find('#resultado_descricao_projeto').html(data.response.resultado.descricao_projeto);
                form_resultado_add.find('#resultado_total_pedido').html(data.response.resultado.total_pedido);
                form_resultado_add.find('#resultado_subtotal_pedido').html(data.response.resultado.subtotal_pedido);
                form_resultado_add.find('#resultado_custo_tecido').html(data.response.resultado.custo_tecido);
                form_resultado_add.find('#resultado_quantidade_total').html(data.response.resultado.quantidade_total);
                form_resultado_add.find('#resultado_custo_insumo').html(data.response.resultado.custo_insumo);
                form_resultado_add.find('#resultado_comissao').html(data.response.resultado.comissao);
                form_resultado_add.find('#resultado_mao_de_obra').html(data.response.resultado.mao_de_obra);
                form_resultado_add.find('#resultado_custo_total').html(data.response.resultado.custo_total);
                form_resultado_add.find('#resultado_custo_unitario').html(data.response.resultado.custo_unitario);
                form_resultado_add.find('#resultado_acima_tabela').html(data.response.resultado.acima_tabela);
                form_resultado_add.find('#resultado_preco_venda').html(data.response.resultado.preco_venda);
                form_resultado_add.find('#resultado_desconto').html(data.response.resultado.desconto);
                form_resultado_add.find('#resultado_valor_desconto').html(data.response.resultado.valor_desconto);
                form_resultado_add.find('#resultado_frete_adicional').html(data.response.resultado.frete_valor);
                form_resultado_add.find('#frete_adicional').html(construcaoFreteAdicional(data.response.resultado));

                chamadaPopover();
            }
        });
    }

    function construcaoFreteAdicional($this){
        html = "";

        if($this.frete_porcetagem == "0,00"){
            html = "Frete Adicional";
        }else{
            html = '<div><div class="bt-detalhe-right" data-toggle="popover" data-placement="top" data-content="<p>Frete Adicional da Região '+$this.regiao+' é de '+$this.frete_porcetagem+' %">Frete Adicional</div></div>';
        }
            
        return html;
    }

    //Limpeza

    function limparMesagemErroAdd(form_modal_add){   
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error, form_modal_add){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_add, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form_modal_add, input, message){
        if(input.localeCompare('servico') == 0){
            var $input = $(form_modal_add).find("#bt-search-servico-busca");
            $(form_modal_add).find("input[name='servico']").addClass('error-input');
        }else if(input.localeCompare('nome_cliente') == 0){
            var $input = form_modal_add.find("#bt-search-cliente");
            $(form_modal_add).find("input[name='nome_cliente']").addClass('error-input');
        }else if(input.localeCompare('codigo_cliente') == 0){
            var $input = form_modal_add.find("#bt-search-cliente");
            $(form_modal_add).find("input[name='nome_cliente']").addClass('error-input');
        }else if(input.localeCompare('condicao_pagamento_descr') == 0){
            var $input = $(form_modal_add).find("#bt-search-condicao_pagamento");
            $(form_modal_add).find("input[name='condicao_pagamento_descr']").addClass('error-input');
        }else if(input.localeCompare('produto_codigo') == 0){
            var $input = $(form_modal_add).find("#bt-buscar-produto");
            $(form_modal_add).find("input[name='produto_codigo']").addClass('error-input');
        }else if(input.localeCompare('tecido_codigo') == 0){
            var $input = $(form_modal_add).find("#bt-search-tecido");
            $(form_modal_add).find("input[name='tecido_codigo']").addClass('error-input');
        }else if(input.localeCompare('insumo_codigo') == 0){
            var $input = $(form_modal_add).find("#bt-search-insumo");
            $(form_modal_add).find("input[name='insumo_codigo']").addClass('error-input');
        }else if(input.localeCompare('servico_codigo_produto_acabado') == 0){
            var $input = $(form_modal_add).find("#bt-search-produto");
            $(form_modal_add).find("input[name='servico_codigo_produto_acabado']").addClass('error-input');
        }else{
            var $input = $(form_modal_add).find("input[name='"+input+"'], select[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function limparCamposProduto(){
        form_modal_add = $(document).find("#form_filter_projeto_produto");

        form_modal_add.find("#id_produto").val("");
        form_modal_add.find("#produto_codigo").val("");
        form_modal_add.find("#produto_descricao").val("");
        form_modal_add.find("#produto_preco_venda").val("");
        form_modal_add.find("#produto_quantidade").val("");
        form_modal_add.find("#produto_detalhes").val("");
        form_modal_add.find("#produto_ficha").val("");
        form_modal_add.find("#id_codigo").val("");
        form_modal_add.find("#produto_ncm").val("");
        form_modal_add.find("#produto_peso").val("");

        form_modal_add.find("#produto_codigo").focus();
    }

    function limparCamposTecido(){
        form_modal_add = $(document).find("#form_filter_projeto_tecido");

        form_modal_add.find("#id_tecido").val('');
        form_modal_add.find("#tecido_codigo").val('');
        form_modal_add.find("#tecido_descricao").val('');
        form_modal_add.find("#tecido_consumo").val('');
        form_modal_add.find("#tecido_preco_unitario").val('');
        form_modal_add.find("#tecido_valor_total").val('');
        form_modal_add.find("#tecido_consumo_total").val('');
        form_modal_add.find("#tecido_produto").val(0);
        form_modal_add.find("#tecido_quantidade").val('');

        table_tecido_estabelecimento.clear().draw();
    }

    function limparCamposInsumo(){
        form_modal_add = $(document).find("#form_filter_projeto_insumo");

        form_modal_add.find("id_insumo").val('');
        form_modal_add.find("#insumo_codigo").val('');
        form_modal_add.find("#insumo_descricao").val('');
        form_modal_add.find("#insumo_consumo").val('');
        form_modal_add.find("#insumo_preco_unitario").val('');
        form_modal_add.find("#insumo_valor_total").val('');
        form_modal_add.find("#insumo_consumo_total").val('');
        form_modal_add.find("#insumo_produto").val(0);
        form_modal_add.find("#insumo_quantidade").val('');

        table_insumo_estabelecimento.clear().draw();
    }

    function limparCamposServico(){
        form_modal_add = $(document).find("#form_filter_projeto_servico");
        form_modal_add.find("#id_servico").val('');
        form_modal_add.find("#faccao").val('');
        form_modal_add.find("#codigo_faccao").val('');
        form_modal_add.find("#servico_codigo").val('');
        form_modal_add.find("#servico_descricao").val('');
        form_modal_add.find("#servico_preco").val('');
        form_modal_add.find("#servico_custo_total").val('');
        form_modal_add.find("#servico_produto").val(0);
        form_modal_add.find("#servico_quantidade").val('');

        form_modal_add.find("#servico_tecido").val(0);
        form_modal_add.find("#servico_codigo_produto_acabado").val('');
        form_modal_add.find("#servico_produto_acabado").val('');

        form_modal_add.find("#servico_tecido").attr("disabled", "disabled");
        form_modal_add.find("#servico_codigo_produto_acabado").attr("disabled", "disabled");
        form_modal_add.find("#servico_produto_acabado").attr("disabled", "disabled");

        form_modal_add.find("#servico_codigo").focus();
        form_modal_add.find("#servico_preco").attr("disabled", "disabled");
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

    function alterarQuantidadeOutrasTelas(id_produto){
        form_tecido_add = $(document).find("#form_filter_projeto_tecido");

        form_insumo_add = $(document).find("#form_filter_projeto_insumo");

        form_servico_add = $(document).find("#form_filter_projeto_servico");
        id_projeto = form_tecido_add.find("#id_projeto").val();
        $.ajax({
            url: '{{ Route("lancamento_projeto.reajuste") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_projeto : id_projeto,
                id_produto : id_produto 
            },
            success: function(data){
                if(data.response.tecidos != ''){
                    tecidos = [];
                    table_tecido.clear().draw();
                    for (var tecido in data.response.tecidos.tabela){
                        var field = [
                            data.response.tecidos.tabela[tecido].codigo,
                            ajusteTamanhoTable(data.response.tecidos.tabela[tecido].descricao),
                            ajusteTamanhoTable(data.response.tecidos.tabela[tecido].produto),
                            data.response.tecidos.tabela[tecido].preco_unitario,
                            data.response.tecidos.tabela[tecido].consumo_unitario,
                            data.response.tecidos.tabela[tecido].consumo_total,
                            data.response.tecidos.tabela[tecido].total_custo,
                            createBtEditarTecido(data.response.tecidos.tabela[tecido].id, data.response.tecidos.tabela[tecido].projeto_id),
                            createBtExcluirTecido(data.response.tecidos.tabela[tecido].id, data.response.tecidos.tabela[tecido].projeto_id)
                        ]; 
    
                        tecidos.push(field);
                    }
                    table_tecido.rows.add(tecidos).draw();
                    $('.dataTables_scrollFootInner').find('#tecido_total_exibicao').html(data.response.tecidos.total);
                }
                

                if(data.response.insumos != ''){
                    insumos = [];
                    table_insumo.clear().draw();
                    for (var insumo in data.response.insumos.tabela){
                        var field = [
                            data.response.insumos.tabela[insumo].codigo,
                            ajusteTamanhoTable(data.response.insumos.tabela[insumo].descricao),
                            ajusteTamanhoTable(data.response.insumos.tabela[insumo].produto),
                            data.response.insumos.tabela[insumo].preco_unitario,
                            data.response.insumos.tabela[insumo].consumo_total,
                            data.response.insumos.tabela[insumo].total_custo,
                            createBtEditarInsumo(data.response.insumos.tabela[insumo].id, data.response.insumos.tabela[insumo].projeto_id),
                            createBtExcluirInsumo(data.response.insumos.tabela[insumo].id, data.response.insumos.tabela[insumo].projeto_id)
                        ];
                        insumos.push(field);
                    }
                    table_insumo.rows.add(insumos).draw();
                    $('.dataTables_scrollFootInner').find('#insumo_total_exibicao').html(data.response.insumos.total);
                }

                if(data.response.servicos != ''){
                    servicos = [];
                    table_servico.clear().draw();
                    for (var servico in data.response.servicos.tabela){
                        var field = [
                            data.response.servicos.tabela[servico].cnpj,
                            ajusteTamanhoTable(data.response.servicos.tabela[servico].faccao),
                            data.response.servicos.tabela[servico].tipo,
                            produtoOuTecido(data.response.servicos.tabela[servico]),
                            ajusteTamanhoTable(data.response.servicos.tabela[servico].tipo_de_servico),
                            data.response.servicos.tabela[servico].preco_unitario,
                            data.response.servicos.tabela[servico].quantidade,
                            data.response.servicos.tabela[servico].total_custo,
                            createBtEditarServico(data.response.servicos.tabela[servico].id, data.response.servicos.tabela[servico].id_projeto),
                            createBtExcluirServico(data.response.servicos.tabela[servico].id, data.response.servicos.tabela[servico].id_projeto)
                        ];
                        servicos.push(field);
                    }
                    table_servico.rows.add(servicos).draw();
                    chamadaPopover();
                    $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.servicos.total);
                }
            }
        });
    }

    function revisaoProjeto(){
        form_modal_add = $(document).find("#cadprojeto");
    
        data_form = form_modal_add.serialize();

        $.ajax({
            url: "{{ route('lancamento_projeto.revisao_ok') }}", 
            dataType: 'json',
            data: data_form,
            method: 'POST',
            async: false,
            success: function(callback){
                message("Atenção", "Projeto gerado com Sucesso");
                $(form_modal_add).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){

                message("Atenção", callback.responseJSON.message);
                var dados = callback.responseJSON;
            }
        });
    }

    function disabledTabs(){
		$(document).find("#lancamento-projeto-tecido-tab").addClass('disabled');
        $(document).find("#lancamento-projeto-insumo-tab").addClass('disabled');
        $(document).find("#lancamento-projeto-servico-tab").addClass('disabled');
        $(document).find("#lancamento-projeto-resultado-tab").addClass('disabled');
	}
	function removeDisabledTabs(){
		$(document).find("#lancamento-projeto-tecido-tab").removeClass('disabled');
        $(document).find("#lancamento-projeto-insumo-tab").removeClass('disabled');
        $(document).find("#lancamento-projeto-servico-tab").removeClass('disabled');
        $(document).find("#lancamento-projeto-resultado-tab").removeClass('disabled');
    }
    
    function getProduto(){
        form_modal_add = $(document).find("#cadprojeto");
    
        data_form = form_modal_add.serialize();
        $.ajax({
            url: '{{ route('lancamento_projeto.produto.get_produto')}}',
            data: data_form,
            method: 'POST',
            async: false,
            success: function(callback){
                construcaoSelectProduto(callback.response.produtos);
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }

    function construcaoSelectProduto($array){
        form_tecido_add = $(document).find('#form_filter_projeto_tecido');
        form_insumo_add = $(document).find('#form_filter_projeto_insumo');
        form_servico_add = $(document).find('#form_filter_projeto_servico');

        form_tecido_add.find("#tecido_produto").empty();
        var option = new Array();
        $.each($array, function( index, value ) {
            option[index] = document.createElement('option');
            $( option[index] ).attr( {value : index} );
            $( option[index] ).append( value );

            form_tecido_add.find("#tecido_produto").append( option[index] );//jogando um à um os options no próximo combo
        });
        form_insumo_add.find("#insumo_produto").empty();
        var option = new Array();
        $.each($array, function( index, value ) {
            option[index] = document.createElement('option');
            $( option[index] ).attr( {value : index} );
            $( option[index] ).append( value );

            form_insumo_add.find("#insumo_produto").append( option[index] );//jogando um à um os options no próximo combo
        });
        form_servico_add.find("#servico_produto").empty();
        var option = new Array();
        $.each($array, function( index, value ) {
            option[index] = document.createElement('option');
            $( option[index] ).attr( {value : index} );
            $( option[index] ).append( value );

            form_servico_add.find("#servico_produto").append( option[index] );//jogando um à um os options no próximo combo
        });
    }

    function getTecido($value, $index){
        form_modal_add = $(document).find("#form_filter_projeto_servico");
        $.ajax({
            url: '{{ route('lancamento_projeto.tecido.get_tecido')}}',
            data: {
                _token: '{{csrf_token()}}',
                id_produto: $value
            },
            method: 'POST',
            success: function(callback){
                construcaoSelectTecido(callback.response.tecidos, $index);
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }

    function construcaoSelectTecido($array, $index){
        form_servico_add = $(document).find('#form_filter_projeto_servico');

        form_servico_add.find("#servico_tecido").empty();
        var option = new Array();
        $.each($array, function( index, value ) {
            option[index] = document.createElement('option');
            $( option[index] ).attr( {value : index} );
            $( option[index] ).append( value );

            form_servico_add.find("#servico_tecido").append( option[index] );//jogando um à um os options no próximo combo
        });
        form_modal_add.find("#servico_tecido").val($index);
        if($index != 0){
            form_modal_add.find("#servico_quantidade").val(retornarQuantidadeTecido(form_modal_add.find("#servico_tecido").val()));
        }
    }

    function getDetalheProduto($produto, $detalhes){
        html = $produto;
        if($detalhes != ''){
            html = html + '<a href="#" class="bt-detalhe" data-toggle="popover" data-placement="top" title="Detalhes de Produção"data-content="<p>'+$detalhes+'"></a>';
        }
    
        return html;
    }

    function recusarProjetoAdd(){
        form_modal_add = $(document).find("#cadprojeto");
        id_projeto = form_modal_add.find("#id_projeto").val();
        $.ajax({
            url: '{{ route('lancamento_projeto.modal.recusar') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}', 
                id_projeto: id_projeto
            },
            success: function(data){
                createModal("recusa-projeto-modal", "Recusar Projeto", data, '')
                ajaxForm('#recusa-projeto-modal');
            }
        })
        
    }

    function ajaxForm($modal){
        $($modal).find('[type="submit"]').on("click", function(event){
            event.stopPropagation();
            var form = $(this).parents('form');
            var form_data = form.serialize();
            var url = form.attr("action");
            $.ajax({
                url: url,
                dataType: 'json',
                data: form_data,
                method: 'POST',
                success: function(data){
                    $($modal).modal('hide');
                    $(document).find('#credito-modal').modal('hide');
                    form_modal_add = $(document).find("#cadprojeto");
                    $(form_modal_add).parents('.modal').modal('hide');
                    filterAjax($("#form_filter").serialize());
                },
                error: function(data){
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputsAdd(form, field, errors[field])
                    }
                }
            });
        });
    }

    function liberarTabs(){
        form_modal_add = $(document).find("#form_filter_projeto_produto");
    
        data_form = form_modal_add.serialize();
        $.ajax({
            url: '{{ route('lancamento_projeto.liberar_tabs')}}',
            data: data_form,
            method: 'POST',
            async: false,
            success: function(callback){
                removeDisabledTabs();
            },
            error: function(callback){
                disabledTabs();
            }
        });
    }

    function createBodyPopOver($this){
        var $return = "";
        $.each($this,function(index, el) {
            $return += "<p>"+this+"</p>";
        });
        return $return;
    }

    function produtoOuTecido($this){
        var $return = "";
        if($this.tecido == ''){
            $return ="<div>"+
                        "<div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$this.produto+"'>"+
                            $this.produto+
                        "</div>"+
                    "</div>"; 
        }else{
            $return = "<div>"+
                        "<div>"+
                            "<a href=\"#\" class=\"bt-detalhe\" data-toggle=\"popover\" data-placement=\"top\" data-title=\"Detalhes\" data-content=\"<p><b>Tecido:</b> "+$this.tecido+"<p><b>Ref. Produto:</b> "+$this.produto+" <p><b>Produto Acabado:</b> "+$this.produto_acabado+"\"></a>"+
                            $this.tecido+
                        "</div>"+
                    "</div>";
        }
        return $return;
    }

    function chamadaPopover(){
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
    }

</script>
@endsection
