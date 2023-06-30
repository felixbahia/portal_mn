@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-3">
                <div class="input-group">
                    {{ Form::text('fornecedor', '', ['id' => 'fornecedor', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Fornecedor', 'maxlength' => '200']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route('fornecedor.busca.index') }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_inicio', '', ['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_fim', '', ['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '20']) }}
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
                <th rowspan="2">Fornecedor<br></th>
                <th colspan="3" class="border-right text-center atraso-col">Pedidos em Aberto no Prazo</th>
                <th colspan="3" class="border-right text-center atraso-col">Pedidos em Aberto Atraso</th>
                <th colspan="3" class="border-right text-center atraso-col">Pedidos Entregues no Prazo</th>
                <th colspan="3">Pedidos Entregues em Atraso</th>
            </tr>
            <tr>
                <th class="lancadas-col tb_number">Qtd</th>
                <th class="lancadas-col tb_number">Valor</th>
                <th class="lancadas-col tb_number">%</th>
                <th class="compras-sem-col tb_number">Qtd</th>
                <th class="compras-sem-col tb_number">Valor</th>
                <th class="compras-sem-col tb_number">%</th>
                <th class="compras-col tb_number">Qtd</th>
                <th class="compras-col tb_number">Valor</th>
                <th class="compras-col tb_number">%</th>
                <th class="mes-col tb_number">Qtd</th>
                <th class="mes-col tb_number">Valor</th>
                <th class="mes-col tb_number">%</th>
            </tr>
        </thead>
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
    $(document).find("#fornecedor").val('');
    $(document).find("#fornecedor").autocomplete(optionsAutoCompleteFornecedor());
    $(document).find("#bt-search-fornecedor-busca").on("click", function(){
        showModalFornecedor($(this).data("route"), "Lista de Fornecedores");
    });
    table_filters_fornecedores = $('#table-filters-index')
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
                title: 'Análise Performance Fornecedor',
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
            {
            "class": "compras-sem-col tb_number",
            "targets": "compras-sem-col tb_number"
            },
            {
            "class": "mes-col tb_number",
            "targets": "mes-col tb_number"
            },
        ]
    });

    table_filters_fornecedores.on('draw', function () {
        $(document).find(".bt-modal").off("click");
        $(document).find(".bt-modal").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
    });
});





function filtro(){
    $(table_filters_fornecedores.column(0).footer()).html('');
    $(table_filters_fornecedores.column(1).footer()).html('');
    $(table_filters_fornecedores.column(2).footer()).html('');
    $(table_filters_fornecedores.column(3).footer()).html('');
    $(table_filters_fornecedores.column(4).footer()).html('');
    $(table_filters_fornecedores.column(5).footer()).html('');
    $(table_filters_fornecedores.column(6).footer()).html('');
    $(table_filters_fornecedores.column(7).footer()).html('');
    $(table_filters_fornecedores.column(8).footer()).html('');
    $(table_filters_fornecedores.column(9).footer()).html('');
    $(table_filters_fornecedores.column(10).footer()).html('');
    $(table_filters_fornecedores.column(11).footer()).html('');
    $(table_filters_fornecedores.column(12).footer()).html('');
    table_filters_fornecedores.draw();
    table_filters_fornecedores.clear().draw();
    $form = $("#form_filter");
    $data = $form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('analise_performance_fornecedor.filtro')}}',
        type: 'POST',
        data: $data,
        success: function(data){
            var out = [];
            for (var fields in data.response.pedidos){
                out.push([
                    "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+data.response.pedidos[fields].fornecedor+"\">"+data.response.pedidos[fields].fornecedor+"</div></div>",
                    createBtViewPedidosAbertosPrazo(data.response.pedidos[fields]),
                    data.response.pedidos[fields].valor_aberto_prazo,
                    data.response.pedidos[fields].percentual_quantidade_aberto_prazo,
                    createBtViewPedidosAbertosAtraso(data.response.pedidos[fields]),
                    data.response.pedidos[fields].valor_aberto_atraso,
                    data.response.pedidos[fields].percentual_quantidade_aberto_atraso,
                    createBtViewPedidosEntreguesPrazo(data.response.pedidos[fields]),
                    data.response.pedidos[fields].valor_entregue_prazo,
                    data.response.pedidos[fields].percentual_quantidade_entregue_prazo,
                    createBtViewPedidosEntreguesAtraso(data.response.pedidos[fields]),
                    data.response.pedidos[fields].valor_entregue_atraso,
                    data.response.pedidos[fields].percentual_quantidade_entregue_atraso,
                ]);
            }
            $(table_filters_fornecedores.column(0).footer()).html((data.response.total.quantidade_aberto_prazo) ? 'TOTAL' : '');
            $(table_filters_fornecedores.column(1).footer()).html((data.response.total.quantidade_aberto_prazo) ? createBtViewPedidosAbertosPrazoTotal(data.response.total) : '');
            $(table_filters_fornecedores.column(2).footer()).html(data.response.total.valor_aberto_prazo);
            $(table_filters_fornecedores.column(3).footer()).html(data.response.total.percentual_quantidade_aberto_prazo);
            $(table_filters_fornecedores.column(4).footer()).html((data.response.total.quantidade_aberto_atraso) ? createBtViewPedidosAbertosAtrasoTotal(data.response.total) : '');
            $(table_filters_fornecedores.column(5).footer()).html(data.response.total.valor_aberto_atraso);
            $(table_filters_fornecedores.column(6).footer()).html(data.response.total.percentual_quantidade_aberto_atraso);
            $(table_filters_fornecedores.column(7).footer()).html((data.response.total.quantidade_entregue_prazo) ? createBtViewPedidosEntreguesPrazoTotal(data.response.total) : '');
            $(table_filters_fornecedores.column(8).footer()).html(data.response.total.valor_entregue_prazo);
            $(table_filters_fornecedores.column(9).footer()).html(data.response.total.percentual_quantidade_entregue_prazo);
            $(table_filters_fornecedores.column(10).footer()).html((data.response.total.quantidade_entregue_prazo) ? createBtViewPedidosEntreguesAtrasoTotal(data.response.total) : '');
            $(table_filters_fornecedores.column(11).footer()).html(data.response.total.valor_entregue_atraso);
            $(table_filters_fornecedores.column(12).footer()).html(data.response.total.percentual_quantidade_entregue_atraso);
            table_filters_fornecedores.rows.add(out).draw();
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

function createBtViewPedidosAbertosPrazo($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_performance_fornecedor.modal.pedidos_abertos_prazo') }}\" data-filter=\""+$this.filters+"\" data-title='PEDIDOS ABERTOS NO PRAZO - "+$this.fornecedor+"' data-total='false' class='bt-modal'>"+$this.quantidade_aberto_prazo +"</a>"
    return html;
}

function createBtViewPedidosAbertosPrazoTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_performance_fornecedor.modal.pedidos_abertos_prazo') }}\" data-filter=\""+$this.filters+"\" data-title='PEDIDOS ABERTOS NO PRAZO' data-total='true' class='bt-modal'>"+$this.quantidade_aberto_prazo +"</a>"
    return html;
}

function createBtViewPedidosAbertosAtraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_performance_fornecedor.modal.pedidos_abertos_atraso') }}\" data-filter=\""+$this.filters+"\" data-title='PEDIDOS ABERTOS ATRASADO- "+$this.fornecedor+"' data-total='false' class='bt-modal'>"+$this.quantidade_aberto_atraso +"</a>"
    return html;
}

function createBtViewPedidosAbertosAtrasoTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_performance_fornecedor.modal.pedidos_abertos_atraso') }}\" data-filter=\""+$this.filters+"\" data-title='PEDIDOS ABERTOS ATRASADO' data-total='true' class='bt-modal'>"+$this.quantidade_aberto_atraso +"</a>"
    return html;
}

function createBtViewPedidosEntreguesPrazo($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_performance_fornecedor.modal.pedidos_entregues_prazo') }}\" data-filter=\""+$this.filters+"\" data-title='PEDIDOS ENTREGUES NO PRAZO - "+$this.fornecedor+"' data-total='false' class='bt-modal'>"+$this.quantidade_entregue_prazo +"</a>"
    return html;
}

function createBtViewPedidosEntreguesPrazoTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_performance_fornecedor.modal.pedidos_entregues_prazo') }}\" data-filter=\""+$this.filters+"\" data-title='PEDIDOS ENTREGUES NO PRAZO' data-total='true' class='bt-modal'>"+$this.quantidade_entregue_prazo +"</a>"
    return html;
}

function createBtViewPedidosEntreguesAtraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_performance_fornecedor.modal.pedidos_entregues_atraso') }}\" data-filter=\""+$this.filters+"\" data-title='PEDIDOS ENTREGUES ATRASO - "+$this.fornecedor+"' data-total='false' class='bt-modal'>"+$this.quantidade_entregue_atraso +"</a>"
    return html;
}

function createBtViewPedidosEntreguesAtrasoTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_performance_fornecedor.modal.pedidos_entregues_atraso') }}\" data-filter=\""+$this.filters+"\" data-title='PEDIDOS ENTREGUES ATRASO' data-total='true' class='bt-modal'>"+$this.quantidade_entregue_atraso +"</a>"
    return html;
}

function optionsAutoCompleteFornecedor(){
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
            $(document).find("#fornecedor").val(ui.item.label);
            $.ajax({
                url: '{{ route('cliente.salvaClientePadrao') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    codcad: ui.item.value
                }
            });
            return false;
        }
    };
}

function showModalFornecedor(url, title){
    xhr = $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("fornecedor_atrasos_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#fornecedor_atrasos_show").find("tbody").find("tr").off("click");
                    $(document).find("#fornecedor_atrasos_show").find("tbody").find("tr").on("click", function(){
                        returnDados($(this));
                    });
                });
                $(document).find("#fornecedor_atrasos_show").find(".bt-selected").on("click", function(){
                    returnDados($(this));
                });
            });
        }
    });
}
    
function returnDados($dados){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#fornecedor_atrasos_show").modal("hide");
    $("#form_filter").find("#fornecedor").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
    $.ajax({
        url: '{{ route('cliente.salvaClientePadrao') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            codcad: $dados.find("td").eq(0).text()
        }
    });
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
                createModal("modal_pedidos", title, body, 'modal-lg');
            }
        });
    }
    
@endsection