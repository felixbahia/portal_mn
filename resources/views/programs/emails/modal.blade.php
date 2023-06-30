@extends('layouts.page-dialog')

@section('content')
<form action="#" id="edicao_email" name="edicao_email" onsubmit="return false">
    @csrf
    {{ Form::hidden('id', $dados['id'], ['id' => 'id'])}}
    <div class="row">
        <div class="form-group col-md-3">
            {{ Form::label('enviado_por', 'Enviado Por') }}
            {{ Form::text('enviado_por', $dados['enviado_por'], ['class' => 'form-control']) }}
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="form-group col-md-12">
            {{ Form::label('emails_enviados', 'E-mails para envio') }}
            @foreach($dados['emails_enviados'] as $email)
            <div class="content-email">
            {{ Form::text('emails_enviados[]', $email, ['class' => 'form-control email_multiplo']) }}
            @if(!empty($email))
            <div class="btn-removeemail"></div>
            @else
            <div class="btn-addemail"></div>
            @endif
            </div>
            @endforeach
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-12">
            {{ Form::label('emails_copias', 'E-mails de cópia') }}
            @foreach($dados['emails_copias'] as $email)
            <div class="content-email">
            {{ Form::text('emails_copias[]', $email, ['class' => 'form-control email_multiplo']) }}
            @if(!empty($email))
            <div class="btn-removeemail"></div>
            @else
            <div class="btn-addemail"></div>
            @endif
            </div>
            @endforeach
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-12">
            {{ Form::label('emails_copias_oculta', 'E-mails de cópia oculta') }}
            @foreach($dados['emails_copias_oculta'] as $email)
            <div class="content-email">
            {{ Form::text('emails_copias_oculta[]', $email, ['class' => 'form-control email_multiplo']) }}
            @if(!empty($email))
            <div class="btn-removeemail"></div>
            @else
            <div class="btn-addemail"></div>
            @endif
            </div>
            @endforeach
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="form-group col-md-12">
        {{ Form::label('assunto', 'Assunto') }}
        {{ Form::text('assunto', $dados['assunto'], ['class' => 'form-control']) }}
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-8">
            {{ Form::label('conteudo', 'Conteudo') }}
            {!! Form::textarea('conteudo', $dados['conteudo'], ['class' => 'form-control']) !!}
        </div>
        <div class="form-group col-md-4">
            {{ Form::label('conteudo', 'Variáveis') }}
            <div>
                {!! $dados['template_variavies'] !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            {{ Form::button('Salvar', ['id' => 'btn_salvar', 'class' => 'btn btn-success', 'onclick'=>'salvarDados()']) }}
        </div>
    </div>
</form>

<script type="text/javascript">
    $(document).ready(function() {
        funcoesCampos();
        CKEDITOR.replace('conteudo', {
            language: 'pt-br',
            enterMode: CKEDITOR.ENTER_BR,
            shiftEnterMode: CKEDITOR.ENTER_BR
        });
    });
    function funcoesCampos(){
        $(document).find('.email_multiplo').off('keyup');
        $(document).find('.email_multiplo').on('keyup', function(event) {
            event.stopPropagation();
            if (event.which == 13){
                criarNovoCampo($(this), event);
                return false;
            }
        });
        $(document).find(".btn-removeemail").off("click");
        $(document).find(".btn-removeemail").on("click", function(){
            var $this = $(this).parent().find('input');
            removeInput($this);
        });
        $(document).find(".btn-addemail").off("click");
        $(document).find(".btn-addemail").on("click", function(){
            var $this = $(this).parent().find('input');
            createNewInput($this);
        });
    }

    function createNewInput($this){
        $content = $this.parent().parent();
        var input = $content.find('input').last();
        if(input.val() != ''){
            var input_new = $(input).parent().clone().appendTo($(input).parent().parent());
            input_new.find('input').val('').focus();
            $(input).parent().find('.btn-addemail').attr('class', 'btn-removeemail');
            funcoesCampos();
        }
    }
    function removeInput($this){
        $content = $this.parent().parent();
        if($content.find('input').length > 1){
            $this.parent().remove();
            funcoesCampos();
        }else{
            $this.val("").focus();
            $this.parent().find(".btn-removeemail").attr('class', 'btn-removeemail');
            funcoesCampos();
        }
    }

    function criarNovoCampo($this, event){
        if ($($this).val() != ''){
            createNewInput($this);
        }else{
            var campos = $(document).find(".email_multiplo:visible");
            var indice = campos.index(event.target) + 1;
            var seletor = $(campos[indice]).focus();
            if (seletor.length == 0) {
                event.target.focus();
            }
        }
    }
    function salvarDados(){
        var form = $('#edicao_email');
        form.find('[name="conteudo"]').val(CKEDITOR.instances.conteudo.getData());
        var form_data = form.serialize();
        $.ajax({
            url: "{{ route('email.editar') }}",
            dataType: 'json',
            data: form_data,
            method: 'POST',
            success: function(callback){
                if(callback.status === "success"){
                    $(form).parents('.modal').modal('hide');
                    filterAjax($("#form_filter").serialize());
                    message("Atenção", "Dados salvos com sucesso!");
                }else{
                    message("Anteção", callback.message);
                }
            },
            error: function(callback){
                if(callback.responseJSON.status === "error"){
                    message("Anteção", callback.responseJSON.message);
                    return false;
                }else{
                    var errors = callback.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }
            }
        });
    }

    function showErrorsInputs(form, input, message){
        var inputexplode = input.split(".");
        if(inputexplode.length > 1){
            input = inputexplode[0]+"[]";
            var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']").parent();
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }else{
            var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
    }

</script>
@endsection