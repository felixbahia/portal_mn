@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_gravar_questao" id="form_gravar_questao" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('novo_grupo', 'Novo Grupo', []) }} 
            <div class="input-group" id="cod_produto_group">
                {{ Form::text('novo_grupo', '', ['id' => 'novo_grupo', 'class' => 'form-control', 'placeholder' => 'Digite o Nome para o Novo Grupo', 'maxlength' => '50']) }}
            </div>
        </div>
        <div class="col-sm-12 mt-1" >
            {{ Form::button('Salvar Novo Grupo', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
        </div> 
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('grupo', 'Selecione um Grupo para Excluir', []) }}
            {{ Form::select('grupo', $grupo_pergunta,'', ['id' => 'grupo', 'class' => 'form-control grupo', 'placeholder' => 'Selecione', 'maxlength' => '60']) }}
        </div>
        <div class="col-sm-12 mt-1">
            {{ Form::button('Excluir Grupo', array('class' => 'btn btn-danger float-right', 'id' => 'btn-excluir')) }}
        </div> 
    </div>
</form>
<script>
$(document).ready( function () {
    form_modal = $(document).find('#form_gravar_questao');

    form_modal.find("#btn-salvar").on('click', function(){
        inserirGrupo(form_modal.serialize());
    });
    form_modal.find("#btn-excluir").on('click', function(){
        ExcluirGrupo(form_modal.serialize());
    });
});

function inserirGrupo(data_form_modal){
    $.ajax({
        url: "{{ route('score_fornecedores_formularios.gravar_grupo') }}", 
        dataType: 'json',
        data: data_form_modal,
        method: 'POST',
        success: function(callback){
            message("Atenção", callback.message);
            $(form_modal).parents('.modal').modal('hide');
            $('.grupo').append($('<option>', {
                value: callback.response.value,
                text: callback.response.text
            }));
        },
        error: function(callback){
            var dados = callback.responseJSON;
            limparMesagemErroModal();
            mensagemErroModal(dados);
        }
    });
}

function ExcluirGrupo(data_form_modal){
    $.ajax({
        url: "{{ route('score_fornecedores_formularios.excluir_grupo') }}", 
        dataType: 'json',
        data: data_form_modal,
        method: 'POST',
        success: function(callback){
            message("Atenção", callback.message);
            $(form_modal).parents('.modal').modal('hide');
            $(".grupo option[value='"+callback.response.id+"']").remove();
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