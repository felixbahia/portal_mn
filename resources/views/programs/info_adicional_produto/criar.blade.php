@extends('layouts.page-dialog')

@section('content')

<div class="container">

	<form action="{{ route("analise.produto.infoadicional.salvar_edicao") }}" method="post" id="cadInfoAdicional" name="cadInfoAdicional" onsubmit="return false">

	    @csrf
	    <div class="form-row">
			<div class="form-group col-sm-4">
				{{ Form::label('cod_produto', 'Código do produto', []) }}
				<div class="input-group" id="cod_produto_group">
		        	{{ Form::text('cod_produto', '', ['id' => 'cod_produto_modal', 'class' => 'input-search-bt form-control', 'placeholder' => 'Código', "maxlength" => "250", "onchange" => "retornaInfoProduto($(this).val());"]) }}
	        		<span class="input-group-addon border rounded-right" id="bt-search-produtos"><i class="bt-view m-2"></i></span>
				</div>
			</div>

			<div class="col-md-8">
				{{ Form::label('descricao', 'Descricao', []) }}
		        {{ Form::text('descricao', '', ['id' => 'descricao_modal', 'class' => 'input-search-bt form-control', 'placeholder' => 'Nome do produto', "maxlength" => "250", 'disabled']) }}
			</div>
		</div>

		<div class="form-row">
			<div class="col-md-12">
				{{ Form::label('procedencia', 'Procedência') }}
				{{ Form::text('procedencia', '', ['id' => 'procedencia_modal', 'class' => 'form-control', 'disabled']) }}
			</div>
		</div>

		<div class="form-row">
				
			<div class="col-md-6">
				{{ Form::label('grupo', 'Grupo') }}
				{{ Form::text('grupo', '', array('id' => 'grupo_modal', 'class' => 'form-control')) }}
			</div>

			<div class="col-md-6">
				{{ Form::label('subgrupo', 'Subgrupo') }}
				{{ Form::text('subgrupo', '', array('id' => 'subgrupo_modal', 'class' => 'form-control')) }}
			</div>
	

		</div>


	    <div class="form-row">
	    	<div class="col-md-6">
		        {{ Form::label('marca', 'Marca') }}
		        {{ Form::text('marca', '', ['id' => 'marca_modal', 'class' => 'form-control']) }}	
	    	</div>

	    	<div class="col-md-6">
		        {{ Form::label('linha', 'Linha') }}
		        {{ Form::text('linha', '', ['id' => 'linha_modal', 'class' => 'form-control']) }}
			</div>

		</div>

	    <div class="form-row">

	    	<div class="col-md-4">
		        {{ Form::label('largura', 'Largura') }}
		        {{ Form::text('largura', '', ['id' => 'largura', 'class' => 'form-control', 'maxlength' => '250', 'readonly']) }}	    		
	    	</div>
	    	<div class="col-md-4">
		        {{ Form::label('gramatura_gml', 'Gramatura g/ml') }}
		        {{ Form::text('gramatura_gml', '', ['id' => 'gramatura_gml', 'class' => 'form-control', 'maxlength' => '250', 'readonly']) }}
		    </div>
			<div class="col-md-4">
		        {{ Form::label('gramatura_gm2', 'Gramatura g/m2') }}
		        {{ Form::text('gramatura_gm2', '', ['id' => 'gramatura_gm2', 'class' => 'form-control', 'maxlength' => '250', 'readonly']) }}
		    </div>
		</div>

		<div class="form-row">
		<div class="form-group col-sm-4"> 
				{{ Form::label('gramatura_tipo', 'Tipo Gramatura', []) }}
				{{ Form::text('gramatura_tipo', '', ['id' => 'gramatura_tipo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Tipo Gramatura', 'readonly']) }}
			</div>
			<div class="col-md-4">
		        {{ Form::label('rendimento', 'Rendimento') }}
		        {{ Form::text('rendimento', '', ['id' => 'rendimento', 'class' => 'form-control', 'maxlength' => '250', 'readonly']) }}
		    </div>
			<div class="col-md-4">
				{{ Form::label('tipo_genero', 'Gênero') }}
				{{ Form::text('tipo_genero', '', ['id' => 'tipo_genero', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Gênero', 'readonly']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-4">
				{{ Form::label('segmento', 'Segmento') }}
				{{ Form::text('segmento', '', ['id' => 'segmento', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Segmento', 'readonly']) }}
			</div>
			<div class="col-md-4">
				{{ Form::label('familia', 'Acabamento') }}
				{{ Form::text('familia', '', ['id' => 'familia', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Acabamento', 'readonly']) }}
			</div>
			<div class="col-md-4">
				{{ Form::label('padrao_codigo', 'Padrão do código') }}
				{{ Form::text('padrao_codigo', '', ['data-old' => '', 'id' => 'padrao_codigo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Padrão do código', 'readonly']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-6"> 
				{{ Form::label('caracteristicas', 'Características', []) }}
				{{ Form::text('caracteristicas', '', ['id' => 'caracteristicas', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Características', 'maxlength' => '250', 'readonly']) }}
			</div>
			<div class="col-md-6"> 
				{{ Form::label('pecas', 'Peças de', []) }}
				{{ Form::text('pecas', '', ['id' => 'pecas', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Peças de', 'maxlength' => '250', 'readonly']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-6"> 
				{{ Form::label('origem', 'País de Origem', []) }}
				{{ Form::text('origem', '', ['id' => 'origem', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'País de Origem', 'maxlength' => '250', 'readonly']) }}
			</div>
			<div class="form-group col-sm-6">
				{{ Form::label('composicao', 'Composição Predominante') }}
				{{ Form::text('composicao', '', ['id' => 'composicao', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Composição Predominante', 'readonly']) }}
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-6">
				{{ Form::label('construcao', 'Construção') }}
				{{ Form::text('construcao', '', ['id' => 'construcao', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Construção', 'readonly']) }}
			</div>
			<div class="form-group col-sm-6">
				{{ Form::label('sazonal', 'Sazonalidade') }}
				{{ Form::text('sazonal', '', ['id' => 'sazonal', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Sazonalidade', 'readonly']) }}
			</div>
		</div>
		
		<div class="form-row">

			<div class="col-md-12 mt-4">

				{{ Form::submit('Salvar', array('id' => 'salvar', 'class' => 'btn btn-primary float-right')) }}
			
			</div>
		
		</div>

	</form>

</div>


<script>

	$(document).ready(function(){
		$(document).find('#cadInfoAdicional').find("#marca_modal").autocomplete(optionsAutoCompleteMarca());
		$(document).find('#cadInfoAdicional').find("#linha_modal").autocomplete(optionsAutoCompleteLinha());
		$(document).find('#cadInfoAdicional').find("#grupo_modal").autocomplete(optionsAutoCompleteGrupo());
		$(document).find('#cadInfoAdicional').find("#subgrupo_modal").autocomplete(optionsAutoCompleteSubgrupo());

		$(document).find('#cadInfoAdicional').find("#bt-search-produtos").on("click", function(){
            showModalProdutoNasajonAdd("Lista de Produtos");
        });
	})

	function optionsAutoCompleteGrupo(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.grupo.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
                event.stopPropagation();
				$(document).find("#grupo_modal").val(ui.item.value);
                buscaGrupo();
            }
		};
	}

	function optionsAutoCompleteSubgrupo(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.subgrupo.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

	function optionsAutoCompleteLinha(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.linha.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

	function optionsAutoCompleteMarca(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.marca.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

	function retornaInfoProduto($codpro){

		form = $(document).find("#cadInfoAdicional");

		form.find("#descricao_modal").val('');
		form.find("#procedencia_modal").val('');

        form.find('.error-message').remove();

		$.ajax({
			url: '{{ route('analise.produto.infoadicional.retornaDetalhesProduto') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				cod_prod: $codpro
			},
			success: function(data){
				form.find("#descricao_modal").val(data.nome);
				form.find("#procedencia_modal").val(data.procedencia);
			},
			error: function(data){
				form.find("#cod_produto_modal").val('');
                var errors = data.responseJSON.errors;
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field]);
                }
			}
		});
	}

	function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

	function showModalProdutoNasajonAdd(){
        $.ajax({
            url: '{{ route('produtos_nasajon.modal.busca') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
                produtos_table.on('draw', function () {

                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                        returnDadosProdutoAdd($(this));
                    });

                });
            }
        });
	}
	
	function returnDadosProdutoAdd($dados){
		form_modal_add = $(document).find("#cadInfoAdicional");;
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){ 
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        form_modal_add.find('#codigo_produto').val($dados.find("td").eq(1).text());
        pesquisaProdutoCodigoAdd(form_modal_add);
    };

	function buscaGrupo(){
		form = $(document).find("#cadInfoAdicional");
		form.find('.error-message').remove();

		$.ajax({
			url: '{{ route("analise.produto.infoadicional.busca_grupo") }}',
			type: 'POST',
			data:form.serialize(),
			success: function(data){
				form.find("#largura").val(data.response.largura);
				form.find("#gramatura_gml").val(data.response.gramatura_gml);
				form.find("#gramatura_gm2").val(data.response.gramatura_gm2);
				form.find("#gramatura_tipo").val(data.response.gramatura_tipo);
				form.find("#rendimento").val(data.response.rendimento);
				form.find("#tipo_genero").val(data.response.tipo_genero);
				form.find("#segmento").val(data.response.segmento);
				form.find("#familia").val(data.response.familia);
				form.find("#padrao_codigo").val(data.response.padrao_codigo);
				form.find("#caracteristicas").val(data.response.caracteristicas);
				form.find("#pecas").val(data.response.pecas);
				form.find("#origem").val(data.response.origem);
				form.find("#composicao").val(data.response.composicao);
				form.find("#construcao").val(data.response.construcaos);
				form.find("#sazonal").val(data.response.sazonalidade);
			},
			error: function(data){
				var errors = data.responseJSON.errors;
				for(var field in errors){
					showErrorsInputs(form, field, errors[field]);
				}
			}
		});
	}
</script>

@endsection