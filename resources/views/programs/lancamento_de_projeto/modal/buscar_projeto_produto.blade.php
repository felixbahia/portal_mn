@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">	
	<form action="#" name="form_filter" id="form_filter_produtos" onsubmit="return false;">
	    @csrf
	    <div class="content-fields">
	    	
	        <div class="col-lg-2">
	            <input type="text" name="grupo" id="grupo_modal_busca" value="" placeholder="Grupo" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="codigo" id="codigo_modal_busca" value="" placeholder="Código Produto" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="nome" id="nome_modal_busca" value="" placeholder="Nome Produto" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="marca" id="marca_modal_busca" value="" placeholder="Marca" maxlength="250" />
	        </div>
	        <div class="col-lg-2">
	            <input type="text" name="linha" id="linha_modal_busca" value="" placeholder="Linha" maxlength="250" />
	        </div>
	    </div>
	    <div class="content-buttons">
	        <button name="btn-filterform-modal" id="btn-filterform-modal" class="btn-filter">Buscar</button>
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
	                <th class="th_view">Código</th>
	                <th>Descrição</th>
	                <th>Marca</th>
	                <th>Linha</th>
	            </tr>
	        </thead>
	        <tbody>
	        </tbody>
	    </table>
	</div>
</div>
<script>

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
                $('.ui-autocomplete').css("z-index", (parseInt($('#table-modal-produtos-busca').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.draw();
                }, 100);
            }
        };
    }

	table_filters_produtos_busca = {
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
        ]

    };

    function returnDadosProduto($dados){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        $(document).find('#cod_produto_modal').val($dados.find("td").eq(1).text());
        $(document).find('#cod_produto_modal').trigger("change");
    };

	produtos_table = $(document).find("#table-filters-produtos-busca").DataTable(table_filters_produtos_busca);

	$("#table-modal-produtos-busca").find("#nome_modal_busca").autocomplete(optionsAutoComplete("nome"));
	$("#table-modal-produtos-busca").find("#marca_modal_busca").autocomplete(optionsAutoComplete("marca"));
	$("#table-modal-produtos-busca").find("#linha_modal_busca").autocomplete(optionsAutoComplete("linha"));
	$("#table-modal-produtos-busca").find("#grupo_modal_busca").autocomplete(optionsAutoComplete("grupo"));


	$(document).ready(function($) {

		produtos_table.on('draw', function () {

		    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
		    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
		        returnDadosProduto($(this));
		    });

		});

		if ($("#form_filter_produtos").find("#codigo_modal_busca").val().length > 0 || $("#form_filter_produtos").find("#nome_modal_busca").val().length > 0) {
		    produtos_table.draw();
		}
		    
		$(document).find("#btn-filterform-modal").on("click", function(){

			produtos_table.clear().draw();
            limparMesagemErro($('#form_filter_produtos'));
			$.ajax({
				url: '{{route('lancamento_projeto.produto.filter')}}',
				type: 'POST',
				data: $('#form_filter_produtos').serialize(),
				success: function(data){
					var temp_line;
                    var pesquisa = [];
                    


					for (var field in data){
						temp_line = [
							data[field].grupo,
							linkFichaTecnica(data[field].codigo),
							data[field].nome,
							data[field].marca,
							data[field].linha,
						];

						pesquisa.push(temp_line);

					}

					produtos_table.rows.add(pesquisa).draw();
				
                },
                error: function(callback){
                    var dados = callback.responseJSON;
                    mensagemErroModal(dados, $('#form_filter_produtos'));
                }
                
			});

		    produtos_table.draw();

		});

    });

    function limparMesagemErro(form_modal_add){   
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }
    
    function mensagemErroModal(json_error, form_modal_add){
        console.log(json_error);
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_add, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form_modal_add, input, message){
        var $input = $(form_modal_add).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function linkFichaTecnica($codigo){
        html = "<td><a href='#' onclick='showFichaTecnica('"+$codigo+"')'>"+$codigo+"</a></td>";

        return html;
    }
    function showFichaTecnica($codigo){

    }
</script>
@endsection

