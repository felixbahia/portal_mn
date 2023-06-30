@extends('layouts.app')

@section('content-filter')

<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <div class="content-fields">
        <div class="row">
            <div class="form-group col-lg-2 col-xl-3">
                {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Estabelecimentos'])}}
            </div>
            <div class="form-group col-lg-2 col-xl-3">
                {{ Form::select('estados', $estados, '', ['id' => 'estados', 'class' => 'form-control', 'placeholder' => 'Estados']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-3">
                {{ Form::text('nota', '', ['id' => 'nota', 'class' => 'form-control', 'placeholder' => 'Nota Fiscal']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-3">
                {{ Form::text('operacao', '', ['id' => 'operacao', 'class' => 'form-control', 'placeholder' => 'Natureza de Operação']) }}
            </div>
        </div>
        <div class="row">
            <div class="form-group col-lg-2 col-xl-3">
                <div class="input-group">
                    {{ Form::text('transportador', '', ['id' => 'transportador', 'class' => 'form-control input-label', 'placeholder' => 'Transportador']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-transportadora-busca" data-route="{{ route("transportador.index.dialog") }}"><i class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-2 col-xl-3">
                {{ Form::text('data_inicio', date('01/m/Y'),['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-2 col-xl-3">
                {{ Form::text('data_fim', date('d/m/Y'),['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '20']) }}
            </div>
        </div>
        <div class="row">    
            <div class="col-lg-1">
                <div class="form-check">
                    {{ Form::checkbox('frete_duplicado', 'value',false, ['id' => 'frete_duplicado', 'class' => 'form-check-input']) }}
                    {{ Form::label('frete_duplicado', 'Frete Duplicado',['class' => 'form-check-label', 'for' => 'frete_duplicado']) }}
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-check">
                    {{ Form::checkbox('divergencia_transportadora', 'value',false, ['id' => 'divergencia_transportadora', 'class' => 'form-check-input']) }}
                    {{ Form::label('divergencia_transportadora', 'Transportadora Divergente',['class' => 'form-check-label', 'for' => 'divergencia_transportadora']) }}
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
<div class="content-table notas-importadas">
    <table class="table table-striped" id="table-filters-frete">
        <thead>
            <tr>
                <th rowspan="2">Transportadora</th>
                <th colspan="3">Notas MN</th>
                <th colspan="4"><center>Nota Transportadora</center></th>
                <th colspan="4"><center>Lançadas</center></th>
            </tr>
            <tr>
                <th>QTDE Notas</th>
                <th>Valor</th>
                <th>Peso Bruto</th>

                <th>QTDE Notas</th>
                <th>Valor</th>
                <th>Peso Bruto</th>
                <th>Custo Peso</th>

                <th>QTDE Notas</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tfoot>    
    </table>
</div>
@endsection

@section('script-footer')
$(document).ready(function(){
    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        autoHide: true
    });
    $("#transportador").autocomplete(optionsAutoComplete("transportador"));
    $("#btn-filterform").on("click", function(){
        filterAjax($("#form_filter").serialize());
    });

    $(document).find("#bt-search-transportadora-busca").off("click");
    $(document).find("#bt-search-transportadora-busca").on("click", function(event){
        event.stopPropagation();
        showModalTransportadoraBusca($(this).data("route"));
        return false;
    });

    $(document).find("#bt-search-transportadora-natureza-operacao").off("click");
    $(document).find("#bt-search-transportadora-natureza-operacao").on("click", function(event){
        event.stopPropagation();
        showModalNaturezaOperacao($(this).data("route"));
        return false;
    });

    table_filters = $('#table-filters-frete')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "scrollCollapse": true,
        "orderMulti": false,
        "pageLength": 15,
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
                "class": "tb_number frete-notas-col",
                "targets": [1,2,3]
            },
            {
            "class": "tb_number frete-transportadora-col",
            "targets": [4,5,6,7]
            },
            {
            "class": "tb_number frete-lancadas-col",
            "targets": [8,9]
            },
            {
                "width": "20%", 
                "targets": 0
            },
        ]
    });

    table_filters.on('draw', function () {
        $(document).find(".abertura-lancadas").off("click");
        $(document).find(".abertura-lancadas").on("click", function(event){
            event.stopPropagation();
            aberturaTitulosLancados($(this));
        });
        $(document).find(".abertura-emitidos").off("click");
        $(document).find(".abertura-emitidos").on("click", function(event){
            event.stopPropagation();
            aberturaTitulosLancados($(this));
        });
        $(document).find(".abertura-notas").off("click");
        $(document).find(".abertura-notas").on("click", function(event){
            event.stopPropagation();
            aberturaNotas($(this));
        });
    });
});

function filterAjax(data_form){
    table_filters.clear().draw();
    $(table_filters.column(0).footer()).html('');
    $(table_filters.column(1).footer()).html('');
    $(table_filters.column(2).footer()).html('');
    $(table_filters.column(3).footer()).html('');
    $(table_filters.column(4).footer()).html('');
    $(table_filters.column(5).footer()).html('');
    $(table_filters.column(6).footer()).html('');
    $(table_filters.column(7).footer()).html('');
    $(table_filters.column(8).footer()).html('');
    $(table_filters.column(9).footer()).html('');
    $.ajax({
        url: "{{ route('consulta_transportadora_frete.filter') }}",
        dataType: 'json',
        data: data_form,
        method: 'POST',
        success: function(callback){
            var data = callback.response.response;
            var fields_filter = [];
            for(var field in data){
                var temp_field = [
                    "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data[field].transportadora_notas + "''>" + data[field].transportadora_notas + "</div></div>",
                    createBtViewTransportadora(data[field]),
                    data[field].transportadora_valor,
                    data[field].transportadora_peso,
                    createBtViewTitulosEmitidos(data[field]),
                    data[field].emitidas_valor,
                    data[field].emitidas_peso,
                    data[field].emitidas_custo,
                    createBtViewTitulosLancado(data[field]),
                    data[field].lancadas_valor,
                ];
                fields_filter.push(temp_field);
            }
            $(table_filters.column(0).footer()).html('total');
            $(table_filters.column(1).footer()).html(callback.response.total.transportadora_quantidade);
            $(table_filters.column(2).footer()).html(callback.response.total.transportadora_valor);
            $(table_filters.column(3).footer()).html(callback.response.total.transportadora_peso);
            $(table_filters.column(4).footer()).html(callback.response.total.emitidas_quantidade);
            $(table_filters.column(5).footer()).html(callback.response.total.emitidas_valor);
            $(table_filters.column(6).footer()).html(callback.response.total.emitidas_peso);
            $(table_filters.column(7).footer()).html(callback.response.total.emitidas_custo);
            $(table_filters.column(8).footer()).html(callback.response.total.lancadas_quantidade);
            $(table_filters.column(9).footer()).html(callback.response.total.lancadas_valor);
            table_filters.rows.add(fields_filter).draw().nodes();
        }
    });
}

function showModalTransportadoraBusca(url){
    var title = "Busca Transportadoras";
    $.ajax({
        url: url,
        method: 'POST',
        data: {
            _token: '{{csrf_token()}}'
        },
        success: function(body){
            $(document).find('#transportadora_searsh_show').remove();
            createModal("transportadora_searsh_show", title, body, 'modal-lg');
            var modal = $(document).find("#transportadora_searsh_show");
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    modal.find('tbody').find("tr").off("click");
                    modal.find('tbody').find("tr").on("click", function(event){
                        returnDadosTransportadoraBusca($(this), event);
                    });
                });
            });
        }
    });
}

function returnDadosTransportadoraBusca($dados, event){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#transportador").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(2).text());
    $(document).find("#transportadora_searsh_show").modal("hide");
}

function optionsAutoComplete($name){
    return {
        source: function (request, response) {
            request.name = $name;
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('transportador.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3,
        select: function( event, ui ) {
            $(document).find("#transportador").val(ui.item.label);
            setTimeout(function(){
                table_filters.draw();
            }, 100);
            return false;
        }
    };
}

function createBtViewTitulosEmitidos($this){
    var html = "<a href=\"#\" data-route=\"{{ route('consulta_transportadora_frete.modal.emitidas') }}\" data-filter=\""+$this.filter+"\" data-title=\"Notas Transportadora - "+$this.transportadora_notas+"\" class='abertura-emitidos'>"+$this.emitidas_quantidade+"</a>"
    return html;
}

function createBtViewTransportadora($this){
    var html = "<a href=\"#\" data-route=\"{{ route('consulta_transportadora_frete.modal.notas') }}\" data-filter=\""+$this.filter+"\" data-title=\"Notas MN - "+$this.transportadora_notas+"\" class='abertura-notas'>"+$this.transportadora_quantidade+"</a>"
    return html;
}

function createBtViewTitulosLancado($this){
    var html = "<a href=\"#\" data-route=\"{{ route('consulta_transportadora_frete.modal.lancadas') }}\" data-filter=\""+$this.filter+"\" data-title=\"Notas Lançadas - "+$this.transportadora_notas+"\" class='abertura-lancadas'>"+$this.lancadas_quantidade+"</a>"
    return html;
}

function aberturaTitulosLancados($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter},
        method: 'POST',
        success: function(body){
            createModal("model_notas_lancadas", title, body, 'modal-lg');
        }
    });
}

function aberturaTitulosEmitidos($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter},
        method: 'POST',
        success: function(body){
            createModal("model_notas_emitidos", title, body, 'modal-lg');
        }
    });
}

function aberturaNotas($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter},
        method: 'POST',
        success: function(body){
            createModal("model_notas_mn", title, body, 'modal-lg');
        }
    });
}

@endsection
