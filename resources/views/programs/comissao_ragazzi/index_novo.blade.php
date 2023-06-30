@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {{ Form::text('data_de', $data_inicial, ['id' => 'data_de', 'class' => 'form-control data', 'placeholder' => 'Data de (DD/MM/YYYY)', 'maxlength' => '20']) }}
        </div>
        <div class="col-lg-2">
            {{ Form::text('data_ate', $data_final, ['id' => 'data_ate', 'class' => 'form-control data', 'placeholder' => 'Data até (DD/MM/YYYY)', 'maxlength' => '20']) }}
        </div>
        <div class="col-sm-8">
            <div class="input-group">
                <input type="text" class="form-control input-label" name="cliente" id="cliente" value="" placeholder="Cliente" maxlength="250" />
                <span class="input-group-addon border rounded-right" id="bt-search-cliente" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
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
    <table class="table table-striped" id="table-filters">
        <thead>
            <tr>
                <th>Estab.</th>
                <th class="tb_number">Nr Título</th>
                <th style='min-width: 150px;'>Cliente</th>
                <th class="tb_number">Vr. Título</th>
                <th class="tb_number">Juros</th>
                <th class="tb_number">Multa</th>
                <th class="tb_number">Vr. Pago</th>
                <th class="tb_number">Honorários</th>
                <th class="text_date">Data do Pag.</th>
                <th class="tb_number">Comissão<a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="top" title="" data-original-title="Valor Comissão = Multa + Honorários + (50% Valor da Nota Débito)" style="color: black;"></a></th>
                <th class="text_date">Nota Débito</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td class="tb_number"></td>
                <td class="tb_number">Total:</td>
                <td class="tb_number" id="total_valor_titulo"></td>
                <td class="tb_number" id="total_valor_juro"></td>
                <td class="tb_number" id="total_valor_multa"></td>
                <td class="tb_number" id="total_valor_pago"></td>
                <td class="tb_number" id="total_valor_cobranca"></td>
                <td class="text_date"></td>
                <td class="tb_number" id="total_valor_comissao"></td>
                <td class="text_date"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {
    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });

    $(document).find('#form_filter').find('.data').datepicker({ 
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        language: 'pt-BR',
        autoHide: true,
        endDate: new Date()
    });
    $(document).find('#form_filter').find('.data').mask('00/00/0000');

    table_filters.destroy();
    table_filters = $('#table-filters').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "autoWidth": false,
        "language": {
            "decimal": ",",
            "thousands": ".",
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
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Comissão Ragazzi',
                footer: true,
                autoFilter: true,
                exportOptions: {
                    modifier: {
                        page: 'all'
                    },
                    columns: ':visible',
                    format: {
                        body: function ( data, row, column, node ) {
                            data = $('<p>' + data + '</p>').text();
                            if(column !== 0 && column !== 1 && column !== 2 && column !== 6 && column !== 7 && column !== 9 && column !== 10){
                                if(data != ''){
                                    numero = data.replace( /[$.]/g, '' ).replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = '';
                                }
                            }
                            return data;
                        }
                    }
                }
            },
        ],
        "columnDefs": [
            { targets: 2, width: '150px',},
            {
                'targets': 'td_acao',
                'class': 'td_acao',
                "orderable": false
            },
            {
                'targets': 'tb_number',
                'class': 'tb_number',
            },{
                'targets': 'text_date',
                'class': 'text_date',
            },
        ],
    });

    form = $(document).find('#form_filter');
    form.find("#cliente").autocomplete(optionsAutoCompleteCliente());
    form.find("#bt-search-cliente").on('click', function(event){
        event.stopPropagation();
        console.log($(this).data("route"));
        showModalClienteBusca($(this).data("route"));
        return false;
    });
});

function filterAjax(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('comissao_ragazzi.filtro_novo')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            
            for (var fields in data.response.dados){
                temp_array = [
                    ajusteTamanhoTable(data.response.dados[fields].estabelecimento),
                    data.response.dados[fields].numero_titulo,
                    ajusteTamanhoTable(data.response.dados[fields].cliente),
                    data.response.dados[fields].valor_titulo,
                    data.response.dados[fields].valor_juro,
                    data.response.dados[fields].multa,
                    data.response.dados[fields].valor_pago,
                    data.response.dados[fields].honorarios,
                    data.response.dados[fields].data_pagamento,
                    data.response.dados[fields].valor_comissao,
                    data.response.dados[fields].nota_debito,
                ];
                produtos.push(temp_array)
            }
            table_filters.rows.add(produtos).draw();            

            $(document).find('#total_valor_titulo').html(data.response.total_valor_titulo);
            $(document).find('#total_valor_juro').html(data.response.total_valor_juro);
            $(document).find('#total_valor_pago').html(data.response.total_valor_pago);
            $(document).find('#total_valor_comissao').html(data.response.total_valor_comissao);
            $(document).find('#total_valor_multa').html(data.response.total_valor_multa);
            $(document).find('#total_valor_cobranca').html(data.response.total_valor_cobranca);
        }
    });
}

function filterClear(){
    table_filters.clear().draw();
}

function ajusteTamanhoTable($value){
    $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

    return $html;
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
        method: 'GET',
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
@endsection