@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_geracao_remessa" id="form_geracao_remessa" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id_faccao', $dados['id_faccao'], ['id' => 'id_faccao']) !!}
    {!! Form::hidden('codigo_produto', $dados['codigo_produto'], ['id' => 'codigo_produto']) !!}
    {!! Form::hidden('id_projeto', $dados['id_projeto'], ['id' => 'id_projeto']) !!}
    {!! Form::hidden('pedido', $dados['pedido'], ['id' => 'pedido']) !!}
    <div class="form-row">
        <div class="form-group col-sm-12">
            {!! Form::label('faccao', 'Facção', ['id' => 'faccao']) !!}
            {!! Form::text('faccao', $dados['faccao'], ['id' => 'faccao', 'class' => 'form-control', 'disabled']) !!}
        </div>
    </div>
    <div class="form-row">
        @if(empty(decrypt($dados['id_projeto'])))
        <div class="form-group col-sm-6 mt-3">
            {!! Form::checkbox('todos_produtos', 'todos_produtos', false, ['id' => 'todos_produtos', 'onclick' => 'checkboxDesativar($(this))']) !!}
            {{ Form::label('todos_produtos', 'Todos Produtos do Pedido',['class' => 'form-check-label']) }}
        </div>
        <div class="form-group col-sm-6 mt-3">
            {!! Form::checkbox('todos_produtos', 'todos_produtos_projetos', false, ['id' => 'todos_produtos_projetos', 'onclick' => 'checkboxDesativar($(this))']) !!}
            {{ Form::label('todos_produtos_projetos', 'Todos Produtos de todos Pedidos', ['class' => 'form-check-label']) }}
        </div>
        @else
        <div class="form-group col-sm-6 mt-3">
            {!! Form::checkbox('todos_produtos', 'todos_produtos', false, ['id' => 'todos_produtos', 'onclick' => 'checkboxDesativar($(this))']) !!}
            {{ Form::label('todos_produtos', 'Todos Produtos do Projeto',['class' => 'form-check-label']) }}
        </div>
        <div class="form-group col-sm-6 mt-3">
            {!! Form::checkbox('todos_produtos', 'todos_produtos_projetos', false, ['id' => 'todos_produtos_projetos', 'onclick' => 'checkboxDesativar($(this))']) !!}
            {{ Form::label('todos_produtos_projetos', 'Todos Produtos de todos Projetos', ['class' => 'form-check-label']) }}
        </div>
        @endif
    </div>
    <div class="content-dialog-table">
        <div id="conteudo_geracao_remessa">
            @foreach($itens as $item)
                <hr>
                <h6><b>Projeto nº</b> {{ $item['numero_projeto'] }} -  <b>Nome do Projeto:</b> {{ $item['nome_projeto'] }} - <b>Cliente:</b> {{ $item['cliente'] }} - <b>Estabelecimento:</b> {{ $item['estabelecimento'] }}</h6>
                <br>
                <div class="form-row">
                    <div class="form-group col-sm-12">
                        {{ Form::label('transportadora_nome-'.$item['numero_projeto'], 'Transportadora para o Envio Facção', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        <div class="input-group" id="transporadora_group">
                            {{ Form::text('transportadora_nome-'.$item['numero_projeto'], '', ['id' => 'transportadora_nome-'.$item['numero_projeto'], 'class' => 'form-control essencial input-label', 'data-estabelecimento' => $item['codigo_estabelecimento'], 'onkeyup' => 'optionsTransportadora($(this))']) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-transportadora-{{ $item['numero_projeto'] }}" data-estabelecimento="{{ $item['codigo_estabelecimento'] }}" data-nome_campo="transportadora_nome-{{ $item['numero_projeto'] }}" onclick="modalTransportador($(this))"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
                        </div>
                    </div>
                </div>
                <div class="form-row" id="transportadora_rondonia-{{ $item['numero_projeto'] }}" name="transportadora_rondonia-{{ $item['numero_projeto'] }}" style="display:none">
                    <div class="form-group col-sm-12">
                        {{ Form::label('transportadora_nome_rondonia-'.$item['numero_projeto'], 'Transportadora Transferência Rondonia', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        <div class="input-group" id="transporadora_group">
                            {{ Form::text('transportadora_nome_rondonia-'.$item['numero_projeto'], '', ['id' => 'transportadora_nome_rondonia-'.$item['numero_projeto'], 'class' => 'form-control essencial input-label', 'data-estabelecimento' => $item['codigo_estabelecimento'], 'onkeyup' => 'optionsTransportadora($(this))']) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-transportadora_rondonia-{{ $item['numero_projeto'] }}" data-estabelecimento="{{ $item['codigo_estabelecimento'] }}" data-nome_campo="transportadora_nome_rondonia-{{ $item['numero_projeto'] }}" onclick="modalTransportador($(this))"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
                        </div>
                    </div>
                </div>
                <div class="form-row" id="transportadora_tocantins-{{ $item['numero_projeto'] }}" name="transportadora_tocantins-{{ $item['numero_projeto'] }}" style="display:none">
                    <div class="form-group col-sm-12">
                        {{ Form::label('transportadora_nome_tocantins-'.$item['numero_projeto'], 'Transportadora Transferência Tocantins', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        <div class="input-group" id="transporadora_group">
                            {{ Form::text('transportadora_nome_tocantins-'.$item['numero_projeto'], '', ['id' => 'transportadora_nome_tocantins-'.$item['numero_projeto'], 'class' => 'form-control essencial input-label', 'data-estabelecimento' => $item['codigo_estabelecimento'], 'onkeyup' => 'optionsTransportadora($(this))']) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-transportadora_tocantis-{{ $item['numero_projeto'] }}" data-estabelecimento="{{ $item['codigo_estabelecimento'] }}" data-nome_campo="transportadora_nome_tocantins-{{ $item['numero_projeto'] }}" onclick="modalTransportador($(this))"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
                        </div>
                    </div>
                </div>
                <div class="form-row" id="transportadora_matriz-{{ $item['numero_projeto'] }}" name="transportadora_matriz-{{ $item['numero_projeto'] }}" style="display:none">
                    <div class="form-group col-sm-12">
                        {{ Form::label('transportadora_nome_matriz-'.$item['numero_projeto'], 'Transportadora Transferência Matriz', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        <div class="input-group" id="transporadora_group">
                            {{ Form::text('transportadora_nome_matriz-'.$item['numero_projeto'], '', ['id' => 'transportadora_nome_matriz-'.$item['numero_projeto'], 'class' => 'form-control essencial input-label', 'data-estabelecimento' => $item['codigo_estabelecimento'], 'onkeyup' => 'optionsTransportadora($(this))']) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-transportadora_matriz-{{ $item['numero_projeto'] }}" data-estabelecimento="{{ $item['codigo_estabelecimento'] }}" data-nome_campo="transportadora_nome_matriz-{{ $item['numero_projeto'] }}" onclick="modalTransportador($(this))"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
                        </div>
                    </div>
                </div>
                <div class="form-row" id="transportadora_almirante_2-{{ $item['numero_projeto'] }}" name="transportadora_almirante_2-{{ $item['numero_projeto'] }}" style="display:none">
                    <div class="form-group col-sm-12">
                        {{ Form::label('transportadora_nome_almirante_2-'.$item['numero_projeto'], 'Transportadora Transferência Almirante 2', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                        <div class="input-group" id="transporadora_group">
                            {{ Form::text('transportadora_nome_almirante_2-'.$item['numero_projeto'], '', ['id' => 'transportadora_nome_almirante_2-'.$item['numero_projeto'], 'class' => 'form-control essencial input-label', 'data-estabelecimento' => $item['codigo_estabelecimento'], 'onkeyup' => 'optionsTransportadora($(this))']) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-transportadora_almirante_2-{{ $item['numero_projeto'] }}" data-estabelecimento="{{ $item['codigo_estabelecimento'] }}" data-nome_campo="transportadora_nome_almirante_2-{{ $item['numero_projeto'] }}" onclick="modalTransportador($(this))"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
                        </div>
                    </div>
                </div>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th class="tb_number">Necessidade</th>
                            <th>Qtde a enviar</th>
                            <th class="tb_number">Est. Estab. 05 - Matriz</th>
                            <th>Est. Transf.</th>
                            <th>Qtde. Transf.</th>
                            <th class="tb_number">Est. Estab. Transf.</th>
                            <th class="tb_number">Est. Total</th>
                            <th class="tb_number">Compras</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="width: 100px;">{{ $item['codigo_produto'] }}</td>
                            <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $item['produto_a_enviar'] }}'>{{ $item['produto_a_enviar'] }}</div></div></td>
                            <td class="tb_number" style="width: 150px;">{{ $item['qtde_a_enviar'] }}</td>
                            <td style="width: 150px;"><input style="height: inherit;" id="qtde_a_enviar-{{ $item['codigo_produto'] }}-{{ $item['id_produto'] }}" name="qtde_a_enviar-{{ $item['codigo_produto'] }}-{{ $item['id_produto'] }}" type="text" class="form-control text-right moeda" value="{{ $item['qtde_a_enviar'] }}" maxlength="10"  data-projeto="{{ $item['numero_projeto'] }}" data-pedido="{{ $item['pedido'] }}" data-codigo_produto="{{ $item['codigo_produto'] }}" data-estoque="{{ $item['estoque_geral'] }}" data-id_produto="{{ $item['id_produto'] }}" data-tipo="{{ $item['tipo'] }}" data-saldo="{{ $item['qtde_a_enviar'] }}" data-estabelecimento_origem="{{ $item['codigo_estabelecimento'] }}"  data-estoque_estabelecimento_origem="{{ $item['estoque_estabelecimento_origem'] }}" onkeyup="calculoEstoque($(this))" onblur="calculoEstoque($(this))"></td>
                            <td class="tb_number" style="width: 150px;">
                                <input style="height: inherit;" id="estoque_atual-{{ $item['codigo_produto'] }}-{{ $item['id_produto'] }}-{{ $item['tipo'] }}" name="estoque_atual-{{ $item['codigo_produto'] }}" value="{{ $item['estoque_estabelecimento_origem_descr'] }}" type="text" class="form-control text-right moeda" maxlength="10" disabled>
                            </td>
                            <td style="width: 200px;">
                                <select style="height: inherit; " id="estabelecimento-{{ $item['codigo_produto'] }}-{{ $item['numero_projeto'] }}" class="form-control" name="estabelecimento-{{ $item['codigo_produto'] }}-{{ $item['id_produto'] }}" placeholder="Estabelecimento" data-projeto="{{ $item['numero_projeto'] }}" data-pedido="{{ $item['pedido'] }}" data-codigo_produto="{{ $item['codigo_produto'] }}" data-estoque_estabelecimento_origem="{{ $item['estoque_estabelecimento_origem'] }}" data-campo_qtde_a_enviar="qtde_a_enviar-{{ $item['codigo_produto'] }}-{{ $item['id_produto'] }}" onchange="liberarTransportadora($(this))" disabled>
                                    <option value="0">Estabelecimento</option>
                                    @foreach($item['estoque'] as $estoque)
                                        @if(!empty($estoque['valor']) && $estoque['codigo'] != $item['codigo_estabelecimento'])
                                            <option value="{{ $estoque['codigo'] }}" data-estoque="{{ $estoque['valor'] }}">{{ $estoque['estabelecimento'] }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>
                            <td style="width: 150px;"><input style="height: inherit;" id="qtde_transf-{{ $item['codigo_produto'] }}-{{ $item['numero_projeto'] }}" name="qtde_transf-{{ $item['codigo_produto'] }}-{{ $item['numero_projeto'] }}" type="text" class="form-control text-right moeda" value="" maxlength="10" data-projeto="{{ $item['numero_projeto'] }}" data-pedido="{{ $item['pedido'] }}" data-codigo_produto="{{ $item['codigo_produto'] }}" data-id_produto="{{ $item['id_produto'] }}" onkeyup="calculoTransferencia($(this))" onblur="calculoTransferencia($(this))" disabled></td>
                            <td style="width: 150px;">
                                <input style="height: inherit;" id="estoque_transf-{{ $item['codigo_produto'] }}-{{ $item['numero_projeto'] }}" name="estoque_transf-{{ $item['codigo_produto'] }}-{{ $item['numero_projeto'] }}" value="" type="text" class="form-control text-right moeda" maxlength="10" disabled>
                            </td>
                            <td style="width: 150px;">
                                <input style="height: inherit;" id="estoque-{{ $item['codigo_produto'] }}-{{ $item['id_produto'] }}-{{ $item['tipo'] }}" name="estoque-{{ $item['codigo_produto'] }}" type="text" class="form-control text-right moeda {{ $item['codigo_produto'] }}" value="{{ $item['estoque_geral'] }}" maxlength="10" disabled>
                                <div>
                                        <div data-toggle="popover" data-placement="top" data-title="Estoque" data-content="@if(!empty($item['estoque']['estoque_rondonia']['valor']))<p><b>{{ $estabelecimentos[3] }}:</b>{{ $item['estoque']['estoque_rondonia']['valor'] }} @endif @if(!empty($item['estoque']['estoque_tocantins']['valor']))<p><b>{{ $estabelecimentos[4] }}:</b>{{ $item['estoque']['estoque_tocantins']['valor'] }} @endif @if(!empty($item['estoque']['estoque_matriz']['valor']))<p><b>{{ $estabelecimentos[5] }}:</b>{{ $item['estoque']['estoque_matriz']['valor'] }} @endif @if(!empty($item['estoque']['estoque_almirante_2']['valor']))<p><b>{{ $estabelecimentos[6] }}:</b>{{ $item['estoque']['estoque_almirante_2']['valor'] }} @endif"> 
                                        <a href="#" class="btn-informacao"></a>
                                    </div>
                                </div>
                            </td>
                            <td class="tb_number" style="width: 150px;">{{ $item['estoque_compras'] }}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
        </div>
    </div>
    <br>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Gerar Remesssa', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    produto_obj = {};
    array_campos = [];
    projeto_obj = {};
    array_projetos = [];

    @foreach($itens as $item)
        if($.isEmptyObject(produto_obj['{{ $item['codigo_produto'] }}'])){
            produto_obj = {
                "{{ $item['codigo_produto'] }}": []
            };
        }
        produto_obj['{{ $item['codigo_produto'] }}'].push({{ $item['id_produto'] }});

        array_campos.push("{{ $item['codigo_produto'] }}-{{ $item['id_produto'] }}");

        if($.isEmptyObject(projeto_obj['{{ $item['numero_projeto'] }}'])){
            projeto_obj["{{ $item['numero_projeto'] }}"] = ["{{ $item['codigo_produto'] }}-{{ $item['numero_projeto'] }}"];
        }else{
            projeto_obj['{{ $item['numero_projeto'] }}'].push("{{ $item['codigo_produto'] }}-{{ $item['numero_projeto'] }}");
        }
       
        array_projetos.push("{{ $item['numero_projeto'] }}");
    @endforeach

    $(document).ready( function () {
        hide_loader();
        form_modal = $(document).find('#form_geracao_remessa');

        form_modal.find("input[name='todos_produtos']").off('click');
        form_modal.find("input[name='todos_produtos']").on('click', function(){
            carregarTabela(form_modal);
        });

        form_modal.find("#btn-salvar").off('click');
        form_modal.find("#btn-salvar").on('click', function(){
            gerarPedidoRemessa(form_modal);
        });

        $(document).find(".moeda").maskMoney({thousands:'', decimal:','});

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

        @foreach($itens as $item)
            calculoEstoque(form_modal.find("#qtde_a_enviar-{{ $item['codigo_produto'] }}-{{ $item['id_produto'] }}"));
        @endforeach
    });

    function inserirDados(data_form_modal){
        $.ajax({
            url: "{{ route('produto.tecido_base.adicionar') }}", 
            dataType: 'json',
            data: data_form_modal,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroModal();
                mensagemErroModal(dados);
            }
        });
    }

    function limparMesagemErroModal(){      
        var form_modal = $("#form_geracao_remessa");
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroModal(json_error){
        var form_modal = $("#form_geracao_remessa");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModal(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form_modal, input, message){
        if(input.localeCompare('transportadora_nome-') == 0){
            var $input = $(form_modal).find("#bt-search-transportadora-"+message.projeto);
            $(form_modal).find("input[name='transportadora_nome-"+message.projeto+"']").addClass('error-input');
            message = message.mensagem;
        }else if(input.localeCompare('transportadora_nome_almirante_2-') == 0){
            var $input = $(form_modal).find("#bt-search-transportadora_almirante_2-"+message.projeto);
            $(form_modal).find("input[name='transportadora_nome_almirante_2-"+message.projeto+"']").addClass('error-input');
            message = message.mensagem;
        }else if(input.localeCompare('transportadora_nome_matriz-') == 0){
            var $input = $(form_modal).find("#bt-search-transportadora_matriz-"+message.projeto);
            $(form_modal).find("input[name='transportadora_nome_matriz-"+message.projeto+"']").addClass('error-input');
            message = message.mensagem;
        }else if(input.localeCompare('transportadora_nome_tocantins-') == 0) {
            var $input = $(form_modal).find("#bt-search-transportadora_tocantins-"+message.projeto);
            $(form_modal).find("input[name='transportadora_nome_tocantins-"+message.projeto+"']").addClass('error-input');
            message = message.mensagem;
        }else if(input.localeCompare('transportadora_nome_rondonia-') == 0){
            var $input = $(form_modal).find("#bt-search-transportadora_rondonia-"+message.projeto);
            $(form_modal).find("input[name='transportadora_nome_rondonia-"+message.projeto+"']").addClass('error-input');
            message = message.mensagem;
        }else if(input.localeCompare('estabelecimento-') == 0){
            input = input+message.codigo_produto+"-"+message.projeto;
            var $input = $(form_modal).find("#"+input);
            message = message.mensagem;
        }else if(input.localeCompare('qtde_transf-') == 0){
            input = input+message.codigo_produto+"-"+message.projeto;
            var $input = $(form_modal).find("#"+input);
            message = message.mensagem;
        }else{
            var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
        }

        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function carregarTabela(form_modal){
        var html = "";
        data_form_modal = form_modal.serialize();
        $.ajax({
            url: '{{ route('remessa_itens.produto_para_remessa') }}',
            type: 'POST',
            data: data_form_modal,
            success: function (data){
                produto_obj = {};
                projeto_obj = {};
                array_estabelecimentos = [];
                array_campos = [];
                array_reverso_projeto = [];
                for (var index_projeto in data.response.projetos){
                    html = html + "<hr>"+
                            "<h6><b>Projeto nº</b> "+data.response.projetos[index_projeto].numero_projeto+" -  <b>Nome do Projeto:</b> "+data.response.projetos[index_projeto].nome_projeto+" - <b>Cliente:</b> "+data.response.projetos[index_projeto].cliente+" - <b>Estabelecimento:</b> "+data.response.projetos[index_projeto].estabelecimento+"</h6>"+
                            "<br>";
                    html = html + "<div class=\"form-row\">"+
                                        "<div class=\"form-group col-sm-12\">"+
                                            "<label for=\"transportadora_nome-"+data.response.projetos[index_projeto].numero_projeto+"\">Transportadora para o Envio Facção</label> <span data-toggle=\"tooltip\" data-placement=\"top\" title=\"\" class=\"campo_obrigatorio\" data-original-title=\"Campo obrigatório\">*</span>"+
                                            "<div class=\"input-group\" id=\"transporadora_group\">"+
                                                "<input id=\"transportadora_nome-"+data.response.projetos[index_projeto].numero_projeto+"\" class=\"form-control essencial input-label\" data-estabelecimento=\""+data.response.projetos[index_projeto].codigo_estabelecimento+"\" onkeyup=\"optionsTransportadora($(this))\" name=\"transportadora_nome-"+data.response.projetos[index_projeto].numero_projeto+"\" type=\"text\" value=\"\" autocomplete=\"off\">"+
                                                "<span class=\"input-group-addon border rounded-right\" id=\"bt-search-transportadora-"+data.response.projetos[index_projeto].numero_projeto+"\" data-estabelecimento=\""+data.response.projetos[index_projeto].codigo_estabelecimento+"\" data-nome_campo=\"transportadora_nome-"+data.response.projetos[index_projeto].numero_projeto+"\" onclick=\"modalTransportador($(this))\"><i id=\"bt-view-transportadora\" class=\"bt-view m-2\"></i></span>"+
                                            "</div>"+
                                        "</div>"+
                                    "</div>";
                    html = html + "<div class=\"form-row\" id=\"transportadora_rondonia-"+data.response.projetos[index_projeto].numero_projeto+"\" name=\"transportadora_rondonia-"+data.response.projetos[index_projeto].numero_projeto+"\" style=\"display:none\">"+
                            "<div class=\"form-group col-sm-12\">"+
                                    "<label for=\"transportadora_nome_rondonia-" + data.response.projetos[index_projeto].numero_projeto + "\">Transportadora Transferência Rondonia</label> <span data-toggle=\"tooltip\" data-placement=\"top\" title=\"\" class=\"campo_obrigatorio\" data-original-title=\"Campo obrigatório\">*</span>"+
                                    "<div class=\"input-group\" id=\"transporadora_group\">"+
                                            "<input id=\"transportadora_nome_rondonia-" + data.response.projetos[index_projeto].numero_projeto + "\"  class=\"form-control essencial input-label\" data-estabelecimento=\""+data.response.projetos[index_projeto].codigo_estabelecimento+"\" onkeyup=\"optionsTransportadora($(this))\" name=\"transportadora_nome_rondonia-"+data.response.projetos[index_projeto].numero_projeto+"\" type=\"text\" value=\"\" autocomplete=\"off\">"+
                                            "<span class=\"input-group-addon border rounded-right\" id=\"bt-search-transportadora_rondonia-"+data.response.projetos[index_projeto].numero_projeto+"\" data-estabelecimento=\""+data.response.projetos[index_projeto].codigo_estabelecimento+"\" data-nome_campo=\"transportadora_nome_rondonia-"+data.response.projetos[index_projeto].numero_projeto+"\" onclick=\"modalTransportador($(this))\"><i id=\"bt-view-transportadora\" class=\"bt-view m-2\"></i></span>"+
                                            "</div>"+
                                        "</div>"+
                                    "</div>";
                    html = html + "<div class=\"form-row\" id=\"transportadora_tocantins-"+data.response.projetos[index_projeto].numero_projeto+"\" name=\"transportadora_tocantins-"+data.response.projetos[index_projeto].numero_projeto+"\" style=\"display:none\">"+
                                            "<div class=\"form-group col-sm-12\">"+
                                                "<label for=\"transportadora_nome_tocantins-"+data.response.projetos[index_projeto].numero_projeto+"\">Transportadora Transferência Tocantins</label> <span data-toggle=\"tooltip\" data-placement=\"top\" title=\"\" class=\"campo_obrigatorio\" data-original-title=\"Campo obrigatório\">*</span>"+
                                                "<div class=\"input-group\" id=\"transporadora_group\">"+
                                                    "<input id=\"transportadora_nome_tocantins-"+data.response.projetos[index_projeto].numero_projeto+"\" class=\"form-control essencial input-label\" data-estabelecimento=\""+data.response.projetos[index_projeto].codigo_estabelecimento+"\" onkeyup=\"optionsTransportadora($(this))\" name=\"transportadora_nome_tocantins-"+data.response.projetos[index_projeto].numero_projeto+"\" type=\"text\" value=\"\" autocomplete=\"off\">"+
                                                    "<span class=\"input-group-addon border rounded-right\" id=\"bt-search-transportadora_tocantins-"+data.response.projetos[index_projeto].numero_projeto+"\" data-estabelecimento=\""+data.response.projetos[index_projeto].codigo_estabelecimento+"\" data-nome_campo=\"transportadora_nome_tocantins-"+data.response.projetos[index_projeto].numero_projeto+"\" onclick=\"modalTransportador($(this))\"><i id=\"bt-view-transportadora\" class=\"bt-view m-2\"></i></span>"+
                                                "</div>"+
                                            "</div>"+
                                        "</div>";
                    html = html + "<div class=\"form-row\" id=\"transportadora_matriz-"+data.response.projetos[index_projeto].numero_projeto+"\" name=\"transportadora_matriz-"+data.response.projetos[index_projeto].numero_projeto+"\" style=\"display:none\">"+
                                            "<div class=\"form-group col-sm-12\">"+
                                                "<label for=\"transportadora_nome_matriz-"+data.response.projetos[index_projeto].numero_projeto+"\">Transportadora Transferência Matriz</label> <span data-toggle=\"tooltip\" data-placement=\"top\" title=\"\" class=\"campo_obrigatorio\" data-original-title=\"Campo obrigatório\">*</span>"+
                                                "<div class=\"input-group\" id=\"transporadora_group\">"+
                                                    "<input id=\"transportadora_nome_matriz-"+data.response.projetos[index_projeto].numero_projeto+"\" class=\"form-control essencial input-label\" data-estabelecimento=\""+data.response.projetos[index_projeto].codigo_estabelecimento+"\" onkeyup=\"optionsTransportadora($(this))\" name=\"transportadora_nome_matriz-"+data.response.projetos[index_projeto].numero_projeto+"\" type=\"text\" value=\"\" autocomplete=\"off\">"+
                                                    "<span class=\"input-group-addon border rounded-right\" id=\"bt-search-transportadora_matriz-"+data.response.projetos[index_projeto].numero_projeto+"\" data-estabelecimento=\""+data.response.projetos[index_projeto].codigo_estabelecimento+"\" data-nome_campo=\"transportadora_nome_matriz-"+data.response.projetos[index_projeto].numero_projeto+"\" onclick=\"modalTransportador($(this))\"><i id=\"bt-view-transportadora\" class=\"bt-view m-2\"></i></span>"+
                                                "</div>"+
                                            "</div>"+
                                        "</div>";
                    html = html + "<div class=\"form-row\" id=\"transportadora_almirante_2-"+data.response.projetos[index_projeto].numero_projeto+"\" name=\"transportadora_almirante_2-"+data.response.projetos[index_projeto].numero_projeto+"\" style=\"display:none\">"+
                                            "<div class=\"form-group col-sm-12\">"+
                                                "<label for=\"transportadora_nome_almirante_2-"+data.response.projetos[index_projeto].numero_projeto+"\">Transportadora Transferência Almirante 2</label> <span data-toggle=\"tooltip\" data-placement=\"top\" title=\"\" class=\"campo_obrigatorio\" data-original-title=\"Campo obrigatório\">*</span>"+
                                                "<div class=\"input-group\" id=\"transporadora_group\">"+
                                                    "<input id=\"transportadora_nome_almirante_2-"+data.response.projetos[index_projeto].numero_projeto+"\" class=\"form-control essencial input-label\" data-estabelecimento=\""+data.response.projetos[index_projeto].codigo_estabelecimento+"\" onkeyup=\"optionsTransportadora($(this))\" name=\"transportadora_nome_almirante_2-"+data.response.projetos[index_projeto].numero_projeto+"\" type=\"text\" value=\"\" autocomplete=\"off\">"+
                                                    "<span class=\"input-group-addon border rounded-right\" id=\"bt-search-transportadora_almirante_2-"+data.response.projetos[index_projeto].numero_projeto+"\" data-estabelecimento=\""+data.response.projetos[index_projeto].codigo_estabelecimento+"\" data-nome_campo=\"transportadora_nome_almirante_2-"+data.response.projetos[index_projeto].numero_projeto+"\" onclick=\"modalTransportador($(this))\"><i id=\"bt-view-transportadora\" class=\"bt-view m-2\"></i></span>"+
                                                "</div>"+
                                            "</div>"+
                                        "</div>";
                    html = html + "<table class=\"table table-striped\">";
                    html = html + "<thead>"+
                            "<tr>"+
                                "<th>Código</th>"+
                                "<th>Descrição</th>"+
                                "<th class=\"tb_number\">Necessidade</th>"+
                                "<th>Qtde a enviar</th>"+
                                "<th class=\"tb_number\">Est. Estab. 05 - Matriz</th>"+
                                "<th>EST. TRANSF.</th>"+
                                "<th>Qtde. Trans.</th>"+
                                "<th>EST. ESTAB. TRANSF.</th>"+
                                "<th>EST. TOTAL</th>"+
                                "<th class=\"tb_number\">Compras</th>"+
                            "</tr>"+
                            "</thead>";
                    html = html + "<tbody>";
                    for(var index_item in data.response.itens[index_projeto]){
                        html = html + "<tr>";
                        html = html + "<td style=\"width: 100px;\">" + data.response.itens[index_projeto][index_item].codigo_produto + "</td>";
                        html = html + "<td>" + ajusteTamanhoTable(data.response.itens[index_projeto][index_item].produto_a_enviar) + "</td>";
                        html = html + "<td class=\"tb_number\" style=\"width: 150px;\">" + data.response.itens[index_projeto][index_item].qtde_a_enviar + "</td>";
                        html = html + "<td style=\"width: 150px;\">" + inputQtdeAEnviar(data.response.itens[index_projeto][index_item], data.response.projetos[index_projeto]) + "</td>";
                        html = html + "<td style=\"width: 150px;\">" + inputEstoqueEstabAtual(data.response.itens[index_projeto][index_item], data.response.projetos[index_projeto]) + "</td>";
                        html = html + "<td style=\"width: 200px;\">" + inputEstabelecimento(data.response.itens[index_projeto][index_item], data.response.projetos[index_projeto]) + "</td>";
                        html = html + "<td  style=\"width: 150px;\">" + inputQtdeTransf(data.response.itens[index_projeto][index_item], data.response.projetos[index_projeto]) + "</td>";
                        html = html + "<td  style=\"width: 150px;\">" + inputEstoqueEstabTransf(data.response.itens[index_projeto][index_item], data.response.projetos[index_projeto]) + "</td>";
                        html = html + "<td class=\"tb_number\"  style=\"width: 150px;\">" + inputEstoque(data.response.itens[index_projeto][index_item], data.response.projetos[index_projeto], data.response.estabelecimentos) + "</td>";
                        html = html + "<td class=\"tb_number\"  style=\"width: 150px;\">" + data.response.itens[index_projeto][index_item].estoque_compras + "</td>";
                        html = html + "</tr>";

                        var codigo_produto = data.response.itens[index_projeto][index_item].codigo_produto;
                        var valor = data.response.itens[index_projeto][index_item].id_produto;

                        if($.isEmptyObject(produto_obj[codigo_produto])){
                            produto_obj[codigo_produto]= [valor];
                        }
                        if(produto_obj[codigo_produto].indexOf(valor) < 0){
                            produto_obj[codigo_produto].push(valor);
                        }
                        array_campos.push(data.response.itens[index_projeto][index_item].codigo_produto+"-"+data.response.itens[index_projeto][index_item].id_produto);

                        var projeto = data.response.projetos[index_projeto].numero_projeto;
                        var estabelecimento = data.response.itens[index_projeto][index_item].codigo_produto+"-"+data.response.projetos[index_projeto].numero_projeto;

                        if($.isEmptyObject(projeto_obj[projeto])){
                            projeto_obj[projeto]= [estabelecimento];
                        }
                        if(projeto_obj[projeto].indexOf(estabelecimento) < 0){
                            projeto_obj[projeto].push(estabelecimento);
                        }
                    }
                    html = html + "</tbody>";
                    html = html + "</table>";
                    array_reverso_projeto.push(projeto);
                }
                $(document).find("#conteudo_geracao_remessa").html(html);
                $(document).find(".moeda").maskMoney({thousands:'', decimal:','});

                array_reverso_projeto.reverse().forEach(function carregarEstoque(index_projeto){
                    for(var index_item in data.response.itens[index_projeto]){
                        calculoEstoque(form_modal.find("#qtde_a_enviar-"+data.response.itens[index_projeto][index_item].codigo_produto+"-"+data.response.itens[index_projeto][index_item].id_produto));
                    }
                });
                
                chamadaPopover();
            },
            error: function (data){

            }
        });
    }

    function inputQtdeAEnviar($value, $projeto){
        var html = "<input style=\"height: inherit;\" id=\"qtde_a_enviar-"+$value.codigo_produto+"-"+$value.id_produto+"\" name=\"qtde_a_enviar-"+$value.codigo_produto+"-"+$value.id_produto+"\" type=\"text\" class=\"form-control text-right moeda\" value=\""+$value.qtde_a_enviar+"\" maxlength=\"10\" data-projeto=\""+$projeto.numero_projeto+"\" data-pedido=\""+$projeto.pedido+"\" data-codigo_produto=\""+$value.codigo_produto+"\" data-estoque=\""+$value.estoque_geral+"\" data-id_produto=\""+$value.id_produto+"\" data-tipo=\""+$value.tipo+"\" data-saldo=\""+$value.qtde_a_enviar+"\" data-estabelecimento_origem=\""+$projeto.codigo_estabelecimento+"\" data-estoque_estabelecimento_origem=\""+$value.estoque_estabelecimento_origem+"\" onkeyup=\"calculoEstoque($(this))\" onblur=\"calculoEstoque($(this))\">";
        return html;
    }

    function inputEstoqueEstabAtual($value, $projeto){
        var html = "<input style=\"height: inherit;\" id=\"estoque_atual-"+$value.codigo_produto+"-"+$value.id_produto+"\" name=\"estoque_atual-"+$value.codigo_produto+"-"+$value.id_produto+"\" value=\""+$value.estoque_estabelecimento_origem_descr+"\" type=\"text\" class=\"form-control text-right moeda\" maxlength=\"10\" disabled autocomplete=\"off\">";
        return html;
    }

    function inputEstoque($value, $projeto, $estabelecimentos){
        var content = "";
        var html = "<input style=\"height: inherit;\" id=\"estoque-"+$value.codigo_produto+"-"+$value.id_produto+"-"+$value.tipo+"\" name=\"estoque-"+$value.codigo_produto+"-"+$value.id_produto+"-"+$value.tipo+"\" type=\"text\" class=\"form-control text-right moeda "+$value.codigo_produto+"\" value=\""+$value.estoque_geral+"\" maxlength=\"10\" disabled>";
        html = html + "<div>";
        if($value.estoque.estoque_rondonia.valor != ""){
            content = content + "<p><b>" + $estabelecimentos[$value.estoque.estoque_rondonia.codigo] + ":</b> " + $value.estoque.estoque_rondonia.valor;
        }
        if($value.estoque.estoque_tocantins.valor != ""){
            content = content + "<p><b>" + $estabelecimentos[$value.estoque.estoque_tocantins.codigo] + ":</b> " + $value.estoque.estoque_tocantins.valor;
        }
        if($value.estoque.estoque_matriz.valor != ""){
            content = content + "<p><b>" + $estabelecimentos[$value.estoque.estoque_matriz.codigo] + ":</b> " + $value.estoque.estoque_matriz.valor;
        }
        if($value.estoque.estoque_almirante_2.valor != ""){
            content = content + "<p><b>" + $estabelecimentos[$value.estoque.estoque_almirante_2.codigo] + ":</b> " + $value.estoque.estoque_almirante_2.valor;
        }
        html = html + "<div data-toggle=\"popover\" data-placement=\"top\" data-title=\"Estoque\" data-content=\""+content+"\">";
        html = html + "<a href=\"#\" class=\"btn-informacao\"></a>";
        html = html + "</div>";
        html = html + "</div>";          
        return html;
    }

    function inputEstabelecimento($value, $projeto){
        var html = "<select style=\"height: inherit;\" id=\"estabelecimento-"+$value.codigo_produto+"-"+$projeto.numero_projeto+"\" class=\"form-control\" name=\"estabelecimento-"+$value.codigo_produto+"-"+$projeto.numero_projeto+"\" data-campo_qtde_a_enviar=\"qtde_a_enviar-"+$value.codigo_produto+"-"+$value.id_produto+"\" placeholder=\"Estabelecimento\" data-projeto=\""+$projeto.numero_projeto+"\" data-pedido=\""+$projeto.pedido+"\" data-codigo_produto=\""+$value.codigo_produto+"\" data-estoque_estabelecimento_origem=\""+$value.estoque_estabelecimento_origem+"\" onchange=\"liberarTransportadora($(this))\" disabled>";
        html = html + "<option value=\"0\">Estabelecimento</option>";
        for(var index_estoque in $value.estoque){
            if($value.estoque[index_estoque].valor != '' && $value.estoque[index_estoque].codigo != $projeto.codigo_estabelecimento){
                html = html + "<option value=\""+$value.estoque[index_estoque].codigo+"\" data-estoque=\""+$value.estoque[index_estoque].valor+"\">"+$value.estoque[index_estoque].estabelecimento+"</option>";
            }
        }
        html = html + "</select>";
        return html;
    }

    function inputQtdeTransf($value, $projeto){
        var html = "<input style=\"height: inherit;\" id=\"qtde_transf-"+$value.codigo_produto+"-"+$projeto.numero_projeto+"\" name=\"qtde_transf-"+$value.codigo_produto+"-"+$projeto.numero_projeto+"\" type=\"text\" class=\"form-control text-right moeda\" value=\"\" maxlength=\"10\" data-projeto=\""+$projeto.numero_projeto+"\" data-pedido=\""+$projeto.pedido+"\" data-codigo_produto=\""+$value.codigo_produto+"\" data-id_produto=\""+$value.id_produto+"\"  onkeyup=\"calculoTransferencia($(this))\" onblur=\"calculoTransferencia($(this))\" disabled>";

        return html;
    }

    function inputEstoqueEstabTransf($value, $projeto){
        var html = "<input style=\"height: inherit;\" id=\"estoque_transf-"+$value.codigo_produto+"-"+$projeto.numero_projeto+"\" name=\"estoque_transf-"+$value.codigo_produto+"-"+$projeto.numero_projeto+" value=\"\" type=\"text\" class=\"form-control text-right moeda\" maxlength=\"10\" disabled autocomplete=\"off\">";
        return html;
    }

    function calculoEstoque($this){
        var form_modal = $("#form_geracao_remessa");
        var codigo_produto = $this.data("codigo_produto");
        var projeto = $this.data("projeto");
        var saldo = $this.data("saldo");
        var estoque = $this.data("estoque");
        var estoque_estabelecimento_origem = parseFloat($this.data("estoque_estabelecimento_origem"));
        var id_produto = $this.data("id_produto");
        var qtde_a_enviar = $this.val();
        var outros_enviar = 0;
        var valor = 0;
        var valor_total = 0;
        var menor = 0;

        outros_enviar = parseFloat(outros_enviar);

        produto_obj[codigo_produto].forEach(function imprimir(item){
            if(form_modal.find("#qtde_a_enviar-"+codigo_produto+"-"+item).val() != ''){
                valor = form_modal.find("#qtde_a_enviar-"+codigo_produto+"-"+item).val().replace(/\./g,"").replace(/\,/g, ".");
                valor = parseFloat(valor);
                outros_enviar = outros_enviar + valor;
            }
        });

        estoque = estoque.replace(/\./g,"").replace(/\,/g, ".");
        qtde_a_enviar = qtde_a_enviar.replace(/\./g,"").replace(/\,/g, ".");
        saldo = saldo.replace(/\./g,"").replace(/\,/g, ".");

        estoque = parseFloat(estoque);
        qtde_a_enviar = parseFloat(qtde_a_enviar);
        saldo = parseFloat(saldo);

        outros_enviar = outros_enviar - qtde_a_enviar;

        estoque = estoque - outros_enviar;

        if(estoque < 0){
            estoque = 0;
        }

        if(estoque < saldo){
            menor = estoque;
        }else{
            menor = saldo;
        }

        if(menor < qtde_a_enviar){
            
            qtde_a_enviar = menor;

            menor = menor.toFixed(2);

            $this.val(numberToReal(menor).replace(/\./g,""));
        }

        valor_total = Math.round((estoque - qtde_a_enviar)* 1000) / 1000;

        if(valor_total < 0){
            valor_total = 0;
        }

        valor_total = valor_total.toFixed(2);

        form_modal.find("."+codigo_produto).val(numberToReal(valor_total));


        form_modal.find("#estabelecimento-"+codigo_produto+"-"+projeto).removeAttr('disabled');
        form_modal.find("#qtde_transf-"+codigo_produto+"-"+projeto).removeAttr('disabled');

        
        if(qtde_a_enviar <= 0){
            liberarTransportadora(form_modal.find("#qtde_transf-"+codigo_produto+"-"+projeto));
        }
    }

    function calculoTransferencia($this){
        var form_modal = $("#form_geracao_remessa");
        var qtde_transferencia = $this.val();
        var codigo_produto = $this.data("codigo_produto");
        var id_produto = $this.data("id_produto");
        var projeto = $this.data("projeto");
        var estabelecimento = form_modal.find("#estabelecimento-"+codigo_produto+"-"+projeto).val();
        var qtde_estabelecimento = form_modal.find("#estabelecimento-"+codigo_produto+"-"+projeto+" option:selected").attr('data-estoque');
        var outras_transferencia = 0;

        array_projetos.forEach(function imprimir(item){
            if(form_modal.find("#estabelecimento-"+codigo_produto+"-"+item).val() == estabelecimento){
                if(form_modal.find("#qtde_transf-"+codigo_produto+"-"+item).val() != ''){
                    valor = form_modal.find("#qtde_transf-"+codigo_produto+"-"+item).val().replace(/\./g,"").replace(/\,/g, ".");
                    valor = parseFloat(valor);
                    outras_transferencia = outras_transferencia + valor;
                }
            }
        });

        qtde_estabelecimento = qtde_estabelecimento.replace(/\./g,"").replace(/\,/g, ".");
        qtde_transferencia = qtde_transferencia.replace(/\./g,"").replace(/\,/g, ".");

        qtde_estabelecimento = parseFloat(qtde_estabelecimento);
        qtde_transferencia = parseFloat(qtde_transferencia);

        outras_transferencia = outras_transferencia - qtde_transferencia;

        qtde_estabelecimento = qtde_estabelecimento - outras_transferencia;

        if(qtde_transferencia > qtde_estabelecimento){
            qtde_estabelecimento = qtde_estabelecimento.toFixed(2);

            $this.val(numberToReal(qtde_estabelecimento).replace(/\./g,""));
        }
    }

    function numberToReal(valor) {
        var numero = valor.split('.');
        numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
        return numero.join(',');
    }

    function gerarPedidoRemessa(form_modal){
        var id_projeto;
        var codigo_produto;
        var quantidade_a_enviar;
        var estabelecimento;
        var array_itens = [];
        var array_estabelecimentos = [];
        var itens_obj = {};
        var transf_obj = {};
        var proj_transf_obj = {};
        var proj_transp_obj = {};
        array_campos.forEach(function imprimir(item){
            array_item = [];
            array_transf = [];
            id_projeto = form_modal.find("#qtde_a_enviar-"+item).data("projeto");
            pedido = form_modal.find("#qtde_a_enviar-"+item).data("pedido");
            codigo_produto = form_modal.find("#qtde_a_enviar-"+item).data("codigo_produto");
            quantidade_a_enviar = form_modal.find("#qtde_a_enviar-"+item).val();
            estabelecimento = form_modal.find("#estabelecimento-"+codigo_produto+"-"+id_projeto).val();
            qtde_transferencia = form_modal.find("#qtde_transf-"+codigo_produto+"-"+id_projeto).val();
            estabelecimento_origem = form_modal.find("#qtde_a_enviar-"+item).data("estabelecimento_origem");
            tipo = form_modal.find("#qtde_a_enviar-"+item).data("tipo");
            saldo = form_modal.find("#qtde_a_enviar-"+item).data("saldo");

            if(quantidade_a_enviar != ''){
                array_item = {
                    "codigo_produto": codigo_produto,
                    "quantidade_a_enviar": quantidade_a_enviar,
                    "estabelecimento": estabelecimento_origem,
                    "tipo": tipo,
                    "saldo": saldo
                };

                if($.isEmptyObject(itens_obj[id_projeto])){
                    itens_obj[id_projeto]= [array_item];
                }else{
                    itens_obj[id_projeto].push(array_item);
                }

                if(estabelecimento != "0"){
                    array_transf = {
                        "codigo_produto": codigo_produto,
                        "quantidade_a_enviar": quantidade_a_enviar,
                        "tipo": tipo,
                        "saldo": saldo,
                        "estabelecimento_origem": estabelecimento_origem,
                        "estabelecimento_transferencia": estabelecimento,
                        "quantidade_transferencia": qtde_transferencia,
                        "pedido": pedido
                    };
                    if($.isEmptyObject(transf_obj[id_projeto])){
                        transf_obj[id_projeto]= [array_transf];
                    }else{
                        transf_obj[id_projeto].push(array_transf);
                    }
                }
            }
        });

        array_projetos.forEach(function imprimir(projeto){
            array_estabelecimentos = [];
            projeto_obj[projeto].forEach(function imprimir(item){
                if(form_modal.find("#estabelecimento-"+item).val() != "0"){
                    if(array_estabelecimentos.indexOf(form_modal.find("#estabelecimento-"+item).val()) < 0){
                        array_estabelecimentos.push(form_modal.find("#estabelecimento-"+item).val());
                    }
                }
            });

            if($.isEmptyObject(proj_transf_obj[projeto])){
                proj_transf_obj[projeto]= array_estabelecimentos;
            }

            proj_transp_obj[projeto] = {
                transportadora : form_modal.find("#transportadora_nome-"+projeto).val(),
                transportadora_rondonia : form_modal.find("#transportadora_nome_rondonia-"+projeto).val(),
                transportadora_tocantins : form_modal.find("#transportadora_nome_tocantins-"+projeto).val(),
                transportadora_matriz : form_modal.find("#transportadora_nome_matriz-"+projeto).val(),
                transportadora_almirante_2 : form_modal.find("#transportadora_nome_almirante_2-"+projeto).val(),
            }
        });

        var id_faccao = form_modal.find("#id_faccao").val();

        $.ajax({
            url: "{{ route('remessa_itens.gerar_remessa') }}", 
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                transf_itens: transf_obj,
                itens: itens_obj,
                id_faccao: id_faccao,
                projetos_estabelecimentos_transferencia: proj_transf_obj,
                projetos_transportadoras: proj_transp_obj
                
            },
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
                var remessa = '';
                for(var index_remessa in callback.response.remessa){
                    if(remessa == ''){
                        remessa = callback.response.remessa[index_remessa];
                    }else{
                        remessa = remessa+", "+callback.response.remessa[index_remessa];
                    }
                }

                var transferencia = '';
                for(var index_transferencia in callback.response.transferencia){
                    if(transferencia == ''){
                        transferencia = callback.response.transferencia[index_transferencia];
                    }else{
                        transferencia = transferencia+", "+callback.response.transferencia[index_transferencia];
                    }
                }

                if(transferencia == ''){
                    var mensagem_retorno = "Pedido de Remessa: "+remessa+"."
                }else{
                    var mensagem_retorno = "Pedido de Remessa: "+remessa+". <p>Pedido de Transferência: "+transferencia+".";
                }
                
                message("Atenção", mensagem_retorno);
            },
            error: function(callback){
                var dados = callback.responseJSON;
                message("Atenção",callback.responseJSON.message);
                limparMesagemErroModal();
                mensagemErroModal(dados);
            }
        });
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

    function modalTransportador($this){
        form_modal = $(document).find('#form_geracao_remessa');
        $.ajax({
            url: '{{ Route('transportador.index.dialog') }}',
            type: 'POST',
           data: {_token: '{{ csrf_token() }}', estabelecimento: $this.data("estabelecimento")},
            success: function(data){
				$(document).find('#modal_busca_transportador').remove();
                createModal('modal_busca_transportador', "Busca de transporadora", data, 'modal-lg');
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        $(document).find("#modal_busca_transportador").find('tbody').find("tr").off("click");
                        $(document).find("#modal_busca_transportador").find('tbody').find("tr").on("click", function(){
							returnDadosTransportador($(this), $this);
                        });
                    });
                });
            }

        });
	}
	function returnDadosTransportador($dados, $this){
        form_modal = $(document).find('#form_geracao_remessa');
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }

		form_modal.find("#"+$this.data("nome_campo")).val($dados.find("td:eq(1)").text() + " - " + $dados.find("td:eq(2)").text());
		$(document).find("#modal_busca_transportador").modal('hide');
    }
    

    function optionsTransportadora($this){
        $this.autocomplete(optionsAutoCompleteTransportador($this));   
    }

    function optionsAutoCompleteTransportador($this){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.estabelecimento = $this.data("estabelecimento");
                $.post("{{ route('transportador.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_geracao_remessa').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma transportadora encontrada');
                    event.stopPropagation();
                    $this.focus();
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

    function liberarTransportadora($this){
        form_modal = $(document).find('#form_geracao_remessa');
        var projeto = $this.data("projeto"); 
        var codigo_produto = $this.data("codigo_produto");
        var estabelecimento = $this.val();
        var estoque = form_modal.find("#estabelecimento-"+codigo_produto+"-"+projeto+" option:selected").attr('data-estoque');
        var qtde_a_enviar = form_modal.find("#"+$this.data("campo_qtde_a_enviar")).val();
        var estoque_estabelecimento_origem = $this.data("estoque_estabelecimento_origem");
        var array_estabelecimentos = [];

        form_modal.find("#transportadora_rondonia-"+projeto).hide();
        form_modal.find("#transportadora_tocantins-"+projeto).hide();
        form_modal.find("#transportadora_matriz-"+projeto).hide();
        form_modal.find("#transportadora_almirante_2-"+projeto).hide();

        projeto_obj[projeto].forEach(function imprimir(item){
            if(form_modal.find("#estabelecimento-"+item).val() != "0"){
                if(array_estabelecimentos.indexOf(form_modal.find("#estabelecimento-"+item).val()) < 0){
                    array_estabelecimentos.push(form_modal.find("#estabelecimento-"+item).val());
                }
            }
        });

        array_estabelecimentos.forEach(function liberar(item){
            switch (item){
                case "3":
                    form_modal.find("#transportadora_rondonia-"+projeto).show();
                    break;
                case "4":
                    form_modal.find("#transportadora_tocantins-"+projeto).show();
                    break;
                case "5":
                    form_modal.find("#transportadora_matriz-"+projeto).show();
                    break;
                case "6":
                    form_modal.find("#transportadora_almirante_2-"+projeto).show();
                    break;
            }
        });

        form_modal.find("#estoque_transf-"+codigo_produto+"-"+projeto).val(estoque);

        if(estabelecimento != "0" && typeof qtde_a_enviar !== "undefined"){
            qtde_a_enviar = qtde_a_enviar.replace(/\./g,"").replace(/\,/g, ".");
            qtde_a_enviar = parseFloat(qtde_a_enviar);

            estoque_estabelecimento_origem = parseFloat(estoque_estabelecimento_origem);

            estoque = estoque.replace(/\./g,"").replace(/\,/g, ".");
            estoque = parseFloat(estoque);

            var valor = qtde_a_enviar - estoque_estabelecimento_origem;

            if(valor > estoque){
                valor = estoque;
            }
            
            if(valor < 0){
                valor = 0;
            }
            valor = valor.toFixed(2);

            form_modal.find("#qtde_transf-"+codigo_produto+"-"+projeto).val(numberToReal(valor));
        }
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

    function checkboxDesativar($this){
        var checado = $this.prop("checked");
        form_modal.find("input[name='todos_produtos']").each(function(){
            $(this).prop("checked", false);
        });
        $this.prop("checked", checado)
    }   

</script>
@endsection