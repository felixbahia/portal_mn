@extends('layouts.page-dialog')

@section('content')
@if(empty($quantidade))
<div class="row">
	<div class="col-sm-12">
		<b>Deseja realmente excluir este Nota da Exceção?</b>
	</div>
</div>
<br>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Estabelecimento</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['estabelecimento']) !!}</div>
</div>
<br>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Cliente</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['cliente']) !!}</div>
</div>
<br>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Nota</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['nota']) !!}</div>
</div>
<br>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Data Emissão</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['data_emissao']) !!}</div>
</div>
<br>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Equipe</b></div>
	<div class='col-sm-6'>{!! nl2br($dados['equipe']) !!}</div>
</div>
<br>
<div class="content-buttons float-right">
    <button name="btn-cancel" id="btn-cancel-delete" class="btn btn-primary text-right">Cancelar</button>
    <button name="btn-delete" id="btn-delete" class="btn btn-danger text-right">Excluir</button>
</div>
@else
<div class="row">
	<div class="col-sm-12">
		<b>Há {{ $quantidade }} produto(s) com esse exceção. Não sendo possível excluir o grupo.</b>
	</div>
</div>
<br>
<div class="content-buttons float-right">
	<button name="btn-cancel" id="btn-cancel-delete" class="btn btn-primary text-right">OK</button>
</div>
@endif
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
				url: "{{ route('mapa_venda.excecao.deletar') }}",
				dataType: 'json',
				data: {_token: "{{ csrf_token() }}", id: '{{ $dados['id'] }}'},
				method: 'POST',
				success: function(callback){
					if(callback.status === "success"){
						$($this).parents(".modal").modal("hide");
                        filterAjax($(document).find("#form_filter").serialize());
						message("Atenção", "Exceção excluido com sucesso!");
					} else {
						message("Atenção", callback.message);
					}
				},
			});
		});
	});
</script>
@endsection