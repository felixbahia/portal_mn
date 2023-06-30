@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="col-lg-6">
                <input type="text" name="unidade" id="unidade" value="" placeholder="Unidade" maxlength="250" require/>
            </div>
            <div class="col-lg-6">
                <div class="input-group" id="cod_cliente_group">
                    <input type="text" name="usuario_responsavel" value="" id="usuario_responsavel" class="form-control input-label" placeholder="Gerente da Unidade" maxlength="250">
                    <span class="input-group-addon border rounded-right" id="bt-search-usuario"><i class="bt-view m-2"></i></span>
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
                    <th>Unidade</th> 
                    <th>Gerente</th>
                    <th>Editar</th>
                    <th>Excluir</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
@endsection
@section('script-footer')
    $(document).ready(function(){
        form = $(document).find("#form_filter");

        form.find("#btn-create").off("click");
        form.find("#btn-create").on("click",function(){
            showModalCreate();
        });

        form.find("#bt-search-usuario").off('click');
        form.find("#bt-search-usuario").on('click', function(){
            showModalUsuario(form);
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
                }
            ],
        });
    });

    function showModalCreate(){
        $.ajax({
            url: '{{ route('unidade_negocio.modal.adicionar') }}',
            method: 'GET',
            success: function(body){
                var title = 'Cadastro de {{ CustomView::programaName() }}';
                createModal('modal_unidade_negocio_adicionar', title, body, '');
            }
        });
    }

    function showModalUsuario(form){
        $.ajax({
            url: '{{ route('usuario.modal.buscar') }}',
            type: 'GET',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_usuario", "Buscar Usuário", data, 'modal-lg');
                table_modal_buscar_user.on('draw', function () {

                    $(document).find("#table-filters-user").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-user").find('tbody').find("tr").on("click", function(){
                        returnDadosUsuarioModal($(this), form);
                    });

                });
            }
        });
    }

    function returnDadosUsuarioModal($dados, form){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_usuario").modal("hide");
        
        form.find('#usuario_responsavel').val($dados.find("td").eq(0).text());
    }

    function filterAjax(){
        filterClear();
        form = $(document).find("#form_filter");
        data_form = form.serialize();
        filterClear();
        $.ajax({
            url: '{{ route('unidade_negocio.filtro')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                linhas = [];
                
                for (var fields in data.response){
                    temp_array = [
                        data.response[fields].unidade,
                        data.response[fields].usuario,
                        createBtnEdit("{{ route('unidade_negocio.modal.editar') }}", data.response[fields].id),
                        createBtnDelete("{{ route('unidade_negocio.modal.deletar') }}", data.response[fields].id),
                    ];
                    linhas.push(temp_array)
                }
                table_filters.rows.add(linhas).draw();            
    
            }
        });
    }
    
    function filterClear(){
        table_filters.clear().draw();
    }

    function createBtnEdit($url, $id){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\" onclick=\"showModal($(this))\"></a>";
        return $html;
    }
    function createBtnDelete($url, $id){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Excluir {{ CustomView::programaName() }}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\" onclick=\"showModal($(this))\"></a>";
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

@endsection