@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-3">
                <div class="input-group">
                    {{ Form::text('fornecedor_filtro', '', ['id' => 'fornecedor_filtro', 'class' => 'form-control input-label', 'placeholder' => 'Fornecedor']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-3">
                {!! Form::select('status', $status, '', ['id' => 'status', 'class' => 'form-control', 'placeholder' => 'Todos Status']) !!}
            </div>
            <div class="col-lg-3">
                {!! Form::text('data_inicio_entrega', '', ['id' => 'data_inicio_entrega', 'class' => 'form-control data', 'placeholder' => 'Data Início DD/MM/AAAA']) !!}
            </div>
            <div class="col-lg-3">
                {!! Form::text('data_fim_entrega', '', ['id' => 'data_fim_entrega', 'class' => 'form-control data', 'placeholder' => 'Data Fim DD/MM/AAAA']) !!}
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3">
                <div class="input-group">
                    {!! Form::text('codigo_produto', '', ['id' => 'codigo_produto', 'class' => 'form-control input-label', 'placeholder' => 'Código do Produto']) !!}
                    <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
                </div>
            </div>            
            <div class="col-lg-3">
                {!! Form::text('descricao_produto', '', ['id' => 'descricao_produto', 'class' => 'form-control', 'placeholder' => 'Descrição do Produto']) !!}
            </div>
            <div class="col-lg-3">
                {!! Form::text('pcmn', '', ['id' => 'pcmn', 'class' => 'form-control', 'placeholder' => 'PCMN']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('proforma', '', ['id' => 'proforma', 'class' => 'form-control', 'placeholder' => 'Proforma']) !!}
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    {{ Form::checkbox('atrasado', 'value',false, ['id' => 'atrasado', 'class' => 'form-check-input']) }}
                    {{ Form::label('atrasado', 'Atrasado',['class' => 'form-check-label', 'for' => 'atrasado']) }}
                </div>
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped table-not-view" id="table-filters_importacao">
        <thead>
            <tr>
                <th>Fornecedor</th>
                <th class="tb_number_150">Em Produção</th>
                <th class="tb_number_150">Carga Pronta</th>
                <th class="tb_number_150">Embarque</th>
                <th class="tb_number_150">Chegada no Porto</th>
                <th class="tb_number_150">DI</th>
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

    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        autoHide: true
    });

    form_filter.find("#fornecedor_filtro").autocomplete(optionsAutoCompleteFornecedorFiltro());
    form_filter.find("#descricao_produto").autocomplete(optionsAutoComplete("nome"));
    
    form_filter.find("#bt-search-produto").off('click');
    form_filter.find("#bt-search-produto").on('click', function(){
        showModalProduto(form_filter);
    });

    form_filter.find("#bt-search-fornecedor-busca").off("click");
    form_filter.find("#bt-search-fornecedor-busca").on("click", function(){
        showModalFornecedorFiltro($(this).data("route"), "Lista de Fornecedores", "fornecedor_filtro");
    });

    form_filter.find("#btn-filterform").off("click");
    form_filter.find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });

    table_filters_importacao = $('#table-filters_importacao').DataTable({
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
            { "class": "tb_number_150", type: 'num-fmt', targets: "tb_number_150" },
            { "class": "tb_date", targets: "tb_date" },
        ],
    });
});

function filterAjax(){
    form_filter = $(document).find("#form_filter");
    data_form_filter = form_filter.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('importacao.fornecedor_credito_debito.consulta.filtro_consulta')}}',
        data: data_form_filter,
        method: 'POST',
        success: function(data){
            importacaos = [];
            
            for (var fields in data.response){
                temp_array = [
                    data.response[fields].fornecedor,
                    createBtViewStatus(data.response[fields], "em_producao", data.response[fields].em_producao_inteiro, "Em Produção", data.response[fields].atrasado),
                    createBtViewStatus(data.response[fields], "carga_pronta", data.response[fields].carga_pronta_inteiro, "Carga Pronta", data.response[fields].atrasado),
                    createBtViewStatus(data.response[fields], "embarque_etd", data.response[fields].embarque_etd_inteiro, "Embarque", data.response[fields].atrasado),
                    createBtViewStatus(data.response[fields], "chegada_porto_eta", data.response[fields].chegada_porto_eta_inteiro, "Chegada no Porto", data.response[fields].atrasado),
                    createBtViewStatus(data.response[fields], "di", data.response[fields].di_inteiro, "DI", data.response[fields].atrasado),
                ];
                importacaos.push(temp_array)
            }
            table_filters_importacao.rows.add(importacaos).draw();            

        }
    });
}

function filterClear(){
    table_filters_importacao.clear().draw();
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
            $('.ui-autocomplete').css("z-index", (parseInt($('#form_filter').css('z-index')) + 1));
        },
        select: function( event, ui ) {
        }
    };
}

function showModalProduto(form_filter){
    $.ajax({
        url: '{{ route('produto.modal_pesquisa') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function (data){
            createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
            table_filters_produtos_busca.on('draw', function () {

                $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                    returnDadosProduto($(this), form_filter);
                });

            });
        }
    });
}

function returnDadosProduto($dados, form){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#modal_search_produto").modal("hide");
    
    form_filter.find('#codigo_produto').val($dados.find("td").eq(1).text());
}

function createBtViewStatus($dados, status, valor, status_exibicao, atrasado){
    html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-codigo_fornecedor=\""+$dados.fornecedor_codigo+"\" data-status=\""+status+"\" data-atrasado=\""+atrasado+"\" title='Visualizar' data-title=\"Importacao - "+$dados.fornecedor+" Status: "+status_exibicao+"\" onclick=\"abriModalCompras($(this))\">"+valor+"</a>";
    
    return html;
}

function abriModalCompras($this){
    var codigo_fornecedor = $($this).data("codigo_fornecedor");
    var status = $($this).data("status");
    var atrasado = $($this).data("atrasado");
    var title = $($this).data("title");
    $.ajax({
        url: '{{ route('importacao.fornecedor_credito_debito.modal.importacao_por_status') }}',
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            codigo_fornecedor: codigo_fornecedor,
            atrasado: atrasado,
            status: status,
        },
        success: function(body){
            createModal('modal_compras', title, body, "modal-lg");
            var modal = $("#modal_compras");
        }
    });
}
@endsection