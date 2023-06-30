@extends('layouts.page-dialog')

@section('content')
@if(Auth::user()->tipo_usuario->nome == 'Administrador' || Auth::user()->id == 92 )
<div class="row">
    <div class="col-sm-12">
        <b>Vendedor</b><br />
        @if(Auth::user()->tipo_usuario->nome == 'Administrador' || Auth::user()->id == 92 )
            {{ Form::select('vendedor', $info_pedido['vendedores_array'], $info_pedido['vendedor'], ['id' => 'vendedor', 'class' => 'form-control'])}}
        @else
        {{ $info_pedido['vendedores_array'][$info_pedido['vendedor']] }}
        @endif
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <b>Comissão do Pedido</b><br />
        Comissão na nota: {{ $info_pedido['comissao_pedido']??0 }}% <br />
        Comissões dos itens/Total do pedido
        @if(Auth::user()->tipo_usuario->nome == 'Administrador' || Auth::user()->id == 92 )
            <div class="input-group">
                {{ Form::text('comissao', $info_pedido['comissao_pedido'], ['id' => 'comissao', 'class' => 'form-control'])}}
                <div class="input-group-append">
                    <div class="input-group-text">%</div>
                </div>
            </div>
        @else
            : {{ $info_pedido['comissao_pedido'] }}%
        @endif
    </div>
</div>
<div class="row float-right my-2">
    <div class="col-lg-12">
        <button class='btn btn-modifica float-left' id="aplicar" onclick="aplicar('{{ $info_pedido['hash'] }}')">Modificar informações de comissão</button>
    </div>
</div>

<script>
    $(document).ready(function(){
        $(document).find('#comissao').maskMoney({thousands:'', decimal:','});
    });

    @if(Auth::user()->tipo_usuario->nome == 'Administrador' || Auth::user()->id == 92 )
    function aplicar($hash){
        $.ajax({
            url: '{{ route('revisao_comissao.nasajon.aplicar') }}',
            data: {
                _token: '{{ csrf_token() }}',
                hash: $hash,
                comissao: $(document).find("#comissao").val(),
                vendedor: $(document).find("#vendedor").val(),
            },
            method: 'POST',
            success: function(data){
                $('#pedido_modal_comissoes').modal('hide');
                message('Sucesso', 'Comissão aplicada com sucesso.<br />');
            },
            error: function (callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    message('Erro', 'Erro ao atualizar.<br />');
                }
            }
        });
    }
    @endif
</script>
@endif
@endsection