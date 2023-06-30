@extends('layouts.page-dialog')

@section('content')
<form id='novo-segmento-form' onsubmit="return false;">
    @csrf
    <div class="row">
         <div class="form-group col-sm-6">
            {!! Form::label('cor_codigo', 'Cor de fundo') !!}
            {!! Form::text('cor_codigo', '', ['class' => 'form-control', 'id' => 'colorpicker-full']) !!}
        </div>
        <div class="form-group col-sm-12">
            {!! Form::label('descricao_modal_novo', 'Descrição do segmento') !!}
            {!! Form::text('descricao', '', ['class' => 'form-control', 'id' => 'descricao']) !!}
        </div>
        <div class="form-group col-sm-12">
            {!! Form::label('posicao', 'Posição') !!}
            {!! Form::number('posicao', '', ['class' => 'form-control', 'id' => 'posicao_modal_novo', 'min' => '1', 'maxlength' => '2']) !!}
        </div>
        <div class="form-group col-sm-6">
            <div class="form-group  form-check form-check-inline">
                {{ Form::label('mostrar', 'Sim', ['class'=>'form-check-label']) }}
                {{ Form::radio('mostrar', true, '', ['id' => 'mostrar', 'class' => 'form-check-input']) }}
            </div>
            <div class="form-group  form-check form-check-inline">
                {!! Form::label('mostrar', 'Não', ['class'=>'form-check-label']) !!}
                {!! Form::radio('mostrar', false, '', ['id' => 'mostrar', 'class' => 'form-check-input']) !!}
            </div>
        </div>
        <div class="form-group col-sm-12">
            {!! Form::label('imagem', 'Imagem do segmento') !!}
        </div>
        <div class="form-group col-sm-12">
            {{ Form::file('imagem', ['id'=>'imagem', 'onchange'=>"this.parentNode.nextSibling.value = this.value" ], null) }}
        </div>
    </div>
    <div class="row">
        <div class="col text-right mt-2">
            {{ Form::button('Enviar', ['id'=> 'submit_modal_novo', 'class' => 'btn btn-success']) }}
        </div>
    </div>
</form>

<script>
    $(document).ready(function(){
        $('#colorpicker-full').colorpicker({
        parts:      'full',
        alpha:      true,
        showOn:     'both',
        buttonColorize: true,
        showNoneButton: true
    });

        $(document).find("#submit_modal_novo").on('click', function(){

            var form = $(document).find("#novo-segmento-form");
            var formData = new FormData($(document).find('#novo-segmento-form')[0]);

            form.find('.error-message').remove();
            form.find('.error-input').removeClass('error-input');

            $.ajax({
                url: '{{ route('segmentos.salvar.novo') }}',
                data: formData,
                processData: false,
                contentType: false,
                method: 'POST',
                success: function(callback){
                    if(callback.status === "success"){
                        buscarSegmentos();
                        $(document).find('#modal-novo-segmento').modal('hide');
                    }
                },
                error: function(callback){
                    var dados = callback.responseJSON;
                    mensagemErroAdd(dados);
                }
            });    
        })
    });

    function mensagemErroAdd(json_error){
        var form_modal_add = $(document).find("#novo-segmento-form");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_add, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form, input, message){

        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");

        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection