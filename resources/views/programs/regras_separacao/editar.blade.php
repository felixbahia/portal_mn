@extends('layouts.page-dialog')

@section('content')
	{!! Form::open(['url' => route('regras_separacao.editar'), 'onsubmit' => 'return false']) !!}
	<div class="form-row mb-4">
		<div class="form-group col-sm-12">
			{{ Form::label('estabelecimento', 'Estabelecimento')}}<br>
			{!! $dados['estabelecimento_nome'] !!}
			{{ Form::hidden('estabelecimento', $dados['estabelecimento'])}}
		</div>
	</div>
	<div id='molde' class="form-row d-none">
		<div class="form-group col-sm-5">
			{{ Form::text('quantidade_pecas[]', '', ['class' => 'quantidade_pecas form-control'])}}
		</div>
		<div class="form-group col-sm-5">
			{{ Form::text('tempo[]', '', ['class' => 'tempo form-control'])}}
		</div>
		<div class="form-group col-sm-2">
			<button class='btn btn-default' onclick="removerQuantidade($(this))">
				<i id="bt-delete" class="bt-delete"></i>
			</button>
		</div>
	</div>
	<div class="form-row">
		<div class="col-sm-5">
			{{ Form::label('quantidade_pecas', 'Quantidade de Peças')}}
		</div>
		<div class="col-sm-5">
			{{ Form::label('tempo', 'Tempo em minutos')}}
		</div>
	</div>
	<span id="campos">
		@foreach ($dados['linha'] as $value)
		<div class="form-row insumo">
			<div class="form-group col-sm-5">
				{{ Form::text('quantidade_pecas[]', $value['quantidade_pecas'], ['class' => 'quantidade_pecas form-control'])}}
			</div>
			<div class="form-group col-sm-5">
				{{ Form::text('tempo[]', $value['tempo'], ['class' => 'tempo form-control'])}}
			</div>

			<div class="form-group col-sm-2">
				<button class='btn btn-default' onclick="removerQuantidade($(this))">
					<i id="bt-delete" class="bt-delete"></i>
				</button>
			</div>
		</div>
		@endforeach
	</span>
	<div class="row">
		<div class="col-sm-12">
			{{ Form::button('Adicionar nova linha', ['class' => 'btn btn-success', 'id' => 'nova_quantidade', 'onclick' => 'novaQuantidade();']) }}
		</div>
	</div>
	<div class="form-row mt-4">
		<div class="form-group col-sm-12">
			{{ Form::label('emails', 'E-mails para notificação (separar por vírgula)')}}
			{{ Form::textarea('emails', $dados['emails'], ['maxlength' => '240', 'rows' => '2', 'id' => 'emails', 'class' => 'form-control'])}}
		</div>
	</div>
	<div class="form-row mt-4 float-right">
		<div class="form-group col-sm-12">
			{{ Form::submit('Enviar', ['class' => 'btn btn-success']) }}
		</div>
	</div>
	{!! Form::close() !!}

	<script>
		function novaQuantidade(){
			elemento = $('#molde')
				.clone()
				.removeClass('d-none')
				.removeProp('id');

			$("#campos").append(elemento);

			elemento.find('.bt-delete').click(function(event) {
				removerQuantidade($(this));
			});
		};
		function removerQuantidade(elemento){
			$(elemento).parent().parent().remove();
		}

	</script>
@endsection