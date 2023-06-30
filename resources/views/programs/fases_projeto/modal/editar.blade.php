@extends('layouts.page-dialog')

@section('content')

<form action="#" id="frm_edit_fase" name="frm_edit_fase" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $dados['id']) !!}
    <div class="form-group col-sm-12">
        {{ Form::label('posicao', 'Nº Posição') }}
        {!! Form::number('posicao', $dados['posicao'], ['min' => '0', 'max' => '98', 'class' => 'form-control']) !!}
    </div>
    <div class="form-group col-sm-12">
        {{ Form::label('fase', 'Fase') }}
        {!! Form::text('descricao', $dados['descricao'], ['maxlength' => '60', 'class' => 'form-control']) !!}
    </div>
    <div class="form-group col-sm-12">
        {{ Form::label('dias', 'Dias') }}
        {!! Form::number('dias', $dados['dias'], ['min' => '0', 'class' => 'form-control']) !!}
    </div>
    <div class="form-group col-sm-12">
        {{ Form::label('status', 'Status') }}
        {!! Form::select('status', $status, $dados['status'], ['id' => 'status', 'placeholder' => 'Selecione', 'class' => 'form-control']) !!}
    </div>
    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'bt_salvar')) }}

</form>
<script type="text/javascript">
	$(function(){
        $(document).find('#frm_edit_fase').find("#bt_salvar").off('click');
        $(document).find('#frm_edit_fase').find("#bt_salvar").on('click', function(event){
            event.stopPropagation();
            var form = $(document).find('#frm_edit_fase');
            $.ajax({
                url: '{{ route("fases_projeto.editar")}}',
                dataType: 'json',
                data: form.serialize(),
                method: 'POST',
                success: function(callback){
                    if(callback.status === "success"){
                        $(document).find('#frm_edit_fase').parents(".modal").modal("hide");
                        filtrarFases($(document).find("#form_filter").serialize());
                        message("Atenção", "Dados salvos com sucesso!");
                    } else {
                        message("Atenção", callback.message);
                    }
                },
                error: function(callback){
                    hide_loader();
                    var errors = callback.responseJSON.error;
                    form.find('.error-message').remove();
                    form.find('div, input, select').each(function(){
                        if($(this).hasClass("is-invalid")){
                            $(this).removeClass("is-invalid")
                        }
                        if($(this).hasClass("error-input")){
                            $(this).removeClass("error-input")
                        }
                    });
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                    if(form.find('.error-message').length){
                        form.find('.error-message').eq(0).focus();
                    }
                }
            });
        });
    });
    
	function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('is-invalid');
	}
</script>
@endsection