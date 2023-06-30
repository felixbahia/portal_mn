@extends('layouts.app')

@section('content-filter')
    <label id="verificacao_atualizacao" name="verificacao_atualizacao">{!! $horario !!}</label></br>
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="row">
                <div class="col-lg-2">
                    {!! Form::text('ano', date('Y'), ['id' => 'ano', 'class' => 'form-control data', 'placeholder' => 'Ano']) !!}
                </div>
                <div class="col-lg-3">
                    {!! Form::select('modo', $modos, 'competencia', ['id' => 'modo', 'class' => 'form-control']) !!}
                </div>
                <div class="col-lg-3">
                    {!! Form::select('tipo_produto', $tipo_produto, '', ['id' => 'tipo_produto', 'class' => 'form-control', 'placeholder' => 'Todos']) !!}
                </div>
                <div class="col-lg-2">
                    {!! Form::text('margem_venda', '', ['id' => 'margem_venda', 'class' => 'form-control valor text-right', 'placeholder' => 'Margem de Venda(Padrão 32,00%)', 'maxlength' => '5']) !!}
                </div>

                <div class="col-lg-1">
                    <div class="form-check">
                        {!! Form::checkbox('prepago', 'true', true, ['id' => 'prepago', 'class' => 'form-check-input']) !!}
                        {!! Form::label('prepago', 'Pré-pago', ['class' => 'form-check-label']) !!}
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
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-filters-acompanhamento_estoque">
            <thead>
                <tr>
                    <th></th> 
                    <th class="tb_number">Jan</th>
                    <th class="tb_number">Fev</th>
                    <th class="tb_number">Mar</th>
                    <th class="tb_number">Abr</th>
                    <th class="tb_number">Mai</th>
                    <th class="tb_number">Jun</th>
                    <th class="tb_number">Jul</th>
                    <th class="tb_number">Ago</th>
                    <th class="tb_number">Set</th>
                    <th class="tb_number">Out</th>
                    <th class="tb_number">Nov</th>
                    <th class="tb_number">Dez</th>
                    <th class="tb_number" id="total_parcial" name="total_parcial">Tot. até<br>{{$mes_anterior}}</th>
                    <th class="tb_number">Total</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
@endsection

@section('script-footer')
    $(document).ready( function () {
        form = $(document).find("#form_filter");
        form.find('.data').mask('0000');
        form.find('.data').datepicker({
            language: 'pt-BR',
            format: 'yyyy',
            zIndex: 2000,
            autoHide: true
        });

        form.find(".valor").maskMoney({thousands:'.', decimal:','});

        esconderPopoverTooltip();

        $('[data-toggle="popover"]').off('show.bs.popover');
        $('[data-toggle="popover"]').popover('hide');

        $('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            trigger: 'hover',
            placement: 'right',
            template: '<div class="popover popover-estoque" role="popover"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });

        $(document).find("#btn-filterform").on("click", function(){
            filterClear();
            filtro();
        });

        table_filters_acompanhamento_orcamentario= $(document).find('#table-filters-acompanhamento_estoque').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "orderMulti": false,
            "ordering": false,
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
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
                "decimal":        ",",
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
            'createdRow': function(row, data, dataIndex){
                if($(data[0].replace(' %', '')).text() === 'FATURAMENTO' || $(data[0].replace(' %', '')).text() === 'Compras' || $(data[0].replace(' %', '')).text() === 'Compras sobre Faturamento' || $(data[0].replace(' %', '')).text() === 'Despesas' || $(data[0].replace(' %', '')).text() === 'Faturamento - Compras - Despesas - Bancos' || $(data[0].replace(' %', '')).text() === 'Estoque' || $(data[0].replace(' %', '')).text() === 'Bancos'){
                    $('td:eq(0)', row).attr('colspan', 15);
                    $('td:eq(0)', row).addClass('tb_date');

                    if($(data[0].replace(' %', '')).text() === 'FATURAMENTO'){
                        $(row).addClass('table-primary');
                    }else if($(data[0].replace(' %', '')).text() === 'Compras'){
                        $(row).addClass('table-secondary');
                    }else if($(data[0].replace(' %', '')).text() === 'Compras sobre Faturamento'){
                        $(row).addClass('table-success');
                    }else if($(data[0].replace(' %', '')).text() === 'Despesas'){
                        $(row).addClass('table-danger');
                    }else if($(data[0].replace(' %', '')).text() === 'Faturamento - Compras - Despesas - Bancos'){
                        $(row).addClass('table-warning');
                    }else if($(data[0].replace(' %', '')).text() === 'Estoque'){
                        $(row).addClass('table-info');
                    }else if($(data[0].replace(' %', '')).text() === 'Bancos'){
                        $(row).addClass('table-laranja');
                    }
                    
        
                    $('td:eq(1)', row).css('display', 'none');
                    $('td:eq(2)', row).css('display', 'none');
                    $('td:eq(3)', row).css('display', 'none');
                    $('td:eq(4)', row).css('display', 'none');
                    $('td:eq(5)', row).css('display', 'none');
                    $('td:eq(6)', row).css('display', 'none');
                    $('td:eq(7)', row).css('display', 'none');
                    $('td:eq(8)', row).css('display', 'none');
                    $('td:eq(9)', row).css('display', 'none');
                    $('td:eq(10)', row).css('display', 'none');
                    $('td:eq(11)', row).css('display', 'none');
                    $('td:eq(12)', row).css('display', 'none');
                    $('td:eq(13)', row).css('display', 'none');
                    $('td:eq(14)', row).css('display', 'none');
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "tb_date" }
            ],
        });
    });

    function filtro(){
        form = $(document).find("#form_filter");
        
        var ano_atual = "{{date('Y')}}";
        var ano_filtro = form.find("#ano").val();
        if(ano_atual != ano_filtro){
            $('#total_parcial').html("Tot. até<br>12/"+ano_filtro);
        }else{
            $('#total_parcial').html("Tot. até<br>"+"{{$mes_anterior}}");
        }

        data_form = form.serialize();
        $('label.error-message').remove();
        $.ajax({
            url: '{{ route('acompanhamento_orcamentario.filtro')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                var margem_venda = form.find("#margem_venda").val();

                if(margem_venda == "" || $.isEmptyObject(margem_venda)){
                    margem_venda = "32,00";
                }

                produtos = [];

                if (form.find("#tipo_produto").val() === 'uso_consumo'){
                    temp_array = [
                        '<b>Compras</b>',
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                    ];
                    produtos.push(temp_array);

                    if (form.find("#modo").val() === 'fluxo_caixa'){
                        temp_array = [
                            data.response.orcamento_compras.descricao,
                            data.response.orcamento_compras.inteiro_1, 
                            data.response.orcamento_compras.inteiro_2, 
                            data.response.orcamento_compras.inteiro_3,
                            data.response.orcamento_compras.inteiro_4,
                            data.response.orcamento_compras.inteiro_5,
                            data.response.orcamento_compras.inteiro_6,
                            data.response.orcamento_compras.inteiro_7,
                            data.response.orcamento_compras.inteiro_8,
                            data.response.orcamento_compras.inteiro_9,
                            data.response.orcamento_compras.inteiro_10,
                            data.response.orcamento_compras.inteiro_11,
                            data.response.orcamento_compras.inteiro_12,
                            data.response.orcamento_compras.inteiro_total_parcial,
                            data.response.orcamento_compras.inteiro_total,
                        ];
                        produtos.push(temp_array);

                        temp_array = [
                            data.response.titulos_caixa_aberto.descricao,
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_1, data.response.ano_codigo, "01", "Janeiro",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_2, data.response.ano_codigo, "02", "Fevereiro",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_3, data.response.ano_codigo, "03", "Março",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_4, data.response.ano_codigo, "04", "Abril",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_5, data.response.ano_codigo, "05", "Maio",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_6, data.response.ano_codigo, "06", "Junho",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_7, data.response.ano_codigo, "07", "Julho",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_8, data.response.ano_codigo, "08", "Agosto",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_9, data.response.ano_codigo, "09", "Setembro",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_10, data.response.ano_codigo, "10", "Outubro",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_11, data.response.ano_codigo, "11", "Novembro",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_12, data.response.ano_codigo, "12", "Dezembro",data.response.estabelecimentos, false, "aberto"),
                            data.response.titulos_caixa_aberto.inteiro_total_parcial,
                            data.response.titulos_caixa_aberto.inteiro_total,
                        ];
                        produtos.push(temp_array);

                        temp_array = [
                            data.response.titulos_caixa_fechado.descricao,
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_1, data.response.ano_codigo, "01", "Janeiro",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_2, data.response.ano_codigo, "02", "Fevereiro",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_3, data.response.ano_codigo, "03", "Março",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_4, data.response.ano_codigo, "04", "Abril",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_5, data.response.ano_codigo, "05", "Maio",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_6, data.response.ano_codigo, "06", "Junho",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_7, data.response.ano_codigo, "07", "Julho",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_8, data.response.ano_codigo, "08", "Agosto",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_9, data.response.ano_codigo, "09", "Setembro",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_10, data.response.ano_codigo, "10", "Outubro",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_11, data.response.ano_codigo, "11", "Novembro",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_12, data.response.ano_codigo, "12", "Dezembro",data.response.estabelecimentos, false, "fechado"),
                            data.response.titulos_caixa_fechado.inteiro_total_parcial,
                            data.response.titulos_caixa_fechado.inteiro_total,
                        ];
                        produtos.push(temp_array);
                
                        temp_array = [
                            data.response.titulos_caixa_total.descricao,
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_1, data.response.ano_codigo, "01", "Janeiro",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_2, data.response.ano_codigo, "02", "Fevereiro",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_3, data.response.ano_codigo, "03", "Março",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_4, data.response.ano_codigo, "04", "Abril",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_5, data.response.ano_codigo, "05", "Maio",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_6, data.response.ano_codigo, "06", "Junho",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_7, data.response.ano_codigo, "07", "Julho",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_8, data.response.ano_codigo, "08", "Agosto",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_9, data.response.ano_codigo, "09", "Setembro",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_10, data.response.ano_codigo, "10", "Outubro",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_11, data.response.ano_codigo, "11", "Novembro",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_12, data.response.ano_codigo, "12", "Dezembro",data.response.estabelecimentos, false, "total"),
                            data.response.titulos_caixa_total.inteiro_total_parcial,
                            data.response.titulos_caixa_total.inteiro_total,
                        ];
                        produtos.push(temp_array);
                    }else{
                        temp_array = [
                            data.response.orcamento_compras.descricao,
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_1, data.response.ano_codigo, "01", "Janeiro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_2, data.response.ano_codigo, "02", "Fevereiro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_3, data.response.ano_codigo, "03", "Março", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_4, data.response.ano_codigo, "04", "Abril", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_5, data.response.ano_codigo, "05", "Maio", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_6, data.response.ano_codigo, "06", "Junho", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_7, data.response.ano_codigo, "07", "Julho", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_8, data.response.ano_codigo, "08", "Agosto", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_9, data.response.ano_codigo, "09", "Setembro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_10, data.response.ano_codigo, "10", "Outubro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_11, data.response.ano_codigo, "11", "Novembro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_12, data.response.ano_codigo, "12", "Dezembro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_total_parcial, data.response.ano_codigo, "parcial", "", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_total, data.response.ano_codigo, "", "", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                        ];
                        produtos.push(temp_array);
                    
                        temp_array = [
                            data.response.compras_planejadas.descricao,
                            createBtViewCompras(data.response.compras_planejadas.inteiro_1, data.response.ano_codigo, "01", "Janeiro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_2, data.response.ano_codigo, "02", "Fevereiro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_3, data.response.ano_codigo, "03", "Março", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_4, data.response.ano_codigo, "04", "Abril", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_5, data.response.ano_codigo, "05", "Maio", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_6, data.response.ano_codigo, "06", "Junho", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_7, data.response.ano_codigo, "07", "Julho", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_8, data.response.ano_codigo, "08", "Agosto", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_9, data.response.ano_codigo, "09", "Setembro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_10, data.response.ano_codigo, "10", "Outubro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_11, data.response.ano_codigo, "11", "Novembro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_12, data.response.ano_codigo, "12", "Dezembro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_total_parcial, data.response.ano_codigo, "parcial", "", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_total, data.response.ano_codigo, "", "", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                        ];
                        produtos.push(temp_array);
                
                        temp_array = [
                            data.response.compras_realizadas.descricao,
                            createBtViewCompras(data.response.compras_realizadas.inteiro_1, data.response.ano_codigo, "01", "Janeiro", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_2, data.response.ano_codigo, "02", "Fevereiro", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_3, data.response.ano_codigo, "03", "Março", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_4, data.response.ano_codigo, "04", "Abril", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_5, data.response.ano_codigo, "05", "Maio", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_6, data.response.ano_codigo, "06", "Junho", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_7, data.response.ano_codigo, "07", "Julho", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_8, data.response.ano_codigo, "08", "Agosto", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_9, data.response.ano_codigo, "09", "Setembro", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_10, data.response.ano_codigo, "10", "Outubro", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_11, data.response.ano_codigo, "11", "Novembro", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_12, data.response.ano_codigo, "12", "Dezembro", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_total_parcial, data.response.ano_codigo, "parcial", "", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_total, data.response.ano_codigo, "", "", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                        ];
                        produtos.push(temp_array);
                    }

                    temp_array = [
                        data.response.compras_diferenca.descricao,
                        data.response.compras_diferenca.inteiro_1,
                        data.response.compras_diferenca.inteiro_2,
                        data.response.compras_diferenca.inteiro_3,
                        data.response.compras_diferenca.inteiro_4,
                        data.response.compras_diferenca.inteiro_5,
                        data.response.compras_diferenca.inteiro_6,
                        data.response.compras_diferenca.inteiro_7,
                        data.response.compras_diferenca.inteiro_8,
                        data.response.compras_diferenca.inteiro_9,
                        data.response.compras_diferenca.inteiro_10,
                        data.response.compras_diferenca.inteiro_11,
                        data.response.compras_diferenca.inteiro_12,
                        data.response.compras_diferenca.inteiro_total_parcial,
                        data.response.compras_diferenca.inteiro_total,  
                    ];
                    produtos.push(temp_array);
            
                    temp_array = [
                        '<b>Estoque</b>',
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                    ];
                    produtos.push(temp_array);
                    
                    temp_array = [
                        data.response.estoque_entrada.descricao,
                        data.response.estoque_entrada.inteiro_1,
                        data.response.estoque_entrada.inteiro_2,
                        data.response.estoque_entrada.inteiro_3,
                        data.response.estoque_entrada.inteiro_4,
                        data.response.estoque_entrada.inteiro_5,
                        data.response.estoque_entrada.inteiro_6,
                        data.response.estoque_entrada.inteiro_7,
                        data.response.estoque_entrada.inteiro_8,
                        data.response.estoque_entrada.inteiro_9,
                        data.response.estoque_entrada.inteiro_10,
                        data.response.estoque_entrada.inteiro_11,
                        data.response.estoque_entrada.inteiro_12,
                        data.response.estoque_entrada.inteiro_total_parcial,
                        data.response.estoque_entrada.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        data.response.estoque_saida.descricao,
                        data.response.estoque_saida.inteiro_1,
                        data.response.estoque_saida.inteiro_2,
                        data.response.estoque_saida.inteiro_3,
                        data.response.estoque_saida.inteiro_4,
                        data.response.estoque_saida.inteiro_5,
                        data.response.estoque_saida.inteiro_6,
                        data.response.estoque_saida.inteiro_7,
                        data.response.estoque_saida.inteiro_8,
                        data.response.estoque_saida.inteiro_9,
                        data.response.estoque_saida.inteiro_10,
                        data.response.estoque_saida.inteiro_11,
                        data.response.estoque_saida.inteiro_12,
                        data.response.estoque_saida.inteiro_total_parcial,
                        data.response.estoque_saida.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        data.response.estoque_final.descricao,
                        data.response.estoque_final.inteiro_1,
                        data.response.estoque_final.inteiro_2,
                        data.response.estoque_final.inteiro_3,
                        data.response.estoque_final.inteiro_4,
                        data.response.estoque_final.inteiro_5,
                        data.response.estoque_final.inteiro_6,
                        data.response.estoque_final.inteiro_7,
                        data.response.estoque_final.inteiro_8,
                        data.response.estoque_final.inteiro_9,
                        data.response.estoque_final.inteiro_10,
                        data.response.estoque_final.inteiro_11,
                        data.response.estoque_final.inteiro_12,
                        data.response.estoque_final.inteiro_total_parcial,
                        data.response.estoque_final.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        '<div>'+data.response.estoque_previsao.descricao+'<a href="#" class="btn-informacao-sem-width" data-toggle="tooltip" data-placement="top" title="" data-original-title="Cálculo: Estoque Final do Mês Anterior + Compras Previsto - (Faturamento Previsto - '+margem_venda+'%)"></a></div>',
                        data.response.estoque_previsao.inteiro_1,
                        data.response.estoque_previsao.inteiro_2,
                        data.response.estoque_previsao.inteiro_3,
                        data.response.estoque_previsao.inteiro_4,
                        data.response.estoque_previsao.inteiro_5,
                        data.response.estoque_previsao.inteiro_6,
                        data.response.estoque_previsao.inteiro_7,
                        data.response.estoque_previsao.inteiro_8,
                        data.response.estoque_previsao.inteiro_9,
                        data.response.estoque_previsao.inteiro_10,
                        data.response.estoque_previsao.inteiro_11,
                        data.response.estoque_previsao.inteiro_12,
                        data.response.estoque_previsao.inteiro_total_parcial,
                        data.response.estoque_previsao.inteiro_total,
                    ];
                    produtos.push(temp_array);

                }else{
                    temp_array = [
                        '<b>FATURAMENTO</b>',
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                    ];
                    produtos.push(temp_array);

                    if (form.find("#modo").val() === 'fluxo_caixa'){
                    
                        temp_array = [
                            data.response.faturamento_previsto.descricao,
                            createBtViewFaturamentoPrevistoFluxoCaixo(data.response.faturamento_previsto.inteiro_1, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_1, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_1, data.response.faturamento_previsto.flag_1),
                            createBtViewFaturamentoPrevistoFluxoCaixo(data.response.faturamento_previsto.inteiro_2, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_2, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_2, data.response.faturamento_previsto.flag_2),
                            createBtViewFaturamentoPrevistoFluxoCaixo(data.response.faturamento_previsto.inteiro_3, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_3, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_3, data.response.faturamento_previsto.flag_3),
                            createBtViewFaturamentoPrevistoFluxoCaixo(data.response.faturamento_previsto.inteiro_4, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_4, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_4, data.response.faturamento_previsto.flag_4),
                            createBtViewFaturamentoPrevistoFluxoCaixo(data.response.faturamento_previsto.inteiro_5, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_5, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_5, data.response.faturamento_previsto.flag_5),
                            createBtViewFaturamentoPrevistoFluxoCaixo(data.response.faturamento_previsto.inteiro_6, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_6, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_6, data.response.faturamento_previsto.flag_6),
                            createBtViewFaturamentoPrevistoFluxoCaixo(data.response.faturamento_previsto.inteiro_7, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_7, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_7, data.response.faturamento_previsto.flag_7),
                            createBtViewFaturamentoPrevistoFluxoCaixo(data.response.faturamento_previsto.inteiro_8, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_8, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_8, data.response.faturamento_previsto.flag_8),
                            createBtViewFaturamentoPrevistoFluxoCaixo(data.response.faturamento_previsto.inteiro_9, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_9, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_9, data.response.faturamento_previsto.flag_9),
                            createBtViewFaturamentoPrevistoFluxoCaixo(data.response.faturamento_previsto.inteiro_10, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_10, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_10, data.response.faturamento_previsto.flag_10),
                            createBtViewFaturamentoPrevistoFluxoCaixo(data.response.faturamento_previsto.inteiro_11, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_11, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_11, data.response.faturamento_previsto.flag_11),
                            createBtViewFaturamentoPrevistoFluxoCaixo(data.response.faturamento_previsto.inteiro_12, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_12, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_12, data.response.faturamento_previsto.flag_12),
                            data.response.faturamento_previsto.inteiro_total_parcial,
                            data.response.faturamento_previsto.inteiro_total,
                        ];
                        produtos.push(temp_array);

                        temp_array = [
                            data.response.faturamento_caixa_aberto.descricao,
                            data.response.faturamento_caixa_aberto.inteiro_1,
                            data.response.faturamento_caixa_aberto.inteiro_2,
                            data.response.faturamento_caixa_aberto.inteiro_3,
                            data.response.faturamento_caixa_aberto.inteiro_4,
                            data.response.faturamento_caixa_aberto.inteiro_5,
                            data.response.faturamento_caixa_aberto.inteiro_6,
                            data.response.faturamento_caixa_aberto.inteiro_7,
                            data.response.faturamento_caixa_aberto.inteiro_8,
                            data.response.faturamento_caixa_aberto.inteiro_9,
                            data.response.faturamento_caixa_aberto.inteiro_10,
                            data.response.faturamento_caixa_aberto.inteiro_11,
                            data.response.faturamento_caixa_aberto.inteiro_12,
                            data.response.faturamento_caixa_aberto.inteiro_total_parcial,
                            data.response.faturamento_caixa_aberto.inteiro_total,
                        ];
                        produtos.push(temp_array);

                        temp_array = [
                            data.response.faturamento_caixa_fechado.descricao,
                            data.response.faturamento_caixa_fechado.inteiro_1,
                            data.response.faturamento_caixa_fechado.inteiro_2,
                            data.response.faturamento_caixa_fechado.inteiro_3,
                            data.response.faturamento_caixa_fechado.inteiro_4,
                            data.response.faturamento_caixa_fechado.inteiro_5,
                            data.response.faturamento_caixa_fechado.inteiro_6,
                            data.response.faturamento_caixa_fechado.inteiro_7,
                            data.response.faturamento_caixa_fechado.inteiro_8,
                            data.response.faturamento_caixa_fechado.inteiro_9,
                            data.response.faturamento_caixa_fechado.inteiro_10,
                            data.response.faturamento_caixa_fechado.inteiro_11,
                            data.response.faturamento_caixa_fechado.inteiro_12,
                            data.response.faturamento_caixa_fechado.inteiro_total_parcial,
                            data.response.faturamento_caixa_fechado.inteiro_total,
                        ];
                        produtos.push(temp_array);

                        temp_array = [
                            data.response.faturamento_caixa.descricao,
                            createBtViewFaturamentoPrevistoFluxoCaixoComparativo(data.response.faturamento_caixa.inteiro_1, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_1, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_1),
                            createBtViewFaturamentoPrevistoFluxoCaixoComparativo(data.response.faturamento_caixa.inteiro_2, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_2, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_2),
                            createBtViewFaturamentoPrevistoFluxoCaixoComparativo(data.response.faturamento_caixa.inteiro_3, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_3, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_3),
                            createBtViewFaturamentoPrevistoFluxoCaixoComparativo(data.response.faturamento_caixa.inteiro_4, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_4, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_4),
                            createBtViewFaturamentoPrevistoFluxoCaixoComparativo(data.response.faturamento_caixa.inteiro_5, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_5, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_5),
                            createBtViewFaturamentoPrevistoFluxoCaixoComparativo(data.response.faturamento_caixa.inteiro_6, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_6, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_6),
                            createBtViewFaturamentoPrevistoFluxoCaixoComparativo(data.response.faturamento_caixa.inteiro_7, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_7, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_7),
                            createBtViewFaturamentoPrevistoFluxoCaixoComparativo(data.response.faturamento_caixa.inteiro_8, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_8, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_8),
                            createBtViewFaturamentoPrevistoFluxoCaixoComparativo(data.response.faturamento_caixa.inteiro_9, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_9, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_9),
                            createBtViewFaturamentoPrevistoFluxoCaixoComparativo(data.response.faturamento_caixa.inteiro_10, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_10, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_10),
                            createBtViewFaturamentoPrevistoFluxoCaixoComparativo(data.response.faturamento_caixa.inteiro_11, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_11, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_11),
                            createBtViewFaturamentoPrevistoFluxoCaixoComparativo(data.response.faturamento_caixa.inteiro_12, data.response.dados_faturamento_previsto_fluxo_caixa.inteiro_12, data.response.dados_faturamento_previsto_fluxo_caixa_titulo.inteiro_12),
                            data.response.faturamento_caixa.inteiro_total_parcial,
                            data.response.faturamento_caixa.inteiro_total,
                        ];
                        produtos.push(temp_array);
                    }else{
                        temp_array = [
                            data.response.faturamento_previsto.descricao,
                            data.response.faturamento_previsto.inteiro_1,
                            data.response.faturamento_previsto.inteiro_2,
                            data.response.faturamento_previsto.inteiro_3,
                            data.response.faturamento_previsto.inteiro_4,
                            data.response.faturamento_previsto.inteiro_5,
                            data.response.faturamento_previsto.inteiro_6,
                            data.response.faturamento_previsto.inteiro_7,
                            data.response.faturamento_previsto.inteiro_8,
                            data.response.faturamento_previsto.inteiro_9,
                            data.response.faturamento_previsto.inteiro_10,
                            data.response.faturamento_previsto.inteiro_11,
                            data.response.faturamento_previsto.inteiro_12,
                            data.response.faturamento_previsto.inteiro_total_parcial,
                            data.response.faturamento_previsto.inteiro_total,
                        ];
                        produtos.push(temp_array);

                        temp_array = [
                            data.response.faturamento_realizado.descricao,
                            data.response.faturamento_realizado.inteiro_1,
                            data.response.faturamento_realizado.inteiro_2,
                            data.response.faturamento_realizado.inteiro_3,
                            data.response.faturamento_realizado.inteiro_4,
                            data.response.faturamento_realizado.inteiro_5,
                            data.response.faturamento_realizado.inteiro_6,
                            data.response.faturamento_realizado.inteiro_7,
                            data.response.faturamento_realizado.inteiro_8,
                            data.response.faturamento_realizado.inteiro_9,
                            data.response.faturamento_realizado.inteiro_10,
                            data.response.faturamento_realizado.inteiro_11,
                            data.response.faturamento_realizado.inteiro_12,
                            data.response.faturamento_realizado.inteiro_total_parcial,
                            data.response.faturamento_realizado.inteiro_total,
                        ];
                        produtos.push(temp_array);
                    }

                    temp_array = [
                        data.response.faturamento_diferenca.descricao,
                        data.response.faturamento_diferenca.inteiro_1,
                        data.response.faturamento_diferenca.inteiro_2,
                        data.response.faturamento_diferenca.inteiro_3,
                        data.response.faturamento_diferenca.inteiro_4,
                        data.response.faturamento_diferenca.inteiro_5,
                        data.response.faturamento_diferenca.inteiro_6,
                        data.response.faturamento_diferenca.inteiro_7,
                        data.response.faturamento_diferenca.inteiro_8,
                        data.response.faturamento_diferenca.inteiro_9,
                        data.response.faturamento_diferenca.inteiro_10,
                        data.response.faturamento_diferenca.inteiro_11,
                        data.response.faturamento_diferenca.inteiro_12,
                        data.response.faturamento_diferenca.inteiro_total_parcial,
                        data.response.faturamento_diferenca.inteiro_total,   
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        '<b>Compras</b>',
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                    ];
                    produtos.push(temp_array);

                    if (form.find("#modo").val() === 'fluxo_caixa'){
                        temp_array = [
                            data.response.orcamento_compras.descricao,
                            data.response.orcamento_compras.inteiro_1, 
                            data.response.orcamento_compras.inteiro_2, 
                            data.response.orcamento_compras.inteiro_3,
                            data.response.orcamento_compras.inteiro_4,
                            data.response.orcamento_compras.inteiro_5,
                            data.response.orcamento_compras.inteiro_6,
                            data.response.orcamento_compras.inteiro_7,
                            data.response.orcamento_compras.inteiro_8,
                            data.response.orcamento_compras.inteiro_9,
                            data.response.orcamento_compras.inteiro_10,
                            data.response.orcamento_compras.inteiro_11,
                            data.response.orcamento_compras.inteiro_12,
                            data.response.orcamento_compras.inteiro_total_parcial,
                            data.response.orcamento_compras.inteiro_total,
                        ];
                        produtos.push(temp_array);

                        console.log(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_total);
                        temp_array = [
                            data.response.pedidos_compras_abertos_titulos_futuros.descricao,
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_1, "01","Janeiro",data.response.compras_caixa_titulos_nao_lancados.filtro_ano), 
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_2, "02", "Fevereiro",data.response.compras_caixa_titulos_nao_lancados.filtro_ano), 
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_3, "03", "Março",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_4, "04", "Abril",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_5, "05", "Maio",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_6, "06", "Junho",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_7, "07", "Julho",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_8, "08", "Agosto",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_9, "09", "Setembro",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_10, "10", "Outubro",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_11, "11", "Novembro",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_12, "12", "Dezembro",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_total_parcial, "parcial", "",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewPedidoComprasAbertoFuturo(data.response.pedidos_compras_abertos_titulos_futuros.inteiro_total, "", "",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                        ];
                        produtos.push(temp_array);

                        temp_array = [
                            data.response.compras_caixa_titulos_nao_lancados.descricao,
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_1, "01","Janeiro",data.response.compras_caixa_titulos_nao_lancados.filtro_ano), 
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_2, "02", "Fevereiro",data.response.compras_caixa_titulos_nao_lancados.filtro_ano), 
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_3, "03", "Março",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_4, "04", "Abril",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_5, "05", "Maio",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_6, "06", "Junho",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_7, "07", "Julho",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_8, "08", "Agosto",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_9, "09", "Setembro",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_10, "10", "Outubro",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_11, "11", "Novembro",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_12, "12", "Dezembro",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_total_parcial, "6_meses", "",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                            createBtViewTitulosNaoLancados(data.response.compras_caixa_titulos_nao_lancados.inteiro_total, "12_meses", "",data.response.compras_caixa_titulos_nao_lancados.filtro_ano),
                        ];
                        produtos.push(temp_array);

                        temp_array = [
                            data.response.titulos_caixa_aberto.descricao,
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_1, data.response.ano_codigo, "01", "Janeiro",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_2, data.response.ano_codigo, "02", "Fevereiro",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_3, data.response.ano_codigo, "03", "Março",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_4, data.response.ano_codigo, "04", "Abril",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_5, data.response.ano_codigo, "05", "Maio",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_6, data.response.ano_codigo, "06", "Junho",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_7, data.response.ano_codigo, "07", "Julho",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_8, data.response.ano_codigo, "08", "Agosto",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_9, data.response.ano_codigo, "09", "Setembro",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_10, data.response.ano_codigo, "10", "Outubro",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_11, data.response.ano_codigo, "11", "Novembro",data.response.estabelecimentos, false, "aberto"),
                            createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_12, data.response.ano_codigo, "12", "Dezembro",data.response.estabelecimentos, false, "aberto"),
                            data.response.titulos_caixa_aberto.inteiro_total_parcial,
                            data.response.titulos_caixa_aberto.inteiro_total,
                        ];
                        produtos.push(temp_array);

                        temp_array = [
                            data.response.titulos_caixa_fechado.descricao,
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_1, data.response.ano_codigo, "01", "Janeiro",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_2, data.response.ano_codigo, "02", "Fevereiro",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_3, data.response.ano_codigo, "03", "Março",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_4, data.response.ano_codigo, "04", "Abril",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_5, data.response.ano_codigo, "05", "Maio",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_6, data.response.ano_codigo, "06", "Junho",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_7, data.response.ano_codigo, "07", "Julho",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_8, data.response.ano_codigo, "08", "Agosto",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_9, data.response.ano_codigo, "09", "Setembro",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_10, data.response.ano_codigo, "10", "Outubro",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_11, data.response.ano_codigo, "11", "Novembro",data.response.estabelecimentos, false, "fechado"),
                            createBtViewPagar(data.response.titulos_caixa_fechado.inteiro_12, data.response.ano_codigo, "12", "Dezembro",data.response.estabelecimentos, false, "fechado"),
                            data.response.titulos_caixa_fechado.inteiro_total_parcial,
                            data.response.titulos_caixa_fechado.inteiro_total,
                        ];
                        produtos.push(temp_array);
                
                        temp_array = [
                            data.response.titulos_caixa_total.descricao,
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_1, data.response.ano_codigo, "01", "Janeiro",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_2, data.response.ano_codigo, "02", "Fevereiro",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_3, data.response.ano_codigo, "03", "Março",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_4, data.response.ano_codigo, "04", "Abril",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_5, data.response.ano_codigo, "05", "Maio",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_6, data.response.ano_codigo, "06", "Junho",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_7, data.response.ano_codigo, "07", "Julho",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_8, data.response.ano_codigo, "08", "Agosto",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_9, data.response.ano_codigo, "09", "Setembro",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_10, data.response.ano_codigo, "10", "Outubro",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_11, data.response.ano_codigo, "11", "Novembro",data.response.estabelecimentos, false, "total"),
                            createBtViewPagar(data.response.titulos_caixa_total.inteiro_12, data.response.ano_codigo, "12", "Dezembro",data.response.estabelecimentos, false, "total"),
                            data.response.titulos_caixa_total.inteiro_total_parcial,
                            data.response.titulos_caixa_total.inteiro_total,
                        ];
                        produtos.push(temp_array);
                    }else{
                        temp_array = [
                            data.response.orcamento_compras.descricao,
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_1, data.response.ano_codigo, "01", "Janeiro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_2, data.response.ano_codigo, "02", "Fevereiro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_3, data.response.ano_codigo, "03", "Março", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_4, data.response.ano_codigo, "04", "Abril", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_5, data.response.ano_codigo, "05", "Maio", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_6, data.response.ano_codigo, "06", "Junho", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_7, data.response.ano_codigo, "07", "Julho", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_8, data.response.ano_codigo, "08", "Agosto", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_9, data.response.ano_codigo, "09", "Setembro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_10, data.response.ano_codigo, "10", "Outubro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_11, data.response.ano_codigo, "11", "Novembro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_12, data.response.ano_codigo, "12", "Dezembro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_total_parcial, data.response.ano_codigo, "parcial", "", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewComprasPrevisto(data.response.orcamento_compras.inteiro_total, data.response.ano_codigo, "", "", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                        ];
                        produtos.push(temp_array);
                    
                        temp_array = [
                            data.response.compras_planejadas.descricao,
                            createBtViewCompras(data.response.compras_planejadas.inteiro_1, data.response.ano_codigo, "01", "Janeiro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_2, data.response.ano_codigo, "02", "Fevereiro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_3, data.response.ano_codigo, "03", "Março", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_4, data.response.ano_codigo, "04", "Abril", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_5, data.response.ano_codigo, "05", "Maio", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_6, data.response.ano_codigo, "06", "Junho", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_7, data.response.ano_codigo, "07", "Julho", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_8, data.response.ano_codigo, "08", "Agosto", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_9, data.response.ano_codigo, "09", "Setembro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_10, data.response.ano_codigo, "10", "Outubro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_11, data.response.ano_codigo, "11", "Novembro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_12, data.response.ano_codigo, "12", "Dezembro", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_total_parcial, data.response.ano_codigo, "parcial", "", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_planejadas.inteiro_total, data.response.ano_codigo, "", "", "planejada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                        ];
                        produtos.push(temp_array);
                
                        temp_array = [
                            data.response.compras_realizadas.descricao,
                            createBtViewCompras(data.response.compras_realizadas.inteiro_1, data.response.ano_codigo, "01", "Janeiro", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_2, data.response.ano_codigo, "02", "Fevereiro", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_3, data.response.ano_codigo, "03", "Março", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_4, data.response.ano_codigo, "04", "Abril", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_5, data.response.ano_codigo, "05", "Maio", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_6, data.response.ano_codigo, "06", "Junho", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_7, data.response.ano_codigo, "07", "Julho", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_8, data.response.ano_codigo, "08", "Agosto", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_9, data.response.ano_codigo, "09", "Setembro", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_10, data.response.ano_codigo, "10", "Outubro", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_11, data.response.ano_codigo, "11", "Novembro", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_12, data.response.ano_codigo, "12", "Dezembro", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_total_parcial, data.response.ano_codigo, "parcial", "", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                            createBtViewCompras(data.response.compras_realizadas.inteiro_total, data.response.ano_codigo, "", "", "realizada", data.response.tipo_produto_codigo,data.response.estabelecimentos),
                        ];
                        produtos.push(temp_array);
                    }

                    temp_array = [
                        data.response.compras_diferenca.descricao,
                        data.response.compras_diferenca.inteiro_1,
                        data.response.compras_diferenca.inteiro_2,
                        data.response.compras_diferenca.inteiro_3,
                        data.response.compras_diferenca.inteiro_4,
                        data.response.compras_diferenca.inteiro_5,
                        data.response.compras_diferenca.inteiro_6,
                        data.response.compras_diferenca.inteiro_7,
                        data.response.compras_diferenca.inteiro_8,
                        data.response.compras_diferenca.inteiro_9,
                        data.response.compras_diferenca.inteiro_10,
                        data.response.compras_diferenca.inteiro_11,
                        data.response.compras_diferenca.inteiro_12,
                        data.response.compras_diferenca.inteiro_total_parcial,
                        data.response.compras_diferenca.inteiro_total,  
                    ];
                    produtos.push(temp_array);
                    
                    temp_array = [
                        '<b>Despesas</b>',
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        data.response.despesa_planejada.descricao,
                        data.response.despesa_planejada.inteiro_1,
                        data.response.despesa_planejada.inteiro_2,
                        data.response.despesa_planejada.inteiro_3,
                        data.response.despesa_planejada.inteiro_4,
                        data.response.despesa_planejada.inteiro_5,
                        data.response.despesa_planejada.inteiro_6,
                        data.response.despesa_planejada.inteiro_7,
                        data.response.despesa_planejada.inteiro_8,
                        data.response.despesa_planejada.inteiro_9,
                        data.response.despesa_planejada.inteiro_10,
                        data.response.despesa_planejada.inteiro_11,
                        data.response.despesa_planejada.inteiro_12,
                        data.response.despesa_planejada.inteiro_total_parcial,
                        data.response.despesa_planejada.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        data.response.despesa_realizada.descricao,
                        createBtViewPagar(data.response.despesa_realizada.inteiro_1, data.response.ano_codigo, "01", "Janeiro",data.response.estabelecimentos, true, ""),
                        createBtViewPagar(data.response.despesa_realizada.inteiro_2, data.response.ano_codigo, "02", "Fevereiro",data.response.estabelecimentos, true, ""),
                        createBtViewPagar(data.response.despesa_realizada.inteiro_3, data.response.ano_codigo, "03", "Março",data.response.estabelecimentos, true, ""),
                        createBtViewPagar(data.response.despesa_realizada.inteiro_4, data.response.ano_codigo, "04", "Abril",data.response.estabelecimentos, true, ""),
                        createBtViewPagar(data.response.despesa_realizada.inteiro_5, data.response.ano_codigo, "05", "Maio",data.response.estabelecimentos, true, ""),
                        createBtViewPagar(data.response.despesa_realizada.inteiro_6, data.response.ano_codigo, "06", "Junho",data.response.estabelecimentos, true, ""),
                        createBtViewPagar(data.response.despesa_realizada.inteiro_7, data.response.ano_codigo, "07", "Julho",data.response.estabelecimentos, true, ""),
                        createBtViewPagar(data.response.despesa_realizada.inteiro_8, data.response.ano_codigo, "08", "Agosto",data.response.estabelecimentos, true, ""),
                        createBtViewPagar(data.response.despesa_realizada.inteiro_9, data.response.ano_codigo, "09", "Setembro",data.response.estabelecimentos, true, ""),
                        createBtViewPagar(data.response.despesa_realizada.inteiro_10, data.response.ano_codigo, "10", "Outubro",data.response.estabelecimentos, true, ""),
                        createBtViewPagar(data.response.despesa_realizada.inteiro_11, data.response.ano_codigo, "11", "Novembro",data.response.estabelecimentos, true, ""),
                        createBtViewPagar(data.response.despesa_realizada.inteiro_12, data.response.ano_codigo, "12", "Dezembro",data.response.estabelecimentos, true, ""),
                        data.response.despesa_realizada.inteiro_total_parcial,
                        data.response.despesa_realizada.inteiro_total,
                    ];
                    produtos.push(temp_array);
            
                    temp_array = [
                        data.response.despesa_diferenca.descricao,
                        data.response.despesa_diferenca.inteiro_1,
                        data.response.despesa_diferenca.inteiro_2,
                        data.response.despesa_diferenca.inteiro_3,
                        data.response.despesa_diferenca.inteiro_4,
                        data.response.despesa_diferenca.inteiro_5,
                        data.response.despesa_diferenca.inteiro_6,
                        data.response.despesa_diferenca.inteiro_7,
                        data.response.despesa_diferenca.inteiro_8,
                        data.response.despesa_diferenca.inteiro_9,
                        data.response.despesa_diferenca.inteiro_10,
                        data.response.despesa_diferenca.inteiro_11,
                        data.response.despesa_diferenca.inteiro_12,
                        data.response.despesa_diferenca.inteiro_total_parcial,
                        data.response.despesa_diferenca.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        '<b>Bancos</b>',
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        data.response.banco_previsto.descricao,
                        data.response.banco_previsto.inteiro_1,
                        data.response.banco_previsto.inteiro_2,
                        data.response.banco_previsto.inteiro_3,
                        data.response.banco_previsto.inteiro_4,
                        data.response.banco_previsto.inteiro_5,
                        data.response.banco_previsto.inteiro_6,
                        data.response.banco_previsto.inteiro_7,
                        data.response.banco_previsto.inteiro_8,
                        data.response.banco_previsto.inteiro_9,
                        data.response.banco_previsto.inteiro_10,
                        data.response.banco_previsto.inteiro_11,
                        data.response.banco_previsto.inteiro_12,
                        data.response.banco_previsto.inteiro_total_parcial,
                        data.response.banco_previsto.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        createBtViewBancoRealizado(data.response.banco_realizado.descricao, data.response.ano_codigo),
                        data.response.banco_realizado.inteiro_1,
                        data.response.banco_realizado.inteiro_2,
                        data.response.banco_realizado.inteiro_3,
                        data.response.banco_realizado.inteiro_4,
                        data.response.banco_realizado.inteiro_5,
                        data.response.banco_realizado.inteiro_6,
                        data.response.banco_realizado.inteiro_7,
                        data.response.banco_realizado.inteiro_8,
                        data.response.banco_realizado.inteiro_9,
                        data.response.banco_realizado.inteiro_10,
                        data.response.banco_realizado.inteiro_11,
                        data.response.banco_realizado.inteiro_12,
                        data.response.banco_realizado.inteiro_total_parcial,
                        data.response.banco_realizado.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        data.response.banco_diferenca.descricao,
                        data.response.banco_diferenca.inteiro_1,
                        data.response.banco_diferenca.inteiro_2,
                        data.response.banco_diferenca.inteiro_3,
                        data.response.banco_diferenca.inteiro_4,
                        data.response.banco_diferenca.inteiro_5,
                        data.response.banco_diferenca.inteiro_6,
                        data.response.banco_diferenca.inteiro_7,
                        data.response.banco_diferenca.inteiro_8,
                        data.response.banco_diferenca.inteiro_9,
                        data.response.banco_diferenca.inteiro_10,
                        data.response.banco_diferenca.inteiro_11,
                        data.response.banco_diferenca.inteiro_12,
                        data.response.banco_diferenca.inteiro_total_parcial,
                        data.response.banco_diferenca.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        '<b>Faturamento - Compras - Despesas - Bancos</b>',
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        data.response.vendas_menos_compras_menos_despesas_previsto.descricao,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_1,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_2,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_3,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_4,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_5,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_6,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_7,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_8,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_9,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_10,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_11,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_12,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_total_parcial,
                        data.response.vendas_menos_compras_menos_despesas_previsto.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        data.response.vendas_menos_compras_menos_despesas_realizado.descricao,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_1,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_2,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_3,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_4,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_5,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_6,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_7,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_8,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_9,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_10,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_11,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_12,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_total_parcial,
                        data.response.vendas_menos_compras_menos_despesas_realizado.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    if (form.find("#modo").val() === 'fluxo_caixa'){
                        temp_array = [
                            data.response.vendas_menos_compras_menos_despesas_previsao.descricao,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_1,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_2,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_3,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_4,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_5,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_6,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_7,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_8,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_9,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_10,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_11,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_12,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_total_parcial,
                            data.response.vendas_menos_compras_menos_despesas_previsao.inteiro_total,
                        ];
                        produtos.push(temp_array);
                    }

                    temp_array = [
                        data.response.vendas_menos_compras_menos_despesas_diferenca.descricao,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_1,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_2,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_3,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_4,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_5,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_6,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_7,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_8,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_9,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_10,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_11,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_12,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_total_parcial,
                        data.response.vendas_menos_compras_menos_despesas_diferenca.inteiro_total,
                    ];
                    produtos.push(temp_array);
            
                    temp_array = [
                        '<b>Estoque</b>',
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                    ];
                    produtos.push(temp_array);
                    
                    temp_array = [
                        data.response.estoque_entrada.descricao,
                        data.response.estoque_entrada.inteiro_1,
                        data.response.estoque_entrada.inteiro_2,
                        data.response.estoque_entrada.inteiro_3,
                        data.response.estoque_entrada.inteiro_4,
                        data.response.estoque_entrada.inteiro_5,
                        data.response.estoque_entrada.inteiro_6,
                        data.response.estoque_entrada.inteiro_7,
                        data.response.estoque_entrada.inteiro_8,
                        data.response.estoque_entrada.inteiro_9,
                        data.response.estoque_entrada.inteiro_10,
                        data.response.estoque_entrada.inteiro_11,
                        data.response.estoque_entrada.inteiro_12,
                        data.response.estoque_entrada.inteiro_total_parcial,
                        data.response.estoque_entrada.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        data.response.estoque_saida.descricao,
                        data.response.estoque_saida.inteiro_1,
                        data.response.estoque_saida.inteiro_2,
                        data.response.estoque_saida.inteiro_3,
                        data.response.estoque_saida.inteiro_4,
                        data.response.estoque_saida.inteiro_5,
                        data.response.estoque_saida.inteiro_6,
                        data.response.estoque_saida.inteiro_7,
                        data.response.estoque_saida.inteiro_8,
                        data.response.estoque_saida.inteiro_9,
                        data.response.estoque_saida.inteiro_10,
                        data.response.estoque_saida.inteiro_11,
                        data.response.estoque_saida.inteiro_12,
                        data.response.estoque_saida.inteiro_total_parcial,
                        data.response.estoque_saida.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        data.response.estoque_final.descricao,
                        data.response.estoque_final.inteiro_1,
                        data.response.estoque_final.inteiro_2,
                        data.response.estoque_final.inteiro_3,
                        data.response.estoque_final.inteiro_4,
                        data.response.estoque_final.inteiro_5,
                        data.response.estoque_final.inteiro_6,
                        data.response.estoque_final.inteiro_7,
                        data.response.estoque_final.inteiro_8,
                        data.response.estoque_final.inteiro_9,
                        data.response.estoque_final.inteiro_10,
                        data.response.estoque_final.inteiro_11,
                        data.response.estoque_final.inteiro_12,
                        data.response.estoque_final.inteiro_total_parcial,
                        data.response.estoque_final.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        '<div>'+data.response.estoque_previsao.descricao+'<a href="#" class="btn-informacao-sem-width" data-toggle="tooltip" data-placement="top" title="" data-original-title="Cálculo: Estoque Final do Mês Anterior + Compras Previsto - (Faturamento Previsto - '+margem_venda+'%)"></a></div>',
                        data.response.estoque_previsao.inteiro_1,
                        data.response.estoque_previsao.inteiro_2,
                        data.response.estoque_previsao.inteiro_3,
                        data.response.estoque_previsao.inteiro_4,
                        data.response.estoque_previsao.inteiro_5,
                        data.response.estoque_previsao.inteiro_6,
                        data.response.estoque_previsao.inteiro_7,
                        data.response.estoque_previsao.inteiro_8,
                        data.response.estoque_previsao.inteiro_9,
                        data.response.estoque_previsao.inteiro_10,
                        data.response.estoque_previsao.inteiro_11,
                        data.response.estoque_previsao.inteiro_12,
                        data.response.estoque_previsao.inteiro_total_parcial,
                        data.response.estoque_previsao.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        '<b>Compras sobre Faturamento %</b>',
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                        "",
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        '<div>'+data.response.faturamento_compras_planejado.descricao+'<a href="#" class="btn-informacao-sem-width" data-toggle="tooltip" data-placement="top" title="" data-original-title="Compras Orçamento x Faturamento Previsto %"></a></div>',
                        data.response.faturamento_compras_planejado.inteiro_1,
                        data.response.faturamento_compras_planejado.inteiro_2,
                        data.response.faturamento_compras_planejado.inteiro_3,
                        data.response.faturamento_compras_planejado.inteiro_4,
                        data.response.faturamento_compras_planejado.inteiro_5,
                        data.response.faturamento_compras_planejado.inteiro_6,
                        data.response.faturamento_compras_planejado.inteiro_7,
                        data.response.faturamento_compras_planejado.inteiro_8,
                        data.response.faturamento_compras_planejado.inteiro_9,
                        data.response.faturamento_compras_planejado.inteiro_10,
                        data.response.faturamento_compras_planejado.inteiro_11,
                        data.response.faturamento_compras_planejado.inteiro_12,
                        data.response.faturamento_compras_planejado.inteiro_total_parcial,
                        data.response.faturamento_compras_planejado.inteiro_total,
                    ];
                    produtos.push(temp_array);

                    temp_array = [
                        '<div>'+data.response.faturamento_compras_realizado.descricao+'<a href="#" class="btn-informacao-sem-width" data-toggle="tooltip" data-placement="top" title="" data-original-title="Compras Realizada x Faturamento Realizado %"></a></div>',
                        data.response.faturamento_compras_realizado.inteiro_1,
                        data.response.faturamento_compras_realizado.inteiro_2,
                        data.response.faturamento_compras_realizado.inteiro_3,
                        data.response.faturamento_compras_realizado.inteiro_4,
                        data.response.faturamento_compras_realizado.inteiro_5,
                        data.response.faturamento_compras_realizado.inteiro_6,
                        data.response.faturamento_compras_realizado.inteiro_7,
                        data.response.faturamento_compras_realizado.inteiro_8,
                        data.response.faturamento_compras_realizado.inteiro_9,
                        data.response.faturamento_compras_realizado.inteiro_10,
                        data.response.faturamento_compras_realizado.inteiro_11,
                        data.response.faturamento_compras_realizado.inteiro_12,
                        data.response.faturamento_compras_realizado.inteiro_total_parcial,
                        data.response.faturamento_compras_realizado.inteiro_total,
                    ];
                    produtos.push(temp_array);
                }
    
                table_filters_acompanhamento_orcamentario.rows.add(produtos).draw();
            },
            error: function(data){
                var errors = data.responseJSON.error;
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
    }

    function filterClear(){
        table_filters_acompanhamento_orcamentario.clear().draw();
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function createBtViewCompras(valor, ano, mes, nome_mes, tipo, tipo_produto,estabelecimentos){
        if(tipo == 'planejada'){
            if(mes == ""){
                title = "Compras em Aberto do "+ano;
            }else{
                title = "Compras em Aberto "+nome_mes+" de "+ano;
            }
        }else{
            if(mes == ""){
                title = "Compras em Realizada do "+ano;
            }else{
                title = "Compras em Realizada "+nome_mes+" de "+ano;
            }
        }

        html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-ano=\""+ano+"\" data-mes=\""+mes+"\" data-tipo=\""+tipo+"\" data-tipo_produto=\""+tipo_produto+"\" data-estabelecimentos=\""+estabelecimentos+"\" title='Visualizar' data-title=\""+title+"\" onclick=\"abriModalCompras($(this))\">"+valor+"</a>";
        
        return html;
    }

    function abriModalCompras($this){
        var ano = $($this).data("ano");
        var mes = $($this).data("mes");
        var tipo = $($this).data("tipo");
        var tipo_produto = $($this).data("tipo_produto");
        var estabelecimentos = $($this).data('estabelecimentos');
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('acompanhamento_orcamentario.modal.compras_detalhes') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ano: ano,
                mes: mes,
                tipo: tipo,
                tipo_produto: tipo_produto,
                estabelecimentos :estabelecimentos,
            },
            success: function(body){
                createModal('modal_compras', title, body, "modal-lg");
                var modal = $("#modal_compras");
            }
        });
    }

    function esconderPopoverTooltip(){
        $('[data-toggle="tooltip"]').tooltip('hide');
        $('[data-toggle="popover"]').popover('hide');
    }

    function createBtViewRealizado(descricao, ano){
        html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-ano=\""+ano+"\" title='Visualizar' data-codigo_conta=\"5\" data-title=\"Despesa Realizada "+ano+"\" onclick=\"abriModalDespesa($(this))\">"+descricao+"</a>";
        
        return html;
    }

    function createBtViewBancoRealizado(descricao, ano){
        html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-ano=\""+ano+"\" title='Visualizar' data-title=\"Banco Realizada "+ano+"\" onclick=\"abriModalBanco($(this))\">"+descricao+"</a>";
        
        return html;
    }

    function abriModalDespesa($this){
        var ano = $($this).data("ano");
        var title = $($this).data("title");
        var codigo_conta  = $($this).data("codigo_conta");
        $.ajax({
            url: '{{ route('acompanhamento_orcamentario.modal.despesa_detalhes') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ano: ano,
                codigo_conta: codigo_conta,
            },
            success: function(body){
                createModal('modal_detalhes_despesa', title, body, "modal-lg");
                var modal = $("#modal_detalhes_despesa");
            }
        });
    }

    function abriModalBanco($this){
        var ano = $($this).data("ano");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('acompanhamento_orcamentario.modal.banco_detalhes') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ano: ano,
            },
            success: function(body){
                createModal('modal_detalhes_banco', title, body, "modal-lg");
                var modal = $("#modal_detalhes_banco");
            }
        });
    }

    function createBtViewPagar(valor, ano, mes, nome_mes,estabelecimentos, despesas, tipo){

        if(despesas){
            title = "Titulos Em Despesas "+nome_mes+" de "+ano;
        }else if(tipo == "aberto"){
            title = "Titulos Em Aberto "+nome_mes+" de "+ano;
        }else if(tipo == "fechado"){
            title = "Titulos Pagos "+nome_mes+" de "+ano;
        }else if(tipo == "total"){
            title = "Titulos "+nome_mes+" de "+ano;
        }else{
            title = "Titulos Em Aberto "+nome_mes+" de "+ano;
        }
        
        html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-ano=\""+ano+"\" data-mes=\""+mes+"\"  data-estabelecimentos=\""+estabelecimentos+"\" title='Visualizar' data-title=\""+title+"\" data-despesas=\""+despesas+"\"  data-tipo=\""+tipo+"\" onclick=\"abriModalPagar($(this))\">"+valor+"</a>";

        return html;
    }

    function abriModalPagar($this){
        var ano = $($this).data("ano");
        var mes= $($this).data("mes");
        var title = $($this).data('title');
        var estabelecimentos = $($this).data('estabelecimentos');
        var despesas = $($this).data('despesas');
        var tipo = $($this).data('tipo');
        $.ajax({
            url: '{{ route('titulos_apagar.modal.titulos_abertura') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}", 
                ano : ano,
                mes : mes,
                estabelecimentos : estabelecimentos,
                banco_boolean: false,
                despesas: despesas,
                tipo: tipo,
            },
            success: function(body){
                createModal("model_aberturas_titulos", title, body, 'modal-lg');
                var modal = $("#model_aberturas_titulos");
            }
        });
    }

    function createBtViewFaturamentoPrevistoFluxoCaixo(descricao, ano, titulo, flag){
        if(flag == true){
            html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-ano=\""+ano+"\" title='Visualizar' data-title=\"Faturamento Previsto Fluxo Caixa "+titulo+"\" onclick=\"abriModalFaturamentoPrevistoFluxoCaixoDetalhes($(this))\">"+descricao+"</a>" + '<a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="top" title="" data-original-title="Falta Lançamento da Previsão do Faturamento da Competência do Mês." style="color: black;"></a>';
        }else{
            html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-ano=\""+ano+"\" title='Visualizar' data-title=\"Faturamento Previsto Fluxo Caixa "+titulo+"\" onclick=\"abriModalFaturamentoPrevistoFluxoCaixoDetalhes($(this))\">"+descricao+"</a>";
        }        
        
        return html;
    }

    function createBtViewTitulosNaoLancados(descricao, mes, titulo,filtro_ano){
        html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-filtro_ano=\""+filtro_ano+"\" data-mes=\""+mes+"\" title='Visualizar' data-title=\"Títulos não lançados "+titulo+"\" onclick=\"abriModalTitulosNaoLancados($(this))\">"+descricao+"</a>";
        
        return html;
    }

    function createBtViewPedidoComprasAbertoFuturo(descricao, mes, titulo,filtro_ano){
        html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-filtro_ano=\""+filtro_ano+"\" data-mes=\""+mes+"\" title='Visualizar' data-title=\"Pedido Compras Aberto "+titulo+"\" onclick=\"abriModalPedidoComprasAbertoFuturo($(this))\">"+descricao+"</a>";
        
        return html;
    }

    function abriModalTitulosNaoLancados($this){
        var mes = $($this).data("mes");
        var title = $($this).data("title");
        var filtro_ano = $($this).data("filtro_ano");

        $.ajax({
            url: '{{ route('acompanhamento_orcamentario.modal.compras_titulos_nao_lancados') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                mes: mes,
                filtro_ano: filtro_ano,
            },
            success: function(body){
                createModal('modal_detalhes_faturamento_previsto_fluxo_caixa', title, body, "modal-lg");
                var modal = $("#modal_detalhes_faturamento_previsto_fluxo_caixa");
            }
        });
    }

    
    function abriModalPedidoComprasAbertoFuturo($this){
        var mes = $($this).data("mes");
        var title = $($this).data("title");
        var filtro_ano = $($this).data("filtro_ano");

        $.ajax({
            url: '{{ route('pedidos_compras_abertos_titulos_futuros.modal.detalhes') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                mes: mes,
                filtro_ano: filtro_ano,
            },
            success: function(body){
                createModal('modal_detalhes_pedidos_compras_abertos_titulos_futuros', title, body, "modal-lg");
                var modal = $("#modal_detalhes_pedidos_compras_abertos_titulos_futuros");
            }
        });
    }

    function abriModalFaturamentoPrevistoFluxoCaixoDetalhes($this){
        var ano = $($this).data("ano");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('faturamento_previsto_fluxo_caixa.modal.detalhes') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ano: ano,
            },
            success: function(body){
                createModal('modal_detalhes_faturamento_previsto_fluxo_caixa', title, body, "modal-lg");
                var modal = $("#modal_detalhes_faturamento_previsto_fluxo_caixa");
            }
        });
    }

    function createBtViewComprasPrevistoFluxoCaixo(descricao, ano, titulo){
        html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-ano=\""+ano+"\" title='Visualizar' data-title=\"Compras Previsto Fluxo Caixa "+titulo+"\" onclick=\"abriModalComprasPrevistoFluxoCaixoDetalhes($(this))\">"+descricao+"</a>";
        
        return html;
    }

    function abriModalComprasPrevistoFluxoCaixoDetalhes($this){
        var ano = $($this).data("ano");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('compras_previsto_fluxo_caixa.modal.detalhes') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ano: ano,
            },
            success: function(body){
                createModal('modal_detalhes_compras_previsto_fluxo_caixa', title, body, "modal-lg");
                var modal = $("#modal_detalhes_compras_previsto_fluxo_caixa");
            }
        });
    }

    function createBtViewFaturamentoPrevistoFluxoCaixoComparativo(descricao, ano, titulo){
        html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-ano=\""+ano+"\" title='Visualizar' data-title=\"Faturamento Previsto Fluxo Caixa "+titulo+"\" onclick=\"abriModalFaturamentoPrevistoFluxoCaixoDetalhesComparativo($(this))\">"+descricao+"</a>";
        
        return html;
    }

    function abriModalFaturamentoPrevistoFluxoCaixoDetalhesComparativo($this){
        var ano = $($this).data("ano");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('faturamento_previsto_fluxo_caixa.modal.detalhes_comparativo') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ano: ano,
            },
            success: function(body){
                createModal('modal_detalhes_faturamento_previsto_fluxo_caixa', title, body, "modal-lg");
                var modal = $("#modal_detalhes_faturamento_previsto_fluxo_caixa");
            }
        });
    }

    function createBtViewComprasPrevisto(valor, ano, mes, nome_mes, tipo, tipo_produto,estabelecimentos){
        if(tipo == 'planejada'){
            if(mes == ""){
                title = "Compras em Aberto do "+ano;
            }else{
                title = "Compras em Aberto "+nome_mes+" de "+ano;
            }
        }else{
            if(mes == ""){
                title = "Compras em Realizada do "+ano;
            }else{
                title = "Compras em Realizada "+nome_mes+" de "+ano;
            }
        }

        html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-ano=\""+ano+"\" data-mes=\""+mes+"\" data-tipo=\""+tipo+"\" data-tipo_produto=\""+tipo_produto+"\" data-estabelecimentos=\""+estabelecimentos+"\" title='Visualizar' data-title=\""+title+"\" onclick=\"abriModalComprasPrevisto($(this))\">"+valor+"</a>";
        
        return html;
    }

    function abriModalComprasPrevisto($this){
        var ano = $($this).data("ano");
        var mes = $($this).data("mes");
        var tipo = $($this).data("tipo");
        var tipo_produto = $($this).data("tipo_produto");
        var estabelecimentos = $($this).data('estabelecimentos');
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('acompanhamento_orcamentario.modal.compras_previsto_detalhes') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ano: ano,
                mes: mes,
                tipo: tipo,
                tipo_produto: tipo_produto,
                estabelecimentos :estabelecimentos,
            },
            success: function(body){
                createModal('modal_compras', title, body, "modal-lg");
                var modal = $("#modal_compras");
            }
        });
    }
@endsection
