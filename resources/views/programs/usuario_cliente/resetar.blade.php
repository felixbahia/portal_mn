@extends('layouts.app-registrar')

@section('content')

<div class="login">
    <form action="#" id='form-usuario-resetar' onsubmit="return false">
        @csrf
        <div class='title'>
            <h3>Para reconfigurar sua senha, informe</h3>
        </div>
        <div class="row mb-2">
            <input id="usuario" class="form-control" name="usuario" placeholder="Seu usuário">
            <label for="usuario">Cliente, seu usuário é seu CPF se pessoa física, ou CNPJ se pessoa jurídica, sem pontos, barras ou traços. <br>Representante, seu usuário é o mesmo informado por seu gestor ou por e-mail.</label>
        </div>

        <div class="row-mb-2 text-center">
            <h3 class="text-white">OU</h3>
        </div>

        <div class="row mb-2">
            <input id="email" type="email" class="form-control" name="email" placeholder="Seu e-mail">
            <label for="usuario">O e-mail utilizado para cadastrar o usuário</label>
        </div>
        <div class="form-group">
            <button class="btn-login" id='btn-salvar'>ENVIAR INFORMAÇÕES</button>
        </div>
    </form>
</div>

@endsection

@section('script-footer')

    $(document).find('#form-usuario-resetar').on('submit', function(){

        form_data = $(this).serialize();

        $.ajax({
            url: '{{ route('usuario_cliente.nova_senha.salvar_requisicao_nova_senha') }}',
            type: 'POST',
            data: form_data,
            success: function(data){

                var response = data.response

                message('Sucesso!', '<p>Obrigado por utilizar nossos serviços.</p><p>Dentro de instantes você receberá nossa mensagem no e-mail <b>' + response.email + '</b> para o acesso da empresa ' + response.empresa + '.</p><p>Caso este e-mail não seja válido, entrar em contato com nosso setor de cadastro</p>', 'reset');

                $(document).find('.message-reset').css('height', '300px');
                $(document).find('.message-reset').on('dialogclose', function(){
                    document.location.href = '{{ route('login') }}';
                })

            },
            error: function(){
                message('Erro!', 'Usuário não localizado.');
            }
        });
    });

    function loader(){
        var $html_loader = "<div class='content-loader'><div class='loader'></div></div>";
        $("body").prepend($html_loader);
    }

@endsection