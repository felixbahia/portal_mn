@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_fornecedor_add" id="form_fornecedor_add" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id_necessidade_compras', $dados['id_necessidade_compras'], ['id' => 'id_necessidade_compras']) !!}
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('fornecedor', 'Fornecedor', []) }} 
            <div class="input-group">
                {{ Form::text('fornecedor', $dados['fornecedor'], ['id' => 'fornecedor', 'class' => 'form-control input-label', 'placeholder' => 'Fornecedor', 'maxLength' => '250']) }}
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
        form_modal_add = $(document).find('#form_fornecedor_add');
        form_modal_add.find("#btn-salvar").on('click', function(){
            editarDados(form_modal_add.serialize());
        });
        form_modal_add.find("#bt-search-fornecedor-busca").on("click", function(){
            showModalFornecedorModal($(this).data("route"), "Lista de Fornecedores");
        });
        form_modal_add.find("#fornecedor").autocomplete(optionsAutoCompleteFornecedorModal());
    });

    function editarDados(data_form_modal_add){
        $.ajax({
            url: "{{ route('necessidade_compras.editar_fornecedor') }}", 
            dataType: 'json',
            data: data_form_modal_add,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal_add).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
    }

    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_fornecedor_add");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_add = $("#form_fornecedor_add");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_add, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form_modal_add, input, message){
        var $input = $(form_modal_add).find("#bt-search-fornecedor-busca");
        $(form_modal_add).find("input[name='fornecedor']").addClass('error-input');
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function showModalFornecedorModal(url, title){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("fornecedor_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosFornecedorModal($(this));
                        });
                    });
                });
            }
        });
    }

    function returnDadosFornecedorModal($this){
        form_modal_add = $(document).find('#form_fornecedor_add');
        if($this.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#fornecedor_search_show").modal("hide");
        form_modal_add.find("#fornecedor").val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
    }

    function optionsAutoCompleteFornecedorModal(){
        $(document).find(".error-message").remove();
        form_modal_add = $(document).find('#form_fornecedor_add');
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('fornecedor.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_editar_fornecedor').css('z-index')) + 1));
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
                form_modal_add.find("#fornecedor").val(ui.item.label);
                return false;
            }
        };
    }
</script>
@endsection