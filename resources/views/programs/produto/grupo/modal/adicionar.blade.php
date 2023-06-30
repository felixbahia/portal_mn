@extends('layouts.page-dialog')

@section('content')

<div class="container">
	<form action="#" id="form_grupo_add" name="form_grupo_add" onsubmit="return false">
	    @csrf
		<div class="form-row">
			<div class="col-md-12">
				{{ Form::label('grupo', 'Grupo') }}
				{{ Form::text('grupo', '', array('id' => 'grupo', 'class' => 'form-control')) }}
			</div>
		</div>
	    <div class="form-row">
	    	<div class="col-md-4">
		        {{ Form::label('largura', 'Largura') }}
		        {{ Form::text('largura', '', ['id' => 'largura', 'class' => 'form-control', 'maxlength' => '250']) }}	    		
	    	</div>
	    	<div class="col-md-4">
		        {{ Form::label('gramatura_gm2', 'Gramatura g/m2') }}
		        {{ Form::text('gramatura_gm2', '', ['id' => 'gramatura_gm2', 'class' => 'form-control', 'maxlength' => '250']) }}
		    </div>
            <div class="col-md-4">
		        {{ Form::label('gramatura_gml', 'Gramatura Linear') }}
		        {{ Form::text('gramatura_gml', '', ['id' => 'gramatura_gml', 'class' => 'form-control', 'maxlength' => '250']) }}
		    </div>
		</div>
		<div class="form-row">
            <div class="form-group col-sm-6"> 
				{{ Form::label('gramatura_tipo', 'Tipo Gramatura', []) }}
				{{ Form::select('gramatura_tipo', $tipo_gramatura, '', ['id' => 'gramatura_tipo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Tipo Gramatura']) }}
			</div>
			<div class="col-md-6">
		        {{ Form::label('rendimento', 'Rendimento') }}
		        {{ Form::text('rendimento', '', ['id' => 'rendimento', 'class' => 'form-control', 'maxlength' => '250']) }}
		    </div>
		</div>
		<div class="form-row">
            <div class="col-md-6">
				{{ Form::label('tipo_genero', 'Gênero') }}
				{{ Form::select('tipo_genero', $tipo_generos, '', ['id' => 'tipo_genero', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Selecione o Gênero']) }}
			</div>
			<div class="col-md-6">
				{{ Form::label('segmento', 'Segmento') }}
				{{ Form::select('segmento', $segmentos, '', ['id' => 'segmento', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Selecione um segmento']) }}
			</div>
		</div>
		<div class="form-row">
            <div class="col-md-6">
				{{ Form::label('familia', 'Acabamento') }}
				{{ Form::select('familia', $familias, '', ['id' => 'familia', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Selecione um Acabamento']) }}
			</div>
			<div class="col-md-6">
				{{ Form::label('padrao_codigo', 'Padrão do código') }}
				{{ Form::select('padrao_codigo', $padroes_codigo, '', ['data-old' => '', 'id' => 'padrao_codigo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => '']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-6"> 
				{{ Form::label('caracteristicas', 'Características', []) }}
				{{ Form::text('caracteristicas', '', ['id' => 'caracteristicas', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Características', 'maxlength' => '250']) }}
			</div>
			<div class="col-md-6"> 
				{{ Form::label('pecas', 'Peças de', []) }}
				{{ Form::text('pecas', '', ['id' => 'pecas', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Peças de', 'maxlength' => '250']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-6"> 
				{{ Form::label('origem', 'País de Origem', []) }}
				{{ Form::text('origem', '', ['id' => 'origem', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'País de Origem', 'maxlength' => '250']) }}
			</div>
			<div class="form-group col-sm-6">
				{{ Form::label('composicao', 'Composição Predominante') }}
				{{ Form::select('composicao', $composicaos, '', ['id' => 'composicao', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Selecione a Composição Predominante']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-4">
				{{ Form::label('construcao_id', 'Tipo de Construção') }}
				{{ Form::select('construcao_id', $construcaos, '', ['id' => 'construcao_id', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Selecione a Construção']) }}
			</div>
			<div class="form-group col-sm-4">
				{{ Form::label('construcao', 'Construção') }}
				{{ Form::text('construcao', '', ['id' => 'construcao', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Construção']) }}
			</div>
			<div class="form-group col-sm-4">
				{{ Form::label('sazonal', 'Sazonalidade') }}
				{{ Form::select('sazonal', $sazonalidades, '', ['id' => 'sazonal', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Selecione a Sazonalidade']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-4"> 
				{{ Form::label('encolhimento', 'Encolhimento', []) }}
				{{ Form::text('encolhimento','', ['id' => 'encolhimento', 'class' =>  'form-control text-right', 'placeholder' => 'Encolhimento', 'maxlength' => '10']) }}
			</div>
			<div class="form-group col-sm-4"> 
				{{ Form::label('titulo_trama', 'Titulo Trama', []) }}
				{{ Form::text('titulo_trama','', ['id' => 'titulo_trama', 'class' => 'form-control cad-laudo-form', 'placeholder' => 'Titulo Trama', 'maxlength' => '10']) }}
			</div>
			<div class="form-group col-sm-4"> 
				{{ Form::label('titulo_urdume', 'Titulo Urdume', []) }}
				{{ Form::text('titulo_urdume', '', ['id' => 'titulo_urdume', 'class' => 'form-control cad-laudo-form', 'placeholder' => 'Titulo Urdume', 'maxlength' => '10']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-6"> 
				{{ Form::label('ligamento', 'Ligamento', []) }}
				{{ Form::text('ligamento','', ['id' => 'ligamento', 'class' => 'form-control cad-laudo-form', 'placeholder' => 'Ligamento', 'maxlength' => '10']) }}
			</div>
			<div class="form-group col-sm-6"> 
				{{ Form::label('informacao_adicional', 'Informacao Adicional', []) }}
				{{ Form::text('informacao_adicional','', ['id' => 'informacao_adicional', 'class' => 'form-control cad-laudo-form', 'placeholder' => 'Informacao Adicional', 'maxlength' => '250']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-12">
				{!! Form::label('imagem', 'Imagem do grupo') !!}
			</div>
			<div class="form-group col-sm-12">
				{{ Form::file('imagem', ['id'=>'imagem', 'onchange'=>"this.parentNode.nextSibling.value = this.value" ], null) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-12 mt-4">
				{{ Form::submit('Adicionar', array('id' => 'btn-salvar', 'class' => 'btn btn-success float-right')) }}
			</div>
		</div>
	</form>
</div>
<script>

	$(document).ready(function(){
        form_modal_add = $(document).find('#form_grupo_add');
        form_modal_add.find("#btn-salvar").on('click', function(){
            adicionarGrupo();
        });
	});

    function adicionarGrupo(){
        var form = $(document).find("#form_grupo_add");
		var formData = new FormData($(document).find('#form_grupo_add')[0]);
        $.ajax({
            url: "{{ route('produto.grupo.adicionar') }}", 
            dataType: 'json',
            data: formData,
			processData: false,
            contentType: false,
            method: 'POST',
            success: function(callback){
                $(form).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
    }

    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_grupo_add");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_add = $("#form_grupo_add");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_add, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form_modal_add, input, message){
        var $input = $(form_modal_add).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>

@endsection