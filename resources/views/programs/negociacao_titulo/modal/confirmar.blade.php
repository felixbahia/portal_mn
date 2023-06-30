@extends('layouts.page-dialog')

@section('content')
<form action="#" method="post" id="form_renegociacao_titulo_confirmar" name="form_renegociacao_titulo_confirmar" onsubmit="return false">
	@csrf
	{!! Form::hidden('id', $id, ['id' => 'id']) !!}
	{!! Form::hidden('hash', $hash, ['id' => 'hash']) !!}
	{!! Form::hidden('tipo', '', ['id' => 'tipo']) !!}
	{!! Form::hidden('socios', $socios, ['id' => 'socios']) !!}
    {!! Form::hidden('avalistas', $avalistas, ['id' => 'avalistas']) !!}
	{!! Form::hidden('email', '', ['id' => 'email']) !!}
	<div class="containter">
		<div class="row">
			<div class="col-sm-12">
				<h4>Gerar esta renegociação?</h4>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				{{ Form::button('Com confissão de dívida', array('class' => 'btn btn-success float-left', 'id' => 'btn-com-confissao')) }}
				{{ Form::button('Sem confissão de dívida', array('class' => 'btn btn-primary float-right', 'id' => 'btn-sem-confissao')) }}
			</div>
		</div>
	</div>
</form>
<script>
	$(document).ready( function () {
        form_modal_renegociacao_titulo_confirmar = $(document).find("#form_renegociacao_titulo_confirmar");
        form_modal_renegociacao_titulo_confirmar.find("#btn-sem-confissao").off("click");
		form_modal_renegociacao_titulo_confirmar.find("#btn-sem-confissao").on("click", function(event) {
			if(form_modal_renegociacao_titulo_confirmar.find("#id").val() == ''){
				event.stopPropagation();
				salvarRenegociacao(form_modal_renegociacao_titulo_confirmar, 'sem_confissao');
			}else{
				event.stopPropagation();
				editarRenegociacao(form_modal_renegociacao_titulo_confirmar, 'sem_confissao');
			}
		});

		form_modal_renegociacao_titulo_confirmar = $(document).find("#form_renegociacao_titulo_confirmar");
        form_modal_renegociacao_titulo_confirmar.find("#btn-com-confissao").off("click");
		form_modal_renegociacao_titulo_confirmar.find("#btn-com-confissao").on("click", function(event) {
			if(form_modal_renegociacao_titulo_confirmar.find("#id").val() == ''){
				event.stopPropagation();
				salvarRenegociacao(form_modal_renegociacao_titulo_confirmar, 'com_confissao');
			}else{
				event.stopPropagation();
				editarRenegociacao(form_modal_renegociacao_titulo_confirmar, 'com_confissao');
			}
		});
	});

	function salvarRenegociacao(form_modal_renegociacao_titulo_confirmar, confissao){
		form_modal_renegociacao_titulo_confirmar.find("#tipo").val(confissao);
        data_form_modal_confirmar = form_modal_renegociacao_titulo_confirmar.serialize();
        $title = 'Selecione os Títulos para Renegociação';
        $.ajax({
            url: '{{ route("renegociacao_titulo.salvar_renegociacao") }}',
            type: 'post',
            data: data_form_modal_confirmar,
            success: function(callback){
                $(form_modal_renegociacao_titulo_confirmar).parents('.modal').modal('hide');
				$(form_modal_negociacao).parents('.modal').modal('hide');
                filterAjaxModalSelecionarTitulosRenegociacao();
                message("Atenção", "Renegociação enviada com sucesso!");
            },
            error: function(callback) {
                var json_error = callback.responseJSON;
                if(Object.keys(json_error).length > 0){
                    for(var field in json_error.error){
                        message("Atenção", json_error.error[field]);
                    }
                }
            }
        });
    }

	function editarRenegociacao(form_modal_renegociacao_titulo_confirmar, confissao){
        form_modal_renegociacao_titulo_confirmar.find("#tipo").val(confissao);
        data_form_modal_confirmar = form_modal_renegociacao_titulo_confirmar.serialize();
        $.ajax({
            url: '{{ route("renegociacao_titulo.editar_renegociacao") }}',
            type: 'post',
            data: data_form_modal_confirmar,
            success: function(callback){
                $(form_modal_renegociacao_titulo_confirmar).parents('.modal').modal('hide');
				$(form_modal_negociacao).parents('.modal').modal('hide');
                filterAjax();
                message("Atenção", "Renegociação enviada com sucesso!");
            },
            error: function(callback) {
                var json_error = callback.responseJSON;
                if(Object.keys(json_error).length > 0){
                    for(var field in json_error.error){
                        message("Atenção", json_error.error[field]);
                    }
                }
                message("Atenção", callback.responseJSON.message);
            }
        });
    }
</script>
@endsection