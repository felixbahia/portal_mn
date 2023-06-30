@extends('layouts.page-dialog')

@section('content')

<div class="container">
	<form action="{{ route("parametros_aprovacao.delete") }}" method="post" id="modal_edit_param" name="modal_edit_param" onsubmit="return false">
	    {{ Form::hidden('id', $parametro['id'])}}

	    @csrf

	    <div class="row">
	    	<div class="col-md-12">
	    		<h3>
	    			Deseja realmente excluir este parâmetro?
	    		</h3>
	    		<table class="table">
	    			<thead class="thead-light">
	    				<tr>
		    				<th>Estabelecimento</th>
		    				<th>Tipo de Usuário</th>
		    				<th>Desconto limite</th>
		    				<th>Prazo limite</th>
	    				</tr>
	    			</thead>
	    			<tbody>
	    				<tr>
	    					<td>{{$parametro['estabelecimento']}}</td>
	    					<td>{{$parametro['tipo_usuario_nome']}}</td>
	    					<td>{{$parametro['percentual_desconto']}}</td>
	    					<td>{{$parametro['prazo_adicional']}}</td>
	    				</tr>
	    			</tbody>
	    		</table>

				<input type="submit" class="btn btn-danger float-right" value="Excluir">
	    	
	    	</div>
	    </div>
	</form>
</div>