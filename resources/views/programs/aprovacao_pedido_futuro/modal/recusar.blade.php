@extends('layouts.page-dialog')
@section('content')
<form action={{ route('aprovacao_pedido_futuro.reprovar') }} method="post" id="cadMargem" name="cadMargem" onsubmit="return false">
	@csrf
	{{ Form::hidden('id', $info['id'], array('id' => 'id')) }}
	<div class="containter">
		<div class="row">
			<div class="col-sm-12">
				<h4>Deseja realmente recusar este pedido?</h4>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-4">
				<b>Estabelecimento:</b>
			</div>
			
			<div class="col-sm-8">
				{{$info['estabelecimento']}}
			</div>
		</div>
		<div class="row">
			<div class="col-sm-4">
				<b>Cliente:</b>
			</div>
			
			<div class="col-sm-8">
				 {{$info['cliente']}}
			</div>
		</div>
		<div class="row">
			<div class="col-sm-4">
				<b>Valor Total:</b> 
			</div>
			
			<div class="col-sm-8">
				{{$info['valor']}}
			</div>
		</div>
		<div class="row">
			<div class="col-sm-4">
				<b>Vendedor:</b>
			</div>
			
			<div class="col-sm-8">
				{{$info['vendedor']}}
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12 my-4">
				<b>Justificativa:</b>			
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				{{ Form::select('motivo_rejeicao', $motivos, '', array('id' => 'motivo_rejeicao', 'class' => 'form-control')) }}
			</div>
		</div>
		<br>
		<div class="row">
			<div class="col-sm-12">
				{{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}
			</div>
		</div>
	</div>
</form>
@endsection