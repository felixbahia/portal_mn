@extends('layouts.page-dialog')

@section('content')
<form id="form_edit_cliente">
    <div class="form-row">
        <div class="col-sm">
            <div class="input-group">
                {{ Form::hidden('id', $cliente->id, ['id' => 'id']) }}
                {{ Form::text('cliente_nome', $cliente->cliente_nasajon->nome . ' - ' . $cliente->cpf_cnpj, ['id' => 'cliente_nome_modal_edit', 'class' => 'form-control input-label', 'placeholder' => 'Cliente']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Enviar', ['id' => 'form_edit_cliente_btn', 'class' => 'btn btn-success']) }}
    </div>
</form>

<script>
    $(document).ready( function(){
        $(document).find("#cliente_nome_modal_edit").autocomplete(optionsAutoCompleteCliente('cliente_nome_modal_edit'));

        $(document).find('#form_edit_cliente_btn').on('click', function(event){
            event.stopPropagation();

            $.ajax({
                url: "{{ route('cliente_prepago.update') }}",
                dataType: 'json',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: $(document).find("#id").val(), 
                    cliente_nome: $(document).find("#cliente_nome_modal_edit").val(),
                },
                success: function(){
                    $(document).find('#modal_cliente_edit_prepago').modal('hide');
                    filterAjax();
                },
                error: function(callback){
                    errors = callback.responseJSON.errors;

                    for(var field in errors){
                        showErrorsInputs('#form_edit_cliente', field, errors[field]);
                    }
                }
            })
        });
    });

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.parent().children().addClass('error-input');
    }

</script>
@endsection
