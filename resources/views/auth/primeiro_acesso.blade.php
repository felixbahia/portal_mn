@extends('layouts.app-login')

@section('content')
<div class="login">
    <div class="content_login">
        <form method="POST" action="{{ route('change_password') }}" aria-label="{{ __('Alteração de senha') }}">
            @csrf
            <div class="logo-login">
                <img src="{{ URL::asset('/images/logotipo.png') }}" border="0" alt="" />
            </div>
            <div class="form-group input-password">
                <input id="senha" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="senha" required placeholder="Senha Nova">
            </div>
            <div class="form-group input-password">
                <input id="confirmacao_senha" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="confirmacao_senha" required placeholder="Confirmação de Senha Nova">
            </div>
            @if($errors->any())
                <div class="alert-login">
                    {{$errors->first()}}
                </div>
            @endif
            <div class="form-group">
                <button type="submit" class="btn-login">{{ __('Alterar senha') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
