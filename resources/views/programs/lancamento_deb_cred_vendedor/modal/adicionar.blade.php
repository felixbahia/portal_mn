@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('lancamento_deb_cred_vendedor.adicionar') }}" id="frm_cad_lancamento_add" name="frm_cad_lancamento_add" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-10">
            {{ Form::label('vendedor', 'Vendedor') }}
            {{ Form::select("vendedor", $vendedor, '', ["class"=>"form-control"]) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            {{ Form::label('documento', 'Documento') }}
            {!! Form::text('documento', '', ['id' => 'documento', 'maxlength' => '10', 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-3">
            {{ Form::radio('tipo', 'D', false, ['id' => 'tipo_d']) }}
            <label for="tipo_d" class="radio-inline">Débito</label>
        </div>
        <div class="form-group col-sm-3">
            {{ Form::radio('tipo', 'C', false, ['id' => 'tipo_c']) }}
            <label for="tipo_c" class="radio-inline">Crédito</label>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-10">
            {{ Form::label('motivo', 'Motivo') }}
            {{ Form::select("motivo", $motivo, '', ["class"=>"form-control"]) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            {{ Form::label('data', 'Data do Lançamento', []) }}
            {{ Form::text('data', '', ['id' => 'data', 'class' => 'form-control data', 'placeholder' => 'Data DD/MM/AAAA']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            {{ Form::label('valor', 'Valor') }}
            {!! Form::text('valor', '', ['maxlength' => '8', 'class' => 'form-control']) !!}
        </div>
    </div>
    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'bt_salvar')) }}

</form>
<script type="text/javascript">
    initMaskCamposAdd($(document).find('#frm_cad_lancamento_add'));

    $(function(){
        $(document).find('#frm_cad_lancamento_add').find("#bt_salvar").off('click');
        $(document).find('#frm_cad_lancamento_add').find("#bt_salvar").on('click', function(event){
            event.stopPropagation();
            var form = $(document).find('#frm_cad_lancamento_add');
            var url = form.attr("action");
            $.ajax({
                url: url,
                dataType: 'json',
                data: form.serialize(),
                method: 'POST',
                success: function(callback){
                    if(callback.status === "success"){
                        $(document).find('#frm_cad_lancamento_add').parents(".modal").modal("hide");
                        if($(document).find("#form_filter").find('#data').val() != ''){
                            filterAjax($(document).find("#form_filter").serialize());
                        }
                        message("Atenção", "Dados salvos com sucesso!");
                    } else {
                        message("Atenção", callback.message);
                    }
                },  
                error: function(callback){
                    hide_loader();
                    var errors = callback.responseJSON.error;
                    form.find('.error-message').remove();
                    form.find('div, input, select, textarea').each(function(){
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
    
    function initMaskCamposAdd(form_modal_add){
        form_modal_add.find("#valor").maskMoney({thousands:'', decimal:','}); 
        
        form_modal_add.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true
        });
        form_modal_add.find('.data').mask('00/00/0000');
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection