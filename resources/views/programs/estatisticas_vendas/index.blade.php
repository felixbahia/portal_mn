@extends('layouts.app')

@section('content-filter')

<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem para {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::select("estabelecimento", $estabelecimentos, '', ["id" => "estabelecimento", "class"=>"form-control"]) }}
            </div>
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::text('marca', '', ['id' => 'marca', 'class' => 'form-control', 'placeholder' => 'Marca']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::text('linha', '', ['id' => 'linha', 'class' => 'form-control', 'placeholder' => 'Linha']) }}
            </div>
            <div class="form-group col-lg-1 col-xl-2">
                {{ Form::text('grupo', '', ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupo']) }}
            </div>   
            <div class="form-group col-lg-1 col-xl-1">
                {{ Form::text('data_inicio', date('01/m/Y'),['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '10']) }}
            </div>
            <div class="form-group col-lg-1 col-xl-1">
                {{ Form::text('data_fim', date('d/m/Y'),['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '10']) }}
            </div>
            <div class="form-group col-lg-1"><div class="form-check">
                {!! Form::checkbox('prepago', 'true', true, ['id' => 'prepago', 'class' => 'form-check-input']) !!}
                {!! Form::label('prepago', 'Pré-pago', ['class' => 'form-check-label']) !!}
            </div></div>   
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
    <table class="table table-striped table-not-edit table-not-view" id="table-filters">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class="tb_number">KG</th>
                <th class="tb_number">Metros</th>
                <th class="tb_number">Outras Unidades</th>
                <th class="tb_number">Clientes</th>
                <th class="tb_number">Notas</th>
                <th class="tb_number">Valor</th>
                <th class="tb_number">Ticket Médio</th>
                <th class="tb_number">Forma Pagamento</th>
                <th class="tb_number">Produto p/ Marca</th>
                <th class="tb_number">Produto p/ Linha</th>
                <th class="tb_number">Produto p/ Grupo</th>                                
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

    table_filters.destroy();
    table_filters = $('#table-filters')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 15,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Análise de Produto',
                footer: true,
                customize: function( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('row c[r^="C"]', sheet).attr( 's', '2' );
                },
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            if(column > 2 ){
                                if(data != ''){
                                    numero = data.replace('.','').replace(',','');
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
                "class"     : "tb_number", 
                "type"      : "num-fmt", 
                "targets"   : "tb_number",
            },
        ]
    });

    $(document).find("#btn-filterform").on("click", function(e){
        e.preventDefault();
        filtro();
    });

    table_filters.on('draw', function () {
        $(document).find(".bt-view").off("click");
        $(document).find(".bt-view").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
        $(document).find(".marca-view").off("click");
        $(document).find(".marca-view").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
        $(document).find(".linha-view").off("click");
        $(document).find(".linha-view").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
        $(document).find(".grupo-view").off("click");
        $(document).find(".grupo-view").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });
        
        
    });
    $("#marca").autocomplete(optionsAutoComplete("marca"));
    $("#linha").autocomplete(optionsAutoComplete("linha"));
    $("#grupo").autocomplete(optionsAutoComplete("grupo"));
  
    

});

function filtro(){
    table_filters.clear().draw();
    $form = $("#form_filter");
    $data = $form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('estatisticas_vendas.filtro')}}',
        type: 'POST',
        data: $data,
        success: function(data){

            var out = [];
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
            $(table_filters.column(10).footer()).html('');
            $(table_filters.column(11).footer()).html('');
            
            if(!isEmpty(data.response.saida)){
                for (var fields in data.response.saida){
                    out.push([
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].estabelecimento + "''>" + data.response.saida[fields].estabelecimento + "</div></div>",
                        data.response.saida[fields].peso,
                        data.response.saida[fields].metros,
                        data.response.saida[fields].outras_unidades,
                        data.response.saida[fields].cliente,
                        data.response.saida[fields].notas,
                        data.response.saida[fields].valor,
                        data.response.saida[fields].ticket_medio,
                        createBtView(data.response.saida[fields]),
                        createBtViewMarca(data.response.saida[fields]),
                        createBtViewLinha(data.response.saida[fields]),
                        createBtViewGrupo(data.response.saida[fields]),
                       
                        
                    ]);
                }
                $(table_filters.column(0).footer()).html('total');
                $(table_filters.column(1).footer()).html(data.response.total.peso);
                $(table_filters.column(2).footer()).html(data.response.total.metros);
                $(table_filters.column(3).footer()).html(data.response.total.outras_unidades);
                $(table_filters.column(4).footer()).html(data.response.total.cliente);
                $(table_filters.column(5).footer()).html(data.response.total.notas);
                $(table_filters.column(6).footer()).html(data.response.total.valor);
                $(table_filters.column(7).footer()).html(data.response.total.ticket_medio);
                $(table_filters.column(8).footer()).html(createBtViewTotal(data.response.total));
                $(table_filters.column(9).footer()).html(createBtViewMarcaTotal(data.response.total));
                $(table_filters.column(10).footer()).html(createBtViewLinhaTotal(data.response.total));
                $(table_filters.column(11).footer()).html(createBtViewGrupoTotal(data.response.total));
              
                
            }
                
            table_filters.rows.add(out).draw();
            
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

function showModal($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var total = $($this).data('total');
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter, total: total},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view", title, body, 'modal-lg');
        }
    });
}

function showModalProduto($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter},
        method: 'POST',
        success: function(body){
            createModal("model_analise_produto", title, body, 'modal-lg');
        }
    });
}

function showModalGrupo($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var codigo = $($this).data('codigo');
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view_grupo", title, body, 'modal-lg');
        }
    });
}

function showModalMarca($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var codigo = $($this).data('codigo');
    var estabelecimento_prods = $($this).data('estabelecimento_prods');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view_marca", title, body, 'modal-lg');
        }
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
    var html = "<a href=\"#\" data-route=\"{{ route('estatisticas_vendas.modal.forma_pagamento') }}\" data-total='false' data-filter=\""+$this.filter+"\" data-title='ANALÍTICO POR FORMA DE PAGAMENTO \""+$this.estabelecimento+"\" DE \""+$this.data_inicio+"\" ATÉ \""+$this.data_final+"\"' class='bt-view'></a>"
    return html;
}

function createBtViewTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('estatisticas_vendas.modal.forma_pagamento') }}\" data-total='true' data-filter=\""+$this.filter+"\" data-title='ANALÍTICO POR FORMA DE PAGAMENTO EM TODOS OS ESTABELECIMENTOS DE \""+$this.data_inicio+"\" ATÉ \""+$this.data_final+"\"' class='bt-view'></a>"
    return html;
}

function createBtViewMarca($this){
    var html = "<a href=\"#\" data-route=\"{{ route('estatisticas_vendas.modal.marca') }}\" data-total='false' data-filter=\""+$this.filter+"\" data-title='ANALÍTICO POR MARCA DE PRODUTO \""+$this.estabelecimento+"\" DE \""+$this.data_inicio+"\" ATÉ \""+$this.data_final+"\"' class='bt-view marca-view'></a>";
    return html;
}

function createBtViewMarcaTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('estatisticas_vendas.modal.marca') }}\" data-total='true' data-filter=\""+$this.filter+"\" data-title='ANALÍTICO POR MARCA DE PRODUTO EM TODOS OS ESTABELECIMENTOS DE \""+$this.data_inicio+"\" ATÉ \""+$this.data_final+"\"' class='bt-view marca-view'></a>";
    return html;
}

function createBtViewGrupo($this){
    var html = "<a href=\"#\" data-route=\"{{ route('estatisticas_vendas.modal.grupo') }}\" data-total='false' data-filter=\""+$this.filter+"\" data-title='ANALÍTICO POR GRUPO DE PRODUTO \""+$this.estabelecimento+"\" DE \""+$this.data_inicio+"\" ATÉ \""+$this.data_final+"\"' class='bt-view grupo-view'></a>";
    return html;
}

function createBtViewGrupoTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('estatisticas_vendas.modal.grupo') }}\" data-total='true' data-filter=\""+$this.filter+"\" data-title='ANALÍTICO POR GRUPO DE PRODUTO EM TODOS OS ESTABELECIMENTOS DE \""+$this.data_inicio+"\" ATÉ \""+$this.data_final+"\"' class='bt-view grupo-view'></a>";
    return html;
}

function createBtViewLinha($this){
    var html = "<a href=\"#\" class=\"bt-view linha-view\" data-total='false' data-title='ANALÍTICO POR LINHA DE PRODUTO \""+$this.estabelecimento+"\" DE \""+$this.data_inicio+"\" ATÉ \""+$this.data_final+"\"' data-route=\"{{ route('estatisticas_vendas.modal.linha') }}\" data-filter=\""+$this.filter+"\" ></a>"
    return html;
}

function createBtViewLinhaTotal($this){
    var html = "<a href=\"#\" class=\"bt-view linha-view\" data-total='true' data-title='ANALÍTICO POR LINHA DE PRODUTO EM TODOS OS ESTABELECIMENTOS DE \""+$this.data_inicio+"\" ATÉ \""+$this.data_final+"\"' data-route=\"{{ route('estatisticas_vendas.modal.linha') }}\" data-filter=\""+$this.filter+"\" ></a>"
    return html;
}



@endsection