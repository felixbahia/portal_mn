@extends('layouts.page-dialog')
@section('content')

<form action="{{ route("produto.novo.adicionar") }}" method="post" id="frm_produto_novo_add" name="frm_produto_novo_add" onsubmit="return false">
	@csrf
	<div class="form-row">
		<div class="form-group col-sm-12">
			{{ Form::label('cod_produto', 'Código do produto', []) }}
			<div class="input-group" id="cod_produto_group">
				{{ Form::text('cod_produto', '', ['id' => 'cod_produto_modal', 'class' => 'input-search-bt form-control', 'placeholder' => 'Código', "maxlength" => "60", "onchange" => "retornaInfoProduto($(this).val());"]) }}
				<span class="input-group-addon border rounded-right" id="bt-buscar-produto"><i class="bt-view m-2"></i></span>
			</div>
		</div>
	</div>
	<div class="form-row">
		<div class="col-md-10">
			{{ Form::label('descricao', 'Descricao', []) }}
			{{ Form::text('descricao', '', ['id' => 'descricao_modal', 'class' => 'input-search-bt form-control', 'placeholder' => 'Nome do produto', "maxlength" => "120"]) }}
		</div>
		<div class="col-md-2">
			{{ Form::label('preco_venda', 'Preço Venda', []) }}
			{{ Form::text('preco_venda', '', ['id' => 'preco_venda', 'class' => 'input-search-bt form-control text-right', 'placeholder' => 'Preço Venda', "maxlength" => "8"]) }}
		</div>
	</div>
	<div class="form-row">
		<div class="col-md-3">
			{{ Form::label('grupo', 'Grupo') }}
			{{ Form::text('grupo', '', ['id' => 'grupo_modal', 'class' => 'form-control', 'placeholder' => 'Grupo', 'maxlength' => '40']) }}
		</div>
		<div class="col-md-3">
			{{ Form::label('subgrupo', 'Subgrupo') }}
			{{ Form::text('subgrupo', '', ['id' => 'subgrupo_modal', 'class' => 'form-control', 'placeholder' => 'Subgrupo', 'maxlength' => '40']) }}
		</div>
		<div class="col-md-3">
			{{ Form::label('marca', 'Marca') }}
			{{ Form::text('marca', '', ['id' => 'marca_modal', 'class' => 'form-control', 'placeholder' => 'Marca', 'maxlength' => '40']) }}	
		</div>
		<div class="col-md-3">
			{{ Form::label('linha', 'Linha') }}
			{{ Form::text('linha', '', ['id' => 'linha_modal', 'class' => 'form-control', 'placeholder' => 'Linha', 'maxlength' => '40']) }}
		</div>
	</div>
	<div class="form-row">
		<div class="col-md-4">
			{{ Form::label('unidade', 'Unidade de Medida Padrão') }}
			{{ Form::select('unidade', $unidades, '', ['id' => 'unidade', 'class' => 'form-control']) }}	    		
		</div>
		<div class="col-md-4">
			{{ Form::label('composicao', 'Composição') }}
			{{ Form::text('composicao', '', ['id' => 'composicao', 'class' => 'form-control', 'placeholder' => 'Composição', 'maxlength' => '250']) }}
		</div>
		<div class="col-md-4">
			{{ Form::label('composicao', 'Composição Predominante') }}
			{{ Form::select('composicao_predominante', $composicao, '', ['id' => 'composicao_predominante', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Selecione a Composição Predominante', 'readonly']) }}
		</div>
	</div>
	<div class="form-row">
		<div class="col-md-3">
			{{ Form::label('largura', 'Largura') }}
			{{ Form::text('largura', '', ['id' => 'largura', 'class' => 'form-control', 'placeholder' => 'Largura', 'maxlength' => '250', 'readonly']) }}	    		
		</div>
		<div class="col-md-3">
			{{ Form::label('gramatura', 'Gramatura') }}
			{{ Form::text('gramatura', '', ['id' => 'gramatura', 'class' => 'form-control', 'placeholder' => 'Gramatura', 'maxlength' => '250', 'readonly']) }}
		</div>
		<div class="col-md-3"> 
			{{ Form::label('gramatura_tipo', 'Tipo Gramatura', []) }}
			{{ Form::select('gramatura_tipo', $tipo_gramatura, '', ['id' => 'gramatura_tipo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Tipo Gramatura', 'readonly']) }}
		</div>
		<div class="col-md-3">
			{{ Form::label('rendimento', 'Rendimento') }}
			{{ Form::text('rendimento', '', ['id' => 'rendimento', 'class' => 'form-control', 'placeholder' => 'Rendimento', 'maxlength' => '250', 'readonly']) }}
		</div>

	</div>
	<div class="form-row">
		<div class="col-md-4">
			{{ Form::label('peso', 'Peso Líquido') }}
			{{ Form::text('peso', '', ['id' => 'peso', 'class' => 'form-control', 'placeholder' => 'Peso Líquido', 'maxlength' => '50']) }}
		</div>
		<div class="col-md-4">
			{{ Form::label('peso_bruto', 'Peso Bruto') }}
			{{ Form::text('peso_bruto', '', ['id' => 'peso_bruto', 'class' => 'form-control', 'placeholder' => 'Peso Bruto', 'maxlength' => '50', 'disabled' => 'disabled']) }}
		</div>
		<div class="col-md-4">
			{{ Form::label('ncm', 'NCM') }}
			{{ Form::text('ncm', '', ['id' => 'ncm', 'class' => 'form-control', 'placeholder' => 'NCM', 'maxlength' => '11']) }}	    		
		</div>
	</div>
	<div class="form-row">
		<div class="col-md-4">
			{{ Form::label('tipo_genero', 'Gênero') }}
			{{ Form::select('tipo_genero', $tipo_generos, '', ['id' => 'tipo_genero', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Selecione o Gênero', 'readonly']) }}
		</div>
		<div class="col-md-4">
			{{ Form::label('segmento', 'Segmento') }}
			{{ Form::select('segmento', $segmentos, '', ['id' => 'segmento', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Selecione um segmento', 'readonly']) }}
		</div>
		<div class="col-md-4">
			{{ Form::label('familia', 'Acabamento') }}
			{{ Form::select('familia', $familia, '', ['id' => 'familia', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Selecione um Acabamento', 'readonly']) }}
		</div>
	</div>
	<div class="form-row">
		<div class="col-md-3"> 
			{{ Form::label('pecas', 'Peças de', []) }}
			{{ Form::text('pecas', '', ['id' => 'pecas', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Peças de', 'maxlength' => '250', 'readonly']) }}
		</div>
		<div class="form-group col-sm-3">
			{{ Form::label('construcao', 'Construção') }}
			{{ Form::select('construcao', $construcao, '', ['id' => 'construcao', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Selecione a Construção', 'readonly']) }}
		</div>
		<div class="form-group col-sm-3">
			{{ Form::label('sazonal', 'Sazonalidade') }}
			{{ Form::select('sazonal', $sazonalidade, '', ['id' => 'sazonal', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Selecione a Sazonalidade', 'readonly']) }}
		</div>
		<div class="col-md-3"> 
			{{ Form::label('caracteristicas', 'Características', []) }}
			{{ Form::text('caracteristicas', '', ['id' => 'caracteristicas', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Características', 'maxlength' => '250', 'readonly']) }}
		</div>
	</div>
	<div class="form-row">
		<div class="col-md-4">
			{{ Form::label('grupo_de_inventario', 'Grupo de inventário') }}
			{{ Form::select('grupo_de_inventario', $grupo_de_inventario, '', ['id' => 'grupo_de_inventario', 'class' => 'form-control', 'placeholder' => 'Selecione Grupo de inventário']) }}	    		
		</div>
		<div class="col-md-4"> 
			{{ Form::label('origem', 'País de Origem', []) }}
			{{ Form::text('origem', '', ['id' => 'origem', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'País de Origem', 'maxlength' => '250', 'readonly']) }}
		</div>
		<div class="col-md-4">
			{{ Form::label('padrao_codigo', 'Padrão do código') }}
			{{ Form::select('padrao_codigo', $padroes_codigo, '', ['data-old' => '', 'id' => 'padrao_codigo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Padrão Código', 'readonly']) }}
		</div>
	</div>
	<div class="form-row">
		<div class="col-md-12">
			{{ Form::label('origem_mercadoria', 'Origem de Mercadoria') }}
			{{ Form::select('origem_mercadoria', $origem_mercadoria, '', ['id' => 'origem_mercadoria', 'class' => 'form-control', 'placeholder' => 'Selecione a Origem de Mercadoria']) }}	    		
		</div>
	</div>
	<div class="form-row">
		<div class="col-md-12 mt-4">
			{{ Form::submit('Salvar', array('id' => 'bt_salvar', 'class' => 'btn btn-primary float-right')) }}
		</div>
	</div>
</form>

<script>
	$(document).ready(function(){
		$(document).find('#frm_produto_novo_add').find("#marca_modal").autocomplete(optionsAutoCompleteMarca());
		$(document).find('#frm_produto_novo_add').find("#linha_modal").autocomplete(optionsAutoCompleteLinha());
		$(document).find('#frm_produto_novo_add').find("#grupo_modal").autocomplete(optionsAutoCompleteGrupo());
		$(document).find('#frm_produto_novo_add').find("#subgrupo_modal").autocomplete(optionsAutoCompleteSubgrupo());
		$(document).find('#frm_produto_novo_add').find("#bt-search-produtos").on("click", function(){
            showModalProdutoNasajonAdd("Lista de Produtos");
        });
		$(document).find('#frm_produto_novo_add').find("#preco_venda").maskMoney({thousands:'', decimal:','});
		$(document).find('#frm_produto_novo_add').find("#peso").maskMoney({thousands:'', decimal:',', precision:3});
		$(document).find('#frm_produto_novo_add').find("#bt-buscar-produto").off("click");
        $(document).find('#frm_produto_novo_add').find("#bt-buscar-produto").on("click", function(){
            showModalProdutoAdd();
        });
        $(document).find('#frm_produto_novo_add').find("#bt_salvar").off('click');
        $(document).find('#frm_produto_novo_add').find("#bt_salvar").on('click', function(event){
            event.stopPropagation();
            var form = $(document).find('#frm_produto_novo_add');
            var url = form.attr("action");
            $.ajax({
                url: url,
                dataType: 'json',
                data: form.serialize(),
                method: 'POST',
                success: function(callback){
                    if(callback.status === "success"){
                        $(document).find('#frm_produto_novo_add').parents(".modal").modal("hide");
                        filterAjax($(document).find("#form_filter").serialize());
                        message("Atenção", "Dados salvos com sucesso!");
                    } else {
						message("Atenção", callback.message);
                    }
                },
                error: function(callback){
					var dados = callback.responseJSON;
					if(dados.message != ''){
						if(dados.message !== 'Campos inválidos'){
							message("Atenção", dados.message);
						}
					}
					limparMesagemErroAdd();
					mensagemErroAdd(dados);
				}
            });
		});
		
		$(document).find('#frm_produto_novo_add').find("#peso").off('keyup');
		$(document).find('#frm_produto_novo_add').find("#peso").on('keyup', function(event){
			caculoPesoBruto();
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_novo_adicionar').css('z-index')) + 1));
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_novo_adicionar').css('z-index')) + 1));
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_novo_adicionar').css('z-index')) + 1));
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_novo_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}
	function retornaInfoProduto($codpro){
		form = $(document).find("#frm_produto_novo_add");
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
                form.find("#grupo_modal").val(data.grupo);
                form.find("#subgrupo_modal").val(data.subgrupo);
                form.find("#marca_modal").val(data.marca);
                form.find("#linha_modal").val(data.linha);
                form.find("#largura").val(data.largura);
                form.find("#gramatura").val(data.gramatura);
                form.find("#composicao").val(data.composicao);
                form.find("#unidade").val(data.unidade);
			},
			error: function(data){
			}
		});
	}
	function showModalProdutoAdd(){
        $.ajax({
            url: '{{ route('analise.produto.infoadicional.produtosSemDetalhes') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto ficha tecnica", data, 'modal-lg');
                var modal = $(document).find("#modal_search_produto");
                $(document).ready( function () {
                    produtos_table.on('draw', function () {
                        modal.find('tbody').find("td").not('.th_view').off("click");
                        modal.find('tbody').find("td").not('.th_view').on("click", function(event){
                            returnDadosProdutoAdd($(this).parent('tr'));
                        });
                    });
                });
            }
        });
    }
    function returnDadosProdutoAdd($dados){
		form_modal_add = $(document).find("#frm_produto_novo_add");
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){ 
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
	}
	
	function limparMesagemErroAdd(){      
        var form_modal_add = $("#frm_produto_novo_add");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }
    function mensagemErroAdd(json_error){
		var form_modal_add = $("#frm_produto_novo_add");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_add, field, json_error.error[field]);
            }
        }
    }
    function showErrorsInputsAdd(form_modal_add, input, message){
        if(input.localeCompare('cod_produto') == 0){
            var $input = $(form_modal_add).find("#bt-buscar-produto");
            $(form_modal_add).find("input[name='cod_produto']").addClass('error-input');
        }else{
            var $input = $(form_modal_add).find("input[name='"+input+"'], select[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
	}
	
	function caculoPesoBruto(){
		var peso = $(document).find('#frm_produto_novo_add').find("#peso").val().replace(/\./g,"").replace(/\,/g, ".");
		peso = parseFloat(peso);

		$(document).find('#frm_produto_novo_add').find("#peso_bruto").val(numberToReal((peso * (1 + (1 / 100))).toFixed(4)));
	}

	function numberToReal(valor) {
        var numero = valor.split('.');
        numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
        return numero.join(',');
    }

	function buscaGrupo(){
		form = $(document).find('#frm_produto_novo_add');
		form.find('.error-message').remove();

		$.ajax({
			url: '{{ route("analise.produto.infoadicional.busca_grupo") }}',
			type: 'POST',
			data:form.serialize(),
			success: function(data){
				form.find("#largura").val(data.response.largura);
				form.find("#gramatura").val(data.response.gramatura_gm2);
				form.find("#gramatura_tipo").val(data.response.gramatura_tipo);
				form.find("#rendimento").val(data.response.rendimento);
				form.find("#tipo_genero").val(data.response.tipo_genero);
				form.find("#segmento").val(data.response.segmentos_id);
				form.find("#familia").val(data.response.familias_id);
				form.find("#padrao_codigo").val(data.response.padrao_codigo);
				form.find("#caracteristicas").val(data.response.caracteristicas);
				form.find("#pecas").val(data.response.pecas);
				form.find("#origem").val(data.response.origem);
				form.find("#composicao_predominante").val(data.response.composicaos_id);
				form.find("#construcao").val(data.response.construcaos_id);
				form.find("#sazonal").val(data.response.sazonalidades_id);
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