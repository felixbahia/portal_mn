@extends('layouts.page-dialog')

@section('content')
	<form action="{{ route('parametros_pedido.salvar') }}" onsubmit="return false">
		@csrf
		<input type="hidden" name="estabelecimento" value='{{ $return['id'] }}'>
		<div class="row">
			<div class="col-lg-12">
				<b>Estabelecimento</b> <br>
				{{$return['estabelecimento']}}
			</div>
		</div>
		<div class="row mt-4">
			<div class="col-lg-12">
				<b>Porcentagem máxima de desconto</b><br>
				<input type="text" name='valor_minimo_porcentagem' value='{{ $return['valor_minimo_porcentagem'] }}'>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-12">
				<b>Porcentagem máxima de acréscimo no preço</b><br>
				<input type="text" name='valor_maximo_porcentagem' value='{{ $return['valor_maximo_porcentagem'] }}'>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-12">
				<b>Dias maximo para integração<br>
				<input type="text" name='dias_integracao' value='{{ $return['dias_integracao'] }}'>
			</div>
		</div>
		<div class="row my-4 float-right">
			<div class="col-lg-12">
				<button type="button" id='fechar' class="btn btn-danger">Fechar</button>
				<input type="submit" class="btn btn-success" value='Salvar'>
			</div>
		</div>
	</form>
	<script>
		$(document).ready(function(){
			ajaxForm("#editar");
			$(document).find('#fechar').on('click', function(){
				$(document).find('#editar').modal('hide');
			})
		});

	</script>

@endsection