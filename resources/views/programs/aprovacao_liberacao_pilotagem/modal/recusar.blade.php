@extends('layouts.page-dialog')

@section('content')
<form name="form_recusa_pilotagem" id="form_recusa_pilotagem">
    <div class="row">
        <div class="col-sm">
            <h5>Deseja realmente recusar esta Pilotagem?</h5>
        </div>
    </div>
    <div class="row registro_pilotagem">
        <div class="col-sm">
            {{ Form::hidden('id', $dados['id'], ['id' => 'id']) }}
            {{ Form::hidden('abonar_pilotagem_alterar_comissao', $dados['abonar_pilotagem_alterar_comissao'], ['id' => 'abonar_pilotagem_alterar_comissao']) }}
            <b>Representante: </b>{{ $dados['vendedor_representante'] }} <br/>
            <b>Nota: </b> {{ $dados['nota'] }} <br/>
            <b>Valor Desconto: </b> <span class="text-danger"> -{{ $dados['valor_desconto'] }} </span><br/>
            <b>Valor Crédito: </b> <span class="text-primary"> {{ $dados['valor_credito'] }} </span><br/>
        </div>
    </div>
    <div class="row registro_comissao">	
        <div class="col-sm">
            <b>Representante: </b>{{ $dados['vendedor_representante'] }} <br/>
            <b>Nota: </b> {{ $dados['nota'] }} <br/>
            <b>Comissão Atual: </b> {{ $dados['comissao_atual'] }} <br/>
            <b>Comissão Alterada: </b> {{ $dados['comissao_alterada'] }} <br/>
        </div>
    </div>
    <br/>
    <div class="form-group">
        {!! Form::label('motivo_recusa', 'Justificativa', []) !!}
        {!! Form::text('motivo_recusa', '', ['id' => 'motivo_recusa', 'class' => 'form-control', 'maxlength' => '250', 'placeholder' => 'Motivo da Recusa']) !!}
    </div>
    <div class="content-buttons float-right mt-3">
        {{ Form::button('Cancelar', ['id' => 'form_recusa_cancelar_btn', 'class' => 'btn btn-primary text-right']) }}
        {{ Form::button('Recusar', ['id' => 'form_recusa_btn', 'class' => 'btn btn-danger']) }}
    </div>
</form>

<script>
    $(document).ready( function(){
        form_modal = $(document).find("#form_recusa_pilotagem");

        form_modal.find(".registro_pilotagem").hide();
        form_modal.find(".registro_comissao").hide();

        if(form_modal.find("#abonar_pilotagem_alterar_comissao").val() == 'abonar_pilotagem'){
            form_modal.find(".registro_pilotagem").show();
            form_modal.find(".registro_comissao").hide();
        }else{
            form_modal.find(".registro_comissao").show();
            form_modal.find(".registro_pilotagem").hide();
        }

        form_modal.find('#form_recusa_btn').off('click');
            form_modal.find('#form_recusa_btn').on('click', function(event){
                event.stopPropagation();
                recusar(form_modal);
            })
    });

    function recusar(form_modal){
        $.ajax({
            url: "{{ route('liberacao_pilotagem.reprovar') }}",
            dataType: 'json',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: form_modal.find("#id").val(),
                motivo_recusa: form_modal.find("#motivo_recusa").val()
            },
            success: function(){
                $(document).find('#modal_recusa_aprovacao_pilotagem').modal('hide');
                filtro();
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroModal();
                mensagemErroModal(dados);
            }
        });
    }

    $(document).find("#form_recusa_cancelar_btn").off("click");
	$(document).find("#form_recusa_cancelar_btn").on("click", function(event){
        var $this = $(this);
        $($this).parents(".modal").modal("hide");
	});

    function limparMesagemErroModal(){      
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span, button').removeClass('error-input');
    }

    function mensagemErroModal(json_error){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModal(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form_modal, input, message){
        var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"'], div[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }


</script>
@endsection
