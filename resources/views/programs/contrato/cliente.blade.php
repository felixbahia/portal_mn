@extends('layouts.app-deslogado')

@section('content')
<div style="max-width: 1200px;margin-left: auto;margin-right: auto;">

    <form action="" name="form_contrato_cliente" id="form_contrato_cliente"  onsubmit="return false;">
        @csrf
        <label>
            <p><h4>{{ Auth::user()->name }}, seja bem-vindo! Obrigado por aceitar nosso convite!</h4>
            <p><h5>Ao acessar Portal MN você garante diversas facilidades, tais como 2ª via de boletos e notas fiscais, baixar o XML da sua nota, consultar seu histórico financeiro e muito mais!</h5>
            <p><h5>Para continuar a utilizar nossos serviços favor ler e dar aceite ao documento, para saber como tratamos seus dados, acesse nossa <a href="http://www.tecidosmn.com.br/sobre-nos/politicas"  target="_blank">Politica de privacidade</a>.</h5>
            <div class="text-right"><h5><a href="{{ URL::asset('/pdf/Contrato_fornecimento_digitalizado.pdf') }}" target="_blanck" class="bt_manual_cliente text-right"><i class='btn-nota-pdf'></i>Contrato</a></h5></div>
        </label>   
        <div class="embed-responsive embed-responsive-21by9">
            <iframe class="embed-responsive-item" src="{{ URL::asset("/contrato/fornecimento") }}" style="border: 3px solid #000; border-radius: 7px"  id="iframe_contrato" name="iframe_contrato" on></iframe>
        </div>

        <div class="center_content mt-1" style="position: relative;text-align: center;" id="button-center">
            {{ Form::submit('Aceito', array('class' => 'btn btn-primary', 'id' => 'btn-aceito')) }}
            {{ Form::button('Recuso', array('class' => 'btn btn-primary', 'id' => 'btn-recuso')) }}
        </div> 

    </form>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        form_modal = $(document).find("#form_contrato_cliente");

        form_modal.find("#btn-aceito").on('click', function(){
            aceitoContrato(form_modal);
        });

        form_modal.find("#btn-recuso").on('click', function(){
            recusoContrato(form_modal);
        });
    });

    function aceitoContrato(form_modal){
        data_form = form_modal.serialize();
        $.ajax({
            url: "{{ route('contrato.cliente.aceito') }}", 
            data: data_form,
            method: 'POST',
            success: function(callback){
                window.location.replace(callback.response.rota);
            },
            error: function(callback){
            }
        });
    }

    function recusoContrato(form_modal){
        mensagem = "Ao recusar o contrato, não será possível utilizar o portal, tem certeza?<br><br><br>"
        var name_option_ok = "recusado_contrato";
        var $class = "dialog_option_deletar";
        message_option("Atenção!", mensagem, $class, name_option_ok, '', '', '');

        $(document).off("recusado_contrato");
        $(document).on("recusado_contrato", function(){
            event.preventDefault(); document.getElementById('logout-form').submit();
        });
    }

@endsection