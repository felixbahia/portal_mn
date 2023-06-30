@extends('layouts.app-registrar')

@section('content')

<div class="login">
    <form action="#" id='form-usuario-cliente' onsubmit="return false">
        @csrf
        <div class='title'>
            <h2>Caro cliente<br>Faça seu registro</h2>
            <h5><a href="{{ URL::asset('/pdf/manual_do_cliente.pdf') }}" target="_blanck" class="bt_manual_cliente"><i class='btn-nota-pdf'></i>Manual do cliente</a></h5>
        </div>
        <div class="row mb-2">
            <input id="cpf_cnpj" class="form-control" name="cpf_cnpj" placeholder="CPF/CNPJ">
        </div>
        <div class="row mb-2">
            <input id="nome" class="form-control" name="nome" readonly placeholder="Cliente">
        </div>
        <div class="row mb-2">
            <input id="email" type="email" class="form-control" name="email" placeholder="E-mail">
        </div>
        <div class="row mb-2">
            <input id="email-confirmacao" type="email" class="form-control" name="email-confirmacao" placeholder="Confirme o E-mail">
        </div>
        <div class="row mb-2">
            <input id="telefone" class="form-control" name="telefone" placeholder="Telefone">
        </div>
        <div class="alert-registro d-none">
        </div>
        <div class="form-group">
            <button class="btn-login" id='btn-salvar'>ENVIAR INFORMAÇÕES</button>
        </div>
    </form>
</div>

@endsection

@section('script-footer')
    $(document).ready(function(){

        cnpjParaNome();

        $(document).find('#cpf_cnpj').on('change', function(){
            cnpjParaNome();
        });

        var options_cpf_cnpj =  {
            onKeyPress: function(cpf_cnpj, e, field, options_cpf_cnpj) {
                var masks = ['000.000.000-009', '00.000.000/0000-00'];
                var mask = (cpf_cnpj.length>14) ? masks[1] : masks[0];
                $(document).find('#cpf_cnpj').mask(mask, options_cpf_cnpj);
            }
        };

        var options_telefone =  {
            onKeyPress: function(telefone, e, field, options_telefone) {
                var masks = ['(00) 0000-00009', '(00) 00000-0000'];
                var mask = (telefone.length>14) ? masks[1] : masks[0];
                $(document).find('#telefone').mask(mask, options_telefone);
            }
        };

        $(document).find("#cpf_cnpj").mask('000.000.000-009', options_cpf_cnpj);

        $(document).find("#telefone").mask('(00) 00000-0000', options_telefone)

        $(document).find("#form-usuario-cliente").on('submit', function(){

            $(document).find('.alert-registro').addClass('d-none');
            $(document).find('.alert-registro').html('');

            var form_data = $(document).find("#form-usuario-cliente").serialize();

            $.ajax({
                url: '{{ route('usuario_cliente.salvar') }}',
                type: 'POST',
                data: form_data,
                success: function(data){

                    if(data.response.salvo == 'usuario'){
                        message('Sucesso!', 'Seu login e senha serão enviados em intantes para seu e-mail.', 'usuario');
                    }
                    else if(data.response.salvo == 'aprovacao'){
                        message('Sucesso!', 'Estamos confirmando suas informações e em breve retornaremos o contato. <br>Obrigado!', 'usuario');                            
                    }

                    $(document).find('.message-usuario').css('height', '150px');

                    $(document).find('.message-usuario').on('dialogclose', function(){
                        document.location.href = '{{ route('login') }}';
                    })

                },
                error: function(data){

                    var errors = data.responseJSON.errors;

                    $(document).find('.alert-registro').removeClass('d-none');

                    for(var field in errors){
                        $(document).find('.alert-registro').append(errors[field] + '<br>');
                    }
                }
            });

            $(document).find("#cpf_cnpj").trigger('keypress');
            $(document).find("#telefone").trigger('keypress');
        });
    });

    function cnpjParaNome(){
        $(document).find('#nome').val('');
        if($(document).find('#cpf_cnpj').val() == ''){
            return false;
        }
        $.ajax({
            url: '{{ route('usuario_cliente.cnpj_para_nome') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                cpf_cnpj: $(document).find('#cpf_cnpj').val()
            },
            success: function(data){
                $(document).find('#nome').val(data.response.nome);
                $(document).find('#email').focus();
            }
        });
    }

    function loader(){
        var $html_loader = "<div class='content-loader'><div class='loader'></div></div>";
        $("body").prepend($html_loader);
    }

@endsection