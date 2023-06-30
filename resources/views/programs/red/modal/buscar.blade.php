@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">	
	<form action="#" name="form_filter_produtos_base" id="form_filter_produtos_base" onsubmit="return false;">
        @csrf
	    <div class="content-fields">
	        <div class="col-lg-2">
	            <input type="text" name="documento_busca_filtro" id="documento_busca_filtro" value="" placeholder="Documento" maxlength="250" />
            </div>
            <div class="col-lg-2">
	            <input type="text" class="data" name="vencimento_inicial_busca_filtro" id="vencimento_inicial_busca_filtro" value="" placeholder="Vencimento Inicial" maxlength="250" />
            </div>
            <div class="col-lg-2">
	            <input type="text" class="data" name="vencimento_final_busca_filtro" id="vencimento_final_busca_filtro" value="" placeholder="Vencimento Final" maxlength="250" />
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
                    <th>Documento</th> 
                    <th class="tb_number">Valor U$</th>
                    <th class="tb_number">Taxa Câmbio R$</th> 
                    <th class="tb_date">Vencimento</th>  
                    <th class="tb_number">Saldo</th>                  
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <td class="tb_number">Total: </td>
                    <td class="tb_number" id='total_valor'></td>
                    <td></td>
                    <td></td>
                    <td class="tb_number" id='total_saldo'></td> 
                </tr>
            </tfoot>
        </table>
	</div>
</div>
<script>
	$(document).ready( function () {
        form_buscar_red_modal = $(document).find("#form_filter_produtos_base");
		
		form_buscar_red_modal.find("#btn-filterform-modal").on("click", function(){
			filtroModalBuscarRedLimpar();
			filtroModalBuscarRed(form_buscar_red_modal.serialize());
		})

		form_buscar_red_modal.find("#btn-clearform").on("click", function(){
			filtroLimpar();
		})

        form_buscar_red_modal.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form_buscar_red_modal.find('.data').mask('00/00/0000');
	});	
	
	function filtroModalBuscarRed(data_form_red){
		table_filters_produtos_busca.clear().draw();
        $.ajax({
            url: "{{ route('red.filtro') }}",
            dataType: 'json',
            data: data_form_red,
            method: 'POST',
            success: function(callback){
                var data = callback.response;
                var fields_filter = [];
                for(var field in data.reds){
                    var temp_field = [
                        data.reds[field].numero_documento,
                        data.reds[field].valor,
                        data.reds[field].taxa_cambio,
                        data.reds[field].vencimento,
                        data.reds[field].saldo,
                    ];
                    fields_filter.push(temp_field);
                }
                table_filters_produtos_busca.rows.add(fields_filter).draw().nodes();

                $(document).find('#total_valor').html(data.total.valor);
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
                {
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    "orderable": false
                },
                {
                    'targets': 'tb_number',
                    'class': 'tb_number',
                },
                {
                    'targets': 'tb_date',
                    'class': 'tb_date',
                }
            ],
	});
	
	function filtroModalBuscarRedLimpar(){
		table_filters_produtos_busca.clear().draw();
	}

</script>
@endsection