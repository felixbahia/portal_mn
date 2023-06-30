@extends('layouts.page-dialog')

@section('content') 
<div class="container">
    <h4>Deseja realmente excluir essa foto?</h4>
    <div class="row">
        <div class="col-sm-4 mr-2">
            {!! $produto['foto'] !!}
        </div>
        <div class="col-sm-7">
            <div class="row">
                <div class="col-sm"><b>Descrição:</b> {{ $produto['descricao'] }}</div>
                <div class="col-sm"><b>Grupo:</b> {{ $produto['grupo'] }}</div>
            </div>
            <div class="row mt-2">
                <div class="col-sm"><b>Marca:</b> {{ $produto['marca'] }}</div>
                <div class="col-sm"><b>Linha:</b> {{ $produto['linha'] }}</div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm mt-4">
            <form id='excluir-produto-foto' action="#" onsubmit='return false;'>
                @csrf
                {!! Form::hidden('hash', $produto['hash']) !!}
                {!! Form::submit('Excluir', ['class' => 'btn btn-danger float-right']) !!}
            </form>
        </div>
    </div>
</div>

<script>
    
    $(document).ready( function(){
        $(document).find('#excluir-produto-foto').on('submit', function(){
            form = $(document).find('#excluir-produto-foto');
            data = form.serialize();

            $.ajax({
                url: '{{ route('produto_foto.excluir') }}',
                data: data,
                type: 'POST',
            }).done(function (data){
                $(document).find('#excluir-foto-modal').modal('hide');
                filterAjax();
            });
        });
    });

</script>
@endsection