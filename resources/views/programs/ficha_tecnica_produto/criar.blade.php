@extends('layouts.page-dialog')

@section('content')
<div class="alert alert-danger" role="alert" id='erro'>
  <p>Erros no cadastro deste produto.<br>
  <span></span></p> 
</div>

<form id='nova_ficha' action="{{ route('ficha_tecnica.cadastrar') }}" onsubmit="return false;">
{{ Form::token() }}
	<div class="row">
		<div class="col-lg-4">
			{{ Form::label('estabelecimento', 'Estabelecimento') }}
		    {{ Form::select('estabelecimento', returnEmpresasPrologusView(), '', ['id' => 'estabelecimento-modal', 'class' => 'form-control', 'placeholder' => 'Estabelecimento']) }}
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			{{ Form::hidden('produto', '', ['id' => 'produto', 'class' => 'chave'])}}
			{{ Form::label('produto', 'Produto') }}
		    <div class="input-group">
			    {{ Form::text('produto-descricao', '', ['id' => 'produto-descricao', 'class' => 'form-control descricao']) }}
				<span class="input-group-addon border rounded-right" id="bt-search" data-route="{{ route("produtos_nasajon.modal.busca") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
			</div>	
		</div>
	</div>

	<div id='novosInsumos' class='col-lg-12 my-4'>
	</div>

	<div class="row my-4" id="botao-criar-div">
		<div class="col-lg-12">
			<input type="button" value="Criar novo insumo" id="novo-insumo" onclick='novoInsumo()'>
			{{ Form::submit('Enviar', ['class' => "btn btn-primary float-right", 'id' => 'enviar'])}}
		</div>
	</div>


	<div class='row insumo d-none modelo'>
		<div class='col-lg-7 form-group'> 
			{{ Form::hidden('insumo', '', ['class' => 'chave'])}}
			{{ Form::label('insumo', 'Insumo') }}
		    <div class="input-group">
				{{ Form::text('insumo_descricao', '', ['class' => 'form-control descricao']) }}
				<span class="input-group-addon border rounded-right" id="bt-search" data-route="{{ route("produtos_nasajon.modal.busca") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
			</div>
		</div>

		<div class="col-lg-3">
			{{ Form::label('quantidade', 'Quantidade') }}
		    <div class="input-group">
				{{ Form::text('quantidade', '', ['class' => 'form-control quantidade']) }}
			</div>
		</div>

		<div class="col-lg-2">
			<label for=""> &nbsp;</label>
			<button class='btn btn-default btn-lg' onclick="removeInsumo($(this))">
				<i id="bt-delete" class="bt-delete"></i>
			</button>
		</div>

	</div>

</form>

<script>
	$(document).ready(function(){
		novoInsumo();

		$(document).find("#produto-descricao").autocomplete(optionsAutoCompleteProdutoDescricao());
		
		$(document).find("#enviar").off('click');
		$(document).find("#enviar").on('click', function(event) {
			event.stopPropagation();
			ajaxForm("#criar");
		});

        $("#erro").hide();

	});

	function novoInsumo(){
		elemento = $(document).find('.modelo').clone().appendTo($("#novosInsumos")).removeClass('d-none modelo');
	
		elemento.find('.descricao').autocomplete(optionsAutoCompleteInsumoDescricao());
	}

	function removeInsumo($elemento){
		$elemento.parent().parent().remove();
	}

    function optionsAutoCompleteProdutoDescricao(){
        $(document).find("#criar").find(".error-message").remove();

        return {
            source: function (request, response) {

                request._token = "{{ csrf_token() }}";
                request.exclude = 'ficha_tecnica_produto';
                $.post("{{ route('produtos_nasajon.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('.modal').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum produto encontrado');
                    event.stopPropagation();
                    event.target.focus();
                    return false;
                }
            },
            select: function( event, ui ) {
            	$(event.target).parent().parent().find('.chave').val(ui.item.value)
            	$(event.target).val(ui.item.label)
                event.stopPropagation();
            	return false;
            }
        };
    }

    function optionsAutoCompleteInsumoDescricao(){
        $(document).find("#criar").find(".error-message").remove();

        return {
            source: function (request, response) {

                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produtos_nasajon.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('.modal').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum produto encontrado');
                    event.stopPropagation();
                    event.target.focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                $(event.target).parent().parent().find('.chave').val(ui.item.value)
                $(event.target).val(ui.item.label)
                event.stopPropagation();
                return false;
            }
        };
    }

    function ajaxForm($modal){

        var form = $('#nova_ficha');
        var url = form.attr("action");
        $(document).find('#erro').find('span').html('');

        form_data = {
        	_token: '{{ csrf_token() }}',
        	estabelecimento: $(document).find('#estabelecimento-modal').val(),
        	produto: $(document).find("#produto").val(),
        };

        insumo_array = new Array();

        $(document).find(".insumo").not('.modelo').each(function(index, el) {
        	temp_array = {
        		produto: $(this).find(".chave").val(),
        		quantidade: $(this).find(".quantidade").val(),
        	};

        	insumo_array.push(temp_array);
        	
        });

        form_data.insumo = insumo_array;

        $.ajax({
            url: url,
            dataType: 'json',
            data: form_data,
            method: 'POST',
            success: function(data){
                $($modal).modal('hide');
                filterAjax($(document).find("#form_filter").serialize());
                message("Atenção", "Dados salvos com sucesso!");
            },
            error: function(data){
                $("#erro").show();
                var errors = data.responseJSON.errors;

                for(var field in errors){
                    $(document).find('#erro').find('span').append(field + " - " + errors[field] + "<br>")
                     
                }
            }
        });
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

</script>
@endsection