@extends('layouts.page-dialog')

@section('content')
<div class="row">
	<div class="col-sm-12">
		<b>Deseja realmente cancelar o pedido {{ $pedido_id }}?</b>
	</div>
</div>
<br>
<div class="content-buttons float-right">
    <button name="btn-cancel" id="btn-cancel-delete" class="btn btn-primary text-right">Cancelar</button>
    <button name="btn-delete" id="btn-delete" class="btn btn-danger text-right">OK</button>
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
				url: "{{ route('aprovacao_pedido_futuro.deletar') }}",
				dataType: 'json',
				data: {
					_token: "{{ csrf_token() }}", 
					aprovacao_id: '{{ $aprovacao_id }}',
					pedido_id: '{{ $pedido_id }}',
					estabelecimento: '{{ $estabelecimento }}',
					cliente_nome: '{{ $cliente_nome }}'
				},
				method: 'POST',
				success: function(callback){
					if(callback.status === "success"){
						$($this).parents(".modal").modal("hide");
						message("Atenção", "Pedido cancelado com sucesso!");
					} else {
						message("Atenção", callback.message);
					}
				},
			});
		});
    });
</script>
@endsection