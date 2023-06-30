@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
			<div class="col-lg-2"> 
				{!! Form::select('estabelecimento', returnEmpresasNasajonView(),'', ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Todos os estabelecimentos']) !!}
			</div>
            <div class="col-lg-2">
                {{ Form::text('pedido', '', ['id' => 'pedido', 'class' => 'form-control', 'placeholder' => 'Numero do pedido', 'maxlength' => '20']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_de', \Carbon\Carbon::now()->format('d/m/Y'), ['id' => 'data_de', 'class' => 'form-control data', 'placeholder' => 'Data de (DD/MM/YYYY)', 'maxlength' => '20']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_ate', \Carbon\Carbon::now()->format('d/m/Y'), ['id' => 'data_ate', 'class' => 'form-control data', 'placeholder' => 'Data até (DD/MM/YYYY)', 'maxlength' => '20']) }}
            </div>
			<div class="col-lg-2"> 
				{!! Form::select('forma_pagamento', ['usar_creditos' => 'Usar Crédito','pix' => 'Pix','stone' => 'Stone','pagar_me' => 'Pagar-me'],'', ['id' => 'forma_pagamento', 'class' => 'form-control', 'placeholder' => 'Todos os Pagamentos']) !!}
			</div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <div class="row m-0 mt-3">
        <div class="col-6 m-0 pl-0">
			<div class="alert alert-success m-0" role="alert">
				<h6>Aguardando pagamento principal cliente</h6>
				<div class="row">
					<div class="col">Pedidos: <a href="#" id="pedido_principal_a_pagar"></a></div>
					<div class="col">Valor: <a href="#" id="valor_principal_a_pagar"></a></div>
				</div>
			</div>
		</div>
        <div class="col-6 m-0 p-0">
			<div class="alert alert-success m-0" role="alert">
				<h6>Aguardando pagamento diferença cliente</h6>
				<div class="row">
					<div class="col">Pedidos: <a href="#" id="pedido_diferenca_a_pagar"></a></div>
					<div class="col">Valor: <a href="#" id="valor_diferenca_a_pagar"></a></div>
				</div>
			</div>
		</div>
    </div>
    <div class="row m-0 mt-3">
        <div class="col alert alert-danger m-0" role="alert">
            <h6>Vendas negadas ou não finalizadas</h6>
            <div class="row">
                <div class="col">Pedidos: <a href="#" id="pedido_erro" onclick=""></a></div>
                <div class="col">Valor: <a href="#" id="valor_erro"></a></div>
            </div>
        </div>
	</div>
	<div class="row m-0 mt-3">
		<div class="col-6 m-0 pl-0">
			<div class="alert alert-warning" role="alert">
				<h6>Venda débito distância (sem nota)</h6>
				<div class="row">
					<div class="col">Pedidos: <a href="#" id="pedido_debito_sem_nota" onclick=""></a></div>
					<div class="col">Valor: <a href="#" id="valor_debito_sem_nota"></a></div>
				</div>
			</div>
		</div>
		<div class="col-6 m-0 p-0">
			<div class="alert alert-warning" role="alert">
				<h6>Venda credito distância (sem nota)</h6>
				<div class="row">
					<div class="col">Pedidos: <a href="#" id="pedido_credito_sem_nota" onclick=""></a></div>
					<div class="col">Valor: <a href="#" id="valor_credito_sem_nota"></a></div>
				</div>
			</div>
		</div>
	</div>


	<div class="row m-0 mt-3">
		<div class="col-6 m-0 pl-0">
			<div class="alert alert-warning" role="alert">
				<h6>Venda débito distância (com nota)</h6>
				<div class="row">
					<div class="col">Pedidos: <a href="#" id="pedido_debito_com_nota" onclick=""></a></div>
					<div class="col">Valor: <a href="#" id="valor_debito_com_nota"></a></div>
				</div>
			</div>
		</div>
		<div class="col-6 m-0 p-0">
			<div class="alert alert-warning" role="alert">
				<h6>Venda credito distância (com nota)</h6>
				<div class="row">
					<div class="col">Pedidos: <a href="#" id="pedido_credito_com_nota" onclick=""></a></div>
					<div class="col">Valor: <a href="#" id="valor_credito_com_nota"></a></div>
				</div>
			</div>
		</div>
	</div>
	<div class="row m-0 mt-3">
		<div class="col-6 m-0 pl-0">
			<div class="alert alert-primary" role="alert">
				<h6>Venda débito presencial (sem nota)</h6>
				<div class="row">
					<div class="col">Pedidos: <a href="#" id="pedido_debito_presencial_sem_nota" onclick=""></a></div>
					<div class="col">Valor: <a href="#" id="valor_debito_presencial_sem_nota"></a></div>
				</div>
			</div>
		</div>
		<div class="col-6 m-0 p-0">
			<div class="alert alert-primary" role="alert">
				<h6>Venda credito presencial (sem nota)</h6>
				<div class="row">
					<div class="col">Pedidos: <a href="#" id="pedido_credito_presencial_sem_nota" onclick=""></a></div>
					<div class="col">Valor: <a href="#" id="valor_credito_presencial_sem_nota"></a></div>
				</div>
			</div>
		</div>
	</div>


	<div class="row m-0 mt-3">
		<div class="col-6 m-0 pl-0">
			<div class="alert alert-primary" role="alert">
				<h6>Venda débito presencial (com nota)</h6>
				<div class="row">
					<div class="col">Pedidos: <a href="#" id="pedido_debito_presencial_com_nota" onclick=""></a></div>
					<div class="col">Valor: <a href="#" id="valor_debito_presencial_com_nota"></a></div>
				</div>
			</div>
		</div>
		<div class="col-6 m-0 p-0">
			<div class="alert alert-primary" role="alert">
				<h6>Venda credito presencial (com nota)</h6>
				<div class="row">
					<div class="col">Pedidos: <a href="#" id="pedido_credito_presencial_com_nota" onclick=""></a></div>
					<div class="col">Valor: <a href="#" id="valor_credito_presencial_com_nota"></a></div>
				</div>
			</div>
		</div>
	</div>
	<div class="row m-0 mt-3">
		<div class="col-6 m-0 pl-0">
			<div class="alert alert-dark" role="alert">
				<h6>Venda Usar Crédito (com nota)</h6>
				<div class="row">
					<div class="col">Pedidos: <a href="#" id="pedido_usar_credito_com_nota" onclick=""></a></div>
					<div class="col">Valor: <a href="#" id="valor_usar_credito_com_nota"></a></div>
				</div>
			</div>
		</div>
		<div class="col-6 m-0 p-0">
			<div class="alert alert-dark" role="alert">
				<h6>Venda Usar Crédito (sem nota)</h6>
				<div class="row">
					<div class="col">Pedidos: <a href="#" id="pedido_usar_credito_sem_nota" onclick=""></a></div>
					<div class="col">Valor: <a href="#" id="valor_usar_redito_sem_nota"></a></div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {
    form = $(document).find("#form_filter");
    //filterAjax();
    form.find("#btn-filterform").off("click");
    form.find("#btn-filterform").on("click", function(){
        filterAjax();
    });

    $(document).find("#pedido_principal_a_liberar").off('click');
    $(document).find("#pedido_principal_a_liberar").on('click', function(){
        abrirALiberarPrincipal();
    });

    $(document).find("#valor_principal_a_liberar").off('click');
    $(document).find("#valor_principal_a_liberar").on('click', function(){
        abrirALiberarPrincipal();
    });

    $(document).find("#pedido_erro").off('click');
    $(document).find("#pedido_erro").on('click', function(){
        abrirErros();
    });

    $(document).find("#valor_erro").off('click');
    $(document).find("#valor_erro").on('click', function(){
        abrirErros();
    });

    $(document).find("#pedido_principal_a_pagar").off('click');
    $(document).find("#pedido_principal_a_pagar").on('click', function(){
        abrirApagarPrincial();
    });

    $(document).find("#valor_principal_a_pagar").off('click');
    $(document).find("#valor_principal_a_pagar").on('click', function(){
        abrirApagarPrincial();
    });

    $(document).find("#pedido_diferenca_a_pagar").off('click');
    $(document).find("#pedido_diferenca_a_pagar").on('click', function(){
        abrirApagarDiferenca();
    });

    $(document).find("#valor_diferenca_a_pagar").off('click');
    $(document).find("#valor_diferenca_a_pagar").on('click', function(){
        abrirApagarDiferenca();
    });
	
	$(document).find("#pedido_debito_com_nota,#valor_debito_com_nota").off('click');
	$(document).find("#pedido_debito_com_nota,#valor_debito_com_nota").on('click', function(){
		debitoComNota();
	});
	
	$(document).find("#pedido_debito_sem_nota,#valor_debito_sem_nota").off('click');
	$(document).find("#pedido_debito_sem_nota,#valor_debito_sem_nota").on('click', function(){
		debitoSemNota();
	});	

	$(document).find("#pedido_credito_com_nota,#valor_credito_com_nota").off('click');
	$(document).find("#pedido_credito_com_nota,#valor_credito_com_nota").on('click', function(){
		creditoComNota();
	});
	
	$(document).find("#pedido_credito_sem_nota,#valor_credito_sem_nota").off('click');
	$(document).find("#pedido_credito_sem_nota,#valor_credito_sem_nota").on('click', function(){
		creditoSemNota();
	});
	
	$(document).find("#pedido_usar_credito_com_nota,#valor_usar_credito_com_nota").off('click');
	$(document).find("#pedido_usar_credito_com_nota,#valor_usar_credito_com_nota").on('click', function(){
		usarCreditoComNota();
	});
	
	$(document).find("#pedido_usar_credito_sem_nota,#valor_usar_redito_sem_nota").off('click');
	$(document).find("#pedido_usar_credito_sem_nota,#valor_usar_redito_sem_nota").on('click', function(){
		usarCreditoSemNota();
	});

	$(document).find("#pedido_debito_presencial_sem_nota,#valor_debito_presencial_sem_nota").off('click');
	$(document).find("#pedido_debito_presencial_sem_nota,#valor_debito_presencial_sem_nota").on('click', function(){
		debitoPresencialSemNota();
	});
	
	$(document).find("#pedido_credito_presencial_sem_nota,#valor_credito_presencial_sem_nota").off('click');
	$(document).find("#pedido_credito_presencial_sem_nota,#valor_credito_presencial_sem_nota").on('click', function(){
		creditoPresencialSemNota();
	});

	$(document).find("#pedido_debito_presencial_com_nota,#valor_debito_presencial_com_nota").off('click');
	$(document).find("#pedido_debito_presencial_com_nota,#valor_debito_presencial_com_nota").on('click', function(){
		debitoPresencialComNota();
	});
	
	$(document).find("#pedido_credito_presencial_com_nota,#valor_credito_presencial_com_nota").off('click');
	$(document).find("#pedido_credito_presencial_com_nota,#valor_credito_presencial_com_nota").on('click', function(){
		creditoPresencialComNota();
	});
    $(document).find('#form_filter').find('.data').datepicker({ 
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        language: 'pt-BR',
        autoHide: true,
        endDate: new Date()
    });
    $(document).find('#form_filter').find('.data').mask('00/00/0000');
});

function filterAjax(){

    $(document).find('#pedido_debito_sem_nota').html('');
    $(document).find('#valor_debito_sem_nota').html('');
    $(document).find('#pedido_credito_sem_nota').html('');
    $(document).find('#valor_credito_sem_nota').html('');

    $(document).find('#pedido_debito_com_nota').html('');
    $(document).find('#valor_debito_com_nota').html('');
    $(document).find('#pedido_credito_com_nota').html('');
    $(document).find('#valor_credito_com_nota').html('');

    $(document).find('#pedido_principal_a_pagar').html('');
    $(document).find('#valor_principal_a_pagar').html('');
    $(document).find('#pedido_diferenca_a_pagar').html('');
    $(document).find('#valor_diferenca_a_pagar').html('');
    $(document).find('#pedido_erro').html('');
    $(document).find('#valor_erro').html('');

    $(document).find('#pedido_usar_credito_sem_nota').html('');
    $(document).find('#valor_usar_redito_sem_nota').html('');
    $(document).find('#pedido_usar_credito_com_nota').html('');
    $(document).find('#valor_usar_credito_com_nota').html('');
    $(document).find('#pedido_debito_presencial_sem_nota').html('');
    $(document).find('#valor_debito_presencial_sem_nota').html('');

    $(document).find('#pedido_credito_presencial_sem_nota').html('');
    $(document).find('#valor_credito_presencial_sem_nota').html('');
    $(document).find('#pedido_debito_presencial_com_nota').html('');
    $(document).find('#valor_debito_presencial_com_nota').html('');
    $(document).find('#pedido_credito_presencial_com_nota').html('');
    $(document).find('#valor_credito_presencial_com_nota').html('');

    form = $(document).find("#form_filter");
    data_form = form.serialize();
    $.ajax({
        url: '{{ route('acompanhamento_cielo.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(callback){
            if(callback.status == 'success'){
				dados = callback.response;
				$(document).find('#pedido_debito_sem_nota').html(dados.debito_sem_nota.pedido);
				$(document).find('#valor_debito_sem_nota').html(dados.debito_sem_nota.valor);

				$(document).find('#pedido_credito_sem_nota').html(dados.credito_sem_nota.pedido);
				$(document).find('#valor_credito_sem_nota').html(dados.credito_sem_nota.valor);

				$(document).find('#pedido_debito_com_nota').html(dados.debito_com_nota.pedido);
				$(document).find('#valor_debito_com_nota').html(dados.debito_com_nota.valor);

				$(document).find('#pedido_credito_com_nota').html(dados.credito_com_nota.pedido);
				$(document).find('#valor_credito_com_nota').html(dados.credito_com_nota.valor);

				$(document).find('#pedido_principal_a_pagar').html(dados.a_pagar_principal.pedido);
				$(document).find('#valor_principal_a_pagar').html(dados.a_pagar_principal.valor);

				$(document).find('#pedido_diferenca_a_pagar').html(dados.a_pagar_diferenca.pedido);
				$(document).find('#valor_diferenca_a_pagar').html(dados.a_pagar_diferenca.valor);

				$(document).find('#pedido_erro').html(dados.erro.pedido);
				$(document).find('#valor_erro').html(dados.erro.valor);

				$(document).find('#pedido_usar_credito_sem_nota').html(dados.usar_credito_sem_nota.pedido);
				$(document).find('#valor_usar_redito_sem_nota').html(dados.usar_credito_sem_nota.valor);
				
				$(document).find('#pedido_usar_credito_com_nota').html(dados.usar_Credito_com_nota.pedido);
				$(document).find('#valor_usar_credito_com_nota').html(dados.usar_Credito_com_nota.valor);

				$(document).find('#pedido_debito_presencial_sem_nota').html(dados.debito_presencial_sem_nota.pedido);
				$(document).find('#valor_debito_presencial_sem_nota').html(dados.debito_presencial_sem_nota.valor);
				
				$(document).find('#pedido_credito_presencial_sem_nota').html(dados.credito_preencial_sem_nota.pedido);
				$(document).find('#valor_credito_presencial_sem_nota').html(dados.credito_preencial_sem_nota.valor);
				
				$(document).find('#pedido_debito_presencial_com_nota').html(dados.debito_presencial_com_nota.pedido);
				$(document).find('#valor_debito_presencial_com_nota').html(dados.debito_presencial_com_nota.valor);
				
				$(document).find('#pedido_credito_presencial_com_nota').html(dados.credito_presencial_com_nota.pedido);
				$(document).find('#valor_credito_presencial_com_nota').html(dados.credito_presencial_com_nota.valor);
            }
        }
    });
}
function abrirApagarDiferenca(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.em_aberto_diferenca") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("acompanhamento_pago_diferenca", "Aguardando pagamento diferença cliente", body, 'modal-lg');
		}
	});
}
function abrirApagarPrincial(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.em_aberto") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("acompanhamento_em_aberto", "Aguardando pagamento principal cliente", body, 'modal-lg');
		}
	});
}
function abrirALiberarPrincipal(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.pago") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("acompanhamento_pago", "Pedidos pagos principal", body, 'modal-lg');
		}
	});
}
function abrirErros(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.erros") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("acompanhamento_pago", "Pedidos que estão com erro no pagamento", body, 'modal-lg');
		}
	});
}
function debitoComNota(){
	form = $(document).find("#form_filter");
	data_form = form.serialize();
	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.debito_com_nota") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("acompanhamento_debito_com_nota", "Debito com nota", body, 'modal-lg');
		}
	});
}
function debitoSemNota(){
	form = $(document).find("#form_filter");
	data_form = form.serialize();
	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.debito_sem_nota") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("acompanhamento_debito_sem_nota", "Debito sem nota", body, 'modal-lg');
		}
	});
}
function creditoComNota(){
	form = $(document).find("#form_filter");
	data_form = form.serialize();
	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.credito_com_nota") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("acompanhamento_credito_com_nota", "Crédito com nota", body, 'modal-lg');
		}
	});
}
function creditoSemNota(){
	form = $(document).find("#form_filter");
	data_form = form.serialize();
	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.credito_sem_nota") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("acompanhamento_credito_sem_nota", "Crédito sem nota", body, 'modal-lg');
		}
	});
}

function usarCreditoComNota(){
	form = $(document).find("#form_filter");
	data_form = form.serializeArray();
	data_form.push({name: 'tipo_abertura', value: 'usar_credito_com_nota'});
	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.boleto_com_nota") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("acompanhamento_credito_sem_nota", "Usar Crédito Com Nota", body, 'modal-lg');
		}
	});
}

function usarCreditoSemNota(){
	form = $(document).find("#form_filter");
	data_form = form.serializeArray();
	data_form.push({name: 'tipo_abertura', value: 'usar_credito_sem_nota'});
	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.boleto_com_nota") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("acompanhamento_credito_sem_nota", "User Crédito Sem Nota", body, 'modal-lg');
		}
	});
}

function creditoPresencialSemNota(){
	form = $(document).find("#form_filter");
	data_form = form.serializeArray();
	data_form.push({name: 'tipo_abertura', value: 'credito_sem_nota'});
	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.venda_presencial") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("acompanhamento_credito_sem_nota", "Credito presencial sem nota", body, 'modal-lg');
		}
	});
}

function creditoPresencialComNota(){
	form = $(document).find("#form_filter");
	data_form = form.serializeArray();
	data_form.push({name: 'tipo_abertura', value: 'credito_com_nota'});
	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.venda_presencial") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("acompanhamento_credito_sem_nota", "Crédito presencial com nota", body, 'modal-lg');
		}
	});
}

function debitoPresencialComNota(){
	form = $(document).find("#form_filter");
	data_form = form.serializeArray();
	data_form.push({name: 'tipo_abertura', value: 'debito_com_nota'});

	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.venda_presencial") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("acompanhamento_credito_sem_nota", "Débito presencial com nota", body, 'modal-lg');
		}
	});
}

function debitoPresencialSemNota(){
	form = $(document).find("#form_filter");
	
	data_form = form.serializeArray();
	data_form.push({name: 'tipo_abertura', value: 'debito_sem_nota'});
	$.ajax({
		url: "{{ route("acompanhamento_cielo.modal.venda_presencial") }}",
		type: 'POST',
		data: data_form,
		success: function(body){
			createModal("debito_presencial_sem_nota", "Débito Presencial Sem Nota", body, 'modal-lg');
		}
	});
}


@endsection
