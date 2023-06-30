@extends('layouts.page-dialog')

@section('content')
<form name="form_delete_pilotagem" id="form_delete_pilotagem">
    <div class="row">
        <div class="col-sm">
            <h5>Deseja realmente apagar este registro?</h5>
        </div>
    </div>
    <div class="row registro_pilotagem">
        <div class="col-sm">
            {{ Form::hidden('id', $dados['id'], ['id' => 'id_pilotagem']) }}
            {{ Form::hidden('abonar_pilotagem_alterar_comissao', $dados['abonar_pilotagem_alterar_comissao'], ['id' => 'abonar_pilotagem_alterar_comissao']) }}
            <b>Representante: </b>{{ $dados['vendedor_representante'] }} <br/>
            <b>Nota: </b> {{ $dados['nota'] }} <br/>
            <b>Valor Desconto: </b> <span class="text-danger"> -{{ $dados['valor_desconto'] }} </span><br/>
            <b>Valor Crédito: </b> <span class="text-primary"> {{ $dados['valor_credito'] }} </span><br/>
        </div>
    </div>
    <div class="row registro_comissao">	
        <div class="col-sm">
            {{ Form::hidden('id', $dados['id'], ['id' => 'id_comissao']) }}
            <b>Representante: </b>{{ $dados['vendedor_representante'] }} <br/>
            <b>Nota: </b> {{ $dados['nota'] }} <br/>
            <b>Comissão Atual: </b> {{ $dados['comissao_atual'] }} <br/>
            <b>Comissão Alterada: </b> {{ $dados['comissao_alterada'] }} <br/>
        </div>
    </div>
    <div class="content-buttons float-right mt-3">
        {{ Form::button('Cancelar', ['id' => 'form_delete_cancelar_btn', 'class' => 'btn btn-primary text-right']) }}
        {{ Form::button('Excluir', ['id' => 'form_delete_btn', 'class' => 'btn btn-danger']) }}
    </div>
</form>

<script>
    $(document).ready( function(){
        form_modal = $(document).find("#form_delete_pilotagem");

        form_modal.find(".registro_pilotagem").hide();
        form_modal.find(".registro_comissao").hide();

        if(form_modal.find("#abonar_pilotagem_alterar_comissao").val() == 'abonar_pilotagem'){
            form_modal.find(".registro_pilotagem").show();
            form_modal.find(".registro_comissao").hide();
        }else{
            form_modal.find(".registro_comissao").show();
            form_modal.find(".registro_pilotagem").hide();
        }

        form_modal.find('#form_delete_btn').off('click');
            form_modal.find('#form_delete_btn').on('click', function(event){
                event.stopPropagation();
                deletar(form_modal);
            })
    });

    function deletar(form_modal){
        $.ajax({
            url: "{{ route('liberacao_pilotagem.deletar') }}",
            dataType: 'json',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: form_modal.find("#id_pilotagem").val()
            },
            success: function(){
                $(document).find('#modal_delete_pilotagem_comissao').modal('hide');
                filtro();
            },
            error: function(callback){
                errors = callback.responseJSON.message;

                for(var field in errors){
                    showErrorsInputs('#registro_pilotagem', field, errors[field]);
                }
            }
        });
    }

    $(document).find("#form_delete_cancelar_btn").off("click");
	$(document).find("#form_delete_cancelar_btn").on("click", function(event){
        var $this = $(this);
        $($this).parents(".modal").modal("hide");
	});

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.parent().children().addClass('error-input');
    }

</script>
@endsection
