@extends('layouts.page-dialog')

@section('content')

@if($dados['editavel'])
<form action="{{ route('lancamento_deb_cred_vendedor.editar') }}" id="frm_cad_lancamento_edt" name="frm_cad_lancamento_edt" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $dados['id']) !!}
    <div class="form-row">
        <div class="form-group">
            {{ Form::label('vendedor', 'Vendedor') }}
            {{ Form::select("vendedor", $vendedor, $dados['vendedor'], ["class"=>"form-control"]) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            {{ Form::label('documento', 'Documento') }}
            {!! Form::text('documento', $dados['documento'], ['id' => 'documento', 'maxlength' => '10', 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-6">
            {{ Form::radio('tipo', 'D', $dados['tipo']=='D'?true:false, ['id' => 'tipo_d']) }}
            <label for="tipo_d" class="radio-inline">Débito</label>
        </div>
        <div class="form-group col-sm-6">
            {{ Form::radio('tipo', 'C', $dados['tipo']=='C'?true:false, ['id' => 'tipo_c']) }}
            <label for="tipo_c" class="radio-inline">Crédito</label>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('motivo', 'Motivo') }}
            {{ Form::select("motivo", $motivo, $dados['motivo'], ["class"=>"form-control"]) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            {{ Form::label('data', 'Data do Lançamento', []) }}
            {{ Form::text('data', $dados['data'], ['id' => 'data', 'class' => 'form-control data', 'placeholder' => 'Data DD/MM/AAAA']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            {{ Form::label('valor', 'Valor') }}
            {!! Form::text('valor', $dados['valor'], ['maxlength' => '8', 'class' => 'form-control']) !!}
        </div>
    </div>
    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'bt_salvar')) }}

</form>
@else
<div>
    <div class="row my-2">
        <div class="col">
            <b>Vendedor:</b><br>
            {{ $vendedor[$dados['vendedor']] }}
        </div>
    </div>
    <div class="row my-2">
        <div class="col">
            <b>Documento:</b><br>
            {{ $dados['documento'] }}
        </div>
    </div>
    <div class="row my-2">
        <div class="col">
            <b>Tipo:</b><br>
            @if($dados['tipo']=='C')
            Crédito
            @elseif($dados['tipo']=='D')
            Débito
            @endif
        </div>
    </div>
    <div class="row my-2">
        <div class="col">
            <b>Motivo:</b><br>
            {{ $motivo[$dados['motivo']] }}
        </div>
    </div>
    <div class="row my-2">
        <div class="col">
            <b>Data do lançamento:</b><br>
            {{ $dados['data'] }}
        </div>
    </div>
    <div class="row my-2">
        <div class="col">
            <b>Valor:</b><br>
            {{ $dados['valor'] }}
        </div>
    </div>
</div>
@endif
<script type="text/javascript">
    initMaskCamposEdt($(document).find('#frm_cad_lancamento_edt'));

    $(function(){
        $(document).find('#frm_cad_lancamento_edt').find("#bt_salvar").off('click');
        $(document).find('#frm_cad_lancamento_edt').find("#bt_salvar").on('click', function(event){
            event.stopPropagation();
            var form = $(document).find('#frm_cad_lancamento_edt');
            var url = form.attr("action");
            $.ajax({
                url: url,
                dataType: 'json',
                data: form.serialize(),
                method: 'POST',
                success: function(callback){
                    if(callback.status === "success"){
                        $(document).find('#frm_cad_lancamento_edt').parents(".modal").modal("hide");
                        filterAjax($(document).find("#form_filter").serialize());
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

    function initMaskCamposEdt(form_modal_edt){
        form_modal_edt.find("#valor").maskMoney({thousands:'', decimal:','}); 
        
        form_modal_edt.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true
        });
        form_modal_edt.find('.data').mask('00/00/0000');
    }
</script>
@endsection