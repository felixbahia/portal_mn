@extends('layouts.page-dialog')

@section('content')

<div class="container">

	<form action="{{ route("aliquota_preco.editar") }}" method="post" id="cadMargem" name="cadMargem" onsubmit="return false">

	    @csrf

	    {{ Form::hidden('id', $aliquota['id'])}}

	    <div class="form-row">
	    	<div class="col-md-8">
	    		<div class="row">
		    		<div class="col-md-6">
				        {{ Form::label('origem_modal', 'Origem') }}
				        {{ Form::select('origem', $origem, $aliquota['origem'], ['id' => 'origem_modal', 'class' => 'form-control', 'placeholder' => 'Origem']) }}	
			    	</div>

			    	<div class="col-md-6">
				        {{ Form::label('estado_modal', 'Estado') }}
				        {{ Form::select('estado', $estados, $aliquota['estado'], ['id' => 'estado_modal', 'class' => 'form-control', 'placeholder' => 'Estado']) }}
			    	</div>
			    </div>

	    		<div class="row">

			    	<div class="col-md-6">
				        {{ Form::label('frete_adicional_modal', 'Frete adicional') }}
				        {{ Form::text('frete_adicional', parserValor($aliquota['frete_adicional']), array('id' => 'frete_adicional_modal', 'class' => 'form-control')) }}
				    </div>
				</div>

				<div class="row mt-4">
					<p class='col-md-12'><b>ICMS para venda</b></p>
				</div>

				<div class='row'>
			    	<div class="col-md-6">
				        {{ Form::label('icms_venda', 'Pessoa jurídica') }}
				        {{ Form::text('icms_venda', parserValor($aliquota['icms_venda']), array('id' => 'icms_venda', 'class' => 'form-control')) }}
				    </div>
			    	<div class="col-md-6">
				        {{ Form::label('icms_venda_cliente_isento', 'Cliente Isento') }}
				        {{ Form::text('icms_venda_cliente_isento', parserValor($aliquota['icms_venda_cliente_isento']), array('id' => 'icms_venda_cliente_isento', 'class' => 'form-control')) }}
				    </div>
			    </div>
			</div>

	    	<div class="col-md-3 ml-4 mt-3">
		        {{ Form::checkbox('internacional', '1', $aliquota['internacional'], array('id' => 'internacional', 'class' => 'form-check-input')) }}
		        {{ Form::label('internacional', 'Aliquota para importados?', ['class' => 'form-check-label']) }}
		    </div>

	    </div>


		<div class="form-row">

			<div class="col-md-12 mt-4">

				{{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}
			
			</div>
		
		</div>

	</form>

</div>

<script>

	$(document).ready(function(){
		ajaxForm('#editar');
	});

	$(document).find('#frete_adicional_modal').maskMoney({thousands:'.', decimal:','});
	$(document).find('#icms_venda').maskMoney({thousands:'.', decimal:','});
	$(document).find('#icms_venda_cliente_isento').maskMoney({thousands:'.', decimal:','});

    function ajaxForm($model){
        $($model).find('[type="submit"]').on("click", function(event){
            event.stopPropagation();
            var form = $(this).parents('form');
            
            var form_data = form.serialize();
            var url = form.attr("action");

            $.ajax({
                url: url,
                dataType: 'json',
                data: form_data,
                method: 'POST',
                success: function(data){
                    $($model).modal('hide');
                    filterAjax($("#form_filter").serialize());
                    message("Atenção", "Dados salvos com sucesso!");
                },
                error: function(data){
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                    
                    $('#aliquota_modal').maskMoney({thousands:'.', decimal:','});

                }
            });
        });
    }
</script>

@endsection