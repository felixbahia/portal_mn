@extends('layouts.page-dialog')

@section('content')

<form action="#" id="frm_cad_cliente_limite_credito" name="frm_cad_cliente_limite_credito" onsubmit="return false;">
    @csrf
    <div class="form-group">
        {{ Form::label('data', 'Data') }}
        {{ Form::text('data', date('d/m/Y'), ['class' => 'form-control data', 'disabled']) }}
    </div>
    <div class="form-group">
        {{ Form::label('raiz_cnpj', 'Cliente raiz CNPJ') }}
        {{ Form::text('raiz_cnpj', $raiz_cnpj, ['class' => 'form-control raiz_cnpj', 'placeholder' => '00.000.000', 'readonly']) }}
    </div>
    <div class="form-group">
        {{ Form::label('valor', 'valor') }}
        {{ Form::text('valor', '', ['class' => 'form-control valor', 'placeholder' => '0,00']) }}
    </div>
    
    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}
</form>
<script>
    $(function(){
        $(document).find(".valor").maskMoney({thousands:'.', decimal:','});
        $(document).find(".raiz_cnpj").mask('99.999.999');
        $(document).find('#frm_cad_cliente_limite_credito').find('[type="submit"]').on("click", function(event){
            event.stopPropagation();
            var form = $(this).parents('form');
            var form_data = form.serialize();
            $.ajax({
                url: '{{ route("cliente.limite.adicionar") }}',
                dataType: 'json',
                data: form_data,
                method: 'POST',
                success: function(data){
                    $(form).parents('.modal').modal('hide');
                    filterAjax($("#form_filter").serialize());
                },
                error: function(data){
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }
            });
        });
    });
    
    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
    
</script>
@endsection