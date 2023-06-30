@extends('layouts.page-dialog')

@section('content')
<form id="form_edit_contato_emergencia">
    @csrf
    <div class="form-row">

            <div class="input-group">
                {{ Form::hidden('id', $dados['id'], ['id' => 'id']) }}
              
            </div>

        <div class="form-group col-sm-12"> 
            <span class='campo_obrigatorio'>*</span>  {{ Form::label('nome', 'Nome Completo', []) }}
           <div class="input-group">
               {!! Form::text('nome', $dados['nome'], ['id' => 'nome', 'placeholder' => 'Nome Completo', 'class' => 'form-control input-label','maxlength' => '200']) !!}
           </div>

       </div>
       <div class="form-group col-sm-12"> 
        <span class='campo_obrigatorio'>*</span> {{ Form::label('telefone', 'Telefone') }}
            <div class="input-group">
                {!! Form::text('telefone', $dados['telefone'], ['id' => 'telefone', 'placeholder' => 'telefone do Contato', 'class' => 'form-control telefone']) !!}
            </div>
        </div>
      <div class="form-group col-sm-12"> 
        <span class='campo_obrigatorio'>*</span> {{ Form::label('setor', 'Setor', []) }}
       <div class="input-group">
           {!! Form::text('setor', $dados['setor'], ['id' => 'setor', 'placeholder' => 'Setor', 'class' => 'form-control input-label','maxlength' => '100']) !!}
       </div>
    </div>
       <div class="form-group col-sm-12"> 
        <span class='campo_obrigatorio'>*</span> {{ Form::label('contato_emergencia', 'Contato  de Emergência', []) }}
       <div class="input-group">
           {!! Form::text('contato_emergencia',$dados['contato_emergencia'], ['id' => 'contato_emergencia', 'placeholder' => 'Contato de Emergência', 'class' => 'form-control input-label','maxlength' => '100']) !!}
       </div>
    </div>
       <div class="form-group col-sm-12"> 
        <span class='campo_obrigatorio'>*</span> {{ Form::label('telefone_emergencia', 'Telefone de Emergência') }}
       <div class="input-group">
           {!! Form::text('telefone_emergencia', $dados['telefone_emergencia'], ['id' => 'telefone_emergencia', 'placeholder' => 'Telefone de Emergência', 'class' => 'form-control telefone']) !!}
       </div>
    </div>
       <div class="form-group col-sm-12"> 
        {{ Form::label('email', 'E-mail') }}
       <div class="input-group">
           {!! Form::text('email',$dados['email'], ['id' => 'email', 'placeholder' => 'email do Contato', 'class' => 'form-control input-label','maxlength' => '200']) !!}
       </div>

   </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Salvar', ['id' => 'form_edit_contato_emergencia_btn', 'class' => 'btn btn-primary']) }}
    </div>
</form>

<script>
    $(document).ready( function(){
        $(document).find('#form_edit_contato_emergencia').find('.telefone').mask('(00) 00000-0000');
     
        $(document).find('#form_edit_contato_emergencia_btn').on('click', function(event){
            event.stopPropagation();

            $.ajax({
                url: "{{ route('contato_emergencia.editar') }}",
                dataType: 'json',
                method: 'POST', 
                data: $(document).find('#form_edit_contato_emergencia').serialize(),
                success: function(callback){
                    if(callback.status === 'success'){
                        $(document).find('#modal_edit_contato_emergencia').modal('hide');
                        filtro();
                    }
                },
                error: function(callback){
                    errors = callback.responseJSON.error;
                    for(var field in errors){
                        limparMesagemErroEdit();
                        showErrorsInputsEdit('#modal_edit_contato_emergencia', field, errors[field]);
                    }
                }
            })
        });
    });

    function limparMesagemErroEdit(){      
        var form_modal_edit = $("#form_edit_contato_emergencia");
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
