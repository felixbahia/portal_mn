@extends('layouts.page-dialog')

@section('content')
<form action="#">

    <div class="row">
        <div class="col mb-3">
            <h5>
                Deseja realmente excluir esta requisição?
            </h5>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <p>
                <b>Estabelecimento</b><br>
                {{ $estabelecimentos[$estabelecimento] }}
            </p>
        </div>
        <div class="col-sm-6">
            <p>
                <b>Número da Nota</b><br>
                {!! $nota_fiscal !!}

            </p>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <p>
                <b>Cliente</b><br>
                {{ $cliente }}
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <p>
                <b>Valor</b><br>
                {{ $valor }}
            </p>
        </div>
        <div class="col-sm-6">
            <p>
                <b>Data de emissão</b><br>
                {!! $emissao !!}
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <p>
                <b>Motivo</b><br>
                {!! $motivo !!}
            </p>
        </div>
        @if($valor_parcial === true) 
        <div class="col-sm-6" id='valor-div'>
            <p>
                <b>Valor devolvido</b><br>
                {!! $valor_devolvido !!}
            </p>
        </div>
        @endif
    </div>
    <div class="row">
        <div class="col text-right">
            {!! Form::button('Excluir', ['class' => 'btn btn-danger', 'id' => 'btn-excluir']) !!}
        </div>
    </div>
</form>

<script>
    $(document).ready(function(){
        $(document).find("#btn-excluir").on('click', function(){
            excluirRequisicao('{{ $id }}');
        })
    })

    function excluirRequisicao($id){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            url: '{{ route('devolucao_nota.salvar.excluir') }}',
            success: function(data){
                message('Atenção!', data.message);
                $(document).find('#excluir-devolucao-modal').modal('hide');
                buscarNotas();
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message);
            }
        });
    }
</script>

@endsection