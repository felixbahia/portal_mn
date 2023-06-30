@extends('layouts.page-dialog')

@section('content')
<form id="form_edit_construcao">
    @csrf
    <div class="form-row">
        <div class="col-sm">
            <div class="input-group">
                {{ Form::hidden('id', $dados['id'], ['id' => 'id']) }}
                {{ Form::text('descricao', $dados['descricao'], ['id' => 'descricao_edit', 'class' => 'form-control input-label', 'placeholder' => 'Descrição', 'maxlength' => '30']) }}
            </div>
        </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Salvar', ['id' => 'form_edit_construcao_btn', 'class' => 'btn btn-primary']) }}
    </div>
</form>

<script>
    $(document).ready( function(){

        $(document).find('#form_edit_construcao_btn').on('click', function(event){
            event.stopPropagation();

            $.ajax({
                url: "{{ route('construcao.editar') }}",
                dataType: 'json',
                method: 'POST', 
                data: $(document).find('#form_edit_construcao').serialize(),
                success: function(callback){
                    if(callback.status === 'success'){
                        $(document).find('#modal_edit_construcao').modal('hide');
                        filtro();
                    }
                },
                error: function(callback){
                    errors = callback.responseJSON.error;
                    for(var field in errors){
                        limparMesagemErroEdit();
                        showErrorsInputsEdit('#form_edit_construcao', field, errors[field]);
                    }
                }
            })
        });
    });

    function limparMesagemErroEdit(){      
        var form_modal_edit = $("#form_edit_construcao");
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
