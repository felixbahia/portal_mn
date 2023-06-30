@extends('layouts.page-dialog')

@section('content')

<div class="container">
 <form action="" name="form_estado_gnre_edit" id="form_estado_gnre_edit" onsubmit="return false;">
        @csrf
     <div class="form-row">
        <div class="input-group">
            {{ Form::hidden('id', $dados['id'], ['id' => 'id']) }}
            <div class="form-group col-sm-12">
                <span class="campo_obrigatorio">*</span>{{ Form::label('estado', 'Estado') }}
                {{ Form::select('estado', $estados,  $dados['estado'], ['class' => 'form-control']) }}	    		
            </div>
        </div>

   <div class="input-group">

            <div class="form-group col-sm-12">
               
                @if(!empty($dados['liminar']))
                
                    <div class="form-group col-sm-12">
                    <a href="{{ asset($dados['liminar']) }}" target="_blank"><i class="fas fa-file-alt"></i>Liminar</a>
                
                    </div>
                @endif

                {{ Form::label('liminar', 'Liminar') }}
                {!! Form::file('liminar', ['id'=>'liminar', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;"], null) !!}
               
            </div>
    </div>
   
    
    <div class="col-sm-12 mt-5">
        {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
</div> 
<script>
    $(document).ready( function (event) {

     
   
        form_modal_edit = $(document).find('#form_estado_gnre_edit');
        $(document).find('#btn-salvar').on('click', function(event){
            event.stopPropagation();
    
            atualizaDados(form_modal_edit.serialize());
        });


     
    });

    function atualizaDados(data_form_modal_edit){
        form_modal_edit = $(document).find('#form_estado_gnre_edit');
        var formData = new FormData($(document).find('#form_estado_gnre_edit')[0]);
        $.ajax({
            url: "{{ route('estado_gnre.editar') }}", 
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            method: 'POST',
            async: false,
            success: function(callback){
       
                    $(form_modal_edit).parents('.modal').modal('hide');
                    filtro();
                
            },
            error: function(callback){
                errors = callback.responseJSON.error;

                for(var field in errors){
                    limparMesagemErroEdit();
                    showErrorsInputsEdit('#form_estado_gnre_edit', field, errors[field]);
                }
            }
        });
    }

    function limparMesagemErroEdit(){    
      
        var form_modal_edit = $("#form_estado_gnre_edit");
        form_modal_edit.find('.error-message').remove();
        form_modal_edit.find('input, select, span').removeClass('error-input');
    }

    function showErrorsInputsEdit(form, input, message){
        
       
            var $input = $(form).find("#"+input);
            $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.parent().children().addClass('error-input');


    }

</script>
@endsection