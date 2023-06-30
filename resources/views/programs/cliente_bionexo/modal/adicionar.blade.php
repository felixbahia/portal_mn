    @extends('layouts.page-dialog')

    @section('content')
    <form id="form_add_cliente">
        <div class="form-row">
            <div class="col-sm">
                <div class="input-group">
                    {{ Form::text('cliente_nome_modal', '', ['id' => 'cliente_nome_modal_add', 'class' => 'form-control input-label', 'placeholder' => 'Cliente']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca-adicionar" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
        </div>
        <div class="form-row float-right mt-3">
            {{ Form::button('Enviar', ['id' => 'form_add_cliente_btn', 'class' => 'btn btn-success']) }}
        </div>
    </form>

    <script>
        $(document).ready( function(){
            $(document).find("#cliente_nome_modal_add").autocomplete(optionsAutoCompleteCliente('cliente_nome_modal_add'));

            $(document).find("#bt-search-cliente-busca-adicionar").off("click");
            $(document).find("#bt-search-cliente-busca-adicionar").on("click", function(event){
                event.stopPropagation();
                showModalClienteBuscaModal($(this).data("route"));
                return false;
            });

            $(document).find('#form_add_cliente_btn').on('click', function(event){
                event.stopPropagation();


                $.ajax({
                    url: "{{ route('cliente_bionexo.adicionar') }}",
                    dataType: 'json',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        cliente_nome_modal: $(document).find("#cliente_nome_modal_add").val(),
                    },
                    success: function(){
                        $(document).find('#modal_cliente_bionexo').modal('hide');
                        filtro();
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
            $(document).find("#cliente_nome_modal_add").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
            $(document).find("#cliente_searsh_show").modal("hide");
        }

    </script>
    @endsection
