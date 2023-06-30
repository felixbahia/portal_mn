@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_tipo_de_servico_add" id="form_tipo_de_servico_add" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('tipo_de_servico', 'Tipo de Serviço', []) }}
            {{ Form::text('tipo_de_servico', '', ['id' => 'tipo_de_servico', 'class' => 'form-control', 'placeholder' => 'Tipo de Serviço', 'maxlength' => '60']) }}
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    $(document).ready( function () {
        form_modal_add = $(document).find('#form_tipo_de_servico_add');
        form_modal_add.find("#btn-salvar").on('click', function(){
            inserirDados(form_modal_add.serialize());
        });
        setTimeout(function(){
            form_modal_add.find('#tipo_de_servico').focus();
        }, 400);
    });

    function inserirDados(data_form_modal_add){
        $.ajax({
            url: "{{ route('tipo_de_servico.adicionar') }}", 
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
        var form_modal_add = $("#form_tipo_de_servico_add");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_add = $("#form_tipo_de_servico_add");
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