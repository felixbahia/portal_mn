@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">	
	<form action="#" name="form_filter_produtos_base" id="form_filter_produtos_base" onsubmit="return false;">
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
            <div class="col-lg-1">
                <input type="text" name="codigo_produto_base" id="codigo_produto_base" value="" placeholder="Prefixo" maxlength="250" />
            </div>
	        <div class="col-lg-1">
	            <input type="text" name="marca" id="marca" value="" placeholder="Marca" maxlength="250" />
            </div>
	        <div class="col-lg-2">
	            <input type="text" name="linha" id="linha" value="" placeholder="Linha" maxlength="250" />
            </div>
	        <div class="col-lg-2">
	            <input type="text" name="subgrupo" id="subgrupo" value="" placeholder="Subgrupo" maxlength="250" />
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
                    <th>Prefixo</th>
	                <th>Marca</th>
                    <th>Linha</th>
                    <th>Subgrupo</th>
	            </tr>
	        </thead>
	        <tbody>
	        </tbody>
	    </table>
	</div>
</div>
<script>
	$(document).ready( function () {
		$(document).find("#form_filter_produtos_base").find("#marca").autocomplete(optionsAutoCompleteModalBuscar("marca"));
        $(document).find("#form_filter_produtos_base").find("#linha").autocomplete(optionsAutoCompleteModalBuscar("linha"));
        $(document).find("#form_filter_produtos_base").find("#grupo").autocomplete(optionsAutoCompleteModalBuscar("grupo"));
        $(document).find("#form_filter_produtos_base").find("#descricao").autocomplete(optionsAutoCompleteTecidoBaseModalBuscar());
        $(document).find("#form_filter_produtos_base").find("#subgrupo").autocomplete(optionsAutoCompleteModalBuscar("subgrupo"));
		
		$(document).find("#btn-filterform-modal").on("click", function(){
			filtroLimpar();
			filtro();
		})

		$(document).find("#btn-clearform").on("click", function(){
			filtroLimpar();
		})

	});	
	
	function filtro(){
		form_search_prod = $(document).find("#form_filter_produtos_base");
		data_form_search_prod = form_search_prod.serialize();
        limparMesagemErroModalBuscar();
		$.ajax({
			url: '{{ route('produto.tecido_base.filtro_buscar')}}',
            data: data_form_search_prod,
            method: 'POST',
			success: function(data){
				
				produtos = [];

				for (var fields in data.response){
					temp_array = [
						ajusteTamanhoTable(data.response[fields].grupo),
						ajusteTamanhoTable(data.response[fields].codigo),
                        ajusteTamanhoTable(data.response[fields].descricao),
                        ajusteTamanhoTable(data.response[fields].prefixo),
						ajusteTamanhoTable(data.response[fields].marca),
                        ajusteTamanhoTable(data.response[fields].linha),
                        ajusteTamanhoTable(data.response[fields].subgrupo)
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
	});
	
	function filtroLimpar(){
		table_filters_produtos_busca.clear().draw();
	}

	function limparMensagemErro(form){
		form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');
	}

	function optionsAutoCompleteModalBuscar($name){
        
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#form_filter_produtos_base').parents('.modal').css('z-index')) + 1));
            },
            response: function( event, ui ) {  
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum tecido base encontrado');
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

    function optionsAutoCompleteTecidoBaseModalBuscar(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.tecido_base.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#form_filter_produtos_base').parents('.modal').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#form_filter_produtos_base").find("#descricao").val(ui.item.label);
                return false;
            }
        };
    }

    function limparMesagemErroModalBuscar(){      
        var form_modal_buscar = $("#form_filter_produtos_base");
        form_modal_buscar.find('.error-message').remove();
        form_modal_buscar.find('input, select, span').removeClass('error-input');
    }
	
	function mensagemErroModalBuscar(json_error){
        var form_modal_buscar = $("#form_filter_produtos_base");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.errors){
                showErrorsInputsModalBuscar(form_modal_buscar, field, json_error.errors[field]);
            }
        }
    }

    function showErrorsInputsModalBuscar(form_modal_buscar, input, message){
		var $input = $(form_modal_buscar).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";
    
        return $html;
    }
</script>
@endsection