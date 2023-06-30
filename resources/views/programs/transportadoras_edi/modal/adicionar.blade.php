@extends('layouts.page-dialog')

@section('content')
<form id="form_add_transportadora">
    <div class="form-row">
        <div class="col-sm">
            <div class="input-group">
                {{ Form::text('transportadora_edi_modal', '', ['id' => 'transportadora_edi_modal_add', 'class' => 'form-control input-label', 'placeholder' => 'Transportadora', 'maxlength' => '250']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-transportadora-busca-adicionar" data-route="{{ route("transportador.index.dialog") }}"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row mt-3">
        <div class="input-group">
            <div class="input-group">
                {{ Form::email('email',  '', ['id' => 'email', 'class' => 'form-control', 'placeholder' => 'Email', 'maxlength' => '250']) }}
            </div>
        </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Cadastrar', ['id' => 'form_edit_transportadora_btn', 'class' => 'btn btn-success']) }}
    </div>
</form>

<script>
    $(document).ready( function(){
        $(document).find("#transportadora_edi_modal_add").autocomplete(optionsAutoCompleteTransportadorAdd());

        $(document).find("#bt-search-transportadora-busca-adicionar").off("click");
        $(document).find("#bt-search-transportadora-busca-adicionar").on("click", function(event){
            event.stopPropagation();
            modalTransportadorAdd();
        });

        $(document).find('#form_edit_transportadora_btn').on('click', function(event){
            event.stopPropagation();

            $.ajax({
                url: "{{ route('transportadoras_edi.adicionar') }}",
                dataType: 'json',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    transportadora_edi_modal: $(document).find("#transportadora_edi_modal_add").val(),
                    email: $(document).find("#email").val(),
                },
                success: function(){
                    $(document).find('#modal_add_transportadoras_edi').modal('hide');
                    filtro();
                },
                error: function(callback){
                    errors = callback.responseJSON.error;
                    
                    for(var field in errors){
                        limparMesagemErroAdd();
                        showErrorsInputsAdd('#form_add_transportadora', field, errors[field]);
                    }
                }
            })
        });
    });

    function optionsAutoCompleteTransportadorAdd(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('transportador.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_add_transportadoras_edi').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma transportadora encontrada');
                    event.stopPropagation();
                    $(document).find("#transportadora_edi_modal_add").focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#transportadora_edi_modal_add").val(ui.item.label);
                return false;
            }
        };
    }

    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_add_transportadora");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function showErrorsInputsAdd(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.parent().children().addClass('error-input');
    }

    function modalTransportadorAdd(){
        $.ajax({
            url: '{{ Route("transportador.index.dialog") }}',
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
                            returnDadosTransportadorAdd($(this), modal);
                        });
                    });
                });
            }
        });
    }

    function returnDadosTransportadorAdd($dados, modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
		$(document).find("#transportadora_edi_modal_add").val($dados.find("td:eq(1)").text() + " - " + $dados.find("td:eq(2)").text());
		modal.modal('hide');
    }

</script>
@endsection
