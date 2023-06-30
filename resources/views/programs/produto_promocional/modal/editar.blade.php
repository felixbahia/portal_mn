@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('listaprecosprodutospromocionais.editar') }}" id="frm_cad_produto_promocional_edt" name="frm_cad_produto_promocional_edt" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $dados['id']) !!}
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('tipo_promocional', 'Tipo Promocional', []) }}
            {{ Form::select('tipo_promocional', $tipo_promocional, $dados['tipo_promocional'],  ['id' => 'tipo_promocional', 'class' => 'form-control cad-prod-promocional-form', 'onclick' => 'exibicaoTipoPromocional()']) }}
        </div>
    </div>
    <div class="form-row" id="linha_grupo">
        <div class="form-group col-sm-12"> 
            {{ Form::label('grupo', 'Grupo', []) }}
            {{ Form::text('grupo', $dados['grupo'], ['id' => 'grupo', 'class' => 'form-control cad-prod-promocional-form', 'maxlength' => '250']) }}
        </div>
    </div>
    <div class="form-row" id="linha_produto">
        <div class="form-group col-sm-4">
            {{ Form::label('codigo_produto', 'Código do produto', []) }}
            <div class="input-group" id="cod_produto_group">
                {{ Form::text('codigo_produto', $dados['codigo_produto'], ['id' => 'codigo_produto', 'class' => 'input-search-bt form-control', 'placeholder' => 'Código', "maxlength" => "250"]) }}
                <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="form-group col-sm-8">
            {{ Form::label('descricao', 'Descrição', []) }}
            <div class="input-group" id="cod_produto_group">
                {{ Form::text('descricao', $dados['descricao'], ['id' => 'descricao', 'class' => 'form-control', 'placeholder' => 'Nome do Produto', "maxlength" => "250"]) }}
            </div>
        </div>
    </div>
    <div class="form-group" id="linha_cliente">
        {{ Form::label('cliente', 'Cliente', []) }}
            <div class="input-group" id="cliente_group">
                {{ Form::text('cliente', $dados['cliente'], ['id' => 'cliente', 'class' => 'input-search-bt form-control cad-prod-promocional-form', 'placeholder' => 'Cliente', "maxlength" => "250"]) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
            {!! Form::hidden("codigo_cliente", $dados['codigo_cliente'], ['id' => 'codigo_cliente']) !!}
    </div>
    <div class="form-row" id="linha_estabelecimento_frete">
        <div class="form-group col-sm-6">
            {{ Form::label('estabelecimento', 'Estabelecimento') }}
            {!! Form::select("estabelecimento", $estabelecimentos, intval($dados['estabelecimento']), ["class"=>"form-control"]) !!}
        </div>

        <div class="form-group col-sm-6">
            {{ Form::label('tipo_frete', 'Tipo de Frete') }}
            {!! Form::select("tipo_frete", $tipo_frete, $dados['tipo_frete'], ["class"=>"form-control"]) !!}
        </div>
    </div>
    <div class="form-group" id="linha_vendedor">
        {{ Form::label('vendedor', 'Vendedor') }}
        {{ Form::select("vendedor", $dropdown_usuarios, $dados['codigo_vendedor'], ["class"=>"form-control"]) }}
    </div>
    <div class="form-row" id='linha_tipo_desconto'>
        <div class="form-group col">
            {{ Form::label('', 'Tipo de desconto', []) }}
            <div class="row">
                <div class="col-sm-4">
                    {{ Form::radio('tipo_desconto', 'valor', !empty($dados['preco_real']), ['id' => 'tipo_desconto_valor', 'class' => 'tipo_desconto']) }}
                    {{ Form::label('tipo_desconto_valor', 'Valor', []) }}
                </div>
                <div class="col-sm-4">
                    {{ Form::radio('tipo_desconto', 'porcentagem', !empty($dados['desconto_porcentagem']), ['id' => 'tipo_desconto_porcentagem', 'class' => 'tipo_desconto']) }}
                    {{ Form::label('tipo_desconto_porcentagem', 'Porcentagem', []) }}
                </div>
            </div>
        </div>
    </div>
    <div class="form-row" id="linha_preco">
        <div class="form-group col-sm-4 {{ empty($dados['preco_real'])?'d-none':'' }}" id='div_valor'>
            {{ Form::label('preco_real', 'Preço Real', []) }}
            {{ Form::text('preco_real', $dados['preco_real'], ['id' => 'preco_real', 'class' => 'form-control text-right cad-prod-promocional-form', 'placeholder' => 'Preço Real', 'maxlength' => '8']) }}
        </div>
        <div class="form-group col-sm-4 {{ empty($dados['desconto_porcentagem'])?'d-none':'' }}" id='div_porcentagem'> 
            {{ Form::label('porcentagem', 'Porcentagem', []) }}
            <div class="input-group">
                {{ Form::text('desconto_porcentagem', $dados['desconto_porcentagem'], ['id' => 'porcentagem', 'class' => 'form-control text-right cad-prod-promocional-form', 'maxlength' => '8']) }}
                <div class="input-group-append">
                    <span class="input-group-text" id="basic-addon">%</span>
                </div>
            </div>
        </div>
        {!! Form::hidden("procedencia", $dados['procedencia'], ['id' => 'procedencia']) !!}
        <div class="form-group preco_fob col-sm-4"> 
            {{ Form::label('preco_fob', 'Preço FOB À Vista', []) }}
            {{ Form::text('preco_fob', $dados['preco_fob'], ['id' => 'preco_fob', 'class' => 'form-control text-right cad-prod-promocional-form', 'maxlength' => '8', 'disabled']) }}
        </div>
    </div>
    <div class="form-row" id="linha_data_comissao">
        <div class="form-group col-sm-6">
            {{ Form::label('data_expiracao', 'Data de Expiração', []) }}
            {{ Form::text('data_expiracao', $dados['data_expiracao'], ['id' => 'data_expiracao', 'class' => 'form-control data', 'placeholder' => 'Data Expiração DD/MM/AAAA']) }}
        </div>
        <div class="form-group col-sm-6">
            {{ Form::label('comissao', 'Comissão', []) }}
            {{ Form::select('comissao', $comissao, $dados['comissao'], ['id' => 'comissao', 'class' => 'form-control text-right']) }}
        </div>
    </div>
    <div class="form-row" id="linha_sem_desconto_adicional">
        <div class="form-group col-sm-10 ml-4">
            {{ Form::checkbox('sem_desconto_adicional', 'true', $dados['sem_desconto_adicional'], ['id' => 'sem_desconto_adicional', 'class' => 'form-check-input']) }}
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
    initMaskCamposEdt($(document).find('#frm_cad_produto_promocional_edt'));
    $(document).ready( function () {
        $('[data-toggle="popover"]').popover();
        form_modal_edt = $(document).find('#frm_cad_produto_promocional_edt');
        form_modal_edt.find("#descricao").autocomplete(optionsAutoCompleteProdutoNasajon());
        form_modal_edt.find("#grupo").autocomplete(optionsAutoCompleteGrupo());
        form_modal_edt.find("#cliente").autocomplete(optionsAutoCompleteClienteAdd(form_modal_edt));
        form_modal_edt.find("#bt-search-produto").on('click', function(){
            var dados_error = form_modal_edt.find('.error-input');
            if(dados_error.length > 0){
                form_modal_edt.find('#codigo_produto').val('');
                form_modal_edt.find('#descricao').val('');
                form_modal_edt.find('#grupo').val('');
            }
            showModalProdutoEdt(form_modal_edt);
        });
        form_modal_edt.find("#bt-search-cliente-busca").on("click", function(){
            showModalClienteEdt($(this).data("route"), "Lista de Clientes");
        });
        form_modal_edt.find('#cliente').on("blur", function(){
            if(form_modal_edt.find('#cliente').val() == ''){
                form_modal_edt.find('#codigo_cliente').val('');
            }
        });
        form_modal_edt.find('#codigo_produto').blur(function(){
            pesquisaProdutoCodigoEdt(form_modal_edt);
        });
        form_modal_edt.find('#descricao').change(function() {
            limparMesagemErroEdt(false);
            pesquisaProdutoDescricaoEdt(form_modal_edt);
        });
		form_modal_edt.find('#grupo').change(function() {
            limparMesagemErroEdt(false);
            pesquisaPrecoFobEdt(form_modal_edt);
            form_modal_edt.find('#codigo').focus();
        });
        form_modal_edt.find('[type="button"]').on('click', function(){
            editarDados(form_modal_edt.serialize());
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

        exibicaoTipoPromocional();
    });
    function initMaskCamposEdt(form_modal_edt){
        form_modal_edt.find("#preco_real").maskMoney({thousands:'', decimal:','});
        form_modal_edt.find("#porcentagem").maskMoney({thousands:'', decimal:','}); 

        form_modal_edt.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
            startDate: new Date(),
        });
        form_modal_edt.find('.data').mask('00/00/0000');
    }
    function optionsAutoCompleteEdt($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_produto_promocional_edit_delete').css('z-index')) + 1));
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
                            form_modal_edt.find('#codigo_produto').focus();
                            break;
                        default:
                            form_modal_edt.find('#cliente').focus();
                            break;
                    }
                    limparMesagemErroEdt(false);
                    pesquisaProdutoDescricaoEdt(form_modal_edt);
                }, 100);
            }
        };
    }

    function optionsAutoCompleteClienteEdt(form_modal_edt){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_produto_promocional_edit_delete').css('z-index')) + 1));
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
                form_modal_edt.find("#codigo_cliente").val(ui.item.value);
                form_modal_edt.find("#cliente").val(ui.item.label);
                form_modal_edt.find('#estabelecimento').focus();
                return false;
            }
        };
    }

    function showModalProdutoEdt(form_modal_edt){
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
                        returnDadosProdutoEdt($(this), form_modal_edt);
                    });
                });
            }
        });
    }

    function showModalClienteEdt(url, title, form_modal_edt){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("cliente_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#cliente_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#cliente_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosClienteEdt($(this),form_modal_edt);
                        });
                    });
                });
            }
        });
    }

    function pesquisaProdutoCodigoEdt(form_modal_edt){
        limparMesagemErroEdt(false);
        data_form_modal_edt = form_modal_edt.serialize();

        $.ajax({
            url: '{{ route('produto.pesquisaprodutocodigo')}}',
            data: data_form_modal_edt,
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoEdt(produto, form_modal_edt);
            },
            error: function(callback){
                if(form_modal_edt.find('#codigo_produto').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_edt.find('#codigo_produto').focus();
                    mensagemErroEdt(dados);
                }
                limparCamposEdt('codigo_produto');
            }
        });
    }

    function pesquisaProdutoDescricaoEdt(){
        form_modal_edt = $(document).find('#frm_cad_produto_promocional_edt');
        data_form_modal_edt = form_modal_edt.serialize();
        $.ajax({
            url: '{{ route('produto.pesquisaprodutodescricao')}}',
            data: data_form_modal_edt,
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                dadosRetornoEdt(produto, form_modal_edt);
            },
            error: function(callback){
                if(form_modal_edt.find('#descricao').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_edt.find('#descricao').focus();
                    mensagemErroEdt(dados);
                }
                limparCamposEdt('descricao');
            }
        });
    }

    function pesquisaPrecoFobEdt(){
        form_modal_edt = $(document).find('#frm_cad_produto_promocional_edt');
        data_form_modal_edt = form_modal_edt.serialize();
        $.ajax({
            url: '{{ route('produto.pesquisaprecofob')}}',
            data: data_form_modal_edt,
            method: 'POST',
            success: function(callback){
                var produto = callback.response;
                
                form_modal_edt.find('#preco_fob').val(produto['preco_fob']);
            },
            error: function(callback){
                if(form_modal_edt.find('#preco_fob').val() != ''){
                    var dados = callback.responseJSON;
                    form_modal_edt.find('#preco_fob').focus();
                    mensagemErroEdt(dados);
                }
                limparCamposEdt('preco_fob');
            }
        });
    }

    function returnDadosClienteEdt($dados,form_modal_edt){
        form_modal_edt = $(document).find('#frm_cad_produto_promocional_edt');
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente_search_show").modal("hide");
        form_modal_edt.find("#codigo_cliente").val($dados.find("td").eq(0).text());
        form_modal_edt.find("#cliente").val($dados.find("td").eq(1).text()+" "+$dados.find("td").eq(3).text());
    }

    function returnDadosProdutoEdt($dados, form_modal_edt){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_produto").modal("hide");
        
        form_modal_edt.find('#codigo_produto').val($dados.find("td").eq(1).text());
        pesquisaProdutoCodigoEdt(form_modal_edt);
    };

    function dadosRetornoEdt(produto, form_modal_edt){
        form_modal_edt.find('#codigo_produto').val(produto['codigo_produto']);
        form_modal_edt.find('#descricao').val(produto['descricao']);
        form_modal_edt.find('#grupo').val(produto['grupo']);
        form_modal_edt.find('#preco_fob').val(produto['preco_fob']);
        form_modal_edt.find('#cliente').focus();
    }

    function editarDados(data_form_modal_edt){
        event.stopPropagation();
        $.ajax({
            url: '{{ route("listaprecosprodutospromocionais.editar") }}',
            dataType: 'json',
            data: data_form_modal_edt,
            method: 'POST',
            success: function(data){
                limparCamposEdt('todos');
                $(form_modal_edt).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(data){
                var dados = data.responseJSON;
                limparMesagemErroEdt(true);
                mensagemErroEdt(dados);
            }
        });
    }

    function limparCamposEdt($campos){
        form_modal_edt = $(document).find('#frm_cad_produto_promocional_edt');

        switch($campos){
            case 'descricao':
                form_modal_edt.find('#codigo_produto').val('');
            break;
            case 'codigo_produto':
                form_modal_edt.find('#descricao').val('');
            break;
            case 'preco_fob':
                form_modal_edt.find('#codigo_produto').val('');
                form_modal_edt.find('#descricao').val('');
                form_modal_edt.find('#preco_fob').val('0,00');
            break;
            case 'todos':
				form_modal_edt.find('#grupo').val('');
                form_modal_edt.find('#codigo_produto').val('');
                form_modal_edt.find('#descricao').val('');
                form_modal_edt.find('#cliente').val('');
                form_modal_edt.find('#estabelecimento').val('');
                form_modal_edt.find('#tipo_frete').val('');
                form_modal_edt.find('#vendedor').val('');
                form_modal_edt.find('#preco_real').val('');
                form_modal_edt.find('#preco_fob').val('');
                form_modal_edt.find('#data_expiracao').val('');
            break;
        }
    }

    function mensagemErroEdt(json_error){
        var form_modal_edt = $("#frm_cad_produto_promocional_edt");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsEdt(form_modal_edt, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsEdt(form_modal_edt, input, message){
        if(input.localeCompare('codigo_produto') == 0){
            var $input = $(form_modal_edt).find("#bt-search-produto");
            $(form_modal_edt).find("input[name='codigo_produto']").addClass('error-input');
        }else if(input.localeCompare('cliente') == 0){
            var $input = $(form_modal_add).find("#bt-search-cliente-busca");
            $(form_modal_add).find("input[name='cliente']").addClass('error-input');
        }else{
            var $input = $(form_modal_edt).find("input[name='"+input+"'], select[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function limparMesagemErroEdt($todos){      
        var form_modal_edt = $("#frm_cad_produto_promocional_edt");
        if($todos){
            form_modal_edt.find('.error-message').remove();
            form_modal_edt.find('input, select, span').removeClass('error-input');
        }else{
            form_modal_edt.find('#codigo_produto').removeClass('error-input');
            form_modal_edt.find('#descricao').removeClass('error-input');
            form_modal_edt.find('#bt-search-produto').removeClass('error-input');
            form_modal_edt.find('label[for=errcodigo_produto]').remove();
            form_modal_edt.find('label[for=errdescricao]').remove();
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_produto_promocional_edit_delete').css('z-index')) + 1));
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
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_produto_promocional_edit_delete').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
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

    function exibicaoTipoPromocional(){
        form_modal_edt = $(document).find('#frm_cad_produto_promocional_edt');
        limparMesagemErroEdt(true);
        switch(form_modal_edt.find("#tipo_promocional").val()){
            case "projeto":
                form_modal_edt.find("#linha_grupo").hide();
                form_modal_edt.find("#linha_produto").hide();
                form_modal_edt.find("#linha_estabelecimento_frete").hide();
                form_modal_edt.find("#linha_tipo_desconto").hide();
                form_modal_edt.find("#linha_preco").hide();
                form_modal_edt.find("#linha_sem_desconto_adicional").hide();
                form_modal_edt.find("#linha_comissao").show();
                form_modal_edt.find("#linha_botao_projeto").show();
                form_modal_edt.find("#linha_botao_pedido").hide();
                break;
            case "pedido":
                form_modal_edt.find("#linha_grupo").show();
                form_modal_edt.find("#linha_produto").show();
                form_modal_edt.find("#linha_estabelecimento_frete").show();
                form_modal_edt.find("#linha_tipo_desconto").show();
                form_modal_edt.find("#linha_preco").show();
                form_modal_edt.find("#linha_sem_desconto_adicional").show();
                form_modal_edt.find("#linha_comissao").hide();
                form_modal_edt.find("#linha_botao_projeto").hide();
                form_modal_edt.find("#linha_botao_pedido").show();
                break;
        }
    }
</script>
@endsection