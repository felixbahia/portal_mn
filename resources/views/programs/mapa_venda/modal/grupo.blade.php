@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_grupos" id="form_grupos" onsubmit="return false;">
    @csrf
    {!! Form::hidden('data_escolhida_inicial', $data_escolhida_inicial, ['id' => 'data_escolhida_inicial']) !!}
    {!! Form::hidden('data_escolhida_final', $data_escolhida_final, ['id' => 'data_escolhida_final']) !!}
    {!! Form::hidden('filtro', $filtro, ['id' => 'filtro']) !!}
    {!! Form::hidden('id_unidade_negocio', $id_unidade_negocio, ['id' => 'id_unidade_negocio']) !!}
    {!! Form::hidden('codigo_cliente', $codigo_cliente, ['id' => 'codigo_cliente']) !!}
    {!! Form::hidden('codigo_vendedor', $codigo_vendedor, ['id' => 'codigo_vendedor']) !!}
    {!! Form::hidden('filtro_grupo', '', ['id' => 'filtro_grupo']) !!}
    <div class="content-filter-dialog">
        @if(empty($codigo_vendedor)) 
            <div class="row">
                @if(empty(decrypt($id_unidade_negocio)))
                    <div class="col-lg-2">
                        {!! Form::select('tipo', $tipo_usuarios, '', ['id' => 'tipo', 'class' => 'form-control']) !!}
                    </div>
                    
                    <div class="col-lg-2">
                        {!! Form::select('equipe', $unidades_negocios, '', ['id' => 'equipe', 'class' => 'form-control', 'placeholder' => 'Todas as Equipes']) !!}
                    </div>
                    
                    <div class="col-lg-2">
                        {!! Form::select('vendedor', $representantes,'', ['id' => 'vendedor', 'class' => 'form-control', 'placeholder' => 'Todos os Vendedores']) !!}
                    </div>
                @else
                    <div class="col-lg-2">
                        {!! Form::select('tipo', $tipo_usuarios, $filtro_cliente['tipo'], ['id' => 'tipo', 'class' => 'form-control']) !!}
                    </div>
                    
                    <div class="col-lg-4">
                        {!! Form::select('vendedor', $representantes, $filtro_cliente['vendedor'], ['id' => 'vendedor', 'class' => 'form-control', 'placeholder' => 'Todos os Vendedores']) !!}
                    </div>
                @endif
                <div class="col-lg-2">
                    {!! Form::select('regiao', $regioes, $filtro_cliente['regiao'], ['id' => 'regiao', 'class' => 'form-control', 'placeholder' => 'Todas as Regiões']) !!}
                </div>
                <div class="col-lg-1">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_todos" value="" @if(empty($filtro_cliente['marca_nacional_importado']))checked @endif/>
                        <label class="form-check-label" for="marca_todos">Todos</label>
                    </div>
                </div>
                <div class="col-lg-1">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_nacional" value="nacional" @if($filtro_cliente['marca_nacional_importado'] === "nacional")checked @endif/>
                        <label class="form-check-label" for="marca_nacional">Nacional</label>
                    </div>
                </div>
                <div class="col-lg-1">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_importado" value="importado" @if($filtro_cliente['marca_nacional_importado'] === "importado")checked @endif/>
                        <label class="form-check-label" for="marca_importado">Importado</label>
                    </div>
                </div>
            </div>
        @else
            {!! Form::hidden('vendedor', $codigo_cliente, ['id' => 'vendedor']) !!}
            <div class="row">
                <div class="col-lg-6">
                    {!! Form::select('regiao', $regioes, $filtro_cliente['regiao'], ['id' => 'regiao', 'class' => 'form-control', 'placeholder' => 'Todas as Regiões']) !!}
                </div>
                <div class="col-lg-2">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_todos" value="" @if(empty($filtro_cliente['marca_nacional_importado']))checked @endif/>
                        <label class="form-check-label" for="marca_todos">Todos</label>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_nacional" value="nacional" @if($filtro_cliente['marca_nacional_importado'] === "nacional")checked @endif/>
                        <label class="form-check-label" for="marca_nacional">Nacional</label>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="marca_nacional_importado" id="marca_importado" value="importado" @if($filtro_cliente['marca_nacional_importado'] === "importado")checked @endif/>
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
        <div class="row">
            <div class="col-lg-1">
                {{ Form::label('filtro_produto_vendedor', 'Venda Por:', []) }}
                <div class="form-check">
                    <input type="radio" class="form-check-input" name="filtro_produto_vendedor" id="filtro_produto" value="produto" checked/>
                    <label class="form-check-label" for="filtro_produto">Produto</label>
                </div>
            </div>
            <div class="col-lg-1">
                <br/>
                <div class="form-check">
                    <input type="radio" class="form-check-input" name="filtro_produto_vendedor" id="filtro_vendedor" value="vendedor" />
                    <label class="form-check-label" for="filtro_vendedor">Vendedor</label>
                </div>
            </div>
        </div>
        <br>
        <div class="content-buttons">
            <button name="btn-filterform_grupos" id="btn-filterform_grupos" class="btn-filter">Buscar</button>
            <input name="btn-clearform_grupos" id="btn-clearform_grupos" class="btn-clear" value="Limpar busca" style="width: 135px;text-align: center;"/>
        </div>
    </div>
    <div class="content-dialog-table">
        <div class="content-table">
            <div class="show-on-produto">
                <table class="table table-striped" id="table-dialog-grupos">
                    <thead>
                        <th class="tb_number">Ordem</th>
                        <th>Grupo</th>
                        <th>Linha</th>
                        <th>Marca</th>
                        <th class="tb_number">Quantidade</th>
                        <th class="tb_number">Valor</th>
                        <th class="tb_number">%</th>
                        <th class="tb_number">Acumulativo %</th>
                        <th class="tb_number">Preço Unitário</th>
                        <th class="tb_number">Custo Gerencial</th>
                        <th class="tb_number">Rentabilidade</th>
                    </thead>
                    <tbody>
                        @foreach ($produtos as $produto)
                            <tr>
                                <td class="tb_number">{{ $produto['rank_codigo'] }}</td>
                                <td><a href="#" data-id_unidade_negocio="{{ $id_unidade_negocio }}" data-filtro="{{ $filtro }}" data-grupo="{{ $produto['grupo'] }}" data-codigo_cliente="{{ $codigo_cliente }}" data-linha="{{ $produto['linha'] }}" data-marca="{{ $produto['marca'] }}" data-title="{{ $title }} - Grupo: {{ $produto['grupo'] }} - Linha: {{ $produto['linha']}} - Marca: {{ $produto['marca'] }}" onclick="abrirModalProduto($(this))">{{ $produto['grupo'] }}</a></td>
                                <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $produto['linha'] }}'>{{ $produto['linha'] }}</div></div></td>
                                <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $produto['marca'] }}'>{{ $produto['marca'] }}</div></div></td>
                                <td class="tb_number">{{ $produto['quantidade'] }}</td>
                                <td class="tb_number">{{ $produto['valor'] }}</td>
                                <td class="tb_number">{{ $produto['porcetagem'] }}</td>
                                <td class="tb_number">{{ $produto['porcetagem_acumulativa'] }}</td>
                                <td class="tb_number">{{ $produto['preco_unitario'] }}</td>
                                <td class="tb_number">{{ $produto['custo_gerencial'] }}</td>
                                <td class="tb_number">{{ $produto['retabilidade_porcetagem'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="tb_number">Total :</td>
                        <td class="tb_number" id="grupo_quantidade_total">{{ $quantidade_total_data_escolhida }}</td>
                        <td class="tb_number" id="grupo_valor_total">{{ $valor_total_data_escolhida }}</td>
                        <td class="tb_number"></td>
                        <td class="tb_number" id="grupo_total_porcetagem">{{ $total_porcetagem }}</td>
                        <td class="tb_number"></td>
                        <td class="tb_number"></td>
                        <td class="tb_number" id="grupo_total_retabilidade">{{ $total_retabilidade }}</td>
                    </tfoot>
                </table>
            </div>
            <div class="show-on-vendedor">
                <table class="table table-striped" id="table-dialog-grupos-vendedores">
                    <thead>
                        <th>Unidade</th>
                        <th>Vendedor</th>
                        <th>Grupo</th>
                        <th>Linha</th>
                        <th>Marca</th>
                        <th class="tb_number">Quantidade</th>
                        <th class="tb_number">Valor</th>
                        <th class="tb_number">%</th>
                        <th class="tb_number">Acumulativo %</th>
                        <th class="tb_number">Preço Unitário</th>
                        <th class="tb_number">Custo Gerencial</th>
                        <th class="tb_number">Rentabilidade</th>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="tb_number">Total :</td>
                        <td class="tb_number" id="grupo_quantidade_total_vendedor"></td>
                        <td class="tb_number" id="grupo_valor_total_vendedor"></td>
                        <td class="tb_number"></td>
                        <td class="tb_number" id="grupo_total_porcetagem_vendedor"></td>
                        <td class="tb_number"></td>
                        <td class="tb_number"></td>
                        <td class="tb_number" id="grupo_total_retabilidade_vendedor"></td>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</form>
<script>
    $(document).ready( function () {
        table_dialog_grupos_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "45vh",
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
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '{{ $nome_excel }}',
                    footer: true,
                    autoFilter: true,
                    exportOptions: {
                        modifier: {
                            page: 'all'
                        },
                        columns: ':visible',
                        format: {
                            body: function ( data, row, column, node ) {
                                data = $('<p>' + data + '</p>').text();
                                if(column != 1 && column != 2 && column != 3){
                                    if(data != ''){
                                        numero = data.replace('.','').replace(',','').replace('%','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            }
                        }
                    }
                },
            ],
            "columnDefs": [
                { targets: 0, width: '15px'},
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number", 'width': '100px',},
            ],
            "order": [[ 0, "asc" ]],
        };
        table_grupos = '';
        table_grupos = $(document).find('#table-dialog-grupos').DataTable(table_dialog_grupos_options);
        table_grupos.draw();

        table_dialog_grupos_vendedores_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "45vh",
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
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '{{ $nome_excel }}',
                    footer: true,
                    autoFilter: true,
                    exportOptions: {
                        modifier: {
                            page: 'all'
                        },
                        columns: ':visible',
                        format: {
                            body: function ( data, row, column, node ) {
                                data = $('<p>' + data + '</p>').text();
                                if(column !== 0 && column !== 1 && column !== 2 && column !== 3 && column !== 4){
                                    if(data != ''){
                                        numero = data.replace('.','').replace(',','').replace('%','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            }
                        }
                    }
                },
            ],
            "columnDefs": [
                { targets: 0, width: '15px'},
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number", 'width': '100px',},
            ],
            "order": [[ 0, "asc" ]],
        };
        table_grupos_vendedores = '';
        table_grupos_vendedores = $(document).find('#table-dialog-grupos-vendedores').DataTable(table_dialog_grupos_vendedores_options);
        table_grupos_vendedores.draw();

        form_modal_grupo = $(document).find("#form_grupos"); 

        form_modal_grupo.find('.show-on-vendedor').hide();

        form_modal_grupo.find("#marca_produto").autocomplete(optionsAutoCompleteMarca());
        form_modal_grupo.find("#linha_produto").autocomplete(optionsAutoCompleteLinha());
        form_modal_grupo.find("#grupo_produto").autocomplete(optionsAutoCompleteGrupo());
        form_modal_grupo.find("#subgrupo_produto").autocomplete(optionsAutoCompleteSubgrupo());
        form_modal_grupo.find("#descricao_produto").autocomplete(optionsAutoComplete("nome"));

        form_modal_grupo.find("#bt-search-produto").off('click');
        form_modal_grupo.find("#bt-search-produto").on('click', function(){
            showModalProduto(form_modal_grupo);
        });

        setTimeout(function(){
            table_grupos.draw(false);
        }, 150);
        
        form_modal_grupo.find("#btn-filterform_grupos").off('click');
        form_modal_grupo.find("#btn-filterform_grupos").on('click', function(){
            if(form_modal_grupo.find("#filtro_produto").prop("checked") === true){
                filterModalGrupo(form_modal_grupo);
            }else{
                filterModalGrupoVendedor(form_modal_grupo);
            }
        });

        form_modal_grupo.find("#filtro_produto").off('click');
        form_modal_grupo.find("#filtro_produto").on('click', function(){
            form_modal_grupo.find('.show-on-vendedor').hide();
            form_modal_grupo.find('.show-on-produto').show();
            filterModalGrupo(form_modal_grupo);
        });

        form_modal_grupo.find("#filtro_vendedor").off('click');
        form_modal_grupo.find("#filtro_vendedor").on('click', function(){
            form_modal_grupo.find('.show-on-vendedor').show();
            form_modal_grupo.find('.show-on-produto').hide();
            filterModalGrupoVendedor(form_modal_grupo);
        });
    });

    function optionsAutoCompleteMarca(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.marca.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo').css('z-index')) + 1));
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo').css('z-index')) + 1));
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo').css('z-index')) + 1));
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo').css('z-index')) + 1));
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_grupo').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.draw();
                }, 100);
            }
        };
    }

    function showModalProduto(form_modal_grupo){
        $.ajax({
            url: '{{ route('produto.modal_pesquisa') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
                table_filters_grupos_busca.on('draw', function () {
    
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                        returnDadosProduto($(this), form_modal_grupo);
                    });
    
                });
            }
        });
    }
    
    function returnDadosProduto($dados, form_modal_grupo){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        form_modal_grupo.find('#codigo_produto').val($dados.find("td").eq(1).text());
        form_modal_grupo.find('#descricao_produto').val($dados.find("td").eq(2).text());
    }

    function filterModalGrupo(form_modal_grupo){
        table_grupos.clear().draw();
        data_form_modal_grupo = form_modal_grupo.serialize();
        $.ajax({
            url: '{{ route('mapa_venda.filtro_grupos')}}',
            data: data_form_modal_grupo,
            method: 'POST',
            success: function(data){  
                linhas = [];
                
                for (var fields in data.response.produtos){
                    temp_array = [
                        data.response.produtos[fields].rank_codigo,
                        linkProduto(data.response.produtos[fields]),
                        ajusteTamanhoTable(data.response.produtos[fields].linha),
                        ajusteTamanhoTable(data.response.produtos[fields].marca),
                        data.response.produtos[fields].quantidade,
                        data.response.produtos[fields].valor,
                        data.response.produtos[fields].porcetagem,
                        data.response.produtos[fields].porcetagem_acumulativa,
                        data.response.produtos[fields].preco_unitario,
                        data.response.produtos[fields].custo_gerencial,
                        data.response.produtos[fields].retabilidade_porcetagem,
                    ];
                    linhas.push(temp_array)
                }
                table_grupos.rows.add(linhas).draw();

                $('.dataTables_scrollFootInner').find('#grupo_quantidade_total').html(data.response.quantidade_total);
                $('.dataTables_scrollFootInner').find('#grupo_valor_total').html(data.response.valor_total);
                $('.dataTables_scrollFootInner').find('#grupo_total_porcetagem').html(data.response.porcetagem_total);
                $('.dataTables_scrollFootInner').find('#grupo_total_retabilidade').html(data.response.total_retabilidade);

            },
            error: function(data){

            }
        });
    }

    function filterModalGrupoVendedor(form_modal_grupo){
        table_grupos_vendedores.clear().draw();
        data_form_modal_grupo = form_modal_grupo.serialize();
        $.ajax({
            url: '{{ route('mapa_venda.filtro_grupos')}}',
            data: data_form_modal_grupo,
            method: 'POST',
            success: function(data){  
                linhas = [];
                
                for (var fields in data.response.produtos){
                    temp_array = [
                        data.response.produtos[fields].unidade_nome,
                        ajusteTamanhoTable(data.response.produtos[fields].vendedor_nome),
                        linkProduto(data.response.produtos[fields]),
                        ajusteTamanhoTable(data.response.produtos[fields].linha),
                        ajusteTamanhoTable(data.response.produtos[fields].marca),
                        data.response.produtos[fields].quantidade,
                        data.response.produtos[fields].valor,
                        data.response.produtos[fields].porcetagem,
                        data.response.produtos[fields].porcetagem_acumulativa,
                        data.response.produtos[fields].preco_unitario,
                        data.response.produtos[fields].custo_gerencial,
                        data.response.produtos[fields].retabilidade_porcetagem,
                    ];
                    linhas.push(temp_array)
                }
                table_grupos_vendedores.rows.add(linhas).draw();

                $('.dataTables_scrollFootInner').find('#grupo_quantidade_total_vendedor').html(data.response.quantidade_total);
                $('.dataTables_scrollFootInner').find('#grupo_valor_total_vendedor').html(data.response.valor_total);
                $('.dataTables_scrollFootInner').find('#grupo_total_porcetagem_vendedor').html(data.response.porcetagem_total);
                $('.dataTables_scrollFootInner').find('#grupo_total_retabilidade_vendedor').html(data.response.total_retabilidade);

            },
            error: function(data){

            }
        });
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

    function abrirModalProduto($this){
        var filtro = $($this).data("filtro");
        var id_unidade_negocio = $($this).data("id_unidade_negocio");
        var grupo = $($this).data("grupo");
        var codigo_cliente = $($this).data("codigo_cliente");
        var title = $($this).data("title");
        var codigo_vendedor = form_modal_grupo.find("#codigo_vendedor").val();
        var linha = $($this).data("linha");
        var marca = $($this).data("marca");
        $.ajax({
            url: '{{ route('mapa_venda.modal.produto') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
                id_unidade_negocio: id_unidade_negocio,
                grupo: grupo,
                codigo_cliente: codigo_cliente,
                codigo_vendedor: codigo_vendedor,
                linha: linha,
                marca: marca,
            },
            success: function(body){
                createModal('modal_produto', title, body, "modal-lg");
                var modal = $("#modal_produto");
            }
        });
    }

    function linkProduto($value){
        var html = "<a href=\"#\" data-id_unidade_negocio=\"{{ $id_unidade_negocio }}\" data-filtro=\"{{ $filtro }}\" data-grupo=\""+$value.grupo+"\" data-codigo_cliente=\"{{ $codigo_cliente }}\" data-linha=\""+$value.linha+"\" data-marca=\""+$value.marca+"\" data-title=\"{{ $title }} - Grupo: "+$value.grupo+" - Linha: "+$value.linha+" - Marca: "+$value.marca+"\" onclick=\"abrirModalProduto($(this))\">"+$value.grupo+"</a>";
        
        return html;
    }
</script>
@endsection