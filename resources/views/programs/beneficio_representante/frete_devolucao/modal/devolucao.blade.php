@extends('layouts.page-dialog')

@section('content')
<ul class="nav nav-tabs">
	<li class="nav-item">
        <a class="nav-link active" id="devolucao-produto-tab" data-toggle="tab" href="#devolucao_produto" role="tab" aria-controls="devolucao_produto" aria-selected="false">Produtos</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="devolucao-cliente-tab" data-toggle="tab" href="#devolucao_cliente" role="tab" aria-controls="devolucao_cliente" aria-selected="false">Clientes</a>
    </li>
</ul>

<div class="tab-content pt-3" id="DevolucaoHeaderContainer">
    <div class="tab-pane show active" id="devolucao_produto" role="tabpanel" aria-labelledby="dados-tab">
        <form action="" name="form_devolucao_produto" id="form_devolucao_produto" onsubmit="return false;">
            @csrf
            {!! Form::hidden('ano_atual', $ano_atual, ['id' => 'ano_atual']) !!}
            {!! Form::hidden('codigo_vendedor', $codigo_vendedor, ['id' => 'codigo_vendedor']) !!}
            <div class="content-filter-dialog">
                <div class="row">
                    <div class="col-lg-2">
                        <div class="input-group">
                            {!! Form::text('codigo_produto', '', ['id' => 'codigo_produto', 'placeholder' => 'Código Produto', 'class' => 'form-control input-label']) !!}
                            <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
                        </div>
                    </div>
                    <div class="col-lg-2">
                        {!! Form::text('descricao_produto', '', ['id' => 'descricao_produto', 'placeholder' => 'Nome Produto', 'class' => 'form-control']) !!}
                    </div>
                    <div class="col-lg-2">
                        {!! Form::text('marca_produto', '', ['id' => 'marca_produto', 'placeholder' => 'Marca', 'class' => 'form-control']) !!}
                    </div>
                    <div class="col-lg-2">
                        {!! Form::text('linha_produto', '', ['id' => 'linha_produto', 'placeholder' => 'Linha', 'class' => 'form-control']) !!}
                    </div>
                    <div class="col-lg-2">
                        {!! Form::text('grupo_produto', '', ['id' => 'grupo_produto', 'placeholder' => 'Grupo', 'class' => 'form-control']) !!}
                    </div>
                    <div class="col-lg-2">
                        {!! Form::text('subgrupo_produto', '', ['id' => 'subgrupo_produto', 'placeholder' => 'Subgrupo', 'class' => 'form-control']) !!}
                    </div>
                </div>
                <br>
                <div class="content-buttons">
                    <button name="btn-filterform_produtos" id="btn-filterform_produtos" class="btn-filter">Buscar</button>
                    <input name="btn-clearform_produtos" id="btn-clearform_produtos" class="btn-clear" value="Limpar busca" style="width: 135px;text-align: center;"/>
                </div>
            </div>
            <div class="content-dialog-table">
                <div class="content-table">
                    <table class="table table-striped" id="table-dialog-devolucao_produtos">
                        <thead>
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Linha</th>
                            <th>Grupo</th>
                            <th>Marca</th>
                            <th class="tb_number">Quantidade</th>
                            <th class="tb_number">Valor</th>
                        </thead>
                        <tbody>
                            @foreach($produtos as $produto)
                                <tr>
                                    <td>{{ $produto['codigo'] }}</td>
                                    <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $produto['descricao'] }}'>{{ $produto['descricao'] }}</div></div></td>
                                    <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $produto['linha'] }}'>{{ $produto['linha'] }}</div></div></td>
                                    <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $produto['grupo'] }}'>{{ $produto['grupo'] }}</div></div></td>
                                    <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $produto['marca'] }}'>{{ $produto['marca'] }}</div></div></td>
                                    <td class="tb_number">{{ $produto['quantidade'] }}</td>
                                    <td class="tb_number">{{ $produto['valor'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="tb_number">Total :</td>
                            <td class="tb_number" id="produto_quantidade">{{ $total_quantidade }}</td>
                            <td class="tb_number" id="produto_valor">{{ $total_valor }}</td>
                        </tfoot>
                    </table>
                </div>
            </div>
        </form>
    </div>
    <div class="tab-pane show" id="devolucao_cliente" role="tabpanel" aria-labelledby="dados-tab">
        <form action="" name="form_devolucao_cliente" id="form_devolucao_cliente" onsubmit="return false;">
            @csrf
            {!! Form::hidden('ano_atual', $ano_atual, ['id' => 'ano_atual']) !!}
            {!! Form::hidden('codigo_vendedor', $codigo_vendedor, ['id' => 'codigo_vendedor']) !!}
            <div class="content-filter-dialog">	
                <div class="row">
                    <div class="col-lg-12">
                        <div class="input-group" id="cod_cliente_group">
                            <input type="text" class="form-control essencial input-label" name="cliente" id="cliente" value="" placeholder="Cliente" maxlength="250" />
                            <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                        </div>
                    </div>
                </div>
                <br>
                <div class="content-buttons">
                    <button name="btn-filterform_clientes" id="btn-filterform_clientes" class="btn-filter">Buscar</button>
                    <input name="btn-clearform_clientes" id="btn-clearform_clientes" class="btn-clear" value="Limpar busca" style="width: 135px;text-align: center;"/>
                </div>
            </div>
            <div class="content-dialog-table">
                <div class="content-table">
                    <table class="table table-striped" id="table-dialog-devolucao_clientes">
                        <thead>
                            <th>Cliente</th>
                            <th class="tb_number">Quantidade Devolvida</th>
                            <th class="tb_number">Valor devolvido</th>
                        </thead>
                        <tbody>
                            @foreach ($clientes as $cliente)
                                <tr>
                                    <td>{{ $cliente['cliente'] }}</td>
                                    <td class="tb_number">{{ $cliente['quantidade'] }}</td>
                                    <td class="tb_number">{{ $cliente['valor'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <td class="tb_number"></td>
                            <td class="tb_number" id="cliente_quantidade">{{ $total_quantidade }}</td>
                            <td class="tb_number" id="cliente_valor">{{ $total_valor }}</td>
                        </tfoot>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    $(document).ready( function () {
        initTable();
        form_modal_devolucao_produto = $(document).find("#form_devolucao_produto"); 

        form_modal_devolucao_produto.find("#marca_produto").autocomplete(optionsAutoCompleteMarca());
        form_modal_devolucao_produto.find("#linha_produto").autocomplete(optionsAutoCompleteLinha());
        form_modal_devolucao_produto.find("#grupo_produto").autocomplete(optionsAutoCompleteGrupo());
        form_modal_devolucao_produto.find("#subgrupo_produto").autocomplete(optionsAutoCompleteSubgrupo());
        form_modal_devolucao_produto.find("#descricao_produto").autocomplete(optionsAutoComplete("nome"));
        
        form_modal_devolucao_produto.find("#bt-search-produto").off('click');
        form_modal_devolucao_produto.find("#bt-search-produto").on('click', function(){
            showModalProduto(form_modal_devolucao_produto);
        });
        form_modal_devolucao_produto.find("#btn-filterform_produtos").off('click');
        form_modal_devolucao_produto.find("#btn-filterform_produtos").on('click', function(){
            filterDialogDevolucaoProduto(form_modal_devolucao_produto);
        });

        form_modal_cliente = $(document).find("#form_devolucao_cliente"); 
        form_modal_cliente.find("#cliente").autocomplete(optionsAutoCompleteCliente());
        form_modal_cliente.find("#bt-search-cliente-busca").off("click");
        form_modal_cliente.find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
            showModalCliente($(this).data("route"), form_modal_cliente);
            return false;
        });
        form_modal_cliente.find("#btn-filterform_clientes").off('click');
        form_modal_cliente.find("#btn-filterform_clientes").on('click', function(){
            filterDialogDevolucaoCliente(form_modal_cliente);
        });
        
        $(document).find("#devolucao-produto-tab").off("click");
        $(document).find("#devolucao-produto-tab").on("click", function(){
            setTimeout(function(){
                table_devolucao_produtos.draw(false);
            }, 100);
        });
        $(document).find("#devolucao-cliente-tab").off("click");
        $(document).find("#devolucao-cliente-tab").on("click", function(){
            setTimeout(function(){
                table_devolucao_clientes.draw(false);
            }, 100);
        });

        setTimeout(function(){
            table_devolucao_produtos.draw(false);
            table_devolucao_clientes.draw(false);
        }, 150);
    });

    function initTable(){
        table_dialog_devolucao_produtos_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "35vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Registro Encontrado",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Registro Encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
            ],
            "order": [[ 5, "desc" ]],
        };
        table_devolucao_produtos = '';
        table_devolucao_produtos = $(document).find('#table-dialog-devolucao_produtos').DataTable(table_dialog_devolucao_produtos_options);
        table_devolucao_produtos.draw();

        table_dialog_devolucao_clientes_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "40vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Registro Encontrado",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Registro Encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
            ],
            "order": [[ 0, "desc" ]],
        };
        table_devolucao_clientes = '';
        table_devolucao_clientes = $(document).find('#table-dialog-devolucao_clientes').DataTable(table_dialog_devolucao_clientes_options);
        table_devolucao_clientes.draw();
    }

    function optionsAutoCompleteMarca(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.marca.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_devolucao').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.draw();
                }, 100);
            }
        };
    }
    
    function optionsAutoCompleteLinha(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.linha.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_devolucao').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.draw();
                }, 100);
            }
        };
    }
    
    function optionsAutoCompleteGrupo(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.grupo.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_devolucao').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.draw();
                }, 100);
            }
        };
    }
    
    function optionsAutoCompleteSubgrupo(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.subgrupo.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_devolucao').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.draw();
                }, 100);
            }
        };
    }
    
    function optionsAutoComplete($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_devolucao').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.draw();
                }, 100);
            }
        };
    }

    function showModalProduto(form_modal_devolucao_produto){
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
                        returnDadosProduto($(this), form_modal_devolucao_produto);
                    });
    
                });
            }
        });
    }
    
    function returnDadosProduto($dados, form_modal_devolucao_produto){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        form_modal_devolucao_produto.find('#codigo_produto').val($dados.find("td").eq(1).text());
        form_modal_devolucao_produto.find('#descricao_produto').val($dados.find("td").eq(2).text());
    }

    function filterDialogDevolucaoProduto(form_modal_devolucao_produto){
        table_devolucao_produtos.clear().draw();
        $(document).find('#produto_quantidade').html("");
            $(document).find('#produto_valor').html("");
        data_form_modal_devolucao_produto = form_modal_devolucao_produto.serialize();
        $.ajax({
            url: '{{ route('beneficio_representante.frete_devolucao.filtro_devolucao_produtos')}}',
            data: data_form_modal_devolucao_produto,
            method: 'POST',
            success: function(data){  
                linhas = [];
            
                for (var index in data.response.produtos){
                    temp_array = [
                        data.response.produtos[index].codigo,
                        ajusteTamanhoTable(data.response.produtos[index].descricao),
                        ajusteTamanhoTable(data.response.produtos[index].linha),
                        ajusteTamanhoTable(data.response.produtos[index].grupo),
                        ajusteTamanhoTable(data.response.produtos[index].marca),
                        data.response.produtos[index].quantidade,
                        data.response.produtos[index].valor,
                    ];
                    linhas.push(temp_array);
                }
                table_devolucao_produtos.rows.add(linhas).draw();
    
                $(document).find('#produto_quantidade').html(data.response.total_quantidade);
                $(document).find('#produto_valor').html(data.response.total_valor);
            },
            error: function(data){

            }
        });
    }

    function filterDialogDevolucaoCliente(form_modal_cliente){
        table_devolucao_clientes.clear().draw();
        data_form_modal_cliente = form_modal_cliente.serialize();
        $.ajax({
            url: '{{ route('beneficio_representante.frete_devolucao.filtro_devolucao_clientes')}}',
            data: data_form_modal_cliente,
            method: 'POST',
            success: function(data){  
                linhas = [];
                
                for (var fields in data.response.clientes){
                    temp_array = [
                        data.response.clientes[fields].cliente,
                        data.response.clientes[fields].quantidade,
                        data.response.clientes[fields].valor,
                    ];
                    linhas.push(temp_array)
                }
                table_devolucao_clientes.rows.add(linhas).draw();

                $(document).find('#cliente_quantidade').html(data.response.total_quantidade);
                $(document).find('#cliente_valor').html(data.response.total_valor);

            },
            error: function(data){

            }
        });
    }

    function optionsAutoCompleteCliente(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_devolucao').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum cliente encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#cliente").val(ui.item.label);
                return false;
            }
        }
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";
    
        return $html;
    }

    function showModalCliente(url, form_modal_cliente){
        var title = "Busca de Clientes";
        esconderPopoverTooltip();
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                $(document).find('#cliente_searsh_show').remove();
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(event){
                            returnDadosCliente($(this), event, form_modal_cliente);
                        });
                    });
                });
            }
        });
    }

    function returnDadosCliente($dados, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        form_modal_cliente.find("#cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
    }
    
</script>
@endsection