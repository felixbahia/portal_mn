@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_modal" id="form_modal" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $dados['id'], ['id' => 'id']) !!}
    <div class="form-row">
        <div class="form-group col-sm-6"> 
            {{ Form::label('modo', 'Modo', []) }}
             @if(!empty($fixo_modulo))
                {{ Form::select('modo', $modos, $fixo_modulo, ['id' => 'modo', 'class' => 'form-control', 'placeholder' => 'Selecione o Modo', 'disabled' => 'disabled']) }}
            @else
                {{ Form::select('modo', $modos, $dados['modo'], ['id' => 'modo', 'class' => 'form-control', 'placeholder' => 'Selecione o Modo', 'disabled' => 'disabled']) }}
            @endif
        </div>
        <div class="form-group col-sm-6"> 
            {{ Form::label('tipo', 'Tipo', []) }}
            {{ Form::select('tipo', $tipos, $dados['tipo'], ['id' => 'tipo', 'class' => 'form-control', 'placeholder' => 'Selecione o Tipo']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('fornecedor', 'Fornecedor', []) }} 
            <div class="input-group">
                {{ Form::text('fornecedor', $dados['fornecedor'], ['id' => 'fornecedor', 'class' => 'form-control input-label', 'placeholder' => 'Fornecedor', 'onkeyup' => "optionsFornecedor($(this))", 'disabled' => 'disabled']) }}
            </div>
        </div>
    </div>
    @if(!empty($fixo_modulo))
        <div class="form-row">
            <div class="form-group col-sm-6" id="condicao_pagamento_group" class="invisible">
                {{ Form::label('condicao_pagamento_descr', 'Condição de Pagamento', []) }}	 <span  data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span>
                <div class="input-group">
                    {{ Form::text('condicao_pagamento_descr', $dados['condicao_pagamento'], array('id' => 'condicao_pagamento_descr', 'class' => 'form-control essencial')) }}
                    {{ Form::hidden('condicao_pagamento', '', ['id' => 'condicao_pagamento', 'class' => ''])}}
                    <span class="input-group-addon border rounded-right" id="bt-search-condicao_pagamento" data-route="{{ route("condicoes_pagamento_web.dialog") }}"><i id="bt-view-condicao" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="form-group col-sm-3">
                {{ Form::label('valor', 'Valor', []) }}
                {!! Form::text('valor', $dados['valor'], ['id' => 'valor', 'class' => 'form-control moeda text-right', 'placeholder' => 'Valor']) !!}
            </div>
            <div class="form-group col-sm-3">            
                {{ Form::label('dia_fluxo_inicial', 'Dia do Faturamento', []) }}
                {!! Form::number('dia_fluxo_inicial', $dados['dia_fluxo_inicial'], ['id' => 'dia_fluxo_inicial', 'class' => 'form-control text-right', 'placeholder' => 'Dia do Faturamento', 'min' => '1', 'max' => '28']) !!}
            </div>
        </div>
    @endif
    <div class="form-row">
        <div class="form-group col-sm-6"> 
            {{ Form::label('ano_do', 'Do Ano', []) }}
            {{ Form::text('ano_do', $dados['ano'], ['id' => 'ano_do', 'class' => 'form-control data', 'placeholder' => 'Do Ano', 'maxlength' => '20']) }}
        </div>
        <div class="form-group col-sm-6"> 
            {{ Form::label('ano_ate', 'Até o Ano', []) }}
            {{ Form::text('ano_ate', $dados['ano'], ['id' => 'ano_ate', 'class' => 'form-control data', 'placeholder' => 'Até o Ano', 'maxlength' => '20']) }}
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
                    @if($dados['meses_edicao']['janeiro'])
                        {!! Form::checkbox('janeiro', '01', true, ['id' => 'janeiro', 'class' => 'form-check-input']) !!}
                    @else
                        {!! Form::checkbox('janeiro', '01', false, ['id' => 'janeiro', 'class' => 'form-check-input']) !!}
                    @endif                    
                    {!! Form::label('janeiro', 'Janeiro', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    @if($dados['meses_edicao']['fevereiro'])
                        {!! Form::checkbox('fevereiro', '02', true, ['id' => 'fevereiro', 'class' => 'form-check-input']) !!}
                    @else
                        {!! Form::checkbox('fevereiro', '02', false, ['id' => 'fevereiro', 'class' => 'form-check-input']) !!}
                    @endif 
                    {!! Form::label('fevereiro', 'Fevereiro', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    @if($dados['meses_edicao']['marco'])
                        {!! Form::checkbox('marco', '03', true, ['id' => 'marco', 'class' => 'form-check-input']) !!}
                    @else
                        {!! Form::checkbox('marco', '03', false, ['id' => 'marco', 'class' => 'form-check-input']) !!}
                    @endif 
                    {!! Form::label('marco', 'Março', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    @if($dados['meses_edicao']['abril'])
                        {!! Form::checkbox('abril', '04', true, ['id' => 'abril', 'class' => 'form-check-input']) !!}
                    @else
                        {!! Form::checkbox('abril', '04', false, ['id' => 'abril', 'class' => 'form-check-input']) !!}
                    @endif 
                    {!! Form::label('abril', 'Abril', ['class' => 'form-check-label']) !!}
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-3">
                <div class="form-check">
                    @if($dados['meses_edicao']['maio'])
                        {!! Form::checkbox('maio', '05', true, ['id' => 'maio', 'class' => 'form-check-input']) !!}
                    @else
                        {!! Form::checkbox('maio', '05', false, ['id' => 'maio', 'class' => 'form-check-input']) !!}
                    @endif 
                    {!! Form::label('maio', 'Maio', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    @if($dados['meses_edicao']['junho'])
                        {!! Form::checkbox('junho', '06', true, ['id' => 'junho', 'class' => 'form-check-input']) !!}
                    @else
                        {!! Form::checkbox('junho', '06', false, ['id' => 'junho', 'class' => 'form-check-input']) !!}
                    @endif                    
                    {!! Form::label('junho', 'Junho', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    @if($dados['meses_edicao']['julho'])
                        {!! Form::checkbox('julho', '07', true, ['id' => 'julho', 'class' => 'form-check-input']) !!}
                    @else
                        {!! Form::checkbox('julho', '07', false, ['id' => 'julho', 'class' => 'form-check-input']) !!}
                    @endif 
                    {!! Form::label('julho', 'Julho', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    @if($dados['meses_edicao']['agosto'])
                        {!! Form::checkbox('agosto', '08', true, ['id' => 'agosto', 'class' => 'form-check-input']) !!}
                    @else
                        {!! Form::checkbox('agosto', '08', false, ['id' => 'agosto', 'class' => 'form-check-input']) !!}
                    @endif 
                    {!! Form::label('agosto', 'Agosto', ['class' => 'form-check-label']) !!}
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-sm-3">
                <div class="form-check">
                    @if($dados['meses_edicao']['setembro'])
                        {!! Form::checkbox('setembro', '09', true, ['id' => 'setembro', 'class' => 'form-check-input']) !!}
                    @else
                        {!! Form::checkbox('setembro', '09', false, ['id' => 'setembro', 'class' => 'form-check-input']) !!}
                    @endif 
                    {!! Form::label('setembro', 'Setembro', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    @if($dados['meses_edicao']['outubro'])
                        {!! Form::checkbox('outubro', '10', true, ['id' => 'outubro', 'class' => 'form-check-input']) !!}
                    @else
                        {!! Form::checkbox('outubro', '10', false, ['id' => 'outubro', 'class' => 'form-check-input']) !!}
                    @endif 
                    {!! Form::label('outubro', 'Outubro', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    @if($dados['meses_edicao']['novembro'])
                        {!! Form::checkbox('novembro', '11', true, ['id' => 'novembro', 'class' => 'form-check-input']) !!}
                    @else
                        {!! Form::checkbox('novembro', '11', false, ['id' => 'novembro', 'class' => 'form-check-input']) !!}
                    @endif 
                    {!! Form::label('novembro', 'Novembro', ['class' => 'form-check-label']) !!}
                </div>
            </div>
            <div class="form-group col-sm-3">
                <div class="form-check">
                    @if($dados['meses_edicao']['dezembro'])
                        {!! Form::checkbox('dezembro', '12', true, ['id' => 'dezembro', 'class' => 'form-check-input']) !!}
                    @else
                        {!! Form::checkbox('dezembro', '12', false, ['id' => 'dezembro', 'class' => 'form-check-input']) !!}
                    @endif 
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
        form_modal = $(document).find("#form_modal");

        form_modal.find('.data').datepicker({ 
            format: 'yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form_modal.find('.data').mask('0000');

        form_modal.find(".moeda").maskMoney({thousands:'.', decimal:','});

        form_modal.find("#btn-salvar").off('click');
        form_modal.find("#btn-salvar").on('click', function(){
            loader();
            editarDados(form_modal);
        });

        form_modal.find("#bt-search-fornecedor-busca").on("click", function(){
            showModalFornecedor($(this).data("route"), "Lista de Fornecedores", "fornecedor");
        });

        form_modal.find("#condicao_pagamento_descr").autocomplete(optionsAutoCompleteCondicoes());  
        form_modal.find("#bt-view-condicao").off("click");
        form_modal.find("#bt-view-condicao").on("click", function(event){
            event.stopPropagation();
            modalCondicao($(this).parent());
            return false;
        });

        form_modal.find('#selecione_todos').on('click', function(){
            if (form_modal.find('#selecione_todos').is(':checked')){
                $('input:checkbox').prop("checked", true);
              }else{
                $('input:checkbox').prop("checked", false);
              }
        });
    });

    function editarDados(form_modal){
        loader();
        limparMesagemErroModal(form_modal);
        data_form_modal = form_modal.serialize();
        $.ajax({
            url: "{{ route('orcamento_compras.editar') }}", 
            dataType: 'json',
            data: data_form_modal,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal).parents('.modal').modal('hide');
                hide_loader();
                filterAjax($("#form_filter").serialize());
            },
            complete: function(){
                loader();
            },
            error: function(callback){
                var dados = callback.responseJSON;
                mensagemErroModal(form_modal, dados);
            }
        });
    }

    function limparMesagemErroModal(form_modal){      
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span, div').removeClass('error-input');
    }

    function mensagemErroModal(form_modal, json_error){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModal(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form_modal, input, message){
        var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
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
        form_modal.find("#"+campo).val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
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

    function loader(){
        var $html_loader = "<div class='content-loader'><div class='loader'></div></div>";
        $("body").prepend($html_loader);
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
                $(document).find('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_orcamento_compras_edit_delete').css('z-index')) + 1));
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
        form_modal = $(document).find('#form_modal');
        form_modal.find("#condicao_pagamento_descr").data('oldvalue', $(document).find("#condicao_pagamento_descr").val());
        form_modal.find("#condicao_pagamento").val($dados.find("td:eq(0)").text() );
        form_modal.find("#condicao_pagamento_descr").val($dados.find("td:eq(1)").text() );
        $(document).find("#modal_busca_condicao").modal("hide");
    }
</script>
@endsection