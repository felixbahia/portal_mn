@extends('layouts.app-lista-preco')
@section('content')
<div class="content-book-virtual">
    <div class="title-book-virtual">Book Virtual</div>
    @foreach ($files as $file)
        <div><a href="{{ $file['url'] }}" target="_blank">{{ $file['nome'] }}</a></div>
    @endforeach
</div>
@endsection
