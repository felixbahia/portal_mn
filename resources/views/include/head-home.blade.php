<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="page-sem-header">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'MN Tecidos') }} @yield('title')</title>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('includes/jQueryUi/jquery-ui.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/jquery.fancybox-1.3.4.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">

    <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
    <link rel="manifest" href="/images/site.webmanifest">
    <link rel="mask-icon" href="/images/safari-pinned-tab.svg" color="#003554">
    <meta name="msapplication-TileColor" content="#003554">
    <meta name="msapplication-TileImage" content="/images/mstile-144x144.png">
    <meta name="theme-color" content="#003554">

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-4XKSV0C58K"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        @if(!empty(Auth::user()->username))
            gtag('config', 'G-4XKSV0C58K', {
                'user_id': '{{Auth::user()->username}}'
            });
            gtag('set', 'user_properties', {
                'crm_id' : '{{Auth::user()->username}}'
            });
        @else
            gtag('config', 'G-4XKSV0C58K');
        @endif
    </script>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="content-logo">
                <a href="{{ route('home') }}">
                    <img src="{{ URL::asset('/images/logotipo.png') }}" border="0" alt="" />
                </a>
            </div>
            <div class="content-menu">
                <a href="{{ route('favoritos.index') }}">
                <img src="{{ Storage::url('public/icons/modulo/favorito-icon.png') }}" border="0" alt=""/>
                <span>Favoritos</span>
            </a>
        </div>
        <div class="content-menu">
            <a href="{{ route('tutorial.index') }}">
                <img src="{{ Storage::url('public/icons/modulo/tutorial-icon.png') }}" border="0" alt=""/>
                <span>Tutorial</span>
            </a>
        </div>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navHeader" aria-controls="navHeader" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navHeader">
                <div class="content-info-user">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                        {{ Auth::user()->name }} <span class="caret"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="{{ route('usuario.editar_dados') }}">
                            {{ __('Editar dados') }}
                        </a>
                        <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            {{ __('Logout') }}
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <div class="content-nav-header">
    </div>
    @if(env('APP_HOST') !== 'producao' && !empty(env('APP_HOST')))
    <div class="local_app">Está no ambiente {{ env('APP_HOST') }} </div>
    @if(env('APP_HOST') !== 'HOMOLOGAÇÃO')
    <div class="local_app">Banco Portal {{ env('DB_HOST') }} </div>
    <div class="local_app">Banco Nasajon {{ env('DB_NASAJON_HOST') }} </div>
    @endif
    @endif

    <div id="app">