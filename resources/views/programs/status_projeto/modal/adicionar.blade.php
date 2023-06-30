@extends('layouts.page-dialog')

@section('content')

<form action="#" id="frm_cad_status" name="frm_cad_status" onsubmit="return false;">
    @csrf
    <div class="form-group col-sm-12">
        {{ Form::label('posicao', 'Nº Posição') }}
        {!! Form::number('posicao', '', ['min' => '0', 'max' => '98', 'class' => 'form-control']) !!}
    </div>
    <div class="form-group col-sm-12">
        {{ Form::label('status', 'Status') }}
        {!! Form::text('descricao', '', ['maxlength' => '60', 'class' => 'form-control']) !!}
    </div>
    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'bt_salvar')) }}
</form>
<script type="text/javascript">
	$(function(){
        $(document).find('#frm_cad_status').find("#bt_salvar").off('click');
        $(document).find('#frm_cad_status').find("#bt_salvar").on('click', function(event){
            event.stopPropagation();
            var form = $(document).find('#frm_cad_status');
            $.ajax({
                url: '{{ route("status_projeto.adicionar")}}',
                dataType: 'json',
                data: form.serialize(),
                method: 'POST',
                success: function(callback){
                    if(callback.status === "success"){
                        $(document).find('#frm_cad_status').parents(".modal").modal("hide");
                        filtrarStatus($(document).find("#form_filter").serialize());
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
