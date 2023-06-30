@extends('layouts.page-dialog')

@section('content')
	<div class="row">
		
		<div class="col-sm-12">
			<b>Deseja realmente excluir este pedido?</b>
		</div>

	</div>

	<br>

	<div class="row border-bottom">
		<div class='col-sm-6'><b>Estabelecimento</b></div>
		<div class='col-sm-6'>{{$pedido['estabelecimento']}}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Cliente</b></div>
		<div class='col-sm-6'>{{$pedido['cliente']}}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Data do pedido</b></div>
		<div class='col-sm-6'>{{$pedido['data_pedido']}}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Valor</b></div>
		<div class='col-sm-6'>{{$pedido['valor_total']}}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Status</b></div>
		<div class='col-sm-6'>{{$pedido['status']}}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Condição de pagamento</b></div>
		<div class='col-sm-6'>{{$pedido['condicao_pagamento']}}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Observação</b></div>
		<div class='col-sm-6'>{{$pedido['observacao']}}</div>
	</div>
	<br>

	<div class="content-buttons float-right">
        <button name="btn-cancel" id="btn-cancel-delete" class="btn btn-primary text-right">Cancelar</button>
        <button name="btn-create" id="btn-item-delete" class="btn btn-danger text-right">Excluir</button>
	</div>
@endsection