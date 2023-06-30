@extends('layouts.page-dialog')

@section('content')
<form method="post" id="frm_cancelamento_pedido" name="frm_cancelamento_pedido" onsubmit="return false">
    @csrf
    {!! Form::hidden('pedido', $dados['id'], ['id' => 'pedido']) !!}

	<div class="containter">
		<div class="row">
			<div class="col-sm-12">
				<h4>Deseja realmente cancelar este pedido?</h4>
			</div>
		</div>

        <div class="row">

			<div class="col-sm-4">
				<b>Pedido:</b>
			</div>
			
			<div class="col-sm-8">
                {{$dados['numero']}}
			</div>

		</div>

		<div class="row">

			<div class="col-sm-4">
				<b>Estabelecimento:</b>
			</div>
			
			<div class="col-sm-8">
                {{$dados['estabelecimento']}}
			</div>

		</div>

		<div class="row">
			<div class="col-sm-4">
				<b>Cliente:</b>
			</div>
			
			<div class="col-sm-8">
                {{$dados['cliente']}}
			</div>
		</div>

		<div class="row">
			<div class="col-sm-4">
				<b>Valor Total:</b> 
			</div>
			
			<div class="col-sm-8">
                {{$dados['valor']}}
			</div>
		</div>
		
	    @csrf
		{{ Form::hidden('id', '', array('id' => 'id')) }}

		<div class="row">
			<div class="col-sm-12 my-4">
				<b>Justificativa:</b>			
			</div>
		</div>
		
		<div class="row">
			<div class="col-sm-12">
				{{ Form::select('motivo_cancelamento', $motivos, '', array('id' => 'motivo_cancelamento', 'class' => 'form-control')) }}
			</div>
		</div>
		<br>
		<div class="content-buttons float-right">
            <button name="btn-delete" id="btn-delete" class="btn btn-danger text-right">Cancelar Pedido</button>
        </div>
	</div>
</form>
<script type="text/javascript">
	$(document).ready(function($) {
		$(document).find("#btn-delete").off("click");
		$(document).find("#btn-delete").on("click", function(event) {
            form_modal = $(document).find('#frm_cancelamento_pedido');
            data_form_modal = form_modal.serialize();
			var $this = $(this);
			$.ajax({
				url: "{{ route('pedidos_orcamentos.cancelar') }}",
				dataType: 'json',
				data: data_form_modal,
				method: 'POST',
				success: function(callback){
					if(callback.status === "success"){
						$($this).parents(".modal").modal("hide");
						message("Atenção", "Pedido cancelado com sucesso!");
					} else {
						message("Atenção", callback.message);
					}
				},
                error: function(callback){
                    if(callback.responseJSON.message != ''){
                        message('Erro', callback.responseJSON.message);
                    }
                    else if(Object.values(callback.responseJSON.error).length > 0){
                        message('Erro', Object.values(callback.responseJSON.error).join('<br>'));
                    }
                }
			});
		});
	});
</script>
@endsection