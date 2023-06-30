@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <input type="text" name="num_projeto" id="num_projeto" value="" placeholder="Número do Projeto" maxlength="250"/>
        </div>
        <div class="col-sm-4">
            <div class="input-group">
                <input type="text" class="form-control input-label" name="cliente" id="cliente" value="" placeholder="Nome / Razão Social" maxlength="250" />
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-lg-2">
            <input type="text" name="produto" id="produto" value="" placeholder="Nome do Produto" maxlength="40"/>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped table-not-edit table-not-view" id="table-filters2">
        <thead>
            <tr>
                <th class="tb_number" style="width: 50px;">Nr. Projeto</th>
                <th>Cliente</th>
                <th>Produto</th>
                <th class="tb_date" style="width: 150px;">Data Requisição</th>
                <th class="td_acao" style="width: 50px;"></th>
                <th class="td_acao" style="width: 50px;"></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
    table_filters_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
        "ordering": false,
        "language": {
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
            "decimal":        ",",
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "text_date", targets: "tb_date" },
            {
                'targets': 'td_acao',
                'class': 'td_acao'
            }
            
        ],
        "order": [[ 0, 'asc' ]]
    }
    table_filters = $(document).find('#table-filters2').DataTable(table_filters_options); 

    $(document).ready( function () {
        filterAjax();
        $(document).find("#btn-filterform").on("click", function(){
            filterClear();
            filterAjax();
        });

        $("#btn-create").on("click", function(){
            showModalCreate();
        });

        function showModalCreate(){
            $.ajax({
                url: '{{ route('produto.novo.modal.adicionar') }}',
                method: 'GET',
                success: function(body){
                    var title = 'Cadastro de {{ CustomView::programaName() }}';
                    createModal('modal_novo_adicionar', title, body, 'modal-lg');
                }
            });
        }
        
        $(document).find("#cliente").autocomplete(optionsAutoCompleteCliente());

        $(document).find("#bt-search-cliente-busca").off("click");
        $(document).find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
            showModalCliente($(this).data("route"));
            return false;
        });

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
    });


    function filterAjax(){
        form = $(document).find("#form_filter");
        data_form = form.serialize();
        filterClear();
        $.ajax({
            url: '{{ route('produto.novo.filter')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                produtos = [];
                
                for (var fields in data.response){
                    temp_array = [
                        createBtView(data.response[fields].num_projeto),
                        ajusteTamanhoTable(data.response[fields].cliente),
                        ajusteTamanhoTable(data.response[fields].descricao),
                        data.response[fields].data_requisicao,
                        createBtFichaTecnica(data.response[fields]),
                        createBtnEdit("{{ route('produto.novo.modal.editar') }}", data.response[fields].id)
                    ];
                    produtos.push(temp_array)
                }
                table_filters.rows.add(produtos).draw();            

            }
        });
    }

    function filterClear(){
        table_filters.clear().draw();
    }

    function createBtFichaTecnica($this){

        if($this.tecido == true ){
            html = "<a href=\"#\" class=\"bt-view\" data-id=\""+$this.projeto_tecidos_id+"\" data-descricao=\""+$this.descricao+"\" data-route=\"{{ route('ficha_tecnica.modal.view_sem_codigo_tecido') }}\"  class=\"link-ficha-tecnica\" title=\"Ficha Técnica\" onClick=\"showModalFichaTecnica($(this));\"></a>";
        
        }else{
            html = "<a href=\"#\" class=\"bt-view\" data-id=\""+$this.projeto_produtos_id+"\" data-descricao=\""+$this.descricao+"\" data-route=\"{{ route('ficha_tecnica.modal.view_sem_codigo') }}\"  class=\"link-ficha-tecnica\" title=\"Ficha Técnica\" onClick=\"showModalFichaTecnica($(this));\"></a>";
        }
        
        return html;
    }

    function createBtnEdit($url, $id){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\" onClick=\"showModal($(this))\"></a>";
        return $html;
    }

    function createBtView($this){

        html = "<a href=\"#\" data-toggle='tooltip' data-html='true' title='Visualizar' onclick=\"abrirProjeto('"+$this+"')\">"+$this+"</a>"
    
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

    function showModal($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_edit_delete', title, body, modal_class);
                var modal = $("#modal_edit_delete");
            }
        });
    }

    function showModalFichaTecnica($this){
        var url = $($this).data("route");
        var id = $($this).data("id");
        var title = "Ficha Tecnica - " + $($this).data("descricao");
        xhr = $.ajax({
            url: url,
            data:{_token: "{{ csrf_token() }}", codigo_produto_projeto: id},
            type: 'POST',
            success: function(body){
                createModal("modal_ficha_tecnica_view", title, body, 'modal-lg');
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

function ajusteTamanhoTable($value){
    $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";

    return $html;
}
@endsection