@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-4">
            <input type="text" name="nome" id="nome" value="" placeholder="Nome" maxlength="250" />
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
    <table class="table table-striped table-filter-pefil-acesso" id="table-filters">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Usuários Vinculados</th>
                <th>Editar</th>
                <th>Excluir</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('content-modal')
<div class="modal fade" id="model_perfil_acesso_add" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel">Adicionar {{ CustomView::programaName()  }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="model_perfil_acesso_edit" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel">Editar {{ CustomView::programaName()  }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="model_perfil_acesso_view_users" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel">Visualizar Usuários com o {{ CustomView::programaName()  }} <span></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="model_view_user" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel">Visualizar Usuário</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="model_add_users" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel">Adicionar Usuário</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="model_perfil_acesso_delete" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel">Excluir {{ CustomView::programaName() }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Deseja excluir o  {{ strtolower(CustomView::programaName()) }}<b></b>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="bt-deleted">Excluir</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script-footer')
var $tree_permissoes;
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
                showModal($(this).data('route'));
            });
            $(document).find(".bt-delete").off("click");
            $(document).find(".bt-delete").on("click", function(event){
                event.stopPropagation();
                showModalDelete($(this));
            });
            $(document).find(".bt-view-users").off("click");
            $(document).find(".bt-view-users").on("click", function(event){
                event.stopPropagation();
                showModalList($(this));
            });
        });
    });
    function showViewUser($this){
        var $url = $this.data("route");
        $("#model_view_user").modal("toggle");
        $("#model_view_user").off('shown.bs.modal');
        $("#model_view_user").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: $url,
                success: function(data){
                    modal.find('.modal-body').html(data);
                    ajaxForm($("#model_view_user"));
                }
            });
        });
        $("#model_view_user").off('hidden.bs.modal');
        $("#model_view_user").on('hidden.bs.modal', function (e) {
            $("#model_view_user").find('.modal-body').html('');
            $("#model_view_user").find(".modal-title").find("span").html('');
        });
    }
    function showModalCreate(){
        $("#model_perfil_acesso_add").modal("toggle");
        $("#model_perfil_acesso_add").off('shown.bs.modal');
        $("#model_perfil_acesso_add").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: "{{ route('perfil.create') }}",
                success: function(data){
                    modal.find('.modal-body').html(data);
                    ajaxForm($("#model_perfil_acesso_add"));
                }
            });
        });
        $("#model_perfil_acesso_add").off('hidden.bs.modal');
        $("#model_perfil_acesso_add").on('hidden.bs.modal', function (e) {
            $("#model_perfil_acesso_add").find('.modal-body').find('#tree_permissoes').jstree("destroy");
            $("#model_perfil_acesso_add").find('.modal-body').html('');
            $("#btn-create").on("click", function(){
                showModalCreate();
            });
        });
    }
    function showModal($url){
        $("#model_perfil_acesso_edit").modal("toggle");
        $("#model_perfil_acesso_edit").off('shown.bs.modal');
        $("#model_perfil_acesso_edit").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: $url,
                success: function(data){
                    modal.find('.modal-body').html(data);
                    ajaxForm($("#model_perfil_acesso_edit"));
                }
            });
        });
        $("#model_perfil_acesso_edit").off('hidden.bs.modal');
        $("#model_perfil_acesso_edit").on('hidden.bs.modal', function (e) {
            $("#model_perfil_acesso_edit").find('.modal-body').html('');
            $("#btn-create").on("click", function(){
                showModalCreate();
            });
        });
    }
    function showModalList($this){
        var $url = $this.data("route");
        $("#model_perfil_acesso_view_users").find(".modal-title").find("span").html('');
        $("#model_perfil_acesso_view_users").find(".modal-title").find("span").html($this.data("nome"));
        $("#model_perfil_acesso_view_users").data("id", $this.data("id"));
        $("#model_perfil_acesso_view_users").modal("toggle");
        $("#model_perfil_acesso_view_users").off('shown.bs.modal');
        $("#model_perfil_acesso_view_users").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: $url,
                success: function(data){
                    modal.find('.modal-body').html(data);
                    ajaxForm($("#model_perfil_acesso_view_users"));
                }
            });
        });
        $("#model_perfil_acesso_view_users").off('hidden.bs.modal');
        $("#model_perfil_acesso_view_users").on('hidden.bs.modal', function (e) {
            $("#model_perfil_acesso_view_users").find('.modal-body').html('');
            $("#model_perfil_acesso_view_users").find(".modal-title").find("span").html('');
        });
    }
    function showModalAddUser($this){
        var $url = "{{ route("usuario.filter-ajax") }}";
        $("#model_add_users").modal("toggle");
        $("#model_add_users").data("id", $this.data("id"));
        $("#model_add_users").off('shown.bs.modal');
        $("#model_add_users").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: $url,
                success: function(data){
                    modal.find('.modal-body').html(data);
                    modal.find('.modal-body').find("#role_id").val($this.data("id"));
                    ajaxForm($("#model_add_users"));
                }
            });
        });
        $("#model_add_users").off('hidden.bs.modal');
        $("#model_add_users").on('hidden.bs.modal', function (e) {
            $("#model_add_users").find('.modal-body').html('');
            $("#model_add_users").find(".modal-title").find("span").html('');
        });
    }
    function showModalDelete($this){
        var $url = $this.data("route");
        $("#model_perfil_acesso_delete").modal("toggle");
        $("#model_perfil_acesso_delete").on('shown.bs.modal', function (event) {
            var modal = $(this);
            modal.find('.modal-body').find('b').html($this.data("nome"));
            $("#model_perfil_acesso_delete").find('#bt-deleted').off("click");
            $("#model_perfil_acesso_delete").find('#bt-deleted').on("click", function(){
                $.ajax({
                    url: $url,
                    dataType: 'json',
                    method: 'POST',
                    data: {_token: "{{ csrf_token() }}"},
                    success: function(data){
                        $(modal).modal('hide');
                        filterAjax($("#form_filter").serialize());
                        message("Atenção", "Perfis de Acesso excluido com sucesso!");
                    }
                });
            });
        });
        $("#model_perfil_acesso_delete").off('hidden.bs.modal');
        $("#model_perfil_acesso_delete").on('hidden.bs.modal', function (e) {
            $("#model_perfil_acesso_delete").find('.modal-body').find('b').html('');
            $("#model_perfil_acesso_delete").find('#bt-deleted').off("click");
        });
    }
    function addUserRole($modal, $id_role, $id_user){
        $.ajax({
            url: "{{ route('perfil.add_usuarios') }}",
            dataType: 'json',
            data: {_token: "{{ csrf_token() }}", role: $id_role, user: $id_user},
            method: 'POST',
            success: function(data){
                $($modal).modal('hide');
                getTableUser($id_role);
                message("Atenção", "Dados salvos com sucesso!");
            },
            error: function(data){
                var errors = data.responseJSON.errors;
                message("Atenção", "Ocorreu uma instabilidade tente novamente mais tarde!");
            }
        });
    }
    function ajaxForm($modal){
        $($modal).find('#add_user').on("click", function(event){
            event.stopPropagation();
            showModalAddUser($modal);
        });
        $($modal).find("#table-users-role").find(".bt-view").on("click", function(event){
            event.stopPropagation();
            showViewUser($(this));
        })
        $($modal).find("#table-filters-ajax-user tbody").on("click", "tr", function(event){
            event.stopPropagation();
            var id_user = table_ajax_user.row(this).data()[0];
            var role_exist = table_ajax_user.row(this).data()[1];
            var nome_usuario = table_ajax_user.row(this).data()[2];
            var id_role = $modal.data("id");
            if(parseInt(role_exist) === 1){
                message_option("Atenção", "O usuário "+nome_usuario+" já vinculado a outro perfil. Deseja trocar o perfil de acesso deste usuario?", "default", "add_user_ajax", {modal: $modal,role: id_role,user: id_user});
                $(document).on("add_user_ajax", function($this, dados){
                    addUserRole(dados.modal, dados.role, dados.user);
                    $(document).off("add_user_ajax");
                });
            }else{
                addUserRole($modal, id_role, id_user);
            }
        });
        $($modal).find('[type="submit"]').on("click", function(event){
            event.stopPropagation();
            var form = $(this).parents('form');
            var url = form.attr("action");
            dados = $($modal).find('#tree_permissoes').jstree(true).get_json('#', {flat:true});
            var checks = [];

            $.each(dados, function(){
                var temp_return = {};
                temp_return.id = this.id;
                temp_return.text = this.text;
                if(this.state.selected == true){
                    checks.push(this.id);
                    if(this.parent != "#" && !checks.includes(this.parent)){
                        checks.push(this.parent);
                    }
                }
            });
            $.ajax({
                url: url,
                dataType: 'json',
                data: {_token: "{{ csrf_token() }}", permissoes: checks, nome: form.find("[name='nome']").val()},
                method: 'POST',
                success: function(data){
                    $($modal).modal('hide');
                    filterAjax($("#form_filter").serialize());
                    message("Atenção", "Dados salvos com sucesso!");
                },
                error: function(data){
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }
            });
        });
    }
    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
    function getTableUser(id){
        var $return;
        table_lancamentos.clear().draw();
        $.ajax({
            url: "{{ route('perfil.get_table_user') }}",
            dataType: 'json',
            data: {_token: "{{ csrf_token() }}", role: id},
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].nome,
                            data[field].usuario,
                            data[field].view
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_lancamentos.rows.add(fields_filter).draw().nodes();
                }
            }
        });
    }
    function filterAjax(data_form){
        var $return;
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('perfil.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].name,
                            data[field].usuario_vinculados,
                            data[field].edit,
                            data[field].delete
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).draw().nodes();
                    $(document).find(".bt-edit").off("click");
                    $(document).find(".bt-edit").on("click", function(event){
                        event.stopPropagation();
                        showModal($(this).data('route'));
                    });
                    $(document).find(".bt-delete").off("click");
                    $(document).find(".bt-delete").on("click", function(event){
                        event.stopPropagation();
                        showModalDelete($(this));
                    });
                    $(document).find(".bt-view-users").off("click");
                    $(document).find(".bt-view-users").on("click", function(event){
                        event.stopPropagation();
                        showModalList($(this));
                    });
                }
            }
        });
    }
@endsection
