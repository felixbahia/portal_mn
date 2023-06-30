<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'MN Tecidos') }}</title>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('includes/jQueryUi/jquery-ui.min.css') }}" rel="stylesheet">
    <link href="{{ asset('includes/DataTables/datatables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('includes/jsTree/themes/default/style.css') }}" rel="stylesheet">
    <link href="{{ asset('includes/LoadPage/loader.css') }}" rel="stylesheet">
    <link href="{{ asset('includes/datetimepicker/datepicker.min.css') }}" rel="stylesheet">
    <link href="{{ asset('includes/DataTables/Buttons-1.5.2/css/buttons.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}?t={{ rand(0,100) }}" rel="stylesheet">
    <link href="{{ asset('css/jquery.fancybox-1.3.4.css') }}" rel="stylesheet">

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
                <a href="#">
                    <img src="{{ URL::asset('/images/logotipo.png') }}" border="0" alt="" />
                </a>
            </div>
        </nav>
    </header>
    <div class="content-nav-header">
    </div>

    <div id="app">