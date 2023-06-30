@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_tecido_base_add" id="form_tecido_base_add" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('codigo_produto', 'Codigo Produto', []) }} 
            <div class="input-group" id="cod_produto_group">
                {{ Form::text('codigo_produto', '', ['id' => 'codigo_produto', 'class' => 'form-control', 'placeholder' => 'Codigo Produto', 'maxlength' => '60']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('descricao', 'Descrição Produto', []) }}
            {{ Form::text('descricao', '', ['id' => 'descricao', 'class' => 'form-control', 'placeholder' => 'Descrição Produto', 'maxlength' => '120']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('codigo_produto_base', 'Prefixo Produto Novo', []) }}
            {{ Form::text('codigo_produto_base', '', ['id' => 'codigo_produto_base', 'class' => 'form-control', 'placeholder' => 'Prefixo Produto Novo', 'maxlength' => '60']) }}
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    $(document).ready( function () {
        form_modal = $(document).find('#form_tecido_base_add');
        form_modal.find("#bt-search-produto").on('click', function(){
            showModalProdutoModal(form_modal);
        });

        form_modal.find("#descricao").autocomplete(optionsAutoComplete("nome"));

        form_modal.find('#codigo_produto').blur(function(){
            pesquisaProdutoCodigoModal(form_modal);
        });

        form_modal.find('#descricao').change(function() {
            limparMesagemErroModal();
            pesquisaProdutoDescricaoModal(form_modal);
        });
        
        form_modal.find("#btn-salvar").on('click', function(){
            inserirDados(form_modal.serialize());
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_tecido_base_adicionar').css('z-index')) + 1));
            },
            select: function( event, ui ) {
            }
        };
    }

    function pesquisaProdutoCodigoModal(form_modal){
        limparMesagemErroModal();
        data_form_modal = form_modal.serialize();

        $.ajax({
            url: '{{ route('produto.pesquisaprodutocodigo')}}',
            data: data_form_modal,
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoModal(produto, form_modal);
            },
            error: function(callback){
                if(form_modal.find('#codigo_produto').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal.find('#codigo_produto').focus();
                    mensagemErroModal(dados);
                }
            }
        });
    }

    function dadosRetornoModal(produto, form_modal){
        form_modal.find('#codigo_produto').val(produto.codigo_produto);
        form_modal.find('#descricao').val(produto.descricao);
        form_modal.find('#codigo_produto_base').focus();
    }

    function pesquisaProdutoDescricaoModal(){
        form_modal = $(document).find('#form_tecido_base_add');
        data_form_modal = form_modal.serialize();
        $.ajax({
            url: '{{ route('produto.pesquisaprodutodescricao')}}',
            data: data_form_modal,
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoModal(produto, form_modal);
            },
            error: function(callback){
                if(form_modal.find('#descricao').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal.find('#descricao').focus();
                    mensagemErroModal(dados);
                }
            }
        });
    }

    function inserirDados(data_form_modal){
        $.ajax({
            url: "{{ route('produto.tecido_base.adicionar') }}", 
            dataType: 'json',
            data: data_form_modal,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroModal();
                mensagemErroModal(dados);
            }
        });
    }

    function limparMesagemErroModal(){      
        var form_modal = $("#form_tecido_base_add");
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroModal(json_error){
        var form_modal = $("#form_tecido_base_add");
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