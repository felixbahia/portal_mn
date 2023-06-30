@extends('layouts.page-dialog')

@section('content')
<form id='editar-segmento-form' onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $id) !!}
    <div class="row">
        <div class="form-group col-sm-6">
            {!! Form::label('cor_codigo', 'Cor de fundo') !!}
            {!! Form::text('cor_codigo', $cor_codigo, ['class' => 'form-control', 'id' => 'colorpicker-full']) !!}
        </div>
        <div class="form-group col-sm-12">
            {!! Form::label('descricao_modal_editar', 'Descrição do segmento') !!}
            {!! Form::text('descricao', $descricao, ['class' => 'form-control', 'id' => 'descricao_modal_editar']) !!}
        </div>
        <div class="form-group col-sm-12">
            {!! Form::label('posicao', 'Posição') !!}
            {!! Form::number('posicao', $posicao, ['class' => 'form-control', 'id' => 'posicao_modal_editar', 'min' => '1', 'maxlength' => '2']) !!}
        </div>
        <div class="form-group col-sm-6">
            <div class="form-group  form-check form-check-inline">
                {{ Form::label('mostrar', 'Sim', ['class'=>'form-check-label']) }}
                {{ Form::radio('mostrar', true, ($mostrar === true), ['id' => 'mostrar', 'class' => 'form-check-input']) }}
            </div>
            <div class="form-group  form-check form-check-inline">
                {!! Form::label('mostrar', 'Não', ['class'=>'form-check-label']) !!}
                {!! Form::radio('mostrar', false, ($mostrar === false || $mostrar === null), ['id' => 'mostrar', 'class' => 'form-check-input']) !!}
            </div>
        </div>
        <div class="form-group col-sm-12">
            {!! Form::label('imagem', 'Imagem do segmento') !!}
        </div>
        @if(!empty($imagem))
            <div class="form-group col-sm-12">
                <div data-toggle='tooltip' data-html='true' data-placement='right' title="Clique para expandir" class='img-container-thumbnail-300'>
                    <a href="{{ asset($imagem) }}" class="foto-thumb"> <img class="etiqueta-foto mt-2" src="{{ asset($imagem) }}" /></a>
                </div>
            </div>
        @endif
        <div class="form-group col-sm-12">
            {{ Form::file('imagem', ['id'=>'imagem', 'onchange'=>"this.parentNode.nextSibling.value = this.value" ], null) }}
        </div>
    </div>
    
    <div class="row">
        <div class="col text-right mt-2">
            {{ Form::button('Enviar', ['id'=> 'submit_modal_editar', 'class' => 'btn btn-success']) }}
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
        $(document).find(".foto-thumb").fancybox(
            {
                onComplete: function(){
                
                    $('#fancybox-content')
                        .on('mouseover', function(){
                            $(this).children('#fancybox-img').css({'transform': 'scale(1.5)'});
                        })
                        .on('mouseout', function(){
                            $(this).children('#fancybox-img').css({'transform': 'scale(1)'});
                        })
                        .on('mousemove', function(e){
                            $(this).children('#fancybox-img').css({'transform-origin': ((e.pageX - $(this).offset().left) / $(this).width()) * 100 + '% ' + ((e.pageY - $(this).offset().top) / $(this).height()) * 100 +'%'});
                        });
                }
            }
        ); 

        $(document).find("#submit_modal_editar").on('click', function(){
            var form = $(document).find('#editar-segmento-form');
            var formData = new FormData($(document).find('#editar-segmento-form')[0]);

            form.find('.error-message').remove();
            form.find('.error-input').removeClass('error-input');
            
            $.ajax({
                url: '{{ route('segmentos.salvar.edicao') }}',
                data: formData,
                processData: false,
                contentType: false,
                method: 'POST',
                success: function(callback){
                    if(callback.status === "success"){
                        buscarSegmentos();
                        $(document).find('#modal-editar-segmento').modal('hide');
                    }
                },
                error: function(callback){
                    var dados = callback.responseJSON;
                    mensagemErroEdit(dados);
                }
            });    
        })
    });

    function mensagemErroEdit(json_error){
        var form_modal_edit = $(document).find("#editar-segmento-form");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsEdit(form_modal_edit, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsEdit(form, input, message){

        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");

        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection