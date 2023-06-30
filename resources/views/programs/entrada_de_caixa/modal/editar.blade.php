@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('banco_contabil.store') }}" id="frm_cad_entrada_de_caixa" name="frm_cad_entrada_de_caixa" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $dados['id']) !!}
    <div class="form-group">
        {{ Form::label('estabelecimento', 'Estábelecimento') }}
        {!! Form::select("estabelecimento", $estabelecimentos, intval($dados['estabelecimento']), ["class"=>"form-control"]) !!}
    </div>
    <div class="form-group">
        {{ Form::label('data', 'data') }}
        {{ Form::text('data', $dados['data'], ['class' => 'form-control data']) }}
    </div>
    <div class="form-group">
        {{ Form::label('valor', 'valor') }}
        {{ Form::text('valor', $dados['valor_inicial'], ['class' => 'form-control valor']) }}
    </div>

    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}
</form>
<script>
    $(function(){
        $(document).find(".valor").maskMoney({thousands:'.', decimal:','});
        $('.data').mask('00/00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            autoHide: true
        });
        $(document).find('#frm_cad_entrada_de_caixa').find('[type="submit"]').on("click", function(event){
            event.stopPropagation();
            var form = $(this).parents('form');
            var form_data = form.serialize();
            $.ajax({
                url: '{{ route("entrada_de_caixa.editar") }}',
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