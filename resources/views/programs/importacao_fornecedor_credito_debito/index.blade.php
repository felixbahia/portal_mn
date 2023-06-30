@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-12">
            <div class="input-group">
                {{ Form::text('fornecedor_filtro', '', ['id' => 'fornecedor_filtro', 'class' => 'form-control input-label', 'placeholder' => 'Fornecedor']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>

    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters">
        <thead>
            <tr>
                <th>Fornecedor</th>
                <th class="tb_number">Saldo</th>
                <th class="td_acao">Editar</th>
                <th class="td_acao">Excluir</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {
    form_filter = $(document).find('#form_filter');

    form_filter.find("#fornecedor_filtro").autocomplete(optionsAutoCompleteFornecedorFiltro());

    form_filter.find("#bt-search-fornecedor-busca").off("click");
    form_filter.find("#bt-search-fornecedor-busca").on("click", function(){
        showModalFornecedorFiltro($(this).data("route"), "Lista de Fornecedores", "fornecedor_filtro");
    });

    form_filter.find("#btn-filterform").off("click");
    form_filter.find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });

    form_filter.find("#btn-create").off("click");
    form_filter.find("#btn-create").on("click", function(){
        showModalCreate();
    });

    table_filters.destroy();
    table_filters = $('#table-filters').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "autoWidth": false,
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "tb_date", targets: "tb_date" },
        ],
    });
    table_filters.on('draw', function () {
        $(document).find(".bt-edit").off("click");
        $(document).find(".bt-edit").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
        $(document).find(".bt-delete").off("click");
        $(document).find(".bt-delete").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
    });
});

function showModalCreate(){
    $.ajax({
        url: '{{ route('importacao.fornecedor_credito_debito.modal.adicionar') }}',
        method: 'GET',
        success: function(body){
            var title = 'Cadastro de {{ CustomView::programaName() }}';
            createModal('modal_fornecedor_credito_debito_adicionar', title, body, '');
        }
    });
}

function filterAjax(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('importacao.fornecedor_credito_debito.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            
            for (var fields in data.response){
                temp_array = [
                    data.response[fields].fornecedor,
                    data.response[fields].saldo,
                    createBtnEdit("{{ route('importacao.fornecedor_credito_debito.modal.editar') }}", data.response[fields].id),
                    createBtnDelete("{{ route('importacao.fornecedor_credito_debito.modal.deletar') }}", data.response[fields].id)
                ];
                produtos.push(temp_array)
            }
            table_filters.rows.add(produtos).draw();            

        }
    });
}

function filterClear(){
    table_filters.clear().draw();
}

function createBtnEdit($url, $id){
    var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>";
    return $html;
}

function createBtnDelete($url, $id){
    var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Excluir {{ CustomView::programaName() }}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\"></a>";
    return $html;
}

function showModal($this){
    var url = $($this).data("route");
    var $id = $($this).data("id");
    var modal_class = $($this).data("modal");
    var title = $($this).data("title_modal");
    $.ajax({
        url: url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", id: $id},
        success: function(body){
            createModal('modal_fornecedor_credito_debito_edit_delete', title, body, modal_class);
            var modal = $("#modal_fornecedor_credito_debito_edit_delete");
        }
    });
}

function showModalFornecedorFiltro(url, title, campo){
    $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("fornecedor_search_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                    $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                        returnDadosFornecedorFiltro($(this), campo);
                    });
                });
            });
        }
    });
}

function returnDadosFornecedorFiltro($this, campo){
    if($this.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#fornecedor_search_show").modal("hide");
    form_filter.find("#"+campo).val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
}

function optionsAutoCompleteFornecedorFiltro(){
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
            $('.ui-autocomplete').css("z-index", (parseInt($('#form_filter').css('z-index')) + 1));
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Nenhum fornecedor encontrado');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            form_filter.find("#fornecedor_filtro").val(ui.item.label);
            return false;
        }
    };
}
@endsection