@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-sm-4">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="cliente" id="cliente" value="" placeholder="Cliente" maxlength="250" />
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="faccao" id="faccao" value="" placeholder="Facção" maxlength="250" />
                    <span class="input-group-addon border rounded-right" id="bt-search-faccao-busca" data-route="{{ route("faccao.modal.buscar") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                </div>
            </div>
            @if (Auth::user()->tipo_usuario_id != 12 && Auth::user()->tipo_usuario_id != 16)
            <div class="form-group col-lg-3">
                {{ Form::select('representantes', $representantes, '', [ 'id' => 'representantes', 'class' => 'form-control', 'placeholder' => 'Todos Representantes'])}}
            </div>
            @endif
        </div>
        <div class="row">
                <div class="col-lg-3">
                    <input type="text" name="linha" id="linha" placeholder="Linha" value="" maxlength="250">
                </div>
                <div class="col-lg-2">
                    <input type="text" class="data" name="data_inicio" id="data_inicio" placeholder="Data Início DD/MM/AAAA" value="" maxlength="20">
                </div>
                <div class="col-lg-2">
                    <input type="text" class="data" name="data_fim" id="data_fim" placeholder="Data Fim DD/MM/AAAA" value="" maxlength="20">
                </div>
                <div class="col-lg-2">
                    <input type="text" class="text-right" name="chegada_dias" id="chegada_dias" placeholder="Dias para Entrega" value="" maxlength="3">
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
    <table class="table table-striped table-not-view table-filter-programas" id="table-filters-projeto">
        <thead>
            <tr>
                <th>Status</th>
                <th class="tb_number">No Prazo</th>
                <th class="tb_number">Em Atraso</th>
                <th class="tb_number">Total</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number">Total:</th>
                <td id="total_prazo"></td>
                <td id="total_atraso"></td>
                <td id="total_total"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {

    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 100,
        autoHide: true
    });
    $('#data_inicio').on('pick.datepicker', function (e) {
        if($('#data_fim').datepicker('getDate') < e.date){
            $('#data_fim').val('');
        }
        $('#data_fim').datepicker('setStartDate', e.date);
        $('#data_fim').datepicker('update');
    });

    $(document).find("#dias").mask('000');

    $(document).find("#btn-filterform").on("click", function(){
        filterAjax();
    });

    $(document).find("#btn-clearform").on("click", function(){
        filterClear();
    });

    $(document).find("#bt-search-cliente").on('click', function(event){
        event.stopPropagation();
        showModalClienteBusca($(this).data("route"));
        return false;
    });

    $(document).find("#bt-search-faccao-busca").on("click", function(){
        showModalFaccao($(this).data("route"), "Lista de Facções");
    });

    form = $(document).find('#form_filter');
    form.find("#linha").autocomplete(optionsAutoCompleteLinha());
    form.find("#faccao").autocomplete(optionsAutoCompleteFaccao());
    form.find("#cliente").autocomplete(optionsAutoCompleteCliente());
});

table_filters = $('#table-filters-projeto').DataTable({
    "searching": false,
    "lengthChange": false,
    "info": false,
    "pageLength": 15,
    "autoWidth": false,
    "ordering": false,

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
            "targets": [1],
            className: 'mes-futuro-col tb_number'
        },{
            "targets": [2],
            className: 'mes-col tb_number'
        },{
            "targets": [3],
            className: 'atraso-col tb_number'
        },
    ],
});


function filterAjax(){
    filterClear();
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    $.ajax({
        url: '{{ route('analise_sintetica_projeto.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            for (var fields in data.response.dados){
                temp_array = [
                    data.response.dados[fields].descricao,
                    createDialog(data.response.dados[fields].prazo, 'prazo', data.response.filtro, data.response.dados[fields].id,"{{ route('analise_sintetica_projeto.modal.dialog') }}", data.response.dados[fields].descricao),
                    createDialog(data.response.dados[fields].atraso, 'atraso', data.response.filtro, data.response.dados[fields].id,"{{ route('analise_sintetica_projeto.modal.dialog') }}", data.response.dados[fields].descricao),
                    createDialog(data.response.dados[fields].total, 'total', data.response.filtro, data.response.dados[fields].id,"{{ route('analise_sintetica_projeto.modal.dialog') }}", data.response.dados[fields].descricao),
                ];

                table_filters.row.add(temp_array).draw();
            }

            $(document).find('#total_prazo').html(data.response.total.prazo);
            $(document).find('#total_atraso').html(data.response.total.atraso);
            $(document).find('#total_total').html(data.response.total.total);
        }
    });
}

function filterClear(){
    table_filters.clear().draw();

    $(document).find('#total_prazo').html("");
        $(document).find('#total_atraso').html("");
        $(document).find('#total_total').html("");
}

function optionsAutoCompleteLinha(){
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.linha.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
        },
        select: function( event, ui ) {
            setTimeout(function(){
                table_filters.draw();
            }, 100);
        }
    };
}

function optionsAutoCompleteFaccao(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('faccao.autocomplete') }}", request, response);
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
            $(document).find("#faccao").val(ui.item.label);
            $(document).find("#codigo_faccao").val(ui.item.value);
            return false;
        }
    };
}
function optionsAutoCompleteCliente(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('clientes.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){

        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Nenhum cliente encontrado');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#cliente").val(ui.item.label);
            return false;
        }
    };
}
function showModalClienteBusca(url){
    var title = "Busca de Clientes";
    $.ajax({
        url: url,
        method: 'POST',
        data: {
            _token: '{{csrf_token()}}'
        },
        success: function(body){
            $(document).find('#cliente_searsh_show').remove();
            createModal("cliente_searsh_show", title, body, 'modal-lg');
            var modal = $(document).find("#cliente_searsh_show");
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    modal.find('tbody').find("tr").off("click");
                    modal.find('tbody').find("tr").on("click", function(event){
                        returnDadosClienteBusca($(this), event);
                    });
                });
            });
        }
    });
}

function returnDadosClienteBusca($dados, event){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#cliente").val($dados.find("td").eq(1).text()+' - '+$dados.find("td").eq(3).text());
    $(document).find("#cliente_searsh_show").modal("hide");
}

function showModalFaccao(url, title){
    $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("faccao_search_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#faccao_search_show").find('tbody').find("tr").off("click");
                    $(document).find("#faccao_search_show").find('tbody').find("tr").on("click", function(){
                        returnDadosFaccao($(this));
                    });
                });
            });
        }
    });
}

function returnDadosFaccao($this){
    if($this.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#faccao_search_show").modal("hide");
    $(document).find("#faccao").val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
}

function createDialog($value, $tipo, $filtro, $id, $url, $descricao){

    html = "<a href=\"#\" data-url=\""+$url+"\" data-id=\""+$id+"\" data-title=\""+$descricao+" - "+$tipo+"\" data-tipo=\""+$tipo+"\" data-filtro=\""+$filtro+"\" onclick=\"dialog($(this));\">"+$value+"</a>"

    return html;
}

function dialog($this){
    var url = $($this).data("url");
    var id = $($this).data("id");
    var tipo = $($this).data("tipo");
    var filtro = $($this).data("filtro");
    var title = $($this).data("title");
    $.ajax({
        url: url,
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}", 
            id: id,
            tipo: tipo,
            filtro: filtro
        },
        success: function(body){
            createModal('modal_dialog', title, body, "modal-lg");
            var modal = $("#modal_dialog");
        }
    });
}

@endsection
