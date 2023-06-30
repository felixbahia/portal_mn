@extends('layouts.app')
@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-4">
            <div class="input-group">
                {{ Form::text('cliente_nome', '', ['id' => 'cliente_nome', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Cliente']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="content-buttons row">
        <div class="col-xs-12 col-sm-6">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </div>
</form>

@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters">
        <thead>
            <tr>
                <th class="th-reprove"></th>
                <th>Cliente</th>
                <th class='sort-date'>Data</th>
                <th>Titulos</th>
                <th class="tb_number">Valor dos Títulos</th>
                <th class="tb_number">Juros por Mês</th>
                <th class="tb_number">Valor Renegociação</th>
                <th class="tb_number">Parcelas</th>
                <th class="tb_number">Período(dias)</th>
                <th>Motivos</th>
                <th class="th-aprove"></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {
    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });
    $("#btn-create").on("click", function(){
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
            {
                'targets': 'td_acao',
                'class': 'td_acao',
                "orderable": false
            }
        ],
    });
});

function filterAjax(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('aprovacao_renegociacao_titulo.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            
            for (var fields in data.response){
                temp_array = [
                    createBtReprove(data.response[fields]),
                    data.response[fields].cliente,
                    data.response[fields].data,
                    createBtView(data.response[fields]),
                    data.response[fields].valor_titulos,
                    data.response[fields].juros_por_mes,
                    data.response[fields].valor_renegociacao,
                    data.response[fields].parcelas,
                    data.response[fields].periodo_dias,
                    ajusteTamanhoTable(data.response[fields].motivo),
                    createBtAproveProjeto(data.response[fields]),
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

function createBtAproveProjeto(obj){
    var html = "<div class=\"bt-aprove\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Aprovar\" onclick=\"aprovarRenegociacao('"+obj.id+"')\"></div>";
    
    return html;
}

function createBtReprove(obj){ 
    var html = "<div class=\"bt-reprove\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Reprovar\" data-id=\""+obj.id+"\" data-renegociacao_titulos_id=\""+obj.renegociacao_titulos_id+"\" data-cliente=\""+obj.cliente+"\" onclick=\"modalRecusarRenegociacao($(this))\"></div>";

    return html;
}

function aprovarRenegociacao($id){
    $.ajax({
        url: "{{ route('aprovacao_renegociacao_titulo.aprovacao_diretoria') }}", 
        dataType: 'json',
        data: {
            _token: '{{ csrf_token() }}', 
            id: $id
        },
        method: 'POST',
        async: false,
        success: function(callback){
            filterAjax($("#form_filter").serialize());
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
            var dados = callback.responseJSON;
        }
    });
}

function recusarRenegociacao($id){
    $.ajax({
        url: '{{ route('aprovacao_renegociacao_titulo.recusa_diretoria') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}', 
            id: $id
        },
        success: function(callback){
            filterAjax($("#form_filter").serialize());
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
            var dados = callback.responseJSON;
        }
    })
}

function createBtView($value){
    html = "<a href=\"#\" class=\"btn-pedido\" data-toggle='tooltip' data-html='true' data-renegociacao_titulos_id=\""+$value.renegociacao_titulos_id+"\" data-cliente=\""+$value.cliente+"\" title='Visualizar' onclick=\"showDialogRenegociacaoTitulo($(this))\"></a>";

    return html;
}

function showDialogRenegociacaoTitulo($value){
    var title = "Detalhes da Renegociação de Títulos - "+$value.data("cliente");
    var id = $value.data("renegociacao_titulos_id");
    $.ajax({
        url: '{{ route('renegociacao_titulo.modal.renegociacao_titulo') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id: id,
            tipo: 'avaliacao',
        },
        success: function (body){
            createModal('dialog_renegociacao',  title, body, 'modal-lg');
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
        }
    }); 
}

function modalRecusarRenegociacao($value){
    var title = "Detalhes da Renegociação de Títulos - "+$value.data("cliente");
    var renegociacao_titulos_id = $value.data("renegociacao_titulos_id");
    var id = $value.data("id");
    $.ajax({
        url: '{{ route('aprovacao_renegociacao_titulo.modal.recusa_diretoria') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id: id,
            renegociacao_titulos_id: renegociacao_titulos_id,
        },
        success: function (body){
            createModal('dialog_renegociacao',  title, body, '');
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
        }
    }); 
}

function ajusteTamanhoTable($value){
    $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

    return $html;
}
@endsection