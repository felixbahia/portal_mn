@extends('layouts.page-dialog')

@section('content')
<div class="row">
	<div class="col-sm-12">
		<b>Deseja realmente excluir este Tecido Estampado?</b>
	</div>
</div>
<br>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Código Tecido Estampado</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['codigo_produto_tecido_estampado']) !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Descrição Tecido Estampado</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['descricao_tecido_estampado']) !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Código Tecido Base</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['codigo_produto_tecido_base']) !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Descrição Tecido Base</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['descricao_tecido_base']) !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Código Desenho</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['codigo_produto_desenho']) !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Descrição Desenho</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['descricao_desenho']) !!}</div>
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
				url: "{{ route('produto.tecido_estampado.deletar') }}",
				dataType: 'json',
				data: {_token: "{{ csrf_token() }}", id: '{{ $dados['id'] }}'},
				method: 'POST',
				success: function(callback){
					if(callback.status === "success"){
						$($this).parents(".modal").modal("hide");
                        filterAjax($(document).find("#form_filter").serialize());
						message("Atenção", "Tecido Base excluído com sucesso!");
					} else {
						message("Atenção", callback.message);
					}
				},
			});
		});
	});
</script>
@endsection