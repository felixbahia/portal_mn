@extends('layouts.page-dialog')

@section('content')
<form action="#" id='form-editar-motivo' onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $id) !!}
    <div class="row">
        <div class="form-group col-sm-12">
            {!! Form::label('descricao_modal', 'Descrição', ['class'=>'input-label']) !!}
            {!! Form::text('descricao', $descricao, ['id' => 'descricao_modal', 'class' => 'form-control']) !!}
        </div>
        <div class="form-check col-sm-12">
            Afeta a premiação?
        </div>
        <div class="form-group col-md-12">
            <div class="form-group  form-check form-check-inline">
                {{ Form::label('afeta_premiacao', 'Sim', ['class'=>'form-check-label']) }}
                {{ Form::radio('afeta_premiacao', 'sim', ($afeta_premiacao === 'sim'), ['id' => 'afeta_premiacao', 'class' => 'form-check-input']) }}
            </div>
            <div class="form-group  form-check form-check-inline">
                {!! Form::label('afeta_premiacao', 'Não', ['class'=>'form-check-label']) !!}
                {!! Form::radio('afeta_premiacao', 'nao', ($afeta_premiacao === 'nao'), ['id' => 'afeta_premiacao', 'class' => 'form-check-input']) !!}
            </div>
        </div>
        <div class="form-check col-sm-12">
            Assinatura no pedido?
        </div>
        <div class="form-group col-md-12">
            <div class="form-group  form-check form-check-inline">
                {{ Form::label('assinatura_pedido', 'Sim', ['class'=>'form-check-label']) }}
                {{ Form::radio('assinatura_pedido', 'sim', ($assinatura_pedido === 'sim'), ['id' => 'assinatura_pedido', 'class' => 'form-check-input']) }}
            </div>
            <div class="form-group  form-check form-check-inline">
                {!! Form::label('assinatura_pedido', 'Não', ['class'=>'form-check-label']) !!}
                {!! Form::radio('assinatura_pedido', 'nao', ($assinatura_pedido === 'nao'), ['id' => 'assinatura_pedido', 'class' => 'form-check-input']) !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm text-right mt-3" id='enviar-div'>
            {!! Form::button('Enviar', ['id' => 'btn_enviar', 'class' => 'btn btn-success']) !!}
        </div>
    </div>
</form>

<script>
    
    $(document).ready(function(){
        $(document).find('#btn_enviar').on('click', function(){
            salvarEdicao();
        });

        $(document).find('#descricao_modal').on('keypress', function(e){
            if(e.which == 13) {
                salvarNovo();
            }
        });
    });

    function salvarEdicao(){

        var form = $(document).find('#form-editar-motivo');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route("devolucao_nota_motivo.salvar.editar") }}',
            dataType: 'json',
            method: 'POST',
            data: form.serialize(),
            success: function(data){
                message('Atenção', 'Motivo salvo com sucesso')
                $(document).find('#modal-editar-motivo').modal('hide');
                buscarMotivos();
            },
            error: function(callback){
                errors = callback.responseJSON.error;

                for(var field in errors){
                    showErrorsInputsModalEditar(form, field, errors[field]);
                }
            }
        })
    }

    function showErrorsInputsModalEditar(form, input, message){
        var $input = form.find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection