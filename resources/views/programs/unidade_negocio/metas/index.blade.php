@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="col-lg-6"> 
                {{ Form::text('mes_ano', '', ['id' => 'mes_ano', 'class' => 'form-control data', 'placeholder' => 'Mês/Ano', 'maxlength' => '20']) }}
            </div>
            <div class="col-lg-6"> 
                <div class="input-group" id="unidade_negocio_group">
                    {{ Form::text('unidade_negocio', '', ['id' => 'unidade_negocio', 'class' => 'form-control input-label', 'placeholder' => 'Unidade Negócio', 'maxlength' => '250']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-unidade_negocio"><i class="bt-view m-2"></i></span>
                </div>
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
        <table class="table table-striped" id="table-filters">
            <thead>
                <tr>
                    <th class="tb_date">Mês/Ano</th>
                    <th>Unidade Negócio</th> 
                    <th class="tb_number">Meta</th>
                    <th class="td_acao">Editar</th>
                    <th class="td_acao">Excluir</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <td class="tb_date"></td>
                    <td class="tb_number">Total :</td> 
                    <td class="tb_number" id='total_meta_principal'></td>
                    <td class="td_acao"></td>
                    <td class="td_acao"></td>
                </tr>
            </tfoot>
        </table>
    </div>
@endsection
@section('script-footer')
    $(document).ready(function(){
        form = $(document).find("#form_filter");

        form.find('.data').datepicker({ 
            format: 'mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form.find('.data').mask('00/0000');

        form.find("#unidade_negocio").autocomplete(optionsAutoCompleteUnidadeNegocio(form));
        form.find("#bt-search-unidade_negocio").off('click');
        form.find("#bt-search-unidade_negocio").on('click', function(){
            showModalUnidadeNegocio(form);
        });

        form.find("#btn-create").off("click");
        form.find("#btn-create").on("click",function(){
            showModalCreate();
        });

        form.find("#btn-filterform").off("click");
        form.find("#btn-filterform").on("click", function(){
            filterClear();
            filterAjax();
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
            url: '{{ route('unidade_negocio.metas.modal.adicionar') }}',
            method: 'GET',
            success: function(body){
                var title = 'Cadastro de {{ CustomView::programaName() }}';
                createModal('modal_unidade_negocio_adicionar', title, body, '');
            }
        });
    }

    function filterAjax(){
        filterClear();
        form = $(document).find("#form_filter");
        $(document).find('#total_basecomissao').html('');
        data_form = form.serialize();
        filterClear();
        $.ajax({
            url: '{{ route('unidade_negocio.metas.filter')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                linhas = [];
                
                for (var fields in data.response.metas){
                    temp_array = [
                        data.response.metas[fields].mes_ano,
                        data.response.metas[fields].unidade_negocio,
                        data.response.metas[fields].meta,
                        createBtnEdit("{{ route('unidade_negocio.metas.modal.editar') }}", data.response.metas[fields]),
                        createBtnDelete("{{ route('unidade_negocio.metas.modal.deletar') }}", data.response.metas[fields]),
                    ];
                    linhas.push(temp_array)
                }
                table_filters.rows.add(linhas).draw();

                $(document).find('#total_meta_principal').html(data.response.total_meta);
            }
        });
    }

    function filterClear(){
        table_filters.clear().draw();
    }
    
    function createBtnEdit($url, $value){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$value.id+"\" data-modal=\"\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\" onclick=\"showModal($(this))\"></a>";

        return $html;
    }
    function createBtnDelete($url, $value){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$value.id+"\" data-modal=\"\" data-title_modal=\"Excluir {{ CustomView::programaName() }}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\" onclick=\"showModal($(this))\"></a>";

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
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_unidade_negocio_edit_delete', title, body, modal_class);
                var modal = $("#modal_unidade_negocio_edit_delete");
            }
        });
    }

    function chamadaPopover(){
        $('[data-toggle="popover"]').off('show.bs.popover');
        $('[data-toggle="popover"]').popover('hide');

        $('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            trigger: 'hover',
            placement: 'right',
            template: '<div class="popover popover-estoque" role="popover"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });
    }

    function chamadaTootip(){
        $('[data-toggle="tooltip"]').off('show.bs.tooltip');
        $('[data-toggle="tooltip"]').tooltip('hide');

        $('[data-toggle="tooltip"]').tooltip({
            container: 'body',
            html: true,
            show: true,
            template: '<div class="tooltip tooltip-estoque" role="tooltip"><div class="arrow"></div><h3 class="tooltip-header"></h3><div class="tooltip-body"></div></div>'
        });
    }

    function optionsAutoCompleteUnidadeNegocio(form){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('unidade_negocio.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_unidade_negocio_adicionar').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form.find("#unidade_negocio").val(ui.item.value)
                return false;
            }
        };
    }

    function showModalUnidadeNegocio(form){
        $.ajax({
            url: '{{ route('unidade_negocio.modal.buscar') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_unidade_negocio", "Buscar Usuário", data, 'modal-lg');
                table_modal_buscar_unidade_negocio.on('draw', function () {

                    $(document).find("#table-filters-unidade_negocio").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-unidade_negocio").find('tbody').find("tr").on("click", function(){
                        returnDadosUnidadeNegocio($(this), form);
                    });

                });
            }
        });
    }

    function returnDadosUnidadeNegocio($dados, form){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_unidade_negocio").modal("hide");
        
        form.find('#unidade_negocio').val($dados.find("td").eq(0).text());
    }

@endsection