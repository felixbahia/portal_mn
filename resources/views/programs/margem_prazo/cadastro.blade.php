@extends('layouts.page-dialog')

@section('content')

<div class="container">
	<form action="{{ route("margem_prazo.cadastro") }}" method="post" id="cadMargem" name="cadMargem" onsubmit="return false">
	    @csrf
	
		<div class="form-row">
			<div class="col-md-12 my-1">
		        {{ Form::label('estabelecimento', 'Origem') }}
		        {{ Form::select('estabelecimento', $origem, null, ['class' => 'form-control']) }}
			</div>
		</div>

		<hr>

		<div class="row text-center">
			<div class="col-md-12">
				Informe a porcentagem por prazo médio sobre o preço atacado
			</div>
		</div>
		
		<div class="form-row">
			<div class="col-md-12 my-1">
				{{ Form::label('fator_diario', "Fator Diário") }}
				{{ Form::text('fator_diario', '', ['class' => 'form-control number_format']) }}
			</div>
		</div>

		<hr>

		<div class="row">
			<div class="col-md-12 text-center">
				Informe a porcentagem para formar preços das tabelas A, B e C
			</div>
		</div>

		<div class="form-row">
			<div class="col-md-4 text-center my-1">
				{{ Form::label('preco_a', "A") }}
				{{ Form::text('preco_a', '', ['class' => 'form-control number_format']) }}
			</div>

			<div class="col-md-4 text-center my-1">
				{{ Form::label('preco_b', "B") }}
				{{ Form::text('preco_b', '', ['class' => 'form-control number_format']) }}
			</div>

			<div class="col-md-4 text-center my-1">
				{{ Form::label('preco_c', "C") }}
				{{ Form::text('preco_c', '', ['class' => 'form-control number_format']) }}
			</div>
		</div>
		
		<hr>

		<div class="form-row">

			<div class="col-md-12 mt-4">

				{{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}
			
			</div>
		
		</div>

	</form>
</div>

<script>
    $(document).find('#fator_diario').mask("#0,99", {reverse: true});
    $(document).find('#preco_a').mask("#0,99", {reverse: true});
    $(document).find('#preco_b').mask("#0,99", {reverse: true});
    $(document).find('#preco_c').mask("#0,99", {reverse: true});
</script>

@endsection