@extends('layouts.page-dialog')

@section('content')
<div class="row">
	<div class="col-sm-12">
		<b>Deseja realmente excluir este Tipo de Serviço?</b>
	</div>
</div>
<br>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Tipo de Serviço</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['tipo_de_servico']) !!}</div>
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
				url: "{{ route('tipo_de_servico.deletar') }}",
				dataType: 'json',
				data: {_token: "{{ csrf_token() }}", id: '{{ $dados['id'] }}'},
				method: 'POST',
				success: function(callback){
					if(callback.status === "success"){
						$($this).parents(".modal").modal("hide");
                        filterAjax($(document).find("#form_filter").serialize());
						message("Atenção", "Tipo de Serviço excluido com sucesso!");
					} else {
						message("Atenção", callback.message);
					}
				},
			});
		});
	});
</script>
@endsection