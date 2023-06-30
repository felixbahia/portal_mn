@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
                {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Estabelecimentos'])}}
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
                <th rowspan="2">Estabelecimento<br></th>
                <th colspan="3">Conferência</th>
                <th colspan="3">Guarda</th>
                <th colspan="2">A Separar</th>
                <th colspan="2">A Faturar</th>
            </tr>
            <tr>
                <th>Notas</th>
                <th>Peças</th>
                <th>Qtd</th>
                <th>Notas</th>
                <th>Peças</th>
                <th>Qtd</th>
                <th>Pedidos</th>
                <th>Qtd</th>
                <th>Pedidos</th>
                <th>Valor</th>
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
        </tfoot>      
    </table>
</div>
@endsection

@section('script-footer')
$(document).ready( function () {
    $('#btn-filterform').on('click', function(){
        filtro();
    });
    posicao_geral_expedicao = $('#table-filters-index')
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
                title: 'Posição Geral Expedição',
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
            className: "notas-col tb_number",
            "targets": [1,2,3]
            },
            {
            "class": "compras-col tb_number",
            "targets": [4,5,6]
            },
            {
            "class": "compras-sem-col tb_number",
            "targets": [7,8]
            },
            {
            "class": "lancadas-col tb_number",
            "targets": [9,10]
            },
        ]
    });
    posicao_geral_expedicao.on('draw', function () {
        $(document).find(".bt-modal").off("click");
        $(document).find(".bt-modal").on("click", function(event){
            event.stopPropagation();
            showModalNotas($(this));
        });
        $(document).find(".bt-modal-total-notas").off("click");
        $(document).find(".bt-modal-total-notas").on("click", function(event){
            event.stopPropagation();
            showModalNotas($(this));
        });
    });
});

function filtro(){
    $(posicao_geral_expedicao.column(0).footer()).html('');
    $(posicao_geral_expedicao.column(1).footer()).html('');
    $(posicao_geral_expedicao.column(2).footer()).html('');
    $(posicao_geral_expedicao.column(3).footer()).html('');
    $(posicao_geral_expedicao.column(4).footer()).html('');
    $(posicao_geral_expedicao.column(5).footer()).html('');
    $(posicao_geral_expedicao.column(6).footer()).html('');
    $(posicao_geral_expedicao.column(7).footer()).html('');
    $(posicao_geral_expedicao.column(8).footer()).html('');
    $(posicao_geral_expedicao.column(9).footer()).html('');
    $(posicao_geral_expedicao.column(10).footer()).html('');
    posicao_geral_expedicao.draw();
    posicao_geral_expedicao.clear().draw();
    $form = $("#form_filter");
    $data = $form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('controle_geral_expedicao.filtro')}}',
        type: 'POST',
        data: $data,
        success: function(data){
            var dados = data.response.saida;
            var total = data.response.total;
            if(dados){
                var out = [];
                for (var fields in dados){
                    out.push([
                        dados[fields].estabelecimento,
                        createBtViewNotasAConferir(dados[fields]),
                        dados[fields].peca_a_conferir,
                        dados[fields].quantidade_a_conferir,
                        createBtViewNotasConferidas(dados[fields]),
                        dados[fields].peca_conferida,
                        dados[fields].quantidade_conferida,
                        createBtViewPedidosAConferir(dados[fields]),
                        dados[fields].quantidade_a_separar,
                        createBtViewPedidosConferidos(dados[fields]),
                        dados[fields].valor,
                    ]);
                }

                $(posicao_geral_expedicao.column(0).footer()).html('total');
                $(posicao_geral_expedicao.column(1).footer()).html(createBtViewTotalNotasAConferir(total));
                $(posicao_geral_expedicao.column(2).footer()).html(total.peca_a_conferir);
                $(posicao_geral_expedicao.column(3).footer()).html(total.quantidade_a_conferir);
                $(posicao_geral_expedicao.column(4).footer()).html(createBtViewTotalNotasConferidas(total));
                $(posicao_geral_expedicao.column(5).footer()).html(total.peca_conferida);
                $(posicao_geral_expedicao.column(6).footer()).html(total.quantidade_conferida);
                $(posicao_geral_expedicao.column(7).footer()).html(createBtViewTotalPedidosAConferir(total));
                $(posicao_geral_expedicao.column(8).footer()).html(total.quantidade_a_separar);
                $(posicao_geral_expedicao.column(9).footer()).html(createBtViewTotalPedidosConferidos(total));
                $(posicao_geral_expedicao.column(10).footer()).html(total.valor);
                posicao_geral_expedicao.rows.add(out).draw();
            }
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

function isEmptyObject(obj)
{
    return obj.toSource() === "({})";

}

function showModalNotas($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var total = $($this).data('total');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter, total: total},
        method: 'POST',
        success: function(body){
            createModal("posicao_geral_expedicao", title, body, 'modal-lg');
        }
    });
}

function showModalTotalNotas($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var total = $($this).data('total');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter, total: total},
        method: 'POST',
        success: function(body){
            createModal("posicao_geral_expedicao", title, body, 'modal-lg');
        }
    });
}

function createBtViewNotasAConferir($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_geral_expedicao.modal.notas_a_conferir') }}\" data-filter=\""+$this.filters+"\" data-title='NOTAS A CONFERIR POR ESTABELECIMENTO - "+$this.estabelecimento+"' data-total='false' class='bt-modal'>"+$this.nota_a_conferir+"</a>"
    return html;
}


function createBtViewTotalNotasAConferir($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_geral_expedicao.modal.notas_a_conferir') }}\" data-filter=\""+$this.filters+"\" data-title='TOTAL DE NOTAS A CONFERIR' data-total='true' class='bt-modal-total-notas'>"+$this.nota_a_conferir+"</a>"
    return html;
}

function createBtViewNotasConferidas($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_geral_expedicao.modal.notas_conferida') }}\" data-filter=\""+$this.filters+"\" data-title='NOTAS CONFERIDAS POR ESTABELECIMENTO - "+$this.estabelecimento+"' data-total='false' class='bt-modal'>"+$this.nota_conferida+"</a>"
    return html;
}

function createBtViewTotalNotasConferidas($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_geral_expedicao.modal.notas_conferida') }}\" data-filter=\""+$this.filters+"\" data-title='TOTAL DE NOTAS CONFERIDAS' data-total='true' class='bt-modal-total-notas'>"+$this.nota_conferida+"</a>"
    return html;
}

function createBtViewPedidosConferidos($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_geral_expedicao.modal.pedidos_conferidos') }}\" data-filter=\""+$this.filters+"\" data-title='PEDIDOS CONFERIDOS POR ESTABELECIMENTO - "+$this.estabelecimento+"' data-total='false' class='bt-modal'>"+$this.pedido_separado+"</a>"
    return html;
}

function createBtViewTotalPedidosConferidos($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_geral_expedicao.modal.pedidos_conferidos') }}\" data-filter=\""+$this.filters+"\" data-title='TOTAL DE PEDIDOS CONFERIDOS' data-total='true' class='bt-modal'>"+$this.pedido_separado+"</a>"
    return html;
}

function createBtViewPedidosAConferir($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_geral_expedicao.modal.pedidos_a_conferir') }}\" data-filter=\""+$this.filters+"\" data-title='PEDIDOS A CONFERIR POR ESTABELECIMENTO - "+$this.estabelecimento+"' data-total='false' class='bt-modal'>"+$this.pedido_a_separar+"</a>"
    return html;
}

function createBtViewTotalPedidosAConferir($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_geral_expedicao.modal.pedidos_a_conferir') }}\" data-filter=\""+$this.filters+"\" data-title='TOTAL DE PEDIDOS A CONFERIR' data-total='true' class='bt-modal'>"+$this.pedido_a_separar+"</a>"
    return html;
}

@endsection