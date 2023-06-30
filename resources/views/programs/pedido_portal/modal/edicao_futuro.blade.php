
@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">
    <div class="row">
        <div class="col-sm-3">
            <strong>Estabelecimento: </strong>{{ $pedido['estabelecimento'] }}
        </div>
        <div class="col-sm-3">
            <strong>Previsão de entrega: </strong>{{ $pedido['previsao_entrega'] }}
        </div>
        <div class="col-sm-3">
            <div name="cliente_destino" id="cliente_destino"><strong>Destino: </strong>{{ $pedido['cliente_destino'] }}</div>
        </div>
    </div>
    <div class="row"><hr class="linha_divisao"></div>
</div>
<div class="row col-sm-12"><hr class="linha_divisao"></div>
<form id="frm_pedido" name="frm_pedido" action="#" onsubmit="return false">
    @csrf
    {!! Form::hidden('pedido', $pedido['id']) !!}
    <div class="form-row">
        <div class="form-group col-sm-8">
            {{ Form::label('cliente_nome', 'Cliente', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
            <div class="input-group" id="transporadora_group">
                {{ Form::text('cliente_nome', $pedido['cliente'], ['id' => 'cliente_nome', 'class' => 'form-control essencial input-label']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca-modal" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-search-cliente-busca-modal" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="form-group col-sm-4">
            {{ Form::label('tipo_venda', "Tipo de venda", []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span><br>
            <select id="tipo_venda" class="form-control" name="tipo_venda">
                @foreach ($pedido['tipo_venda_lista'] as $key => $value)
                <option value="{{ $key }}" @if( $pedido['tipo_venda'] === $key) selected="selected" @endif >{{ $value }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12" id="cliente_conta_e_ordem_div">
            {{ Form::label('nome_cliente_conta_e_ordem', 'Cliente da conta e ordem') }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
            <div class="input-group" id="cod_cliente_conta_e_ordem_group">
                {{ Form::text('nome_cliente_conta_e_ordem', $pedido['cliente_conta_e_ordem_descricao'], ['id' => 'nome_cliente_conta_e_ordem', 'class' => 'form-control input-label', 'placeholder' => '']) }}
                {{ Form::hidden('codigo_cliente_conta_e_ordem', $pedido['cliente_conta_e_ordem_codigo'], ['id' => 'codigo_cliente_conta_e_ordem', 'class' => '']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-conta-e-ordem" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('condicao_pagamento', 'Condição de Pagamentos', []) }}
            <div class="input-group" id="condicao_pagamento_group">
                {!! Form::text('condicao_pagamento', $pedido['condicao_pagamento'], ['id' => 'condicao_pagamento', 'class' => 'form-control', 'placeholder' => 'Condição de Pagamento']) !!}
                <span class="input-group-addon border rounded-right" id="bt-search-condicao_pagamento" data-route="{{ route("condicoes_pagamento_web.dialog") }}"><i id="bt-view-condicao" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-10">
            {{ Form::label('transportadora_nome', 'Transportadora', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
            <div class="input-group" id="transporadora_group">
                {{ Form::text('transportadora_nome', $pedido['transportadora']['nome'], ['id' => 'transportadora_nome', 'class' => 'form-control essencial input-label']) }}
                {{ Form::hidden('transportadora', $pedido['transportadora']['codigo'], ['id' => 'transportadora', 'class' => 'form-control']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-transportadora" data-route="{{ route("transportador.index.dialog") }}"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="form-group col-sm-2">
            {{ Form::label('transportadora_tipo_frete', 'Tipo de frete', []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
            {{ Form::select('transportadora_tipo_frete', $pedido['tipo_frete_list'], $pedido['tipo_frete'], ['id' => 'transportadora_tipo_frete', 'class' => 'form-control essencial']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-grou col-sm-10">
            {{ Form::label('transportadora_redespacho_nome', 'Transportadora Redespacho', []) }}
            <div class="input-group" id="cod_cliente_group">
                {{ Form::text('transportadora_redespacho_nome', $pedido['transportadora_redespacho']['nome'], ['id' => 'transportadora_redespacho_nome', 'class' => 'form-control input-label']) }}
                {{ Form::hidden('transportadora_redespacho', $pedido['transportadora_redespacho']['codigo'], ['id' => 'transportadora_redespacho', 'class' => 'form-control']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-transportadora_redespacho" data-route="{{ route("transportador.index.dialog") }}"><i id="bt-view-transportadora-redespacho" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="form-group hide-on-start col-sm-2">
            {{ Form::label('transportadora_redespacho_tipo_frete', 'Tipo de frete', []) }}
            {{ Form::select('transportadora_redespacho_tipo_frete', $pedido['tipo_frete_list'], $pedido['tipo_frete_redespacho'], ['id' => 'transportadora_redespacho_tipo_frete', 'class' => 'form-control']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-6">
            {{ Form::label('nome_comprador', 'Nome do contato', []) }}
            {{ Form::text('nome_comprador', $pedido['nome_comprador'], ['id' => 'nome_comprador', 'class' => 'form-control', 'maxlength' => '50']) }}
        </div>
        <div class="form-group col-sm-6 col-lg-3">
            {{ Form::label('email_comprador', 'Email do contato', []) }}
            {{ Form::text('email_comprador', $pedido['email_comprador'], ['id' => 'email_comprador', 'class' => 'form-control', 'maxlength' => '50']) }}
        </div>
        <div class="form-group col-sm-12 col-lg-3">
            {{ Form::label('no_pedido_compra', 'Pedido compra', []) }}
            {{ Form::text('no_pedido_compra', $pedido['no_pedido_compra'], ['id' => 'no_pedido_compra', 'class' => 'form-control']) }}
        </div>
    </div>
    <div class="form-row observacao_pedido">
        <div class="form-group col-sm-12">
            {{ Form::label('observacao', 'Observação', []) }}
            {!! Form::textarea('observacao', $pedido['observacao'], ['maxlength' => '230', 'rows' => '2', 'id' => 'observacao', 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped table-filter-pedido-itens" id="table-filters-pedidos-itens">
                <thead>
                    <tr>
                        <th class="td_codigo_produto">Código</th>
                        <th>Descrição</th>
                        <th class="tb_number td_quantidade">Pronta Entrega</th>
                        <th class="tb_number td_quantidade">Compra</th>
                        <th class="td_quantidade_input">Quantidade</th>
                        <th class="td_quantidade_input">Preço Unitário</th>
                        <th class="tb_number td_quantidade">Valor Total</th>
                        <th class="td_acao"></th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($pedido['itens']))
                        @foreach ($pedido['itens'] as $item)
                        <tr class="{{ $item['linha'] }}" id="line_produto_{{ $item['id'] }}">
                            <td>{{ $item['codigo'] }}</td>
                            <td>{!! $item['descricao'] !!}</td>
                            <td>
                                {!! Form::hidden('item_estoque_pronta_entrega_'.$item['id'], $item['estoque_pronta_entrega'], ['id' => 'item_estoque_pronta_entrega_'.$item['id']]) !!}
                                {!! $item['estoque_pronta_entrega'] !!}
                            </td>
                            <td>
                                {!! Form::hidden('item_estoque_compra_'.$item['id'], $item['estoque_compra'], ['id' => 'item_estoque_compra_'.$item['id']]) !!}
                                {!! $item['estoque_compra'] !!}
                            </td>
                            <td>{!! Form::text('item['.$item['id'].']', $item['quantidade'], ['id' => 'item_'.$item['id'], 'class' => 'quantidade form-control text-right', 'data-id' => $item['id'], 'placeholder' => 'Quantidade', 'maxlength' => '12']) !!}</td>
                            <td>{!! Form::text('preco_unitario['.$item['id'].']', $item['preco_unitario'], ['id' => 'preco_unitario_'.$item['id'], 'class' => 'preco_unitario form-control text-right', 'data-id' => $item['id'], 'placeholder' => 'Preço Unitário', 'maxlength' => '12']) !!}</td>
                            <td><div id="preco_unitario_total_{{$item['id']}}" name="preco_unitario_total['{{$item['id']}}']">{!! $item['preco_unitario_total'] !!}</div></td>
                            <td><a href="#" class="bt-delete" title='Excluir' onclick="produtoExcluir($(this).parents('tr'), '{{ $item['id'] }}')"></a></td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <div class="col-sm-12 mt-5" id="button-bottom">
            <button name="btn-salvar" id="btn-salvar" class="btn btn-success float-right">Salvar Alteração</button>
        </div>
    </div>
</form>
<script type="text/javascript">
	table_produtos = '';
    init();
    
	function init(){
        initMaskCampos();
        initFunctionsOn();
        initAutoCompletes();

        form_edicao_futuro = $(document).find('#frm_pedido');

        form_edicao_futuro.find('.observacao_pedido').hide();

		table_filters_produtos_options = {
			"searching": false,
			"lengthChange": false,
			"info": false,
			"paging": false,
			"pageLength": 15,
			"processing": true,
			"orderMulti": false,
			"scrollCollapse": true,
            "scrollY": "65vh",
            "autoWidth": false,
			"drawCallback": function(settings) {
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
                {
                    "class": "tb_number",
                    "type": "num-fmt",
                    "targets": "tb_number",
                    "width": "120px",
                },
                {
                    "targets": "td_acao",
                    "class": "td_acao",
                    "width": "5px",
                    "orderable": false
                },
                {
                    "targets": "td_codigo_produto",
                    "width": "100px"
                },
                {
                    "targets": "td_quantidade_input",
                    "width": "200px",
                    "orderable": false
                },
                
			],
			"order": [[ 0, "asc" ]]
		};
		table_produtos = "";
		table_produtos = $(document).find('#table-filters-pedidos-itens').DataTable(table_filters_produtos_options);
        table_produtos.draw();
        setTimeout(function(){
            table_produtos.draw();
        },300);
    }
    
	function initMaskCampos(){
        $(document).find(".quantidade").maskMoney({thousands:'.', decimal:','});
        $(document).find(".preco_unitario").maskMoney({thousands:'.', decimal:','});
    }

	function initFunctionsOn(){

		$(document).find("#btn-salvar").off("click");
		$(document).find("#btn-salvar").on("click", function(event){
            event.stopPropagation();
			salvar();
            return false;
        });

        $(document).find(".quantidade").off("keyup");
        $(document).find(".quantidade").on("keyup", function(event){
            var id = $(this).data('id');
            var row = $(this).parents('tr');

            var quantidade = $(this).val();
            quantidade = parseFloat((quantidade.replace('.', '')).replace(',', '.'));

            var estoque_compra = row.find('#item_estoque_compra_'+id).val();
            estoque_compra = parseFloat((estoque_compra.replace('.', '')).replace(',', '.'));

            var estoque_pronta_entrega = row.find('#item_estoque_pronta_entrega_'+id).val();
            estoque_pronta_entrega = parseFloat((estoque_pronta_entrega.replace('.', '')).replace(',', '.'));

            $(this).removeClass('error-input');
            $(document).find('.error-message').remove();

            if(quantidade > 0){
                if(estoque_compra > 0){
					if(quantidade > estoque_compra){
						$(this).after("<label class='error-message' for='"+$(this).attr('id')+"'>Quantidade tem que ser até o disponivel na compra</label>");
						$(this).addClass('error-input').addClass('error-quantidade');
						$(this).focus()
					}
                }else if(estoque_pronta_entrega > 0){
					if(quantidade > estoque_pronta_entrega){
						$(this).after("<label class='error-message' for='"+$(this).attr('id')+"'>Quantidade tem que ser até o diponivel no pronta entrega</label>");
						$(this).addClass('error-input').addClass('error-quantidade');
						$(this).focus()
					}
                }
            }else{
	            $(this).after("<label class='error-message' for='"+$(this).attr('id')+"'>Quantidade tem que ser maior que zero</label>");
                $(this).addClass('error-input').addClass('error-quantidade');
                $(this).focus()
            }
        });
        $(document).find(".quantidade").off("blur");
        $(document).find(".quantidade").on("blur", function(event){
            $(document).find('.error-input.error-quantidade').each(function(){
                $(this).focus();
            });
        });

        $(document).find(".preco_unitario").on("keyup", function(event){
            var id = $(this).data('id');
            var row = $(this).parents('tr');

            var preco_unitario = $(this).val();
            preco_unitario = parseFloat((preco_unitario.replace('.', '')).replace(',', '.'));


            $(this).removeClass('error-input');
            $(document).find('.error-message').remove();

            if(preco_unitario <= 0){
	            $(this).after("<label class='error-message' for='"+$(this).attr('id')+"'>Preço Unitário tem que ser maior que zero</label>");
                $(this).addClass('error-input').addClass('error-quantidade');
                $(this).focus()
            }else{
                var quantidade = $(document).find("#item_"+id).val();
                quantidade = parseFloat((quantidade.replace('.', '')).replace(',', '.'));

                total = preco_unitario * quantidade;

                $(document).find("#preco_unitario_total_"+id).html(numberToReal(total.toFixed(2)));
            }
        });

        $(document).find('#transportadora_nome').off('keypress');
        $(document).find('#transportadora_nome').on('keypress', function(){
        	$(document).find('#transportadora').val('');
        });

        $(document).find('#transportadora_redespacho_nome').off('keypress');
        $(document).find('#transportadora_redespacho_nome').on('keypress', function(){
        	$(document).find('#transportadora_redespacho').val('');
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

        $(document).find("#bt-search-cliente-busca-modal").on("click", function(event){
            event.stopPropagation();
            showModalClienteBuscaModal($(this).data("route"));
            return false;
        });

        $(document).find("#bt-view-condicao").off("click");
        $(document).find("#bt-view-condicao").on("click", function(event){
            event.stopPropagation();
            modalCondicao($(this).parent());
            return false;
        });

        checkTipoVenda();

        $(document).find("#tipo_venda").off('change');
		$(document).find("#tipo_venda").on('change', function(event){
			checkTipoVenda();
		});

        $(document).find("#bt-search-cliente-conta-e-ordem").off("click");
		$(document).find("#bt-search-cliente-conta-e-ordem").on("click", function(event){
            event.stopPropagation();
			showModalClienteContaEOrdem($(this).data("route"));
            return false;
		});
    }
	
	function initAutoCompletes(){
        $(document).find("#transportadora_nome").autocomplete(optionsAutoCompleteTransportador());
        $(document).find("#transportadora_redespacho_nome").autocomplete(optionsAutoCompleteTransportadorRedespacho());
        $(document).find('#cliente_nome').autocomplete(optionsAutoCompleteClienteModal('cliente_nome'));
        $(document).find("#condicao_pagamento").autocomplete(optionsAutoCompleteCondicoes());
        $(document).find("#nome_cliente_conta_e_ordem").autocomplete(optionsAutoCompleteClienteContaEOrdem());
	}
    function optionsAutoCompleteTransportador(){
        $(document).find(".error-message.validacao_form").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.estabelecimento = $(document).find('#estabelecimento').val();
                $.post("{{ route('transportador.autocomplete') }}", request, response);
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
                return false;
            }
        };
    }
    function optionsAutoCompleteTransportadorRedespacho(){
        $(document).find(".error-message.validacao_form").remove();

        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.estabelecimento = $(document).find('#estabelecimento').val();
                $.post("{{ route('transportador.autocomplete') }}", request, response);
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
                return false;
            }
        };
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
                table_produtos.row(obj).remove().draw();
            }
        });
    }

    function modalTransportador(){
        $.ajax({
            url: '{{ Route('transportador.index.dialog') }}',
            type: 'POST',
           data: {_token: '{{ csrf_token() }}', estabelecimento: $(document).find('#estabelecimento').val()},
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
	}
	
    function modalTransportadorRedespacho(){
        $.ajax({
            url: '{{ Route('transportador.index.dialog') }}',
            type: 'POST',
           data: {_token: '{{ csrf_token() }}', estabelecimento: $(document).find('#estabelecimento').val()},
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
    }
    
    function salvar(){
        $erro = false;
        $(document).find('.error-input.error-quantidade').each(function(){
            $erro = true;
        });
        if($erro == true){
            return false;
        }
        form = $(document).find('#frm_pedido');
        $.ajax({
            url: '{{ route('pedido_portal.salvar.edicao_futuro') }}',
            type: 'POST',
            data: form.serialize(),
            success: function(callback){
                if(callback.status == 'success'){
                    $(document).find('#modal_pedido_edit').modal('hide');
                    message('Sucesso', callback.message);
                }else{
                    message('Atenção', callback.message);
                }
                
            },
            error: function(callback){
                var errors = callback.responseJSON.error;
                form.find('.error-message.validacao_form').remove();
                if(Object.keys(errors).length > 0){
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field]);
                    }
                }else{
                    message('Atenção', callback.message);
                }
            }
        });
    }
    function showErrorsInputs(form, input, message){
        campos_transportadora = ['transportadora_nome', 'transportadora'];
        campos_transportadora_redespacho = ['transportadora_redespacho_nome', 'transportadora_redespacho'];

        if(campos_transportadora.indexOf(input) != -1){
	        $(document).find('#bt-search-transportadora').after("<label class='error-message validacao_form' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#bt-search-transportadora').addClass('error-input').addClass('validacao_form');
	        $(document).find('#transportadora_nome').addClass('error-input').addClass('validacao_form');
	        $(document).find('#transportadora').addClass('error-input').addClass('validacao_form');
			$(document).find('.hide-on-start').hide();
        }
        else if(campos_transportadora_redespacho.indexOf(input) != -1){
	        $(document).find('#bt-search-transportadora_redespacho').after("<label class='error-message validacao_form' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#bt-search-transportadora_redespacho').addClass('error-input').addClass('validacao_form');
	        $(document).find('#transportadora_redespacho_nome').addClass('error-input').addClass('validacao_form');
	        $(document).find('#transportadora_redespacho').addClass('error-input').addClass('validacao_form');
            $(document).find('.hide-on-start').hide();
            $(document).find('#transportadora_redespacho').parent().parent().show();
        }else if(input.search('preco_unitario_') == 0){
            $(document).find('#'+input).after("<label class='error-message validacao_form' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#'+input).addClass('error-input').addClass('validacao_form');
        }else if(input.search('cliente_nome') == 0){
            $(document).find('#bt-search-cliente-busca-modal').after("<label class='error-message validacao_form' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#bt-search-cliente-busca-modal').addClass('error-input').addClass('validacao_form');
	        $(document).find('#cliente_nome').addClass('error-input').addClass('validacao_form');
        }else if(input.search('condicao_pagamento') == 0){
            $(document).find('#bt-search-condicao_pagamento').after("<label class='error-message validacao_form' for='bt-view-cliente'>"+message+"</label>");
	        $(document).find('#bt-search-condicao_pagamento').addClass('error-input').addClass('validacao_form');
	        $(document).find('#condicao_pagamento').addClass('error-input').addClass('validacao_form');
        }
    	else{
            message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
        }

    }

    function numberToReal(valor) {
        var numero = valor.split('.');
        numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
        return numero.join(',');
    }

    function showModalClienteBuscaModal(url){
        var title = "Busca de Clientes";
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(body){
                $(document).find('#cliente_searsh_show').remove();
                createModal("cliente_searsh_show_modal", title, body, 'modal-lg');
                var modal = $(document).find("#cliente_searsh_show_modal");
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(event){
                            returnDadosClienteBuscaModal($(this), event);
                        });
                    });
                });
            }
        });
    }

    function returnDadosClienteBuscaModal($dados, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente_destino").html("<strong>Destino: </strong>" + $dados.find("td").eq(4).text() + " / " +$dados.find("td").eq(5).text());
        $(document).find("#cliente_nome").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show_modal").modal("hide");
    }

    function optionsAutoCompleteClienteModal($elemento){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", $("#" + $elemento).parents('.modal').css('z-index') + 1);
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
                $(document).find("#" + $elemento).val(ui.item.label);
                $(document).find("#cliente_destino").html("<strong>Destino: </strong>" + ui.item.cidade + " / " + ui.item.estado);
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
                $('.ui-autocomplete').css("z-index", $("#condicao_pagamento").parents('.modal').css('z-index') + 1);
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#condicao_pagamento").val(ui.item.label);
                return false;
            }
        };
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
    
    function returnDadosCondicao($dados,){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#condicao_pagamento").data('oldvalue', $(document).find("#condicao_pagamento").val());
        $(document).find("#condicao_pagamento").val($dados.find("td:eq(1)").text() );
        $(document).find("#modal_busca_condicao").modal("hide");
    }

    function checkTipoVenda(){
    	if(
            $(document).find('#tipo_venda').val() == 'pedido_futuro_triangular' ||
            $(document).find('#tipo_venda').val() == 'producao_triangular' ||
            $(document).find('#tipo_venda').val() == 'rj_x_sp_triangular_futuro' || 
            $(document).find('#tipo_venda').val() == 'pre_pago_producao_triangular'
        ){
    		$(document).find('#cliente_conta_e_ordem_div').show();
        }
        else {
    		$(document).find('#cliente_conta_e_ordem_div').hide();
        }
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
    }
</script>
@endsection
