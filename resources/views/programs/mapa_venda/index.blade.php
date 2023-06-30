@extends('layouts.app')

@section('content-filter') 
    <label id="verificacao_atualizacao" name="verificacao_atualizacao">{!! $horario !!}</label>
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="col-lg-2">
                {{ Form::select('tipo_data', $tipo_datas, '', ['id' => 'tipo_data', 'class' => 'form-control', 'maxlength' => '20']) }}
            </div>
            <div class="show-on-mensal col-lg-3"> 
                {{ Form::text('mes_ano', $data, ['id' => 'mes_ano', 'class' => 'form-control data', 'placeholder' => 'Mês/Ano', 'maxlength' => '20']) }}
            </div>
            <div class="show-on-semanas col-lg-1">
                {{ Form::select('semanas', [], '', ['id' => 'semanas', 'class' => 'form-control', 'maxlength' => '20']) }}
            </div>
            <div class="show-on-quinzenas col-lg-1">
                {{ Form::select('quinzena', $quinzenas, '', ['id' => 'quinzena', 'class' => 'form-control', 'maxlength' => '20']) }}
            </div>
            <div class="show-on-dias col-lg-3">
                {{ Form::text('data_dia', $data_dia, ['id' => 'data_dia', 'class' => 'form-control data_dia', 'placeholder' => 'Mês/Ano', 'maxlength' => '20']) }}
            </div>
            <div class="col-lg-3"> 
                {{ Form::select('unidade_negocio', $unidades_negocios, '', ['id' => 'unidade_negocio', 'class' => 'form-control input-label', 'placeholder' => 'Selecione Unidade Negócio', 'maxlength' => '250']) }}
            </div>
            <div class="col-lg-3"> 
                {!! Form::select('vendedor', $representantes,'', ['id' => 'vendedor', 'class' => 'form-control', 'placeholder' => 'Todos os Vendedores']) !!}
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>

@endsection
@section('content')
    <div id="canvas_graficos">
        <canvas id="grafico_equipe" style="display: inline !important;height: 250px;width: 49%;"></canvas>           
        <canvas id="grafico_donut_equipe" style="display: inline !important;height: 250px;width: 49%;"></canvas>
    </div>

    <div class="content-table">
        <div class="total_dias_uteis"><h6>DIAS ÚTEIS TOTAL: <br> DIAS ÚTEIS RESTANTE: </h6></div>
        <table class="table table-striped" id="table-filters">
            <thead>
                <tr>
                    <th>Equipe</th> 
                    <th class="tb_number">Meta do Período</th>
                    <th class="tb_number">Meta Diária</th>
                    <th class="tb_number">Valor Atingido</th>
                    <th class="tb_number">Atingimento %</th>
                    <th class="tb_number">Dif. Meta</th>
                    <th class="td_acao">Cliente</th>
                    <th class="td_acao">Produto</th>
                    <th class="td_acao">Devolução</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <td class="tb_number">Total:</td>
                    <td class="tb_number" id="total_metas"></td>
                    <td class="tb_number" id="total_metas_diario"></td>
                    <td class="tb_number" id="total_valor"></td>
                    <td class="tb_number" id="total_atingimento_metal_porcetagem"></td>
                    <td class="tb_number" id="total_diferenca_meta"></td>
                    <td class="td_acao" id="total_cliente"></td>
                    <td class="td_acao" id="total_produto"></td>
                    <td class="td_acao" id="total_devolucao"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    </form>
@endsection
@section('script-footer')
    $(document).ready(function(){
        Chart.plugins.register({
            beforeDraw: function (chart) {
                if (chart.config.options.elements.center) {
            //Get ctx from string
            var ctx = chart.ctx;
            
                    //Get options from the center object in options
            var centerConfig = chart.config.options.elements.center;
              var fontStyle = centerConfig.fontStyle || 'Arial';
                    var txt = centerConfig.text;
            var color = centerConfig.color || '#000';
            var sidePadding = centerConfig.sidePadding || 20;
            var sidePaddingCalculated = (sidePadding/100) * (chart.innerRadius * 2)
            //Start with a base font of 30px
            ctx.font = "30px " + fontStyle;
            
                    //Get the width of the string and also the width of the element minus 10 to give it 5px side padding
            var stringWidth = ctx.measureText(txt).width;
            var elementWidth = (chart.innerRadius * 2) - sidePaddingCalculated;
    
            // Find out how much the font can grow in width.
            var widthRatio = elementWidth / stringWidth;
            var newFontSize = Math.floor(30 * widthRatio);
            var elementHeight = (chart.innerRadius * 2);
    
            // Pick a new font size so it will not be larger than the height of label.
            var fontSizeToUse = Math.min(newFontSize, elementHeight);
    
                    //Set font settings to draw it correctly.
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            var centerX = ((chart.chartArea.left + chart.chartArea.right) / 2);
            var centerY = ((chart.chartArea.top + chart.chartArea.bottom) / 2);
            ctx.font = fontSizeToUse+"px " + fontStyle;
            ctx.fillStyle = color;
            
            //Draw text in center
            ctx.fillText(txt, centerX, centerY);
                }
            }
        });
    
        var ctx_donut_equipe = document.getElementById("grafico_donut_equipe").getContext("2d");
        var ctx = document.getElementById('grafico_equipe');
        var config;
        myBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels:[""],
                datasets: [
                    {
                        label: "Meta",
                        type: "bar",
                        data: [0],
                        backgroundColor: "green"
                    },{
                        label: "Atingido",
                        type: "bar",
                        backgroundColor: "blue",
                        data: [0],
                    },
                ]
            },
            options: {
                responsive: false,
				plugins: {
					datalabels: {
						display: false
					}
				}
            },
        });

        config = {
            type: 'doughnut',
            data: {
                labels: [
                  "Atingido",
                  "Meta",
                ],
                datasets: [{
                    data: [0, 0],
                    backgroundColor: [
                      "blue",
                      "green"
                    ],
                    hoverBackgroundColor: [
                      "#FF6384",
                      "#36A2EB"
                    ]
                }]
            },
            options: {
                elements: {
                    center: {
                        text: "",
                        color: 'blue', // Default is #000000
                        fontStyle: 'Arial', // Default is Arial
                        sidePadding: 20 // Defualt is 20 (as a percentage)
                    }
                },
                responsive: false,
                display: false,
				plugins: {
					datalabels: {
						display: false
					}
				}
            }
        };
        myChart_donut_equipe = new Chart(ctx_donut_equipe, config);
        
        form = $(document).find("#form_filter");

        form.find("#btn-filterform").off("click");
        form.find("#btn-filterform").on("click", function(){
            filterClear();
            filterAjax();
        });

        form.find('.data').datepicker({ 
            format: 'mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form.find('.data').mask('00/0000');
        form.find('.data_dia').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form.find('.data_dia').mask('00/00/0000');

        table_filters.destroy();
        table_filters = $('#table-filters').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "autoWidth": false,
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
                                if(column >= 1){
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
                {
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    "orderable": false
                },
                {
                    'targets': 'tb_number',
                    'class': 'tb_number',
                },
            ],
        });

        formatSemana(form.find("#mes_ano").val());
        liberacaoCampos();
        
        form.find("#tipo_data").off("change");
        form.find("#tipo_data").on("change", function(){
            liberacaoCampos();
        });

        form.find("#mes_ano").off("change");
        form.find("#mes_ano").on("change", function(){
            formatSemana(form.find("#mes_ano").val());
        });
    });

    function filterClear(){
        table_filters.clear().draw();
    }

    function filterAjax(){
        filterClear();
        form = $(document).find("#form_filter");
        data_form = form.serialize();
        filterClear();
        $.ajax({
            url: '{{ route('mapa_venda.filtro')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                linhas = [];
                colunas = [];
                metas = [];
                valor_atingido = [];
                
                for (var fields in data.response.unidades){
                    temp_array = [
                        data.response.unidades[fields].unidade_negocio,
                        data.response.unidades[fields].meta,
                        data.response.unidades[fields].meta_diaria,
                        createBtViewEquipeIndividual(data.response.unidades[fields], data.response.filtro, "Equipe: "+data.response.unidades[fields].unidade_negocio+" - "+data.response.data_escolhida),
                        data.response.unidades[fields].atingimento_metal_porcetagem,
                        data.response.unidades[fields].diferenca_meta,
                        createBtViewCliente(data.response.filtro, data.response.unidades[fields].id,"Resumo Cliente "+data.response.data_escolhida+" - Equipe: "+data.response.unidades[fields].unidade_negocio),
                        createBtViewProduto(data.response.filtro, data.response.unidades[fields].id,"Resumo Produto "+data.response.data_escolhida+" - Equipe: "+data.response.unidades[fields].unidade_negocio),
                        createBtViewDevolucao(data.response.filtro, data.response.unidades[fields].id,"Resumo Devolução "+data.response.data_escolhida+" - Equipe: "+data.response.unidades[fields].unidade_negocio),
                    ];
                    linhas.push(temp_array);
                    colunas.push( data.response.unidades[fields].unidade_negocio); 
                    metas.push(data.response.unidades[fields].meta_codigo);
                    valor_atingido.push(data.response.unidades[fields].valor_codigo);
                }

                ctx_donut_equipe = document.getElementById("grafico_donut_equipe").getContext("2d");
                ctx = document.getElementById('grafico_equipe');

                myBarChart.config.data = {
                    labels:colunas,
                    datasets: [
                        {
                            label: "Meta",
                            type: "bar",
                            data: metas,
                            backgroundColor: "green"
                        },{
                            label: "Atingido",
                            type: "bar",
                            backgroundColor: "blue",
                            data: valor_atingido,
                        },
                    ]
                };
                myBarChart.update();
                
                metas_porcetagem = 100 - data.response.atingimento_metal_porcetagem_codigo;

                if(metas_porcetagem < 0){
                    metas_porcetagem = 0;
                }

                data_donut_equipe = {
                    labels: [
                        "Atingido",
                        "Meta",
                    ],
                    datasets: [{
                        data: [data.response.atingimento_metal_porcetagem_codigo, metas_porcetagem],
                        backgroundColor: [
                            "blue",
                            "green"
                        ],
                        hoverBackgroundColor: [
                            "#FF6384",
                            "#36A2EB"
                        ]
                    }]
                };
                myChart_donut_equipe.config.data = data_donut_equipe;
                myChart_donut_equipe.config.options.elements.center.text = data.response.atingimento_metal_porcetagem;
                myChart_donut_equipe.update();
                table_filters.rows.add(linhas).draw();            
    
                $(document).find('#total_metas').html(data.response.total_meta);
                $(document).find('#total_metas_diario').html(data.response.total_meta_diaria);
                $(document).find('#total_valor').html(data.response.total_valor);
                $(document).find('#total_atingimento_metal_porcetagem').html(data.response.atingimento_metal_porcetagem);
                $(document).find('#total_diferenca_meta').html(data.response.diferenca_meta);
                $(document).find('#total_cliente').html(createBtViewCliente(data.response.filtro, "", "Resumo Cliente "+data.response.data_escolhida));
                $(document).find('#total_produto').html(createBtViewProduto(data.response.filtro, "", "Resumo Prooduto "+data.response.data_escolhida));
                $(document).find('#total_devolucao').html(createBtViewDevolucao(data.response.filtro, "", "Resumo Devolução "+data.response.data_escolhida));
                $(document).find("#verificacao_atualizacao").html(data.response.horario);

                if(form.find("#mes_ano").val() === "{{ date('m').'/'.date('Y')}}"){
                    $(document).find(".total_dias_uteis").html("<h6>DIAS ÚTEIS TOTAL: "+data.response.divisao_meta+"<br> DIAS ÚTEIS RESTANTE: "+data.response.dias_uteis+"</h6>");
                }else{
                    $(document).find(".total_dias_uteis").html("<h6>DIAS ÚTEIS TOTAL: "+data.response.divisao_meta+"<br> DIAS ÚTEIS DO PERÍODO: "+data.response.dias_uteis+"</h6>");
                }
            },
            error: function(data){
                $(document).find("#verificacao_atualizacao").html(data.responseJSON.error.horario);
            }
        });
    }

    function createBtViewCliente($value, $unidade_negocio, $title){
        html = "<a href=\"#\" class=\"bt-view\" data-toggle='tooltip' data-html='true' data-id_unidade_negocio=\""+$unidade_negocio+"\" data-filtro=\""+$value+"\" title='Cliente' data-title=\""+$title+"\" onclick=\"abrirModalCliente($(this))\"></a>";
    
        return html;
    }

    function createBtViewProduto($value, $unidade_negocio, $title){
        html = "<a href=\"#\" class=\"bt-view\" data-toggle='tooltip' data-html='true' data-id_unidade_negocio=\""+$unidade_negocio+"\" data-filtro=\""+$value+"\" title='Produto' data-title=\""+$title+"\" onclick=\"abrirModalGrupo($(this))\"></a>";
    
        return html;
    }

    function createBtViewDevolucao($value, $unidade_negocio, $title){
        html = "<a href=\"#\" class=\"bt-view\" data-toggle='tooltip' data-html='true' data-id_unidade_negocio=\""+$unidade_negocio+"\" data-filtro=\""+$value+"\" title='Visualizar' data-title=\""+$title+"\" onclick=\"abrirModalDevolucao($(this))\"></a>";
    
        return html;
    }

    function createBtViewEquipe($value, $filtro){
        html = "<a href=\"#\" data-toggle='tooltip' data-html='true' data-filtro=\""+$filtro+"\" title='Visualizar do Gráfico' onclick=\"abrirModalEquipe($(this))\">"+$value+"</a>";
    
        return html;
    }

    function createBtViewEquipeIndividual($value, $filtro, $title){
        html = "<a href=\"#\" data-toggle='tooltip' data-html='true' data-filtro=\""+$filtro+"\" data-id_unidade_negocio=\""+$value.id+"\" title='Visualizar do Gráfico' data-title=\""+$title+"\" onclick=\"abrirModalEquipeIndividual($(this))\">"+$value.valor+"</a>";
    
        return html;
    }

    function abrirModalEquipe($this){
        var filtro = $($this).data("filtro");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('mapa_venda.modal.equipe') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
            },
            success: function(body){
                createModal('modal_equipe', title, body, "modal-lg");
                var modal = $("#modal_equipe");
            }
        });
    }

    function abrirModalEquipeIndividual($this){
        var filtro = $($this).data("filtro");
        var id_unidade_negocio = $($this).data("id_unidade_negocio");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('mapa_venda.modal.equipe_individual') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
                id_unidade_negocio: id_unidade_negocio,
                title: title,
            },
            success: function(body){
                createModal('modal_equipe', title, body, "modal-lg");
                var modal = $("#modal_equipe");
            }
        });
    }

    function abrirModalCliente($this){
        var filtro = $($this).data("filtro");
        var id_unidade_negocio = $($this).data("id_unidade_negocio");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('mapa_venda.modal.cliente') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
                id_unidade_negocio: id_unidade_negocio,
                title: title,
            },
            success: function(body){
                createModal('modal_cliente', title, body, "modal-lg");
                var modal = $("#modal_cliente");
            }
        });
    }

    function abrirModalGrupo($this){
        var filtro = $($this).data("filtro");
        var id_unidade_negocio = $($this).data("id_unidade_negocio");
        var codigo_cliente = "";
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('mapa_venda.modal.grupo') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
                id_unidade_negocio: id_unidade_negocio,
                codigo_cliente: codigo_cliente,
                title: title
            },
            success: function(body){
                createModal('modal_grupo', title, body, "modal-lg");
                var modal = $("#modal_grupo");
            }
        });
    }

    function abrirModalDevolucao($this){
        var filtro = $($this).data("filtro");
        var id_unidade_negocio = $($this).data("id_unidade_negocio");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('mapa_venda.modal.devolucao') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
                id_unidade_negocio: id_unidade_negocio
            },
            success: function(body){
                createModal('modal_devolucao', title, body, "modal-lg");
                var modal = $("#modal_devolucao");
            }
        });
    }

    function randomColor() {
        var letters = '0123456789ABCDEF';
        var color = '#';
        for (var i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }

    function formatSemana(data) {
        if(data == ''){
            var d = new Date();
        }else{
            data = data.split('/');

            mes = data[0];
            ano = data[1]; 

            var d = new Date(ano, mes - 1, 1);
        }
        
        var anoC = d.getFullYear();
        var mesC = d.getMonth();
        
        var dia_inicial = new Date (anoC, mesC, 1);
        var dia_final = new Date (anoC, mesC+1, 0);

        if(dia_final.getDate() == 30 && dia_inicial.getDay() == 6){
            qtd_semanas = 6;
        }else if(dia_final.getDate() == 28 && dia_inicial.getDay() == 0){
            qtd_semanas = 4;
        }else if(dia_final.getDate() == 31 && (dia_inicial.getDay() == 5 || dia_inicial.getDay() == 6)){
            qtd_semanas = 6;
        }else{
            qtd_semanas = 5;
        }
        
        semanas = form.find('#semanas');
        semanas.html("");

        for(var i = 1; i <= qtd_semanas; i++){
            semanas.append("<option value=\""+i+"\">"+i+"ª Semana</option>");
        }
    }

    function liberacaoCampos(){
        if(form.find("#tipo_data").val() == "semanal"){
            form.find('.show-on-semanas').show();
            form.find('.show-on-quinzenas').hide();
            form.find('.show-on-dias').hide();
            form.find('.show-on-mensal').show();
        }else if(form.find("#tipo_data").val() == "quinzenal"){
            form.find('.show-on-semanas').hide();
            form.find('.show-on-quinzenas').show();
            form.find('.show-on-dias').hide();
            form.find('.show-on-mensal').show();
        }else if(form.find("#tipo_data").val() == "diario"){
            form.find('.show-on-semanas').hide();
            form.find('.show-on-quinzenas').hide();
            form.find('.show-on-dias').show();
            form.find('.show-on-mensal').hide();
        }else{
            form.find('.show-on-semanas').hide();
            form.find('.show-on-quinzenas').hide();
            form.find('.show-on-dias').hide();
            form.find('.show-on-mensal').show();
        }
    }
    
@endsection
