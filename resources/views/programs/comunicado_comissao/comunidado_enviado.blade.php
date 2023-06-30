@extends('layouts.app-registrar')

@section('content')
<div class="mx-auto w-50 p-3">
    <div class="content-filter-dialog text-center">	
        <h5>Para voltar ao portal <a href='{{route("login")}}'>clique aqui</a>.</h5>
    </div>
</div>
@endsection

@section('script-footer')  
$(document).ready(function(){
    message('Atenção','Sua Comissão foi confirmada!','success');
});
@endsection