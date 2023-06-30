@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_gravar_questao" id="form_gravar_questao" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('questao', 'Questão', []) }} 
            <div class="input-group" id="cod_produto_group">
                {{ Form::text('questao', '', ['id' => 'questao', 'class' => 'form-control', 'placeholder' => 'Digite a Nova Questão', 'maxlength' => '150']) }}
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('tipo_questao', 'Tipo de Questão', []) }}
            {{ Form::select('tipo_questao', $tipo_resposta, '', ['id' => 'tipo_questao', 'class' => 'form-control', 'placeholder' => 'Selecione', 'maxlength' => '120']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('ordem', 'Ordem da Questão', []) }}
            {{ Form::number('ordem', '', ['id' => 'ordem', 'class' => 'form-control', 'placeholder' => 'Ordem da Sequência da Questão', 'maxlength' => '60']) }}
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
$(document).ready( function () {
    form_modal = $(document).find('#form_gravar_questao');

    form_modal.find("#btn-salvar").on('click', function(){
        inserirDados(form_modal.serialize());
    });
});

function inserirDados(data_form_modal){
    $.ajax({
        url: "{{ route('pesquisa_satisfacao_formulario.gravar_questao') }}", 
        dataType: 'json',
        data: data_form_modal,
        method: 'POST',
        success: function(callback){
            message("Atenção", callback.message);
            $(form_modal).parents('.modal').modal('hide');
            filterAjax();
        },
        error: function(callback){
            var dados = callback.responseJSON;
            limparMesagemErroModal();
            mensagemErroModal(dados);
        }
    });
}

function limparMesagemErroModal(){
    var form_modal = $("#form_gravar_questao");
    form_modal.find('.error-message').remove();
    form_modal.find('input, select').removeClass('error-input');
}

function mensagemErroModal(json_error){
    var form_modal = $("#form_gravar_questao");
    if(Object.keys(json_error).length > 0){
        for(var field in json_error.error){
            motrarErrosInputsModal(form_modal, field, json_error.error[field]);
        }
    }
}

function motrarErrosInputsModal(form_modal, input, message){
    var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
    $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
    $input.addClass('error-input');
}
</script>
@endsection