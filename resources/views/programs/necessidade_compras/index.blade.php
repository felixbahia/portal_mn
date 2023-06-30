@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-1">
                <input type="text" name="num_projeto" id="num_projeto" value="" placeholder="Número do Projeto"/>
            </div>
            <div class="col-lg-1">
                <input type="text" name="pedido_numero" id="pedido_numero" value="" placeholder="Pedido"/>
            </div>
            <div class="col-lg-2">
                <input type="text" name="nome_projeto" id="nome_projeto" value="" placeholder="Nome do Projeto"/>
            </div>
            <div class="col-sm-4">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="cliente" id="cliente" value="" placeholder="Cliente" maxlength="250" />
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="fornecedor" id="fornecedor" value="" placeholder="Fornecedor" maxlength="250" />
                    <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="codigo_produto" id="codigo_produto" value="" placeholder="Codigo Produto" maxlength="60"/>
                    <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-4">
                <input type="text" name="nome_produto" id="nome_produto" value="" placeholder="Nome do Produto"/>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="radio" class="form-check-input" name="tipo" id="tipo_todos" value="todos" checked/>
                    <label class="form-check-label" for="tipo_todos">Todos</label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="radio" class="form-check-input" name="tipo" id="tipo_servico" value="servico" />
                    <label class="form-check-label" for="tipo_servico">Serviço</label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="radio" class="form-check-input" name="tipo" id="tipo_materia_prima" value="materia_prima" />
                    <label class="form-check-label" for="tipo_materia_prima">Matéria Prima</label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="radio" class="form-check-input" name="tipo" id="tipo_pedido" value="pedido" />
                    <label class="form-check-label" for="tipo_pedido">Estamparia Digital</label>
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
    <table class="table table-striped table-verificacao" id="table-filters">
        <thead>
            <tr>
                <th>Código</th>
                <th>Tecido/Insumo/Serviço</th>
                <th>Fornecedor</th>
                <th class="tb_number">Projetos/Pedido</th>
                <th class="tb_number">Estoque</th>
                <th class="tb_number">Compras</th>
                <th class="tb_number">Necessidade</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {
    form = $(document).find('#form_filter');
    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });

    $(document).find("#cliente").autocomplete(optionsAutoCompleteCliente());

    $(document).find("#bt-search-cliente-busca").off("click");
    $(document).find("#bt-search-cliente-busca").on("click", function(event){
        event.stopPropagation();
        showModalCliente($(this).data("route"));
        return false;
    });

    form.find("#bt-search-fornecedor-busca").on("click", function(){
        showModalFornecedor($(this).data("route"), "Lista de Fornecedores");
    });
    form.find("#fornecedor").autocomplete(optionsAutoCompleteFornecedor());

    form.find("#nome_produto").autocomplete(optionsAutoComplete("nome"));
    form.find("#bt-search-produto").on('click', function(){
        showModalProduto(form);
    });

    table_filters.destroy();
    table_filters = $('#table-filters').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "autoWidth": false,
        "language": {
            "decimal":        ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ",",
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" , width: "150px"},
            { "class": "tb_date", targets: "sort-date" },
            {
                "targets": ['th-criterio', 'th-integracao', 'th-aprove', 'th-reprove'],
                "orderable": false
            },
            {
                'targets': ["icone", "data_prevista"],
                "className": "text-center",
                "width": "10px"
            },

        ],
    }).on('draw', function () {
        chamadaPopover();
        $('[data-toggle="tooltip"]').tooltip();
    });

});

function filterAjax(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('necessidade_compras.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            
            for (var fields in data.response){
                temp_array = [
                    tecidoInsumoServico(data.response[fields]),
                    ajusteTamanhoTable(data.response[fields].produto),
                    modalFornecedor(data.response[fields]),
                    data.response[fields].total_projetos,
                    data.response[fields].estoque,
                    historicoUltimasCompras(data.response[fields]),
                    data.response[fields].necessidade,
                    gerarPedidoCompras(data.response[fields])
                ];
                if(data.response[fields].validar === false){
                    table_filters.row.add(temp_array).draw().nodes().to$().addClass('error-tr');
                }else{
                    table_filters.row.add(temp_array).draw();
                }
            }
            chamadaPopover();
        }
    });
}

function filterClear(){
    table_filters.clear().draw();
}

function linkProjeto($this){
    html = "<div><div><a href=\"#\" data-toggle='tooltip' data-codigo_produto = \""+$this.codigo+"\" data-estoque = \""+$this.estoque+"\" data-compras = \""+$this.compras+"\" data-total = \""+$this.total+"\" data-quantidade_produto_enviado=\""+$this.quantidade_produto_enviado+"\"  data-tipo = \""+$this.tipo+"\" data-id_necessidade_compras = \""+$this.id_necessidade_compras+"\" data-title_exibicao=\""+$this.codigo + " - " + $this.produto + " - Estoque: " + $this.estoque + " - Compras: " + $this.compras + " - Produto Enviado: "+$this.quantidade_produto_enviado+" - Total: " + $this.total+"\" data-fornecedor_cnpj_cpf = \""+$this.fornecedor_cnpj_cpf+"\" data-html='true' onclick=\"abrirProjetoCompras($(this))\">"+$this.codigo+"</a></div></div>"
    
    return html;
}

function abrirProjetoCompras($value){
    var codigo_produto = $value.data("codigo_produto");
    var estoque = $value.data("estoque");
    var compras = $value.data("compras"); 
    var total = $value.data("total");
    var tipo = $value.data("tipo"); 
    var id_necessidade_compras = $value.data("id_necessidade_compras");
    var title = $value.data("title_exibicao");
    var quantidade_produto_enviado = $value.data("quantidade_produto_enviado");
    var fornecedor_cnpj_cpf = $value.data("fornecedor_cnpj_cpf");
    if(estoque == ''){
        estoque = "0,0";
    }
    if(compras == ''){
        compras = "0,0";
    }
    if(total == ''){
        total = "0,0";
    }
    form = $(document).find('#form_filter');
    num_projeto = form.find("#num_projeto").val();
    nome_projeto = form.find("#nome_projeto").val();
    cliente = form.find("#cliente").val();
    fornecedor = form.find("#fornecedor").val();
    $.ajax({
        url: '{{ route('necessidade_compras.dialog') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            codigo_produto: codigo_produto,
            total: total,
            tipo: tipo,
            num_projeto: num_projeto,
            nome_projeto: nome_projeto,
            cliente: cliente,
            fornecedor: fornecedor,
            fornecedor_cnpj_cpf: fornecedor_cnpj_cpf,
            id_necessidade_compras: id_necessidade_compras
        },
        success: function (data){
            createModal('detalhes_compras', title , data, 'modal-lg');
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
            $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
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

function showModalCliente(url){
    var title = "Busca de Clientes";
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
                        returnDadosCliente($(this), event);
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
    $(document).find("#cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
    $(document).find("#cliente_searsh_show").modal("hide");
}

function historicoUltimasCompras($this){
    html = 
    "<a href=\"#\" data-id_compras = \""+$this.id_compras+"\" data-produtos = \""+$this.codigo+"\"  onClick=\"showModalPedidosCompras($(this))\">"+$this.compras+"</a>"
    +"<a href=\"#\" class=\"bt-list\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Últimas Compras\" data-title=\""+$this.codigo+" - "+$this.produto+" - Últimos 12 meses\" data-codigo_produto=\""+$this.codigo+"\" onClick=\"showModalUltimasCompras($(this))\"></a>";

    return html;
}

function showModalPedidosCompras($value){
    var title = 'Lista de Projetos';
    var id_compras = $value.data("id_compras");
    var produtos = $value.data("produtos");

    $.ajax({
        url: '{{ route('necessidade_compras.modal.lista_compras') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            produtos: produtos
        },
        success: function (body){
            createModal('lista_projetos',  title, body, 'modal-lg');
        }
    }); 
}

function showModalUltimasCompras($value){
    var title = $value.data("title");
    var codigo_produto = $value.data("codigo_produto");
    $.ajax({
        url: '{{ route('ultimas_compras.modal.dialog') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            codigo_produto: codigo_produto,
        },
        success: function (body){
            createModal('ultimas_comrpas',  title, body, 'modal-lg');
        }
    }); 
}

function modalFornecedor($value){
    if($value.alteracao_fornecedor === true){
        html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value.fornecedor+"'><a href=\"#\" class=\"bt-edit-direita\" data-title=\"\" data-id_necessidade_compras=\""+$value.id_necessidade_compras+"\" data-codigo_produto=\""+$value.codigo+"\" data-descricao_produto=\""+$value.produto+"\" onClick=\"showModalEditarFornecedor($(this))\"> </a>"+$value.fornecedor+"</div></div>";
    }else{
        html = ajusteTamanhoTable($value.fornecedor);
    }

    return html;
}

function showModalEditarFornecedor($value){
    var title = $value.data("codigo_produto")+" - "+$value.data("descricao_produto");
    var id_necessidade_compras = $value.data("id_necessidade_compras");
    $.ajax({
        url: '{{ route('necessidade_compras.modal.editar_fornecedor') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id_necessidade_compras: id_necessidade_compras,
        },
        success: function (body){
            createModal('modal_editar_fornecedor',  title, body, '');
        }
    }); 
}

function ajusteTamanhoTable($value){
    $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";

    return $html;
}

function gerarPedidoCompras($value){
    if(($value.gerar_pedido === true && $value.validar !== 0) || $value.tipo === 'pedido'){
        var html = "<a href=\"#\" class=\"btn-pedido\" title='Geração de Pedido Compras' data-id_necessidade_compras=\""+$value.id_necessidade_compras+"\" data-fornecedor=\""+$value.fornecedor+"\" data-tipo=\""+$value.tipo+"\" onclick=\"showModalGerarPedidoCompras($(this))\"></a>";
    }else{
        var html = ""; 
    }
    
    return html;
}

function showModalGerarPedidoCompras($value){
    var title = $value.data("fornecedor");
    var id_necessidade_compras = $value.data("id_necessidade_compras");
    var tipo = $value.data("tipo");
    $.ajax({
        url: '{{ route('pedidos_compras.modal.geracao_pedido_necessidade_compras') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id_necessidade_compras: id_necessidade_compras,
            tipo: tipo
        },
        success: function (body){
            createModal('modal_geracao_pedido_necessidade_compras',  title, body, 'modal-lg');
        },
        error: function(callback){
            message("Atenção", callback.responseJSON.message);
        }
    }); 
}

function showModalFornecedor(url, title){
    $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("fornecedor_search_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                    $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                        returnDadosFornecedor($(this));
                    });
                });
            });
        }
    });
}

function returnDadosFornecedor($this){
    form = $(document).find('#form_filter');
    if($this.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#fornecedor_search_show").modal("hide");
    form.find("#fornecedor").val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
}

function optionsAutoCompleteFornecedor(){
    $(document).find(".error-message").remove();
    form = $(document).find('#form_filter');
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('fornecedor.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($('#modal_editar_fornecedor').css('z-index')) + 1));
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
            form.find("#fornecedor").val(ui.item.label);
            return false;
        }
    };
}

function tecidoInsumoServico($this){
    if($this.tipo === "servico"){
        $return = "<div>"+
                "<div data-toggle=\"popover\" data-placement=\"top\" data-title=\"Projeto\" data-content=\"<p><b>Número:</b> "+$this.numero_projeto+"<p><b>Nome:</b> "+$this.nome_projeto+"\"></a>"+
                    "<a href=\"#\" class=\"btn-informacao\"></a>"+
                    linkProjeto($this)+
                "</div>"+
            "</div>";
    }else{
        if($this.validar === false){
            $return = "<div>"+
                "<div data-toggle=\"popover\" data-placement=\"top\" data-title=\"Insumo\" data-content=\"<p>A unidade padrão do insumo está incorreta. Favor verificar com o setor responsável.\"></a>"+
                    "<a href=\"#\" class=\"btn-informacao\"></a>"+
                    linkProjeto($this)+
                "</div>"+
            "</div>";
        }else{
            $return = linkProjeto($this);
        }   
    }
    return $return;
}

function chamadaPopover(){
    $('[data-toggle="popover"]').off('show.bs.popover');
    $('[data-toggle="popover"]').popover('hide');

    $('[data-toggle="popover"]').popover({
        container: 'body',
        html: true,
        show: true,
        trigger: 'hover',
        placement: 'left',
        template: '<div class="popover popover-estoque" role="popover"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
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
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($('#form_filter').css('z-index')) + 1));
        },
        select: function( event, ui ) {
        }
    };
}
function showModalProduto(form_modal){
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
                    returnDadosProduto($(this), form);
                });

            });
        }
    });
}

function returnDadosProduto($dados, form){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#modal_search_produto").modal("hide");
    
    form.find('#codigo_produto').val($dados.find("td").eq(1).text());
}

function showModalDetalhes($this){

    $.ajax({
        data: {
            id: $this.data('id'),
            _token: '{{ csrf_token() }}',
            exibicao_custo_fixo: false,
        },
        url: '{{ route('ficha_tecnica.visualizacao.modal') }}',
        method: 'POST',
        success: function(data){
            var title = 'Ficha técnica do produto: ';
            createModal('modal_ficha_tecnica_exibir', title, data, "modal-lg");

            $(document).find('#modal_ficha_tecnica_exibir').on('shown.bs.modal', function(){
                table_filters_composicao.columns.adjust().draw();
                table_filters_servicos.columns.adjust().draw();
            });
        },
        error: function(callback){
        }
    });
}
@endsection
