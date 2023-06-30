@extends('layouts.page-dialog')
@section('content')
<form action="#" method="post" id="editCondicao" name="editCondicao" class="editCondicao" onsubmit="return false">
    @csrf
	<div class="form-row condicao_nasajon">
		<div class="form-group col-sm-6">
			{{ Form::label('nasajon_forma_pagamento', 'Forma de Pagamento', []) }}
			{{ Form::select('nasajon_forma_pagamento', [null=>'Selecione uma forma de pagamento'] + $forma_pagamento, $condicao->nasajon_forma_pagamento, ['class' => 'form-control']) }}
		</div>
		<div class="form-group col-sm-6">
			{{ Form::label('nasajon_parcelas_label', 'Parcelas', '') }}
			{{ Form::hidden('nasajon_parcelas', $parcela, ['class' => 'form-control', 'id' => 'nasajon_parcelas']) }}
			{{ Form::text('nasajon_parcelas_label', $parcela_label, ['id' => 'nasajon_parcelas_label', 'class' => 'form-control', 'placeholder' => 'Parcela', "maxlength" => "250"]) }}
		</div>
	</div>
	<div class="form-row condicao_prologos">
		<div class="form-group col-sm-12">
			{{ Form::label('id_web', 'Condição', []) }}
			{{ Form::select('id_web', [null=>'Selecione uma condição'] + $condicoes, $condicao->id_web, array('class' => 'form-control', 'id' => 'id_web')) }}
		</div>
	</div>
	<div class="form-row">
		{{ Form::hidden('id', $condicao->id, array('class' => 'form-control', 'id' => 'id')) }}
		<div class="form-group col-sm-12">
			{{ Form::label('descricao', 'Descrição', []) }}
			{{ Form::text('descricao', $condicao->descricao, array('class' => 'form-control', 'id' => 'descricao_modal')) }}
		</div>
	</div>
	<div class="form-row">
		<div class="form-group col-sm-12">
			{{ Form::checkbox('ativo', true, $condicao->ativo, ['id' => 'ativo', 'class' => 'ativo']) }}
			{{ Form::label('ativo', 'Ativo')}}
			<br>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12"><b>Vencimentos:</b> <span id="dias"></span></div>
	</div>
	<div class="form-row">
		<div class="form-row align-items-center">
			<div class="col-sm-12">
					{{ Form::checkbox('liberado_representante', true, $condicao->liberado_representante, ['class' => 'liberado_representante', 'id' => 'liberado_representante']) }}
					{{ Form::label('liberado_representante', 'Liberado para representantes?')}}
			</div>
		</div>
	</div>
	<div class="form-row">
		<div class="form-row align-items-center">
			<div class="col-sm-12">
					{{ Form::checkbox('liberado_clientes', true, $condicao->clientes->isEmpty()?true:false, ['class' => 'liberado_clientes', 'id' => 'liberado_clientes']) }}
					{{ Form::label('liberado_clientes', 'Liberado para todos os clientes?')}}
			</div>
		</div>
	</div>
	<div class="form-row add-clientes">
        <div class="form-group col-sm-12">
			{{ Form::label('cod_cliente', 'Cliente') }}
	        
	        <div class="input-group" id="cod_cliente_group">
	        	{{ Form::text('cod_cliente', '', ['id' => 'codigo', 'class' => 'form-control col-sm-3']) }}
	        	{{ Form::text('nome', '', ['id' => 'nome', 'class' => 'form-control', 'disabled' => 'disabled']) }}
	        	<span class="input-group-addon border" id="bt-search" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
				<div class="input-group-append" id="success_button_cliente">
					<button class="input-group-addon btn btn-success" id="add-clientes-button">+</button>
				</div>
			</div>
    	</div>
	</div>
	<div class="row add-clientes">
		<div class="col-sm-12 table-container">
			<table class="table table-striped content-dialog-table" id="table-filters-clientes">
				<thead>
					<tr>
						<th>Código</th>
						<th>Cliente</th>
						<th>Excluir</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($condicao->clientes as $cliente)
					<tr>
						<td>{{ $cliente->cliente }}</td>
						<td><div><div data-toggle="tooltip" data-html="true" title="{{ $cliente->clienteNasajon->nome }}">{{ $cliente->clienteNasajon->nome }}</div></div></td>
						<td><a href='#' class="bt-delete" data-toggle="popover" data-trigger='hover' title="Apagar" onclick="deletarLinha(this)"></a></td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>
	<div>
		<button class="btn btn-success" id="salvar">Salvar</button>	
	</div>
</form>

<script>
	var form_modal_add = $(document).find('#editCondicao');
	form_modal_add.find("#nasajon_parcelas").autocomplete(optionAutoCompleteParcelas(form_modal_add));

	@if($condicao->nasajon === false)
	$(document).find("#editCondicao").find(".condicao_nasajon").hide();
	@else
	$(document).find("#editCondicao").find(".condicao_prologos").hide();
	@endif
	$(document).find("#editCondicao").find(".nasajon").off('change');
	$(document).find("#editCondicao").find(".nasajon").on('change', function(){
		if(this.value === 'sim'){
			$(document).find("#editCondicao").find(".condicao_nasajon").show();
			$(document).find("#editCondicao").find(".condicao_prologos").hide();
		}else{
			$(document).find("#editCondicao").find(".condicao_nasajon").hide();
			$(document).find("#editCondicao").find(".condicao_prologos").show();
		}
	});
    function salvarEdicao(){
		var form = $(document).find("#editCondicao");
		var clientes = [];
		var data = form.serialize();
        if( !$(document).find("#modal_editar").find("#liberado_clientes").is(':checked')){
            
            $(document).find("#modal_editar").find("#table-filters-clientes > tbody").find("tr").each( function(){
                if (!$(this).find("td:eq(0)").hasClass('dataTables_empty')){
                    clientes.push($(this).find("td:eq(0)").text());
                }
            });
			if(clientes.length  > 0){
				data += '&clientes[]='+clientes.join('&clientes[]=');
			}
		}

		$(document).find('.error-message').remove();
		$(document).find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route('condicoes_pagamento_web.edita')}}',
            type: 'POST',
            data: data,
            success: function(data){
				$(form).parents('.modal').modal("hide");
				filterAjax($("#form_filter").serialize());
                message("Atenção", "Condição de pagamento atualizado com sucesso!");
            },
            error: function(data){
                var errors = data.responseJSON.errors;
                form.find('.error-message').remove();
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
	}

	$(document).ready( function(){
		$(document).find("#editCondicao").find('#table-filters-clientes').DataTable(table_filters_dias_options).draw();
		$(document).find("#editCondicao").find("#bt-search").on("click", function(){
			showModalCliente($(this).data("route"), "Lista de Clientes");
		});

		if ($(document).find("#editCondicao").find("#liberado_clientes").is(":checked")){
			$(document).find("#editCondicao").find(".add-clientes").hide();
		}
		else{
			$(document).find("#editCondicao").find(".add-clientes").show();                            
		}

		$(document).find("#editCondicao").find("#codigo").data('oldvalue', $(document).find("#editCondicao").find("#codigo").val())
		
		$(document).find("#editCondicao").find("#codigo").on("change", function(){
			codParaNome($(this).val());
		});

		$(document).find("#editCondicao").find("#liberado_clientes").on("change", function(){
			if ($(this).is(":checked")){
				$(document).find("#editCondicao").find(".add-clientes").hide();
			}
			else{
				$(document).find("#editCondicao").find(".add-clientes").show();                            
				$(document).find('#table-filters-clientes').DataTable().draw();
			}
		});

		$(document).find("#editCondicao").find("#add-vencimento-button").on("click", function(){
			addVencimento();
		});

		$(document).find("#editCondicao").find("#add-clientes-button").on("click", function(){
			addCliente();
		});

		$(document).find("#editCondicao").find("#salvar").on("click", function(){
			salvarEdicao();
		});

		$("#modal_criar_novo").find("#id_web").on("change", function(){
			loadVencimentos($(this).val());
		});

		@if($condicao->nasajon === false)
		loadVencimentos($(document).find("#editCondicao").find("#id_web").val());
		@endif
		$(document).find('#cadCondicao').find('#nasajon_parcelas').on("change", function(){
			loadParcelas($(this).val());
		});

		form_modal_add.find("#nasajon_parcelas_label").autocomplete(optionAutoCompleteParcelas(form_modal_add));
	});

	function optionAutoCompleteParcelas(form_modal_add){
		return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post('{{ route('condicoes_pagamento_web.retorna_vencimentos.autocompleteParcelas') }}', request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_editar').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma parcela encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(form_modal_add).find("#nasajon_parcelas").val(ui.item.value);
				$(form_modal_add).find("#nasajon_parcelas_label").val(ui.item.label);
				return false;
            }
		};
	}

	function showErrorsInputs(form, input, message){

		if(input == 'clientes'){
			$(document).find(form).find("#liberado_clientes").prop('checked', false);
			$(document).find(form).find("#liberado_clientes").trigger('change');

			var $input = $(form).find("#cod_cliente_group");
			$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
			$input.addClass('error-input');
		}
		else{
			var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
			$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
			$input.addClass('error-input');
		}
	}
</script>
@endsection