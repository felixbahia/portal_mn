@extends('layouts.page-dialog')

@section('content')
	<form action="#" onsubmit="return false" name="form_duplicar_pedido" id="form_duplicar_pedido">
        @csrf
        {!! Form::hidden('pedido', $pedido->id) !!}
        <div class="row border-bottom justify-content-md-center">
            <div class='col-sm-5'><b>Pedido</b></div>
            <div class='col-sm-5' style="text-align:right;">{{ $pedido->id }}</div>
        </div>
        <div class="row border-bottom justify-content-md-center">
            <div class='col-sm-5'><b>Estabelecimento</b></div>
            <div class='col-sm-5' style="text-align:right;">{{ str_pad($pedido->estabelecimento, 2, "0", STR_PAD_LEFT) }}</div>
            {{ Form::hidden('estabelecimento', $pedido->estabelecimento, []) }}
        </div>
        <div class="row border-bottom justify-content-md-center">
            <div class='col-sm-5'><b>Valor</b></div>
            <div class='col-sm-5' style="text-align:right;">@if(isset($pedido->valor_total->total)) {{ parserValor($pedido->valor_total->total) }} @endif</div>
        </div>
        @if($futuro == true)
            <div class="form-group col-sm-12">
                {{ Form::label('previsao_entrega', 'Previsão de Entrega') }}
                <div class="input-group" id="cod_cliente_group">
                    {{ Form::text("previsao_entrega", $data_pedido, ["id" => "previsao_entrega", "class" => "form-control data_previsao", "placeholder" => "dd/mm/AAAA"]) }}
                </div>
            </div>
        @endif
        <div class="form-group col-sm-12">
            {{ Form::label('nome_cliente', 'Cliente') }}
            <div class="input-group" id="cod_cliente_group">
                {{ Form::text('nome_cliente_duplicar', $nome_cliente, ['id' => 'nome_cliente_duplicar', 'class' => 'form-control essencial input-label', 'placeholder' => '']) }}
                {{ Form::hidden('codigo_cliente_duplicar', $codigo_cliente, ['id' => 'codigo_cliente_duplicar', 'class' => '']) }}
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
            $(document).find(".data_previsao").mask("00/00/0000");
            $(document).find(".data_previsao").datepicker({
                language: "pt-BR",
                format: "dd/mm/yyyy",
                zIndex: 999999999999,
                autoHide: true
            });
            $(document).find("#nome_cliente_duplicar").autocomplete(optionsAutoCompleteCliente());
            $(document).find("#btn-cancel-duplicar").on("click", function(){
                $(this).parents(".modal").modal('hide');
            });
            $(document).find("#btn-salvar-duplicar").on("click", function(){
                modal = $(this).parents(".modal");
                var form = $(document).find('#form_duplicar_pedido');
                form.find('.error-message').remove();
                $.ajax({
                    url: '{{ Route("pedido_portal.duplicar.salvar") }}',
                    type: 'POST',
                    data: form.serialize(),
                    success: function(callback){
                        message("Atenção", callback.message, '', 400, 250);
                        if(callback.status === 'success'){
                            modal.modal('hide');
                            filterAjax($("#form_filter_pedidos").serialize());
                        }
                    },
                    error: function(data){
                        var mensagens = data.responseJSON.error;
                        $.each(mensagens, function(index, el) {
                            form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>');
                            form.find('input[name="'+index+'"]').eq(0).addClass('error');
                        });
                        form.find('input.error').eq(0).focus();
                        if(data.responseJSON.errors){
                            var errors = data.responseJSON.errors;
                            form.find('.error-message').remove();
                            for(var field in errors){
                                showErrorsInputs(form, field, errors[field]);
                            }
                        }else if(data.responseJSON.message){
                            if(data.responseJSON.response.continuar === true){
                                var $class = "dialog_option_deletar";
                                var $name_option_sim = "gerar_duplicacao_sim";
                                var $option_sim = "Sim";
                                var $name_option_nao = "gerar_duplicacao_nao";
                                var $option_nao = "Não";
    
                                message_sim_nao("Atenção", data.responseJSON.message, $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao);
                                
                                $(document).off("gerar_duplicacao_nao");
                                $(document).on("gerar_duplicacao_nao", function(){
                                    modal.modal('hide');
                                });

                                $(document).off("gerar_duplicacao_sim");
                                $(document).on("gerar_duplicacao_sim", function(){
                                    gerarDuplicacaoSomenteComProdutoDisponivel();
                                });
                            }else{
                                message("Atenção", data.responseJSON.message);
                            }
                            
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
                    $('.ui-autocomplete').css("z-index", (parseInt($('#duplicacao_pedido').css('z-index')) + 1));
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
                    $(document).find("#codigo_cliente_duplicar").val(ui.item.value);
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
                    _token: '{{csrf_token()}}', estabelecimento: '{{ str_pad($pedido->estabelecimento, 2, "0", STR_PAD_LEFT) }}'
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
            $(document).find("#codigo_cliente_duplicar").val($dados.find("td").eq(0).text());
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

        function gerarDuplicacaoSomenteComProdutoDisponivel(){
            modal = $(this).parents(".modal");
            var form = $(document).find('#form_duplicar_pedido');
            var pedido = {{ $pedido->id }};
            var estabelecimento = form.find("#estabelecimento").val();
            var nome_cliente_duplicar = form.find("#nome_cliente_duplicar").val();
            var codigo_cliente_duplicar = form.find("#codigo_cliente_duplicar").val();
            var verificar_produto_sem_estoque = false; 
            
            $.ajax({
                url: '{{ Route("pedido_portal.duplicar.salvar") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    codigo_cliente_duplicar: codigo_cliente_duplicar,
                    verificar_produto_sem_estoque: verificar_produto_sem_estoque,
                    pedido: pedido,
                },
                success: function(callback){
                    message("Atenção", callback.message, '', 400, 250);
                    if(callback.status === 'success'){
                        $(document).find("#duplicacao_pedido").modal("hide");
                        filterAjax($("#form_filter_pedidos").serialize());
                    }
                },
                error: function(data){
                    if(data.responseJSON.errors){
                        var errors = data.responseJSON.errors;
                        form.find('.error-message').remove();
                        for(var field in errors){
                            showErrorsInputs(form, field, errors[field]);
                        }
                    }else if(data.responseJSON.message){
                        message("Atenção", data.responseJSON.message);  
                    }
                }
            })
        }
    </script>
@endsection