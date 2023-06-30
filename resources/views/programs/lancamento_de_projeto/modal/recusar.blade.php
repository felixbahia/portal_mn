@extends('layouts.page-dialog')

@section('content')
<form action={{ route('lancamento_projeto.recusa_projeto') }} method="post" id="cadMargem" name="cadMargem" onsubmit="return false">
	<div class="containter">
		<div class="row">
			<div class="col-sm-12">
				<h4>Deseja realmente recusar este projeto?</h4>
			</div>
		</div>

		<div class="row">

			<div class="col-sm-4">
				<b>Projeto:</b>
			</div>
			
			<div class="col-sm-8">
				{{ $info['num_projeto'] }} - {{ $info['nome_projeto'] }}
			</div>

		</div>

		<div class="row">
			<div class="col-sm-4">
				<b>Cliente:</b>
			</div>
			
			<div class="col-sm-8">
				 {{ $info['cliente'] }}
			</div>
		</div>

		<div class="row">
			<div class="col-sm-4">
				<b>Valor Total:</b> 
			</div>
			
			<div class="col-sm-8">
				{{ $info['valor_total'] }}
			</div>
		</div>

		<div class="row">
			<div class="col-sm-4">
				<b>Vendedor:</b>
			</div>
			
			<div class="col-sm-8">
				 {{ $info['vendedor'] }}
			</div>
		</div>
		
	    @csrf
		{{ Form::hidden('id_projeto', $info['id'], array('id' => 'id_projeto')) }}

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