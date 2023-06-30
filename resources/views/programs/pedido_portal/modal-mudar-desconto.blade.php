@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('pedido_portal.mudar_desconto')}}" onsubmit="return false">

	@csrf
	{{ Form::hidden('id', $info['id'], ['id' => 'id'])}}

	<div class="row">
		<div class="col-xl-6">
			{{ Form::label('valor_desconto_modal', 'Desconto', []) }}
        	{{ Form::text('valor_desconto', $info['valor_desconto'], ['id' => 'valor_desconto_modal', 'class' => 'form-control']) }}
		</div>
	</div>

	<div class="row">
		<div class="col-xl-12 text-right">
			{{ Form::submit('Confirmar', ['class' => 'btn btn-success']) }}			
		</div>
	</div>

</form>

<script>
	$(document).ready( function(){
		$(document).find('#valor_desconto_modal').maskMoney({thousands:'', decimal:','});
	});

</script>

@endsection