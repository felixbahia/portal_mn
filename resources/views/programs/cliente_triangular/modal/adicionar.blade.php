@extends('layouts.page-dialog')

@section('content')
<form id="form_add_cliente" name="form_add_cliente">
    @csrf
    <div class="form-row">
        <div class="col-sm">
            <div class="input-group">
                {{ Form::text('cliente_nome_modal', '', ['id' => 'cliente_nome_modal', 'class' => 'form-control input-label', 'placeholder' => 'Cliente']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca-adicionar" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Cadastrar', ['id' => 'btn_add_cliente', 'class' => 'btn btn-success']) }}
    </div>
</form>

<script>
    $(document).ready( function(){
        $(document).find("#cliente_nome_modal").autocomplete(modalAutoCompleteCliente('cliente_nome_modal'));

        $(document).find("#bt-search-cliente-busca-adicionar").off("click");
        $(document).find("#bt-search-cliente-busca-adicionar").on("click", function(event){
            event.stopPropagation();
            showModalClienteBuscaModal($(this).data("route"));
            return false;
        });

        $(document).find('#btn_add_cliente').on('click', function(event){
            event.stopPropagation();

            $.ajax({
                url: "{{ route('cliente_triangular.adicionar') }}",
                dataType: 'json',
                method: 'POST',
                data: $(document).find("#form_add_cliente").serialize(),
                success: function(){
                    $(document).find('#modal_cliente_triangular_adicionar').modal('hide');
                    filterClientesDuvidosos();
                },
                error: function(callback){
                    errors = callback.responseJSON.error;
                    for(var field in errors){
                        limparMesagemErroAdd();
                        showErrorsInputs('#form_add_cliente', field, errors[field]);
                    }
                }
            })
        });
    });

    function modalAutoCompleteCliente($elemento){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", $("#" + $elemento).parents('.modal').css('z-index') + 1);
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Cliente não encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#" + $elemento).val(ui.item.label);
                return false;
            }
        };
    }

    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_add_cliente");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.parent().children().addClass('error-input');
    }

    function showModalClienteBuscaModal(url){
        var title = "Busca de Clientes";
        $.ajax({
            url: url,
            method: "GET",
            data: {
                _token: "{{csrf_token()}}"
            },
            success: function(body){
                $(document).find("#cliente_searsh_show").remove();
                createModal("cliente_searsh_show", title, body, "modal-lg");
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on("draw", function () {
                        modal.find("tbody").find("tr").off("click");
                        modal.find("tbody").find("tr").on("click", function(event){
                            returnDadosClienteBuscaModal($(this), event);
                        });
                    });
                });
            }
        });
    }

    function returnDadosClienteBuscaModal($dados, event){
        if($dados.find("td").eq(0).hasClass("dataTables_empty")){
            return false;
        }
        $(document).find("#cliente_nome_modal").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
    }
</script>
@endsection
