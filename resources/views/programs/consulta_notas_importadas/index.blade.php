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
                <div class="input-group">
                    {{ Form::text('fornecedor', '', ['id' => 'fornecedor', 'class' => 'form-control input-label', 'placeholder' => 'Fornecedor','maxlength' => '200']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-2">
                {{ Form::text('natureza', '',['id' => 'natureza', 'class' => 'form-control', 'placeholder' => 'Natureza de Operação (CFOP)','maxlength' => '30']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_inicio', date('01/m/Y'),['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_fim', date('d/m/Y'),['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '20']) }}
            </div>
        </div>
        <div class="row">
            <div class="col-lg-2">
                {{ Form::select("cobranca", ['cobranca' => 'Cobrança (Todos)','sem_cobranca' => 'Sem Cobrança','com_cobranca' => 'Com Cobrança'], 'cobranca', ["id" => "cobranca", "class"=>"form-control"]) }}
            </div>
            <div class="col-lg-2">
                {{ Form::select("frete", ['frete' => 'Todos','sem_frete' => 'Sem CTE','com_frete' => 'CTE'], 'frete', ["id" => "frete", "class"=>"form-control"]) }}
            </div>
            <div class="col-lg-2">
                {{ Form::select("lancadas", ['' => 'Todos','notas_lancadas' => 'Notas Lançadas','notas_nao_lancadas' => 'Notas Não Lançadas   '], 'frete', ["id" => "lancadas", "class"=>"form-control"]) }}
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
                <th colspan="2">Notas Importadas</th>
                <th colspan="2">Pedido Compras</th>
                <th colspan="2">Compras Confirmadas</th>
                <th colspan="2">Compras s/ Confirmação</th>
                <th colspan="2">Lançadas</th>
            </tr>
            <tr>
                <th>Qtd</th>
                <th>Valor</th>
                <th>Qtd</th>
                <th>Valor</th>
                <th>Qtd</th>
                <th>Valor</th>
                <th>Qtd</th>
                <th>Valor</th>
                <th>Qtd</th>
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


    $("#fornecedor").autocomplete(optionsAutoComplete("fornecedor"));
    $(document).find("#bt-search-fornecedor-busca").on("click", function(){
        showModalFornecedor($(this).data("route"), "Lista de Fornecedores");
    });
    $('#btn-filterform').on('click', function(){
        filtro();
    });
    table_notas_omportadas_index = $('#table-filters-index')
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
            "targets": [1,2]
            },
            {
            "class": "pedido-col tb_number",
            "targets": [3,4]
            },
            {
            "class": "compras-col tb_number",
            "targets": [5,6]
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
    table_notas_omportadas_index.on('draw', function () {
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

function showModalFornecedor(url, title){
    $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("fornecedor_search_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                    $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                        returnDadosFornecedor($(this));
                    });
                });
            });
        }
    });
}

function returnDadosFornecedor($this){
    if($this.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#fornecedor_search_show").modal("hide");
    $(document).find("#fornecedor").val($this.find("td").eq(1).text()+' - '+$this.find("td").eq(3).text());
}

function optionsAutoComplete($name){
    return {
        source: function (request, response) {
            request.name = $name;
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('fornecedor.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3,
        select: function( event, ui ) {
            $(document).find("#fornecedor").val(ui.item.label);
            setTimeout(function(){
                table_notas_omportadas_index.draw();
            }, 100);
            return false;
        }

    };
}

function filtro(){
    $(table_notas_omportadas_index.column(0).footer()).html('');
    $(table_notas_omportadas_index.column(1).footer()).html('');
    $(table_notas_omportadas_index.column(2).footer()).html('');
    $(table_notas_omportadas_index.column(3).footer()).html('');
    $(table_notas_omportadas_index.column(4).footer()).html('');
    $(table_notas_omportadas_index.column(5).footer()).html('');
    $(table_notas_omportadas_index.column(6).footer()).html('');
    $(table_notas_omportadas_index.column(7).footer()).html('');
    $(table_notas_omportadas_index.column(8).footer()).html('');
    $(table_notas_omportadas_index.column(9).footer()).html('');
    $(table_notas_omportadas_index.column(10).footer()).html('');
    table_notas_omportadas_index.draw();
    table_notas_omportadas_index.clear().draw();
    $form = $("#form_filter");
    $data = $form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('consulta_notas_importadas.filter')}}',
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
                        createBtViewNotas(dados[fields]),
                        dados[fields].valor,
                        dados[fields].pedido_quantidade,
                        dados[fields].pedido_valor,
                        dados[fields].com_compras,
                        dados[fields].com_compras_Valor,
                        dados[fields].sem_compras,
                        dados[fields].sem_compras_valor,
                        dados[fields].lancadas_quantidade,
                        dados[fields].lancadas_valor,
                    ]);
                }
                $(table_notas_omportadas_index.column(0).footer()).html('total');
                $(table_notas_omportadas_index.column(1).footer()).html(createBtViewTotalNotas(total));
                $(table_notas_omportadas_index.column(2).footer()).html(total.valor);
                $(table_notas_omportadas_index.column(3).footer()).html(total.pedido_quantidade);
                $(table_notas_omportadas_index.column(4).footer()).html(total.pedido_valor);
                $(table_notas_omportadas_index.column(5).footer()).html(total.com_compras);
                $(table_notas_omportadas_index.column(6).footer()).html(total.com_compras_Valor);
                $(table_notas_omportadas_index.column(7).footer()).html(total.sem_compras);
                $(table_notas_omportadas_index.column(8).footer()).html(total.sem_compras_valor);
                $(table_notas_omportadas_index.column(9).footer()).html(total.lancadas_quantidade);
                $(table_notas_omportadas_index.column(10).footer()).html(total.lancadas_valor);
                table_notas_omportadas_index.rows.add(out).draw();
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
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter},
        method: 'POST',
        success: function(body){
            createModal("notas_importadas-modal-notas", title, body, 'modal-lg');
        }
    });
}

function showModalTotalNotas($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter},
        method: 'POST',
        success: function(body){
            createModal("notas_importadas-modal-notas", title, body, 'modal-lg');
        }
    });
}

function createBtViewNotas($this){
    var html = "<a href=\"#\" data-route=\"{{ route('notas_importadas.modal.notas') }}\" data-filter=\""+$this.filter+"\" data-title='NOTAS POR ESTABELECIMENTO - "+$this.estabelecimento+"' class='bt-modal'>"+$this.notas+"</a>"
    return html;
}

function createBtViewTotalNotas($this){
    var html = "<a href=\"#\" data-route=\"{{ route('notas_importadas.modal.notas.total') }}\" data-filter=\""+$this.filter+"\" data-title='TOTAL DE NOTAS' class='bt-modal-total-notas'>"+$this.notas+"</a>"
    return html;
}

function createBtViewInadimplencia($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_inadimplencia.modal.inadimplencia') }}\" data-filter=\""+$this.filter+"\" data-title='ANALÍTICO TITULOS EM INADIMPLÊNCIA' class='bt-modal'>"+$this.inadimplencia+"</a>"
    return html;
}

@endsection