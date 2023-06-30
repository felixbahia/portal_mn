@extends('layouts.page-dialog')

@section('content')
<form action={{ route('aprovacao_pedido.recusa_pedido') }} method="post" id="cadMargem" name="cadMargem" onsubmit="return false">
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

		<div class="row my-4">
			<table class="table">
				<thead class="thead-light">
					<th scope="col">Crédito</th>
					<th scope="col">Condições de Pagamento</th>
					<th scope="col">Preços</th>
				</thead>
				<tbody>
					<tr>
						<td>{{$info['criterio_credito']}}</td>
						<td>{{$info['criterio_condicao_pagamento']}}</td>
						<td>{{$info['criterio_preco']}}</td>
					</tr>
				</tbody>
			</table>
		</div>
		
	    @csrf
		{{ Form::hidden('id', $info['id'], array('id' => 'id')) }}


		
		<div class="row">
			<div class="col-sm-12 my-4">
				<b>Justificativa:</b>			
				{{ Form::select('motivo_rejeicao', $motivos, '', array('id' => 'motivo_rejeicao', 'class' => 'form-control')) }}
			</div>
		</div>
		<div class="row">
			<div class="col-lg-12">
				<b>Permitir a Revisão do Pedido:</b>
				{!! Form::select('revisar', ['sim' => 'Sim', 'nao' => 'Não'], 'sim', ['id' => 'revisar', 'class' => 'form-control']) !!}
			</div>
		</div>
		<div class="row">
			<div class="hide-on-recusar" style="width: 100%;">
				<div class="col-lg-12">
					<br>
					{!! Form::label('mensagem_modal', 'Observação') !!}
					{!! Form::textarea('mensagem', '', ['class' => 'form form-control', 'id' => 'mensagem_modal', 'col' => '5', 'rows' => '4', 'maxlength' => '254']) !!}
				</div>
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
<script>
    $(document).ready(function(){
		if ($(document).find('#revisar').val() == 'sim'){
			$(document).find('.hide-on-recusar').show();
		}else{
			$(document).find('.hide-on-recusar').hide();
		}

		$(document).find("#revisar").off('change');
        $(document).find("#revisar").on('change', function(){
            if ($(document).find('#revisar').val() == 'sim'){
				$(document).find('.hide-on-recusar').show();
			}else{
				$(document).find('.hide-on-recusar').hide();
			}
        });
        
    });

</script>
@endsection