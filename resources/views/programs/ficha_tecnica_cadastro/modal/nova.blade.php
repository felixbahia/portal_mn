@extends('layouts.page-dialog')

@section('content')

<div class="container">
	<form action="#" method="post" id="frm_produto_novo_add" name="frm_produto_novo_add" onsubmit="return false">
        @csrf
	    <div class="form-row">
			<div class="form-group col-sm-12">
				{{ Form::label('cod_produto_modal', 'Código do produto', []) }}
				<div class="input-group" id="cod_produto_group">
                	{{ Form::text('codigo_produto', '', ['id' => 'cod_produto_modal', 'class' => 'input-search-bt form-control', 'placeholder' => 'Código', "maxlength" => "60"]) }}
					<span class="input-group-addon border rounded-right" id="bt-buscar-produto"><i class="bt-view m-2"></i></span>
				</div>
			</div>
		</div>
		<div class="form-row">
			<div class="form-group col-sm-12">
				{{ Form::label('descricao_modal', 'Descrição', []) }}
				{{ Form::text('descricao', '', ['id' => 'descricao_modal', 'class' => 'input-search-bt form-control', 'placeholder' => 'Descrição do produto', "maxlength" => "60"]) }}
			</div>
        </div>
		
		<div class="form-row">

			<div class="col-sm-12 mt-4">

				{{ Form::submit('Salvar', array('id' => 'bt_salvar', 'class' => 'btn btn-primary float-right')) }}
			
			</div>
		
		</div>

	</form>

</div>


<script>

	$(document).ready(function(){

		$(document).find('#frm_produto_novo_add').find("#bt-buscar-produto").off("click");
        $(document).find('#frm_produto_novo_add').find("#bt-buscar-produto").on("click", function(){
            showModalProdutoAdd();
        });

		$(document).find('#frm_produto_novo_add').find("#bt_salvar").off("click");
		$(document).find('#frm_produto_novo_add').find("#bt_salvar").on("click", function(){
			salvarNovaFichaTecnica();
		});

        $(document).find('#modal_ficha_tecnica_novo').find("#descricao_modal").autocomplete(optionsAutoComplete());

		$(document).find('#modal_ficha_tecnica_novo').find("#cod_produto_modal").on('change', function(){
			codigoParaNome()
		});

	});

	function showModalProdutoAdd(){
        $.ajax({
            url: '{{ route('produto.modal_pesquisa') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto ficha tecnica", data, 'modal-lg');
                var modal = $(document).find("#modal_search_produto");
                $(document).ready( function () {
                    table_filters_produtos_busca.on('draw', function () {
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
		$(document).find('#modal_ficha_tecnica_novo').find('#cod_produto_modal').val($dados.find("td").eq(1).html());
		$(document).find('#modal_ficha_tecnica_novo').find('#descricao_modal').val($dados.find("td").eq(2).html());
		
        $(document).find("#modal_search_produto").modal("hide");
	}

	function salvarNovaFichaTecnica(){

		var form = $(document).find('#modal_ficha_tecnica_novo').find('#frm_produto_novo_add');
		var data = form.serialize();

		form.find('.error-message').remove()
		form.find('.error-input').removeClass('error-input');

		$.ajax({
            url: '{{ route('ficha_tecnica.cadastro.modal.salvar') }}',
            type: 'POST',
            data: data,
            success: function (data){
				$(document).find('#modal_ficha_tecnica_novo').modal('hide');

				$.ajax({
					data: {
						id: data.response.id,
						_token: '{{ csrf_token() }}',
						campo: 'linha',
					},
					url: '{{ route('ficha_tecnica.cadastro.modal.editar') }}',
					method: 'POST',
					success: function(data){
						var title = 'Ficha técnica do produto';
						createModal('modal_ficha_tecnica_editar', title, data, "modal-lg");

						$(document).find('#modal_ficha_tecnica_editar').on('shown.bs.modal', function(){
							table_filters_composicao.columns.adjust().draw();
							table_filters_servicos.columns.adjust().draw();
						});
					},
					error: function(callback){
					}
				});
            },
			error: function(callback){
				var errors = callback.responseJSON.errors;
                for(var field in errors){
                    showErrorsInputsModalNovo(form, field, errors[field])
                }
			}
        });
	}

	function showErrorsInputsModalNovo(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']").parent();
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function optionsAutoComplete(){
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
				$('.ui-autocomplete').css("z-index", (parseInt($('#modal_ficha_tecnica_novo').css('z-index')) + 1));
			},
			response: function( event, ui ) {
				if(ui.content.length === 0){
					message('Atenção', 'Nenhum produto encontrado');
					event.stopPropagation();
					return false;
				}
			},
			select: function( event, ui ) {
				$(document).find("#cod_produto_modal").val(ui.item.value);
				$(document).find("#descricao_modal").val(ui.item.label);
				return false;
			}
		};
    }

	function codigoParaNome(){
		
		$.ajax({
			url: "{{ route('produto.pesquisaprodutocodigo') }}",
			dataType: 'json',
			data: {
				_token: '{{ csrf_token() }}',
				codigo_produto: $(document).find('#cod_produto_modal').val(),
			},
			method: 'POST',
			success: function(callback){
				$(document).find('#descricao_modal').val(callback.response.descricao);
			}
		});            
	}

</script>

@endsection