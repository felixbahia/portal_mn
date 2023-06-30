@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_geracao_pedido_compras" id="form_geracao_pedido_compras" onsubmit="return false;">
    @csrf
    @if($dados['tipo'] !== 'pedido')
        {!! Form::hidden('fornecedor_cnpj_cpf', $dados['fornecedor_cnpj_cpf'], ['id' => 'fornecedor_cnpj_cpf']) !!}
    @endif
    {!! Form::hidden('id_necessidade_compras', $dados['id_necessidade_compras'], ['id' => 'id_necessidade_compras']) !!}
    {!! Form::hidden('tipo', $dados['tipo'], ['id' => 'tipo']) !!}
    {!! Form::hidden('todos', 'false', ['id' => 'todos']) !!}
    {!! Form::hidden('volume_produtos', 1, ['id' => 'volume_produtos']) !!}
    <div class="form-row">
        @if($dados['tipo'] === 'pedido')
        <div class="form-group col-sm-12">
            {{ Form::label('fornecedor_cnpj_cpf', 'Fornecedor', []) }}
            <div class="input-group">
                {!! Form::text('fornecedor_cnpj_cpf', $dados['fornecedor_cnpj_cpf'], ['id' => 'fornecedor_cnpj_cpf', 'class' => 'form-control', 'placeholder' => 'Fornecedor']) !!}
                <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca-geracao" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
            </div>
        </div>
        @endif
        @if($dados['tipo'] === 'servico' || $dados['tipo'] === 'pedido')
        <div class="form-group col-sm-4">
            {{ Form::label('estabelecimento', 'Estabelecimento', []) }}
            {!! Form::select('estabelecimento', $estabelecimentos, 5, ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Estabelecimento', 'disabled']) !!}
        </div>
        <div class="form-group col-sm-2">
            {{ Form::label('data_previsao_entrega', 'Previsão de Entrega', []) }}
            {{ Form::text('data_previsao_entrega', $dados['data_previsao_entrega'], ['id' => 'data_previsao_entrega', 'class' => 'form-control data', 'placeholder' => 'Data Previsão de Entrega DD/MM/AAAA']) }}
        </div>
        @else
        <div class="form-group col-sm-6">
            {{ Form::label('estabelecimento', 'Estabelecimento', []) }}
            {!! Form::select('estabelecimento', $estabelecimentos, 5, ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Estabelecimento', 'disabled']) !!}
        </div>
        @endif
        <div class="form-group col-sm-6">
            {{ Form::label('condicao_pagamento', 'Condição de Pagamentos', []) }}
            <div class="input-group" id="condicao_pagamento_group">
                {!! Form::text('condicao_pagamento', $condicao_pagamento, ['id' => 'condicao_pagamento', 'class' => 'form-control', 'placeholder' => 'Condição de Pagamento']) !!}
                <span class="input-group-addon border rounded-right" id="bt-search-condicao_pagamento" data-route="{{ route("condicoes_pagamento_web.dialog") }}"><i id="bt-view-condicao" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-sm-12">
            {!! Form::textarea("observacao_nota", $observacao, ["id" => "observacao_nota", 'class' => 'form-control', 'maxLength' => '240']) !!}
        </div>
    </div>
    <div class="row">
        <div class="form-group col-sm-12 mt-3">
            {!! Form::checkbox('todos_produtos', '', false, ['id' => 'todos_produtos']) !!}
            @if($dados['tipo'] === 'pedido')
                {{ Form::label('todos_produtos', 'Todos Produtos do mesmo Pedido '.$dados['numero_pedido'], array('class' => 'form-check-label')) }}
            @elseif(empty($estabelecimento))
                {{ Form::label('todos_produtos', 'Todos Produtos do mesmo Fornecedor', array('class' => 'form-check-label')) }}
            @else
                {{ Form::label('todos_produtos', 'Todos Produtos do mesmo Fornecedor do Projeto '.$dados['numero_projeto'].' - '.$dados['nome_projeto'], array('class' => 'form-check-label')) }}
            @endif
        </div>
    </div>
    <div class="content-dialog-table">
        <table class="table table-striped table-not-edit" id="table-filters-produtos">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Produto</th>
                    <th class="tb_date">Unid Padr</th>
                    <th class="tb_number">Necessidade Padr</th>
                    <th class="tb_date">Unid Compra</th>
                    <th class="tb_number">Fator de Conv</th>
                    <th class="tb_number">Necessidade Compra</th>
                    <th class="tb_number">Custo Unitário Compra</th>
                    <th class="tb_number">Quantidade Compra</th>
                    <th class="tb_number">Valor Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><div><div>
                        <a href="#" data-id="{{ $dados['id_ficha_tecnica'] }}" data-toggle="tooltip" data-placement="top" title="" onclick="showModalDetalhes($(this))">{{ $dados['codigo'] }}</a>
                        
                    </div></div></td>
                    <td>
                        <div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $dados['descricao'] }}'>{{ $dados['descricao'] }}</div></div>
                    </td>
                    <td>{{ $dados['unidade'] }}</td>
                    <td class="tb_number">{{ $dados['necessidade'] }}</td>
                    <td>
                        {{ $dados['unidade_compra'] }}
                        {!! Form::hidden('unidade_compra-'.$dados['necessidade_compras'], $dados['unidade_compra'], ['id' => 'unidade_compra-'.$dados['necessidade_compras']]) !!}
                    </td>
                    <td class="tb_number">{{ $dados['fator_conversao'] }}</td>
                    <td class="tb_number">{{ $dados['necessidade_compra'] }}</td>
                    <td class="tb_number">
                        <input style="height: inherit;" data-teste="256" id="valor_unitario-{{ $dados['necessidade_compras'] }}" name="valor_unitario-{{ $dados['necessidade_compras'] }}" type="text" class="form-control text-right moeda" value="{{ $dados['valor_unitario'] }}" maxlength="8" data-necessidade_compras="{{ $dados['necessidade_compras'] }}" onkeyup="calculo($(this))">     
                        <input id="valor_unitario_original-{{ $dados['necessidade_compras'] }}" name="valor_unitario_original-{{ $dados['necessidade_compras'] }}" type="hidden" value="{{ $dados['valor_unitario'] }}" autocomplete="off">
                    </td>
                    <td class="tb_number">
                        @if(empty($estabelecimento))
                            <input style="height: inherit;" id="quantidade-{{ $dados['necessidade_compras'] }}" name="quantidade-{{ $dados['necessidade_compras'] }}" type="text" class="form-control text-right moeda" value="{{ $dados['quantidade'] }}" maxlength="8" data-necessidade_compras="{{ $dados['necessidade_compras'] }}" onkeyup="calculo($(this))">  
                        @else
                            <input style="height: inherit;" id="quantidade-{{ $dados['necessidade_compras'] }}" name="quantidade-{{ $dados['necessidade_compras'] }}" type="text" class="form-control text-right moeda" value="{{ $dados['quantidade'] }}" maxlength="8" data-necessidade_compras="{{ $dados['necessidade_compras'] }}" onkeyup="calculo($(this))" disabled>
                        @endif
                            <input id="quantidade_original-{{ $dados['necessidade_compras'] }}" name="quantidade_original-{{ $dados['necessidade_compras'] }}" type="hidden" value="{{ $dados['quantidade'] }}" autocomplete="off"> 
                    </td>
                    <td class="tb_number">
                        <input style="height: inherit;" id="valor_total-{{ $dados['necessidade_compras'] }}" name="valor_total-{{ $dados['necessidade_compras'] }}" type="text" class="form-control text-right moeda" value="{{ $dados['total'] }}" disabled>
                        <input id="valor_total_original-{{ $dados['necessidade_compras'] }}" name="valor_total_original-{{ $dados['necessidade_compras'] }}" type="hidden" value="{{ $dados['total'] }}" autocomplete="off"> 
                    </td>
                    
                </tr>
            </tbody>
            <tfoot>
            </tfoot>
        </table>
    </div>
    <br>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Gerar Pedido Compra', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>


<script>
    array_id_necessidade_compras = ["{{ $dados['necessidade_compras'] }}"];
    $(document).ready(function () {
        initTable();

        form_geracao = $(document).find('#form_geracao_pedido_compras');

        form_geracao.find("#condicao_pagamento").autocomplete(optionsAutoCompleteCondicoes());
        form_geracao.find("#fornecedor_cnpj_cpf").autocomplete(optionsAutoCompleteFornecedorGeracao(form_geracao));

        form_geracao.find("#bt-view-condicao").off("click");
        form_geracao.find("#bt-view-condicao").on("click", function(event){
            event.stopPropagation();
            modalCondicao($(this).parent(), form_geracao);
            return false;
        });

        form_geracao.find("#todos_produtos").on('click', function(){
            carregarTabela(form_geracao);
        });

        form_geracao.find("#btn-salvar").on('click', function(){
            gerarPedidoCompra(form_geracao.serialize());
        });

        $(document).find(".moeda").maskMoney({thousands:'', decimal:','});
        form_geracao.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
            startDate: new Date(),
        });
        form_geracao.find('.data').mask('00/00/0000');

        form_geracao.find("#bt-search-fornecedor-busca-geracao").on("click", function(){
            showModalFornecedorGeracao($(this).data("route"), "Lista de Fornecedores", form_geracao);
        });
    });

    function initTable(){
        table_filters_produto_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "50vh",
            "autoWidth": false,
            "drawCallback": function(settings) {
                $(document).find('.detalhe_produto').tooltip({
                    container: 'body',
                    html: true,
                    show: true,
                    trigger: 'manual'
                });
            },
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Produto inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Produto inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number", width: "150px" },
                { "class": "tb_date", targets: "tb_date", width: "100px" },
            ]
        };
        table_produto = '';
        table_produto = $(document).find('#table-filters-produtos').DataTable(table_filters_produto_options);
        table_produto.draw();
        setTimeout(function(){
            table_produto.draw(false);
        }, 200);
    }

    function carregarTabela(form_geracao){
        id_necessidade_compras = form_geracao.find("#id_necessidade_compras").val();
        fornecedor_cnpj_cpf = form_geracao.find("#fornecedor_cnpj_cpf").val();
        tipo = form_geracao.find("#tipo").val();
        todos_produtos = form_geracao.find("#todos_produtos").prop('checked');

        $.ajax({
            url: '{{ route('necessidade_compras.get_produto_por_fornecedor') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id_necessidade_compras: id_necessidade_compras,
                fornecedor_cnpj_cpf: fornecedor_cnpj_cpf,
                tipo: tipo,
                todos_produtos: todos_produtos
            },
            success: function (data){
                array_id_necessidade_compras = [];
                produtos = [];
                table_produto.clear().draw();
                for (var produto in data.response){
                    var field = [
                        linkFichaTecnica(data.response[produto]),
                        ajusteTamanhoTable(data.response[produto].descricao),
                        data.response[produto].unidade,
                        data.response[produto].necessidade,
                        inputUnidadeCompra(data.response[produto]),
                        data.response[produto].fator_conversao,
                        data.response[produto].necessidade_compra,
                        inputValorUnitario(data.response[produto]),
                        inputQuantidade(data.response[produto]),
                        inputValorTotal(data.response[produto]),
                    ];

                    array_id_necessidade_compras.push(data.response[produto].necessidade_compras);

                    produtos.push(field);
                }
                table_produto.rows.add(produtos).draw();

                form_geracao.find("#volume_produtos").val(produtos.length);

                $(document).find(".moeda").maskMoney({thousands:'', decimal:','});
            }
        });
    }

    function inputUnidadeCompra($value){
        html = $value.unidade_compra +
                "<input id=\"unidade_compra-"+$value.necessidade_compras+"\" name=\"unidade_compra-"+$value.necessidade_compras+"\" type=\"hidden\" value=\""+$value.unidade_compra+"\" autocomplete=\"off\">";

        return html;
    }

    function inputValorUnitario($value){
        html = "<input style=\"height: inherit;\" id=\"valor_unitario-"+$value.necessidade_compras+"\" name=\"valor_unitario-"+$value.necessidade_compras+"\" type=\"text\" class=\"form-control text-right moeda\" value=\""+$value.valor_unitario+"\"  maxlength=\"8\" data-necessidade_compras=\""+$value.necessidade_compras+"\" onkeyup=\"calculo($(this))\">";
        html = html + "<input id=\"valor_unitario_original-"+$value.necessidade_compras+"\" name=\"valor_unitario_original-"+$value.necessidade_compras+"\" type=\"hidden\" value=\""+$value.valor_unitario+"\" autocomplete=\"off\">";
        return html;
    }

    function inputQuantidade($value){
        @if(empty($estabelecimento))
            html = "<input style=\"height: inherit;\" id=\"quantidade-"+$value.necessidade_compras+"\" name=\"quantidade-"+$value.necessidade_compras+"\" type=\"text\" class=\"form-control text-right moeda\" value=\""+$value.quantidade+"\"  maxlength=\"8\" data-necessidade_compras=\""+$value.necessidade_compras+"\" onkeyup=\"calculo($(this))\">";
        @else
            html = "<input style=\"height: inherit;\" id=\"quantidade-"+$value.necessidade_compras+"\" name=\"quantidade-"+$value.necessidade_compras+"\" type=\"text\" class=\"form-control text-right moeda\" value=\""+$value.quantidade+"\"  maxlength=\"8\" data-necessidade_compras=\""+$value.necessidade_compras+"\" onkeyup=\"calculo($(this))\" disabled>";
        @endif

        html = html + "<input id=\"quantidade_original-"+$value.necessidade_compras+"\" name=\"quantidade_original-"+$value.necessidade_compras+"\" type=\"hidden\" value=\""+$value.quantidade+"\" autocomplete=\"off\">";
        
        return html;
    }

    function inputValorTotal($value){
        html = "<input style=\"height: inherit;\" id=\"valor_total-"+$value.necessidade_compras+"\" name=\"valor_total-"+$value.necessidade_compras+"\" type=\"text\" class=\"form-control text-right moeda\" value=\""+$value.valor_total+"\"  maxlength=\"8\" disabled>";

        html = html + "<input id=\"valor_total_original-"+$value.necessidade_compras+"\" name=\"valor_total_original-"+$value.necessidade_compras+"\" type=\"hidden\" value=\""+$value.valor_total+"\" autocomplete=\"off\">";

        return html;
    }

    function gerarPedidoCompra(data_form_geracao){
        limparMesagemErroModal();

        id_necessidade_compras = form_geracao.find("#id_necessidade_compras").val();
        fornecedor_cnpj_cpf = form_geracao.find("#fornecedor_cnpj_cpf").val();
        estabelecimento = form_geracao.find("#estabelecimento").val();
        condicao_pagamento = form_geracao.find("#condicao_pagamento").val();
        tipo = form_geracao.find("#tipo").val();
        observacao_nota = form_geracao.find("#observacao_nota").val();
        todos_produtos = form_geracao.find("#todos_produtos").prop('checked');
        data_previsao_entrega = form_geracao.find("#data_previsao_entrega").val();

        var_itens = [];

        array_id_necessidade_compras.forEach(function imprimir(item){
            var dados = [];
            dados.push(item);
            dados.push(form_geracao.find("#valor_unitario-"+item).val());
            dados.push(form_geracao.find("#quantidade-"+item).val());
            dados.push(form_geracao.find("#valor_unitario_original-"+item).val());
            dados.push(form_geracao.find("#quantidade_original-"+item).val());
            dados.push(form_geracao.find("#valor_total_original-"+item).val());
            dados.push(form_geracao.find("#unidade_compra-"+item).val());
            var_itens.push(dados);
        });

        $.ajax({
            url: "{{ route('necessidade_compras.gerar_pedido_compra') }}", 
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id_necessidade_compras: id_necessidade_compras,
                fornecedor_cnpj_cpf: fornecedor_cnpj_cpf,
                estabelecimento: estabelecimento,
                condicao_pagamento: condicao_pagamento,
                todos_produtos: todos_produtos,
                tipo: tipo,
                observacao_nota: observacao_nota,
                var_itens: var_itens,
                data_previsao_entrega: data_previsao_entrega,
            },
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_geracao).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());

                message("Atenção", "Pedido de Compras gerado com sucesso!");
            },
            error: function(callback){
                var dados = callback.responseJSON;
                if(dados.message != ''){
                    message("Atenção", dados.message);
                }
                mensagemErroModal(dados);
            }
        });
    }

    function modalCondicao($this, form_geracao){
        $.ajax({
            url: $this.data('route'),
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', estabelecimento: $(document).find('#estabelecimento').val()},
            success: function(data){
                $(document).find("#modal_busca_condicao").remove();
                createModal('modal_busca_condicao', "Busca de condição de Pagamento", data, 'modal-lg');
                var modal = $(document).find("#modal_busca_condicao");
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(){
                            returnDadosCondicao($(this), form_geracao);
                        });
                    });
                });
            }
        });
    }
    
    function returnDadosCondicao($dados, form_geracao){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        form_geracao.find("#condicao_pagamento").data('oldvalue', $(document).find("#condicao_pagamento").val());
        form_geracao.find("#condicao_pagamento").val($dados.find("td:eq(1)").text() );
        $(document).find("#modal_busca_condicao").modal("hide");
    }

    function optionsAutoCompleteCondicoes(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.estabelecimento = $(document).find('#estabelecimento').val();
                $.post("{{ route('condicoes_pagamento_web.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_geracao_pedido_necessidade_compras').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#condicao_pagamento").val(ui.item.label);
                return false;
            }
        };
    }

    function limparMesagemErroModal(){      
        var form_geracao = $(document).find('#form_geracao_pedido_compras');
        form_geracao.find('.error-message').remove();
        form_geracao.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroModal(json_error){
        var form_geracao = $("#form_geracao_pedido_compras");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModal(form_geracao, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form_geracao, input, message){
        if(input.localeCompare('condicao_pagamento') == 0){
            var $input = $(form_geracao).find("#bt-search-condicao_pagamento");
            $(form_geracao).find("input[name='condicao_pagamento']").addClass('error-input');
        }else{
            var $input = $(form_geracao).find("input[name='"+input+"'], select[name='"+input+"']");
        }

        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function calculo($value){
        var necessidade_compras = $value.data("necessidade_compras");
        var form_geracao = $(document).find('#form_geracao_pedido_compras');

        var valor_unitario = form_geracao.find("#valor_unitario-"+necessidade_compras).val();
        var quantidade = form_geracao.find("#quantidade-"+necessidade_compras).val();

        var valor_total = Math.round(((valor_unitario.replace(".","").replace(",", ".")) * (quantidade.replace(".","").replace(",", "."))) * 1000) / 1000;

        valor_total = valor_total.toFixed(2);

        form_geracao.find("#valor_total-"+necessidade_compras).val(numberToReal(valor_total));
    }

    function numberToReal(valor) {
        var numero = valor.split('.');
        numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
        return numero.join(',');
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

    function optionsAutoCompleteFornecedorGeracao(form_geracao){
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_geracao_pedido_necessidade_compras').css('z-index')) + 1));
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
                form_geracao.find("#fornecedor_cnpj_cpf").val(ui.item.label);
                return false;
            }
        };
    }

    function linkFichaTecnica($this){
        html = "";

        if($this.ficha_tecnnica_id !== ''){
            html = '<div><div><a href="#" data-id="'+$this.ficha_tecnnica_id+'" data-toggle="tooltip" data-placement="top" title="" onclick="showModalDetalhes($(this))">'+$this.codigo+'</a></div></div>';
        }else{
            html = $this.codigo;
        }
        
        return html;
    }

    function showModalFornecedorGeracao(url, title, form_geracao){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("fornecedor_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosFornecedorGeracao($(this), form_geracao);
                        });
                    });
                });
            }
        });
    }

    function returnDadosFornecedorGeracao($this, form_geracao){
        if($this.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#fornecedor_search_show").modal("hide");
        form_geracao.find("#fornecedor_cnpj_cpf").val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
    }
    
</script>
@endsection
