@extends('layouts.app')

@section('content-filter')
<label id="verificacao_atualizacao" name="verificacao_atualizacao">{!! $horario !!}</label>
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-6">
            <div class="input-group">
                {{ Form::text('fornecedor', '', ['id' => 'fornecedor', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Fornecedor', 'maxlength' => '200']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route('fornecedor.busca.index') }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="input-group">
                {{ Form::text('grupo', '', ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupos']) }}
            </div>
        </div>
        <div class="col-lg-3">
            <div class="input-group">
                {{ Form::text('subgrupo', '', ['id' => 'subgrupo', 'class' => 'form-control', 'placeholder' => 'Subgrupos']) }}
            </div>
        </div>
        <div class="col-lg-3">
            <div class="input-group">
                {{ Form::text('codigo', '', ['id' => 'codigo', 'class' => 'form-control', 'placeholder' => 'Código']) }}
            </div>
        </div>
        <div class="col-lg-3">
            <div class="input-group">
                {{ Form::text('linha', '', ['id' => 'linha', 'class' => 'form-control', 'placeholder' => 'Linhas']) }}
            </div>
        </div>
        <div class="col-lg-3">
            <div class="input-group">
                {{ Form::text('marca', '', ['id' => 'marca', 'class' => 'form-control', 'placeholder' => 'Marcas']) }}
            </div>
        </div>
        <div class="col-lg-3">
            <div class="input-group">
                {{ Form::select('agrupar', $agrupar, '', ['id' => 'agrupar', 'class' => 'form-control', 'placeholder' => 'Agrupar por']) }}
            </div>
        </div>
    </div>
    <div class="content-buttons row">
        <div class="col-xs-12 col-sm-6">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </div>
</form>
@endsection
@section('content')
    <div class="content-table tabela_fornecedor">
        <div class="tabela_fornecedor">
            <table class="table table-striped" id="table_estoque_terceiros">
                <thead>
                    <tr>
                        <th class="fornecedor">Fornecedor</th>
                        <th class="number_format kg">Kg</th>
                        <th class='number_format metros'>Metros </th>
                        <th class='number_format metros'>Outras Unidades </th>
                        <th class='number_format custo'>Custo Médio Contábil (Portal)</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong><h5>Total:</h5></strong></td>
                        <td id="peso_total" class="number_format"></td>
                        <td id="metros_total" class="number_format"></td>
                        <td id="outrasunidades_total" class="number_format"></td>
                        <td id="custo_contabil_portal_total" class="number_format"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="content-table tabela_produto">
        <div class="tabela_produto">
            <table class="table table-striped" id="table_estoque_terceiros_produto">
                <thead>
                    <tr>
                        <th class="fornecedor">Produto</th>
                        <th class="fornecedor">Código</th>
                        <th class="fornecedor">Grupo</th>
                        <th class="fornecedor">Linha</th>
                        <th class="fornecedor">Marca</th>
                        <th class="number_format kg">Qtd. Portal</th>
                        <th class='number_format metros'>Qtd. Nasajon </th>
                        <th class='number_format metros'>Diferença</th>
                        <th class='number_format custo'>Custo Médio Contábil (Portal)</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><strong><h5>Total:</h5></strong></td>
                        <td id="peso_total_portal" class="number_format"></td>
                        <td id="metros_total_nasajon" class="number_format"></td>
                        <td id="outrasunidades_total_diferenca" class="number_format"></td>
                        <td id="custo_contabil_portal_total_produto" class="number_format"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
@section('script-footer')
    $(document).ready(function(){
        form = $(document).find('#form_filter');
        form.find("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });
        $(document).find("#grupo").autocomplete(optionsAutoComplete("grupo"));
        $(document).find("#subgrupo").autocomplete(optionsAutoComplete("subgrupo"));
        $(document).find("#linha").autocomplete(optionsAutoComplete("linha"));
        $(document).find("#marca").autocomplete(optionsAutoComplete("marca"));

        $(document).find("#fornecedor").val('');
        $(document).find("#fornecedor").autocomplete(optionsAutoCompleteFornecedor());
        $(document).find("#bt-search-fornecedor-busca").on("click", function(){
            showModalFornecedor($(this).data("route"), "Lista de Fornecedores");
        });
        
        table_estoque_terceiros = $('#table_estoque_terceiros').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": true,
            "orderMulti": false,
            "pageLength": 15,
            "autoWidth": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'pdfHtml5',
                    text: ' ',
                    footer: true,
                    exportOptions: {
                        columns: ':visible',
                        modifier: {
                            page: 'current'
                        }
                    }
                },
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
                                
                                if(column === 1 || column === 2 || column === 3){
                                    if(data != ''){
                                        
                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 4);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }else if(column === 4){
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
                            footer: function(data, column) {

                             
                                if(column === 1 || column === 2 || column === 3){

                                    if(data != ''){

                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 4);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                              }else if(column === 4){
                                if(data != ''){
                                    
                                    numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = '';
                                }
                            }
                              data = $('<p>' + data + '</p>').text();
                              return data;
                            }
                        }
                    },
                },
            ],
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
                    "targets": 'number_format',
                    className: 'number_format'
                },
                {
                    "targets": 'fornecedor',
                    'width': '60px'
                },
                {
                    "targets": 'codigo',
                    'width': '4px'
                },
                {
                    "targets": 'kg',
                    'width': '4px'
                },
                {
                    "targets": 'metros',
                    'width': '4px'
                },
                {
                    "targets": 'custo',
                    'width': '4px'
                }
            ]
        });

        table_estoque_terceiros_produto = $('#table_estoque_terceiros_produto').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": true,
            "orderMulti": false,
            "pageLength": 15,
            "autoWidth": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'pdfHtml5',
                    text: ' ',
                    footer: true,
                    orientation:'landscape',
                    customize : function(doc){
                        doc.pageMargins = [12,12,12,12]
                    }
                },
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
                                
                                if(column === 5 || column === 6 || column === 7){
                                    if(data != ''){
                                        
                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 4);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }else if(column === 8){
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
                            footer: function(data, column) {

                             
                                if(column === 5 || column === 6 || column === 7){

                                    if(data != ''){

                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 4);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                              }else if(column === 8){
                                if(data != ''){
                                    
                                    numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = '';
                                }
                            }
                              data = $('<p>' + data + '</p>').text();
                              return data;
                            }
                        }
                    },
                },
            ],
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
                    "targets": 'number_format',
                    className: 'number_format'
                },
                {
                    "targets": 'fornecedor',
                    'width': '60px'
                },
                {
                    "targets": 'codigo',
                    'width': '4px'
                },
                {
                    "targets": 'kg',
                    'width': '4px'
                },
                {
                    "targets": 'metros',
                    'width': '4px'
                },
                {
                    "targets": 'custo',
                    'width': '4px'
                }
            ]
        });

        form.find("#agrupar").on("change", function(){
            if(form.find("#agrupar").val() == 'fornecedor'){
                $('.tabela_fornecedor').show();
                $('.tabela_produto').hide();
            }else if(form.find("#agrupar").val() == 'produto'){
                $('.tabela_fornecedor').hide();
                $('.tabela_produto').show();
            }else {
                $('.tabela_fornecedor').show();
                $('.tabela_produto').hide();
            }
        });

        table_estoque_terceiros.on('draw', function () {
            $(document).find(".bt-view-produto").off("click");
            $(document).find(".bt-view-produto").on("click", function(event){
                event.stopPropagation();
                showModalProduto($(this));
            });
        });

        if(form.find("#agrupar").val() == 'fornecedor'){
            $('.tabela_fornecedor').show();
            $('.tabela_produto').hide();
        }else if(form.find("#agrupar").val() == 'produto'){
            $('.tabela_fornecedor').hide();
            $('.tabela_produto').show();
        }else {
            $('.tabela_fornecedor').show();
            $('.tabela_produto').hide();
        }
        
    });

    function filterAjax(data_form){

        $(document).find("#custo_contabil_portal_total").html('');
        $(document).find("#peso_total").html('');
        $(document).find("#metros_total").html('');
        $(document).find("#outrasunidades_total").html(''); 

        estabelecimentos = $("#estabelecimento_filtro").val();
        filterClear();
        limparMesagemErroModal(form);
        if(form.find("#agrupar").val() == 'fornecedor'){
            $.ajax({
                method: 'POST',
                dataType: 'json',
                url: "{{ route('estoque_poder_terceiro.filtro')}}",
                data: data_form,
                success: function(callback){
                    filterClear();
                    var fields_consulta = [];
                    for(var field in callback.response.retorno){
                        var temp_field = [
                            createBtViewProdutos(callback.response.retorno[field]),
                            callback.response.retorno[field].kg,
                            callback.response.retorno[field].metros,
                            callback.response.retorno[field].outras_unidades,
                            callback.response.retorno[field].custo_medio_contabil_portal,
                        ];
                        fields_consulta.push(temp_field);
                    }
                    $(document).find("#custo_contabil_portal_total").html(callback.response.total.custo_medio_contabil_portal);
                    $(document).find("#peso_total").html(callback.response.total.KG);
                    $(document).find("#metros_total").html(callback.response.total.metros);
                    $(document).find("#outrasunidades_total").html(callback.response.total.outras_unidades);
                    table_estoque_terceiros.rows.add(fields_consulta).draw().nodes();
                },
                error: function(error){
                    hide_loader();
                    var dados = error.responseJSON;
                    mensagemErroModal(form, dados);
                }
            });
        }else{
            $.ajax({
                method: 'POST',
                dataType: 'json',
                url: "{{ route('estoque_poder_terceiro.filtro_produto')}}",
                data: data_form,
                success: function(callback){
                    filterClear();
                    var fields_consulta = [];
                    for(var field in callback.response.retorno){
                        var temp_field = [
                            ajusteTamanhoTable(callback.response.retorno[field].link_movimento),
                            createBtViewFornecedor(callback.response.retorno[field]),
                            ajusteTamanhoTable(callback.response.retorno[field].grupo),
                            ajusteTamanhoTable(callback.response.retorno[field].linha),
                            ajusteTamanhoTable(callback.response.retorno[field].marca),
                            callback.response.retorno[field].portal,
                            callback.response.retorno[field].nasajon,
                            callback.response.retorno[field].diferenca,
                            callback.response.retorno[field].custo_medio_contabil_portal,
                        ];
                        fields_consulta.push(temp_field);
                    }
                    $(document).find("#custo_contabil_portal_total_produto").html(callback.response.total.custo_medio_contabil_portal);
                    $(document).find("#peso_total_portal").html(callback.response.total.portal);
                    $(document).find("#metros_total_nasajon").html(callback.response.total.nasajon);
                    $(document).find("#outrasunidades_total_diferenca").html(callback.response.total.diferenca);
                    table_estoque_terceiros_produto.rows.add(fields_consulta).draw().nodes();
                },
                error: function(error){
                    hide_loader();
                    var dados = error.responseJSON;
                    mensagemErroModal(form, dados);
                }
            });
        }
    }

    function filterClear(){
        table_estoque_terceiros.clear().draw();
        table_estoque_terceiros_produto.clear().draw();
    }

    function createBtViewProdutos($this){
        var html = "<a href=\"#\" data-route=\"{{ route('estoque_poder_terceiro.modal.produtos') }}\" data-title='Produtos por Fornecedor - \"" + $this.fornecedor + "\"' class='bt-view-produto' data-filter=\""+$this.filtro+"\">"+
            "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + $this.fornecedor + "''>" + $this.fornecedor + "</div></div>"+
            "</a>"
        return html;
    }

    function showModalProduto($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var estabelecimento_prods = $($this).data('estabelecimento_prods');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filtro: filter},
            method: 'POST',
            success: function(body){
                createModal("model_analise_produto", title, body, 'modal-lg');
            }
        });
    }

    function optionsAutoComplete($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
        };
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

    function limparMesagemErroModal(form){      
        form.find('.error-message').remove();
        form.find('input, select, span, div').removeClass('error-input');
    }

    function mensagemErroModal(form, json_error){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModal(form, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form, input, message){
        var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

    function showModalMovimentoEstoqueProduto($this){
        var url = $($this).data("url");
        var $estabel = $($this).data("estabel");
        var $codigo = $($this).data("codigo");
        var $inicial = $($this).data("inicial");
        var $fim = $($this).data("fim");
        var title = $($this).data("title");
        var $fornecedor_codigo = $($this).data("fornecedor_codigo");
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}", 
                estabel: $estabel,
                codigo: $codigo,
                inicial: $inicial,
                fim: $fim,
                fornecedor_codigo: $fornecedor_codigo
            },
            success: function(body){
                createModal('modal_movimento_estoque', title, body, "modal-lg");
                var modal = $("#modal_movimento_estoque");
            }
        });
    }

    function createBtViewFornecedor($this){
        var html = "<a href=\"#\" data-route=\"{{ route('estoque_poder_terceiro.modal.fornecedor') }}\" data-title='Fornecedor - Produto: " + $this.produto + " - " + $this.codigo + "' class='bt-view-produto' data-filter=\""+$this.filtro+"\" data-codigo=\""+$this.codigo+"\" onclick='showModalFornecedorProduto($(this))''>"+
            "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + $this.codigo + "''>" + $this.codigo + "</div></div>"+
            "</a>"
        return html;
    }

    function showModalFornecedorProduto($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var codigo = $($this).data("codigo");
        var title = $($this).data('title');
        var estabelecimento_prods = $($this).data('estabelecimento_prods');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filtro: filter, codigo: codigo},
            method: 'POST',
            success: function(body){
                createModal("model_analise_produto", title, body, 'modal-lg');
            }
        });
    }
@endsection
