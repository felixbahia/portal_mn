@extends('layouts.page-dialog')
@section('content')
<form name="form_enviar_email" id="form_enviar_email">
    <div class="row">
        @if(!empty($quantidade_arquivos))
            <div class="form-group col-sm-6 alert alert-info">
                Será relizado {{ $quantidade_arquivos }} envios!
            </div>
        @endif
    </div>
    <div class="row">
        <div class="form-group col-sm-12">
            @csrf
            {{ Form::hidden('segmentos_id', $segmentos_id, ['id' => 'segmentos_id']) }}
            {{ Form::hidden('produto_grupos_id', $produto_grupos_id, ['id' => 'produto_grupos_id']) }}
            {{ Form::hidden('desenho', $desenho, ['id' => 'desenho']) }}
            {{ Form::hidden('quantidade_arquivos', $quantidade_arquivos, ['id' => 'quantidade_arquivos']) }}
            {{ Form::label('email', 'E-mail', []) }}
            {{ Form::text('email', '', ['id' => 'email', 'class' => 'form-control', 'placeholder' => 'E-mail', 'maxlength' => '150']) }}
        </div>  
        <div class="col-sm-12 mt-5">
            {{ Form::button('Enviar', array('class' => 'btn btn-success float-right', 'id' => 'btn-enviar')) }}
        </div>   
    </div>
</form>
<script>
    $(document).ready( function(){
        form = $(document).find("#form_enviar_email");

        $(document).find('#btn-enviar').off('click');
        $(document).find('#btn-enviar').on('click', function(event){
            event.stopPropagation();
            enviarPDFEmail(form);
        })
    });

    function enviarPDFEmail(form){
        $.ajax({
            url: "{{ route('book_virtual_exibicao_new.enviar') }}",
            dataType: 'json',
            method: 'POST',
            data: form.serialize(),
            success: function(data){
                message("Atenção", 'Enviado com sucesso!')
                $(document).find('.modal').modal('hide');
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErro(dados);
            }
        });
    }

    function limparMesagemErroAdd(){      
        var form_modal_add = $(document).find("#form_enviar_email");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErro(json_error){
        var form_modal_add = $(document).find("#form_enviar_email");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputs(form_modal_add, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection
