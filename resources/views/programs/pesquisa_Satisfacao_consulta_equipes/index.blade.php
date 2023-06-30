@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de Pesquisa de Satisfação por Equipes Últimos 12 Meses</h3>
        <div class="content-fields">
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
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </form>
@endsection
@section('content')
<div class="container content-tabs-graficos">

</div>
@endsection

@section('script-footer')
$(document).ready(function(){
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

    Chart.plugins.register({
        beforeDraw: function (chart) {
            if (chart.config.options.elements.center) {
        var ctx = chart.ctx;
        
        var centerConfig = chart.config.options.elements.center;
        var fontStyle = centerConfig.fontStyle || 'Arial';
        var txt = centerConfig.text;
        var color = centerConfig.color || '#000';
        var sidePadding = centerConfig.sidePadding || 20;
        var sidePaddingCalculated = (sidePadding/100) * (chart.innerRadius * 2)

        ctx.font = "30px " + fontStyle;
        
        var stringWidth = ctx.measureText(txt).width;
        var elementWidth = (chart.innerRadius * 2) - sidePaddingCalculated;

        var widthRatio = elementWidth / stringWidth;
        var newFontSize = Math.floor(30 * widthRatio);
        var elementHeight = (chart.innerRadius * 2);

        var fontSizeToUse = Math.min(newFontSize, elementHeight);
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        var centerX = ((chart.chartArea.left + chart.chartArea.right) / 2);
        var centerY = ((chart.chartArea.top + chart.chartArea.bottom) / 2);
        ctx.font = fontSizeToUse+"px " + fontStyle;
        ctx.fillStyle = color;
        
        ctx.fillText(txt, centerX, centerY);
            }
        }
    });

    var ctx = $('.content-tabs-graficos');

});

var graficos_criados = [];
var graficos = [];

function filterAjax(){

    form = $(document).find("#form_filter");
    data_form = form.serialize();
    $(document).find('.error-message').remove();

    $.ajax({
        url: '{{ route('pesquisa_satisfacao_consulta_equipes.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(data){

            var retorno = data.response.retorno;
            
            if(retorno.length != 0){
                var contador = 1;
                var cores = ["#00BFFF", "#836FFF","#C0C0C0","#98FB98","#BDB76B","#FA8072","#FFFF00","#B0E0E6","#D8BFD8","#FFA500",
                "#7CFC00", "#00FA9A","#F4A460","#FF69B4","#EED2EE","#FFA54F","#F0FFF0","#FFFFFF","#FFF68F","#BEBEBE"];
                
                for(var field in retorno){
                    var data = [];
                    var dataset = [];
                    var cont = 1;

                    for(var datas in retorno[field].respostas){
                        data.push(formatarData (datas));
                    }

                    for(var resposta in retorno[field].datas){
                        var resposta_quantidade = [];
                        var resposta_alternativa = [];
                        var cor = [];

                        for(var saida in retorno[field].datas[resposta]){
                            resposta_quantidade.push(retorno[field].datas[resposta][saida].quantidade);
                            cor.push(cores[cont]);
                        }


                        dataset.push({
                            label: resposta.replaceAll('_', ' ').toUpperCase(),
                            type: "bar",
                            data: resposta_quantidade,
                            backgroundColor: cor,
                        });

                        cont ++;
                    }

                    var char_area = 'chart-area'+contador;
                    var pergunta = retorno[field].pergunta;
                    var resposta = new Array();

                    if( $.inArray(char_area, graficos_criados) <= -1 ){
                        gerarLayoutGrafico(contador,pergunta,char_area);
                    }

                    gerarGrafico(data,dataset,cores,char_area,contador);

                    contador ++;
                }

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

function formatarData(valor) {
    var data = new Date(valor),
    mes  = (data.getMonth()+2).toString(),
    mesF = (mes.length == 1) ? '0'+mes : mes,
    anoF = data.getFullYear();
    return mesF+"/"+anoF;
}

function gerarLayoutGrafico(contador,pergunta,char_area){
    var linhas = '';
    var total = 0;
    var contagem_itens = 0;
    var filtro = '';


        var conteudo = '<div id="canvas-holder" class="w-100 row float-left position-relative m-2 h-100">'+
            '<div class="w-100">' +
                pergunta +
            '</div>' +   
            '<div id="titulo" class="col w-100 p-3">'+
                '<canvas id='+
                char_area +
                ' class="w-100 float-left position-relative h-100 d-inline-block"></canvas>'+
            '</div>'+
        '</div>';
        
        $('.content-tabs-graficos').append( conteudo );


}

function gerarGrafico(data,dataset,cores,char_area,contador){

    var quantidades = [];
    var total = 0;

    if( $.inArray(char_area, graficos_criados) !== -1 ){
        var config = graficos[contador - 1];

        config.config.data = {
            labels: data,
            datasets: dataset
        };
        config.update();

     }else{

        bar_chart = new Chart(char_area, {
            type: 'bar',
            data: {
                labels: data,
                datasets: dataset
            },
            options: {
                responsive: false,
                plugins: {
                    datalabels: {
                        display: false
                    }
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            },
        });

        graficos.push(bar_chart);
        graficos_criados.push(char_area);

    }
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

@endsection