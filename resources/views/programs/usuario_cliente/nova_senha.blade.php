@extends('layouts.app-registrar')

@section('content')

<div class="login">
    <form action="#" id='form-usuario-resetar' onsubmit="return false">
        @csrf
        <input type='hidden' id="hash" name="hash" value="{{ $hash }}">
        <div class='title'>
            <h3>Escolha uma nova senha</h3>
        </div>
        <div class="row mb-2">
            <input type='password' id="senha" class="form-control" name="senha" placeholder="Nova senha">
        </div>
        <div class="row mb-2">
            <input type='password' id="confirma_senha" class="form-control" name="confirma_senha" placeholder="Confirme a nova senha">
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

    $(document).find('#form-usuario-resetar').on('submit', function(){

        $(document).find('.alert-registro').addClass('d-none');
        $(document).find('.alert-registro').html('');

        form_data = $(this).serialize();

        $.ajax({
            url: '{{ route('usuario_cliente.nova_senha.salvar') }}',
            type: 'POST',
            data: form_data,
            success: function(data){

                message('Sucesso!', 'Sua nova senha foi configurada. Você será redirecionado para a área de autenticação.', 'nova-senha');

                $(document).find('.message-nova-senha').on('dialogclose', function(){
                    document.location.href = '{{ route('login') }}';
                });

            },
            error: function(data){

                var errors = data.responseJSON.errors;

                $(document).find('.alert-registro').removeClass('d-none');

                for(var field in errors){
                    $(document).find('.alert-registro').append(errors[field] + '<br>');
                }
            }
        });
    });

    function loader(){
        var $html_loader = "<div class='content-loader'><div class='loader'></div></div>";
        $("body").prepend($html_loader);
    }

@endsection