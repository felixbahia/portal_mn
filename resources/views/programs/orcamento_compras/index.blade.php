@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        {!! Form::hidden('tipo_todos', 'compras', ['id' => 'tipo_todos']) !!}
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class='form-row'>
            <div class="col-lg-2"> 
                {{ Form::select('modo', $modos, '', ['id' => 'modo', 'class' => 'form-control', 'placeholder' => 'Selecione o Modo']) }}
            </div>
            <div class="col-lg-2"> 
                {{ Form::select('tipo', $tipos, '', ['id' => 'tipo', 'class' => 'form-control', 'placeholder' => 'Selecione o Tipo']) }}
            </div>
            <div class="col-lg-6">
                <div class="input-group">
                    {{ Form::text('fornecedor_filtro', '', ['id' => 'fornecedor_filtro', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Fornecedor', 'onkeyup' => "optionsFornecedorFiltro($(this))"]) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-fornecedor_filtro-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-2"> 
                {{ Form::text('mes_ano', '', ['id' => 'mes_ano', 'class' => 'form-control data', 'placeholder' => 'Mês/Ano', 'maxlength' => '20']) }}
            </div>
        </div>
        <br>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
            <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
        </div>
    </form>
@endsection
@section('content')
    <div class="content-table">
        <table class="table table-striped" id="table-filters">
            <thead>
                <tr>
                    <th>Modo</th>
                    <th>Tipo</th>
                    <th>Fornecedor</th>
                    <th class="tb_date">Mês/Ano</th>
                    <th class="tb_number">Valor</th>
                    <th class="tb_date">Origem</th>
                    <th class="td_acao"></th>
                    <th class="td_acao"></th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="tb_number"><div class="text-right">Total :</div></td>
                    <td class="tb_number" id='total_nacional'></td> 
                    <td></td>
                    <td class="td_acao"></td>
                    <td class="td_acao"></td>
                </tr>
            </tfoot>
        </table>
    </div>
@endsection
@section('script-footer')
    $(document).ready(function(){
        form_filter = $(document).find("#form_filter");

        form_filter.find('.data').datepicker({ 
            format: 'mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form_filter.find('.data').mask('00/0000');

        form_filter.find("#btn-create").off("click");
        form_filter.find("#btn-create").on("click",function(){
            showModalCreate();
        });

        form_filter.find("#btn-filterform").off("click");
        form_filter.find("#btn-filterform").on("click", function(){
            filterClear();
            filterAjax();
        });

        $(document).find("#bt-search-fornecedor_filtro-busca").on("click", function(){
            showModalFornecedorFiltro($(this).data("route"), "Lista de Fornecedores", "fornecedor_filtro");
        });

        table_filters.destroy();
        table_filters = $('#table-filters').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "autoWidth": false,
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
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    "orderable": false
                },
                {
                    'targets': 'tb_number',
                    'class': 'tb_number',
                },
                {
                    'targets': 'tb_date',
                    'class': 'tb_date',
                }
            ],
        });
    });

    function showModalCreate(){
        $.ajax({
            url: '{{ route('orcamento_compras.modal.adicionar') }}',
            data: {_token: "{{ csrf_token() }}", tipo: 'compras'},
            method: 'GET',
            success: function(body){
                var title = 'Cadastro de {{ CustomView::programaName() }}';
                createModal('modal_orcamento_compras', title, body, 'modal-md');
            }
        });
    }

    function filterAjax(){
        filterClear();
        form_filter = $(document).find("#form_filter");
        data_form_filter = form_filter.serialize();
        $.ajax({
            url: '{{ route('orcamento_compras.filtro')}}',
            data: data_form_filter,
            method: 'POST',
            success: function(data){
                linhas = [];
                
                for (var fields in data.response.orcamentos){
                    temp_array = [
                        data.response.orcamentos[fields].modo,
                        data.response.orcamentos[fields].tipo,
                        data.response.orcamentos[fields].fornecedor,
                        data.response.orcamentos[fields].mes_ano,
                        data.response.orcamentos[fields].valor,
                        createBtnEditOrigem("{{ route('orcamento_compras.modal.editar') }}", data.response.orcamentos[fields]),
                        createBtnEdit("{{ route('orcamento_compras.modal.editar') }}", data.response.orcamentos[fields]),
                        createBtnDelete("{{ route('orcamento_compras.modal.deletar') }}", data.response.orcamentos[fields]),
                    ];
                    linhas.push(temp_array)
                }
                table_filters.rows.add(linhas).draw();

                $(document).find('#total_nacional').html(data.response.total.valor);
                $(document).find('#total_importado').html(data.response.total.importado_valor);
            }
        });
    }

    function filterClear(){
        table_filters.clear().draw();
    }
    
    function createBtnEdit($url, $value){
        var $html = '';
        if($value.liberacao_edicao_exclusao === true){
            if($value.modo_codigo != 'fluxo_caixa'){
                var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$value.id+"\" data-modal=\"modal-md\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\" onclick=\"showModal($(this))\"></a>";
            }
        }        
        
        return $html;
    }

    function createBtnEditOrigem($url, $value){
        var $html = '';
        if($value.liberacao_edicao_exclusao === true){
            if($value.origem_id != ''){
                $html = "<div><a href=\"#\" data-route=\""+$url+"\" data-id=\""+$value.origem_id+"\" data-modal=\"modal-md\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit-direita\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\" onclick=\"showModal($(this))\"></a>"+$value.origem+"</div>";
            }
        }
        
        return $html;
    }

    function createBtnDelete($url, $value){
        var $html = '';
        if($value.liberacao_edicao_exclusao === true){
            if($value.modo_codigo != 'fluxo_caixa'){
                $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$value.id+"\" data-modal=\"\" data-title_modal=\"Excluir {{ CustomView::programaName() }}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\" onclick=\"showModal($(this))\"></a>";
            }
        }

        return $html;
    }

    function showModal($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id, tipo: 'compras'},
            success: function(body){
                createModal('modal_orcamento_compras_edit_delete', title, body, modal_class);
                var modal = $("#modal_orcamento_compras_edit_delete");
            }
        });
    }

    function showModalFornecedorFiltro(url, title, campo){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("fornecedor_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosFornecedorFiltro($(this), campo);
                        });
                    });
                });
            }
        });
    }
    function returnDadosFornecedorFiltro($this, campo){
        if($this.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#fornecedor_search_show").modal("hide");
        form_filter.find("#"+campo).val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
    }

    function optionsFornecedorFiltro($this){
        esconderPopoverTooltip();
        $this.autocomplete(optionsAutoCompleteFornecedorFiltro($this));   
    }

    function optionsAutoCompleteFornecedorFiltro($this){
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
                $this.val(ui.item.label);
                return false;
            }
        };
    }

@endsection