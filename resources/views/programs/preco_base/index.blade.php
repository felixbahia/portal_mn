@extends('layouts.app')

@section('content')
<div class="conteudo_centralizado"  style="scroll-behavior: inherit;height: 1000px">
    <div class="content-filter-dialog">	
        <form action="" name="form_calculo_preco_base" id="form_calculo_preco_base" onsubmit="return false;">
            @csrf
            <h3>Calculo {{ CustomView::programaName() }}</h3>
            <div class="content-fields">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('preco_final', 'Preço Final', []) }}
                    {{ Form::text('preco_final', '', ['id' => 'preco_final', 'class' => 'form-control text-right', 'placeholder' => 'Preço Final', 'maxlength' => '8']) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('origem', 'Origem', []) }}
                    {{ Form::select('origem', $origem, '', ['id' => 'origem', 'class' => 'form-control', 'placeholder' => 'Origem', 'maxlength' => '8']) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('estado', 'Destino', []) }}
                    {{ Form::select('estado', $estados, '', ['id' => 'estado', 'class' => 'form-control', 'placeholder' => 'Destino', 'maxlength' => '8']) }}
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('prazo_medio', 'Prazo Médio', []) }}
                    <select name="prazo_medio" id="prazo_medio" class='form-control'>    
                        <option value="0">À vista</option>
                        <option value="15">Prazo 15 dias</option>
                        <option value="30">Prazo 30 dias</option>
                        <option value="45">Prazo 45 dias</option>
                        <option value="60">Prazo 60 dias</option>
                        <option value="75">Prazo 75 dias</option>
                        <option value="90">Prazo 90 dias</option>
                        <option value="120">Prazo 120 dias</option>
                    </select>
                </div>
                <div class="form-group col-sm-12">
                    {{ Form::label('frete', 'Frete', []) }}
                    <select name="frete" id="frete" class='form-control'>  
                        <option value=''>Frete</option>
                            <option value="fob">FOB</option>
                            <option value="cif">CIF</option>
                    </select>
                </div>

                <div class="form-group col-sm-12">
                    {{ Form::label('tipo_cliente', 'Tipo Cliente', []) }}
                    <select name="tipo_cliente" id="tipo_cliente" class='form-control'>
                        <option value="normal">Contribuinte</option>
                        <option value="isento">Isento</option>
                    </select>
                </div>

                <div class="form-group col-sm-12">
                    {{ Form::label('procedencia', 'Procedência', []) }}
                    <select name="procedencia" id="procedencia" class='form-control'>
                        <option value="nacional">Nacional</option>
                        <option value="internacional">Internacional</option>
                    </select>
                </div>

                <div class="form-group col-sm-12">
                    {{ Form::label('resultado', 'Resultado (FOB à Vista)', []) }}
                    {{ Form::text('resultado', '', ['id' => 'resultado', 'class' => 'form-control text-right', 'placeholder' => 'Resultado', 'maxlength' => '8', 'readonly']) }}
                </div>
            </div>
            <div class="col-sm-12 mt-5" id="button-bottom">
                {{ Form::button('Calcular', array('class' => 'btn btn-primary float-right', 'id' => 'btn-calcular')) }}
            </div> 
        </form>
    </div>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        form_modal = $(document).find("#form_calculo_preco_base");
        form_modal.find("#preco_final").maskMoney({thousands:'', decimal:','});

        form_modal.find("#btn-calcular").on('click', function(){
            calcularPrecoBase(form_modal);
        });
    });

    function calcularPrecoBase(form_modal){
        data_form = form_modal.serialize();
        limparMesagemErro();
        $.ajax({
            url: '{{ route('preco_base.calculo')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                form_modal.find('#resultado').val(data.response.preco_base);
            },
            error: function(data){
                var dados = data.responseJSON;
                mensagemErro(dados, form_modal);
            }
        });
    }

    function mensagemErro(json_error, form_modal){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputs(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputs(form_modal, input, message){
        var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function limparMesagemErro(){      
        var form_modal = $("#form_calculo_preco_base");
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span').removeClass('error-input');
    }
@endsection