@extends('layouts.page-dialog')

@section('content')
<div id="tudo">
    <form id='mudar-data'>

        @csrf
        {!! Form::hidden('id', $pedido['id']) !!}

        <div class="border-bottom" id="info-pedido">
            <div class="row">
                <div class="col-sm-6">
                    <b>Estabelecimento</b>: 
                    {{ $pedido['estabelecimento'] }}
                </div>
                <div class="col-sm-6">
                    <b>Pedido</b>: 
                    {{ $pedido['numero_pedido'] }}
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-sm-5">
                    <b>{!! Form::label('previsao_entrega', 'Previsão de entrega', ['class' => 'bold']) !!}</b>
                    {!! Form::text('previsao_entrega', $pedido['previsao_entrega'], ['id' => 'previsao_entrega_modal', 'class' => 'form-control']) !!}
                </div>
            </div>

            <div class="row my-3">
                <div class="col-sm-12 text-right">
                    <button type='submit'class="btn btn-success" id="salvar">Salvar</button>	        
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    $(document).ready( function() {

        $(document).find('#previsao_entrega_modal').mask('00/00/0000');
        $(document).find('#previsao_entrega_modal').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            zIndex: $(document).find("#alterar-data-modal").css("z-index") + 1,
            autoHide: true
        });

        $(document).find('#mudar-data').on('submit', function(){
            event.preventDefault();
            event.stopPropagation();

            var dados = $(document).find('#mudar-data').serialize();

            $.ajax({
                url: '{{ route('pedidos_compras.pedidos_abertos.alterar') }}',
                method: 'POST',
                data: dados,
                success: function(){
                    $(document).find('#alterar-data-modal').modal('hide');
                    filterAjax();
                },
                error: function(data){
                    console.log(data);
                    message('Erro', data.responseJSON.error.usr);
                }

            });
        });
    });
</script>
@endsection