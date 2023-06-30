@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_feriado" id="form_feriado" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('feriado', 'Data', []) }} 
            {{ Form::text('feriado', '', ['id' => 'feriado', 'class' => 'form-control data input-label', 'placeholder' => 'Feriado', 'maxlength' => '20']) }}
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', ['class' => 'btn btn-primary float-right', 'id' => 'btn-salvar']) }}
    </div> 
</form>
<script>
    $(document).ready( function () {
        form_modal_feriado = $(document).find('#form_feriado');
        form_modal_feriado.find('.data').datepicker({ 
            format: 'dd/mm/YYYY',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form_modal_feriado.find('.data').mask('00/00/0000');

        form_modal_feriado.find('#btn-salvar').off('click');
        form_modal_feriado.find('#btn-salvar').on('click',function(){
            inserirDados(form_modal_feriado.serialize());
        });
    });

    function inserirDados(data_form_modal_feriado){
        $.ajax({
            url: "{{ route('feriado.adicionar') }}", 
            dataType: 'json',
            data: data_form_modal_feriado,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal_feriado).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErro(form_modal_feriado);
                mensagemErro(dados, form_modal_feriado);
            }
        });
    }

    function limparMesagemErro(form_modal_feriado){ 
        form_modal_feriado.find('.error-message').remove();
        form_modal_feriado.find('input, select, span').removeClass('error-input');
    }

    function mensagemErro(json_error, form_modal_feriado){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputs(form_modal_feriado, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputs(form_modal_feriado, input, message){
        var $input = $(form_modal_feriado).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

</script>
@endsection