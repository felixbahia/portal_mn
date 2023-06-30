@extends('layouts.page-dialog')

@section('content')

<form action="#" id="frm_cad_motivo_edt" name="frm_cad_motivo_edt" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $dados['id']) !!}
    <div class="form-row">
        <div class="form-group">
            {{ Form::label('motivo', 'Motivo') }}
            {!! Form::textarea('motivo', $dados['motivo'], ['maxlength' => '250', 'rows' => '3', 'class' => 'form-control']) !!}
        </div>
    </div>
    {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'bt_salvar')) }}

</form>
<script>
    $(document).ready( function () {
        form_modal = $(document).find('#frm_cad_motivo_edt');

        form_modal.find("#bt_salvar").off('click');
        form_modal.find("#bt_salvar").on('click', function(){
            editarDados(form_modal);
        });
    });

    function editarDados(form_modal){
        data_form_modal = form_modal.serialize();
        $.ajax({
            url: "{{ route('produto.ajuste_estoque.motivo.editar') }}", 
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
                limparMesagemErroModal(form_modal);
                mensagemErroModal(form_modal, dados);
            }
        });
    }

    function limparMesagemErroModal(){      
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroModal(form_modal, json_error){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                console.log(field);
                console.log(json_error.error[field]);
                showErrorsInputsModal(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form_modal, input, message){
        var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"'], textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection