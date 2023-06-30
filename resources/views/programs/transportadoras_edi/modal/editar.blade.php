@extends('layouts.page-dialog')

@section('content')
<form action="#" id="form_edit_transportadora" name="form_edit_transportadora" onsubmit="return false;">
    <div class="form-row">
        <div class="col-sm">
            <div class="input-group">
            @csrf
                {{ Form::hidden('id', $transportadora['id'], ['id' => 'id']) }}
                {{ Form::text('transportadora_edi_modal_edit', $transportadora['transportadora_nome'] . ' - ' . $transportadora['transportadora_cnpj'], ['id' => 'transportadora_edi_modal_edit', 'class' => 'form-control input-label', 'placeholder' => 'Transportadora', 'maxlength' => '250']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-transportadora-busca-editar" data-route="{{ route("transportador.index.dialog") }}"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row mt-3">
        <div class="col-sm">
            <div class="input-group">
                {{ Form::email('email',  $transportadora['email'], ['id' => 'email', 'class' => 'form-control', 'placeholder' => 'Email', 'maxlength' => '250']) }}
            </div>
        </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Alterar', ['id' => 'form_edit_transportadora_btn', 'class' => 'btn btn-primary float-right']) }}
    </div>
</form>

<script>
    $(document).ready( function(){
        $(document).find("#transportadora_edi_modal_edit").autocomplete(optionsAutoCompleteTransportadorEdit());

        $(document).find("#bt-search-transportadora-busca-editar").off("click");
        $(document).find("#bt-search-transportadora-busca-editar").on("click", function(event){
            event.stopPropagation();
            modalTransportadorEdit();
        });

        $(document).find('#form_edit_transportadora_btn').on('click', function(event){
            event.stopPropagation();

            $.ajax({
                url: "{{ route('transportadoras_edi.editar') }}",
                dataType: 'json',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    transportadora_edi_modal_edit: $(document).find("#transportadora_edi_modal_edit").val(),
                    id: $(document).find("#id").val(),
                    email: $(document).find("#email").val(),
                },
                success: function(){
                    $(document).find('#modal_edit_transportadoras_edi').modal('hide');
                    filtro();
                },
                error: function(callback){
                    errors = callback.responseJSON.error;
                    
                    for(var field in errors){
                        limparMesagemErroEdit();
                        showErrorsInputsEdit('#form_edit_transportadora', field, errors[field]);
                    }
                }
            })
        });
    });

    function optionsAutoCompleteTransportadorEdit(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('transportador.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_edit_transportadoras_edi').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma transportadora encontrada');
                    event.stopPropagation();
                    $(document).find("#transportadora_edi_modal_edit").focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#transportadora_edi_modal_edit").val(ui.item.label);
                return false;
            }
        };
    }

    function limparMesagemErroEdit(){      
        var form_modal_add = $("#form_edit_transportadora");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function showErrorsInputsEdit(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.parent().children().addClass('error-input');
    }

    function modalTransportadorEdit(){
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
                            returnDadosTransportadorEdit($(this), modal);
                        });
                    });
                });
            }
        });
    }

    function returnDadosTransportadorEdit($dados, modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
		$(document).find("#transportadora_edi_modal_edit").val($dados.find("td:eq(1)").text() + " - " + $dados.find("td:eq(2)").text());
		modal.modal('hide');
    }

</script>
@endsection
