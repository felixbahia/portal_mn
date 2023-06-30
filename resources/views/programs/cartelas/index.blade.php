@extends('layouts.app-deslogado')

@section('content')
<div id="oi">
    <div class="content-modulos-home">
        <center>
            <p>ARTIGO: {{ $book['artigo'] }} - {{ $book['nome'] }} - ORIGEM : {{ $book['origem'] }}</p>
        </center>
        <right>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="{{ route('book_virtual_exibicao.index') }}">BOOK VIRTUAL</a></right>
        <div class="content-table">
            <table class="table table-striped table-not-edit table-not-view" id="table-info-adicionais">
                <thead>
                    <tr>
                        <th colspan="5" align="center">Caracteristicas Técnicas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td align="center" valign="middle">{{ $itens['composicao'] }}</td>
                        <td align="center" valign="middle">
                            @if(!empty($itens['info_adicional']['informacoes_adicionais'])) 
                                @if(!empty($itens['info_adicional']['informacoes_adicionais']['largura']))
                                    Larg {{ $itens['info_adicional']['informacoes_adicionais']['largura'] }}
                                @endif
                                @if(!empty($itens['info_adicional']['informacoes_adicionais']['gramatura'])) 
                                    Grt {{ $itens['info_adicional']['informacoes_adicionais']['gramatura'] }} GML 
                                @endif 
                            @endif
                        </td>
                        <td align="center" valign="middle">{{ $book['caracteristicas'] }}</td>
                        <td align="center" valign="middle">Peças {{ $book['pecas'] }}</td>
                        <td align="center">
                            <a href="{{ asset($book["img_instrucoes_lavagem"]) }}" class="thumb-instrucao"><img src="{{ asset($book["thumb_instrucoes_lavagem"]) }}" border="0" alt="" style="max-width: 150px; height: auto; "/></a>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p>
        </div>
    </div>
    <div class="content-table">
        <table class="table table-striped table-produto-analise" id="table-produtos">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th class="tb_number">Pronta<br> Entrega</th>
                    @foreach($header_meses as $header_mes)
                    <th class="tb_number">{!! $header_mes !!}</th>
                    @endforeach
                    <th class="tb_number">Futuro</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itens['produtos'] as $produto)
                <tr>
                    <td>{{ $produto['codigo'] }}</td>
                    <td>{{ $produto['descricao'] }}</td>
                    <td class="tb_number">
                         <div class="bt-view-list" data-toggle="popover" data-trigger='hover' title="" data-content="@if(!empty($produto['estoque_dados'])) @foreach($produto['estoque_dados'] as $estoque)<p>{{ $estoque }}</p>@endforeach @endif" data-original-title="Estoque por empresa">
                            @if(!empty($produto['pronta_entrega_total']))
                                {{ $produto['pronta_entrega_total'] }}
                                @if(!empty($produto['estoque_dados']))
                                <div class="bt-view"></div>
                                @endif
                            @endif
                        </div>
                    </td>
                    @foreach($header_meses as $key => $header_mes)
                    <td class="tb_number">{{ $produto['quinzenas']['k_'.$key]['quantidade'] }}</td>
                    @endforeach 
                    <td class="tb_number">{{ $produto['futuro'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                @if(!empty($itens['totais']))
                <tr>
                    <td></td>
                    <td>Total:</td>
                    <td class="tb_number">{{ $itens['totais']['pronta_entrega'] }}</td>
                    @foreach($itens['totais']['quinzenas'] as $key => $header_mes)
                    <td class="tb_number">{{ $header_mes }}</td>
                    @endforeach
                    <td class="tb_number">{{ $itens['totais']['futuro'] }}</td>
                </tr>
                @endif
            </tfoot>
        </table>
    </div>
</div>
<div class="content-filter" style="clear: both;float: left;position: static;width: 98%;margin: 10px;margin-bottom: 10px;">
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <center><p>Preços - Prazos - Base 3%</p></center>
        <div class="content-fields">
            <div class="filtro-campos">
                <div class="row">
                    <div class="col-sm-2">
                        Entre com informações
                    </div>
                    @if(!empty($itens['produtos']))
                    {!! Form::hidden('produto', $itens['produtos'][0]['codigo'] , ['id'=>'produto']) !!}
                    @endif
                    {!! Form::hidden('coluna', 'coluna_a' , ['id'=>'coluna']) !!}
                    <div class="col-sm-2">
                        <select name="origem" id="origem">
                            <option value='' selected>Origem</option>
                            @foreach($origem as $key => $value)
                            <option value="{{$key}}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <select name="estado" id="estado">
                            <option disabled selected>Selecione a Origem</option>
                        </select>
                    </div>
                    <div class="col-sm-1">
                        <select name="moeda" id="moeda">
                            <option value=''>Moeda</option>
                            <option value="real" selected>Real</option>
                            <option value="dolar">Dólar</option>
                        </select>
                    </div>

                    <div class="col-sm-1">
                        <select name="frete" id="frete">
                            <option value=''>Frete</option>
                            <option value="fob">FOB</option>
                            <option value="cif">CIF</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <select name="tipo_cliente" id="tipo_cliente">
                            <option value="normal">Contribuinte</option>
                            <option value="isento">Isento</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <div class="content-buttons">
                            <button name="btn-filterform" id="btn-filterform" class="btn-filter float-right">Buscar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-filters-precos">
            <thead>
                <tr>
                    <th class="tb_number">Vista</th>
                    <th class="tb_number">15 DDL</th>
                    <th class="tb_number">30 DDL</th>
                    <th class="tb_number">45 DDL</th>
                    <th class="tb_number">60 DDL</th>
                    <th class="tb_number">75 DDL</th>
                    <th class="tb_number">90 DDL</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
@endsection
@section('script-footer')
table_filters = [];
table_produtos = [];
$height = 175;
table_produtos = $('#table-produtos').DataTable({
    "searching": false,
    "lengthChange": false,
    "info": false,
    "scrollX": false,
    "scrollY": $height,
    "scrollCollapse": true,
    "white-space": "nowrap",
    "paging": false,
    "autoWidth": true,
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
        { "class": "tb_date", targets: "sort-date" }
    ],
    "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
});

$(document).ready( function () {
    $(document).find("a.thumb-instrucao").fancybox({
        type: 'image'
    });
    $("#origem").on("change", function(event){
        selectEstado($(this).val());
        if ($('#origem').val() == 'RO'){
            $('#frete').val('cif');
            $('#moeda').parent().removeClass('d-none');
        }
        else if ($('#origem').val() == 'TO'){
            $('#moeda').val('real');
            $('#moeda').parent().addClass('d-none');
        }
        else {
            $('#moeda').parent().addClass('d-none');
            $('#moeda').val('real');
        }
        $("#estado").trigger('change');
    });
    $("#btn-filterform").on("click", function(){
        filterAjax($("#form_filter").serialize());
    });
    $('[data-toggle="popover"]').off('show.bs.popover');
    $('[data-toggle="popover"]').popover('hide');
    $('[data-toggle="popover"]').popover({
        container: 'body',
        html: true,
        show: true,
        template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
    });
    table_filters = $('#table-filters-precos').DataTable({
        "searching": false,
        "searching": false,
        "lengthChange": false,
        "info": false,
        "scrollX": false,
        "scrollCollapse": true,
        "paging": false,
        "autoWidth": true,
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
                "type": 'num-fmt', 
                "targets": "tb_number"},
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
    });

    @foreach($campos_salvos as $campo => $valor)
        @if($campo != 'coluna')
            $(document).find('#{{ $campo }}').val('{{ $valor }}');
        @endif
    @endforeach
    $("#origem").trigger('change');
    setTimeout(function(){
        $("#estado").trigger('change');
        @if(!empty($campos_salvos))
            filterAjax($("#form_filter").serialize());
        @endif
    }, 500);
});

function selectEstado(origem){
    $("#estado").find('option').remove();
    if (origem.length > 0){
        $.ajax({
            url: "{{ route('listagem_precos.aliquotas') }}",
            dataType: 'json',
            data: {_token: "{{ csrf_token() }}", origem: origem},
            method: 'POST',
            success: function(data){
                $("#estado").append("<option value='' selected>Destino</option>");
                for (var i in data){
                    $("#estado").append("<option value='"+data[i].value+"' data-regiao='"+ data[i].regiao +"'>" + data[i].html + "</option>");
                }
                @if(isset($campos_salvos['estado']))
                $("#estado").val("{{ $campos_salvos['estado'] }}");
                @else
                $("#estado").val('');
                @endif
            },
            error: function(data){
                $("#aliquota").append('<option>Erro</option>').prop('disabled selected');
            }
        });
    }
    else{
        $("#aliquota").append("<option value=''>Selecione origem</option>");            
    }
}

function filterAjax(data_form){
    filterClear();
    var form = $("#form_filter");
    form.find('.error-message').remove();
    form.find('input, select').removeClass('error-input');
    $.ajax({
        url: '{{ route('listagem_precos.filter')}}',
        dataType: 'json',
        data: data_form,
        method: 'POST',
        success: function(data){
            valores = [];
            for (var fields in data){
                temp_array = [
                    data[fields].prazo_vista,
                    data[fields].prazo_15,
                    data[fields].prazo_30,
                    data[fields].prazo_45,
                    data[fields].prazo_60,
                    data[fields].prazo_75,
                    data[fields].prazo_90
                ];
                valores.push(temp_array)
            }
            table_filters.rows.add(valores).draw();
        },
        error: function(data){
            hide_loader();
            if((data.responseJSON.errors)){
                var errors = data.responseJSON.errors;
                form.find('.error-message').remove();
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }else{
                message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
            }
        }
    });
}

function filterClear(){
    table_filters.clear().draw();
}

function showErrorsInputs(form, input, message){
    var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
    $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
    $input.addClass('error-input');
}

@endsection
