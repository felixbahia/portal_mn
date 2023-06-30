@extends('layouts.app-sem-header')

@section('content')
<div class="mx-auto w-100">
    <div class="w-100 formulario-pesquisa-satisfacao">	
        <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
            @csrf
            <div class="w-100 fundo-azul">
                <div class="w-50 content-logo-pesquisa-satisfacao">        
                    <a href="{{ route('home') }}">
                        <img src="{{ URL::asset('/images/logotipo.png') }}" alt=""/>
                    </a>
                </div>
                <div class="w-50 dados-cliente">
                    <h3 class="texto-laranja">Pesquisa de Satisfação</h3>
                    {{ Form::label('label_cliente', 'Cliente: '.$cliente['nome'],['class' => 'text-light']) }}<br>
                    {{ Form::label('label_nota', 'Nota(s): '.$cliente['notas'],['class' => 'text-light']) }}<br>
                    {{ Form::hidden('id', $cliente['id'])}}
                </div>
            </div>
        @foreach($estrutura_formulario as $formulario)
            <div class="w-50 p-3 questoes">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-12 corpo-pergunta fundo-azul">
                            <div class="balao-pesquisa-formulario">
                                <div class="balao-pesquisa-formulario-laranja">
                                    {{ $formulario->ordem_pergunta}}
                                </div>
                            </div> 
                            <div class="w-75 mt-1 pergunta">
                                {{ $formulario->pergunta }}
                            </div>
                            {{ Form::hidden('pergunta['.$formulario->id.']', $formulario->pergunta)}}
                            {{ Form::hidden('tipo_pergunta['.$formulario->id.']', $formulario->pesquisa_satisfacao_formulario_tipo_respostas_id)}}
                            <div id="{{'erro-perunta-pesquisa-'.$formulario->id}}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 texto-azul">
                    <div class="row col-md-12">
                        <div class="col-12">
                            @if($formulario->pesquisa_satisfacao_formulario_tipo_respostas_id == 1)
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', 'muito_satisfeito', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, 'Muito Satisfeito', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', 'satisfeito', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, 'Satisfeito', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', 'nem_satisfeito_ou_insatisfeito', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, 'Nem satisfeito ou insatisfeito', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', 'insatisfeito', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, 'Insatisfeito', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', 'muito_insatisfeito', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, 'Muito insatisfeito', ['class'=>'form-check-label']) }}
                                </div>
                            @elseif($formulario->pesquisa_satisfacao_formulario_tipo_respostas_id == 2)
                                <div class="row">
                                    <div class="col">
                                        {{ Form::textarea('resposta['.$formulario->id.']', '', ['class' => 'form form-control mt-2', 'id' => 'resposta['.$formulario->id.']', 'col' => '5', 'rows' => '4', 'maxlength' => '500']) }}
                                    </div>
                                </div>
                            @elseif($formulario->pesquisa_satisfacao_formulario_tipo_respostas_id == 3)
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', 'Revenda de Tecidos', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, 'Revenda de Tecidos', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', 'Confecção (profissional)', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, 'Confecção (profissional)', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', 'Confecção (moda)', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, 'Confecção (moda)', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', 'Varejo (vestuário)', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, 'Varejo (vestuário)', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', 'Atacado (vestuário)', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('segmento_s'.$formulario->id, 'Atacado (vestuário)', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', 'Hospital', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('segmento_s'.$formulario->id, 'Hospital', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', 'Hotel', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('segmento_s'.$formulario->id, 'Hotel', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', 'Órgão Público', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('segmento_s'.$formulario->id, 'Órgão Público', ['class'=>'form-check-label']) }}
                                </div>
                            @elseif($formulario->pesquisa_satisfacao_formulario_tipo_respostas_id == 4)
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', '0', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, '0 ', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', '1', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, '1', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', '2', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, '2', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', '3', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, '3', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', '4', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, '4', ['class'=>'form-check-label']) }}
                                </div>
                                <div class="form-check">
                                    {{ Form::radio('resposta['.$formulario->id.']', '5', '', ['class' => 'form-check-input', 'id'=>'resposta_s'.$formulario->id]) }}
                                    {{ Form::label('resposta_s'.$formulario->id, '5', ['class'=>'form-check-label']) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
            <div class="w-100 p3 fundo-azul rodape">
                <div class="col-md-12 texto-laranja botao">
                    <h3> 
                        {{ Form::button('Enviar Pesquisa', array('class' => 'balao-pesquisa-formulario-botao', 'id' => 'btn-filterform')) }}
                    </h3>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script-footer')        
    
    $(document).ready(function(){
        $(document).find('#btn-filterform').on('click', function(){
            filterAjax();
        });

    });

    function filterAjax(){
        form = $(document).find('#form_filter');

        formulario = form.serialize();
    
        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');
    
        $.ajax({
            url: '{{ route('pesquisa_satisfacao_formulario.gravar_pesquisa') }}',
            data: formulario,
            method: 'POST',
            success: function(data){     
                $('#form_filter')[0].reset();
                if(data.status == 'success'){
                    var $name_option_ok = "ok";
                    var $name_option_cancelar = "cancelar";

                    $(document).off("ok");
                    $(document).on("ok", function(){
                        window.location.href = "{{ route("login") }}";
                        return null;
                    });

                    $(document).off("cancelar");
                    $(document).on("cancelar", function(){
                        window.location.href = "{{ route("login") }}";
                        return null;
                    });

                    message_option("Atenção", data.message, 'success', $name_option_ok,null,$name_option_cancelar);
                }  
            },
            error: function(callback){
                hide_loader();
                var errors = callback.responseJSON.error;
                var count = 0;
                
                form.find('.error-message').remove();
                form.find('div, input, radio,hidden').each(function(){
                    if($(this).hasClass("is-invalid")){
                        $(this).removeClass("is-invalid")
                    }
                    if($(this).hasClass("error-input")){
                        $(this).removeClass("error-input")
                    }
                });
                
                for(var field in errors){
                    motrarErrosInputs(form, field, errors[field]);
                }
                if(form.find('.error-message').length){
                    var id_tab = form.find('.error-message').eq(0).parents(".tab-pane").attr("aria-labelledby");
                    $(document).find("#"+id_tab).tab('show');
                    form.find('.error-message').eq(0).focus();
                }
            }
        }).always(function() {
            hide_loader();
        });
    }

    function motrarErrosInputs(form, input, message){
		var inputexplode = input.split(".");

        if(inputexplode[0] == 'resposta'){
            var id_pergunta = 'erro-perunta-pesquisa-'+inputexplode[1];
            var $input = $(document).find("#"+id_pergunta).parent();
            $input.after("<label class='col-md-12 error-message' for='"+input+"'>"+message+"</label>");
        }

    }
    
    function loader(){
        var $html_loader = "<div class='content-loader'><div class='loader'></div></div>";
        $("body").prepend($html_loader);
    }
 
@endsection