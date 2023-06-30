@extends('layouts.page-dialog')

@section('content')
<ul class="nav nav-tabs">
	<li class="nav-item">
        @if($posicao == "tecido")
		<a class="nav-link active" id="lancamento-projeto-tecido-tab" data-toggle="tab" href="#lancamento_projeto_tecido" role="tab" aria-controls="lancamento_projeto_tecido" aria-selected="false">Tecidos/Fios</a>
        @else
        <a class="nav-link" id="lancamento-projeto-tecido-tab" data-toggle="tab" href="#lancamento_projeto_tecido" role="tab" aria-controls="lancamento_projeto_tecido" aria-selected="false">Tecidos/Fios</a>
        @endif
    </li>
    <li class="nav-item">
        @if($posicao == "insumo")
        <a class="nav-link active" id='lancamento-projeto-insumo-tab' data-toggle="tab" href="#lancamento_projeto_insumo" role="tab" aria-controls="lancamento_projeto_insumo" aria-selected="false">Insumos/Acessórios</a>
        @else
        <a class="nav-link" id='lancamento-projeto-insumo-tab' data-toggle="tab" href="#lancamento_projeto_insumo" role="tab" aria-controls="lancamento_projeto_insumo" aria-selected="false">Insumos/Acessórios</a>
        @endif
	</li>
	<li class="nav-item">
        @if($posicao == "servico")
        <a class="nav-link active" id="lancamento-projeto-servico-tab" data-toggle="tab" href="#lancamento_projeto_servico" role="tab" aria-controls="lancamento_projeto_servico" aria-selected="false">Serviços</a>
        @else
        <a class="nav-link" id="lancamento-projeto-servico-tab" data-toggle="tab" href="#lancamento_projeto_servico" role="tab" aria-controls="lancamento_projeto_servico" aria-selected="false">Serviços</a>
        @endif
    </li>
    <li class="nav-item">
        @if($posicao == "arquivo")
        <a class="nav-link active" id="lancamento-projeto-arquivo-tab" data-toggle="tab" href="#lancamento_projeto_arquivo" role="tab" aria-controls="lancamento_projeto_arquivo" aria-selected="false">Arquivo</a>
        @else
        <a class="nav-link" id="lancamento-projeto-arquivo-tab" data-toggle="tab" href="#lancamento_projeto_arquivo" role="tab" aria-controls="lancamento_projeto_arquivo" aria-selected="false">Arquivo</a>
        @endif
    </li>
</ul>

<div class="tab-content pt-3" id="LancamentoProjetoHeaderContainer">
    @if($posicao == "tecido")
    <div class="tab-pane show active" id="lancamento_projeto_tecido" role="tabpanel" aria-labelledby="dados-tab">
    @else
    <div class="tab-pane" id="lancamento_projeto_tecido" role="tabpanel" aria-labelledby="dados-tab">
    @endif
        <div class="content-filter-dialog">	
            <form action="post" name="form_filter_projeto_tecido" class="cadProjeto" id="form_filter_projeto_tecido" onsubmit="return false;">
                <p><strong>Inserir Tecidos/Fios</strong></p>
                <div class="form-row condicao_media_itens_pedido">
                    <div class="col-sm-12">
                        <strong><span id="produto" class='ml-2'>Produto: {{ $dados['produto_descricao'] }}</span></strong>
                    </div>
                </div>
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
                {!! Form::hidden('id_produto', $dados['id_produto'], ['id' => 'id_produto']) !!}
                {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
                {!! Form::hidden('id_tecido', '', ['id' => 'id_tecido']) !!}
                <div class="form-row mt-3">
                    <div class="form-group col-sm-2">
                        {{ Form::label('tecidos_codigo', 'Código do Tecido', []) }} <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        <div class="input-group" id="cod_tecido_group">
                            {{ Form::text('tecido_codigo', '', ['id' => 'tecido_codigo', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Código', "maxlength" => "250"]) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-tecido"  data-route="{{ route("produto.modal_pesquisa") }}"><i class="bt-view m-2"></i></span>
                        </div>
                    </div>
                    <div class="form-group col-sm-3">
                        {{ Form::label('tecido_descricao', 'Descrição', []) }} <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        {{ Form::text('tecido_descricao', '', ['id' => 'tecido_descricao', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Nome do tecido']) }}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('tecido_consumo', 'Consumo por Peça', []) }} <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        {{ Form::text('tecido_consumo', '', ['id' => 'tecido_consumo', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Consumo', 'maxlength' => '8']) }}
                    </div>
                    <div class="form-group col-sm-3">
                        {{ Form::label('servico_tecido', 'Serviço no Tecido') }}
                        {{ Form::select('servico_tecido', $servicos, '', ['id' => 'servico_tecido', 'class' => 'form-control']) }}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('servico_fator_conversao', 'Fator de Conv. do Serviço') }}
                        {{ Form::text('servico_fator_conversao', '1,00', ['id' => 'servico_fator_conversao', 'class' => 'form-control text-right', 'placeholder' => 'Fator de Conversão do Serviço', 'disabled' => 'disabled', 'maxlength' => '5']) }}
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
                    <div class="form-group col-sm-3">
                        {{ Form::label('tecido_preco_unitario', 'Preço Unitário', []) }}
                        {{ Form::text('tecido_preco_unitario', '', ['id' => 'tecido_preco_unitario', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Preço Unitário', 'maxlength' => '8', 'disabled' => 'disabled']) }}
                    </div>
                    <div class="form-group col-sm-3">
                        {{ Form::label('tecido_quantidade', 'Quantidade de Peça', []) }}
                        {{ Form::text('tecido_quantidade', $quantidade_produto, ['id' => 'tecido_quantidade', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Quantidade de Peça', 'maxlength' => '8', 'disabled' => 'disabled']) }}
                    </div>
                    <div class="form-group col-sm-3">
                        {{ Form::label('tecido_consumo_total', 'Consumo Total') }}
                        {{ Form::text('tecido_consumo_total', '', ['id' => 'tecido_consumo_total', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Preço Total', 'disabled' => 'disabled']) }}
                    </div>
                    <div class="form-group col-sm-3">
                        {{ Form::label('tecido_valor_total', 'Preço Total') }}
                        {{ Form::text('tecido_valor_total', '', ['id' => 'tecido_valor_total', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Preço Total', 'disabled' => 'disabled']) }}
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
                            <th class="tb_number">Preço unitário</th>
                            <th class="tb_number">Consumo por Peça</th>
                            <th class="tb_number">Consumo Total</th>
                            <th class="tb_number">Preço Total</th>
                            <th class="td_acao"></th>
                            <th class="td_acao"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($tecidos_tabela))
                            @foreach($tecidos_tabela as $tecido)
                                <tr>
                                    <td class="td_codigo_produto">{{ $tecido['codigo'] }}</td>
                                    <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $tecido['descricao'] }}">{{ $tecido['descricao'] }}</div></div></td>
                                    <td class="tb_number">{{ $tecido['preco_unitario'] }}</td>
                                    <td class="tb_number">{{ $tecido['consumo_unitario'] }}</td>
                                    <td class="tb_number">{{ $tecido['consumo_total'] }}</td>
                                    <td class="tb_number">{{ $tecido['total_custo'] }}</td>
                                    <td class="td_acao"><a href="#" class="bt-edit" title='Editar' onclick="editarTecidoNoProjeto($(this).parents('tr'), '{{ $tecido['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                    <td class="td_acao"><a href="#" class="bt-delete" title='Excluir' onclick="excluirTecidoNoProjeto($(this).parents('tr'), '{{ $tecido['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="td_codigo_produto"></td>
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
                <button type="button" id="bt_ir_para_insumos" class="btn troca-abas btn-info float-right">Avançar para o insumos/acessórios>></button>
            </div>
            <div class="col-sm-12 mt-5" id="button-bottom">
                <button type="button" id="salvar_composicao_produto_tecido" class="btn btn-success float-right">Salvar Composição</button>
            </div>
        </div>
    </div>

    @if($posicao == "insumo")
    <div class="tab-pane show active" id="lancamento_projeto_insumo" role="tabpanel" aria-labelledby="dados-tab">
    @else
    <div class="tab-pane" id="lancamento_projeto_insumo" role="tabpanel" aria-labelledby="dados-tab">
    @endif
        <div class="content-filter-dialog">	
            <form action="post" name="form_filter_projeto_insumo" class="cadProjeto" id="form_filter_projeto_insumo" onsubmit="return false;">
                <p><strong>Inserir Insumos/Acessórios</strong></p>
                <div class="form-row condicao_media_itens_pedido">
                    <div class="col-sm-12">
                        <strong><span id="produto" class='ml-2'>Produto: {{ $dados['produto_descricao'] }}</span></strong>
                    </div>
                </div>
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
                {!! Form::hidden('id_produto', $dados['id_produto'], ['id' => 'id_produto']) !!}
                {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
                {!! Form::hidden('id_insumo', '', ['id' => 'id_insumo']) !!}
                <div class="form-row mt-3">
                    <div class="form-group col-sm-3">
                        {{ Form::label('insumo_codigo', 'Código do Insumo/Acessórios', []) }} <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        <div class="input-group" id="cod_insumo_group">
                            {{ Form::text('insumo_codigo', '', ['id' => 'insumo_codigo', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Código', "maxlength" => "250"]) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-insumo" data-route="{{ route("produto.modal_pesquisa_limitado") }}"><i class="bt-view m-2"></i></span>
                        </div>
                    </div>
                    <div class="form-group col-sm-5">
                        {{ Form::label('insumo_descricao', 'Descrição', []) }} <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        {{ Form::text('insumo_descricao', '', ['id' => 'insumo_descricao', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Nome do insumo']) }}
                    </div>
                    <div class="form-group col-sm-1">
                        {{ Form::label('insumo_unidade', 'Unidade', []) }} <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        {{ Form::text('insumo_unidade', '', ['id' => 'insumo_unidade', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Unidade', 'disabled' => 'disabled']) }}
                    </div>
                    <div class="form-group col-sm-1">
                        {{ Form::label('insumo_unidade_requisitada', 'Un. Requisitada', []) }} <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        {{ Form::text('insumo_unidade_requisitada', 'UN', ['id' => 'insumo_unidade_requisitada', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Unidade Requisitada', 'disabled' => 'disabled']) }}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('insumo_consumo', 'Consumo Total', []) }} <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
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
                    <div class="form-group col-sm-4">
                        {{ Form::label('insumo_preco_unitario', 'Preço Unitário', []) }}
                        {{ Form::text('insumo_preco_unitario', '', ['id' => 'insumo_preco_unitario', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Preço Unitário', 'maxlength' => '8', 'disabled' => 'disabled']) }}
                    </div>
                    <div class="form-group col-sm-4">
                        {{ Form::label('insumo_quantidade', 'Quantidade de Peça', []) }}
                        {{ Form::text('insumo_quantidade', $quantidade_produto, ['id' => 'insumo_quantidade', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Quantidade de Peça', 'maxlength' => '8', 'disabled' => 'disabled']) }}
                    </div>
                    <div class="form-group col-sm-4">
                        {{ Form::label('insumo_valor_total', 'Preço Total') }}
                        {{ Form::text('insumo_valor_total', '', ['id' => 'insumo_valor_total', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Preço Total', 'disabled' => 'disabled']) }}
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
                <table class="table table-striped table-filter-projeto-insumos table-verificacao" id="table-filters-projeto-insumos">
                    <thead>
                        <tr>
                            <th class="td_codigo_produto">Código</th>
                            <th>Descrição</th>
                            <th class="tb_number">Preço unitário</th>
                            <th class="tb_number">Consumo Total</th>
                            <th class="tb_number">Preço Total</th>
                            <th class="td_acao"></th>
                            <th class="td_acao"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($insumos_tabela))
                            @foreach($insumos_tabela as $chave => $insumo)
                                <tr>
                                    <td>{{ $insumo['codigo'] }}</td>
                                    <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $insumo['descricao'] }}">{{ $insumo['descricao'] }}</div></div></td>
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
                <button type="button" id="bt_ir_para_tecidos" class="btn troca-abas btn-info"><< Voltar para o tecido</button>
                <button type="button" id="bt_ir_para_servicos" class="btn troca-abas btn-info float-right">Avançar para o serviço >></button>
            </div>
            <div class="col-sm-12 mt-1" id="button-bottom">
                <button type="button" id="salvar_composicao_produto_insumo" class="btn btn-success float-right">Salvar Composição</button>
            </div>
        </div>
    </div>

    @if($posicao == "servico")
    <div class="tab-pane show active" id="lancamento_projeto_servico" role="tabpanel" aria-labelledby="dados-tab">
    @else
    <div class="tab-pane" id="lancamento_projeto_servico" role="tabpanel" aria-labelledby="dados-tab">
    @endif
        <div class="content-filter-dialog">	
            <form action="post" name="form_filter_projeto_servico" class="cadProjeto" id="form_filter_projeto_servico" onsubmit="return false;">
                @csrf
                {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
                {!! Form::hidden('id_produto', $dados['id_produto'], ['id' => 'id_produto']) !!}
                {!! Form::hidden('id_revisor', $dados['revisor'], ['id' => 'id_revisor']) !!}
                {!! Form::hidden('id_servico', '', ['id' => 'id_servico']) !!}
                <p><strong>Inserir Serviços</strong></p>
                <div class="form-row condicao_media_itens_pedido">
                    <div class="col-sm-12">
                        <strong><span id="produto" class='ml-2'>Produto: {{ $dados['produto_descricao'] }}</span></strong>
                    </div>
                </div>
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
                    @if(!empty($dados['revisor']))
                    <div class="form-group col-sm-3">
                        {{ Form::label('faccao', 'Facção', []) }} <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        <div class="input-group">
                            {{ Form::text('faccao', '', ['id' => 'faccao', 'class' => 'form-control input-label', 'placeholder' => 'Facção', 'maxlength' => '250', 'onkeyup' => 'optionsFaccaoComposicao();']) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-faccao-busca" data-route="{{ route("faccao.modal.buscar") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                            {{ Form::hidden('codigo_faccao', '', ['id' => 'codigo_faccao']) }}
                        </div>
                    </div>
                    @endif
                    @if(!empty($dados['revisor']))
                    <div class="form-group col-sm-4">
                    @else
                    <div class="form-group col-sm-6">
                    @endif
                        {{ Form::label('servico_codigo', 'Código do Serviço', []) }} <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        <div class="input-group" id="cod_servico_group">
                            {{ Form::text('servico_codigo', '', ['id' => 'servico_codigo', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Código', "maxlength" => "250"]) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-servico" data-route="{{ route("produto.modal_pesquisa_limitado") }}"><i class="bt-view m-2"></i></span>
                        </div>
                    </div>
                    @if(!empty($dados['revisor']))
                    <div class="form-group col-sm-4">
                    @else
                    <div class="form-group col-sm-6">
                    @endif
                        {{ Form::label('servico_descricao', 'Serviço', []) }} <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        {{ Form::text('servico_descricao', '', ['id' => 'servico_descricao', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Nome do servico']) }}
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-4">
                        {{ Form::label('servico_custo_unitario', 'Preço Unitário', []) }}
                        {!! Form::text("servico_custo_unitario", '', ["id"=> "servico_custo_unitario", "class"=>"form-control text-right", "placeholder" => "Preço Unitário", "maxlength"=>"8", "disabled" => "disabled"]) !!}
                    </div>
                    <div class="form-group col-sm-4">
                        {{ Form::label('servico_quantidade', 'Quantidade de Peça', []) }}
                        {!! Form::text("servico_quantidade", $quantidade_produto, ["id"=> "servico_quantidade", "class"=>"form-control text-right", "maxlength"=>"8", "disabled" => "disabled"]) !!}
                    </div>
                    <div class="form-group col-sm-4">
                        {{ Form::label('servico_custo_total', 'Preço Total', []) }}
                        {!! Form::text("servico_custo_total", '', ["id"=> "servico_custo_total", "class"=>"form-control text-right", "placeholder" => "Preço Total", "maxlength"=>"8", "disabled" => "disabled"]) !!}
                    </div>
                </div>
                <div class="content-buttons">
                    <button name="btn-create" id="btn-create-servico-projeto" class="btn-create">Inserir serviço</button>
                    <button name="btn-cancel" id="btn-cancel-servico-projeto" class="btn-cancel">Cancelar</button>
                </div>
            </form>
        </div>
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped table-filters-projeto-servico" id="table-filters-projeto-servico">
                    <thead>
                        <tr>
                            @if(!empty($dados['revisor']))
                            <th>CNPJ</th>
                            <th>Facção</th>
                            @endif
                            <th>Tipo</th>
                            <th>Ref. Produto</th>
                            <th>Tipo de Serviço</th>
                            <th class="tb_number td_preco">Preço unitário</th>
                            <th class="tb_number td_preco">Quantidade</th>
                            <th class="tb_number td_preco">Preço Total</th>
                            <th class="td_acao"></th>
                            <th class="td_acao"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($faccoes_tabela))
                            @foreach($faccoes_tabela as $faccao)
                                <tr>
                                    @if(!empty($dados['revisor']))
                                    <td><div><div>{{ $faccao['cnpj'] }}</div></div></td>
                                    <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $faccao['faccao'] }}">{{ $faccao['faccao'] }}</div></div></td>
                                    @endif
                                    @if(empty($faccao['tecido']))
                                        <td>PRODUTO</td>
                                        <td>
                                            <div>
                                                <div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $faccao['produto'] }}">
                                                    {{ $faccao['produto'] }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $faccao['tipo_de_servico'] }}">
                                                    {{ $faccao['tipo_de_servico'] }}
                                                </div>
                                            </div>
                                        </td>
                                    @else
                                    <td>TECIDO</td>
                                    <td>
                                        <div>
                                            @if(empty($faccao['produto_acabado']))
                                            <div  data-toggle="popover" data-placement="top" data-title="Detalhes" data-content="<p><b>Tecido:</b> {{ $faccao['tecido'] }}<p><b>Ref. Produto:</b> {{ $faccao['produto'] }}">
                                            @else
                                            <div  data-toggle="popover" data-placement="top" data-title="Detalhes" data-content="<p><b>Tecido:</b> {{ $faccao['tecido'] }}<p><b>Ref. Produto:</b> {{ $faccao['produto'] }} <p><b>Produto Acabado:</b> {{ $faccao['produto_acabado'] }}">
                                            @endif
                                                <a href="#" class="btn-informacao"></a>
                                                {{ $faccao['tecido'] }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $faccao['tipo_de_servico'] }}">
                                                {{ $faccao['tipo_de_servico'] }}
                                            </div>
                                        </div>
                                    </td>
                                    @endif
                                    <td class="tb_number td_preco">{{ $faccao['preco_unitario'] }}</td>
                                    <td class="tb_number td_preco">{{ $faccao['quantidade'] }}</td>
                                    <td class="tb_number td_preco">{!! $faccao['custo_total'] !!}</td>
                                    <td class="td_acao"><a href="#" class="bt-edit" title='Editar' onclick="editarServicoNoProjeto($(this).parents('tr'), '{{ $faccao['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                    <td class="td_acao"><a href="#" class="bt-delete" title='Excluir' onclick="excluirServicoNoProjeto($(this).parents('tr'), '{{ $faccao['id'] }}', '{{ $dados['id'] }}')"></a></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            @if(!empty($dados['revisor']))
                            <td></td>
                            <td></td>
                            @endif
                            <td></td>
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
                <button type="button" id="bt_ir_para_insumos" class="btn troca-abas btn-info"><< Voltar para o insumo</button>
                <button type="button" id="bt_ir_para_arquivos" class="btn troca-abas btn-info float-right">Avançar para o arquivo >></button>
            </div>
            <div class="col-sm-12 mt-1" id="button-bottom">
                <button type="button" id="salvar_composicao_produto_servico" class="btn btn-success float-right">Salvar Composição</button>
            </div>
        </div>
    </div>

    @if($posicao == "arquivo")
    <div class="tab-pane show active" id="lancamento_projeto_arquivo" role="tabpanel" aria-labelledby="dados-tab">
    @else
    <div class="tab-pane" id="lancamento_projeto_arquivo" role="tabpanel" aria-labelledby="dados-tab">
    @endif
        <form action="post" name="form_arquivo" id="form_arquivo" onsubmit="return false;">
            @csrf
            {!! Form::hidden('id_produto', $dados['id_produto'], ['id' => 'id_produto']) !!}
            <div class="form-row">
                <div class="form-group col-sm-6"> 
                    {{ Form::label('arquivo', 'Arquivo(Tamanho máximo 2 MB, favor converter antes de enviar)') }}
                    {!! Form::file('arquivo', ['id'=>'arquivo', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;"], null) !!}
                </div>
                <div class="form-group col-sm-6">
                    {!! Form::label('tipo_arquivo', 'Tipo de Arquivo', []) !!}
                    {!! Form::select('tipo_arquivo', ['diversos' => 'Diversos', 'etiqueta' => 'Etiqueta', 'produto' => 'Produto'], '', ['id' => 'tipo_arquivo', 'class' => 'form-control', 'placeholder' => 'Selecione', "style" => "height: 38px;"]) !!}
                </div>
            </div>
            <div class="form-row">
                <div class="content-buttons">
                    <button type="button" id="upload_arquivo" class="btn btn-success">Upload</button>
                </div>
            </div>

        </form>
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped table-filters-projeto-arquivo" id="table-filters-projeto-arquivo">
                    <thead>
                        <tr>
                            <th>Arquivo</th>
                            <th class="td_tipo">Tipo</th>
                            <th class="td_acao"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($arquivos as $arquivo)
                        <tr>
                            <td><a href="{{ asset($arquivo['arquivo']) }}" target="_blank">{{ $arquivo['nome'] }}</a></td>
                            <td class="td_tipo">{{ $arquivo['tipo'] }}</td>
                            <td class="td_acao"><a href="#" class="bt-delete" title='Excluir' data-id="{{ $arquivo['id'] }}" onclick="excluirArquivo($(this).parents('tr'), $(this))"></a></td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    </tfoot>
                </table>
            </div>
            <div class="col-sm-12 mt-14" id="button-bottom">
                <button type="button" id="bt_ir_para_servicos" class="btn troca-abas btn-info"><< Voltar para o serviço</button>
            </div>
            <div class="col-sm-12 mt-1" id="button-bottom">
                <button type="button" id="salvar_composicao_produto_arquivo" class="btn btn-success float-right">Salvar Composição</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready( function () {
        init();

        setTimeout(function(){
            table_tecido.draw(false);
            table_insumo.draw(false);
            table_servico.draw(false);
            table_arquivo.draw(false);
        }, 150);

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
        initTable();
        initAutoCompletes();
        initFunctionsOn();
        initMaskCampos();
    }

    function initTable(){
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
            "order": [[ 0, 'asc' ]]
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
            "order": [[ 0, 'asc' ]]
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

        table_filters_arquivo_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "35vh",
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
                "emptyTable":     "Nenhum arquivo inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum  arquivo inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                {
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    'width': '5px',
                    "orderable": false
                },
                {
                    'targets': ['td_tipo'],
                    'width': '150px'
                },
                
            ],
            "order": [[ 1, 'asc' ]]
        };
        table_arquivo = '';
        table_arquivo = $(document).find('#table-filters-projeto-arquivo').DataTable(table_filters_arquivo_options);
        table_arquivo.draw();
    }

    function initAutoCompletes(){
        form_modal_composicao_tecido = $(document).find('#form_filter_projeto_tecido');
        form_modal_composicao_tecido.find("#tecido_descricao").autocomplete(optionsAutoCompleteTecido(form_modal_composicao_tecido));

        form_modal_composicao_insumo = $(document).find('#form_filter_projeto_insumo');
        form_modal_composicao_insumo.find("#insumo_descricao").autocomplete(optionsAutoCompleteInsumo(form_modal_composicao_insumo));

        form_modal_composicao_servico = $(document).find('#form_filter_projeto_servico');
        form_modal_composicao_servico.find("#servico_descricao").autocomplete(optionsAutoCompleteServico(form_modal_composicao_servico));
    }

    function optionsFaccaoComposicao(){
        form_servico_add = $(document).find('#form_filter_projeto_servico');
        form_servico_add.find("#faccao").autocomplete(optionsAutoCompleteFaccaoFixo(form_servico_add));        
    }

    function initMaskCampos(){
        form_modal_composicao_tecido = $(document).find('#form_filter_projeto_tecido');
        form_modal_composicao_tecido.find("#tecido_consumo").maskMoney({thousands:'', decimal:',', precision: 4});
        form_modal_composicao_tecido.find("#servico_fator_conversao").maskMoney({thousands:'', decimal:','});

        form_modal_composicao_insumo = $(document).find('#form_filter_projeto_insumo');
        form_modal_composicao_insumo.find("#insumo_consumo").maskMoney({thousands:'', decimal:','});

        form_modal_composicao_servico = $(document).find('#form_filter_projeto_servico');
        form_modal_composicao_servico.find("#servico_custo_unitario").maskMoney({thousands:'', decimal:','});
    }

    function initFunctionsOn(){
        form_modal_composicao_tecido = $(document).find('#form_filter_projeto_tecido');
        form_modal_composicao_tecido.find("#tecido_codigo").off('blur');
        form_modal_composicao_tecido.find("#tecido_codigo").on('blur', function(){
            form_modal_composicao_tecido.find("#tecido_consumo").val('');
            retornarDescricaoTecido(form_modal_composicao_tecido);
        });
        form_modal_composicao_tecido.find("#tecido_descricao").off('blur');
        form_modal_composicao_tecido.find("#tecido_descricao").on('blur', function(){
            retornarDescricaoTecido(form_modal_composicao_tecido);
        });
        form_modal_composicao_tecido.find("#bt-search-tecido").off("click");
		form_modal_composicao_tecido.find("#bt-search-tecido").on("click", function(event){
            event.stopPropagation();
            form_modal_composicao_tecido.find("#tecido_consumo").val('');
			showModalTecido($(this).data("route"), 'Lista de Tecidos/Fios');
            return false;
        });
        form_modal_composicao_tecido.find("#tecido_consumo").off('keyup');
        form_modal_composicao_tecido.find("#tecido_consumo").on('keyup', function(){
            calculosTecido(form_modal_composicao_tecido);
        });
        $(document).find("#btn-create-tecido-projeto").off("click");
        $(document).find("#btn-create-tecido-projeto").on("click", function () {
            salvarTecidoNoProjeto();
        });
        form_modal_composicao_tecido.find("#btn-cancel-tecido-projeto").hide();
        form_modal_composicao_tecido.find("#btn-cancel-tecido-projeto").off('click');
        form_modal_composicao_tecido.find("#btn-cancel-tecido-projeto").on('click', function(){
            if(form_modal_composicao_tecido.find("#id_tecido").val() == ''){
                limparMesagemErroComposicao(form_modal_composicao_tecido);
                limparCamposTecido();
            }else{
                cancelarEdicaoTecido();
            }
        });
        form_modal_composicao_tecido.find("#servico_tecido").off('change');
        form_modal_composicao_tecido.find("#servico_tecido").on('change', function(){
            if(form_modal_composicao_tecido.find("#servico_tecido").val() != 0){
                form_modal_composicao_tecido.find("#servico_fator_conversao").removeAttr('disabled');
            }else{
                form_modal_composicao_tecido.find("#servico_fator_conversao").attr("disabled", "disabled");
            }
        });

        form_modal_composicao_insumo = $(document).find('#form_filter_projeto_insumo');
        form_modal_composicao_insumo.find("#insumo_codigo").off('blur');
        form_modal_composicao_insumo.find("#insumo_codigo").on('blur', function(){
            if(form_modal_composicao_insumo.find("#insumo_codigo").val() != ''){
                retornarDescricaoInsumo(form_modal_composicao_insumo);
            }
        });
        form_modal_composicao_insumo.find("#insumo_descricao").off('blur');
        form_modal_composicao_insumo.find("#insumo_descricao").on('blur', function(){
            if(form_modal_composicao_insumo.find("#insumo_codigo").val() != ''){
                retornarDescricaoInsumo(form_modal_composicao_insumo);
            }
        });
        form_modal_composicao_insumo.find("#bt-search-insumo").off("click");
		form_modal_composicao_insumo.find("#bt-search-insumo").on("click", function(event){
            event.stopPropagation();
            form_modal_composicao_insumo.find("#insumo_consumo").val('');
			showModalInsumo($(this).data("route"), "linha", "INSUMO", 'Lista de Insumos');
            return false;
        });
        form_modal_composicao_insumo.find("#insumo_consumo").off('keyup');
        form_modal_composicao_insumo.find("#insumo_consumo").on('keyup', function(){
            calculosInsumo(form_modal_composicao_insumo);
        });
        form_modal_composicao_insumo.find("#btn-cancel-insumo-projeto").hide();
        form_modal_composicao_insumo.find("#btn-cancel-insumo-projeto").off('click');
        form_modal_composicao_insumo.find("#btn-cancel-insumo-projeto").on('click', function(){
            if(form_modal_composicao_insumo.find("#id_insumo").val() == ''){
                limparMesagemErroComposicao(form_modal_composicao_insumo);
                limparCamposInsumo();
            }else{
                cancelarEdicaoInsumo();
            }
        });
        form_modal_composicao_insumo.find("#btn-create-insumo-projeto").off("click");
        form_modal_composicao_insumo.find("#btn-create-insumo-projeto").on("click", function () {
            salvarInsumoNoProjeto();
        });

        form_modal_composicao_servico = $(document).find('#form_filter_projeto_servico');
        form_modal_composicao_servico.find("#servico_codigo").off('blur');
        form_modal_composicao_servico.find("#servico_codigo").on('blur', function(){
            if(form_modal_composicao_servico.find("#servico_codigo").val() != ''){
                retornarDescricaoServico(form_modal_composicao_servico);
            }
        });
        form_modal_composicao_servico.find("#servico_descricao").off('blur');
        form_modal_composicao_servico.find("#servico_descricao").on('blur', function(){
            if(form_modal_composicao_servico.find("#servico_descricao").val() == ''){
                form_modal_composicao_servico.find("#servico_custo_unitario").val('');
                form_modal_composicao_servico.find("#servico_custo_total").val('');
            }else{
                retornarDescricaoServico(form_modal_composicao_servico);
            }
        });
        form_modal_composicao_servico.find("#bt-search-servico").off("click");
		form_modal_composicao_servico.find("#bt-search-servico").on("click", function(event){
            event.stopPropagation();
            @if($licitacao)
                showModalServico($(this).data("route"), "marca", "MAO DE OBRA LICITACAO", 'Lista de Serviços Licitação');
            @else
                showModalServico($(this).data("route"), "linha", "MAO DE OBRA", 'Lista de Serviços');
            @endif
            return false;
        });

        form_modal_composicao_servico.find("#tipo_de_servico").off("change");
        form_modal_composicao_servico.find("#tipo_de_servico").on("change", function () {
            if(form_modal_composicao_servico.find("#tipo_de_servico") == ''){
                form_modal_composicao_servico.find("#servico_custo_unitario").val('');
                form_modal_composicao_servico.find("#servico_custo_total").val('');
            }else{
                getPrecoServico();
            }
        });
        form_modal_composicao_servico.find("#servico_custo_unitario").off("keyup");
        form_modal_composicao_servico.find("#servico_custo_unitario").on("keyup", function () {
            if(form_modal_composicao_servico.find("#servico_custo_unitario") == ''){
                form_modal_composicao_servico.find("#servico_custo_total").val('');
            }else{
                calculosServico(form_modal_composicao_servico);
            }
        });
        form_modal_composicao_servico.find("#btn-create-servico-projeto").off("click");
        form_modal_composicao_servico.find("#btn-create-servico-projeto").on("click", function () {
            salvarServicoNoProjeto();
        });
        form_modal_composicao_servico.find("#btn-cancel-servico-projeto").hide();
        form_modal_composicao_servico.find("#btn-cancel-servico-projeto").off('click');
        form_modal_composicao_servico.find("#btn-cancel-servico-projeto").on('click', function(){
            if(form_modal_composicao_servico.find("#id_servico").val() == ''){
                limparMesagemErroAdd(form_modal_composicao_servico);
                limparCamposServico();
            }else{
                cancelarEdicaoServico();
            }
        });
        form_modal_composicao_servico.find("#bt-search-faccao-busca").off("click");
        form_modal_composicao_servico.find("#bt-search-faccao-busca").on("click", function(){
            showModalFaccaoFixo($(this).data("route"), "Lista de Facções");
        });

        $(document).find("#salvar_composicao_produto_tecido").off("click");
        $(document).find("#salvar_composicao_produto_tecido").on("click", function(){
            $("#form_filter_projeto_tecido").parents('.modal').modal('hide');
        });
        $(document).find("#salvar_composicao_produto_insumo").off("click");
        $(document).find("#salvar_composicao_produto_insumo").on("click", function(){
            $("#form_filter_projeto_tecido").parents('.modal').modal('hide');
        });
        $(document).find("#salvar_composicao_produto_servico").off("click");
        $(document).find("#salvar_composicao_produto_servico").on("click", function(){
            $("#form_filter_projeto_tecido").parents('.modal').modal('hide');
        });
        $(document).find("#salvar_composicao_produto_arquivo").off("click");
        $(document).find("#salvar_composicao_produto_arquivo").on("click", function(){
            $("#form_filter_projeto_tecido").parents('.modal').modal('hide');
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

        $(document).find("#lancamento-projeto-arquivo-tab").off("click");
        $(document).find("#lancamento-projeto-arquivo-tab").on("click", function(){
            setTimeout(function(){
                table_arquivo.draw(false);
            }, 100);
        });

        $(document).find(".troca-abas").off("click");
        $(document).find(".troca-abas").on("click", function(e){
            if($(this).attr("id") === "bt_ir_para_tecidos"){
                $(document).find("#lancamento-projeto-tecido-tab").tab("show");
                table_tecido.draw(false);
            }else if($(this).attr("id") === "bt_ir_para_insumos"){
                $(document).find("#lancamento-projeto-insumo-tab").tab("show");
                table_insumo.draw(false);
            }else if($(this).attr("id") === "bt_ir_para_servicos"){
                $(document).find("#lancamento-projeto-servico-tab").tab("show");
                table_servico.draw(false);
            }else  if($(this).attr("id") === "bt_ir_para_arquivos"){
                $(document).find("#lancamento-projeto-arquivo-tab").tab("show");
                table_arquivo.draw(false);
            }
        });

        form_modal_composicao_arquivo = $(document).find('#form_arquivo');
        form_modal_composicao_arquivo.find("#upload_arquivo").off("click");
        form_modal_composicao_arquivo.find("#upload_arquivo").on("click", function(e){
            anexarArquivo();
        });
    }

    function optionsAutoCompleteTecido(form_modal_composicao_tecido){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.estabelecimento = form_modal_composicao_tecido.find("#tecido_estabelecimento").val();
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
                    form_modal_composicao_tecido.find("#tecido_codigo").val('');
                    form_modal_composicao_tecido.find("#tecido_descricao").val('');
                    form_modal_composicao_tecido.find("#tecido_consumo").val('');
                    form_modal_composicao_tecido.find("#tecido_preco_unitario").val('');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_modal_composicao_tecido.find("#tecido_descricao").val(ui.item.label);
                form_modal_composicao_tecido.find("#tecido_codigo").val(ui.item.value);
                form_modal_composicao_tecido.find("#tecido_preco_unitario").val(ui.item.preco);
                return false;
            }
        };
    }

    function showModalTecido(url, title){
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(body){
                createModal("tecido_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_filters_produtos_busca.on('draw', function () {
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
        form_modal_composicao_tecido = $(document).find('#form_filter_projeto_tecido');
        form_modal_composicao_tecido.find("#tecido_descricao").val($this.find("td").eq(2).text());
        form_modal_composicao_tecido.find("#tecido_codigo").val($this.find("td").eq(1).text());
        retornarDescricaoTecido(form_modal_composicao_tecido);
        calculosTecido(form_modal_composicao_tecido);
    }

    function retornarDescricaoTecido(form_modal_composicao_tecido){
        data_form_modal_composicao_tecido = form_modal_composicao_tecido.serialize();
        
        limparMesagemErroComposicao(form_modal_composicao_tecido);
        $.ajax({
            url: '{{ route('produto.tecido.tecido_descricao')}}',
            data: data_form_modal_composicao_tecido,
            method: 'POST',
            async: false,
            success: function(callback){
                var produto = callback;
                table_tecido_estabelecimento.clear().draw();
                if(produto.estoque != ''){
                    dadosRetornoTecidoDescricao(produto, form_modal_composicao_tecido);  
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
                    form_modal_composicao_tecido.find('#tecido_codigo').val('');
                    form_modal_composicao_tecido.find('#tecido_descricao').val('');
                }
                
            },
            error: function(callback){
                if(form_modal_composicao_tecido.find('#tecido_codigo').val() != ''){
                    var dados = callback.responseJSON;
                    limparMesagemErroComposicao(form_modal_composicao_tecido);
                    mensagemErroComposicao(dados, form_modal_composicao_tecido);
                }
            }
        });
    }

    function dadosRetornoTecidoDescricao(produto, form_modal_composicao_tecido){
        form_modal_composicao_tecido.find('#tecido_descricao').val(produto.nome);
        form_modal_composicao_tecido.find('#tecido_preco_unitario').val(produto.preco);
        calculosTecido(form_modal_composicao_tecido);
    }

    function salvarTecidoNoProjeto(){
        form_modal_composicao_tecido = $(document).find("#form_filter_projeto_tecido");
        if (form_modal_composicao_tecido.find("#id_tecido").val() == ''){

            codigo = form_modal_composicao_tecido.find("#tecido_codigo").val();
            descricao = form_modal_composicao_tecido.find("#tecido_descricao").val();
            consumo = form_modal_composicao_tecido.find("#tecido_consumo").val();
            preco_unitario = form_modal_composicao_tecido.find("#tecido_preco_unitario").val();
            consumo_total = form_modal_composicao_tecido.find("#tecido_consumo_total").val();
            quantidade = form_modal_composicao_tecido.find("#tecido_quantidade").val();
            custo_total = form_modal_composicao_tecido.find("#tecido_valor_total").val();
            id_projeto = form_modal_composicao_tecido.find("#id_projeto").val();
            id_produto = form_modal_composicao_tecido.find("#id_produto").val();
            servico_tecido = form_modal_composicao_tecido.find("#servico_tecido").val();
            servico_fator_conversao = form_modal_composicao_tecido.find("#servico_fator_conversao").val();
            id_revisor = form_modal_composicao_tecido.find("#id_revisor").val();

            limparMesagemErroComposicao(form_modal_composicao_tecido);
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
                    id_produto: id_produto,
                    servico_tecido: servico_tecido,
                    servico_fator_conversao: servico_fator_conversao,
                    id_revisor: id_revisor,
                },
                success: function(data) {
					var field = [
                        data.response.tecido.codigo,
                        ajusteTamanhoTable(data.response.tecido.descricao),
                        data.response.tecido.preco_unitario,
                        data.response.tecido.consumo_unitario,
                        data.response.tecido.consumo_total,
                        data.response.tecido.total_custo,
                        createBtEditarTecido(data.response.tecido.id, data.response.tecido.projeto_id),
                        createBtExcluirTecido(data.response.tecido.id, data.response.tecido.projeto_id)
                    ];
                    table_tecido.row.add(field).draw();

                    if(data.response.servico != ''){
                        var field = [
                            @if(!empty($dados['revisor']))
                            data.response.cnpj,
                            ajusteTamanhoTable(data.response.faccao),
                            @endif
                            data.response.servico.tipo,
                            produtoOuTecido(data.response.servico),
                            ajusteTamanhoTable(data.response.servico.tipo_de_servico),
                            data.response.servico.preco_unitario,
                            data.response.servico.quantidade,
                            data.response.servico.custo_total,
                            createBtEditarServico(data.response.servico.id, data.response.servico.id_projeto),
                            createBtExcluirServico(data.response.servico.id, data.response.servico.id_projeto)
                        ];
    
                        table_servico.row.add(field).draw();

                        $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.servico.total);

                        chamadaPopover();
                    }

                    carregarTabelaProdutos(id_projeto);

                    $('.dataTables_scrollFootInner').find('#tecido_total_exibicao').html(data.response.tecido.tecido_total);

                    form_modal_composicao_tecido.find("#tecido_codigo").focus();

                    limparCamposTecido();
                },
                error: function(data){
                    var dados = data.responseJSON;
                    limparMesagemErroComposicao(form_modal_composicao_tecido);
                    mensagemErroComposicao(dados, form_modal_composicao_tecido);
                }
            });
        }else{
            salvarEdicaoTecidoNoProjeto();
        }
    }

    function salvarEdicaoTecidoNoProjeto(){
        form_modal_composicao_tecido = $(document).find("#form_filter_projeto_tecido");

        codigo = form_modal_composicao_tecido.find("#tecido_codigo").val();
        descricao = form_modal_composicao_tecido.find("#tecido_descricao").val();
        consumo = form_modal_composicao_tecido.find("#tecido_consumo").val();
        preco_unitario = form_modal_composicao_tecido.find("#tecido_preco_unitario").val();
        consumo_total = form_modal_composicao_tecido.find("#tecido_consumo_total").val();
        quantidade = form_modal_composicao_tecido.find("#tecido_quantidade").val();
        custo_total = form_modal_composicao_tecido.find("#tecido_valor_total").val();
        id_projeto = form_modal_composicao_tecido.find("#id_projeto").val();
        id_tecido = form_modal_composicao_tecido.find("#id_tecido").val();
        id_produto = form_modal_composicao_tecido.find("#id_produto").val();
        servico_tecido = form_modal_composicao_tecido.find("#servico_tecido").val();
        servico_fator_conversao = form_modal_composicao_tecido.find("#servico_fator_conversao").val();
        id_revisor = form_modal_composicao_tecido.find("#id_revisor").val();

        limparMesagemErroComposicao(form_modal_composicao_tecido);
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
                id_produto: id_produto,
                servico_tecido: servico_tecido,
                servico_fator_conversao: servico_fator_conversao,
                id_revisor: id_revisor
            },
            success: function(data) {
                var field = [
                    data.response.tecido.codigo,
                    ajusteTamanhoTable(data.response.tecido.descricao),
                    data.response.tecido.preco_unitario,
                    data.response.tecido.consumo_unitario,
                    data.response.tecido.consumo_total,
                    data.response.tecido.total_custo,
                    createBtEditarTecido(data.response.tecido.id, data.response.tecido.projeto_id),
                    createBtExcluirTecido(data.response.tecido.id, data.response.tecido.projeto_id)
                ];
                table_tecido.row.add(field).draw();
                $(document).find("#form_filter_projeto_tecido").find("#id_tecido").val('');
                $(document).find("#form_filter_projeto_tecido").find("#btn-create-tecido-projeto").html('Inserir Tecido');
                $(document).find("#form_filter_projeto_tecido").find("#btn-cancel-tecido-projeto").hide();
                $('.dataTables_scrollFootInner').find('#tecido_total_exibicao').html(data.response.tecido.tecido_total);

                form_modal_composicao_tecido.find("#tecido_codigo").focus();

                if(data.response.servicos != ''){
                    servicos = [];
                    table_servico.clear().draw();
                    for (var servico in data.response.servicos.tabela){
                        var field = [
                            @if(!empty($dados['revisor']))
                            data.response.servicos.tabela[servico].cnpj,
                            ajusteTamanhoTable(data.response.servicos.tabela[servico].faccao),
                            @endif
                            data.response.servicos.tabela[servico].tipo,
                            produtoOuTecido(data.response.servicos.tabela[servico]),
                            ajusteTamanhoTable(data.response.servicos.tabela[servico].tipo_de_servico),
                            data.response.servicos.tabela[servico].preco_unitario,
                            data.response.servicos.tabela[servico].quantidade,
                            data.response.servicos.tabela[servico].custo_total,
                            createBtEditarServico(data.response.servicos.tabela[servico].id, data.response.servicos.tabela[servico].id_projeto),
                            createBtExcluirServico(data.response.servicos.tabela[servico].id, data.response.servicos.tabela[servico].id_projeto)
                        ];
                        servicos.push(field);
                    }
                    table_servico.rows.add(servicos).draw();
                    chamadaPopover();
                    $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.servicos.total);
                }

                limparCamposTecido();
                carregarTabelaProdutos(id_projeto);
            },
            error: function(data){
                var dados = data.responseJSON;
                limparMesagemErroComposicao(form_modal_composicao_tecido);
                mensagemErroComposicao(dados, form_modal_composicao_tecido);
            }
        });
    }

    function cancelarEdicaoTecido(){
        form_modal_composicao_tecido = $(document).find("#form_filter_projeto_tecido");
        data_form = form_modal_composicao_tecido.serialize();
        limparMesagemErroComposicao(form_modal_composicao_tecido);
        $.ajax({
            url: '{{ Route("lancamento_projeto.tecido.cancelar_edicao") }}',
            type: 'POST',
            data: data_form,
            success: function(data){
                var field = [
                    data.response.codigo,
                    ajusteTamanhoTable(data.response.descricao),
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
        form_modal_composicao_tecido = $(document).find("#form_filter_projeto_tecido");

        id_tecido = form_modal_composicao_tecido.find("#id_tecido").val();
    
        limparMesagemErroComposicao(form_modal_composicao_tecido);
        if(id_tecido == ''){
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

                    form_modal_composicao_tecido.find("#id_tecido").val(data.response.id);
                    form_modal_composicao_tecido.find("#tecido_codigo").val(data.response.codigo);
                    form_modal_composicao_tecido.find("#tecido_consumo").val(data.response.consumo_unitario);
                    form_modal_composicao_tecido.find("#tecido_valor_total").val(data.response.custo_total);
                    form_modal_composicao_tecido.find("#servico_tecido").val(data.response.servico);
                    form_modal_composicao_tecido.find("#servico_fator_conversao").val(data.response.fator_conversao);

                    if(data.response.servico != 0){
                        form_modal_composicao_tecido.find("#servico_fator_conversao").removeAttr('disabled');;
                    }

                    form_modal_composicao_tecido.find("#btn-create-tecido-projeto").html('Editar Tecido');
                    form_modal_composicao_tecido.find("#btn-cancel-tecido-projeto").show();

                    $('.dataTables_scrollFootInner').find('#tecido_total_exibicao').html(data.response.total_custo_total);
                    
                    retornarDescricaoTecido(form_modal_composicao_tecido);
                    calculosTecido(form_modal_composicao_tecido);
                }
            });
        }else{
            message("Atenção", "Há um tecido sendo editado.");
        }
    }

    function excluirTecidoNoProjeto(obj, $id_tecido, id_projeto){
        form_modal_composicao_tecido = $(document).find("#form_filter_projeto_tecido");
        id_revisor = form_modal_composicao_tecido.find("#id_revisor").val();
        limparMesagemErroComposicao(form_modal_composicao_tecido);
        $.ajax({
            url: '{{ Route("lancamento_projeto.tecido.deletar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_tecido: $id_tecido, 
                id_projeto: id_projeto,
                id_revisor: id_revisor
            },
            success: function(data){
                table_tecido.row(obj).remove().draw();
                $('.dataTables_scrollFootInner').find('#tecido_total_exibicao').html(data.response.total_exibicao);
                carregarTabelaProdutos(id_projeto);
            },
            error: function(data){
                if(data.responseJSON.message != ''){
                    message("Atenção", data.responseJSON.message);
                }
            }
        });
    }

    function validarDescricaoTecido(form_modal_composicao_tecido){
        data_form_modal_composicao_tecido = form_modal_composicao_tecido.serialize();

        $.ajax({
            url: '{{ route('produto.tecido.validar_descricao')}}',
            data: data_form_modal_composicao_tecido,
            method: 'POST',
            success: function(callback){
            },
            error: function(callback){
                message("Atenção", 'Nenhum tecido encontrado');
                if(form_modal_composicao_tecido.find('#tecido_descricao').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_composicao_tecido.find('#tecido_descricao').focus();
                }
            }
        });
    }

    function calculosTecido(form_modal_composicao_tecido){
        limparMesagemErroComposicao(form_modal_composicao_tecido);     

        preco_unitario = form_modal_composicao_tecido.find("#tecido_preco_unitario").val();
        consumo = form_modal_composicao_tecido.find("#tecido_consumo").val();
        quantidade = form_modal_composicao_tecido.find("#tecido_quantidade").val();

        tecido_consumo_total = Math.round(((consumo.replace(".","").replace(",", ".")) * (quantidade.replace(".","").replace(",", "."))) * 1000) / 1000;
        tecido_valor_total = Math.round(((preco_unitario.replace(".","").replace(",", ".")) * tecido_consumo_total) * 100) / 100;
        
        tecido_consumo_total = tecido_consumo_total.toFixed(3);
        tecido_valor_total = tecido_valor_total.toFixed(2);

        form_modal_composicao_tecido.find('#tecido_consumo_total').val(numberToReal(tecido_consumo_total));
        form_modal_composicao_tecido.find('#tecido_valor_total').val(numberToReal(tecido_valor_total));

    }

    function limparMesagemErroComposicao(form_modal_composicao){   
        form_modal_composicao.find('.error-message').remove();
        form_modal_composicao.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroComposicao(json_error, form_modal_composicao){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsComposicao(form_modal_composicao, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsComposicao(form_modal_composicao, input, message){
        if(input.localeCompare('tecido_codigo') == 0){
            var $input = $(form_modal_composicao).find("#bt-search-tecido");
            $(form_modal_composicao).find("input[name='tecido_codigo']").addClass('error-input');
        }else if(input.localeCompare('insumo_codigo') == 0){
            var $input = $(form_modal_composicao).find("#bt-search-insumo");
            $(form_modal_composicao).find("input[name='insumo_codigo']").addClass('error-input');
        }else{
            var $input = $(form_modal_composicao).find("input[name='"+input+"'], select[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function limparCamposTecido(){
        form_modal_composicao_tecido = $(document).find("#form_filter_projeto_tecido");

        form_modal_composicao_tecido.find("#id_tecido").val('');
        form_modal_composicao_tecido.find("#tecido_codigo").val('');
        form_modal_composicao_tecido.find("#tecido_descricao").val('');
        form_modal_composicao_tecido.find("#tecido_consumo").val('');
        form_modal_composicao_tecido.find("#tecido_preco_unitario").val('');
        form_modal_composicao_tecido.find("#tecido_valor_total").val('');
        form_modal_composicao_tecido.find("#tecido_consumo_total").val('');
        form_modal_composicao_tecido.find("#servico_tecido").val(0);
        form_modal_composicao_tecido.find("#servico_fator_conversao").val("1,00");
        form_modal_composicao_tecido.find("#servico_fator_conversao").attr("disabled", "disabled");

        table_tecido_estabelecimento.clear().draw();
    }

    function optionsAutoCompleteInsumo(form_modal_composicao_insumo){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.estabelecimento = form_modal_composicao_insumo.find("#insumo_estabelecimento").val();
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
                    form_modal_composicao_insumo.find("#insumo_codigo").val('');
                    form_modal_composicao_insumo.find("#insumo_descricao").val('');
                    form_modal_composicao_insumo.find("#insumo_consumo").val('');
                    form_modal_composicao_insumo.find("#insumo_preco_unitario").val('');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_modal_composicao_insumo.find("#insumo_descricao").val(ui.item.label);
                form_modal_composicao_insumo.find("#insumo_codigo").val(ui.item.value);
                form_modal_composicao_insumo.find("#insumo_preco_unitario").val(ui.item.preco);
                return false;
            }
        };
    }

    function showModalInsumo(url, campo, condicao, title){
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                campo: campo,
                condicao: condicao
            },
            success: function(body){
                createModal("insumo_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_filters_produtos_busca.on('draw', function () {
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

    function retornarDescricaoInsumo(form_modal_composicao_insumo){
        data_form_modal_composicao_insumo = form_modal_composicao_insumo.serialize();
        limparMesagemErroComposicao(form_modal_composicao_insumo);
        $.ajax({
            url: '{{ route('produto.insumo.insumo_descricao')}}',
            data: data_form_modal_composicao_insumo,
            method: 'POST',
            async: false,
            success: function(callback){
                var produto = callback;
                table_insumo_estabelecimento.clear().draw();
                if(produto.estoque != ''){
                    dadosRetornoInsumoDescricao(produto, form_modal_composicao_insumo);

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
                    form_modal_composicao_insumo.find('#insumo_codigo').val('');
                    form_modal_composicao_insumo.find('#insumo_descricao').val('');
                }
                
            },
            error: function(callback){
                if(form_modal_composicao_insumo.find('#insumo_codigo').val() != ''){
                    var dados = callback.responseJSON;
                    limparMesagemErroComposicao(form_modal_composicao_insumo);
                    mensagemErroComposicao(dados, form_modal_composicao_insumo);
                }
            }
        });
    }
    function dadosRetornoInsumoDescricao(produto, form_modal_composicao_insumo){
        form_modal_composicao_insumo.find('#insumo_descricao').val(produto.nome);
        form_modal_composicao_insumo.find('#insumo_preco_unitario').val(produto.preco);
        form_modal_composicao_insumo.find('#insumo_unidade').val(produto.unidade);
        calculosInsumo(form_modal_composicao_insumo);
    }

    function salvarInsumoNoProjeto(){
        form_modal_composicao_insumo = $(document).find("#form_filter_projeto_insumo");
        if (form_modal_composicao_insumo.find("#id_insumo").val() == ''){
            codigo = form_modal_composicao_insumo.find("#insumo_codigo").val();
            descricao = form_modal_composicao_insumo.find("#insumo_descricao").val();
            consumo = form_modal_composicao_insumo.find("#insumo_consumo").val();
            preco_unitario = form_modal_composicao_insumo.find("#insumo_preco_unitario").val();
            consumo_total = form_modal_composicao_insumo.find("#insumo_consumo_total").val();
            quantidade = form_modal_composicao_insumo.find("#insumo_quantidade").val();
            custo_total = form_modal_composicao_insumo.find("#insumo_valor_total").val();
            id_projeto = form_modal_composicao_insumo.find("#id_projeto").val();
            id_produto = form_modal_composicao_insumo.find("#id_produto").val();
            id_revisor = form_modal_composicao_insumo.find("#id_revisor").val();

            limparMesagemErroComposicao(form_modal_composicao_insumo);
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
                    id_produto: id_produto,
                    id_revisor: id_revisor
                },
                success: function(data) {
                    var field = [
                        data.response.codigo,
                        ajusteTamanhoTable(data.response.descricao),
                        data.response.preco_unitario,
                        data.response.consumo_total,
                        data.response.total_custo,
                        createBtEditarInsumo(data.response.id, data.response.projeto_id),
                        createBtExcluirInsumo(data.response.id, data.response.projeto_id)
                    ];
                    table_insumo.row.add(field).draw();
                    carregarTabelaProdutos(id_projeto);
                    form_modal_composicao_insumo.find("#insumo_codigo").focus();

                    $('.dataTables_scrollFootInner').find('#insumo_total_exibicao').html(data.response.insumo_total);

                    limparCamposInsumo();
                },
                error: function(data){
                    var dados = data.responseJSON;
                    limparMesagemErroComposicao(form_modal_composicao_insumo);
                    mensagemErroComposicao(dados, form_modal_composicao_insumo);
                }
            });
        }else{
            salvarEdicaoInsumoNoProjeto();
        }
    }

    function salvarEdicaoInsumoNoProjeto(){
        form_modal_composicao_insumo = $(document).find("#form_filter_projeto_insumo");

        codigo = form_modal_composicao_insumo.find("#insumo_codigo").val();
        descricao = form_modal_composicao_insumo.find("#insumo_descricao").val();
        consumo = form_modal_composicao_insumo.find("#insumo_consumo").val();
        preco_unitario = form_modal_composicao_insumo.find("#insumo_preco_unitario").val();
        consumo_total = form_modal_composicao_insumo.find("#insumo_consumo_total").val();
        quantidade = form_modal_composicao_insumo.find("#insumo_quantidade").val();
        custo_total = form_modal_composicao_insumo.find("#insumo_valor_total").val();
        id_projeto = form_modal_composicao_insumo.find("#id_projeto").val();
        id_insumo = form_modal_composicao_insumo.find("#id_insumo").val();
        id_produto = form_modal_composicao_insumo.find("#id_produto").val();
        id_revisor = form_modal_composicao_insumo.find("#id_revisor").val();

        limparMesagemErroComposicao(form_modal_composicao_insumo);
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
                id_produto: id_produto,
                id_revisor: id_revisor
            },
            success: function(data) {
                var field = [
                    data.response.codigo,
                    ajusteTamanhoTable(data.response.descricao),
                    data.response.preco_unitario,
                    data.response.consumo_total,
                    data.response.total_custo,
                    createBtEditarInsumo(data.response.id, data.response.projeto_id),
                    createBtExcluirInsumo(data.response.id, data.response.projeto_id)
                ];
                table_insumo.row.add(field).draw();
                carregarTabelaProdutos(id_projeto);
                $(document).find("#form_filter_projeto_insumo").find("#id_insumo").val('');
                $(document).find("#form_filter_projeto_insumo").find("#btn-create-insumo-projeto").html('Inserir insumo/acessório');
                $(document).find("#form_filter_projeto_insumo").find("#btn-cancel-insumo-projeto").hide();
                $('.dataTables_scrollFootInner').find('#insumo_total_exibicao').html(data.response.insumo_total);

                form_modal_composicao_insumo.find("#insumo_codigo").focus();

                limparCamposInsumo();
            },
            error: function(data){
                var dados = data.responseJSON;
                limparMesagemErroComposicao(form_modal_composicao_insumo);
                mensagemErroComposicao(dados, form_modal_composicao_insumo);
            }
        });
    }

    function cancelarEdicaoInsumo(){
        form_modal_composicao_insumo = $(document).find("#form_filter_projeto_insumo");
        data_form = form_modal_composicao_insumo.serialize();
        limparMesagemErroComposicao(form_modal_composicao_insumo);
        $.ajax({
            url: '{{ Route("lancamento_projeto.insumo.cancelar_edicao") }}',
            type: 'POST',
            data: data_form,
            success: function(data){
                var field = [
                    data.response.codigo, 
                    ajusteTamanhoTable(data.response.descricao),
                    data.response.preco_unitario,
                    data.response.consumo_total,
                    data.response.total_custo,
                    createBtEditarInsumo(data.response.id, data.response.projeto_id),
                    createBtExcluirInsumo(data.response.id, data.response.projeto_id)
                ];

                @if(!empty($dados['revisor']))
                if(data.response.validar === true){
                    table_insumo.row.add(field).draw();
                }else{
                    table_insumo.row.add(field).draw().nodes().to$().addClass('error-tr');
                }
                @else 
                table_insumo.row.add(field).draw();
                @endif

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
        form_modal_composicao_insumo = $(document).find("#form_filter_projeto_insumo");

        id_insumo = form_modal_composicao_insumo.find("#id_insumo").val();
    
        limparMesagemErroComposicao(form_modal_composicao_insumo);
        if(id_insumo == ''){
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

                    form_modal_composicao_insumo.find("#id_insumo").val(data.response.id);
                    form_modal_composicao_insumo.find("#insumo_codigo").val(data.response.codigo);
                    form_modal_composicao_insumo.find("#insumo_consumo").val(data.response.consumo_total);
                    form_modal_composicao_insumo.find("#insumo_valor_total").val(data.response.custo_total);

                    form_modal_composicao_insumo.find("#btn-create-insumo-projeto").html('Editar insumo/acessório');
                    form_modal_composicao_insumo.find("#btn-cancel-insumo-projeto").show();

                    $('.dataTables_scrollFootInner').find('#insumo_total_exibicao').html(data.response.total_custo_total);

                    retornarDescricaoInsumo(form_modal_composicao_insumo);
                    calculosInsumo(form_modal_composicao_insumo);  
                }
            });
        }else{
            message("Atenção", "Há um insumo sendo editado.");
        }
    }

    function excluirInsumoNoProjeto(obj, $id_insumo, id_projeto){
        id_revisor = form_modal_composicao_insumo.find("#id_revisor").val();
        limparMesagemErroComposicao(form_modal_composicao_insumo);
        $.ajax({
            url: '{{ Route("lancamento_projeto.insumo.deletar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_insumo: $id_insumo, 
                id_projeto: id_projeto,
                id_revisor: id_revisor
            },
            success: function(data){
                table_insumo.row(obj).remove().draw();
                $('.dataTables_scrollFootInner').find('#insumo_total_exibicao').html(data.response.total_exibicao);
                carregarTabelaProdutos(id_projeto);
            }
        });
    }

    function verificarInsumo($value){
        var htm = "";
        if($value.validar === true){
            html = $value.codigo;
        }else{
            html = "<div>"+
                        "<div data-toggle=\"popover\" data-placement=\"top\" data-title=\"Detalhes\" data-content=\"<p>A unidade padrão do insumo está incorreta. Favor verificar com o setor responsável.\"></a>"+
                            "<a href=\"#\" class=\"btn-informacao\"></a>"+
                            $value.codigo+
                        "</div>"+
                    "</div>";
        }
        return html;
    }

    function limparCamposInsumo(){
        form_modal_composicao_insumo = $(document).find("#form_filter_projeto_insumo");

        form_modal_composicao_insumo.find("id_insumo").val('');
        form_modal_composicao_insumo.find("#insumo_codigo").val('');
        form_modal_composicao_insumo.find("#insumo_descricao").val('');
        form_modal_composicao_insumo.find("#insumo_consumo").val('');
        form_modal_composicao_insumo.find("#insumo_preco_unitario").val('');
        form_modal_composicao_insumo.find("#insumo_unidade").val('');
        form_modal_composicao_insumo.find("#insumo_valor_total").val('');
        form_modal_composicao_insumo.find("#insumo_consumo_total").val('');

        table_insumo_estabelecimento.clear().draw();
    }

    function calculosInsumo(form_modal_composicao_insumo){
        limparMesagemErroComposicao(form_modal_composicao_insumo);

        preco_unitario = form_modal_composicao_insumo.find("#insumo_preco_unitario").val();
        quantidade = form_modal_composicao_insumo.find("#insumo_quantidade").val();
        consumo_total = form_modal_composicao_insumo.find("#insumo_consumo").val();

        insumo_valor_total = Math.round(((consumo_total.replace(".","").replace(",", ".")) * (preco_unitario.replace(".","").replace(",", "."))) * 100) / 100;
        insumo_valor_total  = insumo_valor_total.toFixed(2);

        form_modal_composicao_insumo.find('#insumo_valor_total').val(numberToReal(insumo_valor_total));
    }

    function optionsAutoCompleteServico(form_modal_composicao_servico){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request.name = form_modal_composicao_servico.find("#servico_descricao").val();
                request._token = "{{ csrf_token() }}";
                @if($licitacao)
                    request.campo = "marca";
                    request.condicao = "MAO DE OBRA LICITACAO";
                @else
                    request.campo = "linha";
                    request.condicao = "MAO DE OBRA";
                @endif
                $.post("{{ route('produto.autocompletelimitacao') }}", request, response);
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
                    form_modal_composicao_servico.find("#servico_descricao").focus();
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_modal_composicao_servico.find("#servico_descricao").val(ui.item.label);
                form_modal_composicao_servico.find("#servico_codigo").val(ui.item.value);
                form_modal_composicao_servico.find("#servico_custo_unitario").val(ui.item.preco);
                retornarDescricaoServico(form_modal_composicao_servico);
                if(form_modal_composicao_servico.find("#custo_unitario").val() != ''){
                    calculosServico(form_modal_composicao_servico);
                }
                return false;
            }
        };
    }
    function showModalServico(url, campo, condicao, title){
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                campo: campo,
                condicao: condicao
            },
            success: function(body){
                createModal("servico_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_filters_produtos_busca.on('draw', function () {
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

    function retornarDescricaoServico(form_modal_composicao_servico){
        form_modal_composicao_servico = $(document).find("#form_filter_projeto_servico");
        data_form_modal_composicao_servico = form_modal_composicao_servico.serialize();
        $.ajax({
            url: '{{ route('produto.insumo.servico_descricao')}}',
            data: data_form_modal_composicao_servico,
            method: 'POST',
            async: false,
            success: function(callback){
                var produto = callback;
                dadosRetornoServicoDescricao(produto, form_modal_composicao_servico);
                
            },
            error: function(callback){
                if(form_modal_composicao_servico.find('#servico_codigo').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_composicao_servico.find('#servico_codigo').focus();
                }
            }
        });
    }
    function dadosRetornoServicoDescricao(produto, form_modal_composicao_servico){
        form_modal_composicao_servico.find('#servico_descricao').val(produto.nome);
        form_modal_composicao_servico.find('#servico_custo_unitario').val(produto.preco);
        calculosServico(form_modal_composicao_servico);
    }

    function salvarServicoNoProjeto(){
        form_modal_composicao_servico = $(document).find("#form_filter_projeto_servico");
        if (form_modal_composicao_servico.find("#id_servico").val() == ''){
            faccao = form_modal_composicao_servico.find("#codigo_faccao").val();
            id_projeto = form_modal_composicao_servico.find("#id_projeto").val();
            tipo_de_servico = form_modal_composicao_servico.find("#servico_codigo").val();
            custo_unitario = form_modal_composicao_servico.find("#servico_custo_unitario").val();
            quantidade = form_modal_composicao_servico.find("#servico_quantidade").val();
            custo_total = form_modal_composicao_servico.find("#servico_custo_total").val();
            id_produto = form_modal_composicao_servico.find("#id_produto").val();

            servico_descricao = form_modal_composicao_servico.find("#servico_descricao").val();
            servico_codigo = form_modal_composicao_servico.find("#servico_codigo").val();

            servico_tecido = form_modal_composicao_servico.find("#servico_tecido").val();
            servico_codigo_produto_acabado = form_modal_composicao_servico.find("#servico_codigo_produto_acabado").val();

            id_revisor = form_modal_composicao_servico.find("#id_revisor").val();

            limparMesagemErroAdd(form_modal_composicao_servico);
            $.ajax({
                url: '{{ Route("lancamento_projeto.servico.adicionar") }}',
                type: 'POST',
                data: {
                    _token: '{{csrf_token()}}',
                    faccao : faccao,
                    tipo_de_servico : tipo_de_servico,
                    custo_unitario : custo_unitario,
                    quantidade : quantidade,
                    custo_total : custo_total,
                    id_projeto : id_projeto,
                    id_produto: id_produto,
                    servico_descricao : servico_descricao,
                    servico_codigo : servico_codigo,

                    servico_tecido : servico_tecido,
                    servico_codigo_produto_acabado : servico_codigo_produto_acabado,
                    id_revisor: id_revisor,
                },
                success: function(data) {
                    var field = [
                        @if(!empty($dados['revisor']))
                        data.response.cnpj,
                        ajusteTamanhoTable(data.response.faccao),
                        @endif
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
                    carregarTabelaProdutos(id_projeto);
                    chamadaPopover();

                    $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.total);
                    limparCamposServico();

                    chamadaPopover();
                    form_modal_composicao_servico.find("#servico_codigo").focus();
                },
                error: function(data){
                    var dados = data.responseJSON;
                    mensagemErroAdd(dados, form_modal_composicao_servico);
                }
            });
        }else{
            salvarEdicaoServicoNoProjeto();
        }
    }

    function salvarEdicaoServicoNoProjeto(){
        form_modal_composicao_servico = $(document).find("#form_filter_projeto_servico");
        faccao = form_modal_composicao_servico.find("#codigo_faccao").val();
        id_servico = form_modal_composicao_servico.find("#id_servico").val(); 
        id_projeto = form_modal_composicao_servico.find("#id_projeto").val();
        tipo_de_servico = form_modal_composicao_servico.find("#servico_codigo").val();
        custo_unitario = form_modal_composicao_servico.find("#servico_custo_unitario").val();
        quantidade = form_modal_composicao_servico.find("#servico_quantidade").val();
        custo_total = form_modal_composicao_servico.find("#servico_custo_total").val();
        id_produto = form_modal_composicao_servico.find("#id_produto").val();

        servico_tecido = form_modal_composicao_servico.find("#servico_tecido").val();
        servico_codigo_produto_acabado = form_modal_composicao_servico.find("#servico_codigo_produto_acabado").val();

        id_revisor = form_modal_composicao_servico.find("#id_revisor").val();

        limparMesagemErroAdd(form_modal_composicao_servico);
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
                id_produto : id_produto,
                faccao : faccao,

                servico_tecido : servico_tecido,
                servico_codigo_produto_acabado : servico_codigo_produto_acabado,

                id_revisor: id_revisor
            },
            success: function(data) {
                var field = [
                    @if(!empty($dados['revisor']))
                    data.response.cnpj,
                    ajusteTamanhoTable(data.response.faccao),
                    @endif
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
                carregarTabelaProdutos(id_projeto);
                chamadaPopover();
                $(document).find("#form_filter_projeto_servico").find("#btn-create-servico-projeto").html("inserir serviço");
                $(document).find("#form_filter_projeto_servico").find("#btn-cancel-servico-projeto").hide();
                $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.total);
                form_modal_composicao_servico.find("#servico_quantidade").val(data.response.quantidade_original);
                limparCamposServico();
                form_modal_composicao_servico.find("#servico_codigo").focus();
            },
            error: function(data){
                var dados = data.responseJSON;
                mensagemErroAdd(dados, form_modal_composicao_servico);
            }
        });
    }

    function cancelarEdicaoServico(){
        form_modal_composicao_servico = $(document).find("#form_filter_projeto_servico");
        data_form = form_modal_composicao_servico.serialize();
        limparMesagemErroAdd(form_modal_composicao_servico);
        $.ajax({
            url: '{{ Route("lancamento_projeto.faccao.cancelar_edicao") }}',
            type: 'POST',
            data: data_form,
            success: function(data){
                var field = [
                    @if(!empty($dados['revisor']))
                    data.response.cnpj,
                    ajusteTamanhoTable(data.response.faccao),
                    @endif
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
                form_modal_composicao_servico.find("#servico_quantidade").val(data.response.quantidade_original);
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
        form_modal_composicao_servico = $(document).find("#form_filter_projeto_servico");

        id_servico = form_modal_composicao_servico.find("#id_servico").val();
    
        limparMesagemErroAdd(form_modal_composicao_servico);
        if(id_servico == ''){
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

                    @if(!empty($dados['revisor']))
                    form_modal_composicao_servico.find("#faccao").val(data.response.faccao);
                    form_modal_composicao_servico.find("#codigo_faccao").val(data.response.codigo_faccao);
                    @endif

                    form_modal_composicao_servico.find("#id_servico").val(data.response.id);
                    form_modal_composicao_servico.find("#servico_codigo").val(data.response.tipo_de_servico);
                    form_modal_composicao_servico.find("#servico_custo_unitario").val(data.response.custo_unitario);
                    form_modal_composicao_servico.find("#servico_custo_total").val(data.response.custo_total);
                    form_modal_composicao_servico.find("#servico_quantidade").val(data.response.quantidade);

                    form_modal_composicao_servico.find("#btn-create-servico-projeto").html("editar serviço");
                    form_modal_composicao_servico.find("#btn-cancel-servico-projeto").show();

                    $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.total);
                    retornarDescricaoServico(form_modal_composicao_servico);
                }
            });
        }else{
            message("Atenção", "Há um serviço sendo editado.");
        }
    }

    function excluirServicoNoProjeto(obj, $id_servico, id_projeto){
        id_revisor = form_modal_composicao_servico.find("#id_revisor").val();
        $.ajax({
            url: '{{ Route("lancamento_projeto.servico.deletar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_servico: $id_servico, 
                id_projeto: id_projeto,
                id_revisor: id_revisor
            },
            success: function(data){
                table_servico.row(obj).remove().draw();
                $('.dataTables_scrollFootInner').find('#servico_total_exibicao').html(data.response.total_exibicao);
                carregarTabelaProdutos(id_projeto);
            }
        });
    }

    function limparCamposServico(){
        form_modal_composicao_servico = $(document).find("#form_filter_projeto_servico");
        @if(!empty($dados['revisor']))
        form_modal_composicao_servico.find("#faccao").val('');
        form_modal_composicao_servico.find("#codigo_faccao").val('');
        @endif
        form_modal_composicao_servico.find("#id_servico").val('');
        form_modal_composicao_servico.find("#servico_codigo").val('');
        form_modal_composicao_servico.find("#servico_descricao").val('');
        form_modal_composicao_servico.find("#servico_custo_unitario").val('');
        form_modal_composicao_servico.find("#servico_custo_total").val('');
    }

    function calculosServico(form_modal_composicao_servico){
        limparMesagemErroAdd(form_modal_composicao_servico);

        quantidade = form_modal_composicao_servico.find("#servico_quantidade").val();
        preco = form_modal_composicao_servico.find("#servico_custo_unitario").val();

        servico_custo_total = Math.round(((quantidade.replace(".","").replace(",", ".")) * (preco.replace(".","").replace(",", "."))) * 100) / 100;

        servico_custo_total = servico_custo_total.toFixed(2);

        form_modal_composicao_servico.find('#servico_custo_total').val(numberToReal(servico_custo_total));
    }

    function getPrecoServico(){
        form_modal_composicao_servico = $(document).find('#form_filter_projeto_servico');

        tipo_de_servico = form_modal_composicao_servico.find("#tipo_de_servico").val();
        quantidade = form_modal_composicao_servico.find("#servico_quantidade").val();

        $.ajax({
            url: '{{ route('faccao_tipo_de_servico.get_preco')}}',
            data: {
                _token: '{{ csrf_token() }}',
                tipo_de_servico: tipo_de_servico,
                quantidade: quantidade
            },
            method: 'POST',
            success: function(data){
                form_modal_composicao_servico.find("#servico_custo_unitario").val(data.response.preco);
                form_modal_composicao_servico.find("#servico_custo_total").val(data.response.custo_total);
            },
            error: function(callback){

            }
        });
    }

    function carregarTabelaProdutos(id_projeto){
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
                        data.response.produtos[index].preco_venda,
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
                        if(data.response.produtos[index].produto_com_faccao === 1){
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
                return false;
            }
        };
    }

    function showModalFaccaoFixo(url, title){
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
                                returnDadosFaccaoFixo($(this));
                            });
                        });
                    });
                }
            });
        }else{
            form_modal_composicao_servico = $(document).find("#form_filter_projeto_servico");
            tipo_de_servico = form_modal_composicao_servico.find("#tipo_de_servico").val();
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
                                returnDadosFaccaoFixo($(this));
                            });
                        });
                    });
                }
            });
        }
    }
    function returnDadosFaccaoFixo($this){
        if($this.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#faccao_search_show").modal("hide");
        $(document).find('#form_filter_projeto_servico').find("#faccao").val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
        $(document).find('#form_filter_projeto_servico').find("#codigo_faccao").val($this.find("td").eq(0).text());
    }
    function numberToReal(valor) {
        var numero = valor.split('.');
        numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
        return numero.join(',');
    }
    function anexarArquivo(){
        var form_modal_composicao_arquivo = $(document).find('#form_arquivo');
        var formData = new FormData($(document).find('#form_arquivo')[0]);
        limparMesagemErroComposicao(form_modal_composicao_arquivo);
        $.ajax({
            url: '{{ Route("lancamento_projeto.produto.arquivo.adicionar") }}',
            dataType: 'json',
            data: formData,
            method: 'POST',
            processData: false,
            contentType: false,
            success: function(data){
                limparCamposArquivo();
                var field = [
                    linkArquivo(data.response),
                    data.response.tipo,
                    createBtExcluirArquivo(data.response),
                ];
                table_arquivo.row.add(field).draw();
            },
            error: function(data){
                var dados = data.responseJSON;
                limparMesagemErroComposicao(form_modal_composicao_arquivo);
                mensagemErroComposicao(dados, form_modal_composicao_arquivo);
            }
        });
    }

    function linkArquivo($value){
        var link = "<a href=\""+$value.arquivo+"\" target=\"_blank\">"+$value.nome+"</a>";

        return link;
    }

    function createBtExcluirArquivo($value){
        var button = "<a href=\"#\" class=\"bt-delete\" title='Excluir' data-id=\""+$value.id+"\" onclick=\"excluirArquivo($(this).parents('tr'), $(this))\"></a>";

        return button;
    }

    function limparCamposArquivo(){
        form_modal_composicao_arquivo = $(document).find('#form_arquivo');

        form_modal_composicao_arquivo.find("#arquivo").val('');
        form_modal_composicao_arquivo.find("#tipo_arquivo").val('');
    }

    function excluirArquivo(obj, $this){
        var $id_arquivo = $this.data("id");
        $.ajax({
            url: '{{ Route("lancamento_projeto.produto.arquivo.deletar") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: $id_arquivo, 
            },
            success: function(data){
                table_arquivo.row(obj).remove().draw();
            }
        });
    }
</script>
@endsection