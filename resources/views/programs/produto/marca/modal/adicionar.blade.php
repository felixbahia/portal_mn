@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_marca_add" id="form_marca_add" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('marca', 'Marca', []) }}
            {{ Form::text('marca', '', ['id' => 'marca', 'class' => 'form-control', 'placeholder' => 'Nome da Marca', 'maxlength' => '40']) }}
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    $(document).ready( function () {
        form_modal_add = $(document).find('#form_marca_add');
        form_modal_add.find("#btn-salvar").on('click', function(){
            inserirDados(form_modal_add.serialize());
        });
    });

    function inserirDados(data_form_modal_add){
        $.ajax({
            url: "{{ route('produto.marca.adicionar') }}", 
            dataType: 'json',
            data: data_form_modal_add,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal_add).parents('.modal').modal('hide');
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
        var form_modal_add = $("#form_marca_add");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_add = $("#form_marca_add");
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