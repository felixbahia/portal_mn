@extends('layouts.page-dialog')

@section('content')
<div class="row">
	<div class="col-sm-12">
		<b>Deseja realmente excluir este Crédito/Débito?</b>
	</div>
</div>
<br>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Fornecedor</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['fornecedor']) !!}</div>
</div>
<br>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Saldo</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['saldo']) !!}</div>
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
				url: "{{ route('importacao.fornecedor_credito_debito.deletar') }}",
				dataType: 'json',
				data: {_token: "{{ csrf_token() }}", id: '{{ $dados['id'] }}'},
				method: 'POST',
				success: function(callback){
                    $($this).parents(".modal").modal("hide");
                    filterAjax($(document).find("#form_filter").serialize());
                    message("Atenção", "Crédito/Débito excluido com sucesso!");
				},
                error: function(callback){
                    message("Atenção", callback.message);
                }
			});
		});
	});
</script>
@endsection