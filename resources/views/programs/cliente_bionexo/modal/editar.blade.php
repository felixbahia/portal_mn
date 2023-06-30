@extends('layouts.page-dialog')

@section('content')
<form action="#" id="form_edit_cliente" name="form_edit_cliente" onsubmit="return false;">
    <div class="form-row">
        <div class="col-sm">
            <div class="input-group">
            @csrf
                {{ Form::hidden('id', $cliente['id'], ['id' => 'id']) }}
                {{ Form::text('cliente_nome_modal', $cliente['nome_razao'] . ' - ' . $cliente['cpf_cnpj'], ['id' => 'cliente_nome_modal_edit', 'class' => 'form-control input-label', 'placeholder' => 'Cliente']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca-editar" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Enviar', ['id' => 'form_edit_cliente_btn', 'class' => 'btn btn-success']) }}
    </div>
</form>

<script>
    $(document).ready( function(){
        $(document).find("#cliente_nome_modal_edit").autocomplete(optionsAutoCompleteCliente('cliente_nome_modal_edit'));

        $(document).find("#bt-search-cliente-busca-editar").off("click");
        $(document).find("#bt-search-cliente-busca-editar").on("click", function(event){
            event.stopPropagation();
            showModalClienteBuscaModal($(this).data("route"));
            return false;
        });

        $(document).find('#form_edit_cliente_btn').on('click', function(event){
            event.stopPropagation();

            $.ajax({
                url: "{{ route('cliente_bionexo.editar') }}",
                dataType: 'json',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    cliente_nome_modal: $(document).find("#cliente_nome_modal_edit").val(),
                    id: $(document).find("#id").val(),
                },
                success: function(){
                    $(document).find('#modal_cliente_edit_bionexo').modal('hide');
                    filtro();
                },
                error: function(callback){
                    errors = callback.responseJSON.error;
                    
                    for(var field in errors){
                        limparMesagemErroEdit();
                        showErrorsInputs('#form_edit_cliente', field, errors[field]);
                    }
                }
            })
        });
    });

    function limparMesagemErroEdit(){      
        var form_modal_add = $("#form_edit_cliente");
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
        $(document).find("#cliente_nome_modal_edit").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
    }

</script>
@endsection
