@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-3">
                <div class="input-group">
                    {{ Form::text('transportadora', '', ['id' => 'transportadora', 'class' => 'form-control input-label', 'placeholder' => 'Transportadora']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("transportador.index.dialog") }}"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-3">
                {{ Form::text('data_inicio', \Carbon\Carbon::now()->addWeeks(-1)->format('d/m/Y'), ['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-3">
                {{ Form::text('data_fim', \Carbon\Carbon::now()->format('d/m/Y'), ['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-3">
                {{ Form::select("uf_destinatario", $uf, '', ["id" => "uf_destinatario", "class"=>"form-control", 'placeholder' => 'UF Destinatário']) }}
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3">
                {{ Form::select("situacao", ['aprovadas' => 'Aprovadas','reprovadas' => 'Reprovadas','pendentes' => 'Pendentes'], '', ["id" => "situacao", "class"=>"form-control", 'placeholder' => 'Todos']) }}
            </div>
            <div class="col-lg-3">
                {{ Form::select("divergencia", ['com_divergencia' => 'Com Divergência','sem_divergencia' => 'Sem Divergência'], '', ["id" => "divergencia", "class"=>"form-control", 'placeholder' => 'Todos']) }}
            </div>
            <div class="col-lg-3">
                {{ Form::text('fatura', '', ['id' => 'fatura', 'placeholder' => 'Nº Fatura','maxlength' => '20']) }}
            </div>
            <div class="col-lg-3">
                {{ Form::text('nota', '', ['id' => 'nota', 'placeholder' => 'Nº Nota','maxlength' => '20']) }}
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
        <table class="table table-striped table-not-edit table-not-view" id="table-filters-index">
            <thead>
            <tr>
                <th rowspan="2">Transportadora<br></th>
                <th colspan="5" class="border-right text-center atraso-col">Faturas Transportadora</th>
                <th colspan="2">Notas MN</th>
            </tr>
            <tr>
                <th class="notas-col tb_number">Qtd</th>
                <th class="notas-col tb_number">Valor</th>
                <th class="notas-col tb_number">Peso</th>
                <th class="notas-col tb_number">Valor<br/>Notas</th>
                <th class="notas-col tb_number">Notas<br/>Qtd</th>
                <th class="compras-col tb_number">Valor<br/>Notas</th>
                <th class="compras-col tb_number">Peso</th>
                <th class="lancadas-col tb_number">% Frete</th>
                <th class="lancadas-col tb_number">Status</th>
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
$(document).ready( function () {
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
    $(document).find("#bt-view-transportadora").off("click");
    $(document).find("#bt-view-transportadora").on("click", function(event){
        event.stopPropagation();
        modalTransportador();
    });
    $(document).find("#transportadora").autocomplete(optionsAutoCompleteTransportador());
    table_filters_faturas = $('#table-filters-index')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
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
                title: 'Consulta Fatura Transportadora',
                footer: true,
                customize: function ( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
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
            "class": "notas-col tb_number",
            "targets": "notas-col tb_number"
            },
            {
            "class": "compras-col tb_number",
            "targets": "compras-col tb_number"
            },
            {
            "class": "lancadas-col tb_number",
            "targets": "lancadas-col tb_number"
            },
        ]
    });
    table_filters_faturas.on('draw', function () {
        $(document).find(".bt-modal").off("click");
        $(document).find(".bt-modal").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
    });
});



function filtro(){
    $(table_filters_faturas.column(0).footer()).html('');
    $(table_filters_faturas.column(1).footer()).html('');
    $(table_filters_faturas.column(2).footer()).html('');
    $(table_filters_faturas.column(3).footer()).html('');
    $(table_filters_faturas.column(4).footer()).html('');
    $(table_filters_faturas.column(5).footer()).html('');
    $(table_filters_faturas.column(6).footer()).html('');
    $(table_filters_faturas.column(7).footer()).html('');
    $(table_filters_faturas.column(8).footer()).html('');
    table_filters_faturas.draw();
    table_filters_faturas.clear().draw();
    $form = $("#form_filter");
    $data = $form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('consulta_fatura_transportadora.filtro')}}',
        type: 'POST',
        data: $data,
        success: function(data){
            var out = [];
            for (var fields in data.response.saida){
                out.push([
                    "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+data.response.saida[fields].transportadora+"\">"+data.response.saida[fields].transportadora+"</div></div>",
                    createBtViewFatura(data.response.saida[fields]),
                    data.response.saida[fields].valor_fatura,
                    data.response.saida[fields].peso_fatura,
                    data.response.saida[fields].notas_fatura,
                    data.response.saida[fields].notas,
                    createCheckNota(data.response.saida[fields]),
                    createCheckPeso(data.response.saida[fields]),
                    createCheckDivergencia(data.response.saida[fields]),
                    data.response.saida[fields].status,
                ]);
            }
            $(table_filters_faturas.column(0).footer()).html((data.response.total.fatura) ? 'TOTAL' : '');
            $(table_filters_faturas.column(1).footer()).html((data.response.total.fatura) ? createBtViewTotalFatura(data.response.total) : '');
            $(table_filters_faturas.column(2).footer()).html(data.response.total.valor_fatura);
            $(table_filters_faturas.column(3).footer()).html(data.response.total.peso_fatura);
            $(table_filters_faturas.column(4).footer()).html(data.response.total.notas_fatura);
            $(table_filters_faturas.column(5).footer()).html(data.response.total.notas);
            $(table_filters_faturas.column(6).footer()).html(data.response.total.valor_notas);
            $(table_filters_faturas.column(7).footer()).html(data.response.total.peso_notas);
            $(table_filters_faturas.column(8).footer()).html(data.response.total.percentual_frete);
            table_filters_faturas.rows.add(out).draw();
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

function createBtViewFatura($this){
    var html = "<a href=\"#\" data-route=\"{{ route('ocorrencia_entrega.modal.faturas') }}\" data-filter=\""+$this.filters+"\" data-title='FATURAS TRANSPORTADORA - "+$this.transportadora+"' data-total='false' class='bt-modal'>"+$this.fatura+"</a>"
    return html;
}

function createBtViewTotalFatura($this){
    var html = "<a href=\"#\" data-route=\"{{ route('ocorrencia_entrega.modal.faturas') }}\" data-filter=\""+$this.filters+"\" data-title='TODAL DE FATURAS' data-total='true' class='bt-modal'>"+$this.fatura+"</a>"
    return html;
}

function createCheckPeso($this){
    if ($this.peso_notas.substring($this.peso_notas.length - 3,0) == $this.peso_fatura.substring($this.peso_fatura.length - 3,0) && $this.peso_notas.length > 0){
        var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'>" + $this.peso_notas + "<i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>";
    }else if($this.peso_notas.length > 0){
        var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'>" + $this.peso_notas + "<i class='fa fa-times error-icon'  aria-hidden='true'></i></div></div>";
    }else{
        var html = '';
    }
    return html;
}

function createCheckNota($this){
    if ($this.valor_notas.substring($this.valor_notas.length - 3,0) == $this.notas_fatura.substring($this.notas_fatura.length - 3,0) && $this.valor_notas.length > 0){
        var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'>" + $this.valor_notas + " <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>";
    }else if($this.valor_notas.length > 0){
        var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'>" + $this.valor_notas + "<i class='fa fa-times error-icon'  aria-hidden='true'></i></div></div>";
    }else{
        var html = '';
    }
    return html;
}

function createCheckDivergencia($this){
    if($this.divergencia == false && $this.percentual_frete.length > 0){
        var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'>" + $this.percentual_frete +" <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>";
    }else if($this.divergencia == true && $this.percentual_frete.length > 0){
        var html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'>" + $this.percentual_frete +"<i class='fa fa-times error-icon'  aria-hidden='true'></i></div></div>";
    }else{
        var html = "";
    }
    return html;
}


function modalTransportador(){
        $.ajax({
            url: '{{ Route('transportador.index.dialog') }}',
            type: 'POST',
           data: {_token: '{{ csrf_token() }}'},
            success: function(data){
				$(document).find('#modal_busca_transportador').remove();
                createModal('modal_busca_transportador', "Busca de transporadora", data, 'modal-lg');
                var modal = $(document).find("#modal_busca_transportador");
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(){
							returnDadosTransportador($(this), modal);
                        });
                    });
                });
            }

        });
    }
    
    function returnDadosTransportador($dados, modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
		$(document).find("#transportadora").val($dados.find("td:eq(1)").text() + " - " + $dados.find("td:eq(2)").text());
		modal.modal('hide');
    }
    
    function optionsAutoCompleteTransportador(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('transportador.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#form_filter').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma transportadora encontrada');
                    event.stopPropagation();
                    $(document).find("#transportadora").focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#transportadora").val(ui.item.label);
                return false;
            }
        };
    }

    function showModal($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var total = $($this).data('total');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters: filter, total: total},
            method: 'POST',
            success: function(body){
                createModal("modal_faturas_transportadora", title, body, 'modal-lg');
            }
        });
    }
@endsection