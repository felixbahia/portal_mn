@extends('layouts.page-dialog')

@section('content')
<form action="#" method="post" id="form_recusa_cliente" name="form_recusa_cliente" onsubmit="return false">
	@csrf
    {!! Form::hidden('id', $id, ['id' => 'id']) !!}
    {!! Form::hidden('id_avalista', $id_avalista, ['id' => 'id_avalista']) !!}
    {!! Form::hidden('tipo', $tipo, ['id' => 'tipo']) !!}
	<div class="containter">
		<div class="row">
			<div class="col-sm-12">
				<h4>Deseja realmente recusar a prévia renegociação?</h4>
			</div>
		</div>
		<hr>
		<div class="row">
            <div class="col-sm-12">
                <b>Titulos:</b><br>
            </div>
        </div>
		<div class="form-row">
			<div class="form-group col-sm-12"> 
				<div class="content-dialog-table">
					<div class="content-table">
						<table class="table table-striped" id="table-unidade_negocio-membros">
							<thead>
								<th>Título</th>
								<th class="tb_number">Valor</th>
							</thead>
							<tbody>
								@foreach ($dados['titulos'] as $titulo)
									<tr>
										<td>{{ $titulo['titulo'] }}</td>
										<td class="tb_number">{{ $titulo['valor_original'] }}</td>
									</tr>
								@endforeach
							</tbody>
							<tfoot>
								<tr>
									<td class="tb_number"></td>
									<td class="tb_number">{{ $total }}</td>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
            <div class="col-sm-12">
                <b>Data:</b><br>
                {{ $dados['data'] }}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <b>Valor Total:</b><br>
                {{ $dados['valor_total'] }}
            </div>
            <div class="col-sm-4">
                <b>Juros por Mês:</b><br>
                {{ $dados['juros_mes'] }}
            </div>
            <div class="col-sm-4">
                <b>Valor da Renegociação:</b><br>
                {{ $dados['valor_total_juros'] }}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-4">
                <b>Quantidade de Parcelas:</b><br>
                {{ $dados['quantidade_parcela'] }}
            </div>
            <div class="col-sm-4">
                <b>Valor da Parcela:</b><br>
                {{ $dados['parcela_valor'] }}
            </div>
            <div class="col-sm-4">
                <b>Período:</b><br>
                {{ $dados['periodo'] }}
            </div>
        </div>
		<div class="row">
			<div class="col-sm-12 my-4">
				<b>Justificativa:</b>			
			</div>
		</div>
		
		<div class="row">
			<div class="col-sm-12">
				{!! Form::textarea('motivo', '', ['id' => 'motivo', 'maxlength' => '250', 'rows' => '3', 'class' => 'form-control']) !!}
			</div>
		</div>
		<br>
		<div class="row">
			<div class="col-sm-12">
				{{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
			</div>
		</div>
	</div>
</form>
<script>
	$(document).ready( function () {
		form_modal_recusa_cliente = $(document).find("#form_recusa_cliente");
		
		form_modal_recusa_cliente.find('#btn-salvar').off('click');
		form_modal_recusa_cliente.find('#btn-salvar').on('click', function(){
            recusoContrato(form_modal_recusa_cliente);
        });
	});

	function recusoContrato(form_modal_recusa_cliente){
        data_form_modal_recusa_cliente = form_modal_recusa_cliente.serialize();
        $.ajax({
            url: "{{ route('aprovacao_renegociacao_titulo.recusa_cliente') }}", 
            dataType: 'json',
            data: data_form_modal_recusa_cliente,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal_recusa_cliente).parents('.modal').modal('hide');
                window.location.reload(true);
            },
            error: function(callback){
                limparMesagemErroAdd();
                var dados = callback.responseJSON;
                mensagemErroAdd(dados);
            }
        });
    }

	function limparMesagemErroAdd(){      
        var form_modal_recusa_cliente = $(document).find("#form_recusa_cliente");
        form_modal_recusa_cliente.find('.error-message').remove();
        form_modal_recusa_cliente.find('input, select, span, textarea').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_recusa_cliente = $(document).find("#form_recusa_cliente");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_recusa_cliente, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form_modal_recusa_cliente, input, message){
        var $input = form_modal_recusa_cliente.find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection