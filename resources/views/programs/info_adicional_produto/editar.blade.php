
@extends('layouts.page-dialog')
@section('content')
<div class="container">
	<form method="post" id="cadInfoAdicional" name="cadInfoAdicional" onsubmit="return false">
	    @csrf
	    {{ Form::hidden('id', $produto['id'])}}
		{{ Form::hidden('cod_produto', $produto['cod_produto'])}}
	    <div class="row">
			<div class="col-sm-4">
				<p><b>Código</b><br>
				{{ $produto['cod_produto'] }}</p>
			</div>
			<div class="col-md-8">
				<p><b>Descrição</b><br>
				{{ $produto['nome'] }}</p>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4">
				<p><b>Procedência:</b><br>
				{{ $produto['procedencia'] }}</p>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<p><b>Estoques:</b></p>
				@foreach ($estoques as $estoque)
				<p>{{ $estoque }}</p>
				@endforeach
				<hr>
			</div>
		</div>
		<div class="form-row">
			{{ Form::hidden('cod_produto', $produto['cod_produto']) }}
			<div class="col-md-6">
				{{ Form::label('marca', 'Marca') }}
				{{ Form::text('marca', $produto['marca'], ['id' => 'marca', 'class' => 'form-control']) }}	    		
			</div>
			<div class="col-md-6">
				{{ Form::label('linha', 'Linha') }}
				{{ Form::text('linha', $produto['linha'], ['id' => 'linha', 'class' => 'form-control']) }}	    		
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-6">
				{{ Form::label('grupo', 'Grupo') }}
				{{ Form::text('grupo', $produto['grupo'], ['id' => 'grupo', 'class' => 'form-control']) }}	    		
			</div>
			<div class="col-md-6">
				{{ Form::label('subgrupo', 'Subgrupo') }}
				{{ Form::text('subgrupo', $produto['subgrupo'], ['id' => 'subgrupo', 'class' => 'form-control']) }}	    		
			</div>
		</div>
	    <div class="form-row">
	    	<div class="col-md-4">
		        {{ Form::label('largura', 'Largura') }}
		        {{ Form::text('largura', $produto['largura'], ['id' => 'largura', 'class' => 'form-control', 'maxlength' => 250, 'readonly']) }}
	    	</div>
	    	<div class="col-md-4">
		        {{ Form::label('gramatura_gml', 'Gramatura g/ml') }}
		        {{ Form::text('gramatura_gml', $produto['gramatura_gml'], ['id' => 'gramatura_gml', 'class' => 'form-control', 'maxlength' => 250, 'readonly']) }}
		    </div>
			<div class="col-md-4">
		        {{ Form::label('gramatura_gm2', 'Gramatura g/m2') }}
		        {{ Form::text('gramatura_gm2', $produto['gramatura_gm2'], ['id' => 'gramatura_gm2', 'class' => 'form-control', 'maxlength' => 250, 'readonly']) }}
		    </div>
		</div>

		<div class="form-row">
			<div class="form-group col-sm-6"> 
				{{ Form::label('gramatura_tipo', 'Tipo Gramatura', []) }}
				{{ Form::text('gramatura_tipo', $produto['gramatura_tipo'], ['id' => 'gramatura_tipo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Tipo Gramatura', 'readonly']) }}
			</div>
			<div class="col-md-6">
		        {{ Form::label('rendimento', 'Rendimento') }}
		        {{ Form::text('rendimento',  $produto['rendimento'], ['id' => 'rendimento', 'class' => 'form-control', 'maxlength' => 250, 'readonly']) }}
		    </div>
		</div>
		<div class="form-row">
			<div class="col-md-6">
			{{ Form::label('status','Status') }}
			{{ Form::select('status', $status, $produto['status']?'true':'false', ['class' => 'form-control', 'id' => 'status']) }}
			</div>
			<div class="col-md-6">
				{{ Form::label('tipo_genero', 'Gênero') }}
				{{ Form::text('tipo_genero', $produto['genero'], ['id' => 'tipo_genero', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Gênero', 'readonly']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-4">
				{{ Form::label('segmento', 'Segmento') }}
				{{ Form::text('segmento', $produto['segmento'], ['id' => 'segmento', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Sgmento', 'readonly']) }}
			</div>
			<div class="col-md-4">
				{{ Form::label('familia', 'Acabamento') }}
				{{ Form::text('familia', $produto['familia'], ['id' => 'familia', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Acabamento', 'readonly']) }}
			</div>
			<div class="col-md-4">
				{{ Form::label('padrao_codigo', 'Padrão do código') }}
				{{ Form::text('padrao_codigo', $produto['padrao_codigo'], ['data-old' => $produto['padrao_codigo'], 'id' => 'padrao_codigo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Padrão Código', 'readonly']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-6"> 
				{{ Form::label('caracteristicas', 'Características', []) }}
				{{ Form::text('caracteristicas', $produto['caracteristicas'], ['id' => 'caracteristicas', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Características', 'maxlength' => '250', 'readonly']) }}
			</div>
			<div class="col-md-6"> 
				{{ Form::label('pecas', 'Peças de', []) }}
				{{ Form::text('pecas', $produto['pecas'], ['id' => 'pecas', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Peças de', 'maxlength' => '250', 'readonly']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-6"> 
				{{ Form::label('origem', 'País de Origem', []) }}
				{{ Form::text('origem', $produto['origem'], ['id' => 'origem', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'País de Origem', 'maxlength' => '250', 'readonly']) }}
			</div>
			<div class="form-group col-sm-6">
				{{ Form::label('composicao', 'Composição Predominante') }}
				{{ Form::text('composicao', $produto['composicao'], ['id' => 'composicao', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Composição Predominante', 'readonly']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-6">
				{{ Form::label('construcao', 'Construção') }}
				{{ Form::text('construcao',  $produto['construcao'], ['id' => 'construcao', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Construção', 'readonly']) }}
			</div>
			<div class="form-group col-sm-6">
				{{ Form::label('sazonal', 'Sazonalidade') }}
				{{ Form::text('sazonal', $produto['sazonal'], ['id' => 'sazonal', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Sazonalidade', 'readonly']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-12 mt-4">
				{{ Form::submit('Salvar', array('id' => 'salvar', 'class' => 'btn btn-primary float-right')) }}
			</div>
		</div>
	</form>
</div>
<script>
	$(document).ready(function(){
		$(document).find('#cadInfoAdicional').find("#marca").autocomplete(optionsAutoCompleteMarca());
		$(document).find('#cadInfoAdicional').find("#linha").autocomplete(optionsAutoCompleteLinha());
		$(document).find('#cadInfoAdicional').find("#grupo").autocomplete(optionsAutoCompleteGrupo());
		$(document).find('#cadInfoAdicional').find("#subgrupo").autocomplete(optionsAutoCompleteSubgrupo());
	})
	function optionsAutoCompleteGrupo(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.grupo.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_editar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
                event.stopPropagation();
                buscaGrupo();
            }
		};
	}
	function optionsAutoCompleteSubgrupo(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.subgrupo.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_editar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}
	function salvar_edicao(){
		$data = $(document).find('#cadInfoAdicional').serialize()
		form = $(document).find("#cadInfoAdicional");
		
		$.ajax({
			url: '{{ route('analise.produto.infoadicional.salvar_edicao') }}',
			type: 'POST',
			data: $data,
			success: function(){
				$(document).find("#modal_editar").modal('hide');
				message('Sucesso', "Informações salvas com sucesso");
				filtro();
			},
			error: function(data){
				var errors = data.responseJSON.errors;
				form.find('.error-message').remove();
				for(var field in errors){
					showErrorsInputs(form, field, errors[field])
				}
			}
		});
	}
	function optionsAutoCompleteLinha(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.linha.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_editar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}
	function optionsAutoCompleteMarca(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.marca.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_editar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

	function buscaGrupo(){
		form = $(document).find("#cadInfoAdicional");
		form.find('.error-message').remove();

		$.ajax({
			url: '{{ route("analise.produto.infoadicional.busca_grupo") }}',
			type: 'POST',
			data:form.serialize(),
			success: function(data){
				form.find("#largura").val(data.response.largura);
				form.find("#gramatura_gml").val(data.response.gramatura_gml);
				form.find("#gramatura_gm2").val(data.response.gramatura_gm2);
				form.find("#gramatura_tipo").val(data.response.gramatura_tipo);
				form.find("#rendimento").val(data.response.rendimento);
				form.find("#tipo_genero").val(data.response.tipo_genero);
				form.find("#segmento").val(data.response.segmento);
				form.find("#familia").val(data.response.familia);
				form.find("#padrao_codigo").val(data.response.padrao_codigo);
				form.find("#caracteristicas").val(data.response.caracteristicas);
				form.find("#pecas").val(data.response.pecas);
				form.find("#origem").val(data.response.origem);
				form.find("#composicao").val(data.response.composicao);
				form.find("#construcao").val(data.response.construcaos);
				form.find("#sazonal").val(data.response.sazonalidade);
			},
			error: function(data){
				var errors = data.responseJSON.errors;
				for(var field in errors){
					showErrorsInputs(form, field, errors[field]);
				}
			}
		});
	}
</script>
@endsection
