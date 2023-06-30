@extends('layouts.app-richtext')

@section('content')
<div class="conteudo_centralizado" style="margin-left: 25%">
    <div class="content-filter-dialog" style="width:820pt">
        <form action="#" name="form_assinatura" id="form_assinatura" onsubmit="return false">
            @csrf

            <div class="input-group">


                <div class="form-group col-sm-12">
                    {{ Form::label('nome_edit', 'Nome', []) }}
                    <div class="input-group">
                        {{ Form::text('nome_edit', $dados['NOME'], ['id' => 'nome_edit', 'class' => 'form-control input-label', 'placeholder' => 'Nome', 'maxlength' => '100']) }}
                    </div>
                    {{ Form::label('email', 'Email', []) }}
                    <div class="input-group">
                        {{ Form::text('email_edit', $dados['EMAIL'], ['id' => 'email_edit', 'class' => 'form-control input-label', 'placeholder' => 'Email', 'maxlength' => '100']) }}
                    </div>
                    {{ Form::label('telefone_edit', 'Telefone', []) }}
                    <div class="input-group">
                        {{ Form::text('telefone_edit', $dados['TELEFONE'], ['id' => 'telefone_edit', 'class' => 'form-control input-label', 'placeholder' => 'Telefone', 'maxlength' => '100']) }}
                    </div>

                    {{ Form::label('setor_edit', 'Setor', []) }}
                    <div class="input-group">
                        {{ Form::text('setor_edit', $dados['DEPARTAMENTO'], ['id' => 'setor_edit', 'class' => 'form-control input-label', 'placeholder' => 'Setor', 'maxlength' => '100']) }}
                    </div>

                </div>
            </div>
            <div class="col-sm-12">
                Intruções de como Adicionar Assinatura
                <a href="{{ $dados['link_thunderbird']}}" target="_blank">Thunderbird</a>
                ou
                <a href="{{ $dados['link_outlook']}}" target="_blank">Outlook</a>


            </div>
            <div class="form-row float-right mt-3">
                {{ Form::button('Atualizar', ['id' => 'atualiza', 'class' => 'btn btn-primary']) }}
            </div>
            <br><br><br>
            <div id="div_editor1" style="height:350px"></div>
        </form>
    </div>





</div>

@endsection

@section('script-footer')
    var editor1 = new RichTextEditor("#div_editor1");
    $(document).ready(function() {

        editor1.setReadOnly(true);

        $(document).find("#atualiza").off("click");
        $(document).find("#atualiza").on("click", function() {

            atualiza();
        });
    });


    function atualiza() {
        form_modal = $(document).find("#form_assinatura");
        var nome_edit = $(document).find("#nome_edit").val();
        var email_edit = $(document).find("#email_edit").val();
        var telefone_edit = $(document).find("#telefone_edit").val();
        var setor_edit = $(document).find("#setor_edit").val();

        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                nome_edit: nome_edit,
                email_edit: email_edit,
                telefone_edit: telefone_edit,
                setor_edit: setor_edit,
            },
            url: "{{ route('assinatura.atualiza') }}",
            success: function(data) {

                 editor1.setHTMLCode(data.response);
            },
            error: function(data){
            var dados = data.responseJSON;
            mensagemErro(dados, form_modal);
        }
        });

    }

    function mensagemErro(json_error, form_modal){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputs(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputs(form_modal, input, message){
        var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

@endsection
