@extends('layouts.page-dialog')

@section('content')
<form action="#" method="post" id="form_renegociacao_titulo_deletar" name="form_renegociacao_titulo_deletar" onsubmit="return false">
	@csrf
	{!! Form::hidden('id', $id, ['id' => 'id']) !!}
	<div class="containter">
		<div class="row">
			<div class="col-sm-12">
				<h4>Deseja realmente excluir esta renegociação?</h4>
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
			<div class="col-sm-12">
				{{ Form::button('Excluir', array('class' => 'btn btn-primary float-right', 'id' => 'btn-delete')) }}
			</div>
		</div>
	</div>
</form>
<script>
	$(document).ready( function () {
        form_modal_renegociacao_titulo_deletar = $(document).find("#form_renegociacao_titulo_deletar");
        form_modal_renegociacao_titulo_deletar.find("#btn-delete").off("click");
		form_modal_renegociacao_titulo_deletar.find("#btn-delete").on("click", function(event) {
			var $this = $(this);
			$.ajax({
				url: "{{ route('renegociacao_titulo.deletar_renegociacao') }}",
				dataType: 'json',
				data: {_token: "{{ csrf_token() }}", id: '{{ $id }}'},
				method: 'POST',
				success: function(callback){
					if(callback.status === "success"){
						$($this).parents(".modal").modal("hide");
                        filterAjax();
						message("Atenção", "Renegociação excluida com sucesso!");
					} else {
						message("Atenção", callback.message);
					}
				},
			});
		});
	});
</script>
@endsection