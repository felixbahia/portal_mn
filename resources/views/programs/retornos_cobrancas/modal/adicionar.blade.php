@extends('layouts.page-dialog')

@section('content')

<form action="#" id="frm_cad_motivo" name="frm_cad_motivo" onsubmit="return false;">
    @csrf
    <div class="form-group">
        {{ Form::label('motivo', 'Motivo') }}
        {!! Form::textarea('motivo', '', ['maxlength' => '250', 'rows' => '1', 'class' => 'form-control']) !!}
    </div>
    <div class="form-check">
        {!! Form::checkbox('observacao', 'true', '', ['class' => 'form-check-input', 'id' => 'observacao']) !!}
        {{ Form::label('observacao', 'Habilita Observação', ['class' => 'form-check-label']) }}
    </div>
    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'bt_salvar')) }}
</form>
<script type="text/javascript">
	$(function(){
        $(document).find('#frm_cad_motivo').find("#bt_salvar").off('click');
        $(document).find('#frm_cad_motivo').find("#bt_salvar").on('click', function(event){
            event.stopPropagation();
            var form = $(document).find('#frm_cad_motivo');
            $.ajax({
                url: '{{ route("retornos_cobrancas.adicionar")}}',
                dataType: 'json',
                data: form.serialize(),
                method: 'POST',
                success: function(callback){
                    if(callback.status === "success"){
                        $(document).find('#frm_cad_motivo').parents(".modal").modal("hide");
                        filtrarMotivo($(document).find("#form_filter").serialize());
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