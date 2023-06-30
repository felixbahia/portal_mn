@extends('layouts.app-coletor-login')

@section('content')
<div class="login">
    <div class="content_login">
        <form method="POST" action="{{ route('login') }}" aria-label="{{ __('Login') }}">
            @csrf
            <div class="logo-login">
                <img src="{{ URL::asset('/images/logotipo.png') }}" border="0" alt="" />
            </div>
            <div class="form-group input-username">
                <input id="username" type="text" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" name="username" value="{{ old('username') }}" required autofocus placeholder="Usuário">
            </div>
            <div class="form-group input-password">
                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required placeholder="Senha">
            </div>
            @if($errors->any())
                <div class="alert-login">
                    {{$errors->first()}}
                </div>
            @endif
            <div class="form-group">
                <button type="submit" class="btn-login">{{ __('Entrar') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
