@extends("layouts.app-deslogado")

@section("content")
<div class="content_pagamento_pedido">
	<div class="content_dados_pedido">
		<div class="col-lg-12 mb-3">
			<h5>Caro cliente.<br><br>Abaixo as informações referente a diferença do seu pedido . Para pagamento preencha as informações, em caso de duvida entre em contato com o seu representante comercial.</h5>
		</div>
		<div class="col-lg-12 border-bottom">
			<h5>
				<b>Pedido nº</b> {{ $pedido->id }} - 
				<b>Data do pedido:</b> {{ $pedido->updated_at->format('d/m/Y') }} - 
				<b>Representante comercial:</b> {{ $pedido->usuario_detalhes->codigo_representante }} - {{ $pedido->usuario_detalhes->name }}
			</h5>
		</div>
		<div class="col-lg-12 border-bottom">
			<h5>
				<b>Estabelecimento:</b> {{ returnEmpresasNasajonView()[$pedido->estabelecimento] }} - 
				<b>Cliente:</b> {{ $pedido->cliente->nome }} - {{ $pedido->cliente->cpf_cnpj }}
			</h5>
		</div>
		<div class="col-lg-12">
			<h5><b>E-mail:</b></h5>
			<input type="text" id="email" name="email" class="form-control" value="{{ $pedido->email_comprador }}" style="display: inline !important;width: auto;"></input>
		</div>
		
		<div class="col-lg-12">
			<h5>
				<b>Condição de pagamento:</b> CARTAO DE CREDITO 1 X 
			</h5>
		</div>
	</div>
	<div class="content_produtos_total_pagamento">
		<div class="content-total-pagamento">
			<div class="row">
				<div class="col-6">Total do pedido</div>
				<div class="col-6 text-right">{{ parserValor($pedido->valor_total_nota) }}</div>
			</div>
			<div class="row border-top">
				<div class="col-6">Total da diferença</div>
				<div class="col-6 text-right">{{ parserValor($diferenca) }}</div>
			</div>
		</div>
	</div>
	<div class="content_pagamento">
		@if($pago == false && $processamento == false)
		<form action="#" id="form_pagamento_online" name="form_pagamento_online" onsubmit="return false;">
			@csrf
			{!! Form::hidden("email_pedido", $pedido->email_comprador, ["id" => "email_pedido"]) !!}
			{!! Form::hidden("pedido", $pedido_token, ["id" => "pedido"]) !!}
			<div class="row">
				<div class="col-lg-12">
					{!! Form::text("cartao", "", ["id" => "cartao", "placeholder" => "Numero do cartão"]) !!}
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					{!! Form::text("nome", "", ["id" => "nome", "placeholder" => "Nome impresso", 'maxlength' => '50']) !!}
				</div>
			</div>
			<div class="row">
				<div class="col-lg-6">
					{!! Form::text("vencimento", "", ["id" => "vencimento", "placeholder" => "Vencimento MM/YY", 'maxlength' => '10']) !!}
				</div>
				<div class="col-lg-6">
					{!! Form::text("codigo_verificacao", "", ["id" => "codigo_verificacao", "placeholder" => "Código de verificação", 'maxlength' => '3']) !!}
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					{!! Form::submit("Pagar", ["id" => "pagar", "class" => "btn btn-success float-right"]) !!}
				</div>
			</div>
		</form>
		@elseif($processamento == true)
		<h3><b>Este pedido já foi submetido estamos aguardado confirmação de crédito</b></h3>
		@else
		<h3><b>Pedido já pago</b></h3>
		@endif
	</div>
</div>
@endsection
@section("script-footer")

$(function(){
	@if(isset($mensagem))
	message('Atenção', '{{ $mensagem['message'] }}');
	@if($mensagem['status']=='success')
	setTimeout(function(){
		window.location.href = "{{ route('home') }}";
	}, 500);
	@endif
	@endif
	
	var options =  {
		onKeyPress: function(cartao, e, field, options) {
			var masks = ['0000 0000 0000 0000', '0000 000000 00000'];
			switch(cartao.substring(0, 2)){
				case '36':
				case '38':
				case '34':
				case '37':
					$('#cartao').mask(masks[1], options);
				break;
				default:
				$('#cartao').mask(masks[0], options);
			}
		}
	};
	$('#cartao').mask("0000 0000 0000 0000", options);
	$('#vencimento').mask("00/00");
	$('#codigo_verificacao').mask("0009");
	$('#vencimento').datepicker({
		language: 'pt-BR',
		format: 'mm/yy',
		zIndex: 2000,
		autoHide: true,
		startDate: new Date()
	});
	$(document).find("#pagar").off('click');
	$(document).find("#pagar").on('click', function(event) {
		event.preventDefault();
		event.stopPropagation();
		enviarPagamento();
	});
});
function enviarPagamento(){
	form = $(document).find("#form_pagamento_online");
	$(document).find(".error-message").remove();
	email = $(document).find("#email").val();
	form.find("#email_pedido").val(email);
	data_form = form.serialize();
	$.ajax({
		url: "{{ route("pedido_online.pagar_restante") }}",
		dataType: "json",
		data: data_form,
		method: "POST",
		success: function(callback){
			if(callback.status === 'success'){
				message('Atenção', callback.message);
				if(callback.response.link){
					setTimeout(function(){
						window.location.href = callback.response.link;
					}, 500);
				}
			}
		},
		error: function(callback){
			if(callback.status != 422){
				switch(callback.status){					
					case 409:
						window.location.reload();
					break;
					case 419:
						window.location.reload();
					break;
				}
			}else{
				if(callback.responseJSON.error){
					if(callback.responseJSON.error.length !== 0){
						var errors = callback.responseJSON.error;
						form.find(".error-message").remove();
						for(var field in errors){
							showErrorsInputs(form, field, errors[field])
						}
					}
					else if(callback.responseJSON.message != ''){
						message("Atenção", callback.responseJSON.message);
					}
				}else{
					message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
				}
			}
		}
	});
}
function showErrorsInputs(form, input, message){
	if(input.localeCompare('email_pedido') == 0){
		var $input = $(document).find("input[name='email'],select[name='email']");
		$input.after("<label class='error-message' for='email'>"+message+"</label>");
		$input.addClass('error-input');
	}else{
		var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"']");
		$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
		$input.addClass('error-input');
	}
}
    
@endsection
