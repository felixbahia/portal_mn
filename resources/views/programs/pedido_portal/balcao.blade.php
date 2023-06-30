@extends('layouts.app')

@section('content')
	
	<form action="{{ route("pedido_portal.salvar") }}" method="post" id="cadPedido" name="cadpedido" class="cadPedido" onsubmit="return false">
				@csrf
				{{ Form::hidden('id', $pedido['id'], ['id' => 'id'])}}

		<div style='width: 95%; height: 95%;'>
	    	<div class="content-fields border rounded">
				<div class="form-group offset-3 col-sm-6 col-lg-6" style="padding: auto">
					{{ Form::label('estabelecimentos', "Estabelecimento", []) }}<br>
					{{ Form::select('estabelecimentos', $estabelecimentos, $pedido['estabelecimentos'], ['id' => 'estabelecimentos', 'class' => 'form-control']) }}
				</div>
			</div>
		</div>


		<div class='border rounded'>
	    	<div class="content-fields">
				<div class="form-group col-sm-6 col-lg-3">
					{{ Form::label('condicao_pagamento', "Forma de pagamento", []) }}<br>
					{{ Form::select('condicao_pagamento', $condicoes_pagamento, $pedido['condicao_pagamento'], ['id' => 'condicao_pagamento', 'class' => 'form-control']) }}
				</div>
			</div>
		<div>
			<p><strong>Inserir produtos</strong></p>
	    	<div class="content-fields">
				<div class="form-row mt-2 ml-2">
					{{ Form::hidden('id', '', ['id' => 'produto_pedido_id'])}}

	        		<div class="form-group col-sm-4 col-lg-2">
						{{ Form::label('produto_codigo', 'Código do produto', []) }}
		    			<div class="input-group" id="cod_produto_group">
				        	{{ Form::text('produto_codigo', '', ['id' => 'produto_codigo', 'class' => 'input-search-bt form-control', 'placeholder' => 'Código', "maxlength" => "250"]) }}
			        		<span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
						</div>
					</div>

	        		<div class="form-group col-sm-8 col-lg-2">
						{{ Form::label('produto_descricao', 'Descrição', []) }}
			        	{{ Form::text('produto_descricao', '', ['id' => 'produto_descricao', 'class' => 'form-control ', 'placeholder' => 'Nome do produto']) }}
			    	</div>

			    	<div class="form-group col-sm-6 col-lg-2" >
						{{ Form::label('quantidade', 'Quantidade', []) }}
			        	{{ Form::text('quantidade', '', ['id' => 'quantidade', 'class' => 'form-control ', 'placeholder' => 'Quantidade', 'maxlength' => '8']) }}
			    	</div>

					<div class="form-group col-sm-6 col-lg-2">
						{{ Form::label('preco_unitario', 'Valor unitário', []) }}
			        	{{ Form::text('preco_unitario', '', ['id' => 'preco_unitario', 'class' => 'form-control ', 'placeholder' => 'Valor unitário', 'maxlength' => '8']) }}
			    	</div>

					<div class="form-group col-sm-4 col-lg-2">
						{{ Form::label('estoque_disponivel', 'Estoque disponível') }}
			        	{{ Form::text('estoque_disponivel', '', ['id' => 'estoque_disponivel', 'class' => 'form-control ', 'placeholder' => 'Estoque', 'disabled' => 'disabled'])}}
			    	</div>

				</div>
			</div>
			<div class="content-buttons">
				<div class="content-total-pedido">
					<b>Total dos Produtos:</b> <span id="total_produtos"></span>
				</div>
		        <button name="btn-create" id="btn-create-itens-pedido" class="btn-create">Inserir produto</button>
		        <button name="btn-cancel" id="btn-cancel-itens-pedido" class="btn-cancel">Cancelar</button>
			</div>
		</div>
	</form>


			<div class="content-table">
				<table class="table table-striped table-filter-pedido-itens" id="table-filters-pedidos-itens">
			        <thead>
			            <tr>
			                <th>Código</th>
			                <th>Descrição</th>
			                <th>Quantidade</th>
			                <th>Preço unitário</th>
			                <th>Valor total</th>
			                <th>Editar</th>
			                <th>Excluir</th>
			            </tr>
			        </thead>
			        <tbody>
			        </tbody>
			    </table>
			</div>

			<div class="col-sm-12 mt-5" id="button-bottom">
				<button type="button" id="enviar_pedido_aprovacao" class="btn btn-success float-right">Enviar pedido</button>
			</div>

@endsection

<script>
@section('script-footer')
@endsection
</script>