@extends('layouts.page-dialog')

@section('content')

<form action="#" onsubmit="return false" name="form_duplicar_produto" id="form_duplicar_produto">
    @csrf
    {{ Form::hidden('id_projeto', $produto['id_projeto'], ['id' => 'id_projeto'])}}
    {{ Form::hidden('id_produto_duplicar', $produto['id'], ['id' => 'id_produto_duplicar'])}}
    {{ Form::hidden('produto_ncm', $produto['ncm'], ['id' => 'produto_ncm'])}}
    {{ Form::hidden('produto_peso', $produto['peso'], ['id' => 'produto_peso'])}}
	<div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('produto_codigo', 'Codigo Produto', []) }} 
            <div class="input-group" id="cod_produto_group">
                {{ Form::text('produto_codigo', $produto['codigo'], ['id' => 'produto_codigo', 'class' => 'form-control', 'placeholder' => 'Codigo Produto', 'maxlength' => '60']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('produto_descricao', 'Descrição Produto', []) }}
            {{ Form::text('produto_descricao', $produto['descricao'], ['id' => 'produto_descricao', 'class' => 'form-control', 'placeholder' => 'Descrição Produto', 'maxlength' => '120']) }}
        </div>
    </div>
    <div class="form-row">
        @if($intercompany == false)
            <div class="form-group col-sm-6">
                {{ Form::label('produto_preco_venda', 'Preço de Venda', []) }}
                {{ Form::text('produto_preco_venda', $produto['preco_venda'], ['id' => 'produto_preco_venda', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Preço de Venda', 'maxlength' => '8']) }}
            </div>
        @endif
        <div class="form-group col-sm-6">
            {{ Form::label('produto_quantidade', 'Quantidade', []) }}
            {{ Form::text('produto_quantidade', $produto['quantidade'], ['id' => 'produto_quantidade', 'class' => 'form-control text-right pedido-item-form', 'placeholder' => 'Quantidade', 'maxlength' => '8']) }}
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('produto_detalhes', 'Detalhes de Produção', []) }}
            {{ Form::text('produto_detalhes', $produto['detalhes'], ['id' => 'produto_detalhes', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Detalhes de Produção', 'maxlength' => '250']) }}
        </div>
    </div>

	<div class="row">
        <div class="col-sm-12 mt-5" id="button-bottom">
            {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
        </div> 
	</div>

</form>

<script>
	$(document).ready( function(){
        form_modal = $(document).find("#form_duplicar_produto");
        form_modal.find("#produto_preco_venda").maskMoney({thousands:'', decimal:','}); 
        form_modal.find("#produto_quantidade").maskMoney({thousands:'', decimal:','});

        form_modal.find("#bt-search-produto").on('click', function(){
            showModalProdutoModal(form_modal);
        });

        form_modal.find("#descricao").autocomplete(optionsAutoComplete("nome"));

        form_modal.find("#btn-salvar").on('click', function(){
            duplicarProduto(form_modal.serialize());
        });
    });

    function showModalProdutoModal(form_modal){
        $.ajax({
            url: '{{ route('produto.modal_pesquisa') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
                table_filters_produtos_busca.on('draw', function () {

                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                        returnDadosProdutoModal($(this), form_modal);
                    });

                });
            }
        });
    }

    function returnDadosProdutoModal($dados, form_modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        form_modal.find('#codigo_produto').val($dados.find("td").eq(1).text());
        form_modal.find('#descricao').val($dados.find("td").eq(2).text());
    };

    function optionsAutoComplete($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_duplicar_produto').css('z-index')) + 1));
            },
            select: function( event, ui ) {
            }
        };
    }

    function duplicarProduto(data_form_modal){
        $.ajax({
            url: "{{ route('lancamento_projeto.duplicar_produto') }}", 
            dataType: 'json',
            data: data_form_modal,
            method: 'POST',
            async: false,
            success: function(data){
                adicionarProdutoDuplicado(data);
                $(form_modal).parents('.modal').modal('hide');
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroModal();
                mensagemErroModal(dados);
            }
        });
    }

    function limparMesagemErroModal(){      
        var form_modal = $("#form_duplicar_produto");
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroModal(json_error){
        console.log(json_error);
        var form_modal = $("#form_duplicar_produto");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModal(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form_modal, input, message){
        if(input.localeCompare('codigo_produto') == 0){
            var $input = $(form_modal).find("#bt-search-produto");
            $(form_modal).find("input[name='codigo_produto']").addClass('error-input');
        }else{
            var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
        }

        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>

@endsection