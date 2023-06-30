@extends('layouts.page-dialog')

@section('content')
	<form action="{{ route('cliente_novo.aprovacao.aprovado') }}" id="form_aprovar_cadastro" name="form_aprovar_cadastro" onsubmit="return false">
		@csrf
		{!! Form::hidden("id", $dados["id"]) !!}
		<div class="form-group">
			<b>Dados Básicos do cliente:</b>
			<b>@if($dados['fisica_juridica'] === 'f') Nome: @else Razão: @endif</b> {!! $dados["nome_razao"] !!}<br />
			<b>@if($dados['fisica_juridica'] === 'f') CPF: @else CNPJ: @endif</b> {!! $dados["cpf_cnpj"] !!}<br />
			<b>E-mail</b> {!! $dados["email"] !!}<br />
			<b>Telefone</b> {!! $dados["telefone"] !!}<br />
		</div>
		<div class="form-group">
			{{ Form::label('limite_credito', 'Informe o Limite de Crédito:') }}
			{{ Form::text('limite_credito', '', ['class' => 'form-control', 'maxlength' => '20']) }}
		</div>
		<div class="form-group">
			{{ Form::label('conceito', 'Conceito:') }}
			{{ Form::select('conceito', $conceitos, '', ['class' => 'form-control']) }}
		</div>
		<div class="content-buttons float-right">
			{{ Form::button('Cancelar', ['class' => 'btn btn-danger', "id"=>"bt_cancel"]) }}
			{{ Form::button('Salvar', ['class' => 'btn btn-success', "id"=>"bt_salvar"]) }}
		</div>
	</form>
	<script type="text/javascript">
		$(document).ready(function($) {
			$(document).find('#limite_credito').maskMoney({prefix: 'R$ ', thousands: '.', decimal: ',', allowZero: true});
			$(document).find("#bt_cancel").off("click");
			$(document).find("#bt_cancel").on("click", function(){
				$(this).parents(".modal").modal("hide");
			});
			$(document).find("#bt_salvar").off("click");
			$(document).find("#bt_salvar").on("click", function(){
				aprovavarCadastro($(this));
			});
		});
		function aprovavarCadastro($this){
			var $form = $("#form_aprovar_cadastro");
			var $form_data = $form.serialize();
			var $action = $form.attr("action");
			clearErrorsInputs($form);
			$.ajax({
				url: $action,
				type: 'POST',
				dataType: 'JSON',
				data: $form_data,
				success: function(callback){
					if(callback.status === "success"){
						message("Atenção", callback.message);
						filterAjax($("#form_filter").serialize());
						$($this).parents(".modal").modal("hide");
					} else {
						message("Atenção", callback.message);
					}
				},
                error: function(callback){
					hide_loader();
					if(callback.responseJSON.errors){
						var errors = callback.responseJSON.errors;
						for(var field in errors){
							showErrorsInputs($form, field, errors[field])
						}
						$form.find('.error-message').eq(0).focus();
		            }else if(callback.responseJSON.message){
						message("Atenção", callback.responseJSON.message);
		            }else{
						message("Atenção", "Ocorreu uma instabilidade.<br/>Tente novamente mais tarde!");
		            }
                }
			});
		}
		function clearErrorsInputs($form){
			$form.find('.error-message').remove();
			$form.find('div, input, select').each(function(){
				if($(this).hasClass("is-invalid")){
					$(this).removeClass("is-invalid")
				}
			});
		}
		function showErrorsInputs(form, input, message){
			var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"']");
			$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
			$input.addClass('is-invalid');
		}
	</script>
@endsection