@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <select name="estabel" id="estabel">
                <option value="">Estábelecimento</option>
                @foreach(returnEmpresasPrologusView() as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2">
            <input type="text" name="codcad" id="codcad" value="" placeholder="Código" maxlength="20" />
        </div>
        <div class="col-lg-3">
            <input type="text" name="nome_codcad" id="nome_codcad" value="" placeholder="Nome" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="conta_contabil" id="conta_contabil" value="" placeholder="Conta Contábil" maxlength="250" />
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <button type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear">Limpar busca</button>
        <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped table-fornecedor" id="table-filters">
        <thead>
            <tr>
                <th>Estábelecimento</th>
                <th>Código</th>
                <th>Nome</th>
                <th>Conta Contábil</th>
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
<div class="modal fade" id="model_fornecedor_contabil_add" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
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
<div class="modal fade" id="model_fornecedor_contabil_edit" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
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
<div class="modal fade" id="model_fornecedor_contabil_delete" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="ModalLabel">Excluir {{ CustomView::programaName() }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          Deseja excluir o {{ strtolower(CustomView::programaName()) }} <b></b>?
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
        $("#model_fornecedor_contabil_add").modal("toggle");
        $("#model_fornecedor_contabil_add").off('shown.bs.modal');
        $("#model_fornecedor_contabil_add").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: "{{ route('fornecedor_contabil.create') }}",
                success: function(data){
                    modal.find('.modal-body').html(data);
                    ajaxForm($("#model_fornecedor_contabil_add"));
                }
            });
        });
        $("#model_fornecedor_contabil_add").off('hidden.bs.modal');
        $("#model_fornecedor_contabil_add").on('hidden.bs.modal', function (e) {
            $("#model_fornecedor_contabil_add").find('.modal-body').html('');
            $("#btn-create").on("click", function(){
                showModalCreate();
            });
        });
    }
    function showModal(url){
        $("#model_fornecedor_contabil_edit").modal("toggle");
        $("#model_fornecedor_contabil_edit").off('shown.bs.modal');
        $("#model_fornecedor_contabil_edit").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: url,
                success: function(data){
                    modal.find('.modal-body').html(data);
                    ajaxForm($("#model_fornecedor_contabil_edit"));
                }
            });
        });
        $("#model_fornecedor_contabil_edit").off('hidden.bs.modal');
        $("#model_fornecedor_contabil_edit").on('hidden.bs.modal', function (e) {
            $("#model_fornecedor_contabil_edit").find('.modal-body').html('');
            $("#btn-create").on("click", function(){
                showModalCreate();
            });
        });
    }
    function showModalDelete($this){
        var $url = $this.data("route");
        $("#model_fornecedor_contabil_delete").modal("toggle");
        $("#model_fornecedor_contabil_delete").off('shown.bs.modal');
        $("#model_fornecedor_contabil_delete").on('shown.bs.modal', function (event) {
            var $modal = $(this);
            $modal.find('.modal-body').find('b').html($this.data("codcad"));
            $("#model_fornecedor_contabil_delete").find('#bt-deleted').off("click");
            $("#model_fornecedor_contabil_delete").find('#bt-deleted').on("click", function(){
                $.ajax({
                    url: $url,
                    dataType: 'json',
                    method: 'POST',
                    data: {_token: "{{ csrf_token() }}"},
                    success: function(data){
                        filterAjax($("#form_filter").serialize());
                        message("Atenção", "Fornecedor excluido com sucesso!");
                        $($modal).modal('hide');
                    }
                });
            });
        });
        $("#model_fornecedor_contabil_delete").off('hidden.bs.modal');
        $("#model_fornecedor_contabil_delete").on('hidden.bs.modal', function (e) {
            $("#model_fornecedor_contabil_delete").find('.modal-body').find('b').html('');
            $("#model_fornecedor_contabil_delete").find('#bt-deleted').off("click");
        });
    }
    function ajaxForm($model){
        $($model).find('#codcad').on("blur", function(event){
            var $this = $(this);
            var form = $(this).parents('form');
            var form_data = form.serialize();
            $($model).find('#codcad_nome').val("");
            $.ajax({
                url: "{{ route('fornecedor_contabil.filter_codcad') }}",
                dataType: 'json',
                data: form_data,
                method: 'POST',
                success: function(data){
                    if(data.status === "error"){
                        message("Atenção", "Nenhum código de fornecedor não encontrado no sistema");
                        $($model).find('#codcad').focus();
                    }else{
                        $($model).find('#codcad_nome').val(data.data);
                    }
                }
            });
        });
        $($model).find('[type="submit"]').on("click", function(event){
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
            url: "{{ route('fornecedor_contabil.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].estabel,
                            data[field].codcad,
                            data[field].nome,
                            data[field].contactb,
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
