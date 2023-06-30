@extends('layouts.page-dialog')

@section('content')

<div class="container">
    <form action="" name="form_estado_gnre_add" id="form_estado_gnre_add" onsubmit="return false;">

        @csrf
    <div class="form-row">
            <div class="input-group">

                <div class="form-group col-sm-12">
                    <span class="campo_obrigatorio">*</span>{{ Form::label('estado', 'Estado') }}
                    {{ Form::select('estado', $estados, '', ['class' => 'form-control']) }}	    		
                </div>
            </div>
    
       <div class="input-group">

                <div class="form-group col-sm-12">
                    {{ Form::label('liminar', 'Liminar') }}
                    {!! Form::file('liminar', ['id'=>'liminar', 'class' => 'form-control', 'onchange'=>"this.parentNode.nextSibling.value = this.value", "style" => "height: 38px;"], null) !!}
                   
                </div>
        </div>
        <div class="col-sm-12 mt-5">
        {{ Form::submit('Cadastrar', array('class' => 'btn btn-success float-right', 'id' => 'btn-salvar')) }}
        </div> 
    </div>
</form>
</div>
<script>
    $(document).ready( function (event) {


   
        form_modal_add = $(document).find('#form_estado_gnre_add');
        $(document).find('#btn-salvar').on('click', function(event){
          
            event.stopPropagation();
            
            inserirDados(form_modal_add.serialize());
        });

        form_modal_add.find("#bt-bt-search-estado-busca").off('click');
        form_modal_add.find("#bt-search-estado-busca").on('click', function(){
            showModalEstado(form_modal_add,"Lista Estados");
        });
    });

    function inserirDados(data_form_modal_add){
        form_modal_add = $(document).find('#form_estado_gnre_add');
        var formData = new FormData($(document).find('#form_estado_gnre_add')[0]);

        $.ajax({
            url: "{{ route('estado_gnre.adicionar') }}", 
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal_add).parents('.modal').modal('hide');
                filtro();
            },
            error: function(callback){
                errors = callback.responseJSON.error;
                    for(var field in errors){

                     
                        limparMesagemErroAdd();
                  
                        showErrorsInputsAdd('#form_estado_gnre_add', field, errors[field]);
                    }
            }
        });
    }

    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_estado_gnre_add");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function showErrorsInputsAdd(form, input, message){
 
            var $input = $(form).find("#"+input);
            $input.parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.parent().children().addClass('error-input');


    }

</script>
@endsection