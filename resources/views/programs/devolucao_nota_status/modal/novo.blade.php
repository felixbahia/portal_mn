@extends('layouts.page-dialog')

@section('content')
<form action="#" name='form-novo-status' id='form-novo-status' onsubmit="return false;">
    @csrf
    <div class="row">
        <div class="col">
            {!! Form::label('descricao_modal', 'Status') !!}
            {!! Form::text('descricao', '', ['id' => 'descricao_modal', 'class' => 'form form-control']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col text-right">
            {!! Form::checkbox('selecionavel', true, false, ['id' => 'selecionavel_modal' ]) !!}
            {!! Form::label('selecionavel_modal', 'Selecionável como etapa') !!}
        </div>
    </div>
    <div class="row mt-3">
        <div class="col">
            {!! Form::button('Salvar', ['id' => 'btn-salvar-novo-modal', 'class' => 'btn btn-success float-right']) !!}
        </div>
    </div>
</form>

<script>
    $(document).ready(function(){

        $(document).find('#btn-salvar-novo-modal').on('click', function(){
            salvarStatus();
        });

        $(document).find('#status_modal').on('keypress', function(e){
            if(e.which == 13) {
                salvarStatus();
            }
        });

        setTimeout(function(){
            $(document).find('#status_modal').focus();
        }, 300);
    });

    function salvarStatus(){
        form = $(document).find('#form-novo-status');

        $.ajax({
            url: '{{ route("devolucao_nota_status.salvar.novo") }}',
            method: 'POST',
            data: form.serialize(),
            success: function(data){
                $(document).find('#modal-novo-status').modal('hide');
                buscarStatus();
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)  
            }
        });
    }
</script>
@endsection