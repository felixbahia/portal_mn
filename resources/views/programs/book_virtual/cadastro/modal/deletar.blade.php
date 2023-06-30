@extends('layouts.page-dialog')

@section('content') 
<div class="row">
	<div class="col-sm-12">
		<b>Deseja realmente excluir este book?</b>
	</div>
</div>
<br>
<div class="row border-bottom">
    <div class='col-sm-6'><b>Book Virtual</b></div>
    <div class='col-sm-6'>{!! $book !!}</div>
</div>
@foreach($produtos as $produto)
    <div class="row border-bottom">
        <div class='col-sm-6'><b>Produto</b></div>
        <div class='col-sm-6'>{!! $produto['produto']." - ".$produto['descricao']!!}</div>
    </div>
@endforeach
<br>
<div class="content-buttons float-right">
    <button name="btn-cancel" id="btn-cancel-delete" class="btn btn-primary text-right">Cancelar</button>
    <button name="btn-delete" id="btn-delete" class="btn btn-danger text-right">Excluir</button>
</div>
<script type="text/javascript">
	$(document).ready(function($) {
		if($(document).find('#preco_dolar').val() == ''){
			$(document).find('.preco_dolar').hide();
		}
		$(document).find("#btn-cancel-delete").off("click");
		$(document).find("#btn-cancel-delete").on("click", function(event) {
			var $this = $(this);
			$($this).parents(".modal").modal("hide");
		});
		$(document).find("#btn-delete").off("click");
		$(document).find("#btn-delete").on("click", function(event) {
			var $this = $(this);
			$.ajax({
				url: "{{ route('book_virtual.cadastro.deletar') }}",
				dataType: 'json',
				data: {_token: "{{ csrf_token() }}", id: '{{ $book }}'},
				method: 'POST',
				success: function(callback){
					if(callback.status === "success"){
                        $($this).parents(".modal").modal("hide");
                        filterClear();
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