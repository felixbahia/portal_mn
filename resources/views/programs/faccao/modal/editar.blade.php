@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_fornecedor_edt" id="form_fornecedor_edt" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $dados['id']) !!}
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('fornecedor', 'Fornecedor', []) }} 
            <div class="input-group">
                {{ Form::text('fornecedor', $dados['faccao'], ['id' => 'fornecedor', 'class' => 'form-control input-label', 'placeholder' => 'Fornecedor']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    $(document).ready( function () {
        form_modal_edt = $(document).find('#form_fornecedor_edt');
        form_modal_edt.find("#btn-salvar").on('click', function(){
            inserirDados(form_modal_edt.serialize());
        });
        $(document).find("#bt-search-fornecedor-busca").on("click", function(){
            showModalFornecedor($(this).data("route"), "Lista de Fornecedores");
        });
        $(document).find("#fornecedor").autocomplete(optionsAutoCompleteFornecedor());
    });

    function inserirDados(data_form_modal_edt){
        $.ajax({
            url: "{{ route('faccao.editar') }}", 
            dataType: 'json',
            data: data_form_modal_edt,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal_edt).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroEdt();
                mensagemErroEdt(dados);
            }
        });
    }

    function limparMesagemErroEdt(){      
        var form_modal_edt = $("#form_fornecedor_edt");
        form_modal_edt.find('.error-message').remove();
        form_modal_edt.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroEdt(json_error){
        var form_modal_edt = $("#form_fornecedor_edt");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsEdt(form_modal_edt, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsEdt(form_modal_edt, input, message){
        var $input = $(form_modal_edt).find("#bt-search-fornecedor-busca");
        $(form_modal_edt).find("input[name='fornecedor']").addClass('error-input');
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function showModalFornecedor(url, title){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("fornecedor_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosFornecedor($(this));
                        });
                    });
                });
            }
        });
    }

    function returnDadosFornecedor($this){
        if($this.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#fornecedor_search_show").modal("hide");
        $(document).find("#fornecedor").val($this.find("td").eq(1).text());
    }

    function optionsAutoCompleteFornecedor(){
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_faccao_edit_delete').css('z-index')) + 1));
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
                $(document).find("#fornecedor").val(ui.item.label);
                return false;
            }
        };
    }
</script>
@endsection