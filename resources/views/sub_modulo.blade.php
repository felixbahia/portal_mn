@extends('layouts.app-modulo')
@section('title', ' | '.$modulo["nome"] .' | '.$submodulo["nome"])
@section('module-image', asset($icon))
@section('module-name', $modulo["nome"])
@section('module-url', $modulo["rota"])
@section('sub-module-name', $submodulo["nome"])
@section('sub-module-url', $submodulo["rota"])
@section('content')
<div class="content-modulo-home">
    <div class="content-programas">
        @foreach($programas as $programa)
        <div class="content-programa">
            <a href="{{ $programa["route"] }}">
                <img src="{{ URL::asset($programa["icon"]) }}" border="0" alt="" />
                <span>{{ $programa["nome"] }}</span>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endsection
