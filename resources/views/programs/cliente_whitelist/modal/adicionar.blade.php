@extends("layouts.page-dialog")

@section("content")
<form id="form_add_cliente" name="form_add_cliente" action="#" onsubmit="return false">
    @csrf
    <div class="form-row">
        <div class="col-sm">
            <div class="input-group">
                {{ Form::text("cliente_nome", "", ["id" => "cliente_nome", "class" => "form-control input-label", "placeholder" => "Cliente"]) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca-adicionar" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button("Enviar", ["id" => "form_add_cliente_btn", "class" => "btn btn-success"]) }}
    </div>
</form>
<script>
    $(document).ready( function(){
        $(document).find("#form_add_cliente").find("#cliente_nome").autocomplete(optionsAutoCompleteCliente());

        $(document).find("#form_add_cliente").find("#bt-search-cliente-busca-adicionar").off("click");
        $(document).find("#form_add_cliente").find("#bt-search-cliente-busca-adicionar").on("click", function(event){
            event.stopPropagation();
            showModalClienteBuscaModal($(this).data("route"));
            return false;
        });

        $(document).find("#form_add_cliente").find("#form_add_cliente_btn").on("click", function(event){
            event.stopPropagation();
            form = $(document).find("#form_add_cliente");
            data_form = form.serialize();
            form.find('.error-message').remove();
            form.find('input, select, span').removeClass('error-input');

            $.ajax({
                url: "{{ route("cliente_whitelist.adicionar") }}",
                dataType: "json",
                method: "POST",
                data: data_form,
                success: function(){
                    $(document).find("#modal_cliente_novo_whitelist").modal("hide");
                    filterAjax();
                },
                error: function(callback){
                    errors = callback.responseJSON.error;
                    
                    for(var field in errors){
                        showErrorsInputs($(document).find("#form_add_cliente"), field, errors[field]);
                    }
                }
            })
        });
    });

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name=\""+input+"\"]");
        $input.parent().after("<label class=\"error-message\" for=\""+input+"\">"+message+"</label>");
        $input.parent().children().addClass("error-input");
    }

    function optionsAutoCompleteCliente(){
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_cliente_novo_whitelist').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum cliente encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#form_add_cliente").find("#cliente_nome").val(ui.item.label);
                return false;
            }
        }
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
        $(document).find("#form_add_cliente").find("#cliente_nome").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
    }

</script>
@endsection
