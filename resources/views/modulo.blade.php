@extends('layouts.app-modulo')
@section('title', ' | '.$nome)
@section('module-image', asset($icon))
@section('module-name', $nome)
@section('module-url', $rota)
@section('content')
<div class="content-modulo-home">
    <div class="content-programas">
        @foreach($programas as $programa)
        <div class="content-programa">
            <a href="{{ $programa["route"] }}">
                <img src="{{ asset($programa["icon"]) }}" border="0" alt="" />
                <span>{{ $programa["nome"] }}</span>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endsection
