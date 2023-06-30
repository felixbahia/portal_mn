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
            <b>Motivo da reprovação</b>
            {{ Form::textarea('motivo_reprovacao', '', ['id' => 'motivo_reprovacao', 'class' => 'form form-control', 'col' => '5', 'rows' => '4']) }}
        </div>
    </div>

    <div class="row">
        <div class="col">
            {{ Form::button('Cancelar requisição', ['class' => 'btn btn-success float-right mt-3', 'id' => 'btn-enviar-modal']) }}
        </div>
    </div>
</form>

<script>

    $(document).ready(function(){
        $(document).find('#btn-enviar-modal').on('click', function(){
            enviar();
        });
    })

    function enviar(){

        var form = $(document).find('#form-cancelamento-devolucao');

        var dados = form.serialize();

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route("devolucao_nota_aprovacao.cancelar") }}',
            method: 'POST',
            data: dados,
            success: function(){
                $(document).find("#cancelar-devolucao-modal").modal('hide');
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
        var $input = form.find("input[name='"+input+"'], textarea[name='"+input+"']");

        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection