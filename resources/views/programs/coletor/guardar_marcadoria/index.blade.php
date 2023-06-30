@extends('layouts.app-coletor')
@section('content')
<form action="#" name="form_guardar_mercadoria" id="form_guardar_mercadoria" onsubmit="return false">
    @csrf
	<div class="form-group">
		<label for="endereco">Endereço</label>
		<input type="text" name="endereco" id="endereco" value="" autofocus />
	</div>
	<div class="form-group">
		<label for="peca">Peça</label>
		<input type="text" name="peca" id="peca" value="">
	</div>
	<div class="form-group">
		<label for="ultima_peca">Ultima Peça Guardada</label>
		<input type="text" name="ultima_peca" id="ultima_peca" value="" readonly="readonly" disabled="disabled">
	</div>
</form>
@endsection
@section('script-footer')
<script type="text/javascript">
$(document).find('input').on("keyup", function(event){
	if(event.which == 27){
		if($(document).find('#peca').val() != ''){
			$(document).find('#peca').val('');
			$(document).find('#peca').focus();
		}else if($(document).find('#endereco').val() != ''){
			$(document).find('#endereco').val('');
			$(document).find('#endereco').focus();
		}else{
			if($this.val() === ''){
				window.location.href = "{{ route('home_coletor') }}";
				return false;
			}
		}
	}
});
$(document).ready(function($) {
	$(document).find('#endereco').focus();
	$(document).find("input").on("keydown", function(event){
		if(event.which == 17){
			$(this).val('');
		}else if(event.which == 9){
			var campos = $(document).find("input:visible");
			var indice = campos.index(event.target) + 1;
			if($(campos[indice]).attr('name') === 'ultima_peca'){
				if($(this).val() === ''){
					$(document).find('#endereco').focus();
					$(document).find('#endereco').val("");
					$(document).find('#peca').val("");
				}else{
					$(document).find('#peca').val("");
					$(document).find('#peca').focus();
				}
				return false;
			}
			if($(campos[indice]).attr('name') === 'peca'){
				if($(this).val() === ''){
					window.location.href = "{{ route('home_coletor') }}";
					return false;
				}
				$(campos[indice]).val('');
			}
		}
	});

	$(document).find("input").on("keyup", function(event){
		var $this = $(this);
		if(event.which == 13 || event.which == 17){
			var campos = $(document).find("input:visible");
			var indice = campos.index(event.target) + 1;
			var seletor = $(campos[indice]).focus();
			if($(campos[indice]).attr('name') === 'ultima_peca'){
				if($this.val() === ''){
					$(document).find('#endereco').focus();
					$(document).find('#endereco').val("");
					$(document).find('#peca').val("");
					return false;
				}
				guardarMercadoria();
			}
			if($(campos[indice]).attr('name') === 'peca'){
				if($this.val() === ''){
					window.location.href = "{{ route('home_coletor') }}";
					return false;
				}
				$(campos[indice]).val('');
			}
			if (seletor.length == 0) {
				event.target.focus();
			}
		}
	});
});
function guardarMercadoria(){
	var $form = $("#form_guardar_mercadoria");
    $.ajax({
        url: '{{ route('coletor.guardar_mercadoria.save') }}',
        type: 'POST',
        dataType: 'json',
        data: $form.serialize(),
        success: function(callback){
        	if(callback.status == 'error'){
        		alert(callback.message);
        	}else{
        		$(document).find("#ultima_peca").val($(document).find("#peca").val());
        		$(document).find("#peca").val('');
        		$(document).find("#peca").focus();
        	}
        },
        error: function(callback){
            if(callback.responseJSON){
            	alert(callback.responseJSON.message);
            }
        }
    }).always(function() {
        hide_loader();
    });
}
</script>
@endsection