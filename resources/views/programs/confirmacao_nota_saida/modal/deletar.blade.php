@extends('layouts.page-dialog')

@section('content')
<div class="row">
	<div class="col-sm-12">
		<b>Deseja realmente excluir esta confirmação de Saída de Nota?</b>
	</div>
</div>
<br>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Estabelecimento</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['estabelecimento']) !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Nota Fiscal</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['nota_saida']) !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>CNPJ</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['cnpj']) !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Cliente</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['cliente']) !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Data Emissão</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['data_emissao']) !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Data Saída</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['data_saida']) !!}</div>
</div>
<br>
<div class="content-buttons float-right">
    <button name="btn-cancel" id="btn-cancel-delete" class="btn btn-primary text-right">Cancelar</button>
    <button name="btn-delete" id="btn-delete" class="btn btn-danger text-right">Excluir</button>
</div>
<script type="text/javascript">
	$(document).ready(function($) {
		$(document).find("#btn-cancel-delete").off("click");
		$(document).find("#btn-cancel-delete").on("click", function(event) {
			$(this).parents(".modal").modal("hide");
		});
		$(document).find("#btn-delete").off("click");
		$(document).find("#btn-delete").on("click", function(event) {
			var $this = $(this);
			$.ajax({
				url: "{{ route('confirmacao_saida_nota.deletar') }}",
				dataType: 'json',
				data: {_token: "{{ csrf_token() }}", id: '{{ $dados['id'] }}'},
				method: 'POST',
				success: function(callback){
					if(callback.status === "success"){
						$($this).parents(".modal").modal("hide");
                        filterAjax($(document).find("#form_filter").serialize());
						message("Atenção", "Dado excluido com sucesso!");
					} else {
						message("Atenção", callback.message);
					}
				},
			});
		});
	});
</script>
@endsection