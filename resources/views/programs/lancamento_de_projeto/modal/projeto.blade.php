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
    @if(!empty($dados['revisor']))
    <li class="nav-item">
        <a class="nav-link" id="lancamento-projeto-revisao_em_massa-produto-tab" data-toggle="tab" href="#lancamento_projeto_revisao_em_massa_produto" role="tab" aria-controls="lancamento_projeto_revisao_em_massa_produto" aria-selected="false">Produto Revisão em Massa</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="lancamento-projeto-revisao_em_massa-tecido-tab" data-toggle="tab" href="#lancamento_projeto_revisao_em_massa_tecido" role="tab" aria-controls="lancamento_projeto_revisao_em_massa_tecido" aria-selected="false">Tecido/Fios Revisão em Massa</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="lancamento-projeto-revisao_em_massa-insumo-tab" data-toggle="tab" href="#lancamento_projeto_revisao_em_massa_insumo" role="tab" aria-controls="lancamento_projeto_revisao_em_massa_insumo" aria-selected="false">Insumo/Acessório Revisão em Massa</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="lancamento-projeto-revisao_em_massa-servico-tab" data-toggle="tab" href="#lancamento_projeto_revisao_em_massa_servico" role="tab" aria-controls="lancamento_projeto_revisao_em_massa_servico" aria-selected="false">Serviço Revisão em Massa</a>
    </li>
    @endif
	<li class="nav-item">
		<a class="nav-link" id="lancamento-projeto-resultado-tab" data-toggle="tab" href="#lancamento_projeto_resultado" role="tab" aria-controls="lancamento_projeto_resultado" aria-selected="false">Resultado</a>
	</li>
</ul>

<div class="tab-content pt-3" id="LancamentoProjetoHeaderContainer">

	<div class="tab-pane show active" id="lancamento_projeto_header" role="tabpanel" aria-labelledby="dados-tab">
		<form action="#" method="post" id="cadprojeto" name="cadprojeto" class="cadProjeto" onsubmit="return false">
            @csrf
            {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
            {!! Form::hidden('id_representante', $dados['representante'], ['id' => 'id_representante']) !!}
            {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
            <div class="form-row">
                <div class="form-group col-sm-2 content-not-estabel">
                    {!! Form::label('numero_projeto', 'Número do Projeto') !!}
                    {!! Form::text('numero_projeto', $dados['numero_projeto'], ['id' => 'numero_projeto', 'class' => 'form-control essencial input-label text-right', 'disabled' => 'disabled']) !!}
                </div>
                <div class="form-group col-sm-5 content-not-estabel">
                    {!! Form::label('nome_projeto', 'Nome do Projeto') !!}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                    {!! Form::text('nome_projeto', $dados['nome_projeto'], ['id' => 'nome_projeto', 'class' => 'form-control essencial input-label', 'maxlength' => '60']) !!}
                </div>
                <div class="form-group col-sm-5 content-not-estabel">
                    {!! Form::label('descricao_vendedor', 'Vendedor') !!}
                    {!! Form::text('descricao_vendedor', $dados['descricao_vendedor'], ['id' => 'descricao_vendedor', 'class' => 'form-control essencial input-label', 'disabled' => 'disabled']) !!}
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
                    {{ Form::text('num_pedido', $dados['pedido'], ['id' => 'num_pedido', 'class' => 'form-control essencial input-label', 'placeholder' => 'Pedido do Cliente', 'maxlength' => '30']) }}
                </div>
            </div>
            <div class="form-row">
                
                <div class="form-group col-sm-9" id="condicao_pagamento_group" class="invisible">
                    {{ Form::label('condicao_pagamento_descr', 'Condição de Pagamento', []) }}	 <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                    <div class="input-group">
                        {{ Form::text('condicao_pagamento_descr', $dados['pagamento_descricao'], array('id' => 'condicao_pagamento_descr', 'class' => 'form-control essencial')) }}
                        {{ Form::hidden('condicao_pagamento', $dados['pagamento'], ['id' => 'condicao_pagamento', 'class' => ''])}}
                        <span class="input-group-addon border rounded-right" id="bt-search-condicao_pagamento" data-route="{{ route("condicoes_pagamento_web.dialog") }}"><i id="bt-view-condicao" class="bt-view m-2"></i></span>
                    </div>
                </div>
                
                <div class="form-group col-sm-3">
                    {{ Form::label('tipo_produto_producao', 'Tipo Produção', []) }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                    {!! Form::select('tipo_produto_producao', $tipo_produto_producao['todos'], $dados['linha'], ['id' => 'tipo_produto_producao', 'class' => 'form-control', 'placeholder' => 'Selecione']) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-6">
                    {{ Form::label('nome_contato', 'Nome Contato', []) }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                    {{ Form::text('nome_contato', $dados['nome_contato'], ['id' => 'nome_contato', 'class' => 'form-control', "maxlength" => "250"]) }}
                </div>
                <div class="form group col-sm-6">
                    {{ Form::label('email_contato', 'Email Contato', []) }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                    {{ Form::text('email_contato', $dados['email_contato'], ['id' => 'email_contato', 'class' => 'form-control', "maxlength" => "250"]) }}
                </div>
            </div>            
            <div class="col-sm-12 mt-5" id="button-bottom">
                    <button type="button" id="bt_ir_para_produtos" class="btn btn-info troca-aba float-right">Ir para produtos >></button>
            </div>
            <div class="col-sm-12 mt-5" id="button-bottom">
                <button type="button" id="salvar_lancamento_projeto" class="btn btn-success float-right">Enviar projeto</button>
            </div>
		</form>
    </div>
    
    <div class="tab-pane" id="lancamento_projeto_produto" role="tabpanel" aria-labelledby="dados-tab">
        <div class="content-filter-dialog">	
            <form action="post" name="form_filter_projeto_produto" class="cadProjeto" id="form_filter_projeto_produto" onsubmit="return false;">
                <p><strong>Inserir Produto</strong></p>
                @csrf
                {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
                {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
                {!! Form::hidden('id_produto', '', ['id' => 'id_produto']) !!}
                {!! Form::hidden('produto_ficha', '', ['id' => 'produto_ficha']) !!}
                {!! Form::hidden('id_codigo', '', ['id' => 'id_codigo']) !!}
                {{ Form::hidden('cnpj_cliente', $dados['cliente'], ['id' => 'cnpj_cliente', 'class' => '']) }}
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
                    <div class="form-group col-sm-5">
                        {{ Form::label('produto_descricao', 'Descrição', []) }}
                        {{ Form::text('produto_descricao', '', ['id' => 'produto_descricao', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Nome do produto', 'maxlength' => '120']) }}
                        {{ Form::hidden('produto_descricao_hidden', '', ['id' => 'produto_descricao_hidden']) }}
                    </div>
                    <div class="form-group col-sm-2" id="id_produto_preco_venda">
                        {{ Form::label('produto_preco_venda', 'Preço de Venda', []) }}
                        {{ Form::text('produto_preco_venda', '', ['id' => 'produto_preco_venda', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Preço de Venda', 'maxlength' => '8']) }}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('produto_quantidade', 'Quantidade', []) }}
                        {{ Form::text('produto_quantidade', '', ['id' => 'produto_quantidade', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Quantidade', 'maxlength' => '8']) }}
                    </div>
                </div>
                <div class="form-row">
                    @if(!empty($dados['revisor']))
                    <div class="form-group col-sm-2">
                        {{ Form::label('produto_ncm', 'NCM', []) }}
                        {{ Form::text('produto_ncm', '', ['id' => 'produto_ncm', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'NCM', 'maxlength' => '8']) }}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('produto_peso', 'Peso Líquido', []) }}
                        {{ Form::text('produto_peso', '', ['id' => 'produto_peso', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Peso Líquido', 'maxlength' => '8']) }}
                    </div>
                    <div class="form-group col-sm-8">
                    @else
                    <div class="form-group col-sm-12">
                    @endif
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
                <table class="table table-striped table-verificacao" id="table-filters-produtos">
                    <thead>
                        <tr>
                            <th class="tb_number"></th>
                            <th class="td_codigo_produto">Código</th>
                            <th>Descrição</th>
                            @if(!empty($dados['revisor']))
                            <th class="tb_number">NCM</th>
                            <th class="tb_number">Peso</th>
                            @endif
                            <th class="tb_number">Preço Venda</th>
                            <th class="tb_number">Preço Tab.</th>
                            <th class="tb_number">Quantidade</th>
                            <th class="tb_number">Tecidos/Fios</th>
                            <th class="tb_number">Insumos</th>
                            <th class="tb_number">Serviços</th>
                            <th class="tb_number">Valor Total</th>
                            <th class="td_acao"></th>
                            <th class="td_acao"></th>
                            <th class="td_acao"></th>
                            <th class="td_acao"></th>
                            @if(!empty($dados['revisor']))
                            <th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($produtos_tabela))
                            @foreach($produtos_tabela as $produto)
                                @if(!empty($dados['revisor']))
                                    @if($produto['produto_com_faccao'] === true) 
                                        <tr>
                                    @else
                                        <tr class="error-tr">
                                    @endif
                                @else
                                    <tr>
                                @endif
                                    <td>{{ $produto['indice'] }}</td>
                                    <td>{{ $produto['codigo'] }}</td>
                                    <td class="detalhe_produto">
                                        @if(!empty($produto['detalhe_producao']))
                                            <div><div data-toggle="popover" data-placement="right" data-title="Detalhes" data-content="<p><b>Produto:</b>{{ $produto['descricao'] }}<p><b>Produção:</b> <p>{{ $produto['detalhe_producao'] }}">
                                                <a href="#" class="btn-informacao"></a>
                                                {{ $produto['descricao'] }}
                                            </div></div>
                                        @else
                                            <div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $produto['descricao'] }}">{{ $produto['descricao'] }}</div></div>
                                        @endif
                                        
                                    </td>
                                    @if(!empty($dados['revisor']))
                                    <td class="tb_number">{{ $produto['ncm'] }}</td>
                                    <td class="tb_number">{{ $produto['peso'] }}</td>
                                    @endif
                                    @if($produto['desconto_acima_permitido'] === true)
                                    <td class="tb_number">
                                        @if($intercompany == false)
                                            <div>
                                                <div data-toggle="popover" data-placement="top" data-title="Detalhes" data-content="<p>Desconto está acima do máximo permitido. Favor verificar.">
                                                <a href="#" class="btn-informacao"></a>
                                                    {{ $produto['preco_venda'] }}
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    @else
                                    <td class="tb_number">{{ $produto['preco_venda'] }}</td>
                                    @endif
                                    <td class="tb_number">{{ $produto['custo_unitario'] }}</td>
                                    <td class="tb_number">{{ $produto['quantidade'] }}</td>
                                    <td class="tb_number">{{ $produto['tecido_total'] }}<a href="#" class="bt-plus-direita" title="Composição" data-url="{{ route('lancamento_projeto.modal.composicao') }}" data-title="{{ $produto['descricao'] }}" data-id_produto="'{{ $produto['id'] }}'" data-id_projeto="'{{ $dados['id'] }}'" data-id_revisor="{{ $dados['revisor'] }}" data-posicao="tecido" onclick="dialogComposicao($(this))"></a></td>
                                    <td class="tb_number">{{ $produto['insumo_total'] }}<a href="#" class="bt-plus-direita" title="Composição" data-url="{{ route('lancamento_projeto.modal.composicao') }}" data-title="{{ $produto['descricao'] }}" data-id_produto="'{{ $produto['id'] }}'" data-id_projeto="'{{ $dados['id'] }}'" data-id_revisor="{{ $dados['revisor'] }}" data-posicao="insumo" onclick="dialogComposicao($(this))"></a></td>
                                    <td class="tb_number">{{ $produto['servico_total'] }}<a href="#" class="bt-plus-direita" title="Composição" data-url="{{ route('lancamento_projeto.modal.composicao') }}" data-title="{{ $produto['descricao'] }}" data-id_produto="'{{ $produto['id'] }}'" data-id_projeto="'{{ $dados['id'] }}'" data-id_revisor="{{ $dados['revisor'] }}" data-posicao="servico" onclick="dialogComposicao($(this))"></a></td>
                                    <td class="tb_number">{{ $produto['custo_total'] }}</td>
                                    <td><a href="#" class="bt-duplicar" data-toggle='tooltip' data-html='true' data-placement="right" title='Duplicar' data-id_produto="{{ $produto['id'] }}" onclick="showModalDuplicarProduto($(this))"></a></td>
                                    <td><a href="#" class="btn-pedido" title="Documentos/Imagens" data-url="{{ route('lancamento_projeto.modal.composicao') }}" data-title="{{ $produto['descricao'] }}" data-id_produto="'{{ $produto['id'] }}'" data-id_projeto="'{{ $dados['id'] }}'" data-id_revisor="{{ $dados['revisor'] }}" data-posicao="arquivo" onclick="dialogComposicao($(this))"></a></td>
                                    <td><a href="#" class="bt-edit" title='Editar' onclick="editarProdutoNoProjeto($(this).parents('tr'), '{{ $produto['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                    <td><a href="#" class="bt-delete" title='Excluir' onclick="excluirProdutoNoProjeto($(this).parents('tr'), '{{ $produto['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                    @if(!empty($dados['revisor']))
                                    <td>
                                        @if($produto['produto_com_faccao'] === true)
                                            <i class='fa fa-check check-icon' aria-hidden='true'></i>
                                        @else
                                            <div><div data-toggle="popover" data-placement="right" data-title="Detalhes" data-content="<p>{{ $produto['mensagem_error'] }}" style="text-align: center;">
                                                <i class='fa fa-times error-icon'  aria-hidden='true'></i>
                                            </div></div>
                                        @endif
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td class="td_codigo_produto"></td>
                            <td></td>
                            @if(!empty($dados['revisor']))
                            <td class="tb_number"></td>
                            <td class="tb_number"></td>
                            @endif
                            <td class="tb_number">Total:</td>
                            <td class="tb_number" id='produto_total_custo_unitario'>{{ $total_custo_unitario }}</td>
                            <td class="tb_number" id='produto_total_exibicao'>{{ $produto_total_ex }}</td>
                            <td class="tb_number" id='produto_total_tecido'>{{ $total_custo_tecido }}</td>
                            <td class="tb_number" id='produto_total_insumo'>{{ $total_custo_insumo }}</td>
                            <td class="tb_number" id='produto_total_servico'>{{ $total_custo_servico }}</td>
                            <td class="tb_number" id='produto_total_custo'>{{ $total_custo_total }}</td>
                            <td class="td_acao"></td>
                            <td class="td_acao"></td>
                            <td class="td_acao"></td>
                            <td class="td_acao"></td>
                            @if(!empty($dados['revisor']))
                            <td></td>
                            @endif
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-sm-12 mt-5" id="button-bottom">
                <button type="button" id="bt_ir_para_cabecalho" class="btn troca-aba btn-info"><< Voltar para o cabeçalho</button>
                <button type="button" id="bt_ir_para_resultado" class="btn troca-aba btn-info float-right">Avançar para o Resultado >></button>
            </div>
            <div class="col-sm-12 mt-1" id="button-bottom">
                <button type="button" id="salvar_lancamento_projeto_produto" class="btn btn-success float-right">Enviar projeto</button>
            </div>
        </div>
    </div>

    @if(!empty($dados['revisor']))
    <div class="tab-pane" id="lancamento_projeto_revisao_em_massa_produto" role="tabpanel" aria-labelledby="dados-tab">
        <form action="post" name="form_revisao_em_massa_produto" id="form_revisao_em_massa_produto" onsubmit="return false;">
            @csrf
            {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
            {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
            <div class="content-filter-dialog">
                <div class="form-row condicao_media_itens_pedido">
                    <div class="col-sm-2">
                        <strong><span id="num_projeto" class='ml-2'>Nr. Projeto: {{ $dados['numero_projeto'] }}</span></strong>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-6">
                        {{ Form::label('ncm', 'NCM', []) }}
                        {{ Form::text('ncm', '', ['id' => 'ncm', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'NCM', 'maxlength' => '8']) }}
                    </div>
                    <div class="form-group col-sm-6">
                        {{ Form::label('peso', 'Peso Líquido', []) }}
                        {{ Form::text('peso', '', ['id' => 'peso', 'class' => 'form-control text-right pedido-item-form moeda', 'placeholder' => 'Peso Líquido', 'maxlength' => '8']) }}
                    </div>
                    <div class="content-buttons">
                        <button name="btn-create" id="btn-create-alteracao_em_massa_produto" class="btn-create">Alteração Em Massa</button>
                    </div>
                </div>
            </div>
            <div class="content-dialog-table">
                <div class="content-table">
                    <table class="table table-striped" id="table-revisao_em_massa_produtos">
                        <thead>
                            <th class="tb_number">Item</th>
                            <th>Descrição</th>
                            <th>NCM</th>
                            <th>Peso</th>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                        </tfoot>
                    </table>
                </div>
                <div class="col-sm-12 mt-5" id="button-bottom">
                    {{ Form::button('APLICAR ALTERAÇÃO', array('class' => 'btn btn-primary float-right', 'id' => 'btn-aplicar_alteracao_produto')) }}
                </div> 
            </div>
        </form>
    </div>

    <div class="tab-pane" id="lancamento_projeto_revisao_em_massa_tecido" role="tabpanel" aria-labelledby="dados-tab">
        <form action="post" name="form_revisao_em_massa_tecido" id="form_revisao_em_massa_tecido" onsubmit="return false;">
            @csrf
            {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
            {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
            <div class="content-filter-dialog">
                <div class="form-row condicao_media_itens_pedido">
                    <div class="col-sm-2">
                        <strong><span id="num_projeto" class='ml-2'>Nr. Projeto: {{ $dados['numero_projeto'] }}</span></strong>
                    </div>
                </div>
            </div>
            <div class="content-dialog-table">
                <div class="content-table">
                    <div id="conteudo_revisao_em_massa_tecido"></div>
                </div>
                <div class="col-sm-12 mt-5" id="button-bottom">
                    {{ Form::button('APLICAR ALTERAÇÃO', array('class' => 'btn btn-primary float-right', 'id' => 'btn-aplicar_alteracao_tecido')) }}
                </div> 
            </div>
        </form>
    </div>

    <div class="tab-pane" id="lancamento_projeto_revisao_em_massa_insumo" role="tabpanel" aria-labelledby="dados-tab">
        <form action="post" name="form_revisao_em_massa_insumo" class="cadProjeto" id="form_revisao_em_massa_insumo" onsubmit="return false;">
            @csrf
            {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
            {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
            <div class="content-filter-dialog">
                <div class="form-row condicao_media_itens_pedido">
                    <div class="col-sm-2">
                        <strong><span id="num_projeto" class='ml-2'>Nr. Projeto: {{ $dados['numero_projeto'] }}</span></strong>
                    </div>
                </div>
            </div>
            <div class="content-dialog-table">
                <div class="content-table">
                    <div id="conteudo_revisao_em_massa_insumo"></div>
                </div>
                <div class="col-sm-12 mt-5" id="button-bottom">
                    {{ Form::button('APLICAR ALTERAÇÃO', array('class' => 'btn btn-primary float-right', 'id' => 'btn-aplicar_alteracao_insumo')) }}
                </div> 
            </div>
        </form>
    </div>

    <div class="tab-pane" id="lancamento_projeto_revisao_em_massa_servico" role="tabpanel" aria-labelledby="dados-tab">
        <form action="post" name="form_revisao_em_massa_servico" id="form_revisao_em_massa_servico" onsubmit="return false;">
            @csrf
            {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
            {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
            <div class="content-filter-dialog">
                <div class="form-row condicao_media_itens_pedido">
                    <div class="col-sm-2">
                        <strong><span id="num_projeto" class='ml-2'>Nr. Projeto: {{ $dados['numero_projeto'] }}</span></strong>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-sm-9">
                        <div class="input-group">
                            <input type="text" class="form-control input-label" name="faccao" id="faccao" value="" placeholder="Facção" maxlength="250" onkeyup="optionsFaccao($(this))">
                            <span class="input-group-addon border rounded-right" id="bt-search-faccao-busca" data-route="{{ route("faccao.modal.buscar") }}" data-nome_campo="faccao" onclick="buscarFaccao($(this))"><i id="bt-view-faccao" class="bt-view m-2"></i></span>
                        </div>
                    </div>
                    <div class="col-lg-1">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="tipo" id="tipo_todos" value="todos" checked/>
                            <label class="form-check-label" for="tipo_todos">Todos</label>
                        </div>
                    </div>
                    <div class="col-lg-1">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="tipo" id="tipo_produto" value="produto" />
                            <label class="form-check-label" for="tipo_produto">Produto</label>
                        </div>
                    </div>
                    <div class="col-lg-1">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="tipo" id="tipo_tecido" value="tecido" />
                            <label class="form-check-label" for="tipo_tecido">Tecido</label>
                        </div>
                    </div>
                </div>
                <div class="content-buttons">
                    <button name="btn-create" id="btn-create-alteracao_em_massa_servico" class="btn-create">Alteração Em Massa</button>
                </div>
            </div>

            <div class="content-dialog-table">
                <div class="content-table">
                    <div id="conteudo_revisao_em_massa_servico"></div>
                </div>
                <div class="col-sm-12 mt-5" id="button-bottom">
                    {{ Form::button('APLICAR ALTERAÇÃO', array('class' => 'btn btn-primary float-right', 'id' => 'btn-aplicar_alteracao_servico')) }}
                </div> 
            </div>
        </form>
    </div>
    @endif

    <div class="tab-pane" id="lancamento_projeto_resultado" role="tabpanel" aria-labelledby="dados-tab">
        <form action="post" name="form_projeto_resultado" class="cadProjeto" id="form_projeto_resultado" onsubmit="return false;">
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
                    <th colspan="2" class="text-center">PREÇOS</th>
                </tr>
                <tr>
                    <td width="30%">Valor Total do Pedido</td>
                    <td width="20%" class="text-right border-right" id="resultado_total_pedido">0</td>
                    <td width="30%">Preço do Tecido</td>
                    <td width="20%" class="text-right" id="resultado_custo_tecido">0</td>
                </tr>
                <tr>
                    <td>Quantidade Total</td>
                    <td class="text-right border-right" id="resultado_quantidade_total">0</td>
                    <td>Preço do Insumo</td>
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
                    <td>Preço Unitário MN</td>
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
                    <td class="border-bottom">Valor Total do Projeto</td>
                    <td class="text-right border-bottom" id="resultado_custo_total"></td>
                </tr>       
            </table>

            <div class="col-sm-12 mt-1" id="button-bottom">
                    <button type="button" id="bt_ir_para_produtos" class="btn troca-aba btn-info"><< Voltar para o Produto</button>
                    <button type="button" id="salvar_lancamento_projeto_resultado" class="btn btn-success float-right">Enviar projeto</button>
            </div>
        </form>
    </div>
    
</div>

<script>
    array_produtos = [];
    array_tecidos = [];
    array_insumos = [];
    array_servicos = [];
    $(document).ready( function () {
        esconderPopoverTooltip();
        init();
        liberarTabs();

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

        @if (!empty($dados['mensagem']))
		message('Alerta', '{{ $dados['mensagem'] }}');
        @endif

        @if($intercompany == true)
            $(document).find('#condicao_pagamento_descr').val("");
            $(document).find('#condicao_pagamento_group').hide();
            $(document).find('#id_produto_preco_venda').val("");
            $(document).find('#id_produto_preco_venda').hide();
        @endif
    }
    
    function initAutoCompletes(){
        form_projeto_add = $(document).find("#cadprojeto");
        form_projeto_add.find("#condicao_pagamento_descr").autocomplete(optionsAutoCompleteCondicoes());
        form_projeto_add.find("#nome_cliente").autocomplete(optionsAutoCompleteCliente());

        form_produto_add = $(document).find('#form_filter_projeto_produto');
        form_produto_add.find("#produto_descricao").autocomplete(optionsAutoCompleteProduto(form_produto_add));
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
        form_projeto_add.find("#num_pedido").off("change");
        form_projeto_add.find("#num_pedido").on("change", function(event){
            saveOnChange();
        });
        form_projeto_add.find("#tipo_produto_producao").off("change");
        form_projeto_add.find("#tipo_produto_producao").on("change", function(event){
            saveOnChange();
        });
        form_projeto_add.find("#nome_contato").off("change");
        form_projeto_add.find("#nome_contato").on("change", function(event){
            saveOnChange();
        });
        form_projeto_add.find("#email_contato").off("change");
        form_projeto_add.find("#email_contato").on("change", function(event){
            saveOnChange();
        });
        form_projeto_add.find("#salvar_lancamento_projeto").off("click");
        form_projeto_add.find("#salvar_lancamento_projeto").on("click", function(){
            salvarProjeto();
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
                form_produto_add.find("#produto_ncm").attr("readonly","readonly");
                form_produto_add.find("#produto_peso").attr("readonly","readonly");
            }else{
                form_produto_add.find("#produto_ncm").removeAttr('readonly');
                form_produto_add.find("#produto_peso").removeAttr('readonly');
            }
        });
        form_produto_add.find("#produto_codigo").off('change');
        form_produto_add.find("#produto_codigo").on('change', function(){
            if(form_produto_add.find("#produto_codigo").val() != ''){
                form_produto_add.find("#produto_ncm").attr("readonly","readonly");
                form_produto_add.find("#produto_peso").attr("readonly","readonly");
            }else{
                form_produto_add.find("#produto_ncm").removeAttr('readonly');
                form_produto_add.find("#produto_peso").removeAttr('readonly');
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
        $(document).find("#salvar_lancamento_projeto_produto").off("click");
        $(document).find("#salvar_lancamento_projeto_produto").on("click", function(){
            salvarProjeto();
        });

        form_tecido_add = $(document).find('#form_filter_projeto_tecido');

        $(document).find("#salvar_lancamento_projeto_tecido").off("click");
        $(document).find("#salvar_lancamento_projeto_tecido").on("click", function(){
            salvarProjeto();
        });
        
        form_insumo_add = $(document).find('#form_filter_projeto_insumo');

        $(document).find("#salvar_lancamento_projeto_insumo").off("click");
        $(document).find("#salvar_lancamento_projeto_insumo").on("click", function(){
            salvarProjeto();
        });

        form_servico_add = $(document).find('#form_filter_projeto_servico');

        
        $(document).find("#salvar_lancamento_projeto_servico").off("click");
        $(document).find("#salvar_lancamento_projeto_servico").on("click", function(){
            salvarProjeto();
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
        form_resultado_add.find("#salvar_lancamento_projeto_resultado").off("click");
        form_resultado_add.find("#salvar_lancamento_projeto_resultado").on("click", function(){
            salvarProjeto();
        });

        //Troca de Aba
        $(document).find(".troca-aba").off("click");
        $(document).find(".troca-aba").on("click", function(e){
            e.preventDefault();
            if($(this).attr("id") === "bt_ir_para_produtos"){
                $(document).find("#lancamento-projeto-produto-tab").tab("show");
                table_produto.draw(false);
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

        $(document).find("#lancamento-projeto-revisao_em_massa-produto-tab").off("click");
        $(document).find("#lancamento-projeto-revisao_em_massa-produto-tab").on("click", function(){
            carregarRevisaoMassaProdutos(form_produto_add.find("#id_projeto").val());
        });

        $(document).find("#lancamento-projeto-revisao_em_massa-tecido-tab").off("click");
        $(document).find("#lancamento-projeto-revisao_em_massa-tecido-tab").on("click", function(){
            carregarRevisaoMassaTecidos(form_produto_add.find("#id_projeto").val());
        });

        $(document).find("#lancamento-projeto-revisao_em_massa-insumo-tab").off("click");
        $(document).find("#lancamento-projeto-revisao_em_massa-insumo-tab").on("click", function(){
            carregarRevisaoMassaInsumos(form_produto_add.find("#id_projeto").val());
        });

        $(document).find("#lancamento-projeto-revisao_em_massa-servico-tab").off("click");
        $(document).find("#lancamento-projeto-revisao_em_massa-servico-tab").on("click", function(){
            carregarRevisaoMassaServicos(form_produto_add.find("#id_projeto").val());
        });

        form_revisao_em_massa_produto = $(document).find("#form_revisao_em_massa_produto");

        form_revisao_em_massa_produto.find("#btn-create-alteracao_em_massa_produto").off("click");
        form_revisao_em_massa_produto.find("#btn-create-alteracao_em_massa_produto").on("click", function(){
            alteracaoEmMassaProduto(form_revisao_em_massa_produto);
        });

        form_revisao_em_massa_produto.find("#btn-aplicar_alteracao_produto").off("click");
        form_revisao_em_massa_produto.find("#btn-aplicar_alteracao_produto").on("click", function(){
            aplicarAlteracaoProduto();
        });

        form_revisao_em_massa_tecido = $(document).find("#form_revisao_em_massa_tecido");

        form_revisao_em_massa_tecido.find("#btn-aplicar_alteracao_tecido").off("click");
        form_revisao_em_massa_tecido.find("#btn-aplicar_alteracao_tecido").on("click", function(){
            aplicarAlteracaoTecido();
        });

        form_revisao_em_massa_insumo = $(document).find("#form_revisao_em_massa_insumo");

        form_revisao_em_massa_insumo.find("#btn-aplicar_alteracao_insumo").off("click");
        form_revisao_em_massa_insumo.find("#btn-aplicar_alteracao_insumo").on("click", function(){
            aplicarAlteracaoInsumo();
        });

        form_revisao_em_massa_servico = $(document).find("#form_revisao_em_massa_servico");

        form_revisao_em_massa_servico.find("#btn-aplicar_alteracao_servico").off("click");
        form_revisao_em_massa_servico.find("#btn-aplicar_alteracao_servico").on("click", function(){
            aplicarAlteracaoServico();
        });

        form_revisao_em_massa_servico.find("#btn-create-alteracao_em_massa_servico").off("click");
        form_revisao_em_massa_servico.find("#btn-create-alteracao_em_massa_servico").on("click", function(){
            alteracaoEmMassaServico(form_revisao_em_massa_servico);
        });

        form_revisao_em_massa_servico.find("#tipo_todos").off("click");
        form_revisao_em_massa_servico.find("#tipo_todos").on("click", function(){
            carregarRevisaoMassaServicos(form_produto_add.find("#id_projeto").val());
        });

        form_revisao_em_massa_servico.find("#tipo_produto").off("click");
        form_revisao_em_massa_servico.find("#tipo_produto").on("click", function(){
            carregarRevisaoMassaServicos(form_produto_add.find("#id_projeto").val());
        });

        form_revisao_em_massa_servico.find("#tipo_tecido").off("click");
        form_revisao_em_massa_servico.find("#tipo_tecido").on("click", function(){
            carregarRevisaoMassaServicos(form_produto_add.find("#id_projeto").val());
        });
    }

    function initMaskCampos(){
        form_produto_add = $(document).find("#form_filter_projeto_produto");
        form_produto_add.find("#produto_preco_venda").maskMoney({thousands:'', decimal:','}); 
        form_produto_add.find("#produto_quantidade").maskMoney({thousands:'', decimal:','});
        @if(!empty($dados['revisor'])) 
        form_produto_add.find("#produto_peso").maskMoney({thousands:'', decimal:','});
        @endif

        $(document).find(".moeda").maskMoney({thousands:'', decimal:','});
    }

    //AutoComplete

    function optionsAutoCompleteCondicoes(){
        esconderPopoverTooltip();
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
                $(document).find('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_grupo_edit_delete').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#condicao_pagamento").val(ui.item.value);
                $(document).find("#condicao_pagamento_descr").val(ui.item.label);
                saveOnChange();
                return false;
            }
        };
    }

    function optionsAutoCompleteCliente(){
        esconderPopoverTooltip();
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
                $(document).find('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_grupo_edit_delete').css('z-index')) + 1));
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
                form_projeto_produto_add = $(document).find('#form_filter_projeto_produto');
                form_projeto_produto_add.find("#cnpj_cliente").val(ui.item.cpf_cnpj);
                form_projeto_add = $(document).find('#cadprojeto');
                form_projeto_add.find("#codigo_cliente").val(ui.item.cpf_cnpj);
                form_projeto_add.find("#nome_cliente").val(ui.item.label);
                saveOnChange();
                return false;
            }
        };
    }

    function optionsAutoCompleteProduto(form_produto_add){
        esconderPopoverTooltip();
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
                $(document).find('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_grupo_edit_delete').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    form_produto_add.find("#produto_codigo").val('');
                    form_produto_add.find("#produto_ficha").val("");
                    form_produto_add.find("#id_codigo").val("");
                    if(form_produto_add.find("#produto_codigo").val() != ''){
                        form_produto_add.find("#produto_ncm").attr("readonly","readonly");
                        form_produto_add.find("#produto_peso").attr("readonly","readonly");
                    }else{
                        form_produto_add.find("#produto_ncm").removeAttr('readonly');
                        form_produto_add.find("#produto_peso").removeAttr('readonly');
                    }
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
                if(form_produto_add.find("#produto_codigo").val() != ''){
                    form_produto_add.find("#produto_ncm").attr("readonly","readonly");
                    form_produto_add.find("#produto_peso").attr("readonly","readonly");
                }else{
                    form_produto_add.find("#produto_ncm").removeAttr('readonly');
                    form_produto_add.find("#produto_peso").removeAttr('readonly');
                }
                return false;
            }
        };
    }

    function pesquisaProdutoDescricao(){
        esconderPopoverTooltip();
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

        if(form_produto_add.find("#produto_codigo").val() != ''){
            form_produto_add.find("#produto_ncm").attr("readonly","readonly");
            form_produto_add.find("#produto_peso").attr("readonly","readonly");
        }else{
            form_produto_add.find("#produto_ncm").removeAttr('readonly');
            form_produto_add.find("#produto_peso").removeAttr('readonly');
        }
    }


    //Modal
    function showModalCliente(url){
        esconderPopoverTooltip();
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
        form_projeto_produto_add = $(document).find('#form_filter_projeto_produto');
        form_projeto_produto_add.find("#cpnj_cliente_produto").val($dados.find("td").eq(3).text());
        form_projeto_add = $(document).find('#cadprojeto');
        form_projeto_add.find("#codigo_cliente").val($dados.find("td").eq(3).text());
        form_projeto_add.find("#nome_cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());

        var documento = $dados.find("td").eq(3).text();

        saveOnChange();

        $(document).find("#cliente_searsh_show").modal("hide");
	}
    function modalCondicao($this){
        esconderPopoverTooltip();
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
        saveOnChange();
        $(document).find("#modal_busca_condicao").modal("hide");
    }

    function showModalProdutoAdd(){
        esconderPopoverTooltip();
        $.ajax({
            url: '{{ route('produto.modal_pesquisa') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto ficha tecnica", data, 'modal-lg');
                var modal = $(document).find("#modal_search_produto");
                $(document).ready( function () {
                    table_filters_produtos_busca.on('draw', function () {
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
        if(form_produto_add.find("#produto_codigo").val() != ''){
            form_produto_add.find("#produto_ncm").attr("readonly","readonly");
            form_produto_add.find("#produto_peso").attr("readonly","readonly");
        }else{
            form_produto_add.find("#produto_ncm").removeAttr('readonly');
            form_produto_add.find("#produto_peso").removeAttr('readonly');
        }
    };

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
                { targets: 0, width: '10px'},
                { targets: 1, width: '90px'},
                { targets: 3, width: '100px'},
                { targets: 4, width: '60px'},
                { targets: [5, 6, 7, 8, 9], width: '100px'},
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
                { "class": "td_acao", targets: "td_acao", "orderable": false}
            ]
        };
        table_produto = '';
        table_produto = $(document).find('#table-filters-produtos').DataTable(table_filters_produto_options);
        table_produto.draw();

        table_revisao_em_massa_produto_options = {
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { targets: 0, width: '10px'},
                { targets: 2, width: '100px'},
                { targets: 3, width: '100px'},
            ]
        };

        table_revisao_em_massa_produtos = '';
        table_revisao_em_massa_produtos = $(document).find("#table-revisao_em_massa_produtos").DataTable(table_revisao_em_massa_produto_options);
        table_revisao_em_massa_produtos.draw();

        table_revisao_em_massa_tecido_options = {
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
                "emptyTable":     "Nenhum Tecido Inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Tecido Inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { targets: 0, width: '10px'},
                { targets: 1, width: '100px'},
                { targets: 3, width: '100px'},
            ]
        };

        table_revisao_em_massa_tecidos = '';
        table_revisao_em_massa_tecidos = $(document).find("#table-revisao_em_massa_tecidos").DataTable(table_revisao_em_massa_tecido_options);
        table_revisao_em_massa_tecidos.draw();
    }

    // Outros
    function retornarDescricaoProduto(form_modal_add){
        esconderPopoverTooltip();
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
                    @if(!empty($dados['revisor']))
                    form_modal_add.find("#produto_ncm").val("");
                    form_modal_add.find("#produto_peso").val("");
                    @endif
                    limparMesagemErroAdd(form_modal_add);
                    mensagemErroAdd(dados, form_modal_add);
                }
                form_modal_add.find("#produto_descricao").val('');
                form_modal_add.find("#produto_quantidade").val('');
                form_modal_add.find("#produto_ficha").val("");
                form_modal_add.find("#id_codigo").val("");
                @if(!empty($dados['revisor']))
                form_modal_add.find("#produto_ncm").val("");
                form_modal_add.find("#produto_peso").val("");
                @endif
            }
        });
    }
    function dadosRetornoAdd(produto, form_modal_add){
        if(form_modal_add.find('#produto_codigo').val() === ''){
            form_modal_add.find('#produto_descricao').focus();
            form_modal_add.find("#produto_ficha").val("");
            form_modal_add.find("#id_codigo").val("");
            @if(!empty($dados['revisor']))
            form_modal_add.find("#produto_ncm").val("");
            form_modal_add.find("#produto_peso").val("");
            @endif
        }else{
            form_modal_add.find('#produto_descricao').val(produto.nome);
            form_modal_add.find('#produto_descricao_hidden').val(produto.nome);
            form_modal_add.find("#produto_ficha").val(produto.codigo);
            form_modal_add.find("#id_codigo").val("ID");
            @if(!empty($dados['revisor']))
            form_modal_add.find("#produto_ncm").val(produto.ncm);
            form_modal_add.find("#produto_peso").val(produto.peso);
            @endif
        }
    }

    //Adicionar, Editar e Excluir

    function saveOnChange(){
        esconderPopoverTooltip();
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

                if(data.response.intercompany == true){
                    $(document).find('#condicao_pagamento_descr').val("");
                    $(document).find('#condicao_pagamento_group').hide();
                    $(document).find('#id_produto_preco_venda').val("");
                    $(document).find('#id_produto_preco_venda').hide();
                }else{
                    $(document).find('#condicao_pagamento_group').show();
                    $(document).find('#id_produto_preco_venda').show();
                }

                $(document).find('#descricao_vendedor').val(data.response.descricao_vendedor);

                if(data.response.info != ''){
                    form_tecido_add = $(document).find('#form_filter_projeto_tecido');
                    form_insumo_add = $(document).find('#form_filter_projeto_insumo');
                    form_servico_add = $(document).find('#form_filter_projeto_servico');
                    form_resultado_add = $(document).find('#form_projeto_resultado');

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

                    form_resultado_add.find("#estabelecimento_exibicao").html(data.response.info.estabelecimento);
                    form_resultado_add.find("#estado_destino").html(data.response.info.estado_destino);
                    form_resultado_add.find("#media_condicao_pagamento").html(data.response.info.media_condicao_pagamento);
                    form_resultado_add.find("#preco_cif_fob").html(data.response.info.preco_cif_fob);
                    form_resultado_add.find("#cif_fob").html(data.response.info.cif_fob);
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
        esconderPopoverTooltip();
        if ($(document).find("#id_produto").val() != ''){
            salvarEdicaoProdutoNoProjeto();
        }else{
            
            form_projeto_add = $(document).find('#cadprojeto');
            form_modal_add = $(document).find("#form_filter_projeto_produto");
            revisor_id = form_modal_add.find("#id_revisor").val();
            form_modal_add.nome_cliente = form_projeto_add.find("#nome_cliente").val();
            data_form = form_modal_add.serialize();

            limparMesagemErroAdd(form_modal_add);
            $.ajax({
                url: '{{ Route("lancamento_projeto.produto.adicionar") }}',
                type: 'POST',
                data: data_form,
                success: function(data) {
                    carregarTabelaProdutos(data.response.produto.projeto_id);
                    chamadaPopover();
                    limparCamposProduto();
                    liberarTabs();
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
                carregarTabelaProdutos(data.response.produto.projeto_id);
                revisor_id = form_modal_add.find("#id_revisor").val();
                chamadaPopover();
                form_modal_add.find("#id_produto").val('');
                form_modal_add.find("#btn-create-produto-projeto").html('Inserir produto');
                form_modal_add.find("#btn-cancel-produto-projeto").hide();
                limparCamposProduto();
            
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
        esconderPopoverTooltip();
        form_modal_add = $(document).find("#form_filter_projeto_produto");

        data_form = form_modal_add.serialize();
        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.produto.cancelar_edicao") }}',
            type: 'POST',
            data: data_form,
            success: function(data) {
                revisor_id = form_modal_add.find("#id_revisor").val();
                var field = [
                    data.response.produto.indice,
                    data.response.produto.codigo,
                    getDetalheProduto(data.response.produto.descricao, data.response.produto.detalhes),
                    @if(!empty($dados['revisor']))
                    data.response.produto.ncm,
                    data.response.produto.peso,
                    @endif
                    @if($intercompany == false)
                        precoInformativo(data.response.produto),
                    @else
                        '',
                    @endif
                    data.response.produto.custo_unitario,
                    data.response.produto.quantidade,
                    produtoComposicaoTabela(data.response.produto.total_tecido, data.response.produto, revisor_id, "tecido"),
                    produtoComposicaoTabela(data.response.produto.total_insumo, data.response.produto, revisor_id, "insumo"),
                    produtoComposicaoTabela(data.response.produto.total_servico, data.response.produto, revisor_id, "servico"),
                    data.response.produto.total_custo,
                    createBtDuplicar(data.response.produto),
                    produtoComposicaoTabela("", data.response.produto, revisor_id, "arquivo"),
                    createBtEditarProduto(data.response.produto),
                    createBtExcluirProduto(data.response.produto),
                    produtoComFaccao(data.response.produto.produto_com_faccao)
                ];
                if(data.response.produto.desconto_acima_permitido === true){
                    table_produto.row.add(field).draw().nodes().to$().addClass('error-tr');
                }else{
                    table_produto.row.add(field).draw();
                }
                chamadaPopover();
                form_modal_add.find("#id_produto").val('');
                $(document).find('.dataTables_scrollFootInner').find('#produto_total_exibicao').html(data.response.total.total_quantidade);
                $(document).find('.dataTables_scrollFootInner').find('#produto_total_tecido').html(data.response.total.total_custo_tecido);
                $(document).find('.dataTables_scrollFootInner').find('#produto_total_insumo').html(data.response.total.total_custo_insumo);
                $(document).find('.dataTables_scrollFootInner').find('#produto_total_servico').html(data.response.total.total_custo_servico);
                $(document).find('.dataTables_scrollFootInner').find('#produto_total_custo_unitario').html(data.response.total.total_custo_unitario);
                $(document).find('.dataTables_scrollFootInner').find('#produto_total_custo').html(data.response.total.total_custo_total);

                form_modal_add.find("#btn-create-produto-projeto").html('Inserir produto');
                form_modal_add.find("#btn-cancel-produto-projeto").hide();
                limparCamposProduto();
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
        var html = "<a href=\"#\" class=\"bt-edit\" title='Editar' onclick=\"editarProdutoNoProjeto($(this).parents('tr'), '"+$this.id+"','"+$this.projeto_id+"')\"></a>";
        return html;
    }

    function createBtExcluirProduto($this){
        var html = "<a href=\"#\" class=\"bt-delete\" title='Excluir' onclick=\"excluirProdutoNoProjeto($(this).parents('tr'), '"+$this.id+"','"+$this.projeto_id+"')\"></a>";
        return html;
    }

    function editarProdutoNoProjeto(obj, $id_produto, $id_projeto){
        esconderPopoverTooltip();
        form_modal_add = $(document).find("#form_filter_projeto_produto");

        id_produto = form_modal_add.find("#id_produto").val();

        limparMesagemErroAdd(form_modal_add);
        if(id_produto == ''){
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

                    @if(!empty($dados['revisor']))
                    form_modal_add.find("#produto_ncm").val(data.response.ncm);
                    form_modal_add.find("#produto_peso").val(data.response.peso);
                    @endif

                    form_modal_add.find("#btn-create-produto-projeto").html('Editar produto');
                    form_modal_add.find("#btn-cancel-produto-projeto").show();

                    $(document).find('.dataTables_scrollFootInner').find('#produto_total_exibicao').html(data.response.total_exibicao);
                    $(document).find('.dataTables_scrollFootInner').find('#produto_total_tecido').html(data.response.total_custo_tecido);
                    $(document).find('.dataTables_scrollFootInner').find('#produto_total_insumo').html(data.response.total_custo_insumo);
                    $(document).find('.dataTables_scrollFootInner').find('#produto_total_servico').html(data.response.total_custo_servico);
                    if(data.response.total_exibicao == ''){
                        liberarTabs();
                    }else{
                        liberarTabs();
                    }

                    if(form_produto_add.find("#produto_codigo").val() != ''){
                        form_produto_add.find("#produto_ncm").attr("readonly","readonly");
                        form_produto_add.find("#produto_peso").attr("readonly","readonly");
                    }else{
                        form_produto_add.find("#produto_ncm").removeAttr('readonly');
                        form_produto_add.find("#produto_peso").removeAttr('readonly');
                    }
                }
            });
        }else{
            message("Atenção", "Há um produto sendo editado.");
        }
    }

    function excluirProdutoNoProjeto(obj, $id_produto, $id_projeto){
        esconderPopoverTooltip();
        form_modal_add = $(document).find("#form_filter_projeto_produto");
        var list_produtos = form_modal_add.find("#list_produtos").val();
        limparMesagemErroAdd(form_modal_add);
        $.ajax({
            url: '{{ Route("lancamento_projeto.produto.deletar") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token()}}',
                id_produto: $id_produto,
                id_projeto: $id_projeto
            },
            success: function(data){
                table_produto.row(obj).remove().draw();
                $(document).find('.dataTables_scrollFootInner').find('#produto_total_exibicao').html(data.response.total_exibicao);
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

    //Calculo

    function resultado(){
        esconderPopoverTooltip();
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
                form_resultado_add.find('#resultado_comissao').html(tooltipComissao(data.response.resultado));
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

    function tooltipComissao($value){
        var comissao = "";

        if($value.bionexo === true){
            var comissao = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='Comissão diferenciada por produção Bionexo.'>"+$value.comissao+"*</div></div>";;
        }else{
            var comissao = $value.comissao;
        }


        return comissao;
    }

    function construcaoFreteAdicional($this){
        html = "";

        if($this.frete_porcetagem == "0,00"){
            html = "Frete Adicional Facção";
        }else{
            html = '<div>Frete Adicional Facção<div class="btn-informacao-sem-alinhamento" data-toggle="popover" data-placement="right" data-content="<p>Frete Adicional é de '+$this.frete_porcetagem+' %"></div></div>';
        }
            
        return html;
    }

    //Limpeza

    function limparMesagemErroAdd(form_modal_add){ 
        esconderPopoverTooltip();  
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error, form_modal_add){
        esconderPopoverTooltip();
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
        }else if(input.localeCompare('faccao') == 0){
            var $input = $(form_modal_add).find("#bt-search-faccao-busca");
            $(form_modal_add).find("input[name='faccao']").addClass('error-input');
        }else if(input.localeCompare('servico_codigo') == 0){
            var $input = $(form_modal_add).find("#bt-search-servico");
            $(form_modal_add).find("input[name='servico_codigo']").addClass('error-input');
        }else{
            var $input = $(form_modal_add).find("input[name='"+input+"'], select[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function limparCamposProduto(){
        esconderPopoverTooltip();
        form_modal_add = $(document).find("#form_filter_projeto_produto");

        form_modal_add.find("#id_produto").val("");
        form_modal_add.find("#produto_codigo").val("");
        form_modal_add.find("#produto_descricao").val("");
        form_modal_add.find("#produto_preco_venda").val("");
        form_modal_add.find("#produto_quantidade").val("");
        form_modal_add.find("#produto_detalhes").val("");
        form_modal_add.find("#produto_ficha").val("");
        form_modal_add.find("#id_codigo").val("");

        @if(!empty($dados['revisor']))
        form_modal_add.find("#produto_ncm").val("");
        form_modal_add.find("#produto_peso").val("");
        @endif

        form_modal_add.find("#produto_codigo").focus();
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

    function salvarProjeto(){
        form_modal_add = $(document).find("#cadprojeto");
    
        data_form = form_modal_add.serialize();

        if(form_modal_add.find("#id_revisor").val() == ''){
            $.ajax({
                url: "{{ route('lancamento_projeto.adicionar') }}", 
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
                    var dados = callback.responseJSON;
                    limparMesagemErroAdd(form_projeto_add);
                    mensagemErroAdd(dados, form_projeto_add);
                    if(!$.isEmptyObject(dados.error)){
                        $(document).find("#lancamento-projeto-header-tab").tab("show");
                    }
                    if(callback.responseJSON.message !== "Campos inválidos"){
                        message("Atenção", callback.responseJSON.message);
                        var verificador = callback.responseJSON.message.split(" ");
                        if(verificador[0] === "Há" || verificador[0] === "Não"){
                            $(document).find("#lancamento-projeto-produto-tab").tab("show");
                        }else{
                            $(document).find("#lancamento-projeto-resultado-tab").tab("show");
                        }
                    }
                }
            });
        }else{
            $.ajax({
                url: "{{ route('lancamento_projeto.revisao_ok') }}", 
                dataType: 'json',
                data: data_form,
                method: 'POST',
                async: false,
                success: function(callback){
                    message("Atenção", "Projeto revisado com Sucesso");
                    $(form_modal_add).parents('.modal').modal('hide');
                    filterAjax($("#form_filter").serialize());
                },
                error: function(callback){
    
                    message("Atenção", callback.responseJSON.message);
                    var dados = callback.responseJSON;
                }
            });
        }
    }

    function disabledTabs(){
        $(document).find("#lancamento-projeto-produto-tab").addClass('disabled');
		$(document).find("#lancamento-projeto-tecido-tab").addClass('disabled');
        $(document).find("#lancamento-projeto-insumo-tab").addClass('disabled');
        $(document).find("#lancamento-projeto-servico-tab").addClass('disabled');
        $(document).find("#lancamento-projeto-resultado-tab").addClass('disabled');
	}
	function removeDisabledTabs(){
        $(document).find("#lancamento-projeto-produto-tab").removeClass('disabled');
		$(document).find("#lancamento-projeto-tecido-tab").removeClass('disabled');
        $(document).find("#lancamento-projeto-insumo-tab").removeClass('disabled');
        $(document).find("#lancamento-projeto-servico-tab").removeClass('disabled');
        $(document).find("#lancamento-projeto-resultado-tab").removeClass('disabled');
    }

    function getDetalheProduto($produto, $detalhes){
        if($detalhes != ''){
            html = "<div>"+
                "<div data-toggle=\"popover\" data-placement=\"top\" data-title=\"Detalhes\" data-content=\"<p><b>Produto:</b>"+$produto+"<p><b>Detalhes de Produção:</b> <p>"+$detalhes+"\">"+
                        "<a href=\"#\" class=\"btn-informacao\"></a>"+
                        $produto+
                "</div></div>";
        }else{
            html = "<div>"+
                    "<div data-toggle='tooltip' placement='right' data-html='true' title='' data-original-title='"+$produto+"'>"+
                        $produto+
                    "</div>"+
                "</div>";
        }
    
        return html;
    }

    function liberarTabs(){
        esconderPopoverTooltip();
        form_modal_add = $(document).find("#cadprojeto");
    
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
                        "<div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$this.produto+"'>"+
                            $this.produto+
                        "</div>"+
                    "</div>"; 
        }else{
            if($this.produto_acabado == ''){
                $return = "<div>"+
                    "<div data-toggle=\"popover\" data-placement=\"top\" data-title=\"Detalhes\" data-content=\"<p><b>Tecido:</b> "+$this.tecido+"<p><b>Ref. Produto:</b> "+$this.produto+"\"></a>"+
                        "<a href=\"#\" class=\"btn-informacao\"></a>"+
                        $this.tecido+
                    "</div>"+
                "</div>";
            }else{
                $return = "<div>"+
                    "<div data-toggle=\"popover\" data-placement=\"top\" data-title=\"Detalhes\" data-content=\"<p><b>Tecido:</b> "+$this.tecido+"<p><b>Ref. Produto:</b> "+$this.produto+" <p><b>Produto Acabado:</b> "+$this.produto_acabado+"\"></a>"+
                        "<a href=\"#\" class=\"btn-informacao\"></a>"+
                        $this.tecido+
                    "</div>"+
                "</div>";
            }
            
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

    function dialogComposicao($value){
        esconderPopoverTooltip();
        var url = $value.data("url");
        var $id_produto = $value.data("id_produto");
        var $id_projeto = $value.data("id_projeto");
        var $id_revisor = $value.data("id_revisor");
        var $posicao = $value.data("posicao");
        var title = "Composição";
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}", 
                id_produto: $id_produto,
                id_projeto: $id_projeto,
                id_revisor: $id_revisor,
                posicao: $posicao
            },
            success: function(body){
                createModal('modal_composicao', title, body, "modal-lg");
                var modal_composicao = $("#modal_composicao");
            }
        });
    }

    function produtoComposicaoTabela($total, $array, $id_revisor, $posicao){
        if($posicao !== "arquivo"){
            html = $total+"<a href=\"#\" class=\"bt-plus-direita\" title=\"Composição\" data-url=\"{{ route('lancamento_projeto.modal.composicao') }}\" data-title=\""+$array.descricao+"\" data-id_produto=\"'"+$array.id+"'\" data-id_projeto=\"'"+$array.projeto_id+"'\" data-id_revisor=\""+$id_revisor+"\" data-posicao=\""+$posicao+"\" onclick=\"dialogComposicao($(this))\"></a>";
        }else{
            html = $total+"<a href=\"#\" class=\"btn-pedido\" title=\"Composição\" data-url=\"{{ route('lancamento_projeto.modal.composicao') }}\" data-title=\""+$array.descricao+"\" data-id_produto=\"'"+$array.id+"'\" data-id_projeto=\"'"+$array.projeto_id+"'\" data-id_revisor=\""+$id_revisor+"\" data-posicao=\""+$posicao+"\" onclick=\"dialogComposicao($(this))\"></a>";
        }
        
        
        return html;
    }

    function produtoComFaccao($bool){
        if ($bool == true){
            return "<label style='display: none;'>a</label><i class='fa fa-check check-icon' aria-hidden='true'></i>";
        }
        else if ($bool == false){
            return "<label style='display: none;'>b</label><i class='fa fa-times error-icon'  aria-hidden='true'></i>";
        }
    }
    
    function showModalDuplicarProduto($this){
        esconderPopoverTooltip();
        var title = "Duplicar Composição";
        var id_produto = $this.data("id_produto");
        $.ajax({
            url: '{{ route('lancamento_projeto.modal.duplicar_produto') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id_produto: id_produto,
            },
            success: function (body){
                createModal('modal_duplicar_produto',  title, body, '');
            }
        }); 
    }

    function createBtDuplicar($value){
        var html = "<a href=\"#\" class=\"bt-duplicar\" data-toggle='tooltip' data-html='true' data-placement=\"right\" title='Duplicar' data-id_produto=\""+$value.id+"\" onclick=\"showModalDuplicarProduto($(this))\"></a>";

        return html;
    }

    function adicionarProdutoDuplicado(data){
        esconderPopoverTooltip();
        revisor_id = form_modal_add.find("#id_revisor").val();
        var field = [
            data.response.produto.indice,
            data.response.produto.codigo,
            getDetalheProduto(data.response.produto.descricao, data.response.produto.detalhes),
            @if(!empty($dados['revisor']))
            data.response.produto.ncm,
            data.response.produto.peso,
            @endif
            @if($intercompany == false)
                precoInformativo(data.response.produto),
            @else
                '',
            @endif
            data.response.produto.custo_unitario,
            data.response.produto.quantidade,
            produtoComposicaoTabela(data.response.produto.total_tecido, data.response.produto, revisor_id, "tecido"),
            produtoComposicaoTabela(data.response.produto.total_insumo, data.response.produto, revisor_id, "insumo"),
            produtoComposicaoTabela(data.response.produto.total_servico, data.response.produto, revisor_id, "servico"),
            data.response.produto.total_custo,
            createBtDuplicar(data.response.produto),
            produtoComposicaoTabela("", data.response.produto, revisor_id, "arquivo"),
            createBtEditarProduto(data.response.produto),
            createBtExcluirProduto(data.response.produto),
            produtoComFaccao(data.response.produto.produto_com_faccao)
        ];
        if(data.response.produto.desconto_acima_permitido === true){
            table_produto.row.add(field).draw().nodes().to$().addClass('error-tr');
        }else{
            table_produto.row.add(field).draw();
        }

        chamadaPopover();
        form_modal_add.find("#id_produto").val('');
        $(document).find('.dataTables_scrollFootInner').find('#produto_total_exibicao').html(data.response.total.total_quantidade);
        $(document).find('.dataTables_scrollFootInner').find('#produto_total_tecido').html(data.response.total.total_custo_tecido);
        $(document).find('.dataTables_scrollFootInner').find('#produto_total_insumo').html(data.response.total.total_custo_insumo);
        $(document).find('.dataTables_scrollFootInner').find('#produto_total_servico').html(data.response.total.total_custo_servico);
        $(document).find('.dataTables_scrollFootInner').find('#produto_total_custo_unitario').html(data.response.total.total_custo_unitario);
        $(document).find('.dataTables_scrollFootInner').find('#produto_total_custo').html(data.response.total.total_custo_total);
    }

    @if(!empty($dados['revisor']))
        function carregarRevisaoMassaProdutos(id_projeto){
            esconderPopoverTooltip();
            $.ajax({
                url: '{{ Route("lancamento_projeto.produto.carregar_produto") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_projeto: id_projeto
                },
                success: function(data) {
                    array_produtos = [];
                    table_revisao_em_massa_produtos.clear().draw();
                    produtos = [];
                    for (var index in data.response.produtos){
                        var field = [
                            data.response.produtos[index].indice,
                            ajusteTamanhoTable(data.response.produtos[index].descricao),
                            inputNCM(data.response.produtos[index]),
                            inputPeso(data.response.produtos[index]),
                        ];

                        array_produtos.push(data.response.produtos[index].produto);

                        produtos.push(field);
                    }
                    table_revisao_em_massa_produtos.rows.add(produtos).draw();
                    $(document).find(".moeda").maskMoney({thousands:'', decimal:','});
                }
            });
        }

        function inputNCM($value){
            html = "<input style=\"height: inherit;\" id=\"ncm_tabela-"+$value.produto+"\" name=\"ncm_tabela-"+$value.produto+"\" type=\"text\" class=\"form-control text-right\" value=\""+$value.ncm+"\"  maxlength=\"8\">";
            return html;
        }

        function inputPeso($value){
            html = "<input style=\"height: inherit;\" id=\"peso_tabela-"+$value.produto+"\" name=\"peso_tabela-"+$value.produto+"\" type=\"text\" class=\"form-control text-right moeda\" value=\""+$value.peso+"\"  maxlength=\"8\">";
            return html;
        }

        function alteracaoEmMassaProduto(form_revisao_em_massa_produto){
            esconderPopoverTooltip();
            var $class = "dialog_option_deletar";
            var $name_option_sim = "alteracao_em_massa_sim";
            var $option_sim = "";
            var $name_option_nao = "alteracao_em_massa_nao";
            var $option_nao = "";
            var $name_option_cancelar = "alteracao_em_massa_cancelar";
            var $option_cancelar = "";
            var sobrescrever = false;
            message_option_sim_nao("Deseja sobrescrever ncm e peso?", "Caso opção seja Não, será preenchido os campos sem valores.", $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao, $name_option_cancelar, $option_cancelar);

            $(document).off("alteracao_em_massa_sim");
            $(document).on("alteracao_em_massa_sim", function(){
                sobrescrever = true;
                alteracaoEmMassaProdutoExecutar(form_revisao_em_massa_produto, sobrescrever);
            });

            $(document).off("alteracao_em_massa_nao");
            $(document).on("alteracao_em_massa_nao", function(){
                sobrescrever = false;
                alteracaoEmMassaProdutoExecutar(form_revisao_em_massa_produto, sobrescrever);
            });
        }

        function alteracaoEmMassaProdutoExecutar(form_revisao_em_massa_produto, sobrescrever){
            var id_projeto = form_revisao_em_massa_produto.find("#id_projeto").val();
            var ncm = form_revisao_em_massa_produto.find("#ncm").val();
            var peso = form_revisao_em_massa_produto.find("#peso").val();
            var id_revisor = form_revisao_em_massa_produto.find("#id_revisor").val();

            $.ajax({
                url: '{{ Route("lancamento_projeto.produto.alteracao_em_massa") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_projeto: id_projeto,
                    ncm: ncm,
                    peso: peso,
                    sobrescrever: sobrescrever,
                    id_revisor: id_revisor
                },
                success: function(data) {
                    id_projeto = form_revisao_em_massa_produto.find("#id_projeto").val();
                    form_revisao_em_massa_produto.find("#ncm").val('');
                    form_revisao_em_massa_produto.find("#peso").val('');
                    carregarRevisaoMassaProdutos(id_projeto);
                    carregarTabelaProdutos(id_projeto);

                    message("Atenção", "Alterado com sucesso");
                }
            });
        }

        function aplicarAlteracaoProduto(){
            esconderPopoverTooltip();
            form_revisao_em_massa_produto = $(document).find("#form_revisao_em_massa_produto");

            id_projeto = form_revisao_em_massa_produto.find("#id_projeto").val();
            id_revisor = form_revisao_em_massa_produto.find("#id_revisor").val();

            var_itens = [];

            array_produtos.forEach(function imprimir(item){
                var dados = [];
                dados.push(item);
                dados.push(form_revisao_em_massa_produto.find("#ncm_tabela-"+item).val());
                dados.push(form_revisao_em_massa_produto.find("#peso_tabela-"+item).val());
                var_itens.push(dados);
            });

            $.ajax({
                url: "{{ route('lancamento_projeto.produto.aplicar_alteracao') }}", 
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_projeto: id_projeto,
                    var_itens: var_itens,
                    id_revisor: id_revisor
                },
                method: 'POST',
                async: false,
                success: function(callback){
                    carregarRevisaoMassaProdutos(id_projeto);
                    carregarTabelaProdutos(id_projeto);

                    message("Atenção", "Alterado com sucesso");
                },
                error: function(callback){

                }
            });
        }

        function carregarRevisaoMassaTecidos(id_projeto){
            esconderPopoverTooltip();
            $.ajax({
                url: '{{ Route("lancamento_projeto.tecido.carregar_tecido") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_projeto: id_projeto
                },
                success: function(data) {
                    var html = "";
                    array_tecidos = [];
                    for (var index in data.response){
                        html = html + "<hr><h5>"+data.response[index].indice+" - "+data.response[index].descricao+" - QTD: "+data.response[index].quantidade+"</h5><br>";
                        html = html + "<table class=\"table table-striped\">";
                        html = html + "<thead>"+
                            "<th>Item</th>"+
                            "<th>Código</th>"+
                            "<th>Descrição</th>"+
                            "<th>Consumo</th>"+
                            "<th>Consumo T.</th>"+
                            "</thead>";
                        html = html + "<tbody>";
                        if(data.response[index].tecidos.length == 0){
                            html = html + "<tr>";
                            html = html + "<td colspan=\"5\" style=\"text-align-last: center;\">NENHUM TECIDO INSERIDO</td>";
                            html = html + "</tr>";
                        }else{
                            for (var index_tecido in data.response[index].tecidos){
                                html = html + "<tr>";
                                html = html + "<td class=\"tb_number\" style=\"width: 10px;\">"+data.response[index].tecidos[index_tecido].indice+"</td>";
                                html = html + "<td style=\"width: 100px;\">"+data.response[index].tecidos[index_tecido].codigo+"</td>";
                                html = html + "<td>"+data.response[index].tecidos[index_tecido].descricao+"</td>";
                                html = html + "<td style=\"width: 150px;\">"+inputConsumoTecido(data.response[index].tecidos[index_tecido])+"</td>";
                                html = html + "<td style=\"width: 150px;\">"+inputConsumoTotalTecido(data.response[index].tecidos[index_tecido])+"</td>";
                                html = html + "</tr>";

                                array_tecidos.push(data.response[index].tecidos[index_tecido].tecido);
                            }
                        }
                        html = html + "</tbody>";
                        html = html + "</table>";
                    }
                    $(document).find("#conteudo_revisao_em_massa_tecido").html(html);
                    $(document).find(".moeda3").maskMoney({thousands:'', decimal:',', precision: 4});
                }
            });
        }

        function inputConsumoTecido($value){
            html = "<input style=\"height: inherit;\" id=\"consumo-"+$value.tecido+"\" name=\"consumo-"+$value.tecido+"\" type=\"text\" class=\"form-control text-right moeda3\" value=\""+$value.consumo+"\"  maxlength=\"8\" data-tecido=\""+$value.tecido+"\" onkeyup=\"calculoConsumoTecido($(this))\">";
            html = html + "<input id=\"quantidade-"+$value.tecido+"\" name=\"quantidade-"+$value.tecido+"\" type=\"hidden\" value=\""+$value.quantidade+"\" autocomplete=\"off\">";
            return html;
        }

        function inputConsumoTotalTecido($value){
            html = "<input style=\"height: inherit;\" id=\"consumo_total-"+$value.tecido+"\" name=\"consumo_total-"+$value.tecido+"\" type=\"text\" class=\"form-control text-right moeda3\" value=\""+$value.consumo_total+"\"  maxlength=\"8\" disabled>";
            return html;
        }

        function calculoConsumoTecido($value){
            var tecido = $value.data("tecido");
            var form_revisao_em_massa_tecido = $(document).find('#form_revisao_em_massa_tecido');

            var consumo = form_revisao_em_massa_tecido.find("#consumo-"+tecido).val();
            var quantidade = form_revisao_em_massa_tecido.find("#quantidade-"+tecido).val();

            var consumo_total = Math.round((consumo.replace(".","").replace(",", ".") * quantidade)* 1000) / 1000;

            consumo_total = consumo_total.toFixed(3);

            form_revisao_em_massa_tecido.find("#consumo_total-"+tecido).val(numberToReal(consumo_total));
        }

        function numberToReal(valor) {
            var numero = valor.split('.');
            numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
            return numero.join(',');
        }

        function aplicarAlteracaoTecido(){
            esconderPopoverTooltip();
            form_revisao_em_massa_tecido = $(document).find("#form_revisao_em_massa_tecido");

            id_projeto = form_revisao_em_massa_tecido.find("#id_projeto").val();
            id_revisor = form_revisao_em_massa_tecido.find("#id_revisor").val();

            var_itens = [];

            array_tecidos.forEach(function imprimir(item){
                var dados = [];
                dados.push(item);
                dados.push(form_revisao_em_massa_tecido.find("#consumo-"+item).val());
                dados.push(form_revisao_em_massa_tecido.find("#quantidade-"+item).val());
                var_itens.push(dados);
            });

            $.ajax({
                url: "{{ route('lancamento_projeto.tecido.aplicar_alteracao') }}", 
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_projeto: id_projeto,
                    var_itens: var_itens,
                    id_revisor: id_revisor
                },
                method: 'POST',
                async: false,
                success: function(callback){
                    carregarRevisaoMassaTecidos(id_projeto);
                    carregarTabelaProdutos(id_projeto);

                    message("Atenção", "Alterado com sucesso");
                },
                error: function(callback){

                }
            });
        }

        function carregarRevisaoMassaInsumos(id_projeto){
            esconderPopoverTooltip();
            $.ajax({
                url: '{{ Route("lancamento_projeto.insumo.carregar_insumo") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_projeto: id_projeto
                },
                success: function(data) {
                    var html = "";
                    array_insumos = [];
                    for (var index in data.response){
                        html = html + "<hr><h5>"+data.response[index].indice+" - "+data.response[index].descricao+" - QTD: "+data.response[index].quantidade+"</h5><br>";
                        html = html + "<table class=\"table table-verificacao table-striped\">";
                        html = html + "<thead>"+
                            "<th>Item</th>"+
                            "<th>Código</th>"+
                            "<th>Descrição</th>"+
                            "<th>Unidade</th>"+
                            "<th>Unidade Requisitada</th>"+
                            "<th>Consumo T.</th>"+
                            "</thead>";
                        html = html + "<tbody>";
                        if(data.response[index].insumos.length == 0){
                            html = html + "<tr>";
                            html = html + "<td colspan=\"6\" style=\"text-align-last: center;\">NENHUM INSUMO INSERIDO</td>";
                            html = html + "</tr>";
                        }else{
                            var num = 0;
                            for (var index_insumo in data.response[index].insumos){
                                if(data.response[index].insumos[index_insumo].validar === true){
                                    html = html + "<tr>";
                                        html = html + "<td class=\"tb_number\" style=\"width: 10px;\">"+data.response[index].insumos[index_insumo].indice+"</td>";
                                        html = html + "<td style=\"width: 100px;\">"+data.response[index].insumos[index_insumo].codigo+ "</td>";
                                }else{
                                    if(num % 2 === 0){
                                        html = html + "<tr class=\"error-tr odd\">";
                                    }else{
                                        html = html + "<tr class=\"error-tr even\">";
                                    }
                                    html = html + "<td class=\"tb_number\" style=\"width: 10px;\">"+data.response[index].insumos[index_insumo].indice+"</td>";
                                    html = html + "<td style=\"width: 100px;\">"+
                                        "<div>"+
                                            "<div data-toggle=\"popover\" data-placement=\"top\" data-title=\"Detalhes\" data-content=\"<p>A unidade padrão do insumo está incorreta. Favor verificar com o setor responsável.\"></a>"+
                                                "<a href=\"#\" class=\"btn-informacao\"></a>"+
                                                data.response[index].insumos[index_insumo].codigo+
                                            "</div>"+
                                                "</td>";
                                    num++;
                                }
                                
                                
                                html = html + "<td>"+data.response[index].insumos[index_insumo].descricao+"</td>";
                                html = html + "<td class=\"tb_date\" style=\"width: 100px;\">"+data.response[index].insumos[index_insumo].unidade+"</td>";
                                html = html + "<td class=\"tb_date\" style=\"width: 100px;\">"+data.response[index].insumos[index_insumo].unidade_requisitada+"</td>";
                                html = html + "<td style=\"width: 150px;\">"+inputConsumoTotalInsumo(data.response[index].insumos[index_insumo])+"</td>";
                                html = html + "</tr>";

                                array_insumos.push(data.response[index].insumos[index_insumo].insumo);
                            }
                        }
                        html = html + "</tbody>";
                        html = html + "</table>";
                    }
                    $(document).find("#conteudo_revisao_em_massa_insumo").html(html);
                    $(document).find(".moeda").maskMoney({thousands:'', decimal:','});
                    chamadaPopover();
                }
            });


        }

        function inputConsumoTotalInsumo($value){
            html = "<input style=\"height: inherit;\" id=\"consumo_total-"+$value.insumo+"\" name=\"consumo_total-"+$value.insumo+"\" type=\"text\" class=\"form-control text-right moeda\" value=\""+$value.consumo_total+"\"  maxlength=\"8\">";
            html = html + "<input id=\"quantidade-"+$value.insumo+"\" name=\"quantidade-"+$value.insumo+"\" type=\"hidden\" value=\""+$value.quantidade+"\" autocomplete=\"off\">";
            return html;
        }

        function aplicarAlteracaoInsumo(){
            esconderPopoverTooltip();
            form_revisao_em_massa_insumo = $(document).find("#form_revisao_em_massa_insumo");

            id_projeto = form_revisao_em_massa_insumo.find("#id_projeto").val();
            id_revisor = form_revisao_em_massa_insumo.find("#id_revisor").val();

            var_itens = [];

            array_insumos.forEach(function imprimir(item){
                var dados = [];
                dados.push(item);
                dados.push(form_revisao_em_massa_insumo.find("#consumo_total-"+item).val());
                dados.push(form_revisao_em_massa_insumo.find("#quantidade-"+item).val());
                var_itens.push(dados);
            });

            $.ajax({
                url: "{{ route('lancamento_projeto.insumo.aplicar_alteracao') }}", 
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_projeto: id_projeto,
                    var_itens: var_itens,
                    id_revisor: id_revisor
                },
                method: 'POST',
                async: false,
                success: function(callback){
                    carregarRevisaoMassaInsumos(id_projeto);
                    carregarTabelaProdutos(id_projeto);

                    message("Atenção", "Alterado com sucesso");
                },
                error: function(callback){

                }
            });
        }

        function carregarRevisaoMassaServicos(id_projeto){
            esconderPopoverTooltip();
            form_revisao_em_massa_servico = $(document).find("#form_revisao_em_massa_servico");
            
            var tipo;

            if(form_revisao_em_massa_servico.find("#tipo_produto").prop('checked')){
                tipo = "produto";
            }else if(form_revisao_em_massa_servico.find("#tipo_tecido").prop('checked')){
                tipo = "tecido";
            }else{
                tipo = "todos";
            }

            $.ajax({
                url: '{{ Route("lancamento_projeto.servico.carregar_servico") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_projeto: id_projeto,
                    tipo: tipo
                },
                success: function(data) {
                    var html = "";
                    array_servicos = [];
                    for (var index in data.response){
                        html = html + "<hr><h5>"+data.response[index].indice+" - "+data.response[index].descricao+" - QTD: "+data.response[index].quantidade+"</h5><br>";
                        html = html + "<table class=\"table table-striped\">";
                        html = html + "<thead>"+
                            "<th>Item</th>"+
                            "<th>Tipo</th>"+
                            "<th>Código</th>"+
                            "<th>Serviço</th>"+
                            "<th>Facção</th>"+
                            "</thead>";
                        html = html + "<tbody>";
                        if(data.response[index].servicos.length == 0){
                            html = html + "<tr>";
                            html = html + "<td colspan=\"5\" style=\"text-align-last: center;\">NENHUM SERVIÇO INSERIDO</td>";
                            html = html + "</tr>";
                        }else{
                            for (var index_servico in data.response[index].servicos){
                                html = html + "<tr>";
                                html = html + "<td class=\"tb_number\" style=\"width: 10px;\">"+data.response[index].servicos[index_servico].indice+"</td>";
                                html = html + "<td style=\"width: 25px;\">"+data.response[index].servicos[index_servico].tipo+"</td>";
                                html = html + "<td style=\"width: 100px;\">"+data.response[index].servicos[index_servico].codigo+"</td>";
                                html = html + "<td>"+data.response[index].servicos[index_servico].descricao+"</td>";
                                html = html + "<td style=\"width: 50%;\">"+inputFaccao(data.response[index].servicos[index_servico])+"</td>";
                                html = html + "</tr>";

                                array_servicos.push(data.response[index].servicos[index_servico].servico);
                            }
                        }
                        html = html + "</tbody>";
                        html = html + "</table>";
                    }
                    $(document).find("#conteudo_revisao_em_massa_servico").html(html);
                }
            });
        }

        function inputFaccao($value){
            html = "<div style=\"display: inline-flex;align-items: center;position: relative;\">"+
                        "<input style=\"height: inherit;\" type=\"text\" class=\"form-control input-label\" name=\"faccao-"+$value.servico+"\" id=\"faccao-"+$value.servico+"\" value=\""+$value.faccao+"\" data-nome_campo=\"faccao-"+$value.servico+"\" placeholder=\"Facção\" maxlength=\"250\" onkeyup=\"optionsFaccao($(this))\"/>"+
                        "<span style=\"height: inherit;\" class=\"input-group-addon border rounded-right\" id=\"bt-search-faccao-busca-"+$value.servico+"\" data-route=\"{{ route('faccao.modal.buscar') }}\" data-nome_campo=\"faccao-"+$value.servico+"\" onclick=\"buscarFaccao($(this))\"><i style=\"margin-bottom: 5px;margin-left: 5px;margin-right: 5px;margin-top: 5px;\" id=\"bt-view-faccao\" class=\"bt-view\"></i></span>"+
                    "</div>";
            return html;
        }

        function optionsFaccao($this){
            esconderPopoverTooltip();
            $this.autocomplete(optionsAutoCompleteFaccao($this));   
        }

        function buscarFaccao($this){
            showModalFaccao($this.data("route"), "Lista de Facções", $this.data("nome_campo"));
        }

        function optionsAutoCompleteFaccao($this){
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
                    $(document).find('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_grupo_edit_delete').css('z-index')) + 1));
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
                    $this.val(ui.item.label);
                    return false;
                }
            };
        }

        function showModalFaccao(url, title, nome_campo){
            esconderPopoverTooltip();
            $.ajax({
                url: url,
                method: 'GET',
                success: function(body){
                    createModal("faccao_search_show", title, body, 'modal-lg');
                    $(document).ready( function () {
                        table_dialog.on('draw', function () {
                            $(document).find("#faccao_search_show").find('tbody').find("tr").off("click");
                            $(document).find("#faccao_search_show").find('tbody').find("tr").on("click", function(){
                                returnDadosFaccao($(this), nome_campo);
                            });
                        });
                    });
                }
            });
        }

        function returnDadosFaccao($this, nome_campo){
            if($this.find("td").eq(0).hasClass('dataTables_empty')){
                return false;
            }
            $(document).find("#faccao_search_show").modal("hide");
            $(document).find('#form_revisao_em_massa_servico').find("#"+nome_campo).val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
        }

        function aplicarAlteracaoServico(){
            esconderPopoverTooltip();
            form_revisao_em_massa_servico = $(document).find("#form_revisao_em_massa_servico");

            id_projeto = form_revisao_em_massa_servico.find("#id_projeto").val();
            id_revisor = form_revisao_em_massa_servico.find("#id_revisor").val();
            
            var_itens = [];

            array_servicos.forEach(function imprimir(item){
                var dados = [];
                dados.push(item);
                dados.push(form_revisao_em_massa_servico.find("#faccao-"+item).val());
                var_itens.push(dados);
            });

            $.ajax({
                url: "{{ route('lancamento_projeto.servico.aplicar_alteracao') }}", 
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_projeto: id_projeto,
                    var_itens: var_itens,
                    id_revisor: id_revisor
                },
                method: 'POST',
                async: false,
                success: function(callback){
                    carregarRevisaoMassaServicos(id_projeto);
                    carregarTabelaProdutos(id_projeto);

                    message("Atenção", "Alterado com sucesso");
                },
                error: function(callback){
                    message("Atenção", callback.responseJSON.message);
                }
            });
        }

        function alteracaoEmMassaServico(form_revisao_em_massa_servico){
            esconderPopoverTooltip();
            var $class = "dialog_option_deletar";
            var $name_option_sim = "alteracao_em_massa_sim";
            var $option_sim = "";
            var $name_option_nao = "alteracao_em_massa_nao";
            var $option_nao = "";
            var $name_option_cancelar = "alteracao_em_massa_cancelar";
            var $option_cancelar = "";
            var sobrescrever = false;

            message_option_sim_nao("Deseja sobrescrever outras facções?", "Caso opção seja Não, será preenchido os serviços que estão sem facção.", $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao, $name_option_cancelar, $option_cancelar);

            $(document).off("alteracao_em_massa_sim");
            $(document).on("alteracao_em_massa_sim", function(){
                sobrescrever = true;
                alteracaoEmMassaServicoExecutar(form_revisao_em_massa_servico, sobrescrever);
            });

            $(document).off("alteracao_em_massa_nao");
            $(document).on("alteracao_em_massa_nao", function(){
                sobrescrever = false;
                alteracaoEmMassaServicoExecutar(form_revisao_em_massa_servico, sobrescrever);
            });
        }

        function alteracaoEmMassaServicoExecutar(form_revisao_em_massa_servico, sobrescrever){
            limparMesagemErroAdd(form_revisao_em_massa_servico);
            var faccao = form_revisao_em_massa_servico.find("#faccao").val();
            var id_projeto = form_revisao_em_massa_servico.find("#id_projeto").val();
            var id_revisor = form_revisao_em_massa_servico.find("#id_revisor").val();
            var tipo;

            if(form_revisao_em_massa_servico.find("#tipo_produto").prop('checked')){
                tipo = "produto";
            }else if(form_revisao_em_massa_servico.find("#tipo_tecido").prop('checked')){
                tipo = "tecido";
            }else{
                tipo = "todos";
            }

            $.ajax({
                url: '{{ Route("lancamento_projeto.servico.alteracao_em_massa") }}',
                type: 'POST',
                data: {
                    _token: '{{csrf_token()}}',
                    id_projeto: id_projeto,
                    faccao: faccao,
                    tipo: tipo,
                    sobrescrever: sobrescrever,
                    id_revisor: id_revisor,
                },
                success: function(data) {
                    id_projeto = form_revisao_em_massa_servico.find("#id_projeto").val();
                    form_revisao_em_massa_servico.find("#faccao").val('');
                    carregarRevisaoMassaServicos(id_projeto);
                    carregarTabelaProdutos(id_projeto);

                    message("Atenção", "Alterado com sucesso");
                },
                error: function(data){
                    var dados = data.responseJSON;
                    limparMesagemErroAdd(form_revisao_em_massa_servico);
                    mensagemErroAdd(dados, form_revisao_em_massa_servico);
                }
            });
        }
    @endif

    function esconderPopoverTooltip(){
        $('[data-toggle="tooltip"]').tooltip('hide');
        $('[data-toggle="popover"]').popover('hide');
    }

    function precoInformativo($value){
        var html = "";

        if($value.desconto_acima_permitido === true){
            html = "<div>"+
                "<div data-toggle=\"popover\" data-placement=\"top\" data-title=\"Detalhes\" data-content=\"<p>Desconto está acima do máximo permitido. Favor verificar.\">"+
                "<a href=\"#\" class=\"btn-informacao\"></a>"+
                $value.preco_venda+
                "</div></div>";
        }else{
            html = $value.preco_venda;
        }

        return html;
    }

    function carregarTabelaProdutos(id_projeto){
        esconderPopoverTooltip();
        $.ajax({
            url: '{{ Route("lancamento_projeto.produto.carregar_produto") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id_projeto: id_projeto
            },
            success: function(data) {
                table_produto.clear().draw();
                produtos = [];
                revisor_id = form_modal_add.find("#id_revisor").val();
                for (var index in data.response.produtos){
                    var field = [
                        data.response.produtos[index].indice,
                        data.response.produtos[index].codigo,
                        getDetalheProduto(data.response.produtos[index].descricao, data.response.produtos[index].detalhes),
                        @if(!empty($dados['revisor']))
                        data.response.produtos[index].ncm,
                        data.response.produtos[index].peso,
                        @endif
                        @if($intercompany == false)
                            precoInformativo(data.response.produtos[index]),
                        @else
                            '',
                        @endif
                        data.response.produtos[index].custo_unitario,
                        data.response.produtos[index].quantidade,
                        produtoComposicaoTabela(data.response.produtos[index].total_tecido, data.response.produtos[index], revisor_id, "tecido"),
                        produtoComposicaoTabela(data.response.produtos[index].total_insumo, data.response.produtos[index], revisor_id, "insumo"),
                        produtoComposicaoTabela(data.response.produtos[index].total_servico, data.response.produtos[index], revisor_id, "servico"),
                        data.response.produtos[index].total_custo,
                        createBtDuplicar(data.response.produtos[index]),
                        produtoComposicaoTabela("", data.response.produtos[index], revisor_id, "arquivo"),
                        createBtEditarProduto(data.response.produtos[index]),
                        createBtExcluirProduto(data.response.produtos[index]),
                        @if(!empty($dados['revisor']))
                        produtoComFaccao(data.response.produtos[index].produto_com_faccao)
                        @endif
                    ];

                    @if(!empty($dados['revisor']))
                        if(data.response.produtos[index].produto_com_faccao === true){
                            table_produto.row.add(field).draw();
                        }else{
                            table_produto.row.add(field).draw().nodes().to$().addClass('error-tr');
                        }
                    @else 
                        produtos.push(field);
                    @endif
                }
                
                @if(empty($dados['revisor']))
                    table_produto.rows.add(produtos).draw();
                @endif

                chamadaPopover();
                $('.dataTables_scrollFootInner').find('#produto_total_exibicao').html(data.response.total.total_quantidade);
                $('.dataTables_scrollFootInner').find('#produto_total_tecido').html(data.response.total.total_custo_tecido);
                $('.dataTables_scrollFootInner').find('#produto_total_insumo').html(data.response.total.total_custo_insumo);
                $('.dataTables_scrollFootInner').find('#produto_total_servico').html(data.response.total.total_custo_servico);
                $('.dataTables_scrollFootInner').find('#produto_total_custo_unitario').html(data.response.total.total_custo_unitario);
                $('.dataTables_scrollFootInner').find('#produto_total_custo').html(data.response.total.total_custo_total);

            }
        });
    }
</script>
@endsection
