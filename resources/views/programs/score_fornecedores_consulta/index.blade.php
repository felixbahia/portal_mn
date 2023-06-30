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
                    {{ Form::text('data_inicio', date('01/m/Y'),['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '20']) }}
                </div>
                <div class="col-lg-2">
                    {{ Form::text('data_fim', date('d/m/Y'),['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '20']) }}
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
<div class="float-left container-graficos-score">
    <div class="row">
        <div class="col-lg-12">
            <div class="nav-graficos">
            </div>
            <div class="tab-content content-tabs-graficos" id="abas_grafico_score">
            </div>
        </div>
    </div>
</div>
@endsection

@section('script-footer')
$(document).ready(function(){
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

    $(document).find("#fornecedor").val('');
    $(document).find("#fornecedor").autocomplete(optionsAutoCompleteFornecedor());
    $(document).find("#bt-search-fornecedor-busca").on("click", function(){
        showModalFornecedor($(this).data("route"), "Lista de Fornecedores");
    });

});

var graficos_criados = [];
var graficos = [];

function filterAjax(){

    form = $(document).find("#form_filter");
    data_form = form.serialize();
    $(document).find('.error-message').remove();

    $.ajax({
        url: '{{ route('score_fornecedores_consulta.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(data){

            var retorno = data.response.retorno;
            var conteudo = data.response.retorno;
            var total = data.response.total;
            
            if(retorno.length != 0){
                var contador = 1;
                var contador_conteudo = 1;
                var contador_conteudo_div = 1;
                var contador_conteudo_div_grafico = 1;
                var cores = ["#00BFFF", "#836FFF","#C0C0C0","#98FB98","#BDB76B","#FA8072","#FFFF00","#B0E0E6","#D8BFD8","#FFA500",
                "#7CFC00", "#00FA9A","#F4A460","#FF69B4","#EED2EE","#FFA54F","#F0FFF0","#FFFFFF","#FFF68F","#BEBEBE"];
                var menu = '<ul class="nav nav-tabs" id="menu_tab_score" role="tablist">';
                var active = 'active';
                var conteudo_grafico = '';

                for(var fields in conteudo){
                    var grupo = conteudo[fields].grupo;
                    var conteudo_div = '';
                    
                    if (!$(".nav-item")[0]){
                        menu += '<li class="nav-item">'+
                            '<a class="nav-link '
                            + active +
                            '" id="'
                            + grupo +
                            '-tab" data-toggle="tab" href="#link_'
                            + contador_conteudo +
                            '" role="tab" aria-controls="'
                            + grupo +
                            '" aria-selected="true">'+ grupo +'</a>'
                        +'</li>';

                        for(var fields_conteudo in conteudo[fields].pergunta){
                            conteudo_div += '<div class="w-100 conteudo_chart' 
                            + contador_conteudo_div +
                            '"></div>';
                            
                            contador_conteudo_div ++;
                        }

                        conteudo_grafico += '<div class="tab-pane fade show '
                        + active +
                        '" id="link_'
                        + contador_conteudo +
                        '" role="tabpanel" aria-labelledby="'
                        + grupo +
                        '-tab">' + conteudo_div + '</div>';
                    }

                    active = '';
                    contador_conteudo ++;
                }

                menu += '</ul>';

                $('.nav-graficos').append( menu );
                $('.content-tabs-graficos').append( conteudo_grafico );

                for(var field in retorno){
                    var grupo = retorno[field].grupo;

                    for(var fields_conteudo in retorno[field].pergunta){
                        var pergunta = retorno[field].pergunta[fields_conteudo].pergunta;
                        var respostas = retorno[field].pergunta[fields_conteudo].resposta;
                        var char_area = 'chart-area'+contador_conteudo_div_grafico;
                        
                        gerarLayoutGrafico(contador_conteudo_div_grafico,pergunta,respostas,total,'.conteudo_chart'+contador_conteudo_div_grafico);

                        gerarGrafico(respostas,pergunta,cores,char_area,contador_conteudo_div_grafico);

                        contador_conteudo_div_grafico ++;
                    }


                    contador ++;
                }

                $(document).find(".bt-abertura-fornecedores").off("click");
                $(document).find(".bt-abertura-fornecedores").on("click", function(event){
                    event.stopPropagation();
                    modalListaFornecedores($(this));
                });
            }else{
                $(document).find('.nav-graficos').html("");
                $(document).find('.content-tabs-graficos').html("");
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


function gerarLayoutGrafico(contador,pergunta,respostas,quantidade_total,link_conteudo){
    var char_area = 'chart-area'+contador;
    var div_table = 'table-area'+contador;
    var linhas = '';
    var total = 0;
    var contagem_itens = 0;
    var filtro = '';
    var total_quantidade_tipo1 = 0;
    var questao = '';

    for(var fields in respostas){
        var alternativa = respostas[fields].resposta == null ? 'Nenhuma' : respostas[fields].resposta;
        contagem_itens ++;
        filtro = respostas[fields].filtro;
        questao = respostas[fields].resposta;

        if(respostas[fields].tipo_resposta == 1){
            var link = criarBoataoFornecedoresPontuacao(respostas[fields]);
            total = total + respostas[fields].soma_respostas;
            total_quantidade_tipo1 = respostas[fields].resposta == null ? 0 : total_quantidade_tipo1 + respostas[fields].quantidade_resposta;
            var perguntas = 'Pergunta';
            var tipo_quantidade = 'QTD/SOMA';
        }else if(respostas[fields].tipo_resposta == 2){
            var link = respostas[fields].quantidade > 0 ? criarBoataoFornecedoresClassificacao(respostas[fields]) : '0';
            total = total + respostas[fields].quantidade;
            var perguntas = 'Resposta';
            var tipo_quantidade = 'QTD';
        }else if(respostas[fields].tipo_resposta == 3){
            var link = respostas[fields].quantidade > 0 ? criarBoataoFornecedoresClassificacao(respostas[fields]) : '0';
            total = total + respostas[fields].quantidade;
            var perguntas = 'Resposta';
            var tipo_quantidade = 'QTD';
        }else if(respostas[fields].tipo_resposta == 11){
            var link = respostas[fields].quantidade > 0 && respostas[fields].resposta != null ? criarBoataoFornecedoresClassificacao(respostas[fields]) : '0';
            total = respostas[fields].resposta != null ? total + respostas[fields].quantidade : 0;
            var perguntas = 'Resposta';
            var tipo_quantidade = 'QTD';
        }else if(respostas[fields].tipo_resposta == 10){
            var link = respostas[fields].quantidade > 0 && respostas[fields].resposta != null ? criarBoataoFornecedoresClassificacao(respostas[fields]) : '0';
            total = respostas[fields].resposta != null ? total + respostas[fields].quantidade : 0;
            var perguntas = 'Resposta';
            var tipo_quantidade = 'QTD';
        }else if(respostas[fields].tipo_resposta == 12){
            var link = respostas[fields].quantidade > 0 && respostas[fields].resposta != null ? criarBoataoFornecedoresClassificacao(respostas[fields]) : '0';
            total = respostas[fields].resposta != null ? total + respostas[fields].quantidade : 0;
            var perguntas = 'Resposta';
            var tipo_quantidade = 'QTD';
        }else if(respostas[fields].tipo_resposta == 9){
            var link = respostas[fields].quantidade > 0 ? criarBoataoFornecedoresClassificacao(respostas[fields]) : '0';
            total = total + respostas[fields].quantidade;
            var perguntas = 'Resposta';
            var tipo_quantidade = 'QTD';
        }else if(respostas[fields].tipo_resposta == 7){
            var link = respostas[fields].quantidade > 0 ? criarBoataoFornecedoresClassificacao(respostas[fields]) : '0';
            total = total + respostas[fields].quantidade;
            var perguntas = 'Resposta';
            var tipo_quantidade = 'QTD';
        }

        linhas += '<tr>'+
            '<td class="text-left">'+
                alternativa +
                '</td>'+
            '<td class="text-right">'+
                    link+
                '</td>'+
            '</tr>';
    }

    total_quantidade = total;
    total = total / total_quantidade_tipo1;
    
    if(perguntas == 'Pergunta'){
        var link_total = criarBoataoFornecedoresPontuacaoTotal(filtro,0,total.toFixed(2),questao);
        perguntas = 'Resposta';
        linhas += 
        '<tr>'+
            '<td class="text-left">'+
                'Soma Total' +
                '</td>'+
            '<td class="text-right">'+
                criarBoataoFornecedoresPontuacaoTotal(filtro,total_quantidade_tipo1,total_quantidade.toFixed(2),questao) +
                '</td>'+
        '</tr>'+
        '<tr>'+
            '<td class="text-left">'+
                'Média' +
                '</td>'+
            '<td class="text-right">'+
                link_total +
                '</td>'+
        '</tr>';
    }else if(perguntas == 'Resposta'){
        var link_total = questao != null ? criarBoataoFornecedoresPontuacaoTotal(filtro,0,total_quantidade.toFixed(2),questao) : 0;
        linhas += '<tr>'+
            '<td class="text-left">'+
                'Total' +
                '</td>'+
            '<td class="text-right">'+
                link_total +
                '</td>'+
            '</tr>';
    }

    var valor_questao = 0;

    if(questao != null){
        valor_questao = quantidade_total;
    }

    linhas += '<tr>'+
        '<td class="text-left">'+
            'QTD Score Total' +
            '</td>'+
        '<td class="text-right">'+
            valor_questao +
            '</td>'+
        '</tr>';

    if($("#"+div_table).length){
        
        $(document).find("#"+div_table).html("");
        var conteudo = '<table class="w-100 table table-not-edit table-grafico-consulta table-not-view">'+
                            '<thead>'+
                                '<tr>'+
                                    '<th class="text-left">'+perguntas+'</th>'+
                                    '<th class="text-right">'+tipo_quantidade+'</th>'+
                                '</tr>'+
                            '</thead>'+
                            '<tbody>'+
                                linhas +
                            '</tbody>'+
                        '</table>';

                        
        $("#"+div_table).append( conteudo );
                    
    }else{

        var conteudo = '<div id="canvas-holder" class="w-100 row float-left position-relative m-2 h-100">'+
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
                                '<th class="text-left">'+perguntas+'</th>'+
                                '<th class="text-right">'+tipo_quantidade+'</th>'+
                            '</tr>'+
                        '</thead>'+
                        '<tbody>'+
                            linhas +
                        '</tbody>'+
                    '</table>'+
                '</div>'+
            '</div>'+
        '</div>';
        
        $(link_conteudo).append( conteudo );
    }

}

function gerarGrafico(respostas,pergunta,cores,char_area,contador){
    var randomScalingFactor = function() {
        return Math.round(Math.random() * 100);
    };

    var quantidades = [];
    var cont = 0;
    var total = 0;
    var cor = [];
    var label = [];

    for(var fields in respostas){
        label.push(respostas[fields].resposta);

        if(respostas[fields].tipo_resposta == 1){
            quantidades.push(respostas[fields].quantidade_resposta);
        }else if(respostas[fields].tipo_resposta == 2){
            quantidades.push(respostas[fields].quantidade);
        }else if(respostas[fields].tipo_resposta == 3){
            quantidades.push(respostas[fields].quantidade);
        }else if(respostas[fields].tipo_resposta == 11){
            quantidades.push(respostas[fields].quantidade);
        }else if(respostas[fields].tipo_resposta == 12){
            quantidades.push(respostas[fields].quantidade);
        }else if(respostas[fields].tipo_resposta == 10){
            quantidades.push(respostas[fields].quantidade);
        }else if(respostas[fields].tipo_resposta == 9){
            quantidades.push(respostas[fields].quantidade);
        }else if(respostas[fields].tipo_resposta == 7){
            quantidades.push(respostas[fields].quantidade);
        }

        cor.push(cores[cont]);
        cont ++;

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


function criarBoataoFornecedoresPontuacao($this){
    var valor = 0;
    if($this.resposta != null){
        valor = $this.quantidade_resposta;
    }
    var html = "<a href=\"#\" data-route=\"{{ route('score_fornecedores_consulta.modal.lista_fornecedores') }}\" data-filtro=\""+$this.filtro+"\" data-total='false' data-respondidos='false' data-title='Lista de Fornecedores' class='bt-abertura-fornecedores'>"+ valor + " / " +$this.soma_respostas.toFixed(2)+"</a>"
    return html;
}

function criarBoataoFornecedoresPontuacaoTotal($filtro,$total_quantidade,$total,$questao){
    if($total_quantidade > 0 && $total > 0){
        var html = "<a href=\"#\" data-route=\"{{ route('score_fornecedores_consulta.modal.lista_fornecedores') }}\" data-filtro=\""+$filtro+"\" data-total='true' data-respondidos='false' data-title='Lista de Fornecedores' class='bt-abertura-fornecedores'>"+ $total_quantidade + " / " + $total+"</a>"
    }else if($total > 0 && $questao != null){
        var html = "<a href=\"#\" data-route=\"{{ route('score_fornecedores_consulta.modal.lista_fornecedores') }}\" data-filtro=\""+$filtro+"\" data-total='true' data-respondidos='false' data-title='Lista de Fornecedores' class='bt-abertura-fornecedores'>"+$total+"</a>"
    }else{
        var html = "<a href=\"#\" data-route=\"{{ route('score_fornecedores_consulta.modal.lista_fornecedores') }}\" data-filtro=\""+$filtro+"\" data-total='true' data-respondidos='false' data-title='Lista de Fornecedores' class='bt-abertura-fornecedores'>0</a>"
    }
    return html;
}

function criarBoataoFornecedoresClassificacao($this){
    var valor = 0;

    if($this.resposta != null){
        valor = $this.quantidade;
    }

    var html = "<a href=\"#\" data-route=\"{{ route('score_fornecedores_consulta.modal.lista_fornecedores') }}\" data-filtro=\""+$this.filtro+"\" data-total='false' data-respondidos='false' data-title='Lista de Fornecedores' class='bt-abertura-fornecedores'>"+valor+"</a>"
    return html;
}

function modalListaFornecedores($this){
    var url = $($this).data("route");
    var filtro = $($this).data("filtro");
    var title = $($this).data('title');
    var total = $($this).data('total');

    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filtro: filtro, total: total},
        method: 'POST',
        success: function(body){
            createModal("modal_pesquisa_satisfacao_clientes", title, body, 'modal-lg');
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

@endsection