@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
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
            <div class="col-lg-2">
                {{ Form::select("estabelecimento", $estabelecimentos, '', ["id" => "estabelecimento", "class"=>"form-control"]) }}
            </div>
            <div class="col-lg-2">
                {{ Form::select("banco", $bancos, '', ["id" => "banco", "class"=>"form-control"]) }}
            </div>
            <div class="col-lg-1">
                {!! Form::select('data_filtro', $data_filtro, '', ['id' => 'data_filtro', 'class' => 'form-control']) !!}
            </div>
            <div class="col-lg-1">
                {{ Form::text('data_inicio', date('d/m/Y', strtotime('-1 week')),['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-1">
                {{ Form::text('data_fim', date('d/m/Y'),['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '20']) }}
            </div>
        </div>
        <div class="row">
            <div class="col-lg-1">
                <div class="form-check">
                    {{ Form::checkbox('armazen', 'value',false, ['id' => 'armazen', 'class' => 'form-check-input']) }}
                    {{ Form::label('armazen', 'Armazém',['class' => 'form-check-label', 'for' => 'armazen']) }}
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    {{ Form::checkbox('intercompany', 'value',false, ['id' => 'intercompany', 'class' => 'form-check-input']) }}
                    {{ Form::label('intercompany', 'Intercompany',['class' => 'form-check-label', 'for' => 'armazen']) }}
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    {{ Form::checkbox('ragazzi', 'value',false, ['id' => 'ragazzi', 'class' => 'form-check-input']) }}
                    {{ Form::label('ragazzi', 'baixas realizadas Ragazzi',['class' => 'form-check-label', 'for' => 'ragazzi']) }}
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
        <table class="table table-striped table-not-edit table-not-view" id="table-filters-index">
            <thead>
                <tr>
                    <th rowspan="2" >Bancos e Contas<br></th>
                    <th rowspan="2" >Valor <br> Principal</th>
                    <th rowspan="2" >Desconto</th>
                    <th rowspan="2" >Devolução</th>
                    <th rowspan="2"  >Cartório</th>
                    <th rowspan="2" >Em dia<br></th>
                    <th colspan="3">Vencido Dias</th>
                    <th rowspan="2" >Antecipados</th>
                    <th rowspan="2" >Juros<br></th>
                    <th rowspan="2" >Multa<br></th>
                    <th rowspan="2" >Total<br> Recebido</th>
                    <th rowspan="2"></th>
                </tr>
                <tr>
   
                    <th class='tb_number'>1 a 10</th>
                    <th class='tb_number'>11 a 30 </th>
                    <th class='tb_number'>Mais de 30</th>
                         
            
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
                "targets": [1,2,3,4,5,6,7,8,9,10,11,12]
            },
        ]
    });
    table_filters_contas.on('draw', function () {
        $(document).find(".bt-view").off("click");
        $(document).find(".bt-view").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
        $(document).find(".bt-modal").off("click");
        $(document).find(".bt-modal").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
    });
});

function filtro(){
    table_filters_contas.clear().draw();
    $form = $("#form_filter");
    $data = $form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('analise_contas_receber.filter')}}',
        type: 'POST',
        data: $data,
        success: function(data){
            var out = [];
            for (var fields in data.response.response){
                out.push([
                    data.response.response[fields].banco,
                    createBtViewPrincipal(data.response.response[fields]),
                    createBtViewDesconto(data.response.response[fields]),
                    createBtViewDevolucao(data.response.response[fields]),
                    createBtViewCartorio(data.response.response[fields]),
                    createBtViewDia(data.response.response[fields]),
                    createBtViewVencido_1(data.response.response[fields]),
                    createBtViewVencido_2(data.response.response[fields]),
                    createBtViewVencido_3(data.response.response[fields]),
                    createBtViewAntecipadas(data.response.response[fields]),
                    createBtViewJuros(data.response.response[fields]),
                    createBtViewMulta(data.response.response[fields]),
                    createBtViewRecebido(data.response.response[fields]),
                    createBtView(data.response.response[fields]),
                ]);
            }
            table_filters_contas.rows.add(out).draw();
            $(table_filters_contas.column(0).footer()).html('total');
            $(table_filters_contas.column(1).footer()).html(data.response.saida.valor_principal);
            $(table_filters_contas.column(2).footer()).html(data.response.saida.desconto);
            $(table_filters_contas.column(3).footer()).html(data.response.saida.devolucao);
            $(table_filters_contas.column(4).footer()).html(data.response.saida.liquidacoes_cartorio);
            $(table_filters_contas.column(5).footer()).html(data.response.saida.titulos_dia);
            $(table_filters_contas.column(6).footer()).html(data.response.saida.liquidacoes_vencido_1);
            $(table_filters_contas.column(7).footer()).html(data.response.saida.liquidacoes_vencido_2);
            $(table_filters_contas.column(8).footer()).html(data.response.saida.liquidacoes_vencido_3);
            $(table_filters_contas.column(9).footer()).html(data.response.saida.liquidacoes_antecipadas);
            $(table_filters_contas.column(10).footer()).html(data.response.saida.juros);
            $(table_filters_contas.column(11).footer()).html(data.response.saida.multa);
            $(table_filters_contas.column(12).footer()).html(data.response.saida.valor_total);
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
    var abertura = $($this).data('abertura');
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter,abertura: abertura},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_contas_receber", title, body, 'modal-lg');
        }
    });
}

function createBtView($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.index') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO CONTAS A RECEBER - "+$this.banco+"' class='bt-view'></a>"
    return html;
}

function createBtViewPrincipal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.index') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO CONTAS A RECEBER - "+$this.banco+"' class='bt-modal'>"+$this.valor_principal+"</a>"
    return html;
}

function createBtViewDesconto($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.showDesconto') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO CONTAS A RECEBER COM DESCONTO - "+$this.banco+"' class='bt-modal'>"+$this.desconto+"</a>"
    return html;
}

function createBtViewDevolucao($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.showDevolucao') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO DEVOLUÇÃO - "+$this.banco+"' class='bt-modal'>"+$this.devolucao+"</a>"
    return html;
}

function createBtViewCartorio($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.index') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO CONTAS A RECEBER - "+$this.banco+"' class='bt-modal'>"+$this.liquidacoes_cartorio+"</a>"
    return html;
}

function createBtViewVencido_1($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.showVencido') }}\" data-filter=\""+$this.filter+"\" data-abertura=\"vencido_1\" data-title='ANALÍTICO CONTAS A RECEBER COM BAIXAS VENCIDAS de 1 A 10 DIAS - "+$this.banco+"' class='bt-modal'>"+$this.liquidacoes_vencido_1+"</a>"
    return html;
}
function createBtViewVencido_2($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.showVencido') }}\" data-filter=\""+$this.filter+"\" data-abertura='vencido_2' data-title='ANALÍTICO CONTAS A RECEBER COM BAIXAS VENCIDAS  de 11 A 30 DIAS- "+$this.banco+"' class='bt-modal'>"+$this.liquidacoes_vencido_2+"</a>"
    return html;
}
function createBtViewVencido_3($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.showVencido') }}\" data-filter=\""+$this.filter+"\" data-abertura='vencido_3' data-title='ANALÍTICO CONTAS A RECEBER COM BAIXAS VENCIDAS MAIS DE 30 DIAS - "+$this.banco+"' class='bt-modal'>"+$this.liquidacoes_vencido_3+"</a>"
    return html;
}

function createBtViewDia($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.showDia') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO CONTAS A RECEBER NO DIA - "+$this.banco+"' class='bt-modal'>"+$this.titulos_dia+"</a>"
    return html;
}

function createBtViewAntecipadas($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.showAntecipadas') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO CONTAS A RECEBER COM BAIXAS ANTECIPADAS - "+$this.banco+"' class='bt-modal'>"+$this.liquidacoes_antecipadas+"</a>"
    return html;
}

function createBtViewJuros($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.showJuros') }}\" data-filter=\""+$this.filter+"\" data-abertura='juros' data-title='ANALÍTICO CONTAS A RECEBER COM JUROS - "+$this.banco+"' class='bt-modal'>"+$this.juros+"</a>"
    return html;
}
function createBtViewMulta($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.showJuros') }}\" data-filter=\""+$this.filter+"\" data-abertura='multa' data-title='ANALÍTICO CONTAS A RECEBER COM MULTA - "+$this.banco+"' class='bt-modal'>"+$this.multa+"</a>"
    return html;
}

function createBtViewTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.index') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO CONTAS A RECEBER - "+$this.banco+"' class='bt-modal'>"+$this.valor_total+"</a>"
    return html;
}

function createBtViewRecebido($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_contas_receber.modal.showRecebido') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO CONTAS A RECEBER - "+$this.banco+"' class='bt-modal'>"+$this.valor_total+"</a>"
    return html;
}
@endsection