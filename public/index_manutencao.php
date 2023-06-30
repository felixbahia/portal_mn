<?php
http_response_code(503);
?><!DOCTYPE html>
<html lang="pt-BR" class="page-sem-header">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>MN Tecidos </title>

    <!-- Styles -->
    <link href="/css/app.css" rel="stylesheet">
    <link href="/css/style.css?t=46" rel="stylesheet">


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
	<div style="color: #FFFFFF;font-size: 40px;text-align: center;display: block;position: absolute !important;top: 50%;left: 50%;transform: translateX(-50%) translateY(-50%);">Sistema em manuntenção,<br /> previsão de retorno 14/05 às 10h.</div>
</body>
</html>
