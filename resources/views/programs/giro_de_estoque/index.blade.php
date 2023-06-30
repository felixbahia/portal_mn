@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
                {!! Form::text('grupo',  '', ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupo']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('marca',  '', ['id' => 'marca', 'class' => 'form-control', 'placeholder' => 'Marca']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('linha',  '', ['id' => 'linha', 'class' => 'form-control', 'placeholder' => 'Linha']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('descricao', '', ['id' => 'descricao', 'class' => 'form-control', 'placeholder' => 'Nome Produto']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::text('codigo', '', ['id' => 'codigo', 'class' => 'form-control', 'placeholder' => 'Código Produto']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::select('dias_media',  $dias_media, '', ['id' => 'dias_media', 'class' => 'form-control', 'placeholder' => 'Dias p/ Cálculo Média']) !!}
            </div>
        </div>
        <div class="row">
            <div class="col-lg-2">
                {!! Form::text('necessidade_dias', '', ['id' => 'necessidade_dias', 'class' => 'form-control number', 'placeholder' => 'Necessidade Dias']) !!}
            </div>
            <div class="col-lg-1">
                {!! Form::text('alto_giro_percentual', '', ['id' => 'alto_giro_percentual', 'class' => 'form-control number', 'placeholder' => 'Alto Giro %']) !!}
            </div>
            <div class="col-lg-1">
                {!! Form::text('baixo_giro_percentual', '', ['id' => 'baixo_giro_percentual', 'class' => 'form-control number', 'placeholder' => 'Baixo Giro %']) !!}
            </div> 
            <div class="col-lg-2">
                {!! Form::select('origem',  $origem, '', ['id' => 'origem', 'class' => 'form-control', 'placeholder' => 'Origem']) !!}
            </div>
            <div class="col-lg-2">
                {!! Form::select('produto_grupo',  $produto_grupo, '', ['id' => 'produto_grupo', 'class' => 'form-control']) !!}
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    {{ Form::checkbox('baixo_giro', 'ok', true,  ['id' => 'baixo_giro', 'class' => 'form-check-input']) }}
                    {{ Form::label('baixo_giro', 'Baixo Giro', ['class' => 'form-check-label','for' => 'compra']) }}
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    {{ Form::checkbox('alto_giro', 'ok', true,  ['id' => 'alto_giro', 'class' => 'form-check-input']) }}
                    {{ Form::label('alto_giro', 'Alto Giro', ['class' => 'form-check-label','for' => 'compra']) }}
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    {{ Form::checkbox('giro_normal', 'ok', true,  ['id' => 'giro_normal', 'class' => 'form-check-input']) }}
                    {{ Form::label('giro_normal', 'Giro Normal', ['class' => 'form-check-label','for' => 'compra']) }}
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
<div class="content-table notas-importadas">
    <table class="table table-striped table-not-edit table-not-view" id="table-filters-giro">
        <thead>
            <tr>
                <th rowspan="2" class="grupo">Grupo</th> 
                <th rowspan="2" class="marca">Marca</th>
                <th rowspan="2" class="linha">Linha</th>
                <th rowspan="2" class="codigo_produto">Código</th>
                <th rowspan="2" class="descricao">Descrição</th>
                <th rowspan="2" class="un">UN</th>
                <th class="tb_date" rowspan="2" >Data<br/>Últ. compra</th>
                <th class="tb_number custo_gerencial" rowspan="2" >Custo<br/>Gerencial</th>
                <th class="tb_number preco_venda" rowspan="2" >Preço<br/>Venda</th>
                <th class="tb_number" rowspan="2">Estoque</th>
                <th class="tb_number" rowspan="2">Em Trânsito</th>
                <th class="tb_number" rowspan="2" >%<br/>Part.</th>
                <th colspan="4">Compras a Receber</th>
                <th class="tb_number" rowspan="2">Pedidos<br>Abertos</th>
                <th class="tb_number" rowspan="2">Saldo</th>
                <th colspan="3">Vendas</th>
                <th rowspan="2" class="media_mes">Média Mês</th>
                <th rowspan="2" class="estoque_meses">Estoque Meses</th>
                <th class="lancadas-col tb_number" rowspan="2">Neces.<br/>Comp. Dias</th>
                <th rowspan="2">Giro</th>
            </tr>
            <tr>
                <th class="notas-col tb_number">{{ $meses['atual'] }}</th>
                <th class="border-left notas-col tb_number">{{ $meses['mes_1'] }}</th>
                <th class="border-left notas-col tb_number">{{ $meses['mes_2'] }}</th>
                <th class="border-left notas-col tb_number">Prox</th>
                <th class="compras-sem-col tb_number">Total</th>
                <th class="border-left compras-sem-col tb_number">Média<br/>Dias</th>
                <th class="border-left compras-sem-col tb_number">%<br/>Part.</th>
                <th class="compras-sem-col tb_number">Média Venda</th>
            </tr>
        </thead>
        <tbody>
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
        </tbody>
    </table>
</div>

@endsection
@section('script-footer')
$(document).ready( function () {
    $('.number').mask('0000');

    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filtro();
    });

    $(document).find("#marca").autocomplete(optionsAutoComplete("marca"));
    $(document).find("#linha").autocomplete(optionsAutoComplete("linha"));
    $(document).find("#grupo").autocomplete(optionsAutoComplete("grupo"));
    $(document).find("#descricao").autocomplete(optionsAutoComplete("nome"));

    table_filters_giro.columns('.codigo_produto').visible(false);
    table_filters_giro.columns('.descricao').visible(false);
    table_filters_giro.columns('.custo_gerencial').visible(false);
    table_filters_giro.columns('.preco_venda').visible(false);
    table_filters_giro.columns('.un').visible(false);
    table_filters_giro.columns('.media_mes').visible(false);
    table_filters_giro.columns('.estoque_meses').visible(false);

    table_filters_giro.on('draw', function () {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
        $(document).find(".bt-modal").off("click");
        $(document).find(".bt-modal").on("click", function(event){
            event.stopPropagation();
            abrirModal($(this));
        });
    });
});



var table_filters_giro = $(document).find('#table-filters-giro').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "processing": true,
        "orderMulti": false,
        "scrollCollapse": false,
        "autoWidth": false,
        dom: 'Bfrtip',
        buttons: [
            {
                text: '<i class="btn-excel"></i>',
                title: 'Exportar para excel',
                action: function(){
                        $('<form action="{{ route('giro_de_estoque.export') }}" method="POST" target="_blank">\
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                        <input type="hidden" name="grupo" value="'+$(document).find("#grupo").val()+'">\
						<input type="hidden" name="marca" value="'+$(document).find("#marca").val()+'">\
						<input type="hidden" name="linha" value="'+$(document).find("#linha").val()+'">\
						<input type="hidden" name="descricao" value="'+$(document).find("#descricao").val()+'">\
						<input type="hidden" name="codigo" value="'+$(document).find("#codigo").val()+'">\
						<input type="hidden" name="dias_media" value="'+$(document).find("#dias_media").val()+'">\
                        <input type="hidden" name="media_mes" value="'+$(document).find("#media_mes").val()+'">\
                        <input type="hidden" name="estoque_meses" value="'+$(document).find("#estoque_meses").val()+'">\
						<input type="hidden" name="necessidade_dias" value="'+$(document).find("#necessidade_dias").val()+'">\
						<input type="hidden" name="alto_giro_percentual" value="'+$(document).find("#alto_giro_percentual").val()+'">\
						<input type="hidden" name="baixo_giro_percentual" value="'+$(document).find("#baixo_giro_percentual").val()+'">\
						<input type="hidden" name="origem" value="'+$(document).find("#origem").val()+'">\
						<input type="hidden" name="produto_grupo" value="'+$(document).find("#produto_grupo").val()+'">\
						<input type="hidden" name="baixo_giro" value="'+$(document).find("#baixo_giro").is(":checked")+'">\
						<input type="hidden" name="alto_giro" value="'+$(document).find("#alto_giro").is(":checked")+'">\
						<input type="hidden" name="giro_normal" value="'+$(document).find("#giro_normal").is(":checked")+'">\
                        </form>').appendTo('body').submit().remove();
                }
            },
        ],
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum produto inserido",
            "infoPostFix":    "",
            "loadingRecords": "Carregando...",
            "processing":     "Processando...",
            "zeroRecords":    "Nenhum  produto inserido",
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
            "class": "compras-sem-col tb_number",
            "targets": "compras-sem-col tb_number"
            },
            {
            "class": "lancadas-col tb_number",
            "targets": "lancadas-col tb_number"
            },
            {
                "class": "tb_date", 
                "targets": "tb_date"
            },
            {
                "class": "tb_number", 
                "targets": "tb_number"
            }
        ]
    }
);

function filtro(){
    $(table_filters_giro.column(0).footer()).html('');
    $(table_filters_giro.column(9).footer()).html('');
    $(table_filters_giro.column(10).footer()).html('');
    $(table_filters_giro.column(11).footer()).html('');
    $(table_filters_giro.column(12).footer()).html('');
    $(table_filters_giro.column(13).footer()).html('');
    $(table_filters_giro.column(14).footer()).html('');
    $(table_filters_giro.column(15).footer()).html('');
    $(table_filters_giro.column(16).footer()).html('');
    $(table_filters_giro.column(17).footer()).html('');
    $(table_filters_giro.column(18).footer()).html('');
    $(table_filters_giro.column(19).footer()).html('');
    $(table_filters_giro.column(22).footer()).html('');
    table_filters_giro.draw();
    table_filters_giro.clear().draw();
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('giro_de_estoque.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            
            if(data.response.produto_grupo == 'produto'){
                    table_filters_giro.columns('.codigo_produto').visible(true);
                    table_filters_giro.columns('.descricao').visible(true);
                    table_filters_giro.columns('.un').visible(true);
                    table_filters_giro.columns('.custo_gerencial').visible(true);
                    table_filters_giro.columns('.preco_venda').visible(true);
                    table_filters_giro.columns('.grupo').visible(false);
                    table_filters_giro.columns('.marca').visible(false);
                    table_filters_giro.columns('.linha').visible(false);
                }
                else{
                    table_filters_giro.columns('.codigo_produto').visible(false);
                    table_filters_giro.columns('.descricao').visible(false);
                    table_filters_giro.columns('.un').visible(false);
                    table_filters_giro.columns('.custo_gerencial').visible(false);
                    table_filters_giro.columns('.preco_venda').visible(false);
                    table_filters_giro.columns('.grupo').visible(true);
                    table_filters_giro.columns('.marca').visible(true);
                    table_filters_giro.columns('.linha').visible(true);
                }

            produtos = [];
            
            for (var fields in data.response.saida){
                temp_array = [
                    "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].grupo + "''>" + data.response.saida[fields].grupo+ "</div></div>",
                    "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].marca + "''>" + data.response.saida[fields].marca + "</div></div>",
                    "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].linha + "''>" + data.response.saida[fields].linha  + "</div></div>",
                    data.response.saida[fields].codigo,
                    "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].descricao + "''>" + data.response.saida[fields].descricao + "</div></div>",
                    data.response.saida[fields].unidade,
                    data.response.saida[fields].ultima_compra,
                    data.response.saida[fields].custo_gerencial,
                    data.response.saida[fields].preco_venda,
                    criarLinkEstoque(data.response.saida[fields]),
                    data.response.saida[fields].estoque_transito,
                    data.response.saida[fields].participacao_estoque,
                    criarLinkComprasMesAtual(data.response.saida[fields]),
                    criarLinkComprasProximoMes(data.response.saida[fields]),
                    criarLinkComprasMesSeguinte(data.response.saida[fields]),
                    criarLinkComprasProximosMeses(data.response.saida[fields]),
                    data.response.saida[fields].pedidos_aberto,
                    data.response.saida[fields].saldo,
                    criarLinkVendas(data.response.saida[fields]),
                    data.response.saida[fields].media_diaria,
                    data.response.saida[fields].participacao_vendas,
                    data.response.saida[fields].media_mes,
                    data.response.saida[fields].estoque_meses,
                    data.response.saida[fields].compras_necessidade,
                    data.response.saida[fields].giro,
                    data.response.saida[fields].media_venda,
                ];
                produtos.push(temp_array)
            }
            
            $(table_filters_giro.column(0).footer()).html((data.response.total.estoque) ? 'TOTAL' : '');
            $(table_filters_giro.column(9).footer()).html((data.response.total.estoque) ? criarLinkEstoqueTotal(data.response.total) : '');
            $(table_filters_giro.column(10).footer()).html(data.response.total.estoque_transito);
            $(table_filters_giro.column(11).footer()).html(data.response.total.participacao_estoque);
            $(table_filters_giro.column(12).footer()).html((data.response.total.compras_mes_atual) ? criarLinkComprasMesAtualTotal(data.response.total) : '');
            $(table_filters_giro.column(13).footer()).html((data.response.total.compras_proximo_mes) ? criarLinkComprasProximoMesTotal(data.response.total) : '');
            $(table_filters_giro.column(14).footer()).html((data.response.total.compras_mes_seguinte) ? criarLinkComprasMesSeguinteTotal(data.response.total) : '');
            $(table_filters_giro.column(15).footer()).html((data.response.total.compras_proximos_meses) ? criarLinkComprasProximosMesesTotal(data.response.total) : '');
            $(table_filters_giro.column(16).footer()).html(data.response.total.pedidos_aberto);
            $(table_filters_giro.column(17).footer()).html(data.response.total.saldo);
            $(table_filters_giro.column(18).footer()).html((data.response.total.vendas) ? criarLinkVendasTotal(data.response.total) : '');
            $(table_filters_giro.column(19).footer()).html(data.response.total.media_diaria);
            $(table_filters_giro.column(20).footer()).html(data.response.total.participacao_vendas);
            $(table_filters_giro.column(23).footer()).html(data.response.total.compras_necessidades);
            $(table_filters_giro.column(25).footer()).html(data.response.total.media_venda);
            table_filters_giro.rows.add(produtos).draw();   
            
        },
        error: function(data){
            var errors = data.responseJSON.error;
            for(var field in errors){
                showErrorsInputs(form, field, errors[field])
            }
        }
    });
}

function criarLinkEstoque($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_preco.modal.estoque') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE ESTOQUE' class='bt-modal'>"+$this.estoque +"</a>"
    return html;
}

function criarLinkComprasMesAtual($this){
    var html = "<a href=\"#\" data-route=\"{{ route('giro_de_estoque.compras') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE COMPRAS MÊS {{ parserNameMonthFull(date("m"))}}' data-mes_atual='true' class='bt-modal'>"+$this.compras_mes_atual +"</a>"
    return html;
}

function criarLinkComprasMesAtualTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('giro_de_estoque.compras') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE COMPRAS TOTAL MÊS {{ parserNameMonthFull(date("m"))}}' data-mes_atual='true' class='bt-modal'>"+$this.compras_mes_atual +"</a>"
    return html;
}

function criarLinkComprasProximoMes($this){
    var html = "<a href=\"#\" data-route=\"{{ route('giro_de_estoque.compras') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE COMPRAS MÊS {{ parserNameMonthFull(date("m", strtotime("+1 months")))}}' data-proximo_mes='true' class='bt-modal'>"+$this.compras_proximo_mes +"</a>"
    return html;
}

function criarLinkComprasMesSeguinte($this){
    var html = "<a href=\"#\" data-route=\"{{ route('giro_de_estoque.compras') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE COMPRAS MÊS {{ parserNameMonthFull(date("m", strtotime("+2 months")))}}' data-mes_seguinte='true' class='bt-modal'>"+$this.compras_mes_seguinte +"</a>"
    return html;
}

function criarLinkComprasProximosMeses($this){
    var html = "<a href=\"#\" data-route=\"{{ route('giro_de_estoque.compras') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE COMPRAS PRÓXIMO MESES' data-proximos_meses='true' class='bt-modal'>"+$this.compras_proximos_meses +"</a>"
    return html;
}

function criarLinkComprasProximosMesesTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('giro_de_estoque.compras') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE COMPRAS TOTAL PRÓXIMO MESES' data-proximos_meses='true' class='bt-modal'>"+$this.compras_proximos_meses +"</a>"
    return html;
}

function criarLinkComprasMesSeguinteTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('giro_de_estoque.compras') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE COMPRAS TOTAL MÊS {{ parserNameMonthFull(date("m", strtotime("+2 months")))}}' data-mes_seguinte='true' class='bt-modal'>"+$this.compras_mes_seguinte +"</a>"
    return html;
}

function criarLinkComprasProximoMesTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('giro_de_estoque.compras') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE COMPRAS TOTAL MÊS {{ parserNameMonthFull(date("m", strtotime("+1 months")))}}' data-proximo_mes='true' class='bt-modal'>"+$this.compras_proximo_mes +"</a>"
    return html;
}

function  criarLinkEstoqueTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_preco.modal.estoque') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE ESTOQUE TOTAL' class='bt-modal'>"+$this.estoque +"</a>"
    return html;
}

function  criarLinkEstoqueTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('analise_preco.modal.estoque') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE ESTOQUE TOTAL' class='bt-modal'>"+$this.estoque +"</a>"
    return html;
}

function  criarLinkVendas($this){
    var html = "<a href=\"#\" data-route=\"{{ route('giro_de_estoque.vendas') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE VENDAS' data-total_vendas='false' class='bt-modal'>"+$this.vendas +"</a>"
    return html;
}

function  criarLinkVendasTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('giro_de_estoque.vendas') }}\" data-filter=\""+$this.filters+"\" data-title='ANALITÍCO DE VENDAS' data-total_vendas='true' class='bt-modal'>"+$this.vendas +"</a>"
    return html;
}

function filterClear(){
    table_filters_giro.clear().draw();
}

function showErrorsInputs(form, input, message){
    var $input = $(form).find("input[name='"+input+"']");
    $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
    $input.addClass('error-input');

    var $select = $(form).find("select[name='"+input+"']");
    $select.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
    $select.addClass('error-input');
}

function limparMesagemErro(form){   
    form.find('.error-message').remove();
    form.find('input, select, span').removeClass('error-input');
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

function abrirModal($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var mes_atual = $($this).data('mes_atual');
    var proximo_mes = $($this).data('proximo_mes');
    var mes_seguinte = $($this).data('mes_seguinte');
    var proximos_meses = $($this).data('proximos_meses');
    var total_vendas = $($this).data('total_vendas');
    xhr = $.ajax({
        url: url,
        data: {
            _token: "{{ csrf_token() }}", filters: filter,
             mes_atual: mes_atual, 
             proximo_mes: proximo_mes, 
             mes_seguinte: mes_seguinte,
             proximos_meses: proximos_meses,  
             total_vendas: total_vendas,
             },
        method: 'POST',
        success: function(body){
            createModal("modal_produtos", title, body, 'modal-lg');
        }
    });
}

@endsection
