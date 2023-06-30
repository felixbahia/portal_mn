@extends('layouts.page-dialog')

@section('content')
<form id="form_delete_cliente" name="form_delete_cliente" action="#" onsubmit="return false">
    <div class="row">
        <div class="col-sm">
            <h5>Deseja realmente apagar este registro?</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-sm">
            {{ Form::hidden('id', $cliente->id, ['id' => 'id']) }}
            {{ $cliente->cliente->nome . ' - ' . $cliente->cpf_cnpj }}
        </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Excluir', ['id' => 'form_delete_cliente_btn', 'class' => 'btn btn-danger']) }}
    </div>
</form>

<script>
    $(document).ready( function(){
        $(document).find("#cliente_nome_modal_delete").autocomplete(optionsAutoCompleteCliente('cliente_nome_modal_delete'));

        $(document).find('#form_delete_cliente_btn').on('click', function(event){
            event.stopPropagation();

            $.ajax({
                url: "{{ route('cliente_whitelist.excluir') }}",
                dataType: 'json',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: $(document).find("#id").val()
                },
                success: function(){
                    $(document).find('#modal_cliente_delete_whitelist').modal('hide');
                    filterAjax();
                },
                error: function(callback){
                    errors = callback.responseJSON.message;

                    for(var field in errors){
                        showErrorsInputs('#form_delete_cliente', field, errors[field]);
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
