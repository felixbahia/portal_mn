@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_digitacao_ajuste_estoque" id="form_digitacao_ajuste_estoque" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-3"> 
            {!! Form::label('estabelecimento', 'Estabelecimento', []) !!}
            {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Selecione o Estabelecimento']) !!}
        </div>
        <div class="form-group col-sm-3">
            {!! Form::label('produto_codigo', 'Código Produto', []) !!}
            <div class="input-group">
                {!! Form::text('produto_codigo', '', ['id' => 'produto_codigo', 'class' => 'form-control']) !!}
                <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class= "form-group col-sm-6">
            {!! Form::label('produto_descricao', 'Produto', []) !!}
            {!! Form::text('produto_descricao', '', ['id' => 'produto_descricao', 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="form-row">
        <div id="totais_ajustes" style="width: 100%;"></div>
    </div>
    <div class="form-row filtro_pecas">
        <div class="content-filter-dialog">	
            <div class="form-row">
                <div class="form-group col-sm-4">
                    {!! Form::label('codigo_peca', 'Peça', []) !!}
                    {!! Form::text('codigo_peca', '', ['id' => 'codigo_peca', 'class' => 'form-control']) !!}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('local_estoque_peca', 'Local de Estoque', []) !!}
                    {!! Form::text('local_estoque_peca', '', ['id' => 'local_estoque_peca', 'class' => 'form-control']) !!}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('ajuste_todas_pecas', 'Ajuste Todas Peças', []) !!}
                    {!! Form::text('ajuste_todas_pecas', '', ['id' => 'ajuste_todas_pecas', 'class' => 'form-control number text-right']) !!}
                </div>
            </div>
            <div class="content-buttons">
                <button name="btn-filterform_pecas" id="btn-filterform_pecas" class="btn-filter">Buscar</button>
                <input name="btn-clearform_pecas" id="btn-clearform_pecas" class="btn-clear" value="Limpar busca" style="width: 135px;text-align: center;"/>
                <button name="btn-create" id="btn-create-alteracao_em_massa_quantidade" class=" btn btn-primary float-right">APLICAR AJUSTE</button>
            </div>
        </div>
    </div>
    <div class="form-row filtro_pecas">
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped" id="table-filters-pecas">
                    <thead>
                        <th>Peça</th>
                        <th>Local de Estoque</th>
                        <th class="tb_number">Quantidade</th>
                        <th class="tb_unidade">Unidade</th>
                        <th>Ajuste</th>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
        
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="form-row filtro_produtos">
        <div class="content-filter-dialog">	
            <div class="form-row">
                <div class="form-group col-sm-8">
                    {!! Form::label('local_de_estoque_codigo', 'Local de Estoque', []) !!}
                    {!! Form::text('local_de_estoque_codigo', '', ['id' => 'local_de_estoque_codigo', 'class' => 'form-control']) !!}
                </div>
                <div class="form-group col-sm-4">
                    {!! Form::label('ajuste_todas_produtos', 'Ajuste Todos Produtos', []) !!}
                    {!! Form::text('ajuste_todas_produtos', '', ['id' => 'ajuste_todas_produtos', 'class' => 'form-control number text-right']) !!}
                </div>
            </div>
            <div class="content-buttons">
                <button name="btn-filterform_produto" id="btn-filterform_produto" class="btn-filter">Buscar</button>
                <input name="btn-clearform_produto" id="btn-clearform_produto" class="btn-clear" value="Limpar busca" style="width: 135px;text-align: center;"/>
                <button name="btn-create" id="btn-create-alteracao_em_massa_quantidade_produto" class=" btn btn-primary float-right">APLICAR AJUSTE</button>
            </div>
        </div>
    </div>
    <div class="form-row filtro_produtos">
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped" id="table-filters-local_de_estoque">
                    <thead>
                        <th>Local Estoque</th>
                        <th class="tb_number">Quantidade</th>
                        <th class="tb_unidade">Unidade</th>
                        <th>Ajuste</th>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
        
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {!! Form::label('motivo', 'Motivo', []) !!}
            {!! Form::select('motivo', $motivos, '', ['id' => 'motivo', 'class' => 'form-control', 'placeholder' => 'Selecione o Motivo']) !!}
        </div>
    </div>

    <div class="form-row">
        <div id="mensagem_erro_peca"></div>
    </div>

    <div class="content-buttons">
        {{ Form::button('Salvar', array('class' => 'btn btn-success float-right', 'id' => 'btn-salvar')) }}
    </div>
</form>
<script>
    var obj_pecas = {};
    var obj_local_estoque = {};
    var temp_obj = {};
    var temp_obj_local_estoque = {};
    var array_pecas = [];
    var array_local_estoque = [];
    var retorno_obj = {};
    var quantidade_total = 0;
    var quantidade_ajuste = 0;
    var ajuste_positivo = 0;
    var ajuste_negativo = 0;

    $(document).ready( function () {
        form_modal = $(document).find("#form_digitacao_ajuste_estoque");

        form_modal.find(".filtro_pecas").hide();
        form_modal.find(".filtro_produtos").hide();

        form_modal.find("#bt-search-produto").off('click');
        form_modal.find("#bt-search-produto").on('click', function(){
            showModalProdutoModal(form_modal);
        });
        form_modal.find("#produto_descricao").autocomplete(optionsAutoComplete(form_modal));

        form_modal.find("#btn-salvar").off('click');
        form_modal.find("#btn-salvar").on('click', function(){
            inserirAjusteEstoque(form_modal);
        });

        form_modal.find("#estabelecimento").off('change');
        form_modal.find("#estabelecimento").on('change', function(){
            liberarBotaoPecas(form_modal);
        });
        form_modal.find("#produto_codigo").off('change');
        form_modal.find("#produto_codigo").on('change', function(){
            liberarBotaoPecas(form_modal);
        });

        form_modal.find("#produto_descricao").off('change');
        form_modal.find("#produto_descricao").on('change', function(){
            if(form_modal.find("#produto_descricao").val() != ''){
                pesquisaProdutoDescricao(form_modal);
                liberarBotaoPecas(form_modal);
            }
        });

        initTable();

        form_modal.find("#btn-filterform_pecas").off('click');
        form_modal.find("#btn-filterform_pecas").on('click', function(){
            filterPecas(form_modal, "");
        });
        form_modal.find("#btn-clearform_pecas").off('click');
        form_modal.find("#btn-clearform_pecas").on('click', function(){
            form_modal.find("#codigo_peca").val("");
            form_modal.find("#local_estoque_peca").val("");
        });
        form_modal.find("#btn-create-alteracao_em_massa_quantidade").off('click');
        form_modal.find("#btn-create-alteracao_em_massa_quantidade").on('click', function(){
            if(form_modal.find("#ajuste_todas_pecas").val() == ""){
                form_modal.find("#ajuste_todas_pecas").val("0,00")
            }
            filterPecas(form_modal, form_modal.find("#ajuste_todas_pecas").val());
        });

        form_modal.find("#btn-filterform_produto").off('click');
        form_modal.find("#btn-filterform_produto").on('click', function(){
            filterProdutos(form_modal, "");
        });
        form_modal.find("#btn-clearform_produto").off('click');
        form_modal.find("#btn-clearform_produto").on('click', function(){
            form_modal.find("#local_de_estoque_codigo").val("");
        });
        form_modal.find("#btn-create-alteracao_em_massa_quantidade_produto").off('click');
        form_modal.find("#btn-create-alteracao_em_massa_quantidade_produto").on('click', function(){
            if(form_modal.find("#ajuste_todas_pecas").val() == ""){
                form_modal.find("#ajuste_todas_pecas").val("0,00")
            }
            filterProdutos(form_modal, form_modal.find("#ajuste_todas_produtos").val());
        });
        
        $(document).find(".number").maskMoney({thousands:'', decimal:','});
    });

    function showModalProdutoModal(form_modal){
        $.ajax({
            url: '{{ route('produto.modal_pesquisa') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
                table_filters_produtos_busca.on('draw', function () {
    
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                        returnDadosProduto($(this), form_modal);
                    });
    
                });
            }
        });
    }

    function returnDadosProduto($dados, form_modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        form_modal.find('#produto_codigo').val($dados.find("td").eq(1).text());
        form_modal.find('#produto_descricao').val($dados.find("td").eq(2).text());
        liberarBotaoPecas(form_modal);
    }

    function optionsAutoComplete(form_modal){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('produto.tecido.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_digitacao_ajuste_estoque').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_modal.find("#produto_descricao").val(ui.item.label);
                form_modal.find("#produto_codigo").val(ui.item.value);
                liberarBotaoPecas(form_modal);
                return false;
            }
        };
    }

    function showModalPecas(form_modal){
        estabelecimento = form_modal.find("#estabelecimento").val();
        produto_codigo = form_modal.find("#produto_codigo").val();
        $.ajax({
            url: '{{ route('produto.ajuste_estoque.digitacao.modal.pecas') }}',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: estabelecimento,
                produto_codigo: produto_codigo,
                obj_pecas: obj_pecas,
            },
            method: 'POST',
            success: function(body){
                var title = 'Peças';
                createModal('modal_pecas', title, body, 'modal-lg');
            }
        });
    }

     function liberarBotaoPecas(form_modal){
        loader();
        obj_pecas = {};
        obj_local_estoque = {};
        temp_obj_local_estoque = {};
        array_pecas = [];
        array_local_estoque = [];
        data_form = form_modal.serialize();
        table_pecas.clear().draw();
        table_local_de_estoque.clear().draw();
        $.ajax({
            url: '{{ route('produto.ajuste_estoque.digitacao.liberar_ajuste')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                limparMesagemErroModal(form_modal);
                if(data.response.produto.nome != ""){
                    form_modal.find("#produto_descricao").val(data.response.produto.nome);
                }
                if(data.response.liberar === true){
                    construcaoTotais(data.response.quantidade);
                    temp_array = [];
                    linhas = [];
                    for (var fields in data.response.lotes){
                        form_modal.find(".filtro_pecas").show();
                        form_modal.find(".filtro_produtos").hide();

                        temp_obj[data.response.lotes[fields].peca] = {
                            produto_lote :  data.response.lotes[fields].produto_lote,
                            peca :  data.response.lotes[fields].peca,
                            local_de_estoque_uuid : data.response.lotes[fields].local_de_estoque_uuid,
                            local_de_estoque_codigo : data.response.lotes[fields].local_de_estoque_codigo,
                            quantidade :  data.response.lotes[fields].quantidade,
                            ajuste :  data.response.lotes[fields].ajuste,
                        };
                        array_pecas.push(data.response.lotes[fields].peca);

                        temp_array = [
                            data.response.lotes[fields].peca,
                            data.response.lotes[fields].local_de_estoque_nome,
                            data.response.lotes[fields].quantidade,
                            data.response.lotes[fields].unidade,
                            inputAjustePeca(data.response.lotes[fields]),
                        ];
        
                        linhas.push(temp_array);
                    }
                    table_pecas.rows.add(linhas).draw();
                    for (var fields in data.response.locais_de_estoque){
                        form_modal.find(".filtro_pecas").hide();
                        form_modal.find(".filtro_produtos").show();
                        temp_obj_local_estoque[data.response.locais_de_estoque[fields].local_de_estoque_codigo] = {
                            produto_codigo : data.response.locais_de_estoque[fields].produto_codigo,
                            local_de_estoque_codigo : data.response.locais_de_estoque[fields].local_de_estoque_codigo,
                            local_de_estoque_uuid : data.response.locais_de_estoque[fields].local_de_estoque_uuid,
                            quantidade : data.response.locais_de_estoque[fields].quantidade,
                            ajuste :  data.response.locais_de_estoque[fields].quantidade,
                        };

                        array_local_estoque.push(data.response.locais_de_estoque[fields].local_de_estoque_codigo);

                        temp_array = [
                            data.response.locais_de_estoque[fields].local_de_estoque_nome,
                            data.response.locais_de_estoque[fields].quantidade,
                            data.response.locais_de_estoque[fields].unidade,
                            inputAjusteLocalEstoque(data.response.locais_de_estoque[fields]),
                        ];

                        linhas.push(temp_array);
                    }

                    table_local_de_estoque.rows.add(linhas).draw();
                    
                    $(document).find(".number").maskMoney({thousands:'', decimal:','});
                }else{
                    form_modal.find(".filtro_pecas").hide();
                    form_modal.find(".filtro_produtos").hide();
                    $(document).find("#totais_ajustes").html("");
                    table_pecas.clear().draw();
                    table_local_de_estoque.clear().draw();
                }
                hide_loader();
            },
            error: function(data){  
                form_modal.find(".filtro_pecas").hide();
                form_modal.find(".filtro_produtos").hide();
                $(document).find("#totais_ajustes").html("");
                table_pecas.clear().draw();
                table_local_de_estoque.clear().draw();

                var dados = data.responseJSON;
                limparMesagemErroModal(form_modal);
                mensagemErroModal(dados, form_modal);
            }
        });
    }

    function inserirAjusteEstoque(form_modal){
        loader();
        estabelecimento = form_modal.find("#estabelecimento").val();
        produto_codigo = form_modal.find("#produto_codigo").val();
        produto_descricao = form_modal.find("#produto_descricao").val();
        motivo = form_modal.find("#motivo").val();

        retorno = {};
        array_pecas.forEach(function imprimir(item){
            if(temp_obj[item]['quantidade'] !== temp_obj[item]['ajuste']){
                retorno[item] = {
                    'produto_lote' : temp_obj[item]['produto_lote'],
                    'peca' : temp_obj[item]['peca'],
                    'local_de_estoque_uuid' : temp_obj[item]['local_de_estoque_uuid'],
                    'local_de_estoque_codigo' : temp_obj[item]['local_de_estoque_codigo'],
                    'quantidade' : temp_obj[item]['quantidade'],
                    'ajuste' : temp_obj[item]['ajuste'],
                }
            }
        });

        obj_pecas = retorno;

        array_local_estoque.forEach(function imprimir(item){
            if(temp_obj_local_estoque[item]['quantidade'] !== temp_obj_local_estoque[item]['ajuste']){
                obj_local_estoque[item] = {
                    'produto_codigo' : temp_obj_local_estoque[item]['produto_codigo'],
                    'local_de_estoque_codigo' : temp_obj_local_estoque[item]['local_de_estoque_codigo'],
                    'local_de_estoque_uuid' : temp_obj_local_estoque[item]['local_de_estoque_uuid'],
                    'quantidade' : temp_obj_local_estoque[item]['quantidade'],
                    'ajuste' : temp_obj_local_estoque[item]['ajuste'],
                }
            }
        });

        $.ajax({
            url: "{{ route('produto.ajuste_estoque.digitacao.adicionar') }}", 
            dataType: 'json',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: estabelecimento,
                produto_codigo: produto_codigo,
                produto_descricao: produto_descricao,
                motivo: motivo,
                obj_pecas: obj_pecas,
                obj_local_estoque: obj_local_estoque,
            },
            method: 'POST',
            success: function(callback){
                $(form_modal).parents('.modal').modal('hide');
                message("Atenção", "Ajuste realizado com sucesso!");
            },
            error: function(callback){
                var dados = callback.responseJSON;
                if(callback.responseJSON.message !== "Campos inválidos"){
                    message("Atenção", callback.responseJSON.message);
                }
                limparMesagemErroModal();
                mensagemErroModal(dados);
            }
        });
    }

    function limparMesagemErroModal(){      
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span, button').removeClass('error-input');
    }

    function mensagemErroModal(json_error){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModal(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form_modal, input, message){
        if(input.localeCompare('produto_codigo') == 0){
            var $input = $(form_modal).find("#bt-search-produto");
            $(form_modal).find("input[name='"+input+"']").addClass('error-input');
        }else if(input.localeCompare('obj_pecas') == 0){
            var $input = $(form_modal).find("#mensagem_erro_peca");
            message = "Não foi feito nenhum ajuste, favor verificar";
        }else if(input.localeCompare('produto_descricao') == 0){
            if(message !== "O campo Produto é obrigatório." && message !=="Produto não encontrado"){
                $(form_modal).find("#produto_descricao").val(message.dados.nome);
                message = message.mensagem;
            }
            var $input = $(form_modal).find("#produto_descricao");
        }else{
            var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"'], div[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function pesquisaProdutoDescricao(){
        form_modal = $(document).find('#form_digitacao_ajuste_estoque');
        descricao = form_modal.find('#produto_descricao').val();
        $.ajax({
            url: '{{ route('produto.pesquisaprodutodescricao')}}',
            data: {
                _token : "{{ csrf_token() }}",
                descricao: descricao
            },
            method: 'POST',
            success: function(callback){
                form_modal.find('#produto_codigo').val(callback.response.codigo_produto);
                form_modal.find('#produto_descricao').val(callback.response.descricao);
            },
            error: function(callback){
                if(form_modal.find('#produto_descricao').val() != ''){
                    form_modal.find('#produto_descricao').val('');
                }
            }
        });
    }

    function initTable(){
        table_filters_pecas_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "26vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Peça Encontrada",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Peça Encontrada",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { targets: 1, width: '250px'},
                { targets: 3, width: '450px'},
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
                { "class": "tb_unidade", targets: "tb_unidade", width: "150px"}
            ]
        };
        table_pecas = '';
        table_pecas = $(document).find('#table-filters-pecas').DataTable(table_filters_pecas_options);
        table_pecas.draw();

        table_filters_local_de_estoque_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "26vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Local de Estoque Encontrada",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Local de Estoque Encontrada",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { targets: 1, width: '250px'},
                { targets: 3, width: '450px'},
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
                { "class": "tb_unidade", targets: "tb_unidade", width: "150px"}
            ]
        };
        table_local_de_estoque = '';
        table_local_de_estoque = $(document).find('#table-filters-local_de_estoque').DataTable(table_filters_local_de_estoque_options);
        table_local_de_estoque.draw();
    }

    function filterPecas(form_modal, ajuste_todas_pecas){
        table_pecas.clear().draw();
        estabelecimento = form_modal.find("#estabelecimento").val();
        produto_codigo = form_modal.find("#produto_codigo").val();
        codigo_peca = form_modal.find("#codigo_peca").val();
        local_estoque_peca = form_modal.find("#local_estoque_peca").val();
        $.ajax({
            url: '{{ route('produto.ajuste_estoque.digitacao.filter_pecas')}}',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: estabelecimento,
                produto_codigo: produto_codigo,
                codigo_peca: codigo_peca,
                ajuste_todas_pecas: ajuste_todas_pecas,
                local_estoque_peca: local_estoque_peca,
            },
            method: 'POST',
            success: function(data){  
                linhas = [];
                for (var fields in data.response.lotes){
                    if(ajuste_todas_pecas != ""){
                        temp_obj[data.response.lotes[fields].peca]['ajuste'] = data.response.lotes[fields].ajuste;
                    }
                    
                    temp_array = [
                        data.response.lotes[fields].peca,
                        data.response.lotes[fields].local_de_estoque_nome,
                        data.response.lotes[fields].quantidade,
                        data.response.lotes[fields].unidade,
                        inputAjustePeca(data.response.lotes[fields]),
                    ];
    
                    linhas.push(temp_array);    
                }

                table_pecas.rows.add(linhas).draw();
                
                $(document).find(".number").maskMoney({thousands:'', decimal:','});

                array_pecas.forEach(function ajusteTotais(item){
                    var ajuste_peca;
                    var quantidade_peca = parseFloat(temp_obj[item]['quantidade'].replace(".","").replace(",", "."));
                    var diferenca;
                    if(temp_obj[item]['ajuste'] == ""){
                        ajuste_peca = 0;
                    }else{
                        ajuste_peca = parseFloat(temp_obj[item]['ajuste'].replace(".","").replace(",", "."));
                    }
        
                    diferenca = ajuste_peca - quantidade_peca;
                    
                    if(diferenca < 0){
                        ajuste_negativo = ajuste_negativo - diferenca;
                    }else{
                        ajuste_positivo = ajuste_positivo + diferenca;
                    }
                });
        
                quantidade_ajuste = quantidade_total + ajuste_positivo - ajuste_negativo;
                atualizacaoDosTotais(quantidade_total, quantidade_ajuste, ajuste_positivo, ajuste_negativo);
            },
            error: function(data){
                var errors = data.responseJSON.error;
                for(var field in errors){

                }
            }
        });
    }

    function filterProdutos(form_modal, ajuste_todas_produtos){
        table_local_de_estoque.clear().draw();
        estabelecimento = form_modal.find("#estabelecimento").val();
        produto_codigo = form_modal.find("#produto_codigo").val();
        local_de_estoque_codigo = form_modal.find("#local_de_estoque_codigo").val();
        $.ajax({
            url: '{{ route('produto.ajuste_estoque.digitacao.filter_produtos')}}',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: estabelecimento,
                produto_codigo: produto_codigo,
                ajuste_todas_produtos: ajuste_todas_produtos,
                local_de_estoque_codigo: local_de_estoque_codigo,
            },
            method: 'POST',
            success: function(data){  
                linhas = [];
                for (var fields in data.response){
                    if(ajuste_todas_produtos != ""){
                        temp_obj_local_estoque[data.response.lotes[fields].local_de_estoque_nome]['ajuste'] = ajuste_todas_produtos;
                    }

                    temp_array = [
                        data.response[fields].local_de_estoque_nome,
                        data.response[fields].quantidade,
                        data.response[fields].unidade,
                        inputAjusteLocalEstoque(data.response[fields]),
                    ];

                    linhas.push(temp_array);
                }
                table_local_de_estoque.rows.add(linhas).draw();

                array_local_estoque.forEach(function ajusteTotais(item){
                    var ajuste_peca;
                    var quantidade_peca = parseFloat(temp_obj_local_estoque[item]['quantidade'].replace(".","").replace(",", "."));
                    var diferenca;
                    if(temp_obj_local_estoque[item]['ajuste'] == ""){
                        ajuste_peca = 0;
                    }else{
                        ajuste_peca = parseFloat(temp_obj_local_estoque[item]['ajuste'].replace(".","").replace(",", "."));
                    }
                 
                    diferenca = ajuste_peca - quantidade_peca;
        
                    if(diferenca < 0){
                        ajuste_negativo = ajuste_negativo - diferenca;
                    }else{
                        ajuste_positivo = ajuste_positivo + diferenca;
                    }
                });
        
                quantidade_ajuste = quantidade_total + ajuste_positivo - ajuste_negativo;
                atualizacaoDosTotais(quantidade_total, quantidade_ajuste, ajuste_positivo, ajuste_negativo);
            },
            error: function(data){
            }
        });
    }

    function inputAjustePeca($value){
        var html = "<input id=\"ajuste-"+$value.peca+"\" class=\"form-control text-right number\" style=\"height: inherit\" data-produto_lote=\""+$value.produto_lote+"\" data-peca=\""+$value.peca+"\" data-valor_original=\""+$value.quantidade+"\" name=\"ajuste-"+$value.peca+"\" type=\"text\" value=\""+temp_obj[$value.peca]['ajuste']+"\" onchange=\"guardarValorPeca($(this))\" autocomplete=\"off\">";

        return html;
    }

    function inputAjusteLocalEstoque($value){
        var html = "<input id=\"ajuste-"+$value.local_de_estoque_codigo+"\" class=\"form-control text-right number\" style=\"height: inherit\" data-local_de_estoque=\""+$value.local_de_estoque_codigo+"\" data-valor_original=\""+$value.quantidade+"\" name=\"ajuste-"+$value.local_de_estoque_codigo+"\" type=\"text\" value=\""+temp_obj_local_estoque[$value.local_de_estoque_codigo]['ajuste']+"\" onchange=\"guardarValorLocalEstoque($(this))\" autocomplete=\"off\">";

        return html;
    }

    function guardarValorPeca($this){
        temp_obj[$this.data("peca")]['ajuste'] = $this.val();
        
        ajuste_positivo = 0;
        ajuste_negativo = 0;

        array_pecas.forEach(function ajusteTotais(item){
            var ajuste_peca;
            var quantidade_peca = parseFloat(temp_obj[item]['quantidade'].replace(".","").replace(",", "."));
            var diferenca;
            if(temp_obj[item]['ajuste'] == ""){
                ajuste_peca = 0;
            }else{
                ajuste_peca = parseFloat(temp_obj[item]['ajuste'].replace(".","").replace(",", "."));
            }

            diferenca = ajuste_peca - quantidade_peca;
            
            if(diferenca < 0){
                ajuste_negativo = ajuste_negativo - diferenca;
            }else{
                ajuste_positivo = ajuste_positivo + diferenca;
            }
        });

        quantidade_ajuste = quantidade_total + ajuste_positivo - ajuste_negativo;
        atualizacaoDosTotais(quantidade_total, quantidade_ajuste, ajuste_positivo, ajuste_negativo);
    }

    function guardarValorLocalEstoque($this){
        temp_obj_local_estoque[$this.data("local_de_estoque")]['ajuste'] = $this.val();
        
        ajuste_positivo = 0;
        ajuste_negativo = 0;

        array_local_estoque.forEach(function ajusteTotais(item){
            var ajuste_peca;
            var quantidade_peca = parseFloat(temp_obj_local_estoque[item]['quantidade'].replace(".","").replace(",", "."));
            var diferenca;
            if(temp_obj_local_estoque[item]['ajuste'] == ""){
                ajuste_peca = 0;
            }else{
                ajuste_peca = parseFloat(temp_obj_local_estoque[item]['ajuste'].replace(".","").replace(",", "."));
            }
         
            diferenca = ajuste_peca - quantidade_peca;

            if(diferenca < 0){
                ajuste_negativo = ajuste_negativo - diferenca;
            }else{
                ajuste_positivo = ajuste_positivo + diferenca;
            }
        });

        quantidade_ajuste = quantidade_total + ajuste_positivo - ajuste_negativo;
        atualizacaoDosTotais(quantidade_total, quantidade_ajuste, ajuste_positivo, ajuste_negativo);
    }

    function construcaoTotais($quantidade){
        var html = "<div class=\"form-row\">"+ 
                        "<div class=\"form-group col-sm-3\">"+
                            "<label><b>Total: "+$quantidade+"</b></label>"+
                        "</div>"+
                        "<div class=\"form-group col-sm-3\">"+
                            "<label><b>Total Ajuste: "+$quantidade+"</b></label>"+
                        "</div>"+
                        "<div class=\"form-group col-sm-3\">"+
                            "<div class=\"positiva-validacao\"><label>Positivo: 0,00</label></div>"+
                        "</div>"+
                        "<div class=\"form-group col-sm-3\">"+
                            "<div class=\"falha-validacao\"><label>Negativo: 0,00</label></div>"+
                        "</div>"+
                    "</div>";

        quantidade_total = parseFloat($quantidade.replace(".","").replace(",", "."));
        quantidade_ajuste = parseFloat($quantidade.replace(".","").replace(",", "."));

        $(document).find("#totais_ajustes").html(html);
    }

    function atualizacaoDosTotais(quantidade_total, quantidade_ajuste, ajuste_positivo, ajuste_negativo){
        var html = "<div class=\"form-row\">"+ 
                        "<div class=\"form-group col-sm-3\">"+
                            "<label><b>Total: "+numberToReal(quantidade_total.toFixed(2))+"</b></label>"+
                        "</div>"+
                        "<div class=\"form-group col-sm-3\">"+
                            "<label><b>Total Ajuste: "+numberToReal(quantidade_ajuste.toFixed(2))+"</b></label>"+
                        "</div>"+
                        "<div class=\"form-group col-sm-3\">"+
                            "<div class=\"positiva-validacao\"><label>Positivo: "+numberToReal(ajuste_positivo.toFixed(2))+"</label></div>"+
                        "</div>"+
                        "<div class=\"form-group col-sm-3\">"+
                            "<div class=\"falha-validacao\"><label>Negativo: "+numberToReal(ajuste_negativo.toFixed(2))+"</label></div>"+
                        "</div>"+
                    "</div>";

        $(document).find("#totais_ajustes").html(html);
    }

    function numberToReal(valor) {
        var numero = valor.split('.');
        numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
        return numero.join(',');
    }
</script>
@endsection