@extends('layouts.page-dialog')
@section('content')
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link active" id='pedido-web-header-tab' data-toggle="tab" href="#pedido_web_header" role="tab" aria-controls="pedido_web_header" aria-selected="true">Dados do Cliente</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" id="pedido-web-itens-tab" data-toggle="tab" href="#pedido_web_itens" role="tab" aria-controls="pedido_web_itens" aria-selected="true">Produtos</a>
	</li>
</ul>
<div class="tab-content pt-3" id="PedidoHeaderContainer">
	<div class="tab-pane show active" id="pedido_web_header" role="tabpanel" aria-labelledby="dados-tab">
		<form action="#" method="post" id="cadPedido" name="cadpedido" class="cadPedido" onsubmit="return false">
		    @csrf
			{{ Form::hidden('id', $pedido['id'], ['id' => 'id'])}}
            {{ Form::hidden('dados_cliente_id', $dados_cliente_id, ['id' => 'dados_cliente_id'])}}
            {{ Form::hidden('pedido', '', ['id' => 'pedido'])}}
            {{ Form::hidden('quantidade', '', ['id' => 'quantidade'])}}
            {{ Form::hidden('preco_unitario', '', ['id' => 'preco_unitario'])}}
            {{ Form::hidden('produto_codigo', $produto_codigo, ['id' => 'produto_codigo'])}}
			{{ Form::hidden('check_cliente_balcao', $pedido['cliente_balcao'], ['id' => 'check_cliente_balcao'])}}
            {{ Form::hidden('estabelecimento', '', ['id' => 'estabelecimento']) }}
            {{ Form::hidden('observacao', '', ['id' => 'observacao']) }}
            {{ Form::hidden('estabelecimento_pedido', $pedido['estabelecimento'], ['id' => 'estabelecimento_pedido']) }}
			{{ Form::hidden('cliente_sem_telefone', $pedido['cliente_sem_telefone'], ['id' => 'cliente_sem_telefone']) }}
            {{ Form::hidden('cliente', true, ['id' => 'cliente'])}}
            {{ Form::hidden('book', false, ['id' => 'book'])}}
            {{ Form::hidden('transportadora', '', ['id' => 'transportadora']) }}
            {{ Form::hidden('transportadora_redespacho', '', ['id' => 'transportadora_redespacho']) }}
            {{ Form::hidden('adicionar_transportadora', '', ['id' => 'adicionar_transportadora'])}}
			<div class="form-row">
				<div class="form-group col-sm-6">
					{{ Form::label('nome_cliente', 'Cliente') }} 
					<div class="input-group" id="cod_cliente_group">
                        @if(!empty($pedido['id']))
						    {{ Form::text('nome_cliente', $pedido['cliente_descricao'], ['id' => 'nome_cliente', 'class' => 'form-control essencial input-label', 'placeholder' => '', 'readonly']) }}
                        @else
                            {{ Form::text('nome_cliente', $pedido['cliente_descricao'], ['id' => 'nome_cliente', 'class' => 'form-control essencial input-label', 'placeholder' => '']) }}
                        @endif
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
					{{ Form::label('data_previsao_entrega', "Previsão de entrega / Data para Liberação", []) }}  
					{{ Form::text('data_previsao_entrega', $pedido['data_previsao_entrega'], ['id' => 'data_previsao_entrega', 'class' => 'form-control']) }}
				</div>
				<div class="form-group col-sm-12 col-lg-6 content-not-estabel cliente_balcao condicao_pagamento">
					{{ Form::label('condicao_pagamento_descr', 'Condição de pagamento', []) }} 
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
					{{ Form::label('transportadora_tipo_frete', 'Tipo de frete', []) }} 
					{{ Form::select('transportadora_tipo_frete', $tipo_frete, $pedido['tipo_frete'], ['id' => 'transportadora_tipo_frete', 'class' => 'form-control essencial']) }}
				</div>
		    	<div class="form-group col-sm-2">
					{{ Form::label('valor_frete', 'Valor do Frete', []) }} 
			        {{ Form::text('valor_frete', $pedido['valor_frete'], ['id' => 'valor_frete', 'class' => 'form-control text-right']) }}
		    	</div>
                <div class="form-group hide-on-start col-sm-6">
                    {{ Form::label('transportadora_redespacho_tipo_frete', 'Tipo de frete redespacho', []) }}
                    {{ Form::select('transportadora_redespacho_tipo_frete', $tipo_frete, '', ['id' => 'transportadora_redespacho_tipo_frete', 'class' => 'form-control']) }}
                </div>
                <div class="form-group col-sm-2">
                    {{ Form::label('valor_frete_redespacho', 'Valor do Frete', []) }}
                    {{ Form::text('valor_frete_redespacho', $pedido['valor_frete_redespacho'], ['id' => 'valor_frete_redespacho', 'class' => 'form-control text-right']) }}
                </div>
			</div>
		    <div class="form-row hide-on-start">
		        <div class="form-group col-sm-4">
                    @if($pedido['cliente_balcao'] == true)
					{{ Form::label('nome_contato', 'Nome do contato', []) }}
                    @else
                    {{ Form::label('nome_contato', 'Nome do cliente', []) }}
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
					{{ Form::label('cliente_telefone', 'Telefone cliente', []) }} 
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
				<div class="col-sm-2 metragem_exata">
					<div class="form-check">
						{!! Form::checkbox('metragem_exata', 'true', $pedido['metragem_exata'], ['id' => 'metragem_exata', 'class' => 'form-check-input']) !!}
						{!! Form::label('metragem_exata', 'Metragem Exata', ['class' => 'form-check-label']) !!}
					</div>
				</div>
		    </div>
			{{ Form::hidden('observacao', $pedido['observacao'], ['id' => 'observacao'])}}
		    <div class="row">
				<div class="col-sm-12">
					<button type="button" id="bt_ir_para_produtos" class="btn btn-success float-right">Confirmar</button>
				</div>
			</div>
		</form>
	</div>
    <div class="tab-pane" id="pedido_web_itens" role="tabpanel" aria-labelledby="dados-tab">
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped nowrap" style="width:100%" id="table-filters-pedidos-itens">
                    <thead>
                        <tr>
                            <th>Estabelecimento</th>
                            <th>Código</th>
                            <th class="tb_number">Quantidade</th>
                            <th class="tb_number">Preço Venda</th>
                            <th class="td_acao"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($produto as $value)
                        <tr>
                            <td>{{ $value['estabelecimento_tabela'] }}</td>
                            <td>{{ $value['codigo_produto'] }}</td>
                            <td><input id="quantidade" class="form-control text-right number" style="height: inherit" name=quantidade type="text" value="{{ $value['estoque'] }}" onchange="guardarValorQuantidade($(this))" autocomplete="off"></td>
                            <td><input id="preco" class="form-control text-right number" style="height: inherit" name="preco_unitario" type="text"  value="{{ $value['preco'] }}" onchange="guardarValorPreco($(this))" autocomplete="off"></td>
                            <td>
                                <a href="#" data-toggle="tooltip" data-html="true" title="Adicionar Carrinho" data-route="{{ route('book_virtual_exibicao.adicionar') }}" data-estabelecimento="{{ $value['estabelecimento'] }}" data-preco_original="{{ $value['preco'] }}" data-quantidade_original="{{ $value['estoque'] }}" class="bt-carrinho-preco"></a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row float-left my-2">
            <div class="col-lg-12">
                <button type="button" id="bt_ir_para_cabecalho" class="btn btn-info troca-aba float-right"> << Dados do Cliente</button>
            </div>
        </div>
    </div>
<script type="text/javascript">
	table_produtos = '';
    var $quantidade = '';
    var $preco = '';
    var tem_cliente = 'false';
    
    var condicoes_especiais = {!! json_encode($condicoes_especiais) !!};
    var tipo_pronta_entrega = {!! json_encode($tipo_pronta_entrega) !!};
	var campos_not_validate = ['enfestar', 'bater_amostra', 'incluir_cartelas', 'metragem_exata'];
	var campos_check_not_validate = ['enfestar', 'bater_amostra', 'incluir_cartelas', 'metragem_exata'];
    
    $(document).ready( function () {
        init();
    });

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
        @if($pedido['blacklist'] == true)
            showClienteBloqueado();
        @endif
        @if (!empty($pedido['id']) || !empty($dados_cliente_id))
            $(document).find('#pedido-web-itens-tab').tab("show");
            $(document).find("#pedido-web-itens-tab").removeClass('disabled')
            $(document).find(".number").maskMoney({thousands:'', decimal:','});
        @else
            $(document).find("#pedido-web-itens-tab").addClass('disabled')
        @endif

        $(document).find('.producao').popover({
            container: 'body',
            html: true,
            show: true,
            trigger: 'hover click'
        });

        $('.modal').on('hidden.bs.modal', function () {
            if (tem_cliente == 'true'){
                @if(empty($dados_cliente_id))
                    window.location.reload()
                @endif
            }
        })

        $(document).find(".bt-carrinho-preco").off("click");
        $(document).find(".bt-carrinho-preco").on("click", function(e){
            e.preventDefault();
            $(document).find("#adicionar_transportadora").val(false);
            adicionarCarrinho($(this), $quantidade, $preco);
		});

		table_filters_produtos_options = {
			"searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "responsive" : true,
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
                { "class": "tb_date", targets: "tb_acao" },
			],
			"order": [[ 1, 'asc' ]]
		};
		table_produtos = '';
		table_produtos = $(document).find('#table-filters-pedidos-itens').DataTable(table_filters_produtos_options);
        table_produtos.on('draw', function () {
            $(document).find(".bt-carrinho-preco").off('click');
            $(document).find(".bt-carrinho-preco").on('click', function(){
                event.stopPropagation();
                $(document).find("#adicionar_transportadora").val(false);
                adicionarCarrinho($(this), $quantidade, $preco);
            });
        });

        $(document).find('#transportadora_tipo_frete').off('change');
        $(document).find('#transportadora_tipo_frete').on('change', function(){
            if($(document).find('#transportadora_tipo_frete').val() != ''){
                exibirValorFrete()
                $(document).find('.hide-on-start').show();
            }else{
                $(document).find('.hide-on-start').hide();
            }
        });
		
        $(document).find(".content_pecas").hide();

    }

    function checkTipoVenda(){
    	if(
            $(document).find('#tipo_venda').val() == 'pronta_entrega_triangular'
        ){
    		$(document).find('#cliente_conta_e_ordem_div').show();
        }
        else {
    		$(document).find('#cliente_conta_e_ordem_div').hide();
        }
        data_entrega();
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

	function initMaskCampos(){

       datepicker_options = {
			format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
			startDate: new Date(),
		};

		
        $(document).find("#data_previsao_entrega").datepicker(datepicker_options);
		$(document).find("#data_previsao_entrega").mask("00/00/0000");

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

        $(document).find("#data_previsao_entrega").datepicker(datepicker_options);
        $(document).find("#data_previsao_entrega").mask("00/00/0000");
	}

	function initFunctionsOn(){

		$(document).find("#bt-search-cliente").off("click");
		$(document).find("#bt-search-cliente").on("click", function(event){
            event.stopPropagation();
			showModalCliente($(this).data("route"));
            return false;
		});
        $(document).find("#bt-view-condicao").off("click");
        $(document).find("#bt-view-condicao").on("click", function(event){
            event.stopPropagation();
            modalCondicao($(this).parent());
            return false;
        });
		$(document).find("#bt_ir_para_produtos").off("click");
        $(document).find("#bt_ir_para_produtos").on("click", function(e){
            e.preventDefault();
            @if(empty($dados_cliente_id))
                if(tem_cliente == 'false'){
                    adicionarCliente();
                }else{
                    editarCliente();
                }
            @else
                editarCliente();
            @endif
		});

        $(document).find(".troca-aba").off("click");
        $(document).find(".troca-aba").on("click", function(e){
            e.preventDefault();
            if($(this).attr("id") === "bt_ir_para_cabecalho"){
                $(document).find("#pedido-web-header-tab").tab("show");
            }
            $(document).find(".tooltip").each(function(index, el) {
                $(document).find("[aria-describedby="+$(this).attr('id')+"]").tooltip('hide');
            });
		});

        $(document).find("#tipo_venda").off('change');
		$(document).find("#tipo_venda").on('change', function(event){
            if($(this).attr('readonly')){
                $(document).find('#tipo_venda').val('pronta_entrega_venda');
                return false;
            }
			checkTipoVenda();
		});
        $(document).find("#bt-search-cliente-conta-e-ordem").off("click");
		$(document).find("#bt-search-cliente-conta-e-ordem").on("click", function(event){
            event.stopPropagation();
			showModalClienteContaEOrdem($(this).data("route"));
            return false;
		});

        exibirValorFrete();
        exibirValorFreteRedespacho();
        
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


        $(document).find("#bt-search-produto_base").on('click', function(){
            var form_modal = $(document).find('#form_filter_itens');
            showModalProdutoTecidoBaseModal(form_modal);
        });
        $(document).find("#bt-search-produto_desenho").on('click', function(){
            var form_modal = $(document).find('#form_filter_itens');
            showModalProdutoModal(form_modal, "grupo", "Desenho Estamparia Digital", "desenho");
        });
        
	}

	function initAutoCompletes(){
        $(document).find("#nome_cliente").autocomplete(optionsAutoCompleteCliente());
        $(document).find("#nome_cliente_conta_e_ordem").autocomplete(optionsAutoCompleteClienteContaEOrdem());
        $(document).find("#condicao_pagamento_descr").autocomplete(optionsAutoCompleteCondicoes());
        $(document).find("#produto_descricao").autocomplete(optionsAutoCompleteProdutoDescricao());
	}


    function optionsAutoCompleteCliente(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_adicionar_carrinho').css('z-index')) + 1));
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
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_adicionar_carrinho').css('z-index')) + 1));
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
    
    function optionsAutoCompleteCondicoes(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('condicoes_pagamento_web.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_adicionar_carrinho').css('z-index')) + 1));
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
                $(document).find('.ui-autocomplete').css("z-index", (parseInt($('#modal_adicionar_carrinho').css('z-index')) + 1));
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_adicionar_carrinho').css('z-index')) + 1));
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_adicionar_carrinho').css('z-index')) + 1));
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
        $(document).find("#codigo_cliente").val($dados.find("td").eq(0).text());
        $(document).find("#nome_cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
        checkNomeCliente($(document).find("#nome_cliente"));

	}
	
    function showModalClienteContaEOrdem(url){
		var title = "Busca de Clientes conta e ordem ";
        $.ajax({
            url: url,
            type: 'POST',
           data: {_token: '{{ csrf_token() }}', conta_ordem: true},
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
    }

    function modalCondicao($this){
        $.ajax({
            url: $this.data('route'),
            type: 'POST',
           data: {_token: '{{ csrf_token() }}'},
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

	}

    function modalAdicionarTransportadora(){
        xhr = $.ajax({
            url: '{{ route("book_virtual_exibicao.modal.adicionar_transportadora") }}',
            data: {
                _token: "{{ csrf_token() }}"
            },
            method: 'POST',
            success: function(body){
                createModal("modal_adicionar_transportadora", 'Adicionar Transportadora', body, '');
            }
        });
    }   

    function filtroPrecos(){
        limparMesagemErroAdd();
        form = $(document).find('#cadPedido');
        $.ajax({
            url: '{{ route('book_virtual_exibicao.filtro_precos') }}',
            type: 'POST',
            dataType: 'json',
            data: form.serialize(),
            success: function (data){
                if(data.status === 'success'){
                    tem_cliente = 'true';

                    if(data.response.saida == true){
                        table_produtos.clear().draw();
                        
                        linhas = [];

                        for (var fields in data.response.precos){
                            temp_array = [
                                data.response.precos[fields].estabelecimento_tabela,
                                data.response.precos[fields].codigo_produto,
                                inputQuantidade(data.response.precos[fields]),
                                inputPreco(data.response.precos[fields]),
                                exibirCarrinho(data.response.precos[fields]),
                            ];
            
                            linhas.push(temp_array);    
                        }
                        table_produtos.rows.add(linhas).draw();
                        $(document).find("#pedido-web-itens-tab").removeClass('disabled');
                        $(document).find('#pedido-web-itens-tab').tab("show");
                        $(document).find(".number").maskMoney({thousands:'', decimal:','});
                    }else{
                        message("Atenção", "Produto sem estoque pronto entrega!");
                        $(document).find('.modal').modal('hide');
                        window.location.reload();
                    }
                }
            },
            error: function (callback){
                var dados = callback.responseJSON;
                message("Atenção", dados.message);
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
    }

    function adicionarCarrinho($this, $quantidade, $preco, $transportadora = null, $transportadora_redespacho = null){
        limparMesagemErroAdd();
        var $url = $this.data('route');
        var $preco_original = $this.data('preco_original');
        var quantidade_original = $this.data('quantidade_original');
        var $estabelecimento = $this.data('estabelecimento');
        var $id = $this.data('id');
        var $metragem_exata = $(document).find('#metragem_exata');
        var $codigo_produto = $(document).find('#produto_codigo').val();
        
        $(document).find('#estabelecimento').val($estabelecimento);

        if($preco == ''){
            $(document).find('#preco_unitario').val($preco_original);
        }else{
            $(document).find('#preco_unitario').val($preco);
        }

        if($quantidade == ''){
            $(document).find('#quantidade').val(quantidade_original);
        }else{
            $(document).find('#quantidade').val($quantidade);
        }

        if($transportadora != null){
            $(document).find("#adicionar_transportadora").val(true);
            $(document).find("#transportadora").val($transportadora);
            $(document).find("#transportadora_redespacho").val($transportadora_redespacho);
        }

        console.log($(document).find("#transportadora_redespacho").val());

        elementos = $this;
        $(document).find('#tipo_venda').val('pronta_entrega_venda');
        $(document).find('#pedido').val($id);

        if($(document).find('#id').val() == $(document).find('#dados_cliente_id').val()){
            $(document).find('#id').val('');
        }

        $(document).find('#observacao').val($metragem_exata);
        form = $(document).find('#cadPedido');
        $.ajax({
            url: $url,
            type: 'POST',
            dataType: 'json',
            data: form.serialize(),
            success: function (data){
                if(data.status === 'success'){
                    if(data.response.transportadora == false){
                        modalAdicionarTransportadora();
                    }
                    else if(data.response.saida == true){
                        message("Atenção", "Produto adicionado com suceso!");

                        table_produtos.clear().draw();
                        
                        linhas = [];

                        for (var fields in data.response.precos){
                            temp_array = [
                                data.response.precos[fields].estabelecimento_tabela,
                                data.response.precos[fields].codigo_produto,
                                inputQuantidade(data.response.precos[fields]),
                                inputPreco(data.response.precos[fields]),
                                exibirCarrinho(data.response.precos[fields]),
                            ];
            
                            linhas.push(temp_array);    
                        }
                        table_produtos.rows.add(linhas).draw();
                        $(document).find(".number").maskMoney({thousands:'', decimal:','});
                        dados($dados, 1);
                        $(document).find('#modal_adicionar_transportadora').modal('hide');
                    }else{
                        message("Atenção", "Produto adicionado com suceso!")
                        if(book_liso == true){
                            atualizarProdutoBook($codigo_produto);
                        }
                        
                        $(document).find('.modal').modal('hide');
                        loader();
                        dados($dados, 1);
                    }
                }
            },
            error: function (callback){
                var dados = callback.responseJSON;
                if(Object.keys(dados).length > 0){
                    for(var field in dados.errors){
                        message("Atenção", dados.errors[field]);
                    }
                }
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
    }

    function adicionarCliente(){
        limparMesagemErroAdd();
        form = $(document).find('#cadPedido');
        $.ajax({
            url: '{{ route('book_virtual_exibicao.adicionar_cliente') }}',
            type: 'POST',
            dataType: 'json',
            data: form.serialize(),
            success: function (data){
                if(data.status === 'success'){
                    message("Atenção", "Cliente adicionado com sucesso!");
                    @if($informar_cliente == 'false')
                        $(document).find('#dados_cliente_id').val(data.response.cliente);
                        filtroPrecos();
                    @else
                        window.location.reload();
                    @endif
                }
            },
            error: function (callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
    }

    function editarCliente(){
        limparMesagemErroAdd();
        form = $(document).find('#cadPedido');
        $.ajax({
            url: '{{ route('book_virtual_exibicao.editar_cliente') }}',
            type: 'POST',
            dataType: 'json',
            data: form.serialize(),
            success: function (data){
                if(data.status === 'success'){
                    message("Atenção", "Dados atualizados com sucesso!");
                    filtroPrecos();
                }
            },
            error: function (callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
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
        limparMesagemErroAdd();
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
                        $(document).find("#nome_contato").val(callback_ultimos_dados.nome_contato);
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
                    }
                });
                
            }
        });

    }
	
	
    function limparMesagemErroAdd(){      
        var form = $(document).find("#cadPedido");
        form.find('.error-message').remove();
        form.find('input, select, span').removeClass('error-input');
        $(document).find("#pedido_web_itens").find(".error-message").remove();
    }

    function mensagemErroAdd(json_error){
        var form = $(document).find("#cadPedido");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form, input, message){
        if(input == 'quantidade' || input == 'preco_unitario' ){
            $(document).find(".content-dialog-table").append().after("<label class='error-message'>"+message+"</label>");
        }else{
            var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
            $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
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
        }
        else if(campos_cliente_conta_e_ordem.indexOf(input) != -1){
	        $(document).find('#bt-search-cliente-conta-e-ordem').after("<label class='error-message' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#bt-search-cliente-conta-e-ordem').addClass('error-input');
	        $(document).find('#nome_cliente_conta_e_ordem').addClass('error-input');
	        $(document).find('#codigo_cliente_conta_e_ordem').addClass('error-input');
			$(document).find('.hide-on-start').hide();
        }
        else if(campos_transportadora.indexOf(input) != -1){
	        $(document).find('#bt-search-transportadora').after("<label class='error-message' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#bt-search-transportadora').addClass('error-input');
	        $(document).find('#transportadora_nome').addClass('error-input');
	        $(document).find('#transportadora').addClass('error-input');
			$(document).find('.hide-on-start').hide();
        }
        else if(campos_transportadora_redespacho.indexOf(input) != -1){
	        $(document).find('#bt-search-transportadora_redespacho').after("<label class='error-message' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#bt-search-transportadora_redespacho').addClass('error-input');
	        $(document).find('#transportadora_redespacho_nome').addClass('error-input');
	        $(document).find('#transportadora_redespacho').addClass('error-input');
            $(document).find('.hide-on-start').hide();
            $(document).find('#transportadora_redespacho').parent().parent().show();
        }
        else if(campos_condicao_pagamento.indexOf(input) != -1){
	        $(document).find('#bt-search-condicao_pagamento').after("<label class='error-message' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#bt-search-condicao_pagamento').addClass('error-input');
	        $(document).find('#condicao_pagamento').addClass('error-input');
	        $(document).find('#condicao_pagamento_descr').addClass('error-input');
			$(document).find('.hide-on-start').hide();
        }
    	else{

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
                var field = [
                    data.codigo,
                    data.descricao,
                    data.quantidade,
                    data.preco_unitario,
                    data.valor_total,
                    data.comissao,
                    createBtEditarProduto(data),
                    createBtExcluirProduto(data),
                ];
                table_produtos.row.add(field).draw();

                $(document).find('#preco_antes').val(data.preco_unitario);
                $(document).find('#comissao_antes').val(data.comissao);

                $(document).find("#produto_codigo_base").val('');
                $(document).find("#produto_descricao_base").val('');
                $(document).find("#produto_codigo_desenho").val('');
                $(document).find("#produto_descricao_desenho").val('');

                $(document).find("#produto_codigo").val('');
                $(document).find("#produto_descricao").val('');
                $(document).find("#quantidade").val('');
                $(document).find("#preco_unitario").val('');
                $(document).find("#estoque_disponivel").val('');
                $(document).find("#coluna").val('');

                $(document).find('#ultimas-vendas-div').hide();
                $(document).find('#alerta_preco_pedido').hide();
                $(document).find('#alerta_preco_pedido').text("*");
				
				$(document).find("label[for='produto_descricao']").html('Descrição');


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
            $(document).find("#produto_descricao").val('');

            $(document).find("#quantidade").val('');
            $(document).find("#preco_unitario").val('');
            $(document).find("#coluna").val('');
            $(document).find("#estoque_disponivel").val('');

            $(document).find('#ultimas-vendas-div').hide();

            table_produtos.row.add(window.temp_row).draw();

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


            $(document).find("#produto_pedido_id").val(id);
            $(document).find("#produto_codigo").val(window.temp_row[0]);
            $(document).find("#produto_descricao").val(window.temp_row[1]);
            $(document).find("#coluna").val(comissao);
            $.ajax({
                url: '{{ Route("produto.retorna_estoque_disponivel") }}',
                type: 'POST',
               data: {
                    _token: '{{csrf_token()}}',
                    codigo: window.temp_row[0],
                    pedido: $(document).find("#id").val()
                },
                success: function(callback){
                    $(document).find("#estoque_disponivel").val(callback.estoque_disponivel);
                }
            });
    
        }
        obj.find('.estoque').popover('hide');
        obj.find('.preco').popover('hide');
        row.remove().draw();
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
            },
            error: function(data){
                message('Atenção', data.responseJSON.message);
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
            }
        });
    }

    function salvaEdicaoProduto(){
        form = $(document).find("#form_filter_itens");

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

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
                var field = [
                    data.codigo,
                    data.descricao,
                    data.quantidade,
                    data.preco_unitario,
                    data.valor_total,
                    data.comissao,
                    createBtEditarProduto(data),
                    createBtExcluirProduto(data),
                ];
                table_produtos.row.add(field).draw();
        
                $(document).find("#btn-create-itens-pedido").html('Inserir produto');
                $(document).find("#btn-cancel-itens-pedido").hide();

                $(document).find("#produto_codigo_base").val('');
                $(document).find("#produto_descricao_base").val('');
                $(document).find("#produto_codigo_desenho").val('');
                $(document).find("#produto_descricao_desenho").val('');

                $(document).find("#produto_pedido_id").val('');
                $(document).find("#produto_codigo").val('');
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

    function salvarPedido(){
        form = $(document).find('#cadPedido');
        table = $(document).find('#table-filters-pedidos-itens');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route('pedido_portal.salvarPedido') }}',
            type: 'POST',
            data: form.serialize(),
            success: function(callback){
                $(document).find('#modal_adicionar_carrinho').modal('hide');
                message('Sucesso', callback.message);
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
                    data.response.itens[field].preco_unitario,
                    data.response.itens[field].valor_total,
                    data.response.itens[field].comissao,
                    createBtEditarProduto(data.response.itens[field]),
                    createBtExcluirProduto(data.response.itens[field])
                ];
                fields_filter.push(temp_field);
            }
            table_produtos.rows.add(fields_filter).draw().nodes();
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
		});

		$(document).off("mensagem_presencial_nao");
		$(document).on("mensagem_presencial_nao", function(){
			$(document).find('#presencial').val('false');
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

    function inputQuantidade($value){
        var html = "<input id=\"quantidade_"+$value.estabelecimento+"\" class=\"form-control text-right number\" style=\"height: inherit\" name=\"quantidade\" type=\"text\" value=\""+$value.estoque+"\" onchange=\"guardarValorQuantidade($(this))\" autocomplete=\"off\">";

        return html;
    }

    function inputPreco($value){
        var html = "<input id=\"preco_"+$value.estabelecimento+"\" class=\"form-control text-right number\" style=\"height: inherit\" name=\"preco_unitario\" type=\"text\"  value=\""+$value.preco+"\" onchange=\"guardarValorPreco($(this))\" autocomplete=\"off\">";

        return html;
    }

    function exibirCarrinho($value){
        var html = "<a href=\"#\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Adicionar Carrinho\" data-route=\"{{ route('book_virtual_exibicao.adicionar') }}\" data-estabelecimento=\""+$value.estabelecimento+"\" data-preco_original=\""+$value.preco+"\" data-quantidade_original=\""+$value.estoque+"\" class=\"bt-carrinho-preco\"></a>"
        return html;
    }

    function guardarValorQuantidade($this){
        $quantidade = $this.val();
        return $quantidade;
    }

    function guardarValorPreco($this){
        $preco = $this.val();
        return $preco;
    }

</script>
@endsection
