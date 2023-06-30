@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_ranking_produto" id="form_ranking_produto" onsubmit="return false;">
    @csrf
    {!! Form::hidden('data_escolhida_inicial', $data_escolhida_inicial, ['id' => 'data_escolhida_inicial']) !!}
    {!! Form::hidden('data_escolhida_final', $data_escolhida_final, ['id' => 'data_escolhida_final']) !!}
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
            <div class="col-lg-4">
                <div class="input-group">
                    {!! Form::text('codigo_produto', '', ['id' => 'codigo_produto', 'placeholder' => 'Código Produto', 'class' => 'form-control input-label']) !!}
                    <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-4">
                {!! Form::text('descricao_produto', '', ['id' => 'descricao_produto', 'placeholder' => 'Nome Produto', 'class' => 'form-control']) !!}
            </div>
                {!! Form::hidden('marca_produto', $marca, ['id' => 'marca_produto', 'placeholder' => 'Marca', 'class' => 'form-control']) !!}
                {!! Form::hidden('linha_produto', $linha, ['id' => 'linha_produto', 'placeholder' => 'Linha', 'class' => 'form-control']) !!}
                {!! Form::hidden('grupo_produto', $grupo, ['id' => 'grupo_produto', 'placeholder' => 'Grupo', 'class' => 'form-control']) !!}
            <div class="col-lg-4">
                {!! Form::text('subgrupo_produto', '', ['id' => 'subgrupo_produto', 'placeholder' => 'Subgrupo', 'class' => 'form-control']) !!}
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
            <table class="table table-striped" id="table-dialog-produtos">
                <thead>
                    <th>Ordem</th>
                    <th>Código</th>
                    <th>Produto</th>
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
                            <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $produto['codigo'] }}'>{{ $produto['codigo'] }}</div></div></td>
                            <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $produto['descricao'] }}'>{{ $produto['descricao'] }}</div></div></td>
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
                    <td class="tb_number">Total :</td>
                    <td class="tb_number" id="quantidade_total_data_escolhida">{{ $quantidade_total_data_escolhida }}</td>
                    <td class="tb_number" id="valor_total_data_escolhida">{{ $valor_total_data_escolhida }}</td>
                    <td class="tb_number"></td>
                    <td class="tb_number">{{ $total_porcetagem }}</td>
                    <td class="tb_number"></td>
                    <td class="tb_number"></td>
                    <td class="tb_number">{{ $total_retabilidade }}</td>
                </tfoot>
            </table>
        </div>
    </div>
</form>
<script>
    $(document).ready( function () {
        table_dialog_produtos_options = {
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
            "columnDefs": [
                { targets: 0, width: '15px'},
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number", 'width': '150px',},
            ],
            "order": [[ 0, "asc" ]],
        };
        table_produtos = '';
        table_produtos = $(document).find('#table-dialog-produtos').DataTable(table_dialog_produtos_options);
        table_produtos.draw();

        form_modal_produto = $(document).find("#form_ranking_produto"); 

        form_modal_produto.find("#marca_produto").autocomplete(optionsAutoCompleteMarca());
        form_modal_produto.find("#linha_produto").autocomplete(optionsAutoCompleteLinha());
        form_modal_produto.find("#grupo_produto").autocomplete(optionsAutoCompleteGrupo());
        form_modal_produto.find("#subgrupo_produto").autocomplete(optionsAutoCompleteSubgrupo());
        form_modal_produto.find("#descricao_produto").autocomplete(optionsAutoComplete("nome"));

        form_modal_produto.find("#bt-search-produto").off('click');
        form_modal_produto.find("#bt-search-produto").on('click', function(){
            showModalProduto(form_modal_produto);
        });

        setTimeout(function(){
            table_produtos.draw(false);
        }, 150);
        
        form_modal_produto.find("#btn-filterform_produtos").off('click');
        form_modal_produto.find("#btn-filterform_produtos").on('click', function(){
            filterDialog(form_modal_produto);
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_produto').css('z-index')) + 1));
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_produto').css('z-index')) + 1));
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_produto').css('z-index')) + 1));
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_produto').css('z-index')) + 1));
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_produto').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.draw();
                }, 100);
            }
        };
    }

    function showModalProduto(form_modal_produto){
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
                        returnDadosProduto($(this), form_modal_produto);
                    });
    
                });
            }
        });
    }
    
    function returnDadosProduto($dados, form_modal_produto){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        form_modal_produto.find('#codigo_produto').val($dados.find("td").eq(1).text());
        form_modal_produto.find('#descricao_produto').val($dados.find("td").eq(2).text());
    }

    function filterDialog(form_modal_produto){
        table_produtos.clear().draw();
        data_form_modal_produto = form_modal_produto.serialize();
        $.ajax({
            url: '{{ route('mapa_venda.filtro_produtos')}}',
            data: data_form_modal_produto,
            method: 'POST',
            success: function(data){  
                linhas = [];
                
                for (var fields in data.response.produtos){
                    temp_array = [
                        data.response.produtos[fields].codigo,
                        ajusteTamanhoTable(data.response.produtos[fields].descricao),
                        ajusteTamanhoTable(data.response.produtos[fields].linha),
                        ajusteTamanhoTable(data.response.produtos[fields].grupo),
                        ajusteTamanhoTable(data.response.produtos[fields].marca),
                        data.response.produtos[fields].quantidade,
                        data.response.produtos[fields].quantidade_ano_anterior,
                        "",
                        "",
                        data.response.produtos[fields].diferenca_em_porcetagem,
                    ];
                    linhas.push(temp_array)
                }
                table_produtos.rows.add(linhas).draw();

                $(document).find('#quantidade_total_data_escolhida').html(data.response.quantidade_total_data_escolhida);
                $(document).find('#quantidade_total_data_anterior').html(data.response.quantidade_total_data_anterior);
                $(document).find('#porcetagem_total').html(data.response.porcetagem_total);
            },
            error: function(data){

            }
        });
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }
</script>
@endsection