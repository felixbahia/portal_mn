@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_unidade_negocio" id="form_unidade_negocio" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('unidade', 'Unidade', []) }}
            {{ Form::text('unidade', '', ['id' => 'unidade', 'class' => 'form-control', 'placeholder' => 'Unidade', 'maxlength' => '250']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('usuario_responsavel', 'Gerente da Unidade', []) }}
            <div class="input-group" id="usuario_group">
                {{ Form::text('usuario_responsavel', '', ['id' => 'usuario_responsavel', 'class' => 'form-control', 'placeholder' => 'Gerente da Unidade', 'maxlength' => '250']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-usuario"><i class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    $(document).ready( function () {
        form_modal = $(document).find("#form_unidade_negocio");
        form_modal.find("#usuario_responsavel").autocomplete(optionsAutoCompleteUsuarioModal(form_modal));

        form_modal.find("#bt-search-usuario").off('click');
        form_modal.find("#bt-search-usuario").on('click', function(){
            showModalUsuarioModal(form_modal);
        });

        form_modal.find("#btn-salvar").on('click', function(){
            inserirDados(form_modal.serialize());
        });
    });

    function optionsAutoCompleteUsuarioModal(form_modal){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('usuario.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_unidade_negocio_adicionar').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_modal.find("#usuario_responsavel").val(ui.item.value)
                return false;
            }
        };
    }

    function inserirDados(data_form_modal){
        $.ajax({
            url: "{{ route('unidade_negocio.adicionar') }}", 
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
        var form_modal = $("#form_unidade_negocio");
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroModal(json_error){
        var form_modal = $("#form_unidade_negocio");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModal(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form_modal, input, message){
        if(input.localeCompare('usuario_responsavel') == 0){
            var $input = $(form_modal).find("#bt-search-usuario");
            $(form_modal).find("input[name='usuario_responsavel']").addClass('error-input');
        }else{
            var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
        }
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function showModalUsuarioModal(form_modal){
        $.ajax({
            url: '{{ route('usuario.modal.buscar') }}',
            type: 'GET',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_usuario", "Buscar Usuário", data, 'modal-lg');
                table_modal_buscar_user.on('draw', function () {

                    $(document).find("#table-filters-user").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-user").find('tbody').find("tr").on("click", function(){
                        returnDadosUsuarioModal($(this), form_modal);
                    });

                });
            }
        });
    }

    function returnDadosUsuarioModal($dados, form_modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_usuario").modal("hide");
        
        form_modal.find('#usuario_responsavel').val($dados.find("td").eq(0).text());
    }
</script>
@endsection