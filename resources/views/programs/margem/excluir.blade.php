@extends('layouts.page-dialog')

@section('content')
	<div class=row>
		<div class='col-lg-12'>
			<h5>Deseja realmente excluir este registro?</h5>
		</div>
	</div>
	<div class="row">
		<table class="table">
			<thead class="thead-light">
				<tr>
					<th>Empresa</th>
					<th>Grupo</th>
					<th>Produto</th>
					<th>Nome</th>
					<th>Marca</th>
					<th>Linha</th>
					<th>Margem A</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>{{$margem['empresa']}}</td>
					<td>{{$margem['grupo']}}</td>
					<td>{{$margem['produto']}}</td>
					<td>{{$margem['nome']}}</td>
					<td>{{$margem['marca']}}</td>
					<td>{{$margem['linha']}}</td>
					<td>{{$margem['margem_a']}}</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<form action="{{ route("margem.excluir") }}" method="POST" onsubmit="return false;" }">
    			@csrf
    			
    			{{ Form::hidden('id', $margem['id']) }}
				
				<input type="submit" class="btn btn-danger float-right" value="Excluir">
			</form>
		</div>
	</div>
@endsection