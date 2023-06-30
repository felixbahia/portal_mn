@extends('layouts.page-dialog')

@section('content')

<div class="container">
	<form action="{{ route("margem.cadastro") }}" method="post" id="cadMargem" name="cadMargem" onsubmit="return false">
	    @csrf
		<div class="form-row">
			<div class="col-md-4 my-1">
		        {{ Form::label('empresa', 'Empresa') }}
		        {{ Form::select('empresa', $empresas, null, ['id' => 'empresa_modal', 'class' => 'form-control']) }}
			</div>
		</div>

		<div class="form-row">
			<div class="col-md-6 my-1">
		        {{ Form::label('grupo', 'Grupo') }}
		        {{ Form::text('grupo', '', array('id' => 'grupo_modal', 'class' => 'form-control')) }}
			</div>
		
			<div class="col-md-6 my-1">
		        {{ Form::label('produto', 'Produto') }}
		        {{ Form::text('produto', '', array('id' => 'produto_modal', 'class' => 'form-control')) }}
			</div>
		</div>

		<div class="form-row">
			<div class="col-md-6 my-1">
		        {{ Form::label('marca', 'Marca') }}
		        {{ Form::text('marca', '', array('id' => 'marca_modal', 'class' => 'form-control')) }}
			</div>
		
			<div class="col-md-6 my-1">
		        {{ Form::label('linha', 'Linha') }}
		        {{ Form::text('linha', '', array('id' => 'linha_modal', 'class' => 'form-control')) }}
			</div>
		</div>

		<div class="form-row">
			<div class="col-md-4 my-1">
				{{ Form::label('margem_a', "Margem %") }}
				{{ Form::text('margem_a', '', ['class' => 'form-control']) }}
			</div>		
		</div>
		
		<div class="form-row">

			<div class="col-md-12 mt-4">

				{{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}
			
			</div>
		
		</div>

	</form>
</div>
@endsection