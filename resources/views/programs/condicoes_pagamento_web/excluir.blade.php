@extends('layouts.page-dialog')

@section('content')
	<div class="row">
		<div class="col-sm-12">
			<b>Deseja realmente excluir esta condição?</b>
		</div>
	</div>
	<br>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Código</b></div>
		<div class='col-sm-6'>{{ $condicao->id }}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Descrição</b></div>
		<div class='col-sm-6'>{{ $condicao->descricao }}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Parcelas</b></div>
		<div class='col-sm-6'>{{ $parcelas }}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Média</b></div>
		<div class='col-sm-6'>{{ $condicao->media }}</div>
	</div>
	<div>
		<div class="row border-bottom">
		<div class='col-sm-6'><b>Visibilidade</b></div>
		<div class='col-sm-6'>{!! $condicao->liberado_representante !!}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Liberado para</b></div>
		<div class='col-sm-6'>{!! $clientes !!}</div>
	</div>
	<br>
	<div class="content-buttons float-right">
        <button name="btn-cancel" id="btn-cancel-delete" class="btn btn-primary text-right">Cancelar</button>
        <button name="btn-delete" id="btn-delete" class="btn btn-danger text-right">Excluir</button>
	</div>
@endsection