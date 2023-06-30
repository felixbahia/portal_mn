@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_cliente" id="form_cliente" onsubmit="return false;">
    @csrf
    <br>
    {!! Form::hidden('data_escolhida_inicial', $data_escolhida_inicial, ['id' => 'data_escolhida_inicial']) !!}
    {!! Form::hidden('data_escolhida_final', $data_escolhida_final, ['id' => 'data_escolhida_final']) !!}
    {!! Form::hidden('filtro', $filtro, ['id' => 'filtro']) !!}
    {!! Form::hidden('id_unidade_negocio', $id_unidade_negocio, ['id' => 'id_unidade_negocio']) !!}
    {!! Form::hidden('filtro_cliente', '', ['id' => 'filtro_cliente']) !!}
    {!! Form::hidden('codigo_vendedor', $codigo_vendedor, ['id' => 'codigo_vendedor']) !!}
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
                        {!! Form::select('tipo', $tipo_usuarios, '', ['id' => 'tipo', 'class' => 'form-control']) !!}
                    </div>
                    
                    <div class="col-lg-4">
                        {!! Form::select('vendedor', $representantes,'', ['id' => 'vendedor', 'class' => 'form-control', 'placeholder' => 'Todos os Vendedores']) !!}
                    </div>
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
            <div class="row">
                <div class="col-lg-12">
                    <div class="input-group" id="cod_cliente_group">
                        {{ Form::text('cliente', '', ['id' => 'cliente', 'class' => 'form-control essencial input-label', 'placeholder' => 'Cliente']) }}
                        <span class="input-group-addon border rounded-right" id="bt-search-cliente" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                    </div>
                </div>
            </div>
        @else
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
                <div class="col-lg-7">
                    <div class="input-group" id="cod_cliente_group">
                        {{ Form::text('cliente', '', ['id' => 'cliente', 'class' => 'form-control essencial input-label', 'placeholder' => 'Cliente']) }}
                        <span class="input-group-addon border rounded-right" id="bt-search-cliente" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                    </div>
                </div>
            </div>
        @endif
        <br>
        <div class="content-buttons">
            <button name="btn-filterform_produtos" id="btn-filterform_produtos" class="btn-filter">Buscar</button>
            <input name="btn-clearform_produtos" id="btn-clearform_produtos" class="btn-clear" value="Limpar busca" style="width: 135px;text-align: center;"/>
        </div>
    </div>
    <div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped" id="table-dialog-clientes">
                <thead>
                    <th>Ordem</th>
                    <th>Cliente</th>
                    <th>Valor</th>
                    <th>%</th>
                    <th>Acumulativo %</th>
                    <th>Rentabilidade</th>
                    <th class="td_acao">Produto</th>
                </thead>
                <tbody>
                    @foreach ($clientes as $cliente)
                        <tr>
                            <td class="tb_number">{{ $cliente['rank_codigo'] }}</td>
                            <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $cliente['cliente'] }}'>{{ $cliente['cliente'] }}</div></div></td>
                            <td class="tb_number">
                                {{ $cliente['valor'] }}

                                @if($cliente['valor'] < 0)
                                <a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="top" title="" data-original-title="Devolução" style="color: black;"></a>
                                @endif 
                            </td>
                            <td class="tb_number">{{ $cliente['porcetagem'] }}</td>
                            <td class="tb_number">{{ $cliente['total_porcetagem'] }}</td>
                            <td class="tb_number">{{ $cliente['retabilidade_porcetagem'] }}</td>
                            <td class="td_acao"><a href="#" class="bt-view" data-toggle='tooltip' data-html='true' data-id_unidade_negocio="{{ $id_unidade_negocio }}" data-filtro="{{ $filtro }}" data-codigo_cliente="{{ $cliente['codigo_cliente'] }}" title='Produto' data-title="{{ $title }} - Cliente: {{ $cliente['cliente'] }}" onclick="abrirModalGrupoCliente($(this))"></a></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="tb_number">Total :</td>
                        <td></td>
                        <td class="tb_number" id="cliente_valor_total">{{ $valor_total }}</td>
                        <td class="tb_number"></td>
                        <td class="tb_number" id="cliente_total_porcetagem">{{ $total_porcetagem }}</td>
                        <td class="tb_number" id="cliente_total_retabilidade">{{ $total_retabilidade }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</form>
<script>
    $(document).ready( function () {
        table_dialog_clientes_options = {
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
                { targets: 0, width: '10px'},
                { targets: 6, width: '50px'},
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number", 'width': '150px',},
                
                
            ],
            "order": [[ 0, "asc" ]],
        };
        table_clientes = '';
        table_clientes = $(document).find('#table-dialog-clientes').DataTable(table_dialog_clientes_options);
        table_clientes.draw();

        setTimeout(function(){
            table_clientes.draw(false);
        }, 150);

        form_modal_cliente = $(document).find("#form_cliente"); 

        form_modal_cliente.find("#btn-filterform_produtos").off('click');
        form_modal_cliente.find("#btn-filterform_produtos").on('click', function(){
            filterDialog(form_modal_cliente);
        });

        form_modal_cliente.find("#cliente").autocomplete(optionsAutoCompleteCliente(form_modal_cliente));

        form_modal_cliente.find("#bt-search-cliente").off("click");
        form_modal_cliente.find("#bt-search-cliente").on("click", function(event){
            event.stopPropagation();
            showModalCliente($(this).data("route"), form_modal_cliente);
            return false;
        });
    });

    function filterDialog(form_modal_cliente){
        table_clientes.clear().draw();
        $('.dataTables_scrollFootInner').find('#cliente_quantidade_total').html("");
        $('.dataTables_scrollFootInner').find('#cliente_quantidade_total_ano_anterior').html("");
        $('.dataTables_scrollFootInner').find('#cliente_valor_total').html("");
        $('.dataTables_scrollFootInner').find('#cliente_valor_total_ano_anterior').html("");
        $('.dataTables_scrollFootInner').find('#cliente_diferenca_porcetagem_quantidade_total').html("");
        $('.dataTables_scrollFootInner').find('#cliente_diferenca_porcetagem_valor_total').html("");

        data_form_modal_cliente = form_modal_cliente.serialize();
        $.ajax({
            url: '{{ route('mapa_venda.filtro_clientes')}}',
            data: data_form_modal_cliente,
            method: 'POST',
            success: function(data){  
                linhas = [];
                
                for (var fields in data.response.clientes){
                    temp_array = [
                        data.response.clientes[fields].rank_codigo,
                        ajusteTamanhoTable(data.response.clientes[fields].cliente),
                        data.response.clientes[fields].valor,
                        data.response.clientes[fields].porcetagem,
                        data.response.clientes[fields].total_porcetagem,
                        data.response.clientes[fields].retabilidade_porcetagem,
                        btnGrupoCliente(data.response.clientes[fields]),
                    ];
                    linhas.push(temp_array)
                }
                table_clientes.rows.add(linhas).draw();
                
                form_modal_cliente.find("#filtro_cliente").val(data.response.filtro_cliente);

                $('.dataTables_scrollFootInner').find('#cliente_total_retabilidade').html(data.response.total_retabilidade);
                $('.dataTables_scrollFootInner').find('#cliente_valor_total').html(data.response.valor_total);
            },
            error: function(data){

            }
        });
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

        return $html;
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
    function abrirModalGrupoCliente($this){
        var filtro = $($this).data("filtro");
        var id_unidade_negocio = $($this).data("id_unidade_negocio");
        var codigo_cliente = $($this).data("codigo_cliente");
        var title = $($this).data("title");
        var filtro_cliente = form_modal_cliente.find("#filtro_cliente").val();
        var codigo_vendedor = form_modal_cliente.find("#codigo_vendedor").val();
        $.ajax({
            url: '{{ route('mapa_venda.modal.grupo') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
                id_unidade_negocio: id_unidade_negocio,
                codigo_cliente: codigo_cliente,
                title: title,
                filtro_cliente: filtro_cliente,
                codigo_vendedor: codigo_vendedor
            },
            success: function(body){
                createModal('modal_grupo', title, body, "modal-lg");
                var modal = $("#modal_grupo");
            }
        });
    }

    function btnGrupoCliente($value){
        var html = "<a href=\"#\" class=\"bt-view\" data-toggle='tooltip' data-html='true' data-id_unidade_negocio=\"{{ $id_unidade_negocio }}\" data-filtro=\"{{ $filtro }}\" data-codigo_cliente=\""+$value.codigo_cliente+"\" title='Produto' data-title=\"{{ $title }} - Cliente: "+$value.codigo_cliente+"\" onclick=\"abrirModalGrupoCliente($(this))\"></a>"
        
        return html;
    }
</script>
@endsection