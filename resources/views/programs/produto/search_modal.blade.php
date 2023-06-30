@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">	
	<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
	    @csrf
	    <div class="content-fields">
	        <div class="col-lg-2">
	            <input type="text" name="grupo" id="grupo_modal_busca" value="" placeholder="Grupo" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="codigo" id="codigo_modal_busca" value="" placeholder="Código Produto" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="nome" id="nome_modal_busca" value="" placeholder="Nome Produto" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="marca" id="marca_modal_busca" value="" placeholder="Marca" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="linha" id="linha_modal_busca" value="" placeholder="Linha" maxlength="250" />
	        </div>
	    </div>
	    <div class="content-buttons">
	        <button name="btn-filterform" id="btn-filterform-modal" class="btn-filter">Buscar</button>
	        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
	    </div>
	</form>
</div>
<div class="content-dialog-table">
	<div class="content-table">
	    <table class="table table-striped" id="table-filters-produtos-busca">
	        <thead>
	            <tr>
	                <th>Grupo</th>
	                <th>Código</th>
	                <th>Descrição</th>
	                <th>Marca</th>
	                <th>Linha</th>
	                <th>Unidade</th>
	                <th>Estoque Disponível</th>
	            </tr>
	        </thead>
	        <tbody>
	        </tbody>
	    </table>
	</div>
</div>
@endsection