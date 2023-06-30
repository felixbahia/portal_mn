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
            {!! Form::hidden('data_escolhida_inicial', $data_escolhida_inicial, ['id' => 'data_escolhida_inicial']) !!}
            {!! Form::hidden('data_escolhida_final', $data_escolhida_final, ['id' => 'data_escolhida_final']) !!}
            {!! Form::hidden('id_unidade_negocio', $id_unidade_negocio, ['id' => 'id_unidade_negocio']) !!}
            <div class="content-filter-dialog">
                @if(empty($codigo_vendedor)) 
                    <div class="row">
                        <div class="col-lg-2">
                            {!! Form::select('tipo', $tipo_usuarios, '', ['id' => 'tipo', 'class' => 'form-control']) !!}
                        </div>
                        <div class="col-lg-2">
                            {!! Form::select('equipe', $unidades_negocios, '', ['id' => 'equipe', 'class' => 'form-control', 'placeholder' => 'Todas as Equipes']) !!}
                        </div>
                        <div class="col-lg-2">
                            {!! Form::select('vendedor', $representantes,'', ['id' => 'vendedor', 'class' => 'form-control', 'placeholder' => 'Todos os Vendedores']) !!}
                        </div>
                        <div class="col-lg-2">
                            {!! Form::select('regiao', $regioes, '', ['id' => 'regiao', 'class' => 'form-control', 'placeholder' => 'Todas as Regiões']) !!}
                        </div>
                        <div class="col-lg-1">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_todos" value="" checked/>
                                <label class="form-check-label" for="marca_todos">Todos</label>
                            </div>
                        </div>
                        <div class="col-lg-1">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_nacional" value="nacional" />
                                <label class="form-check-label" for="marca_nacional">Nacional</label>
                            </div>
                        </div>
                        <div class="col-lg-1">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_importado" value="importado" />
                                <label class="form-check-label" for="marca_importado">Importado</label>
                            </div>
                        </div>
                    </div>
                @else
                    {!! Form::hidden('vendedor', $codigo_vendedor, ['id' => 'vendedor']) !!}
                    <div class="row">
                        <div class="col-lg-2">
                            {!! Form::select('regiao', $regioes, '', ['id' => 'regiao', 'class' => 'form-control', 'placeholder' => 'Todas as Regiões']) !!}
                        </div>
                        <div class="col-lg-1">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_todos" value="" checked/>
                                <label class="form-check-label" for="marca_todos">Todos</label>
                            </div>
                        </div>
                        <div class="col-lg-1">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_nacional" value="nacional" />
                                <label class="form-check-label" for="marca_nacional">Nacional</label>
                            </div>
                        </div>
                        <div class="col-lg-1">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_importado" value="importado" />
                                <label class="form-check-label" for="marca_importado">Importado</label>
                            </div>
                        </div>
                    </div>
                @endif
                <br>
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
                            @foreach($produtos as $unidade_negocio)
                                @foreach ($unidade_negocio as $produto)
                                    <tr>
                                        <td>{{ $produto['codigo'] }}</td>
                                        <td>{{ $produto['descricao'] }}</td>
                                        <td>{{ $produto['linha'] }}</td>
                                        <td>{{ $produto['grupo'] }}</td>
                                        <td>{{ $produto['marca'] }}</td>
                                        <td class="tb_number">{{ $produto['quantidade'] }}</td>
                                        <td class="tb_number">{{ $produto['valor'] }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <tfoot>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="tb_number">Total :</td>
                            <td class="tb_number" id="produto_quantidade_devolvida">{{ $quantidade_total }}</td>
                            <td class="tb_number" id="produto_valor_devolvido">{{ $valor_total }}</td>
                        </tfoot>
                    </table>
                </div>
            </div>
        </form>
    </div>
    <div class="tab-pane show" id="devolucao_cliente" role="tabpanel" aria-labelledby="dados-tab">
        <form action="" name="form_devolucao_cliente" id="form_devolucao_cliente" onsubmit="return false;">
            @csrf
            {!! Form::hidden('data_escolhida_inicial', $data_escolhida_inicial, ['id' => 'data_escolhida_inicial']) !!}
            {!! Form::hidden('data_escolhida_final', $data_escolhida_final, ['id' => 'data_escolhida_final']) !!}
            {!! Form::hidden('filtro', $filtro, ['filtro' => 'filtro']) !!}
            <div class="content-filter-dialog">	
                <div class="row">
                    <div class="col-lg-12">
                        <div class="input-group" id="cod_cliente_group">
                            {{ Form::text('cliente', '', ['id' => 'cliente', 'class' => 'form-control essencial input-label', 'placeholder' => 'Cliente']) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-cliente" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    @if(empty($codigo_vendedor))
                    <div class="col-lg-2">
                        {!! Form::select('tipo', $tipo_usuarios, '', ['id' => 'tipo', 'class' => 'form-control']) !!}
                    </div>
                    @endif
                    @if(empty($id_unidade_negocio))
                    <div class="col-lg-2">
                        {!! Form::select('equipe', $unidades_negocios, '', ['id' => 'equipe', 'class' => 'form-control', 'placeholder' => 'Todas as Equipes']) !!}
                    </div>
                    @else
                    {!! Form::hidden('equipe', $id_unidade_negocio, ['id' => 'equipe']) !!}
                    @endif
                    @if(empty($codigo_vendedor))
                    <div class="col-lg-2">
                        {!! Form::select('vendedor', $representantes,'', ['id' => 'vendedor', 'class' => 'form-control', 'placeholder' => 'Todos os Vendedores']) !!}
                    </div>
                    @else
                    {!! Form::hidden('vendedor', $codigo_vendedor, ['id' => 'vendedor']) !!}
                    @endif
                    <div class="col-lg-2">
                        {!! Form::select('regiao', $regioes, '', ['id' => 'regiao', 'class' => 'form-control', 'placeholder' => 'Todas as Regiões']) !!}
                    </div>
                    <div class="col-lg-1">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_todos" value="" checked/>
                            <label class="form-check-label" for="marca_todos">Todos</label>
                        </div>
                    </div>
                    <div class="col-lg-1">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_nacional" value="nacional" />
                            <label class="form-check-label" for="marca_nacional">Nacional</label>
                        </div>
                    </div>
                    <div class="col-lg-1">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_importado" value="importado" />
                            <label class="form-check-label" for="marca_importado">Importado</label>
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
                            @foreach ($clientes as $unidade_negocio)
                                @foreach ($unidade_negocio as $cliente)
                                    <tr>
                                        <td>{{ $cliente['cliente'] }}</td>
                                        <td class="tb_number">{{ $cliente['quantidade'] }}</td>
                                        <td class="tb_number">{{ $cliente['valor'] }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="tb_number"></td>
                                <td class="tb_number" id="cliente_quantidade_devolvida">{{ $quantidade_total }}</td>
                                <td class="tb_number" id="cliente_valor_devolvido">{{ $valor_total }}</td>
                            </tr>
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
        form_modal_devolucao = $(document).find("#form_devolucao_produto"); 

        form_modal_devolucao.find("#marca_produto").autocomplete(optionsAutoCompleteMarca());
        form_modal_devolucao.find("#linha_produto").autocomplete(optionsAutoCompleteLinha());
        form_modal_devolucao.find("#grupo_produto").autocomplete(optionsAutoCompleteGrupo());
        form_modal_devolucao.find("#subgrupo_produto").autocomplete(optionsAutoCompleteSubgrupo());
        form_modal_devolucao.find("#descricao_produto").autocomplete(optionsAutoComplete("nome"));
        
        form_modal_devolucao.find("#bt-search-produto").off('click');
        form_modal_devolucao.find("#bt-search-produto").on('click', function(){
            showModalProduto(form_modal_devolucao);
        });
        form_modal_devolucao.find("#btn-filterform_produtos").off('click');
        form_modal_devolucao.find("#btn-filterform_produtos").on('click', function(){
            filterDialogDevolucaoProduto(form_modal_devolucao);
        });

        form_modal_cliente = $(document).find("#form_devolucao_cliente"); 
        form_modal_cliente.find("#btn-filterform_clientes").off('click');
        form_modal_cliente.find("#btn-filterform_clientes").on('click', function(){
            filterDialogDevolucaoCliente(form_modal_cliente);
        });
        
        form_modal_cliente.find("#cliente").autocomplete(optionsAutoCompleteCliente(form_modal_cliente));

        form_modal_cliente.find("#bt-search-cliente").off("click");
        form_modal_cliente.find("#bt-search-cliente").on("click", function(event){
            event.stopPropagation();
            showModalCliente($(this).data("route"), form_modal_cliente);
            return false;
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

    function showModalProduto(form_modal_devolucao){
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
                        returnDadosProduto($(this), form_modal_devolucao);
                    });
    
                });
            }
        });
    }
    
    function returnDadosProduto($dados, form_modal_devolucao){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        form_modal_devolucao.find('#codigo_produto').val($dados.find("td").eq(1).text());
        form_modal_devolucao.find('#descricao_produto').val($dados.find("td").eq(2).text());
    }

    function filterDialogDevolucaoProduto(form_modal_devolucao){
        table_devolucao_produtos.clear().draw();
        data_form_modal_devolucao = form_modal_devolucao.serialize();
        $.ajax({
            url: '{{ route('mapa_venda.filtro_devolucao_produtos')}}',
            data: data_form_modal_devolucao,
            method: 'POST',
            success: function(data){  
                linhas = [];
                
                for (var fields in data.response.produtos){
                    temp_array = [
                        data.response.produtos[fields].codigo,
                        data.response.produtos[fields].descricao,
                        data.response.produtos[fields].linha,
                        data.response.produtos[fields].grupo,
                        data.response.produtos[fields].marca,
                        data.response.produtos[fields].quantidade,
                        data.response.produtos[fields].preco_total,
                    ];
                    linhas.push(temp_array)
                }
                table_devolucao_produtos.rows.add(linhas).draw();

                $(document).find('#produto_quantidade_devolvida').html(data.response.quantidade_devolvida);
                $(document).find('#produto_valor_devolvido').html(data.response.valor_devolvido);
            },
            error: function(data){

            }
        });
    }

    function filterDialogDevolucaoCliente(form_modal_cliente){
        table_devolucao_clientes.clear().draw();
        data_form_modal_cliente = form_modal_cliente.serialize();
        $.ajax({
            url: '{{ route('mapa_venda.filtro_devolucao_clientes')}}',
            data: data_form_modal_cliente,
            method: 'POST',
            success: function(data){  
                linhas = [];
                
                for (var unidade in data.response.clientes){
                    for (var index in data.response.clientes[unidade]){
                        temp_array = [
                            data.response.clientes[unidade][index].cliente,
                            data.response.clientes[unidade][index].quantidade,
                            data.response.clientes[unidade][index].valor,
                        ];
                        linhas.push(temp_array)
                    }
                }
                table_devolucao_clientes.rows.add(linhas).draw();

                $('.dataTables_scrollFootInner').find('#cliente_quantidade_devolvida').html(data.response.quantidade_devolvida);
                $('.dataTables_scrollFootInner').find('#cliente_valor_devolvido').html(data.response.valor_devolvido);
            },
            error: function(data){

            }
        });
    }

    function optionsAutoCompleteCliente(form_modal_cliente){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.estabelecimento = $(document).find('#estabelecimento').val();
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_cliente').css('z-index')) + 1));
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
                form_modal_cliente.find("#cliente").val(ui.item.label);
                return false;
            }
        };
    }

    function showModalCliente(url, form_modal_cliente){
		var title = "Busca de Clientes";
        $.ajax({
            url: url,
            type: 'POST',
            data: {_token: '{{ csrf_token() }}'},
            success: function(body){
				$(document).find('#cliente_searsh_show').remove();
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("td").not('.th_view').off("click");
                        modal.find('tbody').find("td").not('.th_view').on("click", function(event){
                            returnDadosCliente($(this).parent('tr'), event, form_modal_cliente);
                        });
                    });
                });
            }
        });
    }

    function returnDadosCliente($dados, event, form_modal_cliente){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }

        form_modal_cliente.find("#cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
    }
</script>
@endsection