@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_estorno" id="form_estorno" onsubmit="return false;">
    @csrf
    <div class="form-row">
        @if($total != true)
            <div class="form-group col-sm-12">
                {!! Form::label('estorno_parcial_label', 'Estorno Parcial?', ['class' => 'form-check-label']) !!}
                <div class="form-check">
                        {{ Form::radio('estorno_parcial', 'sim', '', ['class' => 'form-check-input', 'id'=>'estorno_parcial_resposta_sim']) }}
                        {{ Form::label('estorno_parcial_resposta_sim', 'Sim', ['class'=>'form-check-label']) }}
                    
                </div>
                <div class="form-check">
                    {{ Form::radio('estorno_parcial', 'nao', '', ['class' => 'form-check-input', 'id'=>'estorno_parcial_resposta_nao', 'checked']) }}
                    {{ Form::label('estorno_parcial_resposta_nao', 'Não', ['class'=>'form-check-label']) }}
                </div>
            </div>
        @endif
        <div class="form-group col-sm-2 d-none valor_estorno"> 
            {{ Form::label('valor_estorno', 'Valor à Estornar', []) }}
            {{ Form::text('valor_estorno', '', ['id' => 'valor_estorno', 'class' => 'form-control text-right', 'placeholder' => 'Digite um valor menor que o valor pago.', 'maxlength' => '13']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-6"> 
            {{ Form::label('pedido_portal', 'Pedido Portal', []) }}
            {{ Form::text('pedido_portal', $dados['pedido_portal'], ['id' => 'pedido_portal', 'class' => 'form-control',  'readonly' => 'true']) }}
            {{ Form::hidden('cielo_id', $dados['id_cielo'], ['id'=>'cielo_id', 'class' => 'form-control text']) }}
       </div>
       <div class="form-group col-sm-6">
           {{ Form::label('valor_pago', 'Valor a Estornar') }}
           {{ Form::text('valor_pago', (isset($dados['valor_pago'])) ? $dados['valor_pago'] : '', ['id' => 'valor_pago', 'class' => 'form-control', 'placeholder' => 'Número', 'maxlength' => '20', 'readonly' => 'true']) }}
       </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('cliente', 'Cliente') }}
            {{ Form::text('cliente', (isset($dados['cliente'])) ? $dados['cliente'] : '', ['id'=>'cliente', 'class' => 'form-control text', 'readonly' => 'true', 'placeholder' => 'Cliente']) }}
         </div>
    </div>
    <div class="col-sm-12 mt-5" >
        <div class="form-group">
            {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
        </div>
    </div>
</form>

<script>

    $(document).ready( function () {
        $(document).find("#valor_estorno").maskMoney({thousands:'.', decimal:','});
      
        form_modal_add = $(document).find('#form_estorno');

        form_modal_add.find("#btn-salvar").off("click");
        form_modal_add.find("#btn-salvar").on("click", function(){
            salvarEstornoRequest();
        });

        $(document).find("#estorno_parcial_resposta_sim").off("click");
        $(document).find("#estorno_parcial_resposta_sim").on("click", function(event){
            event.stopPropagation();
            $(document).find(".valor_estorno").removeClass("d-none");
        });

        $(document).find("#estorno_parcial_resposta_nao").off("click");
        $(document).find("#estorno_parcial_resposta_nao").on("click", function(event){
            event.stopPropagation();
            limparMesagemErroAdd();
            $(document).find('input[name=valor_estorno').val('');
            $(document).find(".valor_estorno").addClass("d-none");
        });
    });

    function salvarEstornoRequest(){
        form = $("#form_estorno").serialize()
        
        $.ajax({
            url: "{{ route('acompanhamento_cielo.estorno') }}", 
            method: 'POST',
            type: 'POST',
            dataType: 'json',
            data: form,
            success: function(dados){
                if(dados.status == 'success'){
                    message("Atenção", dados.message);
                    $(document).find("#view-estorno").modal('hide');
                    $(document).find("#acompanhamento_credito_sem_nota").modal('hide');
                    $(document).find("#view-estorno-lista").modal('hide');
                    $(document).find("#view-estorno-restante").modal('hide');
                }else{
                    message("Atenção", dados.error);
                }
            },
            error: function(callback){
                var dados = callback.responseJSON;
                if(dados.status == 'error_request'){
                    message("Atenção", dados.message);
                    $(document).find("#view-estorno").modal('hide');
                    $(document).find("#acompanhamento_credito_sem_nota").modal('hide');
                    $(document).find("#view-estorno-lista").modal('hide');
                    $(document).find("#view-estorno-restante").modal('hide');
                    return;
                }
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
    }

    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_estorno");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_add = $("#form_estorno");
        if(json_error !== undefined){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_add, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form_modal_add, input, message){
        var $input = $(form_modal_add).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }


</script>
@endsection