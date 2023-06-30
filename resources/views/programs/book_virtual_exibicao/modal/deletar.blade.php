@extends('layouts.page-dialog')

@section('content')
<form name="form_delete_carrinho" id="form_delete_carrinho">
    <div class="content" id="registro_item">
        <div class="row">
            <div class="col-sm">
                <h5>Deseja realmente apagar este registro?</h5>
            </div>
        </div>
        <div class="row">
            <div class="col-sm">
                {{ Form::hidden('pedido', $pedido['pedido'], ['id' => 'pedido']) }}
                {{ Form::hidden('id', $item['id'], ['id' => 'id']) }}
                {{ Form::hidden('deletar_item', $item['deletar_item'], ['id' => 'deletar_item']) }}
                <b>Código: </b>{{ $item['codigo'] }} <br/>
                <b>Descrição: </b> {{ $item['descricao'] }} <br/>
            </div>
        </div>
    </div>
    <div class="content" id="registro_pedido">
        <div class="row">
            <div class="col-sm">
                <h5>Deseja realmente cancelar este pedido?</h5>
            </div>
        </div>
        <div class="row">	
            <div class="col-sm">
                {{ Form::hidden('cancelar_pedido', $pedido['cancelar_pedido'], ['id' => 'cancelar_pedido']) }}
                {{ Form::hidden('pedido', $pedido['pedido'], ['id' => 'pedido']) }}
                <b>Pedido: </b> {{ $pedido['pedido'] }}<br/>
                <b>Cliente: </b> {{ $pedido['cliente'] }}<br/>
            </div>
        </div>
    </div>
    <div class="content" id="registro_carrinho">
        <div class="row">
            <div class="col-sm">
                <h5>Deseja realmente excluir este carrinho?</h5>
            </div>
        </div>
        <div class="row">	
            <div class="col-sm">
                {{ Form::hidden('excluir_carrinho', $carrinho['excluir_carrinho'], ['id' => 'excluir_carrinho']) }}
                {{ Form::hidden('pedido', $pedido['pedido'], ['id' => 'pedido']) }}
                <b>Cliente: </b> {{ $carrinho['cliente'] }}<br/>
            </div>
        </div>
    </div>
    <div class="content-buttons float-right mt-3">
        {{ Form::button('Cancelar', ['id' => 'form_delete_cancelar_btn', 'class' => 'btn btn-primary text-right']) }}
        {{ Form::button('Excluir', ['id' => 'form_delete_btn', 'class' => 'btn btn-danger']) }}
    </div>
</form>

<script>
    $(document).ready( function(){
        form_modal_deletar = $(document).find("#form_delete_carrinho");

        form_modal_deletar.find("#registro_item").hide();
        form_modal_deletar.find("#registro_pedido").hide();
        form_modal_deletar.find("#registro_carrinho").hide();

        if(form_modal_deletar.find("#deletar_item").val() == true){
            form_modal_deletar.find("#registro_item").show();
            form_modal_deletar.find("#registro_pedido").hide();
            form_modal_deletar.find("#registro_carrinho").hide();
        }
        else if(form_modal_deletar.find("#cancelar_pedido").val() == true){
            form_modal_deletar.find("#registro_pedido").show();
            form_modal_deletar.find("#registro_item").hide();
            form_modal_deletar.find("#registro_carrinho").hide();
        }
        else if(form_modal_deletar.find("#excluir_carrinho").val() == true){
            form_modal_deletar.find("#registro_carrinho").show();
            form_modal_deletar.find("#registro_item").hide();
            form_modal_deletar.find("#registro_pedido").hide();
        }

        form_modal_deletar.find('#form_delete_btn').off('click');
        form_modal_deletar.find('#form_delete_btn').on('click', function(event){
            if(form_modal_deletar.find("#deletar_item").val() == true){
                event.stopPropagation();
                deletarProduto(form_modal_deletar);
            }
            else if(form_modal_deletar.find("#cancelar_pedido").val() == true){
                event.stopPropagation();
                cancelarPedido(form_modal_deletar);
            }
            else if(form_modal_deletar.find("#excluir_carrinho").val() == true){
                event.stopPropagation();
                excluirCarrinho(form_modal_deletar);
            }
        })
    });

    function deletarProduto(form_modal_deletar){
        var $id = form_modal_deletar.find("#id").val();

        $.ajax({
            url: "{{ route('book_virtual_exibicao.deletar_item') }}",
            dataType: 'json',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            success: function(callback){
                $(document).find('#modal_deletar_carrinho').modal('hide');
                filtro(callback.response.pedido, callback.response.valor); 
                dados($dados, -1);
            },
            error: function(callback){
                errors = callback.responseJSON.message;
                message("Atenção", callback.responseJSON.message)
            }
        });
    }

    function cancelarPedido(form_modal_deletar){
        var $pedido_id = form_modal_deletar.find("#pedido").val();

        $.ajax({
            url: "{{ route('book_virtual_exibicao.cancelar_pedido') }}",
            dataType: 'json',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $pedido_id
            },
            success: function(data){
                $(document).find('#modal_deletar_carrinho').modal('hide');
                filtro($pedido_id);
                if(data.response.pedidos_carrinho == 1){
                    window.location.reload();
                }else{
                    dados($dados, -data.response.contador);
                }
            },
            error: function(callback){
                errors = callback.responseJSON.message;
                message("Atenção", callback.responseJSON.message)
            }
        });
    }

    function excluirCarrinho(form_modal_deletar){
        $.ajax({
            url: "{{ route('book_virtual_exibicao.excluir_carrinho') }}",
            dataType: 'json',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
            },
            success: function(body){
                $(document).find('.modal').modal('hide');
                window.location.reload();
                message("Atenção", 'Carrinho excluído com sucesso!');
            },
            error: function(callback){
                errors = callback.responseJSON.message;
                message("Atenção", callback.responseJSON.message)
            }
        });
    }

    $(document).find("#form_delete_cancelar_btn").off("click");
	$(document).find("#form_delete_cancelar_btn").on("click", function(event){
        var $this = $(this);
        $($this).parents(".modal").modal("hide");
	});

</script>
@endsection
