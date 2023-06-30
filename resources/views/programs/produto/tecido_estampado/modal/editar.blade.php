@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_tecido_estampado_edt" id="form_tecido_estampado_edt" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $dados['id'], ['id' => 'id']) !!}
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('codigo_produto_final', 'Codigo Tecido Estampado', []) }} 
            <div class="input-group" id="cod_produto_group">
                {{ Form::text('codigo_produto_final', $dados['codigo_produto_tecido_estampado'], ['id' => 'codigo_produto_final', 'class' => 'form-control', 'placeholder' => 'Codigo Tecido Estampado', 'maxlength' => '60']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-produto_final"><i class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('descricao_final', 'Descrição Tecido Estampado', []) }}
            {{ Form::text('descricao_final', $dados['descricao_tecido_estampado'], ['id' => 'descricao_final', 'class' => 'form-control', 'placeholder' => 'Descrição Tecido Estampado', 'maxlength' => '120']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-4">
            {{ Form::label('codigo_produto_tecido_base', 'Codigo Tecido Base', []) }} 
            <div class="input-group" id="cod_produto_group">
                {{ Form::text('codigo_produto_tecido_base', $dados['codigo_produto_tecido_base'], ['id' => 'codigo_produto_tecido_base', 'class' => 'form-control', 'placeholder' => 'Codigo Tecido Base', 'maxlength' => '60']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-produto_tecido_base"><i class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="form-group col-sm-8"> 
            {{ Form::label('descricao_tecido_base', 'Descrição Tecido Base', []) }}
            {{ Form::text('descricao_tecido_base', $dados['descricao_tecido_base'], ['id' => 'descricao_tecido_base', 'class' => 'form-control', 'placeholder' => 'Descrição Tecido Base', 'maxlength' => '120']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-4">
            {{ Form::label('codigo_produto_desenho', 'Codigo Desenho', []) }} 
            <div class="input-group" id="cod_produto_group">
                {{ Form::text('codigo_produto_desenho', $dados['codigo_produto_desenho'], ['id' => 'codigo_produto_desenho', 'class' => 'form-control', 'placeholder' => 'Codigo Desenho', 'maxlength' => '60']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-produto_desenho"><i class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="form-group col-sm-8"> 
            {{ Form::label('descricao_desenho', 'Descrição Desenho', []) }}
            {{ Form::text('descricao_desenho', $dados['descricao_desenho'], ['id' => 'descricao_desenho', 'class' => 'form-control', 'placeholder' => 'Descrição Desenho', 'maxlength' => '120']) }}
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    $(document).ready( function () {
        form_modal = $(document).find('#form_tecido_estampado_edt');
        form_modal.find("#bt-search-produto_desenho").on('click', function(){
            showModalProdutoModal(form_modal, "grupo", "Desenho Estamparia Digital", "desenho");
        });
        form_modal.find("#bt-search-produto_final").on('click', function(){
            showModalProdutoModal(form_modal, "", "", "final");
        });
        form_modal.find("#bt-search-produto_tecido_base").on('click', function(){
            showModalProdutoTecidoBaseModal(form_modal);
        });

        form_modal.find("#descricao_final").autocomplete(optionsAutoCompleteModal("nome"));
        form_modal.find("#descricao_tecido_base").autocomplete(optionsAutoCompleteTecidoBaseModal());
        form_modal.find("#descricao_desenho").autocomplete(optionsAutoCompleteLimitadoModal("nome"));

        form_modal.find('#codigo_produto_final').blur(function(){
            limparMesagemErroModal();
            pesquisaProdutoCodigoModal(form_modal.find('#codigo_produto_final').val(), "", "", "final");
        });
        form_modal.find('#codigo_produto_desenho').blur(function(){
            limparMesagemErroModal();
            pesquisaProdutoCodigoModal(form_modal.find('#codigo_produto_desenho').val(), "grupo", "DESENHO ESTAMPARIA DIGITAL", "desenho");
        });

        form_modal.find('#descricao_final').change(function() {
            limparMesagemErroModal();
            pesquisaProdutoDescricaoModal(form_modal.find('#descricao_final').val(), "", "", "final");
        });
        {{-- form_modal.find('#descricao_desenho').change(function() {
            limparMesagemErroModal();
            pesquisaProdutoDescricaoModal(form_modal.find('#descricao_desenho').val(), "grupo", "DESENHO ESTAMPARIA DIGITAL", "desenho");
        }); --}}

        form_modal.find('#codigo_produto_tecido_base').change(function() {
            limparMesagemErroModal();
            getTecidoBaseModal(form_modal.find('#codigo_produto_tecido_base').val(), "");
        });
        form_modal.find('#descricao_tecido_base').change(function() {
            limparMesagemErroModal();
            getTecidoBaseModal("", form_modal.find('#descricao_tecido_base').val());
        });
        
        form_modal.find("#btn-salvar").on('click', function(){
            editarDados(form_modal.serialize());
        });
    });


    function showModalProdutoModal(form_modal, campo, condicao, input){
        $.ajax({
            url: '{{ route('produto.modal_pesquisa_limitado') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                campo: campo,
                condicao: condicao
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
                table_filters_produtos_busca.on('draw', function () {
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                        returnDadosProdutoModal($(this), form_modal, input);
                    });

                });
            }
        });
    }

    function returnDadosProdutoModal($dados, form_modal,input){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        if(input == "desenho"){
            form_modal.find('#codigo_produto_desenho').val($dados.find("td").eq(1).text());
            form_modal.find('#descricao_desenho').val($dados.find("td").eq(2).text());
        }else if(input == "final"){
            form_modal.find('#codigo_produto_final').val($dados.find("td").eq(1).text());
            form_modal.find('#descricao_final').val($dados.find("td").eq(2).text());
        }
        
    };

    function showModalProdutoTecidoBaseModal(form_modal){
        $.ajax({
            url: '{{ route('produto.tecido_base.modal.buscar') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_produto", "Buscar produto", data, 'modal-lg');
                table_filters_produtos_busca.on('draw', function () {
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-produtos-busca").find('tbody').find("tr").on("click", function(){
                        returnDadosProdutoTecidoBaseModal($(this), form_modal);
                    });

                });
            }
        });
    }

    function returnDadosProdutoTecidoBaseModal($dados, form_modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        form_modal.find('#codigo_produto_tecido_base').val($dados.find("td").eq(1).text());
        form_modal.find('#descricao_tecido_base').val($dados.find("td").eq(2).text());
    };

    function optionsAutoCompleteLimitadoModal($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request.campo = "grupo";
                request.condicao = "Desenho Estamparia Digital";
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocompletelimitacao') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_tecido_estampado_edit_delete').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_modal.find('#descricao_desenho').val(ui.item.value)
                pesquisaProdutoDescricaoModal(form_modal.find('#descricao_desenho').val(), "grupo", "DESENHO ESTAMPARIA DIGITAL", "desenho");
                return false;
            }
        };
    }

    function optionsAutoCompleteModal($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_tecido_estampado_edit_delete').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_modal.find('#descricao_final').val(ui.item.label);
                pesquisaProdutoDescricaoModal(form_modal.find('#descricao_final').val(), "", "", "final");
                return false;
            }
        };
    }

    function optionsAutoCompleteTecidoBaseModal(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.tecido_base.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_tecido_estampado_edit_delete').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_modal.find('#descricao_tecido_base').val(ui.item.value);
                return false;
            }
        };
    }


    function pesquisaProdutoCodigoModal(codigo_produto, campo, condicao, input){
        limparMesagemErroModal();

        $.ajax({
            url: '{{ route('produto.pesquisaprodutocodigo')}}',
            data: {
                _token: '{{ csrf_token() }}',
                codigo_produto: codigo_produto,
                campo: campo,
                condicao: condicao
            },
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoModal(produto, form_modal, input);
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

    function dadosRetornoModal(produto, form_modal, input){
        if(input == "desenho"){
            form_modal.find('#codigo_produto_desenho').val(produto.codigo_produto);
            form_modal.find('#descricao_desenho').val(produto.descricao);
        }else if(input == "final"){
            form_modal.find('#codigo_produto_final').val(produto.codigo_produto);
            form_modal.find('#descricao_final').val(produto.descricao);
        }
    }

    function pesquisaProdutoDescricaoModal(descricao, campo, condicao, input){
        form_modal = $(document).find('#form_tecido_estampado_edt');
        $.ajax({
            url: '{{ route('produto.pesquisaprodutodescricao')}}',
            data: {
                _token: '{{ csrf_token() }}',
                descricao: descricao,
                campo: campo,
                condicao: condicao
            },
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoModal(produto, form_modal, input);
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

    function getTecidoBaseModal(codigo, descricao){
        form_modal = $(document).find('#form_tecido_estampado_edt');
        $.ajax({
            url: '{{ route('produto.tecido_base.get_tecido_base')}}',
            data: {
                _token: '{{ csrf_token() }}',
                codigo: codigo,
                descricao: descricao
            },
            method: 'POST',
            success: function(callback){
                var tecido_base = callback.response;
                form_modal.find('#codigo_produto_tecido_base').val(tecido_base.codigo_produto);
                form_modal.find('#descricao_tecido_base').val(tecido_base.descricao);
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

    function editarDados(data_form_modal){
        $.ajax({
            url: "{{ route('produto.tecido_estampado.editar') }}", 
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
        var form_modal = $("#form_tecido_estampado_edt");
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroModal(json_error){
        var form_modal = $("#form_tecido_estampado_edt");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModal(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form_modal, input, message){
        if(input.localeCompare('codigo_produto_final') == 0){
            var $input = $(form_modal).find("#bt-search-produto_final");
            $(form_modal).find("input[name='codigo_produto_final']").addClass('error-input');
        }else if(input.localeCompare('codigo_produto_tecido_base') == 0){
            var $input = $(form_modal).find("#bt-search-produto_tecido_base");
            $(form_modal).find("input[name='codigo_produto_tecido_base']").addClass('error-input');
        }else if(input.localeCompare('codigo_produto_desenho') == 0){
            var $input = $(form_modal).find("#bt-search-produto_desenho");
            $(form_modal).find("input[name='codigo_produto_desenho']").addClass('error-input');
        }else{
            var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection