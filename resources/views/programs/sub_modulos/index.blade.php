@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-4">
            <input type="text" name="nome" id="nome" value="" placeholder="Nome" maxlength="250" />
        </div>
        <div class="col-lg-4">
            <select name="modulo" id="modulo">
                <option value="">Módulo</option>
                @foreach($modulos as $modulo)
                <option value="{{ $modulo->id }}">{{ $modulo->nome }}</option>
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
    <table class="table table-striped" id="table-filters">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Módulo</th>
                <th>Sub-Módulo</th>
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
<div class="modal fade" id="model_modulo_add" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="ModalLabel">Adicionar {{ CustomView::programaName() }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="model_modulo_edit" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="ModalLabel">Editar {{ CustomView::programaName() }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="model_modulo_delete" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel">Excluir {{ CustomView::programaName() }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Deseja excluir os dados abaixo?</p>
                <hr />
                <p id="delete_nome">Nome: <b></b></p>
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
    $(document).ready( function () {
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });
        $("#btn-create").on("click", function(){
            showModalCreate();
        });
        table_filters.on('draw', function () {
            $('#table-filters').find(".bt-edit").off("click");
            $('#table-filters').find(".bt-edit").on("click", function(event){
                event.stopPropagation();
                showModal($(this).data('route'));
            });
            $('#table-filters').find(".bt-delete").off("click");
            $('#table-filters').find(".bt-delete").on("click", function(event){
                event.stopPropagation();
                showModalDelete($(this));
            });
        });
    });
    function showModalCreate(){
        $("#model_modulo_add").modal("toggle");
        $("#model_modulo_add").off('shown.bs.modal');
        $("#model_modulo_add").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: "{{ route('submodulos.create') }}",
                success: function(data){
                    modal.find('.modal-body').html(data);
                    ajaxForm($("#model_modulo_add"));
                }
            });
        });
        $("#model_modulo_add").off('hidden.bs.modal');
        $("#model_modulo_add").on('hidden.bs.modal', function (e) {
            $("#model_modulo_add").find('.modal-body').html('');
            $("#btn-create").on("click", function(){
                showModalCreate();
            });
        });
    }
    function showModal(url){
        $("#model_modulo_edit").modal("toggle");
        $("#model_modulo_edit").off('shown.bs.modal');
        $("#model_modulo_edit").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: url,
                success: function(data){
                    modal.find('.modal-body').html(data);
                    ajaxForm($("#model_modulo_edit"));
                }
            });
        });
        $("#model_modulo_edit").off('hidden.bs.modal');
        $("#model_modulo_edit").on('hidden.bs.modal', function (e) {
            $("#model_modulo_edit").find('.modal-body').html('');
            $("#btn-create").on("click", function(){
                showModalCreate();
            });
        });
    }
    function showModalDelete($this){
        var $url = $this.data("route");
        $("#model_modulo_delete").modal("toggle");
        $("#model_modulo_delete").off('shown.bs.modal');
        $("#model_modulo_delete").on('shown.bs.modal', function (event) {
            var $modal = $(this);
            $modal.find('.modal-body').find('p#delete_nome').find('b').html($this.data("nome"));
            $("#model_modulo_delete").find('#bt-deleted').off("click");
            $("#model_modulo_delete").find('#bt-deleted').on("click", function(){
                $.ajax({
                    url: $url,
                    dataType: 'json',
                    method: 'POST',
                    data: {_token: "{{ csrf_token() }}"},
                    success: function(data){
                        $($modal).modal('hide');
                        filterAjax($("#form_filter").serialize());
                        message("Atenção", "Módulo excluido com sucesso!");
                    }
                });
            });
        });
        $("#model_modulo_delete").off('hidden.bs.modal');
        $("#model_modulo_delete").on('hidden.bs.modal', function (e) {
            $("#model_modulo_delete").find('.modal-body').find('b').html('');
            $("#model_modulo_delete").find('#bt-deleted').off("click");
        });
    }
    function ajaxForm($model){

        $($model).find("#modulos_id").on('change', function(event){
            event.stopPropagation();
            var $this = $(this);
            var $modulo = $this.val();
            if($modulo == ""){
                $("#sub_modulos_id").html("");
                $("#sub_modulos_id").append("<option value=\"\"></option>");
                return false;
            }
            $.ajax({
                url: "{{ route('submodulos.modulo_id') }}",
                dataType: 'json',
                data: {_token: "{{ csrf_token() }}", modulo: $modulo},
                method: 'POST',
                success: function(data){
                    if(data.submodulos){
                        $("#sub_modulos_id").html("");
                        $("#sub_modulos_id").append("<option value=\"\"></option>");
                        var submodulos = data.submodulos;
                        for(var field in submodulos){
                            $("#sub_modulos_id").append("<option value=\""+submodulos[field].id+"\">"+submodulos[field].nome+"</option>");
                        }
                    }
                },
                error: function(data){
                    var errors = data.responseJSON.errors;
                }
            });
        });
        $($model).find('[type="submit"]').on("click", function(event){
            event.stopPropagation();
            var form = $(this).parents('form');
            var url = form.attr("action");
            $.ajax({
                url: url,
                dataType: 'json',
                data: new FormData(form[0]),
                method: 'POST',
                processData: false,
                contentType: false, 
                cache:  false,
                success: function(data){
                    $($model).modal('hide');
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
        var $input = $(form).find("input[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
    function filterAjax(data_form){
        var $return;
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('submodulos.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].nome,
                            data[field].modulo,
                            data[field].submodulo,
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
                }
            }
        });
    }
@endsection
