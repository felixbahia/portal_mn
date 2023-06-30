@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
                {{ Form::select("estabelecimento", $estabelecimentos, '', ["id" => "estabelecimento", "class"=>"form-control"]) }}
            </div>
            <div class="col-lg-2">
                {{ Form::select("banco", $bancos, '', ["id" => "banco", "class"=>"form-control"]) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_inicio', date('d/m/Y', strtotime('-1 week')),['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Vencimento Início DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_fim', date('d/m/Y'),['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Vencimento Fim DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    {{ Form::checkbox('armazen', 'value',false, ['id' => 'armazen', 'class' => 'form-check-input']) }}
                    {{ Form::label('armazen', 'Armazém',['class' => 'form-check-label', 'for' => 'armazen']) }}
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    {{ Form::checkbox('intercompany', 'value',false, ['id' => 'intercompany', 'class' => 'form-check-input']) }}
                    {{ Form::label('intercompany', 'intercompany',['class' => 'form-check-label', 'for' => 'armazen']) }}
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    {{ Form::checkbox('sem_juros', 'value',false, ['id' => 'sem_juros', 'class' => 'form-check-input']) }}
                    {{ Form::label('sem_juros', 'Sem Juros',['class' => 'form-check-label', 'for' => 'armazen']) }}
                </div>
            </div>
        </div>
        <div class="row">
            @if ($check_gerentes === true || $check_supervisores === true || $check_vendedor_representante === true)
            @if($check_gerentes === true)
            <div class="form-group col-lg-2">
                {{ Form::select('gerentes', $gerentes, '', ["id" => 'gerentes', 'class' => 'form-control busca_left', 'placeholder' => 'Gerentes'])}}
            </div>
            @endif
            @if($check_vendedor_representante === true)
            <div class="form-group col-lg-2">
                {{ Form::select('vendedor_representante', $vendedor_representante, '', ["id" => 'vendedor_representante', 'class' => 'form-control', 'placeholder' => 'Vendedor Interno / Representantes'])}}
            </div>
            @endif
            @endif
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
    <table class="table table-striped table-not-edit table-not-view" id="table-filters-index">
        <thead>
            <tr>
                <th>Estabelecimento<br></th>
                <th class='tb_number'>Titulos</th>
                <th class='tb_number tb_icones'>Pagos</th>
                <th class='tb_number'>Cancelados</th>
                <th class='tb_number'>Adiantado</th>
                <th class='tb_number'>Adiantado %</th>
                <th class='tb_number'>Atraso</th>
                <th class='tb_number'>Atraso %</th>
                <th class='tb_number'>Juros Pagos</th>
                <th class='tb_number'>Renegociados</th>
                <th class='tb_number'>Inadimplência</th>
                <th class='tb_number'>Inadimplência %</th>
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
            <td></td>
            <td></td>
        </tfoot>    
    </table>
</div>
@endsection

@section('script-footer')
$(document).ready( function () {
    $(document).find(".valor").maskMoney({thousands:'.', decimal:','});
    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        autoHide: true
    });
    $('#btn-filterform').on('click', function(){
        filtro();
    });
    $(document).find(".busca_left").on('change', function(event){
        var campos = $(document).find("select:visible");
        var indice = campos.index(event.target) + 1;
        var seletor = $(campos[indice]);
        checkDadosUser(seletor, $(this).val());
    });
    table_filters_contas = $('#table-filters-index')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 20,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '',
                footer: true,
                customize: function ( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('c[r=G7] t', sheet).attr( 's', '0' );
                },
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                        }
                    }
                },
            },
        ],
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
                "class": "tb_number", 
                "targets": "tb_number"
            },
            {
                "targets": "tb_icones",
                "width": "100px"
            },
        ]
    });
    table_filters_contas.on('draw', function () {
        $(document).find(".bt-modal").off("click");
        $(document).find(".bt-modal").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });

        $(document).find(".bt-view").off("click");
        $(document).find(".bt-view").on("click", function(event){
            event.stopPropagation();
            showModalDatas($(this));
        });
    });
});

function filtro(){

    table_filters_contas.clear().draw();
    $(table_filters_contas.column(0).footer()).html('');
    $(table_filters_contas.column(1).footer()).html('');
    $(table_filters_contas.column(2).footer()).html('');
    $(table_filters_contas.column(3).footer()).html('');
    $(table_filters_contas.column(4).footer()).html('');
    $(table_filters_contas.column(5).footer()).html('');
    $(table_filters_contas.column(6).footer()).html('');
    $(table_filters_contas.column(7).footer()).html('');
    $(table_filters_contas.column(8).footer()).html('');
    $(table_filters_contas.column(9).footer()).html('');
    $(table_filters_contas.column(10).footer()).html('');
    $(table_filters_contas.column(11).footer()).html('');
    
    $form = $("#form_filter");
    $data = $form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('analise_inadimplencia.filter')}}',
        type: 'POST',
        data: $data,
        success: function(data){
            var out = [];
            for (var fields in data.response.response){
                out.push([
                    data.response.response[fields].estabelecimento,
                    createBtViewEmitidos(data.response.response[fields]),
                    createBtViewPagos(data.response.response[fields]),
                    createBtViewCancelado(data.response.response[fields]),
                    createBtViewAdiantado(data.response.response[fields]),
                    data.response.response[fields].percentual_adiantado,
                    createBtViewAtraso(data.response.response[fields]),
                    data.response.response[fields].percentual_atraso,
                    data.response.response[fields].juros_pagos,
                    createBtViewRenegociados(data.response.response[fields]),
                    createBtViewInadimplencia(data.response.response[fields]),
                    data.response.response[fields].percentual_inadimplencia,
                ]);
            }
            $(table_filters_contas.column(0).footer()).html('total');
            $(table_filters_contas.column(1).footer()).html(createBtViewEmitidos(data.response.saida));
            $(table_filters_contas.column(2).footer()).html(createBtViewPagos(data.response.saida));
            $(table_filters_contas.column(3).footer()).html(createBtViewCancelado(data.response.saida));
         
            $(table_filters_contas.column(4).footer()).html(createBtViewAdiantado(data.response.saida));
            $(table_filters_contas.column(5).footer()).html(data.response.saida.percentual_adiantado);
            $(table_filters_contas.column(6).footer()).html(createBtViewAtraso(data.response.saida));
            $(table_filters_contas.column(7).footer()).html(data.response.saida.percentual_atraso);
            $(table_filters_contas.column(8).footer()).html(data.response.saida.juros_pagos);
            $(table_filters_contas.column(9).footer()).html(createBtViewRenegociados(data.response.saida));
            $(table_filters_contas.column(10).footer()).html(createBtViewInadimplencia(data.response.saida));
            $(table_filters_contas.column(11).footer()).html(data.response.saida.percentual_inadimplencia);

            table_filters_contas.rows.add(out).draw();

            $('[data-toggle="popover"]').off('show.bs.popover');
            $('[data-toggle="popover"]').popover('hide');
    
            $('[data-toggle="popover"]').popover({
                container: 'body',
                html: true,
                show: true,
                trigger: 'hover',
                placement: 'right',
                template: '<div class="popover popover-estoque" role="popover"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
            });

        },
        error: function(callback){
            if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                $.each(data, function(index, el) {
                    $form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                    $form.find('input[name="'+index+'"]').eq(0).addClass('error');
                });
                $form.find('input.error').eq(0).focus();
            }
        }
    }).always(function() {
        hide_loader();
    });
}

function showModal($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var renegociado = $($this).data('renegociado');
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter,renegociado:renegociado},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_inadimplencia_emitidos", title, body, 'modal-lg');
        }
    });
}

function showModalDatas($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_inadimplencia_emitidos", title, body, '');
        }
    });
}

function createBtViewEmitidos($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_inadimplencia.modal.emitidos') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO TITULOS LANÇADOS' class='bt-modal'>"+$this.emitidos+"</a>"
    return html;
}

function createBtViewPagos($this){
    var html = '';

    if($this.pagos){
        html = "<a href=\"#\" data-route=\"{{ route('analise_inadimplencia.modal.dia_a_dia') }}\" data-filter=\""+$this.filter+"\" data-toggle='tooltip' data-original-title='Análise dia a dia de títulos pagos' data-title='"+$this.estabelecimento+" - ANALISE DIA A DIA DE TITULOS PAGOS' class='bt-view float-left'></a><a href=\"#\" data-route=\"{{ route('analise_inadimplencia.modal.pagos') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO TITULOS PAGOS' class='bt-modal'>"+$this.pagos+"</a>"
    }
    return html;
}
function createBtViewRenegociados($this){

    var html = "<a href=\"#\" data-route=\"{{ route('analise_inadimplencia.modal.pagos') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO TITULOS Renegociados' data-renegociado ='true' class='bt-modal'>"+$this.renegociado+"</a>"

    return html;
}

function createBtViewCancelado($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_inadimplencia.modal.cancelados') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO TITULOS CANCELADOS' class='bt-modal'>"+$this.cancelado+"</a>"
    return html;
}

function createBtViewAtraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_inadimplencia.modal.atraso') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO TITULOS VENCIDOS' class='bt-modal'>"+$this.pago_atraso+"</a>"
    return html;
}

function createBtViewAdiantado($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_inadimplencia.modal.adiantado') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO TITULOS PAGOS ADIANTADOS' class='bt-modal'>"+$this.pago_adiantado+"</a>"
    return html;
}

function createBtViewInadimplencia($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_inadimplencia.modal.inadimplencia') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO TITULOS EM INADIMPLÊNCIA' class='bt-modal'>"+$this.inadimplencia+"</a>"
    return html;
}

@if ($check_gerentes === true || $check_vendedor_representante === true)
function checkDadosUser(campo_busca, valor){
    
    primeira_opcao = $(campo_busca).find("option:first").html();
    $(campo_busca).html("");
    var campos = "<option value=\"\">" + primeira_opcao + "</option>";
    $.ajax({
        url: "{{ route('usuario.dados_subordinados_outros') }}",
        dataType: 'json',
        data: {_token:'{{ csrf_token() }}', user: valor},
        method: 'POST',
        success: function(callback){
            if(callback.status === "success"){
                var response = callback.response;
                for(var line in response){
                    campos += "<option value=\""+response[line].id+"\">"+response[line].name+"</option>";
                }
            }
        },
        error: function(data){
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }
    }).done(function(){
        $(campo_busca).html(campos).focus();
    });
}
@endif

@endsection