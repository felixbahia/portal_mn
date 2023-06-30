@extends('layouts.page-dialog')

@section('content')

<form action="{{ route('banco_contabil.store') }}" id="frm_cad_estabelecimento" name="frm_cad_estabelecimento" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', $dados['id']) !!}
    <div class="form-group">
        {{ Form::label('estabelecimento', 'Estábelecimento') }}
        {!! Form::select("estabelecimento", $estabelecimentos, intval($dados['estabelecimento']), ["class"=>"form-control"]) !!}
    </div>
    <div class="form-group">
        {{ Form::label('uf', 'Estado') }}
        {!! Form::select("uf", $uf, $dados['uf'], ["class"=>"form-control"]) !!}
    </div>
    <div class="form-group">
        {{ Form::label('cidade', 'Cidade') }}
        {!! Form::select("cidade", $cidades, $dados['cidade'], ["class"=>"form-control"]) !!}
    </div>

    {{ Form::submit('Salvar', array('class' => 'btn btn-primary float-right')) }}
</form>
<script>
    $(function(){
        $(document).find('#uf').on('change', function(){
            $this = $(this);
            $.ajax({
                url: '{{ route("busca_cep.cidade") }}',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', estado: $this.val() },
                success: function(callback){
                    $(document).find('#cidade').html('');
                    if(callback.status == 'success'){
                        dados = callback.dados;
                        $html = '<option value=""></option>';
                        for(k in dados){
                            $html += '<option value="'+dados[k]+'">'+dados[k]+'</option>';
                        }
                        $(document).find('#cidade').html($html);
                    }
                }
            });
        });
        $(document).find('#frm_cad_estabelecimento').find('[type="submit"]').on("click", function(event){
            event.stopPropagation();
            var form = $(this).parents('form');
            var form_data = form.serialize();
            $.ajax({
                url: '{{ route("estabelecimento_cidade_fob.editar") }}',
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