@extends('layouts.page-dialog')

@section('content')
<form action="#" id='form-cancelamento-devolucao' onsubmit='return false;'>
    @csrf
    {{ Form::hidden('id', $id) }}
    <h5>Reprovação da requisição</h5>

    <div class="row">
        <div class="col-sm-6">
            <p>
                <b>Estabelecimento</b><br>
                {{ $estabelecimento }}
            </p>
        </div>
        <div class="col-sm-3">
            <p>
                <b>Nota</b><br>
                <a href="#" data-toggle="tooltip" data-placement="top" title="Detalhes no Nasajon" onclick="showModalNotaModal('{!! $nota_id !!}')">{!! $nota_fiscal !!}</a>
            </p>
        </div>
        <div class="col-sm-3">
            <p>
                <b>Valor</b><br>
                {{ $valor }}
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-sm">
            <p>
                <b>Cliente</b><br>
                {{ $cliente }}
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-sm" id='valor-div'>
            <p>
                @if($valor_parcial === true)             
                <b>Valor da devolução</b><br>
                {!! $valor_devolvido !!}
                @else
                <b>Devolução total</b>
                @endif
            </p>
        </div>
        <div class="col-sm">
            <p>
                <b>Motivo</b><br>
                {!! $motivo !!}
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> Responsabilidade do Frete
            <div class="row mt-2">
                <div class="col">
                    {!! Form::radio('responsabilidade_frete', 'textil', $responsabilidade_frete == 'textil', ['id' => 'responsabilidade_frete_textil', 'class'=>'responsabilidade_frete_radio']) !!}
                    {!! Form::label('responsabilidade_frete_textil', 'Têxtil MN', ['class'=>'input-label']) !!}
                </div>
                <div class="col">
                    {!! Form::radio('responsabilidade_frete', 'cliente', $responsabilidade_frete == 'cliente', ['id' => 'responsabilidade_frete_cliente', 'class'=>'responsabilidade_frete_radio']) !!}
                    {!! Form::label('responsabilidade_frete_cliente', 'Cliente', ['class'=>'input-label']) !!}
                </div>
                <div class="col">
                    {!! Form::radio('responsabilidade_frete', 'representante', $responsabilidade_frete == 'representante', ['id' => 'responsabilidade_frete_representante', 'class'=>'responsabilidade_frete_radio']) !!}
                    {!! Form::label('responsabilidade_frete_representante', 'Representante', ['class'=>'input-label']) !!}
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            {!! Form::label('frete_valor', 'Valor do Frete', ['class'=>'input-label']) !!}
            {!! Form::text('frete_valor', $frete_valor, ['id'=>'frete_valor', 'class' => 'text-right']) !!}
        </div>
    </div>

    <div class="row">
        <div class="col">
            {{ Form::button('Salvar', ['class' => 'btn btn-success float-right mt-3', 'id' => 'btn-enviar-modal']) }}
        </div>
    </div>
</form>

<script>

    $(document).ready(function(){
        $(document).find('#btn-enviar-modal').on('click', function(){
            enviar();
        });

        $(document)
            .find('#frete_valor')
            .maskMoney({thousands:'.', decimal:','});
    })

    function enviar(){

        var form = $(document).find('#form-cancelamento-devolucao');

        var dados = form.serialize();

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route("devolucao_nota_aprovacao.frete") }}',
            method: 'POST',
            data: dados,
            success: function(){
                $(document).find("#frete-devolucao-modal").modal('hide');
                buscarNotas();
            },
            error: function callback(data){
                var errors = data.responseJSON.error;

                form.find('.error-message').remove();
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        })
    }

    function showErrorsInputs(form, input, message){
        if(input == 'responsabilidade_frete'){
            var $input = form.find("input[name='"+input+"'], textarea[name='"+input+"']").parent().parent();
        }
        else{
            var $input = form.find("input[name='"+input+"'], textarea[name='"+input+"']");
        }

        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection