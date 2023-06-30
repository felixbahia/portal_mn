@extends('layouts.page-dialog')

@section('content')
<form id="form_edit_incoterm">
    <div class="form-row">
        <div class="col-sm">
            <div class="input-group">
                {{ Form::hidden('id', $incoterm['id'], ['id' => 'id']) }}
                {{ Form::text('tipo', $incoterm['tipo'], ['id' => 'incoterm_edit', 'class' => 'form-control input-label', 'placeholder' => 'Tipo', 'maxlength' => '5']) }}
            </div>
        </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Alterar', ['id' => 'form_edit_incoterm_btn', 'class' => 'btn btn-primary float-right']) }}
    </div>
</form>

<script>
    $(document).ready( function(){

        $(document).find('#form_edit_incoterm_btn').on('click', function(event){
            event.stopPropagation();

            $.ajax({
                url: "{{ route('incoterm.editar') }}",
                dataType: 'json',
                method: 'POST', 
                data: {
                    _token: '{{ csrf_token() }}',
                    tipo: $(document).find("#incoterm_edit").val(),
                    id: $(document).find("#id").val(),
                },
                success: function(){
                    $(document).find('#modal_edit_incoterm').modal('hide');
                    filtro();
                },
                error: function(callback){
                    errors = callback.responseJSON.error;
                    
                    for(var field in errors){
                        limparMesagemErroEdit();
                        showErrorsInputsEdit('#form_edit_incoterm', field, errors[field]);
                    }
                }
            })
        });
    });

    function limparMesagemErroEdit(){      
        var form_modal_edit = $("#form_edit_incoterm");
        form_modal_edit.find('.error-message').remove();
        form_modal_edit.find('input, select, span').removeClass('error-input');
    }

    function showErrorsInputsEdit(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.parent().children().addClass('error-input');
    }

</script>
@endsection
