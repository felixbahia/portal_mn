@extends('layouts.page-dialog')

@section('content')
<form id="form_add_familia">
    @csrf
    <div class="form-row">
        <div class="col-sm">
            <div class="input-group">
                {{ Form::text('descricao', '', ['id' => 'descricao_add', 'class' => 'form-control input-label', 'placeholder' => 'Descrição', 'maxlength' => '50']) }}
            </div>
        </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Cadastrar', ['id' => 'form_add_familia_btn', 'class' => 'btn btn-success']) }}
    </div>
</form>

<script>
    $(document).ready( function(){

        $(document).find('#form_add_familia_btn').on('click', function(event){
            event.stopPropagation();

            $.ajax({
                url: "{{ route('familia.salvar') }}",
                dataType: 'json',
                method: 'POST', 
                data: $(document).find('#form_add_familia').serialize(),
                success: function(callback){
                    if(callback.status === 'success'){
                        $(document).find('#modal_add_familia').modal('hide');
                        filtro();
                    }
                },
                error: function(callback){
                    errors = callback.responseJSON.error;
                    for(var field in errors){
                        limparMesagemErroAdd();
                        showErrorsInputsAdd('#form_add_familia', field, errors[field]);
                    }
                }
            })
        });
    });

    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_add_familia");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function showErrorsInputsAdd(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.parent().children().addClass('error-input');
    }

</script>
@endsection
