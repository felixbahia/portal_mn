@extends('layouts.page-dialog')

@section('content')

<div class="container">
	<form action="{{ route("parametros_aprovacao.edit") }}" method="post" id="modal_edit_param" name="modal_edit_param" onsubmit="return false">
	    {{ Form::hidden('id', $parametro->id)}}

	    @csrf
		<div class="form-row">
			<div class="col-md-12 my-1">
		        {{ Form::label('estabelecimento', 'Estabelecimento') }}
		        {{ Form::select('estabelecimento', $estabelecimentos, $parametro->estabelecimento, ['id' => 'estabelecimento_modal', 'class' => 'form-control']) }}
			</div>
		</div>
		<div class="form-row">			
			<div class="col-md-12 my-1">
		        {{ Form::label('tipo_usuario_id', 'Tipo de usuário') }}
		        {{ Form::select('tipo_usuario_id', $tipo_usuarios, $parametro->tipo_usuario_id, ['id' => 'tipo_usuario_id', 'class' => 'form-control']) }}
			</div>
		</div>

		<div class="form-row">
			<div class="col-md-12 my-1">
		        {{ Form::label('percentual_desconto', 'Percentual de desconto limite para aprovação') }}
		        {{ Form::text('percentual_desconto', $parametro->percentual_desconto, array('id' => 'percentual_desconto', 'class' => 'form-control')) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-12 my-1">
		        {{ Form::label('prazo_adicional', 'Prazo adicional limite para aprovação') }}
		        {{ Form::text('prazo_adicional', $parametro->prazo_adicional, array('id' => 'prazo_adicional', 'class' => 'form-control')) }}
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