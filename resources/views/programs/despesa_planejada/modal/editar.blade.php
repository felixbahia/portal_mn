@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_despesa_planejada" id="form_despesa_planejada" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $dados['id'], ['id' => 'id']) !!}
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('mes_ano', 'Mês/Ano', []) }}
            {{ Form::text('mes_ano', $dados['mes_ano'], ['id' => 'mes_ano', 'class' => 'form-control data', 'placeholder' => 'Mês/Ano', 'maxlength' => '20']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('valor', 'Valor', []) }}
            {!! Form::text('valor', $dados['valor'], ['id' => 'valor', 'class' => 'form-control moeda text-right', 'placeholder' => 'Valor Nacional']) !!}
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    $(document).ready(function(){
        form_modal = $(document).find("#form_despesa_planejada");

        form_modal.find('.data').datepicker({ 
            format: 'mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form_modal.find('.data').mask('00/0000');

        form_modal.find(".moeda").maskMoney({thousands:'.', decimal:','});

        form_modal.find("#btn-salvar").off('click');
        form_modal.find("#btn-salvar").on('click', function(){
            editarDados(form_modal);
        });
    });

    function editarDados(form_modal){
        limparMesagemErroModal(form_modal);
        data_form_modal = form_modal.serialize();
        $.ajax({
            url: "{{ route('despesa_planejada.editar') }}", 
            dataType: 'json',
            data: data_form_modal,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                mensagemErroModal(form_modal, dados);
            }
        });
    }

    function limparMesagemErroModal(form_modal){      
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span, div').removeClass('error-input');
    }

    function mensagemErroModal(form_modal, json_error){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModal(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form_modal, input, message){
        var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

</script>
@endsection