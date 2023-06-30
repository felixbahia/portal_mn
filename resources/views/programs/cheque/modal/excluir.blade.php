@extends('layouts.page-dialog')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <b>Deseja realmente excluir este lançamento?</b>
    </div>
</div>
<br>
<div class='row border-bottom'>
    <div class='col-sm-6'><b>Cliente</b></div>
    <div class='col-sm-6'>{{ $cliente }}</div>
</div>
<div class='row border-bottom'>
    <div class='col-sm-6'><b>Banco</b></div>
    <div class='col-sm-6'>{{ $banco }}</div>
</div>
<div class='row border-bottom'>
    <div class='col-sm-6'><b>Agência</b></div>
    <div class='col-sm-6'>{{ $agencia }}</div>
</div>
<div class='row border-bottom'>
    <div class='col-sm-6'><b>Número</b></div>
    <div class='col-sm-6'>{{ $numero_cheque }}</div>
</div>
<div class='row border-bottom'>
    <div class='col-sm-6'><b>Valor</b></div>
    <div class='col-sm-6'>{{ $valor }}</div>
</div>

<div class='row border-bottom'>
    <div class='col-sm-6'><b>Bom para</b></div>
    <div class='col-sm-6'>{{ $bom_para }}</div>
</div>

<div class='row border-bottom'>
    <div class='col-sm-6'><b>Status</b></div>
    <div class='col-sm-6'>{{ $status }}</div>
</div>
<div class="content-buttons">
    <button name="excluir" id="btn-excluir" class="btn btn-danger float-right mt-2 mr-2">Excluir</button>
</div>

<script>
    $(document).ready( function(){
        $(document).find("#btn-excluir").on('click', function(){
            $.ajax({
                url: '{{ route('cheque.excluir')}}',
                dataType: 'json',
                data: {
                    '_token': '{{ csrf_token() }}',
                    'id': '{{ $id }}'
                },
                method: 'POST',
                success: function(callback){
                    filterAjax($(document).find("#form_filter").serialize());
                    $(document).find('#excluir-modal').modal('hide');
                },
                error: function(data){
                    console.log(data);
                    message('Erro!', data.responseJSON.message.user);
                }
            });
        });
    });
</script>

@endsection