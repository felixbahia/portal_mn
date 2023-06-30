@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="row">
                <div class="col-lg-2">
                    {!! Form::select('estabelecimento', $estabelecimentos, '',['id' => 'estabelecimento', 'class' => 'form-control']) !!}
                </div>
                <div class="col-lg-2">
                    {!! Form::text('ano', date('Y'), ['id' => 'ano', 'class' => 'form-control data', 'placeholder' => 'Ano']) !!}
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

        data_form = form.serialize();
        $('label.error-message').remove();
        $.ajax({
            url: '{{ route('valor_estoque_armazem.filtro')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                produtos = [];
                temp_array = [
                    data.response.dados.saldo_anterior.descricao,
                    data.response.dados.saldo_anterior.inteiro_1, 
                    data.response.dados.saldo_anterior.inteiro_2, 
                    data.response.dados.saldo_anterior.inteiro_3,
                    data.response.dados.saldo_anterior.inteiro_4,
                    data.response.dados.saldo_anterior.inteiro_5,
                    data.response.dados.saldo_anterior.inteiro_6,
                    data.response.dados.saldo_anterior.inteiro_7,
                    data.response.dados.saldo_anterior.inteiro_8,
                    data.response.dados.saldo_anterior.inteiro_9,
                    data.response.dados.saldo_anterior.inteiro_10,
                    data.response.dados.saldo_anterior.inteiro_11,
                    data.response.dados.saldo_anterior.inteiro_12,
                    data.response.dados.saldo_anterior.inteiro_total,
                ];
                produtos.push(temp_array);
                temp_array = [
                    data.response.dados.entrada.descricao,
                    createBtViewEntrada(data.response.dados.entrada.inteiro_1, data.response.dados.ano_codigo, "01", "Janeiro", data.response.estabelecimento),
                    createBtViewEntrada(data.response.dados.entrada.inteiro_2, data.response.dados.ano_codigo, "02", "Fevereiro", data.response.estabelecimento),
                    createBtViewEntrada(data.response.dados.entrada.inteiro_3, data.response.dados.ano_codigo, "03", "Março", data.response.estabelecimento),
                    createBtViewEntrada(data.response.dados.entrada.inteiro_4, data.response.dados.ano_codigo, "04", "Abril", data.response.estabelecimento),
                    createBtViewEntrada(data.response.dados.entrada.inteiro_5, data.response.dados.ano_codigo, "05", "Maio", data.response.estabelecimento),
                    createBtViewEntrada(data.response.dados.entrada.inteiro_6, data.response.dados.ano_codigo, "06", "Junho", data.response.estabelecimento),
                    createBtViewEntrada(data.response.dados.entrada.inteiro_7, data.response.dados.ano_codigo, "07", "Julho", data.response.estabelecimento),
                    createBtViewEntrada(data.response.dados.entrada.inteiro_8, data.response.dados.ano_codigo, "08", "Agosto", data.response.estabelecimento),
                    createBtViewEntrada(data.response.dados.entrada.inteiro_9, data.response.dados.ano_codigo, "09", "Setembro", data.response.estabelecimento),
                    createBtViewEntrada(data.response.dados.entrada.inteiro_10, data.response.dados.ano_codigo, "10", "Outubro", data.response.estabelecimento),
                    createBtViewEntrada(data.response.dados.entrada.inteiro_11, data.response.dados.ano_codigo, "11", "Novembro", data.response.estabelecimento),
                    createBtViewEntrada(data.response.dados.entrada.inteiro_12, data.response.dados.ano_codigo, "12", "Dezembro", data.response.estabelecimento),
                    createBtViewEntrada(data.response.dados.entrada.inteiro_total, data.response.dados.ano_codigo, "", "", data.response.estabelecimento),
                ];
                produtos.push(temp_array);
                temp_array = [
                    data.response.dados.saida.descricao,
                    createBtViewSaida(data.response.dados.saida.inteiro_1, data.response.dados.ano_codigo, "01", "Janeiro", data.response.estabelecimento),
                    createBtViewSaida(data.response.dados.saida.inteiro_2, data.response.dados.ano_codigo, "02", "Fevereiro", data.response.estabelecimento),
                    createBtViewSaida(data.response.dados.saida.inteiro_3, data.response.dados.ano_codigo, "03", "Março", data.response.estabelecimento),
                    createBtViewSaida(data.response.dados.saida.inteiro_4, data.response.dados.ano_codigo, "04", "Abril", data.response.estabelecimento),
                    createBtViewSaida(data.response.dados.saida.inteiro_5, data.response.dados.ano_codigo, "05", "Maio", data.response.estabelecimento),
                    createBtViewSaida(data.response.dados.saida.inteiro_6, data.response.dados.ano_codigo, "06", "Junho", data.response.estabelecimento),
                    createBtViewSaida(data.response.dados.saida.inteiro_7, data.response.dados.ano_codigo, "07", "Julho", data.response.estabelecimento),
                    createBtViewSaida(data.response.dados.saida.inteiro_8, data.response.dados.ano_codigo, "08", "Agosto", data.response.estabelecimento),
                    createBtViewSaida(data.response.dados.saida.inteiro_9, data.response.dados.ano_codigo, "09", "Setembro", data.response.estabelecimento),
                    createBtViewSaida(data.response.dados.saida.inteiro_10, data.response.dados.ano_codigo, "10", "Outubro", data.response.estabelecimento),
                    createBtViewSaida(data.response.dados.saida.inteiro_11, data.response.dados.ano_codigo, "11", "Novembro", data.response.estabelecimento),
                    createBtViewSaida(data.response.dados.saida.inteiro_12, data.response.dados.ano_codigo, "12", "Dezembro", data.response.estabelecimento),
                    createBtViewSaida(data.response.dados.saida.inteiro_total, data.response.dados.ano_codigo, "", "", data.response.estabelecimento),
                ];
                produtos.push(temp_array);
                temp_array = [
                    data.response.dados.saldo_atual.descricao,
                    data.response.dados.saldo_atual.inteiro_1, 
                    data.response.dados.saldo_atual.inteiro_2, 
                    data.response.dados.saldo_atual.inteiro_3,
                    data.response.dados.saldo_atual.inteiro_4,
                    data.response.dados.saldo_atual.inteiro_5,
                    data.response.dados.saldo_atual.inteiro_6,
                    data.response.dados.saldo_atual.inteiro_7,
                    data.response.dados.saldo_atual.inteiro_8,
                    data.response.dados.saldo_atual.inteiro_9,
                    data.response.dados.saldo_atual.inteiro_10,
                    data.response.dados.saldo_atual.inteiro_11,
                    data.response.dados.saldo_atual.inteiro_12,
                    data.response.dados.saldo_atual.inteiro_total,
                ];
                produtos.push(temp_array);

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

    function createBtViewEntrada(valor, ano, mes, nome_mes, estabelecimento){
        if(nome_mes == ''){
            title = "ICMS - Estoque Armazém Entrada de "+ano;
        }else{
            title = "ICMS - Estoque Armazém Entrada "+nome_mes+" de "+ano;
        }        

        html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-ano=\""+ano+"\" data-mes=\""+mes+"\" data-estabelecimento=\""+estabelecimento+"\" title='Visualizar' data-title=\""+title+"\" onclick=\"abriModalEntrada($(this))\">"+valor+"</a>";
        
        return html;
    }

    function abriModalEntrada($this){
        var ano = $($this).data("ano");
        var mes = $($this).data("mes");
        var estabelecimento = $($this).data("estabelecimento");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('valor_estoque_armazem.modal.entrada') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ano: ano,
                mes: mes,
                estabelecimento: estabelecimento
            },
            success: function(body){
                createModal('modal_entrada', title, body, "modal-lg");
                var modal = $("#modal_entrada");
            }
        });
    }

    function createBtViewSaida(valor, ano, mes, nome_mes, estabelecimento){
        if(nome_mes == ''){
            title = "ICMS - Estoque Armazém Saída de "+ano;
        }else{
            title = "ICMS - Estoque Armazém Saída "+nome_mes+" de "+ano;
        }        

        html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-ano=\""+ano+"\" data-mes=\""+mes+"\" data-estabelecimento=\""+estabelecimento+"\" title='Visualizar' data-title=\""+title+"\" onclick=\"abriModalSaida($(this))\">"+valor+"</a>";
        
        return html;
    }

    function abriModalSaida($this){
        var ano = $($this).data("ano");
        var mes = $($this).data("mes");
        var estabelecimento = $($this).data("estabelecimento");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('valor_estoque_armazem.modal.saida') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ano: ano,
                mes: mes,
                estabelecimento: estabelecimento,
            },
            success: function(body){
                createModal('modal_saida', title, body, "modal-lg");
                var modal = $("#modal_saida");
            }
        });
    }

@endsection
