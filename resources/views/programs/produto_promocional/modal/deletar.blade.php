@extends('layouts.page-dialog')

@section('content')
<div class="row">
	<div class="col-sm-12">
		<b>Deseja realmente excluir esta Promoção?</b>
	</div>
</div>
<br>
@if($dados['tipo_promocional'] === 'pedido')
<div class="row border-bottom">
	<div class='col-sm-6'><b>Tipo Promocional</b></div>
	<div class='col-sm-6'>Pedido</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Grupo</b></div>
	<div class='col-sm-6'>{!! $dados['grupo'] !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Código / Nome Produto</b></div>
	<div class='col-sm-6'>{!! $dados['descricao'] !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Preço Real</b></div>
	<div class='col-sm-6'>{!! $dados['preco_real'] !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Estabelecimento</b></div>
	<div class='col-sm-6'>{!! $dados['estabelecimento'] !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Cliente</b></div>
	<div class='col-sm-6'>{!! $dados['cliente'] !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Vendedor</b></div>
	<div class='col-sm-6'>{!! $dados['vendedor'] !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Data Expiração</b></div>
	<div class='col-sm-6'>{!! $dados['data_expiracao'] !!}</div>
</div>
@elseif($dados['tipo_promocional'] === 'projeto')
<div class="row border-bottom">
	<div class='col-sm-6'><b>Tipo Promocional</b></div>
	<div class='col-sm-6'>Projeto</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Cliente</b></div>
	<div class='col-sm-6'>{!! $dados['cliente'] !!}</div>
</div>
<div class="row border-bottom">
	<div class='col-sm-6'><b>Vendedor</b></div>
	<div class='col-sm-6'>{!! $dados['vendedor'] !!}</div>
</div>
@endif
<br>
<div class="content-buttons float-right">
    <button name="btn-cancel" id="btn-cancel-delete" class="btn btn-primary text-right">Cancelar</button>
    <button name="btn-delete" id="btn-delete" class="btn btn-danger text-right">Excluir</button>
</div>
<script type="text/javascript">
	$(document).ready(function($) {
		$(document).find("#btn-cancel-delete").off("click");
		$(document).find("#btn-cancel-delete").on("click", function(event) {
			var $this = $(this);
			$($this).parents(".modal").modal("hide");
		});
		$(document).find("#btn-delete").off("click");
		$(document).find("#btn-delete").on("click", function(event) {
			var $this = $(this);
			$.ajax({
				url: "{{ route('listaprecosprodutospromocionais.excluir') }}",
				dataType: 'json',
				data: {_token: "{{ csrf_token() }}", id: '{{ $dados['id'] }}'},
				method: 'POST',
				success: function(callback){
					if(callback.status === "success"){
						$($this).parents(".modal").modal("hide");
						filterAjax($("#form_filter").serialize());
					} else {
						message("Atenção", callback.message);
					}
				},
			});
		});
	});
</script>
@endsection