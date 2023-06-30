@extends('layouts.page-dialog')

@section('content')
	<div class="content-filter-dialog">	
		<form action="post" name="form_filter_vencimentos" class="cadPedido" id="form_filter_vencimentos" onsubmit="return false;">
			<p>Inserir produtos</p>
	    	<div class="content-fields">
				<div class="form-row">		
		        	{{ Form::text('prazo_digitavel', '', ['id' => 'prazo_digitavel', 'class' => 'input-search-bt form-control', 'placeholder' => 'Busque alguns termos', "maxlength" => "250"]) }}
				</div>
			</div>

			<div class="content-buttons">
		        <button name="btn-filterform" id="btn-filterform-vencimentos" class="btn-filter">Buscar</button>
		        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
			</div>

		</form>
	</div>
	<div class="content-dialog-table">
		<div class="content-table">
			<table class="table table-striped table-filter-vencimentos" id="table-filter-vencimentos">
		        <thead>
		            <tr>
		                <th>Código</th>
		                <th>Descrição</th>
		                <th>Vencimento 1</th>
		                <th>Vencimento 2</th>
		                <th>Vencimento 3</th>
		                <th>Vencimento 4</th>
		                <th>Vencimento 5</th>
		                <th>Vencimento 6</th>
		                <th>Vencimento 7</th>
		                <th>Vencimento 8</th>
		            </tr>
		        </thead>
		        <tbody>
		        </tbody>
		    </table>
		</div>
	</div>
@endsection