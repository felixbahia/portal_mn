@extends('layouts.page-dialog')
@section('content')
<div class="container">
	<form action="#" method="post" id="frm_produto_novo_edt" name="frm_produto_novo_edt" onsubmit="return false">
        @csrf
        {!! Form::hidden('id', $dados['id'], ['id' => 'id']) !!}
	    <div class="form-row">
			<div class="form-group col-sm-12">
				{{ Form::label('cod_produto', 'Código do produto', []) }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
				<div class="input-group" id="cod_produto_group">
                	{{ Form::text('cod_produto', '', ['id' => 'cod_produto_modal', 'class' => 'input-search-bt form-control', 'placeholder' => 'Código', "maxlength" => "60", "onchange" => "retornaInfoProduto($(this).val());"]) }}
					<span class="input-group-addon border rounded-right" id="bt-buscar-produto"><i class="bt-view m-2"></i></span>
				</div>
			</div>
        </div>
        <div class="form-row">
			<div class="col-md-8">
				{{ Form::label('descricao', 'Descricao', []) }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
		        {{ Form::text('descricao', $dados['descricao'], ['id' => 'descricao_modal', 'class' => 'input-search-bt form-control', 'placeholder' => 'Nome do produto', "maxlength" => "120"]) }}
            </div>
            <div class="col-md-4">
                {{ Form::label('preco_venda', 'Preço Venda', []) }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                {{ Form::text('preco_venda', $dados['preco_venda'], ['id' => 'preco_venda', 'class' => 'input-search-bt form-control text-right', 'placeholder' => 'Preço Venda', "maxlength" => "8"]) }}
            </div>
		</div>
		<div class="form-row">
			<div class="col-md-6">
				{{ Form::label('grupo', 'Grupo') }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
				{{ Form::text('grupo', '', array('id' => 'grupo_modal', 'class' => 'form-control', 'placeholder' => 'Grupo', 'maxlength' => '40')) }}
			</div>
			<div class="col-md-6">
				{{ Form::label('subgrupo', 'Subgrupo') }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
				{{ Form::text('subgrupo', '', array('id' => 'subgrupo_modal', 'class' => 'form-control', 'placeholder' => 'Subgrupo', 'maxlength' => '40')) }}
			</div>
		</div>
	    <div class="form-row">
	    	<div class="col-md-6">
		        {{ Form::label('marca', 'Marca') }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
		        {{ Form::text('marca', '', ['id' => 'marca_modal', 'class' => 'form-control', 'placeholder' => 'Marca', 'maxlength' => '40']) }}	
	    	</div>
	    	<div class="col-md-6">
		        {{ Form::label('linha', 'Linha') }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
		        {{ Form::text('linha', '', ['id' => 'linha_modal', 'class' => 'form-control', 'placeholder' => 'Linha', 'maxlength' => '40']) }}
			</div>
        </div>
        <div class="form-row">
            <div class="col-md-6">
                {{ Form::label('unidade', 'Unidade') }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                {{ Form::select('unidade', $unidades, '', ['id' => 'unidade', 'class' => 'form-control']) }}	    		
            </div>
            <div class="col-md-6">
                {{ Form::label('composicao', 'Composição') }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                {{ Form::text('composicao', '', array('id' => 'composicao', 'class' => 'form-control', 'placeholder' => 'Composição', 'maxlength' => '250')) }}
            </div>    
        </div>
	    <div class="form-row">
	    	<div class="col-md-4">
		        {{ Form::label('largura', 'Largura') }}
		        {{ Form::text('largura', '', ['id' => 'largura', 'class' => 'form-control', 'placeholder' => 'Largura', 'maxlength' => '250']) }}	    		
	    	</div>
	    	
	    	<div class="col-md-4">
		        {{ Form::label('gramatura', 'Gramatura') }}
		        {{ Form::text('gramatura', '', ['id' => 'gramatura', 'class' => 'form-control', 'placeholder' => 'Gramatura', 'maxlength' => '250']) }}
		    </div>

			<div class="col-md-4">
		        {{ Form::label('rendimento', 'Rendimento') }}
		        {{ Form::text('rendimento', '', ['id' => 'rendimento', 'class' => 'form-control', 'placeholder' => 'Rendimento', 'maxlength' => '250']) }}
		    </div>
		</div>
		<div class="form-row">
			<div class="col-md-6">
				{{ Form::label('peso', 'Peso') }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
				{{ Form::text('peso', $dados['peso'], ['id' => 'peso', 'class' => 'form-control text-right', 'placeholder' => 'Peso', 'maxlength' => '8']) }}	    		
			</div>
			<div class="col-md-6">
				{{ Form::label('ncm', 'NCM') }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
				{{ Form::text('ncm', $dados['ncm'], array('id' => 'ncm', 'class' => 'form-control text-right', 'placeholder' => 'NCM', 'maxlength' => '250')) }}
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-12">
				{{ Form::label('origem_mercadoria', 'Origem de Mercadoria') }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
				{{ Form::select('origem_mercadoria', $origem_mercadoria, '', ['id' => 'origem_mercadoria', 'class' => 'form-control', 'placeholder' => 'Selecione a Origem de Mercadoria']) }}	    		
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-12">
				{{ Form::label('grupo_de_inventario', 'Grupo de inventário') }}<span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
				{{ Form::select('grupo_de_inventario', $grupo_de_inventario, '', ['id' => 'grupo_de_inventario', 'class' => 'form-control', 'placeholder' => 'Selecione Grupo de inventário']) }}	    		
			</div>
		</div>
		<div class="form-row">
			<div class="col-md-12 mt-4">
				{{ Form::submit('Salvar', array('id' => 'bt_salvar', 'class' => 'btn btn-primary float-right')) }}
			</div>
		</div>
	</form>
</div>
<script>
	$(document).ready(function(){
		form_modal = $(document).find('#frm_produto_novo_edt');
		form_modal.find("#marca_modal").autocomplete(optionsAutoCompleteMarca());
		form_modal.find("#linha_modal").autocomplete(optionsAutoCompleteLinha());
		form_modal.find("#grupo_modal").autocomplete(optionsAutoCompleteGrupo());
		form_modal.find("#subgrupo_modal").autocomplete(optionsAutoCompleteSubgrupo());
		form_modal.find("#bt-search-produtos").on("click", function(){
            showModalProdutoNasajonAdd("Lista de Produtos");
        });
		form_modal.find("#preco_venda").maskMoney({thousands:'', decimal:','});
		form_modal.find("#peso").maskMoney({thousands:'', decimal:',', precision:3});
		
		form_modal.find("#bt-buscar-produto").off("click");
        form_modal.find("#bt-buscar-produto").on("click", function(){
            showModalProdutoAdd();
        });
        form_modal.find("#bt_salvar").off('click');
        form_modal.find("#bt_salvar").on('click', function(event){
			form_modal.find("#bt_salvar").attr("disabled", "disabled");
			editarDados();
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_edit_delete').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_edit_delete').css('z-index')) + 1));
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_edit_delete').css('z-index')) + 1));
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_edit_delete').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}
	function retornaInfoProduto($codpro){
		form = $(document).find("#frm_produto_novo_edt");
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
		form_modal_add = $(document).find("#frm_produto_novo_edt");
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){ 
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
	}
	
	function limparMesagemErroAdd(){      
        var form_modal_add = $("#frm_produto_novo_edt");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }
    function mensagemErroAdd(json_error){
		var form_modal_add = $("#frm_produto_novo_edt");
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
	function editarDados(){
		var form_modal = $(document).find('#frm_produto_novo_edt');
		event.stopPropagation();
		$.ajax({
			url: '{{ route("produto.novo.editar") }}',
			dataType: 'json',
			data: form.serialize(),
			method: 'POST',
			async: false,
			success: function(callback){
				if(callback.status === "success"){
					form_modal.parents(".modal").modal("hide");
					filterAjax($(document).find("#form_filter").serialize());
					message("Atenção", "Dados salvos com sucesso!");
				} else {
					form_modal.find("#bt_salvar").removeAttr('disabled');
					message("Atenção", callback.message);
				}
			},
			error: function(callback){
				form_modal.find("#bt_salvar").removeAttr('disabled');
				var dados = callback.responseJSON;
				if(dados.message !== 'Campos inválidos'){
					message("Atenção", dados.message);
				}
				limparMesagemErroAdd();
				mensagemErroAdd(dados);
			}
		});
	}
</script>
@endsection