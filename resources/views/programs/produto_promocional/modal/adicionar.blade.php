@extends('layouts.page-dialog')

@section('content')
<form action="{{ route('listaprecosprodutospromocionais.modal.validar') }}" id="frm_cad_produto_promocional_add" name="frm_cad_produto_promocional_add" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('tipo_promocional', 'Tipo Promocional', []) }}
            {{ Form::select('tipo_promocional', $tipo_promocional, '',  ['id' => 'tipo_promocional', 'class' => 'form-control cad-prod-promocional-form', 'onclick' => 'exibicaoTipoPromocional()']) }}
        </div>
    </div>
    <div class="form-row" id="linha_grupo">
        <div class="form-group col-sm-12"> 
            {{ Form::label('grupo', 'Grupo', []) }}
            {{ Form::text('grupo', '', ['id' => 'grupo', 'class' => 'form-control cad-prod-promocional-form', 'maxlength' => '250']) }}
        </div>
    </div>
    <div class="form-row" id="linha_produto">
        <div class="form-group col-sm-4">
            {{ Form::label('codigo_produto', 'Código do produto', []) }}
            <div class="input-group" id="cod_produto_group">
                {{ Form::text('codigo_produto', '', ['id' => 'codigo_produto', 'class' => 'input-search-bt form-control', 'placeholder' => 'Código', "maxlength" => "250"]) }}
                <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="form-group col-sm-8">
            {{ Form::label('descricao', 'Descrição', []) }}
            <div class="input-group" id="cod_produto_group">
                {{ Form::text('descricao', '', ['id' => 'descricao', 'class' => 'form-control', 'placeholder' => 'Nome do Produto', "maxlength" => "250"]) }}
            </div>
        </div>
    </div>
    <div class="form-group" id="linha_cliente">
        {{ Form::label('cliente', 'Cliente', []) }}
        <div class="input-group" id="cliente_group">
            {{ Form::text('cliente', '', ['id' => 'cliente', 'class' => 'input-search-bt form-control cad-prod-promocional-form', 'placeholder' => 'Cliente', "maxlength" => "250"]) }}
            <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
        </div>
        {!! Form::hidden("codigo_cliente", '', ['id' => 'codigo_cliente']) !!}
    </div>
    <div class="form-row" id="linha_estabelecimento_frete">
        <div class="form-group col-sm-6">
            {{ Form::label('estabelecimento', 'Estabelecimento', []) }}
            {!! Form::select("estabelecimento", $estabelecimentos, '', ["class"=>"form-control"]) !!}
        </div>

        <div class="form-group col-sm-6">
            {{ Form::label('tipo_frete', 'Tipo de Frete', []) }}
            {!! Form::select("tipo_frete", $tipo_frete, '', ["class"=>"form-control"]) !!}
        </div>
    </div>
    <div class="form-row" id="linha_vendedor">
        <div class="form-group col-sm-12">
            {{ Form::label('vendedor', 'Vendedor', []) }}
            {{ Form::select("vendedor", $dropdown_usuarios, '', ["class"=>"form-control"]) }}
        </div>
    </div>
    <div class="form-row" id='linha_tipo_desconto'>
        <div class="form-group col">
            {{ Form::label('', 'Tipo de desconto', []) }}
            <div class="row">
                <div class="col-sm-4">
                    {{ Form::radio('tipo_desconto', 'valor', true, ['id' => 'tipo_desconto_valor', 'class' => 'tipo_desconto']) }}
                    {{ Form::label('tipo_desconto_valor', 'Valor', []) }}
                </div>
                <div class="col-sm-4">
                    {{ Form::radio('tipo_desconto', 'porcentagem', false, ['id' => 'tipo_desconto_porcentagem', 'class' => 'tipo_desconto']) }}
                    {{ Form::label('tipo_desconto_porcentagem', 'Porcentagem', []) }}
                </div>
            </div>
        </div>
    </div>
    <div class="form-row" id="linha_preco">
        <div class="form-group col-sm-4" id='div_valor'>
            {{ Form::label('preco_real', 'Valor', []) }}
            {{ Form::text('preco_real', '', ['id' => 'preco_real', 'class' => 'form-control text-right cad-prod-promocional-form', 'placeholder' => 'Preço Real', 'maxlength' => '8']) }}
        </div>
        <div class="form-group col-sm-4 d-none" id='div_porcentagem'> 
            {{ Form::label('porcentagem', 'Porcentagem', []) }}
            <div class="input-group">
                {{ Form::text('desconto_porcentagem', '0,00', ['id' => 'porcentagem', 'class' => 'form-control text-right cad-prod-promocional-form', 'maxlength' => '8']) }}
                <div class="input-group-append">
                    <span class="input-group-text" id="basic-addon">%</span>
                </div>
            </div>
        </div>
        <div class="form-group preco_fob col-sm-4"> 
            {{ Form::label('preco_fob', 'Preço FOB À Vista', []) }}
            {{ Form::text('preco_fob', '0,00', ['id' => 'preco_fob', 'class' => 'form-control text-right cad-prod-promocional-form', 'maxlength' => '8', 'disabled']) }}
            {{ Form::hidden('preco_fob_val', '',['id' => 'preco_fob_val']) }}
        </div>
    </div>
    <div class="form-row" id="linha_data_comissao">
        <div class="form-group col-sm-6">
            {{ Form::label('data_expiracao', 'Data de Expiração', []) }}
            {{ Form::text('data_expiracao', '', ['id' => 'data_expiracao', 'class' => 'form-control data', 'placeholder' => 'Data Expiração DD/MM/AAAA']) }}
        </div>
        <div class="form-group col-sm-6">
            {{ Form::label('comissao', 'Comissão', []) }}
            {{ Form::select('comissao', $comissao, '', ['id' => 'comissao', 'class' => 'form-control text-right', 'placeholder' => 'Selecione a Comissão']) }}
        </div>
    </div>
    <div class="form-row" id="linha_sem_desconto_adicional">
        <div class="form-group col-sm-10 ml-4">
            {{ Form::checkbox('sem_desconto_adicional', 'true', '', ['id' => 'sem_desconto_adicional', 'class' => 'form-check-input']) }}
            {{ Form::label('sem_desconto_adicional', 'Não permitir desconto adicional', ['class' => 'form-check-label']) }}
        </div>
    </div>
    <div class="row" id="linha_botao_pedido">
        <div class="col-sm-6">
            <a href="#" data-placement="bottom" data-toggle="popover" data-html='true' data-content="<ol>
                <li>Estabelecimento, cliente, grupo, produto e frete;</li>
                <li>Estabelecimento, cliente e grupo;</li>
                <li>Estabelecimento, cliente, grupo e código do produto;</li>
                <li>Estabelecimento, cliente, produto e tipo de frete;</li>
                <li>Estabelecimento, cliente, grupo e tipo de frete;</li>
                <li>Estabelecimento, código do vendedor e grupo;</li>
                <li>Estabelecimento, código do vendedor e produto;</li>
                <li>Estabelecimento, código do vendedor, produto e frete;</li>
                <li>Estabelecimento e produto;</li>
                <li>Estabelecimento e grupo; </li>
            </ol>">Ordem das Buscas</a>
        </div>
        <div class="col-sm-6 text-right">
            {{ Form::button('Salvar', array('class' => 'btn btn-primary text-right', 'id' => 'btn-salvar')) }}
        </div>
    </div>
    <div class="row" id="linha_botao_projeto">
        <div class="col-sm-12 text-right">
            {{ Form::button('Salvar', array('class' => 'btn btn-primary text-right', 'id' => 'btn-salvar_projeto')) }}
        </div>
    </div>
</form>
<script>
    initMaskCamposAdd($(document).find('#frm_cad_produto_promocional_add'));
    $(document).ready( function () {
        $('[data-toggle="popover"]').popover();
        form_modal_add = $(document).find('#frm_cad_produto_promocional_add');
        form_modal_add.find("#descricao").autocomplete(optionsAutoCompleteProdutoNasajon());
        form_modal_add.find("#grupo").autocomplete(optionsAutoCompleteGrupo());
        form_modal_add.find("#cliente").autocomplete(optionsAutoCompleteClienteAdd(form_modal_add));
        form_modal_add.find("#linha_comissao").hide();
        form_modal_add.find("#linha_botao_projeto").hide();
        
        form_modal_add.find("#bt-search-produto").on('click', function(){
            var dados_error = form_modal_add.find('.error-input');
            if(dados_error.length > 0){
                form_modal_add.find('#codigo_produto').val('');
                form_modal_add.find('#descricao').val('');
                form_modal_add.find('#grupo').val('');
            }
            showModalProdutoAdd(form_modal_add);
        });

        form_modal_add.find("#bt-search-cliente-busca").on("click", function(){
            showModalClienteAdd($(this).data("route"), "Lista de Clientes");
        });

        form_modal_add.find('#cliente').on("blur", function(){
            if(form_modal_add.find('#cliente').val() == ''){
                form_modal_add.find('#codigo_cliente').val('');
            }
        });

        form_modal_add.find('#codigo_produto').blur(function(){
            pesquisaProdutoCodigoAdd(form_modal_add);
        });

        form_modal_add.find('#descricao').change(function() {
            limparMesagemErroAdd(false);
            pesquisaProdutoDescricaoAdd(form_modal_add);
        });

        form_modal_add.find('#grupo').change(function() {
            limparMesagemErroAdd(false);
            pesquisaPrecoFobAdd(form_modal_add);
            form_modal_add.find('#codigo').focus();
        });

        form_modal_add.find("#btn-salvar").on('click', function(){
            inserirDados(form_modal_add.serialize());
        });

        form_modal_add.find("#btn-salvar_projeto").on('click', function(){
            inserirDados(form_modal_add.serialize());
        });

        $(document).find('.tipo_desconto').on('change', function(){
            if($(this).val() == 'valor'){
                $(document).find('#div_valor').removeClass('d-none');
                $(document).find('#div_porcentagem').addClass('d-none');
            }
            else if($(this).val() == 'porcentagem'){
                $(document).find('#div_porcentagem').removeClass('d-none');
                $(document).find('#div_valor').addClass('d-none');
            }
        });
    });

    function initMaskCamposAdd(form_modal_add){
        form_modal_add.find("#preco_real").maskMoney({thousands:'', decimal:','});
        form_modal_add.find("#preco_fob").maskMoney({thousands:'', decimal:','}); 
        form_modal_add.find("#porcentagem").maskMoney({thousands:'', decimal:','}); 
        
        form_modal_add.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
            startDate: new Date(),
        });
        form_modal_add.find('.data').mask('00/00/0000');
    }

    function optionsAutoCompleteAdd($name){
        
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_produto_promocional_adicionar').css('z-index')) + 1));
            },
            response: function( event, ui ) {  
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum produto encontrado');
                    event.stopPropagation();
                    event.target.focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                setTimeout(function(){
                    event.stopPropagation();
                    switch($name){
                        case 'grupo':
                            form_modal_add.find('#codigo_produto').focus();
                            break;
                        default:
                            form_modal_add.find('#cliente').focus();
                            break;
                    }
                    limparMesagemErroAdd(false);
                }, 100);
            }
        };
    }

    function optionsAutoCompleteClienteAdd(form_modal_add){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_produto_promocional_adicionar').css('z-index')) + 1));
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
                form_modal_add.find("#codigo_cliente").val(ui.item.value);
                form_modal_add.find("#cliente").val(ui.item.label);
                form_modal_add.find('#estabelecimento').focus();
                return false;
            }
        };
    }

    function showModalProdutoAdd(form_modal_add){
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
                        returnDadosProdutoAdd($(this), form_modal_add);
                    });

                });
            }
        });
    }

    function showModalClienteAdd(url, title, form_modal_add){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("cliente_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#cliente_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#cliente_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosClienteAdd($(this),form_modal_add);
                        });
                    });
                });
            }
        });
    }

    function pesquisaProdutoCodigoAdd(form_modal_add){
        limparMesagemErroAdd(false);
        data_form_modal_add = form_modal_add.serialize();

        $.ajax({
            url: '{{ route('produto.pesquisaprodutocodigo')}}',
            data: data_form_modal_add,
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoAdd(produto, form_modal_add);
            },
            error: function(callback){
                if(form_modal_add.find('#codigo_produto').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_add.find('#codigo_produto').focus();
                    mensagemErroAdd(dados);
                }
                limparCamposAdd('codigo_produto');
            }
        });
    }

    function pesquisaProdutoDescricaoAdd(){
        form_modal_add = $(document).find('#frm_cad_produto_promocional_add');
        data_form_modal_add = form_modal_add.serialize();
        $.ajax({
            url: '{{ route('produto.pesquisaprodutodescricao')}}',
            data: data_form_modal_add,
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoAdd(produto, form_modal_add);
            },
            error: function(callback){
                if(form_modal_add.find('#descricao').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_add.find('#descricao').focus();
                    mensagemErroAdd(dados);
                }
                limparCamposAdd('descricao');
            }
        });
    }

    function pesquisaPrecoFobAdd(){
        form_modal_add = $(document).find('#frm_cad_produto_promocional_add');
        data_form_modal_add = form_modal_add.serialize();
        $.ajax({
            url: '{{ route('produto.pesquisaprecofob')}}',
            data: data_form_modal_add,
            method: 'POST',
            success: function(callback){
                var produto = callback.response;

                form_modal_add.find('#preco_fob').val(produto['preco_fob']);
                form_modal_add.find('#preco_fob_val').val(produto['preco_fob']);
            },
            error: function(callback){
                if(form_modal_add.find('#preco_fob').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_add.find('#codigo_produto').focus();
                    mensagemErroAdd(dados);
                }
                limparCamposAdd('preco_fob');
            }
        });
    }

    function returnDadosClienteAdd($dados,form_modal_add){
        form_modal_add = $(document).find('#frm_cad_produto_promocional_add');
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente_search_show").modal("hide");
        form_modal_add.find("#codigo_cliente").val($dados.find("td").eq(0).text());
        form_modal_add.find("#cliente").val($dados.find("td").eq(1).text()+" "+$dados.find("td").eq(3).text());
    }

    function returnDadosProdutoAdd($dados, form_modal_add){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            
            
            
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        form_modal_add.find('#codigo_produto').val($dados.find("td").eq(1).text());
        pesquisaProdutoCodigoAdd(form_modal_add);
    };

    function dadosRetornoAdd(produto, form_modal_add){
        form_modal_add.find('#codigo_produto').val(produto['codigo_produto']);
        form_modal_add.find('#descricao').val(produto['descricao']);
        form_modal_add.find('#grupo').val(produto['grupo']);
        form_modal_add.find('#preco_fob').val(produto['preco_fob']);
        form_modal_add.find('#preco_fob_val').val(produto['preco_fob']);
        form_modal_add.find('#cliente').focus();
    }

    function inserirDados(data_form_modal_add){
        form_modal_add = $(document).find('#frm_cad_produto_promocional_add');
        data_form_modal_add = form_modal_add.serialize();
        var retorno = false;
        $.ajax({
            url: "{{ route('listaprecosprodutospromocionais.adicionar') }}", 
            dataType: 'json',
            data: data_form_modal_add,
            method: 'POST',
            async: false,
            success: function(callback){
                retorno = true;
                limparCamposAdd('todos');
                $(form_modal_add).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd(true);
                mensagemErroAdd(dados);
            }
        });
        return retorno;
    }

    function limparCamposAdd($campos){
        form_modal_add = $(document).find('#frm_cad_produto_promocional_add');
        
        switch($campos){
            case 'descricao':
                form_modal_add.find('#codigo_produto').val('');
            break;
            case 'codigo_produto':
                form_modal_add.find('#descricao').val('');
            break;
            case 'preco_fob':
                form_modal_add.find('#codigo_produto').val('');
                form_modal_add.find('#descricao').val('');
                form_modal_add.find('#preco_fob').val('0,00');
            break;
            case 'todos':
                form_modal_add.find('#grupo').val('');
                form_modal_add.find('#codigo_produto').val('');
                form_modal_add.find('#descricao').val('');
                form_modal_add.find('#cliente').val('');
                form_modal_add.find('#estabelecimento').val('');
                form_modal_add.find('#tipo_frete').val('');
                form_modal_add.find('#vendedor').val('');
                form_modal_add.find('#preco_real').val('');
                form_modal_add.find('#data_expiracao').val('');
            break;
        }
    }

    function mensagemErroAdd(json_error){
        var form_modal_add = $("#frm_cad_produto_promocional_add");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_add, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form_modal_add, input, message){
        if(input.localeCompare('codigo_produto') == 0){
            var $input = $(form_modal_add).find("#bt-search-produto");
            $(form_modal_add).find("input[name='codigo_produto']").addClass('error-input');
        }else if(input.localeCompare('cliente') == 0){
            var $input = $(form_modal_add).find("#bt-search-cliente-busca");
            $(form_modal_add).find("input[name='cliente']").addClass('error-input');
        }else{
            var $input = $(form_modal_add).find("input[name='"+input+"'], select[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function limparMesagemErroAdd($todos){      
        var form_modal_add = $("#frm_cad_produto_promocional_add");
        if($todos){
            form_modal_add.find('.error-message').remove();
            form_modal_add.find('input, select, span').removeClass('error-input');
        }else{
            form_modal_add.find('#codigo_produto').removeClass('error-input');
            form_modal_add.find('#descricao').removeClass('error-input');
            form_modal_add.find('#bt-search-produto').removeClass('error-input');
            form_modal_add.find('label[for=errcodigo_produto]').remove();
            form_modal_add.find('label[for=errdescricao]').remove();
        }
    }

    function optionsAutoCompleteProdutoNasajon(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produtos_nasajon.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_produto_promocional_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
    }
    
    function optionsAutoCompleteGrupo(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.grupo.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_produto_promocional_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
    }
    
    function exibicaoTipoPromocional(){
        var form_modal_add = $("#frm_cad_produto_promocional_add");
        limparMesagemErroAdd(true);
        switch(form_modal_add.find("#tipo_promocional").val()){
            case "projeto":
                form_modal_add.find("#linha_grupo").hide();
                form_modal_add.find("#linha_produto").hide();
                form_modal_add.find("#linha_estabelecimento_frete").hide();
                form_modal_add.find("#linha_tipo_desconto").hide();
                form_modal_add.find("#linha_preco").hide();
                form_modal_add.find("#linha_sem_desconto_adicional").hide();
                form_modal_add.find("#linha_comissao").show();
                form_modal_add.find("#linha_botao_projeto").show();
                form_modal_add.find("#linha_botao_pedido").hide();
                break;
            case "pedido":
                form_modal_add.find("#linha_grupo").show();
                form_modal_add.find("#linha_produto").show();
                form_modal_add.find("#linha_estabelecimento_frete").show();
                form_modal_add.find("#linha_tipo_desconto").show();
                form_modal_add.find("#linha_preco").show();
                form_modal_add.find("#linha_sem_desconto_adicional").show();
                form_modal_add.find("#linha_comissao").hide();
                break;
        }
    }
</script>
@endsection