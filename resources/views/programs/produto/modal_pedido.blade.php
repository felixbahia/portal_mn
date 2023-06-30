@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">	
	<form action="#" name="form_filter_produtos" id="form_filter_produtos" onsubmit="return false;">
	    @csrf
	    <div class="content-fields">
	    	<input type="hidden" name="pedido" value="{{ $pesquisa['id'] }}">
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
			<div class="col-lg-2">
				{{ Form::select('segmentos', $segmentos, '', ['id' => 'segmentos', 'class' => 'form-control', 'placeholder' => 'Selecione o Segmento']) }}
	        </div>
			<div class="col-lg-2">
				{{ Form::select('campanha', $campanhas, '', ['id' => 'campanha_modal_busca', 'class' => 'form-control', 'placeholder' => 'Selecione a Campanha']) }}
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
	                <th>Unidade</th>
					<th>Peças de</th>
	                <th>{{ $pesquisa['pedido_futuro'] == 'true' ? "Compra na quinzena" : "Estoque disponível" }}</th>
	                <th>Já no pedido?</th>
					<th>Campanha</th>
	            </tr>
	        </thead>
	        <tbody>
	        </tbody>
	    </table>
	</div>
</div>
<script type='text/javascript'>
    table_dialog = [];
	$(document).ready(function($) {
	    table_dialog = $(document).find("#table-filters-produtos-busca").DataTable({
	        "searching": false,
	        "lengthChange": false,
	        "info": false,
	        "pageLength": 15,
	        "orderMulti": false,
	        "language": {
	            "decimal":        ",",
	            "emptyTable":     "Nenhum registro encontrado",
	            "infoPostFix":    "",
	            "thousands":      ".",
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
	            {
	                "targets": 7,
	                "orderable": true,
	                "class": "number_format"
	            },
	            {
	            	"targets": 8,
	            	"visible": false,
	        	}
	        ],
	        "order": [[8, "desc"], [2, "asc"]],
		    "fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
		    	if (aData[8] == "true") {
		        	$('td', nRow).css('background-color', 'rgba(85, 255, 111, 0.5)' );
		     	}
		    }
	    });
		$(document).find("#btn-filterform-modal").off("click");
		$(document).find("#btn-filterform-modal").on("click", function(){

			form = $(document).find('#form_filter_produtos');
			table_dialog.clear().draw();
            form.find('.error-message').remove();
			form.find(".error-input").removeClass('error-input');

			$.ajax({
				url: '{{route('produto.filter_pesquisa_pedido')}}',
				type: 'POST',
				data: form.serialize(),
				success: function(data){
					var temp_line;
					var lines = [];

					for (var field in data){
						temp_line = [
							data[field].grupo,
							data[field].codigo,
							data[field].nome,
							data[field].marca,
							data[field].linha,
							data[field].unidade,
							data[field].pecas,
							data[field].estoque,
							data[field].ja_no_pedido,
							data[field].campanha,
						];
						lines.push(temp_line);
					}
					table_dialog.rows.add(lines).draw();
				},
	            error: function(data){
	                var errors = data.responseJSON.errors;
	                for(var field in errors){
	                    showErrorsInputs(form, field, errors[field])
	                }
	            }
			});
		});
		$(document).find('#form_filter_produtos').find("#nome_modal_busca").autocomplete(optionsAutoCompleteProduto("nome"));
		$(document).find('#form_filter_produtos').find("#marca_modal_busca").autocomplete(optionsAutoCompleteProduto("marca"));
		$(document).find('#form_filter_produtos').find("#linha_modal_busca").autocomplete(optionsAutoCompleteProduto("linha"));
		$(document).find('#form_filter_produtos').find("#grupo_modal_busca").autocomplete(optionsAutoCompleteProduto("grupo"));

	});
    function optionsAutoCompleteProduto($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#table-modal-produtos-busca').css('z-index')) + 1));
            },
        };
    }
</script>
@endsection

