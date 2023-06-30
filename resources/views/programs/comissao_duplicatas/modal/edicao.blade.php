@extends('layouts.page-dialog')

@section('content')

<form action="#" id="editar_comissao_duplicata_form" method="post" name="editar_comissao_duplicata_form" onsubmit="return false">
    @csrf
    {!! Form::hidden('id', $id) !!}
    {!! Form::hidden('vendedor_original', $vendedor) !!}
    <div class="row">
        <div class="col-sm-12">
            <b>Vendedor</b><br />
            @if(Auth::user()->tipo_usuario->nome == 'Administrador' || in_array(Auth::id(), [46, 26, 105]))
                {{ Form::select('vendedor', $vendedores_array, $vendedor, ['id' => 'vendedor', 'class' => 'form-control'])}}
            @else
            {{ $vendedores_array[$vendedor] }}
            @endif
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            {{ Form::label('comissao', 'Comissão') }}
            @if(Auth::user()->tipo_usuario->nome == 'Administrador' || in_array(Auth::id(), [46, 26, 105]))
            <div class="input-group">
                {{ Form::text('comissao', $comissao, ['id' => 'comissao', 'class' => 'form-control'])}}
                <div class="input-group-append">
                    <div class="input-group-text">%</div>
                </div>
            </div>
            @else
            {{ $comissao }}
            @endif
        </div>
    </div>
    <div class="row float-right my-2">
        <div class="col-lg-12">
            <button class='btn btn-modifica float-left' id='salvar_comissao_duplicata'>Modificar informações de comissão</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function(){
        $(document).find('#comissao').maskMoney({thousands:'', decimal:',', allowZero: true});

        $(document).find('#salvar_comissao_duplicata').on('click', function(){
            aplicar();
        });
    });

    @if(Auth::user()->tipo_usuario->nome == 'Administrador' || in_array(Auth::id(), [46, 26, 105]))
    
    function aplicar(){

        $data = $(document).find('#editar_comissao_duplicata_form').serialize();
        $.ajax({
            url: '{{ route('comissao_duplicatas.duplicata.salvar_comissao_duplicata') }}',
            data: $data,
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
@endsection