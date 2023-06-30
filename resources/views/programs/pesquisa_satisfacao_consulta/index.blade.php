@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="col-lg-2">
                {{ Form::select("estabelecimento", $estabelecimentos, '', ["id" => "estabelecimento", "class"=>"form-control", 'placeholder' => 'Estabelecimentos']) }}
            </div>
            @if ($check_gerentes === true || $check_supervisores === true || $check_vendedor_representante === true)
                @if($check_gerentes === true)
                    <div class="col-lg-2">
                        {{ Form::select('gerentes', $gerentes, '', ["id" => 'gerentes', 'class' => 'form-control busca_left', 'placeholder' => 'Gerentes'])}}
                    </div>
                @endif
                @if($check_vendedor_representante === true)
                    <div class="col-lg-2">
                        {{ Form::select('vendedor_representante', $vendedor_representante, '', ["id" => 'vendedor_representante', 'class' => 'form-control', 'placeholder' => 'Vendedor Interno / Representantes'])}}
                    </div>
                @endif
            @endif
            <div class="col-lg-2">
                <div class="input-group">
                    {{ Form::text("cliente", '', ["id" => "cliente_filtro", "class" => "form-control input-label", "placeholder" => "Nome do Cliente",]) }}
                    {{ Form::hidden("cliente_id", '', ["id" => "cliente_id_filtro", "class" => "form-control", "placeholder" => "Código do Cliente"]) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-1">
                {{ Form::text('data_inicio_pesquisa_satisfacao', date('01/m/Y'),['id' => 'data_inicio_pesquisa_satisfacao', 'class' => 'data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-1">
                {{ Form::text('data_fim_pesquisa_satisfacao', date('d/m/Y'),['id' => 'data_fim_pesquisa_satisfacao', 'class' => 'data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-2">
                <div class="ml-4 mt-2">
                    {{ Form::checkbox('tipo_pesquisa', '1', '',  ['id' => 'tipo_pesquisa', 'class' => 'form-check-input']) }}
                    {{ Form::label('tipo_pesquisa', 'Pesquisas Não Respondidas', ['class' => 'form-check-label','for' => 'tipo_pesquisa']) }}
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
    <div class="container graficos">

    </div>
@endsection

@section('script-footer')
$(document).ready(function(){
    $(document).find("#bt-search-cliente-busca").off("click");
    $(document).find("#bt-search-cliente-busca").on("click", function(event){
        event.stopPropagation();
        showModalClienteBusca($(this).data("route"));
        return false;
    });
    $(document).find("#cliente_filtro").autocomplete(optionsAutoCompleteClienteFiltro());

    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        autoHide: true
    });
    

    form = $(document).find("#form_filter");
    form.find("#btn-filterform").off("click");
    form.find("#btn-filterform").on("click", function(){
        filterAjax();
    });


    $(document).find(".busca_left").on('change', function(event){
        var campos = $(document).find("select:visible");
        var indice = campos.index(event.target) + 1;
        var seletor = $(campos[indice]);
        checkDadosUser(seletor, $(this).val());
    });

    setInterval(function(){
        clearFieldIdCliente();
    }
    , 500);
});

function clearFieldIdCliente(){
    if($(document).find("#cliente_filtro").val() == ''){
        $(document).find("#cliente_id_filtro").val('');
    }
}

var graficos_criados = [];
var graficos = [];

function filterAjax(){

    form = $(document).find("#form_filter");
    data_form = form.serialize();
    $(document).find('.error-message').remove();

    $.ajax({
        url: '{{ route('pesquisa_satisfacao_consulta.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(data){

            var retorno = data.response.retorno;
            
            if(retorno.length != 0){
                var nao_respondidos = data.response.nao_respondidos;
                var contador = 1;
                var cores = ["#00BFFF", "#836FFF","#C0C0C0","#98FB98","#BDB76B","#FA8072","#FFFF00","#B0E0E6","#D8BFD8","#FFA500"];

                for(var field in retorno){
                    var pergunta = field;
                    var respostas = new Array();
                    respostas = retorno[field];
                    var char_area = 'chart-area'+contador;

                    gerarLayoutGrafico(contador,pergunta,respostas,nao_respondidos);

                    gerarGrafico(respostas,pergunta,cores,char_area,nao_respondidos,contador);

                    contador ++;
                }
                $(document).find(".bt-abertura-clientes").off("click");
                $(document).find(".bt-abertura-clientes").on("click", function(event){
                    event.stopPropagation();
                    modalClientesPesquisaSatisfacao($(this));
                });
            }else{
                $(document).find('.graficos').html("");
                graficos_criados = [];
                graficos = [];
            }

        },
        error: function(callback){
            if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                $.each(data, function(index, el) {
                    form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>');
                    form.find('input[name="'+index+'"]').eq(0).addClass('error');
                });
                form.find('input.error').eq(0).focus();
            }
        }
    });
}

function gerarGrafico(alternativas,pergunta,cores,char_area,nao_respondidos,contador){
    var randomScalingFactor = function() {
        return Math.round(Math.random() * 100);
    };

    var quantidades = [];
    var cont = 0;
    var total = 0;
    var cor = [];
    var label = [];

    
    for(var fields in alternativas){
        for(var field in alternativas[fields]){
            total = total + alternativas[fields][field].quantidade;
        }
    }

    if(nao_respondidos.nao_respondidos > 0){
        total = total + nao_respondidos.nao_respondidos;
    }

    for(var fields in alternativas){
        for(var field in alternativas[fields]){
            var alternativa = alternativas[fields][field].alternativa.replaceAll('_', ' ').toUpperCase();
            quantidades.push(alternativas[fields][field].quantidade);
            label.push(alternativa);
            cor.push(cores[cont]);
            cont ++;
        }
    }

    if(nao_respondidos.nao_respondidos > 0){
        cont ++;
        quantidades.push(nao_respondidos.nao_respondidos);
        label.push('Não Respondidos');
        cor.push(cores[cont]);
    }

    
    if( $.inArray(char_area, graficos_criados) !== -1 ){
        var config = graficos[contador - 1];
        config.data = {
            labels: label,
            datasets: [{
                    data: quantidades,
                    backgroundColor: cor,
                    label: 'Dataset 1'
                }]
        };
        config.update();

     }else{

        var config = {
            type: 'pie',
            data: {
                datasets: [{
                    data: quantidades,
                    backgroundColor: cor,
                    label: 'Dataset 1'
                }],
                labels: label
            },
            options: {
                responsive: true,
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        fontColor: 'rgb(0, 0, 0)',
                        fontSize: 15
                    }
                },
                tooltips: {
                  enabled: false
                },
                title: {
                    display: true,
                    text: pergunta,
                    fontSize: 20,
                    fontColor: 'rgb(0, 0, 0)'
                },
                plugins: {
                    datalabels: {
                        formatter: (value, ctx) => {
                            let datasets = ctx.chart.data.datasets;
                            if (datasets.indexOf(ctx.dataset) === datasets.length - 1) {
                                let sum = datasets[0].data.reduce((a, b) => a + b, 0);
                                let percentage = parseFloat(((value / sum) * 100).toFixed(2)) + '%';
                                return percentage;
                            } else {
                                return percentage;
                            }
                        },
                        color: '#000',
                    }
                }  
            },         
        };

        var ctx = document.getElementById(char_area).getContext('2d');
        pie_chart = new Chart(ctx, config);

        graficos.push(pie_chart);
        graficos_criados.push(char_area);
    }
}

function gerarLayoutGrafico(contador,pergunta,alternativas,nao_respondidos){
    var char_area = 'chart-area'+contador;
    var div_table = 'table-area'+contador;
    var linhas = '';
    var total = 0;
    var filtro = '';

    for(var fields in alternativas){
        for(var field in alternativas[fields]){
        var alternativa = alternativas[fields][field].alternativa.replaceAll('_', ' ');
        var link = criarBoataoClientes(alternativas[fields][field]);
        total = total + alternativas[fields][field].quantidade;
        filtro = alternativas[fields][field];

        linhas += '<tr>'+
            '<td class="text-left">'+
                alternativa +
                '</td>'+
            '<td class="text-right">'+
                    link+
                '</td>'+
            '</tr>';
        }
    }


    if(nao_respondidos.nao_respondidos > 0){
        total = total + nao_respondidos.nao_respondidos;
        var link_nao_respondidos = criarBoataoNaoRespondidos(filtro,nao_respondidos.nao_respondidos)
        linhas += '<tr>'+
            '<td class="text-left">'+
                'Não Respondidos' +
                '</td>'+
            '<td class="text-right">'+
                link_nao_respondidos +
                '</td>'+
            '</tr>';
    }

    var link_total = criarBoataoClientesTotal(filtro,total);
    linhas += '<tr>'+
        '<td class="text-left">'+
            'Total' +
            '</td>'+
        '<td class="text-right">'+
            link_total +
            '</td>'+
        '</tr>';

    if($("#"+div_table).length){ 
        
        $(document).find("#"+div_table).html("");
        var conteudo = '<table class="w-100 table table-not-edit table-grafico-consulta table-not-view">'+
                            '<thead>'+
                                '<tr>'+
                                    '<th class="text-left">Respostas</th>'+
                                    '<th class="text-right">QTD</th>'+
                                '</tr>'+
                            '</thead>'+
                            '<tbody>'+
                                linhas +
                            '</tbody>'+
                        '</table>';

                        
        $("#"+div_table).append( conteudo );
                    
    }else{

        var conteudo = '<div id="canvas-holder" class="w-100 row border border-info rounded bg-light float-left position-relative m-2 h-100">'+
            '<div id="titulo" class="col w-100 p-3">'+
                '<canvas id='+
                char_area +
                ' class="w-75 float-left position-relative h-100 d-inline-block"></canvas>'+
                '<div id='+
                div_table +
                ' class="w-25 h-75 float-left position-relative">'+
                    '<table class="w-100 table table-not-edit table-grafico-consulta table-not-view">'+
                        '<thead>'+
                            '<tr>'+
                                '<th class="text-left">Respostas</th>'+
                                '<th class="text-right">QTD</th>'+
                            '</tr>'+
                        '</thead>'+
                        '<tbody>'+
                            linhas +
                        '</tbody>'+
                    '</table>'+
                '</div>'+
            '</div>'+
        '</div>';
        
        $('.graficos').append( conteudo );
    }

}

function criarBoataoClientes($this){
    var html = "<a href=\"#\" data-route=\"{{ route('pesquisa_satisfacao_consulta.modal.abertura_clientes') }}\" data-filter=\""+$this.filtro+"\" data-total='false' data-respondidos='false' data-title='Lista de Clientes' class='bt-abertura-clientes'>"+$this.quantidade+"</a>"
    return html;
}

function criarBoataoClientesTotal($this,total){
    var html = "<a href=\"#\" data-route=\"{{ route('pesquisa_satisfacao_consulta.modal.abertura_clientes') }}\" data-filter=\""+$this.filtro+"\" data-total='true' data-respondidos='false' data-title='Lista de Clientes' class='bt-abertura-clientes'>"+total+"</a>"
    return html;
}

function criarBoataoNaoRespondidos($this,nao_respondidos){
    var html = "<a href=\"#\" data-route=\"{{ route('pesquisa_satisfacao_consulta.modal.abertura_clientes') }}\" data-filter=\""+$this.filtro+"\" data-total='false' data-respondidos='true' data-title='Lista de Clientes com formulário não respondido' class='bt-abertura-clientes'>"+nao_respondidos+"</a>"
    return html;
}

function modalClientesPesquisaSatisfacao($this){

    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var total = $($this).data('total');
    var respondidos = $($this).data('respondidos');

    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters: filter, total: total, respondidos: respondidos},
        method: 'POST',
        success: function(body){
            createModal("modal_pesquisa_satisfacao_clientes", title, body, 'modal-lg');
        }
    });
}

@if ($check_gerentes === true || $check_vendedor_representante === true)
function checkDadosUser(campo_busca, valor){

    primeira_opcao = $(campo_busca).find("option:first").html();
    $(campo_busca).html("");
    var campos = "<option value=\"\">" + primeira_opcao + "</option>";
    $.ajax({
        url: "{{ route('usuario.dados_subordinados_outros') }}",
        dataType: 'json',
        data: {_token:'{{ csrf_token() }}', user: valor},
        method: 'POST',
        success: function(callback){
            if(callback.status === "success"){
                var response = callback.response;
                for(var line in response){
                    campos += "<option value=\""+response[line].id+"\">"+response[line].name+"</option>";
                }
            }
        },
        error: function(data){
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }
    }).done(function(){
        $(campo_busca).html(campos).focus();
    });
}
@endif


function showModalClienteBusca(url){
    var title = "Busca de Clientes";
    $.ajax({
        url: url,
        method: "POST",
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
    $(document).find("#cliente_id_filtro").val($dados.find("td").eq(0).text());
    $(document).find("#cliente_filtro").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
    $(document).find("#cliente_searsh_show").modal("hide");
    $.ajax({
        url: "{{ route("cliente.salvaClientePadrao") }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            codcad: $dados.find("td").eq(0).text()
        }
    });
}

function optionsAutoCompleteClienteFiltro(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route("clientes.autocomplete") }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message("Atenção", "Nenhum cliente encontrado");
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#cliente_id_filtro").val(ui.item.value);
            $(document).find("#cliente_filtro").val(ui.item.label);
            return false;
        }
    };
}
@endsection