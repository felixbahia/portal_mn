@extends('layouts.page-dialog')

@section('content')
<form id="form_edit_transportadora" name="form_edit_transportadora">
    @csrf
        {{ Form::hidden('id', $id, ['id' => 'id']) }}
    <div class="form-row">
        <div class="col-sm">
            <div class="input-group">
                {{ Form::label('transportadora_redespacho_nome', 'Transportadora', []) }}
            </div>
            <div class="input-group">
                {{ Form::text('transportadora_nome', $transportadora_nome, ['id' => 'transportadora_nome', 'class' => 'form-control input-label']) }}
                {{ Form::hidden('transportadora_codigo', $transportadora_codigo, ['id' => 'transportadora_codigo']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-transportadora" data-route="{{ route("transportador.index.dialog") }}"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
            </div>
            <div class="input-group">
                {{ Form::label('transportadora_redespacho_nome', 'Transportadora Redespacho', []) }}
            </div>
            <div class="input-group">
                {{ Form::text('transportadora_redespacho_nome', $transportadora_redespacho_nome, ['id' => 'transportadora_redespacho_nome', 'class' => 'form-control input-label']) }}
                {{ Form::hidden('transportadora_resdespacho_codigo', $transportadora_resdespacho_codigo, ['id' => 'transportadora_resdespacho_codigo', 'class' => 'form-control']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-transportadora_redespacho" data-route="{{ route("transportador.index.dialog") }}"><i id="bt-view-transportadora-redespacho" class="bt-view m-1"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Editar', ['id' => 'bt-carrinho-editar', 'class' => 'btn btn-primary']) }}
    </div>
</form>

<script>
    $(document).ready( function(){
        $(document).find("#transportadora_nome").autocomplete(optionsAutoCompleteTransportador());
        $(document).find("#transportadora_redespacho_nome").autocomplete(optionsAutoCompleteTransportadorRedespacho());

        $(document).find("#bt-view-transportadora").off("click");
        $(document).find("#bt-view-transportadora").on("click", function(event){
            event.stopPropagation();
            modalTransportador();
            return false;
        });
        $(document).find("#bt-view-transportadora-redespacho").off("click");
        $(document).find("#bt-view-transportadora-redespacho").on("click", function(event){
            event.stopPropagation();
            modalTransportadorRedespacho();
            return false;
        });

        $(document).find("#bt-carrinho-editar").off('click');
            $(document).find("#bt-carrinho-editar").on('click', function(){
                event.stopPropagation();
                editarTransportadora();
            });
    });

    function optionsAutoCompleteTransportador(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('transportador.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_editar_transportadora').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma transportadora encontrada');
                    event.stopPropagation();
                    $(document).find("#transportadora_nome").focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#transportadora_codigo").val(ui.item.value);
                $(document).find("#transportadora_nome").val(ui.item.label);
                return false;
            }
        };
    }
    function optionsAutoCompleteTransportadorRedespacho(){
        $(document).find(".error-message").remove();

        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('transportador.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_editar_transportadora').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma transportadora encontrada');
                    event.stopPropagation();
                    $(document).find("#transportadora_redespacho_nome").focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                $(document).find("#transportadora_resdespacho_codigo").val(ui.item.value);
                $(document).find("#transportadora_redespacho_nome").val(ui.item.label);
                return false;
            }
        };
    }

    function modalTransportador(){
        $.ajax({
            url: '{{ Route('transportador.index.dialog') }}',
            type: 'POST',
           data: {_token: '{{ csrf_token() }}'},
            success: function(data){
				$(document).find('#modal_busca_transportador').remove();
                createModal('modal_busca_transportador', "Busca de transporadora", data, 'modal-lg');
                var modal = $(document).find("#modal_busca_transportador");
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(){
							returnDadosTransportador($(this), modal);
                        });
                    });
                });
            }

        });
	}
	function returnDadosTransportador($dados, modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
		$(document).find("#transportadora_codigo").val($dados.find("td:eq(0)").text());
		$(document).find("#transportadora_nome").val($dados.find("td:eq(1)").text() + " - " + $dados.find("td:eq(2)").text());
		modal.modal('hide');
	}
	
    function modalTransportadorRedespacho(){
        $.ajax({
            url: '{{ Route('transportador.index.dialog') }}',
            type: 'POST',
           data: {_token: '{{ csrf_token() }}'},
            success: function(data){
				$(document).find("#modal_busca_transportador_redespacho").remove();
                createModal('modal_busca_transportador_redespacho', "Busca de transporadora para redespacho", data, 'modal-lg');
                var modal = $(document).find("#modal_busca_transportador_redespacho");
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(){
							returnDadosTransportadorRedespacho($(this), modal);
                        });
                    });
                });
            }
        });
	}
	function returnDadosTransportadorRedespacho($dados, modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
		$(document).find("#transportadora_resdespacho_codigo").val($dados.find("td:eq('0')").text() );
		$(document).find("#transportadora_redespacho_nome").val($dados.find("td:eq(1)").text() + " - " + $dados.find("td:eq(2)").text());
		modal.modal('hide');
	}

    function editarTransportadora(){
        form = $(document).find("#form_edit_transportadora");
        $.ajax({
            url: '{{ route("book_virtual_exibicao.editar_transportadora") }}',
            data: form.serialize(),
            method: 'POST',
            success: function(body){
                $(document).find('#modal_editar_transportadora').modal('hide');
                message("Atenção", "Transportadora atualizada com sucesso!")
            },
            error: function (callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErro(dados);
            }
        });
    }
    
    function limparMesagemErroAdd(){      
        var form_modal_add = $(document).find("#form_edit_transportadora");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErro(json_error){
        var form_modal_add = $(document).find("#form_edit_transportadora");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputs(form_modal_add, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection
