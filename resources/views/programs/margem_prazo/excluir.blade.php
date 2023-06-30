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
					<th>Origem</th>
					<th>A vista</th>
					<th>Prazo 15 dias</th>
					<th>Prazo 30 dias</th>
					<th>Prazo 45 dias</th>
					<th>Prazo 60</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>{{$margem['Origem']}}</td>
					<td>{{$margem['prazo_vista']}}</td>
					<td>{{$margem['prazo_15']}}</td>
					<td>{{$margem['prazo_30']}}</td>
					<td>{{$margem['prazo_45']}}</td>
					<td>{{$margem['prazo_60']}}</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<form action="{{ route("margem_prazo.excluir") }}" method="POST" onsubmit="return false;" }">
    			@csrf
    			
    			{{ Form::hidden('id', $margem['id']) }}
				
				<input type="submit" class="btn btn-danger float-right" value="Excluir">
				<input type="button" id="cancelar_modal" class="btn btn-primary float-right" value="Cancelar">
			</form>
		</div>
	</div>
@endsection