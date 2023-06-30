@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="form-group col-sm-3 col-xl-4">
                <div class="input-group">
                    {{ Form::text('cliente', '', ['id' => 'cliente', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Grupo ou do cliente']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="form-group col-sm-3 col-xl-2">
                <div class="input-group">
                    {{ Form::text('cnpj', '', ['id' => 'cnpj', 'class' => 'form-control', 'placeholder' => 'CNPJ']) }}
                </div>
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
    <table class="table table-striped" id="table-filters2">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Raiz de CNPJ</th>
                <th>Participantes</th>
                <th class="td_acao"></th>
                <th class="td_acao"></th>
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
                'class': 'td_acao',
                'width': '5px',
                "orderable": false
            },
            
        ],
        "order": [[ 0, 'asc' ]]
    };
    table_filters = $(document).find('#table-filters2').DataTable(table_filters_options);
    table_filters.draw();
    $(document).ready( function () {
        
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });
        $("#btn-create").on("click", function(){
            showModalCreate();
        });

        table_filters.on('draw', function () {
            $(document).find(".bt-edit").off("click");
            $(document).find(".bt-edit").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
            $(document).find(".bt-delete").off("click");
            $(document).find(".bt-delete").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });

        $(document).find("#bt-search-cliente-busca").off("click");
        $(document).find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
            showModalClienteIndex($(this).data("route"));
            return false;
        });

    });

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
                createModal('modal_grupo_empresarial_edit_delete', title, body, modal_class);
                var modal = $("#modal_grupo_empresarial_edit_delete");
            }
        });
    }
    function showModalCreate(){
        $.ajax({
            url: '{{ route('grupo_empresarial.modal.adicionar') }}',
            method: 'GET',
            success: function(body){
            	var title = 'Cadastro de {{ CustomView::programaName() }}';
    			createModal('modal_grupo_empresarial', title, body, '');
            }
        });
    }
    function createBtnEdit($url, $dados){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"\" data-title_modal=\"Editar\" class=\"bt-edit\" title=\"Editar\"></a>";
        return $html;
    }
    function createBtnDelete($url, $dados){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"\" data-title_modal=\"Excluir\" class=\"bt-delete\" title=\"Excluir\"></a>";
        return $html;
    }

    function filterAjax(data_form){
        var $return;

        $('.link-participantes').popover('dispose');

        $.ajax({
            url: "{{ route('grupo_empresarial.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                table_filters.clear().draw();
                if(callback.status == 'success'){
                    var data = callback.response;
                    table_filters.clear().draw();
                    if(data.length > 0){
                        var fields_filter = [];
                        for(var field in data){
                            var temp_field = [
                                data[field].nome,
                                data[field].raiz_cnpj,
                                "<div><a tabindex='0' data-toggle='popover' data-html='true' data-trigger='focus' class='link-participantes' title='Participantes' data-content='" + data[field].participantes +"'>Clique para exibir os participantes</a></div>",
                                createBtnEdit("{{ route('grupo_empresarial.modal.editar') }}", data[field]),
                                createBtnDelete("{{ route('grupo_empresarial.modal.deletar') }}", data[field])
                            ];
                            fields_filter.push(temp_field);
                        }
                        table_filters.rows.add(fields_filter).draw().nodes();

                        $('.link-participantes').popover({
                            container: '#table-filters2',
                            trigger: 'focus'
                        });
                    }
                }
            }
        });
    }

    function showModalClienteIndex(url){
		var title = "Busca de Clientes";
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: '{{ csrf_token() }}'},
            success: function(body){
				$(document).find('#cliente_searsh_show').remove();
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("td").not('.th_view').off("click");
                        modal.find('tbody').find("td").not('.th_view').on("click", function(event){
                            returnDadosClienteIndex($(this).parent('tr'), event);
                        });
                    });
                });
            }
        });
    }

    function returnDadosClienteIndex($dados, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente").val($dados.find("td").eq(1).text());
        $(document).find("#cliente_searsh_show").modal("hide");
    }
@endsection
