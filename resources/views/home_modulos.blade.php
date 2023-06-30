@extends('layouts.app-home')

@section('content')
    <div class="content-modulos-home">
        <div class="content-modulos">
            @foreach($modulos as $modulo)
            <div class="content-modulo">
                <a href="{{ $modulo["route"] }}">
                    <img src="{{ asset($modulo["icon"]) }}" border="0" alt="" />
                    <span>{{ $modulo["nome"] }}</span>
                </a>
            </div>
            @endforeach
            <div class="content-bem-vindo">
                Bem Vindo
            </div>
        </div>
    </div>
    @if (!empty($mensagem))
    <a href="{!! asset($mensagem) !!}" class="fancybox display_none"></a>
    @endif
@endsection
@if (!empty($mensagem))
@section('script-footer')
    $(document).ready( function () {
        $(document).find(".fancybox").fancybox({
            openEffect  : 'none',
            closeEffect : 'none',
        }).click();
    });
@endsection
@endif
