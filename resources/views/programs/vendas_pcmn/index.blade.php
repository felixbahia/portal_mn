@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="form-group col-lg-2">
                {{ Form::select("estabelecimento", $estabelecimentos, '', ["id" => "estabelecimento", "class"=>"form-control"]) }}
            </div>
            <div class="form-group col-lg-2">
                {{ Form::text('grupo', '', ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupo']) }}
            </div>
            <div class="form-group col-lg-2">
                {{ Form::text('pcmn', '', ['id' => 'pcmn', 'class' => 'form-control', 'placeholder' => 'Número do PCMN']) }}
            </div>  
            <div class="col-lg-2">
                {{ Form::text('data_inicio','',['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Data Início MM/AAAA','maxlength' => '10']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_fim', '',['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Data Fim MM/AAAA','maxlength' => '10']) }}
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
    <table class="table table-striped table-not-edit table-not-view" id="table-vendas-pcmn">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>PCMN</th>
                <th>Proforma</th>
                <th>Entrada</th>
                <th>QTD Comprada</th>
                <th>QTD Vendida</th>
                <th>% Vendido</th>
                <th>Média de Vendas (6 meses)</th>
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

    $("#grupo").autocomplete(optionsAutoComplete("grupo"));
    $('#btn-filterform').on('click', function(){
        filtro();
    });

    table_filter_vendas_pcmn = $('#table-vendas-pcmn')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 15,
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
                "class": "tb_number", 
                "targets": [1,4,5,6,7]
            },
            {
                "class": "tb_date", 
                "targets": [3]
            },
            {
                "width": "10%", 
                "targets": [1,4,5,6,7]
            },
        ]
    });
    table_filter_vendas_pcmn.on('draw', function () {
        $(document).find(".bt-modal-produto").off("click");
        $(document).find(".bt-modal-produto").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });

    });
});

function filtro(){

    table_filter_vendas_pcmn.clear().draw();
    $(table_filter_vendas_pcmn.column(0).footer()).html('');
    $(table_filter_vendas_pcmn.column(1).footer()).html('');
    $(table_filter_vendas_pcmn.column(2).footer()).html('');
    $(table_filter_vendas_pcmn.column(3).footer()).html('');
    $(table_filter_vendas_pcmn.column(4).footer()).html('');
    $(table_filter_vendas_pcmn.column(5).footer()).html('');
    $(table_filter_vendas_pcmn.column(6).footer()).html('');   
    $(table_filter_vendas_pcmn.column(7).footer()).html('');   
    
    $form = $("#form_filter");
    $data = $form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('analise_vendas_pcmn.filter') }}',
        type: 'POST',
        data: $data,
        success: function(data){
            if(data.status == 'sucess'){
                var out = [];
                for (var fields in data.response.response){
                    out.push([
                        data.response.response[fields].estabelecimento,
                        createLinkModificarData(data.response.response[fields]),
                        data.response.response[fields].proforma,
                        data.response.response[fields].entrada,
                        data.response.response[fields].quantidade_comprada,
                        data.response.response[fields].quantidade_vendida,
                        data.response.response[fields].percentual_vendido,
                        createBtView(data.response.response[fields]),
                    ]);
                }

                $(table_filter_vendas_pcmn.column(3).footer()).html('Total');
                $(table_filter_vendas_pcmn.column(4).footer()).html(data.response.total.quantidade_comprada);
                $(table_filter_vendas_pcmn.column(5).footer()).html(data.response.total.quantidade_vendida);
                $(table_filter_vendas_pcmn.column(6).footer()).html(data.response.total.percentual_vendido);
                $(table_filter_vendas_pcmn.column(7).footer()).html(data.response.total.media_vendas_mensal);

                table_filter_vendas_pcmn.rows.add(out).draw();
            }else{
                message('Atenção','Tente novamente mais tarde','error');
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

function optionsAutoComplete($name){
    return {
        source: function (request, response) {
            request.name = $name;
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3
    };
}

function createBtView($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_vendas_pcmn.modal.media_mes') }}\" data-filtro=\""+$this.filter+"\" data-total='false' data-title='Média de Venda por mês - Pedido  "+$this.pcmn+"\' class='bt-modal-produto'>"+$this.media_vendas_mensal+"</a>"
    return html;
}

function createLinkModificarData($this){
    var html = "";
    if($this.id_pedido !== ""){
        html = "<a href='#' onclick=\"showComissaoDetalhes('" 
        + $this.id_pedido + "', '"
        + $this.estabelecimento + "')\">" + $this.pcmn + "</a>";
    }
    return html;
}

function showComissaoDetalhes(id, unidade){
    $.ajax({
        url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id: id,
            unidade: unidade,
        },
        success: function(body){
            createModal("1", "Detalhe do Pedido Compras", body, 'modal-lg');
        },
        error: function(callback){
            if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                message = '';
                $.each(data, function(index, el) {
                    message += el+'<br />';
                });
                message("Atenção", message);
            }
        }

    });
}

function showModal($this){
    var url = $($this).data("route");
    var filtro = $($this).data("filtro");
    var title = $($this).data('title');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filtro: filtro},
        method: 'POST',
        success: function(body){
            createModal("modal_pilotagem_media_mes", title, body, 'modal-lg');
        }
    });
}

@endsection