@extends('layouts.page-dialog')

@section('content')

<form action="" onsubmit="return false" name="form_alteracao_data" id="form_alteracao_data">

	@csrf
    {!! Form::hidden('id_projeto', $id_projeto, ['id' => 'id_projeto']) !!}
    {!! Form::hidden('codigo_status_atual', $codigo_status_atual, ['codigo_status_atual' => 'id_projeto']) !!}
	<div class="row">
		<div class="form-group col-sm-8"> 
			{{ Form::label('status_atual', 'Status Atual', []) }}
        	{{ Form::text('status_atual', $status_atual, ['id' => 'status_atual', 'class' => 'form-control', 'readonly']) }}
		</div>
    </div>
    <div class="row">
		<div class="form-group col-sm-8"> 
			{{ Form::label('status_novo', 'Status Novo', []) }}
        	{{ Form::select('status_novo', $status, '', ['id' => 'status_novo', 'class' => 'form-control', 'placeholder' => 'Selecione o Novo Status']) }}
		</div>
    </div>

	<div class="row">
		<div class="col-xl-12 text-right">
            {{ Form::button('Salvar', ['class' => 'btn btn-primary float-right', 'id' => 'btn-salvar']) }}
		</div>
	</div>

</form>
<script>
	$(document).ready( function () {
        form_modal = $(document).find('#form_alteracao_data');
        form_modal.find("#btn-salvar").on('click', function(){
            alterarDados(form_modal);
        });
	});
	
	function alterarDados(form_modal){
        limparMesagemErro(form_modal);
        data_form_modal = form_modal.serialize();
        if(form_modal.find('#status_novo').val() == 99){
            $.ajax({
                url: '{{ Route("lancamento_projeto.carregar_documentos") }}',
                type: 'POST',
                data: data_form_modal,
                success: function(data) {
                    var mensagem = "";
                    var pedidos_remessas = "";
                    for (var index in data.response.pedidos_remessas.pedidos){
                        if(data.response.pedidos_remessas.pedidos[index].situacao !== "Aberto"){
                            if(pedidos_remessas === ""){
                                pedidos_remessas = data.response.pedidos_remessas.pedidos[index].numero_pedido;
                            }else{
                                pedidos_remessas = pedidos_remessas+", "+data.response.pedidos_remessas.pedidos[index].numero_pedido;
                            }
                        }
                    }
                    if(pedidos_remessas !== ""){
                        mensagem = mensagem+"Pedido de Remessa: "+pedidos_remessas+"<br>";
                    }

                    var pedidos_transferencia = "";
                    for (var index in data.response.pedidos_transferencia.pedidos){
                        if(data.response.pedidos_transferencia.pedidos[index].situacao !== "Aberto"){
                            if(pedidos_transferencia === ""){
                                pedidos_transferencia = data.response.pedidos_transferencia.pedidos[index].numero_pedido;
                            }else{
                                pedidos_transferencia = pedidos_transferencia+", "+data.response.pedidos_transferencia.pedidos[index].numero_pedido;
                            }
                        }
                    }
                    if(pedidos_transferencia !== ""){
                        mensagem = mensagem+"Pedido de Transferência: "+pedidos_transferencia+"<br>";
                    }

                    var pedidos_compras = "";
                    for (var index in data.response.pedidos_compras.pedidos){
                        if(data.response.pedidos_compras.pedidos[index].situacao !== "Aberto"){
                            if(pedidos_compras === ""){
                                pedidos_compras = data.response.pedidos_compras.pedidos[index].numero_pedido;
                            }else{
                                pedidos_compras = pedidos_compras+", "+data.response.pedidos_compras.pedidos[index].numero_pedido;
                            }
                        }
                    }
                    if(pedidos_compras !== ""){
                        mensagem = mensagem+"Pedido de Compras: "+pedidos_compras+"<br>";
                    }
                    
                    if(mensagem !== ""){
                        mensagem = "Os pedidos, abaixo, terão que ser cancelado manualmente.<br>"+mensagem+"Deseja cancelar o projeto?<br><br><br>"
                        var name_option_ok = "ok_alteracao_status";
                        var $class = "dialog_option_deletar";
                        message_option("Atenção!", mensagem, $class, name_option_ok, '', '', '');

                        $(document).off("ok_alteracao_status");
                        $(document).on("ok_alteracao_status", function(){
                            alteracaoStatus(form_modal);
                        });
                    }else{
                        alteracaoStatus(form_modal);
                    }
                }
            });
        }else{
            alteracaoStatus(form_modal);
        }
    }
    
    function alteracaoStatus(form_modal){
        data_form_modal = form_modal.serialize();
        $.ajax({
            url: "{{ route('lancamento_projeto.alterar_status')}}", 
            dataType: 'json',
            data: data_form_modal,
            method: 'POST',
            async: false,
            success: function(data){
                $(form_modal).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            complete: function(){
                loader();
            },
            error: function(data){
                if(data.responseJSON.message != ''){
                    message("Atenção", data.responseJSON.message);
                }
                var errors = data.responseJSON.error;
                for(var field in errors){
                    showErrorsInputs(form_modal, field, errors[field])
                }
            }
        });
    }

</script>
@endsection