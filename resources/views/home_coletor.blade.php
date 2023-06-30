@extends('layouts.app-coletor-modulos')
@section('content')
<div class="content-modulos-home container">
    <div class="content-programas">
        @foreach($programas as $programa)
        <div class="content-programa">
            <a href="{{ $programa["route"] }}">
                <span>{{ $programa["nome"] }}</span>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endsection
