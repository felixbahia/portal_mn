@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <input type="text" name="nome" id="nome" value="" placeholder="Nome" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="email" name="email" id="email" value="" placeholder="E-mail" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="setor" id="setor" value="" placeholder="Setor" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <select name="tipo" id="tipo">
                <option value="">Tipo de usuário</option>
                @foreach($tipos as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2">
            <select name="empresa_padrao" id="empresa_padrao">
                <option value="">Empresa Padrão</option>
                @foreach(returnEmpresasNasajonView() as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2">
            <select name="codigo_vendedor" id="codigo_vendedor">
                <option value="">Código representante</option>
                @foreach($vendedor as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
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
    <table class="table table-striped" id="table-filters-users">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Setor</th>
                <th>Tipo</th>
                <th>Perfil de Acesso</th>
                <th>Responsável</th>
                <th>Código representante</th>
                <th>Login</th>
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
                showModal($(this).data('route'), $(this).data('id'));
            });
            $(document).find(".bt-delete").off("click");
            $(document).find(".bt-delete").on("click", function(event){
                event.stopPropagation();
                showModalDelete($(this).data('route'), $(this).data('id'));
            });
            $(document).find(".bt-login").off("click");
            $(document).find(".bt-login").on("click", function(event){
                event.stopPropagation();
                login($(this).data('route'), $(this).data('id'));
            });
        });
    });

    table_filters = $('#table-filters-users').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
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
                "targets": 8,
                className: 'text-center'
            }
        ],
        "order": [[ 0, 'asc' ], [ 2, 'asc' ]]
    });

    function showModalCreate(){
        $.ajax({
            url: "{{ route('usuario.create') }}",
            data: {_token: '{{ csrf_token() }}'},
            method: 'POST',
            success: function(data){
                createModal("modal_user_add", "Criar novo usuário", data, 'modal-lg')
                ajaxForm($("#modal_user_add"));
            }
        });
        $("#modal_user_add").off('hidden.bs.modal');
        $("#modal_user_add").on('hidden.bs.modal', function (e) {
            $("#modal_user_add").find('.modal-body').html('');
            $("#btn-create").on("click", function(){
                showModalCreate();
            });
        });
    }

    function showModal(url, id){
        $.ajax({
            url: url,
            data: {_token: '{{ csrf_token() }}', id:id },
            method: 'POST',
            success: function(data){
                createModal("modal_user_edit", "Editar novo usuário", data, 'modal-lg')
                ajaxForm($("#modal_user_edit"));
            }
        });
        $("#modal_user_edit").off('hidden.bs.modal');
        $("#modal_user_edit").on('hidden.bs.modal', function (e) {
            $("#modal_user_edit").find('.modal-body').html('');
            $("#btn-create").on("click", function(){
                showModal();
            });
        });
    }

    function showModalDelete(url, id){
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: '{{ csrf_token() }}', id:id },
            success: function(data){
                createModal("modal_user_delete", "Excluir usuário", data)
                ajaxForm($("#modal_user_delete"));
            }
        });
        $("#modal_user_delete").off('hidden.bs.modal');
        $("#modal_user_delete").on('hidden.bs.modal', function (e) {
            $("#modal_user_delete").find('.modal-body').html('');
            $("#btn-create").on("click", function(){
                showModalDelete();
            });
        });
    }

    function ajaxForm($modal){
        $($modal).find('[type="submit"]').off("click");
        $($modal).find('[type="submit"]').on("click", function(event){
            event.stopPropagation();
            var form = $(this).parents('form');
            var form_data = form.serialize();
            var url = form.attr("action");
            $.ajax({
                url: url,
                dataType: 'json',
                data: form_data,
                method: 'POST',
                success: function(data){
                    $($modal).modal('hide');
                    filterAjax($("#form_filter").serialize());
                    message("Atenção", "Dados salvos com sucesso!");
                },
                error: function(data){
                    hide_loader();
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }
            });
        });
        $($modal).find("select[name='tipo_usuario_id']").off("change");
        $($modal).find("select[name='tipo_usuario_id']").on("change", function(event){
            event.stopPropagation();
            $($modal).find("#responsavel").html("");
            $($modal).find("#responsavel").parent().hide();

            var $user = $($modal).find('input[name="id"]').val();
            var $this = $(this);
            $.ajax({
                url: '{{ route('usuario.filter.responsavel') }}',
                dataType: 'json',
                data: {_token: '{{ csrf_token() }}', tipo_usuario:$($this).val(), user:$user },
                method: 'POST',
                success: function(callback){
                    if(callback.status === "success"){
                        var $html = "<option value=\"\"></option>";
                        var data = callback.data;
                        $.each(data, function(){
                            $html += "<option value=\""+this.id+"\">"+this.name+" - "+this.tipo_usuario+"</option>";
                        });
                        $($modal).find("#responsavel").parent().show();
                        $($modal).find("#responsavel").html($html);
                    }
                },
                error: function(data){
                    hide_loader();
                }
            });
        });
    }
    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
    function filterAjax(data_form){
        var $return;
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('usuario.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].name,
                            data[field].email,
                            data[field].setor,
                            data[field].tipo_usuario,
                            data[field].perfil_nome,
                            data[field].responsavel,
                            data[field].codigo_representante,
                            data[field].login,
                            data[field].edit,
                            data[field].delete
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).draw().nodes();
                    $(document).find(".bt-edit").off("click");
                    $(document).find(".bt-edit").on("click", function(event){
                        event.stopPropagation();
                        showModal($(this).data('route'), $(this).data('id'));
                    });
                    $(document).find(".bt-delete").off("click");
                    $(document).find(".bt-delete").on("click", function(event){
                        event.stopPropagation();
                        showModalDelete($(this).data('route'), $(this).data('id'));
                    });
                    $(document).find(".bt-login").off("click");
                    $(document).find(".bt-login").on("click", function(event){
                        event.stopPropagation();
                        login($(this).data('route'), $(this).data('id'));
                    });
                }
            }
        });
    }

    function login(rota, id){
        $('<form action="'+rota+'" method="POST" >\
        <input type="hidden" name="_token" value="{{ csrf_token() }}">\
        <input type="hidden" name="usuario" value="'+id+'">\
        </form>').appendTo('body').submit();
    }
@endsection