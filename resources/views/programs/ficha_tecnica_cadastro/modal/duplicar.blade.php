@extends('layouts.page-dialog')

@section('content')

<div class="container">
	<form action="#" method="post" id="frm_produto_duplicar" name="frm_produto_duplicar" onsubmit="return false">
        @csrf
		<div class="row">
        	<div class="col-sm">
				{{ Form::hidden('id', $dados['id'], ['id' => 'id']) }}
				<b>Código: </b> {{ $dados['codigo'] }} <br/>
				<b>Descrição: </b>{{ $dados['descricao'] }}
        	</div>
    	</div>
	    <div class="form-row">
			<div class="form-group col-sm-12">
				{{ Form::label('cod_produto_modal_duplicar', 'Código do produto', []) }}
				<div class="input-group" id="cod_produto_group">
                	{{ Form::text('codigo_produto', '', ['id' => 'cod_produto_modal_duplicar', 'class' => 'input-search-bt form-control', 'placeholder' => 'Código', "maxlength" => "60"]) }}
					<span class="input-group-addon border rounded-right" id="bt-buscar-produto"><i class="bt-view m-2"></i></span>
				</div>
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-12">
				{{ Form::label('descricao_modal_duplicar', 'Descrição', []) }}
				{{ Form::text('descricao', '', ['id' => 'descricao_modal_duplicar', 'class' => 'input-search-bt form-control', 'placeholder' => 'Descrição do produto', "maxlength" => "60"]) }}
			</div>
        </div>
		
		<div class="form-row">

			<div class="col-sm-12 mt-4">

				{{ Form::submit('Duplicar', array('id' => 'bt_salvar', 'class' => 'btn btn-success float-right')) }}
			
			</div>
		
		</div>

	</form>

</div>


<script>

	$(document).ready(function(){

		$(document).find('#frm_produto_duplicar').find("#bt-buscar-produto").off("click");
        $(document).find('#frm_produto_duplicar').find("#bt-buscar-produto").on("click", function(){
            showModalProdutoDuplicar();
        });

		$(document).find('#frm_produto_duplicar').find("#bt_salvar").off("click");
		$(document).find('#frm_produto_duplicar').find("#bt_salvar").on("click", function(){
			salvarFichaTecnicaDuplicar();
		});

        $(document).find('#modal_ficha_tecnica_duplicar').find("#descricao_modal_duplicar").autocomplete(optionsAutoComplete());

		$(document).find('#modal_ficha_tecnica_duplicar').find("#cod_produto_modal_duplicar").on('change', function(){
			codigoParaNome()
		});

	});

	function showModalProdutoDuplicar(){
        $.ajax({
            url: '{{ route('produto.modal_pesquisa') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto duplicar ficha tecnica", data, 'modal-lg');
                var modal = $(document).find("#modal_search_produto");
                $(document).ready( function () {
                    table_filters_produtos_busca.on('draw', function () {
                        modal.find('tbody').find("td").not('.th_view').off("click");
                        modal.find('tbody').find("td").not('.th_view').on("click", function(event){
                            returnDadosProdutoDuplicar($(this).parent('tr'));
                        });
                    });
                });
            }
        });
    }
    function returnDadosProdutoDuplicar($dados){
		form_modal = $(document).find("#frm_produto_duplicar");
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){ 
            return false;
        }
		$(document).find('#modal_ficha_tecnica_duplicar').find('#cod_produto_modal_duplicar').val($dados.find("td").eq(1).html());
		$(document).find('#modal_ficha_tecnica_duplicar').find('#descricao_modal_duplicar').val($dados.find("td").eq(2).html());
		
        $(document).find("#modal_search_produto").modal("hide");
	}

	function salvarFichaTecnicaDuplicar(){
		form = $(document).find('#frm_produto_duplicar');
		$.ajax({
            url: "{{ route('ficha_tecnica.cadastro.duplicar') }}", 
            dataType: 'json',
            data: form.serialize(),
            method: 'POST',
            success: function(callback){
                if(callback.status === "success"){
                    $(form).parents('.modal').modal('hide');
                    message("Atenção", "Cadastro duplicado com sucesso!");
                }
            },
			error: function(callback){
				var dados = callback.responseJSON;
				limparMesagemErroModalDuplicar();
				mensagemErroModalDuplicar(dados)
			}
        });
	}

	function limparMesagemErroModalDuplicar(){  
		form = $(document).find('#frm_produto_duplicar');    
        form.find('.error-message').remove();
        form.find('input, select, span, button').removeClass('error-input');
    }

	function mensagemErroModalDuplicar(json_error){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModalDuplicar(form, field, json_error.error[field]);
            }
        }
    }

	function showErrorsInputsModalDuplicar(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']").parent();
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function optionsAutoComplete(){
		limparMesagemErroModalDuplicar();
		return {
			source: function (request, response) {
				request.name = 'nome';
				request._token = "{{ csrf_token() }}";
				request.campo = 'linha';

				request.condicao = "%";

				$.post("{{ route('produto.autocompletelimitacao') }}", request, response);
			},
		delay: 700,
			minLength: 2,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($('#modal_ficha_tecnica_duplicar').css('z-index')) + 1));
			},
			response: function( event, ui ) {
				if(ui.content.length === 0){
					message('Atenção', 'Nenhum produto encontrado');
					event.stopPropagation();
					return false;
				}
			},
			select: function( event, ui ) {
				$(document).find("#cod_produto_modal_duplicar").val(ui.item.value);
				$(document).find("#descricao_modal_duplicar").val(ui.item.label);
				return false;
			}
		};
    }

	function codigoParaNome(){
		limparMesagemErroModalDuplicar();
		$.ajax({
			url: "{{ route('produto.pesquisaprodutocodigo') }}",
			dataType: 'json',
			data: {
				_token: '{{ csrf_token() }}',
				codigo_produto: $(document).find('#cod_produto_modal_duplicar').val(),
			},
			method: 'POST',
			success: function(callback){
				$(document).find('#descricao_modal_duplicar').val(callback.response.descricao);
			}
		});            
	}

</script>

@endsection