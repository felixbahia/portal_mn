@extends('layouts.page-dialog')

@section('content')
	<form action="#" onsubmit="return false" name="form_duplicar_projeto" id="form_duplicar_projeto">
        @csrf
        {!! Form::hidden('id_projeto', $dados['id'], ['id' => 'id_projeto']) !!}
        <div class="row border-bottom justify-content-md-center">
            <div class='col-sm-5'><b>Número Projeto</b></div>
            <div class='col-sm-5' style="text-align:right;">{{ $dados['numero_projeto'] }}</div>
        </div>
        <div class="row border-bottom justify-content-md-center">
            <div class='col-sm-5'><b>Valor</b></div>
            <div class='col-sm-5' style="text-align:right;">{{ $dados['valor'] }}</div>
        </div>
        <br>
        <div class="form-group col-sm-12">
            {{ Form::label('nome_projeto', 'Nome do Projeto') }}
            <div class="input-group" id="cod_cliente_group">
                {{ Form::text('nome_projeto', $dados['nome_projeto'], ['id' => 'nome_projeto', 'class' => 'form-control essencial input-label', 'placeholder' => '',"maxlength" => "60"]) }}
            </div>
        </div>
        <div class="form-group col-sm-12">
            {{ Form::label('nome_cliente', 'Cliente') }}
            <div class="input-group" id="cod_cliente_group">
                {{ Form::text('nome_cliente_duplicar', $dados['cliente_descricao'], ['id' => 'nome_cliente_duplicar', 'class' => 'form-control essencial input-label', 'placeholder' => '']) }}
                {{ Form::hidden('codigo_cliente_duplicar', $dados['cliente'], ['id' => 'codigo_cliente_duplicar', 'class' => '']) }}
                {{ Form::hidden('cliente_documento_duplicar', $dados['cliente_documento'], ['id' => 'cliente_documento_duplicar', 'class' => '']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-duplicar" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="content-buttons float-right">
            <button name="btn-cancel" id="btn-cancel-duplicar" class="btn btn-danger text-right">Cancelar</button>
            <button name="btn-create" id="btn-salvar-duplicar" class="btn btn-success text-right">Salvar</button>
        </div>
    </form>
    <script type="text/javascript">
        $(function(){
            $(document).find("#nome_cliente_duplicar").autocomplete(optionsAutoCompleteCliente());
            $(document).find("#btn-cancel-duplicar").on("click", function(){
                $(this).parents(".modal").modal('hide');
            });
            $(document).find("#btn-salvar-duplicar").on("click", function(){
                modal = $(this).parents(".modal");
                var form = $(document).find('#form_duplicar_projeto');
                $.ajax({
                    url: '{{ Route("lancamento_projeto.salvar_duplicada") }}',
                    type: 'POST',
                    data: form.serialize(),
                    success: function(callback){
                        message("Atenção", callback.message);
                        if(callback.status === 'success'){
                            $(document).find("#modal_duplicar").modal("hide");
                            filterAjax();
                        }
                    },
                    error: function(data){
                        var errors = data.responseJSON.errors;
                        form.find('.error-message').remove();
                        for(var field in errors){
                            showErrorsInputs(form, field, errors[field]);
                        }
                    }
                })
            });
            
            $(document).find("#bt-search-cliente-duplicar").on('click', function(event){
                event.stopPropagation();
                showModalClienteBuscaDuplicar($(this).data("route"));
                return false;
            });
        });
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
                    $('.ui-autocomplete').css("z-index", (parseInt($('#modal_duplicar').css('z-index')) + 1));
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
                    $(document).find("#codigo_cliente_duplicar").val(ui.item.cpf_cnpj);
                    $(document).find("#nome_cliente_duplicar").val(ui.item.label);
                    return false;
                }
            };
        }
        function showModalClienteBuscaDuplicar(url){
            var title = "Busca de Clientes";
            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: '{{csrf_token()}}'
                },
                success: function(body){
                    $(document).find('#cliente_searsh_duplicar_show').remove();
                    createModal("cliente_searsh_duplicar_show", title, body, 'modal-lg');
                    var modal = $(document).find("#cliente_searsh_duplicar_show");
                    $(document).ready( function () {
                        table_dialog.on('draw', function () {
                            modal.find('tbody').find("tr").off("click");
                            modal.find('tbody').find("tr").on("click", function(event){
                                returnDadosClienteBuscaDuplicar($(this), event);
                            });
                        });
                    });
                }
            });
        }

        function returnDadosClienteBuscaDuplicar($dados, event){
            if($dados.find("td").eq(0).hasClass('dataTables_empty')){
                return false;
            }
            $(document).find("#codigo_cliente_duplicar").val($dados.find("td").eq(3).text());
            $(document).find("#nome_cliente_duplicar").val($dados.find("td").eq(1).text()+' - '+$dados.find("td").eq(3).text());
            $(document).find("#cliente_searsh_duplicar_show").modal("hide");
        }

        function showErrorsInputs(form, input, message){
            campos_cliente = ['nome_cliente_duplicar', 'codigo_cliente_duplicar']
            
            if(campos_cliente.indexOf(input) != -1){
                $(document).find('#bt-search-cliente-duplicar').after("<label class='error-message' for='bt-view-cliente'>"+message+"</label>");
                $(document).find('#bt-search-cliente-duplicar').addClass('error-input');
                $(document).find('#nome_cliente_duplicar').addClass('error-input');
                $(document).find('#codigo_cliente_duplicar').addClass('error-input');
            }
        }
    </script>
@endsection