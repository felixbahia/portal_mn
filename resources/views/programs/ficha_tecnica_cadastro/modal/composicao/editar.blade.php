@extends('layouts.page-dialog')

@section('content')
    <form action="#" id='form-editar-consumo' onsubmit='return false;'>

        @csrf
        {!! Form::hidden('id', $id, ['id' => 'id_modal_consumo']) !!}
        {!! Form::hidden('origem', $origem, ['id' => 'origem_modal_consumo']) !!}

        <div class='tab-content'>
            <div class="row">
                <div class="col-sm-5">
                    {!! Form::label('codigo_produto_modal_consumo', 'Código do Produto') !!}
                    <div class="input-group" id="codigo_produto_group">
                        {!! Form::text('codigo_produto', $codigo_produto, ['class' => 'form-control', 'id' => 'codigo_produto_modal_consumo']) !!}
                        <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
                    </div>
                </div>
                <div class="col-sm-7">
                    {!! Form::label('descricao_modal_consumo', 'Descrição do produto') !!}
                    {!! Form::text('descricao', $descricao, ['class' => 'form-control', 'id' => 'descricao_modal_consumo']) !!}
                </div>
            </div>
            @if($origem!='servico')
            <div class="row">
                <div class="col-sm-12">
                    {!! Form::label('consumo_unitario_modal_consumo', 'Consumo unitário') !!}
                    {!! Form::text('consumo_unitario', $consumo_unitario, ['class' => 'form-control text-right', 'id' => 'consumo_unitario_modal_consumo']) !!}
                </div>
            </div>
            @endif
            <div class="row">
                <div class="col-sm-12 text-right mt-3">
                    {{ Form::submit('Salvar', array('class' => 'btn btn-primary')) }}
                </div>
            </div>
        </div>
    </form>

    <script>

        $(document).ready(function(){
            
            @if($origem!='servico')
            $(document).find("#consumo_unitario_modal_consumo").maskMoney({thousands:'', decimal:',', precision: 3});
            @endif

            $(document).find("#bt-search-produto").off('click');
            $(document).find("#bt-search-produto").on('click', function(){
                showModalProduto();
            });

            $(document).find("#descricao_modal_consumo").autocomplete(optionsAutoCompleteProdutoDescricao());

            $(document).find('#codigo_produto_modal_consumo').off();
            $(document).find('#codigo_produto_modal_consumo').on('blur', function(){
                codigoParaNome();
            });

            $(document).find("#form-editar-consumo").on('submit', function(){

                form = $(this);

                $.ajax({
                    url: "{{ route('ficha_tecnica.cadastro.composicao.editar') }}",
                    dataType: 'json',
                    data: form.serialize(),
                    method: 'POST',
                    success: function(data){
                        $(document).find('#modal-edit-consumo').modal('hide');

                        var linha = $(document).find('a[data-id='+$(document).find('#id_modal_consumo').val()+']').parents('tr');

                        if(data.response.origem == 'servico'){ 
                            tabela = table_filters_servicos;
                        }
                        else{
                            tabela = table_filters_composicao;
                        }

                        tabela.row(linha).remove();

                        var linha = data.response.linha;

                        tabela.row.add([
                            "<div><div data-toggle=\"tooltip\" data-html='true' data-placement=\"right\" title=\""+linha.grupo+"\">"+linha.grupo+"</div></div>",
                            "<div><div data-toggle=\"tooltip\" data-html='true' data-placement=\"right\" title=\""+linha.linha+"\">"+linha.linha+"</div></div>",
                            linha.codigo,
                            "<div><div data-toggle=\"tooltip\" data-html='true' data-placement=\"right\" title=\""+linha.descricao+"\">"+linha.descricao+"</div></div>",
                            linha.consumo,
                            @if(Auth::user()->hasRole('Diretoria') || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Analise Compras') || Auth::user()->hasRole('Gerencia Comercial') || Auth::user()->hasRole('Diretoria Comercial'))
                                linha.custo,
                                linha.custo_total,
                                linha.custo_gerencial,
                                linha.custo_gerencial_total,
                            @else
                                linha.custo,
                                linha.custo_total,
                            @endif
                            createBtnDeleteModal(linha.id, data.response.origem),
                            createBtnEditModal(linha.id, data.response.origem)
                        ]);

                        tabela.draw();
                    },
                    error: function(data){
                        var errors = data.responseJSON.error;
                        form.find('.error-message').remove();
                        for(var field in errors){
                            showErrorsInputsModalComposicaoEditar(form, field, errors[field])
                        }
                    }
                });
            })
        });

        function showErrorsInputsModalComposicaoEditar(form, input, message){

            if(input == 'codigo_produto'){
                var $input = $('#codigo_produto_group');
            }
            else{
                var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
            }
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }

        function showModalProduto(){

            if($(document).find("#origem_modal_consumo").val() == 'insumo'){
                condicao = 'INSUMO';
            }
            else if($(document).find("#origem_modal_consumo").val() == 'servico'){
                condicao = 'MAO DE OBRA';
            }
            else{
                condicao = "%";
            }

            $.ajax({
                url: '{{ route('produto.modal_pesquisa_limitado') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    campo: 'linha',
                    condicao: condicao
                },
                success: function (data){
                    createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
                    table_filters_produtos_busca.on('draw', function () {

                        $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                        $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                            returnDadosProduto($(this));
                        });

                    });
                }
            });
        }

        function returnDadosProduto($dados){
            if($dados.find("td").eq(0).hasClass('dataTables_empty')){
                return false;
            }
            $(document).find("#modal_search_produto").modal("hide");
            
            $(document).find('#codigo_produto_modal_consumo').val($dados.find("td").eq(1).text());
            $(document).find('#descricao_modal_consumo').val($dados.find("td").eq(2).text());
        }

        function codigoParaNome(){

            data = {
                _token: '{{ csrf_token() }}',
                codigo_produto: $(document).find('#codigo_produto_modal_consumo').val()
            };

            if($(document).find("#origem_modal_consumo").val() == 'insumo'){
                data.coluna = 'linha',
                data.condicao = 'INSUMO';
            }
            else if($(document).find("#origem_modal_consumo").val() == 'servico'){
                data.coluna = 'linha',
                data.condicao = 'MAO DE OBRA';
            }

            $.ajax({
                url: "{{ route('produto.pesquisaprodutocodigo') }}",
                dataType: 'json',
                data: data,
                method: 'POST',
                success: function(callback){
                    $(document).find('#descricao_modal_consumo').val(callback.response.descricao);
                },
                error: function(){
                    $(document).find('#descricao_modal_consumo').val('');
                }
            });            
        }

        function optionsAutoCompleteProdutoDescricao(){

            $(document).find(".error-message").remove();

            return {
                source: function (request, response) {
                    request.name = 'nome';
                    request._token = "{{ csrf_token() }}";
                    request.campo = 'linha';

                    if($(document).find("#origem_modal_consumo").val() == 'insumo'){
                        request.condicao = 'INSUMO';
                    }
                    else if($(document).find("#origem_modal_consumo").val() == 'servico'){
                        request.condicao = 'MAO DE OBRA';
                    }
                    else{
                        request.condicao = "%";
                    }

                    $.post("{{ route('produto.autocompletelimitacao') }}", request, response);
                },
            delay: 700,
                minLength: 2,
                open: function( event, ui ){
                    $('.ui-autocomplete').css("z-index", (parseInt($('#modal-edit-consumo').css('z-index')) + 1));
                },
                response: function( event, ui ) {
                    if(ui.content.length === 0){
                        message('Atenção', 'Nenhum produto encontrado');
                        event.stopPropagation();
                        return false;
                    }
                },
                select: function( event, ui ) {
                    $(document).find("#codigo_produto_modal_consumo").val(ui.item.value);
                    $(document).find("#descricao_modal_consumo").val(ui.item.label);
                    return false;
                }
            };
        }

        function createBtnDeleteModal($id, $origem){
            $html = "<a href=\"#\" data-id=\""+$id+"\" data-origem=\""+$origem+"\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\"></a>";
            return $html;
        }

        function createBtnEditModal($id, $origem){
            $html = "<a href=\"#\" data-id=\""+$id+"\" data-origem=\""+$origem+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>";
            return $html;
        }
    </script>

@endsection