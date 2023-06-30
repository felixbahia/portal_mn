@extends('layouts.page-dialog')

@section('content')

<div class="container">
	<form action="#" id="form_inventario_iniciar" name="form_inventario_iniciar" onsubmit="return false">
	    @csrf
        <div class="form-row">
			<div class="col-md-12">
				{{ Form::label('estabelecimento_iniciar', 'Estabelecimento') }}
				{{ Form::select('estabelecimento_iniciar', $estabelecimentos, '', array('id' => 'estabelecimento_iniciar', 'class' => 'form-control', 'placeholder' => 'Selecione o Estabelecimento')) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-12">
				{{ Form::label('codigo_iniciar', 'Código Inventário') }}
				{{ Form::text('codigo_iniciar', '', array('id' => 'codigo_iniciar', 'class' => 'form-control', 'max' => '50')) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-12 mt-4">
				{{ Form::submit('Iniciar', array('id' => 'btn-salvar', 'class' => 'btn btn-success float-right')) }}
			</div>
		</div>
	</form>
</div>
<script>

	$(document).ready(function(){
        form_modal_add = $(document).find('#form_inventario_iniciar');
        form_modal_add.find("#btn-salvar").on('click', function(){
            console.log("aqui");
            IniciarInventario();
        });
	});

    function IniciarInventario (){
        var form_modal_add = $(document).find("#form_inventario_iniciar");
		data_form_modal_add = form_modal_add.serialize()

        $.ajax({
            url: "{{ route('inventario_novo.iniciar') }}", 
            dataType: 'json',
            data: data_form_modal_add,
            method: 'POST',
            success: function(callback){
                $(form_modal_add).parents('.modal').modal('hide');
                message('Sucesso!', callback.message);
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
    }

    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_inventario_iniciar");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_add = $("#form_inventario_iniciar");
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