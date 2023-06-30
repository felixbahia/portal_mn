@extends('layouts.page-dialog')

@section('content')
<form action="#" onsubmit="return false" id='novo_fechamento'>

    @csrf
    <div class="row">
        <div class="col-sm-6">
            {!! Form::label('periodo', 'Período') !!}
            {!! Form::text('periodo', '', ['class' => 'periodo form-control', 'id' => 'periodo'])!!}
        </div>
    </div>
    <div class="row">
        <div class="col">
            {!! Form::label('data_inicio', 'Início do Período') !!}
            {!! Form::text('data_inicio', '', ['class' => 'data_modal form-control', 'id' => 'data_inicio'])!!}
        </div>
        <div class="col">
            {!! Form::label('data_fim', 'Fim do Período') !!}
            {!! Form::text('data_fim', '', ['class' => 'data_modal form-control', 'id' => 'data_fim'])!!}
        </div>
    </div>
    <div class="row">
        <div class="col text-right mt-2">
            {!! Form::button('Enviar', ['class' => 'btn btn-success', 'onclick' => 'enviar()']) !!}
        </div>
    </div>
</form>

<script>

    var z_index = $(document).find('#new_data_fechamento_modal').css('z-index') + 1;

    $('#periodo').mask('00/0000');
    $('#periodo').datepicker({
        language: 'pt-BR',
        format: 'mm/yyyy',
        zIndex: z_index,
        autoHide: true
    });

    $('.data_modal').mask('00/00/0000');
    $('.data_modal').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: z_index,
        autoHide: true
    });

    function enviar(){
        $(document).find('.error-message').remove();
        $(document).find('.error-input').removeClass('error-input');

        $form = $(document).find('#novo_fechamento');
        $.ajax({
            url: '{{ route('comissao_data_fechamento.novo') }}',
            type: 'POST',
            dataType: 'json',
            data: $form.serialize(),
            success: function(callback){
                $(document).find('#new_data_fechamento_modal').modal('hide');
                message('Sucesso', 'Dados cadastrados com sucesso');
                buscaDados($(document).find("#form_filter"));
            },
            error: function(callback){
                errors = callback.responseJSON.error;

                for(var field in errors){
                    showErrorsInputs($form, field, errors[field]);
                }
            }
        });
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

</script>
@endsection