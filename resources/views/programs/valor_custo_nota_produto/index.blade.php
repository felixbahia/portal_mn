@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-sm-3">
            <div class="input-group">
                <input type="text" name="fornecedor" id="fornecedor" class='form-control input-label' value="" placeholder="Fornecedor" />
                <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-sm-2">
            <input type="text" name="data_inicio" id="data_inicio" value="" placeholder="Data nota de:" />
        </div>
        <div class="col-sm-2">
            <input type="text" name="data_fim" id="data_fim" value="" placeholder="Data nota até:" />
        </div>
        <div class="col-sm-2">
            <input type="text" name="pedido" id="pedido" value="" placeholder="Pedido" />
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />

        <button name="btn-novo" id="btn-novo" class="btn btn-success float-right">Cadastrar Custo</button>
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-notas">
        <thead>
            <tr>
                <th class='number_format'>Pedido</th>
                <th>Estabelecimento</th>
                <th>Fornecedor</th>
                <th class='date_format'>Data Nota</th>
                <th class='date_format'>&nbsp;</th>
                <th class='date_format'>&nbsp;</th>          
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
    
@endsection
<script>
@section('script-footer')

    $(document).ready( function () {

        $(document).find("#fornecedor").autocomplete(optionsAutoCompleteFornecedor('fornecedor'));

        datepicker_options = {
			format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
            endDate: new Date()
        };

        $('#data_inicio').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') < e.date){
                $('#data_fim').val('');
            }
            $('#data_fim').datepicker('setStartDate', e.date);
        });

        $('#data_fim').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') > e.date){
                $('#data_inicio').val('');
            }
            $('#data_inicio').datepicker('setEndDate', e.date);
        });

        $(document).find("#data_inicio").datepicker(datepicker_options);
        $(document).find("#data_inicio").mask("00/00/0000");
        
        $(document).find("#data_fim").datepicker(datepicker_options);
        $(document).find("#data_fim").mask("00/00/0000");

        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });

        $(document).find('#btn-novo').on('click', function(){

            $.ajax({
                url: "{{ route('valor_custo_nota_produto.modal.novo') }}",
                data: {
                    '_token': '{{ csrf_token() }}'
                },
                method: 'POST',
                success: function(data){
                    createModal('modal_novo', 'Cadastrar custos do pedido', data, 'modal-lg');
                }
            });

        });

        $(document).find("#bt-search-fornecedor-busca").on("click", function(){
            showModalFornecedor($(this).data("route"), "Lista de Fornecedores");
        });

    });

    table_filters_notas = $('#table-filters-notas').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "autoWidth": false,
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
                'targets': 'number_format',
                "className": 'number_format',
            },
            {
                "targets": 'date_format',
                "className": 'date_format',
            },
            {
                "targets": [-1,-2],
                'orderable': false
            }
        ]
    });

    function createBtnEdit($id){

        var $html = "<a href=\"#\" class=\"bt-edit-money\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar custos\" onclick=\"editar_custos('"+$id+"');\"></a>";

        return $html;
    }

    function createBtnDelete($id){

        var $html = "<a href=\"#\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir custos\" onclick=\"deletar_custos('"+$id+"');\"></a>";

        return $html;
    }

    function filterAjax(data_form){
        var $return;
        table_filters_notas.clear().draw();
        $.ajax({
            url: "{{ route('valor_custo_nota_produto.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
            
                    if(callback.response.length > 0){

                        $(document).find('.rodape').show();

                        var data = callback.response;
                        var fields_filter = [];
                        for(var field in data){

                            var temp_field = [
                                data[field].numero_pedido,
                                data[field].estabelecimento,
                                data[field].fornecedor,
                                data[field].data_compra,
                                createBtnEdit(data[field].id),
                                createBtnDelete(data[field].id),
                            ];

                            fields_filter.push(temp_field);

                        }

                        table_filters_notas.rows.add(fields_filter).draw();

                    }
                }
            }
        });
    }

    function optionsAutoCompleteFornecedor($elemento){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('fornecedor.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", $("#" + $elemento).parents('.modal').css('z-index') + 1);
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#" + $elemento).val(ui.item.label);
                return false;
            }
        };
    }

    function returnDadosClienteBusca($dados, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");

    }

    function editar_custos($id){
        $.ajax({
            url: "{{ route('valor_custo_nota_produto.modal.editar') }}",
            data: {
                '_token': '{{ csrf_token() }}',
                'id': $id
            },
            method: 'POST',
            success: function(data){
                createModal('modal_novo', 'Editar custos do pedido', data, 'modal-lg');
            }
        });
    }

    function deletar_custos($id){
        $.ajax({
            url: "{{ route('valor_custo_nota_produto.modal.excluir') }}",
            data: {
                '_token': '{{ csrf_token() }}',
                'id': $id
            },
            method: 'POST',
            success: function(data){
                createModal('modal_novo', 'Excluir custos do pedido', data, 'ads');
            }
        });
    }

    function showModalFornecedor(url, title){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("fornecedor_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosFornecedor($(this));
                        });
                    });
                });
            }
        });
    }

    function returnDadosFornecedor($this){
        if($this.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#fornecedor_search_show").modal("hide");
        $(document).find("#fornecedor").val($this.find("td").eq(1).text() + ' - ' + $this.find("td").eq(3).text());
    }
@endsection
</script>