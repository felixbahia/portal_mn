@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">	
    <form action="#" name="form_modal_buscar_unidade_negocio" id="form_modal_buscar_unidade_negocio" onsubmit="return false;">
        @csrf
        <div class="content-fields">
            <div class="col-lg-6">
                <input type="text" name="unidade" id="unidade" value="" placeholder="Unidade" maxlength="250" require/>
            </div>
            <div class="col-lg-6">
                <div class="input-group" id="cod_cliente_group">
                    <input type="text" name="usuario_responsavel" value="" id="usuario_responsavel" class="form-control input-label" placeholder="Gerente da Unidade" maxlength="250">
                </div>
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
        <table class="table table-striped" id="table-filters-unidade_negocio">
            <thead>
                <tr>
                    <th>Unidade</th> 
                    <th>Gerente</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
<script>
    table_modal_buscar_unidade_negocio = $("#table-filters-unidade_negocio").DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
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
        }
    });
    $(document).ready( function () {
        form_modal_buscar = $(document).find("#form_modal_buscar_unidade_negocio");

        form_modal_buscar.find("#btn-filterform-modal").off("click");
        form_modal_buscar.find("#btn-filterform-modal").on("click", function(){
            filterClearModalBuscar();
            filterModalBuscar(form_modal_buscar.serialize());
        });
    });

    function filterModalBuscar(data_form_modal_buscar){
        filterClearModalBuscar();
        $.ajax({
            url: '{{ route('unidade_negocio.filtro')}}',
            data: data_form_modal_buscar,
            method: 'POST',
            success: function(data){
                linhas = [];
                
                for (var fields in data.response){
                    temp_array = [
                        data.response[fields].unidade,
                        data.response[fields].usuario,
                    ];
                    linhas.push(temp_array)
                }
                table_modal_buscar_unidade_negocio.rows.add(linhas).draw();            
            }
        });
    }

    function filterClearModalBuscar(){
        table_modal_buscar_unidade_negocio.clear().draw();
    }
</script>
@endsection