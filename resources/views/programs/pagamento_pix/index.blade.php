@extends('layouts.app')
@section('content-filter')
<form action="#" name="form_pagamento_pix" id="form_pagamento_pix" onsubmit="return false">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-3 input-group">
                {{ Form::text('cliente_nome', '', ['id' => 'cliente_nome', 'class' => 'form-control input-label', 'placeholder' => 'Cliente']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>               
                     
            <div class="col-lg-2">
                {{ Form::text('data_inicio', \Carbon\Carbon::now()->format(date('01/m/Y')),['id' => 'data_inicio', 'class' => 'form-control data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '10']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_fim', \Carbon\Carbon::now()->format('d/m/Y'),['id' => 'data_fim', 'class' => 'form-control data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '10']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::select('statusPedido', $statusPedido, 'emaberto', ['class' => 'form-control', 'placeholder' => 'Em Aberto'])}}
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
    <table class="table table-striped table-not-edit table-not-view table-pagamento-pix" id="table-pagamento-pix">
        <thead>
            <tr>
                <th rowspan="2" class="tb_date">Depósito<a href="#" class="btn-informacao-sem-width" data-toggle="tooltip" data-placement="top" title="" data-original-title="Data/Horário do Pagamento"></a></th>
                <th rowspan="2" class="tb_date">Data Inserção<a href="#" class="btn-informacao-sem-width" data-toggle="tooltip" data-placement="top" title="" data-original-title="Data/Horário da Inserção das informações na tabela na base de dados"></a></th>
                <th rowspan="2" class="tb_number">Valor</th>
                <th rowspan="2">Depositante</th>   
                <th rowspan="2">Chave</th>   
                <th rowspan="2">Titulo Crédito</th>   
                <th rowspan="2">Dados Titulo</th>           
                <th rowspan="2">Cliente</th>   
                <th colspan="3" class='th-criterios-titulo' style="text-align: center;">Associar Pix</th>                
            </tr>
            <tr>
                <th class="td_acao"></th>
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
@section("script-footer")

$(document).ready(function(){


    $(document).find("#cliente_nome").autocomplete(optionsAutoCompleteCliente('cliente_nome'));

    $('.data').mask('00/00/0000');

    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        endDate: new Date(),
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

    $(document).find('#btn-filterform').on('click', function(event){
        event.stopPropagation();
        filterPagamentoPix();
        
       
    });
    
    $(document).find("#bt-search-cliente-busca").off("click");
    $(document).find("#bt-search-cliente-busca").on("click", function(event){
        event.stopPropagation();
        showModalClienteBusca($(this).data("route"));
        return false;
    });
   
   table_filtro_pagamento_pix = $('#table-pagamento-pix').DataTable({
    "searching": false,
    "paging": true,
    "lengthChange": false,
    "info": false,
    "pageLength": 15,
    dom: 'Bfrtip',
    buttons: [
        {
            extend: 'excelHtml5',
            text: ' ',
            title: '',
            footer: true,
            exportOptions: {
                columns: ':visible',
                format: {
                    body: function(data, row, column, node) {
                        data = $('<p>' + data + '</p>').text();
                        if(column == 2){
                            if(data != ''){
                                numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                inteiro = Math.floor(numero.length - 2);
                                decimal = Math.floor(numero.length);
                                data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                            }else{
                                data = '';
                            }
                        }
                        return data;
                    },
                    footer: function(data) {
                        data = $('<p>' + data + '</p>').text();
                        return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                    }
                }
            },
        },
    ],
    "language": {
        "emptyTable":     "Nenhum registro encontrado",
        "infoPostFix":    "",
        "decimal":        ".",
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number","width": "100px" },
            { "class": "tb_date", targets: "tb_date"},
            {
                'targets': 'td_acao',
                'class': 'td_acao',
                'width': '5px',
                "orderable": false
            },
            { targets: 0, width: '10px'},
        ],
       
    });
   
    table_filtro_pagamento_pix.on('draw', function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
    });
   
    
 });

     
 function filterPagamentoPix(){ 
    table_filtro_pagamento_pix.clear().draw();
    form = $(document).find("#form_pagamento_pix");
    $form = $("#form_pagamento_pix");
    $data = $form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: "{{ route('pagamento_pix.filter') }}", 
        type: 'POST',
        data: $data,
        success: function(data){

            var out = [];
            $(table_filtro_pagamento_pix.column(0).footer()).html('');
            $(table_filtro_pagamento_pix.column(1).footer()).html('');
            $(table_filtro_pagamento_pix.column(2).footer()).html('');
            $(table_filtro_pagamento_pix.column(3).footer()).html('');
            $(table_filtro_pagamento_pix.column(4).footer()).html('');
            $(table_filtro_pagamento_pix.column(5).footer()).html('');
            $(table_filtro_pagamento_pix.column(6).footer()).html('');
            $(table_filtro_pagamento_pix.column(7).footer()).html('');
            $(table_filtro_pagamento_pix.column(8).footer()).html('');
           
            
            if(!isEmpty(data.response.saida)){
                for (var fields in data.response.saida){
                    out.push([
                        data.response.saida[fields].datadeposito,
                        data.response.saida[fields].datainsercao,
                        data.response.saida[fields].valor,
                        data.response.saida[fields].depositanteNome,
                        data.response.saida[fields].chave,
                        data.response.saida[fields].titulo,
                        data.response.saida[fields].tituloObs,
                        data.response.saida[fields].cliente_nome,
                        createBtAprove(data.response.saida[fields],data.response.saida[fields].id),
                       
                        
                    ]);
                }

                $(table_filtro_pagamento_pix.column(0).footer()).html('');
                $(table_filtro_pagamento_pix.column(1).footer()).html('');               
                $(table_filtro_pagamento_pix.column(2).footer()).html('total: '+data.response.total.valor);
                $(table_filtro_pagamento_pix.column(3).footer()).html('');
                $(table_filtro_pagamento_pix.column(4).footer()).html('');
                $(table_filtro_pagamento_pix.column(5).footer()).html('Automatico: '+data.response.total.auto+' Valor: '+data.response.total.valorauto+'   Manual: '+data.response.total.manual+' Valor: '+data.response.total.valormanual);
                $(table_filtro_pagamento_pix.column(6).footer()).html('');
                $(table_filtro_pagamento_pix.column(7).footer()).html('');
                $(table_filtro_pagamento_pix.column(8).footer()).html('');
              
                
            }
                
            table_filtro_pagamento_pix.rows.add(out).draw();
            
        },
        error: function(callback){
            if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                $.each(data, function(index, el) {
                    $form.find('#'+index).eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                    $form.find('#'+index).eq(0).addClass('error');
                });
                $form.find('input.error').eq(0).focus();
            }
        }
    }).always(function() {
        hide_loader();
    });
}

function isEmpty(obj) {
    for(var prop in obj) {
        if(obj.hasOwnProperty(prop))
            return false;
    }

    return true;
}




function createBtAprove(obj,$dados){

    if (obj.mostrar_botao_aprovar){  
        var html = "<a href=\"#\" class=\"bt-edit\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Associar\" onclick=\"associarPedido('"+$dados+"')\"></a>";         
    }

    if (obj.mostrar_botao_check){
        var html = "<center><a href=\"#\" class=\"fa fa-check check-icon\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Ok\"></a></center>";

    }

    return html;
}

function associarPedido($id) {
    $.ajax({
        url: "{{ route("pagamento_pix.modal.pedido") }}",
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            pix_id: $id
        },
        success: function(data){
            $id = "view-pedido";
            title = "Gerar Crédito"; 
            $class = "modal-lg";
            createModal("pagamento_pix-modal-pedido", title, data, 'modal-lg');
        }
    });
}

function optionsAutoCompleteCliente($elemento){
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
            $('.ui-autocomplete').css("z-index", $("#" + $elemento).parents('.modal').css('z-index') + 1);
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Cliente não encontrado');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#" + $elemento).val(ui.item.label);
            return false;
        }
    };
}


function showModalClienteBusca(url){
    var title = "Busca de Clientes";
    $.ajax({
        url: url,
        method: "GET",
        data: {
            _token: "{{csrf_token()}}"
        },
        success: function(body){
            $(document).find("#cliente_searsh_show").remove();
            createModal("cliente_searsh_show", title, body, "modal-lg");
            var modal = $(document).find("#cliente_searsh_show");
            $(document).ready( function () {
                table_dialog.on("draw", function () {
                    modal.find("tbody").find("tr").off("click");
                    modal.find("tbody").find("tr").on("click", function(event){
                        returnDadosClienteBusca($(this), event);
                    });
                });
            });
        }
    });
}

function returnDadosClienteBusca($dados, event){
    if($dados.find("td").eq(0).hasClass("dataTables_empty")){
        return false;
    }
    $(document).find("#cliente_nome").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
    $(document).find("#cliente_searsh_show").modal("hide"); 
}

@endsection