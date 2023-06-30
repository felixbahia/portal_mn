@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
                {!! Form::text('ano', date('Y'), ['id' => 'ano', 'class' => 'form-control data', 'placeholder' => 'Ano']) !!}
            </div>
            <div class="col-lg-4">
                {!! Form::select('tipo_produto', $tipo_produto, '', ['id' => 'tipo_produto', 'class' => 'form-control', 'placeholder' => 'Todos']) !!}
            </div>

            <div class="col-lg-1">
                <div class="form-check">
                    {!! Form::checkbox('caixa', 'false', false, ['id' => 'caixa', 'class' => 'form-check-input']) !!}
                    {!! Form::label('caixa', 'Fluxo de Caixa', ['class' => 'form-check-label']) !!}
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

    table_filters_acompanhamento_compras_anual= $(document).find('#table-filters-acompanhamento_estoque').DataTable({
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
            if($(data[0].replace(' %', '')).text() === 'Compras'){
                $('td:eq(0)', row).attr('colspan', 15);
                $('td:eq(0)', row).addClass('tb_date');

                 if($(data[0].replace(' %', '')).text() === 'Compras'){
                    $(row).addClass('table-secondary');
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
        url: '{{ route('acompanhamento_compras_anual.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(data){


            produtos = [];
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


            if (form.find("#caixa").is(":checked") === true){

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
                                createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_1, data.response.ano_codigo, "01", "Janeiro",data.response.estabelecimentos),
                                createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_2, data.response.ano_codigo, "02", "Fevereiro",data.response.estabelecimentos),
                                createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_3, data.response.ano_codigo, "03", "Março",data.response.estabelecimentos),
                                createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_4, data.response.ano_codigo, "04", "Abril",data.response.estabelecimentos),
                                createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_5, data.response.ano_codigo, "05", "Maio",data.response.estabelecimentos),
                                createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_6, data.response.ano_codigo, "06", "Junho",data.response.estabelecimentos),
                                createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_7, data.response.ano_codigo, "07", "Julho",data.response.estabelecimentos),
                                createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_8, data.response.ano_codigo, "08", "Agosto",data.response.estabelecimentos),
                                createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_9, data.response.ano_codigo, "09", "Setembro",data.response.estabelecimentos),
                                createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_10, data.response.ano_codigo, "10", "Outubro",data.response.estabelecimentos),
                                createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_11, data.response.ano_codigo, "11", "Novembro",data.response.estabelecimentos),
                                createBtViewPagar(data.response.titulos_caixa_aberto.inteiro_12, data.response.ano_codigo, "12", "Dezembro",data.response.estabelecimentos),
                                data.response.titulos_caixa_aberto.inteiro_total_parcial,
                            data.response.titulos_caixa_aberto.inteiro_total,

                            ];
                            produtos.push(temp_array);

                            temp_array = [
                                data.response.titulos_caixa_fechado.descricao,
                                data.response.titulos_caixa_fechado.inteiro_1,
                                data.response.titulos_caixa_fechado.inteiro_2,
                                data.response.titulos_caixa_fechado.inteiro_3,
                                data.response.titulos_caixa_fechado.inteiro_4,
                                data.response.titulos_caixa_fechado.inteiro_5,
                                data.response.titulos_caixa_fechado.inteiro_6,
                                data.response.titulos_caixa_fechado.inteiro_7,
                                data.response.titulos_caixa_fechado.inteiro_8,
                                data.response.titulos_caixa_fechado.inteiro_9,
                                data.response.titulos_caixa_fechado.inteiro_10,
                                data.response.titulos_caixa_fechado.inteiro_11,
                                data.response.titulos_caixa_fechado.inteiro_12,
                                data.response.titulos_caixa_fechado.inteiro_total_parcial,
                                data.response.titulos_caixa_fechado.inteiro_total,
                        
                                ];
                            produtos.push(temp_array);

                            temp_array = [
                                data.response.titulos_caixa_total.descricao,
                                data.response.titulos_caixa_total.inteiro_1,
                                data.response.titulos_caixa_total.inteiro_2,
                                data.response.titulos_caixa_total.inteiro_3,
                                data.response.titulos_caixa_total.inteiro_4,
                                data.response.titulos_caixa_total.inteiro_5,
                                data.response.titulos_caixa_total.inteiro_6,
                                data.response.titulos_caixa_total.inteiro_7,
                                data.response.titulos_caixa_total.inteiro_8,
                                data.response.titulos_caixa_total.inteiro_9,
                                data.response.titulos_caixa_total.inteiro_10,
                                data.response.titulos_caixa_total.inteiro_11,
                                data.response.titulos_caixa_total.inteiro_12,
                                data.response.titulos_caixa_total.inteiro_total_parcial,
                                data.response.titulos_caixa_total.inteiro_total,
                            ];
                            produtos.push(temp_array);
          
                }else{

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


                }

           

            table_filters_acompanhamento_compras_anual.rows.add(produtos).draw();   
            
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
    table_filters_acompanhamento_compras_anual.clear().draw();
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
        url: '{{ route('acompanhamento_compras_anual.modal.compras_detalhes') }}',
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


function createBtViewPagar(valor, ano, mes, nome_mes,estabelecimentos){

    title = "Titulos Em Aberto "+nome_mes+" de "+ano;

html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-ano=\""+ano+"\" data-mes=\""+mes+"\"  data-estabelecimentos=\""+estabelecimentos+"\" title='Visualizar' data-title=\""+title+"\" onclick=\"abriModalPagar($(this))\">"+valor+"</a>";

return html;
}
function abriModalPagar($this){
            var ano = $($this).data("ano");
            var mes= $($this).data("mes");
            var title = $($this).data('title');
            var estabelecimentos = $($this).data('estabelecimentos');
  
            $.ajax({
            url: '{{ route('titulos_apagar.modal.titulos_abertura') }}',
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", ano : ano,mes : mes,estabelecimentos : estabelecimentos},
            success: function(body){
                createModal("model_aberturas_titulos", title, body, 'modal-lg');
                var modal = $("#model_aberturas_titulos");
            }

            });


}



@endsection
