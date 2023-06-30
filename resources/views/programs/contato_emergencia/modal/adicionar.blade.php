@extends('layouts.page-dialog')

@section('content')
<form id="form_add_contato_emergencia">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            <span class='campo_obrigatorio'>*</span>  {{ Form::label('nome', 'Nome Completo', []) }}
           <div class="input-group">
               {!! Form::text('nome', '', ['id' => 'nome', 'placeholder' => 'Nome Completo', 'class' => 'form-control input-label','maxlength' => '200']) !!}
           </div>

       </div>
       <div class="form-group col-sm-12"> 
        <span class='campo_obrigatorio'>*</span> {{ Form::label('telefone', 'Telefone') }}
            <div class="input-group">
                {!! Form::text('telefone', '', ['id' => 'telefone', 'placeholder' => 'telefone do Contato', 'class' => 'form-control telefone']) !!}
            </div>
        </div>
      <div class="form-group col-sm-12"> 
        <span class='campo_obrigatorio'>*</span> {{ Form::label('setor', 'Setor', []) }}
       <div class="input-group">
           {!! Form::text('setor', '', ['id' => 'setor', 'placeholder' => 'Setor', 'class' => 'form-control input-label','maxlength' => '150']) !!}
       </div>
    </div>
       <div class="form-group col-sm-12"> 
        <span class='campo_obrigatorio'>*</span> {{ Form::label('contato_emergencia', 'Contato  de Emergência', []) }}
       <div class="input-group">
           {!! Form::text('contato_emergencia', '', ['id' => 'contato_emergencia', 'placeholder' => 'Contato de Emergência', 'class' => 'form-control input-label','maxlength' => '30']) !!}
       </div>
    </div>
       <div class="form-group col-sm-12"> 
        <span class='campo_obrigatorio'>*</span> {{ Form::label('telefone_emergencia', 'Telefone de Emergência') }}
       <div class="input-group">
           {!! Form::text('telefone_emergencia', '', ['id' => 'telefone_emergencia', 'placeholder' => 'Telefone de Emergência', 'class' => 'form-control telefone']) !!}
       </div>
    </div>
       <div class="form-group col-sm-12"> 
        {{ Form::label('email', 'E-mail') }}
       <div class="input-group">
           {!! Form::text('email', '', ['id' => 'email', 'placeholder' => 'email do Contato', 'class' => 'form-control input-label','maxlength' => '200']) !!}
       </div>

   </div>
    </div>
    <div class="form-row float-right mt-3">
        {{ Form::button('Cadastrar', ['id' => 'form_add_contato_emergencia_btn', 'class' => 'btn btn-success']) }}
    </div>
</form>

<script>
    $(document).ready( function(){

        $(document).find('#form_add_contato_emergencia').find('.telefone').mask('(00) 00000-0000');
        

        $(document).find('#form_add_contato_emergencia_btn').on('click', function(event){
            event.stopPropagation();

            $.ajax({
                url: "{{ route('contato_emergencia.salvar') }}",
                dataType: 'json',
                method: 'POST', 
                data: $(document).find('#form_add_contato_emergencia').serialize(),
                success: function(callback){
                    if(callback.status === 'success'){
                        $(document).find('#modal_add_contato_emergencia').modal('hide');
                        filtro();
                    }
                },
                error: function(callback){
                    errors = callback.responseJSON.error;
                    for(var field in errors){
                        limparMesagemErroAdd();
                        showErrorsInputsAdd('#form_add_contato_emergencia', field, errors[field]);
                    }
                }
            })
        });
    });

    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_add_contato_emergencia");
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
