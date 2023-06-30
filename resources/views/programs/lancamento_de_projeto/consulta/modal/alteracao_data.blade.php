@extends('layouts.page-dialog')

@section('content')

<form action="" onsubmit="return false" name="form_alteracao_data" id="form_alteracao_data">

	@csrf
	{!! Form::hidden('id_projeto', $id_projeto, ['id' => 'id_projeto']) !!}
	{!! Form::hidden('id_faccao', $id_faccao, ['id' => 'id_faccao']) !!}
	<div class="row">
		<div class="col-xl-6">
			{{ Form::label('data_entrega_cliente', 'Data Entrega Cliente', []) }}
        	{{ Form::text('data_entrega_cliente', $data_entrega_cliente, ['id' => 'data_entrega_cliente', 'class' => 'form-control data', 'maxlength' => '20']) }}
		</div>
    </div>
    
    <div class="row">
		<div class="col-xl-6">
			{{ Form::label('data_previsao_entrega', 'Data de Previsão de Entrega', []) }}
        	{{ Form::text('data_previsao_entrega', $data_previsao_entrega, ['id' => 'data_previsao_entrega', 'class' => 'form-control data', 'maxlength' => '20']) }}
		</div>
	</div>

	<div class="row">
		<div class="col-xl-12 text-right">
			{{ Form::button('Salvar', ['class' => 'btn btn-primary float-right', 'id' => 'btn-salvar']) }}
		</div>
	</div>

</form>
<script>
	initMaskCamposAdd($(document).find('#form_alteracao_data'));
	function initMaskCamposAdd(form_modal){
		
		form_modal.find('.data').datepicker({ 
			format: 'dd/mm/yyyy',
			zIndex: 2000,
			language: 'pt-BR',
			autoHide: true,
		});
		form_modal.find('.data').mask('00/00/0000');
	}

	$(document).ready( function () {
        form_modal = $(document).find('#form_alteracao_data');
        form_modal.find("#btn-salvar").on('click', function(){
            alterarDados(form_modal.serialize());
        });
	});
	
	function alterarDados(data_form_modal){
		limparMesagemErro(form_modal);
        $.ajax({
            url: "{{ route('lancamento_projeto.faccao.alteracao_data')}}", 
            dataType: 'json',
            data: data_form_modal,
            method: 'POST',
            async: false,
            success: function(data){
                $(form_modal).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(data){
                var errors = data.responseJSON.error;
				for(var field in errors){
					showErrorsInputs(form_modal, field, errors[field])
				}
            }
        });
	}

</script>
@endsection