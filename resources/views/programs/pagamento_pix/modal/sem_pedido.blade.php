@extends('layouts.page-dialog')
@section('content')
<form action="#" method="POST" id="cadastro_cria_credito" name="cadastro_cria_credito" onsubmit="return false">
	@csrf
	{!! Form::hidden('id', $id, ['id' => 'id_pix']) !!}
    <div class="content-fields">
        <div class="row">
			<div class="content-tab">

                <div class="col-lg-3 input-group">
                   {{ Form::text('cliente_nome_modal', $clienteDadosNasajon, ['id' => 'cliente_nome_modal', 'class' => 'form-control input-label', 'placeholder' => 'Cliente']) }}
                   <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca-modal" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-clientes" class="bt-view m-2"></i></span>
                </div> 
			    <div class="form-group col-md-4 fisica">
					<span class='campo_obrigatorio'>*</span> 
                    {{ Form::label('observacao', 'Observação') }}
					{!! Form::textarea('observacao', '', ['class' => 'form form-control', 'id' => 'observacao', 'col' => '5', 'rows' => '4', 'maxlength' => '254']) !!}
				</div>
					
			</div>
				
		</div>
	
	</div>
	<div class="content_buttons">
		<div class="form-group col-md-12">			
			{{ Form::submit('Salvar', ['class' => 'btn btn-success float-right', "id"=>"bt_salvar"]) }}
            
		</div>
	</div>
</form>

<script>

    $(document).ready(function(){

        $(document).find('#bt-search-cliente-busca-modal').off('click');
        $(document).find('#bt-search-cliente-busca-modal').on('click', function(event){
            event.stopPropagation();
            showModalClienteBuscaModal($(this).data("route"));
            return false;
        });
   
        $(document).find("#cliente_nome_modal").autocomplete(optionsAutoCompleteClienteModal('cliente_nome_modal'));
               
        $(document).find('#bt_salvar').on('click', function(){ 
            enviaObs();
        });
       
        
    });


    function enviaObs(){        

        var form = $(document).find('#cadastro_cria_credito');
        var data_form = form.serializeArray();

        form.find('.error-input').removeClass('error-input');
        form.find('.error-message').remove(); 


        $.ajax({
            url: "{{ route('pagamento_pix.cria_credito_sem_pedido') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status === "success"){   
                    $('.modal').modal('hide');             
                    message("Atenção", "Credito criado e assossiado ao Pix");
                    filterPagamentoPix();
                    
                } else {
                    message("Atenção", callback.message);
                }
            },
            error: function(data){
                message('Atenção', data.responseJSON.error.msg.user);
            }
        });
        
    }
    
    function optionsAutoCompleteClienteModal($teste){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                $(document).find(".error-message").remove();
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", $("#" + $teste).parents('.modal').css('z-index') + 1);

            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum cliente encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                //$(document).find("#codigo").val(ui.item.value);
                //console.log(ui.item.label);
                $(document).find("#cliente_nome_modal").val(ui.item.label);
                return false;
            }
        };
    }
    
    
    function showModalClienteBuscaModal(url){
        var title = "Busca de Clientes";
        $.ajax({
            url: url,
            method: "GET",
            data: {
                _token: "{{csrf_token()}}"
            },
            success: function(body){
                $(document).find("#cliente_searsh_show").remove();
                createModal("cliente_searsh_show", title, body, "modal-lg");
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on("draw", function () {
                        modal.find("tbody").find("tr").off("click");
                        modal.find("tbody").find("tr").on("click", function(event){
                            returnDadosClienteBuscaModal($(this), event);
                        });
                    });
                });
            }
        });
    }
    
    function returnDadosClienteBuscaModal($dados, event){
        if($dados.find("td").eq(0).hasClass("dataTables_empty")){
            return false;
        }
        $(document).find("#cliente_nome_modal").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide"); 
    }


</script>
@endsection


