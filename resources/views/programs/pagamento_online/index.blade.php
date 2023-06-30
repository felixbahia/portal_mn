@extends("layouts.app-deslogado")

@section("content")
<div class="content_pagamento_pedido">
	<div class="content_dados_pedido">
		<div class="col-lg-12 mb-3">
			<h5>Caro cliente.<br><br>Abaixo as informações referente ao seu pedido. Para pagamento preencha as informações, em caso de duvida entre em contato com o seu representante comercial.</h5>
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
				<b>Condição de pagamento:</b> {{ $pedido->condicao_pagamento_detalhes->descricao }}
			</h5>
		</div>
	</div>
	<div class="content_produtos_total_pagamento">
		<div class="content_produtos_pagamento">
			<h5>Produtos (<a href="#" onclick="abrirItens('{{ encrypt($pedido->id) }}')">visualize aqui os produtos</a>)</h5>
		</div>
		<div class="content-total-pagamento">
			<div class="row">
				<div class="col-6">Total dos Produtos</div>
				<div class="col-6 text-right">{{ parserValor($pedido->valor_total_produtos) }}</div>
			</div>
			<div class="row">
				<div class="col-6">Total de Frete</div>
				<div class="col-6 text-right">{{ parserValor($pedido->frete) }}</div>
			</div>
			<div class="row border-top">
				<div class="col-6"><b>Total do Pedido</b></div>
				<div class="col-6 text-right"><b>{{ parserValor($pedido->valor_total_nota) }}</b></div>
			</div>
		</div>
	</div>
	<div class="content_pagamento">
		@if($pago == false && $processamento == false)
		<form action="#" id="form_pagamento_online" name="form_pagamento_online" onsubmit="return false;">
			@csrf
			{!! Form::hidden("pedido", $pedido_token, ["id" => "pedido"]) !!}
			{!! Form::hidden("email_pedido", $pedido->email_comprador, ["id" => "email_pedido"]) !!}
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
		@elseif($pago == true && $liberado == true)
		<h3><b>Pedido já pago</b></h3>
		@elseif($pago == true && $liberado == false)
		<h3><b>Prazo para pagamento em 48h vencido, por favor entrar em contato com seu representante comercial</b></h3>
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
function abrirItens($id){
	$.ajax({
		url: "{{ route("pedido_online.itens_pedido") }}",
		type: 'POST',
		data: {
			_token: '{{ csrf_token() }}',
			id: $id
		},
		success: function(body){
			createModal("itens_pedido", "itens do pedido {{ $pedido->id }}", body, 'modal-lg');
		}
	});
}
function enviarPagamento(){
	form = $(document).find("#form_pagamento_online");
	$(document).find(".error-message").remove();
	email = $(document).find("#email").val();
	form.find("#email_pedido").val(email);
	data_form = form.serialize();
	$.ajax({
		url: "{{ route("pedido_online.pagar") }}",
		dataType: "json",
		data: data_form,
		method: "POST",
		success: function(callback){
			if(callback.status === 'success'){
				message('Atenção', callback.message);
				setTimeout(function(){
					window.location.href = "{{ route("pedido_online.pagamento", ["token" => 'agradecimento']) }}";
				}, 500);
			}
		},
		error: function(callback){
			if(callback.status != 422){
				switch(callback.status){					
					case 400:
						window.location.reload();
					break;
					case 410:
						window.location.reload();
					break;
				}
			}else{
				if(callback.responseJSON.error){
					if(callback.responseJSON.error.length > 0){
						var errors = callback.responseJSON.error;
						form.find(".error-message").remove();
						for(var field in errors){
							if(field == 0){
								message("Atenção", callback.responseJSON.message);
							}else{
								showErrorsInputs(form, field, errors[field]);
							}
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