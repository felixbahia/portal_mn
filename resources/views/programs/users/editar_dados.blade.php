@extends('layouts.app-home')

@section('content')
<div class="conteudo_centralizado"  style="scroll-behavior: inherit;height: 1000px">
    <div class="content-filter">	
        <form action="" name="form_atualizacao_dados" id="form_atualizacao_dados" onsubmit="return false;">
            @csrf
            <h3>Atualização de dados</h3>
            <div class="content-fields">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('email', 'E-mail', []) }}
                    {{ Form::text('email', $dados['email'], ['id' => 'email', 'class' => 'form-control', 'placeholder' => 'E-mail', 'maxlength' => '100']) }}
                </div>
                <div class="form-group col-sm-12"> 
                    {{ Form::label('email_pessoal', 'E-mail Pessoal', []) }}
                    {{ Form::text('email_pessoal', $dados['email_pessoal'], ['id' => 'email_pessoal', 'class' => 'form-control', 'placeholder' => 'E-mail Pessoal', 'maxlength' => '100']) }}
                </div>
                <div class="form-group col-sm-12"> 
                    {{ Form::label('telefone', 'Telefone', []) }}
                    {{ Form::text('telefone', $dados['telefone'], ['id' => 'telefone', 'class' => 'form-control', 'placeholder' => 'Telefone', 'maxlength' => '100']) }}
                </div>
                <div class="form-group col-sm-12"> 
                    {{ Form::label('contato_emergencia', 'Contato Emergência', []) }}
                    {{ Form::text('contato_emergencia', $dados['contato_emergencia'], ['id' => 'contato_emergencia', 'class' => 'form-control', 'placeholder' => 'Contato Emergência', 'maxlength' => '100']) }}
                </div>
                <div class="form-group col-sm-12"> 
                    {{ Form::label('telefone_emergencia', 'Telefone Emergência', []) }}
                    {{ Form::text('telefone_emergencia', $dados['telefone_emergencia'], ['id' => 'telefone_emergencia', 'class' => 'form-control', 'placeholder' => 'Telefone Emergência', 'maxlength' => '100']) }}
                </div>
                @if(isset($dados['usar_carteira']))
                <div class="form-group col-sm-12"> 
                <div class="form-check col-sm-12"> 
                    {{ Form::checkbox('usar_carteira', true, $dados['usar_carteira'], ['id' => 'usar_carteira', 'class' => 'form-check-input']) }}
                    {{ Form::label('usar_carteira', 'Usar apenas Clientes da carteira', ['class' => 'form-check-label']) }}
                </div>
                </div>
                @endif
            </div>
            <div class="col-sm-12 mt-5" id="button-bottom">
                {{ Form::button('Atualizar', ['class' => 'btn btn-primary float-right', 'id' => 'btn-editar']) }}
            </div> 
        </form>
    </div>
</div>
@endsection

@section('script-footer')
$(document).ready(function(){
    @if(isset($dados['telefone']))
        
        var SPMaskBehavior = function (val) {
            return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
        },
        spOptions = {
            onKeyPress: function(val, e, field, options) {
                field.mask(SPMaskBehavior.apply({}, arguments), options);
            }
        };
        $(document).find("#telefone").mask(SPMaskBehavior, spOptions);
    @endif
    form = $(document).find("#form_atualizacao_dados");
    form.find("#btn-editar").on('click', function(){
        atualizarDados(form);
    });
});

function atualizarDados(form_modal){
    data_form = form_modal.serialize();
    limparMesagemErro(form_modal);
    $.ajax({
        url: '{{ route('usuario.salvar_editar_dados')}}',
        data: data_form,
        method: 'POST',
        success: function(callback){
            message("Atenção", callback.message);
            if(callback.status = 'success'){
				window.location.href = "{{ route('home') }}";
            }
        },
        error: function(data){
            var dados = data.responseJSON;
            mensagemErro(dados, form_modal);
        }
    });
}

function mensagemErro(json_error, form_modal){
    if(Object.keys(json_error).length > 0){
        for(var field in json_error.error){
            showErrorsInputs(form_modal, field, json_error.error[field]);
        }
    }
}

function showErrorsInputs(form_modal, input, message){
    var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
    $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
    $input.addClass('error-input');
}

function limparMesagemErro(form_modal){
    form_modal.find('.error-message').remove();
    form_modal.find('input, select, span').removeClass('error-input');
}
@endsection