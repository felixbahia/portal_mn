@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-3">
            {{ Form::select("vendedor", $vendedor, '', ["class"=>"form-control"]) }}
        </div>
        <div class="col-lg-3">
            <input type="text" class='data' name="data" id="data" value="" placeholder="Data MM/AAAA" maxlength="20" />
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
                <th>Vendedor</th>
                <th>Documento</th>
                <th>Déb./Créd.</th>
                <th>Motivo</th> 
                <th>Data</th>
                <th>Valor</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        $("#btn-create").on("click", function(){
            showModalCreate();
        });
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });
        table_filters.on('draw', function () {
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
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
        })
        $('.data').mask('00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'mm/yyyy',
            zIndex: 100,
            autoHide: true
        });
    });

    function createBtnEdit($url, $id, $editavel){
        if($editavel == false){
            var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Visualizar {{ CustomView::programaName() }}\" class=\"bt-view\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Visualizar\"></a>";
        }
        else{
            var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>";
        }
        return $html;
    }
    function createBtnDelete($url, $id){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Excluir {{ CustomView::programaName() }}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\"></a>";
        return $html;
    }

    function showModalCreate(){
        $.ajax({
            url: '{{ route('lancamento_deb_cred_vendedor.modal.adicionar') }}',
            method: 'POST',
            data: {_token: "{{ csrf_token() }}"},
            success: function(body){
            	var title = 'Cadastro de {{ CustomView::programaName() }}';
    			createModal('lancamento_deb_cred_vendedor_adicionar', title, body, "");
            	var modal = $("#lancamento_deb_cred_vendedor_adicionar");
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
                createModal('lancamento_deb_cred_vendedor_edit_delete', title, body, modal_class);
                var modal = $("#lancamento_deb_cred_vendedor_edit_delete");
            }
        });
    }

    function filterAjax(data_form){
        var $return;
        table_filters.clear().draw();
        var form = $(document).find('#form_filter');
        $.ajax({
            url: "{{ route('lancamento_deb_cred_vendedor.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                var data = callback.response;
                form.find('.error-message').remove();
                form.find('div, input, select, textarea').each(function(){
                    if($(this).hasClass("error-input")){
                        $(this).removeClass("error-input")
                    }
                });
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].vendedor,
                            data[field].documento,
                            data[field].tipo,
                            data[field].motivo,
                            data[field].data,
                            data[field].valor,
                            createBtnEdit("{{ route('lancamento_deb_cred_vendedor.modal.editar') }}", data[field].id, data[field].editavel),
                            createBtnDelete("{{ route('lancamento_deb_cred_vendedor.modal.deletar') }}", data[field].id)
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).order([ 0, 'asc' ] ).draw().nodes();
                }
            },
            error: function(callback){
                hide_loader();
                var errors = callback.responseJSON.error;
                form.find('.error-message').remove();
                form.find('div, input, select, textarea').each(function(){
                    if($(this).hasClass("error-input")){
                        $(this).removeClass("error-input")
                    }
                });
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
                if(form.find('.error-message').length){
                    form.find('.error-message').eq(0).focus();
                }
            }
        })
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
@endsection
