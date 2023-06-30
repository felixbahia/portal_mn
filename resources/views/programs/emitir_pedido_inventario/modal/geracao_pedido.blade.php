@extends('layouts.page-dialog')

@section('content')
<form action="#" id='form_gerar_pedido_pelo_inventario' onsubmit="return false;">
    @csrf
    {!! Form::hidden('criterios', $criterios) !!}
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            <div class="content-dialog-table">
                <div class="content-table">
                    <table class="table table-striped" id="table-unidade_negocio-membros">
                        <thead>
                            <th style="width: 150px;">Cód.</th>
                            <th>Prod.</th>
                            <th class="tb_number">Qtd.</th>
                            <th class="tb_number">Custo</th>
                        </thead>
                        <tbody>
                            @foreach ($produtos_inventario as $produto)
                                <tr>
                                    <td>{{ $produto['produto_codigo'] }}</td>
                                    <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $produto['produto']['especificacao'] }}'>{{ $produto['produto']['especificacao'] }}</div></div></td>
                                    <td class="tb_number_150">{{ $produto['quantidade'] }}</td>
                                    <td class="tb_number_150">{{ $produto['preco'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-3">
        </div>
        <div class="form-group col-md-6">
            <div class="form-group  form-check form-check-inline">
                {{ Form::radio('remessa_retorno', 'remessa', true, ['id' => 'remessa', 'class' => 'form-check-input']) }}
                <h5 style="margin-top: 10px;">{{ Form::label('remessa', 'Remessa', ['class'=>'form-check-label']) }}</h5>
            </div>
            <div class="form-group  form-check form-check-inline">          
                {!! Form::radio('remessa_retorno', 'Retorno', false, ['id' => 'retorno', 'class' => 'form-check-input']) !!}
                <h5 style="margin-top: 10px;">{!! Form::label('retorno', 'Retorno', []) !!}</h5>
            </div>
        </div>
        <div class="form-group col-md-3">
        </div>
    </div>
    <div class="row">
        <div class="col-sm text-right mt-3" id='enviar-div'>
            {!! Form::button('Gerar Pedido', ['id' => 'btn_enviar', 'class' => 'btn btn-success']) !!}
        </div>
    </div>
</form>

<script>
    
    $(document).ready(function(){
        $(document).find('#btn_enviar').on('click', function(){
            salvarEdicao();
        });
    });

    function salvarEdicao(){

        var form = $(document).find('#form_gerar_pedido_pelo_inventario');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route("emitir_pedido_inventario.gerar_pedido") }}',
            dataType: 'json',
            method: 'POST',
            data: form.serialize(),
            success: function(data){
                message('Atenção', 'Pedido Gerado com Sucesso.')
                $(document).find('#modal_geracao_pedidos').modal('hide');
                buscaDados($("#form_filter").serialize());
            },
            error: function(callback){
                reponse_json = callback.responseJSON;
                if(reponse_json.status == 'erro'){
                    message('Atenção', reponse_json.message);
                }else{
                    message('Atenção', 'Ocorreu um erro ao gerar o pedido, consulte o setor responsável.');
                }
            }
        })
    }

</script>
@endsection