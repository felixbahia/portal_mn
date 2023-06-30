@extends('layouts.page-dialog')

@section('content')
	<div class="row">
		
		<div class="col-sm-12">
			<b>Deseja realmente excluir este item?</b>
		</div>

	</div>

	<br>

	<div class="row border-bottom">
		<div class='col-sm-6'><b>Grupo</b></div>
		<div class='col-sm-6'>{{$item['grupo']}}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Código</b></div>
		<div class='col-sm-6'>{{$item['codigo']}}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Descrição</b></div>
		<div class='col-sm-6'>{{$item['descricao']}}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Marca</b></div>
		<div class='col-sm-6'>{{$item['marca']}}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Linha</b></div>
		<div class='col-sm-6'>{{$item['linha']}}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Quantidade</b></div>
		<div class='col-sm-6'>{{$item['quantidade']}}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Preço Unitário</b></div>
		<div class='col-sm-6'>{{$item['preco_unitario']}}</div>
	</div>
	<div class="row border-bottom">
		<div class='col-sm-6'><b>Valor Total</b></div>
		<div class='col-sm-6'>{{$item['valor_total']}}</div>
	</div>

	<br>

	<div class="content-buttons float-right">
        <button name="btn-cancel" id="btn-cancel-delete" class="btn btn-primary text-right">Cancelar</button>
        <button name="btn-create" id="btn-item-delete" class="btn btn-danger text-right">Excluir</button>
	</div>
@endsection