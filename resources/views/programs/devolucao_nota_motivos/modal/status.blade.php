@extends('layouts.page-dialog')

@section('content')
<div class="row">
    <div class="col-sm-6">
        Status vinculados
        <form action="#" id='form-motivo-status' onsubmit="return false;">
            @csrf
            {!! Form::hidden('id', $id) !!}
            <div class="row border rounded m-1 p-1">
                <div class="col status" id="vinculados">
                    <div class="row placeholder border rounded p-1">
                        <div class="col">
                            Coloque os status aqui
                        </div>
                    </div>
                    @foreach($vinculados as $id_status => $status)
                    <div class="row lista border rounded p-1">
                        <div class='col'>
                            {!! Form::hidden('id_status[]', $id_status) !!}
                            {!! $status !!}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </form>
    </div>
    <div class="col-sm-6">
        Status cadastrados
        <div class="row border rounded m-1 p-1">
            <div class="col status" id="cadastrados">
                <div class="row placeholder border rounded p-1">
                    <div class="col">
                        Coloque os status aqui
                    </div>
                </div>
                @foreach($cadastrados as $id_status => $status)
                <div class="row lista border rounded p-1">
                    <div class='col'>
                        {!! Form::hidden('id_status[]', $id_status) !!}
                        {!! $status !!}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col text-right mt-3">
        {!! Form::button('Enviar', ['class' => 'btn btn-success', 'id' => 'btn-enviar-status']) !!}
    </div>
</div>

<script>
    
    $(document).ready(function(){
        $(document).find('#btn_enviar').on('click', function(){
            salvarStatus();
        });

        if($(document).find('#vinculados').find('.lista').length > 0){
            $(document).find('#vinculados').find('.placeholder').hide();
        }

        if($(document).find('#cadastrados').find('.lista').length > 0){
            $(document).find('#cadastrados').find('.placeholder').hide();
        }

        $('#vinculados, #cadastrados').sortable({
            connectWith: ".status",
            items: '.lista',
            over: function() {
                $(this).find('.placeholder').hide();
            },
            stop: function(event, ui) {
                if($(event.target).find('.lista').length > 0){
                    $(event.target).find('.placeholder').hide();
                }
                else{
                    $(event.target).find('.placeholder').show();
                }
            },
            receive( event, ui ){
                if($(event.target).find('.lista').length > 0){
                    $(event.target).find('.placeholder').hide();
                }
                else{
                    $(event.target).find('.placeholder').show();
                }                
            }
        }).disableSelection();

        $(document).find('#btn-enviar-status').on('click', function(){
            salvarStatus();
        });
    });

    function salvarStatus(){

        var form = $(document).find('#form-motivo-status');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route("devolucao_nota_motivo.salvar.status") }}',
            dataType: 'json',
            method: 'POST',
            data: form.serialize(),
            success: function(data){
                message('Atenção', 'Status salvos com sucesso')
                $(document).find('#modal-motivo-status').modal('hide');
                buscarMotivos();
            },
            error: function(callback){
                errors = callback.responseJSON.error;

                for(var field in errors){
                    showErrorsInputsModalEditar(form, field, errors[field]);
                }
            }
        })
    }

    function showErrorsInputsModalEditar(form, input, message){
        var $input = form.find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection