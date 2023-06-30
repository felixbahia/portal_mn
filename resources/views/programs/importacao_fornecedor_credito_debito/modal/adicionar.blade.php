@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_importacao_fornecedor_credito_debito" id="form_importacao_fornecedor_credito_debito" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-12">
            {{ Form::label('fornecedor', 'Fornecedor', []) }} 
            <div class="input-group">
                {{ Form::text('fornecedor', '', ['id' => 'fornecedor', 'class' => 'form-control input-label', 'placeholder' => 'Fornecedor', 'onkeyup' => "optionsFornecedor($(this))"]) }}
                <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-6"> 
            <div class="form-check">
                {{ Form::radio('tipo', 'credito', '', ['class' => 'form-check-input', 'id'=>'tipo_credito', 'checked']) }}
                {{ Form::label('tipo_credito', 'Crédito', ['class'=>'form-check-label']) }}
            </div>
        </div>
        <div class="form-group col-sm-6"> 
            <div class="form-check">
                {{ Form::radio('tipo', 'debito', '', ['class' => 'form-check-input', 'id'=>'tipo_debito']) }}
                {{ Form::label('tipo_debito', 'Débito', ['class'=>'form-check-label']) }}
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('valor', 'Valor', []) }}
            {{ Form::text('valor', '', ['id' => 'valor', 'class' => 'form-control text-right decimal', 'placeholder' => 'Valor']) }}
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    $(document).ready( function () {
        form_importacao_fornecedor_credito_debito = $(document).find('#form_importacao_fornecedor_credito_debito');

        form_importacao_fornecedor_credito_debito.find(".decimal").maskMoney({thousands:'.', decimal:','});

        form_importacao_fornecedor_credito_debito.find("#bt-search-fornecedor-busca").off("click");
        form_importacao_fornecedor_credito_debito.find("#bt-search-fornecedor-busca").on("click", function(){
            showModalFornecedor($(this).data("route"), "Lista de Fornecedores", "fornecedor");
        });

        form_importacao_fornecedor_credito_debito.find("#btn-salvar").off('click');
        form_importacao_fornecedor_credito_debito.find("#btn-salvar").on('click', function(){
            inserirDados(form_importacao_fornecedor_credito_debito.serialize());
        });
    });

    function inserirDados(data_form_importacao_fornecedor_credito_debito){
        $.ajax({
            url: "{{ route('importacao.fornecedor_credito_debito.adicionar') }}", 
            dataType: 'json',
            data: data_form_importacao_fornecedor_credito_debito,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_importacao_fornecedor_credito_debito).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErro();
                mensagemErro(dados);
            }
        });
    }

    function limparMesagemErro(){      
        var form_importacao_fornecedor_credito_debito = $("#form_importacao_fornecedor_credito_debito");
        form_importacao_fornecedor_credito_debito.find('.error-message').remove();
        form_importacao_fornecedor_credito_debito.find('input, select, span').removeClass('error-input');
    }

    function mensagemErro(json_error){
        var form_importacao_fornecedor_credito_debito = $("#form_importacao_fornecedor_credito_debito");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputs(form_importacao_fornecedor_credito_debito, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputs(form_importacao_fornecedor_credito_debito, input, message){
        if(input.localeCompare('fornecedor') == 0){
            var $input = $(form_importacao_fornecedor_credito_debito).find("#bt-search-fornecedor-busca");
            $(form_importacao_fornecedor_credito_debito).find("input[name='fornecedor']").addClass('error-input');
        }else{
            var $input = $(form_importacao_fornecedor_credito_debito).find("input[name='"+input+"'], select[name='"+input+"']");
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
        form_importacao_fornecedor_credito_debito.find("#"+campo).val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_fornecedor_credito_debito_adicionar').css('z-index')) + 1));
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
</script>
@endsection