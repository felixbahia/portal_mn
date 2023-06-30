@extends('layouts.page-dialog')

@section('content')
<div class="row">
	<div class="col-sm-12">
		<b>Deseja realmente excluir este lançamento?</b>
	</div>
</div>
<br>
<div class="row border-bottom">
    <div class='col-sm-6'><b>Vendedor</b></div>
    <div class='col-sm-6'>{!! $dados['vendedor'] !!}</div>
</div>
<div class="row border-bottom">
    <div class='col-sm-6'><b>Documento</b></div>
    <div class='col-sm-6'>{!! $dados['documento'] !!}</div>
</div>
<div class="row border-bottom">
        <div class='col-sm-6'><b>Déb./Créd.</b></div>
        <div class='col-sm-6'>{!! $dados['tipo'] !!}</div>
</div>
<div class="row border-bottom">
        <div class='col-sm-6'><b>Motivo</b></div>
        <div class='col-sm-6'>{!! nl2br($dados['motivo']) !!}</div>
</div>
<div class="row border-bottom">
    <div class='col-sm-6'><b>Data</b></div>
    <div class='col-sm-6'>{!! $dados['data'] !!}</div>
</div>

<div class="row border-bottom">
    <div class='col-sm-6'><b>Valor</b></div>
    <div class='col-sm-6'>{!! $dados['valor'] !!}</div>
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
				url: "{{ route('lancamento_deb_cred_vendedor.deletar') }}",
				dataType: 'json',
				data: {_token: "{{ csrf_token() }}", id: '{{ $dados['id'] }}'},
				method: 'POST',
				success: function(callback){
					if(callback.status === "success"){
						$($this).parents(".modal").modal("hide");
                        filterAjax($(document).find("#form_filter").serialize());
						message("Atenção", "Lançamento excluido com sucesso!");
					} else {
						message("Atenção", callback.message);
					}
				},
			});
		});
	});
</script>
@endsection