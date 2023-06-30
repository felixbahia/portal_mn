@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_orcamento_compras" id="form_orcamento_compras" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-6"> 
            {{ Form::label('modo', 'Modo', []) }}
            @if(!empty($fixo_modulo))
                {{ Form::select('modo', $modos, $fixo_modulo, ['id' => 'modo', 'class' => 'form-control', 'placeholder' => 'Selecione o Modo', 'disabled' => 'disabled']) }}
            @else
                {{ Form::select('modo', $modos, '', ['id' => 'modo', 'class' => 'form-control', 'placeholder' => 'Selecione o Modo']) }}
            @endif
        </div>
        <div class="form-group col-sm-6"> 
            {{ Form::label('tipo', 'Tipo', []) }}
            {{ Form::select('tipo', $tipos, '', ['id' => 'tipo', 'class' => 'form-control', 'placeholder' => 'Selecione o Tipo']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('fornecedor', 'Fornecedor', []) }} 
            <div class="input-group">
                {{ Form::text('fornecedor', '', ['id' => 'fornecedor', 'class' => 'form-control input-label', 'placeholder' => 'Fornecedor', 'onkeyup' => "optionsFornecedor($(this))"]) }}
                <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    @if($fixo_modulo == 'competencia')
        <div class="form-row">
            <div class="form-group col-sm-6" id="condicao_pagamento_group" class="invisible">
                {{ Form::label('condicao_pagamento_descr', 'Condição de Pagamento', []) }}	 <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                <div class="input-group">
                    {{ Form::text('condicao_pagamento_descr', '', array('id' => 'condicao_pagamento_descr', 'class' => 'form-control essencial')) }}
                    {{ Form::hidden('condicao_pagamento', '', ['id' => 'condicao_pagamento', 'class' => ''])}}
                    <span class="input-group-addon border rounded-right" id="bt-search-condicao_pagamento" data-route="{{ route("condicoes_pagamento_web.dialog") }}"><i id="bt-view-condicao" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="form-group col-sm-3">
                {!! Form::label('valor', 'Valor (R$)', []) !!}<a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="top" title="" data-original-title="Valor que será lançado para cada mês apontado." style="color: black;"></a>
                {!! Form::text('valor', '', ['id' => 'valor', 'class' => 'form-control moeda text-right', 'placeholder' => 'Valor']) !!}
            </div>
            <div class="form-group col-sm-3">            
                {{ Form::label('dia_fluxo_inicial', 'Dia do Faturamento', []) }}
                {!! Form::number('dia_fluxo_inicial', '', ['id' => 'dia_fluxo_inicial', 'class' => 'form-control text-right data_dia', 'placeholder' => 'Dia do Faturamento', 'min' => '1', 'max' => '28']) !!}
            </div>
        </div>
    @endif
    <div class="form-row">
        <div class="form-group col-sm-6"> 
            {{ Form::label('ano_do', 'Do Ano', []) }}
            {{ Form::text('ano_do', date('Y'), ['id' => 'ano_do', 'class' => 'form-control data', 'placeholder' => 'Do Ano', 'maxlength' => '20']) }}
        </div>
        <div class="form-group col-sm-6"> 
            {{ Form::label('ano_ate', 'Até o Ano', []) }}
            {{ Form::text('ano_ate', date('Y'), ['id' => 'ano_ate', 'class' => 'form-control data', 'placeholder' => 'Até o Ano', 'maxlength' => '20']) }}
        </div>
    </div>
    <fieldset>
        <label>Meses: </label><a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="top" title="Ano Atual, só vai se considerado mês atual e posterior." data-original-title="." style="color: black;"></a>
        <div class="form-row">
            <div class="form-group col-sm-12">
                <div class="form-check">
                    {!! Form::checkbox('selecione_todos', 'selecione_todos', false, ['id' => 'selecione_todos', 'class' => 'form-check-input']) !!}
                    {!! Form::label('selecione_todos', 'Selecione Todos Meses', ['class' => 'form-check-label']) !!}
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-3">
                <div class="form-check">
                    {!! Form::checkbox('janeiro', '01', false, ['id' => 'janeiro', 'class' => 'form-check-input']) !!}
                    {!! Form::label('janeiro', 'Janeiro', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    {!! Form::checkbox('fevereiro', '02', false, ['id' => 'fevereiro', 'class' => 'form-check-input']) !!}
                    {!! Form::label('fevereiro', 'Fevereiro', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    {!! Form::checkbox('marco', '03', false, ['id' => 'marco', 'class' => 'form-check-input']) !!}
                    {!! Form::label('marco', 'Março', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    {!! Form::checkbox('abril', '04', false, ['id' => 'abril', 'class' => 'form-check-input']) !!}
                    {!! Form::label('abril', 'Abril', ['class' => 'form-check-label']) !!}
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-3">
                <div class="form-check">
                    {!! Form::checkbox('maio', '05', false, ['id' => 'maio', 'class' => 'form-check-input']) !!}
                    {!! Form::label('maio', 'Maio', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    {!! Form::checkbox('junho', '06', false, ['id' => 'junho', 'class' => 'form-check-input']) !!}
                    {!! Form::label('junho', 'Junho', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    {!! Form::checkbox('julho', '07', false, ['id' => 'julho', 'class' => 'form-check-input']) !!}
                    {!! Form::label('julho', 'Julho', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    {!! Form::checkbox('agosto', '08', false, ['id' => 'agosto', 'class' => 'form-check-input']) !!}
                    {!! Form::label('agosto', 'Agosto', ['class' => 'form-check-label']) !!}
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-3">
                <div class="form-check">
                    {!! Form::checkbox('setembro', '09', false, ['id' => 'setembro', 'class' => 'form-check-input']) !!}
                    {!! Form::label('setembro', 'Setembro', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    {!! Form::checkbox('outubro', '10', false, ['id' => 'outubro', 'class' => 'form-check-input']) !!}
                    {!! Form::label('outubro', 'Outubro', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    {!! Form::checkbox('novembro', '11', false, ['id' => 'novembro', 'class' => 'form-check-input']) !!}
                    {!! Form::label('novembro', 'Novembro', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    {!! Form::checkbox('dezembro', '12', false, ['id' => 'dezembro', 'class' => 'form-check-input']) !!}
                    {!! Form::label('dezembro', 'Dezembro', ['class' => 'form-check-label']) !!}
                </div>
            </div>
        </div>
    </fieldset>
    <div class="col-sm-12 mt-2" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>

<script>
    $(document).ready(function(){
        form_orcamento_compras = $(document).find("#form_orcamento_compras");

        form_orcamento_compras.find('.data').datepicker({ 
            format: 'yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
            startDate: new Date(),
        });
        form_orcamento_compras.find('.data').mask('0000');

        form_orcamento_compras.find('.data_dia').mask('00');

        form_orcamento_compras.find(".moeda").maskMoney({thousands:'.', decimal:','});

        form_orcamento_compras.find("#btn-salvar").off('click');
        form_orcamento_compras.find("#btn-salvar").on('click', function(){
            inserirDados(form_orcamento_compras);
        });

        form_orcamento_compras.find("#condicao_pagamento_descr").autocomplete(optionsAutoCompleteCondicoes());

        form_orcamento_compras.find("#bt-search-fornecedor-busca").on("click", function(){
            showModalFornecedor($(this).data("route"), "Lista de Fornecedores", "fornecedor");
        });

        form_orcamento_compras.find("#bt-view-condicao").off("click");
        form_orcamento_compras.find("#bt-view-condicao").on("click", function(event){
            event.stopPropagation();
            modalCondicao($(this).parent());
            return false;
        });

        form_orcamento_compras.find("#ano_do").off('change');
        form_orcamento_compras.find("#ano_do").on('change', function(){
            form_orcamento_compras.find("#ano_ate").val(form_orcamento_compras.find("#ano_do").val());
        });

        form_orcamento_compras.find('#selecione_todos').on('click', function(){
            if (form_orcamento_compras.find('#selecione_todos').is(':checked')){
                $('input:checkbox').prop("checked", true);
              }else{
                $('input:checkbox').prop("checked", false);
              }
        });
    });

    function inserirDados(form_orcamento_compras){
        limparMesagemErroModal(form_orcamento_compras);
        data_form_orcamento_compras = form_orcamento_compras.serialize();
        $.ajax({
            url: "{{ route('orcamento_compras.adicionar') }}", 
            dataType: 'json',
            data: data_form_orcamento_compras,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_orcamento_compras).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                mensagemErroModal(form_orcamento_compras, dados);
            }
        });
    }

    function limparMesagemErroModal(form_orcamento_compras){      
        form_orcamento_compras.find('.error-message').remove();
        form_orcamento_compras.find('input, select, span, div').removeClass('error-input');
    }

    function mensagemErroModal(form_orcamento_compras, json_error){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModal(form_orcamento_compras, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form_orcamento_compras, input, message){
        if(input.localeCompare('fornecedor') == 0){
            var $input = $(form_orcamento_compras).find("#bt-search-fornecedor-busca");
            $(form_orcamento_compras).find("input[name='fornecedor']").addClass('error-input');
        }else if(input.localeCompare('condicao_pagamento_descr') == 0){
            var $input = $(form_orcamento_compras).find("#bt-search-condicao_pagamento");
            $(form_orcamento_compras).find("input[name='condicao_pagamento_descr']").addClass('error-input');
        }else{
            var $input = $(form_orcamento_compras).find("input[name='"+input+"'], select[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function showModalFornecedor(url, title, campo){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("fornecedor_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosFornecedor($(this), campo);
                        });
                    });
                });
            }
        });
    }

    function returnDadosFornecedor($this, campo){
        if($this.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#fornecedor_search_show").modal("hide");
        form_orcamento_compras.find("#"+campo).val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
    }

    function optionsFornecedor($this){
        esconderPopoverTooltip();
        $this.autocomplete(optionsAutoCompleteFornecedor($this));   
    }

    function optionsAutoCompleteFornecedor($this){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('fornecedor.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_orcamento_compras').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum fornecedor encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $this.val(ui.item.label);
                return false;
            }
        };
    }

    function optionsAutoCompleteCondicoes(){
        esconderPopoverTooltip();
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.estabelecimento = '05';
                $.post("{{ route('condicoes_pagamento_web.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $(document).find('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_orcamento_compras').css('z-index')) + 1));
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#condicao_pagamento").val(ui.item.value);
                $(document).find("#condicao_pagamento_descr").val(ui.item.label);
                return false;
            }
        };
    }

    function esconderPopoverTooltip(){
        $('[data-toggle="tooltip"]').tooltip('hide');
        $('[data-toggle="popover"]').popover('hide');
    }
    
    function modalCondicao($this){
        esconderPopoverTooltip();
        $.ajax({
            url: $this.data('route'),
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', estabelecimento: '05'},
            success: function(data){
                $(document).find("#modal_busca_condicao").remove();
                createModal('modal_busca_condicao', "Busca de condição de Pagamento", data, 'modal-lg');
                var modal = $(document).find("#modal_busca_condicao");
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(){
                            returnDadosCondicao($(this));
                        });
                    });
                });
            }
        });
    }
    
    function returnDadosCondicao($dados){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        form_orcamento_compras = $(document).find('#form_orcamento_compras');
        form_orcamento_compras.find("#condicao_pagamento_descr").data('oldvalue', $(document).find("#condicao_pagamento_descr").val());
        form_orcamento_compras.find("#condicao_pagamento").val($dados.find("td:eq(0)").text() );
        form_orcamento_compras.find("#condicao_pagamento_descr").val($dados.find("td:eq(1)").text() );
        $(document).find("#modal_busca_condicao").modal("hide");
    }
</script>
@endsection