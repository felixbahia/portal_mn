@extends('layouts.app')

@section('content')
<div class="conteudo_centralizado"  style="scroll-behavior: inherit;height: 1000px">
    <div class="content-filter-dialog">	
        <form action="" name="form_calculo_preco_base" id="form_calculo_preco_base" onsubmit="return false;">
            @csrf
            {!! Form::hidden('id', $dados['id'], ['id' => 'id']) !!}
            <h3>Cadastro {{ CustomView::programaName() }}</h3>
            <label id="ultima_atualizacao" name="ultima_atualizacao">{!! $dados['ultima_atualizacao'] !!}</label>
            <div class="content-fields">
                <div class="form-row">
                    <div class="form-group col-sm-6">
                        {!! Form::label('dolar_referencia', 'Dolar Referência', []) !!}
                        {{ Form::text('dolar_referencia', $dados['dolar_referencia'], ['id' => 'dolar_referencia', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Dolar Referência', 'maxlength' => '12']) }}
                    </div>
                    <div class="form-group col-sm-6">
                        {!! Form::label('pis', 'PIS(%)', []) !!}
                        {{ Form::text('pis', $dados['pis'], ['id' => 'pis', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'PIS', 'maxlength' => '12']) }}
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-6">
                        {!! Form::label('cofins', 'COFINS(%)', []) !!}
                        {{ Form::text('cofins', $dados['cofins'], ['id' => 'porto_origem', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'COFINS', 'maxlength' => '12']) }}
                    </div>
                    <div class="form-group col-sm-6">
                        {!! Form::label('capatazia', 'Capatazia', []) !!}
                        {{ Form::text('capatazia', $dados['capatazia'], ['id' => 'capatazia', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Capatazia', 'maxlength' => '12']) }}
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-6">
                        {!! Form::label('taxa_siscomex', 'Taxa Siscomex', []) !!}
                        {{ Form::text('taxa_siscomex', $dados['taxa_siscomex'], ['id' => 'taxa_siscomex', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Taxa Siscomex', 'maxlength' => '12']) }}
                    </div>
                    <div class="form-group col-sm-6">
                        {!! Form::label('sda', 'SDA', []) !!}
                        {{ Form::text('sda', $dados['sda'], ['id' => 'sda', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'SDA', 'maxlength' => '12']) }}
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-6">
                        {!! Form::label('honorarios', 'Honorários', []) !!}
                        {{ Form::text('honorarios', $dados['honorarios'], ['id' => 'honorarios', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Honorários', 'maxlength' => '12']) }}
                    </div>
                    <div class="form-group col-sm-6">
                        {!! Form::label('expediente', 'Expediente', []) !!}
                        {{ Form::text('expediente', $dados['expediente'], ['id' => 'expediente', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Expediente', 'maxlength' => '12']) }}
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-6">
                        {!! Form::label('armazenagem', 'Armazenagem', []) !!}
                        {{ Form::text('armazenagem', $dados['armazenagem'], ['id' => 'armazenagem', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Armazenagem', 'maxlength' => '12']) }}
                    </div>
                    <div class="form-group col-sm-6">
                        {!! Form::label('laudo', 'Laudo', []) !!}
                        {{ Form::text('laudo', $dados['laudo'], ['id' => 'laudo', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Laudo', 'maxlength' => '12']) }}
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-6">
                        {!! Form::label('agencia_maritima', 'Agência Marítima', []) !!}
                        {{ Form::text('agencia_maritima', $dados['agencia_maritima'], ['id' => 'agencia_maritima', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Agência Marítima', 'maxlength' => '12']) }}
                    </div>
                    <div class="form-group col-sm-6">
                        {!! Form::label('frete_rodoviario', 'Transporte Rodoviário', []) !!}
                        {{ Form::text('frete_rodoviario', $dados['frete_rodoviario'], ['id' => 'frete_rodoviario', 'class' => 'form-control decimal text-right input-label', 'placeholder' => 'Frete Rodoviário', 'maxlength' => '12']) }}
                    </div>
                </div>
            </div>
            <div class="col-sm-12 mt-5" id="button-bottom">
                {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
            </div> 
        </form>
    </div>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        form_modal = $(document).find("#form_calculo_preco_base");
        form_modal.find(".decimal").maskMoney({thousands:'.', decimal:','});

        form_modal.find("#btn-salvar").on('click', function(){
            modificarValor(form_modal);
        });
    });

    function modificarValor(form_modal){
        data_form = form_modal.serialize();
        limparMesagemErro();
        $.ajax({
            url: '{{ route('importacao.valor_padrao.modificar')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                form_modal.find("#id").val(data.response.id);
                form_modal.find("#ultima_atualizacao").html(data.response.ultima_atualizacao);
                message("Atenção", "Alterado com Sucesso!");
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