@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-1">
                <input type="text" name="numero_projeto" id="numero_projeto" value="" placeholder="Número do Projeto"/>
            </div>
            <div class="col-lg-3">
                <input type="text" name="nome_projeto" id="nome_projeto" value="" placeholder="Nome do Projeto"/>
            </div>
            <div class="col-lg-1">
                <input type="text" name="numero_pedido" id="numero_pedido" value="" placeholder="Pedido"/>
            </div>
            <div class="col-sm-7">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="faccao" id="faccao" value="" placeholder="Facção" maxlength="250" />
                    <span class="input-group-addon border rounded-right" id="bt-search-faccao-busca" data-route="{{ route("faccao.modal.buscar") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="codigo_produto" id="codigo_produto" value="" placeholder="Codigo Produto" maxlength="60"/>
                    <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
                </div>
            </div>
            @if (Auth::user()->tipo_usuario_id != 12 && Auth::user()->tipo_usuario_id != 16)   
            <div class="col-lg-3">
                <input type="text" name="nome_produto" id="nome_produto" value="" placeholder="Nome do Produto"/>
            </div>
            <div class="col-lg-3">
                {!! Form::select('representante', $representantes, '', ['id' => 'representante', 'placeholder'=> 'Todos Representantes']) !!}
            </div>
            @else
            <div class="col-lg-6">
                <input type="text" name="nome_produto" id="nome_produto" value="" placeholder="Nome do Produto"/>
            </div>
            @endif
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="radio" class="form-check-input" name="tipo" id="tipo_todos" value="todos" checked />
                    <label class="form-check-label" for="tipo_todos">Todos</label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="radio" class="form-check-input" name="tipo" id="tipo_projeto" value="projeto" />
                    <label class="form-check-label" for="tipo_projeto">Projeto</label>
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
    <table class="table table-striped table-filter-pedido-portal table-not-edit table-not-view table-verificacao" id="table-filters">
        <thead>
            <tr>
                <th class="tb_number">Num. Projeto/Pedido</th>
                <th>Projeto</th>
                <th>Facção</th>
                <th>Produto a enviar</th>
                <th class="tb_number">Qtde a enviar</th>
                <th class="tb_number">Estoque</th>
                <th class="tb_number">Compras</th>
                <th class="td_acao"></th>
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
    form.find("#faccao").autocomplete(optionsAutoCompleteFaccao());
    form.find("#cliente").autocomplete(optionsAutoCompleteCliente());

    form.find("#bt-search-cliente").on('click', function(event){
        event.stopPropagation();
        showModalClienteBusca($(this).data("route"));
        return false;
    });

    form.find("#bt-search-faccao-busca").on("click", function(){
        showModalFaccao($(this).data("route"), "Lista de Facções");
    });

    form.find("#btn-filterform").on("click", function(){
        filterAjax();
    });

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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number","width": "100px" },
            { "class": "tb_date", targets: "sort-date" },
            {
                'targets': 'td_acao',
                'class': 'td_acao',
                'width': '5px',
                "orderable": false
            },
            { targets: 0, width: '10px'},
        ],
    });

});

function optionsAutoCompleteFaccao(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('faccao.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($('#form_filter').css('z-index')) + 1));
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
            $(document).find("#faccao").val(ui.item.label);
            $(document).find("#codigo_faccao").val(ui.item.value);
            return false;
        }
    };
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
    };
}

function showModalClienteBusca(url){
    var title = "Busca de Clientes";
    $.ajax({
        url: url,
        method: 'POST',
        data: {
            _token: '{{csrf_token()}}'
        },
        success: function(body){
            $(document).find('#cliente_searsh_show').remove();
            createModal("cliente_searsh_show", title, body, 'modal-lg');
            var modal = $(document).find("#cliente_searsh_show");
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    modal.find('tbody').find("tr").off("click");
                    modal.find('tbody').find("tr").on("click", function(event){
                        returnDadosClienteBusca($(this), event);
                    });
                });
            });
        }
    });
}

function returnDadosClienteBusca($dados, event){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#cliente").val($dados.find("td").eq(1).text()+' - '+$dados.find("td").eq(3).text());
    $(document).find("#cliente_searsh_show").modal("hide");
}

function showModalFaccao(url, title){
    $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("faccao_search_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#faccao_search_show").find('tbody').find("tr").off("click");
                    $(document).find("#faccao_search_show").find('tbody').find("tr").on("click", function(){
                        returnDadosFaccao($(this));
                    });
                });
            });
        }
    });
}

function returnDadosFaccao($this){
    if($this.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#faccao_search_show").modal("hide");
    $(document).find("#faccao").val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
}

function filterAjax(){
    filterClear();
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    $.ajax({
        url: '{{ route('remessa_itens.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            linhas = [];

            for(var projeto in data.response){
                for(var faccao in data.response[projeto]){
                    for(var produto in data.response[projeto][faccao]){
                        temp_array = [
                            data.response[projeto][faccao][produto].numero_projeto,
                            data.response[projeto][faccao][produto].nome_projeto,
                            data.response[projeto][faccao][produto].faccao,
                            validar(data.response[projeto][faccao][produto]),
                            data.response[projeto][faccao][produto].qtde_a_enviar,
                            data.response[projeto][faccao][produto].estoque,
                            data.response[projeto][faccao][produto].compras,
                            createBtRemessa(data.response[projeto][faccao][produto]),
                        ];
                        if(data.response[projeto][faccao][produto].validar === false){
                            table_filters.row.add(temp_array).draw().nodes().to$().addClass('error-tr');
                        }else{
                            table_filters.row.add(temp_array).draw();
                        }
                    }
                }
            }

            chamadaPopover();
        }
    });
}
function filterClear(){
    table_filters.clear().draw();
}

function ajusteTamanhoTable($value){
    $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";

    return $html;
}

function createBtRemessa($value){
    if ($value.botao_remessa === true){
        var html = "<div class=\"btn-pedido\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Gerar Remessa\" data-title=\"Geração de Remessa\" data-id_projeto=\""+$value.id_projeto+"\" data-tipo=\""+$value.tipo+"\" data-codigo_produto=\""+$value.codigo_produto+"\" data-id_faccao=\""+$value.faccao_id+"\" data-pedido=\""+$value.pedido+"\" onclick=\"showModalGeracaoRemessa($(this))\"></div>";
    }else{
        var html = '';
    }
    return html;
}

function linkProjeto($value){
    html = "<a href=\"#\" data-toggle='tooltip' data-html='true' title='Visualizar' onclick=\"abrirProjeto('"+$value+"')\">"+$value+"</a>";

    return html;
}

function abrirProjeto($id){
    $.ajax({
        url: '{{ route('lancamento_projeto.modal.view') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id_projeto: $id 
        },
        success: function (data){
            createModal('detalhes', 'Detalhes do Projeto', data, 'modal-lg');
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

function showModalGeracaoRemessa($value){
    var title = $value.data("title");
    var tipo = $value.data("tipo");
    var codigo_produto = $value.data("codigo_produto");
    var id_faccao = $value.data("id_faccao");
    var id_projeto = $value.data("id_projeto");
    var pedido = $value.data("pedido");
    $.ajax({
        url: '{{ route('remessa_itens.modal.geracao_remessa') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            tipo: tipo,
            codigo_produto: codigo_produto,
            id_faccao: id_faccao,
            id_projeto: id_projeto,
            pedido: pedido,
        },
        success: function (body){
            createModal('modal_geracao_remessa',  title, body, 'modal-lg');
        },
        error: function (callback){
            message("Atenção", callback.responseJSON.message);
        }
    }); 
}

function validar($value){
    if($value.validar === false){
        $return = "<div>"+
            "<div data-toggle=\"popover\" data-placement=\"top\" data-title=\"Insumo\" data-content=\"<p>A unidade padrão do insumo está incorreta. Favor verificar com o setor responsável.\"></a>"+
                "<a href=\"#\" class=\"btn-informacao\"></a>"+
                $value.produto_a_enviar+
            "</div>"+
        "</div>";
    }else{
        $return = $value.produto_a_enviar;
    }   

    return $return
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
@endsection