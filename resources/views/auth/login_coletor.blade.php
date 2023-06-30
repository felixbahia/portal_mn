@extends('layouts.app-coletor-login')

@section('content')
<div class="login">
    <div class="content_login">
        <form method="POST" action="{{ route('login') }}" aria-label="{{ __('Login') }}">
            @csrf
            <div class="logo-login">
                <img src="{{ URL::asset('/images/logotipo.png') }}" border="0" alt="" />
            </div>
            @if($errors->any())
                <div class="alert-login">
                    {{$errors->first()}}
                </div>
            @endif
            <div class="form-group input-username">
                <label for="username">Usuário:</label>
                <input id="username" type="text" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" name="username" value="{{ old('username') }}" autofocus>
            </div>
            <div class="form-group input-password">
                <label for="password">Senha:</label>
                <input id="password" type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn-login">{{ __('Entrar') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
