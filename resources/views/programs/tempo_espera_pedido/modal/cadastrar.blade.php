@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_lancamento_tempo_separacao" id="form_lancamento_tempo_separacao" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-4"> 
            {{ Form::label('pedido', 'Pedido') }}
            {{ Form::text('pedido', $id, ['id' => 'pedido', 'class' => 'form-control','disabled' => 'disabled']) }}
        </div>
        <div class="form-group col-sm-8"> 
            {{ Form::label('cliente', 'Cliente') }}
            {{ Form::text('cliente', $cliente, ['id' => 'cliente', 'class' => 'form-control', 'placeholder' => 'Cliente', 'disabled' => 'disabled']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('data_inicio', 'Tempo Inicial') }}
            {{ Form::text('data_inicio', '', ['id' => 'data_inicio', 'class' => 'form-control data', 'placeholder' => 'Data Emissão', 'maxlength' => '16']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('data_fim', 'Tempo Final') }}
            {{ Form::text('data_fim', '', ['id' => 'data_fim', 'class' => 'form-control data', 'placeholder' => 'Data Saída', 'maxlength' => '16']) }}
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
        form_modal_add = $(document).find('#form_lancamento_tempo_separacao');
        initMaskCamposAdd(form_modal_add);

        form_modal_add.find("#btn-salvar").off("click");
        form_modal_add.find("#btn-salvar").on("click", function(){
            inserirDados(form_modal_add);
        });

    });

    function inserirDados(form_modal_add){
        pedido = form_modal_add.find("#pedido").val();
        data_inicio = form_modal_add.find("#data_inicio").val();
        data_fim = form_modal_add.find("#data_fim").val();
        
        limparMesagemErroAdd();
        $.ajax({
            url: "{{ route('tempo_espera_pedido.cadastrar') }}", 
            dataType: 'json',
            data: {
                _token: "{{ csrf_token() }}",
                pedido: pedido,
                data_inicio: data_inicio,
                data_fim: data_fim
            },
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
                    message('Atenção', callback.message);
                    $(document).find('#registrar-tempo-espera').modal('hide');
                    buscarPedidos();
                }else if(callback.status == 'error'){
                    message('Atenção', callback.message);
                }
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
    }

    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_lancamento_tempo_separacao");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_add = $("#form_lancamento_tempo_separacao");
        if(Object.keys(json_error).length > 0){
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

    function initMaskCamposAdd(form_modal_add){
        form_modal_add.find('.data').dateAndTime();
    }

</script>
@endsection