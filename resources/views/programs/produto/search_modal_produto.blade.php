@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">	
	<form action="#" name="form_filter_produtos" id="form_filter_produtos" onsubmit="return false;">
	    @csrf
	    <div class="content-fields">
	    	
	        <div class="col-lg-2">
	            <input type="text" name="grupo" id="grupo" value="" placeholder="Grupo" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="codigo_produto" id="codigo_produto" value="" placeholder="Código Produto" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="descricao" id="descricao" value="" placeholder="Nome Produto" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="marca" id="marca" value="" placeholder="Marca" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="linha" id="linha" value="" placeholder="Linha" maxlength="250" />
	        </div>
	    </div>
	    <div class="content-buttons">
	        <button name="btn-filterform" id="btn-filterform-modal" class="btn-filter">Buscar</button>
	        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
	    </div>
	</form>
</div>


<div class="content-dialog-table">
	<div class="content-table">
	    <table class="table table-striped" id="table-filters-produtos-busca">
	        <thead>
	            <tr>
	                <th>Grupo</th>
	                <th>Código</th>
	                <th>Descrição</th>
	                <th>Marca</th>
	                <th>Linha</th>
	                <th>Composição</th>
	                <th class="tb_number">Estoque</th>
	            </tr>
	        </thead>
	        <tbody>
	        </tbody>
	    </table>
	</div>
</div>

<script>
	$(document).ready( function () {
			
		$(document).find("#form_filter_produtos").find("#marca").autocomplete(optionsAutoComplete("marca"));
        $(document).find("#form_filter_produtos").find("#linha").autocomplete(optionsAutoComplete("linha"));
        $(document).find("#form_filter_produtos").find("#grupo").autocomplete(optionsAutoComplete("grupo"));
		$(document).find("#form_filter_produtos").find("#descricao").autocomplete(optionsAutoComplete("nome"));
		
		$(document).find("#btn-filterform-modal").on("click", function(){
			filtroLimpar();
			filtro();
		})

		$(document).find("#btn-clearform").on("click", function(){
			filtroLimpar();
		})

		
	});	
	
	function filtro(){
		form_search_prod = $(document).find("#form_filter_produtos");
		data_form_search_prod = form_search_prod.serialize();
		limparMesagemErroModalBuscar(form_search_prod);
		$.ajax({
			url: '{{ route('produto.filtersimples')}}',
            data: data_form_search_prod,
            method: 'POST',
			success: function(data){
				
				produtos = [];

				for (var fields in data.response){
					$grupo = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response[fields].grupo + "''>" + (data.response[fields].grupo) + "</div></div>";
					$descricao = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response[fields].descricao + "''>" + data.response[fields].descricao + "</div></div>";
					$composicao = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response[fields].composicao + "''>" + data.response[fields].composicao + "</div></div>";
					$marca = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response[fields].marca + "''>" + data.response[fields].marca + "</div></div>";
					$linha = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response[fields].linha + "''>" + data.response[fields].linha + "</div></div>";

					temp_array = [
						$grupo,
						data.response[fields].codigo_produto,
						$descricao,
						$marca,
						$linha,
						$composicao,
						data.response[fields].estoque
					];

					produtos.push(temp_array)
				}

				table_filters_produtos_busca.rows.add(produtos).draw();

			},
            error: function(data){
				var dados = data.responseJSON;
                limparMesagemErroModalBuscar();
                mensagemErroModalBuscar(dados);
			}
		});
	}

    table_filters_produtos_busca = $(document).find('#table-filters-produtos-busca').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
        "language": {
            "decimal":        ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ",",
            "loadingRecords": "Carregando...",
            "processing":     "Processando...",
            "zeroRecords":    "Nenhum registro encontrado",
            "paginate": {
                "first":      "<<",
                "last":       ">>",
                "next":       ">",
                "previous":   "<"
            }
        },
		"columnDefs": [
			{ "class": "tb_number", targets: "tb_number"},
		],
	});
	
	function filtroLimpar(){
		table_filters_produtos_busca.clear().draw();
	}

	function limparMensagemErro(form){
		form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');
	}

	function optionsAutoComplete($name){
        
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#form_filter_produtos').css('z-index')) + 1));
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
                setTimeout(function(){
                    event.stopPropagation();
                    limparMesagemErroAdd(false);
                }, 100);
            }
        };
	}
	

    function limparMesagemErroModalBuscar(){      
        var form_modal_buscar = $("#form_filter_produtos");
        form_modal_buscar.find('.error-message').remove();
        form_modal_buscar.find('input, select, span').removeClass('error-input');
    }
	
	function mensagemErroModalBuscar(json_error){
		var form_modal_buscar = $("#form_filter_produtos");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModalBuscar(form_modal_buscar, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModalBuscar(form_modal_buscar, input, message){
		var $input = $(form_modal_buscar).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection

