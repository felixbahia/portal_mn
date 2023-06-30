@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_email_previa" id="form_email_previa" onsubmit="return false;">
    @csrf
    {!! Form::hidden('hash', $hash, ['id' => 'hash']) !!}
    {!! Form::hidden('id', $id, ['id' => 'id']) !!}
    {!! Form::hidden('tipo', 'previa', ['id' => 'tipo']) !!}
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('email', 'E-mail', []) }}
            {{ Form::text('email', '', ['id' => 'email', 'class' => 'form-control', 'placeholder' => 'E-mail']) }}
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Enviar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-enviar_previa')) }}
    </div> 
</form>
<script>
    $(document).ready(function(){
        form_modal_email_previa = $(document).find("#form_email_previa");

        form_modal_email_previa.find("#btn-enviar_previa").off('click');
        form_modal_email_previa.find("#btn-enviar_previa").on('click', function(){
            if(form_modal_email_previa.find("#id").val() == ''){
                salvarRenegociacao(form_modal_email_previa);
            }else{
                editarRenegociacao(form_modal_email_previa);
            }
        });
    });

    function salvarRenegociacao(form_modal_email_previa){
        data_form_modal_email_previa = form_modal_email_previa.serialize();
        $.ajax({
            url: '{{ route('renegociacao_titulo.salvar_renegociacao') }}',
            type: 'post',
            data: data_form_modal_email_previa,
            success: function(callback){
                $(form_modal_email_previa).parents('.modal').modal('hide');
                filterAjaxModalSelecionarTitulosRenegociacao();
                message("Atenção", "Renegociação enviada com sucesso!");
            },
            error: function(callback) {
                var dados = callback.responseJSON;
                var dados = callback.responseJSON;
                message("Atenção", dados.error.email);
            }
        });
    }

    function editarRenegociacao(form_modal_negociacao){
        data_form_modal_email_previa = form_modal_email_previa.serialize();
        $.ajax({
            url: '{{ route('renegociacao_titulo.editar_renegociacao') }}',
            type: 'post',
            data: data_form_modal_negociacao,
            success: function(callback){
                $(form_modal_negociacao).parents('.modal').modal('hide');
                filterAjax();
                message("Atenção", "Renegociação enviada com sucesso!");
            },
            error: function(callback) {
                var dados = callback.responseJSON;
                message("Atenção", dados.error.email);
            }
        });
    }

</script>
@endsection