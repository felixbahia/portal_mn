@extends('layouts.page-dialog')

@section('content')
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link active" id="itens-carrinho-tab" data-toggle="tab" href="#itens_carrinho" role="tab" aria-controls="itens_carrinho" aria-selected="true">Itens Carrinho</a>
	</li>
    <li class="nav-item">
		<a class="nav-link" id="dados-cliente-tab" data-toggle="tab" href="#dados_cliente" role="tab" aria-controls="dados_cliente" aria-selected="false">Dados do Cliente</a>
	</li>
</ul>
<div class="tab-content pt-3" id="carrinhoContainer">
    <div class="tab-pane show active" id="itens_carrinho" role="tabpanel" aria-labelledby="dados-tab">
        <form action="" name="form_visualizar_carrinho" id="form_visualizar_carrinho" onsubmit="return false;">
            <div class="content-filter-dialog">	
                @foreach($dados as $value)
                    <div class="form-row mb-0" id="cancelar_pedido_{{ $value['pedido'] }}">
                        <div class="col-sm-2">
                            <strong>
                                <span id="estabelecimento-exibicao" class='ml-2'>{{ $value['estabelecimento'] }}</span>
                            </strong>
                        </div>
                        <div class="col-sm-1">
                            <span id="venda-futura-exibicao"><a href="#" class="bt-modal" onclick="abrirModalPedido('{{ $value['pedido'] }}')">{{ $value['pedido'] }}</a></span>
                        </div>
                        <div class="col-sm-1">
                            <strong>UF:</strong>
                            <span id="estado_destino">{{ $value['uf'] }}</span>
                        </div>
                        <div class="col-sm-1">
                            <strong>Frete:</strong>
                            <span id="frete">{{ $value['frete'] }} </span>
                        </div>
                        <div class="col-sm-1">
                            <strong>
                                <span id="contribuinte">{{ $value['cliente_pedido'] }} </span>
                            </strong>
                        </div>
                        <div class="col-sm-1">
                            <strong>Prazo Médio:</strong>
                            <span id="parazo_medio">{{ $value['prazo_medio'] }} </span>
                        </div>
                        <div class="col-sm-1">
                            <strong>Valor:</strong>
                            <span id="valor_{{ $value['pedido'] }}">{{ $value['valor'] }} </span>
                        </div>
                        <div class="col-sm-1">
                            <button type="button" id="bt_cancelar_pedido" data-route="{{ route('book_virtual_exibicao.modal.cancelar') }}" data-title="Cancelar Pedido" data-id="{{ $value['pedido'] }}" class="btn btn-danger btn-sm">Excluir</button>
                        </div>
                        <div class="col-sm-1">
                            <a href="#" id="bt_editar_transportadora" data-route="{{ route('book_virtual_exibicao.modal.editar_transportadora') }}" data-title="Editar Transportadora" title="Editar Transportadora" data-id="{{ $value['id'] }}" data-toggle="tooltip" data-placement="top" class="bt-edicao"></a>
                        </div>
                    </div>
                @endforeach
                <div class="form-row mb-0">
                    <div class="col-sm-1">
                        <strong>Valor Total:</strong>
                        <span id="valor_total">{{ $valor_total }} </span>
                    </div>
                </div>
            </div>	
        </form>
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped nowrap" style="width:100%" id="table-filters-carrinho">
                    <thead>
                    <tr>
                        <th> Estabelecimento</th>
                        <th> Código</th>
                        <th class="tb_number">Quantidade</th>
                        <th class="tb_number">Preco</th>
                        <th class="tb_acao"></th>
                        <th class="tb_acao"></th>
                    </tr>
                    </thead>
                    <tbody>
                        @foreach($filtro as $item)
                            <tr>
                                <td>{{ $item['estabelecimento'] }}</td>
                                <td>{{ $item['produto_codigo'] }}</td>
                                <td>
                                    <input id="quantidade" class="form-control text-right number" style="height: inherit" name="quantidade" type="text" value="{{ $item['quantidade'] }}" onchange="guardarValorQuantidade($(this))" autocomplete="off">
                                </td>
                                <td>
                                    <input id="preco" class="form-control text-right number" style="height: inherit" name="preco" type="text"  value="{{ $item['preco'] }}" onchange="guardarValorPreco($(this))" autocomplete="off">
                                </td>
                                <td>
                                    <a href='#' data-id="{{ $item['id'] }}" data-pedido="{{ $item['pedido_id'] }}" data-route="{{ route('book_virtual_exibicao.atualizar_carrinho') }}" data-toggle="tooltip" data-placement="top" title="Atualizar" class="bt-atualizar-carrinho"></a>
                                </td>
                                <td>
                                    <a href='#' data-id="{{ $item['id'] }}" data-route="{{ route('book_virtual_exibicao.modal.deletar') }}"  data-title="Deletar Produto" class="bt-delete" data-toggle="tooltip" data-placement="top" title="Excluir"></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                    </tfoot>
                </table>
            </div>
            <div class="row my-2">
                <div class="col-auto mr-auto">
                        <button type="button" id="bt_excluir" data-route="{{ route('book_virtual_exibicao.modal.excluir') }}" data-title="Excluir Carrinho" data-id="{{ $value['pedido'] }}" class="btn btn-danger float-left">Excluir Todos</button>
                </div>
                <div class="col-auto">
                        <button type="button" id="bt_finalizar" class="btn btn-success float-right">Finalizar Compra</button>
                </div>
            </div>
        </div>   
     </div>
     <div class="tab-pane" id="dados_cliente" role="tabpanel" aria-labelledby="dados-tab">
        <form action="#" method="post" id="form_dados_cliente" name="form_dados_cliente" class="cadPedido" onsubmit="return false">
			<div class="form-row">
				<div class="form-group col-sm-6">
					{{ Form::label('nome_cliente', 'Cliente') }} 
					<div class="input-group" id="cod_cliente_group">
						{{ Form::text('nome_cliente', $pedido['cliente_descricao'], ['id' => 'nome_cliente', 'class' => 'form-control essencial input-label', 'placeholder' => '', 'readonly']) }}
					</div>
				</div>
				<div class="form-group col-md-6 col-lg-3 content-not-estabel cliente_not_balcao">
                    {{ Form::label('tipo_venda', "Tipo de venda", []) }} <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span><br>
                    @if(empty($data_previsao_entrega_original))
                    <select id="tipo_venda" class="form-control" name="tipo_venda" readonly>
                    @else
                    <select id="tipo_venda" class="form-control" name="tipo_venda" readonly>
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
						{{ Form::text('nome_cliente_conta_e_ordem', $pedido['cliente_conta_e_ordem_descricao'], ['id' => 'nome_cliente_conta_e_ordem', 'class' => 'form-control input-label', 'placeholder' => '', 'readonly']) }}
						{{ Form::hidden('codigo_cliente_conta_e_ordem', $pedido['cliente_conta_e_ordem_codigo'], ['id' => 'codigo_cliente_conta_e_ordem', 'class' => '']) }}
					</div>
				</div>
				<div class="form-group col-sm-6">
					{{ Form::label('data_previsao_entrega', "Previsão de entrega / Data para Liberação", []) }}  
					{{ Form::text('data_previsao_entrega', $pedido['data_previsao_entrega'], ['id' => 'data_previsao_entrega', 'class' => 'form-control', 'readonly']) }}
				</div>
				<div class="form-group col-sm-12 col-lg-6">
					{{ Form::label('condicao_pagamento_descr', 'Condição de pagamento', []) }} 
					{{ Form::text('condicao_pagamento_descr', $pedido['condicao_pagamento_descricao'], ['id' => 'condicao_pagamento_descr', 'class' => 'form-control essencial', 'readonly']) }}
				</div>
			</div>
			<div class="form-row">
				<div class="form-group col-sm-6">
					{{ Form::label('transportadora_tipo_frete', 'Tipo de frete', []) }} 
					{{ Form::select('transportadora_tipo_frete', $tipo_frete, $pedido['tipo_frete'], ['id' => 'transportadora_tipo_frete', 'class' => 'form-control essencial', 'readonly']) }}
				</div>
		    	<div class="form-group col-sm-2">
					{{ Form::label('valor_frete', 'Valor do Frete', []) }} 
			        {{ Form::text('valor_frete', $pedido['valor_frete'], ['id' => 'valor_frete', 'class' => 'form-control text-right', 'readonly']) }}
		    	</div>
			</div>
			<div class="form-row">
		    	<div class="form-group col-sm-6">
					{{ Form::label('transportadora_redespacho_tipo_frete', 'Tipo de frete Redespacho', []) }}
			        {{ Form::select('transportadora_redespacho_tipo_frete', $tipo_frete, '', ['id' => 'transportadora_redespacho_tipo_frete', 'class' => 'form-control', 'readonly']) }}
		    	</div>
		    	<div class="form-group col-sm-2">
					{{ Form::label('valor_frete_redespacho', 'Valor do Frete', []) }}
			        {{ Form::text('valor_frete_redespacho', $pedido['valor_frete_redespacho'], ['id' => 'valor_frete_redespacho', 'class' => 'form-control text-right', 'readonly']) }}
		    	</div>
		    </div>
		    <div class="form-row">
		        <div class="form-group col-sm-4">
					{{ Form::label('nome_contato', 'Nome do contato', []) }}
			        {{ Form::text('nome_contato', $pedido['nome_comprador'], ['id' => 'nome_contato', 'class' => 'form-control', 'maxlength' => '50', 'readonly']) }}
		    	</div>
		    	<div class="form-group col-sm-6 col-lg-3">
					{{ Form::label('email_contato', 'Email do contato', []) }}
			        {{ Form::text('email_contato', $pedido['email_comprador'], ['id' => 'email_contato', 'class' => 'form-control', 'maxlength' => '50', 'readonly']) }}
		    	</div>
		    	<div class="form-group col-sm-12 col-lg-3">
                    {{ Form::label('no_pedido_compra', 'Pedido compra', []) }}
			        {{ Form::text('no_pedido_compra', $pedido['no_pedido_compra'], ['id' => 'no_pedido_compra', 'class' => 'form-control', 'maxlength' => '50', 'readonly']) }}
		    	</div>
		    	<div class="form-group col-sm-12 col-lg-2">
					{{ Form::label('cliente_telefone', 'Telefone cliente', []) }} 
			        {{ Form::text('cliente_telefone', $pedido['cliente_telefone'], ['id' => 'cliente_telefone', 'class' => 'form-control', 'readonly']) }}
		    	</div>
		    </div>
		    <div class="form-row">
				<div class="col-sm-2">
					<div class="form-check">
						{!! Form::checkbox('enfestar', 'true', $pedido['enfestar'], ['id' => 'enfestar', 'class' => 'form-check-input', 'readonly']) !!}
						{!! Form::label('enfestar', 'Enfestar produto', ['class' => 'form-check-label']) !!}
					</div>
				</div>
				<div class="col-sm-2">
					<div class="form-check">
					{!! Form::checkbox('bater_amostra', 'true', $pedido['bater_amostra'], ['id' => 'bater_amostra', 'class' => 'form-check-input', 'readonly']) !!}
					{!! Form::label('bater_amostra', 'Bater Amostra', ['class' => 'form-check-label']) !!}
					</div>
				</div>
				<div class="col-sm-2">
					<div class="form-check">
						{!! Form::checkbox('incluir_cartelas', 'true', $pedido['incluir_cartelas'], ['id' => 'incluir_cartelas', 'class' => 'form-check-input', 'readonly']) !!}
						{!! Form::label('incluir_cartelas', 'Incluir Cartela', ['class' => 'form-check-label']) !!}
					</div>
				</div>
				<div class="col-sm-2">
					<div class="form-check">
						{!! Form::checkbox('metragem_exata', 'true', $pedido['metragem_exata'], ['id' => 'metragem_exata', 'class' => 'form-check-input', 'readonly']) !!}
						{!! Form::label('metragem_exata', 'Metragem Exata', ['class' => 'form-check-label']) !!}
					</div>
				</div>
		    </div>
		</form>
     </div>
</div>


<script>
    
    var $quantidade = 0;
    var $preco = 0;

    $(document).ready( function () {
        form_modal = $(document).find("#form_visualizar_carrinho");

        $(document).find(".bt-delete").off('click');
        $(document).find(".bt-delete").on('click', function(){
            event.stopPropagation();
            modalDeletar($(this));
        });
        $(document).find(".bt-atualizar-carrinho").off('click');
        $(document).find(".bt-atualizar-carrinho").on('click', function(){
            event.stopPropagation();
            atualizarItens($(this), $quantidade, $preco);
        });

        form_modal.find("#bt_finalizar").off("click");
        $(document).find("#bt_finalizar").on("click", function(e){
            e.preventDefault();
            finalizarCompra();
		});

        form_modal.find(".btn-sm").off("click");
        $(document).find(".btn-sm").on("click", function(e){
            e.preventDefault();
            modalDeletar($(this));
		});

        form_modal.find("#bt_excluir").off("click");
        $(document).find("#bt_excluir").on("click", function(e){
            e.preventDefault();
            modalDeletar($(this));
		});

        form_modal.find(".bt-edicao").off("click");
        $(document).find(".bt-edicao").on("click", function(e){
            e.preventDefault();
            modalEditar($(this));
		});

        $(document).find(".number").maskMoney({thousands:'', decimal:','});

        table_carrinho = $(document).find('#table-filters-carrinho').DataTable({
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
        }).on('draw', function () {
            $(document).find(".bt-delete").off('click');
            $(document).find(".bt-delete").on('click', function(){
                event.stopPropagation();
                modalDeletar($(this));
            });
            $(document).find(".bt-atualizar-carrinho").off('click');
            $(document).find(".bt-atualizar-carrinho").on('click', function(){
                event.stopPropagation();
                atualizarItens($(this), $quantidade, $preco);
            });
        });
    });

    function filtro($pedido_id = null, $valor = null){
        $.ajax({
            url: '{{ route("book_virtual_exibicao.filtro") }}',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function (data){
                if(data.status === 'success'){
                    table_carrinho.clear().draw();
                    linhas = [];
                    for (var fields in data.response.produto){
                        temp_array = [
                            data.response.produto[fields].estabelecimento,
                            data.response.produto[fields].produto_codigo,
                            inputQuantidade(data.response.produto[fields]),
                            inputPreco(data.response.produto[fields]),
                            atualizarItem(data.response.produto[fields]),
                            deletarItem(data.response.produto[fields]),
                        ];
        
                        linhas.push(temp_array);    
                    }
                    table_carrinho.rows.add(linhas).draw();
                    $(document).find(".number").maskMoney({thousands:'', decimal:','});
                    
                    if($pedido_id != null && $valor == null){
                        var $valor_total = parserNumber($(document).find('#valor_total').html()) - parserNumber($(document).find('#valor_'+$pedido_id).html());
                        $(document).find('#cancelar_pedido_'+$pedido_id).remove();
                        $(document).find('#valor_total').html(parserValor($valor_total));
                    }
                    if($pedido_id != null && $valor != null){
                        var $valor_total = parserNumber($(document).find('#valor_total').html()) - $valor;
                        $(document).find('#valor_total').html(parserValor($valor_total));
                        var $valor_pedido = parserNumber($(document).find('#valor_'+$pedido_id).html()) -  $valor;
                        $(document).find('#valor_'+$pedido_id).html(parserValor($valor_pedido));
                    }
                }
            },
            error: function (callback){
                var dados = callback.responseJSON.message;
                message("Atenção", callback.responseJSON.message);
            }
        });
    }


    function abrirModalPedido($id){
        $.ajax({
            url: "{{ route('pedido_portal.detalhes') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                pedido_id: $id
            },
            success: function(data){
                $id = "view-pedido";
                $title = "Detalhes do pedido"; 
                $body = data;
                $class = "modal-lg";
                createModal($id, $title, $body, $class);
            }
        });
    }

    function atualizarItens($this, $quantidade, $preco){
        $.ajax({
            url: $this.data('route'),
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                quantidade : $quantidade,
                preco : $preco,
                id: $this.data('id'),
                valor_total : $(document).find('#valor_total').html(),
            },
            success: function(data){
                $(document).find('#valor_'+$this.data('pedido')).html(data.response.valor);
                $(document).find('#valor_total').html(data.response.valor_total);
                filtro();
            },
            error: function (callback){
                var dados = callback.responseJSON;
                if(Object.keys(dados).length > 0){
                    for(var field in dados.errors){
                        message("Atenção", dados.errors[field]);
                    }
                }else{
                    message("Atenção", dados.message);
                }
            }
        });
    }

    function finalizarCompra(){
        $.ajax({
            url: "{{ route('book_virtual_exibicao.finalizar') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
            },
            success: function(data){
                $(document).find('.modal').modal('hide');
                window.location.reload();
                message("Atenção", "Compra finalizada com sucesso!");
            },
            error: function (callback){
                var dados = callback.responseJSON;
                if(Object.keys(dados).length > 0){
                    for(var field in dados.errors){
                        message("Atenção", dados.errors[field]);
                    }
                }else{
                    message("Atenção", dados.message);
                }
            }
        });
    }

    function inputQuantidade($value){
        var html = "<input id=\"quantidade_"+$value.produto_codigo+"\" class=\"form-control text-right number\" style=\"height: inherit\"  name=\"quantidade_"+$value.produto_codigo+"\" type=\"text\" value=\""+$value.quantidade+"\" onchange=\"guardarValorQuantidade($(this))\" autocomplete=\"off\">";

        return html;
    }

    function inputPreco($value){
        var html = "<input id=\"preco_"+$value.produto_codigo+"\" class=\"form-control text-right number\" style=\"height: inherit\" name=\"preco_"+$value.produto_codigo+" type=\"text\"  value=\""+$value.preco+"\" onchange=\"guardarValorPreco($(this))\" autocomplete=\"off\">";

        return html;
    }

    function deletarItem($dados){
        var $html = "<a href='#' data-id=\""+$dados.id+"\" data-route=\"{{ route('book_virtual_exibicao.modal.deletar') }}\"  data-title=\"Deletar Produto\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\"></a>";
        return $html;
    }

    function atualizarItem($dados){
        var $html = "<a href='#' data-id=\""+$dados.id+"\" data-pedido=\""+$dados.pedido_id+"\" data-route=\"{{ route('book_virtual_exibicao.atualizar_carrinho') }}\"  data-title=\"Deletar Produto\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Atualizar\" class=\"bt-atualizar-carrinho\"></a>";
        return $html;
    }

    function guardarValorQuantidade($this){
        $quantidade = $this.val(); 
        return $quantidade;
    }

    function guardarValorPreco($this){
        $preco = $this.val();
        return $preco;
    }

    function modalDeletar($this){
        $.ajax({
            url: $this.data('route'),
            data: {
                _token: "{{ csrf_token() }}",
                id : $this.data('id'),
            },
            method: 'POST',
            success: function(body){
                createModal("modal_deletar_carrinho", $this.data('title'), body, '');
            }
        });
    }

    function modalEditar($this){
        $.ajax({
            url: $this.data('route'),
            data: {
                _token: "{{ csrf_token() }}",
                id : $this.data('id'),
            },
            method: 'POST',
            success: function(body){
                createModal("modal_editar_transportadora", $this.data('title'), body, '');
            }
        });
    }
    
    function parserNumber($value){
        $value = $value.replace('.', '');
        $value = $value.replace(',', '.');
        $value = $value.replace(' ', '');
        return parseFloat($value);
    }

    function parserValor($value){
        $value = parseFloat($value);
        $value = $value.toFixed(2);
        $value = $value.replace('.', ',');
        return $value;
    }

</script>
@endsection